from __future__ import annotations

import argparse
import re
import sys
from dataclasses import dataclass
from html import unescape
from urllib.error import HTTPError, URLError
from urllib.parse import urljoin
from urllib.request import Request, urlopen


DEFAULT_BASE_URL = "http://localhost:8080/"
DEFAULT_ROUTES = [
    "/",
    "/shop/",
    "/blog/",
    "/giai-phap/",
    "/product/duluxeasycleanlauchuihieuquabematbong/",
    "/cart/",
    "/checkout/",
    "/my-account/",
]


@dataclass
class SmokeResult:
    route: str
    status: int
    title: str
    canonical: str
    h1_count: int
    ok: bool
    error: str = ""


def fetch_html(url: str) -> tuple[int, str]:
    request = Request(
        url,
        headers={
            "User-Agent": "wp-route-smoke-test/1.0",
        },
    )
    with urlopen(request, timeout=20) as response:
        status = getattr(response, "status", 200)
        html = response.read().decode("utf-8", errors="replace")
    return status, html


def extract_first(pattern: str, html: str) -> str:
    match = re.search(pattern, html, re.IGNORECASE | re.DOTALL)
    if not match:
        return ""
    return unescape(match.group(1).strip())


def run_smoke(base_url: str, routes: list[str]) -> list[SmokeResult]:
    results: list[SmokeResult] = []
    for route in routes:
        url = urljoin(base_url, route.lstrip("/"))
        if route == "/":
            url = base_url.rstrip("/") + "/"
        try:
            status, html = fetch_html(url)
            title = extract_first(r"<title>(.*?)</title>", html)
            canonical = extract_first(r'<link rel=[\'"]canonical[\'"] href=[\'"](.*?)[\'"]', html)
            h1_count = len(re.findall(r"<h1\b", html, re.IGNORECASE))
            ok = status == 200 and title != "" and h1_count == 1 and canonical != ""
            results.append(
                SmokeResult(
                    route=route,
                    status=status,
                    title=title,
                    canonical=canonical,
                    h1_count=h1_count,
                    ok=ok,
                )
            )
        except HTTPError as exc:
            results.append(
                SmokeResult(route=route, status=exc.code, title="", canonical="", h1_count=0, ok=False, error=str(exc))
            )
        except URLError as exc:
            results.append(
                SmokeResult(route=route, status=0, title="", canonical="", h1_count=0, ok=False, error=str(exc))
            )
    return results


def main() -> int:
    if hasattr(sys.stdout, "reconfigure"):
        sys.stdout.reconfigure(encoding="utf-8", errors="replace")
    if hasattr(sys.stderr, "reconfigure"):
        sys.stderr.reconfigure(encoding="utf-8", errors="replace")

    parser = argparse.ArgumentParser(description="Smoke test key WordPress storefront routes.")
    parser.add_argument("--base-url", default=DEFAULT_BASE_URL, help="Base URL of the WordPress site.")
    parser.add_argument("--route", action="append", dest="routes", help="Specific route to test. Can be repeated.")
    args = parser.parse_args()

    routes = args.routes if args.routes else DEFAULT_ROUTES
    results = run_smoke(args.base_url, routes)

    failures = 0
    for result in results:
        print(f"ROUTE: {result.route}")
        print(f"STATUS: {result.status}")
        print(f"TITLE: {result.title}")
        print(f"CANONICAL: {result.canonical}")
        print(f"H1_COUNT: {result.h1_count}")
        if result.error:
            print(f"ERROR: {result.error}")
        print(f"OK: {'yes' if result.ok else 'no'}")
        print("---")
        if not result.ok:
            failures += 1

    return 1 if failures else 0


if __name__ == "__main__":
    sys.exit(main())
