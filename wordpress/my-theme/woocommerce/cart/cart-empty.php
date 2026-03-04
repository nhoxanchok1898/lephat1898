<?php
/**
 * Empty cart template.
 */

defined('ABSPATH') || exit;

$shop_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
$solutions_url = home_url('/giai-phap');
$contact_url = home_url('/lien-he');
?>

<section class="empty-state empty-state--cart" aria-label="Giỏ hàng đang trống">
  <h2>Giỏ hàng của bạn đang trống</h2>
  <p>Bạn có thể quay lại kho sản phẩm, đi theo nhóm giải pháp hoặc gửi nhu cầu để đội kỹ thuật gợi ý nhanh nhóm vật tư phù hợp.</p>
  <div class="empty-state__actions">
    <a class="btn btn-primary" href="<?php echo esc_url($shop_url); ?>">Mở cửa hàng</a>
    <a class="btn btn-outline" href="<?php echo esc_url($solutions_url); ?>">Xem giải pháp</a>
    <a class="btn btn-accent" href="<?php echo esc_url($contact_url); ?>">Gửi yêu cầu báo giá</a>
  </div>
</section>

<?php if (function_exists('my_theme_render_commerce_support')) : ?>
  <?php my_theme_render_commerce_support('cart'); ?>
<?php endif; ?>

<?php if (function_exists('my_theme_render_recently_viewed_products')) : ?>
  <?php my_theme_render_recently_viewed_products([
    'title' => 'Quay lại các sản phẩm bạn vừa xem',
    'aria_label' => 'Sản phẩm vừa xem gần đây',
    'class' => 'related-products-block--recently-viewed related-products-block--cart',
  ]); ?>
<?php endif; ?>
