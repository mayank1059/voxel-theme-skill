---
name: stage-2-build
description: Build Voxel + Elementor templates programmatically using PHP scripts and WP-CLI. Covers dynamic tags, JSON structure, all widget types, layout patterns, images, post relations, feeds, galleries, buttons, and all known pitfalls.
---

# Stage 2: Build Templates

**Purpose:** Write PHP build scripts that generate Elementor template JSON and save it to the database via WP-CLI.

---

## Step 0: Post Type & Field Setup (If Needed)

Before building templates, ensure your Voxel post types, fields, taxonomies, and search filters are configured. Create a `setup-post-types.php` script.

### Creating Taxonomies

```php
$taxonomies = [
    'lesson_activity' => [
        'singular' => 'Activity',
        'plural'   => 'Activities',
        'terms'    => ['Surf', 'Kitesurf', 'Wingfoil'],
    ],
];

// Register in Voxel
$voxel_taxonomies = json_decode(get_option('voxel:taxonomies', '{}'), true) ?: [];
foreach ($taxonomies as $slug => $tax) {
    $voxel_taxonomies[$slug] = [
        'settings' => [
            'key' => $slug, 'singular' => $tax['singular'],
            'plural' => $tax['plural'], 'post_type' => ['lessons'],
        ],
    ];
}
update_option('voxel:taxonomies', wp_json_encode($voxel_taxonomies));

// Register with WordPress + create terms
foreach ($taxonomies as $slug => $tax) {
    if (!taxonomy_exists($slug)) {
        register_taxonomy($slug, ['lessons'], [
            'public' => true, 'hierarchical' => true, 'show_in_rest' => true,
        ]);
    }
    foreach ($tax['terms'] as $term_name) {
        if (!term_exists($term_name, $slug)) {
            wp_insert_term($term_name, $slug);
        }
    }
}
```

### Configuring Post Type Fields

```php
$fields = [
    // UI steps organize the submission form
    ['type' => 'ui-step', 'key' => 'step-basic', 'label' => 'Basic Info'],
    ['type' => 'title', 'key' => 'title', 'label' => 'Name'],
    ['type' => 'description', 'key' => 'description', 'label' => 'Description'],
    ['type' => 'image', 'key' => 'cover-image', 'label' => 'Cover Photo', 'max-count' => 1],
    ['type' => 'image', 'key' => 'gallery', 'label' => 'Gallery', 'max-count' => 10],
    ['type' => 'taxonomy', 'key' => 'activity', 'label' => 'Activity', 'taxonomy' => 'lesson_activity'],
    ['type' => 'number', 'key' => 'price', 'label' => 'Price', 'min' => 0],
    ['type' => 'location', 'key' => 'location', 'label' => 'Location'],
    ['type' => 'switcher', 'key' => 'pickup-service', 'label' => 'Pickup Available'],
    ['type' => 'select', 'key' => 'lesson-type', 'label' => 'Type', 'choices' => [
        ['value' => 'group', 'label' => 'Group'],
        ['value' => 'private', 'label' => 'Private'],
    ]],
    // Repeater for structured data
    ['type' => 'repeater', 'key' => 'lesson-structure', 'label' => 'Structure', 'fields' => [
        ['type' => 'text', 'key' => 'heading', 'label' => 'Step Title'],
        ['type' => 'text', 'key' => 'content', 'label' => 'Description'],
        ['type' => 'text', 'key' => 'minutes', 'label' => 'Duration'],
    ]],
    ['type' => 'post-relation', 'key' => 'school', 'label' => 'School',
     'post_types' => ['schools'], 'relation_type' => 'has_one'],
];

// Apply via Voxel API
$pt = \Voxel\Post_Type::get('lessons');
$pt->repository->set_config([
    'settings' => ['key' => 'lessons', 'singular' => 'Lesson', 'plural' => 'Lessons'],
    'fields' => $fields,
    'search' => $search_config,
]);
```

### Search Filters & Ordering

