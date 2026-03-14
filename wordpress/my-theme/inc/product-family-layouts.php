<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('my_theme_get_product_by_slug')) {
    function my_theme_get_product_by_slug($slug)
    {
        static $cache = [];

        $slug = sanitize_title((string) $slug);
        if ($slug === '') {
            return null;
        }
        if (array_key_exists($slug, $cache)) {
            return $cache[$slug];
        }

        $post = get_page_by_path($slug, OBJECT, 'product');
        if (!$post instanceof WP_Post) {
            $cache[$slug] = null;
            return null;
        }

        $product = function_exists('my_theme_resolve_product')
            ? my_theme_resolve_product((int) $post->ID)
            : wc_get_product((int) $post->ID);
        $cache[$slug] = $product instanceof WC_Product ? $product : null;
        return $cache[$slug];
    }
}

if (!function_exists('my_theme_get_aquatech_3in1_colour_options')) {
    function my_theme_get_aquatech_3in1_colour_options()
    {
        return [
            [
                'code' => '25155',
                'name' => 'Trắng',
                'label' => 'Trắng',
                'hex' => 'f4f4f1',
                'product_code' => 'V189-25155',
            ],
            [
                'code' => '75700',
                'name' => 'Trắng sứ',
                'label' => 'Trắng sứ',
                'hex' => 'eae5de',
                'product_code' => 'V189-75700',
            ],
            [
                'code' => '70620',
                'name' => 'Xám nhạt',
                'label' => 'Xám nhạt',
                'hex' => 'c4c8cb',
                'product_code' => 'V189-70620',
            ],
            [
                'code' => '70621',
                'name' => 'Xám sáng',
                'label' => 'Xám sáng',
                'hex' => 'aeb4bb',
                'product_code' => 'V189-70621',
            ],
        ];
    }
}

if (!function_exists('my_theme_get_single_product_colour_picker_options')) {
    function my_theme_get_single_product_colour_picker_options($product = null)
    {
        $product = function_exists('my_theme_resolve_product')
            ? my_theme_resolve_product($product)
            : (($product instanceof WC_Product) ? $product : wc_get_product(get_the_ID()));
        if (!$product instanceof WC_Product) {
            return [];
        }

        $slug = sanitize_title((string) $product->get_slug());
        if ($slug !== 'chatchongthamduluxaquatechtm3in1') {
            return [];
        }

        return my_theme_get_aquatech_3in1_colour_options();
    }
}

if (!function_exists('my_theme_get_single_product_colour_picker_inline_onchange')) {
    function my_theme_get_single_product_colour_picker_inline_onchange()
    {
        return "(function(input){var picker=input.closest('.product-colour-picker');if(!picker){return;}var code=input.getAttribute('data-colour-code')||input.value||'';var name=input.getAttribute('data-colour-name')||'';var productCode=input.getAttribute('data-colour-product-code')||'';var current=picker.querySelector('[data-colour-current]');if(current){current.textContent=code!==''?(code+(name!==''?' - '+name:'')):'-';}var currentCode=picker.querySelector('[data-colour-product-current]');if(currentCode){currentCode.textContent=productCode!==''?productCode:'-';}var hiddenCode=picker.querySelector('input[name=\"selected_colour_code\"]');if(hiddenCode){hiddenCode.value=code;}var hiddenName=picker.querySelector('input[name=\"selected_colour_name\"]');if(hiddenName){hiddenName.value=name;}var hiddenProductCode=picker.querySelector('input[name=\"selected_colour_product_code\"]');if(hiddenProductCode){hiddenProductCode.value=productCode;}Array.prototype.forEach.call(picker.querySelectorAll('.product-colour-option'),function(label){label.classList.toggle('is-active',label.getAttribute('for')===input.id);});var summary=picker.closest('.summary');if(!summary){return;}var note=summary.querySelector('[data-colour-selection-note]');if(note){note.textContent=productCode!==''?('Đang chọn mã '+productCode+(name!==''?' - '+name:'')+'. Khi gửi yêu cầu, form sẽ mang theo đúng mã này.'):('Đang chọn mã '+code+(name!==''?' - '+name:'')+'. Khi gửi yêu cầu, form sẽ mang theo đúng mã này.');}var phoneLabel=summary.querySelector('[data-phone-label]');if(phoneLabel){phoneLabel.textContent=code!==''?('Gọi báo giá mã '+code):'Gọi báo giá';}Array.prototype.forEach.call(summary.querySelectorAll('[data-colour-cta]'),function(link){var base=link.getAttribute('data-base-href')||link.getAttribute('href')||'';if(base===''){return;}if((link.getAttribute('data-colour-cta')||'')!=='contact'){link.href=base;return;}try{var url=new URL(base,window.location.href);var productName=picker.getAttribute('data-product-name')||'';if(productName!==''){url.searchParams.set('lead_product',productName);}if(code!==''){url.searchParams.set('lead_colour_code',code);}if(name!==''){url.searchParams.set('lead_colour_name',name);}if(productCode!==''){url.searchParams.set('lead_product_code',productCode);}url.searchParams.set('source','product-colour-picker');link.href=url.toString();}catch(e){link.href=base;}});})(this)";
    }
}

