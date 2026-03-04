<?php
/** Template Name: Giải pháp sơn epoxy */
get_header();

$landing_business = function_exists('my_theme_get_business_profile') ? my_theme_get_business_profile() : [];
$landing_phone_display = isset($landing_business['phone_display']) ? (string) $landing_business['phone_display'] : '0944 857 999';
$landing_phone_href = isset($landing_business['phone_href']) ? (string) $landing_business['phone_href'] : 'tel:0944857999';
$landing_zalo_url = isset($landing_business['zalo_url']) ? (string) $landing_business['zalo_url'] : 'https://zalo.me/0944857999';
$landing_shop_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
$landing_contact_url = home_url('/lien-he');
$landing_guide_url = home_url('/huong-dan-mua-hang');
$landing_blog_url = trailingslashit(home_url('/blog'));
$landing_calculator_url = trailingslashit(home_url('/')) . '#tinh-son';

$landing_get_products = static function (array $slugs) {
    $products = [];
    foreach ($slugs as $slug) {
        $slug = sanitize_title((string) $slug);
        if ($slug === '') {
            continue;
        }
        $post = get_page_by_path($slug, OBJECT, 'product');
        if (!($post instanceof WP_Post)) {
            continue;
        }
        $product = wc_get_product((int) $post->ID);
        if ($product instanceof WC_Product) {
            $products[] = $product;
        }
    }
    return $products;
};

$landing_capture = static function (callable $callback) {
    ob_start();
    $callback();
    return trim((string) ob_get_clean());
};

$landing_render_product_cards = static function (array $products) use ($landing_capture) {
    foreach ($products as $product) {
        if (!$product instanceof WC_Product) {
            continue;
        }
        $name = function_exists('my_theme_get_product_display_name')
            ? (string) my_theme_get_product_display_name($product)
            : (string) $product->get_name();
        $line = function_exists('my_theme_get_product_line_label')
            ? trim((string) my_theme_get_product_line_label($product))
            : '';
        $category = function_exists('my_theme_get_product_primary_category_label')
            ? trim((string) my_theme_get_product_primary_category_label($product))
            : '';
        $excerpt = function_exists('my_theme_get_product_card_excerpt')
            ? (string) my_theme_get_product_card_excerpt($product, 18)
            : '';
        $price_html = trim((string) $product->get_price_html());
        if ($price_html === '') {
            $price_html = '<span class="product-price-contact-inline">Liên hệ báo giá</span>';
        }

        $pack_summary = function_exists('my_theme_render_loop_pack_summary')
            ? $landing_capture(static function () use ($product) {
                my_theme_render_loop_pack_summary($product, true);
            })
            : '';
        $pack_prices = function_exists('my_theme_render_pack_price_list')
            ? $landing_capture(static function () use ($product) {
                my_theme_render_pack_price_list($product, 'related');
            })
            : '';
        ?>
        <article class="landing-product-card">
          <a class="landing-product-card__thumb" href="<?php echo esc_url($product->get_permalink()); ?>">
            <?php echo $product->get_image('woocommerce_thumbnail', ['alt' => $name, 'loading' => 'lazy']); ?>
          </a>
          <div class="landing-product-card__body">
            <div class="landing-product-card__eyebrow">
              <?php echo esc_html($category !== '' ? $category : 'Sản phẩm gợi ý'); ?>
            </div>
            <h3 class="landing-product-card__title">
              <a href="<?php echo esc_url($product->get_permalink()); ?>"><?php echo esc_html($name); ?></a>
            </h3>
            <?php if ($line !== '') : ?>
              <p class="landing-product-card__line"><?php echo esc_html($line); ?></p>
            <?php endif; ?>
            <?php if ($excerpt !== '') : ?>
              <p class="landing-product-card__excerpt"><?php echo esc_html($excerpt); ?></p>
            <?php endif; ?>
            <?php if ($pack_summary !== '') : ?>
              <div class="landing-product-card__packs"><?php echo $pack_summary; ?></div>
            <?php endif; ?>
            <?php if ($pack_prices !== '') : ?>
              <div class="landing-product-card__pack-prices"><?php echo $pack_prices; ?></div>
            <?php endif; ?>
          </div>
          <div class="landing-product-card__actions">
            <div class="landing-product-card__price"><?php echo wp_kses_post($price_html); ?></div>
            <a class="btn btn-primary w-100" href="<?php echo esc_url($product->get_permalink()); ?>">Xem sản phẩm</a>
          </div>
        </article>
        <?php
    }
};

