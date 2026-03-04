<?php
/** Page template aligned with old layout */
get_header();
$page_business = function_exists('my_theme_get_business_profile') ? my_theme_get_business_profile() : [];
$page_phone_href = isset($page_business['phone_href']) ? (string) $page_business['phone_href'] : 'tel:0944857999';
$page_zalo_url = isset($page_business['zalo_url']) ? (string) $page_business['zalo_url'] : 'https://zalo.me/0944857999';
?>
<main id="main-content">
  <div class="container">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
      <?php
      $current_page_id = (int) get_queried_object_id();
      $cart_page_id = function_exists('wc_get_page_id') ? (int) wc_get_page_id('cart') : 0;
      $checkout_page_id = function_exists('wc_get_page_id') ? (int) wc_get_page_id('checkout') : 0;
      $account_page_id = function_exists('wc_get_page_id') ? (int) wc_get_page_id('myaccount') : 0;
      $is_commerce_page = (
        (function_exists('is_cart') && is_cart()) ||
        (function_exists('is_checkout') && is_checkout()) ||
        (function_exists('is_account_page') && is_account_page()) ||
        ($current_page_id > 0 && in_array($current_page_id, array_filter([$cart_page_id, $checkout_page_id, $account_page_id]), true))
      );
      $article_class = 'page-section single-article page-shell' . ($is_commerce_page ? ' page-shell--commerce' : '');
      ?>
      <article class="<?php echo esc_attr($article_class); ?>">
        <ul class="breadcrumb">
          <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
          <li><?php the_title(); ?></li>
        </ul>
        <h1 class="page-title"><?php the_title(); ?></h1>
        <?php if (!$is_commerce_page) : ?>
          <div class="cta-compact">
            <div>
              <strong>Nhận tư vấn hệ sơn phù hợp</strong>
              <p class="text-muted">Gọi số tư vấn hoặc Zalo để được báo giá theo diện tích.</p>
            </div>
            <div class="cta-compact__actions">
              <a class="btn btn-primary btn-sm" href="<?php echo esc_url($page_phone_href); ?>">Gọi tư vấn</a>
              <a class="btn btn-outline btn-sm" href="<?php echo esc_url($page_zalo_url); ?>" target="_blank" rel="noopener">Zalo kỹ thuật</a>
            </div>
          </div>
        <?php endif; ?>

        <div class="entry-content<?php echo $is_commerce_page ? ' entry-content--commerce' : ''; ?>"><?php the_content(); ?></div>
        <?php
        if (
          $is_commerce_page &&
          function_exists('is_order_received_page') &&
          is_order_received_page() &&
          function_exists('my_theme_render_checkout_thankyou_panel')
        ) {
          my_theme_render_checkout_thankyou_panel(0);
        }
        ?>

        <?php if (!$is_commerce_page) : ?>
          <?php get_template_part('template-parts/home', 'cta-inline'); ?>
          <div class="cta">
          <div>
            <h3>Cần báo giá nhanh?</h3>
            <p>Gửi yêu cầu, chúng tôi phản hồi trong 15 phút giờ hành chính.</p>
          </div>
          <div>
            <a class="btn btn-primary" href="<?php echo esc_url($page_phone_href); ?>">Gọi báo giá</a>
            <a class="btn btn-outline" href="<?php echo esc_url($page_zalo_url); ?>" target="_blank" rel="noopener">Zalo tư vấn</a>
            <a class="btn btn-accent" href="<?php echo esc_url(home_url('/lien-he')); ?>">Gửi yêu cầu</a>
          </div>
        </div>
        <?php endif; ?>
      </article>
    <?php endwhile; endif; ?>
  </div>
</main>
<?php get_footer();
