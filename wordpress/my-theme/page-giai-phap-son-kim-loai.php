<?php
/** Template Name: Giải pháp sơn kim loại */
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
    'jotun-gardex-metal-primer-0-8l',
    'jotun-gardex-premium-gloss-0-8l',
    'jotun-gardex-metal-primer-2-5l',
    'jotun-gardex-premium-gloss-2-5l',
]) : [];
?>
<main id="main-content">
  <div class="container">
    <article class="page-section single-article page-shell landing-shell">
      <ul class="breadcrumb">
        <li><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a></li>
        <li>Giải pháp sơn kim loại</li>
      </ul>
      <section class="landing-hero">
        <div class="landing-hero__main">
          <p class="eyebrow eyebrow-muted">Giải pháp cho cửa sắt và lan can</p>
          <h1 class="page-title">Giải pháp sơn kim loại</h1>
          <p class="landing-hero__lead">Trang này gom nhanh các tình huống sơn kim loại và chống rỉ cho cửa sắt, lan can, cổng và khung thép nhỏ để khách hàng chọn đúng lớp xử lý bề mặt và sơn phủ.</p>
          <div class="search-scope" aria-label="Lối tắt nội dung sơn kim loại">
            <a class="chip" href="#kim-loai-theo-bematsat">Theo tình trạng rỉ</a>
            <a class="chip" href="#kim-loai-theo-he-son">Theo hệ sơn</a>
            <a class="chip" href="#goi-y-son-kim-loai">Sản phẩm gợi ý</a>
            <a class="chip" href="<?php echo esc_url($landing_blog_url . 'cach-chon-son-chong-ri-cho-cua-sat-va-lan-can/'); ?>">Bài tư vấn</a>
            <a class="chip" href="<?php echo esc_url($landing_contact_url); ?>">Gửi ảnh hiện trạng</a>
          </div>
          <div class="trust-row" aria-label="Điểm nổi bật sơn kim loại">
            <span class="trust-item">Chốt theo mức rỉ và lớp sơn cũ</span>
            <span class="trust-item">Ưu tiên primer chống rỉ trước lớp phủ</span>
            <span class="trust-item">Phù hợp cửa sắt, cổng, lan can, mái sắt</span>
          </div>
          <div class="landing-hero__actions">
            <a class="btn btn-primary" href="<?php echo esc_url($landing_phone_href); ?>">Gọi báo giá</a>
            <a class="btn btn-outline" href="<?php echo esc_url($landing_zalo_url); ?>" target="_blank" rel="noopener">Zalo kỹ thuật</a>
            <a class="btn btn-accent" href="<?php echo esc_url($landing_shop_url); ?>">Mở kho sản phẩm</a>
          </div>
        </div>
        <aside class="landing-hero__panel">
          <h3>Thông tin nên gửi trước khi hỏi sơn kim loại</h3>
          <ol class="list-numbered landing-checklist">
            <li>Ảnh hiện trạng rỉ, bong sơn cũ và khu vực tiếp xúc nắng mưa.</li>
            <li>Loại hạng mục: cổng, cửa, lan can, khung thép, mái sắt.</li>
            <li>Nhu cầu chính: chống rỉ, làm mới màu, dặm sửa hay sơn lại toàn bộ.</li>
            <li>Diện tích ước tính và thời gian cần giao vật tư.</li>
          </ol>
          <div class="landing-kpis" aria-label="Tóm tắt hỗ trợ sơn kim loại">
            <div class="landing-kpi">
              <strong><?php echo esc_html($landing_store_hours); ?></strong>
              <span>Khung giờ phản hồi nhanh cho đơn cửa sắt và hạng mục dân dụng.</span>
            </div>
            <div class="landing-kpi">
              <strong><?php echo esc_html($landing_store_areas); ?></strong>
              <span>Khu vực ưu tiên hỗ trợ giao hàng và chốt vật tư.</span>
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
          <h3>Cổng và cửa sắt dân dụng</h3>
          <p>Ưu tiên xử lý sạch lớp rỉ và primer chống rỉ trước khi lên màu hoàn thiện để bề mặt đỡ xuống cấp lại nhanh.</p>
        </div>
        <div class="info-card">
          <h3>Lan can và hạng mục ngoài trời</h3>
          <p>Cần nhìn thêm mức nắng mưa và khu vực hay đọng nước để quyết định hệ sơn phù hợp hơn cho ngoài trời.</p>
        </div>
        <div class="info-card">
          <h3>Khung thép hoặc mái sắt</h3>
          <p>Nên báo rõ mức rỉ, diện tích và tình trạng lớp cũ để đội kỹ thuật khoanh nhanh bước xử lý bề mặt trước khi báo giá.</p>
        </div>
      </div>

      <div class="content-block" id="kim-loai-theo-bematsat">
        <h3>Chọn theo mức rỉ và hiện trạng bề mặt</h3>
        <p>Nếu bề mặt chỉ oxy hóa nhẹ, có thể đi theo hướng xử lý sạch rồi lên primer + phủ màu. Nếu rỉ đã ăn sâu hoặc lớp cũ bong nhiều, nên báo kỹ hiện trạng để đội kỹ thuật khoanh lại bước xử lý trước khi chọn sơn.</p>
      </div>

      <div class="content-block" id="kim-loai-theo-he-son">
        <h3>Chọn theo hệ sơn chứ không chỉ theo màu</h3>
        <p>Cùng là cửa sắt ngoài trời nhưng hạng mục mới, hạng mục cũ đã bong sơn nhiều lần hoặc hạng mục gần biển, gần mưa tạt sẽ khác nhau. Nên chốt theo tình trạng thật thay vì chỉ chọn màu phủ.</p>
      </div>

      <?php
      if (function_exists('my_theme_render_visual_story_gallery')) {
          my_theme_render_visual_story_gallery(
              'metal',
              [
                  'title' => 'Hình minh họa cửa sắt và lan can',
                  'subtitle' => 'Ảnh minh họa cho các hạng mục kim loại ngoài trời để khách dễ đối chiếu với hiện trạng thực tế.',
                  'class' => 'landing-visual-story',
              ]
          );
      }
      ?>

      <section id="goi-y-son-kim-loai" class="landing-section">
        <div class="section-heading landing-section-head">
          <div>
            <h2 class="section-title">4 mã sơn kim loại nên xem trước</h2>
            <p class="section-sub">Các mã dưới đây phù hợp để khách xem nhanh theo hướng chống rỉ, primer và lớp phủ hoàn thiện.</p>
          </div>
          <a class="btn btn-outline btn-sm" href="<?php echo esc_url($landing_shop_url); ?>">Mở kho sản phẩm</a>
        </div>
        <div class="landing-product-grid">
          <?php
          if (function_exists('my_theme_render_landing_product_cards')) {
              my_theme_render_landing_product_cards($landing_featured_products, [
                  'fallback_eyebrow' => 'Sơn kim loại',
                  'show_category' => false,
                  'show_line' => false,
              ]);
          }
          ?>
        </div>
      </section>

      <div class="info-grid landing-faq-grid">
        <div class="info-card">
          <h3>Bề mặt rỉ nhẹ có cần primer không?</h3>
          <p>Có. Dù chỉ là rỉ nhẹ, lớp primer chống rỉ vẫn là phần quan trọng để hệ sơn ổn định lâu hơn ngoài trời.</p>
        </div>
        <div class="info-card">
          <h3>Cửa sắt cũ có thể sơn đè ngay không?</h3>
          <p>Không nên mặc định như vậy. Cần xem lớp cũ còn bám tốt hay đã bong, phấn và có rỉ ăn sâu trước khi phủ mới.</p>
        </div>
        <div class="info-card">
          <h3>Nên gửi gì để chốt hệ sơn nhanh?</h3>
          <p>Ảnh hiện trạng, loại hạng mục và mức rỉ là đủ để đội kỹ thuật khoanh lại nên xử lý nền đến mức nào trước khi báo giá.</p>
        </div>
      </div>

      <?php
      if (function_exists('my_theme_render_group_knowledge_sections')) {
          my_theme_render_group_knowledge_sections('metal');
      }

      if (function_exists('my_theme_render_lead_capture_form')) {
          echo my_theme_render_lead_capture_form([
              'source' => 'landing-son-kim-loai',
              'title' => 'Gửi ảnh hạng mục kim loại để nhận gợi ý hệ sơn',
              'subtitle' => 'Điền loại hạng mục, tình trạng rỉ và nhu cầu sử dụng để đội kỹ thuật gọi lại nhanh hơn.',
              'button' => 'Nhận tư vấn sơn kim loại',
          ]);
      }
      ?>

      <?php
      if (function_exists('my_theme_render_solution_pathways')) {
          my_theme_render_solution_pathways('metal');
      }

      if (function_exists('my_theme_render_service_compass')) {
          my_theme_render_service_compass([
              'class' => 'service-compass--solutions',
              'eyebrow' => 'Nếu vẫn còn đang so lớp primer và phủ',
              'title' => 'Từ giải pháp sơn kim loại, bạn có thể đi tiếp theo 3 đường này',
              'subtitle' => 'Vào kho sản phẩm nếu bạn đã có dòng sơn cụ thể. Xem các giải pháp lân cận nếu hạng mục giao nhau với chống thấm hoặc ngoại thất. Hoặc gửi ảnh rỉ và bề mặt để đội kỹ thuật chốt nhanh hơn.',
          ]);
      }
      ?>

      <div class="page-section cta-inline cta-inline--essentials">
        <div class="cta-inline__content">
          <div class="cta-inline__lead">
            <p class="eyebrow eyebrow-muted">Quy trình chốt nhanh</p>
            <h3>Gửi ảnh hiện trạng rỉ, chốt hệ primer và phủ, rồi nhận lịch giao vật tư</h3>
            <p class="text-muted">Khi đã có ảnh bề mặt và loại hạng mục, đội kỹ thuật có thể khoanh nhanh nên xử lý đến đâu trước khi lên lớp phủ hoàn thiện.</p>
            <div class="cta-inline__steps" aria-label="Các bước chốt vật tư kim loại">
              <span class="cta-inline__step">1. Gửi ảnh và hạng mục</span>
              <span class="cta-inline__step">2. Chốt primer + phủ</span>
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
          <h3>Cần chốt nhanh hệ sơn kim loại cho cửa sắt, cổng hoặc lan can?</h3>
          <p>Gửi ảnh hiện trạng rỉ và khu vực thi công. Đội kỹ thuật sẽ gợi ý nhóm primer, lớp phủ và quy cách nên xem trước để tránh chốt sai hệ.</p>
        </div>
        <div>
          <a class="btn btn-primary" href="<?php echo esc_url($landing_contact_url); ?>">Gửi ảnh hiện trạng</a>
          <a class="btn btn-outline" href="<?php echo esc_url($landing_blog_url . 'cach-chon-son-chong-ri-cho-cua-sat-va-lan-can/'); ?>">Xem bài tư vấn</a>
          <a class="btn btn-accent" href="<?php echo esc_url($landing_shop_url); ?>">Xem sản phẩm</a>
        </div>
      </div>
    </article>
  </div>
</main>
<?php get_footer();
