<?php
/** WooCommerce shop template */
get_header();

$q = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
$cat = isset($_GET['category']) ? absint($_GET['category']) : 0;
$brand = isset($_GET['brand']) ? sanitize_title(wp_unslash($_GET['brand'])) : '';
$line = isset($_GET['line']) ? sanitize_title(wp_unslash($_GET['line'])) : '';
$sort = isset($_GET['sort']) ? sanitize_text_field(wp_unslash($_GET['sort'])) : '';
$in_stock = isset($_GET['in_stock']) && sanitize_text_field(wp_unslash($_GET['in_stock'])) === '1';
$on_sale = isset($_GET['on_sale']) && sanitize_text_field(wp_unslash($_GET['on_sale'])) === '1';
$catalog_visible_ids = function_exists('my_theme_get_catalog_visible_product_ids')
    ? my_theme_get_catalog_visible_product_ids(false)
    : [];
if (empty($catalog_visible_ids)) {
    $catalog_visible_ids = [0];
}

$brand_options = function_exists('my_theme_get_brand_filter_options')
    ? my_theme_get_brand_filter_options($catalog_visible_ids)
    : [];
$brand_options = is_array($brand_options) ? $brand_options : [];
$brand_map = function_exists('my_theme_get_brand_keyword_map') ? my_theme_get_brand_keyword_map() : [];
$core_brand_slugs = ['dulux', 'maxilite', 'weber', 'jotun', 'nippon', 'kova', 'toa', 'sika', 'apollo'];
$merged_brand_options = [];
foreach ($core_brand_slugs as $core_slug) {
    $meta = isset($brand_options[$core_slug]) && is_array($brand_options[$core_slug]) ? $brand_options[$core_slug] : [];
    $map_label = isset($brand_map[$core_slug]['label']) ? (string) $brand_map[$core_slug]['label'] : ucfirst((string) $core_slug);
    $merged_brand_options[$core_slug] = [
        'label' => isset($meta['label']) && (string) $meta['label'] !== '' ? (string) $meta['label'] : $map_label,
        'count' => isset($meta['count']) ? max(0, (int) $meta['count']) : 0,
    ];
}
foreach ($brand_options as $slug => $meta) {
    $slug = sanitize_title((string) $slug);
    if ($slug === '' || isset($merged_brand_options[$slug])) {
        continue;
    }
    $merged_brand_options[$slug] = [
        'label' => isset($meta['label']) ? (string) $meta['label'] : ucfirst((string) $slug),
        'count' => isset($meta['count']) ? max(0, (int) $meta['count']) : 0,
    ];
}
$brand_options = $merged_brand_options;
if ($brand !== '' && !isset($brand_options[$brand])) {
    $brand = '';
}
if ($brand !== '' && function_exists('my_theme_filter_product_ids_by_brand_slug')) {
    $catalog_visible_ids = my_theme_filter_product_ids_by_brand_slug($catalog_visible_ids, $brand);
    if (empty($catalog_visible_ids)) {
        $catalog_visible_ids = [0];
    }
}

$line_options = function_exists('my_theme_get_line_filter_options')
    ? my_theme_get_line_filter_options($catalog_visible_ids, $brand)
    : [];
if ($line !== '' && !isset($line_options[$line])) {
    $line = '';
}
if ($line !== '' && function_exists('my_theme_filter_product_ids_by_line_slug')) {
    $catalog_visible_ids = my_theme_filter_product_ids_by_line_slug($catalog_visible_ids, $line, $brand);
    if (empty($catalog_visible_ids)) {
        $catalog_visible_ids = [0];
    }
}

$matched_cat_ids = [];
$matched_product_ids = [];
if ($q !== '' && function_exists('my_theme_get_search_matched_product_ids')) {
    $matched_product_ids = my_theme_get_search_matched_product_ids($q, $catalog_visible_ids, 96);
}
if (!$cat && $q !== '' && function_exists('my_theme_get_search_matched_product_cat_ids')) {
    $matched_cat_ids = my_theme_get_search_matched_product_cat_ids($q, $catalog_visible_ids);
}
$use_product_match = (!$cat && $q !== '' && !empty($matched_product_ids));

