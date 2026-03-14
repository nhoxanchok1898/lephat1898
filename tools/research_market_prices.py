#!/usr/bin/env python
"""Build a market-pricing report for WooCommerce products."""

from __future__ import annotations

import argparse
import hashlib
import html
import json
import math
import re
import sys
import unicodedata
import xml.etree.ElementTree as ET
from collections import Counter, defaultdict
from dataclasses import dataclass, field
from difflib import SequenceMatcher
from pathlib import Path
from typing import Any

import requests


ROOT = Path(__file__).resolve().parents[1]
DEFAULT_CATALOG = ROOT / "tmp-catalog-audit.json"
DEFAULT_OUTPUT = ROOT / "tmp-market-pricing.json"
CACHE_DIR = ROOT / "tmp-market-cache"
HEADERS = {"User-Agent": "Mozilla/5.0"}
TARGET_BRANDS = {"dulux", "maxilite", "weber"}
IGNORED_TOKENS = {
    "2023",
    "2024",
    "2025",
    "2026",
    "bao",
    "bao-gia",
    "be-mat",
    "be",
    "ben",
    "bot",
    "cao",
    "cap",
    "chat",
    "chinh",
    "chon",
    "chong",
    "cong",
    "cu",
    "dai-ly",
    "dan",
    "de",
    "dulux",
    "gia",
    "goi",
    "hang",
    "hau",
    "hieu",
    "huong",
    "kg",
    "loai",
    "lot",
    "maxilite",
    "moi",
    "mua",
    "ngoai",
    "nhat",
    "noi",
    "nuoc",
    "pha",
    "pham",
    "phu",
    "rieng",
    "sac",
    "san",
    "se",
    "sieu",
    "son",
    "tai",
    "tham",
    "that",
    "thi",
    "thu",
    "trong",
    "tu",
    "tuong",
    "uy",
    "va",
    "vi",
    "weber",
}


def log(message: str) -> None:
    print(message, file=sys.stderr)


def strip_accents(value: str) -> str:
    normalized = unicodedata.normalize("NFD", value)
    return "".join(char for char in normalized if unicodedata.category(char) != "Mn")


def normalize_space(value: str) -> str:
    return re.sub(r"\s+", " ", value or "").strip()


def clean_html_text(value: str) -> str:
    text = re.sub(r"<(script|style)[^>]*>.*?</\1>", " ", value, flags=re.I | re.S)
    text = re.sub(r"<[^>]+>", " ", text)
    return normalize_space(html.unescape(text))


def tokenize(value: str) -> list[str]:
    ascii_text = strip_accents(html.unescape(value or "")).lower()
    ascii_text = re.sub(r"[^a-z0-9]+", " ", ascii_text)
    return [token for token in ascii_text.split() if token]


def normalize_name(value: str) -> str:
    return " ".join(tokenize(value))


def significant_tokens(value: str) -> set[str]:
    tokens = set(tokenize(value))
    return {
        token
        for token in tokens
        if token not in IGNORED_TOKENS and not token.isdigit() and len(token) > 1
    }


def format_number_token(raw: str) -> str:
    if "." in raw:
        raw = raw.rstrip("0").rstrip(".")
    return raw


def canonical_pack_label(raw: str) -> str | None:
    if not raw:
        return None

    value = strip_accents(html.unescape(raw)).lower()
    value = normalize_space(value.replace(",", "."))

    match = re.search(r"(\d+(?:\.\d+)?)\s*m\s*x\s*(\d+(?:\.\d+)?)\s*m", value)
    if match:
        first = format_number_token(match.group(1))
        second = format_number_token(match.group(2))
        return f"{first}m x {second}m"

    for unit in ("ml", "kg", "l", "g"):
        match = re.search(r"(\d+(?:\.\d+)?)\s*" + unit + r"\b", value)
        if not match:
            continue
        amount = format_number_token(match.group(1))
        suffix = unit.upper() if unit == "l" else unit
        return f"{amount}{suffix}"

    return None


def parse_money_number(raw: str) -> int:
    value = html.unescape(raw or "").strip().lower().replace("\xa0", " ")
    value = value.replace("vnd", "").replace("vnđ", "").replace("đ", "")
    value = normalize_space(value)

    if value.endswith("k"):
        digits = re.sub(r"[^0-9.]", "", value[:-1])
        if digits:
            return int(round(float(digits) * 1000))

    digits = re.sub(r"[^0-9]", "", value)
    return int(digits) if digits else 0


