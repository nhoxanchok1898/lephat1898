<?php
/** Template Name: Giải pháp keo và ron gạch */
get_header();

$landing_business = function_exists('my_theme_get_business_profile') ? my_theme_get_business_profile() : [];
$landing_phone_display = isset($landing_business['phone_display']) ? (string) $landing_business['phone_display'] : '0944 857 999';
$landing_phone_href = isset($landing_business['phone_href']) ? (string) $landing_business['phone_href'] : 'tel:0944857999';
$landing_zalo_url = isset($landing_business['zalo_url']) ? (string) $landing_business['zalo_url'] : 'https://zalo.me/0944857999';
$landing_shop_url = function_exists('my_theme_get_shop_url') ? my_theme_get_shop_url() : home_url('/shop');
$landing_contact_url = home_url('/lien-he');
$landing_guide_url = home_url('/huong-dan-mua-hang');
$landing_blog_url = trailingslashit(home_url('/blog'));
$landing_snapshot = function_exists('my_theme_get_store_snapshot') ? my_theme_get_store_snapshot() : [];
$landing_store_hours = isset($landing_snapshot['hours_display']) ? (string) $landing_snapshot['hours_display'] : 'Thứ 2 - Thứ 7: 7:30 - 18:00';
$landing_store_areas = isset($landing_snapshot['service_areas_display']) ? (string) $landing_snapshot['service_areas_display'] : 'TP.HCM, Bình Dương, Đồng Nai';
$landing_store_address = isset($landing_snapshot['address_full']) ? (string) $landing_snapshot['address_full'] : '392 TL10, Bình Trị Đông, Bình Tân, TP.HCM';
$landing_brand_preview = isset($landing_snapshot['brand_preview']) && is_array($landing_snapshot['brand_preview'])
    ? $landing_snapshot['brand_preview']
    : [];

