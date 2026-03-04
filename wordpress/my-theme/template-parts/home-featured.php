<?php
$core_brands = ['dulux', 'maxilite', 'weber', 'jotun', 'nippon', 'kova', 'toa', 'sika', 'apollo'];
$shop_page_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
$per_brand_display = 4;

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
$section_cache_key = 'my_theme_home_featured_sections_' . $cache_version . '_' . md5($digest . '|' . $shop_page_url . '|' . (string) $per_brand_display);
$brand_sections_data = get_transient($section_cache_key);

if (!is_array($brand_sections_data)) {
    $brand_sections_data = [];
    $product_map = function_exists('my_theme_get_product_object_map')
        ? my_theme_get_product_object_map($visible_ids)
        : [];

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
            ? my_theme_get_line_filter_options($brand_ids, $brand_slug)
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

        $brand_products_all = [];
        foreach ($brand_ids as $pid) {
            $pid = (int) $pid;
            if ($pid <= 0) {
                continue;
            }
            if (isset($product_map[$pid]) && $product_map[$pid] instanceof WC_Product) {
                $brand_products_all[] = $product_map[$pid];
            }
        }

        if (!empty($brand_products_all)) {
            usort($brand_products_all, function ($a, $b) {
                if (!$a instanceof WC_Product || !$b instanceof WC_Product) {
                    return 0;
                }

                $pa = function_exists('my_theme_get_default_loop_price') ? (float) my_theme_get_default_loop_price($a) : (float) $a->get_price();
                $pb = function_exists('my_theme_get_default_loop_price') ? (float) my_theme_get_default_loop_price($b) : (float) $b->get_price();
                $a_has_price = $pa > 0 ? 1 : 0;
                $b_has_price = $pb > 0 ? 1 : 0;
                if ($a_has_price !== $b_has_price) {
                    return ($a_has_price > $b_has_price) ? -1 : 1;
                }
                if ($pa !== $pb) {
                    return ($pa > $pb) ? -1 : 1;
                }

                $an = function_exists('my_theme_get_product_display_name') ? (string) my_theme_get_product_display_name($a) : (string) $a->get_name();
                $bn = function_exists('my_theme_get_product_display_name') ? (string) my_theme_get_product_display_name($b) : (string) $b->get_name();
                return strnatcasecmp($an, $bn);
            });
        }

        $product_ids = [];
        foreach (array_slice($brand_products_all, 0, $per_brand_display) as $product_obj) {
            if (!$product_obj instanceof WC_Product) {
                continue;
            }
            $product_id = (int) $product_obj->get_id();
            if ($product_id > 0) {
                $product_ids[] = $product_id;
            }
        }
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
?>
<section class="page-section featured-by-brand" id="featured-by-brand">
  <div class="section-heading section-heading--structured">
    <div class="section-heading__main">
      <h2 class="section-title">Sản phẩm theo từng danh mục hãng</h2>
      <p class="section-sub">Xem theo từng hãng: hết Dulux sẽ tới Maxilite, mỗi hãng có nút xem thêm sản phẩm ở cuối.</p>
    </div>
    <div class="section-heading__meta" aria-label="Tóm tắt sản phẩm theo hãng">
      <span class="section-heading__meta-item"><?php echo esc_html((string) count($brand_sections)); ?> hãng đang hiển thị</span>
      <span class="section-heading__meta-item"><?php echo esc_html((string) max(0, $per_brand_display)); ?> mã tiêu biểu mỗi hãng</span>
      <a class="btn btn-outline btn-sm" href="<?php echo esc_url($shop_page_url); ?>">
        <?php echo ($catalog_count > 0) ? esc_html('Xem toàn bộ ' . $catalog_count . ' sản phẩm') : 'Xem toàn bộ sản phẩm'; ?>
      </a>
    </div>
  </div>

  <?php if (!empty($brand_sections)) : ?>
    <nav class="brand-strip home-brand-menu" aria-label="Menu thương hiệu">
      <?php foreach ($brand_sections as $section) : ?>
        <?php
        $menu_slug = isset($section['slug']) ? sanitize_title((string) $section['slug']) : '';
        $menu_label = isset($section['label']) ? (string) $section['label'] : '';
        $menu_count = isset($section['total_count']) ? (int) $section['total_count'] : 0;
        if ($menu_slug === '' || $menu_label === '') {
            continue;
        }
        ?>
        <a class="brand-chip" href="<?php echo esc_url('#featured-brand-' . $menu_slug); ?>">
          <span><?php echo esc_html($menu_label); ?></span>
          <span class="brand-chip__count"><?php echo esc_html((string) max(0, $menu_count)); ?></span>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="brand-showcase-list">
      <?php foreach ($brand_sections as $section) : ?>
        <?php
        $section_slug = isset($section['slug']) ? sanitize_title((string) $section['slug']) : '';
        $section_label = isset($section['label']) ? (string) $section['label'] : '';
        $section_total = isset($section['total_count']) ? (int) $section['total_count'] : 0;
        $section_url = isset($section['url']) ? (string) $section['url'] : $shop_page_url;
        $section_lines = isset($section['line_items']) && is_array($section['line_items']) ? $section['line_items'] : [];
        $section_products = isset($section['products']) && is_array($section['products']) ? $section['products'] : [];
        if ($section_slug === '' || $section_label === '' || empty($section_products)) {
            continue;
        }
        $shown_count = count($section_products);
        $remain_count = max(0, $section_total - $shown_count);
        $more_label = $remain_count > 0
            ? ('Xem thêm ' . $remain_count . ' sản phẩm ' . $section_label)
            : ('Xem toàn bộ sản phẩm ' . $section_label);
        ?>
        <article id="<?php echo esc_attr('featured-brand-' . $section_slug); ?>" class="brand-showcase">
          <div class="brand-showcase__head">
            <h3><?php echo esc_html($section_label); ?></h3>
            <span class="brand-showcase__meta"><?php echo esc_html((string) max(0, $section_total)); ?> sản phẩm</span>
          </div>

          <?php if (!empty($section_lines)) : ?>
            <div class="brand-showcase__lines" aria-label="Dòng sản phẩm">
              <?php foreach ($section_lines as $line_item) : ?>
                <?php
                $line_label = isset($line_item['label']) ? (string) $line_item['label'] : '';
                $line_count = isset($line_item['count']) ? (int) $line_item['count'] : 0;
                $line_url = isset($line_item['url']) ? (string) $line_item['url'] : $section_url;
                if ($line_label === '') {
                    continue;
                }
                ?>
                <a class="brand-showcase__line" href="<?php echo esc_url($line_url); ?>">
                  <span><?php echo esc_html($line_label); ?></span>
                  <?php if ($line_count > 0) : ?><strong><?php echo esc_html((string) $line_count); ?></strong><?php endif; ?>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <div class="product-grid product-grid--home brand-showcase__grid">
            <?php foreach ($section_products as $product) : ?>
              <?php
              $brand_label = function_exists('my_theme_get_product_brand_label') ? my_theme_get_product_brand_label($product) : 'Sản phẩm';
              $line_label = function_exists('my_theme_get_product_line_label') ? my_theme_get_product_line_label($product) : '';
              $cat_label = function_exists('my_theme_get_product_primary_category_label') ? my_theme_get_product_primary_category_label($product) : '';
              $product_name = function_exists('my_theme_get_product_display_name') ? my_theme_get_product_display_name($product) : $product->get_name();
              $brand_display = ($brand_label !== '' && $brand_label !== 'Sản phẩm') ? (string) $brand_label : '';
              $line_display = ($line_label !== '') ? (string) $line_label : '';
              $cat_display = ($cat_label !== '') ? (string) $cat_label : '';
              if ($line_display !== '' && $cat_display !== '') {
                  $line_norm = function_exists('my_theme_normalize_search_text')
                      ? my_theme_normalize_search_text($line_display)
                      : strtolower((string) $line_display);
                  $cat_norm = function_exists('my_theme_normalize_search_text')
                      ? my_theme_normalize_search_text($cat_display)
                      : strtolower((string) $cat_display);
                  if ($line_norm === $cat_norm) {
                      $line_display = '';
                  }
              }
              $excerpt = function_exists('my_theme_get_product_card_excerpt') ? trim((string) my_theme_get_product_card_excerpt($product, 15)) : '';
              $brand_class = 'product-card__brand' . ($brand_display === '' ? ' product-card__brand--empty' : '');
              $line_class = 'product-card__line' . ($line_display === '' ? ' product-card__line--empty' : '');
              $cat_class = 'product-card__taxonomy' . ($cat_display === '' ? ' product-card__taxonomy--empty' : '');
              $thumb_id = function_exists('my_theme_get_preferred_product_image_id')
                  ? (int) my_theme_get_preferred_product_image_id($product)
                  : (int) $product->get_image_id();
              $has_placeholder_thumb = $thumb_id <= 0;
              $thumb_class = 'product-card__thumb';
              if ($thumb_id > 0) {
                  $thumb_meta = wp_get_attachment_metadata($thumb_id);
                  $thumb_w = isset($thumb_meta['width']) ? (int) $thumb_meta['width'] : 0;
                  $thumb_h = isset($thumb_meta['height']) ? (int) $thumb_meta['height'] : 0;
                  if ($thumb_w > 0 && $thumb_h > 0) {
                      if ($thumb_w < 320 || $thumb_h < 320) {
                          $thumb_class .= ' product-card__thumb--small-source';
                      }
                      $thumb_ratio = $thumb_h > 0 ? ((float) $thumb_w / (float) $thumb_h) : 1.0;
                      if ($thumb_ratio > 1.8 || $thumb_ratio < 0.55) {
                          $thumb_class .= ' product-card__thumb--extreme-ratio';
                      }
                  }
              }
              if ($has_placeholder_thumb) {
                  $thumb_class .= ' product-card__thumb--fallback';
              }
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
                  <div class="<?php echo esc_attr($line_class); ?>"<?php echo ($line_display === '') ? ' aria-hidden="true"' : ''; ?>><?php echo esc_html($line_display); ?></div>
                  <h3 class="product-card__title"><a href="<?php echo esc_url($product->get_permalink()); ?>"><?php echo esc_html($product_name); ?></a></h3>
                  <div class="<?php echo esc_attr($cat_class); ?>"<?php echo ($cat_display === '') ? ' aria-hidden="true"' : ''; ?>><?php echo esc_html($cat_display); ?></div>
                  <?php if ($excerpt !== '') : ?><p class="product-card__excerpt"><?php echo esc_html($excerpt); ?></p><?php endif; ?>
                  <?php if (function_exists('my_theme_render_loop_price')) { my_theme_render_loop_price($product); } else { ?><div class="product-card__price"><?php echo wp_kses_post($product->get_price_html()); ?></div><?php } ?>
                  <?php if (function_exists('my_theme_render_loop_pack_summary')) { my_theme_render_loop_pack_summary($product, true); } ?>
                  <?php if (function_exists('my_theme_render_product_colour_swatches')) { my_theme_render_product_colour_swatches($product, ['context' => 'home', 'limit' => 4]); } ?>
                </div>
                <div class="<?php echo esc_attr($home_actions_class); ?>">
                  <a class="btn btn-primary w-100" href="<?php echo esc_url($product->get_permalink()); ?>">Xem chi tiết</a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>

          <div class="brand-showcase__foot">
            <a class="btn btn-outline btn-sm" href="<?php echo esc_url($section_url); ?>"><?php echo esc_html($more_label); ?></a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else : ?>
    <p class="text-muted">Chưa có sản phẩm.</p>
  <?php endif; ?>
</section>
