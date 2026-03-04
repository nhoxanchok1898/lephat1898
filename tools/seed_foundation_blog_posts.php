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

if (!function_exists('seed_foundation_blog_posts_get_post_link')) {
    function seed_foundation_blog_posts_get_post_link($slug, $post_type = 'product', $fallback = '')
    {
        $post = get_page_by_path((string) $slug, OBJECT, (string) $post_type);
        if ($post instanceof WP_Post) {
            return get_permalink($post);
        }

        return $fallback;
    }
}

if (!function_exists('seed_foundation_blog_posts_get_thumbnail_id')) {
    function seed_foundation_blog_posts_get_thumbnail_id($slug, $post_type = 'product')
    {
        $post = get_page_by_path((string) $slug, OBJECT, (string) $post_type);
        if (!($post instanceof WP_Post)) {
            return 0;
        }

        return (int) get_post_thumbnail_id($post->ID);
    }
}

if (!function_exists('seed_foundation_blog_posts_ensure_category')) {
    function seed_foundation_blog_posts_ensure_category($name, $slug)
    {
        $existing = get_term_by('slug', (string) $slug, 'category');
        if ($existing instanceof WP_Term) {
            return (int) $existing->term_id;
        }

        $created = wp_insert_term((string) $name, 'category', [
            'slug' => (string) $slug,
        ]);

        if (is_wp_error($created) || empty($created['term_id'])) {
            return 0;
        }

        return (int) $created['term_id'];
    }
}

$site_name = get_bloginfo('name');
$contact_url = home_url('/lien-he/');
$guide_url = home_url('/huong-dan-mua-hang/');
$faq_url = home_url('/faq/');
$calculator_url = trailingslashit(home_url('/')) . '#tinh-son';

$category_ids = [
    'son-noi-that' => seed_foundation_blog_posts_ensure_category('Sơn nội thất', 'son-noi-that'),
    'son-ngoai-that' => seed_foundation_blog_posts_ensure_category('Sơn ngoại thất', 'son-ngoai-that'),
    'chong-tham' => seed_foundation_blog_posts_ensure_category('Chống thấm', 'chong-tham'),
    'keo-va-phu-gia' => seed_foundation_blog_posts_ensure_category('Keo và phụ gia', 'keo-va-phu-gia'),
    'son-kim-loai' => seed_foundation_blog_posts_ensure_category('Sơn kim loại', 'son-kim-loai'),
    'son-lot' => seed_foundation_blog_posts_ensure_category('Sơn lót', 'son-lot'),
    'bot-tret' => seed_foundation_blog_posts_ensure_category('Bột trét', 'bot-tret'),
    'son-epoxy' => seed_foundation_blog_posts_ensure_category('Sơn epoxy', 'son-epoxy'),
];

