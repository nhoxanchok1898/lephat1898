<?php
/** Template Name: Hướng dẫn mua hàng */
get_header();
$guide_business = function_exists('my_theme_get_business_profile') ? my_theme_get_business_profile() : [];
$guide_phone_display = isset($guide_business['phone_display']) ? (string) $guide_business['phone_display'] : '0944 857 999';
$guide_phone_href = isset($guide_business['phone_href']) ? (string) $guide_business['phone_href'] : 'tel:0944857999';
$guide_zalo_url = isset($guide_business['zalo_url']) ? (string) $guide_business['zalo_url'] : 'https://zalo.me/0944857999';
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
