#!/usr/bin/env python3
"""
Fetch official Dulux VN colour catalogue from:
https://www.dulux.vn/vi/mau-sac-bang-mau/filters/b_dulux

Output:
  wordpress/my-theme/data/dulux_colour_catalog.json
"""

from __future__ import annotations

import json
import pathlib
import re
import sys
from typing import Dict, List, Tuple
from urllib.request import Request, urlopen

SOURCE_URL = "https://www.dulux.vn/vi/mau-sac-bang-mau/filters/b_dulux"


def fetch_html(url: str) -> str:
    req = Request(
        url,
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


def extract_json_array_blob(html: str) -> str:
    """
    Find the very large inlined JSON array that starts with hue groups:
    [{"image":{"src":"/content/dam/akzonobel-common/colorWall/...
    """
    marker = '[{"image":{"src":"/content/dam/akzonobel-common/colorWall/'
    start = html.find(marker)
    if start < 0:
        raise RuntimeError("Cannot find Dulux colour JSON block in page HTML.")

    level = 0
    in_string = False
    escaped = False
    end = -1
    for i in range(start, len(html)):
        ch = html[i]
        if in_string:
            if escaped:
                escaped = False
            elif ch == "\\":
                escaped = True
            elif ch == '"':
                in_string = False
            continue

        if ch == '"':
            in_string = True
            continue
        if ch == "[":
            level += 1
            continue
        if ch == "]":
            level -= 1
            if level == 0:
                end = i + 1
                break

    if end <= start:
        raise RuntimeError("Failed to locate end of Dulux colour JSON block.")

    return html[start:end]


def split_name_and_code(title: str) -> Tuple[str, str]:
    """
    Example title:
      "Wine Sensation 80RR 10/306"
    Return:
      ("Wine Sensation", "80RR 10/306")
    """
    clean = " ".join((title or "").strip().split())
    if not clean:
        return "", ""

    m = re.search(r"([0-9]{1,3}[A-Z]{1,3}\s[0-9]{2}/[0-9]{3})$", clean)
    if m:
        code = m.group(1).strip()
        name = clean[: m.start()].strip(" -")
        return (name or clean), code

    return clean, ""


def normalize_hex(value: str) -> str:
    hx = re.sub(r"[^0-9a-fA-F]", "", value or "").lower()
    return hx if len(hx) == 6 else ""


def build_catalog(raw_groups: List[dict]) -> Dict[str, object]:
    items: List[Dict[str, str]] = []
    seen = set()

    for group in raw_groups:
        if not isinstance(group, dict):
            continue
        colors = group.get("colors")
        if not isinstance(colors, list):
            continue
        for row in colors:
            if not isinstance(row, dict):
                continue
            color = row.get("color")
            if not isinstance(color, dict):
                continue

            ccid = str(color.get("ccid", "")).strip()
            title = str(color.get("title", "")).strip() or str(color.get("name", "")).strip()
            name, code = split_name_and_code(title)
            if code == "":
                code = ccid
            if not code:
                continue

            hx = normalize_hex(str(color.get("hex", "")))
            if hx == "":
                continue

            href = str(color.get("href", "")).strip()
            if href and href.startswith("/"):
                href = "https://www.dulux.vn" + href

            dedupe_key = ccid or code
            if dedupe_key in seen:
                continue
            seen.add(dedupe_key)

            items.append(
                {
                    "code": code,
                    "name": name,
                    "hex": hx,
                    "link": href,
                    "ccid": ccid,
                }
            )

    return {
        "source_page": SOURCE_URL,
        "total": len(items),
        "items": items,
    }


def main() -> int:
    repo_root = pathlib.Path(__file__).resolve().parents[1]
    out_path = repo_root / "wordpress" / "my-theme" / "data" / "dulux_colour_catalog.json"
    out_path.parent.mkdir(parents=True, exist_ok=True)

    html = fetch_html(SOURCE_URL)
    blob = extract_json_array_blob(html)
    groups = json.loads(blob)
    if not isinstance(groups, list):
        raise RuntimeError("Unexpected Dulux payload format.")

    catalog = build_catalog(groups)
    out_path.write_text(json.dumps(catalog, ensure_ascii=False, indent=2), encoding="utf-8")

    print(f"Saved {catalog['total']} Dulux colours -> {out_path}")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as exc:  # pragma: no cover
        print(f"ERROR: {exc}", file=sys.stderr)
        raise SystemExit(1)

