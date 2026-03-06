<?php
/**
 * Generic Single Page Builder — Voxel Post Type
 *
 * Builds a single-page template for ANY Voxel custom post type.
 * Sections: Hero (image + title), Content, Specs Table, Related Posts, CTA
 *
 * Customize by:
 *   1. Setting TEMPLATE_ID and POST_TYPE below
 *   2. Updating field keys in each section to match your post type's fields
 *   3. Adjusting design tokens in config.php
 *
 * Usage: wp eval-file build-single.php --path=path/to/wordpress
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/config.php';

// ═══ CUSTOMIZE THESE ═══
$TEMPLATE_ID = 0;           // From discover.php
$POST_TYPE = 'your-type'; // Your post type slug


// ═══════════════════════════════════
// SECTION 1 — HERO (Image + Info)
// ═══════════════════════════════════
$hero = container(
    [
        'content_width' => 'boxed',
        'boxed_width' => ['size' => $TOKENS['content_width'], 'unit' => 'px', 'sizes' => []],
        'flex_direction' => 'row',
        'flex_gap' => ['size' => 32, 'unit' => 'px'],
        'padding' => [
            'top' => '40',
            'right' => $TOKENS['section_padding_x'],
            'bottom' => '40',
            'left' => $TOKENS['section_padding_x'],
            'unit' => 'px',
            'isLinked' => false
        ],
    ],
    [
        // Image column (50%)
        inner(
            ['width' => ['size' => 50, 'unit' => '%']],
            [
                widget('image', [
                    'image' => ['url' => field('hero-image'), 'id' => field('hero-image.id')],
                    'image_size' => 'large',
                    'custom_css' => "selector img { border-radius: {$TOKENS['card_radius']}px; }",
                ]),
            ]
        ),
        // Info column (50%)
        inner(
            [
                'width' => ['size' => 50, 'unit' => '%'],
                'flex_direction' => 'column',
                'flex_gap' => ['size' => 16, 'unit' => 'px'],
            ],
            [
                // Title
                widget('heading', [
                    'title' => field(':title'),
                    'header_size' => 'h1',
                    'title_color' => $P,
                    '__globals__' => ['title_color' => ''],
                    'typography_typography' => 'custom',
                    'typography_font_family' => $F,
                    'typography_font_weight' => '700',
                    'typography_font_size' => ['size' => 36, 'unit' => 'px'],
                    'typography_font_size_tablet' => ['size' => 28, 'unit' => 'px'],
                    'typography_font_size_mobile' => ['size' => 22, 'unit' => 'px'],
                ]),
                // Description
                widget('text-editor', [
                    'editor' => field('short-description'),  // ← CUSTOMIZE field key
                    'text_color' => $TD,
                    '__globals__' => ['text_color' => ''],
                    'typography_typography' => 'custom',
                    'typography_font_family' => $F,
                    'typography_font_size' => ['size' => 16, 'unit' => 'px'],
                    'typography_line_height' => ['size' => 1.7, 'unit' => 'em'],
                ]),
                // CTA button
                cta_button('Visit Website →', tag('@post(website)'), 'primary'),  // ← CUSTOMIZE
            ]
        ),
    ]
);


// ═══════════════════════════════════
// SECTION 2 — DETAILS / SPECS TABLE
// ═══════════════════════════════════
$details = boxed_section(
    [
        section_heading('Details', 'fas fa-list-alt'),
        widget('text-editor', [
            // CUSTOMIZE: Replace with your post type's fields
            'editor' => tag(
                '<table style="width:100%;border-collapse:collapse;font-family:' . $F . ',sans-serif;">'
                . '<tr style="border-bottom:1px solid #e2e8f0;"><td style="padding:12px 0;color:#64748b;width:35%;">Field 1</td><td style="padding:12px 0;color:#1e293b;font-weight:600;">@post(field-key-1)</td></tr>'
                . '<tr style="border-bottom:1px solid #e2e8f0;"><td style="padding:12px 0;color:#64748b;">Field 2</td><td style="padding:12px 0;color:#1e293b;font-weight:600;">@post(field-key-2)</td></tr>'
                . '<tr style="border-bottom:1px solid #e2e8f0;"><td style="padding:12px 0;color:#64748b;">Field 3</td><td style="padding:12px 0;color:#1e293b;font-weight:600;">@post(field-key-3)</td></tr>'
                . '</table>'
            ),
        ]),
    ],
    $W
);


// ═══════════════════════════════════
// SECTION 3 — GALLERY (if applicable)
// ═══════════════════════════════════
// Uncomment if your post type has a gallery/image-list field
/*
$gallery = boxed_section(
    [
        section_heading('Gallery', 'fas fa-images'),
        widget('ts-gallery', [
            'ts_gallery_images' => [
                ['id' => tag('@post(gallery.ids)'), 'url' => ' '],  // ← CUSTOMIZE field key
            ],
            'ts_visible_count' => 8,
            'ts_display_size' => 'medium_large',
            'custom_css' => "
selector .ts-gallery-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
selector .ts-gallery-grid li { overflow: hidden; border-radius: 12px; aspect-ratio: 4/3; }
selector .ts-gallery-grid li img { width: 100% !important; height: 100% !important; object-fit: cover !important; }
selector .ts-gallery-grid li:first-child { grid-column: span 2; grid-row: span 2; }
",
        ]),
    ],
    $BG
);
*/


// ═══════════════════════════════════
// SECTION 4 — RELATED POSTS FEED
// ═══════════════════════════════════
$related = boxed_section(
    [
        section_heading('Related', 'fas fa-th-large'),
        widget('ts-post-feed', array_merge([
            'ts_choose_post_type' => $POST_TYPE,  // ← Same post type for "related"
            'ts_source' => 'search-filters',
            'ts_post_number' => $TOKENS['feed_columns'],
            'ts_feed_column_no' => $TOKENS['feed_columns'],
            'ts_feed_column_no_tablet' => $TOKENS['feed_columns_tablet'],
            'ts_feed_column_no_mobile' => $TOKENS['feed_columns_mobile'],
        ], empty_filter_lists())),
    ],
    $BG
);


// ═══════════════════════════════════
// SAVE
// ═══════════════════════════════════
$elements = [$hero, $details, $related];
// Uncomment and add $gallery to $elements if using gallery

save_template($TEMPLATE_ID, $elements, 'page');
