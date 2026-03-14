<?php
/**
 * Apply curated catalog fixes for remaining missing-price products and low-res images.
 *
 * Run:
 *   Get-Content -Raw tools/apply_curated_catalog_fixes.php | docker compose -f docker-compose.wordpress.yml exec -T wordpress php
 * Optional:
 *   ... php -- --dry-run
 *   ... php -- --skip-prices
 *   ... php -- --skip-images
 */

require '/var/www/html/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

if (!function_exists('download_url')) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
}
if (!function_exists('media_handle_sideload')) {
    require_once ABSPATH . 'wp-admin/includes/media.php';
}
if (!function_exists('wp_generate_attachment_metadata')) {
    require_once ABSPATH . 'wp-admin/includes/image.php';
}

$args = array_values(array_filter((array) ($_SERVER['argv'] ?? []), static function ($value): bool {
    return is_string($value) && $value !== '';
}));
$dry_run = in_array('--dry-run', $args, true);
$skip_prices = in_array('--skip-prices', $args, true);
$skip_images = in_array('--skip-images', $args, true);

$theme_root = (string) get_theme_file_path();
$market_report_path = wp_normalize_path(trailingslashit($theme_root) . 'data/market-pricing.json');
$market_report = [];
if (file_exists($market_report_path)) {
    $decoded = json_decode((string) file_get_contents($market_report_path), true);
    if (is_array($decoded) && !empty($decoded['products']) && is_array($decoded['products'])) {
        foreach ($decoded['products'] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $slug = sanitize_title((string) ($row['slug'] ?? ''));
            if ($slug !== '') {
                $market_report[$slug] = $row;
            }
        }
    }
}

