<?php
/** Template Name: Giải pháp tổng hợp */
get_header();

$solutions_catalog = function_exists('my_theme_get_visual_story_group_catalog') ? my_theme_get_visual_story_group_catalog() : [];
$solutions_phone_href = function_exists('my_theme_get_business_profile') ? (string) (my_theme_get_business_profile()['phone_href'] ?? 'tel:0944857999') : 'tel:0944857999';
$solutions_phone_display = function_exists('my_theme_get_business_profile') ? (string) (my_theme_get_business_profile()['phone_display'] ?? '0944 857 999') : '0944 857 999';
$solutions_zalo_url = function_exists('my_theme_get_business_profile') ? (string) (my_theme_get_business_profile()['zalo_url'] ?? 'https://zalo.me/0944857999') : 'https://zalo.me/0944857999';
$solutions_shop_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
$solutions_contact_url = home_url('/lien-he');
$solutions_guide_url = home_url('/huong-dan-mua-hang');
$solutions_cards = [];

foreach (['interior', 'exterior', 'waterproofing', 'epoxy', 'metal', 'grout'] as $solutions_group_key) {
    $solutions_group_key = sanitize_key((string) $solutions_group_key);
    if ($solutions_group_key === '' || !isset($solutions_catalog[$solutions_group_key]) || !function_exists('my_theme_get_visual_story_items_by_group')) {
        continue;
    }

    $solutions_items = my_theme_get_visual_story_items_by_group($solutions_group_key);
    if (empty($solutions_items)) {
        continue;
    }

    $solutions_meta = (array) $solutions_catalog[$solutions_group_key];
    $solutions_cards[] = [
        'label' => isset($solutions_meta['label']) ? (string) $solutions_meta['label'] : $solutions_group_key,
        'title' => isset($solutions_meta['title']) ? (string) $solutions_meta['title'] : $solutions_group_key,
        'description' => isset($solutions_meta['description']) ? (string) $solutions_meta['description'] : '',
        'url' => isset($solutions_meta['url']) ? (string) $solutions_meta['url'] : home_url('/'),
        'cta' => isset($solutions_meta['cta']) ? (string) $solutions_meta['cta'] : 'Xem thêm',
        'item' => (array) $solutions_items[0],
    ];
}
?>
<main id="main-content">
  <div class="container">
    <article class="page-section single-article page-shell landing-shell solutions-hub">
      <ul class="breadcrumb">
        <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
        <li>Giải pháp</li>
      </ul>

      <section class="landing-hero solutions-hub__hero">
        <div class="landing-hero__main">
          <p class="eyebrow eyebrow-muted">Lối đi nhanh theo nhu cầu</p>
          <h1 class="page-title">Giải pháp tổng hợp theo từng hạng mục</h1>
          <p class="landing-hero__lead">Trang này gom toàn bộ 6 nhóm giải pháp chính để khách đi nhanh vào đúng nhu cầu thực tế: nội thất, ngoại thất, chống thấm, epoxy, kim loại và keo ron.</p>

          <div class="search-scope" aria-label="Lối tắt nhóm giải pháp">
            <?php foreach ($solutions_cards as $solutions_card) : ?>
              <a class="chip" href="<?php echo esc_url((string) $solutions_card['url']); ?>"><?php echo esc_html((string) $solutions_card['label']); ?></a>
            <?php endforeach; ?>
          </div>

          <div class="trust-row" aria-label="Điểm nổi bật">
            <span class="trust-item">Đi theo đúng bề mặt và hạng mục</span>
            <span class="trust-item">Có ảnh minh họa và mã sản phẩm gợi ý</span>
            <span class="trust-item">Có form nhận báo giá nhanh ở từng nhóm</span>
          </div>

          <div class="landing-hero__actions">
            <a class="btn btn-primary" href="<?php echo esc_url($solutions_phone_href); ?>">Gọi <?php echo esc_html($solutions_phone_display); ?></a>
            <a class="btn btn-outline" href="<?php echo esc_url($solutions_zalo_url); ?>" target="_blank" rel="noopener">Zalo kỹ thuật</a>
            <a class="btn btn-accent" href="<?php echo esc_url($solutions_shop_url); ?>">Mở kho sản phẩm</a>
          </div>
        </div>

        <aside class="landing-hero__panel">
          <h3>Nên bắt đầu từ đâu?</h3>
          <ol class="list-numbered landing-checklist">
            <li>Nếu đang sơn tường trong nhà, đi vào nội thất.</li>
            <li>Nếu là mặt tiền, tường ngoài trời, đi vào ngoại thất.</li>
            <li>Nếu có dấu hiệu thấm, ưu tiên nhóm chống thấm trước khi chọn sơn phủ.</li>
            <li>Nếu là nền sàn, cửa sắt hoặc gạch ốp lát, chọn đúng epoxy, kim loại hoặc keo ron.</li>
          </ol>
        </aside>
      </section>

      <?php
      if (function_exists('my_theme_render_service_compass')) {
          my_theme_render_service_compass([
              'class' => 'service-compass--solutions',
              'eyebrow' => 'Điều hướng mua hàng',
              'title' => 'Ngoài việc đi theo giải pháp, bạn vẫn có thể quay sang kho sản phẩm hoặc gửi ảnh hiện trạng',
              'subtitle' => 'Hub này dành cho khách chọn theo hạng mục. Nếu đã có mã sơn cụ thể hoặc cần đội kỹ thuật điều hướng lại, có thể chuyển đường đi ngay tại đây.',
          ]);
      }
      ?>

      <section class="page-section home-solutions solutions-hub__section" aria-label="6 nhóm giải pháp chính">
        <div class="section-heading home-solutions__head">
          <div>
            <h2 class="section-title">6 nhóm giải pháp đang có trên web</h2>
            <p class="section-sub">Mỗi nhóm đã có ảnh minh họa, sản phẩm gợi ý, FAQ ngắn và form để gửi nhu cầu thực tế.</p>
          </div>
          <div class="home-solutions__chips">
            <a class="chip" href="<?php echo esc_url($solutions_guide_url); ?>">Hướng dẫn mua hàng</a>
            <a class="chip" href="<?php echo esc_url($solutions_contact_url); ?>">Gửi yêu cầu báo giá</a>
          </div>
        </div>

        <div class="home-solutions__grid">
          <?php foreach ($solutions_cards as $solutions_card) : ?>
            <?php
            $solutions_item = (array) $solutions_card['item'];
            $solutions_attachment_id = isset($solutions_item['attachment_id']) ? (int) $solutions_item['attachment_id'] : 0;
            $solutions_caption = isset($solutions_item['caption']) ? trim((string) $solutions_item['caption']) : '';
            $solutions_alt = $solutions_caption !== '' ? $solutions_caption : (string) $solutions_card['title'];
            ?>
            <article class="home-solution-card">
              <a class="home-solution-card__thumb" href="<?php echo esc_url((string) $solutions_card['url']); ?>">
                <?php echo wp_get_attachment_image($solutions_attachment_id, 'large', false, ['loading' => 'lazy', 'alt' => $solutions_alt]); ?>
              </a>
              <div class="home-solution-card__body">
                <div class="home-solution-card__eyebrow"><?php echo esc_html((string) $solutions_card['label']); ?></div>
                <h3 class="home-solution-card__title">
                  <a href="<?php echo esc_url((string) $solutions_card['url']); ?>"><?php echo esc_html((string) $solutions_card['title']); ?></a>
                </h3>
                <p class="home-solution-card__description"><?php echo esc_html((string) $solutions_card['description']); ?></p>
                <?php if ($solutions_caption !== '') : ?>
                  <p class="home-solution-card__caption"><?php echo esc_html($solutions_caption); ?></p>
                <?php endif; ?>
              </div>
              <div class="home-solution-card__actions">
                <a class="btn btn-outline w-100" href="<?php echo esc_url((string) $solutions_card['url']); ?>"><?php echo esc_html((string) $solutions_card['cta']); ?></a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>

      <?php
      if (function_exists('my_theme_render_article_recommendations')) {
          my_theme_render_article_recommendations(
              [
                  'cach-chon-son-noi-that-de-lau-chui-cho-nha-o',
                  'cach-chon-son-ngoai-that-ben-mau-cho-mat-tien',
                  'chong-tham-san-thuong-nen-dung-he-nao',
                  'cach-chon-son-epoxy-cho-san-nha-xuong-nho',
                  'cach-chon-son-chong-ri-cho-cua-sat-va-lan-can',
                  'cach-chon-keo-cha-ron-cho-nha-tam-va-bep',
              ],
              [
                  'title' => '6 bài nên đọc trước khi chốt vật tư',
                  'subtitle' => 'Đây là các bài tư vấn nền tảng tương ứng trực tiếp với 6 nhóm giải pháp trên, giúp khách tự đối chiếu nhu cầu trước khi gửi yêu cầu báo giá.',
              ]
          );
      }
      ?>

      <div class="info-grid landing-faq-grid">
        <div class="info-card">
          <h3>Chưa biết nên chọn nhóm nào?</h3>
          <p>Nếu chưa chắc là sơn, chống thấm hay vật tư gạch, chỉ cần gửi ảnh bề mặt và mô tả hiện trạng. Đội kỹ thuật sẽ điều hướng lại đúng nhóm.</p>
        </div>
        <div class="info-card">
          <h3>Cần gửi gì để được gợi ý nhanh?</h3>
          <p>Ảnh bề mặt, diện tích ước tính, vị trí thi công và nhu cầu chính là 4 thông tin đủ để khoanh nhanh giải pháp nên đi tiếp.</p>
        </div>
        <div class="info-card">
          <h3>Khi nào nên gọi trực tiếp?</h3>
          <p>Nếu đơn cần gấp, công trình đang thi công hoặc bạn cần chốt ngay hệ vật tư trong ngày thì gọi trực tiếp sẽ nhanh hơn gửi form.</p>
        </div>
      </div>

      <?php
      echo do_shortcode(
          '[lead_capture_form source="landing-giai-phap" title="Chưa chắc nên đi theo giải pháp nào? Gửi nhu cầu để được điều hướng nhanh" subtitle="Điền bề mặt, diện tích và mô tả hiện trạng. Đội kỹ thuật sẽ gọi lại và hướng bạn vào đúng nhóm giải pháp phù hợp." button="Nhận tư vấn giải pháp"]'
      );
      ?>

      <div class="page-section cta-inline cta-inline--essentials">
        <div class="cta-inline__content">
          <div class="cta-inline__lead">
            <p class="eyebrow eyebrow-muted">Quy trình chốt nhanh</p>
            <h3>Chọn đúng nhóm giải pháp, gửi ảnh hoặc mô tả nhu cầu, rồi chốt vật tư gọn hơn ngay từ đầu</h3>
            <p class="text-muted">Nếu chưa chắc nên đi theo nhóm nào, chỉ cần mô tả bề mặt, hiện trạng và diện tích. Đội kỹ thuật sẽ điều hướng lại đúng landing page và nhóm sản phẩm phù hợp.</p>
            <div class="cta-inline__steps" aria-label="Các bước chốt vật tư theo giải pháp">
              <span class="cta-inline__step">1. Chọn đúng nhóm bề mặt</span>
              <span class="cta-inline__step">2. Gửi ảnh hoặc nhu cầu</span>
              <span class="cta-inline__step">3. Nhận gợi ý vật tư</span>
            </div>
          </div>
          <div class="cta-inline__actions">
            <a class="btn btn-primary" href="<?php echo esc_url($solutions_phone_href); ?>">Gọi <?php echo esc_html($solutions_phone_display); ?></a>
            <a class="btn btn-outline" href="<?php echo esc_url($solutions_contact_url); ?>">Gửi yêu cầu báo giá</a>
          </div>
        </div>
      </div>
    </article>
  </div>
</main>
<?php get_footer();
