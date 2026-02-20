<?php get_header(); ?>

<!-- Hero -->
<section class="hero">
  <div>
    <p class="eyebrow">ĐẠI LÝ SƠN · Dulux · Jotun · Kova · Nippon · Maxilite</p>
    <h1>Bảng giá sơn chính hãng – giao tận công trình</h1>
    <p>Giá đại lý – chiết khấu cho thợ & công trình. Tư vấn định mức và phối màu miễn phí.</p>
    <div class="hero-actions">
      <a class="btn btn-accent btn-lg" href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>">Xem bảng giá & chiết khấu</a>
      <a class="btn btn-outline btn-lg" href="<?php echo esc_url(home_url('/lien-he')); ?>">Nhận tư vấn kỹ thuật</a>
    </div>
  </div>
  <div class="hero-card">
    <h3>Cam kết khi mua tại Phát Tấn</h3>
    <ul class="list-plain">
      <li>Hàng mới 100% · Có hóa đơn VAT & chứng từ hãng</li>
      <li>Tư vấn định mức m², chọn hệ sơn phù hợp bề mặt</li>
      <li>Giao 24–48h nội thành TP.HCM · Hỗ trợ kỹ thuật</li>
    </ul>
  </div>
</section>

<!-- Thương hiệu -->
<section class="page-section">
  <div class="section-heading">
    <h2 class="section-title">Thương hiệu đối tác</h2>
    <p class="section-sub">Dulux · Jotun · Kova · Nippon · Maxilite</p>
  </div>
  <div class="brand-strip">
    <span class="brand-chip">Dulux</span>
    <span class="brand-chip">Jotun</span>
    <span class="brand-chip">Kova</span>
    <span class="brand-chip">Nippon</span>
    <span class="brand-chip">Maxilite</span>
  </div>
</section>

<!-- Danh mục -->
<section class="page-section">
  <div class="section-heading">
    <h2 class="section-title">Danh mục sản phẩm</h2>
    <p class="section-sub">Tư vấn đúng bề mặt, đúng môi trường, tiết kiệm chi phí thi công</p>
  </div>
  <div class="product-grid">
    <?php
    $cats = [
      ['title' => 'Sơn nội thất', 'desc' => 'Mịn đẹp, bền màu, dễ lau chùi'],
      ['title' => 'Sơn ngoại thất', 'desc' => 'Chống bám bẩn, chống tia UV'],
      ['title' => 'Chống thấm', 'desc' => 'Giải pháp mái, sàn, tường'],
      ['title' => 'Bột bả', 'desc' => 'Bề mặt phẳng mịn, bền màu'],
    ];
    foreach ($cats as $cat) : ?>
      <article class="product-card">
        <div class="product-card__body">
          <div class="product-card__brand">Danh mục</div>
          <h3 class="product-card__title"><?php echo esc_html($cat['title']); ?></h3>
          <p class="text-muted"><?php echo esc_html($cat['desc']); ?></p>
        </div>
        <div class="product-card__actions">
          <a class="btn btn-primary w-100" href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>"><?php _e('Xem sản phẩm','my-custom-theme'); ?></a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<!-- Bài viết nổi bật -->
<?php
$posts_page_id = get_option('page_for_posts');
$posts_url = $posts_page_id ? get_permalink($posts_page_id) : home_url('/');
?>
<section class="page-section">
  <div class="section-heading">
    <h2 class="section-title">Tư vấn thi công & chọn màu</h2>
    <a class="btn btn-outline btn-sm" href="<?php echo esc_url($posts_url); ?>">Xem toàn bộ bài viết</a>
  </div>
  <div class="product-grid">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
      <article <?php post_class('product-card'); ?>>
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
    <?php endwhile; else : ?>
      <p class="text-muted">Chưa có bài viết.</p>
    <?php endif; ?>
  </div>
  <?php the_posts_navigation(); ?>
</section>

<?php get_footer(); ?>
