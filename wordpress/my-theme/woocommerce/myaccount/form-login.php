<?php
/**
 * Custom login/register layout for My Account.
 *
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

do_action( 'woocommerce_before_customer_login_form' );

$account_url   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
$show_login    = isset( $_GET['login'] ) && '1' === $_GET['login'];
$show_register = isset( $_GET['register'] ) && '1' === $_GET['register'];

if ( 'yes' !== get_option( 'woocommerce_enable_myaccount_registration' ) ) {
  $show_login    = true;
  $show_register = false;
}

if ( ! $show_login && ! $show_register ) {
  $show_register = true;
}
?>

<div class="account-auth page-section">
  <?php if ( $show_login ) : ?>
    <div class="account-auth__header">
      <h2 class="section-title">Đăng nhập</h2>
      <?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>
        <a class="btn btn-outline btn-sm" href="<?php echo esc_url( add_query_arg( 'register', '1', $account_url ) ); ?>">Đăng ký</a>
      <?php endif; ?>
    </div>

    <form class="woocommerce-form woocommerce-form-login login" method="post">
      <?php do_action( 'woocommerce_login_form_start' ); ?>

      <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="username">Tên đăng nhập hoặc email&nbsp;<span class="required">*</span></label>
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" />
      </p>
      <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="password">Mật khẩu&nbsp;<span class="required">*</span></label>
        <input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" />
      </p>

      <?php do_action( 'woocommerce_login_form' ); ?>

      <p class="form-row">
        <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
          <input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" />
          <span>Ghi nhớ mật khẩu</span>
        </label>
        <?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
        <button type="submit" class="woocommerce-button button woocommerce-form-login__submit<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="login" value="login">Đăng nhập</button>
      </p>
      <p class="woocommerce-LostPassword lost_password">
        <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>">Quên mật khẩu?</a>
      </p>

      <?php do_action( 'woocommerce_login_form_end' ); ?>
    </form>
  <?php endif; ?>

  <?php if ( $show_register ) : ?>
    <div class="account-auth__header" style="margin-top: <?php echo $show_login ? '20px' : '0'; ?>;">
      <h2 class="section-title">Đăng ký</h2>
      <a class="btn btn-outline btn-sm" href="<?php echo esc_url( add_query_arg( 'login', '1', $account_url ) ); ?>">Đăng nhập</a>
    </div>

    <form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?>>
      <?php do_action( 'woocommerce_register_form_start' ); ?>

      <?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
          <label for="reg_username">Tên đăng nhập&nbsp;<span class="required">*</span></label>
          <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" />
        </p>
      <?php endif; ?>

      <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="reg_full_name">Họ và tên&nbsp;<span class="required">*</span></label>
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_full_name" id="reg_full_name" autocomplete="name" required value="<?php echo ( ! empty( $_POST['account_full_name'] ) ) ? esc_attr( wp_unslash( $_POST['account_full_name'] ) ) : ''; ?>" />
      </p>
      <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="reg_phone">Số điện thoại&nbsp;<span class="required">*</span></label>
        <input type="tel" class="woocommerce-Input woocommerce-Input--text input-text" name="account_phone" id="reg_phone" autocomplete="tel" required value="<?php echo ( ! empty( $_POST['account_phone'] ) ) ? esc_attr( wp_unslash( $_POST['account_phone'] ) ) : ''; ?>" />
      </p>
      <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="reg_address">Địa chỉ nhận hàng&nbsp;<span class="required">*</span></label>
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_address" id="reg_address" autocomplete="street-address" required value="<?php echo ( ! empty( $_POST['account_address'] ) ) ? esc_attr( wp_unslash( $_POST['account_address'] ) ) : ''; ?>" />
      </p>

      <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="reg_email">Email (không bắt buộc)</label>
        <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" />
        <small>Không có email vẫn đăng ký được. Hệ thống sẽ tạo email nội bộ để quản lý tài khoản.</small>
      </p>

      <?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
          <label for="reg_password">Mật khẩu&nbsp;<span class="required">*</span></label>
        <input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" required />
      </p>
      <?php else : ?>
        <p>Vui lòng nhập email để nhận liên kết tạo mật khẩu.</p>
      <?php endif; ?>

      <?php do_action( 'woocommerce_register_form' ); ?>

      <p class="woocommerce-form-row form-row">
        <?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
        <button type="submit" class="woocommerce-Button woocommerce-button button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?> woocommerce-form-register__submit" name="register" value="register">Đăng ký</button>
      </p>

      <?php do_action( 'woocommerce_register_form_end' ); ?>
    </form>
  <?php endif; ?>
</div>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
