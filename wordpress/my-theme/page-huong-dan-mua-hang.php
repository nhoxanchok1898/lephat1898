<?php
/** Template Name: Hướng dẫn mua hàng */
get_header();
?>
<main id="main-content">
  <div class="container">
    <article class="page-section single-article">
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
          <a class="btn btn-primary btn-sm" href="tel:0944857999">Gọi đặt hàng</a>
          <a class="btn btn-outline btn-sm" href="https://zalo.me/0944857999" target="_blank" rel="noopener">Zalo báo giá</a>
        </div>
      </div>

      <div class="page-section" style="margin-bottom:14px;">
        <h3>Bước 1: Gửi nhu cầu</h3>
        <p>Liên hệ số tư vấn <a href="tel:0944857999">0944 857 999</a> hoặc nhắn Zalo <a href="https://zalo.me/0944857999" target="_blank" rel="noopener">0944 857 999</a>. Cung cấp: loại công trình, diện tích, yêu cầu màu sắc, thời gian giao.</p>

        <h3>Bước 2: Nhận báo giá & phối màu</h3>
        <p>Kỹ thuật đề xuất hệ sơn (lót, phủ, chống thấm) và bảng màu phù hợp. Báo giá gửi trong 30 phút kèm chiết khấu nếu lấy số lượng.</p>

        <div class="cta-inline" style="margin:14px 0;">
          <div class="cta-inline__content">
            <div>
              <h3>Ước tính vật tư nhanh theo m²</h3>
              <p class="text-muted">Gửi diện tích và bề mặt, chúng tôi tính định mức phù hợp.</p>
            </div>
            <div class="cta-inline__actions">
              <a class="btn btn-accent" href="<?php echo esc_url(home_url('/lien-he')); ?>">Gửi diện tích</a>
              <a class="btn btn-outline" href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>">Xem bảng giá</a>
            </div>
          </div>
        </div>

        <h3>Bước 3: Xác nhận & thanh toán</h3>
        <p>Đặt cọc 0–20% tuỳ đơn (hàng pha màu cần cọc). Thanh toán chuyển khoản hoặc tiền mặt khi nhận hàng.</p>

        <h3>Bước 4: Giao hàng & hỗ trợ thi công</h3>
        <p>Giao 24–48h khu vực TP.HCM và lân cận. Hỗ trợ hướng dẫn quy trình thi công, vệ sinh bề mặt, định mức lớp lót/phủ.</p>

        <h3>Thông tin thanh toán</h3>
        <p>Thông tin chuyển khoản sẽ được gửi qua Zalo hoặc điện thoại sau khi xác nhận đơn hàng.</p>
      </div>

      <div class="cta">
        <div>
          <h3>Đặt hàng ngay hôm nay</h3>
          <p>Chúng tôi ưu tiên giao trong ngày với đơn nội thành và hỗ trợ phối màu miễn phí.</p>
        </div>
        <div>
          <a class="btn btn-primary" href="tel:0944857999">Gọi báo giá</a>
          <a class="btn btn-outline" href="https://zalo.me/0944857999" target="_blank" rel="noopener">Zalo đặt hàng</a>
          <a class="btn btn-accent" href="<?php echo esc_url(home_url('/lien-he')); ?>">Gửi yêu cầu nhanh</a>
        </div>
      </div>
    </article>
  </div>
</main>
<?php get_footer();