$products = [
    'dulux_easyclean_bong' => [
        'url' => seed_foundation_blog_posts_get_post_link('duluxeasycleanlauchuihieuquabematbong', 'product', home_url('/shop/?brand=dulux')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('duluxeasycleanlauchuihieuquabematbong'),
    ],
    'jotun_majestic_silk' => [
        'url' => seed_foundation_blog_posts_get_post_link('jotun-majestic-silk-5l', 'product', home_url('/shop/?brand=jotun')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('jotun-majestic-silk-5l'),
    ],
    'nippon_odourless' => [
        'url' => seed_foundation_blog_posts_get_post_link('nippon-odourless-5l', 'product', home_url('/shop/?brand=nippon')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('nippon-odourless-5l'),
    ],
    'maxilite_total' => [
        'url' => seed_foundation_blog_posts_get_post_link('sonnuocnoithatmaxilitetotaltuduluxbematmo', 'product', home_url('/shop/?brand=maxilite')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('sonnuocnoithatmaxilitetotaltuduluxbematmo'),
    ],
    'dulux_weathershield' => [
        'url' => seed_foundation_blog_posts_get_post_link('duluxweathershieldbematmo', 'product', home_url('/shop/?brand=dulux')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('duluxweathershieldbematmo'),
    ],
    'jotashield' => [
        'url' => seed_foundation_blog_posts_get_post_link('jotashield-ben-mau-5l', 'product', home_url('/shop/?brand=jotun')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('jotashield-ben-mau-5l'),
    ],
    'nippon_weathergard' => [
        'url' => seed_foundation_blog_posts_get_post_link('nippon-weatherbond-5l', 'product', home_url('/shop/?brand=nippon')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('nippon-weatherbond-5l'),
    ],
    'kova_k261' => [
        'url' => seed_foundation_blog_posts_get_post_link('kova-k261-son-lot-khang-kiem-20kg', 'product', home_url('/shop/?brand=kova')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('kova-k261-son-lot-khang-kiem-20kg'),
    ],
    'sikatop_107' => [
        'url' => seed_foundation_blog_posts_get_post_link('sikatop-seal-107-25kg', 'product', home_url('/shop/?brand=sika')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('sikatop-seal-107-25kg'),
    ],
    'weberdry_2kflex' => [
        'url' => seed_foundation_blog_posts_get_post_link('weberdry-2kflex', 'product', home_url('/shop/?brand=weber')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('weberdry-2kflex'),
    ],
    'aquatech_max' => [
        'url' => seed_foundation_blog_posts_get_post_link('chongthamsanduluxaquatechmax', 'product', home_url('/shop/?brand=dulux')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('chongthamsanduluxaquatechmax'),
    ],
    'kova_ct11a' => [
        'url' => seed_foundation_blog_posts_get_post_link('kova-ct11a-san-thuong-20kg', 'product', home_url('/shop/?brand=kova')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('kova-ct11a-san-thuong-20kg'),
    ],
    'webercolor_no_stain' => [
        'url' => seed_foundation_blog_posts_get_post_link('webercolor-no-stain', 'product', home_url('/shop/?brand=weber')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('webercolor-no-stain'),
    ],
    'webercolor_classic' => [
        'url' => seed_foundation_blog_posts_get_post_link('keo-cha-ron-webercolor-classic', 'product', home_url('/shop/?brand=weber')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('keo-cha-ron-webercolor-classic'),
    ],
    'webercolor_slim' => [
        'url' => seed_foundation_blog_posts_get_post_link('webercolor-slim', 'product', home_url('/shop/?brand=weber')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('webercolor-slim'),
    ],
    'jotun_gardex' => [
        'url' => seed_foundation_blog_posts_get_post_link('jotun-gardex-metal-primer-0-8l', 'product', home_url('/shop/?brand=jotun')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('jotun-gardex-metal-primer-0-8l'),
    ],
    'kova_metal_primer' => [
        'url' => seed_foundation_blog_posts_get_post_link('kova-son-kim-loai-metal-primer-0-8l', 'product', home_url('/shop/?brand=kova')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('kova-son-kim-loai-metal-primer-0-8l'),
    ],
    'toa_rust_tech' => [
        'url' => seed_foundation_blog_posts_get_post_link('toa-rust-tech-kim-loai-primer-0-8l', 'product', home_url('/shop/?brand=toa')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('toa-rust-tech-kim-loai-primer-0-8l'),
    ],
    'dulux_primer' => [
        'url' => seed_foundation_blog_posts_get_post_link('sonlotcaocapdulux', 'product', home_url('/shop/?brand=dulux')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('sonlotcaocapdulux'),
    ],
    'jotun_majestic_primer' => [
        'url' => seed_foundation_blog_posts_get_post_link('jotun-majestic-primer-5l', 'product', home_url('/shop/?brand=jotun')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('jotun-majestic-primer-5l'),
    ],
    'nippon_sealer' => [
        'url' => seed_foundation_blog_posts_get_post_link('nippon-exterior-sealer-5l', 'product', home_url('/shop/?brand=nippon')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('nippon-exterior-sealer-5l'),
    ],
    'toa_1000' => [
        'url' => seed_foundation_blog_posts_get_post_link('toa-1000-lot-khang-kiem-5l', 'product', home_url('/shop/?brand=toa')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('toa-1000-lot-khang-kiem-5l'),
    ],
    'dulux_putty' => [
        'url' => seed_foundation_blog_posts_get_post_link('bottretcaocapdulux', 'product', home_url('/shop/?brand=dulux')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('bottretcaocapdulux'),
    ],
    'kova_putty_interior' => [
        'url' => seed_foundation_blog_posts_get_post_link('kova-bot-tret-noi-that-40kg', 'product', home_url('/shop/?brand=kova')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('kova-bot-tret-noi-that-40kg'),
    ],
    'kova_putty_exterior' => [
        'url' => seed_foundation_blog_posts_get_post_link('kova-matit-deo-ngoai-that-40kg', 'product', home_url('/shop/?brand=kova')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('kova-matit-deo-ngoai-that-40kg'),
    ],
    'maxilite_putty' => [
        'url' => seed_foundation_blog_posts_get_post_link('bottrettuongnoingoaithatmaxilitetudulux', 'product', home_url('/shop/?brand=maxilite')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('bottrettuongnoingoaithatmaxilitetudulux'),
    ],
    'sikafloor_263' => [
        'url' => seed_foundation_blog_posts_get_post_link('sikafloor-263-20kg', 'product', home_url('/shop/?brand=sika')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('sikafloor-263-20kg'),
    ],
    'toa_epoxy_topcoat' => [
        'url' => seed_foundation_blog_posts_get_post_link('toa-epoxy-floor-topcoat-20kg', 'product', home_url('/shop/?brand=toa')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('toa-epoxy-floor-topcoat-20kg'),
    ],
    'kova_epoxy' => [
        'url' => seed_foundation_blog_posts_get_post_link('kova-son-epoxy-san-cong-nghiep-20kg', 'product', home_url('/shop/?brand=kova')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('kova-son-epoxy-san-cong-nghiep-20kg'),
    ],
    'weberepox_easy' => [
        'url' => seed_foundation_blog_posts_get_post_link('weberepox-easy', 'product', home_url('/shop/?brand=weber')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('weberepox-easy'),
    ],
    'dulux_wall_waterproof' => [
        'url' => seed_foundation_blog_posts_get_post_link('duluxweathershieldchatchongtham', 'product', home_url('/shop/?brand=dulux')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('duluxweathershieldchatchongtham'),
    ],
    'aquatech_flex' => [
        'url' => seed_foundation_blog_posts_get_post_link('chatchongthamduluxaquatechflexwaterproofing', 'product', home_url('/shop/?brand=dulux')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('chatchongthamduluxaquatechflexwaterproofing'),
    ],
    'kova_ct11a_plus' => [
        'url' => seed_foundation_blog_posts_get_post_link('kova-ct11a-plus-5l', 'product', home_url('/shop/?brand=kova')),
        'thumb_id' => seed_foundation_blog_posts_get_thumbnail_id('kova-ct11a-plus-5l'),
    ],
];

$posts = [
    [
        'slug' => 'cach-chon-son-noi-that-de-lau-chui-cho-nha-o',
        'title' => 'Cách chọn sơn nội thất dễ lau chùi cho nhà ở',
        'excerpt' => 'Gợi ý cách chọn sơn nội thất dễ lau chùi theo phòng, mức sử dụng và ngân sách để chốt mã sơn gọn hơn.',
        'category_ids' => [$category_ids['son-noi-that']],
        'thumbnail_id' => $products['dulux_easyclean_bong']['thumb_id'],
        'date' => '2026-02-24 08:30:00',
        'content' => <<<HTML
<p>Khi sơn lại nhà ở, nhu cầu phổ biến nhất là bề mặt phải sạch lâu, dễ chùi vết tay và nhìn vẫn sáng sau một thời gian sử dụng. Nếu chọn đúng nhóm sơn ngay từ đầu, bạn sẽ tránh được tình trạng tường nhanh bám bẩn, lau xong bị bóng loang hoặc phải sơn lại sớm.</p>
<h2>1. Khu vực nào nên ưu tiên sơn dễ lau chùi</h2>
<p>Phòng khách, hành lang, khu vực trẻ nhỏ, khu ăn uống và những mảng tường gần cầu thang là nơi nên ưu tiên nhóm sơn có khả năng lau chùi tốt. Phòng ngủ ít va chạm hơn có thể dùng dòng mờ để nhìn dịu hơn, nhưng vẫn nên chọn loại có màng sơn đủ chắc.</p>
<h2>2. Nên chọn mờ, bán bóng hay bóng</h2>
<ul>
<li><strong>Bề mặt mờ:</strong> dịu mắt, dễ hợp nhiều kiểu nhà, che khuyết điểm tường tốt hơn.</li>
<li><strong>Bề mặt bóng:</strong> lau chùi mạnh hơn, nhìn sáng hơn nhưng bề mặt tường phải xử lý phẳng hơn.</li>
<li><strong>Bán bóng hoặc satin:</strong> cân bằng giữa độ đẹp và khả năng vệ sinh nếu gia đình dùng thường xuyên.</li>
</ul>
<h2>3. Chọn theo ngân sách và nhu cầu sử dụng</h2>
<p>Nếu ưu tiên độ lau chùi và cảm giác hoàn thiện tốt, có thể bắt đầu từ <a href="{$products['dulux_easyclean_bong']['url']}">Dulux EasyClean bề mặt bóng</a> hoặc <a href="{$products['jotun_majestic_silk']['url']}">Jotun Majestic Silk</a>. Với nhu cầu tiết kiệm hơn nhưng vẫn cần bề mặt sạch sẽ, có thể tham khảo <a href="{$products['nippon_odourless']['url']}">Nippon Odour-less</a> hoặc <a href="{$products['maxilite_total']['url']}">Maxilite Total nội thất</a>.</p>
<h2>4. Đừng bỏ qua lớp lót và xử lý bề mặt</h2>
<p>Sơn phủ tốt nhưng tường còn bụi, ẩm hoặc chưa ổn định độ kiềm thì vẫn nhanh xuống cấp. Với nhà mới hoặc tường đã sửa vá, nên kiểm tra lại bột trét và sơn lót trước khi chọn màu hoàn thiện. Nếu chưa chắc định mức vật tư, có thể dùng ngay <a href="{$calculator_url}">công cụ tính sơn theo m2</a> để ước lượng số lít/xô cần dùng.</p>
<h2>5. Kịch bản chọn nhanh cho khách gia đình</h2>
<ul>
<li>Nhà có trẻ nhỏ, tường hay bám bẩn: ưu tiên dòng lau chùi tốt.</li>
<li>Phòng ngủ cần dịu mắt: chọn bề mặt mờ nhưng vẫn thuộc nhóm nội thất cao hơn.</li>
<li>Nhà cần tối ưu chi phí: chọn hệ lót + phủ phổ thông nhưng giữ đúng quy trình thi công.</li>
</ul>
<p>Nếu cần chốt nhanh theo diện tích và màu sắc, bạn có thể xem thêm <a href="{$guide_url}">hướng dẫn mua hàng</a> hoặc gửi nhu cầu qua <a href="{$contact_url}">trang liên hệ của {$site_name}</a> để được gợi ý đúng dòng sơn theo từng phòng.</p>
HTML,
    ],
    [
        'slug' => 'cach-chon-son-ngoai-that-ben-mau-cho-mat-tien',
        'title' => 'Cách chọn sơn ngoại thất bền màu cho mặt tiền',
        'excerpt' => 'Tổng hợp cách chọn sơn ngoại thất bền màu, chống bám bụi và phù hợp mặt tiền nhà phố.',
        'category_ids' => [$category_ids['son-ngoai-that']],
        'thumbnail_id' => $products['dulux_weathershield']['thumb_id'],
        'date' => '2026-02-25 09:15:00',
        'content' => <<<HTML
<p>Mặt tiền là khu vực chịu nắng, mưa, bụi và thay đổi nhiệt độ mạnh nhất. Vì vậy, chọn sơn ngoại thất không nên nhìn riêng màu sắc mà phải xét thêm độ bền màng sơn, khả năng chống bám bụi và tình trạng bề mặt thực tế.</p>
<h2>1. Vì sao mặt tiền xuống màu nhanh</h2>
<p>Tường ngoài trời thường xuống màu do bề mặt cũ chưa xử lý hết phấn hóa, bỏ qua lớp lót kháng kiềm hoặc chọn dòng sơn không phù hợp với nắng gắt và mưa hắt. Với nhà phố mặt tiền hẹp, mảng tường bị hắt nước nhiều cũng dễ sinh rêu mốc và loang màu hơn.</p>
<h2>2. Tiêu chí chọn sơn ngoại thất</h2>
<ul>
<li>Giữ màu ổn định dưới nắng gắt.</li>
<li>Màng sơn đủ chắc để hạn chế bám bụi và rêu mốc.</li>
<li>Phù hợp với bề mặt mới hoặc tường cũ đã sơn lại.</li>
<li>Có hệ lót và phủ đi cùng để thi công đồng bộ.</li>
</ul>
<h2>3. Một số dòng dễ tham khảo</h2>
<p>Nhóm phổ biến cho nhà ở có thể bắt đầu từ <a href="{$products['dulux_weathershield']['url']}">Dulux Weathershield</a>, <a href="{$products['jotashield']['url']}">Jotashield bền màu</a> hoặc <a href="{$products['nippon_weathergard']['url']}">Nippon WeatherGard</a>. Nếu cần thêm lớp lót ổn định nền tường, có thể tham khảo <a href="{$products['kova_k261']['url']}">Kova K-261 Plus</a> hoặc hệ lót phù hợp của từng hãng.</p>
<h2>4. Khi nào cần chống bám bụi và chống rêu mốc mạnh hơn</h2>
<p>Nhà gần đường lớn, khu nhiều bụi, tường hướng tây hoặc khu vực hay bị nước mưa tạt nên ưu tiên nhóm ngoại thất cao hơn. Trường hợp tường cũ có dấu hiệu thấm, nên xử lý chống thấm trước rồi mới sơn lại để tránh màng sơn phủ bị phá từ bên trong.</p>
<h2>5. Nên chốt hệ sơn theo bộ</h2>
<p>Thay vì chỉ chọn một mã phủ, nên chốt theo bộ gồm xử lý bề mặt, lớp lót và lớp phủ. Cách này giúp báo giá sát hơn và hạn chế phát sinh vật tư giữa chừng. Nếu cần xem thêm cách lên đơn và thời gian giao, bạn có thể mở <a href="{$faq_url}">FAQ</a> hoặc gửi yêu cầu tại <a href="{$contact_url}">trang liên hệ</a> để nhận báo giá theo diện tích mặt tiền.</p>
HTML,
    ],
    [
        'slug' => 'chong-tham-san-thuong-nen-dung-he-nao',
        'title' => 'Chống thấm sân thượng nên dùng hệ nào',
        'excerpt' => 'Cách chọn hệ chống thấm sân thượng theo hiện trạng nứt, đọng nước và bề mặt cũ để tránh làm lại nhiều lần.',
        'category_ids' => [$category_ids['chong-tham']],
        'thumbnail_id' => $products['sikatop_107']['thumb_id'],
        'date' => '2026-02-26 08:45:00',
        'content' => <<<HTML
<p>Sân thượng là khu vực dễ phát sinh thấm nhất vì chịu nắng gắt, mưa trực tiếp và đọng nước cục bộ. Chọn đúng hệ chống thấm cần dựa trên hiện trạng bề mặt thay vì chỉ chọn theo thương hiệu.</p>
<h2>1. Dấu hiệu cần xử lý sớm</h2>
<ul>
<li>Trần tầng dưới xuất hiện ố vàng hoặc bong sơn.</li>
<li>Bề mặt sân thượng có rạn nứt chân chim hoặc nứt mép tường.</li>
<li>Nước đọng lâu sau mưa hoặc sau khi xịt rửa.</li>
</ul>
<h2>2. Chọn theo tình trạng bề mặt</h2>
<p>Với bề mặt bê tông ổn định và cần hệ gốc xi măng hai thành phần, có thể tham khảo <a href="{$products['sikatop_107']['url']}">SikaTop Seal-107</a> hoặc <a href="{$products['weberdry_2kflex']['url']}">Weberdry 2KFlex</a>. Nếu cần nhóm chống thấm dễ thi công cho sàn hoặc tường ngoài, <a href="{$products['aquatech_max']['url']}">Dulux Aquatech Max</a> và <a href="{$products['kova_ct11a']['url']}">Kova CT-11A</a> cũng là những mã nên xem theo từng hạng mục.</p>
<h2>3. Khi nào cần ưu tiên độ đàn hồi</h2>
<p>Nếu sân thượng có rung động nhẹ, nền cũ hoặc dễ phát sinh vết nứt nhỏ, nên ưu tiên hệ có độ linh hoạt tốt hơn. Ngược lại, nếu bề mặt còn chắc, chưa có nứt động lớn và cần giải pháp phổ biến, nhóm hai thành phần gốc xi măng vẫn là lựa chọn an toàn.</p>
<h2>4. Lỗi thi công hay gặp</h2>
<ul>
<li>Bề mặt chưa sạch bụi, rêu hoặc lớp sơn cũ yếu.</li>
<li>Không xử lý cổ ống, chân tường và khe tiếp giáp.</li>
<li>Thi công khi nền còn ẩm không đúng yêu cầu của sản phẩm.</li>
<li>Không ước lượng đủ vật tư nên lớp phủ bị mỏng.</li>
</ul>
<h2>5. Cách chốt vật tư gọn hơn</h2>
<p>Trước khi đặt hàng, bạn nên chuẩn bị ảnh hiện trạng, diện tích, số lớp dự kiến và thời gian cần giao vật tư. Đội ngũ {$site_name} có thể dựa vào những thông tin này để chốt đúng hệ chống thấm và báo giá nhanh hơn qua <a href="{$contact_url}">trang liên hệ</a>. Nếu cần tự ước lượng trước, hãy dùng <a href="{$calculator_url}">công cụ tính định mức</a>.</p>
HTML,
    ],
    [
        'slug' => 'cach-chon-keo-cha-ron-cho-nha-tam-va-bep',
        'title' => 'Cách chọn keo chà ron cho nhà tắm và bếp',
        'excerpt' => 'Gợi ý chọn keo chà ron cho nhà tắm và bếp theo độ bám bẩn, chống mốc và màu ron phù hợp.',
        'category_ids' => [$category_ids['keo-va-phu-gia']],
        'thumbnail_id' => $products['webercolor_no_stain']['thumb_id'],
        'date' => '2026-02-27 10:10:00',
        'content' => <<<HTML
<p>Nhà tắm và bếp là hai khu vực ron gạch dễ xuống màu, bám bẩn và phát sinh mốc. Nếu chọn sai loại chà ron, bề mặt nhìn cũ rất nhanh dù gạch còn mới.</p>
<h2>1. Chọn theo vị trí sử dụng</h2>
<ul>
<li><strong>Nhà tắm:</strong> ưu tiên ron chống bám bẩn, chống mốc và chịu ẩm tốt.</li>
<li><strong>Khu bếp:</strong> ưu tiên ron dễ vệ sinh, ít lưu bám dầu mỡ.</li>
<li><strong>Khu vực ron mảnh:</strong> chọn đúng cỡ hạt và khuyến nghị khe ron của nhà sản xuất.</li>
</ul>
<h2>2. Nhóm sản phẩm nên tham khảo</h2>
<p>Nếu cần bề mặt sạch lâu hơn, có thể bắt đầu từ <a href="{$products['webercolor_no_stain']['url']}">Webercolor No Stain</a>. Với nhu cầu phổ thông, dễ phối màu và thi công quen tay, có thể xem <a href="{$products['webercolor_classic']['url']}">Keo chà ron Webercolor Classic</a> hoặc <a href="{$products['webercolor_slim']['url']}">Webercolor Slim</a> tùy loại khe ron.</p>
<h2>3. Cách chọn màu ron</h2>
<p>Màu ron không nhất thiết phải trùng hoàn toàn màu gạch. Với sàn hoặc tường sử dụng nhiều, tông xám nhạt, xám trung tính hoặc be thường giúp bề mặt sạch nhìn lâu hơn. Khu bếp có thể chọn màu gần với mạch gạch để tổng thể gọn và ít lộ bẩn.</p>
<h2>4. Những lỗi khiến ron nhanh hỏng</h2>
<ul>
<li>Thi công khi khe ron còn bụi hoặc chưa đủ sâu.</li>
<li>Pha trộn sai tỷ lệ nước.</li>
<li>Chọn sai loại ron cho khu vực ẩm liên tục.</li>
<li>Không vệ sinh bề mặt đúng lúc sau khi chà ron.</li>
</ul>
<h2>5. Khi nào nên hỏi kỹ trước khi mua</h2>
<p>Nếu bạn chưa chắc độ rộng khe ron, loại gạch hoặc khu vực thi công, nên gửi ảnh và thông tin hạng mục để được tư vấn trước. Có thể xem thêm <a href="{$guide_url}">hướng dẫn mua hàng</a> hoặc liên hệ trực tiếp qua <a href="{$contact_url}">đội kỹ thuật của {$site_name}</a> để chọn đúng mã ron và số lượng cần dùng.</p>
HTML,
    ],
    [
        'slug' => 'cach-chon-son-chong-ri-cho-cua-sat-va-lan-can',
        'title' => 'Cách chọn sơn chống rỉ cho cửa sắt và lan can',
        'excerpt' => 'Cách chọn sơn chống rỉ cho cửa sắt, lan can và khung thép nhỏ theo tình trạng bề mặt và hệ phủ màu.',
        'category_ids' => [$category_ids['son-kim-loai']],
        'thumbnail_id' => $products['jotun_gardex']['thumb_id'],
        'date' => '2026-02-28 08:20:00',
        'content' => <<<HTML
<p>Cửa sắt, lan can và khung thép ngoài trời xuống cấp rất nhanh nếu bỏ qua lớp chống rỉ hoặc xử lý bề mặt sơ sài. Chọn đúng mã sơn chống rỉ giúp lớp phủ màu bám tốt hơn và kéo dài tuổi thọ của kim loại.</p>
<h2>1. Xử lý bề mặt luôn là bước đầu tiên</h2>
<p>Trước khi sơn, cần làm sạch bụi, lớp sơn bong yếu và phần rỉ sét lỏng. Nếu bề mặt còn dầu mỡ hoặc lớp cũ quá kém, màng sơn mới sẽ không bám ổn định dù chọn đúng sản phẩm.</p>
<h2>2. Nên chọn nhóm chống rỉ nào</h2>
<p>Với nhu cầu dân dụng phổ biến, có thể tham khảo <a href="{$products['jotun_gardex']['url']}">Jotun Gardex Metal Primer</a>, <a href="{$products['kova_metal_primer']['url']}">Kova Metal Primer</a> hoặc <a href="{$products['toa_rust_tech']['url']}">TOA Rust Tech Primer</a>. Tùy vị trí trong nhà hay ngoài trời, bạn có thể chọn thêm lớp phủ màu tương thích để hoàn thiện bề mặt.</p>
<h2>3. Khi nào cần sơn lại trọn hệ</h2>
<ul>
<li>Bề mặt đã bong tróc nhiều lớp cũ.</li>
<li>Lan can ngoài trời bị nắng mưa liên tục.</li>
<li>Cửa sắt gần biển hoặc khu ẩm cao.</li>
</ul>
<p>Những trường hợp này nên xử lý lại tương đối đồng bộ thay vì chỉ dặm vá cục bộ, vì lớp cũ và lớp mới thường không bền đều nhau.</p>
<h2>4. Sai lầm phổ biến khi sơn chống rỉ</h2>
<ul>
<li>Sơn trực tiếp lên lớp rỉ chưa xử lý.</li>
<li>Không đủ thời gian khô giữa các lớp.</li>
<li>Chỉ mua lớp phủ màu mà bỏ qua lớp primer.</li>
<li>Dùng sai quy cách vật tư so với diện tích thực tế.</li>
</ul>
<h2>5. Cách chốt nhanh vật tư cho hạng mục sắt thép</h2>
<p>Nếu bạn đã có diện tích hoặc số mét dài cửa, lan can, có thể gửi kèm tình trạng bề mặt để được chốt quy cách nhanh hơn. Xem thêm <a href="{$faq_url}">FAQ</a> hoặc liên hệ qua <a href="{$contact_url}">trang báo giá</a> để nhận gợi ý đúng hệ sơn cho kim loại.</p>
HTML,
    ],
    [
        'slug' => 'khi-nao-can-son-lot-khang-kiem',
        'title' => 'Khi nào cần sơn lót kháng kiềm',
        'excerpt' => 'Tóm tắt khi nào nên dùng sơn lót kháng kiềm cho tường mới, tường cũ và khu vực ngoài trời.',
        'category_ids' => [$category_ids['son-lot']],
        'thumbnail_id' => $products['dulux_primer']['thumb_id'],
        'date' => '2026-03-01 08:40:00',
        'content' => <<<HTML
<p>Sơn lót kháng kiềm là lớp hay bị bỏ qua nhất khi làm nhà, nhưng cũng là lớp quyết định độ bền màu và độ ổn định của màng sơn phủ. Nếu nền tường còn kiềm hoặc chưa ổn định, lớp phủ đẹp mấy cũng dễ ố, loang và bong sớm.</p>
<h2>1. Những trường hợp nên dùng sơn lót</h2>
<ul>
<li>Tường mới xây hoặc mới trát xong.</li>
<li>Tường đã sửa vá, bề mặt không đồng đều.</li>
<li>Tường ngoài trời chịu nắng mưa nhiều.</li>
<li>Bề mặt có hiện tượng phấn hóa hoặc đã từng bong tróc.</li>
</ul>
<h2>2. Có thể tham khảo những dòng nào</h2>
<p>Trong nhóm dân dụng, có thể bắt đầu từ <a href="{$products['dulux_primer']['url']}">sơn lót cao cấp Dulux</a>, <a href="{$products['jotun_majestic_primer']['url']}">Jotun Majestic Primer</a>, <a href="{$products['nippon_sealer']['url']}">Nippon Odour-less Sealer</a> hoặc <a href="{$products['toa_1000']['url']}">TOA 1000 kháng kiềm</a> tùy hệ sơn và khu vực sử dụng.</p>
<h2>3. Dấu hiệu bỏ qua sơn lót sẽ dễ phát sinh lỗi</h2>
<p>Tường có mảng ẩm cũ, độ chênh hút nước lớn hoặc nhà cần sơn màu sáng thường dễ lộ lỗi hơn nếu thiếu lớp lót. Với ngoại thất, lớp lót càng quan trọng vì phải làm việc cùng nắng gắt và hơi ẩm liên tục.</p>
<h2>4. Chốt hệ lót như thế nào cho gọn</h2>
<p>Cách an toàn nhất là chốt theo bộ lót + phủ cùng nhu cầu sử dụng. Nếu bạn chưa rõ cần bao nhiêu thùng, có thể dùng <a href="{$calculator_url}">công cụ tính định mức</a> rồi gửi diện tích qua <a href="{$contact_url}">trang liên hệ</a> để được kiểm tra lại trước khi đặt hàng.</p>
HTML,
    ],
    [
        'slug' => 'bot-tret-noi-that-va-ngoai-that-khac-nhau-o-dau',
        'title' => 'Bột trét nội thất và ngoại thất khác nhau ở đâu',
        'excerpt' => 'Giải thích sự khác nhau giữa bột trét nội thất và ngoại thất để tránh lấy sai vật tư cho công trình.',
        'category_ids' => [$category_ids['bot-tret']],
        'thumbnail_id' => $products['dulux_putty']['thumb_id'],
        'date' => '2026-03-01 14:00:00',
        'content' => <<<HTML
<p>Bột trét giúp bề mặt phẳng và ổn định hơn trước khi sơn, nhưng không phải công trình nào cũng dùng cùng một loại. Nội thất và ngoại thất khác nhau ở môi trường làm việc, nên chọn sai bột trét sẽ kéo theo rạn nứt hoặc bong lớp sơn phủ phía trên.</p>
<h2>1. Bột trét nội thất dùng khi nào</h2>
<p>Bột trét nội thất phù hợp cho tường trong nhà, khu vực ít chịu mưa nắng trực tiếp và yêu cầu bề mặt mịn để lên màu đẹp. Bạn có thể tham khảo <a href="{$products['dulux_putty']['url']}">bột trét cao cấp Dulux</a> hoặc <a href="{$products['kova_putty_interior']['url']}">bột trét Kova nội thất</a> khi cần hoàn thiện mảng tường ở phòng khách, phòng ngủ, hành lang.</p>
<h2>2. Bột trét ngoại thất cần chịu gì nhiều hơn</h2>
<p>Ngoại thất phải chịu thay đổi nhiệt độ, hơi ẩm và rung co giãn của tường mạnh hơn. Vì vậy nên ưu tiên nhóm bột trét có khả năng làm việc với môi trường ngoài trời tốt hơn, chẳng hạn <a href="{$products['kova_putty_exterior']['url']}">Kova ma-tít dẻo ngoại thất</a> hoặc <a href="{$products['maxilite_putty']['url']}">bột trét tường nội ngoại thất Maxilite</a> cho các hạng mục phù hợp.</p>
<h2>3. Sai lầm thường gặp</h2>
<ul>
<li>Trét quá dày để sửa nhanh bề mặt xấu.</li>
<li>Dùng bột nội thất cho mảng ngoài trời.</li>
<li>Không chờ đủ thời gian khô trước khi sơn lót.</li>
<li>Không xử lý bề mặt ẩm hoặc bột cũ yếu.</li>
</ul>
<h2>4. Nên hỏi gì trước khi đặt bột trét</h2>
<p>Hãy xác định rõ bề mặt là trong nhà hay ngoài trời, tường mới hay tường cũ, và cần mức độ phẳng tới đâu. Nếu chưa chắc loại bột phù hợp, bạn có thể gửi ảnh hạng mục qua <a href="{$contact_url}">trang liên hệ</a> để được chốt lại trước khi mua.</p>
HTML,
    ],
    [
        'slug' => 'cach-chon-son-epoxy-cho-san-nha-xuong-nho',
        'title' => 'Cách chọn sơn epoxy cho sàn nhà xưởng nhỏ',
        'excerpt' => 'Gợi ý chọn sơn epoxy cho gara, kho và xưởng nhỏ theo tải trọng, nền hiện trạng và mức đầu tư.',
        'category_ids' => [$category_ids['son-epoxy']],
        'thumbnail_id' => $products['sikafloor_263']['thumb_id'],
        'date' => '2026-03-02 08:10:00',
        'content' => <<<HTML
<p>Sơn epoxy thường được khách hàng hỏi khi cần làm gara, kho nhỏ, xưởng mini hoặc mặt sàn cần dễ vệ sinh hơn. Điều quan trọng là phải chọn theo hiện trạng nền và mức chịu tải, không nên nhìn mỗi màu hoặc giá thùng.</p>
<h2>1. Cần hỏi gì trước khi chọn epoxy</h2>
<ul>
<li>Nền bê tông mới hay cũ.</li>
<li>Có dầu mỡ, bụi mài mòn hoặc xe nâng nhẹ hay không.</li>
<li>Muốn bề mặt lăn đơn giản hay cần hệ dày hơn.</li>
<li>Thời gian cần đưa công trình vào sử dụng.</li>
</ul>
<h2>2. Một số mã nên tham khảo</h2>
<p>Nhóm phổ biến có thể bắt đầu từ <a href="{$products['sikafloor_263']['url']}">SikaFloor 263</a>, <a href="{$products['toa_epoxy_topcoat']['url']}">TOA Epoxy Floor Topcoat</a>, <a href="{$products['kova_epoxy']['url']}">Kova sơn epoxy sàn công nghiệp</a> hoặc <a href="{$products['weberepox_easy']['url']}">Weberepox Easy</a> tùy loại nền và yêu cầu thi công.</p>
<h2>3. Khi nào nên ưu tiên lớp lót và xử lý nền</h2>
<p>Nền yếu, còn ẩm hoặc có bụi xi măng sẽ làm màng epoxy bám kém. Với sàn cũ, khâu xử lý nền quan trọng không kém việc chọn mã sơn. Nếu bỏ qua bước này, lớp hoàn thiện dễ bong hoặc mài mòn không đều.</p>
<h2>4. Cách lên bộ vật tư cho sàn nhỏ</h2>
<p>Với gara hoặc kho nhỏ, nên chốt theo bộ gồm xử lý bề mặt, lớp lót, lớp phủ và định mức thi công. Bạn có thể gửi diện tích, ảnh nền và thời gian cần bàn giao qua <a href="{$contact_url}">trang liên hệ</a> để nhận tư vấn kỹ thuật trước khi mua vật tư.</p>
HTML,
    ],
    [
        'slug' => 'so-sanh-dulux-jotun-nippon-cho-nhu-cau-pho-thong',
        'title' => 'So sánh Dulux, Jotun, Nippon cho nhu cầu phổ thông',
        'excerpt' => 'So sánh nhanh Dulux, Jotun và Nippon theo nhu cầu nội thất, ngoại thất và mức đầu tư phổ thông.',
        'category_ids' => [$category_ids['son-noi-that'], $category_ids['son-ngoai-that']],
        'thumbnail_id' => $products['dulux_easyclean_bong']['thumb_id'],
        'date' => '2026-03-02 14:20:00',
        'content' => <<<HTML
<p>Khi so sánh Dulux, Jotun và Nippon, cách dễ chốt nhất là so theo nhu cầu sử dụng thay vì chỉ nhìn tên hãng. Mỗi hãng đều có nhóm nội thất, ngoại thất và mức ngân sách khác nhau nên quyết định sẽ rõ hơn nếu bám đúng hạng mục.</p>
<h2>1. Nếu ưu tiên nội thất dễ lau chùi</h2>
<p>Bạn có thể bắt đầu từ <a href="{$products['dulux_easyclean_bong']['url']}">Dulux EasyClean</a>, <a href="{$products['jotun_majestic_silk']['url']}">Jotun Majestic Silk</a> hoặc <a href="{$products['nippon_odourless']['url']}">Nippon Odour-less</a>. Mỗi dòng có cảm giác hoàn thiện và mức đầu tư khác nhau, nhưng đều phù hợp nhóm nhà ở dân dụng nếu chọn đúng bề mặt.</p>
<h2>2. Nếu ưu tiên ngoại thất bền màu</h2>
<p>Nhóm ngoại thất có thể so giữa <a href="{$products['dulux_weathershield']['url']}">Dulux Weathershield</a>, <a href="{$products['jotashield']['url']}">Jotashield</a> và <a href="{$products['nippon_weathergard']['url']}">Nippon WeatherGard</a>. Điểm cần so là độ bền màu, chống bám bụi và hệ lót đi cùng.</p>
<h2>3. Khi nào nên chọn theo ngân sách</h2>
<p>Nếu cần tối ưu chi phí nhưng vẫn giữ đúng quy trình, nên chọn theo bộ vật tư vừa đủ thay vì cố lấy riêng lớp phủ cao nhất. Với nhà ở phổ thông, xử lý nền và lớp lót đúng thường quyết định độ bền nhiều hơn việc nhảy vọt sang một dòng quá cao cấp.</p>
<h2>4. Cách chốt nhanh cho chủ nhà</h2>
<ul>
<li>Ưu tiên lau chùi: đi theo nhóm nội thất cao hơn.</li>
<li>Ưu tiên mặt tiền bền màu: đi theo nhóm ngoại thất có hệ lót đồng bộ.</li>
<li>Ưu tiên dễ mua, dễ dặm lại: chọn dòng phổ biến, sẵn hàng.</li>
</ul>
<p>Nếu bạn cần chốt nhanh theo diện tích và ngân sách, hãy xem thêm <a href="{$guide_url}">hướng dẫn mua hàng</a> hoặc gửi nhu cầu qua <a href="{$contact_url}">trang liên hệ</a> để được gợi ý mã sơn phù hợp hơn.</p>
HTML,
    ],
    [
        'slug' => 'chong-tham-tuong-ngoai-troi-khi-da-tham-nuoc',
        'title' => 'Chống thấm tường ngoài trời khi đã thấm nước',
        'excerpt' => 'Cách xử lý chống thấm tường ngoài trời khi đã có dấu hiệu thấm nước, loang ẩm hoặc rạn nứt nhỏ.',
        'category_ids' => [$category_ids['chong-tham']],
        'thumbnail_id' => $products['dulux_wall_waterproof']['thumb_id'],
        'date' => '2026-03-02 18:15:00',
        'content' => <<<HTML
<p>Tường ngoài trời đã thấm nước thường không thể xử lý chỉ bằng cách sơn phủ lại. Muốn bền, cần nhìn đúng nguyên nhân thấm và chọn hệ xử lý theo tình trạng thực tế của bề mặt.</p>
<h2>1. Phân biệt một số dạng thấm thường gặp</h2>
<ul>
<li>Thấm ngang qua mạch nứt nhỏ.</li>
<li>Thấm từ chân tường hoặc mép ban công.</li>
<li>Thấm do lớp sơn cũ lão hóa, mất khả năng che chắn.</li>
</ul>
<h2>2. Một số hệ chống thấm ngoài trời nên xem</h2>
<p>Với tường ngoài trời, có thể tham khảo <a href="{$products['dulux_wall_waterproof']['url']}">Dulux Weathershield Chất Chống Thấm</a>, <a href="{$products['aquatech_flex']['url']}">Dulux Aquatech Flex Waterproofing</a>, <a href="{$products['kova_ct11a_plus']['url']}">Kova CT-11A Plus</a> hoặc <a href="{$products['weberdry_2kflex']['url']}">Weberdry 2KFlex</a> tùy nền tường và mức độ nứt.</p>
<h2>3. Khi nào cần bóc lớp sơn cũ</h2>
<p>Nếu lớp sơn cũ đã phồng rộp, bong hoặc nền bên dưới bở yếu, nên xử lý lại tương đối kỹ trước khi chống thấm. Sơn đè trực tiếp lên nền kém ổn định thường chỉ che tạm trong thời gian ngắn.</p>
<h2>4. Cách chuẩn bị thông tin để báo giá nhanh</h2>
<p>Hãy chuẩn bị ảnh khu vực bị thấm, chiều cao mảng tường, vị trí nứt và thời gian cần xử lý. Khi có đủ các thông tin này, đội kỹ thuật sẽ chốt hệ vật tư sát hơn và tránh mua thiếu hoặc mua sai. Bạn có thể gửi trực tiếp qua <a href="{$contact_url}">trang liên hệ</a> để được tư vấn nhanh.</p>
HTML,
    ],
];

$results = [];

foreach ($posts as $item) {
    $existing = get_page_by_path($item['slug'], OBJECT, 'post');
    $postarr = [
        'post_type' => 'post',
        'post_status' => 'publish',
        'post_name' => $item['slug'],
        'post_title' => $item['title'],
        'post_excerpt' => $item['excerpt'],
        'post_content' => $item['content'],
        'post_category' => array_values(array_filter(array_map('intval', $item['category_ids']))),
        'post_author' => 1,
        'post_date' => $item['date'],
        'post_date_gmt' => get_gmt_from_date($item['date']),
    ];

    if ($existing instanceof WP_Post) {
        $postarr['ID'] = (int) $existing->ID;
        $post_id = wp_update_post(wp_slash($postarr), true);
        $action = 'updated';
    } else {
        $post_id = wp_insert_post(wp_slash($postarr), true);
        $action = 'created';
    }

    if (is_wp_error($post_id)) {
        $results[] = [
            'slug' => $item['slug'],
            'status' => 'error',
            'message' => $post_id->get_error_message(),
        ];
        continue;
    }

    if (!empty($item['thumbnail_id'])) {
        set_post_thumbnail((int) $post_id, (int) $item['thumbnail_id']);
    }

    $results[] = [
        'slug' => $item['slug'],
        'status' => $action,
        'post_id' => (int) $post_id,
    ];
}

delete_transient('my_theme_placeholder_blog_post_ids_v1');

foreach ($results as $result) {
    if ($result['status'] === 'error') {
        echo '[error] ' . $result['slug'] . ' - ' . $result['message'] . PHP_EOL;
        continue;
    }

    echo '[' . $result['status'] . '] ' . $result['slug'] . ' #' . $result['post_id'] . PHP_EOL;
}
