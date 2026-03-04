<?php
/** Template Name: Câu hỏi thường gặp */
get_header();
$faq_business = function_exists('my_theme_get_business_profile') ? my_theme_get_business_profile() : [];
$faq_phone_display = isset($faq_business['phone_display']) ? (string) $faq_business['phone_display'] : '0944 857 999';
$faq_phone_href = isset($faq_business['phone_href']) ? (string) $faq_business['phone_href'] : 'tel:0944857999';
$faq_zalo_url = isset($faq_business['zalo_url']) ? (string) $faq_business['zalo_url'] : 'https://zalo.me/0944857999';
?>
<main id="main-content">
  <div class="container">
    <article class="page-section single-article page-shell">
      <ul class="breadcrumb">
        <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
        <li>Câu hỏi thường gặp</li>
      </ul>
      <h1 class="page-title">Câu hỏi thường gặp</h1>
      <p class="text-muted">Tổng hợp thắc mắc phổ biến khi chọn sơn và đặt hàng tại Đại lý Sơn Phát Tấn.</p>
      <div class="cta-compact">
        <div>
          <strong>Cần tư vấn gấp?</strong>
          <p class="text-muted">Gọi số tư vấn hoặc Zalo để được kỹ thuật hỗ trợ ngay.</p>
        </div>
        <div class="cta-compact__actions">
          <a class="btn btn-primary btn-sm" href="<?php echo esc_url($faq_phone_href); ?>">Gọi tư vấn</a>
          <a class="btn btn-outline btn-sm" href="<?php echo esc_url($faq_zalo_url); ?>" target="_blank" rel="noopener">Zalo kỹ thuật</a>
        </div>
      </div>
      <div class="search-scope" aria-label="Mục nhanh câu hỏi">
        <a class="chip" href="#faq-lien-he">Liên hệ tư vấn</a>
        <a class="chip" href="#faq-giao-hang">Nhận hàng</a>
        <a class="chip" href="#faq-hoa-don">Hóa đơn CO/CQ</a>
        <a class="chip" href="#faq-doi-tra">Đổi trả</a>
        <a class="chip" href="#faq-gia-dai-ly">Giá đại lý</a>
      </div>

      <?php
      if (function_exists('my_theme_render_service_compass')) {
          my_theme_render_service_compass([
              'class' => 'service-compass--faq',
              'eyebrow' => 'Đọc FAQ rồi đi tiếp',
              'title' => 'Nếu câu trả lời đã đủ rõ, bạn có thể đi tiếp theo 3 đường này',
              'subtitle' => 'Sang kho sản phẩm nếu đã có mã. Mở nhóm giải pháp nếu đang chọn theo bề mặt. Hoặc gửi nhu cầu thực tế để đội kỹ thuật hướng bạn vào đúng nhóm vật tư.',
          ]);
      }
      ?>

      <div class="content-block">
        <h3 id="faq-lien-he">1. Tôi cần tư vấn màu và diện tích, liên hệ ai?</h3>
        <p>Gọi số tư vấn <a href="<?php echo esc_url($faq_phone_href); ?>"><?php echo esc_html($faq_phone_display); ?></a> hoặc nhắn Zalo <a href="<?php echo esc_url($faq_zalo_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($faq_phone_display); ?></a>. Kỹ thuật sẽ hỗ trợ đo bóc khối lượng, đề xuất hệ sơn trong 15 phút giờ hành chính.</p>

        <h3 id="faq-giao-hang">2. Bao lâu nhận được hàng?</h3>
        <p>Trong nội thành TP.HCM: 24 giờ. Các tỉnh lân cận: 24–48 giờ. Hàng pha màu gấp vui lòng báo trước để ưu tiên.</p>

        <div class="cta-inline content-block__cta-inline">
          <div class="cta-inline__content">
            <div>
              <h3>Nhận báo giá theo m²</h3>
              <p class="text-muted">Gửi diện tích và bề mặt, chúng tôi đề xuất định mức phù hợp.</p>
            </div>
            <div class="cta-inline__actions">
              <a class="btn btn-accent" href="<?php echo esc_url(home_url('/lien-he')); ?>">Gửi yêu cầu báo giá</a>
              <a class="btn btn-outline" href="<?php echo esc_url(function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop')); ?>">Xem bảng giá</a>
            </div>
          </div>
        </div>

        <h3 id="faq-hoa-don">3. Có xuất hóa đơn VAT và chứng nhận CO/CQ?</h3>
        <p>Có. Vui lòng cung cấp thông tin công ty khi đặt hàng; chúng tôi giao kèm hóa đơn và chứng từ hãng.</p>

        <h3 id="faq-doi-tra">4. Điều kiện đổi trả như thế nào?</h3>
        <p>Không đổi trả với sơn đã pha màu. Các trường hợp giao sai mã, lỗi bao bì hoặc lỗi kỹ thuật sẽ được đổi trong 48 giờ (xem chi tiết tại trang <a href="<?php echo esc_url(home_url('/chinh-sach-doi-tra')); ?>">Chính sách đổi trả</a>).</p>

        <h3 id="faq-gia-dai-ly">5. Tôi muốn lấy giá đại lý số lượng?</h3>
        <p>Liên hệ số tư vấn để nhận bảng chiết khấu theo thương hiệu và dung tích. Đơn số lượng sẽ được giao bằng xe tải/ cẩu nếu cần.</p>
      </div>

      <?php
      if (function_exists('my_theme_render_lead_capture_form')) {
          echo my_theme_render_lead_capture_form([
              'source' => 'faq-page',
              'title' => 'FAQ chưa giải quyết hết trường hợp của bạn?',
              'subtitle' => 'Gửi bề mặt, diện tích, mã đang cân nhắc hoặc yêu cầu giao hàng để đội kỹ thuật phản hồi theo tình huống thực tế thay vì trả lời chung.',
              'button' => 'Gửi câu hỏi thực tế',
          ]);
      }
      ?>

      <div class="cta">
        <div>
          <h3>Chưa thấy câu trả lời?</h3>
          <p>Gọi hoặc nhắn Zalo, chúng tôi phản hồi ngay trong giờ làm việc.</p>
        </div>
        <div>
          <a class="btn btn-primary" href="<?php echo esc_url($faq_phone_href); ?>">Gọi hỗ trợ</a>
          <a class="btn btn-outline" href="<?php echo esc_url($faq_zalo_url); ?>" target="_blank" rel="noopener">Zalo hỗ trợ</a>
          <a class="btn btn-accent" href="<?php echo esc_url(home_url('/lien-he')); ?>">Gửi câu hỏi nhanh</a>
        </div>
      </div>
    </article>
  </div>
</main>
<?php get_footer();