$tax_query = ['relation' => 'AND'];
if ($cat) {
    $tax_query[] = [
        'taxonomy' => 'product_cat',
        'field'    => 'term_id',
        'terms'    => [$cat],
    ];
}
if (!$cat && !$use_product_match && !empty($matched_cat_ids)) {
    $tax_query[] = [
        'taxonomy' => 'product_cat',
        'field'    => 'term_id',
        'terms'    => $matched_cat_ids,
        'operator' => 'IN',
    ];
}

$args = [
    'post_type'           => 'product',
    'post_status'         => 'publish',
    'posts_per_page'      => 24,
    'paged'               => max(1, get_query_var('paged')),
    'ignore_sticky_posts' => true,
    's'                   => ($use_product_match || !empty($matched_cat_ids)) ? '' : $q,
    'post__in'            => $use_product_match ? $matched_product_ids : $catalog_visible_ids,
];
if (count($tax_query) > 1) {
    $args['tax_query'] = $tax_query;
}

$meta_query = [];
if ($in_stock) {
    $meta_query[] = [
        'key' => '_stock_status',
        'value' => 'instock',
        'compare' => '=',
    ];
}
if (!empty($meta_query)) {
    $args['meta_query'] = $meta_query;
}
if ($on_sale) {
    $sale_ids = function_exists('my_theme_get_effective_sale_product_ids')
        ? my_theme_get_effective_sale_product_ids(array_map('intval', (array) $catalog_visible_ids))
        : (function_exists('wc_get_product_ids_on_sale') ? array_map('intval', (array) wc_get_product_ids_on_sale()) : []);
    if (empty($sale_ids)) {
        $args['post__in'] = [0];
    } else {
        $args['post__in'] = array_values(array_intersect(array_map('intval', (array) $args['post__in']), $sale_ids));
        if (empty($args['post__in'])) {
            $args['post__in'] = [0];
        }
    }
}

$effective_sort = $sort;
if ($effective_sort === '' && $use_product_match) {
    $effective_sort = 'match';
}

switch ($effective_sort) {
    case 'price_asc':
        $sorted_ids = function_exists('my_theme_get_price_sorted_query_product_ids')
            ? my_theme_get_price_sorted_query_product_ids($args, 'asc')
            : [];
        $args['post__in'] = !empty($sorted_ids) ? $sorted_ids : [0];
        $args['s'] = '';
        unset($args['tax_query'], $args['meta_query'], $args['meta_key']);
        $args['orderby'] = 'post__in';
        $args['order'] = 'ASC';
        break;
    case 'price_desc':
        $sorted_ids = function_exists('my_theme_get_price_sorted_query_product_ids')
            ? my_theme_get_price_sorted_query_product_ids($args, 'desc')
            : [];
        $args['post__in'] = !empty($sorted_ids) ? $sorted_ids : [0];
        $args['s'] = '';
        unset($args['tax_query'], $args['meta_query'], $args['meta_key']);
        $args['orderby'] = 'post__in';
        $args['order'] = 'ASC';
        break;
    case 'name_asc':
        $args['orderby'] = 'title';
        $args['order'] = 'ASC';
        break;
    case 'name_desc':
        $args['orderby'] = 'title';
        $args['order'] = 'DESC';
        break;
    case 'match':
        $args['orderby'] = 'post__in';
        $args['order'] = 'ASC';
        break;
    default:
        if ($use_product_match) {
            $args['orderby'] = 'post__in';
            $args['order'] = 'ASC';
        } else {
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        }
        break;
}

$loop = new WP_Query($args);
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop');

$build_url = function ($overrides = []) use ($shop_url, $q, $cat, $brand, $line, $sort, $in_stock, $on_sale) {
    $params = [];
    if ($q !== '') {
        $params['q'] = $q;
    }
    if ($cat) {
        $params['category'] = $cat;
    }
    if ($brand !== '') {
        $params['brand'] = $brand;
    }
    if ($line !== '') {
        $params['line'] = $line;
    }
    if ($sort !== '') {
        $params['sort'] = $sort;
    }
    if ($in_stock) {
        $params['in_stock'] = '1';
    }
    if ($on_sale) {
        $params['on_sale'] = '1';
    }

    foreach ($overrides as $key => $value) {
        if ($value === '' || $value === 0 || $value === null) {
            unset($params[$key]);
            continue;
        }
        $params[$key] = $value;
    }

    return add_query_arg($params, $shop_url);
};