```php
$search = [
    'filters' => [
        ['type' => 'keywords', 'key' => 'keywords', 'label' => 'Search'],
        ['type' => 'terms', 'key' => 'activity', 'label' => 'Activity', 'source' => 'activity'],
        ['type' => 'location', 'key' => 'location', 'label' => 'Location', 'source' => 'location'],
        ['type' => 'range', 'key' => 'price', 'label' => 'Price', 'source' => 'price'],
        ['type' => 'order-by', 'key' => 'order-by', 'label' => 'Sort By'],
    ],
    'order' => [
        ['key' => 'latest', 'label' => 'Latest',
         'clauses' => [['type' => 'date-created', 'order' => 'desc']]],
        ['key' => 'price-low', 'label' => 'Price: Low to High',
         'clauses' => [['type' => 'number-field', 'source' => 'price', 'order' => 'asc']]],
    ],
];
```

### Creating Demo Content

```php
// Sideload images from local files
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

function sideload_local_image($path, $title) {
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    $tmp = wp_tempnam($title . '.' . $ext);
    copy($path, $tmp);
    $id = media_handle_sideload(['name' => sanitize_file_name($title . '.' . $ext), 'tmp_name' => $tmp], 0, $title);
    return is_wp_error($id) ? 0 : $id;
}

// Create post with Voxel fields
$post_id = wp_insert_post([
    'post_title' => 'My Lesson', 'post_content' => 'Description...',
    'post_status' => 'publish', 'post_type' => 'lessons',
]);
update_post_meta($post_id, 'price', 120);
update_post_meta($post_id, 'duration', 90);
update_post_meta($post_id, 'cover-image', $image_id);
set_post_thumbnail($post_id, $image_id);
// Location (JSON encoded)
update_post_meta($post_id, 'location', wp_json_encode([
    'address' => 'Tarifa, Spain', 'latitude' => 36.01, 'longitude' => -5.60,
]));
// Repeater data (JSON array, wp_slash'd)
update_post_meta($post_id, 'lesson-structure',
    wp_slash(wp_json_encode([
        ['heading' => 'Step 1', 'content' => 'Details...', 'minutes' => '20'],
    ]))
);
// Gallery (comma-separated IDs)
update_post_meta($post_id, 'gallery', '85,86,87');
// Taxonomy terms
wp_set_object_terms($post_id, $term_id, 'lesson_activity');
```

---

## Step 1: Project Setup

Every build script starts with:
```php
<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/config.php'; // Optional — design tokens
$TEMPLATE_ID = 16; // From Stage 1 research
```

---

## Step 2: Dynamic Tags — THE #1 RULE

### Voxel does NOT use Elementor's `__dynamic__` system

Using `__dynamic__` causes PHP warnings and broken rendering. Voxel has its own syntax.

### Syntax

```
@tags()@post(:title)@endtags()              → Post title
@tags()@post(:content)@endtags()            → Post content
@tags()@post(:url)@endtags()                → Post permalink
@tags()@post(field-key)@endtags()           → Custom field value
@tags()@post(field-key.id)@endtags()        → Image attachment ID
@tags()@post(field-key.ids)@endtags()       → Gallery comma-separated IDs
@tags()@post(field-key.label)@endtags()     → Select/taxonomy display label
@tags()@post(field-key.address)@endtags()   → Location address
@tags()@post(field-key.:title)@endtags()    → Post-relation title
@tags()@post(field-key.:url)@endtags()      → Post-relation URL
@site(post_types.slug.archive_link)         → Archive URL
```

### PHP Helpers (in helpers.php)

```php
tag('@post(:title)')     → '@tags()@post(:title)@endtags()'
field(':title')          → '@tags()@post(:title)@endtags()'
field('logo.id')         → '@tags()@post(logo.id)@endtags()'
```

### ❌ NEVER DO THIS
```php
'__dynamic__' => ['title' => "voxel:post-field|key=title"]  // BREAKS EVERYTHING
```

---

## Step 3: Build Sections

### Container Hierarchy

```
container() — top-level section (isInner=false)
  └─ inner() — nested layout container (isInner=true)
       └─ widget() — leaf element (heading, image, text-editor, etc.)
```

