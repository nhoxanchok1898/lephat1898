<?php
/**
 * Repair live WordPress catalog integrity.
 *
 * What it does:
 * - Restore missing product posts that accidentally fell into trash.
 * - Merge pack/price metadata from older snapshots before republishing.
 * - Sync canonical pricing for obvious duplicate clusters.
 * - Demote duplicate published products to drafts so legacy redirects can take over.
 *
 * Run:
 *   Get-Content -Raw tools/repair_wordpress_catalog_integrity.php | docker compose -f docker-compose.wordpress.yml exec -T wordpress php
 * Optional:
 *   ... php -- --dry-run
 */

$stdout = fopen('php://stdout', 'wb');
$stderr = fopen('php://stderr', 'wb');

require '/var/www/html/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite($stderr, "WooCommerce is not loaded.\n");
    exit(1);
}

$argv = isset($_SERVER['argv']) && is_array($_SERVER['argv']) ? $_SERVER['argv'] : [];
$dry_run = in_array('--dry-run', $argv, true);

$find_product_id_by_slug = static function (string $slug, array $statuses = ['publish', 'draft', 'pending', 'private', 'trash']): int {
    $slug = sanitize_title($slug);
    if ($slug === '') {
        return 0;
    }

    $ids = get_posts([
        'post_type' => 'product',
        'post_status' => $statuses,
        'posts_per_page' => 1,
        'fields' => 'ids',
        'name' => $slug,
    ]);

    return !empty($ids) ? (int) $ids[0] : 0;
};

$sort_labels = static function (array $labels, string $unit): array {
    $labels = array_values(array_unique(array_filter(array_map('trim', $labels))));
    if (empty($labels)) {
        return [];
    }

    if (function_exists('my_theme_sort_pack_labels')) {
        return my_theme_sort_pack_labels($labels, $unit);
    }

    sort($labels);
    return array_values(array_unique($labels));
};

