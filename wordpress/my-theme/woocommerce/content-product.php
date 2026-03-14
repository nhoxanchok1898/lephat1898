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

$catalog_profile = function_exists('my_theme_get_product_catalog_profile')
    ? my_theme_get_product_catalog_profile($product)
    : [];
$brand_label = isset($catalog_profile['brand_label']) ? (string) $catalog_profile['brand_label'] : 'Sản phẩm';
$line_label = isset($catalog_profile['line_label']) ? (string) $catalog_profile['line_label'] : '';
$cat_label = isset($catalog_profile['category_label']) ? (string) $catalog_profile['category_label'] : '';
$product_name = isset($catalog_profile['display_name']) && (string) $catalog_profile['display_name'] !== ''
    ? (string) $catalog_profile['display_name']
    : $product->get_name();
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
$brand_class = 'product-card__brand' . ($brand_display === '' ? ' product-card__brand--empty' : '');
$meta_secondary_parts = [];
if ($line_display !== '') {
    $meta_secondary_parts[] = $line_display;
}
if ($cat_display !== '') {
    $meta_secondary_parts[] = $cat_display;
}
$meta_secondary = implode(' · ', $meta_secondary_parts);
$in_stock = $product->is_in_stock();
$stock_label = $in_stock ? 'Còn hàng' : 'Hết hàng';
$stock_class = 'product-card__stock ' . ($in_stock ? 'is-in' : 'is-out');
$media_state = function_exists('my_theme_get_product_card_media_state')
    ? my_theme_get_product_card_media_state($product)
    : [];
$thumb_id = isset($media_state['thumb_id']) ? (int) $media_state['thumb_id'] : 0;
$thumb_class = isset($media_state['thumb_class']) ? (string) $media_state['thumb_class'] : 'product-card__thumb';
$has_placeholder_thumb = !empty($media_state['has_placeholder']);
$pack_price_map = function_exists('my_theme_get_pack_price_display_map')
    ? my_theme_get_pack_price_display_map($product)
    : (function_exists('my_theme_get_pack_price_map_for_display')
        ? my_theme_get_pack_price_map_for_display($product)
        : []);
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
        if ($thumb_id > 0 && !$has_placeholder_thumb) {
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
        <div class="product-card__meta-top">
            <div class="<?php echo esc_attr($brand_class); ?>"<?php echo ($brand_display === '') ? ' aria-hidden="true"' : ''; ?>><?php echo esc_html($brand_display); ?></div>
            <span class="<?php echo esc_attr($stock_class); ?>"><?php echo esc_html($stock_label); ?></span>
        </div>
        <h2 class="woocommerce-loop-product__title"><a href="<?php the_permalink(); ?>"><?php echo esc_html($product_name); ?></a></h2>
        <div class="product-card__meta-secondary<?php echo $meta_secondary === '' ? ' product-card__meta-secondary--empty' : ''; ?>"<?php echo $meta_secondary === '' ? ' aria-hidden="true"' : ''; ?>><?php echo $meta_secondary !== '' ? esc_html($meta_secondary) : '&nbsp;'; ?></div>
        <?php if (function_exists('my_theme_render_loop_price')) { my_theme_render_loop_price($product); } else { woocommerce_template_loop_price(); } ?>
        <?php if (function_exists('my_theme_render_loop_pack_summary')) { my_theme_render_loop_pack_summary($product); } ?>
    </div>

    <div class="<?php echo esc_attr($actions_class); ?>">
        <?php if (function_exists('my_theme_render_loop_add_to_cart')) { my_theme_render_loop_add_to_cart($product); } else { woocommerce_template_loop_add_to_cart(); } ?>
    </div>
</li>
