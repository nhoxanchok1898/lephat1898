<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('my_theme_get_woocommerce_url')) {
    function my_theme_get_woocommerce_url($page = 'shop', $fallback = '')
    {
        $page = sanitize_key((string) $page);
        if ($page === '') {
            $page = 'shop';
        }
        $fallback_url = $fallback !== '' ? (string) $fallback : home_url('/');

        if ($page === 'cart' && function_exists('wc_get_cart_url')) {
            $url = (string) wc_get_cart_url();
            if ($url !== '') {
                return $url;
            }
        }

        if ($page === 'checkout' && function_exists('wc_get_checkout_url')) {
            $url = (string) wc_get_checkout_url();
            if ($url !== '') {
                return $url;
            }
        }

        if (function_exists('wc_get_page_permalink')) {
            $url = (string) wc_get_page_permalink($page);
            if ($url !== '') {
                return $url;
            }
        }

        if (function_exists('wc_get_page_id')) {
            $page_id = (int) wc_get_page_id($page);
            if ($page_id > 0) {
                $permalink = get_permalink($page_id);
                if (is_string($permalink) && $permalink !== '') {
                    return $permalink;
                }
            }
        }

        return $fallback_url;
    }
}

if (!function_exists('my_theme_get_shop_url')) {
    function my_theme_get_shop_url()
    {
        return my_theme_get_woocommerce_url('shop', home_url('/shop'));
    }
}

if (!function_exists('my_theme_get_cart_url_safe')) {
    function my_theme_get_cart_url_safe()
    {
        return my_theme_get_woocommerce_url('cart', home_url('/gio-hang'));
    }
}

if (!function_exists('my_theme_get_checkout_url_safe')) {
    function my_theme_get_checkout_url_safe()
    {
        return my_theme_get_woocommerce_url('checkout', home_url('/thanh-toan'));
    }
}

if (!function_exists('my_theme_get_account_url')) {
    function my_theme_get_account_url()
    {
        return my_theme_get_woocommerce_url('myaccount', wp_login_url());
    }
}

if (!function_exists('my_theme_get_account_login_url')) {
    function my_theme_get_account_login_url()
    {
        return add_query_arg('login', '1', my_theme_get_account_url());
    }
}
