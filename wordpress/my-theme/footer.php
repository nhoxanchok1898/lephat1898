<footer>
  <div class="container footer-grid">
    <div>
      <div class="footer-heading">Đại lý Sơn Phát Tấn</div>
      <p>Đại lý sơn chính hãng, báo giá minh bạch, giao nhanh và hỗ trợ kỹ thuật theo công trình.</p>
    </div>

    <div>
      <div class="footer-heading">Điều hướng nhanh</div>
      <div class="footer-links">
        <a href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>">Cửa hàng</a><br>
        <a href="<?php echo esc_url(wc_get_cart_url()); ?>">Giỏ hàng</a><br>
        <a href="<?php echo esc_url(wc_get_checkout_url()); ?>">Thanh toán</a><br>
        <a href="<?php echo esc_url(get_permalink(wc_get_page_id('myaccount'))); ?>">Tài khoản</a>
      </div>
    </div>

    <div>
      <div class="footer-heading">Chính sách</div>
      <div class="footer-links">
        <a href="<?php echo esc_url(home_url('/huong-dan-mua-hang')); ?>">Hướng dẫn mua hàng</a><br>
        <a href="<?php echo esc_url(home_url('/van-chuyen-giao-hang')); ?>">Vận chuyển và giao hàng</a><br>
        <a href="<?php echo esc_url(home_url('/chinh-sach-doi-tra')); ?>">Chính sách đổi trả</a><br>
        <a href="<?php echo esc_url(home_url('/faq')); ?>">Câu hỏi thường gặp</a>
      </div>
    </div>

    <div>
      <div class="footer-heading">Liên hệ</div>
      <p>Điện thoại: <a href="tel:0944857999">0944 857 999</a></p>
      <p>Zalo: <a href="https://zalo.me/0944857999" target="_blank" rel="noopener">Tư vấn kỹ thuật</a></p>
      <p>Địa chỉ: 392 TL10, Bình Trị Đông, Bình Tân, TP.HCM</p>
    </div>
  </div>

  <div class="footer-copy">&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>.</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
