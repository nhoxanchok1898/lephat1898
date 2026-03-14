<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('my_theme_get_search_assist_payload')) {
    function my_theme_get_search_assist_payload($force_refresh = false)
    {
        static $request_cache = null;

        if (!$force_refresh && is_array($request_cache)) {
            return $request_cache;
        }

        $visible_product_ids = function_exists('my_theme_get_catalog_visible_product_ids')
            ? my_theme_get_catalog_visible_product_ids(false)
            : [];
        $visible_product_ids = my_theme_normalize_product_id_list($visible_product_ids);

        $shop_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
        $cache_version = (string) get_option('my_theme_filter_cache_version', '1');
        $digest = !empty($visible_product_ids) ? md5(implode(',', $visible_product_ids)) : 'empty';
        $cache_key = 'my_theme_search_assist_v4_' . $cache_version . '_' . $digest;

        if (!$force_refresh) {
            $cached = get_transient($cache_key);
            if (is_array($cached)) {
                $request_cache = $cached;
                return $cached;
            }
        }

        $payload = [
            'products' => [],
            'brands' => [],
            'lines' => [],
            'defaults' => [],
            'quick_queries' => [],
            'quick_links' => [],
        ];

        $quick_queries = [
            'Sơn nội thất',
            'Sơn ngoại thất',
            'Chống thấm',
            'Sơn epoxy',
            'Sơn kim loại',
            'Bột trét',
        ];
        foreach ($quick_queries as $query_label) {
            $query_label = trim((string) $query_label);
            if ($query_label === '') {
                continue;
            }
            $payload['quick_queries'][] = [
                'label' => $query_label,
                'url' => add_query_arg('q', $query_label, $shop_url),
            ];
        }

        $payload['quick_links'] = [
            [
                'label' => 'Mở giải pháp',
                'url' => home_url('/giai-phap'),
            ],
            [
                'label' => 'Liên hệ báo giá',
                'url' => home_url('/lien-he'),
            ],
            [
                'label' => 'FAQ',
                'url' => home_url('/faq'),
            ],
            [
                'label' => 'Tính sơn',
                'url' => my_theme_get_paint_calculator_url(),
            ],
        ];

        if (!empty($visible_product_ids) && function_exists('my_theme_get_catalog_search_index')) {
            $top_product_ids = function_exists('my_theme_get_catalog_ranked_product_ids')
                ? my_theme_get_catalog_ranked_product_ids($visible_product_ids, 48)
                : array_slice($visible_product_ids, 0, 48);
            $search_index = my_theme_get_catalog_search_index($visible_product_ids);

            foreach ($top_product_ids as $product_id) {
                if (!isset($search_index[$product_id]) || !is_array($search_index[$product_id])) {
                    continue;
                }
                $entry = $search_index[$product_id];
                $product_name = isset($entry['name']) ? trim((string) $entry['name']) : '';
                if ($product_name === '') {
                    continue;
                }

                $brand_label = isset($entry['brand_label']) ? trim((string) $entry['brand_label']) : '';
                $line_label = isset($entry['line_label']) ? trim((string) $entry['line_label']) : '';
                $meta_bits = array_values(array_filter([$brand_label, $line_label]));
                $meta_text = implode(' • ', $meta_bits);
                $payload['products'][] = [
                    'type' => 'Sản phẩm',
                    'label' => $product_name,
                    'meta' => $meta_text,
                    'url' => isset($entry['url']) ? (string) $entry['url'] : get_permalink((int) $product_id),
                    'search' => my_theme_normalize_search_text($product_name . ' ' . $meta_text),
                ];
            }
        }

        $brand_options = function_exists('my_theme_get_brand_filter_options')
            ? my_theme_get_brand_filter_options($visible_product_ids)
            : [];
        if (is_array($brand_options)) {
            foreach (array_slice($brand_options, 0, 8, true) as $brand_slug => $brand_meta) {
                $brand_slug = sanitize_title((string) $brand_slug);
                $brand_label = isset($brand_meta['label']) ? trim((string) $brand_meta['label']) : '';
                $brand_count = isset($brand_meta['count']) ? max(0, (int) $brand_meta['count']) : 0;
                if ($brand_slug === '' || $brand_label === '') {
                    continue;
                }

                $meta_text = $brand_count > 0 ? ($brand_count . ' sản phẩm') : 'Thương hiệu';
                $payload['brands'][] = [
                    'type' => 'Thương hiệu',
                    'label' => $brand_label,
                    'meta' => $meta_text,
                    'url' => add_query_arg('brand', $brand_slug, $shop_url),
                    'search' => my_theme_normalize_search_text($brand_label . ' ' . $brand_slug),
                ];
            }
        }

        $line_options = function_exists('my_theme_get_line_filter_options')
            ? my_theme_get_line_filter_options($visible_product_ids, '')
            : [];
        if (is_array($line_options)) {
            foreach (array_slice($line_options, 0, 8, true) as $line_slug => $line_meta) {
                $line_slug = sanitize_title((string) $line_slug);
                $line_label = isset($line_meta['label']) ? trim((string) $line_meta['label']) : '';
                $line_count = isset($line_meta['count']) ? max(0, (int) $line_meta['count']) : 0;
                if ($line_slug === '' || $line_label === '') {
                    continue;
                }

                $meta_text = $line_count > 0 ? ($line_count . ' sản phẩm') : 'Danh mục';
                $payload['lines'][] = [
                    'type' => 'Hạng mục',
                    'label' => $line_label,
                    'meta' => $meta_text,
                    'url' => add_query_arg('line', $line_slug, $shop_url),
                    'search' => my_theme_normalize_search_text($line_label . ' ' . $line_slug),
                ];
            }
        }

        $payload['defaults'] = array_values(array_slice(array_merge(
            array_slice($payload['products'], 0, 3),
            array_slice($payload['brands'], 0, 2),
            array_slice($payload['lines'], 0, 2)
        ), 0, 6));

        $request_cache = $payload;
        set_transient($cache_key, $payload, 30 * MINUTE_IN_SECONDS);
        return $payload;
    }
}