### Basic Section

```php
$section = container(
    [
        'content_width' => 'boxed',
        'boxed_width' => ['size' => 1200, 'unit' => 'px'],
        'padding' => ['top' => '60', 'right' => '24', 'bottom' => '60', 'left' => '24',
                      'unit' => 'px', 'isLinked' => false],
        'background_background' => 'classic',
        'background_color' => '#f5f8fa',
        '__globals__' => ['background_color' => ''],
    ],
    [
        widget('heading', [
            'title' => field(':title'),
            'header_size' => 'h1',
            'title_color' => '#1a2a47',
            '__globals__' => ['title_color' => ''],
            'typography_typography' => 'custom',
            'typography_font_family' => 'Montserrat',
            'typography_font_weight' => '700',
            'typography_font_size' => ['size' => 36, 'unit' => 'px'],
        ]),
    ]
);
```

### Multi-Column Layout

**CRITICAL:** `flex_direction => 'row'` alone does NOT work. Each child needs explicit `width`.

```php
inner(
    [
        'flex_direction' => 'row',
        'flex_gap' => ['size' => 24, 'unit' => 'px'],
        'flex_wrap' => 'wrap',
    ],
    [
        inner(['width' => ['size' => 48, 'unit' => '%']], [...]),  // Left
        inner(['width' => ['size' => 48, 'unit' => '%']], [...]),  // Right
    ]
);
```

Column widths: 2-col=48%, 3-col=30%, 4-col=23%.

Alternative — **use custom_css for precise control:**
```php
// 65/35 content + sidebar layout
$left = inner([
    'custom_css' => 'selector { flex: 1 1 65%; min-width: 500px; }',
], [...]);
$right = inner([
    'custom_css' => 'selector { width: 350px; flex-shrink: 0; }',
], [...]);
```

---

## Step 4: Widget Patterns

### Image Widget (Dynamic)

**Both `url` AND `id` must be set:**
```php
widget('image', [
    'image' => ['url' => field('cover-image'), 'id' => field('cover-image.id')],
    'image_size' => 'large',
]);
```

For images as part of card templates with fixed height:
```php
widget('image', [
    'image' => ['url' => field('cover-image.url'), 'id' => field('cover-image.id')],
    'image_size' => 'medium_large',
    'width' => ['size' => 100, 'unit' => '%'],
    'custom_css' => "selector { margin: 0; }\nselector img { width: 100%; height: 192px; object-fit: cover; display: block; }",
]);
```

### Text Editor (Dynamic HTML)

The most versatile widget — use for complex layouts that heading/icon widgets can't achieve:
```php
widget('text-editor', [
    'editor' => tag('<p>Founded: @post(year-founded) | HQ: @post(hq-city)</p>'),
]);
```

### Material Symbols Icon via HTML Widget

```php
widget('html', [
    'html' => '<span class="material-symbols-outlined" style="font-size:28px;color:#1BB5B5;">schedule</span>',
]);
```

Or inline within text-editor:
```php
widget('text-editor', [
    'editor' => '<span class="material-symbols-outlined" style="font-size:16px;">location_on</span> '
        . field('location.short_address'),
]);
```

### Post Feed (Voxel)

```php
widget('ts-post-feed', [
    'ts_choose_post_type' => 'lessons',       // NOT ts_post_type
    'ts_source' => 'search-filters',
    'ts_post_number' => 4,                    // NOT ts_posts_per_page
    'ts_feed_column_no' => 3,
    // REQUIRED: empty arrays for ALL registered post types
    'ts_filter_list__lessons' => [],
    'ts_filter_list__schools' => [],
    'ts_filter_list__post' => [],
    'ts_filter_list__page' => [],
    'ts_manual_posts' => [],
]);
```

### Gallery (Voxel — uses `.ids` suffix)

```php
widget('ts-gallery', [
    'ts_gallery_images' => [
        ['id' => tag('@post(gallery.ids)'), 'url' => ' '],
    ],
]);
```
**Without `.ids` the gallery renders empty.**

