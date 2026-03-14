<?php
/**
 * Apply researched market prices into WooCommerce.
 *
 * Run:
 *   Get-Content -Raw tools/apply_market_prices_from_report.php | docker compose -f docker-compose.wordpress.yml exec -T wordpress php -- --report /var/www/html/wp-content/themes/my-theme/data/market-pricing.json --backup /var/www/html/wp-content/themes/my-theme/data/market-pricing-backup.json
 */

$stdout = fopen('php://stdout', 'wb');
$stderr = fopen('php://stderr', 'wb');

require '/var/www/html/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite($stderr, "WooCommerce is not loaded.\n");
    exit(1);
}

$argv = isset($_SERVER['argv']) && is_array($_SERVER['argv']) ? $_SERVER['argv'] : [];
$report_path = '/var/www/html/wp-content/themes/my-theme/data/market-pricing.json';
$backup_path = '/var/www/html/wp-content/themes/my-theme/data/market-pricing-backup.json';

for ($i = 1; $i < count($argv); $i++) {
    if ($argv[$i] === '--report' && isset($argv[$i + 1])) {
        $report_path = (string) $argv[$i + 1];
        $i++;
        continue;
    }
    if ($argv[$i] === '--backup' && isset($argv[$i + 1])) {
        $backup_path = (string) $argv[$i + 1];
        $i++;
    }
}

if (!file_exists($report_path)) {
    fwrite($stderr, "Report file not found: {$report_path}\n");
    exit(1);
}

$report_raw = file_get_contents($report_path);
$report = json_decode((string) $report_raw, true);
if (!is_array($report)) {
    fwrite($stderr, "Invalid JSON report.\n");
    exit(1);
}

$products = isset($report['products']) && is_array($report['products']) ? $report['products'] : [];
$backup_rows = [];
$stats = [
    'checked' => 0,
    'updated' => 0,
    'skipped' => 0,
    'errors' => 0,
];

$sort_labels = static function (array $labels, string $unit): array {
    if (function_exists('my_theme_sort_pack_labels')) {
        return my_theme_sort_pack_labels($labels, $unit);
    }

    sort($labels);
    return array_values(array_unique($labels));
};

$split_meta_labels = static function (string $raw_value): array {
    $raw_value = trim($raw_value);
    if ($raw_value === '') {
        return [];
    }

    $raw_value = str_replace([';', "\n"], '|', $raw_value);
    $parts = preg_split('/[|]/', $raw_value);
    $labels = [];
    foreach ((array) $parts as $part) {
        $part = trim((string) $part);
        if ($part === '') {
            continue;
        }
        if (strpos($part, ':') !== false) {
            [$part] = array_map('trim', explode(':', $part, 2));
        }
        if ($part !== '') {
            $labels[] = $part;
        }
    }

    $labels = array_values(array_unique($labels));
    if (function_exists('my_theme_compare_pack_labels')) {
        usort($labels, 'my_theme_compare_pack_labels');
    }

    return $labels;
};

$parse_price_map = static function (string $raw_value) use ($split_meta_labels): array {
    $raw_value = trim($raw_value);
    if ($raw_value === '') {
        return [];
    }

    $raw_value = str_replace([';', "\n"], '|', $raw_value);
    $parts = preg_split('/[|]/', $raw_value);
    $map = [];
    foreach ((array) $parts as $part) {
        $part = trim((string) $part);
        if ($part === '' || strpos($part, ':') === false) {
            continue;
        }

        [$label, $price_value] = array_map('trim', explode(':', $part, 2));
        $label = trim((string) $label);
        $price_value = (float) preg_replace('/[^\d.,]/', '', (string) $price_value);
        if ($label === '' || $price_value <= 0) {
            continue;
        }

        $map[$label] = $price_value;
    }

    if (empty($map)) {
        $labels = $split_meta_labels($raw_value);
        if (count($labels) === 1) {
            $price = 0.0;
            if (preg_match('/(\d+(?:[.,]\d+)?)/', $raw_value, $matches)) {
                $price = (float) str_replace(',', '.', $matches[1]);
            }
            if ($price > 0) {
                $map[$labels[0]] = $price;
            }
        }
    }

    if (function_exists('my_theme_compare_pack_labels')) {
        uksort($map, 'my_theme_compare_pack_labels');
    }

    return $map;
};

$classify_label = static function (string $label): string {
    $normalized = strtolower(trim($label));
    if ($normalized === '') {
        return 'pack';
    }
    if (preg_match('/\b\d+(?:[.,]\d+)?\s*l\b/i', $normalized) || preg_match('/\b\d+(?:[.,]\d+)?ml\b/i', $normalized)) {
        return 'capacity';
    }
    if (preg_match('/\b\d+(?:[.,]\d+)?\s*kg\b/i', $normalized) || preg_match('/\b\d+(?:[.,]\d+)?g\b/i', $normalized)) {
        return 'weight';
    }
    return 'pack';
};

$encode_display_price = static function (float $display_price): float {
    $display_price = (float) round($display_price, 0);
    if ($display_price <= 0) {
        return 0.0;
    }

    if (!function_exists('my_theme_global_sale_is_enabled') || !my_theme_global_sale_is_enabled()) {
        return $display_price;
    }

    $percent = function_exists('my_theme_get_global_sale_percent')
        ? (float) my_theme_get_global_sale_percent()
        : 0.0;
    if ($percent <= 0 || $percent >= 100) {
        return $display_price;
    }

    $ratio = (100 - $percent) / 100;
    $start = max((int) $display_price, (int) floor($display_price / $ratio) - 4);
    $end = $start + 24;

    for ($candidate = $start; $candidate <= $end; $candidate++) {
        $computed = function_exists('my_theme_get_global_sale_price_from_regular')
            ? (float) my_theme_get_global_sale_price_from_regular($candidate)
            : (float) round($candidate * $ratio, 0);
        if ((int) round($computed, 0) === (int) $display_price) {
            return (float) $candidate;
        }
    }

    return (float) ceil($display_price / $ratio);
};

