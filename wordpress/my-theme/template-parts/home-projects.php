<?php
/**
 * Home projects visual showcase.
 */

if (!function_exists('my_theme_render_visual_story_showcase')) {
    return;
}

my_theme_render_visual_story_showcase(
    ['interior', 'waterproofing', 'exterior', 'epoxy', 'metal', 'grout'],
    [
        'title' => 'Hình minh họa công trình & ứng dụng thực tế',
        'subtitle' => 'Xem nhanh từng nhóm hạng mục để đi thẳng tới giải pháp, bài tư vấn hoặc dòng sản phẩm phù hợp hơn với hiện trạng thực tế.',
        'class' => 'home-projects',
    ]
);
