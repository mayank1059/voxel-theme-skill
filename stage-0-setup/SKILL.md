---
name: stage-0-setup
description: Prerequisites, environment setup, config.php creation, and Material Symbols loading for Voxel + Elementor programmatic template building.
---

# Stage 0: Environment Setup

**Purpose:** Install all prerequisites, copy the helpers.php build library, create a config.php with design tokens, and ensure icon fonts load globally.

> ⚠️ **This stage does NOT create any templates.** It only prepares the environment.

---

## Prerequisites Checklist

### 1. PHP 8.0+

```bash
php --version
# macOS with Homebrew
brew install php@8.2
```

### 2. WP-CLI

```bash
wp --version
# If missing (macOS)
brew install wp-cli
```

### 3. WordPress + Voxel + Elementor

```bash
# Verify plugins & theme
wp plugin list --path=wordpress | grep -E "voxel|elementor"
wp theme list --path=wordpress | grep -i voxel
```

Required:
- Voxel Theme activated
- Elementor (free) installed
- Elementor Pro installed (for blog templates only)

### 4. Database Access

```bash
wp db check --path=wordpress
```

### 5. Copy Helpers Library

```bash
cp .agent/skills/stage-0-setup/scripts/helpers.php ./helpers.php
```

---

## Step 1: Create config.php

Every project needs a centralized config.php for design tokens, template IDs, and utility functions. This is extracted from the client's brand guidelines or the HTML demo design.

```php
<?php
/**
 * Project Configuration — [CLIENT NAME]
 * Design tokens and site-specific settings for Voxel build scripts.
 */

// ═══════════════ PATHS ═══════════════
define('WP_PATH', __DIR__ . '/wordpress');

// ═══════════════ DESIGN TOKENS ═══════════════
$TOKENS = [
    // Colors — extract from demo HTML or brand guide
    'primary'       => '#2145D9',     // Primary — links, headers
    'primary_dark'  => '#1a37ad',     // Dark — hover states, footer bg
    'cta'           => '#ff6b6b',     // CTA buttons
    'cta_hover'     => '#ff4757',
    'bg_light'      => '#F8F9FA',     // Light backgrounds
    'white'         => '#ffffff',
    'text_dark'     => '#1c1917',     // Primary text
    'text_muted'    => '#6b7280',     // Secondary text
    'border'        => '#E5E7EB',     // Borders

    // Typography — identify from demo CSS
    'font_heading'  => 'Montserrat',  // Headlines
    'font_body'     => 'Inter',       // Body text

    // Spacing
    'section_padding_y' => '60',
    'section_padding_x' => '24',
    'content_width' => 1200,
    'card_radius'   => '12',
    'button_radius' => '50',          // Pill buttons
];

// ═══════════════ SHORTHAND ACCESSORS ═══════════════
$OB = $TOKENS['primary'];
$DS = $TOKENS['primary_dark'];
$W  = $TOKENS['white'];
$TD = $TOKENS['text_dark'];
$TM = $TOKENS['text_muted'];
$BDR = $TOKENS['border'];
$FH = $TOKENS['font_heading'];
$FB = $TOKENS['font_body'];

// ═══════════════ TEMPLATE IDS ═══════════════
// Populated after Stage 1 research
$HEADER_ID = 0;
$FOOTER_ID = 0;

// ═══════════════ ALL POST TYPES ═══════════════
$ALL_POST_TYPES = ['post', 'page'];

/**
 * Generate empty filter list arrays for ts-post-feed widget.
 * MUST include every registered post type or feed shows zero results.
 */
function empty_filter_lists() {
    global $ALL_POST_TYPES;
    $filters = [];
    foreach ($ALL_POST_TYPES as $pt) {
        $filters["ts_filter_list__$pt"] = [];
    }
    $filters['ts_manual_posts'] = [];
    return $filters;
}

/**
 * Create a standard boxed section container with consistent spacing.
 */
function boxed_section($children, $bg = null, $width = null) {
    global $TOKENS;
    $settings = [
        'content_width' => 'boxed',
        'boxed_width' => ['size' => $width ?? $TOKENS['content_width'], 'unit' => 'px'],
        'flex_direction' => 'column',
        'flex_gap' => ['size' => 24, 'unit' => 'px'],
        'padding' => [
            'top' => $TOKENS['section_padding_y'], 'right' => $TOKENS['section_padding_x'],
            'bottom' => $TOKENS['section_padding_y'], 'left' => $TOKENS['section_padding_x'],
            'unit' => 'px', 'isLinked' => false,
        ],
    ];
    if ($bg) {
        $settings['background_background'] = 'classic';
        $settings['background_color'] = $bg;
        $settings['__globals__'] = ['background_color' => ''];
    }
    return container($settings, $children);
}

/**
 * Create a CTA button using text-editor (avoids Voxel button override bug).
 */
function cta_button($text, $url, $style = 'primary') {
    global $TOKENS;
    $fb = $TOKENS['font_body'];
    if ($style === 'primary') {
        $css = "display:inline-flex;align-items:center;gap:8px;font-family:$fb,sans-serif;"
            . "font-size:15px;font-weight:700;color:#fff;padding:12px 28px;"
            . "background:{$TOKENS['cta']};border-radius:{$TOKENS['button_radius']}px;"
            . "text-decoration:none;transition:all 0.2s;";
    } else {
        $css = "display:inline-flex;align-items:center;gap:8px;font-family:$fb,sans-serif;"
            . "font-size:14px;font-weight:600;color:#475569;padding:12px 24px;"
            . "border:1.5px solid #e2e8f0;border-radius:{$TOKENS['button_radius']}px;"
            . "text-decoration:none;background:#fff;";
    }
    return widget('text-editor', [
        'editor' => "<a href=\"$url\" style=\"$css\">$text</a>",
    ]);
}
```

