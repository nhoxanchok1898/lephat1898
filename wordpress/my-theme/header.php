<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php if (!function_exists('has_site_icon') || !has_site_icon()) : ?>
    <?php $theme_favicon_url = get_theme_file_uri('assets/logo-phat-tan.svg'); ?>
    <link rel="icon" href="<?php echo esc_url($theme_favicon_url); ?>" sizes="any">
    <link rel="shortcut icon" href="<?php echo esc_url($theme_favicon_url); ?>">
  <?php endif; ?>
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php
$header_snapshot = function_exists('my_theme_get_store_snapshot') ? my_theme_get_store_snapshot() : [];
$header_phone_display = isset($header_snapshot['phone_display']) ? (string) $header_snapshot['phone_display'] : '0944 857 999';
$header_phone_href = isset($header_snapshot['phone_href']) ? (string) $header_snapshot['phone_href'] : 'tel:0944857999';
$header_email = isset($header_snapshot['email']) ? (string) $header_snapshot['email'] : 'lephat1898@gmail.com';
$header_hours_display = isset($header_snapshot['hours_display']) ? (string) $header_snapshot['hours_display'] : 'Thứ 2 - Thứ 7: 7:30 - 18:00';
$header_service_areas = isset($header_snapshot['service_areas_display']) ? (string) $header_snapshot['service_areas_display'] : 'TP.HCM, Bình Dương, Đồng Nai';
$header_zalo_url = isset($header_snapshot['zalo_url']) ? (string) $header_snapshot['zalo_url'] : 'https://zalo.me/0944857999';
?>
<a class="skip-link" href="#main-content">Bỏ qua menu, đến nội dung chính</a>
<div id="top"></div>
<header class="site-header">
  <div class="header-top">
    <div class="container header-top__inner">
      <div class="header-top__commit">
        <span class="badge-pill">Đại lý chính hãng</span>
        <span class="badge-pill">Hàng mới 100%</span>
        <span class="badge-pill">Hỗ trợ kỹ thuật</span>
      </div>
      <div class="header-top__contact">
        <a class="hotline" href="<?php echo esc_url($header_phone_href); ?>">Báo giá nhanh: <?php echo esc_html($header_phone_display); ?></a>
        <a class="btn btn-outline btn-sm" href="<?php echo esc_url($header_zalo_url); ?>" target="_blank" rel="noopener">Zalo tư vấn</a>
      </div>
    </div>
  </div>

  <div class="container header-main">
    <a class="brand" href="<?php echo esc_url(home_url('/')); ?>">
      <span class="brand-mark">
        <img src="<?php echo esc_url(get_theme_file_uri('assets/logo-phat-tan.svg')); ?>" alt="Logo Phát Tấn" width="52" height="52" loading="eager" decoding="async" fetchpriority="high">
      </span>
      <span class="brand-copy">
        <span class="brand-name">Đại Lý Sơn Phát Tấn</span>
        <span class="brand-tagline">Sơn nước, chống thấm, epoxy và vật liệu hoàn thiện chính hãng</span>
      </span>
    </a>

    <?php
      $shop_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
      $calculator_url = function_exists('my_theme_get_paint_calculator_url') ? my_theme_get_paint_calculator_url() : home_url('/tinh-son');
      $cart_url = function_exists('my_theme_get_cart_url_safe') ? my_theme_get_cart_url_safe() : $shop_url;
      $account_url = function_exists('my_theme_get_account_url') ? my_theme_get_account_url() : wp_login_url();
      $account_login_url = function_exists('my_theme_get_account_login_url') ? my_theme_get_account_login_url() : add_query_arg('login', '1', $account_url);
      $shop_q = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
      $header_search_supports_assist = !(function_exists('my_theme_is_core_woocommerce_page') && my_theme_is_core_woocommerce_page());
    ?>
    <div class="header-search-wrap"<?php echo $header_search_supports_assist ? ' data-search-assist-root="header"' : ''; ?>>
      <form class="header-search" method="get" action="<?php echo esc_url($shop_url); ?>" role="search" aria-label="Tìm kiếm sản phẩm">
        <label class="visually-hidden" for="header-search-q">Tìm kiếm sản phẩm</label>
        <input id="header-search-q" type="search" name="q" value="<?php echo esc_attr($shop_q); ?>" placeholder="Tìm sơn kim loại, sơn epoxy, bột trét..." autocomplete="off" />
        <button type="submit" class="header-search__btn">Tìm</button>
      </form>
      <?php
      if ($header_search_supports_assist && function_exists('my_theme_render_search_assist')) {
          my_theme_render_search_assist('header');
      }
      ?>
    </div>

    <div class="header-utility">
      <?php
      $cart_count = 0;
      if (function_exists('WC') && WC()->cart) {
          $cart_count = max(0, (int) WC()->cart->get_cart_contents_count());
      }
      ?>
      <a class="utility-pill utility-pill--primary" href="<?php echo esc_url($calculator_url); ?>">Tính sơn</a>
      <a class="utility-pill utility-pill--cart" href="<?php echo esc_url($cart_url); ?>">
        <span>Giỏ hàng</span>
        <?php if ($cart_count > 0) : ?><span class="utility-pill__count"><?php echo esc_html((string) $cart_count); ?></span><?php endif; ?>
      </a>
      <?php if (is_user_logged_in()): ?>
        <a class="utility-pill" href="<?php echo esc_url($account_url); ?>">Tài khoản</a>
      <?php else: ?>
        <a class="utility-pill" href="<?php echo esc_url($account_login_url); ?>">Đăng nhập</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="header-nav-wrap">
    <div class="container header-nav">
      <nav class="main-nav" id="primary-menu" aria-label="<?php esc_attr_e('Menu chính','my-custom-theme'); ?>">
        <button class="menu-toggle" type="button" aria-controls="primary-menu-list" aria-expanded="false">
          <span class="menu-toggle__bars" aria-hidden="true">
            <span></span>
            <span></span>
            <span></span>
          </span>
          <span class="menu-toggle__label">Menu</span>
        </button>
        <?php
        wp_nav_menu([
          'theme_location' => 'primary',
          'container' => false,
          'menu_class' => 'menu main-menu',
          'items_wrap' => '<ul id="primary-menu-list" class="menu main-menu">%3$s</ul>',
          'fallback_cb' => 'my_theme_fallback_menu',
        ]);
        ?>
      </nav>
    </div>
  </div>
