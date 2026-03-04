<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('my_theme_enqueue_optional_stylesheet')) {
    function my_theme_enqueue_optional_stylesheet($handle, $relative_path, $deps = [])
    {
        $path = get_theme_file_path($relative_path);
        if (!is_string($path) || !file_exists($path)) {
            return;
        }
        $ver = filemtime($path);
        wp_enqueue_style($handle, get_theme_file_uri($relative_path), $deps, $ver ?: null);
    }
}

if (!function_exists('my_theme_page_has_shortcode')) {
    function my_theme_page_has_shortcode($shortcode_tag)
    {
        $post_id = (int) get_queried_object_id();
        if ($post_id <= 0) {
            return false;
        }
        $post = get_post($post_id);
        if (!$post instanceof WP_Post) {
            return false;
        }
        $content = (string) $post->post_content;
        if ($content === '' || !function_exists('has_shortcode')) {
            return false;
        }
        return has_shortcode($content, $shortcode_tag);
    }
}

if (!function_exists('my_theme_is_core_woocommerce_page')) {
    function my_theme_is_core_woocommerce_page()
    {
        $is_woo = function_exists('is_woocommerce') && is_woocommerce();
        $is_shop = function_exists('is_shop') && is_shop();
        $is_product = function_exists('is_product') && is_product();
        $is_product_tax = function_exists('is_product_taxonomy') && is_product_taxonomy();
        $is_cart = function_exists('is_cart') && is_cart();
        $is_checkout = function_exists('is_checkout') && is_checkout();
        $is_account = function_exists('is_account_page') && is_account_page();

        return (bool) ($is_woo || $is_shop || $is_product || $is_product_tax || $is_cart || $is_checkout || $is_account);
    }
}

if (!function_exists('my_theme_should_load_paint_calculator_script')) {
    function my_theme_should_load_paint_calculator_script()
    {
        if (is_front_page()) {
            return true;
        }

        if (is_page(['gia-tho', 'bang-gia-son', 'huong-dan-mua-hang'])) {
            return true;
        }

        if (my_theme_page_has_shortcode('paint_calculator')) {
            return true;
        }

        return false;
    }
}

if (!function_exists('my_theme_current_view_likely_uses_blocks')) {
    function my_theme_current_view_likely_uses_blocks()
    {
        if (is_admin() || !function_exists('has_blocks')) {
            return false;
        }

        $post_id = (int) get_queried_object_id();
        if ($post_id <= 0) {
            return false;
        }

        $post = get_post($post_id);
        if (!$post instanceof WP_Post) {
            return false;
        }

        $content = (string) $post->post_content;
        if ($content === '') {
            return false;
        }

        return has_blocks($content);
    }
}

if (!function_exists('my_theme_should_load_lead_capture_assets')) {
    function my_theme_should_load_lead_capture_assets()
    {
        if (is_front_page()) {
            return true;
        }

        if (my_theme_is_core_woocommerce_page()) {
            return true;
        }

        if (is_search() || is_home() || is_archive() || is_singular('post') || is_404()) {
            return true;
        }

        if (my_theme_page_has_shortcode('lead_capture_form') || is_page('lien-he')) {
            return true;
        }

        if (!is_page()) {
            return false;
        }

        $template = (string) get_page_template_slug(get_queried_object_id());
        if ($template !== '' && strpos($template, 'page-giai-phap-') === 0) {
            return true;
        }

        $page = get_post(get_queried_object_id());
        if (!$page instanceof WP_Post) {
            return false;
        }

        $slug = sanitize_title((string) $page->post_name);
        $lead_pages = [
            'lien-he',
            'faq',
            'huong-dan-mua-hang',
            'giai-phap',
            'giai-phap-son-noi-that',
            'giai-phap-son-ngoai-that',
            'giai-phap-chong-tham',
            'giai-phap-son-epoxy',
            'giai-phap-son-kim-loai',
            'giai-phap-keo-va-ron',
        ];

        return in_array($slug, $lead_pages, true);
    }
}

add_action('wp_enqueue_scripts', function () {
    $asset_rev = '20260224-scroll-fix';
    $style_path = get_stylesheet_directory() . '/style.css';
    $style_mtime = file_exists($style_path) ? (int) filemtime($style_path) : time();
    $style_ver = $style_mtime . '-' . $asset_rev;

    wp_enqueue_style(
        'my-custom-theme-font',
        'https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800;900&display=swap',
        [],
        null
    );
    wp_enqueue_style('my-custom-theme-style', get_stylesheet_uri(), [], $style_ver);

    if (is_home() || is_archive() || is_singular('post')) {
        my_theme_enqueue_optional_stylesheet('my-custom-theme-blog', 'assets/css/blog.css', ['my-custom-theme-style']);
    }

    if (function_exists('is_account_page') && is_account_page()) {
        my_theme_enqueue_optional_stylesheet('my-custom-theme-account', 'assets/css/account.css', ['my-custom-theme-style']);
    }

    if (my_theme_should_load_lead_capture_assets()) {
        my_theme_enqueue_optional_stylesheet('my-custom-theme-lead-capture', 'assets/css/lead-capture.css', ['my-custom-theme-style']);
    }

    $main_js_path = get_theme_file_path('assets/main.js');
    $main_js_mtime = file_exists($main_js_path) ? (int) filemtime($main_js_path) : time();
    $main_js_ver = $main_js_mtime . '-' . $asset_rev;
    wp_enqueue_script('my-custom-theme-main', get_theme_file_uri('assets/main.js'), [], $main_js_ver, true);
    if (function_exists('my_theme_get_search_assist_payload')) {
        wp_add_inline_script(
            'my-custom-theme-main',
            'window.MyThemeSearchAssist = ' . wp_json_encode(my_theme_get_search_assist_payload()) . ';',
            'before'
        );
    }

    if (my_theme_should_load_paint_calculator_script()) {
        $calc_path = get_theme_file_path('assets/paint-calculator.js');
        $calc_mtime = file_exists($calc_path) ? (int) filemtime($calc_path) : time();
        $calc_ver = $calc_mtime . '-' . $asset_rev;
        wp_enqueue_script('my-custom-theme-paint-calculator', get_theme_file_uri('assets/paint-calculator.js'), [], $calc_ver, true);
    }
});

