<?php
// Danh sách ưu tiên cho trang chủ: mỗi nhóm lấy tối đa 2 sản phẩm.
$group_keywords = [
    ['dulux'],
    ['maxilite'],
    ['nippon'],
    ['bột trét', 'bot tret'],
    ['kova'],
    ['keo chà ron', 'keo cha ron'],
    ['weber', 'webertai', 'webertec'],
];
$per_group = 2;
$products = [];
$selected_ids = [];

$candidate_ids = get_transient('my_theme_home_featured_candidate_ids_v1');
if (!is_array($candidate_ids) || empty($candidate_ids)) {
    // Limit source records to keep home load fast while still covering enough SKUs.
    $candidate_ids = wc_get_products([
        'status'  => 'publish',
        'limit'   => 240,
        'orderby' => 'date',
        'order'   => 'DESC',
        'return'  => 'ids',
    ]);
    if (is_array($candidate_ids) && !empty($candidate_ids)) {
        set_transient('my_theme_home_featured_candidate_ids_v1', $candidate_ids, 4 * HOUR_IN_SECONDS);
    }
}

foreach ($group_keywords as $keywords) {
    $added = 0;
    foreach ($candidate_ids as $pid) {
        if ($added >= $per_group) {
            break;
        }
        if (in_array($pid, $selected_ids, true)) {
            continue;
        }

        $title = get_the_title($pid);
        if (!$title) {
            continue;
        }
        $title_lower = function_exists('mb_strtolower') ? mb_strtolower($title) : strtolower($title);

        $is_match = false;
        foreach ($keywords as $kw) {
            $kw_lower = function_exists('mb_strtolower') ? mb_strtolower($kw) : strtolower($kw);
            if (strpos($title_lower, $kw_lower) !== false) {
                $is_match = true;
                break;
            }
        }

        // Nhóm bột trét: fallback theo danh mục nếu tên chưa chứa từ khóa.
        if (!$is_match && in_array('bot tret', $keywords, true)) {
            $cat_names = wp_get_post_terms($pid, 'product_cat', ['fields' => 'names']);
            if (!is_wp_error($cat_names)) {
                foreach ($cat_names as $cat_name) {
                    $cat_lower = function_exists('mb_strtolower') ? mb_strtolower($cat_name) : strtolower($cat_name);
                    if (strpos($cat_lower, 'bột trét') !== false || strpos($cat_lower, 'bot tret') !== false) {
                        $is_match = true;
                        break;
                    }
                }
            }
        }

        if (!$is_match) {
            continue;
        }

        $product_obj = wc_get_product($pid);
        if (!$product_obj) {
            continue;
        }
        if (function_exists('my_theme_is_shop_visible_product') && !my_theme_is_shop_visible_product($product_obj)) {
            continue;
        }
        if (function_exists('my_theme_is_catalog_ready_product') && !my_theme_is_catalog_ready_product($product_obj, true)) {
            continue;
        }

        $products[] = $product_obj;
        $selected_ids[] = $pid;
        $added++;
    }
}

// Fallback nếu chưa đủ dữ liệu.
if (empty($products)) {
    $query = new WC_Product_Query([
        'limit'   => 8,
        'status'  => 'publish',
        'min_price' => 1,
        'orderby' => 'date',
        'order'   => 'DESC',
    ]);
    $products = array_values(array_filter($query->get_products(), function ($product_obj) {
        return ($product_obj instanceof WC_Product)
            && function_exists('my_theme_is_shop_visible_product')
            && my_theme_is_shop_visible_product($product_obj)
            && function_exists('my_theme_is_catalog_ready_product')
            && my_theme_is_catalog_ready_product($product_obj, true);
    }));
}
?>
<section class="page-section">
  <div class="section-heading">
    <div>
      <h2 class="section-title">Sản phẩm bán chạy</h2>
      <p class="section-sub">Sẵn kho, đủ dung tích, giao nhanh cho thợ & công trình</p>
    </div>
    <a class="btn btn-outline btn-sm" href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>">Xem toàn bộ sản phẩm</a>
  </div>
  <div class="product-grid product-grid--home">
    <?php if (!empty($products)) : foreach ($products as $product) : ?>
      <?php
      $brand_label = function_exists('my_theme_get_product_brand_label') ? my_theme_get_product_brand_label($product) : 'Sản phẩm';
      $cat_label = function_exists('my_theme_get_product_primary_category_label') ? my_theme_get_product_primary_category_label($product) : '';
      $product_name = function_exists('my_theme_get_product_display_name') ? my_theme_get_product_display_name($product) : $product->get_name();
      $excerpt = function_exists('my_theme_get_product_card_excerpt') ? my_theme_get_product_card_excerpt($product, 12) : '';
      ?>
      <article class="product-card">
        <a class="product-card__thumb" href="<?php echo esc_url($product->get_permalink()); ?>">
          <?php echo $product->get_image('woocommerce_thumbnail'); ?>
        </a>
        <div class="product-card__body">
          <?php if ($brand_label !== '' && $brand_label !== 'Sản phẩm') : ?>
            <div class="product-card__brand"><?php echo esc_html($brand_label); ?></div>
          <?php endif; ?>
          <h3 class="product-card__title"><a href="<?php echo esc_url($product->get_permalink()); ?>"><?php echo esc_html($product_name); ?></a></h3>
          <?php if ($cat_label !== '') : ?><div class="product-card__taxonomy"><?php echo esc_html($cat_label); ?></div><?php endif; ?>
          <?php if ($excerpt !== '') : ?><p class="product-card__excerpt"><?php echo esc_html($excerpt); ?></p><?php endif; ?>
          <?php if (function_exists('my_theme_render_loop_price')) { my_theme_render_loop_price($product); } else { ?><div class="product-card__price"><?php echo wp_kses_post($product->get_price_html()); ?></div><?php } ?>
          <?php if (function_exists('my_theme_render_pack_price_list')) { my_theme_render_pack_price_list($product, 'loop'); } ?>
          <?php if (function_exists('my_theme_render_capacity_weight')) { my_theme_render_capacity_weight($product); } ?>
          <?php if (function_exists('my_theme_render_capacity_badges')) { my_theme_render_capacity_badges($product); } ?>
        </div>
        <div class="product-card__actions">
          <a class="btn btn-primary w-100" href="<?php echo esc_url($product->get_permalink()); ?>">Xem chi tiết</a>
        </div>
      </article>
    <?php endforeach; else : ?>
      <p class="text-muted">Chưa có sản phẩm.</p>
    <?php endif; ?>
  </div>
</section>
