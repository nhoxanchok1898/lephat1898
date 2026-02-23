<?php
/**
 * Functions for My Custom Theme.
 */

// Enqueue theme assets.
add_action('wp_enqueue_scripts', function () {
    $style_path = get_stylesheet_directory() . '/style.css';
    $style_ver = file_exists($style_path) ? filemtime($style_path) : null;

    wp_enqueue_style('my-custom-theme-font', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Be+Vietnam+Pro:wght@400;600;700;800&display=swap', [], null);
    wp_enqueue_style('my-custom-theme-style', get_stylesheet_uri(), [], $style_ver);

    $main_js_path = get_theme_file_path('assets/main.js');
    $main_js_ver = file_exists($main_js_path) ? filemtime($main_js_path) : null;
    wp_enqueue_script('my-custom-theme-main', get_theme_file_uri('assets/main.js'), [], $main_js_ver, true);

    if ((function_exists('is_product') && is_product()) || is_front_page()) {
        $ver_calc = file_exists(get_theme_file_path('assets/paint-calculator.js')) ? filemtime(get_theme_file_path('assets/paint-calculator.js')) : null;
        wp_enqueue_script('my-custom-theme-paint-calculator', get_theme_file_uri('assets/paint-calculator.js'), [], $ver_calc, true);
    }
});

// Performance trim: remove Woo assets not needed on most front pages.
add_action('wp_enqueue_scripts', function () {
    if (is_admin()) {
        return;
    }

    $is_cart_page = function_exists('is_cart') && is_cart();
    $is_checkout_page = function_exists('is_checkout') && is_checkout();
    $is_account_page = function_exists('is_account_page') && is_account_page();

    if (!$is_cart_page && !$is_checkout_page && !$is_account_page) {
        wp_dequeue_script('wc-cart-fragments');
    }

    if (!$is_cart_page && !$is_checkout_page) {
        wp_dequeue_style('wc-blocks-style');
        wp_dequeue_style('wc-blocks-vendors-style');
        wp_dequeue_style('wc-blocks-packages-style');
    }
}, 99);

// Performance: disable WP emoji assets on front-end.
add_action('init', function () {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
});
add_filter('emoji_svg_url', '__return_false');

// Keep product listing images lightweight on archive/single pages.
add_filter('wp_get_attachment_image_attributes', function ($attr) {
    if (is_admin()) {
        return $attr;
    }
    $is_woo_screen = function_exists('is_woocommerce') && is_woocommerce();
    $is_home_screen = is_front_page() || is_home();
    if (!$is_woo_screen && !$is_home_screen) {
        return $attr;
    }
    $attr['decoding'] = 'async';
    if (empty($attr['loading'])) {
        $attr['loading'] = 'lazy';
    }
    if (isset($attr['fetchpriority']) && $attr['fetchpriority'] === 'high') {
        unset($attr['fetchpriority']);
    }
    return $attr;
}, 20);

/**
 * Bản dịch tiếng Việt cho checkout WooCommerce (không đổi layout).
 */
add_filter('gettext', function ($translated_text, $text, $domain) {
    $map = [
        'Checkout' => 'Thanh toán',
        'Contact information' => 'Thông tin liên hệ',
        "We'll use this email to send you details and updates about your order." => 'Thư điện tử để nhận thông tin đơn hàng.',
        'Order summary' => 'Tóm tắt đơn hàng',
        'Add a coupon' => 'Thêm mã giảm giá',
        'Subtotal' => 'Tạm tính',
        'Total' => 'Tổng cộng',
        'Billing address' => 'Địa chỉ thanh toán',
        'Payment' => 'Thanh toán',
        'Place order' => 'Đặt hàng',
        'Continue' => 'Tiếp tục',
        'First name' => 'Họ',
        'Last name' => 'Tên',
        'Street address' => 'Địa chỉ',
        'Town / City' => 'Thành phố',
        'Postcode / ZIP' => 'Mã bưu chính',
        'Phone' => 'Số điện thoại',
        'Email address' => 'Địa chỉ thư điện tử',
        'State' => 'Tỉnh/Thành',
        'Apartment, suite, unit, etc. (optional)' => 'Căn hộ, tầng, số nhà (tuỳ chọn)',
        'Optional' => 'Tuỳ chọn',
        'Order notes (optional)' => 'Ghi chú đơn hàng (tuỳ chọn)',
        'Country / Region' => 'Quốc gia / Khu vực',
        'Company name (optional)' => 'Công ty (tuỳ chọn)',
    ];
    if (isset($map[$text])) {
        return $map[$text];
    }
    return $translated_text;
}, 10, 3);

// Đổi label & placeholder trường địa chỉ.
add_filter('woocommerce_default_address_fields', function ($fields) {
    if (isset($fields['first_name'])) $fields['first_name']['label'] = 'Họ';
    if (isset($fields['last_name'])) $fields['last_name']['label'] = 'Tên';
    if (isset($fields['address_1'])) { $fields['address_1']['label'] = 'Địa chỉ'; $fields['address_1']['placeholder'] = 'Số nhà, đường'; }
    if (isset($fields['city'])) $fields['city']['label'] = 'Thành phố';
    if (isset($fields['postcode'])) $fields['postcode']['label'] = 'Mã bưu chính';
    if (isset($fields['state'])) $fields['state']['label'] = 'Tỉnh/Thành';
    return $fields;
});

add_filter('woocommerce_checkout_fields', function ($fields) {
    if (isset($fields['billing']['billing_email'])) {
        $fields['billing']['billing_email']['label'] = 'Thư điện tử liên hệ';
        $fields['billing']['billing_email']['placeholder'] = 'email@domain.com';
    }
    if (isset($fields['billing']['billing_phone'])) {
        $fields['billing']['billing_phone']['label'] = 'Số điện thoại';
        $fields['billing']['billing_phone']['placeholder'] = '090xxxxxxx';
    }
    return $fields;
});

add_filter('woocommerce_order_button_text', function () {
    return 'Đặt hàng';
});

// Đổi nhãn "Sale" sang tiếng Việt.
add_filter('woocommerce_sale_flash', function () {
    return '<span class="onsale">Giảm giá</span>';
});

// Woo adds "first/last" loop classes for float layout; remove them for CSS Grid cards.
add_filter('woocommerce_post_class', function ($classes) {
    if (!is_array($classes) || empty($classes)) {
        return $classes;
    }
    return array_values(array_filter($classes, function ($class_name) {
        return $class_name !== 'first' && $class_name !== 'last';
    }));
}, 20);

// Đổi nút thêm vào giỏ hàng sang tiếng Việt.
add_filter('woocommerce_product_add_to_cart_text', function () {
    return 'Thêm vào giỏ';
});
add_filter('woocommerce_product_single_add_to_cart_text', function () {
    return 'Thêm vào giỏ';
});

// Đổi tiêu đề trang Woo thành tiếng Việt
add_filter('woocommerce_page_title', function ($title) {
    if (is_shop()) return 'Sản phẩm';
    if (is_cart()) return 'Giỏ hàng';
    if (is_checkout()) return 'Thanh toán';
    if (is_account_page()) return 'Tài khoản';
    return $title;
});
add_filter('the_title', function ($title, $id) {
    if ($id == wc_get_page_id('shop')) return 'Sản phẩm';
    if ($id == wc_get_page_id('cart')) return 'Giỏ hàng';
    if ($id == wc_get_page_id('checkout')) return 'Thanh toán';
    if ($id == wc_get_page_id('myaccount')) return 'Tài khoản';
    return $title;
}, 10, 2);

// Bổ sung dịch cho Woo (núi nút/nội dung còn sót)
add_filter('gettext', function ($translated_text, $text, $domain) {
    $map = [
        'Cart totals' => 'Cộng giỏ hàng',
        'Cart' => 'Giỏ hàng',
        'Proceed to checkout' => 'Thanh toán',
        'Proceed to Checkout' => 'Thanh toán',
        'Checkout' => 'Thanh toán',
        'Apply coupon' => 'Áp dụng mã',
        'Coupon code' => 'Mã giảm giá',
        'Update cart' => 'Cập nhật giỏ hàng',
        'Billing details' => 'Thông tin thanh toán',
        'Order summary' => 'Tóm tắt đơn hàng',
        'Place order' => 'Đặt hàng',
        'Payment' => 'Thanh toán',
        'Add a coupon' => 'Thêm mã giảm giá',
        'Add to cart' => 'Thêm vào giỏ',
        'Select options' => 'Chọn tuỳ chọn',
        'Read more' => 'Xem thêm',
        'View cart' => 'Xem giỏ hàng',
        'View product' => 'Xem sản phẩm',
        'Out of stock' => 'Hết hàng',
        'In stock' => 'Còn hàng',
        'Related products' => 'Sản phẩm liên quan',
        'Description' => 'Mô tả',
        'Additional information' => 'Thông tin bổ sung',
        'Reviews' => 'Đánh giá',
        'Available on backorder' => 'Đặt trước khi hết hàng',
    ];
    if (isset($map[$text])) return $map[$text];
    return $translated_text;
}, 10, 3);

// Enable featured images.
add_theme_support('post-thumbnails');

// Enable document title tag.
add_theme_support('title-tag');

// Register a primary menu.
add_action('after_setup_theme', function () {
    register_nav_menus([
        'primary' => __('Menu chính', 'my-custom-theme'),
    ]);
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
});

// Loại bỏ mục "Bài viết" khỏi menu primary (menu cũ không có blog).
add_filter('wp_nav_menu_objects', function ($items, $args) {
    if (isset($args->theme_location) && $args->theme_location === 'primary') {
        $items = array_filter($items, function ($item) {
            $title = trim(wp_strip_all_tags($item->title));
            if ($title === 'Bài viết') {
                return false;
            }
            if ($title === 'Giỏ hàng') {
                return false;
            }
            if ($title === 'Thanh toán') {
                return false;
            }
            if ($title === 'Liên hệ') {
                return false;
            }
            return true;
        });
        // Loại bỏ mục bị lặp trong menu chính.
        $seen = [];
        $filtered = [];
        foreach ($items as $item) {
            $title = trim(wp_strip_all_tags($item->title));
            if ($title !== '' && (int) $item->menu_item_parent === 0) {
                $key = function_exists('mb_strtolower') ? mb_strtolower($title) : strtolower($title);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
            }
            $filtered[] = $item;
        }
        $items = $filtered;
    }
    return $items;
}, 10, 2);

if (!function_exists('my_theme_normalize_search_text')) {
    function my_theme_normalize_search_text($value)
    {
        $text = remove_accents(wp_strip_all_tags((string) $value));
        $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
        $text = preg_replace('/[^a-z0-9\\s-]+/', ' ', $text);
        $text = preg_replace('/\\s+/', ' ', trim((string) $text));
        return $text;
    }
}

if (!function_exists('my_theme_is_placeholder_product_name')) {
    function my_theme_is_placeholder_product_name($title)
    {
        $normalized = my_theme_normalize_search_text((string) $title);
        if ($normalized === '') {
            return true;
        }
        if (preg_match('/^(image|product|san pham)\\s*\\d+$/', $normalized)) {
            return true;
        }
        if (preg_match('/^packshot medium(\\s+\\d+)?$/', $normalized)) {
            return true;
        }
        if (strpos($normalized, 'screenshot') === 0) {
            return true;
        }
        return false;
    }
}

if (!function_exists('my_theme_clean_product_display_title')) {
    function my_theme_clean_product_display_title($title)
    {
        $title = str_replace('_', ' ', (string) $title);
        $title = preg_replace('/\s+/u', ' ', trim((string) $title));
        return $title;
    }
}

if (!function_exists('my_theme_get_product_display_name')) {
    function my_theme_get_product_display_name($prod = null)
    {
        $product = ($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID());
        if (!$product instanceof WC_Product) {
            return '';
        }
        return my_theme_clean_product_display_title($product->get_name());
    }
}

add_filter('the_title', function ($title, $id) {
    if (is_admin()) {
        return $title;
    }
    $post_id = (int) $id;
    if ($post_id <= 0 || get_post_type($post_id) !== 'product') {
        return $title;
    }
    return my_theme_clean_product_display_title($title);
}, 12, 2);

if (!function_exists('my_theme_slug_list_has_any')) {
    function my_theme_slug_list_has_any($slugs, $targets)
    {
        if (empty($slugs) || empty($targets)) {
            return false;
        }
        $normalized_slugs = array_values(array_unique(array_map('sanitize_title', (array) $slugs)));
        $normalized_targets = array_values(array_unique(array_map('sanitize_title', (array) $targets)));
        return !empty(array_intersect($normalized_slugs, $normalized_targets));
    }
}

if (!function_exists('my_theme_get_product_category_priority_map')) {
    function my_theme_get_product_category_priority_map()
    {
        return [
            'son-noi-that'    => 10,
            'son-ngoai-that'  => 20,
            'son-lot'         => 30,
            'chong-tham'      => 40,
            'bot-tret'        => 50,
            'keo-va-phu-gia'  => 60,
            'son-kim-loai'    => 70,
            'son-cong-nghiep' => 80,
            'son-epoxy'       => 90,
            'son-dau'         => 100,
        ];
    }
}

if (!function_exists('my_theme_sort_product_category_terms')) {
    function my_theme_sort_product_category_terms($terms)
    {
        $priority = my_theme_get_product_category_priority_map();
        usort($terms, function ($a, $b) use ($priority) {
            $a_slug = ($a instanceof WP_Term) ? (string) $a->slug : '';
            $b_slug = ($b instanceof WP_Term) ? (string) $b->slug : '';
            $a_rank = $priority[$a_slug] ?? 999;
            $b_rank = $priority[$b_slug] ?? 999;
            if ($a_rank !== $b_rank) {
                return ($a_rank < $b_rank) ? -1 : 1;
            }
            $a_name = ($a instanceof WP_Term) ? (string) $a->name : '';
            $b_name = ($b instanceof WP_Term) ? (string) $b->name : '';
            return strnatcasecmp($a_name, $b_name);
        });
        return $terms;
    }
}

if (!function_exists('my_theme_get_product_cat_ids_by_slugs')) {
    function my_theme_get_product_cat_ids_by_slugs($slugs)
    {
        $ids = [];
        foreach ((array) $slugs as $slug) {
            $slug = sanitize_title((string) $slug);
            if ($slug === '') {
                continue;
            }
            $term = get_term_by('slug', $slug, 'product_cat');
            if ($term instanceof WP_Term && !empty($term->term_id)) {
                $ids[] = (int) $term->term_id;
            }
        }
        return array_values(array_unique(array_filter($ids)));
    }
}

if (!function_exists('my_theme_get_search_intent_category_slugs')) {
    function my_theme_get_search_intent_category_slugs($query_norm)
    {
        $q = my_theme_normalize_search_text($query_norm);
        if ($q === '') {
            return [];
        }

        $slugs = [];
        $contains = function ($needle) use ($q) {
            return strpos($q, my_theme_normalize_search_text($needle)) !== false;
        };

        if ($contains('sơn kim loại') || $contains('kim loai') || $contains('chống rỉ') || $contains('chong ri')) {
            $slugs[] = 'son-kim-loai';
        }
        if ($contains('epoxy')) {
            $slugs[] = 'son-epoxy';
        }
        if ($contains('sơn công nghiệp') || $contains('son cong nghiep')) {
            $slugs[] = 'son-cong-nghiep';
        }
        if ($contains('sơn dầu') || $contains('son dau')) {
            $slugs[] = 'son-dau';
        }
        if ($contains('sơn lót') || $contains('son lot') || $contains('primer') || $contains('sealer')) {
            $slugs[] = 'son-lot';
        }
        if ($contains('nội thất') || $contains('noi that')) {
            $slugs[] = 'son-noi-that';
        }
        if ($contains('ngoại thất') || $contains('ngoai that')) {
            $slugs[] = 'son-ngoai-that';
        }
        if ($contains('chống thấm') || $contains('chong tham') || $contains('waterproof')) {
            $slugs[] = 'chong-tham';
        }
        if ($contains('bột trét') || $contains('bot tret') || $contains('matit') || $contains('putty')) {
            $slugs[] = 'bot-tret';
        }
        if ($contains('keo') || $contains('chà ron') || $contains('cha ron') || $contains('dán gạch') || $contains('dan gach') || $contains('weber')) {
            $slugs[] = 'keo-va-phu-gia';
        }

        return array_values(array_unique(array_filter(array_map('sanitize_title', $slugs))));
    }
}

if (!function_exists('my_theme_get_search_matched_product_cat_ids')) {
    function my_theme_get_search_matched_product_cat_ids($raw_query)
    {
        $query_norm = my_theme_normalize_search_text($raw_query);
        if ($query_norm === '') {
            return [];
        }

        // Prioritize explicit user intent (e.g. "son kim loai") to keep results accurate.
        $intent_slugs = my_theme_get_search_intent_category_slugs($query_norm);
        if (!empty($intent_slugs)) {
            $intent_ids = [];
            foreach ($intent_slugs as $intent_slug) {
                $term = get_term_by('slug', sanitize_title($intent_slug), 'product_cat');
                if (!$term instanceof WP_Term || empty($term->term_id)) {
                    continue;
                }
                if ((int) $term->count <= 0) {
                    continue;
                }
                $intent_ids[] = (int) $term->term_id;
            }
            if (!empty($intent_ids)) {
                return array_values(array_unique($intent_ids));
            }
        }

        $terms = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);
        if (is_wp_error($terms) || empty($terms)) {
            return [];
        }

        $stop_words = ['son', 'san', 'pham', 'gia', 'bao', 'loai', 'cho', 'cua', 'va', 'theo', 'hang'];
        $tokens = array_values(array_filter(explode(' ', $query_norm), function ($token) use ($stop_words) {
            return strlen($token) >= 2 && !in_array($token, $stop_words, true);
        }));

        $alias_map = [
            'kim loai'      => ['kim loai', 'chong ri', 'ri set'],
            'epoxy'         => ['epoxy'],
            'chong tham'    => ['chong tham'],
            'noi that'      => ['noi that'],
            'ngoai that'    => ['ngoai that'],
            'bot tret'      => ['bot tret', 'bot ba', 'matit'],
            'keo'           => ['keo', 'phu gia', 'cha ron', 'dan gach', 'webercolor', 'webertai', 'webertec'],
            'cha ron'       => ['cha ron', 'grout', 'webercolor'],
            'dan gach'      => ['dan gach', 'keo dan', 'webertai'],
            'son lot'       => ['son lot'],
            'son nuoc'      => ['son nuoc'],
            'son go'        => ['son go'],
            'giao thong'    => ['giao thong'],
            'hai thanh phan'=> ['hai thanh phan'],
            'chong ri'      => ['chong ri'],
        ];

        $scores = [];
        foreach ($terms as $term) {
            if (empty($term->term_id)) {
                continue;
            }
            if (!empty($term->slug) && $term->slug === 'uncategorized') {
                continue;
            }
            $candidate = my_theme_normalize_search_text($term->name . ' ' . str_replace('-', ' ', $term->slug));
            if ($candidate === '') {
                continue;
            }

            $score = 0;
            if ($query_norm === $candidate) {
                $score += 12;
            } elseif (strpos($query_norm, $candidate) !== false || strpos($candidate, $query_norm) !== false) {
                $score += 8;
            }

            if (!empty($tokens)) {
                $token_hits = 0;
                foreach ($tokens as $token) {
                    if (strpos($candidate, $token) !== false) {
                        $token_hits++;
                    }
                }
                if ($token_hits === count($tokens)) {
                    $score += 6;
                } else {
                    $score += $token_hits;
                }
            }

            foreach ($alias_map as $phrase => $hints) {
                if (strpos($query_norm, $phrase) === false) {
                    continue;
                }
                foreach ($hints as $hint) {
                    $hint_norm = my_theme_normalize_search_text($hint);
                    if ($hint_norm !== '' && strpos($candidate, $hint_norm) !== false) {
                        $score += 4;
                        break;
                    }
                }
            }

            if ($score > 0) {
                $scores[(int) $term->term_id] = $score;
            }
        }

        if (empty($scores)) {
            return [];
        }

        arsort($scores);
        $matched = [];
        foreach ($scores as $term_id => $score) {
            if ($score < 4) {
                continue;
            }
            $matched[] = (int) $term_id;
            if (count($matched) >= 8) {
                break;
            }
        }

        return $matched;
    }
}

