<?php
/**
 * Convert legacy Apollo paint sample products to Apollo silicone catalog.
 *
 * Run:
 *   Get-Content -Raw tools/fix_apollo_silicone_catalog.php | docker exec -i lephat1898-wordpress-1 php
 */

require '/var/www/html/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

$taxonomy_brand = '';
foreach (['pa_brand', 'product_brand', 'brand'] as $tax) {
    if (taxonomy_exists($tax)) {
        $taxonomy_brand = $tax;
        break;
    }
}

$taxonomy_line = '';
foreach (['pa_line', 'product_line', 'line'] as $tax) {
    if (taxonomy_exists($tax)) {
        $taxonomy_line = $tax;
        break;
    }
}

$ensure_term = static function ($term_name, $taxonomy, $slug = '') {
    if ($taxonomy === '' || !taxonomy_exists($taxonomy)) {
        return 0;
    }

    $slug = sanitize_title((string) $slug);
    if ($slug !== '') {
        $existing = get_term_by('slug', $slug, $taxonomy);
        if ($existing instanceof WP_Term) {
            return (int) $existing->term_id;
        }
    }

    $exists = term_exists($term_name, $taxonomy);
    if ($exists) {
        return is_array($exists) ? (int) $exists['term_id'] : (int) $exists;
    }

    $res = wp_insert_term($term_name, $taxonomy, [
        'slug' => ($slug !== '' ? $slug : sanitize_title((string) $term_name)),
    ]);

    if (is_wp_error($res)) {
        return 0;
    }

    return isset($res['term_id']) ? (int) $res['term_id'] : 0;
};

