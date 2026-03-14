<?php
$posts_url = trailingslashit(home_url('/blog'));
$shop_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
$section_action_url = $posts_url;
$section_action_label = 'Xem góc tư vấn';
$is_fallback_content = false;
$placeholder_post_ids = function_exists('my_theme_get_placeholder_blog_post_ids') ? my_theme_get_placeholder_blog_post_ids() : [];
$blog_cache_version = (string) get_option('my_theme_blog_cache_version', '1');
$cards_cache_key = 'my_theme_home_posts_cards_v1_' . md5($blog_cache_version . '|' . implode(',', array_map('intval', (array) $placeholder_post_ids)));

$cards = get_transient($cards_cache_key);
if (!is_array($cards)) {
  $cards = [];
  $q = new WP_Query([
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => 6,
    'ignore_sticky_posts' => true,
    'post__not_in' => $placeholder_post_ids,
    'suppress_filters' => true,
    'no_found_rows' => true,
  ]);

  if ($q->have_posts()) {
    while ($q->have_posts()) {
      $q->the_post();
      $title = trim((string) get_the_title());
      if ($title === '' || (function_exists('my_theme_is_placeholder_blog_post') && my_theme_is_placeholder_blog_post(get_post()))) {
        continue;
      }

      $excerpt = trim((string) get_the_excerpt());
      if ($excerpt === '') {
        $excerpt = wp_trim_words(wp_strip_all_tags((string) get_the_content()), 22);
      }

      $cards[] = [
        'title' => $title,
        'excerpt' => $excerpt,
        'url' => get_permalink(),
        'date' => get_the_date(),
        'thumb' => get_the_post_thumbnail(null, 'medium'),
        'thumb_label' => '',
        'cta_label' => 'Đọc tiếp',
      ];
    }
    wp_reset_postdata();
  }

  set_transient($cards_cache_key, $cards, 30 * MINUTE_IN_SECONDS);
}

