<?php
/**
 * Curate catalog naming/taxonomy/source metadata without deleting products.
 *
 * Run:
 *   Get-Content -Raw tools/curate_catalog_products.php | docker exec -i lephat1898-wordpress-1 php
 */

require '/var/www/html/wp-load.php';

if (!function_exists('wc_get_product')) {
    echo "WooCommerce is not loaded.\n";
    exit(1);
}

$taxonomy_brand = '';
foreach (['pa_brand', 'product_brand', 'brand'] as $tax) {
    if (taxonomy_exists($tax)) {
        $taxonomy_brand = $tax;
        break;
    }
}

$taxonomy_line = '';
foreach (['pa_line', 'product_line', 'line'] as $tax) {
    if (taxonomy_exists($tax)) {
        $taxonomy_line = $tax;
        break;
    }
}

$ensure_term = static function (string $term_name, string $taxonomy, string $slug = ''): int {
    if ($taxonomy === '' || !taxonomy_exists($taxonomy)) {
        return 0;
    }

    $slug = sanitize_title($slug !== '' ? $slug : $term_name);
    if ($slug !== '') {
        $exists_by_slug = get_term_by('slug', $slug, $taxonomy);
        if ($exists_by_slug instanceof WP_Term) {
            return (int) $exists_by_slug->term_id;
        }
    }

    $exists = term_exists($term_name, $taxonomy);
    if ($exists) {
        return is_array($exists) ? (int) $exists['term_id'] : (int) $exists;
    }

    $res = wp_insert_term($term_name, $taxonomy, [
        'slug' => $slug,
    ]);
    if (is_wp_error($res)) {
        return 0;
    }
    return (int) $res['term_id'];
};

$brand_label_from_slug = static function (string $slug): string {
    $slug = sanitize_title($slug);
    if ($slug === '') {
        return '';
    }
    if (function_exists('my_theme_get_brand_label_from_slug')) {
        $label = (string) my_theme_get_brand_label_from_slug($slug);
        if ($label !== '') {
            return $label;
        }
    }
    $fallback = [
        'toa' => 'TOA',
        'sika' => 'Sika',
        'weber' => 'Weber',
        'jotun' => 'Jotun',
        'nippon' => 'Nippon',
        'kova' => 'Kova',
        'dulux' => 'Dulux',
        'maxilite' => 'Maxilite',
        'apollo' => 'Apollo',
    ];
    if (isset($fallback[$slug])) {
        return $fallback[$slug];
    }
    return ucfirst($slug);
};

$line_label_from_slug = static function (string $slug): string {
    $slug = sanitize_title($slug);
    if ($slug === '') {
        return '';
    }
    if (function_exists('my_theme_get_line_label_from_slug')) {
        $label = (string) my_theme_get_line_label_from_slug($slug);
        if ($label !== '') {
            return $label;
        }
    }
    return ucwords(str_replace('-', ' ', $slug));
};

$normalize_vi_name = static function (string $name): string {
    $name = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
    if ($name === '') {
        return '';
    }

    $replacements = [
        '/\bSon\b/u' => 'Sơn',
        '/\bson\b/u' => 'sơn',
        '/\bBot\b/u' => 'Bột',
        '/\bbot\b/u' => 'bột',
        '/\bVua\b/u' => 'Vữa',
        '/\bvua\b/u' => 'vữa',
        '/\bChong\b/u' => 'Chống',
        '/\bchong\b/u' => 'chống',
        '/\blot\b/u' => 'lót',
        '/\bngoai\b/u' => 'ngoại',
        '/\bthat\b/u' => 'thất',
        '/\bnoi\b/u' => 'nội',
        '/\btham\b/u' => 'thấm',
        '/\bde\b/u' => 'dễ',
        '/\bchui\b/u' => 'chùi',
        '/\bkim\b/u' => 'kim',
        '/\bloai\b/u' => 'loại',
        '/\bcong\b/u' => 'công',
        '/\bnghiep\b/u' => 'nghiệp',
        '/\btret\b/u' => 'trét',
        '/\bmatit\b/u' => 'ma-tít',
        '/\bdeo\b/u' => 'dẻo',
        '/\bdau\b/u' => 'dầu',
        '/\bsieu\b/u' => 'siêu',
        '/\btrang\b/u' => 'trắng',
        '/\bkhang\b/u' => 'kháng',
        '/\bkiem\b/u' => 'kiềm',
        '/\bdan\b/u' => 'dán',
        '/\bgach\b/u' => 'gạch',
        '/\btram\b/u' => 'trám',
        '/\brot\b/u' => 'rót',
        '/\bsua\b/u' => 'sửa',
        '/\bchua\b/u' => 'chữa',
        '/\bsan\b/u' => 'sàn',
        '/\bthuong\b/u' => 'thượng',
        '/\bben\b/u' => 'bền',
        '/\bmau\b/u' => 'màu',
        '/\bCha Ron\b/u' => 'Chà ron',
        '/\bcha ron\b/u' => 'chà ron',
        '/\bDan Gach\b/u' => 'Dán gạch',
        '/\bdan gach\b/u' => 'dán gạch',
        '/\bSieu Trang\b/u' => 'Siêu Trắng',
        '/\bsieu trang\b/u' => 'siêu trắng',
        '/\bHo Boi\b/u' => 'Hồ Bơi',
        '/\bho boi\b/u' => 'hồ bơi',
    ];

    return (string) preg_replace(array_keys($replacements), array_values($replacements), $name);
};

