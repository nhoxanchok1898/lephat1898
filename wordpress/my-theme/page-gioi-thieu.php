<?php
/* Template Name: Giới thiệu đại lý */
get_header();
$about_business = function_exists('my_theme_get_business_profile') ? my_theme_get_business_profile() : [];
$about_phone_href = isset($about_business['phone_href']) ? (string) $about_business['phone_href'] : 'tel:0944857999';
$about_phone_display = isset($about_business['phone_display']) ? (string) $about_business['phone_display'] : '0944 857 999';
$about_zalo_url = isset($about_business['zalo_url']) ? (string) $about_business['zalo_url'] : 'https://zalo.me/0944857999';
$about_address = isset($about_business['address_full']) ? (string) $about_business['address_full'] : '392 TL10, Bình Trị Đông, Bình Tân, TP.HCM';
$about_hours = isset($about_business['hours_display']) ? (string) $about_business['hours_display'] : 'Thứ 2 - Thứ 7: 7:30 - 18:00';
$about_service_areas = isset($about_business['service_areas_display']) ? (string) $about_business['service_areas_display'] : 'TP.HCM, Bình Dương, Đồng Nai';
$about_contact_name = isset($about_business['contact_name']) ? (string) $about_business['contact_name'] : 'Đội ngũ Phát Tấn';
$about_maps_url = isset($about_business['maps_url']) ? (string) $about_business['maps_url'] : home_url('/');
$about_shop_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
$about_visible_ids = function_exists('my_theme_get_catalog_visible_product_ids')
    ? my_theme_get_catalog_visible_product_ids(false)
    : [];
$about_visible_ids = array_values(array_filter(array_map('intval', (array) $about_visible_ids), function ($id) {
    return $id > 0;
}));
$about_catalog_count = count($about_visible_ids);
$about_category_count = function_exists('my_theme_count_visible_product_categories')
    ? (int) my_theme_count_visible_product_categories($about_visible_ids)
    : 0;
$about_brand_options = function_exists('my_theme_get_brand_filter_options')
    ? my_theme_get_brand_filter_options($about_visible_ids)
    : [];