$landing_featured_products = function_exists('my_theme_get_products_by_slugs') ? my_theme_get_products_by_slugs([
    'keo-cha-ron-webercolor-classic',
    'webercolor-no-stain',
    'keo-dan-gach-webertai-fix-40kg',
    'weberseal-ws500',
]) : [];
?>
<main id="main-content">
  <div class="container">
    <article class="page-section single-article page-shell landing-shell">
      <ul class="breadcrumb">
        <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
        <li>Giải pháp keo và ron gạch</li>
      </ul>
      <section class="landing-hero">
        <div class="landing-hero__main">
          <p class="eyebrow eyebrow-muted">Giải pháp cho gạch ốp lát</p>
          <h1 class="page-title">Giải pháp keo và ron gạch</h1>
          <p class="landing-hero__lead">Trang này gom nhanh các tình huống dán gạch, chà ron và trám khe cho nhà tắm, bếp, khu ẩm và gạch ốp lát để khách chọn đúng nhóm vật tư hơn.</p>
          <div class="search-scope" aria-label="Lối tắt nội dung keo và ron gạch">
            <a class="chip" href="#keo-ron-theo-khu-vuc">Theo khu vực</a>
            <a class="chip" href="#keo-ron-theo-vat-tu">Theo loại vật tư</a>
            <a class="chip" href="#goi-y-keo-ron">Sản phẩm gợi ý</a>
            <a class="chip" href="<?php echo esc_url($landing_blog_url . 'cach-chon-keo-cha-ron-cho-nha-tam-va-bep/'); ?>">Bài tư vấn</a>
            <a class="chip" href="<?php echo esc_url($landing_contact_url); ?>">Gửi nhu cầu</a>
          </div>
          <div class="trust-row" aria-label="Điểm nổi bật keo và ron gạch">
            <span class="trust-item">Phân biệt rõ keo dán, chà ron và trám khe</span>
            <span class="trust-item">Đi theo khu vực nhà tắm, bếp, ban công</span>
            <span class="trust-item">Dễ chốt theo loại gạch và diện tích</span>
          </div>
          <div class="landing-hero__actions">
            <a class="btn btn-primary" href="<?php echo esc_url($landing_phone_href); ?>">Gọi báo giá</a>
            <a class="btn btn-outline" href="<?php echo esc_url($landing_zalo_url); ?>" target="_blank" rel="noopener">Zalo kỹ thuật</a>
            <a class="btn btn-accent" href="<?php echo esc_url($landing_shop_url); ?>">Mở kho sản phẩm</a>
          </div>
        </div>
        <aside class="landing-hero__panel">
          <h3>Thông tin nên gửi trước khi hỏi keo và ron</h3>
          <ol class="list-numbered landing-checklist">
            <li>Loại gạch, kích thước gạch và khu vực thi công.</li>
            <li>Đang cần dán gạch, chà ron hay xử lý khe tiếp giáp.</li>
            <li>Khu vực có ẩm thường xuyên, ngoài trời hay trong nhà.</li>
            <li>Diện tích hoặc số lượng bao cần lấy.</li>
          </ol>
          <div class="landing-kpis" aria-label="Tóm tắt hỗ trợ keo và ron">
            <div class="landing-kpi">
              <strong><?php echo esc_html($landing_store_hours); ?></strong>
              <span>Khung giờ phản hồi nhanh cho đơn dân dụng và thợ thi công.</span>
            </div>
            <div class="landing-kpi">
              <strong><?php echo esc_html($landing_store_areas); ?></strong>
              <span>Khu vực ưu tiên hỗ trợ giao hàng và tư vấn kỹ thuật.</span>
            </div>
          </div>
          <div class="about-store-facts">
            <div class="about-store-fact">
              <strong>Hotline kỹ thuật</strong>
              <span><?php echo esc_html($landing_phone_display); ?></span>
            </div>
            <div class="about-store-fact">
              <strong>Địa chỉ cửa hàng</strong>
              <span><?php echo esc_html($landing_store_address); ?></span>
            </div>
          </div>
          <?php if (!empty($landing_brand_preview)) : ?>
            <div class="about-store-brands" aria-label="Một số thương hiệu đang có">
              <?php foreach ($landing_brand_preview as $landing_brand_meta) : ?>
                <?php
                $landing_brand_label = isset($landing_brand_meta['label']) ? trim((string) $landing_brand_meta['label']) : '';
                if ($landing_brand_label === '') {
                    continue;
                }
                ?>
                <span class="chip chip--soft"><?php echo esc_html($landing_brand_label); ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </aside>
      </section>

      <div class="info-grid">
        <div class="info-card">
          <h3>Nhà tắm và khu vệ sinh</h3>
          <p>Ưu tiên vật tư phù hợp khu ẩm kéo dài, ron sạch hơn và khe tiếp giáp cần trám kín đúng chỗ.</p>
        </div>
        <div class="info-card">
          <h3>Bếp và khu sinh hoạt</h3>
          <p>Cần chú ý dầu mỡ, vệ sinh và độ bám bẩn của đường ron để chọn vật tư hợp lý hơn ngay từ đầu.</p>
        </div>
        <div class="info-card">
          <h3>Ban công và khu ngoài trời</h3>
          <p>Nên báo rõ khu vực chịu nắng mưa và loại gạch để cửa hàng chốt nhanh nhóm keo dán, ron hoặc trám khe phù hợp.</p>
        </div>
      </div>

      <div class="content-block" id="keo-ron-theo-khu-vuc">
        <h3>Chọn theo khu vực thi công</h3>
        <p><strong>Nhà tắm và khu vệ sinh:</strong> thường ưu tiên nhóm chà ron sạch hơn, ít bám bẩn và phù hợp khu vực ẩm kéo dài.</p>
        <p><strong>Bếp và ban công:</strong> cần xem thêm yếu tố dầu mỡ, bụi bẩn và mức co giãn ở các khe tiếp giáp để chốt vật tư hợp lý hơn.</p>
      </div>

      <div class="content-block" id="keo-ron-theo-vat-tu">
        <h3>Chọn đúng nhóm vật tư thay vì gọi chung là “keo ron”</h3>
        <p>Keo dán gạch, bột chà ron và vật tư trám khe là 3 nhóm khác nhau. Việc gọi chung dễ dẫn tới lấy sai loại hoặc thiếu vật tư cần thiết cho công trình.</p>
      </div>

      <?php
      if (function_exists('my_theme_render_visual_story_gallery')) {
          my_theme_render_visual_story_gallery(
              'grout',
              [
                  'title' => 'Hình minh họa nhà tắm, bếp và ron gạch',
                  'subtitle' => 'Ảnh minh họa tham khảo cho các khu vực gạch ốp lát, nhà tắm và bếp để khách dễ hình dung nhóm vật tư đang cần.',
                  'class' => 'landing-visual-story',
              ]
          );
      }
      ?>

      <section id="goi-y-keo-ron" class="landing-section">
        <div class="section-heading landing-section-head">
          <div>
            <h2 class="section-title">4 mã keo và ron nên xem trước</h2>
            <p class="section-sub">Các mã dưới đây đại diện cho nhóm dán gạch, chà ron và trám khe phổ biến cho nhà tắm, bếp và khu ẩm.</p>
          </div>
          <a class="btn btn-outline btn-sm" href="<?php echo esc_url($landing_shop_url); ?>">Xem toàn bộ kho</a>
        </div>
        <div class="landing-product-grid">
          <?php
          if (function_exists('my_theme_render_landing_product_cards')) {
              my_theme_render_landing_product_cards($landing_featured_products, [
                  'fallback_eyebrow' => 'Keo và ron gạch',
                  'show_category' => false,
                  'show_line' => false,
              ]);
          }
          ?>
        </div>
      </section>

      <div class="info-grid landing-faq-grid">
        <div class="info-card">
          <h3>Keo dán gạch và chà ron có thay thế nhau được không?</h3>
          <p>Không. Keo dán gạch dùng để bám dính viên gạch, còn chà ron xử lý khe ron sau khi ốp lát hoàn tất.</p>
        </div>
        <div class="info-card">
          <h3>Khu ẩm nên ưu tiên loại ron nào?</h3>
          <p>Nhà tắm, khu vệ sinh và bếp nên ưu tiên nhóm ron sạch hơn, ít bám bẩn hơn và phù hợp môi trường ẩm kéo dài.</p>
        </div>
        <div class="info-card">
          <h3>Cần gửi gì để chốt vật tư đúng?</h3>
          <p>Loại gạch, khu vực thi công, kích thước gạch và diện tích là 4 thông tin quan trọng nhất để đội kỹ thuật chốt đúng nhóm vật tư.</p>
        </div>
      </div>

      <?php
      if (function_exists('my_theme_render_group_knowledge_sections')) {
          my_theme_render_group_knowledge_sections('grout');
      }

      if (function_exists('my_theme_render_lead_capture_form')) {
          echo my_theme_render_lead_capture_form([
              'source' => 'landing-keo-ron',
              'title' => 'Gửi nhu cầu để nhận gợi ý keo, ron và phụ gia phù hợp',
              'subtitle' => 'Điền loại gạch, khu vực thi công và diện tích để đội kỹ thuật gọi lại nhanh hơn.',
              'button' => 'Nhận tư vấn keo và ron',
          ]);
      }
      ?>

      <?php
      if (function_exists('my_theme_render_solution_pathways')) {
          my_theme_render_solution_pathways('grout');
      }

      if (function_exists('my_theme_render_service_compass')) {
          my_theme_render_service_compass([
              'class' => 'service-compass--solutions',
              'eyebrow' => 'Nếu vẫn chưa chốt loại vật tư ốp lát',
              'title' => 'Từ giải pháp keo và ron, bạn có thể đi tiếp theo 3 hướng này',
              'subtitle' => 'Mở kho sản phẩm nếu bạn đã có loại vật tư cụ thể. Xem các giải pháp lân cận nếu nhu cầu còn giao với chống thấm hoặc nội ngoại thất. Hoặc gửi loại gạch và khu vực thi công để đội kỹ thuật điều hướng lại.',
          ]);
      }
      ?>

      <div class="page-section cta-inline cta-inline--essentials">
        <div class="cta-inline__content">
          <div class="cta-inline__lead">
            <p class="eyebrow eyebrow-muted">Quy trình chốt nhanh</p>
            <h3>Gửi loại gạch và khu vực thi công, chốt nhóm vật tư, rồi lên đơn theo diện tích</h3>
            <p class="text-muted">Chỉ cần loại gạch, khu vực ốp lát và m2 là đủ để đội kỹ thuật khoanh nhanh keo dán, chà ron hay vật tư trám khe nên xem trước.</p>
            <div class="cta-inline__steps" aria-label="Các bước chốt vật tư keo và ron">
              <span class="cta-inline__step">1. Gửi loại gạch và m2</span>
              <span class="cta-inline__step">2. Chốt keo, ron hoặc trám khe</span>
              <span class="cta-inline__step">3. Nhận lịch giao</span>
            </div>
          </div>
          <div class="cta-inline__actions">
            <a class="btn btn-primary" href="<?php echo esc_url($landing_phone_href); ?>">Gọi <?php echo esc_html($landing_phone_display); ?></a>
            <a class="btn btn-outline" href="<?php echo esc_url($landing_guide_url); ?>">Xem hướng dẫn mua hàng</a>
          </div>
        </div>
      </div>

      <div class="cta">
        <div>
          <h3>Cần chốt nhanh keo dán gạch, chà ron hoặc vật tư trám khe?</h3>
          <p>Gửi loại gạch, khu vực thi công và diện tích. Đội kỹ thuật sẽ gợi ý đúng nhóm vật tư để tránh thiếu hoặc lấy nhầm loại.</p>
        </div>
        <div>
          <a class="btn btn-primary" href="<?php echo esc_url($landing_contact_url); ?>">Gửi nhu cầu thi công</a>
          <a class="btn btn-outline" href="<?php echo esc_url($landing_blog_url . 'cach-chon-keo-cha-ron-cho-nha-tam-va-bep/'); ?>">Xem bài tư vấn</a>
          <a class="btn btn-accent" href="<?php echo esc_url($landing_shop_url); ?>">Xem sản phẩm</a>
        </div>
      </div>
    </article>
  </div>
</main>
<?php get_footer();
