<?php
/** Template Name: Liên hệ */
get_header();
$contact_business = function_exists('my_theme_get_business_profile') ? my_theme_get_business_profile() : [];
$contact_name = isset($contact_business['contact_name']) ? (string) $contact_business['contact_name'] : 'Trần Thị Ngọc Thúy';
$contact_phone_display = isset($contact_business['phone_display']) ? (string) $contact_business['phone_display'] : '0944 857 999';
$contact_phone_href = isset($contact_business['phone_href']) ? (string) $contact_business['phone_href'] : 'tel:0944857999';
$contact_email = isset($contact_business['email']) ? (string) $contact_business['email'] : 'lephat1898@gmail.com';
$contact_email_href = isset($contact_business['email_href']) ? (string) $contact_business['email_href'] : 'mailto:lephat1898@gmail.com';
$contact_zalo_url = isset($contact_business['zalo_url']) ? (string) $contact_business['zalo_url'] : 'https://zalo.me/0944857999';
$contact_address = isset($contact_business['address_full']) ? (string) $contact_business['address_full'] : '392 TL10, Bình Trị Đông, Bình Tân, TP.HCM';
$contact_maps_url = isset($contact_business['maps_url']) ? (string) $contact_business['maps_url'] : '';
$contact_hours = isset($contact_business['hours_display']) ? (string) $contact_business['hours_display'] : 'Thứ 2 - Thứ 7: 7:30 - 18:00';
$contact_hours_note = isset($contact_business['hours_note']) ? (string) $contact_business['hours_note'] : 'Ngoài giờ vẫn nhận yêu cầu qua Zalo và phản hồi sớm trong khung tiếp theo.';
$contact_service_areas = isset($contact_business['service_areas_display']) ? (string) $contact_business['service_areas_display'] : 'TP.HCM, Bình Dương, Đồng Nai';
$contact_shop_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
$contact_visible_ids = function_exists('my_theme_get_catalog_visible_product_ids')
    ? my_theme_get_catalog_visible_product_ids(false)
    : [];
$contact_visible_ids = array_values(array_filter(array_map('intval', (array) $contact_visible_ids), function ($id) {
    return $id > 0;
}));
$contact_brand_options = function_exists('my_theme_get_brand_filter_options')
    ? my_theme_get_brand_filter_options($contact_visible_ids)
    : [];
$contact_brand_options = is_array($contact_brand_options) ? $contact_brand_options : [];
$contact_brand_preview = array_slice($contact_brand_options, 0, 6, true);
$contact_catalog_count = count($contact_visible_ids);
$contact_category_count = function_exists('my_theme_count_visible_product_categories')
    ? (int) my_theme_count_visible_product_categories($contact_visible_ids)
    : 0;
