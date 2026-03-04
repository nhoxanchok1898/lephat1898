param(
    [switch]$DryRun,
    [int]$ArchiveRetentionDays = 14,
    [switch]$CleanPythonCaches,
    [switch]$TrimVSCodeProcesses,
    [int]$MaxCodeProcesses = 8
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Write-Info {
    param([string]$Message)
    Write-Host "[quick-clean] $Message"
}

function Move-FileSafely {
    param(
        [string]$SourcePath,
        [string]$DestinationPath
    )

    if ($DryRun) {
        Write-Info "DRY-RUN move: $SourcePath -> $DestinationPath"
        return
    }

    Move-Item -LiteralPath $SourcePath -Destination $DestinationPath -Force
}

function Remove-PathSafely {
    param([string]$PathToRemove)

    if ($DryRun) {
        Write-Info "DRY-RUN remove: $PathToRemove"
        return
    }

    Remove-Item -LiteralPath $PathToRemove -Recurse -Force
}

$repoRoot = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $repoRoot

Write-Info "Repo root: $repoRoot"

$archiveRoot = Join-Path $repoRoot '.tmp-archive'
$todayFolder = Get-Date -Format 'yyyy-MM-dd'
$archiveTarget = Join-Path $archiveRoot $todayFolder

if (!(Test-Path -LiteralPath $archiveTarget)) {
    if ($DryRun) {
        Write-Info "DRY-RUN create dir: $archiveTarget"
    } else {
        New-Item -ItemType Directory -Path $archiveTarget -Force | Out-Null
    }
}

$trackedTemp = @()
try {
    $trackedTemp = @(git ls-files "tmp_*" 2>$null)
} catch {
    $trackedTemp = @()
}

$tempFiles = Get-ChildItem -LiteralPath $repoRoot -File -Filter 'tmp_*' -ErrorAction SilentlyContinue
$movedCount = 0
$keptCount = 0

foreach ($file in $tempFiles) {
    if ($trackedTemp -contains $file.Name) {
        $keptCount++
        continue
    }

    $destination = Join-Path $archiveTarget $file.Name
    Move-FileSafely -SourcePath $file.FullName -DestinationPath $destination
    $movedCount++
}

Write-Info "Moved temp files: $movedCount"
Write-Info "Kept tracked temp files: $keptCount"

if ($ArchiveRetentionDays -lt 0) {
    $ArchiveRetentionDays = 0
}

if (Test-Path -LiteralPath $archiveRoot) {
    $cutoff = (Get-Date).AddDays(-$ArchiveRetentionDays)
    $oldFolders = Get-ChildItem -LiteralPath $archiveRoot -Directory -ErrorAction SilentlyContinue |
        Where-Object { $_.LastWriteTime -lt $cutoff }

    foreach ($folder in $oldFolders) {
        Remove-PathSafely -PathToRemove $folder.FullName
    }

    Write-Info ("Pruned archive folders older than {0} days: {1}" -f $ArchiveRetentionDays, (($oldFolders | Measure-Object).Count))
}

if ($CleanPythonCaches) {
    $cacheDirs = @(
        '__pycache__',
        '.pytest_cache',
        '.mypy_cache'
    )

    foreach ($dirName in $cacheDirs) {
        $matches = Get-ChildItem -LiteralPath $repoRoot -Directory -Recurse -Force -ErrorAction SilentlyContinue |
            Where-Object { $_.Name -eq $dirName }

        foreach ($match in $matches) {
            Remove-PathSafely -PathToRemove $match.FullName
        }

        Write-Info ("Removed cache directories named '{0}': {1}" -f $dirName, (($matches | Measure-Object).Count))
    }
}

$remainingTemp = @(Get-ChildItem -LiteralPath $repoRoot -File -Filter 'tmp_*' -ErrorAction SilentlyContinue)
Write-Info "Remaining temp files in repo root: $($remainingTemp.Count)"

if ($TrimVSCodeProcesses) {
    if ($MaxCodeProcesses -lt 1) {
        $MaxCodeProcesses = 1
    }
    if ($MaxCodeProcesses -gt 40) {
        $MaxCodeProcesses = 40
    }

    $codeProcesses = @(Get-Process -Name 'Code' -ErrorAction SilentlyContinue)
    $codeCount = $codeProcesses.Count
    Write-Info "Detected VS Code processes: $codeCount"

    if ($codeCount -le $MaxCodeProcesses) {
        Write-Info "No VS Code trim needed (limit: $MaxCodeProcesses)."
    } else {
        $ranked = $codeProcesses |
            Sort-Object `
                @{ Expression = { if ($_.MainWindowHandle -ne 0) { 1 } else { 0 } }; Descending = $true }, `
                @{ Expression = { try { $_.StartTime } catch { Get-Date '2000-01-01' } }; Descending = $true }

        $keep = @($ranked | Select-Object -First $MaxCodeProcesses)
        $keepIds = @($keep | ForEach-Object { [int]($_.Id) })
        $toStop = @($codeProcesses | Where-Object { $keepIds -notcontains [int]($_.Id) })

        foreach ($proc in $toStop) {
            if ($DryRun) {
                Write-Info ("DRY-RUN stop VS Code PID={0} WS={1:N1}MB" -f $proc.Id, ($proc.WorkingSet64 / 1MB))
                continue
            }
            try {
                Stop-Process -Id $proc.Id -Force -ErrorAction Stop
                Write-Info ("Stopped VS Code PID={0} WS={1:N1}MB" -f $proc.Id, ($proc.WorkingSet64 / 1MB))
            } catch {
                Write-Info ("Skip VS Code PID={0}: {1}" -f $proc.Id, $_.Exception.Message)
            }
        }

        Write-Info ("VS Code processes kept: {0}, trimmed: {1}" -f $keepIds.Count, $toStop.Count)
    }
}

Write-Info "Done."