$split_meta_labels = static function (string $raw_value): array {
    $raw_value = trim($raw_value);
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

$parse_price_map = static function (string $raw_value): array {
    $raw_value = trim($raw_value);
    if ($raw_value === '') {
        return [];
    }

    $raw_value = str_replace([';', "\n"], '|', $raw_value);
    $parts = preg_split('/[|]/', $raw_value);
    $map = [];
    foreach ((array) $parts as $part) {
        $part = trim((string) $part);
        if ($part === '' || strpos($part, ':') === false) {
            continue;
        }

        [$label, $price_value] = array_map('trim', explode(':', $part, 2));
        $label = trim((string) $label);
        $price_value = (float) preg_replace('/[^\d.]/', '', (string) str_replace(',', '', $price_value));
        if ($label === '' || $price_value <= 0) {
            continue;
        }

        $map[$label] = $price_value;
    }

    if (function_exists('my_theme_compare_pack_labels')) {
        uksort($map, 'my_theme_compare_pack_labels');
    }

    return $map;
};

$classify_label = static function (string $label): string {
    $label = trim($label);
    if ($label === '') {
        return 'pack';
    }

    if (function_exists('my_theme_parse_pack_label')) {
        $parsed = my_theme_parse_pack_label($label);
        if (is_array($parsed)) {
            $unit = (string) ($parsed['unit'] ?? '');
            if (in_array($unit, ['L', 'ml'], true)) {
                return 'capacity';
            }
            if ($unit === 'kg') {
                return 'weight';
            }
        }
    }

    $normalized = strtolower($label);
    if (preg_match('/\b\d+(?:[.,]\d+)?\s*l\b/i', $normalized) || preg_match('/\b\d+(?:[.,]\d+)?ml\b/i', $normalized)) {
        return 'capacity';
    }
    if (preg_match('/\b\d+(?:[.,]\d+)?\s*kg\b/i', $normalized) || preg_match('/\b\d+(?:[.,]\d+)?g\b/i', $normalized)) {
        return 'weight';
    }

    return 'pack';
};

$build_label_groups = static function (array $labels) use ($classify_label, $sort_labels): array {
    $capacity = [];
    $weight = [];
    $pack = [];

    foreach ($labels as $label) {
        $label = trim((string) $label);
        if ($label === '') {
            continue;
        }

        $type = $classify_label($label);
        if ($type === 'capacity') {
            $capacity[] = $label;
        } elseif ($type === 'weight') {
            $weight[] = $label;
        } else {
            $pack[] = $label;
        }
    }

    if (function_exists('my_theme_compare_pack_labels')) {
        usort($pack, 'my_theme_compare_pack_labels');
    } else {
        sort($pack);
    }

    return [
        'capacity' => $sort_labels($capacity, 'L'),
        'weight' => $sort_labels($weight, 'kg'),
        'pack' => array_values(array_unique($pack)),
    ];
};

$collect_restore_payload = static function (array $merge_ids) use ($split_meta_labels, $parse_price_map): array {
    $labels = [];
    $price_map = [];
    $thumb_id = 0;
    $category_ids = [];

    foreach ($merge_ids as $merge_id) {
        $merge_id = (int) $merge_id;
        if ($merge_id <= 0) {
            continue;
        }

        $post = get_post($merge_id);
        if (!$post instanceof WP_Post) {
            continue;
        }

        if ($thumb_id <= 0) {
            $thumb_id = (int) get_post_thumbnail_id($merge_id);
        }

        $terms = wp_get_post_terms($merge_id, 'product_cat', ['fields' => 'ids']);
        if (is_array($terms)) {
            foreach ($terms as $term_id) {
                $term_id = (int) $term_id;
                if ($term_id > 0) {
                    $category_ids[$term_id] = $term_id;
                }
            }
        }

        $labels = array_merge(
            $labels,
            $split_meta_labels((string) get_post_meta($merge_id, '_display_capacity_list', true)),
            $split_meta_labels((string) get_post_meta($merge_id, '_display_weight_list', true)),
            $split_meta_labels((string) get_post_meta($merge_id, '_display_pack_list', true))
        );

        $row_map = $parse_price_map((string) get_post_meta($merge_id, '_capacity_price_map', true));
        foreach ($row_map as $label => $price_value) {
            $price_map[$label] = (float) $price_value;
            $labels[] = $label;
        }
    }

    $labels = array_values(array_unique(array_filter(array_map('trim', $labels))));
    if (function_exists('my_theme_compare_pack_labels')) {
        usort($labels, 'my_theme_compare_pack_labels');
        uksort($price_map, 'my_theme_compare_pack_labels');
    }

    return [
        'labels' => $labels,
        'price_map' => $price_map,
        'thumb_id' => $thumb_id,
        'category_ids' => array_values($category_ids),
    ];
};

$restore_targets = [
    'apollo-pu-foam' => [
        'primary_id' => 945,
        'merge_ids' => [945],
        'title' => 'Apollo PU Foam',
        'source_url' => 'https://apollosilicone.vn/san-pham/apollo-foam',
    ],
    'apollo-pu-foam-b1' => [
        'primary_id' => 947,
        'merge_ids' => [947],
        'title' => 'Apollo PU Foam B1',
        'source_url' => 'https://apollosilicone.vn/san-pham/gioi-thieu-ve-san-pham-chuyen-dung-apollo-foam-b1',
    ],
    'sika-primer-3n-1l' => [
        'primary_id' => 820,
        'merge_ids' => [820],
        'title' => 'Sơn lót Sika Primer 3N',
        'source_url' => 'https://vnm.sika.com/vi/kenh-phan-ph-i-banl/tram-khe-k-t-dinh/sika-primer-3-n.html',
    ],
    'sonlotcaocapdulux' => [
        'primary_id' => 617,
        'merge_ids' => [428, 617],
        'title' => 'Sơn lót cao cấp Dulux',
        'source_url' => 'https://www.dulux.vn/vi/san-pham/sơn-lót-cao-cấp-dulux',
    ],
    'sonlotngoaithatsieucaocapduluxweathershieldpowersealer' => [
        'primary_id' => 533,
        'merge_ids' => [344, 533],
        'title' => 'Sơn Lót Ngoại Thất Siêu Cao Cấp Dulux Weathershield Powersealer',
        'source_url' => 'https://www.dulux.vn/vi/san-pham/sơn-lót-ngoại-thất-siêu-cao-cấp-dulux-weathershield-powersealer',
    ],
    'duluxweathershieldchatchongtham' => [
        'primary_id' => 539,
        'merge_ids' => [539],
        'title' => 'Dulux Weathershield Chất Chống Thấm',
        'source_url' => 'https://www.dulux.vn/vi/san-pham/dulux-weathershield-chất-chống-thấm',
        'category_slugs' => ['son-ngoai-that', 'chong-tham'],
    ],
];

$canonical_price_clusters = [
    'keo-cha-ron-webercolor-classic' => [
        'keo-cha-ron-webercolor-classic',
        'webercolor-classic',
        'webercolor-classic-2023',
        'webercolor-classic-ps',
    ],
];

$duplicate_targets = [
    'webercolor-classic' => 'keo-cha-ron-webercolor-classic',
    'webercolor-classic-2023' => 'keo-cha-ron-webercolor-classic',
    'webercolor-classic-ps' => 'keo-cha-ron-webercolor-classic',
    'webertai-gres' => 'keo-dan-gach-webertai-gres-40kg',
    'webertai-vis' => 'keo-dan-gach-webertai-vis-40kg',
];

$stats = [
    'restored' => 0,
    'restore_skipped' => 0,
    'restore_errors' => 0,
    'canonical_synced' => 0,
    'canonical_skipped' => 0,
    'duplicates_demoted' => 0,
    'duplicates_skipped' => 0,
    'duplicate_errors' => 0,
];

foreach ($restore_targets as $target_slug => $config) {
    $primary_id = isset($config['primary_id']) ? (int) $config['primary_id'] : 0;
    if ($primary_id <= 0) {
        $stats['restore_errors']++;
        fwrite($stderr, "restore missing primary for {$target_slug}\n");
        continue;
    }

    $payload = $collect_restore_payload((array) ($config['merge_ids'] ?? [$primary_id]));
    $label_groups = $build_label_groups((array) $payload['labels']);
    $source_url = esc_url_raw((string) ($config['source_url'] ?? ''));
    $title = trim((string) ($config['title'] ?? ''));
    $category_ids = (array) ($payload['category_ids'] ?? []);
    foreach ((array) ($config['category_slugs'] ?? []) as $category_slug) {
        $category_slug = sanitize_title((string) $category_slug);
        if ($category_slug === '') {
            continue;
        }

        $term = get_term_by('slug', $category_slug, 'product_cat');
        if ($term instanceof WP_Term) {
            $category_ids[] = (int) $term->term_id;
        }
    }
    $category_ids = array_values(array_unique(array_filter(array_map('intval', $category_ids))));

    if ($dry_run) {
        fwrite(
            $stdout,
            'restore_product slug=' . $target_slug
            . ' primary=' . $primary_id
            . ' labels=' . implode(',', (array) $payload['labels'])
            . ' price_map=' . wp_json_encode($payload['price_map'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . PHP_EOL
        );
        $stats['restored']++;
        continue;
    }

    $primary_post = get_post($primary_id);
    if (!$primary_post instanceof WP_Post) {
        $stats['restore_errors']++;
        fwrite($stderr, "restore missing post {$primary_id}\n");
        continue;
    }

    if ($primary_post->post_status === 'trash') {
        wp_untrash_post($primary_id);
    }

    $update_args = [
        'ID' => $primary_id,
        'post_status' => 'publish',
        'post_name' => $target_slug,
    ];
    if ($title !== '') {
        $update_args['post_title'] = $title;
    }

    $update_result = wp_update_post($update_args, true);
    if (is_wp_error($update_result)) {
        $stats['restore_errors']++;
        fwrite($stderr, 'restore failed slug=' . $target_slug . ' error=' . $update_result->get_error_message() . PHP_EOL);
        continue;
    }

    $product = wc_get_product($primary_id);
    if (!$product instanceof WC_Product) {
        $stats['restore_errors']++;
        fwrite($stderr, "restore missing product {$primary_id}\n");
        continue;
    }

    if ($source_url !== '') {
        update_post_meta($primary_id, '_official_source_page', $source_url);
        update_post_meta($primary_id, '_official_source_url', $source_url);
        update_post_meta($primary_id, '_source_url', $source_url);
    }

    if (!empty($category_ids)) {
        wp_set_post_terms($primary_id, $category_ids, 'product_cat', false);
    }

    if ((int) $payload['thumb_id'] > 0) {
        set_post_thumbnail($primary_id, (int) $payload['thumb_id']);
    }

    if (!empty($label_groups['capacity'])) {
        $product->update_meta_data('_display_capacity_list', implode(' | ', $label_groups['capacity']));
    } else {
        $product->delete_meta_data('_display_capacity_list');
    }

    if (!empty($label_groups['weight'])) {
        $product->update_meta_data('_display_weight_list', implode(' | ', $label_groups['weight']));
        if (function_exists('my_theme_parse_pack_label')) {
            $first_weight = my_theme_parse_pack_label((string) $label_groups['weight'][0]);
            if (is_array($first_weight) && ($first_weight['unit'] ?? '') === 'kg') {
                $product->set_weight((string) $first_weight['value']);
            }
        }
    } else {
        $product->delete_meta_data('_display_weight_list');
    }

    if (!empty($label_groups['pack'])) {
        $product->update_meta_data('_display_pack_list', implode(' | ', $label_groups['pack']));
    } else {
        $product->delete_meta_data('_display_pack_list');
    }

    if (!empty($payload['price_map'])) {
        $map_parts = [];
        foreach ($payload['price_map'] as $label => $price_value) {
            $map_parts[] = trim((string) $label) . ':' . (float) $price_value;
        }
        $product->update_meta_data('_capacity_price_map', implode(' | ', $map_parts));
        $base_price = min(array_map('floatval', array_values($payload['price_map'])));
        if ($base_price > 0) {
            $product->set_regular_price((string) $base_price);
            $product->set_price((string) $base_price);
            $product->set_sale_price('');
        }
    } else {
        $product->delete_meta_data('_capacity_price_map');
        $product->set_regular_price('');
        $product->set_price('');
        $product->set_sale_price('');
    }

    $product->save();
    wc_delete_product_transients($primary_id);
    clean_post_cache($primary_id);

    $stats['restored']++;
    fwrite($stdout, 'restore_product slug=' . $target_slug . ' post_id=' . $primary_id . PHP_EOL);
}

foreach ($canonical_price_clusters as $canonical_slug => $cluster_slugs) {
    $canonical_id = $find_product_id_by_slug($canonical_slug, ['publish', 'draft', 'pending', 'private']);
    if ($canonical_id <= 0) {
        $stats['canonical_skipped']++;
        continue;
    }

    $cluster_price_map = [];
    $cluster_labels = [];
    foreach ($cluster_slugs as $cluster_slug) {
        $cluster_id = $find_product_id_by_slug($cluster_slug, ['publish', 'draft', 'pending', 'private']);
        if ($cluster_id <= 0) {
            continue;
        }

        $product = wc_get_product($cluster_id);
        if (!$product instanceof WC_Product) {
            continue;
        }

        $labels = array_merge(
            $split_meta_labels((string) get_post_meta($cluster_id, '_display_capacity_list', true)),
            $split_meta_labels((string) get_post_meta($cluster_id, '_display_weight_list', true)),
            $split_meta_labels((string) get_post_meta($cluster_id, '_display_pack_list', true))
        );
        foreach ($labels as $label) {
            $cluster_labels[$label] = $label;
        }

        $row_map = $parse_price_map((string) get_post_meta($cluster_id, '_capacity_price_map', true));
        foreach ($row_map as $label => $price_value) {
            $cluster_price_map[$label][] = (float) $price_value;
        }

        $current_price = (float) $product->get_price('edit');
        if ($current_price > 0 && count($labels) === 1) {
            $cluster_price_map[$labels[0]][] = $current_price;
        }
    }

    $resolved_map = [];
    foreach ($cluster_price_map as $label => $prices) {
        $prices = array_values(array_filter(array_map('floatval', (array) $prices), static function (float $price): bool {
            return $price > 0;
        }));
        if (!empty($prices)) {
            $resolved_map[$label] = min($prices);
        }
    }

    if (empty($resolved_map)) {
        $stats['canonical_skipped']++;
        continue;
    }

    if ($dry_run) {
        fwrite(
            $stdout,
            'sync_canonical_price slug=' . $canonical_slug
            . ' map=' . wp_json_encode($resolved_map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . PHP_EOL
        );
        $stats['canonical_synced']++;
        continue;
    }

    $canonical_product = wc_get_product($canonical_id);
    if (!$canonical_product instanceof WC_Product) {
        $stats['canonical_skipped']++;
        continue;
    }

    if (function_exists('my_theme_compare_pack_labels')) {
        uksort($resolved_map, 'my_theme_compare_pack_labels');
    }
    $map_parts = [];
    foreach ($resolved_map as $label => $price_value) {
        $map_parts[] = $label . ':' . $price_value;
    }

    $label_groups = $build_label_groups(array_keys($resolved_map));
    if (!empty($label_groups['capacity'])) {
        $canonical_product->update_meta_data('_display_capacity_list', implode(' | ', $label_groups['capacity']));
    }
    if (!empty($label_groups['weight'])) {
        $canonical_product->update_meta_data('_display_weight_list', implode(' | ', $label_groups['weight']));
    }
    if (!empty($label_groups['pack'])) {
        $canonical_product->update_meta_data('_display_pack_list', implode(' | ', $label_groups['pack']));
    }
    $canonical_product->update_meta_data('_capacity_price_map', implode(' | ', $map_parts));
    $canonical_product->set_regular_price((string) min(array_map('floatval', array_values($resolved_map))));
    $canonical_product->set_price((string) min(array_map('floatval', array_values($resolved_map))));
    $canonical_product->set_sale_price('');
    $canonical_product->save();
    wc_delete_product_transients($canonical_id);
    clean_post_cache($canonical_id);

    $stats['canonical_synced']++;
    fwrite($stdout, 'sync_canonical_price slug=' . $canonical_slug . ' post_id=' . $canonical_id . PHP_EOL);
}

foreach ($duplicate_targets as $duplicate_slug => $canonical_slug) {
    $duplicate_id = $find_product_id_by_slug($duplicate_slug, ['publish', 'draft', 'pending', 'private']);
    if ($duplicate_id <= 0) {
        $stats['duplicates_skipped']++;
        continue;
    }

    $current_status = get_post_status($duplicate_id);
    if ($current_status !== 'publish') {
        $stats['duplicates_skipped']++;
        continue;
    }

    if ($dry_run) {
        fwrite($stdout, 'demote_duplicate slug=' . $duplicate_slug . ' canonical=' . $canonical_slug . PHP_EOL);
        $stats['duplicates_demoted']++;
        continue;
    }

    update_post_meta($duplicate_id, '_my_theme_duplicate_of', $canonical_slug);
    $result = wp_update_post([
        'ID' => $duplicate_id,
        'post_status' => 'draft',
    ], true);

    if (is_wp_error($result)) {
        $stats['duplicate_errors']++;
        fwrite($stderr, 'demote failed slug=' . $duplicate_slug . ' error=' . $result->get_error_message() . PHP_EOL);
        continue;
    }

    clean_post_cache($duplicate_id);
    $stats['duplicates_demoted']++;
    fwrite($stdout, 'demote_duplicate slug=' . $duplicate_slug . ' canonical=' . $canonical_slug . ' post_id=' . $duplicate_id . PHP_EOL);
}

if (!$dry_run) {
    if (function_exists('my_theme_flush_product_cache_fragments')) {
        my_theme_flush_product_cache_fragments(0);
    }
    update_option('my_theme_filter_cache_version', (string) time(), false);
}

fwrite($stdout, wp_json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);