$price_overrides = [
    'chatchongthamduluxaquatechchongthamvuottroi' => [
        'weight' => ['6kg', '20kg'],
        'market_price_map' => [
            '6kg' => 1274000,
            '20kg' => 3904545,
        ],
        'source_page' => 'https://tavaco.vn/son-chong-tham-dulux-pha-xi-mang-aquatech.html',
        'source_note' => 'Aquatech pha xi mang page + public Dulux price list reference',
    ],
    'duluxweathershieldchatchongtham' => [
        'weight' => ['6kg', '20kg'],
        'market_price_map' => [
            '6kg' => 715000,
            '20kg' => 2180000,
        ],
        'source_page' => 'https://sonzin.vn/gia-son-dulux.html',
        'source_note' => 'Son Zin Dulux price list',
    ],
    'duluxweathershieldcolourprotectbematbong' => [
        'capacity' => ['1L', '5L', '15L'],
        'market_price_map' => [
            '1L' => 454000,
            '5L' => 2046000,
            '15L' => 5828000,
        ],
        'source_page' => 'https://sonthanhcong.com/son-dulux-weathershield-colour-protect/',
        'source_note' => 'Son Thanh Cong product variations',
    ],
    'duluxweathershieldcolourprotectbematmo' => [
        'capacity' => ['1L', '5L', '15L'],
        'market_price_map' => [
            '1L' => 454000,
            '5L' => 2046000,
            '15L' => 5828000,
        ],
        'source_page' => 'https://tavaco.vn/son-dulux-weathershield-colour-protect-be-mat-mo-e015.html',
        'source_note' => 'Tavaco pricing table',
    ],
    'duluxweathershieldroyalshine' => [
        'capacity' => ['1L', '5L'],
        'market_price_map' => [
            '1L' => 528000,
            '5L' => 1720000,
        ],
        'source_page' => 'https://tavaco.vn/son-dulux-weathershield-royal-shine-rs86.html',
        'source_note' => 'Tavaco product page + market cross-check',
    ],
    'keo-dan-gach-webertai-fix-40kg' => [
        'weight' => ['40kg'],
        'market_price_map' => [
            '40kg' => 224000,
        ],
        'source_page' => 'https://phugiacongtrinh.com/product/keo-dan-gach-webertai-fix/',
        'source_note' => 'Phu Gia Cong Trinh product price',
    ],
    'keo-dan-gach-webertai-gres-40kg' => [
        'weight' => ['40kg'],
        'market_price_map' => [
            '40kg' => 359000,
        ],
        'source_page' => 'https://phugiacongtrinh.com/product/keo-dan-gach-webertai-gres/',
        'source_note' => 'Phu Gia Cong Trinh product price',
    ],
    'keo-dan-gach-webertai-vis-40kg' => [
        'weight' => ['40kg'],
        'market_price_map' => [
            '40kg' => 199900,
        ],
        'source_page' => 'https://phugiacongtrinh.com/product/keo-dan-gach-webertai-vis/',
        'source_note' => 'Phu Gia Cong Trinh product price',
    ],
    'webercolor-hr' => [
        'weight' => ['1kg'],
        'market_price_map' => [
            '1kg' => 199900,
        ],
        'source_page' => 'https://phugiacongtrinh.com/product/keo-cha-ron-weber-color-hr/',
        'source_note' => 'Phu Gia Cong Trinh product price',
    ],
    'webercolor-outside' => [
        'weight' => ['1kg'],
        'market_price_map' => [
            '1kg' => 59900,
        ],
        'source_page' => 'https://phugiacongtrinh.com/product/keo-cha-ron-ngoai-troi-webercolor-outside/',
        'source_note' => 'Phu Gia Cong Trinh product price',
    ],
    'webercolor-sp' => [
        'weight' => ['18.2kg'],
        'market_price_map' => [
            '18.2kg' => 2000000,
        ],
        'source_page' => 'https://chongthamthanhcong.vn/san-pham/keo-cha-ron/webercolor-sp.html',
        'source_note' => 'Chong Tham Thanh Cong product price',
    ],
    'webercolor-sp-ho-boi' => [
        'weight' => ['18.2kg'],
        'market_price_map' => [
            '18.2kg' => 2000000,
        ],
        'source_page' => 'https://chongthamthanhcong.vn/san-pham/keo-cha-ron/webercolor-sp.html',
        'source_note' => 'Chong Tham Thanh Cong product price',
    ],
    'weberdry-pu' => [
        'weight' => ['20kg'],
        'market_price_map' => [
            '20kg' => 3132000,
        ],
        'source_page' => 'https://dtlgroup.vn/weberdry-pu-grey-20kg-chong-tham-goc-polyurethane-1-thanh-phan',
        'source_note' => 'DTL Group product payload price',
    ],
    'weberdry-pu-pro' => [
        'weight' => ['20kg'],
        'market_price_map' => [
            '20kg' => 2600000,
        ],
        'source_page' => 'https://chongthamthanhcong.vn/san-pham/vat-lieu-chong-tham/weberdry-pu-pro.html',
        'source_note' => 'Chong Tham Thanh Cong product price',
    ],
    'weberdry-seal-pro' => [
        'weight' => ['5kg'],
        'market_price_map' => [
            '5kg' => 1125000,
        ],
        'source_page' => 'https://topto.vn/san-pham/chong-tham-weber-dry-seal-5kg/',
        'source_note' => 'Topto product sale price',
    ],
    'weberprime-spf' => [
        'pack' => ['Thùng 17kg'],
        'market_price_map' => [
            'Thùng 17kg' => 2495000,
        ],
        'source_page' => 'https://a-zone.vn/c/vat-tu-chong-tham-kova-sika/p/weberprime-spf-11-17kg-fdc529',
        'source_note' => 'A-Zone product price',
    ],
    'weberseal-wa100' => [
        'pack' => ['450g/chai', 'Thùng 24 chai'],
        'market_price_map' => [
            '450g/chai' => 26620,
            'Thùng 24 chai' => 638880,
        ],
        'source_page' => 'https://hoahoa.com.vn/san-pham/weberseal-wa-100/',
        'source_note' => 'Hoahoa product price',
    ],
    'weberseal-ws300' => [
        'capacity' => ['300ml/chai'],
        'market_price_map' => [
            '300ml/chai' => 47300,
        ],
        'source_page' => 'https://hoahoa.com.vn/san-pham/weberseal-ws-300/',
        'source_note' => 'Hoahoa product price',
    ],
    'weberseal-ws500' => [
        'capacity' => ['300ml/chai', '600ml/sausage'],
        'market_price_map' => [
            '300ml/chai' => 48400,
            '600ml/sausage' => 96800,
        ],
        'source_page' => 'https://hoahoa.com.vn/san-pham/weberseal-ws-500/',
        'source_note' => 'Hoahoa product price + linear pack scaling',
    ],
];

