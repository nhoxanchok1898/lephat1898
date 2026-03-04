<#
.SYNOPSIS
  Park (pause) Django services without deleting any files.

.DESCRIPTION
  - Stops local Django dev Python processes (runserver/runserver_plus/gunicorn).
  - Brings down docker-compose.yml stack (Django stack) if Docker is available.
  - Optionally starts WordPress stack immediately.

.PARAMETER StartWordPress
  If passed, start docker-compose.wordpress.yml after parking Django.

.USAGE
  .\park_django.ps1
  .\park_django.ps1 -StartWordPress
#>

param(
    [switch]$StartWordPress
)

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$djangoCompose = Join-Path $projectRoot 'docker-compose.yml'
$wpCompose = Join-Path $projectRoot 'docker-compose.wordpress.yml'

function Write-Step([string]$message) {
    Write-Host $message -ForegroundColor Cyan
}

function Stop-DjangoPythonProcesses {
    Write-Step 'Checking local Django processes...'

    $stopped = 0
    $pythonProcs = Get-CimInstance Win32_Process -Filter "Name='python.exe'" -ErrorAction SilentlyContinue
    foreach ($proc in $pythonProcs) {
        $cmd = [string]$proc.CommandLine
        if ($cmd -match 'manage\.py\s+runserver' -or
            $cmd -match 'manage\.py\s+runserver_plus' -or
            $cmd -match 'gunicorn\s+paint_store\.wsgi:application') {
            try {
                Stop-Process -Id $proc.ProcessId -Force -ErrorAction Stop
                $stopped++
            } catch {
                Write-Host "Could not stop PID $($proc.ProcessId): $($_.Exception.Message)" -ForegroundColor Yellow
            }
        }
    }

    if ($stopped -gt 0) {
        Write-Host "Stopped $stopped Django process(es)." -ForegroundColor Green
    } else {
        Write-Host 'No local Django dev process found.' -ForegroundColor DarkGray
    }
}

function Try-DockerComposeDown([string]$composeFile, [string]$label) {
    if (-not (Test-Path $composeFile)) {
        return
    }
    if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
        Write-Host "Docker not found. Skip $label down." -ForegroundColor DarkGray
        return
    }

    Write-Step "Stopping $label..."
    docker compose -f $composeFile stop | Out-Host
    docker compose -f $composeFile rm -f | Out-Host
}

function Try-DockerComposeUp([string]$composeFile, [string]$label) {
    if (-not (Test-Path $composeFile)) {
        Write-Host "$label compose file not found: $composeFile" -ForegroundColor Yellow
        return
    }
    if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
        Write-Host "Docker not found. Cannot start $label." -ForegroundColor Yellow
        return
    }

    Write-Step "Starting $label..."
    docker compose -f $composeFile up -d | Out-Host
}

Set-Location $projectRoot

Stop-DjangoPythonProcesses
Try-DockerComposeDown -composeFile $djangoCompose -label 'Django stack'

if ($StartWordPress) {
    Try-DockerComposeUp -composeFile $wpCompose -label 'WordPress stack'
    Write-Host 'WordPress URL: http://localhost:8080' -ForegroundColor Green
}

Write-Host 'Django is now parked. Nothing was deleted.' -ForegroundColor Green
Write-Host 'To resume Django later: .\resume_django.ps1' -ForegroundColor Green
