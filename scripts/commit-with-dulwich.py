#!/usr/bin/env python3
"""Create a commit and annotated tag using dulwich (no git.exe required).

Usage:
  python scripts/commit-with-dulwich.py [--message "msg"] [--tag NAME]

This will stage a predefined set of files (if present), create a commit,
and create an annotated tag pointing at that commit.
"""
from __future__ import annotations
import os
import sys
from typing import List

FILES = [
    "static/js/cart-qty.js",
    "static/css/cart-qty.css",
    "templates/store/base.html",
    "static/js/mini-cart.js",
    "static/css/mini-cart.css",
    "CHANGELOG.md",
]


def main(argv: List[str] | None = None) -> int:
    argv = argv if argv is not None else sys.argv[1:]
    try:
        from dulwich import porcelain
        from dulwich.repo import Repo
    except Exception as exc:  # pragma: no cover - runtime helpful message
        print("dulwich not installed. Install with: pip install dulwich", file=sys.stderr)
        print(str(exc), file=sys.stderr)
        return 2

    import argparse
    parser = argparse.ArgumentParser(description="Commit and tag using dulwich")
    parser.add_argument("--message", "-m", default="feat(cart): optimistic AJAX quantity updates (cart & mini-cart)")
    parser.add_argument("--tag", "-t", default="ui-cart-optimistic-v1")
    args = parser.parse_args(argv)

    repo_path = os.getcwd()
    # open or init
    try:
        repo = Repo(repo_path)
    except Exception:
        repo = porcelain.init(repo_path)

    existing = [f for f in FILES if os.path.exists(os.path.join(repo_path, f))]
    if not existing:
        print("No files found to add. Checked:")
        for f in FILES:
            print(" -", f)
        return 1

    print("Staging files:")
    for f in existing:
        print("  ", f)

    try:
        porcelain.add(repo.path, paths=existing)
    except TypeError:
        # fallback signature: porcelain.add(repo, paths)
        porcelain.add(repo, existing)

    author_name = os.environ.get("GIT_AUTHOR_NAME") or os.environ.get("USERNAME") or "user"
    author_email = os.environ.get("GIT_AUTHOR_EMAIL") or f"{author_name}@example.com"
    author = f"{author_name} <{author_email}>"

    print("Creating commit: %s" % args.message)
    try:
        try:
            porcelain.commit(repo.path, message=args.message.encode("utf8"), author=author)
        except TypeError:
            # some dulwich versions accept repo object
            porcelain.commit(repo, message=args.message.encode("utf8"), author=author)
    except Exception as exc:
        print("Commit failed:", exc, file=sys.stderr)
        return 3

    # determine HEAD
    try:
        head = repo.head()
        # head is bytes
        from binascii import hexlify
        full = hexlify(head).decode("ascii")
        short = full[:7]
    except Exception:
        # try rev-parse like approach
        full = repo.head().hex()
        short = full[:7]

    print("Commit created:", short)

    # create annotated tag
    tag = args.tag
    print("Creating annotated tag:", tag)
    try:
        try:
            porcelain.tag_create(repo.path, tag, message=("UI: " + args.message).encode("utf8"), objectish=full, tagger=author)
        except TypeError:
            porcelain.tag_create(repo, tag, message=("UI: " + args.message).encode("utf8"), objectish=full, tagger=author)
    except Exception as exc:
        print("Tag creation failed:", exc, file=sys.stderr)
        return 4

    print("Tag created:", tag)
    print("Done. To push the tag to remote use your normal git client: git push origin %s" % tag)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