def request_text(url: str) -> str:
    CACHE_DIR.mkdir(parents=True, exist_ok=True)
    cache_path = CACHE_DIR / f"{hashlib.sha256(url.encode('utf-8')).hexdigest()}.txt"
    if cache_path.exists():
        return cache_path.read_text(encoding="utf-8", errors="ignore")

    response = requests.get(url, headers=HEADERS, timeout=45)
    response.raise_for_status()
    response.encoding = response.encoding or "utf-8"
    text = response.text
    cache_path.write_text(text, encoding="utf-8")
    return text


def parse_xml_urls(url: str, namespace: str = "http://www.sitemaps.org/schemas/sitemap/0.9") -> list[str]:
    xml_text = request_text(url).lstrip()
    root = ET.fromstring(xml_text)
    ns = {"sm": namespace}
    return [node.text.strip() for node in root.findall(".//sm:loc", ns) if node.text]


def extract_meta_content(text: str, property_name: str) -> str:
    match = re.search(
        r'<meta[^>]+(?:property|name)="%s"[^>]+content="(.*?)"' % re.escape(property_name),
        text,
        flags=re.I | re.S,
    )
    return normalize_space(html.unescape(match.group(1))) if match else ""


def extract_h1(text: str) -> str:
    match = re.search(r"<h1[^>]*>(.*?)</h1>", text, flags=re.I | re.S)
    return normalize_space(clean_html_text(match.group(1))) if match else ""


def extract_title(text: str) -> str:
    match = re.search(r"<title>(.*?)</title>", text, flags=re.I | re.S)
    return normalize_space(clean_html_text(match.group(1))) if match else ""


def extract_row_pack_prices(text: str, title: str) -> dict[str, int]:
    title_tokens = significant_tokens(title)
    if not title_tokens:
        return {}

    prices: dict[str, int] = {}
    for row_html in re.findall(r"<tr[^>]*>(.*?)</tr>", text, flags=re.I | re.S):
        row_text = clean_html_text(row_html)
        row_tokens = significant_tokens(row_text)
        if len(title_tokens & row_tokens) < max(1, min(2, len(title_tokens))):
            continue

        pack_matches = re.findall(r"\d+(?:[\.,]\d+)?\s*(?:kg|l|ml|g)\b", row_text, flags=re.I)
        price_matches = re.findall(r"\b[0-9][0-9\.,]{3,}\b", row_text)
        if not pack_matches or not price_matches:
            continue

        pack = canonical_pack_label(pack_matches[0])
        price = parse_money_number(price_matches[-1])
        if pack and price > 0:
            current = prices.get(pack)
            prices[pack] = price if current is None else min(current, price)
    return prices


@dataclass
class Offer:
    brand: str
    source: str
    url: str
    title: str
    title_normalized: str
    tokens: set[str]
    prices: dict[str, int] = field(default_factory=dict)


def make_offer(brand: str, source: str, url: str, title: str, prices: dict[str, int]) -> Offer | None:
    clean_prices = {label: int(price) for label, price in prices.items() if int(price) > 0}
    if not clean_prices:
        return None

    title = normalize_space(title or url)
    return Offer(
        brand=brand,
        source=source,
        url=url,
        title=title,
        title_normalized=normalize_name(title),
        tokens=set(tokenize(title)),
        prices=clean_prices,
    )


def brand_from_text(*values: str) -> str:
    haystack = " ".join(tokenize(" ".join(values)))
    for brand in TARGET_BRANDS:
        if brand in haystack:
            return brand
    return ""


def extract_sonthanhcong_offer(url: str) -> Offer | None:
    text = request_text(url)
    title = extract_h1(text) or extract_meta_content(text, "og:title") or extract_title(text)
    brand = brand_from_text(title, url)
    if brand not in TARGET_BRANDS:
        return None

    prices: dict[str, int] = {}
    variations_match = re.search(r'data-product_variations="(.*?)"', text, flags=re.S)
    if variations_match:
        payload = html.unescape(variations_match.group(1))
        try:
            variations = json.loads(payload)
        except json.JSONDecodeError:
            variations = []
        for variation in variations:
            attributes = variation.get("attributes") or {}
            labels = [str(value) for value in attributes.values() if value]
            label = " ".join(labels) or str(variation.get("sku") or variation.get("variation_id") or "")
            pack = canonical_pack_label(label)
            price = int(round(float(variation.get("display_price") or variation.get("display_regular_price") or 0)))
            if pack and price > 0:
                current = prices.get(pack)
                prices[pack] = price if current is None else min(current, price)

    if not prices:
        price_match = re.search(
            r'<p class="price[^"]*product-page-price[^"]*">.*?<bdi>([0-9\.,]+)',
            text,
            flags=re.I | re.S,
        )
        if price_match:
            price = parse_money_number(price_match.group(1))
            if price > 0:
                pack = canonical_pack_label(title)
                prices[pack or "__default__"] = price

    for pack, price in extract_row_pack_prices(text, title).items():
        current = prices.get(pack)
        prices[pack] = price if current is None else min(current, price)

    return make_offer(brand, "sonthanhcong.com", url, title, prices)


