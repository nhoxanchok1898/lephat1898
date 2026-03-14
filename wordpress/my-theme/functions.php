<?php
/**
 * Functions for My Custom Theme.
 */

$my_theme_modules = [
    'inc/woocommerce.php',
    'inc/performance.php',
    'inc/product-performance.php',
    'inc/archive-support-layouts.php',
    'inc/product-family-layouts.php',
    'inc/product-support-layouts.php',
    'inc/search-assist.php',
    'inc/translations.php',
    'inc/customer-leads.php',
];
foreach ($my_theme_modules as $my_theme_module_rel) {
    $my_theme_module_file = get_theme_file_path($my_theme_module_rel);
    if (is_string($my_theme_module_file) && file_exists($my_theme_module_file)) {
        require_once $my_theme_module_file;
    }
}

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

// Checkout gọn hơn cho luồng mua nhanh trong nước.
add_filter('default_checkout_billing_country', function () {
    return 'VN';
});

add_filter('woocommerce_checkout_fields', function ($fields) {
    if (!is_array($fields)) {
        return $fields;
    }

    if (isset($fields['billing']['billing_country'])) {
        unset($fields['billing']['billing_country']);
    }

    foreach (['billing_company', 'billing_address_2', 'billing_postcode', 'billing_state'] as $redundant_key) {
        if (isset($fields['billing'][$redundant_key])) {
            unset($fields['billing'][$redundant_key]);
        }
    }
    foreach (['shipping_company', 'shipping_address_2', 'shipping_postcode', 'shipping_state'] as $redundant_key) {
        if (isset($fields['shipping'][$redundant_key])) {
            unset($fields['shipping'][$redundant_key]);
        }
    }
    if (isset($fields['shipping']['shipping_country'])) {
        unset($fields['shipping']['shipping_country']);
    }

    if (isset($fields['order']['order_comments'])) {
        $fields['order']['order_comments']['label'] = 'Ghi chú đơn hàng';
        $fields['order']['order_comments']['placeholder'] = 'Ví dụ: giao giờ hành chính, gọi trước khi giao.';
    }

    return $fields;
}, 40);

add_filter('woocommerce_order_button_text', function () {
    return 'Đặt hàng';
});

add_action('woocommerce_before_checkout_form', function () {
    if (!function_exists('WC') || !WC() || empty(WC()->customer)) {
        return;
    }
    WC()->customer->set_billing_country('VN');
    WC()->customer->set_shipping_country('VN');
}, 1);

// Đổi nhãn "Sale" sang tiếng Việt.
add_filter('woocommerce_sale_flash', function () {
    return '<span class="onsale">Giảm giá</span>';
});

if (!function_exists('my_theme_get_global_sale_config')) {
    function my_theme_get_global_sale_config()
    {
        $config = [
            'enabled' => true,
            'percent' => 10.0,
            'label' => 'Khuyến mãi toàn site',
        ];

        return apply_filters('my_theme_global_sale_config', $config);
    }
}

if (!function_exists('my_theme_global_sale_is_enabled')) {
    function my_theme_global_sale_is_enabled()
    {
        $config = my_theme_get_global_sale_config();
        return !empty($config['enabled']) && (float) ($config['percent'] ?? 0) > 0;
    }
}

if (!function_exists('my_theme_get_global_sale_percent')) {
    function my_theme_get_global_sale_percent()
    {
        $config = my_theme_get_global_sale_config();
        return max(0.0, min(95.0, (float) ($config['percent'] ?? 0)));
    }
}

if (!function_exists('my_theme_get_global_sale_price_from_regular')) {
    function my_theme_get_global_sale_price_from_regular($regular_price = 0.0)
    {
        $regular_price = (float) $regular_price;
        if ($regular_price <= 0 || !my_theme_global_sale_is_enabled()) {
            return 0.0;
        }

        $percent = my_theme_get_global_sale_percent();
        if ($percent <= 0) {
            return 0.0;
        }

        $sale_price = round($regular_price * ((100 - $percent) / 100), 0);
        if ($sale_price <= 0 || $sale_price >= $regular_price) {
            return 0.0;
        }

        return (float) $sale_price;
    }
}

if (!function_exists('my_theme_get_product_raw_regular_price')) {
    function my_theme_get_product_raw_regular_price($product)
    {
        if (!$product instanceof WC_Product) {
            return 0.0;
        }

        $regular_price = (float) $product->get_regular_price('edit');
        if ($regular_price > 0) {
            return $regular_price;
        }

        return (float) $product->get_price('edit');
    }
}

if (!function_exists('my_theme_get_product_raw_sale_price')) {
    function my_theme_get_product_raw_sale_price($product)
    {
        if (!$product instanceof WC_Product) {
            return 0.0;
        }

        return (float) $product->get_sale_price('edit');
    }
}

if (!function_exists('my_theme_product_has_manual_sale_price')) {
    function my_theme_product_has_manual_sale_price($product)
    {
        if (!$product instanceof WC_Product) {
            return false;
        }

        $regular_price = my_theme_get_product_raw_regular_price($product);
        $sale_price = my_theme_get_product_raw_sale_price($product);

        return $regular_price > 0 && $sale_price > 0 && $sale_price < $regular_price;
    }
}

if (!function_exists('my_theme_get_product_effective_sale_price')) {
    function my_theme_get_product_effective_sale_price($product)
    {
        if (!$product instanceof WC_Product) {
            return 0.0;
        }

        $regular_price = my_theme_get_product_raw_regular_price($product);
        if ($regular_price <= 0) {
            return 0.0;
        }

        if (my_theme_product_has_manual_sale_price($product)) {
            $sale_price = my_theme_get_product_raw_sale_price($product);
            return ($sale_price > 0 && $sale_price < $regular_price) ? $sale_price : 0.0;
        }

        return my_theme_get_global_sale_price_from_regular($regular_price);
    }
}

if (!function_exists('my_theme_get_runtime_price_override')) {
    function my_theme_get_runtime_price_override($product)
    {
        if (!$product instanceof WC_Product) {
            return null;
        }

        $registry = isset($GLOBALS['my_theme_runtime_price_overrides']) && is_array($GLOBALS['my_theme_runtime_price_overrides'])
            ? $GLOBALS['my_theme_runtime_price_overrides']
            : [];
        $override_key = spl_object_hash($product);
        $override = isset($registry[$override_key]) && is_array($registry[$override_key])
            ? $registry[$override_key]
            : null;
        if (!is_array($override)) {
            return null;
        }

        $override_price = isset($override['price']) ? (float) $override['price'] : 0.0;
        if ($override_price <= 0) {
            return null;
        }

        $override_regular = isset($override['regular_price']) ? (float) $override['regular_price'] : 0.0;
        if ($override_regular <= 0) {
            $override_regular = $override_price;
        }

        return [
            'price' => $override_price,
            'regular_price' => max($override_price, $override_regular),
        ];
    }
}

if (!function_exists('my_theme_apply_runtime_price_override')) {
    function my_theme_apply_runtime_price_override($product, $price = 0.0, $regular_price = 0.0)
    {
        if (!$product instanceof WC_Product) {
            return;
        }

        if (!isset($GLOBALS['my_theme_runtime_price_overrides']) || !is_array($GLOBALS['my_theme_runtime_price_overrides'])) {
            $GLOBALS['my_theme_runtime_price_overrides'] = [];
        }

        $override_key = spl_object_hash($product);
        $price = (float) $price;
        if ($price <= 0) {
            unset($GLOBALS['my_theme_runtime_price_overrides'][$override_key]);
            return;
        }

        $regular_price = max($price, (float) $regular_price);
        $GLOBALS['my_theme_runtime_price_overrides'][$override_key] = [
            'price' => $price,
            'regular_price' => $regular_price,
        ];
    }
}

add_filter('woocommerce_product_get_sale_price', function ($sale_price, $product) {
    if (!$product instanceof WC_Product) {
        return $sale_price;
    }

    $runtime_override = my_theme_get_runtime_price_override($product);
    if (is_array($runtime_override)) {
        return $runtime_override['regular_price'] > $runtime_override['price']
            ? (string) $runtime_override['price']
            : '';
    }

    $effective_sale_price = my_theme_get_product_effective_sale_price($product);
    if ($effective_sale_price > 0) {
        return (string) $effective_sale_price;
    }

    return $sale_price;
}, 99, 2);

add_filter('woocommerce_product_variation_get_sale_price', function ($sale_price, $product) {
    if (!$product instanceof WC_Product) {
        return $sale_price;
    }

    $runtime_override = my_theme_get_runtime_price_override($product);
    if (is_array($runtime_override)) {
        return $runtime_override['regular_price'] > $runtime_override['price']
            ? (string) $runtime_override['price']
            : '';
    }

    $effective_sale_price = my_theme_get_product_effective_sale_price($product);
    if ($effective_sale_price > 0) {
        return (string) $effective_sale_price;
    }

    return $sale_price;
}, 99, 2);

add_filter('woocommerce_product_get_price', function ($price, $product) {
    if (!$product instanceof WC_Product) {
        return $price;
    }

    $runtime_override = my_theme_get_runtime_price_override($product);
    if (is_array($runtime_override)) {
        return (string) $runtime_override['price'];
    }

    $effective_sale_price = my_theme_get_product_effective_sale_price($product);
    if ($effective_sale_price > 0) {
        return (string) $effective_sale_price;
    }

    return $price;
}, 99, 2);

add_filter('woocommerce_product_variation_get_price', function ($price, $product) {
    if (!$product instanceof WC_Product) {
        return $price;
    }

    $runtime_override = my_theme_get_runtime_price_override($product);
    if (is_array($runtime_override)) {
        return (string) $runtime_override['price'];
    }

    $effective_sale_price = my_theme_get_product_effective_sale_price($product);
    if ($effective_sale_price > 0) {
        return (string) $effective_sale_price;
    }

    return $price;
}, 99, 2);

add_filter('woocommerce_product_get_regular_price', function ($regular_price, $product) {
    if (!$product instanceof WC_Product) {
        return $regular_price;
    }

    $runtime_override = my_theme_get_runtime_price_override($product);
    if (is_array($runtime_override)) {
        return (string) $runtime_override['regular_price'];
    }

    return $regular_price;
}, 99, 2);

add_filter('woocommerce_product_variation_get_regular_price', function ($regular_price, $product) {
    if (!$product instanceof WC_Product) {
        return $regular_price;
    }

    $runtime_override = my_theme_get_runtime_price_override($product);
    if (is_array($runtime_override)) {
        return (string) $runtime_override['regular_price'];
    }

    return $regular_price;
}, 99, 2);

add_filter('woocommerce_product_is_on_sale', function ($is_on_sale, $product) {
    if ($is_on_sale) {
        return true;
    }

    return ($product instanceof WC_Product) ? my_theme_product_has_active_sale($product) : $is_on_sale;
}, 99, 2);

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
    if (!function_exists('wc_get_page_id')) {
        return $title;
    }
    if ($id == wc_get_page_id('shop')) return 'Sản phẩm';
    if ($id == wc_get_page_id('cart')) return 'Giỏ hàng';
    if ($id == wc_get_page_id('checkout')) return 'Thanh toán';
    if ($id == wc_get_page_id('myaccount')) return 'Tài khoản';
    return $title;
}, 10, 2);

if (!function_exists('my_theme_render_wc_admin_help_tabs_safe')) {
    function my_theme_render_wc_admin_help_tabs_safe($screen = null)
    {
        if (!function_exists('wc_get_screen_ids')) {
            return;
        }

        if (!$screen instanceof WP_Screen && function_exists('get_current_screen')) {
            $screen = get_current_screen();
        }
        if (!$screen instanceof WP_Screen || !method_exists($screen, 'add_help_tab')) {
            return;
        }

        $screen_id = isset($screen->id) ? (string) $screen->id : '';
        $wc_screen_ids = array_map('strval', (array) wc_get_screen_ids());
        if ($screen_id === '' || !in_array($screen_id, $wc_screen_ids, true)) {
            return;
        }

        $screen->add_help_tab([
            'id' => 'woocommerce_support_tab',
            'title' => __('Help &amp; Support', 'woocommerce'),
            'content' =>
                '<h2>' . __('Help &amp; Support', 'woocommerce') . '</h2>' .
                '<p>' . sprintf(
                    __('Should you need help understanding, using, or extending WooCommerce, <a href="%s">please read our documentation</a>. You will find all kinds of resources including snippets, tutorials and much more.', 'woocommerce'),
                    'https://woocommerce.com/documentation/plugins/woocommerce/?utm_source=helptab&utm_medium=product&utm_content=docs&utm_campaign=woocommerceplugin'
                ) . '</p>' .
                '<p>' . sprintf(
                    __('For further assistance with WooCommerce core, use the <a href="%1$s">community forum</a>. For help with premium extensions sold on WooCommerce.com, <a href="%2$s">open a support request at WooCommerce.com</a>.', 'woocommerce'),
                    'https://wordpress.org/support/plugin/woocommerce',
                    'https://woocommerce.com/my-account/create-a-ticket/?utm_source=helptab&utm_medium=product&utm_content=tickets&utm_campaign=woocommerceplugin'
                ) . '</p>' .
                '<p>' . __('Before asking for help, we recommend checking the system status page to identify any problems with your configuration.', 'woocommerce') . '</p>' .
                '<p><a href="' . admin_url('admin.php?page=wc-status') . '" class="button button-primary">' . __('System status', 'woocommerce') . '</a> <a href="https://wordpress.org/support/plugin/woocommerce" class="button">' . __('Community forum', 'woocommerce') . '</a> <a href="https://woocommerce.com/my-account/create-a-ticket/?utm_source=helptab&utm_medium=product&utm_content=tickets&utm_campaign=woocommerceplugin" class="button">' . __('WooCommerce.com support', 'woocommerce') . '</a></p>',
        ]);

        $screen->add_help_tab([
            'id' => 'woocommerce_bugs_tab',
            'title' => __('Found a bug?', 'woocommerce'),
            'content' =>
                '<h2>' . __('Found a bug?', 'woocommerce') . '</h2>' .
                '<p>' . sprintf(
                    __('If you find a bug within WooCommerce core you can create a ticket via <a href="%1$s">GitHub issues</a>. Ensure you read the <a href="%2$s">contribution guide</a> prior to submitting your report. To help us solve your issue, please be as descriptive as possible and include your <a href="%3$s">system status report</a>.', 'woocommerce'),
                    'https://github.com/woocommerce/woocommerce/issues?state=open',
                    'https://github.com/woocommerce/woocommerce/blob/trunk/.github/CONTRIBUTING.md',
                    admin_url('admin.php?page=wc-status')
                ) . '</p>' .
                '<p><a href="https://github.com/woocommerce/woocommerce/issues/new?assignees=&labels=&template=1-bug-report.yml" class="button button-primary">' . __('Report a bug', 'woocommerce') . '</a> <a href="' . admin_url('admin.php?page=wc-status') . '" class="button">' . __('System status', 'woocommerce') . '</a></p>',
        ]);

        if (method_exists($screen, 'set_help_sidebar')) {
            $screen->set_help_sidebar(
                '<p><strong>' . __('For more information:', 'woocommerce') . '</strong></p>' .
                '<p><a href="https://woocommerce.com/?utm_source=helptab&utm_medium=product&utm_content=about&utm_campaign=woocommerceplugin" target="_blank">' . __('About WooCommerce', 'woocommerce') . '</a></p>' .
                '<p><a href="https://wordpress.org/plugins/woocommerce/" target="_blank">' . __('WordPress.org project', 'woocommerce') . '</a></p>' .
                '<p><a href="https://github.com/woocommerce/woocommerce/" target="_blank">' . __('GitHub project', 'woocommerce') . '</a></p>' .
                '<p><a href="https://woocommerce.com/product-category/themes/?utm_source=helptab&utm_medium=product&utm_content=wcthemes&utm_campaign=woocommerceplugin" target="_blank">' . __('Official themes', 'woocommerce') . '</a></p>' .
                '<p><a href="https://woocommerce.com/product-category/woocommerce-extensions/?utm_source=helptab&utm_medium=product&utm_content=wcextensions&utm_campaign=woocommerceplugin" target="_blank">' . __('Official extensions', 'woocommerce') . '</a></p>'
            );
        }
    }
}

if (!function_exists('my_theme_replace_broken_wc_admin_help_callback')) {
    function my_theme_replace_broken_wc_admin_help_callback($screen = null)
    {
        global $wp_filter;
        $hook = isset($wp_filter['current_screen']) ? $wp_filter['current_screen'] : null;

        static $did_replace = false;
        if ($did_replace) {
            return;
        }
        $did_replace = true;

        if ($hook instanceof WP_Hook && is_array($hook->callbacks)) {
            foreach ($hook->callbacks as $priority => $callbacks) {
                foreach ((array) $callbacks as $callback) {
                    $fn = isset($callback['function']) ? $callback['function'] : null;
                    if (!is_array($fn) || !isset($fn[0], $fn[1])) {
                        continue;
                    }
                    if (!($fn[0] instanceof WC_Admin_Help) || $fn[1] !== 'add_tabs') {
                        continue;
                    }
                    remove_action('current_screen', $fn, (int) $priority);
                }
            }
        }

        my_theme_render_wc_admin_help_tabs_safe($screen);
    }
}
add_action('current_screen', 'my_theme_replace_broken_wc_admin_help_callback', 1);

add_filter('wc_empty_cart_message', function () {
    return 'Giỏ hàng của bạn đang trống.';
});

add_filter('woocommerce_return_to_shop_text', function () {
    return 'Quay lại cửa hàng';
});

add_filter('woocommerce_checkout_coupon_message', function () {
    return 'Bạn có mã giảm giá? <a href="#" class="showcoupon">Bấm vào đây để nhập mã</a>';
});

add_filter('woocommerce_order_review_heading', function () {
    return 'Đơn hàng của bạn';
});

add_filter('woocommerce_get_availability_text', function ($availability, $product) {
    if (!$product instanceof WC_Product) {
        return $availability;
    }
    if ($product->is_on_backorder(1)) {
        return 'Cho phép đặt trước';
    }
    if ($product->is_in_stock()) {
        return 'Còn hàng';
    }
    return 'Hết hàng';
}, 20, 2);

add_filter('woocommerce_structured_data_product', function ($markup, $product) {
    if (!is_array($markup) || !$product instanceof WC_Product) {
        return $markup;
    }

    $catalog_profile = function_exists('my_theme_get_product_catalog_profile')
        ? my_theme_get_product_catalog_profile($product)
        : [];
    $display_name = isset($catalog_profile['display_name']) ? trim((string) $catalog_profile['display_name']) : '';
    if ($display_name !== '') {
        $markup['name'] = $display_name;
    }

    $description = trim((string) wp_strip_all_tags($product->get_description()));
    if ($description === '' || (function_exists('my_theme_text_looks_unaccented_vi') && my_theme_text_looks_unaccented_vi($description))) {
        $description = function_exists('my_theme_get_product_card_excerpt')
            ? trim((string) my_theme_get_product_card_excerpt($product, 32))
            : '';
    }
    if ($description !== '') {
        $markup['description'] = wp_strip_all_tags($description);
    }

    if ($product->is_type('simple') && !empty($markup['offers']) && is_array($markup['offers'])) {
        $display_price = function_exists('my_theme_get_default_loop_price')
            ? (float) my_theme_get_default_loop_price($product)
            : (float) $product->get_price();
        $currency = function_exists('get_woocommerce_currency')
            ? (string) get_woocommerce_currency()
            : 'VND';

        if ($display_price > 0) {
            $formatted_price = wc_format_decimal($display_price, wc_get_price_decimals());
            $is_offer_list = array_keys($markup['offers']) === range(0, count($markup['offers']) - 1);

            if ($is_offer_list) {
                foreach ($markup['offers'] as $offer_index => $offer_markup) {
                    if (!is_array($offer_markup)) {
                        continue;
                    }

                    $offer_type = isset($offer_markup['@type']) ? (string) $offer_markup['@type'] : '';
                    if ($offer_type === 'AggregateOffer') {
                        $markup['offers'][$offer_index]['lowPrice'] = $formatted_price;
                        $markup['offers'][$offer_index]['highPrice'] = $formatted_price;
                        $markup['offers'][$offer_index]['priceCurrency'] = $currency;
                        unset($markup['offers'][$offer_index]['price']);
                        continue;
                    }

                    $markup['offers'][$offer_index]['price'] = $formatted_price;
                    $markup['offers'][$offer_index]['priceCurrency'] = $currency;
                    unset($markup['offers'][$offer_index]['lowPrice'], $markup['offers'][$offer_index]['highPrice']);
                }
            } else {
                $offer_type = isset($markup['offers']['@type']) ? (string) $markup['offers']['@type'] : '';
                if ($offer_type === 'AggregateOffer') {
                    $markup['offers']['lowPrice'] = $formatted_price;
                    $markup['offers']['highPrice'] = $formatted_price;
                    $markup['offers']['priceCurrency'] = $currency;
                    unset($markup['offers']['price']);
                } else {
                    $markup['offers']['price'] = $formatted_price;
                    $markup['offers']['priceCurrency'] = $currency;
                    unset($markup['offers']['lowPrice'], $markup['offers']['highPrice']);
                }
            }
        }
    }

    return $markup;
}, 20, 2);

add_filter('woocommerce_get_privacy_policy_text', function ($text) {
    $policy_url = function_exists('get_privacy_policy_url') ? get_privacy_policy_url() : '';
    if (!is_string($policy_url) || trim($policy_url) === '') {
        $policy_url = home_url('/');
    }
    $policy_link = '<a href="' . esc_url($policy_url) . '" class="woocommerce-privacy-policy-link" target="_blank">chính sách bảo mật</a>';
    return '<p>Thông tin cá nhân của bạn chỉ được dùng để xử lý đơn hàng và hỗ trợ trải nghiệm mua sắm theo ' . $policy_link . '.</p>';
});

add_filter('woocommerce_checkout_no_js_message', function () {
    return 'Trình duyệt của bạn đang tắt JavaScript. Vui lòng bấm <em>Cập nhật tổng</em> trước khi đặt hàng để tránh sai lệch số tiền.';
});

add_filter('wc_add_to_cart_message_html', function ($message, $products) {
    if (empty($products) || !is_array($products)) {
        return $message;
    }
    $first_product_id = (int) array_key_first($products);
    $product = $first_product_id > 0 ? wc_get_product($first_product_id) : null;
    if (!$product instanceof WC_Product) {
        return $message;
    }

    $catalog_profile = function_exists('my_theme_get_product_catalog_profile')
        ? my_theme_get_product_catalog_profile($product)
        : [];
    $name = isset($catalog_profile['display_name']) && (string) $catalog_profile['display_name'] !== ''
        ? (string) $catalog_profile['display_name']
        : $product->get_name();
    $cart_link = '<a href="' . esc_url(wc_get_cart_url()) . '" tabindex="1" class="button wc-forward">Xem giỏ hàng</a>';
    return $cart_link . ' &ldquo;' . esc_html($name) . '&rdquo; đã được thêm vào giỏ hàng.';
}, 10, 2);

if (!function_exists('my_theme_add_gallery_link_labels')) {
    function my_theme_add_gallery_link_labels($html, $attachment_id = 0)
    {
        if (!is_string($html) || trim($html) === '' || stripos($html, '<a ') === false) {
            return $html;
        }

        $current_product = null;
        global $product;
        if ($product instanceof WC_Product) {
            $current_product = $product;
        } else {
            $maybe_product = wc_get_product(get_the_ID());
            if ($maybe_product instanceof WC_Product) {
                $current_product = $maybe_product;
            }
        }

        $product_name = '';
        if ($current_product instanceof WC_Product) {
            $catalog_profile = function_exists('my_theme_get_product_catalog_profile')
                ? my_theme_get_product_catalog_profile($current_product)
                : [];
            $product_name = isset($catalog_profile['display_name'])
                ? trim((string) $catalog_profile['display_name'])
                : '';
            if ($product_name === '') {
                $product_name = trim((string) $current_product->get_name());
            }
        }

        $label = ($product_name !== '') ? ('Xem ảnh sản phẩm ' . $product_name) : 'Xem ảnh sản phẩm';
        $label_attr = esc_attr($label);

        if (stripos($html, 'aria-label=') === false) {
            $html = preg_replace('/<a\b(?![^>]*\baria-label=)([^>]*)>/i', '<a$1 aria-label="' . $label_attr . '">', $html, 1);
        }
        if (stripos($html, 'title=') === false) {
            $html = preg_replace('/<a\b(?![^>]*\btitle=)([^>]*)>/i', '<a$1 title="' . $label_attr . '">', $html, 1);
        }

        return $html;
    }
}
add_filter('woocommerce_single_product_image_thumbnail_html', 'my_theme_add_gallery_link_labels', 20, 2);
add_filter('woocommerce_single_product_image_html', 'my_theme_add_gallery_link_labels', 20, 2);

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
            return !in_array($title, [
                'Bài viết',
                'Blog',
                'Tính sơn',
                'FAQ',
                'Hỗ trợ nhanh',
                'Hướng dẫn mua hàng',
                'Giới thiệu',
                'Giới thiệu đại lý',
                'Giá thợ',
                'Giải pháp',
                'Thương hiệu',
                'Danh mục sơn',
                'Danh mục sản phẩm',
                'Chính sách đổi trả',
                'Vận chuyển & giao hàng',
                'Giỏ hàng',
                'Thanh toán',
                'Liên hệ',
            ], true);
        });
        // Loại bỏ mục bị lặp trong menu chính.
        $seen = [];
        $seen_urls = [];
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

            if ((int) $item->menu_item_parent === 0) {
                $item_url = isset($item->url) ? trim((string) $item->url) : '';
                if ($item_url !== '') {
                    $url_parts = wp_parse_url($item_url);
                    $path = isset($url_parts['path']) ? untrailingslashit((string) $url_parts['path']) : '';
                    $query = isset($url_parts['query']) ? trim((string) $url_parts['query']) : '';
                    $url_key = $path === '' ? '/' : $path;
                    if ($query === '' && isset($seen_urls[$url_key])) {
                        continue;
                    }
                    if ($query === '') {
                        $seen_urls[$url_key] = true;
                    }
                }
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

if (!function_exists('my_theme_is_placeholder_blog_post')) {
    function my_theme_is_placeholder_blog_post($post = null)
    {
        $post = get_post($post);
        if (!($post instanceof WP_Post) || $post->post_type !== 'post') {
            return false;
        }

        $post_slug = sanitize_title((string) $post->post_name);
        if ($post_slug === 'hello-world') {
            return true;
        }

        $title_norm = function_exists('my_theme_normalize_search_text')
            ? my_theme_normalize_search_text((string) $post->post_title)
            : strtolower(trim(wp_strip_all_tags((string) $post->post_title)));
        $content_norm = function_exists('my_theme_normalize_search_text')
            ? my_theme_normalize_search_text((string) $post->post_content)
            : strtolower(trim(wp_strip_all_tags((string) $post->post_content)));

        if ((bool) preg_match('/^san pham son mau\\s*\\d+$/', $title_norm)) {
            return true;
        }

        $is_default_title = in_array($title_norm, ['hello world', 'chao tat ca moi nguoi'], true);
        $looks_like_default_post = strpos($content_norm, 'wordpress') !== false
            && (
                strpos($content_norm, 'day la bai viet dau tien') !== false
                || strpos($content_norm, 'edit or delete it') !== false
                || strpos($content_norm, 'sua hoac xoa bai viet nay') !== false
            );

        return $is_default_title && $looks_like_default_post;
    }
}

if (!function_exists('my_theme_get_placeholder_blog_post_ids')) {
    function my_theme_get_placeholder_blog_post_ids()
    {
        $cache_key = 'my_theme_placeholder_blog_post_ids_v1';
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return array_values(array_filter(array_map('intval', $cached)));
        }

        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => ['publish', 'draft', 'pending', 'private', 'future'],
            'orderby' => 'ID',
            'order' => 'ASC',
            'numberposts' => 25,
            'suppress_filters' => true,
        ]);

        $hidden_ids = [];
        foreach ($posts as $post) {
            if (my_theme_is_placeholder_blog_post($post)) {
                $hidden_ids[] = (int) $post->ID;
            }
        }

        set_transient($cache_key, $hidden_ids, 12 * HOUR_IN_SECONDS);
        return $hidden_ids;
    }
}

if (!function_exists('my_theme_get_public_blog_post_count')) {
    function my_theme_get_public_blog_post_count()
    {
        $published_posts = wp_count_posts('post');
        $published_total = ($published_posts && isset($published_posts->publish)) ? (int) $published_posts->publish : 0;
        $hidden_ids = function_exists('my_theme_get_placeholder_blog_post_ids')
            ? my_theme_get_placeholder_blog_post_ids()
            : [];

        if (empty($hidden_ids)) {
            return max(0, $published_total);
        }

        $hidden_published = 0;
        foreach ($hidden_ids as $post_id) {
            if (get_post_status((int) $post_id) === 'publish') {
                $hidden_published++;
            }
        }

        return max(0, $published_total - $hidden_published);
    }
}

if (!function_exists('my_theme_bump_blog_cache_version')) {
    function my_theme_bump_blog_cache_version()
    {
        update_option('my_theme_blog_cache_version', (string) time(), false);
    }
}

if (!function_exists('my_theme_get_page_permalink_by_path_or_template')) {
    function my_theme_get_page_permalink_by_path_or_template($path = '', $template_file = '')
    {
        $path = trim((string) $path, '/');
        $template_file = trim((string) $template_file);
        $cache_key = md5($path . '|' . $template_file);

        static $cache = [];
        if (array_key_exists($cache_key, $cache)) {
            return (string) $cache[$cache_key];
        }

        $permalink = '';
        if ($path !== '') {
            $page = get_page_by_path($path);
            if ($page instanceof WP_Post) {
                $permalink = (string) get_permalink($page);
            }
        }

        if ($permalink === '' && $template_file !== '') {
            $pages = get_pages([
                'meta_key' => '_wp_page_template',
                'meta_value' => $template_file,
                'number' => 1,
            ]);

            if (!empty($pages) && $pages[0] instanceof WP_Post) {
                $permalink = (string) get_permalink($pages[0]);
            }
        }

        $cache[$cache_key] = $permalink;
        return $permalink;
    }
}

add_action('save_post_post', function ($post_id) {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    delete_transient('my_theme_placeholder_blog_post_ids_v1');
    if (function_exists('my_theme_bump_blog_cache_version')) {
        my_theme_bump_blog_cache_version();
    }
});

add_action('save_post_page', function ($post_id) {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    if (function_exists('my_theme_bump_blog_cache_version')) {
        my_theme_bump_blog_cache_version();
    }
});

add_action('before_delete_post', function ($post_id) {
    $post_type = get_post_type($post_id);
    if ($post_type === 'post') {
        delete_transient('my_theme_placeholder_blog_post_ids_v1');
    }
    if (in_array($post_type, ['post', 'page'], true) && function_exists('my_theme_bump_blog_cache_version')) {
        my_theme_bump_blog_cache_version();
    }
});

add_action('pre_get_posts', function ($query) {
    if (!($query instanceof WP_Query) || is_admin() || !$query->is_main_query()) {
        return;
    }

    if (!($query->is_archive() || $query->is_search())) {
        return;
    }

    $hidden_ids = function_exists('my_theme_get_placeholder_blog_post_ids')
        ? my_theme_get_placeholder_blog_post_ids()
        : [];
    if (empty($hidden_ids)) {
        return;
    }

    $existing = $query->get('post__not_in');
    if (!is_array($existing)) {
        $existing = empty($existing) ? [] : [(int) $existing];
    }

    $query->set('post__not_in', array_values(array_unique(array_merge($existing, $hidden_ids))));
});

add_action('template_redirect', function () {
    if (is_admin() || !is_singular('post')) {
        return;
    }

    $post = get_queried_object();
    if (!($post instanceof WP_Post) || !function_exists('my_theme_is_placeholder_blog_post') || !my_theme_is_placeholder_blog_post($post)) {
        return;
    }

    wp_safe_redirect(trailingslashit(home_url('/blog')), 302);
    exit;
});

if (!function_exists('my_theme_text_looks_unaccented_vi')) {
    function my_theme_text_looks_unaccented_vi($text = '')
    {
        $text = trim((string) $text);
        if ($text === '') {
            return false;
        }
        $normalized = function_exists('my_theme_normalize_search_text')
            ? (string) my_theme_normalize_search_text($text)
            : strtolower($text);
        if ($normalized === '') {
            return false;
        }

        $has_vi_accent = (bool) preg_match('/[ăâđêôơưáàảãạắằẳẵặấầẩẫậéèẻẽẹếềểễệíìỉĩịóòỏõọốồổỗộớờởỡợúùủũụứừửữựýỳỷỹỵ]/u', $text);
        if ($has_vi_accent) {
            return false;
        }

        $keyword_pattern = '/\b(son|ngoai|noi|chong|lot|bot|tret|phu|gia|cong|nghiep|tham|keo|dan|gach|tram|khe|vua|rot|sua|chua|bam|dinh|be|tong|bao|ve|moi|truong|khac|nghiet|sieu|trang|khang|kiem|de|chui|ben|mau)\b/';
        $keyword_hits = preg_match_all($keyword_pattern, $normalized, $matches);
        $word_count = count(array_filter(explode(' ', $normalized), static function ($token) {
            return trim((string) $token) !== '';
        }));

        $looks_unaccented_vi = $keyword_hits >= 2;
        $looks_ascii_vi_sentence = $word_count >= 5
            && (bool) preg_match('/\b(cho|cua|trong|ngoai|va|co|khong|truoc|sau)\b/', $normalized);

        return ($looks_unaccented_vi || $looks_ascii_vi_sentence);
    }
}

if (!function_exists('my_theme_is_generic_line_label')) {
    function my_theme_is_generic_line_label($line_label = '', $line_slug = '', $cat_label = '')
    {
        $line_label = trim((string) $line_label);
        $line_slug = sanitize_title((string) $line_slug);
        $cat_label = trim((string) $cat_label);
        if ($line_label === '') {
            return true;
        }

        if ($line_slug !== '' && strpos($line_slug, 'line-') === 0) {
            return true;
        }

        $normalize = function ($value) {
            if (function_exists('my_theme_normalize_search_text')) {
                return (string) my_theme_normalize_search_text($value);
            }
            return strtolower((string) $value);
        };

        $line_norm = $normalize($line_label);
        $cat_norm = $normalize($cat_label);
        if ($line_norm !== '' && $cat_norm !== '' && $line_norm === $cat_norm) {
            return true;
        }

        $generic_labels = [
            'Sơn lót',
            'Chống thấm',
            'Bột trét',
            'Sơn nội thất',
            'Sơn ngoại thất',
            'Sơn kim loại',
            'Sơn epoxy',
            'Sơn công nghiệp',
            'Keo và phụ gia',
            'Sơn dầu',
        ];
        $generic_norm = array_map($normalize, $generic_labels);
        return in_array($line_norm, $generic_norm, true);
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

if (!function_exists('my_theme_strip_localized_product_prefix')) {
    function my_theme_strip_localized_product_prefix($title)
    {
        $original_title = my_theme_clean_product_display_title($title);
        $title = $original_title;
        if ($title === '') {
            return '';
        }

        // Drop generic Vietnamese type prefixes to keep product names natural.
        $title = preg_replace(
            '/^(sơn(?:\s+nước)?|bột\s+trét|chống\s+thấm|keo(?:\s+dán\s+gạch|\s+trám\s+khe|\s+chà\s+ron)?|v(?:ữa|ừa|ua)(?:\s+s(?:ửa|ua)\s+ch(?:ữa|ua)|\s+r(?:ót|ot))?|phụ\s+gia)\s+/iu',
            '',
            $title
        );
        $title = trim((string) $title);

        $brand_anchor = '/\b(Dulux[a-z0-9-]*|Maxilite[a-z0-9-]*|Weber[a-z0-9-]*|Jotun[a-z0-9-]*|Nippon[a-z0-9-]*|Kova[a-z0-9-]*|TOA[a-z0-9-]*|Sika[a-z0-9-]*|Apollo[a-z0-9-]*)\b/iu';
        if (preg_match($brand_anchor, $title, $m, PREG_OFFSET_CAPTURE)) {
            $offset = isset($m[0][1]) ? (int) $m[0][1] : 0;
            $brand_token = isset($m[0][0]) ? (string) $m[0][0] : '';
            $trailing_text = $brand_token !== '' ? trim((string) substr($title, $offset + strlen($brand_token))) : '';
            if ($offset > 0 && $trailing_text !== '') {
                $title = trim((string) substr($title, $offset));
            } elseif ($offset > 0) {
                return $original_title;
            }
        }

        $title = preg_replace('/^[\-\:\.,\s]+/u', '', (string) $title);
        return my_theme_clean_product_display_title($title);
    }
}

if (!function_exists('my_theme_resolve_product')) {
    function my_theme_resolve_product($prod = null)
    {
        if ($prod instanceof WC_Product) {
            return $prod;
        }

        $product_id = 0;
        if ($prod instanceof WP_Post) {
            $product_id = (int) $prod->ID;
        } elseif (is_numeric($prod)) {
            $product_id = (int) $prod;
        }

        if ($product_id > 0 && function_exists('wc_get_product')) {
            $resolved = wc_get_product($product_id);
            if ($resolved instanceof WC_Product) {
                return $resolved;
            }
        }

        global $product;
        if ($product instanceof WC_Product) {
            return $product;
        }

        $current_id = get_the_ID();
        if ($current_id && function_exists('wc_get_product')) {
            $resolved = wc_get_product((int) $current_id);
            if ($resolved instanceof WC_Product) {
                return $resolved;
            }
        }

        return null;
    }
}

if (!function_exists('my_theme_get_product_display_name')) {
    function my_theme_get_product_display_name($prod = null)
    {
        $product = function_exists('my_theme_resolve_product')
            ? my_theme_resolve_product($prod)
            : (($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID()));
        if (!$product instanceof WC_Product) {
            return '';
        }

        static $cache = [];
        $product_id = (int) $product->get_id();
        if ($product_id > 0 && array_key_exists($product_id, $cache)) {
            return (string) $cache[$product_id];
        }

        $raw_name = (string) $product->get_name();
        $display_name = my_theme_strip_localized_product_prefix($raw_name);
        if ($display_name !== '') {
            if ($product_id > 0) {
                $cache[$product_id] = $display_name;
            }
            return $display_name;
        }

        $display_name = my_theme_clean_product_display_title($raw_name);
        if ($product_id > 0) {
            $cache[$product_id] = $display_name;
        }

        return $display_name;
    }
}

if (!function_exists('my_theme_get_accessible_product_name')) {
    function my_theme_get_accessible_product_name($prod = null)
    {
        $product = function_exists('my_theme_resolve_product')
            ? my_theme_resolve_product($prod)
            : (($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID()));
        if (!$product instanceof WC_Product) {
            return '';
        }

        $name = function_exists('my_theme_get_product_display_name')
            ? (string) my_theme_get_product_display_name($product)
            : (string) $product->get_name();
        $name = trim((string) wp_strip_all_tags($name));
        if ($name !== '') {
            return $name;
        }

        return trim((string) wp_strip_all_tags((string) $product->get_name()));
    }
}

add_filter('woocommerce_cart_item_name', function ($product_name, $cart_item, $cart_item_key) {
    $product = isset($cart_item['data']) && $cart_item['data'] instanceof WC_Product ? $cart_item['data'] : null;
    if (!$product instanceof WC_Product) {
        return $product_name;
    }

    $clean_name = function_exists('my_theme_get_accessible_product_name')
        ? my_theme_get_accessible_product_name($product)
        : '';
    if ($clean_name === '') {
        return $product_name;
    }

    return $clean_name;
}, 20, 3);

add_filter('woocommerce_order_item_name', function ($item_name, $item) {
    if (!($item instanceof WC_Order_Item_Product)) {
        return $item_name;
    }
    $product = $item->get_product();
    if (!$product instanceof WC_Product) {
        return $item_name;
    }

    $clean_name = function_exists('my_theme_get_accessible_product_name')
        ? my_theme_get_accessible_product_name($product)
        : '';
    if ($clean_name === '') {
        return $item_name;
    }

    return esc_html($clean_name);
}, 20, 2);

add_filter('woocommerce_quantity_input_args', function ($args, $product) {
    if (!$product instanceof WC_Product || !is_array($args)) {
        return $args;
    }

    $clean_name = function_exists('my_theme_get_accessible_product_name')
        ? my_theme_get_accessible_product_name($product)
        : '';
    if ($clean_name === '') {
        return $args;
    }

    $args['product_name'] = $clean_name;
    return $args;
}, 20, 2);

add_filter('woocommerce_product_get_image', function ($image, $product, $size, $attr, $placeholder) {
    if (!is_string($image) || trim($image) === '' || !$product instanceof WC_Product) {
        return $image;
    }

    $clean_name = function_exists('my_theme_get_accessible_product_name')
        ? my_theme_get_accessible_product_name($product)
        : '';
    if ($clean_name === '') {
        return $image;
    }

    $escaped_name = esc_attr($clean_name);
    if (strpos($image, ' alt=') !== false) {
        $image = preg_replace('/\salt="[^"]*"/i', ' alt="' . $escaped_name . '"', $image, 1);
    } else {
        $image = preg_replace('/<img\b/i', '<img alt="' . $escaped_name . '"', $image, 1);
    }

    if (strpos($image, ' title=') !== false) {
        $image = preg_replace('/\stitle="[^"]*"/i', ' title="' . $escaped_name . '"', $image, 1);
    }

    return $image;
}, 20, 5);

add_filter('woocommerce_gallery_image_html_attachment_image_params', function ($attributes, $attachment_id, $main_image) {
    if (!is_array($attributes)) {
        return $attributes;
    }
    if (!function_exists('is_product') || !is_product()) {
        return $attributes;
    }

    $product = wc_get_product(get_the_ID());
    if (!$product instanceof WC_Product) {
        return $attributes;
    }
    $clean_name = function_exists('my_theme_get_accessible_product_name')
        ? my_theme_get_accessible_product_name($product)
        : '';
    if ($clean_name === '') {
        return $attributes;
    }

    $attributes['alt'] = $clean_name;
    $attributes['title'] = $clean_name;
    return $attributes;
}, 20, 3);

add_filter('woocommerce_single_product_image_thumbnail_html', function ($html, $post_thumbnail_id) {
    if (!is_string($html) || trim($html) === '') {
        return $html;
    }
    if (!function_exists('is_product') || !is_product()) {
        return $html;
    }

    $product = wc_get_product(get_the_ID());
    if (!$product instanceof WC_Product) {
        return $html;
    }

    $clean_name = function_exists('my_theme_get_accessible_product_name')
        ? my_theme_get_accessible_product_name($product)
        : '';
    if ($clean_name === '') {
        return $html;
    }

    $escaped_name = esc_attr($clean_name);
    if (strpos($html, 'data-thumb-alt=') !== false) {
        $html = preg_replace('/\sdata-thumb-alt="[^"]*"/i', ' data-thumb-alt="' . $escaped_name . '"', $html, 1);
    }

    return $html;
}, 20, 2);

if (!function_exists('my_theme_source_url_looks_non_product_image')) {
    function my_theme_source_url_looks_non_product_image($source_url)
    {
        $source_url = html_entity_decode(trim((string) $source_url), ENT_QUOTES, 'UTF-8');
        if ($source_url === '') {
            return false;
        }

        $normalized = strtolower($source_url);
        $path = (string) parse_url($normalized, PHP_URL_PATH);
        $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'pdf' || $ext === 'svg' || strpos($normalized, '.pdf') !== false) {
            return true;
        }

        $markers = [
            'product-logos',
            '/logo/',
            'logo-',
            '_logo',
            'wordmark',
            'datasheet',
            'data-sheet',
            'technical-data-sheet',
            'safety-data-sheet',
            'spec-sheet',
            'ban-chi-tiet',
            'chi-tiet-san-pham',
            'msds',
            'tds',
        ];
        foreach ($markers as $marker) {
            if (strpos($normalized, $marker) !== false) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('my_theme_attachment_has_non_product_traits')) {
    function my_theme_attachment_has_non_product_traits($attachment_id, $source_url = '')
    {
        $attachment_id = (int) $attachment_id;
        if ($attachment_id <= 0) {
            return false;
        }

        $source_url = trim((string) $source_url);
        $cache_key = $attachment_id . '|' . md5($source_url);
        static $cache = [];
        if (array_key_exists($cache_key, $cache)) {
            return (bool) $cache[$cache_key];
        }

        $is_non_product = false;
        if ($source_url !== '' && function_exists('my_theme_source_url_looks_non_product_image')) {
            $is_non_product = my_theme_source_url_looks_non_product_image($source_url);
        }

        if (!$is_non_product) {
            $mime_type = strtolower((string) get_post_mime_type($attachment_id));
            if (strpos($mime_type, 'svg') !== false) {
                $is_non_product = true;
            }
        }

        $file_markers = [
            'product-logo',
            'product-logos',
            'wordmark',
            'datasheet',
            'data-sheet',
            'technical-data-sheet',
            'safety-data-sheet',
            'spec-sheet',
            'ban-chi-tiet',
            'chi-tiet-san-pham',
            'msds',
            'tds',
        ];
        if (!$is_non_product) {
            $attached_file = strtolower((string) get_post_meta($attachment_id, '_wp_attached_file', true));
            $filename = strtolower((string) wp_basename($attached_file));
            if ($filename !== '') {
                if (preg_match('/\.(pdf|svg)$/', $filename)) {
                    $is_non_product = true;
                } else {
                    foreach ($file_markers as $marker) {
                        if (strpos($filename, $marker) !== false) {
                            $is_non_product = true;
                            break;
                        }
                    }
                }
            }
        }

        if (!$is_non_product) {
            $title = strtolower(trim((string) get_the_title($attachment_id)));
            $alt = strtolower(trim((string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true)));
            $probe = trim($title . ' ' . $alt);
            if ($probe !== '' && preg_match('/\b(logo|wordmark|datasheet|spec-sheet|msds|tds|tai-lieu)\b/', $probe)) {
                $is_non_product = true;
            }
        }

        if (!$is_non_product) {
            $meta = function_exists('my_theme_get_attachment_media_state')
                ? my_theme_get_attachment_media_state($attachment_id)
                : [];
            $width = isset($meta['width']) ? (int) $meta['width'] : 0;
            $height = isset($meta['height']) ? (int) $meta['height'] : 0;
            if ($width > 0 && $height > 0) {
                $ratio = (float) $width / (float) $height;
                if ($ratio > 2.8 || $ratio < 0.36) {
                    $is_non_product = true;
                }

                if (
                    !$is_non_product &&
                    $ratio > 0.62 &&
                    $ratio < 0.78 &&
                    $width >= 860 &&
                    $height >= 1180 &&
                    $source_url !== '' &&
                    stripos($source_url, '/content/dam/') !== false
                ) {
                    $is_non_product = true;
                }
            }
        }

        $cache[$cache_key] = $is_non_product;
        return $is_non_product;
    }
}

if (!function_exists('my_theme_get_preferred_product_image_id')) {
    function my_theme_get_preferred_product_image_id($prod = null)
    {
        $product = function_exists('my_theme_resolve_product')
            ? my_theme_resolve_product($prod)
            : (($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID()));
        if (!$product instanceof WC_Product) {
            return 0;
        }

        $product_id = (int) $product->get_id();
        if ($product_id <= 0) {
            return 0;
        }

        static $cache = [];
        if (array_key_exists($product_id, $cache)) {
            return (int) $cache[$product_id];
        }

        $source_image = trim((string) get_post_meta($product_id, '_official_source_image', true));
        $primary_thumb_id = (int) get_post_thumbnail_id($product_id);
        $preferred_id = 0;

        if (
            $primary_thumb_id > 0 &&
            (
                !function_exists('my_theme_attachment_has_non_product_traits') ||
                !my_theme_attachment_has_non_product_traits($primary_thumb_id, $source_image)
            )
        ) {
            $preferred_id = $primary_thumb_id;
        }

        if ($preferred_id <= 0) {
            $gallery_ids = array_values(array_unique(array_map('intval', (array) $product->get_gallery_image_ids())));
            foreach ($gallery_ids as $gallery_id) {
                if ($gallery_id <= 0 || $gallery_id === $primary_thumb_id) {
                    continue;
                }
                if (
                    !function_exists('my_theme_attachment_has_non_product_traits') ||
                    !my_theme_attachment_has_non_product_traits($gallery_id, '')
                ) {
                    $preferred_id = (int) $gallery_id;
                    break;
                }
            }
        }

        // Last-resort fallback: only use featured image when it is not document-like.
        if ($preferred_id <= 0 && $primary_thumb_id > 0) {
            if (
                !function_exists('my_theme_attachment_has_non_product_traits') ||
                !my_theme_attachment_has_non_product_traits($primary_thumb_id, $source_image)
            ) {
                $preferred_id = $primary_thumb_id;
            }
        }

        $cache[$product_id] = $preferred_id;
        return $preferred_id;
    }
}

if (!function_exists('my_theme_get_attachment_media_state')) {
    function my_theme_get_attachment_media_state($attachment_id = 0)
    {
        $attachment_id = (int) $attachment_id;
        if ($attachment_id <= 0) {
            return [
                'width' => 0,
                'height' => 0,
                'ratio' => 0.0,
                'is_small' => false,
                'has_extreme_ratio' => false,
            ];
        }

        static $cache = [];
        if (isset($cache[$attachment_id])) {
            return $cache[$attachment_id];
        }

        $meta = wp_get_attachment_metadata($attachment_id);
        $width = is_array($meta) && isset($meta['width']) ? (int) $meta['width'] : 0;
        $height = is_array($meta) && isset($meta['height']) ? (int) $meta['height'] : 0;
        $ratio = ($width > 0 && $height > 0) ? ((float) $width / (float) $height) : 0.0;

        $cache[$attachment_id] = [
            'width' => $width,
            'height' => $height,
            'ratio' => $ratio,
            'is_small' => ($width > 0 && $height > 0 && ($width < 320 || $height < 320)),
            'has_extreme_ratio' => ($ratio > 1.8 || $ratio < 0.55),
        ];

        return $cache[$attachment_id];
    }
}

if (!function_exists('my_theme_get_product_card_media_state')) {
    function my_theme_get_product_card_media_state($prod = null)
    {
        $product = function_exists('my_theme_resolve_product')
            ? my_theme_resolve_product($prod)
            : (($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID()));
        if (!$product instanceof WC_Product) {
            return [
                'thumb_id' => 0,
                'thumb_class' => 'product-card__thumb product-card__thumb--fallback',
                'has_placeholder' => true,
            ];
        }

        static $cache = [];
        $product_id = (int) $product->get_id();
        if ($product_id > 0 && isset($cache[$product_id])) {
            return $cache[$product_id];
        }

        $thumb_id = function_exists('my_theme_get_preferred_product_image_id')
            ? (int) my_theme_get_preferred_product_image_id($product)
            : (int) $product->get_image_id();
        $thumb_class = 'product-card__thumb';
        $has_placeholder = $thumb_id <= 0;

        if ($thumb_id > 0) {
            $thumb_state = function_exists('my_theme_get_attachment_media_state')
                ? my_theme_get_attachment_media_state($thumb_id)
                : [];
            if (!empty($thumb_state)) {
                if (!empty($thumb_state['is_small'])) {
                    $thumb_class .= ' product-card__thumb--small-source';
                }
                if (!empty($thumb_state['has_extreme_ratio'])) {
                    $thumb_class .= ' product-card__thumb--extreme-ratio';
                }
            }
        }

        if ($has_placeholder) {
            $thumb_class .= ' product-card__thumb--fallback';
        }

        $media_state = [
            'thumb_id' => $thumb_id,
            'thumb_class' => $thumb_class,
            'has_placeholder' => $has_placeholder,
        ];

        if ($product_id > 0) {
            $cache[$product_id] = $media_state;
        }

        return $media_state;
    }
}

if (!function_exists('my_theme_get_product_thumbnail_markup')) {
    function my_theme_get_product_thumbnail_markup($prod = null, $size = 'medium_large', array $attrs = [], $show_note = false)
    {
        static $cache = [];

        $product = function_exists('my_theme_resolve_product')
            ? my_theme_resolve_product($prod)
            : (($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID()));
        if (!$product instanceof WC_Product) {
            $markup = wc_placeholder_img($size);
            if ($show_note) {
                $markup .= '<span class="product-card__thumb-note">Ảnh sản phẩm đang cập nhật</span>';
            }
            return $markup;
        }

        $media_state = function_exists('my_theme_get_product_card_media_state')
            ? my_theme_get_product_card_media_state($product)
            : [];
        $thumb_id = isset($media_state['thumb_id']) ? (int) $media_state['thumb_id'] : 0;
        $has_placeholder = !empty($media_state['has_placeholder']) || $thumb_id <= 0;

        $display_name = function_exists('my_theme_get_product_display_name')
            ? trim((string) my_theme_get_product_display_name($product))
            : trim((string) $product->get_name());
        if ($display_name !== '') {
            if (!isset($attrs['alt']) || trim((string) $attrs['alt']) === '') {
                $attrs['alt'] = $display_name;
            }
            if (!isset($attrs['title']) || trim((string) $attrs['title']) === '') {
                $attrs['title'] = $display_name;
            }
        }

        $product_id = (int) $product->get_id();
        $cache_key = $product_id . '|' . (string) $size . '|' . md5(wp_json_encode($attrs) . '|' . (int) $show_note);
        if ($product_id > 0 && array_key_exists($cache_key, $cache)) {
            return (string) $cache[$cache_key];
        }

        if (!$has_placeholder && $thumb_id > 0) {
            $markup = (string) wp_get_attachment_image($thumb_id, $size, false, $attrs);
            if ($product_id > 0) {
                $cache[$cache_key] = $markup;
            }
            return $markup;
        }

        $markup = wc_placeholder_img($size);
        if ($show_note) {
            $markup .= '<span class="product-card__thumb-note">Ảnh sản phẩm đang cập nhật</span>';
        }

        if ($product_id > 0) {
            $cache[$cache_key] = $markup;
        }

        return $markup;
    }
}

if (!function_exists('my_theme_product_has_document_like_image')) {
    function my_theme_product_has_document_like_image($prod = null)
    {
        $product = function_exists('my_theme_resolve_product')
            ? my_theme_resolve_product($prod)
            : (($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID()));
        if (!$product instanceof WC_Product) {
            return false;
        }

        static $cache = [];
        $product_id = (int) $product->get_id();
        if ($product_id > 0 && array_key_exists($product_id, $cache)) {
            return (bool) $cache[$product_id];
        }

        $source_image = trim((string) get_post_meta($product_id, '_official_source_image', true));
        $thumb_id = (int) get_post_thumbnail_id($product_id);
        $is_doc_like = false;
        if ($source_image !== '' && function_exists('my_theme_source_url_looks_non_product_image')) {
            $is_doc_like = my_theme_source_url_looks_non_product_image($source_image);
        }
        if (!$is_doc_like && $thumb_id > 0 && function_exists('my_theme_attachment_has_non_product_traits')) {
            $is_doc_like = my_theme_attachment_has_non_product_traits($thumb_id, $source_image);
        }

        if ($product_id > 0) {
            $cache[$product_id] = $is_doc_like;
        }
        return $is_doc_like;
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
    $clean_title = my_theme_strip_localized_product_prefix($title);
    if ($clean_title !== '') {
        return $clean_title;
    }
    return my_theme_clean_product_display_title($title);
}, 12, 2);

add_filter('document_title_parts', function ($parts) {
    if (!is_array($parts)) {
        return $parts;
    }
    $business_name = 'Đại lý Sơn Phát Tấn';
    if (function_exists('my_theme_get_business_profile')) {
        $business = my_theme_get_business_profile();
        if (is_array($business) && !empty($business['name'])) {
            $business_name = trim((string) $business['name']);
        }
    }
    $parts['site'] = $business_name;
    if (is_front_page()) {
        $parts['title'] = $business_name;
        unset($parts['site']);
        return $parts;
    }
    if (function_exists('my_theme_is_virtual_blog_request') && my_theme_is_virtual_blog_request()) {
        $parts['title'] = 'Góc tư vấn thi công';
        return $parts;
    }
    if (is_page()) {
        $page_titles = [
            'faq' => 'Câu hỏi thường gặp',
            'cau-hoi-thuong-gap' => 'Câu hỏi thường gặp',
            'lien-he' => 'Liên hệ',
            'huong-dan-mua-hang' => 'Hướng dẫn mua hàng',
            'giai-phap' => 'Giải pháp tổng hợp',
            'giai-phap-son-noi-that' => 'Giải pháp sơn nội thất',
            'giai-phap-son-ngoai-that' => 'Giải pháp sơn ngoại thất',
            'giai-phap-chong-tham' => 'Giải pháp chống thấm',
            'giai-phap-son-epoxy' => 'Giải pháp sơn epoxy',
            'giai-phap-son-kim-loai' => 'Giải pháp sơn kim loại',
            'giai-phap-keo-va-ron' => 'Giải pháp keo và ron gạch',
            'gioi-thieu' => 'Giới thiệu',
            'gia-tho' => 'Bảng giá thợ',
            'van-chuyen-giao-hang' => 'Vận chuyển & giao hàng',
            'chinh-sach-doi-tra' => 'Chính sách đổi trả',
        ];
        foreach ($page_titles as $page_slug => $page_title) {
            if (is_page($page_slug)) {
                $parts['title'] = $page_title;
                return $parts;
            }
        }
    }
    if (function_exists('is_shop') && is_shop()) {
        $parts['title'] = 'Sản phẩm';
        return $parts;
    }
    if (function_exists('is_cart') && is_cart()) {
        $parts['title'] = 'Giỏ hàng';
        return $parts;
    }
    if (function_exists('is_order_received_page') && is_order_received_page()) {
        $parts['title'] = 'Đơn hàng đã nhận';
        return $parts;
    }
    if (function_exists('is_checkout') && is_checkout()) {
        $parts['title'] = 'Thanh toán';
        return $parts;
    }
    if (function_exists('is_account_page') && is_account_page()) {
        $title = 'Tài khoản';
        if (function_exists('is_wc_endpoint_url')) {
            $account_titles = [
                'orders' => 'Đơn hàng',
                'view-order' => 'Đơn hàng',
                'downloads' => 'Tệp tải xuống',
                'edit-address' => 'Địa chỉ',
                'edit-account' => 'Tài khoản',
                'payment-methods' => 'Phương thức thanh toán',
                'add-payment-method' => 'Thêm phương thức thanh toán',
                'lost-password' => 'Quên mật khẩu',
                'customer-logout' => 'Đăng xuất',
            ];
            foreach ($account_titles as $endpoint => $label) {
                if (is_wc_endpoint_url($endpoint)) {
                    $title = $label;
                    break;
                }
            }
        }
        $parts['title'] = $title;
        return $parts;
    }
    if (function_exists('is_product') && is_product() && !empty($parts['title'])) {
        $parts['title'] = my_theme_strip_localized_product_prefix((string) $parts['title']);
    }
    return $parts;
}, 20);

if (!function_exists('my_theme_get_business_profile')) {
    function my_theme_get_business_profile()
    {
        return [
            'name' => 'Đại lý Sơn Phát Tấn',
            'contact_name' => 'Trần Thị Ngọc Thúy',
            'email' => 'lephat1898@gmail.com',
            'email_href' => 'mailto:lephat1898@gmail.com',
            'phone_display' => '0944 857 999',
            'phone_digits' => '0944857999',
            'phone_href' => 'tel:0944857999',
            'phone_raw' => '+84944857999',
            'zalo_url' => 'https://zalo.me/0944857999',
            'address_street' => '392 TL10, Bình Trị Đông',
            'address_locality' => 'Bình Tân',
            'address_region' => 'TP.HCM',
            'address_country' => 'VN',
            'address_full' => '392 TL10, Bình Trị Đông, Bình Tân, TP.HCM',
            'maps_url' => 'https://www.google.com/maps/place/392+TL10,+B%C3%ACnh+Tr%E1%BB%8B+%C4%90%C3%B4ng,+B%C3%ACnh+T%C3%A2n,+Th%C3%A0nh+ph%E1%BB%91+H%E1%BB%93+Ch%C3%AD+Minh,+Vi%E1%BB%87t+Nam/@10.7569515,106.6195492,17z/data=!3m1!4b1!4m6!3m5!1s0x31752c2ec14b688b:0xe43d34f4d14c3f98!8m2!3d10.7569515!4d106.6221241!16s%2Fg%2F11rp3djv_1?entry=ttu',
            'hours_display' => 'Thứ 2 - Thứ 7: 7:30 - 18:00',
            'hours_note' => 'Ngoài giờ vẫn nhận yêu cầu qua Zalo và phản hồi sớm trong khung tiếp theo.',
            'hours_schema_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            'hours_open' => '07:30',
            'hours_close' => '18:00',
            'service_areas' => ['TP.HCM', 'Bình Dương', 'Đồng Nai'],
            'service_areas_display' => 'TP.HCM, Bình Dương, Đồng Nai',
            'logo_url' => get_theme_file_uri('assets/logo-phat-tan.svg'),
        ];
    }
}

if (!function_exists('my_theme_get_store_snapshot')) {
    function my_theme_get_store_snapshot()
    {
        static $snapshot = null;

        if (is_array($snapshot)) {
            return $snapshot;
        }

        $business = function_exists('my_theme_get_business_profile')
            ? my_theme_get_business_profile()
            : [];
        $cache_version = (string) get_option('my_theme_filter_cache_version', '1');
        $business_hash = md5(wp_json_encode($business));
        $cache_key = 'my_theme_store_snapshot_v2_' . $cache_version . '_' . $business_hash;
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            $snapshot = $cached;
            return $snapshot;
        }

        $visible_ids = function_exists('my_theme_get_catalog_visible_product_ids')
            ? my_theme_get_catalog_visible_product_ids(false)
            : [];
        $visible_ids = array_values(array_filter(array_map('intval', (array) $visible_ids), function ($id) {
            return $id > 0;
        }));

        $brand_options = function_exists('my_theme_get_brand_filter_options')
            ? my_theme_get_brand_filter_options($visible_ids)
            : [];
        $brand_options = is_array($brand_options) ? $brand_options : [];

        $snapshot = [
            'name' => isset($business['name']) ? (string) $business['name'] : get_bloginfo('name'),
            'contact_name' => isset($business['contact_name']) ? (string) $business['contact_name'] : '',
            'phone_display' => isset($business['phone_display']) ? (string) $business['phone_display'] : '0944 857 999',
            'phone_digits' => isset($business['phone_digits']) ? (string) $business['phone_digits'] : '0944857999',
            'phone_href' => isset($business['phone_href']) ? (string) $business['phone_href'] : 'tel:0944857999',
            'phone_raw' => isset($business['phone_raw']) ? (string) $business['phone_raw'] : '+84944857999',
            'email' => isset($business['email']) ? (string) $business['email'] : 'lephat1898@gmail.com',
            'email_href' => isset($business['email_href']) ? (string) $business['email_href'] : 'mailto:lephat1898@gmail.com',
            'zalo_url' => isset($business['zalo_url']) ? (string) $business['zalo_url'] : 'https://zalo.me/0944857999',
            'hours_display' => isset($business['hours_display']) ? (string) $business['hours_display'] : 'Thứ 2 - Thứ 7: 7:30 - 18:00',
            'hours_note' => isset($business['hours_note']) ? (string) $business['hours_note'] : 'Ngoài giờ vẫn nhận yêu cầu qua Zalo và phản hồi sớm trong khung tiếp theo.',
            'service_areas_display' => isset($business['service_areas_display']) ? (string) $business['service_areas_display'] : 'TP.HCM, Bình Dương, Đồng Nai',
            'address_full' => isset($business['address_full']) ? (string) $business['address_full'] : '392 TL10, Bình Trị Đông, Bình Tân, TP.HCM',
            'maps_url' => isset($business['maps_url']) ? (string) $business['maps_url'] : '',
            'logo_url' => isset($business['logo_url']) ? (string) $business['logo_url'] : '',
            'catalog_count' => count($visible_ids),
            'category_count' => function_exists('my_theme_count_visible_product_categories')
                ? (int) my_theme_count_visible_product_categories($visible_ids)
                : 0,
            'brand_count' => count($brand_options),
            'brand_preview' => array_slice($brand_options, 0, 6, true),
        ];

        set_transient($cache_key, $snapshot, 30 * MINUTE_IN_SECONDS);
        return $snapshot;
    }
}

if (!function_exists('my_theme_get_page_context_links')) {
    function my_theme_get_page_context_links($page_slug = '')
    {
        $page_slug = sanitize_title((string) $page_slug);
        $shop_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
        $calculator_url = function_exists('my_theme_get_paint_calculator_url') ? my_theme_get_paint_calculator_url() : home_url('/tinh-son');
        $contact_url = home_url('/lien-he');
        $guide_url = home_url('/huong-dan-mua-hang');
        $solutions_url = home_url('/giai-phap');
        $faq_url = home_url('/faq');
        $cart_url = function_exists('my_theme_get_cart_url_safe') ? my_theme_get_cart_url_safe() : home_url('/gio-hang');

        $defaults = [
            ['label' => 'Mở kho sản phẩm', 'url' => $shop_url],
            ['label' => 'Đi theo giải pháp', 'url' => $solutions_url],
            ['label' => 'Tính sơn nhanh', 'url' => $calculator_url],
            ['label' => 'Liên hệ kỹ thuật', 'url' => $contact_url],
        ];

        $map = [
            'faq' => [
                ['label' => 'Mở kho sản phẩm', 'url' => $shop_url],
                ['label' => 'Xem hướng dẫn mua hàng', 'url' => $guide_url],
                ['label' => 'Tính sơn nhanh', 'url' => $calculator_url],
                ['label' => 'Liên hệ kỹ thuật', 'url' => $contact_url],
            ],
            'huong-dan-mua-hang' => [
                ['label' => 'Mở kho sản phẩm', 'url' => $shop_url],
                ['label' => 'Xem giỏ hàng', 'url' => $cart_url],
                ['label' => 'Xem FAQ', 'url' => $faq_url],
                ['label' => 'Liên hệ kỹ thuật', 'url' => $contact_url],
            ],
            'gioi-thieu' => [
                ['label' => 'Mở kho sản phẩm', 'url' => $shop_url],
                ['label' => 'Xem giải pháp', 'url' => $solutions_url],
                ['label' => 'Xem FAQ', 'url' => $faq_url],
                ['label' => 'Liên hệ cửa hàng', 'url' => $contact_url],
            ],
            'van-chuyen-giao-hang' => [
                ['label' => 'Mở kho sản phẩm', 'url' => $shop_url],
                ['label' => 'Xem hướng dẫn mua hàng', 'url' => $guide_url],
                ['label' => 'Xem giải pháp', 'url' => $solutions_url],
                ['label' => 'Liên hệ kỹ thuật', 'url' => $contact_url],
            ],
            'chinh-sach-doi-tra' => [
                ['label' => 'Mở kho sản phẩm', 'url' => $shop_url],
                ['label' => 'Xem hướng dẫn mua hàng', 'url' => $guide_url],
                ['label' => 'Xem FAQ', 'url' => $faq_url],
                ['label' => 'Liên hệ cửa hàng', 'url' => $contact_url],
            ],
            'gia-tho' => [
                ['label' => 'Mở kho sản phẩm', 'url' => $shop_url],
                ['label' => 'Xem giải pháp', 'url' => $solutions_url],
                ['label' => 'Liên hệ báo giá', 'url' => $contact_url],
                ['label' => 'Xem FAQ', 'url' => $faq_url],
            ],
            'lien-he' => [
                ['label' => 'Mở kho sản phẩm', 'url' => $shop_url],
                ['label' => 'Xem giải pháp', 'url' => $solutions_url],
                ['label' => 'Tính sơn nhanh', 'url' => $calculator_url],
                ['label' => 'Xem FAQ', 'url' => $faq_url],
            ],
        ];

        return isset($map[$page_slug]) ? $map[$page_slug] : $defaults;
    }
}

if (!function_exists('my_theme_get_home_brand_priority_slugs')) {
    function my_theme_get_home_brand_priority_slugs()
    {
        return ['dulux', 'maxilite', 'weber', 'jotun', 'nippon', 'kova', 'toa', 'sika', 'apollo'];
    }
}

if (!function_exists('my_theme_get_home_category_priority_slugs')) {
    function my_theme_get_home_category_priority_slugs()
    {
        return [
            'son-noi-that',
            'son-ngoai-that',
            'chong-tham',
            'son-epoxy',
            'son-kim-loai',
            'keo-va-phu-gia',
            'bot-tret',
            'son-lot',
        ];
    }
}

if (!function_exists('my_theme_get_products_by_slugs')) {
    function my_theme_get_products_by_slugs(array $slugs)
    {
        $product_ids = [];

        foreach ($slugs as $slug) {
            $slug = sanitize_title((string) $slug);
            if ($slug === '') {
                continue;
            }

            $post = get_page_by_path($slug, OBJECT, 'product');
            if (!($post instanceof WP_Post)) {
                continue;
            }

            if ((int) $post->ID > 0) {
                $product_ids[] = (int) $post->ID;
            }
        }

        $product_ids = function_exists('my_theme_preserve_product_id_order')
            ? my_theme_preserve_product_id_order($product_ids)
            : my_theme_normalize_product_id_list($product_ids);
        if (empty($product_ids)) {
            return [];
        }

        $product_map = function_exists('my_theme_get_product_object_map')
            ? my_theme_get_product_object_map($product_ids)
            : [];
        $products = [];
        foreach ($product_ids as $product_id) {
            if (isset($product_map[$product_id]) && $product_map[$product_id] instanceof WC_Product) {
                $products[] = $product_map[$product_id];
            }
        }

        return $products;
    }
}

if (!function_exists('my_theme_capture_markup')) {
    function my_theme_capture_markup(callable $callback)
    {
        ob_start();
        $callback();
        return trim((string) ob_get_clean());
    }
}

if (!function_exists('my_theme_render_landing_product_cards')) {
    function my_theme_render_landing_product_cards(array $products, array $args = [])
    {
        $fallback_eyebrow = isset($args['fallback_eyebrow']) ? trim((string) $args['fallback_eyebrow']) : 'Sản phẩm gợi ý';
        $show_category = array_key_exists('show_category', $args) ? (bool) $args['show_category'] : true;
        $show_line = array_key_exists('show_line', $args) ? (bool) $args['show_line'] : true;
        $show_excerpt = array_key_exists('show_excerpt', $args) ? (bool) $args['show_excerpt'] : true;
        $show_pack_summary = array_key_exists('show_pack_summary', $args) ? (bool) $args['show_pack_summary'] : true;
        $show_pack_prices = !empty($args['show_pack_prices']);
        $excerpt_limit = isset($args['excerpt_limit']) ? max(8, (int) $args['excerpt_limit']) : 18;

        foreach ($products as $product) {
            if (!$product instanceof WC_Product) {
                continue;
            }

            $catalog_profile = function_exists('my_theme_get_product_catalog_profile')
                ? my_theme_get_product_catalog_profile($product)
                : [];
            $name = isset($catalog_profile['display_name']) && (string) $catalog_profile['display_name'] !== ''
                ? (string) $catalog_profile['display_name']
                : (string) $product->get_name();
            $line = isset($catalog_profile['line_label'])
                ? trim((string) $catalog_profile['line_label'])
                : '';
            $category = isset($catalog_profile['category_label'])
                ? trim((string) $catalog_profile['category_label'])
                : '';
            $excerpt = ($show_excerpt && function_exists('my_theme_get_product_card_excerpt'))
                ? (string) my_theme_get_product_card_excerpt($product, $excerpt_limit)
                : '';
            $price_html = function_exists('my_theme_get_loop_price_html')
                ? my_theme_get_loop_price_html($product, 'landing-product-card__price product-card__price')
                : '';
            if ($price_html === '') {
                $price_html = '<div class="landing-product-card__price product-card__price"><span class="product-card__price-contact">Liên hệ báo giá</span></div>';
            }

            $pack_summary = ($show_pack_summary && function_exists('my_theme_render_loop_pack_summary'))
                ? my_theme_capture_markup(static function () use ($product) {
                    my_theme_render_loop_pack_summary($product, true);
                })
                : '';
            $pack_prices = ($show_pack_prices && function_exists('my_theme_render_pack_price_list'))
                ? my_theme_capture_markup(static function () use ($product) {
                    my_theme_render_pack_price_list($product, 'related');
                })
                : '';
            $eyebrow = ($show_category && $category !== '') ? $category : $fallback_eyebrow;
            ?>
            <article class="landing-product-card">
              <a class="landing-product-card__thumb" href="<?php echo esc_url($product->get_permalink()); ?>">
                <?php
                echo function_exists('my_theme_get_product_thumbnail_markup')
                    ? my_theme_get_product_thumbnail_markup($product, 'woocommerce_thumbnail', ['alt' => $name, 'loading' => 'lazy'])
                    : $product->get_image('woocommerce_thumbnail', ['alt' => $name, 'loading' => 'lazy']);
                ?>
              </a>
              <div class="landing-product-card__body">
                <div class="landing-product-card__eyebrow"><?php echo esc_html($eyebrow); ?></div>
                <h3 class="landing-product-card__title">
                  <a href="<?php echo esc_url($product->get_permalink()); ?>"><?php echo esc_html($name); ?></a>
                </h3>
                <?php if ($show_line && $line !== '') : ?>
                  <p class="landing-product-card__line"><?php echo esc_html($line); ?></p>
                <?php endif; ?>
                <?php if ($excerpt !== '') : ?>
                  <p class="landing-product-card__excerpt"><?php echo esc_html($excerpt); ?></p>
                <?php endif; ?>
                <?php if ($pack_summary !== '') : ?>
                  <div class="landing-product-card__packs"><?php echo $pack_summary; ?></div>
                <?php endif; ?>
                <?php if ($pack_prices !== '') : ?>
                  <div class="landing-product-card__pack-prices"><?php echo $pack_prices; ?></div>
                <?php endif; ?>
              </div>
              <div class="landing-product-card__actions">
                <?php echo wp_kses_post($price_html); ?>
                <a class="btn btn-primary w-100" href="<?php echo esc_url($product->get_permalink()); ?>">Xem sản phẩm</a>
              </div>
            </article>
            <?php
        }
    }
}

if (!function_exists('my_theme_score_home_featured_product')) {
    function my_theme_score_home_featured_product($product = null, $brand_slug = '')
    {
        $state = function_exists('my_theme_get_home_featured_product_sort_state')
            ? my_theme_get_home_featured_product_sort_state($product, $brand_slug)
            : [];
        if (isset($state['score'])) {
            return (int) $state['score'];
        }

        $product = ($product instanceof WC_Product) ? $product : (function_exists('wc_get_product') ? wc_get_product($product) : null);
        if (!$product instanceof WC_Product) {
            return -999999;
        }

        $product_id = (int) $product->get_id();
        $sales_total = (int) get_post_meta($product_id, 'total_sales', true);
        $price_value = function_exists('my_theme_get_default_loop_price')
            ? (float) my_theme_get_default_loop_price($product)
            : (float) $product->get_price();
        $is_featured = method_exists($product, 'is_featured') && $product->is_featured();
        $is_in_stock = method_exists($product, 'is_in_stock') && $product->is_in_stock();
        $created_at = method_exists($product, 'get_date_created') ? $product->get_date_created() : null;
        $created_ts = ($created_at instanceof WC_DateTime) ? (int) $created_at->getTimestamp() : 0;
        $days_old = $created_ts > 0 ? max(0, (int) floor((time() - $created_ts) / DAY_IN_SECONDS)) : 9999;

        $score = 0;
        if ($is_featured) {
            $score += 140;
        }
        if ($sales_total > 0) {
            $score += min(90, $sales_total);
            $score += min(24, (int) floor(log($sales_total + 1, 2) * 3));
        }
        if ($is_in_stock) {
            $score += 18;
        }
        if ($price_value > 0) {
            $score += 12;
        }
        if ($days_old <= 45) {
            $score += 14;
        } elseif ($days_old <= 120) {
            $score += 9;
        } elseif ($days_old <= 240) {
            $score += 4;
        }
        $catalog_profile = function_exists('my_theme_get_product_catalog_profile')
            ? my_theme_get_product_catalog_profile($product)
            : [];
        $product_brand_slug = isset($catalog_profile['brand_slug'])
            ? sanitize_title((string) $catalog_profile['brand_slug'])
            : '';
        if ($brand_slug !== '' && $product_brand_slug === sanitize_title((string) $brand_slug)) {
            $score += 8;
        }

        return (int) $score;
    }
}

if (!function_exists('my_theme_get_home_featured_product_sort_state')) {
    function my_theme_get_home_featured_product_sort_state($product = null, $brand_slug = '')
    {
        static $cache = [];

        $product = ($product instanceof WC_Product) ? $product : (function_exists('wc_get_product') ? wc_get_product($product) : null);
        if (!$product instanceof WC_Product) {
            return [
                'score' => -999999,
                'sales_total' => 0,
                'created_ts' => 0,
                'name' => '',
            ];
        }

        $product_id = (int) $product->get_id();
        $brand_slug = sanitize_title((string) $brand_slug);
        $cache_key = $product_id . ':' . $brand_slug;
        if (isset($cache[$cache_key])) {
            return $cache[$cache_key];
        }

        $sales_total = method_exists($product, 'get_total_sales')
            ? (int) $product->get_total_sales()
            : (int) get_post_meta($product_id, 'total_sales', true);
        $price_value = function_exists('my_theme_get_default_loop_price')
            ? (float) my_theme_get_default_loop_price($product)
            : (float) $product->get_price();
        $is_featured = method_exists($product, 'is_featured') && $product->is_featured();
        $is_in_stock = method_exists($product, 'is_in_stock') && $product->is_in_stock();
        $created_at = method_exists($product, 'get_date_created') ? $product->get_date_created() : null;
        $created_ts = ($created_at instanceof WC_DateTime) ? (int) $created_at->getTimestamp() : 0;
        $days_old = $created_ts > 0 ? max(0, (int) floor((time() - $created_ts) / DAY_IN_SECONDS)) : 9999;

        $score = 0;
        if ($is_featured) {
            $score += 140;
        }
        if ($sales_total > 0) {
            $score += min(90, $sales_total);
            $score += min(24, (int) floor(log($sales_total + 1, 2) * 3));
        }
        if ($is_in_stock) {
            $score += 18;
        }
        if ($price_value > 0) {
            $score += 12;
        }
        if ($days_old <= 45) {
            $score += 14;
        } elseif ($days_old <= 120) {
            $score += 9;
        } elseif ($days_old <= 240) {
            $score += 4;
        }
        $catalog_profile = function_exists('my_theme_get_product_catalog_profile')
            ? my_theme_get_product_catalog_profile($product)
            : [];
        $product_brand_slug = isset($catalog_profile['brand_slug'])
            ? sanitize_title((string) $catalog_profile['brand_slug'])
            : '';
        if ($brand_slug !== '' && $product_brand_slug === $brand_slug) {
            $score += 8;
        }

        $cache[$cache_key] = [
            'score' => (int) $score,
            'sales_total' => max(0, (int) $sales_total),
            'created_ts' => max(0, (int) $created_ts),
            'name' => isset($catalog_profile['display_name']) && (string) $catalog_profile['display_name'] !== ''
                ? (string) $catalog_profile['display_name']
                : (string) $product->get_name(),
        ];

        return $cache[$cache_key];
    }
}

if (!function_exists('my_theme_sort_home_featured_products')) {
    function my_theme_sort_home_featured_products(array $products, $brand_slug = '')
    {
        $ranked_products = [];
        foreach ($products as $product) {
            if (!$product instanceof WC_Product) {
                continue;
            }

            $ranked_products[] = [
                'product' => $product,
                'state' => function_exists('my_theme_get_home_featured_product_sort_state')
                    ? my_theme_get_home_featured_product_sort_state($product, $brand_slug)
                    : [
                        'score' => my_theme_score_home_featured_product($product, $brand_slug),
                        'sales_total' => 0,
                        'created_ts' => 0,
                        'name' => (string) $product->get_name(),
                    ],
            ];
        }

        usort($ranked_products, static function ($a, $b) {
            $state_a = isset($a['state']) && is_array($a['state']) ? $a['state'] : [];
            $state_b = isset($b['state']) && is_array($b['state']) ? $b['state'] : [];

            $score_a = isset($state_a['score']) ? (int) $state_a['score'] : -999999;
            $score_b = isset($state_b['score']) ? (int) $state_b['score'] : -999999;
            if ($score_a !== $score_b) {
                return ($score_a > $score_b) ? -1 : 1;
            }

            $sales_a = isset($state_a['sales_total']) ? (int) $state_a['sales_total'] : 0;
            $sales_b = isset($state_b['sales_total']) ? (int) $state_b['sales_total'] : 0;
            if ($sales_a !== $sales_b) {
                return ($sales_a > $sales_b) ? -1 : 1;
            }

            $created_a = isset($state_a['created_ts']) ? (int) $state_a['created_ts'] : 0;
            $created_b = isset($state_b['created_ts']) ? (int) $state_b['created_ts'] : 0;
            if ($created_a !== $created_b) {
                return ($created_a > $created_b) ? -1 : 1;
            }

            return strnatcasecmp((string) ($state_a['name'] ?? ''), (string) ($state_b['name'] ?? ''));
        });

        return array_values(array_filter(array_map(static function ($entry) {
            $product = isset($entry['product']) ? $entry['product'] : null;
            return ($product instanceof WC_Product) ? $product : null;
        }, $ranked_products)));
    }
}

if (!function_exists('my_theme_get_catalog_ranked_product_ids')) {
    function my_theme_get_catalog_ranked_product_ids($product_ids, $limit = 0)
    {
        static $request_cache = [];

        $product_ids = my_theme_normalize_product_id_list($product_ids);
        if (empty($product_ids)) {
            return [];
        }

        $limit = max(0, (int) $limit);
        $cache_version = (string) get_option('my_theme_filter_cache_version', '1');
        $digest = md5(implode(',', $product_ids));
        $request_key = $cache_version . ':' . $digest;
        if (!isset($request_cache[$request_key])) {
            $transient_key = 'my_theme_catalog_ranked_ids_v2_' . $cache_version . '_' . $digest;
            $cached = get_transient($transient_key);
            if (is_array($cached)) {
                $request_cache[$request_key] = function_exists('my_theme_preserve_product_id_order')
                    ? my_theme_preserve_product_id_order($cached)
                    : my_theme_normalize_product_id_list($cached);
            } else {
                $posts = get_posts([
                    'post_type' => 'product',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'post__in' => $product_ids,
                    'orderby' => 'post__in',
                    'suppress_filters' => true,
                    'no_found_rows' => true,
                    'ignore_sticky_posts' => true,
                    'update_post_meta_cache' => true,
                    'update_post_term_cache' => false,
                ]);

                $post_map = [];
                foreach ((array) $posts as $post) {
                    if ($post instanceof WP_Post && $post->ID > 0) {
                        $post_map[(int) $post->ID] = $post;
                    }
                }

                $featured_lookup = [];
                if (function_exists('wc_get_featured_product_ids')) {
                    foreach ((array) wc_get_featured_product_ids() as $featured_id) {
                        $featured_id = (int) $featured_id;
                        if ($featured_id > 0) {
                            $featured_lookup[$featured_id] = true;
                        }
                    }
                }

                $rank_product_map = function_exists('my_theme_get_product_object_map')
                    ? my_theme_get_product_object_map($product_ids)
                    : [];
                $scored = [];
                $now_ts = time();
                foreach ($product_ids as $product_id) {
                    $product_id = (int) $product_id;
                    if ($product_id <= 0) {
                        continue;
                    }

                    $post = isset($post_map[$product_id]) ? $post_map[$product_id] : null;
                    $product = isset($rank_product_map[$product_id]) ? $rank_product_map[$product_id] : null;
                    $sales_total = (int) get_post_meta($product_id, 'total_sales', true);
                    $price_value = ($product instanceof WC_Product && function_exists('my_theme_get_default_loop_price'))
                        ? (float) my_theme_get_default_loop_price($product)
                        : (float) get_post_meta($product_id, '_price', true);
                    $stock_status = (string) get_post_meta($product_id, '_stock_status', true);
                    $created_raw = $post instanceof WP_Post ? (string) $post->post_date_gmt : '';
                    $created_ts = $created_raw !== '' ? (int) strtotime($created_raw . ' GMT') : 0;
                    $days_old = $created_ts > 0 ? max(0, (int) floor(($now_ts - $created_ts) / DAY_IN_SECONDS)) : 9999;

                    $score = 0;
                    if (isset($featured_lookup[$product_id])) {
                        $score += 140;
                    }
                    if ($sales_total > 0) {
                        $score += min(90, $sales_total);
                        $score += min(24, (int) floor(log($sales_total + 1, 2) * 3));
                    }
                    if ($stock_status === 'instock') {
                        $score += 18;
                    }
                    if ($price_value > 0) {
                        $score += 12;
                    }
                    if ($days_old <= 45) {
                        $score += 14;
                    } elseif ($days_old <= 120) {
                        $score += 9;
                    } elseif ($days_old <= 240) {
                        $score += 4;
                    }

                    $scored[] = [
                        'id' => $product_id,
                        'score' => $score,
                        'sales_total' => max(0, $sales_total),
                        'created_ts' => max(0, $created_ts),
                        'name' => $post instanceof WP_Post ? (string) $post->post_title : '',
                    ];
                }

                usort($scored, static function (array $a, array $b): int {
                    $score_cmp = ((int) ($b['score'] ?? 0)) <=> ((int) ($a['score'] ?? 0));
                    if ($score_cmp !== 0) {
                        return $score_cmp;
                    }

                    $sales_cmp = ((int) ($b['sales_total'] ?? 0)) <=> ((int) ($a['sales_total'] ?? 0));
                    if ($sales_cmp !== 0) {
                        return $sales_cmp;
                    }

                    $created_cmp = ((int) ($b['created_ts'] ?? 0)) <=> ((int) ($a['created_ts'] ?? 0));
                    if ($created_cmp !== 0) {
                        return $created_cmp;
                    }

                    return strnatcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
                });

                $request_cache[$request_key] = array_values(array_filter(array_map(static function ($entry) {
                    return isset($entry['id']) ? (int) $entry['id'] : 0;
                }, $scored)));
                if (function_exists('my_theme_preserve_product_id_order')) {
                    $request_cache[$request_key] = my_theme_preserve_product_id_order($request_cache[$request_key]);
                }
                set_transient($transient_key, $request_cache[$request_key], 30 * MINUTE_IN_SECONDS);
            }
        }

        if ($limit > 0) {
            return array_slice($request_cache[$request_key], 0, $limit);
        }

        return $request_cache[$request_key];
    }
}

if (!function_exists('my_theme_get_visual_story_bank')) {
    function my_theme_get_visual_story_bank()
    {
        $bank = get_option('my_theme_visual_story_bank_v1', []);
        return is_array($bank) ? $bank : [];
    }
}

if (!function_exists('my_theme_get_visual_story_group_catalog')) {
    function my_theme_get_visual_story_group_catalog()
    {
        return [
            'interior' => [
                'label' => 'Sơn nội thất',
                'title' => 'Không gian nội thất nhà ở',
                'description' => 'Nhóm minh họa cho phòng khách, phòng ngủ và khu sinh hoạt trong nhà cần bề mặt đẹp, sạch và dễ lau chùi.',
                'url' => home_url('/giai-phap-son-noi-that'),
                'cta' => 'Xem giải pháp nội thất',
            ],
            'exterior' => [
                'label' => 'Sơn ngoại thất',
                'title' => 'Mặt tiền và tường ngoài trời',
                'description' => 'Nhóm minh họa cho mặt tiền, mảng tường ngoài trời và bề mặt cần giữ màu ổn định dưới nắng mưa.',
                'url' => home_url('/giai-phap-son-ngoai-that'),
                'cta' => 'Xem giải pháp ngoại thất',
            ],
            'waterproofing' => [
                'label' => 'Chống thấm',
                'title' => 'Mái, sân thượng và khu ẩm',
                'description' => 'Minh họa các tình huống cần xử lý chống thấm ở sân thượng, mái bằng, chân tường và bề mặt bê tông ngoài trời.',
                'url' => home_url('/giai-phap-chong-tham'),
                'cta' => 'Xem giải pháp chống thấm',
            ],
            'epoxy' => [
                'label' => 'Sơn epoxy',
                'title' => 'Sàn kho, gara và xưởng nhỏ',
                'description' => 'Nhóm ảnh ứng dụng cho nền sàn cần dễ vệ sinh, chịu tải và thi công theo hệ sàn công nghiệp hoặc gara.',
                'url' => home_url('/giai-phap-son-epoxy'),
                'cta' => 'Xem giải pháp epoxy',
            ],
            'metal' => [
                'label' => 'Sơn kim loại',
                'title' => 'Cửa sắt, lan can và cổng',
                'description' => 'Nhóm ảnh minh họa cho bề mặt kim loại ngoài trời cần xử lý rỉ và chọn đúng primer trước khi phủ màu.',
                'url' => home_url('/giai-phap-son-kim-loai'),
                'cta' => 'Xem giải pháp kim loại',
            ],
            'grout' => [
                'label' => 'Keo và ron gạch',
                'title' => 'Nhà tắm, bếp và bề mặt gạch',
                'description' => 'Minh họa cho khu vực ron gạch, nhà tắm và bề mặt gạch ốp lát cần chà ron sạch, ít bám bẩn hơn.',
                'url' => home_url('/giai-phap-keo-va-ron'),
                'cta' => 'Xem giải pháp keo và ron',
            ],
        ];
    }
}

if (!function_exists('my_theme_get_visual_story_items_by_group')) {
    function my_theme_get_visual_story_items_by_group($group_key = '')
    {
        $group_key = sanitize_key((string) $group_key);
        if ($group_key === '') {
            return [];
        }

        $bank = my_theme_get_visual_story_bank();
        $groups = isset($bank['groups']) && is_array($bank['groups']) ? $bank['groups'] : [];
        $items = isset($groups[$group_key]) && is_array($groups[$group_key]) ? $groups[$group_key] : [];
        $resolved = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $attachment_id = isset($item['attachment_id']) ? (int) $item['attachment_id'] : 0;
            if ($attachment_id <= 0 || !wp_attachment_is_image($attachment_id)) {
                continue;
            }

            $resolved[] = [
                'attachment_id' => $attachment_id,
                'caption' => isset($item['caption']) ? trim((string) $item['caption']) : '',
                'source_label' => isset($item['source_label']) ? trim((string) $item['source_label']) : '',
                'source_url' => isset($item['source_url']) ? trim((string) $item['source_url']) : '',
                'license' => isset($item['license']) ? trim((string) $item['license']) : '',
            ];
        }

        return $resolved;
    }
}

if (!function_exists('my_theme_get_visual_story_group_key_from_product_category_slug')) {
    function my_theme_get_visual_story_group_key_from_product_category_slug($slug = '')
    {
        $slug = sanitize_title((string) $slug);
        if ($slug === '') {
            return '';
        }

        $map = [
            'son-noi-that' => 'interior',
            'son-lot' => 'interior',
            'bot-tret' => 'interior',
            'son-ngoai-that' => 'exterior',
            'chong-tham' => 'waterproofing',
            'keo-va-phu-gia' => 'grout',
            'son-epoxy' => 'epoxy',
            'son-cong-nghiep' => 'epoxy',
            'son-kim-loai' => 'metal',
            'son-dau' => 'metal',
        ];

        return isset($map[$slug]) ? $map[$slug] : '';
    }
}

if (!function_exists('my_theme_get_visual_story_group_key_for_object')) {
    function my_theme_get_visual_story_group_key_for_object($object = null)
    {
        if (is_string($object)) {
            return sanitize_key($object);
        }

        if ($object instanceof WC_Product) {
            $primary = function_exists('my_theme_get_product_primary_category_term')
                ? my_theme_get_product_primary_category_term($object)
                : null;
            $slug = ($primary instanceof WP_Term) ? sanitize_title((string) $primary->slug) : '';
            return my_theme_get_visual_story_group_key_from_product_category_slug($slug);
        }

        $post = get_post($object);
        if (!($post instanceof WP_Post)) {
            return '';
        }

        $meta_group = sanitize_key((string) get_post_meta($post->ID, '_my_theme_visual_group', true));
        if ($meta_group !== '') {
            return $meta_group;
        }

        if ($post->post_type !== 'post') {
            return '';
        }

        $term_slugs = wp_get_post_terms($post->ID, 'category', ['fields' => 'slugs']);
        if (is_wp_error($term_slugs) || empty($term_slugs)) {
            return '';
        }

        $term_slugs = array_map('sanitize_title', (array) $term_slugs);
        foreach ($term_slugs as $term_slug) {
            $group_key = my_theme_get_visual_story_group_key_from_product_category_slug($term_slug);
            if ($group_key !== '') {
                return $group_key;
            }
        }

        return '';
    }
}

if (!function_exists('my_theme_get_visual_story_items_for_object')) {
    function my_theme_get_visual_story_items_for_object($object = null)
    {
        $group_key = my_theme_get_visual_story_group_key_for_object($object);
        if ($group_key === '') {
            return [];
        }

        return my_theme_get_visual_story_items_by_group($group_key);
    }
}

if (!function_exists('my_theme_render_visual_story_gallery')) {
    function my_theme_render_visual_story_gallery($object = null, array $args = [])
    {
        $items = my_theme_get_visual_story_items_for_object($object);
        if (empty($items)) {
            return;
        }

        $title = isset($args['title']) ? trim((string) $args['title']) : 'Hình minh họa công trình';
        $subtitle = isset($args['subtitle']) ? trim((string) $args['subtitle']) : 'Ảnh minh họa tham khảo theo nhóm ứng dụng để hình dung rõ hơn bề mặt và hạng mục thi công.';
        $section_class = isset($args['class']) ? trim((string) $args['class']) : '';
        ?>
        <section class="page-section visual-story<?php echo $section_class !== '' ? ' ' . esc_attr($section_class) : ''; ?>" aria-label="<?php echo esc_attr($title); ?>">
          <div class="section-heading">
            <div>
              <h2 class="section-title"><?php echo esc_html($title); ?></h2>
              <?php if ($subtitle !== '') : ?>
                <p class="section-sub"><?php echo esc_html($subtitle); ?></p>
              <?php endif; ?>
            </div>
          </div>
          <div class="visual-story-grid">
            <?php foreach ($items as $item) : ?>
              <?php
                $attachment_id = (int) $item['attachment_id'];
                $caption = trim((string) ($item['caption'] ?? ''));
                $source_label = trim((string) ($item['source_label'] ?? ''));
                $source_url = trim((string) ($item['source_url'] ?? ''));
                $license = trim((string) ($item['license'] ?? ''));
                $alt = $caption !== '' ? $caption : get_the_title($attachment_id);
              ?>
              <article class="visual-story-card">
                <div class="visual-story-card__figure">
                  <?php echo wp_get_attachment_image($attachment_id, 'large', false, ['loading' => 'lazy', 'alt' => $alt]); ?>
                </div>
                <?php if ($caption !== '' || $source_label !== '' || $license !== '') : ?>
                  <div class="visual-story-card__meta">
                    <?php if ($caption !== '') : ?>
                      <p class="visual-story-card__caption"><?php echo esc_html($caption); ?></p>
                    <?php endif; ?>
                    <?php if ($source_label !== '' || $license !== '') : ?>
                      <div class="visual-story-card__source">
                        <?php if ($source_url !== '') : ?>
                          <a href="<?php echo esc_url($source_url); ?>" target="_blank" rel="noopener nofollow"><?php echo esc_html($source_label !== '' ? $source_label : 'Nguồn ảnh'); ?></a>
                        <?php elseif ($source_label !== '') : ?>
                          <span><?php echo esc_html($source_label); ?></span>
                        <?php endif; ?>
                        <?php if ($license !== '') : ?>
                          <span class="visual-story-card__license"><?php echo esc_html($license); ?></span>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
        </section>
        <?php
    }
}

if (!function_exists('my_theme_render_visual_story_showcase')) {
    function my_theme_render_visual_story_showcase(array $group_keys = [], array $args = [])
    {
        $catalog = my_theme_get_visual_story_group_catalog();
        if (empty($group_keys)) {
            $group_keys = array_keys($catalog);
        }

        $cards = [];
        foreach ($group_keys as $group_key) {
            $group_key = sanitize_key((string) $group_key);
            if ($group_key === '' || !isset($catalog[$group_key])) {
                continue;
            }

            $items = my_theme_get_visual_story_items_by_group($group_key);
            if (empty($items)) {
                continue;
            }

            $meta = $catalog[$group_key];
            $cards[] = [
                'group_key' => $group_key,
                'label' => isset($meta['label']) ? (string) $meta['label'] : $group_key,
                'title' => isset($meta['title']) ? (string) $meta['title'] : $group_key,
                'description' => isset($meta['description']) ? (string) $meta['description'] : '',
                'url' => isset($meta['url']) ? (string) $meta['url'] : home_url('/'),
                'cta' => isset($meta['cta']) ? (string) $meta['cta'] : 'Xem thêm',
                'item' => $items[0],
                'count' => count($items),
            ];
        }

        if (empty($cards)) {
            return;
        }

        $title = isset($args['title']) ? trim((string) $args['title']) : 'Công trình & ứng dụng tiêu biểu';
        $subtitle = isset($args['subtitle']) ? trim((string) $args['subtitle']) : 'Xem nhanh các nhóm bề mặt và hạng mục thi công thường gặp để đi tiếp vào đúng giải pháp hoặc bài tư vấn phù hợp.';
        $section_class = isset($args['class']) ? trim((string) $args['class']) : '';
        ?>
        <section class="page-section visual-showcase<?php echo $section_class !== '' ? ' ' . esc_attr($section_class) : ''; ?>" aria-label="<?php echo esc_attr($title); ?>">
          <div class="section-heading">
            <div>
              <h2 class="section-title"><?php echo esc_html($title); ?></h2>
              <?php if ($subtitle !== '') : ?>
                <p class="section-sub"><?php echo esc_html($subtitle); ?></p>
              <?php endif; ?>
            </div>
          </div>
          <div class="visual-showcase-grid">
            <?php foreach ($cards as $card) : ?>
              <?php
                $item = (array) $card['item'];
                $attachment_id = isset($item['attachment_id']) ? (int) $item['attachment_id'] : 0;
                $caption = isset($item['caption']) ? trim((string) $item['caption']) : '';
                $source_label = isset($item['source_label']) ? trim((string) $item['source_label']) : '';
                $license = isset($item['license']) ? trim((string) $item['license']) : '';
                $img_alt = $caption !== '' ? $caption : (string) $card['title'];
              ?>
              <article class="visual-showcase-card">
                <a class="visual-showcase-card__thumb" href="<?php echo esc_url((string) $card['url']); ?>">
                  <?php echo wp_get_attachment_image($attachment_id, 'large', false, ['loading' => 'lazy', 'alt' => $img_alt]); ?>
                </a>
                <div class="visual-showcase-card__body">
                  <div class="visual-showcase-card__eyebrow"><?php echo esc_html((string) $card['label']); ?></div>
                  <h3 class="visual-showcase-card__title">
                    <a href="<?php echo esc_url((string) $card['url']); ?>"><?php echo esc_html((string) $card['title']); ?></a>
                  </h3>
                  <p class="visual-showcase-card__description"><?php echo esc_html((string) $card['description']); ?></p>
                  <?php if ($caption !== '') : ?>
                    <p class="visual-showcase-card__caption"><?php echo esc_html($caption); ?></p>
                  <?php endif; ?>
                </div>
                <div class="visual-showcase-card__actions">
                  <div class="visual-showcase-card__meta">
                    <?php if ($source_label !== '') : ?><span><?php echo esc_html($source_label); ?></span><?php endif; ?>
                    <?php if ($license !== '') : ?><span><?php echo esc_html($license); ?></span><?php endif; ?>
                    <span><?php echo esc_html((string) $card['count']); ?> ảnh</span>
                  </div>
                  <a class="btn btn-outline w-100" href="<?php echo esc_url((string) $card['url']); ?>"><?php echo esc_html((string) $card['cta']); ?></a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </section>
        <?php
    }
}

if (!function_exists('my_theme_render_solution_pathways')) {
    function my_theme_render_solution_pathways($current_group_key = '', array $args = [])
    {
        $catalog = my_theme_get_visual_story_group_catalog();
        if (empty($catalog)) {
            return;
        }

        $current_group_key = sanitize_key((string) $current_group_key);
        $limit = isset($args['limit']) ? max(1, (int) $args['limit']) : 3;
        $title = isset($args['title']) ? trim((string) $args['title']) : 'Xem thêm các giải pháp liên quan';
        $subtitle = isset($args['subtitle']) ? trim((string) $args['subtitle']) : 'Nếu nhu cầu của bạn còn giao nhau giữa nhiều bề mặt, có thể xem nhanh các nhóm dưới đây để chốt hướng phù hợp hơn.';
        $section_class = isset($args['class']) ? trim((string) $args['class']) : '';

        $cards = [];
        foreach ($catalog as $group_key => $meta) {
            $group_key = sanitize_key((string) $group_key);
            if ($group_key === '' || $group_key === $current_group_key) {
                continue;
            }

            $items = my_theme_get_visual_story_items_by_group($group_key);
            if (empty($items)) {
                continue;
            }

            $cards[] = [
                'label' => isset($meta['label']) ? (string) $meta['label'] : $group_key,
                'title' => isset($meta['title']) ? (string) $meta['title'] : $group_key,
                'description' => isset($meta['description']) ? (string) $meta['description'] : '',
                'url' => isset($meta['url']) ? (string) $meta['url'] : home_url('/'),
                'cta' => isset($meta['cta']) ? (string) $meta['cta'] : 'Xem thêm',
                'item' => (array) $items[0],
            ];
        }

        if (empty($cards)) {
            return;
        }

        $cards = array_slice($cards, 0, $limit);
        ?>
        <section class="page-section solution-pathways<?php echo $section_class !== '' ? ' ' . esc_attr($section_class) : ''; ?>" aria-label="<?php echo esc_attr($title); ?>">
          <div class="section-heading solution-pathways__head">
            <div>
              <h2 class="section-title"><?php echo esc_html($title); ?></h2>
              <?php if ($subtitle !== '') : ?>
                <p class="section-sub"><?php echo esc_html($subtitle); ?></p>
              <?php endif; ?>
            </div>
          </div>
          <div class="solution-pathways__grid">
            <?php foreach ($cards as $card) : ?>
              <?php
              $item = (array) $card['item'];
              $attachment_id = isset($item['attachment_id']) ? (int) $item['attachment_id'] : 0;
              $caption = isset($item['caption']) ? trim((string) $item['caption']) : '';
              $img_alt = $caption !== '' ? $caption : (string) $card['title'];
              ?>
              <article class="solution-pathway-card">
                <a class="solution-pathway-card__thumb" href="<?php echo esc_url((string) $card['url']); ?>">
                  <?php echo wp_get_attachment_image($attachment_id, 'large', false, ['loading' => 'lazy', 'alt' => $img_alt]); ?>
                </a>
                <div class="solution-pathway-card__body">
                  <div class="solution-pathway-card__eyebrow"><?php echo esc_html((string) $card['label']); ?></div>
                  <h3 class="solution-pathway-card__title">
                    <a href="<?php echo esc_url((string) $card['url']); ?>"><?php echo esc_html((string) $card['title']); ?></a>
                  </h3>
                  <p class="solution-pathway-card__description"><?php echo esc_html((string) $card['description']); ?></p>
                </div>
                <div class="solution-pathway-card__actions">
                  <a class="btn btn-outline w-100" href="<?php echo esc_url((string) $card['url']); ?>"><?php echo esc_html((string) $card['cta']); ?></a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </section>
        <?php
    }
}

if (!function_exists('my_theme_render_service_compass')) {
    function my_theme_render_service_compass(array $args = [])
    {
        $store_snapshot = function_exists('my_theme_get_store_snapshot') ? my_theme_get_store_snapshot() : [];
        $shop_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
        $solutions_url = home_url('/giai-phap');
        $contact_url = home_url('/lien-he');
        $phone_href = isset($store_snapshot['phone_href']) ? (string) $store_snapshot['phone_href'] : 'tel:0944857999';
        $phone_display = isset($store_snapshot['phone_display']) ? (string) $store_snapshot['phone_display'] : '0944 857 999';
        $zalo_url = isset($store_snapshot['zalo_url']) ? (string) $store_snapshot['zalo_url'] : 'https://zalo.me/0944857999';
        $hours_display = isset($store_snapshot['hours_display']) ? (string) $store_snapshot['hours_display'] : 'Thứ 2 - Thứ 7: 7:30 - 18:00';
        $service_areas = isset($store_snapshot['service_areas_display']) ? (string) $store_snapshot['service_areas_display'] : 'TP.HCM, Bình Dương, Đồng Nai';

        $title = isset($args['title']) ? trim((string) $args['title']) : 'Chọn cách đi nhanh nhất để chốt vật tư';
        $subtitle = isset($args['subtitle']) ? trim((string) $args['subtitle']) : 'Web đã tách sẵn 3 đường đi: tự mở kho sản phẩm, đi theo nhóm giải pháp hoặc gửi ảnh hiện trạng để đội kỹ thuật điều hướng lại.';
        $section_class = isset($args['class']) ? trim((string) $args['class']) : '';
        $eyebrow = isset($args['eyebrow']) ? trim((string) $args['eyebrow']) : 'Quy trình chốt đơn';
        $panel_title = isset($args['panel_title']) ? trim((string) $args['panel_title']) : 'Cam kết vận hành khi hỗ trợ báo giá';
        $panel_intro = isset($args['panel_intro']) ? trim((string) $args['panel_intro']) : 'Giữ đúng dữ liệu cần chốt: bề mặt, quy cách, tiến độ giao và chứng từ nếu công trình cần.';

        $cards = isset($args['cards']) && is_array($args['cards']) ? $args['cards'] : [];
        if (empty($cards)) {
            $cards = [
                [
                    'eyebrow' => 'Đã có mã sơn',
                    'title' => 'Mở kho sản phẩm',
                    'description' => 'Phù hợp khi bạn đã biết thương hiệu, dòng sơn hoặc muốn lọc trực tiếp theo danh mục và quy cách.',
                    'url' => $shop_url,
                    'cta' => 'Mở cửa hàng',
                ],
                [
                    'eyebrow' => 'Theo hạng mục',
                    'title' => 'Đi theo giải pháp',
                    'description' => 'Phù hợp khi đang chọn theo bề mặt như nội thất, ngoại thất, chống thấm, epoxy, kim loại hoặc keo ron.',
                    'url' => $solutions_url,
                    'cta' => 'Mở giải pháp',
                ],
                [
                    'eyebrow' => 'Chưa chắc mã',
                    'title' => 'Gửi ảnh hiện trạng',
                    'description' => 'Phù hợp khi cần đội kỹ thuật gọi lại, đối chiếu bề mặt và chốt nhanh hệ vật tư trước khi đặt hàng.',
                    'url' => $contact_url,
                    'cta' => 'Gửi yêu cầu báo giá',
                ],
            ];
        }

        $commitments = isset($args['commitments']) && is_array($args['commitments']) ? $args['commitments'] : [];
        if (empty($commitments)) {
            $commitments = [
                'Phản hồi trong giờ làm việc, ngoài giờ vẫn nhận yêu cầu qua Zalo.',
                'Tư vấn theo bề mặt, quy cách và tiến độ giao thay vì báo mã rời rạc.',
                'Có hỗ trợ hóa đơn, xác nhận quy cách và chia đợt giao cho công trình khi cần.',
            ];
        }
        ?>
        <section class="page-section service-compass<?php echo $section_class !== '' ? ' ' . esc_attr($section_class) : ''; ?>" aria-label="<?php echo esc_attr($title); ?>">
          <div class="section-heading service-compass__head">
            <div>
              <?php if ($eyebrow !== '') : ?>
                <p class="eyebrow eyebrow-muted"><?php echo esc_html($eyebrow); ?></p>
              <?php endif; ?>
              <h2 class="section-title"><?php echo esc_html($title); ?></h2>
              <?php if ($subtitle !== '') : ?>
                <p class="section-sub"><?php echo esc_html($subtitle); ?></p>
              <?php endif; ?>
            </div>
          </div>

          <div class="service-compass__grid">
            <div class="service-compass__cards">
              <?php foreach ($cards as $card) : ?>
                <?php
                $card = is_array($card) ? $card : [];
                $card_eyebrow = isset($card['eyebrow']) ? trim((string) $card['eyebrow']) : '';
                $card_title = isset($card['title']) ? trim((string) $card['title']) : '';
                $card_description = isset($card['description']) ? trim((string) $card['description']) : '';
                $card_url = isset($card['url']) ? (string) $card['url'] : $shop_url;
                $card_cta = isset($card['cta']) ? trim((string) $card['cta']) : 'Mở ngay';
                if ($card_title === '') {
                    continue;
                }
                ?>
                <article class="service-compass-card">
                  <?php if ($card_eyebrow !== '') : ?>
                    <p class="service-compass-card__eyebrow"><?php echo esc_html($card_eyebrow); ?></p>
                  <?php endif; ?>
                  <h3 class="service-compass-card__title"><?php echo esc_html($card_title); ?></h3>
                  <?php if ($card_description !== '') : ?>
                    <p class="service-compass-card__description"><?php echo esc_html($card_description); ?></p>
                  <?php endif; ?>
                  <a class="btn btn-outline w-100" href="<?php echo esc_url($card_url); ?>"><?php echo esc_html($card_cta); ?></a>
                </article>
              <?php endforeach; ?>
            </div>

            <aside class="service-compass__panel">
              <p class="eyebrow eyebrow-muted">Cam kết vận hành</p>
              <h3><?php echo esc_html($panel_title); ?></h3>
              <?php if ($panel_intro !== '') : ?>
                <p class="service-compass__panel-intro"><?php echo esc_html($panel_intro); ?></p>
              <?php endif; ?>

              <ol class="list-numbered landing-checklist">
                <?php foreach ($commitments as $commitment) : ?>
                  <?php $commitment = trim((string) $commitment); ?>
                  <?php if ($commitment === '') { continue; } ?>
                  <li><?php echo esc_html($commitment); ?></li>
                <?php endforeach; ?>
              </ol>

              <div class="service-compass__meta" aria-label="Thông tin hỗ trợ">
                <span class="service-compass__meta-item"><?php echo esc_html($hours_display); ?></span>
                <span class="service-compass__meta-item"><?php echo esc_html($service_areas); ?></span>
                <span class="service-compass__meta-item">Hotline <?php echo esc_html($phone_display); ?></span>
              </div>

              <div class="service-compass__actions">
                <a class="btn btn-primary btn-sm" href="<?php echo esc_url($phone_href); ?>">Gọi <?php echo esc_html($phone_display); ?></a>
                <a class="btn btn-outline btn-sm" href="<?php echo esc_url($zalo_url); ?>" target="_blank" rel="noopener">Zalo kỹ thuật</a>
                <a class="btn btn-accent btn-sm" href="<?php echo esc_url($contact_url); ?>">Gửi yêu cầu</a>
              </div>
            </aside>
          </div>
        </section>
        <?php
    }
}

if (!function_exists('my_theme_render_quick_answers')) {
    function my_theme_render_quick_answers(array $args = [])
    {
        $cards = isset($args['cards']) && is_array($args['cards']) ? $args['cards'] : [];
        if (empty($cards)) {
            if (!function_exists('my_theme_get_faq_schema_items')) {
                return;
            }

            $faq_items = my_theme_get_faq_schema_items();
            if (empty($faq_items) || !is_array($faq_items)) {
                return;
            }

            $indexes = isset($args['indexes']) && is_array($args['indexes']) ? $args['indexes'] : [0, 1, 4];
            foreach ($indexes as $index) {
                $index = (int) $index;
                if (!isset($faq_items[$index]) || !is_array($faq_items[$index])) {
                    continue;
                }
                $question = trim((string) ($faq_items[$index]['question'] ?? ''));
                $answer = trim((string) ($faq_items[$index]['answer'] ?? ''));
                if ($question === '' || $answer === '') {
                    continue;
                }
                $cards[] = [
                    'question' => $question,
                    'answer' => $answer,
                ];
            }
        }

        if (empty($cards)) {
            return;
        }

        $title = isset($args['title']) ? trim((string) $args['title']) : 'Khách thường hỏi gì trước khi chốt đơn?';
        $subtitle = isset($args['subtitle']) ? trim((string) $args['subtitle']) : 'Tóm tắt nhanh các câu hỏi thường gặp để khách tự đối chiếu trước khi gọi hoặc gửi nhu cầu.';
        $section_class = isset($args['class']) ? trim((string) $args['class']) : '';
        $eyebrow = isset($args['eyebrow']) ? trim((string) $args['eyebrow']) : 'FAQ ngắn';
        $faq_url = home_url('/faq');
        $contact_url = home_url('/lien-he');
        ?>
        <section class="page-section quick-answers<?php echo $section_class !== '' ? ' ' . esc_attr($section_class) : ''; ?>" aria-label="<?php echo esc_attr($title); ?>">
          <div class="section-heading quick-answers__head">
            <div>
              <?php if ($eyebrow !== '') : ?>
                <p class="eyebrow eyebrow-muted"><?php echo esc_html($eyebrow); ?></p>
              <?php endif; ?>
              <h2 class="section-title"><?php echo esc_html($title); ?></h2>
              <?php if ($subtitle !== '') : ?>
                <p class="section-sub"><?php echo esc_html($subtitle); ?></p>
              <?php endif; ?>
            </div>
            <div class="quick-answers__actions">
              <a class="chip" href="<?php echo esc_url($faq_url); ?>">Mở FAQ đầy đủ</a>
              <a class="chip" href="<?php echo esc_url($contact_url); ?>">Gửi yêu cầu báo giá</a>
            </div>
          </div>

          <div class="info-grid quick-answers__grid">
            <?php foreach ($cards as $card) : ?>
              <article class="info-card quick-answer-card">
                <h3><?php echo esc_html((string) $card['question']); ?></h3>
                <p><?php echo esc_html((string) $card['answer']); ?></p>
              </article>
            <?php endforeach; ?>
          </div>
        </section>
        <?php
    }
}

if (!function_exists('my_theme_render_article_recommendations')) {
    function my_theme_render_article_recommendations(array $slugs = [], array $args = [])
    {
        $cards = [];
        foreach ($slugs as $slug) {
            $slug = sanitize_title((string) $slug);
            if ($slug === '') {
                continue;
            }

            $post = get_page_by_path($slug, OBJECT, 'post');
            if (!($post instanceof WP_Post) || $post->post_status !== 'publish') {
                continue;
            }

            $excerpt = has_excerpt($post)
                ? get_the_excerpt($post)
                : wp_trim_words(wp_strip_all_tags((string) $post->post_content), 24, '...');
            $cards[] = [
                'title' => get_the_title($post),
                'url' => get_permalink($post),
                'excerpt' => trim((string) $excerpt),
                'thumb_id' => (int) get_post_thumbnail_id($post->ID),
            ];
        }

        if (empty($cards)) {
            return;
        }

        $title = isset($args['title']) ? trim((string) $args['title']) : 'Bài nên đọc trước khi chốt vật tư';
        $subtitle = isset($args['subtitle']) ? trim((string) $args['subtitle']) : 'Một số bài tư vấn nền tảng để khách xem nhanh trước khi chọn hệ sơn, chống thấm hay vật tư ốp lát.';
        $section_class = isset($args['class']) ? trim((string) $args['class']) : '';
        ?>
        <section class="page-section solution-pathways article-recommendations<?php echo $section_class !== '' ? ' ' . esc_attr($section_class) : ''; ?>" aria-label="<?php echo esc_attr($title); ?>">
          <div class="section-heading solution-pathways__head">
            <div>
              <h2 class="section-title"><?php echo esc_html($title); ?></h2>
              <?php if ($subtitle !== '') : ?>
                <p class="section-sub"><?php echo esc_html($subtitle); ?></p>
              <?php endif; ?>
            </div>
          </div>
          <div class="solution-pathways__grid">
            <?php foreach ($cards as $card) : ?>
              <?php
              $thumb_id = (int) ($card['thumb_id'] ?? 0);
              $title_attr = (string) ($card['title'] ?? '');
              ?>
              <article class="solution-pathway-card article-recommendation-card">
                <a class="solution-pathway-card__thumb" href="<?php echo esc_url((string) $card['url']); ?>">
                  <?php
                  if ($thumb_id > 0) {
                      echo wp_get_attachment_image($thumb_id, 'large', false, ['loading' => 'lazy', 'alt' => $title_attr]);
                  } else {
                      echo '<span class="product-card__thumb--fallback"></span>';
                  }
                  ?>
                </a>
                <div class="solution-pathway-card__body">
                  <div class="solution-pathway-card__eyebrow">Bài tư vấn</div>
                  <h3 class="solution-pathway-card__title">
                    <a href="<?php echo esc_url((string) $card['url']); ?>"><?php echo esc_html((string) $card['title']); ?></a>
                  </h3>
                  <p class="solution-pathway-card__description"><?php echo esc_html((string) $card['excerpt']); ?></p>
                </div>
                <div class="solution-pathway-card__actions">
                  <a class="btn btn-outline w-100" href="<?php echo esc_url((string) $card['url']); ?>">Xem bài tư vấn</a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </section>
        <?php
    }
}

if (!function_exists('my_theme_get_faq_schema_items')) {
    function my_theme_get_faq_schema_items()
    {
        return [
            [
                'question' => 'Tôi cần tư vấn màu và diện tích, liên hệ ai?',
                'answer' => 'Gọi số tư vấn 0944 857 999 hoặc nhắn Zalo 0944 857 999 để đội kỹ thuật hỗ trợ đo bóc khối lượng và đề xuất hệ sơn trong giờ hành chính.',
            ],
            [
                'question' => 'Bao lâu nhận được hàng?',
                'answer' => 'Trong nội thành TP.HCM thời gian giao thường trong 24 giờ. Các tỉnh lân cận dự kiến 24 đến 48 giờ, hàng pha màu gấp nên báo trước để ưu tiên.',
            ],
            [
                'question' => 'Có xuất hóa đơn VAT và chứng nhận CO/CQ không?',
                'answer' => 'Có. Khách hàng chỉ cần cung cấp thông tin công ty khi đặt hàng để đại lý giao kèm hóa đơn và chứng từ hãng theo yêu cầu.',
            ],
            [
                'question' => 'Điều kiện đổi trả như thế nào?',
                'answer' => 'Sơn đã pha màu không áp dụng đổi trả. Các trường hợp giao sai mã, lỗi bao bì hoặc lỗi kỹ thuật sẽ được tiếp nhận xử lý trong vòng 48 giờ kể từ khi nhận hàng.',
            ],
            [
                'question' => 'Tôi muốn lấy giá đại lý số lượng thì làm sao?',
                'answer' => 'Liên hệ số tư vấn để nhận bảng chiết khấu theo thương hiệu, dung tích và số lượng. Đơn công trình có thể sắp lịch giao bằng xe tải hoặc xe cẩu nếu cần.',
            ],
        ];
    }
}

if (!function_exists('my_theme_get_group_knowledge_bundle')) {
    function my_theme_get_group_knowledge_bundle($group_key = '')
    {
        $group_key = sanitize_key((string) $group_key);
        if ($group_key === '') {
            return [];
        }

        $bundles = [
            'interior' => [
                'quick_answers' => [
                    [
                        'question' => 'Nhà có trẻ nhỏ hoặc tường hay bám bẩn nên ưu tiên gì?',
                        'answer' => 'Nên đi theo nhóm sơn nội thất có màng sơn chắc và lau chùi tốt, rồi mới chọn tiếp độ bóng hay mờ theo từng phòng.',
                    ],
                    [
                        'question' => 'Có cần dùng lót và bột trét cùng lúc không?',
                        'answer' => 'Nếu là tường mới, tường đã sửa vá hoặc bề mặt hút nước không đều thì nên chốt đủ bộ bột trét, sơn lót và sơn phủ để màu lên ổn định hơn.',
                    ],
                    [
                        'question' => 'Nên gửi gì trước khi hỏi báo giá nội thất?',
                        'answer' => 'Chỉ cần diện tích, số phòng, mức ưu tiên lau chùi hay tối ưu chi phí và màu đang cân nhắc là đội kỹ thuật có thể gợi ý nhanh nhóm vật tư phù hợp.',
                    ],
                ],
                'article_slugs' => [
                    'cach-chon-son-noi-that-de-lau-chui-cho-nha-o',
                    'khi-nao-can-son-lot-khang-kiem',
                    'so-sanh-dulux-jotun-nippon-cho-nhu-cau-pho-thong',
                ],
            ],
            'exterior' => [
                'quick_answers' => [
                    [
                        'question' => 'Tường mặt tiền bị nắng mưa nhiều nên ưu tiên tiêu chí nào?',
                        'answer' => 'Nên ưu tiên độ bền màu, chống bám bụi và khả năng làm việc đồng bộ với lớp lót kháng kiềm trước khi so màu hoặc giá.',
                    ],
                    [
                        'question' => 'Ngoại thất có cần xử lý thấm trước khi sơn lại không?',
                        'answer' => 'Nếu tường đã có dấu hiệu thấm, rêu mốc hoặc loang ẩm thì nên xử lý chống thấm trước, vì sơn phủ ngoài không giải quyết triệt để nguyên nhân thấm.',
                    ],
                    [
                        'question' => 'Khách nên chuẩn bị gì để báo giá ngoại thất sát hơn?',
                        'answer' => 'Nên gửi ảnh mặt tiền, tình trạng tường cũ hay mới, diện tích ước tính và thời gian cần giao vật tư để chốt hệ lót và phủ gọn hơn.',
                    ],
                ],
                'article_slugs' => [
                    'cach-chon-son-ngoai-that-ben-mau-cho-mat-tien',
                    'khi-nao-can-son-lot-khang-kiem',
                    'so-sanh-dulux-jotun-nippon-cho-nhu-cau-pho-thong',
                ],
            ],
            'waterproofing' => [
                'quick_answers' => [
                    [
                        'question' => 'Sân thượng, tường ngoài hay nhà vệ sinh có dùng chung một hệ chống thấm không?',
                        'answer' => 'Không nên gom chung. Mỗi hạng mục sẽ khác về bề mặt, độ nứt, đọng nước và mức co giãn nên cần chốt đúng nhóm chống thấm trước khi lên giá.',
                    ],
                    [
                        'question' => 'Khi nào nên ưu tiên hệ đàn hồi hoặc 2 thành phần?',
                        'answer' => 'Nếu nền có rung động nhẹ, nứt hairline hoặc cần độ ổn định tốt hơn ngoài trời, nên hỏi thêm về hệ linh hoạt hoặc 2 thành phần thay vì chọn theo tên hãng đơn thuần.',
                    ],
                    [
                        'question' => 'Gửi gì để đội kỹ thuật chốt chống thấm nhanh?',
                        'answer' => 'Ảnh hiện trạng, vị trí thấm, diện tích, có đọng nước hay không và bề mặt đang là bê tông, tường hay sàn là đủ để khoanh nhanh nhóm vật tư.',
                    ],
                ],
                'article_slugs' => [
                    'chong-tham-san-thuong-nen-dung-he-nao',
                    'chong-tham-tuong-ngoai-troi-khi-da-tham-nuoc',
                    'khi-nao-can-son-lot-khang-kiem',
                ],
            ],
            'epoxy' => [
                'quick_answers' => [
                    [
                        'question' => 'Sàn gara, kho nhỏ và xưởng mini khác nhau ở điểm nào khi chọn epoxy?',
                        'answer' => 'Khác nhiều ở tải trọng, mức mài mòn và yêu cầu vệ sinh. Muốn chốt đúng hệ phải biết nền đang dùng cho xe máy, xe đẩy hay khu kỹ thuật nhẹ.',
                    ],
                    [
                        'question' => 'Có nên hỏi lớp primer và xử lý nền trước khi xem topcoat không?',
                        'answer' => 'Có. Với epoxy, nền và primer thường quyết định độ bám và độ bền không kém lớp phủ hoàn thiện nên không nên báo riêng một mã topcoat.',
                    ],
                    [
                        'question' => 'Thông tin nào giúp báo giá epoxy sát nhất?',
                        'answer' => 'Diện tích, ảnh nền sàn, tình trạng bụi hoặc nứt, mục đích sử dụng và thời gian cần đưa sàn vào khai thác là bộ thông tin nên gửi trước.',
                    ],
                ],
                'article_slugs' => [
                    'cach-chon-son-epoxy-cho-san-nha-xuong-nho',
                    'chong-tham-san-thuong-nen-dung-he-nao',
                    'so-sanh-dulux-jotun-nippon-cho-nhu-cau-pho-thong',
                ],
            ],
            'metal' => [
                'quick_answers' => [
                    [
                        'question' => 'Kim loại mới và kim loại đã rỉ có thể dùng cùng một quy trình không?',
                        'answer' => 'Không nên. Mức độ rỉ và lớp sơn cũ quyết định khâu làm sạch, primer chống rỉ và số lớp phủ phù hợp.',
                    ],
                    [
                        'question' => 'Có nhất thiết phải đi đủ primer và lớp phủ màu không?',
                        'answer' => 'Với cửa sắt, lan can và hạng mục ngoài trời, nên chốt theo bộ primer chống rỉ và lớp phủ tương thích để màng sơn bền hơn.',
                    ],
                    [
                        'question' => 'Khách nên gửi gì để chốt nhanh vật tư kim loại?',
                        'answer' => 'Chỉ cần loại hạng mục, mức rỉ hiện tại, vị trí trong nhà hay ngoài trời và diện tích hoặc số mét dài là đủ để lên bộ vật tư sơ bộ.',
                    ],
                ],
                'article_slugs' => [
                    'cach-chon-son-chong-ri-cho-cua-sat-va-lan-can',
                    'khi-nao-can-son-lot-khang-kiem',
                    'so-sanh-dulux-jotun-nippon-cho-nhu-cau-pho-thong',
                ],
            ],
            'grout' => [
                'quick_answers' => [
                    [
                        'question' => 'Keo dán gạch và chà ron có thay thế cho nhau không?',
                        'answer' => 'Không. Keo dán gạch xử lý bám dính, còn chà ron xử lý khe ron và bề mặt hoàn thiện nên cần chốt đúng vai trò trước khi lên đơn.',
                    ],
                    [
                        'question' => 'Ron khu bếp và nhà tắm nên ưu tiên gì?',
                        'answer' => 'Nên ưu tiên chống bám bẩn, chống mốc và chọn đúng độ rộng khe ron để bề mặt dùng lâu vẫn gọn và dễ vệ sinh.',
                    ],
                    [
                        'question' => 'Cần chuẩn bị gì khi hỏi vật tư ốp lát?',
                        'answer' => 'Ảnh khu vực thi công, loại gạch, độ rộng khe ron, vị trí sử dụng và m2 dự kiến sẽ giúp chốt keo dán và chà ron nhanh hơn.',
                    ],
                ],
                'article_slugs' => [
                    'cach-chon-keo-cha-ron-cho-nha-tam-va-bep',
                    'chong-tham-san-thuong-nen-dung-he-nao',
                    'bot-tret-noi-that-va-ngoai-that-khac-nhau-o-dau',
                ],
            ],
        ];

        return isset($bundles[$group_key]) ? (array) $bundles[$group_key] : [];
    }
}

if (!function_exists('my_theme_render_group_knowledge_sections')) {
    function my_theme_render_group_knowledge_sections($group_key = '', array $args = [])
    {
        $group_key = sanitize_key((string) $group_key);
        if ($group_key === '') {
            return;
        }

        $bundle = function_exists('my_theme_get_group_knowledge_bundle')
            ? (array) my_theme_get_group_knowledge_bundle($group_key)
            : [];
        if (empty($bundle)) {
            return;
        }

        $quick_answers = isset($bundle['quick_answers']) && is_array($bundle['quick_answers'])
            ? $bundle['quick_answers']
            : [];
        $article_slugs = isset($bundle['article_slugs']) && is_array($bundle['article_slugs'])
            ? $bundle['article_slugs']
            : [];
        if (empty($quick_answers) && empty($article_slugs)) {
            return;
        }

        $catalog = function_exists('my_theme_get_visual_story_group_catalog')
            ? my_theme_get_visual_story_group_catalog()
            : [];
        $group_meta = isset($catalog[$group_key]) && is_array($catalog[$group_key]) ? $catalog[$group_key] : [];
        $group_label = isset($group_meta['label']) ? trim((string) $group_meta['label']) : 'giải pháp này';
        $group_label_lower = function_exists('mb_strtolower')
            ? mb_strtolower($group_label, 'UTF-8')
            : strtolower($group_label);

        if (!empty($quick_answers) && function_exists('my_theme_render_quick_answers')) {
            my_theme_render_quick_answers([
                'cards' => $quick_answers,
                'class' => isset($args['quick_class']) ? trim((string) $args['quick_class']) : 'quick-answers--solution',
                'eyebrow' => isset($args['quick_eyebrow']) ? trim((string) $args['quick_eyebrow']) : 'FAQ ngắn trước khi chốt',
                'title' => isset($args['quick_title']) ? trim((string) $args['quick_title']) : 'Một vài câu hỏi nên chốt trước khi chọn ' . $group_label_lower,
                'subtitle' => isset($args['quick_subtitle']) ? trim((string) $args['quick_subtitle']) : 'Các câu hỏi ngắn dưới đây giúp bạn tự đối chiếu nhanh hiện trạng, cách dùng và dữ liệu nên gửi trước khi chốt vật tư.',
            ]);
        }

        if (!empty($article_slugs) && function_exists('my_theme_render_article_recommendations')) {
            my_theme_render_article_recommendations($article_slugs, [
                'class' => isset($args['article_class']) ? trim((string) $args['article_class']) : 'article-recommendations--solution',
                'title' => isset($args['article_title']) ? trim((string) $args['article_title']) : 'Bài nên đọc thêm về ' . $group_label_lower,
                'subtitle' => isset($args['article_subtitle']) ? trim((string) $args['article_subtitle']) : 'Nếu bạn vẫn còn đang so giữa nhiều hệ vật tư hoặc nhiều cách xử lý bề mặt, nên đọc nhanh vài bài nền tảng này trước khi chốt đơn.',
            ]);
        }
    }
}

if (!function_exists('my_theme_get_shop_filter_url')) {
    function my_theme_get_shop_filter_url(array $args = [])
    {
        $shop_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
        $query_args = [];

        $category_slug = isset($args['category_slug']) ? sanitize_title((string) $args['category_slug']) : '';
        if ($category_slug !== '' && taxonomy_exists('product_cat')) {
            $term = get_term_by('slug', $category_slug, 'product_cat');
            if ($term instanceof WP_Term && !empty($term->term_id)) {
                $query_args['category'] = (int) $term->term_id;
            }
        }

        $brand_slug = isset($args['brand_slug']) ? sanitize_title((string) $args['brand_slug']) : '';
        if ($brand_slug !== '') {
            $query_args['brand'] = $brand_slug;
        }

        $q = isset($args['q']) ? trim((string) $args['q']) : '';
        if ($q !== '') {
            $query_args['q'] = $q;
        }

        if (empty($query_args)) {
            return $shop_url;
        }

        return add_query_arg($query_args, $shop_url);
    }
}

if (!function_exists('my_theme_get_group_companion_cards')) {
    function my_theme_get_group_companion_cards($group_key = '', $product = null)
    {
        $group_key = sanitize_key((string) $group_key);
        if ($group_key === '') {
            return [];
        }

        $brand_slug = '';
        $brand_label = '';
        if ($product instanceof WC_Product) {
            $catalog_profile = function_exists('my_theme_get_product_catalog_profile')
                ? my_theme_get_product_catalog_profile($product)
                : [];
            $brand_slug = isset($catalog_profile['brand_slug'])
                ? sanitize_title((string) $catalog_profile['brand_slug'])
                : '';
            $brand_label = isset($catalog_profile['brand_label'])
                ? trim((string) $catalog_profile['brand_label'])
                : '';
            if ($brand_label === 'Sản phẩm') {
                $brand_label = '';
            }
        }

        $catalog = function_exists('my_theme_get_visual_story_group_catalog') ? my_theme_get_visual_story_group_catalog() : [];
        $solution_meta = isset($catalog[$group_key]) && is_array($catalog[$group_key]) ? $catalog[$group_key] : [];
        $solution_url = isset($solution_meta['url']) ? (string) $solution_meta['url'] : home_url('/giai-phap');
        $solution_cta = isset($solution_meta['cta']) ? (string) $solution_meta['cta'] : 'Mở giải pháp';
        $brand_tail = $brand_label !== '' ? ' của ' . $brand_label : '';

        $maps = [
            'interior' => [
                [
                    'eyebrow' => 'Lớp nền đi cùng',
                    'title' => 'Sơn lót kháng kiềm' . $brand_tail,
                    'description' => 'Nên xem thêm lớp lót để chốt đủ bộ thi công cho tường mới, tường sửa vá hoặc bề mặt hút nước không đều.',
                    'url' => my_theme_get_shop_filter_url(['category_slug' => 'son-lot', 'brand_slug' => $brand_slug]),
                    'cta' => 'Xem sơn lót',
                ],
                [
                    'eyebrow' => 'Chuẩn bị bề mặt',
                    'title' => 'Bột trét nội thất và hoàn thiện nền',
                    'description' => 'Nếu tường chưa phẳng hoặc còn vá sửa, nên xem thêm nhóm bột trét để tránh mua thiếu lớp chuẩn bị bề mặt.',
                    'url' => my_theme_get_shop_filter_url(['category_slug' => 'bot-tret', 'brand_slug' => $brand_slug]),
                    'cta' => 'Xem bột trét',
                ],
                [
                    'eyebrow' => 'Đi theo hệ',
                    'title' => 'Giải pháp sơn nội thất',
                    'description' => 'Mở landing page để xem ảnh minh họa, FAQ ngắn và cách chốt vật tư theo phòng, mức lau chùi và ngân sách.',
                    'url' => $solution_url,
                    'cta' => $solution_cta,
                ],
            ],
            'exterior' => [
                [
                    'eyebrow' => 'Lớp nền đi cùng',
                    'title' => 'Sơn lót kháng kiềm ngoại thất',
                    'description' => 'Ngoại thất nên chốt đủ lớp lót trước khi chọn phủ để giữ màu và ổn định nền tường dưới nắng mưa mạnh.',
                    'url' => my_theme_get_shop_filter_url(['category_slug' => 'son-lot', 'brand_slug' => $brand_slug]),
                    'cta' => 'Xem sơn lót',
                ],
                [
                    'eyebrow' => 'Chuẩn bị bề mặt',
                    'title' => 'Bột trét cho tường ngoài trời',
                    'description' => 'Nếu tường còn lỗ rỗ, nứt nhỏ hoặc phải sửa vá, nên kiểm tra thêm nhóm bột trét tương ứng trước khi chốt sơn phủ.',
                    'url' => my_theme_get_shop_filter_url(['category_slug' => 'bot-tret', 'brand_slug' => $brand_slug]),
                    'cta' => 'Xem bột trét',
                ],
                [
                    'eyebrow' => 'Đi theo hệ',
                    'title' => 'Giải pháp sơn ngoại thất',
                    'description' => 'Xem nhanh cách đi theo hiện trạng tường, nhu cầu bền màu và ảnh minh họa mặt tiền để chốt hệ gọn hơn.',
                    'url' => $solution_url,
                    'cta' => $solution_cta,
                ],
            ],
            'waterproofing' => [
                [
                    'eyebrow' => 'Cùng hạng mục',
                    'title' => 'Các mã chống thấm cùng nhóm',
                    'description' => 'So thêm những mã chống thấm cùng nhóm công trình để chọn đúng hệ theo nứt, đọng nước và bề mặt thực tế.',
                    'url' => my_theme_get_shop_filter_url(['category_slug' => 'chong-tham', 'brand_slug' => $brand_slug]),
                    'cta' => 'Xem chống thấm',
                ],
                [
                    'eyebrow' => 'Phụ trợ thi công',
                    'title' => 'Keo và phụ gia xử lý khe, cổ ống',
                    'description' => 'Một số hạng mục chống thấm cần thêm keo hoặc phụ gia xử lý điểm yếu trước khi phủ lớp chính.',
                    'url' => my_theme_get_shop_filter_url(['category_slug' => 'keo-va-phu-gia', 'brand_slug' => $brand_slug]),
                    'cta' => 'Xem keo & phụ gia',
                ],
                [
                    'eyebrow' => 'Đi theo hệ',
                    'title' => 'Giải pháp chống thấm',
                    'description' => 'Mở landing page để đi theo sân thượng, tường hay khu ẩm và xem checklist cần gửi trước khi hỏi báo giá.',
                    'url' => $solution_url,
                    'cta' => $solution_cta,
                ],
            ],
            'epoxy' => [
                [
                    'eyebrow' => 'Cùng hạng mục',
                    'title' => 'Các mã epoxy cùng nhóm',
                    'description' => 'So nhanh thêm những mã epoxy theo cùng bề mặt nền, tải trọng và cách thi công để tránh chốt lệch hệ.',
                    'url' => my_theme_get_shop_filter_url(['category_slug' => 'son-epoxy', 'brand_slug' => $brand_slug]),
                    'cta' => 'Xem sơn epoxy',
                ],
                [
                    'eyebrow' => 'Vật tư phụ trợ',
                    'title' => 'Nhóm sơn công nghiệp và vật tư liên quan',
                    'description' => 'Khi cần xử lý nền hoặc chọn hệ rộng hơn, nên xem thêm các mã công nghiệp liên quan để lên bộ vật tư đủ hơn.',
                    'url' => my_theme_get_shop_filter_url(['category_slug' => 'son-cong-nghiep', 'brand_slug' => $brand_slug]),
                    'cta' => 'Xem vật tư liên quan',
                ],
                [
                    'eyebrow' => 'Đi theo hệ',
                    'title' => 'Giải pháp sơn epoxy',
                    'description' => 'Landing page epoxy giúp đi thẳng theo hiện trạng nền, mức tải và form gửi ảnh nền sàn trước khi chốt đơn.',
                    'url' => $solution_url,
                    'cta' => $solution_cta,
                ],
            ],
            'metal' => [
                [
                    'eyebrow' => 'Cùng hạng mục',
                    'title' => 'Primer và sơn kim loại liên quan',
                    'description' => 'So thêm các mã chống rỉ, primer và phủ kim loại cùng nhóm để lên đúng hệ cho cửa sắt, lan can hoặc khung thép.',
                    'url' => my_theme_get_shop_filter_url(['category_slug' => 'son-kim-loai', 'brand_slug' => $brand_slug]),
                    'cta' => 'Xem sơn kim loại',
                ],
                [
                    'eyebrow' => 'Lớp phủ đi cùng',
                    'title' => 'Sơn dầu và lớp phủ hoàn thiện',
                    'description' => 'Một số hạng mục kim loại sẽ cần lớp phủ màu hoặc hệ dầu đi cùng sau primer chống rỉ.',
                    'url' => my_theme_get_shop_filter_url(['category_slug' => 'son-dau', 'brand_slug' => $brand_slug]),
                    'cta' => 'Xem sơn dầu',
                ],
                [
                    'eyebrow' => 'Đi theo hệ',
                    'title' => 'Giải pháp sơn kim loại',
                    'description' => 'Mở landing page kim loại để đi theo mức rỉ, điều kiện ngoài trời và quy trình chốt hệ sơn gọn hơn.',
                    'url' => $solution_url,
                    'cta' => $solution_cta,
                ],
            ],
            'grout' => [
                [
                    'eyebrow' => 'Cùng hạng mục',
                    'title' => 'Keo dán và chà ron cùng nhóm',
                    'description' => 'Xem thêm nhóm keo dán gạch, chà ron và vật tư ốp lát liên quan để không bị thiếu lớp khi lên đơn.',
                    'url' => my_theme_get_shop_filter_url(['category_slug' => 'keo-va-phu-gia', 'brand_slug' => $brand_slug]),
                    'cta' => 'Xem keo & ron',
                ],
                [
                    'eyebrow' => 'Khu ẩm và mép tiếp giáp',
                    'title' => 'Chống thấm cho khu bếp và nhà tắm',
                    'description' => 'Nếu khu vực thi công đi kèm nhà tắm, ban công hoặc khu ẩm, nên xem thêm chống thấm để chốt trọn hệ.',
                    'url' => my_theme_get_shop_filter_url(['category_slug' => 'chong-tham', 'brand_slug' => $brand_slug]),
                    'cta' => 'Xem chống thấm',
                ],
                [
                    'eyebrow' => 'Đi theo hệ',
                    'title' => 'Giải pháp keo và ron gạch',
                    'description' => 'Landing page keo ron giúp xem nhanh cách chọn theo khe ron, khu vực ẩm và các tình huống thi công thường gặp.',
                    'url' => $solution_url,
                    'cta' => $solution_cta,
                ],
            ],
        ];

        return isset($maps[$group_key]) ? $maps[$group_key] : [];
    }
}

if (!function_exists('my_theme_render_product_companion_paths')) {
    function my_theme_render_product_companion_paths($group_key = '', $product = null, array $args = [])
    {
        $cards = my_theme_get_group_companion_cards($group_key, $product);
        if (empty($cards)) {
            return;
        }

        $title = isset($args['title']) ? trim((string) $args['title']) : 'Bộ vật tư thường đi cùng mã này';
        $subtitle = isset($args['subtitle']) ? trim((string) $args['subtitle']) : 'Khách hay mua thiếu lớp nền, phụ trợ hoặc nhóm vật tư liên quan. Có thể đi tiếp nhanh từ đây để chốt đủ hệ.';
        $section_class = isset($args['class']) ? trim((string) $args['class']) : '';
        ?>
        <section class="page-section product-companion-paths<?php echo $section_class !== '' ? ' ' . esc_attr($section_class) : ''; ?>" aria-label="<?php echo esc_attr($title); ?>">
          <div class="section-heading">
            <div>
              <h2 class="section-title"><?php echo esc_html($title); ?></h2>
              <?php if ($subtitle !== '') : ?>
                <p class="section-sub"><?php echo esc_html($subtitle); ?></p>
              <?php endif; ?>
            </div>
          </div>
          <div class="info-grid product-companion-paths__grid">
            <?php foreach ($cards as $card) : ?>
              <?php
              $card = is_array($card) ? $card : [];
              $card_title = trim((string) ($card['title'] ?? ''));
              $card_description = trim((string) ($card['description'] ?? ''));
              $card_url = (string) ($card['url'] ?? '');
              $card_cta = trim((string) ($card['cta'] ?? 'Xem thêm'));
              $card_eyebrow = trim((string) ($card['eyebrow'] ?? ''));
              if ($card_title === '' || $card_url === '') {
                  continue;
              }
              ?>
              <article class="info-card product-companion-card">
                <?php if ($card_eyebrow !== '') : ?>
                  <p class="product-companion-card__eyebrow"><?php echo esc_html($card_eyebrow); ?></p>
                <?php endif; ?>
                <h3><?php echo esc_html($card_title); ?></h3>
                <?php if ($card_description !== '') : ?>
                  <p><?php echo esc_html($card_description); ?></p>
                <?php endif; ?>
                <div class="product-companion-card__actions">
                  <a class="btn btn-outline btn-sm" href="<?php echo esc_url($card_url); ?>"><?php echo esc_html($card_cta); ?></a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </section>
        <?php
    }
}

if (!function_exists('my_theme_get_cart_companion_cards')) {
    function my_theme_get_cart_companion_cards($limit = 4)
    {
        if (!function_exists('WC') || !WC()->cart) {
            return [];
        }

        $limit = max(1, (int) $limit);
        $groups = [];
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = isset($cart_item['data']) ? $cart_item['data'] : null;
            if (!$product instanceof WC_Product) {
                continue;
            }

            $group_key = function_exists('my_theme_get_visual_story_group_key_for_object')
                ? sanitize_key((string) my_theme_get_visual_story_group_key_for_object($product))
                : '';
            if ($group_key === '' || isset($groups[$group_key])) {
                continue;
            }

            $groups[$group_key] = $product;
        }

        if (empty($groups)) {
            return [];
        }

        $cards = [];
        $seen_urls = [];
        foreach ($groups as $group_key => $product) {
            $group_cards = my_theme_get_group_companion_cards($group_key, $product);
            if (empty($group_cards)) {
                continue;
            }

            foreach ($group_cards as $card) {
                $card = is_array($card) ? $card : [];
                $card_url = isset($card['url']) ? (string) $card['url'] : '';
                $card_title = isset($card['title']) ? trim((string) $card['title']) : '';
                if ($card_url === '' || $card_title === '' || isset($seen_urls[$card_url])) {
                    continue;
                }

                $seen_urls[$card_url] = true;
                $cards[] = $card;
                if (count($cards) >= $limit) {
                    break 2;
                }
            }
        }

        return $cards;
    }
}

if (!function_exists('my_theme_render_cart_companion_paths')) {
    function my_theme_render_cart_companion_paths(array $args = [])
    {
        $cards = my_theme_get_cart_companion_cards(4);
        if (empty($cards)) {
            return;
        }

        $title = isset($args['title']) ? trim((string) $args['title']) : 'Nhóm vật tư nên rà lại trước khi chốt đơn';
        $subtitle = isset($args['subtitle']) ? trim((string) $args['subtitle']) : 'Giỏ hàng thường bị thiếu lớp nền hoặc nhóm vật tư đi cùng. Có thể mở nhanh các nhóm dưới đây để chốt đủ bộ trước khi thanh toán.';
        $section_class = isset($args['class']) ? trim((string) $args['class']) : '';
        ?>
        <section class="page-section product-companion-paths product-companion-paths--cart<?php echo $section_class !== '' ? ' ' . esc_attr($section_class) : ''; ?>" aria-label="<?php echo esc_attr($title); ?>">
          <div class="section-heading">
            <div>
              <h2 class="section-title"><?php echo esc_html($title); ?></h2>
              <?php if ($subtitle !== '') : ?>
                <p class="section-sub"><?php echo esc_html($subtitle); ?></p>
              <?php endif; ?>
            </div>
          </div>
          <div class="info-grid product-companion-paths__grid">
            <?php foreach ($cards as $card) : ?>
              <?php
              $card = is_array($card) ? $card : [];
              $card_title = trim((string) ($card['title'] ?? ''));
              $card_description = trim((string) ($card['description'] ?? ''));
              $card_url = (string) ($card['url'] ?? '');
              $card_cta = trim((string) ($card['cta'] ?? 'Xem thêm'));
              $card_eyebrow = trim((string) ($card['eyebrow'] ?? ''));
              if ($card_title === '' || $card_url === '') {
                  continue;
              }
              ?>
              <article class="info-card product-companion-card">
                <?php if ($card_eyebrow !== '') : ?>
                  <p class="product-companion-card__eyebrow"><?php echo esc_html($card_eyebrow); ?></p>
                <?php endif; ?>
                <h3><?php echo esc_html($card_title); ?></h3>
                <?php if ($card_description !== '') : ?>
                  <p><?php echo esc_html($card_description); ?></p>
                <?php endif; ?>
                <div class="product-companion-card__actions">
                  <a class="btn btn-outline btn-sm" href="<?php echo esc_url($card_url); ?>"><?php echo esc_html($card_cta); ?></a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </section>
        <?php
    }
}

if (!function_exists('my_theme_get_single_product_compare_cards')) {
    function my_theme_get_single_product_compare_cards($product = null, $limit = 3)
    {
        $product = ($product instanceof WC_Product) ? $product : wc_get_product(get_the_ID());
        $limit = max(1, (int) $limit);
        if (!$product instanceof WC_Product) {
            return [];
        }

        $current_id = (int) $product->get_id();
        $visible_ids = function_exists('my_theme_get_catalog_visible_product_ids')
            ? my_theme_get_catalog_visible_product_ids(false)
            : [];
        $visible_ids = my_theme_normalize_product_id_list($visible_ids);
        if (empty($visible_ids)) {
            return [];
        }

        $current_brand = function_exists('my_theme_get_product_brand_slug')
            ? sanitize_title((string) my_theme_get_product_brand_slug($product))
            : '';
        $current_line = function_exists('my_theme_get_product_line_slug')
            ? sanitize_title((string) my_theme_get_product_line_slug($product))
            : '';
        $current_group = function_exists('my_theme_get_visual_story_group_key_for_object')
            ? sanitize_key((string) my_theme_get_visual_story_group_key_for_object($product))
            : '';
        $current_cat_ids = wc_get_product_term_ids($current_id, 'product_cat');
        $current_cat_ids = array_values(array_filter(array_map('intval', (array) $current_cat_ids), static function ($term_id) {
            return $term_id > 0;
        }));

        $cache_version = (string) get_option('my_theme_filter_cache_version', '1');
        $compare_cache_key = 'my_theme_single_compare_cards_v3_' . $cache_version . '_' . md5(
            $current_id . '|' . $current_brand . '|' . $current_line . '|' . $current_group . '|' . implode(',', $current_cat_ids) . '|' . $limit
        );
        $compare_card_ids = get_transient($compare_cache_key);

        if (!is_array($compare_card_ids)) {
            $candidate_ids = [];

            if ($current_line !== '' && function_exists('my_theme_filter_product_ids_by_line_slug')) {
                $line_ids = my_theme_filter_product_ids_by_line_slug($visible_ids, $current_line, $current_brand);
                foreach ((array) $line_ids as $candidate_id) {
                    $candidate_id = (int) $candidate_id;
                    if ($candidate_id > 0 && $candidate_id !== $current_id) {
                        $candidate_ids[$candidate_id] = $candidate_id;
                    }
                }
            }

            if ($current_brand !== '' && function_exists('my_theme_filter_product_ids_by_brand_slug') && count($candidate_ids) < 72) {
                $brand_ids = my_theme_filter_product_ids_by_brand_slug($visible_ids, $current_brand);
                foreach ((array) $brand_ids as $candidate_id) {
                    $candidate_id = (int) $candidate_id;
                    if ($candidate_id > 0 && $candidate_id !== $current_id) {
                        $candidate_ids[$candidate_id] = $candidate_id;
                    }
                    if (count($candidate_ids) >= 96) {
                        break;
                    }
                }
            }

            if (!empty($current_cat_ids) && count($candidate_ids) < 48) {
                $category_matches = get_posts([
                    'post_type' => 'product',
                    'post_status' => 'publish',
                    'posts_per_page' => 72,
                    'fields' => 'ids',
                    'post__in' => $visible_ids,
                    'post__not_in' => [$current_id],
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'no_found_rows' => true,
                    'ignore_sticky_posts' => true,
                    'update_post_meta_cache' => false,
                    'update_post_term_cache' => false,
                    'tax_query' => [
                        [
                            'taxonomy' => 'product_cat',
                            'field' => 'term_id',
                            'terms' => $current_cat_ids,
                            'operator' => 'IN',
                        ],
                    ],
                ]);
                foreach ((array) $category_matches as $candidate_id) {
                    $candidate_id = (int) $candidate_id;
                    if ($candidate_id > 0 && $candidate_id !== $current_id) {
                        $candidate_ids[$candidate_id] = $candidate_id;
                    }
                }
            }

            if (count($candidate_ids) < 36) {
                foreach (array_slice($visible_ids, 0, 96) as $candidate_id) {
                    $candidate_id = (int) $candidate_id;
                    if ($candidate_id > 0 && $candidate_id !== $current_id) {
                        $candidate_ids[$candidate_id] = $candidate_id;
                    }
                    if (count($candidate_ids) >= 96) {
                        break;
                    }
                }
            }

            $candidate_ids = array_values($candidate_ids);
            $product_map = function_exists('my_theme_get_product_object_map')
                ? my_theme_get_product_object_map($candidate_ids)
                : [];
            if (empty($product_map)) {
                set_transient($compare_cache_key, [], 30 * MINUTE_IN_SECONDS);
                return [];
            }

            $scored = [];
            $current_cat_lookup = array_fill_keys($current_cat_ids, true);
            foreach ($product_map as $candidate_id => $candidate) {
                $candidate_id = (int) $candidate_id;
                if ($candidate_id <= 0 || $candidate_id === $current_id || !$candidate instanceof WC_Product) {
                    continue;
                }

                $score = 0;
                $candidate_brand = function_exists('my_theme_get_product_brand_slug')
                    ? sanitize_title((string) my_theme_get_product_brand_slug($candidate))
                    : '';
                $candidate_line = function_exists('my_theme_get_product_line_slug')
                    ? sanitize_title((string) my_theme_get_product_line_slug($candidate))
                    : '';
                $candidate_group = function_exists('my_theme_get_visual_story_group_key_for_object')
                    ? sanitize_key((string) my_theme_get_visual_story_group_key_for_object($candidate))
                    : '';

                if ($current_brand !== '' && $candidate_brand === $current_brand) {
                    $score += 34;
                }
                if ($current_line !== '' && $candidate_line === $current_line) {
                    $score += 42;
                }
                if ($current_group !== '' && $candidate_group === $current_group) {
                    $score += 26;
                }

                if (!empty($current_cat_lookup)) {
                    $candidate_cat_ids = wc_get_product_term_ids($candidate_id, 'product_cat');
                    foreach ((array) $candidate_cat_ids as $candidate_cat_id) {
                        $candidate_cat_id = (int) $candidate_cat_id;
                        if ($candidate_cat_id > 0 && isset($current_cat_lookup[$candidate_cat_id])) {
                            $score += 18;
                            break;
                        }
                    }
                }

                $candidate_price = function_exists('my_theme_get_default_loop_price')
                    ? (float) my_theme_get_default_loop_price($candidate)
                    : (float) $candidate->get_price();
                if ($candidate_price > 0) {
                    $score += 8;
                }

                if ($score <= 0) {
                    continue;
                }

                $scored[] = [
                    'score' => $score,
                    'product_id' => $candidate_id,
                    'name' => (string) $candidate->get_name(),
                ];
            }

            if (empty($scored)) {
                set_transient($compare_cache_key, [], 30 * MINUTE_IN_SECONDS);
                return [];
            }

            usort($scored, static function (array $a, array $b): int {
                $score_cmp = ((int) ($b['score'] ?? 0)) <=> ((int) ($a['score'] ?? 0));
                if ($score_cmp !== 0) {
                    return $score_cmp;
                }

                return strnatcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
            });

            $compare_card_ids = array_values(array_filter(array_map(static function ($entry) {
                return isset($entry['product_id']) ? (int) $entry['product_id'] : 0;
            }, array_slice($scored, 0, $limit))));
            set_transient($compare_cache_key, $compare_card_ids, 30 * MINUTE_IN_SECONDS);
        }

        $product_map = function_exists('my_theme_get_product_object_map')
            ? my_theme_get_product_object_map($compare_card_ids)
            : [];
        if (empty($product_map)) {
            return [];
        }

        $cards = [];
        foreach ($compare_card_ids as $candidate_id) {
            $candidate_id = (int) $candidate_id;
            if ($candidate_id <= 0 || !isset($product_map[$candidate_id])) {
                continue;
            }

            $candidate = $product_map[$candidate_id];
            if (!$candidate instanceof WC_Product) {
                continue;
            }

            $catalog_profile = function_exists('my_theme_get_product_catalog_profile')
                ? my_theme_get_product_catalog_profile($candidate)
                : [];
            $brand_label = isset($catalog_profile['brand_label'])
                ? trim((string) $catalog_profile['brand_label'])
                : '';
            if ($brand_label === 'Sản phẩm') {
                $brand_label = '';
            }
            $line_label = isset($catalog_profile['line_label'])
                ? trim((string) $catalog_profile['line_label'])
                : '';
            $package_text = function_exists('my_theme_get_package_summary_text')
                ? trim((string) my_theme_get_package_summary_text($candidate))
                : '';
            $meta_bits = array_values(array_filter([$brand_label, $line_label]));
            if ($package_text !== '') {
                $meta_bits[] = $package_text;
            }

            $cards[] = [
                'title' => isset($catalog_profile['display_name']) && (string) $catalog_profile['display_name'] !== ''
                    ? (string) $catalog_profile['display_name']
                    : (string) $candidate->get_name(),
                'url' => get_permalink($candidate->get_id()),
                'image' => function_exists('my_theme_get_product_thumbnail_markup')
                    ? my_theme_get_product_thumbnail_markup($candidate, 'woocommerce_thumbnail', ['loading' => 'lazy'])
                    : $candidate->get_image('woocommerce_thumbnail', ['loading' => 'lazy']),
                'meta' => implode(' • ', $meta_bits),
                'price_html' => function_exists('my_theme_get_loop_price_html')
                    ? (string) my_theme_get_loop_price_html($candidate, 'product-compare-card__price product-card__price')
                    : '<div class="product-compare-card__price product-card__price"><span class="product-card__price-contact">Liên hệ báo giá</span></div>',
                'excerpt' => function_exists('my_theme_get_product_card_excerpt')
                    ? trim((string) my_theme_get_product_card_excerpt($candidate, 18))
                    : '',
            ];
        }

        return $cards;
    }
}

if (!function_exists('my_theme_get_single_product_fit_notes')) {
    function my_theme_get_single_product_fit_notes($group_key = '')
    {
        $group_key = sanitize_key((string) $group_key);
        $map = [
            'interior' => [
                'fit' => [
                    'Nhà ở cần bề mặt sạch, dễ lau chùi và hoàn thiện gọn theo phòng.',
                    'Khách đã có nhu cầu rõ về độ bóng, độ mờ hoặc ngân sách theo từng khu vực.',
                    'Công trình cần chốt nhanh bộ lót, bột trét và lớp phủ trong nhà.',
                ],
                'consider' => [
                    'Nếu là tường ngoài trời hoặc khu ẩm, nên chuyển sang nhóm ngoại thất hoặc chống thấm.',
                    'Nếu nền tường còn nứt, ẩm hoặc hút nước mạnh, cần chốt lớp nền trước khi báo giá.',
                ],
            ],
            'exterior' => [
                'fit' => [
                    'Mặt tiền, mảng tường ngoài trời và khu vực chịu nắng mưa trực tiếp.',
                    'Khách ưu tiên độ bền màu, ổn định màng sơn và bảo trì ít hơn.',
                    'Công trình đang so giữa lớp lót kháng kiềm và lớp phủ ngoại thất cùng hệ.',
                ],
                'consider' => [
                    'Nếu tường đã thấm hoặc có nứt hairline, nên kiểm tra thêm chống thấm thay vì chỉ chốt sơn phủ.',
                    'Nếu là khu vực nội thất, nên so lại nhóm sơn lau chùi và độ bóng trong nhà.',
                ],
            ],
            'waterproofing' => [
                'fit' => [
                    'Sân thượng, khu ẩm, tường ngoài trời hoặc vị trí đã xuất hiện dấu hiệu thấm.',
                    'Khách cần đi theo hiện trạng bề mặt hơn là chọn một mã sơn đơn lẻ.',
                    'Công trình muốn chốt nhanh hệ 1 thành phần, 2 thành phần hoặc nhóm linh hoạt.',
                ],
                'consider' => [
                    'Nếu chỉ cần phủ màu hoàn thiện, nên xem thêm nhóm ngoại thất hoặc nội thất tương ứng.',
                    'Nếu chưa rõ vị trí thấm và mức nứt, nên gửi ảnh hiện trạng trước khi chốt quy cách.',
                ],
            ],
            'epoxy' => [
                'fit' => [
                    'Nền gara, kho nhỏ, xưởng mini hoặc khu kỹ thuật cần dễ vệ sinh.',
                    'Khách đã có thông tin sơ bộ về diện tích, tải trọng và tình trạng nền sàn.',
                    'Công trình cần chốt đủ primer, lớp phủ và tiến độ đưa sàn vào sử dụng.',
                ],
                'consider' => [
                    'Nếu nền đang ẩm, yếu hoặc nhiều vết nứt, phải chốt lại xử lý nền trước khi báo hệ epoxy.',
                    'Nếu là bề mặt tường hoặc mái, nên chuyển sang nhóm sơn/chống thấm tương ứng.',
                ],
            ],
            'metal' => [
                'fit' => [
                    'Cửa sắt, lan can, hàng rào hoặc khung kim loại cần primer chống rỉ và lớp phủ đi cùng.',
                    'Khách đã có ảnh hiện trạng để phân biệt rỉ nhẹ, rỉ nặng hoặc sơn cũ bong tróc.',
                    'Công trình cần chốt theo bộ primer + phủ thay vì mua lẻ một lớp màu.',
                ],
                'consider' => [
                    'Nếu bề mặt là tường, sàn hoặc mái bê tông, nên chọn lại nhóm sản phẩm phù hợp hơn.',
                    'Nếu chỉ cần trám khe, dán gạch hoặc xử lý ron, nên chuyển sang nhóm keo và ron.',
                ],
            ],
            'grout' => [
                'fit' => [
                    'Nhà tắm, bếp, khe ron, khu ốp lát và các hạng mục cần keo dán/chà ron đúng vai trò.',
                    'Khách đã biết loại gạch, khu vực thi công và độ rộng khe ron.',
                    'Công trình cần chốt nhanh bộ vật tư ốp lát thay vì mua riêng từng món rời.',
                ],
                'consider' => [
                    'Nếu khu vực đang thấm hoặc cần xử lý nền ẩm, nên xem thêm nhóm chống thấm trước.',
                    'Nếu nhu cầu là sơn hoàn thiện tường hoặc kim loại, nên đổi sang nhóm giải pháp tương ứng.',
                ],
            ],
        ];

        return isset($map[$group_key]) && is_array($map[$group_key]) ? $map[$group_key] : [];
    }
}

if (!function_exists('my_theme_render_single_product_buying_guide')) {
    function my_theme_render_single_product_buying_guide($product = null, $group_key = '')
    {
        $product = ($product instanceof WC_Product) ? $product : wc_get_product(get_the_ID());
        if (!$product instanceof WC_Product) {
            return;
        }

        $compare_cards = my_theme_get_single_product_compare_cards($product, 3);
        $fit_notes = my_theme_get_single_product_fit_notes($group_key);
        $fit_items = isset($fit_notes['fit']) && is_array($fit_notes['fit']) ? array_values(array_filter($fit_notes['fit'])) : [];
        $consider_items = isset($fit_notes['consider']) && is_array($fit_notes['consider']) ? array_values(array_filter($fit_notes['consider'])) : [];

        if (empty($compare_cards) && empty($fit_items) && empty($consider_items)) {
            return;
        }
        ?>
        <section class="page-section product-buying-guide" aria-label="So nhanh và đối chiếu nhu cầu">
          <div class="section-heading">
            <div>
              <h2 class="section-title">So nhanh trước khi chốt mã này</h2>
              <p class="section-sub">Phần này giúp khách đối chiếu nhanh mã đang xem với nhu cầu thực tế, tránh mở từng sản phẩm rời rạc rồi vẫn chưa chốt được hệ.</p>
            </div>
          </div>

          <?php if (!empty($compare_cards)) : ?>
            <div class="product-buying-guide__block">
              <div class="section-heading section-heading--compact">
                <div>
                  <h3 class="section-title">Mã cùng nhóm để so nhanh</h3>
                  <p class="section-sub">Các mã dưới đây được lấy từ cùng dòng, cùng hãng hoặc cùng hạng mục đang xem.</p>
                </div>
              </div>
              <div class="product-compare-grid">
                <?php foreach ($compare_cards as $card) : ?>
                  <article class="product-compare-card">
                    <a class="product-compare-card__thumb" href="<?php echo esc_url((string) ($card['url'] ?? '#')); ?>">
                      <?php echo wp_kses_post((string) ($card['image'] ?? '')); ?>
                    </a>
                    <div class="product-compare-card__body">
                      <h3><a href="<?php echo esc_url((string) ($card['url'] ?? '#')); ?>"><?php echo esc_html((string) ($card['title'] ?? '')); ?></a></h3>
                      <?php if (!empty($card['meta'])) : ?>
                        <p class="product-compare-card__meta"><?php echo esc_html((string) $card['meta']); ?></p>
                      <?php endif; ?>
                      <?php if (!empty($card['excerpt'])) : ?>
                        <p class="product-compare-card__excerpt"><?php echo esc_html((string) $card['excerpt']); ?></p>
                      <?php endif; ?>
                    </div>
                    <div class="product-compare-card__foot">
                      <?php echo !empty($card['price_html']) ? wp_kses_post((string) $card['price_html']) : '<div class="product-compare-card__price product-card__price"><span class="product-card__price-contact">Liên hệ báo giá</span></div>'; ?>
                      <a class="btn btn-outline btn-sm" href="<?php echo esc_url((string) ($card['url'] ?? '#')); ?>">Xem mã này</a>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!empty($fit_items) || !empty($consider_items)) : ?>
            <div class="info-grid product-buying-guide__fit-grid">
              <?php if (!empty($fit_items)) : ?>
                <article class="info-card product-fit-card product-fit-card--fit">
                  <p class="product-fit-card__eyebrow">Phù hợp khi</p>
                  <ul class="list-check">
                    <?php foreach ($fit_items as $item) : ?>
                      <li><?php echo esc_html((string) $item); ?></li>
                    <?php endforeach; ?>
                  </ul>
                </article>
              <?php endif; ?>

              <?php if (!empty($consider_items)) : ?>
                <article class="info-card product-fit-card product-fit-card--consider">
                  <p class="product-fit-card__eyebrow">Cần cân nhắc thêm khi</p>
                  <ul class="list-check">
                    <?php foreach ($consider_items as $item) : ?>
                      <li><?php echo esc_html((string) $item); ?></li>
                    <?php endforeach; ?>
                  </ul>
                </article>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </section>
        <?php
    }
}

if (!function_exists('my_theme_get_current_full_url')) {
    function my_theme_get_current_full_url()
    {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '/';
        if ($request_uri === '') {
            $request_uri = '/';
        }

        return home_url($request_uri);
    }
}

if (!function_exists('my_theme_get_seo_description_from_text')) {
    function my_theme_get_seo_description_from_text($text = '', $fallback = '')
    {
        $text = (string) $text;
        if ($text !== '') {
            $text = strip_shortcodes($text);
            $text = preg_replace('/\[[^\]]+\]/', ' ', $text);
            $text = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags($text)));
        }
        if ($text === '') {
            $text = trim((string) $fallback);
        }
        if ($text === '') {
            return '';
        }

        return wp_trim_words($text, 28, '...');
    }
}

if (!function_exists('my_theme_should_noindex_current_view')) {
    function my_theme_should_noindex_current_view()
    {
        if (is_404() || is_search()) {
            return true;
        }

        if (function_exists('is_cart') && is_cart()) {
            return true;
        }

        if (function_exists('is_checkout') && is_checkout()) {
            return true;
        }

        if (function_exists('is_account_page') && is_account_page()) {
            return true;
        }

        if (function_exists('is_shop') && is_shop()) {
            foreach (['q', 'brand', 'line', 'category', 'sort'] as $filter_key) {
                if (!isset($_GET[$filter_key])) {
                    continue;
                }

                $filter_value = trim((string) wp_unslash($_GET[$filter_key]));
                if ($filter_value !== '' && $filter_value !== '0') {
                    return true;
                }
            }
        }

        return false;
    }
}

if (!function_exists('my_theme_get_seo_meta_payload')) {
    function my_theme_get_seo_meta_payload()
    {
        $business = my_theme_get_business_profile();
        $image_url = (string) $business['logo_url'];
        $description = 'Đại lý Sơn Phát Tấn cung cấp sơn chính hãng, báo giá nhanh, giao vật tư và hỗ trợ kỹ thuật cho thợ và công trình tại TP.HCM.';
        $type = 'website';

        if (is_front_page()) {
            $description = 'Đại lý Sơn Phát Tấn cung cấp sơn chính hãng, báo giá nhanh, giao vật tư và hỗ trợ kỹ thuật cho thợ và công trình tại TP.HCM, Bình Dương, Đồng Nai.';
        } elseif (function_exists('my_theme_is_virtual_blog_request') && my_theme_is_virtual_blog_request()) {
            $description = 'Góc tư vấn thi công tổng hợp hướng dẫn chọn hệ sơn, định mức, quy trình thi công và kinh nghiệm đặt vật tư theo từng bề mặt.';
        } elseif (function_exists('is_shop') && is_shop()) {
            $description = 'Kho sản phẩm sơn chính hãng với bộ lọc nhanh theo thương hiệu, danh mục và dòng sản phẩm để chốt vật tư gọn hơn.';
        } elseif (function_exists('is_cart') && is_cart()) {
            $description = 'Kiểm tra giỏ hàng, số lượng, quy cách và tổng tiền trước khi chuyển sang bước thanh toán.';
        } elseif (function_exists('is_checkout') && is_checkout()) {
            $description = 'Hoàn tất thông tin thanh toán, giao hàng và xác nhận đơn vật tư sơn tại Đại lý Sơn Phát Tấn.';
        } elseif (function_exists('is_account_page') && is_account_page()) {
            $description = 'Đăng nhập, theo dõi đơn hàng, địa chỉ giao hàng và thông tin tài khoản tại Đại lý Sơn Phát Tấn.';
        } elseif (function_exists('is_product') && is_product()) {
            $type = 'product';
            $product = wc_get_product(get_queried_object_id());
            if ($product instanceof WC_Product) {
                $short_description = $product->get_short_description();
                $description = my_theme_get_seo_description_from_text(
                    $short_description,
                    'Báo giá ' . $product->get_name() . ', hỗ trợ chọn quy cách, giao vật tư và tư vấn kỹ thuật tại Đại lý Sơn Phát Tấn.'
                );
                if (function_exists('my_theme_get_preferred_product_image_id')) {
                    $image_id = (int) my_theme_get_preferred_product_image_id($product);
                    if ($image_id > 0) {
                        $candidate = wp_get_attachment_image_url($image_id, 'full');
                        if (is_string($candidate) && $candidate !== '') {
                            $image_url = $candidate;
                        }
                    }
                }
            }
        } elseif (is_singular('post')) {
            $type = 'article';
            $post = get_queried_object();
            if ($post instanceof WP_Post) {
                $description = my_theme_get_seo_description_from_text(
                    has_excerpt($post) ? get_the_excerpt($post) : $post->post_content,
                    'Bài viết tư vấn thi công và chọn hệ sơn tại Đại lý Sơn Phát Tấn.'
                );
                if (has_post_thumbnail($post)) {
                    $candidate = get_the_post_thumbnail_url($post, 'full');
                    if (is_string($candidate) && $candidate !== '') {
                        $image_url = $candidate;
                    }
                }
            }
        } elseif (is_page()) {
            $page = get_queried_object();
            $page_title = ($page instanceof WP_Post) ? trim((string) get_the_title($page)) : '';
            $page_map = [
                'Liên hệ' => 'Liên hệ Đại lý Sơn Phát Tấn để nhận báo giá nhanh, tư vấn hệ sơn phù hợp và lên lịch giao vật tư theo công trình.',
                'Câu hỏi thường gặp' => 'Giải đáp nhanh các câu hỏi thường gặp về chọn sơn, nhận hàng, hóa đơn, đổi trả và giá đại lý.',
                'Hướng dẫn mua hàng' => 'Quy trình đặt sơn nhanh theo 4 bước: gửi nhu cầu, nhận báo giá, xác nhận đơn và giao hàng theo tiến độ.',
                'Giải pháp tổng hợp' => 'Trang tổng hợp 6 nhóm giải pháp theo từng bề mặt và hạng mục để đi nhanh vào đúng nhu cầu, sản phẩm và form báo giá.',
                'Giải pháp sơn nội thất' => 'Landing page tư vấn chọn sơn nội thất theo phòng, nhu cầu lau chùi, ngân sách và hệ lót phù hợp cho nhà ở.',
                'Giải pháp sơn ngoại thất' => 'Landing page tư vấn chọn sơn ngoại thất theo hiện trạng tường, mức nắng mưa, độ bền màu và hệ lót phù hợp cho mặt tiền.',
                'Giải pháp chống thấm' => 'Landing page tổng hợp giải pháp chống thấm theo sân thượng, tường ngoài trời, khu ẩm và hiện trạng bề mặt.',
                'Giải pháp sơn epoxy' => 'Landing page tổng hợp giải pháp sơn epoxy theo hiện trạng nền sàn, mức tải và nhu cầu sử dụng cho gara, kho nhỏ và xưởng.',
                'Giải pháp sơn kim loại' => 'Landing page tổng hợp giải pháp sơn kim loại, chống rỉ cho cửa sắt, lan can, cổng và khung thép ngoài trời.',
                'Giải pháp keo và ron gạch' => 'Landing page tư vấn keo dán gạch, chà ron và phụ gia theo nhà tắm, bếp, khu ẩm và loại gạch thi công.',
                'Giới thiệu Đại lý Sơn Phát Tấn' => 'Thông tin về Đại lý Sơn Phát Tấn, khu vực phục vụ, kinh nghiệm cung cấp sơn chính hãng và hỗ trợ kỹ thuật cho công trình.',
                'Vận chuyển & giao hàng' => 'Chính sách giao hàng, phạm vi phục vụ, thời gian giao và lưu ý khi nhận vật tư tại Đại lý Sơn Phát Tấn.',
                'Chính sách đổi trả' => 'Điều kiện đổi trả, quy trình xử lý lỗi và hướng dẫn gửi yêu cầu hỗ trợ đổi trả vật tư sơn.',
            ];
            if ($page_title !== '' && isset($page_map[$page_title])) {
                $description = $page_map[$page_title];
            } elseif ($page instanceof WP_Post) {
                $description = my_theme_get_seo_description_from_text($page->post_content, $description);
                if (has_post_thumbnail($page)) {
                    $candidate = get_the_post_thumbnail_url($page, 'full');
                    if (is_string($candidate) && $candidate !== '') {
                        $image_url = $candidate;
                    }
                }
            }
        } elseif (is_tax() || is_category() || is_tag()) {
            $term = get_queried_object();
            if ($term instanceof WP_Term) {
                $description = my_theme_get_seo_description_from_text(
                    term_description($term),
                    'Danh mục nội dung và sản phẩm được tổng hợp để theo dõi nhanh hơn tại Đại lý Sơn Phát Tấn.'
                );
            }
        } elseif (is_search()) {
            $query_text = trim((string) get_search_query());
            $description = ($query_text !== '')
                ? 'Kết quả tìm kiếm cho "' . $query_text . '" trên Đại lý Sơn Phát Tấn.'
                : 'Tìm kiếm sản phẩm và nội dung tư vấn trên Đại lý Sơn Phát Tấn.';
        }

        $title = wp_get_document_title();
        $url = my_theme_get_current_full_url();
        $robots = my_theme_should_noindex_current_view()
            ? 'noindex, nofollow'
            : 'index, follow, max-image-preview:large';
        $twitter_card = str_ends_with(strtolower((string) $image_url), '.svg') ? 'summary' : 'summary_large_image';

        return [
            'title' => trim((string) $title),
            'description' => trim((string) $description),
            'url' => trim((string) $url),
            'image' => trim((string) $image_url),
            'type' => $type,
            'robots' => $robots,
            'twitter_card' => $twitter_card,
        ];
    }
}

add_filter('wp_robots', function ($robots) {
    if (is_admin()) {
        return $robots;
    }

    if (my_theme_should_noindex_current_view()) {
        $robots['noindex'] = true;
        $robots['nofollow'] = true;
        return $robots;
    }

    if ((int) get_option('blog_public', 1) === 1) {
        unset($robots['noindex'], $robots['nofollow']);
        $robots['max-image-preview'] = 'large';
    }

    return $robots;
}, 20);

add_action('wp_head', function () {
    if (is_admin()) {
        return;
    }

    $payload = my_theme_get_seo_meta_payload();
    $business = my_theme_get_business_profile();
    if (!is_array($payload) || empty($payload['title'])) {
        return;
    }

    echo "\n" . '<link rel="canonical" href="' . esc_url($payload['url']) . '">' . "\n";
    echo '<meta name="description" content="' . esc_attr($payload['description']) . '">' . "\n";
    echo '<meta property="og:locale" content="vi_VN">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr($business['name']) . '">' . "\n";
    echo '<meta property="og:type" content="' . esc_attr($payload['type']) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($payload['title']) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($payload['description']) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($payload['url']) . '">' . "\n";
    echo '<meta property="og:image" content="' . esc_url($payload['image']) . '">' . "\n";
    echo '<meta property="og:image:alt" content="' . esc_attr($business['name']) . '">' . "\n";
    echo '<meta name="twitter:card" content="' . esc_attr($payload['twitter_card']) . '">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($payload['title']) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($payload['description']) . '">' . "\n";
    echo '<meta name="twitter:image" content="' . esc_url($payload['image']) . '">' . "\n";

    $organization_id = trailingslashit(home_url('/')) . '#organization';
    $website_id = trailingslashit(home_url('/')) . '#website';
    $schema_graph = [];

    $schema_graph[] = [
        '@type' => 'Store',
        '@id' => $organization_id,
        'name' => $business['name'],
        'url' => home_url('/'),
        'logo' => $business['logo_url'],
        'image' => $payload['image'],
        'email' => 'mailto:' . $business['email'],
        'telephone' => $business['phone_raw'],
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $business['address_street'],
            'addressLocality' => $business['address_locality'],
            'addressRegion' => $business['address_region'],
            'addressCountry' => $business['address_country'],
        ],
        'areaServed' => $business['service_areas'],
        'contactPoint' => [
            [
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'telephone' => $business['phone_raw'],
                'email' => $business['email'],
                'availableLanguage' => ['vi'],
                'areaServed' => 'VN',
            ],
        ],
        'openingHoursSpecification' => [
            [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => $business['hours_schema_days'],
                'opens' => $business['hours_open'],
                'closes' => $business['hours_close'],
            ],
        ],
    ];

    $search_target = add_query_arg('q', '{search_term_string}', function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop'));
    $schema_graph[] = [
        '@type' => 'WebSite',
        '@id' => $website_id,
        'url' => home_url('/'),
        'name' => $business['name'],
        'publisher' => [
            '@id' => $organization_id,
        ],
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => $search_target,
            'query-input' => 'required name=search_term_string',
        ],
    ];

    $page_node = [
        '@type' => 'WebPage',
        '@id' => $payload['url'] . '#webpage',
        'url' => $payload['url'],
        'name' => $payload['title'],
        'description' => $payload['description'],
        'isPartOf' => [
            '@id' => $website_id,
        ],
    ];
    $schema_graph[] = $page_node;

    if (is_page(['faq', 'cau-hoi-thuong-gap'])) {
        $faq_items = my_theme_get_faq_schema_items();
        if (!empty($faq_items)) {
            $main_entities = [];
            foreach ($faq_items as $faq_index => $faq_item) {
                $question = trim((string) ($faq_item['question'] ?? ''));
                $answer = trim((string) ($faq_item['answer'] ?? ''));
                if ($question === '' || $answer === '') {
                    continue;
                }

                $main_entities[] = [
                    '@type' => 'Question',
                    '@id' => $payload['url'] . '#faq-' . ($faq_index + 1),
                    'name' => $question,
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $answer,
                    ],
                ];
            }

            if (!empty($main_entities)) {
                $schema_graph[] = [
                    '@type' => 'FAQPage',
                    '@id' => $payload['url'] . '#faqpage',
                    'url' => $payload['url'],
                    'name' => $payload['title'],
                    'mainEntity' => $main_entities,
                    'isPartOf' => [
                        '@id' => $website_id,
                    ],
                ];
            }
        }
    }

    if (is_page('lien-he')) {
        $schema_graph[] = [
            '@type' => 'ContactPage',
            '@id' => $payload['url'] . '#contactpage',
            'url' => $payload['url'],
            'name' => $payload['title'],
            'description' => $payload['description'],
            'mainEntity' => [
                '@id' => $organization_id,
            ],
            'isPartOf' => [
                '@id' => $website_id,
            ],
        ];
    }

    if (is_singular('post')) {
        $post = get_queried_object();
        if ($post instanceof WP_Post) {
            $author_name = trim((string) get_the_author_meta('display_name', (int) $post->post_author));
            if ($author_name === '') {
                $author_name = $business['name'];
            }

            echo '<meta property="article:published_time" content="' . esc_attr(get_post_time(DATE_W3C, true, $post)) . '">' . "\n";
            echo '<meta property="article:modified_time" content="' . esc_attr(get_post_modified_time(DATE_W3C, true, $post)) . '">' . "\n";

            $article_schema = [
                '@type' => 'Article',
                '@id' => get_permalink($post) . '#article',
                'headline' => get_the_title($post),
                'description' => $payload['description'],
                'datePublished' => get_post_time(DATE_W3C, true, $post),
                'dateModified' => get_post_modified_time(DATE_W3C, true, $post),
                'mainEntityOfPage' => [
                    '@id' => $payload['url'] . '#webpage',
                ],
                'author' => [
                    '@type' => 'Person',
                    'name' => $author_name,
                ],
                'publisher' => [
                    '@id' => $organization_id,
                ],
            ];
            if (!empty($payload['image'])) {
                $article_schema['image'] = [$payload['image']];
            }
            $schema_graph[] = $article_schema;
        }
    }

    if (function_exists('is_product') && is_product()) {
        $product = wc_get_product(get_queried_object_id());
        if ($product instanceof WC_Product) {
            $price = function_exists('my_theme_get_default_loop_price')
                ? (float) my_theme_get_default_loop_price($product)
                : (float) $product->get_price();
            $currency = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'VND';
            $permalink = get_permalink($product->get_id());
            $catalog_profile = function_exists('my_theme_get_product_catalog_profile')
                ? my_theme_get_product_catalog_profile($product)
                : [];
            $product_name = isset($catalog_profile['display_name']) && (string) $catalog_profile['display_name'] !== ''
                ? (string) $catalog_profile['display_name']
                : $product->get_name();
            $brand_label = isset($catalog_profile['brand_label'])
                ? trim((string) $catalog_profile['brand_label'])
                : '';
            if ($brand_label === 'Sản phẩm') {
                $brand_label = '';
            }
            $category_label = isset($catalog_profile['category_label'])
                ? trim((string) $catalog_profile['category_label'])
                : '';

            if ($price > 0) {
                echo '<meta property="product:price:amount" content="' . esc_attr(wc_format_decimal($price, wc_get_price_decimals())) . '">' . "\n";
                echo '<meta property="product:price:currency" content="' . esc_attr($currency) . '">' . "\n";
            }
            echo '<meta property="product:availability" content="' . esc_attr($product->is_in_stock() ? 'in stock' : 'out of stock') . '">' . "\n";

            $product_schema = [
                '@type' => 'Product',
                '@id' => $permalink . '#product',
                'name' => $product_name,
                'url' => $permalink,
                'description' => $payload['description'],
                'sku' => $product->get_sku() ? $product->get_sku() : (string) $product->get_id(),
                'mainEntityOfPage' => [
                    '@id' => $payload['url'] . '#webpage',
                ],
            ];
            if (!empty($payload['image'])) {
                $product_schema['image'] = [$payload['image']];
            }
            if ($brand_label !== '' && $brand_label !== 'Sản phẩm') {
                $product_schema['brand'] = [
                    '@type' => 'Brand',
                    'name' => $brand_label,
                ];
            }
            if ($category_label !== '') {
                $product_schema['category'] = $category_label;
            }
            if ($price !== '') {
                $product_schema['offers'] = [
                    '@type' => 'Offer',
                    'url' => $permalink,
                    'priceCurrency' => $currency,
                    'price' => wc_format_decimal($price, wc_get_price_decimals()),
                    'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    'seller' => [
                        '@id' => $organization_id,
                    ],
                ];
            }

            $schema_graph[] = $product_schema;
        }
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@graph' => $schema_graph,
    ];

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}, 5);

if (!function_exists('my_theme_get_current_request_path')) {
    function my_theme_get_current_request_path()
    {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        if ($request_uri === '') {
            return '';
        }

        $request_path = (string) wp_parse_url($request_uri, PHP_URL_PATH);
        $request_path = trim($request_path, '/');
        if ($request_path === '') {
            return '';
        }

        $home_path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
        $home_path = trim($home_path, '/');
        if ($home_path !== '' && str_starts_with($request_path, $home_path . '/')) {
            $request_path = substr($request_path, strlen($home_path) + 1);
        } elseif ($home_path !== '' && $request_path === $home_path) {
            return '';
        }

        return trim((string) $request_path, '/');
    }
}

if (!function_exists('my_theme_get_virtual_blog_page')) {
    function my_theme_get_virtual_blog_page()
    {
        $request_path = my_theme_get_current_request_path();
        if ($request_path === '') {
            return 0;
        }

        if (!preg_match('#^blog(?:/page/([0-9]+))?$#', $request_path, $matches)) {
            return 0;
        }

        $page = isset($matches[1]) ? (int) $matches[1] : 1;
        return max(1, $page);
    }
}

if (!function_exists('my_theme_is_virtual_blog_request')) {
    function my_theme_is_virtual_blog_request()
    {
        if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return false;
        }
        return my_theme_get_virtual_blog_page() > 0;
    }
}

add_filter('redirect_canonical', function ($redirect_url) {
    if (function_exists('my_theme_is_virtual_blog_request') && my_theme_is_virtual_blog_request()) {
        return false;
    }
    return $redirect_url;
}, 10, 2);

if (!function_exists('my_theme_get_paint_calculator_url')) {
    function my_theme_get_paint_calculator_url()
    {
        $calculator_page = get_page_by_path('tinh-son', OBJECT, 'page');
        if ($calculator_page instanceof WP_Post) {
            return (string) get_permalink($calculator_page);
        }

        return home_url('/tinh-son');
    }
}

if (!function_exists('my_theme_get_support_menu_links')) {
    function my_theme_get_support_menu_links()
    {
        return [
            ['label' => 'Tính sơn', 'url' => my_theme_get_paint_calculator_url()],
            ['label' => 'Hướng dẫn mua hàng', 'url' => home_url('/huong-dan-mua-hang')],
            ['label' => 'FAQ', 'url' => home_url('/faq')],
            ['label' => 'Liên hệ kỹ thuật', 'url' => home_url('/lien-he')],
            ['label' => 'Vận chuyển', 'url' => home_url('/van-chuyen-giao-hang')],
            ['label' => 'Giới thiệu', 'url' => home_url('/gioi-thieu')],
            ['label' => 'Giá thợ', 'url' => home_url('/gia-tho')],
            ['label' => 'Blog', 'url' => trailingslashit(home_url('/blog'))],
        ];
    }
}

add_action('template_redirect', function () {
    if (!function_exists('my_theme_is_virtual_blog_request') || !my_theme_is_virtual_blog_request()) {
        return;
    }

    global $wp_query;
    if ($wp_query instanceof WP_Query) {
        $wp_query->is_404 = false;
        $wp_query->is_home = true;
        $wp_query->is_archive = false;
        $wp_query->is_singular = false;
    }

    status_header(200);
}, 1);

add_filter('template_include', function ($template) {
    if (!function_exists('my_theme_is_virtual_blog_request') || !my_theme_is_virtual_blog_request()) {
        return $template;
    }

    $virtual_template = get_theme_file_path('blog-hub.php');
    if (is_string($virtual_template) && $virtual_template !== '' && file_exists($virtual_template)) {
        return $virtual_template;
    }

    return $template;
}, 20);

add_filter('body_class', function ($classes) {
    if (!function_exists('my_theme_is_virtual_blog_request') || !my_theme_is_virtual_blog_request()) {
        return $classes;
    }

    $classes = is_array($classes) ? $classes : [];
    $classes = array_values(array_filter($classes, function ($class_name) {
        return $class_name !== 'error404';
    }));
    $classes[] = 'blog-hub-page';
    $classes[] = 'blog-hub-virtual';

    return array_values(array_unique($classes));
});

add_filter('body_class', function ($classes) {
    if (is_admin()) {
        return $classes;
    }

    $classes = is_array($classes) ? $classes : [];
    $classes[] = 'design-alt-horizon';

    return array_values(array_unique($classes));
}, 30);

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
    function my_theme_get_search_matched_product_cat_ids($raw_query, $product_ids = null, $limit = 0)
    {
        static $request_cache = [];
        static $ancestor_cache = [];

        $query_norm = my_theme_normalize_search_text($raw_query);
        if ($query_norm === '') {
            return [];
        }

        $limit = max(0, (int) $limit);
        $has_product_scope = is_array($product_ids);
        $visible_product_ids = $has_product_scope
            ? my_theme_normalize_product_id_list($product_ids)
            : [];
        if ($has_product_scope && empty($visible_product_ids)) {
            return [];
        }

        $scope_digest = !empty($visible_product_ids)
            ? md5(implode(',', $visible_product_ids))
            : ($has_product_scope ? 'none' : 'all');
        $cache_version = (string) get_option('my_theme_filter_cache_version', '1');
        $request_key = $cache_version . ':' . $query_norm . ':' . $scope_digest;
        if (array_key_exists($request_key, $request_cache)) {
            return ($limit > 0)
                ? array_slice($request_cache[$request_key], 0, $limit)
                : $request_cache[$request_key];
        }
        $cache_key = 'my_theme_search_matched_cat_ids_v2_' . $cache_version . '_' . md5($query_norm . '|' . $scope_digest);
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            $request_cache[$request_key] = array_values(array_filter(array_map('intval', $cached)));
            return ($limit > 0)
                ? array_slice($request_cache[$request_key], 0, $limit)
                : $request_cache[$request_key];
        }

        $allowed_term_ids = [];
        if (!empty($visible_product_ids) && function_exists('my_theme_get_visible_product_category_groups')) {
            $visible_groups = my_theme_get_visible_product_category_groups($visible_product_ids);
            $visible_lookup = isset($visible_groups['lookup']) && is_array($visible_groups['lookup'])
                ? $visible_groups['lookup']
                : [];

            foreach ($visible_lookup as $visible_term_id => $term_data) {
                $visible_term_id = (int) $visible_term_id;
                if ($visible_term_id <= 0) {
                    continue;
                }

                $allowed_term_ids[$visible_term_id] = true;
                if (!isset($ancestor_cache[$visible_term_id])) {
                    $ancestor_cache[$visible_term_id] = array_values(array_filter(array_map('intval', (array) get_ancestors($visible_term_id, 'product_cat', 'taxonomy'))));
                }
                foreach ($ancestor_cache[$visible_term_id] as $ancestor_id) {
                    $ancestor_id = (int) $ancestor_id;
                    if ($ancestor_id > 0) {
                        $allowed_term_ids[$ancestor_id] = true;
                    }
                }
            }

            if (empty($allowed_term_ids)) {
                $request_cache[$request_key] = [];
                set_transient($cache_key, [], 30 * MINUTE_IN_SECONDS);
                return [];
            }
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
                if (!empty($allowed_term_ids) && !isset($allowed_term_ids[(int) $term->term_id])) {
                    continue;
                }
                if ((int) $term->count <= 0) {
                    continue;
                }
                $intent_ids[] = (int) $term->term_id;
            }
            if (!empty($intent_ids)) {
                $request_cache[$request_key] = array_values(array_unique($intent_ids));
                set_transient($cache_key, $request_cache[$request_key], 30 * MINUTE_IN_SECONDS);
                return ($limit > 0)
                    ? array_slice($request_cache[$request_key], 0, $limit)
                    : $request_cache[$request_key];
            }
        }

        $term_query_args = [
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ];
        if (!empty($allowed_term_ids)) {
            $term_query_args['include'] = array_values(array_map('intval', array_keys($allowed_term_ids)));
            $term_query_args['hide_empty'] = false;
        }

        $terms = get_terms($term_query_args);
        if (is_wp_error($terms) || empty($terms)) {
            $request_cache[$request_key] = [];
            set_transient($cache_key, [], 30 * MINUTE_IN_SECONDS);
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
            if (!empty($allowed_term_ids) && !isset($allowed_term_ids[(int) $term->term_id])) {
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
            $request_cache[$request_key] = [];
            set_transient($cache_key, [], 30 * MINUTE_IN_SECONDS);
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

        $request_cache[$request_key] = $matched;
        set_transient($cache_key, $matched, 30 * MINUTE_IN_SECONDS);
        return ($limit > 0) ? array_slice($matched, 0, $limit) : $matched;
    }
}

if (!function_exists('my_theme_get_search_intent_filtered_product_ids')) {
    function my_theme_get_search_intent_filtered_product_ids($product_ids, $category_slug = '')
    {
        static $request_cache = [];

        $source_product_ids = function_exists('my_theme_preserve_product_id_order')
            ? my_theme_preserve_product_id_order($product_ids)
            : my_theme_normalize_product_id_list($product_ids);
        $product_ids = my_theme_normalize_product_id_list($source_product_ids);
        $category_slug = sanitize_title((string) $category_slug);
        if ($category_slug === '' || empty($product_ids) || !taxonomy_exists('product_cat')) {
            return $source_product_ids;
        }

        $cache_version = (string) get_option('my_theme_filter_cache_version', '1');
        $digest = md5(implode(',', $product_ids));
        $source_digest = md5(implode(',', $source_product_ids));
        $request_key = $cache_version . ':' . $category_slug . ':' . $source_digest;
        if (array_key_exists($request_key, $request_cache)) {
            return $request_cache[$request_key];
        }

        $cache_key = 'my_theme_search_intent_filtered_ids_v1_' . $cache_version . '_' . md5($category_slug . '|' . $digest);
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            $request_cache[$request_key] = function_exists('my_theme_filter_product_ids_by_source_order')
                ? my_theme_filter_product_ids_by_source_order($source_product_ids, $cached)
                : my_theme_normalize_product_id_list($cached);
            return $request_cache[$request_key];
        }

        $term = get_term_by('slug', $category_slug, 'product_cat');
        if (!$term instanceof WP_Term || empty($term->term_id)) {
            $request_cache[$request_key] = $source_product_ids;
            set_transient($cache_key, $product_ids, 30 * MINUTE_IN_SECONDS);
            return $request_cache[$request_key];
        }

        $term_ids = [(int) $term->term_id];
        $child_ids = get_term_children((int) $term->term_id, 'product_cat');
        if (!is_wp_error($child_ids) && is_array($child_ids)) {
            foreach ($child_ids as $child_id) {
                $child_id = (int) $child_id;
                if ($child_id > 0) {
                    $term_ids[] = $child_id;
                }
            }
        }
        $term_ids = array_values(array_unique(array_filter($term_ids)));

        $matched_ids = get_posts([
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'post__in' => $product_ids,
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'tax_query' => [
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $term_ids,
                    'operator' => 'IN',
                ],
            ],
        ]);

        $request_cache[$request_key] = function_exists('my_theme_filter_product_ids_by_source_order')
            ? my_theme_filter_product_ids_by_source_order($source_product_ids, $matched_ids)
            : my_theme_normalize_product_id_list($matched_ids);
        set_transient($cache_key, my_theme_normalize_product_id_list($matched_ids), 30 * MINUTE_IN_SECONDS);
        return $request_cache[$request_key];
    }
}

if (!function_exists('my_theme_get_search_matched_product_ids')) {
    function my_theme_get_search_matched_product_ids($raw_query, $product_ids = null, $limit = 48)
    {
        $query_norm = my_theme_normalize_search_text($raw_query);
        if ($query_norm === '') {
            return [];
        }

        if ($product_ids === null) {
            $product_ids = function_exists('my_theme_get_catalog_visible_product_ids')
                ? my_theme_get_catalog_visible_product_ids(false)
                : [];
        }

        $product_ids = my_theme_normalize_product_id_list($product_ids);
        if (empty($product_ids)) {
            return [];
        }

        $limit = max(8, (int) $limit);
        $cache_version = (string) get_option('my_theme_filter_cache_version', '1');
        $digest = md5($query_norm . '|' . implode(',', $product_ids) . '|' . (string) $limit);
        $cache_key = 'my_theme_search_product_match_v3_' . $cache_version . '_' . $digest;
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        $stop_words = ['son', 'san', 'pham', 'gia', 'bao', 'loai', 'cho', 'cua', 'va', 'theo', 'hang'];
        $tokens = array_values(array_filter(explode(' ', $query_norm), function ($token) use ($stop_words) {
            return strlen($token) >= 2 && !in_array($token, $stop_words, true);
        }));

        $brand_intent = function_exists('my_theme_detect_brand_slug_from_text')
            ? my_theme_detect_brand_slug_from_text($query_norm)
            : '';
        $category_intent = function_exists('my_theme_guess_primary_category_slug')
            ? my_theme_guess_primary_category_slug($query_norm)
            : '';
        $search_candidate_ids = $product_ids;
        if ($brand_intent !== '' && function_exists('my_theme_filter_product_ids_by_brand_slug')) {
            $brand_candidate_ids = my_theme_filter_product_ids_by_brand_slug($search_candidate_ids, $brand_intent);
            if (!empty($brand_candidate_ids)) {
                $search_candidate_ids = $brand_candidate_ids;
            }
        }
        if ($category_intent !== '' && function_exists('my_theme_get_search_intent_filtered_product_ids')) {
            $category_candidate_ids = my_theme_get_search_intent_filtered_product_ids($search_candidate_ids, $category_intent);
            if (!empty($category_candidate_ids)) {
                $search_candidate_ids = $category_candidate_ids;
            }
        }
        $search_index = function_exists('my_theme_get_catalog_search_index')
            ? my_theme_get_catalog_search_index($product_ids)
            : [];
        if (empty($search_index)) {
            set_transient($cache_key, [], 30 * MINUTE_IN_SECONDS);
            return [];
        }

        $scored = [];
        foreach ($search_candidate_ids as $product_id) {
            $product_id = (int) $product_id;
            if ($product_id <= 0 || !isset($search_index[$product_id]) || !is_array($search_index[$product_id])) {
                continue;
            }

            $entry = $search_index[$product_id];
            $name = isset($entry['name']) ? (string) $entry['name'] : '';
            $name_norm = isset($entry['name_norm']) ? (string) $entry['name_norm'] : '';
            $haystack = isset($entry['haystack']) ? (string) $entry['haystack'] : '';
            if ($haystack === '') {
                continue;
            }

            $score = 0;
            if ($query_norm === $name_norm) {
                $score += 36;
            } elseif ($name_norm !== '' && strpos($name_norm, $query_norm) !== false) {
                $score += 28;
            } elseif ($name_norm !== '' && strlen($name_norm) >= 4 && strpos($query_norm, $name_norm) !== false) {
                $score += 20;
            }

            if (strpos($haystack, $query_norm) !== false) {
                $score += 10;
            }

            if (!empty($tokens)) {
                $token_hits = 0;
                foreach ($tokens as $token) {
                    if (strpos($haystack, $token) !== false) {
                        $token_hits++;
                    }
                }
                if ($token_hits === count($tokens)) {
                    $score += 12;
                } else {
                    $score += ($token_hits * 3);
                }
            }

            if ($brand_intent !== '' && isset($entry['brand_slug']) && (string) $entry['brand_slug'] === $brand_intent) {
                $score += 10;
            }

            if ($category_intent !== '') {
                $primary_slug = isset($entry['category_slug']) ? (string) $entry['category_slug'] : '';
                $parent_slug = isset($entry['category_parent_slug']) ? (string) $entry['category_parent_slug'] : '';
                if ($primary_slug === $category_intent || $parent_slug === $category_intent) {
                    $score += 8;
                }
            }

            if (!empty($entry['featured'])) {
                $score += 6;
            }
            if (!empty($entry['stock'])) {
                $score += 2;
            }

            $sales_total = isset($entry['sales_total']) ? (int) $entry['sales_total'] : 0;
            if ($sales_total > 0) {
                $score += min(10, (int) floor(log($sales_total + 1, 2)));
            }

            if ($score < 6) {
                continue;
            }

            $created_ts = isset($entry['created_ts']) ? (int) $entry['created_ts'] : 0;
            $scored[] = [
                'id' => $product_id,
                'score' => $score,
                'sales' => $sales_total,
                'featured' => !empty($entry['featured']) ? 1 : 0,
                'stock' => !empty($entry['stock']) ? 1 : 0,
                'created' => $created_ts,
                'name' => $name,
            ];
        }

        if (empty($scored)) {
            set_transient($cache_key, [], 30 * MINUTE_IN_SECONDS);
            return [];
        }

        usort($scored, function ($a, $b) {
            foreach (['score', 'featured', 'sales', 'stock', 'created'] as $field) {
                $value_a = isset($a[$field]) ? (int) $a[$field] : 0;
                $value_b = isset($b[$field]) ? (int) $b[$field] : 0;
                if ($value_a !== $value_b) {
                    return ($value_a > $value_b) ? -1 : 1;
                }
            }
            return strnatcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        $matched_ids = [];
        foreach ($scored as $item) {
            $product_id = isset($item['id']) ? (int) $item['id'] : 0;
            if ($product_id <= 0) {
                continue;
            }
            $matched_ids[] = $product_id;
            if (count($matched_ids) >= $limit) {
                break;
            }
        }

        set_transient($cache_key, $matched_ids, 30 * MINUTE_IN_SECONDS);
        return $matched_ids;
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
        static $request_cache = [];

        if (!function_exists('wc_get_product')) {
            return [];
        }

        $strict_price = (bool) $strict_price;
        $cache_suffix = $strict_price ? 'priced' : 'shop';
        if (array_key_exists($cache_suffix, $request_cache)) {
            return $request_cache[$cache_suffix];
        }
        $cache_key = 'my_theme_catalog_visible_ids_' . $cache_suffix . '_v2';
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            $request_cache[$cache_suffix] = $cached;
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
            'ignore_sticky_posts' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);
        if (empty($candidate_ids)) {
            $request_cache[$cache_suffix] = [];
            set_transient($cache_key, [], 6 * HOUR_IN_SECONDS);
            return [];
        }

        $product_map = function_exists('my_theme_get_product_object_map')
            ? my_theme_get_product_object_map($candidate_ids)
            : [];
        $visible_ids = [];
        foreach ($candidate_ids as $candidate_id) {
            $candidate_id = (int) $candidate_id;
            if ($candidate_id <= 0 || !isset($product_map[$candidate_id]) || !$product_map[$candidate_id] instanceof WC_Product) {
                continue;
            }
            $product = $product_map[$candidate_id];

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
        $request_cache[$cache_suffix] = $visible_ids;
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
        // Keep catalog full: products without explicit price still show "Liên hệ báo giá".
        return my_theme_is_catalog_ready_product($product, false);
    }
}

if (!function_exists('my_theme_render_product_category_menu_item')) {
    function my_theme_render_product_category_menu_item()
    {
        if (!taxonomy_exists('product_cat')) {
            return '';
        }

        $cache_key = 'my_theme_catalog_menu_html_v4';
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

        $html = '<li class="menu-item menu-item-catalog menu-item-has-children"><a href="' . esc_url($shop_url) . '">Danh mục sản phẩm</a>' . $content . '</li>';
        set_transient($cache_key, $html, 12 * HOUR_IN_SECONDS);
        return $html;
    }
}

if (!function_exists('my_theme_render_brand_menu_item')) {
    function my_theme_render_brand_menu_item()
    {
        $cache_key = 'my_theme_brand_menu_html_v4';
        $cached_html = get_transient($cache_key);
        if (is_string($cached_html) && $cached_html !== '') {
            return $cached_html;
        }

        $visible_product_ids = my_theme_get_catalog_visible_product_ids(false);
        if (empty($visible_product_ids)) {
            return '';
        }

        $brand_options = function_exists('my_theme_get_brand_filter_options')
            ? my_theme_get_brand_filter_options($visible_product_ids)
            : [];
        if (empty($brand_options)) {
            return '';
        }

        $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop');
        $html = '<li class="menu-item menu-item-brands menu-item-has-children"><a href="' . esc_url($shop_url) . '">Thương hiệu</a><ul class="sub-menu">';
        $html .= '<li class="menu-item menu-item-brand"><a href="' . esc_url($shop_url) . '">Tất cả thương hiệu</a></li>';
        foreach ($brand_options as $slug => $meta) {
            $label = isset($meta['label']) ? (string) $meta['label'] : '';
            if ($slug === '' || $label === '') {
                continue;
            }
            $brand_url = add_query_arg('brand', sanitize_title((string) $slug), $shop_url);
            $html .= '<li class="menu-item menu-item-brand"><a href="' . esc_url($brand_url) . '">' . esc_html($label) . '</a></li>';
        }
        $html .= '</ul></li>';

        set_transient($cache_key, $html, 12 * HOUR_IN_SECONDS);
        return $html;
    }
}

if (!function_exists('my_theme_render_solution_menu_item')) {
    function my_theme_render_solution_menu_item()
    {
        $catalog = function_exists('my_theme_get_visual_story_group_catalog')
            ? my_theme_get_visual_story_group_catalog()
            : [];
        if (empty($catalog) || !is_array($catalog)) {
            return '';
        }

        $order = ['interior', 'exterior', 'waterproofing', 'epoxy', 'metal', 'grout'];
        $html = '<li class="menu-item menu-item-solutions menu-item-has-children"><a href="' . esc_url(home_url('/giai-phap')) . '">Giải pháp</a><ul class="sub-menu">';
        $html .= '<li class="menu-item menu-item-solution"><a href="' . esc_url(home_url('/giai-phap')) . '">Tất cả giải pháp</a></li>';

        foreach ($order as $group_key) {
            $group_key = sanitize_key((string) $group_key);
            if ($group_key === '' || !isset($catalog[$group_key])) {
                continue;
            }

            $meta = (array) $catalog[$group_key];
            $label = isset($meta['label']) ? trim((string) $meta['label']) : '';
            $url = isset($meta['url']) ? trim((string) $meta['url']) : '';
            if ($label === '' || $url === '') {
                continue;
            }

            $html .= '<li class="menu-item menu-item-solution"><a href="' . esc_url($url) . '">' . esc_html($label) . '</a></li>';
        }

        $html .= '</ul></li>';
        return $html;
    }
}

if (!function_exists('my_theme_render_support_menu_item')) {
    function my_theme_render_support_menu_item()
    {
        $links = my_theme_get_support_menu_links();
        if (empty($links)) {
            return '';
        }

        $menu_url = home_url('/huong-dan-mua-hang');
        $html = '<li class="menu-item menu-item-support menu-item-has-children"><a href="' . esc_url($menu_url) . '">Hỗ trợ nhanh</a><ul class="sub-menu">';

        foreach ($links as $link) {
            $label = isset($link['label']) ? trim((string) $link['label']) : '';
            $url = isset($link['url']) ? trim((string) $link['url']) : '';
            if ($label === '' || $url === '') {
                continue;
            }

            $html .= '<li class="menu-item menu-item-support-link"><a href="' . esc_url($url) . '">' . esc_html($label) . '</a></li>';
        }

        $html .= '</ul></li>';
        return $html;
    }
}

if (!function_exists('my_theme_flush_catalog_menu_cache')) {
    function my_theme_flush_catalog_menu_cache()
    {
        delete_transient('my_theme_catalog_menu_html_v1');
        delete_transient('my_theme_catalog_menu_html_v2');
        delete_transient('my_theme_catalog_menu_html_v3');
        delete_transient('my_theme_catalog_menu_html_v4');
        delete_transient('my_theme_brand_menu_html_v1');
        delete_transient('my_theme_brand_menu_html_v2');
        delete_transient('my_theme_brand_menu_html_v3');
        delete_transient('my_theme_brand_menu_html_v4');
        update_option('my_theme_filter_cache_version', (string) time(), false);
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
        delete_transient('my_theme_home_featured_candidate_ids_v2');
        delete_transient('my_theme_catalog_visible_ids_shop_v1');
        delete_transient('my_theme_catalog_visible_ids_priced_v1');
        delete_transient('my_theme_catalog_visible_ids_shop_v2');
        delete_transient('my_theme_catalog_visible_ids_priced_v2');
        my_theme_flush_catalog_menu_cache();
        update_option('my_theme_filter_cache_version', (string) time(), false);
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

    $items_plain_text = function_exists('my_theme_normalize_search_text')
        ? my_theme_normalize_search_text(wp_strip_all_tags((string) $items))
        : strtolower(trim(wp_strip_all_tags((string) $items)));
    $has_menu_label = function (array $labels) use ($items_plain_text) {
        foreach ($labels as $label) {
            $needle = function_exists('my_theme_normalize_search_text')
                ? my_theme_normalize_search_text((string) $label)
                : strtolower(trim((string) $label));
            if ($needle !== '' && strpos($items_plain_text, $needle) !== false) {
                return true;
            }
        }
        return false;
    };

    if (
        strpos($items, 'menu-item-catalog') === false
        && !$has_menu_label(['Danh mục sản phẩm', 'Danh mục sơn'])
    ) {
        $items .= my_theme_render_product_category_menu_item();
    }

    if (strpos($items, 'menu-item-brands') === false && !$has_menu_label(['Thương hiệu'])) {
        $items .= my_theme_render_brand_menu_item();
    }

    if (strpos($items, 'menu-item-solutions') === false && !$has_menu_label(['Giải pháp'])) {
        $items .= my_theme_render_solution_menu_item();
    }

    if (strpos($items, 'menu-item-support') === false && !$has_menu_label(['Hỗ trợ nhanh'])) {
        $items .= my_theme_render_support_menu_item();
    }

    return $items;
}, 20, 2);

// Fallback menu if user chưa cấu hình.
function my_theme_fallback_menu() {
    $menu = [
        ['label' => 'Trang chủ', 'url' => home_url('/')],
        ['label' => 'Cửa hàng', 'url' => wc_get_page_permalink('shop')],
        [
            'label' => 'Giải pháp',
            'url' => home_url('/giai-phap'),
            'class' => 'menu-item-has-children menu-item-solutions',
            'children' => [
                ['label' => 'Tất cả giải pháp', 'url' => home_url('/giai-phap')],
                ['label' => 'Sơn nội thất', 'url' => home_url('/giai-phap-son-noi-that')],
                ['label' => 'Sơn ngoại thất', 'url' => home_url('/giai-phap-son-ngoai-that')],
                ['label' => 'Chống thấm', 'url' => home_url('/giai-phap-chong-tham')],
                ['label' => 'Sơn epoxy', 'url' => home_url('/giai-phap-son-epoxy')],
                ['label' => 'Sơn kim loại', 'url' => home_url('/giai-phap-son-kim-loai')],
                ['label' => 'Keo và ron', 'url' => home_url('/giai-phap-keo-va-ron')],
            ],
        ],
    ];
    echo '<ul id="primary-menu-list" class="menu main-menu">';
    foreach ($menu as $item) {
        $classes = isset($item['class']) ? trim((string) $item['class']) : '';
        $children = isset($item['children']) && is_array($item['children']) ? $item['children'] : [];
        echo '<li' . ($classes !== '' ? ' class="' . esc_attr($classes) . '"' : '') . '><a href="' . esc_url($item['url']) . '">' . esc_html($item['label']) . '</a>';
        if (!empty($children)) {
            echo '<ul class="sub-menu">';
            foreach ($children as $child) {
                echo '<li><a href="' . esc_url((string) $child['url']) . '">' . esc_html((string) $child['label']) . '</a></li>';
            }
            echo '</ul>';
        }
        echo '</li>';
    }
    echo my_theme_render_product_category_menu_item();
    echo my_theme_render_brand_menu_item();
    echo my_theme_render_support_menu_item();
    echo '</ul>';
}

if (!function_exists('my_theme_get_foundation_pages_config')) {
    function my_theme_get_foundation_pages_config()
    {
        return [
            ['title' => 'Liên hệ', 'slug' => 'lien-he', 'content' => 'Liên hệ Đại lý Sơn Phát Tấn để nhận báo giá và tư vấn kỹ thuật.', 'template' => 'page-lien-he.php'],
            ['title' => 'Chính sách đổi trả', 'slug' => 'chinh-sach-doi-tra', 'content' => 'Xem điều kiện đổi trả và quy trình hỗ trợ.', 'template' => 'page-chinh-sach-doi-tra.php'],
            ['title' => 'Câu hỏi thường gặp', 'slug' => 'faq', 'content' => 'Tổng hợp câu hỏi thường gặp về đặt hàng và thi công.', 'template' => 'page-faq.php'],
            ['title' => 'Hướng dẫn mua hàng', 'slug' => 'huong-dan-mua-hang', 'content' => 'Hướng dẫn đặt hàng nhanh tại Đại lý Sơn Phát Tấn.', 'template' => 'page-huong-dan-mua-hang.php'],
            ['title' => 'Tính sơn', 'slug' => 'tinh-son', 'content' => '[paint_calculator]'],
            ['title' => 'Giới thiệu đại lý', 'slug' => 'gioi-thieu', 'content' => 'Thông tin về Đại lý Sơn Phát Tấn, kinh nghiệm và khu vực phục vụ.', 'template' => 'page-gioi-thieu.php'],
            ['title' => 'Vận chuyển & giao hàng', 'slug' => 'van-chuyen-giao-hang', 'content' => 'Thông tin phạm vi giao hàng, thời gian và phí vận chuyển.', 'template' => 'page-van-chuyen-giao-hang.php'],
            ['title' => 'Giá thợ / công trình', 'slug' => 'gia-tho', 'content' => 'Ưu đãi và điều kiện áp dụng giá thợ, giá công trình.', 'template' => 'page-gia-tho.php'],
            ['title' => 'Giải pháp tổng hợp', 'slug' => 'giai-phap', 'content' => 'Tổng hợp 6 nhóm giải pháp theo từng bề mặt và hạng mục để chọn đúng vật tư nhanh hơn.', 'template' => 'page-giai-phap.php'],
            ['title' => 'Giải pháp sơn nội thất', 'slug' => 'giai-phap-son-noi-that', 'content' => 'Tư vấn chọn sơn nội thất theo phòng, mức lau chùi, ngân sách và hệ lót phù hợp.', 'template' => 'page-giai-phap-son-noi-that.php'],
            ['title' => 'Giải pháp sơn ngoại thất', 'slug' => 'giai-phap-son-ngoai-that', 'content' => 'Tư vấn chọn sơn ngoại thất theo mặt tiền, hiện trạng tường, mức nắng mưa và độ bền màu cần có.', 'template' => 'page-giai-phap-son-ngoai-that.php'],
            ['title' => 'Giải pháp chống thấm', 'slug' => 'giai-phap-chong-tham', 'content' => 'Chọn hệ chống thấm theo sân thượng, tường ngoài trời, khu ẩm và hiện trạng bề mặt.', 'template' => 'page-giai-phap-chong-tham.php'],
            ['title' => 'Giải pháp sơn epoxy', 'slug' => 'giai-phap-son-epoxy', 'content' => 'Chọn hệ epoxy theo nền sàn, mức tải và nhu cầu sử dụng cho gara, kho nhỏ và xưởng.', 'template' => 'page-giai-phap-son-epoxy.php'],
            ['title' => 'Giải pháp sơn kim loại', 'slug' => 'giai-phap-son-kim-loai', 'content' => 'Chọn hệ sơn kim loại và chống rỉ theo mức rỉ, hạng mục và điều kiện ngoài trời.', 'template' => 'page-giai-phap-son-kim-loai.php'],
            ['title' => 'Giải pháp keo và ron gạch', 'slug' => 'giai-phap-keo-va-ron', 'content' => 'Chọn keo dán gạch, chà ron và phụ gia theo khu vực thi công, loại gạch và hiện trạng thực tế.', 'template' => 'page-giai-phap-keo-va-ron.php'],
        ];
    }
}

if (!function_exists('my_theme_ensure_foundation_pages')) {
    function my_theme_ensure_foundation_pages($force = false)
    {
        $version = '20260304-landing-pages-v5';
        if (!$force && get_option('my_theme_foundation_pages_version') === $version) {
            return;
        }

        foreach (my_theme_get_foundation_pages_config() as $page_config) {
            if (!is_array($page_config)) {
                continue;
            }

            $slug = isset($page_config['slug']) ? sanitize_title((string) $page_config['slug']) : '';
            $title = isset($page_config['title']) ? trim((string) $page_config['title']) : '';
            if ($slug === '' || $title === '') {
                continue;
            }

            $page = get_page_by_path($slug, OBJECT, 'page');
            $page_id = ($page instanceof WP_Post) ? (int) $page->ID : 0;

            if ($page_id <= 0) {
                $created = wp_insert_post([
                    'post_title'   => $title,
                    'post_name'    => $slug,
                    'post_content' => isset($page_config['content']) ? (string) $page_config['content'] : '',
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                ], true);

                if (!is_wp_error($created)) {
                    $page_id = (int) $created;
                }
            }

            if ($page_id <= 0) {
                continue;
            }

            $template = isset($page_config['template']) ? trim((string) $page_config['template']) : '';
            if ($template !== '') {
                update_post_meta($page_id, '_wp_page_template', $template);
            }
        }

        update_option('my_theme_foundation_pages_version', $version, false);
    }
}

add_action('after_switch_theme', function () {
    my_theme_ensure_foundation_pages(true);
});

add_action('admin_init', function () {
    if (!current_user_can('manage_options')) {
        return;
    }
    my_theme_ensure_foundation_pages(false);
}, 5);

add_action('init', function () {
    my_theme_ensure_foundation_pages(false);
}, 5);

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

if (!function_exists('my_theme_render_paint_calculator_shortcode')) {
    function my_theme_render_paint_calculator_shortcode()
    {
        ob_start();
        get_template_part('template-parts/paint-calculator');
        return (string) ob_get_clean();
    }
}

add_shortcode('paint_calculator', 'my_theme_render_paint_calculator_shortcode');

if (!function_exists('my_theme_track_recently_viewed_product')) {
    function my_theme_track_recently_viewed_product()
    {
        if (!function_exists('is_product') || !is_product() || !function_exists('wc_get_product')) {
            return;
        }

        $product_id = get_queried_object_id();
        $product_id = $product_id ? (int) $product_id : 0;
        if ($product_id <= 0) {
            return;
        }

        global $product;
        $product = ($product instanceof WC_Product && (int) $product->get_id() === $product_id)
            ? $product
            : wc_get_product($product_id);
        if (!$product instanceof WC_Product) {
            return;
        }

        $cookie_name = 'my_theme_recently_viewed_products';
        $stored = isset($_COOKIE[$cookie_name]) ? (string) wp_unslash($_COOKIE[$cookie_name]) : '';
        $ids = array_values(array_filter(array_map('absint', preg_split('/\|+/', $stored))));
        $ids = array_values(array_diff($ids, [$product_id]));
        array_unshift($ids, $product_id);
        $ids = array_slice(array_values(array_unique($ids)), 0, 12);
        $value = implode('|', $ids);

        if (function_exists('wc_setcookie')) {
            wc_setcookie($cookie_name, $value, time() + MONTH_IN_SECONDS, false);
        } else {
            setcookie($cookie_name, $value, time() + MONTH_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true);
        }

        $_COOKIE[$cookie_name] = $value;
    }
}
add_action('template_redirect', 'my_theme_track_recently_viewed_product', 20);

if (!function_exists('my_theme_get_recently_viewed_products')) {
    function my_theme_get_recently_viewed_products($limit = 4, $exclude_ids = [])
    {
        if (!function_exists('wc_get_product')) {
            return [];
        }

        $limit = max(1, (int) $limit);
        $exclude_ids = array_values(array_filter(array_map('absint', (array) $exclude_ids)));
        $cookie_name = 'my_theme_recently_viewed_products';
        $stored = isset($_COOKIE[$cookie_name]) ? (string) wp_unslash($_COOKIE[$cookie_name]) : '';
        if ($stored === '') {
            return [];
        }

        $ids = array_values(array_filter(array_map('absint', preg_split('/\|+/', $stored))));
        if (empty($ids)) {
            return [];
        }

        $candidate_ids = [];
        foreach ($ids as $product_id) {
            if ($product_id <= 0 || in_array($product_id, $exclude_ids, true)) {
                continue;
            }
            $candidate_ids[] = (int) $product_id;
            if (count($candidate_ids) >= 12) {
                break;
            }
        }

        $candidate_ids = function_exists('my_theme_preserve_product_id_order')
            ? my_theme_preserve_product_id_order($candidate_ids)
            : my_theme_normalize_product_id_list($candidate_ids);
        if (empty($candidate_ids)) {
            return [];
        }

        $product_map = function_exists('my_theme_get_product_object_map')
            ? my_theme_get_product_object_map($candidate_ids)
            : [];
        $products = [];
        foreach ($candidate_ids as $product_id) {
            if (!isset($product_map[$product_id]) || !$product_map[$product_id] instanceof WC_Product) {
                continue;
            }
            $product = $product_map[$product_id];
            if (function_exists('my_theme_is_catalog_ready_product') && !my_theme_is_catalog_ready_product($product, true)) {
                continue;
            }
            $products[] = $product;
            if (count($products) >= $limit) {
                break;
            }
        }

        return $products;
    }
}

if (!function_exists('my_theme_render_recently_viewed_products')) {
    function my_theme_render_recently_viewed_products(array $args = [])
    {
        $limit = isset($args['limit']) ? max(1, (int) $args['limit']) : 4;
        $exclude_ids = isset($args['exclude_ids']) ? (array) $args['exclude_ids'] : [];
        $products = my_theme_get_recently_viewed_products($limit, $exclude_ids);
        if (empty($products)) {
            return;
        }

        wc_get_template(
            'single-product/related.php',
            [
                'related_products' => $products,
                'posts_per_page' => $limit,
                'columns' => $limit,
                'section_title' => isset($args['title']) ? (string) $args['title'] : 'Sản phẩm bạn vừa xem',
                'section_aria_label' => isset($args['aria_label']) ? (string) $args['aria_label'] : 'Sản phẩm bạn vừa xem',
                'section_class' => isset($args['class']) ? (string) $args['class'] : 'related-products-block--recently-viewed',
            ]
        );
    }
}

if (!function_exists('my_theme_get_commerce_support_payload')) {
    function my_theme_get_commerce_support_payload($context = 'cart')
    {
        $context = sanitize_key((string) $context);
        $store_snapshot = function_exists('my_theme_get_store_snapshot') ? my_theme_get_store_snapshot() : [];
        $phone_href = isset($store_snapshot['phone_href']) ? (string) $store_snapshot['phone_href'] : 'tel:0944857999';
        $phone_display = isset($store_snapshot['phone_display']) ? (string) $store_snapshot['phone_display'] : '0944 857 999';
        $zalo_url = isset($store_snapshot['zalo_url']) ? (string) $store_snapshot['zalo_url'] : 'https://zalo.me/0944857999';
        $hours_display = isset($store_snapshot['hours_display']) ? (string) $store_snapshot['hours_display'] : 'Thứ 2 - Thứ 7: 7:30 - 18:00';
        $service_areas = isset($store_snapshot['service_areas_display']) ? (string) $store_snapshot['service_areas_display'] : 'TP.HCM, Bình Dương, Đồng Nai';
        $cart_url = function_exists('my_theme_get_cart_url_safe') ? my_theme_get_cart_url_safe() : home_url('/gio-hang');
        $checkout_url = function_exists('my_theme_get_checkout_url_safe') ? my_theme_get_checkout_url_safe() : home_url('/thanh-toan');
        $shop_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
        $contact_url = home_url('/lien-he');
        $faq_url = home_url('/faq');

        $payloads = [
            'home' => [
                'eyebrow' => 'Điều hướng mua hàng từ trang chủ',
                'title' => 'Nếu chưa biết nên bắt đầu từ đâu, hãy đi theo 3 đường này',
                'subtitle' => 'Trang chủ đã chia sẵn theo kho sản phẩm, nhóm giải pháp và form nhận nhu cầu để khách chốt vật tư nhanh hơn ngay từ vòng đầu.',
                'checklist' => [
                    'Nếu đã có hãng, dòng hoặc mã sơn cụ thể, đi thẳng vào kho sản phẩm để lọc và so quy cách nhanh hơn.',
                    'Nếu đang xuất phát từ hiện trạng bề mặt hoặc hạng mục thi công, nên vào nhóm giải pháp tương ứng trước rồi mới chốt mã.',
                    'Nếu công trình cần báo giá hoặc chưa chắc hệ vật tư, dùng form nhận nhu cầu hoặc gọi hotline để đội kỹ thuật điều hướng lại.',
                ],
                'cards' => [
                    [
                        'question' => 'Khi nào nên vào shop thay vì hỏi tư vấn ngay?',
                        'answer' => 'Khi bạn đã biết hãng, dòng hoặc muốn tự so nhanh quy cách, giá và nhóm mã đang bán trước khi chốt đơn.',
                    ],
                    [
                        'question' => 'Khi nào nên đi qua trang giải pháp?',
                        'answer' => 'Khi bạn đang chọn theo nhu cầu thực tế như sơn nội thất, ngoại thất, chống thấm, epoxy, kim loại hoặc keo ron.',
                    ],
                    [
                        'question' => 'Form báo giá trên homepage cần ghi gì là đủ?',
                        'answer' => 'Chỉ cần diện tích ước tính, loại bề mặt, khu vực giao và tiến độ cần hàng là đội kỹ thuật đã có thể khoanh vật tư ban đầu.',
                    ],
                ],
                'actions' => [
                    ['label' => 'Mở kho sản phẩm', 'url' => $shop_url, 'class' => 'btn btn-primary btn-sm'],
                    ['label' => 'Xem 6 nhóm giải pháp', 'url' => home_url('/giai-phap'), 'class' => 'btn btn-outline btn-sm'],
                    ['label' => 'Gửi nhu cầu báo giá', 'url' => home_url('/#lead-capture-home-page'), 'class' => 'btn btn-accent btn-sm'],
                ],
                'meta' => [$hours_display, $service_areas, 'Hỗ trợ qua hotline và Zalo kỹ thuật'],
            ],
            'cart' => [
                'eyebrow' => 'Chốt lại trước khi thanh toán',
                'title' => 'Trước khi đi tới thanh toán, nên kiểm tra 3 điểm này',
                'subtitle' => 'Chốt đúng quy cách, thời gian giao và nhu cầu hóa đơn ngay từ giỏ hàng sẽ giúp checkout nhanh và ít phát sinh hơn.',
                'checklist' => [
                    'Kiểm tra lại dung tích, khối lượng hoặc quy cách từng mã để tránh chốt thiếu vật tư.',
                    'Nếu đơn công trình cần giao theo tiến độ hoặc tách nhiều đợt, nên báo trước ở bước ghi chú.',
                    'Nếu cần hóa đơn hoặc chứng từ hãng, nên chuẩn bị thông tin công ty ngay từ bây giờ để checkout gọn hơn.',
                ],
                'cards' => [
                    [
                        'question' => 'Giỏ hàng đã có đủ lớp lót, phủ hoặc vật tư phụ chưa?',
                        'answer' => 'Nhiều đơn bị thiếu lớp nền hoặc vật tư bổ trợ. Nếu còn đang so từng mã, nên quay lại nhóm giải pháp hoặc danh mục liên quan trước khi đi tiếp.',
                    ],
                    [
                        'question' => 'Khi nào nên đi thẳng sang checkout?',
                        'answer' => 'Khi bạn đã chốt đủ mã, quy cách và địa điểm giao. Nếu vẫn còn phải hỏi kỹ theo bề mặt, nên nhắn Zalo kỹ thuật trước để tránh sửa đơn nhiều lần.',
                    ],
                    [
                        'question' => 'Đơn hàng ở đây thanh toán như thế nào?',
                        'answer' => 'Hệ thống đang chốt theo luồng chuyển khoản 100% trước khi giao, nên bạn sẽ chỉ cần xác nhận đúng người nhận, địa chỉ và ghi chú giao hàng.',
                    ],
                ],
                'actions' => [
                    ['label' => 'Đi tới thanh toán', 'url' => $checkout_url, 'class' => 'btn btn-primary btn-sm'],
                    ['label' => 'Nhắn Zalo kỹ thuật', 'url' => $zalo_url, 'class' => 'btn btn-outline btn-sm', 'target' => '_blank'],
                    ['label' => 'Xem FAQ', 'url' => $faq_url, 'class' => 'btn btn-accent btn-sm'],
                ],
                'meta' => [$hours_display, $service_areas, 'Chuyển khoản 100% trước khi giao'],
            ],
            'checkout' => [
                'eyebrow' => 'Bước cuối để chốt đơn',
                'title' => 'Bạn chỉ còn một bước để chốt đơn hàng',
                'subtitle' => 'Điền đúng người nhận, số điện thoại, địa chỉ và ghi chú giao hàng để đội vận hành xác nhận nhanh hơn sau khi đơn được tạo.',
                'checklist' => [
                    'Giữ số điện thoại đang dùng để đội vận hành dễ gọi xác nhận đơn và lịch giao.',
                    'Điền địa chỉ nhận hàng rõ khu vực, tuyến đường hoặc mốc giao nếu công trình khó tìm.',
                    'Nếu cần hóa đơn, chứng từ hoặc chia đợt giao, ghi rõ ngay trong phần ghi chú đơn hàng.',
                ],
                'cards' => [
                    [
                        'question' => 'Sau khi đặt hàng thì đội hỗ trợ liên hệ bằng cách nào?',
                        'answer' => 'Đội vận hành sẽ gọi hoặc nhắn theo số điện thoại bạn để xác nhận thông tin nhận hàng, quy cách và tiến độ giao trong giờ làm việc.',
                    ],
                    [
                        'question' => 'Nếu cần đổi quy cách hoặc bổ sung vật tư sau khi đặt thì sao?',
                        'answer' => 'Bạn vẫn có thể gọi hotline hoặc nhắn Zalo kỹ thuật ngay sau khi tạo đơn để điều chỉnh trước khi đội giao chốt lệnh xuất hàng.',
                    ],
                    [
                        'question' => 'Cần chuẩn bị gì để việc giao hàng suôn sẻ hơn?',
                        'answer' => 'Nên ghi rõ giờ nhận, người nhận, điều kiện xuống hàng và yêu cầu hóa đơn nếu có, nhất là với đơn công trình hoặc giao cho bảo vệ nhận thay.',
                    ],
                ],
                'actions' => [
                    ['label' => 'Quay lại giỏ hàng', 'url' => $cart_url, 'class' => 'btn btn-outline btn-sm'],
                    ['label' => 'Gọi ' . $phone_display, 'url' => $phone_href, 'class' => 'btn btn-primary btn-sm'],
                    ['label' => 'Gửi yêu cầu hỗ trợ', 'url' => $contact_url, 'class' => 'btn btn-accent btn-sm'],
                ],
                'meta' => [$hours_display, $service_areas, 'Hỗ trợ hóa đơn và chứng từ khi cần'],
            ],
            'account' => [
                'eyebrow' => 'Tài khoản & hỗ trợ',
                'title' => 'Dùng tài khoản để theo dõi đơn và chốt hỗ trợ kỹ thuật gọn hơn',
                'subtitle' => 'Đăng nhập hoặc đăng ký để lưu thông tin nhận hàng, xem lại đơn cũ và rút ngắn bước xác nhận khi đặt lại vật tư cho công trình.',
                'checklist' => [
                    'Nếu thường xuyên đặt lại vật tư, nên lưu sẵn số điện thoại và địa chỉ nhận hàng để checkout nhanh hơn ở các lần sau.',
                    'Nếu bạn chỉ cần hỏi kỹ thuật hoặc xin báo giá, vẫn có thể dùng Zalo hoặc form liên hệ mà không cần chờ tạo tài khoản xong.',
                    'Với đơn công trình cần hóa đơn hoặc giao nhiều đợt, tài khoản giúp đối chiếu lịch sử đơn hàng và thông tin nhận hàng gọn hơn.',
                ],
                'cards' => [
                    [
                        'question' => 'Có cần có tài khoản mới đặt được hàng không?',
                        'answer' => 'Không bắt buộc cho mọi trường hợp, nhưng tài khoản giúp lưu thông tin nhận hàng và theo dõi đơn cũ thuận tiện hơn nếu bạn mua lặp lại.',
                    ],
                    [
                        'question' => 'Tài khoản này hữu ích nhất khi nào?',
                        'answer' => 'Khi bạn là thợ, chủ nhà hoặc công trình hay đặt lại cùng nhóm vật tư và muốn rút ngắn bước nhập thông tin mỗi lần mua.',
                    ],
                    [
                        'question' => 'Nếu vẫn chưa chắc mã vật tư thì nên làm gì?',
                        'answer' => 'Hãy nhắn Zalo kỹ thuật hoặc gửi yêu cầu báo giá để được điều hướng trước, rồi mới quay lại tạo đơn hoặc dùng tài khoản để theo dõi tiếp.',
                    ],
                ],
                'actions' => [
                    ['label' => 'Mở kho sản phẩm', 'url' => $shop_url, 'class' => 'btn btn-primary btn-sm'],
                    ['label' => 'Gửi yêu cầu báo giá', 'url' => $contact_url, 'class' => 'btn btn-outline btn-sm'],
                    ['label' => 'Xem FAQ', 'url' => $faq_url, 'class' => 'btn btn-accent btn-sm'],
                ],
                'meta' => [$hours_display, $service_areas, 'Theo dõi đơn cũ và đặt lại vật tư nhanh hơn'],
            ],
        ];

        return isset($payloads[$context]) ? $payloads[$context] : [];
    }
}

if (!function_exists('my_theme_render_commerce_support')) {
    function my_theme_render_commerce_support($context = 'cart')
    {
        // Product-first UX: tạm tắt toàn bộ khối hỗ trợ dài để giao diện tập trung vào sản phẩm.
        return;

        $payload = my_theme_get_commerce_support_payload($context);
        if (empty($payload)) {
            return;
        }

        $context = sanitize_key((string) $context);
        $section_class = 'commerce-support commerce-support--' . $context;
        $actions = isset($payload['actions']) && is_array($payload['actions']) ? $payload['actions'] : [];
        $meta_items = isset($payload['meta']) && is_array($payload['meta']) ? $payload['meta'] : [];
        $cards = isset($payload['cards']) && is_array($payload['cards']) ? $payload['cards'] : [];
        ?>
        <section class="page-section <?php echo esc_attr($section_class); ?>" aria-label="<?php echo esc_attr((string) ($payload['title'] ?? 'Hỗ trợ đặt hàng')); ?>">
          <div class="section-heading commerce-support__head">
            <div>
              <?php if (!empty($payload['eyebrow'])) : ?>
                <p class="eyebrow eyebrow-muted"><?php echo esc_html((string) $payload['eyebrow']); ?></p>
              <?php endif; ?>
              <h2 class="section-title"><?php echo esc_html((string) ($payload['title'] ?? 'Hỗ trợ đặt hàng')); ?></h2>
              <?php if (!empty($payload['subtitle'])) : ?>
                <p class="section-sub"><?php echo esc_html((string) $payload['subtitle']); ?></p>
              <?php endif; ?>
            </div>
          </div>

          <div class="commerce-support__grid">
            <div class="commerce-support__panel">
              <ol class="list-numbered landing-checklist">
                <?php foreach ((array) ($payload['checklist'] ?? []) as $item) : ?>
                  <?php $item = trim((string) $item); if ($item === '') { continue; } ?>
                  <li><?php echo esc_html($item); ?></li>
                <?php endforeach; ?>
              </ol>

              <?php if (!empty($meta_items)) : ?>
                <div class="commerce-support__meta" aria-label="Thông tin hỗ trợ">
                  <?php foreach ($meta_items as $meta_item) : ?>
                    <?php $meta_item = trim((string) $meta_item); if ($meta_item === '') { continue; } ?>
                    <span class="commerce-support__meta-item"><?php echo esc_html($meta_item); ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <?php if (!empty($actions)) : ?>
                <div class="commerce-support__actions">
                  <?php foreach ($actions as $action) : ?>
                    <?php
                    $action = is_array($action) ? $action : [];
                    $action_label = trim((string) ($action['label'] ?? ''));
                    $action_url = (string) ($action['url'] ?? '');
                    $action_class = trim((string) ($action['class'] ?? 'btn btn-outline btn-sm'));
                    $action_target = trim((string) ($action['target'] ?? ''));
                    if ($action_label === '' || $action_url === '') {
                        continue;
                    }
                    ?>
                    <a class="<?php echo esc_attr($action_class); ?>" href="<?php echo esc_url($action_url); ?>"<?php echo $action_target !== '' ? ' target="' . esc_attr($action_target) . '" rel="noopener"' : ''; ?>><?php echo esc_html($action_label); ?></a>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

            <div class="info-grid commerce-support__cards">
              <?php foreach ($cards as $card) : ?>
                <?php
                $card = is_array($card) ? $card : [];
                $question = trim((string) ($card['question'] ?? ''));
                $answer = trim((string) ($card['answer'] ?? ''));
                if ($question === '' || $answer === '') {
                    continue;
                }
                ?>
                <article class="info-card commerce-support__card">
                  <h3><?php echo esc_html($question); ?></h3>
                  <p><?php echo esc_html($answer); ?></p>
                </article>
              <?php endforeach; ?>
            </div>
          </div>
        </section>
        <?php
    }
}

if (!function_exists('my_theme_render_checkout_snapshot_panel')) {
    function my_theme_render_checkout_snapshot_panel()
    {
        // Product-first UX: tắt snapshot phụ ở checkout để giảm nhiễu.
        return;

        if (!function_exists('is_checkout') || !is_checkout()) {
            return;
        }
        if (function_exists('is_order_received_page') && is_order_received_page()) {
            return;
        }
        if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
            return;
        }

        $cart = WC()->cart;
        $item_count = max(0, (int) $cart->get_cart_contents_count());
        $subtotal_text = trim(wp_strip_all_tags((string) $cart->get_cart_subtotal()));
        $needs_shipping = $cart->needs_shipping();
        $store_snapshot = function_exists('my_theme_get_store_snapshot') ? my_theme_get_store_snapshot() : [];
        $hours_display = isset($store_snapshot['hours_display']) ? (string) $store_snapshot['hours_display'] : 'Thứ 2 - Thứ 7: 7:30 - 18:00';
        $service_areas = isset($store_snapshot['service_areas_display']) ? (string) $store_snapshot['service_areas_display'] : 'TP.HCM, Bình Dương, Đồng Nai';
        $phone_display = isset($store_snapshot['phone_display']) ? (string) $store_snapshot['phone_display'] : '0944 857 999';
        $phone_href = isset($store_snapshot['phone_href']) ? (string) $store_snapshot['phone_href'] : 'tel:0944857999';
        $cart_url = function_exists('my_theme_get_cart_url_safe') ? my_theme_get_cart_url_safe() : home_url('/gio-hang');
        $shop_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
        ?>
        <section class="page-section checkout-snapshot" aria-label="Tóm tắt trước khi thanh toán">
          <div class="section-heading checkout-snapshot__head">
            <div>
              <p class="eyebrow eyebrow-muted">Rà nhanh trước khi bấm đặt hàng</p>
              <h2 class="section-title">Thông tin đang được chốt ở bước này</h2>
              <p class="section-sub">Giữ số điện thoại sẵn sàng, ghi rõ địa chỉ và ghi chú giao hàng nếu cần chia đợt hoặc xuất hóa đơn.</p>
            </div>
            <div class="checkout-snapshot__actions">
              <a class="btn btn-outline btn-sm" href="<?php echo esc_url($cart_url); ?>">Quay lại giỏ hàng</a>
              <a class="btn btn-primary btn-sm" href="<?php echo esc_url($phone_href); ?>">Gọi <?php echo esc_html($phone_display); ?></a>
              <a class="btn btn-accent btn-sm" href="<?php echo esc_url($shop_url); ?>">Mở thêm sản phẩm</a>
            </div>
          </div>
          <div class="shop-summary__insight" aria-label="Thông tin checkout nhanh">
            <span class="chip chip--soft"><?php echo esc_html((string) $item_count); ?> sản phẩm đang chờ chốt</span>
            <?php if ($subtotal_text !== '') : ?><span class="chip chip--soft">Tạm tính: <?php echo esc_html($subtotal_text); ?></span><?php endif; ?>
            <span class="chip chip--soft"><?php echo esc_html($needs_shipping ? 'Có bước giao hàng và xác nhận người nhận' : 'Không cần bước giao hàng riêng'); ?></span>
            <span class="chip chip--soft"><?php echo esc_html($hours_display); ?></span>
            <span class="chip chip--soft"><?php echo esc_html($service_areas); ?></span>
          </div>
        </section>
        <?php
    }
}

// Ép nút "Proceed to checkout" hiển thị tiếng Việt bằng hook WooCommerce.
add_action('init', function () {
    remove_action('woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20);
    add_action('woocommerce_proceed_to_checkout', 'my_theme_button_proceed_to_checkout', 20);
});

function my_theme_button_proceed_to_checkout() {
    $checkout_url = function_exists('my_theme_get_checkout_url_safe') ? my_theme_get_checkout_url_safe() : '';
    if ($checkout_url === '') {
        return;
    }
    echo '<a href="' . esc_url($checkout_url) . '" class="checkout-button button alt wc-forward">' . esc_html('Thanh toán') . '</a>';
}

// Keep checkout page accessible even when cart is empty so menu "Thanh toán" is never confusing.
add_filter('woocommerce_checkout_redirect_empty_cart', '__return_false');

// Replace WooCommerce's expired-session checkout output with a friendly empty state.
add_filter('the_content', function ($content) {
    if (is_admin() || !is_main_query() || !in_the_loop()) {
        return $content;
    }
    if (!function_exists('is_checkout') || !is_checkout()) {
        return $content;
    }
    if (function_exists('is_order_received_page') && is_order_received_page()) {
        return $content;
    }
    if (!function_exists('WC')) {
        return $content;
    }

    $cart = WC()->cart;
    if ($cart && !$cart->is_empty()) {
        return $content;
    }

    $shop_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
    $cart_url = function_exists('my_theme_get_cart_url_safe') ? my_theme_get_cart_url_safe() : home_url('/gio-hang');
    return ''
        . '<section class="empty-state empty-state--checkout" aria-label="Chưa có sản phẩm để thanh toán">'
        . '<h2>Chưa có sản phẩm để thanh toán</h2>'
        . '<p>Thêm sản phẩm vào giỏ trước khi điền thông tin nhận hàng và thanh toán.</p>'
        . '<div class="empty-state__actions">'
        . '<a class="btn btn-primary" href="' . esc_url($shop_url) . '">Quay lại cửa hàng</a>'
        . '<a class="btn btn-outline" href="' . esc_url($cart_url) . '">Xem giỏ hàng</a>'
        . '</div>'
        . '</section>';
}, 20);

// Never cache cart/checkout HTML to avoid stale totals/items.
add_action('template_redirect', function () {
    $is_cart_page = function_exists('is_cart') && is_cart();
    $is_checkout_page = function_exists('is_checkout') && is_checkout();
    if (!$is_cart_page && !$is_checkout_page) {
        return;
    }

    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }
    if (!defined('DONOTCACHEOBJECT')) {
        define('DONOTCACHEOBJECT', true);
    }
    if (!defined('DONOTCACHEDB')) {
        define('DONOTCACHEDB', true);
    }

    nocache_headers();
}, 0);

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
// Keep checkout minimal: only notices and native WooCommerce payment form.

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

if (!function_exists('my_theme_admin_can_manage_store')) {
    function my_theme_admin_can_manage_store()
    {
        return current_user_can('manage_options') || current_user_can('manage_woocommerce');
    }
}

if (!function_exists('my_theme_admin_update_menu_label')) {
    function my_theme_admin_update_menu_label($slug, $label)
    {
        global $menu;
        if (!is_array($menu)) {
            return;
        }

        foreach ($menu as &$item) {
            if (!isset($item[2]) || (string) $item[2] !== (string) $slug) {
                continue;
            }

            $item[0] = $label;
            break;
        }
    }
}

if (!function_exists('my_theme_admin_get_hidden_menu_slugs')) {
    function my_theme_admin_get_hidden_menu_slugs()
    {
        return [
            'edit-comments.php',
            'jetpack',
            'mailpoet-homepage',
            'mailpoet-newsletters',
            'wc-admin&path=/analytics/overview',
            'wc-admin&path=/marketing',
            'wc-admin&path=/payments',
            'wc-settings&tab=checkout',
        ];
    }
}

add_action('admin_menu', function () {
    if (!my_theme_admin_can_manage_store()) {
        return;
    }

    foreach (my_theme_admin_get_hidden_menu_slugs() as $slug) {
        remove_menu_page($slug);
    }

    my_theme_admin_update_menu_label('woocommerce', 'Cửa hàng');
    my_theme_admin_update_menu_label('edit.php?post_type=customer_lead', 'Lead');
    my_theme_admin_update_menu_label('edit.php', 'Bài viết');
}, 999);

add_filter('custom_menu_order', function ($enabled) {
    if (!my_theme_admin_can_manage_store()) {
        return $enabled;
    }
    return true;
});

add_filter('menu_order', function ($menu_order) {
    if (!my_theme_admin_can_manage_store() || !is_array($menu_order)) {
        return $menu_order;
    }

    $preferred = [
        'index.php',
        'separator1',
        'edit.php?post_type=product',
        'woocommerce',
        'edit.php?post_type=customer_lead',
        'edit.php',
        'upload.php',
        'edit.php?post_type=page',
        'themes.php',
        'users.php',
        'plugins.php',
        'tools.php',
        'options-general.php',
        'separator-last',
    ];

    $ordered = [];
    foreach ($preferred as $slug) {
        if (in_array($slug, $menu_order, true)) {
            $ordered[] = $slug;
        }
    }

    foreach ($menu_order as $slug) {
        if (!in_array($slug, $ordered, true)) {
            $ordered[] = $slug;
        }
    }

    return $ordered;
});

add_action('load-post.php', function () {
    if (!is_admin()) {
        return;
    }

    $action = isset($_GET['action']) ? sanitize_key((string) $_GET['action']) : '';
    if ($action !== 'trash') {
        return;
    }

    $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
    if ($post_id <= 0 || get_post_status($post_id) !== 'trash') {
        return;
    }

    if (!current_user_can('delete_post', $post_id)) {
        return;
    }

    $post = get_post($post_id);
    if (!$post instanceof WP_Post) {
        return;
    }

    $redirect_args = [
        'trashed' => 1,
        'ids'     => $post_id,
    ];
    $redirect_url = admin_url('edit.php');
    if ($post->post_type !== 'post' && $post->post_type !== '') {
        $redirect_args['post_type'] = $post->post_type;
    }

    wp_safe_redirect(add_query_arg($redirect_args, $redirect_url));
    exit;
});

if (!function_exists('my_theme_get_product_brand_taxonomy')) {
    function my_theme_get_product_brand_taxonomy()
    {
        foreach (['pa_brand', 'product_brand', 'brand'] as $candidate) {
            if (taxonomy_exists($candidate)) {
                return $candidate;
            }
        }

        return '';
    }
}

if (!function_exists('my_theme_get_product_brand_manage_terms_capability')) {
    function my_theme_get_product_brand_manage_terms_capability()
    {
        $taxonomy = my_theme_get_product_brand_taxonomy();
        if ($taxonomy !== '') {
            $taxonomy_object = get_taxonomy($taxonomy);
            if ($taxonomy_object && isset($taxonomy_object->cap->manage_terms) && is_string($taxonomy_object->cap->manage_terms) && $taxonomy_object->cap->manage_terms !== '') {
                return $taxonomy_object->cap->manage_terms;
            }
        }

        return 'manage_woocommerce';
    }
}

if (!function_exists('my_theme_get_product_brand_admin_list_slug')) {
    function my_theme_get_product_brand_admin_list_slug($brand_slug = '')
    {
        $brand_slug = sanitize_title((string) $brand_slug);
        $args = ['post_type' => 'product'];
        if ($brand_slug !== '') {
            $args['my_theme_brand_filter'] = $brand_slug;
        }

        return add_query_arg($args, 'edit.php');
    }
}

if (!function_exists('my_theme_get_product_brand_admin_term_slug')) {
    function my_theme_get_product_brand_admin_term_slug($focus = '')
    {
        $taxonomy = my_theme_get_product_brand_taxonomy();
        if ($taxonomy === '') {
            return '';
        }

        $args = [
            'taxonomy'  => $taxonomy,
            'post_type' => 'product',
        ];

        $focus = sanitize_key((string) $focus);
        if ($focus !== '') {
            $args['my_theme_focus'] = $focus;
        }

        return add_query_arg($args, 'edit-tags.php');
    }
}

if (!function_exists('my_theme_get_product_brand_admin_term_url')) {
    function my_theme_get_product_brand_admin_term_url($focus = '')
    {
        $slug = my_theme_get_product_brand_admin_term_slug($focus);
        if ($slug === '') {
            return '';
        }

        return admin_url($slug);
    }
}

if (!function_exists('my_theme_get_product_brand_admin_menu_items')) {
    function my_theme_get_product_brand_admin_menu_items()
    {
        $taxonomy = my_theme_get_product_brand_taxonomy();
        if ($taxonomy !== '') {
            $terms = get_terms([
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ]);

            if (!is_wp_error($terms) && !empty($terms)) {
                $items = [];
                foreach ($terms as $term) {
                    if (!$term instanceof WP_Term || empty($term->slug) || empty($term->name)) {
                        continue;
                    }

                    $linked_products = get_posts([
                        'post_type'              => 'product',
                        'post_status'            => 'any',
                        'posts_per_page'         => 1,
                        'fields'                 => 'ids',
                        'no_found_rows'          => true,
                        'ignore_sticky_posts'    => true,
                        'update_post_meta_cache' => false,
                        'update_post_term_cache' => false,
                        'tax_query'              => [
                            [
                                'taxonomy' => $taxonomy,
                                'field'    => 'term_id',
                                'terms'    => [(int) $term->term_id],
                            ],
                        ],
                    ]);
                    if (empty($linked_products)) {
                        continue;
                    }

                    $items[] = [
                        'brand_slug' => sanitize_title((string) $term->slug),
                        'label'      => (string) $term->name,
                        'menu_slug'  => my_theme_get_product_brand_admin_list_slug((string) $term->slug),
                    ];
                }

                if (!empty($items)) {
                    return $items;
                }
            }
        }

        $brand_options = function_exists('my_theme_get_brand_filter_options')
            ? my_theme_get_brand_filter_options()
            : [];
        if (!is_array($brand_options) || empty($brand_options)) {
            return [];
        }

        uasort($brand_options, function ($a, $b) {
            return strnatcasecmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
        });

        $items = [];
        foreach ($brand_options as $slug => $meta) {
            $slug = sanitize_title((string) $slug);
            $label = trim((string) ($meta['label'] ?? ''));
            if ($slug === '' || $label === '') {
                continue;
            }

            $items[] = [
                'brand_slug' => $slug,
                'label'      => $label,
                'menu_slug'  => my_theme_get_product_brand_admin_list_slug($slug),
            ];
        }

        return $items;
    }
}

add_action('admin_menu', function () {
    if (!is_admin() || !my_theme_admin_can_manage_store()) {
        return;
    }

    $parent_slug = 'edit.php?post_type=product';
    $brand_term_slug = my_theme_get_product_brand_admin_term_slug();
    $add_brand_slug = my_theme_get_product_brand_admin_term_slug('add');
    $brand_items = my_theme_get_product_brand_admin_menu_items();

    global $submenu;
    if ($brand_term_slug !== '' && isset($submenu[$parent_slug]) && is_array($submenu[$parent_slug])) {
        foreach ($submenu[$parent_slug] as &$item) {
            $item_slug = isset($item[2]) ? (string) $item[2] : '';
            if ($item_slug === $brand_term_slug) {
                $item[0] = 'Tất cả hãng';
                break;
            }
        }
        unset($item);
    }

    if ($add_brand_slug !== '') {
        add_submenu_page(
            $parent_slug,
            'Thêm hãng',
            'Thêm hãng',
            my_theme_get_product_brand_manage_terms_capability(),
            $add_brand_slug
        );
    }

    foreach ($brand_items as $item) {
        add_submenu_page(
            $parent_slug,
            'Sản phẩm hãng ' . $item['label'],
            $item['label'],
            'edit_products',
            $item['menu_slug']
        );
    }

    if (!isset($submenu[$parent_slug]) || !is_array($submenu[$parent_slug])) {
        return;
    }

    $priority_slugs = [
        'edit.php?post_type=product',
        'post-new.php?post_type=product',
    ];
    if ($brand_term_slug !== '') {
        $priority_slugs[] = $brand_term_slug;
    }
    if ($add_brand_slug !== '') {
        $priority_slugs[] = $add_brand_slug;
    }
    foreach ($brand_items as $item) {
        if (!empty($item['menu_slug'])) {
            $priority_slugs[] = (string) $item['menu_slug'];
        }
    }

    $priority_lookup = array_fill_keys($priority_slugs, true);
    $priority_items = [];
    $other_items = [];
    foreach ($submenu[$parent_slug] as $item) {
        $item_slug = isset($item[2]) ? (string) $item[2] : '';
        if ($item_slug !== '' && isset($priority_lookup[$item_slug])) {
            $priority_items[$item_slug] = $item;
            continue;
        }
        $other_items[] = $item;
    }

    $ordered = [];
    foreach ($priority_slugs as $item_slug) {
        if (isset($priority_items[$item_slug])) {
            $ordered[] = $priority_items[$item_slug];
        }
    }

    $submenu[$parent_slug] = array_values(array_merge($ordered, $other_items));
}, 999);

add_filter('parent_file', function ($parent_file) {
    $taxonomy = isset($_GET['taxonomy']) ? sanitize_key((string) $_GET['taxonomy']) : '';
    $post_type = isset($_GET['post_type']) ? sanitize_key((string) $_GET['post_type']) : '';
    if ($post_type === 'product' && $taxonomy !== '' && $taxonomy === my_theme_get_product_brand_taxonomy()) {
        return 'edit.php?post_type=product';
    }

    return $parent_file;
});

add_filter('submenu_file', function ($submenu_file, $parent_file) {
    if ($parent_file !== 'edit.php?post_type=product') {
        return $submenu_file;
    }

    $brand_slug = isset($_GET['my_theme_brand_filter']) ? sanitize_title((string) $_GET['my_theme_brand_filter']) : '';
    if ($brand_slug !== '') {
        return my_theme_get_product_brand_admin_list_slug($brand_slug);
    }

    $focus = isset($_GET['my_theme_focus']) ? sanitize_key((string) $_GET['my_theme_focus']) : '';
    $taxonomy = isset($_GET['taxonomy']) ? sanitize_key((string) $_GET['taxonomy']) : '';
    if ($focus === 'add' && $taxonomy !== '' && $taxonomy === my_theme_get_product_brand_taxonomy()) {
        $add_brand_slug = my_theme_get_product_brand_admin_term_slug('add');
        if ($add_brand_slug !== '') {
            return $add_brand_slug;
        }
    }

    return $submenu_file;
}, 10, 2);

add_action('restrict_manage_posts', function () {
    global $typenow;
    if ($typenow !== 'product' || !my_theme_admin_can_manage_store()) {
        return;
    }

    $add_brand_url = my_theme_get_product_brand_admin_term_url('add');
    if ($add_brand_url === '') {
        return;
    }

    echo '<a class="button" style="margin-left:8px;" href="' . esc_url($add_brand_url) . '">Thêm hãng</a>';
}, 30);

add_action('pre_get_posts', function ($query) {
    if (!is_admin() || !($query instanceof WP_Query) || !$query->is_main_query()) {
        return;
    }

    $post_type = $query->get('post_type');
    if (is_array($post_type)) {
        if (!in_array('product', $post_type, true)) {
            return;
        }
    } elseif ($post_type !== 'product') {
        $requested_post_type = isset($_GET['post_type']) ? sanitize_key((string) $_GET['post_type']) : '';
        if ($requested_post_type !== 'product') {
            return;
        }
    }

    $screen_base = isset($_GET['post_type']) ? sanitize_key((string) $_GET['post_type']) : '';
    if ($screen_base !== '' && $screen_base !== 'product') {
        return;
    }

    $brand_slug = isset($_GET['my_theme_brand_filter']) ? sanitize_title((string) $_GET['my_theme_brand_filter']) : '';
    if ($brand_slug === '') {
        return;
    }

    $brand_taxonomy = my_theme_get_product_brand_taxonomy();
    if ($brand_taxonomy !== '') {
        $term = get_term_by('slug', $brand_slug, $brand_taxonomy);
        if ($term instanceof WP_Term && !empty($term->term_id)) {
            $tax_query = $query->get('tax_query');
            if (!is_array($tax_query)) {
                $tax_query = [];
            }
            $tax_query[] = [
                'taxonomy' => $brand_taxonomy,
                'field'    => 'term_id',
                'terms'    => [(int) $term->term_id],
            ];
            $query->set('tax_query', $tax_query);
            return;
        }
    }

    $product_ids = get_posts([
        'post_type'              => 'product',
        'post_status'            => 'any',
        'posts_per_page'         => -1,
        'fields'                 => 'ids',
        'no_found_rows'          => true,
        'ignore_sticky_posts'    => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ]);
    if (empty($product_ids)) {
        $query->set('post__in', [0]);
        return;
    }

    $matched_ids = [];
    foreach ($product_ids as $product_id) {
        if (sanitize_title((string) my_theme_get_product_brand_slug((int) $product_id)) === $brand_slug) {
            $matched_ids[] = (int) $product_id;
        }
    }

    $existing_post_in = $query->get('post__in');
    if (is_array($existing_post_in) && !empty($existing_post_in)) {
        $matched_ids = array_values(array_intersect(array_map('intval', $existing_post_in), $matched_ids));
    }

    if (empty($matched_ids)) {
        $matched_ids = [0];
    }

    $query->set('post__in', $matched_ids);
    $query->set('orderby', 'post__in');
}, 15);

add_action('admin_footer-edit-tags.php', function () {
    if (!is_admin()) {
        return;
    }

    $focus = isset($_GET['my_theme_focus']) ? sanitize_key((string) $_GET['my_theme_focus']) : '';
    $taxonomy = isset($_GET['taxonomy']) ? sanitize_key((string) $_GET['taxonomy']) : '';
    $post_type = isset($_GET['post_type']) ? sanitize_key((string) $_GET['post_type']) : '';
    if ($focus !== 'add' || $post_type !== 'product' || $taxonomy !== my_theme_get_product_brand_taxonomy()) {
        return;
    }

    echo "<script>window.addEventListener('load',function(){var column=document.getElementById('col-left');if(column){column.scrollIntoView({block:'start'});}var input=document.getElementById('tag-name');if(input){input.focus();}});</script>";
});

add_action('admin_bar_menu', function ($wp_admin_bar) {
    if (!is_admin() || !my_theme_admin_can_manage_store()) {
        return;
    }

    foreach (['wp-logo', 'comments', 'customize', 'search'] as $node_id) {
        $wp_admin_bar->remove_node($node_id);
    }

    $site_name = get_bloginfo('name');
    $site_node = $wp_admin_bar->get_node('site-name');
    if ($site_node) {
        $wp_admin_bar->add_node([
            'id' => 'site-name',
            'title' => esc_html($site_name !== '' ? $site_name : 'Trang web'),
            'href' => home_url('/'),
        ]);
    }

    $wp_admin_bar->remove_node('view-store');
    $wp_admin_bar->add_node([
        'id' => 'my-theme-view-shop',
        'parent' => 'site-name',
        'title' => 'Mở cửa hàng',
        'href' => home_url('/shop/'),
    ]);
    $wp_admin_bar->add_node([
        'id' => 'my-theme-view-leads',
        'parent' => 'site-name',
        'title' => 'Mở lead',
        'href' => admin_url('edit.php?post_type=customer_lead'),
    ]);
    $wp_admin_bar->add_node([
        'id' => 'my-theme-view-catalog-qa',
        'parent' => 'site-name',
        'title' => 'Mở Catalog QA',
        'href' => admin_url('admin.php?page=my-theme-catalog-qa'),
    ]);
}, 90);

add_action('wp_dashboard_setup', function () {
    if (!my_theme_admin_can_manage_store()) {
        return;
    }

    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
    remove_meta_box('dashboard_activity', 'dashboard', 'normal');
    remove_meta_box('dashboard_primary', 'dashboard', 'side');
    remove_meta_box('dashboard_site_health', 'dashboard', 'normal');
    remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
    remove_meta_box('jetpack_summary_widget', 'dashboard', 'normal');
    remove_meta_box('woocommerce_dashboard_status', 'dashboard', 'normal');
}, 99);

add_action('admin_init', function () {
    if (!my_theme_admin_can_manage_store()) {
        return;
    }

    remove_action('welcome_panel', 'wp_welcome_panel');
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

if (!function_exists('my_theme_is_internal_account_email')) {
    function my_theme_is_internal_account_email($email)
    {
        $email = strtolower(trim((string) $email));
        $needle = '@noemail.local';
        return $email !== '' && substr($email, -strlen($needle)) === $needle;
    }
}

if (!function_exists('my_theme_get_account_endpoint_url_safe')) {
    function my_theme_get_account_endpoint_url_safe($endpoint = '', $fallback = '')
    {
        $endpoint = sanitize_title((string) $endpoint);
        if ($endpoint !== '' && function_exists('wc_get_account_endpoint_url')) {
            $url = wc_get_account_endpoint_url($endpoint);
            if (is_string($url) && $url !== '') {
                return $url;
            }
        }

        if ($fallback !== '') {
            return $fallback;
        }

        return function_exists('my_theme_get_account_url') ? my_theme_get_account_url() : home_url('/');
    }
}

if (!function_exists('my_theme_get_customer_account_snapshot')) {
    function my_theme_get_customer_account_snapshot($user_id = 0)
    {
        $user_id = $user_id > 0 ? (int) $user_id : get_current_user_id();
        $user = get_userdata($user_id);
        if (!$user) {
            return [];
        }

        $display_name = trim((string) get_user_meta($user_id, 'billing_first_name', true));
        if ($display_name === '') {
            $display_name = trim((string) $user->display_name);
        }
        if ($display_name === '') {
            $display_name = trim((string) $user->user_login);
        }

        $phone = trim((string) get_user_meta($user_id, 'billing_phone', true));
        $address = trim((string) get_user_meta($user_id, 'billing_address_1', true));
        if ($address === '') {
            $address = trim((string) get_user_meta($user_id, 'shipping_address_1', true));
        }

        $email = trim((string) $user->user_email);
        $has_internal_email = my_theme_is_internal_account_email($email);
        $order_total = 0;

        if (function_exists('wc_get_orders')) {
            $order_query = wc_get_orders([
                'customer_id' => $user_id,
                'limit' => 1,
                'paginate' => true,
                'return' => 'ids',
            ]);
            if (is_object($order_query) && isset($order_query->total)) {
                $order_total = max(0, (int) $order_query->total);
            } elseif (is_array($order_query)) {
                $order_total = count($order_query);
            }
        }

        return [
            'display_name' => $display_name,
            'username' => (string) $user->user_login,
            'phone' => $phone,
            'address' => $address,
            'email' => $has_internal_email ? '' : $email,
            'email_missing' => $has_internal_email || $email === '',
            'order_total' => $order_total,
        ];
    }
}

if (!function_exists('my_theme_render_account_quick_links')) {
    function my_theme_render_account_quick_links($modifier = '')
    {
        $store_snapshot = function_exists('my_theme_get_store_snapshot') ? my_theme_get_store_snapshot() : [];
        $shop_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
        $orders_url = my_theme_get_account_endpoint_url_safe('orders', home_url('/my-account/orders'));
        $address_url = my_theme_get_account_endpoint_url_safe('edit-address', home_url('/my-account/edit-address'));
        $account_url = my_theme_get_account_endpoint_url_safe('edit-account', home_url('/my-account/edit-account'));
        $contact_url = home_url('/lien-he');
        $phone_href = isset($store_snapshot['phone_href']) ? (string) $store_snapshot['phone_href'] : 'tel:0944857999';
        $modifier_class = $modifier !== '' ? ' account-quick-links--' . sanitize_html_class($modifier) : '';

        echo '<div class="account-quick-links' . esc_attr($modifier_class) . '">';
        echo '<a class="account-quick-link" href="' . esc_url($shop_url) . '"><strong>Mua thêm vật tư</strong><span>Xem kho sơn, chống thấm và phụ gia đang có sẵn.</span></a>';
        echo '<a class="account-quick-link" href="' . esc_url($orders_url) . '"><strong>Theo dõi đơn hàng</strong><span>Kiểm tra tiến độ xử lý và lịch sử mua hàng gần đây.</span></a>';
        echo '<a class="account-quick-link" href="' . esc_url($address_url) . '"><strong>Cập nhật địa chỉ</strong><span>Lưu sẵn địa chỉ nhận hàng để chốt đơn nhanh hơn.</span></a>';
        echo '<a class="account-quick-link" href="' . esc_url($account_url) . '"><strong>Hoàn thiện hồ sơ</strong><span>Bổ sung email, đổi mật khẩu và cập nhật thông tin liên hệ.</span></a>';
        echo '<a class="account-quick-link" href="' . esc_url($contact_url) . '"><strong>Nhận hỗ trợ kỹ thuật</strong><span>Gửi mô tả bề mặt để được đề xuất hệ sơn phù hợp.</span></a>';
        echo '<a class="account-quick-link" href="' . esc_url($phone_href) . '"><strong>Gọi chốt báo giá</strong><span>Ưu tiên đơn công trình và hỗ trợ giao nhanh nội thành.</span></a>';
        echo '</div>';
    }
}

add_action('woocommerce_before_account_navigation', function () {
    if (!is_user_logged_in()) {
        return;
    }

    $store_snapshot = function_exists('my_theme_get_store_snapshot') ? my_theme_get_store_snapshot() : [];
    $snapshot = my_theme_get_customer_account_snapshot();
    if (empty($snapshot)) {
        return;
    }

    $address_text = $snapshot['address'] !== '' ? $snapshot['address'] : 'Chưa lưu địa chỉ mặc định';
    $phone_text = $snapshot['phone'] !== '' ? $snapshot['phone'] : 'Chưa cập nhật số điện thoại';
    $email_text = !$snapshot['email_missing'] && $snapshot['email'] !== '' ? $snapshot['email'] : 'Nên cập nhật email để nhận thông báo';
    $order_label = $snapshot['order_total'] > 0 ? number_format_i18n($snapshot['order_total']) . ' đơn đã tạo' : 'Chưa có đơn hàng';

    echo '<div class="account-sidebar-card">';
    echo '<div class="account-sidebar-card__eyebrow">Hồ sơ khách hàng</div>';
    echo '<h2 class="account-sidebar-card__name">' . esc_html($snapshot['display_name']) . '</h2>';
    echo '<div class="account-sidebar-card__meta">';
    echo '<span><strong>Tài khoản:</strong> ' . esc_html($snapshot['username']) . '</span>';
    echo '<span><strong>Số điện thoại:</strong> ' . esc_html($phone_text) . '</span>';
    echo '<span><strong>Email:</strong> ' . esc_html($email_text) . '</span>';
    echo '<span><strong>Địa chỉ:</strong> ' . esc_html($address_text) . '</span>';
    echo '<span><strong>Đơn hàng:</strong> ' . esc_html($order_label) . '</span>';
    echo '</div>';
    echo '<div class="account-sidebar-card__actions">';
    echo '<a class="btn btn-primary btn-sm" href="' . esc_url(isset($store_snapshot['phone_href']) ? (string) $store_snapshot['phone_href'] : 'tel:0944857999') . '">Gọi hỗ trợ</a>';
    echo '<a class="btn btn-outline btn-sm" href="' . esc_url(isset($store_snapshot['zalo_url']) ? (string) $store_snapshot['zalo_url'] : 'https://zalo.me/0944857999') . '" target="_blank" rel="noopener">Zalo kỹ thuật</a>';
    echo '</div>';
    echo '</div>';
}, 5);

add_action('woocommerce_account_dashboard', function () {
    if (!is_user_logged_in()) {
        return;
    }

    $snapshot = my_theme_get_customer_account_snapshot();
    if (empty($snapshot)) {
        return;
    }

    $shop_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
    $orders_url = my_theme_get_account_endpoint_url_safe('orders', home_url('/my-account/orders'));
    $account_url = my_theme_get_account_endpoint_url_safe('edit-account', home_url('/my-account/edit-account'));
    $order_total = max(0, (int) $snapshot['order_total']);

    echo '<section class="account-dashboard-panel account-dashboard-panel--compact">';
    echo '<div class="account-section-intro__header">';
    echo '<div>';
    echo '<div class="account-section-intro__eyebrow">Tổng quan tài khoản</div>';
    echo '<h2 class="account-section-intro__title">Tài khoản của bạn</h2>';
    echo '<p class="account-section-intro__sub">Bạn đã có ' . esc_html(number_format_i18n($order_total)) . ' đơn hàng. Chọn nhanh đúng mục để xử lý tiếp.</p>';
    echo '</div>';
    echo '<div class="account-dashboard-actions">';
    echo '<a class="btn btn-outline btn-sm" href="' . esc_url($orders_url) . '">Đơn hàng</a>';
    echo '<a class="btn btn-outline btn-sm" href="' . esc_url($account_url) . '">Thông tin</a>';
    echo '<a class="btn btn-primary btn-sm" href="' . esc_url($shop_url) . '">Mua thêm</a>';
    echo '</div>';
    echo '</div>';
    echo '</section>';
}, 5);

add_action('woocommerce_account_dashboard', function () {
    if (!is_user_logged_in()) {
        return;
    }
    // Giữ dashboard tài khoản gọn và tập trung vào điều hướng chính.
    return;
}, 25);

add_action('woocommerce_before_account_orders', function ($has_orders = false) {
    if (!is_user_logged_in()) {
        return;
    }

    echo '<section class="account-section-intro account-section-intro--orders">';
    echo '<div class="account-section-intro__header">';
    echo '<div>';
    echo '<div class="account-section-intro__eyebrow">Theo dõi đơn hàng</div>';
    echo '<h2 class="account-section-intro__title">Kiểm tra tiến độ xử lý và lịch sử mua hàng</h2>';
    echo '<p class="account-section-intro__sub">' . ($has_orders ? 'Mỗi đơn đều có trạng thái để bạn dễ theo dõi xử lý, giao hàng và thanh toán.' : 'Bạn chưa có đơn trong tài khoản này. Có thể bắt đầu từ kho sản phẩm hoặc gửi yêu cầu báo giá theo công trình.') . '</p>';
    echo '</div>';
    echo '<a class="btn btn-outline btn-sm" href="' . esc_url(function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop')) . '">Duyệt sản phẩm</a>';
    echo '</div>';
    echo '</section>';
}, 5, 1);

if (!function_exists('my_theme_render_account_downloads_intro')) {
    function my_theme_render_account_downloads_intro()
    {
        static $rendered = false;

        if ($rendered || !is_user_logged_in()) {
            return;
        }
        $rendered = true;

        echo '<section class="account-section-intro account-section-intro--downloads">';
        echo '<div class="account-section-intro__header">';
        echo '<div>';
        echo '<div class="account-section-intro__eyebrow">Tệp kỹ thuật</div>';
        echo '<h2 class="account-section-intro__title">Tập trung các tài liệu tải xuống sau khi mua</h2>';
        echo '<p class="account-section-intro__sub">Khi đơn hàng có tài liệu kèm theo, bạn sẽ thấy bảng tải xuống tại đây cùng thời hạn sử dụng.</p>';
        echo '</div>';
        echo '<a class="btn btn-outline btn-sm" href="' . esc_url(home_url('/lien-he')) . '">Yêu cầu tài liệu</a>';
        echo '</div>';
        echo '</section>';
    }
}
add_action('woocommerce_before_available_downloads', 'my_theme_render_account_downloads_intro', 5);
add_action('woocommerce_before_account_downloads', 'my_theme_render_account_downloads_intro', 5);

add_action('woocommerce_before_edit_account_address_form', function () {
    if (!is_user_logged_in()) {
        return;
    }

    echo '<section class="account-section-intro account-section-intro--address">';
    echo '<div class="account-section-intro__header">';
    echo '<div>';
    echo '<div class="account-section-intro__eyebrow">Địa chỉ giao hàng</div>';
    echo '<h2 class="account-section-intro__title">Lưu sẵn thông tin để checkout gọn hơn</h2>';
    echo '<p class="account-section-intro__sub">Ưu tiên cập nhật đầy đủ họ tên, số điện thoại và địa chỉ thực tế để đội giao hàng liên hệ chính xác.</p>';
    echo '</div>';
    echo '<a class="btn btn-outline btn-sm" href="' . esc_url(my_theme_get_account_endpoint_url_safe('orders', home_url('/my-account/orders'))) . '">Xem đơn hàng</a>';
    echo '</div>';
    echo '</section>';
}, 5);

add_action('woocommerce_before_edit_account_form', function () {
    if (!is_user_logged_in()) {
        return;
    }

    echo '<section class="account-section-intro account-section-intro--profile">';
    echo '<div class="account-section-intro__header">';
    echo '<div>';
    echo '<div class="account-section-intro__eyebrow">Thông tin tài khoản</div>';
    echo '<h2 class="account-section-intro__title">Giữ hồ sơ luôn sẵn sàng cho lần đặt tiếp theo</h2>';
    echo '<p class="account-section-intro__sub">Cập nhật tên hiển thị, email nhận thông báo và thay đổi mật khẩu khi cần để quản lý đơn hàng an toàn hơn.</p>';
    echo '</div>';
    echo '<a class="btn btn-outline btn-sm" href="' . esc_url(home_url('/lien-he')) . '">Nhờ hỗ trợ</a>';
    echo '</div>';
    echo '</section>';
}, 5);

if (!function_exists('my_theme_render_account_recovery_intro')) {
    function my_theme_render_account_recovery_intro()
    {
        if (!function_exists('is_wc_endpoint_url') || !is_wc_endpoint_url('lost-password')) {
            return;
        }

        $store_snapshot = function_exists('my_theme_get_store_snapshot') ? my_theme_get_store_snapshot() : [];
        $account_login_url = function_exists('my_theme_get_account_login_url') ? my_theme_get_account_login_url() : wp_login_url();

        echo '<section class="account-recovery-shell">';
        echo '<div class="account-recovery-shell__header">';
        echo '<div>';
        echo '<div class="account-recovery-shell__eyebrow">Khôi phục tài khoản</div>';
        echo '<h2 class="account-recovery-shell__title">Lấy lại quyền truy cập nhanh, không cần gọi nhiều lần</h2>';
        echo '<p class="account-recovery-shell__sub">Nhập tên đăng nhập hoặc email đã dùng trước đó. Hệ thống sẽ gửi liên kết tạo lại mật khẩu để bạn tiếp tục theo dõi đơn hàng.</p>';
        echo '</div>';
        echo '<a class="btn btn-outline btn-sm" href="' . esc_url($account_login_url) . '">Quay lại đăng nhập</a>';
        echo '</div>';
        echo '<div class="account-recovery-shell__grid">';
        echo '<article class="account-recovery-card"><strong>Không nhớ email?</strong><span>Thử tên đăng nhập hoặc liên hệ số đã từng dùng để đặt hàng.</span></article>';
        echo '<article class="account-recovery-card"><strong>Cần chốt đơn gấp?</strong><span>Đội kỹ thuật vẫn có thể hỗ trợ báo giá và xác nhận vật tư qua điện thoại.</span></article>';
        echo '<article class="account-recovery-card"><strong>Muốn tạo tài khoản mới?</strong><span>Trang đăng ký vẫn mở để bạn khởi tạo hồ sơ nhận hàng mới.</span></article>';
        echo '</div>';
        echo '<div class="account-recovery-shell__actions">';
        echo '<a class="btn btn-primary btn-sm" href="' . esc_url(isset($store_snapshot['phone_href']) ? (string) $store_snapshot['phone_href'] : 'tel:0944857999') . '">Gọi ngay</a>';
        echo '<a class="btn btn-outline btn-sm" href="' . esc_url(isset($store_snapshot['zalo_url']) ? (string) $store_snapshot['zalo_url'] : 'https://zalo.me/0944857999') . '" target="_blank" rel="noopener">Zalo kỹ thuật</a>';
        echo '<a class="btn btn-accent btn-sm" href="' . esc_url(home_url('/lien-he')) . '">Gửi yêu cầu</a>';
        echo '</div>';
        echo '</section>';
    }
}
add_action('woocommerce_before_lost_password_form', 'my_theme_render_account_recovery_intro', 5);

if (!function_exists('my_theme_render_checkout_thankyou_panel')) {
    function my_theme_render_checkout_thankyou_panel($order_id = 0)
    {
        static $rendered = false;

        if ($rendered) {
            return;
        }
        if (!function_exists('is_order_received_page') || !is_order_received_page()) {
            return;
        }
        $rendered = true;

        $order = ($order_id && function_exists('wc_get_order')) ? wc_get_order($order_id) : null;
        $order_number = $order ? $order->get_order_number() : '';
        $payment_method = $order ? trim(wp_strip_all_tags((string) $order->get_payment_method_title())) : '';
        $shop_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
        $account_url = function_exists('my_theme_get_account_url') ? my_theme_get_account_url() : home_url('/my-account');
        $store_snapshot = function_exists('my_theme_get_store_snapshot') ? my_theme_get_store_snapshot() : [];
        $hours_display = isset($store_snapshot['hours_display']) ? (string) $store_snapshot['hours_display'] : 'Thứ 2 - Thứ 7: 7:30 - 18:00';
        $service_areas = isset($store_snapshot['service_areas_display']) ? (string) $store_snapshot['service_areas_display'] : 'TP.HCM, Bình Dương, Đồng Nai';
        $phone_href = isset($store_snapshot['phone_href']) ? (string) $store_snapshot['phone_href'] : 'tel:0944857999';
        $phone_display = isset($store_snapshot['phone_display']) ? (string) $store_snapshot['phone_display'] : '0944 857 999';
        $zalo_url = isset($store_snapshot['zalo_url']) ? (string) $store_snapshot['zalo_url'] : 'https://zalo.me/0944857999';

        echo '<section class="page-section order-complete-panel" aria-label="Hướng dẫn sau khi đặt hàng">';
        echo '<div class="section-heading">';
        echo '<div>';
        echo '<div class="section-kicker">Sau khi đặt hàng</div>';
        echo '<h2 class="section-title">Bước tiếp theo để đơn được xử lý nhanh hơn</h2>';
        if ($order_number !== '') {
            $detail = 'Mã đơn #' . $order_number . ' đã được ghi nhận.';
            if ($payment_method !== '') {
                $detail .= ' Hình thức thanh toán: ' . $payment_method . '.';
            }
            echo '<p class="section-sub">' . esc_html($detail) . '</p>';
        } else {
            echo '<p class="section-sub">Nếu bạn vừa gửi đơn, đội vận hành sẽ tiếp nhận và xác nhận lại thông tin giao hàng trong giờ làm việc.</p>';
        }
        echo '</div>';
        echo '<a class="btn btn-outline btn-sm" href="' . esc_url($account_url) . '">Mở tài khoản</a>';
        echo '</div>';
        echo '<div class="shop-summary__insight" aria-label="Thông tin hỗ trợ sau khi đặt hàng">';
        echo '<span class="chip chip--soft">' . esc_html($hours_display) . '</span>';
        echo '<span class="chip chip--soft">' . esc_html($service_areas) . '</span>';
        echo '<span class="chip chip--soft">Giữ máy để đội vận hành xác nhận đơn</span>';
        echo '</div>';
        echo '<div class="order-complete-grid">';
        echo '<article class="info-card"><h3>1. Theo dõi đơn hàng</h3><p>Kiểm tra trạng thái xử lý trong tài khoản để biết khi nào đơn được chốt và chuyển giao.</p></article>';
        echo '<article class="info-card"><h3>2. Chuẩn bị nhận hàng</h3><p>Giữ điện thoại sẵn sàng, xác nhận lại địa chỉ và người nhận để xe giao hàng liên hệ nhanh hơn.</p></article>';
        echo '<article class="info-card"><h3>3. Cần đổi vật tư?</h3><p>Nếu cần điều chỉnh dung tích, số lượng hoặc hệ sơn, liên hệ ngay trước khi đơn chuyển sang giao hàng.</p></article>';
        echo '</div>';
        echo '<div class="cta-inline order-complete-panel__cta">';
        echo '<div class="cta-inline__content"><strong>Cần chốt lại đơn ngay?</strong><p>Đội kỹ thuật hỗ trợ đổi quy cách, bổ sung vật tư và xác nhận tiến độ giao trong giờ hành chính.</p></div>';
        echo '<div class="cta-inline__actions">';
        echo '<a class="btn btn-primary btn-sm" href="' . esc_url($phone_href) . '">Gọi ' . esc_html($phone_display) . '</a>';
        echo '<a class="btn btn-outline btn-sm" href="' . esc_url($zalo_url) . '" target="_blank" rel="noopener">Nhắn Zalo</a>';
        echo '<a class="btn btn-accent btn-sm" href="' . esc_url($shop_url) . '">Mua thêm vật tư</a>';
        echo '</div>';
        echo '</div>';
        echo '</section>';
    }
}
add_action('woocommerce_thankyou', 'my_theme_render_checkout_thankyou_panel', 20);
add_action('woocommerce_thankyou', function ($order_id = 0) {
    if (!function_exists('my_theme_render_recently_viewed_products')) {
        return;
    }

    $exclude_ids = [];
    $order = ($order_id && function_exists('wc_get_order')) ? wc_get_order($order_id) : null;
    if ($order instanceof WC_Order) {
        foreach ($order->get_items() as $item) {
            if (!$item instanceof WC_Order_Item_Product) {
                continue;
            }
            $product_id = (int) $item->get_product_id();
            if ($product_id > 0) {
                $exclude_ids[] = $product_id;
            }
        }
    }

    my_theme_render_recently_viewed_products([
        'title' => 'Các mã bạn vừa xem trước khi chốt đơn',
        'aria_label' => 'Các mã bạn vừa xem trước khi chốt đơn',
        'class' => 'related-products-block--recently-viewed related-products-block--thankyou',
        'exclude_ids' => array_values(array_unique($exclude_ids)),
    ]);
}, 25);

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
        'id' => '_display_pack_list',
        'label' => 'Quy cách hiển thị',
        'placeholder' => 'Cuộn 1m x 20m | Bộ 18L (A 14.4L + B 3.6L)',
        'desc_tip' => true,
        'description' => 'Dùng cho quy cách không nên ép sang dung tích/khối lượng như cuộn, bộ 2 thành phần, ống/xúc xích.',
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
    $pack = isset($_POST['_display_pack_list']) ? wc_clean(wp_unslash($_POST['_display_pack_list'])) : '';
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
    if ($pack !== '') {
        $product->update_meta_data('_display_pack_list', $pack);
    } else {
        $product->delete_meta_data('_display_pack_list');
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

if (!function_exists('my_theme_get_product_term_objects')) {
    function my_theme_get_product_term_objects($product_id, $taxonomy = '')
    {
        static $cache = [];

        $product_id = (int) $product_id;
        $taxonomy = sanitize_key((string) $taxonomy);
        $cache_key = $product_id . ':' . $taxonomy;
        if (array_key_exists($cache_key, $cache)) {
            return $cache[$cache_key];
        }

        if ($product_id <= 0 || $taxonomy === '' || !taxonomy_exists($taxonomy)) {
            $cache[$cache_key] = [];
            return [];
        }

        $terms = get_the_terms($product_id, $taxonomy);
        if (is_wp_error($terms) || empty($terms) || !is_array($terms)) {
            $cache[$cache_key] = [];
            return [];
        }

        $valid_terms = [];
        foreach ($terms as $term) {
            if ($term instanceof WP_Term) {
                $valid_terms[] = $term;
            }
        }

        $cache[$cache_key] = $valid_terms;
        return $valid_terms;
    }
}

if (!function_exists('my_theme_get_product_term_values')) {
    function my_theme_get_product_term_values($product_id, $taxonomy = '', $field = 'slug')
    {
        static $cache = [];

        $product_id = (int) $product_id;
        $taxonomy = sanitize_key((string) $taxonomy);
        $field = ($field === 'name') ? 'name' : 'slug';
        $cache_key = $product_id . ':' . $taxonomy . ':' . $field;
        if (array_key_exists($cache_key, $cache)) {
            return $cache[$cache_key];
        }

        $terms = my_theme_get_product_term_objects($product_id, $taxonomy);
        if (empty($terms)) {
            $cache[$cache_key] = [];
            return [];
        }

        $values = [];
        foreach ($terms as $term) {
            if (!$term instanceof WP_Term) {
                continue;
            }

            if ($field === 'name') {
                $value = trim(wp_strip_all_tags((string) $term->name));
            } else {
                $value = sanitize_title((string) $term->slug);
            }

            if ($value === '') {
                continue;
            }

            $values[$value] = $value;
        }

        $cache[$cache_key] = array_values($values);
        return $cache[$cache_key];
    }
}

// Lấy giá trị attribute/meta cho dung tích và khối lượng (ưu tiên attribute).
function my_theme_extract_attr_values($product, $slugs) {
    $values = [];
    $product_id = ($product instanceof WC_Product) ? (int) $product->get_id() : 0;
    foreach ($slugs as $slug) {
        // Lấy terms taxonomy nếu có
        if ($product_id > 0 && taxonomy_exists($slug)) {
            $terms = my_theme_get_product_term_values($product_id, $slug, 'name');
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

if (!function_exists('my_theme_normalize_pack_container')) {
    function my_theme_normalize_pack_container($raw_container = '')
    {
        $value = strtolower(remove_accents(trim((string) $raw_container)));
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/[^a-z]+/', '-', $value);
        $value = trim((string) $value, '-');

        $to_chai = ['chai', 'tube', 'cartridge', 'ong', 'tuyp', 'tup'];
        $to_sausage = ['sausage', 'xuc-xich', 'xucxich'];

        if (in_array($value, $to_chai, true)) {
            return 'chai';
        }
        if (in_array($value, $to_sausage, true)) {
            return 'sausage';
        }

        return '';
    }
}

function my_theme_parse_pack_label($raw_label) {
    $label = trim(wp_strip_all_tags((string) $raw_label));
    if ($label === '') {
        return null;
    }

    $ascii = strtolower(remove_accents($label));
    $ascii = preg_replace('/\s+/', ' ', trim((string) $ascii));
    if ($ascii === '') {
        return null;
    }

    // Parse expressions like 24x300ml / 300ml x 24 as package-level labels.
    if (preg_match('/(\d+)\s*(?:x|\*)\s*(\d+(?:[.,]\d+)?)\s*(ml|l|lit|liter|litre)\b(?:\s*(chai|sausage|tube|cartridge|ong|tuyp|tup))?/i', $ascii, $m)) {
        $qty = (int) $m[1];
        if ($qty > 1) {
            $item = my_theme_normalize_pack_container($m[4] ?? 'chai');
            if ($item === '') {
                $item = 'chai';
            }
            return [
                'value' => (float) $qty,
                'unit'  => 'pack',
                'label' => 'Thùng ' . $qty . ' ' . $item,
            ];
        }
    }
    if (preg_match('/(\d+(?:[.,]\d+)?)\s*(ml|l|lit|liter|litre)\s*(?:x|\*)\s*(\d+)\b(?:\s*(chai|sausage|tube|cartridge|ong|tuyp|tup))?/i', $ascii, $m)) {
        $qty = (int) $m[3];
        if ($qty > 1) {
            $item = my_theme_normalize_pack_container($m[4] ?? 'chai');
            if ($item === '') {
                $item = 'chai';
            }
            return [
                'value' => (float) $qty,
                'unit'  => 'pack',
                'label' => 'Thùng ' . $qty . ' ' . $item,
            ];
        }
    }

    if (preg_match('/(\d+(?:[.,]\d+)?)\s*(kg|ml|l|lit|liter|litre)\b(?:\s*\/\s*(chai|sausage|tube|cartridge|ong|tuyp|tup))?/i', $ascii, $m)) {
        $value = (float) str_replace(',', '.', (string) $m[1]);
        if ($value <= 0) {
            return null;
        }

        $unit_raw = strtolower((string) $m[2]);
        $container_raw = my_theme_normalize_pack_container($m[3] ?? '');
        $value_text = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');

        if ($unit_raw === 'kg') {
            return [
                'value' => $value,
                'unit'  => 'kg',
                'label' => $value_text . 'kg',
            ];
        }

        if ($unit_raw === 'ml') {
            $label_text = $value_text . 'ml';
            if ($container_raw === 'chai') {
                $label_text .= '/chai';
            } elseif ($container_raw === 'sausage') {
                $label_text .= '/sausage';
            }
            return [
                'value' => $value,
                'unit'  => 'ml',
                'label' => $label_text,
            ];
        }

        $label_text = $value_text . 'L';
        if ($container_raw === 'chai') {
            $label_text .= '/chai';
        } elseif ($container_raw === 'sausage') {
            $label_text .= '/sausage';
        }
        return [
            'value' => $value,
            'unit'  => 'L',
            'label' => $label_text,
        ];
    }

    if (
        preg_match('/(?:thung|carton|hop)\s*(\d+)\s*(chai|sausage|tube|cartridge|ong|tuyp|tup)\b/i', $ascii, $m) ||
        preg_match('/(\d+)\s*(chai|sausage|tube|cartridge|ong|tuyp|tup)\s*\/\s*(?:thung|carton|hop)\b/i', $ascii, $m)
    ) {
        $qty = (int) $m[1];
        if ($qty <= 0) {
            return null;
        }
        $item = my_theme_normalize_pack_container($m[2] ?? '');
        if ($item === '') {
            $item = 'chai';
        }
        return [
            'value' => (float) $qty,
            'unit'  => 'pack',
            'label' => 'Thùng ' . $qty . ' ' . $item,
        ];
    }

    return null;
}

function my_theme_get_pack_unit_rank($unit) {
    $unit = (string) $unit;
    if ($unit === 'ml' || $unit === 'L') {
        return 0;
    }
    if ($unit === 'kg') {
        return 1;
    }
    if ($unit === 'pack') {
        return 2;
    }
    return 9;
}

function my_theme_get_pack_sort_value($parsed_pack) {
    if (!is_array($parsed_pack)) {
        return 0.0;
    }
    $value = isset($parsed_pack['value']) ? (float) $parsed_pack['value'] : 0.0;
    $unit = isset($parsed_pack['unit']) ? (string) $parsed_pack['unit'] : '';
    if ($unit === 'L') {
        return $value * 1000;
    }
    return $value;
}

function my_theme_compare_parsed_pack($a, $b) {
    $ra = my_theme_get_pack_unit_rank($a['unit'] ?? '');
    $rb = my_theme_get_pack_unit_rank($b['unit'] ?? '');
    if ($ra !== $rb) {
        return ($ra < $rb) ? -1 : 1;
    }

    $va = my_theme_get_pack_sort_value($a);
    $vb = my_theme_get_pack_sort_value($b);
    if ((float) $va === (float) $vb) {
        return strcmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
    }
    return ((float) $va < (float) $vb) ? -1 : 1;
}

function my_theme_compare_pack_labels($a, $b) {
    $pa = my_theme_parse_pack_label($a);
    $pb = my_theme_parse_pack_label($b);
    if (!$pa || !$pb) {
        return strcmp((string) $a, (string) $b);
    }
    return my_theme_compare_parsed_pack($pa, $pb);
}

function my_theme_sort_pack_labels($labels, $unit_filter = '') {
    $rows = [];
    foreach ((array) $labels as $raw_label) {
        $parsed = my_theme_parse_pack_label($raw_label);
        if (!$parsed) {
            continue;
        }
        if ($unit_filter !== '') {
            if ($unit_filter === 'L') {
                if (!in_array($parsed['unit'], ['L', 'ml'], true)) {
                    continue;
                }
            } elseif ($parsed['unit'] !== $unit_filter) {
                continue;
            }
        }
        $rows[$parsed['label']] = $parsed;
    }

    if (empty($rows)) {
        return [];
    }

    $rows = array_values($rows);
    usort($rows, function ($a, $b) {
        return my_theme_compare_parsed_pack($a, $b);
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
        $container_tokens = '(?:chai|sausage|tube|cartridge|ong|tuyp|tup)';
        $pack_tokens = '(?:thung|carton|hop)';
        $patterns = [
            // e.g. 5L: 592,000 | 300ml - 45,000 | 5kg 125,000
            '/(\d+(?:[.,]\d+)?)\s*(ml|l|lit|liter|litre|kg)\s*[:\-]?\s*([\d][\d\.,]{3,})/i',
            // e.g. 592,000d / 5L or 45,000 vnd / 300ml
            '/([\d][\d\.,]{3,})\s*(?:d|vnd|dong)\s*(?:\/|cho|for)?\s*(\d+(?:[.,]\d+)?)\s*(ml|l|lit|liter|litre|kg)\b/i',
            // e.g. Thung 25 chai: 1,250,000
            '/' . $pack_tokens . '\s*(\d+)\s*(' . $container_tokens . ')\s*[:\-]?\s*([\d][\d\.,]{3,})/i',
            // e.g. 1,250,000d / thung 25 chai
            '/([\d][\d\.,]{3,})\s*(?:d|vnd|dong)\s*(?:\/|cho|for)?\s*' . $pack_tokens . '\s*(\d+)\s*(' . $container_tokens . ')\b/i',
            // e.g. 24x300ml: 1,250,000
            '/(\d+)\s*(?:x|\*)\s*(\d+(?:[.,]\d+)?)\s*(ml|l|lit|liter|litre)\s*[:\-]?\s*([\d][\d\.,]{3,})/i',
            // e.g. 1,250,000d / 24x300ml
            '/([\d][\d\.,]{3,})\s*(?:d|vnd|dong)\s*(?:\/|cho|for)?\s*(\d+)\s*(?:x|\*)\s*(\d+(?:[.,]\d+)?)\s*(ml|l|lit|liter|litre)\b/i',
        ];

        foreach ($patterns as $idx => $pattern) {
            if (!preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
                continue;
            }

            foreach ($matches as $row) {
                if (!is_array($row) || count($row) < 4) {
                    continue;
                }

                if ($idx === 0) {
                    $pack_raw = $row[1] . $row[2];
                    $price_raw = $row[3];
                } elseif ($idx === 1) {
                    $pack_raw = $row[2] . $row[3];
                    $price_raw = $row[1];
                } elseif ($idx === 2) {
                    $pack_raw = 'thung ' . $row[1] . ' ' . $row[2];
                    $price_raw = $row[3];
                } elseif ($idx === 3) {
                    $pack_raw = 'thung ' . $row[2] . ' ' . $row[3];
                    $price_raw = $row[1];
                } elseif ($idx === 4) {
                    $pack_raw = $row[1] . 'x' . $row[2] . $row[3];
                    $price_raw = $row[4];
                } else {
                    $pack_raw = $row[2] . 'x' . $row[3] . $row[4];
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
            return my_theme_compare_pack_labels($a, $b);
        });

        return $map;
    }
}

if (!function_exists('my_theme_extract_pack_price_map_from_source_html')) {
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
                    $unit = 'L';
                    $normalized_volume = $volume;
                    if ($is_putty) {
                        $unit = 'kg';
                    } elseif ($volume > 20) {
                        // Some sources store cartridge volume as 300/750 (ml) instead of 0.3/0.75 (L).
                        $unit = 'ml';
                    }
                    $normalized_text = rtrim(rtrim(number_format($normalized_volume, 2, '.', ''), '0'), '.');
                    $label = $normalized_text . $unit;
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
            return my_theme_compare_pack_labels($a, $b);
        });

        return $map;
    }
}

if (!function_exists('my_theme_extract_offer_price_list_from_source_html')) {
    function my_theme_extract_offer_price_list_from_source_html($raw_html)
    {
        $html = (string) $raw_html;
        if ($html === '') {
            return [];
        }

        $prices = [];
        if (preg_match_all('/<script[^>]*type=(["\'])application\/ld\+json\1[^>]*>([\s\S]*?)<\/script>/i', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $row) {
                $json = isset($row[2]) ? trim((string) $row[2]) : '';
                if ($json === '') {
                    continue;
                }

                $decoded = json_decode($json, true);
                if (!is_array($decoded)) {
                    continue;
                }

                $stack = [$decoded];
                while (!empty($stack)) {
                    $node = array_pop($stack);
                    if (!is_array($node)) {
                        continue;
                    }

                    if (isset($node['price'])) {
                        $digits = preg_replace('/\D+/', '', (string) $node['price']);
                        if ($digits !== '' && (float) $digits > 0) {
                            $prices[] = (float) $digits;
                        }
                    }

                    foreach ($node as $child) {
                        if (is_array($child)) {
                            $stack[] = $child;
                        }
                    }
                }
            }
        }

        if (empty($prices) && preg_match_all('/"price"\s*:\s*"?(\\d+(?:\\.\\d+)?)"?/i', $html, $matches)) {
            foreach ((array) ($matches[1] ?? []) as $raw_price) {
                $digits = preg_replace('/\D+/', '', (string) $raw_price);
                if ($digits !== '' && (float) $digits > 0) {
                    $prices[] = (float) $digits;
                }
            }
        }

        $prices = array_values(array_unique(array_map('floatval', $prices)));
        sort($prices, SORT_NUMERIC);

        return $prices;
    }
}

if (!function_exists('my_theme_fetch_pack_price_map_from_source_url')) {
    function my_theme_fetch_pack_price_map_from_source_url($source_url, $is_putty = false, $force_refresh = false)
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
        if (
            stripos($host, 'dulux.vn') === false &&
            stripos($host, 'akzonobel.com') === false &&
            stripos($host, 'vn.weber') === false &&
            stripos($host, 'saint-gobain') === false
        ) {
            return [];
        }

        $cache_key = 'my_theme_src_price_' . md5($url . ($is_putty ? '|kg' : '|all'));
        $cached = $force_refresh ? false : get_transient($cache_key);
        if (is_array($cached) && !empty($cached)) {
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
        set_transient($cache_key, $map, !empty($map) ? (24 * HOUR_IN_SECONDS) : (6 * HOUR_IN_SECONDS));
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
        return my_theme_compare_pack_labels($a, $b);
    });

    return $map;
}

function my_theme_get_product_pack_groups($product) {
    if (!$product instanceof WC_Product) {
        return ['capacity' => [], 'weight' => [], 'package' => [], 'is_putty' => false];
    }

    static $cache = [];
    $cache_key = (int) $product->get_id();
    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }

    $capacity_slugs = ['pa_dung-tich', 'pa_dung_tich', 'pa_dungtich', 'dung-tich', 'dung_tich', 'dungtich'];
    $weight_slugs   = ['pa_khoi-luong', 'pa_khoi_luong', 'pa_khoiluong', 'khoi-luong', 'khoi_luong', 'khoiluong', 'trong-luong', 'trong_luong', 'trongluong'];
    $package_values = [];

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

    $package_raw = $product->get_meta('_display_pack_list');
    if ($package_raw) {
        $package_raw = str_replace([';', "\n"], '|', $package_raw);
        $parts = preg_split('/[|]/', $package_raw);
        foreach ((array) $parts as $part) {
            $part = trim((string) $part);
            if ($part !== '') {
                $package_values[] = $part;
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
    $package = my_theme_sort_pack_labels($raw_labels, 'pack');
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
            if ($is_putty || !empty($weight)) {
                $capacity = [];
            }
        } elseif (my_theme_slug_list_has_any($category_slugs, $liter_priority_categories) && !empty($capacity)) {
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
        if (count($package) > 4) {
            $package = array_slice($package, 0, 4);
        }
    }

    $cache[$cache_key] = [
        'capacity' => $capacity,
        'weight'   => $weight,
        'package'  => array_values(array_unique(array_merge($package, $package_values))),
        'is_putty' => $is_putty,
    ];

    return $cache[$cache_key];
}

function my_theme_get_pack_price_map_for_display($product, $apply_global_sale = true) {
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
        return my_theme_compare_pack_labels($a, $b);
    });

    if ($apply_global_sale && my_theme_global_sale_is_enabled()) {
        foreach ($map as $label => $price_value) {
            $discounted_price = my_theme_get_global_sale_price_from_regular((float) $price_value);
            if ($discounted_price > 0 && $discounted_price < (float) $price_value) {
                $map[$label] = (float) $discounted_price;
            }
        }
    }

    return $map;
}

if (!function_exists('my_theme_get_pack_price_maps')) {
    function my_theme_get_pack_price_maps($product)
    {
        if (!$product instanceof WC_Product) {
            return [
                'raw' => [],
                'display' => [],
            ];
        }

        static $cache = [];

        $product_id = (int) $product->get_id();
        $sale_signature = my_theme_global_sale_is_enabled()
            ? ('1:' . number_format(my_theme_get_global_sale_percent(), 4, '.', ''))
            : '0:0';
        $cache_key = ($product_id > 0 ? ('id:' . $product_id) : ('obj:' . spl_object_hash($product))) . ':' . $sale_signature;
        if (isset($cache[$cache_key]) && is_array($cache[$cache_key])) {
            return $cache[$cache_key];
        }

        $raw_map = my_theme_get_pack_price_map_for_display($product, false);
        $display_map = $raw_map;
        if (!empty($display_map) && my_theme_global_sale_is_enabled()) {
            foreach ($display_map as $label => $price_value) {
                $discounted_price = my_theme_get_global_sale_price_from_regular((float) $price_value);
                if ($discounted_price > 0 && $discounted_price < (float) $price_value) {
                    $display_map[$label] = (float) $discounted_price;
                }
            }
        }

        $cache[$cache_key] = [
            'raw' => $raw_map,
            'display' => $display_map,
        ];

        return $cache[$cache_key];
    }
}

if (!function_exists('my_theme_get_pack_price_display_map')) {
    function my_theme_get_pack_price_display_map($product)
    {
        if (!$product instanceof WC_Product) {
            return [];
        }

        $price_maps = function_exists('my_theme_get_pack_price_maps')
            ? my_theme_get_pack_price_maps($product)
            : ['display' => my_theme_get_pack_price_map_for_display($product, true)];

        return (isset($price_maps['display']) && is_array($price_maps['display']))
            ? $price_maps['display']
            : [];
    }
}

if (!function_exists('my_theme_get_pack_price_raw_map')) {
    function my_theme_get_pack_price_raw_map($product)
    {
        if (!$product instanceof WC_Product) {
            return [];
        }

        $price_maps = function_exists('my_theme_get_pack_price_maps')
            ? my_theme_get_pack_price_maps($product)
            : ['raw' => my_theme_get_pack_price_map_for_display($product, false)];

        return (isset($price_maps['raw']) && is_array($price_maps['raw']))
            ? $price_maps['raw']
            : [];
    }
}

if (!function_exists('my_theme_product_has_active_sale')) {
    function my_theme_product_has_active_sale($product)
    {
        if (!$product instanceof WC_Product) {
            return false;
        }

        $default_pack_context = function_exists('my_theme_get_default_selected_capacity_price_context')
            ? my_theme_get_default_selected_capacity_price_context($product)
            : ['capacity' => '', 'price' => 0.0, 'regular_price' => 0.0];
        if (
            !empty($default_pack_context['capacity'])
            && (float) ($default_pack_context['regular_price'] ?? 0) > (float) ($default_pack_context['price'] ?? 0)
            && (float) ($default_pack_context['price'] ?? 0) > 0
        ) {
            return true;
        }

        $regular_price = my_theme_get_product_raw_regular_price($product);
        $sale_price = my_theme_get_product_effective_sale_price($product);

        return $regular_price > 0 && $sale_price > 0 && $sale_price < $regular_price;
    }
}

if (!function_exists('my_theme_get_effective_sale_product_ids')) {
    function my_theme_get_effective_sale_product_ids(array $candidate_ids = [])
    {
        static $request_cache = [];

        if (empty($candidate_ids)) {
            $candidate_ids = function_exists('my_theme_get_catalog_visible_product_ids')
                ? my_theme_get_catalog_visible_product_ids(false)
                : [];
        }

        $candidate_ids = array_values(array_filter(array_map('intval', $candidate_ids), function ($id) {
            return $id > 0;
        }));
        if (empty($candidate_ids)) {
            return [];
        }

        $cache_version = (string) get_option('my_theme_filter_cache_version', '1');
        $digest = md5(implode(',', $candidate_ids));
        $request_key = $cache_version . ':' . $digest;
        if (array_key_exists($request_key, $request_cache)) {
            return $request_cache[$request_key];
        }
        $transient_key = 'my_theme_effective_sale_ids_' . $cache_version . '_' . $digest;
        $cached = get_transient($transient_key);
        if (is_array($cached)) {
            $request_cache[$request_key] = array_values(array_unique(array_map('intval', $cached)));
            return $request_cache[$request_key];
        }

        $product_map = function_exists('my_theme_get_product_object_map')
            ? my_theme_get_product_object_map($candidate_ids)
            : [];
        $sale_ids = [];

        foreach ($candidate_ids as $product_id) {
            if (!isset($product_map[$product_id]) || !$product_map[$product_id] instanceof WC_Product) {
                continue;
            }

            if (my_theme_product_has_active_sale($product_map[$product_id])) {
                $sale_ids[] = $product_id;
            }
        }

        $sale_ids = array_values(array_unique(array_map('intval', $sale_ids)));
        $request_cache[$request_key] = $sale_ids;
        set_transient($transient_key, $sale_ids, 30 * MINUTE_IN_SECONDS);
        return $sale_ids;
    }
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

function my_theme_get_package_options($product) {
    $groups = my_theme_get_product_pack_groups($product);
    return $groups['package'];
}

if (!function_exists('my_theme_get_brand_keyword_map')) {
    function my_theme_get_brand_keyword_map()
    {
        return [
            'dulux'    => ['label' => 'Dulux', 'aliases' => ['dulux']],
            'maxilite' => ['label' => 'Maxilite', 'aliases' => ['maxilite']],
            'weber'    => ['label' => 'Weber', 'aliases' => ['weber', 'webertec', 'webertai', 'weberdry', 'weberseal', 'webercolor']],
            'jotun'    => ['label' => 'Jotun', 'aliases' => ['jotun', 'jotashield', 'majestic', 'waterguard', 'jotaplast', 'essence', 'jotamastic', 'penguard', 'gardex']],
            'nippon'   => ['label' => 'Nippon', 'aliases' => ['nippon', 'nippon paint', 'odourless', 'odour less', 'weatherbond', 'skim coat', 'vinilex', 'matex', 'bodelac', 'hydroshield']],
            'kova'     => ['label' => 'Kova', 'aliases' => ['kova', 'ct-11a', 'ct11a', 'k-209', 'k209', 'k871', 'k261', 'k-261', 'k-871', 'k5501', 'k-5501']],
            'toa'      => ['label' => 'TOA', 'aliases' => ['toa', 'supershield', 'nanoshield', '4seasons', '4 seasons', 'toa 1000', 'rust tech']],
            'sika'     => ['label' => 'Sika', 'aliases' => ['sika', 'sikatop', 'sikalatex', 'sikafloor', 'sikaceram', 'sikagrout', 'sikaflex', 'sikaguard']],
            'apollo'   => ['label' => 'Apollo', 'aliases' => ['apollo', 'apollo silicone', 'apollo sealant', 'a100', 'a200', 'a300', 'a500', 'a600', 'a68', 'a79', 'pu foam', 'sanitary']],
            'expo'     => ['label' => 'Expo', 'aliases' => ['expo']],
            'insee'    => ['label' => 'Insee', 'aliases' => ['insee']],
        ];
    }
}

if (!function_exists('my_theme_get_brand_label_from_slug')) {
    function my_theme_get_brand_label_from_slug($brand_slug = '')
    {
        $brand_slug = sanitize_title((string) $brand_slug);
        if ($brand_slug === '') {
            return '';
        }

        $map = my_theme_get_brand_keyword_map();
        if (isset($map[$brand_slug]['label']) && trim((string) $map[$brand_slug]['label']) !== '') {
            return (string) $map[$brand_slug]['label'];
        }

        return ucfirst($brand_slug);
    }
}

if (!function_exists('my_theme_normalize_product_id_list')) {
    function my_theme_normalize_product_id_list($product_ids)
    {
        if (!is_array($product_ids) || empty($product_ids)) {
            return [];
        }

        $normalized = array_values(array_unique(array_map('intval', $product_ids)));
        $normalized = array_values(array_filter($normalized, function ($id) {
            return $id > 0;
        }));
        sort($normalized, SORT_NUMERIC);

        return $normalized;
    }
}

if (!function_exists('my_theme_preserve_product_id_order')) {
    function my_theme_preserve_product_id_order($product_ids)
    {
        if (!is_array($product_ids) || empty($product_ids)) {
            return [];
        }

        $normalized = [];
        $seen = [];
        foreach ($product_ids as $product_id) {
            $product_id = (int) $product_id;
            if ($product_id <= 0 || isset($seen[$product_id])) {
                continue;
            }

            $seen[$product_id] = true;
            $normalized[] = $product_id;
        }

        return $normalized;
    }
}

if (!function_exists('my_theme_filter_product_ids_by_source_order')) {
    function my_theme_filter_product_ids_by_source_order($source_product_ids, $allowed_product_ids)
    {
        $source_product_ids = function_exists('my_theme_preserve_product_id_order')
            ? my_theme_preserve_product_id_order($source_product_ids)
            : my_theme_normalize_product_id_list($source_product_ids);
        if (empty($source_product_ids)) {
            return [];
        }

        $allowed_product_ids = function_exists('my_theme_preserve_product_id_order')
            ? my_theme_preserve_product_id_order($allowed_product_ids)
            : my_theme_normalize_product_id_list($allowed_product_ids);
        if (empty($allowed_product_ids)) {
            return [];
        }

        $allowed_lookup = [];
        foreach ($allowed_product_ids as $product_id) {
            $allowed_lookup[(int) $product_id] = true;
        }

        $filtered = [];
        foreach ($source_product_ids as $product_id) {
            $product_id = (int) $product_id;
            if ($product_id > 0 && isset($allowed_lookup[$product_id])) {
                $filtered[] = $product_id;
            }
        }

        return $filtered;
    }
}

if (!function_exists('my_theme_get_product_object_map')) {
    function my_theme_get_product_object_map($product_ids)
    {
        static $request_cache = [];
        static $object_cache = [];

        $product_ids = my_theme_normalize_product_id_list($product_ids);
        if (empty($product_ids) || !function_exists('wc_get_product')) {
            return [];
        }

        $request_key = md5(implode(',', $product_ids));
        if (isset($request_cache[$request_key]) && is_array($request_cache[$request_key])) {
            return $request_cache[$request_key];
        }

        if (function_exists('update_postmeta_cache')) {
            update_postmeta_cache($product_ids);
        }
        if (function_exists('update_object_term_cache')) {
            update_object_term_cache($product_ids, 'product');
        }

        $map = [];
        foreach ($product_ids as $product_id) {
            $product_id = (int) $product_id;
            if ($product_id <= 0) {
                continue;
            }
            if (array_key_exists($product_id, $object_cache)) {
                $cached_product = $object_cache[$product_id];
                if ($cached_product instanceof WC_Product) {
                    $map[$product_id] = $cached_product;
                }
                continue;
            }

            $product = wc_get_product($product_id);
            if ($product instanceof WC_Product) {
                $object_cache[$product_id] = $product;
                $map[$product_id] = $product;
            } else {
                $object_cache[$product_id] = null;
            }
        }

        $request_cache[$request_key] = $map;
        return $map;
    }
}

if (!function_exists('my_theme_count_visible_product_categories')) {
    function my_theme_count_visible_product_categories($product_ids)
    {
        $product_ids = my_theme_normalize_product_id_list($product_ids);
        if (empty($product_ids) || !taxonomy_exists('product_cat')) {
            return 0;
        }

        $cache_version = (string) get_option('my_theme_filter_cache_version', '1');
        $digest = md5(implode(',', $product_ids));
        $cache_key = 'my_theme_cat_count_' . $cache_version . '_' . $digest;
        $cached = get_transient($cache_key);
        if (is_numeric($cached)) {
            return max(0, (int) $cached);
        }

        $term_ids = wp_get_object_terms($product_ids, 'product_cat', [
            'fields' => 'ids',
        ]);
        if (is_wp_error($term_ids) || !is_array($term_ids)) {
            set_transient($cache_key, 0, 30 * MINUTE_IN_SECONDS);
            return 0;
        }

        $term_ids = array_values(array_unique(array_map('intval', $term_ids)));
        $term_ids = array_values(array_filter($term_ids, function ($term_id) {
            return $term_id > 0;
        }));

        $uncategorized_id = 0;
        $uncategorized = get_term_by('slug', 'uncategorized', 'product_cat');
        if ($uncategorized instanceof WP_Term) {
            $uncategorized_id = (int) $uncategorized->term_id;
        }
        if ($uncategorized_id > 0) {
            $term_ids = array_values(array_filter($term_ids, function ($term_id) use ($uncategorized_id) {
                return (int) $term_id !== $uncategorized_id;
            }));
        }

        $count = count($term_ids);
        set_transient($cache_key, $count, 30 * MINUTE_IN_SECONDS);
        return $count;
    }
}

if (!function_exists('my_theme_get_visible_product_category_groups')) {
    function my_theme_get_visible_product_category_groups($product_ids)
    {
        static $request_cache = [];

        $product_ids = my_theme_normalize_product_id_list($product_ids);
        if (empty($product_ids) || !taxonomy_exists('product_cat')) {
            return [
                'lookup' => [],
                'by_parent' => [],
            ];
        }

        $cache_version = (string) get_option('my_theme_filter_cache_version', '1');
        $digest = md5(implode(',', $product_ids));
        $request_key = $cache_version . ':' . $digest;
        if (isset($request_cache[$request_key]) && is_array($request_cache[$request_key])) {
            return $request_cache[$request_key];
        }

        $transient_key = 'my_theme_visible_cat_groups_' . $cache_version . '_' . $digest;
        $cached = get_transient($transient_key);
        if (
            is_array($cached)
            && isset($cached['lookup']) && is_array($cached['lookup'])
            && isset($cached['by_parent']) && is_array($cached['by_parent'])
        ) {
            $request_cache[$request_key] = $cached;
            return $cached;
        }

        $terms = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'object_ids' => $product_ids,
        ]);

        if (is_wp_error($terms) || empty($terms)) {
            $empty_groups = [
                'lookup' => [],
                'by_parent' => [],
            ];
            $request_cache[$request_key] = $empty_groups;
            set_transient($transient_key, $empty_groups, 30 * MINUTE_IN_SECONDS);
            return $empty_groups;
        }

        $term_objects = [];
        $lookup = [];
        foreach ($terms as $term) {
            if (!$term instanceof WP_Term || empty($term->slug) || (string) $term->slug === 'uncategorized') {
                continue;
            }

            $term_id = (int) $term->term_id;
            if ($term_id <= 0) {
                continue;
            }

            $term_objects[$term_id] = $term;
            $lookup[$term_id] = [
                'term_id' => $term_id,
                'parent' => max(0, (int) $term->parent),
                'slug' => (string) $term->slug,
                'name' => (string) $term->name,
                'count' => max(0, (int) $term->count),
            ];
        }

        if (empty($lookup)) {
            $empty_groups = [
                'lookup' => [],
                'by_parent' => [],
            ];
            $request_cache[$request_key] = $empty_groups;
            set_transient($transient_key, $empty_groups, 30 * MINUTE_IN_SECONDS);
            return $empty_groups;
        }

        $group_objects = [];
        foreach ($lookup as $term_id => $term_data) {
            $parent_id = (int) $term_data['parent'];
            if ($parent_id > 0 && !isset($lookup[$parent_id])) {
                $parent_id = 0;
                $lookup[$term_id]['parent'] = 0;
            }
            if (!isset($group_objects[$parent_id])) {
                $group_objects[$parent_id] = [];
            }
            if (isset($term_objects[$term_id])) {
                $group_objects[$parent_id][] = $term_objects[$term_id];
            }
        }

        $by_parent = [];
        foreach ($group_objects as $parent_id => $group_terms) {
            if (function_exists('my_theme_sort_product_category_terms')) {
                $group_terms = my_theme_sort_product_category_terms($group_terms);
            } else {
                usort($group_terms, function ($a, $b) {
                    $a_name = ($a instanceof WP_Term) ? (string) $a->name : '';
                    $b_name = ($b instanceof WP_Term) ? (string) $b->name : '';
                    return strnatcasecmp($a_name, $b_name);
                });
            }

            $by_parent[(int) $parent_id] = [];
            foreach ($group_terms as $term) {
                if (!$term instanceof WP_Term) {
                    continue;
                }
                $term_id = (int) $term->term_id;
                if ($term_id > 0 && isset($lookup[$term_id])) {
                    $by_parent[(int) $parent_id][] = $lookup[$term_id];
                }
            }
        }

        $groups = [
            'lookup' => $lookup,
            'by_parent' => $by_parent,
        ];

        $request_cache[$request_key] = $groups;
        set_transient($transient_key, $groups, 30 * MINUTE_IN_SECONDS);
        return $groups;
    }
}

if (!function_exists('my_theme_detect_brand_slug_from_text')) {
    function my_theme_detect_brand_slug_from_text($text = '')
    {
        $normalized = my_theme_normalize_search_text((string) $text);
        if ($normalized === '') {
            return '';
        }

        foreach (my_theme_get_brand_keyword_map() as $slug => $meta) {
            $aliases = isset($meta['aliases']) && is_array($meta['aliases']) ? $meta['aliases'] : [$slug];
            foreach ($aliases as $alias) {
                $needle = my_theme_normalize_search_text((string) $alias);
                if ($needle !== '' && strpos($normalized, $needle) !== false) {
                    return (string) $slug;
                }
            }
        }

        return '';
    }
}

if (!function_exists('my_theme_get_product_brand_slug')) {
    function my_theme_get_product_brand_slug($prod = null)
    {
        $product = function_exists('my_theme_resolve_product')
            ? my_theme_resolve_product($prod)
            : (($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID()));
        if (!$product instanceof WC_Product) {
            return '';
        }

        static $cache = [];
        $product_id = (int) $product->get_id();
        if ($product_id > 0 && array_key_exists($product_id, $cache)) {
            return (string) $cache[$product_id];
        }

        $tax_candidates = ['pa_brand', 'product_brand', 'brand'];
        foreach ($tax_candidates as $taxonomy) {
            $slugs = my_theme_get_product_term_values($product_id, $taxonomy, 'slug');
            if (!empty($slugs)) {
                $slug = sanitize_title((string) $slugs[0]);
                if ($slug !== '') {
                    if ($product_id > 0) {
                        $cache[$product_id] = $slug;
                    }
                    return $slug;
                }
            }
        }

        $brand_slug = my_theme_detect_brand_slug_from_text($product->get_name());
        if ($brand_slug !== '') {
            if ($product_id > 0) {
                $cache[$product_id] = $brand_slug;
            }
            return $brand_slug;
        }

        $cat_names = my_theme_get_product_term_values($product_id, 'product_cat', 'name');
        if (!empty($cat_names)) {
            foreach ($cat_names as $cat_name) {
                $brand_slug = my_theme_detect_brand_slug_from_text((string) $cat_name);
                if ($brand_slug !== '') {
                    if ($product_id > 0) {
                        $cache[$product_id] = $brand_slug;
                    }
                    return $brand_slug;
                }
            }
        }

        if ($product_id > 0) {
            $cache[$product_id] = '';
        }
        return '';
    }
}

if (!function_exists('my_theme_get_product_brand_label')) {
    function my_theme_get_product_brand_label($prod = null)
    {
        $product = function_exists('my_theme_resolve_product')
            ? my_theme_resolve_product($prod)
            : (($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID()));
        if (!$product instanceof WC_Product) {
            return '';
        }

        $brand_slug = my_theme_get_product_brand_slug($product);
        if ($brand_slug !== '') {
            $map = my_theme_get_brand_keyword_map();
            if (isset($map[$brand_slug]['label']) && (string) $map[$brand_slug]['label'] !== '') {
                return (string) $map[$brand_slug]['label'];
            }
            return ucfirst($brand_slug);
        }

        return 'Sản phẩm';
    }
}

if (!function_exists('my_theme_get_brand_filter_options')) {
    function my_theme_get_brand_filter_options($product_ids = null)
    {
        static $request_cache = [];

        if ($product_ids === null) {
            $product_ids = function_exists('my_theme_get_catalog_visible_product_ids')
                ? my_theme_get_catalog_visible_product_ids(false)
                : [];
        }

        $product_ids = my_theme_normalize_product_id_list($product_ids);
        if (empty($product_ids)) {
            return [];
        }

        $digest = md5(implode(',', $product_ids));
        $cache_version = (string) get_option('my_theme_filter_cache_version', '1');
        $request_key = $cache_version . ':' . $digest;
        if (array_key_exists($request_key, $request_cache)) {
            return $request_cache[$request_key];
        }
        $transient_key = 'my_theme_brand_filter_options_' . $cache_version . '_' . $digest;
        $cached = get_transient($transient_key);
        if (is_array($cached)) {
            $request_cache[$request_key] = $cached;
            return $cached;
        }

        $tax_candidates = ['pa_brand', 'product_brand', 'brand'];
        foreach ($tax_candidates as $taxonomy) {
            if (!taxonomy_exists($taxonomy)) {
                continue;
            }
            $terms = get_terms([
                'taxonomy'   => $taxonomy,
                'hide_empty' => true,
                'orderby'    => 'name',
                'order'      => 'ASC',
                'object_ids' => $product_ids,
            ]);
            if (is_wp_error($terms) || empty($terms)) {
                continue;
            }

            $options = [];
            foreach ($terms as $term) {
                if (!$term instanceof WP_Term || empty($term->slug) || empty($term->name)) {
                    continue;
                }
                $slug = sanitize_title((string) $term->slug);
                if ($slug === '') {
                    continue;
                }
                $options[$slug] = [
                    'label' => (string) $term->name,
                    'count' => max(0, (int) $term->count),
                ];
            }
            if (!empty($options)) {
                uasort($options, function ($a, $b) {
                    $ca = isset($a['count']) ? (int) $a['count'] : 0;
                    $cb = isset($b['count']) ? (int) $b['count'] : 0;
                    if ($ca !== $cb) {
                        return ($ca > $cb) ? -1 : 1;
                    }
                    return strnatcasecmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
                });
                $request_cache[$request_key] = $options;
                set_transient($transient_key, $options, 30 * MINUTE_IN_SECONDS);
                return $options;
            }
        }

        $counts = [];
        $map = my_theme_get_brand_keyword_map();
        $product_map = function_exists('my_theme_get_product_object_map')
            ? my_theme_get_product_object_map($product_ids)
            : [];
        foreach ($product_ids as $product_id) {
            $product = isset($product_map[(int) $product_id]) ? $product_map[(int) $product_id] : wc_get_product((int) $product_id);
            if (!$product instanceof WC_Product) {
                continue;
            }
            $slug = my_theme_get_product_brand_slug($product);
            if ($slug === '') {
                continue;
            }
            if (!isset($counts[$slug])) {
                $counts[$slug] = 0;
            }
            $counts[$slug]++;
        }

        if (empty($counts)) {
            $request_cache[$request_key] = [];
            set_transient($transient_key, [], 30 * MINUTE_IN_SECONDS);
            return [];
        }

        arsort($counts, SORT_NUMERIC);
        $options = [];
        foreach ($counts as $slug => $count) {
            $label = isset($map[$slug]['label']) ? (string) $map[$slug]['label'] : ucfirst((string) $slug);
            $options[$slug] = [
                'label' => $label,
                'count' => (int) $count,
            ];
        }

        $request_cache[$request_key] = $options;
        set_transient($transient_key, $options, 30 * MINUTE_IN_SECONDS);
        return $options;
    }
}

if (!function_exists('my_theme_filter_product_ids_by_brand_slug')) {
    function my_theme_filter_product_ids_by_brand_slug($product_ids, $brand_slug = '')
    {
        static $request_cache = [];

        $brand_slug = sanitize_title((string) $brand_slug);
        $source_product_ids = function_exists('my_theme_preserve_product_id_order')
            ? my_theme_preserve_product_id_order($product_ids)
            : my_theme_normalize_product_id_list($product_ids);
        $product_ids = my_theme_normalize_product_id_list($source_product_ids);
        if ($brand_slug === '' || empty($product_ids)) {
            return $source_product_ids;
        }

        $digest = md5(implode(',', $product_ids));
        $cache_version = (string) get_option('my_theme_filter_cache_version', '1');
        $source_digest = md5(implode(',', $source_product_ids));
        $request_key = $cache_version . ':' . $source_digest . ':' . $brand_slug;
        if (array_key_exists($request_key, $request_cache)) {
            return $request_cache[$request_key];
        }
        $transient_key = 'my_theme_brand_filtered_ids_' . $cache_version . '_' . md5($digest . '|' . $brand_slug);
        $cached = get_transient($transient_key);
        if (is_array($cached)) {
            $request_cache[$request_key] = function_exists('my_theme_filter_product_ids_by_source_order')
                ? my_theme_filter_product_ids_by_source_order($source_product_ids, $cached)
                : my_theme_normalize_product_id_list($cached);
            return $request_cache[$request_key];
        }

        $tax_candidates = ['pa_brand', 'product_brand', 'brand'];
        foreach ($tax_candidates as $taxonomy) {
            if (!taxonomy_exists($taxonomy)) {
                continue;
            }

            $term = get_term_by('slug', $brand_slug, $taxonomy);
            if (!$term instanceof WP_Term || empty($term->term_id)) {
                continue;
            }

            $matched_ids = get_posts([
                'post_type'      => 'product',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'post__in'       => $product_ids,
                'no_found_rows'  => true,
                'ignore_sticky_posts' => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
                'tax_query'      => [
                    [
                        'taxonomy' => $taxonomy,
                        'field'    => 'term_id',
                        'terms'    => [(int) $term->term_id],
                    ],
                ],
            ]);
            $matched_ids = function_exists('my_theme_filter_product_ids_by_source_order')
                ? my_theme_filter_product_ids_by_source_order($source_product_ids, $matched_ids)
                : my_theme_normalize_product_id_list($matched_ids);
            if (!empty($matched_ids)) {
                $request_cache[$request_key] = $matched_ids;
                set_transient($transient_key, my_theme_normalize_product_id_list($matched_ids), 30 * MINUTE_IN_SECONDS);
                return $matched_ids;
            }
        }

        $filtered = [];
        $product_map = function_exists('my_theme_get_product_object_map')
            ? my_theme_get_product_object_map($product_ids)
            : [];
        foreach ($product_ids as $product_id) {
            $product = isset($product_map[(int) $product_id]) ? $product_map[(int) $product_id] : wc_get_product((int) $product_id);
            if (!$product instanceof WC_Product) {
                continue;
            }
            $product_brand_slug = my_theme_get_product_brand_slug($product);
            if ($product_brand_slug === $brand_slug) {
                $filtered[] = (int) $product_id;
            }
        }

        $request_cache[$request_key] = $filtered;
        set_transient($transient_key, my_theme_normalize_product_id_list($filtered), 30 * MINUTE_IN_SECONDS);
        return $filtered;
    }
}

if (!function_exists('my_theme_get_line_keyword_map')) {
    function my_theme_get_line_keyword_map()
    {
        return [
            'easyclean'     => ['label' => 'EasyClean', 'aliases' => ['easyclean'], 'brands' => ['dulux']],
            'ambiance'      => ['label' => 'Ambiance', 'aliases' => ['ambiance'], 'brands' => ['dulux']],
            'weathershield' => ['label' => 'Weathershield', 'aliases' => ['weathershield'], 'brands' => ['dulux']],
            'aquatech'      => ['label' => 'Aquatech', 'aliases' => ['aquatech'], 'brands' => ['dulux']],
            'inspire'       => ['label' => 'Inspire', 'aliases' => ['inspire'], 'brands' => ['dulux']],
            'powerflexx'    => ['label' => 'Powerflexx', 'aliases' => ['powerflexx'], 'brands' => ['dulux']],
            'smooth'        => ['label' => 'Smooth', 'aliases' => ['smooth'], 'brands' => ['maxilite']],
            'total'         => ['label' => 'Total', 'aliases' => ['total'], 'brands' => ['maxilite']],
            'ultima'        => ['label' => 'Ultima', 'aliases' => ['ultima'], 'brands' => ['maxilite']],
            'tough'         => ['label' => 'Tough', 'aliases' => ['tough'], 'brands' => ['maxilite']],
            'webercolor'    => ['label' => 'Webercolor', 'aliases' => ['webercolor'], 'brands' => ['weber']],
            'webertai'      => ['label' => 'Webertai', 'aliases' => ['webertai'], 'brands' => ['weber']],
            'webertec'      => ['label' => 'Webertec', 'aliases' => ['webertec'], 'brands' => ['weber']],
            'weberdry'      => ['label' => 'Weberdry', 'aliases' => ['weberdry'], 'brands' => ['weber']],
            'weberseal'     => ['label' => 'Weberseal', 'aliases' => ['weberseal'], 'brands' => ['weber']],
            'weberprime'    => ['label' => 'Weberprime', 'aliases' => ['weberprime'], 'brands' => ['weber']],
            'weberepox'     => ['label' => 'Weberepox', 'aliases' => ['weberepox'], 'brands' => ['weber']],
            'webershield'   => ['label' => 'Webershield', 'aliases' => ['webershield'], 'brands' => ['weber']],
            'majestic'      => ['label' => 'Majestic', 'aliases' => ['majestic'], 'brands' => ['jotun']],
            'essence'       => ['label' => 'Essence', 'aliases' => ['essence'], 'brands' => ['jotun']],
            'jotaplast'     => ['label' => 'Jotaplast', 'aliases' => ['jotaplast'], 'brands' => ['jotun']],
            'jotashield'    => ['label' => 'Jotashield', 'aliases' => ['jotashield'], 'brands' => ['jotun']],
            'waterguard'    => ['label' => 'WaterGuard', 'aliases' => ['waterguard'], 'brands' => ['jotun']],
            'odourless'     => ['label' => 'Odour-less', 'aliases' => ['odour-less', 'odourless', 'odour less'], 'brands' => ['nippon']],
            'vinilex'       => ['label' => 'Vinilex', 'aliases' => ['vinilex'], 'brands' => ['nippon']],
            'matex'         => ['label' => 'Matex', 'aliases' => ['matex', 'super matex'], 'brands' => ['nippon']],
            'weatherbond'   => ['label' => 'Weatherbond', 'aliases' => ['weatherbond'], 'brands' => ['nippon']],
            'skimcoat'      => ['label' => 'Skim Coat', 'aliases' => ['skim coat', 'skimcoat'], 'brands' => ['nippon']],
            'ct11a-plus'    => ['label' => 'CT-11A Plus', 'aliases' => ['ct-11a plus', 'ct11a plus', 'ct11a'], 'brands' => ['kova']],
            'k209'          => ['label' => 'K-209', 'aliases' => ['k-209', 'k209'], 'brands' => ['kova']],
            'k261'          => ['label' => 'K-261', 'aliases' => ['k-261', 'k261'], 'brands' => ['kova']],
            'k871'          => ['label' => 'K-871', 'aliases' => ['k-871', 'k871'], 'brands' => ['kova']],
            'k5501'         => ['label' => 'K-5501', 'aliases' => ['k-5501', 'k5501'], 'brands' => ['kova']],
            'supershield'   => ['label' => 'SuperShield', 'aliases' => ['supershield', 'super shield'], 'brands' => ['toa']],
            'nanoshield'    => ['label' => 'NanoShield', 'aliases' => ['nanoshield', 'nano shield'], 'brands' => ['toa']],
            '4seasons'      => ['label' => '4Seasons', 'aliases' => ['4seasons', '4 seasons'], 'brands' => ['toa']],
            'toa1000'       => ['label' => 'TOA 1000', 'aliases' => ['toa 1000', '1000'], 'brands' => ['toa']],
            'rusttech'      => ['label' => 'Rust Tech', 'aliases' => ['rust tech', 'rusttech'], 'brands' => ['toa']],
            'sikatop'       => ['label' => 'SikaTop', 'aliases' => ['sikatop', 'sika top'], 'brands' => ['sika']],
            'sikalatex'     => ['label' => 'SikaLatex', 'aliases' => ['sikalatex', 'sika latex'], 'brands' => ['sika']],
            'sikafloor'     => ['label' => 'SikaFloor', 'aliases' => ['sikafloor', 'sika floor'], 'brands' => ['sika']],
            'sikaceram'     => ['label' => 'SikaCeram', 'aliases' => ['sikaceram', 'sika ceram'], 'brands' => ['sika']],
            'sikagrout'     => ['label' => 'SikaGrout', 'aliases' => ['sikagrout', 'sika grout'], 'brands' => ['sika']],
            'sikaflex'      => ['label' => 'SikaFlex', 'aliases' => ['sikaflex', 'sika flex'], 'brands' => ['sika']],
            'sikaguard'     => ['label' => 'SikaGuard', 'aliases' => ['sikaguard', 'sika guard'], 'brands' => ['sika']],
            'a100'          => ['label' => 'A100', 'aliases' => ['a100', 'a-100', 'acrylic a100'], 'brands' => ['apollo']],
            'a200'          => ['label' => 'A200', 'aliases' => ['a200', 'a-200'], 'brands' => ['apollo']],
            'a300'          => ['label' => 'A300', 'aliases' => ['a300', 'a-300'], 'brands' => ['apollo']],
            'a500'          => ['label' => 'A500', 'aliases' => ['a500', 'a-500'], 'brands' => ['apollo']],
            'a600'          => ['label' => 'A600', 'aliases' => ['a600', 'a-600'], 'brands' => ['apollo']],
            'sanitary-n'    => ['label' => 'Sanitary-N', 'aliases' => ['sanitary n', 'sanitary-n'], 'brands' => ['apollo']],
            'weatherseal-a68' => ['label' => 'Weatherseal A68', 'aliases' => ['a68', 'weatherseal a68', 'weatherseal-a68'], 'brands' => ['apollo']],
            'weatherseal-a79' => ['label' => 'Weatherseal A79', 'aliases' => ['a79', 'weatherseal a79', 'weatherseal-a79'], 'brands' => ['apollo']],
            'pu-foam'       => ['label' => 'PU Foam', 'aliases' => ['pu foam', 'apollo foam'], 'brands' => ['apollo']],
            'pu-foam-b1'    => ['label' => 'PU Foam B1', 'aliases' => ['pu foam b1', 'foam b1', 'apollo foam b1'], 'brands' => ['apollo']],
            'line-primer'   => ['label' => 'Sơn lót', 'aliases' => ['son lot', 'primer', 'sealer'], 'brands' => []],
            'line-waterproof' => ['label' => 'Chống thấm', 'aliases' => ['chong tham', 'waterproof'], 'brands' => []],
            'line-putty'    => ['label' => 'Bột trét', 'aliases' => ['bot tret', 'matit', 'putty'], 'brands' => []],
            'line-interior' => ['label' => 'Sơn nội thất', 'aliases' => ['son noi that', 'interior'], 'brands' => []],
            'line-exterior' => ['label' => 'Sơn ngoại thất', 'aliases' => ['son ngoai that', 'exterior'], 'brands' => []],
            'line-metal'    => ['label' => 'Sơn kim loại', 'aliases' => ['son kim loai', 'kim loai', 'metal'], 'brands' => []],
            'line-epoxy'    => ['label' => 'Sơn epoxy', 'aliases' => ['son epoxy', 'epoxy'], 'brands' => []],
            'line-industrial' => ['label' => 'Sơn công nghiệp', 'aliases' => ['son cong nghiep', 'cong nghiep', 'industrial'], 'brands' => []],
            'line-adhesive' => ['label' => 'Keo và phụ gia', 'aliases' => ['keo va phu gia', 'tilefix', 'grout', 'phu gia'], 'brands' => []],
            'line-oil'      => ['label' => 'Sơn dầu', 'aliases' => ['son dau', 'alkyd', 'enamel'], 'brands' => []],
        ];
    }
}

if (!function_exists('my_theme_get_line_label_from_slug')) {
    function my_theme_get_line_label_from_slug($line_slug = '')
    {
        $line_slug = sanitize_title((string) $line_slug);
        if ($line_slug === '') {
            return '';
        }
        $map = my_theme_get_line_keyword_map();
        if (isset($map[$line_slug]['label']) && (string) $map[$line_slug]['label'] !== '') {
            return (string) $map[$line_slug]['label'];
        }
        return ucwords(str_replace('-', ' ', $line_slug));
    }
}

if (!function_exists('my_theme_detect_line_slug_from_text')) {
    function my_theme_detect_line_slug_from_text($text = '', $brand_slug = '')
    {
        $normalized = my_theme_normalize_search_text((string) $text);
        if ($normalized === '') {
            return '';
        }

        $brand_slug = sanitize_title((string) $brand_slug);
        foreach (my_theme_get_line_keyword_map() as $line_slug => $meta) {
            $brands = isset($meta['brands']) && is_array($meta['brands']) ? $meta['brands'] : [];
            if ($brand_slug !== '' && !empty($brands) && !in_array($brand_slug, $brands, true)) {
                continue;
            }

            $aliases = isset($meta['aliases']) && is_array($meta['aliases']) ? $meta['aliases'] : [$line_slug];
            foreach ($aliases as $alias) {
                $needle = my_theme_normalize_search_text((string) $alias);
                if ($needle !== '' && strpos($normalized, $needle) !== false) {
                    return (string) $line_slug;
                }
            }
        }

        return '';
    }
}

if (!function_exists('my_theme_get_product_line_slug')) {
    function my_theme_get_product_line_slug($prod = null)
    {
        $product = function_exists('my_theme_resolve_product')
            ? my_theme_resolve_product($prod)
            : (($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID()));
        if (!$product instanceof WC_Product) {
            return '';
        }

        static $cache = [];
        $product_id = (int) $product->get_id();
        if ($product_id > 0 && array_key_exists($product_id, $cache)) {
            return (string) $cache[$product_id];
        }

        $tax_candidates = ['pa_line', 'product_line', 'line'];
        foreach ($tax_candidates as $taxonomy) {
            $slugs = my_theme_get_product_term_values($product_id, $taxonomy, 'slug');
            if (!empty($slugs)) {
                $term_slug = sanitize_title((string) $slugs[0]);
                if ($term_slug !== '') {
                    if ($product_id > 0) {
                        $cache[$product_id] = $term_slug;
                    }
                    return $term_slug;
                }
            }
        }

        $brand_slug = my_theme_get_product_brand_slug($product);
        $line_slug = my_theme_detect_line_slug_from_text($product->get_name(), $brand_slug);
        if ($line_slug !== '') {
            if ($product_id > 0) {
                $cache[$product_id] = $line_slug;
            }
            return $line_slug;
        }

        $cat_names = my_theme_get_product_term_values($product_id, 'product_cat', 'name');
        if (!empty($cat_names)) {
            foreach ($cat_names as $cat_name) {
                $line_slug = my_theme_detect_line_slug_from_text((string) $cat_name, $brand_slug);
                if ($line_slug !== '') {
                    if ($product_id > 0) {
                        $cache[$product_id] = $line_slug;
                    }
                    return $line_slug;
                }
            }
        }

        $cat_slugs = my_theme_get_product_term_values($product_id, 'product_cat', 'slug');
        if (!empty($cat_slugs)) {
            $fallback_map = [
                'son-lot' => 'line-primer',
                'chong-tham' => 'line-waterproof',
                'bot-tret' => 'line-putty',
                'son-noi-that' => 'line-interior',
                'son-ngoai-that' => 'line-exterior',
                'son-kim-loai' => 'line-metal',
                'son-epoxy' => 'line-epoxy',
                'son-cong-nghiep' => 'line-industrial',
                'keo-va-phu-gia' => 'line-adhesive',
                'son-dau' => 'line-oil',
            ];
            foreach ($cat_slugs as $cat_slug) {
                $cat_slug = sanitize_title((string) $cat_slug);
                if ($cat_slug === '' || !isset($fallback_map[$cat_slug])) {
                    continue;
                }
                $mapped_line = (string) $fallback_map[$cat_slug];
                if ($mapped_line !== '') {
                    if ($product_id > 0) {
                        $cache[$product_id] = $mapped_line;
                    }
                    return $mapped_line;
                }
            }
        }

        if ($product_id > 0) {
            $cache[$product_id] = '';
        }
        return '';
    }
}

if (!function_exists('my_theme_get_product_line_label')) {
    function my_theme_get_product_line_label($prod = null)
    {
        $line_slug = my_theme_get_product_line_slug($prod);
        if ($line_slug === '') {
            return '';
        }
        return my_theme_get_line_label_from_slug($line_slug);
    }
}

if (!function_exists('my_theme_get_line_filter_options')) {
    function my_theme_get_line_filter_options($product_ids, $brand_slug = '')
    {
        static $request_cache = [];

        $product_ids = my_theme_normalize_product_id_list($product_ids);
        if (empty($product_ids)) {
            return [];
        }

        $brand_slug = sanitize_title((string) $brand_slug);
        $digest = md5(implode(',', $product_ids));
        $cache_version = (string) get_option('my_theme_filter_cache_version', '1');
        $request_key = $cache_version . ':' . $digest . ':' . $brand_slug;
        if (array_key_exists($request_key, $request_cache)) {
            return $request_cache[$request_key];
        }
        $transient_key = 'my_theme_line_filter_options_' . $cache_version . '_' . md5($digest . '|' . $brand_slug);
        $cached = get_transient($transient_key);
        if (is_array($cached)) {
            $request_cache[$request_key] = $cached;
            return $cached;
        }

        if ($brand_slug !== '' && function_exists('my_theme_filter_product_ids_by_brand_slug')) {
            $product_ids = my_theme_filter_product_ids_by_brand_slug($product_ids, $brand_slug);
            if (empty($product_ids)) {
                $request_cache[$request_key] = [];
                set_transient($transient_key, [], 30 * MINUTE_IN_SECONDS);
                return [];
            }
        }

        $tax_candidates = ['pa_line', 'product_line', 'line'];
        foreach ($tax_candidates as $taxonomy) {
            if (!taxonomy_exists($taxonomy)) {
                continue;
            }
            $terms = wp_get_object_terms($product_ids, $taxonomy, [
                'orderby' => 'name',
                'order'   => 'ASC',
                'fields'  => 'all_with_object_id',
            ]);
            if (is_wp_error($terms) || empty($terms)) {
                continue;
            }

            $options = [];
            $line_product_map = [];
            foreach ($terms as $term) {
                if (!$term instanceof WP_Term || empty($term->slug)) {
                    continue;
                }
                $slug = sanitize_title((string) $term->slug);
                if ($slug === '') {
                    continue;
                }
                $label = !empty($term->name)
                    ? (string) $term->name
                    : my_theme_get_line_label_from_slug($slug);
                if (!isset($options[$slug])) {
                    $options[$slug] = [
                        'label' => $label,
                        'count' => 0,
                    ];
                }

                $object_id = isset($term->object_id) ? (int) $term->object_id : 0;
                if ($object_id > 0) {
                    if (!isset($line_product_map[$slug])) {
                        $line_product_map[$slug] = [];
                    }
                    $line_product_map[$slug][$object_id] = true;
                }
            }
            if (empty($options)) {
                continue;
            }

            foreach ($options as $slug => $meta) {
                $count = !empty($line_product_map[$slug]) ? count($line_product_map[$slug]) : 0;
                $options[$slug]['count'] = max(0, (int) $count);
            }
            $options = array_filter($options, function ($meta) {
                return isset($meta['count']) && (int) $meta['count'] > 0;
            });
            if (empty($options)) {
                continue;
            }

            uasort($options, function ($a, $b) {
                $ca = isset($a['count']) ? (int) $a['count'] : 0;
                $cb = isset($b['count']) ? (int) $b['count'] : 0;
                if ($ca !== $cb) {
                    return ($ca > $cb) ? -1 : 1;
                }
                return strnatcasecmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
            });

            $request_cache[$request_key] = $options;
            set_transient($transient_key, $options, 30 * MINUTE_IN_SECONDS);
            return $options;
        }

        $line_counts = [];
        $product_map = function_exists('my_theme_get_product_object_map')
            ? my_theme_get_product_object_map($product_ids)
            : [];
        foreach ($product_ids as $product_id) {
            $product = isset($product_map[(int) $product_id]) ? $product_map[(int) $product_id] : wc_get_product((int) $product_id);
            if (!$product instanceof WC_Product) {
                continue;
            }

            $line_slug = my_theme_get_product_line_slug($product);
            if ($line_slug === '') {
                continue;
            }

            if (!isset($line_counts[$line_slug])) {
                $line_counts[$line_slug] = 0;
            }
            $line_counts[$line_slug]++;
        }

        if (empty($line_counts)) {
            $request_cache[$request_key] = [];
            set_transient($transient_key, [], 30 * MINUTE_IN_SECONDS);
            return [];
        }

        $options = [];
        foreach ($line_counts as $line_slug => $count) {
            $options[$line_slug] = [
                'label' => my_theme_get_line_label_from_slug($line_slug),
                'count' => (int) $count,
            ];
        }

        uasort($options, function ($a, $b) {
            $ca = isset($a['count']) ? (int) $a['count'] : 0;
            $cb = isset($b['count']) ? (int) $b['count'] : 0;
            if ($ca !== $cb) {
                return ($ca > $cb) ? -1 : 1;
            }
            return strnatcasecmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
        });

        $request_cache[$request_key] = $options;
        set_transient($transient_key, $options, 30 * MINUTE_IN_SECONDS);
        return $options;
    }
}

if (!function_exists('my_theme_filter_product_ids_by_line_slug')) {
    function my_theme_filter_product_ids_by_line_slug($product_ids, $line_slug = '', $brand_slug = '')
    {
        static $request_cache = [];

        $line_slug = sanitize_title((string) $line_slug);
        $brand_slug = sanitize_title((string) $brand_slug);
        $source_product_ids = function_exists('my_theme_preserve_product_id_order')
            ? my_theme_preserve_product_id_order($product_ids)
            : my_theme_normalize_product_id_list($product_ids);
        $product_ids = my_theme_normalize_product_id_list($source_product_ids);
        if ($line_slug === '' || empty($product_ids)) {
            return $source_product_ids;
        }

        $digest = md5(implode(',', $product_ids));
        $cache_version = (string) get_option('my_theme_filter_cache_version', '1');
        $source_digest = md5(implode(',', $source_product_ids));
        $request_key = $cache_version . ':' . $source_digest . ':' . $line_slug . ':' . $brand_slug;
        if (array_key_exists($request_key, $request_cache)) {
            return $request_cache[$request_key];
        }
        $transient_key = 'my_theme_line_filtered_ids_' . $cache_version . '_' . md5($digest . '|' . $line_slug . '|' . $brand_slug);
        $cached = get_transient($transient_key);
        if (is_array($cached)) {
            $request_cache[$request_key] = function_exists('my_theme_filter_product_ids_by_source_order')
                ? my_theme_filter_product_ids_by_source_order($source_product_ids, $cached)
                : my_theme_normalize_product_id_list($cached);
            return $request_cache[$request_key];
        }

        $tax_candidates = ['pa_line', 'product_line', 'line'];
        foreach ($tax_candidates as $line_taxonomy) {
            if (!taxonomy_exists($line_taxonomy)) {
                continue;
            }
            $line_term = get_term_by('slug', $line_slug, $line_taxonomy);
            if (!$line_term instanceof WP_Term || empty($line_term->term_id)) {
                continue;
            }

            $tax_query = [
                'relation' => 'AND',
                [
                    'taxonomy' => $line_taxonomy,
                    'field'    => 'term_id',
                    'terms'    => [(int) $line_term->term_id],
                ],
            ];

            if ($brand_slug !== '') {
                $brand_tax_candidates = ['pa_brand', 'product_brand', 'brand'];
                foreach ($brand_tax_candidates as $brand_tax) {
                    if (!taxonomy_exists($brand_tax)) {
                        continue;
                    }
                    $brand_term = get_term_by('slug', $brand_slug, $brand_tax);
                    if ($brand_term instanceof WP_Term && !empty($brand_term->term_id)) {
                        $tax_query[] = [
                            'taxonomy' => $brand_tax,
                            'field'    => 'term_id',
                            'terms'    => [(int) $brand_term->term_id],
                        ];
                        break;
                    }
                }
            }

            $matched_ids = get_posts([
                'post_type'      => 'product',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'post__in'       => $product_ids,
                'no_found_rows'  => true,
                'ignore_sticky_posts' => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
                'tax_query'      => $tax_query,
            ]);
            $matched_ids = function_exists('my_theme_filter_product_ids_by_source_order')
                ? my_theme_filter_product_ids_by_source_order($source_product_ids, $matched_ids)
                : my_theme_normalize_product_id_list($matched_ids);
            if (!empty($matched_ids)) {
                $request_cache[$request_key] = $matched_ids;
                set_transient($transient_key, my_theme_normalize_product_id_list($matched_ids), 30 * MINUTE_IN_SECONDS);
                return $matched_ids;
            }
        }

        $filtered = [];
        $product_map = function_exists('my_theme_get_product_object_map')
            ? my_theme_get_product_object_map($product_ids)
            : [];
        foreach ($product_ids as $product_id) {
            $product = isset($product_map[(int) $product_id]) ? $product_map[(int) $product_id] : wc_get_product((int) $product_id);
            if (!$product instanceof WC_Product) {
                continue;
            }
            if ($brand_slug !== '' && my_theme_get_product_brand_slug($product) !== $brand_slug) {
                continue;
            }
            $product_line_slug = my_theme_get_product_line_slug($product);
            if ($product_line_slug === $line_slug) {
                $filtered[] = (int) $product_id;
            }
        }

        $request_cache[$request_key] = $filtered;
        set_transient($transient_key, my_theme_normalize_product_id_list($filtered), 30 * MINUTE_IN_SECONDS);
        return $filtered;
    }
}

if (!function_exists('my_theme_get_product_primary_category_term')) {
    function my_theme_get_product_primary_category_term($prod = null)
    {
        $product = function_exists('my_theme_resolve_product')
            ? my_theme_resolve_product($prod)
            : (($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID()));
        if (!$product instanceof WC_Product || !taxonomy_exists('product_cat')) {
            return null;
        }

        static $cache = [];
        $cache_key = (int) $product->get_id();
        if (array_key_exists($cache_key, $cache)) {
            return $cache[$cache_key];
        }

        $terms = my_theme_get_product_term_objects($cache_key, 'product_cat');
        if (empty($terms)) {
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

if (!function_exists('my_theme_get_product_catalog_profile')) {
    function my_theme_get_product_catalog_profile($prod = null)
    {
        $product = function_exists('my_theme_resolve_product')
            ? my_theme_resolve_product($prod)
            : (($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID()));
        if (!$product instanceof WC_Product) {
            return [
                'display_name' => '',
                'brand_label' => '',
                'brand_slug' => '',
                'line_label' => '',
                'line_slug' => '',
                'primary_term' => null,
                'category_id' => 0,
                'category_label' => '',
                'category_slug' => '',
            ];
        }

        static $cache = [];
        $product_id = (int) $product->get_id();
        if ($product_id > 0 && array_key_exists($product_id, $cache)) {
            return $cache[$product_id];
        }

        $display_name = function_exists('my_theme_get_product_display_name')
            ? (string) my_theme_get_product_display_name($product)
            : (string) $product->get_name();
        $brand_slug = function_exists('my_theme_get_product_brand_slug')
            ? sanitize_title((string) my_theme_get_product_brand_slug($product))
            : '';
        $brand_label = '';
        if ($brand_slug !== '') {
            $brand_map = my_theme_get_brand_keyword_map();
            $brand_label = isset($brand_map[$brand_slug]['label'])
                ? trim((string) $brand_map[$brand_slug]['label'])
                : ucfirst($brand_slug);
        }
        if ($brand_label === '') {
            $brand_label = 'Sản phẩm';
        }

        $line_slug = function_exists('my_theme_get_product_line_slug')
            ? sanitize_title((string) my_theme_get_product_line_slug($product))
            : '';
        $line_label = $line_slug !== ''
            ? my_theme_get_line_label_from_slug($line_slug)
            : '';

        $primary_term = function_exists('my_theme_get_product_primary_category_term')
            ? my_theme_get_product_primary_category_term($product)
            : null;

        $profile = [
            'display_name' => $display_name,
            'brand_label' => $brand_label,
            'brand_slug' => $brand_slug,
            'line_label' => $line_label,
            'line_slug' => $line_slug,
            'primary_term' => $primary_term instanceof WP_Term ? $primary_term : null,
            'category_id' => $primary_term instanceof WP_Term ? (int) $primary_term->term_id : 0,
            'category_label' => $primary_term instanceof WP_Term ? (string) $primary_term->name : '',
            'category_slug' => $primary_term instanceof WP_Term ? sanitize_title((string) $primary_term->slug) : '',
        ];

        if ($product_id > 0) {
            $cache[$product_id] = $profile;
        }

        return $profile;
    }
}

if (!function_exists('my_theme_get_product_card_excerpt')) {
    function my_theme_get_product_card_excerpt($prod = null, $limit = 16)
    {
        $product = function_exists('my_theme_resolve_product')
            ? my_theme_resolve_product($prod)
            : (($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID()));
        if (!$product instanceof WC_Product) {
            return '';
        }

        static $base_cache = [];
        static $trimmed_cache = [];

        $product_id = (int) $product->get_id();
        $limit = max(8, (int) $limit);
        $trimmed_cache_key = $product_id . ':' . $limit;
        if ($product_id > 0 && array_key_exists($trimmed_cache_key, $trimmed_cache)) {
            return (string) $trimmed_cache[$trimmed_cache_key];
        }

        if ($product_id > 0 && array_key_exists($product_id, $base_cache)) {
            $text = (string) $base_cache[$product_id];
        } else {
            $text = trim(wp_strip_all_tags((string) $product->get_short_description()));
            if ($text === '') {
                $text = trim(wp_strip_all_tags((string) $product->get_description()));
            }
            if ($text !== '' && function_exists('my_theme_text_looks_unaccented_vi') && my_theme_text_looks_unaccented_vi($text)) {
                $text = '';
            }
            if ($text === '') {
                $profile = function_exists('my_theme_get_product_catalog_profile')
                    ? my_theme_get_product_catalog_profile($product)
                    : [];
                $cat_slug = isset($profile['category_slug']) ? sanitize_title((string) $profile['category_slug']) : '';
                $cat_name = isset($profile['category_label']) ? trim((string) $profile['category_label']) : '';
                $line_label = isset($profile['line_label']) ? trim((string) $profile['line_label']) : '';
                $line_slug = isset($profile['line_slug']) ? sanitize_title((string) $profile['line_slug']) : '';
                $brand_label = isset($profile['brand_label']) ? trim((string) $profile['brand_label']) : '';

                $usage_map = [
                    'son-noi-that' => 'Dùng cho tường nội thất, bám dính tốt và giữ màu bền khi sử dụng hằng ngày.',
                    'son-ngoai-that' => 'Dùng cho tường ngoại thất, tăng độ bền màu và chống chịu thời tiết.',
                    'son-lot' => 'Dùng làm lớp lót giúp tăng bám dính và ổn định bề mặt trước khi sơn phủ.',
                    'chong-tham' => 'Dùng cho khu vực cần chống thấm như tường ngoài, sân thượng và khu ẩm ướt.',
                    'bot-tret' => 'Dùng để làm phẳng bề mặt trước khi thi công sơn lót và sơn phủ.',
                    'keo-va-phu-gia' => 'Dùng cho hạng mục dán, chà ron, trám khe hoặc tăng độ kết dính khi thi công.',
                    'son-epoxy' => 'Dùng cho bề mặt chịu tải và ma sát cao, cần lớp phủ bền cơ học.',
                    'son-kim-loai' => 'Dùng cho bề mặt kim loại, hỗ trợ chống gỉ và bảo vệ lớp nền.',
                    'son-cong-nghiep' => 'Dùng cho hạng mục công nghiệp cần độ bền và khả năng bảo vệ cao.',
                    'son-dau' => 'Dùng cho bề mặt yêu cầu độ phủ và độ bền phù hợp hệ sơn dầu.',
                ];

                if ($cat_slug !== '' && isset($usage_map[$cat_slug])) {
                    $text = (string) $usage_map[$cat_slug];
                } else {
                    $scope = $cat_name !== '' ? $cat_name : 'hạng mục thi công';
                    $text = 'Sản phẩm dùng cho ' . mb_strtolower($scope) . ', hỗ trợ thi công ổn định và bền bề mặt.';
                }

                // Keep usage text neutral and concise; avoid awkward prefixes like "Dòng ...".
                $line_is_generic = function_exists('my_theme_is_generic_line_label')
                    ? my_theme_is_generic_line_label($line_label, $line_slug, $cat_name)
                    : false;
                if (!$line_is_generic && $brand_label !== '' && $brand_label !== 'Sản phẩm') {
                    $text = trim((string) preg_replace('/^\s*Dòng\s+/iu', '', $text));
                }
            }

            $text = trim((string) preg_replace('/^\s*Dòng\s+[^:]{1,80}:\s*/u', '', $text));
            if ($product_id > 0) {
                $base_cache[$product_id] = $text;
            }
        }

        $trimmed = wp_trim_words($text, $limit, '...');
        if ($product_id > 0) {
            $trimmed_cache[$trimmed_cache_key] = $trimmed;
        }

        return $trimmed;
    }
}

if (!function_exists('my_theme_get_catalog_search_index')) {
    function my_theme_get_catalog_search_index($product_ids = null)
    {
        static $request_cache = [];
        static $parent_term_cache = [];

        if ($product_ids === null) {
            $product_ids = function_exists('my_theme_get_catalog_visible_product_ids')
                ? my_theme_get_catalog_visible_product_ids(false)
                : [];
        }

        $product_ids = my_theme_normalize_product_id_list($product_ids);
        if (empty($product_ids)) {
            return [];
        }

        $cache_version = (string) get_option('my_theme_filter_cache_version', '1');
        $digest = md5(implode(',', $product_ids));
        $request_key = $cache_version . ':' . $digest;
        if (array_key_exists($request_key, $request_cache)) {
            return $request_cache[$request_key];
        }

        $cache_key = 'my_theme_catalog_search_index_v1_' . $cache_version . '_' . $digest;
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            $request_cache[$request_key] = $cached;
            return $request_cache[$request_key];
        }

        $product_map = function_exists('my_theme_get_product_object_map')
            ? my_theme_get_product_object_map($product_ids)
            : [];
        if (empty($product_map)) {
            $request_cache[$request_key] = [];
            set_transient($cache_key, [], 30 * MINUTE_IN_SECONDS);
            return [];
        }

        $index = [];
        foreach ($product_ids as $product_id) {
            $product_id = (int) $product_id;
            if ($product_id <= 0 || !isset($product_map[$product_id])) {
                continue;
            }

            $product = $product_map[$product_id];
            if (!$product instanceof WC_Product) {
                continue;
            }

            $profile = function_exists('my_theme_get_product_catalog_profile')
                ? my_theme_get_product_catalog_profile($product)
                : [];
            $name = isset($profile['display_name']) && (string) $profile['display_name'] !== ''
                ? (string) $profile['display_name']
                : ((string) $product->get_name());
            $brand_label = isset($profile['brand_label']) ? (string) $profile['brand_label'] : '';
            $brand_slug = isset($profile['brand_slug']) ? sanitize_title((string) $profile['brand_slug']) : '';
            $line_label = isset($profile['line_label']) ? (string) $profile['line_label'] : '';
            $line_slug = isset($profile['line_slug']) ? sanitize_title((string) $profile['line_slug']) : '';
            $primary_term = isset($profile['primary_term']) && $profile['primary_term'] instanceof WP_Term
                ? $profile['primary_term']
                : null;
            $category_label = isset($profile['category_label']) ? (string) $profile['category_label'] : '';
            $category_slug = isset($profile['category_slug']) ? sanitize_title((string) $profile['category_slug']) : '';
            $category_parent_slug = '';
            if ($primary_term instanceof WP_Term && (int) $primary_term->parent > 0) {
                $parent_id = (int) $primary_term->parent;
                if (!array_key_exists($parent_id, $parent_term_cache)) {
                    $parent_term = get_term($parent_id, 'product_cat');
                    $parent_term_cache[$parent_id] = ($parent_term instanceof WP_Term && !is_wp_error($parent_term))
                        ? sanitize_title((string) $parent_term->slug)
                        : '';
                }
                $category_parent_slug = (string) $parent_term_cache[$parent_id];
            }

            $excerpt = function_exists('my_theme_get_product_card_excerpt')
                ? (string) my_theme_get_product_card_excerpt($product, 18)
                : '';
            $name_norm = my_theme_normalize_search_text($name);
            $brand_norm = my_theme_normalize_search_text($brand_label);
            $line_norm = my_theme_normalize_search_text($line_label);
            $category_norm = my_theme_normalize_search_text($category_label);
            $excerpt_norm = my_theme_normalize_search_text($excerpt);

            $created = method_exists($product, 'get_date_created') ? $product->get_date_created() : null;
            $index[$product_id] = [
                'id' => $product_id,
                'name' => $name,
                'name_norm' => $name_norm,
                'brand_label' => $brand_label,
                'brand_slug' => $brand_slug,
                'brand_norm' => $brand_norm,
                'line_label' => $line_label,
                'line_slug' => $line_slug,
                'line_norm' => $line_norm,
                'category_label' => $category_label,
                'category_slug' => $category_slug,
                'category_parent_slug' => $category_parent_slug,
                'category_norm' => $category_norm,
                'excerpt' => $excerpt,
                'excerpt_norm' => $excerpt_norm,
                'haystack' => trim(implode(' ', array_filter([
                    $name_norm,
                    $brand_norm,
                    $line_norm,
                    $category_norm,
                    $excerpt_norm,
                ]))),
                'featured' => (method_exists($product, 'is_featured') && $product->is_featured()) ? 1 : 0,
                'stock' => (method_exists($product, 'is_in_stock') && $product->is_in_stock()) ? 1 : 0,
                'sales_total' => method_exists($product, 'get_total_sales')
                    ? (int) $product->get_total_sales()
                    : (int) get_post_meta($product_id, 'total_sales', true),
                'created_ts' => ($created instanceof WC_DateTime) ? (int) $created->getTimestamp() : 0,
                'url' => get_permalink($product_id),
            ];
        }

        $request_cache[$request_key] = $index;
        set_transient($cache_key, $index, 30 * MINUTE_IN_SECONDS);
        return $request_cache[$request_key];
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

        $brand_slug = function_exists('my_theme_get_product_brand_slug')
            ? sanitize_title((string) my_theme_get_product_brand_slug($product))
            : '';
        if ($brand_slug === '') {
            return false;
        }

        if (!$require_price) {
            return true;
        }

        $map = function_exists('my_theme_get_pack_price_display_map')
            ? my_theme_get_pack_price_display_map($product)
            : my_theme_get_pack_price_map_for_display($product);
        if (!empty($map)) {
            return true;
        }

        $display_price = function_exists('my_theme_get_default_loop_price')
            ? (float) my_theme_get_default_loop_price($product)
            : (float) $product->get_price();

        return $display_price > 0;
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
        $cache_key = 'my_theme_related_v2_' . $cache_version . '_' . $product_id . '_' . $limit;
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

            $candidate_query_args = [
                'post_type'           => 'product',
                'post_status'         => 'publish',
                'posts_per_page'      => max(48, $limit * 16),
                'post__not_in'        => array_merge([$product_id], $seen_ids),
                'ignore_sticky_posts' => true,
            ];
            if (!empty($stage['tax_query'])) {
                $candidate_query_args['tax_query'] = $stage['tax_query'];
            }

            $candidate_ids = function_exists('my_theme_get_price_sorted_query_product_ids')
                ? my_theme_get_price_sorted_query_product_ids($candidate_query_args, 'desc')
                : get_posts(array_merge($candidate_query_args, [
                    'fields' => 'ids',
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'no_found_rows' => true,
                    'update_post_meta_cache' => false,
                    'update_post_term_cache' => false,
                ]));
            $candidate_ids = function_exists('my_theme_preserve_product_id_order')
                ? my_theme_preserve_product_id_order($candidate_ids)
                : my_theme_normalize_product_id_list($candidate_ids);
            if (empty($candidate_ids)) {
                continue;
            }
            if (count($candidate_ids) > $candidate_query_args['posts_per_page']) {
                $candidate_ids = array_slice($candidate_ids, 0, $candidate_query_args['posts_per_page']);
            }

            $product_map = function_exists('my_theme_get_product_object_map')
                ? my_theme_get_product_object_map($candidate_ids)
                : [];
            if (empty($product_map)) {
                continue;
            }

            foreach ($candidate_ids as $candidate_id) {
                $candidate_id = (int) $candidate_id;
                $candidate = isset($product_map[$candidate_id]) ? $product_map[$candidate_id] : null;
                if (!$candidate instanceof WC_Product) {
                    continue;
                }
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

function my_theme_get_package_summary_text($product) {
    if (!$product instanceof WC_Product) {
        return '';
    }

    $package_values = my_theme_get_package_options($product);
    if (empty($package_values)) {
        return '';
    }

    return implode(' • ', $package_values);
}

function my_theme_render_capacity_weight($prod = null) {
    $product = ($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID());
    if (!$product instanceof WC_Product) {
        return;
    }

    $has_pack_price = !empty(function_exists('my_theme_get_pack_price_display_map')
        ? my_theme_get_pack_price_display_map($product)
        : my_theme_get_pack_price_map_for_display($product));
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
    $package = my_theme_get_package_summary_text($product);
    $parts = [];
    if ($capacity !== '') {
        $parts[] = sprintf('Dung tích: %s', $capacity);
    }
    if ($weight_attr !== '') {
        $parts[] = sprintf('Khối lượng: %s', $weight_attr);
    } elseif ($weight !== '') {
        $parts[] = sprintf('Khối lượng: %s', wc_format_weight($weight));
    }
    if ($package !== '') {
        $parts[] = sprintf('Quy cách: %s', $package);
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

if (!function_exists('my_theme_render_loop_pack_summary')) {
    function my_theme_render_loop_pack_summary($prod = null, $show_map_sizes = false)
    {
        $product = ($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID());
        if (!$product instanceof WC_Product) {
            return;
        }

        $parts = [];
        $capacity_values = [];
        $weight_values = [];
        $package_values = [];
        $pack_price_map = function_exists('my_theme_get_pack_price_display_map')
            ? my_theme_get_pack_price_display_map($product)
            : my_theme_get_pack_price_map_for_display($product);

        if (!empty($pack_price_map)) {
            if (!$show_map_sizes) {
                return;
            }

            $map_labels = array_keys($pack_price_map);
            $capacity_values = my_theme_sort_pack_labels($map_labels, 'L');
            $weight_values = my_theme_sort_pack_labels($map_labels, 'kg');
        } else {
            $capacity_values = my_theme_get_capacity_options($product);
            $weight_values = my_theme_get_weight_options($product);
            $package_values = my_theme_get_package_options($product);
        }

        if (!empty($capacity_values)) {
            $parts[] = sprintf('Dung tích: %s', implode(' • ', $capacity_values));
        }

        if (!empty($weight_values)) {
            $parts[] = sprintf('Khối lượng: %s', implode(' • ', $weight_values));
        } else {
            $numeric_weight = $product->get_weight();
            if ($numeric_weight !== '') {
                $parts[] = sprintf('Khối lượng: %s', wc_format_weight((float) $numeric_weight));
            }
        }

        if (!empty($package_values)) {
            $parts[] = sprintf('Quy cách: %s', implode(' • ', $package_values));
        }

        if (empty($parts)) {
            return;
        }

        echo '<div class="product-card__meta product-card__meta--packs meta-stack" aria-label="Quy cách sản phẩm">';
        foreach ($parts as $part) {
            echo '<span class="meta-line">' . esc_html($part) . '</span>';
        }
        echo '</div>';
    }
}

// Render chip dung tích / khối lượng cho archive + single.
function my_theme_render_capacity_badges($prod = null) {
    $product = ($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID());
    if (!$product instanceof WC_Product) return;

    $map = function_exists('my_theme_get_pack_price_display_map')
        ? my_theme_get_pack_price_display_map($product)
        : my_theme_get_pack_price_map_for_display($product);
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
    $packages = my_theme_get_package_options($product);
    if (empty($caps) && empty($weights) && empty($packages)) return;
    $readonly = ($product->is_type('simple') && empty($map));
    $wrap_class = 'capacity-badges' . ($readonly ? ' capacity-badges--readonly' : '');
    $chip_class = 'capacity-chip' . ($readonly ? ' capacity-chip--readonly' : '');
    $weight_chip_class = $chip_class . ' capacity-chip--muted';
    $package_chip_class = $chip_class . ' capacity-chip--package';

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
    if (!empty($packages)) {
        echo '<div class="capacity-badges__row" aria-label="Quy cách">';
        foreach ($packages as $item) {
            echo '<span class="' . esc_attr($package_chip_class) . '">' . esc_html($item) . '</span>';
        }
        echo '</div>';
    }
    echo '</div>';
}
add_action('woocommerce_after_shop_loop_item_title', 'my_theme_render_capacity_badges', 12);
add_action('woocommerce_single_product_summary', 'my_theme_render_capacity_badges', 12);

if (!function_exists('my_theme_get_jotun_interior_colour_catalog')) {
    function my_theme_get_jotun_interior_colour_catalog()
    {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }

        $cache = [
            'source_page' => 'https://www.jotun.com/vn-vi/decorative/colours/interior/all-interior-colours?relatedProductIds=1707',
            'total'       => 0,
            'items'       => [],
        ];

        $path = get_theme_file_path('data/jotun_interior_colours.json');
        if (!is_string($path) || !is_readable($path)) {
            return $cache;
        }

        $raw = file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return $cache;
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['items']) || !is_array($data['items'])) {
            return $cache;
        }

        $items = [];
        foreach ($data['items'] as $row) {
            if (!is_array($row)) {
                continue;
            }

            $code = trim((string) ($row['code'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            $hex = strtolower(trim((string) ($row['hex'] ?? '')));
            $hex = preg_replace('/[^0-9a-f]/i', '', $hex);
            $link = trim((string) ($row['link'] ?? ''));

            if ($code === '' || !is_string($hex) || strlen($hex) !== 6) {
                continue;
            }

            if ($link !== '' && strpos($link, 'http') !== 0) {
                $link = 'https://www.jotun.com' . $link;
            }

            $items[] = [
                'code' => $code,
                'name' => $name,
                'hex'  => $hex,
                'link' => $link,
            ];
        }

        if (!empty($items)) {
            $cache['items'] = $items;
            $cache['total'] = count($items);
        }
        if (!empty($data['source_page'])) {
            $cache['source_page'] = (string) $data['source_page'];
        }

        return $cache;
    }
}

if (!function_exists('my_theme_get_dulux_colour_catalog')) {
    function my_theme_get_dulux_colour_catalog()
    {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }

        $cache = [
            'source_page' => 'https://www.dulux.vn/vi/mau-sac-bang-mau/filters/b_dulux',
            'total'       => 0,
            'items'       => [],
        ];

        $path = get_theme_file_path('data/dulux_colour_catalog.json');
        if (!is_string($path) || !is_readable($path)) {
            return $cache;
        }

        $raw = file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return $cache;
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['items']) || !is_array($data['items'])) {
            return $cache;
        }

        $items = [];
        foreach ($data['items'] as $row) {
            if (!is_array($row)) {
                continue;
            }

            $code = trim((string) ($row['code'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            $hex = strtolower(trim((string) ($row['hex'] ?? '')));
            $hex = preg_replace('/[^0-9a-f]/i', '', $hex);
            $link = trim((string) ($row['link'] ?? ''));

            if ($code === '' || !is_string($hex) || strlen($hex) !== 6) {
                continue;
            }

            if ($link !== '' && strpos($link, 'http') !== 0) {
                $link = 'https://www.dulux.vn' . $link;
            }

            $items[] = [
                'code' => $code,
                'name' => $name,
                'hex'  => $hex,
                'link' => $link,
            ];
        }

        if (!empty($items)) {
            $cache['items'] = $items;
            $cache['total'] = count($items);
        }
        if (!empty($data['source_page'])) {
            $cache['source_page'] = (string) $data['source_page'];
        }

        return $cache;
    }
}

if (!function_exists('my_theme_normalize_official_product_source_url')) {
    function my_theme_normalize_official_product_source_url($url = '')
    {
        $url = html_entity_decode(trim((string) $url), ENT_QUOTES, 'UTF-8');
        if ($url === '') {
            return '';
        }

        if (strpos($url, '//') === 0) {
            $url = 'https:' . $url;
        }

        $parts = wp_parse_url($url);
        if (!is_array($parts) || empty($parts['host'])) {
            return esc_url_raw($url);
        }

        $scheme = isset($parts['scheme']) ? strtolower((string) $parts['scheme']) : 'https';
        $host = strtolower((string) $parts['host']);
        $path = isset($parts['path']) ? rawurldecode((string) $parts['path']) : '';
        $path = preg_replace('#/+#', '/', $path);
        $path = ($path === '' ? '/' : rtrim($path, '/'));
        if ($path === '') {
            $path = '/';
        }

        return $scheme . '://' . $host . $path;
    }
}

if (!function_exists('my_theme_parse_bool_flag')) {
    function my_theme_parse_bool_flag($value)
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return ((float) $value) > 0;
        }
        $text = strtolower(trim((string) $value));
        return in_array($text, ['1', 'true', 'yes', 'y', 'on'], true);
    }
}

if (!function_exists('my_theme_get_product_official_source_url')) {
    function my_theme_get_product_official_source_url($product = null)
    {
        if (!$product instanceof WC_Product) {
            return '';
        }
        $product_id = (int) $product->get_id();
        if ($product_id <= 0) {
            return '';
        }
        $raw = (string) get_post_meta($product_id, '_official_source_url', true);
        if (trim($raw) === '') {
            $raw = (string) get_post_meta($product_id, '_official_source_page', true);
        }
        return my_theme_normalize_official_product_source_url($raw);
    }
}

if (!function_exists('my_theme_make_absolute_source_url')) {
    function my_theme_make_absolute_source_url($base_url, $raw_url)
    {
        $base_url = trim((string) $base_url);
        $raw_url = trim((string) $raw_url);
        if ($raw_url === '') {
            return '';
        }

        if (wp_http_validate_url($raw_url)) {
            return esc_url_raw($raw_url);
        }

        $scheme = (string) wp_parse_url($base_url, PHP_URL_SCHEME);
        $host = (string) wp_parse_url($base_url, PHP_URL_HOST);
        if ($host === '') {
            return '';
        }

        if (strpos($raw_url, '//') === 0) {
            return esc_url_raw(($scheme !== '' ? $scheme : 'https') . ':' . $raw_url);
        }

        if (strpos($raw_url, '/') === 0) {
            return esc_url_raw(($scheme !== '' ? $scheme : 'https') . '://' . $host . $raw_url);
        }

        $base_path = (string) wp_parse_url($base_url, PHP_URL_PATH);
        $base_dir = '/';
        if ($base_path !== '') {
            $base_dir = trailingslashit(trim((string) dirname($base_path), '.\\'));
            if ($base_dir === '') {
                $base_dir = '/';
            }
        }

        return esc_url_raw(($scheme !== '' ? $scheme : 'https') . '://' . $host . $base_dir . ltrim($raw_url, './'));
    }
}

if (!function_exists('my_theme_get_product_official_documents')) {
    function my_theme_get_product_official_documents($product = null, $force_refresh = false)
    {
        if (!$product instanceof WC_Product) {
            return [];
        }

        $product_id = (int) $product->get_id();
        if ($product_id <= 0) {
            return [];
        }

        $source_url = my_theme_get_product_official_source_url($product);
        if ($source_url === '') {
            return [];
        }

        $cache_key = 'my_theme_product_docs_' . md5($product_id . '|' . $source_url);
        $cached = $force_refresh ? false : get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        $response = wp_remote_get($source_url, [
            'timeout' => 20,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; PaintStoreBot/1.0)',
            ],
        ]);
        if (is_wp_error($response)) {
            set_transient($cache_key, [], 6 * HOUR_IN_SECONDS);
            return [];
        }

        $html = (string) wp_remote_retrieve_body($response);
        if ($html === '') {
            set_transient($cache_key, [], 6 * HOUR_IN_SECONDS);
            return [];
        }

        $brand_slug = function_exists('my_theme_get_product_brand_slug')
            ? sanitize_title((string) my_theme_get_product_brand_slug($product))
            : '';
        $source_slug = sanitize_title((string) basename((string) wp_parse_url($source_url, PHP_URL_PATH)));
        $token_source = implode(' ', array_filter([
            $product->get_name(),
            $product->get_slug(),
            $source_slug,
        ]));
        $raw_tokens = preg_split('/\s+/', my_theme_normalize_search_text($token_source));
        $stop_tokens = [
            'weber', 'dulux', 'maxilite', 'son', 'nuoc', 'noi', 'that', 'ngoai', 'mat',
            'be', 'cao', 'cap', 'lot', 'chat', 'kg', 'ml', 'lit', 'gach', 'keo',
            'tu', 'tren', 'cho', 'voi', 'mau',
        ];
        $match_tokens = [];
        foreach ((array) $raw_tokens as $token) {
            $token = trim((string) $token);
            if ($token === '' || strlen($token) < 3 || ctype_digit($token) || in_array($token, $stop_tokens, true)) {
                continue;
            }
            $match_tokens[$token] = $token;
        }
        $match_tokens = array_values($match_tokens);

        $build_doc_label = static function ($text, $url): string {
            $label = trim(html_entity_decode(wp_strip_all_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $label_norm = my_theme_normalize_search_text($label);
            if ($label === '' || in_array($label_norm, ['tai ve', 'download'], true)) {
                $path = (string) wp_parse_url((string) $url, PHP_URL_PATH);
                $label = urldecode((string) pathinfo($path, PATHINFO_FILENAME));
                $label = preg_replace('/[_\-]+/', ' ', $label);
                $label = trim((string) $label);
            }
            if ($label === '') {
                $label = 'PDF hãng';
            }
            return $label;
        };

        $score_doc = static function ($label, $url, $brand_slug, array $match_tokens): int {
            $haystack = my_theme_normalize_search_text($label . ' ' . urldecode((string) $url));
            $score = 0;
            foreach ($match_tokens as $token) {
                if (strpos($haystack, $token) !== false) {
                    $score += 3;
                }
            }
            if (preg_match('/\b(tds|datasheet|technical data|test report|msds|sds)\b/i', $haystack)) {
                $score += 2;
            }
            if (strpos($haystack, 'proposal') !== false) {
                $score -= 3;
            }
            if ($brand_slug === 'weber' && strpos($haystack, 'weber') !== false) {
                $score += 1;
            }
            return $score;
        };

        $detect_doc_type = static function ($label, $url): array {
            $haystack = my_theme_normalize_search_text($label . ' ' . urldecode((string) $url));
            $type = 'PDF hãng';
            if (
                strpos($haystack, 'tds') !== false ||
                strpos($haystack, 'technical data') !== false ||
                strpos($haystack, 'datasheet') !== false ||
                preg_match('/(?:^|\s)ds(?:\s|$)/', $haystack)
            ) {
                $type = 'TDS';
            } elseif (strpos($haystack, 'test report') !== false) {
                $type = 'Test report';
            } elseif (preg_match('/\b(msds|sds)\b/i', $haystack)) {
                $type = 'SDS';
            }

            $lang = '';
            if (preg_match('/(?:^|[_\-\s\/])(vi|vn vi)(?:[_\-\s\/]|$)/i', $haystack)) {
                $lang = 'VI';
            } elseif (preg_match('/(?:^|[_\-\s\/])(en|vn en)(?:[_\-\s\/]|$)/i', $haystack)) {
                $lang = 'EN';
            }

            return [$type, $lang];
        };

        $candidates = [];
        $seen_urls = [];
        if (preg_match_all('/<a[^>]+href=(["\'])([^"\']+\.pdf(?:\?[^"\']*)?)\1[^>]*>([\s\S]*?)<\/a>/i', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $row) {
                $pdf_url = my_theme_make_absolute_source_url($source_url, (string) ($row[2] ?? ''));
                if ($pdf_url === '' || isset($seen_urls[$pdf_url])) {
                    continue;
                }
                $seen_urls[$pdf_url] = true;

                $label = $build_doc_label((string) ($row[3] ?? ''), $pdf_url);
                $score = $score_doc($label, $pdf_url, $brand_slug, $match_tokens);
                [$type, $lang] = $detect_doc_type($label, $pdf_url);

                $candidates[] = [
                    'url' => $pdf_url,
                    'label' => $label,
                    'type' => $type,
                    'lang' => $lang,
                    'score' => $score,
                ];
            }
        }

        if (empty($candidates) && preg_match_all('/https?:\/\/[^"\'\s<>]+\.pdf(?:\?[^"\'\s<>]*)?/i', $html, $matches)) {
            foreach ((array) ($matches[0] ?? []) as $raw_pdf_url) {
                $pdf_url = esc_url_raw((string) $raw_pdf_url);
                if ($pdf_url === '' || isset($seen_urls[$pdf_url])) {
                    continue;
                }
                $seen_urls[$pdf_url] = true;

                $label = $build_doc_label('', $pdf_url);
                $score = $score_doc($label, $pdf_url, $brand_slug, $match_tokens);
                [$type, $lang] = $detect_doc_type($label, $pdf_url);

                $candidates[] = [
                    'url' => $pdf_url,
                    'label' => $label,
                    'type' => $type,
                    'lang' => $lang,
                    'score' => $score,
                ];
            }
        }

        if (empty($candidates)) {
            set_transient($cache_key, [], 6 * HOUR_IN_SECONDS);
            return [];
        }

        if (in_array($brand_slug, ['dulux', 'maxilite'], true)) {
            if (count($candidates) > 4) {
                $candidates = array_values(array_filter($candidates, static function (array $item): bool {
                    return (int) ($item['score'] ?? 0) > 0;
                }));
            }
        } else {
            $candidates = array_values(array_filter($candidates, static function (array $item): bool {
                return (int) ($item['score'] ?? 0) >= 5;
            }));
        }

        if (empty($candidates)) {
            set_transient($cache_key, [], 6 * HOUR_IN_SECONDS);
            return [];
        }

        usort($candidates, static function (array $a, array $b): int {
            $score_cmp = ((int) ($b['score'] ?? 0)) <=> ((int) ($a['score'] ?? 0));
            if ($score_cmp !== 0) {
                return $score_cmp;
            }
            return strcmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
        });

        $candidates = array_slice($candidates, 0, 4);
        set_transient($cache_key, $candidates, 24 * HOUR_IN_SECONDS);

        return $candidates;
    }
}

if (!function_exists('my_theme_get_catalog_completeness_report')) {
    function my_theme_get_catalog_completeness_report($force_refresh = false)
    {
        static $request_cache = [];
        $cache_key = $force_refresh ? 'refresh' : 'default';
        if (isset($request_cache[$cache_key]) && is_array($request_cache[$cache_key])) {
            return $request_cache[$cache_key];
        }

        $transient_key = 'my_theme_catalog_completeness_report_v1';
        if (!$force_refresh) {
            $cached = get_transient($transient_key);
            if (is_array($cached) && !empty($cached['rows'])) {
                $request_cache[$cache_key] = $cached;
                return $cached;
            }
        }

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

        $product_ids = function_exists('my_theme_get_catalog_visible_product_ids')
            ? my_theme_get_catalog_visible_product_ids(false)
            : [];
        $product_ids = array_values(array_filter(array_map('intval', (array) $product_ids), static function (int $id): bool {
            return $id > 0;
        }));

        $brand_counts = [];
        $brand_missing_price = [];
        $brand_missing_pack = [];
        $brand_low_res = [];
        $rows = [];

        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            if (!$product instanceof WC_Product) {
                continue;
            }

            $slug = sanitize_title((string) $product->get_slug());
            $brand = function_exists('my_theme_get_product_brand_slug')
                ? sanitize_title((string) my_theme_get_product_brand_slug($product))
                : '';
            $brand = $brand !== '' ? $brand : 'unknown';
            $brand_counts[$brand] = isset($brand_counts[$brand]) ? ((int) $brand_counts[$brand] + 1) : 1;

            $price = trim((string) $product->get_price());
            $thumb_id = (int) $product->get_image_id();
            $source_url = trim((string) get_post_meta($product_id, '_official_source_url', true));
            if ($source_url === '') {
                $source_url = trim((string) get_post_meta($product_id, '_official_source_page', true));
            }

            $capacity_list = trim((string) get_post_meta($product_id, '_display_capacity_list', true));
            $weight_list = trim((string) get_post_meta($product_id, '_display_weight_list', true));
            $pack_list = trim((string) get_post_meta($product_id, '_display_pack_list', true));
            $has_pack = ($capacity_list !== '' || $weight_list !== '' || $pack_list !== '');

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
            $mapped_w = 0;
            $mapped_h = 0;
            if (is_array($map_row)) {
                $mapped_local_file = trim((string) ($map_row['local_file'] ?? ''));
                if ($mapped_local_file !== '') {
                    $mapped_path = wp_normalize_path(trailingslashit($tools_root) . ltrim($mapped_local_file, '/'));
                    if (!file_exists($mapped_path)) {
                        $mapped_path = wp_normalize_path(trailingslashit($theme_root) . ltrim($mapped_local_file, '/'));
                    }
                    if (file_exists($mapped_path)) {
                        [$mapped_w, $mapped_h] = $get_image_dimensions($mapped_path);
                    }
                }
            }

            $thumb_area = $thumb_w * $thumb_h;
            $mapped_area = $mapped_w * $mapped_h;
            [$scaled_mapped_w, $scaled_mapped_h] = $get_wp_scaled_dimensions($mapped_w, $mapped_h);
            $scaled_match = (
                $scaled_mapped_w > 0 &&
                $scaled_mapped_h > 0 &&
                abs($thumb_w - $scaled_mapped_w) <= 8 &&
                abs($thumb_h - $scaled_mapped_h) <= 8
            );
            $has_better_local_image = (
                $mapped_area > 0 &&
                !$scaled_match &&
                ($thumb_area <= 0 || $mapped_area > ($thumb_area * 1.2))
            );

            $missing_price = ($price === '' || (float) $price <= 0);
            $low_res = ($thumb_id <= 0 || $thumb_w < 320 || $thumb_h < 320);
            $missing_source = ($source_url === '');

            if ($missing_price) {
                $brand_missing_price[$brand] = isset($brand_missing_price[$brand]) ? ((int) $brand_missing_price[$brand] + 1) : 1;
            }
            if (!$has_pack) {
                $brand_missing_pack[$brand] = isset($brand_missing_pack[$brand]) ? ((int) $brand_missing_pack[$brand] + 1) : 1;
            }
            if ($low_res) {
                $brand_low_res[$brand] = isset($brand_low_res[$brand]) ? ((int) $brand_low_res[$brand] + 1) : 1;
            }

            $issues = [];
            if ($missing_price) {
                $issues[] = 'missing_price';
            }
            if (!$has_pack) {
                $issues[] = 'missing_pack';
            }
            if ($low_res) {
                $issues[] = 'low_res';
            }
            if ($missing_source) {
                $issues[] = 'missing_source';
            }
            if ($has_better_local_image) {
                $issues[] = 'better_local_image';
            }

            $rows[] = [
                'id' => $product_id,
                'slug' => $slug,
                'name' => trim((string) $product->get_name()),
                'brand' => $brand,
                'price' => $price,
                'has_price' => !$missing_price,
                'has_pack' => $has_pack,
                'low_res' => $low_res,
                'missing_source' => $missing_source,
                'has_better_local_image' => $has_better_local_image,
                'image_width' => $thumb_w,
                'image_height' => $thumb_h,
                'mapped_image_width' => $mapped_w,
                'mapped_image_height' => $mapped_h,
                'capacity_list' => $capacity_list,
                'weight_list' => $weight_list,
                'pack_list' => $pack_list,
                'source_url' => $source_url,
                'issues' => $issues,
                'edit_url' => get_edit_post_link($product_id, ''),
                'view_url' => get_permalink($product_id),
            ];
        }

        usort($rows, static function (array $a, array $b): int {
            $issue_cmp = count((array) ($b['issues'] ?? [])) <=> count((array) ($a['issues'] ?? []));
            if ($issue_cmp !== 0) {
                return $issue_cmp;
            }
            $brand_cmp = strcmp((string) ($a['brand'] ?? ''), (string) ($b['brand'] ?? ''));
            if ($brand_cmp !== 0) {
                return $brand_cmp;
            }
            return strcmp((string) ($a['slug'] ?? ''), (string) ($b['slug'] ?? ''));
        });

        $report = [
            'generated_at' => current_time('mysql'),
            'summary' => [
                'live_total' => count($rows),
                'missing_price_total' => count(array_filter($rows, static function (array $row): bool {
                    return empty($row['has_price']);
                })),
                'missing_pack_total' => count(array_filter($rows, static function (array $row): bool {
                    return empty($row['has_pack']);
                })),
                'low_res_total' => count(array_filter($rows, static function (array $row): bool {
                    return !empty($row['low_res']);
                })),
                'missing_source_total' => count(array_filter($rows, static function (array $row): bool {
                    return !empty($row['missing_source']);
                })),
                'better_local_image_total' => count(array_filter($rows, static function (array $row): bool {
                    return !empty($row['has_better_local_image']);
                })),
                'brand_counts' => $brand_counts,
                'brand_missing_price' => $brand_missing_price,
                'brand_missing_pack' => $brand_missing_pack,
                'brand_low_res' => $brand_low_res,
            ],
            'rows' => $rows,
        ];

        set_transient($transient_key, $report, 15 * MINUTE_IN_SECONDS);
        $request_cache[$cache_key] = $report;

        return $report;
    }
}

if (!function_exists('my_theme_get_catalog_qa_row')) {
    function my_theme_get_catalog_qa_row($product_id, $force_refresh = false)
    {
        $product_id = (int) $product_id;
        if ($product_id <= 0) {
            return [];
        }

        $report = my_theme_get_catalog_completeness_report($force_refresh);
        $rows = isset($report['rows']) && is_array($report['rows']) ? $report['rows'] : [];
        foreach ($rows as $row) {
            if ((int) ($row['id'] ?? 0) === $product_id) {
                return is_array($row) ? $row : [];
            }
        }

        return [];
    }
}

if (!function_exists('my_theme_filter_catalog_qa_rows')) {
    function my_theme_filter_catalog_qa_rows(array $rows, $issue_filter = 'all', $brand_filter = '')
    {
        $issue_filter = sanitize_key((string) $issue_filter);
        $brand_filter = sanitize_title((string) $brand_filter);

        return array_values(array_filter($rows, static function (array $row) use ($issue_filter, $brand_filter): bool {
            if ($issue_filter !== 'all' && !in_array($issue_filter, (array) ($row['issues'] ?? []), true)) {
                return false;
            }
            if ($brand_filter !== '' && sanitize_title((string) ($row['brand'] ?? '')) !== $brand_filter) {
                return false;
            }
            return true;
        }));
    }
}

if (!function_exists('my_theme_get_catalog_qa_priority_meta')) {
    function my_theme_get_catalog_qa_priority_meta(array $row, $focus_issue = 'all')
    {
        $focus_issue = sanitize_key((string) $focus_issue);
        $issues = array_values(array_filter(array_map('sanitize_key', (array) ($row['issues'] ?? []))));
        $issue_count = count($issues);
        $brand = sanitize_title((string) ($row['brand'] ?? ''));
        $score = 0;
        $reasons = [];

        $pack_labels = [];
        foreach (['capacity_list', 'weight_list', 'pack_list'] as $key) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value === '') {
                continue;
            }
            foreach (preg_split('/\s*\|\s*/', $value) as $label) {
                $label = trim((string) $label);
                if ($label !== '') {
                    $pack_labels[$label] = true;
                }
            }
        }

        $pack_option_count = count($pack_labels);
        $has_source = !empty($row['source_url']);
        $has_price = !empty($row['has_price']);
        $is_low_res = !empty($row['low_res']);
        $has_better_local_image = !empty($row['has_better_local_image']);
        $image_width = (int) ($row['image_width'] ?? 0);
        $image_height = (int) ($row['image_height'] ?? 0);

        if ($has_source) {
            $score += 24;
            $reasons[] = 'co nguon hang';
        }

        if ($issue_count <= 1) {
            $score += 18;
            $reasons[] = 'it loi kem theo';
        } elseif ($issue_count === 2) {
            $score += 10;
        }

        if ($pack_option_count === 1) {
            $score += 18;
            $reasons[] = '1 quy cach';
        } elseif ($pack_option_count > 1) {
            $score += 8;
        }

        if ($focus_issue === 'missing_price') {
            if ($brand === 'weber') {
                $score += 14;
                $reasons[] = 'weber de doi gia hon';
            } elseif (in_array($brand, ['dulux', 'maxilite'], true)) {
                $score += 8;
            }

            if (!$is_low_res) {
                $score += 6;
            }

            if ($has_price) {
                $score -= 30;
            }
        } elseif ($focus_issue === 'low_res') {
            if ($has_better_local_image) {
                $score += 28;
                $reasons[] = 'da co anh local tot hon';
            }

            if ($image_width > 0 && $image_height > 0 && ($image_width <= 160 || $image_height <= 160)) {
                $score += 18;
                $reasons[] = 'anh rat nho';
            } elseif ($image_width > 0 && $image_height > 0 && ($image_width < 320 || $image_height < 320)) {
                $score += 10;
            }

            if ($brand === 'dulux' || $brand === 'maxilite') {
                $score += 10;
                $reasons[] = 'nhom anh Akzo can xu ly';
            }
        } else {
            if (in_array('missing_price', $issues, true) && $has_source) {
                $score += 10;
            }
            if (in_array('low_res', $issues, true) && $has_better_local_image) {
                $score += 10;
            }
        }

        $score = max(0, min(100, $score));
        if ($score >= 70) {
            $label = 'Cao';
        } elseif ($score >= 45) {
            $label = 'Trung binh';
        } else {
            $label = 'Thap';
        }

        return [
            'score' => $score,
            'label' => $label,
            'reason' => implode(', ', array_slice(array_values(array_unique($reasons)), 0, 3)),
            'pack_option_count' => $pack_option_count,
        ];
    }
}

if (!function_exists('my_theme_get_site_readiness_snapshot')) {
    function my_theme_get_site_readiness_snapshot($force_refresh = false)
    {
        $report = my_theme_get_catalog_completeness_report($force_refresh);
        $summary = isset($report['summary']) && is_array($report['summary']) ? $report['summary'] : [];

        $published_posts = 0;
        $post_counts = wp_count_posts('post');
        if (is_object($post_counts) && isset($post_counts->publish)) {
            $published_posts = max(0, (int) $post_counts->publish);
        }

        $blog_public = ((int) get_option('blog_public', 1) === 1);
        $live_total = (int) ($summary['live_total'] ?? 0);
        $missing_price = (int) ($summary['missing_price_total'] ?? 0);
        $low_res = (int) ($summary['low_res_total'] ?? 0);
        $missing_source = (int) ($summary['missing_source_total'] ?? 0);

        $issues = [];
        if (!$blog_public) {
            $issues[] = [
                'severity' => 'warning',
                'label' => 'Search indexing đang tắt',
                'detail' => 'WordPress đang bật Discourage search engines.',
                'url' => admin_url('options-reading.php'),
                'cta' => 'Mở Reading Settings',
            ];
        }
        if ($missing_price > 0) {
            $issues[] = [
                'severity' => 'warning',
                'label' => 'Catalog còn thiếu giá',
                'detail' => sprintf('%d sản phẩm vẫn chưa có giá thật.', $missing_price),
                'url' => add_query_arg(['page' => 'my-theme-catalog-qa', 'issue' => 'missing_price'], admin_url('admin.php')),
                'cta' => 'Mở Thiếu giá',
            ];
        }
        if ($low_res > 0) {
            $issues[] = [
                'severity' => 'warning',
                'label' => 'Catalog còn ảnh nhỏ',
                'detail' => sprintf('%d sản phẩm đang dùng ảnh độ phân giải thấp.', $low_res),
                'url' => add_query_arg(['page' => 'my-theme-catalog-qa', 'issue' => 'low_res'], admin_url('admin.php')),
                'cta' => 'Mở Ảnh nhỏ',
            ];
        }
        if ($missing_source > 0) {
            $issues[] = [
                'severity' => 'info',
                'label' => 'Một số sản phẩm thiếu nguồn official',
                'detail' => sprintf('%d sản phẩm chưa có nguồn để đối chiếu.', $missing_source),
                'url' => add_query_arg(['page' => 'my-theme-catalog-qa', 'issue' => 'missing_source'], admin_url('admin.php')),
                'cta' => 'Mở Thiếu nguồn',
            ];
        }
        if ($published_posts < 10) {
            $issues[] = [
                'severity' => 'info',
                'label' => 'Blog còn mỏng',
                'detail' => sprintf('Hiện mới có %d bài viết publish.', $published_posts),
                'url' => admin_url('edit.php'),
                'cta' => 'Mở bài viết',
            ];
        }

        return [
            'blog_public' => $blog_public,
            'published_posts' => $published_posts,
            'live_total' => $live_total,
            'missing_price_total' => $missing_price,
            'low_res_total' => $low_res,
            'missing_source_total' => $missing_source,
            'issues' => $issues,
            'generated_at' => (string) ($report['generated_at'] ?? current_time('mysql')),
        ];
    }
}

if (!function_exists('my_theme_parse_admin_price_number')) {
    function my_theme_parse_admin_price_number($raw)
    {
        $digits = preg_replace('/\D+/', '', (string) $raw);
        if ($digits === '') {
            return 0.0;
        }
        return (float) $digits;
    }
}

if (!function_exists('my_theme_parse_admin_price_map_string')) {
    function my_theme_parse_admin_price_map_string($raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/[\r\n|;]+/', $raw);
        $map = [];
        foreach ((array) $parts as $part) {
            $part = trim((string) $part);
            if ($part === '' || strpos($part, ':') === false) {
                continue;
            }

            [$label_raw, $price_raw] = array_map('trim', explode(':', $part, 2));
            if ($label_raw === '' || $price_raw === '') {
                continue;
            }

            $parsed_label = my_theme_parse_pack_label($label_raw);
            if (!$parsed_label) {
                continue;
            }

            $price_value = my_theme_parse_admin_price_number($price_raw);
            if ($price_value <= 0) {
                continue;
            }

            $map[$parsed_label['label']] = $price_value;
        }

        if (empty($map)) {
            return [];
        }

        uksort($map, 'my_theme_compare_pack_labels');
        return $map;
    }
}

if (!function_exists('my_theme_apply_manual_price_data_to_product')) {
    function my_theme_apply_manual_price_data_to_product($product, $base_price = 0.0, array $price_map = [])
    {
        if (!$product instanceof WC_Product) {
            return false;
        }

        $base_price = (float) $base_price;
        $dirty = false;

        if (!empty($price_map)) {
            uksort($price_map, 'my_theme_compare_pack_labels');
            $map_parts = [];
            $labels = array_keys($price_map);
            foreach ($price_map as $label => $price_value) {
                $map_parts[] = $label . ':' . (float) $price_value;
            }

            $product->update_meta_data('_capacity_price_map', implode(' | ', $map_parts));

            $capacity_labels = my_theme_sort_pack_labels($labels, 'L');
            $weight_labels = my_theme_sort_pack_labels($labels, 'kg');

            if (!empty($capacity_labels)) {
                $product->update_meta_data('_display_capacity_list', implode(' | ', $capacity_labels));
            }
            if (!empty($weight_labels)) {
                $product->update_meta_data('_display_weight_list', implode(' | ', $weight_labels));
                $first_weight = my_theme_parse_pack_label($weight_labels[0]);
                if ($first_weight && ($first_weight['unit'] ?? '') === 'kg') {
                    $product->set_weight((string) $first_weight['value']);
                }
            }

            $base_price = min(array_map('floatval', array_values($price_map)));
            $dirty = true;
        }

        if ($base_price > 0) {
            $product->set_regular_price((string) $base_price);
            $product->set_price((string) $base_price);
            $product->set_sale_price('');
            $dirty = true;
        }

        return $dirty;
    }
}

if (!function_exists('my_theme_get_allowed_remote_image_hosts')) {
    function my_theme_get_allowed_remote_image_hosts()
    {
        $hosts = [
            'dulux.vn',
            'maxilite.com.vn',
            'jotun.com',
            'jotun.com.vn',
            'nipponpaint.com.vn',
            'kova.com.vn',
            'toa.com.vn',
            'sika.com',
            'sika.com.vn',
            'apollosilicone.vn',
            'weber.com',
            'vn.weber',
            'commons.wikimedia.org',
            'upload.wikimedia.org',
        ];

        $hosts = array_values(array_filter(array_map(static function ($host): string {
            $host = strtolower(trim((string) $host));
            $host = preg_replace('/^www\./', '', $host);
            return $host !== '' ? $host : '';
        }, $hosts)));

        return array_values(array_unique(apply_filters('my_theme_allowed_remote_image_hosts', $hosts)));
    }
}

if (!function_exists('my_theme_is_allowed_remote_image_url')) {
    function my_theme_is_allowed_remote_image_url($image_url)
    {
        $image_url = esc_url_raw((string) $image_url);
        if ($image_url === '') {
            return false;
        }

        $parts = wp_parse_url($image_url);
        if (!is_array($parts)) {
            return false;
        }

        $scheme = isset($parts['scheme']) ? strtolower((string) $parts['scheme']) : '';
        $host = isset($parts['host']) ? strtolower((string) $parts['host']) : '';
        $host = preg_replace('/^www\./', '', $host);
        if ($scheme !== 'https' || $host === '') {
            return false;
        }

        foreach (my_theme_get_allowed_remote_image_hosts() as $allowed_host) {
            $allowed_host = strtolower((string) $allowed_host);
            if ($allowed_host === '') {
                continue;
            }
            if ($host === $allowed_host || substr($host, -1 - strlen($allowed_host)) === '.' . $allowed_host) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('my_theme_validate_downloaded_image_file')) {
    function my_theme_validate_downloaded_image_file($tmp_file)
    {
        $tmp_file = (string) $tmp_file;
        if ($tmp_file === '' || !file_exists($tmp_file)) {
            return false;
        }

        $size_bytes = (int) @filesize($tmp_file);
        if ($size_bytes <= 0 || $size_bytes > 15 * MB_IN_BYTES) {
            return false;
        }

        $mime = function_exists('wp_get_image_mime') ? (string) wp_get_image_mime($tmp_file) : '';
        $allowed_mimes = [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
            'image/avif',
        ];
        if ($mime === '' || !in_array($mime, $allowed_mimes, true)) {
            return false;
        }

        $image_size = @getimagesize($tmp_file);
        return is_array($image_size) && !empty($image_size[0]) && !empty($image_size[1]);
    }
}

if (!function_exists('my_theme_require_admin_get_action_nonce')) {
    function my_theme_require_admin_get_action_nonce($action)
    {
        $action = sanitize_key((string) $action);
        if ($action === '') {
            wp_die('Invalid protected action.');
        }

        $nonce_action = 'my_theme_' . $action;
        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash((string) $_GET['_wpnonce'])) : '';
        if ($nonce !== '' && wp_verify_nonce($nonce, $nonce_action)) {
            return;
        }

        $retry_url = wp_nonce_url(remove_query_arg('_wpnonce'), $nonce_action);
        wp_die(
            'Protected action requires a valid nonce. ' .
            '<a href="' . esc_url($retry_url) . '">Tiếp tục bằng liên kết đã ký</a>.'
        );
    }
}

if (!function_exists('my_theme_import_remote_image_for_product')) {
    function my_theme_import_remote_image_for_product($product, $image_url)
    {
        if (!$product instanceof WC_Product) {
            return 0;
        }

        $image_url = esc_url_raw((string) $image_url);
        if ($image_url === '' || !my_theme_is_allowed_remote_image_url($image_url)) {
            return 0;
        }

        if (!function_exists('download_url')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if (!function_exists('media_handle_sideload')) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $existing = get_posts([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_key' => '_official_remote_url',
            'meta_value' => $image_url,
        ]);
        $attachment_id = !empty($existing) ? (int) $existing[0] : 0;

        if ($attachment_id <= 0) {
            $tmp = download_url($image_url, 45);
            if (is_wp_error($tmp)) {
                return 0;
            }

            if (!my_theme_validate_downloaded_image_file($tmp)) {
                @unlink($tmp);
                return 0;
            }

            $path = (string) wp_parse_url($image_url, PHP_URL_PATH);
            $filename = sanitize_file_name((string) wp_basename($path));
            if ($filename === '' || strpos($filename, '.') === false) {
                $filename = sanitize_file_name(sanitize_title((string) $product->get_name()) . '.jpg');
            }

            $file_array = [
                'name' => $filename,
                'tmp_name' => $tmp,
            ];

            $attachment_id = media_handle_sideload($file_array, $product->get_id(), (string) $product->get_name());
            if (is_wp_error($attachment_id)) {
                @unlink($tmp);
                return 0;
            }

            $attachment_id = (int) $attachment_id;
            update_post_meta($attachment_id, '_official_remote_url', $image_url);
        }

        if ($attachment_id <= 0) {
            return 0;
        }

        set_post_thumbnail($product->get_id(), $attachment_id);
        update_post_meta($product->get_id(), '_official_source_image', $image_url);
        update_post_meta($product->get_id(), '_official_image_synced_at', gmdate('c'));

        $alt = trim((string) $product->get_name());
        if ($alt !== '') {
            update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt);
            wp_update_post([
                'ID' => $attachment_id,
                'post_title' => $alt,
            ]);
        }

        return $attachment_id;
    }
}

if (!function_exists('my_theme_get_catalog_qa_product_ids_by_issue')) {
    function my_theme_get_catalog_qa_product_ids_by_issue($issue)
    {
        $issue = sanitize_key((string) $issue);
        if ($issue === '') {
            return [];
        }

        $report = my_theme_get_catalog_completeness_report();
        $rows = isset($report['rows']) && is_array($report['rows']) ? $report['rows'] : [];
        $ids = [];
        foreach ($rows as $row) {
            if (in_array($issue, (array) ($row['issues'] ?? []), true)) {
                $ids[] = (int) ($row['id'] ?? 0);
            }
        }

        return array_values(array_filter(array_unique($ids)));
    }
}

add_action('admin_init', function () {
    if (!is_admin() || empty($_GET['page']) || $_GET['page'] !== 'my-theme-catalog-qa' || empty($_GET['export'])) {
        return;
    }
    if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
        wp_die('Permission denied.');
    }
    if (!in_array($_GET['export'], ['csv', 'price-template'], true)) {
        return;
    }
    check_admin_referer('my_theme_catalog_qa_export');

    $issue_filter = isset($_GET['issue']) ? sanitize_key((string) $_GET['issue']) : 'all';
    $brand_filter = isset($_GET['brand']) ? sanitize_title((string) $_GET['brand']) : '';
    $report = my_theme_get_catalog_completeness_report(!empty($_GET['refresh']));
    $rows = isset($report['rows']) && is_array($report['rows']) ? $report['rows'] : [];
    $rows = my_theme_filter_catalog_qa_rows($rows, $issue_filter, $brand_filter);
    usort($rows, static function (array $a, array $b) use ($issue_filter): int {
        $a_priority = my_theme_get_catalog_qa_priority_meta($a, $issue_filter);
        $b_priority = my_theme_get_catalog_qa_priority_meta($b, $issue_filter);
        $score_cmp = ((int) ($b_priority['score'] ?? 0)) <=> ((int) ($a_priority['score'] ?? 0));
        if ($score_cmp !== 0) {
            return $score_cmp;
        }

        return strcmp((string) ($a['slug'] ?? ''), (string) ($b['slug'] ?? ''));
    });

    nocache_headers();
    header('Content-Type: text/csv; charset=UTF-8');
    $filename_prefix = $_GET['export'] === 'price-template' ? 'catalog-price-template-' : 'catalog-qa-';
    header('Content-Disposition: attachment; filename=' . $filename_prefix . gmdate('Ymd-His') . '.csv');

    $out = fopen('php://output', 'w');
    if ($out === false) {
        exit;
    }

    fwrite($out, chr(239) . chr(187) . chr(191));
    if ($_GET['export'] === 'price-template') {
        fputcsv($out, [
            'ID',
            'Slug',
            'Name',
            'Brand',
            'Issues',
            'Priority',
            'Priority Score',
            'Priority Reason',
            'Current Price',
            'Capacity',
            'Weight',
            'Package',
            'Source URL',
            'Price',
            'PriceMap',
            'Notes',
        ]);

        foreach ($rows as $row) {
            $priority = my_theme_get_catalog_qa_priority_meta($row, $issue_filter);
            fputcsv($out, [
                (int) ($row['id'] ?? 0),
                (string) ($row['slug'] ?? ''),
                (string) ($row['name'] ?? ''),
                (string) ($row['brand'] ?? ''),
                implode('|', array_map('strval', (array) ($row['issues'] ?? []))),
                (string) ($priority['label'] ?? ''),
                (int) ($priority['score'] ?? 0),
                (string) ($priority['reason'] ?? ''),
                (string) ($row['price'] ?? ''),
                (string) ($row['capacity_list'] ?? ''),
                (string) ($row['weight_list'] ?? ''),
                (string) ($row['pack_list'] ?? ''),
                (string) ($row['source_url'] ?? ''),
                '',
                '',
                'Price = gia base; PriceMap = vi du 5L:442500 | 18L:1501500',
            ]);
        }
    } else {
        fputcsv($out, [
            'ID',
            'Slug',
            'Name',
            'Brand',
            'Issues',
            'Priority',
            'Priority Score',
            'Priority Reason',
            'Price',
            'Capacity',
            'Weight',
            'Package',
            'Image Width',
            'Image Height',
            'Current Image URL',
            'Source URL',
            'Edit URL',
            'View URL',
            'Replacement Image URL',
        ]);

        foreach ($rows as $row) {
            $priority = my_theme_get_catalog_qa_priority_meta($row, $issue_filter);
            $image_url = '';
            if (!empty($row['id'])) {
                $thumb_id = get_post_thumbnail_id((int) $row['id']);
                if ($thumb_id > 0) {
                    $image_url = (string) wp_get_attachment_url($thumb_id);
                }
            }
            fputcsv($out, [
                (int) ($row['id'] ?? 0),
                (string) ($row['slug'] ?? ''),
                (string) ($row['name'] ?? ''),
                (string) ($row['brand'] ?? ''),
                implode('|', array_map('strval', (array) ($row['issues'] ?? []))),
                (string) ($priority['label'] ?? ''),
                (int) ($priority['score'] ?? 0),
                (string) ($priority['reason'] ?? ''),
                (string) ($row['price'] ?? ''),
                (string) ($row['capacity_list'] ?? ''),
                (string) ($row['weight_list'] ?? ''),
                (string) ($row['pack_list'] ?? ''),
                (int) ($row['image_width'] ?? 0),
                (int) ($row['image_height'] ?? 0),
                $image_url,
                (string) ($row['source_url'] ?? ''),
                (string) ($row['edit_url'] ?? ''),
                (string) ($row['view_url'] ?? ''),
                '',
            ]);
        }
    }

    fclose($out);
    exit;
});

add_action('admin_init', function () {
    if (!is_admin() || $_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['my_theme_catalog_price_import'])) {
        return;
    }
    if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
        wp_die('Permission denied.');
    }

    check_admin_referer('my_theme_catalog_price_import');

    $redirect_args = [
        'page' => 'my-theme-catalog-qa',
        'issue' => isset($_POST['issue']) ? sanitize_key((string) $_POST['issue']) : 'all',
        'brand' => isset($_POST['brand']) ? sanitize_title((string) $_POST['brand']) : '',
    ];

    if (empty($_FILES['catalog_price_csv']['tmp_name']) || !is_uploaded_file((string) $_FILES['catalog_price_csv']['tmp_name'])) {
        wp_safe_redirect(add_query_arg(array_merge($redirect_args, [
            'imported' => 0,
            'import_error' => 'missing_file',
        ]), admin_url('admin.php')));
        exit;
    }

    $handle = fopen((string) $_FILES['catalog_price_csv']['tmp_name'], 'r');
    if ($handle === false) {
        wp_safe_redirect(add_query_arg(array_merge($redirect_args, [
            'imported' => 0,
            'import_error' => 'open_failed',
        ]), admin_url('admin.php')));
        exit;
    }

    $normalize_header = static function ($value): string {
        $value = my_theme_normalize_search_text((string) $value);
        return str_replace(' ', '_', $value);
    };

    $header_row = fgetcsv($handle);
    if (!is_array($header_row)) {
        fclose($handle);
        wp_safe_redirect(add_query_arg(array_merge($redirect_args, [
            'imported' => 0,
            'import_error' => 'empty_csv',
        ]), admin_url('admin.php')));
        exit;
    }

    if (!empty($header_row[0])) {
        $header_row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header_row[0]);
    }

    $headers = array_map($normalize_header, $header_row);
    $stats = [
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    while (($row = fgetcsv($handle)) !== false) {
        if (!is_array($row) || empty(array_filter($row, static function ($value) {
            return trim((string) $value) !== '';
        }))) {
            continue;
        }

        $assoc = [];
        foreach ($headers as $index => $header_key) {
            if ($header_key === '') {
                continue;
            }
            $assoc[$header_key] = isset($row[$index]) ? trim((string) $row[$index]) : '';
        }

        $product_id = isset($assoc['id']) ? (int) $assoc['id'] : 0;
        $slug = isset($assoc['slug']) ? sanitize_title((string) $assoc['slug']) : '';
        $product = $product_id > 0 ? wc_get_product($product_id) : null;
        if (!$product instanceof WC_Product && $slug !== '') {
            $ids = get_posts([
                'post_type' => 'product',
                'post_status' => ['publish', 'draft', 'pending', 'private'],
                'posts_per_page' => 1,
                'fields' => 'ids',
                'name' => $slug,
            ]);
            if (!empty($ids)) {
                $product = wc_get_product((int) $ids[0]);
            }
        }

        if (!$product instanceof WC_Product) {
            $stats['errors']++;
            continue;
        }

        $base_price = my_theme_parse_admin_price_number($assoc['price'] ?? '');
        $price_map = my_theme_parse_admin_price_map_string($assoc['pricemap'] ?? '');
        if ($base_price <= 0 && empty($price_map)) {
            $stats['skipped']++;
            continue;
        }

        $dirty = my_theme_apply_manual_price_data_to_product($product, $base_price, $price_map);
        if (!$dirty) {
            $stats['skipped']++;
            continue;
        }

        $product->save();
        wc_delete_product_transients($product->get_id());
        $stats['updated']++;
    }

    fclose($handle);

    if (function_exists('my_theme_flush_product_cache_fragments')) {
        my_theme_flush_product_cache_fragments(0);
    }
    delete_transient('my_theme_catalog_completeness_report_v1');

    wp_safe_redirect(add_query_arg(array_merge($redirect_args, [
        'imported' => 1,
        'updated' => (int) $stats['updated'],
        'skipped' => (int) $stats['skipped'],
        'errors' => (int) $stats['errors'],
    ]), admin_url('admin.php')));
    exit;
});

add_action('admin_init', function () {
    if (!is_admin() || $_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['my_theme_catalog_image_import'])) {
        return;
    }
    if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
        wp_die('Permission denied.');
    }

    check_admin_referer('my_theme_catalog_image_import');

    $redirect_args = [
        'page' => 'my-theme-catalog-qa',
        'issue' => isset($_POST['issue']) ? sanitize_key((string) $_POST['issue']) : 'all',
        'brand' => isset($_POST['brand']) ? sanitize_title((string) $_POST['brand']) : '',
    ];

    if (empty($_FILES['catalog_image_csv']['tmp_name']) || !is_uploaded_file((string) $_FILES['catalog_image_csv']['tmp_name'])) {
        wp_safe_redirect(add_query_arg(array_merge($redirect_args, [
            'image_imported' => 0,
            'image_import_error' => 'missing_file',
        ]), admin_url('admin.php')));
        exit;
    }

    $handle = fopen((string) $_FILES['catalog_image_csv']['tmp_name'], 'r');
    if ($handle === false) {
        wp_safe_redirect(add_query_arg(array_merge($redirect_args, [
            'image_imported' => 0,
            'image_import_error' => 'open_failed',
        ]), admin_url('admin.php')));
        exit;
    }

    $normalize_header = static function ($value): string {
        $value = my_theme_normalize_search_text((string) $value);
        return str_replace(' ', '_', $value);
    };

    $header_row = fgetcsv($handle);
    if (!is_array($header_row)) {
        fclose($handle);
        wp_safe_redirect(add_query_arg(array_merge($redirect_args, [
            'image_imported' => 0,
            'image_import_error' => 'empty_csv',
        ]), admin_url('admin.php')));
        exit;
    }

    if (!empty($header_row[0])) {
        $header_row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header_row[0]);
    }

    $headers = array_map($normalize_header, $header_row);
    $stats = [
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    while (($row = fgetcsv($handle)) !== false) {
        if (!is_array($row) || empty(array_filter($row, static function ($value) {
            return trim((string) $value) !== '';
        }))) {
            continue;
        }

        $assoc = [];
        foreach ($headers as $index => $header_key) {
            if ($header_key === '') {
                continue;
            }
            $assoc[$header_key] = isset($row[$index]) ? trim((string) $row[$index]) : '';
        }

        $product_id = isset($assoc['id']) ? (int) $assoc['id'] : 0;
        $slug = isset($assoc['slug']) ? sanitize_title((string) $assoc['slug']) : '';
        $product = $product_id > 0 ? wc_get_product($product_id) : null;
        if (!$product instanceof WC_Product && $slug !== '') {
            $ids = get_posts([
                'post_type' => 'product',
                'post_status' => ['publish', 'draft', 'pending', 'private'],
                'posts_per_page' => 1,
                'fields' => 'ids',
                'name' => $slug,
            ]);
            if (!empty($ids)) {
                $product = wc_get_product((int) $ids[0]);
            }
        }

        if (!$product instanceof WC_Product) {
            $stats['errors']++;
            continue;
        }

        $image_url = '';
        foreach (['replacement_image_url', 'new_image_url', 'image_url', 'official_image_url'] as $key) {
            if (!empty($assoc[$key])) {
                $image_url = esc_url_raw((string) $assoc[$key]);
                break;
            }
        }

        if ($image_url === '') {
            $stats['skipped']++;
            continue;
        }

        $attachment_id = my_theme_import_remote_image_for_product($product, $image_url);
        if ($attachment_id <= 0) {
            $stats['errors']++;
            continue;
        }

        wc_delete_product_transients($product->get_id());
        $stats['updated']++;
    }

    fclose($handle);

    if (function_exists('my_theme_flush_product_cache_fragments')) {
        my_theme_flush_product_cache_fragments(0);
    }
    delete_transient('my_theme_catalog_completeness_report_v1');

    wp_safe_redirect(add_query_arg(array_merge($redirect_args, [
        'image_imported' => 1,
        'updated' => (int) $stats['updated'],
        'skipped' => (int) $stats['skipped'],
        'errors' => (int) $stats['errors'],
    ]), admin_url('admin.php')));
    exit;
});

if (!function_exists('my_theme_render_catalog_qa_admin_page')) {
    function my_theme_render_catalog_qa_admin_page()
    {
        if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
            wp_die('Permission denied.');
        }

        $issue_filter = isset($_GET['issue']) ? sanitize_key((string) $_GET['issue']) : 'all';
        $allowed_filters = ['all', 'missing_price', 'missing_pack', 'low_res', 'missing_source', 'better_local_image'];
        if (!in_array($issue_filter, $allowed_filters, true)) {
            $issue_filter = 'all';
        }

        $brand_filter = isset($_GET['brand']) ? sanitize_title((string) $_GET['brand']) : '';
        $force_refresh = !empty($_GET['refresh']);
        $report = my_theme_get_catalog_completeness_report($force_refresh);
        $summary = isset($report['summary']) && is_array($report['summary']) ? $report['summary'] : [];
        $readiness = my_theme_get_site_readiness_snapshot($force_refresh);
        $rows = isset($report['rows']) && is_array($report['rows']) ? $report['rows'] : [];

        $rows = array_values(array_filter($rows, static function (array $row) use ($issue_filter, $brand_filter): bool {
            if ($issue_filter !== 'all' && !in_array($issue_filter, (array) ($row['issues'] ?? []), true)) {
                return false;
            }
            if ($brand_filter !== '' && sanitize_title((string) ($row['brand'] ?? '')) !== $brand_filter) {
                return false;
            }
            return true;
        }));
        usort($rows, static function (array $a, array $b) use ($issue_filter): int {
            $a_priority = my_theme_get_catalog_qa_priority_meta($a, $issue_filter);
            $b_priority = my_theme_get_catalog_qa_priority_meta($b, $issue_filter);
            $score_cmp = ((int) ($b_priority['score'] ?? 0)) <=> ((int) ($a_priority['score'] ?? 0));
            if ($score_cmp !== 0) {
                return $score_cmp;
            }

            return strcmp((string) ($a['slug'] ?? ''), (string) ($b['slug'] ?? ''));
        });

        $base_url = admin_url('admin.php?page=my-theme-catalog-qa');
        $make_url = static function (array $args = []) use ($base_url): string {
            return add_query_arg($args, $base_url);
        };
        $export_url = wp_nonce_url($make_url([
            'issue' => $issue_filter,
            'brand' => $brand_filter,
            'export' => 'csv',
        ]), 'my_theme_catalog_qa_export');
        $image_review_url = wp_nonce_url($make_url([
            'issue' => 'low_res',
            'brand' => $brand_filter,
            'export' => 'csv',
        ]), 'my_theme_catalog_qa_export');
        $price_template_url = wp_nonce_url($make_url([
            'issue' => $issue_filter === 'all' ? 'missing_price' : $issue_filter,
            'brand' => $brand_filter,
            'export' => 'price-template',
        ]), 'my_theme_catalog_qa_export');

        $issue_labels = [
            'missing_price' => 'Thiếu giá',
            'missing_pack' => 'Thiếu quy cách',
            'low_res' => 'Ảnh nhỏ',
            'missing_source' => 'Thiếu nguồn',
            'better_local_image' => 'Có ảnh local tốt hơn',
        ];

        echo '<div class="wrap">';
        echo '<h1>Catalog QA</h1>';
        echo '<p>Rà soát nhanh tình trạng catalog hiện tại để xử lý nốt giá, ảnh và metadata còn thiếu.</p>';
        if (!empty($_GET['imported'])) {
            echo '<div class="notice notice-success is-dismissible"><p>Import giá hoàn tất. Updated: ' . intval($_GET['updated'] ?? 0) . ' | Skipped: ' . intval($_GET['skipped'] ?? 0) . ' | Errors: ' . intval($_GET['errors'] ?? 0) . '</p></div>';
        } elseif (!empty($_GET['image_imported'])) {
            echo '<div class="notice notice-success is-dismissible"><p>Import ảnh hoàn tất. Updated: ' . intval($_GET['updated'] ?? 0) . ' | Skipped: ' . intval($_GET['skipped'] ?? 0) . ' | Errors: ' . intval($_GET['errors'] ?? 0) . '</p></div>';
        } elseif (!empty($_GET['import_error'])) {
            echo '<div class="notice notice-error is-dismissible"><p>Import giá thất bại: ' . esc_html((string) $_GET['import_error']) . '</p></div>';
        } elseif (!empty($_GET['image_import_error'])) {
            echo '<div class="notice notice-error is-dismissible"><p>Import ảnh thất bại: ' . esc_html((string) $_GET['image_import_error']) . '</p></div>';
        }
        echo '<p>';
        echo '<a class="button button-secondary" href="' . esc_url($make_url(['refresh' => 1, 'issue' => $issue_filter, 'brand' => $brand_filter])) . '">Làm mới dữ liệu</a> ';
        echo '<a class="button button-primary" href="' . esc_url($export_url) . '">Xuất CSV theo bộ lọc</a>';
        echo ' <a class="button button-secondary" href="' . esc_url($price_template_url) . '">Xuất mẫu CSV nhập giá</a>';
        echo ' <a class="button button-secondary" href="' . esc_url($image_review_url) . '">Xuất CSV ảnh nhỏ</a>';
        echo '</p>';

        $readiness_issues = isset($readiness['issues']) && is_array($readiness['issues']) ? $readiness['issues'] : [];
        $readiness_class = empty($readiness_issues) ? ' is-good' : ' is-warning';
        echo '<section class="my-theme-qa-readiness' . esc_attr($readiness_class) . '">';
        echo '<div class="my-theme-qa-readiness__head">';
        echo '<div>';
        echo '<h2>Site Readiness</h2>';
        echo '<p>Kiểm tra nhanh trạng thái public của site, blog và catalog để biết còn thiếu gì trước khi đẩy live.</p>';
        echo '</div>';
        echo '<div class="my-theme-qa-readiness__meta">';
        echo '<span>' . (!empty($readiness['blog_public']) ? 'Indexing: bật' : 'Indexing: tắt') . '</span>';
        echo '<span>Bài blog: ' . intval($readiness['published_posts'] ?? 0) . '</span>';
        echo '<span>Sản phẩm live: ' . intval($readiness['live_total'] ?? 0) . '</span>';
        echo '</div>';
        echo '</div>';
        if (empty($readiness_issues)) {
            echo '<p class="my-theme-qa-readiness__ok">Không thấy blocker lớn ở lớp quản trị hiện tại. Phần còn lại chủ yếu là bổ sung dữ liệu sản phẩm.</p>';
        } else {
            echo '<div class="my-theme-qa-readiness__issues">';
            foreach ($readiness_issues as $readiness_issue) {
                $severity = sanitize_key((string) ($readiness_issue['severity'] ?? 'info'));
                echo '<article class="my-theme-qa-readiness__issue my-theme-qa-readiness__issue--' . esc_attr($severity) . '">';
                echo '<strong>' . esc_html((string) ($readiness_issue['label'] ?? 'Lưu ý')) . '</strong>';
                echo '<p>' . esc_html((string) ($readiness_issue['detail'] ?? '')) . '</p>';
                if (!empty($readiness_issue['url']) && !empty($readiness_issue['cta'])) {
                    echo '<a class="button button-secondary" href="' . esc_url((string) $readiness_issue['url']) . '">' . esc_html((string) $readiness_issue['cta']) . '</a>';
                }
                echo '</article>';
            }
            echo '</div>';
        }
        echo '</section>';

        echo '<div class="my-theme-qa-cards">';
        $cards = [
            'Tổng sản phẩm' => (int) ($summary['live_total'] ?? 0),
            'Thiếu giá' => (int) ($summary['missing_price_total'] ?? 0),
            'Thiếu quy cách' => (int) ($summary['missing_pack_total'] ?? 0),
            'Ảnh nhỏ' => (int) ($summary['low_res_total'] ?? 0),
            'Thiếu nguồn' => (int) ($summary['missing_source_total'] ?? 0),
            'Ảnh local tốt hơn' => (int) ($summary['better_local_image_total'] ?? 0),
        ];
        foreach ($cards as $label => $value) {
            echo '<div class="my-theme-qa-card"><strong>' . esc_html((string) $value) . '</strong><span>' . esc_html($label) . '</span></div>';
        }
        echo '</div>';

        echo '<form class="my-theme-qa-import" method="post" enctype="multipart/form-data" action="' . esc_url($base_url) . '">';
        wp_nonce_field('my_theme_catalog_price_import');
        echo '<input type="hidden" name="my_theme_catalog_price_import" value="1">';
        echo '<input type="hidden" name="issue" value="' . esc_attr($issue_filter) . '">';
        echo '<input type="hidden" name="brand" value="' . esc_attr($brand_filter) . '">';
        echo '<strong>Nhập giá hàng loạt bằng CSV</strong>';
        echo '<p>Dùng file mẫu để điền cột `Price` hoặc `PriceMap`. `PriceMap` nhận format như `5L:442500 | 18L:1501500`. Nếu có `PriceMap`, hệ thống sẽ tự set giá base = mức nhỏ nhất.</p>';
        echo '<div class="my-theme-qa-import-actions">';
        echo '<input type="file" name="catalog_price_csv" accept=".csv,text/csv" required>';
        echo '<button type="submit" class="button button-primary">Nhập CSV giá</button>';
        echo '</div>';
        echo '</form>';
        echo '<form class="my-theme-qa-import" method="post" enctype="multipart/form-data" action="' . esc_url($base_url) . '">';
        wp_nonce_field('my_theme_catalog_image_import');
        echo '<input type="hidden" name="my_theme_catalog_image_import" value="1">';
        echo '<input type="hidden" name="issue" value="' . esc_attr($issue_filter) . '">';
        echo '<input type="hidden" name="brand" value="' . esc_attr($brand_filter) . '">';
        echo '<strong>Nhập ảnh hàng loạt bằng CSV</strong>';
        echo '<p>Dùng file `Xuất CSV theo bộ lọc`, điền cột `Replacement Image URL` bằng ảnh lớn hơn, rồi upload lại tại đây. Hệ thống sẽ sideload ảnh và gắn làm featured image cho sản phẩm khớp `ID` hoặc `Slug`.</p>';
        echo '<div class="my-theme-qa-import-actions">';
        echo '<input type="file" name="catalog_image_csv" accept=".csv,text/csv" required>';
        echo '<button type="submit" class="button button-secondary">Nhập CSV ảnh</button>';
        echo '</div>';
        echo '</form>';

        echo '<div class="my-theme-qa-filters">';
        foreach ($allowed_filters as $filter_key) {
            $label = $filter_key === 'all' ? 'Tất cả' : ($issue_labels[$filter_key] ?? $filter_key);
            $active_class = $filter_key === $issue_filter ? ' is-active' : '';
            echo '<a class="' . esc_attr($active_class) . '" href="' . esc_url($make_url(['issue' => $filter_key, 'brand' => $brand_filter])) . '">' . esc_html($label) . '</a>';
        }
        echo '</div>';

        $brand_counts = isset($summary['brand_counts']) && is_array($summary['brand_counts']) ? $summary['brand_counts'] : [];
        if (!empty($brand_counts)) {
            echo '<div class="my-theme-qa-filters">';
            echo '<a class="' . ($brand_filter === '' ? 'is-active' : '') . '" href="' . esc_url($make_url(['issue' => $issue_filter])) . '">Tất cả hãng</a>';
            foreach ($brand_counts as $brand => $count) {
                $active_class = $brand_filter === $brand ? 'is-active' : '';
                echo '<a class="' . esc_attr($active_class) . '" href="' . esc_url($make_url(['issue' => $issue_filter, 'brand' => $brand])) . '">' . esc_html(ucfirst((string) $brand)) . ' (' . intval($count) . ')</a>';
            }
            echo '</div>';
        }

        echo '<table class="my-theme-qa-table">';
        echo '<thead><tr>';
        echo '<th>Sản phẩm</th>';
        echo '<th>Vấn đề</th>';
        echo '<th>Ưu tiên</th>';
        echo '<th>Quy cách</th>';
        echo '<th>Ảnh</th>';
        echo '<th>Nguồn</th>';
        echo '<th>Thao tác</th>';
        echo '</tr></thead><tbody>';

        if (empty($rows)) {
            echo '<tr><td colspan="7"><span class="my-theme-qa-muted">Không có sản phẩm nào khớp bộ lọc hiện tại.</span></td></tr>';
        } else {
            foreach ($rows as $row) {
                $priority = my_theme_get_catalog_qa_priority_meta($row, $issue_filter);
                $pack_bits = array_values(array_filter([
                    !empty($row['capacity_list']) ? 'Dung tích: ' . $row['capacity_list'] : '',
                    !empty($row['weight_list']) ? 'Khối lượng: ' . $row['weight_list'] : '',
                    !empty($row['pack_list']) ? 'Quy cách: ' . $row['pack_list'] : '',
                ]));

                echo '<tr>';
                echo '<td>';
                echo '<strong>' . esc_html((string) ($row['name'] ?? '')) . '</strong><br>';
                echo '<span class="my-theme-qa-muted">' . esc_html((string) ($row['brand'] ?? '')) . '</span><br>';
                echo '<code>' . esc_html((string) ($row['slug'] ?? '')) . '</code>';
                if (!empty($row['has_price']) && !empty($row['price'])) {
                    echo '<br><span class="my-theme-qa-muted">Giá hiện tại: ' . esc_html(wc_price((float) $row['price'])) . '</span>';
                } else {
                    echo '<br><span class="my-theme-qa-muted">Giá hiện tại: Liên hệ báo giá</span>';
                }
                echo '</td>';

                echo '<td><div class="my-theme-qa-issue-list">';
                foreach ((array) ($row['issues'] ?? []) as $issue) {
                    $label = $issue_labels[$issue] ?? $issue;
                    echo '<span class="my-theme-qa-issue my-theme-qa-issue--' . esc_attr($issue) . '">' . esc_html($label) . '</span>';
                }
                echo '</div></td>';

                echo '<td>';
                echo '<strong>' . esc_html((string) ($priority['label'] ?? '')) . '</strong>';
                echo '<br><span class="my-theme-qa-muted">Score: ' . intval($priority['score'] ?? 0) . '</span>';
                if (!empty($priority['reason'])) {
                    echo '<br><span class="my-theme-qa-muted">' . esc_html((string) $priority['reason']) . '</span>';
                }
                echo '</td>';

                echo '<td>';
                if (empty($pack_bits)) {
                    echo '<span class="my-theme-qa-muted">Chưa có</span>';
                } else {
                    echo '<div class="my-theme-qa-pack-list">';
                    foreach ($pack_bits as $pack_bit) {
                        echo '<span class="my-theme-qa-issue">' . esc_html($pack_bit) . '</span>';
                    }
                    echo '</div>';
                }
                echo '</td>';

                echo '<td>';
                echo '<span>' . intval($row['image_width'] ?? 0) . 'x' . intval($row['image_height'] ?? 0) . '</span>';
                if (!empty($row['has_better_local_image'])) {
                    echo '<br><span class="my-theme-qa-muted">Local map: ' . intval($row['mapped_image_width'] ?? 0) . 'x' . intval($row['mapped_image_height'] ?? 0) . '</span>';
                }
                echo '</td>';

                echo '<td>';
                if (!empty($row['source_url'])) {
                    echo '<a href="' . esc_url((string) $row['source_url']) . '" target="_blank" rel="noopener nofollow">Mở nguồn</a>';
                } else {
                    echo '<span class="my-theme-qa-muted">Chưa có</span>';
                }
                echo '</td>';

                echo '<td><div class="my-theme-qa-actions">';
                if (!empty($row['edit_url'])) {
                    echo '<a class="button button-small" href="' . esc_url((string) $row['edit_url']) . '">Sửa</a>';
                }
                if (!empty($row['view_url'])) {
                    echo '<a class="button button-small" href="' . esc_url((string) $row['view_url']) . '" target="_blank" rel="noopener">Xem</a>';
                }
                echo '</div></td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';
        echo '<p class="my-theme-qa-muted">Cập nhật lần cuối: ' . esc_html((string) ($report['generated_at'] ?? current_time('mysql'))) . '</p>';
        echo '</div>';
    }
}

add_action('admin_menu', function () {
    if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
        return;
    }

    add_submenu_page(
        'woocommerce',
        'Catalog QA',
        'Catalog QA',
        'manage_woocommerce',
        'my-theme-catalog-qa',
        'my_theme_render_catalog_qa_admin_page'
    );
}, 60);

if (!function_exists('my_theme_is_local_environment')) {
    function my_theme_is_local_environment()
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $env = function_exists('wp_get_environment_type') ? (string) wp_get_environment_type() : '';
        if (in_array($env, ['local', 'development'], true)) {
            $cached = true;
            return $cached;
        }

        $request_host = isset($_SERVER['HTTP_HOST']) ? strtolower(trim((string) wp_unslash($_SERVER['HTTP_HOST']))) : '';
        $site_host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
        $hosts = array_values(array_unique(array_filter([$request_host, $site_host])));

        foreach ($hosts as $host) {
            if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
                $cached = true;
                return $cached;
            }
            if ((bool) preg_match('/(\.local|\.test|\.localhost)$/', $host)) {
                $cached = true;
                return $cached;
            }
        }

        $cached = false;
        return $cached;
    }
}

add_action('admin_notices', function () {
    if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
        return;
    }

    if (function_exists('my_theme_is_local_environment') && my_theme_is_local_environment()) {
        return;
    }

    if ((int) get_option('blog_public', 1) === 1) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if ($screen instanceof WP_Screen && strpos((string) $screen->id, 'woocommerce_page_my-theme-catalog-qa') !== false) {
        return;
    }

    echo '<div class="notice notice-warning"><p><strong>Search indexing đang tắt.</strong> WordPress hiện bật <code>Discourage search engines</code>. <a href="' . esc_url(admin_url('options-reading.php')) . '">Mở Reading Settings</a>.</p></div>';
});

add_action('admin_head', function () {
    if (!is_admin() || !function_exists('my_theme_is_local_environment') || !my_theme_is_local_environment()) {
        return;
    }
    ?>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        var hiddenPhrases = [
          'Search indexing đang tắt',
          'Discourage search engines',
          'Trình lập lịch hành động:',
          'past-due action',
          'Cửa hàng chưa sử dụng kết nối an toàn',
          'sử dụng HTTPS',
          'Pinterest for WooCommerce will soon discontinue support',
          'Please update WooCommerce to take advantage'
        ];

        var selectors = [
          '.notice',
          '.update-nag',
          '.woocommerce-message',
          '.woocommerce-layout__notice-list .notice'
        ];

        var normalize = function (text) {
          return String(text || '').replace(/\s+/g, ' ').trim().toLowerCase();
        };

        var hideMatchingNotices = function (root) {
          selectors.forEach(function (selector) {
            var nodes = [];
            if (root.matches && root.matches(selector)) {
              nodes.push(root);
            }
            root.querySelectorAll(selector).forEach(function (node) {
              nodes.push(node);
            });
            nodes.forEach(function (node) {
              var content = normalize(node.textContent);
              if (!content) {
                return;
              }
              var matched = hiddenPhrases.some(function (phrase) {
                return content.indexOf(normalize(phrase)) !== -1;
              });
              if (matched) {
                node.style.display = 'none';
              }
            });
          });
        };

        hideMatchingNotices(document);

        if ('MutationObserver' in window) {
          var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
              mutation.addedNodes.forEach(function (node) {
                if (!node || node.nodeType !== 1) {
                  return;
                }
                hideMatchingNotices(node);
              });
            });
          });
          observer.observe(document.body, { childList: true, subtree: true });
        }
      });
    </script>
    <?php
});

if (!function_exists('my_theme_render_site_readiness_dashboard_widget')) {
    function my_theme_render_site_readiness_dashboard_widget()
    {
        $readiness = my_theme_get_site_readiness_snapshot();
        $issues = isset($readiness['issues']) && is_array($readiness['issues']) ? $readiness['issues'] : [];

        echo '<div class="my-theme-site-readiness-widget">';
        echo '<p><strong>Indexing:</strong> ' . (!empty($readiness['blog_public']) ? 'Bật' : 'Tắt') . '</p>';
        echo '<p><strong>Bài blog:</strong> ' . intval($readiness['published_posts'] ?? 0) . '</p>';
        echo '<p><strong>Thiếu giá:</strong> ' . intval($readiness['missing_price_total'] ?? 0) . '</p>';
        echo '<p><strong>Ảnh nhỏ:</strong> ' . intval($readiness['low_res_total'] ?? 0) . '</p>';
        echo '<p><strong>Thiếu nguồn:</strong> ' . intval($readiness['missing_source_total'] ?? 0) . '</p>';

        if (!empty($issues)) {
            echo '<ul>';
            foreach (array_slice($issues, 0, 4) as $issue) {
                echo '<li>' . esc_html((string) ($issue['label'] ?? 'Lưu ý')) . '</li>';
            }
            echo '</ul>';
        }

        echo '<p><a class="button button-primary" href="' . esc_url(add_query_arg(['page' => 'my-theme-catalog-qa'], admin_url('admin.php'))) . '">Mở Catalog QA</a></p>';
        echo '</div>';
    }
}

add_action('wp_dashboard_setup', function () {
    if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
        return;
    }

    wp_add_dashboard_widget(
        'my_theme_site_readiness',
        'Site Readiness',
        'my_theme_render_site_readiness_dashboard_widget'
    );
});

if (!function_exists('my_theme_render_product_qa_metabox')) {
    function my_theme_render_product_qa_metabox($post)
    {
        $product = wc_get_product($post);
        if (!$product instanceof WC_Product) {
            echo '<p>Không tải được dữ liệu sản phẩm.</p>';
            return;
        }

        $row = my_theme_get_catalog_qa_row($product->get_id());
        $issues = isset($row['issues']) && is_array($row['issues']) ? $row['issues'] : [];
        $source_url = function_exists('my_theme_get_product_official_source_url')
            ? my_theme_get_product_official_source_url($product)
            : '';
        $documents = function_exists('my_theme_get_product_official_documents')
            ? my_theme_get_product_official_documents($product)
            : [];
        $qa_url = add_query_arg([
            'page' => 'my-theme-catalog-qa',
            'brand' => sanitize_title((string) ($row['brand'] ?? '')),
            'issue' => !empty($issues) ? (string) $issues[0] : 'all',
        ], admin_url('admin.php'));

        $issue_labels = [
            'missing_price' => 'Thiếu giá',
            'missing_pack' => 'Thiếu quy cách',
            'low_res' => 'Ảnh nhỏ',
            'missing_source' => 'Thiếu nguồn',
            'better_local_image' => 'Có ảnh local tốt hơn',
        ];

        echo '<div class="my-theme-product-qa">';
        echo '<div>';
        echo '<strong>Trạng thái catalog</strong>';
        if (empty($issues)) {
            echo '<p class="my-theme-product-qa__meta">Sản phẩm này hiện không có cờ lỗi trong audit catalog.</p>';
        } else {
            echo '<div class="my-theme-product-qa__chips">';
            foreach ($issues as $issue) {
                $label = $issue_labels[$issue] ?? $issue;
                echo '<span class="my-theme-product-qa__chip my-theme-product-qa__chip--' . esc_attr($issue) . '">' . esc_html($label) . '</span>';
            }
            echo '</div>';
        }
        echo '</div>';

        echo '<ul class="my-theme-product-qa__list">';
        $pack_summary = implode(' | ', array_values(array_filter([
            !empty($row['capacity_list']) ? 'Dung tích ' . $row['capacity_list'] : '',
            !empty($row['weight_list']) ? 'Khối lượng ' . $row['weight_list'] : '',
            !empty($row['pack_list']) ? 'Quy cách ' . $row['pack_list'] : '',
        ])));
        echo '<li>Giá: ' . (!empty($row['has_price']) && !empty($row['price']) ? wp_kses_post(wc_price((float) $row['price'])) : 'Liên hệ báo giá') . '</li>';
        echo '<li>Quy cách: ' . esc_html($pack_summary !== '' ? $pack_summary : 'Chưa có') . '</li>';
        echo '<li>Ảnh hiện tại: ' . intval($row['image_width'] ?? 0) . 'x' . intval($row['image_height'] ?? 0) . '</li>';
        if (!empty($row['has_better_local_image'])) {
            echo '<li>Ảnh local tốt hơn: ' . intval($row['mapped_image_width'] ?? 0) . 'x' . intval($row['mapped_image_height'] ?? 0) . '</li>';
        }
        echo '</ul>';

        echo '<div class="my-theme-product-qa__actions">';
        if ($source_url !== '') {
            echo '<a class="button button-secondary" href="' . esc_url($source_url) . '" target="_blank" rel="noopener">Mở nguồn hãng</a>';
        }
        if (!empty($row['view_url'])) {
            echo '<a class="button button-secondary" href="' . esc_url((string) $row['view_url']) . '" target="_blank" rel="noopener">Xem trang sản phẩm</a>';
        }
        echo '<a class="button button-secondary" href="' . esc_url($qa_url) . '">Mở Catalog QA</a>';
        echo '</div>';

        if (!empty($documents)) {
            echo '<div>';
            echo '<strong>Tài liệu hãng</strong>';
            echo '<div class="my-theme-product-qa__docs">';
            foreach ($documents as $doc) {
                $doc_url = isset($doc['url']) ? (string) $doc['url'] : '';
                if ($doc_url === '') {
                    continue;
                }
                $type = isset($doc['type']) ? (string) $doc['type'] : 'PDF';
                $lang = isset($doc['lang']) && $doc['lang'] !== '' ? ' • ' . (string) $doc['lang'] : '';
                echo '<a class="my-theme-product-qa__doc" href="' . esc_url($doc_url) . '" target="_blank" rel="noopener">';
                echo '<span>' . esc_html((string) ($doc['label'] ?? 'PDF hãng')) . '</span>';
                echo '<small>' . esc_html($type . $lang) . '</small>';
                echo '</a>';
            }
            echo '</div>';
            echo '</div>';
        }

        echo '</div>';
    }
}

add_action('add_meta_boxes_product', function () {
    if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
        return;
    }

    add_meta_box(
        'my-theme-product-qa',
        'Catalog QA',
        'my_theme_render_product_qa_metabox',
        'product',
        'side',
        'high'
    );
});

if (!function_exists('my_theme_get_catalog_qa_issue_labels')) {
    function my_theme_get_catalog_qa_issue_labels()
    {
        return [
            'missing_price' => 'Thiếu giá',
            'missing_pack' => 'Thiếu quy cách',
            'low_res' => 'Ảnh nhỏ',
            'missing_source' => 'Thiếu nguồn',
            'better_local_image' => 'Ảnh local tốt hơn',
        ];
    }
}

add_filter('manage_edit-product_columns', function ($columns) {
    if (!is_array($columns)) {
        return $columns;
    }

    $result = [];
    foreach ($columns as $key => $label) {
        $result[$key] = $label;
        if ($key === 'name') {
            $result['my_theme_catalog_qa'] = 'Catalog QA';
        }
    }

    if (!isset($result['my_theme_catalog_qa'])) {
        $result['my_theme_catalog_qa'] = 'Catalog QA';
    }

    return $result;
}, 25);

add_action('manage_product_posts_custom_column', function ($column, $post_id) {
    if ($column !== 'my_theme_catalog_qa') {
        return;
    }

    $row = my_theme_get_catalog_qa_row((int) $post_id);
    $issues = isset($row['issues']) && is_array($row['issues']) ? $row['issues'] : [];
    $labels = my_theme_get_catalog_qa_issue_labels();

    echo '<div class="my-theme-admin-qa-col">';
    if (empty($issues)) {
        echo '<span class="my-theme-admin-qa-col__chip">OK</span>';
    } else {
        echo '<div class="my-theme-admin-qa-col__chips">';
        foreach ($issues as $issue) {
            $label = $labels[$issue] ?? $issue;
            echo '<span class="my-theme-admin-qa-col__chip my-theme-admin-qa-col__chip--' . esc_attr($issue) . '">' . esc_html($label) . '</span>';
        }
        echo '</div>';
    }

    $image_text = intval($row['image_width'] ?? 0) . 'x' . intval($row['image_height'] ?? 0);
    echo '<span class="my-theme-admin-qa-col__meta">Ảnh: ' . esc_html($image_text) . '</span>';
    if (!empty($row['source_url'])) {
        echo '<a class="my-theme-admin-qa-col__meta" href="' . esc_url((string) $row['source_url']) . '" target="_blank" rel="noopener nofollow">Mở nguồn</a>';
    }
    echo '</div>';
}, 10, 2);

add_action('admin_enqueue_scripts', function ($hook_suffix) {
    if (!is_admin()) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    $is_catalog_qa_screen = is_string($hook_suffix) && $hook_suffix === 'woocommerce_page_my-theme-catalog-qa';
    $is_product_screen = $screen instanceof WP_Screen && $screen->post_type === 'product';
    $is_dashboard_screen = $screen instanceof WP_Screen && $screen->id === 'dashboard';
    if (!$is_catalog_qa_screen && !$is_product_screen && !$is_dashboard_screen) {
        return;
    }

    $asset = function_exists('my_theme_resolve_theme_asset')
        ? my_theme_resolve_theme_asset('assets/css/admin-catalog-qa.css')
        : null;
    if (!is_array($asset) || empty($asset['uri'])) {
        return;
    }

    wp_enqueue_style(
        'my-theme-admin-catalog-qa',
        $asset['uri'],
        [],
        isset($asset['ver']) ? (string) $asset['ver'] : null
    );
});

add_action('restrict_manage_posts', function () {
    global $typenow;
    if ($typenow !== 'product') {
        return;
    }
    if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
        return;
    }

    $selected = isset($_GET['my_theme_catalog_issue']) ? sanitize_key((string) $_GET['my_theme_catalog_issue']) : '';
    $labels = my_theme_get_catalog_qa_issue_labels();

    echo '<select name="my_theme_catalog_issue">';
    echo '<option value="">Loc Catalog QA</option>';
    foreach ($labels as $issue => $label) {
        echo '<option value="' . esc_attr($issue) . '"' . selected($selected, $issue, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
}, 20);

add_action('pre_get_posts', function ($query) {
    if (!is_admin() || !$query instanceof WP_Query || !$query->is_main_query()) {
        return;
    }

    $post_type = $query->get('post_type');
    if ($post_type !== 'product') {
        return;
    }

    $issue = isset($_GET['my_theme_catalog_issue']) ? sanitize_key((string) $_GET['my_theme_catalog_issue']) : '';
    $labels = my_theme_get_catalog_qa_issue_labels();
    if ($issue === '' || !isset($labels[$issue])) {
        return;
    }

    $ids = my_theme_get_catalog_qa_product_ids_by_issue($issue);
    if (empty($ids)) {
        $ids = [0];
    }

    $query->set('post__in', $ids);
    $query->set('orderby', 'post__in');
});

if (!function_exists('my_theme_get_dulux_product_colour_support_catalog')) {
    function my_theme_get_dulux_product_colour_support_catalog()
    {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }

        $cache = [
            'source_page'     => 'https://www.dulux.vn/vi/san-pham',
            'total'           => 0,
            'items'           => [],
            'by_url'          => [],
            'by_compact_slug' => [],
        ];

        $path = get_theme_file_path('data/dulux_product_colour_support.json');
        if (!is_string($path) || !is_readable($path)) {
            return $cache;
        }

        $raw = file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return $cache;
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return $cache;
        }

        if (!empty($data['source_page'])) {
            $cache['source_page'] = (string) $data['source_page'];
        }

        $items_raw = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];
        foreach ($items_raw as $row) {
            if (!is_array($row)) {
                continue;
            }

            $slug = sanitize_title((string) ($row['slug'] ?? ''));
            $url = my_theme_normalize_official_product_source_url((string) ($row['url'] ?? ''));
            $requires_colour = my_theme_parse_bool_flag($row['requires_color'] ?? false);

            if ($slug !== '') {
                $cache['items'][$slug] = [
                    'requires_color' => $requires_colour,
                    'url'            => $url,
                ];
                $cache['by_compact_slug'][str_replace('-', '', $slug)] = $requires_colour;
            }

            if ($url !== '') {
                $cache['by_url'][$url] = $requires_colour;
            }
        }

        $cache['total'] = count($cache['items']);
        return $cache;
    }
}

if (!function_exists('my_theme_get_official_import_slug')) {
    function my_theme_get_official_import_slug($product = null, $brand_slug = '')
    {
        if (!$product instanceof WC_Product) {
            return '';
        }
        $product_id = (int) $product->get_id();
        if ($product_id <= 0) {
            return '';
        }

        $import_key = trim((string) get_post_meta($product_id, '_official_import_key', true));
        if ($import_key === '') {
            return '';
        }

        $brand_slug = sanitize_title((string) $brand_slug);
        if ($brand_slug !== '') {
            $pattern = '/^official-' . preg_quote($brand_slug, '/') . '-(.+)$/i';
            if (preg_match($pattern, $import_key, $m) === 1) {
                return sanitize_title((string) $m[1]);
            }
            return '';
        }

        if (preg_match('/^official-[^-]+-(.+)$/i', $import_key, $m) === 1) {
            return sanitize_title((string) $m[1]);
        }

        return '';
    }
}

if (!function_exists('my_theme_product_requires_dulux_colour_catalog')) {
    function my_theme_product_requires_dulux_colour_catalog($product = null)
    {
        if (!$product instanceof WC_Product) {
            return false;
        }

        $catalog = my_theme_get_dulux_product_colour_support_catalog();
        if (empty($catalog['items']) || !is_array($catalog['items'])) {
            return false;
        }

        $source_url = my_theme_get_product_official_source_url($product);
        if ($source_url !== '' && isset($catalog['by_url'][$source_url])) {
            return my_theme_parse_bool_flag($catalog['by_url'][$source_url]);
        }

        $import_slug = my_theme_get_official_import_slug($product, 'dulux');
        if ($import_slug !== '' && isset($catalog['items'][$import_slug])) {
            return my_theme_parse_bool_flag($catalog['items'][$import_slug]['requires_color'] ?? false);
        }

        $product_slug = sanitize_title((string) $product->get_slug());
        if ($product_slug !== '' && isset($catalog['items'][$product_slug])) {
            return my_theme_parse_bool_flag($catalog['items'][$product_slug]['requires_color'] ?? false);
        }

        $compact_from_product = str_replace('-', '', $product_slug);
        if ($compact_from_product !== '' && isset($catalog['by_compact_slug'][$compact_from_product])) {
            return my_theme_parse_bool_flag($catalog['by_compact_slug'][$compact_from_product]);
        }

        if ($source_url !== '') {
            $source_path = wp_parse_url($source_url, PHP_URL_PATH);
            if (is_string($source_path) && $source_path !== '') {
                $source_slug = sanitize_title((string) basename($source_path));
                if ($source_slug !== '') {
                    if (isset($catalog['items'][$source_slug])) {
                        return my_theme_parse_bool_flag($catalog['items'][$source_slug]['requires_color'] ?? false);
                    }
                    $compact_source_slug = str_replace('-', '', $source_slug);
                    if ($compact_source_slug !== '' && isset($catalog['by_compact_slug'][$compact_source_slug])) {
                        return my_theme_parse_bool_flag($catalog['by_compact_slug'][$compact_source_slug]);
                    }
                }
            }
        }

        // Fail closed: if a Dulux product is not mapped from official data, do not show colour swatches.
        return false;
    }
}

if (!function_exists('my_theme_get_static_brand_palette_catalogs')) {
    function my_theme_get_static_brand_palette_catalogs()
    {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }

        $cache = [];
        $path = get_theme_file_path('data/brand_palettes.json');
        if (!is_string($path) || !is_readable($path)) {
            return $cache;
        }

        $raw = file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return $cache;
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['brands']) || !is_array($data['brands'])) {
            return $cache;
        }

        foreach ($data['brands'] as $brand_slug => $brand_catalog) {
            if (!is_array($brand_catalog)) {
                continue;
            }

            $slug = sanitize_title((string) $brand_slug);
            if ($slug === '') {
                continue;
            }

            $label = trim((string) ($brand_catalog['label'] ?? ucfirst($slug)));
            $source_page = trim((string) ($brand_catalog['source_page'] ?? ''));
            $source_note = trim((string) ($brand_catalog['source_note'] ?? ''));
            $items_raw = isset($brand_catalog['items']) && is_array($brand_catalog['items']) ? $brand_catalog['items'] : [];
            $items = [];

            foreach ($items_raw as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $code = trim((string) ($row['code'] ?? ''));
                $name = trim((string) ($row['name'] ?? ''));
                $hex = strtolower(trim((string) ($row['hex'] ?? '')));
                $hex = preg_replace('/[^0-9a-f]/i', '', $hex);
                $link = trim((string) ($row['link'] ?? ''));

                if ($code === '' || !is_string($hex) || strlen($hex) !== 6) {
                    continue;
                }

                if ($link !== '' && strpos($link, 'http') !== 0) {
                    $link = 'https://' . ltrim($link, '/');
                }

                $items[] = [
                    'code' => $code,
                    'name' => $name,
                    'hex'  => $hex,
                    'link' => $link,
                ];
            }

            if (empty($items)) {
                continue;
            }

            $cache[$slug] = [
                'brand_slug'  => $slug,
                'label'       => ($label !== '' ? $label : ucfirst($slug)),
                'source_page' => $source_page,
                'source_note' => $source_note,
                'total'       => count($items),
                'items'       => $items,
            ];
        }

        return $cache;
    }
}

if (!function_exists('my_theme_get_brand_palette_catalog')) {
    function my_theme_get_brand_palette_catalog($brand_slug = '')
    {
        $brand_slug = sanitize_title((string) $brand_slug);
        if ($brand_slug === '') {
            return [];
        }

        if ($brand_slug === 'jotun') {
            $jotun = my_theme_get_jotun_interior_colour_catalog();
            $items = isset($jotun['items']) && is_array($jotun['items']) ? $jotun['items'] : [];
            if (empty($items)) {
                return [];
            }
            return [
                'brand_slug'  => 'jotun',
                'label'       => 'Jotun',
                'source_page' => (string) ($jotun['source_page'] ?? ''),
                'source_note' => 'official',
                'total'       => (int) ($jotun['total'] ?? count($items)),
                'items'       => $items,
            ];
        }

        if ($brand_slug === 'dulux') {
            $dulux = my_theme_get_dulux_colour_catalog();
            $items = isset($dulux['items']) && is_array($dulux['items']) ? $dulux['items'] : [];
            if (empty($items)) {
                return [];
            }
            return [
                'brand_slug'  => 'dulux',
                'label'       => 'Dulux',
                'source_page' => (string) ($dulux['source_page'] ?? ''),
                'source_note' => 'official',
                'total'       => (int) ($dulux['total'] ?? count($items)),
                'items'       => $items,
            ];
        }

        $catalogs = my_theme_get_static_brand_palette_catalogs();
        if (!isset($catalogs[$brand_slug]) || !is_array($catalogs[$brand_slug])) {
            return [];
        }
        return $catalogs[$brand_slug];
    }
}

if (!function_exists('my_theme_product_supports_palette_catalog')) {
    function my_theme_product_supports_palette_catalog($product, $catalog = [])
    {
        if (!$product instanceof WC_Product || !is_array($catalog) || empty($catalog['items'])) {
            return false;
        }

        $source_note = sanitize_key((string) ($catalog['source_note'] ?? ''));
        if ($source_note !== 'official') {
            // Avoid showing reference palettes on product cards to prevent wrong mapping.
            return false;
        }

        $brand_slug = sanitize_title((string) ($catalog['brand_slug'] ?? ''));
        $line_slug = function_exists('my_theme_get_product_line_slug')
            ? sanitize_title((string) my_theme_get_product_line_slug($product))
            : '';

        if ($brand_slug === 'dulux') {
            $disallowed_line_slugs = [
                'line-primer',
                'line-waterproof',
                'line-putty',
                'line-metal',
                'line-epoxy',
                'line-industrial',
                'line-adhesive',
                'line-oil',
            ];
            if ($line_slug !== '' && in_array($line_slug, $disallowed_line_slugs, true)) {
                return false;
            }
            return my_theme_product_requires_dulux_colour_catalog($product);
        }

        if ($brand_slug === 'jotun') {
            $disallowed_line_slugs = [
                'line-primer',
                'line-waterproof',
                'line-putty',
                'line-metal',
                'line-epoxy',
                'line-industrial',
                'line-adhesive',
                'line-oil',
            ];
            if ($line_slug !== '' && in_array($line_slug, $disallowed_line_slugs, true)) {
                return false;
            }

            // Keep Jotun palette only for decorative pages when official source URL exists.
            $source_url = my_theme_get_product_official_source_url($product);
            if ($source_url !== '') {
                if (strpos($source_url, 'jotun.com') !== false) {
                    return (strpos($source_url, '/jotun/decorative/') !== false);
                }
            }

            return in_array($line_slug, ['line-interior', 'line-exterior', 'majestic', 'essence', 'jotaplast', 'jotashield', 'waterguard'], true);
        }

        return false;
    }
}

if (!function_exists('my_theme_get_product_palette_catalog')) {
    function my_theme_get_product_palette_catalog($prod = null)
    {
        $product = ($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID());
        if (!$product instanceof WC_Product || !function_exists('my_theme_get_product_brand_slug')) {
            return [];
        }

        $brand_slug = sanitize_title((string) my_theme_get_product_brand_slug($product));
        if ($brand_slug === '') {
            return [];
        }

        $catalog = my_theme_get_brand_palette_catalog($brand_slug);
        if (empty($catalog) || !is_array($catalog)) {
            return [];
        }

        if (!my_theme_product_supports_palette_catalog($product, $catalog)) {
            return [];
        }

        return $catalog;
    }
}

if (!function_exists('my_theme_product_has_palette')) {
    function my_theme_product_has_palette($prod = null)
    {
        $catalog = my_theme_get_product_palette_catalog($prod);
        return !empty($catalog['items']) && is_array($catalog['items']);
    }
}

if (!function_exists('my_theme_product_has_jotun_palette')) {
    function my_theme_product_has_jotun_palette($prod = null)
    {
        // Backward compatibility wrapper for older template calls.
        return my_theme_product_has_palette($prod);
    }
}

if (!function_exists('my_theme_get_product_palette_swatches')) {
    function my_theme_get_product_palette_swatches($prod = null, $limit = 8)
    {
        $catalog = my_theme_get_product_palette_catalog($prod);
        $items = isset($catalog['items']) && is_array($catalog['items']) ? $catalog['items'] : [];
        if (empty($items)) {
            return [];
        }

        $limit = max(1, min(40, (int) $limit));
        return array_slice($items, 0, $limit);
    }
}

if (!function_exists('my_theme_render_product_colour_swatches')) {
    function my_theme_render_product_colour_swatches($prod = null, $args = [])
    {
        $product = ($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID());
        if (!$product instanceof WC_Product) {
            return;
        }

        $catalog = my_theme_get_product_palette_catalog($product);
        if (empty($catalog) || empty($catalog['items']) || !is_array($catalog['items'])) {
            return;
        }

        $defaults = [
            'context'   => 'loop',
            'limit'     => 8,
            'show_more' => true,
        ];
        $args = wp_parse_args(is_array($args) ? $args : [], $defaults);

        $swatches = array_slice($catalog['items'], 0, max(1, (int) $args['limit']));
        if (empty($swatches)) {
            return;
        }

        $brand_label = trim((string) ($catalog['label'] ?? ''));
        $total = isset($catalog['total']) ? (int) $catalog['total'] : count($catalog['items']);
        $shown = count($swatches);
        $context_class = 'product-colour-swatches--' . sanitize_html_class((string) $args['context']);
        $aria = ($brand_label !== '') ? ('Mã màu ' . $brand_label) : 'Mã màu sản phẩm';

        echo '<div class="product-colour-swatches ' . esc_attr($context_class) . '" aria-label="' . esc_attr($aria) . '">';
        foreach ($swatches as $swatch) {
            $hex = '#' . esc_attr((string) ($swatch['hex'] ?? ''));
            $code = (string) ($swatch['code'] ?? '');
            $name = (string) ($swatch['name'] ?? '');
            $label = trim($code . ' ' . $name);
            $url = (string) ($swatch['link'] ?? '');
            if ($url !== '') {
                echo '<a class="product-colour-swatch" href="' . esc_url($url) . '" target="_blank" rel="noopener nofollow" title="' . esc_attr($label) . '" aria-label="' . esc_attr($label) . '" style="--swatch-color:' . $hex . ';">';
                echo '</a>';
            } else {
                echo '<span class="product-colour-swatch" title="' . esc_attr($label) . '" aria-label="' . esc_attr($label) . '" style="--swatch-color:' . $hex . ';"></span>';
            }
        }

        if (!empty($args['show_more']) && $total > $shown) {
            echo '<span class="product-colour-swatches__more">+' . esc_html((string) ($total - $shown)) . '</span>';
        }
        echo '</div>';
    }
}

if (!function_exists('my_theme_render_single_product_colour_chart')) {
    function my_theme_render_single_product_colour_chart($prod = null, $limit = 40)
    {
        $product = ($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID());
        if (!$product instanceof WC_Product) {
            return;
        }

        $catalog = my_theme_get_product_palette_catalog($product);
        if (empty($catalog) || empty($catalog['items']) || !is_array($catalog['items'])) {
            return;
        }

        $limit = max(8, min(80, (int) $limit));
        $items = array_slice($catalog['items'], 0, $limit);
        if (empty($items)) {
            return;
        }

        $brand_label = trim((string) ($catalog['label'] ?? ''));
        $source_page = isset($catalog['source_page']) ? (string) $catalog['source_page'] : '';
        $source_note = isset($catalog['source_note']) ? (string) $catalog['source_note'] : '';
        $total = isset($catalog['total']) ? (int) $catalog['total'] : count($catalog['items']);
        $title = ($brand_label !== '') ? ('Bảng màu mã màu ' . $brand_label) : 'Bảng màu mã màu';
        $subtitle = ($source_note === 'official')
            ? 'Đồng bộ từ nguồn chính thức của hãng.'
            : 'Bảng màu tham khảo theo tông của hãng để chọn nhanh.';
        ?>
        <section class="page-section product-colour-chart" aria-label="<?php echo esc_attr($title); ?>">
          <div class="section-heading">
            <h2 class="section-title"><?php echo esc_html($title); ?></h2>
            <p class="section-sub"><?php echo esc_html($subtitle); ?></p>
          </div>
          <div class="product-colour-chart__grid">
            <?php foreach ($items as $swatch) : ?>
              <?php
                $hex = '#' . esc_attr((string) ($swatch['hex'] ?? ''));
                $code = (string) ($swatch['code'] ?? '');
                $name = (string) ($swatch['name'] ?? '');
                $label = trim($code . ' ' . $name);
                $url = (string) ($swatch['link'] ?? '');
              ?>
              <?php if ($url !== '') : ?>
                <a class="product-colour-chart__item" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener nofollow" aria-label="<?php echo esc_attr($label); ?>" title="<?php echo esc_attr($label); ?>">
                  <span class="product-colour-chart__dot" style="--swatch-color:<?php echo $hex; ?>;"></span>
                  <span class="product-colour-chart__code"><?php echo esc_html($code); ?></span>
                  <span class="product-colour-chart__name"><?php echo esc_html($name); ?></span>
                </a>
              <?php else : ?>
                <div class="product-colour-chart__item" aria-label="<?php echo esc_attr($label); ?>">
                  <span class="product-colour-chart__dot" style="--swatch-color:<?php echo $hex; ?>;"></span>
                  <span class="product-colour-chart__code"><?php echo esc_html($code); ?></span>
                  <span class="product-colour-chart__name"><?php echo esc_html($name); ?></span>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
          <?php if ($source_page !== '') : ?>
            <div class="product-colour-chart__foot">
              <a class="btn btn-outline btn-sm" href="<?php echo esc_url($source_page); ?>" target="_blank" rel="noopener nofollow">
                <?php if ($total > count($items)) : ?>
                  Xem toàn bộ <?php echo esc_html(number_format_i18n($total)); ?> mã màu <?php echo esc_html($brand_label); ?>
                <?php else : ?>
                  Mở trang màu <?php echo esc_html($brand_label); ?>
                <?php endif; ?>
              </a>
            </div>
          <?php endif; ?>
        </section>
        <?php
    }
}

function my_theme_get_pack_kind_from_unit($unit = '') {
    $unit = (string) $unit;
    if ($unit === 'kg') {
        return 'weight';
    }
    if ($unit === 'L' || $unit === 'ml') {
        return 'capacity';
    }
    if ($unit === 'pack') {
        return 'package';
    }
    return 'capacity';
}

function my_theme_get_pack_kind_for_labels($labels) {
    $kinds = [];
    foreach ((array) $labels as $label) {
        $parsed = my_theme_parse_pack_label($label);
        if (!$parsed) {
            continue;
        }
        $kind = my_theme_get_pack_kind_from_unit($parsed['unit']);
        $kinds[$kind] = true;
    }

    if (empty($kinds)) {
        return 'capacity';
    }
    if (count($kinds) === 1) {
        return (string) array_key_first($kinds);
    }
    if (!empty($kinds['package'])) {
        return 'package';
    }
    return 'package';
}

function my_theme_get_pack_title_text($labels) {
    $kind = my_theme_get_pack_kind_for_labels($labels);
    if ($kind === 'weight') {
        return 'Giá theo khối lượng:';
    }
    if ($kind === 'capacity') {
        return 'Giá theo dung tích:';
    }
    return 'Giá theo quy cách:';
}

function my_theme_get_pack_picker_text($labels, $with_colon = false) {
    $kind = my_theme_get_pack_kind_for_labels($labels);
    if ($kind === 'weight') {
        return $with_colon ? 'Chọn khối lượng:' : 'Chọn khối lượng';
    }
    if ($kind === 'capacity') {
        return $with_colon ? 'Chọn dung tích:' : 'Chọn dung tích';
    }
    return $with_colon ? 'Chọn quy cách:' : 'Chọn quy cách';
}

function my_theme_get_pack_meta_label($selected_pack) {
    $parsed = my_theme_parse_pack_label($selected_pack);
    if ($parsed && $parsed['unit'] === 'kg') {
        return 'Khối lượng';
    }
    if ($parsed && $parsed['unit'] === 'pack') {
        return 'Quy cách';
    }
    return 'Dung tích';
}

// Render danh sách giá theo từng dung tích/khối lượng nếu có map giá.
function my_theme_render_pack_price_list($prod = null, $context = 'loop') {
    // Hidden by request: keep pack-selection and pricing logic, remove the extra reference list UI.
    return;
}

add_action('woocommerce_single_product_summary', function () {
    my_theme_render_pack_price_list(null, 'single');
}, 10);

function my_theme_get_default_loop_price($product) {
    if (!$product instanceof WC_Product) {
        return 0.0;
    }

    $default_pack_context = function_exists('my_theme_get_default_selected_capacity_price_context')
        ? my_theme_get_default_selected_capacity_price_context($product)
        : ['capacity' => '', 'price' => 0.0, 'regular_price' => 0.0];
    if (!empty($default_pack_context['capacity']) && (float) ($default_pack_context['price'] ?? 0) > 0) {
        return (float) $default_pack_context['price'];
    }

    return (float) $product->get_price();
}

function my_theme_get_default_loop_regular_price($product) {
    if (!$product instanceof WC_Product) {
        return 0.0;
    }

    $default_pack_context = function_exists('my_theme_get_default_selected_capacity_price_context')
        ? my_theme_get_default_selected_capacity_price_context($product)
        : ['capacity' => '', 'price' => 0.0, 'regular_price' => 0.0];
    if (!empty($default_pack_context['capacity']) && (float) ($default_pack_context['regular_price'] ?? 0) > 0) {
        return (float) $default_pack_context['regular_price'];
    }

    return my_theme_get_product_raw_regular_price($product);
}

if (!function_exists('my_theme_sort_product_ids_by_loop_price')) {
    function my_theme_sort_product_ids_by_loop_price($product_ids, $direction = 'asc')
    {
        static $request_cache = [];

        $source_product_ids = function_exists('my_theme_preserve_product_id_order')
            ? my_theme_preserve_product_id_order($product_ids)
            : my_theme_normalize_product_id_list($product_ids);
        if (empty($source_product_ids)) {
            return [];
        }

        $direction = (strtolower((string) $direction) === 'desc') ? 'desc' : 'asc';
        $cache_version = (string) get_option('my_theme_filter_cache_version', '1');
        $digest = md5($direction . '|' . implode(',', $source_product_ids));
        $request_key = $cache_version . ':' . $digest;
        if (array_key_exists($request_key, $request_cache)) {
            return $request_cache[$request_key];
        }

        $cache_key = 'my_theme_loop_price_sorted_ids_v1_' . $cache_version . '_' . $digest;
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            $allowed_lookup = array_fill_keys($source_product_ids, true);
            $filtered = [];
            $seen = [];
            foreach ($cached as $product_id) {
                $product_id = (int) $product_id;
                if ($product_id <= 0 || isset($seen[$product_id]) || !isset($allowed_lookup[$product_id])) {
                    continue;
                }

                $seen[$product_id] = true;
                $filtered[] = $product_id;
            }
            foreach ($source_product_ids as $product_id) {
                $product_id = (int) $product_id;
                if ($product_id > 0 && !isset($seen[$product_id])) {
                    $filtered[] = $product_id;
                }
            }

            $request_cache[$request_key] = $filtered;
            return $request_cache[$request_key];
        }

        $product_map = function_exists('my_theme_get_product_object_map')
            ? my_theme_get_product_object_map($source_product_ids)
            : [];
        if (empty($product_map)) {
            $request_cache[$request_key] = [];
            set_transient($cache_key, [], 30 * MINUTE_IN_SECONDS);
            return [];
        }

        $sortable = [];
        foreach ($source_product_ids as $position => $product_id) {
            $product_id = (int) $product_id;
            if ($product_id <= 0 || !isset($product_map[$product_id])) {
                continue;
            }

            $product = $product_map[$product_id];
            if (!$product instanceof WC_Product) {
                continue;
            }

            $sortable[] = [
                'id' => $product_id,
                'position' => (int) $position,
                'price' => function_exists('my_theme_get_default_loop_price')
                    ? (float) my_theme_get_default_loop_price($product)
                    : (float) $product->get_price(),
            ];
        }

        if (empty($sortable)) {
            $request_cache[$request_key] = [];
            set_transient($cache_key, [], 30 * MINUTE_IN_SECONDS);
            return [];
        }

        usort($sortable, static function (array $a, array $b) use ($direction): int {
            $price_a = isset($a['price']) ? (float) $a['price'] : 0.0;
            $price_b = isset($b['price']) ? (float) $b['price'] : 0.0;
            $has_price_a = ($price_a > 0);
            $has_price_b = ($price_b > 0);

            if ($has_price_a !== $has_price_b) {
                return $has_price_a ? -1 : 1;
            }

            if ($has_price_a && $price_a !== $price_b) {
                if ($direction === 'desc') {
                    return ($price_a > $price_b) ? -1 : 1;
                }

                return ($price_a < $price_b) ? -1 : 1;
            }

            $position_a = isset($a['position']) ? (int) $a['position'] : 0;
            $position_b = isset($b['position']) ? (int) $b['position'] : 0;
            if ($position_a === $position_b) {
                return 0;
            }

            return ($position_a < $position_b) ? -1 : 1;
        });

        $sorted_ids = array_values(array_filter(array_map(static function (array $row): int {
            return isset($row['id']) ? (int) $row['id'] : 0;
        }, $sortable)));

        $request_cache[$request_key] = $sorted_ids;
        set_transient($cache_key, $sorted_ids, 30 * MINUTE_IN_SECONDS);

        return $request_cache[$request_key];
    }
}

if (!function_exists('my_theme_get_price_sorted_query_product_ids')) {
    function my_theme_get_price_sorted_query_product_ids($query_args, $direction = 'asc')
    {
        static $request_cache = [];

        if (!is_array($query_args) || empty($query_args)) {
            return [];
        }

        $direction = (strtolower((string) $direction) === 'desc') ? 'desc' : 'asc';
        $base_args = $query_args;
        unset(
            $base_args['paged'],
            $base_args['page'],
            $base_args['posts_per_page'],
            $base_args['offset'],
            $base_args['orderby'],
            $base_args['order'],
            $base_args['meta_key'],
            $base_args['fields'],
            $base_args['no_found_rows'],
            $base_args['cache_results'],
            $base_args['update_post_meta_cache'],
            $base_args['update_post_term_cache'],
            $base_args['lazy_load_term_meta']
        );

        $base_args['fields'] = 'ids';
        $base_args['posts_per_page'] = -1;
        $base_args['paged'] = 1;
        $base_args['ignore_sticky_posts'] = true;
        $base_args['no_found_rows'] = true;
        $base_args['cache_results'] = false;
        $base_args['update_post_meta_cache'] = false;
        $base_args['update_post_term_cache'] = false;
        $base_args['lazy_load_term_meta'] = false;
        $base_args['suppress_filters'] = false;

        if (!empty($base_args['post__in']) && is_array($base_args['post__in'])) {
            $base_args['orderby'] = 'post__in';
            $base_args['order'] = 'ASC';
        } else {
            $base_args['orderby'] = 'date';
            $base_args['order'] = 'DESC';
        }

        $cache_version = (string) get_option('my_theme_filter_cache_version', '1');
        $encoded_args = wp_json_encode($base_args);
        if (!is_string($encoded_args) || $encoded_args === '') {
            $encoded_args = serialize($base_args);
        }
        $digest = md5($direction . '|' . $encoded_args);
        $request_key = $cache_version . ':' . $digest;
        if (array_key_exists($request_key, $request_cache)) {
            return $request_cache[$request_key];
        }

        $cache_key = 'my_theme_price_sorted_query_ids_v1_' . $cache_version . '_' . $digest;
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            $request_cache[$request_key] = function_exists('my_theme_preserve_product_id_order')
                ? my_theme_preserve_product_id_order($cached)
                : my_theme_normalize_product_id_list($cached);
            return $request_cache[$request_key];
        }

        $matched_ids = get_posts($base_args);
        $matched_ids = function_exists('my_theme_preserve_product_id_order')
            ? my_theme_preserve_product_id_order($matched_ids)
            : my_theme_normalize_product_id_list($matched_ids);
        if (empty($matched_ids)) {
            $request_cache[$request_key] = [];
            set_transient($cache_key, [], 15 * MINUTE_IN_SECONDS);
            return [];
        }

        $sorted_ids = function_exists('my_theme_sort_product_ids_by_loop_price')
            ? my_theme_sort_product_ids_by_loop_price($matched_ids, $direction)
            : $matched_ids;
        $sorted_ids = function_exists('my_theme_preserve_product_id_order')
            ? my_theme_preserve_product_id_order($sorted_ids)
            : my_theme_normalize_product_id_list($sorted_ids);

        $request_cache[$request_key] = $sorted_ids;
        set_transient($cache_key, $sorted_ids, 15 * MINUTE_IN_SECONDS);

        return $request_cache[$request_key];
    }
}

if (!function_exists('my_theme_get_loop_price_html')) {
    function my_theme_get_loop_price_html($prod = null, $wrapper_class = 'product-card__price')
    {
        $product = function_exists('my_theme_resolve_product')
            ? my_theme_resolve_product($prod)
            : (($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID()));
        if (!$product instanceof WC_Product) {
            return '';
        }

        $wrapper_class = trim((string) $wrapper_class);
        if ($wrapper_class === '') {
            $wrapper_class = 'product-card__price';
        }

        $price_value = my_theme_get_default_loop_price($product);
        $regular_price_value = my_theme_get_default_loop_regular_price($product);
        if ($price_value > 0) {
            if ($regular_price_value > $price_value) {
                return '<div class="' . esc_attr($wrapper_class) . '"><del class="product-card__price-regular">' . wp_kses_post(wc_price($regular_price_value)) . '</del><ins class="product-card__price-sale"><span class="product-card__price-value" data-price="' . esc_attr($price_value) . '" data-regular-price="' . esc_attr($regular_price_value) . '">' . wp_kses_post(wc_price($price_value)) . '</span></ins></div>';
            }

            return '<div class="' . esc_attr($wrapper_class) . '"><span class="product-card__price-value" data-price="' . esc_attr($price_value) . '">' . wp_kses_post(wc_price($price_value)) . '</span></div>';
        }

        return '<div class="' . esc_attr($wrapper_class) . '"><span class="product-card__price-contact">Liên hệ báo giá</span></div>';
    }
}

function my_theme_render_loop_price($prod = null) {
    $price_html = function_exists('my_theme_get_loop_price_html')
        ? my_theme_get_loop_price_html($prod, 'product-card__price')
        : '';
    if ($price_html === '') {
        return;
    }

    echo $price_html;
}

if (!function_exists('my_theme_render_single_product_quick_facts')) {
    function my_theme_render_single_product_quick_facts()
    {
        if (!function_exists('is_product') || !is_product()) {
            return;
        }
        $product = wc_get_product(get_the_ID());
        if (!$product instanceof WC_Product) {
            return;
        }

        $catalog_profile = function_exists('my_theme_get_product_catalog_profile')
            ? my_theme_get_product_catalog_profile($product)
            : [];
        $brand_label = isset($catalog_profile['brand_label'])
            ? trim((string) $catalog_profile['brand_label'])
            : '';
        if ($brand_label === 'Sản phẩm') {
            $brand_label = '';
        }
        $line_label = isset($catalog_profile['line_label'])
            ? trim((string) $catalog_profile['line_label'])
            : '';
        $line_slug = isset($catalog_profile['line_slug'])
            ? sanitize_title((string) $catalog_profile['line_slug'])
            : '';
        $cat_label = isset($catalog_profile['category_label'])
            ? trim((string) $catalog_profile['category_label'])
            : '';

        $facts = [];
        if ($brand_label !== '' && $brand_label !== 'Sản phẩm') {
            $facts[] = ['label' => 'Thương hiệu', 'value' => $brand_label];
        }
        $line_is_generic = function_exists('my_theme_is_generic_line_label')
            ? my_theme_is_generic_line_label($line_label, $line_slug, $cat_label)
            : false;
        if ($line_label !== '' && !$line_is_generic) {
            $facts[] = ['label' => 'Dòng', 'value' => $line_label];
        }
        if ($cat_label !== '' && $cat_label !== 'Chưa phân loại') {
            $facts[] = ['label' => 'Nhóm', 'value' => $cat_label];
        }
        if (empty($facts)) {
            return;
        }

        echo '<div class="single-product-facts" aria-label="Thông tin nhanh sản phẩm">';
        foreach ($facts as $fact) {
            echo '<span class="single-product-facts__chip"><strong>' . esc_html($fact['label']) . ':</strong> ' . esc_html($fact['value']) . '</span>';
        }
        echo '</div>';
    }
}
add_action('woocommerce_single_product_summary', 'my_theme_render_single_product_quick_facts', 7);

if (!function_exists('my_theme_render_single_product_usage_note')) {
    function my_theme_render_single_product_usage_note()
    {
        if (!function_exists('is_product') || !is_product()) {
            return;
        }
        $product = wc_get_product(get_the_ID());
        if (!$product instanceof WC_Product) {
            return;
        }

        $short = trim((string) wp_strip_all_tags($product->get_short_description()));
        if ($short !== '' && (!function_exists('my_theme_text_looks_unaccented_vi') || !my_theme_text_looks_unaccented_vi($short))) {
            return;
        }

        $usage = function_exists('my_theme_get_product_card_excerpt')
            ? trim((string) my_theme_get_product_card_excerpt($product, 24))
            : '';
        $usage = preg_replace('/\.\.\.$/u', '', (string) $usage);
        $usage = trim((string) $usage);
        if ($usage === '') {
            return;
        }

        echo '<div class="single-product-usage"><strong>Công dụng chính:</strong> ' . esc_html($usage) . '</div>';
    }
}
add_action('woocommerce_single_product_summary', 'my_theme_render_single_product_usage_note', 22);

// Fallback price for simple products that only store capacity-price map.
add_filter('woocommerce_get_price_html', function ($price_html, $product) {
    if (!$product instanceof WC_Product || !$product->is_type('simple')) {
        return $price_html;
    }

    $default_pack_context = function_exists('my_theme_get_default_selected_capacity_price_context')
        ? my_theme_get_default_selected_capacity_price_context($product)
        : ['capacity' => '', 'price' => 0.0, 'regular_price' => 0.0];
    $raw_price = (float) $product->get_price();

    if (!empty($default_pack_context['capacity']) && (float) ($default_pack_context['price'] ?? 0) > 0) {
        $default_price = (float) $default_pack_context['price'];
        $default_regular_price = max($default_price, (float) ($default_pack_context['regular_price'] ?? 0));
        if ($default_regular_price > $default_price) {
            return '<del>' . wp_kses_post(wc_price($default_regular_price)) . '</del><ins>' . wp_kses_post(wc_price($default_price)) . '</ins>';
        }

        return wc_price($default_price);
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

    $map = function_exists('my_theme_get_pack_price_display_map')
        ? my_theme_get_pack_price_display_map($product)
        : my_theme_get_pack_price_map_for_display($product);
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

    $pack_price_maps = function_exists('my_theme_get_pack_price_maps')
        ? my_theme_get_pack_price_maps($product)
        : [
            'raw' => my_theme_get_pack_price_map_for_display($product, false),
            'display' => my_theme_get_pack_price_map_for_display($product, true),
        ];
    $price_map = isset($pack_price_maps['display']) && is_array($pack_price_maps['display'])
        ? $pack_price_maps['display']
        : [];
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
    $raw_price_map = isset($pack_price_maps['raw']) && is_array($pack_price_maps['raw'])
        ? $pack_price_maps['raw']
        : [];
    $default_context = function_exists('my_theme_get_default_selected_capacity_price_context')
        ? my_theme_get_default_selected_capacity_price_context($product)
        : ['capacity' => '', 'price' => 0.0, 'regular_price' => 0.0];
    $default_size = !empty($default_context['capacity']) ? (string) $default_context['capacity'] : (string) array_key_first($price_map);
    $default_price = isset($price_map[$default_size]) ? (float) $price_map[$default_size] : (float) ($default_context['price'] ?? 0);
    $picker_label = my_theme_get_pack_picker_text($sizes, false);
    $loop_sync_onchange = my_theme_get_loop_capacity_inline_onchange();
    $loop_label_onclick = function_exists('my_theme_get_loop_capacity_label_inline_onclick')
        ? my_theme_get_loop_capacity_label_inline_onclick()
        : '';

    echo '<form class="loop-pack-form" method="post" action="' . esc_url($product->get_permalink()) . '" enctype="multipart/form-data" data-product-id="' . esc_attr($product->get_id()) . '">';
    $option_wrap_class = 'loop-pack-picker__options';
    if (count($sizes) === 1) {
        $option_wrap_class .= ' loop-pack-picker__options--single';
    }

    echo '<div class="loop-pack-picker">';
    echo '<span class="loop-pack-picker__label">' . esc_html($picker_label) . ':</span>';
    echo '<div class="' . esc_attr($option_wrap_class) . '" role="radiogroup" aria-label="' . esc_attr($picker_label) . '">';
    foreach ($sizes as $size_label) {
        $input_id = 'loop-pack-' . $product->get_id() . '-' . sanitize_title($size_label);
        $regular_size_price = isset($raw_price_map[$size_label]) ? (float) $raw_price_map[$size_label] : (float) $price_map[$size_label];
        echo '<input class="loop-pack-option__input" type="radio" name="selected_capacity" id="' . esc_attr($input_id) . '" value="' . esc_attr($size_label) . '" data-capacity="' . esc_attr($size_label) . '" data-price="' . esc_attr($price_map[$size_label]) . '" data-regular-price="' . esc_attr($regular_size_price) . '" onchange="' . esc_attr($loop_sync_onchange) . '"' . checked($size_label, $default_size, false) . '>';
        $label_onclick_attr = $loop_label_onclick !== '' ? ' onclick="' . esc_attr($loop_label_onclick) . '"' : '';
        echo '<label class="loop-pack-option" for="' . esc_attr($input_id) . '" data-capacity="' . esc_attr($size_label) . '" data-price="' . esc_attr($price_map[$size_label]) . '" data-regular-price="' . esc_attr($regular_size_price) . '"' . $label_onclick_attr . '>' . esc_html($size_label) . '</label>';
    }
    echo '</div>';
    echo '</div>';
    echo '<input type="hidden" name="add-to-cart" value="' . esc_attr($product->get_id()) . '">';
    echo '<input type="hidden" name="quantity" value="1">';
    echo '<input type="hidden" name="selected_capacity_price" value="' . esc_attr($default_price) . '">';
    echo '<button type="submit" class="button loop-pack-form__submit w-100">Thêm vào giỏ</button>';
    echo '</form>';
}

if (!function_exists('my_theme_get_single_capacity_inline_onchange')) {
    function my_theme_get_single_capacity_inline_onchange()
    {
        return "(function(input){var picker=input.closest('.capacity-picker');if(!picker){return;}var raw=input.getAttribute('data-price')||'';var amount=parseFloat(String(raw).replace(/\\./g,'').replace(/,/g,'.').replace(/[^0-9.-]/g,''))||0;var rawRegular=input.getAttribute('data-regular-price')||'';var regularAmount=parseFloat(String(rawRegular).replace(/\\./g,'').replace(/,/g,'.').replace(/[^0-9.-]/g,''))||0;var cap=input.value||input.getAttribute('data-capacity')||'';var current=picker.querySelector('[data-capacity-current]');if(current){current.textContent=cap||'-';}var hidden=picker.querySelector('input[name=\"selected_capacity_price\"]');if(hidden){hidden.value=amount>0?String(Math.round(amount)):'';}Array.prototype.forEach.call(picker.querySelectorAll('.capacity-option'),function(label){label.classList.toggle('is-active',label.getAttribute('for')===input.id);});var summary=picker.closest('.summary');if(!summary){return;}var fmt=function(value){return new Intl.NumberFormat('vi-VN').format(Math.round(value))+'&nbsp;<span class=\"woocommerce-Price-currencySymbol\">&#8363;</span>';};var priceWrap=summary.querySelector('.price');if(priceWrap){if(amount>0&&regularAmount>amount){priceWrap.innerHTML='<del><span class=\"woocommerce-Price-amount amount\"><bdi>'+fmt(regularAmount)+'</bdi></span></del><ins><span class=\"woocommerce-Price-amount amount\"><bdi>'+fmt(amount)+'</bdi></span></ins>';}else if(amount>0){priceWrap.innerHTML='<span class=\"woocommerce-Price-amount amount\"><bdi>'+fmt(amount)+'</bdi></span>';}else{priceWrap.innerHTML='<span class=\"product-price-contact-inline\">Liên hệ báo giá</span>';}}Array.prototype.forEach.call(summary.querySelectorAll('.product-pack-prices__item'),function(item){item.classList.toggle('is-active',(item.getAttribute('data-pack-size')||'')===cap);});})(this)";
    }
}

if (!function_exists('my_theme_get_loop_capacity_inline_onchange')) {
    function my_theme_get_loop_capacity_inline_onchange()
    {
        return "(function(input){var form=input.closest('.loop-pack-form');if(!form){return;}var raw=input.getAttribute('data-price')||'';var amount=parseFloat(String(raw).replace(/\\./g,'').replace(/,/g,'.').replace(/[^0-9.-]/g,''))||0;var rawRegular=input.getAttribute('data-regular-price')||'';var regularAmount=parseFloat(String(rawRegular).replace(/\\./g,'').replace(/,/g,'.').replace(/[^0-9.-]/g,''))||0;var cap=input.value||input.getAttribute('data-capacity')||'';var hidden=form.querySelector('input[name=\"selected_capacity_price\"]');if(hidden){hidden.value=amount>0?String(Math.round(amount)):'';}Array.prototype.forEach.call(form.querySelectorAll('.loop-pack-option'),function(label){label.classList.toggle('is-active',label.getAttribute('for')===input.id);});var card=form.closest('.product-card');if(card){var fmt=function(value){return new Intl.NumberFormat('vi-VN').format(Math.round(value))+'&nbsp;<span class=\"woocommerce-Price-currencySymbol\">&#8363;</span>';};var priceWrap=card.querySelector('.product-card__price');if(priceWrap){if(amount>0&&regularAmount>amount){priceWrap.innerHTML='<del class=\"product-card__price-regular\"><span class=\"woocommerce-Price-amount amount\"><bdi>'+fmt(regularAmount)+'</bdi></span></del><ins class=\"product-card__price-sale\"><span class=\"product-card__price-value\" data-price=\"'+String(Math.round(amount))+'\" data-regular-price=\"'+String(Math.round(regularAmount))+'\"><span class=\"woocommerce-Price-amount amount\"><bdi>'+fmt(amount)+'</bdi></span></span></ins>';}else if(amount>0){priceWrap.innerHTML='<span class=\"product-card__price-value\" data-price=\"'+String(Math.round(amount))+'\"><span class=\"woocommerce-Price-amount amount\"><bdi>'+fmt(amount)+'</bdi></span></span>';}else{priceWrap.innerHTML='<span class=\"product-card__price-contact\">Liên hệ báo giá</span>';}}Array.prototype.forEach.call(card.querySelectorAll('.product-pack-prices__item'),function(item){item.classList.toggle('is-active',(item.getAttribute('data-pack-size')||'')===cap);});}})(this)";
    }
}

if (!function_exists('my_theme_get_single_capacity_label_inline_onclick')) {
    function my_theme_get_single_capacity_label_inline_onclick()
    {
        return "(function(label){var picker=label.closest('.capacity-picker');if(!picker){return;}var id=label.getAttribute('for')||'';if(!id){return;}var input=null;Array.prototype.some.call(picker.querySelectorAll('.capacity-option__input'),function(node){if((node.id||'')!==id){return false;}input=node;return true;});if(!input){return;}input.checked=true;try{input.dispatchEvent(new Event('change',{bubbles:true}));}catch(e){if(document.createEvent){var evt=document.createEvent('Event');evt.initEvent('change',true,true);input.dispatchEvent(evt);}else if(typeof input.onchange==='function'){input.onchange();}}})(this)";
    }
}

if (!function_exists('my_theme_get_loop_capacity_label_inline_onclick')) {
    function my_theme_get_loop_capacity_label_inline_onclick()
    {
        return "(function(label){var form=label.closest('.loop-pack-form');if(!form){return;}var id=label.getAttribute('for')||'';if(!id){return;}var input=null;Array.prototype.some.call(form.querySelectorAll('.loop-pack-option__input'),function(node){if((node.id||'')!==id){return false;}input=node;return true;});if(!input){return;}input.checked=true;try{input.dispatchEvent(new Event('change',{bubbles:true}));}catch(e){if(document.createEvent){var evt=document.createEvent('Event');evt.initEvent('change',true,true);input.dispatchEvent(evt);}else if(typeof input.onchange==='function'){input.onchange();}}})(this)";
    }
}

// --- Simple product: picker dung tích đổi giá theo bảng map ---
function my_theme_render_capacity_price_picker() {
    if (!is_product()) return;
    global $product;
    if (!$product instanceof WC_Product || $product->is_type('variable')) return; // biến thể dùng core

    $pack_price_maps = function_exists('my_theme_get_pack_price_maps')
        ? my_theme_get_pack_price_maps($product)
        : [
            'raw' => my_theme_get_pack_price_map_for_display($product, false),
            'display' => my_theme_get_pack_price_map_for_display($product, true),
        ];
    $raw_map = isset($pack_price_maps['raw']) && is_array($pack_price_maps['raw'])
        ? $pack_price_maps['raw']
        : [];
    $map = isset($pack_price_maps['display']) && is_array($pack_price_maps['display'])
        ? $pack_price_maps['display']
        : [];
    if (empty($map)) return;

    $caps = array_keys($map);
    $picker_label = my_theme_get_pack_picker_text($caps, true);
    $picker_aria = my_theme_get_pack_picker_text($caps, false);
    $single_sync_onchange = my_theme_get_single_capacity_inline_onchange();
    $single_label_onclick = function_exists('my_theme_get_single_capacity_label_inline_onclick')
        ? my_theme_get_single_capacity_label_inline_onclick()
        : '';

    $default_context = function_exists('my_theme_get_default_selected_capacity_price_context')
        ? my_theme_get_default_selected_capacity_price_context($product)
        : ['capacity' => '', 'price' => 0.0, 'regular_price' => 0.0];
    $default_cap = !empty($default_context['capacity']) ? (string) $default_context['capacity'] : (string) $caps[0];
    $default_price = isset($map[$default_cap]) ? (float) $map[$default_cap] : (float) ($default_context['price'] ?? 0);
    ?>
    <?php
    $option_wrap_class = 'capacity-picker__options';
    if (count($caps) === 1) {
        $option_wrap_class .= ' capacity-picker__options--single';
    }
    ?>
    <div class="capacity-picker" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
      <div class="capacity-picker__label"><?php echo esc_html($picker_label); ?></div>
      <div class="<?php echo esc_attr($option_wrap_class); ?>" role="radiogroup" aria-label="<?php echo esc_attr($picker_aria); ?>">
        <?php foreach ($caps as $cap) : ?>
          <?php $input_id = 'capacity-option-' . $product->get_id() . '-' . sanitize_title($cap); ?>
          <input
            class="capacity-option__input"
            type="radio"
            name="selected_capacity"
            id="<?php echo esc_attr($input_id); ?>"
            value="<?php echo esc_attr($cap); ?>"
            data-capacity="<?php echo esc_attr($cap); ?>"
            data-price="<?php echo esc_attr($map[$cap]); ?>"
            data-regular-price="<?php echo esc_attr($raw_map[$cap] ?? $map[$cap]); ?>"
            onchange="<?php echo esc_attr($single_sync_onchange); ?>"
            <?php checked($cap, $default_cap); ?>
          >
          <label class="capacity-option" for="<?php echo esc_attr($input_id); ?>" data-capacity="<?php echo esc_attr($cap); ?>" data-price="<?php echo esc_attr($map[$cap]); ?>" data-regular-price="<?php echo esc_attr($raw_map[$cap] ?? $map[$cap]); ?>"<?php echo $single_label_onclick !== '' ? ' onclick="' . esc_attr($single_label_onclick) . '"' : ''; ?>>
            <?php echo esc_html($cap); ?>
          </label>
        <?php endforeach; ?>
      </div>
      <div class="capacity-picker__current">Đang chọn: <strong data-capacity-current><?php echo esc_html($default_cap); ?></strong></div>
      <input type="hidden" name="selected_capacity_price" value="<?php echo esc_attr($default_price); ?>">
    </div>
    <?php
}
add_action('woocommerce_before_add_to_cart_button', 'my_theme_render_capacity_price_picker', 8);

if (!function_exists('my_theme_get_selected_capacity_price_context')) {
    function my_theme_get_selected_capacity_price_context($product, $selected_capacity = '')
    {
        $selected_capacity = trim((string) $selected_capacity);
        if ($selected_capacity === '' || !$product instanceof WC_Product) {
            return [
                'capacity' => '',
                'price' => 0.0,
                'regular_price' => 0.0,
            ];
        }

        $pack_price_maps = function_exists('my_theme_get_pack_price_maps')
            ? my_theme_get_pack_price_maps($product)
            : [
                'raw' => my_theme_get_pack_price_map_for_display($product, false),
                'display' => my_theme_get_pack_price_map_for_display($product, true),
            ];
        $display_map = isset($pack_price_maps['display']) && is_array($pack_price_maps['display'])
            ? $pack_price_maps['display']
            : [];
        if (empty($display_map)) {
            return [
                'capacity' => '',
                'price' => 0.0,
                'regular_price' => 0.0,
            ];
        }

        if (!isset($display_map[$selected_capacity])) {
            return [
                'capacity' => '',
                'price' => 0.0,
                'regular_price' => 0.0,
            ];
        }

        $raw_map = isset($pack_price_maps['raw']) && is_array($pack_price_maps['raw'])
            ? $pack_price_maps['raw']
            : [];
        $selected_price = (float) $display_map[$selected_capacity];
        $selected_regular_price = isset($raw_map[$selected_capacity])
            ? (float) $raw_map[$selected_capacity]
            : $selected_price;

        return [
            'capacity' => $selected_capacity,
            'price' => $selected_price,
            'regular_price' => max($selected_price, $selected_regular_price),
        ];
    }
}

if (!function_exists('my_theme_get_default_selected_capacity_price_context')) {
    function my_theme_get_default_selected_capacity_price_context($product)
    {
        if (!$product instanceof WC_Product) {
            return [
                'capacity' => '',
                'price' => 0.0,
                'regular_price' => 0.0,
            ];
        }

        $display_map = function_exists('my_theme_get_pack_price_display_map')
            ? my_theme_get_pack_price_display_map($product)
            : my_theme_get_pack_price_map_for_display($product);
        if (empty($display_map)) {
            return [
                'capacity' => '',
                'price' => 0.0,
                'regular_price' => 0.0,
            ];
        }

        $default_capacity = (string) array_key_first($display_map);
        if ($default_capacity === '') {
            return [
                'capacity' => '',
                'price' => 0.0,
                'regular_price' => 0.0,
            ];
        }

        return my_theme_get_selected_capacity_price_context($product, $default_capacity);
    }
}

if (!function_exists('my_theme_get_pack_cart_item_unique_key')) {
    function my_theme_get_pack_cart_item_unique_key($product_id, $selected_capacity = '')
    {
        $product_id = (int) $product_id;
        $selected_capacity = trim((string) $selected_capacity);
        if ($product_id <= 0 || $selected_capacity === '') {
            return '';
        }

        return md5(implode('|', [
            'pack',
            (string) $product_id,
            $selected_capacity,
        ]));
    }
}

if (!function_exists('my_theme_normalize_cart_item_identity_value')) {
    function my_theme_normalize_cart_item_identity_value($value)
    {
        if (is_null($value) || is_scalar($value)) {
            return $value;
        }

        if (!is_array($value)) {
            return null;
        }

        $normalized = [];
        foreach ($value as $item_key => $item_value) {
            if (!is_scalar($item_key) && !is_null($item_key)) {
                continue;
            }

            $normalized_value = my_theme_normalize_cart_item_identity_value($item_value);
            if ($normalized_value === null && !is_null($item_value)) {
                continue;
            }

            $normalized[$item_key] = $normalized_value;
        }

        if (!empty($normalized) && array_keys($normalized) !== range(0, count($normalized) - 1)) {
            ksort($normalized);
        }

        return $normalized;
    }
}

if (!function_exists('my_theme_sync_cart_item_selected_capacity')) {
    function my_theme_sync_cart_item_selected_capacity($cart_item, $product = null)
    {
        if (!is_array($cart_item)) {
            return $cart_item;
        }

        $product = ($product instanceof WC_Product)
            ? $product
            : ((isset($cart_item['data']) && $cart_item['data'] instanceof WC_Product) ? $cart_item['data'] : null);
        if (!$product instanceof WC_Product) {
            return $cart_item;
        }

        $selected_capacity = isset($cart_item['selected_capacity']) ? trim((string) $cart_item['selected_capacity']) : '';
        $selected_context = [
            'capacity' => '',
            'price' => 0.0,
            'regular_price' => 0.0,
        ];

        if ($selected_capacity !== '') {
            $selected_context = my_theme_get_selected_capacity_price_context($product, $selected_capacity);
        }

        if ($selected_context['capacity'] === '' || $selected_context['price'] <= 0) {
            $selected_context = my_theme_get_default_selected_capacity_price_context($product);
        }

        if ($selected_context['capacity'] !== '' && $selected_context['price'] > 0) {
            $cart_item['selected_capacity'] = (string) $selected_context['capacity'];
            $cart_item['selected_capacity_price'] = (float) $selected_context['price'];
            $cart_item['selected_capacity_regular_price'] = (float) $selected_context['regular_price'];
            $pack_key = my_theme_get_pack_cart_item_unique_key($product->get_id(), $selected_context['capacity']);
            if ($pack_key !== '') {
                $cart_item['my_theme_pack_key'] = $pack_key;
            }
        } else {
            unset(
                $cart_item['selected_capacity'],
                $cart_item['selected_capacity_price'],
                $cart_item['selected_capacity_regular_price'],
                $cart_item['my_theme_pack_key'],
                $cart_item['unique_key']
            );
        }

        return $cart_item;
    }
}

if (!function_exists('my_theme_get_cart_item_identity_data')) {
    function my_theme_get_cart_item_identity_data($cart_item)
    {
        if (!is_array($cart_item)) {
            return [];
        }

        $identity_data = [];
        $excluded_keys = [
            'key',
            'product_id',
            'variation_id',
            'variation',
            'quantity',
            'data',
            'data_hash',
            'line_tax_data',
            'line_subtotal',
            'line_subtotal_tax',
            'line_total',
            'line_tax',
            'selected_capacity_price',
            'selected_capacity_regular_price',
            'unique_key',
            'my_theme_pack_key',
        ];

        foreach ($cart_item as $data_key => $data_value) {
            if (in_array((string) $data_key, $excluded_keys, true)) {
                continue;
            }

            $normalized_value = function_exists('my_theme_normalize_cart_item_identity_value')
                ? my_theme_normalize_cart_item_identity_value($data_value)
                : $data_value;
            if ($normalized_value === null && !is_null($data_value)) {
                continue;
            }

            $identity_data[$data_key] = $normalized_value;
        }

        if (!empty($cart_item['selected_capacity'])) {
            $product_id = isset($cart_item['product_id']) ? (int) $cart_item['product_id'] : 0;
            $pack_key = my_theme_get_pack_cart_item_unique_key($product_id, (string) $cart_item['selected_capacity']);
            if ($pack_key !== '') {
                $identity_data['my_theme_pack_key'] = $pack_key;
            }
        } else {
            unset($identity_data['my_theme_pack_key']);
        }

        return $identity_data;
    }
}

// Lưu dung tích vào cart item
add_filter('woocommerce_add_to_cart_validation', function ($passed, $product_id, $quantity, $variation_id = 0, $variations = [], $cart_item_data = []) {
    if (!$passed || !isset($_POST['selected_capacity'])) {
        return $passed;
    }

    $product = wc_get_product($product_id);
    if (!$product instanceof WC_Product) {
        return $passed;
    }

    $display_map = function_exists('my_theme_get_pack_price_display_map')
        ? my_theme_get_pack_price_display_map($product)
        : my_theme_get_pack_price_map_for_display($product);
    if (empty($display_map)) {
        return $passed;
    }

    $selected_capacity = wc_clean(wp_unslash($_POST['selected_capacity']));
    $selected_context = ($selected_capacity !== '')
        ? my_theme_get_selected_capacity_price_context($product, $selected_capacity)
        : ['capacity' => '', 'price' => 0.0, 'regular_price' => 0.0];
    if ($selected_context['capacity'] !== '' && $selected_context['price'] > 0) {
        return $passed;
    }

    if (function_exists('wc_add_notice')) {
        wc_add_notice('Quy cách bạn chọn không còn hợp lệ. Vui lòng chọn lại trước khi thêm vào giỏ.', 'error');
    }

    return false;
}, 20, 6);

add_filter('woocommerce_add_cart_item_data', function ($cart_item_data, $product_id) {
    $selected_capacity = '';
    if (isset($_POST['selected_capacity'])) {
        $selected_capacity = wc_clean(wp_unslash($_POST['selected_capacity']));
    }
    $product = wc_get_product($product_id);
    if ($product instanceof WC_Product) {
        $selected_context = ($selected_capacity !== '')
            ? my_theme_get_selected_capacity_price_context($product, $selected_capacity)
            : my_theme_get_default_selected_capacity_price_context($product);
        if ($selected_context['capacity'] !== '' && $selected_context['price'] > 0) {
            $selected_capacity = (string) $selected_context['capacity'];
        } elseif ($selected_capacity !== '') {
            $selected_capacity = '';
        }
    }

    if ($selected_capacity !== '') {
        $cart_item_data['selected_capacity'] = $selected_capacity;
        $pack_key = my_theme_get_pack_cart_item_unique_key($product_id, $selected_capacity);
        if ($pack_key !== '') {
            $cart_item_data['my_theme_pack_key'] = $pack_key;
        }
    }

    return $cart_item_data;
}, 10, 2);

add_filter('woocommerce_add_cart_item', function ($cart_item) {
    return function_exists('my_theme_sync_cart_item_selected_capacity')
        ? my_theme_sync_cart_item_selected_capacity($cart_item)
        : $cart_item;
}, 20);

add_filter('woocommerce_get_cart_item_from_session', function ($cart_item, $values, $cart_item_key) {
    if (!is_array($cart_item) || !is_array($values)) {
        return $cart_item;
    }

    if (!empty($values['selected_capacity'])) {
        $cart_item['selected_capacity'] = (string) $values['selected_capacity'];
    }
    if (!empty($values['my_theme_pack_key'])) {
        $cart_item['my_theme_pack_key'] = (string) $values['my_theme_pack_key'];
    }

    unset($cart_item['unique_key']);

    return function_exists('my_theme_sync_cart_item_selected_capacity')
        ? my_theme_sync_cart_item_selected_capacity($cart_item)
        : $cart_item;
}, 20, 3);

add_action('woocommerce_cart_loaded_from_session', function ($cart) {
    if (!$cart instanceof WC_Cart || empty($cart->cart_contents) || !method_exists($cart, 'generate_cart_id')) {
        return;
    }

    $normalized_contents = [];
    $did_change = false;

    foreach ($cart->cart_contents as $cart_item_key => $cart_item) {
        if (!is_array($cart_item)) {
            continue;
        }

        if (function_exists('my_theme_sync_cart_item_selected_capacity')) {
            $cart_item = my_theme_sync_cart_item_selected_capacity($cart_item);
        }

        $product_id = isset($cart_item['product_id']) ? (int) $cart_item['product_id'] : 0;
        $variation_id = isset($cart_item['variation_id']) ? (int) $cart_item['variation_id'] : 0;
        $variation = isset($cart_item['variation']) && is_array($cart_item['variation']) ? $cart_item['variation'] : [];
        $identity_data = function_exists('my_theme_get_cart_item_identity_data')
            ? my_theme_get_cart_item_identity_data($cart_item)
            : [];
        $normalized_key = $cart->generate_cart_id($product_id, $variation_id, $variation, $identity_data);
        if ($normalized_key === '') {
            $normalized_key = (string) $cart_item_key;
        }

        if (isset($normalized_contents[$normalized_key]) && is_array($normalized_contents[$normalized_key])) {
            $normalized_contents[$normalized_key]['quantity'] = (int) ($normalized_contents[$normalized_key]['quantity'] ?? 0) + (int) ($cart_item['quantity'] ?? 0);
            $did_change = true;
            continue;
        }

        $normalized_contents[$normalized_key] = $cart_item;
        if ((string) $cart_item_key !== $normalized_key) {
            $did_change = true;
        }
    }

    if ($did_change) {
        $cart->cart_contents = $normalized_contents;
    }
}, 20);

if (!function_exists('my_theme_get_cart_item_price_html')) {
    function my_theme_get_cart_item_price_html($cart_item)
    {
        if (!is_array($cart_item)) {
            return '';
        }

        $product = isset($cart_item['data']) ? $cart_item['data'] : null;
        if ($product instanceof WC_Product && function_exists('my_theme_sync_cart_item_selected_capacity')) {
            $cart_item = my_theme_sync_cart_item_selected_capacity($cart_item, $product);
        }

        $selected_capacity = isset($cart_item['selected_capacity']) ? trim((string) $cart_item['selected_capacity']) : '';
        $selected_price = isset($cart_item['selected_capacity_price']) ? (float) $cart_item['selected_capacity_price'] : 0.0;
        $selected_regular_price = isset($cart_item['selected_capacity_regular_price']) ? (float) $cart_item['selected_capacity_regular_price'] : 0.0;

        if ($selected_capacity !== '' && $selected_price > 0) {
            if ($selected_regular_price > $selected_price) {
                return '<span class="cart-item__price-value cart-item__price-value--sale"><del>' . wp_kses_post(wc_price($selected_regular_price)) . '</del><ins>' . wp_kses_post(wc_price($selected_price)) . '</ins></span>';
            }

            return '<span class="cart-item__price-value">' . wp_kses_post(wc_price($selected_price)) . '</span>';
        }

        if ($product instanceof WC_Product) {
            $price_value = function_exists('my_theme_get_default_loop_price')
                ? (float) my_theme_get_default_loop_price($product)
                : (float) $product->get_price();
            $regular_price_value = function_exists('my_theme_get_default_loop_regular_price')
                ? (float) my_theme_get_default_loop_regular_price($product)
                : (float) $product->get_regular_price();

            if ($price_value > 0) {
                if ($regular_price_value > $price_value) {
                    return '<span class="cart-item__price-value cart-item__price-value--sale"><del>' . wp_kses_post(wc_price($regular_price_value)) . '</del><ins>' . wp_kses_post(wc_price($price_value)) . '</ins></span>';
                }

                return '<span class="cart-item__price-value">' . wp_kses_post(wc_price($price_value)) . '</span>';
            }

            return '<span class="cart-item__price-value">Liên hệ báo giá</span>';
        }

        return '';
    }
}

// Hiển thị dung tích trong cart/checkout
add_filter('woocommerce_get_item_data', function ($item_data, $cart_item) {
    if (!empty($cart_item['selected_capacity'])) {
        $pack_label = my_theme_get_pack_meta_label($cart_item['selected_capacity']);
        $item_data[] = [
            'name' => $pack_label,
            'value' => $cart_item['selected_capacity'],
        ];
    }
    return $item_data;
}, 10, 2);

add_filter('woocommerce_cart_item_price', function ($price_html, $cart_item) {
    $selected_price_html = function_exists('my_theme_get_cart_item_price_html')
        ? my_theme_get_cart_item_price_html($cart_item)
        : '';
    return ($selected_price_html !== '') ? $selected_price_html : $price_html;
}, 20, 2);

// Lưu dung tích đã chọn vào order items để hiển thị trong admin/email.
add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values) {
    if (!empty($values['selected_capacity'])) {
        $pack_label = my_theme_get_pack_meta_label($values['selected_capacity']);
        $item->add_meta_data($pack_label, $values['selected_capacity'], true);
    }
}, 10, 3);

// Set giá theo dung tích đã chọn
add_action('woocommerce_before_calculate_totals', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    if (!$cart instanceof WC_Cart) {
        return;
    }

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        if (empty($cart_item['data']) || !$cart_item['data'] instanceof WC_Product) {
            continue;
        }

        $product_for_cart = null;
        $product_id = (int) $cart_item['data']->get_id();
        if ($product_id > 0) {
            $base_product = wc_get_product($product_id);
            if ($base_product instanceof WC_Product) {
                $product_for_cart = clone $base_product;
            }
        }
        if (!$product_for_cart instanceof WC_Product) {
            $product_for_cart = clone $cart_item['data'];
        }

        $cart_item['data'] = $product_for_cart;
        if (function_exists('my_theme_sync_cart_item_selected_capacity')) {
            $cart_item = my_theme_sync_cart_item_selected_capacity($cart_item, $product_for_cart);
        }

        $selected_capacity = !empty($cart_item['selected_capacity']) ? (string) $cart_item['selected_capacity'] : '';
        $selected_price = !empty($cart_item['selected_capacity_price']) ? (float) $cart_item['selected_capacity_price'] : 0.0;
        $selected_regular_price = !empty($cart_item['selected_capacity_regular_price'])
            ? (float) $cart_item['selected_capacity_regular_price']
            : $selected_price;

        if ($selected_capacity !== '' && $selected_price > 0) {
            my_theme_apply_runtime_price_override($product_for_cart, $selected_price, $selected_regular_price);
            $product_for_cart->set_price($selected_price);
            $product_for_cart->set_regular_price(max($selected_price, $selected_regular_price));
            $product_for_cart->set_sale_price($selected_regular_price > $selected_price ? $selected_price : '');
        } else {
            my_theme_apply_runtime_price_override($product_for_cart, 0.0, 0.0);
        }

        $cart->cart_contents[$cart_item_key] = $cart_item;
    }
}, 100, 1);

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
// Run only when explicitly enabled with a signed admin URL.
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

    my_theme_require_admin_get_action_nonce('legacy_import');

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
        'apollo' => 'Apollo',
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
// Run only when explicitly enabled with a signed admin URL.
add_action('admin_init', function () {
    if (!is_admin() || empty($_GET['import_official'])) {
        return;
    }

    if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
        wp_die('Permission denied.');
    }

    my_theme_require_admin_get_action_nonce('official_import');

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
                return my_theme_compare_pack_labels($a, $b);
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

        $price_map = function_exists('my_theme_get_pack_price_raw_map')
            ? my_theme_get_pack_price_raw_map($product)
            : my_theme_get_pack_price_map_for_display($product, false);
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

        $existing_map = function_exists('my_theme_get_pack_price_raw_map')
            ? my_theme_get_pack_price_raw_map($product)
            : my_theme_get_pack_price_map_for_display($product, false);
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

        $map = function_exists('my_theme_get_pack_price_raw_map')
            ? my_theme_get_pack_price_raw_map($product)
            : my_theme_get_pack_price_map_for_display($product, false);
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

        $catalog_profile = function_exists('my_theme_get_product_catalog_profile')
            ? my_theme_get_product_catalog_profile($product)
            : [];
        $brand = isset($catalog_profile['brand_label']) ? trim((string) $catalog_profile['brand_label']) : '';
        if ($brand === 'Sản phẩm') {
            $brand = '';
        }
        $category = isset($catalog_profile['category_label']) ? trim((string) $catalog_profile['category_label']) : '';

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

        $store_snapshot = function_exists('my_theme_get_store_snapshot') ? my_theme_get_store_snapshot() : [];
        $quote_url = home_url('/lien-he/');
        $colour_options = function_exists('my_theme_get_single_product_colour_picker_options')
            ? my_theme_get_single_product_colour_picker_options($product)
            : [];
        $has_colour_picker = !empty($colour_options);
        $default_colour = $has_colour_picker ? $colour_options[0] : [];
        $default_colour_code = trim((string) ($default_colour['code'] ?? ''));
        $default_colour_name = trim((string) ($default_colour['name'] ?? ''));
        $default_product_code = trim((string) ($default_colour['product_code'] ?? ''));
        $catalog_profile = function_exists('my_theme_get_product_catalog_profile')
            ? my_theme_get_product_catalog_profile($product)
            : [];
        $product_name = isset($catalog_profile['display_name']) && (string) $catalog_profile['display_name'] !== ''
            ? trim((string) $catalog_profile['display_name'])
            : trim((string) $product->get_name());
        if ($has_colour_picker) {
            $quote_url = add_query_arg([
                'lead_product' => $product_name,
                'lead_colour_code' => $default_colour_code,
                'lead_colour_name' => $default_colour_name,
                'lead_product_code' => $default_product_code,
                'source' => 'product-colour-picker',
            ], $quote_url);
        }

        echo '<div class="single-product-actions single-product-actions--contact">';
        echo '<a class="btn btn-primary" href="' . esc_url(isset($store_snapshot['phone_href']) ? (string) $store_snapshot['phone_href'] : 'tel:0944857999') . '" data-colour-cta="phone" data-base-href="' . esc_url(isset($store_snapshot['phone_href']) ? (string) $store_snapshot['phone_href'] : 'tel:0944857999') . '"><span data-phone-label>' . esc_html($has_colour_picker && $default_colour_code !== '' ? ('Gọi báo giá mã ' . $default_colour_code) : 'Gọi báo giá') . '</span></a>';
        echo '<a class="btn btn-outline" href="' . esc_url(isset($store_snapshot['zalo_url']) ? (string) $store_snapshot['zalo_url'] : 'https://zalo.me/0944857999') . '" data-colour-cta="zalo" data-base-href="' . esc_url(isset($store_snapshot['zalo_url']) ? (string) $store_snapshot['zalo_url'] : 'https://zalo.me/0944857999') . '" target="_blank" rel="noopener">Zalo tư vấn</a>';
        echo '<a class="btn btn-accent" href="' . esc_url($quote_url) . '" data-colour-cta="contact" data-base-href="' . esc_url(home_url('/lien-he/')) . '">Gửi yêu cầu</a>';
        echo '</div>';
        if ($has_colour_picker) {
            $default_selection_text = $default_product_code !== ''
                ? $default_product_code . ($default_colour_name !== '' ? ' - ' . $default_colour_name : '')
                : $default_colour_code;
            echo '<p class="single-product-actions__selection-note" data-colour-selection-note>Đang chọn mã ' . esc_html($default_selection_text) . '. Khi gửi yêu cầu, form sẽ mang theo đúng mã này.</p>';
        }
    }
}

add_action('woocommerce_single_product_summary', 'my_theme_render_single_contact_actions', 31);

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
        'apollo' => 'Apollo',
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

        if (preg_match('/^(dulux|jotun|nippon|kova|apollo)-(bot-tret|son)-(.+)$/', $key, $m)) {
            $brand_slug = $m[1];
            $type_label = ($m[2] === 'bot-tret') ? 'Bột trét' : 'Sơn';
            $line_slug = $m[3];
        } elseif (preg_match('/^(dulux|jotun|nippon|kova|apollo)-(.+)$/', $key, $m)) {
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
    $decoded_request_path = trim((string) rawurldecode($request_path), '/');
    if ($decoded_request_path !== '' && sanitize_title($decoded_request_path) === 'trang-mau') {
        $sample_page = get_page_by_path('trang-mau');
        if ($sample_page instanceof WP_Post) {
            wp_safe_redirect(get_permalink($sample_page), 301);
        } else {
            wp_safe_redirect(home_url('/'), 301);
        }
        exit;
    }

    $shop_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop/');
    $legacy_map = [
        'thanh-toan' => function_exists('my_theme_get_checkout_url_safe') ? my_theme_get_checkout_url_safe() : home_url('/thanh-toan'),
        'gio-hang' => function_exists('my_theme_get_cart_url_safe') ? my_theme_get_cart_url_safe() : home_url('/gio-hang'),
        'tinh-son' => my_theme_get_paint_calculator_url(),
        'product/son-dulux-easyclean-noi-that' => home_url('/product/duluxeasycleanlauchuihieuquabematmo/'),
        'product/son-dulux-weathershield-ngoai-that' => home_url('/product/duluxweathershieldbematbong/'),
        'product/son-maxilite-noi-that-5l' => home_url('/product/sonnuocnoithatmaxilitehi-covertudulux/'),
        'product/son-nippon-odour-less-noi-that' => add_query_arg('q', 'nippon', $shop_url),
        'product/bot-tret-kova-noi-that' => add_query_arg('q', 'kova', $shop_url),
        'product/bot-tret-kova-ngoai-that' => add_query_arg('q', 'kova', $shop_url),
        'product/son-kova-ngoai-that-effective-chuyen-dung' => add_query_arg('q', 'kova', $shop_url),
        'product/webercolor-classic' => home_url('/product/keo-cha-ron-webercolor-classic/'),
        'product/webercolor-classic-2023' => home_url('/product/keo-cha-ron-webercolor-classic/'),
        'product/webercolor-classic-ps' => home_url('/product/keo-cha-ron-webercolor-classic/'),
        'product/webertai-gres' => home_url('/product/keo-dan-gach-webertai-gres-40kg/'),
        'product/webertai-vis' => home_url('/product/keo-dan-gach-webertai-vis-40kg/'),
    ];

    if (!isset($legacy_map[$request_path])) {
        return;
    }

    wp_safe_redirect($legacy_map[$request_path], 301);
    exit;
}, 1);

if (!function_exists('my_theme_render_sitemap_urlset')) {
    function my_theme_render_sitemap_urlset($post_type = 'page')
    {
        $post_type = sanitize_key((string) $post_type);
        $allowed = ['page', 'post', 'product'];
        if (!in_array($post_type, $allowed, true)) {
            $post_type = 'page';
        }

        $ids = get_posts([
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => 5000,
            'fields'         => 'ids',
            'orderby'        => 'modified',
            'order'          => 'DESC',
        ]);

        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach ((array) $ids as $id) {
            $loc = get_permalink((int) $id);
            if (!is_string($loc) || $loc === '') {
                continue;
            }
            $modified = get_post_modified_time('c', true, (int) $id);
            $modified = is_string($modified) && $modified !== '' ? $modified : gmdate('c');
            echo "  <url>\n";
            echo "    <loc>" . esc_url($loc) . "</loc>\n";
            echo "    <lastmod>" . esc_html($modified) . "</lastmod>\n";
            echo "  </url>\n";
        }
        echo "</urlset>\n";
    }
}

if (!function_exists('my_theme_render_sitemap_index')) {
    function my_theme_render_sitemap_index()
    {
        $sections = [
            'pages' => home_url('/sitemap-pages.xml'),
            'posts' => home_url('/sitemap-posts.xml'),
        ];
        if (post_type_exists('product')) {
            $sections['products'] = home_url('/sitemap-products.xml');
        }

        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        $lastmod = gmdate('c');
        foreach ($sections as $url) {
            echo "  <sitemap>\n";
            echo "    <loc>" . esc_url($url) . "</loc>\n";
            echo "    <lastmod>" . esc_html($lastmod) . "</lastmod>\n";
            echo "  </sitemap>\n";
        }
        echo "</sitemapindex>\n";
    }
}

// Serve XML sitemaps without requiring extra plugins.
add_action('template_redirect', function () {
    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    if ($request_uri === '') {
        return;
    }
    $request_path = trim((string) wp_parse_url($request_uri, PHP_URL_PATH), '/');
    if ($request_path === '') {
        return;
    }

    $path_map = [
        'sitemap.xml' => 'index',
        'sitemap-pages.xml' => 'page',
        'sitemap-posts.xml' => 'post',
        'sitemap-products.xml' => 'product',
    ];
    if (!isset($path_map[$request_path])) {
        return;
    }

    if ($path_map[$request_path] === 'product' && !post_type_exists('product')) {
        status_header(404);
        exit;
    }

    status_header(200);
    nocache_headers();
    header('Content-Type: application/xml; charset=utf-8');
    if ($path_map[$request_path] === 'index') {
        my_theme_render_sitemap_index();
    } else {
        my_theme_render_sitemap_urlset($path_map[$request_path]);
    }
    exit;
}, 0);

// Ensure robots.txt always advertises the sitemap entry point.
add_filter('robots_txt', function ($output, $public) {
    $sitemap = home_url('/sitemap.xml');
    $normalized = trim((string) $output);
    if ($normalized === '') {
        $normalized = "User-agent: *\nDisallow:";
    }
    if (stripos($normalized, 'Sitemap:') === false) {
        $normalized .= "\nSitemap: " . $sitemap;
    }
    return $normalized . "\n";
}, 20, 2);

// Add baseline security headers at application layer (web server can still override).
add_action('send_headers', function () {
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    if (is_ssl()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
}, 20);

if (!function_exists('my_theme_fix_legacy_sample_page_slug')) {
    function my_theme_fix_legacy_sample_page_slug()
    {
        global $wpdb;
        if (!$wpdb instanceof wpdb) {
            return;
        }

        $legacy_slug = 'Trang mẫu';
        $target_slug = 'trang-mau';
        $query = $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_name = %s",
            $legacy_slug
        );
        $ids = $wpdb->get_col($query);
        if (empty($ids) || !is_array($ids)) {
            return;
        }

        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id <= 0) {
                continue;
            }
            wp_update_post([
                'ID' => $id,
                'post_name' => $target_slug,
            ]);
        }
    }
}

// Normalize legacy sample page slug generated with spaces/accents.
add_action('init', 'my_theme_fix_legacy_sample_page_slug', 5);