### Post Relation (Object Tags)

`@post(relation-field)` renders NOTHING. Must use nested properties:
```php
widget('heading', ['title' => tag('@post(manufacturer.:title)')]);
```

### Buttons

**Option A: text-editor (safest — avoids Voxel button override bug):**
```php
widget('text-editor', [
    'editor' => '<a href="#" style="display:inline-flex;padding:14px 28px;'
        . 'background:#30bee0;color:#fff;border-radius:10px;font-weight:700;'
        . 'text-decoration:none;">CTA Text →</a>',
]);
```

**Option B: Elementor button (works with gradient override via custom_css):**
```php
widget('button', [
    'text' => 'Book Now →',
    'link' => ['url' => '#'],
    'align' => 'stretch',
    'button_text_color' => '#FFFFFF',
    '__globals__' => ['button_text_color' => '', 'background_color' => ''],
    'background_color' => '#1BB5B5',
    'border_radius' => ['top' => '30', 'right' => '30', 'bottom' => '30', 'left' => '30',
                         'unit' => 'px', 'isLinked' => true],
    'custom_css' => 'selector .elementor-button { background: linear-gradient(to right, #1BB5B5, #2B3990) !important; width: 100%; justify-content: center; border: none; }',
]);
```

### Clickable Card Container

```php
inner([
    'html_tag' => 'a',
    'link' => ['url' => field(':url')],
    'overflow' => 'hidden', // Required for rounded corners on child images
    'custom_css' => "selector { transition: all 0.3s ease; text-decoration: none !important; cursor: pointer; }\nselector:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(0,0,0,0.14); }",
], [...]);
```

---

## Step 5: Repeater Loops (`_voxel_loop`)

**This is a critical feature missing from most documentation.** Voxel repeater fields are rendered using `_voxel_loop` on an inner container.

### Basic Loop

```php
inner(
    [
        '_voxel_loop' => '@post(lesson-structure)',  // Repeater field key
        'flex_direction' => 'row',
        'flex_gap' => ['size' => 16, 'unit' => 'px'],
    ],
    [
        // These widgets repeat for each repeater row
        widget('heading', [
            'title' => field('lesson-structure.heading'),  // Sub-field access
        ]),
        widget('heading', [
            'title' => field('lesson-structure.content'),
        ]),
    ]
);
```

### Timeline/Stepper Pattern with CSS Counters

```php
inner(
    [
        '_voxel_loop' => '@post(lesson-structure)',
        'flex_direction' => 'row',
        'flex_gap' => ['size' => 16, 'unit' => 'px'],
        'custom_css' => 'selector { counter-increment: step-counter; padding-left: 52px; position: relative; }'
            . ' selector::before { content: counter(step-counter, decimal-leading-zero); width: 36px; height: 36px;'
            . ' border-radius: 50%; background: rgba(43,57,144,0.08); color: #2B3990; display: flex;'
            . ' align-items: center; justify-content: center; font-weight: 700; font-size: 14px;'
            . ' position: absolute; left: 0; top: 0; }'
            . ' selector::after { content: ""; position: absolute; left: 17px; top: 36px; bottom: -20px;'
            . ' width: 2px; background: rgba(43,57,144,0.08); }'
            . ' selector:last-child::after { display: none; }',
    ],
    [
        inner(['flex_direction' => 'column', 'flex_gap' => ['size' => 6, 'unit' => 'px']], [
            widget('heading', [
                'title' => field('lesson-structure.heading'),
                'header_size' => 'h4',
            ]),
            widget('heading', [
                'title' => field('lesson-structure.content'),
                'header_size' => 'span',
                'title_color' => '#64748b',
                '__globals__' => ['title_color' => ''],
            ]),
        ]),
    ]
);
```

### Icon-List Loop (What's Included / What to Bring)