if (!function_exists('my_theme_guess_primary_category_slug')) {
    function my_theme_guess_primary_category_slug($raw_text)
    {
        $text = my_theme_normalize_search_text($raw_text);
        if ($text === '') {
            return '';
        }

        $is_waterproof_system = (
            strpos($text, 'aquatech') !== false ||
            strpos($text, 'waterproof') !== false ||
            strpos($text, 'weberdry') !== false ||
            strpos($text, 'weberseal') !== false ||
            strpos($text, 'weberproof') !== false ||
            strpos($text, 'webershield') !== false
        );
        if ($is_waterproof_system) {
            return 'chong-tham';
        }
        if (
            strpos($text, 'chong tham') !== false &&
            strpos($text, 'son lot') === false &&
            strpos($text, 'ngoai that') === false &&
            strpos($text, 'weathershield') === false &&
            strpos($text, 'jotashield') === false
        ) {
            return 'chong-tham';
        }

        $rules = [
            'keo-va-phu-gia' => ['keo', 'cha ron', 'dan gach', 'webercolor', 'webertai', 'webertec', 'weberad', 'grout', 'mortar', 'vua kho'],
            'bot-tret'       => ['bot tret', 'putty', 'matit', 'bot ba'],
            'son-lot'        => ['son lot', 'primer', 'sealer', 'lot chong kiem'],
            'son-epoxy'      => ['epoxy'],
            'son-kim-loai'   => ['kim loai', 'chong ri', 'ri set', 'ngan ngua ri', 'gardex', 'alkyd'],
            'son-dau'        => ['son dau'],
            'son-ngoai-that' => ['ngoai that', 'exterior', 'weathershield', 'jotashield', 'ultima', 'powerflexx'],
            'son-noi-that'   => ['noi that', 'interior', 'easyclean', 'ambiance', 'airfresh', 'odour less'],
            'chong-tham'     => ['chong tham', 'waterproof', 'aquatech', 'weberdry', 'weberseal', 'weberproof', 'webershield'],
        ];

        foreach ($rules as $slug => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    return $slug;
                }
            }
        }

        return '';
    }
}

if (!function_exists('my_theme_set_product_primary_category_by_guess')) {
    function my_theme_set_product_primary_category_by_guess($product_id, $source_text = '', $force = false)
    {
        $product_id = (int) $product_id;
        if ($product_id <= 0 || !taxonomy_exists('product_cat')) {
            return false;
        }

        $current_slugs = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'slugs']);
        if (is_wp_error($current_slugs)) {
            $current_slugs = [];
        }
        $current_slugs = array_values(array_filter((array) $current_slugs, function ($slug) {
            return $slug !== '' && $slug !== 'uncategorized';
        }));

        if (!$force && !empty($current_slugs)) {
            return false;
        }

        $guess_slug = my_theme_guess_primary_category_slug($source_text);
        if ($guess_slug === '') {
            return false;
        }

        $term = get_term_by('slug', sanitize_title($guess_slug), 'product_cat');
        if (!$term instanceof WP_Term || empty($term->term_id)) {
            return false;
        }

        wp_set_object_terms($product_id, [(int) $term->term_id], 'product_cat', false);
        return true;
    }
}

if (!function_exists('my_theme_get_catalog_visible_product_ids')) {
    function my_theme_get_catalog_visible_product_ids($strict_price = false)
    {
        if (!function_exists('wc_get_product')) {
            return [];
        }

        $strict_price = (bool) $strict_price;
        $cache_suffix = $strict_price ? 'priced' : 'shop';
        $cache_key = 'my_theme_catalog_visible_ids_' . $cache_suffix . '_v1';
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        $candidate_ids = get_posts([
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'orderby'        => 'date',
            'order'          => 'DESC',
            'no_found_rows'  => true,
        ]);
        if (empty($candidate_ids)) {
            set_transient($cache_key, [], 6 * HOUR_IN_SECONDS);
            return [];
        }

        $visible_ids = [];
        foreach ($candidate_ids as $candidate_id) {
            $product = wc_get_product((int) $candidate_id);
            if (!$product instanceof WC_Product) {
                continue;
            }

            if ($strict_price) {
                if (!my_theme_is_catalog_ready_product($product, true)) {
                    continue;
                }
            } else {
                if (!my_theme_is_shop_visible_product($product)) {
                    continue;
                }
            }

            $visible_ids[] = (int) $candidate_id;
        }

        $visible_ids = array_values(array_unique($visible_ids));
        set_transient($cache_key, $visible_ids, 6 * HOUR_IN_SECONDS);
        return $visible_ids;
    }
}

if (!function_exists('my_theme_is_shop_visible_product')) {
    function my_theme_is_shop_visible_product($prod = null)
    {
        $product = ($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID());
        if (!$product instanceof WC_Product) {
            return false;
        }
        if (!my_theme_is_catalog_ready_product($product, false)) {
            return false;
        }

        $product_id = (int) $product->get_id();
        $is_official = ((string) get_post_meta($product_id, '_official_import_key', true) !== '');
        if ($is_official) {
            return true;
        }

        return my_theme_is_catalog_ready_product($product, true);
    }
}

if (!function_exists('my_theme_render_product_category_menu_item')) {
    function my_theme_render_product_category_menu_item()
    {
        if (!taxonomy_exists('product_cat')) {
            return '';
        }

        $cache_key = 'my_theme_catalog_menu_html_v3';
        $cached_html = get_transient($cache_key);
        if (is_string($cached_html) && $cached_html !== '') {
            return $cached_html;
        }

        $visible_product_ids = my_theme_get_catalog_visible_product_ids(false);
        if (empty($visible_product_ids)) {
            return '';
        }

        $terms = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
            'orderby'    => 'name',
            'order'      => 'ASC',
            'object_ids' => $visible_product_ids,
        ]);
        if (is_wp_error($terms) || empty($terms)) {
            return '';
        }

        $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop');
        $children_by_parent = [];
        foreach ($terms as $term) {
            if (!empty($term->slug) && $term->slug === 'uncategorized') {
                continue;
            }
            $parent_id = (int) $term->parent;
            if (!isset($children_by_parent[$parent_id])) {
                $children_by_parent[$parent_id] = [];
            }
            $children_by_parent[$parent_id][] = $term;
        }
        foreach ($children_by_parent as $parent_id => $group_terms) {
            $children_by_parent[$parent_id] = my_theme_sort_product_category_terms($group_terms);
        }

        $render_tree = function ($parent_id, $depth = 0) use (&$render_tree, $children_by_parent, $shop_url) {
            if (empty($children_by_parent[$parent_id])) {
                return '';
            }

            $html = '<ul class="sub-menu">';
            foreach ($children_by_parent[$parent_id] as $term) {
                $term_id = (int) $term->term_id;
                $has_children = !empty($children_by_parent[$term_id]) && $depth < 2;
                $item_classes = 'menu-item menu-item-product-cat';
                if ($has_children) {
                    $item_classes .= ' menu-item-has-children';
                }
                $term_url = add_query_arg('category', $term_id, $shop_url);
                $html .= '<li class="' . esc_attr($item_classes) . '">';
                $html .= '<a href="' . esc_url($term_url) . '">' . esc_html($term->name) . '</a>';
                if ($has_children) {
                    $html .= $render_tree($term_id, $depth + 1);
                }
                $html .= '</li>';
            }
            $html .= '</ul>';

            return $html;
        };

        $content = $render_tree(0, 0);
        if ($content === '') {
            return '';
        }

        $html = '<li class="menu-item menu-item-catalog menu-item-has-children"><a href="' . esc_url($shop_url) . '">Danh mục sơn</a>' . $content . '</li>';
        set_transient($cache_key, $html, 12 * HOUR_IN_SECONDS);
        return $html;
    }
}

if (!function_exists('my_theme_render_brand_menu_item')) {
    function my_theme_render_brand_menu_item()
    {
        if (!taxonomy_exists('pa_brand')) {
            return '';
        }

        $cache_key = 'my_theme_brand_menu_html_v2';
        $cached_html = get_transient($cache_key);
        if (is_string($cached_html) && $cached_html !== '') {
            return $cached_html;
        }

        $visible_product_ids = my_theme_get_catalog_visible_product_ids(false);
        if (empty($visible_product_ids)) {
            return '';
        }

        $terms = get_terms([
            'taxonomy'   => 'pa_brand',
            'hide_empty' => true,
            'orderby'    => 'name',
            'order'      => 'ASC',
            'number'     => 12,
            'object_ids' => $visible_product_ids,
        ]);
        if (is_wp_error($terms) || empty($terms)) {
            return '';
        }

        $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop');
        $html = '<li class="menu-item menu-item-brands menu-item-has-children"><a href="' . esc_url($shop_url) . '">Thương hiệu</a><ul class="sub-menu">';
        $html .= '<li class="menu-item menu-item-brand"><a href="' . esc_url($shop_url) . '">Tất cả thương hiệu</a></li>';
        foreach ($terms as $term) {
            if (!$term instanceof WP_Term || empty($term->slug)) {
                continue;
            }
            $brand_url = add_query_arg('brand', sanitize_title((string) $term->slug), $shop_url);
            $html .= '<li class="menu-item menu-item-brand"><a href="' . esc_url($brand_url) . '">' . esc_html($term->name) . '</a></li>';
        }
        $html .= '</ul></li>';

        set_transient($cache_key, $html, 12 * HOUR_IN_SECONDS);
        return $html;
    }
}

if (!function_exists('my_theme_flush_catalog_menu_cache')) {
    function my_theme_flush_catalog_menu_cache()
    {
        delete_transient('my_theme_catalog_menu_html_v1');
        delete_transient('my_theme_catalog_menu_html_v2');
        delete_transient('my_theme_catalog_menu_html_v3');
        delete_transient('my_theme_brand_menu_html_v1');
        delete_transient('my_theme_brand_menu_html_v2');
    }
}

add_action('created_product_cat', 'my_theme_flush_catalog_menu_cache');
add_action('edited_product_cat', 'my_theme_flush_catalog_menu_cache');
add_action('delete_product_cat', 'my_theme_flush_catalog_menu_cache');
add_action('created_pa_brand', 'my_theme_flush_catalog_menu_cache');
add_action('edited_pa_brand', 'my_theme_flush_catalog_menu_cache');
add_action('delete_pa_brand', 'my_theme_flush_catalog_menu_cache');

if (!function_exists('my_theme_flush_product_cache_fragments')) {
    function my_theme_flush_product_cache_fragments($post_id = 0)
    {
        if ($post_id && get_post_type($post_id) !== 'product') {
            return;
        }
        delete_transient('my_theme_home_featured_candidate_ids_v1');
        delete_transient('my_theme_catalog_visible_ids_shop_v1');
        delete_transient('my_theme_catalog_visible_ids_priced_v1');
        my_theme_flush_catalog_menu_cache();
        // Related cache keys are per product and short-lived; version bump invalidates all.
        update_option('my_theme_related_cache_version', (string) time(), false);
    }
}

add_action('save_post_product', 'my_theme_flush_product_cache_fragments');
add_action('deleted_post', function ($post_id) {
    if (get_post_type($post_id) === 'product') {
        my_theme_flush_product_cache_fragments((int) $post_id);
    }
});

// Bổ sung menu chuyên nghiệp: nút tính sơn + danh mục sản phẩm đa cấp.
add_filter('wp_nav_menu_items', function ($items, $args) {
    if (empty($args->theme_location) || $args->theme_location !== 'primary' || is_admin()) {
        return $items;
    }

    $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop');
    $calc_url = (function_exists('is_product') && is_product()) ? get_permalink() . '#tinh-son' : add_query_arg('q', 'son', $shop_url);
    $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/gio-hang');
    $checkout_url = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/thanh-toan');

    if (strpos($items, 'menu-item-calculator') === false) {
        $items .= '<li class="menu-item menu-item-calculator"><a href="' . esc_url($calc_url) . '">Tính sơn</a></li>';
    }

    if (strpos($items, 'menu-item-brands') === false) {
        $items .= my_theme_render_brand_menu_item();
    }

    if (strpos($items, 'menu-item-catalog') === false) {
        $items .= my_theme_render_product_category_menu_item();
    }

    if (strpos($items, 'menu-item-cart') === false) {
        $items .= '<li class="menu-item menu-item-cart"><a href="' . esc_url($cart_url) . '">Giỏ hàng</a></li>';
    }

    if (strpos($items, 'menu-item-checkout') === false) {
        $items .= '<li class="menu-item menu-item-checkout"><a href="' . esc_url($checkout_url) . '">Thanh toán</a></li>';
    }

    return $items;
}, 20, 2);

// Fallback menu if user chưa cấu hình.
function my_theme_fallback_menu() {
    $menu = [
        ['label' => 'Trang chủ', 'url' => '#top'],
        ['label' => 'Cửa hàng', 'url' => wc_get_page_permalink('shop')],
        ['label' => 'Tính sơn', 'url' => add_query_arg('q', 'son', wc_get_page_permalink('shop'))],
        ['label' => 'Giới thiệu', 'url' => home_url('/gioi-thieu')],
        ['label' => 'Giá thợ', 'url' => home_url('/gia-tho')],
        ['label' => 'Liên hệ', 'url' => home_url('/lien-he')],
    ];
    echo '<ul id="primary-menu-list" class="menu main-menu">';
    foreach ($menu as $item) {
        echo '<li><a href="' . esc_url($item['url']) . '">' . esc_html($item['label']) . '</a></li>';
    }
    echo '</ul>';
}