$apollo_items = [
    [
        'legacy_slug' => 'apollo-interior-putty-40kg',
        'slug' => 'apollo-acrylic-sealant-a100',
        'name' => 'Keo Acrylic Apollo Sealant A100',
        'line_slug' => 'a100',
        'line_label' => 'A100',
        'source_url' => 'https://apollosilicone.vn/san-pham/apollo-sealant-acrylic-a100',
        'packs' => [['300ml/chai', 32000], ['Thùng 25 chai', 780000]],
        'stock' => 56,
        'desc' => 'Keo acrylic Apollo A100 dùng cho khe nứt nhỏ, mối nối nội thất và bề mặt cần trám kín.',
    ],
    [
        'legacy_slug' => 'apollo-a800-exterior-5l',
        'slug' => 'apollo-silicone-sealant-a200',
        'name' => 'Keo Silicone Apollo Sealant A200',
        'line_slug' => 'a200',
        'line_label' => 'A200',
        'source_url' => 'https://apollosilicone.vn/san-pham/keo-silicon-apollo-silicone-sealant-a200',
        'packs' => [['300ml/chai', 36000], ['Thùng 25 chai', 870000]],
        'stock' => 52,
        'desc' => 'Keo silicone Apollo A200 trung tính, phù hợp nhôm kính và các vật liệu xây dựng thông dụng.',
    ],
    [
        'legacy_slug' => 'apollo-a300-primer-5l',
        'slug' => 'apollo-silicone-sealant-a300',
        'name' => 'Keo Silicone Apollo Sealant A300',
        'line_slug' => 'a300',
        'line_label' => 'A300',
        'source_url' => 'https://apollosilicone.vn/san-pham/keo-silicon-apollo-silicone-sealant-a300',
        'packs' => [['300ml/chai', 42000], ['Thùng 25 chai', 1020000]],
        'stock' => 49,
        'desc' => 'Keo silicone Apollo A300 đa dụng, bám dính tốt và co giãn ổn định cho thi công dân dụng.',
    ],
    [
        'legacy_slug' => 'apollo-a500-interior-5l',
        'slug' => 'apollo-silicone-sealant-a500',
        'name' => 'Keo Silicone Apollo Sealant A500',
        'line_slug' => 'a500',
        'line_label' => 'A500',
        'source_url' => 'https://apollosilicone.vn/san-pham/keo-silicon-apollo-silicone-sealant-a500',
        'packs' => [['300ml/chai', 56000], ['Thùng 25 chai', 1360000]],
        'stock' => 44,
        'desc' => 'Keo silicone Apollo A500 chuyên cho nhôm kính và mặt dựng, độ bền thời tiết cao.',
    ],
    [
        'legacy_slug' => 'apollo-a600-interior-easywash-5l',
        'slug' => 'apollo-silicone-sealant-a600',
        'name' => 'Keo Silicone Apollo Sealant A600',
        'line_slug' => 'a600',
        'line_label' => 'A600',
        'source_url' => 'https://apollosilicone.vn/san-pham/keo-silicon-apollo-silicone-sealant-a600',
        'packs' => [['300ml/chai', 65000], ['Thùng 25 chai', 1580000]],
        'stock' => 38,
        'desc' => 'Keo silicone Apollo A600 chịu thời tiết tốt, phù hợp hạng mục ngoài trời yêu cầu độ bền cao.',
    ],
    [
        'legacy_slug' => 'apollo-waterproof-flex-20kg',
        'slug' => 'apollo-silicone-sealant-sanitary-n',
        'name' => 'Keo Silicone Apollo Sealant Sanitary-N',
        'line_slug' => 'sanitary-n',
        'line_label' => 'Sanitary-N',
        'source_url' => 'https://apollosilicone.vn/san-pham/keo-silicon-apollo-silicone-sealant-sanitary-n',
        'packs' => [['300ml/chai', 72000], ['Thùng 25 chai', 1750000]],
        'stock' => 33,
        'desc' => 'Keo silicone Sanitary-N dùng cho khu vực ẩm ướt như bếp và phòng tắm, hạn chế nấm mốc.',
    ],
    [
        'legacy_slug' => 'apollo-metal-primer-0-8l',
        'slug' => 'apollo-silicone-weatherseal-a68',
        'name' => 'Keo Silicone Apollo Weatherseal A68',
        'line_slug' => 'weatherseal-a68',
        'line_label' => 'Weatherseal A68',
        'source_url' => 'https://apollosilicone.vn/san-pham/keo-silicon-apollo-silicone-sealant-weatherseal-a68',
        'packs' => [['310ml/chai', 95000], ['Thùng 25 chai', 2325000], ['500ml/sausage', 145000], ['Thùng 20 sausage', 2850000]],
        'stock' => 28,
        'desc' => 'Keo silicone Apollo Weatherseal A68 cho mặt dựng và khe liên kết, có thêm dạng sausage 500ml.',
    ],
    [
        'legacy_slug' => 'apollo-a1000-exterior-premium-5l',
        'slug' => 'apollo-silicone-weatherseal-a79',
        'name' => 'Keo Silicone Apollo Weatherseal A79',
        'line_slug' => 'weatherseal-a79',
        'line_label' => 'Weatherseal A79',
        'source_url' => 'https://apollosilicone.vn/san-pham/keo-silicon-thoi-tiet-cao-cap-apollo-silicone-sealant-weatherseal-a79',
        'packs' => [['300ml/chai', 108000], ['Thùng 25 chai', 2625000]],
        'stock' => 24,
        'desc' => 'Keo silicone Apollo Weatherseal A79 dòng cao cấp cho hạng mục yêu cầu độ bền và đàn hồi cao.',
    ],
    [
        'legacy_slug' => 'apollo-epoxy-floor-topcoat-20kg',
        'slug' => 'apollo-pu-foam',
        'name' => 'Apollo PU Foam',
        'line_slug' => 'pu-foam',
        'line_label' => 'PU Foam',
        'source_url' => 'https://apollosilicone.vn/san-pham/apollo-foam',
        'packs' => [['750ml/chai', 98000], ['Thùng 12 chai', 1140000]],
        'stock' => 31,
        'desc' => 'Apollo PU Foam trương nở lấp khe hở, tăng cách âm và cách nhiệt cho cửa và vách.',
    ],
    [
        'legacy_slug' => 'apollo-industrial-shield-18l',
        'slug' => 'apollo-pu-foam-b1',
        'name' => 'Apollo PU Foam B1',
        'line_slug' => 'pu-foam-b1',
        'line_label' => 'PU Foam B1',
        'source_url' => 'https://apollosilicone.vn/san-pham/gioi-thieu-ve-san-pham-chuyen-dung-apollo-foam-b1',
        'packs' => [['750ml/chai', 128000], ['Thùng 12 chai', 1500000]],
        'stock' => 19,
        'desc' => 'Apollo PU Foam B1 giảm bắt lửa, phù hợp vị trí cần tiêu chuẩn an toàn cháy cao hơn.',
    ],
];

$category_term = get_term_by('slug', 'keo-va-phu-gia', 'product_cat');
$category_id = ($category_term instanceof WP_Term) ? (int) $category_term->term_id : 0;
if ($category_id <= 0) {
    $insert_cat = wp_insert_term('Keo và phụ gia', 'product_cat', ['slug' => 'keo-va-phu-gia']);
    if (!is_wp_error($insert_cat) && !empty($insert_cat['term_id'])) {
        $category_id = (int) $insert_cat['term_id'];
    }
}

$brand_term_id = 0;
if ($taxonomy_brand !== '') {
    $brand_term_id = $ensure_term('Apollo', $taxonomy_brand, 'apollo');
}

$stats = ['updated' => 0, 'created' => 0, 'errors' => 0];
$touched_ids = [];

