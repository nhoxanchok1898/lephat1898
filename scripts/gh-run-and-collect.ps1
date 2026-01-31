param(
  [string]$workflow = 'django-ci.yml',
  [string]$ref = 'main',
  [int]$timeoutSeconds = 1200,
  [int]$pollInterval = 5
)

function Fail([string]$msg){
  Write-Error $msg
  exit 2
}

if (-not (Get-Command gh -ErrorAction SilentlyContinue)){
  Fail 'gh CLI not found. Install it: winget install GitHub.cli'
}

Write-Host "Triggering workflow '$workflow' on ref '$ref'..."
$trigger = gh workflow run $workflow --ref $ref 2>&1
if ($LASTEXITCODE -ne 0){
  Write-Host $trigger
  Fail 'Failed to trigger workflow.'
}
Write-Host $trigger

$start = Get-Date
$runId = $null

Write-Host "Polling for run id and status (timeout ${timeoutSeconds}s)..."
while ((Get-Date).Subtract($start).TotalSeconds -lt $timeoutSeconds) {
  $jsonRaw = gh run list --workflow $workflow --limit 10 --json databaseId,headBranch,createdAt,status,conclusion 2>&1
  if ($LASTEXITCODE -ne 0) { Write-Host $jsonRaw }

  try {
    $runs = $jsonRaw | ConvertFrom-Json -ErrorAction Stop
  } catch {
    $runs = @()
  }

  if ($runs -and $runs.Count -gt 0) {
    $match = $runs | Where-Object { $_.headBranch -eq $ref } | Sort-Object {[datetime]$_.createdAt} -Descending | Select-Object -First 1
    if (-not $match) { $match = $runs | Sort-Object {[datetime]$_.createdAt} -Descending | Select-Object -First 1 }
    if ($match) { $runId = $match.databaseId }
  }

  if ($runId) {
    Write-Host "Found run id: $runId"
    $statusRaw = gh run view $runId --json status,conclusion 2>&1
    if ($LASTEXITCODE -ne 0) { Write-Host $statusRaw; Fail "Failed to view run $runId" }

    try { $statusObj = $statusRaw | ConvertFrom-Json -ErrorAction Stop } catch { $statusObj = $null }
    $status = ''
    if ($statusObj) { $status = $statusObj.status }
    Write-Host "Run status: $status"

    if ($status -in @('completed','failure','cancelled','success','neutral')){ break }
  }

  Start-Sleep -Seconds $pollInterval
}

if (-not $runId){ Fail 'Timed out waiting for run id.' }

Write-Host "Showing logs for run $runId..."
gh run view $runId --log

Write-Host "Downloading artifacts for run $runId into ./artifacts/$runId ..."
gh run download $runId --dir ./artifacts/$runId
if ($LASTEXITCODE -ne 0){ Fail "Failed to download artifacts for run $runId" }

Write-Host "Done. Artifacts saved to ./artifacts/$runId"

exit 0
param(
  [string]$workflow = "django-ci.yml",
  [string]$ref = "main",
  [int]$timeoutSeconds = 1200,
  [int]$pollInterval = 5
)

function Fail($msg){ Write-Error $msg; exit 2 }

# Check gh
if (-not (Get-Command gh -ErrorAction SilentlyContinue)){
  Fail "gh CLI not found. Install it: winget install GitHub.cli"
}

# Trigger workflow
Write-Host "Triggering workflow '$workflow' on ref '$ref'..."
$trigger = gh workflow run $workflow --ref $ref 2>&1
if ($LASTEXITCODE -ne 0){
  Write-Host $trigger
  Fail "Failed to trigger workflow."
}
Write-Host $trigger

$start = Get-Date
$runId = $null

Write-Host "Polling for run id and status (timeout ${timeoutSeconds}s)..."
while ((Get-Date) - $start).TotalSeconds -lt $timeoutSeconds {
  try {
    $json = gh run list --workflow $workflow --limit 5 --json databaseId,headBranch,createdAt,status,conclusion 2>&1
  } catch {
    Fail "Failed to list runs: $_"
  }

  # If gh returned an error string, show it
  if ($json -is [string] -and $json.Trim().StartsWith('{') -ne $true -and $json.Trim().StartsWith('[') -ne $true) {
    Write-Host $json
  }

  try {
    $runs = $json | ConvertFrom-Json -ErrorAction Stop
  } catch {
    # Could not parse JSON; fallback to text parsing
    $runs = @()
  }

  if ($runs -and $runs.Count -gt 0) {
    # pick the newest run for the workflow on the desired ref
    $candidate = $runs | Sort-Object {[datetime]$_.createdAt} -Descending | Select-Object -First 1
    if ($candidate) { $runId = $candidate.databaseId }
  } else {
    # fallback: text parse of gh run list
    $lines = $json -split "`n" | ForEach-Object { $_.Trim() } | Where-Object { $_ -ne "" }
    foreach ($ln in $lines) {
      if ($ln -match '^(\d+)') { $runId = $Matches[1]; break }
    }
  }

  if ($runId) {
    Write-Host "Found run id: $runId"
    # Check status
    $statusRaw = gh run view $runId --json status,conclusion 2>&1
    if ($LASTEXITCODE -ne 0) { Write-Host $statusRaw; Fail "Failed to view run $runId" }

    try { $statusObj = $statusRaw | ConvertFrom-Json -ErrorAction Stop } catch { $statusObj = $null }
    $statusText = $null
    if ($statusObj) { $statusText = $statusObj.status } else { $statusText = ($statusRaw -split "`n" | Where-Object { $_ -match 'Status:' } | Select-Object -First 1) -replace '^.*Status:\s*','' }
    $statusText = $statusText.Trim()
    Write-Host "Run status: $statusText"

    if ($statusText -and ($statusText -ieq 'completed' -or $statusText -ieq 'failure' -or $statusText -ieq 'cancelled' -or $statusText -ieq 'success' -or $statusText -ieq 'neutral')){ break }
  }

  Start-Sleep -Seconds $pollInterval
}

if (-not $runId){ Fail "Timed out waiting for run id." }

Write-Host "Showing logs for run $runId..."
gh run view $runId --log

Write-Host "Downloading artifacts for run $runId into ./artifacts/$runId ..."
gh run download $runId --dir ./artifacts/$runId
if ($LASTEXITCODE -ne 0){
  Fail "Failed to download artifacts for run $runId"
}

Write-Host "Done. Artifacts saved to ./artifacts/$runId"

exit 0
