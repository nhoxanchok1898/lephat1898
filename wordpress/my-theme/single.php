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
      <?php
      $article_id = (int) get_the_ID();
      $article_title = trim((string) get_the_title());
      $article_slug = sanitize_title((string) get_post_field('post_name', $article_id));
      $article_solution_group = function_exists('my_theme_get_visual_story_group_key_for_object')
        ? my_theme_get_visual_story_group_key_for_object(get_post())
        : '';
      $article_solution_catalog = function_exists('my_theme_get_visual_story_group_catalog')
        ? my_theme_get_visual_story_group_catalog()
        : [];
      $article_solution = ($article_solution_group !== '' && isset($article_solution_catalog[$article_solution_group]))
        ? (array) $article_solution_catalog[$article_solution_group]
        : [];
      $article_knowledge_bundle = function_exists('my_theme_get_group_knowledge_bundle')
        ? (array) my_theme_get_group_knowledge_bundle($article_solution_group)
        : [];
      $article_quick_answers = isset($article_knowledge_bundle['quick_answers']) && is_array($article_knowledge_bundle['quick_answers'])
        ? $article_knowledge_bundle['quick_answers']
        : [];
      $article_article_slugs = isset($article_knowledge_bundle['article_slugs']) && is_array($article_knowledge_bundle['article_slugs'])
        ? $article_knowledge_bundle['article_slugs']
        : [];
      $article_solution_url = isset($article_solution['url']) ? (string) $article_solution['url'] : home_url('/giai-phap');
      $article_solution_label = isset($article_solution['label']) ? (string) $article_solution['label'] : 'Giải pháp phù hợp';
      $article_solution_title = isset($article_solution['title']) ? (string) $article_solution['title'] : $article_solution_label;
      $article_solution_description = isset($article_solution['description']) ? (string) $article_solution['description'] : '';
      $article_solution_label_lower = function_exists('mb_strtolower')
        ? mb_strtolower($article_solution_label, 'UTF-8')
        : strtolower($article_solution_label);
      $article_compass_cards = [];
      if (!empty($article_solution)) {
          $article_compass_cards[] = [
              'eyebrow' => 'Theo đúng bài này',
              'title' => $article_solution_label,
              'description' => $article_solution_description !== '' ? $article_solution_description : $article_solution_title,
              'url' => $article_solution_url,
              'cta' => 'Mở ' . $article_solution_label_lower,
          ];
      }
      ?>
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
        <?php if (!empty($article_solution)) : ?>
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
        <?php
        if (function_exists('my_theme_render_quick_answers')) {
            $article_quick_answer_args = [
                'class' => 'quick-answers--article',
                'eyebrow' => 'Chốt nhanh sau khi đọc',
                'title' => 'Một vài câu hỏi nên tự đối chiếu trước khi chốt vật tư',
                'subtitle' => 'Các câu hỏi ngắn này giúp bạn kiểm tra lại bề mặt, định mức và thông tin cần gửi trước khi gọi hoặc nhắn báo giá.',
            ];
            if (!empty($article_quick_answers)) {
                $article_quick_answer_args['cards'] = $article_quick_answers;
                $article_quick_answer_args['title'] = 'Câu hỏi nhanh đúng với nhóm nội dung của bài này';
                $article_quick_answer_args['subtitle'] = 'Nếu bài viết đang gần với nhu cầu của bạn, hãy đối chiếu nhanh các câu hỏi này để rút ngắn bước tư vấn và chốt hệ vật tư.';
            } else {
                $article_quick_answer_args['indexes'] = [0, 1, 4];
            }
            my_theme_render_quick_answers($article_quick_answer_args);
        }

        if (!empty($article_article_slugs) && function_exists('my_theme_render_article_recommendations')) {
            my_theme_render_article_recommendations($article_article_slugs, [
                'title' => 'Bài nên đọc tiếp nếu bạn vẫn đang so sánh',
                'subtitle' => 'Một vài bài nền tảng cùng nhóm để bạn nối tiếp từ phần đọc hiểu sang bước chọn giải pháp, chọn vật tư và chốt báo giá.',
                'class' => 'article-recommendations--article',
            ]);
        }

        if (function_exists('my_theme_render_service_compass')) {
            $article_compass_args = [
                'class' => 'service-compass--blog',
                'eyebrow' => 'Đọc bài rồi đi tiếp',
                'title' => 'Bài viết giúp hiểu vấn đề, còn chốt vật tư thì nên đi tiếp theo đường này',
                'subtitle' => 'Mở kho sản phẩm nếu bạn đã có mã. Vào landing page giải pháp nếu đang đi theo bề mặt. Hoặc gửi hiện trạng thực tế để đội kỹ thuật chốt nhanh hơn.',
            ];
            if (!empty($article_compass_cards)) {
                $article_compass_cards[] = [
                    'eyebrow' => 'Đã có mã sơn',
                    'title' => 'Mở kho sản phẩm',
                    'description' => 'Phù hợp khi bạn đã biết mã, dòng sơn hoặc muốn so sánh trực tiếp quy cách và thương hiệu.',
                    'url' => function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop'),
                    'cta' => 'Mở cửa hàng',
                ];
                $article_compass_cards[] = [
                    'eyebrow' => 'Chưa chốt hẳn',
                    'title' => 'Gửi hiện trạng để được điều hướng',
                    'description' => 'Gửi diện tích, ảnh bề mặt hoặc tiến độ công trình để đội kỹ thuật đi thẳng vào nhóm vật tư phù hợp thay vì trả lời chung.',
                    'url' => home_url('/lien-he'),
                    'cta' => 'Gửi yêu cầu báo giá',
                ];
                $article_compass_args['cards'] = $article_compass_cards;
            }
            my_theme_render_service_compass($article_compass_args);
        }

        if ($article_solution_group !== '' && function_exists('my_theme_render_product_companion_paths')) {
            my_theme_render_product_companion_paths($article_solution_group, null, [
                'title' => 'Đi thẳng vào nhóm sản phẩm liên quan đến bài này',
                'subtitle' => 'Nếu bạn đã hiểu hiện trạng qua bài viết, các lối đi bên dưới sẽ đưa bạn sang đúng nhóm vật tư, dòng sản phẩm hoặc giải pháp liên quan để chốt nhanh hơn.',
                'class' => 'product-companion-paths--article',
            ]);
        }
        ?>
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
        <?php
        if (function_exists('my_theme_render_lead_capture_form')) {
            echo my_theme_render_lead_capture_form([
                'source' => 'post-' . ($article_slug !== '' ? $article_slug : $article_id),
                'title' => $article_title !== '' ? 'Đọc xong "' . $article_title . '" nhưng vẫn chưa chốt được vật tư?' : 'Đọc xong nhưng vẫn chưa chốt được vật tư?',
                'subtitle' => 'Gửi bề mặt, diện tích, mã đang so sánh hoặc ảnh hiện trạng. Đội kỹ thuật sẽ nối từ nội dung bài viết sang đúng nhóm sản phẩm hoặc giải pháp phù hợp hơn.',
                'button' => 'Gửi nhu cầu sau khi đọc',
            ]);
        }

        if (function_exists('my_theme_render_recently_viewed_products')) {
            my_theme_render_recently_viewed_products([
                'title' => 'Quay lại các mã bạn vừa xem',
                'aria_label' => 'Quay lại các mã bạn vừa xem',
                'class' => 'related-products-block--recently-viewed related-products-block--article',
            ]);
        }
        ?>
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
