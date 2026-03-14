<?php
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop');
$visible_ids = function_exists('my_theme_get_catalog_visible_product_ids')
    ? my_theme_get_catalog_visible_product_ids(false)
    : [];
$visible_ids = array_values(array_filter(array_map('intval', (array) $visible_ids), function ($id) {
    return $id > 0;
}));

$cache_version = (string) get_option('my_theme_filter_cache_version', '1');
$digest = md5(implode(',', $visible_ids));
$hub_cache_key = 'my_theme_home_hub_data_v3_' . $cache_version . '_' . md5($digest . '|' . $shop_url);
$hub_data = get_transient($hub_cache_key);

$brand_cards = [];
$has_cached_brand_cards = is_array($hub_data) && array_key_exists('brand_cards', $hub_data) && is_array($hub_data['brand_cards']);
if ($has_cached_brand_cards) {
    $brand_cards = $hub_data['brand_cards'];
}

$category_cards = [];
$has_cached_category_cards = is_array($hub_data) && array_key_exists('category_cards', $hub_data) && is_array($hub_data['category_cards']);
if ($has_cached_category_cards) {
    $category_cards = $hub_data['category_cards'];
}

$catalog_count = count($visible_ids);
$category_count = function_exists('my_theme_count_visible_product_categories')
    ? (int) my_theme_count_visible_product_categories($visible_ids)
    : 0;

if (!$has_cached_brand_cards) {
    $brand_options = function_exists('my_theme_get_brand_filter_options')
        ? my_theme_get_brand_filter_options($visible_ids)
        : [];
    if (!is_array($brand_options)) {
        $brand_options = [];
    }

    $core_brand_slugs = function_exists('my_theme_get_home_brand_priority_slugs')
        ? my_theme_get_home_brand_priority_slugs()
        : ['dulux', 'maxilite', 'weber', 'jotun', 'nippon', 'kova', 'toa', 'sika', 'apollo'];

    foreach ($core_brand_slugs as $brand_slug) {
        $brand_slug = sanitize_title((string) $brand_slug);
        if ($brand_slug === '') {
            continue;
        }

        $meta = isset($brand_options[$brand_slug]) && is_array($brand_options[$brand_slug]) ? $brand_options[$brand_slug] : [];
        $label = isset($meta['label']) ? trim((string) $meta['label']) : '';
        $count = isset($meta['count']) ? max(0, (int) $meta['count']) : 0;
        if ($label === '' && function_exists('my_theme_get_brand_label_from_slug')) {
            $label = (string) my_theme_get_brand_label_from_slug($brand_slug);
        }
        if ($label === '') {
            $label = ucfirst($brand_slug);
        }
        if ($count <= 0) {
            continue;
        }

        $brand_ids = function_exists('my_theme_filter_product_ids_by_brand_slug')
            ? my_theme_filter_product_ids_by_brand_slug($visible_ids, $brand_slug)
            : [];
        $brand_ids = array_values(array_filter(array_map('intval', (array) $brand_ids), function ($id) {
            return $id > 0;
        }));

        $line_options = function_exists('my_theme_get_line_filter_options')
            ? my_theme_get_line_filter_options($brand_ids, $brand_slug)
            : [];
        if (!is_array($line_options)) {
            $line_options = [];
        }
        $line_options = array_slice($line_options, 0, 3, true);

        $line_preview = [];
        foreach ($line_options as $line_slug => $line_meta) {
            $line_label = isset($line_meta['label']) ? trim((string) $line_meta['label']) : '';
            $line_count = isset($line_meta['count']) ? max(0, (int) $line_meta['count']) : 0;
            if ($line_slug === '' || $line_label === '' || $line_count <= 0) {
                continue;
            }
            $line_preview[] = [
                'url' => add_query_arg(
                    [
                        'brand' => $brand_slug,
                        'line' => sanitize_title((string) $line_slug),
                    ],
                    $shop_url
                ),
                'label' => $line_label,
                'count' => $line_count,
            ];
        }

        $brand_cards[] = [
            'slug' => $brand_slug,
            'label' => $label,
            'count' => $count,
            'url' => add_query_arg('brand', $brand_slug, $shop_url),
            'lines' => $line_preview,
        ];
    }

    if (empty($brand_cards) && !empty($brand_options)) {
        foreach ($brand_options as $brand_slug => $meta) {
            $brand_slug = sanitize_title((string) $brand_slug);
            if ($brand_slug === '') {
                continue;
            }
            $label = isset($meta['label']) ? trim((string) $meta['label']) : ucfirst($brand_slug);
            $count = isset($meta['count']) ? max(0, (int) $meta['count']) : 0;
            if ($count <= 0) {
                continue;
            }
            $brand_cards[] = [
                'slug' => $brand_slug,
                'label' => $label,
                'count' => $count,
                'url' => add_query_arg('brand', $brand_slug, $shop_url),
                'lines' => [],
            ];
            if (count($brand_cards) >= 10) {
                break;
            }
        }
    }

}