$landing_featured_products = $landing_get_products([
    'son-epoxy-tu-san-weberfloor-top-2f',
    'son-epoxy-tu-san-weberfloor-top-s',
    'webershield',
    'weberepox-easy',
]);
?>
<main id="main-content">
  <div class="container">
    <article class="page-section single-article page-shell landing-shell">
      <ul class="breadcrumb">
        <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
        <li>Giải pháp sơn epoxy</li>
      </ul>

      <section class="landing-hero">
        <div class="landing-hero__main">
          <p class="eyebrow eyebrow-muted">Giải pháp theo nền sàn</p>
          <h1 class="page-title">Giải pháp sơn epoxy</h1>
          <p class="landing-hero__lead">Trang này giúp khách hàng đi từ hiện trạng nền sàn, mức tải trọng và yêu cầu vệ sinh đến nhóm epoxy phù hợp hơn cho gara, kho nhỏ, xưởng và khu kỹ thuật.</p>
          <div class="search-scope" aria-label="Lối tắt nội dung sơn epoxy">
            <a class="chip" href="#epoxy-theo-khu-vuc">Theo khu vực</a>
            <a class="chip" href="#epoxy-theo-hien-trang">Theo hiện trạng nền</a>
            <a class="chip" href="#goi-y-he-epoxy">Hệ gợi ý</a>
            <a class="chip" href="<?php echo esc_url($landing_blog_url . 'cach-chon-son-epoxy-cho-san-nha-xuong-nho/'); ?>">Bài tư vấn</a>
            <a class="chip" href="<?php echo esc_url($landing_contact_url); ?>">Nhận báo giá</a>
          </div>
          <div class="trust-row" aria-label="Điểm nổi bật epoxy">
            <span class="trust-item">Chốt theo nền bê tông và mức tải</span>
            <span class="trust-item">Ưu tiên vệ sinh, chống bụi, độ bền</span>
            <span class="trust-item">Khoanh đúng primer, basecoat, topcoat</span>
          </div>
          <div class="landing-hero__actions">
            <a class="btn btn-primary" href="<?php echo esc_url($landing_phone_href); ?>">Gọi kỹ thuật sàn</a>
            <a class="btn btn-outline" href="<?php echo esc_url($landing_zalo_url); ?>" target="_blank" rel="noopener">Gửi ảnh nền sàn</a>
            <a class="btn btn-accent" href="<?php echo esc_url($landing_shop_url); ?>">Mở kho sản phẩm</a>
          </div>
        </div>
        <aside class="landing-hero__panel">
          <h3>Thông tin nên gửi trước khi hỏi epoxy</h3>
          <ol class="list-numbered landing-checklist">
            <li>Ảnh nền hiện tại và khu vực mép, khe nứt hoặc vết dầu bẩn.</li>
            <li>Diện tích, mức tải dự kiến và khu vực có xe nâng hoặc xe máy ra vào.</li>
            <li>Nhu cầu chính: chống bụi, dễ vệ sinh, tăng thẩm mỹ hay đánh line.</li>
            <li>Thời gian cần thi công hoặc giao vật tư.</li>
          </ol>
        </aside>
      </section>

      <div class="info-grid">
        <div class="info-card">
          <h3>Gara và kho nhỏ</h3>
          <p>Ưu tiên lớp phủ dễ vệ sinh, ít bám bụi, nhìn gọn mặt sàn và dễ bảo trì theo từng khu vực sử dụng.</p>
        </div>
        <div class="info-card">
          <h3>Xưởng và khu kỹ thuật</h3>
          <p>Cần chú ý tải trọng, độ bám nền, độ dày mong muốn và tình trạng nền cũ trước khi chốt hệ vật tư.</p>
        </div>
        <div class="info-card">
          <h3>Nền cũ nhiều bụi</h3>
          <p>Đây là nhóm rất nên xem lại bước xử lý nền và primer. Chỉ nhìn topcoat mà bỏ nền sẽ dễ hỏng nhanh.</p>
        </div>
      </div>

      <div id="epoxy-theo-khu-vuc" class="content-block">
        <h3>Chọn epoxy theo khu vực sử dụng</h3>
        <p><strong>Gara gia đình và kho nhỏ:</strong> thường ưu tiên hệ giúp nền dễ vệ sinh, đỡ bám bụi, nhìn sáng và gọn hơn mà không cần cấu hình quá nặng.</p>
        <p><strong>Xưởng nhỏ và khu kỹ thuật:</strong> nên nhìn thêm loại tải, tần suất đi lại và yêu cầu bảo trì để chọn hệ phù hợp hơn với tuổi thọ mong muốn.</p>
      </div>

      <div id="epoxy-theo-hien-trang" class="content-block">
        <h3>Chọn theo hiện trạng nền thay vì chỉ chọn màu</h3>
        <p>Nền bê tông mới, nền đã dùng lâu, nền bị bụi bề mặt hoặc có vết nứt hairline sẽ cần cách xử lý khác nhau. Nếu chỉ nhìn màu hoặc giá mà bỏ qua hiện trạng nền, hệ epoxy dễ mất bám dính hoặc xuống cấp nhanh.</p>
        <p>Trước khi chốt đơn, bạn có thể gửi ảnh qua <a href="<?php echo esc_url($landing_contact_url); ?>">trang liên hệ</a> và dùng <a href="<?php echo esc_url($landing_calculator_url); ?>">công cụ tính định mức</a> để có mốc vật tư tham khảo theo m2.</p>
      </div>

      <?php
      if (function_exists('my_theme_render_visual_story_gallery')) {
          my_theme_render_visual_story_gallery(
              'epoxy',
              [
                  'title' => 'Hình minh họa nền sàn epoxy',
                  'subtitle' => 'Ảnh minh họa tham khảo cho gara, kho nhỏ và nền xưởng để khách dễ hình dung mặt sàn sau hoàn thiện.',
                  'class' => 'landing-visual-story',
              ]
          );
      }
      ?>

      <section id="goi-y-he-epoxy" class="landing-section">
        <div class="section-heading landing-section-head">
          <div>
            <h2 class="section-title">4 hệ epoxy nên xem trước</h2>
            <p class="section-sub">Các mã dưới đây đại diện cho nhóm sơn sàn và lớp phủ epoxy thường dùng cho nền dân dụng, kho nhỏ và khu kỹ thuật.</p>
          </div>
          <a class="btn btn-outline btn-sm" href="<?php echo esc_url($landing_shop_url); ?>">Xem toàn bộ kho</a>
        </div>
        <div class="landing-product-grid">
          <?php $landing_render_product_cards($landing_featured_products); ?>
        </div>
      </section>

      <?php
      echo do_shortcode(
          '[lead_capture_form source="landing-son-epoxy" title="Gửi ảnh nền sàn để nhận gợi ý hệ epoxy" subtitle="Điền diện tích, hiện trạng nền và nhu cầu sử dụng để đội kỹ thuật gọi lại tư vấn nhanh." button="Nhận tư vấn sơn epoxy"]'
      );
      ?>

      <?php
      if (function_exists('my_theme_render_solution_pathways')) {
          my_theme_render_solution_pathways('epoxy');
      }
      ?>

      <div class="page-section cta-inline cta-inline--essentials">
        <div class="cta-inline__content">
          <div class="cta-inline__lead">
            <p class="eyebrow eyebrow-muted">Quy trình chốt nhanh</p>
            <h3>Gửi ảnh nền sàn, chốt hệ epoxy và primer, rồi lên phương án vật tư theo m2</h3>
            <p class="text-muted">Khi đã có ảnh nền và nhu cầu sử dụng, đội kỹ thuật có thể khoanh nhanh nhóm epoxy nên đi trước khi báo giá chi tiết.</p>
            <div class="cta-inline__steps" aria-label="Các bước chốt vật tư epoxy">
              <span class="cta-inline__step">1. Gửi ảnh nền và m2</span>
              <span class="cta-inline__step">2. Chốt primer + topcoat</span>
              <span class="cta-inline__step">3. Nhận lịch giao</span>
            </div>
          </div>
          <div class="cta-inline__actions">
            <a class="btn btn-primary" href="<?php echo esc_url($landing_phone_href); ?>">Gọi <?php echo esc_html($landing_phone_display); ?></a>
            <a class="btn btn-outline" href="<?php echo esc_url($landing_guide_url); ?>">Xem hướng dẫn mua hàng</a>
          </div>
        </div>
      </div>

      <div class="cta">
        <div>
          <h3>Cần chốt nhanh hệ epoxy cho gara, kho hoặc nền kỹ thuật?</h3>
          <p>Gửi ảnh nền, diện tích và nhu cầu sử dụng. Đội kỹ thuật sẽ khoanh nhóm vật tư nên xem trước để tránh chốt sai hệ.</p>
        </div>
        <div>
          <a class="btn btn-primary" href="<?php echo esc_url($landing_contact_url); ?>">Gửi yêu cầu báo giá</a>
          <a class="btn btn-outline" href="<?php echo esc_url($landing_blog_url . 'cach-chon-son-epoxy-cho-san-nha-xuong-nho/'); ?>">Xem bài tư vấn</a>
          <a class="btn btn-accent" href="<?php echo esc_url($landing_shop_url); ?>">Xem sản phẩm</a>
        </div>
      </div>
    </article>
  </div>
</main>
<?php get_footer();