// Tự tạo các trang Liên hệ / Chính sách nếu chưa có.
add_action('after_switch_theme', function () {
    $pages = [
        ['title' => 'Liên hệ', 'slug' => 'lien-he', 'content' => 'Liên hệ Đại lý Sơn Phát Tấn để nhận báo giá và tư vấn kỹ thuật.'],
        ['title' => 'Chính sách đổi trả', 'slug' => 'chinh-sach-doi-tra', 'content' => 'Xem điều kiện đổi trả và quy trình hỗ trợ.'],
        ['title' => 'Câu hỏi thường gặp', 'slug' => 'faq', 'content' => 'Tổng hợp câu hỏi thường gặp về đặt hàng và thi công.'],
        ['title' => 'Hướng dẫn mua hàng', 'slug' => 'huong-dan-mua-hang', 'content' => 'Hướng dẫn đặt hàng nhanh tại Đại lý Sơn Phát Tấn.'],
        ['title' => 'Giới thiệu đại lý', 'slug' => 'gioi-thieu', 'content' => 'Thông tin về Đại lý Sơn Phát Tấn, kinh nghiệm và khu vực phục vụ.'],
        ['title' => 'Vận chuyển & giao hàng', 'slug' => 'van-chuyen-giao-hang', 'content' => 'Thông tin phạm vi giao hàng, thời gian và phí vận chuyển.'],
        ['title' => 'Giá thợ / công trình', 'slug' => 'gia-tho', 'content' => 'Ưu đãi và điều kiện áp dụng giá thợ, giá công trình.'],
    ];
    foreach ($pages as $p) {
        if (!get_page_by_path($p['slug'])) {
            wp_insert_post([
                'post_title'   => $p['title'],
                'post_name'    => $p['slug'],
                'post_content' => $p['content'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ]);
        }
    }
});

// Mô tả ngắn cho danh mục sản phẩm (shop/category).
function my_theme_get_category_intro($cat_id = 0) {
    $default = 'Chọn sơn chính hãng theo bề mặt và mục đích sử dụng. Tư vấn định mức m² miễn phí.';
    $term = null;
    if (function_exists('is_product_category') && is_product_category()) {
        $term = get_queried_object();
    }
    if (!$term && $cat_id) {
        $term = get_term($cat_id, 'product_cat');
    }
    if ($term && !is_wp_error($term)) {
        if (!empty($term->description)) {
            return wp_strip_all_tags($term->description);
        }
        $slug = $term->slug;
        $map = [
            'noi-that' => 'Phù hợp phòng khách, phòng ngủ, văn phòng. Ưu tiên sơn mùi nhẹ, dễ lau chùi.',
            'ngoai-that' => 'Phù hợp mặt tiền, tường ngoài, ban công. Ưu tiên chống tia UV và bám bẩn.',
            'chong-tham' => 'Phù hợp sân thượng, mái, tường đứng. Yêu cầu lớp màng chống thấm đàn hồi.',
            'bot-tra' => 'Phù hợp bả phẳng tường trước khi sơn phủ, tăng độ mịn và bám dính.',
            'bot-ba' => 'Phù hợp bả phẳng tường trước khi sơn phủ, tăng độ mịn và bám dính.',
            'matit' => 'Phù hợp bả phẳng tường trước khi sơn phủ, tăng độ mịn và bám dính.',
        ];
        foreach ($map as $key => $text) {
            if (strpos($slug, $key) !== false) {
                return $text;
            }
        }
    }
    return $default;
}

function my_theme_render_paint_calculator() {
    if (!function_exists('is_product') || !is_product()) {
        return;
    }
    get_template_part('template-parts/paint-calculator');
}

// Dịch nhanh một số chuỗi WooCommerce (giới hạn domain woocommerce).
add_filter('gettext', function ($translated_text, $text, $domain) {
    if ($domain !== 'woocommerce') {
        return $translated_text;
    }
    $map = [
        'Proceed to checkout' => 'Thanh toán',
        'Proceed to Checkout' => 'Thanh toán',
        'Add a coupon' => 'Nhập mã giảm giá',
        'Remove item' => 'Xóa sản phẩm',
        'Cart totals' => 'Cộng giỏ hàng',
        'Subtotal' => 'Tạm tính',
        'Total' => 'Tổng',
        'View cart' => 'Xem giỏ hàng',
        'Checkout' => 'Thanh toán',
        'Cart' => 'Giỏ hàng',
    ];
    return $map[$text] ?? $translated_text;
}, 20, 3);

// Ép nút "Proceed to checkout" hiển thị tiếng Việt bằng hook WooCommerce.
add_action('init', function () {
    remove_action('woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20);
    add_action('woocommerce_proceed_to_checkout', 'my_theme_button_proceed_to_checkout', 20);
});

function my_theme_button_proceed_to_checkout() {
    if (!function_exists('wc_get_checkout_url')) {
        return;
    }
    $checkout_url = wc_get_checkout_url();
    echo '<a href="' . esc_url($checkout_url) . '" class="checkout-button button alt wc-forward">' . esc_html('Thanh toán') . '</a>';
}

// Keep checkout page accessible even when cart is empty so menu "Thanh toán" is never confusing.
add_filter('woocommerce_checkout_redirect_empty_cart', '__return_false');

// Chỉ chấp nhận thanh toán chuyển khoản 100% (BACS).
add_filter('woocommerce_available_payment_gateways', function ($gateways) {
    if (is_admin()) {
        return $gateways;
    }
    if (isset($gateways['bacs'])) {
        foreach ($gateways as $id => $gateway) {
            if ($id !== 'bacs') {
                unset($gateways[$id]);
            }
        }
    }
    return $gateways;
});

// Thông báo rõ ràng ở giỏ hàng và thanh toán.
add_action('woocommerce_before_cart', function () {
    if (function_exists('wc_print_notice')) {
        wc_print_notice('Đơn hàng chỉ chấp nhận thanh toán chuyển khoản 100% trước khi giao.', 'notice');
    }
});
add_action('woocommerce_before_checkout_form', function () {
    if (function_exists('wc_print_notice')) {
        wc_print_notice('Vui lòng chuyển khoản 100% trước khi giao hàng.', 'notice');
    }
}, 5);

// Đảm bảo người dùng đăng ký chỉ có quyền khách hàng, admin mới có quyền chỉnh sửa.
add_action('user_register', function ($user_id) {
    $user = get_user_by('id', $user_id);
    if (!$user) {
        return;
    }
    if (user_can($user, 'manage_options')) {
        return;
    }
    $role = get_role('customer') ? 'customer' : 'subscriber';
    $user->set_role($role);
});

// Chặn người dùng không phải admin vào wp-admin, đưa về trang tài khoản.
add_action('init', function () {
    if (is_admin() && !defined('DOING_AJAX') && is_user_logged_in()) {
        if (!current_user_can('manage_options')) {
            $account = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/');
            wp_safe_redirect($account);
            exit;
        }
    }
});

// Ẩn thanh admin bar cho khách hàng.
add_filter('show_admin_bar', function ($show) {
    if (!is_user_logged_in()) {
        return $show;
    }
    return current_user_can('manage_options') ? $show : false;
});

// Bật đăng ký tài khoản và ưu tiên trang "Tài khoản" của WooCommerce.
add_filter('pre_option_users_can_register', function ($value) {
    return 1;
});
add_filter('pre_option_woocommerce_enable_myaccount_registration', function ($value) {
    return 'yes';
});
add_filter('woocommerce_enable_myaccount_registration', '__return_true');

// Nếu vào wp-login?action=register thì chuyển về trang tài khoản.
add_action('login_init', function () {
    if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'register') {
        if (function_exists('wc_get_page_permalink')) {
            $account = wc_get_page_permalink('myaccount');
            if (!empty($account)) {
                wp_safe_redirect($account);
                exit;
            }
        }
    }
});

// Luôn hiển thị trường mật khẩu khi đăng ký (không gửi link qua email).
add_filter('pre_option_woocommerce_registration_generate_password', function () {
    return 'no';
});

// Tự tạo email nội bộ nếu người dùng không nhập email.
add_action('wp_loaded', function () {
    if (empty($_POST['register']) || !isset($_POST['email'])) {
        return;
    }
    $email = trim((string) wp_unslash($_POST['email']));
    if ($email !== '') {
        return;
    }
    $phone_raw = isset($_POST['account_phone']) ? wp_unslash($_POST['account_phone']) : '';
    $digits = preg_replace('/\D+/', '', (string) $phone_raw);
    $seed = $digits !== '' ? $digits : 'khach';
    $suffix = wp_generate_password(4, false, false);
    $generated = sanitize_email($seed . '-' . $suffix . '@noemail.local');
    while (email_exists($generated)) {
        $suffix = wp_generate_password(4, false, false);
        $generated = sanitize_email($seed . '-' . $suffix . '@noemail.local');
    }
    $_POST['email'] = $generated;
}, 5);

// Bắt buộc họ tên, số điện thoại, địa chỉ khi đăng ký.
add_filter('woocommerce_process_registration_errors', function ($errors, $username, $password, $email) {
    $full_name = isset($_POST['account_full_name']) ? trim((string) wp_unslash($_POST['account_full_name'])) : '';
    $phone = isset($_POST['account_phone']) ? trim((string) wp_unslash($_POST['account_phone'])) : '';
    $address = isset($_POST['account_address']) ? trim((string) wp_unslash($_POST['account_address'])) : '';

    if ($full_name === '') {
        $errors->add('account_full_name_error', 'Vui lòng nhập họ và tên.');
    }
    if ($phone === '') {
        $errors->add('account_phone_error', 'Vui lòng nhập số điện thoại.');
    } else {
        $digits = preg_replace('/\D+/', '', $phone);
        if (strlen($digits) < 9) {
            $errors->add('account_phone_error', 'Số điện thoại chưa hợp lệ.');
        }
    }
    if ($address === '') {
        $errors->add('account_address_error', 'Vui lòng nhập địa chỉ nhận hàng.');
    }
    return $errors;
}, 10, 4);

// Lưu thông tin đăng ký vào hồ sơ khách hàng.
add_action('woocommerce_created_customer', function ($customer_id) {
    $full_name = isset($_POST['account_full_name']) ? trim((string) wp_unslash($_POST['account_full_name'])) : '';
    $phone = isset($_POST['account_phone']) ? trim((string) wp_unslash($_POST['account_phone'])) : '';
    $address = isset($_POST['account_address']) ? trim((string) wp_unslash($_POST['account_address'])) : '';

    if ($full_name !== '') {
        update_user_meta($customer_id, 'first_name', $full_name);
        update_user_meta($customer_id, 'billing_first_name', $full_name);
    }
    if ($phone !== '') {
        update_user_meta($customer_id, 'billing_phone', $phone);
    }
    if ($address !== '') {
        update_user_meta($customer_id, 'billing_address_1', $address);
        update_user_meta($customer_id, 'shipping_address_1', $address);
    }
    $user = get_user_by('id', $customer_id);
    if ($user && !empty($user->user_email)) {
        update_user_meta($customer_id, 'billing_email', $user->user_email);
    }
}, 10, 1);

// --- WooCommerce: nút chọn dung tích/quy cách & giữ 1 ảnh chính ---
// Biến dropdown variation thành nút (áp dụng mọi attribute trên trang sản phẩm).
add_action('wp_enqueue_scripts', function () {
    if (!function_exists('is_product') || !is_product()) {
        return;
    }

    $js = <<<'JS'
    jQuery(function($){
      $('.variations_form').each(function(){
        $(this).find('select[data-attribute_name]').each(function(){
          const $sel = $(this);
          if ($sel.data('wvb-ready')) return;
          $sel.data('wvb-ready', true).hide();

          const selected = $sel.val();
          const $wrap = $('<div class="wvb-attr-buttons" role="group"></div>');
          $sel.find('option').each(function(){
            const v = $(this).val(); if(!v) return; // bỏ option trống
            const txt = $(this).text();
            const $btn = $('<button type="button" class="wvb-attr-btn" aria-pressed="false" />').text(txt).attr('data-value', v);
            if (v === selected) $btn.addClass('is-active');
            if (v === selected) $btn.attr('aria-pressed', 'true');
            $btn.on('click', function(){
              $wrap.find('.wvb-attr-btn').removeClass('is-active');
              $wrap.find('.wvb-attr-btn').attr('aria-pressed', 'false');
              $(this).addClass('is-active');
              $(this).attr('aria-pressed', 'true');
              $sel.val(v).trigger('change');
            });
            $wrap.append($btn);
          });
          $sel.after($wrap);
        });
      });
    });
    JS;

    wp_add_inline_script('wc-add-to-cart-variation', $js);
}, 20);

// Không đổi ảnh khi chọn variation (giữ 1 ảnh chính của sản phẩm).
add_filter('woocommerce_available_variation', function ($data) {
    $data['image'] = false;
    return $data;
});

// Thêm trường nhập nhanh dung tích / khối lượng để hiển thị ngoài frontend (cho sản phẩm đơn giản).
add_action('woocommerce_product_options_general_product_data', function () {
    echo '<div class="options_group">';
    woocommerce_wp_text_input([
        'id' => '_display_capacity_list',
        'label' => 'Dung tích hiển thị',
        'placeholder' => '1L | 5L | 15L',
        'desc_tip' => true,
        'description' => 'Nhập danh sách dung tích (dùng | hoặc ,). Có thể kèm giá: 1L:99000 | 5L:450000.',
    ]);
    woocommerce_wp_text_input([
        'id' => '_display_weight_list',
        'label' => 'Khối lượng hiển thị',
        'placeholder' => '40kg',
        'desc_tip' => true,
        'description' => 'Nhập khối lượng hiển thị dưới giá (vd: 40kg hoặc 1kg | 5kg).',
    ]);
    woocommerce_wp_text_input([
        'id' => '_capacity_price_map',
        'label' => 'Bảng giá theo dung tích',
        'placeholder' => '1L:99000 | 5L:450000 | 15L:1200000',
        'desc_tip' => true,
        'description' => 'Dạng capacity:price, cách nhau bởi | hoặc ,. Tự áp dụng cho sản phẩm đơn giản; ưu tiên dùng biến thể nếu đã tạo.',
    ]);
    echo '</div>';
});
add_action('woocommerce_admin_process_product_object', function ($product) {
    $cap = isset($_POST['_display_capacity_list']) ? wc_clean(wp_unslash($_POST['_display_capacity_list'])) : '';
    $wgt = isset($_POST['_display_weight_list']) ? wc_clean(wp_unslash($_POST['_display_weight_list'])) : '';
    $cap_price_map = isset($_POST['_capacity_price_map']) ? wc_clean(wp_unslash($_POST['_capacity_price_map'])) : '';
    if ($cap !== '') {
        $product->update_meta_data('_display_capacity_list', $cap);
    } else {
        $product->delete_meta_data('_display_capacity_list');
    }
    if ($wgt !== '') {
        $product->update_meta_data('_display_weight_list', $wgt);
    } else {
        $product->delete_meta_data('_display_weight_list');
    }
    if ($cap_price_map !== '') {
        $product->update_meta_data('_capacity_price_map', $cap_price_map);
    } else {
        $product->delete_meta_data('_capacity_price_map');
    }
});

// Preset chips cho ô nhập dung tích/khối lượng (admin product edit).
add_action('admin_footer', function () {
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'product') {
        return;
    }
    ?>
    <style>
      .capacity-map-builder { margin-top: 6px; border: 1px solid #e3e7f0; border-radius: 6px; padding: 10px; background: #f8faff; }
      .capacity-map-header { display:flex; gap:10px; font-weight:700; color:#0b172a; margin-bottom:6px; }
      .capacity-map-rows { display:flex; flex-direction:column; gap:6px; }
      .capacity-map-row { display:grid; grid-template-columns: 1fr 1fr auto; gap:8px; align-items:center; }
      .capacity-map-row input { width:100%; }
      .capacity-map-row .button-link-delete { color:#c00; }
      .capacity-map-actions { margin-top:8px; }
    </style>
    <script>
      jQuery(function($){
        // Builder cho bảng giá dung tích _capacity_price_map
        const $mapInput = $('#_capacity_price_map');
        if ($mapInput.length && !$mapInput.data('codex-ready')) {
          $mapInput.data('codex-ready', true);
          const parseMap = (str) => {
            if (!str) return [];
            str = str.replace(/;/g,'|').replace(/\n/g,'|');
            return str.split(/[|,]/).map(s=>s.trim()).filter(Boolean).map(pair=>{
              if (!pair.includes(':')) return null;
              const [c,p] = pair.split(':').map(v=>v.trim());
              if (!c || !p) return null;
              return {cap:c, price:p};
            }).filter(Boolean);
          };
          const serialize = (rows) => rows
            .filter(r=>r.cap && r.price)
            .map(r=>`${r.cap}:${r.price}`)
            .join(' | ');

          const rows = parseMap($mapInput.val());

          const $builder = $(`
            <div class="capacity-map-builder">
              <div class="capacity-map-header">
                <div>Dung tích</div>
                <div>Giá</div>
                <div></div>
              </div>
              <div class="capacity-map-rows"></div>
              <div class="capacity-map-actions">
                <button type="button" class="button add-row">+ Thêm dòng</button>
              </div>
            </div>
          `);

          const $rows = $builder.find('.capacity-map-rows');
          const addRow = (cap='', price='') => {
            const $row = $(`
              <div class="capacity-map-row">
                <input type="text" class="cap" placeholder="5L" value="${cap}">
                <input type="text" class="price" placeholder="450000" value="${price}">
                <button type="button" class="button button-link-delete">Xóa</button>
              </div>
            `);
            $row.on('click', '.button-link-delete', function(){
              $row.remove();
              sync();
            });
            $row.find('input').on('input', sync);
            $rows.append($row);
          };

          const sync = () => {
            const data = [];
            $rows.find('.capacity-map-row').each(function(){
              const cap = $(this).find('.cap').val().trim();
              const price = $(this).find('.price').val().trim();
              if (cap && price) data.push({cap, price});
            });
            $mapInput.val(serialize(data));
          };

          if (rows.length) {
            rows.forEach(r=>addRow(r.cap, r.price));
          } else {
            addRow();
          }

          $builder.find('.add-row').on('click', function(){
            addRow();
          });

          $mapInput.after($builder);
          $mapInput.attr('placeholder', '1L:99000 | 5L:450000 | 15L:1200000');
        }
      });
    </script>
    <?php
});

// Lấy giá trị attribute/meta cho dung tích và khối lượng (ưu tiên attribute).
function my_theme_extract_attr_values($product, $slugs) {
    $values = [];
    foreach ($slugs as $slug) {
        // Lấy terms taxonomy nếu có
        if (taxonomy_exists($slug)) {
            $terms = wc_get_product_terms($product->get_id(), $slug, ['fields' => 'names']);
            if (!empty($terms)) {
                $values = array_merge($values, $terms);
            }
        }
        // Lấy giá trị chuỗi attribute của sản phẩm
        $raw = $product->get_attribute($slug);
        if ($raw) {
            $sep = strpos($raw, '|') !== false ? '|' : (strpos($raw, ',') !== false ? ',' : ' ');
            $parts = array_map('trim', explode($sep, str_replace(['/', ';'], $sep, $raw)));
            $values = array_merge($values, $parts);
        }
    }

    // Nếu là variable product, lấy danh sách options của variation attributes
    if ($product->is_type('variable')) {
        $var_attrs = $product->get_variation_attributes();
        foreach ($slugs as $slug) {
            $key = 'attribute_' . $slug;
            if (!empty($var_attrs[$key])) {
                $values = array_merge($values, array_map('wc_clean', (array) $var_attrs[$key]));
            }
        }
    }

    $values = array_unique(array_filter(array_map('wp_strip_all_tags', $values)));
    return array_values($values);
}

function my_theme_parse_pack_label($raw_label) {
    $label = trim(wp_strip_all_tags((string) $raw_label));
    if ($label === '') {
        return null;
    }

    $ascii = strtolower(remove_accents($label));
    $ascii = preg_replace('/\s+/', ' ', trim((string) $ascii));
    if (!preg_match('/(\d+(?:[.,]\d+)?)\s*(kg|l|lit|liter|litre)\b/i', $ascii, $m)) {
        return null;
    }

    $value = (float) str_replace(',', '.', $m[1]);
    if ($value <= 0) {
        return null;
    }

    $unit_raw = strtolower($m[2]);
    $unit = ($unit_raw === 'kg') ? 'kg' : 'L';
    $value_text = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');

    return [
        'value' => $value,
        'unit'  => $unit,
        'label' => $value_text . ($unit === 'kg' ? 'kg' : 'L'),
    ];
}

function my_theme_sort_pack_labels($labels, $unit_filter = '') {
    $rows = [];
    foreach ((array) $labels as $raw_label) {
        $parsed = my_theme_parse_pack_label($raw_label);
        if (!$parsed) {
            continue;
        }
        if ($unit_filter !== '' && $parsed['unit'] !== $unit_filter) {
            continue;
        }
        $rows[$parsed['label']] = $parsed;
    }

    if (empty($rows)) {
        return [];
    }

    $rows = array_values($rows);
    usort($rows, function ($a, $b) {
        $unit_rank = ['L' => 0, 'kg' => 1];
        $ra = $unit_rank[$a['unit']] ?? 9;
        $rb = $unit_rank[$b['unit']] ?? 9;
        if ($ra !== $rb) {
            return ($ra < $rb) ? -1 : 1;
        }
        if ((float) $a['value'] === (float) $b['value']) {
            return strcmp($a['label'], $b['label']);
        }
        return ((float) $a['value'] < (float) $b['value']) ? -1 : 1;
    });

    return array_values(array_map(function ($row) {
        return $row['label'];
    }, $rows));
}

function my_theme_is_putty_product($product) {
    if (!$product instanceof WC_Product) {
        return false;
    }

    $title = my_theme_normalize_search_text($product->get_name());
    $weight_only_keywords = [
        'bot tret',
        'putty',
        'keo cha ron',
        'keo dan gach',
        'vua kho',
        'grout',
        'mortar',
    ];
    foreach ($weight_only_keywords as $keyword) {
        if (strpos($title, $keyword) !== false) {
            return true;
        }
    }

    $terms = wp_get_post_terms($product->get_id(), 'product_cat');
    if (is_wp_error($terms) || empty($terms)) {
        return false;
    }
    foreach ($terms as $term) {
        $hay = my_theme_normalize_search_text($term->name . ' ' . $term->slug);
        foreach ($weight_only_keywords as $keyword) {
            if (strpos($hay, $keyword) !== false) {
                return true;
            }
        }
    }
    return false;
}

if (!function_exists('my_theme_extract_pack_price_map_from_text')) {
    function my_theme_extract_pack_price_map_from_text($raw_text, $is_putty = false)
    {
        $text = (string) $raw_text;
        if ($text === '') {
            return [];
        }

        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = remove_accents(wp_strip_all_tags($text));
        $text = preg_replace('/\s+/', ' ', trim((string) $text));
        if ($text === '') {
            return [];
        }

        $map = [];
        $patterns = [
            // e.g. 5L: 592,000 | 18 lit - 1,943,500 | 5kg 125,000
            '/(\d+(?:[.,]\d+)?)\s*(l|lit|liter|kg)\s*[:\-]?\s*([\d][\d\.,]{3,})/i',
            // e.g. 592,000d / 5L
            '/([\d][\d\.,]{3,})\s*(?:d|vnd|dong)\s*(?:\/|cho|for)?\s*(\d+(?:[.,]\d+)?)\s*(l|lit|liter|kg)\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (!preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
                continue;
            }

            foreach ($matches as $row) {
                if (!is_array($row) || count($row) < 4) {
                    continue;
                }

                if ($pattern === $patterns[0]) {
                    $pack_raw = $row[1] . $row[2];
                    $price_raw = $row[3];
                } else {
                    $pack_raw = $row[2] . $row[3];
                    $price_raw = $row[1];
                }

                $parsed_pack = my_theme_parse_pack_label($pack_raw);
                if (!$parsed_pack) {
                    continue;
                }
                if ($is_putty && $parsed_pack['unit'] !== 'kg') {
                    continue;
                }

                $price_digits = preg_replace('/\D+/', '', (string) $price_raw);
                if ($price_digits === '') {
                    continue;
                }
                $price_val = (float) $price_digits;
                // Guard against accidental non-price numbers.
                if ($price_val < 1000) {
                    continue;
                }

                $map[$parsed_pack['label']] = $price_val;
            }
        }

        if (empty($map)) {
            return [];
        }

        uksort($map, function ($a, $b) {
            $pa = my_theme_parse_pack_label($a);
            $pb = my_theme_parse_pack_label($b);
            if (!$pa || !$pb) {
                return strcmp($a, $b);
            }
            $unit_rank = ['L' => 0, 'kg' => 1];
            $ra = $unit_rank[$pa['unit']] ?? 9;
            $rb = $unit_rank[$pb['unit']] ?? 9;
            if ($ra !== $rb) {
                return ($ra < $rb) ? -1 : 1;
            }
            if ((float) $pa['value'] === (float) $pb['value']) {
                return strcmp($a, $b);
            }
            return ((float) $pa['value'] < (float) $pb['value']) ? -1 : 1;
        });

        return $map;
    }
}

if (!function_exists('my_theme_fetch_pack_price_map_from_source_url')) {
    function my_theme_extract_pack_price_map_from_source_html($raw_html, $is_putty = false)
    {
        $html = (string) $raw_html;
        if ($html === '') {
            return [];
        }

        if (!preg_match('/<script[^>]*class=(["\'])js-price-list\1[^>]*>\s*(\[[\s\S]*?\])\s*<\/script>/i', $html, $matches)) {
            return [];
        }

        $rows = json_decode(trim((string) $matches[2]), true);
        if (!is_array($rows)) {
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $label = isset($row['label']) ? wp_strip_all_tags((string) $row['label']) : '';
            if ($label === '' && isset($row['volume'])) {
                $volume = (float) $row['volume'];
                if ($volume > 0) {
                    $unit = $is_putty ? 'kg' : 'L';
                    $normalized_volume = rtrim(rtrim(number_format($volume, 2, '.', ''), '0'), '.');
                    $label = $normalized_volume . $unit;
                }
            }

            $parsed_pack = my_theme_parse_pack_label($label);
            if (!$parsed_pack) {
                continue;
            }
            if ($is_putty && $parsed_pack['unit'] !== 'kg') {
                continue;
            }

            $price_value = isset($row['noCurrencyPrice']) ? (float) $row['noCurrencyPrice'] : 0.0;
            if ($price_value <= 0 && !empty($row['price'])) {
                $digits = preg_replace('/\D+/', '', (string) $row['price']);
                if ($digits !== '') {
                    $price_value = (float) $digits;
                }
            }
            if ($price_value <= 0) {
                continue;
            }

            $map[$parsed_pack['label']] = $price_value;
        }

        if (empty($map)) {
            return [];
        }

        uksort($map, function ($a, $b) {
            $pa = my_theme_parse_pack_label($a);
            $pb = my_theme_parse_pack_label($b);
            if (!$pa || !$pb) {
                return strcmp($a, $b);
            }
            $unit_rank = ['L' => 0, 'kg' => 1];
            $ra = $unit_rank[$pa['unit']] ?? 9;
            $rb = $unit_rank[$pb['unit']] ?? 9;
            if ($ra !== $rb) {
                return ($ra < $rb) ? -1 : 1;
            }
            if ((float) $pa['value'] === (float) $pb['value']) {
                return strcmp($a, $b);
            }
            return ((float) $pa['value'] < (float) $pb['value']) ? -1 : 1;
        });

        return $map;
    }
}

if (!function_exists('my_theme_fetch_pack_price_map_from_source_url')) {
    function my_theme_fetch_pack_price_map_from_source_url($source_url, $is_putty = false)
    {
        $url = esc_url_raw((string) $source_url);
        if ($url === '' || !wp_http_validate_url($url)) {
            return [];
        }

        $host = (string) wp_parse_url($url, PHP_URL_HOST);
        if ($host === '') {
            return [];
        }

        // Restrict crawling to known official sources.
        if (stripos($host, 'dulux.vn') === false && stripos($host, 'akzonobel.com') === false) {
            return [];
        }

        $cache_key = 'my_theme_src_price_' . md5($url . ($is_putty ? '|kg' : '|all'));
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        $response = wp_remote_get($url, [
            'timeout' => 20,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; PaintStoreBot/1.0)',
            ],
        ]);
        if (is_wp_error($response)) {
            set_transient($cache_key, [], 6 * HOUR_IN_SECONDS);
            return [];
        }

        $body = (string) wp_remote_retrieve_body($response);
        if ($body === '') {
            set_transient($cache_key, [], 6 * HOUR_IN_SECONDS);
            return [];
        }

        $map = my_theme_extract_pack_price_map_from_text($body, $is_putty);
        if (empty($map)) {
            $map = my_theme_extract_pack_price_map_from_source_html($body, $is_putty);
        }
        set_transient($cache_key, $map, 24 * HOUR_IN_SECONDS);
        return $map;
    }
}