if (!$has_cached_category_cards) {
    $category_groups = function_exists('my_theme_get_visible_product_category_groups')
        ? my_theme_get_visible_product_category_groups($visible_ids)
        : [
            'lookup' => [],
            'by_parent' => [],
        ];
    $root_terms = isset($category_groups['by_parent'][0]) && is_array($category_groups['by_parent'][0])
        ? array_values(array_filter($category_groups['by_parent'][0], function ($term) {
            return is_array($term) && !empty($term['term_id']);
        }))
        : [];

    if (!empty($root_terms)) {
        $priority_slugs = function_exists('my_theme_get_home_category_priority_slugs')
            ? my_theme_get_home_category_priority_slugs()
            : [];
        if (!empty($priority_slugs)) {
            $priority_terms = [];
            $seen_term_ids = [];

            foreach ($priority_slugs as $priority_slug) {
                $priority_slug = sanitize_title((string) $priority_slug);
                if ($priority_slug === '') {
                    continue;
                }
                foreach ($root_terms as $term) {
                    $term_slug = isset($term['slug']) ? sanitize_title((string) $term['slug']) : '';
                    $term_id = isset($term['term_id']) ? (int) $term['term_id'] : 0;
                    if ($term_slug !== $priority_slug || $term_id <= 0) {
                        continue;
                    }
                    $priority_terms[] = $term;
                    $seen_term_ids[$term_id] = true;
                    break;
                }
            }

            foreach ($root_terms as $term) {
                $term_id = isset($term['term_id']) ? (int) $term['term_id'] : 0;
                if ($term_id <= 0 || isset($seen_term_ids[$term_id])) {
                    continue;
                }
                $priority_terms[] = $term;
            }

            $root_terms = $priority_terms;
        }

        $visual_groups = function_exists('my_theme_get_visual_story_group_catalog')
            ? my_theme_get_visual_story_group_catalog()
            : [];
        $visual_items_cache = [];
        $root_terms = array_slice($root_terms, 0, 8);

        foreach ($root_terms as $term) {
            $term_id = isset($term['term_id']) ? (int) $term['term_id'] : 0;
            if ($term_id <= 0) {
                continue;
            }

            $child_terms = isset($category_groups['by_parent'][$term_id]) && is_array($category_groups['by_parent'][$term_id])
                ? $category_groups['by_parent'][$term_id]
                : [];
            $child_terms = array_slice($child_terms, 0, 4);

            $children = [];
            foreach ($child_terms as $child_term) {
                $child_term_id = isset($child_term['term_id']) ? (int) $child_term['term_id'] : 0;
                $child_term_name = isset($child_term['name']) ? (string) $child_term['name'] : '';
                if ($child_term_id <= 0 || $child_term_name === '') {
                    continue;
                }
                $children[] = [
                    'label' => $child_term_name,
                    'count' => isset($child_term['count']) ? max(0, (int) $child_term['count']) : 0,
                    'url' => add_query_arg('category', $child_term_id, $shop_url),
                ];
            }

            $category_slug = isset($term['slug']) ? (string) $term['slug'] : '';
            $visual_group_key = ($category_slug !== '' && function_exists('my_theme_get_visual_story_group_key_from_product_category_slug'))
                ? (string) my_theme_get_visual_story_group_key_from_product_category_slug($category_slug)
                : '';
            if ($visual_group_key !== '' && !array_key_exists($visual_group_key, $visual_items_cache)) {
                $visual_items_cache[$visual_group_key] = function_exists('my_theme_get_visual_story_items_by_group')
                    ? my_theme_get_visual_story_items_by_group($visual_group_key)
                    : [];
            }
            $visual_group_meta = ($visual_group_key !== '' && isset($visual_groups[$visual_group_key]) && is_array($visual_groups[$visual_group_key]))
                ? $visual_groups[$visual_group_key]
                : [];
            $visual_items = ($visual_group_key !== '' && isset($visual_items_cache[$visual_group_key]) && is_array($visual_items_cache[$visual_group_key]))
                ? $visual_items_cache[$visual_group_key]
                : [];

            $category_cards[] = [
                'label' => isset($term['name']) ? (string) $term['name'] : '',
                'slug' => $category_slug,
                'count' => isset($term['count']) ? max(0, (int) $term['count']) : 0,
                'url' => add_query_arg('category', $term_id, $shop_url),
                'intro' => function_exists('my_theme_get_category_intro')
                    ? (string) my_theme_get_category_intro($term_id)
                    : '',
                'children' => $children,
                'cover_id' => (!empty($visual_items) && isset($visual_items[0]['attachment_id']))
                    ? (int) $visual_items[0]['attachment_id']
                    : 0,
                'cover_label' => isset($visual_group_meta['label']) ? trim((string) $visual_group_meta['label']) : '',
                'cover_title' => isset($visual_group_meta['title']) ? trim((string) $visual_group_meta['title']) : '',
            ];
        }
    }
}

