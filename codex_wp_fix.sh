#!/usr/bin/env bash
set -euo pipefail

echo "▶ Fix WordPress theme auto-setup..."

# Work inside WP root
cd /var/www/html

# Bootstrap wp-cli if missing
if ! command -v wp >/dev/null 2>&1; then
  WP_CLI=/tmp/wp-cli.phar
  if [ ! -f "$WP_CLI" ]; then
    echo "→ Downloading wp-cli..."
    curl -sS -o "$WP_CLI" https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
  fi
  wp() { php "$WP_CLI" "$@"; }
else
  WP_BIN=$(command -v wp)
  wp() { "$WP_BIN" "$@"; }
fi

# 1) Set static homepage
wp option update show_on_front page >/dev/null
HOME_ID=$(wp post list --post_type=page --post_title="Trang chủ" --field=ID --posts_per_page=1 2>/dev/null || true)
if [ -z "${HOME_ID:-}" ]; then
  HOME_ID=$(wp post create --post_type=page --post_title="Trang chủ" --post_status=publish --porcelain)
fi
wp option update page_on_front "$HOME_ID" >/dev/null

BLOG_ID=$(wp post list --post_type=page --post_title="Bài viết" --field=ID --posts_per_page=1 2>/dev/null || true)
if [ -z "${BLOG_ID:-}" ]; then
  BLOG_ID=$(wp post create --post_type=page --post_title="Bài viết" --post_status=publish --porcelain)
fi
wp option update page_for_posts "$BLOG_ID" >/dev/null

# 2) Create menu & assign location
MENU_ID=$(wp menu list --format=ids 2>/dev/null | head -n1 || true)
if [ -z "${MENU_ID:-}" ]; then
  MENU_ID=$(wp menu create "Main Menu" --porcelain)
fi
wp menu item add-post "$MENU_ID" "$HOME_ID" --title="Trang chủ" >/dev/null || true
wp menu item add-post "$MENU_ID" "$BLOG_ID" --title="Bài viết" >/dev/null || true
wp menu location assign "$MENU_ID" primary >/dev/null || true

# 3) Create demo categories
for cat in "Sơn nội thất" "Sơn ngoại thất" "Chống thấm" "Bột trét"; do
  wp term create category "$cat" >/dev/null || true
done

# Helper to get category id by name
cat_id_by_name() {
  wp term list category --search="$1" --field=term_id --format=ids 2>/dev/null | head -n1
}

# 4) Create demo posts
CAT_ID=$(cat_id_by_name "Sơn nội thất")
for i in {1..6}; do
  wp post create \
    --post_title="Sản phẩm sơn mẫu $i" \
    --post_status=publish \
    --post_type=post \
    ${CAT_ID:+--post_category="$CAT_ID"} \
    >/dev/null || true
done

# 5) Flush permalinks
wp rewrite flush --hard >/dev/null

echo "✅ DONE. Reload frontend."
