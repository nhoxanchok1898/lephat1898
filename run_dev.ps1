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
    [string]$Requirements,
    [switch]$Https
)

function Abort([string]$msg) {
    Write-Host $msg -ForegroundColor Red
    exit 1
}

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$venvPath = Join-Path $projectRoot '.venv'
$activateScript = Join-Path $venvPath 'Scripts\Activate.ps1'
$certFile = Join-Path $projectRoot 'local-dev.pem'
$keyFile = Join-Path $projectRoot 'local-dev-key.pem'
$script:lanIp = $null

function Get-LanIp {
    # Pick first private IPv4 that is not loopback
    $filter = { $_.IPAddress -match '^(192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[01])\.)' -and $_.IPAddress -ne '127.0.0.1' }
    $ip = Get-NetIPAddress -AddressFamily IPv4 -PrefixOrigin Dhcp -ErrorAction SilentlyContinue |
        Where-Object $filter |
        Select-Object -ExpandProperty IPAddress -First 1
    if (-not $ip) {
        $ip = Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue |
            Where-Object $filter |
            Select-Object -ExpandProperty IPAddress -First 1
    }
    $script:lanIp = $ip
}

function Ensure-HttpsCert {
    if (-not $Https) { return }
    if (-not (Get-Command mkcert -ErrorAction SilentlyContinue)) {
        Abort "mkcert chưa được cài (cần cho -Https). Cài bằng: choco install mkcert -y"
    }
    Get-LanIp
    if (-not $lanIp) {
        Abort "Không tìm thấy LAN IPv4 private. Kết nối mạng nội bộ rồi chạy lại để tạo cert với LAN IP."
    }
    Write-Host "Detected LAN IP for cert: $lanIp"
    if ((Test-Path $certFile)) { Remove-Item $certFile -Force }
    if ((Test-Path $keyFile)) { Remove-Item $keyFile -Force }
    Write-Host "Generating trusted dev certificate with mkcert -> $certFile / $keyFile"
    mkcert -key-file $keyFile -cert-file $certFile 127.0.0.1 localhost $lanIp | Out-Null
    Write-Warning "IMPORTANT: Please CLOSE and REOPEN your browser to clear SSL cache."
    Write-Warning "For MOBILE DEV: install mkcert-rootCA.pem as trusted CA on your phone."
    Write-Warning "For CocCoc on PC: run .\\fix_coccoc_ca.ps1 then restart Cốc Cốc."
}

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

if ($Https) {
    $checkModules = @(
        @{ name = 'django_extensions'; pip = 'django-extensions==3.2.3' },
        @{ name = 'werkzeug'; pip = 'Werkzeug==3.0.1' },
        @{ name = 'OpenSSL'; pip = 'pyOpenSSL>=23.0.0' }
    )
    $toInstall = @()
    foreach ($m in $checkModules) {
        python -c "import $($m.name)" 2>$null
        if ($LASTEXITCODE -ne 0) { $toInstall += $m.pip }
    }
    if ($toInstall.Count -gt 0) {
        Write-Host "Installing missing HTTPS dev deps: $($toInstall -join ', ')"
        pip install $toInstall
    }
}

Write-Host "Applying Django migrations..."
python manage.py migrate --noinput 2>&1 | Tee-Object migrate.log
if ($LASTEXITCODE -ne 0) { Write-Host 'Migration reported non-zero exit code. Check migrate.log' -ForegroundColor Yellow }

Ensure-HttpsCert

if ($Https) {
    # Guard: ensure OpenSSL import succeeds (PowerShell-safe)
    python -c "import OpenSSL" 2>$null
    if ($LASTEXITCODE -ne 0) {
        Write-Error "Python OpenSSL Library is required (pyOpenSSL). Run: pip install pyOpenSSL"
        exit 1
    }
}

$addr = '0.0.0.0:8000'
$httpsArgs = @('manage.py','runserver_plus',$addr,'--cert-file',$certFile,'--key-file',$keyFile)
$httpArgs = @('manage.py','runserver',$addr)

if ($Background) {
    $stdoutLog = Join-Path $projectRoot 'runserver.out.log'
    $stderrLog = Join-Path $projectRoot 'runserver.err.log'
    Write-Host "Starting dev server in background (logs -> $stdoutLog / $stderrLog)"
    if ($Https) {
        Start-Process -FilePath python -ArgumentList $httpsArgs -RedirectStandardOutput $stdoutLog -RedirectStandardError $stderrLog
        Write-Host "HTTPS dev server: https://127.0.0.1:8000 (runserver_plus)"
    } else {
        Start-Process -FilePath python -ArgumentList $httpArgs -RedirectStandardOutput $stdoutLog -RedirectStandardError $stderrLog
        Write-Host "HTTP dev server: http://127.0.0.1:8000 (runserver)"
    }
    Write-Host "Background server started. Tail logs with: Get-Content $stdoutLog -Wait"
} else {
    Write-Host "Starting dev server (foreground). Press Ctrl+C to stop. Logs are also written to runserver.log"
    if ($Https) {
        python @($httpsArgs) 2>&1 | Tee-Object runserver.log
    } else {
        python @($httpArgs) 2>&1 | Tee-Object runserver.log
    }
}

Write-Host 'run_dev.ps1 finished.'