$name_overrides = [
    // Jotun seed products
    'jotashield-ben-mau-5l' => 'Sơn ngoại thất Jotun Jotashield bền màu',
    'jotashield-clean-extreme-5l' => 'Sơn ngoại thất Jotun Jotashield Clean',
    'jotun-essence-de-lau-chui-5l' => 'Sơn nội thất Jotun Essence dễ lau chùi',
    'jotun-gardex-metal-primer-0-8l' => 'Sơn kim loại Jotun Gardex Metal Primer',
    'jotun-jotamastic-87-20l' => 'Sơn công nghiệp Jotun Jotamastic 87',
    'jotun-jotaplast-noi-that-18l' => 'Sơn nội thất Jotun Jotaplast',
    'jotun-majestic-primer-5l' => 'Sơn lót Jotun Majestic Primer nội thất',
    'jotun-majestic-silk-5l' => 'Sơn nội thất Jotun Majestic Silk',
    'jotun-penguard-primer-5l' => 'Sơn epoxy Jotun Penguard Primer',
    'jotun-waterguard-4l' => 'Sơn chống thấm Jotun WaterGuard',

    // Nippon seed products
    'nippon-bodelac-9000-0-9l' => 'Sơn dầu Nippon Bodelac 9000',
    'nippon-exterior-sealer-5l' => 'Sơn lót Nippon Odour-less Sealer',
    'nippon-hydroshield-5l' => 'Sơn chống thấm Nippon WP-100',
    'nippon-matex-sieu-trang-5l' => 'Sơn nội thất Nippon Matex siêu trắng',
    'nippon-odourless-5l' => 'Sơn nội thất Nippon Odour-less',
    'nippon-odourless-easywash-5l' => 'Sơn nội thất Nippon Odour-less EasyWash',
    'nippon-skim-coat-40kg' => 'Bột trét Nippon Skim Coat',
    'nippon-super-matex-5l' => 'Sơn nội thất Nippon Super Matex',
    'nippon-vinilex-5000-5l' => 'Sơn lót Nippon Vinilex 130 Active Primer',
    'nippon-weatherbond-5l' => 'Sơn ngoại thất Nippon WeatherGard Plus',

    // Kova seed products
    'kova-bot-tret-noi-that-40kg' => 'Bột trét Kova nội thất',
    'kova-ct11a-plus-5l' => 'Sơn chống thấm Kova CT-11A Plus',
    'kova-ct11a-san-thuong-20kg' => 'Sơn chống thấm Kova CT-11A sân thượng',
    'kova-k209-20kg' => 'Sơn lót ngoại thất Kova K-209',
    'kova-k261-son-lot-khang-kiem-20kg' => 'Sơn ngoại thất Kova K-261 Plus',
    'kova-k5501-noi-that-5l' => 'Sơn ngoại thất cao cấp Kova K-5501 Plus',
    'kova-k871-ngoai-that-5l' => 'Sơn nội thất cao cấp Kova K-871',
    'kova-matit-deo-ngoai-that-40kg' => 'Bột trét Kova ma-tít dẻo ngoại thất',
    'kova-son-epoxy-san-cong-nghiep-20kg' => 'Sơn epoxy Kova sàn công nghiệp',
    'kova-son-kim-loai-metal-primer-0-8l' => 'Sơn kim loại Kova Metal Primer',

    // TOA seed products
    'toa-1000-lot-khang-kiem-5l' => 'Sơn lót TOA 1000 kháng kiềm',
    'toa-4seasons-ngoai-that-5l' => 'Sơn ngoại thất TOA 4Seasons',
    'toa-bot-tret-noi-that-40kg' => 'Bột trét TOA nội thất',
    'toa-cong-nghiep-weatherproof-18l' => 'Sơn công nghiệp TOA Weatherproof',
    'toa-epoxy-floor-topcoat-20kg' => 'Sơn epoxy TOA Floor Topcoat',
    'toa-nanoshield-noi-that-5l' => 'Sơn nội thất TOA NanoShield',
    'toa-rust-tech-kim-loai-primer-0-8l' => 'Sơn kim loại TOA Rust Tech Primer',
    'toa-supershield-ngoai-that-ben-mau-5l' => 'Sơn ngoại thất TOA SuperShield bền màu',
    'toa-supershield-noi-that-de-lau-chui-5l' => 'Sơn nội thất TOA SuperShield dễ lau chùi',
    'toa-waterproof-201-20kg' => 'Sơn chống thấm TOA Waterproof 201',

    // Sika seed products
    'sika-monotop-615-25kg' => 'Vữa sửa chữa Sika MonoTop 615 HB',
    'sika-primer-3n-1l' => 'Sơn lót Sika Primer 3N',
    'sikaceram-200-tilefix-25kg' => 'Keo dán gạch SikaCeram 200 TileFix',
    'sikaflex-construction-600ml' => 'Keo trám khe SikaFlex Construction',
    'sikafloor-263-20kg' => 'Sơn epoxy SikaFloor 263',
    'sikafloor-81-epocem-25kg' => 'Sơn công nghiệp SikaFloor 81 EpoCem',
    'sikagrout-214-25kg' => 'Vữa rót SikaGrout 214',
    'sikaguard-905w-5l' => 'Sơn lót SikaGuard 905W',
    'sikalatex-th-5l' => 'Phụ gia SikaLatex TH',
    'sikatop-seal-107-25kg' => 'Chống thấm SikaTop Seal-107',

    // Weber products containing Vietnamese words
    'keo-cha-ron-webercolor-classic' => 'Keo chà ron Webercolor Classic',
    'keo-dan-gach-webertai-fix-40kg' => 'Keo dán gạch Webertai Fix 40kg',
    'keo-dan-gach-webertai-gres-40kg' => 'Keo dán gạch Webertai Gres 40kg',
    'keo-dan-gach-webertai-vis-40kg' => 'Keo dán gạch Webertai Vis 40kg',
    'webercolor-sieu-trang-g68s' => 'Webercolor Siêu Trắng G68S',
    'webercolor-sp-ho-boi' => 'Webercolor SP Hồ Bơi',

    // Existing curated names
    'duluxambiance5in1pearlglow-bongmo' => 'Dulux Ambiance 5in1 Pearl Glow Bề mặt mờ',
    'duluxambiance5in1diamondglow-sieubong' => 'Dulux Ambiance 5in1 Diamond Glow Siêu bóng',
    'duluxambiance5in1superflexx-bongmo' => 'Dulux Ambiance 5in1 Superflexx Bóng mờ',
    'duluxambiance5in1superflexx-sieubong' => 'Dulux Ambiance 5in1 Superflexx Siêu bóng',
    'duluxeasycleanchongbambankhangvirus-bematbong' => 'Dulux EasyClean chống bám bẩn kháng virus bề mặt bóng',
    'sonnuocngoaithatmaxilitetoughtudulux_bematmo' => 'Sơn nước ngoại thất Maxilite Tough từ Dulux bề mặt mờ',
    'sonnuocngoaithatmaxilitetoughtudulux_bematbongmo' => 'Sơn nước ngoại thất Maxilite Tough từ Dulux bề mặt bóng mờ',
    'sonnuocngoaitroimaxiliteultima-bematbong' => 'Sơn nước ngoài trời Maxilite Ultima bề mặt bóng',
    'sonnuocngoaitroimaxiliteultima-bematmo' => 'Sơn nước ngoài trời Maxilite Ultima bề mặt mờ',
];

