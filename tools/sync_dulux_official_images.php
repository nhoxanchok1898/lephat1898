<?php
/**
 * Sync featured product images from data/dulux_official.json by product slug.
 *
 * Run:
 *   Get-Content -Raw tools/sync_dulux_official_images.php | docker exec -i lephat1898-wordpress-1 php
 * Optional:
 *   ... php -- --dry-run
 */

require '/var/www/html/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

$argv = isset($_SERVER['argv']) && is_array($_SERVER['argv']) ? $_SERVER['argv'] : [];
$dry_run = in_array('--dry-run', $argv, true);

if (!function_exists('download_url')) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
}
if (!function_exists('media_handle_sideload')) {
    require_once ABSPATH . 'wp-admin/includes/media.php';
}
if (!function_exists('wp_generate_attachment_metadata')) {
    require_once ABSPATH . 'wp-admin/includes/image.php';
}

$theme_root = (string) get_theme_file_path();
$data_file = wp_normalize_path(trailingslashit($theme_root) . 'data/dulux_official.json');
$fallback_data_file = '/var/www/html/wp-content/themes/my-theme/data/dulux_official.json';
if (!file_exists($data_file) && file_exists($fallback_data_file)) {
    $data_file = $fallback_data_file;
}
if (!file_exists($data_file)) {
    fwrite(STDERR, "Data file not found: {$data_file}\n");
    exit(1);
}

$raw = file_get_contents($data_file);
$rows = json_decode((string) $raw, true);
if (!is_array($rows)) {
    fwrite(STDERR, "Invalid JSON in {$data_file}\n");
    exit(1);
}

$find_attachment_by_remote_url = static function (string $url): int {
    $url = trim((string) $url);
    if ($url === '') {
        return 0;
    }

    $cached = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_key' => '_official_remote_url',
        'meta_value' => $url,
    ]);
    if (empty($cached)) {
        return 0;
    }
    return (int) $cached[0];
};

$find_or_import_attachment = static function (string $url, string $hint_name) use ($find_attachment_by_remote_url): int {
    $url = trim((string) $url);
    if ($url === '') {
        return 0;
    }

    $cached_id = $find_attachment_by_remote_url($url);
    if ($cached_id > 0) {
        return $cached_id;
    }

    $tmp = download_url($url, 60);
    if (is_wp_error($tmp)) {
        return 0;
    }

    $path = (string) parse_url($url, PHP_URL_PATH);
    $filename = sanitize_file_name((string) basename($path));
    if ($filename === '' || strpos($filename, '.') === false) {
        $filename = sanitize_file_name(sanitize_title($hint_name) . '.jpg');
    }

    $file_array = [
        'name' => $filename,
        'tmp_name' => $tmp,
    ];

    $attach_id = media_handle_sideload($file_array, 0, $hint_name);
    if (is_wp_error($attach_id)) {
        @unlink($tmp);
        return 0;
    }

    update_post_meta((int) $attach_id, '_official_remote_url', $url);
    return (int) $attach_id;
};

$stats = [
    'total_rows' => 0,
    'matched_products' => 0,
    'updated' => 0,
    'unchanged' => 0,
    'missing_product' => 0,
    'missing_image' => 0,
    'import_error' => 0,
];

foreach ($rows as $row) {
    $stats['total_rows']++;

    $slug = sanitize_title((string) ($row['slug'] ?? ''));
    $image_url = esc_url_raw((string) ($row['image'] ?? ''));
    $source_page = esc_url_raw((string) ($row['url'] ?? ''));
    $official_name = trim((string) ($row['name'] ?? ''));

    if ($slug === '' || $image_url === '') {
        $stats['missing_image']++;
        continue;
    }

    $matched = get_posts([
        'post_type' => 'product',
        'post_status' => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => 1,
        'fields' => 'ids',
        'name' => $slug,
    ]);
    if (empty($matched)) {
        $stats['missing_product']++;
        continue;
    }

    $product_id = (int) $matched[0];
    $product = wc_get_product($product_id);
    if (!$product instanceof WC_Product) {
        $stats['missing_product']++;
        continue;
    }
    $stats['matched_products']++;

    if ($dry_run) {
        $attach_id = (int) $find_attachment_by_remote_url($image_url);
        if ($attach_id <= 0) {
            $attach_id = -1;
        }
    } else {
        $attach_id = $find_or_import_attachment(
            $image_url,
            $official_name !== '' ? $official_name : $product->get_name()
        );
    }
    if ($attach_id <= 0) {
        $stats['import_error']++;
        continue;
    }

    $current_thumb = (int) get_post_thumbnail_id($product_id);
    if ($current_thumb === $attach_id) {
        if (!$dry_run) {
            update_post_meta($product_id, '_official_source_page', $source_page);
            update_post_meta($product_id, '_official_source_image', $image_url);
            update_post_meta($product_id, '_official_image_synced_at', gmdate('c'));
            update_post_meta($product_id, '_official_image_map_file', 'data/dulux_official.json');
        }
        $stats['unchanged']++;
        continue;
    }

    if (!$dry_run) {
        set_post_thumbnail($product_id, $attach_id);

        $alt = trim((string) $product->get_name());
        if ($alt !== '') {
            update_post_meta($attach_id, '_wp_attachment_image_alt', $alt);
            wp_update_post([
                'ID' => $attach_id,
                'post_title' => $alt,
            ]);
        }

        update_post_meta($product_id, '_official_source_page', $source_page);
        update_post_meta($product_id, '_official_source_image', $image_url);
        update_post_meta($product_id, '_official_image_synced_at', gmdate('c'));
        update_post_meta($product_id, '_official_image_map_file', 'data/dulux_official.json');
    }

    $stats['updated']++;
}

if (!$dry_run) {
    if (function_exists('my_theme_flush_product_cache_fragments')) {
        my_theme_flush_product_cache_fragments(0);
    }
    update_option('my_theme_filter_cache_version', (string) time(), false);
}

echo 'dulux_official_image_sync_done '
    . 'total_rows=' . (int) $stats['total_rows']
    . ' matched_products=' . (int) $stats['matched_products']
    . ' updated=' . (int) $stats['updated']
    . ' unchanged=' . (int) $stats['unchanged']
    . ' missing_product=' . (int) $stats['missing_product']
    . ' missing_image=' . (int) $stats['missing_image']
    . ' import_error=' . (int) $stats['import_error']
    . ' dry_run=' . ($dry_run ? 'yes' : 'no')
    . "\n";
