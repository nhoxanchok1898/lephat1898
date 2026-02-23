<?php
/** Trang chu tinh gon */
get_header();
?>
<main id="main-content">
  <div class="container">
    <?php get_template_part('template-parts/home', 'hero'); ?>
    <?php get_template_part('template-parts/home', 'commitments'); ?>
    <?php get_template_part('template-parts/home', 'categories'); ?>
    <?php get_template_part('template-parts/home', 'featured'); ?>
    <?php get_template_part('template-parts/paint-calculator'); ?>
    <?php get_template_part('template-parts/home', 'cta-inline'); ?>
  </div>
</main>
<?php get_footer();