```php
inner(
    [
        '_voxel_loop' => '@post(inclusions)',
        'flex_direction' => 'row',
        'flex_align_items' => 'center',
        'flex_gap' => ['size' => 8, 'unit' => 'px'],
    ],
    [
        widget('text-editor', [
            'editor' => '<span class="material-symbols-outlined" style="font-size:18px;color:#2B3990;">done</span>',
            'custom_css' => 'selector { flex-shrink: 0; margin: 0; }',
        ]),
        widget('heading', [
            'title' => field('inclusions.item'),
            'header_size' => 'span',
        ]),
    ]
);
```

---

## Step 6: Full-Page Layout Patterns

### A. Hero Section (Full-Width Background Image + Gradient Overlay)

```php
$hero = container(
    [
        'content_width' => 'full',
        'min_height' => ['size' => 600, 'unit' => 'px'],
        'flex_direction' => 'column',
        'flex_justify_content' => 'flex-end',
        'padding' => ['top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'unit' => 'px'],
        'background_background' => 'classic',
        'background_image' => [
            'url' => field('cover-image'),
            'id'  => field('cover-image.id'),
        ],
        'background_position' => 'center center',
        'background_size' => 'cover',
        // Dark gradient overlay from bottom
        'custom_css' => 'selector::before { background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.60) 50%, rgba(0,0,0,0.50) 100%) !important; }',
    ],
    [
        inner(
            [
                'content_width' => 'boxed',
                'boxed_width' => ['size' => 1200, 'unit' => 'px'],
                'padding' => ['top' => '60', 'right' => '30', 'bottom' => '50', 'left' => '30', 'unit' => 'px', 'isLinked' => false],
            ],
            [
                // Badge row
                inner(['flex_direction' => 'row', 'flex_gap' => ['size' => 8, 'unit' => 'px']], [
                    widget('heading', [
                        'title' => tag('@post(activity.label)'),
                        'header_size' => 'span',
                        'title_color' => '#fff',
                        '__globals__' => ['title_color' => '', '_background_color' => ''],
                        '_background_background' => 'classic',
                        '_background_color' => '#1BB5B5',  // Solid color badge
                        '_padding' => ['top' => '4', 'right' => '12', 'bottom' => '4', 'left' => '12', 'unit' => 'px', 'isLinked' => false],
                        '_border_radius' => ['top' => '20', 'right' => '20', 'bottom' => '20', 'left' => '20', 'unit' => 'px'],
                        'typography_typography' => 'custom',
                        'typography_font_size' => ['size' => 12, 'unit' => 'px'],
                        'typography_font_weight' => '700',
                        'typography_text_transform' => 'uppercase',
                    ]),
                    widget('heading', [
                        'title' => tag('@post(skill-level.label)'),
                        'header_size' => 'span',
                        '_background_background' => 'classic',
                        '_background_color' => 'rgba(0,0,0,0.45)',  // Semi-transparent badge
                        // ... same badge styling ...
                    ]),
                ]),
                // Title
                widget('heading', [
                    'title' => field(':title'),
                    'header_size' => 'h1',
                    'title_color' => '#fff',
                    '__globals__' => ['title_color' => ''],
                    'typography_typography' => 'custom',
                    'typography_font_family' => 'Montserrat',
                    'typography_font_weight' => '900',
                    'typography_font_size' => ['size' => 60, 'unit' => 'px'],
                ]),
            ]
        ),
    ]
);
```

### B. Quick Info Bar (Negative Margin Overlap)

A stat bar that overlaps the hero section bottom:

```php
$quick_info = container(
    [
        'content_width' => 'boxed',
        'boxed_width' => ['size' => 1200, 'unit' => 'px'],
        'padding' => ['top' => '0', 'right' => '20', 'bottom' => '0', 'left' => '20', 'unit' => 'px'],
        'margin' => ['top' => '-40', 'unit' => 'px', 'isLinked' => false],  // Overlap hero
        'z_index' => '30',
    ],
    [
        inner(
            [
                'flex_direction' => 'row',
                'flex_wrap' => 'nowrap',
                'background_background' => 'classic',
                'background_color' => '#fff',
                '__globals__' => ['background_color' => ''],
                'border_radius' => ['top' => '12', 'right' => '12', 'bottom' => '12', 'left' => '12', 'unit' => 'px'],
                'box_shadow_box_shadow_type' => 'yes',
                'box_shadow_box_shadow' => ['horizontal' => 0, 'vertical' => 20, 'blur' => 60, 'spread' => -12, 'color' => 'rgba(0,0,0,0.15)'],
            ],
            [
                stat_box('payments', 'PRICE', tag('€@post(price)')),
                stat_box('schedule', 'DURATION', tag('@post(duration) min')),
                // etc.
            ]
        ),
    ]
);
```