// Parse map size:price (number) from _capacity_price_map or legacy _display_capacity_list.
function my_theme_parse_capacity_price_map($product) {
    $map_raw = $product->get_meta('_capacity_price_map');
    if (!$map_raw) {
        $map_raw = $product->get_meta('_display_capacity_list');
    }
    if (!$map_raw) {
        return [];
    }

    $map_raw = str_replace([';', "\n"], '|', $map_raw);
    $pairs = preg_split('/[|,]/', $map_raw);
    $map = [];
    foreach ($pairs as $pair) {
        $pair = trim((string) $pair);
        if ($pair === '' || strpos($pair, ':') === false) {
            continue;
        }
        [$size_raw, $price_raw] = array_map('trim', explode(':', $pair, 2));
        if ($size_raw === '' || $price_raw === '') {
            continue;
        }

        $parsed_size = my_theme_parse_pack_label($size_raw);
        if (!$parsed_size) {
            continue;
        }
        $digits = preg_replace('/\D+/', '', (string) $price_raw);
        if ($digits === '') {
            continue;
        }
        $price_num = (float) $digits;
        if ($price_num <= 0) {
            continue;
        }
        $map[$parsed_size['label']] = $price_num;
    }

    if (empty($map)) {
        return [];
    }

    uksort($map, function ($a, $b) {
        $pa = my_theme_parse_pack_label($a);
        $pb = my_theme_parse_pack_label($b);
        if (!$pa || !$pb) {
            return strcmp($a, $b);
        }
        $unit_rank = ['L' => 0, 'kg' => 1];
        $ra = $unit_rank[$pa['unit']] ?? 9;
        $rb = $unit_rank[$pb['unit']] ?? 9;
        if ($ra !== $rb) {
            return ($ra < $rb) ? -1 : 1;
        }
        if ((float) $pa['value'] === (float) $pb['value']) {
            return strcmp($a, $b);
        }
        return ((float) $pa['value'] < (float) $pb['value']) ? -1 : 1;
    });

    return $map;
}

function my_theme_get_product_pack_groups($product) {
    if (!$product instanceof WC_Product) {
        return ['capacity' => [], 'weight' => [], 'is_putty' => false];
    }

    static $cache = [];
    $cache_key = (int) $product->get_id();
    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }

    $capacity_slugs = ['pa_dung-tich', 'pa_dung_tich', 'pa_dungtich', 'dung-tich', 'dung_tich', 'dungtich'];
    $weight_slugs   = ['pa_khoi-luong', 'pa_khoi_luong', 'pa_khoiluong', 'khoi-luong', 'khoi_luong', 'khoiluong', 'trong-luong', 'trong_luong', 'trongluong'];

    $raw_labels = [];
    $raw_labels = array_merge($raw_labels, my_theme_extract_attr_values($product, $capacity_slugs));
    $raw_labels = array_merge($raw_labels, my_theme_extract_attr_values($product, $weight_slugs));

    foreach (['_display_capacity_list', '_display_weight_list'] as $meta_key) {
        $meta_raw = $product->get_meta($meta_key);
        if (!$meta_raw) {
            continue;
        }
        $meta_raw = str_replace([';', "\n"], '|', $meta_raw);
        $parts = preg_split('/[|,]/', $meta_raw);
        foreach ((array) $parts as $part) {
            $part = trim((string) $part);
            if ($part === '') {
                continue;
            }
            if (strpos($part, ':') !== false) {
                [$size_name] = array_map('trim', explode(':', $part, 2));
                if ($size_name !== '') {
                    $raw_labels[] = $size_name;
                }
            } else {
                $raw_labels[] = $part;
            }
        }
    }

    $price_map = my_theme_parse_capacity_price_map($product);
    if (!empty($price_map)) {
        $raw_labels = array_merge($raw_labels, array_keys($price_map));
    }

    $numeric_weight = $product->get_weight();
    if ($numeric_weight !== '') {
        $raw_labels[] = $numeric_weight . 'kg';
    }

    $raw_labels = array_values(array_unique(array_filter(array_map('trim', $raw_labels))));
    $is_putty = my_theme_is_putty_product($product);

    $capacity = my_theme_sort_pack_labels($raw_labels, 'L');
    $weight = my_theme_sort_pack_labels($raw_labels, 'kg');
    $has_map = !empty($price_map);
    $category_slugs = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'slugs']);
    if (is_wp_error($category_slugs) || empty($category_slugs)) {
        $category_slugs = [];
    }

    $map_capacity = [];
    $map_weight = [];
    if ($has_map) {
        $map_labels = array_keys($price_map);
        $map_capacity = my_theme_sort_pack_labels($map_labels, 'L');
        $map_weight = my_theme_sort_pack_labels($map_labels, 'kg');
    }

    if ($is_putty) {
        $capacity = [];
        if (!empty($map_weight)) {
            $weight = $map_weight;
        }
    } else {
        if (!empty($map_capacity)) {
            $capacity = $map_capacity;
        }
        if (!empty($map_weight)) {
            $weight = $map_weight;
        } elseif (!empty($map_capacity)) {
            // Khi đã có bảng giá theo L, ẩn các giá trị kg nhiễu từ dữ liệu thô.
            $weight = [];
        }
    }

    // Khi chưa có bảng giá map, ưu tiên 1 đơn vị theo danh mục để tránh hiển thị lẫn lộn L/kg.
    if (!$has_map) {
        $weight_priority_categories = ['bot-tret', 'keo-va-phu-gia', 'chong-tham'];
        $liter_priority_categories = ['son-noi-that', 'son-ngoai-that', 'son-lot', 'son-dau', 'son-kim-loai', 'son-cong-nghiep', 'son-epoxy'];

        if ($is_putty || my_theme_slug_list_has_any($category_slugs, $weight_priority_categories)) {
            $capacity = [];
        } elseif (my_theme_slug_list_has_any($category_slugs, $liter_priority_categories)) {
            $weight = [];
        }

        // Safety fallback: never render mixed units together when there is no reliable price map.
        if (!empty($capacity) && !empty($weight)) {
            if (count($weight) >= count($capacity)) {
                $capacity = [];
            } else {
                $weight = [];
            }
        }

        // Giới hạn số chip khi dữ liệu thô quá dài.
        if (count($capacity) > 4) {
            $capacity = array_slice($capacity, 0, 4);
        }
        if (count($weight) > 4) {
            $weight = array_slice($weight, 0, 4);
        }
    }

    $cache[$cache_key] = [
        'capacity' => $capacity,
        'weight'   => $weight,
        'is_putty' => $is_putty,
    ];

    return $cache[$cache_key];
}

function my_theme_get_pack_price_map_for_display($product) {
    if (!$product instanceof WC_Product) {
        return [];
    }
    $map = my_theme_parse_capacity_price_map($product);
    if (empty($map)) {
        return [];
    }

    $groups = my_theme_get_product_pack_groups($product);
    if (!empty($groups['is_putty'])) {
        $map = array_filter($map, function ($price, $label) {
            $parsed = my_theme_parse_pack_label($label);
            return $parsed && $parsed['unit'] === 'kg';
        }, ARRAY_FILTER_USE_BOTH);
    }

    if (empty($map)) {
        return [];
    }

    uksort($map, function ($a, $b) {
        $pa = my_theme_parse_pack_label($a);
        $pb = my_theme_parse_pack_label($b);
        if (!$pa || !$pb) {
            return strcmp($a, $b);
        }
        $unit_rank = ['L' => 0, 'kg' => 1];
        $ra = $unit_rank[$pa['unit']] ?? 9;
        $rb = $unit_rank[$pb['unit']] ?? 9;
        if ($ra !== $rb) {
            return ($ra < $rb) ? -1 : 1;
        }
        if ((float) $pa['value'] === (float) $pb['value']) {
            return strcmp($a, $b);
        }
        return ((float) $pa['value'] < (float) $pb['value']) ? -1 : 1;
    });

    return $map;
}

// Lấy danh sách dung tích/khối lượng (mảng) đã chuẩn hóa đơn vị & thứ tự.
function my_theme_get_capacity_options($product) {
    $groups = my_theme_get_product_pack_groups($product);
    return $groups['capacity'];
}

function my_theme_get_weight_options($product) {
    $groups = my_theme_get_product_pack_groups($product);
    return $groups['weight'];
}

if (!function_exists('my_theme_get_product_brand_label')) {
    function my_theme_get_product_brand_label($prod = null)
    {
        $product = ($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID());
        if (!$product instanceof WC_Product) {
            return '';
        }

        $tax_candidates = ['pa_brand', 'product_brand', 'brand'];
        foreach ($tax_candidates as $taxonomy) {
            if (!taxonomy_exists($taxonomy)) {
                continue;
            }
            $names = wc_get_product_terms($product->get_id(), $taxonomy, ['fields' => 'names']);
            if (!empty($names) && !is_wp_error($names)) {
                return (string) $names[0];
            }
        }

        $name_norm = my_theme_normalize_search_text($product->get_name());
        $brand_map = [
            'dulux'  => 'Dulux',
            'jotun'  => 'Jotun',
            'nippon' => 'Nippon',
            'kova'   => 'Kova',
            'weber'  => 'Weber',
            'maxilite' => 'Maxilite',
            'toa'    => 'TOA',
            'sika'   => 'Sika',
        ];
        foreach ($brand_map as $needle => $label) {
            if (strpos($name_norm, $needle) !== false) {
                return $label;
            }
        }

        return 'Sản phẩm';
    }
}

if (!function_exists('my_theme_get_product_primary_category_term')) {
    function my_theme_get_product_primary_category_term($prod = null)
    {
        $product = ($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID());
        if (!$product instanceof WC_Product || !taxonomy_exists('product_cat')) {
            return null;
        }

        static $cache = [];
        $cache_key = (int) $product->get_id();
        if (array_key_exists($cache_key, $cache)) {
            return $cache[$cache_key];
        }

        $terms = wc_get_product_terms($cache_key, 'product_cat', ['fields' => 'all']);
        if (is_wp_error($terms) || empty($terms)) {
            $cache[$cache_key] = null;
            return null;
        }

        $valid_terms = [];
        foreach ($terms as $term) {
            if (!$term instanceof WP_Term || $term->slug === 'uncategorized') {
                continue;
            }
            $valid_terms[] = $term;
        }

        if (empty($valid_terms)) {
            $cache[$cache_key] = null;
            return null;
        }

        $title_norm = my_theme_normalize_search_text($product->get_name());
        $title_priority = [
            'keo-va-phu-gia' => ['keo', 'cha ron', 'dan gach', 'webercolor', 'webertai', 'webertec', 'grout', 'mortar', 'vua kho'],
            'bot-tret'       => ['bot tret', 'putty', 'matit', 'bot ba'],
            'son-lot'        => ['son lot', 'primer', 'sealer', 'lot chong'],
            'chong-tham'     => ['chong tham', 'waterproof', 'aquatech', 'weberdry', 'weberproof', 'weberseal'],
            'son-epoxy'      => ['epoxy'],
            'son-kim-loai'   => ['kim loai', 'chong ri', 'ri set', 'ngan ngua ri', 'gardex', 'alkyd'],
            'son-dau'        => ['son dau'],
            'son-ngoai-that' => ['ngoai that', 'exterior', 'weathershield', 'jotashield', 'ultima'],
            'son-noi-that'   => ['noi that', 'interior', 'easyclean', 'ambiance', 'odour less'],
        ];

        foreach ($title_priority as $slug => $keywords) {
            $has_keyword = false;
            foreach ($keywords as $keyword) {
                if (strpos($title_norm, $keyword) !== false) {
                    $has_keyword = true;
                    break;
                }
            }
            if (!$has_keyword) {
                continue;
            }
            foreach ($valid_terms as $term) {
                if ($term->slug === $slug) {
                    $cache[$cache_key] = $term;
                    return $term;
                }
            }
        }

        $priority_map = [
            'keo-va-phu-gia' => 10,
            'bot-tret'       => 20,
            'son-lot'        => 30,
            'chong-tham'     => 40,
            'son-epoxy'      => 50,
            'son-kim-loai'   => 60,
            'son-cong-nghiep'=> 70,
            'son-dau'        => 80,
            'son-ngoai-that' => 90,
            'son-noi-that'   => 100,
        ];

        usort($valid_terms, function ($a, $b) use ($priority_map) {
            $pa = $priority_map[$a->slug] ?? 999;
            $pb = $priority_map[$b->slug] ?? 999;
            if ($pa !== $pb) {
                return ($pa < $pb) ? -1 : 1;
            }
            if ((int) $a->parent !== (int) $b->parent) {
                return ((int) $a->parent > (int) $b->parent) ? -1 : 1;
            }
            return strnatcasecmp($a->name, $b->name);
        });

        $cache[$cache_key] = $valid_terms[0];
        return $valid_terms[0];
    }
}

