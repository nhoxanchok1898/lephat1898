<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<div id="top"></div>
<header class="site-header">
  <div class="header-top">
    <div class="container header-top__inner">
      <div class="header-top__commit">
        <span class="badge-pill">Đại lý chính hãng</span>
        <span class="badge-pill">Hàng mới 100%</span>
        <span class="badge-pill">Hỗ trợ kỹ thuật</span>
      </div>
      <div class="header-top__contact">
        <a class="hotline" href="tel:0944857999">Số báo giá 0944 857 999</a>
        <a class="btn btn-outline btn-sm" href="https://zalo.me/0944857999" target="_blank" rel="noopener">Zalo tư vấn</a>
        <a class="btn btn-primary btn-sm" href="<?php echo esc_url(home_url('/lien-he')); ?>">Nhận báo giá</a>
      </div>
    </div>
  </div>
  <div class="container header-bar">
    <a class="brand" href="<?php echo esc_url(home_url('/')); ?>">
      <span class="brand-mark">
        <img src="<?php echo esc_url(get_theme_file_uri('assets/logo-phat-tan.svg')); ?>" alt="Logo Phát Tấn">
      </span>
      <span class="brand-name">Đại Lý Sơn Phát Tấn</span>
    </a>
    <nav class="main-nav" id="primary-menu" aria-label="<?php esc_attr_e('Menu chính','my-custom-theme'); ?>">
      <button class="menu-toggle" type="button" aria-controls="primary-menu-list" aria-expanded="false">
        <span class="menu-toggle__bars" aria-hidden="true">
          <span></span>
          <span></span>
          <span></span>
        </span>
        <span class="menu-toggle__label">Menu</span>
      </button>
      <?php
      wp_nav_menu([
        'theme_location' => 'primary',
        'container' => false,
        'menu_class' => 'menu main-menu',
        'items_wrap' => '<ul id="primary-menu-list" class="menu main-menu">%3$s</ul>',
        'fallback_cb' => 'my_theme_fallback_menu',
      ]);
      ?>
    </nav>
    <div class="header-actions">
      <div class="header-actions__primary">
        <a class="btn btn-outline btn-sm" href="<?php echo esc_url(home_url('/gioi-thieu')); ?>">Giới thiệu</a>
        <a class="btn btn-outline btn-sm" href="<?php echo esc_url(home_url('/gia-tho')); ?>">Giá thợ</a>
        <a class="btn btn-outline btn-sm" href="tel:0944857999">Gọi đặt hàng</a>
        <?php if (is_user_logged_in()): ?>
          <a class="btn-ghost" href="<?php echo esc_url(get_permalink(get_option('woocommerce_myaccount_page_id'))); ?>">Tài khoản</a>
          <a class="btn-ghost" href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">Đăng xuất</a>
        <?php else: ?>
          <?php $account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : wp_login_url(); ?>
          <a class="btn-ghost" href="<?php echo esc_url(add_query_arg('login', '1', $account_url)); ?>">Đăng nhập</a>
          <a class="btn btn-accent btn-sm" href="<?php echo esc_url(add_query_arg('register', '1', $account_url)); ?>">Đăng ký</a>
        <?php endif; ?>
      </div>
      <div class="header-actions__cart">
        <a class="btn btn-primary btn-sm" href="<?php echo esc_url(home_url('/lien-he')); ?>">Liên hệ</a>
        <a class="btn btn-primary btn-sm" href="<?php echo esc_url(wc_get_cart_url()); ?>">Giỏ hàng</a>
        <a class="btn btn-primary btn-sm" href="<?php echo esc_url(wc_get_checkout_url()); ?>">Thanh toán</a>
      </div>
    </div>
  </div>
</header>
<main id="main-content" class="container">
