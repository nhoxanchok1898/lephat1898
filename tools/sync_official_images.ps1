param(
    [string]$Brand = '',
    [switch]$ForceDownload,
    [switch]$IncludeNonSeed,
    [switch]$DryRunApply
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $repoRoot

$brand = ([string]$Brand).Trim().ToLowerInvariant()

# Weber catalog in this workspace is imported without seed keys.
# Auto-include non-seed products so sync does not skip the whole brand.
if ($brand -eq 'weber' -and -not $IncludeNonSeed) {
    Write-Host '[sync-official] Auto-enable -IncludeNonSeed for weber.'
    $IncludeNonSeed = $true
}

$buildArgs = @(
    '-ExecutionPolicy', 'Bypass',
    '-File', (Join-Path $PSScriptRoot 'build_official_image_map.ps1')
)
if (-not [string]::IsNullOrWhiteSpace($brand)) {
    $buildArgs += @('-OnlyBrand', $brand)
}
if ($IncludeNonSeed) {
    $buildArgs += '-IncludeNonSeed'
}
if ($ForceDownload) {
    $buildArgs += '-ForceDownload'
}

Write-Host '[sync-official] Building official image map...'
powershell @buildArgs

$applyPhp = Get-Content -Raw -Path (Join-Path $PSScriptRoot 'apply_official_image_map.php')
$applyArgs = @('--')
if ($DryRunApply) {
    $applyArgs += '--dry-run'
}
if (-not [string]::IsNullOrWhiteSpace($brand)) {
    $applyArgs += ('--only-brand=' + $brand)
}

if ($DryRunApply) {
    Write-Host '[sync-official] Applying map to WordPress (dry-run)...'
} else {
    Write-Host '[sync-official] Applying map to WordPress...'
}
$applyPhp | docker exec -i lephat1898-wordpress-1 php @applyArgs

Write-Host '[sync-official] Done.'