$image_overrides = [
    'chatchongthamduluxaquatechchongthamvuottroi' => [
        'source_page' => 'https://sonthanhcong.com/chong-tham-dulux-aquatech-pha-xi-mang-c8033/',
    ],
    'chatchongthamduluxaquatechtm3in1' => [
        'source_page' => 'https://tavaco.vn/chat-chong-tham-dulux-aquatech-3in1.html',
    ],
    'duluxambianceairfresh' => [
        'source_page' => 'https://tavaco.vn/son-dulux-ambiance-airfresh-68a.html',
    ],
    'duluxweathershieldcolourprotectbematbong' => [
        'source_page' => 'https://sonthanhcong.com/son-dulux-weathershield-colour-protect/',
    ],
    'duluxweathershieldcolourprotectbematmo' => [
        'source_page' => 'https://tavaco.vn/son-dulux-weathershield-colour-protect-be-mat-mo-e015.html',
    ],
    'duluxweathershieldbematbong' => [
        'source_page' => 'https://tavaco.vn/son-dulux-weathershield-bong-bj9.html',
    ],
    'duluxweathershieldbematmo' => [
        'source_page' => 'https://tavaco.vn/son-dulux-weathershield-mo-bj8-5lit.html',
    ],
    'duluxinspirengoaithatsacmaubenepbematbong' => [
        'source_page' => 'https://tavaco.vn/son-dulux-inspire-ngoai-troi-z98.html',
    ],
    'duluxinspirengoaithatsacmaubenepbematmo' => [
        'source_page' => 'https://tavaco.vn/son-dulux-inspire-ngoai-troi-z98.html',
    ],
    'duluxinspirenoithatsacmaubenepbematbong' => [
        'source_page' => 'https://sonthanhcong.com/son-dulux-inspire-noi-that/',
    ],
    'duluxinspirenoithatsacmaubenepbematmo' => [
        'source_page' => 'https://sonthanhcong.com/son-dulux-inspire-noi-that/',
    ],
    'sonlotnoithatcaocapduluxeasyclean' => [
        'source_page' => 'https://sonthanhcong.com/son-lot-dulux-easyclean-noi-that-a935/',
    ],
    'sonnuocngoaithatmaxilitetoughtudulux_bematbongmo' => [
        'source_page' => 'https://tavaco.vn/son-maxilite-ngoai-troi-tough.html',
    ],
    'sonnuocngoaithatmaxilitetoughtudulux_bematmo' => [
        'source_page' => 'https://tavaco.vn/son-maxilite-ngoai-troi-tough.html',
    ],
    'sonnuocnoithatmaxilitechephuhieuquatudulux' => [
        'source_page' => 'https://sonthanhcong.com/son-maxilite-smooth-kinh-te/',
    ],
    'sonnuocnoithatmaxilitehi-covertudulux' => [
        'source_page' => 'https://sonthanhcong.com/son-maxilite-noi-that-hi-cover/',
    ],
    'sonnuocnoithatmaxilitetotaltuduluxbematbongmo' => [
        'source_page' => 'https://sonthanhcong.com/son-maxilite-noi-that-total/',
    ],
    'sonnuocnoithatmaxilitetotaltuduluxbematmo' => [
        'source_page' => 'https://sonthanhcong.com/son-maxilite-noi-that-total/',
    ],
    'toa-waterproof-201-20kg' => [
        'source_page' => 'https://www.toagroup.com.vn/san-pham-chi-tiet/son-chong-tham-mau-toa-waterblock-color',
        'image_url' => 'https://www.toagroup.com.vn/uploads/product/209cbef29adcc5-ctmaunew.png',
        'allow_upscale' => true,
    ],
];

$stats = [
    'prices_checked' => 0,
    'prices_updated' => 0,
    'prices_skipped' => 0,
    'images_checked' => 0,
    'images_updated' => 0,
    'images_skipped' => 0,
    'errors' => 0,
];

$get_product_by_slug = static function (string $slug): ?WC_Product {
    $slug = sanitize_title($slug);
    if ($slug === '') {
        return null;
    }

    $ids = get_posts([
        'post_type' => 'product',
        'post_status' => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => 1,
        'fields' => 'ids',
        'name' => $slug,
    ]);
    if (empty($ids)) {
        return null;
    }

    $product = wc_get_product((int) $ids[0]);
    return ($product instanceof WC_Product) ? $product : null;
};

