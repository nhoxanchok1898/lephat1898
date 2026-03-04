<?php
/** Template Name: Liên hệ */
get_header();
$contact_business = function_exists('my_theme_get_business_profile') ? my_theme_get_business_profile() : [];
$contact_name = isset($contact_business['contact_name']) ? (string) $contact_business['contact_name'] : 'Trần Thị Ngọc Thúy';
$contact_phone_display = isset($contact_business['phone_display']) ? (string) $contact_business['phone_display'] : '0944 857 999';
$contact_phone_href = isset($contact_business['phone_href']) ? (string) $contact_business['phone_href'] : 'tel:0944857999';
$contact_email = isset($contact_business['email']) ? (string) $contact_business['email'] : 'lephat1898@gmail.com';
$contact_email_href = isset($contact_business['email_href']) ? (string) $contact_business['email_href'] : 'mailto:lephat1898@gmail.com';
$contact_zalo_url = isset($contact_business['zalo_url']) ? (string) $contact_business['zalo_url'] : 'https://zalo.me/0944857999';
$contact_address = isset($contact_business['address_full']) ? (string) $contact_business['address_full'] : '392 TL10, Bình Trị Đông, Bình Tân, TP.HCM';
$contact_maps_url = isset($contact_business['maps_url']) ? (string) $contact_business['maps_url'] : '';
$contact_hours = isset($contact_business['hours_display']) ? (string) $contact_business['hours_display'] : 'Thứ 2 - Thứ 7: 7:30 - 18:00';
$contact_hours_note = isset($contact_business['hours_note']) ? (string) $contact_business['hours_note'] : 'Ngoài giờ vẫn nhận yêu cầu qua Zalo và phản hồi sớm trong khung tiếp theo.';
?>
<main id="main-content">
  <div class="container">
    <article class="page-section single-article page-shell">
      <ul class="breadcrumb">
        <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
        <li>Liên hệ</li>
      </ul>
      <h1 class="page-title">Liên hệ</h1>
      <p class="text-muted">Mọi thắc mắc và đặt hàng, vui lòng liên hệ trực tiếp để được báo giá nhanh.</p>
      <div class="cta-compact">
        <div>
          <strong>Cần báo giá ngay?</strong>
          <p class="text-muted">Gọi số tư vấn hoặc nhắn Zalo để được tư vấn hệ sơn phù hợp.</p>
        </div>
        <div class="cta-compact__actions">
          <a class="btn btn-primary btn-sm" href="<?php echo esc_url($contact_phone_href); ?>">Gọi báo giá</a>
          <a class="btn btn-outline btn-sm" href="<?php echo esc_url($contact_zalo_url); ?>" target="_blank" rel="noopener">Zalo tư vấn</a>
        </div>
      </div>
      <div class="search-scope" aria-label="Kênh hỗ trợ nhanh">
        <a class="chip" href="<?php echo esc_url($contact_phone_href); ?>">Gọi ngay</a>
        <a class="chip" href="<?php echo esc_url($contact_zalo_url); ?>" target="_blank" rel="noopener">Zalo kỹ thuật</a>
        <a class="chip" href="<?php echo esc_url(home_url('/huong-dan-mua-hang')); ?>">Hướng dẫn mua hàng</a>
        <a class="chip" href="<?php echo esc_url(home_url('/faq')); ?>">FAQ</a>
      </div>
      <div class="content-block">
        <p><strong>Phụ trách:</strong> <?php echo esc_html($contact_name); ?></p>
        <p><strong>Điện thoại/Zalo:</strong> <a href="<?php echo esc_url($contact_phone_href); ?>"><?php echo esc_html($contact_phone_display); ?></a></p>
        <p><strong>Thư điện tử:</strong> <a href="<?php echo esc_url($contact_email_href); ?>"><?php echo esc_html($contact_email); ?></a></p>
        <p><strong>Địa chỉ:</strong> <?php echo esc_html($contact_address); ?></p>
        <p><?php if ($contact_maps_url !== '') : ?><a class="btn btn-primary" href="<?php echo esc_url($contact_maps_url); ?>" target="_blank" rel="noopener">Mở bản đồ Google</a><?php endif; ?>
        <a class="btn btn-outline" href="<?php echo esc_url($contact_email_href); ?>">Gửi email báo giá</a></p>
      </div>
      <div class="info-grid">
        <div class="info-card">
          <h3>Giờ làm việc</h3>
          <p><?php echo esc_html($contact_hours); ?>. <?php echo esc_html($contact_hours_note); ?></p>
        </div>
        <div class="info-card">
          <h3>Khu vực phục vụ</h3>
          <p>Ưu tiên giao nhanh tại TP.HCM, Bình Dương, Long An và các công trình lân cận. Đơn tỉnh được lên lịch theo tuyến xe.</p>
        </div>
        <div class="info-card">
          <h3>Nhận báo giá nhanh hơn</h3>
          <p>Gửi trước diện tích, bề mặt thi công, thương hiệu dự kiến và thời gian cần hàng để đội kỹ thuật chốt hệ sơn nhanh.</p>
        </div>
      </div>
      <div class="content-block">
        <h3>Thông tin nên chuẩn bị khi liên hệ</h3>
        <ol class="list-numbered">
          <li>Diện tích hoặc khối lượng công việc cần sơn, chống thấm hoặc bột trét.</li>
          <li>Bề mặt thi công: tường mới, tường cũ, sàn, mái, kim loại hay khu vực ẩm ướt.</li>
          <li>Thương hiệu hoặc dòng sơn đang quan tâm nếu đã có mã tham khảo.</li>
          <li>Địa chỉ giao hàng và mốc thời gian cần nhận vật tư.</li>
        </ol>
      </div>
      <div class="trust-row" aria-label="Cam kết phản hồi">
        <span class="trust-item">Phản hồi trong giờ làm việc</span>
        <span class="trust-item">Báo giá theo diện tích</span>
        <span class="trust-item">Gợi ý hệ sơn theo bề mặt</span>
      </div>
      <?php
      echo do_shortcode(
          '[lead_capture_form source="trang-lien-he" title="Để lại thông tin, chúng tôi gọi lại báo giá nhanh" subtitle="Điền thông tin để đội kỹ thuật liên hệ, tư vấn mã sơn và khối lượng phù hợp." button="Gửi yêu cầu liên hệ"]'
      );
      ?>
      <div class="cta-inline">
        <div class="cta-inline__content">
          <div>
            <h3>Nhận tư vấn hệ sơn theo hạng mục</h3>
            <p class="text-muted">Gửi diện tích và yêu cầu, chúng tôi đề xuất vật tư trong 15 phút.</p>
          </div>
          <div class="cta-inline__actions">
            <a class="btn btn-accent" href="<?php echo esc_url(home_url('/huong-dan-mua-hang')); ?>">Xem cách đặt hàng</a>
            <a class="btn btn-outline" href="<?php echo esc_url(function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop')); ?>">Xem bảng giá</a>
          </div>
        </div>
      </div>
      <div class="cta">
        <div>
          <h3>Đặt lịch khảo sát hoặc lấy báo giá nhanh</h3>
          <p>Trong giờ làm việc, phản hồi trong 15 phút. Ngoài giờ, chúng tôi sẽ gọi lại vào buổi sáng hôm sau.</p>
        </div>
        <div>
          <a class="btn btn-primary" href="<?php echo esc_url($contact_phone_href); ?>">Gọi đặt hàng</a>
          <a class="btn btn-outline" href="<?php echo esc_url($contact_zalo_url); ?>" target="_blank" rel="noopener">Zalo kỹ thuật</a>
          <a class="btn btn-accent" href="<?php echo esc_url(home_url('/huong-dan-mua-hang')); ?>">Đặt lịch khảo sát</a>
        </div>
      </div>
    </article>
  </div>
</main>
<?php get_footer();