if (!function_exists('my_theme_get_product_primary_category_id')) {
    function my_theme_get_product_primary_category_id($prod = null)
    {
        $term = my_theme_get_product_primary_category_term($prod);
        return ($term instanceof WP_Term) ? (int) $term->term_id : 0;
    }
}

if (!function_exists('my_theme_get_product_primary_category_label')) {
    function my_theme_get_product_primary_category_label($prod = null)
    {
        $term = my_theme_get_product_primary_category_term($prod);
        return ($term instanceof WP_Term) ? (string) $term->name : 'Chưa phân loại';
    }
}

if (!function_exists('my_theme_get_product_card_excerpt')) {
    function my_theme_get_product_card_excerpt($prod = null, $limit = 16)
    {
        $product = ($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID());
        if (!$product instanceof WC_Product) {
            return '';
        }

        $text = trim(wp_strip_all_tags((string) $product->get_short_description()));
        if ($text === '') {
            $text = trim(wp_strip_all_tags((string) $product->get_description()));
        }
        if ($text === '') {
            return '';
        }
        return wp_trim_words($text, max(8, (int) $limit), '...');
    }
}

if (!function_exists('my_theme_is_catalog_ready_product')) {
    function my_theme_is_catalog_ready_product($prod = null, $require_price = false)
    {
        $product = ($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID());
        if (!$product instanceof WC_Product) {
            return false;
        }

        $product_id = (int) $product->get_id();
        if ($product_id <= 0 || !has_post_thumbnail($product_id)) {
            return false;
        }

        if (function_exists('my_theme_is_placeholder_product_name') && my_theme_is_placeholder_product_name($product->get_name())) {
            return false;
        }

        $primary = function_exists('my_theme_get_product_primary_category_term')
            ? my_theme_get_product_primary_category_term($product)
            : null;
        if (!$primary instanceof WP_Term || empty($primary->term_id) || $primary->slug === 'uncategorized') {
            return false;
        }

        if (!$require_price) {
            return true;
        }

        $map = my_theme_get_pack_price_map_for_display($product);
        if (!empty($map)) {
            return true;
        }

        return ((float) $product->get_price()) > 0;
    }
}

if (!function_exists('my_theme_get_related_products_for_display')) {
    function my_theme_get_related_products_for_display($prod = null, $limit = 4)
    {
        $product = ($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID());
        if (!$product instanceof WC_Product) {
            return [];
        }

        $limit = max(1, (int) $limit);
        $product_id = (int) $product->get_id();
        $cache_version = (string) get_option('my_theme_related_cache_version', '1');
        $cache_key = 'my_theme_related_' . $cache_version . '_' . $product_id . '_' . $limit;
        $cached_ids = get_transient($cache_key);
        if (is_array($cached_ids) && !empty($cached_ids)) {
            $cached_products = [];
            foreach ($cached_ids as $cached_id) {
                $cached_product = wc_get_product((int) $cached_id);
                if ($cached_product instanceof WC_Product && my_theme_is_catalog_ready_product($cached_product, true)) {
                    $cached_products[] = $cached_product;
                }
            }
            if (!empty($cached_products)) {
                return array_slice($cached_products, 0, $limit);
            }
        }

        $primary_category_id = function_exists('my_theme_get_product_primary_category_id')
            ? (int) my_theme_get_product_primary_category_id($product)
            : 0;
        $primary_category = ($primary_category_id > 0) ? get_term($primary_category_id, 'product_cat') : null;
        $parent_category_id = ($primary_category instanceof WP_Term && (int) $primary_category->parent > 0)
            ? (int) $primary_category->parent
            : 0;

        $brand_taxonomy = '';
        foreach (['pa_brand', 'product_brand', 'brand'] as $candidate) {
            if (taxonomy_exists($candidate)) {
                $brand_taxonomy = $candidate;
                break;
            }
        }
        $brand_ids = ($brand_taxonomy !== '') ? array_map('intval', wc_get_product_term_ids($product_id, $brand_taxonomy)) : [];

        $stages = [];
        if ($primary_category_id > 0 && !empty($brand_ids) && $brand_taxonomy !== '') {
            $stages[] = [
                'tax_query' => [
                    'relation' => 'AND',
                    [
                        'taxonomy' => 'product_cat',
                        'field'    => 'term_id',
                        'terms'    => [$primary_category_id],
                        'operator' => 'IN',
                    ],
                    [
                        'taxonomy' => $brand_taxonomy,
                        'field'    => 'term_id',
                        'terms'    => $brand_ids,
                        'operator' => 'IN',
                    ],
                ],
                'strict_primary' => true,
            ];
        }
        if ($primary_category_id > 0) {
            $stages[] = [
                'tax_query' => [
                    [
                        'taxonomy' => 'product_cat',
                        'field'    => 'term_id',
                        'terms'    => [$primary_category_id],
                        'operator' => 'IN',
                    ],
                ],
                'strict_primary' => true,
            ];
        }
        if ($parent_category_id > 0) {
            $stages[] = [
                'tax_query' => [
                    [
                        'taxonomy' => 'product_cat',
                        'field'    => 'term_id',
                        'terms'    => [$parent_category_id],
                        'operator' => 'IN',
                    ],
                ],
                'strict_primary' => false,
            ];
        }
        if (!empty($brand_ids) && $brand_taxonomy !== '') {
            $stages[] = [
                'tax_query' => [
                    [
                        'taxonomy' => $brand_taxonomy,
                        'field'    => 'term_id',
                        'terms'    => $brand_ids,
                        'operator' => 'IN',
                    ],
                ],
                'strict_primary' => false,
            ];
        }
        $stages[] = ['tax_query' => [], 'strict_primary' => false];

        $result = [];
        $seen_ids = [];

        foreach ($stages as $stage) {
            if (count($result) >= $limit) {
                break;
            }

            $query_args = [
                'status'  => 'publish',
                'type'    => ['simple', 'variable'],
                'limit'   => max(48, $limit * 16),
                'exclude' => array_merge([$product_id], $seen_ids),
                'orderby' => 'meta_value_num',
                'meta_key' => '_price',
                'order'   => 'DESC',
                'return'  => 'objects',
            ];
            if (!empty($stage['tax_query'])) {
                $query_args['tax_query'] = $stage['tax_query'];
            }

            $products = wc_get_products($query_args);
            if (empty($products)) {
                continue;
            }

            foreach ($products as $candidate) {
                if (!$candidate instanceof WC_Product) {
                    continue;
                }
                $candidate_id = (int) $candidate->get_id();
                if ($candidate_id <= 0 || isset($seen_ids[$candidate_id])) {
                    continue;
                }
                $seen_ids[$candidate_id] = $candidate_id;

                if (!my_theme_is_catalog_ready_product($candidate, true)) {
                    continue;
                }
                if (!empty($stage['strict_primary']) && $primary_category_id > 0) {
                    $candidate_primary_id = function_exists('my_theme_get_product_primary_category_id')
                        ? (int) my_theme_get_product_primary_category_id($candidate)
                        : 0;
                    if ($candidate_primary_id !== $primary_category_id) {
                        continue;
                    }
                }

                $result[] = $candidate;
                if (count($result) >= $limit) {
                    break;
                }
            }
        }

        if (!empty($result)) {
            $result_ids = [];
            foreach ($result as $item) {
                if ($item instanceof WC_Product) {
                    $result_ids[] = (int) $item->get_id();
                }
            }
            if (!empty($result_ids)) {
                set_transient($cache_key, $result_ids, 6 * HOUR_IN_SECONDS);
            }
        }

        return array_slice($result, 0, $limit);
    }
}

// Lấy dung tích & khối lượng đã chuẩn hóa.
function my_theme_get_capacity_weight($product) {
    if (!$product instanceof WC_Product) {
        return ['', '', ''];
    }

    $capacity_values = my_theme_get_capacity_options($product);
    $weight_values = my_theme_get_weight_options($product);
    $capacity = implode(' • ', $capacity_values);
    $weight_attr = implode(' • ', $weight_values);

    $weight = '';
    if ($weight_attr === '') {
        $numeric_weight = $product->get_weight();
        if ($numeric_weight !== '') {
            $weight = (float) $numeric_weight;
        }
    }

    return [$capacity, $weight, $weight_attr];
}

function my_theme_render_capacity_weight($prod = null) {
    $product = ($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID());
    if (!$product instanceof WC_Product) {
        return;
    }

    $has_pack_price = !empty(my_theme_get_pack_price_map_for_display($product));
    $is_putty = my_theme_is_putty_product($product);
    if ($product->is_type('simple')) {
        if ($has_pack_price) {
            return;
        }
        // Hide unpriced liter chips for simple paint products to avoid misleading info.
        if (!$is_putty) {
            return;
        }
    }

    [$capacity, $weight, $weight_attr] = my_theme_get_capacity_weight($product);
    $parts = [];
    if ($capacity !== '') {
        $parts[] = sprintf('Dung tích: %s', $capacity);
    }
    if ($weight_attr !== '') {
        $parts[] = sprintf('Khối lượng: %s', $weight_attr);
    } elseif ($weight !== '') {
        $parts[] = sprintf('Khối lượng: %s', wc_format_weight($weight));
    }
    if ($parts) {
        echo '<div class="product-card__meta meta-stack">';
        foreach ($parts as $part) {
            echo '<span class="meta-line">' . esc_html($part) . '</span>';
        }
        echo '</div>';
    }
}

add_action('woocommerce_after_shop_loop_item_title', 'my_theme_render_capacity_weight', 11);
add_action('woocommerce_single_product_summary', 'my_theme_render_capacity_weight', 11);

// Render chip dung tích / khối lượng cho archive + single.
function my_theme_render_capacity_badges($prod = null) {
    $product = ($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID());
    if (!$product instanceof WC_Product) return;

    $map = my_theme_get_pack_price_map_for_display($product);
    $is_putty = my_theme_is_putty_product($product);
    if ($product->is_type('simple')) {
        if (!empty($map)) {
            return;
        }
        if (!$is_putty) {
            return;
        }
    }

    $caps = my_theme_get_capacity_options($product);
    $weights = my_theme_get_weight_options($product);
    if (empty($caps) && empty($weights)) return;
    $readonly = ($product->is_type('simple') && empty($map));
    $wrap_class = 'capacity-badges' . ($readonly ? ' capacity-badges--readonly' : '');
    $chip_class = 'capacity-chip' . ($readonly ? ' capacity-chip--readonly' : '');
    $weight_chip_class = $chip_class . ' capacity-chip--muted';

    echo '<div class="' . esc_attr($wrap_class) . '">';
    if (!empty($caps)) {
        echo '<div class="capacity-badges__row" aria-label="Dung tích">';
        foreach ($caps as $cap) {
            echo '<span class="' . esc_attr($chip_class) . '">' . esc_html($cap) . '</span>';
        }
        echo '</div>';
    }
    if (!empty($weights)) {
        echo '<div class="capacity-badges__row" aria-label="Khối lượng">';
        foreach ($weights as $w) {
            echo '<span class="' . esc_attr($weight_chip_class) . '">' . esc_html($w) . '</span>';
        }
        echo '</div>';
    }
    echo '</div>';
}
add_action('woocommerce_after_shop_loop_item_title', 'my_theme_render_capacity_badges', 12);
add_action('woocommerce_single_product_summary', 'my_theme_render_capacity_badges', 12);

// Render danh sách giá theo từng dung tích/khối lượng nếu có map giá.
function my_theme_render_pack_price_list($prod = null, $context = 'loop') {
    $product = ($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID());
    if (!$product instanceof WC_Product) {
        return;
    }

    $map = my_theme_get_pack_price_map_for_display($product);
    if (empty($map)) {
        return;
    }

    $all_kg = true;
    foreach (array_keys($map) as $size_label) {
        $parsed = my_theme_parse_pack_label($size_label);
        if (!$parsed || $parsed['unit'] !== 'kg') {
            $all_kg = false;
            break;
        }
    }
    $title = $all_kg ? 'Giá theo khối lượng:' : 'Giá theo dung tích:';
    $class = 'product-pack-prices';
    if ($context === 'single') {
        $class .= ' product-pack-prices--single';
    } elseif ($context === 'related') {
        $class .= ' product-pack-prices--related';
    } else {
        $class .= ' product-pack-prices--loop';
    }

    $display_map = $map;
    $more_count = 0;
    $max_items = 0;
    if ($context === 'related') {
        $max_items = 2;
    } elseif ($context !== 'single') {
        $max_items = 3;
    }
    if ($max_items > 0 && count($map) > $max_items) {
        $display_map = array_slice($map, 0, $max_items, true);
        $more_count = count($map) - $max_items;
    }

    echo '<div class="' . esc_attr($class) . '">';
    echo '<span class="product-pack-prices__label">' . esc_html($title) . '</span>';
    $is_first = true;
    foreach ($display_map as $size_label => $price_value) {
        $item_class = 'product-pack-prices__item' . ($is_first ? ' is-active' : '');
        echo '<span class="' . esc_attr($item_class) . '" data-pack-size="' . esc_attr($size_label) . '"><strong>' . esc_html($size_label) . '</strong>: ' . wp_kses_post(wc_price($price_value)) . '</span>';
        $is_first = false;
    }
    if ($more_count > 0) {
        echo '<span class="product-pack-prices__more">+' . esc_html((string) $more_count) . ' mức giá</span>';
    }
    echo '</div>';
}

add_action('woocommerce_single_product_summary', function () {
    my_theme_render_pack_price_list(null, 'single');
}, 10);

function my_theme_get_default_loop_price($product) {
    if (!$product instanceof WC_Product) {
        return 0.0;
    }
    $map = my_theme_get_pack_price_map_for_display($product);
    if (!empty($map)) {
        $first_key = array_key_first($map);
        if ($first_key !== null) {
            return (float) $map[$first_key];
        }
    }
    return (float) $product->get_price();
}

function my_theme_render_loop_price($prod = null) {
    $product = ($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID());
    if (!$product instanceof WC_Product) {
        return;
    }

    $price_value = my_theme_get_default_loop_price($product);
    if ($price_value > 0) {
        echo '<div class="product-card__price"><span class="product-card__price-value" data-price="' . esc_attr($price_value) . '">' . wp_kses_post(wc_price($price_value)) . '</span></div>';
        return;
    }
    echo '<div class="product-card__price"><span class="product-card__price-contact">Liên hệ báo giá</span></div>';
}

// Fallback price for simple products that only store capacity-price map.
add_filter('woocommerce_get_price_html', function ($price_html, $product) {
    if (!$product instanceof WC_Product || !$product->is_type('simple')) {
        return $price_html;
    }

    $map = my_theme_get_pack_price_map_for_display($product);
    $raw_price = (float) $product->get_price();

    if (!empty($map)) {
        if (trim((string) $price_html) !== '' && $raw_price > 0) {
            return $price_html;
        }
        $first_price = (float) reset($map);
        if ($first_price > 0) {
            return wc_price($first_price);
        }
    }

    if ($raw_price <= 0) {
        return '<span class="product-price-contact-inline">Liên hệ báo giá</span>';
    }

    return $price_html;
}, 20, 2);

add_filter('woocommerce_is_purchasable', function ($purchasable, $product) {
    if (!$product instanceof WC_Product || !$product->is_type('simple')) {
        return $purchasable;
    }
    if (!$product->is_in_stock()) {
        return false;
    }

    $map = my_theme_get_pack_price_map_for_display($product);
    if (!empty($map)) {
        return true;
    }

    $raw_price = (float) $product->get_price();
    if ($raw_price <= 0) {
        return false;
    }

    return $purchasable;
}, 20, 2);

function my_theme_render_loop_add_to_cart($prod = null) {
    $product = ($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID());
    if (!$product instanceof WC_Product) {
        return;
    }

    $price_map = my_theme_get_pack_price_map_for_display($product);
    $has_pack_price = !empty($price_map);
    if ((!$product->is_purchasable() && !$has_pack_price) || !$product->is_in_stock()) {
        echo '<a class="button btn-outline w-100" href="' . esc_url($product->get_permalink()) . '">Xem chi tiết</a>';
        return;
    }

    $is_simple = $product->is_type('simple');
    if (!$is_simple || !$has_pack_price) {
        $classes = ['button', 'product_type_' . $product->get_type()];
        if ($product->supports('ajax_add_to_cart') && $product->is_purchasable() && $product->is_in_stock()) {
            $classes[] = 'add_to_cart_button';
            $classes[] = 'ajax_add_to_cart';
        }

        $attrs = [
            'href' => $product->add_to_cart_url(),
            'data-quantity' => 1,
            'class' => implode(' ', array_map('sanitize_html_class', $classes)),
            'data-product_id' => $product->get_id(),
            'data-product_sku' => $product->get_sku(),
            'aria-label' => $product->add_to_cart_description(),
            'rel' => 'nofollow',
        ];

        $attr_html = [];
        foreach ($attrs as $attr_name => $attr_value) {
            if ($attr_value === '' || $attr_value === null) {
                continue;
            }
            $attr_html[] = esc_attr($attr_name) . '="' . esc_attr((string) $attr_value) . '"';
        }
        echo '<a ' . implode(' ', $attr_html) . '>' . esc_html($product->add_to_cart_text()) . '</a>';
        return;
    }

    $sizes = array_keys($price_map);
    $default_size = (string) array_key_first($price_map);
    $default_price = (float) $price_map[$default_size];
    $all_kg = true;
    foreach ($sizes as $size_label) {
        $parsed = my_theme_parse_pack_label($size_label);
        if (!$parsed || $parsed['unit'] !== 'kg') {
            $all_kg = false;
            break;
        }
    }
    $picker_label = $all_kg ? 'Chọn khối lượng' : 'Chọn dung tích';

    echo '<form class="loop-pack-form" method="post" action="' . esc_url($product->get_permalink()) . '" enctype="multipart/form-data" data-product-id="' . esc_attr($product->get_id()) . '">';
    echo '<div class="loop-pack-picker">';
    echo '<span class="loop-pack-picker__label">' . esc_html($picker_label) . ':</span>';
    echo '<div class="loop-pack-picker__options" role="group" aria-label="' . esc_attr($picker_label) . '">';
    foreach ($sizes as $size_label) {
        $is_active = ($size_label === $default_size) ? ' is-active' : '';
        echo '<button type="button" class="loop-pack-option' . esc_attr($is_active) . '" data-capacity="' . esc_attr($size_label) . '" data-price="' . esc_attr($price_map[$size_label]) . '">' . esc_html($size_label) . '</button>';
    }
    echo '</div>';
    echo '</div>';
    echo '<input type="hidden" name="add-to-cart" value="' . esc_attr($product->get_id()) . '">';
    echo '<input type="hidden" name="quantity" value="1">';
    echo '<input type="hidden" name="selected_capacity" value="' . esc_attr($default_size) . '">';
    echo '<input type="hidden" name="selected_capacity_price" value="' . esc_attr($default_price) . '">';
    echo '<button type="submit" class="button add_to_cart_button w-100">Thêm vào giỏ</button>';
    echo '</form>';
}

