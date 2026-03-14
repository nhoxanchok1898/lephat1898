#!/usr/bin/env python3
"""Fetch Jotun interior colour catalogue and export it as JSON.

Source page:
https://www.jotun.com/vn-vi/decorative/colours/interior/all-interior-colours?relatedProductIds=1707
"""

from __future__ import annotations

import argparse
import json
import sys
from datetime import UTC, datetime
from pathlib import Path
from typing import Any
from urllib.parse import urlencode
from urllib.request import Request, urlopen


API_URL = "https://www.jotun.com/api/v2/search/colour"


def _fetch_json(params: dict[str, Any]) -> dict[str, Any]:
    query = urlencode(params, doseq=True)
    req = Request(
        f"{API_URL}?{query}",
        headers={
            "Accept": "application/json",
            "User-Agent": "Mozilla/5.0 (compatible; lephat1898-sync/1.0)",
        },
    )
    with urlopen(req, timeout=30) as resp:  # nosec B310
        payload = resp.read().decode("utf-8")
    return json.loads(payload)


def fetch_all_colours(take: int = 100) -> dict[str, Any]:
    page_url = "/vn-vi/decorative/colours/interior/all-interior-colours"
    base_params = {
        "applicationAreas": "Interior",
        "pageUrl": page_url,
        "relatedProductIds": "1707",
        "take": str(take),
        "page": "0",
    }

    all_items: list[dict[str, Any]] = []
    seen: set[str] = set()
    total = 0
    skip = 0
    page = 0

    while True:
        params = dict(base_params)
        params["skip"] = str(skip)
        params["page"] = str(page)
        raw = _fetch_json(params)

        total = int(raw.get("total", total) or 0)
        rows = raw.get("results") or []
        if not rows:
            break

        added_this_page = 0
        for row in rows:
            code = str(row.get("colourCode") or "").strip()
            name = str(row.get("colourName") or "").strip()
            hex_code = str(row.get("colourHexCode") or "").strip().lstrip("#")
            link = str(row.get("link") or "").strip()
            tag = str(row.get("tag") or "").strip()

            if not code or not hex_code:
                continue

            key = f"{code}|{hex_code.lower()}"
            if key in seen:
                continue
            seen.add(key)

            item: dict[str, Any] = {
                "code": code,
                "name": name,
                "hex": hex_code.lower(),
                "link": link,
            }
            if tag:
                item["tag"] = tag
            all_items.append(item)
            added_this_page += 1

        if added_this_page == 0:
            break

        skip += len(rows)
        page += 1
        if total and len(all_items) >= total:
            break

    return {
        "source": "jotun.com",
        "source_page": (
            "https://www.jotun.com/vn-vi/decorative/colours/interior/"
            "all-interior-colours?relatedProductIds=1707"
        ),
        "api_endpoint": API_URL,
        "application_areas": "Interior",
        "related_product_ids": ["1707"],
        "fetched_at_utc": datetime.now(UTC).isoformat(timespec="seconds"),
        "total": len(all_items),
        "items": all_items,
    }


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument(
        "--output",
        default="wordpress/my-theme/data/jotun_interior_colours.json",
        help="Output JSON file path.",
    )
    parser.add_argument(
        "--take",
        type=int,
        default=100,
        help="Page size for each API request.",
    )
    args = parser.parse_args()

    try:
        data = fetch_all_colours(take=max(10, min(args.take, 200)))
    except Exception as exc:  # pragma: no cover - operational script
        print(f"ERROR: {exc}", file=sys.stderr)
        return 1

    out_path = Path(args.output)
    out_path.parent.mkdir(parents=True, exist_ok=True)
    out_path.write_text(
        json.dumps(data, ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )
    print(f"Wrote {data['total']} colours -> {out_path}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
