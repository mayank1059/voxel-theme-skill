<?php
/**
 * Project Configuration — EDIT THIS FILE
 *
 * Design tokens and site-specific settings for your Voxel build scripts.
 * Copy this file to your project root and customize the values.
 *
 * Usage: require_once __DIR__ . '/config.php';
 */

// ═══════════════ PATHS ═══════════════
// Adjust these to match your WordPress installation
define('WP_PATH', __DIR__ . '/wordpress');       // Path to WordPress root

// ═══════════════ DESIGN TOKENS ═══════════════
// These are used across all build scripts for consistent styling.
// Change these to match your brand.

$TOKENS = [
    // Colors
    'primary' => '#1a2a47',    // Dark navy — headers, titles
    'accent' => '#30bee0',    // Teal — CTAs, highlights, links
    'bg_light' => '#f5f8fa',    // Light gray — section backgrounds
    'white' => '#ffffff',
    'text_dark' => '#1e293b',    // Body text
    'text_muted' => '#64748b',    // Secondary text, labels
    'border' => '#e2e8f0',    // Borders, dividers

    // Typography
    'font' => 'Space Grotesk',  // Primary font (must be loaded on site)

    // Spacing (px)
    'section_padding_y' => '60',
    'section_padding_x' => '24',
    'content_width' => 1200,     // Max content width in px
    'card_radius' => '16',     // Border radius for cards
    'button_radius' => '10',     // Border radius for buttons

    // Feed Defaults
    'feed_columns' => 4,
    'feed_columns_tablet' => 2,
    'feed_columns_mobile' => 1,
];

// ═══════════════ SHORTHAND ACCESSORS ═══════════════
// Use these in your build scripts: $P, $A, $F, etc.

$P = $TOKENS['primary'];
$A = $TOKENS['accent'];
$BG = $TOKENS['bg_light'];
$W = $TOKENS['white'];
$TD = $TOKENS['text_dark'];
$TM = $TOKENS['text_muted'];
$F = $TOKENS['font'];

// ═══════════════ POST TYPE SLUGS ═══════════════
// Update these after running discover.php
// Example: 'products', 'manufacturers', 'events', 'places'

$POST_TYPES = [
    // 'products',
    // 'manufacturers',
];

// ═══════════════ TEMPLATE IDS ═══════════════
// Populated by discover.php — update after running it
// Format: 'post_type' => ['single' => ID, 'card' => ID, 'archive' => ID]

$TEMPLATE_IDS = [
    // 'products' => ['single' => 0, 'card' => 0, 'archive' => 0],
    // 'manufacturers' => ['single' => 0, 'card' => 0, 'archive' => 0],
    // 'blog' => ['single' => 0],  // Elementor Pro single-post template
];

// ═══════════════ ALL REGISTERED POST TYPES ═══════════════
// Required for ts-post-feed empty filter arrays
// discover.php will output these — paste them here

$ALL_POST_TYPES = [
    // 'products', 'manufacturers', 'post', 'page', 'events', ...
];

/**
 * Generate empty filter list arrays for ts-post-feed widget
 * Prevents the "no results" bug (Pitfall #11)
 */
function empty_filter_lists()
{
    global $ALL_POST_TYPES;
    $filters = [];
    foreach ($ALL_POST_TYPES as $pt) {
        $filters["ts_filter_list__$pt"] = [];
    }
    $filters['ts_manual_posts'] = [];
    return $filters;
}

/**
 * Create a standard section heading with icon + title in a row
 */
function section_heading($title, $icon = 'fas fa-cubes', $tag = 'h2', $size = 28)
{
    global $P, $A, $W, $F;
    return inner(
        [
            'flex_direction' => 'row',
            'flex_align_items' => 'center',
            'flex_gap' => ['size' => 14, 'unit' => 'px'],
        ],
        [
            widget('icon', [
                'selected_icon' => ['value' => $icon, 'library' => 'fa-solid'],
                'view' => 'stacked',
                'shape' => 'circle',
                'primary_color' => $A,
                'secondary_color' => $W,
                'size' => ['size' => 18, 'unit' => 'px'],
                'icon_padding' => ['size' => 12, 'unit' => 'px'],
            ]),
            widget('heading', [
                'title' => $title,
                'header_size' => $tag,
                'title_color' => $P,
                '__globals__' => ['title_color' => ''],
                'typography_typography' => 'custom',
                'typography_font_family' => $F,
                'typography_font_weight' => '700',
                'typography_font_size' => ['size' => $size, 'unit' => 'px'],
            ]),
        ]
    );
}

/**
 * Create a styled CTA button (primary or outline)
 */
function cta_button($text, $url, $style = 'primary')
{
    global $A, $F;
    if ($style === 'primary') {
        $css = "display:inline-flex;align-items:center;gap:8px;font-family:$F,sans-serif;"
            . "font-size:15px;font-weight:700;color:#fff;padding:14px 28px;background:$A;"
            . "border-radius:10px;text-decoration:none;transition:all 0.2s;"
            . "box-shadow:0 4px 14px rgba(48,190,224,0.3);";
    } else {
        $css = "display:inline-flex;align-items:center;gap:8px;font-family:$F,sans-serif;"
            . "font-size:14px;font-weight:600;color:#475569;padding:14px 20px;"
            . "border:1.5px solid #e2e8f0;border-radius:10px;text-decoration:none;"
            . "transition:all 0.2s;";
    }
    return widget('text-editor', [
        'editor' => "<a href=\"$url\" style=\"$css\">$text</a>",
    ]);
}

/**
 * Create a boxed section container with standard padding
 */
function boxed_section($children, $bg = null, $width = null)
{
    global $TOKENS;
    $settings = [
        'content_width' => 'boxed',
        'boxed_width' => ['size' => $width ?? $TOKENS['content_width'], 'unit' => 'px', 'sizes' => []],
        'flex_direction' => 'column',
        'flex_gap' => ['size' => 24, 'unit' => 'px'],
        'padding' => [
            'top' => $TOKENS['section_padding_y'],
            'right' => $TOKENS['section_padding_x'],
            'bottom' => $TOKENS['section_padding_y'],
            'left' => $TOKENS['section_padding_x'],
            'unit' => 'px',
            'isLinked' => false,
        ],
    ];
    if ($bg) {
        $settings['background_background'] = 'classic';
        $settings['background_color'] = $bg;
        $settings['__globals__'] = ['background_color' => ''];
    }
    return container($settings, $children);
}
