<?php
/**
 * Auto-Discovery Script for Voxel Sites
 *
 * Run this FIRST on any new Voxel project to discover:
 * - All registered post types and their template IDs
 * - All custom fields per post type with types
 * - All taxonomies
 * - Active header/footer template IDs
 * - Elementor Pro Theme Builder templates (blog overrides)
 *
 * Usage: wp eval-file discover.php --path=path/to/wordpress
 *
 * Output: Copy the generated config values into your config.php
 */

if (!function_exists('get_post_meta')) {
    echo "❌ Must be run via WP-CLI: wp eval-file discover.php --path=path/to/wordpress\n";
    exit(1);
}

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║        VOXEL SITE DISCOVERY — AUTO-CONFIGURATION        ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

// ────────────────────────────────────────────
// 1. VOXEL POST TYPES & TEMPLATES
// ────────────────────────────────────────────
echo "═══ 1. POST TYPES & TEMPLATE IDS ═══\n\n";

$pt_data = json_decode(get_option('voxel:post_types', '{}'), true);
$all_types = [];
$template_ids = [];

if (empty($pt_data)) {
    echo "⚠️  No Voxel post types found. Is Voxel Theme activated?\n\n";
} else {
    foreach ($pt_data as $slug => $config) {
        $all_types[] = $slug;
        $label = $config['settings']['singular'] ?? $config['settings']['plural'] ?? $slug;
        echo "📦 $slug ($label)\n";

        if (isset($config['templates'])) {
            $template_ids[$slug] = [];
            foreach ($config['templates'] as $tpl => $id) {
                echo "   $tpl => $id\n";
                $template_ids[$slug][$tpl] = $id;
            }
        }
        echo "\n";
    }
}

// Add WordPress built-in types
foreach (['post', 'page'] as $builtin) {
    if (!in_array($builtin, $all_types)) {
        $all_types[] = $builtin;
    }
}

echo "\n";

// ────────────────────────────────────────────
// 2. CUSTOM FIELDS PER POST TYPE
// ────────────────────────────────────────────
echo "═══ 2. CUSTOM FIELDS ═══\n\n";

foreach ($pt_data as $slug => $config) {
    $fields = $config['fields'] ?? [];
    if (empty($fields))
        continue;

    echo "📋 $slug (" . count($fields) . " fields)\n";
    echo str_repeat('─', 60) . "\n";

    foreach ($fields as $f) {
        $key = $f['key'] ?? '?';
        $type = $f['type'] ?? '?';
        $label = $f['label'] ?? $key;

        // Show the correct @post() syntax based on field type
        $tag_hint = "@post($key)";
        if ($type === 'image')
            $tag_hint .= "  |  @post($key.id) for ID";
        if ($type === 'post-relation')
            $tag_hint .= ".:title  |  .:url  |  .field-name";
        if (in_array($type, ['image-list', 'gallery']))
            $tag_hint = "@post($key.ids) for gallery";

        printf("   %-12s  %-25s  %s\n", $type, $key, $tag_hint);
    }
    echo "\n";
}

// ────────────────────────────────────────────
// 3. TAXONOMIES
// ────────────────────────────────────────────
echo "═══ 3. TAXONOMIES ═══\n\n";

$taxonomies = get_taxonomies(['public' => true], 'objects');
foreach ($taxonomies as $tax) {
    $count = wp_count_terms(['taxonomy' => $tax->name, 'hide_empty' => false]);
    if (is_wp_error($count))
        $count = 0;
    printf("   %-40s  %d terms\n", $tax->name, $count);
}
echo "\n";

// ────────────────────────────────────────────
// 4. ELEMENTOR PRO THEME BUILDER TEMPLATES
// ────────────────────────────────────────────
echo "═══ 4. ELEMENTOR PRO THEME BUILDER TEMPLATES ═══\n\n";

$theme_templates = get_posts([
    'post_type' => 'elementor_library',
    'meta_key' => '_elementor_template_type',
    'posts_per_page' => -1,
    'post_status' => 'publish',
]);

$theme_types = [];
foreach ($theme_templates as $t) {
    $type = get_post_meta($t->ID, '_elementor_template_type', true);
    if (in_array($type, ['header', 'footer', 'single', 'single-post', 'single-page', 'archive', 'search-results', 'error-404'])) {
        $theme_types[$type][] = $t;
        echo "   $type => ID $t->ID  ($t->post_title)\n";
    }
}
echo "\n";

// ────────────────────────────────────────────
// 5. SEARCH FILTERS (for ts-post-feed)
// ────────────────────────────────────────────
echo "═══ 5. SEARCH FILTERS PER POST TYPE ═══\n\n";

foreach ($pt_data as $slug => $config) {
    $filters = $config['search']['filters'] ?? [];
    if (empty($filters)) {
        echo "   ⚠️  $slug — NO search filters configured (feeds may show no results)\n";
    } else {
        echo "   ✅ $slug — " . count($filters) . " filters: ";
        $filter_names = array_map(function ($f) {
            return $f['key'] ?? '?'; }, $filters);
        echo implode(', ', $filter_names) . "\n";
    }
}
echo "\n";

// ────────────────────────────────────────────
// 6. GENERATE CONFIG SNIPPET
// ────────────────────────────────────────────
echo "═══ 6. COPY THIS INTO YOUR config.php ═══\n\n";

echo "// Template IDs (discovered " . date('Y-m-d H:i') . ")\n";
echo "\$TEMPLATE_IDS = [\n";
foreach ($template_ids as $slug => $templates) {
    $parts = [];
    foreach ($templates as $tpl => $id) {
        $parts[] = "'$tpl' => $id";
    }
    echo "    '$slug' => [" . implode(', ', $parts) . "],\n";
}
// Add theme builder templates
foreach ($theme_types as $type => $posts) {
    foreach ($posts as $t) {
        echo "    // '$type' => $t->ID,  // $t->post_title\n";
    }
}
echo "];\n\n";

echo "// All post types (for empty_filter_lists)\n";
echo "\$ALL_POST_TYPES = ['" . implode("', '", $all_types) . "'];\n\n";

echo "// Post type slugs\n";
$voxel_types = array_filter($all_types, function ($t) {
    return !in_array($t, ['post', 'page']); });
echo "\$POST_TYPES = ['" . implode("', '", $voxel_types) . "'];\n\n";

echo "✅ Discovery complete! Copy the above into your config.php\n";
