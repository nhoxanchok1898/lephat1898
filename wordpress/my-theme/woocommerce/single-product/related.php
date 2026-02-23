<?php
/**
 * Custom related products template.
 */

defined('ABSPATH') || exit;

if (empty($related_products)) {
    return;
}

$visible_related = [];
foreach ($related_products as $related_product) {
    if (!$related_product instanceof WC_Product) {
        continue;
    }
    if (function_exists('my_theme_is_catalog_ready_product') && !my_theme_is_catalog_ready_product($related_product, true)) {
        continue;
    }
    $visible_related[] = $related_product;
}

if (empty($visible_related)) {
    return;
}

?>

<section class="page-section related products related-products-block" aria-label="Sản phẩm liên quan">
  <h2 class="related-title"><?php echo esc_html(apply_filters('woocommerce_product_related_products_heading', 'Sản phẩm liên quan')); ?></h2>

  <ul class="products product-grid product-grid--related related-products-grid">
    <?php foreach ($visible_related as $related_product) : ?>
      <?php
      $brand_label = function_exists('my_theme_get_product_brand_label') ? my_theme_get_product_brand_label($related_product) : 'Sản phẩm';
      $cat_label = function_exists('my_theme_get_product_primary_category_label') ? my_theme_get_product_primary_category_label($related_product) : '';
      $related_id = (int) $related_product->get_id();
      $related_name = function_exists('my_theme_get_product_display_name') ? my_theme_get_product_display_name($related_product) : $related_product->get_name();
      ?>

      <li <?php wc_product_class('product-card related-product-card related-product-card--' . $related_id, $related_product); ?>>
        <a class="product-card__thumb" href="<?php echo esc_url(get_permalink($related_id)); ?>">
          <?php if ($related_product->is_on_sale()) : ?><span class="product-card__badge">Giảm giá</span><?php endif; ?>
          <?php echo $related_product->get_image('woocommerce_thumbnail'); ?>
        </a>

        <div class="product-card__body">
          <?php if ($brand_label !== '' && $brand_label !== 'Sản phẩm') : ?>
            <div class="product-card__brand"><?php echo esc_html($brand_label); ?></div>
          <?php endif; ?>
          <h3 class="product-card__title"><a href="<?php echo esc_url(get_permalink($related_id)); ?>"><?php echo esc_html($related_name); ?></a></h3>
          <?php if ($cat_label !== '') : ?><div class="product-card__taxonomy"><?php echo esc_html($cat_label); ?></div><?php endif; ?>
          <?php if (function_exists('my_theme_render_loop_price')) { my_theme_render_loop_price($related_product); } else { ?><div class="product-card__price"><?php echo wp_kses_post($related_product->get_price_html()); ?></div><?php } ?>
          <?php if (function_exists('my_theme_render_pack_price_list')) { my_theme_render_pack_price_list($related_product, 'related'); } ?>
          <?php if (function_exists('my_theme_render_capacity_weight')) { my_theme_render_capacity_weight($related_product); } ?>
          <?php if (function_exists('my_theme_render_capacity_badges')) { my_theme_render_capacity_badges($related_product); } ?>
        </div>

        <div class="product-card__actions">
          <a class="btn btn-outline w-100" href="<?php echo esc_url(get_permalink($related_id)); ?>">Xem chi tiết</a>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
</section>
