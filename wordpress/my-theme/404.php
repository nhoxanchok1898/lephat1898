<?php
/**
 * 404 template aligned with refined design system.
 */
get_header();

$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop');
?>
<main id="main-content">
  <div class="container home-page">
    <section class="page-section page-shell not-found-shell">
      <nav class="breadcrumb-nav" aria-label="Đường dẫn">
        <ol class="breadcrumb">
          <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
          <li>Không tìm thấy trang</li>
        </ol>
      </nav>

      <div class="not-found-shell__code">404</div>
      <h1 class="page-title">Trang bạn tìm không tồn tại</h1>
      <p class="section-sub">Liên kết có thể đã thay đổi hoặc nội dung đã được chuyển sang khu vực khác.</p>

      <form class="search-inline-form search-inline-form--404" role="search" method="get" action="<?php echo esc_url($shop_url); ?>">
        <label class="visually-hidden" for="search-404-q">Tìm trong kho sản phẩm</label>
        <input id="search-404-q" type="search" name="q" placeholder="Tìm sơn chống thấm, sơn nội thất..." />
        <button class="btn btn-primary btn-sm" type="submit">Tìm sản phẩm</button>
      </form>

      <div class="not-found-shell__actions">
        <a class="btn btn-primary" href="<?php echo esc_url(home_url('/')); ?>">Về trang chủ</a>
        <a class="btn btn-outline" href="<?php echo esc_url($shop_url); ?>">Mở cửa hàng</a>
        <a class="btn btn-accent" href="<?php echo esc_url(home_url('/lien-he')); ?>">Liên hệ tư vấn</a>
      </div>

      <div class="not-found-shell__links" aria-label="Liên kết gợi ý">
        <a href="<?php echo esc_url(home_url('/huong-dan-mua-hang')); ?>">Hướng dẫn mua hàng</a>
        <a href="<?php echo esc_url(home_url('/faq')); ?>">Câu hỏi thường gặp</a>
        <a href="<?php echo esc_url(home_url('/van-chuyen-giao-hang')); ?>">Vận chuyển & giao hàng</a>
        <a href="<?php echo esc_url(home_url('/chinh-sach-doi-tra')); ?>">Chính sách đổi trả</a>
      </div>
    </section>

    <?php
    if (function_exists('my_theme_render_recently_viewed_products')) {
        my_theme_render_recently_viewed_products([
            'title' => 'Quay lại các sản phẩm bạn vừa xem',
            'aria_label' => 'Quay lại các sản phẩm bạn vừa xem',
            'class' => 'related-products-block--recently-viewed related-products-block--404',
        ]);
    }
    ?>
  </div>
</main>
<?php get_footer(); ?>
