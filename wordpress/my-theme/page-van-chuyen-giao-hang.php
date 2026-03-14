<?php
/* Template Name: Vận chuyển & giao hàng */
get_header();
$shipping_business = function_exists('my_theme_get_business_profile') ? my_theme_get_business_profile() : [];
$shipping_phone_href = isset($shipping_business['phone_href']) ? (string) $shipping_business['phone_href'] : 'tel:0944857999';
$shipping_zalo_url = isset($shipping_business['zalo_url']) ? (string) $shipping_business['zalo_url'] : 'https://zalo.me/0944857999';
?>
<main id="main-content">
  <div class="container">
    <article class="page-section single-article page-shell">
      <ul class="breadcrumb">
        <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
        <li>Vận chuyển & giao hàng</li>
      </ul>

      <h1 class="page-title">Vận chuyển & giao hàng</h1>
      <p class="text-muted">Chủ động lịch giao, rõ phí trước khi chốt đơn.</p>

      <div class="info-grid">
        <div class="info-card">
          <h3>Phạm vi giao hàng</h3>
          <p>TP.HCM và các tỉnh lân cận. Đơn tỉnh xa sẽ báo lộ trình và phí trước.</p>
        </div>
        <div class="info-card">
          <h3>Thời gian giao</h3>
          <p>Nội thành: 24–48h. Ngoại tỉnh: 2–5 ngày tùy tuyến và số lượng.</p>
        </div>
        <div class="info-card">
          <h3>Giao lẻ – giao công trình</h3>
          <p>Nhận giao lẻ cho nhà dân và giao theo tiến độ cho công trình.</p>
        </div>
        <div class="info-card">
          <h3>Phí vận chuyển</h3>
          <p>Phí được thông báo rõ theo khoảng cách và khối lượng. Nội thành hỗ trợ giao nhanh.</p>
        </div>
      </div>

      <div class="search-scope" aria-label="Lối tắt giao hàng">
        <a class="chip" href="<?php echo esc_url($shipping_phone_href); ?>">Gọi báo phí</a>
        <a class="chip" href="<?php echo esc_url($shipping_zalo_url); ?>" target="_blank" rel="noopener">Zalo xác nhận lịch giao</a>
        <a class="chip" href="<?php echo esc_url(home_url('/chinh-sach-doi-tra')); ?>">Chính sách đổi trả</a>
        <a class="chip" href="<?php echo esc_url(home_url('/faq')); ?>">FAQ</a>
      </div>
      <?php
      if (function_exists('my_theme_render_service_compass')) {
          my_theme_render_service_compass([
              'class' => 'service-compass--shipping',
              'eyebrow' => 'Chốt giao hàng gọn hơn',
              'title' => 'Nếu đang chuẩn bị nhận hàng, bạn có thể đi tiếp theo 3 hướng này',
              'subtitle' => 'Gọi để chốt phí và giờ giao. Nhắn Zalo nếu cần gửi vị trí hoặc mốc nhận hàng. Hoặc quay lại liên hệ nếu đơn còn phải điều chỉnh vật tư.',
          ]);
      }
      ?>

      <div class="content-block">
        <h3>Lưu ý khi nhận hàng</h3>
        <ul class="list-plain">
          <li>Kiểm tra đúng mã sơn, dung tích và số lượng trước khi ký nhận.</li>
          <li>Thông báo ngay nếu thùng hàng móp, rách hoặc sai nhãn.</li>
          <li>Đơn công trình nên xác nhận lịch giao theo từng giai đoạn thi công.</li>
        </ul>
      </div>

      <?php
      if (function_exists('my_theme_render_lead_capture_form')) {
          echo my_theme_render_lead_capture_form([
              'source' => 'shipping-page',
              'title' => 'Cần chốt phí giao hoặc lịch giao theo công trình?',
              'subtitle' => 'Gửi địa điểm nhận hàng, khối lượng vật tư, thời gian cần giao và ghi chú xuống hàng. Đội vận hành sẽ phản hồi phương án phù hợp hơn.',
              'button' => 'Gửi yêu cầu giao hàng',
          ]);
      }
      ?>

      <div class="cta-inline">
        <div class="cta-inline__content">
          <div>
            <h3>Cần báo phí giao nhanh?</h3>
            <p class="text-muted">Gọi/Zalo để xác nhận phí và lịch giao phù hợp.</p>
          </div>
          <div class="cta-inline__actions">
            <a class="btn btn-primary" href="<?php echo esc_url($shipping_phone_href); ?>">Gọi báo phí</a>
            <a class="btn btn-outline" href="<?php echo esc_url($shipping_zalo_url); ?>" target="_blank" rel="noopener">Zalo kỹ thuật</a>
          </div>
        </div>
      </div>
    </article>
  </div>
</main>
<?php get_footer(); ?>
