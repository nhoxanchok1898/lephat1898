<?php
/** Trang chu tong hop */
get_header();
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
$contact_url = home_url('/lien-he/');
$featured_url = home_url('/#featured-home');
$store_snapshot = function_exists('my_theme_get_store_snapshot')
    ? my_theme_get_store_snapshot()
    : [];
$catalog_count = isset($store_snapshot['catalog_count']) ? max(0, (int) $store_snapshot['catalog_count']) : 0;
$brand_count = isset($store_snapshot['brand_count']) ? max(0, (int) $store_snapshot['brand_count']) : 0;
$phone_display = isset($store_snapshot['phone_display']) ? (string) $store_snapshot['phone_display'] : '0944 857 999';
$phone_href = isset($store_snapshot['phone_href']) ? (string) $store_snapshot['phone_href'] : 'tel:0944857999';
$zalo_url = isset($store_snapshot['zalo_url']) ? (string) $store_snapshot['zalo_url'] : 'https://zalo.me/0944857999';
$address_full = isset($store_snapshot['address_full']) ? (string) $store_snapshot['address_full'] : '392 TL10, Bình Trị Đông, Bình Tân, TP.HCM';
$intro_gallery = [
    'hero' => get_theme_file_uri('assets/images/storefront/phat-tan-noi-that.jpg'),
    'tough' => get_theme_file_uri('assets/images/storefront/phat-tan-maxilite-tough.jpg'),
    'product' => get_theme_file_uri('assets/images/storefront/phat-tan-maxilite-hi-cover.jpg'),
];
?>
<main id="main-content">
  <div class="container home-page">
    <style id="home-page-grid-balance">
      @media (min-width: 1320px) {
        .home-featured-tabs .product-grid--home,
        .home-sale-products__grid,
        .home-latest-products__grid {
          grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
        }
      }
    </style>
    <section class="page-section home-page__intro" aria-labelledby="home-main-heading">
      <div class="home-page__intro-layout">
        <div class="home-page__intro-copy">
          <p class="eyebrow eyebrow-muted">Đại lý Sơn Phát Tấn</p>
          <h1 id="home-main-heading" class="section-title">Sơn chính hãng theo hãng, dòng và mã</h1>
          <p class="section-sub">Trang chủ ưu tiên sản phẩm nổi bật và lối vào báo giá nhanh. Các danh mục, bề mặt thi công và nhóm giải pháp đã chuyển hết lên menu để khách vào là thấy hàng trước, không bị rối.</p>
          <?php if ($catalog_count > 0 || $brand_count > 0) : ?>
            <p class="home-page__intro-stock">
              <?php echo esc_html((string) max(0, $catalog_count)); ?> sản phẩm đang có sẵn
              <?php if ($brand_count > 0) : ?>
                <span>từ <?php echo esc_html((string) max(0, $brand_count)); ?> thương hiệu.</span>
              <?php endif; ?>
            </p>
          <?php endif; ?>
          <div class="hero-actions home-page__intro-actions">
            <a class="btn btn-primary" href="<?php echo esc_url($featured_url); ?>">Xem sản phẩm nổi bật</a>
            <a class="btn btn-outline" href="<?php echo esc_url($shop_url); ?>">Mở toàn bộ kho sản phẩm</a>
            <a class="btn btn-accent" href="<?php echo esc_url($contact_url); ?>">Nhận báo giá theo mã</a>
          </div>
          <div class="home-page__intro-badges trust-row" aria-label="Điểm mạnh vận hành">
            <span class="trust-item">Lọc theo hãng và dòng</span>
            <span class="trust-item">Báo giá theo đúng mã</span>
            <span class="trust-item">Hỗ trợ thợ và công trình</span>
          </div>
        </div>
        <div class="home-page__intro-showcase" aria-label="Hình ảnh thực tế tại cửa hàng">
          <div class="home-page__intro-gallery">
            <figure class="home-page__shot home-page__shot--hero">
              <img
                src="<?php echo esc_url($intro_gallery['hero']); ?>"
                alt="Không gian trưng bày và kho hàng thực tế tại Đại lý Sơn Phát Tấn"
                width="1280"
                height="588"
                loading="eager"
                decoding="async"
                fetchpriority="high"
              >
              <figcaption class="home-page__shot-caption">
                <span class="home-page__shot-kicker">Ảnh thực tế tại cửa hàng</span>
                <strong>Kho hàng và quầy trưng bày của Phát Tấn</strong>
                <span class="home-page__shot-meta"><?php echo esc_html($address_full); ?>. Hàng thật, kệ thật và quy cách thật được đưa lên ngay phần đầu trang.</span>
              </figcaption>
            </figure>
            <figure class="home-page__shot home-page__shot--tough">
              <img
                src="<?php echo esc_url($intro_gallery['tough']); ?>"
                alt="Sản phẩm Maxilite Tough có sẵn tại cửa hàng"
                width="960"
                height="1280"
                loading="lazy"
                decoding="async"
              >
              <figcaption class="home-page__shot-pill">Maxilite Tough sẵn kho</figcaption>
            </figure>
            <figure class="home-page__shot home-page__shot--product">
              <img
                src="<?php echo esc_url($intro_gallery['product']); ?>"
                alt="Sản phẩm Maxilite Hi-Cover đang có tại cửa hàng"
                width="960"
                height="1280"
                loading="lazy"
                decoding="async"
              >
              <figcaption class="home-page__shot-pill">Xem quy cách thật trước khi chốt</figcaption>
            </figure>
          </div>
        </div>
        <aside class="home-page__intro-panel" aria-label="Hỗ trợ mua nhanh">
          <p class="home-page__intro-panel-label">Kho thật - hỗ trợ thật</p>
          <ul class="home-page__intro-metrics list-plain">
            <li><strong>Ảnh chụp tại cửa hàng</strong><span>Khách nhìn thấy mặt tiền, kệ hàng và quy cách thực tế ngay từ đầu trang.</span></li>
            <li><strong>Gửi mã hoặc ảnh công trình để báo nhanh</strong><span>Đội ngũ cửa hàng kiểm tra tồn, quy cách và tư vấn theo đúng nhu cầu thi công.</span></li>
            <li><strong>Hỗ trợ lấy hàng gấp cho thợ</strong><span>Phù hợp cho nhà dân, công trình sửa chữa hoặc đơn cần chốt nhanh trong ngày.</span></li>
          </ul>
          <div class="home-page__intro-contact">
            <a class="btn btn-primary btn-sm" href="<?php echo esc_url($phone_href); ?>">Gọi <?php echo esc_html($phone_display); ?></a>
            <a class="btn btn-outline btn-sm" href="<?php echo esc_url($zalo_url); ?>" target="_blank" rel="noopener">Zalo kỹ thuật</a>
          </div>
        </aside>
      </div>
    </section>
    <?php get_template_part('template-parts/home', 'featured'); ?>
    <?php get_template_part('template-parts/home', 'sale-products'); ?>
    <?php get_template_part('template-parts/home', 'latest-products'); ?>
    <?php get_template_part('template-parts/home', 'lead-capture'); ?>
    <?php get_template_part('template-parts/home', 'posts'); ?>
  </div>
</main>
<?php get_footer();
