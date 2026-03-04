<?php
/** Single template aligned with old layout */
get_header();
$single_business = function_exists('my_theme_get_business_profile') ? my_theme_get_business_profile() : [];
$single_phone_href = isset($single_business['phone_href']) ? (string) $single_business['phone_href'] : 'tel:0944857999';
$single_zalo_url = isset($single_business['zalo_url']) ? (string) $single_business['zalo_url'] : 'https://zalo.me/0944857999';
?>
<main id="main-content">
  <div class="container">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
      <article class="page-section single-article page-shell">
        <ul class="breadcrumb">
          <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
          <li><?php the_title(); ?></li>
        </ul>
        <h1 class="page-title"><?php the_title(); ?></h1>
        <div class="post-meta"><?php echo get_the_date(); ?> · <?php the_author(); ?></div>
        <div class="cta-compact">
          <div>
            <strong>Đang cần tư vấn chọn sơn?</strong>
            <p class="text-muted">Kỹ thuật hỗ trợ miễn phí theo diện tích và bề mặt.</p>
          </div>
          <div class="cta-compact__actions">
            <a class="btn btn-primary btn-sm" href="<?php echo esc_url($single_phone_href); ?>">Gọi tư vấn</a>
            <a class="btn btn-outline btn-sm" href="<?php echo esc_url($single_zalo_url); ?>" target="_blank" rel="noopener">Zalo kỹ thuật</a>
          </div>
        </div>
        <?php if (has_post_thumbnail()) : ?>
          <figure class="single-article__hero">
            <?php the_post_thumbnail('large', ['loading' => 'eager', 'decoding' => 'async']); ?>
          </figure>
        <?php endif; ?>
        <div class="entry-content"><?php the_content(); ?></div>
        <?php if (function_exists('my_theme_render_visual_story_gallery')) : ?>
          <?php my_theme_render_visual_story_gallery(get_post(), [
            'title' => 'Hình minh họa công trình',
            'subtitle' => 'Ảnh minh họa theo hạng mục để khách dễ hình dung bề mặt, không gian sử dụng và tình huống thi công thực tế.',
            'class' => 'visual-story--article',
          ]); ?>
        <?php endif; ?>
        <?php
        $article_solution_group = function_exists('my_theme_get_visual_story_group_key_for_object')
          ? my_theme_get_visual_story_group_key_for_object(get_post())
          : '';
        $article_solution_catalog = function_exists('my_theme_get_visual_story_group_catalog')
          ? my_theme_get_visual_story_group_catalog()
          : [];
        if ($article_solution_group !== '' && isset($article_solution_catalog[$article_solution_group])) :
          $article_solution = (array) $article_solution_catalog[$article_solution_group];
          $article_solution_url = isset($article_solution['url']) ? (string) $article_solution['url'] : home_url('/giai-phap');
          $article_solution_label = isset($article_solution['label']) ? (string) $article_solution['label'] : 'Giải pháp phù hợp';
          $article_solution_title = isset($article_solution['title']) ? (string) $article_solution['title'] : $article_solution_label;
          $article_solution_description = isset($article_solution['description']) ? (string) $article_solution['description'] : '';
          $article_solution_label_lower = function_exists('mb_strtolower')
            ? mb_strtolower($article_solution_label, 'UTF-8')
            : strtolower($article_solution_label);
        ?>
        <section class="page-section article-solution-bridge" aria-label="Đi tiếp vào giải pháp phù hợp">
          <div class="section-heading">
            <div>
              <h2 class="section-title">Đi tiếp vào giải pháp phù hợp</h2>
              <p class="section-sub">Nếu bài viết này đúng với nhu cầu của bạn, bước tiếp theo nên là xem landing page tương ứng để thấy ảnh minh họa, sản phẩm gợi ý và form nhận báo giá.</p>
            </div>
          </div>
          <div class="info-grid article-solution-bridge__grid">
            <div class="info-card">
              <h3><?php echo esc_html($article_solution_label); ?></h3>
              <p><?php echo esc_html($article_solution_description !== '' ? $article_solution_description : $article_solution_title); ?></p>
              <div class="article-solution-bridge__actions">
                <a class="btn btn-primary btn-sm" href="<?php echo esc_url($article_solution_url); ?>">Mở <?php echo esc_html($article_solution_label_lower); ?></a>
                <a class="btn btn-outline btn-sm" href="<?php echo esc_url(home_url('/giai-phap')); ?>">Xem tất cả giải pháp</a>
              </div>
            </div>
            <div class="info-card">
              <h3>Gửi nhu cầu để chốt nhanh hơn</h3>
              <p>Landing page giải pháp sẽ giúp bạn đi thẳng vào nhóm vật tư, FAQ ngắn và form gửi ảnh hoặc diện tích để đội kỹ thuật phản hồi nhanh hơn.</p>
              <div class="article-solution-bridge__actions">
                <a class="btn btn-outline btn-sm" href="<?php echo esc_url(home_url('/lien-he')); ?>">Gửi yêu cầu báo giá</a>
              </div>
            </div>
          </div>
        </section>
        <?php endif; ?>
        <?php get_template_part('template-parts/home', 'cta-inline'); ?>
        <div class="info-grid">
          <div class="info-card">
            <h3>Phù hợp cho</h3>
            <ul class="list-plain">
              <li>Nhà ở, căn hộ, văn phòng, công trình dân dụng.</li>
              <li>Khách cần tư vấn chọn hệ sơn theo bề mặt.</li>
            </ul>
          </div>
          <div class="info-card">
            <h3>Ưu điểm nổi bật</h3>
            <ul class="list-plain">
              <li>Đại lý chính hãng, hàng mới, có chứng từ.</li>
              <li>Hỗ trợ kỹ thuật, định mức m² rõ ràng.</li>
            </ul>
          </div>
          <div class="info-card">
            <h3>Gợi ý sử dụng</h3>
            <ul class="list-plain">
              <li>Xem bảng giá và liên hệ tư vấn trước khi thi công.</li>
              <li>Gửi diện tích để nhận định mức và hệ sơn phù hợp.</li>
            </ul>
          </div>
        </div>
        <div class="info-grid article-links-grid">
          <div class="info-card">
            <h3>Mở kho sản phẩm</h3>
            <p>Xem nhanh danh mục sơn, chống thấm và phụ gia đang có để so sánh vật tư ngay.</p>
            <a class="btn btn-outline btn-sm" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop')); ?>">Xem cửa hàng</a>
          </div>
          <div class="info-card">
            <h3>Xem FAQ thi công</h3>
            <p>Kiểm tra các câu hỏi thường gặp về định mức, quy trình và điều kiện giao hàng.</p>
            <a class="btn btn-outline btn-sm" href="<?php echo esc_url(home_url('/faq')); ?>">Mở FAQ</a>
          </div>
          <div class="info-card">
            <h3>Nhận tư vấn công trình</h3>
            <p>Gửi mô tả bề mặt, diện tích và thời gian giao để nhận báo giá sát nhu cầu thực tế.</p>
            <a class="btn btn-accent btn-sm" href="<?php echo esc_url(home_url('/lien-he')); ?>">Gửi yêu cầu</a>
          </div>
        </div>
        <div class="cta">
          <div>
            <h3>Nhận báo giá theo m²</h3>
            <p>Gửi thông tin công trình, chúng tôi phản hồi trong 15 phút.</p>
          </div>
          <div>
            <a class="btn btn-primary" href="<?php echo esc_url($single_phone_href); ?>">Gọi báo giá</a>
            <a class="btn btn-outline" href="<?php echo esc_url($single_zalo_url); ?>" target="_blank" rel="noopener">Zalo tư vấn</a>
            <a class="btn btn-accent" href="<?php echo esc_url(home_url('/lien-he')); ?>">Gửi yêu cầu</a>
          </div>
        </div>
      </article>
    <?php endwhile; endif; ?>
  </div>
</main>
<?php get_footer();
