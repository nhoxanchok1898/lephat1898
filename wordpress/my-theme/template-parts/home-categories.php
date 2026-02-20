<section id="categories" class="page-section">
  <div class="section-heading">
    <h2 class="section-title">Chọn đúng hệ sơn cho công trình</h2>
    <p class="section-sub">Tư vấn đúng bề mặt, đúng môi trường, tiết kiệm chi phí thi công</p>
  </div>
  <div class="product-grid">
    <?php
    $cats = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
    if (!empty($cats) && !is_wp_error($cats)) :
      foreach ($cats as $cat): ?>
        <article class="product-card">
          <div class="product-card__body">
            <div class="product-card__brand">Danh mục</div>
            <h3 class="product-card__title"><?php echo esc_html($cat->name); ?></h3>
            <?php if (!empty($cat->description)): ?>
              <p class="text-muted"><?php echo esc_html($cat->description); ?></p>
            <?php endif; ?>
          </div>
          <div class="product-card__actions">
            <a class="btn btn-primary w-100" href="<?php echo esc_url(get_term_link($cat)); ?>">Xem sản phẩm</a>
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
        <article class="product-card">
          <div class="product-card__body">
            <div class="product-card__brand">Danh mục</div>
            <h3 class="product-card__title"><?php echo esc_html($cat['title']); ?></h3>
            <p class="text-muted"><?php echo esc_html($cat['desc']); ?></p>
          </div>
          <div class="product-card__actions">
            <a class="btn btn-primary w-100" href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>">Xem sản phẩm</a>
          </div>
        </article>
      <?php endforeach;
    endif; ?>
  </div>
</section>
