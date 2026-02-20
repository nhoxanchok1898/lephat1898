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

$candidate_ids = wc_get_products([
    'status'  => 'publish',
    'limit'   => -1,
    'orderby' => 'date',
    'order'   => 'DESC',
    'return'  => 'ids',
]);

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
        'orderby' => 'date',
        'order'   => 'DESC',
    ]);
    $products = $query->get_products();
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
  <div class="product-grid">
    <?php if (!empty($products)) : foreach ($products as $product) : ?>
      <article class="product-card">
        <a class="product-card__thumb" href="<?php echo esc_url($product->get_permalink()); ?>">
          <?php echo $product->get_image('woocommerce_thumbnail'); ?>
        </a>
        <div class="product-card__body">
          <div class="product-card__brand">Sản phẩm</div>
          <h3 class="product-card__title"><a href="<?php echo esc_url($product->get_permalink()); ?>"><?php echo esc_html($product->get_name()); ?></a></h3>
          <div class="product-card__price"><?php echo wp_kses_post($product->get_price_html()); ?></div>
          <?php if (function_exists('my_theme_render_capacity_weight')) { my_theme_render_capacity_weight($product); } ?>
          <?php if (function_exists('my_theme_render_capacity_badges')) { my_theme_render_capacity_badges($product); } ?>
        </div>
        <div class="product-card__actions">
          <?php woocommerce_template_loop_add_to_cart(['product' => $product]); ?>
          <a class="btn btn-outline w-100" href="<?php echo esc_url($product->get_permalink()); ?>">Xem chi tiết</a>
        </div>
      </article>
    <?php endforeach; else : ?>
      <p class="text-muted">Chưa có sản phẩm.</p>
    <?php endif; ?>
  </div>
</section>