if (!$has_cached_brand_cards || !$has_cached_category_cards) {
    set_transient($hub_cache_key, [
        'brand_cards' => $brand_cards,
        'category_cards' => $category_cards,
    ], 30 * MINUTE_IN_SECONDS);
}

$brand_preview = array_slice($brand_cards, 0, 8);
?>
<section id="catalog-hub" class="page-section home-hub home-catalog-spotlight">
  <span id="brands" class="home-hub__anchor" aria-hidden="true"></span>
  <span id="categories" class="home-hub__anchor" aria-hidden="true"></span>

  <div class="home-catalog-spotlight__hero">
    <div class="home-catalog-spotlight__copy">
      <p class="eyebrow eyebrow-muted">Danh mục sản phẩm</p>
      <h2 class="section-title">Kho sản phẩm chính hãng</h2>
      <p class="section-sub">Chọn đúng danh mục hoặc thương hiệu để mở ngay nhóm sản phẩm bạn cần.</p>
      <div class="home-catalog-spotlight__stats" aria-label="Tóm tắt kho sản phẩm">
        <span class="home-catalog-spotlight__stat"><strong><?php echo esc_html((string) max(0, $catalog_count)); ?></strong><small>sản phẩm</small></span>
        <span class="home-catalog-spotlight__stat"><strong><?php echo esc_html((string) max(0, $category_count)); ?></strong><small>danh mục</small></span>
        <span class="home-catalog-spotlight__stat"><strong><?php echo esc_html((string) count($brand_cards)); ?></strong><small>thương hiệu</small></span>
      </div>
      <div class="home-catalog-spotlight__actions">
        <a class="btn btn-primary" href="<?php echo esc_url($shop_url); ?>">Mở toàn bộ sản phẩm</a>
      </div>
      <?php if (!empty($brand_preview)) : ?>
        <div class="shop-quick-brands" aria-label="Thương hiệu phổ biến">
          <span class="shop-subcats__label">Thương hiệu phổ biến:</span>
          <?php foreach ($brand_preview as $brand_item) : ?>
            <?php
            $brand_label = isset($brand_item['label']) ? (string) $brand_item['label'] : '';
            $brand_url = isset($brand_item['url']) ? (string) $brand_item['url'] : $shop_url;
            if ($brand_label === '') {
                continue;
            }
            ?>
            <a class="chip" href="<?php echo esc_url($brand_url); ?>"><?php echo esc_html($brand_label); ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($category_cards)) : ?>
    <div class="home-catalog-spotlight__grid">
      <?php foreach ($category_cards as $card) : ?>
        <?php
        $category_label = isset($card['label']) ? (string) $card['label'] : '';
        $category_count = isset($card['count']) ? (int) $card['count'] : 0;
        $category_url = isset($card['url']) ? (string) $card['url'] : $shop_url;
        $category_intro = isset($card['intro']) ? trim((string) $card['intro']) : '';
        $category_children = isset($card['children']) && is_array($card['children']) ? $card['children'] : [];
        $category_cover_id = isset($card['cover_id']) ? (int) $card['cover_id'] : 0;
        $category_cover_label = isset($card['cover_label']) ? trim((string) $card['cover_label']) : '';
        $category_cover_title = isset($card['cover_title']) ? trim((string) $card['cover_title']) : '';
        if ($category_label === '') {
            continue;
        }
        ?>
        <article class="catalog-focus-card">
          <?php if ($category_cover_id > 0) : ?>
            <a class="catalog-focus-card__visual" href="<?php echo esc_url($category_url); ?>" tabindex="-1" aria-hidden="true">
              <?php
              echo wp_get_attachment_image($category_cover_id, 'large', false, [
                  'loading' => 'lazy',
                  'decoding' => 'async',
                  'alt' => $category_label,
              ]);
              ?>
              <?php if ($category_cover_label !== '') : ?>
                <span class="catalog-focus-card__eyebrow"><?php echo esc_html($category_cover_label); ?></span>
              <?php endif; ?>
            </a>
          <?php endif; ?>
          <div class="catalog-focus-card__body">
            <div class="catalog-focus-card__head">
              <h3><?php echo esc_html($category_label); ?></h3>
              <span class="home-hub__count"><?php echo esc_html((string) max(0, $category_count)); ?> sản phẩm</span>
            </div>
            <?php if ($category_cover_title !== '') : ?>
              <p class="catalog-focus-card__scene"><?php echo esc_html($category_cover_title); ?></p>
            <?php endif; ?>
            <?php if ($category_intro !== '') : ?>
              <p class="catalog-focus-card__desc"><?php echo esc_html($category_intro); ?></p>
            <?php endif; ?>
            <?php if (!empty($category_children)) : ?>
              <div class="catalog-focus-card__chips" aria-label="<?php echo esc_attr('Nhóm con của ' . $category_label); ?>">
                <?php foreach ($category_children as $child_item) : ?>
                  <?php
                  $child_label = isset($child_item['label']) ? (string) $child_item['label'] : '';
                  $child_count = isset($child_item['count']) ? (int) $child_item['count'] : 0;
                  $child_url = isset($child_item['url']) ? (string) $child_item['url'] : $category_url;
                  if ($child_label === '') {
                      continue;
                  }
                  ?>
                  <a class="home-hub__chip" href="<?php echo esc_url($child_url); ?>">
                    <span><?php echo esc_html($child_label); ?></span>
                    <?php if ($child_count > 0) : ?><strong><?php echo esc_html((string) $child_count); ?></strong><?php endif; ?>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            <div class="catalog-focus-card__foot">
              <a class="home-hub__cta" href="<?php echo esc_url($category_url); ?>">Xem sản phẩm trong nhóm</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php elseif (!empty($brand_cards)) : ?>
    <div class="home-hub__folder-list home-hub__folder-list--brands">
      <?php foreach ($brand_cards as $card) : ?>
            <?php
            $brand_label = isset($card['label']) ? (string) $card['label'] : '';
            $brand_count = isset($card['count']) ? (int) $card['count'] : 0;
            $brand_url = isset($card['url']) ? (string) $card['url'] : $shop_url;
            $brand_lines = isset($card['lines']) && is_array($card['lines']) ? $card['lines'] : [];
            if ($brand_label === '') {
                continue;
            }
            ?>
        <article class="home-hub__folder home-hub__folder--brand">
          <div class="home-hub__folder-head">
            <h3><?php echo esc_html($brand_label); ?></h3>
            <span class="home-hub__count"><?php echo esc_html((string) max(0, $brand_count)); ?> sản phẩm</span>
          </div>
          <?php if (!empty($brand_lines)) : ?>
            <div class="home-hub__chips" aria-label="Dòng sản phẩm">
              <?php foreach ($brand_lines as $line_item) : ?>
                <?php
                $line_label = isset($line_item['label']) ? (string) $line_item['label'] : '';
                $line_count = isset($line_item['count']) ? (int) $line_item['count'] : 0;
                $line_url = isset($line_item['url']) ? (string) $line_item['url'] : $brand_url;
                if ($line_label === '') {
                    continue;
                }
                ?>
                <a class="home-hub__chip" href="<?php echo esc_url($line_url); ?>">
                  <span><?php echo esc_html($line_label); ?></span>
                  <strong><?php echo esc_html((string) max(0, $line_count)); ?></strong>
                </a>
              <?php endforeach; ?>
            </div>
          <?php else : ?>
            <p class="home-hub__desc">Thư mục sản phẩm đã được chuẩn hóa theo thương hiệu này.</p>
          <?php endif; ?>
          <a class="home-hub__cta" href="<?php echo esc_url($brand_url); ?>">Xem thư mục hãng</a>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else : ?>
    <p class="text-muted">Đang cập nhật danh mục sản phẩm.</p>
  <?php endif; ?>
</section>