### C. Two-Column Content + Sidebar (65/35)

```php
$content_area = container(
    [
        'content_width' => 'boxed',
        'boxed_width' => ['size' => 1200, 'unit' => 'px'],
        'flex_direction' => 'row',
        'flex_gap' => ['size' => 40, 'unit' => 'px'],
        'flex_wrap' => 'wrap',
        'flex_align_items' => 'flex-start',
    ],
    [
        // Left column (65%)
        inner([
            'flex_direction' => 'column',
            'flex_gap' => ['size' => 28, 'unit' => 'px'],
            'custom_css' => 'selector { flex: 1 1 65%; min-width: 500px; }',
        ], [$about, $lesson_structure, $included_row, $notice]),

        // Right sidebar (fixed width)
        inner([
            'flex_direction' => 'column',
            'flex_gap' => ['size' => 20, 'unit' => 'px'],
            'custom_css' => 'selector { width: 350px; flex-shrink: 0; }',
        ], [$booking_card, $need_help_card]),
    ]
);
```

### D. Sidebar Booking Card

```php
inner(
    [
        'flex_direction' => 'column',
        'background_background' => 'classic',
        'background_color' => '#fff',
        '__globals__' => ['background_color' => ''],
        'border_radius' => ['top' => '12', 'right' => '12', 'bottom' => '12', 'left' => '12', 'unit' => 'px'],
        'box_shadow_box_shadow_type' => 'yes',
        'box_shadow_box_shadow' => ['horizontal' => 0, 'vertical' => 20, 'blur' => 60, 'spread' => -12, 'color' => 'rgba(0,0,0,0.15)'],
        'overflow' => 'hidden',
        'padding' => ['top' => '0', 'right' => '0', 'bottom' => '24', 'left' => '0', 'unit' => 'px', 'isLinked' => false],
    ],
    [
        // Top banner
        inner(['background_color' => '#FEF3C7', ...], [
            widget('text-editor', ['editor' => '⚡ BOOK IN ADVANCE']),
        ]),
        // Detail rows using text-editor for icon + label + value layouts
        inner(['custom_css' => 'selector .detail-row { display: flex; justify-content: space-between; padding: 14px 0; border-bottom: 1px solid rgba(0,0,0,0.05); }'], [
            widget('text-editor', [
                'editor' => '<div class="detail-row"><span>Instructor</span><strong>' . tag('@post(instructor-name)') . '</strong></div>',
            ]),
        ]),
        // CTA button
        widget('button', [...]),
    ]
);
```

### E. Header (Sticky, 3-Zone)

```php
$header = container([
    'content_width' => 'boxed',
    'flex_direction' => 'row',
    'flex_align_items' => 'center',
    'flex_justify_content' => 'space-between',
    'background_background' => 'classic',
    'background_color' => '#ffffff',
    '__globals__' => ['background_color' => ''],
    'custom_css' => "selector { position: sticky; top: 0; z-index: 999; }",
], [
    // LEFT: Logo (25%)
    inner(['width' => ['size' => 25, 'unit' => '%']], [
        widget('text-editor', ['editor' => '<a href="/">LOGO</a>']),
    ]),
    // CENTER: Nav (50%)
    inner(['width' => ['size' => 50, 'unit' => '%'], 'flex_justify_content' => 'center'], [
        widget('text-editor', ['editor' => '<nav>...</nav>']),
    ]),
    // RIGHT: Auth (25%)
    inner(['width' => ['size' => 25, 'unit' => '%'], 'flex_justify_content' => 'flex-end'], [
        widget('text-editor', ['editor' => '<a href="/login">Log In</a>']),
        widget('text-editor', ['editor' => '<a href="/register" style="...">Register</a>']),
    ]),
]);
save_template($HEADER_ID, [$header], 'header');  // Note: type = 'header'
```

