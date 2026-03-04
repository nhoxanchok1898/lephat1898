<?php
/**
 * Audit live WooCommerce catalog completeness and media quality.
 *
 * Run:
 *   Get-Content -Raw tools/audit_catalog_completeness.php | docker compose -f docker-compose.wordpress.yml exec -T wordpress php
 * Optional:
 *   ... php -- --json
 */

require '/var/www/html/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

$argv = isset($_SERVER['argv']) && is_array($_SERVER['argv']) ? $_SERVER['argv'] : [];
$as_json = in_array('--json', $argv, true);

$theme_root = wp_normalize_path((string) get_theme_file_path());
$data_root = wp_normalize_path(trailingslashit($theme_root) . 'data');
$tools_root = wp_normalize_path('/var/www/html/wp-content/themes/my-theme');

$read_json_file = static function (string $path): array {
    if (!file_exists($path)) {
        return [];
    }
    $raw = file_get_contents($path);
    $data = json_decode((string) $raw, true);
    return is_array($data) ? $data : [];
};

$parse_seed_slugs = static function (string $path): array {
    if (!file_exists($path)) {
        return [];
    }

    $raw = (string) file_get_contents($path);
    if ($raw === '') {
        return [];
    }

    preg_match_all("/'slug'\\s*=>\\s*'([^']+)'/", $raw, $matches);
    if (empty($matches[1]) || !is_array($matches[1])) {
        return [];
    }

    $slugs = [];
    foreach ($matches[1] as $slug) {
        $slug = sanitize_title((string) $slug);
        if ($slug !== '') {
            $slugs[$slug] = $slug;
        }
    }

    return array_values($slugs);
};

$get_image_dimensions = static function (string $file_path): array {
    $file_path = wp_normalize_path($file_path);
    if ($file_path === '' || !file_exists($file_path)) {
        return [0, 0];
    }

    $size = @getimagesize($file_path);
    if (!is_array($size)) {
        return [0, 0];
    }

    return [
        isset($size[0]) ? max(0, (int) $size[0]) : 0,
        isset($size[1]) ? max(0, (int) $size[1]) : 0,
    ];
};

$get_wp_scaled_dimensions = static function (int $width, int $height, int $max_dim = 2560): array {
    $width = max(0, $width);
    $height = max(0, $height);
    if ($width <= 0 || $height <= 0) {
        return [0, 0];
    }
    $largest = max($width, $height);
    if ($largest <= $max_dim) {
        return [$width, $height];
    }

    $scale = $max_dim / $largest;
    return [
        (int) round($width * $scale),
        (int) round($height * $scale),
    ];
};

$product_ids = function_exists('my_theme_get_catalog_visible_product_ids')
    ? my_theme_get_catalog_visible_product_ids(false)
    : [];
$product_ids = array_values(array_filter(array_map('intval', (array) $product_ids), static function (int $id): bool {
    return $id > 0;
}));

$live_slugs = [];
$brand_counts = [];
$brand_missing_price = [];
$brand_low_res = [];
$brand_missing_pack = [];
$rows = [];

$official_image_rows = $read_json_file(wp_normalize_path(trailingslashit($data_root) . 'official_image_map.json'));
$official_image_map = [];
foreach ($official_image_rows as $row) {
    if (!is_array($row)) {
        continue;
    }
    $row_slug = sanitize_title((string) ($row['slug'] ?? ''));
    $row_id = isset($row['product_id']) ? (int) $row['product_id'] : 0;
    $key = $row_id > 0 ? 'id:' . $row_id : ($row_slug !== '' ? 'slug:' . $row_slug : '');
    if ($key === '') {
        continue;
    }
    $official_image_map[$key] = $row;
    if ($row_slug !== '') {
        $official_image_map['slug:' . $row_slug] = $row;
    }
}

