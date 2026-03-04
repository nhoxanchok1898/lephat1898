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
$hub_cache_key = 'my_theme_home_hub_data_v2_' . $cache_version . '_' . md5($digest . '|' . $shop_url);
$hub_data = get_transient($hub_cache_key);

$brand_cards = [];
if (is_array($hub_data) && isset($hub_data['brand_cards']) && is_array($hub_data['brand_cards'])) {
    $brand_cards = $hub_data['brand_cards'];
}

if (empty($brand_cards)) {
    $brand_options = function_exists('my_theme_get_brand_filter_options')
        ? my_theme_get_brand_filter_options($visible_ids)
        : [];
    if (!is_array($brand_options)) {
        $brand_options = [];
    }

    $core_brand_slugs = ['dulux', 'maxilite', 'weber', 'jotun', 'nippon', 'kova', 'toa', 'sika', 'apollo'];

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

    set_transient($hub_cache_key, [
        'brand_cards' => $brand_cards,
    ], 30 * MINUTE_IN_SECONDS);
}
?>
<section id="catalog-hub" class="page-section home-hub">
  <span id="brands" class="home-hub__anchor" aria-hidden="true"></span>
  <span id="categories" class="home-hub__anchor" aria-hidden="true"></span>

  <div class="section-heading section-heading--structured">
    <div class="section-heading__main">
      <h2 class="section-title">Danh mục</h2>
      <p class="section-sub">Hiển thị gọn theo từng hãng để chọn sản phẩm nhanh, ít rối hơn.</p>
    </div>
    <div class="section-heading__meta" aria-label="Tóm tắt danh mục">
      <span class="section-heading__meta-item"><?php echo esc_html((string) count($brand_cards)); ?> hãng chính</span>
      <span class="section-heading__meta-item">Có line preview theo từng hãng</span>
      <a class="btn btn-outline btn-sm" href="<?php echo esc_url($shop_url); ?>">Mở toàn bộ kho</a>
    </div>
  </div>

  <div class="home-hub__panels">
    <div class="home-hub__panel is-active" data-hub-panel="brands">
      <?php if (!empty($brand_cards)) : ?>
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
        <p class="text-muted">Đang cập nhật nhóm thương hiệu.</p>
      <?php endif; ?>
    </div>
  </div>
</section>
