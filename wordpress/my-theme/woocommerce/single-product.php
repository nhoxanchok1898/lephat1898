<?php
defined('ABSPATH') || exit;

get_header();
?>
<main id="main-content">
  <div class="container">
    <?php while (have_posts()) : the_post(); ?>
      <?php wc_get_template_part('content', 'single-product'); ?>
    <?php endwhile; ?>
  </div>
</main>
<?php get_footer();
