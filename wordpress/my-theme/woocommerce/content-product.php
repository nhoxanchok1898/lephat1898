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
if (!has_post_thumbnail($product->get_id())) {
    return;
}

$brand_label = function_exists('my_theme_get_product_brand_label') ? my_theme_get_product_brand_label($product) : 'Sản phẩm';
$cat_label = function_exists('my_theme_get_product_primary_category_label') ? my_theme_get_product_primary_category_label($product) : '';
$product_name = function_exists('my_theme_get_product_display_name') ? my_theme_get_product_display_name($product) : $product->get_name();
$excerpt = function_exists('my_theme_get_product_card_excerpt') ? my_theme_get_product_card_excerpt($product, 14) : '';
?>
<li <?php wc_product_class('product-card', $product); ?>>
    <a class="product-card__thumb" href="<?php the_permalink(); ?>">
        <?php if ($product->is_on_sale()) : ?><span class="product-card__badge">Giảm giá</span><?php endif; ?>
        <?php woocommerce_template_loop_product_thumbnail(); ?>
    </a>

    <div class="product-card__body">
        <?php if ($brand_label !== '' && $brand_label !== 'Sản phẩm') : ?>
            <div class="product-card__brand"><?php echo esc_html($brand_label); ?></div>
        <?php endif; ?>
        <h2 class="woocommerce-loop-product__title"><a href="<?php the_permalink(); ?>"><?php echo esc_html($product_name); ?></a></h2>
        <?php if ($cat_label !== '') : ?><div class="product-card__taxonomy"><?php echo esc_html($cat_label); ?></div><?php endif; ?>
        <?php if ($excerpt !== '') : ?><p class="product-card__excerpt"><?php echo esc_html($excerpt); ?></p><?php endif; ?>
        <?php if (function_exists('my_theme_render_loop_price')) { my_theme_render_loop_price($product); } else { woocommerce_template_loop_price(); } ?>
        <?php if (function_exists('my_theme_render_pack_price_list')) { my_theme_render_pack_price_list($product, 'loop'); } ?>
        <?php if (function_exists('my_theme_render_capacity_weight')) { my_theme_render_capacity_weight($product); } ?>
        <?php if (function_exists('my_theme_render_capacity_badges')) { my_theme_render_capacity_badges($product); } ?>
    </div>

    <div class="product-card__actions">
        <?php if (function_exists('my_theme_render_loop_add_to_cart')) { my_theme_render_loop_add_to_cart($product); } else { woocommerce_template_loop_add_to_cart(); } ?>
    </div>
</li>
