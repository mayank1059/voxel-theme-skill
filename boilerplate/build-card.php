<?php
/**
 * Generic Card Template Builder — Voxel Post Type
 *
 * Builds a card template for feeds/grids. Cards are compact preview blocks.
 * Structure: Image → Title → Description (2-line) → Meta row → Label pills
 *
 * Usage: wp eval-file build-card.php --path=path/to/wordpress
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/config.php';

// ═══ CUSTOMIZE THESE ═══
$TEMPLATE_ID = 0;           // Card template ID from discover.php
$POST_TYPE = 'your-type';


// ═══════════════════════════════════
// CARD LAYOUT
// ═══════════════════════════════════

// The entire card is a clickable link
$card = container(
    [
        'content_width' => 'full',
        'html_tag' => 'a',
        'link' => ['url' => tag('@post(:url)')],
        'flex_direction' => 'column',
        'flex_gap' => ['size' => 0, 'unit' => 'px'],
        'padding' => [
            'top' => '0',
            'right' => '0',
            'bottom' => '0',
            'left' => '0',
            'unit' => 'px',
            'isLinked' => true
        ],
        'background_background' => 'classic',
        'background_color' => $W,
        '__globals__' => ['background_color' => ''],
        'border_radius' => [
            'top' => $TOKENS['card_radius'],
            'right' => $TOKENS['card_radius'],
            'bottom' => $TOKENS['card_radius'],
            'left' => $TOKENS['card_radius'],
            'unit' => 'px',
            'isLinked' => true
        ],
        'box_shadow_box_shadow_type' => 'yes',
        'box_shadow_box_shadow' => [
            'horizontal' => 0,
            'vertical' => 2,
            'blur' => 12,
            'spread' => 0,
            'color' => 'rgba(0,0,0,0.06)',
        ],
        'custom_css' => "selector { transition: all 0.25s ease; text-decoration: none; overflow: hidden; }\n"
            . "selector:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,0.1); }",
    ],
    [
        // ── Cover Image ──
        inner(
            [
                'content_width' => 'full',
                'padding' => [
                    'top' => '0',
                    'right' => '0',
                    'bottom' => '0',
                    'left' => '0',
                    'unit' => 'px',
                    'isLinked' => true
                ],
                'custom_css' => "selector { aspect-ratio: 16/10; overflow: hidden; }\n"
                    . "selector img { width: 100% !important; height: 100% !important; object-fit: cover !important; transition: transform 0.4s; }\n"
                    . "a:hover selector img { transform: scale(1.05); }",
            ],
            [
                widget('image', [
                    'image' => ['url' => field('hero-image'), 'id' => field('hero-image.id')],  // ← CUSTOMIZE
                    'image_size' => 'medium_large',
                ]),
            ]
        ),

        // ── Card Body ──
        inner(
            [
                'content_width' => 'full',
                'flex_direction' => 'column',
                'flex_gap' => ['size' => 8, 'unit' => 'px'],
                'padding' => [
                    'top' => '16',
                    'right' => '16',
                    'bottom' => '16',
                    'left' => '16',
                    'unit' => 'px',
                    'isLinked' => true
                ],
            ],
            [
                // Title
                widget('heading', [
                    'title' => field(':title'),
                    'header_size' => 'h3',
                    'title_color' => $P,
                    '__globals__' => ['title_color' => ''],
                    'typography_typography' => 'custom',
                    'typography_font_family' => $F,
                    'typography_font_weight' => '700',
                    'typography_font_size' => ['size' => 16, 'unit' => 'px'],
                    'typography_line_height' => ['size' => 1.3, 'unit' => 'em'],
                ]),

                // Description (2-line clamp)
                widget('text-editor', [
                    'editor' => field('short-description'),  // ← CUSTOMIZE
                    'text_color' => $TM,
                    '__globals__' => ['text_color' => ''],
                    'typography_typography' => 'custom',
                    'typography_font_family' => $F,
                    'typography_font_size' => ['size' => 13, 'unit' => 'px'],
                    'typography_line_height' => ['size' => 1.5, 'unit' => 'em'],
                    'custom_css' => "selector .elementor-widget-container { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }",
                ]),

                // Meta row (customize with your fields)
                widget('text-editor', [
                    'editor' => tag(
                        '<div style="display:flex;gap:12px;align-items:center;margin-top:4px;font-family:' . $F . ',sans-serif;font-size:12px;color:#94a3b8;">'
                        . '<span>@post(field-1)</span>'   // ← CUSTOMIZE
                        . '<span>·</span>'
                        . '<span>@post(field-2)</span>'   // ← CUSTOMIZE
                        . '</div>'
                    ),
                ]),

                // Label pills (customize)
                widget('text-editor', [
                    'editor' => tag(
                        '<div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px;">'
                        . '<span style="font-family:' . $F . ',sans-serif;font-size:11px;font-weight:600;color:#475569;background:#f1f5f9;padding:3px 8px;border-radius:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px;">@post(taxonomy-field)</span>'  // ← CUSTOMIZE
                        . '</div>'
                    ),
                ]),
            ]
        ),
    ]
);


// ═══════════════════════════════════
// SAVE
// ═══════════════════════════════════
save_template($TEMPLATE_ID, [$card], 'page');
