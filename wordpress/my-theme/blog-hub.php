<?php
/**
 * Virtual blog hub route for /blog/.
 */

get_header();

$current_page = function_exists('my_theme_get_virtual_blog_page') ? my_theme_get_virtual_blog_page() : 1;
$current_page = max(1, (int) $current_page);
$blog_url = trailingslashit(home_url('/blog'));
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop');
$placeholder_post_ids = function_exists('my_theme_get_placeholder_blog_post_ids') ? my_theme_get_placeholder_blog_post_ids() : [];

$resolve_page_permalink = function ($path = '', $template_file = '') {
    $path = trim((string) $path, '/');
    if ($path !== '') {
        $page = get_page_by_path($path);
        if ($page instanceof WP_Post) {
            return (string) get_permalink($page);
        }
    }

    $template_file = trim((string) $template_file);
    if ($template_file !== '') {
        $pages = get_pages([
            'meta_key' => '_wp_page_template',
            'meta_value' => $template_file,
            'number' => 1,
        ]);
        if (!empty($pages) && $pages[0] instanceof WP_Post) {
            return (string) get_permalink($pages[0]);
        }
    }

    return '';
};

$posts_query = new WP_Query([
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => 12,
    'paged' => $current_page,
    'ignore_sticky_posts' => true,
    'post__not_in' => $placeholder_post_ids,
    'suppress_filters' => true,
]);

$published_total = function_exists('my_theme_get_public_blog_post_count')
    ? my_theme_get_public_blog_post_count()
    : 0;
$cards = [];

if ($posts_query->have_posts()) {
    while ($posts_query->have_posts()) {
        $posts_query->the_post();
        $title = trim((string) get_the_title());
        if ($title === '' || (function_exists('my_theme_is_placeholder_blog_post') && my_theme_is_placeholder_blog_post(get_post()))) {
            continue;
        }

        $excerpt = trim((string) get_the_excerpt());
        if ($excerpt === '') {
            $excerpt = wp_trim_words(wp_strip_all_tags((string) get_the_content()), 24);
        }

        $cards[] = [
            'title' => $title,
            'excerpt' => $excerpt,
            'url' => get_permalink(),
            'date' => get_the_date(),
            'thumb' => get_the_post_thumbnail(null, 'medium_large'),
            'thumb_label' => 'Bài viết',
            'cta_label' => 'Đọc tiếp',
        ];
    }
    wp_reset_postdata();
}

$guide_url = $resolve_page_permalink('huong-dan-mua-hang', 'page-huong-dan-mua-hang.php');
$faq_url = $resolve_page_permalink('faq', 'page-faq.php');
if ($faq_url === '') {
    $faq_url = $resolve_page_permalink('cau-hoi-thuong-gap', 'page-faq.php');
}
$contact_url = $resolve_page_permalink('lien-he', 'page-lien-he.php');

