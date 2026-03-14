<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('my_theme_get_archive_support_group_key')) {
    function my_theme_get_archive_support_group_key(array $args = [])
    {
        $category_term = isset($args['category_term']) && $args['category_term'] instanceof WP_Term
            ? $args['category_term']
            : null;
        $brand_slug = isset($args['brand_slug']) ? sanitize_title((string) $args['brand_slug']) : '';
        $line_slug = isset($args['line_slug']) ? sanitize_title((string) $args['line_slug']) : '';
        $search_query = isset($args['search_query']) ? sanitize_text_field((string) $args['search_query']) : '';

        if ($category_term instanceof WP_Term && function_exists('my_theme_get_visual_story_group_key_from_product_category_slug')) {
            $group_key = sanitize_key((string) my_theme_get_visual_story_group_key_from_product_category_slug($category_term->slug));
            if ($group_key !== '') {
                return $group_key;
            }
        }

        if ($line_slug === '' && $search_query !== '' && function_exists('my_theme_detect_line_slug_from_text')) {
            $line_slug = sanitize_title((string) my_theme_detect_line_slug_from_text($search_query, $brand_slug));
        }

        $line_slug = sanitize_title((string) $line_slug);
        if ($line_slug === '') {
            return '';
        }

        $group_map = [
            'waterproofing' => ['aquatech', 'line-waterproof', 'waterproof-201', 'weatherproof', 'hydroshield', 'waterguard', 'monotop', 'sikatop', 'weberdry'],
            'epoxy' => ['line-epoxy', 'line-industrial', 'sikafloor', 'epoxy', 'epoxy-floor', 'penguard'],
            'metal' => ['line-metal', 'line-oil', 'rusttech', 'bodelac', 'metal'],
            'grout' => ['line-adhesive', 'sikaceram', 'sikaflex', 'sikagrout', 'sikaguard', 'tilefix', 'weatherseal-a68', 'weatherseal-a79', 'a100', 'a200', 'a300', 'a500', 'a600', 'sanitary-n', 'pu-foam', 'pu-foam-b1'],
            'exterior' => ['line-exterior', 'weathershield', '4seasons', 'weatherbond', 'maxilite', 'jotashield'],
            'interior' => ['line-interior', 'line-primer', 'line-putty', 'easyclean', 'nanoshield', 'majestic', 'odourless', 'vinilex', 'matex', 'skimcoat', 'jotaplast', 'essence'],
        ];

        foreach ($group_map as $group_key => $tokens) {
            foreach ($tokens as $token) {
                if ($line_slug === $token || strpos($line_slug, $token) !== false) {
                    return $group_key;
                }
            }
        }

        return '';
    }
}