if (empty($cards)) {
  $is_fallback_content = true;

  $guide_url = function_exists('my_theme_get_page_permalink_by_path_or_template')
    ? my_theme_get_page_permalink_by_path_or_template('huong-dan-mua-hang', 'page-huong-dan-mua-hang.php')
    : '';
  $faq_url = function_exists('my_theme_get_page_permalink_by_path_or_template')
    ? my_theme_get_page_permalink_by_path_or_template('faq', 'page-faq.php')
    : '';
  if ($faq_url === '') {
    $faq_url = function_exists('my_theme_get_page_permalink_by_path_or_template')
      ? my_theme_get_page_permalink_by_path_or_template('cau-hoi-thuong-gap', 'page-faq.php')
      : '';
  }
  $contact_url = function_exists('my_theme_get_page_permalink_by_path_or_template')
    ? my_theme_get_page_permalink_by_path_or_template('lien-he', 'page-lien-he.php')
    : '';
  $calculator_url = function_exists('my_theme_get_paint_calculator_url') ? my_theme_get_paint_calculator_url() : home_url('/tinh-son');

  $fallback_cards = [
    [
      'title' => 'Hướng dẫn mua hàng cho thợ và công trình',
      'excerpt' => 'Tổng hợp quy trình đặt hàng, xác nhận, giao hàng và các mốc phản hồi để đặt sơn nhanh hơn.',
      'url' => $guide_url,
      'date' => 'Hướng dẫn đặt hàng',
      'thumb' => '',
      'thumb_label' => 'Quy trình',
      'cta_label' => 'Xem hướng dẫn',
    ],
    [
      'title' => 'Câu hỏi thường gặp khi chọn sơn và nhận hàng',
      'excerpt' => 'Xem nhanh các câu hỏi phổ biến về phối màu, hóa đơn, thời gian giao và hỗ trợ kỹ thuật.',
      'url' => $faq_url,
      'date' => 'Giải đáp nhanh',
      'thumb' => '',
      'thumb_label' => 'FAQ',
      'cta_label' => 'Xem giải đáp',
    ],
    [
      'title' => 'Tính nhanh định mức vật tư theo diện tích thực tế',
      'excerpt' => 'Đi tới công cụ tính m2 và số lít/xô cần dùng trước khi chốt mã sơn cho công trình.',
      'url' => $calculator_url,
      'date' => 'Công cụ định mức',
      'thumb' => '',
      'thumb_label' => 'Tinh m2',
      'cta_label' => 'Mở công cụ',
    ],
    [
      'title' => 'Gửi yêu cầu báo giá và nhận tư vấn kỹ thuật',
      'excerpt' => 'Điền thông tin công trình để đội kỹ thuật gọi lại, chốt hệ sơn và báo giá phù hợp ngân sách.',
      'url' => $contact_url,
      'date' => 'Lien he nhanh',
      'thumb' => '',
      'thumb_label' => 'Bao gia',
      'cta_label' => 'Liên hệ ngay',
    ],
  ];

  foreach ($fallback_cards as $item) {
    $item_url = isset($item['url']) ? trim((string) $item['url']) : '';
    if ($item_url === '') {
      continue;
    }
    $cards[] = $item;
  }

  if (empty($cards)) {
    return;
  }

  $section_action_url = $guide_url !== '' ? $guide_url : ($contact_url !== '' ? $contact_url : $shop_url);
  $section_action_label = 'Mở hướng dẫn chi tiết';
}
?>
<section id="posts" class="page-section home-posts<?php echo $is_fallback_content ? ' home-posts--fallback' : ''; ?>">
  <div class="section-heading">
    <div>
      <h2 class="section-title">Góc tư vấn thi công</h2>
      <p class="section-sub">
        <?php echo $is_fallback_content ? esc_html('Các tài nguyên nền tảng để chốt mã sơn, định mức và quy trình đặt hàng ngay cả khi mục blog chưa cập nhật bài mới.') : esc_html('Nội dung chọn sơn, định mức và kỹ thuật ứng dụng thực tế'); ?>
      </p>
    </div>
    <a class="btn btn-outline btn-sm" href="<?php echo esc_url($section_action_url); ?>"><?php echo esc_html($section_action_label); ?></a>
  </div>
  <?php if ($is_fallback_content) : ?>
    <div class="search-scope" aria-label="Lối tắt tài nguyên tư vấn">
      <?php foreach ($cards as $item) : ?>
        <a class="chip" href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html((string) ($item['thumb_label'] !== '' ? $item['thumb_label'] : $item['title'])); ?></a>
      <?php endforeach; ?>
    </div>
    <div class="trust-row" aria-label="Điểm hỗ trợ nổi bật">
      <span class="trust-item">Hướng dẫn mua nhanh</span>
      <span class="trust-item">FAQ thực tế</span>
      <span class="trust-item">Tính định mức theo m2</span>
    </div>
  <?php endif; ?>
  <div class="insight-grid">
    <?php foreach ($cards as $item) : ?>
      <article class="insight-card">
        <?php if (!empty($item['thumb'])) : ?>
          <a class="insight-card__thumb" href="<?php echo esc_url($item['url']); ?>">
            <?php echo $item['thumb']; ?>
          </a>
        <?php elseif (!empty($item['thumb_label'])) : ?>
          <a class="insight-card__thumb insight-card__thumb--placeholder" href="<?php echo esc_url($item['url']); ?>">
            <span class="insight-card__thumb-badge"><?php echo esc_html((string) $item['thumb_label']); ?></span>
            <strong class="insight-card__thumb-title"><?php echo esc_html((string) $item['title']); ?></strong>
          </a>
        <?php endif; ?>
        <div class="insight-card__body">
          <div class="insight-card__date"><?php echo esc_html($item['date']); ?></div>
          <h3 class="insight-card__title"><a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['title']); ?></a></h3>
          <p class="insight-card__excerpt"><?php echo esc_html($item['excerpt']); ?></p>
        </div>
        <div class="insight-card__actions">
          <a class="btn btn-primary w-100" href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html(isset($item['cta_label']) ? (string) $item['cta_label'] : 'Đọc tiếp'); ?></a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
