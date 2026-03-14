<?php
if (!defined('ABSPATH')) {
    exit;
}

// Convert variation dropdowns to button groups for faster size/capacity selection.
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
            const v = $(this).val(); if(!v) return; // skip blank option
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

// Keep one main product image while switching variation attributes.
add_filter('woocommerce_available_variation', function ($data) {
    $data['image'] = false;
    return $data;
});

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
