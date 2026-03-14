<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('my_theme_get_single_product_support_profiles')) {
    function my_theme_get_single_product_support_profiles()
    {
        $base = [
            'eyebrow' => 'Hướng dẫn thi công',
            'surface_value' => 'Bề mặt cần đối chiếu',
            'surface_text' => 'Nên đối chiếu theo bề mặt thực tế và hiện trạng công trình để chốt đúng hệ vật liệu.',
            'project_value' => 'Công trình dân dụng',
            'project_text' => 'Phù hợp cho các hạng mục cần chốt vật tư theo bề mặt, diện tích và điều kiện sử dụng cụ thể.',
            'tool_value' => 'Theo từng hệ thi công',
            'tool_text' => 'Dụng cụ và cách làm cần đối chiếu theo đúng nhóm sản phẩm, không nên áp một công thức cho mọi hạng mục.',
            'fit_title' => 'Công dụng và vai trò sản phẩm',
            'fit_text' => 'Sản phẩm này cần được đối chiếu theo đúng bề mặt, công trình và hiện trạng để chọn đủ hệ thi công.',
            'project_title' => 'Công trình và khu vực nên dùng',
            'advice_title' => 'Khi nào nên hỏi kỹ trước khi chốt',
            'advice_text' => 'Nếu công trình có nhiều lớp cũ, bề mặt ẩm, nứt, bong hoặc không rõ hệ đang dùng, nên gửi ảnh hiện trạng trước khi chốt đơn.',
            'note_title' => 'Lưu ý trước khi đặt và thi công',
            'notes' => [
                'Gửi ảnh hiện trạng, diện tích và mục tiêu sử dụng sẽ giúp chốt vật tư nhanh hơn.',
                'Không nên chốt sản phẩm chỉ theo tên gọi nếu chưa xác định rõ là tường, sàn, mái, kim loại hay gạch.',
                'Nếu công trình có lớp cũ yếu, cần xử lý nền trước khi thi công hệ mới.',
            ],
            'process_title' => 'Quy trình thi công gợi ý',
            'process_subtitle' => 'Phần này giúp khách nhìn rõ thứ tự xử lý nền, lớp lót và lớp phủ thay vì chỉ mua một mã rồi thi công theo cảm tính.',
            'steps' => [
                [
                    'title' => 'Kiểm tra bề mặt',
                    'text' => 'Xác định rõ loại bề mặt, mức độ cũ mới, độ ẩm và các điểm yếu trước khi chốt vật tư.',
                ],
                [
                    'title' => 'Chọn đúng hệ sản phẩm',
                    'text' => 'Đối chiếu thêm lớp lót, lớp xử lý nền hoặc vật liệu phụ trợ nếu công trình cần đi đủ hệ.',
                ],
                [
                    'title' => 'Thi công đúng quy trình',
                    'text' => 'Khuấy trộn và thi công theo từng mảng, giữ đúng nhịp thao tác và thời gian khô giữa các lớp.',
                ],
                [
                    'title' => 'Rà soát và hoàn thiện',
                    'text' => 'Kiểm tra lại mép cạnh, góc giao, điểm yếu và chỉ đưa vào sử dụng khi hệ vật liệu đã ổn định.',
                ],
            ],
            'gallery_title' => 'Hình minh họa công trình tham khảo',
            'gallery_subtitle' => 'Ảnh tham khảo được lấy theo nhóm bề mặt gần nhất để hỗ trợ khách hình dung phạm vi sử dụng.',
            'cta_title' => 'Gửi ảnh hiện trạng để cửa hàng chốt đúng hệ vật tư',
            'cta_lead' => 'Chỉ cần ảnh bề mặt, diện tích và nhu cầu sử dụng, cửa hàng sẽ đi tiếp nhanh hơn sang đúng nhóm sản phẩm.',
        ];

        return [
            'interior' => array_merge($base, [
                'eyebrow' => 'Hướng dẫn thi công nội thất',
                'surface_value' => 'Tường trong nhà',
                'surface_text' => 'Phù hợp bề mặt tường, trần, bột trét, hồ vữa hoặc bê tông đã ổn định, khô và xử lý sạch bụi.',
                'project_value' => 'Nhà ở, văn phòng',
                'project_text' => 'Thường dùng cho phòng khách, phòng ngủ, hành lang, showroom và khu sinh hoạt cần bề mặt đẹp, dễ vệ sinh.',
                'tool_value' => 'Cọ, rulô, máy phun',
                'tool_text' => 'Nên chọn dụng cụ theo diện tích thi công và yêu cầu độ mịn để màng sơn đều hơn.',
                'fit_text' => 'Dòng này phù hợp cho hệ sơn hoàn thiện trong nhà, ưu tiên thẩm mỹ, độ mịn và cảm giác sạch bề mặt.',
                'advice_text' => 'Nếu tường đang thấm ngược, còn ẩm, bề mặt cũ bong phấn hoặc cần đổi từ hệ sơn cũ sang hệ mới, nên gửi ảnh hiện trạng để chốt đủ lớp.',
                'note_title' => 'Lưu ý trước khi thi công nội thất',
                'notes' => [
                    'Tường mới nên đạt độ khô ổn định trước khi phủ hoàn thiện để màu và độ bám dính lên đúng hơn.',
                    'Nếu bề mặt cũ bị phấn hóa hoặc bong yếu, cần cạo bỏ lớp yếu và xử lý lại nền trước khi sơn.',
                    'Với khu vực ẩm cao như gần nhà tắm, nên kiểm tra lại hiện trạng để tránh bong màng sớm.',
                ],
                'process_title' => 'Quy trình thi công gợi ý cho bề mặt nội thất',
                'gallery_title' => 'Hình minh họa ứng dụng nội thất',
                'gallery_subtitle' => 'Ảnh tham khảo giúp khách dễ hình dung bề mặt, ánh sáng và kiểu không gian thường dùng với nhóm sản phẩm này.',
                'cta_title' => 'Gửi ảnh tường và diện tích để chốt nhanh hệ sơn nội thất',
            ]),
            'exterior' => array_merge($base, [
                'eyebrow' => 'Hướng dẫn thi công ngoại thất',
                'surface_value' => 'Mặt tiền, tường ngoài trời',
                'surface_text' => 'Phù hợp bề mặt xi măng, hồ vữa, bê tông hoặc tường ngoài trời đã xử lý sạch rong rêu, bụi bẩn và lớp cũ bong.',
                'project_value' => 'Nhà phố, công trình dân dụng',
                'project_text' => 'Thường dùng cho mặt tiền, tường hông, ban công, tường rào và các mảng ngoài trời cần bền màu dưới nắng mưa.',
                'tool_value' => 'Cọ, rulô, máy phun',
                'tool_text' => 'Nên canh thời tiết khô ráo, tránh thi công lúc nắng gắt hoặc trước mưa để bề mặt ổn định hơn.',
                'fit_text' => 'Nhóm sản phẩm này phục vụ lớp phủ ngoại thất, tập trung vào độ bền màu, khả năng bảo vệ bề mặt và tính ổn định dưới thời tiết thực tế.',
                'advice_text' => 'Nếu mặt tường từng loang kiềm, nứt chân chim, thấm ngang hoặc bong lớp cũ nhiều, cần đối chiếu thêm lớp lót và xử lý nền trước khi phủ.',
                'note_title' => 'Lưu ý trước khi thi công ngoại thất',
                'notes' => [
                    'Không thi công khi tường còn ẩm cao, trời sắp mưa hoặc bề mặt đang đọng nước.',
                    'Mảng tường cũ có rong rêu, nấm mốc hoặc loang muối cần được xử lý triệt để trước khi phủ lại.',
                    'Nên kiểm tra kỹ chân tường, mép cửa sổ và các vị trí nứt để tránh xuống cấp lại nhanh.',
                ],
                'process_title' => 'Quy trình thi công gợi ý cho ngoại thất',
                'gallery_title' => 'Hình minh họa mặt tiền và tường ngoài trời',
                'gallery_subtitle' => 'Ảnh tham khảo cho các hạng mục ngoại thất thường gặp để khách dễ đối chiếu với công trình thực tế.',
                'cta_title' => 'Gửi ảnh mặt tiền để chốt nhanh mã phủ, lớp lót và định mức',
            ]),
            'waterproofing' => array_merge($base, [
                'eyebrow' => 'Hướng dẫn chống thấm',
                'surface_value' => 'Tường, mái, sàn, khu ẩm',
                'surface_text' => 'Phù hợp các bề mặt có nguy cơ thấm như tường đứng, sân thượng, mái bằng, chân tường hoặc bê tông ngoài trời, tùy đúng hệ sản phẩm.',
                'project_value' => 'Công trình cần xử lý thấm',
                'project_text' => 'Thường dùng cho sân thượng, tường ngoài trời, khu ẩm, logia, mái và các mảng bê tông hoặc hồ vữa cần ngăn nước xâm nhập.',
                'tool_value' => 'Cọ, rulô, bay, máy phun',
                'tool_text' => 'Chọn đúng dụng cụ theo hệ chống thấm sẽ giúp lớp vật liệu bám đều hơn và đạt độ dày màng ổn định hơn.',
                'fit_text' => 'Nhóm này tập trung vào bảo vệ bề mặt trước nước mưa, hơi ẩm và các điểm dễ phát sinh thấm, từ đó giảm bong tróc lớp hoàn thiện phía ngoài.',
                'advice_text' => 'Nếu khu vực là sàn mái đọng nước, khe nứt lớn, mạch ngừng bê tông hoặc chân tường thấm nặng, nên gửi ảnh để đối chiếu đúng hệ chuyên dụng.',
                'note_title' => 'Lưu ý trước khi chống thấm',
                'notes' => [
                    'Không thi công khi bề mặt còn ẩm đọng nước, bụi xi măng chưa làm sạch hoặc nền chưa ổn định.',
                    'Các khe nứt, lỗ rỗ và điểm yếu cần được sửa chữa trước khi phủ lớp chống thấm đại trà.',
                    'Cần phân biệt rõ tường đứng, sàn mái, khu vực đọng nước hay chỉ hứng mưa để chốt đúng hệ và đúng số lớp.',
                ],
                'process_title' => 'Quy trình chống thấm gợi ý',
                'gallery_title' => 'Hình minh họa hạng mục chống thấm',
                'gallery_subtitle' => 'Ảnh tham khảo cho tường, mái, sân thượng và các khu vực ẩm để khách hình dung đúng phạm vi sử dụng.',
                'cta_title' => 'Gửi ảnh vị trí thấm để chốt nhanh đúng hệ chống thấm',
            ]),
            'epoxy' => array_merge($base, [
                'eyebrow' => 'Hướng dẫn thi công epoxy',
                'surface_value' => 'Sàn bê tông',
                'surface_text' => 'Phù hợp nền bê tông, sàn xưởng nhỏ, gara, kho hoặc khu vực cần bề mặt dễ vệ sinh, ổn định và thi công theo hệ sơn sàn.',
                'project_value' => 'Kho, gara, xưởng nhỏ',
                'project_text' => 'Thường dùng cho nền nhà xưởng nhỏ, gara gia đình, kho hàng, khu kỹ thuật và mặt sàn cần dễ lau chùi hoặc chịu tải vừa.',
                'tool_value' => 'Máy mài, rulô, bàn cao su',
                'tool_text' => 'Thi công epoxy cần chú ý chuẩn bị bề mặt và đúng quy trình pha trộn, vì chất lượng nền ảnh hưởng trực tiếp tới độ bám dính của hệ.',
                'fit_text' => 'Nhóm epoxy tập trung vào xử lý và phủ sàn, hỗ trợ bề mặt sạch hơn, dễ vệ sinh hơn và đồng bộ hơn cho khu vực vận hành.',
                'advice_text' => 'Nếu nền còn ẩm, bám dầu, có vết nứt, cần chống trơn hoặc chịu tải cao, nên chốt đủ hệ primer, lớp giữa và lớp phủ thay vì mua rời từng thành phần.',
                'note_title' => 'Lưu ý trước khi thi công epoxy',
                'notes' => [
                    'Độ ẩm nền và chất lượng xử lý bề mặt là yếu tố quyết định lớn tới độ bền của hệ epoxy.',
                    'Cần kiểm soát thời gian sống của vật liệu sau khi pha trộn để tránh thi công khi vật liệu đã phản ứng mạnh.',
                    'Những khu vực yêu cầu chống trượt, chịu tải hoặc chịu hóa chất cần được chốt hệ riêng trước khi báo giá.',
                ],
                'process_title' => 'Quy trình thi công gợi ý cho sàn epoxy',
                'gallery_title' => 'Hình minh họa sàn epoxy và khu kỹ thuật',
                'gallery_subtitle' => 'Ảnh tham khảo cho các mặt sàn thường gặp để khách dễ hình dung hơn trước khi chốt hệ epoxy.',
                'cta_title' => 'Gửi ảnh nền sàn để chốt nhanh đúng hệ epoxy',
            ]),
            'metal' => array_merge($base, [
                'eyebrow' => 'Hướng dẫn thi công kim loại',
                'surface_value' => 'Cửa sắt, lan can, cổng',
                'surface_text' => 'Phù hợp cho các bề mặt kim loại cần xử lý rỉ, khóa nền và phủ hoàn thiện đúng hệ trước khi đưa vào môi trường trong nhà hoặc ngoài trời.',
                'project_value' => 'Hàng rào, khung sắt, lan can',
                'project_text' => 'Thường dùng cho cửa sắt, cổng, hàng rào, lan can, khung thép nhẹ và các chi tiết kim loại cần lớp phủ đồng bộ hơn.',
                'tool_value' => 'Bàn chải sắt, cọ, súng phun',
                'tool_text' => 'Khâu làm sạch rỉ và chọn đúng lớp primer quan trọng không kém lớp màu phủ ngoài.',
                'fit_text' => 'Nhóm này tập trung vào xử lý nền kim loại, hỗ trợ hạn chế rỉ tái phát và giúp bề mặt đồng màu, sạch và bền hơn theo điều kiện sử dụng.',
                'advice_text' => 'Nếu kim loại đã rỉ nặng, đang ở môi trường ngoài trời hoặc gần biển, cần chốt đủ bộ xử lý rỉ, lót và phủ thay vì chỉ chọn một lớp màu hoàn thiện.',
                'note_title' => 'Lưu ý trước khi thi công kim loại',
                'notes' => [
                    'Bề mặt kim loại phải được làm sạch dầu mỡ, bụi bẩn và lớp rỉ bong trước khi thi công primer.',
                    'Mức độ rỉ nặng hay nhẹ sẽ quyết định công đoạn xử lý nền và số lớp lót cần đi.',
                    'Ngoài trời hoặc môi trường ẩm cần ưu tiên hệ có khả năng bảo vệ cao hơn so với điều kiện trong nhà.',
                ],
                'process_title' => 'Quy trình thi công gợi ý cho kim loại',
                'gallery_title' => 'Hình minh họa cửa sắt, cổng và lan can',
                'gallery_subtitle' => 'Ảnh tham khảo cho các bề mặt kim loại thường gặp để khách so nhanh với hiện trạng công trình của mình.',
                'cta_title' => 'Gửi ảnh cửa sắt hoặc khung thép để chốt đúng hệ sơn',
            ]),
            'grout' => array_merge($base, [
                'eyebrow' => 'Hướng dẫn thi công keo và ron',
                'surface_value' => 'Gạch, ron, khu ốp lát',
                'surface_text' => 'Phù hợp khu vực ốp lát, khe ron, tường gạch, sàn gạch và các hạng mục cần chốt đúng vật liệu theo loại gạch và khu vực sử dụng.',
                'project_value' => 'Nhà tắm, bếp, ban công',
                'project_text' => 'Thường dùng cho nhà tắm, bếp, khu ẩm, ban công, sân và các bề mặt gạch cần dán, chà ron hoặc trám khe đúng vai trò.',
                'tool_value' => 'Bay răng cưa, bay cao su',
                'tool_text' => 'Nên chọn đúng dụng cụ theo công đoạn dán gạch hay chà ron để thao tác đều hơn và kiểm soát độ dày lớp vật liệu tốt hơn.',
                'fit_text' => 'Nhóm này hỗ trợ các hạng mục ốp lát, dán gạch, chà ron hoặc trám khe, ưu tiên độ bám, độ kín khe và độ ổn định theo khu vực sử dụng.',
                'advice_text' => 'Nếu công trình ở khu ẩm, ngoài trời, gạch khổ lớn hoặc khe ron đặc biệt, nên gửi thêm ảnh và thông số gạch để tránh chọn sai loại keo hoặc ron.',
                'note_title' => 'Lưu ý trước khi thi công keo và ron',
                'notes' => [
                    'Cần biết loại gạch, kích thước gạch, bề mặt nền và khu vực sử dụng trước khi chốt vật tư.',
                    'Khe ron phải sạch và đủ độ sâu theo khuyến nghị thì vật liệu mới phát huy đúng vai trò.',
                    'Nhà tắm, khu ẩm và khu ngoài trời cần ưu tiên nhóm vật liệu phù hợp hơn so với khu khô thông thường.',
                ],
                'process_title' => 'Quy trình thi công gợi ý cho keo và ron',
                'gallery_title' => 'Hình minh họa khu vực ốp lát và ron gạch',
                'gallery_subtitle' => 'Ảnh tham khảo cho nhà tắm, bếp và bề mặt gạch để khách dễ chọn đúng nhóm vật tư hơn.',
                'cta_title' => 'Gửi ảnh gạch, khe ron và khu vực thi công để chốt nhanh vật tư',
            ]),
            'default' => $base,
        ];
    }
}