$get_attachment_dimensions = static function (int $attachment_id): array {
    if ($attachment_id <= 0) {
        return [0, 0];
    }

    $file_path = (string) get_attached_file($attachment_id);
    if ($file_path !== '' && file_exists($file_path)) {
        $size = @getimagesize($file_path);
        if (is_array($size)) {
            return [(int) ($size[0] ?? 0), (int) ($size[1] ?? 0)];
        }
    }

    $meta = wp_get_attachment_metadata($attachment_id);
    if (is_array($meta)) {
        return [(int) ($meta['width'] ?? 0), (int) ($meta['height'] ?? 0)];
    }

    return [0, 0];
};

$encode_display_price = static function (float $display_price): float {
    $display_price = (float) round($display_price, 0);
    if ($display_price <= 0) {
        return 0.0;
    }

    if (!function_exists('my_theme_global_sale_is_enabled') || !my_theme_global_sale_is_enabled()) {
        return $display_price;
    }

    $percent = function_exists('my_theme_get_global_sale_percent')
        ? (float) my_theme_get_global_sale_percent()
        : 0.0;
    if ($percent <= 0 || $percent >= 100) {
        return $display_price;
    }

    $ratio = (100 - $percent) / 100;
    $start = max((int) $display_price, (int) floor($display_price / $ratio) - 4);
    $end = $start + 24;

    for ($candidate = $start; $candidate <= $end; $candidate++) {
        $computed = function_exists('my_theme_get_global_sale_price_from_regular')
            ? (float) my_theme_get_global_sale_price_from_regular($candidate)
            : (float) round($candidate * $ratio, 0);
        if ((int) round($computed, 0) === (int) $display_price) {
            return (float) $candidate;
        }
    }

    return (float) ceil($display_price / $ratio);
};

$set_pack_labels = static function (WC_Product $product, array $override): void {
    $capacity = array_values(array_unique(array_filter(array_map('trim', (array) ($override['capacity'] ?? [])))));
    $weight = array_values(array_unique(array_filter(array_map('trim', (array) ($override['weight'] ?? [])))));
    $pack = array_values(array_unique(array_filter(array_map('trim', (array) ($override['pack'] ?? [])))));

    if (!empty($capacity) && function_exists('my_theme_sort_pack_labels')) {
        $capacity = my_theme_sort_pack_labels($capacity, 'L');
    }
    if (!empty($weight) && function_exists('my_theme_sort_pack_labels')) {
        $weight = my_theme_sort_pack_labels($weight, 'kg');
    }

    if (!empty($capacity)) {
        $product->update_meta_data('_display_capacity_list', implode(' | ', $capacity));
    } else {
        $product->delete_meta_data('_display_capacity_list');
    }

    if (!empty($weight)) {
        $product->update_meta_data('_display_weight_list', implode(' | ', $weight));
        if (function_exists('my_theme_parse_pack_label')) {
            $first_weight = my_theme_parse_pack_label((string) $weight[0]);
            if (is_array($first_weight) && ($first_weight['unit'] ?? '') === 'kg') {
                $product->set_weight((string) $first_weight['value']);
            }
        }
    } else {
        $product->delete_meta_data('_display_weight_list');
    }

    if (!empty($pack)) {
        $product->update_meta_data('_display_pack_list', implode(' | ', $pack));
    } else {
        $product->delete_meta_data('_display_pack_list');
    }
};

$extract_source_candidate_from_report = static function (array $row): array {
    $resolved_detail = isset($row['resolved_detail']) && is_array($row['resolved_detail']) ? $row['resolved_detail'] : [];
    foreach ($resolved_detail as $detail) {
        if (!is_array($detail) || empty($detail['sources']) || !is_array($detail['sources'])) {
            continue;
        }
        foreach ($detail['sources'] as $source) {
            $url = esc_url_raw((string) ($source['url'] ?? ''));
            if ($url !== '') {
                return [
                    'url' => $url,
                    'title' => trim((string) ($source['title'] ?? '')),
                ];
            }
        }
    }

    $candidates = isset($row['candidates']) && is_array($row['candidates']) ? $row['candidates'] : [];
    foreach ($candidates as $candidate) {
        $url = esc_url_raw((string) ($candidate['url'] ?? ''));
        if ($url !== '') {
            return [
                'url' => $url,
                'title' => trim((string) ($candidate['title'] ?? '')),
            ];
        }
    }

    return [
        'url' => '',
        'title' => '',
    ];
};

