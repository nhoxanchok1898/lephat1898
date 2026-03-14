<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('my_theme_is_woocommerce_front_context')) {
    function my_theme_is_woocommerce_front_context()
    {
        if (function_exists('is_woocommerce') && is_woocommerce()) {
            return true;
        }
        if (function_exists('is_cart') && is_cart()) {
            return true;
        }
        if (function_exists('is_checkout') && is_checkout()) {
            return true;
        }
        if (function_exists('is_account_page') && is_account_page()) {
            return true;
        }
        return false;
    }
}

if (!function_exists('my_theme_gettext_translation_map')) {
    function my_theme_gettext_translation_map()
    {
        static $map = null;
        if (is_array($map)) {
            return $map;
        }

        $map = [
            'Checkout' => 'Thanh toán',
            'Cart' => 'Giỏ hàng',
            'Proceed to checkout' => 'Thanh toán',
            'Proceed to Checkout' => 'Thanh toán',
            'Add a coupon' => 'Nhập mã giảm giá',
            'Cart totals' => 'Cộng giỏ hàng',
            'Subtotal' => 'Tạm tính',
            'Total' => 'Tổng',
            'View cart' => 'Xem giỏ hàng',
            'Update cart' => 'Cập nhật giỏ hàng',
            'Update totals' => 'Cập nhật tổng',
            'Apply coupon' => 'Áp dụng mã',
            'Coupon code' => 'Mã giảm giá',
            'Coupon:' => 'Mã giảm giá:',
            'Have a coupon?' => 'Bạn có mã giảm giá?',
            'Click here to enter your code' => 'Bấm vào đây để nhập mã',
            'If you have a coupon code, please apply it below.' => 'Nếu có mã giảm giá, vui lòng nhập bên dưới.',
            'Order summary' => 'Tóm tắt đơn hàng',
            'Your order' => 'Đơn hàng của bạn',
            'Product' => 'Sản phẩm',
            'Product quantity' => 'Số lượng sản phẩm',
            '%s quantity' => '%s số lượng',
            'Payment' => 'Thanh toán',
            'Place order' => 'Đặt hàng',
            'Continue' => 'Tiếp tục',
            'Contact information' => 'Thông tin liên hệ',
            "We'll use this email to send you details and updates about your order." => 'Thư điện tử để nhận thông tin đơn hàng.',
            'Billing details' => 'Thông tin thanh toán',
            'Billing address' => 'Địa chỉ thanh toán',
            'First name' => 'Họ',
            'Last name' => 'Tên',
            'Street address' => 'Địa chỉ',
            'Town / City' => 'Thành phố',
            'Postcode / ZIP' => 'Mã bưu chính',
            'State' => 'Tỉnh/Thành',
            'Phone' => 'Số điện thoại',
            'Email address' => 'Địa chỉ thư điện tử',
            'Country / Region' => 'Quốc gia / Khu vực',
            'Select a country / region…' => 'Chọn quốc gia / khu vực…',
            'Select a country / region...' => 'Chọn quốc gia / khu vực…',
            'Select a country / region&hellip;' => 'Chọn quốc gia / khu vực…',
            'Select a country / region' => 'Chọn quốc gia / khu vực',
            'Update country / region' => 'Cập nhật quốc gia / khu vực',
            'Company name' => 'Tên công ty',
            'Company name (optional)' => 'Công ty (tuỳ chọn)',
            'Apartment, suite, unit, etc. (optional)' => 'Căn hộ, tầng, số nhà (tuỳ chọn)',
            'Optional' => 'Tuỳ chọn',
            '(optional)' => '(tùy chọn)',
            'optional' => 'tùy chọn',
            'required' => 'bắt buộc',
            'Order notes' => 'Ghi chú đơn hàng',
            'Order notes (optional)' => 'Ghi chú đơn hàng (tuỳ chọn)',
            'Notes about your order, e.g. special notes for delivery.' => 'Ví dụ: giao giờ hành chính, gọi trước khi giao.',
            'Description' => 'Mô tả',
            'Additional information' => 'Thông tin bổ sung',
            'Reviews' => 'Đánh giá',
            'Related products' => 'Sản phẩm liên quan',
            'Add to cart' => 'Thêm vào giỏ',
            'Select options' => 'Chọn tuỳ chọn',
            'Read more' => 'Xem thêm',
            'Out of stock' => 'Hết hàng',
            'In stock' => 'Còn hàng',
            'Available on backorder' => 'Đặt trước khi hết hàng',
            'Your cart is currently empty.' => 'Giỏ hàng của bạn đang trống.',
            'Return to shop' => 'Quay lại cửa hàng',
            'Remove item' => 'Xóa sản phẩm',
            'Since your browser does not support JavaScript, or it is disabled, please ensure you click the Update Totals button before placing your order. You may be charged more than the amount stated above if you fail to do so.' => 'Trình duyệt của bạn đang tắt JavaScript. Vui lòng bấm Cập nhật tổng trước khi đặt hàng để tránh sai lệch số tiền.',
            'Since your browser does not support JavaScript, or it is disabled, please ensure you click the <em>Update Totals</em> button before placing your order. You may be charged more than the amount stated above if you fail to do so.' => 'Trình duyệt của bạn đang tắt JavaScript. Vui lòng bấm <em>Cập nhật tổng</em> trước khi đặt hàng để tránh sai lệch số tiền.',
            'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our privacy policy.' => 'Thông tin cá nhân của bạn chỉ được dùng để xử lý đơn hàng và hỗ trợ trải nghiệm mua sắm theo chính sách bảo mật.',
        ];

        return $map;
    }
}

if (!function_exists('my_theme_translate_gettext_cached')) {
    function my_theme_translate_gettext_cached($translated_text, $text, $domain)
    {
        $raw = trim((string) $text);
        if ($raw === '') {
            return $translated_text;
        }

        $is_woo_domain = ($domain === 'woocommerce' || $domain === 'woocommerce-blocks');
        if (!$is_woo_domain && !my_theme_is_woocommerce_front_context()) {
            return $translated_text;
        }

        $map = my_theme_gettext_translation_map();
        if (isset($map[$raw])) {
            return $map[$raw];
        }

        $decoded = trim((string) html_entity_decode($raw, ENT_QUOTES, 'UTF-8'));
        if ($decoded !== '' && isset($map[$decoded])) {
            return $map[$decoded];
        }

        if (strpos($raw, 'Since your browser does not support JavaScript') !== false) {
            if (strpos($raw, '%1$s') !== false || strpos($raw, '%2$s') !== false) {
                return 'Trình duyệt của bạn đang tắt JavaScript. Vui lòng bấm %1$sCập nhật tổng%2$s trước khi đặt hàng để tránh sai lệch số tiền.';
            }
            return 'Trình duyệt của bạn đang tắt JavaScript. Vui lòng bấm <em>Cập nhật tổng</em> trước khi đặt hàng để tránh sai lệch số tiền.';
        }

        return $translated_text;
    }
}

add_filter('gettext', 'my_theme_translate_gettext_cached', 20, 3);
