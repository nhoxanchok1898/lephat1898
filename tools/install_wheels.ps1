param(
  [string]$WheelsPath = ".\wheels",
  [switch]$UpgradePip
)

if ($UpgradePip) {
  Write-Host "Upgrading pip/setuptools/wheel in current Python..."
  python -m pip install --upgrade pip setuptools wheel
}

if (-not (Test-Path $WheelsPath)) {
  Write-Error "Wheels path '$WheelsPath' not found. Copy wheels directory here first."
  exit 1
}

Write-Host "Installing packages from $WheelsPath (no network)..."
python -m pip install --no-index --find-links "$WheelsPath" --no-deps -r "$WheelsPath\packages.txt" 2>$null || {
  # Fallback: install all wheel files present
  Get-ChildItem -Path $WheelsPath -Filter '*.whl' | ForEach-Object {
    Write-Host "Installing $_"
    python -m pip install --no-index --find-links "$WheelsPath" "$_"
  }
}

Write-Host "Done. Run: python -m flake8 .   and   python -m black ."