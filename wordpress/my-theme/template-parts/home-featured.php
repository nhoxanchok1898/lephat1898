<?php
$core_brands = function_exists('my_theme_get_home_brand_priority_slugs')
    ? my_theme_get_home_brand_priority_slugs()
    : ['dulux', 'maxilite', 'weber', 'jotun', 'nippon', 'kova', 'toa', 'sika', 'apollo'];
$shop_page_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
$per_brand_display = 8;

$visible_ids = function_exists('my_theme_get_catalog_visible_product_ids')
    ? my_theme_get_catalog_visible_product_ids(false)
    : [];
if (!is_array($visible_ids) || empty($visible_ids)) {
    $visible_ids = wc_get_products([
        'status' => 'publish',
        'limit' => 420,
        'orderby' => 'date',
        'order' => 'DESC',
        'return' => 'ids',
    ]);
}
$visible_ids = array_values(array_filter(array_map('intval', (array) $visible_ids), function ($id) {
    return $id > 0;
}));
$cache_version = (string) get_option('my_theme_filter_cache_version', '1');
$digest = md5(implode(',', $visible_ids));
$section_cache_key = 'my_theme_home_featured_sections_v4_' . $cache_version . '_' . md5($digest . '|' . $shop_page_url . '|' . (string) $per_brand_display);
$brand_sections_data = get_transient($section_cache_key);

if (!is_array($brand_sections_data)) {
    $brand_sections_data = [];

    $brand_options = function_exists('my_theme_get_brand_filter_options')
        ? my_theme_get_brand_filter_options($visible_ids)
        : [];
    if (!is_array($brand_options)) {
        $brand_options = [];
    }

    $ordered_brand_slugs = [];
    foreach ($core_brands as $core_slug) {
        $core_slug = sanitize_title((string) $core_slug);
        if ($core_slug !== '' && !in_array($core_slug, $ordered_brand_slugs, true)) {
            $ordered_brand_slugs[] = $core_slug;
        }
    }
    foreach ($brand_options as $brand_slug => $brand_meta) {
        $brand_slug = sanitize_title((string) $brand_slug);
        if ($brand_slug !== '' && !in_array($brand_slug, $ordered_brand_slugs, true)) {
            $ordered_brand_slugs[] = $brand_slug;
        }
    }

    foreach ($ordered_brand_slugs as $brand_slug) {
        if ($brand_slug === '' || !function_exists('my_theme_filter_product_ids_by_brand_slug')) {
            continue;
        }

        $brand_ids = my_theme_filter_product_ids_by_brand_slug($visible_ids, $brand_slug);
        $brand_ids = array_values(array_filter(array_map('intval', (array) $brand_ids), function ($id) {
            return $id > 0;
        }));
        if (empty($brand_ids)) {
            continue;
        }

        $brand_label = '';
        if (isset($brand_options[$brand_slug]['label'])) {
            $brand_label = trim((string) $brand_options[$brand_slug]['label']);
        }
        if ($brand_label === '' && function_exists('my_theme_get_brand_label_from_slug')) {
            $brand_label = (string) my_theme_get_brand_label_from_slug($brand_slug);
        }
        if ($brand_label === '') {
            $brand_label = ucfirst($brand_slug);
        }

        $brand_total_count = isset($brand_options[$brand_slug]['count'])
            ? max(0, (int) $brand_options[$brand_slug]['count'])
            : count($brand_ids);
        if ($brand_total_count <= 0) {
            $brand_total_count = count($brand_ids);
        }

        $line_options = function_exists('my_theme_get_line_filter_options')
            ? my_theme_get_line_filter_options($brand_ids)
            : [];
        if (!is_array($line_options)) {
            $line_options = [];
        }
        $line_options = array_slice($line_options, 0, 4, true);

        $line_items = [];
        foreach ($line_options as $line_slug => $line_meta) {
            $line_slug = sanitize_title((string) $line_slug);
            $line_label = isset($line_meta['label']) ? trim((string) $line_meta['label']) : '';
            $line_count = isset($line_meta['count']) ? max(0, (int) $line_meta['count']) : 0;
            if ($line_slug === '' || $line_label === '' || $line_count <= 0) {
                continue;
            }
            $line_items[] = [
                'label' => $line_label,
                'count' => $line_count,
                'url' => add_query_arg(
                    [
                        'brand' => $brand_slug,
                        'line' => $line_slug,
                    ],
                    $shop_page_url
                ),
            ];
        }

        $product_ids = function_exists('my_theme_get_catalog_ranked_product_ids')
            ? my_theme_get_catalog_ranked_product_ids($brand_ids, $per_brand_display)
            : array_slice($brand_ids, 0, $per_brand_display);
        $product_ids = array_values(array_filter(array_map('intval', (array) $product_ids), function ($id) {
            return $id > 0;
        }));
        if (empty($product_ids)) {
            continue;
        }

        $brand_sections_data[] = [
            'slug' => $brand_slug,
            'label' => $brand_label,
            'total_count' => $brand_total_count,
            'url' => add_query_arg('brand', $brand_slug, $shop_page_url),
            'line_items' => $line_items,
            'product_ids' => $product_ids,
        ];
    }

    set_transient($section_cache_key, $brand_sections_data, 30 * MINUTE_IN_SECONDS);
}