$normalize_significant_tokens = static function (string $value): array {
    $value = strtolower(remove_accents(wp_strip_all_tags($value)));
    $value = preg_replace('/[^a-z0-9]+/', ' ', (string) $value);
    $tokens = preg_split('/\s+/', trim((string) $value));
    $ignored = [
        'be',
        'bong',
        'cao',
        'cap',
        'chat',
        'chong',
        'cong',
        'dan',
        'dulux',
        'gach',
        'hang',
        'hieu',
        'kg',
        'lit',
        'litre',
        'lot',
        'mat',
        'maxilite',
        'ml',
        'mo',
        'ngoai',
        'nha',
        'noi',
        'nuoc',
        'san',
        'son',
        'that',
        'thung',
        'toa',
        'tu',
        'tuong',
        'weber',
    ];

    $result = [];
    foreach ((array) $tokens as $token) {
        $token = trim((string) $token);
        if ($token === '' || strlen($token) < 3 || in_array($token, $ignored, true)) {
            continue;
        }
        $result[$token] = $token;
    }

    return array_values($result);
};

$is_report_source_compatible = static function (string $product_name, string $source_title, string $source_url) use ($normalize_significant_tokens): bool {
    $product_tokens = $normalize_significant_tokens($product_name);
    if (empty($product_tokens)) {
        return false;
    }

    $source_tokens = array_values(array_unique(array_merge(
        $normalize_significant_tokens($source_title),
        $normalize_significant_tokens($source_url)
    )));
    if (empty($source_tokens)) {
        return false;
    }

    $shared = array_values(array_intersect($product_tokens, $source_tokens));
    if (count($shared) >= 2) {
        return true;
    }

    $line_tokens = ['airfresh', 'ambiance', 'aquatech', 'easyclean', 'inspire', 'maxilite', 'powersealer', 'royal', 'weathershield'];
    foreach ($shared as $token) {
        if (in_array($token, $line_tokens, true)) {
            return true;
        }
    }

    return false;
};

$looks_like_product_image = static function (string $url): bool {
    $lc = strtolower($url);
    foreach (['logo', 'icon', 'avatar', 'banner', 'facebook.com/tr'] as $blocked) {
        if (strpos($lc, $blocked) !== false) {
            return false;
        }
    }
    return true;
};

