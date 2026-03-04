<?php
$hero_shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop');
$hero_business = function_exists('my_theme_get_business_profile') ? my_theme_get_business_profile() : [];
$hero_phone_display = isset($hero_business['phone_display']) ? (string) $hero_business['phone_display'] : '0944 857 999';
$hero_phone_href = isset($hero_business['phone_href']) ? (string) $hero_business['phone_href'] : 'tel:0944857999';
$hero_zalo_url = isset($hero_business['zalo_url']) ? (string) $hero_business['zalo_url'] : 'https://zalo.me/0944857999';
$hero_solutions_url = home_url('/giai-phap');
$hero_contact_url = home_url('/lien-he');
$hero_visible_ids = function_exists('my_theme_get_catalog_visible_product_ids')
  ? my_theme_get_catalog_visible_product_ids(false)
  : [];
$hero_visible_ids = array_values(array_filter(array_map('intval', (array) $hero_visible_ids), function ($id) {
    return $id > 0;
}));
$hero_brand_options = function_exists('my_theme_get_brand_filter_options')
  ? my_theme_get_brand_filter_options($hero_visible_ids)
  : [];
$hero_brand_preview = array_slice($hero_brand_options, 0, 5, true);
$hero_product_count = is_array($hero_visible_ids) ? count($hero_visible_ids) : 0;
$hero_brand_count = is_array($hero_brand_options) ? count($hero_brand_options) : 0;
$hero_cat_count = function_exists('my_theme_count_visible_product_categories')
    ? (int) my_theme_count_visible_product_categories($hero_visible_ids)
    : 0;

$hero_core_brands = ['dulux', 'maxilite', 'weber', 'jotun', 'nippon', 'kova', 'toa', 'sika', 'apollo'];
$hero_cache_version = (string) get_option('my_theme_filter_cache_version', '1');
$hero_digest = md5(implode(',', $hero_visible_ids));
$hero_index_cache_key = 'my_theme_home_hero_index_' . $hero_cache_version . '_' . $hero_digest;
$hero_index_data = get_transient($hero_index_cache_key);

if (!is_array($hero_index_data)) {
    $hero_index_data = [];
    if (!empty($hero_visible_ids) && function_exists('my_theme_filter_product_ids_by_brand_slug')) {
        foreach ($hero_core_brands as $hero_brand_slug) {
            $hero_brand_slug = sanitize_title((string) $hero_brand_slug);
            if ($hero_brand_slug === '') {
                continue;
            }

            $hero_brand_ids = my_theme_filter_product_ids_by_brand_slug($hero_visible_ids, $hero_brand_slug);
            $hero_brand_ids = array_values(array_filter(array_map('intval', (array) $hero_brand_ids), function ($id) {
                return $id > 0;
            }));
            if (empty($hero_brand_ids)) {
                continue;
            }

            $hero_selected_id = 0;
            foreach ($hero_brand_ids as $hero_pid) {
                $hero_pid = (int) $hero_pid;
                if ($hero_pid > 0) {
                    $hero_selected_id = $hero_pid;
                    break;
                }
            }
            if ($hero_selected_id <= 0) {
                continue;
            }

            $hero_index_data[] = [
                'brand_slug' => $hero_brand_slug,
                'product_id' => $hero_selected_id,
            ];
        }
    }
    set_transient($hero_index_cache_key, $hero_index_data, 30 * MINUTE_IN_SECONDS);
}

$hero_render_ids = [];
if (!empty($hero_index_data)) {
    foreach ($hero_index_data as $hero_index_item) {
        if (!is_array($hero_index_item)) {
            continue;
        }
        $hero_pid = isset($hero_index_item['product_id']) ? (int) $hero_index_item['product_id'] : 0;
        if ($hero_pid > 0) {
            $hero_render_ids[$hero_pid] = $hero_pid;
        }
    }
}
$hero_product_map = function_exists('my_theme_get_product_object_map')
    ? my_theme_get_product_object_map(array_values($hero_render_ids))
    : [];

