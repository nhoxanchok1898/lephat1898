<?php
/**
 * Custom single product content template.
 */

defined('ABSPATH') || exit;

global $product;

do_action('woocommerce_before_single_product');

if (post_password_required()) {
    echo get_the_password_form();
    return;
}

$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop');
$product_title = function_exists('my_theme_get_product_display_name')
  ? my_theme_get_product_display_name($product)
  : get_the_title();
$single_product_business = function_exists('my_theme_get_business_profile') ? my_theme_get_business_profile() : [];
$single_product_phone_href = isset($single_product_business['phone_href']) ? (string) $single_product_business['phone_href'] : 'tel:0944857999';
$single_product_zalo_url = isset($single_product_business['zalo_url']) ? (string) $single_product_business['zalo_url'] : 'https://zalo.me/0944857999';
$single_product_contact_url = home_url('/lien-he');
$single_product_solution_group = function_exists('my_theme_get_visual_story_group_key_for_object') ? my_theme_get_visual_story_group_key_for_object($product) : '';
$single_product_solution_catalog = function_exists('my_theme_get_visual_story_group_catalog') ? my_theme_get_visual_story_group_catalog() : [];
$single_product_solution = ($single_product_solution_group !== '' && isset($single_product_solution_catalog[$single_product_solution_group]))
  ? (array) $single_product_solution_catalog[$single_product_solution_group]
  : [];
$single_product_knowledge_bundle = function_exists('my_theme_get_group_knowledge_bundle')
  ? (array) my_theme_get_group_knowledge_bundle($single_product_solution_group)
  : [];
$single_product_quick_answers = isset($single_product_knowledge_bundle['quick_answers']) && is_array($single_product_knowledge_bundle['quick_answers'])
  ? $single_product_knowledge_bundle['quick_answers']
  : [];
$single_product_article_slugs = isset($single_product_knowledge_bundle['article_slugs']) && is_array($single_product_knowledge_bundle['article_slugs'])
  ? $single_product_knowledge_bundle['article_slugs']
  : [];
$single_media_class = 'single-product-layout__media';
$single_media_note = '';
if ($product instanceof WC_Product) {
  $single_thumb_id = (int) get_post_thumbnail_id($product->get_id());
  if ($single_thumb_id > 0) {
    $single_thumb_meta = wp_get_attachment_metadata($single_thumb_id);
    $single_thumb_width = is_array($single_thumb_meta) && isset($single_thumb_meta['width']) ? (int) $single_thumb_meta['width'] : 0;
    $single_thumb_height = is_array($single_thumb_meta) && isset($single_thumb_meta['height']) ? (int) $single_thumb_meta['height'] : 0;
    if ($single_thumb_width > 0 && $single_thumb_height > 0 && ($single_thumb_width < 300 || $single_thumb_height < 300)) {
      $single_media_class .= ' single-product-layout__media--small-source';
      $single_media_note = 'Ảnh packshot nguồn hãng hiện có độ phân giải nhỏ hơn nhóm sản phẩm khác.';
    }
  }
}
?>

