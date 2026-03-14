<?php
/**
 * Ensure WooCommerce brand attribute taxonomy (pa_brand) exists and sync products into it.
 *
 * Run:
 *   Get-Content -Raw tools/sync_pa_brand_taxonomy.php | docker exec -i lephat1898-wordpress-1 php
 */

require '/var/www/html/wp-load.php';

if (!function_exists('wc_get_product') || !function_exists('wc_attribute_taxonomy_id_by_name')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

if (!function_exists('wc_create_attribute')) {
    fwrite(STDERR, "Missing wc_create_attribute.\n");
    exit(1);
}

$attribute_slug = 'brand';
$taxonomy = 'pa_' . $attribute_slug;
$attribute_id = (int) wc_attribute_taxonomy_id_by_name($attribute_slug);
if ($attribute_id <= 0) {
    $created = wc_create_attribute([
        'name' => 'Brand',
        'slug' => $attribute_slug,
        'type' => 'select',
        'order_by' => 'menu_order',
        'has_archives' => true,
    ]);
    if (is_wp_error($created)) {
        fwrite(STDERR, "Cannot create brand attribute: " . $created->get_error_message() . "\n");
        exit(1);
    }
    delete_transient('wc_attribute_taxonomies');
    $attribute_id = (int) wc_attribute_taxonomy_id_by_name($attribute_slug);
}

if (!taxonomy_exists($taxonomy)) {
    register_taxonomy(
        $taxonomy,
        ['product'],
        [
            'hierarchical' => false,
            'show_ui' => false,
            'query_var' => true,
            'rewrite' => false,
        ]
    );
}

$brand_map = function_exists('my_theme_get_brand_keyword_map')
    ? my_theme_get_brand_keyword_map()
    : [
        'dulux' => ['label' => 'Dulux'],
        'maxilite' => ['label' => 'Maxilite'],
        'weber' => ['label' => 'Weber'],
        'jotun' => ['label' => 'Jotun'],
        'nippon' => ['label' => 'Nippon'],
        'kova' => ['label' => 'Kova'],
        'toa' => ['label' => 'TOA'],
        'sika' => ['label' => 'Sika'],
        'apollo' => ['label' => 'Apollo'],
    ];

$ensure_brand_term = function ($slug, $label) use ($taxonomy) {
    $slug = sanitize_title((string) $slug);
    $label = trim((string) $label);
    if ($slug === '') {
        return 0;
    }
    if ($label === '') {
        $label = ucfirst($slug);
    }

    $term = get_term_by('slug', $slug, $taxonomy);
    if ($term instanceof WP_Term) {
        return (int) $term->term_id;
    }

    $res = wp_insert_term($label, $taxonomy, ['slug' => $slug]);
    if (is_wp_error($res)) {
        return 0;
    }
    return isset($res['term_id']) ? (int) $res['term_id'] : 0;
};

$product_ids = get_posts([
    'post_type' => 'product',
    'post_status' => ['publish', 'draft', 'pending', 'private'],
    'posts_per_page' => -1,
    'fields' => 'ids',
    'no_found_rows' => true,
]);

$stats = ['processed' => 0, 'assigned' => 0, 'skipped' => 0, 'errors' => 0];

foreach ($product_ids as $product_id) {
    $stats['processed']++;
    $product = wc_get_product((int) $product_id);
    if (!$product instanceof WC_Product) {
        $stats['errors']++;
        continue;
    }

    $brand_slug = function_exists('my_theme_get_product_brand_slug')
        ? my_theme_get_product_brand_slug($product)
        : '';
    $brand_slug = sanitize_title((string) $brand_slug);
    if ($brand_slug === '') {
        $stats['skipped']++;
        continue;
    }

    $brand_label = isset($brand_map[$brand_slug]['label']) ? (string) $brand_map[$brand_slug]['label'] : ucfirst($brand_slug);
    $term_id = $ensure_brand_term($brand_slug, $brand_label);
    if ($term_id <= 0) {
        $stats['errors']++;
        continue;
    }

    $set = wp_set_object_terms((int) $product_id, [$term_id], $taxonomy, false);
    if (is_wp_error($set)) {
        $stats['errors']++;
        continue;
    }

    $attributes = $product->get_attributes();
    $existing = isset($attributes[$taxonomy]) ? $attributes[$taxonomy] : null;
    $need_save = true;
    if ($existing instanceof WC_Product_Attribute) {
        $opts = array_map('intval', (array) $existing->get_options());
        if (count($opts) === 1 && (int) $opts[0] === (int) $term_id) {
            $need_save = false;
        }
    }

    if ($need_save) {
        $attr = new WC_Product_Attribute();
        $attr->set_id($attribute_id);
        $attr->set_name($taxonomy);
        $attr->set_options([(int) $term_id]);
        $attr->set_position(0);
        $attr->set_visible(false);
        $attr->set_variation(false);
        $attributes[$taxonomy] = $attr;
        $product->set_attributes($attributes);
        $product->save();
    }

    $stats['assigned']++;
}

if (function_exists('my_theme_flush_product_cache_fragments')) {
    my_theme_flush_product_cache_fragments(0);
}
update_option('my_theme_filter_cache_version', (string) time(), false);

echo 'sync_pa_brand_done processed=' . $stats['processed'] .
    ' assigned=' . $stats['assigned'] .
    ' skipped=' . $stats['skipped'] .
    ' errors=' . $stats['errors'] . PHP_EOL;