if (!function_exists('my_theme_get_single_product_support_profile')) {
    function my_theme_get_single_product_support_profile($product = null, $group_key = '')
    {
        $product = function_exists('my_theme_resolve_product')
            ? my_theme_resolve_product($product)
            : (($product instanceof WC_Product) ? $product : wc_get_product(get_the_ID()));
        if (!$product instanceof WC_Product) {
            return [];
        }

        $group_key = sanitize_key((string) $group_key);
        if ($group_key === '' && function_exists('my_theme_get_visual_story_group_key_for_object')) {
            $group_key = sanitize_key((string) my_theme_get_visual_story_group_key_for_object($product));
        }

        $profiles = my_theme_get_single_product_support_profiles();
        $profile = ($group_key !== '' && isset($profiles[$group_key]) && is_array($profiles[$group_key]))
            ? $profiles[$group_key]
            : $profiles['default'];

        $line_slug = function_exists('my_theme_get_product_line_slug')
            ? sanitize_title((string) my_theme_get_product_line_slug($product))
            : '';
        $product_slug = sanitize_title((string) $product->get_slug());

        $line_overrides = [
            'line-primer' => [
                'eyebrow' => 'Tư vấn lớp lót',
                'surface_value' => 'Tường mới, nền hút nước, bột trét',
                'tool_value' => 'Cọ, rulô, máy phun',
                'lead_text' => 'Đây là lớp lót cho hệ sơn, có vai trò khóa nền, giảm hút nước và hỗ trợ lớp phủ bám đều hơn; không phải lớp hoàn thiện cuối cùng.',
                'process_title' => 'Quy trình đi lớp lót gợi ý',
            ],
            'line-epoxy' => [
                'eyebrow' => 'Tư vấn hệ sơn epoxy',
                'surface_value' => 'Sàn bê tông cần phủ hệ',
                'tool_value' => 'Máy mài, rulô, bàn cao su',
                'lead_text' => 'Đây là dòng vật liệu cho hệ sơn sàn epoxy, cần chốt theo cấu tạo sàn, độ ẩm nền và yêu cầu sử dụng chứ không nên nhìn như một lon sơn lẻ.',
            ],
            'line-metal' => [
                'eyebrow' => 'Tư vấn hệ sơn kim loại',
                'surface_value' => 'Kim loại, cửa sắt, khung thép',
                'tool_value' => 'Bàn chải sắt, cọ, súng phun',
                'lead_text' => 'Nhóm này dành cho bề mặt kim loại, cần đi đúng thứ tự xử lý rỉ, lót và phủ để bề mặt bền hơn ngoài thực tế.',
            ],
            'easyclean' => [
                'eyebrow' => 'Tư vấn dòng EasyClean',
                'lead_text' => 'EasyClean phù hợp cho tường nội thất cần lau chùi tốt hơn ở khu sinh hoạt hằng ngày, nhất là phòng khách, phòng ngủ trẻ em và hành lang.',
                'project_text' => 'Thường dùng cho căn hộ, nhà phố và không gian sinh hoạt có tần suất chạm tay, bám bẩn nhẹ hoặc cần dọn vệ sinh nhanh.',
                'advice_text' => 'Nếu tường đã sơn bóng cũ, có nhiều vá sửa hoặc từng loang ẩm, nên kiểm tra nền và lớp lót trước để độ đồng đều mặt sơn đẹp hơn.',
            ],
            'nanoshield' => [
                'eyebrow' => 'Tư vấn dòng NanoShield',
                'lead_text' => 'NanoShield phù hợp cho lớp phủ nội thất phổ thông cần bề mặt sáng, ổn định và chi phí vật tư dễ kiểm soát hơn.',
                'project_text' => 'Phù hợp căn hộ, nhà phố, phòng ngủ, khu làm việc và các mảng tường trong nhà cần hoàn thiện nhanh, gọn và dễ bảo trì.',
            ],
            'weathershield' => [
                'eyebrow' => 'Tư vấn dòng Weathershield',
                'lead_text' => 'Weathershield hướng tới lớp phủ ngoại thất cho mặt tiền và tường ngoài trời, ưu tiên độ bền màu, độ ổn định màng sơn và khả năng chống bám bẩn tốt hơn.',
            ],
            'aquatech' => [
                'eyebrow' => 'Tư vấn dòng Aquatech',
                'lead_text' => 'Aquatech là nhóm chống thấm tường và bề mặt ngoài trời, cần chốt đúng từng mã trong dòng để biết đây là bản cơ bản, vượt trội, 3in1 hay Flex.',
                'advice_text' => 'Nếu đang phân vân giữa tường đứng, sàn mái hay khu vực đọng nước, nên gửi ảnh hiện trạng để chọn đúng mã Aquatech hoặc chuyển sang hệ chuyên dụng hơn.',
            ],
            'toa1000' => [
                'eyebrow' => 'Tư vấn lớp lót TOA 1000',
                'surface_value' => 'Tường mới, bột trét, nền hút nước',
                'tool_value' => 'Cọ, rulô, máy phun',
                'lead_text' => 'TOA 1000 là lớp lót kháng kiềm cho tường mới hoặc nền hút nước, không phải lớp hoàn thiện cuối nhưng ảnh hưởng trực tiếp tới độ bền của lớp phủ sau đó.',
                'process_title' => 'Quy trình đi lớp lót kháng kiềm gợi ý',
                'notes' => [
                    'Không dùng sơn lót như lớp trang trí hoàn thiện cuối cùng.',
                    'Bề mặt cần khô, sạch và ổn định trước khi đi lót.',
                    'Sau lớp lót cần chờ khô đúng thời gian rồi mới phủ màu hoàn thiện.',
                ],
            ],
            'line-putty' => [
                'eyebrow' => 'Tư vấn bột trét và làm phẳng nền',
                'surface_value' => 'Tường mới cần làm phẳng',
                'tool_value' => 'Bay thép, bàn xoa, giấy nhám',
                'lead_text' => 'Bột trét dùng để xử lý độ phẳng và độ mịn của nền trước khi sơn lót và sơn phủ, không phải lớp trang trí cuối cùng.',
                'process_title' => 'Quy trình bả và làm phẳng nền gợi ý',
                'notes' => [
                    'Chỉ bả trên nền đủ khô và đủ cứng.',
                    'Không để lớp bả quá dày ở một lần thi công.',
                    'Sau khi xả nhám cần làm sạch bụi kỹ trước khi đi lót.',
                ],
            ],
            'rusttech' => [
                'eyebrow' => 'Tư vấn dòng Rust Tech',
                'surface_value' => 'Kim loại có nguy cơ rỉ',
                'tool_value' => 'Bàn chải sắt, cọ, súng phun',
                'lead_text' => 'Rust Tech là lớp sơn lót/primer cho kim loại, tập trung xử lý và khóa nền rỉ trước khi phủ lớp màu hoàn thiện.',
                'advice_text' => 'Nếu đang chốt sơn cho cửa sắt ngoài trời, nên tính đủ bộ xử lý rỉ, lớp lót và lớp phủ chứ không chỉ mua riêng primer.',
            ],
            'sikaceram' => [
                'eyebrow' => 'Tư vấn dòng SikaCeram',
                'surface_value' => 'Nền và tường ốp lát gạch',
                'tool_value' => 'Bay răng cưa, bay cao su',
                'lead_text' => 'SikaCeram là keo dán gạch, phù hợp hạng mục ốp lát cần bám dính ổn định hơn theo loại gạch, vị trí dùng và kích thước viên.',
                'process_title' => 'Quy trình dán gạch gợi ý',
                'notes' => [
                    'Cần xác định loại gạch, kích thước gạch và khu vực dùng trước khi chốt mã.',
                    'Không trét keo quá rộng khi chưa kịp dán gạch.',
                    'Nên dùng bay răng cưa đúng cỡ để kiểm soát độ dày lớp keo.',
                ],
            ],
            'sikaflex' => [
                'eyebrow' => 'Tư vấn dòng SikaFlex',
                'surface_value' => 'Khe nối, khe co giãn, khe cửa',
                'tool_value' => 'Súng bắn keo, dao miết',
                'lead_text' => 'SikaFlex là keo trám khe cho các khe nối và khe co giãn, phù hợp hạng mục cửa, mặt dựng, bê tông hoặc khe kỹ thuật cần độ bám và độ đàn hồi.',
                'process_title' => 'Quy trình bắn keo trám khe gợi ý',
            ],
            'sikafloor' => [
                'eyebrow' => 'Tư vấn dòng SikaFloor',
                'lead_text' => 'SikaFloor là hệ vật liệu cho sàn epoxy hoặc sàn công nghiệp, cần chốt đúng cấu hình theo độ ẩm nền, tải trọng và mức hoàn thiện mong muốn.',
                'notes' => [
                    'Không bỏ qua bước mài nền và primer khi thi công sàn.',
                    'Cần kiểm soát tỉ lệ pha và thời gian sử dụng sau khi trộn.',
                    'Nên xác định rõ sàn kho, gara, xưởng hay khu kỹ thuật để chốt đúng hệ.',
                ],
            ],
            'sikalatex' => [
                'eyebrow' => 'Tư vấn dòng SikaLatex',
                'surface_value' => 'Vữa sửa chữa, hồ dầu, lớp kết nối',
                'tool_value' => 'Xô trộn, cọ, bay thép',
                'lead_text' => 'SikaLatex là phụ gia kết nối và tăng bám dính cho vữa/hồ dầu, phù hợp xử lý sửa chữa, trát vá hoặc tăng liên kết giữa lớp cũ và lớp mới.',
                'process_title' => 'Quy trình pha và dùng phụ gia kết nối gợi ý',
            ],
            'sikatop' => [
                'eyebrow' => 'Tư vấn dòng SikaTop',
                'lead_text' => 'SikaTop thường dùng cho hệ chống thấm gốc xi măng, phù hợp bể nước, sân thượng, nhà vệ sinh hoặc tường ngoài trời cần lớp chống thấm chắc hơn.',
            ],
            'sikaguard' => [
                'eyebrow' => 'Tư vấn dòng SikaGuard',
                'lead_text' => 'SikaGuard phù hợp cho bề mặt ngoài trời hoặc façade cần lớp bảo vệ bề mặt, giảm hút nước và ổn định bề mặt trước thời tiết.',
            ],
            'sikagrout' => [
                'eyebrow' => 'Tư vấn dòng SikaGrout',
                'surface_value' => 'Bệ máy, chân đế, khe hở kỹ thuật',
                'tool_value' => 'Máy trộn chậm, máng rót',
                'lead_text' => 'SikaGrout là vữa rót không co ngót, dùng cho chân đế máy, bản mã, khe hở hoặc vị trí cần rót đầy và ổn định thể tích.',
                'process_title' => 'Quy trình rót vữa không co ngót gợi ý',
            ],
            'a100' => [
                'eyebrow' => 'Tư vấn Apollo A100',
                'surface_value' => 'Khe hở trong nhà, khe nhỏ ít chuyển vị',
                'tool_value' => 'Súng bắn keo, dao miết',
                'lead_text' => 'Apollo A100 là keo acrylic phù hợp cho khe nội thất, khe tiếp giáp nhỏ và vị trí có thể sơn phủ lại sau khi trám.',
            ],
            'a200' => [
                'eyebrow' => 'Tư vấn Apollo A200',
                'surface_value' => 'Khe kính, nhôm, cửa và khe hoàn thiện',
                'tool_value' => 'Súng bắn keo, dao miết',
                'lead_text' => 'Apollo A200 phù hợp các khe hoàn thiện thông dụng cho nhôm kính và nội thất, cần bề mặt sạch và khe được xử lý gọn trước khi bắn keo.',
            ],
            'a300' => [
                'eyebrow' => 'Tư vấn Apollo A300',
                'surface_value' => 'Khe kính, nhôm, bề mặt hoàn thiện',
                'tool_value' => 'Súng bắn keo, dao miết',
                'lead_text' => 'Apollo A300 là keo silicone cho các khe hoàn thiện phổ biến, phù hợp khi cần thao tác nhanh và bề mặt trám gọn, sạch.',
            ],
            'a500' => [
                'eyebrow' => 'Tư vấn Apollo A500',
                'surface_value' => 'Khe nối ngoài trời, khe chịu thời tiết',
                'tool_value' => 'Súng bắn keo, dao miết',
                'lead_text' => 'Apollo A500 phù hợp các khe nối cần độ bền thời tiết tốt hơn, nhất là khu vực cửa, nhôm kính và mặt ngoài công trình.',
            ],
            'a600' => [
                'eyebrow' => 'Tư vấn Apollo A600',
                'surface_value' => 'Khe nối ngoài trời, khe kỹ thuật',
                'tool_value' => 'Súng bắn keo, dao miết',
                'lead_text' => 'Apollo A600 hướng tới các khe nối yêu cầu cao hơn về độ bám và độ ổn định khi dùng cho hạng mục ngoài trời hoặc công trình kỹ thuật.',
            ],
            'sanitary-n' => [
                'eyebrow' => 'Tư vấn Apollo Sanitary-N',
                'surface_value' => 'Nhà tắm, bếp, lavabo, thiết bị vệ sinh',
                'tool_value' => 'Súng bắn keo, dao miết',
                'lead_text' => 'Apollo Sanitary-N phù hợp cho khu vệ sinh, lavabo, bồn rửa và các vị trí ẩm thường xuyên cần đường keo gọn, sạch và phù hợp môi trường ẩm.',
            ],
            'weatherseal-a68' => [
                'eyebrow' => 'Tư vấn Apollo Weatherseal A68',
                'surface_value' => 'Khe mặt dựng, khe ngoài trời',
                'tool_value' => 'Súng bắn keo, dao miết',
                'lead_text' => 'Weatherseal A68 phù hợp các khe ngoài trời, cửa và mặt dựng cần chống thời tiết tốt hơn so với keo hoàn thiện trong nhà.',
            ],
            'weatherseal-a79' => [
                'eyebrow' => 'Tư vấn Apollo Weatherseal A79',
                'surface_value' => 'Khe mặt dựng, khe ngoài trời',
                'tool_value' => 'Súng bắn keo, dao miết',
                'lead_text' => 'Weatherseal A79 phù hợp khe ngoài trời, cửa và vị trí cần độ ổn định tốt dưới nắng mưa, nhất là công trình có nhiều mối nối hở.',
            ],
        ];

        $token_overrides = [
            'weatherproof' => [
                'eyebrow' => 'Tư vấn lớp phủ chống thời tiết',
                'lead_text' => 'Dòng Weatherproof hướng tới tường ngoài trời và mặt tiền cần lớp phủ ổn định hơn trước nắng mưa và bụi bẩn bám mặt.',
            ],
            'waterproof-201' => [
                'eyebrow' => 'Tư vấn TOA Waterproof 201',
                'lead_text' => 'TOA Waterproof 201 phù hợp hạng mục chống thấm thông dụng, cần đối chiếu rõ đây là tường đứng, sân thượng hay khu vực ẩm để chốt đúng cách thi công.',
            ],
            'monotop' => [
                'eyebrow' => 'Tư vấn Sika MonoTop',
                'surface_value' => 'Bê tông cần sửa chữa cục bộ',
                'tool_value' => 'Bay thép, bàn xoa',
                'lead_text' => 'Sika MonoTop phù hợp sửa chữa bê tông cục bộ, vá cạnh, vá góc hoặc khôi phục bề mặt hư hỏng trước khi đưa vào các lớp tiếp theo.',
                'process_title' => 'Quy trình sửa chữa bê tông gợi ý',
            ],
        ];

        if ($line_slug !== '' && isset($line_overrides[$line_slug]) && is_array($line_overrides[$line_slug])) {
            $profile = array_merge($profile, $line_overrides[$line_slug]);
        }

        if ($product_slug !== '') {
            foreach ($token_overrides as $token => $override) {
                if ($token === '' || strpos($product_slug, $token) === false || !is_array($override)) {
                    continue;
                }
                $profile = array_merge($profile, $override);
                break;
            }
        }

        return $profile;
    }
}

