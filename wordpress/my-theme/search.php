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
$shop_search_url = ($query_text !== '') ? add_query_arg('q', $query_text, $shop_url) : $shop_url;
$search_visible_ids = function_exists('my_theme_get_catalog_visible_product_ids')
    ? my_theme_get_catalog_visible_product_ids(false)
    : [];
$search_visible_ids = array_values(array_filter(array_map('intval', (array) $search_visible_ids), function ($id) {
    return $id > 0;
}));
$matched_product_ids = ($query_text !== '' && function_exists('my_theme_get_search_matched_product_ids'))
    ? my_theme_get_search_matched_product_ids($query_text, $search_visible_ids, 3)
    : [];
$matched_product_ids = array_values(array_filter(array_map('intval', (array) $matched_product_ids), function ($id) {
    return $id > 0;
}));
$matched_product_map = (!empty($matched_product_ids) && function_exists('my_theme_get_product_object_map'))
    ? my_theme_get_product_object_map($matched_product_ids)
    : [];
$matched_products = [];
foreach ($matched_product_ids as $matched_product_id) {
    if (!isset($matched_product_map[$matched_product_id]) || !$matched_product_map[$matched_product_id] instanceof WC_Product) {
        continue;
    }
    $matched_products[] = $matched_product_map[$matched_product_id];
}
$matched_category_ids = ($query_text !== '' && function_exists('my_theme_get_search_matched_product_cat_ids'))
    ? my_theme_get_search_matched_product_cat_ids($query_text, $search_visible_ids, 1)
    : [];
$matched_category_ids = array_values(array_filter(array_map('intval', (array) $matched_category_ids), function ($id) {
    return $id > 0;
}));
$matched_category = !empty($matched_category_ids) ? get_term((int) $matched_category_ids[0], 'product_cat') : null;
$matched_category_url = ($matched_category instanceof WP_Term)
    ? add_query_arg('category', (int) $matched_category->term_id, $shop_url)
    : '';
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

    <?php if ($query_text !== '' && (!empty($matched_products) || $matched_category instanceof WP_Term)) : ?>
      <section class="page-section search-intent-panel" aria-label="Đi thẳng vào kết quả sản phẩm phù hợp">
        <div class="section-heading search-intent-panel__head">
          <div>
            <p class="eyebrow eyebrow-muted">Nếu bạn đang tìm hàng theo mã hoặc nhóm nhu cầu</p>
            <h2 class="section-title">Đi thẳng vào kho sản phẩm sẽ nhanh hơn</h2>
            <p class="section-sub">Trang tìm kiếm toàn site phù hợp để xem cả bài viết lẫn sản phẩm. Nếu bạn đang chốt hàng, các lối đi nhanh dưới đây sẽ đưa bạn vào đúng kết quả trong shop.</p>
          </div>
          <div class="search-intent-panel__actions">
            <a class="btn btn-primary btn-sm" href="<?php echo esc_url($shop_search_url); ?>">Mở kết quả trong shop</a>
            <?php if ($matched_category_url !== '') : ?>
              <a class="btn btn-outline btn-sm" href="<?php echo esc_url($matched_category_url); ?>">Vào nhóm <?php echo esc_html((string) $matched_category->name); ?></a>
            <?php endif; ?>
          </div>
        </div>

        <?php if (!empty($matched_products)) : ?>
          <div class="search-intent-panel__grid">
            <?php foreach ($matched_products as $matched_product) : ?>
              <?php
              $matched_profile = function_exists('my_theme_get_product_catalog_profile')
                  ? my_theme_get_product_catalog_profile($matched_product)
                  : [];
              $matched_name = isset($matched_profile['display_name']) && (string) $matched_profile['display_name'] !== ''
                  ? (string) $matched_profile['display_name']
                  : $matched_product->get_name();
              $matched_line = isset($matched_profile['line_label'])
                  ? trim((string) $matched_profile['line_label'])
                  : '';
              $matched_brand_label = isset($matched_profile['brand_label'])
                  ? trim((string) $matched_profile['brand_label'])
                  : '';
              if ($matched_brand_label === 'Sản phẩm') {
                  $matched_brand_label = '';
              }
              $matched_price_html = function_exists('my_theme_get_loop_price_html')
                  ? my_theme_get_loop_price_html($matched_product, 'search-intent-card__price product-card__price')
                  : '<div class="search-intent-card__price product-card__price"><span class="product-card__price-contact">Liên hệ báo giá</span></div>';
              ?>
              <article class="search-intent-card">
                <a class="search-intent-card__thumb" href="<?php echo esc_url($matched_product->get_permalink()); ?>">
                  <?php
                  echo function_exists('my_theme_get_product_thumbnail_markup')
                      ? my_theme_get_product_thumbnail_markup($matched_product, 'woocommerce_thumbnail', [
                          'loading' => 'lazy',
                          'decoding' => 'async',
                          'alt' => $matched_name,
                      ])
                      : $matched_product->get_image('woocommerce_thumbnail', ['loading' => 'lazy', 'decoding' => 'async', 'alt' => $matched_name]);
                  ?>
                </a>
                <div class="search-intent-card__body">
                  <div class="search-intent-card__meta">
                    <?php if ($matched_brand_label !== '') : ?><span class="chip chip--soft"><?php echo esc_html($matched_brand_label); ?></span><?php endif; ?>
                    <?php if ($matched_line !== '') : ?><span class="chip chip--soft"><?php echo esc_html($matched_line); ?></span><?php endif; ?>
                  </div>
                  <h3><a href="<?php echo esc_url($matched_product->get_permalink()); ?>"><?php echo esc_html((string) $matched_name); ?></a></h3>
                  <?php if ($matched_price_html !== '') : ?>
                    <?php echo wp_kses_post($matched_price_html); ?>
                  <?php endif; ?>
                </div>
                <div class="search-intent-card__actions">
                  <a class="btn btn-primary w-100" href="<?php echo esc_url($matched_product->get_permalink()); ?>">Xem sản phẩm</a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    <?php endif; ?>

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

    if (function_exists('my_theme_render_service_compass')) {
        my_theme_render_service_compass([
            'class' => 'service-compass--search',
            'eyebrow' => 'Nếu tìm chưa ra đúng ý',
            'title' => 'Từ trang tìm kiếm, bạn có thể đi tiếp theo 3 đường rõ ràng',
            'subtitle' => 'Vào kho sản phẩm nếu đang tìm theo mã. Xem giải pháp nếu đang tìm theo nhu cầu thi công. Hoặc gửi mô tả hiện trạng để đội kỹ thuật điều hướng lại.',
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
            <a class="btn btn-primary" href="<?php echo esc_url($shop_search_url); ?>"><?php echo ($query_text !== '') ? 'Tìm trong kho sản phẩm' : 'Xem kho sản phẩm'; ?></a>
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

    <?php
    if (function_exists('my_theme_render_lead_capture_form')) {
        echo my_theme_render_lead_capture_form([
            'source' => 'search-page',
            'title' => 'Tìm kiếm chưa ra đúng thứ bạn cần?',
            'subtitle' => 'Gửi từ khóa đang tìm, bề mặt, diện tích hoặc thương hiệu đang cân nhắc. Đội kỹ thuật sẽ hướng bạn sang đúng sản phẩm hoặc landing phù hợp hơn.',
            'button' => 'Gửi nhu cầu tìm kiếm',
        ]);
    }
    ?>
  </div>
</main>
<?php get_footer(); ?>