$about_brand_options = is_array($about_brand_options) ? $about_brand_options : [];
$about_brand_preview = array_slice($about_brand_options, 0, 6, true);
?>
<main id="main-content">
  <div class="container">
    <article class="page-section single-article page-shell">
      <ul class="breadcrumb">
        <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
        <li>Giới thiệu đại lý</li>
      </ul>

      <h1 class="page-title">Giới thiệu Đại lý Sơn Phát Tấn</h1>
      <p class="text-muted">Cửa hàng sơn chính hãng tại Bình Tân, phục vụ từ khách lẻ, thợ thi công đến công trình cần chốt vật tư nhanh và đúng hệ.</p>

      <div class="trust-row">
        <span class="trust-item">Đại lý chính hãng</span>
        <span class="trust-item">Hàng mới 100%</span>
        <span class="trust-item">Hỗ trợ kỹ thuật</span>
      </div>

      <section class="landing-hero about-store-hero">
        <div class="landing-hero__main">
          <p class="eyebrow eyebrow-muted">Cửa hàng tại Bình Tân</p>
          <h2 class="section-title">Đi nhanh từ nhu cầu thực tế sang đúng nhóm vật tư cần mua</h2>
          <p class="landing-hero__lead">Phát Tấn không chỉ bán sơn. Cửa hàng tập trung vào việc giúp khách xem đúng nhóm hàng, hỏi đúng kỹ thuật và chốt đơn nhanh theo bề mặt, diện tích và tiến độ thi công. Vì vậy khách lẻ, thợ và công trình đều có thể vào sản phẩm nhanh thay vì phải tự mò giữa quá nhiều mã.</p>
          <div class="landing-hero__actions">
            <a class="btn btn-primary" href="<?php echo esc_url($about_shop_url); ?>">Mở kho sản phẩm</a>
            <a class="btn btn-outline" href="<?php echo esc_url($about_maps_url); ?>" target="_blank" rel="noopener">Xem vị trí cửa hàng</a>
            <a class="btn btn-accent" href="<?php echo esc_url($about_zalo_url); ?>" target="_blank" rel="noopener">Zalo tư vấn nhanh</a>
          </div>
          <div class="landing-kpis">
            <div class="landing-kpi">
              <strong><?php echo esc_html((string) max(0, $about_catalog_count)); ?></strong>
              <span>Mã hàng đang hiển thị trong kho để khách xem và lọc nhanh.</span>
            </div>
            <div class="landing-kpi">
              <strong><?php echo esc_html((string) max(0, $about_category_count)); ?></strong>
              <span>Danh mục sản phẩm được sắp theo bề mặt và hạng mục thi công.</span>
            </div>
            <div class="landing-kpi">
              <strong><?php echo esc_html((string) count($about_brand_options)); ?></strong>
              <span>Thương hiệu phổ biến để khách chọn theo hãng trước khi đi sâu vào mã.</span>
            </div>
            <div class="landing-kpi">
              <strong><?php echo esc_html($about_phone_display); ?></strong>
              <span>Hotline để hỏi kỹ thuật, xin báo giá hoặc chốt đơn nhanh trong giờ làm việc.</span>
            </div>
          </div>
        </div>
        <aside class="landing-hero__panel about-store-hero__panel">
          <h3>Thông tin nhanh về cửa hàng</h3>
          <div class="about-store-facts">
            <div class="about-store-fact">
              <strong>Địa chỉ</strong>
              <span><?php echo esc_html($about_address); ?></span>
            </div>
            <div class="about-store-fact">
              <strong>Giờ làm việc</strong>
              <span><?php echo esc_html($about_hours); ?></span>
            </div>
            <div class="about-store-fact">
              <strong>Khu vực phục vụ</strong>
              <span><?php echo esc_html($about_service_areas); ?></span>
            </div>
            <div class="about-store-fact">
              <strong>Đầu mối liên hệ</strong>
              <span><?php echo esc_html($about_contact_name . ' - ' . $about_phone_display); ?></span>
            </div>
          </div>
          <?php if (!empty($about_brand_preview)) : ?>
            <div class="about-store-brands" aria-label="Một số thương hiệu đang có">
              <?php foreach ($about_brand_preview as $brand_meta) : ?>
                <?php
                $brand_label = isset($brand_meta['label']) ? trim((string) $brand_meta['label']) : '';
                if ($brand_label === '') {
                    continue;
                }
                ?>
                <span class="chip chip--soft"><?php echo esc_html($brand_label); ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </aside>
      </section>

      <div class="content-block">
        <h3>Đại lý Sơn Phát Tấn là cửa hàng như thế nào?</h3>
        <p><strong>Đại lý Sơn Phát Tấn</strong> là cửa hàng chuyên sơn nước, chống thấm, bột trét, sơn epoxy, sơn kim loại và vật tư hoàn thiện cho nhà ở, cửa hàng, tổ đội thi công và công trình dân dụng. Điểm mạnh của cửa hàng không chỉ nằm ở việc có hàng chính hãng, mà còn ở khả năng giúp khách đi nhanh từ nhu cầu thực tế sang đúng nhóm vật tư cần mua.</p>
        <p>Khi khách chưa chắc nên chọn hãng nào, dòng nào hay hệ nào phù hợp bề mặt, đội ngũ cửa hàng sẽ tư vấn theo hiện trạng thi công, diện tích, ngân sách và tiến độ thay vì chỉ gửi bảng giá chung. Vì vậy khách vào cửa hàng có thể xem sản phẩm nhanh, hỏi kỹ thuật nhanh và chốt đơn gọn hơn.</p>
      </div>
      <?php
      if (function_exists('my_theme_render_service_compass')) {
          my_theme_render_service_compass([
              'class' => 'service-compass--about',
              'eyebrow' => 'Từ trang giới thiệu',
              'title' => 'Nếu đã hiểu đại lý đang làm gì, bước tiếp theo nên là gì?',
              'subtitle' => 'Mở kho sản phẩm nếu bạn đã biết mã hoặc hãng. Vào giải pháp nếu đang chọn theo hạng mục. Hoặc gửi nhu cầu thực tế để đội kỹ thuật báo giá và định hướng luôn.',
          ]);
      }
      ?>

      <div class="info-grid">
        <div class="info-card">
          <h3>Cửa hàng phục vụ ai?</h3>
          <p>Phù hợp cho khách sửa nhà, chủ nhà cần mua lẻ, thợ thi công cần lấy hàng đều, và công trình muốn chốt đúng hệ sơn theo từng hạng mục.</p>
        </div>
        <div class="info-card">
          <h3>Dòng hàng chủ lực</h3>
          <p>Cửa hàng tập trung các nhóm sơn nội thất, ngoại thất, chống thấm, bột trét, sơn epoxy, sơn kim loại, keo và phụ gia từ các hãng phổ biến trên thị trường.</p>
        </div>
        <div class="info-card">
          <h3>Khu vực và giờ phục vụ</h3>
          <p><?php echo esc_html($about_service_areas); ?>. Thời gian làm việc: <?php echo esc_html($about_hours); ?>.</p>
        </div>
        <div class="info-card">
          <h3>Thông tin cửa hàng</h3>
          <p><?php echo esc_html($about_address); ?>. Hotline tư vấn: <?php echo esc_html($about_phone_display); ?>. Liên hệ trực tiếp với <?php echo esc_html($about_contact_name); ?> hoặc đội hỗ trợ kỹ thuật để chốt nhanh hơn.</p>
        </div>
      </div>

      <div class="content-block">
        <h3>Phát Tấn làm việc theo cách nào?</h3>
        <ol class="list-numbered">
          <li>Tiếp nhận nhu cầu theo bề mặt, diện tích, hãng đang cân nhắc hoặc tiến độ công trình.</li>
          <li>Gợi ý đúng nhóm vật tư, báo giá rõ ràng và chốt theo mã hoặc theo hệ thi công thực tế.</li>
          <li>Chuẩn bị hàng, giao theo tiến độ và tiếp tục hỗ trợ kỹ thuật nếu khách cần rà lại trong lúc thi công.</li>
        </ol>
      </div>

      <div class="content-block">
        <h3>Vì sao nhiều khách chọn mua ở Đại lý Sơn Phát Tấn?</h3>
        <p>Vì khách không chỉ cần một nơi “có bán sơn”, mà cần một nơi giúp chọn đúng hàng để đỡ mất thời gian đổi đi đổi lại. Cửa hàng đi theo hướng thực dụng: hỏi đúng thông tin, đưa đúng lựa chọn, báo giá nhanh, giao hàng gọn và hỗ trợ kỹ thuật khi cần. Với khách đã có mã thì vào sản phẩm là mua ngay. Với khách chưa chắc mã thì cửa hàng vẫn có thể điều hướng từ nhu cầu thực tế sang đúng nhóm vật tư phù hợp.</p>
      </div>

      <?php
      if (function_exists('my_theme_render_lead_capture_form')) {
          echo my_theme_render_lead_capture_form([
              'source' => 'about-page',
              'title' => 'Muốn đội ngũ Phát Tấn tư vấn trực tiếp cho nhu cầu của bạn?',
              'subtitle' => 'Gửi diện tích, bề mặt, thương hiệu đang quan tâm hoặc tiến độ công trình để đại lý phản hồi sát nhu cầu thay vì chỉ xem thông tin giới thiệu chung.',
              'button' => 'Gửi nhu cầu tư vấn',
          ]);
      }
      ?>

      <?php get_template_part('template-parts/home', 'cta-inline'); ?>

      <div class="cta">
        <div>
          <h3>Cần báo giá hoặc tư vấn hệ sơn?</h3>
          <p>Gửi diện tích và bề mặt, đại lý sẽ lên báo giá nhanh cho bạn.</p>
        </div>
        <div>
          <a class="btn btn-primary" href="<?php echo esc_url($about_phone_href); ?>">Gọi tư vấn</a>
          <a class="btn btn-outline" href="<?php echo esc_url($about_zalo_url); ?>" target="_blank" rel="noopener">Zalo kỹ thuật</a>
          <a class="btn btn-accent" href="<?php echo esc_url(home_url('/lien-he')); ?>">Gửi yêu cầu</a>
        </div>
      </div>
    </article>
  </div>
</main>
<?php get_footer(); ?>
