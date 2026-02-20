<?php
/** Trang chủ bán hàng */
get_header();
?>
<main id="main-content">
  <div class="container">
    <?php get_template_part('template-parts/home', 'hero'); ?>
    <?php get_template_part('template-parts/home', 'commitments'); ?>
    <?php get_template_part('template-parts/home', 'brands'); ?>
    <?php get_template_part('template-parts/home', 'categories'); ?>
    <?php get_template_part('template-parts/home', 'cta-inline'); ?>
    <?php get_template_part('template-parts/price-faq'); ?>
    <?php get_template_part('template-parts/home', 'featured'); ?>
    <?php get_template_part('template-parts/home', 'reasons'); ?>
    <?php get_template_part('template-parts/home', 'posts'); ?>
    <?php get_template_part('template-parts/home', 'cta'); ?>
  </div>
</main>
<?php get_footer();
