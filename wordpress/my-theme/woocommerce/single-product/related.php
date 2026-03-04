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

$section_title = isset($section_title) ? trim((string) $section_title) : '';
if ($section_title === '') {
    $section_title = (string) apply_filters('woocommerce_product_related_products_heading', 'Sản phẩm liên quan');
}
$section_aria_label = isset($section_aria_label) ? trim((string) $section_aria_label) : $section_title;
$section_class = isset($section_class) ? trim((string) $section_class) : '';

?>

<section class="page-section related products related-products-block<?php echo $section_class !== '' ? ' ' . esc_attr($section_class) : ''; ?>" aria-label="<?php echo esc_attr($section_aria_label); ?>">
  <h2 class="related-title"><?php echo esc_html($section_title); ?></h2>

  <ul class="products product-grid product-grid--related related-products-grid">
    <?php foreach ($visible_related as $related_product) : ?>
      <?php
      $brand_label = function_exists('my_theme_get_product_brand_label') ? my_theme_get_product_brand_label($related_product) : 'Sản phẩm';
      $line_label = function_exists('my_theme_get_product_line_label') ? my_theme_get_product_line_label($related_product) : '';
      $cat_label = function_exists('my_theme_get_product_primary_category_label') ? my_theme_get_product_primary_category_label($related_product) : '';
      $related_id = (int) $related_product->get_id();
      $related_name = function_exists('my_theme_get_product_display_name') ? my_theme_get_product_display_name($related_product) : $related_product->get_name();
      $line_display = (string) $line_label;
      $cat_display = (string) $cat_label;
      if ($line_display !== '' && $cat_display !== '') {
          $line_norm = function_exists('my_theme_normalize_search_text')
              ? my_theme_normalize_search_text($line_display)
              : strtolower($line_display);
          $cat_norm = function_exists('my_theme_normalize_search_text')
              ? my_theme_normalize_search_text($cat_display)
              : strtolower($cat_display);
          if ($line_norm === $cat_norm) {
              $line_display = '';
          }
      }
      $related_excerpt = function_exists('my_theme_get_product_card_excerpt') ? trim((string) my_theme_get_product_card_excerpt($related_product, 14)) : '';
      $thumb_id = function_exists('my_theme_get_preferred_product_image_id')
          ? (int) my_theme_get_preferred_product_image_id($related_product)
          : (int) $related_product->get_image_id();
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
      ?>

      <li <?php wc_product_class('product-card related-product-card related-product-card--' . $related_id, $related_product); ?>>
        <a
          class="<?php echo esc_attr($thumb_class); ?>"
          href="<?php echo esc_url(get_permalink($related_id)); ?>"
          aria-label="<?php echo esc_attr('Xem sản phẩm ' . $related_name); ?>"
          title="<?php echo esc_attr($related_name); ?>">
          <?php if ($related_product->is_on_sale()) : ?><span class="product-card__badge">Giảm giá</span><?php endif; ?>
          <?php
          if ($thumb_id > 0) {
              echo wp_get_attachment_image($thumb_id, 'medium_large', false, [
                  'loading' => 'lazy',
                  'decoding' => 'async',
                  'alt' => $related_name,
                  'title' => $related_name,
              ]);
          } else {
              echo wc_placeholder_img('medium_large');
              echo '<span class="product-card__thumb-note">Ảnh sản phẩm đang cập nhật</span>';
          }
          ?>
        </a>

        <div class="product-card__body">
          <?php if ($brand_label !== '' && $brand_label !== 'Sản phẩm') : ?>
            <div class="product-card__brand"><?php echo esc_html($brand_label); ?></div>
          <?php endif; ?>
          <?php if ($line_display !== '') : ?><div class="product-card__line"><?php echo esc_html($line_display); ?></div><?php endif; ?>
          <h3 class="product-card__title"><a href="<?php echo esc_url(get_permalink($related_id)); ?>"><?php echo esc_html($related_name); ?></a></h3>
          <?php if ($cat_display !== '') : ?><div class="product-card__taxonomy"><?php echo esc_html($cat_display); ?></div><?php endif; ?>
          <?php if ($related_excerpt !== '') : ?><p class="product-card__excerpt"><?php echo esc_html($related_excerpt); ?></p><?php endif; ?>
          <?php if (function_exists('my_theme_render_loop_price')) { my_theme_render_loop_price($related_product); } else { ?><div class="product-card__price"><?php echo wp_kses_post($related_product->get_price_html()); ?></div><?php } ?>
          <?php if (function_exists('my_theme_render_product_colour_swatches')) { my_theme_render_product_colour_swatches($related_product, ['context' => 'related', 'limit' => 5]); } ?>
          <?php if (function_exists('my_theme_render_pack_price_list')) { my_theme_render_pack_price_list($related_product, 'related'); } ?>
          <?php if (function_exists('my_theme_render_loop_pack_summary')) { my_theme_render_loop_pack_summary($related_product, true); } ?>
        </div>

        <div class="product-card__actions product-card__actions--simple">
          <a class="btn btn-outline w-100" href="<?php echo esc_url(get_permalink($related_id)); ?>">Xem chi tiết</a>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
</section>
