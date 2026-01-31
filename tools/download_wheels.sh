#!/usr/bin/env bash
set -euo pipefail
OUT_DIR=${1:-wheels}
shift || true
PACKAGES=${@:-"black flake8"}
mkdir -p "$OUT_DIR"
python -m pip download -d "$OUT_DIR" $PACKAGES
echo "Downloaded packages into $OUT_DIR"
