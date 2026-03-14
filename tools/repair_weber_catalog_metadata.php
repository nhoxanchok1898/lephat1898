<?php
/**
 * Repair Weber source URLs and packaging metadata with verified official pages.
 *
 * Run:
 *   Get-Content -Raw tools/repair_weber_catalog_metadata.php | docker compose -f docker-compose.wordpress.yml exec -T wordpress php
 * Optional:
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

$overrides = [
    'weberad-latex' => [
        'source_url' => 'https://www.vn.weber/vi/weberad-latex',
        'capacity' => ['5L'],
    ],
    'keo-dan-gach-webertai-fix-40kg' => [
        'source_url' => 'https://www.vn.weber/vi/keo-dan-gach-webertai-fix-40kg',
        'weight' => ['40kg'],
    ],
    'keo-dan-gach-webertai-gres-40kg' => [
        'source_url' => 'https://www.vn.weber/vi/keo-dan-gach-webertai-gres-40kg',
        'weight' => ['40kg'],
    ],
    'keo-dan-gach-webertai-vis-40kg' => [
        'source_url' => 'https://www.vn.weber/vi/keo-dan-gach-webertai-vis-40kg',
        'weight' => ['40kg'],
    ],
    'weberepox-easy' => [
        'source_url' => 'https://www.vn.weber/vi/weberepox-easy',
        'weight' => ['5kg'],
    ],
    'weberprime-epox' => [
        'source_url' => 'https://www.vn.weber/vi/weberprime-epox-094',
        'pack' => ['Bộ 16kg (A 8kg + B 8kg)'],
    ],
    'weberprime-spf' => [
        'source_url' => 'https://www.vn.weber/vi/weberprime-spf-11',
        'pack' => ['Thùng 17kg'],
    ],
    'weberproof-hdpe' => [
        'source_url' => 'https://www.vn.weber/vi/weberproof-hdpe',
        'pack' => ['Cuộn 1m x 20m'],
    ],
    'weberproof-tpo' => [
        'source_url' => 'https://www.vn.weber/vi/weberproof-tpo',
        'pack' => ['Cuộn 1m x 20m'],
    ],
    'weberseal-wa100' => [
        'source_url' => 'https://www.vn.weber/vi/weberseal-wa100',
        'pack' => ['450g/chai', 'Thùng 24 chai'],
    ],
    'weberseal-ws300' => [
        'source_url' => 'https://www.vn.weber/vi/weberseal-ws300',
        'capacity' => ['300ml/chai'],
    ],
    'weberseal-ws500' => [
        'source_url' => 'https://www.vn.weber/vi/weberseal-ws500',
        'capacity' => ['300ml/chai', '600ml/sausage'],
    ],
    'webershield' => [
        'source_url' => 'https://www.vn.weber/vi/webershield-320',
        'pack' => ['Bộ 18L (A 14.4L + B 3.6L)'],
    ],
    'webertai-fix' => [
        'source_url' => 'https://www.vn.weber/vi/webertai-fix',
        'weight' => ['25kg'],
    ],
    'webertai-flex' => [
        'source_url' => 'https://www.vn.weber/vi/webertai-flex',
        'weight' => ['20kg'],
    ],
    'webertai-gres' => [
        'source_url' => 'https://www.vn.weber/vi/keo-dan-gach-webertai-gres-40kg',
        'weight' => ['40kg'],
    ],
    'webertai-st250' => [
        'source_url' => 'https://www.vn.weber/vi/webertai-ST250',
        'weight' => ['25kg'],
    ],
    'webertai-vis' => [
        'source_url' => 'https://www.vn.weber/vi/keo-dan-gach-webertai-vis-40kg',
        'weight' => ['40kg'],
    ],
    'keo-cha-ron-webercolor-classic' => [
        'source_url' => 'https://www.vn.weber/vi/webercolor-no-stain',
        'weight' => ['1kg'],
    ],
    'webercolor-classic' => [
        'source_url' => 'https://www.vn.weber/vi/webercolor-no-stain',
        'weight' => ['1kg'],
    ],
    'webercolor-classic-2023' => [
        'source_url' => 'https://www.vn.weber/vi/webercolor-no-stain',
        'weight' => ['1kg'],
    ],
    'webercolor-classic-ps' => [
        'source_url' => 'https://www.vn.weber/vi/webercolor-no-stain',
        'weight' => ['1kg'],
    ],
    'webercolor-hr' => [
        'source_url' => 'https://www.vn.weber/vi/webercolor-no-stain',
        'weight' => ['1kg'],
    ],
    'webercolor-mosaic' => [
        'source_url' => 'https://www.vn.weber/vi/webercolor-no-stain',
        'weight' => ['1kg'],
    ],
    'webercolor-no-stain' => [
        'source_url' => 'https://www.vn.weber/vi/webercolor-no-stain',
        'weight' => ['1kg'],
    ],
    'webercolor-outside' => [
        'source_url' => 'https://www.vn.weber/vi/webercolor-no-stain',
        'weight' => ['1kg'],
    ],
    'webercolor-power-po111s' => [
        'source_url' => 'https://www.vn.weber/vi/webercolor-no-stain',
        'weight' => ['1kg'],
    ],
    'webercolor-power-ps' => [
        'source_url' => 'https://www.vn.weber/vi/webercolor-no-stain',
        'weight' => ['1kg'],
    ],
    'webercolor-shine' => [
        'source_url' => 'https://www.vn.weber/vi/webercolor-no-stain',
        'weight' => ['1kg'],
    ],
    'webercolor-sieu-trang-g68s' => [
        'source_url' => 'https://www.vn.weber/vi/webercolor-no-stain',
        'weight' => ['1kg'],
    ],
    'webercolor-slim' => [
        'source_url' => 'https://www.vn.weber/vi/webercolor-no-stain',
        'weight' => ['1kg'],
    ],
    'webercolor-sp' => [
        'source_url' => 'https://www.vn.weber/vi/webercolor-no-stain',
        'weight' => ['1kg'],
    ],
    'webercolor-sp-ho-boi' => [
        'source_url' => 'https://www.vn.weber/vi/webercolor-no-stain',
        'weight' => ['1kg'],
    ],
    'weberdry-2kflex' => [
        'source_url' => 'https://www.vn.weber/vi/weberdry-2kflex',
        'weight' => ['25kg'],
    ],
    'weberdry-crystallize-hybrid' => [
        'source_url' => 'https://www.vn.weber/vi/weberdry-crystallize-hybrid',
        'weight' => ['25kg'],
    ],
    'weberdry-pu' => [
        'source_url' => 'https://www.vn.weber/vi/weberdry-pu',
        'weight' => ['25kg'],
    ],
    'weberdry-pu-pro' => [
        'source_url' => 'https://www.vn.weber/vi/weberdry-pu-pro',
        'weight' => ['25kg'],
    ],
    'weberdry-seal-pro' => [
        'source_url' => 'https://www.vn.weber/vi/weberdry-seal-pro',
        'weight' => ['25kg'],
    ],
    'weberdry-top' => [
        'source_url' => 'https://www.vn.weber/vi/weberdry-top',
        'weight' => ['25kg'],
    ],
    'webertec-grout' => [
        'source_url' => 'https://www.vn.weber/vi/webertec-grout-60',
        'weight' => ['25kg'],
    ],
];

$stats = [
    'checked' => 0,
    'updated' => 0,
    'skipped' => 0,
    'errors' => 0,
];

foreach ($overrides as $slug => $data) {
    $slug = sanitize_title((string) $slug);
    if ($slug === '') {
        continue;
    }

    $stats['checked']++;
    $ids = get_posts([
        'post_type' => 'product',
        'post_status' => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => 1,
        'fields' => 'ids',
        'name' => $slug,
    ]);
    if (empty($ids)) {
        $stats['errors']++;
        echo 'repair_weber missing slug=' . $slug . PHP_EOL;
        continue;
    }

    $product_id = (int) $ids[0];
    $product = wc_get_product($product_id);
    if (!$product instanceof WC_Product) {
        $stats['errors']++;
        continue;
    }

    $source_url = isset($data['source_url']) ? esc_url_raw((string) $data['source_url']) : '';
    $capacity = isset($data['capacity']) && is_array($data['capacity']) ? array_values(array_filter(array_map('trim', $data['capacity']))) : [];
    $weight = isset($data['weight']) && is_array($data['weight']) ? array_values(array_filter(array_map('trim', $data['weight']))) : [];
    $pack = isset($data['pack']) && is_array($data['pack']) ? array_values(array_filter(array_map('trim', $data['pack']))) : [];

    $dirty = false;
    if ($source_url !== '') {
        $current_source = trim((string) get_post_meta($product_id, '_official_source_page', true));
        if ($current_source !== $source_url) {
            $dirty = true;
            if (!$dry_run) {
                update_post_meta($product_id, '_official_source_page', $source_url);
                update_post_meta($product_id, '_official_source_url', $source_url);
            }
        }
    }

    $updates = [
        '_display_capacity_list' => !empty($capacity) ? implode(' | ', $capacity) : '',
        '_display_weight_list' => !empty($weight) ? implode(' | ', $weight) : '',
        '_display_pack_list' => !empty($pack) ? implode(' | ', $pack) : '',
    ];

    foreach ($updates as $meta_key => $value) {
        $current_value = trim((string) $product->get_meta($meta_key));
        if ($current_value === $value) {
            continue;
        }
        $dirty = true;
        if ($dry_run) {
            continue;
        }
        if ($value !== '') {
            $product->update_meta_data($meta_key, $value);
        } else {
            $product->delete_meta_data($meta_key);
        }
    }

    if (!$dirty) {
        $stats['skipped']++;
        continue;
    }

    if (!$dry_run) {
        $product->save();
    }

    $stats['updated']++;
    echo 'repair_weber slug=' . $slug
        . ' source=' . $source_url
        . ' capacity=' . (!empty($capacity) ? implode(',', $capacity) : '-')
        . ' weight=' . (!empty($weight) ? implode(',', $weight) : '-')
        . ' pack=' . (!empty($pack) ? implode(',', $pack) : '-')
        . PHP_EOL;
}

if (!$dry_run) {
    if (function_exists('my_theme_flush_product_cache_fragments')) {
        my_theme_flush_product_cache_fragments(0);
    }
    update_option('my_theme_filter_cache_version', (string) time(), false);
}

echo 'repair_weber_done'
    . ' checked=' . (int) $stats['checked']
    . ' updated=' . (int) $stats['updated']
    . ' skipped=' . (int) $stats['skipped']
    . ' errors=' . (int) $stats['errors']
    . ' dry_run=' . ($dry_run ? 'yes' : 'no')
    . PHP_EOL;
