<?php
/**
 * Enrich live WooCommerce catalog data using existing local datasets and official source pages.
 *
 * Run:
 *   Get-Content -Raw tools/enrich_existing_catalog.php | docker compose -f docker-compose.wordpress.yml exec -T wordpress php
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

$theme_root = (string) get_theme_file_path();
$dulux_file = wp_normalize_path(trailingslashit($theme_root) . 'data/dulux_official.json');
if (!file_exists($dulux_file)) {
    $dulux_file = '/var/www/html/wp-content/themes/my-theme/data/dulux_official.json';
}

$dulux_rows = [];
if (file_exists($dulux_file)) {
    $decoded = json_decode((string) file_get_contents($dulux_file), true);
    $items = is_array($decoded) && isset($decoded['items']) && is_array($decoded['items']) ? $decoded['items'] : [];
    foreach ($items as $row) {
        if (!is_array($row)) {
            continue;
        }
        $slug = sanitize_title((string) ($row['slug'] ?? ''));
        if ($slug === '') {
            continue;
        }
        $dulux_rows[$slug] = $row;
    }
}

$normalize_text = static function ($text) {
    $text = html_entity_decode((string) $text, ENT_QUOTES, 'UTF-8');
    $text = wp_strip_all_tags($text);
    $text = preg_replace('/\s+/u', ' ', $text);
    return trim((string) $text);
};

$build_short_text = static function ($text, $fallback = '') use ($normalize_text) {
    $text = $normalize_text($text);
    if ($text === '') {
        $text = $normalize_text($fallback);
    }
    if ($text === '') {
        return '';
    }

    $sentences = preg_split('/(?<=[\.\!\?])\s+/u', $text);
    $summary = '';
    foreach ((array) $sentences as $sentence) {
        $sentence = trim((string) $sentence);
        if ($sentence === '') {
            continue;
        }
        $summary = $summary === '' ? $sentence : ($summary . ' ' . $sentence);
        if ((function_exists('mb_strlen') ? mb_strlen($summary) : strlen($summary)) >= 125) {
            break;
        }
    }

    if ($summary === '') {
        $summary = $text;
    }

    return wp_trim_words($summary, 26, '...');
};

$build_category_note = static function ($category_slug, $product_name) {
    $name = trim((string) $product_name);
    switch ($category_slug) {
        case 'son-noi-that':
            return $name . ' phù hợp hoàn thiện tường và trần nội thất, ưu tiên bề mặt đều màu và dễ vệ sinh.';
        case 'son-ngoai-that':
            return $name . ' phù hợp cho bề mặt ngoài trời, ưu tiên độ bền màu và khả năng chống bám bẩn.';
        case 'son-lot':
            return $name . ' dùng ở bước sơn lót để tăng độ bám dính và ổn định bề mặt trước khi phủ hoàn thiện.';
        case 'chong-tham':
            return $name . ' phù hợp cho khu vực cần chống thấm, bảo vệ bề mặt trước ẩm nước và thời tiết.';
        case 'bot-tret':
            return $name . ' dùng để làm phẳng bề mặt tường trước khi sơn, giúp lớp phủ hoàn thiện đều và bám tốt hơn.';
        case 'keo-va-phu-gia':
            return $name . ' phù hợp cho nhu cầu trám khe, kết dính hoặc tăng cường phụ gia theo từng hạng mục thi công.';
        case 'son-kim-loai':
            return $name . ' phù hợp cho bề mặt kim loại, hỗ trợ chống gỉ và tăng độ bền cho lớp phủ.';
        case 'son-cong-nghiep':
            return $name . ' phù hợp cho hạng mục công nghiệp cần độ bền cơ học và ổn định bề mặt cao hơn.';
        case 'son-epoxy':
            return $name . ' phù hợp cho sàn và bề mặt epoxy cần độ bền cơ học, chống mài mòn và dễ vệ sinh.';
        default:
            return $name . ' là sản phẩm vật liệu hoàn thiện phù hợp cho nhu cầu thi công và bảo vệ bề mặt theo đúng hạng mục.';
    }
};

$fetch_source_summary = static function ($url) use ($normalize_text) {
    static $cache = [];

    $url = trim((string) $url);
    if ($url === '' || !wp_http_validate_url($url)) {
        return '';
    }
    if (isset($cache[$url])) {
        return $cache[$url];
    }

    $response = wp_remote_get($url, [
        'timeout' => 20,
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (compatible; CatalogEnricher/1.0)',
        ],
    ]);
    if (is_wp_error($response)) {
        $cache[$url] = '';
        return '';
    }

    $body = (string) wp_remote_retrieve_body($response);
    if ($body === '') {
        $cache[$url] = '';
        return '';
    }

    $candidates = [];
    $patterns = [
        '/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\']/iu',
        '/<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']+)["\']/iu',
        '/"description"\s*:\s*"([^"]+)"/iu',
        '/<p[^>]*class=["\'][^"\']*thumbnail-excerpt[^"\']*["\'][^>]*>(.*?)<\/p>/isu',
        '/<p[^>]*>(.*?)<\/p>/isu',
    ];

    foreach ($patterns as $pattern) {
        if (!preg_match_all($pattern, $body, $matches) || empty($matches[1])) {
            continue;
        }
        foreach ((array) $matches[1] as $match) {
            $text = $normalize_text($match);
            if ($text === '') {
                continue;
            }
            if ((function_exists('mb_strlen') ? mb_strlen($text) : strlen($text)) < 48) {
                continue;
            }
            $candidates[] = $text;
        }
        if (!empty($candidates)) {
            break;
        }
    }

    if (empty($candidates)) {
        $cache[$url] = '';
        return '';
    }

    $best = '';
    foreach ($candidates as $candidate) {
        if ((function_exists('mb_strlen') ? mb_strlen($candidate) : strlen($candidate)) > (function_exists('mb_strlen') ? mb_strlen($best) : strlen($best))) {
            $best = $candidate;
        }
    }

    $cache[$url] = trim((string) $best);
    return $cache[$url];
};

$set_pack_labels = static function (WC_Product $product, array $labels) {
    $labels = array_values(array_filter(array_map('trim', $labels), static function ($label) {
        return $label !== '';
    }));
    $labels = array_values(array_unique($labels));
    if (empty($labels)) {
        $product->delete_meta_data('_display_capacity_list');
        $product->delete_meta_data('_display_weight_list');
        return false;
    }

    $capacity_labels = function_exists('my_theme_sort_pack_labels') ? my_theme_sort_pack_labels($labels, 'L') : [];
    $weight_labels = function_exists('my_theme_sort_pack_labels') ? my_theme_sort_pack_labels($labels, 'kg') : [];

    if (!empty($capacity_labels)) {
        $product->update_meta_data('_display_capacity_list', implode(' | ', $capacity_labels));
    } else {
        $product->delete_meta_data('_display_capacity_list');
    }

    if (!empty($weight_labels)) {
        $product->update_meta_data('_display_weight_list', implode(' | ', $weight_labels));
        if (function_exists('my_theme_parse_pack_label')) {
            $first_weight = my_theme_parse_pack_label($weight_labels[0]);
            if ($first_weight && isset($first_weight['unit']) && $first_weight['unit'] === 'kg') {
                $product->set_weight((string) $first_weight['value']);
            }
        }
    } else {
        $product->delete_meta_data('_display_weight_list');
    }

    return true;
};

$stats = [
    'checked' => 0,
    'updated' => 0,
    'desc_updated' => 0,
    'short_updated' => 0,
    'price_zero_cleared' => 0,
    'price_map_updated' => 0,
    'pack_labels_updated' => 0,
    'source_url_filled' => 0,
];

$product_ids = get_posts([
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'orderby' => 'ID',
    'order' => 'ASC',
]);

foreach ((array) $product_ids as $product_id) {
    $product = wc_get_product((int) $product_id);
    if (!$product instanceof WC_Product) {
        continue;
    }

    $stats['checked']++;
    $dirty = false;
    $slug = sanitize_title((string) $product->get_slug());
    $name = trim((string) $product->get_name());
    $brand_slug = function_exists('my_theme_get_product_brand_slug')
        ? sanitize_title((string) my_theme_get_product_brand_slug($product))
        : '';
    $line_label = function_exists('my_theme_get_product_line_label')
        ? trim((string) my_theme_get_product_line_label($product))
        : '';
    $category_term = function_exists('my_theme_get_product_primary_category_term')
        ? my_theme_get_product_primary_category_term($product)
        : null;
    $category_slug = $category_term instanceof WP_Term ? sanitize_title((string) $category_term->slug) : '';
    $category_label = $category_term instanceof WP_Term ? trim((string) $category_term->name) : '';

    $source_url = trim((string) get_post_meta($product_id, '_official_source_url', true));
    $source_page = trim((string) get_post_meta($product_id, '_official_source_page', true));
    if ($source_url === '' && $source_page !== '') {
        $source_url = $source_page;
        if (!$dry_run) {
            update_post_meta($product_id, '_official_source_url', esc_url_raw($source_page));
        }
        $dirty = true;
        $stats['source_url_filled']++;
    }

    $dulux_row = isset($dulux_rows[$slug]) && is_array($dulux_rows[$slug]) ? $dulux_rows[$slug] : null;
    $current_description = (string) $product->get_description();
    $current_desc_text = $normalize_text($current_description);
    $current_short = (string) $product->get_short_description();
    $current_short_text = $normalize_text($current_short);

    $replacement_description = '';
    if ($dulux_row && !empty($dulux_row['description'])) {
        $replacement_description = trim((string) $dulux_row['description']);
    } elseif ($current_desc_text === '' || (function_exists('mb_strlen') ? mb_strlen($current_desc_text) : strlen($current_desc_text)) < 90) {
        $replacement_description = $fetch_source_summary($source_url);
    }

    if ($replacement_description === '' && ((function_exists('mb_strlen') ? mb_strlen($current_desc_text) : strlen($current_desc_text)) < 90)) {
        $pack_map = function_exists('my_theme_get_pack_price_map_for_display')
            ? my_theme_get_pack_price_map_for_display($product)
            : [];
        $pack_labels = !empty($pack_map) ? array_keys($pack_map) : [];
        $display_capacity = trim((string) $product->get_meta('_display_capacity_list'));
        $display_weight = trim((string) $product->get_meta('_display_weight_list'));
        if ($display_capacity !== '') {
            $pack_labels = array_merge($pack_labels, preg_split('/\s*\|\s*/', $display_capacity) ?: []);
        }
        if ($display_weight !== '') {
            $pack_labels = array_merge($pack_labels, preg_split('/\s*\|\s*/', $display_weight) ?: []);
        }
        $pack_labels = array_values(array_filter(array_unique(array_map('trim', $pack_labels))));

        $brand_label = function_exists('my_theme_get_product_brand_label')
            ? trim((string) my_theme_get_product_brand_label($product))
            : '';
        $pack_text = '';
        if (!empty($pack_labels)) {
            $pack_text = ' Quy cách đang dùng trên web: ' . implode(', ', $pack_labels) . '.';
        }
        $line_text = $line_label !== '' && $line_label !== $category_label ? ' thuộc dòng ' . $line_label : '';
        $replacement_description = $build_category_note($category_slug, $name) . ($line_text !== '' ? ' Sản phẩm' . $line_text . ' giúp người mua đối chiếu nhanh hơn theo đúng nhóm cần dùng.' : '') . $pack_text;
    }

    if ($replacement_description !== '') {
        $replacement_plain = $normalize_text($replacement_description);
        $replacement_length = function_exists('mb_strlen') ? mb_strlen($replacement_plain) : strlen($replacement_plain);
        if ($replacement_length < 90) {
            $pack_map = function_exists('my_theme_get_pack_price_map_for_display')
                ? my_theme_get_pack_price_map_for_display($product)
                : [];
            $pack_labels = !empty($pack_map) ? array_keys($pack_map) : [];
            $display_capacity = trim((string) $product->get_meta('_display_capacity_list'));
            $display_weight = trim((string) $product->get_meta('_display_weight_list'));
            if ($display_capacity !== '') {
                $pack_labels = array_merge($pack_labels, preg_split('/\s*\|\s*/', $display_capacity) ?: []);
            }
            if ($display_weight !== '') {
                $pack_labels = array_merge($pack_labels, preg_split('/\s*\|\s*/', $display_weight) ?: []);
            }
            $pack_labels = array_values(array_filter(array_unique(array_map('trim', $pack_labels))));
            $pack_text = !empty($pack_labels) ? (' Quy cách tham khảo: ' . implode(', ', $pack_labels) . '.') : '';
            $generic_tail = $build_category_note($category_slug, $name) . $pack_text;
            if ($generic_tail !== '') {
                $replacement_description = trim($replacement_description . ' ' . $generic_tail);
            }
        }
    }

    if ($replacement_description !== '') {
        $replacement_text = $normalize_text($replacement_description);
        if ($replacement_text !== '' && $replacement_text !== $current_desc_text) {
            if (!$dry_run) {
                $product->set_description($replacement_description);
            }
            $dirty = true;
            $stats['desc_updated']++;
            $current_desc_text = $replacement_text;
        }
    }

    $target_short = $build_short_text($current_desc_text, $name . ' phù hợp cho nhu cầu thi công và hoàn thiện bề mặt.');
    if ($target_short !== '' && $target_short !== $current_short_text) {
        if (!$dry_run) {
            $product->set_short_description($target_short);
        }
        $dirty = true;
        $stats['short_updated']++;
    }

    $price_raw = (string) $product->get_price();
    if ($price_raw === '0') {
        if (!$dry_run) {
            $product->set_price('');
            $product->set_regular_price('');
            $product->set_sale_price('');
        }
        $dirty = true;
        $stats['price_zero_cleared']++;
    }

    $price_map = function_exists('my_theme_get_pack_price_map_for_display')
        ? my_theme_get_pack_price_map_for_display($product)
        : [];

    if (empty($price_map)) {
        $map_candidate = [];

        if ($dulux_row && !empty($dulux_row['description']) && function_exists('my_theme_extract_pack_price_map_from_text')) {
            $map_candidate = my_theme_extract_pack_price_map_from_text((string) $dulux_row['description'], function_exists('my_theme_is_putty_product') ? my_theme_is_putty_product($product) : false);
        }
        if (empty($map_candidate) && $source_url !== '' && function_exists('my_theme_fetch_pack_price_map_from_source_url')) {
            $map_candidate = my_theme_fetch_pack_price_map_from_source_url($source_url, function_exists('my_theme_is_putty_product') ? my_theme_is_putty_product($product) : false);
        }

        if (!empty($map_candidate)) {
            if (function_exists('my_theme_compare_pack_labels')) {
                uksort($map_candidate, 'my_theme_compare_pack_labels');
            }
            $map_parts = [];
            foreach ($map_candidate as $size_label => $price_value) {
                $map_parts[] = $size_label . ':' . (float) $price_value;
            }
            if (!$dry_run) {
                $product->update_meta_data('_capacity_price_map', implode(' | ', $map_parts));
                $set_pack_labels($product, array_keys($map_candidate));
                $min_price = min(array_values($map_candidate));
                if ($min_price > 0) {
                    $product->set_regular_price((string) $min_price);
                    $product->set_price((string) $min_price);
                }
            }
            $dirty = true;
            $stats['price_map_updated']++;
            $stats['pack_labels_updated']++;
            $price_map = $map_candidate;
        }
    }

    if (empty($price_map)) {
        $pack_labels = [];
        if ($dulux_row && !empty($dulux_row['capacities']) && is_array($dulux_row['capacities'])) {
            foreach ($dulux_row['capacities'] as $capacity_row) {
                if (!is_array($capacity_row) || empty($capacity_row['label'])) {
                    continue;
                }
                $label = trim((string) $capacity_row['label']);
                if ($label !== '') {
                    $pack_labels[] = $label;
                }
            }
        }

        if (empty($pack_labels) && preg_match_all('/\b\d+(?:[.,]\d+)?\s*(?:kg|l|ml)\b/iu', $name . ' ' . $slug, $matches)) {
            foreach ((array) $matches[0] as $label) {
                $pack_labels[] = strtoupper(str_replace(' ', '', trim((string) $label)));
            }
        }

        $pack_labels = array_values(array_filter(array_unique($pack_labels)));
        if (!empty($pack_labels)) {
            if (!$dry_run) {
                $set_pack_labels($product, $pack_labels);
            }
            $dirty = true;
            $stats['pack_labels_updated']++;
        }
    }

    if ($dirty) {
        if (!$dry_run) {
            $product->save();
        }
        $stats['updated']++;
    }
}

if (!$dry_run) {
    if (function_exists('my_theme_flush_product_cache_fragments')) {
        my_theme_flush_product_cache_fragments(0);
    }
    update_option('my_theme_filter_cache_version', (string) time(), false);
}

echo 'catalog_enrich_done'
    . ' checked=' . (int) $stats['checked']
    . ' updated=' . (int) $stats['updated']
    . ' desc_updated=' . (int) $stats['desc_updated']
    . ' short_updated=' . (int) $stats['short_updated']
    . ' price_zero_cleared=' . (int) $stats['price_zero_cleared']
    . ' price_map_updated=' . (int) $stats['price_map_updated']
    . ' pack_labels_updated=' . (int) $stats['pack_labels_updated']
    . ' source_url_filled=' . (int) $stats['source_url_filled']
    . ' dry_run=' . ($dry_run ? 'yes' : 'no')
    . PHP_EOL;
