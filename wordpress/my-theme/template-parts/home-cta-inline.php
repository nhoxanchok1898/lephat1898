<?php
$cta_business = function_exists('my_theme_get_business_profile') ? my_theme_get_business_profile() : [];
$cta_phone_href = isset($cta_business['phone_href']) ? (string) $cta_business['phone_href'] : 'tel:0944857999';
$cta_zalo_url = isset($cta_business['zalo_url']) ? (string) $cta_business['zalo_url'] : 'https://zalo.me/0944857999';
$cta_hours = isset($cta_business['hours_display']) ? (string) $cta_business['hours_display'] : 'Thứ 2 - Thứ 7: 7:30 - 18:00';
$cta_hours_note = isset($cta_business['hours_note']) ? (string) $cta_business['hours_note'] : 'Ngoài giờ vẫn nhận yêu cầu qua Zalo và phản hồi sớm trong khung tiếp theo.';
$cta_service_areas = isset($cta_business['service_areas_display']) ? (string) $cta_business['service_areas_display'] : 'TP.HCM, Bình Dương, Đồng Nai';
?>
<section class="page-section cta-inline cta-inline--essentials">
  <div class="cta-inline__content">
    <div class="cta-inline__lead">
      <p class="eyebrow eyebrow-muted">Thông tin mua hàng nhanh</p>
      <h3>Chốt vật tư gọn hơn cho thợ và công trình</h3>
      <p class="text-muted">Gửi diện tích, bề mặt và tiến độ giao để đội kỹ thuật đề xuất hệ sơn, quy cách và báo giá sát nhu cầu thực tế.</p>
      <div class="cta-inline__steps" aria-label="Quy trình hỗ trợ nhanh">
        <span class="cta-inline__step">1. Gửi nhu cầu</span>
        <span class="cta-inline__step">2. Chốt mã sơn</span>
        <span class="cta-inline__step">3. Sắp lịch giao</span>
      </div>
    </div>
    <div class="cta-inline__actions">
      <a class="btn btn-primary" href="<?php echo esc_url($cta_phone_href); ?>">Gọi tư vấn nhanh</a>
      <a class="btn btn-outline" href="<?php echo esc_url($cta_zalo_url); ?>" target="_blank" rel="noopener">Zalo kỹ thuật</a>
      <a class="btn btn-accent" href="<?php echo esc_url(home_url('/huong-dan-mua-hang')); ?>">Xem hướng dẫn</a>
    </div>
  </div>
  <div class="cta-inline__meta">
    <article class="cta-inline__meta-card">
      <h4>Giờ làm việc</h4>
      <p><?php echo esc_html($cta_hours); ?>. <?php echo esc_html($cta_hours_note); ?></p>
    </article>
    <article class="cta-inline__meta-card">
      <h4>Khu vực phục vụ</h4>
      <p><?php echo esc_html($cta_service_areas); ?> và các công trình lân cận cần giao vật tư nhanh.</p>
    </article>
    <article class="cta-inline__meta-card">
      <h4>Hồ sơ đơn hàng</h4>
      <p>Có hóa đơn, xác nhận quy cách, hỗ trợ tiến độ giao và tư vấn đúng bề mặt thi công.</p>
    </article>
  </div>
</section>
