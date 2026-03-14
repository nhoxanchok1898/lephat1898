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
      $catalog_profile = function_exists('my_theme_get_product_catalog_profile')
          ? my_theme_get_product_catalog_profile($related_product)
          : [];
      $brand_label = isset($catalog_profile['brand_label']) ? (string) $catalog_profile['brand_label'] : 'Sản phẩm';
      $line_label = isset($catalog_profile['line_label']) ? (string) $catalog_profile['line_label'] : '';
      $cat_label = isset($catalog_profile['category_label']) ? (string) $catalog_profile['category_label'] : '';
      $related_id = (int) $related_product->get_id();
      $related_name = isset($catalog_profile['display_name']) && (string) $catalog_profile['display_name'] !== ''
          ? (string) $catalog_profile['display_name']
          : $related_product->get_name();
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
      $media_state = function_exists('my_theme_get_product_card_media_state')
          ? my_theme_get_product_card_media_state($related_product)
          : [];
      $thumb_id = isset($media_state['thumb_id']) ? (int) $media_state['thumb_id'] : 0;
      $thumb_class = isset($media_state['thumb_class']) ? (string) $media_state['thumb_class'] : 'product-card__thumb';
      $has_placeholder_thumb = !empty($media_state['has_placeholder']);
      ?>

      <li <?php wc_product_class('product-card related-product-card related-product-card--' . $related_id, $related_product); ?>>
        <a
          class="<?php echo esc_attr($thumb_class); ?>"
          href="<?php echo esc_url(get_permalink($related_id)); ?>"
          aria-label="<?php echo esc_attr('Xem sản phẩm ' . $related_name); ?>"
          title="<?php echo esc_attr($related_name); ?>">
          <?php if ($related_product->is_on_sale()) : ?><span class="product-card__badge">Giảm giá</span><?php endif; ?>
          <?php
          if ($thumb_id > 0 && !$has_placeholder_thumb) {
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
          <?php if (function_exists('my_theme_render_loop_price')) { my_theme_render_loop_price($related_product); } else { ?><div class="product-card__price"><span class="product-card__price-contact">Liên hệ báo giá</span></div><?php } ?>
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
