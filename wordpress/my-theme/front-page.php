<?php
/** Trang chu tong hop */
get_header();
?>
<main id="main-content">
  <div class="container home-page home-page--integrated">
    <div class="home-top-combined">
      <?php get_template_part('template-parts/home', 'hero'); ?>
      <?php get_template_part('template-parts/home', 'hub'); ?>
    </div>
    <?php get_template_part('template-parts/home', 'solutions'); ?>
    <?php get_template_part('template-parts/home', 'service-compass'); ?>
    <?php
    if (function_exists('my_theme_render_commerce_support')) {
        my_theme_render_commerce_support('home');
    }
    ?>
    <?php get_template_part('template-parts/home', 'featured'); ?>
    <?php get_template_part('template-parts/home', 'projects'); ?>
    <?php get_template_part('template-parts/home', 'faq-teaser'); ?>
    <?php get_template_part('template-parts/home', 'posts'); ?>
    <?php get_template_part('template-parts/paint-calculator'); ?>
    <?php get_template_part('template-parts/home', 'lead-capture'); ?>
    <?php get_template_part('template-parts/home', 'cta-inline'); ?>
  </div>
</main>
<?php get_footer();
