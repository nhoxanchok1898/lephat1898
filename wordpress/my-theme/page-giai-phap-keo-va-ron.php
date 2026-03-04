<?php
/** Template Name: Giải pháp keo và ron gạch */
get_header();

$landing_business = function_exists('my_theme_get_business_profile') ? my_theme_get_business_profile() : [];
$landing_phone_display = isset($landing_business['phone_display']) ? (string) $landing_business['phone_display'] : '0944 857 999';
$landing_phone_href = isset($landing_business['phone_href']) ? (string) $landing_business['phone_href'] : 'tel:0944857999';
$landing_zalo_url = isset($landing_business['zalo_url']) ? (string) $landing_business['zalo_url'] : 'https://zalo.me/0944857999';
$landing_shop_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
$landing_contact_url = home_url('/lien-he');
$landing_guide_url = home_url('/huong-dan-mua-hang');
$landing_blog_url = trailingslashit(home_url('/blog'));

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
        ?>
        <article class="landing-product-card">
          <a class="landing-product-card__thumb" href="<?php echo esc_url($product->get_permalink()); ?>">
            <?php echo $product->get_image('woocommerce_thumbnail', ['alt' => $name, 'loading' => 'lazy']); ?>
          </a>
          <div class="landing-product-card__body">
            <div class="landing-product-card__eyebrow">Keo và ron gạch</div>
            <h3 class="landing-product-card__title">
              <a href="<?php echo esc_url($product->get_permalink()); ?>"><?php echo esc_html($name); ?></a>
            </h3>
            <?php if ($excerpt !== '') : ?>
              <p class="landing-product-card__excerpt"><?php echo esc_html($excerpt); ?></p>
            <?php endif; ?>
            <?php if ($pack_summary !== '') : ?>
              <div class="landing-product-card__packs"><?php echo $pack_summary; ?></div>
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
    'keo-cha-ron-webercolor-classic',
    'webercolor-no-stain',
    'keo-dan-gach-webertai-fix-40kg',
    'weberseal-ws500',
]);
?>
<main id="main-content">
  <div class="container">
    <article class="page-section single-article page-shell landing-shell">
      <ul class="breadcrumb">
        <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
        <li>Giải pháp keo và ron gạch</li>
      </ul>
      <section class="landing-hero">
        <div class="landing-hero__main">
          <p class="eyebrow eyebrow-muted">Giải pháp cho gạch ốp lát</p>
          <h1 class="page-title">Giải pháp keo và ron gạch</h1>
          <p class="landing-hero__lead">Trang này gom nhanh các tình huống dán gạch, chà ron và trám khe cho nhà tắm, bếp, khu ẩm và gạch ốp lát để khách chọn đúng nhóm vật tư hơn.</p>
          <div class="search-scope" aria-label="Lối tắt nội dung keo và ron gạch">
            <a class="chip" href="#keo-ron-theo-khu-vuc">Theo khu vực</a>
            <a class="chip" href="#keo-ron-theo-vat-tu">Theo loại vật tư</a>
            <a class="chip" href="#goi-y-keo-ron">Sản phẩm gợi ý</a>
            <a class="chip" href="<?php echo esc_url($landing_blog_url . 'cach-chon-keo-cha-ron-cho-nha-tam-va-bep/'); ?>">Bài tư vấn</a>
            <a class="chip" href="<?php echo esc_url($landing_contact_url); ?>">Gửi nhu cầu</a>
          </div>
        </div>
        <aside class="landing-hero__panel">
          <h3>Thông tin nên gửi trước khi hỏi keo và ron</h3>
          <ol class="list-numbered landing-checklist">
            <li>Loại gạch, kích thước gạch và khu vực thi công.</li>
            <li>Đang cần dán gạch, chà ron hay xử lý khe tiếp giáp.</li>
            <li>Khu vực có ẩm thường xuyên, ngoài trời hay trong nhà.</li>
            <li>Diện tích hoặc số lượng bao cần lấy.</li>
          </ol>
        </aside>
      </section>

      <div class="content-block" id="keo-ron-theo-khu-vuc">
        <h3>Chọn theo khu vực thi công</h3>
        <p><strong>Nhà tắm và khu vệ sinh:</strong> thường ưu tiên nhóm chà ron sạch hơn, ít bám bẩn và phù hợp khu vực ẩm kéo dài.</p>
        <p><strong>Bếp và ban công:</strong> cần xem thêm yếu tố dầu mỡ, bụi bẩn và mức co giãn ở các khe tiếp giáp để chốt vật tư hợp lý hơn.</p>
      </div>

      <div class="content-block" id="keo-ron-theo-vat-tu">
        <h3>Chọn đúng nhóm vật tư thay vì gọi chung là “keo ron”</h3>
        <p>Keo dán gạch, bột chà ron và vật tư trám khe là 3 nhóm khác nhau. Việc gọi chung dễ dẫn tới lấy sai loại hoặc thiếu vật tư cần thiết cho công trình.</p>
      </div>

      <?php
      if (function_exists('my_theme_render_visual_story_gallery')) {
          my_theme_render_visual_story_gallery(
              'grout',
              [
                  'title' => 'Hình minh họa nhà tắm, bếp và ron gạch',
                  'subtitle' => 'Ảnh minh họa tham khảo cho các khu vực gạch ốp lát, nhà tắm và bếp để khách dễ hình dung nhóm vật tư đang cần.',
                  'class' => 'landing-visual-story',
              ]
          );
      }
      ?>

      <section id="goi-y-keo-ron" class="landing-section">
        <div class="section-heading landing-section-head">
          <div>
            <h2 class="section-title">4 mã keo và ron nên xem trước</h2>
            <p class="section-sub">Các mã dưới đây đại diện cho nhóm dán gạch, chà ron và trám khe phổ biến cho nhà tắm, bếp và khu ẩm.</p>
          </div>
          <a class="btn btn-outline btn-sm" href="<?php echo esc_url($landing_shop_url); ?>">Xem toàn bộ kho</a>
        </div>
        <div class="landing-product-grid">
          <?php $landing_render_product_cards($landing_featured_products); ?>
        </div>
      </section>

      <div class="info-grid landing-faq-grid">
        <div class="info-card">
          <h3>Keo dán gạch và chà ron có thay thế nhau được không?</h3>
          <p>Không. Keo dán gạch dùng để bám dính viên gạch, còn chà ron xử lý khe ron sau khi ốp lát hoàn tất.</p>
        </div>
        <div class="info-card">
          <h3>Khu ẩm nên ưu tiên loại ron nào?</h3>
          <p>Nhà tắm, khu vệ sinh và bếp nên ưu tiên nhóm ron sạch hơn, ít bám bẩn hơn và phù hợp môi trường ẩm kéo dài.</p>
        </div>
        <div class="info-card">
          <h3>Cần gửi gì để chốt vật tư đúng?</h3>
          <p>Loại gạch, khu vực thi công, kích thước gạch và diện tích là 4 thông tin quan trọng nhất để đội kỹ thuật chốt đúng nhóm vật tư.</p>
        </div>
      </div>

      <?php
      echo do_shortcode(
          '[lead_capture_form source="landing-keo-ron" title="Gửi nhu cầu để nhận gợi ý keo, ron và phụ gia phù hợp" subtitle="Điền loại gạch, khu vực thi công và diện tích để đội kỹ thuật gọi lại nhanh hơn." button="Nhận tư vấn keo và ron"]'
      );
      ?>

      <?php
      if (function_exists('my_theme_render_solution_pathways')) {
          my_theme_render_solution_pathways('grout');
      }
      ?>

      <div class="page-section cta-inline cta-inline--essentials">
        <div class="cta-inline__content">
          <div class="cta-inline__lead">
            <p class="eyebrow eyebrow-muted">Quy trình chốt nhanh</p>
            <h3>Gửi loại gạch và khu vực thi công, chốt nhóm vật tư, rồi lên đơn theo diện tích</h3>
            <p class="text-muted">Chỉ cần loại gạch, khu vực ốp lát và m2 là đủ để đội kỹ thuật khoanh nhanh keo dán, chà ron hay vật tư trám khe nên xem trước.</p>
            <div class="cta-inline__steps" aria-label="Các bước chốt vật tư keo và ron">
              <span class="cta-inline__step">1. Gửi loại gạch và m2</span>
              <span class="cta-inline__step">2. Chốt keo, ron hoặc trám khe</span>
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
          <h3>Cần chốt nhanh keo dán gạch, chà ron hoặc vật tư trám khe?</h3>
          <p>Gửi loại gạch, khu vực thi công và diện tích. Đội kỹ thuật sẽ gợi ý đúng nhóm vật tư để tránh thiếu hoặc lấy nhầm loại.</p>
        </div>
        <div>
          <a class="btn btn-primary" href="<?php echo esc_url($landing_contact_url); ?>">Gửi nhu cầu thi công</a>
          <a class="btn btn-outline" href="<?php echo esc_url($landing_blog_url . 'cach-chon-keo-cha-ron-cho-nha-tam-va-bep/'); ?>">Xem bài tư vấn</a>
          <a class="btn btn-accent" href="<?php echo esc_url($landing_shop_url); ?>">Xem sản phẩm</a>
        </div>
      </div>
    </article>
  </div>
</main>
<?php get_footer();
