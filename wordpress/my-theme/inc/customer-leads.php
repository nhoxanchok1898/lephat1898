<?php
/**
 * Customer lead capture core.
 *
 * Provides:
 * - Lead data model + processing hooks.
 * - Front-end shortcode [lead_capture_form].
 * - Shared helpers used by admin modules.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('my_theme_lead_status_options')) {
    function my_theme_lead_status_options()
    {
        return [
            'new' => 'Mới',
            'contacted' => 'Đã liên hệ',
            'qualified' => 'Tiềm năng',
            'closed' => 'Đã chốt',
            'lost' => 'Không phù hợp',
        ];
    }
}

if (!function_exists('my_theme_lead_channel_label')) {
    function my_theme_lead_channel_label($channel = '')
    {
        $map = [
            'phone' => 'Điện thoại',
            'zalo' => 'Zalo',
            'email' => 'Email',
        ];
        $channel = sanitize_key((string) $channel);
        return isset($map[$channel]) ? $map[$channel] : 'Điện thoại';
    }
}

if (!function_exists('my_theme_lead_priority_options')) {
    function my_theme_lead_priority_options()
    {
        return [
            'low' => 'Thấp',
            'normal' => 'Bình thường',
            'high' => 'Cao',
            'urgent' => 'Khẩn',
        ];
    }
}

if (!function_exists('my_theme_lead_priority_label')) {
    function my_theme_lead_priority_label($priority = '')
    {
        $priority = sanitize_key((string) $priority);
        $options = my_theme_lead_priority_options();
        return isset($options[$priority]) ? $options[$priority] : $options['normal'];
    }
}

if (!function_exists('my_theme_lead_followup_filter_options')) {
    function my_theme_lead_followup_filter_options()
    {
        return [
            'overdue' => 'Quá hạn',
            'today' => 'Hôm nay',
            'upcoming' => 'Sắp tới',
            'none' => 'Chưa đặt lịch',
        ];
    }
}

if (!function_exists('my_theme_lead_origin_filter_options')) {
    function my_theme_lead_origin_filter_options()
    {
        return [
            'webform' => 'Form website',
            'woocommerce' => 'WooCommerce',
            'repeat' => 'Khách gửi lại form',
            'with_orders' => 'Có đơn hàng',
            'without_orders' => 'Chưa có đơn hàng',
        ];
    }
}

if (!function_exists('my_theme_lead_origin_label')) {
    function my_theme_lead_origin_label($source_tag = '')
    {
        $source_tag = sanitize_key((string) $source_tag);
        if ($source_tag === 'woocommerce-checkout') {
            return 'WooCommerce';
        }
        return 'Form website';
    }
}

if (!function_exists('my_theme_lead_origin_meta_query')) {
    function my_theme_lead_origin_meta_query($origin = '')
    {
        $origin = sanitize_key((string) $origin);
        if ($origin === 'woocommerce') {
            return [
                'key' => '_lead_source_tag',
                'value' => 'woocommerce-checkout',
            ];
        }
        if ($origin === 'webform') {
            return [
                'relation' => 'OR',
                [
                    'key' => '_lead_source_tag',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key' => '_lead_source_tag',
                    'value' => '',
                    'compare' => '=',
                ],
                [
                    'key' => '_lead_source_tag',
                    'value' => 'woocommerce-checkout',
                    'compare' => '!=',
                ],
            ];
        }
        if ($origin === 'repeat') {
            return [
                'key' => '_lead_duplicate_submit_count',
                'value' => 0,
                'compare' => '>',
                'type' => 'NUMERIC',
            ];
        }
        if ($origin === 'with_orders') {
            return [
                'key' => '_lead_order_count',
                'value' => 0,
                'compare' => '>',
                'type' => 'NUMERIC',
            ];
        }
        if ($origin === 'without_orders') {
            return [
                'relation' => 'OR',
                [
                    'key' => '_lead_order_count',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key' => '_lead_order_count',
                    'value' => '',
                    'compare' => '=',
                ],
                [
                    'key' => '_lead_order_count',
                    'value' => 0,
                    'compare' => '<=',
                    'type' => 'NUMERIC',
                ],
            ];
        }

        return [];
    }
}

if (!function_exists('my_theme_lead_get_day_timestamps')) {
    function my_theme_lead_get_day_timestamps()
    {
        $timezone = wp_timezone();
        $now = new DateTimeImmutable('now', $timezone);
        $today_start = $now->setTime(0, 0, 0);
        $tomorrow_start = $today_start->modify('+1 day');

        return [
            'now' => $now->getTimestamp(),
            'today_start' => $today_start->getTimestamp(),
            'tomorrow_start' => $tomorrow_start->getTimestamp(),
        ];
    }
}

if (!function_exists('my_theme_lead_followup_status_label')) {
    function my_theme_lead_followup_status_label($timestamp = 0)
    {
        $timestamp = (int) $timestamp;
        if ($timestamp <= 0) {
            return 'Chưa đặt lịch';
        }

        $times = my_theme_lead_get_day_timestamps();
        if ($timestamp < $times['now']) {
            return 'Quá hạn';
        }
        if ($timestamp < $times['tomorrow_start']) {
            return 'Hôm nay';
        }

        return 'Sắp tới';
    }
}

if (!function_exists('my_theme_lead_normalize_followup_datetime')) {
    function my_theme_lead_normalize_followup_datetime($raw = '')
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return [
                'value' => '',
                'timestamp' => 0,
            ];
        }

        $raw = str_replace('T', ' ', $raw);
        $timezone = wp_timezone();
        $date = DateTimeImmutable::createFromFormat('Y-m-d H:i', $raw, $timezone);
        if (!$date instanceof DateTimeImmutable) {
            return [
                'value' => '',
                'timestamp' => 0,
            ];
        }

        return [
            'value' => $date->format('Y-m-d H:i'),
            'timestamp' => $date->getTimestamp(),
        ];
    }
}

if (!function_exists('my_theme_lead_followup_input_value')) {
    function my_theme_lead_followup_input_value($stored_value = '')
    {
        $stored_value = trim((string) $stored_value);
        if ($stored_value === '' || strlen($stored_value) < 16) {
            return '';
        }
        return str_replace(' ', 'T', substr($stored_value, 0, 16));
    }
}

if (!function_exists('my_theme_register_customer_lead_post_type')) {
    function my_theme_register_customer_lead_post_type()
    {
        $labels = [
            'name' => 'Khách hàng tiềm năng',
            'singular_name' => 'Khách hàng tiềm năng',
            'menu_name' => 'Khách hàng tiềm năng',
            'name_admin_bar' => 'Khách hàng tiềm năng',
            'add_new' => 'Thêm mới',
            'add_new_item' => 'Thêm khách hàng tiềm năng',
            'new_item' => 'Khách hàng mới',
            'edit_item' => 'Chỉnh sửa khách hàng',
            'view_item' => 'Xem khách hàng',
            'all_items' => 'Tất cả khách hàng',
            'search_items' => 'Tìm khách hàng',
            'not_found' => 'Chưa có khách hàng nào.',
            'not_found_in_trash' => 'Không có khách hàng trong thùng rác.',
        ];

        register_post_type('customer_lead', [
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => false,
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'has_archive' => false,
            'rewrite' => false,
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'menu_position' => 26,
            'menu_icon' => 'dashicons-id-alt',
            'supports' => ['title'],
        ]);
    }
}
add_action('init', 'my_theme_register_customer_lead_post_type');

if (!function_exists('my_theme_lead_get_request_ip')) {
    function my_theme_lead_get_request_ip()
    {
        $candidates = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($candidates as $key) {
            if (empty($_SERVER[$key])) {
                continue;
            }
            $raw = (string) $_SERVER[$key];
            $parts = array_map('trim', explode(',', $raw));
            foreach ($parts as $part) {
                if ($part !== '' && filter_var($part, FILTER_VALIDATE_IP)) {
                    return $part;
                }
            }
        }

        return '';
    }
}

if (!function_exists('my_theme_lead_get_safe_redirect_url')) {
    function my_theme_lead_get_safe_redirect_url($raw = '')
    {
        $fallback = home_url('/lien-he');
        $raw = trim((string) $raw);
        if ($raw === '') {
            return $fallback;
        }

        $validated = wp_validate_redirect($raw, $fallback);
        if (!is_string($validated) || trim($validated) === '') {
            return $fallback;
        }

        return remove_query_arg(['lead_status', 'lead_form'], $validated);
    }
}

if (!function_exists('my_theme_lead_redirect')) {
    function my_theme_lead_redirect($redirect_url, $status = 'error', $form_key = 'lead')
    {
        $redirect_url = my_theme_lead_get_safe_redirect_url($redirect_url);
        $form_key = sanitize_key((string) $form_key);
        if ($form_key === '') {
            $form_key = 'lead';
        }

        $url = add_query_arg([
            'lead_status' => sanitize_key((string) $status),
            'lead_form' => $form_key,
        ], $redirect_url);

        wp_safe_redirect($url);
        exit;
    }
}

if (!function_exists('my_theme_lead_normalize_phone')) {
    function my_theme_lead_normalize_phone($phone = '')
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (!is_string($digits) || $digits === '') {
            return '';
        }
        if (strpos($digits, '84') === 0 && strlen($digits) >= 11) {
            $digits = '0' . substr($digits, 2);
        }
        return $digits;
    }
}

if (!function_exists('my_theme_lead_parse_email_list')) {
    function my_theme_lead_parse_email_list($raw = '')
    {
        $raw = str_replace(["\r\n", "\r", ';'], "\n", (string) $raw);
        $parts = preg_split('/[\n,]+/', $raw);
        if (!is_array($parts)) {
            return [];
        }

        $emails = [];
        foreach ($parts as $part) {
            $email = sanitize_email(trim((string) $part));
            if ($email === '' || !is_email($email)) {
                continue;
            }
            $emails[$email] = $email;
        }

        return array_values($emails);
    }
}

if (!function_exists('my_theme_lead_get_notification_settings')) {
    function my_theme_lead_get_notification_settings()
    {
        $enabled = get_option('my_theme_lead_notify_enabled', '1');
        $emails_raw = (string) get_option('my_theme_lead_notify_emails', '');
        $subject_prefix = trim((string) get_option('my_theme_lead_notify_subject_prefix', '[Lead mới]'));
        $reminder_enabled = get_option('my_theme_lead_daily_reminder_enabled', '1');
        $reminder_hour = (int) get_option('my_theme_lead_daily_reminder_hour', 8);
        $reminder_limit = (int) get_option('my_theme_lead_daily_reminder_limit', 25);
        $reminder_last_sent_at = (string) get_option('my_theme_lead_daily_reminder_last_sent', '');
        if ($subject_prefix === '') {
            $subject_prefix = '[Lead mới]';
        }
        if ($reminder_hour < 0 || $reminder_hour > 23) {
            $reminder_hour = 8;
        }
        if ($reminder_limit < 5 || $reminder_limit > 100) {
            $reminder_limit = 25;
        }

        $emails = my_theme_lead_parse_email_list($emails_raw);
        if (empty($emails)) {
            $admin_email = sanitize_email((string) get_option('admin_email'));
            if ($admin_email !== '' && is_email($admin_email)) {
                $emails = [$admin_email];
            }
        }
        if (trim($emails_raw) === '' && !empty($emails)) {
            $emails_raw = implode("\n", $emails);
        }

        return [
            'enabled' => ((string) $enabled === '1'),
            'emails' => $emails,
            'emails_raw' => trim($emails_raw),
            'subject_prefix' => $subject_prefix,
            'reminder_enabled' => ((string) $reminder_enabled === '1'),
            'reminder_hour' => $reminder_hour,
            'reminder_limit' => $reminder_limit,
            'reminder_last_sent_at' => $reminder_last_sent_at,
        ];
    }
}

if (!function_exists('my_theme_lead_find_existing_id')) {
    function my_theme_lead_find_existing_id($phone = '', $email = '')
    {
        $phone = sanitize_text_field((string) $phone);
        $email_raw = sanitize_email((string) $email);
        $email = is_email($email_raw) ? $email_raw : '';
        $phone_normalized = my_theme_lead_normalize_phone($phone);

        $conditions = [];
        if ($phone_normalized !== '') {
            $conditions[] = [
                'key' => '_lead_phone_normalized',
                'value' => $phone_normalized,
            ];
        }
        if ($phone !== '') {
            $conditions[] = [
                'key' => '_lead_phone',
                'value' => $phone,
            ];
        }
        if ($email !== '') {
            $conditions[] = [
                'key' => '_lead_email',
                'value' => $email,
            ];
        }

        if (empty($conditions)) {
            return 0;
        }

        $meta_query = (count($conditions) === 1)
            ? [$conditions[0]]
            : [array_merge(['relation' => 'OR'], $conditions)];

        $ids = get_posts([
            'post_type' => 'customer_lead',
            'post_status' => ['publish', 'private', 'draft'],
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'ids',
            'meta_query' => $meta_query,
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);

        if (empty($ids)) {
            return 0;
        }

        return (int) $ids[0];
    }
}

if (!function_exists('my_theme_lead_send_notification_email')) {
    function my_theme_lead_send_notification_email($lead_id, $context = 'new', $extra_lines = [])
    {
        $lead_id = (int) $lead_id;
        if ($lead_id <= 0 || get_post_type($lead_id) !== 'customer_lead') {
            return false;
        }

        $settings = my_theme_lead_get_notification_settings();
        if (!$settings['enabled'] || empty($settings['emails'])) {
            return false;
        }

        $context = sanitize_key((string) $context);
        $context_subject_map = [
            'new' => 'Mới',
            'updated' => 'Cập nhật',
            'order' => 'Đơn hàng',
        ];
        $context_intro_map = [
            'new' => 'Có khách hàng mới vừa để lại thông tin.',
            'updated' => 'Khách hàng đã gửi lại form, hệ thống đã gộp vào lead hiện có.',
            'order' => 'Khách hàng phát sinh từ đơn hàng WooCommerce.',
        ];

        $name = (string) get_post_meta($lead_id, '_lead_name', true);
        if ($name === '') {
            $name = get_the_title($lead_id);
        }
        $phone = (string) get_post_meta($lead_id, '_lead_phone', true);
        $email = (string) get_post_meta($lead_id, '_lead_email', true);
        $channel = (string) get_post_meta($lead_id, '_lead_contact_channel', true);
        $project_type = (string) get_post_meta($lead_id, '_lead_project_type', true);
        $budget = (string) get_post_meta($lead_id, '_lead_budget', true);
        $message = (string) get_post_meta($lead_id, '_lead_message', true);
        $source_tag = (string) get_post_meta($lead_id, '_lead_source_tag', true);
        $source_url = (string) get_post_meta($lead_id, '_lead_source_url', true);
        $utm_source = (string) get_post_meta($lead_id, '_lead_utm_source', true);
        $utm_medium = (string) get_post_meta($lead_id, '_lead_utm_medium', true);
        $utm_campaign = (string) get_post_meta($lead_id, '_lead_utm_campaign', true);

        $subject_prefix = $settings['subject_prefix'];
        if (isset($context_subject_map[$context])) {
            $subject_prefix .= ' ' . $context_subject_map[$context];
        }
        $subject_suffix = $name !== '' ? $name : ('Lead #' . $lead_id);
        if ($phone !== '') {
            $subject_suffix .= ' - ' . $phone;
        }
        $subject = trim($subject_prefix . ' ' . $subject_suffix);

        $lines = [];
        $lines[] = isset($context_intro_map[$context]) ? $context_intro_map[$context] : 'Có cập nhật lead.';
        $lines[] = '';
        $lines[] = 'Họ tên: ' . ($name !== '' ? $name : '-');
        $lines[] = 'Điện thoại: ' . ($phone !== '' ? $phone : '-');
        if ($email !== '') {
            $lines[] = 'Email: ' . $email;
        }
        $lines[] = 'Kênh liên hệ ưu tiên: ' . my_theme_lead_channel_label($channel);
        if ($project_type !== '') {
            $lines[] = 'Nhu cầu: ' . $project_type;
        }
        if ($budget !== '') {
            $lines[] = 'Ngân sách: ' . $budget;
        }
        if ($message !== '') {
            $lines[] = 'Ghi chú: ' . $message;
        }
        if ($source_tag !== '') {
            $lines[] = 'Nguồn form: ' . $source_tag;
        }
        if ($source_url !== '') {
            $lines[] = 'URL gửi form: ' . $source_url;
        }
        if ($utm_source !== '' || $utm_campaign !== '') {
            $lines[] = 'UTM: ' . trim($utm_source . ' / ' . $utm_medium . ' / ' . $utm_campaign, ' /');
        }

        if (is_array($extra_lines)) {
            foreach ($extra_lines as $line) {
                if (!is_scalar($line)) {
                    continue;
                }
                $line = sanitize_text_field((string) $line);
                if ($line === '') {
                    continue;
                }
                $lines[] = $line;
            }
        }

        $lines[] = '';
        $lines[] = 'Xem chi tiết: ' . admin_url('post.php?post=' . $lead_id . '&action=edit');

        return (bool) wp_mail(
            $settings['emails'],
            $subject,
            implode("\n", $lines),
            ['Content-Type: text/plain; charset=UTF-8']
        );
    }
}

if (!function_exists('my_theme_lead_get_daily_reminder_next_timestamp')) {
    function my_theme_lead_get_daily_reminder_next_timestamp($hour = 8)
    {
        $hour = (int) $hour;
        if ($hour < 0 || $hour > 23) {
            $hour = 8;
        }

        $timezone = wp_timezone();
        $now = new DateTimeImmutable('now', $timezone);
        $target = $now->setTime($hour, 0, 0);
        if ($target->getTimestamp() <= $now->getTimestamp()) {
            $target = $target->modify('+1 day');
        }
        return (int) $target->getTimestamp();
    }
}

if (!function_exists('my_theme_lead_schedule_daily_reminder_event')) {
    function my_theme_lead_schedule_daily_reminder_event($force_reschedule = false)
    {
        $settings = my_theme_lead_get_notification_settings();
        $hook = 'my_theme_send_daily_overdue_lead_reminder';
        $force_reschedule = (bool) $force_reschedule;

        if (!$settings['reminder_enabled']) {
            $next = wp_next_scheduled($hook);
            while ($next) {
                wp_unschedule_event((int) $next, $hook);
                $next = wp_next_scheduled($hook);
            }
            return;
        }

        $next = wp_next_scheduled($hook);
        if ($next && !$force_reschedule) {
            return;
        }
        while ($next) {
            wp_unschedule_event((int) $next, $hook);
            $next = wp_next_scheduled($hook);
        }

        $first_run = my_theme_lead_get_daily_reminder_next_timestamp((int) $settings['reminder_hour']);
        if ($first_run > 0) {
            wp_schedule_event($first_run, 'daily', $hook);
        }
    }
}

if (!function_exists('my_theme_lead_run_daily_overdue_reminder')) {
    function my_theme_lead_run_daily_overdue_reminder($force = false)
    {
        $settings = my_theme_lead_get_notification_settings();
        if (!$settings['enabled'] || empty($settings['emails'])) {
            return [
                'sent' => false,
                'count' => 0,
                'total' => 0,
                'reason' => 'Đã tắt email thông báo hoặc chưa có người nhận.',
            ];
        }
        if (!$force && !$settings['reminder_enabled']) {
            return [
                'sent' => false,
                'count' => 0,
                'total' => 0,
                'reason' => 'Đã tắt nhắc quá hạn hằng ngày.',
            ];
        }

        $times = my_theme_lead_get_day_timestamps();
        $limit = (int) $settings['reminder_limit'];
        if ($limit < 5 || $limit > 100) {
            $limit = 25;
        }

        $query = new WP_Query([
            'post_type' => 'customer_lead',
            'post_status' => ['publish', 'private', 'draft'],
            'posts_per_page' => $limit,
            'fields' => 'ids',
            'orderby' => 'meta_value_num',
            'order' => 'ASC',
            'meta_key' => '_lead_next_follow_up_ts',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_lead_next_follow_up_ts',
                    'value' => $times['now'],
                    'compare' => '<',
                    'type' => 'NUMERIC',
                ],
                [
                    'relation' => 'OR',
                    [
                        'key' => '_lead_status',
                        'compare' => 'NOT EXISTS',
                    ],
                    [
                        'key' => '_lead_status',
                        'value' => ['closed', 'lost'],
                        'compare' => 'NOT IN',
                    ],
                ],
            ],
            'no_found_rows' => false,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
        ]);

        $lead_ids = is_array($query->posts) ? $query->posts : [];
        $total_overdue = max(0, (int) $query->found_posts);
        if (empty($lead_ids) || $total_overdue <= 0) {
            return [
                'sent' => false,
                'count' => 0,
                'total' => 0,
                'reason' => 'Không có lead quá hạn.',
            ];
        }

        $status_options = my_theme_lead_status_options();
        $priority_options = my_theme_lead_priority_options();
        $lines = [];
        $lines[] = 'Nhắc chăm sóc lead quá hạn từ website: ' . get_bloginfo('name');
        $lines[] = 'Thời gian: ' . current_time('mysql');
        $lines[] = 'Tổng lead quá hạn: ' . (string) $total_overdue;
        $lines[] = '';

        $position = 0;
        foreach ($lead_ids as $lead_id) {
            $lead_id = (int) $lead_id;
            if ($lead_id <= 0) {
                continue;
            }
            $position++;
            $name = trim((string) get_post_meta($lead_id, '_lead_name', true));
            $phone = trim((string) get_post_meta($lead_id, '_lead_phone', true));
            $status = (string) get_post_meta($lead_id, '_lead_status', true);
            if (!isset($status_options[$status])) {
                $status = 'new';
            }
            $priority = (string) get_post_meta($lead_id, '_lead_priority', true);
            if (!isset($priority_options[$priority])) {
                $priority = 'normal';
            }
            $assignee = trim((string) get_post_meta($lead_id, '_lead_assignee', true));
            if ($assignee === '') {
                $assignee = 'Chưa phân công';
            }
            $followup = (string) get_post_meta($lead_id, '_lead_next_follow_up', true);
            $origin = my_theme_lead_origin_label((string) get_post_meta($lead_id, '_lead_source_tag', true));
            $edit_url = admin_url('post.php?post=' . $lead_id . '&action=edit');

            $line = $position . ') ';
            $line .= ($name !== '' ? $name : ('Lead #' . $lead_id));
            if ($phone !== '') {
                $line .= ' - ' . $phone;
            }
            $line .= ' | Trạng thái: ' . $status_options[$status];
            $line .= ' | Ưu tiên: ' . $priority_options[$priority];
            $line .= ' | Hẹn: ' . ($followup !== '' ? $followup : 'Chưa rõ');
            $line .= ' | Phụ trách: ' . $assignee;
            $line .= ' | Nguồn: ' . $origin;
            $lines[] = $line;
            $lines[] = '   ' . $edit_url;
        }

        if ($total_overdue > count($lead_ids)) {
            $lines[] = '';
            $lines[] = '... và ' . (string) ($total_overdue - count($lead_ids)) . ' lead quá hạn khác.';
        }
        $lines[] = '';
        $lines[] = 'Danh sách đầy đủ: ' . admin_url('edit.php?post_type=customer_lead&lead_followup=overdue');

        $subject = trim($settings['subject_prefix'] . ' Nhắc chăm sóc quá hạn (' . $total_overdue . ')');
        $sent = (bool) wp_mail(
            $settings['emails'],
            $subject,
            implode("\n", $lines),
            ['Content-Type: text/plain; charset=UTF-8']
        );

        if ($sent) {
            update_option('my_theme_lead_daily_reminder_last_sent', current_time('mysql'));
        }

        return [
            'sent' => $sent,
            'count' => count($lead_ids),
            'total' => $total_overdue,
            'reason' => $sent ? 'Đã gửi.' : 'Không gửi được email.',
        ];
    }
}

if (!function_exists('my_theme_action_scheduler_get_table_name')) {
    function my_theme_action_scheduler_get_table_name()
    {
        global $wpdb;
        if (!isset($wpdb) || !is_object($wpdb)) {
            return '';
        }
        return (string) $wpdb->prefix . 'actionscheduler_actions';
    }
}

if (!function_exists('my_theme_action_scheduler_overdue_grace_seconds')) {
    function my_theme_action_scheduler_overdue_grace_seconds()
    {
        $seconds = (int) apply_filters('my_theme_action_scheduler_overdue_grace_seconds', 300);
        if ($seconds < 0) {
            $seconds = 0;
        }
        if ($seconds > HOUR_IN_SECONDS) {
            $seconds = HOUR_IN_SECONDS;
        }
        return $seconds;
    }
}

if (!function_exists('my_theme_action_scheduler_overdue_warning_threshold')) {
    function my_theme_action_scheduler_overdue_warning_threshold()
    {
        $threshold = (int) apply_filters('my_theme_action_scheduler_overdue_warning_threshold', 5);
        if ($threshold < 1) {
            $threshold = 1;
        }
        if ($threshold > 1000) {
            $threshold = 1000;
        }
        return $threshold;
    }
}

if (!function_exists('my_theme_action_scheduler_ignored_hooks')) {
    function my_theme_action_scheduler_ignored_hooks()
    {
        $default_hooks = [
            // MailPoet daemon trigger can appear as short-lived pending jobs.
            'mailpoet/cron/daemon-trigger',
            // Internal recurring scheduler hooks are usually re-created quickly.
            'action_scheduler_run_recurring_actions_schedule_hook',
            // Google listings cron notes can lag briefly without affecting lead flow.
            'wc_gla_cron_daily_notes',
        ];

        $hooks = apply_filters('my_theme_action_scheduler_ignored_hooks', $default_hooks);
        if (!is_array($hooks) || empty($hooks)) {
            return [];
        }

        $normalized = [];
        foreach ($hooks as $hook) {
            $hook = sanitize_text_field((string) $hook);
            if ($hook === '') {
                continue;
            }
            $normalized[$hook] = $hook;
        }

        return array_values($normalized);
    }
}

if (!function_exists('my_theme_action_scheduler_get_pending_overdue_count')) {
    function my_theme_action_scheduler_get_pending_overdue_count()
    {
        global $wpdb;
        if (!isset($wpdb) || !is_object($wpdb)) {
            return -1;
        }

        $table = my_theme_action_scheduler_get_table_name();
        if ($table === '') {
            return -1;
        }
        $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
        if (!is_string($table_exists) || $table_exists === '') {
            return -1;
        }

        $grace_seconds = my_theme_action_scheduler_overdue_grace_seconds();
        $cutoff_gmt = gmdate('Y-m-d H:i:s', time() - $grace_seconds);
        $ignored_hooks = my_theme_action_scheduler_ignored_hooks();
        $where_sql = 'status = %s AND scheduled_date_gmt <= %s';
        $params = ['pending', $cutoff_gmt];
        if (!empty($ignored_hooks)) {
            $placeholders = implode(', ', array_fill(0, count($ignored_hooks), '%s'));
            $where_sql .= " AND hook NOT IN ({$placeholders})";
            $params = array_merge($params, $ignored_hooks);
        }

        $count_sql = "SELECT COUNT(1) FROM {$table} WHERE {$where_sql}";
        $count = $wpdb->get_var($wpdb->prepare($count_sql, $params));
        return max(0, (int) $count);
    }
}

if (!function_exists('my_theme_action_scheduler_run_queue_batches')) {
    function my_theme_action_scheduler_run_queue_batches($max_batches = 10)
    {
        $max_batches = (int) $max_batches;
        if ($max_batches < 1) {
            $max_batches = 1;
        }
        if ($max_batches > 20) {
            $max_batches = 20;
        }

        $before = my_theme_action_scheduler_get_pending_overdue_count();
        if ($before < 0) {
            return [
                'supported' => false,
                'before' => 0,
                'after' => 0,
                'processed' => 0,
                'batches' => 0,
                'message' => 'Không tìm thấy bảng Action Scheduler.',
            ];
        }

        $runner_supported = class_exists('ActionScheduler_QueueRunner');
        if (!$runner_supported) {
            return [
                'supported' => false,
                'before' => $before,
                'after' => $before,
                'processed' => 0,
                'batches' => 0,
                'message' => 'Action Scheduler chưa sẵn sàng trong runtime hiện tại.',
            ];
        }

        $batches = 0;
        $stagnant_rounds = 0;
        for ($i = 0; $i < $max_batches; $i++) {
            $pending_before = my_theme_action_scheduler_get_pending_overdue_count();
            if ($pending_before <= 0) {
                break;
            }

            $batches++;
            $runner = ActionScheduler_QueueRunner::instance();
            if ($runner && method_exists($runner, 'run')) {
                $runner->run();
            } else {
                do_action('action_scheduler_run_queue', 'my_theme_manual');
            }

            $pending_after = my_theme_action_scheduler_get_pending_overdue_count();
            if ($pending_after <= 0) {
                break;
            }
            if ($pending_after >= $pending_before) {
                $stagnant_rounds++;
                if ($stagnant_rounds >= 2) {
                    break;
                }
            } else {
                $stagnant_rounds = 0;
            }
        }

        $after = my_theme_action_scheduler_get_pending_overdue_count();
        if ($after < 0) {
            $after = $before;
        }
        $processed = max(0, $before - $after);

        return [
            'supported' => true,
            'before' => $before,
            'after' => $after,
            'processed' => $processed,
            'batches' => $batches,
            'message' => 'Queue đã được chạy thủ công.',
        ];
    }
}

if (!function_exists('my_theme_action_scheduler_get_overdue_hook_counts')) {
    function my_theme_action_scheduler_get_overdue_hook_counts($limit = 8)
    {
        global $wpdb;
        if (!isset($wpdb) || !is_object($wpdb)) {
            return [];
        }

        $table = my_theme_action_scheduler_get_table_name();
        if ($table === '') {
            return [];
        }
        $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
        if (!is_string($table_exists) || $table_exists === '') {
            return [];
        }

        $limit = (int) $limit;
        if ($limit < 1) {
            $limit = 8;
        }
        if ($limit > 20) {
            $limit = 20;
        }

        $grace_seconds = my_theme_action_scheduler_overdue_grace_seconds();
        $cutoff_gmt = gmdate('Y-m-d H:i:s', time() - $grace_seconds);
        $ignored_hooks = my_theme_action_scheduler_ignored_hooks();
        $where_sql = 'status = %s AND scheduled_date_gmt <= %s';
        $params = ['pending', $cutoff_gmt];
        if (!empty($ignored_hooks)) {
            $placeholders = implode(', ', array_fill(0, count($ignored_hooks), '%s'));
            $where_sql .= " AND hook NOT IN ({$placeholders})";
            $params = array_merge($params, $ignored_hooks);
        }

        $query_sql = "SELECT hook, COUNT(1) AS total FROM {$table} WHERE {$where_sql} GROUP BY hook ORDER BY total DESC LIMIT {$limit}";
        $sql = $wpdb->prepare($query_sql, $params);
        $rows = $wpdb->get_results($sql, ARRAY_A);
        if (!is_array($rows) || empty($rows)) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $hook = isset($row['hook']) ? sanitize_text_field((string) $row['hook']) : '';
            $count = isset($row['total']) ? max(0, (int) $row['total']) : 0;
            if ($hook === '' || $count <= 0) {
                continue;
            }
            $items[] = [
                'hook' => $hook,
                'count' => $count,
            ];
        }

        return $items;
    }
}

if (!function_exists('my_theme_action_scheduler_repair_queue')) {
    function my_theme_action_scheduler_repair_queue($stuck_minutes = 30)
    {
        global $wpdb;
        if (!isset($wpdb) || !is_object($wpdb)) {
            return [
                'supported' => false,
                'message' => 'Không có kết nối database.',
                'released_pending_claims' => 0,
                'requeued_stuck' => 0,
                'locks_cleared' => 0,
            ];
        }

        $actions_table = my_theme_action_scheduler_get_table_name();
        if ($actions_table === '') {
            return [
                'supported' => false,
                'message' => 'Không xác định được bảng Action Scheduler.',
                'released_pending_claims' => 0,
                'requeued_stuck' => 0,
                'locks_cleared' => 0,
            ];
        }
        $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($actions_table)));
        if (!is_string($table_exists) || $table_exists === '') {
            return [
                'supported' => false,
                'message' => 'Không tìm thấy bảng Action Scheduler.',
                'released_pending_claims' => 0,
                'requeued_stuck' => 0,
                'locks_cleared' => 0,
            ];
        }

        $stuck_minutes = (int) $stuck_minutes;
        if ($stuck_minutes < 5) {
            $stuck_minutes = 30;
        }

        $released_pending_claims = 0;
        $requeued_stuck = 0;

        $released = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$actions_table} SET claim_id = 0 WHERE status = %s AND claim_id <> 0",
                'pending'
            )
        );
        if (is_int($released) && $released > 0) {
            $released_pending_claims = $released;
        }

        $cutoff = gmdate('Y-m-d H:i:s', time() - ($stuck_minutes * MINUTE_IN_SECONDS));
        $requeued = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$actions_table} SET status = %s, claim_id = 0 WHERE status = %s AND last_attempt_gmt <= %s",
                'pending',
                'in-progress',
                $cutoff
            )
        );
        if (is_int($requeued) && $requeued > 0) {
            $requeued_stuck = $requeued;
        }

        $lock_keys = [
            'action_scheduler_lock_async-request-runner',
            'action_scheduler_lock_wp_cron',
            'action_scheduler_lock_run_queue',
            'action_scheduler_run_queue',
        ];
        $locks_cleared = 0;
        foreach ($lock_keys as $lock_key) {
            if (delete_transient($lock_key)) {
                $locks_cleared++;
            }
            if (delete_option($lock_key)) {
                $locks_cleared++;
            }
            if (delete_option('_transient_' . $lock_key)) {
                $locks_cleared++;
            }
            if (delete_option('_transient_timeout_' . $lock_key)) {
                $locks_cleared++;
            }
        }

        return [
            'supported' => true,
            'message' => 'Đã reset claim/lock để chạy queue lại.',
            'released_pending_claims' => $released_pending_claims,
            'requeued_stuck' => $requeued_stuck,
            'locks_cleared' => $locks_cleared,
        ];
    }
}

if (!function_exists('my_theme_lead_get_webhook_settings')) {
    function my_theme_lead_get_webhook_settings()
    {
        $enabled = get_option('my_theme_lead_webhook_enabled', '0');
        $url = get_option('my_theme_lead_webhook_url', '');
        $secret = get_option('my_theme_lead_webhook_secret', '');
        $timeout = (int) get_option('my_theme_lead_webhook_timeout', 8);
        $retry_enabled = get_option('my_theme_lead_webhook_retry_enabled', '1');
        $retry_max = (int) get_option('my_theme_lead_webhook_retry_max', 5);
        if ($timeout < 3 || $timeout > 30) {
            $timeout = 8;
        }
        if ($retry_max < 1 || $retry_max > 20) {
            $retry_max = 5;
        }

        return [
            'enabled' => ((string) $enabled === '1'),
            'url' => esc_url_raw((string) $url),
            'secret' => trim((string) $secret),
            'timeout' => $timeout,
            'retry_enabled' => ((string) $retry_enabled === '1'),
            'retry_max' => $retry_max,
        ];
    }
}

if (!function_exists('my_theme_lead_webhook_status_label')) {
    function my_theme_lead_webhook_status_label($status = '')
    {
        $status = sanitize_key((string) $status);
        $labels = [
            'success' => 'Thành công',
            'failed' => 'Thất bại',
            'skipped' => 'Bỏ qua',
        ];
        return isset($labels[$status]) ? $labels[$status] : '-';
    }
}

if (!function_exists('my_theme_lead_get_activity_log')) {
    function my_theme_lead_get_activity_log($lead_id)
    {
        $lead_id = (int) $lead_id;
        if ($lead_id <= 0) {
            return [];
        }
        $log = get_post_meta($lead_id, '_lead_activity_log', true);
        return is_array($log) ? $log : [];
    }
}

if (!function_exists('my_theme_lead_add_activity')) {
    function my_theme_lead_add_activity($lead_id, $event = 'update', $message = '', $context = [])
    {
        $lead_id = (int) $lead_id;
        if ($lead_id <= 0 || get_post_type($lead_id) !== 'customer_lead') {
            return;
        }

        $event = sanitize_key((string) $event);
        if ($event === '') {
            $event = 'update';
        }
        $message = sanitize_text_field((string) $message);
        if ($message === '') {
            $message = 'Cập nhật lead';
        }

        $clean_context = [];
        if (is_array($context)) {
            foreach ($context as $key => $value) {
                $clean_key = sanitize_key((string) $key);
                if ($clean_key === '') {
                    continue;
                }
                if (is_scalar($value)) {
                    $clean_context[$clean_key] = sanitize_text_field((string) $value);
                }
            }
        }

        $log = my_theme_lead_get_activity_log($lead_id);
        $log[] = [
            'time' => current_time('mysql'),
            'event' => $event,
            'message' => $message,
            'user_id' => get_current_user_id(),
            'context' => $clean_context,
        ];

        if (count($log) > 80) {
            $log = array_slice($log, -80);
        }

        update_post_meta($lead_id, '_lead_activity_log', $log);
    }
}

if (!function_exists('my_theme_build_lead_webhook_payload')) {
    function my_theme_build_lead_webhook_payload($lead_id, $event = 'lead_created')
    {
        $lead_id = (int) $lead_id;
        if ($lead_id <= 0 || get_post_type($lead_id) !== 'customer_lead') {
            return [];
        }

        $payload = [
            'event' => sanitize_key((string) $event),
            'lead_id' => $lead_id,
            'site' => [
                'name' => get_bloginfo('name'),
                'url' => home_url('/'),
            ],
            'lead' => [
                'name' => (string) get_post_meta($lead_id, '_lead_name', true),
                'phone' => (string) get_post_meta($lead_id, '_lead_phone', true),
                'email' => (string) get_post_meta($lead_id, '_lead_email', true),
                'contact_channel' => (string) get_post_meta($lead_id, '_lead_contact_channel', true),
                'project_type' => (string) get_post_meta($lead_id, '_lead_project_type', true),
                'budget' => (string) get_post_meta($lead_id, '_lead_budget', true),
                'message' => (string) get_post_meta($lead_id, '_lead_message', true),
                'status' => (string) get_post_meta($lead_id, '_lead_status', true),
                'priority' => (string) get_post_meta($lead_id, '_lead_priority', true),
                'duplicate_submit_count' => (int) get_post_meta($lead_id, '_lead_duplicate_submit_count', true),
                'order_count' => (int) get_post_meta($lead_id, '_lead_order_count', true),
                'last_order_id' => (int) get_post_meta($lead_id, '_lead_last_order_id', true),
                'last_order_number' => (string) get_post_meta($lead_id, '_lead_last_order_number', true),
                'total_order_value' => (string) get_post_meta($lead_id, '_lead_total_order_value', true),
                'next_follow_up' => (string) get_post_meta($lead_id, '_lead_next_follow_up', true),
                'next_follow_up_status' => my_theme_lead_followup_status_label((int) get_post_meta($lead_id, '_lead_next_follow_up_ts', true)),
                'assignee' => (string) get_post_meta($lead_id, '_lead_assignee', true),
                'origin' => my_theme_lead_origin_label((string) get_post_meta($lead_id, '_lead_source_tag', true)),
                'source_tag' => (string) get_post_meta($lead_id, '_lead_source_tag', true),
                'source_url' => (string) get_post_meta($lead_id, '_lead_source_url', true),
                'referrer' => (string) get_post_meta($lead_id, '_lead_referrer', true),
                'utm_source' => (string) get_post_meta($lead_id, '_lead_utm_source', true),
                'utm_medium' => (string) get_post_meta($lead_id, '_lead_utm_medium', true),
                'utm_campaign' => (string) get_post_meta($lead_id, '_lead_utm_campaign', true),
                'utm_term' => (string) get_post_meta($lead_id, '_lead_utm_term', true),
                'utm_content' => (string) get_post_meta($lead_id, '_lead_utm_content', true),
                'fbclid' => (string) get_post_meta($lead_id, '_lead_fbclid', true),
                'gclid' => (string) get_post_meta($lead_id, '_lead_gclid', true),
                'ip' => (string) get_post_meta($lead_id, '_lead_ip', true),
                'user_agent' => (string) get_post_meta($lead_id, '_lead_user_agent', true),
                'submitted_at' => (string) get_post_meta($lead_id, '_lead_submitted_at', true),
                'last_status_change' => (string) get_post_meta($lead_id, '_lead_last_status_change', true),
                'edit_url' => admin_url('post.php?post=' . $lead_id . '&action=edit'),
            ],
        ];

        return $payload;
    }
}

if (!function_exists('my_theme_send_lead_webhook')) {
    function my_theme_send_lead_webhook($lead_id, $event = 'lead_created', $force = false)
    {
        $lead_id = (int) $lead_id;
        if ($lead_id <= 0 || get_post_type($lead_id) !== 'customer_lead') {
            return new WP_Error('invalid_lead', 'Invalid lead.');
        }

        $settings = my_theme_lead_get_webhook_settings();
        if ((!$settings['enabled'] || $settings['url'] === '') && !$force) {
            update_post_meta($lead_id, '_lead_webhook_last_status', 'skipped');
            update_post_meta($lead_id, '_lead_webhook_last_sent_at', current_time('mysql'));
            update_post_meta($lead_id, '_lead_webhook_last_error', 'Webhook disabled');
            update_post_meta($lead_id, '_lead_webhook_last_event', sanitize_key((string) $event));
            my_theme_lead_add_activity($lead_id, 'webhook_skipped', 'Bỏ qua webhook vì chưa bật cấu hình.', [
                'event' => (string) $event,
            ]);
            return new WP_Error('webhook_disabled', 'Webhook disabled.');
        }
        if ($settings['url'] === '') {
            return new WP_Error('webhook_url_empty', 'Webhook URL is empty.');
        }

        $payload = my_theme_build_lead_webhook_payload($lead_id, $event);
        if (empty($payload)) {
            return new WP_Error('payload_error', 'Cannot build payload.');
        }

        $body = wp_json_encode($payload);
        if (!is_string($body) || $body === '') {
            return new WP_Error('encode_error', 'Cannot encode payload.');
        }

        $headers = [
            'Content-Type' => 'application/json; charset=utf-8',
            'X-Lead-Event' => sanitize_key((string) $event),
            'X-Lead-Id' => (string) $lead_id,
        ];
        if ($settings['secret'] !== '') {
            $headers['X-Lead-Signature'] = hash_hmac('sha256', $body, $settings['secret']);
        }

        $attempt_count = (int) get_post_meta($lead_id, '_lead_webhook_attempt_count', true);
        update_post_meta($lead_id, '_lead_webhook_attempt_count', $attempt_count + 1);
        update_post_meta($lead_id, '_lead_webhook_last_event', sanitize_key((string) $event));

        $response = wp_remote_post($settings['url'], [
            'timeout' => $settings['timeout'],
            'method' => 'POST',
            'headers' => $headers,
            'body' => $body,
        ]);

        $sent_at = current_time('mysql');
        update_post_meta($lead_id, '_lead_webhook_last_sent_at', $sent_at);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            update_post_meta($lead_id, '_lead_webhook_last_status', 'failed');
            update_post_meta($lead_id, '_lead_webhook_last_code', 0);
            update_post_meta($lead_id, '_lead_webhook_last_error', $error_message);
            update_post_meta($lead_id, '_lead_webhook_last_response', '');
            $consecutive_failures = (int) get_post_meta($lead_id, '_lead_webhook_consecutive_failures', true);
            update_post_meta($lead_id, '_lead_webhook_consecutive_failures', $consecutive_failures + 1);
            my_theme_lead_add_activity($lead_id, 'webhook_failed', 'Webhook thất bại: ' . $error_message, [
                'event' => (string) $event,
                'http_code' => '0',
            ]);
            return new WP_Error('webhook_request_failed', $error_message);
        }

        $status_code = (int) wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        if (!is_string($response_body)) {
            $response_body = '';
        }
        $response_body = trim($response_body);
        if (strlen($response_body) > 500) {
            $response_body = substr($response_body, 0, 500);
        }

        update_post_meta($lead_id, '_lead_webhook_last_code', $status_code);
        update_post_meta($lead_id, '_lead_webhook_last_response', $response_body);
        update_post_meta($lead_id, '_lead_webhook_last_error', '');
        $delivery_count = (int) get_post_meta($lead_id, '_lead_webhook_delivery_count', true);

        if ($status_code >= 200 && $status_code < 300) {
            update_post_meta($lead_id, '_lead_webhook_last_status', 'success');
            update_post_meta($lead_id, '_lead_webhook_delivery_count', $delivery_count + 1);
            update_post_meta($lead_id, '_lead_webhook_consecutive_failures', 0);
            my_theme_lead_add_activity($lead_id, 'webhook_success', 'Webhook gửi thành công.', [
                'event' => (string) $event,
                'http_code' => (string) $status_code,
            ]);
            return true;
        }

        $error_text = 'HTTP ' . $status_code;
        if ($response_body !== '') {
            $error_text .= ' - ' . $response_body;
        }
        update_post_meta($lead_id, '_lead_webhook_last_status', 'failed');
        update_post_meta($lead_id, '_lead_webhook_last_error', $error_text);
        $consecutive_failures = (int) get_post_meta($lead_id, '_lead_webhook_consecutive_failures', true);
        update_post_meta($lead_id, '_lead_webhook_consecutive_failures', $consecutive_failures + 1);
        my_theme_lead_add_activity($lead_id, 'webhook_failed', 'Webhook trả lỗi: ' . $error_text, [
            'event' => (string) $event,
            'http_code' => (string) $status_code,
        ]);
        return new WP_Error('webhook_bad_status', $error_text);
    }
}

add_filter('cron_schedules', function ($schedules) {
    if (!is_array($schedules)) {
        $schedules = [];
    }
    if (!isset($schedules['my_theme_every_fifteen_minutes'])) {
        $schedules['my_theme_every_fifteen_minutes'] = [
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display' => 'Every 15 minutes',
        ];
    }
    return $schedules;
});

add_action('init', function () {
    if (!wp_next_scheduled('my_theme_retry_failed_lead_webhooks')) {
        wp_schedule_event(time() + (5 * MINUTE_IN_SECONDS), 'my_theme_every_fifteen_minutes', 'my_theme_retry_failed_lead_webhooks');
    }
});

add_action('init', function () {
    my_theme_lead_schedule_daily_reminder_event();
}, 20);

add_action('my_theme_send_daily_overdue_lead_reminder', function () {
    my_theme_lead_run_daily_overdue_reminder(false);
});

if (!function_exists('my_theme_retry_failed_lead_webhooks')) {
    function my_theme_retry_failed_lead_webhooks()
    {
        $settings = my_theme_lead_get_webhook_settings();
        if (!$settings['enabled'] || !$settings['retry_enabled'] || $settings['url'] === '') {
            return 0;
        }

        $retry_max = max(1, (int) $settings['retry_max']);
        $failed_ids = get_posts([
            'post_type' => 'customer_lead',
            'post_status' => ['publish', 'private', 'draft'],
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_query' => [
                [
                    'key' => '_lead_webhook_last_status',
                    'value' => 'failed',
                ],
                [
                    'relation' => 'OR',
                    [
                        'key' => '_lead_webhook_consecutive_failures',
                        'compare' => 'NOT EXISTS',
                    ],
                    [
                        'key' => '_lead_webhook_consecutive_failures',
                        'value' => $retry_max,
                        'compare' => '<',
                        'type' => 'NUMERIC',
                    ],
                ],
            ],
        ]);
        if (!is_array($failed_ids) || empty($failed_ids)) {
            return 0;
        }

        $processed = 0;
        foreach ($failed_ids as $lead_id) {
            $lead_id = (int) $lead_id;
            if ($lead_id <= 0) {
                continue;
            }
            my_theme_send_lead_webhook($lead_id, 'lead_retry', false);
            $processed++;
        }

        return $processed;
    }
}
add_action('my_theme_retry_failed_lead_webhooks', 'my_theme_retry_failed_lead_webhooks');

if (!function_exists('my_theme_handle_customer_lead_submit')) {
    function my_theme_handle_customer_lead_submit()
    {
        $redirect_to = isset($_POST['redirect_to']) ? wp_unslash((string) $_POST['redirect_to']) : '';
        $form_key = isset($_POST['lead_form']) ? sanitize_key((string) wp_unslash($_POST['lead_form'])) : 'lead';
        if ($form_key === '') {
            $form_key = 'lead';
        }

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            my_theme_lead_redirect($redirect_to, 'error', $form_key);
        }

        $nonce = isset($_POST['my_theme_lead_nonce']) ? wp_unslash((string) $_POST['my_theme_lead_nonce']) : '';
        if (!wp_verify_nonce($nonce, 'my_theme_submit_customer_lead')) {
            my_theme_lead_redirect($redirect_to, 'error', $form_key);
        }

        $honeypot = isset($_POST['lead_website']) ? trim((string) wp_unslash($_POST['lead_website'])) : '';
        if ($honeypot !== '') {
            my_theme_lead_redirect($redirect_to, 'success', $form_key);
        }

        $name = isset($_POST['lead_name']) ? sanitize_text_field((string) wp_unslash($_POST['lead_name'])) : '';
        $phone_raw = isset($_POST['lead_phone']) ? sanitize_text_field((string) wp_unslash($_POST['lead_phone'])) : '';
        $phone = preg_replace('/[^0-9+]/', '', $phone_raw);
        $email_raw = isset($_POST['lead_email']) ? sanitize_email((string) wp_unslash($_POST['lead_email'])) : '';
        $email = is_email($email_raw) ? $email_raw : '';
        $contact_channel = isset($_POST['lead_contact_channel']) ? sanitize_key((string) wp_unslash($_POST['lead_contact_channel'])) : 'phone';
        if (!in_array($contact_channel, ['phone', 'zalo', 'email'], true)) {
            $contact_channel = 'phone';
        }

        $project_type = isset($_POST['lead_project_type']) ? sanitize_text_field((string) wp_unslash($_POST['lead_project_type'])) : '';
        $budget = isset($_POST['lead_budget']) ? sanitize_text_field((string) wp_unslash($_POST['lead_budget'])) : '';
        $message = isset($_POST['lead_message']) ? sanitize_textarea_field((string) wp_unslash($_POST['lead_message'])) : '';
        $source_tag = isset($_POST['lead_source_tag']) ? sanitize_text_field((string) wp_unslash($_POST['lead_source_tag'])) : '';
        $source_url = isset($_POST['lead_source_url']) ? esc_url_raw((string) wp_unslash($_POST['lead_source_url'])) : '';
        $utm_source = isset($_POST['lead_utm_source']) ? sanitize_text_field((string) wp_unslash($_POST['lead_utm_source'])) : '';
        $utm_medium = isset($_POST['lead_utm_medium']) ? sanitize_text_field((string) wp_unslash($_POST['lead_utm_medium'])) : '';
        $utm_campaign = isset($_POST['lead_utm_campaign']) ? sanitize_text_field((string) wp_unslash($_POST['lead_utm_campaign'])) : '';
        $utm_term = isset($_POST['lead_utm_term']) ? sanitize_text_field((string) wp_unslash($_POST['lead_utm_term'])) : '';
        $utm_content = isset($_POST['lead_utm_content']) ? sanitize_text_field((string) wp_unslash($_POST['lead_utm_content'])) : '';
        $fbclid = isset($_POST['lead_fbclid']) ? sanitize_text_field((string) wp_unslash($_POST['lead_fbclid'])) : '';
        $gclid = isset($_POST['lead_gclid']) ? sanitize_text_field((string) wp_unslash($_POST['lead_gclid'])) : '';
        $consent = !empty($_POST['lead_consent']) ? 'yes' : 'no';

        if ($name === '' || $phone === '' || strlen($phone) < 8 || $consent !== 'yes') {
            my_theme_lead_redirect($redirect_to, 'invalid', $form_key);
        }
        if ($contact_channel === 'email' && $email === '') {
            my_theme_lead_redirect($redirect_to, 'invalid', $form_key);
        }

        $submitted_at = current_time('mysql');
        $title = $name . ' - ' . $phone;
        $referrer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw((string) $_SERVER['HTTP_REFERER']) : '';
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 255)) : '';
        $phone_normalized = my_theme_lead_normalize_phone($phone);
        $existing_lead_id = my_theme_lead_find_existing_id($phone, $email);

        if ($existing_lead_id > 0) {
            $post_content = $message !== '' ? $message : (string) get_post_field('post_content', $existing_lead_id);
            wp_update_post([
                'ID' => $existing_lead_id,
                'post_title' => $title,
                'post_content' => $post_content,
            ]);

            update_post_meta($existing_lead_id, '_lead_name', $name);
            update_post_meta($existing_lead_id, '_lead_phone', $phone);
            update_post_meta($existing_lead_id, '_lead_phone_normalized', $phone_normalized);
            update_post_meta($existing_lead_id, '_lead_contact_channel', $contact_channel);
            update_post_meta($existing_lead_id, '_lead_last_submission_at', $submitted_at);
            update_post_meta($existing_lead_id, '_lead_consent', $consent);
            update_post_meta($existing_lead_id, '_lead_ip', my_theme_lead_get_request_ip());
            update_post_meta($existing_lead_id, '_lead_user_agent', $user_agent);
            if ($email !== '') {
                update_post_meta($existing_lead_id, '_lead_email', $email);
            }
            if ($project_type !== '') {
                update_post_meta($existing_lead_id, '_lead_project_type', $project_type);
            }
            if ($budget !== '') {
                update_post_meta($existing_lead_id, '_lead_budget', $budget);
            }
            if ($message !== '') {
                update_post_meta($existing_lead_id, '_lead_message', $message);
            }
            if ($source_tag !== '') {
                update_post_meta($existing_lead_id, '_lead_source_tag', $source_tag);
            }
            if ($source_url !== '') {
                update_post_meta($existing_lead_id, '_lead_source_url', $source_url);
            }
            if ($utm_source !== '') {
                update_post_meta($existing_lead_id, '_lead_utm_source', $utm_source);
            }
            if ($utm_medium !== '') {
                update_post_meta($existing_lead_id, '_lead_utm_medium', $utm_medium);
            }
            if ($utm_campaign !== '') {
                update_post_meta($existing_lead_id, '_lead_utm_campaign', $utm_campaign);
            }
            if ($utm_term !== '') {
                update_post_meta($existing_lead_id, '_lead_utm_term', $utm_term);
            }
            if ($utm_content !== '') {
                update_post_meta($existing_lead_id, '_lead_utm_content', $utm_content);
            }
            if ($fbclid !== '') {
                update_post_meta($existing_lead_id, '_lead_fbclid', $fbclid);
            }
            if ($gclid !== '') {
                update_post_meta($existing_lead_id, '_lead_gclid', $gclid);
            }
            if ($referrer !== '') {
                update_post_meta($existing_lead_id, '_lead_referrer', $referrer);
            }

            $first_submitted_at = (string) get_post_meta($existing_lead_id, '_lead_submitted_at', true);
            if ($first_submitted_at === '') {
                update_post_meta($existing_lead_id, '_lead_submitted_at', $submitted_at);
            }

            $status_options = my_theme_lead_status_options();
            $old_status = (string) get_post_meta($existing_lead_id, '_lead_status', true);
            if (!isset($status_options[$old_status])) {
                $old_status = 'new';
            }
            if (in_array($old_status, ['closed', 'lost'], true)) {
                update_post_meta($existing_lead_id, '_lead_status', 'new');
                update_post_meta($existing_lead_id, '_lead_last_status_change', $submitted_at);
                my_theme_lead_add_activity($existing_lead_id, 'status_changed', 'Mở lại lead do khách gửi lại form: ' . $status_options[$old_status] . ' -> ' . $status_options['new'], [
                    'from' => $old_status,
                    'to' => 'new',
                ]);
            }

            $duplicate_submit_count = (int) get_post_meta($existing_lead_id, '_lead_duplicate_submit_count', true);
            $duplicate_submit_count++;
            update_post_meta($existing_lead_id, '_lead_duplicate_submit_count', $duplicate_submit_count);

            my_theme_lead_add_activity($existing_lead_id, 'lead_resubmitted', 'Khách gửi lại form, gộp vào lead hiện có.', [
                'source_tag' => $source_tag !== '' ? $source_tag : '-',
                'submit_count' => (string) $duplicate_submit_count,
            ]);

            my_theme_lead_send_notification_email($existing_lead_id, 'updated', [
                'Số lần gửi lại: ' . (string) $duplicate_submit_count,
            ]);
            my_theme_send_lead_webhook($existing_lead_id, 'lead_updated', false);
            my_theme_lead_redirect($redirect_to, 'success', $form_key);
        }

        $lead_id = wp_insert_post([
            'post_type' => 'customer_lead',
            'post_status' => 'publish',
            'post_title' => $title,
            'post_content' => $message,
        ], true);

        if (is_wp_error($lead_id) || (int) $lead_id <= 0) {
            my_theme_lead_redirect($redirect_to, 'error', $form_key);
        }

        update_post_meta($lead_id, '_lead_name', $name);
        update_post_meta($lead_id, '_lead_phone', $phone);
        update_post_meta($lead_id, '_lead_phone_normalized', $phone_normalized);
        update_post_meta($lead_id, '_lead_email', $email);
        update_post_meta($lead_id, '_lead_contact_channel', $contact_channel);
        update_post_meta($lead_id, '_lead_project_type', $project_type);
        update_post_meta($lead_id, '_lead_budget', $budget);
        update_post_meta($lead_id, '_lead_message', $message);
        update_post_meta($lead_id, '_lead_source_tag', $source_tag);
        update_post_meta($lead_id, '_lead_source_url', $source_url);
        update_post_meta($lead_id, '_lead_utm_source', $utm_source);
        update_post_meta($lead_id, '_lead_utm_medium', $utm_medium);
        update_post_meta($lead_id, '_lead_utm_campaign', $utm_campaign);
        update_post_meta($lead_id, '_lead_utm_term', $utm_term);
        update_post_meta($lead_id, '_lead_utm_content', $utm_content);
        update_post_meta($lead_id, '_lead_fbclid', $fbclid);
        update_post_meta($lead_id, '_lead_gclid', $gclid);
        update_post_meta($lead_id, '_lead_referrer', $referrer);
        update_post_meta($lead_id, '_lead_ip', my_theme_lead_get_request_ip());
        update_post_meta($lead_id, '_lead_user_agent', $user_agent);
        update_post_meta($lead_id, '_lead_submitted_at', $submitted_at);
        update_post_meta($lead_id, '_lead_last_submission_at', $submitted_at);
        update_post_meta($lead_id, '_lead_status', 'new');
        update_post_meta($lead_id, '_lead_priority', 'normal');
        update_post_meta($lead_id, '_lead_last_status_change', $submitted_at);
        update_post_meta($lead_id, '_lead_consent', $consent);
        my_theme_lead_add_activity($lead_id, 'lead_created', 'Tạo lead mới từ form.', [
            'source_tag' => $source_tag !== '' ? $source_tag : '-',
            'phone' => $phone,
        ]);

        my_theme_lead_send_notification_email($lead_id, 'new');
        my_theme_send_lead_webhook($lead_id, 'lead_created', false);
        my_theme_lead_redirect($redirect_to, 'success', $form_key);
    }
}
add_action('admin_post_nopriv_my_theme_submit_customer_lead', 'my_theme_handle_customer_lead_submit');
add_action('admin_post_my_theme_submit_customer_lead', 'my_theme_handle_customer_lead_submit');

if (!function_exists('my_theme_lead_build_wc_order_items_summary')) {
    function my_theme_lead_build_wc_order_items_summary($order, $limit = 4)
    {
        if (!is_object($order) || !method_exists($order, 'get_items')) {
            return '';
        }

        $limit = (int) $limit;
        if ($limit <= 0) {
            $limit = 4;
        }

        $items = $order->get_items();
        if (!is_array($items) && !($items instanceof Traversable)) {
            return '';
        }

        $parts = [];
        $total_items = 0;
        foreach ($items as $item) {
            if (!is_object($item) || !method_exists($item, 'get_name')) {
                continue;
            }
            $total_items++;
            if (count($parts) >= $limit) {
                continue;
            }
            $name = sanitize_text_field((string) $item->get_name());
            if ($name === '') {
                continue;
            }
            $qty = method_exists($item, 'get_quantity') ? (int) $item->get_quantity() : 1;
            if ($qty < 1) {
                $qty = 1;
            }
            $parts[] = $name . ' x' . $qty;
        }

        if (empty($parts)) {
            return '';
        }

        $summary = implode(', ', $parts);
        if ($total_items > count($parts)) {
            $summary .= ', ...';
        }

        return $summary;
    }
}

if (!function_exists('my_theme_sync_customer_lead_from_wc_order')) {
    function my_theme_sync_customer_lead_from_wc_order($order_id)
    {
        $order_id = (int) $order_id;
        if ($order_id <= 0 || !function_exists('wc_get_order')) {
            return 0;
        }

        $existing_synced_id = (int) get_post_meta($order_id, '_my_theme_lead_id', true);
        $synced_at = (string) get_post_meta($order_id, '_my_theme_lead_synced', true);
        if ($existing_synced_id > 0 && $synced_at !== '') {
            return $existing_synced_id;
        }

        $order = wc_get_order($order_id);
        if (!$order || !is_object($order) || !method_exists($order, 'get_billing_phone')) {
            return 0;
        }

        $phone_raw = sanitize_text_field((string) $order->get_billing_phone());
        $phone = preg_replace('/[^0-9+]/', '', $phone_raw);
        $phone_normalized = my_theme_lead_normalize_phone($phone);
        $email_raw = sanitize_email((string) $order->get_billing_email());
        $email = is_email($email_raw) ? $email_raw : '';
        if ($phone === '' && $email === '') {
            return 0;
        }

        $name = '';
        if (method_exists($order, 'get_formatted_billing_full_name')) {
            $name = trim(wp_strip_all_tags((string) $order->get_formatted_billing_full_name()));
        }
        if ($name === '' || strtolower($name) === 'n/a') {
            $first_name = method_exists($order, 'get_billing_first_name') ? sanitize_text_field((string) $order->get_billing_first_name()) : '';
            $last_name = method_exists($order, 'get_billing_last_name') ? sanitize_text_field((string) $order->get_billing_last_name()) : '';
            $name = trim($last_name . ' ' . $first_name);
        }
        if ($name === '') {
            $name = 'Khách đơn #' . $order_id;
        }

        $order_number = method_exists($order, 'get_order_number') ? sanitize_text_field((string) $order->get_order_number()) : (string) $order_id;
        $order_total = method_exists($order, 'get_total') ? (float) $order->get_total() : 0.0;
        $order_total_text = method_exists($order, 'get_formatted_order_total') ? wp_strip_all_tags((string) $order->get_formatted_order_total()) : (string) $order_total;
        $customer_note = method_exists($order, 'get_customer_note') ? sanitize_textarea_field((string) $order->get_customer_note()) : '';
        $items_summary = my_theme_lead_build_wc_order_items_summary($order, 4);
        $source_tag = 'woocommerce-checkout';
        $source_url = home_url('/checkout');
        $submitted_at = current_time('mysql');

        $summary_parts = ['Đơn WooCommerce #' . $order_number];
        if ($items_summary !== '') {
            $summary_parts[] = 'Sản phẩm: ' . $items_summary;
        }
        if ($order_total_text !== '') {
            $summary_parts[] = 'Tổng: ' . $order_total_text;
        }
        if ($customer_note !== '') {
            $summary_parts[] = 'Ghi chú khách: ' . $customer_note;
        }
        $summary_text = implode(' | ', $summary_parts);

        $lead_id = my_theme_lead_find_existing_id($phone, $email);
        $title = $name . ($phone !== '' ? (' - ' . $phone) : '');

        if ($lead_id > 0) {
            $current_content = (string) get_post_field('post_content', $lead_id);
            $next_content = $summary_text;
            if ($current_content !== '' && strpos($current_content, $summary_text) === false) {
                $next_content = $summary_text . "\n" . $current_content;
            }
            wp_update_post([
                'ID' => $lead_id,
                'post_title' => $title,
                'post_content' => $next_content,
            ]);

            update_post_meta($lead_id, '_lead_name', $name);
            if ($phone !== '') {
                update_post_meta($lead_id, '_lead_phone', $phone);
            }
            if ($phone_normalized !== '') {
                update_post_meta($lead_id, '_lead_phone_normalized', $phone_normalized);
            }
            if ($email !== '') {
                update_post_meta($lead_id, '_lead_email', $email);
            }
            update_post_meta($lead_id, '_lead_contact_channel', 'phone');
            update_post_meta($lead_id, '_lead_project_type', 'Khách từ đơn hàng WooCommerce #' . $order_number);
            if ($order_total_text !== '') {
                update_post_meta($lead_id, '_lead_budget', $order_total_text);
            }
            update_post_meta($lead_id, '_lead_message', $summary_text);
            update_post_meta($lead_id, '_lead_source_tag', $source_tag);
            update_post_meta($lead_id, '_lead_source_url', $source_url);
            update_post_meta($lead_id, '_lead_last_submission_at', $submitted_at);
            update_post_meta($lead_id, '_lead_consent', 'yes');

            $first_submitted_at = (string) get_post_meta($lead_id, '_lead_submitted_at', true);
            if ($first_submitted_at === '') {
                update_post_meta($lead_id, '_lead_submitted_at', $submitted_at);
            }

            $status_options = my_theme_lead_status_options();
            $old_status = (string) get_post_meta($lead_id, '_lead_status', true);
            if (!isset($status_options[$old_status])) {
                $old_status = 'new';
            }
            if (in_array($old_status, ['new', 'contacted'], true)) {
                update_post_meta($lead_id, '_lead_status', 'qualified');
                update_post_meta($lead_id, '_lead_last_status_change', $submitted_at);
                my_theme_lead_add_activity($lead_id, 'status_changed', 'Đồng bộ đơn hàng: ' . $status_options[$old_status] . ' -> ' . $status_options['qualified'], [
                    'from' => $old_status,
                    'to' => 'qualified',
                ]);
            }
        } else {
            $lead_id = wp_insert_post([
                'post_type' => 'customer_lead',
                'post_status' => 'publish',
                'post_title' => $title,
                'post_content' => $summary_text,
            ], true);
            if (is_wp_error($lead_id) || (int) $lead_id <= 0) {
                return 0;
            }

            update_post_meta($lead_id, '_lead_name', $name);
            update_post_meta($lead_id, '_lead_phone', $phone);
            update_post_meta($lead_id, '_lead_phone_normalized', $phone_normalized);
            update_post_meta($lead_id, '_lead_email', $email);
            update_post_meta($lead_id, '_lead_contact_channel', 'phone');
            update_post_meta($lead_id, '_lead_project_type', 'Khách từ đơn hàng WooCommerce #' . $order_number);
            update_post_meta($lead_id, '_lead_budget', $order_total_text);
            update_post_meta($lead_id, '_lead_message', $summary_text);
            update_post_meta($lead_id, '_lead_source_tag', $source_tag);
            update_post_meta($lead_id, '_lead_source_url', $source_url);
            update_post_meta($lead_id, '_lead_submitted_at', $submitted_at);
            update_post_meta($lead_id, '_lead_last_submission_at', $submitted_at);
            update_post_meta($lead_id, '_lead_status', 'qualified');
            update_post_meta($lead_id, '_lead_priority', 'normal');
            update_post_meta($lead_id, '_lead_last_status_change', $submitted_at);
            update_post_meta($lead_id, '_lead_consent', 'yes');
            my_theme_lead_add_activity($lead_id, 'lead_created_from_order', 'Tạo lead mới từ đơn WooCommerce #' . $order_number . '.', [
                'order_id' => (string) $order_id,
            ]);
        }

        $order_count = (int) get_post_meta($lead_id, '_lead_order_count', true);
        $order_count++;
        update_post_meta($lead_id, '_lead_order_count', $order_count);
        update_post_meta($lead_id, '_lead_last_order_id', $order_id);
        update_post_meta($lead_id, '_lead_last_order_number', $order_number);
        update_post_meta($lead_id, '_lead_last_order_total', $order_total_text);
        if ($order_total > 0) {
            $current_spent = (float) get_post_meta($lead_id, '_lead_total_order_value', true);
            $current_spent += $order_total;
            update_post_meta($lead_id, '_lead_total_order_value', number_format($current_spent, 2, '.', ''));
        }

        my_theme_lead_add_activity($lead_id, 'order_synced', 'Đồng bộ thông tin từ đơn WooCommerce #' . $order_number . '.', [
            'order_id' => (string) $order_id,
            'order_total' => (string) $order_total_text,
        ]);

        my_theme_lead_send_notification_email($lead_id, 'order', [
            'Đơn hàng: #' . $order_number,
            'Tổng tiền: ' . ($order_total_text !== '' ? $order_total_text : '-'),
        ]);
        my_theme_send_lead_webhook($lead_id, 'lead_order_synced', false);

        update_post_meta($order_id, '_my_theme_lead_id', (int) $lead_id);
        update_post_meta($order_id, '_my_theme_lead_synced', current_time('mysql'));

        return (int) $lead_id;
    }
}

add_action('woocommerce_checkout_order_processed', function ($order_id) {
    my_theme_sync_customer_lead_from_wc_order($order_id);
}, 30, 1);

if (!function_exists('my_theme_render_lead_capture_form')) {
    function my_theme_render_lead_capture_form($atts = [])
    {
        $atts = shortcode_atts([
            'title' => 'Để lại thông tin, chúng tôi gọi lại trong 15 phút',
            'subtitle' => 'Đội ngũ kỹ thuật sẽ tư vấn hệ sơn phù hợp và báo giá theo nhu cầu thực tế.',
            'button' => 'Gửi thông tin',
            'source' => 'lead-form',
        ], $atts, 'lead_capture_form');

        $title = sanitize_text_field((string) $atts['title']);
        $subtitle = sanitize_text_field((string) $atts['subtitle']);
        $button = sanitize_text_field((string) $atts['button']);
        $form_key = sanitize_key((string) $atts['source']);
        if ($form_key === '') {
            $form_key = 'lead-form';
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '/';
        $current_url = home_url($request_uri);
        $current_url = remove_query_arg(['lead_status', 'lead_form'], $current_url);
        $utm_source = isset($_GET['utm_source']) ? sanitize_text_field((string) wp_unslash($_GET['utm_source'])) : '';
        $utm_medium = isset($_GET['utm_medium']) ? sanitize_text_field((string) wp_unslash($_GET['utm_medium'])) : '';
        $utm_campaign = isset($_GET['utm_campaign']) ? sanitize_text_field((string) wp_unslash($_GET['utm_campaign'])) : '';
        $utm_term = isset($_GET['utm_term']) ? sanitize_text_field((string) wp_unslash($_GET['utm_term'])) : '';
        $utm_content = isset($_GET['utm_content']) ? sanitize_text_field((string) wp_unslash($_GET['utm_content'])) : '';
        $fbclid = isset($_GET['fbclid']) ? sanitize_text_field((string) wp_unslash($_GET['fbclid'])) : '';
        $gclid = isset($_GET['gclid']) ? sanitize_text_field((string) wp_unslash($_GET['gclid'])) : '';

        $status = isset($_GET['lead_status']) ? sanitize_key((string) wp_unslash($_GET['lead_status'])) : '';
        $status_form = isset($_GET['lead_form']) ? sanitize_key((string) wp_unslash($_GET['lead_form'])) : '';

        $notice_html = '';
        if ($status !== '' && $status_form === $form_key) {
            if ($status === 'success') {
                $notice_html = '<div class="lead-capture__notice lead-capture__notice--success">Cảm ơn bạn. Chúng tôi đã nhận thông tin và sẽ liên hệ sớm.</div>';
            } elseif ($status === 'invalid') {
                $notice_html = '<div class="lead-capture__notice lead-capture__notice--error">Vui lòng nhập đầy đủ họ tên, số điện thoại và đồng ý để chúng tôi liên hệ.</div>';
            } else {
                $notice_html = '<div class="lead-capture__notice lead-capture__notice--error">Có lỗi xảy ra khi gửi thông tin. Vui lòng thử lại.</div>';
            }
        }

        ob_start();
        ?>
        <section class="page-section lead-capture" id="<?php echo esc_attr('lead-capture-' . $form_key); ?>">
          <div class="section-heading">
            <div>
              <h2 class="section-title"><?php echo esc_html($title); ?></h2>
              <p class="section-sub"><?php echo esc_html($subtitle); ?></p>
            </div>
          </div>

          <?php echo $notice_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

          <form class="lead-capture__form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="my_theme_submit_customer_lead" />
            <input type="hidden" name="lead_form" value="<?php echo esc_attr($form_key); ?>" />
            <input type="hidden" name="redirect_to" value="<?php echo esc_url($current_url); ?>" />
            <input type="hidden" name="lead_source_tag" value="<?php echo esc_attr($form_key); ?>" />
            <input type="hidden" name="lead_source_url" value="<?php echo esc_url($current_url); ?>" />
            <input type="hidden" name="lead_utm_source" value="<?php echo esc_attr($utm_source); ?>" />
            <input type="hidden" name="lead_utm_medium" value="<?php echo esc_attr($utm_medium); ?>" />
            <input type="hidden" name="lead_utm_campaign" value="<?php echo esc_attr($utm_campaign); ?>" />
            <input type="hidden" name="lead_utm_term" value="<?php echo esc_attr($utm_term); ?>" />
            <input type="hidden" name="lead_utm_content" value="<?php echo esc_attr($utm_content); ?>" />
            <input type="hidden" name="lead_fbclid" value="<?php echo esc_attr($fbclid); ?>" />
            <input type="hidden" name="lead_gclid" value="<?php echo esc_attr($gclid); ?>" />
            <?php wp_nonce_field('my_theme_submit_customer_lead', 'my_theme_lead_nonce'); ?>

            <div class="lead-capture__hp" aria-hidden="true">
              <label>Website
                <input type="text" name="lead_website" tabindex="-1" autocomplete="off" />
              </label>
            </div>

            <div class="lead-capture__grid">
              <div class="lead-capture__field">
                <label for="<?php echo esc_attr('lead-name-' . $form_key); ?>">Họ và tên *</label>
                <input id="<?php echo esc_attr('lead-name-' . $form_key); ?>" type="text" name="lead_name" required />
              </div>

              <div class="lead-capture__field">
                <label for="<?php echo esc_attr('lead-phone-' . $form_key); ?>">Số điện thoại *</label>
                <input id="<?php echo esc_attr('lead-phone-' . $form_key); ?>" type="tel" name="lead_phone" inputmode="tel" required />
              </div>

              <div class="lead-capture__field">
                <label for="<?php echo esc_attr('lead-email-' . $form_key); ?>">Email</label>
                <input id="<?php echo esc_attr('lead-email-' . $form_key); ?>" type="email" name="lead_email" />
              </div>

              <div class="lead-capture__field">
                <label for="<?php echo esc_attr('lead-project-' . $form_key); ?>">Nhu cầu</label>
                <select id="<?php echo esc_attr('lead-project-' . $form_key); ?>" name="lead_project_type">
                  <option value="">Chọn nhu cầu</option>
                  <option value="Bao gia son nha dan dung">Báo giá sơn nhà dân dụng</option>
                  <option value="Bao gia cong trinh">Báo giá công trình</option>
                  <option value="Tu van chong tham">Tư vấn chống thấm</option>
                  <option value="Tu van vat tu theo m2">Tư vấn vật tư theo m²</option>
                </select>
              </div>

              <div class="lead-capture__field">
                <label for="<?php echo esc_attr('lead-budget-' . $form_key); ?>">Ngân sách dự kiến</label>
                <input id="<?php echo esc_attr('lead-budget-' . $form_key); ?>" type="text" name="lead_budget" placeholder="Ví dụ: 5-10 triệu" />
              </div>

              <div class="lead-capture__field">
                <label for="<?php echo esc_attr('lead-channel-' . $form_key); ?>">Kênh muốn liên hệ</label>
                <select id="<?php echo esc_attr('lead-channel-' . $form_key); ?>" name="lead_contact_channel">
                  <option value="phone">Điện thoại</option>
                  <option value="zalo">Zalo</option>
                  <option value="email">Email</option>
                </select>
              </div>

              <div class="lead-capture__field lead-capture__field--full">
                <label for="<?php echo esc_attr('lead-message-' . $form_key); ?>">Ghi chú thêm</label>
                <textarea id="<?php echo esc_attr('lead-message-' . $form_key); ?>" name="lead_message" rows="4" placeholder="Ví dụ: Diện tích, loại bề mặt, thời gian cần giao..."></textarea>
              </div>
            </div>

            <label class="lead-capture__consent">
              <input type="checkbox" name="lead_consent" value="1" required />
              <span>Tôi đồng ý để Đại lý Sơn Phát Tấn liên hệ tư vấn và báo giá.</span>
            </label>

            <div class="lead-capture__actions">
              <button class="btn btn-primary" type="submit"><?php echo esc_html($button); ?></button>
            </div>
          </form>
        </section>
        <?php

        return (string) ob_get_clean();
    }
}
add_shortcode('lead_capture_form', 'my_theme_render_lead_capture_form');

if (is_admin()) {
    $my_theme_lead_admin_file = get_theme_file_path('inc/customer-leads-admin.php');
    if (is_string($my_theme_lead_admin_file) && file_exists($my_theme_lead_admin_file)) {
        require_once $my_theme_lead_admin_file;
    }
}

