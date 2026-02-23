<section id="categories" class="page-section">
  <?php $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop'); ?>
  <div class="section-heading">
    <h2 class="section-title">Chọn đúng hệ sơn cho công trình</h2>
    <p class="section-sub">Tư vấn đúng bề mặt, đúng môi trường, tiết kiệm chi phí thi công</p>
  </div>
  <div class="category-grid">
    <?php
    $cats = get_terms([
      'taxonomy' => 'product_cat',
      'hide_empty' => true,
      'parent' => 0,
      'orderby' => 'name',
      'order' => 'ASC',
    ]);
    if (!empty($cats) && !is_wp_error($cats)) {
      $cats = array_values(array_filter($cats, function ($cat) {
        return isset($cat->slug) && $cat->slug !== 'uncategorized';
      }));
      $priority = [
        'son-noi-that' => 10,
        'son-ngoai-that' => 20,
        'son-lot' => 30,
        'chong-tham' => 40,
        'bot-tret' => 50,
        'keo-va-phu-gia' => 60,
        'son-kim-loai' => 70,
        'son-cong-nghiep' => 80,
        'son-dau' => 90,
      ];
      usort($cats, function ($a, $b) use ($priority) {
        $pa = $priority[$a->slug] ?? 999;
        $pb = $priority[$b->slug] ?? 999;
        if ($pa !== $pb) {
          return ($pa < $pb) ? -1 : 1;
        }
        return strnatcasecmp($a->name, $b->name);
      });
      $cats = array_slice($cats, 0, 8);
    } else {
      $cats = [];
    }
    if (!empty($cats)) :
      foreach ($cats as $cat): ?>
        <article class="category-card">
          <div class="category-card__head">
            <h3 class="category-card__title"><?php echo esc_html($cat->name); ?></h3>
            <span class="category-card__count"><?php echo esc_html((string) $cat->count); ?> sản phẩm</span>
          </div>
          <div class="category-card__body">
            <?php if (!empty($cat->description)): ?>
              <p class="category-card__desc"><?php echo esc_html(wp_trim_words(wp_strip_all_tags($cat->description), 16, '...')); ?></p>
            <?php else: ?>
              <p class="category-card__desc">Danh mục sơn chính hãng, tối ưu cho nhu cầu thi công thực tế.</p>
            <?php endif; ?>
          </div>
          <div class="category-card__actions">
            <a class="btn btn-primary w-100" href="<?php echo esc_url(add_query_arg('category', (int) $cat->term_id, $shop_url)); ?>">Xem danh mục</a>
          </div>
        </article>
      <?php endforeach;
    else:
      $fallback = [
        ['title' => 'Sơn nội thất', 'desc' => 'Mịn đẹp, bền màu, dễ lau chùi cho nhà ở, văn phòng.'],
        ['title' => 'Sơn ngoại thất', 'desc' => 'Chống tia UV, chống bám bẩn, bền thời tiết.'],
        ['title' => 'Chống thấm', 'desc' => 'Giải pháp cho sân thượng, mái, tường đứng.'],
        ['title' => 'Bột bả/Matit', 'desc' => 'Làm phẳng bề mặt, tăng bám dính cho lớp sơn.'],
      ];
      foreach ($fallback as $cat): ?>
        <article class="category-card">
          <div class="category-card__head">
            <h3 class="category-card__title"><?php echo esc_html($cat['title']); ?></h3>
          </div>
          <div class="category-card__body">
            <p class="category-card__desc"><?php echo esc_html($cat['desc']); ?></p>
          </div>
          <div class="category-card__actions">
            <a class="btn btn-primary w-100" href="<?php echo esc_url($shop_url); ?>">Xem danh mục</a>
          </div>
        </article>
      <?php endforeach;
    endif; ?>
  </div>
</section>