$brand_overrides = [
    'sonnuocngoaithatmaxilitetoughtudulux_bematmo' => 'maxilite',
    'sonnuocnoithatmaxilitetotaltuduluxbematmo' => 'maxilite',
    'sonnuocnoithatmaxilitechephuhieuquatudulux' => 'maxilite',
    'sonnuocngoaithatmaxilitetoughtudulux_bematbongmo' => 'maxilite',
    'bottrettuongnoingoaithatmaxilitetudulux' => 'maxilite',
    'sonnuocnoithatmaxilitetotaltuduluxbematbongmo' => 'maxilite',
    'sonnuocnoithatmaxilitehi-covertudulux' => 'maxilite',
];

$line_overrides = [
    'jotun-gardex-metal-primer-0-8l' => 'line-metal',
    'jotun-majestic-primer-5l' => 'line-primer',
    'jotun-penguard-primer-5l' => 'line-epoxy',
    'nippon-bodelac-9000-0-9l' => 'line-oil',
    'nippon-exterior-sealer-5l' => 'line-primer',
    'nippon-weatherbond-5l' => 'weatherbond',
];

$source_by_brand_line = [
    'weber' => [
        'webercolor' => 'https://www.vn.weber/vi/webercolor-no-stain',
        'webertai' => 'https://www.vn.weber/vi/keo-dan-gach-webertai-gres',
        'webertec' => 'https://www.vn.weber/vi/webertec-grout-60',
        'weberdry' => 'https://www.vn.weber/vi/weberdry-2kflex',
        'weberseal' => 'https://www.vn.weber/vi/weberseal-ws300',
        'weberprime' => 'https://www.vn.weber/vi/weberprime-spf-11',
        'weberepox' => 'https://www.vn.weber/vi/keo-epoxy',
        'webershield' => 'https://www.vn.weber/vi/webershield-320',
        'line-adhesive' => 'https://www.vn.weber/vi/keo-dan-gach-webertai-vis-40kg',
        'line-waterproof' => 'https://www.vn.weber/vi/weberdry-2kflex',
        'line-primer' => 'https://www.vn.weber/vi/weberprime-spf-11',
    ],
];