def extract_tavaco_offer(url: str) -> Offer | None:
    text = request_text(url)
    title = extract_meta_content(text, "og:title") or extract_title(text)
    brand = brand_from_text(title, url)
    if brand not in TARGET_BRANDS:
        return None

    prices: dict[str, int] = {}
    plain_text = clean_html_text(text)

    for pack_raw, price_raw in re.findall(
        r"<tr[^>]*>\s*<td[^>]*>.*?<strong>([^<]+)</strong>.*?</td>\s*<td[^>]*>([0-9\.,]+)\s*(?:VNĐ|đ)",
        text,
        flags=re.I | re.S,
    ):
        pack = canonical_pack_label(pack_raw)
        price = parse_money_number(price_raw)
        if pack and price > 0:
            current = prices.get(pack)
            prices[pack] = price if current is None else min(current, price)

    for pack_raw, price_raw in re.findall(
        r"(?:Thùng|Bao|Can)\s*(\d+(?:[\.,]\d+)?\s*(?:L|KG|kg|l))\s*:\s*([0-9\.,]+)\s*VNĐ",
        plain_text,
        flags=re.I,
    ):
        pack = canonical_pack_label(pack_raw)
        price = parse_money_number(price_raw)
        if pack and price > 0:
            current = prices.get(pack)
            prices[pack] = price if current is None else min(current, price)

    for pack_raw, low_raw, _high_raw in re.findall(
        r"(?:bao|thùng|can)\s*(\d+(?:[\.,]\d+)?\s*(?:kg|l))[^.]{0,80}?giá tham khảo:\s*([0-9\.,]+)\s*-\s*([0-9\.,]+)",
        plain_text,
        flags=re.I,
    ):
        pack = canonical_pack_label(pack_raw)
        price = parse_money_number(low_raw)
        if pack and price > 0:
            current = prices.get(pack)
            prices[pack] = price if current is None else min(current, price)

    description = extract_meta_content(text, "description")
    for price_raw, pack_raw in re.findall(r"([0-9]+(?:\.[0-9]{3})?)k\s*/\s*([0-9]+(?:[\.,][0-9]+)?\s*L)", description, flags=re.I):
        pack = canonical_pack_label(pack_raw)
        price = parse_money_number(f"{price_raw}k")
        if pack and price > 0:
            current = prices.get(pack)
            prices[pack] = price if current is None else min(current, price)

    if not prices:
        for price_raw in re.findall(r"Giá:\s*([0-9\.,]+)\s*VNĐ", plain_text, flags=re.I):
            price = parse_money_number(price_raw)
            if price > 0:
                pack = canonical_pack_label(title)
                prices[pack or "__default__"] = price
                break

    for pack, price in extract_row_pack_prices(text, title).items():
        current = prices.get(pack)
        prices[pack] = price if current is None else min(current, price)

    return make_offer(brand, "tavaco.vn", url, title, prices)


def extract_topto_offer(url: str) -> Offer | None:
    text = request_text(url)
    title = extract_h1(text) or extract_meta_content(text, "og:title") or extract_title(text)
    brand = brand_from_text(title, url)
    if brand not in TARGET_BRANDS:
        return None

    prices: dict[str, int] = {}
    for price_raw in re.findall(r'"price":"?([0-9\.]+)"?', text):
        price = parse_money_number(price_raw)
        pack = canonical_pack_label(title)
        if price > 0 and pack:
            current = prices.get(pack)
            prices[pack] = price if current is None else min(current, price)

    if not prices:
        price_match = re.search(r'<span class="woocommerce-Price-amount amount"><bdi>([0-9\.,]+)', text, flags=re.I)
        if price_match:
            price = parse_money_number(price_match.group(1))
            if price > 0:
                prices[canonical_pack_label(title) or "__default__"] = price

    return make_offer(brand, "topto.vn", url, title, prices)


