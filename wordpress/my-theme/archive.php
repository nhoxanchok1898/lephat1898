<?php
/**
 * Archive template aligned with refined design system.
 */
get_header();

$archive_title = wp_strip_all_tags((string) get_the_archive_title());
$archive_desc = trim(wp_strip_all_tags((string) get_the_archive_description()));
if ($archive_title === '') {
    $archive_title = 'Lưu trữ bài viết';
}
$archive_type = 'Lưu trữ';
if (is_category()) {
    $archive_type = 'Chuyên mục';
} elseif (is_tag()) {
    $archive_type = 'Thẻ';
} elseif (is_author()) {
    $archive_type = 'Tác giả';
} elseif (is_date()) {
    $archive_type = 'Theo thời gian';
}

global $wp_query;
$found_posts = ($wp_query instanceof WP_Query) ? max(0, (int) $wp_query->found_posts) : 0;
$current_page = max(1, (int) get_query_var('paged'));
$archive_suggestions = [
    ['label' => 'Về trang chủ', 'url' => home_url('/')],
    ['label' => 'Xem sản phẩm', 'url' => function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop')],
    ['label' => 'FAQ', 'url' => home_url('/faq')],
    ['label' => 'Liên hệ tư vấn', 'url' => home_url('/lien-he')],
];
?>
<main id="main-content">
  <div class="container home-page">
    <section class="page-section blog-shell blog-shell--hero archive-shell__hero">
      <nav class="breadcrumb-nav" aria-label="Đường dẫn">
        <ol class="breadcrumb">
          <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
          <li><?php echo esc_html($archive_title); ?></li>
        </ol>
      </nav>

      <div class="section-heading">
        <div>
          <h1 class="page-title"><?php echo esc_html($archive_title); ?></h1>
          <p class="section-sub"><?php echo ($archive_desc !== '') ? esc_html($archive_desc) : 'Tổng hợp bài viết theo chủ đề để theo dõi nhanh nội dung liên quan.'; ?></p>
        </div>
        <div class="archive-shell__actions">
          <a class="btn btn-outline btn-sm" href="<?php echo esc_url(home_url('/')); ?>">Về trang chủ</a>
          <a class="btn btn-primary btn-sm" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop')); ?>">Xem sản phẩm</a>
        </div>
      </div>

      <div class="blog-shell__stats" aria-label="Tổng quan lưu trữ">
        <span><?php echo esc_html($archive_type); ?></span>
        <span><?php echo esc_html((string) $found_posts); ?> bài viết</span>
        <span>Trang <?php echo esc_html((string) $current_page); ?></span>
      </div>
    </section>

    <section class="page-section blog-shell archive-shell__listing">
      <?php if (have_posts()) : ?>
        <?php $rendered_posts = 0; ?>
        <div class="insight-grid">
          <?php while (have_posts()) : the_post(); ?>
            <?php
            $title = trim((string) get_the_title());
            if ($title === '' || (function_exists('my_theme_is_placeholder_blog_post') && my_theme_is_placeholder_blog_post(get_post()))) {
                continue;
            }
            $rendered_posts++;
            $excerpt = trim((string) get_the_excerpt());
            if ($excerpt === '') {
                $excerpt = wp_trim_words(wp_strip_all_tags((string) get_the_content()), 24);
            }
            ?>
            <article <?php post_class('insight-card'); ?>>
              <?php if (has_post_thumbnail()) : ?>
                <a class="insight-card__thumb" href="<?php the_permalink(); ?>">
                  <?php the_post_thumbnail('medium_large', ['loading' => 'lazy', 'decoding' => 'async']); ?>
                </a>
              <?php else : ?>
                <a class="insight-card__thumb insight-card__thumb--placeholder" href="<?php the_permalink(); ?>">
                  <span class="insight-card__thumb-badge"><?php echo esc_html($archive_type); ?></span>
                  <strong class="insight-card__thumb-title"><?php echo esc_html($title); ?></strong>
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
          <?php endwhile; ?>
        </div>

        <?php if ($rendered_posts === 0) : ?>
          <div class="empty-state">
            <h2>Đang cập nhật nội dung cho mục này</h2>
            <p>Dữ liệu có thể đang được làm mới. Vui lòng quay lại sau hoặc xem các mục khác.</p>
            <div class="empty-state__actions">
              <a class="btn btn-primary" href="<?php echo esc_url(home_url('/')); ?>">Về trang chủ</a>
              <a class="btn btn-outline" href="<?php echo esc_url(home_url('/lien-he')); ?>">Liên hệ tư vấn</a>
            </div>
            <div class="search-scope" aria-label="Gợi ý điều hướng">
              <?php foreach ($archive_suggestions as $suggestion) : ?>
                <a class="chip" href="<?php echo esc_url($suggestion['url']); ?>"><?php echo esc_html($suggestion['label']); ?></a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($rendered_posts > 0 && $wp_query instanceof WP_Query && (int) $wp_query->max_num_pages > 1) : ?>
          <nav class="pagination-wrapper pagination-wrapper--posts" aria-label="Phân trang lưu trữ">
            <?php
            the_posts_pagination([
                'mid_size' => 1,
                'prev_text' => 'Trước',
                'next_text' => 'Sau',
            ]);
            ?>
          </nav>
        <?php endif; ?>
      <?php else : ?>
        <div class="empty-state">
          <h2>Chưa có bài viết trong mục này</h2>
          <p>Thử quay lại trang chủ hoặc chọn mục khác để xem thêm nội dung tư vấn thi công.</p>
          <div class="empty-state__actions">
            <a class="btn btn-primary" href="<?php echo esc_url(home_url('/')); ?>">Về trang chủ</a>
            <a class="btn btn-outline" href="<?php echo esc_url(home_url('/lien-he')); ?>">Liên hệ tư vấn</a>
          </div>
          <div class="search-scope" aria-label="Gợi ý điều hướng">
            <?php foreach ($archive_suggestions as $suggestion) : ?>
              <a class="chip" href="<?php echo esc_url($suggestion['url']); ?>"><?php echo esc_html($suggestion['label']); ?></a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </section>
  </div>
</main>
<?php get_footer(); ?>