$hero_ad_items = [];
if (!empty($hero_index_data)) {
    foreach ($hero_index_data as $hero_index_item) {
        if (!is_array($hero_index_item)) {
            continue;
        }
        $hero_brand_slug = isset($hero_index_item['brand_slug']) ? sanitize_title((string) $hero_index_item['brand_slug']) : '';
        if ($hero_brand_slug === '') {
            continue;
        }

        $hero_selected_id = isset($hero_index_item['product_id']) ? (int) $hero_index_item['product_id'] : 0;
        $hero_selected_product = ($hero_selected_id > 0 && isset($hero_product_map[$hero_selected_id]))
            ? $hero_product_map[$hero_selected_id]
            : null;

        if (!$hero_selected_product instanceof WC_Product) {
            continue;
        }

        $hero_brand_label = function_exists('my_theme_get_brand_label_from_slug')
            ? (string) my_theme_get_brand_label_from_slug($hero_brand_slug)
            : ucfirst($hero_brand_slug);
        $hero_line_label = function_exists('my_theme_get_product_line_label')
            ? (string) my_theme_get_product_line_label($hero_selected_product)
            : '';
        $hero_cat_label = function_exists('my_theme_get_product_primary_category_label')
            ? (string) my_theme_get_product_primary_category_label($hero_selected_product)
            : '';
        $hero_product_name = function_exists('my_theme_get_product_display_name')
            ? (string) my_theme_get_product_display_name($hero_selected_product)
            : (string) $hero_selected_product->get_name();

        $hero_price_raw = (float) $hero_selected_product->get_price();
        $hero_price_html = $hero_price_raw > 0
            ? wc_price($hero_price_raw)
            : 'Liên hệ báo giá';

        $hero_ad_items[] = [
            'brand_slug' => $hero_brand_slug,
            'brand_label' => $hero_brand_label !== '' ? $hero_brand_label : ucfirst($hero_brand_slug),
            'name' => $hero_product_name,
            'line' => $hero_line_label,
            'category' => $hero_cat_label,
            'url' => $hero_selected_product->get_permalink(),
            'image' => $hero_selected_product->get_image('woocommerce_thumbnail', [
                'alt' => $hero_product_name,
                'title' => $hero_product_name,
            ]),
            'price_html' => $hero_price_html,
        ];
    }
}
?>
<section class="hero hero--refined" id="hero">
  <div class="hero__content">
    <p class="eyebrow">ĐẠI LÝ SƠN PHÁT TẤN</p>
    <h1>Hệ thống bảng giá sơn chính hãng cho thợ và công trình</h1>
    <p>Kho sản phẩm được phân lớp theo thương hiệu, dòng và hạng mục thi công để tìm mã nhanh, báo giá chuẩn ngay lần đầu.</p>

    <div class="hero-kpi-grid">
      <div class="hero-kpi">
        <strong><?php echo esc_html((string) max(0, $hero_product_count)); ?></strong>
        <span>Sản phẩm đang bán</span>
      </div>
      <div class="hero-kpi">
        <strong><?php echo esc_html((string) max(0, $hero_brand_count)); ?></strong>
        <span>Nhóm thương hiệu</span>
      </div>
      <div class="hero-kpi">
        <strong><?php echo esc_html((string) max(0, $hero_cat_count)); ?></strong>
        <span>Danh mục thi công</span>
      </div>
    </div>

    <?php if (!empty($hero_brand_preview)) : ?>
      <div class="brand-strip hero-brand-strip">
        <?php foreach ($hero_brand_preview as $brand_slug => $brand_meta) : ?>
          <?php
          $brand_label = isset($brand_meta['label']) ? (string) $brand_meta['label'] : '';
          if ($brand_slug === '' || $brand_label === '') {
              continue;
          }
          $brand_url = add_query_arg('brand', sanitize_title($brand_slug), $hero_shop_url);
          ?>
          <a class="brand-chip" href="<?php echo esc_url($brand_url); ?>"><?php echo esc_html($brand_label); ?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="hero-pathways" aria-label="Ba cách đi nhanh trên web">
      <a class="hero-pathway" href="<?php echo esc_url($hero_shop_url); ?>">
        <strong>Mở kho theo mã</strong>
        <span>Đi thẳng vào toàn bộ sản phẩm nếu bạn đã biết hãng hoặc dòng cần xem.</span>
      </a>
      <a class="hero-pathway" href="<?php echo esc_url($hero_solutions_url); ?>">
        <strong>Chọn theo hạng mục</strong>
        <span>Đi theo nội thất, ngoại thất, chống thấm, epoxy, kim loại hoặc keo ron.</span>
      </a>
      <a class="hero-pathway" href="<?php echo esc_url($hero_contact_url); ?>">
        <strong>Gửi nhu cầu thực tế</strong>
        <span>Nếu chưa chắc mã, chỉ cần gửi ảnh bề mặt hoặc mô tả công trình để được điều hướng.</span>
      </a>
    </div>

    <div class="hero-actions">
      <a href="<?php echo esc_url($hero_shop_url); ?>" class="btn btn-accent btn-lg">Mở kho theo danh mục</a>
      <a href="<?php echo esc_url($hero_contact_url); ?>" class="btn btn-outline btn-lg">Nhận tư vấn kỹ thuật</a>
    </div>
    <a class="hero-call" href="<?php echo esc_url($hero_phone_href); ?>">Hotline báo giá nhanh: <?php echo esc_html($hero_phone_display); ?></a>
  </div>

  <?php if (!empty($hero_ad_items)) : ?>
    <aside class="hero-panel hero-panel--rotator" data-hero-rotator data-interval="5000">
      <div class="hero-rotator__intro">
        <h3>Giới thiệu nhanh theo hãng</h3>
        <p>Mỗi hãng 1 sản phẩm tiêu biểu. Tự động chạy sau mỗi 5 giây.</p>
      </div>

      <div class="hero-rotator__viewport">
        <?php foreach ($hero_ad_items as $hero_index => $hero_item) : ?>
          <?php
          $hero_slide_id = 'hero-spotlight-' . sanitize_title((string) $hero_item['brand_slug']) . '-' . (int) $hero_index;
          $hero_is_active = ($hero_index === 0);
          ?>
          <article
            id="<?php echo esc_attr($hero_slide_id); ?>"
            class="hero-rotator__slide <?php echo $hero_is_active ? 'is-active' : ''; ?>"
            data-hero-slide="<?php echo esc_attr((string) $hero_index); ?>"
            aria-hidden="<?php echo $hero_is_active ? 'false' : 'true'; ?>">
            <a
              class="hero-rotator__thumb"
              href="<?php echo esc_url((string) $hero_item['url']); ?>"
              aria-label="<?php echo esc_attr('Xem sản phẩm ' . (string) $hero_item['name']); ?>"
              title="<?php echo esc_attr((string) $hero_item['name']); ?>">
              <?php echo wp_kses_post((string) $hero_item['image']); ?>
            </a>
            <div class="hero-rotator__body">
              <div class="hero-rotator__meta">
                <span class="hero-rotator__brand"><?php echo esc_html((string) $hero_item['brand_label']); ?></span>
                <?php if (!empty($hero_item['line'])) : ?>
                  <span class="hero-rotator__line"><?php echo esc_html((string) $hero_item['line']); ?></span>
                <?php endif; ?>
              </div>
              <h4><a href="<?php echo esc_url((string) $hero_item['url']); ?>"><?php echo esc_html((string) $hero_item['name']); ?></a></h4>
              <?php if (!empty($hero_item['category'])) : ?>
                <div class="hero-rotator__cat"><?php echo esc_html((string) $hero_item['category']); ?></div>
              <?php endif; ?>
              <div class="hero-rotator__price"><?php echo wp_kses_post((string) $hero_item['price_html']); ?></div>
              <a class="btn btn-accent btn-sm" href="<?php echo esc_url((string) $hero_item['url']); ?>">Xem sản phẩm</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <div class="hero-rotator__folders" role="tablist" aria-label="Thư mục thương hiệu">
        <?php foreach ($hero_ad_items as $hero_index => $hero_item) : ?>
          <?php
          $hero_slide_id = 'hero-spotlight-' . sanitize_title((string) $hero_item['brand_slug']) . '-' . (int) $hero_index;
          $hero_is_active = ($hero_index === 0);
          ?>
          <button
            type="button"
            class="hero-rotator__folder <?php echo $hero_is_active ? 'is-active' : ''; ?>"
            role="tab"
            aria-selected="<?php echo $hero_is_active ? 'true' : 'false'; ?>"
            aria-controls="<?php echo esc_attr($hero_slide_id); ?>"
            data-hero-folder="<?php echo esc_attr((string) $hero_index); ?>">
            <span><?php echo esc_html((string) $hero_item['brand_label']); ?></span>
          </button>
        <?php endforeach; ?>
      </div>

      <div class="hero-actions hero-actions--compact">
        <a class="btn btn-primary btn-sm" href="<?php echo esc_url($hero_zalo_url); ?>" target="_blank" rel="noopener">Zalo kỹ thuật</a>
        <a class="btn btn-outline btn-sm" href="<?php echo esc_url(home_url('/huong-dan-mua-hang')); ?>">Quy trình đặt hàng</a>
      </div>
    </aside>
  <?php else : ?>
    <aside class="hero-panel">
      <h3>Vì sao thợ chọn Phát Tấn?</h3>
      <ul class="list-plain">
        <li>Không giao hàng tồn cũ, có hóa đơn và chứng từ rõ ràng</li>
        <li>Định mức m² theo từng bề mặt, tránh mua dư</li>
        <li>Phân loại theo thương hiệu, dòng sản phẩm và ứng dụng thực tế</li>
        <li>Hỗ trợ kỹ thuật trực tiếp qua điện thoại và Zalo</li>
      </ul>
      <div class="hero-actions hero-actions--compact">
        <a class="btn btn-primary btn-sm" href="<?php echo esc_url($hero_zalo_url); ?>" target="_blank" rel="noopener">Zalo kỹ thuật</a>
        <a class="btn btn-outline btn-sm" href="<?php echo esc_url(home_url('/huong-dan-mua-hang')); ?>">Quy trình đặt hàng</a>
      </div>
    </aside>
  <?php endif; ?>
</section>
