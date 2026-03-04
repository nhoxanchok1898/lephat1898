<?php
/* Template Name: Giới thiệu đại lý */
get_header();
$about_business = function_exists('my_theme_get_business_profile') ? my_theme_get_business_profile() : [];
$about_phone_href = isset($about_business['phone_href']) ? (string) $about_business['phone_href'] : 'tel:0944857999';
$about_zalo_url = isset($about_business['zalo_url']) ? (string) $about_business['zalo_url'] : 'https://zalo.me/0944857999';
?>
<main id="main-content">
  <div class="container">
    <article class="page-section single-article page-shell">
      <ul class="breadcrumb">
        <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
        <li>Giới thiệu đại lý</li>
      </ul>

      <h1 class="page-title">Giới thiệu Đại lý Sơn Phát Tấn</h1>
      <p class="text-muted">Đại lý sơn chính hãng phục vụ thợ và công trình tại TP.HCM và khu vực lân cận.</p>

      <div class="trust-row">
        <span class="trust-item">Đại lý chính hãng</span>
        <span class="trust-item">Hàng mới 100%</span>
        <span class="trust-item">Hỗ trợ kỹ thuật</span>
      </div>

      <div class="info-grid">
        <div class="info-card">
          <h3>Đại lý Sơn Phát Tấn là ai?</h3>
          <p>Chúng tôi là đại lý sơn uy tín chuyên cung cấp sơn nội thất, ngoại thất, chống thấm và bột bả cho thợ và công trình.</p>
        </div>
        <div class="info-card">
          <h3>Kinh nghiệm & dòng sơn chủ lực</h3>
          <p>Hơn 10 năm phục vụ, chuyên các thương hiệu Dulux, Jotun, Nippon, Kova, Maxilite với đủ hệ sơn từ dân dụng đến công trình.</p>
        </div>
        <div class="info-card">
          <h3>Khu vực phục vụ</h3>
          <p>Giao hàng nhanh tại TP.HCM, hỗ trợ giao tỉnh theo lộ trình. Đơn công trình có lịch giao theo tiến độ.</p>
        </div>
        <div class="info-card">
          <h3>Cam kết khi mua</h3>
          <p>Giá đại lý rõ ràng, hàng mới 100%, tư vấn kỹ thuật tận nơi và hỗ trợ đổi trả theo chính sách.</p>
        </div>
      </div>

      <div class="content-block">
        <h3>Quy trình tư vấn & phục vụ</h3>
        <ol class="list-numbered">
          <li>Tiếp nhận nhu cầu theo diện tích, bề mặt và tiến độ thi công.</li>
          <li>Đề xuất hệ sơn phù hợp ngân sách, gửi báo giá và định mức m².</li>
          <li>Giao hàng đúng tiến độ, hỗ trợ kỹ thuật trong suốt quá trình thi công.</li>
        </ol>
      </div>

      <?php get_template_part('template-parts/home', 'cta-inline'); ?>

      <div class="cta">
        <div>
          <h3>Cần báo giá hoặc tư vấn hệ sơn?</h3>
          <p>Gửi diện tích và bề mặt, đại lý sẽ lên báo giá nhanh cho bạn.</p>
        </div>
        <div>
          <a class="btn btn-primary" href="<?php echo esc_url($about_phone_href); ?>">Gọi tư vấn</a>
          <a class="btn btn-outline" href="<?php echo esc_url($about_zalo_url); ?>" target="_blank" rel="noopener">Zalo kỹ thuật</a>
          <a class="btn btn-accent" href="<?php echo esc_url(home_url('/lien-he')); ?>">Gửi yêu cầu</a>
        </div>
      </div>
    </article>
  </div>
</main>
<?php get_footer(); ?>
