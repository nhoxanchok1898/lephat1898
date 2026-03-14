<?php
/** Template Name: Hướng dẫn mua hàng */
get_header();
$guide_business = function_exists('my_theme_get_business_profile') ? my_theme_get_business_profile() : [];
$guide_phone_display = isset($guide_business['phone_display']) ? (string) $guide_business['phone_display'] : '0944 857 999';
$guide_phone_href = isset($guide_business['phone_href']) ? (string) $guide_business['phone_href'] : 'tel:0944857999';
$guide_zalo_url = isset($guide_business['zalo_url']) ? (string) $guide_business['zalo_url'] : 'https://zalo.me/0944857999';
$guide_store_snapshot = function_exists('my_theme_get_store_snapshot') ? my_theme_get_store_snapshot() : [];
$guide_hours = isset($guide_store_snapshot['hours_display']) ? (string) $guide_store_snapshot['hours_display'] : 'Thứ 2 - Thứ 7: 7:30 - 18:00';
$guide_service_areas = isset($guide_store_snapshot['service_areas_display']) ? (string) $guide_store_snapshot['service_areas_display'] : 'TP.HCM, Bình Dương, Đồng Nai';
$guide_catalog_count = isset($guide_store_snapshot['catalog_count']) ? max(0, (int) $guide_store_snapshot['catalog_count']) : 0;
$guide_context_links = function_exists('my_theme_get_page_context_links') ? my_theme_get_page_context_links('huong-dan-mua-hang') : [];
?>
<main id="main-content">
  <div class="container">
    <article class="page-section single-article page-shell">
      <ul class="breadcrumb">
        <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
        <li>Hướng dẫn mua hàng</li>
      </ul>
      <h1 class="page-title">Hướng dẫn mua hàng</h1>
      <p class="text-muted">Quy trình 4 bước để đặt sơn nhanh tại Đại lý Sơn Phát Tấn.</p>
      <div class="cta-compact">
        <div>
          <strong>Đặt hàng trong 15 phút</strong>
          <p class="text-muted">Gọi hoặc Zalo để nhận báo giá theo diện tích.</p>
        </div>
        <div class="cta-compact__actions">
          <a class="btn btn-primary btn-sm" href="<?php echo esc_url($guide_phone_href); ?>">Gọi đặt hàng</a>
          <a class="btn btn-outline btn-sm" href="<?php echo esc_url($guide_zalo_url); ?>" target="_blank" rel="noopener">Zalo báo giá</a>
        </div>
      </div>
      <div class="search-scope" aria-label="Lối tắt quy trình đặt hàng">
        <a class="chip" href="#guide-step-1">Bước 1</a>
        <a class="chip" href="#guide-step-2">Bước 2</a>
        <a class="chip" href="#guide-step-3">Bước 3</a>
        <a class="chip" href="#guide-step-4">Bước 4</a>
        <a class="chip" href="<?php echo esc_url(home_url('/van-chuyen-giao-hang')); ?>">Vận chuyển</a>
      </div>

      <section class="page-context-panel" aria-label="Lối đi nhanh từ hướng dẫn mua hàng">
        <div class="page-context-panel__lead">
          <h2 class="page-context-panel__title">Quy trình đã rõ, giờ đi đúng bước để chốt nhanh hơn</h2>
          <p class="page-context-panel__copy">Nếu bạn đã biết mình đang ở bước nào, hãy chuyển ngay sang kho sản phẩm, giỏ hàng hoặc liên hệ kỹ thuật để rút ngắn thời gian chốt đơn.</p>
        </div>
        <div class="shop-summary__insight" aria-label="Thông tin cửa hàng nhanh">
          <?php if ($guide_catalog_count > 0) : ?><span class="chip chip--soft"><?php echo esc_html((string) $guide_catalog_count); ?> sản phẩm đang có</span><?php endif; ?>
          <span class="chip chip--soft"><?php echo esc_html($guide_hours); ?></span>
          <span class="chip chip--soft"><?php echo esc_html($guide_service_areas); ?></span>
        </div>
        <div class="shop-summary__support" aria-label="Đi tiếp từ hướng dẫn mua hàng">
          <?php foreach ($guide_context_links as $guide_context_link) : ?>
            <?php
            $guide_context_label = isset($guide_context_link['label']) ? trim((string) $guide_context_link['label']) : '';
            $guide_context_url = isset($guide_context_link['url']) ? trim((string) $guide_context_link['url']) : '';
            if ($guide_context_label === '' || $guide_context_url === '') {
                continue;
            }
            ?>
            <a class="chip" href="<?php echo esc_url($guide_context_url); ?>"><?php echo esc_html($guide_context_label); ?></a>
          <?php endforeach; ?>
        </div>
      </section>

      <?php
      if (function_exists('my_theme_render_service_compass')) {
          my_theme_render_service_compass([
              'class' => 'service-compass--guide',
              'eyebrow' => 'Đi theo quy trình này để chốt gọn hơn',
              'title' => 'Nếu đã biết mình đang ở bước nào, bạn có thể rẽ đúng hướng ngay tại đây',
              'subtitle' => 'Tiếp tục vào kho sản phẩm nếu đã có mã. Sang trang liên hệ nếu cần báo giá theo diện tích. Hoặc quay về giải pháp nếu vẫn đang chọn theo bề mặt thi công.',
          ]);
      }
      ?>

      <div class="info-grid">
        <div class="info-card">
          <h3>Chuẩn bị trước khi gọi</h3>
          <p>Diện tích, bề mặt, thương hiệu dự kiến và thời gian cần hàng là đủ để lên báo giá nhanh.</p>
        </div>
        <div class="info-card">
          <h3>Thanh toán</h3>
          <p>Chuyển khoản hoặc tiền mặt. Hàng pha màu và đơn lớn có thể cần xác nhận đặt cọc trước.</p>
        </div>
        <div class="info-card">
          <h3>Giao vật tư</h3>
          <p>Nội thành ưu tiên giao nhanh; đơn công trình được chốt lịch theo từng đợt thi công.</p>
        </div>
      </div>

      <div class="content-block">
        <h3 id="guide-step-1">Bước 1: Gửi nhu cầu</h3>
        <p>Liên hệ số tư vấn <a href="<?php echo esc_url($guide_phone_href); ?>"><?php echo esc_html($guide_phone_display); ?></a> hoặc nhắn Zalo <a href="<?php echo esc_url($guide_zalo_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($guide_phone_display); ?></a>. Cung cấp: loại công trình, diện tích, yêu cầu màu sắc, thời gian giao.</p>

        <h3 id="guide-step-2">Bước 2: Nhận báo giá & phối màu</h3>
        <p>Kỹ thuật đề xuất hệ sơn (lót, phủ, chống thấm) và bảng màu phù hợp. Báo giá gửi trong 30 phút kèm chiết khấu nếu lấy số lượng.</p>

        <div class="cta-inline content-block__cta-inline">
          <div class="cta-inline__content">
            <div>
              <h3>Ước tính vật tư nhanh theo m²</h3>
              <p class="text-muted">Gửi diện tích và bề mặt, chúng tôi tính định mức phù hợp.</p>
            </div>
            <div class="cta-inline__actions">
              <a class="btn btn-accent" href="<?php echo esc_url(home_url('/lien-he')); ?>">Gửi diện tích</a>
              <a class="btn btn-outline" href="<?php echo esc_url(function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop')); ?>">Xem bảng giá</a>
            </div>
          </div>
        </div>

        <h3 id="guide-step-3">Bước 3: Xác nhận & thanh toán</h3>
        <p>Đặt cọc 0–20% tuỳ đơn (hàng pha màu cần cọc). Thanh toán chuyển khoản hoặc tiền mặt khi nhận hàng.</p>

        <h3 id="guide-step-4">Bước 4: Giao hàng & hỗ trợ thi công</h3>
        <p>Giao 24–48h khu vực TP.HCM và lân cận. Hỗ trợ hướng dẫn quy trình thi công, vệ sinh bề mặt, định mức lớp lót/phủ.</p>

        <h3>Thông tin thanh toán</h3>
        <p>Thông tin chuyển khoản sẽ được gửi qua Zalo hoặc điện thoại sau khi xác nhận đơn hàng.</p>
      </div>

      <?php
      if (function_exists('my_theme_render_lead_capture_form')) {
          echo my_theme_render_lead_capture_form([
              'source' => 'guide-page',
              'title' => 'Muốn bỏ qua các bước thủ công và nhận báo giá nhanh hơn?',
              'subtitle' => 'Gửi diện tích, bề mặt, thương hiệu dự kiến và thời gian cần hàng. Đội kỹ thuật sẽ gom giúp bạn các bước cần thiết để chốt vật tư nhanh hơn.',
              'button' => 'Nhận hỗ trợ đặt hàng',
          ]);
      }

      if (function_exists('my_theme_render_recently_viewed_products')) {
          my_theme_render_recently_viewed_products([
              'title' => 'Các mã bạn vừa xem trước khi mở hướng dẫn',
              'aria_label' => 'Các mã bạn vừa xem trước khi mở hướng dẫn',
              'class' => 'related-products-block--recently-viewed related-products-block--guide',
          ]);
      }
      ?>

      <div class="cta">
        <div>
          <h3>Đặt hàng ngay hôm nay</h3>
          <p>Chúng tôi ưu tiên giao trong ngày với đơn nội thành và hỗ trợ phối màu miễn phí.</p>
        </div>
        <div>
          <a class="btn btn-primary" href="<?php echo esc_url($guide_phone_href); ?>">Gọi báo giá</a>
          <a class="btn btn-outline" href="<?php echo esc_url($guide_zalo_url); ?>" target="_blank" rel="noopener">Zalo đặt hàng</a>
          <a class="btn btn-accent" href="<?php echo esc_url(home_url('/lien-he')); ?>">Gửi yêu cầu nhanh</a>
        </div>
      </div>
    </article>
  </div>
</main>
<?php get_footer();