def extract_dtl_offer(url: str) -> Offer | None:
    text = request_text(url)
    title = extract_meta_content(text, "og:title") or extract_title(text)
    brand = brand_from_text(title, url)
    if brand not in TARGET_BRANDS:
        return None

    prices: dict[str, int] = {}
    variants_match = re.search(r'"variants":(\[.*?\]),"featured_image"', text, flags=re.S)
    if variants_match:
        try:
            variants = json.loads(variants_match.group(1))
        except json.JSONDecodeError:
            variants = []
        for variant in variants:
            label = str(variant.get("title") or variant.get("option1") or variant.get("barcode") or "")
            pack = canonical_pack_label(label) or canonical_pack_label(title)
            price = int(round(float(variant.get("price") or 0)))
            if pack and price > 0:
                current = prices.get(pack)
                prices[pack] = price if current is None else min(current, price)

    if not prices:
        price_match = re.search(r'<meta property="og:price:amount" content="([0-9\.,]+)"', text, flags=re.I)
        if price_match:
            price = parse_money_number(price_match.group(1))
            if price > 0:
                prices[canonical_pack_label(title) or "__default__"] = price

    return make_offer(brand, "dtlgroup.vn", url, title, prices)


def extract_phugiacongtrinh_offer(url: str) -> Offer | None:
    text = request_text(url)
    title = extract_h1(text) or extract_meta_content(text, "og:title") or extract_title(text)
    brand = brand_from_text(title, url)
    if brand not in TARGET_BRANDS:
        return None

    prices: dict[str, int] = {}
    for price_raw in re.findall(r'"price":"?([0-9\.]+)"?', text):
        price = parse_money_number(price_raw)
        if price <= 0:
            continue
        pack = canonical_pack_label(title)
        prices[pack or "__default__"] = price

    return make_offer(brand, "phugiacongtrinh.com", url, title, prices)


def extract_chongthamsonphat_offer(url: str) -> Offer | None:
    text = request_text(url)
    title = extract_h1(text) or extract_title(text)
    brand = brand_from_text(title, url)
    if brand not in TARGET_BRANDS:
        return None

    prices: dict[str, int] = {}
    price_match = re.search(r'<p class="price-new">\s*([0-9\.,]+)\s*VNĐ', text, flags=re.I | re.S)
    if price_match:
        price = parse_money_number(price_match.group(1))
        if price > 0:
            prices[canonical_pack_label(title) or "__default__"] = price

    return make_offer(brand, "chongthamsonphat.com.vn", url, title, prices)


def extract_tatmart_offer(url: str) -> Offer | None:
    text = request_text(url)
    title = extract_meta_content(text, "og:title") or extract_title(text)
    brand = brand_from_text(title, url)
    if brand not in TARGET_BRANDS:
        return None

    prices: dict[str, int] = {}
    price_match = re.search(r'"price">\s*<bdi\s*><span>([0-9\.,]+)</span>', text, flags=re.I)
    if price_match:
        price = parse_money_number(price_match.group(1))
        if price > 0:
            prices[canonical_pack_label(title) or "__default__"] = price

    return make_offer(brand, "tatmart.com", url, title, prices)


def parse_product_pack_map(product: dict[str, Any]) -> dict[str, str]:
    mapping: dict[str, str] = {}
    for field_name in ("capacity_list", "weight_list", "pack_list"):
        for item in (product.get(field_name) or "").split("|"):
            label = normalize_space(item)
            canonical = canonical_pack_label(label)
            if canonical and canonical not in mapping:
                mapping[canonical] = label
    return mapping