// --- Simple product: picker dung tích đổi giá theo bảng map ---
function my_theme_render_capacity_price_picker() {
    if (!is_product()) return;
    global $product;
    if (!$product instanceof WC_Product || $product->is_type('variable')) return; // biến thể dùng core

    $map = my_theme_get_pack_price_map_for_display($product);
    if (empty($map)) return;

    $caps = array_keys($map);
    $all_kg = true;
    foreach ($caps as $label) {
        $parsed = my_theme_parse_pack_label($label);
        if (!$parsed || $parsed['unit'] !== 'kg') {
            $all_kg = false;
            break;
        }
    }
    $picker_label = $all_kg ? 'Chọn khối lượng:' : 'Chọn dung tích:';

    $default_cap = $caps[0];
    $default_price = $map[$default_cap];
    ?>
    <div class="capacity-picker" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
      <div class="capacity-picker__label"><?php echo esc_html($picker_label); ?></div>
      <div class="capacity-picker__options" role="group" aria-label="<?php echo esc_attr($all_kg ? 'Khối lượng' : 'Dung tích'); ?>">
        <?php foreach ($caps as $cap) : ?>
          <button type="button" class="capacity-option<?php echo $cap === $default_cap ? ' is-active' : ''; ?>" data-capacity="<?php echo esc_attr($cap); ?>" data-price="<?php echo esc_attr($map[$cap]); ?>">
            <?php echo esc_html($cap); ?>
          </button>
        <?php endforeach; ?>
      </div>
      <div class="capacity-picker__current">Đang chọn: <strong data-capacity-current><?php echo esc_html($default_cap); ?></strong></div>
      <input type="hidden" name="selected_capacity" value="<?php echo esc_attr($default_cap); ?>">
      <input type="hidden" name="selected_capacity_price" value="<?php echo esc_attr($default_price); ?>">
    </div>
    <?php
}
add_action('woocommerce_before_add_to_cart_button', 'my_theme_render_capacity_price_picker', 8);

// Lưu dung tích vào cart item
add_filter('woocommerce_add_cart_item_data', function ($cart_item_data, $product_id) {
    $selected_capacity = '';
    if (isset($_POST['selected_capacity'])) {
        $selected_capacity = wc_clean(wp_unslash($_POST['selected_capacity']));
    }
    $selected_price = 0.0;
    if (isset($_POST['selected_capacity_price'])) {
        $selected_price = (float) wc_clean(wp_unslash($_POST['selected_capacity_price']));
    }

    if ($selected_capacity !== '') {
        $product = wc_get_product($product_id);
        if ($product instanceof WC_Product) {
            $price_map = my_theme_get_pack_price_map_for_display($product);
            if (!empty($price_map)) {
                if (!isset($price_map[$selected_capacity])) {
                    $first_key = (string) array_key_first($price_map);
                    if ($first_key !== '') {
                        $selected_capacity = $first_key;
                    }
                }
                if ($selected_capacity !== '' && isset($price_map[$selected_capacity])) {
                    $selected_price = (float) $price_map[$selected_capacity];
                }
            }
        }
        $cart_item_data['selected_capacity'] = $selected_capacity;
    }
    if ($selected_price > 0) {
        $cart_item_data['selected_capacity_price'] = $selected_price;
    }
    if (!empty($cart_item_data)) {
        $cart_item_data['unique_key'] = md5(microtime().rand());
    }
    return $cart_item_data;
}, 10, 2);

// Hiển thị dung tích trong cart/checkout
add_filter('woocommerce_get_item_data', function ($item_data, $cart_item) {
    if (!empty($cart_item['selected_capacity'])) {
        $pack_label = 'Dung tích';
        $parsed = my_theme_parse_pack_label($cart_item['selected_capacity']);
        if ($parsed && $parsed['unit'] === 'kg') {
            $pack_label = 'Khối lượng';
        }
        $item_data[] = [
            'name' => $pack_label,
            'value' => $cart_item['selected_capacity'],
        ];
    }
    return $item_data;
}, 10, 2);

// Lưu dung tích đã chọn vào order items để hiển thị trong admin/email.
add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values) {
    if (!empty($values['selected_capacity'])) {
        $pack_label = 'Dung tích';
        $parsed = my_theme_parse_pack_label($values['selected_capacity']);
        if ($parsed && $parsed['unit'] === 'kg') {
            $pack_label = 'Khối lượng';
        }
        $item->add_meta_data($pack_label, $values['selected_capacity'], true);
    }
}, 10, 3);

// Set giá theo dung tích đã chọn
add_action('woocommerce_before_calculate_totals', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        if (!empty($cart_item['selected_capacity_price'])) {
            $cart_item['data']->set_price((float) $cart_item['selected_capacity_price']);
        }
    }
}, 10, 1);

// Helper: download remote image and attach to product (used by official import)
if (!function_exists('my_theme_download_remote_image')) {
    function my_theme_download_remote_image($url, $post_id = 0, $group_key = '')
    {
        if (!$url) {
            return 0;
        }

        if (!function_exists('download_url')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if (!function_exists('wp_handle_sideload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if (!function_exists('wp_insert_attachment')) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $tmp = download_url($url);
        if (is_wp_error($tmp)) {
            return 0;
        }

        $filename = basename(parse_url($url, PHP_URL_PATH));
        if (!$filename) {
            $filename = 'remote-image-' . time() . '.jpg';
        }

        $file_array = [
            'name'     => $filename,
            'tmp_name' => $tmp,
        ];

        $overrides = ['test_form' => false, 'test_size' => true];
        $file = wp_handle_sideload($file_array, $overrides);
        if (!empty($file['error'])) {
            @unlink($tmp);
            return 0;
        }

        $attachment = [
            'post_mime_type' => $file['type'] ?? 'image/jpeg',
            'post_title'     => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ];

        $attach_id = wp_insert_attachment($attachment, $file['file'], $post_id);
        if (is_wp_error($attach_id) || !$attach_id) {
            return 0;
        }

        $attach_data = wp_generate_attachment_metadata($attach_id, $file['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        if ($group_key !== '') {
            update_post_meta($attach_id, '_official_import_group_key', sanitize_title($group_key));
        }

        return (int) $attach_id;
    }
}

// Legacy image-folder import (kept for recovery only).
// Run only when explicitly enabled: /wp-admin/?run_import=1&allow_legacy_import=1&force_import=1
add_action('admin_init', function () {
    if (!is_admin() || empty($_GET['run_import'])) {
        return;
    }

    if (empty($_GET['allow_legacy_import'])) {
        wp_die('Legacy import is disabled by default. Use import_official=1 or add allow_legacy_import=1 intentionally.');
    }

    if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
        wp_die('Permission denied.');
    }

    @set_time_limit(0);
    @ini_set('memory_limit', '512M');

    $force = !empty($_GET['force_import']);
    $lock_key = 'wc_import_brand_line_cleanup_v2_done';
    if (get_option($lock_key) === '1' && !$force) {
        wp_die('Import already completed. Add force_import=1 to rerun.');
    }

    if (!class_exists('WooCommerce') || !class_exists('WC_Product_Simple')) {
        wp_die('WooCommerce is required.');
    }

    if (!function_exists('wp_upload_bits')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    if (!function_exists('wp_generate_attachment_metadata')) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }
    if (!function_exists('wp_insert_attachment')) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
    }

    $stats = [
        'deleted' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors'  => 0,
    ];

    $source_candidates = [
        'C:\\Users\\letan\\OneDrive\\Máy tính\\hình dulux',
        WP_CONTENT_DIR . '/uploads/dulux',
        get_theme_file_path('assets/dulux_import'),
        '/var/www/html/wp-content/themes/my-theme/assets/dulux_import',
    ];

    $source_dir = '';
    foreach ($source_candidates as $candidate) {
        if (is_dir($candidate)) {
            $source_dir = $candidate;
            break;
        }
    }

    if ($source_dir === '') {
        $stats['errors']++;
        update_option($lock_key, '1', false);
        wp_die(
            'Deleted: ' . intval($stats['deleted']) .
            ' | Created: ' . intval($stats['created']) .
            ' | Updated: ' . intval($stats['updated']) .
            ' | Skipped: ' . intval($stats['skipped']) .
            ' | Errors: ' . intval($stats['errors']) .
            ' | Source directory not found.'
        );
    }

    $taxonomy_capacity = 'pa_dung-tich';
    $taxonomy_brand_candidates = ['pa_brand', 'product_brand', 'brand'];

    if (!taxonomy_exists($taxonomy_capacity) && function_exists('wc_create_attribute')) {
        $attr_slug = 'dung-tich';
        $attr_id = function_exists('wc_attribute_taxonomy_id_by_name') ? (int) wc_attribute_taxonomy_id_by_name($attr_slug) : 0;
        if ($attr_id <= 0) {
            wc_create_attribute([
                'name'         => 'Dung tich',
                'slug'         => $attr_slug,
                'type'         => 'select',
                'order_by'     => 'menu_order',
                'has_archives' => false,
            ]);
            delete_transient('wc_attribute_taxonomies');
        }
    }

    if (!taxonomy_exists($taxonomy_capacity)) {
        register_taxonomy(
            $taxonomy_capacity,
            ['product'],
            [
                'hierarchical' => true,
                'show_ui'      => false,
                'query_var'    => true,
                'rewrite'      => false,
            ]
        );
    }

    $taxonomy_brand = '';
    foreach ($taxonomy_brand_candidates as $tax) {
        if (taxonomy_exists($tax)) {
            $taxonomy_brand = $tax;
            break;
        }
    }

    $brand_map = [
        'dulux'  => 'Dulux',
        'jotun'  => 'Jotun',
        'nippon' => 'Nippon',
        'kova'   => 'Kova',
    ];

    $to_lower = function ($value) {
        $value = remove_accents((string) $value);
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    };

    $normalize = function ($value) use ($to_lower) {
        $value = $to_lower($value);
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value);
        $value = preg_replace('/\s+/u', ' ', trim((string) $value));
        return $value;
    };

    $format_measure = function ($value, $unit) {
        $value = (float) str_replace(',', '.', (string) $value);
        $text = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
        return $text . ($unit === 'kg' ? 'kg' : 'L');
    };

    $extract_measures = function ($raw) use ($format_measure, $normalize) {
        $items = [];
        $text = $normalize(pathinfo((string) $raw, PATHINFO_FILENAME));
        if (preg_match_all('/(\d+(?:[.,]\d+)?)\s*(l|kg)\b/i', $text, $m, PREG_SET_ORDER)) {
            foreach ($m as $row) {
                $label = $format_measure($row[1], strtolower($row[2]));
                $items[strtolower($label)] = $label;
            }
        }
        return array_values($items);
    };

    $get_brand_key = function ($filename) use ($normalize, $brand_map) {
        $name = $normalize(pathinfo($filename, PATHINFO_FILENAME));
        foreach (array_keys($brand_map) as $brand_key) {
            if (preg_match('/(^|\s)' . preg_quote($brand_key, '/') . '(\s|$)/u', $name)) {
                return $brand_key;
            }
        }
        return '';
    };

    $get_product_type = function ($filename) use ($normalize) {
        $name = $normalize(pathinfo($filename, PATHINFO_FILENAME));
        if (preg_match('/(^|\s)(bot|putty|tret)(\s|$)/u', $name)) {
            return ['bot-tret', 'Bột trét'];
        }
        return ['son', 'Sơn'];
    };

    $get_line_slug = function ($filename, $brand_key) use ($normalize) {
        $base = $normalize(pathinfo($filename, PATHINFO_FILENAME));
        $tokens = preg_split('/\s+/u', $base, -1, PREG_SPLIT_NO_EMPTY);

        $remove = [
            'son', 'paint', 'bot', 'tret', 'putty',
            'noi', 'ngoai', 'that', 'interior', 'exterior',
            'front', 'back', 'side', 'top', 'label',
            'mat', 'truoc', 'sau', 'left', 'right',
            'hinh', 'anh', 'image', 'img', 'packshot',
            'jpeg', 'jpg', 'png', 'webp', 'avif',
            $brand_key,
        ];

        $line_tokens = [];
        foreach ($tokens as $token) {
            if ($token === '' || in_array($token, $remove, true)) {
                continue;
            }
            if (preg_match('/^\d+(?:[.,]\d+)?(l|kg)$/i', $token)) {
                continue;
            }
            if (preg_match('/^\d+(?:[.,]\d+)?$/', $token)) {
                continue;
            }
            if ($token === 'l' || $token === 'kg') {
                continue;
            }
            $line_tokens[] = $token;
        }

        while (!empty($line_tokens) && preg_match('/^\d+$/', (string) end($line_tokens))) {
            array_pop($line_tokens);
        }

        if (empty($line_tokens)) {
            return '';
        }

        return sanitize_title(implode(' ', $line_tokens));
    };

    $format_line_label = function ($line_slug) {
        $map = [
            'noi'           => 'Nội',
            'ngoai'         => 'Ngoại',
            'that'          => 'Thất',
            'easyclean'     => 'EasyClean',
            'weathershield' => 'Weathershield',
            'jotashield'    => 'Jotashield',
            'odour'         => 'Odour',
            'less'          => 'Less',
            'maxilite'      => 'Maxilite',
        ];

        $tokens = preg_split('/[-\s]+/u', (string) $line_slug, -1, PREG_SPLIT_NO_EMPTY);
        $out = [];
        foreach ($tokens as $token) {
            $low = strtolower($token);
            if (isset($map[$low])) {
                $out[] = $map[$low];
                continue;
            }
            if (preg_match('/^[a-z]{1,4}\d+[a-z0-9]*$/i', $token)) {
                $out[] = strtoupper($token);
                continue;
            }
            $out[] = ucfirst(strtolower($token));
        }

        return trim(implode(' ', $out));
    };

    $is_jotun_featured = function ($product_id) use ($to_lower) {
        $thumb_id = (int) get_post_thumbnail_id($product_id);
        if ($thumb_id <= 0) {
            return false;
        }

        $file = (string) get_attached_file($thumb_id);
        $basename = basename($file);
        $filename_no_ext = pathinfo($basename, PATHINFO_FILENAME);
        $haystack = $to_lower(
            $basename . ' ' .
            get_the_title($thumb_id) . ' ' .
            get_post_field('post_excerpt', $thumb_id) . ' ' .
            get_post_field('post_content', $thumb_id) . ' ' .
            wp_get_attachment_url($thumb_id)
        );

        if (strpos($haystack, 'jotun') !== false) {
            return true;
        }

        return preg_match('/^(image\d+|a\d+|packshot|screenshot)/i', (string) $filename_no_ext) === 1;
    };

    $find_product_by_import_key = function ($key) {
        $ids = get_posts([
            'post_type'      => 'product',
            'post_status'    => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => '_import_brand_line_key',
            'meta_value'     => $key,
        ]);
        return !empty($ids) ? (int) $ids[0] : 0;
    };

    $ensure_term = function ($term_name, $taxonomy) {
        $exists = term_exists($term_name, $taxonomy);
        if (!$exists) {
            $res = wp_insert_term($term_name, $taxonomy, ['slug' => sanitize_title($term_name)]);
            if (is_wp_error($res)) {
                return 0;
            }
            return (int) $res['term_id'];
        }
        return is_array($exists) ? (int) $exists['term_id'] : (int) $exists;
    };

    $do_cleanup = !empty($_GET['cleanup_import']);
    if ($do_cleanup) {
        $all_product_ids = get_posts([
            'post_type'      => 'product',
            'post_status'    => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);
        foreach ($all_product_ids as $product_id) {
            $import_key = (string) get_post_meta($product_id, '_import_brand_line_key', true);
            $title_norm = $to_lower(get_the_title($product_id));
            $old_brand_title = preg_match('/(^|\s)(dulux|nippon|maxilite)(\s|$)/u', $title_norm) === 1;
            $need_delete = ($import_key === '') || ($old_brand_title && $is_jotun_featured($product_id));

            if (!$need_delete) {
                continue;
            }

            $trashed = wp_trash_post($product_id);
            if ($trashed !== false && $trashed !== null) {
                $stats['deleted']++;
            } else {
                $stats['errors']++;
            }
        }
    }

    $files = [];
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source_dir, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($it as $f) {
        if (!$f->isFile()) {
            continue;
        }
        $ext = strtolower((string) $f->getExtension());
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'avif'], true)) {
            $files[] = $f->getPathname();
        }
    }
    natsort($files);
    $files = array_values($files);

    $groups = [];
    foreach ($files as $path) {
        $filename = basename($path);
        $brand_key = $get_brand_key($filename);
        if ($brand_key === '') {
            $stats['skipped']++;
            continue;
        }

        [$type_key, $type_label] = $get_product_type($filename);
        $line_slug = $get_line_slug($filename, $brand_key);
        if ($line_slug === '') {
            $stats['skipped']++;
            continue;
        }

        $line_label = $format_line_label($line_slug);
        if ($line_label === '') {
            $line_label = ucfirst(str_replace('-', ' ', $line_slug));
        }

        $product_name = trim($type_label . ' ' . $brand_map[$brand_key] . ' ' . $line_label);
        $key = sanitize_title($brand_key . '-' . $type_key . '-' . $line_slug);

        if (!isset($groups[$key])) {
            $groups[$key] = [
                'brand_key'    => $brand_key,
                'brand_label'  => $brand_map[$brand_key],
                'type_key'     => $type_key,
                'type_label'   => $type_label,
                'line_slug'    => $line_slug,
                'line_label'   => $line_label,
                'product_name' => $product_name,
                'files'        => [],
                'measures'     => [],
            ];
        }

        $groups[$key]['files'][] = $path;

        $measures = $extract_measures($filename);
        foreach ($measures as $ms) {
            $groups[$key]['measures'][strtolower($ms)] = $ms;
        }
    }

    foreach ($groups as $key => $group) {
        $product_name = $group['product_name'];
        $product_id = $find_product_by_import_key($key);

        if ($product_id > 0) {
            $product = wc_get_product($product_id);
            if (!$product || !($product instanceof WC_Product)) {
                $stats['errors']++;
                continue;
            }
            $stats['updated']++;
        } else {
            $product = new WC_Product_Simple();
            $product->set_name($product_name);
            $product->set_slug(sanitize_title($product_name));
            $product->set_status('publish');
            $product->set_catalog_visibility('visible');
            $product->set_regular_price('0');
            $product->set_price('0');
            $product->set_stock_status('instock');
            $product_id = $product->save();
            if (!$product_id) {
                $stats['errors']++;
                continue;
            }
            $stats['created']++;
        }
        update_post_meta($product_id, '_import_brand_line_key', $key);

        $capacity_labels = array_values($group['measures']);
        if (!empty($capacity_labels) && taxonomy_exists($taxonomy_capacity)) {
            $current_terms = wp_get_object_terms($product_id, $taxonomy_capacity, ['fields' => 'names']);
            if (is_wp_error($current_terms)) {
                $current_terms = [];
            }

            $merged_terms = array_values(array_unique(array_merge($current_terms, $capacity_labels)));
            foreach ($merged_terms as $t) {
                $ensure_term($t, $taxonomy_capacity);
            }

            if (!empty($merged_terms)) {
                wp_set_object_terms($product_id, $merged_terms, $taxonomy_capacity, false);

                $term_ids = wp_get_object_terms($product_id, $taxonomy_capacity, ['fields' => 'ids']);
                if (is_wp_error($term_ids)) {
                    $term_ids = [];
                }

                $attrs = $product->get_attributes();
                $attr_obj = new WC_Product_Attribute();
                $tax_id = function_exists('wc_attribute_taxonomy_id_by_name') ? (int) wc_attribute_taxonomy_id_by_name('dung-tich') : 0;
                if ($tax_id > 0) {
                    $attr_obj->set_id($tax_id);
                }
                $attr_obj->set_name($taxonomy_capacity);
                $attr_obj->set_options(array_map('intval', $term_ids));
                $attr_obj->set_position(0);
                $attr_obj->set_visible(true);
                $attr_obj->set_variation(false);
                $attrs[$taxonomy_capacity] = $attr_obj;
                $product->set_attributes($attrs);

                update_post_meta($product_id, '_display_capacity_list', implode(' | ', $merged_terms));
            }
        }

        $cat_id = $ensure_term($group['type_label'], 'product_cat');
        if ($cat_id > 0) {
            wp_set_object_terms($product_id, [$cat_id], 'product_cat', false);
        }

        if ($taxonomy_brand !== '') {
            $ensure_term($group['brand_label'], $taxonomy_brand);
            wp_set_object_terms($product_id, [$group['brand_label']], $taxonomy_brand, false);
        }

        natsort($group['files']);
        $group_files = array_values($group['files']);
        $new_attach_ids = [];

        foreach ($group_files as $img_path) {
            $file_hash = @md5_file($img_path);
            if (!$file_hash) {
                $stats['errors']++;
                continue;
            }

            $exists = get_posts([
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'post_parent'    => $product_id,
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_query'     => [
                    [
                        'key'   => '_import_file_hash',
                        'value' => $file_hash,
                    ],
                    [
                        'key'   => '_import_group_key',
                        'value' => $key,
                    ],
                ],
            ]);

            if (!empty($exists)) {
                $new_attach_ids[] = (int) $exists[0];
                continue;
            }

            $binary = @file_get_contents($img_path);
            if ($binary === false) {
                $stats['errors']++;
                continue;
            }

            $filename = basename($img_path);
            $upload = wp_upload_bits($filename, null, $binary);
            if (!empty($upload['error'])) {
                $stats['errors']++;
                continue;
            }

            $ft = wp_check_filetype($upload['file'], null);
            $attach_id = wp_insert_attachment(
                [
                    'post_mime_type' => $ft['type'],
                    'post_title'     => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
                    'post_content'   => '',
                    'post_status'    => 'inherit',
                ],
                $upload['file'],
                $product_id
            );

            if (is_wp_error($attach_id) || !$attach_id) {
                $stats['errors']++;
                continue;
            }

            $meta = wp_generate_attachment_metadata($attach_id, $upload['file']);
            wp_update_attachment_metadata($attach_id, $meta);
            update_post_meta($attach_id, '_import_file_hash', $file_hash);
            update_post_meta($attach_id, '_import_group_key', $key);

            $new_attach_ids[] = (int) $attach_id;
        }

        $featured = (int) get_post_thumbnail_id($product_id);
        if ($featured <= 0 && !empty($new_attach_ids)) {
            $featured = (int) $new_attach_ids[0];
            set_post_thumbnail($product_id, $featured);
        }

        $current_gallery = $product->get_gallery_image_ids();
        $all_gallery = array_values(array_unique(array_merge($current_gallery, $new_attach_ids)));
        if ($featured > 0) {
            $all_gallery = array_values(array_diff($all_gallery, [$featured]));
        }
        $product->set_gallery_image_ids($all_gallery);

        $product->save();
    }

    update_option($lock_key, '1', false);

    wp_die(
        'Deleted: ' . intval($stats['deleted']) .
        ' | Created: ' . intval($stats['created']) .
        ' | Updated: ' . intval($stats['updated']) .
        ' | Skipped: ' . intval($stats['skipped']) .
        ' | Errors: ' . intval($stats['errors'])
    );
});