$extract_page_image_url = static function (string $source_page) use ($looks_like_product_image): string {
    $source_page = esc_url_raw($source_page);
    if ($source_page === '') {
        return '';
    }

    $response = wp_remote_get($source_page, [
        'timeout' => 30,
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (compatible; CatalogFixBot/1.0)',
        ],
    ]);
    if (is_wp_error($response)) {
        return '';
    }

    $html = (string) wp_remote_retrieve_body($response);
    if ($html === '') {
        return '';
    }

    $patterns = [
        '/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)/i',
        '/<meta[^>]+name=["\']twitter:image["\'][^>]+content=["\']([^"\']+)/i',
        '/data-large_image=["\']([^"\']+)/i',
        '/<img[^>]+class=["\'][^"\']*wp-post-image[^"\']*["\'][^>]+src=["\']([^"\']+)/i',
        '/"image"\s*:\s*\[\s*"([^"]+)/i',
        '/"imageUrl"\s*:\s*"([^"]+)/i',
    ];

    $candidates = [];
    foreach ($patterns as $pattern) {
        if (!preg_match_all($pattern, $html, $matches)) {
            continue;
        }
        foreach ((array) ($matches[1] ?? []) as $match) {
            $url = esc_url_raw(html_entity_decode((string) $match, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($url === '' || !$looks_like_product_image($url)) {
                continue;
            }
            $candidates[$url] = $url;
        }
    }

    foreach ($candidates as $url) {
        return $url;
    }

    return '';
};

$create_upscaled_temp_image = static function (string $source_file) {
    if ($source_file === '' || !file_exists($source_file) || !function_exists('imagecreatefromstring')) {
        return '';
    }

    $contents = @file_get_contents($source_file);
    if (!is_string($contents) || $contents === '') {
        return '';
    }

    $image = @imagecreatefromstring($contents);
    if (!$image) {
        return '';
    }

    $src_w = imagesx($image);
    $src_h = imagesy($image);
    if ($src_w <= 0 || $src_h <= 0) {
        imagedestroy($image);
        return '';
    }

    $canvas_size = 1200;
    $padding = 110;
    $max_w = $canvas_size - ($padding * 2);
    $max_h = $canvas_size - ($padding * 2);
    $scale = min($max_w / $src_w, $max_h / $src_h);
    $dst_w = max(1, (int) round($src_w * $scale));
    $dst_h = max(1, (int) round($src_h * $scale));
    $dst_x = (int) floor(($canvas_size - $dst_w) / 2);
    $dst_y = (int) floor(($canvas_size - $dst_h) / 2);

    $canvas = imagecreatetruecolor($canvas_size, $canvas_size);
    if (!$canvas) {
        imagedestroy($image);
        return '';
    }

    $white = imagecolorallocate($canvas, 255, 255, 255);
    imagefill($canvas, 0, 0, $white);
    imagecopyresampled($canvas, $image, $dst_x, $dst_y, 0, 0, $dst_w, $dst_h, $src_w, $src_h);

    $tmp_path = tempnam(sys_get_temp_dir(), 'catalog-upscale-');
    if (!is_string($tmp_path) || $tmp_path === '') {
        imagedestroy($canvas);
        imagedestroy($image);
        return '';
    }

    @unlink($tmp_path);
    $tmp_path .= '.jpg';
    imagejpeg($canvas, $tmp_path, 92);

    imagedestroy($canvas);
    imagedestroy($image);

    return $tmp_path;
};

$find_cached_attachment_by_remote_url = static function (string $url): int {
    $url = trim($url);
    if ($url === '') {
        return 0;
    }

    $cached = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_key' => '_official_remote_url',
        'meta_value' => $url,
    ]);
    return !empty($cached) ? (int) $cached[0] : 0;
};

$import_remote_image = static function (string $image_url, string $hint_name, bool $allow_upscale = false) use ($find_cached_attachment_by_remote_url, $create_upscaled_temp_image): int {
    $image_url = esc_url_raw($image_url);
    if ($image_url === '') {
        return 0;
    }

    $cached_id = $find_cached_attachment_by_remote_url($image_url);
    if ($cached_id > 0) {
        return $cached_id;
    }

    $tmp = download_url($image_url, 60);
    if (is_wp_error($tmp)) {
        return 0;
    }

    $final_tmp = $tmp;
    if ($allow_upscale) {
        $upscaled = $create_upscaled_temp_image($tmp);
        if (is_string($upscaled) && $upscaled !== '') {
            @unlink($tmp);
            $final_tmp = $upscaled;
        }
    }

    $path = (string) parse_url($image_url, PHP_URL_PATH);
    $filename = sanitize_file_name((string) basename($path));
    if ($filename === '' || strpos($filename, '.') === false) {
        $filename = sanitize_file_name(sanitize_title($hint_name) . '.jpg');
    }
    if ($allow_upscale && strtolower((string) pathinfo($final_tmp, PATHINFO_EXTENSION)) === 'jpg' && strpos($filename, '.') !== false) {
        $filename = preg_replace('/\.[a-z0-9]+$/i', '.jpg', $filename);
    }

    $file_array = [
        'name' => $filename,
        'tmp_name' => $final_tmp,
    ];

    $attach_id = media_handle_sideload($file_array, 0, $hint_name);
    if (is_wp_error($attach_id) || $attach_id <= 0) {
        @unlink($final_tmp);
        return 0;
    }

    update_post_meta((int) $attach_id, '_official_remote_url', $image_url);
    if ($allow_upscale) {
        update_post_meta((int) $attach_id, '_curated_upscaled_source', $image_url);
    }

    return (int) $attach_id;
};

