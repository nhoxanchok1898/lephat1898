<?php
/** Page template aligned with old layout */
get_header();
$page_business = function_exists('my_theme_get_business_profile') ? my_theme_get_business_profile() : [];
$page_phone_href = isset($page_business['phone_href']) ? (string) $page_business['phone_href'] : 'tel:0944857999';
$page_zalo_url = isset($page_business['zalo_url']) ? (string) $page_business['zalo_url'] : 'https://zalo.me/0944857999';
$page_store_snapshot = function_exists('my_theme_get_store_snapshot') ? my_theme_get_store_snapshot() : [];
$page_hours = isset($page_store_snapshot['hours_display']) ? (string) $page_store_snapshot['hours_display'] : 'Thứ 2 - Thứ 7: 7:30 - 18:00';
$page_service_areas = isset($page_store_snapshot['service_areas_display']) ? (string) $page_store_snapshot['service_areas_display'] : 'TP.HCM, Bình Dương, Đồng Nai';
$page_catalog_count = isset($page_store_snapshot['catalog_count']) ? max(0, (int) $page_store_snapshot['catalog_count']) : 0;
?>
<main id="main-content">
  <div class="container">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
      <?php
      $current_page_id = (int) get_queried_object_id();
      $cart_page_id = function_exists('wc_get_page_id') ? (int) wc_get_page_id('cart') : 0;
      $checkout_page_id = function_exists('wc_get_page_id') ? (int) wc_get_page_id('checkout') : 0;
      $account_page_id = function_exists('wc_get_page_id') ? (int) wc_get_page_id('myaccount') : 0;
      $page_title = trim((string) get_the_title());
      $page_slug = sanitize_title((string) get_post_field('post_name', get_the_ID()));
      $page_content_raw = (string) get_post_field('post_content', get_the_ID());
      $page_has_inline_lead_form = function_exists('has_shortcode') && has_shortcode($page_content_raw, 'lead_capture_form');
      $page_has_paint_calculator = function_exists('has_shortcode') && has_shortcode($page_content_raw, 'paint_calculator');
      $page_context_links = function_exists('my_theme_get_page_context_links')
        ? my_theme_get_page_context_links($page_slug)
        : [];
      $is_commerce_page = (
        (function_exists('is_cart') && is_cart()) ||
        (function_exists('is_checkout') && is_checkout()) ||
        (function_exists('is_account_page') && is_account_page()) ||
        ($current_page_id > 0 && in_array($current_page_id, array_filter([$cart_page_id, $checkout_page_id, $account_page_id]), true))
      );
      $article_class = 'page-section single-article page-shell' . ($is_commerce_page ? ' page-shell--commerce' : '');
      $entry_content_class = 'entry-content'
        . ($is_commerce_page ? ' entry-content--commerce' : '')
        . ($page_has_paint_calculator ? ' entry-content--tool' : '');
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

          <section class="page-context-panel" aria-label="Lối đi nhanh từ trang này">
            <div class="page-context-panel__lead">
              <h2 class="page-context-panel__title">Nếu đã đọc xong, đây là các đường đi tiếp nhanh nhất</h2>
              <p class="page-context-panel__copy">Trang này giúp bạn hiểu thông tin nền. Khi cần chốt hàng, nên chuyển thẳng sang kho sản phẩm, giải pháp hoặc liên hệ kỹ thuật để không bị mất nhịp.</p>
            </div>
            <div class="shop-summary__insight" aria-label="Thông tin cửa hàng nhanh">
              <?php if ($page_catalog_count > 0) : ?><span class="chip chip--soft"><?php echo esc_html((string) $page_catalog_count); ?> sản phẩm đang có</span><?php endif; ?>
              <span class="chip chip--soft"><?php echo esc_html($page_hours); ?></span>
              <span class="chip chip--soft"><?php echo esc_html($page_service_areas); ?></span>
            </div>
            <?php if (!empty($page_context_links)) : ?>
              <div class="shop-summary__support" aria-label="Đi tiếp từ trang này">
                <?php foreach ($page_context_links as $page_context_link) : ?>
                  <?php
                  $page_context_label = isset($page_context_link['label']) ? trim((string) $page_context_link['label']) : '';
                  $page_context_url = isset($page_context_link['url']) ? trim((string) $page_context_link['url']) : '';
                  if ($page_context_label === '' || $page_context_url === '') {
                      continue;
                  }
                  ?>
                  <a class="chip" href="<?php echo esc_url($page_context_url); ?>"><?php echo esc_html($page_context_label); ?></a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </section>
        <?php endif; ?>

        <div class="<?php echo esc_attr($entry_content_class); ?>"><?php the_content(); ?></div>
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
          <?php
          if (function_exists('my_theme_render_service_compass')) {
              my_theme_render_service_compass([
                  'eyebrow' => 'Đọc nội dung rồi đi tiếp',
                  'title' => 'Nếu trang này đã đủ để bạn hiểu vấn đề, bước tiếp theo nên là gì?',
                  'subtitle' => 'Mở kho sản phẩm nếu bạn đã có mã hoặc hãng. Vào giải pháp nếu còn đang chọn theo bề mặt. Hoặc gửi nhu cầu thực tế để đội kỹ thuật điều hướng và báo giá nhanh hơn.',
              ]);
          }
          ?>
          <?php get_template_part('template-parts/home', 'cta-inline'); ?>
          <?php
          if (!$page_has_inline_lead_form && function_exists('my_theme_render_lead_capture_form')) {
              echo my_theme_render_lead_capture_form([
                  'source' => 'page-' . ($page_slug !== '' ? $page_slug : $current_page_id),
                  'title' => $page_title !== '' ? 'Cần tư vấn thêm cho trang "' . $page_title . '"?' : 'Cần tư vấn thêm cho nội dung này?',
                  'subtitle' => 'Gửi bề mặt, diện tích, mã đang cân nhắc hoặc tiến độ công trình để đội kỹ thuật chuyển bạn sang đúng nhóm vật tư hay landing page phù hợp hơn.',
                  'button' => 'Gửi nhu cầu từ trang này',
              ]);
          }

          if (function_exists('my_theme_render_recently_viewed_products')) {
              my_theme_render_recently_viewed_products([
                  'title' => 'Các mã bạn vừa xem trước khi mở trang này',
                  'aria_label' => 'Các mã bạn vừa xem trước khi mở trang này',
                  'class' => 'related-products-block--recently-viewed related-products-block--page',
              ]);
          }
          ?>
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