// Import products from official catalog JSON.
// Example: /wp-admin/?import_official=1&brand=dulux&cleanup=1&cleanup_legacy=1&force_image=1
add_action('admin_init', function () {
    if (!is_admin() || empty($_GET['import_official'])) {
        return;
    }

    if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
        wp_die('Permission denied.');
    }

    @set_time_limit(0);
    @ini_set('memory_limit', '512M');

    $brand = isset($_GET['brand']) ? sanitize_title($_GET['brand']) : 'dulux';
    $data_file = get_theme_file_path('data/' . $brand . '_official.json');
    if (!file_exists($data_file)) {
        wp_die('Data file not found: ' . esc_html($data_file));
    }

    $items = json_decode(file_get_contents($data_file), true);
    if (!is_array($items)) {
        wp_die('Invalid JSON in ' . esc_html($data_file));
    }

    if (!function_exists('wc_get_product')) {
        wp_die('WooCommerce is required.');
    }
    if (!function_exists('wp_upload_bits')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    if (!function_exists('wp_generate_attachment_metadata')) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }
    if (!function_exists('wp_insert_attachment')) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
    }

    $taxonomy_capacity = 'pa_dung-tich';
    if (!taxonomy_exists($taxonomy_capacity) && function_exists('wc_create_attribute')) {
        $attr_slug = 'dung-tich';
        $attr_id = function_exists('wc_attribute_taxonomy_id_by_name') ? (int) wc_attribute_taxonomy_id_by_name($attr_slug) : 0;
        if ($attr_id <= 0) {
            wc_create_attribute([
                'name'         => 'Dung tich',
                'slug'         => $attr_slug,
                'type'         => 'select',
                'order_by'     => 'menu_order',
                'has_archives' => false,
            ]);
            delete_transient('wc_attribute_taxonomies');
        }
    }
    if (!taxonomy_exists($taxonomy_capacity)) {
        register_taxonomy(
            $taxonomy_capacity,
            ['product'],
            [
                'hierarchical' => true,
                'show_ui'      => false,
                'query_var'    => true,
                'rewrite'      => false,
            ]
        );
    }

    $taxonomy_brand_candidates = ['pa_brand', 'product_brand', 'brand'];
    $taxonomy_brand = '';
    foreach ($taxonomy_brand_candidates as $tax) {
        if (taxonomy_exists($tax)) {
            $taxonomy_brand = $tax;
            break;
        }
    }

    $ensure_term = function ($term_name, $taxonomy) {
        $exists = term_exists($term_name, $taxonomy);
        if (!$exists) {
            $res = wp_insert_term($term_name, $taxonomy, ['slug' => sanitize_title($term_name)]);
            if (is_wp_error($res)) {
                return 0;
            }
            return (int) $res['term_id'];
        }
        return is_array($exists) ? (int) $exists['term_id'] : (int) $exists;
    };

    $find_product = function ($key) {
        $ids = get_posts([
            'post_type'      => 'product',
            'post_status'    => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => '_official_import_key',
            'meta_value'     => $key,
        ]);
        return !empty($ids) ? (int) $ids[0] : 0;
    };

    // Reuse legacy products by slug/title to avoid creating duplicates when migrating.
    $find_product_legacy = function ($slug, $name) {
        $slug = sanitize_title($slug);
        $name = trim((string) $name);

        if ($slug !== '') {
            $ids = get_posts([
                'post_type'      => 'product',
                'post_status'    => ['publish', 'draft', 'pending', 'private'],
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'name'           => $slug,
                'meta_query'     => [
                    [
                        'key'     => '_official_import_key',
                        'compare' => 'NOT EXISTS',
                    ],
                ],
            ]);
            if (!empty($ids)) {
                return (int) $ids[0];
            }
        }

        if ($name !== '') {
            $ids = get_posts([
                'post_type'      => 'product',
                'post_status'    => ['publish', 'draft', 'pending', 'private'],
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'title'          => $name,
                'meta_query'     => [
                    [
                        'key'     => '_official_import_key',
                        'compare' => 'NOT EXISTS',
                    ],
                ],
            ]);
            if (!empty($ids)) {
                return (int) $ids[0];
            }
        }

        return 0;
    };

    $cleanup = !empty($_GET['cleanup']);
    $cleanup_legacy = !empty($_GET['cleanup_legacy']);
    $force_image = !empty($_GET['force_image']);
    // Keep current featured image only when explicitly requested.
    $keep_existing_image = !empty($_GET['keep_existing_image']);
    $stats = [
        'deleted' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors'  => 0,
    ];

    if ($cleanup) {
        $all_products = get_posts([
            'post_type'      => 'product',
            'post_status'    => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_key'       => '_official_import_key',
            'meta_compare'   => 'EXISTS',
        ]);
        foreach ($all_products as $pid) {
            $trashed = wp_trash_post($pid);
            if ($trashed !== false && $trashed !== null) {
                $stats['deleted']++;
            } else {
                $stats['errors']++;
            }
        }
    }

    if ($cleanup_legacy) {
        $legacy_products = get_posts([
            'post_type'      => 'product',
            'post_status'    => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_key'       => '_import_brand_line_key',
            'meta_compare'   => 'EXISTS',
        ]);
        foreach ($legacy_products as $pid) {
            $trashed = wp_trash_post($pid);
            if ($trashed !== false && $trashed !== null) {
                $stats['deleted']++;
            } else {
                $stats['errors']++;
            }
        }
    }

    foreach ($items as $item) {
        $name = isset($item['name']) ? wp_strip_all_tags($item['name']) : '';
        $slug = isset($item['slug']) ? sanitize_title($item['slug']) : sanitize_title($name);
        $product_url = isset($item['url']) ? esc_url_raw($item['url']) : '';
        $brand_label = isset($item['brand']) ? wp_strip_all_tags($item['brand']) : ucfirst($brand);
        $capacities = isset($item['capacities']) && is_array($item['capacities']) ? $item['capacities'] : [];
        $description = isset($item['description']) ? wp_kses_post($item['description']) : '';

        if ($name === '') {
            $stats['skipped']++;
            continue;
        }

        $import_key = sanitize_title('official-' . $brand . '-' . $slug);
        $product_id = $find_product($import_key);
        if ($product_id <= 0) {
            $product_id = $find_product_legacy($slug, $name);
        }
        if ($product_id > 0) {
            $product = wc_get_product($product_id);
            if (!$product) {
                $stats['errors']++;
                continue;
            }
            $stats['updated']++;
        } else {
            $product = new WC_Product_Simple();
            $product->set_name($name);
            $product->set_slug($slug);
            $product->set_status('publish');
            $product->set_catalog_visibility('visible');
            $product_id = $product->save();
            if (!$product_id) {
                $stats['errors']++;
                continue;
            }
            $stats['created']++;
        }

        $product->set_name($name);
        $product->set_slug($slug);
        $product->set_description($description);
        $product->set_short_description('');

        $name_norm = my_theme_normalize_search_text($name);
        $is_putty_name = (strpos($name_norm, 'bot tret') !== false || strpos($name_norm, 'putty') !== false);

        $pack_labels_raw = [];
        foreach ($capacities as $cap) {
            if (!isset($cap['label'])) {
                continue;
            }
            $parsed_pack = my_theme_parse_pack_label($cap['label']);
            if ($parsed_pack) {
                $pack_labels_raw[] = $parsed_pack['label'];
            }
        }

        $capacity_labels = my_theme_sort_pack_labels($pack_labels_raw, 'L');
        $weight_labels = my_theme_sort_pack_labels($pack_labels_raw, 'kg');
        if ($is_putty_name) {
            $capacity_labels = [];
        }

        // Parse capacity-price pairs from description and, if needed, official source page.
        $price_map = my_theme_extract_pack_price_map_from_text($description, $is_putty_name);
        if (empty($price_map) && $product_url !== '') {
            $price_map = my_theme_fetch_pack_price_map_from_source_url($product_url, $is_putty_name);
        }

        if (!empty($price_map)) {
            $map_labels = array_keys($price_map);
            $map_capacity = my_theme_sort_pack_labels($map_labels, 'L');
            $map_weight = my_theme_sort_pack_labels($map_labels, 'kg');
            if ($is_putty_name) {
                $map_capacity = [];
            }
            if (!empty($map_capacity)) {
                $capacity_labels = array_values(array_unique(array_merge($capacity_labels, $map_capacity)));
                $capacity_labels = my_theme_sort_pack_labels($capacity_labels, 'L');
            }
            if (!empty($map_weight)) {
                $weight_labels = array_values(array_unique(array_merge($weight_labels, $map_weight)));
                $weight_labels = my_theme_sort_pack_labels($weight_labels, 'kg');
            }
        }

        if (!empty($price_map)) {
            uksort($price_map, function ($a, $b) {
                $pa = my_theme_parse_pack_label($a);
                $pb = my_theme_parse_pack_label($b);
                if (!$pa || !$pb) {
                    return strcmp($a, $b);
                }
                $unit_rank = ['L' => 0, 'kg' => 1];
                $ra = $unit_rank[$pa['unit']] ?? 9;
                $rb = $unit_rank[$pb['unit']] ?? 9;
                if ($ra !== $rb) {
                    return ($ra < $rb) ? -1 : 1;
                }
                if ((float) $pa['value'] === (float) $pb['value']) {
                    return strcmp($a, $b);
                }
                return ((float) $pa['value'] < (float) $pb['value']) ? -1 : 1;
            });
            $map_parts = [];
            foreach ($price_map as $c => $p) {
                $map_parts[] = $c . ':' . $p;
            }
            $product->update_meta_data('_capacity_price_map', implode(' | ', $map_parts));
            // base price = min price
            $product->set_regular_price(min(array_values($price_map)));
            $product->set_price(min(array_values($price_map)));
        } else {
            $product->delete_meta_data('_capacity_price_map');
        }

        if (!empty($capacity_labels)) {
            $product->update_meta_data('_display_capacity_list', implode(' | ', array_unique($capacity_labels)));
        } else {
            $product->delete_meta_data('_display_capacity_list');
        }
        if (!empty($weight_labels)) {
            $product->update_meta_data('_display_weight_list', implode(' | ', array_unique($weight_labels)));
            $first_weight = my_theme_parse_pack_label($weight_labels[0]);
            if ($first_weight && $first_weight['unit'] === 'kg') {
                $product->set_weight($first_weight['value']);
            }
        } else {
            $product->delete_meta_data('_display_weight_list');
            $product->set_weight('');
        }
        $product->delete_meta_data('_import_brand_line_key');

        // Attach capacity taxonomy
        if (!empty($capacity_labels) && taxonomy_exists($taxonomy_capacity)) {
            $term_names = [];
            foreach ($capacity_labels as $c) {
                $term_id = $ensure_term($c, $taxonomy_capacity);
                if ($term_id > 0) {
                    $term_names[] = $c;
                }
            }
            if (!empty($term_names)) {
                wp_set_object_terms($product_id, $term_names, $taxonomy_capacity, false);
            }
        }

        // Set brand term
        if ($taxonomy_brand !== '') {
            $ensure_term($brand_label, $taxonomy_brand);
            wp_set_object_terms($product_id, [$brand_label], $taxonomy_brand, false);
        }

        if (function_exists('my_theme_set_product_primary_category_by_guess')) {
            my_theme_set_product_primary_category_by_guess($product_id, $name . ' ' . wp_strip_all_tags($description), true);
        }

        // Featured image
        $featured_id = (int) get_post_thumbnail_id($product_id);
        $should_replace_image = !empty($item['image']) && ($force_image || !$keep_existing_image || $featured_id <= 0);
        if ($should_replace_image) {
            $new_img_id = my_theme_download_remote_image($item['image'], $product_id, $import_key);
            if ($new_img_id > 0) {
                set_post_thumbnail($product_id, $new_img_id);
            }
        }

        update_post_meta($product_id, '_official_import_key', $import_key);
        if ($product_url !== '') {
            update_post_meta($product_id, '_official_source_url', $product_url);
        }

        $product->save();
    }

    wp_die(
        'Official import finished. Deleted: ' . intval($stats['deleted']) .
        ' | Created: ' . intval($stats['created']) .
        ' | Updated: ' . intval($stats['updated']) .
        ' | Skipped: ' . intval($stats['skipped']) .
        ' | Errors: ' . intval($stats['errors'])
    );
});

// Auto-assign product categories from product name/description.
// Run: /wp-admin/?normalize_product_categories=1 or /wp-admin/?normalize_product_categories=1&force=1
add_action('admin_init', function () {
    if (!is_admin() || empty($_GET['normalize_product_categories'])) {
        return;
    }
    if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
        wp_die('Permission denied.');
    }

    @set_time_limit(0);
    @ini_set('memory_limit', '512M');

    $force = !empty($_GET['force']);
    $ids = get_posts([
        'post_type'      => 'product',
        'post_status'    => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);

    $updated = 0;
    $skipped = 0;
    foreach ($ids as $pid) {
        $product = wc_get_product($pid);
        if (!$product instanceof WC_Product) {
            $skipped++;
            continue;
        }
        $source = trim($product->get_name() . ' ' . wp_strip_all_tags((string) $product->get_description()) . ' ' . wp_strip_all_tags((string) $product->get_short_description()));
        if ($source === '') {
            $skipped++;
            continue;
        }
        if (my_theme_set_product_primary_category_by_guess($pid, $source, $force)) {
            $updated++;
        } else {
            $skipped++;
        }
    }

    wp_die(
        'Product category normalization finished. Updated: ' . intval($updated) .
        ' | Skipped: ' . intval($skipped) .
        ' | Force: ' . ($force ? 'yes' : 'no')
    );
});

