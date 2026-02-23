<?php
$posts_page_id = get_option('page_for_posts');
$posts_url = $posts_page_id ? get_permalink($posts_page_id) : home_url('/');

$is_placeholder_title = function ($title) {
  $normalized = my_theme_normalize_search_text($title);
  return (bool) preg_match('/^san pham son mau\\s*\\d+$/', $normalized);
};

$cards = [];
$q = new WP_Query([
  'post_type' => 'post',
  'post_status' => 'publish',
  'posts_per_page' => 6,
  'ignore_sticky_posts' => true,
]);

if ($q->have_posts()) {
  while ($q->have_posts()) {
    $q->the_post();
    $title = trim((string) get_the_title());
    if ($title === '' || $is_placeholder_title($title)) {
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
    ];
  }
  wp_reset_postdata();
}

if (empty($cards)) {
  return;
}
?>
<section id="posts" class="page-section">
  <div class="section-heading">
    <div>
      <h2 class="section-title">Góc tư vấn thi công</h2>
      <p class="section-sub">Nội dung chọn sơn, định mức và kỹ thuật ứng dụng thực tế</p>
    </div>
    <a class="btn btn-outline btn-sm" href="<?php echo esc_url($posts_url); ?>">Xem toàn bộ bài viết</a>
  </div>
  <div class="product-grid product-grid--home">
    <?php foreach ($cards as $item) : ?>
      <article class="product-card">
        <?php if (!empty($item['thumb'])) : ?>
          <a class="product-card__thumb" href="<?php echo esc_url($item['url']); ?>">
            <?php echo $item['thumb']; ?>
          </a>
        <?php endif; ?>
        <div class="product-card__body">
          <div class="product-card__brand"><?php echo esc_html($item['date']); ?></div>
          <h3 class="product-card__title"><a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['title']); ?></a></h3>
          <p class="text-muted"><?php echo esc_html($item['excerpt']); ?></p>
        </div>
        <div class="product-card__actions">
          <a class="btn btn-primary w-100" href="<?php echo esc_url($item['url']); ?>">Đọc tiếp</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
