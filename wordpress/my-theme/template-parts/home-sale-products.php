<?php
$shop_page_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
$shop_sale_url = add_query_arg('on_sale', '1', $shop_page_url);
$visible_ids = function_exists('my_theme_get_catalog_visible_product_ids')
    ? my_theme_get_catalog_visible_product_ids(false)
    : [];
$visible_ids = array_values(array_filter(array_map('intval', (array) $visible_ids), function ($id) {
    return $id > 0;
}));

$cache_version = (string) get_option('my_theme_filter_cache_version', '1');
$sale_digest = !empty($visible_ids) ? md5(implode(',', $visible_ids)) : 'all';
$sale_cache_key = 'my_theme_home_sale_entries_v4_' . $cache_version . '_' . $sale_digest;
$sale_entries = get_transient($sale_cache_key);

if (!is_array($sale_entries)) {
    $sale_ids = function_exists('my_theme_get_effective_sale_product_ids')
        ? my_theme_get_effective_sale_product_ids($visible_ids)
        : (function_exists('wc_get_product_ids_on_sale')
            ? array_values(array_filter(array_map('intval', (array) wc_get_product_ids_on_sale()), function ($id) {
                return $id > 0;
            }))
            : []);

    $sale_entries = [];
    if (!empty($sale_ids)) {
        $sale_product_map = function_exists('my_theme_get_product_object_map')
            ? my_theme_get_product_object_map($sale_ids)
            : [];

        foreach ($sale_ids as $product_id) {
            if (!isset($sale_product_map[$product_id]) || !$sale_product_map[$product_id] instanceof WC_Product) {
                continue;
            }

            $sale_product = $sale_product_map[$product_id];
            $has_active_sale = function_exists('my_theme_product_has_active_sale')
                ? my_theme_product_has_active_sale($sale_product)
                : $sale_product->is_on_sale();
            if (!$sale_product->is_visible() || !$has_active_sale) {
                continue;
            }

            $regular_price = function_exists('my_theme_get_default_loop_regular_price')
                ? (float) my_theme_get_default_loop_regular_price($sale_product)
                : (float) $sale_product->get_regular_price();
            $sale_price = function_exists('my_theme_get_default_loop_price')
                ? (float) my_theme_get_default_loop_price($sale_product)
                : (float) $sale_product->get_price();
            $discount_percent = 0;

            if ($regular_price > 0 && $sale_price > 0 && $sale_price < $regular_price) {
                $discount_percent = (int) round((($regular_price - $sale_price) / $regular_price) * 100);
            }

            $sales_total = method_exists($sale_product, 'get_total_sales')
                ? (int) $sale_product->get_total_sales()
                : (int) get_post_meta((int) $sale_product->get_id(), 'total_sales', true);
            $created_at = method_exists($sale_product, 'get_date_created')
                ? $sale_product->get_date_created()
                : null;
            $date_ts = $created_at instanceof WC_DateTime
                ? (int) $created_at->getTimestamp()
                : strtotime((string) get_post_field('post_date_gmt', (int) $sale_product->get_id()));

            $sale_entries[] = [
                'product_id' => (int) $sale_product->get_id(),
                'discount_percent' => max(0, $discount_percent),
                'sales_total' => max(0, $sales_total),
                'date_ts' => max(0, (int) $date_ts),
            ];
        }
    }

    usort($sale_entries, function ($a, $b) {
        $discount_a = isset($a['discount_percent']) ? (int) $a['discount_percent'] : 0;
        $discount_b = isset($b['discount_percent']) ? (int) $b['discount_percent'] : 0;
        if ($discount_a !== $discount_b) {
            return ($discount_a > $discount_b) ? -1 : 1;
        }

        $sales_a = isset($a['sales_total']) ? (int) $a['sales_total'] : 0;
        $sales_b = isset($b['sales_total']) ? (int) $b['sales_total'] : 0;
        if ($sales_a !== $sales_b) {
            return ($sales_a > $sales_b) ? -1 : 1;
        }

        $date_a = isset($a['date_ts']) ? (int) $a['date_ts'] : 0;
        $date_b = isset($b['date_ts']) ? (int) $b['date_ts'] : 0;
        return ($date_a > $date_b) ? -1 : 1;
    });

    $sale_entries = array_slice($sale_entries, 0, 12);
    set_transient($sale_cache_key, $sale_entries, 30 * MINUTE_IN_SECONDS);
}

if (empty($sale_entries)) {
    return;
}

$sale_product_ids = array_values(array_filter(array_map(function ($entry) {
    return isset($entry['product_id']) ? (int) $entry['product_id'] : 0;
}, $sale_entries)));
$sale_product_map = function_exists('my_theme_get_product_object_map')
    ? my_theme_get_product_object_map($sale_product_ids)
    : [];
$sale_products = [];

foreach ($sale_entries as $sale_entry) {
    $product_id = isset($sale_entry['product_id']) ? (int) $sale_entry['product_id'] : 0;
    if ($product_id <= 0 || !isset($sale_product_map[$product_id]) || !$sale_product_map[$product_id] instanceof WC_Product) {
        continue;
    }

    $sale_products[] = [
        'product' => $sale_product_map[$product_id],
        'discount_percent' => isset($sale_entry['discount_percent']) ? (int) $sale_entry['discount_percent'] : 0,
    ];
}

if (empty($sale_products)) {
    return;
}
?>
<section class="page-section home-sale-products" aria-label="Sản phẩm khuyến mãi">
  <div class="section-heading section-heading--structured">
    <div class="section-heading__main">
      <h2 class="section-title">Sản phẩm khuyến mãi</h2>
      <p class="section-sub">Các mã đang có giá giảm trong hệ thống WooCommerce. Ưu tiên hiển thị mức giảm mạnh hơn để khách vào là thấy ngay hàng đang khuyến mãi.</p>
    </div>
    <div class="section-heading__meta" aria-label="Mở toàn bộ sản phẩm giảm giá">
      <a class="btn btn-outline btn-sm" href="<?php echo esc_url($shop_sale_url); ?>">Xem tất cả sản phẩm khuyến mãi</a>
    </div>
  </div>

  <div class="product-grid product-grid--home home-sale-products__grid">
    <?php foreach ($sale_products as $sale_entry) : ?>
      <?php
      $product = $sale_entry['product'];
      $discount_percent = isset($sale_entry['discount_percent']) ? (int) $sale_entry['discount_percent'] : 0;
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
      <article class="product-card product-card--sale">
        <a
          class="<?php echo esc_attr($thumb_class); ?>"
          href="<?php echo esc_url($product->get_permalink()); ?>"
          aria-label="<?php echo esc_attr('Xem sản phẩm ' . $product_name); ?>"
          title="<?php echo esc_attr($product_name); ?>">
          <span class="product-card__badge">Giảm giá</span>
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
          <?php if ($discount_percent > 0) : ?>
            <div class="product-card__sale-meta">Tiết kiệm <?php echo esc_html((string) $discount_percent); ?>%</div>
          <?php endif; ?>
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
