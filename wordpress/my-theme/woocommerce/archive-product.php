<?php
/** WooCommerce shop template */
get_header();

$q = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
$cat = isset($_GET['category']) ? absint($_GET['category']) : 0;
$brand = isset($_GET['brand']) ? sanitize_text_field(wp_unslash($_GET['brand'])) : '';
$sort = isset($_GET['sort']) ? sanitize_text_field(wp_unslash($_GET['sort'])) : '';
$catalog_visible_ids = function_exists('my_theme_get_catalog_visible_product_ids')
    ? my_theme_get_catalog_visible_product_ids(false)
    : [];
if (empty($catalog_visible_ids)) {
    $catalog_visible_ids = [0];
}

$matched_cat_ids = [];
if (!$cat && $q !== '' && function_exists('my_theme_get_search_matched_product_cat_ids')) {
    $matched_cat_ids = my_theme_get_search_matched_product_cat_ids($q);
}

$tax_query = ['relation' => 'AND'];
if ($cat) {
    $tax_query[] = [
        'taxonomy' => 'product_cat',
        'field'    => 'term_id',
        'terms'    => [$cat],
    ];
}
if ($brand !== '') {
    $tax_query[] = [
        'taxonomy' => 'pa_brand',
        'field'    => 'slug',
        'terms'    => [$brand],
    ];
}
if (!$cat && !empty($matched_cat_ids)) {
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
    'posts_per_page'      => 16,
    'paged'               => max(1, get_query_var('paged')),
    'ignore_sticky_posts' => true,
    's'                   => empty($matched_cat_ids) ? $q : '',
    'post__in'            => $catalog_visible_ids,
];
if (count($tax_query) > 1) {
    $args['tax_query'] = $tax_query;
}

switch ($sort) {
    case 'price_asc':
        $args['orderby'] = 'meta_value_num';
        $args['meta_key'] = '_price';
        $args['order'] = 'ASC';
        break;
    case 'price_desc':
        $args['orderby'] = 'meta_value_num';
        $args['meta_key'] = '_price';
        $args['order'] = 'DESC';
        break;
    case 'name_asc':
        $args['orderby'] = 'title';
        $args['order'] = 'ASC';
        break;
    case 'name_desc':
        $args['orderby'] = 'title';
        $args['order'] = 'DESC';
        break;
    default:
        $args['orderby'] = 'date';
        $args['order'] = 'DESC';
        break;
}

$loop = new WP_Query($args);
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop');

