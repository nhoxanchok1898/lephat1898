<#
.SYNOPSIS
  Resume Django when needed.

.DESCRIPTION
  Starts Django in either local dev mode (run_dev.ps1) or Docker mode.

.PARAMETER Mode
  local  - call run_dev.ps1 (default)
  docker - docker compose up for docker-compose.yml

.PARAMETER Background
  Passed to run_dev.ps1 in local mode.

.PARAMETER Https
  Passed to run_dev.ps1 in local mode.

.USAGE
  .\resume_django.ps1
  .\resume_django.ps1 -Mode local -Background
  .\resume_django.ps1 -Mode docker
#>

param(
    [ValidateSet('local', 'docker')]
    [string]$Mode = 'local',
    [switch]$Background,
    [switch]$Https
)

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$runDevScript = Join-Path $projectRoot 'run_dev.ps1'
$djangoCompose = Join-Path $projectRoot 'docker-compose.yml'

Set-Location $projectRoot

if ($Mode -eq 'docker') {
    if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
        Write-Error 'Docker is not available on this machine.'
        exit 1
    }
    if (-not (Test-Path $djangoCompose)) {
        Write-Error "Cannot find compose file: $djangoCompose"
        exit 1
    }

    docker compose -f $djangoCompose up -d --build | Out-Host
    Write-Host 'Django Docker service started at http://localhost:8000' -ForegroundColor Green
    exit 0
}

if (-not (Test-Path $runDevScript)) {
    Write-Error "Cannot find run script: $runDevScript"
    exit 1
}

if ($Background) {
    & $runDevScript -Background:$true -Https:$Https
} else {
    & $runDevScript -Https:$Https
}
