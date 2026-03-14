<?php
/**
 * Empty cart template.
 */

defined('ABSPATH') || exit;

$shop_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
?>

<section class="empty-state empty-state--cart" aria-label="Giỏ hàng đang trống">
  <h2>Giỏ hàng của bạn đang trống</h2>
  <p>Quay lại kho sản phẩm để chọn mã phù hợp và thêm vào giỏ hàng.</p>
  <div class="empty-state__actions">
    <a class="btn btn-primary" href="<?php echo esc_url($shop_url); ?>">Mở cửa hàng</a>
  </div>
</section>
