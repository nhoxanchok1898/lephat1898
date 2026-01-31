# PowerShell helper: stage files and create commit for optimistic-mini-cart milestone
git add static/js/cart-qty.js static/css/cart-qty.css templates/store/base.html static/js/mini-cart.js static/css/mini-cart.css CHANGELOG.md
git commit -m "feat(cart): optimistic AJAX quantity updates (cart & mini-cart)" -m "Add optimistic frontend for cart quantity updates and mini-cart bindings" -m "Disable controls during in-flight; revert on failure with toast" -m "Subtotal/total highlight animations; badge sync" -m "No backend changes"
if ($LASTEXITCODE -eq 0) { git rev-parse --short HEAD }
