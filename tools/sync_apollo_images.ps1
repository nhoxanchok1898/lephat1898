param(
    [switch]$ForceDownload,
    [switch]$DryRunApply
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $repoRoot

$buildArgs = @(
    '-ExecutionPolicy', 'Bypass',
    '-File', (Join-Path $PSScriptRoot 'build_official_image_map.ps1'),
    '-OnlyBrand', 'apollo'
)
if ($ForceDownload) {
    $buildArgs += '-ForceDownload'
}

Write-Host '[sync-apollo] Building Apollo official map...'
powershell @buildArgs

$applyPhp = Get-Content -Raw -Path (Join-Path $PSScriptRoot 'apply_official_image_map.php')

if ($DryRunApply) {
    Write-Host '[sync-apollo] Applying map to WordPress (dry-run)...'
    $applyPhp | docker exec -i lephat1898-wordpress-1 php -- --dry-run --only-brand=apollo
} else {
    Write-Host '[sync-apollo] Applying map to WordPress...'
    $applyPhp | docker exec -i lephat1898-wordpress-1 php -- --only-brand=apollo
}

Write-Host '[sync-apollo] Done.'
