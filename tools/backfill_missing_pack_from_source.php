<?php
/**
 * Backfill missing capacity/weight metadata from official source pages.
 *
 * Run:
 *   Get-Content -Raw tools/backfill_missing_pack_from_source.php | docker compose -f docker-compose.wordpress.yml exec -T wordpress php
 * Optional:
 *   ... php -- --only-brand=weber
 *   ... php -- --dry-run
 */

require '/var/www/html/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

$args = array_values(array_filter((array) ($_SERVER['argv'] ?? []), static function ($value): bool {
    return is_string($value) && $value !== '';
}));
$dry_run = in_array('--dry-run', $args, true);
$only_brand = '';
foreach ($args as $index => $value) {
    if (strpos($value, '--only-brand=') === 0) {
        $only_brand = sanitize_title((string) substr($value, 13));
        break;
    }
    if ($value === '--only-brand' && isset($args[$index + 1])) {
        $only_brand = sanitize_title((string) $args[$index + 1]);
        break;
    }
}

$allowed_hosts = [
    'www.vn.weber',
    'vn.weber',
    'www.dulux.vn',
    'dulux.vn',
    'www.jotun.com',
    'jotun.com',
    'nipponpaint.com.vn',
    'www.kovapaint.com',
    'kovapaint.com',
    'www.toagroup.com.vn',
    'toagroup.com.vn',
    'apollosilicone.vn',
    'www.apollosilicone.vn',
    'www.sika.com',
    'vnm.sika.com',
    'www.sika.vn',
];

$detect_weber_family = static function (string $value): string {
    $value = sanitize_title($value);
    if ($value === '') {
        return '';
    }

    foreach (['webercolor', 'webertai', 'weberdry', 'weberproof', 'webertec', 'weberprime', 'weberepox', 'weberseal', 'weberad'] as $family) {
        if (strpos($value, $family) !== false) {
            return $family;
        }
    }

    return '';
};

$extract_pack_labels_from_text = static function (string $raw_text, bool $strict = false): array {
    $text = html_entity_decode($raw_text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = wp_strip_all_tags($text, true);
    $text = preg_replace('/\s+/u', ' ', trim((string) $text));
    if ($text === '') {
        return [];
    }

    if ($strict) {
        $patterns = [
            '/(?:khối\s*lượng|trọng\s*lượng|quy\s*cách|đóng\s*gói|bao\s*bì|pack|size)(?:\s*1\s*(?:bộ|bao|gói|thùng))?\s*[:\-]?\s*((?:thùng|carton|hộp)\s*\d+\s*(?:chai|sausage|tube|cartridge|ống|tuýp|tup)\b)/iu',
            '/(?:khối\s*lượng|trọng\s*lượng|quy\s*cách|đóng\s*gói|bao\s*bì|pack|size)(?:\s*1\s*(?:bộ|bao|gói|thùng))?\s*[:\-]?\s*((?:\d+\s*(?:x|\*)\s*\d+(?:[.,]\d+)?\s*(?:ml|l|lit|liter|litre|kg)))/iu',
            '/(?:khối\s*lượng|trọng\s*lượng|quy\s*cách|đóng\s*gói|bao\s*bì|pack|size)(?:\s*1\s*(?:bộ|bao|gói|thùng))?\s*[:\-]?\s*((?:\d+(?:[.,]\d+)?\s*(?:kg|ml|l|lit|liter|litre)(?:\s*\/\s*(?:chai|sausage|tube|cartridge|ống|tuýp|tup))?))/iu',
            '/(?:gói|bao|bộ)\s*((?:\d+(?:[.,]\d+)?\s*(?:kg|ml|l|lit|liter|litre)))/iu',
            '/((?:thùng|carton|hộp)\s*\d+\s*(?:chai|sausage|tube|cartridge|ống|tuýp|tup)\b)/iu',
        ];
    } else {
        $patterns = [
            '/((?:thùng|carton|hộp)\s*\d+\s*(?:chai|sausage|tube|cartridge|ống|tuýp|tup)\b)/iu',
            '/((?:\d+\s*(?:x|\*)\s*\d+(?:[.,]\d+)?\s*(?:ml|l|lit|liter|litre|kg)))/iu',
            '/((?:\d+(?:[.,]\d+)?\s*(?:kg|ml|l|lit|liter|litre)(?:\s*\/\s*(?:chai|sausage|tube|cartridge|ống|tuýp|tup))?))/iu',
        ];
    }

    $labels = [];
    foreach ($patterns as $pattern) {
        if (!preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
            continue;
        }

        foreach ((array) ($matches[1] ?? []) as $match) {
            if (!is_array($match) || count($match) < 2) {
                continue;
            }

            $label_raw = trim((string) $match[0]);
            $offset = (int) $match[1];
            if ($label_raw === '') {
                continue;
            }

            $tail = mb_strtolower((string) mb_substr($text, $offset + mb_strlen($label_raw), 12));
            if (preg_match('/^\s*\/\s*(m2|m²|m|mm|cm|cm2|cm²|bao|gio|phut|ngay)\b/u', $tail)) {
                continue;
            }

            $parsed = my_theme_parse_pack_label($label_raw);
            if (!$parsed || empty($parsed['label'])) {
                continue;
            }

            $labels[$parsed['label']] = $parsed['label'];
        }
    }

    return array_values($labels);
};

$fetch_source_html = static function (string $url) use ($allowed_hosts): string {
    $url = esc_url_raw($url);
    if ($url === '' || !wp_http_validate_url($url)) {
        return '';
    }

    $host = (string) wp_parse_url($url, PHP_URL_HOST);
    if ($host === '') {
        return '';
    }

    $host = strtolower($host);
    $allowed = false;
    foreach ($allowed_hosts as $allowed_host) {
        if ($host === strtolower($allowed_host)) {
            $allowed = true;
            break;
        }
    }
    if (!$allowed) {
        return '';
    }

    $response = wp_remote_get($url, [
        'timeout' => 20,
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (compatible; PaintStoreBot/1.0)',
            'Accept-Language' => 'vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7',
        ],
    ]);
    if (is_wp_error($response)) {
        return '';
    }

    return (string) wp_remote_retrieve_body($response);
};

