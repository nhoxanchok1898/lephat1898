<#
.SYNOPSIS
  Import mkcert Root CA into Cốc Cốc (Chromium NSS) profile to remove "Không bảo mật".

.DESCRIPTION
  - Finds Cốc Cốc default profile (NSS sql DB).
  - Ensures mkcert Root CA exists.
  - Ensures an NSS `certutil` binary is available (prefers shipped ones; can download a portable NSS tools zip as fallback).
  - Imports mkcert Root CA into the NSS store.
  - Reminds user to restart browser.

Usage:
  powershell -ExecutionPolicy Bypass -File .\fix_coccoc_ca.ps1
#>

$ErrorActionPreference = 'Stop'

function Abort([string]$msg) {
    Write-Host $msg -ForegroundColor Red
    exit 1
}

 $userDataDir = Join-Path $env:LOCALAPPDATA "CocCoc\Browser\User Data"
 if (-not (Test-Path $userDataDir)) {
    Abort "Không tìm thấy thư mục User Data của Cốc Cốc tại $userDataDir. Mở Cốc Cốc một lần để tạo profile rồi chạy lại."
 }

 $mkcertCA = Join-Path $env:LOCALAPPDATA "mkcert\rootCA.pem"
 if (-not (Test-Path $mkcertCA)) {
    Abort "Không tìm thấy mkcert Root CA tại $mkcertCA. Hãy chạy: mkcert -install"
 }

function Find-NssCertutil {
    $candidates = @(
        (Join-Path $env:ProgramFiles "CocCoc\Browser\Application\certutil.exe"),
        (Join-Path ${env:ProgramFiles} "Mozilla Firefox\certutil.exe"),
        (Join-Path ${env:ProgramFiles(x86)} "Mozilla Firefox\certutil.exe"),
        (Join-Path $env:ProgramFiles "NSS\bin\certutil.exe"),
        (Join-Path $env:LOCALAPPDATA "nss-tools\certutil.exe")
    ) | Where-Object { $_ -and (Test-Path $_) }
    if ($candidates.Count -gt 0) { return $candidates[0] }

    # Fallback: download portable NSS tools (Windows x64) to %LOCALAPPDATA%\nss-tools
    $downloadDir = Join-Path $env:LOCALAPPDATA "nss-tools"
    New-Item -ItemType Directory -Force -Path $downloadDir | Out-Null
    $zipPath = Join-Path $downloadDir "nss-tools.zip"
    $urls = @(
        "https://archive.mozilla.org/pub/security/nss/releases/NSS_3_90_RTM/MSVC2017/Release_ZLIB/nss-3.90-with-nspr-4.35-win64.zip",
        "https://archive.mozilla.org/pub/security/nss/releases/NSS_3_80_RTM/MSVC2015/Release_ZLIB/nss-3.80-with-nspr-4.32-win64.zip"
    )
    $downloaded = $false
    foreach ($u in $urls) {
        try {
            Write-Host "Downloading NSS tools from $u ..."
            Invoke-WebRequest -Uri $u -OutFile $zipPath -UseBasicParsing
            $downloaded = $true
            break
        } catch {
            Write-Host "Download failed: $u" -ForegroundColor Yellow
        }
    }
    if (-not $downloaded) {
        Abort "Không tải được NSS tools (certutil). Hãy cài Firefox hoặc đặt certutil.exe vào PATH rồi chạy lại."
    }
    Write-Host "Extracting NSS tools..."
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    [System.IO.Compression.ZipFile]::ExtractToDirectory($zipPath, $downloadDir, $true)
    $certutilPath = Get-ChildItem -Recurse -Path $downloadDir -Filter certutil.exe -ErrorAction SilentlyContinue | Select-Object -First 1 -ExpandProperty FullName
    if (-not $certutilPath) { Abort "Không tìm thấy certutil.exe trong gói NSS tải về." }
    return $certutilPath
}

 $certutil = Find-NssCertutil
 Write-Host "Using certutil: $certutil"

 # Find all profiles containing cert9.db
 $profiles = Get-ChildItem -Path $userDataDir -Directory -ErrorAction SilentlyContinue | Where-Object {
    Test-Path (Join-Path $_.FullName "cert9.db")
 }

 if (-not $profiles -or $profiles.Count -eq 0) {
    Abort "Không tìm thấy profile Cốc Cốc nào có cert9.db dưới $userDataDir."
 }

 foreach ($p in $profiles) {
    $dbPath = "sql:$($p.FullName)"
    Write-Host "Importing mkcert Root CA into NSS DB at $dbPath ..."
    & $certutil -d $dbPath -A -t "C,," -n "mkcert-dev" -i $mkcertCA
    if ($LASTEXITCODE -ne 0) {
        Write-Host "⚠️  Import failed for profile $($p.Name) (exit $LASTEXITCODE). Check log above." -ForegroundColor Yellow
    } else {
        Write-Host "✅ Imported into profile: $($p.Name)"
    }
 }

 Write-Host ""
 Write-Host "SUCCESS: mkcert Root CA imported into ALL CocCoc profiles." -ForegroundColor Green
 Write-Host "Please CLOSE ALL CocCoc windows and reopen." -ForegroundColor Yellow
 Write-Host "If mobile cần tin cậy: copy mkcert-rootCA.pem sang điện thoại và cài làm CA tin cậy." -ForegroundColor Yellow
