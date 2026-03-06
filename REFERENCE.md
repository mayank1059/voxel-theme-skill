# Voxel Theme + Elementor — Programmatic Template Development Guide

> **Purpose:** Universal reference for building WordPress templates programmatically with Voxel Theme + Elementor. No project-specific data — works for any Voxel site.
>
> **For LLMs:** This document is structured as a decision tree. Each section starts with the principle, then gives the exact code pattern, then lists the gotcha. Read top-to-bottom before generating any code.

---

## Table of Contents

1. [Dynamic Tags — The #1 Thing To Get Right](#1-dynamic-tags)
2. [Elementor JSON Structure & Helper Functions](#2-elementor-json-structure)
3. [Saving Templates to Database](#3-saving-templates)
4. [Template Architecture — Which ID To Target](#4-template-architecture)
5. [Widget Reference & Settings](#5-widget-reference)
6. [Layout Patterns — Flex, Grid, Columns](#6-layout-patterns)
7. [Image Handling](#7-image-handling)
8. [Post Relation Fields](#8-post-relation-fields)
9. [Post Feed & Filtering](#9-post-feeds)
10. [Gallery Widgets](#10-gallery-widgets)
11. [Buttons & CTAs](#11-buttons-and-ctas)
12. [Icons & Visual Elements](#12-icons)
13. [Blog / Standard WP Post Templates](#13-blog-templates)
14. [Card Template Design Patterns](#14-card-patterns)
15. [CSS Techniques for Voxel Sites](#15-css-techniques)
16. [MU-Plugin Patterns](#16-mu-plugin-patterns)
17. [Common Pitfalls & Gotchas (Quick Reference)](#17-pitfalls)

---

## 1. Dynamic Tags

### THE RULE: Voxel does NOT use Elementor's `__dynamic__` system

Voxel has its own dynamic tag syntax. Using Elementor's `__dynamic__` key causes PHP warnings in `controls-stack.php` and broken rendering.

### Correct Syntax

```
@tags()@post(:title)@endtags()              → Post title
@tags()@post(:content)@endtags()            → Post content
@tags()@post(:url)@endtags()                → Post permalink
@tags()@post(:id)@endtags()                 → Post ID
@tags()@post(field-key)@endtags()           → Custom field value
@tags()@post(field-key.id)@endtags()        → Image field attachment ID
@tags()@post(field-key.ids)@endtags()       → Gallery field — comma-separated IDs
@tags()@post(:reviews.average)@endtags()    → Review average
@site(post_types.slug.archive_link)         → Archive URL for a post type
```

### Usage in PHP Build Scripts

```php
// Helper functions
function tag($expr) { return '@tags()' . $expr . '@endtags()'; }
function field($f)  { return tag("@post($f)"); }

// Heading widget — renders the post title
widget('heading', ['title' => field(':title')]);

// Image widget — BOTH url AND id must be set
widget('image', [
    'image' => [
        'url' => field('logo'),
        'id'  => field('logo.id'),
    ],
]);

// Text editor with multiple dynamic fields inline
widget('text-editor', [
    'editor' => tag('<p>Founded: @post(year-founded) | HQ: @post(hq-city)</p>'),
]);

// Link using dynamic URL
'link' => ['url' => tag('@post(website)'), 'is_external' => 'true', 'nofollow' => '']
```

### ❌ What NOT to Do

```php
// WRONG — causes PHP warnings and broken output
'__dynamic__' => ['title' => "voxel:post-field|key=title"]

// WRONG — missing @tags() wrapper
'title' => '@post(:title)'

// WRONG — image missing .id suffix
'image' => ['url' => field('logo'), 'id' => '']
```

---

## 2. Elementor JSON Structure

### Element Hierarchy

```
container (section-level, isInner=false)
  └─ container (inner, isInner=true)
       └─ widget (leaf node, widgetType="heading")
```

Every Elementor page is an array of top-level `container` elements. Each can contain nested `inner` containers and `widget` leaf nodes.

### Helper Functions (Copy These Into Every Build Script)

Create a file called `helpers.php`:

```php
<?php
function eid() {
    return substr(md5(uniqid(mt_rand(), true)), 0, 7);
}

function container($settings = [], $children = [], $extra = []) {
    return array_merge([
        'id'       => eid(),
        'elType'   => 'container',
        'settings' => $settings,
        'elements' => $children,
        'isInner'  => false,
    ], $extra);
}

function inner($settings = [], $children = []) {
    return [
        'id'       => eid(),
        'elType'   => 'container',
        'settings' => $settings,
        'elements' => $children,
        'isInner'  => true,
    ];
}

function widget($type, $settings = []) {
    return [
        'id'         => eid(),
        'elType'     => 'widget',
        'settings'   => $settings,
        'elements'   => [],
        'widgetType' => $type,
    ];
}
```

### Build Script Skeleton

```php
<?php
require_once __DIR__ . '/helpers.php';

$TEMPLATE_ID = 1234; // Your template ID

// Build sections
$hero    = container([...], [...]);
$content = container([...], [...]);

// Save
$elements = [$hero, $content];
$json = json_encode($elements, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

update_post_meta($TEMPLATE_ID, '_elementor_data', wp_slash($json));
update_post_meta($TEMPLATE_ID, '_elementor_edit_mode', 'builder');
update_post_meta($TEMPLATE_ID, '_elementor_version', '3.35.2');

// Clear caches
delete_post_meta($TEMPLATE_ID, '_elementor_css');
clean_post_cache($TEMPLATE_ID);
\Elementor\Plugin::$instance->files_manager->clear_cache();
$css = \Elementor\Core\Files\CSS\Post::create($TEMPLATE_ID);
$css->update();
```

---

## 3. Saving Templates

### CRITICAL: `wp_slash()` is mandatory

Elementor JSON contains escaped characters. Without `wp_slash()`, WordPress strips backslashes and the template breaks silently.

```php
// ✅ CORRECT
update_post_meta($id, '_elementor_data', wp_slash($json));

// ❌ WRONG — backslashes stripped, JSON corruption
update_post_meta($id, '_elementor_data', $json);
```

### Required Meta Keys

| Key | Value |
|-----|-------|
| `_elementor_data` | The JSON template (wp_slashed) |
| `_elementor_edit_mode` | `'builder'` |
| `_elementor_version` | `'3.35.2'` (match your version) |
| `_elementor_template_type` | `'page'` for Voxel templates, `'single-post'` for Elementor Pro |

### Cache Clearing Checklist

After saving, ALWAYS run all of these:

```php
delete_post_meta($id, '_elementor_css');
delete_post_meta($id, '_elementor_controls_usage');
clean_post_cache($id);
wp_get_post_revisions($id); // delete old revisions if desired
\Elementor\Plugin::$instance->files_manager->clear_cache();
$css = \Elementor\Core\Files\CSS\Post::create($id);
$css->update();
```

---

## 4. Template Architecture

### Voxel Post Types → Voxel Templates

Each Voxel custom post type has 4 templates stored as `elementor_library` posts:
- **single** — Individual post page
- **card** — Preview card in feeds/grids
- **archive** — Listing/search page
- **form** — Submission form

Find template IDs:
```bash
wp eval '
$pt = json_decode(get_option("voxel:post_types", "{}"), true);
foreach ($pt as $key => $config) {
    if (isset($config["templates"])) {
        echo "$key:\n";
        foreach ($config["templates"] as $tpl => $id) echo "  $tpl => $id\n";
    }
}
'
```

### Standard WordPress Posts → Elementor Pro Theme Builder

**THIS IS CRITICAL:** Standard WordPress post types (blog posts) use **Elementor Pro Theme Builder** templates, NOT Voxel templates. These override everything.

To find the active template for any page:
```bash
curl -s YOUR_URL | grep 'data-elementor-type.*data-elementor-id'
```

The output shows which template IDs are active (header, single-post, footer).

### When to use which template type:
- **Voxel custom post types** (products, manufacturers, etc.): Use the Voxel template IDs from `voxel:post_types` option. Set `_elementor_template_type` = `'page'`.
- **WordPress blog posts**: Use the Elementor Pro single-post template. Set `_elementor_template_type` = `'single-post'`.
- **Header/Footer**: NEVER rebuild from scratch — Voxel's `ts-user-bar` components have internal configs that break when recreated. Only apply CSS overrides.

---

## 5. Widget Reference

### Voxel Widgets

| Widget | Purpose | Key Settings |
|--------|---------|-------------|
| `ts-search-form` | Search/filter bar | `ts_choose_post_types`, `ts_filter_list__<type>`, `ts_on_submit` |
| `ts-post-feed` | Post grid/list | `ts_choose_post_type`, `ts_post_number`, `ts_source` |
| `ts-gallery` | Image gallery | `ts_gallery_images` (see Gallery section) |
| `ts-slider` | Image slider | `ts_slider_images` |
| `ts-advanced-list` | Action buttons | `ts_actions` |
| `ts-timeline` | Activity feed | `ts_mode`, `ts_ordering_options` |
| `ts-review-stats` | Review stats | minimal config |

### Elementor Pro Theme Widgets (for blog/standard posts)

| Widget | Purpose |
|--------|---------|
| `theme-post-featured-image` | Featured image |
| `theme-post-title` | Post title (auto-dynamic) |
| `theme-post-content` | Post content (auto-dynamic) |
| `post-info` | Date, author, categories, tags, reading time |
| `author-box` | Author avatar + bio |
| `post-navigation` | Previous/next post links |
| `post-comments` | Comments form |

### Standard Elementor Widgets

`heading`, `text-editor`, `image`, `icon`, `divider`, `star-rating` — all work fine in Voxel templates. Use `@tags()` syntax for dynamic content.

---

## 6. Layout Patterns

### Horizontal Layouts (Multi-Column)

**THE GOTCHA:** Setting `flex_direction => 'row'` on a parent does NOT make children sit side-by-side. Each child MUST have an explicit `width`.

```php
// Parent container
inner([
    'flex_direction' => 'row',
    'flex_gap' => ['size' => 16, 'unit' => 'px'],
    'flex_wrap' => 'wrap',
], [
    // Child 1 — explicit width required!
    inner(['width' => ['size' => 48, 'unit' => '%']], [...]),
    // Child 2
    inner(['width' => ['size' => 48, 'unit' => '%']], [...]),
]);
```

### Column Patterns

| Layout | Width per child |
|--------|----------------|
| 2-column | 48% each |
| 3-column | 30% each |
| 4-column | 23% each |
| 55/45 split | 55% + 45% |

### Equal-Width with Flex Grow (Alternative)

```php
inner([
    'flex_size' => 'custom',
    'flex_grow' => '1',
    'flex_shrink' => '1',
], [...])
```

Combine with parent: `'flex_wrap' => 'nowrap'`, `'flex_align_items' => 'stretch'`.

### Full-Width Section with Boxed Content

```php
container([
    'content_width' => 'boxed',
    'boxed_width' => ['size' => 1200, 'unit' => 'px', 'sizes' => []],
    'padding' => ['top' => '60', 'right' => '24', 'bottom' => '60', 'left' => '24',
                  'unit' => 'px', 'isLinked' => false],
], [...]);
```

---

## 7. Image Handling

### Dynamic Image Widget

Both `url` AND `id` must be set with dynamic tags:

```php
widget('image', [
    'image' => [
        'url' => field('hero-image'),
        'id'  => field('hero-image.id'),
    ],
    'image_size' => 'large',
]);
```

### Logo Images — Use `object-fit: contain`

For logos that shouldn't be cropped:
```php
'custom_css' => "selector img { object-fit: contain !important; padding: 10px; }"
```

### Featured Image (Elementor Pro)

For blog hero banners, do NOT use `position: absolute` — it causes a placeholder bug:
```php
// ✅ CORRECT — crop with max-height
widget('theme-post-featured-image', [
    'image_size' => 'full',
    'custom_css' => "selector { max-height: 480px; overflow: hidden; }\n"
        . "selector img { width: 100% !important; height: 480px !important; "
        . "object-fit: cover !important; filter: brightness(0.7); }",
]);

// ❌ WRONG — causes placeholder.png render bug
'custom_css' => "selector { position: absolute !important; ... }"
```

---

## 8. Post Relation Fields

### Relation Fields Are Object Tags

`@post(relation-field)` renders **NOTHING**. You must access nested properties:

```
@post(relation-field.:title)    → Related post title
@post(relation-field.:url)      → Related post URL
@post(relation-field.:id)       → Related post ID
@post(relation-field.logo)      → Related post's "logo" field
@post(relation-field.logo.id)   → Related post's logo attachment ID
```

### Clickable Manufacturer/Related Post Card

```php
inner([
    'html_tag' => 'a',
    'link' => ['url' => tag('@post(manufacturer.:url)')],
    'flex_direction' => 'row',
    'flex_align_items' => 'center',
    'flex_gap' => ['size' => 12, 'unit' => 'px'],
    'custom_css' => "selector { transition: all 0.2s; text-decoration: none; }\n"
        . "selector:hover { background: #eef9fc !important; }",
], [
    widget('image', [
        'image' => ['url' => tag('@post(manufacturer.logo)'), 'id' => tag('@post(manufacturer.logo.id)')],
    ]),
    widget('text-editor', [
        'editor' => tag('<p>@post(manufacturer.:title)</p>'),
    ]),
]);
```

---

## 9. Post Feeds

### `ts-post-feed` Configuration

```php
widget('ts-post-feed', [
    'ts_choose_post_type' => 'products',     // NOT ts_post_type
    'ts_source' => 'search-filters',
    'ts_post_number' => 4,                   // NOT ts_posts_per_page
    'ts_feed_column_no' => 4,
    'ts_feed_column_no_tablet' => 2,
    'ts_feed_column_no_mobile' => 1,
    // REQUIRED: empty arrays for ALL registered post types
    'ts_filter_list__products' => [],
    'ts_filter_list__manufacturers' => [],
    'ts_filter_list__post' => [],
    'ts_filter_list__page' => [],
    // ... add every post type registered in your Voxel install
    'ts_manual_posts' => [],
]);
```

**Critical:** If any `ts_filter_list__<type>` array is missing, the feed may show no results.

### Filtering by Current Post's Relation

To show only related posts (e.g., products by current manufacturer):

```php
'ts_filter_list__products' => [
    [
        '_id' => 'rel_filter',
        'ts_choose_filter' => 'relations',
        'relations:value' => '@tags()@post(:id)@endtags()',
    ],
],
```

The filter name (`relations`) must match the search filter key configured on the target post type in Voxel's admin settings.

---

## 10. Gallery Widgets

### Dynamic Gallery Using `.ids` Suffix

The `ts-gallery` and `ts-slider` widgets only accept static image arrays. To make them dynamic, use the `.ids` suffix:

```php
widget('ts-gallery', [
    'ts_gallery_images' => [
        ['id' => tag('@post(gallery.ids)'), 'url' => ' '],
    ],
    'ts_visible_count' => 8,
    'ts_display_size' => 'medium_large',
    'custom_css' => "
selector .ts-gallery-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
selector .ts-gallery-grid li { overflow: hidden; border-radius: 12px; aspect-ratio: 4/3; transition: all 0.3s; }
selector .ts-gallery-grid li:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); }
selector .ts-gallery-grid li img { width: 100% !important; height: 100% !important; object-fit: cover !important; transition: transform 0.4s; }
selector .ts-gallery-grid li:hover img { transform: scale(1.05); }
selector .ts-gallery-grid li:first-child { grid-column: span 2; grid-row: span 2; }
",
]);
```

**Without `.ids`**, the gallery renders empty. The `.ids` suffix outputs comma-separated attachment IDs that Voxel internally expands.

---

## 11. Buttons and CTAs

### Voxel Overrides Elementor Button Backgrounds

The `button` widget ignores `button_background_color: 'transparent'` and renders solid colored blocks.

**Workaround:** Use `text-editor` with styled `<a>` tags:

```php
// Primary CTA
widget('text-editor', [
    'editor' => '<a href="DYNAMIC_URL" style="display:inline-flex;align-items:center;'
        . 'gap:8px;font-family:YOUR_FONT,sans-serif;font-size:15px;font-weight:700;'
        . 'color:#fff;padding:14px 28px;background:#30bee0;border-radius:10px;'
        . 'text-decoration:none;transition:all 0.2s;box-shadow:0 4px 14px rgba(48,190,224,0.3);"'
        . '>View Product →</a>',
]);

// Secondary/outline CTA
widget('text-editor', [
    'editor' => '<a href="URL" style="display:inline-flex;align-items:center;gap:8px;'
        . 'font-size:14px;font-weight:600;color:#475569;padding:14px 20px;'
        . 'border:1.5px solid #e2e8f0;border-radius:10px;text-decoration:none;'
        . 'transition:all 0.2s;">Secondary Action</a>',
]);
```

---

## 12. Icons

### Use Native Elementor `icon` Widget

Don't embed SVG HTML in text-editor widgets. Use Elementor's icon widget with Font Awesome:

```php
widget('icon', [
    'selected_icon' => ['value' => 'fas fa-industry', 'library' => 'fa-solid'],
    'view' => 'stacked',      // 'stacked' | 'framed' | 'default'
    'shape' => 'circle',
    'primary_color' => '#30bee0',
    'secondary_color' => '#ffffff',
    'size' => ['size' => 18, 'unit' => 'px'],
    'icon_padding' => ['size' => 12, 'unit' => 'px'],
]);
```

### Section Title Pattern (Icon + Heading in a Row)

```php
inner([
    'flex_direction' => 'row',
    'flex_align_items' => 'center',
    'flex_gap' => ['size' => 14, 'unit' => 'px'],
], [
    widget('icon', [
        'selected_icon' => ['value' => 'fas fa-cubes', 'library' => 'fa-solid'],
        'view' => 'stacked',
        'shape' => 'circle',
        'primary_color' => '#30bee0',
        'secondary_color' => '#ffffff',
        'size' => ['size' => 18, 'unit' => 'px'],
        'icon_padding' => ['size' => 12, 'unit' => 'px'],
    ]),
    widget('heading', [
        'title' => 'Section Title',
        'header_size' => 'h2',
        'title_color' => '#1a2a47',
        '__globals__' => ['title_color' => ''],
        'typography_typography' => 'custom',
        'typography_font_family' => 'Your Font',
        'typography_font_weight' => '700',
        'typography_font_size' => ['size' => 28, 'unit' => 'px'],
    ]),
]);
```

---

## 13. Blog / Standard WP Post Templates

### Key Differences from Voxel Templates

| Aspect | Voxel Custom Post Types | Standard WP Posts (Blog) |
|--------|------------------------|--------------------------|
| Template system | Voxel template IDs | Elementor Pro Theme Builder |
| Template type meta | `page` | `single-post` |
| Dynamic content | `@tags()@post()@endtags()` | Auto-dynamic (widgets pull data) |
| Title widget | `heading` + dynamic tag | `theme-post-title` (auto-fills) |
| Content widget | `text-editor` + dynamic tag | `theme-post-content` (auto-fills) |
| Image widget | `image` + dynamic tag | `theme-post-featured-image` (auto-fills) |

### Blog Template Widgets

These Elementor Pro widgets automatically pull from the current post context — no dynamic tags needed:

```php
widget('theme-post-featured-image', ['image_size' => 'full']);
widget('theme-post-title', ['header_size' => 'h1']);
widget('theme-post-content', []);
widget('post-info', ['layout' => 'inline', 'list' => [
    ['_id' => 'date1', 'type' => 'date', 'date_format' => 'custom', 'custom_date_format' => 'M j, Y'],
    ['_id' => 'author1', 'type' => 'author'],
    ['_id' => 'time1', 'type' => 'time'],
]]);
widget('author-box', ['show_name' => 'yes', 'show_biography' => 'yes', 'show_avatar' => 'yes']);
widget('post-navigation', ['show_label' => 'yes']);
widget('post-comments', []);
```

---

## 14. Card Template Design Patterns

### Recommended Structure

```
Card Container (clickable via html_tag='a')
├── Image (object-fit: cover or contain for logos)
├── Title (font-weight: 700)
├── Description (2-line clamp)
├── Metadata row (year, city — small grey text)
├── Label pills row (grey bg for info)
└── Price/highlight pill (green bg, pushed to bottom with margin-top: auto)
```

### Clickable Card Container

```php
inner([
    'html_tag' => 'a',
    'link' => ['url' => tag('@post(:url)')],
    'custom_css' => "selector { transition: all 0.2s; text-decoration: none; }\n"
        . "selector:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.08); transform: translateY(-2px); }",
], [...]);
```

### Text Truncation

```css
/* 2-line clamp for descriptions */
display: -webkit-box;
-webkit-line-clamp: 2;
-webkit-box-orient: vertical;
overflow: hidden;

/* Single-line truncation for pills */
white-space: nowrap;
overflow: hidden;
text-overflow: ellipsis;
max-width: 160px;
```

### Equal-Height Cards in Feed Grids

Requires global CSS:
```css
.post-feed-grid.ts-feed-grid-default { align-items: stretch; }
.post-feed-grid .ts-feed-grid-cell { display: flex; }
.post-feed-grid .ts-feed-grid-cell > * { height: 100%; }
/* Push last element to bottom */
.card-inner { flex-grow: 1; display: flex; flex-direction: column; }
.card-inner > :last-child { margin-top: auto; }
```

---

## 15. CSS Techniques

### Custom CSS on Widgets

Elementor supports `custom_css` on any widget. Use `selector` as the placeholder:

```php
'custom_css' => "selector .some-class { color: red; }\nselector:hover { opacity: 0.8; }"
```

### Setting Colors — Override `__globals__`

Elementor auto-applies global color presets. To use custom colors, you MUST clear the global:

```php
'title_color' => '#1a2a47',
'__globals__' => ['title_color' => ''],  // Clear the global override
```

Without clearing `__globals__`, your custom color is ignored.

### Background Colors

```php
'background_background' => 'classic',
'background_color' => '#f5f8fa',
'__globals__' => ['background_color' => ''],
```

### Border Radius

```php
'border_radius' => [
    'top' => '16', 'right' => '16', 'bottom' => '16', 'left' => '16',
    'unit' => 'px', 'isLinked' => true,
],
```

### Box Shadow

```php
'box_shadow_box_shadow_type' => 'yes',
'box_shadow_box_shadow' => [
    'horizontal' => 0, 'vertical' => 2, 'blur' => 12, 'spread' => 0,
    'color' => 'rgba(0,0,0,0.05)',
],
```

---

## 16. MU-Plugin Patterns

### Hiding Empty/N/A Values

Create `wp-content/mu-plugins/smart-cleanup.php`:

```php
<?php
add_action("wp_footer", function() { ?>
<script>
(function(){
    function cleanup() {
        // Hide N/A labels in feed grids
        document.querySelectorAll(".post-feed-grid span").forEach(function(el) {
            var t = el.textContent.trim();
            if (t === "N/A" || t.indexOf("N/A ") === 0) el.style.display = "none";
        });

        // Collapse duplicate prices: "$X – $X" → "$X"
        document.querySelectorAll("p, div, span").forEach(function(el) {
            var m = el.textContent.trim().match(/\$\s*([0-9,.]+)\s*[\u2013\u2014\-]\s*\$\s*([0-9,.]+)/);
            if (!m || m[1].replace(/,/g,"") !== m[2].replace(/,/g,"")) return;
            var kids = el.childNodes;
            for (var i = kids.length - 1; i >= 0; i--) {
                var n = kids[i];
                if (n.nodeType === 1 && n.textContent.trim().match(/^[\u2013\u2014\-]\s*\$/))
                    n.style.display = "none";
                if (n.nodeType === 1 && n.textContent.trim().match(/^[\u2013\u2014\-]$/)) {
                    n.style.display = "none";
                    if (n.nextSibling && n.nextSibling.nodeType === 3) n.nextSibling.textContent = "";
                }
            }
        });
    }
    document.readyState === "loading"
        ? document.addEventListener("DOMContentLoaded", cleanup)
        : cleanup();
    if (window.MutationObserver) {
        var d; new MutationObserver(function() {
            clearTimeout(d); d = setTimeout(cleanup, 100);
        }).observe(document.body, {childList:true, subtree:true});
    }
})();
</script>
<?php });
```

---

## 17. Common Pitfalls — Quick Reference

| # | Pitfall | Fix |
|---|---------|-----|
| 1 | Using `__dynamic__` with Voxel | Use `@tags()@post()@endtags()` |
| 2 | Forgetting `wp_slash()` when saving JSON | Always `wp_slash()` before `update_post_meta` |
| 3 | Button transparent bg shows solid color | Use `text-editor` + styled `<a>` tag |
| 4 | Flex row children stack vertically | Add explicit `width` to each child container |
| 5 | Image widget shows placeholder | Set BOTH `url` and `id` with dynamic tags |
| 6 | Post relation field renders empty | Use `@post(field.:title)` not `@post(field)` |
| 7 | Gallery widget shows empty | Use `.ids` suffix: `@post(gallery.ids)` |
| 8 | Blog template changes don't appear | Check if Elementor Pro Theme Builder template overrides Voxel |
| 9 | Custom colors ignored | Clear `__globals__`: `'__globals__' => ['color_key' => '']` |
| 10 | `position: absolute` on featured image | Causes placeholder.png — use `max-height` + `object-fit` instead |
| 11 | Feed shows no results | Add empty `ts_filter_list__<type>` arrays for ALL post types |
| 12 | Header breaks after rebuild | Never rebuild — only apply CSS overrides to existing structure |
| 13 | Voxel background images from fields | Don't work reliably — use image widgets instead |
| 14 | Search filters return nothing | Must configure `search.filters` in Voxel post type admin settings |

---

## WP-CLI Execution Pattern

```bash
# Run a build script
php -d memory_limit=512M $(which wp) eval-file my-build-script.php --path=path/to/wordpress

# Or use eval for quick commands
php -d memory_limit=512M $(which wp) eval '
    $data = json_decode(get_post_meta(TEMPLATE_ID, "_elementor_data", true), true);
    echo count($data) . " sections\n";
' --path=path/to/wordpress
```

---

*End of knowledge base. This document covers all patterns needed to programmatically build Voxel + Elementor templates via PHP build scripts and WP-CLI.*