if (!function_exists('my_theme_render_search_assist')) {
    function my_theme_render_search_assist($context = 'header')
    {
        $context = sanitize_key((string) $context);
        if ($context === '') {
            $context = 'header';
        }

        $payload = my_theme_get_search_assist_payload();
        $quick_queries = isset($payload['quick_queries']) && is_array($payload['quick_queries']) ? $payload['quick_queries'] : [];
        $quick_links = isset($payload['quick_links']) && is_array($payload['quick_links']) ? $payload['quick_links'] : [];
        $defaults = isset($payload['defaults']) && is_array($payload['defaults']) ? $payload['defaults'] : [];

        echo '<div class="search-assist search-assist--' . esc_attr($context) . '" data-search-assist-panel hidden>';
        echo '<div class="search-assist__section">';
        echo '<p class="search-assist__eyebrow">Tìm nhanh theo nhu cầu</p>';
        echo '<div class="search-assist__chips">';
        foreach ($quick_queries as $item) {
            $item_label = isset($item['label']) ? trim((string) $item['label']) : '';
            $item_url = isset($item['url']) ? (string) $item['url'] : '';
            if ($item_label === '' || $item_url === '') {
                continue;
            }
            echo '<a class="search-assist__chip" href="' . esc_url($item_url) . '">' . esc_html($item_label) . '</a>';
        }
        echo '</div>';
        echo '</div>';

        echo '<div class="search-assist__section">';
        echo '<p class="search-assist__eyebrow">Lối đi nhanh</p>';
        echo '<div class="search-assist__chips search-assist__chips--links">';
        foreach ($quick_links as $item) {
            $item_label = isset($item['label']) ? trim((string) $item['label']) : '';
            $item_url = isset($item['url']) ? (string) $item['url'] : '';
            if ($item_label === '' || $item_url === '') {
                continue;
            }
            echo '<a class="search-assist__chip search-assist__chip--link" href="' . esc_url($item_url) . '">' . esc_html($item_label) . '</a>';
        }
        echo '</div>';
        echo '</div>';

        echo '<div class="search-assist__section">';
        echo '<div class="search-assist__head">';
        echo '<p class="search-assist__eyebrow">Gợi ý từ kho hiện có</p>';
        echo '<span class="search-assist__status" data-search-assist-status>Gõ tên mã, hãng hoặc hạng mục để lọc nhanh hơn.</span>';
        echo '</div>';
        echo '<div class="search-assist__results" data-search-assist-results>';
        foreach ($defaults as $item) {
            $item_label = isset($item['label']) ? trim((string) $item['label']) : '';
            $item_url = isset($item['url']) ? (string) $item['url'] : '';
            $item_type = isset($item['type']) ? trim((string) $item['type']) : 'Gợi ý';
            $item_meta = isset($item['meta']) ? trim((string) $item['meta']) : '';
            if ($item_label === '' || $item_url === '') {
                continue;
            }
            echo '<a class="search-assist__item" href="' . esc_url($item_url) . '">';
            echo '<span class="search-assist__item-top"><span class="search-assist__badge">' . esc_html($item_type) . '</span></span>';
            echo '<strong>' . esc_html($item_label) . '</strong>';
            if ($item_meta !== '') {
                echo '<span class="search-assist__meta">' . esc_html($item_meta) . '</span>';
            }
            echo '</a>';
        }
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
}