if (!function_exists('my_theme_get_single_product_support_gallery_items')) {
    function my_theme_get_single_product_support_gallery_items($product = null, $group_key = '', $limit = 3)
    {
        $product = function_exists('my_theme_resolve_product')
            ? my_theme_resolve_product($product)
            : (($product instanceof WC_Product) ? $product : wc_get_product(get_the_ID()));
        if (!$product instanceof WC_Product) {
            return [];
        }

        $limit = max(1, (int) $limit);
        $group_key = sanitize_key((string) $group_key);
        $items = [];
        $seen_attachment_ids = [];

        if ($group_key !== '' && function_exists('my_theme_get_visual_story_items_by_group')) {
            foreach (my_theme_get_visual_story_items_by_group($group_key) as $visual_item) {
                $attachment_id = isset($visual_item['attachment_id']) ? (int) $visual_item['attachment_id'] : 0;
                if ($attachment_id <= 0 || in_array($attachment_id, $seen_attachment_ids, true)) {
                    continue;
                }

                $seen_attachment_ids[] = $attachment_id;
                $items[] = [
                    'attachment_id' => $attachment_id,
                    'badge' => 'Ảnh công trình tham khảo',
                    'caption' => isset($visual_item['caption']) ? trim((string) $visual_item['caption']) : '',
                ];

                if (count($items) >= $limit) {
                    return $items;
                }
            }
        }

        $catalog_profile = function_exists('my_theme_get_product_catalog_profile')
            ? my_theme_get_product_catalog_profile($product)
            : [];
        $product_name = isset($catalog_profile['display_name']) && (string) $catalog_profile['display_name'] !== ''
            ? trim((string) $catalog_profile['display_name'])
            : trim((string) $product->get_name());
        $image_ids = [];
        $media_state = function_exists('my_theme_get_product_card_media_state')
            ? my_theme_get_product_card_media_state($product)
            : [];
        $preferred_id = isset($media_state['thumb_id'])
            ? (int) $media_state['thumb_id']
            : (function_exists('my_theme_get_preferred_product_image_id')
                ? (int) my_theme_get_preferred_product_image_id($product)
                : (int) $product->get_image_id());
        if ($preferred_id > 0) {
            $image_ids[] = $preferred_id;
        }
        foreach ((array) $product->get_gallery_image_ids() as $gallery_id) {
            $gallery_id = (int) $gallery_id;
            if ($gallery_id > 0) {
                $image_ids[] = $gallery_id;
            }
        }

        foreach (array_unique($image_ids) as $attachment_id) {
            if ($attachment_id <= 0 || in_array($attachment_id, $seen_attachment_ids, true) || !wp_attachment_is_image($attachment_id)) {
                continue;
            }

            $items[] = [
                'attachment_id' => $attachment_id,
                'badge' => 'Ảnh sản phẩm',
                'caption' => $product_name !== '' ? $product_name : get_the_title($attachment_id),
            ];

            if (count($items) >= $limit) {
                break;
            }
        }

        return array_slice($items, 0, $limit);
    }
}

