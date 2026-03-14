<?php
/** Template Name: Chính sách đổi trả */
get_header();
$returns_business = function_exists('my_theme_get_business_profile') ? my_theme_get_business_profile() : [];
$returns_phone_display = isset($returns_business['phone_display']) ? (string) $returns_business['phone_display'] : '0944 857 999';
$returns_phone_href = isset($returns_business['phone_href']) ? (string) $returns_business['phone_href'] : 'tel:0944857999';
$returns_zalo_url = isset($returns_business['zalo_url']) ? (string) $returns_business['zalo_url'] : 'https://zalo.me/0944857999';
?>
<main id="main-content">
  <div class="container">
    <article class="page-section single-article page-shell">
      <ul class="breadcrumb">
        <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
        <li>Chính sách đổi trả</li>
      </ul>
      <h1 class="page-title">Chính sách đổi trả</h1>
      <p class="text-muted">Áp dụng cho sản phẩm sơn, bột bả, vật liệu chống thấm do Đại lý Sơn Phát Tấn cung cấp.</p>
      <div class="cta-compact">
        <div>
          <strong>Kiểm tra hàng ngay khi nhận</strong>
          <p class="text-muted">Báo đổi trả trong 48 giờ để được hỗ trợ nhanh nhất.</p>
        </div>
        <div class="cta-compact__actions">
          <a class="btn btn-primary btn-sm" href="<?php echo esc_url($returns_phone_href); ?>">Gọi hỗ trợ</a>
          <a class="btn btn-outline btn-sm" href="<?php echo esc_url($returns_zalo_url); ?>" target="_blank" rel="noopener">Zalo hỗ trợ</a>
        </div>
      </div>
      <div class="search-scope" aria-label="Lối tắt hỗ trợ đổi trả">
        <a class="chip" href="<?php echo esc_url($returns_phone_href); ?>">Gọi hỗ trợ</a>
        <a class="chip" href="<?php echo esc_url($returns_zalo_url); ?>" target="_blank" rel="noopener">Gửi ảnh qua Zalo</a>
        <a class="chip" href="<?php echo esc_url(home_url('/van-chuyen-giao-hang')); ?>">Vận chuyển</a>
        <a class="chip" href="<?php echo esc_url(home_url('/faq')); ?>">FAQ</a>
      </div>
      <?php
      if (function_exists('my_theme_render_service_compass')) {
          my_theme_render_service_compass([
              'class' => 'service-compass--policy',
              'eyebrow' => 'Sau khi đọc chính sách',
              'title' => 'Nếu đang xử lý đơn thực tế, đây là 3 đường đi nhanh hơn',
              'subtitle' => 'Gửi ảnh và mã đơn nếu cần xử lý đổi trả. Xem thêm vận chuyển nếu đang đối chiếu trách nhiệm giao nhận. Hoặc liên hệ trực tiếp để đội kỹ thuật xác nhận tình huống cụ thể.',
          ]);
      }
      ?>
      <div class="info-grid">
        <div class="info-card">
          <h3>Cần gửi gì?</h3>
          <p>Mã đơn, ảnh bao bì, ảnh tem nhãn và mô tả lỗi để đội kỹ thuật kiểm tra nhanh hơn.</p>
        </div>
        <div class="info-card">
          <h3>Thời gian xác nhận</h3>
          <p>Trong 24 giờ làm việc kể từ khi nhận đủ hình ảnh và thông tin sản phẩm.</p>
        </div>
        <div class="info-card">
          <h3>Lưu ý quan trọng</h3>
          <p>Sơn đã pha màu hoặc hàng đã mở nắp sẽ không áp dụng đổi trả trừ lỗi từ nhà sản xuất.</p>
        </div>
      </div>
      <div class="content-block">
        <h3>1. Trường hợp không hỗ trợ đổi trả</h3>
        <ul class="list-plain">
          <li>Sơn đã pha màu theo yêu cầu riêng.</li>
          <li>Sản phẩm đã mở nắp, sử dụng hoặc hư hỏng do bảo quản/thi công sai.</li>
          <li>Sản phẩm mua quá 07 ngày kể từ ngày giao.</li>
        </ul>
        <h3>2. Trường hợp được đổi trả</h3>
        <ul class="list-plain">
          <li>Giao sai mã màu, sai dung tích so với đơn đặt.</li>
          <li>Lỗi kỹ thuật từ nhà sản xuất (phồng rộp, tách lớp ngay khi mở nắp).</li>
          <li>Hư hỏng do vận chuyển, bao bì rách/ móp nặng.</li>
        </ul>
        <p><strong>Thời hạn báo đổi trả:</strong> trong vòng 48 giờ kể từ khi nhận hàng, kèm hình ảnh/ video.</p>
        <h3>3. Quy trình xử lý</h3>
        <ol>
          <li>Liên hệ số tư vấn <?php echo esc_html($returns_phone_display); ?> hoặc Zalo <?php echo esc_html($returns_phone_display); ?>, cung cấp mã đơn và ảnh sản phẩm.</li>
          <li>Đội kỹ thuật kiểm tra và xác nhận trong 24 giờ làm việc.</li>
          <li>Đổi mới/ hoàn hàng tại công trình hoặc kho Phát Tấn. Chi phí vận chuyển do đại lý chịu nếu lỗi từ chúng tôi.</li>
        </ol>
        <h3>4. Hoàn tiền</h3>
        <p>Chỉ áp dụng khi không thể đổi hàng tương đương. Thời gian hoàn tối đa 3 ngày làm việc.</p>
      </div>
      <?php
      if (function_exists('my_theme_render_lead_capture_form')) {
          echo my_theme_render_lead_capture_form([
              'source' => 'returns-page',
              'title' => 'Cần xử lý đổi trả hoặc kiểm tra lỗi đơn hàng?',
              'subtitle' => 'Điền mã đơn, mô tả tình trạng, bề mặt hoặc lỗi gặp phải. Đội kỹ thuật sẽ phản hồi theo trường hợp thực tế thay vì trả lời chung.',
              'button' => 'Gửi yêu cầu xử lý',
          ]);
      }
      ?>
      <div class="cta-inline">
        <div class="cta-inline__content">
          <div>
            <h3>Cần xác nhận tình trạng lỗi?</h3>
            <p class="text-muted">Gửi ảnh sản phẩm và mã đơn qua Zalo để kỹ thuật phản hồi nhanh.</p>
          </div>
          <div class="cta-inline__actions">
            <a class="btn btn-accent" href="<?php echo esc_url($returns_zalo_url); ?>" target="_blank" rel="noopener">Gửi ảnh qua Zalo</a>
            <a class="btn btn-outline" href="<?php echo esc_url(home_url('/lien-he')); ?>">Gửi yêu cầu hỗ trợ</a>
          </div>
        </div>
      </div>
      <div class="cta">
        <div>
          <h3>Cần hỗ trợ đổi trả?</h3>
          <p>Gọi hoặc nhắn Zalo ngay, chúng tôi phản hồi trong 15 phút giờ hành chính.</p>
        </div>
        <div>
          <a class="btn btn-primary" href="<?php echo esc_url($returns_phone_href); ?>">Gọi hỗ trợ đổi trả</a>
          <a class="btn btn-outline" href="<?php echo esc_url($returns_zalo_url); ?>" target="_blank" rel="noopener">Zalo hỗ trợ</a>
        </div>
      </div>
    </article>
  </div>
</main>
<?php get_footer();
