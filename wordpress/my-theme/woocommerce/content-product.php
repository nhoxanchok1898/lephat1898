<?php
/**
 * Custom loop product card.
 */

defined('ABSPATH') || exit;

global $product;

if (empty($product) || !$product->is_visible()) {
    return;
}
if (function_exists('my_theme_is_shop_visible_product') && !my_theme_is_shop_visible_product($product)) {
    return;
}
if (function_exists('my_theme_is_placeholder_product_name') && my_theme_is_placeholder_product_name($product->get_name())) {
    return;
}

$brand_label = function_exists('my_theme_get_product_brand_label') ? my_theme_get_product_brand_label($product) : 'Sản phẩm';
$line_label = function_exists('my_theme_get_product_line_label') ? my_theme_get_product_line_label($product) : '';
$cat_label = function_exists('my_theme_get_product_primary_category_label') ? my_theme_get_product_primary_category_label($product) : '';
$product_name = function_exists('my_theme_get_product_display_name') ? my_theme_get_product_display_name($product) : $product->get_name();
$brand_display = ($brand_label !== '' && $brand_label !== 'Sản phẩm') ? (string) $brand_label : '';
$line_display = ($line_label !== '') ? (string) $line_label : '';
$cat_display = ($cat_label !== '') ? (string) $cat_label : '';
if ($line_display !== '' && $cat_display !== '') {
    $line_norm = function_exists('my_theme_normalize_search_text')
        ? my_theme_normalize_search_text($line_display)
        : strtolower((string) $line_display);
    $cat_norm = function_exists('my_theme_normalize_search_text')
        ? my_theme_normalize_search_text($cat_display)
        : strtolower((string) $cat_display);
    if ($line_norm === $cat_norm) {
        $line_display = '';
    }
}
$excerpt = function_exists('my_theme_get_product_card_excerpt') ? trim((string) my_theme_get_product_card_excerpt($product, 18)) : '';
$excerpt_class = 'product-card__excerpt' . ($excerpt === '' ? ' product-card__excerpt--empty' : '');
$brand_class = 'product-card__brand' . ($brand_display === '' ? ' product-card__brand--empty' : '');
$line_class = 'product-card__line' . ($line_display === '' ? ' product-card__line--empty' : '');
$cat_class = 'product-card__taxonomy' . ($cat_display === '' ? ' product-card__taxonomy--empty' : '');
$thumb_id = function_exists('my_theme_get_preferred_product_image_id')
    ? (int) my_theme_get_preferred_product_image_id($product)
    : (int) $product->get_image_id();
$thumb_class = 'product-card__thumb';
if ($thumb_id > 0) {
    $thumb_meta = wp_get_attachment_metadata($thumb_id);
    $thumb_w = isset($thumb_meta['width']) ? (int) $thumb_meta['width'] : 0;
    $thumb_h = isset($thumb_meta['height']) ? (int) $thumb_meta['height'] : 0;
    if ($thumb_w > 0 && $thumb_h > 0) {
        if ($thumb_w < 320 || $thumb_h < 320) {
            $thumb_class .= ' product-card__thumb--small-source';
        }
        $thumb_ratio = $thumb_h > 0 ? ((float) $thumb_w / (float) $thumb_h) : 1.0;
        if ($thumb_ratio > 1.8 || $thumb_ratio < 0.55) {
            $thumb_class .= ' product-card__thumb--extreme-ratio';
        }
    }
}
if ($thumb_id <= 0) {
    $thumb_class .= ' product-card__thumb--fallback';
}

$swatches_html = '';
if (function_exists('my_theme_render_product_colour_swatches')) {
    ob_start();
    my_theme_render_product_colour_swatches($product, ['context' => 'loop', 'limit' => 5]);
    $swatches_html = trim((string) ob_get_clean());
}
$pack_price_map = function_exists('my_theme_get_pack_price_map_for_display')
    ? my_theme_get_pack_price_map_for_display($product)
    : [];
$actions_class = 'product-card__actions' . (!empty($pack_price_map) ? ' product-card__actions--pack' : ' product-card__actions--simple');
?>
<li <?php wc_product_class('product-card', $product); ?>>
    <a
      class="<?php echo esc_attr($thumb_class); ?>"
      href="<?php the_permalink(); ?>"
      aria-label="<?php echo esc_attr('Xem sản phẩm ' . $product_name); ?>"
      title="<?php echo esc_attr($product_name); ?>">
        <?php if ($product->is_on_sale()) : ?><span class="product-card__badge">Giảm giá</span><?php endif; ?>
        <?php
        if ($thumb_id > 0) {
            echo wp_get_attachment_image($thumb_id, 'medium_large', false, [
                'loading' => 'lazy',
                'decoding' => 'async',
                'alt' => $product_name,
                'title' => $product_name,
            ]);
        } else {
            echo wc_placeholder_img('medium_large');
            echo '<span class="product-card__thumb-note">Ảnh sản phẩm đang cập nhật</span>';
        }
        ?>
    </a>

    <div class="product-card__body">
        <div class="<?php echo esc_attr($brand_class); ?>"<?php echo ($brand_display === '') ? ' aria-hidden="true"' : ''; ?>><?php echo esc_html($brand_display); ?></div>
        <div class="<?php echo esc_attr($line_class); ?>"<?php echo ($line_display === '') ? ' aria-hidden="true"' : ''; ?>><?php echo esc_html($line_display); ?></div>
        <h2 class="woocommerce-loop-product__title"><a href="<?php the_permalink(); ?>"><?php echo esc_html($product_name); ?></a></h2>
        <div class="<?php echo esc_attr($cat_class); ?>"<?php echo ($cat_display === '') ? ' aria-hidden="true"' : ''; ?>><?php echo esc_html($cat_display); ?></div>
        <p class="<?php echo esc_attr($excerpt_class); ?>"<?php echo ($excerpt === '') ? ' aria-hidden="true"' : ''; ?>><?php echo ($excerpt !== '') ? esc_html($excerpt) : '&nbsp;'; ?></p>
        <?php if (function_exists('my_theme_render_loop_price')) { my_theme_render_loop_price($product); } else { woocommerce_template_loop_price(); } ?>
        <?php if (function_exists('my_theme_render_loop_pack_summary')) { my_theme_render_loop_pack_summary($product); } ?>
        <div class="product-card__swatches"<?php echo ($swatches_html === '') ? ' aria-hidden="true"' : ''; ?>><?php echo $swatches_html; ?></div>
    </div>

    <div class="<?php echo esc_attr($actions_class); ?>">
        <?php if (function_exists('my_theme_render_loop_add_to_cart')) { my_theme_render_loop_add_to_cart($product); } else { woocommerce_template_loop_add_to_cart(); } ?>
    </div>
</li>
