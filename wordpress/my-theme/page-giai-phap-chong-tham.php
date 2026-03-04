<?php
/** Template Name: Giải pháp chống thấm */
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
    'sikatop-seal-107-25kg',
    'weberdry-2kflex',
    'chongthamsanduluxaquatechmax',
    'kova-ct11a-san-thuong-20kg',
]);
?>
<main id="main-content">
  <div class="container">
    <article class="page-section single-article page-shell landing-shell">
      <ul class="breadcrumb">
        <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
        <li>Giải pháp chống thấm</li>
      </ul>

      <section class="landing-hero">
        <div class="landing-hero__main">
          <p class="eyebrow eyebrow-muted">Giải pháp theo hiện trạng</p>
          <h1 class="page-title">Giải pháp chống thấm</h1>
          <p class="landing-hero__lead">Trang này gom nhanh các tình huống chống thấm thường gặp như sân thượng, tường ngoài trời và khu ẩm để khách hàng đi thẳng từ hiện trạng bề mặt đến hệ vật tư phù hợp hơn.</p>

          <div class="search-scope" aria-label="Lối tắt nội dung chống thấm">
            <a class="chip" href="#chong-tham-theo-khu-vuc">Theo khu vực</a>
            <a class="chip" href="#chong-tham-theo-hien-trang">Theo hiện trạng</a>
            <a class="chip" href="#goi-y-he-chong-tham">Hệ gợi ý</a>
            <a class="chip" href="<?php echo esc_url($landing_blog_url . 'chong-tham-san-thuong-nen-dung-he-nao/'); ?>">Bài tư vấn</a>
            <a class="chip" href="<?php echo esc_url($landing_contact_url); ?>">Gửi ảnh hiện trạng</a>
          </div>

          <div class="trust-row" aria-label="Điểm nổi bật chống thấm">
            <span class="trust-item">Chọn theo sân thượng, tường, khu ẩm</span>
            <span class="trust-item">Ưu tiên đúng hệ theo vết nứt và đọng nước</span>
            <span class="trust-item">Chốt vật tư sau khi xem hiện trạng</span>
          </div>

          <div class="landing-hero__actions">
            <a class="btn btn-primary" href="<?php echo esc_url($landing_phone_href); ?>">Gọi kỹ thuật</a>
            <a class="btn btn-outline" href="<?php echo esc_url($landing_zalo_url); ?>" target="_blank" rel="noopener">Gửi ảnh qua Zalo</a>
            <a class="btn btn-accent" href="<?php echo esc_url($landing_shop_url); ?>">Xem sản phẩm</a>
          </div>
        </div>

        <aside class="landing-hero__panel">
          <h3>Thông tin nên gửi trước khi hỏi chống thấm</h3>
          <ol class="list-numbered landing-checklist">
            <li>Ảnh khu vực đang thấm và mảng bị ảnh hưởng phía dưới.</li>
            <li>Loại bề mặt: bê tông, hồ vữa, tường đã sơn hay nền cũ.</li>
            <li>Có vết nứt, đọng nước hay chân tường ẩm kéo dài hay không.</li>
            <li>Diện tích và thời gian cần thi công hoặc nhận hàng.</li>
          </ol>
          <div class="landing-kpis" aria-label="Cam kết hỗ trợ">
            <div class="landing-kpi">
              <strong>1 lần nhìn ảnh</strong>
              <span>là có thể khoanh được nhóm vật tư cần xem trước</span>
            </div>
            <div class="landing-kpi">
              <strong>Ưu tiên thực tế</strong>
              <span>không chốt sai hệ khi chưa xem hiện trạng bề mặt</span>
            </div>
          </div>
        </aside>
      </section>

      <div class="info-grid">
        <div class="info-card">
          <h3>Sân thượng</h3>
          <p>Ưu tiên nhìn tình trạng nứt, đọng nước và độ ổn định của nền. Đây là nhóm hay cần hệ hai thành phần hoặc hệ linh hoạt hơn.</p>
        </div>
        <div class="info-card">
          <h3>Tường ngoài trời</h3>
          <p>Cần phân biệt thấm qua nứt, thấm chân tường hay lớp sơn cũ mất khả năng bảo vệ để chọn đúng vật tư trước khi sơn lại.</p>
        </div>
        <div class="info-card">
          <h3>Khu ẩm và nhà vệ sinh</h3>
          <p>Ưu tiên hệ phù hợp với khu vực ẩm liên tục, chân tường, khu vực góc hoặc cổ ống thay vì chỉ quan tâm màu phủ bên ngoài.</p>
        </div>
      </div>

      <div id="chong-tham-theo-khu-vuc" class="content-block">
        <h3>Chọn hệ chống thấm theo khu vực thi công</h3>
        <p><strong>Sân thượng và mái bằng:</strong> nên ưu tiên nhóm có khả năng chống thấm tốt trên nền bê tông và chịu điều kiện ngoài trời. Đây là khu vực thường phải xét thêm độ linh hoạt và cách xử lý cổ ống, chân tường.</p>
        <p><strong>Tường ngoài trời:</strong> nếu đã có dấu hiệu thấm hoặc rạn nứt nhỏ, nên xử lý chống thấm đúng trước khi phủ lại màu ngoại thất. Sơn phủ không thay thế được lớp chống thấm khi nguồn thấm còn hoạt động.</p>
        <p><strong>Nhà vệ sinh, ban công, khu ẩm:</strong> nên báo rõ loại nền, vị trí tiếp giáp và lớp hoàn thiện phía trên để đội kỹ thuật gợi ý đúng hệ vật tư hơn.</p>
      </div>

      <div id="chong-tham-theo-hien-trang" class="content-block">
        <h3>Chọn theo hiện trạng bề mặt thay vì chọn theo tên hãng</h3>
        <p>Nếu bề mặt có nứt chân chim, đọng nước hoặc đã từng chống thấm nhưng bong lại, việc đầu tiên không phải là chọn thương hiệu mà là xác định lại nguồn thấm và lớp nền hiện có. Cùng một khu vực nhưng nền mới, nền cũ, nền đã sơn lại hoặc nền đang bở yếu sẽ cần hệ khác nhau.</p>
        <p>Để báo giá sát hơn, bạn có thể gửi ảnh và diện tích qua <a href="<?php echo esc_url($landing_contact_url); ?>">trang liên hệ</a>, đồng thời dùng <a href="<?php echo esc_url($landing_calculator_url); ?>">công cụ tính định mức</a> để có mốc vật tư sơ bộ trước khi chốt đơn.</p>
      </div>

      <?php
      if (function_exists('my_theme_render_visual_story_gallery')) {
          my_theme_render_visual_story_gallery(
              'waterproofing',
              [
                  'title' => 'Hình minh họa hiện trạng chống thấm',
                  'subtitle' => 'Ảnh minh họa tham khảo cho sân thượng, mái bằng và khu ẩm để khách dễ đối chiếu hiện trạng trước khi gửi yêu cầu kỹ thuật.',
                  'class' => 'landing-visual-story',
              ]
          );
      }
      ?>

      <section id="goi-y-he-chong-tham" class="landing-section">
        <div class="section-heading landing-section-head">
          <div>
            <h2 class="section-title">4 hệ chống thấm nên xem trước</h2>
            <p class="section-sub">Những mã dưới đây đại diện cho nhóm chống thấm sân thượng, tường ngoài và khu ẩm phổ biến.</p>
          </div>
          <a class="btn btn-outline btn-sm" href="<?php echo esc_url($landing_shop_url); ?>">Mở kho chống thấm</a>
        </div>
        <div class="landing-product-grid">
          <?php $landing_render_product_cards($landing_featured_products); ?>
        </div>
      </section>

      <div class="info-grid landing-faq-grid">
        <div class="info-card">
          <h3>Có cần bóc lớp sơn cũ trước khi chống thấm?</h3>
          <p>Nếu lớp cũ bong, phồng hoặc nền bên dưới bở yếu thì nên xử lý lại tương đối kỹ trước khi thi công hệ mới. Sơn đè thường chỉ che tạm.</p>
        </div>
        <div class="info-card">
          <h3>Chống thấm xong có sơn màu được không?</h3>
          <p>Có, nhưng phải đúng quy trình và đúng hệ tương thích với lớp chống thấm đã chọn. Không phải lớp nào cũng phủ màu ngay giống nhau.</p>
        </div>
        <div class="info-card">
          <h3>Khi nào nên gọi kỹ thuật trước khi đặt hàng?</h3>
          <p>Khi khu vực đang có vết nứt, thấm kéo dài hoặc bạn chưa chắc đây là thấm từ đâu. Một lần xem ảnh trước sẽ giúp chốt vật tư đúng hơn nhiều.</p>
        </div>
      </div>

      <?php
      echo do_shortcode(
          '[lead_capture_form source="landing-chong-tham" title="Gửi ảnh hiện trạng để được gợi ý hệ chống thấm" subtitle="Điền diện tích, vị trí đang thấm và thời gian cần hàng để đội kỹ thuật gọi lại nhanh hơn." button="Nhận tư vấn chống thấm"]'
      );
      ?>

      <?php
      if (function_exists('my_theme_render_solution_pathways')) {
          my_theme_render_solution_pathways('waterproofing');
      }
      ?>

      <div class="page-section cta-inline cta-inline--essentials">
        <div class="cta-inline__content">
          <div class="cta-inline__lead">
            <p class="eyebrow eyebrow-muted">Quy trình xử lý nhanh</p>
            <h3>Gửi ảnh hiện trạng, chốt nhóm vật tư, rồi lên kế hoạch giao hàng theo hạng mục</h3>
            <p class="text-muted">Khi đã có ảnh bề mặt và diện tích, đội kỹ thuật có thể khoanh nhanh nên đi theo hệ nào và cần chú ý bước nào trước khi mua.</p>
            <div class="cta-inline__steps" aria-label="Các bước chốt vật tư chống thấm">
              <span class="cta-inline__step">1. Gửi ảnh và diện tích</span>
              <span class="cta-inline__step">2. Chốt hệ theo hiện trạng</span>
              <span class="cta-inline__step">3. Nhận lịch giao vật tư</span>
            </div>
          </div>
          <div class="cta-inline__actions">
            <a class="btn btn-primary" href="<?php echo esc_url($landing_phone_href); ?>">Gọi <?php echo esc_html($landing_phone_display); ?></a>
            <a class="btn btn-outline" href="<?php echo esc_url($landing_guide_url); ?>">Xem cách đặt hàng</a>
          </div>
        </div>
      </div>

      <div class="cta">
        <div>
          <h3>Cần chốt nhanh hệ chống thấm theo hiện trạng thật?</h3>
          <p>Gửi ảnh bề mặt, vị trí thấm và diện tích dự kiến. Đội kỹ thuật sẽ gợi ý nhóm vật tư nên xem trước để tránh đặt sai hệ.</p>
        </div>
        <div>
          <a class="btn btn-primary" href="<?php echo esc_url($landing_contact_url); ?>">Gửi ảnh hiện trạng</a>
          <a class="btn btn-outline" href="<?php echo esc_url($landing_blog_url . 'chong-tham-san-thuong-nen-dung-he-nao/'); ?>">Xem bài tư vấn</a>
          <a class="btn btn-accent" href="<?php echo esc_url($landing_shop_url); ?>">Xem sản phẩm</a>
        </div>
      </div>
    </article>
  </div>
</main>
<?php get_footer();