foreach ($product_ids as $product_id) {
    $product = wc_get_product($product_id);
    if (!$product instanceof WC_Product) {
        continue;
    }

    $slug = sanitize_title((string) $product->get_slug());
    $name = trim((string) $product->get_name());
    $brand = function_exists('my_theme_get_product_brand_slug') ? sanitize_title((string) my_theme_get_product_brand_slug($product)) : '';
    $brand = $brand !== '' ? $brand : 'unknown';
    $price = trim((string) $product->get_price());
    $thumb_id = (int) $product->get_image_id();
    $desc = trim((string) get_post_field('post_content', $product_id));
    $short_desc = trim((string) get_post_field('post_excerpt', $product_id));
    $capacity_list = trim((string) get_post_meta($product_id, '_display_capacity_list', true));
    $weight_list = trim((string) get_post_meta($product_id, '_display_weight_list', true));
    $pack_list = trim((string) get_post_meta($product_id, '_display_pack_list', true));
    $source_url = trim((string) get_post_meta($product_id, '_official_source_url', true));
    $source_page = trim((string) get_post_meta($product_id, '_official_source_page', true));

    $live_slugs[$slug] = $slug;
    $brand_counts[$brand] = isset($brand_counts[$brand]) ? ((int) $brand_counts[$brand] + 1) : 1;

    $thumb_w = 0;
    $thumb_h = 0;
    $thumb_file = '';
    if ($thumb_id > 0) {
        $thumb_file = (string) get_attached_file($thumb_id);
        if ($thumb_file !== '') {
            [$thumb_w, $thumb_h] = $get_image_dimensions($thumb_file);
        } else {
            $meta = wp_get_attachment_metadata($thumb_id);
            if (is_array($meta)) {
                $thumb_w = isset($meta['width']) ? (int) $meta['width'] : 0;
                $thumb_h = isset($meta['height']) ? (int) $meta['height'] : 0;
            }
        }
    }

    $map_row = $official_image_map['id:' . $product_id] ?? ($official_image_map['slug:' . $slug] ?? null);
    $mapped_local_file = '';
    $mapped_w = 0;
    $mapped_h = 0;
    if (is_array($map_row)) {
        $mapped_local_file = trim((string) ($map_row['local_file'] ?? ''));
        if ($mapped_local_file !== '') {
            $mapped_path = wp_normalize_path(trailingslashit($tools_root) . ltrim(str_replace('assets/', 'assets/', $mapped_local_file), '/'));
            if (!file_exists($mapped_path)) {
                $mapped_path = wp_normalize_path(trailingslashit('/var/www/html/wp-content/themes/my-theme') . ltrim($mapped_local_file, '/'));
            }
            if (file_exists($mapped_path)) {
                [$mapped_w, $mapped_h] = $get_image_dimensions($mapped_path);
                $mapped_local_file = $mapped_path;
            }
        }
    }

    $thumb_area = $thumb_w * $thumb_h;
    $mapped_area = $mapped_w * $mapped_h;
    $has_pack = ($capacity_list !== '' || $weight_list !== '' || $pack_list !== '');
    $missing_price = ($price === '' || (float) $price <= 0);
    $low_res = ($thumb_id <= 0 || $thumb_w < 320 || $thumb_h < 320);
    [$scaled_mapped_w, $scaled_mapped_h] = $get_wp_scaled_dimensions($mapped_w, $mapped_h);
    $scaled_match = (
        $scaled_mapped_w > 0 &&
        $scaled_mapped_h > 0 &&
        abs($thumb_w - $scaled_mapped_w) <= 8 &&
        abs($thumb_h - $scaled_mapped_h) <= 8
    );
    $candidate_upgrade = (
        $mapped_area > 0 &&
        !$scaled_match &&
        ($thumb_area <= 0 || $mapped_area > ($thumb_area * 1.2))
    );

    if ($missing_price) {
        $brand_missing_price[$brand] = isset($brand_missing_price[$brand]) ? ((int) $brand_missing_price[$brand] + 1) : 1;
    }
    if ($low_res) {
        $brand_low_res[$brand] = isset($brand_low_res[$brand]) ? ((int) $brand_low_res[$brand] + 1) : 1;
    }
    if (!$has_pack) {
        $brand_missing_pack[$brand] = isset($brand_missing_pack[$brand]) ? ((int) $brand_missing_pack[$brand] + 1) : 1;
    }

    $rows[] = [
        'id' => $product_id,
        'slug' => $slug,
        'name' => $name,
        'brand' => $brand,
        'price' => $price,
        'has_price' => !$missing_price,
        'has_pack' => $has_pack,
        'has_description' => $desc !== '',
        'has_short_description' => $short_desc !== '',
        'has_source' => ($source_url !== '' || $source_page !== ''),
        'image_id' => $thumb_id,
        'image_width' => $thumb_w,
        'image_height' => $thumb_h,
        'low_res' => $low_res,
        'mapped_image_width' => $mapped_w,
        'mapped_image_height' => $mapped_h,
        'has_better_local_image' => $candidate_upgrade,
        'capacity_list' => $capacity_list,
        'weight_list' => $weight_list,
        'pack_list' => $pack_list,
        'source_url' => $source_url !== '' ? $source_url : $source_page,
    ];
}