add_filter('wp_resource_hints', function ($urls, $relation_type) {
    if ($relation_type !== 'preconnect') {
        return $urls;
    }

    $hints = [
        'https://fonts.googleapis.com',
        'https://fonts.gstatic.com',
    ];
    foreach ($hints as $hint) {
        if (in_array($hint, $urls, true)) {
            continue;
        }
        if ($hint === 'https://fonts.gstatic.com') {
            $urls[] = [
                'href' => $hint,
                'crossorigin',
            ];
            continue;
        }
        $urls[] = $hint;
    }
    return $urls;
}, 10, 2);

add_action('wp_enqueue_scripts', function () {
    if (is_admin()) {
        return;
    }

    $is_cart_page = function_exists('is_cart') && is_cart();
    $is_checkout_page = function_exists('is_checkout') && is_checkout();
    $is_account_page = function_exists('is_account_page') && is_account_page();
    $is_core_woo_page = my_theme_is_core_woocommerce_page();

    if (!$is_cart_page && !$is_checkout_page && !$is_account_page) {
        wp_dequeue_script('wc-cart-fragments');
    }

    if (!$is_cart_page && !$is_checkout_page) {
        wp_dequeue_style('wc-blocks-style');
        wp_dequeue_style('wc-blocks-vendors-style');
        wp_dequeue_style('wc-blocks-packages-style');
        wp_deregister_style('wc-blocks-style');
        wp_deregister_style('wc-blocks-vendors-style');
        wp_deregister_style('wc-blocks-packages-style');
    }

    if (!$is_cart_page && !$is_checkout_page && !my_theme_current_view_likely_uses_blocks()) {
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_style('classic-theme-styles');
        wp_dequeue_style('global-styles');
        wp_deregister_style('wp-block-library');
        wp_deregister_style('wp-block-library-theme');
        wp_deregister_style('classic-theme-styles');
        wp_deregister_style('global-styles');
    }

    if (!$is_core_woo_page) {
        wp_dequeue_style('woocommerce-layout');
        wp_dequeue_style('woocommerce-smallscreen');
        wp_dequeue_style('woocommerce-general');
    }

    // Attribution scripts are only needed around checkout conversion tracking.
    if (!$is_checkout_page) {
        wp_dequeue_script('sourcebuster-js');
        wp_dequeue_script('wc-order-attribution');
    }

    wp_dequeue_script('wp-embed');

    $optimize_marketing_assets = (bool) apply_filters('my_theme_optimize_marketing_assets', true);
    if ($optimize_marketing_assets && !$is_cart_page && !$is_checkout_page && !$is_account_page) {
        $marketing_fragments = [
            'mailpoet',
            'jetpack',
            'google-listings',
            'gla',
            'pinterest',
            'tiktok',
            'facebook-for-woocommerce',
            'wc-facebook',
        ];

        global $wp_scripts, $wp_styles;
        if (is_object($wp_scripts) && isset($wp_scripts->queue) && is_array($wp_scripts->queue)) {
            foreach ($wp_scripts->queue as $handle) {
                $needle_hit = false;
                foreach ($marketing_fragments as $fragment) {
                    if (strpos((string) $handle, $fragment) !== false) {
                        $needle_hit = true;
                        break;
                    }
                }
                if (!$needle_hit) {
                    continue;
                }
                wp_dequeue_script($handle);
            }
        }
        if (is_object($wp_styles) && isset($wp_styles->queue) && is_array($wp_styles->queue)) {
            foreach ($wp_styles->queue as $handle) {
                $needle_hit = false;
                foreach ($marketing_fragments as $fragment) {
                    if (strpos((string) $handle, $fragment) !== false) {
                        $needle_hit = true;
                        break;
                    }
                }
                if (!$needle_hit) {
                    continue;
                }
                wp_dequeue_style($handle);
            }
        }
    }
}, 999);

add_filter('script_loader_tag', function ($tag, $handle, $src) {
    if (is_admin() || $src === '') {
        return $tag;
    }

    $defer_handles = [
        'my-custom-theme-main',
        'my-custom-theme-paint-calculator',
    ];
    if (!in_array((string) $handle, $defer_handles, true)) {
        return $tag;
    }

    if (strpos($tag, ' defer') !== false) {
        return $tag;
    }

    return str_replace(' src=', ' defer src=', $tag);
}, 10, 3);

add_action('init', function () {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

    // Keep classic-theme frontend lean by disabling automatic block/global styles.
    remove_action('wp_enqueue_scripts', 'wp_common_block_scripts_and_styles');
    remove_action('wp_enqueue_scripts', 'wp_enqueue_classic_theme_styles');
    remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
    remove_action('wp_footer', 'wp_enqueue_global_styles', 1);
});

add_filter('emoji_svg_url', '__return_false');

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