foreach ($products as $row) {
    if (!is_array($row)) {
        continue;
    }

    $stats['checked']++;
    $product_id = isset($row['id']) ? (int) $row['id'] : 0;
    if ($product_id <= 0) {
        $stats['skipped']++;
        continue;
    }

    $resolved_map = isset($row['resolved_price_map']) && is_array($row['resolved_price_map'])
        ? $row['resolved_price_map']
        : [];
    if (empty($resolved_map)) {
        $stats['skipped']++;
        continue;
    }

    $product = wc_get_product($product_id);
    if (!$product instanceof WC_Product) {
        $stats['errors']++;
        fwrite($stderr, "Missing product {$product_id}\n");
        continue;
    }

    $backup_rows[] = [
        'id' => $product_id,
        'slug' => $product->get_slug(),
        'name' => $product->get_name(),
        'price' => $product->get_price('edit'),
        'regular_price' => $product->get_regular_price('edit'),
        'sale_price' => $product->get_sale_price('edit'),
        'capacity_price_map' => (string) $product->get_meta('_capacity_price_map', true),
        'display_capacity_list' => (string) $product->get_meta('_display_capacity_list', true),
        'display_weight_list' => (string) $product->get_meta('_display_weight_list', true),
        'display_pack_list' => (string) $product->get_meta('_display_pack_list', true),
    ];

    $existing_price_map = $parse_price_map((string) $product->get_meta('_capacity_price_map', true));
    $price_map = $existing_price_map;
    foreach ($resolved_map as $label => $price_value) {
        $label = trim((string) $label);
        $price_value = (float) $price_value;
        if ($label === '' || $price_value <= 0) {
            continue;
        }
        $price_map[$label] = $encode_display_price($price_value);
    }

    if (empty($price_map)) {
        $stats['skipped']++;
        continue;
    }

    if (function_exists('my_theme_compare_pack_labels')) {
        uksort($price_map, 'my_theme_compare_pack_labels');
    }

    $declared_labels = array_merge(
        $split_meta_labels((string) $product->get_meta('_display_capacity_list', true)),
        $split_meta_labels((string) $product->get_meta('_display_weight_list', true)),
        $split_meta_labels((string) $product->get_meta('_display_pack_list', true)),
        array_keys($existing_price_map),
        array_keys($price_map)
    );
    $declared_labels = array_values(array_unique(array_filter(array_map('trim', $declared_labels))));

    $map_parts = [];
    $capacity_labels = [];
    $weight_labels = [];
    $pack_labels = [];

    foreach ($price_map as $label => $price_value) {
        $map_parts[] = $label . ':' . $price_value;
        $type = $classify_label($label);
        if ($type === 'capacity') {
            $capacity_labels[] = $label;
        } elseif ($type === 'weight') {
            $weight_labels[] = $label;
        } else {
            $pack_labels[] = $label;
        }
    }

    foreach ($declared_labels as $label) {
        $type = $classify_label($label);
        if ($type === 'capacity') {
            $capacity_labels[] = $label;
        } elseif ($type === 'weight') {
            $weight_labels[] = $label;
        } else {
            $pack_labels[] = $label;
        }
    }

    $capacity_labels = array_values(array_unique($capacity_labels));
    $weight_labels = array_values(array_unique($weight_labels));
    $pack_labels = array_values(array_unique($pack_labels));

    $product->update_meta_data('_capacity_price_map', implode(' | ', $map_parts));

    if (!empty($capacity_labels)) {
        $product->update_meta_data('_display_capacity_list', implode(' | ', $sort_labels($capacity_labels, 'L')));
    } else {
        $product->delete_meta_data('_display_capacity_list');
    }

    if (!empty($weight_labels)) {
        $sorted_weights = $sort_labels($weight_labels, 'kg');
        $product->update_meta_data('_display_weight_list', implode(' | ', $sorted_weights));
        if (function_exists('my_theme_parse_pack_label')) {
            $first_weight = my_theme_parse_pack_label((string) $sorted_weights[0]);
            if (is_array($first_weight) && ($first_weight['unit'] ?? '') === 'kg') {
                $product->set_weight((string) $first_weight['value']);
            }
        }
    } else {
        $product->delete_meta_data('_display_weight_list');
    }

    if (!empty($pack_labels)) {
        $product->update_meta_data('_display_pack_list', implode(' | ', array_values(array_unique($pack_labels))));
    } else {
        $product->delete_meta_data('_display_pack_list');
    }

    $base_price = min(array_map('floatval', array_values($price_map)));
    $product->set_regular_price((string) $base_price);
    $product->set_price((string) $base_price);
    $product->set_sale_price('');
    $product->update_meta_data('_market_price_report_row', wp_json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    try {
        $product->save();
        $stats['updated']++;
    } catch (Throwable $throwable) {
        $stats['errors']++;
        fwrite($stderr, "Save failed for {$product_id}: {$throwable->getMessage()}\n");
    }
}

if (!empty($backup_rows)) {
    wp_mkdir_p(dirname($backup_path));
    file_put_contents($backup_path, wp_json_encode($backup_rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

fwrite($stdout, wp_json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);
