param(
  [string]$ref = 'main'
)

function Fail([string]$m){ Write-Error $m; exit 2 }

if (-not (Get-Command gh -ErrorAction SilentlyContinue)){
  Fail 'gh CLI not found. Install: winget install GitHub.cli'
}

$workflows = @('runner-sanity.yml','django-ci.yml','render-deploy-manual.yml')

foreach ($wf in $workflows) {
  Write-Host "--- Running $wf on $ref ---"
  gh workflow run $wf --ref $ref
  if ($LASTEXITCODE -ne 0) { Fail "Failed to trigger $wf" }

  Write-Host "Waiting for $wf to finish (this may take a while)..."
  gh run watch --exit-status
  if ($LASTEXITCODE -ne 0) { Write-Host "Workflow $wf failed or was cancelled" }

  # try to download latest artifact for this workflow
  try {
    $listJson = gh run list --workflow $wf --limit 1 --json databaseId 2>&1
    $obj = $null
    $obj = $listJson | ConvertFrom-Json -ErrorAction Stop
    if ($obj -and $obj.Count -gt 0) { $runId = $obj[0].databaseId } else { $runId = $null }
  } catch {
    $runId = $null
  }

  if ($runId) {
    Write-Host "Downloading artifacts for run $runId into ./artifacts/$wf-$runId"
    gh run download $runId --dir ./artifacts/$wf-$runId || Write-Host "No artifacts or download failed for $wf"
  } else {
    Write-Host "Could not determine run id for $wf; attempting to download most recent artifacts"
    gh run download --dir ./artifacts/$wf || Write-Host "No artifacts for $wf"
  }
}

Write-Host "All done. Check ./artifacts for results."
exit 0
