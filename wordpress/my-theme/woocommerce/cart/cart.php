<?php
defined('ABSPATH') || exit;

wc_print_notices();

do_action('woocommerce_before_cart'); ?>

<div class="cart-card">
  <div class="section-heading cart-page__head">
    <div>
      <h2 class="section-title">Giỏ hàng của bạn</h2>
      <p class="section-sub">Kiểm tra số lượng, dung tích và tổng tiền trước khi thanh toán.</p>
    </div>
    <div class="cart-page__actions">
      <a class="btn btn-outline btn-sm" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Tiếp tục mua hàng</a>
      <a class="btn btn-primary btn-sm" href="<?php echo esc_url(wc_get_checkout_url()); ?>">Đi tới thanh toán</a>
    </div>
  </div>

  <form class="woocommerce-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
    <table class="shop_table shop_table_responsive cart" cellspacing="0">
      <thead>
        <tr>
          <th class="product-name"><?php esc_html_e('Sản phẩm', 'woocommerce'); ?></th>
          <th class="product-subtotal"><?php esc_html_e('Tổng', 'woocommerce'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php do_action('woocommerce_before_cart_contents'); ?>
        <?php foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) :
          $_product   = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
          $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
          if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) :
            $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
            $display_name = function_exists('my_theme_get_product_display_name') ? my_theme_get_product_display_name($_product) : $_product->get_name();
            ?>
            <tr class="woocommerce-cart-form__cart-item <?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?>">
              <td class="product-name" data-title="<?php esc_attr_e('Sản phẩm', 'woocommerce'); ?>">
                <?php
                $thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key);
                echo $product_permalink ? '<a href="' . esc_url($product_permalink) . '">' . $thumbnail . '</a>' : $thumbnail;
                echo $product_permalink ? '<a href="' . esc_url($product_permalink) . '">' . esc_html($display_name) . '</a>' : esc_html($display_name);
                do_action('woocommerce_after_cart_item_name', $cart_item, $cart_item_key);
                echo wc_get_formatted_cart_item_data($cart_item); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                // Backorder notification
                if ($_product->backorders_require_notification() && $_product->is_on_backorder($cart_item['quantity'])) {
                  echo '<p class="backorder_notification">' . esc_html__('Available on backorder', 'woocommerce') . '</p>';
                }
                ?>
                <div class="quantity">
                  <?php
                  if ($_product->is_sold_individually()) {
                    $product_quantity = sprintf('1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key);
                  } else {
                    $product_quantity = woocommerce_quantity_input([
                      'input_name'  => "cart[{$cart_item_key}][qty]",
                      'input_value' => $cart_item['quantity'],
                      'max_value'   => $_product->get_max_purchase_quantity(),
                      'min_value'   => '0',
                    ], $_product, false);
                  }
                  echo apply_filters('woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                  ?>
                </div>
                <div class="remove-item">
                  <?php
                  echo apply_filters('woocommerce_cart_item_remove_link', sprintf(
                    '<a href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">%s</a>',
                    esc_url(wc_get_cart_remove_url($cart_item_key)),
                    esc_html__('Xóa', 'woocommerce'),
                    esc_attr($product_id),
                    esc_attr($_product->get_sku()),
                    esc_html__('Xóa', 'woocommerce')
                  ), $cart_item_key);
                  ?>
                </div>
              </td>

              <td class="product-subtotal" data-title="<?php esc_attr_e('Tổng', 'woocommerce'); ?>">
                <?php echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key); ?>
              </td>
            </tr>
          <?php endif; endforeach; ?>
        <?php do_action('woocommerce_cart_contents'); ?>
      </tbody>
    </table>

    <div class="actions">
      <?php if (wc_coupons_enabled()) { ?>
        <div class="coupon">
          <label for="coupon_code"><?php esc_html_e('Mã giảm giá:', 'woocommerce'); ?></label>
          <input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e('Nhập mã', 'woocommerce'); ?>" />
          <button type="submit" class="button" name="apply_coupon" value="<?php esc_attr_e('Áp dụng', 'woocommerce'); ?>"><?php esc_html_e('Áp dụng', 'woocommerce'); ?></button>
          <?php do_action('woocommerce_cart_coupon'); ?>
        </div>
      <?php } ?>

      <button type="submit" class="button" name="update_cart" value="<?php esc_attr_e('Cập nhật giỏ hàng', 'woocommerce'); ?>"><?php esc_html_e('Cập nhật giỏ hàng', 'woocommerce'); ?></button>
      <?php do_action('woocommerce_cart_actions'); ?>
      <?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
    </div>
  </form>

  <div class="cart_totals_wrapper">
    <?php woocommerce_cart_totals(); ?>
  </div>
</div>

<?php if (function_exists('my_theme_render_commerce_support')) : ?>
  <?php my_theme_render_commerce_support('cart'); ?>
<?php endif; ?>

<?php if (function_exists('my_theme_render_cart_companion_paths')) : ?>
  <?php my_theme_render_cart_companion_paths([
    'title' => 'Rà lại vật tư thường đi cùng trước khi thanh toán',
    'subtitle' => 'Nếu giỏ đang có lớp phủ, chống thấm hoặc vật tư chính, nên kiểm tra thêm các nhóm đi cùng bên dưới để tránh thiếu lớp khi giao hàng.',
  ]); ?>
<?php endif; ?>

<?php
$cart_product_ids = [];
foreach (WC()->cart->get_cart() as $cart_entry) {
  $cart_product_id = isset($cart_entry['product_id']) ? (int) $cart_entry['product_id'] : 0;
  if ($cart_product_id > 0) {
    $cart_product_ids[] = $cart_product_id;
  }
}
$cart_product_ids = array_values(array_unique($cart_product_ids));
?>
<?php if (function_exists('my_theme_render_recently_viewed_products')) : ?>
  <?php my_theme_render_recently_viewed_products([
    'title' => 'Các mã bạn vừa xem để so thêm',
    'aria_label' => 'Các mã bạn vừa xem để so thêm',
    'class' => 'related-products-block--recently-viewed related-products-block--cart',
    'exclude_ids' => $cart_product_ids,
  ]); ?>
<?php endif; ?>

<?php do_action('woocommerce_after_cart'); ?>
