<?php
/**
 * Custom loop product card
 */

defined('ABSPATH') || exit;

global $product;

if (empty($product) || !$product->is_visible()) {
    return;
}
?>
<li <?php wc_product_class('product-card', $product); ?>>
    <a class="product-card__thumb" href="<?php the_permalink(); ?>">
        <?php
        woocommerce_show_product_loop_sale_flash();
        woocommerce_template_loop_product_thumbnail();
        ?>
    </a>
    <div class="product-card__body">
        <h2 class="woocommerce-loop-product__title"><?php the_title(); ?></h2>
        <?php woocommerce_template_loop_price(); ?>
        <?php do_action('woocommerce_after_shop_loop_item_title'); ?>
        <?php if (function_exists('my_theme_render_capacity_badges')) { my_theme_render_capacity_badges($product); } ?>
    </div>
    <div class="product-card__actions">
        <?php woocommerce_template_loop_add_to_cart(); ?>
    </div>
</li>
