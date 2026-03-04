<?php
/** Template Name: Giải pháp sơn nội thất */
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
    'duluxeasycleanlauchuihieuquabematbong',
    'jotun-majestic-silk-5l',
    'nippon-odourless-5l',
    'sonnuocnoithatmaxilitetotaltuduluxbematmo',
]);
?>
<main id="main-content">
  <div class="container">
    <article class="page-section single-article page-shell landing-shell">
      <ul class="breadcrumb">
        <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
        <li>Giải pháp sơn nội thất</li>
      </ul>

      <section class="landing-hero">
        <div class="landing-hero__main">
          <p class="eyebrow eyebrow-muted">Giải pháp theo nhu cầu</p>
          <h1 class="page-title">Giải pháp sơn nội thất</h1>
          <p class="landing-hero__lead">Landing page này giúp chốt nhanh nhóm sơn nội thất theo phòng sử dụng, mức lau chùi, ngân sách và hệ lót cần đi cùng để tránh mua dư hoặc mua sai vật tư.</p>

          <div class="search-scope" aria-label="Lối tắt nội dung sơn nội thất">
            <a class="chip" href="#son-noi-that-theo-phong">Theo phòng</a>
            <a class="chip" href="#son-noi-that-theo-ngan-sach">Theo ngân sách</a>
            <a class="chip" href="#goi-y-son-noi-that">Sản phẩm gợi ý</a>
            <a class="chip" href="<?php echo esc_url($landing_blog_url . 'cach-chon-son-noi-that-de-lau-chui-cho-nha-o/'); ?>">Bài tư vấn</a>
            <a class="chip" href="<?php echo esc_url($landing_contact_url); ?>">Nhận báo giá</a>
          </div>

          <div class="trust-row" aria-label="Điểm nổi bật sơn nội thất">
            <span class="trust-item">Chốt theo từng phòng</span>
            <span class="trust-item">Ưu tiên mùi nhẹ, dễ lau chùi</span>
            <span class="trust-item">Có hệ lót và phủ đồng bộ</span>
          </div>

          <div class="landing-hero__actions">
            <a class="btn btn-primary" href="<?php echo esc_url($landing_phone_href); ?>">Gọi báo giá</a>
            <a class="btn btn-outline" href="<?php echo esc_url($landing_zalo_url); ?>" target="_blank" rel="noopener">Zalo kỹ thuật</a>
            <a class="btn btn-accent" href="<?php echo esc_url($landing_shop_url); ?>">Mở kho sản phẩm</a>
          </div>
        </div>

        <aside class="landing-hero__panel">
          <h3>Chuẩn bị trước khi hỏi báo giá</h3>
          <ol class="list-numbered landing-checklist">
            <li>Diện tích từng phòng hoặc tổng m2 cần sơn.</li>
            <li>Tường mới hay tường cũ đã sơn lại nhiều lần.</li>
            <li>Mức ưu tiên: lau chùi, bề mặt mờ hay tối ưu chi phí.</li>
            <li>Thời điểm cần giao hàng hoặc cần hỗ trợ màu sắc.</li>
          </ol>
          <div class="landing-kpis" aria-label="Cam kết hỗ trợ">
            <div class="landing-kpi">
              <strong>15 phút</strong>
              <span>phản hồi báo giá trong giờ làm việc</span>
            </div>
            <div class="landing-kpi">
              <strong>3 nhóm</strong>
              <span>lựa chọn gọn theo phòng, ngân sách, mức lau chùi</span>
            </div>
          </div>
        </aside>
      </section>

      <div class="info-grid">
        <div class="info-card">
          <h3>Chọn theo phòng</h3>
          <p>Phòng khách cần đẹp và dễ vệ sinh, phòng ngủ ưu tiên mùi nhẹ và bề mặt dịu, còn căn hộ cho thuê cần tối ưu chi phí nhưng vẫn ổn định.</p>
        </div>
        <div class="info-card">
          <h3>Chọn theo mức lau chùi</h3>
          <p>Nhà có trẻ nhỏ, hành lang hoặc khu ăn uống nên đi vào nhóm màng sơn chắc hơn để lau chùi thường xuyên mà ít lo loang bề mặt.</p>
        </div>
        <div class="info-card">
          <h3>Đừng bỏ qua sơn lót</h3>
          <p>Tường mới hoặc mảng vá sửa cần lớp lót đúng để màu đều hơn và lớp phủ không nhanh ố, loang hoặc bong sau thời gian ngắn.</p>
        </div>
      </div>

      <div id="son-noi-that-theo-phong" class="content-block">
        <h3>Chọn sơn nội thất theo từng phòng</h3>
        <p><strong>Phòng khách:</strong> ưu tiên cảm giác sạch, sáng, dễ lau chùi ở các mảng tường hay va chạm. Nhóm như Dulux EasyClean hoặc Jotun Majestic thường phù hợp khi cần bề mặt nhìn đẹp lâu.</p>
        <p><strong>Phòng ngủ:</strong> nên ưu tiên mùi nhẹ, màu dịu và bề mặt mờ hoặc satin để tổng thể dễ chịu hơn. Với phòng ít va chạm, có thể tối ưu chi phí bằng cách chọn hệ vừa đủ.</p>
        <p><strong>Căn hộ cho thuê hoặc công trình cần tối ưu ngân sách:</strong> nên chốt theo bộ lót + phủ phổ thông, tránh chỉ nhìn riêng lớp phủ mà bỏ qua bước xử lý bề mặt.</p>
      </div>

      <div id="son-noi-that-theo-ngan-sach" class="content-block">
        <h3>Chọn theo ngân sách mà vẫn giữ hệ sơn đúng</h3>
        <p>Nếu cần hoàn thiện đẹp và bề mặt chắc hơn, nên đi vào nhóm nội thất cao hơn. Nếu cần tối ưu chi phí cho diện tích lớn, hãy giảm ở cấp độ dòng sản phẩm nhưng vẫn giữ đúng lớp lót và số lớp phủ.</p>
        <p>Thực tế nhiều công trình xuống cấp nhanh không phải vì chọn sai thương hiệu, mà vì bỏ lớp lót hoặc mua thiếu vật tư làm lớp sơn phủ bị mỏng. Trước khi đặt hàng, bạn có thể dùng <a href="<?php echo esc_url($landing_calculator_url); ?>">công cụ tính sơn theo m2</a> để có số liệu gần đúng hơn.</p>
      </div>

      <?php
      if (function_exists('my_theme_render_visual_story_gallery')) {
          my_theme_render_visual_story_gallery(
              'interior',
              [
                  'title' => 'Hình minh họa không gian nội thất',
                  'subtitle' => 'Một số ảnh minh họa thực tế để khách dễ hình dung bề mặt, ánh sáng và cảm giác hoàn thiện trước khi chốt hệ sơn nội thất.',
                  'class' => 'landing-visual-story',
              ]
          );
      }
      ?>

      <section id="goi-y-son-noi-that" class="landing-section">
        <div class="section-heading landing-section-head">
          <div>
            <h2 class="section-title">4 mã sơn nội thất nên xem trước</h2>
            <p class="section-sub">Các mã dưới đây đại diện cho nhóm dễ lau chùi, mùi nhẹ và phổ biến cho nhà ở dân dụng.</p>
          </div>
          <a class="btn btn-outline btn-sm" href="<?php echo esc_url($landing_shop_url); ?>">Xem toàn bộ kho</a>
        </div>
        <div class="landing-product-grid">
          <?php $landing_render_product_cards($landing_featured_products); ?>
        </div>
      </section>

      <div class="info-grid landing-faq-grid">
        <div class="info-card">
          <h3>Có cần chọn bóng hay mờ ngay từ đầu?</h3>
          <p>Có. Bề mặt bóng hoặc bán bóng lau chùi tốt hơn, còn bề mặt mờ dịu mắt hơn và che khuyết điểm tường tốt hơn.</p>
        </div>
        <div class="info-card">
          <h3>Nhà mới có cần lót không?</h3>
          <p>Nên có. Tường mới và mảng vá sửa là trường hợp rất nên dùng sơn lót để ổn định nền trước khi lên màu hoàn thiện.</p>
        </div>
        <div class="info-card">
          <h3>Khi nào nên hỏi kỹ thuật trước khi mua?</h3>
          <p>Khi bạn chưa chắc loại tường, cần phối màu nhiều phòng hoặc muốn tối ưu chi phí theo diện tích lớn. Lúc đó nên gửi ảnh và m2 để được chốt đúng hệ.</p>
        </div>
      </div>

      <?php
      echo do_shortcode(
          '[lead_capture_form source="landing-son-noi-that" title="Để lại thông tin để nhận gợi ý sơn nội thất phù hợp" subtitle="Gửi diện tích, số phòng và nhu cầu lau chùi để đội kỹ thuật gọi lại tư vấn nhanh." button="Nhận tư vấn sơn nội thất"]'
      );
      ?>

      <?php
      if (function_exists('my_theme_render_solution_pathways')) {
          my_theme_render_solution_pathways('interior');
      }
      ?>

      <div class="page-section cta-inline cta-inline--essentials">
        <div class="cta-inline__content">
          <div class="cta-inline__lead">
            <p class="eyebrow eyebrow-muted">Quy trình chốt nhanh</p>
            <h3>Gửi nhu cầu, nhận gợi ý hệ sơn, rồi chốt vật tư trong cùng một lần trao đổi</h3>
            <p class="text-muted">Chỉ cần diện tích, tình trạng tường và mức đầu tư dự kiến là đủ để lên báo giá và hệ lót/phủ phù hợp hơn.</p>
            <div class="cta-inline__steps" aria-label="Các bước chốt vật tư nội thất">
              <span class="cta-inline__step">1. Gửi m2 và số phòng</span>
              <span class="cta-inline__step">2. Chốt nhóm sơn + lót</span>
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
          <h3>Cần chốt nhanh sơn nội thất cho nhà ở hoặc căn hộ?</h3>
          <p>Gửi diện tích, bề mặt tường và mức đầu tư dự kiến. Đội kỹ thuật sẽ gợi ý nhóm sơn và quy cách phù hợp hơn ngay trong khung làm việc.</p>
        </div>
        <div>
          <a class="btn btn-primary" href="<?php echo esc_url($landing_contact_url); ?>">Gửi yêu cầu báo giá</a>
          <a class="btn btn-outline" href="<?php echo esc_url($landing_blog_url . 'cach-chon-son-noi-that-de-lau-chui-cho-nha-o/'); ?>">Xem bài tư vấn</a>
          <a class="btn btn-accent" href="<?php echo esc_url($landing_shop_url); ?>">Xem sản phẩm</a>
        </div>
      </div>
    </article>
  </div>
</main>
<?php get_footer();