if (!function_exists('my_theme_get_single_product_colour_picker_label_onclick')) {
    function my_theme_get_single_product_colour_picker_label_onclick()
    {
        return "(function(label){var picker=label.closest('.product-colour-picker');if(!picker){return;}var id=label.getAttribute('for')||'';if(!id){return;}var input=null;Array.prototype.some.call(picker.querySelectorAll('.product-colour-option__input'),function(node){if((node.id||'')!==id){return false;}input=node;return true;});if(!input){return;}input.checked=true;try{input.dispatchEvent(new Event('change',{bubbles:true}));}catch(e){if(document.createEvent){var evt=document.createEvent('Event');evt.initEvent('change',true,true);input.dispatchEvent(evt);}else if(typeof input.onchange==='function'){input.onchange();}}})(this)";
    }
}

if (!function_exists('my_theme_render_single_product_colour_picker')) {
    function my_theme_render_single_product_colour_picker()
    {
        if (!function_exists('is_product') || !is_product()) {
            return;
        }

        $product = function_exists('my_theme_resolve_product')
            ? my_theme_resolve_product()
            : wc_get_product(get_the_ID());
        if (!$product instanceof WC_Product) {
            return;
        }

        $colour_options = my_theme_get_single_product_colour_picker_options($product);
        if (empty($colour_options)) {
            return;
        }

        $catalog_profile = function_exists('my_theme_get_product_catalog_profile')
            ? my_theme_get_product_catalog_profile($product)
            : [];
        $product_name = isset($catalog_profile['display_name']) && (string) $catalog_profile['display_name'] !== ''
            ? trim((string) $catalog_profile['display_name'])
            : trim((string) $product->get_name());
        $contact_url = home_url('/lien-he/');
        $default_option = $colour_options[0];
        $default_code = trim((string) ($default_option['code'] ?? ''));
        $default_name = trim((string) ($default_option['name'] ?? ''));
        $default_product_code = trim((string) ($default_option['product_code'] ?? ''));
        $current_label = trim($default_code . ($default_name !== '' ? ' - ' . $default_name : ''));
        $sync_onchange = my_theme_get_single_product_colour_picker_inline_onchange();
        $label_onclick = my_theme_get_single_product_colour_picker_label_onclick();

        echo '<div class="product-colour-picker product-colour-picker--aquatech" data-product-name="' . esc_attr($product_name) . '" data-contact-base="' . esc_url($contact_url) . '">';
        echo '<div class="product-colour-picker__head">';
        echo '<div>';
        echo '<div class="product-colour-picker__label">Chọn mã màu Aquatech 3in1</div>';
        echo '<p class="product-colour-picker__sub">4 màu chuẩn của dòng này. Chọn đúng mã trước khi gửi yêu cầu.</p>';
        echo '</div>';
        echo '</div>';
        echo '<div class="product-colour-picker__options" role="radiogroup" aria-label="Chọn màu Aquatech 3in1">';
        foreach ($colour_options as $index => $colour_option) {
            $code = trim((string) ($colour_option['code'] ?? ''));
            $name = trim((string) ($colour_option['name'] ?? ''));
            $label = trim((string) ($colour_option['label'] ?? $name));
            $product_code = trim((string) ($colour_option['product_code'] ?? ''));
            $hex = '#' . preg_replace('/[^0-9a-f]/i', '', (string) ($colour_option['hex'] ?? ''));
            if ($code === '' || $label === '') {
                continue;
            }
            $input_id = 'product-colour-' . $product->get_id() . '-' . sanitize_title($code);
            echo '<input class="product-colour-option__input" type="radio" name="selected_colour_option" id="' . esc_attr($input_id) . '" value="' . esc_attr($code) . '" data-colour-code="' . esc_attr($code) . '" data-colour-name="' . esc_attr($name) . '" data-colour-product-code="' . esc_attr($product_code) . '" onchange="' . esc_attr($sync_onchange) . '"' . checked($index, 0, false) . '>';
            echo '<label class="product-colour-option' . ($index === 0 ? ' is-active' : '') . '" for="' . esc_attr($input_id) . '" data-colour-code="' . esc_attr($code) . '" style="--product-colour:' . esc_attr($hex) . ';" onclick="' . esc_attr($label_onclick) . '">';
            echo '<span class="product-colour-option__swatch"></span>';
            echo '<span class="product-colour-option__meta">';
            echo '<span class="product-colour-option__code">' . esc_html($code) . '</span>';
            echo '<span class="product-colour-option__name">' . esc_html($label) . '</span>';
            if ($product_code !== '') {
                echo '<span class="product-colour-option__sku">' . esc_html($product_code) . '</span>';
            }
            echo '</span>';
            echo '</label>';
        }
        echo '</div>';
        echo '<div class="product-colour-picker__footer">';
        echo '<div class="product-colour-picker__current">Đang chọn: <strong data-colour-current>' . esc_html($current_label) . '</strong><span class="product-colour-picker__current-code">Mã đặt hàng: <strong data-colour-product-current>' . esc_html($default_product_code) . '</strong></span></div>';
        echo '<div class="product-colour-picker__hint">Mã này sẽ được đưa sang form liên hệ để cửa hàng chốt đúng màu.</div>';
        echo '</div>';
        echo '<input type="hidden" name="selected_colour_code" value="' . esc_attr($default_code) . '">';
        echo '<input type="hidden" name="selected_colour_name" value="' . esc_attr($default_name) . '">';
        echo '<input type="hidden" name="selected_colour_product_code" value="' . esc_attr($default_product_code) . '">';
        echo '</div>';
    }
}
add_action('woocommerce_single_product_summary', 'my_theme_render_single_product_colour_picker', 23);