<div id="product-<?php the_ID(); ?>" <?php wc_product_class('single-product-card', $product); ?>>
  <nav class="breadcrumb-nav" aria-label="Đường dẫn">
    <ol class="breadcrumb">
      <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
      <li><a href="<?php echo esc_url($shop_url); ?>">Cửa hàng</a></li>
      <li><?php echo esc_html($product_title); ?></li>
    </ol>
  </nav>

  <div class="single-product-layout">
    <div class="<?php echo esc_attr($single_media_class); ?>">
      <?php do_action('woocommerce_before_single_product_summary'); ?>
      <?php if ($single_media_note !== '') : ?>
        <p class="single-product-layout__media-note"><?php echo esc_html($single_media_note); ?></p>
      <?php endif; ?>
    </div>

    <div class="single-product-layout__summary summary entry-summary">
      <?php do_action('woocommerce_single_product_summary'); ?>
    </div>
  </div>

  <?php
    $full_description = '';
    $spec_lines = [];
    $fallback_overview = '';
    $official_source_url = '';
    $official_documents = [];
    if ($product instanceof WC_Product) {
      $raw_description = trim((string) $product->get_description());
      if ($raw_description !== '') {
        $full_description = apply_filters('the_content', $raw_description);
      }
      if (function_exists('my_theme_get_capacity_weight')) {
        [$capacity_text, $weight_value, $weight_text] = my_theme_get_capacity_weight($product);
        if ($capacity_text !== '') {
          $spec_lines[] = 'Dung tích: ' . $capacity_text;
        }
        if ($weight_text !== '') {
          $spec_lines[] = 'Khối lượng: ' . $weight_text;
        } elseif ($weight_value !== '') {
          $spec_lines[] = 'Khối lượng: ' . wc_format_weight($weight_value);
        }
      }
      if (function_exists('my_theme_get_package_summary_text')) {
        $package_text = trim((string) my_theme_get_package_summary_text($product));
        if ($package_text !== '') {
          $spec_lines[] = 'Quy cách: ' . $package_text;
        }
      }
      if ($full_description === '' && function_exists('my_theme_get_product_card_excerpt')) {
        $fallback_overview = trim((string) my_theme_get_product_card_excerpt($product, 34));
      }
      if (function_exists('my_theme_get_product_official_source_url')) {
        $official_source_url = trim((string) my_theme_get_product_official_source_url($product));
      }
      if (function_exists('my_theme_get_product_official_documents')) {
        $official_documents = (array) my_theme_get_product_official_documents($product);
      }
    }
  ?>
  <?php if ($full_description !== '' || !empty($spec_lines) || $fallback_overview !== '') : ?>
    <section class="page-section product-description-block" aria-label="Thông tin sản phẩm">
      <div class="section-heading">
        <h2 class="section-title">Thông tin sản phẩm</h2>
      </div>
      <?php if (!empty($spec_lines)) : ?>
        <div class="product-description-specs">
          <?php foreach ($spec_lines as $line) : ?>
            <span class="product-description-spec"><?php echo esc_html($line); ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php if ($full_description !== '') : ?>
        <div class="product-description-content">
          <?php echo wp_kses_post($full_description); ?>
        </div>
      <?php elseif ($fallback_overview !== '') : ?>
        <div class="product-description-content">
          <p><?php echo esc_html($fallback_overview); ?></p>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <?php if (function_exists('my_theme_render_visual_story_gallery')) : ?>
    <?php my_theme_render_visual_story_gallery($product, [
      'title' => 'Ứng dụng minh họa',
      'subtitle' => 'Ảnh minh họa tham khảo theo nhóm công trình và bề mặt để dễ hình dung hơn khi chọn vật tư.',
      'class' => 'visual-story--product',
    ]); ?>
  <?php endif; ?>

  <?php if (!empty($single_product_solution)) : ?>
    <section class="page-section product-solution-bridge" aria-label="Giải pháp phù hợp">
      <div class="section-heading">
        <div>
          <h2 class="section-title">Giải pháp phù hợp với sản phẩm này</h2>
          <p class="section-sub">Nếu bạn đang so sánh vật tư theo bề mặt hoặc hạng mục, nên xem thêm landing page tương ứng để có ảnh minh họa, FAQ ngắn và cách chốt hệ nhanh hơn.</p>
        </div>
      </div>
      <div class="info-grid product-solution-bridge__grid">
        <div class="info-card">
          <h3><?php echo esc_html((string) ($single_product_solution['label'] ?? 'Giải pháp phù hợp')); ?></h3>
          <p><?php echo esc_html((string) ($single_product_solution['description'] ?? '')); ?></p>
          <div class="product-solution-bridge__actions">
            <a class="btn btn-primary btn-sm" href="<?php echo esc_url((string) ($single_product_solution['url'] ?? home_url('/giai-phap'))); ?>">
              <?php echo esc_html((string) ($single_product_solution['cta'] ?? 'Xem giải pháp')); ?>
            </a>
            <a class="btn btn-outline btn-sm" href="<?php echo esc_url(home_url('/giai-phap')); ?>">Xem tất cả giải pháp</a>
          </div>
        </div>
        <div class="info-card">
          <h3>Cần chốt nhanh theo bề mặt?</h3>
          <p>Gửi ảnh hiện trạng, diện tích hoặc số lớp dự kiến để đội kỹ thuật gợi ý đúng nhóm vật tư và quy cách trước khi chốt đơn.</p>
          <div class="product-solution-bridge__actions">
            <a class="btn btn-outline btn-sm" href="<?php echo esc_url($single_product_contact_url); ?>">Gửi yêu cầu báo giá</a>
          </div>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <?php if (!empty($single_product_quick_answers) && function_exists('my_theme_render_quick_answers')) : ?>
    <?php my_theme_render_quick_answers([
      'cards' => $single_product_quick_answers,
      'title' => 'Một vài câu hỏi nên chốt trước khi đặt mã này',
      'subtitle' => 'Các câu hỏi ngắn dưới đây giúp khách tự đối chiếu nhanh hiện trạng, nhu cầu sử dụng và thông tin nên gửi trước khi chốt vật tư.',
      'class' => 'quick-answers--product',
      'eyebrow' => 'Chốt nhanh trước khi đặt',
    ]); ?>
  <?php endif; ?>

  <?php if (function_exists('my_theme_render_single_product_buying_guide')) : ?>
    <?php my_theme_render_single_product_buying_guide($product, $single_product_solution_group); ?>
  <?php endif; ?>

  <?php if ($official_source_url !== '' || !empty($official_documents)) : ?>
    <section class="page-section product-resource-section" aria-label="Nguồn hãng và tài liệu kỹ thuật">
      <div class="section-heading">
        <div>
          <h2 class="section-title">Nguồn hãng & tài liệu kỹ thuật</h2>
          <p class="section-sub">Tra cứu nhanh trang hãng và các tài liệu PDF còn được công bố cho mã sản phẩm này.</p>
        </div>
      </div>

      <div class="product-resource-grid">
        <?php if ($official_source_url !== '') : ?>
          <article class="product-resource-card">
            <div class="product-resource-card__eyebrow">Nguồn tham chiếu</div>
            <h3>Trang sản phẩm chính hãng</h3>
            <p>Dùng để đối chiếu mô tả, quy cách, dòng sản phẩm và các cập nhật mới nhất từ hãng.</p>
            <div class="product-resource-actions">
              <a class="btn btn-outline" href="<?php echo esc_url($official_source_url); ?>" target="_blank" rel="noopener nofollow">Mở trang hãng</a>
            </div>
          </article>
        <?php endif; ?>

        <?php if (!empty($official_documents)) : ?>
          <article class="product-resource-card">
            <div class="product-resource-card__eyebrow">PDF kèm theo</div>
            <h3>Tài liệu kỹ thuật liên quan</h3>
            <div class="product-resource-list">
              <?php foreach ($official_documents as $doc) : ?>
                <?php
                  $doc_url = isset($doc['url']) ? (string) $doc['url'] : '';
                  $doc_label = isset($doc['label']) ? (string) $doc['label'] : 'PDF hãng';
                  $doc_type = isset($doc['type']) ? (string) $doc['type'] : 'PDF hãng';
                  $doc_lang = isset($doc['lang']) ? (string) $doc['lang'] : '';
                  if ($doc_url === '') {
                    continue;
                  }
                ?>
                <a class="product-resource-link" href="<?php echo esc_url($doc_url); ?>" target="_blank" rel="noopener nofollow">
                  <span class="product-resource-link__text"><?php echo esc_html($doc_label); ?></span>
                  <span class="product-resource-link__meta">
                    <span class="product-resource-badge"><?php echo esc_html($doc_type); ?></span>
                    <?php if ($doc_lang !== '') : ?>
                      <span class="product-resource-badge product-resource-badge--muted"><?php echo esc_html($doc_lang); ?></span>
                    <?php endif; ?>
                  </span>
                </a>
              <?php endforeach; ?>
            </div>
          </article>
        <?php endif; ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if (!empty($single_product_article_slugs) && function_exists('my_theme_render_article_recommendations')) : ?>
    <?php my_theme_render_article_recommendations($single_product_article_slugs, [
      'title' => 'Bài nên đọc cùng nhóm sản phẩm này',
      'subtitle' => 'Nếu bạn chưa chốt hẳn theo hiện trạng bề mặt hoặc ngân sách, nên xem thêm vài bài tư vấn nền tảng để tránh chọn lệch hệ vật tư.',
      'class' => 'article-recommendations--product',
    ]); ?>
  <?php endif; ?>

  <?php if (function_exists('my_theme_render_product_companion_paths')) : ?>
    <?php my_theme_render_product_companion_paths($single_product_solution_group, $product, [
      'class' => 'product-companion-paths--single',
    ]); ?>
  <?php endif; ?>

  <section class="page-section single-product-support" aria-label="Hỗ trợ đặt hàng và giao hàng">
    <div class="section-heading">
      <div>
        <h2 class="section-title">Hỗ trợ đặt hàng & giao vật tư</h2>
        <p class="section-sub">Bổ sung thông tin để chốt mã sơn, khối lượng và tiến độ giao nhanh hơn.</p>
      </div>
    </div>

    <div class="info-grid">
      <div class="info-card">
        <h3>Tư vấn theo bề mặt</h3>
        <p>Đội kỹ thuật hỗ trợ chọn hệ lót, phủ và lớp hoàn thiện theo bề mặt thực tế, tránh mua lệch hệ sơn.</p>
      </div>
      <div class="info-card">
        <h3>Giao theo tiến độ</h3>
        <p>Đơn nội thành có thể giao trong ngày hoặc 24 giờ. Công trình được tách đợt giao theo lịch thi công khi cần.</p>
      </div>
      <div class="info-card">
        <h3>Hóa đơn & chứng từ</h3>
        <p>Có xuất hóa đơn và hỗ trợ chứng từ theo yêu cầu công trình. Hàng pha màu hoặc đơn đặc thù sẽ được xác nhận trước khi chốt.</p>
      </div>
    </div>

    <div class="content-block">
      <h3>Để chốt đơn nhanh, nên gửi trước</h3>
      <ol class="list-numbered">
        <li>Diện tích hoặc số khu vực cần thi công và số lớp dự kiến.</li>
        <li>Bề mặt sử dụng: tường mới, tường cũ, mái, sàn, kim loại hoặc khu vực chống thấm.</li>
        <li>Mã sơn, thương hiệu hoặc dòng sản phẩm đang so sánh nếu đã có.</li>
        <li>Địa điểm giao hàng và mốc thời gian cần nhận vật tư.</li>
      </ol>
    </div>

    <div class="cta-inline content-block__cta-inline">
      <div class="cta-inline__content">
        <div>
          <h3>Cần chốt nhanh theo m² hoặc theo hạng mục?</h3>
          <p class="text-muted">Gửi nhu cầu để nhận gợi ý vật tư, báo giá và lịch giao phù hợp công trình.</p>
        </div>
        <div class="cta-inline__actions">
          <a class="btn btn-primary" href="<?php echo esc_url($single_product_phone_href); ?>">Gọi tư vấn ngay</a>
          <a class="btn btn-outline" href="<?php echo esc_url($single_product_zalo_url); ?>" target="_blank" rel="noopener">Zalo kỹ thuật</a>
          <a class="btn btn-accent" href="<?php echo esc_url($single_product_contact_url); ?>">Gửi yêu cầu báo giá</a>
        </div>
      </div>
    </div>
  </section>

  <?php
  $single_product_id = ($product instanceof WC_Product) ? (int) $product->get_id() : 0;
  $single_product_lead_title = 'Cần chốt đủ hệ vật tư cho mã ' . (string) $product_title . '?';
  $single_product_lead_subtitle = 'Gửi diện tích, bề mặt, số lớp dự kiến hoặc ảnh hiện trạng. Đội kỹ thuật sẽ hỗ trợ kiểm tra thêm lớp lót, phủ, quy cách và tiến độ giao phù hợp trước khi bạn chốt đơn.';
  if (function_exists('my_theme_render_lead_capture_form')) {
      echo my_theme_render_lead_capture_form([
          'source' => 'product-' . $single_product_id,
          'title' => $single_product_lead_title,
          'subtitle' => $single_product_lead_subtitle,
          'button' => 'Gửi nhu cầu cho mã này',
      ]);
  }
  ?>

  <?php if (function_exists('my_theme_render_single_product_colour_chart')) : ?>
    <?php my_theme_render_single_product_colour_chart($product, 40); ?>
  <?php endif; ?>

  <?php if (function_exists('my_theme_render_paint_calculator')) : ?>
    <?php my_theme_render_paint_calculator(); ?>
  <?php endif; ?>

  <?php
    $related_products = function_exists('my_theme_get_related_products_for_display')
      ? my_theme_get_related_products_for_display($product, 4)
      : [];

    wc_get_template(
      'single-product/related.php',
      [
        'related_products' => $related_products,
        'posts_per_page'   => 4,
        'columns'          => 4,
      ]
    );
  ?>

  <?php
  if (function_exists('my_theme_render_recently_viewed_products')) {
      my_theme_render_recently_viewed_products([
          'title' => 'Quay lại các mã bạn vừa xem',
          'aria_label' => 'Quay lại các mã bạn vừa xem',
          'class' => 'related-products-block--recently-viewed related-products-block--single',
          'exclude_ids' => [$single_product_id],
      ]);
  }
  ?>
</div>

<?php do_action('woocommerce_after_single_product'); ?>
