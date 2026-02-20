<?php
/** Page template aligned with old layout */
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
        <div class="cta-compact">
          <div>
            <strong>Nhận tư vấn hệ sơn phù hợp</strong>
            <p class="text-muted">Gọi số tư vấn hoặc Zalo để được báo giá theo diện tích.</p>
          </div>
          <div class="cta-compact__actions">
            <a class="btn btn-primary btn-sm" href="tel:0944857999">Gọi tư vấn</a>
            <a class="btn btn-outline btn-sm" href="https://zalo.me/0944857999" target="_blank" rel="noopener">Zalo kỹ thuật</a>
          </div>
        </div>
        <div class="entry-content"><?php the_content(); ?></div>
        <?php get_template_part('template-parts/home', 'cta-inline'); ?>
        <div class="cta">
          <div>
            <h3>Cần báo giá nhanh?</h3>
            <p>Gửi yêu cầu, chúng tôi phản hồi trong 15 phút giờ hành chính.</p>
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
