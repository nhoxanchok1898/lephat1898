<?php
/* Template Name: Giá thợ / giá công trình */
get_header();
$trade_business = function_exists('my_theme_get_business_profile') ? my_theme_get_business_profile() : [];
$trade_phone_href = isset($trade_business['phone_href']) ? (string) $trade_business['phone_href'] : 'tel:0944857999';
$trade_zalo_url = isset($trade_business['zalo_url']) ? (string) $trade_business['zalo_url'] : 'https://zalo.me/0944857999';
?>
<main id="main-content">
  <div class="container">
    <article class="page-section single-article page-shell">
      <ul class="breadcrumb">
        <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
        <li>Giá thợ / công trình</li>
      </ul>

      <h1 class="page-title">Giá thợ / giá công trình</h1>
      <p class="text-muted">Ưu đãi riêng cho thợ sơn, đội thi công và công trình số lượng lớn.</p>

      <div class="trust-row">
        <span class="trust-item">Giá đại lý</span>
        <span class="trust-item">Hàng mới 100%</span>
        <span class="trust-item">Báo giá nhanh</span>
      </div>

      <div class="info-grid">
        <div class="info-card">
          <h3>Ưu đãi cho thợ & công trình</h3>
          <p>Chiết khấu theo khối lượng, hỗ trợ giao theo tiến độ và tư vấn hệ sơn tối ưu chi phí.</p>
        </div>
        <div class="info-card">
          <h3>Điều kiện áp dụng</h3>
          <p>Cung cấp mã sơn, diện tích và thời gian thi công dự kiến để nhận báo giá nhanh.</p>
        </div>
        <div class="info-card">
          <h3>Cam kết giá đại lý</h3>
          <p>Giá rõ ràng theo thương hiệu và dung tích, có hỗ trợ đổi trả theo chính sách.</p>
        </div>
      </div>

      <div class="content-block">
        <h3>Thông tin cần gửi để báo giá nhanh</h3>
        <ul class="list-plain">
          <li>Mã sơn hoặc nhóm sản phẩm cần lấy.</li>
          <li>Diện tích thi công và số lớp dự kiến.</li>
          <li>Địa điểm giao hàng và tiến độ công trình.</li>
        </ul>
      </div>

      <?php get_template_part('template-parts/home', 'cta-inline'); ?>

      <div class="cta">
        <div>
          <h3>Gọi là có giá</h3>
          <p>Gửi nhu cầu và số lượng, đại lý sẽ báo giá trong ngày.</p>
        </div>
        <div>
          <a class="btn btn-primary" href="<?php echo esc_url($trade_phone_href); ?>">Gọi báo giá</a>
          <a class="btn btn-outline" href="<?php echo esc_url($trade_zalo_url); ?>" target="_blank" rel="noopener">Zalo báo giá</a>
          <a class="btn btn-accent" href="<?php echo esc_url(home_url('/lien-he')); ?>">Gửi yêu cầu</a>
        </div>
      </div>
    </article>
  </div>
</main>
<?php get_footer(); ?>