---

## Step 2: Load Material Symbols Globally

Google Material Symbols Outlined icons are far more reliable than FontAwesome in Elementor templates (they render inline via a font, no widget needed).

Create an MU plugin to load them site-wide:

```php
<?php
// File: wordpress/wp-content/mu-plugins/project-custom-styles.php
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('material-symbols', 
        'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0',
        [], null);
    wp_enqueue_style('google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@500;600;700;800;900&display=swap',
        [], null);
}, 5);
```

> **Why Material Symbols?** They work anywhere via `<span class="material-symbols-outlined">icon_name</span>` — in headings, text-editors, HTML widgets, custom CSS `::before` content. No dependency on Elementor's icon library or FontAwesome.

Browse icons at: https://fonts.google.com/icons

---

## Step 3: Extract Design Tokens from Demo HTML

If you have a Stitch export or HTML demo file:

```bash
# Extract colors
grep -oP '#[0-9a-fA-F]{6}' demo/index.html | sort | uniq -c | sort -rn | head -20

# Extract font families
grep -oP "font-family:\s*['\"]?([^;\"']+)" demo/index.html | sort -u

# Extract text sizes
grep -oP "font-size:\s*\d+px" demo/index.html | sort | uniq -c | sort -rn
```

Map these into your `config.php` design tokens.

---

## Output

| Tool | Status | Purpose |
|------|--------|---------|
| PHP 8.0+ | ✅ | Runtime for build scripts |
| WP-CLI | ✅ | Database operations |
| WordPress | ✅ | Target CMS |
| Voxel Theme | ✅ | Custom post types + tags |
| Elementor Pro | ✅ | Template rendering |
| helpers.php | ✅ | Build functions library |
| config.php | ✅ | Design tokens + utilities |
| MU plugin | ✅ | Material Symbols + Google Fonts |

---

## Next Step

→ **Stage 1: Research & Discovery** (`stage-1-research`)
