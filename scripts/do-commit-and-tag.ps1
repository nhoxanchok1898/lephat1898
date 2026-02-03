Write-Host "=== UI Cart Optimistic Commit Script ===" -ForegroundColor Cyan

# 1. Check git
if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
    Write-Host "Git not found in PATH." -ForegroundColor Red
    Write-Host "Please install Git (https://git-scm.com/download/win) and restart PowerShell." -ForegroundColor Yellow
    exit 1
}

Write-Host "Git found:" -NoNewline; git --version

# 2. Files to commit
$files = @(
    "static/js/cart-qty.js",
    "static/css/cart-qty.css",
    "templates/store/base.html",
    "static/js/mini-cart.js",
    "static/css/mini-cart.css",
    "CHANGELOG.md"
)

# 3. Stage files
Write-Host "Staging files..."
$missing = @()
foreach ($f in $files) {
    if (-not (Test-Path $f)) { $missing += $f }
}
if ($missing.Count -gt 0) {
    Write-Host "Warning: some files not found:" -ForegroundColor Yellow
    $missing | ForEach-Object { Write-Host "  - $_" }
}

$existing = $files | Where-Object { Test-Path $_ }
if ($existing.Count -eq 0) { Write-Host "No files to add. Aborting." -ForegroundColor Red; exit 1 }

git add $existing
if ($LASTEXITCODE -ne 0) { Write-Host "git add failed." -ForegroundColor Red; exit 1 }

# 4. Commit
$commitMsg = "feat(cart): optimistic AJAX quantity updates (cart & mini-cart)"
Write-Host "Creating commit: $commitMsg"
git commit -m "$commitMsg" 2>&1 | ForEach-Object { Write-Host $_ }

if ($LASTEXITCODE -ne 0) {
    $status = git status --porcelain
    if ([string]::IsNullOrWhiteSpace($status)) { Write-Host "Nothing to commit (working tree clean)." -ForegroundColor Yellow }
    else { Write-Host "Commit failed. Inspect output above." -ForegroundColor Red; exit 1 }
}

# 5. Get short hash
$hash = (git rev-parse --short HEAD).Trim()
if (-not $hash) { Write-Host "Could not determine HEAD hash." -ForegroundColor Red; exit 1 }
Write-Host "Commit created:" $hash -ForegroundColor Green

# 6. Create annotated tag
$tag = "ui-cart-optimistic-v1"
Write-Host "Creating tag '$tag' -> $hash"
$tagExists = (git tag --list $tag)
if ($tagExists) {
    Write-Host "Tag '$tag' already exists. Use a different name or delete the existing tag first." -ForegroundColor Yellow
} else {
    git tag -a $tag $hash -m "UI: optimistic cart qty (mini-cart + cart page) v1"
    if ($LASTEXITCODE -ne 0) { Write-Host "Tag creation failed." -ForegroundColor Red; exit 1 }
    Write-Host "Tag created: $tag" -ForegroundColor Green
}

# 7. Push tag is optional; user can push manually
Write-Host "Done. To push the tag run: git push origin $tag" -ForegroundColor Cyan