$source_by_brand_default = [
    'weber' => 'https://www.vn.weber/vi',
];

$source_by_slug_override = [
    'weberad-latex' => 'https://www.vn.weber/vi/weberad-latex',
    'weberepox-easy' => 'https://www.vn.weber/vi/weberepox-easy',
    'weberprime-epox' => 'https://www.vn.weber/vi/weberprime-epox-094',
    'weberprime-spf' => 'https://www.vn.weber/vi/weberprime-spf-11',
    'weberproof-hdpe' => 'https://www.vn.weber/vi/weberproof-hdpe',
    'weberproof-tpo' => 'https://www.vn.weber/vi/weberproof-tpo',
    'weberseal-wa100' => 'https://www.vn.weber/vi/weberseal-wa100',
    'weberseal-ws300' => 'https://www.vn.weber/vi/weberseal-ws300',
    'weberseal-ws500' => 'https://www.vn.weber/vi/weberseal-ws500',
    'webershield' => 'https://www.vn.weber/vi/webershield-320',
    'webertai-fix' => 'https://www.vn.weber/vi/webertai-fix',
    'webertai-flex' => 'https://www.vn.weber/vi/webertai-flex',
    'webertai-gres' => 'https://www.vn.weber/vi/keo-dan-gach-webertai-gres-40kg',
    'webertai-st250' => 'https://www.vn.weber/vi/webertai-ST250',
    'webertai-vis' => 'https://www.vn.weber/vi/keo-dan-gach-webertai-vis-40kg',
];

$vi_ascii_name_pattern = '/\b(son|bot|keo|vua|chong|noi|ngoai|tham|lot|tret|sieu|trang|khang|kiem|matit|dan|gach|tram|rot|sua|chua|san|thuong|mau|ben|de|chui|kim|loai|cong|nghiep|dau)\b/iu';

$visible_ids = function_exists('my_theme_get_catalog_visible_product_ids')
    ? my_theme_get_catalog_visible_product_ids(false)
    : get_posts([
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ]);

$stats = [
    'total_checked' => 0,
    'name_updated' => 0,
    'brand_updated' => 0,
    'line_updated' => 0,
    'source_updated' => 0,
    'image_alt_updated' => 0,
    'saved_products' => 0,
    'errors' => 0,
];