$import_local_upscaled_image = static function (int $attachment_id, string $hint_name) use ($create_upscaled_temp_image): int {
    if ($attachment_id <= 0) {
        return 0;
    }

    $file_path = (string) get_attached_file($attachment_id);
    if ($file_path === '' || !file_exists($file_path)) {
        return 0;
    }

    $upscaled = $create_upscaled_temp_image($file_path);
    if (!is_string($upscaled) || $upscaled === '') {
        return 0;
    }

    $file_array = [
        'name' => sanitize_file_name(sanitize_title($hint_name) . '-upscaled.jpg'),
        'tmp_name' => $upscaled,
    ];
    $attach_id = media_handle_sideload($file_array, 0, $hint_name);
    if (is_wp_error($attach_id) || $attach_id <= 0) {
        @unlink($upscaled);
        return 0;
    }

    update_post_meta((int) $attach_id, '_curated_upscaled_from_attachment', $attachment_id);
    return (int) $attach_id;
};

if (!$skip_prices) {
    foreach ($price_overrides as $slug => $override) {
        $stats['prices_checked']++;

        $product = $get_product_by_slug($slug);
        if (!$product instanceof WC_Product) {
            $stats['errors']++;
            fwrite(STDERR, "Missing product for price override: {$slug}\n");
            continue;
        }

        $market_price_map = isset($override['market_price_map']) && is_array($override['market_price_map'])
            ? $override['market_price_map']
            : [];
        if (empty($market_price_map)) {
            $stats['prices_skipped']++;
            continue;
        }

        $display_price_map = [];
        foreach ($market_price_map as $label => $market_price) {
            $label = trim((string) $label);
            $market_price = (float) $market_price;
            $target_display = max(0.0, round($market_price - 10000, 0));
            if ($label === '' || $target_display <= 0) {
                continue;
            }
            $display_price_map[$label] = $target_display;
        }
        if (empty($display_price_map)) {
            $stats['prices_skipped']++;
            continue;
        }

        $raw_price_map = [];
        foreach ($display_price_map as $label => $target_display) {
            $raw_price_map[$label] = $encode_display_price((float) $target_display);
        }

        if (!$dry_run) {
            if (!$product->meta_exists('_curated_price_backup')) {
                $product->update_meta_data('_curated_price_backup', wp_json_encode([
                    'price' => $product->get_price('edit'),
                    'regular_price' => $product->get_regular_price('edit'),
                    'sale_price' => $product->get_sale_price('edit'),
                    'capacity_price_map' => (string) $product->get_meta('_capacity_price_map', true),
                    'display_capacity_list' => (string) $product->get_meta('_display_capacity_list', true),
                    'display_weight_list' => (string) $product->get_meta('_display_weight_list', true),
                    'display_pack_list' => (string) $product->get_meta('_display_pack_list', true),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }

            $map_parts = [];
            foreach ($raw_price_map as $label => $raw_price) {
                $map_parts[] = $label . ':' . (int) round((float) $raw_price, 0);
            }
            $product->update_meta_data('_capacity_price_map', implode(' | ', $map_parts));
            $set_pack_labels($product, $override);

            $base_price = min(array_map('floatval', array_values($raw_price_map)));
            $product->set_regular_price((string) $base_price);
            $product->set_price((string) $base_price);
            $product->set_sale_price('');

            $source_page = esc_url_raw((string) ($override['source_page'] ?? ''));
            if ($source_page !== '') {
                $product->update_meta_data('_official_source_page', $source_page);
                $product->update_meta_data('_official_source_url', $source_page);
            }

            $product->update_meta_data('_market_price_manual_source', wp_json_encode([
                'applied_at' => gmdate('c'),
                'source_page' => $source_page,
                'source_note' => (string) ($override['source_note'] ?? ''),
                'market_price_map' => $market_price_map,
                'display_price_map' => $display_price_map,
                'raw_price_map' => $raw_price_map,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            $product->save();
            wc_delete_product_transients($product->get_id());
        }

        $stats['prices_updated']++;
        echo 'curated_price'
            . ' slug=' . sanitize_title((string) $product->get_slug())
            . ' dry_run=' . ($dry_run ? 'yes' : 'no')
            . ' map=' . wp_json_encode($display_price_map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . PHP_EOL;
    }
}

if (!$skip_images) {
    $product_ids = function_exists('my_theme_get_catalog_visible_product_ids')
        ? my_theme_get_catalog_visible_product_ids(false)
        : [];

    foreach ((array) $product_ids as $product_id) {
        $product = wc_get_product((int) $product_id);
        if (!$product instanceof WC_Product) {
            continue;
        }

        $stats['images_checked']++;

        $thumb_id = (int) $product->get_image_id();
        [$thumb_w, $thumb_h] = $get_attachment_dimensions($thumb_id);
        $is_low_res = ($thumb_id <= 0 || $thumb_w < 320 || $thumb_h < 320);
        if (!$is_low_res) {
            $stats['images_skipped']++;
            continue;
        }

        $slug = sanitize_title((string) $product->get_slug());
        $source_page = '';
        $image_url = '';
        $allow_upscale = false;

        if (isset($image_overrides[$slug]) && is_array($image_overrides[$slug])) {
            $source_page = esc_url_raw((string) ($image_overrides[$slug]['source_page'] ?? ''));
            $image_url = esc_url_raw((string) ($image_overrides[$slug]['image_url'] ?? ''));
            $allow_upscale = !empty($image_overrides[$slug]['allow_upscale']);
        }

        if ($source_page === '' && isset($market_report[$slug]) && is_array($market_report[$slug])) {
            $source_candidate = $extract_source_candidate_from_report($market_report[$slug]);
            $candidate_url = (string) ($source_candidate['url'] ?? '');
            $candidate_title = (string) ($source_candidate['title'] ?? '');
            if ($candidate_url !== '' && $is_report_source_compatible((string) $product->get_name(), $candidate_title, $candidate_url)) {
                $source_page = $candidate_url;
            }
        }

        if ($image_url === '' && $source_page !== '') {
            $image_url = $extract_page_image_url($source_page);
        }

        $attach_id = 0;
        if ($image_url !== '') {
            $attach_id = $dry_run
                ? 1
                : $import_remote_image($image_url, (string) $product->get_name(), $allow_upscale);
        } elseif ($thumb_id > 0) {
            $attach_id = $dry_run
                ? 1
                : $import_local_upscaled_image($thumb_id, (string) $product->get_name());
            $allow_upscale = true;
        }
        if ($attach_id <= 0) {
            $stats['images_skipped']++;
            fwrite(STDERR, "Missing image source for {$slug}\n");
            continue;
        }

        if (!$dry_run) {
            if ($thumb_id > 0 && !metadata_exists('post', $product->get_id(), '_curated_previous_thumbnail_id')) {
                update_post_meta($product->get_id(), '_curated_previous_thumbnail_id', $thumb_id);
            }

            set_post_thumbnail($product->get_id(), $attach_id);
            update_post_meta($product->get_id(), '_official_source_image', $image_url);
            if ($source_page !== '') {
                update_post_meta($product->get_id(), '_official_source_page', $source_page);
            }
            update_post_meta($product->get_id(), '_curated_image_synced_at', gmdate('c'));

            $alt = trim((string) $product->get_name());
            if ($alt !== '') {
                update_post_meta($attach_id, '_wp_attachment_image_alt', $alt);
                wp_update_post([
                    'ID' => $attach_id,
                    'post_title' => $alt,
                ]);
            }

            wc_delete_product_transients($product->get_id());
        }

        $stats['images_updated']++;
        echo 'curated_image'
            . ' slug=' . $slug
            . ' dry_run=' . ($dry_run ? 'yes' : 'no')
            . ' source_page=' . ($source_page !== '' ? $source_page : '-')
            . ' image_url=' . $image_url
            . PHP_EOL;
    }
}

if (!$dry_run) {
    if (function_exists('my_theme_flush_product_cache_fragments')) {
        my_theme_flush_product_cache_fragments(0);
    }
    update_option('my_theme_filter_cache_version', (string) time(), false);
}

echo 'curated_catalog_fix_done'
    . ' prices_checked=' . (int) $stats['prices_checked']
    . ' prices_updated=' . (int) $stats['prices_updated']
    . ' prices_skipped=' . (int) $stats['prices_skipped']
    . ' images_checked=' . (int) $stats['images_checked']
    . ' images_updated=' . (int) $stats['images_updated']
    . ' images_skipped=' . (int) $stats['images_skipped']
    . ' errors=' . (int) $stats['errors']
    . ' dry_run=' . ($dry_run ? 'yes' : 'no')
    . PHP_EOL;
