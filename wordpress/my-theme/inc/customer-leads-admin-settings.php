<?php
/**
 * Admin lead settings pages (notification + webhook).
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('my_theme_render_customer_lead_notification_settings_page')) {
    function my_theme_render_customer_lead_notification_settings_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied.');
        }

        $errors = [];
        $notices = [];

        if (
            isset($_POST['my_theme_lead_notify_settings_nonce']) &&
            wp_verify_nonce((string) wp_unslash($_POST['my_theme_lead_notify_settings_nonce']), 'my_theme_save_lead_notify_settings')
        ) {
            $enabled = !empty($_POST['lead_notify_enabled']) ? '1' : '0';
            $emails_raw = isset($_POST['lead_notify_emails']) ? trim((string) wp_unslash($_POST['lead_notify_emails'])) : '';
            $subject_prefix = isset($_POST['lead_notify_subject_prefix']) ? sanitize_text_field((string) wp_unslash($_POST['lead_notify_subject_prefix'])) : '[Lead mới]';
            $reminder_enabled = !empty($_POST['lead_daily_reminder_enabled']) ? '1' : '0';
            $reminder_hour = isset($_POST['lead_daily_reminder_hour']) ? (int) wp_unslash($_POST['lead_daily_reminder_hour']) : 8;
            $reminder_limit = isset($_POST['lead_daily_reminder_limit']) ? absint((string) wp_unslash($_POST['lead_daily_reminder_limit'])) : 25;
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
                    $notices[] = 'Không có email hợp lệ trong danh sách, hệ thống tạm dùng admin email.';
                } else {
                    $errors[] = 'Danh sách email nhận lead không hợp lệ.';
                }
            }

            if (empty($errors)) {
                update_option('my_theme_lead_notify_enabled', $enabled);
                update_option('my_theme_lead_notify_emails', implode("\n", $emails));
                update_option('my_theme_lead_notify_subject_prefix', $subject_prefix);
                update_option('my_theme_lead_daily_reminder_enabled', $reminder_enabled);
                update_option('my_theme_lead_daily_reminder_hour', (string) $reminder_hour);
                update_option('my_theme_lead_daily_reminder_limit', (string) $reminder_limit);
                my_theme_lead_schedule_daily_reminder_event(true);
                $notices[] = 'Đã lưu cấu hình thông báo lead.';
            }
        }

        if (
            isset($_POST['my_theme_run_lead_daily_reminder']) &&
            isset($_POST['my_theme_lead_notify_settings_nonce']) &&
            wp_verify_nonce((string) wp_unslash($_POST['my_theme_lead_notify_settings_nonce']), 'my_theme_save_lead_notify_settings')
        ) {
            $result = my_theme_lead_run_daily_overdue_reminder(true);
            if (!empty($result['sent'])) {
                $notices[] = 'Đã gửi email nhắc quá hạn cho ' . (int) $result['count'] . ' lead (tổng quá hạn: ' . (int) $result['total'] . ').';
            } elseif (isset($result['reason']) && $result['reason'] === 'Không có lead quá hạn.') {
                $notices[] = 'Hiện chưa có lead quá hạn để gửi nhắc.';
            } else {
                $errors[] = 'Không gửi được email nhắc quá hạn: ' . (isset($result['reason']) ? sanitize_text_field((string) $result['reason']) : 'Lỗi không xác định.');
            }
        }

        if (
            isset($_POST['my_theme_run_action_scheduler_queue']) &&
            isset($_POST['my_theme_lead_notify_settings_nonce']) &&
            wp_verify_nonce((string) wp_unslash($_POST['my_theme_lead_notify_settings_nonce']), 'my_theme_save_lead_notify_settings')
        ) {
            $warning_threshold = my_theme_action_scheduler_overdue_warning_threshold();
            $result = my_theme_action_scheduler_run_queue_batches(12);
            if (!empty($result['supported'])) {
                $processed = isset($result['processed']) ? (int) $result['processed'] : 0;
                $before = isset($result['before']) ? (int) $result['before'] : 0;
                $after = isset($result['after']) ? (int) $result['after'] : 0;
                if ($processed > 0) {
                    $notices[] = 'Đã chạy queue Action Scheduler: xử lý ' . $processed . ' tác vụ (từ ' . $before . ' còn ' . $after . ').';
                } elseif ($after <= 0) {
                    $notices[] = 'Queue Action Scheduler hiện không còn tác vụ quá hạn.';
                } elseif ($after <= $warning_threshold) {
                    $notices[] = 'Queue Action Scheduler còn ' . $after . ' tác vụ quá hạn nhẹ, hệ thống sẽ tự xử lý trong lần cron kế tiếp.';
                } else {
                    $repair = my_theme_action_scheduler_repair_queue(30);
                    if (!empty($repair['supported'])) {
                        $rerun = my_theme_action_scheduler_run_queue_batches(16);
                        $rerun_processed = isset($rerun['processed']) ? (int) $rerun['processed'] : 0;
                        $rerun_after = isset($rerun['after']) ? (int) $rerun['after'] : $after;
                        if ($rerun_processed > 0) {
                            $notices[] = 'Queue ban đầu chưa giảm, đã repair claim/lock và xử lý thêm ' . $rerun_processed . ' tác vụ (còn ' . max(0, $rerun_after) . ').';
                        } elseif ($rerun_after <= $warning_threshold) {
                            $notices[] = 'Đã repair queue, còn ' . max(0, $rerun_after) . ' tác vụ quá hạn nhẹ và sẽ tự chạy theo cron.';
                        } else {
                            $released = isset($repair['released_pending_claims']) ? (int) $repair['released_pending_claims'] : 0;
                            $requeued = isset($repair['requeued_stuck']) ? (int) $repair['requeued_stuck'] : 0;
                            $errors[] = 'Đã thử chạy queue và repair claim/lock nhưng tác vụ quá hạn chưa giảm (còn ' . max(0, $rerun_after) . '). Pending claim reset: ' . $released . ', in-progress requeue: ' . $requeued . '.';
                        }
                    } else {
                        $errors[] = 'Đã thử chạy queue nhưng chưa giảm tác vụ quá hạn (còn ' . $after . '). Cần kiểm tra plugin/hạ tầng gửi nền.';
                    }
                }
            } else {
                $errors[] = 'Không thể chạy queue Action Scheduler: ' . (isset($result['message']) ? sanitize_text_field((string) $result['message']) : 'Không xác định.');
            }
        }

        if (
            isset($_POST['my_theme_repair_action_scheduler_queue']) &&
            isset($_POST['my_theme_lead_notify_settings_nonce']) &&
            wp_verify_nonce((string) wp_unslash($_POST['my_theme_lead_notify_settings_nonce']), 'my_theme_save_lead_notify_settings')
        ) {
            $warning_threshold = my_theme_action_scheduler_overdue_warning_threshold();
            $repair = my_theme_action_scheduler_repair_queue(30);
            if (empty($repair['supported'])) {
                $errors[] = 'Không thể sửa queue Action Scheduler: ' . (isset($repair['message']) ? sanitize_text_field((string) $repair['message']) : 'Không xác định.');
            } else {
                $run = my_theme_action_scheduler_run_queue_batches(16);
                $released = isset($repair['released_pending_claims']) ? (int) $repair['released_pending_claims'] : 0;
                $requeued = isset($repair['requeued_stuck']) ? (int) $repair['requeued_stuck'] : 0;
                $processed = isset($run['processed']) ? (int) $run['processed'] : 0;
                $after = isset($run['after']) ? (int) $run['after'] : my_theme_action_scheduler_get_pending_overdue_count();
                if ($processed > 0) {
                    $notices[] = 'Đã sửa queue và xử lý được ' . $processed . ' tác vụ quá hạn (còn ' . max(0, $after) . ').';
                } elseif ($after <= $warning_threshold) {
                    $notices[] = 'Đã repair queue. Còn ' . max(0, $after) . ' tác vụ quá hạn nhẹ và sẽ tự chạy theo cron.';
                } else {
                    $errors[] = 'Đã repair claim/lock (pending claim: ' . $released . ', requeue in-progress: ' . $requeued . ') nhưng tác vụ quá hạn chưa giảm.';
                }
            }
        }

        $settings = my_theme_lead_get_notification_settings();
        $next_daily_timestamp = wp_next_scheduled('my_theme_send_daily_overdue_lead_reminder');
        $next_daily_label = $next_daily_timestamp ? wp_date('Y-m-d H:i:s', (int) $next_daily_timestamp) : '';
        $last_sent_label = trim((string) $settings['reminder_last_sent_at']);
        $action_scheduler_overdue = my_theme_action_scheduler_get_pending_overdue_count();
        $action_scheduler_top_hooks = my_theme_action_scheduler_get_overdue_hook_counts(8);
        $action_scheduler_grace_seconds = my_theme_action_scheduler_overdue_grace_seconds();
        $action_scheduler_warning_threshold = my_theme_action_scheduler_overdue_warning_threshold();
        $show_action_scheduler_warning = $action_scheduler_overdue > $action_scheduler_warning_threshold;
        $action_scheduler_url = function_exists('WC') ? admin_url('admin.php?page=wc-status&tab=action-scheduler') : admin_url('tools.php?page=action-scheduler');

        echo '<div class="wrap">';
        echo '<h1>Cấu hình thông báo lead</h1>';
        echo '<p class="description">Thiết lập người nhận email khi khách để lại thông tin hoặc phát sinh lead từ đơn hàng.</p>';

        foreach ($errors as $error) {
            echo '<div class="notice notice-error"><p>' . esc_html((string) $error) . '</p></div>';
        }
        foreach ($notices as $notice) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html((string) $notice) . '</p></div>';
        }
        if ($next_daily_label !== '') {
            echo '<p class="my-theme-lead-notify-muted">Lần nhắc quá hạn kế tiếp: ' . esc_html($next_daily_label) . '</p>';
        }
        if ($last_sent_label !== '') {
            echo '<p class="my-theme-lead-notify-muted">Lần gửi nhắc gần nhất: ' . esc_html($last_sent_label) . '</p>';
        }
        if ($action_scheduler_overdue >= 0) {
            if ($action_scheduler_overdue > 0) {
                if ($show_action_scheduler_warning) {
                    echo '<p class="my-theme-lead-notify-muted"><strong>Action Scheduler quá hạn:</strong> ' . esc_html((string) $action_scheduler_overdue) . ' tác vụ. <a href="' . esc_url($action_scheduler_url) . '">Mở trang queue</a></p>';
                } else {
                    echo '<p class="my-theme-lead-notify-muted"><strong>Action Scheduler:</strong> Còn ' . esc_html((string) $action_scheduler_overdue) . ' tác vụ quá hạn nhẹ (đã trừ ngưỡng ' . esc_html((string) floor($action_scheduler_grace_seconds / 60)) . ' phút), hệ thống sẽ tự chạy.</p>';
                }
            } else {
                echo '<p class="my-theme-lead-notify-muted"><strong>Action Scheduler:</strong> Không có tác vụ quá hạn.</p>';
            }
        }
        if ($show_action_scheduler_warning && !empty($action_scheduler_top_hooks)) {
            echo '<div class="notice notice-warning"><p><strong>Top hook quá hạn:</strong></p><ol style="margin:0 0 0 18px;">';
            foreach ($action_scheduler_top_hooks as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $hook_name = isset($item['hook']) ? sanitize_text_field((string) $item['hook']) : '';
                $hook_count = isset($item['count']) ? (int) $item['count'] : 0;
                if ($hook_name === '' || $hook_count <= 0) {
                    continue;
                }
                echo '<li><code>' . esc_html($hook_name) . '</code>: ' . esc_html((string) $hook_count) . '</li>';
            }
            echo '</ol></div>';
        }

        echo '<form method="post" class="my-theme-lead-notify-form">';
        wp_nonce_field('my_theme_save_lead_notify_settings', 'my_theme_lead_notify_settings_nonce');

        echo '<div class="field"><label><input type="checkbox" name="lead_notify_enabled" value="1" ' . checked($settings['enabled'], true, false) . ' /> Bật gửi email thông báo lead</label></div>';
        echo '<div class="field"><label for="lead-notify-emails">Email nhận lead</label><textarea id="lead-notify-emails" name="lead_notify_emails" rows="5" placeholder="sale1@domain.com&#10;sale2@domain.com">' . esc_textarea($settings['emails_raw']) . '</textarea><p class="my-theme-lead-notify-muted">Mỗi dòng 1 email, có thể nhập nhiều email để nhiều nhân viên cùng nhận lead.</p></div>';
        echo '<div class="field"><label for="lead-notify-subject-prefix">Tiền tố tiêu đề email</label><input id="lead-notify-subject-prefix" type="text" name="lead_notify_subject_prefix" value="' . esc_attr($settings['subject_prefix']) . '" placeholder="[Lead mới]" /><p class="my-theme-lead-notify-muted">Ví dụ: [Lephat Paint] hoặc [Lead website].</p></div>';
        echo '<div class="field"><label><input type="checkbox" name="lead_daily_reminder_enabled" value="1" ' . checked($settings['reminder_enabled'], true, false) . ' /> Bật email nhắc lead quá hạn mỗi ngày</label><p class="my-theme-lead-notify-muted">Hệ thống tự động gửi danh sách lead quá hạn chăm sóc cho đội sale theo lịch.</p></div>';
        echo '<div class="field"><label for="lead-daily-reminder-hour">Giờ gửi nhắc hằng ngày (0-23)</label><input id="lead-daily-reminder-hour" type="number" min="0" max="23" name="lead_daily_reminder_hour" value="' . esc_attr((string) $settings['reminder_hour']) . '" /></div>';
        echo '<div class="field"><label for="lead-daily-reminder-limit">Số lead tối đa mỗi email nhắc</label><input id="lead-daily-reminder-limit" type="number" min="5" max="100" name="lead_daily_reminder_limit" value="' . esc_attr((string) $settings['reminder_limit']) . '" /><p class="my-theme-lead-notify-muted">Nếu vượt quá, email sẽ kèm thông báo còn bao nhiêu lead chưa liệt kê.</p></div>';
        echo '<div class="my-theme-lead-notify-actions"><button class="button button-primary" type="submit" name="my_theme_save_lead_notify_settings" value="1">Lưu cấu hình</button><button class="button" type="submit" name="my_theme_run_lead_daily_reminder" value="1">Gửi nhắc quá hạn ngay</button><button class="button" type="submit" name="my_theme_run_action_scheduler_queue" value="1">Chạy queue Action Scheduler ngay</button><button class="button" type="submit" name="my_theme_repair_action_scheduler_queue" value="1">Sửa queue bị kẹt + chạy lại</button></div>';
        echo '</form>';
        echo '</div>';
    }
}

add_action('admin_menu', function () {
    if (!current_user_can('manage_options')) {
        return;
    }

    add_submenu_page(
        'edit.php?post_type=customer_lead',
        'Thông báo lead',
        'Thông báo',
        'manage_options',
        'customer-lead-notify',
        'my_theme_render_customer_lead_notification_settings_page'
    );
}, 29);

if (!function_exists('my_theme_render_customer_lead_webhook_settings_page')) {
    function my_theme_render_customer_lead_webhook_settings_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied.');
        }

        $errors = [];
        $notices = [];

        if (
            isset($_POST['my_theme_lead_webhook_settings_nonce']) &&
            wp_verify_nonce((string) wp_unslash($_POST['my_theme_lead_webhook_settings_nonce']), 'my_theme_save_lead_webhook_settings')
        ) {
            $enabled = !empty($_POST['lead_webhook_enabled']) ? '1' : '0';
            $url_raw = isset($_POST['lead_webhook_url']) ? trim((string) wp_unslash($_POST['lead_webhook_url'])) : '';
            $url = $url_raw !== '' ? esc_url_raw($url_raw) : '';
            $secret = isset($_POST['lead_webhook_secret']) ? trim((string) wp_unslash($_POST['lead_webhook_secret'])) : '';
            if (strlen($secret) > 200) {
                $secret = substr($secret, 0, 200);
            }
            $timeout = isset($_POST['lead_webhook_timeout']) ? absint((string) wp_unslash($_POST['lead_webhook_timeout'])) : 8;
            if ($timeout < 3 || $timeout > 30) {
                $timeout = 8;
            }
            $retry_enabled = !empty($_POST['lead_webhook_retry_enabled']) ? '1' : '0';
            $retry_max = isset($_POST['lead_webhook_retry_max']) ? absint((string) wp_unslash($_POST['lead_webhook_retry_max'])) : 5;
            if ($retry_max < 1 || $retry_max > 20) {
                $retry_max = 5;
            }

            if ($url_raw !== '' && !wp_http_validate_url($url)) {
                $errors[] = 'Webhook URL không hợp lệ.';
            }

            if (empty($errors)) {
                update_option('my_theme_lead_webhook_enabled', $enabled);
                update_option('my_theme_lead_webhook_url', $url);
                update_option('my_theme_lead_webhook_secret', $secret);
                update_option('my_theme_lead_webhook_timeout', (string) $timeout);
                update_option('my_theme_lead_webhook_retry_enabled', $retry_enabled);
                update_option('my_theme_lead_webhook_retry_max', (string) $retry_max);
                $notices[] = 'Đã lưu cấu hình webhook.';
            }
        }

        if (
            isset($_POST['my_theme_test_lead_webhook']) &&
            isset($_POST['my_theme_lead_webhook_settings_nonce']) &&
            wp_verify_nonce((string) wp_unslash($_POST['my_theme_lead_webhook_settings_nonce']), 'my_theme_save_lead_webhook_settings')
        ) {
            $latest_ids = get_posts([
                'post_type' => 'customer_lead',
                'post_status' => ['publish', 'private', 'draft'],
                'posts_per_page' => 1,
                'orderby' => 'date',
                'order' => 'DESC',
                'fields' => 'ids',
                'no_found_rows' => true,
            ]);

            if (empty($latest_ids)) {
                $errors[] = 'Chưa có lead để gửi thử webhook.';
            } else {
                $test_result = my_theme_send_lead_webhook((int) $latest_ids[0], 'lead_test', true);
                if (is_wp_error($test_result)) {
                    $errors[] = 'Gửi test thất bại: ' . $test_result->get_error_message();
                } else {
                    $notices[] = 'Gửi test webhook thành công.';
                }
            }
        }

        if (
            isset($_POST['my_theme_run_lead_webhook_retry']) &&
            isset($_POST['my_theme_lead_webhook_settings_nonce']) &&
            wp_verify_nonce((string) wp_unslash($_POST['my_theme_lead_webhook_settings_nonce']), 'my_theme_save_lead_webhook_settings')
        ) {
            $processed = my_theme_retry_failed_lead_webhooks();
            $notices[] = 'Đã chạy retry webhook cho ' . (int) $processed . ' lead lỗi.';
        }

        $settings = my_theme_lead_get_webhook_settings();
        $next_retry_timestamp = wp_next_scheduled('my_theme_retry_failed_lead_webhooks');
        $next_retry_label = $next_retry_timestamp ? wp_date('Y-m-d H:i:s', (int) $next_retry_timestamp) : '';
        $sample_payload = [
            'event' => 'lead_created',
            'lead_id' => 123,
            'site' => [
                'name' => get_bloginfo('name'),
                'url' => home_url('/'),
            ],
            'lead' => [
                'name' => 'Nguyen Van A',
                'phone' => '0909123456',
                'email' => 'a@example.com',
                'status' => 'new',
                'origin' => 'Form website',
                'source_tag' => 'trang-lien-he',
                'utm_source' => 'facebook',
                'utm_campaign' => 'paint-campaign',
            ],
        ];

        echo '<div class="wrap">';
        echo '<h1>Cấu hình webhook lead</h1>';
        echo '<p class="description">Tự động gửi lead mới sang CRM/Google Sheets/Zapier qua HTTP POST JSON.</p>';

        foreach ($errors as $error) {
            echo '<div class="notice notice-error"><p>' . esc_html((string) $error) . '</p></div>';
        }
        foreach ($notices as $notice) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html((string) $notice) . '</p></div>';
        }
        if ($next_retry_label !== '') {
            echo '<p class="my-theme-webhook-muted">Lần retry tự động kế tiếp: ' . esc_html($next_retry_label) . '</p>';
        }

        echo '<form method="post" class="my-theme-webhook-form">';
        wp_nonce_field('my_theme_save_lead_webhook_settings', 'my_theme_lead_webhook_settings_nonce');

        echo '<div class="field"><label><input type="checkbox" name="lead_webhook_enabled" value="1" ' . checked($settings['enabled'], true, false) . ' /> Bật gửi webhook tự động khi có lead mới</label></div>';
        echo '<div class="field"><label for="lead-webhook-url">Webhook URL</label><input id="lead-webhook-url" type="url" name="lead_webhook_url" value="' . esc_attr($settings['url']) . '" placeholder="https://your-crm.example.com/webhooks/leads" /></div>';
        echo '<div class="field"><label for="lead-webhook-secret">Webhook Secret (tùy chọn)</label><input id="lead-webhook-secret" type="text" name="lead_webhook_secret" value="' . esc_attr($settings['secret']) . '" placeholder="Dùng để ký header X-Lead-Signature (HMAC SHA256)" /></div>';
        echo '<div class="field"><label for="lead-webhook-timeout">Timeout (giây)</label><input id="lead-webhook-timeout" type="number" min="3" max="30" name="lead_webhook_timeout" value="' . esc_attr((string) $settings['timeout']) . '" /></div>';
        echo '<div class="field"><label><input type="checkbox" name="lead_webhook_retry_enabled" value="1" ' . checked($settings['retry_enabled'], true, false) . ' /> Tự động retry webhook lỗi (WP-Cron mỗi 15 phút)</label></div>';
        echo '<div class="field"><label for="lead-webhook-retry-max">Số lần retry tối đa cho mỗi lead</label><input id="lead-webhook-retry-max" type="number" min="1" max="20" name="lead_webhook_retry_max" value="' . esc_attr((string) $settings['retry_max']) . '" /></div>';

        echo '<p class="my-theme-webhook-muted">Header gửi kèm: <code>X-Lead-Event</code>, <code>X-Lead-Id</code>, <code>X-Lead-Signature</code> (nếu có secret).</p>';
        echo '<p class="my-theme-webhook-muted">Payload mẫu:</p>';
        echo '<pre>' . esc_html((string) wp_json_encode($sample_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>';

        echo '<div class="my-theme-webhook-actions">';
        echo '<button class="button button-primary" type="submit" name="my_theme_save_lead_webhook_settings" value="1">Lưu cấu hình</button>';
        echo '<button class="button" type="submit" name="my_theme_test_lead_webhook" value="1">Gửi test webhook (lead mới nhất)</button>';
        echo '<button class="button" type="submit" name="my_theme_run_lead_webhook_retry" value="1">Chạy retry lỗi ngay</button>';
        echo '</div>';
        echo '</form>';
        echo '</div>';
    }
}

add_action('admin_menu', function () {
    if (!current_user_can('manage_options')) {
        return;
    }

    add_submenu_page(
        'edit.php?post_type=customer_lead',
        'Webhook lead',
        'Webhook',
        'manage_options',
        'customer-lead-webhook',
        'my_theme_render_customer_lead_webhook_settings_page'
    );
}, 31);

