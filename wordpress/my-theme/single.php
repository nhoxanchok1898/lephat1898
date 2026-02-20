<?php
/** Single template aligned with old layout */
get_header();
?>
<main id="main-content">
  <div class="container">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
      <article class="page-section single-article">
        <ul class="breadcrumb">
          <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
          <li><?php the_title(); ?></li>
        </ul>
        <h1 class="page-title"><?php the_title(); ?></h1>
        <div class="post-meta"><?php echo get_the_date(); ?> · <?php the_author(); ?></div>
        <div class="cta-compact">
          <div>
            <strong>Đang cần tư vấn chọn sơn?</strong>
            <p class="text-muted">Kỹ thuật hỗ trợ miễn phí theo diện tích và bề mặt.</p>
          </div>
          <div class="cta-compact__actions">
            <a class="btn btn-primary btn-sm" href="tel:0944857999">Gọi tư vấn</a>
            <a class="btn btn-outline btn-sm" href="https://zalo.me/0944857999" target="_blank" rel="noopener">Zalo kỹ thuật</a>
          </div>
        </div>
        <div class="entry-content"><?php the_content(); ?></div>
        <?php get_template_part('template-parts/home', 'cta-inline'); ?>
        <div class="info-grid">
          <div class="info-card">
            <h3>Phù hợp cho</h3>
            <ul class="list-plain">
              <li>Nhà ở, căn hộ, văn phòng, công trình dân dụng.</li>
              <li>Khách cần tư vấn chọn hệ sơn theo bề mặt.</li>
            </ul>
          </div>
          <div class="info-card">
            <h3>Ưu điểm nổi bật</h3>
            <ul class="list-plain">
              <li>Đại lý chính hãng, hàng mới, có chứng từ.</li>
              <li>Hỗ trợ kỹ thuật, định mức m² rõ ràng.</li>
            </ul>
          </div>
          <div class="info-card">
            <h3>Gợi ý sử dụng</h3>
            <ul class="list-plain">
              <li>Xem bảng giá và liên hệ tư vấn trước khi thi công.</li>
              <li>Gửi diện tích để nhận định mức và hệ sơn phù hợp.</li>
            </ul>
          </div>
        </div>
        <div class="cta">
          <div>
            <h3>Nhận báo giá theo m²</h3>
            <p>Gửi thông tin công trình, chúng tôi phản hồi trong 15 phút.</p>
          </div>
          <div>
            <a class="btn btn-primary" href="tel:0944857999">Gọi báo giá</a>
            <a class="btn btn-outline" href="https://zalo.me/0944857999" target="_blank" rel="noopener">Zalo tư vấn</a>
            <a class="btn btn-accent" href="<?php echo esc_url(home_url('/lien-he')); ?>">Gửi yêu cầu</a>
          </div>
        </div>
      </article>
    <?php endwhile; endif; ?>
  </div>
</main>
<?php get_footer();
