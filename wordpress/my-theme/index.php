<?php get_header(); ?>
<main id="main-content">
  <div class="container">
    <?php get_template_part('template-parts/home', 'hero'); ?>

    <?php
      $is_placeholder_post = function ($title) {
        if (!function_exists('my_theme_normalize_search_text')) {
          return false;
        }
        $normalized = my_theme_normalize_search_text((string) $title);
        return (bool) preg_match('/^san pham son mau\\s*\\d+$/', $normalized);
      };
    ?>

    <section class="page-section">
      <div class="section-heading">
        <div>
          <h2 class="section-title">Bài viết mới</h2>
          <p class="section-sub">Kinh nghiệm chọn sơn, thi công và bảo quản công trình</p>
        </div>
      </div>

      <div class="product-grid">
        <?php
          $rendered_posts = 0;
          if (have_posts()) :
            while (have_posts()) : the_post();
              $title = (string) get_the_title();
              if ($title === '' || $is_placeholder_post($title)) {
                continue;
              }
              $rendered_posts++;
        ?>
            <article <?php post_class('product-card'); ?>>
              <?php if (has_post_thumbnail()) : ?>
                <a class="product-card__thumb" href="<?php the_permalink(); ?>">
                  <?php the_post_thumbnail('medium'); ?>
                </a>
              <?php endif; ?>
              <div class="product-card__body">
                <div class="product-card__brand"><?php echo esc_html(get_the_date()); ?></div>
                <h3 class="product-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                <p class="text-muted"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 20)); ?></p>
              </div>
              <div class="product-card__actions">
                <a class="btn btn-primary w-100" href="<?php the_permalink(); ?>">Đọc tiếp</a>
              </div>
            </article>
        <?php
            endwhile;
          endif;
        ?>
        <?php if ($rendered_posts === 0) : ?>
          <p class="text-muted">Chưa có bài viết.</p>
        <?php endif; ?>
      </div>

      <?php the_posts_navigation(); ?>
    </section>
  </div>
</main>
<?php get_footer(); ?>