if (!function_exists('my_theme_get_product_family_layout_scene_items')) {
    function my_theme_get_product_family_layout_scene_items($group_key = 'waterproofing', array $fallback_products = [], $limit = 3)
    {
        $limit = max(1, (int) $limit);
        $items = [];

        if (function_exists('my_theme_get_visual_story_items_by_group')) {
            $visual_items = my_theme_get_visual_story_items_by_group($group_key);
            if (!empty($visual_items)) {
                foreach (array_slice($visual_items, 0, $limit) as $visual_item) {
                    if (!is_array($visual_item)) {
                        continue;
                    }

                    $attachment_id = isset($visual_item['attachment_id']) ? (int) $visual_item['attachment_id'] : 0;
                    if ($attachment_id <= 0) {
                        continue;
                    }

                    $items[] = [
                        'attachment_id' => $attachment_id,
                        'caption' => isset($visual_item['caption']) ? trim((string) $visual_item['caption']) : '',
                        'badge' => 'Ảnh ứng dụng chống thấm',
                    ];
                }
            }
        }

        if (!empty($items)) {
            return $items;
        }

        foreach ($fallback_products as $fallback_product) {
            if (!$fallback_product instanceof WC_Product) {
                continue;
            }

            $fallback_media_state = function_exists('my_theme_get_product_card_media_state')
                ? my_theme_get_product_card_media_state($fallback_product)
                : [];
            $attachment_id = isset($fallback_media_state['thumb_id'])
                ? (int) $fallback_media_state['thumb_id']
                : (function_exists('my_theme_get_preferred_product_image_id')
                    ? (int) my_theme_get_preferred_product_image_id($fallback_product)
                    : (int) $fallback_product->get_image_id());
            if ($attachment_id <= 0) {
                continue;
            }

            $fallback_catalog_profile = function_exists('my_theme_get_product_catalog_profile')
                ? my_theme_get_product_catalog_profile($fallback_product)
                : [];
            $items[] = [
                'attachment_id' => $attachment_id,
                'caption' => isset($fallback_catalog_profile['display_name']) && (string) $fallback_catalog_profile['display_name'] !== ''
                    ? (string) $fallback_catalog_profile['display_name']
                    : (string) $fallback_product->get_name(),
                'badge' => 'Ảnh sản phẩm cùng dòng',
            ];

            if (count($items) >= $limit) {
                break;
            }
        }

        return $items;
    }
}