$assign_pack_meta = static function (WC_Product $product, array $labels) use ($dry_run): bool {
    $labels = array_values(array_unique(array_filter(array_map('trim', $labels))));
    if (empty($labels)) {
        return false;
    }

    $capacity = my_theme_sort_pack_labels($labels, 'L');
    $weight = my_theme_sort_pack_labels($labels, 'kg');
    $is_putty = function_exists('my_theme_is_putty_product') ? my_theme_is_putty_product($product) : false;

    $category_slugs = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'slugs']);
    if (is_wp_error($category_slugs) || !is_array($category_slugs)) {
        $category_slugs = [];
    }

    $weight_priority_categories = ['bot-tret', 'keo-va-phu-gia', 'chong-tham'];
    $liter_priority_categories = ['son-noi-that', 'son-ngoai-that', 'son-lot', 'son-dau', 'son-kim-loai', 'son-cong-nghiep', 'son-epoxy'];

    if ($is_putty || function_exists('my_theme_slug_list_has_any') && my_theme_slug_list_has_any($category_slugs, $weight_priority_categories)) {
        $capacity = [];
    } elseif (function_exists('my_theme_slug_list_has_any') && my_theme_slug_list_has_any($category_slugs, $liter_priority_categories)) {
        $weight = [];
    }

    if (!empty($capacity) && !empty($weight)) {
        if (count($weight) >= count($capacity)) {
            $capacity = [];
        } else {
            $weight = [];
        }
    }

    if (empty($capacity) && empty($weight)) {
        return false;
    }

    if (!$dry_run) {
        if (!empty($capacity)) {
            $product->update_meta_data('_display_capacity_list', implode(' | ', $capacity));
        }
        if (!empty($weight)) {
            $product->update_meta_data('_display_weight_list', implode(' | ', $weight));
            if ($product->get_weight() === '') {
                $first = my_theme_parse_pack_label($weight[0]);
                if ($first && isset($first['unit'], $first['value']) && $first['unit'] === 'kg') {
                    $product->set_weight((string) $first['value']);
                }
            }
        }
        $product->save();
    }

    return true;
};