if (!function_exists('my_theme_render_single_product_support_layout')) {
    function my_theme_render_single_product_support_layout($prod = null)
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

        if (sanitize_title((string) $product->get_slug()) === 'chatchongthamduluxaquatechtm3in1') {
            return;
        }

        $group_key = function_exists('my_theme_get_visual_story_group_key_for_object')
            ? sanitize_key((string) my_theme_get_visual_story_group_key_for_object($product))
            : '';
        $profile = my_theme_get_single_product_support_profile($product, $group_key);
        if (empty($profile)) {
            return;
        }

        $catalog_profile = function_exists('my_theme_get_product_catalog_profile')
            ? my_theme_get_product_catalog_profile($product)
            : [];
        $display_name = isset($catalog_profile['display_name']) && (string) $catalog_profile['display_name'] !== ''
            ? trim((string) $catalog_profile['display_name'])
            : trim((string) $product->get_name());
        $brand_label = isset($catalog_profile['brand_label']) ? trim((string) $catalog_profile['brand_label']) : '';
        if ($brand_label === 'Sản phẩm') {
            $brand_label = '';
        }
        $line_label = isset($catalog_profile['line_label']) ? trim((string) $catalog_profile['line_label']) : '';
        $category_label = isset($catalog_profile['category_label']) ? trim((string) $catalog_profile['category_label']) : '';
        $category_id = isset($catalog_profile['category_id']) ? (int) $catalog_profile['category_id'] : 0;
        $category_intro = function_exists('my_theme_get_category_intro')
            ? trim((string) my_theme_get_category_intro($category_id))
            : '';
        $usage_excerpt = function_exists('my_theme_get_product_card_excerpt')
            ? trim((string) my_theme_get_product_card_excerpt($product, 32))
            : '';
        $short_description = trim((string) wp_strip_all_tags($product->get_short_description()));
        $full_description = trim((string) wp_strip_all_tags($product->get_description()));
        $overview_primary = wp_trim_words($short_description !== '' ? $short_description : ($usage_excerpt !== '' ? $usage_excerpt : $full_description), 34, '...');
        $overview_secondary = $full_description !== '' ? wp_trim_words($full_description, 40, '...') : $category_intro;
        if ($overview_secondary === '' || $overview_secondary === $overview_primary) {
            $overview_secondary = $category_intro;
        }

        $package_summary = function_exists('my_theme_get_package_summary_text')
            ? trim((string) my_theme_get_package_summary_text($product))
            : '';
        $capacity_text = '';
        $weight_text = '';
        if (function_exists('my_theme_get_capacity_weight')) {
            [$capacity_text, $weight_value, $weight_text] = my_theme_get_capacity_weight($product);
            if ($weight_text === '' && $weight_value !== '') {
                $weight_text = wc_format_weight($weight_value);
            }
        }

        $package_value = $capacity_text !== '' ? $capacity_text : ($weight_text !== '' ? $weight_text : ($package_summary !== '' ? $package_summary : 'Chốt theo quy cách'));
        $package_bits = [];
        if ($package_summary !== '' && $package_summary !== $package_value) {
            $package_bits[] = $package_summary;
        }
        if ($capacity_text !== '' && $capacity_text !== $package_value) {
            $package_bits[] = 'Dung tích ' . $capacity_text;
        }
        if ($weight_text !== '' && $weight_text !== $package_value) {
            $package_bits[] = 'Khối lượng ' . $weight_text;
        }
        $package_text = !empty($package_bits) ? implode(' | ', $package_bits) : 'Nên chốt đúng dung tích và quy cách trước khi đặt hàng hoặc hỏi giá.';

        $catalog = function_exists('my_theme_get_visual_story_group_catalog') ? my_theme_get_visual_story_group_catalog() : [];
        $catalog_entry = ($group_key !== '' && isset($catalog[$group_key]) && is_array($catalog[$group_key])) ? $catalog[$group_key] : [];
        $solution_url = isset($catalog_entry['url']) ? trim((string) $catalog_entry['url']) : '';
        $solution_cta = isset($catalog_entry['cta']) ? trim((string) $catalog_entry['cta']) : 'Xem giải pháp';
        $official_source_url = function_exists('my_theme_get_product_official_source_url')
            ? trim((string) my_theme_get_product_official_source_url($product))
            : '';
        $official_documents = function_exists('my_theme_get_product_official_documents')
            ? (array) my_theme_get_product_official_documents($product)
            : [];
        $first_document = !empty($official_documents) && is_array($official_documents[0]) ? $official_documents[0] : [];
        $first_document_url = isset($first_document['url']) ? trim((string) $first_document['url']) : '';
        $first_document_label = isset($first_document['label']) ? trim((string) $first_document['label']) : 'PDF kỹ thuật';
        $contact_url = add_query_arg(
            [
                'lead_product' => $display_name,
                'lead_group' => $group_key,
                'source' => 'single-product-support',
            ],
            home_url('/lien-he/')
        );
        $gallery_items = my_theme_get_single_product_support_gallery_items($product, $group_key, 3);
        $heading_meta = array_values(array_filter([
            $brand_label !== '' ? 'Thương hiệu: ' . $brand_label : '',
            $line_label !== '' ? 'Dòng: ' . $line_label : '',
            $category_label !== '' ? 'Nhóm: ' . $category_label : '',
        ]));
        $facts = [
            ['label' => 'Bề mặt phù hợp', 'value' => (string) ($profile['surface_value'] ?? ''), 'text' => (string) ($profile['surface_text'] ?? '')],
            ['label' => 'Công trình nên dùng', 'value' => (string) ($profile['project_value'] ?? ''), 'text' => (string) ($profile['project_text'] ?? '')],
            ['label' => 'Dụng cụ thi công', 'value' => (string) ($profile['tool_value'] ?? ''), 'text' => (string) ($profile['tool_text'] ?? '')],
            ['label' => 'Đóng gói tham khảo', 'value' => $package_value, 'text' => $package_text],
        ];
        $lead_text = trim((string) ($profile['lead_text'] ?? ''));
        $fit_text = trim((string) ($profile['fit_text'] ?? ''));
        $fit_block_text = $overview_primary !== '' ? $overview_primary : $fit_text;
        if ($lead_text !== '') {
            $fit_block_text = trim($lead_text . ($overview_primary !== '' ? ' ' . $overview_primary : ''));
        }
        $insights = [
            ['title' => (string) ($profile['fit_title'] ?? 'Công dụng chính'), 'text' => $fit_block_text],
            ['title' => (string) ($profile['project_title'] ?? 'Khu vực nên dùng'), 'text' => trim((string) ($profile['project_text'] ?? '') . ($category_intro !== '' ? ' ' . $category_intro : ''))],
            ['title' => (string) ($profile['advice_title'] ?? 'Khi nào nên hỏi kỹ'), 'text' => (string) ($profile['advice_text'] ?? '')],
        ];
        $notes = isset($profile['notes']) && is_array($profile['notes']) ? array_values(array_filter($profile['notes'])) : [];
        $steps = isset($profile['steps']) && is_array($profile['steps']) ? array_values(array_filter($profile['steps'])) : [];

        echo '<section class="page-section single-product-support product-support-layout" aria-label="' . esc_attr('Công dụng và cách thi công ' . $display_name) . '">';
        echo '<div class="section-heading section-heading--structured"><div class="section-heading__main">';
        echo '<p class="eyebrow eyebrow-muted">' . esc_html((string) ($profile['eyebrow'] ?? 'Hướng dẫn thi công')) . '</p>';
        echo '<h2 class="section-title">' . esc_html('Công dụng, ứng dụng và cách thi công ' . $display_name) . '</h2>';
        if ($overview_secondary !== '') {
            echo '<p class="section-sub">' . esc_html($overview_secondary) . '</p>';
        }
        echo '</div>';
        if (!empty($heading_meta)) {
            echo '<div class="section-heading__meta" aria-label="Thông tin nhanh sản phẩm">';
            foreach ($heading_meta as $meta_item) {
                echo '<span class="section-heading__meta-item">' . esc_html($meta_item) . '</span>';
            }
            echo '</div>';
        }
        echo '</div>';

        echo '<div class="product-support-layout__fact-grid">';
        foreach ($facts as $fact) {
            if (trim((string) ($fact['value'] ?? '')) === '') {
                continue;
            }
            echo '<article class="product-support-layout__fact-card">';
            echo '<span class="product-support-layout__fact-label">' . esc_html((string) $fact['label']) . '</span>';
            echo '<strong class="product-support-layout__fact-value">' . esc_html((string) $fact['value']) . '</strong>';
            echo '<p class="product-support-layout__fact-text">' . esc_html((string) ($fact['text'] ?? '')) . '</p>';
            echo '</article>';
        }
        echo '</div>';

        echo '<div class="info-grid product-support-layout__insight-grid">';
        foreach ($insights as $insight) {
            if (trim((string) ($insight['text'] ?? '')) === '') {
                continue;
            }
            echo '<article class="info-card"><h3>' . esc_html((string) $insight['title']) . '</h3><p>' . esc_html((string) $insight['text']) . '</p></article>';
        }
        echo '</div>';

        if (!empty($notes)) {
            echo '<div class="content-block product-support-layout__note-block"><h3>' . esc_html((string) ($profile['note_title'] ?? 'Lưu ý trước khi đặt và thi công')) . '</h3><ul class="list-check">';
            foreach ($notes as $note) {
                echo '<li>' . esc_html((string) $note) . '</li>';
            }
            echo '</ul></div>';
        }

        if (!empty($steps)) {
            echo '<div class="product-support-layout__process"><div class="section-heading"><div>';
            echo '<h3 class="section-title">' . esc_html((string) ($profile['process_title'] ?? 'Quy trình thi công gợi ý')) . '</h3>';
            if (!empty($profile['process_subtitle'])) {
                echo '<p class="section-sub">' . esc_html((string) $profile['process_subtitle']) . '</p>';
            }
            echo '</div></div><div class="product-support-layout__process-grid">';
            foreach ($steps as $index => $step) {
                echo '<article class="product-support-layout__process-card">';
                echo '<span class="product-support-layout__process-step">Bước ' . esc_html((string) ($index + 1)) . '</span>';
                echo '<h4 class="product-support-layout__process-title">' . esc_html((string) ($step['title'] ?? '')) . '</h4>';
                echo '<p class="product-support-layout__process-text">' . esc_html((string) ($step['text'] ?? '')) . '</p>';
                echo '</article>';
            }
            echo '</div></div>';
        }

        if (!empty($gallery_items)) {
            echo '<div class="product-support-layout__gallery"><div class="section-heading"><div>';
            echo '<h3 class="section-title">' . esc_html((string) ($profile['gallery_title'] ?? 'Hình minh họa công trình')) . '</h3>';
            if (!empty($profile['gallery_subtitle'])) {
                echo '<p class="section-sub">' . esc_html((string) $profile['gallery_subtitle']) . '</p>';
            }
            echo '</div></div><div class="visual-story-grid">';
            foreach ($gallery_items as $gallery_item) {
                $attachment_id = isset($gallery_item['attachment_id']) ? (int) $gallery_item['attachment_id'] : 0;
                if ($attachment_id <= 0) {
                    continue;
                }
                $caption = isset($gallery_item['caption']) ? trim((string) $gallery_item['caption']) : '';
                $badge = isset($gallery_item['badge']) ? trim((string) $gallery_item['badge']) : '';
                $alt = $caption !== '' ? $caption : get_the_title($attachment_id);
                echo '<article class="visual-story-card"><div class="visual-story-card__figure">';
                echo wp_get_attachment_image($attachment_id, 'large', false, ['loading' => 'lazy', 'decoding' => 'async', 'alt' => $alt]);
                echo '</div><div class="visual-story-card__meta">';
                if ($caption !== '') {
                    echo '<p class="visual-story-card__caption">' . esc_html($caption) . '</p>';
                }
                if ($badge !== '') {
                    echo '<p class="visual-story-card__source">' . esc_html($badge) . '</p>';
                }
                echo '</div></article>';
            }
            echo '</div></div>';
        }

        echo '<section class="page-section cta-inline cta-inline--essentials product-support-layout__cta" aria-label="Liên hệ tư vấn sản phẩm">';
        echo '<div class="cta-inline__content"><p class="eyebrow-muted">Chốt vật tư nhanh hơn</p>';
        echo '<h3>' . esc_html((string) ($profile['cta_title'] ?? 'Gửi ảnh hiện trạng để được tư vấn')) . '</h3>';
        echo '<p class="cta-inline__lead">' . esc_html((string) ($profile['cta_lead'] ?? 'Gửi ảnh bề mặt, diện tích và nhu cầu sử dụng để cửa hàng đối chiếu nhanh hơn.')) . '</p>';
        echo '<div class="cta-inline__steps"><span class="cta-inline__step">Ảnh hiện trạng</span><span class="cta-inline__step">Diện tích dự kiến</span><span class="cta-inline__step">Bề mặt cần thi công</span><span class="cta-inline__step">Tiến độ và mục tiêu sử dụng</span></div></div>';
        echo '<div class="cta-inline__actions"><a class="btn btn-primary" href="' . esc_url($contact_url) . '">Gửi yêu cầu</a>';
        if ($solution_url !== '') {
            echo '<a class="btn btn-outline" href="' . esc_url($solution_url) . '">' . esc_html($solution_cta !== '' ? $solution_cta : 'Xem giải pháp') . '</a>';
        }
        if ($official_source_url !== '') {
            echo '<a class="btn btn-outline" href="' . esc_url($official_source_url) . '" target="_blank" rel="noopener nofollow">Trang hãng</a>';
        } elseif ($first_document_url !== '') {
            echo '<a class="btn btn-outline" href="' . esc_url($first_document_url) . '" target="_blank" rel="noopener nofollow">' . esc_html($first_document_label) . '</a>';
        }
        echo '</div></section>';
        echo '</section>';

        if (function_exists('my_theme_render_single_product_buying_guide')) {
            my_theme_render_single_product_buying_guide($product, $group_key);
        }
    }
}
