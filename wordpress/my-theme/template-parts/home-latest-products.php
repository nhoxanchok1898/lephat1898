<?php
$shop_page_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
$visible_ids = function_exists('my_theme_get_catalog_visible_product_ids')
    ? my_theme_get_catalog_visible_product_ids(false)
    : [];
$visible_ids = array_values(array_filter(array_map('intval', (array) $visible_ids), function ($id) {
    return $id > 0;
}));

$latest_limit = 12;
$cache_version = (string) get_option('my_theme_filter_cache_version', '1');
$latest_digest = !empty($visible_ids) ? md5(implode(',', $visible_ids)) : 'all';
$latest_cache_key = 'my_theme_home_latest_ids_v2_' . $cache_version . '_' . md5($latest_digest . '|' . (string) $latest_limit);
$latest_product_ids = get_transient($latest_cache_key);

if (!is_array($latest_product_ids)) {
    $latest_query_args = [
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => $latest_limit,
        'orderby' => 'date',
        'order' => 'DESC',
        'fields' => 'ids',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ];

    if (!empty($visible_ids)) {
        $latest_query_args['post__in'] = $visible_ids;
    }

    $latest_product_ids = get_posts($latest_query_args);
    $latest_product_ids = array_values(array_filter(array_map('intval', (array) $latest_product_ids), function ($id) {
        return $id > 0;
    }));
    set_transient($latest_cache_key, $latest_product_ids, 30 * MINUTE_IN_SECONDS);
}

if (empty($latest_product_ids)) {
    return;
}

$latest_product_map = function_exists('my_theme_get_product_object_map')
    ? my_theme_get_product_object_map($latest_product_ids)
    : [];
$latest_products = [];

foreach ($latest_product_ids as $product_id) {
    if (!isset($latest_product_map[$product_id]) || !$latest_product_map[$product_id] instanceof WC_Product) {
        continue;
    }
    $latest_products[] = $latest_product_map[$product_id];
}

if (empty($latest_products)) {
    return;
}
?>
<section class="page-section home-latest-products" aria-label="Sản phẩm mới cập nhật">
  <div class="section-heading section-heading--structured">
    <div class="section-heading__main">
      <h2 class="section-title">Sản phẩm mới cập nhật</h2>
      <p class="section-sub">Thêm các mã vừa được đưa lên hệ thống để khách kéo xuống vẫn tiếp tục thấy sản phẩm, không bị hụt nội dung quá sớm.</p>
    </div>
    <div class="section-heading__meta" aria-label="Mở toàn bộ sản phẩm">
      <a class="btn btn-outline btn-sm" href="<?php echo esc_url($shop_page_url); ?>">Xem toàn bộ kho sản phẩm</a>
    </div>
  </div>

  <div class="product-grid product-grid--home home-latest-products__grid">
    <?php foreach ($latest_products as $product) : ?>
      <?php
      $catalog_profile = function_exists('my_theme_get_product_catalog_profile')
          ? my_theme_get_product_catalog_profile($product)
          : [];
      $brand_label = isset($catalog_profile['brand_label']) ? (string) $catalog_profile['brand_label'] : 'Sản phẩm';
      $product_name = isset($catalog_profile['display_name']) && (string) $catalog_profile['display_name'] !== ''
          ? (string) $catalog_profile['display_name']
          : $product->get_name();
      $brand_display = ($brand_label !== '' && $brand_label !== 'Sản phẩm') ? (string) $brand_label : '';
      $brand_class = 'product-card__brand' . ($brand_display === '' ? ' product-card__brand--empty' : '');
      $media_state = function_exists('my_theme_get_product_card_media_state')
          ? my_theme_get_product_card_media_state($product)
          : [];
      $thumb_id = isset($media_state['thumb_id']) ? (int) $media_state['thumb_id'] : 0;
      $thumb_class = isset($media_state['thumb_class']) ? (string) $media_state['thumb_class'] : 'product-card__thumb';
      $has_placeholder_thumb = !empty($media_state['has_placeholder']);
      ?>
      <article class="product-card">
        <a
          class="<?php echo esc_attr($thumb_class); ?>"
          href="<?php echo esc_url($product->get_permalink()); ?>"
          aria-label="<?php echo esc_attr('Xem sản phẩm ' . $product_name); ?>"
          title="<?php echo esc_attr($product_name); ?>">
          <?php
          if ($has_placeholder_thumb) {
              echo wc_placeholder_img('medium_large');
              echo '<span class="product-card__thumb-note">Ảnh sản phẩm đang cập nhật</span>';
          } elseif ($thumb_id > 0) {
              echo wp_get_attachment_image($thumb_id, 'medium_large', false, [
                  'loading' => 'lazy',
                  'decoding' => 'async',
                  'alt' => $product_name,
                  'title' => $product_name,
              ]);
          } else {
              echo wc_placeholder_img('medium_large');
          }
          ?>
        </a>
        <div class="product-card__body">
          <div class="<?php echo esc_attr($brand_class); ?>"<?php echo ($brand_display === '') ? ' aria-hidden="true"' : ''; ?>><?php echo esc_html($brand_display); ?></div>
          <h3 class="product-card__title"><a href="<?php echo esc_url($product->get_permalink()); ?>"><?php echo esc_html($product_name); ?></a></h3>
          <?php if (function_exists('my_theme_render_loop_price')) { my_theme_render_loop_price($product); } else { ?><div class="product-card__price"><span class="product-card__price-contact">Liên hệ báo giá</span></div><?php } ?>
          <?php if (function_exists('my_theme_render_loop_pack_summary')) { my_theme_render_loop_pack_summary($product, true); } ?>
        </div>
        <div class="product-card__actions product-card__actions--simple">
          <a class="btn btn-primary w-100" href="<?php echo esc_url($product->get_permalink()); ?>">Xem chi tiết</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
