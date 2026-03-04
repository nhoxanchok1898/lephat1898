<?php
/**
 * Admin lead reporting and dashboard widgets.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('my_theme_lead_count_by_status')) {
    function my_theme_lead_count_by_status($status = '')
    {
        $status = sanitize_key((string) $status);
        $status_options = my_theme_lead_status_options();
        if (!isset($status_options[$status])) {
            return 0;
        }

        $query = new WP_Query([
            'post_type' => 'customer_lead',
            'post_status' => ['publish', 'private', 'draft'],
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => '_lead_status',
                    'value' => $status,
                ],
            ],
            'no_found_rows' => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);

        return max(0, (int) $query->found_posts);
    }
}

if (!function_exists('my_theme_lead_count_by_meta_query')) {
    function my_theme_lead_count_by_meta_query($meta_query = [])
    {
        if (!is_array($meta_query) || empty($meta_query)) {
            return 0;
        }

        $query = new WP_Query([
            'post_type' => 'customer_lead',
            'post_status' => ['publish', 'private', 'draft'],
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => $meta_query,
            'no_found_rows' => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);

        return max(0, (int) $query->found_posts);
    }
}

if (!function_exists('my_theme_lead_report_render_count_list')) {
    function my_theme_lead_report_render_count_list($items = [], $empty_label = 'Chưa có dữ liệu', $limit = 8)
    {
        if (!is_array($items) || empty($items)) {
            echo '<p class="my-theme-lead-report-empty">' . esc_html($empty_label) . '</p>';
            return;
        }

        echo '<ol class="my-theme-lead-report-list">';
        $position = 0;
        foreach ($items as $label => $count) {
            $position++;
            if ($position > $limit) {
                break;
            }
            echo '<li><span>' . esc_html((string) $label) . '</span><strong>' . esc_html((string) ((int) $count)) . '</strong></li>';
        }
        echo '</ol>';
    }
}


if (!function_exists('my_theme_render_customer_lead_report_page')) {
    function my_theme_render_customer_lead_report_page()
    {
        if (!current_user_can('edit_posts')) {
            wp_die('Permission denied.');
        }

        $status = my_theme_lead_get_status_from_request();
        $priority = my_theme_lead_get_priority_from_request();
        $followup = my_theme_lead_get_followup_filter_from_request();
        $origin = my_theme_lead_get_origin_from_request();
        $keyword = my_theme_lead_get_keyword_from_request();
        $keyword_phone = my_theme_lead_normalize_phone($keyword);
        $date_from = my_theme_lead_get_date_from_request('lead_date_from');
        $date_to = my_theme_lead_get_date_from_request('lead_date_to');
        $status_options = my_theme_lead_status_options();
        $priority_options = my_theme_lead_priority_options();
        $followup_options = my_theme_lead_followup_filter_options();
        $origin_options = my_theme_lead_origin_filter_options();

        $query_args = [
            'post_type' => 'customer_lead',
            'post_status' => ['publish', 'private', 'draft'],
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'ids',
            'no_found_rows' => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
        ];

        $meta_query = [];
        if ($status !== '') {
            $meta_query[] = [
                'key' => '_lead_status',
                'value' => $status,
            ];
        }
        if ($priority !== '') {
            $meta_query[] = [
                'key' => '_lead_priority',
                'value' => $priority,
            ];
        }
        if ($origin !== '') {
            $origin_meta_query = my_theme_lead_origin_meta_query($origin);
            if (!empty($origin_meta_query)) {
                $meta_query[] = $origin_meta_query;
            }
        }
        if ($keyword !== '') {
            $meta_query[] = [
                'relation' => 'OR',
                [
                    'key' => '_lead_name',
                    'value' => $keyword,
                    'compare' => 'LIKE',
                ],
                [
                    'key' => '_lead_phone',
                    'value' => $keyword,
                    'compare' => 'LIKE',
                ],
                [
                    'key' => '_lead_phone_normalized',
                    'value' => ($keyword_phone !== '' ? $keyword_phone : $keyword),
                    'compare' => 'LIKE',
                ],
                [
                    'key' => '_lead_email',
                    'value' => $keyword,
                    'compare' => 'LIKE',
                ],
                [
                    'key' => '_lead_project_type',
                    'value' => $keyword,
                    'compare' => 'LIKE',
                ],
                [
                    'key' => '_lead_note',
                    'value' => $keyword,
                    'compare' => 'LIKE',
                ],
            ];
        }
        if ($followup !== '') {
            $times = my_theme_lead_get_day_timestamps();
            if ($followup === 'overdue') {
                $meta_query[] = [
                    'key' => '_lead_next_follow_up_ts',
                    'value' => $times['now'],
                    'compare' => '<',
                    'type' => 'NUMERIC',
                ];
            } elseif ($followup === 'today') {
                $meta_query[] = [
                    'key' => '_lead_next_follow_up_ts',
                    'value' => [$times['today_start'], $times['tomorrow_start'] - 1],
                    'compare' => 'BETWEEN',
                    'type' => 'NUMERIC',
                ];
            } elseif ($followup === 'upcoming') {
                $meta_query[] = [
                    'key' => '_lead_next_follow_up_ts',
                    'value' => $times['tomorrow_start'],
                    'compare' => '>=',
                    'type' => 'NUMERIC',
                ];
            } elseif ($followup === 'none') {
                $meta_query[] = [
                    'relation' => 'OR',
                    [
                        'key' => '_lead_next_follow_up_ts',
                        'compare' => 'NOT EXISTS',
                    ],
                    [
                        'key' => '_lead_next_follow_up_ts',
                        'value' => '',
                        'compare' => '=',
                    ],
                ];
            }
        }
        if (!empty($meta_query)) {
            $query_args['meta_query'] = $meta_query;
        }
        if ($date_from !== '' || $date_to !== '') {
            $date_rule = [
                'inclusive' => true,
            ];
            if ($date_from !== '') {
                $date_rule['after'] = $date_from;
            }
            if ($date_to !== '') {
                $date_rule['before'] = $date_to;
            }
            $query_args['date_query'] = [$date_rule];
        }

        $lead_ids = get_posts($query_args);
        if (!is_array($lead_ids)) {
            $lead_ids = [];
        }

        $status_counts = [];
        foreach (array_keys($status_options) as $status_key) {
            $status_counts[$status_key] = 0;
        }
        $priority_counts = [];
        foreach (array_keys($priority_options) as $priority_key) {
            $priority_counts[$priority_key] = 0;
        }
        $followup_counts = [
            'overdue' => 0,
            'today' => 0,
            'upcoming' => 0,
            'none' => 0,
        ];
        $origin_counts = [];
        foreach (array_keys($origin_options) as $origin_key) {
            $origin_counts[$origin_key] = 0;
        }
        $channel_counts = [];
        $source_counts = [];
        $campaign_counts = [];
        $assignee_counts = [];
        $webhook_counts = [];

        foreach ($lead_ids as $lead_id) {
            $lead_id = (int) $lead_id;
            if ($lead_id <= 0) {
                continue;
            }

            $lead_status = (string) get_post_meta($lead_id, '_lead_status', true);
            if (!isset($status_options[$lead_status])) {
                $lead_status = 'new';
            }
            $status_counts[$lead_status]++;

            $lead_priority = (string) get_post_meta($lead_id, '_lead_priority', true);
            if (!isset($priority_options[$lead_priority])) {
                $lead_priority = 'normal';
            }
            $priority_counts[$lead_priority]++;

            $lead_followup_ts = (int) get_post_meta($lead_id, '_lead_next_follow_up_ts', true);
            if ($lead_followup_ts <= 0) {
                $followup_counts['none']++;
            } else {
                $lead_followup_status = my_theme_lead_followup_status_label($lead_followup_ts);
                if ($lead_followup_status === 'Quá hạn') {
                    $followup_counts['overdue']++;
                } elseif ($lead_followup_status === 'Hôm nay') {
                    $followup_counts['today']++;
                } else {
                    $followup_counts['upcoming']++;
                }
            }

            $channel_label = my_theme_lead_channel_label((string) get_post_meta($lead_id, '_lead_contact_channel', true));
            if (!isset($channel_counts[$channel_label])) {
                $channel_counts[$channel_label] = 0;
            }
            $channel_counts[$channel_label]++;

            $source_tag = trim((string) get_post_meta($lead_id, '_lead_source_tag', true));
            $utm_source = trim((string) get_post_meta($lead_id, '_lead_utm_source', true));
            $source_origin = my_theme_lead_origin_label($source_tag);
            if ($source_origin === 'WooCommerce') {
                $origin_counts['woocommerce']++;
            } else {
                $origin_counts['webform']++;
            }
            $duplicate_submit_count = (int) get_post_meta($lead_id, '_lead_duplicate_submit_count', true);
            if ($duplicate_submit_count > 0) {
                $origin_counts['repeat']++;
            }
            $order_count = (int) get_post_meta($lead_id, '_lead_order_count', true);
            if ($order_count > 0) {
                $origin_counts['with_orders']++;
            } else {
                $origin_counts['without_orders']++;
            }
            $source_label = $source_tag !== '' ? $source_tag : 'Không rõ nguồn';
            if ($utm_source !== '') {
                $source_label .= ' / ' . $utm_source;
            }
            if (!isset($source_counts[$source_label])) {
                $source_counts[$source_label] = 0;
            }
            $source_counts[$source_label]++;

            $utm_campaign = trim((string) get_post_meta($lead_id, '_lead_utm_campaign', true));
            if ($utm_campaign !== '') {
                if (!isset($campaign_counts[$utm_campaign])) {
                    $campaign_counts[$utm_campaign] = 0;
                }
                $campaign_counts[$utm_campaign]++;
            }

            $assignee = trim((string) get_post_meta($lead_id, '_lead_assignee', true));
            if ($assignee === '') {
                $assignee = 'Chưa phân công';
            }
            if (!isset($assignee_counts[$assignee])) {
                $assignee_counts[$assignee] = 0;
            }
            $assignee_counts[$assignee]++;

            $webhook_status = (string) get_post_meta($lead_id, '_lead_webhook_last_status', true);
            $webhook_label = $webhook_status !== '' ? my_theme_lead_webhook_status_label($webhook_status) : 'Chưa gửi';
            if (!isset($webhook_counts[$webhook_label])) {
                $webhook_counts[$webhook_label] = 0;
            }
            $webhook_counts[$webhook_label]++;
        }

        arsort($channel_counts);
        arsort($source_counts);
        arsort($campaign_counts);
        arsort($assignee_counts);
        arsort($webhook_counts);

        $total_leads = count($lead_ids);
        $closed_count = isset($status_counts['closed']) ? (int) $status_counts['closed'] : 0;
        $conversion_rate = $total_leads > 0 ? round(((float) $closed_count / (float) $total_leads) * 100, 1) : 0;
        $unassigned_count = isset($assignee_counts['Chưa phân công']) ? (int) $assignee_counts['Chưa phân công'] : 0;
        $overdue_followup_count = isset($followup_counts['overdue']) ? (int) $followup_counts['overdue'] : 0;

        $report_url = admin_url('edit.php?post_type=customer_lead&page=customer-lead-report');
        $list_url = admin_url('edit.php?post_type=customer_lead');
        $export_args = [
            'action' => 'my_theme_export_customer_leads',
        ];
        if ($status !== '') {
            $export_args['lead_status'] = $status;
        }
        if ($priority !== '') {
            $export_args['lead_priority'] = $priority;
        }
        if ($followup !== '') {
            $export_args['lead_followup'] = $followup;
        }
        if ($origin !== '') {
            $export_args['lead_origin'] = $origin;
        }
        if ($keyword !== '') {
            $export_args['lead_keyword'] = $keyword;
        }
        if ($date_from !== '') {
            $export_args['lead_date_from'] = $date_from;
        }
        if ($date_to !== '') {
            $export_args['lead_date_to'] = $date_to;
        }
        $export_url = wp_nonce_url(
            add_query_arg($export_args, admin_url('admin-post.php')),
            'my_theme_export_customer_leads'
        );
        $base_list_args = [];
        if ($priority !== '') {
            $base_list_args['lead_priority'] = $priority;
        }
        if ($followup !== '') {
            $base_list_args['lead_followup'] = $followup;
        }
        if ($origin !== '') {
            $base_list_args['lead_origin'] = $origin;
        }
        if ($keyword !== '') {
            $base_list_args['lead_keyword'] = $keyword;
        }
        if ($date_from !== '') {
            $base_list_args['lead_date_from'] = $date_from;
        }
        if ($date_to !== '') {
            $base_list_args['lead_date_to'] = $date_to;
        }

        echo '<div class="wrap my-theme-lead-report-wrap">';
        echo '<h1>Báo cáo khách hàng tiềm năng</h1>';
        echo '<p class="description">Theo dõi số lead, trạng thái xử lý và nguồn marketing để tối ưu hoạt động bán hàng.</p>';
        if (current_user_can('manage_options')) {
            $webhook_settings_url = admin_url('edit.php?post_type=customer_lead&page=customer-lead-webhook');
            echo '<p><a class="button" href="' . esc_url($webhook_settings_url) . '">Cấu hình webhook</a></p>';
        }

        echo '<form method="get" class="my-theme-lead-report-filter">';
        echo '<input type="hidden" name="post_type" value="customer_lead" />';
        echo '<input type="hidden" name="page" value="customer-lead-report" />';
        echo '<div class="field"><label for="lead-report-status">Trạng thái</label>';
        echo '<select id="lead-report-status" name="lead_status"><option value="">Tất cả</option>';
        foreach ($status_options as $status_key => $status_label) {
            echo '<option value="' . esc_attr($status_key) . '" ' . selected($status, $status_key, false) . '>' . esc_html($status_label) . '</option>';
        }
        echo '</select></div>';
        echo '<div class="field"><label for="lead-report-priority">Ưu tiên</label>';
        echo '<select id="lead-report-priority" name="lead_priority"><option value="">Tất cả</option>';
        foreach ($priority_options as $priority_key => $priority_label) {
            echo '<option value="' . esc_attr($priority_key) . '" ' . selected($priority, $priority_key, false) . '>' . esc_html($priority_label) . '</option>';
        }
        echo '</select></div>';
        echo '<div class="field"><label for="lead-report-followup">Nhắc chăm sóc</label>';
        echo '<select id="lead-report-followup" name="lead_followup"><option value="">Tất cả</option>';
        foreach ($followup_options as $followup_key => $followup_label) {
            echo '<option value="' . esc_attr($followup_key) . '" ' . selected($followup, $followup_key, false) . '>' . esc_html($followup_label) . '</option>';
        }
        echo '</select></div>';
        echo '<div class="field"><label for="lead-report-origin">Nguồn lead</label>';
        echo '<select id="lead-report-origin" name="lead_origin"><option value="">Tất cả</option>';
        foreach ($origin_options as $origin_key => $origin_label) {
            echo '<option value="' . esc_attr($origin_key) . '" ' . selected($origin, $origin_key, false) . '>' . esc_html($origin_label) . '</option>';
        }
        echo '</select></div>';
        echo '<div class="field"><label for="lead-report-keyword">Từ khóa</label><input id="lead-report-keyword" type="search" name="lead_keyword" value="' . esc_attr($keyword) . '" placeholder="Tên, SĐT, email..." /></div>';
        echo '<div class="field"><label for="lead-report-date-from">Từ ngày</label><input id="lead-report-date-from" type="date" name="lead_date_from" value="' . esc_attr($date_from) . '" /></div>';
        echo '<div class="field"><label for="lead-report-date-to">Đến ngày</label><input id="lead-report-date-to" type="date" name="lead_date_to" value="' . esc_attr($date_to) . '" /></div>';
        echo '<div class="actions"><button class="button button-primary" type="submit">Lọc báo cáo</button>';
        echo '<a class="button" href="' . esc_url($export_url) . '">Xuất CSV</a>';
        echo '<a class="button" href="' . esc_url($report_url) . '">Xóa lọc</a></div>';
        echo '</form>';

        echo '<div class="my-theme-lead-report-cards">';
        echo '<div class="my-theme-lead-report-card"><a href="' . esc_url(add_query_arg($base_list_args, $list_url)) . '"><span>Tổng lead</span><strong>' . esc_html((string) $total_leads) . '</strong></a></div>';
        foreach ($status_options as $status_key => $status_label) {
            $status_count = isset($status_counts[$status_key]) ? (int) $status_counts[$status_key] : 0;
            $status_url = add_query_arg(array_merge($base_list_args, ['lead_status' => $status_key]), $list_url);
            echo '<div class="my-theme-lead-report-card"><a href="' . esc_url($status_url) . '"><span>' . esc_html($status_label) . '</span><strong>' . esc_html((string) $status_count) . '</strong></a></div>';
        }
        echo '<div class="my-theme-lead-report-card"><span>Tỉ lệ chốt</span><strong>' . esc_html((string) $conversion_rate) . '%</strong></div>';
        echo '<div class="my-theme-lead-report-card"><span>Chưa phân công</span><strong>' . esc_html((string) $unassigned_count) . '</strong></div>';
        $overdue_url = add_query_arg(array_merge($base_list_args, ['lead_followup' => 'overdue']), $list_url);
        echo '<div class="my-theme-lead-report-card"><a href="' . esc_url($overdue_url) . '"><span>Nhắc chăm sóc quá hạn</span><strong>' . esc_html((string) $overdue_followup_count) . '</strong></a></div>';
        $woo_count = isset($origin_counts['woocommerce']) ? (int) $origin_counts['woocommerce'] : 0;
        $repeat_count = isset($origin_counts['repeat']) ? (int) $origin_counts['repeat'] : 0;
        $woo_url = add_query_arg(array_merge($base_list_args, ['lead_origin' => 'woocommerce']), $list_url);
        $repeat_url = add_query_arg(array_merge($base_list_args, ['lead_origin' => 'repeat']), $list_url);
        echo '<div class="my-theme-lead-report-card"><a href="' . esc_url($woo_url) . '"><span>Lead WooCommerce</span><strong>' . esc_html((string) $woo_count) . '</strong></a></div>';
        echo '<div class="my-theme-lead-report-card"><a href="' . esc_url($repeat_url) . '"><span>Khách gửi lại form</span><strong>' . esc_html((string) $repeat_count) . '</strong></a></div>';
        echo '</div>';

        $recent_query_args = $query_args;
        $recent_query_args['posts_per_page'] = 8;
        $recent_query_args['fields'] = 'all';
        $recent_query_args['no_found_rows'] = true;
        $recent_query_args['orderby'] = 'date';
        $recent_query_args['order'] = 'DESC';
        $recent_leads = get_posts($recent_query_args);

        $priority_panel_counts = [];
        foreach ($priority_options as $priority_key => $priority_label) {
            $priority_panel_counts[$priority_label] = isset($priority_counts[$priority_key]) ? (int) $priority_counts[$priority_key] : 0;
        }
        $followup_panel_counts = [];
        foreach ($followup_options as $followup_key => $followup_label) {
            $followup_panel_counts[$followup_label] = isset($followup_counts[$followup_key]) ? (int) $followup_counts[$followup_key] : 0;
        }
        $origin_panel_counts = [];
        foreach ($origin_options as $origin_key => $origin_label) {
            $origin_panel_counts[$origin_label] = isset($origin_counts[$origin_key]) ? (int) $origin_counts[$origin_key] : 0;
        }

        echo '<div class="my-theme-lead-report-panels">';
        echo '<section class="my-theme-lead-report-panel"><h2>Mức ưu tiên lead</h2>';
        my_theme_lead_report_render_count_list($priority_panel_counts, 'Chưa có dữ liệu ưu tiên.');
        echo '</section>';

        echo '<section class="my-theme-lead-report-panel"><h2>Nhắc chăm sóc</h2>';
        my_theme_lead_report_render_count_list($followup_panel_counts, 'Chưa có dữ liệu nhắc chăm sóc.');
        echo '</section>';

        echo '<section class="my-theme-lead-report-panel"><h2>Nhóm nguồn lead</h2>';
        my_theme_lead_report_render_count_list($origin_panel_counts, 'Chưa có dữ liệu nhóm nguồn.');
        echo '</section>';

        echo '<section class="my-theme-lead-report-panel"><h2>Nguồn lead nổi bật</h2>';
        my_theme_lead_report_render_count_list($source_counts, 'Chưa có dữ liệu nguồn.');
        echo '</section>';

        echo '<section class="my-theme-lead-report-panel"><h2>Chiến dịch UTM nổi bật</h2>';
        my_theme_lead_report_render_count_list($campaign_counts, 'Chưa có dữ liệu UTM campaign.');
        echo '</section>';

        echo '<section class="my-theme-lead-report-panel"><h2>Phân bổ phụ trách</h2>';
        my_theme_lead_report_render_count_list($assignee_counts, 'Chưa có dữ liệu phụ trách.');
        echo '</section>';

        echo '<section class="my-theme-lead-report-panel"><h2>Kênh liên hệ ưu tiên</h2>';
        my_theme_lead_report_render_count_list($channel_counts, 'Chưa có dữ liệu kênh liên hệ.');
        echo '</section>';

        echo '<section class="my-theme-lead-report-panel"><h2>Tình trạng webhook</h2>';
        my_theme_lead_report_render_count_list($webhook_counts, 'Chưa có dữ liệu webhook.');
        echo '</section>';
        echo '</div>';

        echo '<section class="my-theme-lead-report-panel" style="margin-top:10px;"><h2>Lead mới gần đây</h2>';
        if (empty($recent_leads)) {
            echo '<p class="my-theme-lead-report-empty">Chưa có lead mới.</p>';
        } else {
            echo '<table class="my-theme-lead-report-table"><thead><tr><th>Khách hàng</th><th>Điện thoại</th><th>Trạng thái</th><th>Ưu tiên</th><th>Nhắc chăm sóc</th><th>Nguồn lead</th><th>Phụ trách</th><th></th></tr></thead><tbody>';
            foreach ($recent_leads as $lead_post) {
                if (!$lead_post instanceof WP_Post) {
                    continue;
                }
                $lead_id = (int) $lead_post->ID;
                if ($lead_id <= 0) {
                    continue;
                }
                $lead_name = (string) get_post_meta($lead_id, '_lead_name', true);
                $lead_phone = (string) get_post_meta($lead_id, '_lead_phone', true);
                $lead_status = (string) get_post_meta($lead_id, '_lead_status', true);
                if (!isset($status_options[$lead_status])) {
                    $lead_status = 'new';
                }
                $lead_priority = (string) get_post_meta($lead_id, '_lead_priority', true);
                if (!isset($priority_options[$lead_priority])) {
                    $lead_priority = 'normal';
                }
                $lead_followup = (string) get_post_meta($lead_id, '_lead_next_follow_up', true);
                $lead_followup_ts = (int) get_post_meta($lead_id, '_lead_next_follow_up_ts', true);
                $lead_source_tag = (string) get_post_meta($lead_id, '_lead_source_tag', true);
                $lead_origin_label = my_theme_lead_origin_label($lead_source_tag);
                $lead_assignee = trim((string) get_post_meta($lead_id, '_lead_assignee', true));
                if ($lead_assignee === '') {
                    $lead_assignee = '-';
                }
                $edit_url = admin_url('post.php?post=' . $lead_id . '&action=edit');

                echo '<tr>';
                echo '<td>' . esc_html($lead_name !== '' ? $lead_name : $lead_post->post_title) . '</td>';
                echo '<td>' . esc_html($lead_phone !== '' ? $lead_phone : '-') . '</td>';
                echo '<td>' . esc_html($status_options[$lead_status]) . '</td>';
                echo '<td>' . esc_html($priority_options[$lead_priority]) . '</td>';
                echo '<td>' . esc_html($lead_followup !== '' ? ($lead_followup . ' (' . my_theme_lead_followup_status_label($lead_followup_ts) . ')') : 'Chưa đặt') . '</td>';
                echo '<td>' . esc_html($lead_origin_label) . '</td>';
                echo '<td>' . esc_html($lead_assignee) . '</td>';
                echo '<td><a href="' . esc_url($edit_url) . '">Mở</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        echo '</section>';
        echo '</div>';
    }
}

add_action('admin_menu', function () {
    if (!current_user_can('edit_posts')) {
        return;
    }

    add_submenu_page(
        'edit.php?post_type=customer_lead',
        'Báo cáo lead',
        'Báo cáo',
        'edit_posts',
        'customer-lead-report',
        'my_theme_render_customer_lead_report_page'
    );
}, 30);

add_action('admin_menu', function () {
    if (!current_user_can('edit_posts')) {
        return;
    }

    $new_count = my_theme_lead_count_by_status('new');
    if ($new_count <= 0) {
        return;
    }

    global $menu;
    if (!is_array($menu)) {
        return;
    }

    foreach ($menu as $index => $item) {
        if (!isset($item[2]) || $item[2] !== 'edit.php?post_type=customer_lead') {
            continue;
        }
        $menu[$index][0] .= ' <span class="awaiting-mod update-plugins count-' . (int) $new_count . '"><span class="plugin-count">' . (int) $new_count . '</span></span>';
        break;
    }
}, 99);

if (!function_exists('my_theme_render_customer_lead_dashboard_widget')) {
    function my_theme_render_customer_lead_dashboard_widget()
    {
        $status_options = my_theme_lead_status_options();
        $list_url = admin_url('edit.php?post_type=customer_lead');
        $counts_obj = wp_count_posts('customer_lead');
        $total_leads = 0;
        if (is_object($counts_obj)) {
            foreach ((array) $counts_obj as $status_count) {
                $total_leads += (int) $status_count;
            }
        }
        $times = my_theme_lead_get_day_timestamps();
        $overdue_count = my_theme_lead_count_by_meta_query([
            [
                'key' => '_lead_next_follow_up_ts',
                'value' => $times['now'],
                'compare' => '<',
                'type' => 'NUMERIC',
            ],
        ]);
        $urgent_count = my_theme_lead_count_by_meta_query([
            [
                'key' => '_lead_priority',
                'value' => 'urgent',
            ],
        ]);
        $woo_count = my_theme_lead_count_by_meta_query([
            [
                'key' => '_lead_source_tag',
                'value' => 'woocommerce-checkout',
            ],
        ]);
        $repeat_count = my_theme_lead_count_by_meta_query([
            [
                'key' => '_lead_duplicate_submit_count',
                'value' => 0,
                'compare' => '>',
                'type' => 'NUMERIC',
            ],
        ]);

        $report_url = admin_url('edit.php?post_type=customer_lead&page=customer-lead-report');
        $overdue_url = add_query_arg('lead_followup', 'overdue', $list_url);
        $urgent_url = add_query_arg('lead_priority', 'urgent', $list_url);
        $woo_url = add_query_arg('lead_origin', 'woocommerce', $list_url);
        $repeat_url = add_query_arg('lead_origin', 'repeat', $list_url);
        echo '<p><strong>Tổng lead:</strong> ' . esc_html((string) $total_leads) . ' <a href="' . esc_url($list_url) . '">Xem tất cả</a> | <a href="' . esc_url($report_url) . '">Xem báo cáo</a>';
        if (current_user_can('manage_options')) {
            $notify_url = admin_url('edit.php?post_type=customer_lead&page=customer-lead-notify');
            $webhook_url = admin_url('edit.php?post_type=customer_lead&page=customer-lead-webhook');
            echo ' | <a href="' . esc_url($notify_url) . '">Thông báo</a>';
            echo ' | <a href="' . esc_url($webhook_url) . '">Webhook</a>';
        }
        echo ' | <a href="' . esc_url($overdue_url) . '">Quá hạn: ' . esc_html((string) $overdue_count) . '</a>';
        echo ' | <a href="' . esc_url($urgent_url) . '">Ưu tiên khẩn: ' . esc_html((string) $urgent_count) . '</a>';
        echo ' | <a href="' . esc_url($woo_url) . '">Woo: ' . esc_html((string) $woo_count) . '</a>';
        echo ' | <a href="' . esc_url($repeat_url) . '">Gửi lại: ' . esc_html((string) $repeat_count) . '</a>';
        echo '</p>';
        echo '<div class="my-theme-lead-dashboard-grid">';
        foreach ($status_options as $status_key => $status_label) {
            $count = my_theme_lead_count_by_status($status_key);
            $url = add_query_arg('lead_status', $status_key, $list_url);
            echo '<a class="my-theme-lead-dashboard-card" href="' . esc_url($url) . '">';
            echo '<strong>' . esc_html($status_label) . '</strong>';
            echo '<span>' . esc_html((string) $count) . '</span>';
            echo '</a>';
        }
        echo '</div>';

        $recent_ids = get_posts([
            'post_type' => 'customer_lead',
            'post_status' => ['publish', 'private', 'draft'],
            'posts_per_page' => 7,
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        if (empty($recent_ids)) {
            echo '<p class="my-theme-lead-dashboard-empty">Chưa có lead mới.</p>';
            return;
        }

        echo '<table class="my-theme-lead-dashboard-table">';
        echo '<thead><tr><th>Khách</th><th>Điện thoại</th><th>Trạng thái</th><th></th></tr></thead><tbody>';

        foreach ((array) $recent_ids as $lead_id) {
            $lead_id = (int) $lead_id;
            if ($lead_id <= 0) {
                continue;
            }
            $name = (string) get_post_meta($lead_id, '_lead_name', true);
            $phone = (string) get_post_meta($lead_id, '_lead_phone', true);
            $status = (string) get_post_meta($lead_id, '_lead_status', true);
            if (!isset($status_options[$status])) {
                $status = 'new';
            }
            $edit_url = admin_url('post.php?post=' . $lead_id . '&action=edit');

            echo '<tr>';
            echo '<td>' . esc_html($name !== '' ? $name : get_the_title($lead_id)) . '</td>';
            echo '<td>' . esc_html($phone !== '' ? $phone : '-') . '</td>';
            echo '<td>' . esc_html($status_options[$status]) . '</td>';
            echo '<td><a href="' . esc_url($edit_url) . '">Mở</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }
}

add_action('wp_dashboard_setup', function () {
    if (!current_user_can('edit_posts')) {
        return;
    }
    wp_add_dashboard_widget(
        'my_theme_customer_lead_dashboard_widget',
        'Khách hàng tiềm năng',
        'my_theme_render_customer_lead_dashboard_widget'
    );
});