$render_product_ids = [];
foreach ($brand_sections_data as $section_data) {
    if (!is_array($section_data) || empty($section_data['product_ids']) || !is_array($section_data['product_ids'])) {
        continue;
    }
    foreach ($section_data['product_ids'] as $pid) {
        $pid = (int) $pid;
        if ($pid > 0) {
            $render_product_ids[$pid] = $pid;
        }
    }
}

$render_product_map = function_exists('my_theme_get_product_object_map')
    ? my_theme_get_product_object_map(array_values($render_product_ids))
    : [];

$brand_sections = [];
foreach ($brand_sections_data as $section_data) {
    if (!is_array($section_data)) {
        continue;
    }

    $section_slug = isset($section_data['slug']) ? sanitize_title((string) $section_data['slug']) : '';
    $section_label = isset($section_data['label']) ? trim((string) $section_data['label']) : '';
    $section_total = isset($section_data['total_count']) ? max(0, (int) $section_data['total_count']) : 0;
    $section_url = isset($section_data['url']) ? (string) $section_data['url'] : add_query_arg('brand', $section_slug, $shop_page_url);
    $section_lines = isset($section_data['line_items']) && is_array($section_data['line_items']) ? $section_data['line_items'] : [];
    $section_product_ids = isset($section_data['product_ids']) && is_array($section_data['product_ids']) ? $section_data['product_ids'] : [];
    if ($section_slug === '' || $section_label === '' || empty($section_product_ids)) {
        continue;
    }

    $section_products = [];
    foreach ($section_product_ids as $pid) {
        $pid = (int) $pid;
        if ($pid <= 0 || !isset($render_product_map[$pid])) {
            continue;
        }
        $product_obj = $render_product_map[$pid];
        if ($product_obj instanceof WC_Product) {
            $section_products[] = $product_obj;
        }
    }
    if (empty($section_products)) {
        continue;
    }

    $brand_sections[] = [
        'slug' => $section_slug,
        'label' => $section_label,
        'total_count' => $section_total,
        'url' => $section_url,
        'line_items' => $section_lines,
        'products' => $section_products,
    ];
}