$build_url = function ($overrides = []) use ($shop_url, $q, $cat, $brand, $sort) {
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
    if ($sort !== '') {
        $params['sort'] = $sort;
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

$cat_terms_args = [
    'taxonomy' => 'product_cat',
    'hide_empty' => true,
    'object_ids' => $catalog_visible_ids,
];
$cats = get_terms($cat_terms_args);
if (!is_wp_error($cats) && !empty($cats)) {
    $cats = array_values(array_filter($cats, function ($term) {
        return !empty($term->slug) && $term->slug !== 'uncategorized';
    }));
} else {
    $cats = [];
}

$cat_lookup = [];
$cats_by_parent = [];
foreach ($cats as $term) {
    $cat_lookup[(int) $term->term_id] = $term;
}
foreach ($cats as $term) {
    $parent_id = (int) $term->parent;
    if ($parent_id > 0 && !isset($cat_lookup[$parent_id])) {
        $parent_id = 0;
    }
    if (!isset($cats_by_parent[$parent_id])) {
        $cats_by_parent[$parent_id] = [];
    }
    $cats_by_parent[$parent_id][] = $term;
}
foreach ($cats_by_parent as $pid => $group) {
    if (function_exists('my_theme_sort_product_category_terms')) {
        $group = my_theme_sort_product_category_terms($group);
    } else {
        usort($group, function ($a, $b) {
            return strnatcasecmp($a->name, $b->name);
        });
    }
    $cats_by_parent[$pid] = $group;
}

$active_chain = [];
if ($cat > 0 && isset($cat_lookup[$cat])) {
    $cursor = $cat_lookup[$cat];
    while ($cursor instanceof WP_Term) {
        $active_chain[] = (int) $cursor->term_id;
        if ((int) $cursor->parent <= 0 || !isset($cat_lookup[(int) $cursor->parent])) {
            break;
        }
        $cursor = $cat_lookup[(int) $cursor->parent];
    }
}
$top_level_cats = $cats_by_parent[0] ?? [];
$active_parent_term = null;
$active_children_terms = [];
if ($cat > 0 && isset($cat_lookup[$cat])) {
    $active_term = $cat_lookup[$cat];
    $active_parent_id = ((int) $active_term->parent > 0) ? (int) $active_term->parent : (int) $active_term->term_id;
    if (isset($cat_lookup[$active_parent_id])) {
        $active_parent_term = $cat_lookup[$active_parent_id];
    }
    $active_children_terms = $cats_by_parent[$active_parent_id] ?? [];
}

$render_category_tree = function ($parent_id = 0, $depth = 0) use (&$render_category_tree, $cats_by_parent, $active_chain, $cat, $build_url) {
    if (empty($cats_by_parent[$parent_id])) {
        return '';
    }

    $class = 'shop-cat-list';
    if ($depth > 0) {
        $class .= ' shop-cat-list--sub';
    }

    $html = '<ul class="' . esc_attr($class) . '">';
    foreach ($cats_by_parent[$parent_id] as $term) {
        if (!$term instanceof WP_Term) {
            continue;
        }

        $term_id = (int) $term->term_id;
        $is_active = ((int) $cat === $term_id);
        $is_open = in_array($term_id, $active_chain, true);
        $has_children = !empty($cats_by_parent[$term_id]);
        $children_html = '';
        if ($has_children && ($is_open || $is_active)) {
            $children_html = $render_category_tree($term_id, $depth + 1);
        }

        $item_class = 'shop-cat-item';
        if ($is_active) {
            $item_class .= ' is-active';
        }
        if ($is_open) {
            $item_class .= ' is-open';
        }
        if ($has_children) {
            $item_class .= ' has-children';
        }

        $html .= '<li class="' . esc_attr($item_class) . '">';
        $html .= '<a class="shop-cat-link" href="' . esc_url($build_url(['category' => $term_id])) . '">';
        $html .= '<span>' . esc_html($term->name) . '</span>';
        $html .= '<span class="shop-cat-count">' . esc_html((string) $term->count) . '</span>';
        $html .= '</a>';
        if ($children_html !== '') {
            $html .= $children_html;
        }
        $html .= '</li>';
    }
    $html .= '</ul>';

    return $html;
};

$brand_terms_args = [
    'taxonomy' => 'pa_brand',
    'hide_empty' => true,
    'object_ids' => $catalog_visible_ids,
];
$brands = get_terms($brand_terms_args);
if (is_wp_error($brands) || empty($brands)) {
    $brands = [];
}

$active_brand_label = $brand;
if ($brand !== '' && taxonomy_exists('pa_brand')) {
    $active_brand_term = get_term_by('slug', $brand, 'pa_brand');
    if ($active_brand_term instanceof WP_Term && !empty($active_brand_term->name)) {
        $active_brand_label = $active_brand_term->name;
    }
}

$matched_names = [];
if (!$cat && !empty($matched_cat_ids)) {
    foreach ($matched_cat_ids as $matched_id) {
        $term_obj = get_term((int) $matched_id, 'product_cat');
        if (!is_wp_error($term_obj) && $term_obj instanceof WP_Term) {
            $matched_names[] = $term_obj->name;
        }
    }
}

$current_page = max(1, (int) get_query_var('paged'));
$per_page = max(1, (int) $loop->get('posts_per_page'));
$showing_from = ($loop->found_posts > 0) ? (($current_page - 1) * $per_page) + 1 : 0;
$showing_to = min($loop->found_posts, $current_page * $per_page);
?>

<main id="main-content">
  <div class="container">
    <section class="page-section shop-hero">
      <div class="shop-hero__content">
        <h1>Sơn chính hãng cho thợ và công trình</h1>
        <p>Danh mục rõ ràng, thông tin quy cách minh bạch, tìm kiếm nhanh theo tên vật liệu hoặc nhu cầu thi công.</p>
      </div>
    </section>

    <section class="page-section shop-shell">
      <aside class="shop-sidebar" aria-label="Bộ lọc sản phẩm">
        <div class="shop-sidebar__block">
          <h3 class="shop-sidebar__title">Tìm sản phẩm</h3>
          <form method="get" class="shop-search-form" role="search" aria-label="Tìm sản phẩm">
            <label class="visually-hidden" for="shop-q">Tìm sản phẩm</label>
            <input id="shop-q" class="shop-search-form__input" type="search" name="q" value="<?php echo esc_attr($q); ?>" placeholder="Ví dụ: sơn kim loại, sơn epoxy, bột trét..." />
            <?php if ($cat) : ?><input type="hidden" name="category" value="<?php echo esc_attr($cat); ?>"><?php endif; ?>
            <?php if ($brand !== '') : ?><input type="hidden" name="brand" value="<?php echo esc_attr($brand); ?>"><?php endif; ?>
            <?php if ($sort !== '') : ?><input type="hidden" name="sort" value="<?php echo esc_attr($sort); ?>"><?php endif; ?>
            <button class="btn btn-primary btn-sm w-100" type="submit">Tìm sản phẩm</button>
            <?php if ($q !== '' || $cat || $brand !== '') : ?>
              <a class="btn btn-outline btn-sm w-100" href="<?php echo esc_url($build_url(['q' => '', 'category' => 0, 'brand' => ''])); ?>">Xóa bộ lọc</a>
            <?php endif; ?>
          </form>
        </div>

        <div class="shop-sidebar__block">
          <h3 class="shop-sidebar__title">Danh mục sơn</h3>
          <a class="shop-cat-link shop-cat-link--all <?php echo (!$cat) ? 'is-active' : ''; ?>" href="<?php echo esc_url($build_url(['category' => 0])); ?>">
            <span>Tất cả danh mục</span>
            <span class="shop-cat-count"><?php echo esc_html((string) $loop->found_posts); ?></span>
          </a>
          <?php echo $render_category_tree(0, 0); ?>
        </div>

        <div class="shop-sidebar__block">
          <h3 class="shop-sidebar__title">Thương hiệu</h3>
          <div class="shop-brand-list">
            <a class="chip <?php echo ($brand === '') ? 'active' : ''; ?>" href="<?php echo esc_url($build_url(['brand' => ''])); ?>">Tất cả</a>
            <?php foreach ($brands as $b) : ?>
              <?php if (empty($b->slug) || empty($b->name)) { continue; } ?>
              <a class="chip <?php echo ($brand === $b->slug) ? 'active' : ''; ?>" href="<?php echo esc_url($build_url(['brand' => $b->slug])); ?>"><?php echo esc_html($b->name); ?></a>
            <?php endforeach; ?>
          </div>
        </div>
      </aside>

      <div class="shop-results">
        <nav class="breadcrumb-nav" aria-label="Đường dẫn">
          <ol class="breadcrumb">
            <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
            <li>Cửa hàng</li>
          </ol>
        </nav>

        <div class="shop-results__head">
          <div>
            <h2 class="section-title">Danh sách sản phẩm</h2>
            <p class="section-sub">Hiển thị <?php echo esc_html((string) $showing_from); ?>-<?php echo esc_html((string) $showing_to); ?> trên <?php echo esc_html((string) $loop->found_posts); ?> sản phẩm</p>
          </div>

          <form method="get" class="sort-form">
            <?php if ($q !== '') : ?><input type="hidden" name="q" value="<?php echo esc_attr($q); ?>"><?php endif; ?>
            <?php if ($cat) : ?><input type="hidden" name="category" value="<?php echo esc_attr($cat); ?>"><?php endif; ?>
            <?php if ($brand !== '') : ?><input type="hidden" name="brand" value="<?php echo esc_attr($brand); ?>"><?php endif; ?>
            <label for="sort" class="visually-hidden">Sắp xếp</label>
            <select id="sort" name="sort" class="sort-select" onchange="this.form.submit()">
              <option value="">Mới nhất</option>
              <option value="price_asc" <?php selected($sort, 'price_asc'); ?>>Giá thấp đến cao</option>
              <option value="price_desc" <?php selected($sort, 'price_desc'); ?>>Giá cao đến thấp</option>
              <option value="name_asc" <?php selected($sort, 'name_asc'); ?>>Tên A-Z</option>
              <option value="name_desc" <?php selected($sort, 'name_desc'); ?>>Tên Z-A</option>
            </select>
          </form>
        </div>

        <?php if (!empty($top_level_cats)) : ?>
          <div class="shop-quick-cats" aria-label="Danh mục nhanh">
            <a class="chip <?php echo !$cat ? 'active' : ''; ?>" href="<?php echo esc_url($build_url(['category' => 0])); ?>">Tất cả</a>
            <?php foreach ($top_level_cats as $top_cat) : ?>
              <?php if (!$top_cat instanceof WP_Term) { continue; } ?>
              <a class="chip <?php echo ((int) $cat === (int) $top_cat->term_id) ? 'active' : ''; ?>" href="<?php echo esc_url($build_url(['category' => (int) $top_cat->term_id])); ?>">
                <?php echo esc_html($top_cat->name); ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($active_children_terms) && $active_parent_term instanceof WP_Term) : ?>
          <div class="shop-subcats" aria-label="Danh mục con">
            <span class="shop-subcats__label"><?php echo esc_html($active_parent_term->name); ?>:</span>
            <a class="chip <?php echo ((int) $cat === (int) $active_parent_term->term_id) ? 'active' : ''; ?>" href="<?php echo esc_url($build_url(['category' => (int) $active_parent_term->term_id])); ?>">Tất cả nhóm này</a>
            <?php foreach ($active_children_terms as $child_term) : ?>
              <?php if (!$child_term instanceof WP_Term) { continue; } ?>
              <a class="chip <?php echo ((int) $cat === (int) $child_term->term_id) ? 'active' : ''; ?>" href="<?php echo esc_url($build_url(['category' => (int) $child_term->term_id])); ?>">
                <?php echo esc_html($child_term->name); ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($q !== '' || $cat || $brand !== '' || !empty($matched_names)) : ?>
          <div class="shop-active-filters">
            <?php if ($q !== '') : ?><span class="chip chip--soft">Từ khóa: <?php echo esc_html($q); ?></span><?php endif; ?>
            <?php if ($cat && isset($cat_lookup[$cat])) : ?><span class="chip chip--soft">Danh mục: <?php echo esc_html($cat_lookup[$cat]->name); ?></span><?php endif; ?>
            <?php if ($brand !== '') : ?><span class="chip chip--soft">Thương hiệu: <?php echo esc_html($active_brand_label); ?></span><?php endif; ?>
            <?php if (!$cat && !empty($matched_names)) : ?><span class="chip chip--soft">Gợi ý theo nhu cầu: <?php echo esc_html(implode(', ', array_unique($matched_names))); ?></span><?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ($loop->have_posts()) : ?>
          <ul class="products product-grid product-grid--shop">
            <?php while ($loop->have_posts()) : $loop->the_post(); ?>
              <?php wc_get_template_part('content', 'product'); ?>
            <?php endwhile; ?>
          </ul>
        <?php else : ?>
          <p class="text-muted">Không tìm thấy sản phẩm phù hợp với bộ lọc hiện tại.</p>
        <?php endif; ?>
        <?php wp_reset_postdata(); ?>

        <?php
        if ($loop->max_num_pages > 1) {
            echo '<nav class="pagination-wrapper" aria-label="Phân trang sản phẩm">';
            echo paginate_links([
                'total' => (int) $loop->max_num_pages,
                'current' => $current_page,
                'prev_text' => 'Trước',
                'next_text' => 'Sau',
            ]);
            echo '</nav>';
        }
        ?>
      </div>
    </section>
  </div>
</main>

<?php get_footer();