if (!function_exists('my_theme_render_single_product_family_layout')) {
    function my_theme_render_single_product_family_layout($prod = null)
    {
        if (!function_exists('is_product') || !is_product()) {
            return;
        }

        $product = function_exists('my_theme_resolve_product')
            ? my_theme_resolve_product($prod)
            : (($prod instanceof WC_Product) ? $prod : wc_get_product(get_the_ID()));
        if (!$product instanceof WC_Product) {
            return;
        }

        $current_slug = sanitize_title((string) $product->get_slug());
        if ($current_slug !== 'chatchongthamduluxaquatechtm3in1') {
            return;
        }

        $family_items = [
            [
                'slug' => 'chatchongthamduluxaquatech',
                'tag' => 'Hiệu quả',
                'focus' => 'Dòng chống thấm cơ bản, pha xi măng cho tường đứng.',
            ],
            [
                'slug' => 'chatchongthamduluxaquatechchongthamvuottroi',
                'tag' => 'Vượt trội',
                'focus' => 'Tăng cường khả năng chống thấm và độ đanh chắc bề mặt.',
            ],
            [
                'slug' => 'chatchongthamduluxaquatechtm3in1',
                'tag' => '3 trong 1',
                'focus' => 'Không cần sơn lót, chống loang kiềm và cho màng sáng đẹp.',
            ],
            [
                'slug' => 'chatchongthamduluxaquatechflexwaterproofing',
                'tag' => 'Flex',
                'focus' => 'Bản siêu co giãn cho yêu cầu bảo vệ cao hơn.',
            ],
        ];

        $family_products = [];
        $family_product_objects = [];
        $family_product_map = [];
        $family_slugs = array_values(array_filter(array_map(static function ($family_item) {
            return isset($family_item['slug']) ? sanitize_title((string) $family_item['slug']) : '';
        }, $family_items)));
        if (!empty($family_slugs) && function_exists('my_theme_get_products_by_slugs')) {
            foreach (my_theme_get_products_by_slugs($family_slugs) as $family_product) {
                if (!$family_product instanceof WC_Product) {
                    continue;
                }

                $family_product_map[sanitize_title((string) $family_product->get_slug())] = $family_product;
            }
        }

        foreach ($family_items as $family_item) {
            $family_slug = isset($family_item['slug']) ? sanitize_title((string) $family_item['slug']) : '';
            if ($family_slug === '') {
                continue;
            }

            $family_product = isset($family_product_map[$family_slug])
                ? $family_product_map[$family_slug]
                : my_theme_get_product_by_slug($family_slug);
            if (!$family_product instanceof WC_Product) {
                continue;
            }
            $family_product_objects[] = $family_product;
            $family_products[] = [
                'product' => $family_product,
                'tag' => (string) $family_item['tag'],
                'focus' => (string) $family_item['focus'],
            ];
        }

        if (count($family_products) < 4) {
            return;
        }

        $reference_swatches = my_theme_get_aquatech_3in1_colour_options();

        $scene_items = my_theme_get_product_family_layout_scene_items('waterproofing', $family_product_objects, 3);

        $fact_items = [
            [
                'label' => 'Độ phủ tham khảo',
                'value' => '6 - 7 m²/kg/lớp',
                'text' => 'Theo thông tin hãng, độ phủ thực tế sẽ thay đổi theo độ hút nước và độ phẳng của tường.',
            ],
            [
                'label' => 'Khô bề mặt',
                'value' => '1 - 2 giờ',
                'text' => 'Phù hợp khi cần chốt tiến độ nhanh cho hạng mục tường ngoài trời hoặc công trình sơn sửa.',
            ],
            [
                'label' => 'Số lớp đề nghị',
                'value' => '2 lớp',
                'text' => 'Nên đi đủ 2 lớp để màng chống thấm kín hơn và màu hoàn thiện đều hơn trên mảng tường đứng.',
            ],
            [
                'label' => 'Dụng cụ thi công',
                'value' => 'Cọ, rulô, máy phun',
                'text' => 'Dễ thích ứng với nhiều hiện trạng thi công, từ sửa nhà dân dụng đến mảng tường lớn ngoài trời.',
            ],
        ];

        $usage_cards = [
            [
                'eyebrow' => 'Công dụng chính',
                'title' => 'Chống nước mưa và hơi ẩm cho tường đứng ngoại thất',
                'text' => 'Aquatech 3in1 được tối ưu cho mặt đứng ngoài trời, giúp hạn chế nước thấm qua tường và giữ lớp màng bảo vệ ổn định hơn dưới điều kiện mưa nắng.',
            ],
            [
                'eyebrow' => 'Khu vực nên dùng',
                'title' => 'Mặt tiền, tường hông, logia phần tường đứng và tường rào',
                'text' => 'Phù hợp cho bề mặt xi măng, hồ vữa hoặc bê tông ngoài trời cần vừa chống thấm vừa có lớp hoàn thiện sáng đẹp ngay trên bề mặt.',
            ],
            [
                'eyebrow' => 'Lý do chọn 3in1',
                'title' => 'Không pha xi măng, không cần sơn lót trên nền phù hợp',
                'text' => 'Điểm mạnh của dòng 3in1 là rút gọn công đoạn, giảm thao tác trộn vật liệu và đồng thời hỗ trợ chống loang màu do hiện tượng kiềm hóa từ hồ vữa.',
            ],
        ];

        $application_notes = [
            'Tường mới nên khô 21 - 28 ngày hoặc có độ ẩm dưới 16% trước khi thi công để màng chống thấm ổn định hơn.',
            'Bề mặt cũ bị phấn hóa, bong yếu hoặc từng thấm nặng cần cạo bỏ lớp cũ và xử lý nền trước khi phủ lại.',
            'Aquatech 3in1 phù hợp cho tường đứng; nếu là sàn mái hoặc khu vực đọng nước thường xuyên, nên chuyển sang hệ chống thấm sàn chuyên dụng.',
        ];

        $application_steps = [
            [
                'title' => 'Làm sạch và ổn định bề mặt',
                'text' => 'Tẩy sạch màng sơn cũ yếu, bụi bẩn, vữa bám, rêu mốc và các mảng bong. Với khe nứt hoặc lỗ lớn, cần vá sửa lại trước khi phủ Aquatech 3in1.',
            ],
            [
                'title' => 'Kiểm tra độ ẩm và xử lý nền hút nước',
                'text' => 'Tường phải khô, ổn định. Những bề mặt hút nước mạnh hoặc quá khô nên được làm ẩm nhẹ bằng rulô sạch trước khi thi công để màng phủ bám đều hơn.',
            ],
            [
                'title' => 'Thi công lớp 1',
                'text' => 'Khuấy đều trước khi dùng. Theo hướng dẫn hãng, lớp đầu có thể pha loãng khoảng 10% với nước sạch rồi thi công bằng cọ, rulô hoặc máy phun.',
            ],
            [
                'title' => 'Thi công lớp 2 hoàn thiện',
                'text' => 'Sau khi bề mặt khô khoảng 1 - 2 giờ, thi công lớp tiếp theo với mức pha loãng tối đa 5%. Đi kỹ ở mép ban công, chân tường, vị trí hứng mưa và vùng từng loang ẩm.',
            ],
        ];

        echo '<section class="page-section product-family-layout product-family-layout--aquatech" aria-label="Thông tin sản phẩm Dulux Aquatech 3in1">';
        echo '<div class="section-heading section-heading--structured">';
        echo '<div class="section-heading__main">';
        echo '<p class="eyebrow eyebrow-muted">Dulux Aquatech 3in1</p>';
        echo '<h2 class="section-title">Thông tin sản phẩm và cách thi công</h2>';
        echo '<p class="section-sub">Phần này tập trung vào chính Aquatech 3in1: dùng ở đâu, khi nào nên chọn, cách thi công ra sao và những điểm cần chốt trước khi hỏi báo giá theo mã màu.</p>';
        echo '</div>';
        echo '<div class="section-heading__meta" aria-label="Điểm chính của Aquatech 3in1">';
        echo '<span class="section-heading__meta-item">Tường đứng ngoại thất</span>';
        echo '<span class="section-heading__meta-item">4 màu chuẩn đã có ở đầu trang</span>';
        echo '<span class="section-heading__meta-item">Có thêm 3 lựa chọn cùng dòng để so nhanh</span>';
        echo '</div>';
        echo '</div>';

        echo '<div class="product-family-layout__deep-dive">';
        echo '<div class="product-family-layout__fact-grid">';
        foreach ($fact_items as $fact_item) {
            echo '<article class="product-family-layout__fact-card">';
            echo '<span class="product-family-layout__fact-label">' . esc_html((string) $fact_item['label']) . '</span>';
            echo '<strong class="product-family-layout__fact-value">' . esc_html((string) $fact_item['value']) . '</strong>';
            echo '<p class="product-family-layout__fact-text">' . esc_html((string) $fact_item['text']) . '</p>';
            echo '</article>';
        }
        echo '</div>';

        echo '<div class="product-family-layout__insight-grid">';
        foreach ($usage_cards as $usage_card) {
            echo '<article class="product-family-layout__insight-card">';
            echo '<p class="product-family-layout__insight-eyebrow">' . esc_html((string) $usage_card['eyebrow']) . '</p>';
            echo '<h4 class="product-family-layout__insight-title">' . esc_html((string) $usage_card['title']) . '</h4>';
            echo '<p class="product-family-layout__insight-text">' . esc_html((string) $usage_card['text']) . '</p>';
            echo '</article>';
        }
        echo '</div>';

        echo '<div class="product-family-layout__application-note">';
        echo '<h4 class="product-family-layout__application-title">Lưu ý trước khi đặt và thi công</h4>';
        echo '<ul class="product-family-layout__application-list">';
        foreach ($application_notes as $application_note) {
            echo '<li>' . esc_html((string) $application_note) . '</li>';
        }
        echo '</ul>';
        echo '</div>';

        echo '<div class="product-family-layout__process">';
        echo '<div class="section-heading">';
        echo '<div>';
        echo '<h3 class="section-title">Quy trình thi công gợi ý cho tường đứng</h3>';
        echo '<p class="section-sub">Các bước dưới đây bám theo hướng dẫn hãng cho Aquatech 3in1 và được viết lại theo cách dễ đọc hơn để khách hoặc đội thi công nhìn là hiểu ngay.</p>';
        echo '</div>';
        echo '</div>';
        echo '<div class="product-family-layout__process-grid">';
        foreach ($application_steps as $index => $application_step) {
            echo '<article class="product-family-layout__process-card">';
            echo '<span class="product-family-layout__process-step">Bước ' . esc_html((string) ($index + 1)) . '</span>';
            echo '<h4 class="product-family-layout__process-title">' . esc_html((string) $application_step['title']) . '</h4>';
            echo '<p class="product-family-layout__process-text">' . esc_html((string) $application_step['text']) . '</p>';
            echo '</article>';
        }
        echo '</div>';
        echo '</div>';

        if (!empty($scene_items)) {
            echo '<div class="product-family-layout__scenes">';
            echo '<div class="section-heading">';
            echo '<div>';
            echo '<h3 class="section-title">Hình minh họa ứng dụng chống thấm</h3>';
            echo '<p class="section-sub">Ảnh tham khảo giúp khách hình dung rõ hơn các mảng tường, khu vực hứng mưa và hiện trạng cần xử lý trước khi chốt vật tư.</p>';
            echo '</div>';
            echo '</div>';
            echo '<div class="product-family-layout__scene-grid">';
            foreach ($scene_items as $scene_index => $scene_item) {
                $scene_attachment_id = isset($scene_item['attachment_id']) ? (int) $scene_item['attachment_id'] : 0;
                if ($scene_attachment_id <= 0) {
                    continue;
                }

                $scene_caption = trim((string) ($scene_item['caption'] ?? ''));
                $scene_badge = trim((string) ($scene_item['badge'] ?? ''));
                $scene_alt = $scene_caption !== '' ? $scene_caption : get_the_title($scene_attachment_id);
                $scene_card_class = 'product-family-layout__scene-card';
                if ($scene_index === 0) {
                    $scene_card_class .= ' product-family-layout__scene-card--feature';
                }

                echo '<article class="' . esc_attr($scene_card_class) . '">';
                echo '<div class="product-family-layout__scene-figure">';
                echo wp_get_attachment_image($scene_attachment_id, 'large', false, [
                    'loading' => 'lazy',
                    'decoding' => 'async',
                    'alt' => $scene_alt,
                ]);
                echo '</div>';
                echo '<div class="product-family-layout__scene-meta">';
                if ($scene_badge !== '') {
                    echo '<span class="product-family-layout__scene-badge">' . esc_html($scene_badge) . '</span>';
                }
                if ($scene_caption !== '') {
                    echo '<p class="product-family-layout__scene-caption">' . esc_html($scene_caption) . '</p>';
                }
                echo '</div>';
                echo '</article>';
            }
            echo '</div>';
            echo '</div>';
        }

        echo '<div class="product-family-layout__compare">';
        echo '<div class="section-heading section-heading--structured">';
        echo '<div class="section-heading__main">';
        echo '<h3 class="section-title">So sánh nhanh 4 loại Dulux Aquatech</h3>';
        echo '<p class="section-sub">Chỉ xem phần này khi bạn muốn đổi sang loại cơ bản hơn, chống thấm mạnh hơn hoặc cần bản co giãn cao hơn so với Aquatech 3in1.</p>';
        echo '</div>';
        echo '<div class="section-heading__meta" aria-label="Điểm chính của dòng Aquatech">';
        echo '<span class="section-heading__meta-item">Đã gom đủ 4 lựa chọn</span>';
        echo '<span class="section-heading__meta-item">Có loại 3in1 và loại Flex</span>';
        echo '<span class="section-heading__meta-item">Bấm vào từng thẻ để xem chi tiết</span>';
        echo '</div>';
        echo '</div>';

        echo '<div class="product-family-layout__grid">';
        foreach ($family_products as $family_entry) {
            $family_product = $family_entry['product'];
            $family_slug = sanitize_title((string) $family_product->get_slug());
            $is_current = $family_slug === $current_slug;
            $family_id = (int) $family_product->get_id();
            $family_catalog_profile = function_exists('my_theme_get_product_catalog_profile')
                ? my_theme_get_product_catalog_profile($family_product)
                : [];
            $family_name = isset($family_catalog_profile['display_name']) && (string) $family_catalog_profile['display_name'] !== ''
                ? (string) $family_catalog_profile['display_name']
                : (string) $family_product->get_name();
            $family_sku = trim((string) $family_product->get_sku());
            if ($family_sku === '') {
                $family_sku = (string) $family_id;
            }
            $family_media_state = function_exists('my_theme_get_product_card_media_state')
                ? my_theme_get_product_card_media_state($family_product)
                : [];
            $thumb_id = isset($family_media_state['thumb_id'])
                ? (int) $family_media_state['thumb_id']
                : (function_exists('my_theme_get_preferred_product_image_id')
                    ? (int) my_theme_get_preferred_product_image_id($family_product)
                    : (int) $family_product->get_image_id());
            $thumb_class = isset($family_media_state['thumb_class'])
                ? (string) $family_media_state['thumb_class']
                : 'product-card__thumb';
            $has_placeholder_thumb = !empty($family_media_state['has_placeholder']);

            echo '<article class="product-card product-family-layout__card' . ($is_current ? ' is-current' : '') . '">';
            echo '<a class="' . esc_attr($thumb_class) . '" href="' . esc_url($family_product->get_permalink()) . '" aria-label="' . esc_attr('Xem sản phẩm ' . $family_name) . '">';
            echo '<span class="product-family-layout__tag">' . esc_html((string) $family_entry['tag']) . '</span>';
            if ($thumb_id > 0 && !$has_placeholder_thumb) {
                echo wp_get_attachment_image($thumb_id, 'medium_large', false, [
                    'loading' => 'lazy',
                    'decoding' => 'async',
                    'alt' => $family_name,
                    'title' => $family_name,
                ]);
            } else {
                echo wc_placeholder_img('medium_large');
                echo '<span class="product-card__thumb-note">Ảnh sản phẩm đang cập nhật</span>';
            }
            echo '</a>';

            echo '<div class="product-card__body">';
            echo '<div class="product-family-layout__meta">';
            echo '<span class="product-family-layout__sku">Mã: ' . esc_html($family_sku) . '</span>';
            if ($is_current) {
                echo '<span class="product-family-layout__status">Đang xem</span>';
            }
            echo '</div>';
            echo '<h3 class="product-card__title"><a href="' . esc_url($family_product->get_permalink()) . '">' . esc_html($family_name) . '</a></h3>';
            echo '<p class="product-family-layout__focus">' . esc_html((string) $family_entry['focus']) . '</p>';
            echo '</div>';

            echo '<div class="product-card__actions product-card__actions--simple">';
            if ($is_current) {
                echo '<span class="btn btn-primary w-100 product-family-layout__current">Bạn đang xem loại này</span>';
            } else {
                echo '<a class="btn btn-outline w-100" href="' . esc_url($family_product->get_permalink()) . '">Xem sản phẩm này</a>';
            }
            echo '</div>';
            echo '</article>';
        }
        echo '</div>';
        echo '</div>';

        if (!empty($reference_swatches)) {
            echo '<div class="product-family-layout__palette product-family-layout__palette--compact">';
            echo '<div class="product-family-layout__palette-compact-head">';
            echo '<strong>4 màu chuẩn đã có ở thanh chọn phía trên.</strong>';
            echo '<span>Nếu cần báo giá nhanh, chọn mã ở đầu trang rồi bấm Gửi yêu cầu.</span>';
            echo '</div>';
            echo '</div>';
        }

        echo '</section>';
    }
}
