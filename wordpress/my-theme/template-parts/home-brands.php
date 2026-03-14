<?php
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop');
$visible_ids = function_exists('my_theme_get_catalog_visible_product_ids')
    ? my_theme_get_catalog_visible_product_ids(false)
    : [];
$brand_options = function_exists('my_theme_get_brand_filter_options')
    ? my_theme_get_brand_filter_options($visible_ids)
    : [];
$brand_map = function_exists('my_theme_get_brand_keyword_map')
    ? my_theme_get_brand_keyword_map()
    : [];
$core_brand_slugs = ['dulux', 'maxilite', 'weber', 'jotun', 'nippon', 'kova', 'toa', 'sika', 'apollo'];
$brand_notes = [
    'dulux' => 'Sơn nội/ngoại thất, chống thấm và hệ lót phổ biến cho nhà dân dụng.',
    'maxilite' => 'Giải pháp kinh tế cho công trình cần tối ưu chi phí nhưng vẫn bền màu.',
    'weber' => 'Keo dán gạch, chà ron và vật liệu hoàn thiện cho khu vực ẩm ướt.',
    'jotun' => 'Dòng sơn bền màu, phù hợp công trình yêu cầu thẩm mỹ cao.',
    'nippon' => 'Sơn mùi nhẹ, phù hợp hạng mục nội thất cần thi công nhanh.',
    'kova' => 'Sơn và chống thấm phù hợp khí hậu nóng ẩm tại Việt Nam.',
    'toa' => 'Danh mục dân dụng và công nghiệp với nhiều dòng lót, phủ và chống thấm.',
    'sika' => 'Giải pháp chống thấm, sàn epoxy, keo và phụ gia chuyên cho công trình.',
    'apollo' => 'Dòng sơn Apollo đa phân khúc, dễ thi công và tối ưu chi phí cho nhà ở.',
];
$brand_tones = [
    'dulux' => '#1f5fbf',
    'maxilite' => '#0f7c5a',
    'weber' => '#2d4f93',
    'jotun' => '#005f99',
    'nippon' => '#7d3b91',
    'kova' => '#19715d',
    'toa' => '#8c4d15',
    'sika' => '#6a4f13',
    'apollo' => '#275f7c',
    'expo' => '#4f5f8f',
    'insee' => '#7a3f3f',
];

