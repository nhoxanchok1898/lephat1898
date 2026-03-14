<?php
/**
 * Home solutions quick navigation.
 */

if (!function_exists('my_theme_get_visual_story_group_catalog') || !function_exists('my_theme_get_visual_story_items_by_group')) {
    return;
}

$home_solution_catalog = my_theme_get_visual_story_group_catalog();
$home_solution_order = ['interior', 'exterior', 'waterproofing', 'epoxy', 'metal', 'grout'];
$home_solution_cards = [];

foreach ($home_solution_order as $home_solution_group_key) {
    $home_solution_group_key = sanitize_key((string) $home_solution_group_key);
    if ($home_solution_group_key === '' || !isset($home_solution_catalog[$home_solution_group_key])) {
        continue;
    }

    $home_solution_items = my_theme_get_visual_story_items_by_group($home_solution_group_key);
    if (empty($home_solution_items)) {
        continue;
    }

    $home_solution_meta = (array) $home_solution_catalog[$home_solution_group_key];
    $home_solution_cards[] = [
        'label' => isset($home_solution_meta['label']) ? (string) $home_solution_meta['label'] : $home_solution_group_key,
        'title' => isset($home_solution_meta['title']) ? (string) $home_solution_meta['title'] : $home_solution_group_key,
        'description' => isset($home_solution_meta['description']) ? (string) $home_solution_meta['description'] : '',
        'url' => isset($home_solution_meta['url']) ? (string) $home_solution_meta['url'] : home_url('/'),
        'cta' => isset($home_solution_meta['cta']) ? (string) $home_solution_meta['cta'] : 'Xem thêm',
        'item' => (array) $home_solution_items[0],
    ];
}

if (empty($home_solution_cards)) {
    return;
}
?>
<section class="page-section home-solutions" aria-label="Giải pháp theo nhu cầu">
  <div class="section-heading home-solutions__head">
    <div>
      <p class="eyebrow eyebrow-muted">Khi chưa có mã sản phẩm</p>
      <h2 class="section-title">Chọn theo bề mặt và hạng mục thi công</h2>
      <p class="section-sub">Dùng lối này khi bạn mới xác định nhu cầu như nội thất, ngoại thất, chống thấm, epoxy hoặc kim loại. Nếu đã có hãng hay mã, ưu tiên xem sản phẩm nổi bật và kho sản phẩm ở trên.</p>
    </div>
    <div class="home-solutions__chips" aria-label="Lối tắt hỗ trợ">
      <a class="chip" href="<?php echo esc_url(home_url('/shop')); ?>">Mở kho sản phẩm</a>
      <a class="chip" href="<?php echo esc_url(home_url('/giai-phap')); ?>">Mở tất cả giải pháp</a>
      <a class="chip" href="<?php echo esc_url(home_url('/lien-he')); ?>">Gửi yêu cầu báo giá</a>
      <a class="chip" href="<?php echo esc_url(home_url('/huong-dan-mua-hang')); ?>">Hướng dẫn mua hàng</a>
    </div>
  </div>

  <div class="home-solutions__grid">
    <?php foreach ($home_solution_cards as $home_solution_card) : ?>
      <?php
      $home_solution_item = (array) $home_solution_card['item'];
      $home_solution_attachment_id = isset($home_solution_item['attachment_id']) ? (int) $home_solution_item['attachment_id'] : 0;
      $home_solution_caption = isset($home_solution_item['caption']) ? trim((string) $home_solution_item['caption']) : '';
      $home_solution_alt = $home_solution_caption !== '' ? $home_solution_caption : (string) $home_solution_card['title'];
      ?>
      <article class="home-solution-card">
        <a class="home-solution-card__thumb" href="<?php echo esc_url((string) $home_solution_card['url']); ?>">
          <?php echo wp_get_attachment_image($home_solution_attachment_id, 'large', false, ['loading' => 'lazy', 'alt' => $home_solution_alt]); ?>
        </a>
        <div class="home-solution-card__body">
          <div class="home-solution-card__eyebrow"><?php echo esc_html((string) $home_solution_card['label']); ?></div>
          <h3 class="home-solution-card__title">
            <a href="<?php echo esc_url((string) $home_solution_card['url']); ?>"><?php echo esc_html((string) $home_solution_card['title']); ?></a>
          </h3>
          <p class="home-solution-card__description"><?php echo esc_html((string) $home_solution_card['description']); ?></p>
          <?php if ($home_solution_caption !== '') : ?>
            <p class="home-solution-card__caption"><?php echo esc_html($home_solution_caption); ?></p>
          <?php endif; ?>
        </div>
        <div class="home-solution-card__actions">
          <a class="btn btn-outline w-100" href="<?php echo esc_url((string) $home_solution_card['url']); ?>"><?php echo esc_html((string) $home_solution_card['cta']); ?></a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