// Normalize pack data (lit/kg + map price) after import.
// Run: /wp-admin/?normalize_pack_data=1 or /wp-admin/?normalize_pack_data=1&official_only=1
add_action('admin_init', function () {
    if (!is_admin() || empty($_GET['normalize_pack_data'])) {
        return;
    }
    if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
        wp_die('Permission denied.');
    }

    @set_time_limit(0);
    @ini_set('memory_limit', '512M');

    $official_only = !empty($_GET['official_only']);
    $query_args = [
        'post_type'      => 'product',
        'post_status'    => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ];
    if ($official_only) {
        $query_args['meta_key'] = '_official_import_key';
        $query_args['meta_compare'] = 'EXISTS';
    }
    $ids = get_posts($query_args);

    $updated = 0;
    $skipped = 0;
    foreach ($ids as $pid) {
        $product = wc_get_product($pid);
        if (!$product instanceof WC_Product) {
            $skipped++;
            continue;
        }

        $groups = my_theme_get_product_pack_groups($product);
        $cap_labels = $groups['capacity'];
        $weight_labels = $groups['weight'];

        if (!empty($cap_labels)) {
            $product->update_meta_data('_display_capacity_list', implode(' | ', $cap_labels));
        } else {
            $product->delete_meta_data('_display_capacity_list');
        }

        if (!empty($weight_labels)) {
            $product->update_meta_data('_display_weight_list', implode(' | ', $weight_labels));
            $first_weight = my_theme_parse_pack_label($weight_labels[0]);
            if ($first_weight && $first_weight['unit'] === 'kg') {
                $product->set_weight($first_weight['value']);
            }
        } else {
            $product->delete_meta_data('_display_weight_list');
            $product->set_weight('');
        }

        $price_map = my_theme_get_pack_price_map_for_display($product);
        if (!empty($price_map)) {
            $map_parts = [];
            foreach ($price_map as $size_label => $price_value) {
                $map_parts[] = $size_label . ':' . (float) $price_value;
            }
            $product->update_meta_data('_capacity_price_map', implode(' | ', $map_parts));
            $min_price = min(array_values($price_map));
            $product->set_regular_price($min_price);
            $product->set_price($min_price);
        }

        $product->save();
        $updated++;
    }

    wp_die(
        'Pack data normalized. Updated: ' . intval($updated) .
        ' | Skipped: ' . intval($skipped) .
        ' | Official only: ' . ($official_only ? 'yes' : 'no')
    );
});

// Backfill official products with capacity-price map from stored description/source URL.
// Run: /wp-admin/?backfill_official_pack_prices=1
add_action('admin_init', function () {
    if (!is_admin() || empty($_GET['backfill_official_pack_prices'])) {
        return;
    }
    if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
        wp_die('Permission denied.');
    }

    @set_time_limit(0);
    @ini_set('memory_limit', '512M');

    $force = !empty($_GET['force']);
    $dry_run = !empty($_GET['dry_run']);
    $ids = get_posts([
        'post_type'      => 'product',
        'post_status'    => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_key'       => '_official_import_key',
        'meta_compare'   => 'EXISTS',
    ]);

    $checked = 0;
    $updated = 0;
    $skipped = 0;
    $errors = 0;
    foreach ($ids as $pid) {
        $product = wc_get_product($pid);
        if (!$product instanceof WC_Product) {
            $errors++;
            continue;
        }
        $checked++;

        $existing_map = my_theme_get_pack_price_map_for_display($product);
        if (!$force && !empty($existing_map)) {
            $skipped++;
            continue;
        }

        $is_putty = my_theme_is_putty_product($product);
        $description = (string) $product->get_description();
        $source_url = (string) get_post_meta($pid, '_official_source_url', true);

        $price_map = my_theme_extract_pack_price_map_from_text($description, $is_putty);
        if (empty($price_map) && $source_url !== '') {
            $price_map = my_theme_fetch_pack_price_map_from_source_url($source_url, $is_putty);
        }

        if (empty($price_map)) {
            $skipped++;
            continue;
        }

        if (!$dry_run) {
            $map_parts = [];
            foreach ($price_map as $size_label => $price_value) {
                $map_parts[] = $size_label . ':' . (float) $price_value;
            }
            $product->update_meta_data('_capacity_price_map', implode(' | ', $map_parts));

            $size_labels = array_keys($price_map);
            $capacity_labels = my_theme_sort_pack_labels($size_labels, 'L');
            $weight_labels = my_theme_sort_pack_labels($size_labels, 'kg');

            if (!empty($capacity_labels)) {
                $product->update_meta_data('_display_capacity_list', implode(' | ', $capacity_labels));
            } else {
                $product->delete_meta_data('_display_capacity_list');
            }

            if (!empty($weight_labels)) {
                $product->update_meta_data('_display_weight_list', implode(' | ', $weight_labels));
                $first_weight = my_theme_parse_pack_label($weight_labels[0]);
                if ($first_weight && $first_weight['unit'] === 'kg') {
                    $product->set_weight($first_weight['value']);
                }
            } else {
                $product->delete_meta_data('_display_weight_list');
            }

            $min_price = min(array_values($price_map));
            if ($min_price > 0) {
                $product->set_regular_price($min_price);
                $product->set_price($min_price);
            }
            $product->save();
        }

        $updated++;
    }

    my_theme_flush_product_cache_fragments(0);

    wp_die(
        'Official pack price backfill finished. Checked: ' . intval($checked) .
        ' | Updated: ' . intval($updated) .
        ' | Skipped: ' . intval($skipped) .
        ' | Errors: ' . intval($errors) .
        ' | Dry run: ' . ($dry_run ? 'yes' : 'no') .
        ' | Force: ' . ($force ? 'yes' : 'no')
    );
});

// Audit danh sách sản phẩm simple có quy cách nhưng chưa có map giá dung tích/khối lượng.
// Run: /wp-admin/?pack_price_audit=1 or /wp-admin/?pack_price_audit=1&official_only=1
add_action('admin_init', function () {
    if (!is_admin() || empty($_GET['pack_price_audit'])) {
        return;
    }
    if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
        wp_die('Permission denied.');
    }

    $official_only = !empty($_GET['official_only']);
    $query_args = [
        'post_type'      => 'product',
        'post_status'    => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ];
    if ($official_only) {
        $query_args['meta_key'] = '_official_import_key';
        $query_args['meta_compare'] = 'EXISTS';
    }

    $ids = get_posts($query_args);
    $rows = [];
    $missing_count = 0;

    foreach ($ids as $pid) {
        $product = wc_get_product($pid);
        if (!$product instanceof WC_Product || !$product->is_type('simple')) {
            continue;
        }

        $map = my_theme_get_pack_price_map_for_display($product);
        $groups = my_theme_get_product_pack_groups($product);
        $capacity = isset($groups['capacity']) ? (array) $groups['capacity'] : [];
        $weight = isset($groups['weight']) ? (array) $groups['weight'] : [];

        if (!empty($map) || (empty($capacity) && empty($weight))) {
            continue;
        }

        $missing_count++;
        $category = my_theme_get_product_primary_category_label($product);
        $rows[] = [
            'id' => (int) $pid,
            'name' => $product->get_name(),
            'category' => $category,
            'capacity' => implode(' | ', $capacity),
            'weight' => implode(' | ', $weight),
            'edit_url' => admin_url('post.php?post=' . (int) $pid . '&action=edit'),
        ];
    }

    header('Content-Type: text/plain; charset=utf-8');
    echo "PACK PRICE AUDIT\n";
    echo "Official only: " . ($official_only ? 'yes' : 'no') . "\n";
    echo "Missing map count: " . intval($missing_count) . "\n\n";
    echo "ID\tCategory\tName\tCapacity\tWeight\tEdit URL\n";
    foreach ($rows as $row) {
        echo $row['id'] . "\t" .
            $row['category'] . "\t" .
            $row['name'] . "\t" .
            $row['capacity'] . "\t" .
            $row['weight'] . "\t" .
            $row['edit_url'] . "\n";
    }
    exit;
});

// WooCommerce single product layout: full-width wrapper and no sidebar.
if (!function_exists('my_theme_render_single_product_meta_clean')) {
    function my_theme_render_single_product_meta_clean()
    {
        global $product;
        if (!$product instanceof WC_Product) {
            return;
        }

        $brand = my_theme_get_product_brand_label($product);
        $category = my_theme_get_product_primary_category_label($product);

        if ($brand === '' && $category === '') {
            return;
        }

        echo '<div class="product_meta product_meta--clean">';
        if ($brand !== '') {
            echo '<span class="meta-line"><strong>Thương hiệu:</strong> ' . esc_html($brand) . '</span>';
        }
        if ($category !== '') {
            echo '<span class="meta-line"><strong>Danh mục:</strong> ' . esc_html($category) . '</span>';
        }
        echo '</div>';
    }
}

add_action('woocommerce_single_product_summary', 'my_theme_render_single_product_meta_clean', 40);

if (!function_exists('my_theme_render_single_contact_actions')) {
    function my_theme_render_single_contact_actions()
    {
        if (!function_exists('is_product') || !is_product()) {
            return;
        }

        global $product;
        if (!$product instanceof WC_Product) {
            return;
        }

        // Không render khi WooCommerce đã hiển thị form mua hàng chuẩn.
        if ($product->is_in_stock() && $product->is_purchasable()) {
            return;
        }

        $phone_number = '0944857999';
        $zalo_url = 'https://zalo.me/0944857999';
        $quote_url = home_url('/lien-he');

        echo '<div class="single-product-actions single-product-actions--contact">';
        echo '<a class="btn btn-primary" href="tel:' . esc_attr($phone_number) . '">Gọi báo giá</a>';
        echo '<a class="btn btn-outline" href="' . esc_url($zalo_url) . '" target="_blank" rel="noopener">Zalo tư vấn</a>';
        echo '<a class="btn btn-accent" href="' . esc_url($quote_url) . '">Gửi yêu cầu</a>';
        echo '</div>';
    }
}

add_action('woocommerce_single_product_summary', 'my_theme_render_single_contact_actions', 31);

add_action('wp', function () {
    if (!function_exists('is_product') || !is_product()) {
        return;
    }

    // Disable WooCommerce sidebar only on single product pages.
    remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);
    // Keep single product page clean: no tabs and no default related hook.
    remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);
    remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
});

add_filter('woocommerce_show_sidebar', function ($show_sidebar) {
    if (function_exists('is_product') && is_product()) {
        return false;
    }
    return $show_sidebar;
});

add_filter('comments_open', function ($open, $post_id) {
    if (get_post_type($post_id) === 'product') {
        return false;
    }
    return $open;
}, 10, 2);

add_filter('woocommerce_product_tabs', function ($tabs) {
    if (function_exists('is_product') && is_product()) {
        return [];
    }
    return $tabs;
}, 20);

// Reduce single-product front-end load by disabling heavy gallery features.
add_filter('woocommerce_single_product_zoom_enabled', '__return_false');
add_filter('woocommerce_single_product_flexslider_enabled', '__return_false');
add_filter('woocommerce_single_product_photoswipe_enabled', '__return_false');
add_action('wp_enqueue_scripts', function () {
    if (!function_exists('is_product') || !is_product()) {
        return;
    }
    wp_dequeue_script('zoom');
    wp_dequeue_script('flexslider');
    wp_dequeue_script('photoswipe');
    wp_dequeue_script('photoswipe-ui-default');
    wp_dequeue_style('photoswipe');
    wp_dequeue_style('photoswipe-default-skin');
}, 99);

// Fix imported product titles/slugs from _import_brand_line_key. Run once with /wp-admin/?fix_titles=1
add_action('admin_init', function () {
    if (!is_admin() || empty($_GET['fix_titles'])) {
        return;
    }

    if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
        wp_die('Permission denied.');
    }

    $brand_map = [
        'dulux'  => 'Dulux',
        'jotun'  => 'Jotun',
        'nippon' => 'Nippon',
        'kova'   => 'Kova',
    ];
    $to_lower = function ($value) {
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    };

    $format_line = function ($line_slug) {
        $line_slug = trim((string) $line_slug, "- \t\n\r\0\x0B");
        if ($line_slug === '') {
            return '';
        }

        $tokens = preg_split('/[-\s]+/u', $line_slug);
        $token_map = [
            'noi'          => 'Nội',
            'ngoai'        => 'Ngoại',
            'that'         => 'Thất',
            'chuyen'       => 'Chuyên',
            'dung'         => 'Dụng',
            'lot'          => 'Lót',
            'chong'        => 'Chống',
            'kiem'         => 'Kiềm',
            'de'           => 'Dễ',
            'lau'          => 'Lau',
            'chui'         => 'Chùi',
            'tran'         => 'Trần',
            'tuong'        => 'Tường',
            'easyclean'    => 'EasyClean',
            'weathershield'=> 'Weathershield',
            'odour'        => 'Odour',
            'less'         => 'Less',
            'jotashield'   => 'Jotashield',
            'maxilite'     => 'Maxilite',
        ];
        $pretty = [];
        foreach ($tokens as $token) {
            if ($token === '') {
                continue;
            }
            $token_l = strtolower($token);
            if (isset($token_map[$token_l])) {
                $pretty[] = $token_map[$token_l];
                continue;
            }
            if (preg_match('/^[a-z]{1,4}\d+[a-z0-9]*$/i', $token)) {
                $pretty[] = strtoupper($token);
                continue;
            }
            $pretty[] = ucfirst(strtolower($token));
        }

        return trim(implode(' ', $pretty));
    };

    $parse_key = function ($key) use ($brand_map) {
        $brand_slug = '';
        $type_label = '';
        $line_slug = '';

        if (preg_match('/^(dulux|jotun|nippon|kova)-(bot-tret|son)-(.+)$/', $key, $m)) {
            $brand_slug = $m[1];
            $type_label = ($m[2] === 'bot-tret') ? 'Bột trét' : 'Sơn';
            $line_slug = $m[3];
        } elseif (preg_match('/^(dulux|jotun|nippon|kova)-(.+)$/', $key, $m)) {
            $brand_slug = $m[1];
            $rest = $m[2];

            if (strpos($rest, 'bot-tret-') === 0) {
                $type_label = 'Bột trét';
                $line_slug = substr($rest, 9);
            } elseif (strpos($rest, 'son-') === 0) {
                $type_label = 'Sơn';
                $line_slug = substr($rest, 4);
            } else {
                $line_slug = $rest;
            }
        }

        if ($brand_slug === '' || !isset($brand_map[$brand_slug])) {
            return ['', '', ''];
        }
        if ($type_label === '') {
            $type_label = 'Sơn';
        }
        $line_slug = trim((string) $line_slug, "- \t\n\r\0\x0B");
        if ($line_slug === '') {
            $line_slug = 'dong-chuan';
        }

        return [$brand_slug, $type_label, sanitize_title($line_slug)];
    };

    $derive_from_title = function ($title) use ($brand_map, $to_lower) {
        $title_plain = remove_accents((string) $title);
        $title_lower = $to_lower($title_plain);
        $title_norm = preg_replace('/[_\-]+/u', ' ', $title_lower);
        $title_norm = preg_replace('/\s+/u', ' ', trim($title_norm));

        $brand_slug = '';
        foreach (array_keys($brand_map) as $b) {
            if (preg_match('/(^|\s)' . preg_quote($b, '/') . '(\s|$)/u', $title_norm)) {
                $brand_slug = $b;
                break;
            }
        }
        if ($brand_slug === '') {
            return ['', '', ''];
        }

        $type_label = (strpos($title_norm, 'bot tret') !== false || strpos($title_norm, 'putty') !== false) ? 'Bột trét' : 'Sơn';
        $type_tokens = ($type_label === 'Bột trét') ? ['bot tret', 'bot', 'tret', 'putty'] : ['son', 'paint'];

        $line = $title_norm;
        foreach ($type_tokens as $tk) {
            $line = preg_replace('/(^|\s)' . preg_quote($tk, '/') . '(\s|$)/u', ' ', $line);
        }
        $line = preg_replace('/(^|\s)' . preg_quote($brand_slug, '/') . '(\s|$)/u', ' ', $line);
        $line = preg_replace('/\b\d+(?:[.,]\d+)?\s*(l|kg)\b/iu', ' ', $line);
        $line = preg_replace('/\s+/u', ' ', trim($line));

        $line_slug = sanitize_title($line);
        if ($line_slug === '') {
            $line_slug = 'dong-chuan';
        }

        return [$brand_slug, $type_label, $line_slug];
    };

    $ids = get_posts([
        'post_type'      => 'product',
        'post_status'    => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);

    $updated = 0;
    $skipped = 0;
    $meta_fixed = 0;

    foreach ($ids as $pid) {
        $key = (string) get_post_meta($pid, '_import_brand_line_key', true);
        if ($key !== '') {
            [$brand_slug, $type_label, $line_slug] = $parse_key($key);
        } else {
            [$brand_slug, $type_label, $line_slug] = ['', '', ''];
        }

        if ($brand_slug === '' || $line_slug === '') {
            [$brand_slug, $type_label, $line_slug] = $derive_from_title((string) get_the_title($pid));
        }

        if ($brand_slug === '' || $line_slug === '' || !isset($brand_map[$brand_slug])) {
            $skipped++;
            continue;
        }

        $target_key = sanitize_title($brand_slug . '-' . (($type_label === 'Bột trét') ? 'bot-tret' : 'son') . '-' . $line_slug);
        if ($key !== $target_key) {
            update_post_meta($pid, '_import_brand_line_key', $target_key);
            $meta_fixed++;
        }

        $brand_label = $brand_map[$brand_slug];
        $line_label = $format_line($line_slug);
        $new_title = trim($type_label . ' ' . $brand_label . ' ' . $line_label);

        if ($new_title === '') {
            $skipped++;
            continue;
        }

        $new_slug = sanitize_title($new_title);
        $current_post = get_post($pid);
        if (!$current_post) {
            $skipped++;
            continue;
        }

        $current_title = (string) $current_post->post_title;
        $current_slug = (string) $current_post->post_name;

        if ($current_title === $new_title && $current_slug === $new_slug) {
            $skipped++;
            continue;
        }

        wp_update_post([
            'ID'         => $pid,
            'post_title' => $new_title,
            'post_name'  => $new_slug,
        ]);

        $updated++;
    }

    wp_die(
        'Titles/slugs fixed. Updated: ' . intval($updated) .
        ' | Meta fixed: ' . intval($meta_fixed) .
        ' | Skipped: ' . intval($skipped)
    );
});

// Redirect legacy wrong product URLs to cleaned catalog targets.
add_action('template_redirect', function () {
    if (!is_404()) {
        return;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    if ($request_uri === '') {
        return;
    }
    $request_path = wp_parse_url($request_uri, PHP_URL_PATH);
    $request_path = trim((string) $request_path, '/');
    if ($request_path === '') {
        return;
    }

    $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
    $legacy_map = [
        'thanh-toan' => wc_get_checkout_url(),
        'gio-hang' => wc_get_cart_url(),
        'product/son-dulux-easyclean-noi-that' => home_url('/product/duluxeasycleanlauchuihieuquabematmo/'),
        'product/son-dulux-weathershield-ngoai-that' => home_url('/product/duluxweathershieldbematbong/'),
        'product/son-maxilite-noi-that-5l' => home_url('/product/sonnuocnoithatmaxilitehi-covertudulux/'),
        'product/son-nippon-odour-less-noi-that' => add_query_arg('q', 'nippon', $shop_url),
        'product/bot-tret-kova-noi-that' => add_query_arg('q', 'kova', $shop_url),
        'product/bot-tret-kova-ngoai-that' => add_query_arg('q', 'kova', $shop_url),
        'product/son-kova-ngoai-that-effective-chuyen-dung' => add_query_arg('q', 'kova', $shop_url),
    ];

    if (!isset($legacy_map[$request_path])) {
        return;
    }

    wp_safe_redirect($legacy_map[$request_path], 301);
    exit;
}, 1);