</header>
<?php
$show_catalog_dock = false;

$catalog_support_links = [
    ['label' => 'Tính sơn', 'url' => function_exists('my_theme_get_paint_calculator_url') ? my_theme_get_paint_calculator_url() : home_url('/tinh-son')],
    ['label' => 'Giải pháp', 'url' => home_url('/giai-phap')],
    ['label' => 'Sơn nội thất', 'url' => home_url('/giai-phap-son-noi-that')],
    ['label' => 'Sơn ngoại thất', 'url' => home_url('/giai-phap-son-ngoai-that')],
    ['label' => 'Chống thấm', 'url' => home_url('/giai-phap-chong-tham')],
    ['label' => 'Sơn epoxy', 'url' => home_url('/giai-phap-son-epoxy')],
    ['label' => 'Sơn kim loại', 'url' => home_url('/giai-phap-son-kim-loai')],
    ['label' => 'Keo & ron', 'url' => home_url('/giai-phap-keo-va-ron')],
    ['label' => 'Hướng dẫn mua hàng', 'url' => home_url('/huong-dan-mua-hang')],
    ['label' => 'FAQ', 'url' => home_url('/faq')],
    ['label' => 'Liên hệ kỹ thuật', 'url' => home_url('/lien-he')],
];

$catalog_dock_data = [
    'all_items' => [],
    'brands' => [],
];

