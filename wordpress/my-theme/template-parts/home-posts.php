<?php
$posts_page_id = get_option('page_for_posts');
$posts_url = $posts_page_id ? get_permalink($posts_page_id) : home_url('/');
?>
<section id="posts" class="page-section">
  <div class="section-heading">
    <div>
      <h2 class="section-title">Tư vấn thi công & chọn màu</h2>
      <p class="section-sub">Kinh nghiệm thực tế từ thợ và kỹ thuật</p>
    </div>
    <a class="btn btn-outline btn-sm" href="<?php echo esc_url($posts_url); ?>">Xem toàn bộ bài viết</a>
  </div>
  <div class="product-grid">
    <?php
    $q = new WP_Query(['posts_per_page' => 6]);
    if ($q->have_posts()):
      while ($q->have_posts()): $q->the_post(); ?>
        <article class="product-card">
          <a class="product-card__thumb" href="<?php the_permalink(); ?>">
            <?php if (has_post_thumbnail()) { the_post_thumbnail('medium'); } ?>
          </a>
          <div class="product-card__body">
            <div class="product-card__brand"><?php echo get_the_date(); ?></div>
            <h3 class="product-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
            <p class="text-muted"><?php echo wp_trim_words(get_the_excerpt(), 18); ?></p>
          </div>
          <div class="product-card__actions">
            <a class="btn btn-primary w-100" href="<?php the_permalink(); ?>">Đọc tiếp</a>
          </div>
        </article>
      <?php endwhile; wp_reset_postdata();
    else: ?>
      <p class="text-muted">Chưa có bài viết nào. Vui lòng quay lại sau.</p>
    <?php endif; ?>
  </div>
</section>
