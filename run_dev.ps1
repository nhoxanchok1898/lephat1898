<#
.SYNOPSIS
  Safe developer start script for this Django project.

.DESCRIPTION
  Runs steps separately to avoid hanging: activate venv, (optionally) install packages,
  apply migrations, then start the development server. Follow the prompts.

.PARAMETER InstallPackages
  If passed, installs packages from requirements.txt (network required).

.PARAMETER Background
  If passed, starts the dev server in a background process and returns immediately.

.PARAMETER Requirements
  Path to requirements file to install when -InstallPackages is used.

USAGE
  PowerShell (run from project root):
    Set-ExecutionPolicy -Scope Process -ExecutionPolicy RemoteSigned -Force
    .\run_dev.ps1 -InstallPackages

  Start dev server only (assumes venv already activated):
    .\run_dev.ps1
#>

param(
    [switch]$InstallPackages,
    [switch]$Background,
    [string]$Requirements
)

function Abort([string]$msg) {
    Write-Host $msg -ForegroundColor Red
    exit 1
}

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$venvPath = Join-Path $projectRoot '.venv'
$activateScript = Join-Path $venvPath 'Scripts\Activate.ps1'

Write-Host "Project: $projectRoot"

if (-not (Test-Path $activateScript)) {
    Write-Host ".venv not found at $venvPath" -ForegroundColor Yellow
    $create = Read-Host "Create virtualenv now? (y/n)"
    if ($create -ne 'y') { Abort 'User chose not to create virtualenv.' }
    Write-Host "Creating virtualenv at $venvPath..."
    python -m venv "$venvPath" || Abort 'Failed to create virtualenv using `python -m venv`.'
    if (-not (Test-Path $activateScript)) { Abort 'Activation script still missing after venv creation.' }
}

Write-Host "Activating virtualenv..."
& $activateScript
if ($LASTEXITCODE -ne 0) { Write-Host 'Warning: activation script returned non-zero exit code.' -ForegroundColor Yellow }

Write-Host "Upgrading pip (safe)..."
python -m pip install --upgrade pip

if ($InstallPackages) {
    if (-not $Requirements) { $Requirements = Join-Path $projectRoot 'requirements.txt' }
    if (-not (Test-Path $Requirements)) {
        Write-Host "Requirements file not found: $Requirements" -ForegroundColor Yellow
    } else {
        Write-Host "Installing packages from $Requirements (network required). Press Ctrl+C to cancel if it hangs.)"
        pip install -r "$Requirements"
    }
} else {
    Write-Host "Skipping package installation (use -InstallPackages to install)."
}

Write-Host "Applying Django migrations..."
python manage.py migrate --noinput 2>&1 | Tee-Object migrate.log
if ($LASTEXITCODE -ne 0) { Write-Host 'Migration reported non-zero exit code. Check migrate.log' -ForegroundColor Yellow }

if ($Background) {
    $stdoutLog = Join-Path $projectRoot 'runserver.out.log'
    $stderrLog = Join-Path $projectRoot 'runserver.err.log'
    Write-Host "Starting dev server in background (logs -> $stdoutLog / $stderrLog)"
    Start-Process -FilePath python -ArgumentList 'manage.py','runserver','0.0.0.0:8000' -RedirectStandardOutput $stdoutLog -RedirectStandardError $stderrLog
    Write-Host "Background server started. Tail logs with: Get-Content $stdoutLog -Wait"
} else {
    Write-Host "Starting dev server (foreground). Press Ctrl+C to stop. Logs are also written to runserver.log"
    python manage.py runserver 0.0.0.0:8000 2>&1 | Tee-Object runserver.log
}

Write-Host 'run_dev.ps1 finished.'