if ($show_catalog_dock && function_exists('my_theme_get_catalog_visible_product_ids')) {
    $dock_visible_ids = my_theme_get_catalog_visible_product_ids(false);
    $dock_visible_ids = array_values(array_filter(array_map('intval', (array) $dock_visible_ids), function ($id) {
        return $id > 0;
    }));

    if (!empty($dock_visible_ids)) {
        $dock_cache_version = (string) get_option('my_theme_filter_cache_version', '1');
        $dock_digest = md5(implode(',', $dock_visible_ids));
        $dock_cache_key = 'my_theme_header_catalog_dock_v1_' . $dock_cache_version . '_' . $dock_digest;
        $dock_cached = get_transient($dock_cache_key);

        if (is_array($dock_cached)) {
            $catalog_dock_data = $dock_cached;
        } else {
            $dock_shop_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
            $dock_brand_options = function_exists('my_theme_get_brand_filter_options')
                ? my_theme_get_brand_filter_options($dock_visible_ids)
                : [];
            if (!is_array($dock_brand_options)) {
                $dock_brand_options = [];
            }

            $dock_core_brand_slugs = ['dulux', 'jotun', 'toa', 'kova', 'nippon', 'maxilite', 'weber', 'sika', 'apollo'];
            $dock_ordered_brand_options = [];

            foreach ($dock_core_brand_slugs as $dock_core_slug) {
                $dock_core_slug = sanitize_title((string) $dock_core_slug);
                if ($dock_core_slug === '' || !isset($dock_brand_options[$dock_core_slug])) {
                    continue;
                }
                $dock_ordered_brand_options[$dock_core_slug] = $dock_brand_options[$dock_core_slug];
            }
            foreach ($dock_brand_options as $dock_brand_slug => $dock_brand_meta) {
                $dock_brand_slug = sanitize_title((string) $dock_brand_slug);
                if ($dock_brand_slug === '' || isset($dock_ordered_brand_options[$dock_brand_slug])) {
                    continue;
                }
                $dock_ordered_brand_options[$dock_brand_slug] = $dock_brand_meta;
            }

            $dock_ordered_brand_options = array_slice($dock_ordered_brand_options, 0, 12, true);

            $dock_category_groups = function_exists('my_theme_get_visible_product_category_groups')
                ? my_theme_get_visible_product_category_groups($dock_visible_ids)
                : [
                    'lookup' => [],
                    'by_parent' => [],
                ];
            $dock_all_terms = isset($dock_category_groups['by_parent'][0]) && is_array($dock_category_groups['by_parent'][0])
                ? array_slice($dock_category_groups['by_parent'][0], 0, 12)
                : [];
            if (!empty($dock_all_terms)) {
                foreach ($dock_all_terms as $dock_term) {
                    $dock_term_id = isset($dock_term['term_id']) ? (int) $dock_term['term_id'] : 0;
                    $dock_term_name = isset($dock_term['name']) ? (string) $dock_term['name'] : '';
                    $dock_term_count = isset($dock_term['count']) ? max(0, (int) $dock_term['count']) : 0;
                    if ($dock_term_id <= 0 || $dock_term_name === '') {
                        continue;
                    }
                    $catalog_dock_data['all_items'][] = [
                        'label' => $dock_term_name,
                        'count' => $dock_term_count,
                        'url' => add_query_arg('category', $dock_term_id, $dock_shop_url),
                    ];
                }
            }

            if (empty($catalog_dock_data['all_items']) && function_exists('my_theme_get_line_filter_options')) {
                $dock_all_lines = my_theme_get_line_filter_options($dock_visible_ids, '');
                if (is_array($dock_all_lines)) {
                    $dock_all_lines = array_slice($dock_all_lines, 0, 12, true);
                    foreach ($dock_all_lines as $dock_line_slug => $dock_line_meta) {
                        $dock_line_slug = sanitize_title((string) $dock_line_slug);
                        $dock_line_label = isset($dock_line_meta['label']) ? trim((string) $dock_line_meta['label']) : '';
                        if ($dock_line_slug === '' || $dock_line_label === '') {
                            continue;
                        }
                        $catalog_dock_data['all_items'][] = [
                            'label' => $dock_line_label,
                            'count' => isset($dock_line_meta['count']) ? max(0, (int) $dock_line_meta['count']) : 0,
                            'url' => add_query_arg('line', $dock_line_slug, $dock_shop_url),
                        ];
                    }
                }
            }

            foreach ($dock_ordered_brand_options as $dock_brand_slug => $dock_brand_meta) {
                $dock_brand_slug = sanitize_title((string) $dock_brand_slug);
                $dock_brand_label = isset($dock_brand_meta['label']) ? trim((string) $dock_brand_meta['label']) : '';
                $dock_brand_count = isset($dock_brand_meta['count']) ? max(0, (int) $dock_brand_meta['count']) : 0;
                if ($dock_brand_slug === '' || $dock_brand_label === '') {
                    continue;
                }

                $dock_brand_url = add_query_arg('brand', $dock_brand_slug, $dock_shop_url);
                $dock_brand_ids = function_exists('my_theme_filter_product_ids_by_brand_slug')
                    ? my_theme_filter_product_ids_by_brand_slug($dock_visible_ids, $dock_brand_slug)
                    : [];
                $dock_brand_ids = array_values(array_filter(array_map('intval', (array) $dock_brand_ids), function ($id) {
                    return $id > 0;
                }));

                $dock_brand_items = [];
                if (!empty($dock_brand_ids) && function_exists('my_theme_get_line_filter_options')) {
                    $dock_line_options = my_theme_get_line_filter_options($dock_brand_ids, $dock_brand_slug);
                    if (is_array($dock_line_options) && !empty($dock_line_options)) {
                        $dock_line_options = array_slice($dock_line_options, 0, 12, true);
                        foreach ($dock_line_options as $dock_line_slug => $dock_line_meta) {
                            $dock_line_slug = sanitize_title((string) $dock_line_slug);
                            $dock_line_label = isset($dock_line_meta['label']) ? trim((string) $dock_line_meta['label']) : '';
                            if ($dock_line_slug === '' || $dock_line_label === '') {
                                continue;
                            }
                            $dock_brand_items[] = [
                                'label' => $dock_line_label,
                                'count' => isset($dock_line_meta['count']) ? max(0, (int) $dock_line_meta['count']) : 0,
                                'url' => add_query_arg(
                                    [
                                        'brand' => $dock_brand_slug,
                                        'line' => $dock_line_slug,
                                    ],
                                    $dock_shop_url
                                ),
                            ];
                        }
                    }
                }

                if (empty($dock_brand_items) && !empty($dock_brand_ids)) {
                    $dock_brand_category_groups = function_exists('my_theme_get_visible_product_category_groups')
                        ? my_theme_get_visible_product_category_groups($dock_brand_ids)
                        : [
                            'lookup' => [],
                            'by_parent' => [],
                        ];
                    $dock_brand_terms = isset($dock_brand_category_groups['by_parent'][0]) && is_array($dock_brand_category_groups['by_parent'][0])
                        ? array_slice($dock_brand_category_groups['by_parent'][0], 0, 10)
                        : [];
                    if (!empty($dock_brand_terms)) {
                        foreach ($dock_brand_terms as $dock_term) {
                            $dock_term_id = isset($dock_term['term_id']) ? (int) $dock_term['term_id'] : 0;
                            $dock_term_name = isset($dock_term['name']) ? (string) $dock_term['name'] : '';
                            $dock_term_count = isset($dock_term['count']) ? max(0, (int) $dock_term['count']) : 0;
                            if ($dock_term_id <= 0 || $dock_term_name === '') {
                                continue;
                            }
                            $dock_brand_items[] = [
                                'label' => $dock_term_name,
                                'count' => $dock_term_count,
                                'url' => add_query_arg(
                                    [
                                        'brand' => $dock_brand_slug,
                                        'category' => $dock_term_id,
                                    ],
                                    $dock_shop_url
                                ),
                            ];
                        }
                    }
                }

                $catalog_dock_data['brands'][] = [
                    'slug' => $dock_brand_slug,
                    'label' => $dock_brand_label,
                    'count' => $dock_brand_count,
                    'url' => $dock_brand_url,
                    'items' => $dock_brand_items,
                ];
            }

            set_transient($dock_cache_key, $catalog_dock_data, 30 * MINUTE_IN_SECONDS);
        }
    }
}
?>
<?php if ($show_catalog_dock && !empty($catalog_dock_data['brands'])) : ?>
<div class="header-catalog-dock-wrap" aria-label="Danh mục sản phẩm nhanh">
  <div class="container">
    <section class="header-catalog-dock" data-catalog-dock>
      <aside class="catalog-rail" aria-label="Danh mục theo hãng">
        <div class="catalog-rail__title">
          <span class="catalog-rail__icon" aria-hidden="true">&#9776;</span>
          <span>Danh mục sản phẩm</span>
        </div>
        <ul class="catalog-rail__list">
          <li class="catalog-rail__item">
            <a class="catalog-rail__link is-active" href="<?php echo esc_url($shop_url); ?>" data-catalog-target="all">
              <span>Tất cả sản phẩm</span>
            </a>
          </li>
          <?php foreach ($catalog_dock_data['brands'] as $dock_brand) : ?>
            <?php
            $dock_brand_slug = isset($dock_brand['slug']) ? sanitize_title((string) $dock_brand['slug']) : '';
            $dock_brand_label = isset($dock_brand['label']) ? trim((string) $dock_brand['label']) : '';
            $dock_brand_count = isset($dock_brand['count']) ? max(0, (int) $dock_brand['count']) : 0;
            $dock_brand_url = isset($dock_brand['url']) ? (string) $dock_brand['url'] : $shop_url;
            if ($dock_brand_slug === '' || $dock_brand_label === '') {
                continue;
            }
            ?>
            <li class="catalog-rail__item">
              <a class="catalog-rail__link" href="<?php echo esc_url($dock_brand_url); ?>" data-catalog-target="<?php echo esc_attr('brand-' . $dock_brand_slug); ?>">
                <span><?php echo esc_html('Sơn ' . $dock_brand_label); ?></span>
                <?php if ($dock_brand_count > 0) : ?><strong><?php echo esc_html((string) $dock_brand_count); ?></strong><?php endif; ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </aside>

      <div class="catalog-stage">
        <section class="catalog-panel is-active" data-catalog-panel="all">
          <div class="catalog-panel__head">
            <h3>Kho danh mục tổng</h3>
            <a class="btn btn-outline btn-sm" href="<?php echo esc_url($shop_url); ?>">Mở kho</a>
          </div>
          <?php if (!empty($catalog_dock_data['all_items'])) : ?>
            <div class="catalog-panel__grid">
              <?php foreach ($catalog_dock_data['all_items'] as $dock_item) : ?>
                <?php
                $dock_item_label = isset($dock_item['label']) ? trim((string) $dock_item['label']) : '';
                $dock_item_count = isset($dock_item['count']) ? max(0, (int) $dock_item['count']) : 0;
                $dock_item_url = isset($dock_item['url']) ? (string) $dock_item['url'] : $shop_url;
                if ($dock_item_label === '') {
                    continue;
                }
                ?>
                <a class="catalog-panel__link" href="<?php echo esc_url($dock_item_url); ?>">
                  <span><?php echo esc_html($dock_item_label); ?></span>
                  <?php if ($dock_item_count > 0) : ?><strong><?php echo esc_html((string) $dock_item_count); ?></strong><?php endif; ?>
                </a>
              <?php endforeach; ?>
            </div>
            <div class="catalog-panel__support">
              <div class="catalog-panel__support-card">
                <p class="eyebrow eyebrow-muted">Lối tắt mua nhanh</p>
                <h4>Chốt nhu cầu gọn hơn ngay từ đầu</h4>
                <p>Đi thẳng tới công cụ tính sơn, hướng dẫn mua hàng và đội kỹ thuật để chốt mã sơn, khối lượng và lịch giao nhanh hơn.</p>
                <div class="catalog-panel__support-links" aria-label="Lối tắt hỗ trợ">
                  <?php foreach ($catalog_support_links as $support_link) : ?>
                    <a class="catalog-panel__support-link" href="<?php echo esc_url($support_link['url']); ?>"><?php echo esc_html($support_link['label']); ?></a>
                  <?php endforeach; ?>
                </div>
              </div>
              <div class="catalog-panel__support-meta" aria-label="Điểm hỗ trợ nổi bật">
                <span>Tư vấn theo bề mặt</span>
                <span>Báo giá trong giờ làm việc</span>
                <span>Giao vật tư nội thành</span>
              </div>
            </div>
          <?php else : ?>
            <p class="catalog-panel__empty">Đang cập nhật dữ liệu danh mục.</p>
          <?php endif; ?>
        </section>

        <?php foreach ($catalog_dock_data['brands'] as $dock_brand) : ?>
          <?php
          $dock_brand_slug = isset($dock_brand['slug']) ? sanitize_title((string) $dock_brand['slug']) : '';
          $dock_brand_label = isset($dock_brand['label']) ? trim((string) $dock_brand['label']) : '';
          $dock_brand_count = isset($dock_brand['count']) ? max(0, (int) $dock_brand['count']) : 0;
          $dock_brand_url = isset($dock_brand['url']) ? (string) $dock_brand['url'] : $shop_url;
          $dock_brand_items = isset($dock_brand['items']) && is_array($dock_brand['items']) ? $dock_brand['items'] : [];
          $dock_brand_cta_label = $dock_brand_label !== '' ? ('Xem sơn ' . $dock_brand_label) : 'Xem tất cả';
          $dock_brand_primary_item = !empty($dock_brand_items) ? (array) $dock_brand_items[0] : [];
          $dock_brand_primary_label = isset($dock_brand_primary_item['label']) ? trim((string) $dock_brand_primary_item['label']) : '';
          $dock_brand_primary_url = isset($dock_brand_primary_item['url']) ? (string) $dock_brand_primary_item['url'] : $dock_brand_url;
          if ($dock_brand_slug === '' || $dock_brand_label === '') {
              continue;
          }
          ?>
          <section class="catalog-panel" data-catalog-panel="<?php echo esc_attr('brand-' . $dock_brand_slug); ?>" hidden>
            <div class="catalog-panel__head">
              <h3><?php echo esc_html('Sơn ' . $dock_brand_label); ?></h3>
              <a class="btn btn-outline btn-sm" href="<?php echo esc_url($dock_brand_url); ?>"><?php echo esc_html($dock_brand_cta_label); ?></a>
            </div>
            <?php if (!empty($dock_brand_items)) : ?>
              <div class="catalog-panel__list">
                <?php foreach ($dock_brand_items as $dock_item) : ?>
                  <?php
                  $dock_item_label = isset($dock_item['label']) ? trim((string) $dock_item['label']) : '';
                  $dock_item_count = isset($dock_item['count']) ? max(0, (int) $dock_item['count']) : 0;
                  $dock_item_url = isset($dock_item['url']) ? (string) $dock_item['url'] : $dock_brand_url;
                  if ($dock_item_label === '') {
                      continue;
                  }
                  ?>
                  <a class="catalog-panel__line" href="<?php echo esc_url($dock_item_url); ?>">
                    <span><?php echo esc_html($dock_item_label); ?></span>
                    <?php if ($dock_item_count > 0) : ?><strong><?php echo esc_html((string) $dock_item_count); ?></strong><?php endif; ?>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php else : ?>
              <p class="catalog-panel__empty">Đang cập nhật nhóm sản phẩm cho hãng này.</p>
            <?php endif; ?>
            <div class="catalog-panel__support catalog-panel__support--brand">
              <div class="catalog-panel__support-card">
                <p class="eyebrow eyebrow-muted"><?php echo esc_html('Lối tắt cho sơn ' . $dock_brand_label); ?></p>
                <h4>Chốt nhanh mã sơn và nhóm phù hợp</h4>
                <p><?php echo esc_html('Đi thẳng tới toàn bộ dòng ' . $dock_brand_label . ', nhóm đang có sẵn và kênh hỗ trợ để báo giá nhanh hơn.'); ?></p>
                <div class="catalog-panel__support-links" aria-label="<?php echo esc_attr('Lối tắt hỗ trợ cho hãng ' . $dock_brand_label); ?>">
                  <a class="catalog-panel__support-link" href="<?php echo esc_url($dock_brand_url); ?>"><?php echo esc_html($dock_brand_cta_label); ?></a>
                  <?php if ($dock_brand_primary_label !== '') : ?>
                    <a class="catalog-panel__support-link" href="<?php echo esc_url($dock_brand_primary_url); ?>"><?php echo esc_html($dock_brand_primary_label); ?></a>
                  <?php endif; ?>
                  <a class="catalog-panel__support-link" href="<?php echo esc_url(home_url('/lien-he')); ?>">Nhận báo giá</a>
                  <a class="catalog-panel__support-link" href="<?php echo esc_url('mailto:' . sanitize_email($header_email)); ?>">Mail báo giá</a>
                  <a class="catalog-panel__support-link" href="<?php echo esc_url($header_zalo_url); ?>" target="_blank" rel="noopener">Nhắn Zalo</a>
                </div>
              </div>
              <div class="catalog-panel__support-meta" aria-label="<?php echo esc_attr('Điểm hỗ trợ cho hãng ' . $dock_brand_label); ?>">
                <?php if ($dock_brand_count > 0) : ?><span><?php echo esc_html($dock_brand_count . ' mã đang hiển thị'); ?></span><?php endif; ?>
                <span><?php echo esc_html($header_hours_display); ?></span>
                <span><?php echo esc_html('Phục vụ: ' . $header_service_areas); ?></span>
                <span><?php echo esc_html('Báo giá: ' . $header_phone_display); ?></span>
              </div>
            </div>
          </section>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
</div>
<?php endif; ?>
