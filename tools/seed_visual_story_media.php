<?php
$bootstrap_paths = [
    __DIR__ . '/../wordpress/wp-load.php',
    dirname(__DIR__) . '/wordpress/wp-load.php',
    '/var/www/html/wp-load.php',
];

$wp_load_path = '';
foreach ($bootstrap_paths as $candidate) {
    if (is_string($candidate) && $candidate !== '' && file_exists($candidate)) {
        $wp_load_path = $candidate;
        break;
    }
}

if ($wp_load_path === '') {
    fwrite(STDERR, "Unable to locate wp-load.php\n");
    exit(1);
}

require $wp_load_path;

if (!function_exists('seed_visual_story_import_attachment')) {
    function seed_visual_story_import_attachment($image_url, $title = '')
    {
        $image_url = esc_url_raw((string) $image_url);
        if ($image_url === '') {
            return 0;
        }

        $existing = get_posts([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_key' => '_my_theme_remote_media_url',
            'meta_value' => $image_url,
        ]);
        if (!empty($existing)) {
            return (int) $existing[0];
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

        $tmp = download_url($image_url, 60);
        if (is_wp_error($tmp)) {
            return 0;
        }

        $path = (string) wp_parse_url($image_url, PHP_URL_PATH);
        $filename = sanitize_file_name((string) wp_basename($path));
        if ($filename === '' || strpos($filename, '.') === false) {
            $filename = sanitize_file_name(($title !== '' ? $title : 'visual-story') . '.jpg');
        }

        $file_array = [
            'name' => $filename,
            'tmp_name' => $tmp,
        ];

        $attachment_id = media_handle_sideload($file_array, 0, $title);
        if (is_wp_error($attachment_id)) {
            @unlink($tmp);
            return 0;
        }

        $attachment_id = (int) $attachment_id;
        update_post_meta($attachment_id, '_my_theme_remote_media_url', $image_url);
        if ($title !== '') {
            update_post_meta($attachment_id, '_wp_attachment_image_alt', $title);
            wp_update_post([
                'ID' => $attachment_id,
                'post_title' => $title,
            ]);
        }

        return $attachment_id;
    }
}

$groups = [
    'interior' => [
        [
            'image_url' => 'https://images.akzonobel.com/akzonobel-flourish/dulux/vn/vi/articles-import/chon-mau-son-nao-cho-phong-khach-hien-dai/how_to_choose_colour_for_your_living_room.jpeg?impolicy=.auto&imwidth=1366',
            'title' => 'Không gian phòng khách hiện đại',
            'caption' => 'Không gian phòng khách sáng, gọn và phù hợp với nhóm sơn nội thất dễ lau chùi cho nhà ở.',
            'source_label' => 'Dulux Việt Nam',
            'source_url' => 'https://www.dulux.vn/vi/y-tuong/chon-mau-son-nao-cho-phong-khach-hien-dai',
            'license' => 'Nguồn hãng',
        ],
        [
            'image_url' => 'https://images.akzonobel.com/akzonobel-flourish/dulux/vn/vi/articles-import/dich-vu-phoi-mau-nha-online/2.banner-body-dulux.jpg?impolicy=.auto&imwidth=1366',
            'title' => 'Không gian nội thất phối màu thực tế',
            'caption' => 'Ảnh minh họa không gian nội thất cần phối màu và chọn hệ lót, phủ theo từng khu vực sử dụng.',
            'source_label' => 'Dulux Việt Nam',
            'source_url' => 'https://www.dulux.vn/vi/y-tuong/dich-vu-phoi-mau-nha-online',
            'license' => 'Nguồn hãng',
        ],
    ],
    'exterior' => [
        [
            'image_url' => 'https://www.jotun.com/contentassetsjot03/3caeb69b0e06468280ff5324a972a163/outdoor-inspiration_1408x800.jpg',
            'title' => 'Mặt tiền ngoài trời',
            'caption' => 'Mặt tiền ngoài trời cần lớp sơn bền màu, ổn định dưới nắng mưa và ít bám bẩn hơn theo thời gian.',
            'source_label' => 'Jotun Vietnam',
            'source_url' => 'https://www.jotun.com/vn-vi/decorative/inspiration/outdoor',
            'license' => 'Nguồn hãng',
        ],
        [
            'image_url' => 'https://images.akzonobel.com/akzonobel-flourish/dulux/vn/vi/articles-import/dich-vu-phoi-mau-nha-online/1.dulux-preview-topbanner.jpg?impolicy=.auto&imwidth=1366',
            'title' => 'Phối màu mặt tiền và ngoại thất',
            'caption' => 'Ảnh minh họa bề mặt ngoài trời cần phối màu và chọn đúng hệ lót, phủ để giữ màu ổn định lâu hơn.',
            'source_label' => 'Dulux Việt Nam',
            'source_url' => 'https://www.dulux.vn/vi/y-tuong/dich-vu-phoi-mau-nha-online',
            'license' => 'Nguồn hãng',
        ],
    ],
    'waterproofing' => [
        [
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/6d/Construction_Workers_on_Roof_-_Near_Bac_Ha_-_Lao_Cai_Province_-_Vietnam_%2848203385447%29.jpg/1920px-Construction_Workers_on_Roof_-_Near_Bac_Ha_-_Lao_Cai_Province_-_Vietnam_%2848203385447%29.jpg',
            'title' => 'Thi công mái và sân thượng',
            'caption' => 'Ảnh minh họa công trình mái/sân thượng đang xử lý bề mặt trước khi chốt hệ chống thấm.',
            'source_label' => 'Wikimedia Commons',
            'source_url' => 'https://commons.wikimedia.org/wiki/File:Construction_Workers_on_Roof_-_Near_Bac_Ha_-_Lao_Cai_Province_-_Vietnam_(48203385447).jpg',
            'license' => 'CC image',
        ],
        [
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Concrete_roof_insulation_in_Haikou%2C_Hainan%2C_China_-_01.JPG/1920px-Concrete_roof_insulation_in_Haikou%2C_Hainan%2C_China_-_01.JPG',
            'title' => 'Bề mặt mái bê tông ngoài trời',
            'caption' => 'Bề mặt mái bê tông ngoài trời cần xử lý kỹ ở các mối nối, vùng đọng nước và lớp nền trước khi thi công.',
            'source_label' => 'Wikimedia Commons',
            'source_url' => 'https://commons.wikimedia.org/wiki/File:Concrete_roof_insulation_in_Haikou,_Hainan,_China_-_01.JPG',
            'license' => 'CC image',
        ],
    ],
    'epoxy' => [
        [
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/6b/Warehouse_Concrete_Floor_preparation_done_by_Platinum_Constru%C3%A7%C3%B5es.jpg/1920px-Warehouse_Concrete_Floor_preparation_done_by_Platinum_Constru%C3%A7%C3%B5es.jpg',
            'title' => 'Chuẩn bị nền sàn công nghiệp',
            'caption' => 'Ảnh minh họa nền sàn kho/xưởng đang xử lý trước khi thi công hệ phủ epoxy.',
            'source_label' => 'Wikimedia Commons',
            'source_url' => 'https://commons.wikimedia.org/wiki/File:Warehouse_Concrete_Floor_preparation_done_by_Platinum_Constru%C3%A7%C3%B5es.jpg',
            'license' => 'CC image',
        ],
        [
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8a/Parking_garage_at_level_K2_of_Redi_in_August_2024.jpg/1920px-Parking_garage_at_level_K2_of_Redi_in_August_2024.jpg',
            'title' => 'Sàn gara và khu để xe',
            'caption' => 'Không gian gara/sàn kỹ thuật là nhóm bề mặt hay cần hệ epoxy để dễ vệ sinh và tăng độ bền mặt sàn.',
            'source_label' => 'Wikimedia Commons',
            'source_url' => 'https://commons.wikimedia.org/wiki/File:Parking_garage_at_level_K2_of_Redi_in_August_2024.jpg',
            'license' => 'CC image',
        ],
    ],
    'metal' => [
        [
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/9/96/Railings_and_gate_at_Newdigate_House_-_geograph.org.uk_-_6115428.jpg',
            'title' => 'Lan can và cổng sắt ngoài trời',
            'caption' => 'Lan can, cổng và chi tiết kim loại ngoài trời cần lớp chống rỉ đúng hệ trước khi phủ màu hoàn thiện.',
            'source_label' => 'Wikimedia Commons',
            'source_url' => 'https://commons.wikimedia.org/wiki/File:Railings_and_gate_at_Newdigate_House_-_geograph.org.uk_-_6115428.jpg',
            'license' => 'CC image',
        ],
        [
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/df/Metal_door_of_an_old_house.jpg/1920px-Metal_door_of_an_old_house.jpg',
            'title' => 'Cửa sắt và bề mặt kim loại',
            'caption' => 'Bề mặt cửa sắt cũ là nhóm hay cần xử lý rỉ, vệ sinh nền và sơn chống rỉ trước khi sơn phủ màu.',
            'source_label' => 'Wikimedia Commons',
            'source_url' => 'https://commons.wikimedia.org/wiki/File:Metal_door_of_an_old_house.jpg',
            'license' => 'CC image',
        ],
    ],
    'grout' => [
        [
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4b/Mid-City_New_Orleans_Bathroom_-_Fully_Grouted.jpg/1920px-Mid-City_New_Orleans_Bathroom_-_Fully_Grouted.jpg',
            'title' => 'Nhà tắm đã chà ron hoàn thiện',
            'caption' => 'Ảnh minh họa khu nhà tắm đã hoàn thiện ron gạch, phù hợp cho bài tư vấn chọn ron và chống bám bẩn.',
            'source_label' => 'Wikimedia Commons',
            'source_url' => 'https://commons.wikimedia.org/wiki/File:Mid-City_New_Orleans_Bathroom_-_Fully_Grouted.jpg',
            'license' => 'CC image',
        ],
        [
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4c/Bathroom_tile%2C_Coleton_Fishacre_-_geograph.org.uk_-_5719032.jpg/1920px-Bathroom_tile%2C_Coleton_Fishacre_-_geograph.org.uk_-_5719032.jpg',
            'title' => 'Bề mặt gạch và mạch ron',
            'caption' => 'Ảnh minh họa bề mặt gạch và mạch ron trong khu ẩm, dùng để hình dung lựa chọn keo chà ron và chống bám bẩn.',
            'source_label' => 'Wikimedia Commons',
            'source_url' => 'https://commons.wikimedia.org/wiki/File:Bathroom_tile,_Coleton_Fishacre_-_geograph.org.uk_-_5719032.jpg',
            'license' => 'CC image',
        ],
    ],
];

$bank = [
    'version' => '20260303-visual-story-v1',
    'groups' => [],
];

foreach ($groups as $group_key => $items) {
    $bank['groups'][$group_key] = [];
    foreach ($items as $item) {
        if (!is_array($item) || empty($item['image_url'])) {
            continue;
        }

        $attachment_id = seed_visual_story_import_attachment((string) $item['image_url'], (string) ($item['title'] ?? 'Hình minh họa'));
        if ($attachment_id <= 0) {
            continue;
        }

        update_post_meta($attachment_id, '_my_theme_visual_source_url', (string) ($item['source_url'] ?? ''));
        update_post_meta($attachment_id, '_my_theme_visual_source_label', (string) ($item['source_label'] ?? ''));
        update_post_meta($attachment_id, '_my_theme_visual_license', (string) ($item['license'] ?? ''));

        $bank['groups'][$group_key][] = [
            'attachment_id' => $attachment_id,
            'caption' => (string) ($item['caption'] ?? ''),
            'source_label' => (string) ($item['source_label'] ?? ''),
            'source_url' => (string) ($item['source_url'] ?? ''),
            'license' => (string) ($item['license'] ?? ''),
        ];
    }
}

update_option('my_theme_visual_story_bank_v1', $bank, false);

$post_groups = [
    'cach-chon-son-noi-that-de-lau-chui-cho-nha-o' => 'interior',
    'cach-chon-son-ngoai-that-ben-mau-cho-mat-tien' => 'exterior',
    'chong-tham-san-thuong-nen-dung-he-nao' => 'waterproofing',
    'cach-chon-keo-cha-ron-cho-nha-tam-va-bep' => 'grout',
    'cach-chon-son-chong-ri-cho-cua-sat-va-lan-can' => 'metal',
    'khi-nao-can-son-lot-khang-kiem' => 'interior',
    'bot-tret-noi-that-va-ngoai-that-khac-nhau-o-dau' => 'interior',
    'cach-chon-son-epoxy-cho-san-nha-xuong-nho' => 'epoxy',
    'so-sanh-dulux-jotun-nippon-cho-nhu-cau-pho-thong' => 'interior',
    'chong-tham-tuong-ngoai-troi-khi-da-tham-nuoc' => 'waterproofing',
];

foreach ($post_groups as $slug => $group_key) {
    $post = get_page_by_path($slug, OBJECT, 'post');
    if (!($post instanceof WP_Post)) {
        continue;
    }

    update_post_meta($post->ID, '_my_theme_visual_group', $group_key);

    $group_items = isset($bank['groups'][$group_key]) && is_array($bank['groups'][$group_key]) ? $bank['groups'][$group_key] : [];
    if (!empty($group_items)) {
        $first_attachment = isset($group_items[0]['attachment_id']) ? (int) $group_items[0]['attachment_id'] : 0;
        if ($first_attachment > 0) {
            set_post_thumbnail($post->ID, $first_attachment);
        }
    }
}

echo 'Visual story bank updated: ' . count($bank['groups']) . " groups\n";
foreach ($bank['groups'] as $group_key => $items) {
    echo $group_key . ': ' . count($items) . " images\n";
}
