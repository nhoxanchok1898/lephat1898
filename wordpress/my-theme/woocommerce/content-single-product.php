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
?>

<div id="product-<?php the_ID(); ?>" <?php wc_product_class('single-product-card', $product); ?>>
  <div class="single-product-layout">
    <div class="single-product-layout__media">
      <?php do_action('woocommerce_before_single_product_summary'); ?>
    </div>

    <div class="single-product-layout__summary summary entry-summary">
      <?php do_action('woocommerce_single_product_summary'); ?>
    </div>
  </div>

  <?php
    $full_description = '';
    $spec_lines = [];
    if ($product instanceof WC_Product) {
      $raw_description = trim((string) $product->get_description());
      if ($raw_description !== '') {
        $full_description = apply_filters('the_content', $raw_description);
      }
      $is_putty = function_exists('my_theme_is_putty_product')
        ? my_theme_is_putty_product($product)
        : false;
      // Keep single page concise: pack sizes are already shown in price/picker block.
      $allow_specs = $is_putty || $product->is_type('variable');
      if (function_exists('my_theme_get_capacity_weight')) {
        [$capacity_text, $weight_value, $weight_text] = my_theme_get_capacity_weight($product);
        if ($allow_specs && !$is_putty && $capacity_text !== '') {
          $spec_lines[] = 'Dung tích: ' . $capacity_text;
        }
        if ($allow_specs && $weight_text !== '') {
          $spec_lines[] = 'Khối lượng: ' . $weight_text;
        } elseif ($allow_specs && $weight_value !== '') {
          $spec_lines[] = 'Khối lượng: ' . wc_format_weight($weight_value);
        }
      }
    }
  ?>
  <?php if ($full_description !== '' || !empty($spec_lines)) : ?>
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
      <?php endif; ?>
    </section>
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
</div>

<?php do_action('woocommerce_after_single_product'); ?>
