<?php
/** Template Name: Giải pháp sơn ngoại thất */
get_header();

$landing_business = function_exists('my_theme_get_business_profile') ? my_theme_get_business_profile() : [];
$landing_phone_display = isset($landing_business['phone_display']) ? (string) $landing_business['phone_display'] : '0944 857 999';
$landing_phone_href = isset($landing_business['phone_href']) ? (string) $landing_business['phone_href'] : 'tel:0944857999';
$landing_zalo_url = isset($landing_business['zalo_url']) ? (string) $landing_business['zalo_url'] : 'https://zalo.me/0944857999';
$landing_shop_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
$landing_contact_url = home_url('/lien-he');
$landing_blog_url = trailingslashit(home_url('/blog'));
$landing_guide_url = home_url('/huong-dan-mua-hang');

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
            <div class="landing-product-card__eyebrow">Sơn ngoại thất</div>
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
    'duluxweathershieldcolourprotectbematmo',
    'jotun-jotashield-che-phu-vet-nut',
    'nippon-weatherbond-solareflect',
    'sonnuocngoaithatmaxilitetotaltuduluxbematmo',
]);
?>
<main id="main-content">
  <div class="container">
    <article class="page-section single-article page-shell landing-shell">
      <ul class="breadcrumb">
        <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
        <li>Giải pháp sơn ngoại thất</li>
      </ul>

      <section class="landing-hero">
        <div class="landing-hero__main">
          <p class="eyebrow eyebrow-muted">Giải pháp cho mặt tiền và tường ngoài trời</p>
          <h1 class="page-title">Giải pháp sơn ngoại thất</h1>
          <p class="landing-hero__lead">Trang này gom nhanh các tình huống sơn ngoại thất theo mức nắng mưa, hiện trạng tường, khả năng che phủ và độ bền màu để khách chọn đúng hệ vật tư ngay từ đầu.</p>

          <div class="search-scope" aria-label="Lối tắt nội dung sơn ngoại thất">
            <a class="chip" href="#ngoai-that-theo-hien-trang">Theo hiện trạng tường</a>
            <a class="chip" href="#ngoai-that-theo-nhu-cau">Theo nhu cầu sử dụng</a>
            <a class="chip" href="#goi-y-son-ngoai-that">Sản phẩm gợi ý</a>
            <a class="chip" href="<?php echo esc_url($landing_blog_url . 'cach-chon-son-ngoai-that-ben-mau-cho-mat-tien/'); ?>">Bài tư vấn</a>
            <a class="chip" href="<?php echo esc_url($landing_contact_url); ?>">Nhận báo giá</a>
          </div>

          <div class="trust-row" aria-label="Điểm nổi bật sơn ngoại thất">
            <span class="trust-item">Ưu tiên bền màu ngoài trời</span>
            <span class="trust-item">Chốt theo tường mới hoặc tường cũ</span>
            <span class="trust-item">Có hệ lót và lớp phủ đồng bộ</span>
          </div>

          <div class="landing-hero__actions">
            <a class="btn btn-primary" href="<?php echo esc_url($landing_phone_href); ?>">Gọi báo giá</a>
            <a class="btn btn-outline" href="<?php echo esc_url($landing_zalo_url); ?>" target="_blank" rel="noopener">Zalo kỹ thuật</a>
            <a class="btn btn-accent" href="<?php echo esc_url($landing_shop_url); ?>">Mở kho sản phẩm</a>
          </div>
        </div>

        <aside class="landing-hero__panel">
          <h3>Chuẩn bị trước khi hỏi báo giá ngoại thất</h3>
          <ol class="list-numbered landing-checklist">
            <li>Ảnh tổng thể mặt tiền hoặc tường ngoài trời cần thi công.</li>
            <li>Tường mới hay tường cũ đã phấn hóa, bong tróc, rạn nứt.</li>
            <li>Mức ưu tiên chính: bền màu, che phủ, chống bám bẩn hay tối ưu chi phí.</li>
            <li>Diện tích ước tính và thời gian cần giao vật tư.</li>
          </ol>
          <div class="landing-kpis" aria-label="Cam kết hỗ trợ">
            <div class="landing-kpi">
              <strong>15 phút</strong>
              <span>phản hồi báo giá trong giờ làm việc</span>
            </div>
            <div class="landing-kpi">
              <strong>3 lớp</strong>
              <span>tối thiểu nên chốt đủ hệ xử lý nền, lót và phủ khi cần</span>
            </div>
          </div>
        </aside>
      </section>

      <div class="info-grid">
        <div class="info-card">
          <h3>Chọn theo hiện trạng tường</h3>
          <p>Tường mới, tường cũ bị phấn hóa, tường có rạn nhẹ và tường đã thấm nước sẽ cần hệ xử lý khác nhau. Nên chốt hiện trạng trước khi chọn dòng sơn phủ.</p>
        </div>
        <div class="info-card">
          <h3>Đừng chỉ chọn theo màu</h3>
          <p>Mặt tiền ngoài trời chịu nắng mưa mạnh hơn rất nhiều so với trong nhà. Vì vậy độ bền màu, chống bám bẩn và khả năng che phủ quan trọng hơn việc chỉ nhìn mã màu.</p>
        </div>
        <div class="info-card">
          <h3>Lót ngoài trời là bước cần giữ</h3>
          <p>Nếu cắt lớp lót để giảm giá, lớp phủ dễ nhanh xuống màu, loang hoặc bám bẩn hơn. Tối ưu chi phí nên tối ưu ở cấp độ dòng sản phẩm, không nên bỏ hệ lót đúng.</p>
        </div>
      </div>

      <div id="ngoai-that-theo-hien-trang" class="content-block">
        <h3>Chọn sơn ngoại thất theo hiện trạng bề mặt</h3>
        <p><strong>Tường mới:</strong> ưu tiên hệ lót kháng kiềm và lớp phủ bền màu để nền tường ổn định ngay từ đầu. <strong>Tường cũ đã phấn hóa:</strong> cần kiểm tra độ bám nền trước khi chọn sơn mới, không nên chỉ phủ đè.</p>
        <p><strong>Tường có nứt nhỏ hoặc hay đọng nước:</strong> nên báo hiện trạng sớm để khoanh lại có cần xử lý chống thấm hoặc vá nứt trước khi lên lớp sơn hoàn thiện hay không.</p>
      </div>

      <div id="ngoai-that-theo-nhu-cau" class="content-block">
        <h3>Chọn theo nhu cầu sử dụng và ngân sách</h3>
        <p>Nếu mặt tiền hướng nắng gắt hoặc công trình cần bền màu lâu hơn, nên ưu tiên nhóm ngoại thất cao hơn. Nếu cần tối ưu ngân sách cho diện tích lớn, có thể chọn dòng phổ thông nhưng vẫn nên giữ đủ lớp lót và số lớp phủ.</p>
        <p>Trước khi chốt vật tư, nên ước lượng m2 và khu vực chịu nắng mưa mạnh để báo giá không bị thiếu vật tư hoặc hụt lớp phủ khi thi công.</p>
      </div>

      <?php
      if (function_exists('my_theme_render_visual_story_gallery')) {
          my_theme_render_visual_story_gallery(
              'exterior',
              [
                  'title' => 'Hình minh họa mặt tiền và tường ngoài trời',
                  'subtitle' => 'Một số ảnh minh họa thực tế cho mặt tiền, tường ngoài trời và bề mặt cần giữ màu ổn định dưới nắng mưa.',
                  'class' => 'landing-visual-story',
              ]
          );
      }
      ?>

      <section id="goi-y-son-ngoai-that" class="landing-section">
        <div class="section-heading landing-section-head">
          <div>
            <h2 class="section-title">4 mã sơn ngoại thất nên xem trước</h2>
            <p class="section-sub">Các mã dưới đây đại diện cho nhóm ngoại thất phổ biến khi cần che phủ, bền màu và dễ chốt theo mặt tiền nhà ở.</p>
          </div>
          <a class="btn btn-outline btn-sm" href="<?php echo esc_url($landing_shop_url); ?>">Xem toàn bộ kho</a>
        </div>
        <div class="landing-product-grid">
          <?php $landing_render_product_cards($landing_featured_products); ?>
        </div>
      </section>

      <div class="info-grid landing-faq-grid">
        <div class="info-card">
          <h3>Khi nào cần chống thấm trước khi sơn?</h3>
          <p>Nếu tường đã ngấm nước, rêu mốc hoặc chân tường ẩm kéo dài, nên xử lý chống thấm hoặc nguyên nhân thấm trước rồi mới sơn phủ.</p>
        </div>
        <div class="info-card">
          <h3>Tường cũ có thể sơn đè luôn không?</h3>
          <p>Không nên mặc định như vậy. Cần xem lớp cũ còn bám tốt hay đã phấn hóa, bong tróc để quyết định xử lý sạch nền và dùng lót lại.</p>
        </div>
        <div class="info-card">
          <h3>Cần gửi gì để chốt báo giá nhanh?</h3>
          <p>Ảnh mặt tiền, diện tích ước tính, hiện trạng tường và mong muốn về độ bền màu là đủ để đội kỹ thuật gợi ý nhóm sơn nhanh hơn.</p>
        </div>
      </div>

      <?php
      echo do_shortcode(
          '[lead_capture_form source="landing-son-ngoai-that" title="Để lại thông tin để nhận gợi ý sơn ngoại thất phù hợp" subtitle="Gửi ảnh mặt tiền, diện tích và hiện trạng tường để đội kỹ thuật gọi lại tư vấn nhanh." button="Nhận tư vấn sơn ngoại thất"]'
      );
      ?>

      <?php
      if (function_exists('my_theme_render_solution_pathways')) {
          my_theme_render_solution_pathways('exterior');
      }
      ?>

      <div class="page-section cta-inline cta-inline--essentials">
        <div class="cta-inline__content">
          <div class="cta-inline__lead">
            <p class="eyebrow eyebrow-muted">Quy trình chốt nhanh</p>
            <h3>Gửi ảnh mặt tiền, nhận gợi ý hệ sơn, rồi chốt vật tư theo hiện trạng thực tế</h3>
            <p class="text-muted">Chỉ cần ảnh bề mặt, m2 và mức ưu tiên là đủ để lên phương án lót, phủ hoặc xử lý nền gọn hơn.</p>
            <div class="cta-inline__steps" aria-label="Các bước chốt vật tư ngoại thất">
              <span class="cta-inline__step">1. Gửi ảnh và m2</span>
              <span class="cta-inline__step">2. Chốt lót + phủ</span>
              <span class="cta-inline__step">3. Nhận lịch giao</span>
            </div>
          </div>
          <div class="cta-inline__actions">
            <a class="btn btn-primary" href="<?php echo esc_url($landing_phone_href); ?>">Gọi <?php echo esc_html($landing_phone_display); ?></a>
            <a class="btn btn-outline" href="<?php echo esc_url($landing_guide_url); ?>">Xem hướng dẫn mua hàng</a>
          </div>
        </div>
      </div>
    </article>
  </div>
</main>
<?php get_footer();