$brand_options = is_array($brand_options) ? $brand_options : [];
$merged_brand_options = [];
foreach ($core_brand_slugs as $core_slug) {
    $meta = isset($brand_options[$core_slug]) && is_array($brand_options[$core_slug]) ? $brand_options[$core_slug] : [];
    $map_label = isset($brand_map[$core_slug]['label']) ? (string) $brand_map[$core_slug]['label'] : ucfirst((string) $core_slug);
    $label = isset($meta['label']) && (string) $meta['label'] !== '' ? (string) $meta['label'] : $map_label;
    $count = isset($meta['count']) ? max(0, (int) $meta['count']) : 0;
    $merged_brand_options[$core_slug] = [
        'label' => $label,
        'count' => $count,
    ];
}
foreach ($brand_options as $slug => $meta) {
    $slug = sanitize_title((string) $slug);
    if ($slug === '' || isset($merged_brand_options[$slug])) {
        continue;
    }
    $label = isset($meta['label']) ? (string) $meta['label'] : ucfirst((string) $slug);
    $count = isset($meta['count']) ? max(0, (int) $meta['count']) : 0;
    $merged_brand_options[$slug] = [
        'label' => $label,
        'count' => $count,
    ];
}
$brand_options = $merged_brand_options;
?>
<section id="brands" class="page-section">
  <div class="section-heading">
    <h2 class="section-title">Kho thương hiệu</h2>
    <p class="section-sub">Phân loại theo từng hãng và dòng sản phẩm để tìm đúng mã hàng nhanh hơn</p>
  </div>

  <div class="brand-grid">
    <?php if (!empty($brand_options)) : ?>
      <?php foreach ($brand_options as $slug => $meta) : ?>
        <?php
        $label = isset($meta['label']) ? (string) $meta['label'] : '';
        $count = isset($meta['count']) ? (int) $meta['count'] : 0;
        $note = isset($brand_notes[$slug]) ? $brand_notes[$slug] : 'Danh mục sản phẩm được phân loại sẵn theo nhu cầu thi công.';
        $tone = isset($brand_tones[$slug]) ? (string) $brand_tones[$slug] : '#1f5fbf';
        if ($label === '' || $slug === '') {
            continue;
        }
        $brand_url = add_query_arg('brand', sanitize_title($slug), $shop_url);

        $brand_ids = [];
        if ($count > 0 && function_exists('my_theme_filter_product_ids_by_brand_slug')) {
            $brand_ids = my_theme_filter_product_ids_by_brand_slug($visible_ids, $slug);
        }
        if (!is_array($brand_ids)) {
            $brand_ids = [];
        }
        $brand_ids = array_values(array_filter(array_unique(array_map('intval', $brand_ids)), function ($id) {
            return $id > 0;
        }));

        $line_options = function_exists('my_theme_get_line_filter_options')
            ? my_theme_get_line_filter_options($brand_ids, $slug)
            : [];
        if (!is_array($line_options)) {
            $line_options = [];
        }
        $line_options = array_slice($line_options, 0, 6, true);

        $top_categories = [];
        if (!empty($brand_ids)) {
            $brand_cat_terms = get_terms([
                'taxonomy' => 'product_cat',
                'hide_empty' => true,
                'object_ids' => $brand_ids,
            ]);
            if (!is_wp_error($brand_cat_terms) && !empty($brand_cat_terms)) {
                $brand_cat_terms = array_values(array_filter($brand_cat_terms, function ($term) {
                    return $term instanceof WP_Term && $term->slug !== 'uncategorized';
                }));
                usort($brand_cat_terms, function ($a, $b) {
                    $ca = (int) ($a->count ?? 0);
                    $cb = (int) ($b->count ?? 0);
                    if ($ca !== $cb) {
                        return ($ca > $cb) ? -1 : 1;
                    }
                    return strnatcasecmp((string) ($a->name ?? ''), (string) ($b->name ?? ''));
                });
                $top_categories = array_slice($brand_cat_terms, 0, 3);
            }
        }
        ?>
        <article class="brand-tile brand-tile--rich <?php echo ($count <= 0) ? 'brand-tile--empty' : ''; ?>" style="--brand-tone: <?php echo esc_attr($tone); ?>;">
          <div class="brand-tile__head">
            <h3><?php echo esc_html($label); ?></h3>
            <span class="brand-tile__meta"><?php echo esc_html((string) max(0, $count)); ?> sản phẩm</span>
          </div>
          <p><?php echo esc_html($note); ?></p>

          <?php if (!empty($top_categories)) : ?>
            <div class="brand-tile__cats" aria-label="Nhóm danh mục phổ biến">
              <?php foreach ($top_categories as $cat_term) : ?>
                <span><?php echo esc_html((string) $cat_term->name); ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($line_options)) : ?>
            <div class="brand-line-list" aria-label="Dòng sản phẩm">
              <?php foreach ($line_options as $line_slug => $line_meta) : ?>
                <?php
                $line_label = isset($line_meta['label']) ? (string) $line_meta['label'] : '';
                $line_count = isset($line_meta['count']) ? (int) $line_meta['count'] : 0;
                if ($line_slug === '' || $line_label === '') {
                    continue;
                }
                $line_url = add_query_arg(
                    [
                        'brand' => sanitize_title($slug),
                        'line' => sanitize_title($line_slug),
                    ],
                    $shop_url
                );
                ?>
                <a class="brand-line-chip" href="<?php echo esc_url($line_url); ?>">
                  <span><?php echo esc_html($line_label); ?></span>
                  <?php if ($line_count > 0) : ?><span class="brand-line-chip__count"><?php echo esc_html((string) $line_count); ?></span><?php endif; ?>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if ($count > 0) : ?>
            <div class="brand-tile__actions">
              <a class="btn btn-primary btn-sm" href="<?php echo esc_url($brand_url); ?>">Xem mã hàng</a>
              <a class="brand-link-arrow" href="<?php echo esc_url($brand_url); ?>">Vào shop</a>
            </div>
          <?php else : ?>
            <div class="brand-tile__actions">
              <span class="brand-empty-note">Đang cập nhật mã hàng cho thương hiệu này</span>
              <a class="btn btn-outline btn-sm" href="<?php echo esc_url(home_url('/lien-he')); ?>">Yêu cầu báo giá</a>
            </div>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    <?php else : ?>
      <div class="text-muted">Đang cập nhật danh mục thương hiệu.</div>
    <?php endif; ?>
  </div>
</section>
