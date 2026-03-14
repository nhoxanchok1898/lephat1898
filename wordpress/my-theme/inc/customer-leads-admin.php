<?php
/**
 * Admin-only lead management bootstrap.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('my_theme_lead_admin_include_module')) {
    function my_theme_lead_admin_include_module($relative_path)
    {
        $file = get_theme_file_path($relative_path);
        if (!is_string($file) || !file_exists($file)) {
            return;
        }
        require_once $file;
    }
}

my_theme_lead_admin_include_module('inc/customer-leads-admin-meta.php');
my_theme_lead_admin_include_module('inc/customer-leads-admin-settings.php');
my_theme_lead_admin_include_module('inc/customer-leads-admin-report.php');

if (!function_exists('my_theme_lead_admin_enqueue_assets')) {
    function my_theme_lead_admin_enqueue_assets()
    {
        if (!is_admin() || !function_exists('get_current_screen')) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen) {
            return;
        }

        $screen_id = isset($screen->id) ? (string) $screen->id : '';
        $post_type = isset($screen->post_type) ? (string) $screen->post_type : '';
        $allowed_screen_ids = [
            'customer_lead_page_customer-lead-notify',
            'customer_lead_page_customer-lead-webhook',
            'customer_lead_page_customer-lead-report',
            'dashboard',
        ];

        $should_enqueue = ($post_type === 'customer_lead') || in_array($screen_id, $allowed_screen_ids, true);
        if (!$should_enqueue) {
            return;
        }

        $asset = function_exists('my_theme_resolve_theme_asset')
            ? my_theme_resolve_theme_asset('assets/css/admin-leads.css')
            : null;
        if (!is_array($asset) || empty($asset['uri'])) {
            return;
        }

        wp_enqueue_style(
            'my-theme-admin-leads',
            $asset['uri'],
            [],
            isset($asset['ver']) ? (string) $asset['ver'] : null
        );
    }
}
add_action('admin_enqueue_scripts', 'my_theme_lead_admin_enqueue_assets', 20);
