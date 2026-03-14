<?php
$footer_shop_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
$footer_blog_url = trailingslashit(home_url('/blog'));
$footer_cart_url = function_exists('my_theme_get_cart_url_safe') ? my_theme_get_cart_url_safe() : $footer_shop_url;
$footer_checkout_url = function_exists('my_theme_get_checkout_url_safe') ? my_theme_get_checkout_url_safe() : home_url('/thanh-toan');
$footer_account_url = function_exists('my_theme_get_account_url') ? my_theme_get_account_url() : wp_login_url();
$footer_snapshot = function_exists('my_theme_get_store_snapshot') ? my_theme_get_store_snapshot() : [];
$footer_phone_display = isset($footer_snapshot['phone_display']) ? (string) $footer_snapshot['phone_display'] : '0944 857 999';
$footer_phone_href = isset($footer_snapshot['phone_href']) ? (string) $footer_snapshot['phone_href'] : 'tel:0944857999';
$footer_email = isset($footer_snapshot['email']) ? (string) $footer_snapshot['email'] : 'lephat1898@gmail.com';
$footer_email_href = isset($footer_snapshot['email_href']) ? (string) $footer_snapshot['email_href'] : 'mailto:lephat1898@gmail.com';
$footer_zalo_url = isset($footer_snapshot['zalo_url']) ? (string) $footer_snapshot['zalo_url'] : 'https://zalo.me/0944857999';
$footer_hours = isset($footer_snapshot['hours_display']) ? (string) $footer_snapshot['hours_display'] : 'Thứ 2 - Thứ 7: 7:30 - 18:00';
$footer_service_areas = isset($footer_snapshot['service_areas_display']) ? (string) $footer_snapshot['service_areas_display'] : 'TP.HCM, Bình Dương, Đồng Nai';
$footer_address = isset($footer_snapshot['address_full']) ? (string) $footer_snapshot['address_full'] : '392 TL10, Bình Trị Đông, Bình Tân, TP.HCM';
$footer_primary_links = [
  ['label' => 'Cửa hàng', 'url' => $footer_shop_url],
  ['label' => 'Giải pháp', 'url' => home_url('/giai-phap')],
  ['label' => 'Blog', 'url' => $footer_blog_url],
  ['label' => 'Hướng dẫn mua hàng', 'url' => home_url('/huong-dan-mua-hang')],
  ['label' => 'FAQ', 'url' => home_url('/faq')],
  ['label' => 'Liên hệ', 'url' => home_url('/lien-he')],
];
$footer_cart_count = 0;
if (function_exists('WC') && WC()->cart) {
  $footer_cart_count = max(0, (int) WC()->cart->get_cart_contents_count());
}
$footer_mobile_middle_label = $footer_cart_count > 0 ? 'Giỏ hàng' : 'Zalo';
$footer_mobile_middle_url = $footer_cart_count > 0 ? $footer_cart_url : $footer_zalo_url;
$footer_mobile_middle_target = $footer_cart_count > 0 ? '' : '_blank';
$footer_mobile_middle_rel = $footer_cart_count > 0 ? '' : 'noopener';
?>
<footer>
  <div class="container footer-cta-band">
    <div class="footer-cta-band__content">
      <p class="footer-cta-band__eyebrow">Hỗ trợ báo giá công trình</p>
      <h2>Một đầu mối để chốt mã sơn, giá và lịch giao</h2>
      <p class="footer-cta-band__lead">Gửi mã, diện tích hoặc ảnh bề mặt để được tư vấn đúng hệ vật tư và nhận báo giá nhanh trong khung giờ làm việc.</p>
      <div class="footer-cta-band__meta" aria-label="Cam kết dịch vụ">
        <span><?php echo esc_html($footer_hours); ?></span>
        <span><?php echo esc_html($footer_service_areas); ?></span>
        <span>Giao hàng nhanh nội thành</span>
      </div>
    </div>
    <div class="footer-cta-band__actions">
      <a class="btn btn-primary" href="<?php echo esc_url($footer_phone_href); ?>">Gọi <?php echo esc_html($footer_phone_display); ?></a>
      <a class="btn btn-outline" href="<?php echo esc_url($footer_zalo_url); ?>" target="_blank" rel="noopener">Zalo kỹ thuật</a>
      <a class="footer-cta-band__textlink" href="<?php echo esc_url(home_url('/lien-he')); ?>">Gửi yêu cầu báo giá</a>
    </div>
  </div>

  <div class="container footer-grid footer-grid--compact">
    <div>
      <div class="footer-heading">Đại lý Sơn Phát Tấn</div>
      <p>Đại lý sơn và vật liệu hoàn thiện chính hãng cho thợ, nhà thầu và khách công trình cần chốt vật tư nhanh, đúng hệ và đúng tiến độ.</p>
      <div class="footer-meta-list">
        <span><strong>Giờ làm việc:</strong> <?php echo esc_html($footer_hours); ?></span>
        <span><strong>Phục vụ:</strong> <?php echo esc_html($footer_service_areas); ?></span>
      </div>
    </div>

    <div>
      <div class="footer-heading">Lối đi chính</div>
      <div class="footer-links footer-links--compact">
        <?php foreach ($footer_primary_links as $footer_primary_link) : ?>
          <a href="<?php echo esc_url((string) $footer_primary_link['url']); ?>"><?php echo esc_html((string) $footer_primary_link['label']); ?></a>
        <?php endforeach; ?>
      </div>
      <div class="footer-links footer-links--secondary">
        <a href="<?php echo esc_url(function_exists('my_theme_get_paint_calculator_url') ? my_theme_get_paint_calculator_url() : home_url('/tinh-son')); ?>">Tính sơn</a>
        <a href="<?php echo esc_url(home_url('/van-chuyen-giao-hang')); ?>">Vận chuyển</a>
        <a href="<?php echo esc_url($footer_cart_url); ?>">Giỏ hàng</a>
        <a href="<?php echo esc_url($footer_account_url); ?>">Tài khoản</a>
      </div>
    </div>

    <div>
      <div class="footer-heading">Liên hệ & vận hành</div>
      <div class="footer-contact-list">
        <p><strong>Điện thoại:</strong> <a href="<?php echo esc_url($footer_phone_href); ?>"><?php echo esc_html($footer_phone_display); ?></a></p>
        <p><strong>Email:</strong> <a href="<?php echo esc_url($footer_email_href); ?>"><?php echo esc_html($footer_email); ?></a></p>
        <p><strong>Zalo:</strong> <a href="<?php echo esc_url($footer_zalo_url); ?>" target="_blank" rel="noopener">Tư vấn kỹ thuật</a></p>
        <p><strong>Địa chỉ:</strong> <?php echo esc_html($footer_address); ?></p>
      </div>
      <p class="footer-contact-note">Hỗ trợ xác nhận mã sơn, quy cách và tiến độ giao hàng theo công trình.</p>
    </div>
  </div>

  <div class="footer-copy">&copy; <?php echo date('Y'); ?> <?php echo esc_html(isset($footer_snapshot['name']) ? (string) $footer_snapshot['name'] : get_bloginfo('name')); ?>.</div>
</footer>

<div class="mobile-quickbar" aria-label="Liên hệ nhanh">
  <a class="mobile-quickbar__item" href="<?php echo esc_url($footer_phone_href); ?>">
    <span>Gọi ngay</span>
  </a>
  <a
    class="mobile-quickbar__item mobile-quickbar__item--commerce"
    href="<?php echo esc_url($footer_mobile_middle_url); ?>"
    <?php echo $footer_mobile_middle_target !== '' ? 'target="' . esc_attr($footer_mobile_middle_target) . '"' : ''; ?>
    <?php echo $footer_mobile_middle_rel !== '' ? 'rel="' . esc_attr($footer_mobile_middle_rel) . '"' : ''; ?>>
    <span><?php echo esc_html($footer_mobile_middle_label); ?></span>
    <?php if ($footer_cart_count > 0) : ?><span class="mobile-quickbar__count"><?php echo esc_html((string) $footer_cart_count); ?></span><?php endif; ?>
  </a>
  <a class="mobile-quickbar__item mobile-quickbar__item--primary" href="<?php echo esc_url($footer_shop_url); ?>">
    <span>Cửa hàng</span>
  </a>
</div>
<?php wp_footer(); ?>
</body>
</html>
