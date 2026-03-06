<?php
/**
 * Blog Single Page Builder — Elementor Pro Theme Builder
 *
 * Builds a blog post template using Elementor Pro theme widgets.
 * These widgets auto-pull from the current post — NO Voxel dynamic tags needed.
 *
 * Sections: Hero image, Title strip, Article body, Tags, Navigation, Author, Related, Comments
 *
 * IMPORTANT: Blog posts use Elementor Pro Theme Builder templates, NOT Voxel templates.
 * Use discover.php to find the active single-post template ID.
 *
 * Usage: wp eval-file build-blog.php --path=path/to/wordpress
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/config.php';

// ═══ CUSTOMIZE THESE ═══
$TEMPLATE_ID = 0;  // Elementor Pro single-post template ID from discover.php


// ═══════════════════════════════════
// SECTION 1 — HERO IMAGE
// ═══════════════════════════════════
// NOTE: Do NOT use position:absolute on this widget — causes placeholder.png bug
$hero_image = container(
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
        'background_background' => 'classic',
        'background_color' => '#0f172a',
        '__globals__' => ['background_color' => ''],
    ],
    [
        widget('theme-post-featured-image', [
            'image_size' => 'full',
            'custom_css' => "selector { width: 100%; max-height: 480px; overflow: hidden; }\n"
                . "selector img { width: 100% !important; height: 480px !important; "
                . "object-fit: cover !important; filter: brightness(0.7); }",
        ]),
    ]
);


// ═══════════════════════════════════
// SECTION 2 — TITLE STRIP
// ═══════════════════════════════════
$title_strip = container(
    [
        'content_width' => 'boxed',
        'boxed_width' => ['size' => 860, 'unit' => 'px', 'sizes' => []],
        'flex_direction' => 'column',
        'flex_gap' => ['size' => 14, 'unit' => 'px'],
        'padding' => [
            'top' => '36',
            'right' => '24',
            'bottom' => '36',
            'left' => '24',
            'unit' => 'px',
            'isLinked' => false
        ],
        'background_background' => 'classic',
        'background_color' => $P,
        '__globals__' => ['background_color' => ''],
        'margin' => [
            'top' => '-60',
            'right' => '0',
            'bottom' => '0',
            'left' => '0',
            'unit' => 'px',
            'isLinked' => false
        ],
        'border_radius' => [
            'top' => '16',
            'right' => '16',
            'bottom' => '0',
            'left' => '0',
            'unit' => 'px',
            'isLinked' => false
        ],
        'z_index' => 2,
        'custom_css' => "selector { position: relative; }",
    ],
    [
        // Category badge
        widget('post-info', [
            'layout' => 'inline',
            'list' => [
                [
                    '_id' => 'cat1',
                    'type' => 'terms',
                    'taxonomy' => 'category',
                    'text_prefix' => '',
                    'icon' => ['value' => '', 'library' => ''],
                    'show_icon' => ''
                ],
            ],
            'text_color' => $A,
            '__globals__' => ['text_color' => ''],
            'typography_typography' => 'custom',
            'typography_font_family' => $F,
            'typography_font_weight' => '700',
            'typography_font_size' => ['size' => 13, 'unit' => 'px'],
            'typography_text_transform' => 'uppercase',
            'typography_letter_spacing' => ['size' => 1, 'unit' => 'px'],
            'custom_css' => "selector .elementor-icon-list-text { background: rgba(48,190,224,0.15); padding: 4px 12px; border-radius: 6px; }",
        ]),
        // Title
        widget('theme-post-title', [
            'header_size' => 'h1',
            'title_color' => '#ffffff',
            '__globals__' => ['title_color' => ''],
            'typography_typography' => 'custom',
            'typography_font_family' => $F,
            'typography_font_weight' => '700',
            'typography_font_size' => ['size' => 36, 'unit' => 'px'],
            'typography_font_size_tablet' => ['size' => 28, 'unit' => 'px'],
            'typography_font_size_mobile' => ['size' => 22, 'unit' => 'px'],
        ]),
        // Date + Author + Reading time
        widget('post-info', [
            'layout' => 'inline',
            'list' => [
                [
                    '_id' => 'date1',
                    'type' => 'date',
                    'date_format' => 'custom',
                    'custom_date_format' => 'M j, Y',
                    'icon' => ['value' => 'far fa-calendar-alt', 'library' => 'fa-regular']
                ],
                [
                    '_id' => 'author1',
                    'type' => 'author',
                    'icon' => ['value' => 'far fa-user', 'library' => 'fa-regular']
                ],
                [
                    '_id' => 'time1',
                    'type' => 'time',
                    'icon' => ['value' => 'far fa-clock', 'library' => 'fa-regular']
                ],
            ],
            'text_color' => 'rgba(255,255,255,0.7)',
            '__globals__' => ['text_color' => ''],
            'icon_color' => $A,
            'typography_typography' => 'custom',
            'typography_font_family' => $F,
            'typography_font_size' => ['size' => 14, 'unit' => 'px'],
        ]),
    ]
);


// ═══════════════════════════════════
// SECTION 3 — ARTICLE BODY
// ═══════════════════════════════════
$article = container(
    [
        'content_width' => 'boxed',
        'boxed_width' => ['size' => 860, 'unit' => 'px', 'sizes' => []],
        'flex_direction' => 'column',
        'padding' => [
            'top' => '48',
            'right' => '24',
            'bottom' => '48',
            'left' => '24',
            'unit' => 'px',
            'isLinked' => false
        ],
        'background_background' => 'classic',
        'background_color' => $W,
        '__globals__' => ['background_color' => ''],
    ],
    [
        widget('theme-post-content', [
            'custom_css' => "
selector .elementor-widget-container { font-family: '$F', sans-serif; font-size: 17px; line-height: 1.8; color: #334155; }
selector h2, selector h3, selector h4 { font-family: '$F', sans-serif; color: $P; margin-top: 2em; margin-bottom: 0.8em; font-weight: 700; }
selector h2 { font-size: 28px; } selector h3 { font-size: 22px; }
selector p { margin-bottom: 1.4em; }
selector img { border-radius: 12px; margin: 24px 0; max-width: 100%; height: auto; }
selector blockquote { border-left: 4px solid $A; padding: 16px 24px; background: #f0f9ff; border-radius: 0 12px 12px 0; margin: 24px 0; font-style: italic; }
selector a { color: $A; text-decoration: none; font-weight: 600; border-bottom: 1px solid transparent; transition: border-color 0.2s; }
selector a:hover { border-bottom-color: $A; }
",
        ]),
        // Post navigation
        widget('post-navigation', [
            'show_label' => 'yes',
            'prev_label' => '← Previous',
            'next_label' => 'Next →',
            'custom_css' => "selector { padding: 24px 0; border-top: 1px solid #e2e8f0; }",
        ]),
    ]
);


// ═══════════════════════════════════
// SECTION 4 — AUTHOR + RELATED + COMMENTS
// ═══════════════════════════════════
$author = container(
    [
        'content_width' => 'boxed',
        'boxed_width' => ['size' => 860, 'unit' => 'px', 'sizes' => []],
        'padding' => [
            'top' => '0',
            'right' => '24',
            'bottom' => '48',
            'left' => '24',
            'unit' => 'px',
            'isLinked' => false
        ],
    ],
    [
        inner(
            [
                'content_width' => 'full',
                'padding' => [
                    'top' => '32',
                    'right' => '32',
                    'bottom' => '32',
                    'left' => '32',
                    'unit' => 'px',
                    'isLinked' => true
                ],
                'background_background' => 'classic',
                'background_color' => '#f8fafc',
                '__globals__' => ['background_color' => ''],
                'border_radius' => [
                    'top' => '16',
                    'right' => '16',
                    'bottom' => '16',
                    'left' => '16',
                    'unit' => 'px',
                    'isLinked' => true
                ],
            ],
            [
                widget('text-editor', [
                    'editor' => "<p style=\"font-family:$F,sans-serif;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;margin:0 0 12px 0;\">About the Author</p>",
                ]),
                widget('author-box', [
                    'show_name' => 'yes',
                    'show_biography' => 'yes',
                    'show_avatar' => 'yes',
                    'show_link' => 'yes',
                    'link_text' => 'View All Posts →',
                ]),
            ]
        ),
    ]
);

$related = boxed_section(
    [
        section_heading('You May Also Enjoy', 'fas fa-newspaper'),
        widget('ts-post-feed', array_merge([
            'ts_choose_post_type' => 'post',
            'ts_source' => 'search-filters',
            'ts_post_number' => 3,
            'ts_feed_column_no' => 3,
            'ts_feed_column_no_tablet' => 2,
            'ts_feed_column_no_mobile' => 1,
        ], empty_filter_lists())),
    ],
    $BG,
    1200
);

$comments = container(
    [
        'content_width' => 'boxed',
        'boxed_width' => ['size' => 860, 'unit' => 'px', 'sizes' => []],
        'padding' => [
            'top' => '48',
            'right' => '24',
            'bottom' => '80',
            'left' => '24',
            'unit' => 'px',
            'isLinked' => false
        ],
    ],
    [
        section_heading('Comments', 'fas fa-comments', 'h2', 24),
        widget('post-comments', [
            'custom_css' => "
selector .elementor-widget-container { font-family: '$F', sans-serif; }
selector .comment-form input, selector .comment-form textarea { border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; font-size: 14px; }
selector .comment-form input:focus, selector .comment-form textarea:focus { border-color: $A; outline: none; }
selector .comment-form .submit { background: $A; color: #fff; border: none; padding: 12px 28px; border-radius: 10px; font-weight: 700; cursor: pointer; }
",
        ]),
    ]
);


// ═══════════════════════════════════
// SAVE — Note: 'single-post' type for Elementor Pro
// ═══════════════════════════════════
save_template($TEMPLATE_ID, [$hero_image, $title_strip, $article, $author, $related, $comments], 'single-post');
