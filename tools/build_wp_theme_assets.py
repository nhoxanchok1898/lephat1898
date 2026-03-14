from __future__ import annotations

from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
THEME_DIR = ROOT / "wordpress" / "my-theme"
CSS_DIR = THEME_DIR / "assets" / "css"


def minify_css(text: str) -> str:
    out: list[str] = []
    i = 0
    n = len(text)
    in_quote = ""
    pending_space = False

    while i < n:
        ch = text[i]
        nxt = text[i + 1] if i + 1 < n else ""

        if in_quote:
            out.append(ch)
            if ch == "\\" and i + 1 < n:
                i += 1
                out.append(text[i])
            elif ch == in_quote:
                in_quote = ""
            i += 1
            continue

        if ch in ("'", '"'):
            if pending_space and out and out[-1] not in "{:;,>+~(":
                out.append(" ")
            pending_space = False
            in_quote = ch
            out.append(ch)
            i += 1
            continue

        if ch == "/" and nxt == "*":
            i += 2
            while i + 1 < n and not (text[i] == "*" and text[i + 1] == "/"):
                i += 1
            i += 2
            continue

        if ch.isspace():
            pending_space = True
            i += 1
            continue

        if pending_space:
            if out:
                prev = out[-1]
                if prev not in "{:;,>+~(" and ch not in "}:;,>+~)":
                    out.append(" ")
            pending_space = False

        if ch in "{}:;,>+~)":
            while out and out[-1] == " ":
                out.pop()
            out.append(ch)
        else:
            out.append(ch)

        i += 1

    minified = "".join(out).strip()
    return minified.replace(";}", "}")


def write_minified(source: Path, target: Path) -> None:
    css = source.read_text(encoding="utf-8")
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_text(minify_css(css), encoding="utf-8")


def main() -> None:
    targets = [(THEME_DIR / "style.css", CSS_DIR / "theme-runtime.min.css")]

    for source in sorted(CSS_DIR.glob("*.css")):
        if source.name.endswith(".min.css"):
            continue
        targets.append((source, source.with_name(f"{source.stem}.min.css")))

    for source, target in targets:
        write_minified(source, target)
        print(f"{source.relative_to(ROOT)} -> {target.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
