<?php
/**
 * Generic Archive Page Builder — Voxel Post Type
 *
 * Builds a listing/search page with:
 *   - Hero banner with title + search form
 *   - Post feed grid
 *
 * Usage: wp eval-file build-archive.php --path=path/to/wordpress
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/config.php';

// ═══ CUSTOMIZE THESE ═══
$TEMPLATE_ID = 0;           // Archive template ID from discover.php
$POST_TYPE = 'your-type';
$PAGE_TITLE = 'Browse Items';  // Main heading
$PAGE_DESC = 'Explore our collection of items.';


// ═══════════════════════════════════
// SECTION 1 — HERO BANNER + SEARCH
// ═══════════════════════════════════
$hero = container(
    [
        'content_width' => 'boxed',
        'boxed_width' => ['size' => $TOKENS['content_width'], 'unit' => 'px', 'sizes' => []],
        'flex_direction' => 'column',
        'flex_gap' => ['size' => 24, 'unit' => 'px'],
        'flex_align_items' => 'center',
        'padding' => [
            'top' => '80',
            'right' => $TOKENS['section_padding_x'],
            'bottom' => '48',
            'left' => $TOKENS['section_padding_x'],
            'unit' => 'px',
            'isLinked' => false
        ],
        'background_background' => 'classic',
        'background_color' => $P,
        '__globals__' => ['background_color' => ''],
    ],
    [
        // Title
        widget('heading', [
            'title' => $PAGE_TITLE,
            'header_size' => 'h1',
            'align' => 'center',
            'title_color' => $W,
            '__globals__' => ['title_color' => ''],
            'typography_typography' => 'custom',
            'typography_font_family' => $F,
            'typography_font_weight' => '700',
            'typography_font_size' => ['size' => 42, 'unit' => 'px'],
            'typography_font_size_tablet' => ['size' => 32, 'unit' => 'px'],
            'typography_font_size_mobile' => ['size' => 24, 'unit' => 'px'],
        ]),
        // Subtitle
        widget('text-editor', [
            'editor' => "<p style=\"text-align:center;font-family:$F,sans-serif;font-size:17px;color:rgba(255,255,255,0.75);max-width:600px;line-height:1.6;\">$PAGE_DESC</p>",
        ]),
        // Search form
        widget('ts-search-form', [
            'ts_choose_post_types' => [$POST_TYPE],
            'ts_on_submit' => 'filter_results',
            // CUSTOMIZE: Add your filters here
            // 'ts_filter_list__' . $POST_TYPE => [
            //     ['_id' => 'fkw', 'ts_choose_filter' => 'keywords'],
            //     ['_id' => 'fcat', 'ts_choose_filter' => 'category_taxonomy'],
            // ],
        ]),
    ]
);


// ═══════════════════════════════════
// SECTION 2 — POST FEED GRID
// ═══════════════════════════════════
$feed = boxed_section(
    [
        widget('ts-post-feed', array_merge([
            'ts_choose_post_type' => $POST_TYPE,
            'ts_source' => 'search-filters',
            'ts_post_number' => 12,
            'ts_feed_column_no' => $TOKENS['feed_columns'],
            'ts_feed_column_no_tablet' => $TOKENS['feed_columns_tablet'],
            'ts_feed_column_no_mobile' => $TOKENS['feed_columns_mobile'],
            'ts_noresults_text' => 'No results found — try adjusting your filters.',
        ], empty_filter_lists())),
    ],
    $BG
);


// ═══════════════════════════════════
// SAVE
// ═══════════════════════════════════
save_template($TEMPLATE_ID, [$hero, $feed], 'page');