$catalog_count = count($visible_ids);
$home_featured_base_url = home_url('/');
$requested_featured_brand = isset($_GET['featured_brand']) ? sanitize_title(wp_unslash((string) $_GET['featured_brand'])) : '';
$available_featured_slugs = array_values(array_filter(array_map(function ($section) {
    return isset($section['slug']) ? sanitize_title((string) $section['slug']) : '';
}, $brand_sections)));
$active_featured_slug = '';
if ($requested_featured_brand !== '' && in_array($requested_featured_brand, $available_featured_slugs, true)) {
    $active_featured_slug = $requested_featured_brand;
} elseif (!empty($available_featured_slugs)) {
    $active_featured_slug = (string) $available_featured_slugs[0];
}
?>
<section class="page-section featured-by-brand home-featured-tabs" id="featured-home" data-home-hub>
  <div class="section-heading section-heading--structured">
    <div class="section-heading__main">
      <h2 class="section-title">Sản phẩm nổi bật</h2>
      <p class="section-sub">Chọn thương hiệu để xem ngay các mã nổi bật và vào chi tiết sản phẩm nhanh hơn.</p>
    </div>
    <div class="section-heading__meta" aria-label="Tóm tắt sản phẩm theo hãng">
      <a class="btn btn-outline btn-sm" href="<?php echo esc_url($shop_page_url); ?>">
        <?php echo ($catalog_count > 0) ? esc_html('Xem toàn bộ ' . $catalog_count . ' sản phẩm') : 'Xem toàn bộ sản phẩm'; ?>
      </a>
    </div>
  </div>

  <?php if (!empty($brand_sections)) : ?>
    <nav class="brand-strip home-brand-menu home-featured-tabs__nav" role="tablist" aria-label="Chọn hãng nổi bật">
      <?php foreach ($brand_sections as $index => $section) : ?>
        <?php
        $menu_slug = isset($section['slug']) ? sanitize_title((string) $section['slug']) : '';
        $menu_label = isset($section['label']) ? (string) $section['label'] : '';
        $menu_count = isset($section['total_count']) ? (int) $section['total_count'] : 0;
        $menu_active = ($menu_slug !== '' && $menu_slug === $active_featured_slug) || ($active_featured_slug === '' && $index === 0);
        $menu_url = add_query_arg('featured_brand', $menu_slug, $home_featured_base_url) . '#featured-home';
        if ($menu_slug === '' || $menu_label === '') {
            continue;
        }
        ?>
        <a
          href="<?php echo esc_url($menu_url); ?>"
          role="tab"
          id="<?php echo esc_attr('featured-tab-' . $menu_slug); ?>"
          class="brand-chip home-featured-tabs__tab <?php echo $menu_active ? 'is-active' : ''; ?>"
          data-hub-target="<?php echo esc_attr('featured-brand-' . $menu_slug); ?>"
          data-hub-label="<?php echo esc_attr($menu_label); ?>"
          aria-controls="<?php echo esc_attr('featured-brand-' . $menu_slug); ?>"
          aria-selected="<?php echo $menu_active ? 'true' : 'false'; ?>"
          tabindex="<?php echo $menu_active ? '0' : '-1'; ?>">
          <span><?php echo esc_html($menu_label); ?></span>
          <span class="brand-chip__count"><?php echo esc_html((string) max(0, $menu_count)); ?></span>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="home-featured-tabs__panels">
      <?php foreach ($brand_sections as $index => $section) : ?>
        <?php
        $section_slug = isset($section['slug']) ? sanitize_title((string) $section['slug']) : '';
        $section_label = isset($section['label']) ? (string) $section['label'] : '';
        $section_total = isset($section['total_count']) ? (int) $section['total_count'] : 0;
        $section_url = isset($section['url']) ? (string) $section['url'] : $shop_page_url;
        $section_products = isset($section['products']) && is_array($section['products']) ? $section['products'] : [];
        $section_active = ($section_slug !== '' && $section_slug === $active_featured_slug) || ($active_featured_slug === '' && $index === 0);
        if ($section_slug === '' || $section_label === '' || empty($section_products)) {
            continue;
        }
        $shown_count = count($section_products);
        $remain_count = max(0, $section_total - $shown_count);
        $more_label = $remain_count > 0
            ? ('Xem thêm ' . $remain_count . ' sản phẩm ' . $section_label)
            : ('Xem toàn bộ sản phẩm ' . $section_label);
        ?>
        <article
          id="<?php echo esc_attr('featured-brand-' . $section_slug); ?>"
          class="brand-showcase home-featured-tabs__panel <?php echo $section_active ? 'is-active' : ''; ?>"
          data-hub-panel="<?php echo esc_attr('featured-brand-' . $section_slug); ?>"
          role="tabpanel"
          aria-labelledby="<?php echo esc_attr('featured-tab-' . $section_slug); ?>"
          <?php echo $section_active ? '' : 'hidden'; ?>>
          <div class="brand-showcase__head">
            <div>
              <h3><?php echo esc_html($section_label); ?></h3>
              <p class="brand-showcase__lead"><?php echo esc_html((string) $shown_count); ?> mã nổi bật đang hiển thị cho nhu cầu mua nhanh.</p>
            </div>
            <div class="brand-showcase__summary">
              <span class="brand-showcase__meta"><?php echo esc_html((string) max(0, $section_total)); ?> sản phẩm</span>
              <a class="btn btn-outline btn-sm brand-showcase__jump" href="<?php echo esc_url($section_url); ?>"><?php echo esc_html($more_label); ?></a>
            </div>
          </div>

          <div class="product-grid product-grid--home brand-showcase__grid">
            <?php foreach ($section_products as $product) : ?>
              <?php
              $catalog_profile = function_exists('my_theme_get_product_catalog_profile')
                  ? my_theme_get_product_catalog_profile($product)
                  : [];
              $brand_label = isset($catalog_profile['brand_label']) ? (string) $catalog_profile['brand_label'] : 'Sản phẩm';
              $product_name = isset($catalog_profile['display_name']) && (string) $catalog_profile['display_name'] !== ''
                  ? (string) $catalog_profile['display_name']
                  : $product->get_name();
              $brand_display = ($brand_label !== '' && $brand_label !== 'Sản phẩm') ? (string) $brand_label : '';
              $brand_class = 'product-card__brand' . ($brand_display === '' ? ' product-card__brand--empty' : '');
              $media_state = function_exists('my_theme_get_product_card_media_state')
                  ? my_theme_get_product_card_media_state($product)
                  : [];
              $thumb_id = isset($media_state['thumb_id']) ? (int) $media_state['thumb_id'] : 0;
              $thumb_class = isset($media_state['thumb_class']) ? (string) $media_state['thumb_class'] : 'product-card__thumb';
              $has_placeholder_thumb = !empty($media_state['has_placeholder']);
              $home_actions_class = 'product-card__actions product-card__actions--simple';
              ?>
              <article class="product-card">
                <a
                  class="<?php echo esc_attr($thumb_class); ?>"
                  href="<?php echo esc_url($product->get_permalink()); ?>"
                  aria-label="<?php echo esc_attr('Xem sản phẩm ' . $product_name); ?>"
                  title="<?php echo esc_attr($product_name); ?>">
                  <?php
                  if ($has_placeholder_thumb) {
                      echo wc_placeholder_img('medium_large');
                      echo '<span class="product-card__thumb-note">Ảnh sản phẩm đang cập nhật</span>';
                  } elseif ($thumb_id > 0) {
                      echo wp_get_attachment_image($thumb_id, 'medium_large', false, [
                          'loading' => 'lazy',
                          'decoding' => 'async',
                          'alt' => $product_name,
                          'title' => $product_name,
                      ]);
                  } else {
                      echo wc_placeholder_img('medium_large');
                  }
                  ?>
                </a>
                <div class="product-card__body">
                  <div class="<?php echo esc_attr($brand_class); ?>"<?php echo ($brand_display === '') ? ' aria-hidden="true"' : ''; ?>><?php echo esc_html($brand_display); ?></div>
                  <h3 class="product-card__title"><a href="<?php echo esc_url($product->get_permalink()); ?>"><?php echo esc_html($product_name); ?></a></h3>
                  <?php if (function_exists('my_theme_render_loop_price')) { my_theme_render_loop_price($product); } else { ?><div class="product-card__price"><span class="product-card__price-contact">Liên hệ báo giá</span></div><?php } ?>
                  <?php if (function_exists('my_theme_render_loop_pack_summary')) { my_theme_render_loop_pack_summary($product, true); } ?>
                </div>
                <div class="<?php echo esc_attr($home_actions_class); ?>">
                  <a class="btn btn-primary w-100" href="<?php echo esc_url($product->get_permalink()); ?>">Xem chi tiết</a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else : ?>
    <p class="text-muted">Chưa có sản phẩm.</p>
  <?php endif; ?>
</section>
