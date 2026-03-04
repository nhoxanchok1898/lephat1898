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

$landing_get_products = static function (array $slugs) {
    $products = [];
    foreach ($slugs as $slug) {
        $slug = sanitize_title((string) $slug);
        if ($slug === '') {
            continue;
        }
        $post = get_page_by_path($slug, OBJECT, 'product');
        if (!($post instanceof WP_Post)) {
            continue;
        }
        $product = wc_get_product((int) $post->ID);
        if ($product instanceof WC_Product) {
            $products[] = $product;
        }
    }
    return $products;
};

$landing_capture = static function (callable $callback) {
    ob_start();
    $callback();
    return trim((string) ob_get_clean());
};

$landing_render_product_cards = static function (array $products) use ($landing_capture) {
    foreach ($products as $product) {
        if (!$product instanceof WC_Product) {
            continue;
        }
        $name = function_exists('my_theme_get_product_display_name')
            ? (string) my_theme_get_product_display_name($product)
            : (string) $product->get_name();
        $excerpt = function_exists('my_theme_get_product_card_excerpt')
            ? (string) my_theme_get_product_card_excerpt($product, 18)
            : '';
        $price_html = trim((string) $product->get_price_html());
        if ($price_html === '') {
            $price_html = '<span class="product-price-contact-inline">Liên hệ báo giá</span>';
        }
        $pack_summary = function_exists('my_theme_render_loop_pack_summary')
            ? $landing_capture(static function () use ($product) {
                my_theme_render_loop_pack_summary($product, true);
            })
            : '';
        ?>
        <article class="landing-product-card">
          <a class="landing-product-card__thumb" href="<?php echo esc_url($product->get_permalink()); ?>">
            <?php echo $product->get_image('woocommerce_thumbnail', ['alt' => $name, 'loading' => 'lazy']); ?>
          </a>
          <div class="landing-product-card__body">
            <div class="landing-product-card__eyebrow">Sơn kim loại</div>
            <h3 class="landing-product-card__title">
              <a href="<?php echo esc_url($product->get_permalink()); ?>"><?php echo esc_html($name); ?></a>
            </h3>
            <?php if ($excerpt !== '') : ?>
              <p class="landing-product-card__excerpt"><?php echo esc_html($excerpt); ?></p>
            <?php endif; ?>
            <?php if ($pack_summary !== '') : ?>
              <div class="landing-product-card__packs"><?php echo $pack_summary; ?></div>
            <?php endif; ?>
          </div>
          <div class="landing-product-card__actions">
            <div class="landing-product-card__price"><?php echo wp_kses_post($price_html); ?></div>
            <a class="btn btn-primary w-100" href="<?php echo esc_url($product->get_permalink()); ?>">Xem sản phẩm</a>
          </div>
        </article>
        <?php
    }
};

$landing_featured_products = $landing_get_products([
    'jotun-gardex-metal-primer-0-8l',
    'jotun-gardex-premium-gloss-0-8l',
    'jotun-gardex-metal-primer-2-5l',
    'jotun-gardex-premium-gloss-2-5l',
]);
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
        </div>
        <aside class="landing-hero__panel">
          <h3>Thông tin nên gửi trước khi hỏi sơn kim loại</h3>
          <ol class="list-numbered landing-checklist">
            <li>Ảnh hiện trạng rỉ, bong sơn cũ và khu vực tiếp xúc nắng mưa.</li>
            <li>Loại hạng mục: cổng, cửa, lan can, khung thép, mái sắt.</li>
            <li>Nhu cầu chính: chống rỉ, làm mới màu, dặm sửa hay sơn lại toàn bộ.</li>
            <li>Diện tích ước tính và thời gian cần giao vật tư.</li>
          </ol>
        </aside>
      </section>

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
          <?php $landing_render_product_cards($landing_featured_products); ?>
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
      echo do_shortcode(
          '[lead_capture_form source="landing-son-kim-loai" title="Gửi ảnh hạng mục kim loại để nhận gợi ý hệ sơn" subtitle="Điền loại hạng mục, tình trạng rỉ và nhu cầu sử dụng để đội kỹ thuật gọi lại nhanh hơn." button="Nhận tư vấn sơn kim loại"]'
      );
      ?>

      <?php
      if (function_exists('my_theme_render_solution_pathways')) {
          my_theme_render_solution_pathways('metal');
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