$fallback_resources = [];
foreach ([
    [
        'title' => 'Hướng dẫn mua hàng cho thợ và công trình',
        'excerpt' => 'Xem quy trình đặt hàng, xác nhận, giao hàng và các mốc phản hồi để chốt vật tư nhanh hơn.',
        'url' => $guide_url,
        'label' => 'Hướng dẫn',
    ],
    [
        'title' => 'Câu hỏi thường gặp khi chọn sơn và nhận hàng',
        'excerpt' => 'Tổng hợp các câu hỏi phổ biến về phối màu, hóa đơn, đổi trả và hỗ trợ kỹ thuật.',
        'url' => $faq_url,
        'label' => 'FAQ',
    ],
    [
        'title' => 'Liên hệ đội kỹ thuật để nhận báo giá nhanh',
        'excerpt' => 'Gửi diện tích, bề mặt và thời gian giao để nhận tư vấn hệ sơn sát nhu cầu thực tế.',
        'url' => $contact_url,
        'label' => 'Liên hệ',
    ],
    [
        'title' => 'Mở kho sản phẩm sơn chính hãng',
        'excerpt' => 'Đi thẳng vào danh mục sơn, chống thấm và phụ gia đang có để so sánh vật tư ngay.',
        'url' => $shop_url,
        'label' => 'Sản phẩm',
    ],
] as $resource) {
    if (!empty($resource['url'])) {
        $fallback_resources[] = $resource;
    }
}
?>
<main id="main-content">
  <div class="container home-page">
    <section class="page-section blog-shell blog-shell--hero">
      <nav class="breadcrumb-nav" aria-label="Đường dẫn">
        <ol class="breadcrumb">
          <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
          <li>Blog</li>
        </ol>
      </nav>

      <div class="section-heading">
        <div>
          <h1 class="page-title">Góc tư vấn thi công</h1>
          <p class="section-sub">Tổng hợp bài viết thực tế về chọn hệ sơn, định mức, quy trình thi công và kinh nghiệm đặt hàng theo từng bề mặt.</p>
        </div>
        <a class="btn btn-outline btn-sm" href="<?php echo esc_url($shop_url); ?>">Xem danh mục sản phẩm</a>
      </div>

      <div class="blog-shell__stats" aria-label="Tổng quan bài viết">
        <span><?php echo esc_html((string) max(0, $published_total)); ?> bài đã xuất bản</span>
        <span>Trang <?php echo esc_html((string) $current_page); ?></span>
      </div>

      <?php if (!empty($fallback_resources)) : ?>
        <div class="search-scope" aria-label="Lối tắt nội dung nên xem">
          <?php foreach ($fallback_resources as $resource) : ?>
            <a class="chip" href="<?php echo esc_url($resource['url']); ?>"><?php echo esc_html($resource['label']); ?></a>
          <?php endforeach; ?>
          <a class="chip" href="<?php echo esc_url($shop_url); ?>#brands">Theo thương hiệu</a>
        </div>
      <?php endif; ?>
    </section>

    <?php
    if (function_exists('my_theme_render_service_compass')) {
        my_theme_render_service_compass([
            'class' => 'service-compass--blog',
            'eyebrow' => 'Đọc xong rồi đi tiếp',
            'title' => 'Blog dùng để hiểu vấn đề, còn chốt vật tư thì đi tiếp theo 3 đường này',
            'subtitle' => 'Sau khi đọc tư vấn, bạn có thể mở kho sản phẩm, đi vào hub giải pháp hoặc gửi ảnh hiện trạng để đội kỹ thuật chốt nhanh hơn.',
        ]);
    }
    ?>

    <?php if (!empty($fallback_resources)) : ?>
      <section class="page-section blog-shell">
        <div class="section-heading">
          <div>
            <h2 class="section-title">Tài nguyên nên xem trước</h2>
            <p class="section-sub">Các lối tắt nhanh để chốt vật tư, quy trình đặt hàng và câu hỏi thường gặp.</p>
          </div>
        </div>
        <div class="insight-grid">
          <?php foreach ($fallback_resources as $resource) : ?>
            <article class="insight-card">
              <a class="insight-card__thumb insight-card__thumb--placeholder" href="<?php echo esc_url($resource['url']); ?>">
                <span class="insight-card__thumb-badge"><?php echo esc_html($resource['label']); ?></span>
                <strong class="insight-card__thumb-title"><?php echo esc_html($resource['title']); ?></strong>
              </a>
              <div class="insight-card__body">
                <div class="insight-card__date"><?php echo esc_html($resource['label']); ?></div>
                <h3 class="insight-card__title"><a href="<?php echo esc_url($resource['url']); ?>"><?php echo esc_html($resource['title']); ?></a></h3>
                <p class="insight-card__excerpt"><?php echo esc_html($resource['excerpt']); ?></p>
              </div>
              <div class="insight-card__actions">
                <a class="btn btn-primary w-100" href="<?php echo esc_url($resource['url']); ?>">Mở ngay</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <section class="page-section blog-shell blog-listing">
      <?php if (!empty($cards)) : ?>
        <?php $show_listing_support = count($cards) === 1 && !empty($fallback_resources); ?>
        <div class="section-heading">
          <div>
            <h2 class="section-title"><?php echo ($current_page > 1) ? 'Bài viết ở trang ' . esc_html((string) $current_page) : 'Bài viết mới nhất'; ?></h2>
            <p class="section-sub"><?php echo ($current_page > 1) ? 'Các bài viết hiện có trên trang này.' : 'Nội dung mới nhất về chọn hệ sơn, xử lý bề mặt và kinh nghiệm đặt vật tư thực tế.'; ?></p>
          </div>
        </div>
        <div class="blog-listing__layout<?php echo $show_listing_support ? ' blog-listing__layout--sparse' : ''; ?>">
          <div class="insight-grid">
            <?php foreach ($cards as $item) : ?>
              <article class="insight-card">
                <?php if (!empty($item['thumb'])) : ?>
                  <a class="insight-card__thumb" href="<?php echo esc_url($item['url']); ?>">
                    <?php echo $item['thumb']; ?>
                  </a>
                <?php else : ?>
                  <a class="insight-card__thumb insight-card__thumb--placeholder" href="<?php echo esc_url($item['url']); ?>">
                    <span class="insight-card__thumb-badge"><?php echo esc_html($item['thumb_label']); ?></span>
                    <strong class="insight-card__thumb-title"><?php echo esc_html($item['title']); ?></strong>
                  </a>
                <?php endif; ?>

                <div class="insight-card__body">
                  <div class="insight-card__date"><?php echo esc_html($item['date']); ?></div>
                  <h2 class="insight-card__title"><a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['title']); ?></a></h2>
                  <p class="insight-card__excerpt"><?php echo esc_html($item['excerpt']); ?></p>
                </div>

                <div class="insight-card__actions">
                  <a class="btn btn-primary w-100" href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['cta_label']); ?></a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>

          <?php if ($show_listing_support) : ?>
            <aside class="info-card blog-listing__support">
              <p class="eyebrow eyebrow-muted">Tài nguyên hỗ trợ</p>
              <h3>Đang bổ sung thêm bài viết chuyên sâu</h3>
              <p>Trong lúc chờ cập nhật, bạn có thể đi nhanh tới FAQ, hướng dẫn mua hàng hoặc liên hệ đội kỹ thuật để chốt vật tư theo công trình thực tế.</p>
              <div class="search-scope" aria-label="Tài nguyên nên xem thêm">
                <?php foreach ($fallback_resources as $resource) : ?>
                  <a class="chip" href="<?php echo esc_url($resource['url']); ?>"><?php echo esc_html($resource['label']); ?></a>
                <?php endforeach; ?>
              </div>
              <div class="trust-row" aria-label="Điểm hỗ trợ nổi bật">
                <span class="trust-item">Báo giá nhanh</span>
                <span class="trust-item">Tư vấn đúng bề mặt</span>
                <span class="trust-item">Giao vật tư theo lịch</span>
              </div>
              <div class="empty-state__actions">
                <a class="btn btn-primary" href="<?php echo esc_url($guide_url !== '' ? $guide_url : $shop_url); ?>">Xem hướng dẫn</a>
                <a class="btn btn-outline" href="<?php echo esc_url($contact_url !== '' ? $contact_url : home_url('/lien-he')); ?>">Liên hệ kỹ thuật</a>
              </div>
            </aside>
          <?php endif; ?>
        </div>

        <?php if ((int) $posts_query->max_num_pages > 1) : ?>
          <nav class="pagination-wrapper pagination-wrapper--posts" aria-label="Phân trang bài viết">
            <?php
            echo paginate_links([
                'base' => $blog_url . '%_%',
                'format' => 'page/%#%/',
                'current' => $current_page,
                'total' => (int) $posts_query->max_num_pages,
                'mid_size' => 1,
                'prev_text' => 'Trước',
                'next_text' => 'Sau',
            ]);
            ?>
          </nav>
        <?php endif; ?>
      <?php else : ?>
        <div class="empty-state">
          <h2>Blog đang được hoàn thiện</h2>
          <p>Trong lúc chờ bài viết mới, bạn có thể xem trước các tài nguyên mua hàng, FAQ và danh mục sản phẩm.</p>
          <div class="empty-state__actions">
            <?php if ($current_page > 1) : ?>
              <a class="btn btn-primary" href="<?php echo esc_url($blog_url); ?>">Về trang đầu blog</a>
            <?php endif; ?>
            <a class="btn <?php echo ($current_page > 1) ? 'btn-outline' : 'btn-primary'; ?>" href="<?php echo esc_url($shop_url); ?>">Xem kho sản phẩm</a>
            <a class="btn btn-outline" href="<?php echo esc_url($contact_url !== '' ? $contact_url : home_url('/lien-he')); ?>">Liên hệ tư vấn</a>
          </div>
          <div class="search-scope" aria-label="Gợi ý điều hướng">
            <?php foreach ($fallback_resources as $resource) : ?>
              <a class="chip" href="<?php echo esc_url($resource['url']); ?>"><?php echo esc_html($resource['label']); ?></a>
            <?php endforeach; ?>
          </div>
          <div class="trust-row" aria-label="Điểm hỗ trợ nổi bật">
            <span class="trust-item">Chốt mã sơn nhanh</span>
            <span class="trust-item">Định mức sát thực tế</span>
            <span class="trust-item">Giao vật tư theo tiến độ</span>
          </div>
        </div>
      <?php endif; ?>
    </section>

    <?php
    if (function_exists('my_theme_render_lead_capture_form')) {
        echo my_theme_render_lead_capture_form([
            'source' => 'blog-hub',
            'title' => 'Đọc xong vẫn chưa chốt được vật tư? Gửi nhu cầu để được điều hướng nhanh',
            'subtitle' => 'Nếu bài viết mới chỉ giúp bạn hiểu vấn đề nhưng chưa chốt được mã, hãy gửi diện tích, bề mặt và thương hiệu đang cân nhắc để đội kỹ thuật hướng bạn sang đúng sản phẩm hoặc landing page.',
            'button' => 'Gửi nhu cầu sau khi đọc',
        ]);
    }
    ?>
  </div>
</main>
<?php get_footer(); ?>
