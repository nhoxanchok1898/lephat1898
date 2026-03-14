param(
    [switch]$DryRun,
    [switch]$StopLanguageServers,
    [ValidateSet('Idle', 'BelowNormal', 'Normal')]
    [string]$ChildPriority = 'BelowNormal'
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Write-Info {
    param([string]$Message)
    Write-Host "[vscode-lite] $Message"
}

$codeProcesses = @(Get-Process Code -ErrorAction SilentlyContinue)
if ($codeProcesses.Count -eq 0) {
    Write-Info "No Code.exe process found."
    exit 0
}

$mainWindowProcess = $codeProcesses |
    Where-Object { $_.MainWindowHandle -ne 0 } |
    Sort-Object StartTime |
    Select-Object -First 1

if (-not $mainWindowProcess) {
    $mainWindowProcess = $codeProcesses | Sort-Object StartTime | Select-Object -First 1
}

Write-Info ("Main VS Code PID: {0}" -f $mainWindowProcess.Id)

$wmi = @(Get-CimInstance Win32_Process -Filter "Name='Code.exe'")
$children = @()
foreach ($proc in $wmi) {
    if ([int]$proc.ParentProcessId -eq [int]$mainWindowProcess.Id) {
        $children += $proc
    }
}

Write-Info ("Child process count: {0}" -f $children.Count)

$priorityUpdated = 0
foreach ($child in $children) {
    $procId = [int]$child.ProcessId
    if ($procId -eq [int]$mainWindowProcess.Id) {
        continue
    }

    try {
        $p = Get-Process -Id $procId -ErrorAction Stop
    } catch {
        continue
    }

    $current = [string]$p.PriorityClass
    if ($current -eq $ChildPriority) {
        continue
    }

    if ($DryRun) {
        Write-Info ("DRY-RUN priority: PID {0} {1} -> {2}" -f $procId, $current, $ChildPriority)
    } else {
        try {
            $p.PriorityClass = $ChildPriority
            $priorityUpdated++
            Write-Info ("Priority updated: PID {0} {1} -> {2}" -f $procId, $current, $ChildPriority)
        } catch {
            Write-Info ("Skip priority update PID {0}: {1}" -f $procId, $_.Exception.Message)
        }
    }
}

Write-Info ("Priority updated count: {0}" -f $priorityUpdated)

if ($StopLanguageServers) {
    $pattern = 'jsonServerMain|vscode-pylance.*server\.bundle\.js'
    $targets = $wmi | Where-Object { $_.CommandLine -match $pattern }
    $killed = 0

    foreach ($target in $targets) {
        $procId = [int]$target.ProcessId
        if ($DryRun) {
            Write-Info ("DRY-RUN stop language server PID {0}" -f $procId)
            continue
        }

        try {
            Stop-Process -Id $procId -Force -ErrorAction Stop
            $killed++
            Write-Info ("Stopped language server PID {0}" -f $procId)
        } catch {
            Write-Info ("Skip stop PID {0}: {1}" -f $procId, $_.Exception.Message)
        }
    }

    Write-Info ("Language server stopped count: {0}" -f $killed)
}

Write-Info "Done."