def build_offers() -> list[Offer]:
    offers: list[Offer] = []

    source_specs = [
        (
            "https://sonthanhcong.com/product-sitemap.xml",
            lambda url: any(brand in url.lower() for brand in ("dulux", "maxilite")),
            extract_sonthanhcong_offer,
        ),
        (
            "https://tavaco.vn/sitemap-vi.san-pham.xml",
            lambda url: any(brand in url.lower() for brand in ("dulux", "maxilite")),
            extract_tavaco_offer,
        ),
        (
            "https://topto.vn/product-sitemap.xml",
            lambda url: "weber" in url.lower(),
            extract_topto_offer,
        ),
        (
            "https://dtlgroup.vn/sitemap_products_1.xml",
            lambda url: "weber" in url.lower(),
            extract_dtl_offer,
        ),
        (
            "https://phugiacongtrinh.com/product-sitemap.xml",
            lambda url: "weber" in url.lower(),
            extract_phugiacongtrinh_offer,
        ),
        (
            "https://chongthamsonphat.com.vn/sitemap.xml",
            lambda url: "weber" in url.lower(),
            extract_chongthamsonphat_offer,
        ),
        (
            "https://www.tatmart.com/sitemap_products.xml",
            lambda url: "weber" in url.lower() and "/en/" not in url.lower(),
            extract_tatmart_offer,
        ),
    ]

    for sitemap_url, predicate, extractor in source_specs:
        log(f"Loading sitemap: {sitemap_url}")
        namespace = "https://www.sitemaps.org/schemas/sitemap/0.9" if "chongthamsonphat" in sitemap_url else "http://www.sitemaps.org/schemas/sitemap/0.9"
        urls = [url for url in parse_xml_urls(sitemap_url, namespace=namespace) if predicate(url)]
        log(f"  candidate URLs: {len(urls)}")
        for index, url in enumerate(urls, start=1):
            try:
                offer = extractor(url)
            except Exception as exc:  # pragma: no cover - runtime diagnostics only
                log(f"  skip {url} ({exc})")
                continue
            if offer:
                offers.append(offer)
            if index % 20 == 0:
                log(f"  processed {index}/{len(urls)}")

    deduped: dict[tuple[str, str, str], Offer] = {}
    for offer in offers:
        key = (offer.source, offer.url, offer.title)
        if key not in deduped:
            deduped[key] = offer
            continue
        for label, price in offer.prices.items():
            current = deduped[key].prices.get(label)
            deduped[key].prices[label] = price if current is None else min(current, price)
    return list(deduped.values())


def compute_token_idf(products: list[dict[str, Any]], offers: list[Offer]) -> dict[str, float]:
    documents: list[set[str]] = []
    for product in products:
        documents.append(set(tokenize(" ".join([product.get("name", ""), product.get("source_url", ""), product.get("slug", "")]))))
    for offer in offers:
        documents.append(set(offer.tokens))

    frequencies = Counter()
    for document in documents:
        for token in document:
            frequencies[token] += 1

    total_docs = max(len(documents), 1)
    weights: dict[str, float] = {}
    for token, count in frequencies.items():
        if token in IGNORED_TOKENS:
            continue
        if count / total_docs >= 0.22:
            continue
        weights[token] = 1.0 + math.log((1 + total_docs) / (1 + count))
    return weights


def score_offer(product: dict[str, Any], offer: Offer, token_weights: dict[str, float]) -> tuple[float, dict[str, Any]]:
    product_tokens = set(tokenize(" ".join([product.get("name", ""), product.get("source_url", ""), product.get("slug", "")])))
    shared = sorted(token for token in product_tokens & offer.tokens if token in token_weights)
    score = sum(token_weights.get(token, 0.0) for token in shared)

    product_norm = normalize_name(" ".join([product.get("name", ""), product.get("source_url", "")]))
    ratio = SequenceMatcher(None, product_norm, offer.title_normalized).ratio()
    score += ratio * 2.2

    product_pack_map = parse_product_pack_map(product)
    offer_packs = {label for label in offer.prices if not label.startswith("__")}
    if product_pack_map and offer_packs & set(product_pack_map):
        score += 1.8

    return score, {
        "ratio": ratio,
        "shared_tokens": shared,
    }


