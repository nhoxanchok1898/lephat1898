<?php
/**
 * Backfill missing product prices from official sources when the match is unambiguous.
 *
 * Strategy:
 * - Dulux/Maxilite: extract pack-price map from curated local snapshot first, then source URL.
 *   Apply only when every extracted pack label matches an existing product pack label.
 * - Weber: extract a single official offer price from JSON-LD and apply only when the product
 *   has exactly one declared pack label.
 *
 * Run:
 *   Get-Content -Raw tools/backfill_missing_prices_from_source.php | docker compose -f docker-compose.wordpress.yml exec -T wordpress php
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

$theme_data_path = get_theme_file_path('data/dulux_official.json');
$dulux_rows = [];
if ($theme_data_path && file_exists($theme_data_path)) {
    $decoded = json_decode((string) file_get_contents($theme_data_path), true);
    if (is_array($decoded)) {
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }
            $slug = sanitize_title((string) ($row['slug'] ?? ''));
            if ($slug !== '') {
                $dulux_rows[$slug] = $row;
            }
        }
    }
}

$split_meta_labels = static function ($raw_value): array {
    $raw_value = trim((string) $raw_value);
    if ($raw_value === '') {
        return [];
    }

    $raw_value = str_replace([';', "\n"], '|', $raw_value);
    $parts = preg_split('/[|]/', $raw_value);
    $labels = [];
    foreach ((array) $parts as $part) {
        $part = trim((string) $part);
        if ($part === '') {
            continue;
        }
        if (strpos($part, ':') !== false) {
            [$part] = array_map('trim', explode(':', $part, 2));
        }
        if ($part !== '') {
            $labels[] = $part;
        }
    }

    $labels = array_values(array_unique($labels));
    if (function_exists('my_theme_compare_pack_labels')) {
        usort($labels, 'my_theme_compare_pack_labels');
    }

    return $labels;
};

$get_declared_pack_labels = static function (WC_Product $product) use ($split_meta_labels): array {
    $labels = [];
    if (function_exists('my_theme_get_product_pack_groups')) {
        $groups = my_theme_get_product_pack_groups($product);
        $labels = array_merge(
            (array) ($groups['capacity'] ?? []),
            (array) ($groups['weight'] ?? []),
            (array) ($groups['package'] ?? [])
        );
    }

    if (empty($labels)) {
        $labels = array_merge(
            $split_meta_labels($product->get_meta('_display_capacity_list')),
            $split_meta_labels($product->get_meta('_display_weight_list')),
            $split_meta_labels($product->get_meta('_display_pack_list'))
        );
    }

    $labels = array_values(array_unique(array_filter(array_map('trim', $labels))));
    if (function_exists('my_theme_compare_pack_labels')) {
        usort($labels, 'my_theme_compare_pack_labels');
    }

    return $labels;
};

$set_pack_labels = static function (WC_Product $product, array $labels): void {
    $capacity = [];
    $weight = [];
    $package = [];

    foreach ($labels as $label) {
        $label = trim((string) $label);
        if ($label === '') {
            continue;
        }

        $parsed = function_exists('my_theme_parse_pack_label') ? my_theme_parse_pack_label($label) : false;
        if (is_array($parsed)) {
            if (in_array((string) $parsed['unit'], ['L', 'ml'], true)) {
                $capacity[] = $parsed['label'];
                continue;
            }
            if ((string) $parsed['unit'] === 'kg') {
                $weight[] = $parsed['label'];
                continue;
            }
        }

        $package[] = $label;
    }

    $capacity = function_exists('my_theme_sort_pack_labels') ? my_theme_sort_pack_labels($capacity, 'L') : array_values(array_unique($capacity));
    $weight = function_exists('my_theme_sort_pack_labels') ? my_theme_sort_pack_labels($weight, 'kg') : array_values(array_unique($weight));
    if (function_exists('my_theme_compare_pack_labels')) {
        usort($package, 'my_theme_compare_pack_labels');
    } else {
        $package = array_values(array_unique($package));
    }

    if (!empty($capacity)) {
        $product->update_meta_data('_display_capacity_list', implode(' | ', $capacity));
    } else {
        $product->delete_meta_data('_display_capacity_list');
    }

    if (!empty($weight)) {
        $product->update_meta_data('_display_weight_list', implode(' | ', $weight));
        $first_weight = function_exists('my_theme_parse_pack_label') ? my_theme_parse_pack_label((string) $weight[0]) : false;
        if (is_array($first_weight) && ($first_weight['unit'] ?? '') === 'kg') {
            $product->set_weight((string) $first_weight['value']);
        }
    } else {
        $product->delete_meta_data('_display_weight_list');
    }

    if (!empty($package)) {
        $product->update_meta_data('_display_pack_list', implode(' | ', $package));
    } else {
        $product->delete_meta_data('_display_pack_list');
    }
};

$apply_price_map = static function (WC_Product $product, array $price_map) use ($set_pack_labels): void {
    if (empty($price_map)) {
        return;
    }

    if (function_exists('my_theme_compare_pack_labels')) {
        uksort($price_map, 'my_theme_compare_pack_labels');
    }

    $map_parts = [];
    foreach ($price_map as $label => $price_value) {
        $price_value = (float) $price_value;
        if ($price_value <= 0) {
            continue;
        }
        $map_parts[] = trim((string) $label) . ':' . $price_value;
    }

    if (empty($map_parts)) {
        return;
    }

    $product->update_meta_data('_capacity_price_map', implode(' | ', $map_parts));
    $set_pack_labels($product, array_keys($price_map));

    $min_price = min(array_map('floatval', array_values($price_map)));
    if ($min_price > 0) {
        $product->set_regular_price((string) $min_price);
        $product->set_price((string) $min_price);
        $product->set_sale_price('');
    }
};

$stats = [
    'checked' => 0,
    'updated' => 0,
    'skipped' => 0,
    'errors' => 0,
    'dulux_map' => 0,
    'weber_offer' => 0,
];

$product_ids = function_exists('my_theme_get_catalog_visible_product_ids')
    ? my_theme_get_catalog_visible_product_ids(false)
    : [];

foreach ((array) $product_ids as $product_id) {
    $product = wc_get_product($product_id);
    if (!$product instanceof WC_Product) {
        continue;
    }

    $stats['checked']++;
    $current_price = (float) $product->get_price();
    if ($current_price > 0) {
        $stats['skipped']++;
        continue;
    }

    $brand = function_exists('my_theme_get_product_brand_slug') ? sanitize_title((string) my_theme_get_product_brand_slug($product)) : '';
    if (!in_array($brand, ['dulux', 'maxilite', 'weber'], true)) {
        $stats['skipped']++;
        continue;
    }

    $slug = sanitize_title((string) $product->get_slug());
    $source_url = trim((string) get_post_meta($product_id, '_official_source_url', true));
    if ($source_url === '') {
        $source_url = trim((string) get_post_meta($product_id, '_official_source_page', true));
    }

    $declared_labels = $get_declared_pack_labels($product);
    $candidate_map = [];
    $source_kind = '';

    if (in_array($brand, ['dulux', 'maxilite'], true)) {
        $row = $dulux_rows[$slug] ?? null;
        if (is_array($row) && !empty($row['description']) && function_exists('my_theme_extract_pack_price_map_from_text')) {
            $candidate_map = my_theme_extract_pack_price_map_from_text(
                (string) $row['description'],
                function_exists('my_theme_is_putty_product') ? my_theme_is_putty_product($product) : false
            );
            if (!empty($candidate_map)) {
                $source_kind = 'dulux_snapshot';
            }
        }

        if (empty($candidate_map) && $source_url !== '' && function_exists('my_theme_fetch_pack_price_map_from_source_url')) {
            $candidate_map = my_theme_fetch_pack_price_map_from_source_url(
                $source_url,
                function_exists('my_theme_is_putty_product') ? my_theme_is_putty_product($product) : false,
                true
            );
            if (!empty($candidate_map)) {
                $source_kind = 'official_html';
            }
        }

        if (!empty($candidate_map) && !empty($declared_labels)) {
            $filtered_map = [];
            foreach ($candidate_map as $label => $price_value) {
                if (in_array($label, $declared_labels, true)) {
                    $filtered_map[$label] = $price_value;
                }
            }

            if (count($filtered_map) !== count($candidate_map)) {
                $candidate_map = [];
            } else {
                $candidate_map = $filtered_map;
            }
        }
    } elseif ($brand === 'weber' && $source_url !== '' && function_exists('my_theme_extract_offer_price_list_from_source_html')) {
        $response = wp_remote_get($source_url, [
            'timeout' => 20,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; PaintStoreBot/1.0)',
            ],
        ]);

        if (!is_wp_error($response)) {
            $body = (string) wp_remote_retrieve_body($response);
            $offer_prices = my_theme_extract_offer_price_list_from_source_html($body);
            if (count($offer_prices) === 1 && count($declared_labels) === 1) {
                $candidate_map = [
                    $declared_labels[0] => (float) $offer_prices[0],
                ];
                $source_kind = 'weber_offer';
            }
        }
    }

    if (empty($candidate_map)) {
        $stats['skipped']++;
        continue;
    }

    if (!$dry_run) {
        $apply_price_map($product, $candidate_map);
        $product->save();
        wc_delete_product_transients($product_id);
    }

    if ($source_kind === 'weber_offer') {
        $stats['weber_offer']++;
    } else {
        $stats['dulux_map']++;
    }
    $stats['updated']++;

    echo 'backfill_price'
        . ' slug=' . $slug
        . ' brand=' . $brand
        . ' source=' . $source_kind
        . ' map=' . wp_json_encode($candidate_map, JSON_UNESCAPED_UNICODE)
        . PHP_EOL;
}

if (!$dry_run && function_exists('my_theme_flush_product_cache_fragments')) {
    my_theme_flush_product_cache_fragments(0);
}

echo 'backfill_missing_prices'
    . ' checked=' . (int) $stats['checked']
    . ' updated=' . (int) $stats['updated']
    . ' skipped=' . (int) $stats['skipped']
    . ' errors=' . (int) $stats['errors']
    . ' dulux_map=' . (int) $stats['dulux_map']
    . ' weber_offer=' . (int) $stats['weber_offer']
    . ' dry_run=' . ($dry_run ? 'yes' : 'no')
    . PHP_EOL;
