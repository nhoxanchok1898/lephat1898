<?php
/**
 * Admin lead management: edit screen, columns, filters, exports.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('my_theme_lead_add_meta_box')) {
    function my_theme_lead_add_meta_box()
    {
        add_meta_box(
            'my-theme-customer-lead-detail',
            'Thông tin khách hàng',
            'my_theme_lead_render_meta_box',
            'customer_lead',
            'normal',
            'high'
        );
    }
}
add_action('add_meta_boxes_customer_lead', 'my_theme_lead_add_meta_box');

if (!function_exists('my_theme_lead_render_meta_box')) {
    function my_theme_lead_render_meta_box($post)
    {
        $name = (string) get_post_meta($post->ID, '_lead_name', true);
        $phone = (string) get_post_meta($post->ID, '_lead_phone', true);
        $email = (string) get_post_meta($post->ID, '_lead_email', true);
        $channel = (string) get_post_meta($post->ID, '_lead_contact_channel', true);
        $project = (string) get_post_meta($post->ID, '_lead_project_type', true);
        $budget = (string) get_post_meta($post->ID, '_lead_budget', true);
        $message = (string) get_post_meta($post->ID, '_lead_message', true);
        $source_tag = (string) get_post_meta($post->ID, '_lead_source_tag', true);
        $source_url = (string) get_post_meta($post->ID, '_lead_source_url', true);
        $lead_origin_label = my_theme_lead_origin_label($source_tag);
        $utm_source = (string) get_post_meta($post->ID, '_lead_utm_source', true);
        $utm_medium = (string) get_post_meta($post->ID, '_lead_utm_medium', true);
        $utm_campaign = (string) get_post_meta($post->ID, '_lead_utm_campaign', true);
        $utm_term = (string) get_post_meta($post->ID, '_lead_utm_term', true);
        $utm_content = (string) get_post_meta($post->ID, '_lead_utm_content', true);
        $fbclid = (string) get_post_meta($post->ID, '_lead_fbclid', true);
        $gclid = (string) get_post_meta($post->ID, '_lead_gclid', true);
        $referrer = (string) get_post_meta($post->ID, '_lead_referrer', true);
        $submitted_at = (string) get_post_meta($post->ID, '_lead_submitted_at', true);
        $last_status_change = (string) get_post_meta($post->ID, '_lead_last_status_change', true);
        $ip = (string) get_post_meta($post->ID, '_lead_ip', true);
        $user_agent = (string) get_post_meta($post->ID, '_lead_user_agent', true);
        $webhook_status = (string) get_post_meta($post->ID, '_lead_webhook_last_status', true);
        $webhook_sent_at = (string) get_post_meta($post->ID, '_lead_webhook_last_sent_at', true);
        $webhook_code = (string) get_post_meta($post->ID, '_lead_webhook_last_code', true);
        $webhook_error = (string) get_post_meta($post->ID, '_lead_webhook_last_error', true);
        $webhook_delivery_count = (string) get_post_meta($post->ID, '_lead_webhook_delivery_count', true);
        $webhook_attempt_count = (string) get_post_meta($post->ID, '_lead_webhook_attempt_count', true);
        $webhook_consecutive_failures = (string) get_post_meta($post->ID, '_lead_webhook_consecutive_failures', true);
        $webhook_last_event = (string) get_post_meta($post->ID, '_lead_webhook_last_event', true);
        $status = (string) get_post_meta($post->ID, '_lead_status', true);
        $priority = (string) get_post_meta($post->ID, '_lead_priority', true);
        $next_follow_up = (string) get_post_meta($post->ID, '_lead_next_follow_up', true);
        $next_follow_up_ts = (int) get_post_meta($post->ID, '_lead_next_follow_up_ts', true);
        $assignee = (string) get_post_meta($post->ID, '_lead_assignee', true);
        $note = (string) get_post_meta($post->ID, '_lead_note', true);
        $order_count = (int) get_post_meta($post->ID, '_lead_order_count', true);
        $last_order_id = (int) get_post_meta($post->ID, '_lead_last_order_id', true);
        $last_order_number = (string) get_post_meta($post->ID, '_lead_last_order_number', true);
        $last_order_total = (string) get_post_meta($post->ID, '_lead_last_order_total', true);
        $total_order_value = (string) get_post_meta($post->ID, '_lead_total_order_value', true);
        $duplicate_submit_count = (int) get_post_meta($post->ID, '_lead_duplicate_submit_count', true);
        $activity_log = my_theme_lead_get_activity_log($post->ID);
        $webhook_settings = my_theme_lead_get_webhook_settings();
        $resend_webhook_url = '';
        $last_order_edit_url = '';
        if ($last_order_id > 0) {
            $last_order_edit_url = admin_url('post.php?post=' . $last_order_id . '&action=edit');
        }
        if (current_user_can('edit_post', $post->ID) && $webhook_settings['url'] !== '') {
            $resend_webhook_url = add_query_arg([
                'action' => 'my_theme_lead_resend_webhook',
                'lead_id' => (int) $post->ID,
            ], admin_url('admin-post.php'));
            $resend_webhook_url = wp_nonce_url($resend_webhook_url, 'my_theme_lead_resend_webhook_' . (int) $post->ID);
        }
        $status_options = my_theme_lead_status_options();
        if (!isset($status_options[$status])) {
            $status = 'new';
        }
        $priority_options = my_theme_lead_priority_options();
        if (!isset($priority_options[$priority])) {
            $priority = 'normal';
        }
        $next_follow_up_input = my_theme_lead_followup_input_value($next_follow_up);
        $followup_status_label = my_theme_lead_followup_status_label($next_follow_up_ts);

        wp_nonce_field('my_theme_save_customer_lead_meta', 'my_theme_customer_lead_meta_nonce');
        ?>
        <dl class="my-theme-lead-grid">
          <dt>Họ tên</dt><dd><?php echo esc_html($name); ?></dd>
          <dt>Điện thoại</dt>
          <dd>
            <?php if ($phone !== '') : ?>
              <a href="<?php echo esc_url('tel:' . $phone); ?>"><?php echo esc_html($phone); ?></a>
            <?php else : ?>
              -
            <?php endif; ?>
          </dd>
          <dt>Email</dt><dd><?php echo ($email !== '') ? esc_html($email) : '-'; ?></dd>
          <dt>Kênh ưu tiên</dt><dd><?php echo esc_html(my_theme_lead_channel_label($channel)); ?></dd>
          <dt>Nhu cầu</dt><dd><?php echo ($project !== '') ? esc_html($project) : '-'; ?></dd>
          <dt>Ngân sách</dt><dd><?php echo ($budget !== '') ? esc_html($budget) : '-'; ?></dd>
          <dt>Mức ưu tiên</dt><dd><?php echo esc_html(my_theme_lead_priority_label($priority)); ?></dd>
          <dt>Lịch chăm sóc</dt><dd><?php echo ($next_follow_up !== '') ? esc_html($next_follow_up . ' (' . $followup_status_label . ')') : 'Chưa đặt lịch'; ?></dd>
          <dt>Nguồn lead</dt><dd><?php echo esc_html($lead_origin_label); ?></dd>
          <dt>Số đơn WooCommerce</dt><dd><?php echo esc_html((string) max(0, $order_count)); ?></dd>
          <dt>Đơn gần nhất</dt>
          <dd>
            <?php if ($last_order_id > 0 && $last_order_edit_url !== '') : ?>
              <a href="<?php echo esc_url($last_order_edit_url); ?>">#<?php echo esc_html($last_order_number !== '' ? $last_order_number : (string) $last_order_id); ?></a>
              <?php if ($last_order_total !== '') : ?>
                <span> (<?php echo esc_html($last_order_total); ?>)</span>
              <?php endif; ?>
            <?php else : ?>
              -
            <?php endif; ?>
          </dd>
          <dt>Tổng giá trị đơn</dt><dd><?php echo ($total_order_value !== '') ? esc_html($total_order_value) : '-'; ?></dd>
          <dt>Số lần gửi lại form</dt><dd><?php echo esc_html((string) max(0, $duplicate_submit_count)); ?></dd>
          <dt>Ghi chú khách</dt><dd><?php echo ($message !== '') ? nl2br(esc_html($message)) : '-'; ?></dd>
          <dt>Nguồn form</dt><dd><?php echo ($source_tag !== '') ? esc_html($source_tag) : '-'; ?></dd>
          <dt>URL gửi form</dt>
          <dd>
            <?php if ($source_url !== '') : ?>
              <a href="<?php echo esc_url($source_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($source_url); ?></a>
            <?php else : ?>
              -
            <?php endif; ?>
          </dd>
          <dt>UTM source / medium</dt><dd><?php echo ($utm_source !== '' || $utm_medium !== '') ? esc_html(trim($utm_source . ' / ' . $utm_medium, ' /')) : '-'; ?></dd>
          <dt>UTM campaign</dt><dd><?php echo ($utm_campaign !== '') ? esc_html($utm_campaign) : '-'; ?></dd>
          <dt>UTM term / content</dt><dd><?php echo ($utm_term !== '' || $utm_content !== '') ? esc_html(trim($utm_term . ' / ' . $utm_content, ' /')) : '-'; ?></dd>
          <dt>FBCLID / GCLID</dt><dd><?php echo ($fbclid !== '' || $gclid !== '') ? esc_html(trim($fbclid . ' / ' . $gclid, ' /')) : '-'; ?></dd>
          <dt>Referrer</dt>
          <dd>
            <?php if ($referrer !== '') : ?>
              <a href="<?php echo esc_url($referrer); ?>" target="_blank" rel="noopener"><?php echo esc_html($referrer); ?></a>
            <?php else : ?>
              -
            <?php endif; ?>
          </dd>
          <dt>Gửi lúc</dt><dd><?php echo ($submitted_at !== '') ? esc_html($submitted_at) : '-'; ?></dd>
          <dt>Cập nhật trạng thái</dt><dd><?php echo ($last_status_change !== '') ? esc_html($last_status_change) : '-'; ?></dd>
          <dt>Webhook lần cuối</dt><dd><?php echo ($webhook_sent_at !== '') ? esc_html($webhook_sent_at) : '-'; ?></dd>
          <dt>Sự kiện webhook</dt><dd><?php echo ($webhook_last_event !== '') ? esc_html($webhook_last_event) : '-'; ?></dd>
          <dt>Kết quả webhook</dt><dd><?php echo esc_html(my_theme_lead_webhook_status_label($webhook_status)); ?></dd>
          <dt>Mã phản hồi webhook</dt><dd><?php echo ($webhook_code !== '') ? esc_html($webhook_code) : '-'; ?></dd>
          <dt>Lỗi webhook</dt><dd><?php echo ($webhook_error !== '') ? esc_html($webhook_error) : '-'; ?></dd>
          <dt>Số lần gửi thành công</dt><dd><?php echo ($webhook_delivery_count !== '') ? esc_html($webhook_delivery_count) : '0'; ?></dd>
          <dt>Số lần thử gửi</dt><dd><?php echo ($webhook_attempt_count !== '') ? esc_html($webhook_attempt_count) : '0'; ?></dd>
          <dt>Lỗi liên tiếp</dt><dd><?php echo ($webhook_consecutive_failures !== '') ? esc_html($webhook_consecutive_failures) : '0'; ?></dd>
          <dt>IP</dt><dd><?php echo ($ip !== '') ? esc_html($ip) : '-'; ?></dd>
          <dt>User Agent</dt><dd><?php echo ($user_agent !== '') ? esc_html($user_agent) : '-'; ?></dd>
        </dl>

        <?php if ($resend_webhook_url !== '') : ?>
          <p><a class="button" href="<?php echo esc_url($resend_webhook_url); ?>">Gửi lại webhook</a></p>
        <?php endif; ?>

        <div class="my-theme-lead-admin-field">
          <label for="my-theme-lead-status">Trạng thái chăm sóc</label>
          <select id="my-theme-lead-status" name="my_theme_lead_status">
            <?php foreach ($status_options as $value => $label) : ?>
              <option value="<?php echo esc_attr($value); ?>" <?php selected($status, $value); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="my-theme-lead-admin-field">
          <label for="my-theme-lead-priority">Mức ưu tiên</label>
          <select id="my-theme-lead-priority" name="my_theme_lead_priority">
            <?php foreach ($priority_options as $value => $label) : ?>
              <option value="<?php echo esc_attr($value); ?>" <?php selected($priority, $value); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="my-theme-lead-admin-field">
          <label for="my-theme-lead-next-follow-up">Lịch chăm sóc tiếp theo</label>
          <input id="my-theme-lead-next-follow-up" type="datetime-local" name="my_theme_lead_next_follow_up" value="<?php echo esc_attr($next_follow_up_input); ?>" />
        </div>

        <div class="my-theme-lead-admin-field">
          <label for="my-theme-lead-assignee">Phụ trách</label>
          <input id="my-theme-lead-assignee" type="text" name="my_theme_lead_assignee" value="<?php echo esc_attr($assignee); ?>" placeholder="Ví dụ: Nguyễn Văn A" />
        </div>

        <div class="my-theme-lead-admin-field">
          <label for="my-theme-lead-note">Ghi chú nội bộ</label>
          <textarea id="my-theme-lead-note" name="my_theme_lead_note" rows="4" placeholder="Ghi tiến độ liên hệ, nhu cầu thực tế, trạng thái chốt..."><?php echo esc_textarea($note); ?></textarea>
        </div>

        <div class="my-theme-lead-activity">
          <h4>Lịch sử hoạt động</h4>
          <?php
          if (empty($activity_log) || !is_array($activity_log)) :
              ?>
            <p>Chưa có lịch sử hoạt động.</p>
            <?php
          else :
              $recent_activity = array_reverse(array_slice($activity_log, -25));
              ?>
            <ul class="my-theme-lead-activity-list">
              <?php foreach ($recent_activity as $entry) : ?>
                <?php
                if (!is_array($entry)) {
                    continue;
                }
                $entry_time = isset($entry['time']) ? sanitize_text_field((string) $entry['time']) : '-';
                $entry_message = isset($entry['message']) ? sanitize_text_field((string) $entry['message']) : 'Cập nhật lead';
                $entry_event = isset($entry['event']) ? sanitize_key((string) $entry['event']) : '';
                $entry_user_id = isset($entry['user_id']) ? absint($entry['user_id']) : 0;
                $entry_user = '';
                if ($entry_user_id > 0) {
                    $entry_user_obj = get_userdata($entry_user_id);
                    if ($entry_user_obj && !empty($entry_user_obj->display_name)) {
                        $entry_user = (string) $entry_user_obj->display_name;
                    }
                }
                ?>
                <li>
                  <div class="my-theme-lead-activity-meta">
                    <?php echo esc_html($entry_time); ?>
                    <?php if ($entry_user !== '') : ?>
                      <?php echo esc_html(' - ' . $entry_user); ?>
                    <?php endif; ?>
                    <?php if ($entry_event !== '') : ?>
                      <?php echo esc_html(' (' . $entry_event . ')'); ?>
                    <?php endif; ?>
                  </div>
                  <div><?php echo esc_html($entry_message); ?></div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
        <?php
    }
}

if (!function_exists('my_theme_lead_save_meta_box')) {
    function my_theme_lead_save_meta_box($post_id)
    {
        if (!isset($_POST['my_theme_customer_lead_meta_nonce'])) {
            return;
        }
        if (!wp_verify_nonce((string) wp_unslash($_POST['my_theme_customer_lead_meta_nonce']), 'my_theme_save_customer_lead_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (get_post_type($post_id) !== 'customer_lead') {
            return;
        }

        $status_options = my_theme_lead_status_options();
        $status = isset($_POST['my_theme_lead_status']) ? sanitize_key((string) wp_unslash($_POST['my_theme_lead_status'])) : 'new';
        if (!isset($status_options[$status])) {
            $status = 'new';
        }
        $old_status = (string) get_post_meta($post_id, '_lead_status', true);
        update_post_meta($post_id, '_lead_status', $status);
        if ($old_status !== $status) {
            update_post_meta($post_id, '_lead_last_status_change', current_time('mysql'));
            $from_label = isset($status_options[$old_status]) ? $status_options[$old_status] : $old_status;
            $to_label = isset($status_options[$status]) ? $status_options[$status] : $status;
            my_theme_lead_add_activity($post_id, 'status_changed', 'Đổi trạng thái: ' . $from_label . ' -> ' . $to_label, [
                'from' => (string) $old_status,
                'to' => (string) $status,
            ]);
            my_theme_send_lead_webhook($post_id, 'lead_status_updated', false);
        }

        $priority_options = my_theme_lead_priority_options();
        $priority = isset($_POST['my_theme_lead_priority']) ? sanitize_key((string) wp_unslash($_POST['my_theme_lead_priority'])) : 'normal';
        if (!isset($priority_options[$priority])) {
            $priority = 'normal';
        }
        $old_priority = (string) get_post_meta($post_id, '_lead_priority', true);
        if (!isset($priority_options[$old_priority])) {
            $old_priority = 'normal';
        }
        update_post_meta($post_id, '_lead_priority', $priority);
        if ($old_priority !== $priority) {
            my_theme_lead_add_activity($post_id, 'priority_changed', 'Đổi mức ưu tiên: ' . $priority_options[$old_priority] . ' -> ' . $priority_options[$priority], [
                'from' => $old_priority,
                'to' => $priority,
            ]);
        }

        $old_followup_value = (string) get_post_meta($post_id, '_lead_next_follow_up', true);
        $followup_raw = isset($_POST['my_theme_lead_next_follow_up']) ? sanitize_text_field((string) wp_unslash($_POST['my_theme_lead_next_follow_up'])) : '';
        $followup_data = my_theme_lead_normalize_followup_datetime($followup_raw);
        if ($followup_data['value'] === '' || (int) $followup_data['timestamp'] <= 0) {
            delete_post_meta($post_id, '_lead_next_follow_up');
            delete_post_meta($post_id, '_lead_next_follow_up_ts');
            if ($old_followup_value !== '') {
                my_theme_lead_add_activity($post_id, 'followup_updated', 'Xóa lịch chăm sóc.', []);
            }
        } else {
            update_post_meta($post_id, '_lead_next_follow_up', $followup_data['value']);
            update_post_meta($post_id, '_lead_next_follow_up_ts', (int) $followup_data['timestamp']);
            if ($old_followup_value !== $followup_data['value']) {
                my_theme_lead_add_activity($post_id, 'followup_updated', 'Đặt lịch chăm sóc: ' . $followup_data['value'], [
                    'followup_at' => $followup_data['value'],
                ]);
            }
        }

        $assignee = isset($_POST['my_theme_lead_assignee']) ? sanitize_text_field((string) wp_unslash($_POST['my_theme_lead_assignee'])) : '';
        $old_assignee = (string) get_post_meta($post_id, '_lead_assignee', true);
        if ($assignee === '') {
            delete_post_meta($post_id, '_lead_assignee');
        } else {
            update_post_meta($post_id, '_lead_assignee', $assignee);
        }
        if ($old_assignee !== $assignee) {
            $old_assignee_label = $old_assignee !== '' ? $old_assignee : 'Chưa phân công';
            $new_assignee_label = $assignee !== '' ? $assignee : 'Chưa phân công';
            my_theme_lead_add_activity($post_id, 'assignee_changed', 'Đổi phụ trách: ' . $old_assignee_label . ' -> ' . $new_assignee_label, [
                'from' => $old_assignee_label,
                'to' => $new_assignee_label,
            ]);
        }

        $note = isset($_POST['my_theme_lead_note']) ? sanitize_textarea_field((string) wp_unslash($_POST['my_theme_lead_note'])) : '';
        $old_note = (string) get_post_meta($post_id, '_lead_note', true);
        if ($note === '') {
            delete_post_meta($post_id, '_lead_note');
        } else {
            update_post_meta($post_id, '_lead_note', $note);
        }
        if ($old_note !== $note) {
            my_theme_lead_add_activity($post_id, 'note_updated', 'Cập nhật ghi chú nội bộ.', []);
        }
    }
}
add_action('save_post_customer_lead', 'my_theme_lead_save_meta_box');

add_filter('manage_customer_lead_posts_columns', function ($columns) {
    return [
        'cb' => isset($columns['cb']) ? $columns['cb'] : '<input type="checkbox" />',
        'title' => 'Khách hàng',
        'lead_phone' => 'Điện thoại',
        'lead_email' => 'Email',
        'lead_source' => 'Nguồn',
        'lead_orders' => 'Đơn hàng',
        'lead_status' => 'Trạng thái',
        'lead_priority' => 'Ưu tiên',
        'lead_followup' => 'Nhắc chăm sóc',
        'lead_assignee' => 'Phụ trách',
        'lead_webhook' => 'Webhook',
        'date' => 'Ngày tạo',
    ];
});

add_action('manage_customer_lead_posts_custom_column', function ($column, $post_id) {
    if ($column === 'lead_phone') {
        $phone = (string) get_post_meta($post_id, '_lead_phone', true);
        if ($phone !== '') {
            echo '<a href="' . esc_url('tel:' . $phone) . '">' . esc_html($phone) . '</a>';
        } else {
            echo '-';
        }
        return;
    }

    if ($column === 'lead_email') {
        $email = (string) get_post_meta($post_id, '_lead_email', true);
        if ($email !== '') {
            echo '<a href="' . esc_url('mailto:' . $email) . '">' . esc_html($email) . '</a>';
        } else {
            echo '-';
        }
        return;
    }

    if ($column === 'lead_source') {
        $tag = (string) get_post_meta($post_id, '_lead_source_tag', true);
        $url = (string) get_post_meta($post_id, '_lead_source_url', true);
        $utm_source = (string) get_post_meta($post_id, '_lead_utm_source', true);
        $utm_campaign = (string) get_post_meta($post_id, '_lead_utm_campaign', true);
        $origin_label = my_theme_lead_origin_label($tag);
        $origin_class = ($origin_label === 'WooCommerce') ? 'woocommerce' : 'webform';
        $duplicate_submit_count = (int) get_post_meta($post_id, '_lead_duplicate_submit_count', true);

        echo '<span class="my-theme-lead-origin-badge my-theme-lead-origin-' . esc_attr($origin_class) . '">' . esc_html($origin_label) . '</span>';
        if ($tag !== '') {
            echo '<br><small>' . esc_html($tag) . '</small>';
        }
        if ($duplicate_submit_count > 0) {
            echo '<br><small>Gửi lại: ' . esc_html((string) $duplicate_submit_count) . '</small>';
        }
        if ($utm_source !== '' || $utm_campaign !== '') {
            echo '<br><small>' . esc_html(trim($utm_source . ' / ' . $utm_campaign, ' /')) . '</small>';
        }
        if ($url !== '') {
            echo '<br><a href="' . esc_url($url) . '" target="_blank" rel="noopener">Mở trang gửi</a>';
        }
        return;
    }

    if ($column === 'lead_orders') {
        $order_count = (int) get_post_meta($post_id, '_lead_order_count', true);
        $last_order_id = (int) get_post_meta($post_id, '_lead_last_order_id', true);
        $last_order_number = (string) get_post_meta($post_id, '_lead_last_order_number', true);

        if ($order_count <= 0 && $last_order_id <= 0) {
            echo '-';
            return;
        }

        echo esc_html((string) max(0, $order_count));
        if ($last_order_id > 0) {
            $order_label = $last_order_number !== '' ? $last_order_number : (string) $last_order_id;
            $order_url = admin_url('post.php?post=' . $last_order_id . '&action=edit');
            echo '<br><small><a href="' . esc_url($order_url) . '">#' . esc_html($order_label) . '</a></small>';
        }
        return;
    }

    if ($column === 'lead_status') {
        $status = (string) get_post_meta($post_id, '_lead_status', true);
        $options = my_theme_lead_status_options();
        if (!isset($options[$status])) {
            $status = 'new';
        }
        echo '<span class="my-theme-lead-status-badge my-theme-lead-status-' . esc_attr($status) . '">' . esc_html($options[$status]) . '</span>';
        return;
    }

    if ($column === 'lead_priority') {
        $priority = (string) get_post_meta($post_id, '_lead_priority', true);
        $priority_options = my_theme_lead_priority_options();
        if (!isset($priority_options[$priority])) {
            $priority = 'normal';
        }
        echo '<span class="my-theme-lead-priority-badge my-theme-lead-priority-' . esc_attr($priority) . '">' . esc_html($priority_options[$priority]) . '</span>';
        return;
    }

    if ($column === 'lead_followup') {
        $followup_value = (string) get_post_meta($post_id, '_lead_next_follow_up', true);
        $followup_ts = (int) get_post_meta($post_id, '_lead_next_follow_up_ts', true);
        if ($followup_value === '' || $followup_ts <= 0) {
            echo '<span class="my-theme-lead-followup-muted">Chưa đặt</span>';
            return;
        }
        $status_label = my_theme_lead_followup_status_label($followup_ts);
        $status_key = 'upcoming';
        if ($status_label === 'Quá hạn') {
            $status_key = 'overdue';
        } elseif ($status_label === 'Hôm nay') {
            $status_key = 'today';
        }
        echo esc_html($followup_value);
        echo '<br><span class="my-theme-lead-followup-badge my-theme-lead-followup-' . esc_attr($status_key) . '">' . esc_html($status_label) . '</span>';
        return;
    }

    if ($column === 'lead_assignee') {
        $assignee = (string) get_post_meta($post_id, '_lead_assignee', true);
        echo ($assignee !== '') ? esc_html($assignee) : '-';
        return;
    }

    if ($column === 'lead_webhook') {
        $webhook_status = (string) get_post_meta($post_id, '_lead_webhook_last_status', true);
        $webhook_code = (string) get_post_meta($post_id, '_lead_webhook_last_code', true);
        if ($webhook_status === '') {
            echo '-';
            return;
        }

        echo '<span class="my-theme-lead-webhook-badge my-theme-lead-webhook-' . esc_attr($webhook_status) . '">' . esc_html(my_theme_lead_webhook_status_label($webhook_status)) . '</span>';
        if ($webhook_code !== '') {
            echo '<br><small>HTTP ' . esc_html($webhook_code) . '</small>';
        }
    }
}, 10, 2);

if (!function_exists('my_theme_lead_phone_to_zalo')) {
    function my_theme_lead_phone_to_zalo($phone = '')
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

add_filter('post_row_actions', function ($actions, $post) {
    if (!$post instanceof WP_Post || $post->post_type !== 'customer_lead') {
        return $actions;
    }

    $phone = (string) get_post_meta($post->ID, '_lead_phone', true);
    $email = (string) get_post_meta($post->ID, '_lead_email', true);
    $status = (string) get_post_meta($post->ID, '_lead_status', true);

    if ($phone !== '') {
        $actions['my_theme_lead_call'] = '<a href="' . esc_url('tel:' . $phone) . '">Gọi</a>';
        $zalo_phone = my_theme_lead_phone_to_zalo($phone);
        if ($zalo_phone !== '') {
            $actions['my_theme_lead_zalo'] = '<a href="' . esc_url('https://zalo.me/' . $zalo_phone) . '" target="_blank" rel="noopener">Zalo</a>';
        }
    }

    if ($email !== '') {
        $actions['my_theme_lead_email'] = '<a href="' . esc_url('mailto:' . $email) . '">Email</a>';
    }

    if ($status !== 'contacted' && current_user_can('edit_post', $post->ID)) {
        $mark_url = add_query_arg([
            'action' => 'my_theme_lead_quick_status_update',
            'lead_id' => (int) $post->ID,
            'lead_status' => 'contacted',
        ], admin_url('admin-post.php'));
        $mark_url = wp_nonce_url($mark_url, 'my_theme_lead_quick_status_' . (int) $post->ID);
        $actions['my_theme_lead_mark_contacted'] = '<a href="' . esc_url($mark_url) . '">Đánh dấu đã liên hệ</a>';
    }

    $webhook_settings = my_theme_lead_get_webhook_settings();
    if (current_user_can('edit_post', $post->ID) && $webhook_settings['url'] !== '') {
        $resend_webhook_url = add_query_arg([
            'action' => 'my_theme_lead_resend_webhook',
            'lead_id' => (int) $post->ID,
        ], admin_url('admin-post.php'));
        $resend_webhook_url = wp_nonce_url($resend_webhook_url, 'my_theme_lead_resend_webhook_' . (int) $post->ID);
        $actions['my_theme_lead_resend_webhook'] = '<a href="' . esc_url($resend_webhook_url) . '">Gửi lại webhook</a>';
    }

    return $actions;
}, 20, 2);

if (!function_exists('my_theme_lead_handle_quick_status_update')) {
    function my_theme_lead_handle_quick_status_update()
    {
        if (!current_user_can('edit_posts')) {
            wp_die('Permission denied.');
        }

        $lead_id = isset($_GET['lead_id']) ? absint((string) wp_unslash($_GET['lead_id'])) : 0;
        $status = isset($_GET['lead_status']) ? sanitize_key((string) wp_unslash($_GET['lead_status'])) : '';
        $status_options = my_theme_lead_status_options();

        if ($lead_id <= 0 || !isset($status_options[$status]) || get_post_type($lead_id) !== 'customer_lead') {
            wp_die('Invalid request.');
        }
        if (!current_user_can('edit_post', $lead_id)) {
            wp_die('Permission denied.');
        }

        check_admin_referer('my_theme_lead_quick_status_' . $lead_id);

        $old_status = (string) get_post_meta($lead_id, '_lead_status', true);
        update_post_meta($lead_id, '_lead_status', $status);
        if ($old_status !== $status) {
            update_post_meta($lead_id, '_lead_last_status_change', current_time('mysql'));
            $from_label = isset($status_options[$old_status]) ? $status_options[$old_status] : $old_status;
            $to_label = isset($status_options[$status]) ? $status_options[$status] : $status;
            my_theme_lead_add_activity($lead_id, 'status_changed', 'Đổi trạng thái nhanh: ' . $from_label . ' -> ' . $to_label, [
                'from' => (string) $old_status,
                'to' => (string) $status,
            ]);
            my_theme_send_lead_webhook($lead_id, 'lead_status_updated', false);
        }

        $redirect = wp_get_referer();
        $fallback = admin_url('edit.php?post_type=customer_lead');
        $redirect = is_string($redirect) ? wp_validate_redirect($redirect, $fallback) : $fallback;
        if (!is_string($redirect) || trim($redirect) === '') {
            $redirect = $fallback;
        }

        $redirect = add_query_arg([
            'my_theme_lead_quick_updated' => 1,
            'my_theme_lead_quick_status' => $status,
        ], $redirect);
        wp_safe_redirect($redirect);
        exit;
    }
}
add_action('admin_post_my_theme_lead_quick_status_update', 'my_theme_lead_handle_quick_status_update');

if (!function_exists('my_theme_lead_handle_resend_webhook')) {
    function my_theme_lead_handle_resend_webhook()
    {
        if (!current_user_can('edit_posts')) {
            wp_die('Permission denied.');
        }

        $lead_id = isset($_GET['lead_id']) ? absint((string) wp_unslash($_GET['lead_id'])) : 0;
        if ($lead_id <= 0 || get_post_type($lead_id) !== 'customer_lead') {
            wp_die('Invalid request.');
        }
        if (!current_user_can('edit_post', $lead_id)) {
            wp_die('Permission denied.');
        }

        check_admin_referer('my_theme_lead_resend_webhook_' . $lead_id);

        $result = my_theme_send_lead_webhook($lead_id, 'lead_resend', true);
        $redirect = wp_get_referer();
        $fallback = admin_url('edit.php?post_type=customer_lead');
        $redirect = is_string($redirect) ? wp_validate_redirect($redirect, $fallback) : $fallback;
        if (!is_string($redirect) || trim($redirect) === '') {
            $redirect = $fallback;
        }

        if (is_wp_error($result)) {
            $error_msg = sanitize_text_field((string) $result->get_error_message());
            if (strlen($error_msg) > 160) {
                $error_msg = substr($error_msg, 0, 160);
            }
            $redirect = add_query_arg([
                'my_theme_lead_webhook' => 'error',
                'my_theme_lead_webhook_msg' => $error_msg,
            ], $redirect);
        } else {
            $redirect = add_query_arg([
                'my_theme_lead_webhook' => 'success',
            ], $redirect);
        }

        wp_safe_redirect($redirect);
        exit;
    }
}
add_action('admin_post_my_theme_lead_resend_webhook', 'my_theme_lead_handle_resend_webhook');

if (!function_exists('my_theme_lead_get_status_from_request')) {
    function my_theme_lead_get_status_from_request()
    {
        $status = isset($_GET['lead_status']) ? sanitize_key((string) wp_unslash($_GET['lead_status'])) : '';
        $options = my_theme_lead_status_options();
        return isset($options[$status]) ? $status : '';
    }
}

if (!function_exists('my_theme_lead_get_priority_from_request')) {
    function my_theme_lead_get_priority_from_request()
    {
        $priority = isset($_GET['lead_priority']) ? sanitize_key((string) wp_unslash($_GET['lead_priority'])) : '';
        $options = my_theme_lead_priority_options();
        return isset($options[$priority]) ? $priority : '';
    }
}

if (!function_exists('my_theme_lead_get_followup_filter_from_request')) {
    function my_theme_lead_get_followup_filter_from_request()
    {
        $value = isset($_GET['lead_followup']) ? sanitize_key((string) wp_unslash($_GET['lead_followup'])) : '';
        $options = my_theme_lead_followup_filter_options();
        return isset($options[$value]) ? $value : '';
    }
}

if (!function_exists('my_theme_lead_get_origin_from_request')) {
    function my_theme_lead_get_origin_from_request()
    {
        $value = isset($_GET['lead_origin']) ? sanitize_key((string) wp_unslash($_GET['lead_origin'])) : '';
        $options = my_theme_lead_origin_filter_options();
        return isset($options[$value]) ? $value : '';
    }
}

if (!function_exists('my_theme_lead_get_keyword_from_request')) {
    function my_theme_lead_get_keyword_from_request()
    {
        if (!isset($_GET['lead_keyword'])) {
            return '';
        }
        $keyword = sanitize_text_field((string) wp_unslash($_GET['lead_keyword']));
        $keyword = trim($keyword);
        if ($keyword === '') {
            return '';
        }
        return substr($keyword, 0, 120);
    }
}

if (!function_exists('my_theme_lead_get_date_from_request')) {
    function my_theme_lead_get_date_from_request($key = '')
    {
        $key = sanitize_key((string) $key);
        if ($key === '' || !isset($_GET[$key])) {
            return '';
        }

        $raw = sanitize_text_field((string) wp_unslash($_GET[$key]));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return '';
        }

        $parts = array_map('intval', explode('-', $raw));
        if (count($parts) !== 3) {
            return '';
        }

        $year = $parts[0];
        $month = $parts[1];
        $day = $parts[2];
        if (!checkdate($month, $day, $year)) {
            return '';
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }
}

add_action('pre_get_posts', function ($query) {
    if (!is_admin() || !$query instanceof WP_Query || !$query->is_main_query()) {
        return;
    }

    $post_type = $query->get('post_type');
    if ($post_type !== 'customer_lead') {
        return;
    }

    $status = my_theme_lead_get_status_from_request();
    $priority = my_theme_lead_get_priority_from_request();
    $followup = my_theme_lead_get_followup_filter_from_request();
    $origin = my_theme_lead_get_origin_from_request();
    $keyword = my_theme_lead_get_keyword_from_request();
    $keyword_phone = my_theme_lead_normalize_phone($keyword);
    $date_from = my_theme_lead_get_date_from_request('lead_date_from');
    $date_to = my_theme_lead_get_date_from_request('lead_date_to');
    $meta_query = (array) $query->get('meta_query');
    $should_apply_meta_query = false;

    if ($status !== '') {
        $meta_query[] = [
            'key' => '_lead_status',
            'value' => $status,
        ];
        $should_apply_meta_query = true;
    }
    if ($priority !== '') {
        $meta_query[] = [
            'key' => '_lead_priority',
            'value' => $priority,
        ];
        $should_apply_meta_query = true;
    }
    if ($origin !== '') {
        $origin_meta_query = my_theme_lead_origin_meta_query($origin);
        if (!empty($origin_meta_query)) {
            $meta_query[] = $origin_meta_query;
            $should_apply_meta_query = true;
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
        $should_apply_meta_query = true;
        $query->set('s', '');
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
        $should_apply_meta_query = true;
    }

    if ($should_apply_meta_query) {
        $query->set('meta_query', $meta_query);
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
        $query->set('date_query', [$date_rule]);
    }
});

add_action('restrict_manage_posts', function ($post_type) {
    if ($post_type !== 'customer_lead') {
        return;
    }

    $selected = my_theme_lead_get_status_from_request();
    $priority = my_theme_lead_get_priority_from_request();
    $followup = my_theme_lead_get_followup_filter_from_request();
    $origin = my_theme_lead_get_origin_from_request();
    $keyword = my_theme_lead_get_keyword_from_request();
    $date_from = my_theme_lead_get_date_from_request('lead_date_from');
    $date_to = my_theme_lead_get_date_from_request('lead_date_to');
    $options = my_theme_lead_status_options();
    $priority_options = my_theme_lead_priority_options();
    $followup_options = my_theme_lead_followup_filter_options();
    $origin_options = my_theme_lead_origin_filter_options();

    echo '<label class="screen-reader-text" for="filter-by-lead-status">Lọc theo trạng thái</label>';
    echo '<select id="filter-by-lead-status" name="lead_status">';
    echo '<option value="">Tất cả trạng thái</option>';
    foreach ($options as $value => $label) {
        echo '<option value="' . esc_attr($value) . '" ' . selected($selected, $value, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
    echo '<label class="screen-reader-text" for="filter-by-lead-priority">Lọc theo ưu tiên</label>';
    echo '<select id="filter-by-lead-priority" name="lead_priority">';
    echo '<option value="">Tất cả ưu tiên</option>';
    foreach ($priority_options as $value => $label) {
        echo '<option value="' . esc_attr($value) . '" ' . selected($priority, $value, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
    echo '<label class="screen-reader-text" for="filter-by-lead-followup">Lọc theo nhắc chăm sóc</label>';
    echo '<select id="filter-by-lead-followup" name="lead_followup">';
    echo '<option value="">Tất cả nhắc chăm sóc</option>';
    foreach ($followup_options as $value => $label) {
        echo '<option value="' . esc_attr($value) . '" ' . selected($followup, $value, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
    echo '<label class="screen-reader-text" for="filter-by-lead-origin">Lọc theo nguồn lead</label>';
    echo '<select id="filter-by-lead-origin" name="lead_origin">';
    echo '<option value="">Tất cả nguồn lead</option>';
    foreach ($origin_options as $value => $label) {
        echo '<option value="' . esc_attr($value) . '" ' . selected($origin, $value, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
    echo '<label class="screen-reader-text" for="filter-by-lead-keyword">Từ khóa lead</label>';
    echo '<input type="search" id="filter-by-lead-keyword" name="lead_keyword" value="' . esc_attr($keyword) . '" placeholder="Tên, SĐT, email..." />';
    echo '<label class="screen-reader-text" for="filter-by-lead-date-from">Từ ngày</label>';
    echo '<input type="date" id="filter-by-lead-date-from" name="lead_date_from" value="' . esc_attr($date_from) . '" />';
    echo '<label class="screen-reader-text" for="filter-by-lead-date-to">Đến ngày</label>';
    echo '<input type="date" id="filter-by-lead-date-to" name="lead_date_to" value="' . esc_attr($date_to) . '" />';

    $export_args = [
        'action' => 'my_theme_export_customer_leads',
    ];
    if ($selected !== '') {
        $export_args['lead_status'] = $selected;
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
    echo '<a class="button my-theme-lead-export-btn" href="' . esc_url($export_url) . '">Xuất CSV</a>';
}, 20, 1);

if (!function_exists('my_theme_export_customer_leads_csv')) {
    function my_theme_export_customer_leads_csv()
    {
        if (!current_user_can('edit_posts')) {
            wp_die('Permission denied.');
        }

        check_admin_referer('my_theme_export_customer_leads');

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

        $query_args = [
            'post_type' => 'customer_lead',
            'post_status' => ['publish', 'private', 'draft'],
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'ids',
            'no_found_rows' => true,
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

        $filename_suffix = $status !== '' ? ('-' . $status) : '-all';
        if ($priority !== '') {
            $filename_suffix .= '-priority';
        }
        if ($followup !== '') {
            $filename_suffix .= '-followup';
        }
        if ($origin !== '') {
            $filename_suffix .= '-origin';
        }
        if ($keyword !== '') {
            $filename_suffix .= '-search';
        }
        if ($date_from !== '' || $date_to !== '') {
            $filename_suffix .= '-date';
        }
        $filename = 'customer-leads' . $filename_suffix . '-' . gmdate('Ymd-His') . '.csv';

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        if ($output === false) {
            wp_die('Cannot open export stream.');
        }

        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, [
            'ID',
            'Ngay tao',
            'Ho ten',
            'Dien thoai',
            'Email',
            'Kenh lien he',
            'Nhu cau',
            'Ngan sach',
            'Trang thai',
            'Uu tien',
            'So lan gui lai form',
            'So don WooCommerce',
            'Don gan nhat',
            'Tong gia tri don',
            'Lich cham soc',
            'Trang thai lich cham soc',
            'Phu trach',
            'Nguon lead',
            'Nguon form',
            'URL gui form',
            'UTM source',
            'UTM medium',
            'UTM campaign',
            'UTM term',
            'UTM content',
            'FBCLID',
            'GCLID',
            'Webhook status',
            'Webhook code',
            'Webhook sent at',
            'Last activity at',
            'Last activity',
            'Referrer',
            'Ghi chu khach',
            'Ghi chu noi bo',
        ]);

        foreach ($lead_ids as $lead_id) {
            $lead_id = (int) $lead_id;
            if ($lead_id <= 0) {
                continue;
            }

            $name = (string) get_post_meta($lead_id, '_lead_name', true);
            $phone = (string) get_post_meta($lead_id, '_lead_phone', true);
            $email = (string) get_post_meta($lead_id, '_lead_email', true);
            $channel = (string) get_post_meta($lead_id, '_lead_contact_channel', true);
            $project = (string) get_post_meta($lead_id, '_lead_project_type', true);
            $budget = (string) get_post_meta($lead_id, '_lead_budget', true);
            $lead_status = (string) get_post_meta($lead_id, '_lead_status', true);
            $lead_priority = (string) get_post_meta($lead_id, '_lead_priority', true);
            $duplicate_submit_count = (int) get_post_meta($lead_id, '_lead_duplicate_submit_count', true);
            $order_count = (int) get_post_meta($lead_id, '_lead_order_count', true);
            $last_order_id = (int) get_post_meta($lead_id, '_lead_last_order_id', true);
            $last_order_number = (string) get_post_meta($lead_id, '_lead_last_order_number', true);
            $total_order_value = (string) get_post_meta($lead_id, '_lead_total_order_value', true);
            $next_follow_up = (string) get_post_meta($lead_id, '_lead_next_follow_up', true);
            $next_follow_up_ts = (int) get_post_meta($lead_id, '_lead_next_follow_up_ts', true);
            $assignee = (string) get_post_meta($lead_id, '_lead_assignee', true);
            $source_tag = (string) get_post_meta($lead_id, '_lead_source_tag', true);
            $lead_origin_label = my_theme_lead_origin_label($source_tag);
            $source_url = (string) get_post_meta($lead_id, '_lead_source_url', true);
            $utm_source = (string) get_post_meta($lead_id, '_lead_utm_source', true);
            $utm_medium = (string) get_post_meta($lead_id, '_lead_utm_medium', true);
            $utm_campaign = (string) get_post_meta($lead_id, '_lead_utm_campaign', true);
            $utm_term = (string) get_post_meta($lead_id, '_lead_utm_term', true);
            $utm_content = (string) get_post_meta($lead_id, '_lead_utm_content', true);
            $fbclid = (string) get_post_meta($lead_id, '_lead_fbclid', true);
            $gclid = (string) get_post_meta($lead_id, '_lead_gclid', true);
            $webhook_status = (string) get_post_meta($lead_id, '_lead_webhook_last_status', true);
            $webhook_code = (string) get_post_meta($lead_id, '_lead_webhook_last_code', true);
            $webhook_sent_at = (string) get_post_meta($lead_id, '_lead_webhook_last_sent_at', true);
            $activity_log = my_theme_lead_get_activity_log($lead_id);
            $last_activity_time = '';
            $last_activity_message = '';
            if (!empty($activity_log) && is_array($activity_log)) {
                $last_activity = end($activity_log);
                if (is_array($last_activity)) {
                    $last_activity_time = isset($last_activity['time']) ? sanitize_text_field((string) $last_activity['time']) : '';
                    $last_activity_message = isset($last_activity['message']) ? sanitize_text_field((string) $last_activity['message']) : '';
                }
                reset($activity_log);
            }
            $referrer = (string) get_post_meta($lead_id, '_lead_referrer', true);
            $customer_note = (string) get_post_meta($lead_id, '_lead_message', true);
            $internal_note = (string) get_post_meta($lead_id, '_lead_note', true);
            $submitted_at = (string) get_post_meta($lead_id, '_lead_submitted_at', true);

            if (!isset($status_options[$lead_status])) {
                $lead_status = 'new';
            }
            if (!isset($priority_options[$lead_priority])) {
                $lead_priority = 'normal';
            }
            $last_order_label = '';
            if ($last_order_id > 0) {
                $last_order_label = '#' . ($last_order_number !== '' ? $last_order_number : (string) $last_order_id);
            }

            fputcsv($output, [
                $lead_id,
                $submitted_at,
                $name,
                $phone,
                $email,
                my_theme_lead_channel_label($channel),
                $project,
                $budget,
                $status_options[$lead_status],
                $priority_options[$lead_priority],
                max(0, $duplicate_submit_count),
                max(0, $order_count),
                $last_order_label,
                $total_order_value,
                $next_follow_up,
                my_theme_lead_followup_status_label($next_follow_up_ts),
                $assignee,
                $lead_origin_label,
                $source_tag,
                $source_url,
                $utm_source,
                $utm_medium,
                $utm_campaign,
                $utm_term,
                $utm_content,
                $fbclid,
                $gclid,
                my_theme_lead_webhook_status_label($webhook_status),
                $webhook_code,
                $webhook_sent_at,
                $last_activity_time,
                $last_activity_message,
                $referrer,
                $customer_note,
                $internal_note,
            ]);
        }

        fclose($output);
        exit;
    }
}
add_action('admin_post_my_theme_export_customer_leads', 'my_theme_export_customer_leads_csv');

add_filter('bulk_actions-edit-customer_lead', function ($bulk_actions) {
    $status_options = my_theme_lead_status_options();
    foreach ($status_options as $status_key => $status_label) {
        $bulk_actions['my_theme_lead_mark_' . $status_key] = 'Đặt trạng thái: ' . $status_label;
    }
    $bulk_actions['my_theme_lead_retry_webhook'] = 'Gửi lại webhook';
    return $bulk_actions;
});

add_filter('handle_bulk_actions-edit-customer_lead', function ($redirect_to, $doaction, $post_ids) {
    if ((string) $doaction === 'my_theme_lead_retry_webhook') {
        $sent_count = 0;
        $failed_count = 0;
        foreach ((array) $post_ids as $post_id) {
            $post_id = (int) $post_id;
            if ($post_id <= 0 || get_post_type($post_id) !== 'customer_lead') {
                continue;
            }
            if (!current_user_can('edit_post', $post_id)) {
                continue;
            }
            $result = my_theme_send_lead_webhook($post_id, 'lead_resend', true);
            if (is_wp_error($result)) {
                $failed_count++;
            } else {
                $sent_count++;
            }
        }
        return add_query_arg([
            'my_theme_lead_webhook_bulk_sent' => $sent_count,
            'my_theme_lead_webhook_bulk_failed' => $failed_count,
        ], $redirect_to);
    }

    if (strpos((string) $doaction, 'my_theme_lead_mark_') !== 0) {
        return $redirect_to;
    }

    $status = sanitize_key(substr((string) $doaction, strlen('my_theme_lead_mark_')));
    $status_options = my_theme_lead_status_options();
    if (!isset($status_options[$status])) {
        return $redirect_to;
    }

    $updated_count = 0;
    foreach ((array) $post_ids as $post_id) {
        $post_id = (int) $post_id;
        if ($post_id <= 0 || get_post_type($post_id) !== 'customer_lead') {
            continue;
        }
        if (!current_user_can('edit_post', $post_id)) {
            continue;
        }
        $old_status = (string) get_post_meta($post_id, '_lead_status', true);
        update_post_meta($post_id, '_lead_status', $status);
        if ($old_status !== $status) {
            update_post_meta($post_id, '_lead_last_status_change', current_time('mysql'));
            $from_label = isset($status_options[$old_status]) ? $status_options[$old_status] : $old_status;
            $to_label = isset($status_options[$status]) ? $status_options[$status] : $status;
            my_theme_lead_add_activity($post_id, 'status_changed', 'Đổi trạng thái hàng loạt: ' . $from_label . ' -> ' . $to_label, [
                'from' => (string) $old_status,
                'to' => (string) $status,
            ]);
            my_theme_send_lead_webhook($post_id, 'lead_status_updated', false);
        }
        $updated_count++;
    }

    return add_query_arg([
        'my_theme_lead_bulk_status' => $status,
        'my_theme_lead_bulk_updated' => $updated_count,
    ], $redirect_to);
}, 10, 3);

add_action('admin_notices', function () {
    if (!is_admin() || !function_exists('get_current_screen')) {
        return;
    }
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->id, ['edit-customer_lead', 'customer_lead'], true)) {
        return;
    }
    $status_options = my_theme_lead_status_options();

    if (isset($_GET['my_theme_lead_bulk_updated']) && isset($_GET['my_theme_lead_bulk_status'])) {
        $updated_count = absint((string) wp_unslash($_GET['my_theme_lead_bulk_updated']));
        $status = sanitize_key((string) wp_unslash($_GET['my_theme_lead_bulk_status']));
        if ($updated_count > 0 && isset($status_options[$status])) {
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo esc_html('Đã cập nhật ' . $updated_count . ' khách hàng sang trạng thái "' . $status_options[$status] . '".');
            echo '</p></div>';
        }
    }

    if (isset($_GET['my_theme_lead_quick_updated']) && isset($_GET['my_theme_lead_quick_status'])) {
        $status = sanitize_key((string) wp_unslash($_GET['my_theme_lead_quick_status']));
        if (isset($status_options[$status])) {
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo esc_html('Đã cập nhật trạng thái lead thành "' . $status_options[$status] . '".');
            echo '</p></div>';
        }
    }

    if (isset($_GET['my_theme_lead_webhook'])) {
        $result = sanitize_key((string) wp_unslash($_GET['my_theme_lead_webhook']));
        if ($result === 'success') {
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo esc_html('Đã gửi lại webhook thành công.');
            echo '</p></div>';
        } elseif ($result === 'error') {
            $message = isset($_GET['my_theme_lead_webhook_msg']) ? sanitize_text_field((string) wp_unslash($_GET['my_theme_lead_webhook_msg'])) : 'Không gửi được webhook.';
            echo '<div class="notice notice-error is-dismissible"><p>';
            echo esc_html($message);
            echo '</p></div>';
        }
    }

    if (isset($_GET['my_theme_lead_webhook_bulk_sent']) || isset($_GET['my_theme_lead_webhook_bulk_failed'])) {
        $sent_count = isset($_GET['my_theme_lead_webhook_bulk_sent']) ? absint((string) wp_unslash($_GET['my_theme_lead_webhook_bulk_sent'])) : 0;
        $failed_count = isset($_GET['my_theme_lead_webhook_bulk_failed']) ? absint((string) wp_unslash($_GET['my_theme_lead_webhook_bulk_failed'])) : 0;
        if ($sent_count > 0 || $failed_count > 0) {
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo esc_html('Webhook hàng loạt: thành công ' . $sent_count . ', lỗi ' . $failed_count . '.');
            echo '</p></div>';
        }
    }
});

