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

    <?php
    if (function_exists('my_theme_render_quick_answers')) {
        my_theme_render_quick_answers([
            'class' => 'quick-answers--blog',
            'eyebrow' => 'FAQ ngắn trước khi đi tiếp',
            'title' => 'Một vài câu hỏi khách thường cần chốt trước khi rời blog',
            'subtitle' => 'Nếu bạn đang đọc blog để hiểu vấn đề, hãy kiểm tra nhanh các câu hỏi này trước khi chuyển sang sản phẩm, giải pháp hoặc gửi yêu cầu.',
            'indexes' => [0, 1, 4],
        ]);
    }

    if (function_exists('my_theme_render_service_compass')) {
        my_theme_render_service_compass([
            'class' => 'service-compass--blog',
            'eyebrow' => 'Đọc xong rồi đi tiếp',
            'title' => 'Blog giúp hiểu vấn đề, còn chốt vật tư thì đi tiếp theo 3 đường này',
            'subtitle' => 'Sau khi đọc tư vấn, bạn có thể mở kho sản phẩm, đi vào nhóm giải pháp hoặc gửi nhu cầu thực tế để đội kỹ thuật điều hướng nhanh hơn.',
        ]);
    }

    if (function_exists('my_theme_render_recently_viewed_products')) {
        my_theme_render_recently_viewed_products([
            'title' => 'Các mã bạn vừa xem trước khi đọc blog',
            'aria_label' => 'Các mã bạn vừa xem trước khi đọc blog',
            'class' => 'related-products-block--recently-viewed related-products-block--blog',
        ]);
    }
    ?>

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

    <?php
    if (function_exists('my_theme_render_lead_capture_form')) {
        echo my_theme_render_lead_capture_form([
            'source' => 'blog-index',
            'title' => 'Đọc blog xong nhưng vẫn chưa chốt được mã?',
            'subtitle' => 'Gửi bề mặt, diện tích, hãng đang cân nhắc hoặc tiến độ công trình để đội kỹ thuật chuyển bạn sang đúng sản phẩm hoặc landing page phù hợp hơn.',
            'button' => 'Gửi nhu cầu từ blog',
        ]);
    }
    ?>
  </div>
</main>
<?php get_footer(); ?>