$dulux_rows = $read_json_file(wp_normalize_path(trailingslashit($data_root) . 'dulux_official.json'));
$dulux_source_slugs = [];
foreach ($dulux_rows as $row) {
    if (!is_array($row)) {
        continue;
    }
    $slug = sanitize_title((string) ($row['slug'] ?? ''));
    if ($slug !== '') {
        $dulux_source_slugs[$slug] = $slug;
    }
}

$seed_slugs = $parse_seed_slugs('/var/www/html/tools/import_multibrand_seed_catalog.php');

$live_slug_values = array_values($live_slugs);
$dulux_missing = array_values(array_diff(array_values($dulux_source_slugs), $live_slug_values));
$seed_missing = array_values(array_diff($seed_slugs, $live_slug_values));

usort($rows, static function (array $a, array $b): int {
    $brand_cmp = strcmp((string) $a['brand'], (string) $b['brand']);
    if ($brand_cmp !== 0) {
        return $brand_cmp;
    }
    return strcmp((string) $a['slug'], (string) $b['slug']);
});

$upgrade_candidates = array_values(array_filter($rows, static function (array $row): bool {
    return !empty($row['has_better_local_image']);
}));

$summary = [
    'live_total' => count($rows),
    'brand_counts' => $brand_counts,
    'missing_price_total' => count(array_filter($rows, static function (array $row): bool {
        return empty($row['has_price']);
    })),
    'missing_pack_total' => count(array_filter($rows, static function (array $row): bool {
        return empty($row['has_pack']);
    })),
    'low_res_total' => count(array_filter($rows, static function (array $row): bool {
        return !empty($row['low_res']);
    })),
    'better_local_image_total' => count($upgrade_candidates),
    'dulux_source_total' => count($dulux_source_slugs),
    'dulux_missing_live_total' => count($dulux_missing),
    'seed_source_total' => count($seed_slugs),
    'seed_missing_live_total' => count($seed_missing),
    'brand_missing_price' => $brand_missing_price,
    'brand_low_res' => $brand_low_res,
    'brand_missing_pack' => $brand_missing_pack,
];

if ($as_json) {
    echo wp_json_encode([
        'summary' => $summary,
        'missing_from_dulux_source' => $dulux_missing,
        'missing_from_seed_source' => $seed_missing,
        'upgrade_candidates' => $upgrade_candidates,
        'products' => $rows,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    echo "\n";
    exit(0);
}

echo 'catalog_audit'
    . ' live_total=' . (int) $summary['live_total']
    . ' missing_price=' . (int) $summary['missing_price_total']
    . ' missing_pack=' . (int) $summary['missing_pack_total']
    . ' low_res=' . (int) $summary['low_res_total']
    . ' better_local_image=' . (int) $summary['better_local_image_total']
    . ' dulux_missing=' . (int) $summary['dulux_missing_live_total']
    . ' seed_missing=' . (int) $summary['seed_missing_live_total']
    . "\n";

foreach ($brand_counts as $brand => $count) {
    $line = 'brand=' . $brand
        . ' total=' . (int) $count
        . ' missing_price=' . (int) ($brand_missing_price[$brand] ?? 0)
        . ' missing_pack=' . (int) ($brand_missing_pack[$brand] ?? 0)
        . ' low_res=' . (int) ($brand_low_res[$brand] ?? 0);
    echo $line . "\n";
}

if (!empty($dulux_missing)) {
    echo 'dulux_missing_slugs=' . implode(',', array_slice($dulux_missing, 0, 20)) . "\n";
}
if (!empty($seed_missing)) {
    echo 'seed_missing_slugs=' . implode(',', array_slice($seed_missing, 0, 20)) . "\n";
}
if (!empty($upgrade_candidates)) {
    $preview = array_slice($upgrade_candidates, 0, 12);
    foreach ($preview as $row) {
        echo 'upgrade_candidate slug=' . $row['slug']
            . ' brand=' . $row['brand']
            . ' current=' . $row['image_width'] . 'x' . $row['image_height']
            . ' mapped=' . $row['mapped_image_width'] . 'x' . $row['mapped_image_height']
            . "\n";
    }
}
