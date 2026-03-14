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

$package_summary = function_exists('my_theme_get_package_summary_text')
  ? trim((string) my_theme_get_package_summary_text($product))
  : '';

$single_media_class = 'single-product-layout__media';
$single_media_note = '';
if ($product instanceof WC_Product) {
  $single_thumb_id = (int) get_post_thumbnail_id($product->get_id());
  if ($single_thumb_id > 0) {
    $single_thumb_state = function_exists('my_theme_get_attachment_media_state')
      ? my_theme_get_attachment_media_state($single_thumb_id)
      : [];
    $single_thumb_width = isset($single_thumb_state['width']) ? (int) $single_thumb_state['width'] : 0;
    $single_thumb_height = isset($single_thumb_state['height']) ? (int) $single_thumb_state['height'] : 0;
    if ($single_thumb_width > 0 && $single_thumb_height > 0 && ($single_thumb_width < 300 || $single_thumb_height < 300)) {
      $single_media_class .= ' single-product-layout__media--small-source';
      $single_media_note = 'Ảnh sản phẩm đang dùng từ nguồn hãng có độ phân giải thấp.';
    }
  }
}

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

  if ($package_summary !== '') {
    $spec_lines[] = 'Quy cách: ' . $package_summary;
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

<div id="product-<?php the_ID(); ?>" <?php wc_product_class('single-product-card', $product); ?>>
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

  <?php if (function_exists('my_theme_render_single_product_family_layout')) : ?>
    <?php my_theme_render_single_product_family_layout($product); ?>
  <?php endif; ?>

  <?php if (function_exists('my_theme_render_single_product_support_layout')) : ?>
    <?php my_theme_render_single_product_support_layout($product); ?>
  <?php endif; ?>

  <?php if ($full_description !== '' || !empty($spec_lines) || $fallback_overview !== '') : ?>
    <section id="product-value" class="page-section product-description-block" aria-label="Thông tin giá trị sản phẩm">
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

  <?php if ($official_source_url !== '' || !empty($official_documents)) : ?>
    <section class="page-section product-resource-section" aria-label="Nguồn hãng và tài liệu kỹ thuật">
      <div class="section-heading">
        <h2 class="section-title">Tài liệu chính hãng</h2>
      </div>

      <div class="product-resource-grid">
        <?php if ($official_source_url !== '') : ?>
          <article class="product-resource-card">
            <h3>Trang hãng</h3>
            <div class="product-resource-actions">
              <a class="btn btn-outline" href="<?php echo esc_url($official_source_url); ?>" target="_blank" rel="noopener nofollow">Mở trang hãng</a>
            </div>
          </article>
        <?php endif; ?>

        <?php if (!empty($official_documents)) : ?>
          <article class="product-resource-card">
            <h3>PDF kỹ thuật</h3>
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

  <?php if (function_exists('my_theme_render_single_product_colour_chart')) : ?>
    <?php my_theme_render_single_product_colour_chart($product, 40); ?>
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
</div>

<?php do_action('woocommerce_after_single_product'); ?>