?>
<main id="main-content">
  <div class="container">
    <article class="page-section single-article page-shell contact-page-shell">
      <ul class="breadcrumb">
        <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
        <li>Liên hệ</li>
      </ul>
      <h1 class="page-title">Liên hệ</h1>
      <p class="text-muted">Chọn đúng kênh liên hệ để chốt mã sơn, báo giá và thời gian giao nhanh hơn mà không phải đi qua nhiều bước.</p>

      <section class="landing-hero contact-store-hero">
        <div class="landing-hero__main">
          <p class="eyebrow eyebrow-muted">Liên hệ cửa hàng</p>
          <h2 class="section-title">Gọi hoặc nhắn Zalo khi cần chốt vật tư nhanh trong ngày</h2>
          <p class="landing-hero__lead">Nếu bạn đã có mã hoặc hãng cần lấy, gọi điện hay nhắn Zalo là nhanh nhất. Nếu nhu cầu còn nhiều ngữ cảnh như diện tích, bề mặt, hình ảnh hiện trạng hoặc tiến độ giao nhiều đợt, hãy dùng form báo giá phía dưới để đội kỹ thuật phản hồi chính xác hơn.</p>
          <div class="landing-hero__actions">
            <a class="btn btn-primary" href="<?php echo esc_url($contact_phone_href); ?>">Gọi <?php echo esc_html($contact_phone_display); ?></a>
            <a class="btn btn-accent" href="<?php echo esc_url($contact_zalo_url); ?>" target="_blank" rel="noopener">Mở Zalo kỹ thuật</a>
            <?php if ($contact_maps_url !== '') : ?>
              <a class="btn btn-outline" href="<?php echo esc_url($contact_maps_url); ?>" target="_blank" rel="noopener">Xem đường đi</a>
            <?php endif; ?>
          </div>
          <div class="landing-kpis">
            <div class="landing-kpi">
              <strong><?php echo esc_html($contact_phone_display); ?></strong>
              <span>Hotline tiếp nhận nhu cầu, chốt mã, báo giá và thời gian giao.</span>
            </div>
            <div class="landing-kpi">
              <strong><?php echo esc_html($contact_hours); ?></strong>
              <span>Khung giờ phản hồi ưu tiên để không phải chờ qua nhiều ca xử lý.</span>
            </div>
            <div class="landing-kpi">
              <strong><?php echo esc_html((string) max(0, $contact_catalog_count)); ?></strong>
              <span>Mã sản phẩm đang có để đối chiếu nhanh theo hãng, dòng hoặc mã.</span>
            </div>
            <div class="landing-kpi">
              <strong><?php echo esc_html($contact_service_areas); ?></strong>
              <span>Khu vực phục vụ chính cho đơn lẻ, thợ và khách công trình.</span>
            </div>
          </div>
        </div>
        <aside class="landing-hero__panel contact-store-hero__panel">
          <p class="eyebrow eyebrow-muted">Quy trình làm việc</p>
          <h3>Để được báo giá nhanh, chỉ cần gửi đúng 3 nhóm thông tin</h3>
          <ul class="landing-checklist contact-store-checklist">
            <li>Thông tin nhu cầu: diện tích, bề mặt thi công và thương hiệu hoặc mã đang quan tâm nếu đã có.</li>
            <li>Thông tin vận hành: địa chỉ giao, thời điểm cần nhận hàng và yêu cầu giao từng đợt nếu là công trình.</li>
            <li>Thông tin xác minh: ảnh hiện trạng, màu tham khảo hoặc ghi chú kỹ thuật để cửa hàng tư vấn đúng hệ vật tư hơn.</li>
          </ul>
          <div class="about-store-facts">
            <div class="about-store-fact">
              <strong>Địa chỉ</strong>
              <span><?php echo esc_html($contact_address); ?></span>
            </div>
            <div class="about-store-fact">
              <strong>Giờ làm việc</strong>
              <span><?php echo esc_html($contact_hours); ?></span>
            </div>
            <div class="about-store-fact">
              <strong>Email</strong>
              <span><?php echo esc_html($contact_email); ?></span>
            </div>
            <div class="about-store-fact">
              <strong>Danh mục</strong>
              <span><?php echo esc_html((string) max(0, $contact_category_count)); ?> nhóm sản phẩm đã được chuẩn hóa để tra cứu nhanh.</span>
            </div>
          </div>
          <?php if (!empty($contact_brand_preview)) : ?>
            <div class="about-store-brands" aria-label="Một số thương hiệu đang có">
              <?php foreach ($contact_brand_preview as $brand_meta) : ?>
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

      <div class="search-scope" aria-label="Kênh hỗ trợ nhanh">
        <a class="chip" href="<?php echo esc_url($contact_phone_href); ?>">Gọi ngay</a>
        <a class="chip" href="<?php echo esc_url($contact_zalo_url); ?>" target="_blank" rel="noopener">Zalo kỹ thuật</a>
        <a class="chip" href="<?php echo esc_url($contact_shop_url); ?>">Kho sản phẩm</a>
        <a class="chip" href="<?php echo esc_url(home_url('/huong-dan-mua-hang')); ?>">Hướng dẫn mua hàng</a>
      </div>
      <div class="info-grid contact-channel-grid">
        <div class="info-card contact-channel-card">
          <h3>Gọi điện trực tiếp</h3>
          <p>Phù hợp khi bạn đã có mã, cần chốt giá nhanh hoặc muốn xác nhận ngay thời gian giao hàng cho đơn đang gấp.</p>
          <div class="contact-channel-card__actions">
            <a class="btn btn-primary btn-sm" href="<?php echo esc_url($contact_phone_href); ?>">Gọi <?php echo esc_html($contact_phone_display); ?></a>
          </div>
        </div>
        <div class="info-card contact-channel-card">
          <h3>Zalo kỹ thuật</h3>
          <p>Phù hợp khi cần gửi ảnh bề mặt, bảng màu, hiện trạng công trình hoặc mô tả ngắn để kỹ thuật phản hồi nhanh hơn.</p>
          <div class="contact-channel-card__actions">
            <a class="btn btn-outline btn-sm" href="<?php echo esc_url($contact_zalo_url); ?>" target="_blank" rel="noopener">Nhắn Zalo</a>
          </div>
        </div>
        <div class="info-card contact-channel-card">
          <h3>Email và bản đồ</h3>
          <p>Dùng khi cần gửi thông tin dài, chứng từ hoặc mở bản đồ để ghé cửa hàng và làm việc trực tiếp.</p>
          <div class="contact-channel-card__actions">
            <a class="btn btn-outline btn-sm" href="<?php echo esc_url($contact_email_href); ?>">Gửi email</a>
            <?php if ($contact_maps_url !== '') : ?>
              <a class="btn btn-accent btn-sm" href="<?php echo esc_url($contact_maps_url); ?>" target="_blank" rel="noopener">Mở bản đồ</a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="contact-support-grid">
        <div class="content-block contact-support-card">
          <h3>Thông tin nên chuẩn bị trước khi liên hệ</h3>
          <ol class="list-numbered">
            <li>Diện tích hoặc khối lượng công việc cần sơn, chống thấm hoặc bột trét.</li>
            <li>Bề mặt thi công: tường mới, tường cũ, sàn, mái, kim loại hay khu vực ẩm ướt.</li>
            <li>Thương hiệu hoặc dòng sơn đang quan tâm nếu đã có mã tham khảo.</li>
            <li>Địa chỉ giao hàng và mốc thời gian cần nhận vật tư.</li>
          </ol>
        </div>
        <div class="content-block contact-support-card">
          <h3>Cách cửa hàng phản hồi để chốt nhanh hơn</h3>
          <ol class="list-numbered">
            <li>Xác nhận đúng nhóm vật tư theo bề mặt và phạm vi thi công.</li>
            <li>Đối chiếu mã, quy cách, số lượng và các lựa chọn gần nhất nếu mã hết hàng.</li>
            <li>Báo giá theo nhu cầu thực tế và kiểm tra phương án giao phù hợp với khu vực.</li>
            <li>Hướng dẫn bước tiếp theo nếu bạn muốn vào thẳng kho sản phẩm hoặc chốt qua điện thoại.</li>
          </ol>
        </div>
      </div>

      <div class="info-grid contact-support-summary">
        <div class="info-card">
          <h3>Người phụ trách</h3>
          <p><?php echo esc_html($contact_name); ?> là đầu mối tiếp nhận nhu cầu và chuyển đúng cho bộ phận kỹ thuật hoặc xử lý đơn hàng khi cần.</p>
        </div>
        <div class="info-card">
          <h3>Khung phản hồi</h3>
          <p><?php echo esc_html($contact_hours); ?>. <?php echo esc_html($contact_hours_note); ?></p>
        </div>
        <div class="info-card">
          <h3>Vào kho sản phẩm trước</h3>
          <p>Nếu bạn đã biết hãng hoặc mã, có thể mở thẳng kho hàng để xem trước rồi quay lại liên hệ khi cần chốt đơn hoặc xác nhận giao hàng.</p>
          <div class="contact-channel-card__actions">
            <a class="btn btn-outline btn-sm" href="<?php echo esc_url($contact_shop_url); ?>">Mở kho sản phẩm</a>
          </div>
        </div>
      </div>
      <?php
      if (function_exists('my_theme_render_lead_capture_form')) {
          echo my_theme_render_lead_capture_form([
              'source' => 'trang-lien-he',
              'title' => 'Để lại thông tin, chúng tôi gọi lại báo giá nhanh',
              'subtitle' => 'Điền thông tin để đội kỹ thuật liên hệ, tư vấn mã sơn, khối lượng và hướng mua phù hợp hơn.',
              'button' => 'Gửi yêu cầu liên hệ',
          ]);
      }
      ?>
      <div class="cta-inline contact-cta-inline">
        <div class="cta-inline__content">
          <div>
            <h3>Đã có nhu cầu rõ rồi thì đi tiếp theo 2 hướng này</h3>
            <p class="text-muted">Mở kho sản phẩm nếu bạn muốn tự tra cứu theo hãng hoặc liên hệ ngay để chốt mã, giá và lịch giao.</p>
          </div>
          <div class="cta-inline__actions">
            <a class="btn btn-accent" href="<?php echo esc_url(home_url('/huong-dan-mua-hang')); ?>">Xem cách đặt hàng</a>
            <a class="btn btn-outline" href="<?php echo esc_url($contact_shop_url); ?>">Xem kho sản phẩm</a>
            <a class="btn btn-primary" href="<?php echo esc_url($contact_phone_href); ?>">Gọi chốt nhanh</a>
          </div>
        </div>
      </div>
    </article>
  </div>
</main>
<?php get_footer();
