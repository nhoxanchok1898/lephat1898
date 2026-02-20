<?php
defined('ABSPATH') || exit;

get_header();
?>
<div class="container wc-container">
  <?php
  /**
   * Hook: woocommerce_before_main_content.
   */
  do_action('woocommerce_before_main_content');
  ?>
  <div class="cta-compact product-cta-top">
    <div>
      <strong>Nhận báo giá theo m² ngay hôm nay</strong>
      <p class="text-muted">Tư vấn chọn hệ sơn đúng bề mặt, giao nhanh 24–48h.</p>
    </div>
    <div class="cta-compact__actions">
      <a class="btn btn-primary btn-sm" href="tel:0944857999">Gọi báo giá</a>
      <a class="btn btn-outline btn-sm" href="https://zalo.me/0944857999" target="_blank" rel="noopener">Zalo tư vấn</a>
      <a class="btn btn-accent btn-sm" href="<?php echo esc_url(home_url('/lien-he')); ?>">Gửi yêu cầu</a>
    </div>
  </div>
  <?php
  while (have_posts()) :
    the_post();
    wc_get_template_part('content', 'single-product');
  endwhile;
  /**
   * Hook: woocommerce_after_main_content.
   */
  do_action('woocommerce_after_main_content');
  ?>
  <div class="cta product-cta-bottom">
    <div>
      <h3>Cần chiết khấu cho thợ & công trình?</h3>
      <p>Liên hệ để nhận bảng giá đại lý và lịch giao tận nơi.</p>
    </div>
    <div>
      <a class="btn btn-primary" href="tel:0944857999">Gọi đặt hàng</a>
      <a class="btn btn-outline" href="https://zalo.me/0944857999" target="_blank" rel="noopener">Zalo báo giá</a>
      <a class="btn btn-accent" href="<?php echo esc_url(home_url('/huong-dan-mua-hang')); ?>">Xem cách mua</a>
    </div>
  </div>
</div>
<?php
get_footer();