foreach ((array) $visible_ids as $id) {
    $product = wc_get_product((int) $id);
    if (!$product instanceof WC_Product) {
        continue;
    }

    $stats['total_checked']++;
    $product_id = (int) $product->get_id();
    $slug = sanitize_title((string) $product->get_slug());
    $dirty = false;

    $current_name_raw = (string) $product->get_name();
    $current_name = trim($current_name_raw);
    $target_name = '';

    if ($slug !== '' && isset($name_overrides[$slug])) {
        $target_name = trim((string) $name_overrides[$slug]);
    } elseif ($current_name !== '' && preg_match($vi_ascii_name_pattern, $current_name) === 1) {
        $normalized_name = $normalize_vi_name($current_name);
        if ($normalized_name !== $current_name) {
            $target_name = $normalized_name;
        }
    }

    if ($target_name !== '' && $target_name !== $current_name_raw) {
        $product->set_name($target_name);
        $dirty = true;
        $stats['name_updated']++;
    }

    if ($slug !== '' && isset($brand_overrides[$slug]) && $taxonomy_brand !== '') {
        $brand_slug = sanitize_title((string) $brand_overrides[$slug]);
        if ($brand_slug !== '') {
            $brand_label = $brand_label_from_slug($brand_slug);
            $brand_term_id = $ensure_term($brand_label, $taxonomy_brand, $brand_slug);
            if ($brand_term_id > 0) {
                wp_set_object_terms($product_id, [$brand_term_id], $taxonomy_brand, false);
                $stats['brand_updated']++;
            }
        }
    }

    if ($slug !== '' && isset($line_overrides[$slug]) && $taxonomy_line !== '') {
        $line_slug = sanitize_title((string) $line_overrides[$slug]);
        if ($line_slug !== '') {
            $line_label = $line_label_from_slug($line_slug);
            $line_term_id = $ensure_term($line_label, $taxonomy_line, $line_slug);
            if ($line_term_id > 0) {
                wp_set_object_terms($product_id, [$line_term_id], $taxonomy_line, false);
                $stats['line_updated']++;
            }
        }
    }

    $brand_slug_now = function_exists('my_theme_get_product_brand_slug')
        ? sanitize_title((string) my_theme_get_product_brand_slug($product))
        : '';
    $line_slug_now = function_exists('my_theme_get_product_line_slug')
        ? sanitize_title((string) my_theme_get_product_line_slug($product))
        : '';

    $source_page = trim((string) get_post_meta($product_id, '_official_source_page', true));
    $source_override = ($slug !== '' && isset($source_by_slug_override[$slug])) ? (string) $source_by_slug_override[$slug] : '';
    if ($source_override !== '' && $source_page !== $source_override) {
        update_post_meta($product_id, '_official_source_page', esc_url_raw($source_override));
        update_post_meta($product_id, '_official_source_url', esc_url_raw($source_override));
        $stats['source_updated']++;
        $source_page = $source_override;
    }
    if ($source_page === '') {
        $legacy_source = trim((string) get_post_meta($product_id, '_official_source_url', true));
        if ($legacy_source !== '') {
            update_post_meta($product_id, '_official_source_page', esc_url_raw($legacy_source));
            $stats['source_updated']++;
            $source_page = $legacy_source;
        }
    }
    if ($source_page === '' && $brand_slug_now !== '') {
        $source_candidate = '';
        if (isset($source_by_brand_line[$brand_slug_now][$line_slug_now])) {
            $source_candidate = (string) $source_by_brand_line[$brand_slug_now][$line_slug_now];
        } elseif (isset($source_by_brand_default[$brand_slug_now])) {
            $source_candidate = (string) $source_by_brand_default[$brand_slug_now];
        }
        if ($source_candidate !== '') {
            update_post_meta($product_id, '_official_source_page', esc_url_raw($source_candidate));
            $stats['source_updated']++;
        }
    }

    $thumb_id = (int) get_post_thumbnail_id($product_id);
    if ($thumb_id > 0) {
        $alt = trim((string) get_post_meta($thumb_id, '_wp_attachment_image_alt', true));
        $target_alt = trim((string) $product->get_name());
        if ($target_alt !== '' && $alt !== $target_alt) {
            update_post_meta($thumb_id, '_wp_attachment_image_alt', $target_alt);
            $stats['image_alt_updated']++;
        }
    }

    if ($dirty) {
        $saved_id = $product->save();
        if ($saved_id > 0) {
            $stats['saved_products']++;
        } else {
            $stats['errors']++;
        }
    }
}

if (function_exists('my_theme_flush_product_cache_fragments')) {
    my_theme_flush_product_cache_fragments(0);
}
update_option('my_theme_filter_cache_version', (string) time(), false);

echo 'catalog_curate_done'
    . ' total_checked=' . (int) $stats['total_checked']
    . ' name_updated=' . (int) $stats['name_updated']
    . ' brand_updated=' . (int) $stats['brand_updated']
    . ' line_updated=' . (int) $stats['line_updated']
    . ' source_updated=' . (int) $stats['source_updated']
    . ' image_alt_updated=' . (int) $stats['image_alt_updated']
    . ' saved_products=' . (int) $stats['saved_products']
    . ' errors=' . (int) $stats['errors']
    . PHP_EOL;
