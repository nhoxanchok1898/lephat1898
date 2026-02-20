<?php
/** Woo shop template mirroring old product_list layout */
get_header();
?>
<div class="shop-hero container">
  <div class="shop-hero__content">
    <h1>Sơn chính hãng – giá đại lý cho thợ & công trình</h1>
    <p>Bảng giá rõ ràng, chiết khấu theo khối lượng, giao nhanh 24–48h.</p>
    <div class="hero-actions">
      <a class="btn btn-accent btn-lg" href="tel:0944857999">Gọi báo giá nhanh</a>
      <a class="btn btn-outline btn-lg" href="https://zalo.me/0944857999" target="_blank" rel="noopener">Zalo tư vấn</a>
      <a class="btn btn-primary btn-lg" href="<?php echo esc_url(home_url('/lien-he')); ?>">Gửi yêu cầu</a>
    </div>
    <div class="trust-row">
      <span class="trust-item">Đại lý chính hãng</span>
      <span class="trust-item">Hàng mới 100%</span>
      <span class="trust-item">Hỗ trợ kỹ thuật</span>
    </div>
  </div>
</div>
<div class="container wc-container page-section">
  <?php
  $q = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
  $cat = isset($_GET['category']) ? absint($_GET['category']) : 0;
  $brand = isset($_GET['brand']) ? sanitize_text_field(wp_unslash($_GET['brand'])) : '';
  $sort = isset($_GET['sort']) ? sanitize_text_field(wp_unslash($_GET['sort'])) : '';

  $tax_query = [];
  if ($cat) {
    $tax_query[] = [
      'taxonomy' => 'product_cat',
      'field'    => 'term_id',
      'terms'    => [$cat],
    ];
  }
  if ($brand) {
    $tax_query[] = [
      'taxonomy' => 'pa_brand',
      'field'    => 'slug',
      'terms'    => [$brand],
    ];
  }

  $args = [
    'post_type' => 'product',
    'post_status' => 'publish',
    's' => $q,
    'tax_query' => $tax_query,
    'paged' => max(1, get_query_var('paged')),
  ];
  switch ($sort) {
    case 'price_asc': $args['orderby'] = 'meta_value_num'; $args['meta_key'] = '_price'; $args['order'] = 'ASC'; break;
    case 'price_desc': $args['orderby'] = 'meta_value_num'; $args['meta_key'] = '_price'; $args['order'] = 'DESC'; break;
    case 'name_asc': $args['orderby'] = 'title'; $args['order'] = 'ASC'; break;
    case 'name_desc': $args['orderby'] = 'title'; $args['order'] = 'DESC'; break;
    default: $args['orderby'] = 'date'; $args['order'] = 'DESC';
  }
  $loop = new WP_Query($args);

  $cats = get_terms([ 'taxonomy' => 'product_cat', 'hide_empty' => false ]);
  $brands = get_terms([ 'taxonomy' => 'pa_brand', 'hide_empty' => false ]);
  $normalize_term = function ($term, $taxonomy) {
    if (is_wp_error($term) || empty($term)) {
      return null;
    }
    if (is_numeric($term)) {
      $term_obj = get_term((int) $term, $taxonomy);
      if (is_wp_error($term_obj) || !$term_obj) {
        return null;
      }
      return $term_obj;
    }
    if (is_array($term)) {
      if (!empty($term['term_id'])) {
        $term_obj = get_term((int) $term['term_id'], $taxonomy);
        if (!is_wp_error($term_obj) && $term_obj) {
          return $term_obj;
        }
      }
      $term = (object) $term;
    }
    return is_object($term) ? $term : null;
  };
  ?>

  <section class="page-section">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
      <div>
        <nav class="breadcrumb-nav" aria-label="Đường dẫn">
          <ol class="breadcrumb">
            <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
            <li>Sản phẩm</li>
            <?php if ($cat && ($term = get_term($cat, 'product_cat'))) : ?><li><?php echo esc_html($term->name); ?></li><?php endif; ?>
          </ol>
        </nav>
        <h1 class="page-title">Sản phẩm</h1>
        <p class="page-subtitle mb-0"><strong><?php echo esc_html($loop->found_posts); ?></strong> sản phẩm</p>
        <p class="section-note"><?php echo esc_html(my_theme_get_category_intro($cat)); ?></p>
        <div class="filter-bar" style="grid-template-columns: 1fr 1fr;">
          <div class="chip-group">
            <span class="chip-label">Thương hiệu:</span>
            <a class="chip <?php echo $brand ? '' : 'active'; ?>" href="?<?php echo esc_attr(remove_query_arg('brand')); ?>">Tất cả</a>
            <?php foreach ($brands as $b) : ?>
              <?php $b = $normalize_term($b, 'pa_brand'); if (!$b || empty($b->slug) || empty($b->name)) { continue; } ?>
              <a class="chip <?php echo ($brand === $b->slug) ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg('brand', $b->slug)); ?>"><?php echo esc_html($b->name); ?></a>
            <?php endforeach; ?>
          </div>
          <div class="chip-group">
            <span class="chip-label">Danh mục:</span>
            <a class="chip <?php echo $cat ? '' : 'active'; ?>" href="?<?php echo esc_attr(remove_query_arg('category')); ?>">Tất cả</a>
            <?php foreach ($cats as $c) : ?>
              <?php $c = $normalize_term($c, 'product_cat'); if (!$c || empty($c->term_id) || empty($c->name)) { continue; } ?>
              <a class="chip <?php echo ($cat === $c->term_id) ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg('category', $c->term_id)); ?>"><?php echo esc_html($c->name); ?></a>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="cta-inline" style="margin-top:14px;">
          <div class="cta-inline__content">
            <div>
              <h3>Chọn đúng hệ sơn theo công trình</h3>
              <p class="text-muted">Gửi diện tích và bề mặt, chúng tôi tư vấn định mức & vật tư phù hợp.</p>
            </div>
            <div class="cta-inline__actions">
              <a class="btn btn-accent" href="<?php echo esc_url(home_url('/lien-he')); ?>">Gửi diện tích</a>
              <a class="btn btn-outline" href="tel:0944857999">Gọi tư vấn</a>
            </div>
          </div>
        </div>
      </div>
      <div class="sort-box">
        <form method="get" class="sort-form">
          <?php if ($cat): ?><input type="hidden" name="category" value="<?php echo esc_attr($cat); ?>"><?php endif; ?>
          <?php if ($brand): ?><input type="hidden" name="brand" value="<?php echo esc_attr($brand); ?>"><?php endif; ?>
          <?php if ($q): ?><input type="hidden" name="q" value="<?php echo esc_attr($q); ?>"><?php endif; ?>
          <label for="sort" class="visually-hidden">Sắp xếp</label>
          <select id="sort" name="sort" class="sort-select" onchange="this.form.submit()">
            <option value="">Sắp xếp</option>
            <option value="price_asc" <?php selected($sort, 'price_asc'); ?>>Giá: Thấp → Cao</option>
            <option value="price_desc" <?php selected($sort, 'price_desc'); ?>>Giá: Cao → Thấp</option>
            <option value="name_asc" <?php selected($sort, 'name_asc'); ?>>Tên: A → Z</option>
            <option value="name_desc" <?php selected($sort, 'name_desc'); ?>>Tên: Z → A</option>
          </select>
        </form>
      </div>
    </div>
  </section>

  <section class="page-section">
    <div class="product-grid">
      <?php if ($loop->have_posts()) : while ($loop->have_posts()) : $loop->the_post(); global $product; ?>
        <article <?php wc_product_class('product-card', $product); ?> >
          <a class="product-card__thumb" href="<?php the_permalink(); ?>">
            <?php if ($product->is_on_sale()) : ?><span class="product-card__badge badge-sale">Giảm giá</span><?php endif; ?>
            <?php if (!$product->is_in_stock()) : ?><span class="product-card__badge">Hết hàng</span><?php endif; ?>
            <?php echo woocommerce_get_product_thumbnail(); ?>
          </a>
          <div class="product-card__body">
            <div class="product-card__brand">Sản phẩm</div>
            <h3 class="product-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
            <div class="product-card__price"><?php woocommerce_template_single_price(); ?></div>
            <?php if (function_exists('my_theme_render_capacity_weight')) { my_theme_render_capacity_weight($product); } ?>
            <?php if (function_exists('my_theme_render_capacity_badges')) { my_theme_render_capacity_badges($product); } ?>
          </div>
          <div class="product-card__actions">
            <?php woocommerce_template_loop_add_to_cart(); ?>
            <a class="btn btn-outline w-100" href="<?php the_permalink(); ?>">Xem chi tiết</a>
          </div>
        </article>
      <?php endwhile; else: ?>
        <div class="no-products"><p>Không tìm thấy sản phẩm phù hợp.</p></div>
      <?php endif; wp_reset_postdata(); ?>
    </div>

    <?php
    $total_pages = $loop->max_num_pages;
    if ($total_pages > 1) {
      echo '<nav class="pagination-wrapper">';
      echo paginate_links([
        'total' => $total_pages,
        'current' => max(1, get_query_var('paged')),
        'prev_text' => 'Trước',
        'next_text' => 'Sau',
      ]);
      echo '</nav>';
    }
    ?>
    <div class="cta" style="margin-top:20px;">
      <div>
        <h3>Cần báo giá theo khối lượng?</h3>
        <p>Đơn số lượng lớn sẽ có chiết khấu riêng và hỗ trợ giao tận nơi.</p>
      </div>
      <div>
        <a class="btn btn-primary" href="tel:0944857999">Gọi báo giá</a>
        <a class="btn btn-outline" href="https://zalo.me/0944857999" target="_blank" rel="noopener">Zalo báo giá</a>
        <a class="btn btn-accent" href="<?php echo esc_url(home_url('/lien-he')); ?>">Gửi yêu cầu nhanh</a>
      </div>
    </div>
  </section>
</div>
<?php get_footer();