def resolve_prices_for_product(product: dict[str, Any], offers: list[Offer], token_weights: dict[str, float]) -> dict[str, Any]:
    brand = (product.get("brand") or "").strip().lower()
    product_pack_map = parse_product_pack_map(product)
    candidate_rows = []

    for offer in offers:
        if offer.brand != brand:
            continue
        score, details = score_offer(product, offer, token_weights)
        if score <= 1.5:
            continue
        candidate_rows.append(
            {
                "offer": offer,
                "score": round(score, 4),
                "details": details,
            }
        )

    candidate_rows.sort(key=lambda row: (-row["score"], row["offer"].url))
    if not candidate_rows:
        return {
            "status": "unmatched",
            "reason": "no_candidate_offer",
            "candidates": [],
            "resolved_price_map": {},
            "base_price": 0,
        }

    top_score = candidate_rows[0]["score"]
    filtered_rows = [row for row in candidate_rows if row["score"] >= max(2.2, top_score - 1.4)]
    market_prices: dict[str, dict[str, Any]] = {}

    if product_pack_map:
        for row in filtered_rows:
            offer = row["offer"]
            for pack, price in offer.prices.items():
                if pack.startswith("__") or pack not in product_pack_map:
                    continue
                target_label = product_pack_map[pack]
                existing = market_prices.get(target_label)
                source_row = {
                    "url": offer.url,
                    "source": offer.source,
                    "title": offer.title,
                    "score": row["score"],
                    "market_price": price,
                }
                if existing is None or price < existing["market_price"]:
                    market_prices[target_label] = {
                        "market_price": price,
                        "sources": [source_row],
                    }
                elif price == existing["market_price"]:
                    existing["sources"].append(source_row)
    else:
        for row in filtered_rows:
            offer = row["offer"]
            selected_price = 0
            if "__default__" in offer.prices:
                selected_price = offer.prices["__default__"]
            elif len(offer.prices) == 1:
                selected_price = next(iter(offer.prices.values()))
            if selected_price <= 0:
                continue
            source_row = {
                "url": offer.url,
                "source": offer.source,
                "title": offer.title,
                "score": row["score"],
                "market_price": selected_price,
            }
            existing = market_prices.get("__default__")
            if existing is None or selected_price < existing["market_price"]:
                market_prices["__default__"] = {
                    "market_price": selected_price,
                    "sources": [source_row],
                }
            elif selected_price == existing["market_price"]:
                existing["sources"].append(source_row)

    resolved_price_map: dict[str, int] = {}
    detail_rows: dict[str, Any] = {}
    for label, data in market_prices.items():
        target_price = max(0, int(data["market_price"]) - 10000)
        if target_price <= 0:
            continue
        resolved_price_map[label] = target_price
        detail_rows[label] = {
            "market_price": int(data["market_price"]),
            "target_price": target_price,
            "sources": data["sources"],
        }

    if not resolved_price_map:
        return {
            "status": "unmatched",
            "reason": "no_exact_pack_match",
            "candidates": [
                {
                    "title": row["offer"].title,
                    "url": row["offer"].url,
                    "score": row["score"],
                    "shared_tokens": row["details"]["shared_tokens"],
                    "prices": row["offer"].prices,
                }
                for row in filtered_rows[:5]
            ],
            "resolved_price_map": {},
            "base_price": 0,
        }

    if product_pack_map:
        expected_labels = set(product_pack_map.values())
        status = "matched" if expected_labels <= set(resolved_price_map) else "partial"
    else:
        status = "matched"

    base_price = min(resolved_price_map.values()) if resolved_price_map else 0
    return {
        "status": status,
        "reason": "",
        "candidates": [
            {
                "title": row["offer"].title,
                "url": row["offer"].url,
                "score": row["score"],
                "shared_tokens": row["details"]["shared_tokens"],
                "prices": row["offer"].prices,
            }
            for row in filtered_rows[:5]
        ],
        "resolved_price_map": resolved_price_map,
        "resolved_detail": detail_rows,
        "base_price": base_price,
    }


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--catalog", type=Path, default=DEFAULT_CATALOG)
    parser.add_argument("--output", type=Path, default=DEFAULT_OUTPUT)
    args = parser.parse_args()

    with args.catalog.open("r", encoding="utf-8") as handle:
        catalog_data = json.load(handle)

    products = [product for product in catalog_data.get("products", []) if (product.get("brand") or "").lower() in TARGET_BRANDS]
    log(f"Catalog products for pricing: {len(products)}")

    offers = build_offers()
    log(f"Collected offers: {len(offers)}")
    token_weights = compute_token_idf(products, offers)

    results = []
    summary = {
        "product_total": len(products),
        "matched_total": 0,
        "partial_total": 0,
        "unmatched_total": 0,
        "brand": defaultdict(lambda: {"matched": 0, "partial": 0, "unmatched": 0}),
        "source_offer_total": len(offers),
    }

    for product in products:
        resolved = resolve_prices_for_product(product, offers, token_weights)
        row = {
            "id": product.get("id"),
            "slug": product.get("slug"),
            "name": product.get("name"),
            "brand": product.get("brand"),
            "existing_price": product.get("price"),
            "pack_labels": parse_product_pack_map(product),
            **resolved,
        }
        results.append(row)
        summary[resolved["status"] + "_total"] += 1
        summary["brand"][product["brand"]][resolved["status"]] += 1

    output = {
        "summary": {
            **summary,
            "brand": dict(summary["brand"]),
        },
        "products": results,
    }

    args.output.write_text(json.dumps(output, ensure_ascii=False, indent=2), encoding="utf-8")
    log(f"Wrote pricing report: {args.output}")


if __name__ == "__main__":
    main()
