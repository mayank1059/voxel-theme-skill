---
name: stage-2-build
description: Build Voxel + Elementor templates programmatically using PHP scripts and WP-CLI. Covers dynamic tags, JSON structure, all widget types, layout patterns, images, post relations, feeds, galleries, buttons, and all known pitfalls.
---

# Stage 2: Build Templates

**Purpose:** Write PHP build scripts that generate Elementor template JSON and save it to the database via WP-CLI.

---

## Step 1: Project Setup

```bash
# Ensure helpers.php is in your project root
cp .agent/skills/voxel-theme-agentic-skill/stage-0-setup/scripts/helpers.php ./helpers.php
```

Every build script starts with:
```php
<?php
require_once __DIR__ . '/helpers.php';
$TEMPLATE_ID = 1234; // From Stage 1 research
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
@tags()@post(:id)@endtags()                 → Post ID
@tags()@post(field-key)@endtags()           → Custom field value
@tags()@post(field-key.id)@endtags()        → Image attachment ID
@tags()@post(field-key.ids)@endtags()       → Gallery comma-separated IDs
@tags()@post(field-key.:title)@endtags()    → Post-relation title
@tags()@post(field-key.:url)@endtags()      → Post-relation URL
@site(post_types.slug.archive_link)         → Archive URL
```

### PHP Helpers (already in helpers.php)

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
        'boxed_width' => ['size' => 1200, 'unit' => 'px', 'sizes' => []],
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
            'typography_font_family' => 'Inter',
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

Alternative — **equal-width with flex-grow:**
```php
inner(['flex_size' => 'custom', 'flex_grow' => '1', 'flex_shrink' => '1'], [...])
```

---

## Step 4: Widget Patterns

### Image Widget (Dynamic)

**Both `url` AND `id` must be set:**
```php
widget('image', [
    'image' => ['url' => field('hero-image'), 'id' => field('hero-image.id')],
    'image_size' => 'large',
]);
```

### Text Editor (Dynamic HTML)

```php
widget('text-editor', [
    'editor' => tag('<p>Founded: @post(year-founded) | HQ: @post(hq-city)</p>'),
]);
```

### Post Feed (Voxel)

```php
widget('ts-post-feed', [
    'ts_choose_post_type' => 'products',     // NOT ts_post_type
    'ts_source' => 'search-filters',
    'ts_post_number' => 4,                   // NOT ts_posts_per_page
    'ts_feed_column_no' => 4,
    // REQUIRED: empty arrays for ALL registered post types
    'ts_filter_list__products' => [],
    'ts_filter_list__manufacturers' => [],
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
widget('image', ['image' => [
    'url' => tag('@post(manufacturer.logo)'),
    'id'  => tag('@post(manufacturer.logo.id)'),
]]);
```

### Buttons — Use text-editor, NOT button widget

Voxel overrides Elementor button backgrounds:
```php
widget('text-editor', [
    'editor' => '<a href="URL" style="display:inline-flex;padding:14px 28px;'
        . 'background:#30bee0;color:#fff;border-radius:10px;font-weight:700;'
        . 'text-decoration:none;">CTA Text →</a>',
]);
```

### Icon + Heading Section Title

```php
inner(['flex_direction' => 'row', 'flex_align_items' => 'center',
       'flex_gap' => ['size' => 14, 'unit' => 'px']], [
    widget('icon', [
        'selected_icon' => ['value' => 'fas fa-cubes', 'library' => 'fa-solid'],
        'view' => 'stacked', 'shape' => 'circle',
        'primary_color' => '#30bee0', 'secondary_color' => '#ffffff',
        'size' => ['size' => 18, 'unit' => 'px'],
    ]),
    widget('heading', ['title' => 'Section Title', 'header_size' => 'h2']),
]);
```

### Clickable Card Container

```php
inner([
    'html_tag' => 'a',
    'link' => ['url' => tag('@post(:url)')],
    'custom_css' => "selector { transition: all 0.2s; text-decoration: none; }\n"
        . "selector:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }",
], [...]);
```

---

## Step 5: Blog / Standard WP Post Templates

Blog posts use **Elementor Pro Theme Builder** widgets — NOT Voxel dynamic tags:

| Widget | Auto-Pulls |
|--------|-----------|
| `theme-post-featured-image` | Featured image |
| `theme-post-title` | Post title |
| `theme-post-content` | Post content |
| `post-info` | Date, author, categories, tags, reading time |
| `author-box` | Author avatar + bio |
| `post-navigation` | Previous/next links |
| `post-comments` | Comments form |

Set `_elementor_template_type` = `'single-post'` (not `'page'`).

**CRITICAL:** Do NOT use `position: absolute` CSS on `theme-post-featured-image` — it causes a placeholder.png render bug. Use `max-height` + `object-fit: cover` instead.

---

## Step 6: Styling Patterns

### Custom Colors — Must Clear `__globals__`

```php
'title_color' => '#1a2a47',
'__globals__' => ['title_color' => ''],  // Without this, color is IGNORED
```

### Background Color

```php
'background_background' => 'classic',
'background_color' => '#f5f8fa',
'__globals__' => ['background_color' => ''],
```

### Border Radius

```php
'border_radius' => ['top' => '16', 'right' => '16', 'bottom' => '16', 'left' => '16',
                     'unit' => 'px', 'isLinked' => true],
```

### Custom CSS on Any Widget

```php
'custom_css' => "selector .some-class { color: red; }\nselector:hover { opacity: 0.8; }"
```

---

## Step 7: Save Template

Use the `save_template()` function from helpers.php:

```php
$elements = [$hero, $content, $related, $footer_cta];
save_template($TEMPLATE_ID, $elements, 'page');       // Voxel templates
save_template($TEMPLATE_ID, $elements, 'single-post'); // Elementor Pro blog
```

### Run the Script

```bash
php -d memory_limit=512M $(which wp) eval-file build-my-template.php --path=path/to/wordpress
```

---

## Next Step

→ **Stage 3: Verify & Debug** (`stage-3-verify`)
