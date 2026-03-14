<?php
/**
 * Export a CSV template for products currently flagged by a Catalog QA issue.
 *
 * Run:
 *   Get-Content -Raw tools/export_missing_price_template.php | docker compose -f docker-compose.wordpress.yml exec -T wordpress php
 * Optional:
 *   ... php -- --brand=dulux
 *   ... php -- --issue=low_res
 *   ... php -- --issue=missing_price --sort=priority
 */

require '/var/www/html/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

$args = array_values(array_filter((array) ($_SERVER['argv'] ?? []), static function ($value): bool {
    return is_string($value) && $value !== '';
}));

$brand_filter = '';
$issue_filter = 'missing_price';
$sort_mode = 'priority';
foreach ($args as $index => $arg) {
    if (strpos((string) $arg, '--brand=') === 0) {
        $brand_filter = sanitize_title((string) substr((string) $arg, 8));
        continue;
    }
    if ($arg === '--brand' && isset($args[$index + 1])) {
        $brand_filter = sanitize_title((string) $args[$index + 1]);
        continue;
    }
    if (strpos((string) $arg, '--issue=') === 0) {
        $issue_filter = sanitize_key((string) substr((string) $arg, 8));
        continue;
    }
    if ($arg === '--issue' && isset($args[$index + 1])) {
        $issue_filter = sanitize_key((string) $args[$index + 1]);
        continue;
    }
    if (strpos((string) $arg, '--sort=') === 0) {
        $sort_mode = sanitize_key((string) substr((string) $arg, 7));
        continue;
    }
    if ($arg === '--sort' && isset($args[$index + 1])) {
        $sort_mode = sanitize_key((string) $args[$index + 1]);
        continue;
    }
}

$allowed_issues = ['all', 'missing_price', 'missing_pack', 'low_res', 'missing_source', 'better_local_image'];
if (!in_array($issue_filter, $allowed_issues, true)) {
    $issue_filter = 'missing_price';
}
if (!in_array($sort_mode, ['default', 'priority'], true)) {
    $sort_mode = 'priority';
}

if (!function_exists('my_theme_get_catalog_completeness_report')) {
    fwrite(STDERR, "Catalog QA helpers are not loaded.\n");
    exit(1);
}

$report = my_theme_get_catalog_completeness_report(true);
$rows = isset($report['rows']) && is_array($report['rows']) ? $report['rows'] : [];
if (function_exists('my_theme_filter_catalog_qa_rows')) {
    $rows = my_theme_filter_catalog_qa_rows($rows, $issue_filter, $brand_filter);
}
if ($sort_mode === 'priority' && function_exists('my_theme_get_catalog_qa_priority_meta')) {
    usort($rows, static function (array $a, array $b) use ($issue_filter): int {
        $a_priority = my_theme_get_catalog_qa_priority_meta($a, $issue_filter);
        $b_priority = my_theme_get_catalog_qa_priority_meta($b, $issue_filter);
        $score_cmp = ((int) ($b_priority['score'] ?? 0)) <=> ((int) ($a_priority['score'] ?? 0));
        if ($score_cmp !== 0) {
            return $score_cmp;
        }

        return strcmp((string) ($a['slug'] ?? ''), (string) ($b['slug'] ?? ''));
    });
}

$out = fopen('php://output', 'w');
if ($out === false) {
    fwrite(STDERR, "Cannot open stdout.\n");
    exit(1);
}

fwrite($out, chr(239) . chr(187) . chr(191));
fputcsv($out, [
    'ID',
    'Slug',
    'Name',
    'Brand',
    'Issues',
    'Priority',
    'Priority Score',
    'Priority Reason',
    'Current Price',
    'Capacity',
    'Weight',
    'Package',
    'Image Width',
    'Image Height',
    'Mapped Image Width',
    'Mapped Image Height',
    'Current Image URL',
    'Source URL',
    'Price',
    'PriceMap',
    'Replacement Image URL',
    'Notes',
]);

foreach ($rows as $row) {
    $priority = function_exists('my_theme_get_catalog_qa_priority_meta')
        ? my_theme_get_catalog_qa_priority_meta($row, $issue_filter)
        : ['label' => '', 'score' => 0, 'reason' => ''];
    $current_image_url = '';
    if (!empty($row['id'])) {
        $thumb_id = get_post_thumbnail_id((int) $row['id']);
        if ($thumb_id > 0) {
            $current_image_url = (string) wp_get_attachment_url($thumb_id);
        }
    }

    fputcsv($out, [
        (int) ($row['id'] ?? 0),
        (string) ($row['slug'] ?? ''),
        (string) ($row['name'] ?? ''),
        (string) ($row['brand'] ?? ''),
        implode('|', array_map('strval', (array) ($row['issues'] ?? []))),
        (string) ($priority['label'] ?? ''),
        (int) ($priority['score'] ?? 0),
        (string) ($priority['reason'] ?? ''),
        (string) ($row['price'] ?? ''),
        (string) ($row['capacity_list'] ?? ''),
        (string) ($row['weight_list'] ?? ''),
        (string) ($row['pack_list'] ?? ''),
        (int) ($row['image_width'] ?? 0),
        (int) ($row['image_height'] ?? 0),
        (int) ($row['mapped_image_width'] ?? 0),
        (int) ($row['mapped_image_height'] ?? 0),
        $current_image_url,
        (string) ($row['source_url'] ?? ''),
        '',
        '',
        '',
        $issue_filter === 'low_res'
            ? 'Review anh: uu tien doi anh lon hon / doi map local neu co.'
            : 'Price = gia base; PriceMap = vi du 5L:442500 | 18L:1501500',
    ]);
}

fclose($out);
