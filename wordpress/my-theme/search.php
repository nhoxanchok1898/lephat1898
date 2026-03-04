<?php
/**
 * Search results template aligned with refined design system.
 */
get_header();

$query_text = trim((string) get_search_query());
global $wp_query;
$found_posts = ($wp_query instanceof WP_Query) ? max(0, (int) $wp_query->found_posts) : 0;
$current_page = max(1, (int) get_query_var('paged'));
$selected_scope = isset($_GET['post_type']) ? sanitize_key((string) wp_unslash($_GET['post_type'])) : '';
$scope_map = [
    '' => 'Tất cả',
    'post' => 'Bài viết',
    'product' => 'Sản phẩm',
];
$is_valid_scope = array_key_exists($selected_scope, $scope_map);
if (!$is_valid_scope) {
    $selected_scope = '';
}
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop');
$suggested_links = [
    ['label' => 'Kho sản phẩm', 'url' => $shop_url],
    ['label' => 'Hướng dẫn mua hàng', 'url' => home_url('/huong-dan-mua-hang')],
    ['label' => 'Câu hỏi thường gặp', 'url' => home_url('/faq')],
    ['label' => 'Liên hệ tư vấn', 'url' => home_url('/lien-he')],
];

$scope_url = function ($scope = '') use ($query_text) {
    $params = [];
    if ($query_text !== '') {
        $params['s'] = $query_text;
    }
    if ($scope !== '') {
        $params['post_type'] = sanitize_key((string) $scope);
    }
    return add_query_arg($params, home_url('/'));
};
?>
<main id="main-content">
  <div class="container home-page">
    <section class="page-section blog-shell blog-shell--hero search-shell__hero">
      <nav class="breadcrumb-nav" aria-label="Đường dẫn">
        <ol class="breadcrumb">
          <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
          <li>Tìm kiếm</li>
        </ol>
      </nav>

      <div class="section-heading">
        <div>
          <h1 class="page-title">Kết quả tìm kiếm</h1>
          <p class="section-sub"><?php echo ($query_text !== '') ? esc_html('Từ khóa: "' . $query_text . '"') : 'Nhập từ khóa để tìm nội dung phù hợp.'; ?></p>
        </div>
        <a class="btn btn-outline btn-sm" href="<?php echo esc_url(home_url('/')); ?>">Về trang chủ</a>
      </div>

      <form class="search-inline-form" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
        <label class="visually-hidden" for="site-search-input">Tìm kiếm</label>
        <input id="site-search-input" type="search" name="s" value="<?php echo esc_attr($query_text); ?>" placeholder="Nhập từ khóa bài viết, sản phẩm..." />
        <?php if ($selected_scope !== '') : ?>
          <input type="hidden" name="post_type" value="<?php echo esc_attr($selected_scope); ?>" />
        <?php endif; ?>
        <button class="btn btn-primary btn-sm" type="submit">Tìm kiếm</button>
      </form>

      <div class="search-scope" aria-label="Phạm vi tìm kiếm">
        <?php foreach ($scope_map as $scope_value => $scope_label) : ?>
          <a class="chip <?php echo ($selected_scope === $scope_value) ? 'active' : ''; ?>" href="<?php echo esc_url($scope_url($scope_value)); ?>">
            <?php echo esc_html($scope_label); ?>
          </a>
        <?php endforeach; ?>
      </div>

      <div class="blog-shell__stats" aria-label="Thống kê kết quả">
        <span><?php echo esc_html((string) $found_posts); ?> kết quả</span>
        <span>Trang <?php echo esc_html((string) $current_page); ?></span>
      </div>
    </section>

    <?php
    if (function_exists('my_theme_render_quick_answers')) {
        my_theme_render_quick_answers([
            'class' => 'quick-answers--search',
            'eyebrow' => 'Trước khi gọi hỏi lại',
            'title' => 'Một số câu trả lời nhanh khách thường cần khi đang tìm sản phẩm',
            'subtitle' => 'Nếu kết quả tìm kiếm chưa ra đúng thứ bạn cần, có thể đối chiếu nhanh các câu hỏi này trước khi chuyển sang shop hoặc gửi yêu cầu.',
            'indexes' => [0, 1, 3],
        ]);
    }

    if (function_exists('my_theme_render_recently_viewed_products')) {
        my_theme_render_recently_viewed_products([
            'title' => 'Sản phẩm bạn vừa xem',
            'aria_label' => 'Sản phẩm bạn vừa xem gần đây',
            'class' => 'related-products-block--recently-viewed related-products-block--search',
        ]);
    }
    ?>

    <section class="page-section blog-shell search-shell__listing">
      <?php if (have_posts()) : ?>
        <?php $rendered_posts = 0; ?>
        <div class="insight-grid">
          <?php while (have_posts()) : the_post(); ?>
            <?php
            $title = trim((string) get_the_title());
            if ($title === '' || (get_post_type() === 'post' && function_exists('my_theme_is_placeholder_blog_post') && my_theme_is_placeholder_blog_post(get_post()))) {
                continue;
            }
            $rendered_posts++;

            $excerpt = trim((string) get_the_excerpt());
            if ($excerpt === '') {
                $excerpt = wp_trim_words(wp_strip_all_tags((string) get_the_content()), 24);
            }

            $type_obj = get_post_type_object(get_post_type());
            $type_label = ($type_obj && !empty($type_obj->labels->singular_name))
                ? (string) $type_obj->labels->singular_name
                : 'Nội dung';
            ?>
            <article <?php post_class('insight-card'); ?>>
              <?php if (has_post_thumbnail()) : ?>
                <a class="insight-card__thumb" href="<?php the_permalink(); ?>">
                  <?php the_post_thumbnail('medium_large', ['loading' => 'lazy', 'decoding' => 'async']); ?>
                </a>
              <?php else : ?>
                <a class="insight-card__thumb insight-card__thumb--placeholder" href="<?php the_permalink(); ?>">
                  <span class="insight-card__thumb-badge"><?php echo esc_html($type_label); ?></span>
                  <strong class="insight-card__thumb-title"><?php echo esc_html($title); ?></strong>
                </a>
              <?php endif; ?>

              <div class="insight-card__body">
                <div class="insight-card__date"><?php echo esc_html($type_label); ?> • <?php echo esc_html(get_the_date()); ?></div>
                <h2 class="insight-card__title"><a href="<?php the_permalink(); ?>"><?php echo esc_html($title); ?></a></h2>
                <p class="insight-card__excerpt"><?php echo esc_html($excerpt); ?></p>
              </div>

              <div class="insight-card__actions">
                <a class="btn btn-primary w-100" href="<?php the_permalink(); ?>">Xem chi tiết</a>
              </div>
            </article>
          <?php endwhile; ?>
        </div>

        <?php if ($rendered_posts === 0) : ?>
          <div class="empty-state">
            <h2>Không có nội dung hiển thị được</h2>
            <p>Kết quả tồn tại nhưng không phù hợp với bộ lọc hiển thị hiện tại.</p>
            <div class="empty-state__actions">
              <a class="btn btn-primary" href="<?php echo esc_url(home_url('/')); ?>">Về trang chủ</a>
              <a class="btn btn-outline" href="<?php echo esc_url(home_url('/lien-he')); ?>">Liên hệ tư vấn</a>
            </div>
            <div class="search-scope" aria-label="Gợi ý điều hướng">
              <?php foreach ($suggested_links as $suggestion) : ?>
                <a class="chip" href="<?php echo esc_url($suggestion['url']); ?>"><?php echo esc_html($suggestion['label']); ?></a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($rendered_posts > 0 && $wp_query instanceof WP_Query && (int) $wp_query->max_num_pages > 1) : ?>
          <nav class="pagination-wrapper pagination-wrapper--posts" aria-label="Phân trang kết quả">
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
          <h2>Không tìm thấy kết quả phù hợp</h2>
          <p>Thử từ khóa ngắn gọn hơn hoặc chuyển sang tìm sản phẩm trong kho hàng.</p>
          <div class="empty-state__actions">
            <a class="btn btn-primary" href="<?php echo esc_url($shop_url); ?>">Xem kho sản phẩm</a>
            <a class="btn btn-outline" href="<?php echo esc_url(home_url('/lien-he')); ?>">Nhận tư vấn nhanh</a>
          </div>
          <div class="search-scope" aria-label="Gợi ý điều hướng">
            <?php foreach ($suggested_links as $suggestion) : ?>
              <a class="chip" href="<?php echo esc_url($suggestion['url']); ?>"><?php echo esc_html($suggestion['label']); ?></a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </section>
  </div>
</main>
<?php get_footer(); ?>
