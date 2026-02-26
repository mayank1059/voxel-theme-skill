<?php
/**
 * Elementor JSON Helper Functions
 * 
 * Copy this file into your project alongside your build scripts.
 * Provides container(), inner(), and widget() functions for building
 * Elementor template JSON programmatically.
 *
 * Usage:
 *   require_once __DIR__ . '/helpers.php';
 *   $section = container(['content_width' => 'boxed'], [
 *       widget('heading', ['title' => 'Hello World']),
 *   ]);
 */

/**
 * Generate a random 7-char Elementor element ID
 */
function eid()
{
    return substr(md5(uniqid(mt_rand(), true)), 0, 7);
}

/**
 * Create a top-level container (section)
 *
 * @param array $settings  Elementor settings (padding, background, flex, etc.)
 * @param array $children  Child elements (inner containers or widgets)
 * @param array $extra     Extra keys to merge (e.g., conditions)
 * @return array
 */
function container($settings = [], $children = [], $extra = [])
{
    return array_merge([
        'id' => eid(),
        'elType' => 'container',
        'settings' => $settings,
        'elements' => $children,
        'isInner' => false,
    ], $extra);
}

/**
 * Create an inner (nested) container
 *
 * @param array $settings  Elementor settings
 * @param array $children  Child widgets or deeper containers
 * @return array
 */
function inner($settings = [], $children = [])
{
    return [
        'id' => eid(),
        'elType' => 'container',
        'settings' => $settings,
        'elements' => $children,
        'isInner' => true,
    ];
}

/**
 * Create a widget element
 *
 * @param string $type     Widget type (e.g., 'heading', 'text-editor', 'ts-post-feed')
 * @param array  $settings Widget settings
 * @return array
 */
function widget($type, $settings = [])
{
    return [
        'id' => eid(),
        'elType' => 'widget',
        'settings' => $settings,
        'elements' => [],
        'widgetType' => $type,
    ];
}

/**
 * Voxel dynamic tag helper — wraps expression in @tags()...@endtags()
 *
 * @param string $expr  e.g., '@post(:title)' or '@post(field-key)'
 * @return string
 */
function tag($expr)
{
    return '@tags()' . $expr . '@endtags()';
}

/**
 * Shorthand for a single Voxel field tag
 *
 * @param string $field  e.g., ':title', 'logo.id', 'year-founded'
 * @return string
 */
function field($field)
{
    return tag("@post($field)");
}

/**
 * Save Elementor template data to the database
 *
 * @param int    $template_id    Post ID of the template
 * @param array  $elements       Array of top-level container elements
 * @param string $template_type  'page' for Voxel, 'single-post' for Elementor Pro
 */
function save_template($template_id, $elements, $template_type = 'page')
{
    $json = json_encode($elements, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // wp_slash() is CRITICAL — prevents backslash stripping
    update_post_meta($template_id, '_elementor_data', wp_slash($json));
    update_post_meta($template_id, '_elementor_edit_mode', 'builder');
    update_post_meta($template_id, '_elementor_version', '3.35.2');
    update_post_meta($template_id, '_elementor_template_type', $template_type);

    // Touch the post to invalidate caches
    wp_update_post([
        'ID' => $template_id,
        'post_content' => '',
        'post_modified' => current_time('mysql'),
        'post_modified_gmt' => current_time('mysql', true),
    ]);

    // Clear all caches
    delete_post_meta($template_id, '_elementor_css');
    delete_post_meta($template_id, '_elementor_controls_usage');
    clean_post_cache($template_id);

    // Delete old revisions
    $revisions = wp_get_post_revisions($template_id);
    foreach ($revisions as $rev) {
        wp_delete_post_revision($rev->ID);
    }

    // Regenerate CSS
    if (class_exists('\Elementor\Plugin')) {
        \Elementor\Plugin::$instance->files_manager->clear_cache();
        if (class_exists('\Elementor\Core\Files\CSS\Post')) {
            $css = \Elementor\Core\Files\CSS\Post::create($template_id);
            $css->update();
        }
    }

    echo "✅ Template $template_id saved (" . strlen($json) . " bytes, " . count($elements) . " sections)\n";
}
