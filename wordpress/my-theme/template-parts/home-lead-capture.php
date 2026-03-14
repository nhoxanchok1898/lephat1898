<?php
$home_lead_snapshot = function_exists('my_theme_get_store_snapshot') ? my_theme_get_store_snapshot() : [];
$home_lead_phone_href = isset($home_lead_snapshot['phone_href']) ? (string) $home_lead_snapshot['phone_href'] : 'tel:0944857999';
$home_lead_phone_display = isset($home_lead_snapshot['phone_display']) ? (string) $home_lead_snapshot['phone_display'] : '0944 857 999';
$home_lead_zalo_url = isset($home_lead_snapshot['zalo_url']) ? (string) $home_lead_snapshot['zalo_url'] : 'https://zalo.me/0944857999';
$home_lead_hours = isset($home_lead_snapshot['hours_display']) ? (string) $home_lead_snapshot['hours_display'] : 'Thứ 2 - Thứ 7: 7:30 - 18:00';
$home_lead_service_areas = isset($home_lead_snapshot['service_areas_display']) ? (string) $home_lead_snapshot['service_areas_display'] : 'TP.HCM, Bình Dương, Đồng Nai';
$home_lead_shop_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
?>
<div class="home-lead-shell" id="bao-gia-nhanh">
  <section class="page-section home-lead-shell__intro" aria-label="Gửi nhu cầu để nhận báo giá nhanh">
    <div class="landing-hero">
      <div class="landing-hero__main">
        <p class="eyebrow eyebrow-muted">Báo giá và điều hướng kỹ thuật</p>
        <h2 class="section-title">Chưa chắc mã sơn? Gửi nhu cầu để đội kỹ thuật chốt hướng đi nhanh hơn</h2>
        <p class="landing-hero__lead">Khối này dành cho khách đang ở giai đoạn khoanh vật tư: chưa chắc nên chọn dòng nào, cần báo giá theo diện tích hoặc muốn xác nhận lại hệ sơn trước khi đặt.</p>

        <div class="search-scope" aria-label="Lối tắt nhóm nhu cầu">
          <a class="chip" href="<?php echo esc_url(home_url('/giai-phap-son-noi-that')); ?>">Sơn nội thất</a>
          <a class="chip" href="<?php echo esc_url(home_url('/giai-phap-son-ngoai-that')); ?>">Sơn ngoại thất</a>
          <a class="chip" href="<?php echo esc_url(home_url('/giai-phap-chong-tham')); ?>">Chống thấm</a>
          <a class="chip" href="<?php echo esc_url(home_url('/giai-phap-son-epoxy')); ?>">Sơn epoxy</a>
          <a class="chip" href="<?php echo esc_url(home_url('/huong-dan-mua-hang')); ?>">Hướng dẫn mua hàng</a>
        </div>

        <div class="trust-row" aria-label="Cam kết hỗ trợ">
          <span class="trust-item">Chốt theo bề mặt và hạng mục</span>
          <span class="trust-item">Hỗ trợ hotline và Zalo kỹ thuật</span>
          <span class="trust-item">Có thể điều hướng lại sang đúng landing page</span>
        </div>

        <div class="landing-hero__actions">
          <a class="btn btn-primary" href="<?php echo esc_url($home_lead_phone_href); ?>">Gọi <?php echo esc_html($home_lead_phone_display); ?></a>
          <a class="btn btn-outline" href="<?php echo esc_url($home_lead_zalo_url); ?>" target="_blank" rel="noopener">Zalo kỹ thuật</a>
          <a class="btn btn-accent" href="<?php echo esc_url($home_lead_shop_url); ?>">Mở kho sản phẩm</a>
        </div>
      </div>

      <aside class="landing-hero__panel">
        <h3>Nên gửi gì để được phản hồi nhanh hơn?</h3>
        <ol class="list-numbered landing-checklist">
          <li>Loại bề mặt đang thi công: tường trong nhà, mặt tiền, sân thượng, sàn epoxy hay cửa sắt.</li>
          <li>Diện tích ước tính hoặc kích thước cơ bản để khoanh định mức vật tư ban đầu.</li>
          <li>Khu vực giao hàng và tiến độ cần vật tư để đội vận hành sắp luồng hỗ trợ phù hợp.</li>
          <li>Nếu có ảnh hiện trạng hoặc mã đang cân nhắc, ghi luôn trong phần ghi chú để khỏi gọi hỏi lại nhiều lần.</li>
        </ol>

        <div class="landing-kpis" aria-label="Thông tin hỗ trợ">
          <div class="landing-kpi">
            <strong><?php echo esc_html($home_lead_hours); ?></strong>
            <span>Khung giờ đội kỹ thuật phản hồi và xác nhận nhu cầu.</span>
          </div>
          <div class="landing-kpi">
            <strong><?php echo esc_html($home_lead_service_areas); ?></strong>
            <span>Khu vực giao nhanh và hỗ trợ công trình hiện đang phục vụ.</span>
          </div>
          <div class="landing-kpi">
            <strong>Điện thoại hoặc Zalo</strong>
            <span>Nếu đơn gấp, có thể chuyển thẳng sang kênh liên hệ phù hợp ngay sau khi gửi form.</span>
          </div>
          <div class="landing-kpi">
            <strong>Đi từ nhu cầu thực tế</strong>
            <span>Không cần biết sẵn mã sơn, chỉ cần mô tả đúng hiện trạng để được điều hướng.</span>
          </div>
        </div>
      </aside>
    </div>
  </section>

  <?php
  if (function_exists('my_theme_render_lead_capture_form')) {
      echo my_theme_render_lead_capture_form([
          'source' => 'home-page',
          'title' => 'Gửi thông tin để nhận báo giá hoặc hướng dẫn chọn vật tư',
          'subtitle' => 'Điền nhu cầu thực tế, diện tích, bề mặt và kênh muốn liên hệ. Đội kỹ thuật sẽ dựa vào đó để báo giá hoặc điều hướng bạn sang đúng nhóm giải pháp.',
          'button' => 'Gửi yêu cầu ngay',
      ]);
  }
  ?>
</div>