foreach ($apollo_items as $item) {
    $slug = sanitize_title((string) $item['slug']);
    $legacy_slug = sanitize_title((string) $item['legacy_slug']);
    if ($slug === '') {
        continue;
    }

    $seed_key_new = 'seed-catalog-' . $slug;
    $seed_key_old = 'seed-catalog-' . $legacy_slug;

    $product_id = 0;
    foreach ([$seed_key_new, $seed_key_old] as $seed_key) {
        if ($seed_key === '') {
            continue;
        }
        $by_meta = get_posts([
            'post_type' => 'product',
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_key' => '_seed_catalog_key',
            'meta_value' => $seed_key,
        ]);
        if (!empty($by_meta)) {
            $product_id = (int) $by_meta[0];
            break;
        }
    }

    if ($product_id <= 0) {
        foreach ([$slug, $legacy_slug] as $search_slug) {
            if ($search_slug === '') {
                continue;
            }
            $by_slug = get_posts([
                'post_type' => 'product',
                'post_status' => ['publish', 'draft', 'pending', 'private'],
                'posts_per_page' => 1,
                'fields' => 'ids',
                'name' => $search_slug,
            ]);
            if (!empty($by_slug)) {
                $product_id = (int) $by_slug[0];
                break;
            }
        }
    }

    if ($product_id > 0) {
        $product = wc_get_product($product_id);
        if (!$product instanceof WC_Product) {
            $stats['errors']++;
            continue;
        }
        $stats['updated']++;
    } else {
        $product = new WC_Product_Simple();
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        $product->set_manage_stock(true);
        $product_id = $product->save();
        if ($product_id <= 0) {
            $stats['errors']++;
            continue;
        }
        $stats['created']++;
    }

    $product->set_name((string) $item['name']);
    $product->set_slug($slug);
    $product->set_description((string) $item['desc']);
    $product->set_short_description('');
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_manage_stock(true);
    $qty = max(0, (int) $item['stock']);
    $product->set_stock_quantity($qty);
    $product->set_stock_status($qty > 0 ? 'instock' : 'outofstock');

    $map_parts = [];
    $capacity_labels = [];
    $weight_labels = [];
    $min_price = 0.0;

    foreach ((array) ($item['packs'] ?? []) as $pack) {
        if (!is_array($pack) || count($pack) < 2) {
            continue;
        }
        $label = trim((string) $pack[0]);
        $price = (float) $pack[1];
        if ($label === '' || $price <= 0) {
            continue;
        }

        $map_parts[] = $label . ':' . $price;
        if ($min_price <= 0 || $price < $min_price) {
            $min_price = $price;
        }

        if (stripos($label, 'kg') !== false) {
            $weight_labels[] = $label;
        } else {
            $capacity_labels[] = $label;
        }
    }

    if ($min_price > 0) {
        $product->set_regular_price((string) $min_price);
        $product->set_price((string) $min_price);
    }

    if (!empty($map_parts)) {
        $product->update_meta_data('_capacity_price_map', implode(' | ', $map_parts));
    } else {
        $product->delete_meta_data('_capacity_price_map');
    }

    if (!empty($capacity_labels)) {
        $product->update_meta_data('_display_capacity_list', implode(' | ', array_values(array_unique($capacity_labels))));
    } else {
        $product->delete_meta_data('_display_capacity_list');
    }

    if (!empty($weight_labels)) {
        $unique_weight = array_values(array_unique($weight_labels));
        $product->update_meta_data('_display_weight_list', implode(' | ', $unique_weight));
        if (preg_match('/([0-9]+(?:\.[0-9]+)?)/', (string) $unique_weight[0], $m) === 1) {
            $product->set_weight((string) ((float) $m[1]));
        }
    } else {
        $product->delete_meta_data('_display_weight_list');
        $product->set_weight('');
    }

    if ($category_id > 0) {
        wp_set_object_terms($product_id, [$category_id], 'product_cat', false);
    }

    if ($taxonomy_brand !== '' && $brand_term_id > 0) {
        wp_set_object_terms($product_id, [$brand_term_id], $taxonomy_brand, false);
    }

    if ($taxonomy_line !== '') {
        $line_slug = sanitize_title((string) $item['line_slug']);
        $line_label = trim((string) $item['line_label']);
        if ($line_slug !== '' && $line_label !== '') {
            $line_id = $ensure_term($line_label, $taxonomy_line, $line_slug);
            if ($line_id > 0) {
                wp_set_object_terms($product_id, [$line_id], $taxonomy_line, false);
            }
        }
    }

    $product->update_meta_data('_official_product_url', esc_url_raw((string) $item['source_url']));
    $product->update_meta_data('_seed_catalog_key', $seed_key_new);
    $product->update_meta_data('_seed_catalog_brand', 'apollo');
    $product->update_meta_data('_seed_catalog_source', 'tools/fix_apollo_silicone_catalog.php');
    $product->save();

    $touched_ids[] = (int) $product_id;
}

$touched_ids = array_values(array_unique(array_filter(array_map('intval', $touched_ids))));
sort($touched_ids, SORT_NUMERIC);

echo "[fix-apollo] done\n";
echo "[fix-apollo] updated={$stats['updated']} created={$stats['created']} errors={$stats['errors']}\n";
echo "[fix-apollo] touched_ids=" . implode(',', $touched_ids) . "\n";

