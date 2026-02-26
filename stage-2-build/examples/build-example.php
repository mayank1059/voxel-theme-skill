<?php
/**
 * Example Build Script — Product Single Page
 *
 * Demonstrates how to build a Voxel post type template with:
 * - Hero section with dynamic featured image
 * - Content section with dynamic fields
 * - Related posts feed
 *
 * Usage:
 *   wp eval-file examples/build-example.php --path=path/to/wordpress
 *
 * Requires: helpers.php in project root or adjust require_once path
 */

require_once __DIR__ . '/../scripts/helpers.php';

// ─── Config ───
$TEMPLATE_ID = 0000;  // Replace with your actual template ID
$FONT = 'Inter';

// ─── Design tokens ───
$PRIMARY = '#1a2a47';
$ACCENT = '#30bee0';
$BG_LIGHT = '#f5f8fa';
$WHITE = '#ffffff';
$TEXT = '#334155';
$MUTED = '#64748b';

// ─── Section 1: Hero ───
$hero = container(
    [
        'content_width' => 'boxed',
        'boxed_width' => ['size' => 1200, 'unit' => 'px', 'sizes' => []],
        'flex_direction' => 'row',
        'flex_gap' => ['size' => 32, 'unit' => 'px'],
        'padding' => [
            'top' => '40',
            'right' => '24',
            'bottom' => '40',
            'left' => '24',
            'unit' => 'px',
            'isLinked' => false
        ],
    ],
    [
        // Image column
        inner(
            ['width' => ['size' => 50, 'unit' => '%']],
            [
                widget('image', [
                    'image' => ['url' => field('hero-image'), 'id' => field('hero-image.id')],
                    'image_size' => 'large',
                    'custom_css' => "selector img { border-radius: 16px; }",
                ]),
            ]
        ),
        // Info column
        inner(
            [
                'width' => ['size' => 50, 'unit' => '%'],
                'flex_direction' => 'column',
                'flex_gap' => ['size' => 16, 'unit' => 'px'],
            ],
            [
                widget('heading', [
                    'title' => field(':title'),
                    'header_size' => 'h1',
                    'title_color' => $PRIMARY,
                    '__globals__' => ['title_color' => ''],
                    'typography_typography' => 'custom',
                    'typography_font_family' => $FONT,
                    'typography_font_weight' => '700',
                    'typography_font_size' => ['size' => 36, 'unit' => 'px'],
                ]),
                widget('text-editor', [
                    'editor' => field('short-description'),
                    'text_color' => $TEXT,
                    '__globals__' => ['text_color' => ''],
                    'typography_typography' => 'custom',
                    'typography_font_family' => $FONT,
                    'typography_font_size' => ['size' => 16, 'unit' => 'px'],
                ]),
            ]
        ),
    ]
);

// ─── Section 2: Related Posts Feed ───
$related = container(
    [
        'content_width' => 'boxed',
        'boxed_width' => ['size' => 1200, 'unit' => 'px', 'sizes' => []],
        'flex_direction' => 'column',
        'flex_gap' => ['size' => 24, 'unit' => 'px'],
        'padding' => [
            'top' => '60',
            'right' => '24',
            'bottom' => '80',
            'left' => '24',
            'unit' => 'px',
            'isLinked' => false
        ],
        'background_background' => 'classic',
        'background_color' => $BG_LIGHT,
        '__globals__' => ['background_color' => ''],
    ],
    [
        widget('heading', [
            'title' => 'Related Products',
            'header_size' => 'h2',
            'title_color' => $PRIMARY,
            '__globals__' => ['title_color' => ''],
            'typography_typography' => 'custom',
            'typography_font_family' => $FONT,
            'typography_font_weight' => '700',
            'typography_font_size' => ['size' => 28, 'unit' => 'px'],
        ]),
        widget('ts-post-feed', [
            'ts_choose_post_type' => 'products',
            'ts_source' => 'search-filters',
            'ts_post_number' => 3,
            'ts_feed_column_no' => 3,
            'ts_feed_column_no_tablet' => 2,
            'ts_feed_column_no_mobile' => 1,
            // REQUIRED: empty filter lists for all post types
            'ts_filter_list__products' => [],
            'ts_filter_list__manufacturers' => [],
            'ts_filter_list__post' => [],
            'ts_filter_list__page' => [],
            'ts_manual_posts' => [],
        ]),
    ]
);

// ─── Save ───
save_template($TEMPLATE_ID, [$hero, $related], 'page');
