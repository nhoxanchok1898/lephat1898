#!/usr/bin/env python3
"""
Fetch official product data (name, image, capacities, description) from Dulux VN site
and export to JSON for the theme importer.

Usage (from repo root):
  python scripts/fetch_official_catalog.py --brand dulux --out wordpress/my-theme/data/dulux_official.json
"""

import argparse
import json
import re
import sys
import unicodedata
from pathlib import Path
from typing import Dict, List, Optional

import requests
from lxml import etree, html

HEADERS = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36"
}


def slugify(text: str) -> str:
    # strip accents then keep ascii
    text = unicodedata.normalize("NFKD", text)
    text = text.encode("ascii", "ignore").decode("ascii")
    text = re.sub(r"[^\w\\s-]", "", text, flags=re.UNICODE)
    text = re.sub(r"\\s+", "-", text.strip())
    return text.lower()


def fetch_dulux_product_urls() -> List[str]:
    sitemap_url = "https://www.dulux.vn/vi/sitemap.xml"
    resp = requests.get(sitemap_url, timeout=30, headers=HEADERS)
    resp.raise_for_status()
    root = etree.fromstring(resp.content)
    ns = {"sm": "https://www.sitemaps.org/schemas/sitemap/0.9"}
    locs = [loc.text for loc in root.xpath("//sm:url/sm:loc", namespaces=ns)]
    return [u for u in locs if "/san-pham/" in u]


def parse_jsonld(tree: html.HtmlElement) -> Dict:
    scripts = tree.xpath('//script[@type="application/ld+json"]/text()')
    for raw in scripts:
        try:
            data = json.loads(raw)
        except Exception:
            continue
        if isinstance(data, list):
            for item in data:
                if isinstance(item, dict) and item.get("@type") in ("Product", ["Product"], "Thing", ["Thing"]):
                    return item
        if isinstance(data, dict) and data.get("@type") in ("Product", ["Product"], "Thing", ["Thing"]):
            return data
    return {}


def extract_capacities(text: str) -> List[Dict]:
    norm = unicodedata.normalize("NFKD", text)
    norm = norm.encode("ascii", "ignore").decode("ascii")

    caps = {}
    patterns = (
        (r"(\d+(?:[.,]\d+)?)[\s-]*(?:l|lit)", "L"),
        (r"(\d+(?:[.,]\d+)?)\s*kg", "kg"),
    )
    for pattern, unit in patterns:
        for match in re.findall(pattern, norm, flags=re.IGNORECASE):
            val = match.replace(",", ".")
            label = f"{val}{unit}"
            caps[label.lower()] = {"label": label, "value": float(val), "unit": unit}
    filtered = [v for v in caps.values() if v["value"] > 0]
    return sorted(filtered, key=lambda x: (x["unit"], x["value"]))

    caps = {}
    for pattern, unit in ((r"(\d+(?:[.,]\d+)?)\\s*l", "L"), (r"(\d+(?:[.,]\d+)?)\\s*kg", "kg")):
        for match in re.findall(pattern, norm, flags=re.IGNORECASE):
            val = match.replace(",", ".")
            label = f"{val}{unit}"
            caps[label.lower()] = {"label": label, "value": float(val), "unit": unit}
    return list(caps.values())


def scrape_dulux_products() -> List[Dict]:
    urls = fetch_dulux_product_urls()
    products = []
    for url in urls:
        try:
            html_text = requests.get(url, timeout=30, headers=HEADERS).text
        except Exception as exc:
            print(f"[warn] Skip {url}: {exc}", file=sys.stderr)
            continue

        tree = html.fromstring(html_text)
        jsonld = parse_jsonld(tree)

        name = jsonld.get("name") or tree.xpath("string(//title)")
        if not name:
            continue

        brand = ""
        if isinstance(jsonld.get("brand"), dict):
            brand = jsonld.get("brand", {}).get("name", "")
        elif isinstance(jsonld.get("brand"), str):
            brand = jsonld.get("brand")
        brand = brand or "Dulux"

        image = None
        if jsonld.get("image"):
            if isinstance(jsonld["image"], list):
                image = jsonld["image"][0]
            else:
                image = jsonld["image"]
        if not image:
            # fallback to og:image
            img_meta = tree.xpath("string(//meta[@property='og:image']/@content)")
            image = img_meta or None

        description = jsonld.get("description") or tree.xpath("string(//meta[@name='description']/@content)")
        raw_text = tree.xpath("string(//body)")

        capacities = extract_capacities(raw_text)

        products.append(
            {
                "brand": brand,
                "name": name.strip(),
                "slug": slugify(name),
                "url": url,
                "image": image,
                "description": description.strip() if description else "",
                "capacities": capacities,
            }
        )

    return products


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--brand", default="dulux", choices=["dulux"], help="Brand to fetch")
    parser.add_argument("--out", default="wordpress/my-theme/data/dulux_official.json", help="Output JSON path")
    args = parser.parse_args()

    repo_root = Path(__file__).resolve().parents[1]
    out_path = (repo_root / args.out).resolve()
    out_path.parent.mkdir(parents=True, exist_ok=True)

    if args.brand == "dulux":
        data = scrape_dulux_products()
    else:
        print(f"Brand {args.brand} not implemented", file=sys.stderr)
        sys.exit(1)

    out_path.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"Wrote {len(data)} products -> {out_path}")


if __name__ == "__main__":
    main()
