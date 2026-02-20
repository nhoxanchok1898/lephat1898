<?php
/**
 * Functions for My Custom Theme.
 */

// Enqueue main stylesheet with cache-bust by mtime.
add_action('wp_enqueue_scripts', function () {
    $ver_main = file_exists(get_theme_file_path('assets/main.css')) ? filemtime(get_theme_file_path('assets/main.css')) : null;
    wp_enqueue_style('my-custom-theme-font', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Be+Vietnam+Pro:wght@400;600;700;800&display=swap', [], null);
    wp_enqueue_style('my-custom-theme-main', get_theme_file_uri('assets/main.css'), [], $ver_main);
    wp_enqueue_style('my-custom-theme-style', get_stylesheet_uri(), [], filemtime(get_stylesheet_directory() . '/style.css'));

    if (function_exists('is_product') && is_product()) {
        $ver_calc = file_exists(get_theme_file_path('assets/paint-calculator.js')) ? filemtime(get_theme_file_path('assets/paint-calculator.js')) : null;
        wp_enqueue_script('my-custom-theme-paint-calculator', get_theme_file_uri('assets/paint-calculator.js'), [], $ver_calc, true);
    }

    $ver_fix = file_exists(get_theme_file_path('assets/woo-text-fix.js')) ? filemtime(get_theme_file_path('assets/woo-text-fix.js')) : null;
    wp_enqueue_script('my-custom-theme-woo-text-fix', get_theme_file_uri('assets/woo-text-fix.js'), [], $ver_fix, true);
});

/**
 * Bản dịch tiếng Việt cho checkout WooCommerce (không đổi layout).
 */
add_filter('gettext', function ($translated_text, $text, $domain) {
    $map = [
        'Checkout' => 'Thanh toán',
        'Contact information' => 'Thông tin liên hệ',
        "We'll use this email to send you details and updates about your order." => 'Thư điện tử để nhận thông tin đơn hàng.',
        'Order summary' => 'Tóm tắt đơn hàng',
        'Add a coupon' => 'Thêm mã giảm giá',
        'Subtotal' => 'Tạm tính',
        'Total' => 'Tổng cộng',
        'Billing address' => 'Địa chỉ thanh toán',
        'Payment' => 'Thanh toán',
        'Place order' => 'Đặt hàng',
        'Continue' => 'Tiếp tục',
        'First name' => 'Họ',
        'Last name' => 'Tên',
        'Street address' => 'Địa chỉ',
        'Town / City' => 'Thành phố',
        'Postcode / ZIP' => 'Mã bưu chính',
        'Phone' => 'Số điện thoại',
        'Email address' => 'Địa chỉ thư điện tử',
        'State' => 'Tỉnh/Thành',
        'Apartment, suite, unit, etc. (optional)' => 'Căn hộ, tầng, số nhà (tuỳ chọn)',
        'Optional' => 'Tuỳ chọn',
        'Order notes (optional)' => 'Ghi chú đơn hàng (tuỳ chọn)',
        'Country / Region' => 'Quốc gia / Khu vực',
        'Company name (optional)' => 'Công ty (tuỳ chọn)',
    ];
    if (isset($map[$text])) {
        return $map[$text];
    }
    return $translated_text;
}, 10, 3);

// Đổi label & placeholder trường địa chỉ.
add_filter('woocommerce_default_address_fields', function ($fields) {
    if (isset($fields['first_name'])) $fields['first_name']['label'] = 'Họ';
    if (isset($fields['last_name'])) $fields['last_name']['label'] = 'Tên';
    if (isset($fields['address_1'])) { $fields['address_1']['label'] = 'Địa chỉ'; $fields['address_1']['placeholder'] = 'Số nhà, đường'; }
    if (isset($fields['city'])) $fields['city']['label'] = 'Thành phố';
    if (isset($fields['postcode'])) $fields['postcode']['label'] = 'Mã bưu chính';
    if (isset($fields['state'])) $fields['state']['label'] = 'Tỉnh/Thành';
    return $fields;
});

add_filter('woocommerce_checkout_fields', function ($fields) {
    if (isset($fields['billing']['billing_email'])) {
        $fields['billing']['billing_email']['label'] = 'Thư điện tử liên hệ';
        $fields['billing']['billing_email']['placeholder'] = 'email@domain.com';
    }
    if (isset($fields['billing']['billing_phone'])) {
        $fields['billing']['billing_phone']['label'] = 'Số điện thoại';
        $fields['billing']['billing_phone']['placeholder'] = '090xxxxxxx';
    }
    return $fields;
});

add_filter('woocommerce_order_button_text', function () {
    return 'Đặt hàng';
});

// Đổi nhãn "Sale" sang tiếng Việt.
add_filter('woocommerce_sale_flash', function () {
    return '<span class="onsale">Giảm giá</span>';
});

// Đổi nút thêm vào giỏ hàng sang tiếng Việt.
add_filter('woocommerce_product_add_to_cart_text', function () {
    return 'Thêm vào giỏ';
});
add_filter('woocommerce_product_single_add_to_cart_text', function () {
    return 'Thêm vào giỏ';
});

// Đổi tiêu đề trang Woo thành tiếng Việt
add_filter('woocommerce_page_title', function ($title) {
    if (is_shop()) return 'Sản phẩm';
    if (is_cart()) return 'Giỏ hàng';
    if (is_checkout()) return 'Thanh toán';
    if (is_account_page()) return 'Tài khoản';
    return $title;
});
add_filter('the_title', function ($title, $id) {
    if ($id == wc_get_page_id('shop')) return 'Sản phẩm';
    if ($id == wc_get_page_id('cart')) return 'Giỏ hàng';
    if ($id == wc_get_page_id('checkout')) return 'Thanh toán';
    if ($id == wc_get_page_id('myaccount')) return 'Tài khoản';
    return $title;
}, 10, 2);

// Bổ sung dịch cho Woo (núi nút/nội dung còn sót)
add_filter('gettext', function ($translated_text, $text, $domain) {
    $map = [
        'Cart totals' => 'Cộng giỏ hàng',
        'Cart' => 'Giỏ hàng',
        'Proceed to checkout' => 'Thanh toán',
        'Proceed to Checkout' => 'Thanh toán',
        'Checkout' => 'Thanh toán',
        'Apply coupon' => 'Áp dụng mã',
        'Coupon code' => 'Mã giảm giá',
        'Update cart' => 'Cập nhật giỏ hàng',
        'Billing details' => 'Thông tin thanh toán',
        'Order summary' => 'Tóm tắt đơn hàng',
        'Place order' => 'Đặt hàng',
        'Payment' => 'Thanh toán',
        'Add a coupon' => 'Thêm mã giảm giá',
        'Add to cart' => 'Thêm vào giỏ',
        'Select options' => 'Chọn tuỳ chọn',
        'Read more' => 'Xem thêm',
        'View cart' => 'Xem giỏ hàng',
        'View product' => 'Xem sản phẩm',
        'Out of stock' => 'Hết hàng',
        'In stock' => 'Còn hàng',
        'Related products' => 'Sản phẩm liên quan',
        'Description' => 'Mô tả',
        'Additional information' => 'Thông tin bổ sung',
        'Reviews' => 'Đánh giá',
        'Available on backorder' => 'Đặt trước khi hết hàng',
    ];
    if (isset($map[$text])) return $map[$text];
    return $translated_text;
}, 10, 3);

// Enable featured images.
add_theme_support('post-thumbnails');

// Enable document title tag.
add_theme_support('title-tag');

// Register a primary menu.
add_action('after_setup_theme', function () {
    register_nav_menus([
        'primary' => __('Menu chính', 'my-custom-theme'),
    ]);
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
});

// Loại bỏ mục "Bài viết" khỏi menu primary (menu cũ không có blog).
add_filter('wp_nav_menu_objects', function ($items, $args) {
    if (isset($args->theme_location) && $args->theme_location === 'primary') {
        $items = array_filter($items, function ($item) {
            $title = trim(wp_strip_all_tags($item->title));
            if ($title === 'Bài viết') {
                return false;
            }
            if ($title === 'Giỏ hàng') {
                return false;
            }
            if ($title === 'Thanh toán') {
                return false;
            }
            if ($title === 'Liên hệ') {
                return false;
            }
            return true;
        });
        // Loại bỏ mục bị lặp trong menu chính.
        $seen = [];
        $filtered = [];
        foreach ($items as $item) {
            $title = trim(wp_strip_all_tags($item->title));
            if ($title !== '' && (int) $item->menu_item_parent === 0) {
                $key = function_exists('mb_strtolower') ? mb_strtolower($title) : strtolower($title);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
            }
            $filtered[] = $item;
        }
        $items = $filtered;
    }
    return $items;
}, 10, 2);

// Không tự thêm Liên hệ vào menu chính (đã có nút riêng ở header).
add_filter('wp_nav_menu_items', function ($items, $args) {
    return $items;
}, 10, 2);

// Fallback menu if user chưa cấu hình.
function my_theme_fallback_menu() {
    $menu = [
        ['label' => 'Trang chủ', 'url' => '#top'],
        ['label' => 'Cửa hàng', 'url' => wc_get_page_permalink('shop')],
        ['label' => 'Thanh toán', 'url' => wc_get_checkout_url()],
    ];
    echo '<ul id="primary-menu-list" class="menu main-menu">';
    foreach ($menu as $item) {
        echo '<li><a href="' . esc_url($item['url']) . '">' . esc_html($item['label']) . '</a></li>';
    }
    echo '</ul>';
}

// Tự tạo các trang Liên hệ / Chính sách nếu chưa có.
add_action('after_switch_theme', function () {
    $pages = [
        ['title' => 'Liên hệ', 'slug' => 'lien-he', 'content' => 'Liên hệ Đại lý Sơn Phát Tấn để nhận báo giá và tư vấn kỹ thuật.'],
        ['title' => 'Chính sách đổi trả', 'slug' => 'chinh-sach-doi-tra', 'content' => 'Xem điều kiện đổi trả và quy trình hỗ trợ.'],
        ['title' => 'Câu hỏi thường gặp', 'slug' => 'faq', 'content' => 'Tổng hợp câu hỏi thường gặp về đặt hàng và thi công.'],
        ['title' => 'Hướng dẫn mua hàng', 'slug' => 'huong-dan-mua-hang', 'content' => 'Hướng dẫn đặt hàng nhanh tại Đại lý Sơn Phát Tấn.'],
        ['title' => 'Giới thiệu đại lý', 'slug' => 'gioi-thieu', 'content' => 'Thông tin về Đại lý Sơn Phát Tấn, kinh nghiệm và khu vực phục vụ.'],
        ['title' => 'Vận chuyển & giao hàng', 'slug' => 'van-chuyen-giao-hang', 'content' => 'Thông tin phạm vi giao hàng, thời gian và phí vận chuyển.'],
        ['title' => 'Giá thợ / công trình', 'slug' => 'gia-tho', 'content' => 'Ưu đãi và điều kiện áp dụng giá thợ, giá công trình.'],
    ];
    foreach ($pages as $p) {
        if (!get_page_by_path($p['slug'])) {
            wp_insert_post([
                'post_title'   => $p['title'],
                'post_name'    => $p['slug'],
                'post_content' => $p['content'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ]);
        }
    }
});

// Đảm bảo trang hỗ trợ luôn tồn tại (tránh 404 nếu bị xoá nhầm).
add_action('init', function () {
    $pages = [
        ['title' => 'Liên hệ', 'slug' => 'lien-he', 'content' => 'Liên hệ Đại lý Sơn Phát Tấn để nhận báo giá và tư vấn kỹ thuật.'],
        ['title' => 'Chính sách đổi trả', 'slug' => 'chinh-sach-doi-tra', 'content' => 'Xem điều kiện đổi trả và quy trình hỗ trợ.'],
        ['title' => 'Câu hỏi thường gặp', 'slug' => 'faq', 'content' => 'Tổng hợp câu hỏi thường gặp về đặt hàng và thi công.'],
        ['title' => 'Hướng dẫn mua hàng', 'slug' => 'huong-dan-mua-hang', 'content' => 'Hướng dẫn đặt hàng nhanh tại Đại lý Sơn Phát Tấn.'],
        ['title' => 'Giới thiệu đại lý', 'slug' => 'gioi-thieu', 'content' => 'Thông tin về Đại lý Sơn Phát Tấn, kinh nghiệm và khu vực phục vụ.'],
        ['title' => 'Vận chuyển & giao hàng', 'slug' => 'van-chuyen-giao-hang', 'content' => 'Thông tin phạm vi giao hàng, thời gian và phí vận chuyển.'],
        ['title' => 'Giá thợ / công trình', 'slug' => 'gia-tho', 'content' => 'Ưu đãi và điều kiện áp dụng giá thợ, giá công trình.'],
    ];
    foreach ($pages as $p) {
        if (!get_page_by_path($p['slug'])) {
            wp_insert_post([
                'post_title'   => $p['title'],
                'post_name'    => $p['slug'],
                'post_content' => $p['content'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ]);
        }
    }
});

// Mô tả ngắn cho danh mục sản phẩm (shop/category).
function my_theme_get_category_intro($cat_id = 0) {
    $default = 'Chọn sơn chính hãng theo bề mặt và mục đích sử dụng. Tư vấn định mức m² miễn phí.';
    $term = null;
    if (function_exists('is_product_category') && is_product_category()) {
        $term = get_queried_object();
    }
    if (!$term && $cat_id) {
        $term = get_term($cat_id, 'product_cat');
    }
    if ($term && !is_wp_error($term)) {
        if (!empty($term->description)) {
            return wp_strip_all_tags($term->description);
        }
        $slug = $term->slug;
        $map = [
            'noi-that' => 'Phù hợp phòng khách, phòng ngủ, văn phòng. Ưu tiên sơn mùi nhẹ, dễ lau chùi.',
            'ngoai-that' => 'Phù hợp mặt tiền, tường ngoài, ban công. Ưu tiên chống tia UV và bám bẩn.',
            'chong-tham' => 'Phù hợp sân thượng, mái, tường đứng. Yêu cầu lớp màng chống thấm đàn hồi.',
            'bot-tra' => 'Phù hợp bả phẳng tường trước khi sơn phủ, tăng độ mịn và bám dính.',
            'bot-ba' => 'Phù hợp bả phẳng tường trước khi sơn phủ, tăng độ mịn và bám dính.',
            'matit' => 'Phù hợp bả phẳng tường trước khi sơn phủ, tăng độ mịn và bám dính.',
        ];
        foreach ($map as $key => $text) {
            if (strpos($slug, $key) !== false) {
                return $text;
            }
        }
    }
    return $default;
}

// Khối gợi ý sử dụng cho trang sản phẩm.
function my_theme_get_product_insights($product) {
    $fit = [];
    $benefits = [];
    $usage = [];
    $categories = wp_get_post_terms($product->get_id(), 'product_cat');
    $slugs = [];
    if (!empty($categories) && !is_wp_error($categories)) {
        foreach ($categories as $term) {
            $slugs[] = $term->slug;
        }
    }
    $slug_text = implode(' ', $slugs);
    $configs = [
        [
            'keys' => ['noi-that', 'son-noi-that', 'interior'],
            'fit' => ['Phòng khách, phòng ngủ, căn hộ, văn phòng.'],
            'benefits' => ['Mùi nhẹ, dễ lau chùi, màu sắc bền lâu.'],
            'usage' => ['Thi công 1 lớp lót + 2 lớp phủ, bề mặt khô ráo.'],
        ],
        [
            'keys' => ['ngoai-that', 'son-ngoai-that', 'exterior'],
            'fit' => ['Mặt tiền, tường ngoài, ban công, khu vực nắng mưa.'],
            'benefits' => ['Chống tia UV, chống bám bẩn, hạn chế rêu mốc.'],
            'usage' => ['Thi công 2–3 lớp, tránh mưa trong 24 giờ đầu.'],
        ],
        [
            'keys' => ['chong-tham', 'chongtham'],
            'fit' => ['Sân thượng, mái, nhà vệ sinh, tường đứng.'],
            'benefits' => ['Tạo màng chống thấm đàn hồi, che phủ tốt.'],
            'usage' => ['Vệ sinh bề mặt, thi công 2 lớp chéo.'],
        ],
        [
            'keys' => ['bot-tra', 'bot-ba', 'matit'],
            'fit' => ['Bả phẳng tường nội/ngoại thất trước khi sơn phủ.'],
            'benefits' => ['Bề mặt mịn, tăng bám dính cho lớp phủ.'],
            'usage' => ['Bả 1–2 lớp, xả nhám kỹ trước khi sơn.'],
        ],
    ];
    foreach ($configs as $cfg) {
        foreach ($cfg['keys'] as $key) {
            if ($slug_text && strpos($slug_text, $key) !== false) {
                $fit = array_merge($fit, $cfg['fit']);
                $benefits = array_merge($benefits, $cfg['benefits']);
                $usage = array_merge($usage, $cfg['usage']);
                break;
            }
        }
    }
    if (empty($fit)) {
        $fit = ['Nhà ở, căn hộ, văn phòng, công trình dân dụng.'];
    }
    if (empty($benefits)) {
        $benefits = ['Đại lý chính hãng, hàng mới, có chứng từ và bảo hành hãng.'];
    }
    if (empty($usage)) {
        $usage = ['Liên hệ kỹ thuật để nhận định mức và quy trình thi công phù hợp.'];
    }
    return [
        'fit' => array_unique($fit),
        'benefits' => array_unique($benefits),
        'usage' => array_unique($usage),
    ];
}

function my_theme_render_product_insights() {
    if (!function_exists('is_product') || !is_product()) {
        return;
    }
    global $product;
    if (!$product) {
        return;
    }
    $insights = my_theme_get_product_insights($product);
    ?>
    <section class="page-section product-insights">
      <div class="section-heading">
        <div>
          <h2 class="section-title">Gợi ý sử dụng nhanh</h2>
          <p class="section-sub">Tư vấn theo bề mặt và mục đích sử dụng.</p>
        </div>
        <div class="trust-row">
          <span class="trust-item">Đại lý chính hãng</span>
          <span class="trust-item">Hàng mới 100%</span>
          <span class="trust-item">Hỗ trợ kỹ thuật</span>
        </div>
      </div>
      <div class="info-grid">
        <div class="info-card">
          <h3>Phù hợp cho</h3>
          <ul class="list-plain">
            <?php foreach ($insights['fit'] as $item) : ?>
              <li><?php echo esc_html($item); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="info-card">
          <h3>Ưu điểm nổi bật</h3>
          <ul class="list-plain">
            <?php foreach ($insights['benefits'] as $item) : ?>
              <li><?php echo esc_html($item); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="info-card">
          <h3>Gợi ý sử dụng</h3>
          <ul class="list-plain">
            <?php foreach ($insights['usage'] as $item) : ?>
              <li><?php echo esc_html($item); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
      <div class="cta-inline">
        <div class="cta-inline__content">
          <div>
            <h3>Nhận tư vấn kỹ thuật cho sản phẩm này</h3>
            <p class="text-muted">Gửi diện tích và bề mặt, chúng tôi tư vấn định mức phù hợp.</p>
          </div>
          <div class="cta-inline__actions">
            <a class="btn btn-accent" href="tel:0944857999">Gọi kỹ thuật</a>
            <a class="btn btn-outline" href="https://zalo.me/0944857999" target="_blank" rel="noopener">Zalo tư vấn</a>
            <a class="btn btn-primary" href="<?php echo esc_url(home_url('/lien-he')); ?>">Gửi yêu cầu</a>
          </div>
        </div>
      </div>
    </section>
    <?php
}
add_action('woocommerce_after_single_product_summary', 'my_theme_render_product_insights', 6);

function my_theme_render_paint_calculator() {
    if (!function_exists('is_product') || !is_product()) {
        return;
    }
    get_template_part('template-parts/paint-calculator');
}
add_action('woocommerce_after_single_product_summary', 'my_theme_render_paint_calculator', 8);

function my_theme_render_price_faq() {
    if (!function_exists('is_product') || !is_product()) {
        return;
    }
    get_template_part('template-parts/price-faq');
}
add_action('woocommerce_after_single_product_summary', 'my_theme_render_price_faq', 9);

// Dịch nhanh một số chuỗi WooCommerce (giới hạn domain woocommerce).
add_filter('gettext', function ($translated_text, $text, $domain) {
    if ($domain !== 'woocommerce') {
        return $translated_text;
    }
    $map = [
        'Proceed to checkout' => 'Thanh toán',
        'Proceed to Checkout' => 'Thanh toán',
        'Add a coupon' => 'Nhập mã giảm giá',
        'Remove item' => 'Xóa sản phẩm',
        'Cart totals' => 'Cộng giỏ hàng',
        'Subtotal' => 'Tạm tính',
        'Total' => 'Tổng',
        'View cart' => 'Xem giỏ hàng',
        'Checkout' => 'Thanh toán',
        'Cart' => 'Giỏ hàng',
    ];
    return $map[$text] ?? $translated_text;
}, 20, 3);

// Ép nút "Proceed to checkout" hiển thị tiếng Việt bằng hook WooCommerce.
add_action('init', function () {
    remove_action('woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20);
    add_action('woocommerce_proceed_to_checkout', 'my_theme_button_proceed_to_checkout', 20);
});

function my_theme_button_proceed_to_checkout() {
    if (!function_exists('wc_get_checkout_url')) {
        return;
    }
    $checkout_url = wc_get_checkout_url();
    echo '<a href="' . esc_url($checkout_url) . '" class="checkout-button button alt wc-forward">' . esc_html('Thanh toán') . '</a>';
}

// Chỉ chấp nhận thanh toán chuyển khoản 100% (BACS).
add_filter('woocommerce_available_payment_gateways', function ($gateways) {
    if (is_admin()) {
        return $gateways;
    }
    if (isset($gateways['bacs'])) {
        foreach ($gateways as $id => $gateway) {
            if ($id !== 'bacs') {
                unset($gateways[$id]);
            }
        }
    }
    return $gateways;
});

// Thông báo rõ ràng ở giỏ hàng và thanh toán.
add_action('woocommerce_before_cart', function () {
    if (function_exists('wc_print_notice')) {
        wc_print_notice('Đơn hàng chỉ chấp nhận thanh toán chuyển khoản 100% trước khi giao.', 'notice');
    }
});
add_action('woocommerce_before_checkout_form', function () {
    if (function_exists('wc_print_notice')) {
        wc_print_notice('Vui lòng chuyển khoản 100% trước khi giao hàng.', 'notice');
    }
}, 5);

// Đảm bảo người dùng đăng ký chỉ có quyền khách hàng, admin mới có quyền chỉnh sửa.
add_action('user_register', function ($user_id) {
    $user = get_user_by('id', $user_id);
    if (!$user) {
        return;
    }
    if (user_can($user, 'manage_options')) {
        return;
    }
    $role = get_role('customer') ? 'customer' : 'subscriber';
    $user->set_role($role);
});

// Chặn người dùng không phải admin vào wp-admin, đưa về trang tài khoản.
add_action('init', function () {
    if (is_admin() && !defined('DOING_AJAX') && is_user_logged_in()) {
        if (!current_user_can('manage_options')) {
            $account = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/');
            wp_safe_redirect($account);
            exit;
        }
    }
});

// Ẩn thanh admin bar cho khách hàng.
add_filter('show_admin_bar', function ($show) {
    if (!is_user_logged_in()) {
        return $show;
    }
    return current_user_can('manage_options') ? $show : false;
});

// Bật đăng ký tài khoản và ưu tiên trang "Tài khoản" của WooCommerce.
add_filter('pre_option_users_can_register', function ($value) {
    return 1;
});
add_filter('pre_option_woocommerce_enable_myaccount_registration', function ($value) {
    return 'yes';
});
add_filter('woocommerce_enable_myaccount_registration', '__return_true');

// Nếu vào wp-login?action=register thì chuyển về trang tài khoản.
add_action('login_init', function () {
    if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'register') {
        if (function_exists('wc_get_page_permalink')) {
            $account = wc_get_page_permalink('myaccount');
            if (!empty($account)) {
                wp_safe_redirect($account);
                exit;
            }
        }
    }
});

// Luôn hiển thị trường mật khẩu khi đăng ký (không gửi link qua email).
add_filter('pre_option_woocommerce_registration_generate_password', function () {
    return 'no';
});

// Tự tạo email nội bộ nếu người dùng không nhập email.
add_action('wp_loaded', function () {
    if (empty($_POST['register']) || !isset($_POST['email'])) {
        return;
    }
    $email = trim((string) wp_unslash($_POST['email']));
    if ($email !== '') {
        return;
    }
    $phone_raw = isset($_POST['account_phone']) ? wp_unslash($_POST['account_phone']) : '';
    $digits = preg_replace('/\D+/', '', (string) $phone_raw);
    $seed = $digits !== '' ? $digits : 'khach';
    $suffix = wp_generate_password(4, false, false);
    $generated = sanitize_email($seed . '-' . $suffix . '@noemail.local');
    while (email_exists($generated)) {
        $suffix = wp_generate_password(4, false, false);
        $generated = sanitize_email($seed . '-' . $suffix . '@noemail.local');
    }
    $_POST['email'] = $generated;
}, 5);

// Bắt buộc họ tên, số điện thoại, địa chỉ khi đăng ký.
add_filter('woocommerce_process_registration_errors', function ($errors, $username, $password, $email) {
    $full_name = isset($_POST['account_full_name']) ? trim((string) wp_unslash($_POST['account_full_name'])) : '';
    $phone = isset($_POST['account_phone']) ? trim((string) wp_unslash($_POST['account_phone'])) : '';
    $address = isset($_POST['account_address']) ? trim((string) wp_unslash($_POST['account_address'])) : '';

    if ($full_name === '') {
        $errors->add('account_full_name_error', 'Vui lòng nhập họ và tên.');
    }
    if ($phone === '') {
        $errors->add('account_phone_error', 'Vui lòng nhập số điện thoại.');
    } else {
        $digits = preg_replace('/\D+/', '', $phone);
        if (strlen($digits) < 9) {
            $errors->add('account_phone_error', 'Số điện thoại chưa hợp lệ.');
        }
    }
    if ($address === '') {
        $errors->add('account_address_error', 'Vui lòng nhập địa chỉ nhận hàng.');
    }
    return $errors;
}, 10, 4);

// Lưu thông tin đăng ký vào hồ sơ khách hàng.
add_action('woocommerce_created_customer', function ($customer_id) {
    $full_name = isset($_POST['account_full_name']) ? trim((string) wp_unslash($_POST['account_full_name'])) : '';
    $phone = isset($_POST['account_phone']) ? trim((string) wp_unslash($_POST['account_phone'])) : '';
    $address = isset($_POST['account_address']) ? trim((string) wp_unslash($_POST['account_address'])) : '';

    if ($full_name !== '') {
        update_user_meta($customer_id, 'first_name', $full_name);
        update_user_meta($customer_id, 'billing_first_name', $full_name);
    }
    if ($phone !== '') {
        update_user_meta($customer_id, 'billing_phone', $phone);
    }
    if ($address !== '') {
        update_user_meta($customer_id, 'billing_address_1', $address);
        update_user_meta($customer_id, 'shipping_address_1', $address);
    }
    $user = get_user_by('id', $customer_id);
    if ($user && !empty($user->user_email)) {
        update_user_meta($customer_id, 'billing_email', $user->user_email);
    }
}, 10, 1);

// --- WooCommerce: nút chọn dung tích/quy cách & giữ 1 ảnh chính ---
// Biến dropdown variation thành nút (áp dụng mọi attribute trên trang sản phẩm).
add_action('wp_enqueue_scripts', function () {
    if (!function_exists('is_product') || !is_product()) {
        return;
    }

    // Nhẹ, không đụng layout: chỉ thêm inline CSS/JS.
    $css = '
      .wvb-attr-buttons{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}
      .wvb-attr-btn{border:1px solid #ccc;border-radius:4px;padding:8px 12px;background:#fff;cursor:pointer}
      .wvb-attr-btn.is-active{border-color:#111;background:#f2f2f2;font-weight:600}
      .wvb-attr-btn:focus{outline:2px solid #111}
    ';

    $style_handle = null;
    if (wp_style_is('woocommerce-inline', 'enqueued')) {
        $style_handle = 'woocommerce-inline';
    } elseif (wp_style_is('woocommerce-general', 'enqueued')) {
        $style_handle = 'woocommerce-general';
    }
    if ($style_handle) {
        wp_add_inline_style($style_handle, $css);
    }

    $js = <<<'JS'
    jQuery(function($){
      $('.variations_form').each(function(){
        $(this).find('select[data-attribute_name]').each(function(){
          const $sel = $(this);
          if ($sel.data('wvb-ready')) return;
          $sel.data('wvb-ready', true).hide();

          const selected = $sel.val();
          const $wrap = $('<div class="wvb-attr-buttons" role="group"></div>');
          $sel.find('option').each(function(){
            const v = $(this).val(); if(!v) return; // bỏ option trống
            const txt = $(this).text();
            const $btn = $('<button type="button" class="wvb-attr-btn" />').text(txt).attr('data-value', v);
            if (v === selected) $btn.addClass('is-active');
            $btn.on('click', function(){
              $wrap.find('.wvb-attr-btn').removeClass('is-active');
              $(this).addClass('is-active');
              $sel.val(v).trigger('change');
            });
            $wrap.append($btn);
          });
          $sel.after($wrap);
        });
      });
    });
    JS;

    wp_add_inline_script('wc-add-to-cart-variation', $js);
}, 20);

// Không đổi ảnh khi chọn variation (giữ 1 ảnh chính của sản phẩm).
add_filter('woocommerce_available_variation', function ($data) {
    $data['image'] = false;
    return $data;
});

// Thêm trường nhập nhanh dung tích / khối lượng để hiển thị ngoài frontend (cho sản phẩm đơn giản).
add_action('woocommerce_product_options_general_product_data', function () {
    echo '<div class="options_group">';
    woocommerce_wp_text_input([
        'id' => '_display_capacity_list',
        'label' => 'Dung tích hiển thị',
        'placeholder' => '1L | 5L | 15L',
        'desc_tip' => true,
        'description' => 'Nhập danh sách dung tích (dùng | hoặc ,). Có thể kèm giá: 1L:99000 | 5L:450000.',
    ]);
    woocommerce_wp_text_input([
        'id' => '_display_weight_list',
        'label' => 'Khối lượng hiển thị',
        'placeholder' => '40kg',
        'desc_tip' => true,
        'description' => 'Nhập khối lượng hiển thị dưới giá (vd: 40kg hoặc 1kg | 5kg).',
    ]);
    woocommerce_wp_text_input([
        'id' => '_capacity_price_map',
        'label' => 'Bảng giá theo dung tích',
        'placeholder' => '1L:99000 | 5L:450000 | 15L:1200000',
        'desc_tip' => true,
        'description' => 'Dạng capacity:price, cách nhau bởi | hoặc ,. Tự áp dụng cho sản phẩm đơn giản; ưu tiên dùng biến thể nếu đã tạo.',
    ]);
    echo '</div>';
});
add_action('woocommerce_admin_process_product_object', function ($product) {
    $cap = isset($_POST['_display_capacity_list']) ? wc_clean(wp_unslash($_POST['_display_capacity_list'])) : '';
    $wgt = isset($_POST['_display_weight_list']) ? wc_clean(wp_unslash($_POST['_display_weight_list'])) : '';
    $cap_price_map = isset($_POST['_capacity_price_map']) ? wc_clean(wp_unslash($_POST['_capacity_price_map'])) : '';
    if ($cap !== '') {
        $product->update_meta_data('_display_capacity_list', $cap);
    } else {
        $product->delete_meta_data('_display_capacity_list');
    }
    if ($wgt !== '') {
        $product->update_meta_data('_display_weight_list', $wgt);
    } else {
        $product->delete_meta_data('_display_weight_list');
    }
    if ($cap_price_map !== '') {
        $product->update_meta_data('_capacity_price_map', $cap_price_map);
    } else {
        $product->delete_meta_data('_capacity_price_map');
    }
});

// Preset chips cho ô nhập dung tích/khối lượng (admin product edit).
add_action('admin_footer', function () {
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'product') {
        return;
    }
    ?>
    <style>
      .capacity-map-builder { margin-top: 6px; border: 1px solid #e3e7f0; border-radius: 6px; padding: 10px; background: #f8faff; }
      .capacity-map-header { display:flex; gap:10px; font-weight:700; color:#0b172a; margin-bottom:6px; }
      .capacity-map-rows { display:flex; flex-direction:column; gap:6px; }
      .capacity-map-row { display:grid; grid-template-columns: 1fr 1fr auto; gap:8px; align-items:center; }
      .capacity-map-row input { width:100%; }
      .capacity-map-row .button-link-delete { color:#c00; }
      .capacity-map-actions { margin-top:8px; }
    </style>
    <script>
      jQuery(function($){
        // Builder cho bảng giá dung tích _capacity_price_map
        const $mapInput = $('#_capacity_price_map');
        if ($mapInput.length && !$mapInput.data('codex-ready')) {
          $mapInput.data('codex-ready', true);
          const parseMap = (str) => {
            if (!str) return [];
            str = str.replace(/;/g,'|').replace(/\n/g,'|');
            return str.split(/[|,]/).map(s=>s.trim()).filter(Boolean).map(pair=>{
              if (!pair.includes(':')) return null;
              const [c,p] = pair.split(':').map(v=>v.trim());
              if (!c || !p) return null;
              return {cap:c, price:p};
            }).filter(Boolean);
          };
          const serialize = (rows) => rows
            .filter(r=>r.cap && r.price)
            .map(r=>`${r.cap}:${r.price}`)
            .join(' | ');

          const rows = parseMap($mapInput.val());

          const $builder = $(`
            <div class="capacity-map-builder">
              <div class="capacity-map-header">
                <div>Dung tích</div>
                <div>Giá</div>
                <div></div>
              </div>
              <div class="capacity-map-rows"></div>
              <div class="capacity-map-actions">
                <button type="button" class="button add-row">+ Thêm dòng</button>
              </div>
            </div>
          `);

          const $rows = $builder.find('.capacity-map-rows');
          const addRow = (cap='', price='') => {
            const $row = $(`
              <div class="capacity-map-row">
                <input type="text" class="cap" placeholder="5L" value="${cap}">
                <input type="text" class="price" placeholder="450000" value="${price}">
                <button type="button" class="button button-link-delete">Xóa</button>
              </div>
            `);
            $row.on('click', '.button-link-delete', function(){
              $row.remove();
              sync();
            });
            $row.find('input').on('input', sync);
            $rows.append($row);
          };

          const sync = () => {
            const data = [];
            $rows.find('.capacity-map-row').each(function(){
              const cap = $(this).find('.cap').val().trim();
              const price = $(this).find('.price').val().trim();
              if (cap && price) data.push({cap, price});
            });
            $mapInput.val(serialize(data));
          };

          if (rows.length) {
            rows.forEach(r=>addRow(r.cap, r.price));
          } else {
            addRow();
          }

          $builder.find('.add-row').on('click', function(){
            addRow();
          });

          $mapInput.after($builder);
          $mapInput.attr('placeholder', '1L:99000 | 5L:450000 | 15L:1200000');
        }
      });
    </script>
    <?php
});

// Lấy giá trị attribute/meta cho dung tích và khối lượng (ưu tiên attribute).
function my_theme_extract_attr_values($product, $slugs) {
    $values = [];
    foreach ($slugs as $slug) {
        // Lấy terms taxonomy nếu có
        if (taxonomy_exists($slug)) {
            $terms = wc_get_product_terms($product->get_id(), $slug, ['fields' => 'names']);
            if (!empty($terms)) {
                $values = array_merge($values, $terms);
            }
        }
        // Lấy giá trị chuỗi attribute của sản phẩm
        $raw = $product->get_attribute($slug);
        if ($raw) {
            $sep = strpos($raw, '|') !== false ? '|' : (strpos($raw, ',') !== false ? ',' : ' ');
            $parts = array_map('trim', explode($sep, str_replace(['/', ';'], $sep, $raw)));
            $values = array_merge($values, $parts);
        }
    }

    // Nếu là variable product, lấy danh sách options của variation attributes
    if ($product->is_type('variable')) {
        $var_attrs = $product->get_variation_attributes();
        foreach ($slugs as $slug) {
            $key = 'attribute_' . $slug;
            if (!empty($var_attrs[$key])) {
                $values = array_merge($values, array_map('wc_clean', (array) $var_attrs[$key]));
            }
        }
    }

    $values = array_unique(array_filter(array_map('wp_strip_all_tags', $values)));
    return array_values($values);
}

// Parse map capacity:price (number) from meta or capacity list.
function my_theme_parse_capacity_price_map($product) {
    $map_raw = $product->get_meta('_capacity_price_map');
    if (!$map_raw) {
        $map_raw = $product->get_meta('_display_capacity_list'); // cho phép nhập kèm giá trong trường dung tích
    }
    if (!$map_raw) {
        return [];
    }
    // Normalize separators
    $map_raw = str_replace([';', "\n"], '|', $map_raw);
    $pairs = preg_split('/[|,]/', $map_raw);
    $map = [];
    foreach ($pairs as $pair) {
        $pair = trim($pair);
        if ($pair === '') continue;
        if (strpos($pair, ':') === false) continue;
        [$cap, $price] = array_map('trim', explode(':', $pair, 2));
        if ($cap === '' || $price === '') continue;
        $price_num = wc_format_decimal($price, wc_get_price_decimals());
        if ($price_num === '') continue;
        $map[$cap] = (float) $price_num;
    }
    return $map;
}

// Lấy danh sách dung tích/khối lượng (mảng) để render chip.
function my_theme_get_capacity_options($product) {
    if (!$product instanceof WC_Product) {
        return [];
    }
    $capacity_slugs = ['pa_dung-tich', 'pa_dung_tich', 'pa_dungtich', 'dung-tich', 'dung_tich', 'dungtich'];
    $values = my_theme_extract_attr_values($product, $capacity_slugs);

    if (empty($values)) {
        $map = my_theme_parse_capacity_price_map($product);
        if (!empty($map)) {
            $values = array_keys($map);
        }
    }
    if (empty($values)) {
        $cap_list = $product->get_meta('_display_capacity_list');
        if ($cap_list) {
            $cap_list = str_replace([';', "\n"], '|', $cap_list);
            $parts = preg_split('/[|,]/', $cap_list);
            foreach ($parts as $p) {
                $p = trim($p);
                if ($p === '') continue;
                if (strpos($p, ':') !== false) {
                    [$capName] = array_map('trim', explode(':', $p, 2));
                    if ($capName !== '') $values[] = $capName;
                } else {
                    $values[] = $p;
                }
            }
        }
    }
    return array_values(array_unique(array_filter($values)));
}

function my_theme_get_weight_options($product) {
    if (!$product instanceof WC_Product) {
        return [];
    }
    $weight_slugs = ['pa_khoi-luong', 'pa_khoi_luong', 'pa_khoiluong', 'khoi-luong', 'khoi_luong', 'khoiluong', 'trong-luong', 'trong_luong', 'trongluong'];
    $values = my_theme_extract_attr_values($product, $weight_slugs);
    if (empty($values)) {
        $meta = $product->get_meta('_display_weight_list');
        if ($meta) {
            $meta = str_replace([';', "\n"], '|', $meta);
            $parts = preg_split('/[|,]/', $meta);
            $values = array_filter(array_map('trim', $parts));
        }
    }
    return array_values(array_unique(array_filter($values)));
}

// Lấy dung tích & khối lượng (ưu tiên attribute, fallback meta, rồi biến thể).
function my_theme_get_capacity_weight($product) {
    if (!$product instanceof WC_Product) {
        return ['', ''];
    }

    $capacity_slugs = ['pa_dung-tich', 'pa_dung_tich', 'pa_dungtich', 'dung-tich', 'dung_tich', 'dungtich'];
    $weight_slugs   = ['pa_khoi-luong', 'pa_khoi_luong', 'pa_khoiluong', 'khoi-luong', 'khoi_luong', 'khoiluong', 'trong-luong', 'trong_luong', 'trongluong'];

    $capacity_values = my_theme_extract_attr_values($product, $capacity_slugs);
    $capacity = implode(' • ', $capacity_values);

    // Fallback meta tùy chỉnh nhập nhanh (hỗ trợ định dạng cap hoặc cap:price)
    if ($capacity === '') {
        $meta_cap_list = $product->get_meta('_display_capacity_list');
        if ($meta_cap_list) {
            $meta_cap_list = str_replace([';', "\n"], '|', $meta_cap_list);
            $meta_parts = preg_split('/[|,]/', $meta_cap_list);
            $names = [];
            foreach ($meta_parts as $p) {
                $p = trim($p);
                if ($p === '') continue;
                if (strpos($p, ':') !== false) {
                    [$capName] = array_map('trim', explode(':', $p, 2));
                    if ($capName !== '') $names[] = $capName;
                } else {
                    $names[] = $p;
                }
            }
            $names = array_unique(array_filter($names));
            if (!empty($names)) {
                $capacity = implode(' • ', $names);
            }
        }
    }

    // Fallback meta key nếu chưa có attribute
    if ($capacity === '') {
        foreach (['dung_tich', 'dung-tich', 'dungtich'] as $meta_key) {
            $meta_capacity = $product->get_meta($meta_key);
            if ($meta_capacity) {
                $capacity = wc_clean(wp_strip_all_tags($meta_capacity));
                break;
            }
        }
    }

    $weight_values = my_theme_extract_attr_values($product, $weight_slugs);
    $weight_attr = implode(' • ', $weight_values);

    if ($weight_attr === '') {
        $meta_weight_list = $product->get_meta('_display_weight_list');
        if ($meta_weight_list) {
            $sep = strpos($meta_weight_list, '|') !== false ? '|' : (strpos($meta_weight_list, ',') !== false ? ',' : ' ');
            $meta_parts = array_filter(array_map('trim', explode($sep, $meta_weight_list)));
            if ($meta_parts) {
                $weight_attr = implode(' • ', $meta_parts);
            }
        }
    }

    $weight = $product->get_weight();

    // Fallback: lấy từ biến thể đầu tiên nếu sản phẩm biến thể
    if (($capacity === '' || ($weight === '' && $weight_attr === '')) && $product->is_type('variable')) {
        $children = $product->get_visible_children();
        if (empty($children)) {
            $children = $product->get_children();
        }
        foreach ($children as $child_id) {
            $variation = wc_get_product($child_id);
            if (!$variation) {
                continue;
            }
            if ($capacity === '') {
                $var_cap_values = my_theme_extract_attr_values($variation, $capacity_slugs);
                if (!empty($var_cap_values)) {
                    $capacity = implode(' • ', $var_cap_values);
                }
            }
            if ($weight === '') {
                $var_weight = $variation->get_weight();
                if ($var_weight !== '') {
                    $weight = $var_weight;
                }
            }
            if ($weight_attr === '') {
                $var_weight_values = my_theme_extract_attr_values($variation, $weight_slugs);
                if (!empty($var_weight_values)) {
                    $weight_attr = implode(' • ', $var_weight_values);
                }
            }
            if ($capacity !== '' && ($weight !== '' || $weight_attr !== '')) {
                break;
            }
        }
    }

    return [$capacity, $weight, $weight_attr];
}

function my_theme_render_capacity_weight($prod = null) {
    $product = ($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID());
    if (!$product instanceof WC_Product) {
        return;
    }

    [$capacity, $weight, $weight_attr] = my_theme_get_capacity_weight($product);
    $parts = [];
    if ($capacity !== '') {
        $parts[] = sprintf('Dung tích: %s', $capacity);
    }
    if ($weight_attr !== '') {
        $parts[] = sprintf('Khối lượng: %s', $weight_attr);
    } elseif ($weight !== '') {
        $parts[] = sprintf('Khối lượng: %s', wc_format_weight($weight));
    }
    if ($parts) {
        echo '<div class="product-card__meta meta-stack">';
        foreach ($parts as $part) {
            echo '<span class="meta-line">' . esc_html($part) . '</span>';
        }
        echo '</div>';
    }
}

add_action('woocommerce_after_shop_loop_item_title', 'my_theme_render_capacity_weight', 11);
add_action('woocommerce_single_product_summary', 'my_theme_render_capacity_weight', 11);

// Render chip dung tích / khối lượng cho archive + single.
function my_theme_render_capacity_badges($prod = null) {
    $product = ($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID());
    if (!$product instanceof WC_Product) return;

    $caps = my_theme_get_capacity_options($product);
    $weights = my_theme_get_weight_options($product);
    if (empty($caps) && empty($weights)) return;
    echo '<div class="capacity-badges">';
    if (!empty($caps)) {
        echo '<div class="capacity-badges__row" aria-label="Dung tích">';
        foreach ($caps as $cap) {
            echo '<span class="capacity-chip">'.$cap.'</span>';
        }
        echo '</div>';
    }
    if (!empty($weights)) {
        echo '<div class="capacity-badges__row" aria-label="Khối lượng">';
        foreach ($weights as $w) {
            echo '<span class="capacity-chip capacity-chip--muted">'.$w.'</span>';
        }
        echo '</div>';
    }
    echo '</div>';
}
add_action('woocommerce_after_shop_loop_item_title', 'my_theme_render_capacity_badges', 12);
add_action('woocommerce_single_product_summary', 'my_theme_render_capacity_badges', 12);

// --- Simple product: picker dung tích đổi giá theo bảng map ---
function my_theme_render_capacity_price_picker() {
    if (!is_product()) return;
    global $product;
    if (!$product instanceof WC_Product || $product->is_type('variable')) return; // biến thể dùng core

    $map = my_theme_parse_capacity_price_map($product);

    // Nếu chưa có map, nhưng có danh sách dung tích => dùng giá hiện tại cho tất cả
    if (empty($map)) {
        $cap_list = $product->get_meta('_display_capacity_list');
        if ($cap_list) {
            $cap_list = str_replace([';', "\n"], '|', $cap_list);
            $parts = preg_split('/[|,]/', $cap_list);
            $names = [];
            foreach ($parts as $p) {
                $p = trim($p);
                if ($p === '') continue;
                if (strpos($p, ':') !== false) {
                    [$capName] = array_map('trim', explode(':', $p, 2));
                    if ($capName !== '') $names[] = $capName;
                } else {
                    $names[] = $p;
                }
            }
            $names = array_unique(array_filter($names));
            $base = (float) $product->get_price();
            foreach ($names as $n) {
                $map[$n] = $base;
            }
        }
    }

    if (empty($map)) return;

    // Sort by numeric if possible
    $caps = array_keys($map);
    usort($caps, function($a, $b){
        $na = (float) filter_var($a, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $nb = (float) filter_var($b, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        if ($na == $nb) return strcmp($a, $b);
        return ($na < $nb) ? -1 : 1;
    });
    $default_cap = $caps[0];
    $default_price = $map[$default_cap];
    ?>
    <div class="capacity-picker" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
      <div class="capacity-picker__label">Chọn dung tích:</div>
      <div class="capacity-picker__options" role="group" aria-label="Dung tích">
        <?php foreach ($caps as $cap) : ?>
          <button type="button" class="capacity-option<?php echo $cap === $default_cap ? ' is-active' : ''; ?>" data-capacity="<?php echo esc_attr($cap); ?>" data-price="<?php echo esc_attr($map[$cap]); ?>">
            <?php echo esc_html($cap); ?>
          </button>
        <?php endforeach; ?>
      </div>
      <input type="hidden" name="selected_capacity" value="<?php echo esc_attr($default_cap); ?>">
      <input type="hidden" name="selected_capacity_price" value="<?php echo esc_attr($default_price); ?>">
    </div>
    <?php
}
add_action('woocommerce_before_add_to_cart_button', 'my_theme_render_capacity_price_picker', 8);

// JS: cập nhật giá khi chọn dung tích cho sản phẩm đơn giản
add_action('wp_enqueue_scripts', function () {
    if (!is_product()) return;
    wp_add_inline_script('jquery', <<<JS
    jQuery(function($){
      const fmt = window.wc_price || function(n){ return new Intl.NumberFormat('vi-VN').format(n) + ' ₫'; };
      $('.capacity-picker').each(function(){
        const $wrap = $(this);
        const $priceBox = $('.summary .price .amount').first();
        const basePrice = parseFloat(($priceBox.text()||'').replace(/[^0-9,.-]/g,'')) || 0;
        $wrap.on('click', '.capacity-option', function(){
          const $btn = $(this);
          $wrap.find('.capacity-option').removeClass('is-active');
          $btn.addClass('is-active');
          const cap = $btn.data('capacity');
          const price = parseFloat($btn.data('price')) || basePrice;
          $wrap.find('input[name=\"selected_capacity\"]').val(cap);
          $wrap.find('input[name=\"selected_capacity_price\"]').val(price);
          if ($priceBox.length && price > 0) {
            $priceBox.text(fmt(price));
          }
        });
      });
    });
    JS);
}, 30);

// Lưu dung tích vào cart item
add_filter('woocommerce_add_cart_item_data', function ($cart_item_data, $product_id) {
    if (isset($_POST['selected_capacity'])) {
        $cart_item_data['selected_capacity'] = wc_clean(wp_unslash($_POST['selected_capacity']));
    }
    if (isset($_POST['selected_capacity_price'])) {
        $cart_item_data['selected_capacity_price'] = (float) wc_clean(wp_unslash($_POST['selected_capacity_price']));
    }
    if (!empty($cart_item_data)) {
        $cart_item_data['unique_key'] = md5(microtime().rand());
    }
    return $cart_item_data;
}, 10, 2);

// Hiển thị dung tích trong cart/checkout
add_filter('woocommerce_get_item_data', function ($item_data, $cart_item) {
    if (!empty($cart_item['selected_capacity'])) {
        $item_data[] = [
            'name' => 'Dung tích',
            'value' => $cart_item['selected_capacity'],
        ];
    }
    return $item_data;
}, 10, 2);

// Set giá theo dung tích đã chọn
add_action('woocommerce_before_calculate_totals', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        if (!empty($cart_item['selected_capacity_price'])) {
            $cart_item['data']->set_price((float) $cart_item['selected_capacity_price']);
        }
    }
}, 10, 1);

// Cleanup + reimport products by strict brand/type/line. Run with /wp-admin/?run_import=1&force_import=1
add_action('admin_init', function () {
    if (!is_admin() || empty($_GET['run_import'])) {
        return;
    }

    if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
        wp_die('Permission denied.');
    }

    @set_time_limit(0);
    @ini_set('memory_limit', '512M');

    $force = !empty($_GET['force_import']);
    $lock_key = 'wc_import_brand_line_cleanup_v2_done';
    if (get_option($lock_key) === '1' && !$force) {
        wp_die('Import already completed. Add force_import=1 to rerun.');
    }

    if (!class_exists('WooCommerce') || !class_exists('WC_Product_Simple')) {
        wp_die('WooCommerce is required.');
    }

    if (!function_exists('wp_upload_bits')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    if (!function_exists('wp_generate_attachment_metadata')) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }
    if (!function_exists('wp_insert_attachment')) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
    }

    $stats = [
        'deleted' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors'  => 0,
    ];

    $source_candidates = [
        'C:\\Users\\letan\\OneDrive\\Máy tính\\hình dulux',
        WP_CONTENT_DIR . '/uploads/dulux',
        get_theme_file_path('assets/dulux_import'),
        '/var/www/html/wp-content/themes/my-theme/assets/dulux_import',
    ];

    $source_dir = '';
    foreach ($source_candidates as $candidate) {
        if (is_dir($candidate)) {
            $source_dir = $candidate;
            break;
        }
    }

    if ($source_dir === '') {
        $stats['errors']++;
        update_option($lock_key, '1', false);
        wp_die(
            'Deleted: ' . intval($stats['deleted']) .
            ' | Created: ' . intval($stats['created']) .
            ' | Updated: ' . intval($stats['updated']) .
            ' | Skipped: ' . intval($stats['skipped']) .
            ' | Errors: ' . intval($stats['errors']) .
            ' | Source directory not found.'
        );
    }

    $taxonomy_capacity = 'pa_dung-tich';
    $taxonomy_brand_candidates = ['pa_brand', 'product_brand', 'brand'];

    if (!taxonomy_exists($taxonomy_capacity) && function_exists('wc_create_attribute')) {
        $attr_slug = 'dung-tich';
        $attr_id = function_exists('wc_attribute_taxonomy_id_by_name') ? (int) wc_attribute_taxonomy_id_by_name($attr_slug) : 0;
        if ($attr_id <= 0) {
            wc_create_attribute([
                'name'         => 'Dung tich',
                'slug'         => $attr_slug,
                'type'         => 'select',
                'order_by'     => 'menu_order',
                'has_archives' => false,
            ]);
            delete_transient('wc_attribute_taxonomies');
        }
    }

    if (!taxonomy_exists($taxonomy_capacity)) {
        register_taxonomy(
            $taxonomy_capacity,
            ['product'],
            [
                'hierarchical' => true,
                'show_ui'      => false,
                'query_var'    => true,
                'rewrite'      => false,
            ]
        );
    }

    $taxonomy_brand = '';
    foreach ($taxonomy_brand_candidates as $tax) {
        if (taxonomy_exists($tax)) {
            $taxonomy_brand = $tax;
            break;
        }
    }

    $brand_map = [
        'dulux'  => 'Dulux',
        'jotun'  => 'Jotun',
        'nippon' => 'Nippon',
        'kova'   => 'Kova',
    ];

    $to_lower = function ($value) {
        $value = remove_accents((string) $value);
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    };

    $normalize = function ($value) use ($to_lower) {
        $value = $to_lower($value);
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value);
        $value = preg_replace('/\s+/u', ' ', trim((string) $value));
        return $value;
    };

    $format_measure = function ($value, $unit) {
        $value = (float) str_replace(',', '.', (string) $value);
        $text = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
        return $text . ($unit === 'kg' ? 'kg' : 'L');
    };

    $extract_measures = function ($raw) use ($format_measure, $normalize) {
        $items = [];
        $text = $normalize(pathinfo((string) $raw, PATHINFO_FILENAME));
        if (preg_match_all('/(\d+(?:[.,]\d+)?)\s*(l|kg)\b/i', $text, $m, PREG_SET_ORDER)) {
            foreach ($m as $row) {
                $label = $format_measure($row[1], strtolower($row[2]));
                $items[strtolower($label)] = $label;
            }
        }
        return array_values($items);
    };

    $get_brand_key = function ($filename) use ($normalize, $brand_map) {
        $name = $normalize(pathinfo($filename, PATHINFO_FILENAME));
        foreach (array_keys($brand_map) as $brand_key) {
            if (preg_match('/(^|\s)' . preg_quote($brand_key, '/') . '(\s|$)/u', $name)) {
                return $brand_key;
            }
        }
        return '';
    };

    $get_product_type = function ($filename) use ($normalize) {
        $name = $normalize(pathinfo($filename, PATHINFO_FILENAME));
        if (preg_match('/(^|\s)(bot|putty|tret)(\s|$)/u', $name)) {
            return ['bot-tret', 'Bột trét'];
        }
        return ['son', 'Sơn'];
    };

    $get_line_slug = function ($filename, $brand_key) use ($normalize) {
        $base = $normalize(pathinfo($filename, PATHINFO_FILENAME));
        $tokens = preg_split('/\s+/u', $base, -1, PREG_SPLIT_NO_EMPTY);

        $remove = [
            'son', 'paint', 'bot', 'tret', 'putty',
            'noi', 'ngoai', 'that', 'interior', 'exterior',
            'front', 'back', 'side', 'top', 'label',
            'mat', 'truoc', 'sau', 'left', 'right',
            'hinh', 'anh', 'image', 'img', 'packshot',
            'jpeg', 'jpg', 'png', 'webp', 'avif',
            $brand_key,
        ];

        $line_tokens = [];
        foreach ($tokens as $token) {
            if ($token === '' || in_array($token, $remove, true)) {
                continue;
            }
            if (preg_match('/^\d+(?:[.,]\d+)?(l|kg)$/i', $token)) {
                continue;
            }
            if (preg_match('/^\d+(?:[.,]\d+)?$/', $token)) {
                continue;
            }
            if ($token === 'l' || $token === 'kg') {
                continue;
            }
            $line_tokens[] = $token;
        }

        while (!empty($line_tokens) && preg_match('/^\d+$/', (string) end($line_tokens))) {
            array_pop($line_tokens);
        }

        if (empty($line_tokens)) {
            return '';
        }

        return sanitize_title(implode(' ', $line_tokens));
    };

    $format_line_label = function ($line_slug) {
        $map = [
            'noi'           => 'Nội',
            'ngoai'         => 'Ngoại',
            'that'          => 'Thất',
            'easyclean'     => 'EasyClean',
            'weathershield' => 'Weathershield',
            'jotashield'    => 'Jotashield',
            'odour'         => 'Odour',
            'less'          => 'Less',
            'maxilite'      => 'Maxilite',
        ];

        $tokens = preg_split('/[-\s]+/u', (string) $line_slug, -1, PREG_SPLIT_NO_EMPTY);
        $out = [];
        foreach ($tokens as $token) {
            $low = strtolower($token);
            if (isset($map[$low])) {
                $out[] = $map[$low];
                continue;
            }
            if (preg_match('/^[a-z]{1,4}\d+[a-z0-9]*$/i', $token)) {
                $out[] = strtoupper($token);
                continue;
            }
            $out[] = ucfirst(strtolower($token));
        }

        return trim(implode(' ', $out));
    };

    $is_jotun_featured = function ($product_id) use ($to_lower) {
        $thumb_id = (int) get_post_thumbnail_id($product_id);
        if ($thumb_id <= 0) {
            return false;
        }

        $file = (string) get_attached_file($thumb_id);
        $basename = basename($file);
        $filename_no_ext = pathinfo($basename, PATHINFO_FILENAME);
        $haystack = $to_lower(
            $basename . ' ' .
            get_the_title($thumb_id) . ' ' .
            get_post_field('post_excerpt', $thumb_id) . ' ' .
            get_post_field('post_content', $thumb_id) . ' ' .
            wp_get_attachment_url($thumb_id)
        );

        if (strpos($haystack, 'jotun') !== false) {
            return true;
        }

        return preg_match('/^(image\d+|a\d+|packshot|screenshot)/i', (string) $filename_no_ext) === 1;
    };

    $find_product_by_import_key = function ($key) {
        $ids = get_posts([
            'post_type'      => 'product',
            'post_status'    => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => '_import_brand_line_key',
            'meta_value'     => $key,
        ]);
        return !empty($ids) ? (int) $ids[0] : 0;
    };

    $ensure_term = function ($term_name, $taxonomy) {
        $exists = term_exists($term_name, $taxonomy);
        if (!$exists) {
            $res = wp_insert_term($term_name, $taxonomy, ['slug' => sanitize_title($term_name)]);
            if (is_wp_error($res)) {
                return 0;
            }
            return (int) $res['term_id'];
        }
        return is_array($exists) ? (int) $exists['term_id'] : (int) $exists;
    };

    $do_cleanup = !empty($_GET['cleanup_import']);
    if ($do_cleanup) {
        $all_product_ids = get_posts([
            'post_type'      => 'product',
            'post_status'    => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);
        foreach ($all_product_ids as $product_id) {
            $import_key = (string) get_post_meta($product_id, '_import_brand_line_key', true);
            $title_norm = $to_lower(get_the_title($product_id));
            $old_brand_title = preg_match('/(^|\s)(dulux|nippon|maxilite)(\s|$)/u', $title_norm) === 1;
            $need_delete = ($import_key === '') || ($old_brand_title && $is_jotun_featured($product_id));

            if (!$need_delete) {
                continue;
            }

            $trashed = wp_trash_post($product_id);
            if ($trashed !== false && $trashed !== null) {
                $stats['deleted']++;
            } else {
                $stats['errors']++;
            }
        }
    }

    $files = [];
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source_dir, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($it as $f) {
        if (!$f->isFile()) {
            continue;
        }
        $ext = strtolower((string) $f->getExtension());
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'avif'], true)) {
            $files[] = $f->getPathname();
        }
    }
    natsort($files);
    $files = array_values($files);

    $groups = [];
    foreach ($files as $path) {
        $filename = basename($path);
        $brand_key = $get_brand_key($filename);
        if ($brand_key === '') {
            $stats['skipped']++;
            continue;
        }

        [$type_key, $type_label] = $get_product_type($filename);
        $line_slug = $get_line_slug($filename, $brand_key);
        if ($line_slug === '') {
            $stats['skipped']++;
            continue;
        }

        $line_label = $format_line_label($line_slug);
        if ($line_label === '') {
            $line_label = ucfirst(str_replace('-', ' ', $line_slug));
        }

        $product_name = trim($type_label . ' ' . $brand_map[$brand_key] . ' ' . $line_label);
        $key = sanitize_title($brand_key . '-' . $type_key . '-' . $line_slug);

        if (!isset($groups[$key])) {
            $groups[$key] = [
                'brand_key'    => $brand_key,
                'brand_label'  => $brand_map[$brand_key],
                'type_key'     => $type_key,
                'type_label'   => $type_label,
                'line_slug'    => $line_slug,
                'line_label'   => $line_label,
                'product_name' => $product_name,
                'files'        => [],
                'measures'     => [],
            ];
        }

        $groups[$key]['files'][] = $path;

        $measures = $extract_measures($filename);
        foreach ($measures as $ms) {
            $groups[$key]['measures'][strtolower($ms)] = $ms;
        }
    }

    foreach ($groups as $key => $group) {
        $product_name = $group['product_name'];
        $product_id = $find_product_by_import_key($key);

        if ($product_id > 0) {
            $product = wc_get_product($product_id);
            if (!$product || !($product instanceof WC_Product)) {
                $stats['errors']++;
                continue;
            }
            $stats['updated']++;
        } else {
            $product = new WC_Product_Simple();
            $product->set_name($product_name);
            $product->set_slug(sanitize_title($product_name));
            $product->set_status('publish');
            $product->set_catalog_visibility('visible');
            $product->set_regular_price('0');
            $product->set_price('0');
            $product->set_stock_status('instock');
            $product_id = $product->save();
            if (!$product_id) {
                $stats['errors']++;
                continue;
            }
            $stats['created']++;
        }
        update_post_meta($product_id, '_import_brand_line_key', $key);

        $capacity_labels = array_values($group['measures']);
        if (!empty($capacity_labels) && taxonomy_exists($taxonomy_capacity)) {
            $current_terms = wp_get_object_terms($product_id, $taxonomy_capacity, ['fields' => 'names']);
            if (is_wp_error($current_terms)) {
                $current_terms = [];
            }

            $merged_terms = array_values(array_unique(array_merge($current_terms, $capacity_labels)));
            foreach ($merged_terms as $t) {
                $ensure_term($t, $taxonomy_capacity);
            }

            if (!empty($merged_terms)) {
                wp_set_object_terms($product_id, $merged_terms, $taxonomy_capacity, false);

                $term_ids = wp_get_object_terms($product_id, $taxonomy_capacity, ['fields' => 'ids']);
                if (is_wp_error($term_ids)) {
                    $term_ids = [];
                }

                $attrs = $product->get_attributes();
                $attr_obj = new WC_Product_Attribute();
                $tax_id = function_exists('wc_attribute_taxonomy_id_by_name') ? (int) wc_attribute_taxonomy_id_by_name('dung-tich') : 0;
                if ($tax_id > 0) {
                    $attr_obj->set_id($tax_id);
                }
                $attr_obj->set_name($taxonomy_capacity);
                $attr_obj->set_options(array_map('intval', $term_ids));
                $attr_obj->set_position(0);
                $attr_obj->set_visible(true);
                $attr_obj->set_variation(false);
                $attrs[$taxonomy_capacity] = $attr_obj;
                $product->set_attributes($attrs);

                update_post_meta($product_id, '_display_capacity_list', implode(' | ', $merged_terms));
            }
        }

        $cat_id = $ensure_term($group['type_label'], 'product_cat');
        if ($cat_id > 0) {
            wp_set_object_terms($product_id, [$cat_id], 'product_cat', false);
        }

        if ($taxonomy_brand !== '') {
            $ensure_term($group['brand_label'], $taxonomy_brand);
            wp_set_object_terms($product_id, [$group['brand_label']], $taxonomy_brand, false);
        }

        natsort($group['files']);
        $group_files = array_values($group['files']);
        $new_attach_ids = [];

        foreach ($group_files as $img_path) {
            $file_hash = @md5_file($img_path);
            if (!$file_hash) {
                $stats['errors']++;
                continue;
            }

            $exists = get_posts([
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'post_parent'    => $product_id,
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_query'     => [
                    [
                        'key'   => '_import_file_hash',
                        'value' => $file_hash,
                    ],
                    [
                        'key'   => '_import_group_key',
                        'value' => $key,
                    ],
                ],
            ]);

            if (!empty($exists)) {
                $new_attach_ids[] = (int) $exists[0];
                continue;
            }

            $binary = @file_get_contents($img_path);
            if ($binary === false) {
                $stats['errors']++;
                continue;
            }

            $filename = basename($img_path);
            $upload = wp_upload_bits($filename, null, $binary);
            if (!empty($upload['error'])) {
                $stats['errors']++;
                continue;
            }

            $ft = wp_check_filetype($upload['file'], null);
            $attach_id = wp_insert_attachment(
                [
                    'post_mime_type' => $ft['type'],
                    'post_title'     => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
                    'post_content'   => '',
                    'post_status'    => 'inherit',
                ],
                $upload['file'],
                $product_id
            );

            if (is_wp_error($attach_id) || !$attach_id) {
                $stats['errors']++;
                continue;
            }

            $meta = wp_generate_attachment_metadata($attach_id, $upload['file']);
            wp_update_attachment_metadata($attach_id, $meta);
            update_post_meta($attach_id, '_import_file_hash', $file_hash);
            update_post_meta($attach_id, '_import_group_key', $key);

            $new_attach_ids[] = (int) $attach_id;
        }

        $featured = (int) get_post_thumbnail_id($product_id);
        if ($featured <= 0 && !empty($new_attach_ids)) {
            $featured = (int) $new_attach_ids[0];
            set_post_thumbnail($product_id, $featured);
        }

        $current_gallery = $product->get_gallery_image_ids();
        $all_gallery = array_values(array_unique(array_merge($current_gallery, $new_attach_ids)));
        if ($featured > 0) {
            $all_gallery = array_values(array_diff($all_gallery, [$featured]));
        }
        $product->set_gallery_image_ids($all_gallery);

        $product->save();
    }

    update_option($lock_key, '1', false);

    wp_die(
        'Deleted: ' . intval($stats['deleted']) .
        ' | Created: ' . intval($stats['created']) .
        ' | Updated: ' . intval($stats['updated']) .
        ' | Skipped: ' . intval($stats['skipped']) .
        ' | Errors: ' . intval($stats['errors'])
    );
});

// Fix imported product titles/slugs from _import_brand_line_key. Run once with /wp-admin/?fix_titles=1
add_action('admin_init', function () {
    if (!is_admin() || empty($_GET['fix_titles'])) {
        return;
    }

    if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
        wp_die('Permission denied.');
    }

    $brand_map = [
        'dulux'  => 'Dulux',
        'jotun'  => 'Jotun',
        'nippon' => 'Nippon',
        'kova'   => 'Kova',
    ];
    $to_lower = function ($value) {
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    };

    $format_line = function ($line_slug) {
        $line_slug = trim((string) $line_slug, "- \t\n\r\0\x0B");
        if ($line_slug === '') {
            return '';
        }

        $tokens = preg_split('/[-\s]+/u', $line_slug);
        $token_map = [
            'noi'          => 'Nội',
            'ngoai'        => 'Ngoại',
            'that'         => 'Thất',
            'chuyen'       => 'Chuyên',
            'dung'         => 'Dụng',
            'lot'          => 'Lót',
            'chong'        => 'Chống',
            'kiem'         => 'Kiềm',
            'de'           => 'Dễ',
            'lau'          => 'Lau',
            'chui'         => 'Chùi',
            'tran'         => 'Trần',
            'tuong'        => 'Tường',
            'easyclean'    => 'EasyClean',
            'weathershield'=> 'Weathershield',
            'odour'        => 'Odour',
            'less'         => 'Less',
            'jotashield'   => 'Jotashield',
            'maxilite'     => 'Maxilite',
        ];
        $pretty = [];
        foreach ($tokens as $token) {
            if ($token === '') {
                continue;
            }
            $token_l = strtolower($token);
            if (isset($token_map[$token_l])) {
                $pretty[] = $token_map[$token_l];
                continue;
            }
            if (preg_match('/^[a-z]{1,4}\d+[a-z0-9]*$/i', $token)) {
                $pretty[] = strtoupper($token);
                continue;
            }
            $pretty[] = ucfirst(strtolower($token));
        }

        return trim(implode(' ', $pretty));
    };

    $parse_key = function ($key) use ($brand_map) {
        $brand_slug = '';
        $type_label = '';
        $line_slug = '';

        if (preg_match('/^(dulux|jotun|nippon|kova)-(bot-tret|son)-(.+)$/', $key, $m)) {
            $brand_slug = $m[1];
            $type_label = ($m[2] === 'bot-tret') ? 'Bột trét' : 'Sơn';
            $line_slug = $m[3];
        } elseif (preg_match('/^(dulux|jotun|nippon|kova)-(.+)$/', $key, $m)) {
            $brand_slug = $m[1];
            $rest = $m[2];

            if (strpos($rest, 'bot-tret-') === 0) {
                $type_label = 'Bột trét';
                $line_slug = substr($rest, 9);
            } elseif (strpos($rest, 'son-') === 0) {
                $type_label = 'Sơn';
                $line_slug = substr($rest, 4);
            } else {
                $line_slug = $rest;
            }
        }

        if ($brand_slug === '' || !isset($brand_map[$brand_slug])) {
            return ['', '', ''];
        }
        if ($type_label === '') {
            $type_label = 'Sơn';
        }
        $line_slug = trim((string) $line_slug, "- \t\n\r\0\x0B");
        if ($line_slug === '') {
            $line_slug = 'dong-chuan';
        }

        return [$brand_slug, $type_label, sanitize_title($line_slug)];
    };

    $derive_from_title = function ($title) use ($brand_map, $to_lower) {
        $title_plain = remove_accents((string) $title);
        $title_lower = $to_lower($title_plain);
        $title_norm = preg_replace('/[_\-]+/u', ' ', $title_lower);
        $title_norm = preg_replace('/\s+/u', ' ', trim($title_norm));

        $brand_slug = '';
        foreach (array_keys($brand_map) as $b) {
            if (preg_match('/(^|\s)' . preg_quote($b, '/') . '(\s|$)/u', $title_norm)) {
                $brand_slug = $b;
                break;
            }
        }
        if ($brand_slug === '') {
            return ['', '', ''];
        }

        $type_label = (strpos($title_norm, 'bot tret') !== false || strpos($title_norm, 'putty') !== false) ? 'Bột trét' : 'Sơn';
        $type_tokens = ($type_label === 'Bột trét') ? ['bot tret', 'bot', 'tret', 'putty'] : ['son', 'paint'];

        $line = $title_norm;
        foreach ($type_tokens as $tk) {
            $line = preg_replace('/(^|\s)' . preg_quote($tk, '/') . '(\s|$)/u', ' ', $line);
        }
        $line = preg_replace('/(^|\s)' . preg_quote($brand_slug, '/') . '(\s|$)/u', ' ', $line);
        $line = preg_replace('/\b\d+(?:[.,]\d+)?\s*(l|kg)\b/iu', ' ', $line);
        $line = preg_replace('/\s+/u', ' ', trim($line));

        $line_slug = sanitize_title($line);
        if ($line_slug === '') {
            $line_slug = 'dong-chuan';
        }

        return [$brand_slug, $type_label, $line_slug];
    };

    $ids = get_posts([
        'post_type'      => 'product',
        'post_status'    => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);

    $updated = 0;
    $skipped = 0;
    $meta_fixed = 0;

    foreach ($ids as $pid) {
        $key = (string) get_post_meta($pid, '_import_brand_line_key', true);
        if ($key !== '') {
            [$brand_slug, $type_label, $line_slug] = $parse_key($key);
        } else {
            [$brand_slug, $type_label, $line_slug] = ['', '', ''];
        }

        if ($brand_slug === '' || $line_slug === '') {
            [$brand_slug, $type_label, $line_slug] = $derive_from_title((string) get_the_title($pid));
        }

        if ($brand_slug === '' || $line_slug === '' || !isset($brand_map[$brand_slug])) {
            $skipped++;
            continue;
        }

        $target_key = sanitize_title($brand_slug . '-' . (($type_label === 'Bột trét') ? 'bot-tret' : 'son') . '-' . $line_slug);
        if ($key !== $target_key) {
            update_post_meta($pid, '_import_brand_line_key', $target_key);
            $meta_fixed++;
        }

        $brand_label = $brand_map[$brand_slug];
        $line_label = $format_line($line_slug);
        $new_title = trim($type_label . ' ' . $brand_label . ' ' . $line_label);

        if ($new_title === '') {
            $skipped++;
            continue;
        }

        $new_slug = sanitize_title($new_title);
        $current_post = get_post($pid);
        if (!$current_post) {
            $skipped++;
            continue;
        }

        $current_title = (string) $current_post->post_title;
        $current_slug = (string) $current_post->post_name;

        if ($current_title === $new_title && $current_slug === $new_slug) {
            $skipped++;
            continue;
        }

        wp_update_post([
            'ID'         => $pid,
            'post_title' => $new_title,
            'post_name'  => $new_slug,
        ]);

        $updated++;
    }

    wp_die(
        'Titles/slugs fixed. Updated: ' . intval($updated) .
        ' | Meta fixed: ' . intval($meta_fixed) .
        ' | Skipped: ' . intval($skipped)
    );
});
