<?php
/** Template Name: Liên hệ */
get_header();
?>
<main id="main-content">
  <div class="container">
    <article class="page-section single-article">
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
          <a class="btn btn-primary btn-sm" href="tel:0944857999">Gọi báo giá</a>
          <a class="btn btn-outline btn-sm" href="https://zalo.me/0944857999" target="_blank" rel="noopener">Zalo tư vấn</a>
        </div>
      </div>
      <div class="page-section" style="margin-bottom:12px;">
        <p><strong>Giám đốc:</strong> Trần Thị Ngọc Thúy</p>
        <p><strong>Điện thoại/Zalo:</strong> <a href="tel:0944857999">0944 857 999</a></p>
        <p><strong>Thư điện tử:</strong> <a href="mailto:info@paintstore.vn">info@paintstore.vn</a></p>
        <p><strong>Địa chỉ:</strong> 392 TL10, Bình Trị Đông, Bình Tân, TP.HCM</p>
        <p><a class="btn btn-primary" href="https://www.google.com/maps/place/392+TL10,+B%C3%ACnh+Tr%E1%BB%8B+%C4%90%C3%B4ng,+B%C3%ACnh+T%C3%A2n,+Th%C3%A0nh+ph%E1%BB%91+H%E1%BB%93+Ch%C3%AD+Minh,+Vi%E1%BB%87t+Nam/@10.7569515,106.6195492,17z/data=!3m1!4b1!4m6!3m5!1s0x31752c2ec14b688b:0xe43d34f4d14c3f98!8m2!3d10.7569515!4d106.6221241!16s%2Fg%2F11rp3djv_1?entry=ttu" target="_blank" rel="noopener">Mở bản đồ Google</a>
        <a class="btn btn-outline" href="https://www.facebook.com/thuy.ngoc.9250595" target="_blank" rel="noopener">Trang Facebook</a></p>
      </div>
      <div class="cta-inline">
        <div class="cta-inline__content">
          <div>
            <h3>Nhận tư vấn hệ sơn theo hạng mục</h3>
            <p class="text-muted">Gửi diện tích và yêu cầu, chúng tôi đề xuất vật tư trong 15 phút.</p>
          </div>
          <div class="cta-inline__actions">
            <a class="btn btn-accent" href="<?php echo esc_url(home_url('/huong-dan-mua-hang')); ?>">Xem cách đặt hàng</a>
            <a class="btn btn-outline" href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>">Xem bảng giá</a>
          </div>
        </div>
      </div>
      <div class="cta">
        <div>
          <h3>Đặt lịch khảo sát hoặc lấy báo giá nhanh</h3>
          <p>Trong giờ làm việc, phản hồi trong 15 phút. Ngoài giờ, chúng tôi sẽ gọi lại vào buổi sáng hôm sau.</p>
        </div>
        <div>
          <a class="btn btn-primary" href="tel:0944857999">Gọi đặt hàng</a>
          <a class="btn btn-outline" href="https://zalo.me/0944857999" target="_blank" rel="noopener">Zalo kỹ thuật</a>
          <a class="btn btn-accent" href="<?php echo esc_url(home_url('/huong-dan-mua-hang')); ?>">Đặt lịch khảo sát</a>
        </div>
      </div>
    </article>
  </div>
</main>
<?php get_footer();