$stats = [
    'checked' => 0,
    'fetched' => 0,
    'updated' => 0,
    'skipped' => 0,
    'errors' => 0,
];

$source_cache = [];
$product_ids = function_exists('my_theme_get_catalog_visible_product_ids')
    ? my_theme_get_catalog_visible_product_ids(false)
    : [];

foreach ((array) $product_ids as $product_id) {
    $product_id = (int) $product_id;
    if ($product_id <= 0) {
        continue;
    }

    $product = wc_get_product($product_id);
    if (!$product instanceof WC_Product) {
        continue;
    }

    $brand = function_exists('my_theme_get_product_brand_slug')
        ? sanitize_title((string) my_theme_get_product_brand_slug($product))
        : '';
    if ($only_brand !== '' && $brand !== $only_brand) {
        continue;
    }

    $stats['checked']++;

    $capacity_raw = trim((string) $product->get_meta('_display_capacity_list'));
    $weight_raw = trim((string) $product->get_meta('_display_weight_list'));
    if ($capacity_raw !== '' || $weight_raw !== '') {
        $stats['skipped']++;
        continue;
    }

    $source_url = trim((string) $product->get_meta('_official_source_url'));
    if ($source_url === '') {
        $source_url = trim((string) $product->get_meta('_official_source_page'));
    }
    if ($source_url === '') {
        $stats['skipped']++;
        continue;
    }

    $weber_family_mismatch = false;
    if ($brand === 'weber') {
        $product_family = $detect_weber_family((string) $product->get_slug());
        $source_path = (string) wp_parse_url($source_url, PHP_URL_PATH);
        $source_slug = sanitize_title((string) basename($source_path));
        $source_family = $detect_weber_family($source_slug);
        $weber_family_mismatch = ($product_family !== '' && $source_family !== '' && $product_family !== $source_family);
    }

    $labels = [];
    $labels = array_merge($labels, $extract_pack_labels_from_text((string) $product->get_name(), false));
    if (!$weber_family_mismatch) {
        $labels = array_merge($labels, $extract_pack_labels_from_text((string) $product->get_short_description(), false));
        $labels = array_merge($labels, $extract_pack_labels_from_text((string) $product->get_description(), false));
    }

    if (empty($labels)) {
        if (!isset($source_cache[$source_url])) {
            $source_cache[$source_url] = $fetch_source_html($source_url);
            $stats['fetched']++;
        }
        $source_family_ok = true;
        if ($brand === 'weber') {
            $product_family = $detect_weber_family((string) $product->get_slug());
            $source_slug = (string) wp_parse_url($source_url, PHP_URL_PATH);
            $source_slug = sanitize_title((string) basename($source_slug));
            $source_family = $detect_weber_family($source_slug);
            if ($product_family !== '' && $source_family !== '' && $product_family !== $source_family) {
                $source_family_ok = false;
            }
        }

        if ($source_family_ok) {
            $labels = array_merge($labels, $extract_pack_labels_from_text((string) $source_cache[$source_url], true));
        }
    }

    $labels = array_values(array_unique(array_filter(array_map('trim', $labels))));
    if (empty($labels)) {
        $stats['skipped']++;
        continue;
    }

    if ($assign_pack_meta($product, $labels)) {
        $stats['updated']++;
        echo 'pack_backfill slug=' . $product->get_slug() . ' labels=' . implode(', ', $labels) . PHP_EOL;
    } else {
        $stats['skipped']++;
    }
}

if (!$dry_run) {
    if (function_exists('my_theme_flush_product_cache_fragments')) {
        my_theme_flush_product_cache_fragments(0);
    }
    update_option('my_theme_filter_cache_version', (string) time(), false);
}

echo 'pack_backfill_done'
    . ' checked=' . (int) $stats['checked']
    . ' fetched=' . (int) $stats['fetched']
    . ' updated=' . (int) $stats['updated']
    . ' skipped=' . (int) $stats['skipped']
    . ' errors=' . (int) $stats['errors']
    . ' dry_run=' . ($dry_run ? 'yes' : 'no')
    . ' only_brand=' . ($only_brand !== '' ? $only_brand : 'all')
    . PHP_EOL;
