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

## Step 7: Header with CSS-Driven Mega Menu

Building headers with dropdown mega menus using native Elementor containers and CSS-only hover logic.

### Why CSS-only? Why mu-plugin?

Elementor's own `custom_css` on containers has **severe specificity problems**:
- Elementor wraps containers in `.e-con` and sometimes `.e-con-inner`, making selector targeting unreliable.
- `overflow: hidden` is applied by default on `.e-con` elements, **clipping absolutely-positioned dropdowns**.
- Selectors like `selector:hover ~ .sibling` often fail because Elementor inserts wrapper layers that break the sibling relationship.

**Solution:** Inject all critical header/mega-menu CSS via a **WordPress mu-plugin** (must-use plugin) directly into `<head>`. This bypasses Elementor's CSS pipeline entirely.

### DOM Structure: Parent-Child (NOT Sibling)

The mega menu panel **must be a child** of the trigger container, NOT a sibling. This is the most reliable approach because:
- Standard CSS `parent:hover child { ... }` works regardless of Elementor wrapper layers.
- Sibling selectors (`~`) break when Elementor inserts `.e-con-inner` wrappers between elements.

```php
// ❌ WRONG — Sibling approach (unreliable with Elementor)
inner(['css_classes' => 'pa-link'], [nav_heading(...)]),  // trigger
inner(['css_classes' => 'mega-panel'], [...]),             // panel (sibling)

// ✅ CORRECT — Parent-child approach (bulletproof)
inner(['css_classes' => 'pa-wrapper'], [       // wrapper (parent)
    widget('heading', [...]),                    // trigger text
    inner(['css_classes' => 'mega-panel'], [...]), // panel (child)
]),
```

### Header Build Pattern

```php
$elements = [
    container([
        'content_width' => 'full',
        'css_classes' => 'mal-header',
    ], [
        inner([
            'content_width' => 'full',
            'flex_direction' => 'row',
            'css_classes' => 'mal-nav-row',
        ], [
            // LOGO
            inner(['css_classes' => 'mal-logo-area'], [
                widget('image', ['image' => ['url' => $logo_url, 'id' => 30], ...]),
            ]),

            // NAV AREA (horizontal flex row)
            inner([
                'content_width' => 'full',
                'flex_direction' => 'row',
                'flex_justify_content' => 'center',
                'flex_align_items' => 'center',
                'css_classes' => 'mal-nav-area',
            ], [
                nav_heading('Home', '/'),
                nav_heading('About', '/about'),

                // ⭐ PA WRAPPER — contains both trigger & mega panel
                inner(['css_classes' => 'mal-pa-wrapper'], [
                    widget('heading', ['title' => 'Practice Areas', 'link' => ['url' => '/services']]),
                    inner(['css_classes' => 'mal-mega-panel', ...], [
                        // ... mega columns ...
                    ]),
                ]),

                nav_heading('FAQ', '/faq'),
                nav_heading('Blog', '/blog'),
            ]),

            // CTA
            inner(['css_classes' => 'mal-cta-area'], [
                widget('button', [...]),
            ]),
        ]),
    ]),
];
```

### Mu-Plugin CSS Pattern

Create `wp-content/mu-plugins/mal-mega-menu.php`:

```php
<?php
add_action('wp_head', function() {
?>
<style id="mal-mega-menu-css">
/* CRITICAL: overflow visible on ALL ancestors */
.mal-header, .mal-header.e-con,
.mal-nav-row, .mal-nav-row.e-con,
.mal-nav-area, .mal-nav-area.e-con,
.mal-pa-wrapper, .mal-pa-wrapper.e-con,
.mal-header .e-con, .mal-header .e-con-inner {
    overflow: visible !important;
}

/* Mega panel: hidden by default, absolutely positioned */
.mal-mega-panel, .mal-mega-panel.e-con {
    position: absolute !important;
    top: 100% !important;
    left: 50% !important;
    transform: translateX(-50%) !important;
    width: 880px !important;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.25s ease, visibility 0.25s;
    z-index: 200;
    background: #ffffff !important;
    border-radius: 16px !important;
    box-shadow: 0 20px 60px rgba(0,0,0,0.12) !important;
    padding: 28px !important;
}

/* HOVER: Parent -> Child (bulletproof) */
.mal-pa-wrapper:hover .mal-mega-panel,
.mal-pa-wrapper:hover .mal-mega-panel.e-con,
.mal-mega-panel:hover,
.mal-mega-panel.e-con:hover {
    opacity: 1 !important;
    visibility: visible !important;
    pointer-events: auto !important;
}
</style>
<?php
}, 999);
```

### Key Lessons Learned

| # | Issue | Root Cause | Fix |
|---|-------|-----------|-----|
| 1 | Mega menu not visible on hover | Sibling CSS selectors broken by Elementor wrappers | Use **parent-child** DOM structure, not siblings |
| 2 | Mega menu clipped/hidden | Elementor sets `overflow: hidden` on `.e-con` | Apply `overflow: visible !important` via mu-plugin to ALL ancestor containers |
| 3 | Elementor `custom_css` ignored or overridden | Elementor's own CSS has higher specificity | Inject CSS via **mu-plugin** with `add_action('wp_head', ..., 999)` |
| 4 | Nav items stacking vertically | Elementor wraps with `.e-con-inner` breaking flex | Target both `.class` and `.class.e-con > .e-con-inner` in CSS |
| 5 | `content_width => 'full'` vs `'boxed'` | `full` removes the `.e-con-inner` wrapper | Use `full` on containers where you need a flat DOM structure |

---

## Step 8: Save Template

Use the `save_template()` function from helpers.php:

```php
$elements = [$hero, $content, $related, $footer_cta];
save_template($TEMPLATE_ID, $elements, 'page');       // Voxel templates
save_template($TEMPLATE_ID, $elements, 'single-post'); // Elementor Pro blog
save_template($TEMPLATE_ID, $elements, 'header');      // Global header
```

### Run the Script

```bash
# Via WP-CLI (required for WordPress functions)
wp eval-file build_header.php --allow-root

# With memory limit increase
php -d memory_limit=512M $(which wp) eval-file build-my-template.php --path=path/to/wordpress
```

> ⚠️ **NEVER run build scripts with bare `php build_header.php`** — WordPress classes like `WP_Query` won't be available. Always use `wp eval-file`.

---

## Step 9: Local Development Server

For local testing without a full Apache/Nginx setup, use PHP's built-in server with a `router.php`:

### router.php

```php
<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false; // serve the file directly
}
require_once __DIR__ . '/index.php';
```

### Start the server

```bash
# WordPress site_url must match this port (set via wp option update siteurl)
php -S localhost:8093 -t . router.php 2>&1
```

> 💡 If WordPress redirects to a different port, it's because `siteurl` or `home` in the database points elsewhere. Fix with:
> ```bash
> wp option update siteurl 'http://localhost:8093'
> wp option update home 'http://localhost:8093'
> ```

---

## Next Step

→ **Stage 3: Verify & Debug** (`stage-3-verify`)