### F. Footer (Dark, Multi-Column)

```php
$footer = container([
    'content_width' => 'boxed',
    'flex_direction' => 'column',
    'background_background' => 'classic',
    'background_color' => '#1a37ad',
    '__globals__' => ['background_color' => ''],
], [
    inner([
        'flex_direction' => 'row',
        'flex_gap' => ['size' => 48, 'unit' => 'px'],
        'flex_wrap' => 'nowrap',
        // Force horizontal layout
        'custom_css' => 'selector { display: flex !important; flex-direction: row !important; }',
    ], [
        inner(['width' => ['size' => 30, 'unit' => '%']], [...]),  // Logo + description
        inner(['width' => ['size' => 20, 'unit' => '%']], [...]),  // Link column
        inner(['width' => ['size' => 20, 'unit' => '%']], [...]),
        inner(['width' => ['size' => 20, 'unit' => '%']], [...]),
    ]),
]);
save_template($FOOTER_ID, [$footer], 'footer');  // Note: type = 'footer'
```

---

## Step 7: Blog / Standard WP Post Templates

Blog posts use **Elementor Pro Theme Builder** widgets — NOT Voxel dynamic tags:

| Widget | Auto-Pulls |
|--------|-----------|
| `theme-post-featured-image` | Featured image |
| `theme-post-title` | Post title |
| `theme-post-content` | Post content |
| `post-info` | Date, author, categories |
| `author-box` | Author avatar + bio |
| `post-navigation` | Previous/next links |

Set `_elementor_template_type` = `'single-post'` (not `'page'`).

**CRITICAL:** Do NOT use `position: absolute` CSS on `theme-post-featured-image` — it causes a placeholder.png render bug. Use `max-height` + `object-fit: cover` instead.

---

## Step 8: Styling Patterns

### Custom Colors — Must Clear `__globals__`

```php
'title_color' => '#1a2a47',
'__globals__' => ['title_color' => ''],  // Without this, color is IGNORED
```

### Widget-Level Background (Badge Pattern)

Use `_background_*` prefix (with underscore) for widget-level styling:
```php
'_background_background' => 'classic',
'_background_color' => 'rgba(0,0,0,0.45)',
'__globals__' => ['_background_color' => ''],
'_padding' => ['top' => '4', 'right' => '12', 'bottom' => '4', 'left' => '12', 'unit' => 'px'],
'_border_radius' => ['top' => '20', 'right' => '20', 'bottom' => '20', 'left' => '20', 'unit' => 'px'],
```

### Box Shadow

```php
'box_shadow_box_shadow_type' => 'yes',
'box_shadow_box_shadow' => [
    'horizontal' => 0, 'vertical' => 20, 'blur' => 60,
    'spread' => -12, 'color' => 'rgba(0,0,0,0.15)',
],
```

### Text Truncation (Line Clamp)

```php
'custom_css' => "selector .elementor-heading-title { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }",
```

### Custom CSS on Any Widget

```php
'custom_css' => "selector .some-class { color: red; }\nselector:hover { opacity: 0.8; }"
```

---

## Step 9: Save Template

```php
$elements = [$hero, $content, $related, $footer_cta];
save_template($TEMPLATE_ID, $elements, 'page');        // Voxel templates
save_template($TEMPLATE_ID, $elements, 'single-post'); // Elementor Pro blog
save_template($HEADER_ID, [$header], 'header');         // Header
save_template($FOOTER_ID, [$footer], 'footer');         // Footer
```

### Run the Script

```bash
php -d memory_limit=512M $(which wp) eval-file build-my-template.php --path=wordpress
```

---

## Next Step

→ **Stage 3: Verify & Debug** (`stage-3-verify`)
