<?php
/**
 * Homepage quick FAQ teaser.
 */

if (!function_exists('my_theme_render_quick_answers')) {
    return;
}

my_theme_render_quick_answers([
    'class' => 'quick-answers--home',
    'eyebrow' => 'Khách hay hỏi trước khi đặt',
    'title' => 'Một vài câu hỏi nên chốt ngay từ đầu',
    'subtitle' => 'Nếu bạn đang chuẩn bị lấy báo giá, đây là 3 câu hỏi khách hỏi nhiều nhất trước khi chốt vật tư và lịch giao.',
    'indexes' => [0, 1, 4],
]);