if (!function_exists('my_theme_get_archive_support_profile')) {
    function my_theme_get_archive_support_profile(array $args = [])
    {
        $shop_url = isset($args['shop_url']) ? esc_url_raw((string) $args['shop_url']) : (function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop'));
        $brand_slug = isset($args['brand_slug']) ? sanitize_title((string) $args['brand_slug']) : '';
        $line_slug = isset($args['line_slug']) ? sanitize_title((string) $args['line_slug']) : '';
        $search_query = isset($args['search_query']) ? sanitize_text_field((string) $args['search_query']) : '';
        $category_term = isset($args['category_term']) && $args['category_term'] instanceof WP_Term
            ? $args['category_term']
            : null;
        $found_posts = isset($args['found_posts']) ? max(0, (int) $args['found_posts']) : 0;
        $in_stock = !empty($args['in_stock']);
        $on_sale = !empty($args['on_sale']);

        $brand_label = $brand_slug !== '' && function_exists('my_theme_get_brand_label_from_slug')
            ? (string) my_theme_get_brand_label_from_slug($brand_slug)
            : '';
        $line_label = $line_slug !== '' && function_exists('my_theme_get_line_label_from_slug')
            ? (string) my_theme_get_line_label_from_slug($line_slug)
            : '';
        if ($line_label === '' && $search_query !== '' && function_exists('my_theme_detect_line_slug_from_text') && function_exists('my_theme_get_line_label_from_slug')) {
            $detected_line = sanitize_title((string) my_theme_detect_line_slug_from_text($search_query, $brand_slug));
            if ($detected_line !== '') {
                $line_slug = $detected_line;
                $line_label = (string) my_theme_get_line_label_from_slug($detected_line);
            }
        }

        $category_label = $category_term instanceof WP_Term ? trim((string) $category_term->name) : '';
        $category_intro = ($category_term instanceof WP_Term && function_exists('my_theme_get_category_intro'))
            ? trim((string) my_theme_get_category_intro((int) $category_term->term_id))
            : '';

        $group_key = my_theme_get_archive_support_group_key([
            'category_term' => $category_term,
            'brand_slug' => $brand_slug,
            'line_slug' => $line_slug,
            'search_query' => $search_query,
        ]);

        $support_profiles = function_exists('my_theme_get_single_product_support_profiles')
            ? my_theme_get_single_product_support_profiles()
            : [];
        $base_profile = isset($support_profiles[$group_key]) && is_array($support_profiles[$group_key])
            ? $support_profiles[$group_key]
            : (isset($support_profiles['default']) && is_array($support_profiles['default']) ? $support_profiles['default'] : []);

        $brand_notes = [
            'dulux' => 'Thiên về hệ sơn trang trí dân dụng, phủ mạnh các nhóm nội thất, ngoại thất và chống thấm dễ triển khai cho nhà ở.',
            'maxilite' => 'Phù hợp các công trình dân dụng cần giải pháp kinh tế, dễ thi công và dễ chốt theo diện tích thực tế.',
            'weber' => 'Tập trung vật liệu kỹ thuật như chống thấm, keo dán gạch, chà ron và xử lý nền cho hạng mục hoàn thiện.',
            'jotun' => 'Mạnh về sơn trang trí và bảo vệ bề mặt, phù hợp khi khách muốn so giữa độ hoàn thiện, độ bền màu và hệ vật tư đồng bộ.',
            'nippon' => 'Phổ biến ở cả nội và ngoại thất, phù hợp khi cần so mã theo nhu cầu mùi nhẹ, dễ lau chùi hoặc bền thời tiết.',
            'kova' => 'Phù hợp công trình dân dụng quen dùng hệ vật liệu trong nước, nhất là khi khách hỏi theo chống thấm và sơn tường truyền thống.',
            'toa' => 'Danh mục rất rộng từ sơn tường, chống thấm tới epoxy và kim loại, nên cần chốt đúng dòng trước khi so giá.',
            'sika' => 'Thiên về vật liệu kỹ thuật công trình như chống thấm, sửa chữa, keo dán gạch, ron và phụ gia chuyên dụng.',
            'apollo' => 'Tập trung keo silicone, sealant và vật liệu xử lý khe nối, phù hợp khi khách hỏi theo cửa, kính, nhà tắm và mối nối hoàn thiện.',
        ];

        $line_notes = [
            'aquatech' => 'Dòng Aquatech nên được chốt theo đúng vị trí thi công như tường đứng, sàn mái hay khu vực cần đàn hồi để tránh chọn sai mã.',
            'easyclean' => 'EasyClean phù hợp nội thất cần lau chùi tốt hơn ở khu sinh hoạt hằng ngày, đặc biệt nơi có trẻ nhỏ hoặc bề mặt dễ bám bẩn nhẹ.',
            'nanoshield' => 'NanoShield thiên về lớp phủ nội thất phổ thông, nên so theo độ mịn, độ che phủ và ngân sách thay vì chỉ nhìn tên dòng.',
            'weathershield' => 'Weathershield phù hợp mặt tiền và tường ngoài trời cần độ bền màu và lớp phủ ổn định dưới thời tiết thực tế.',
            'toa1000' => 'TOA 1000 là lớp lót kháng kiềm, cần đi trước lớp phủ màu chứ không phải lớp hoàn thiện cuối cùng.',
            'rusttech' => 'Rust Tech dùng cho bề mặt kim loại cần xử lý rỉ, nên chốt thêm tình trạng nền và môi trường sử dụng trước khi lấy số lớp.',
            'sikaceram' => 'SikaCeram nên so theo loại gạch, vị trí ốp lát và yêu cầu bám dính để tránh chọn thiếu cấp độ vật liệu.',
            'sikaflex' => 'SikaFlex phù hợp trám khe và mối nối cần độ đàn hồi, nên xác định rõ chiều rộng khe và khu vực thi công.',
            'sikafloor' => 'SikaFloor thuộc hệ sàn kỹ thuật, cần chốt theo độ ẩm nền, tải trọng và yêu cầu hoàn thiện bề mặt.',
            'sikatop' => 'SikaTop thường đi với hạng mục sửa chữa, chống thấm hoặc gia cường bề mặt, không nên chọn như một vật liệu phủ đơn lẻ.',
            'monotop' => 'MonoTop là vật liệu sửa chữa vữa kỹ thuật, cần so theo chiều dày bù vá và tình trạng nền bê tông.',
            'weatherseal-a68' => 'Weatherseal A68 phù hợp trám khe hoàn thiện dân dụng, ưu tiên so theo khu vực dùng trong nhà hay ngoài trời.',
            'weatherseal-a79' => 'Weatherseal A79 nên chốt theo bề mặt bám dính và yêu cầu chịu thời tiết của khe nối.',
            'a100' => 'A100 phù hợp silicone phổ thông, nên kiểm tra kỹ loại bề mặt trước khi đặt số lượng.',
            'a200' => 'A200 cần được chọn theo ứng dụng trám khe và điều kiện môi trường để bền hơn ngoài thực tế.',
            'a300' => 'A300 thường dùng cho mối nối kỹ thuật hơn, nên đối chiếu lại khe nối và vật liệu nền.',
            'a500' => 'A500 phù hợp nhóm keo silicone bám dính cao hơn, cần xác định đúng khu vực dùng.',
            'a600' => 'A600 nên được chốt theo mục tiêu bám dính, độ đàn hồi và môi trường thi công.',
            'sanitary-n' => 'Sanitary-N phù hợp khu vệ sinh và mảng ẩm, nên ưu tiên so đúng nhu cầu chống mốc và bề mặt tiếp xúc.',
        ];

        $filter_meta = [];
        if ($found_posts > 0) {
            $filter_meta[] = number_format_i18n($found_posts) . ' sản phẩm phù hợp';
        }
        if ($brand_label !== '') {
            $filter_meta[] = $brand_label;
        }
        if ($line_label !== '') {
            $filter_meta[] = $line_label;
        }
        if ($category_label !== '') {
            $filter_meta[] = $category_label;
        }
        if ($in_stock) {
            $filter_meta[] = 'Chỉ hiển thị mã còn hàng';
        }
        if ($on_sale) {
            $filter_meta[] = 'Đang lọc khuyến mãi';
        }

        $heading_parts = [];
        if ($brand_label !== '') {
            $heading_parts[] = $brand_label;
        }
        if ($line_label !== '' && !in_array($line_label, $heading_parts, true)) {
            $heading_parts[] = $line_label;
        }
        if ($category_label !== '' && !in_array($category_label, $heading_parts, true)) {
            $heading_parts[] = $category_label;
        }

        if (!empty($heading_parts)) {
            $heading_title = implode(' / ', $heading_parts);
        } elseif ($search_query !== '') {
            $heading_title = 'Kết quả tìm cho "' . $search_query . '"';
        } else {
            $heading_title = 'Kho sản phẩm chính hãng';
        }

        $panel_title = 'Công dụng, công trình và cách chọn nhanh';
        if ($line_label !== '') {
            $panel_title = 'Công dụng và cách chọn nhanh cho ' . $line_label;
        } elseif ($category_label !== '' && $brand_label !== '') {
            $panel_title = $brand_label . ' cho ' . $category_label;
        } elseif ($category_label !== '') {
            $panel_title = 'Thông tin chọn nhanh cho ' . $category_label;
        } elseif ($brand_label !== '') {
            $panel_title = 'Cách chọn nhanh sản phẩm ' . $brand_label;
        }

        $subtitle_parts = [];
        if ($category_intro !== '') {
            $subtitle_parts[] = $category_intro;
        }
        if ($line_slug !== '' && isset($line_notes[$line_slug])) {
            $subtitle_parts[] = $line_notes[$line_slug];
        } elseif ($brand_slug !== '' && isset($brand_notes[$brand_slug])) {
            $subtitle_parts[] = $brand_notes[$brand_slug];
        } elseif (!empty($base_profile['fit_text'])) {
            $subtitle_parts[] = (string) $base_profile['fit_text'];
        }
        if ($search_query !== '') {
            $subtitle_parts[] = 'Hiện đang lọc theo nhu cầu "' . $search_query . '" để khách so nhanh đúng nhóm vật tư hơn.';
        }
        $panel_subtitle = implode(' ', array_slice(array_values(array_filter($subtitle_parts)), 0, 2));

        $quick_pick_text = 'Chốt đúng bề mặt, đúng quy cách và đúng nhóm vật tư trước khi hỏi giá sẽ giảm việc sửa đơn nhiều lần.';
        if ($line_slug !== '' && isset($line_notes[$line_slug])) {
            $quick_pick_text = $line_notes[$line_slug];
        } elseif ($brand_slug !== '' && isset($brand_notes[$brand_slug])) {
            $quick_pick_text = $brand_notes[$brand_slug];
        }
        if ($on_sale) {
            $quick_pick_text .= ' Vì đang có lọc khuyến mãi, nên kiểm tra thêm quy cách và tiến độ giao trước khi chốt.';
        } elseif ($in_stock) {
            $quick_pick_text .= ' Bộ lọc hiện đang ưu tiên các mã còn hàng để khách chốt nhanh hơn.';
        }

        $checklist = [
            'Xác định rõ bề mặt, vị trí thi công và nhu cầu sử dụng trước khi so giữa các mã cùng nhóm.',
            $line_label !== ''
                ? 'Trong dòng ' . $line_label . ', nên đối chiếu thêm hiện trạng công trình để chọn đúng mã thay vì chỉ nhìn tên gọi.'
                : ($brand_label !== ''
                    ? 'Nếu đã chốt hãng ' . $brand_label . ', hãy dùng thêm bộ lọc dòng và danh mục để thu hẹp nhanh hơn.'
                    : 'Dùng thêm bộ lọc thương hiệu, dòng và danh mục để thu hẹp kho sản phẩm theo đúng nhu cầu.'),
            $group_key === 'waterproofing'
                ? 'Với vị trí có nứt, đọng nước hoặc thấm nặng, nên gửi ảnh hiện trạng trước khi báo giá để tránh chọn thiếu hệ.'
                : ($group_key === 'epoxy'
                    ? 'Với sàn epoxy, cần kiểm tra độ ẩm nền và số lớp trước khi so giá để không thiếu vật tư.'
                    : ($group_key === 'metal'
                        ? 'Với kim loại đã rỉ, nên chốt thêm bước xử lý nền và lớp lót trước khi lấy lớp phủ màu.'
                        : 'Nếu công trình có lớp cũ yếu, bề mặt ẩm hoặc cần đồng bộ nhiều lớp, nên gửi ảnh để cửa hàng đối chiếu lại.')),
        ];

        $stats = [
            [
                'value' => number_format_i18n($found_posts),
                'label' => 'mã phù hợp',
            ],
            [
                'value' => $brand_label !== '' ? $brand_label : ($category_label !== '' ? $category_label : 'Toàn kho'),
                'label' => $line_label !== '' ? $line_label : 'phạm vi đang xem',
            ],
            [
                'value' => $on_sale ? 'Sale' : ($in_stock ? 'Còn hàng' : 'Sẵn tư vấn'),
                'label' => $on_sale ? 'ưu đãi đang áp dụng' : ($in_stock ? 'lọc tồn kho' : 'chốt theo nhu cầu'),
            ],
        ];

        $facts = [
            [
                'label' => 'Bề mặt và hạng mục',
                'value' => isset($base_profile['surface_value']) ? (string) $base_profile['surface_value'] : 'Đối chiếu theo bề mặt',
                'text' => isset($base_profile['surface_text']) ? (string) $base_profile['surface_text'] : 'Cần xác định đúng bề mặt thực tế trước khi chốt mã.',
            ],
            [
                'label' => 'Công trình phù hợp',
                'value' => isset($base_profile['project_value']) ? (string) $base_profile['project_value'] : 'Nhà ở và công trình dân dụng',
                'text' => isset($base_profile['project_text']) ? (string) $base_profile['project_text'] : 'So theo khu vực dùng thực tế sẽ giúp chốt vật tư nhanh hơn.',
            ],
            [
                'label' => 'Cách chốt nhanh',
                'value' => $line_label !== '' ? $line_label : ($brand_label !== '' ? $brand_label : 'Chọn đúng hệ'),
                'text' => $quick_pick_text,
            ],
        ];

        $gallery_items = [];
        if ($group_key !== '' && function_exists('my_theme_get_visual_story_items_by_group')) {
            $gallery_items = array_values(array_filter((array) my_theme_get_visual_story_items_by_group($group_key), function ($item) {
                return is_array($item) && !empty($item['attachment_id']);
            }));
            $gallery_items = array_slice($gallery_items, 0, 3);
        }

        $lead_scope = trim(implode(' / ', array_values(array_filter([
            $brand_label !== '' ? $brand_label : '',
            $line_label !== '' ? $line_label : '',
            $category_label !== '' ? $category_label : '',
            $search_query !== '' ? 'Nhu cầu: ' . $search_query : '',
        ]))));
        if ($lead_scope === '') {
            $lead_scope = 'Tư vấn chọn sản phẩm trong kho';
        }

        $contact_url = add_query_arg(
            [
                'lead_product' => $lead_scope,
            ],
            home_url('/lien-he')
        );

        $sale_args = ['on_sale' => '1'];
        if ($brand_slug !== '') {
            $sale_args['brand'] = $brand_slug;
        }
        if ($line_slug !== '') {
            $sale_args['line'] = $line_slug;
        }
        if ($category_term instanceof WP_Term) {
            $sale_args['category'] = (int) $category_term->term_id;
        }

        return [
            'heading_title' => $heading_title,
            'eyebrow' => 'Tư vấn theo bộ lọc đang xem',
            'panel_title' => $panel_title,
            'panel_subtitle' => $panel_subtitle,
            'meta_items' => $filter_meta,
            'stats' => $stats,
            'facts' => $facts,
            'checklist' => $checklist,
            'gallery_items' => $gallery_items,
            'gallery_title' => $group_key !== '' ? 'Hình công trình tham khảo' : '',
            'gallery_subtitle' => $group_key !== '' ? 'Ảnh minh họa giúp khách đối chiếu nhanh bề mặt và phạm vi ứng dụng trước khi chốt mã.' : '',
            'cta_title' => 'Cần cửa hàng chốt nhanh đúng mã và quy cách?',
            'cta_lead' => 'Gửi ảnh hiện trạng, diện tích và nhu cầu thi công để đội ngũ tư vấn đi thẳng vào đúng nhóm vật tư.',
            'actions' => [
                [
                    'label' => 'Gửi yêu cầu tư vấn',
                    'url' => $contact_url,
                    'class' => 'btn btn-primary btn-sm',
                ],
                [
                    'label' => 'Xem khuyến mãi',
                    'url' => add_query_arg($sale_args, $shop_url),
                    'class' => 'btn btn-outline btn-sm',
                ],
                [
                    'label' => 'Mở toàn bộ kho',
                    'url' => $shop_url,
                    'class' => 'btn btn-outline btn-sm',
                ],
            ],
        ];
    }
}

if (!function_exists('my_theme_render_archive_support_layout')) {
    function my_theme_render_archive_support_layout(array $args = [])
    {
        $profile = my_theme_get_archive_support_profile($args);
        if (empty($profile)) {
            return;
        }

        $meta_items = isset($profile['meta_items']) && is_array($profile['meta_items']) ? $profile['meta_items'] : [];
        $stats = isset($profile['stats']) && is_array($profile['stats']) ? $profile['stats'] : [];
        $facts = isset($profile['facts']) && is_array($profile['facts']) ? $profile['facts'] : [];
        $checklist = isset($profile['checklist']) && is_array($profile['checklist']) ? $profile['checklist'] : [];
        $gallery_items = isset($profile['gallery_items']) && is_array($profile['gallery_items']) ? $profile['gallery_items'] : [];
        $actions = isset($profile['actions']) && is_array($profile['actions']) ? $profile['actions'] : [];
        ?>
        <section class="archive-support" aria-label="<?php echo esc_attr((string) ($profile['panel_title'] ?? 'Tư vấn chọn sản phẩm')); ?>">
          <div class="archive-support__hero">
            <div class="archive-support__copy">
              <?php if (!empty($profile['eyebrow'])) : ?>
                <p class="eyebrow eyebrow-muted"><?php echo esc_html((string) $profile['eyebrow']); ?></p>
              <?php endif; ?>
              <h2 class="section-title"><?php echo esc_html((string) ($profile['panel_title'] ?? 'Tư vấn chọn sản phẩm')); ?></h2>
              <?php if (!empty($profile['panel_subtitle'])) : ?>
                <p class="section-sub"><?php echo esc_html((string) $profile['panel_subtitle']); ?></p>
              <?php endif; ?>
              <?php if (!empty($meta_items)) : ?>
                <div class="section-heading__meta" aria-label="Bối cảnh đang xem">
                  <?php foreach ($meta_items as $meta_item) : ?>
                    <?php $meta_item = trim((string) $meta_item); if ($meta_item === '') { continue; } ?>
                    <span class="section-heading__meta-item"><?php echo esc_html($meta_item); ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

            <?php if (!empty($stats)) : ?>
              <div class="archive-support__stats" aria-label="Thông tin nhanh bộ lọc">
                <?php foreach ($stats as $stat) : ?>
                  <?php
                  $value = trim((string) ($stat['value'] ?? ''));
                  $label = trim((string) ($stat['label'] ?? ''));
                  if ($value === '' || $label === '') {
                      continue;
                  }
                  ?>
                  <div class="archive-support__stat">
                    <strong><?php echo esc_html($value); ?></strong>
                    <span><?php echo esc_html($label); ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <?php if (!empty($facts)) : ?>
            <div class="info-grid archive-support__facts">
              <?php foreach ($facts as $fact) : ?>
                <?php
                $label = trim((string) ($fact['label'] ?? ''));
                $value = trim((string) ($fact['value'] ?? ''));
                $text = trim((string) ($fact['text'] ?? ''));
                if ($label === '' || $value === '' || $text === '') {
                    continue;
                }
                ?>
                <article class="info-card archive-support__fact">
                  <p class="archive-support__fact-label"><?php echo esc_html($label); ?></p>
                  <h3><?php echo esc_html($value); ?></h3>
                  <p><?php echo esc_html($text); ?></p>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <div class="archive-support__body<?php echo empty($gallery_items) ? ' archive-support__body--single' : ''; ?>">
            <?php if (!empty($checklist)) : ?>
              <div class="content-block archive-support__checklist">
                <h3>Chốt nhanh trước khi hỏi giá</h3>
                <ol class="list-numbered landing-checklist">
                  <?php foreach ($checklist as $item) : ?>
                    <?php $item = trim((string) $item); if ($item === '') { continue; } ?>
                    <li><?php echo esc_html($item); ?></li>
                  <?php endforeach; ?>
                </ol>
              </div>
            <?php endif; ?>

            <?php if (!empty($gallery_items)) : ?>
              <div class="archive-support__gallery">
                <div class="section-heading section-heading--compact">
                  <div>
                    <h3><?php echo esc_html((string) ($profile['gallery_title'] ?? 'Hình công trình tham khảo')); ?></h3>
                    <?php if (!empty($profile['gallery_subtitle'])) : ?>
                      <p class="section-sub"><?php echo esc_html((string) $profile['gallery_subtitle']); ?></p>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="archive-support__gallery-grid">
                  <?php foreach ($gallery_items as $gallery_item) : ?>
                    <?php
                    $attachment_id = (int) ($gallery_item['attachment_id'] ?? 0);
                    $caption = trim((string) ($gallery_item['caption'] ?? ''));
                    if ($attachment_id <= 0) {
                        continue;
                    }
                    ?>
                    <figure class="archive-support__gallery-card">
                      <div class="archive-support__gallery-media">
                        <?php echo wp_get_attachment_image($attachment_id, 'medium_large', false, ['loading' => 'lazy', 'alt' => $caption !== '' ? $caption : get_the_title($attachment_id)]); ?>
                      </div>
                      <?php if ($caption !== '') : ?>
                        <figcaption><?php echo esc_html($caption); ?></figcaption>
                      <?php endif; ?>
                    </figure>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <?php if (!empty($actions)) : ?>
            <?php
            static $archive_support_cta_contrast_printed = false;
            if (!$archive_support_cta_contrast_printed) :
                $archive_support_cta_contrast_printed = true;
                ?>
              <style id="archive-support-cta-contrast">
                .archive-support__cta .cta-inline__content {
                  grid-template-columns: minmax(0, 1fr);
                  gap: 10px;
                  justify-items: start;
                }

                .archive-support__cta .eyebrow-muted {
                  color: rgba(243, 248, 255, 0.92) !important;
                  letter-spacing: 0.08em;
                }

                .archive-support__cta strong {
                  display: block;
                  color: #ffffff;
                  font-size: clamp(1.55rem, 2vw, 2.2rem);
                  line-height: 1.15;
                  letter-spacing: -0.02em;
                  text-shadow: 0 10px 24px rgba(2, 14, 34, 0.2);
                }

                .archive-support__cta .cta-inline__lead {
                  color: rgba(233, 242, 255, 0.92) !important;
                  font-size: 1.02rem;
                  line-height: 1.7;
                  text-shadow: 0 6px 18px rgba(3, 18, 40, 0.16);
                }
              </style>
            <?php endif; ?>
            <section class="cta-inline archive-support__cta" aria-label="Liên hệ và điều hướng nhanh">
              <div class="cta-inline__content">
                <p class="eyebrow-muted">Đi tiếp nhanh hơn</p>
                <strong><?php echo esc_html((string) ($profile['cta_title'] ?? 'Cần chốt nhanh đúng mã?')); ?></strong>
                <p class="cta-inline__lead"><?php echo esc_html((string) ($profile['cta_lead'] ?? 'Gửi ảnh hiện trạng để cửa hàng đối chiếu nhanh hơn.')); ?></p>
              </div>
              <div class="cta-inline__actions">
                <?php foreach ($actions as $action) : ?>
                  <?php
                  $label = trim((string) ($action['label'] ?? ''));
                  $url = trim((string) ($action['url'] ?? ''));
                  $class = trim((string) ($action['class'] ?? 'btn btn-outline btn-sm'));
                  if ($label === '' || $url === '') {
                      continue;
                  }
                  ?>
                  <a class="<?php echo esc_attr($class); ?>" href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a>
                <?php endforeach; ?>
              </div>
            </section>
          <?php endif; ?>
        </section>
        <?php
    }
}
