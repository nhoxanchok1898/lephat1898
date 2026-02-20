  </main>
  <footer>
    <div class="container footer-grid">
      <div>
        <div class="footer-heading">Đại lý Sơn Phát Tấn</div>
        <p>Đại lý chính hãng Dulux, Jotun, Kova, Nippon, Maxilite. Hàng mới, giao nhanh 24-48h, hỗ trợ kỹ thuật tại công trình.</p>
      </div>
      <div>
        <div class="footer-heading">Liên kết</div>
        <div class="footer-links">
          <a href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>">Sản phẩm</a><br>
          <a href="<?php echo esc_url(home_url('/gioi-thieu')); ?>">Giới thiệu đại lý</a><br>
          <a href="<?php echo esc_url(home_url('/gia-tho')); ?>">Giá thợ / công trình</a><br>
          <a href="<?php echo esc_url(home_url('/lien-he')); ?>">Liên hệ</a>
        </div>
      </div>
      <div>
        <div class="footer-heading">Hỗ trợ</div>
        <div class="footer-links">
          <a href="<?php echo esc_url(home_url('/faq')); ?>">Câu hỏi thường gặp</a><br>
          <a href="<?php echo esc_url(home_url('/chinh-sach-doi-tra')); ?>">Chính sách đổi trả</a><br>
          <a href="<?php echo esc_url(home_url('/huong-dan-mua-hang')); ?>">Hướng dẫn mua hàng</a><br>
          <a href="<?php echo esc_url(home_url('/van-chuyen-giao-hang')); ?>">Vận chuyển & giao hàng</a>
        </div>
      </div>
      <div>
        <div class="footer-heading">Liên hệ</div>
        <p>📞 <a href="tel:0944857999">0944 857 999 (Zalo)</a><br>Giám đốc: Trần Thị Ngọc Thúy</p>
        <p>📧 <a href="mailto:info@paintstore.vn">info@paintstore.vn</a></p>
        <p>📍 <a href="https://www.google.com/maps/place/392+TL10,+B%C3%ACnh+Tr%E1%BB%8B+%C4%90%C3%B4ng,+B%C3%ACnh+T%C3%A2n,+Th%C3%A0nh+ph%E1%BB%91+H%E1%BB%93+Ch%C3%AD+Minh,+Vi%E1%BB%87t+Nam/@10.7569515,106.6195492,17z/data=!3m1!4b1!4m6!3m5!1s0x31752c2ec14b688b:0xe43d34f4d14c3f98!8m2!3d10.7569515!4d106.6221241!16s%2Fg%2F11rp3djv_1?entry=ttu" target="_blank" rel="noopener">392 TL10, Bình Trị Đông, Bình Tân, TP.HCM</a></p>
        <p>🌐 <a href="https://www.facebook.com/thuy.ngoc.9250595" target="_blank" rel="noopener">Trang Facebook</a></p>
      </div>
      <div>
        <div class="footer-heading">Kênh đặt hàng nhanh</div>
        <p>Gọi trực tiếp, gửi nhu cầu qua Zalo hoặc đặt lịch tư vấn.</p>
        <p><a class="btn btn-primary btn-sm" href="tel:0944857999">Gọi báo giá</a></p>
        <p><a class="btn btn-outline btn-sm" href="https://zalo.me/0944857999" target="_blank" rel="noopener">Zalo kỹ thuật</a></p>
        <p><a class="btn btn-accent btn-sm" href="<?php echo esc_url(home_url('/lien-he')); ?>">Gửi yêu cầu</a></p>
      </div>
    </div>
    <div class="footer-copy">© <?php echo date('Y'); ?> <?php bloginfo('name'); ?>.</div>
    <div class="sticky-cta" aria-label="Liên hệ nhanh">
      <a class="btn btn-primary" href="tel:0944857999">Gọi báo giá</a>
      <a class="btn btn-outline" href="https://zalo.me/0944857999" target="_blank" rel="noopener">Zalo tư vấn</a>
      <a class="btn btn-accent" href="<?php echo esc_url(home_url('/lien-he')); ?>">Đặt hàng nhanh</a>
    </div>
  </footer>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var nav = document.querySelector('.main-nav');
      var toggle = document.querySelector('.menu-toggle');
      if (!nav || !toggle) return;
      toggle.addEventListener('click', function () {
        var isOpen = nav.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      });
    });
  </script>
  <?php wp_footer(); ?>
</body>
</html>