$category_groups = function_exists('my_theme_get_visible_product_category_groups')
    ? my_theme_get_visible_product_category_groups($catalog_visible_ids)
    : [
        'lookup' => [],
        'by_parent' => [],
    ];
$cats_by_parent = isset($category_groups['by_parent']) && is_array($category_groups['by_parent'])
    ? $category_groups['by_parent']
    : [];

$current_page = max(1, (int) get_query_var('paged'));
$per_page = max(1, (int) $loop->get('posts_per_page'));
$showing_from = ($loop->found_posts > 0) ? (($current_page - 1) * $per_page) + 1 : 0;
$showing_to = min($loop->found_posts, $current_page * $per_page);

$top_level_cats = isset($cats_by_parent[0]) && is_array($cats_by_parent[0])
    ? array_values(array_filter($cats_by_parent[0], function ($term) {
        return is_array($term) && !empty($term['term_id']);
    }))
    : [];
if (!empty($top_level_cats)) {
    $top_level_cats = array_slice($top_level_cats, 0, 12);
}

$active_cat_term = ($cat > 0)
    ? get_term($cat, 'product_cat')
    : null;
if (!($active_cat_term instanceof WP_Term) || is_wp_error($active_cat_term)) {
    $active_cat_term = null;
}

$archive_support_profile = function_exists('my_theme_get_archive_support_profile')
    ? my_theme_get_archive_support_profile([
        'shop_url' => $shop_url,
        'brand_slug' => $brand,
        'line_slug' => $line,
        'search_query' => $q,
        'category_term' => $active_cat_term,
        'found_posts' => (int) $loop->found_posts,
        'in_stock' => $in_stock,
        'on_sale' => $on_sale,
    ])
    : [];
$archive_heading_title = isset($archive_support_profile['heading_title']) && trim((string) $archive_support_profile['heading_title']) !== ''
    ? trim((string) $archive_support_profile['heading_title'])
    : 'Kho sản phẩm chính hãng';

?>

