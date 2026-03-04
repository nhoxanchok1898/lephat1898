<?php
get_header();

$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop');
$blog_url = trailingslashit(home_url('/blog'));
$published_total = function_exists('my_theme_get_public_blog_post_count')
    ? my_theme_get_public_blog_post_count()
    : 0;
$current_page = max(1, (int) get_query_var('paged'));
?>
<main id="main-content">
  <div class="container home-page">
    <section class="page-section blog-shell blog-shell--hero">
      <nav class="breadcrumb-nav" aria-label="Đường dẫn">
        <ol class="breadcrumb">
          <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
          <li>Blog</li>
        </ol>
      </nav>

      <div class="section-heading">
        <div>
          <h1 class="page-title">Góc tư vấn thi công</h1>
          <p class="section-sub">Nội dung thực tế về chọn hệ sơn, định mức và quy trình thi công theo từng bề mặt.</p>
        </div>
        <a class="btn btn-outline btn-sm" href="<?php echo esc_url($shop_url); ?>">Xem danh mục sản phẩm</a>
      </div>

      <div class="blog-shell__stats" aria-label="Tổng quan bài viết">
        <span><?php echo esc_html((string) max(0, $published_total)); ?> bài đã xuất bản</span>
        <span>Trang <?php echo esc_html((string) $current_page); ?></span>
      </div>
    </section>

    <section class="page-section blog-shell blog-listing">
      <div class="insight-grid">
        <?php
        $rendered_posts = 0;
        if (have_posts()) :
            while (have_posts()) :
                the_post();
                $title = trim((string) get_the_title());
                if ($title === '' || (function_exists('my_theme_is_placeholder_blog_post') && my_theme_is_placeholder_blog_post(get_post()))) {
                    continue;
                }

                $excerpt = trim((string) get_the_excerpt());
                if ($excerpt === '') {
                    $excerpt = wp_trim_words(wp_strip_all_tags((string) get_the_content()), 24);
                }
                $rendered_posts++;
                ?>
            <article <?php post_class('insight-card'); ?>>
              <?php if (has_post_thumbnail()) : ?>
                <a class="insight-card__thumb" href="<?php the_permalink(); ?>">
                  <?php the_post_thumbnail('medium_large', ['loading' => 'lazy', 'decoding' => 'async']); ?>
                </a>
              <?php endif; ?>
              <div class="insight-card__body">
                <div class="insight-card__date"><?php echo esc_html(get_the_date()); ?></div>
                <h2 class="insight-card__title"><a href="<?php the_permalink(); ?>"><?php echo esc_html($title); ?></a></h2>
                <p class="insight-card__excerpt"><?php echo esc_html($excerpt); ?></p>
              </div>
              <div class="insight-card__actions">
                <a class="btn btn-primary w-100" href="<?php the_permalink(); ?>">Đọc tiếp</a>
              </div>
            </article>
          <?php
            endwhile;
        endif;
        ?>
      </div>

      <?php if ($rendered_posts === 0) : ?>
        <div class="empty-state">
          <h2>Blog đang được hoàn thiện</h2>
          <p>Hiện chưa có bài viết công khai. Bạn có thể xem kho sản phẩm hoặc liên hệ đội kỹ thuật để được tư vấn nhanh.</p>
          <div class="empty-state__actions">
            <a class="btn btn-primary" href="<?php echo esc_url($shop_url); ?>">Xem kho sản phẩm</a>
            <a class="btn btn-outline" href="<?php echo esc_url(home_url('/lien-he')); ?>">Liên hệ tư vấn</a>
          </div>
        </div>
      <?php endif; ?>

      <?php
      global $wp_query;
      if ($wp_query instanceof WP_Query && (int) $wp_query->max_num_pages > 1) :
          ?>
        <nav class="pagination-wrapper pagination-wrapper--posts" aria-label="Phân trang bài viết">
          <?php
          the_posts_pagination([
              'mid_size' => 1,
              'prev_text' => 'Trước',
              'next_text' => 'Sau',
          ]);
          ?>
        </nav>
      <?php endif; ?>
    </section>
  </div>
</main>
<?php get_footer(); ?>
