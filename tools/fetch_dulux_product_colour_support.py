#!/usr/bin/env python3
"""
Fetch official Dulux VN "requires color" flags per product page.

Input:
  wordpress/my-theme/data/dulux_official.json

Output:
  wordpress/my-theme/data/dulux_product_colour_support.json
"""

from __future__ import annotations

import datetime as dt
import json
import pathlib
import re
import sys
from concurrent.futures import ThreadPoolExecutor, as_completed
from typing import Dict, List, Optional, Tuple
from urllib.error import HTTPError, URLError
from urllib.parse import quote, urlsplit, urlunsplit
from urllib.request import Request, urlopen

SOURCE_PAGE = "https://www.dulux.vn/vi/san-pham"
FLAG_RE = re.compile(r'data-requires-color\s*=\s*"(true|false)"', re.IGNORECASE)
ALT_FLAG_RE = re.compile(r'"requiresColor"\s*:\s*(true|false)', re.IGNORECASE)


def read_json(path: pathlib.Path) -> object:
    return json.loads(path.read_text(encoding="utf-8"))


def fetch_html(url: str) -> str:
    split = urlsplit(url)
    safe_path = quote(split.path, safe="/%")
    safe_query = quote(split.query, safe="=&%")
    encoded_url = urlunsplit((split.scheme, split.netloc, safe_path, safe_query, split.fragment))

    req = Request(
        encoded_url,
        headers={
            "User-Agent": (
                "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
                "AppleWebKit/537.36 (KHTML, like Gecko) "
                "Chrome/124.0 Safari/537.36"
            )
        },
    )
    with urlopen(req, timeout=30) as resp:
        return resp.read().decode("utf-8", errors="replace")


def detect_requires_color(html: str) -> Tuple[Optional[bool], str]:
    m = FLAG_RE.search(html)
    if m:
        return (m.group(1).lower() == "true"), "data-requires-color"

    m2 = ALT_FLAG_RE.search(html)
    if m2:
        return (m2.group(1).lower() == "true"), "requiresColor"

    return None, "missing-flag"


def normalize_slug(value: str) -> str:
    text = re.sub(r"[^a-z0-9-]+", "", (value or "").strip().lower())
    text = re.sub(r"-{2,}", "-", text).strip("-")
    return text


def probe_item(row: Dict[str, object]) -> Dict[str, object]:
    slug = normalize_slug(str(row.get("slug", "")))
    url = str(row.get("url", "")).strip()
    name = str(row.get("name", "")).strip()

    result: Dict[str, object] = {
        "slug": slug,
        "url": url,
        "name": name,
        "requires_color": False,
        "detected_from": "missing-url",
        "status": "skipped",
    }

    if slug == "" or url == "":
        return result

    try:
        html = fetch_html(url)
    except HTTPError as exc:
        result["detected_from"] = f"http-{exc.code}"
        result["status"] = "error"
        return result
    except URLError:
        result["detected_from"] = "network-error"
        result["status"] = "error"
        return result
    except Exception:
        result["detected_from"] = "fetch-error"
        result["status"] = "error"
        return result

    requires, detected_from = detect_requires_color(html)
    result["detected_from"] = detected_from
    if requires is None:
        # Fail-safe: if the page does not expose an explicit flag, do not assume it has a color chart.
        result["requires_color"] = False
        result["status"] = "ok-missing-flag"
    else:
        result["requires_color"] = bool(requires)
        result["status"] = "ok"

    return result


def main() -> int:
    repo_root = pathlib.Path(__file__).resolve().parents[1]
    input_path = repo_root / "wordpress" / "my-theme" / "data" / "dulux_official.json"
    output_path = repo_root / "wordpress" / "my-theme" / "data" / "dulux_product_colour_support.json"

    if not input_path.exists():
        raise RuntimeError(f"Input file not found: {input_path}")

    rows = read_json(input_path)
    if not isinstance(rows, list):
        raise RuntimeError("dulux_official.json must be a JSON array.")

    unique_rows: List[Dict[str, object]] = []
    seen = set()
    for row in rows:
        if not isinstance(row, dict):
            continue
        slug = normalize_slug(str(row.get("slug", "")))
        if slug == "" or slug in seen:
            continue
        seen.add(slug)
        unique_rows.append(row)

    items: List[Dict[str, object]] = []
    with ThreadPoolExecutor(max_workers=8) as pool:
        futures = [pool.submit(probe_item, row) for row in unique_rows]
        for fut in as_completed(futures):
            items.append(fut.result())

    items.sort(key=lambda x: str(x.get("slug", "")))

    supported = sum(1 for x in items if bool(x.get("requires_color", False)))
    unsupported = sum(1 for x in items if not bool(x.get("requires_color", False)))
    errors = sum(1 for x in items if str(x.get("status", "")).startswith("error"))
    missing_flag = sum(1 for x in items if str(x.get("detected_from", "")) == "missing-flag")

    payload = {
        "source_page": SOURCE_PAGE,
        "generated_at": dt.datetime.now(dt.timezone.utc).replace(microsecond=0).isoformat().replace("+00:00", "Z"),
        "total": len(items),
        "supported": supported,
        "unsupported": unsupported,
        "errors": errors,
        "missing_flag": missing_flag,
        "items": items,
    }

    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")

    print(
        "Saved Dulux product colour support map: "
        f"total={payload['total']} supported={supported} unsupported={unsupported} "
        f"errors={errors} missing_flag={missing_flag} -> {output_path}"
    )
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as exc:  # pragma: no cover
        print(f"ERROR: {exc}", file=sys.stderr)
        raise SystemExit(1)