<main id="main-content">
  <div class="container">
    <style id="shop-fill-screen-layout">
      @media (min-width: 1100px) {
        .shop-shell {
          grid-template-columns: minmax(0, 1fr) !important;
          gap: 18px !important;
        }

        .shop-sidebar {
          position: static !important;
          top: auto !important;
          width: 100%;
        }

        .product-grid--shop {
          grid-template-columns: repeat(auto-fit, minmax(min(100%, 250px), 1fr)) !important;
          justify-content: stretch !important;
          justify-items: stretch !important;
        }

        .product-grid--shop > .product,
        .product-grid--shop > .product-card {
          width: auto !important;
          max-width: none !important;
          justify-self: stretch !important;
        }
      }
    </style>
    <button
      type="button"
      class="shop-mobile-filter-toggle btn btn-outline btn-sm"
      data-shop-filter-toggle
      aria-expanded="false"
      aria-controls="shop-filter-panel"
    >
      Bộ lọc sản phẩm
    </button>
    <div class="shop-mobile-filter-backdrop" data-shop-filter-backdrop hidden></div>

    <section class="page-section shop-shell">
      <aside id="shop-filter-panel" class="shop-sidebar" data-shop-filter-panel aria-label="Bộ lọc sản phẩm">
        <button type="button" class="shop-sidebar__close btn btn-outline btn-sm" data-shop-filter-close>
          Đóng bộ lọc
        </button>
        <div class="shop-sidebar__block">
          <h3 class="shop-sidebar__title">Tìm sản phẩm</h3>
          <div class="shop-search-assist-wrap" data-search-assist-root="shop">
            <form method="get" class="shop-search-form" role="search" aria-label="Tìm sản phẩm">
              <label class="visually-hidden" for="shop-q">Tìm sản phẩm</label>
              <input id="shop-q" class="shop-search-form__input" type="search" name="q" value="<?php echo esc_attr($q); ?>" placeholder="Ví dụ: sơn kim loại, sơn epoxy, bột trét..." autocomplete="off" />
              <?php if ($cat) : ?><input type="hidden" name="category" value="<?php echo esc_attr($cat); ?>"><?php endif; ?>
              <?php if ($brand !== '') : ?><input type="hidden" name="brand" value="<?php echo esc_attr($brand); ?>"><?php endif; ?>
              <?php if ($line !== '') : ?><input type="hidden" name="line" value="<?php echo esc_attr($line); ?>"><?php endif; ?>
              <?php if ($sort !== '') : ?><input type="hidden" name="sort" value="<?php echo esc_attr($sort); ?>"><?php endif; ?>
              <?php if ($in_stock) : ?><input type="hidden" name="in_stock" value="1"><?php endif; ?>
              <?php if ($on_sale) : ?><input type="hidden" name="on_sale" value="1"><?php endif; ?>
              <button class="btn btn-primary btn-sm w-100" type="submit">Tìm sản phẩm</button>
              <?php if ($q !== '' || $cat || $brand !== '' || $line !== '' || $in_stock || $on_sale) : ?>
                <a class="btn btn-outline btn-sm w-100" href="<?php echo esc_url($build_url(['q' => '', 'category' => 0, 'brand' => '', 'line' => '', 'in_stock' => '', 'on_sale' => '', 'sort' => ''])); ?>">Xóa bộ lọc</a>
              <?php endif; ?>
            </form>
            <div class="shop-quick-brands" aria-label="Nhu cầu phổ biến">
              <span class="shop-subcats__label">Mở nhanh:</span>
              <a class="chip" href="<?php echo esc_url($build_url(['q' => 'Sơn nội thất', 'category' => 0, 'brand' => '', 'line' => '', 'in_stock' => '', 'on_sale' => '', 'sort' => ''])); ?>">Nội thất</a>
              <a class="chip" href="<?php echo esc_url($build_url(['q' => 'Sơn ngoại thất', 'category' => 0, 'brand' => '', 'line' => '', 'in_stock' => '', 'on_sale' => '', 'sort' => ''])); ?>">Ngoại thất</a>
              <a class="chip" href="<?php echo esc_url($build_url(['q' => 'Chống thấm', 'category' => 0, 'brand' => '', 'line' => '', 'in_stock' => '', 'on_sale' => '', 'sort' => ''])); ?>">Chống thấm</a>
            </div>
            <?php
            if (function_exists('my_theme_render_search_assist')) {
                my_theme_render_search_assist('shop');
            }
            ?>
          </div>
        </div>
      </aside>

      <div class="shop-results" id="shop-results">
        <div class="shop-results__head">
          <div>
            <h1 class="section-title"><?php echo esc_html($archive_heading_title); ?></h1>
            <p class="section-sub">Hiển thị <?php echo esc_html((string) $showing_from); ?>-<?php echo esc_html((string) $showing_to); ?> trên <?php echo esc_html((string) $loop->found_posts); ?> sản phẩm</p>
          </div>

          <form method="get" class="sort-form">
            <?php if ($q !== '') : ?><input type="hidden" name="q" value="<?php echo esc_attr($q); ?>"><?php endif; ?>
            <?php if ($cat) : ?><input type="hidden" name="category" value="<?php echo esc_attr($cat); ?>"><?php endif; ?>
            <?php if ($brand !== '') : ?><input type="hidden" name="brand" value="<?php echo esc_attr($brand); ?>"><?php endif; ?>
            <?php if ($line !== '') : ?><input type="hidden" name="line" value="<?php echo esc_attr($line); ?>"><?php endif; ?>
            <?php if ($in_stock) : ?><input type="hidden" name="in_stock" value="1"><?php endif; ?>
            <?php if ($on_sale) : ?><input type="hidden" name="on_sale" value="1"><?php endif; ?>
            <label for="sort" class="visually-hidden">Sắp xếp</label>
            <select id="sort" name="sort" class="sort-select" onchange="this.form.submit()">
              <option value="" <?php selected($effective_sort, ''); ?>>Mới nhất</option>
              <?php if ($use_product_match) : ?>
                <option value="match" <?php selected($effective_sort, 'match'); ?>>Phù hợp nhất</option>
              <?php endif; ?>
              <option value="price_asc" <?php selected($sort, 'price_asc'); ?>>Giá thấp đến cao</option>
              <option value="price_desc" <?php selected($sort, 'price_desc'); ?>>Giá cao đến thấp</option>
              <option value="name_asc" <?php selected($sort, 'name_asc'); ?>>Tên A-Z</option>
              <option value="name_desc" <?php selected($sort, 'name_desc'); ?>>Tên Z-A</option>
            </select>
          </form>
        </div>

        <?php
        if (function_exists('my_theme_render_archive_support_layout')) {
            my_theme_render_archive_support_layout([
                'shop_url' => $shop_url,
                'brand_slug' => $brand,
                'line_slug' => $line,
                'search_query' => $q,
                'category_term' => $active_cat_term,
                'found_posts' => (int) $loop->found_posts,
                'in_stock' => $in_stock,
                'on_sale' => $on_sale,
            ]);
        }
        ?>

        <div class="shop-quick-brands" aria-label="Lọc nhanh sản phẩm">
          <span class="shop-subcats__label">Lọc nhanh:</span>
          <a class="chip <?php echo $in_stock ? 'active' : ''; ?>" href="<?php echo esc_url($build_url(['in_stock' => $in_stock ? '' : '1'])); ?>">Còn hàng</a>
          <a class="chip <?php echo $on_sale ? 'active' : ''; ?>" href="<?php echo esc_url($build_url(['on_sale' => $on_sale ? '' : '1'])); ?>">Đang giảm giá</a>
          <?php if ($q !== '' || $cat || $brand !== '' || $line !== '' || $in_stock || $on_sale || $sort !== '') : ?>
            <a class="chip" href="<?php echo esc_url($build_url(['q' => '', 'category' => 0, 'brand' => '', 'line' => '', 'in_stock' => '', 'on_sale' => '', 'sort' => ''])); ?>">Xóa tất cả</a>
          <?php endif; ?>
        </div>

        <?php if (!empty($brand_options)) : ?>
          <div class="shop-quick-brands" aria-label="Thương hiệu nhanh">
            <span class="shop-subcats__label">Thương hiệu:</span>
            <a class="chip <?php echo ($brand === '') ? 'active' : ''; ?>" href="<?php echo esc_url($build_url(['brand' => ''])); ?>">Tất cả</a>
            <?php foreach ($brand_options as $brand_slug => $brand_meta) : ?>
              <?php
              $brand_label = isset($brand_meta['label']) ? (string) $brand_meta['label'] : '';
              $brand_count = isset($brand_meta['count']) ? (int) $brand_meta['count'] : 0;
              if ($brand_slug === '' || $brand_label === '') {
                  continue;
              }
              ?>
              <a class="chip <?php echo ($brand === $brand_slug) ? 'active' : ''; ?> <?php echo ($brand_count <= 0) ? 'chip--ghost' : ''; ?>" href="<?php echo esc_url($build_url(['brand' => $brand_slug])); ?>">
                <span><?php echo esc_html($brand_label); ?></span>
                <span class="shop-brand-count"><?php echo esc_html((string) max(0, $brand_count)); ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($line_options) && ($brand !== '' || $line !== '')) : ?>
          <div class="shop-quick-brands shop-quick-lines" aria-label="Dòng sản phẩm nhanh">
            <span class="shop-subcats__label">Dòng:</span>
            <a class="chip <?php echo ($line === '') ? 'active' : ''; ?>" href="<?php echo esc_url($build_url(['line' => ''])); ?>">Tất cả</a>
            <?php foreach ($line_options as $line_slug_option => $line_meta) : ?>
              <?php
              $line_label_option = isset($line_meta['label']) ? trim((string) $line_meta['label']) : '';
              $line_count_option = isset($line_meta['count']) ? (int) $line_meta['count'] : 0;
              $line_slug_option = sanitize_title((string) $line_slug_option);
              if ($line_slug_option === '' || $line_label_option === '') {
                  continue;
              }
              ?>
              <a class="chip <?php echo ($line === $line_slug_option) ? 'active' : ''; ?> <?php echo ($line_count_option <= 0) ? 'chip--ghost' : ''; ?>" href="<?php echo esc_url($build_url(['line' => $line_slug_option])); ?>">
                <span><?php echo esc_html($line_label_option); ?></span>
                <span class="shop-brand-count"><?php echo esc_html((string) max(0, $line_count_option)); ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($top_level_cats)) : ?>
          <div class="shop-quick-brands shop-quick-categories" aria-label="Danh mục nhanh">
            <span class="shop-subcats__label">Danh mục:</span>
            <a class="chip <?php echo (!$cat) ? 'active' : ''; ?>" href="<?php echo esc_url($build_url(['category' => 0])); ?>">Tất cả</a>
            <?php foreach ($top_level_cats as $top_term) : ?>
              <?php
              $top_term_id = isset($top_term['term_id']) ? (int) $top_term['term_id'] : 0;
              $top_term_name = isset($top_term['name']) ? (string) $top_term['name'] : '';
              $top_term_count = isset($top_term['count']) ? (int) $top_term['count'] : 0;
              if ($top_term_id <= 0) {
                  continue;
              }
              ?>
              <a class="chip <?php echo ($cat === $top_term_id) ? 'active' : ''; ?>" href="<?php echo esc_url($build_url(['category' => $top_term_id])); ?>">
                <span><?php echo esc_html($top_term_name); ?></span>
                <span class="shop-brand-count"><?php echo esc_html((string) max(0, $top_term_count)); ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($loop->have_posts()) : ?>
          <ul class="products product-grid product-grid--shop">
            <?php while ($loop->have_posts()) : $loop->the_post(); ?>
              <?php wc_get_template_part('content', 'product'); ?>
            <?php endwhile; ?>
          </ul>
        <?php else : ?>
          <div class="empty-state">
            <h2>Không tìm thấy sản phẩm phù hợp</h2>
            <p>Thử bỏ bớt bộ lọc hoặc chọn nhanh nhóm sản phẩm phổ biến bên dưới.</p>
            <div class="empty-state__actions">
              <a class="btn btn-primary btn-sm" href="<?php echo esc_url($build_url(['q' => '', 'category' => 0, 'brand' => '', 'line' => '', 'in_stock' => '', 'on_sale' => '', 'sort' => ''])); ?>">Xem toàn bộ sản phẩm</a>
              <a class="btn btn-outline btn-sm" href="<?php echo esc_url($build_url(['q' => 'Sơn nội thất', 'category' => 0, 'brand' => '', 'line' => '', 'in_stock' => '', 'on_sale' => '', 'sort' => ''])); ?>">Gợi ý: Nội thất</a>
              <a class="btn btn-outline btn-sm" href="<?php echo esc_url($build_url(['q' => 'Chống thấm', 'category' => 0, 'brand' => '', 'line' => '', 'in_stock' => '', 'on_sale' => '', 'sort' => ''])); ?>">Gợi ý: Chống thấm</a>
            </div>
          </div>
        <?php endif; ?>
        <?php wp_reset_postdata(); ?>

        <?php
        if ($loop->max_num_pages > 1) {
            $pagination_args = [];
            if ($q !== '') {
                $pagination_args['q'] = $q;
            }
            if ($cat) {
                $pagination_args['category'] = $cat;
            }
            if ($brand !== '') {
                $pagination_args['brand'] = $brand;
            }
            if ($line !== '') {
                $pagination_args['line'] = $line;
            }
            if ($sort !== '') {
                $pagination_args['sort'] = $sort;
            }
            if ($in_stock) {
                $pagination_args['in_stock'] = '1';
            }
            if ($on_sale) {
                $pagination_args['on_sale'] = '1';
            }
            echo '<nav class="pagination-wrapper" aria-label="Phân trang sản phẩm">';
            echo paginate_links([
                'total' => (int) $loop->max_num_pages,
                'current' => $current_page,
                'prev_text' => 'Trước',
                'next_text' => 'Sau',
                'add_args' => $pagination_args,
            ]);
            echo '</nav>';
        }
        ?>
      </div>
    </section>
  </div>
</main>

<?php get_footer();
