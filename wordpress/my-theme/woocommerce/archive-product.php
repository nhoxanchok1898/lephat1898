<?php
/** WooCommerce shop template */
get_header();

$q = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
$cat = isset($_GET['category']) ? absint($_GET['category']) : 0;
$brand = isset($_GET['brand']) ? sanitize_title(wp_unslash($_GET['brand'])) : '';
$line = isset($_GET['line']) ? sanitize_title(wp_unslash($_GET['line'])) : '';
$sort = isset($_GET['sort']) ? sanitize_text_field(wp_unslash($_GET['sort'])) : '';
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
    'posts_per_page'      => 24,
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

$build_url = function ($overrides = []) use ($shop_url, $q, $cat, $brand, $line, $sort) {
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

$active_brand_label = $brand;
if ($brand !== '' && isset($brand_options[$brand]['label'])) {
    $active_brand_label = (string) $brand_options[$brand]['label'];
}
$active_line_label = ($line !== '' && function_exists('my_theme_get_line_label_from_slug'))
    ? my_theme_get_line_label_from_slug($line)
    : $line;

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
$brand_total_count = count($brand_options);
$line_total_count = count($line_options);

$top_level_cats = isset($cats_by_parent[0]) && is_array($cats_by_parent[0])
    ? array_values(array_filter($cats_by_parent[0], function ($term) {
        return $term instanceof WP_Term;
    }))
    : [];
if (!empty($top_level_cats)) {
    $top_level_cats = array_slice($top_level_cats, 0, 12);
}

$shop_solution_group = '';
$shop_solution = [];
$shop_quick_answers = [];
$shop_article_slugs = [];
if ($cat > 0 && function_exists('my_theme_get_visual_story_group_key_from_product_category_slug')) {
    $shop_cat_term = get_term($cat, 'product_cat');
    if (!is_wp_error($shop_cat_term) && $shop_cat_term instanceof WP_Term) {
        $shop_solution_group = my_theme_get_visual_story_group_key_from_product_category_slug($shop_cat_term->slug);
        if ($shop_solution_group === '' && (int) $shop_cat_term->parent > 0) {
            $shop_parent_term = get_term((int) $shop_cat_term->parent, 'product_cat');
            if (!is_wp_error($shop_parent_term) && $shop_parent_term instanceof WP_Term) {
                $shop_solution_group = my_theme_get_visual_story_group_key_from_product_category_slug($shop_parent_term->slug);
            }
        }
    }
}
if ($shop_solution_group !== '' && function_exists('my_theme_get_visual_story_group_catalog')) {
    $shop_solution_catalog = my_theme_get_visual_story_group_catalog();
    if (isset($shop_solution_catalog[$shop_solution_group])) {
        $shop_solution = (array) $shop_solution_catalog[$shop_solution_group];
    }
}
if ($shop_solution_group !== '' && function_exists('my_theme_get_group_knowledge_bundle')) {
    $shop_knowledge_bundle = (array) my_theme_get_group_knowledge_bundle($shop_solution_group);
    $shop_quick_answers = isset($shop_knowledge_bundle['quick_answers']) && is_array($shop_knowledge_bundle['quick_answers'])
        ? $shop_knowledge_bundle['quick_answers']
        : [];
    $shop_article_slugs = isset($shop_knowledge_bundle['article_slugs']) && is_array($shop_knowledge_bundle['article_slugs'])
        ? $shop_knowledge_bundle['article_slugs']
        : [];
}

$shop_support_links = [
    ['label' => 'Giải pháp', 'url' => home_url('/giai-phap')],
    ['label' => 'Tính sơn', 'url' => home_url('/#tinh-son')],
    ['label' => 'Hướng dẫn mua hàng', 'url' => home_url('/huong-dan-mua-hang')],
    ['label' => 'FAQ', 'url' => home_url('/faq')],
    ['label' => 'Liên hệ kỹ thuật', 'url' => home_url('/lien-he')],
];
?>

<main id="main-content">
  <div class="container">
    <section class="page-section shop-summary">
      <div class="shop-summary__layout">
        <div class="shop-summary__lead">
          <div class="shop-summary__head">
            <h1>Kho sản phẩm sơn chính hãng</h1>
            <p>Tìm nhanh theo từ khóa, thương hiệu và danh mục; giao diện gọn để xem sản phẩm trực tiếp.</p>
          </div>
          <div class="shop-summary__stats" aria-label="Tổng quan danh mục">
            <span><?php echo esc_html((string) $loop->found_posts); ?> mã hàng</span>
            <span><?php echo esc_html((string) $brand_total_count); ?> thương hiệu</span>
            <span><?php echo esc_html((string) $line_total_count); ?> dòng sản phẩm</span>
          </div>
          <div class="shop-summary__support" aria-label="Lối tắt hỗ trợ">
            <?php foreach ($shop_support_links as $support_link) : ?>
              <a class="chip" href="<?php echo esc_url($support_link['url']); ?>"><?php echo esc_html($support_link['label']); ?></a>
            <?php endforeach; ?>
          </div>
        </div>
        <aside class="shop-summary__panel" aria-label="Cách đi nhanh trong cửa hàng">
          <h2 class="shop-summary__panel-title">Đi nhanh theo đúng nhu cầu</h2>
          <ul class="list-plain shop-summary__panel-list">
            <li>Nếu đã có mã hoặc hãng: dùng bộ lọc và mở sản phẩm ngay.</li>
            <li>Nếu chọn theo hạng mục: sang phần giải pháp để đỡ lọc lan man.</li>
            <li>Nếu chưa chắc hệ vật tư: gửi ảnh hiện trạng để đội kỹ thuật điều hướng lại.</li>
          </ul>
          <div class="shop-summary__panel-actions">
            <a class="btn btn-primary btn-sm" href="<?php echo esc_url(home_url('/giai-phap')); ?>">Mở giải pháp</a>
            <a class="btn btn-outline btn-sm" href="<?php echo esc_url(home_url('/lien-he')); ?>">Gửi yêu cầu</a>
          </div>
        </aside>
      </div>
      <?php if (!empty($shop_solution)) : ?>
        <div class="shop-solution-bridge" aria-label="Giải pháp liên quan theo danh mục đang xem">
          <div class="shop-solution-bridge__content">
            <div>
              <strong><?php echo esc_html((string) ($shop_solution['label'] ?? 'Giải pháp liên quan')); ?></strong>
              <p><?php echo esc_html((string) ($shop_solution['description'] ?? '')); ?></p>
            </div>
            <div class="shop-solution-bridge__actions">
              <a class="btn btn-outline btn-sm" href="<?php echo esc_url((string) ($shop_solution['url'] ?? home_url('/giai-phap'))); ?>">
                <?php echo esc_html((string) ($shop_solution['cta'] ?? 'Xem giải pháp')); ?>
              </a>
              <a class="btn btn-accent btn-sm" href="<?php echo esc_url(home_url('/giai-phap')); ?>">Mở tất cả giải pháp</a>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </section>

    <?php
    if (function_exists('my_theme_render_service_compass')) {
        my_theme_render_service_compass([
            'class' => 'service-compass--shop',
            'eyebrow' => 'Nếu chưa chắc cách chọn',
            'title' => 'Từ cửa hàng, bạn có thể tiếp tục theo 3 đường đi rõ ràng',
            'subtitle' => 'Tiếp tục lọc sản phẩm nếu đã có mã. Đi vào giải pháp nếu đang chọn theo bề mặt. Hoặc gửi ảnh hiện trạng để đội kỹ thuật chốt lại hệ vật tư giúp bạn.',
        ]);
    }

    if (!empty($shop_quick_answers) && function_exists('my_theme_render_quick_answers')) {
        my_theme_render_quick_answers([
            'cards' => $shop_quick_answers,
            'title' => 'Một vài câu hỏi nên chốt ngay ở danh mục này',
            'subtitle' => 'Các câu hỏi ngắn dưới đây giúp bạn lọc tiếp đúng hướng trước khi mở từng mã sản phẩm.',
            'class' => 'quick-answers--shop',
            'eyebrow' => 'Chốt nhanh theo danh mục',
        ]);
    }

    if ($shop_solution_group !== '' && function_exists('my_theme_render_product_companion_paths')) {
        my_theme_render_product_companion_paths($shop_solution_group, null, [
            'class' => 'product-companion-paths--shop',
            'title' => 'Nhóm vật tư nên mở tiếp từ danh mục này',
            'subtitle' => 'Nếu bạn đang đi theo hạng mục thay vì một mã cụ thể, có thể mở thêm các nhóm dưới đây để chốt đủ hệ nhanh hơn.',
        ]);
    }
    ?>

    <section class="page-section shop-shell">
      <aside class="shop-sidebar" aria-label="Bộ lọc sản phẩm">
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
              <button class="btn btn-primary btn-sm w-100" type="submit">Tìm sản phẩm</button>
              <?php if ($q !== '' || $cat || $brand !== '' || $line !== '') : ?>
                <a class="btn btn-outline btn-sm w-100" href="<?php echo esc_url($build_url(['q' => '', 'category' => 0, 'brand' => '', 'line' => ''])); ?>">Xóa bộ lọc</a>
              <?php endif; ?>
            </form>
            <?php
            if (function_exists('my_theme_render_search_assist')) {
                my_theme_render_search_assist('shop');
            }
            ?>
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
            <?php if ($line !== '') : ?><input type="hidden" name="line" value="<?php echo esc_attr($line); ?>"><?php endif; ?>
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

        <?php if (!empty($top_level_cats)) : ?>
          <div class="shop-quick-brands shop-quick-categories" aria-label="Danh mục nhanh">
            <span class="shop-subcats__label">Danh mục:</span>
            <a class="chip <?php echo (!$cat) ? 'active' : ''; ?>" href="<?php echo esc_url($build_url(['category' => 0])); ?>">Tất cả</a>
            <?php foreach ($top_level_cats as $top_term) : ?>
              <?php
              $top_term_id = (int) $top_term->term_id;
              if ($top_term_id <= 0) {
                  continue;
              }
              ?>
              <a class="chip <?php echo ($cat === $top_term_id) ? 'active' : ''; ?>" href="<?php echo esc_url($build_url(['category' => $top_term_id])); ?>">
                <span><?php echo esc_html($top_term->name); ?></span>
                <span class="shop-brand-count"><?php echo esc_html((string) max(0, (int) $top_term->count)); ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($q !== '' || $cat || $brand !== '' || $line !== '' || !empty($matched_names)) : ?>
          <div class="shop-active-filters">
            <?php if ($q !== '') : ?><span class="chip chip--soft">Từ khóa: <?php echo esc_html($q); ?></span><?php endif; ?>
            <?php if ($cat && isset($cat_lookup[$cat])) : ?><span class="chip chip--soft">Danh mục: <?php echo esc_html($cat_lookup[$cat]->name); ?></span><?php endif; ?>
            <?php if ($brand !== '') : ?><span class="chip chip--soft">Thương hiệu: <?php echo esc_html($active_brand_label); ?></span><?php endif; ?>
            <?php if ($line !== '') : ?><span class="chip chip--soft">Dòng: <?php echo esc_html($active_line_label); ?></span><?php endif; ?>
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

    <?php if (!empty($shop_article_slugs) && function_exists('my_theme_render_article_recommendations')) : ?>
      <?php my_theme_render_article_recommendations($shop_article_slugs, [
        'title' => 'Bài nên đọc trước khi chốt nhóm vật tư này',
        'subtitle' => 'Nếu bạn đang chọn theo bề mặt hoặc công năng thay vì một mã cụ thể, các bài tư vấn này sẽ giúp khoanh nhanh hệ vật tư phù hợp hơn.',
        'class' => 'article-recommendations--shop',
      ]); ?>
    <?php endif; ?>

    <?php
    $shop_lead_title = !empty($shop_solution['label'])
        ? 'Chưa chắc nên lấy mã nào trong nhóm ' . (string) $shop_solution['label'] . '?'
        : 'Chưa chắc nên chọn mã nào trong kho sản phẩm?';
    $shop_lead_subtitle = ($brand !== '' || $line !== '' || $cat > 0)
        ? 'Gửi hãng, dòng, danh mục hoặc hiện trạng bề mặt đang cân nhắc. Đội kỹ thuật sẽ giúp bạn rút gọn danh sách mã cần xem và báo giá sát hơn.'
        : 'Gửi bề mặt, diện tích, thương hiệu đang cân nhắc và thời gian cần hàng. Đội kỹ thuật sẽ điều hướng bạn vào đúng nhóm sản phẩm hoặc landing phù hợp.';
    if (function_exists('my_theme_render_lead_capture_form')) {
        echo my_theme_render_lead_capture_form([
            'source' => 'shop-page',
            'title' => $shop_lead_title,
            'subtitle' => $shop_lead_subtitle,
            'button' => 'Nhận tư vấn chọn mã',
        ]);
    }
    ?>

    <?php
    if (function_exists('my_theme_render_recently_viewed_products')) {
        my_theme_render_recently_viewed_products([
            'title' => 'Sản phẩm bạn vừa xem gần đây',
            'aria_label' => 'Sản phẩm bạn vừa xem gần đây',
            'class' => 'related-products-block--recently-viewed related-products-block--shop',
        ]);
    }
    ?>
  </div>
</main>

<?php get_footer();
