#!/usr/bin/env bash
set -euo pipefail
REF=${1:-main}
WORKFLOWS=(runner-sanity.yml django-ci.yml render-deploy-manual.yml)

command -v gh >/dev/null 2>&1 || { echo "gh not installed. Install it and authenticate." >&2; exit 2; }

for wf in "${WORKFLOWS[@]}"; do
  echo "--- Running $wf on $REF ---"
  gh workflow run "$wf" --ref "$REF"
  echo "Waiting for run to finish..."
  gh run watch --exit-status

  # try to download artifacts for latest run of this workflow
  runid=$(gh run list --workflow "$wf" --limit 1 --json databaseId --jq '.[0].databaseId') || true
  if [ -n "$runid" ]; then
    echo "Downloading artifacts for run $runid"
    gh run download "$runid" --dir "./artifacts/$wf-$runid" || echo "no artifacts"
  else
    echo "No run id found; attempting to download all artifacts"
    gh run download --dir "./artifacts/$wf" || echo "no artifacts"
  fi
done

echo "Done. Artifacts in ./artifacts"
