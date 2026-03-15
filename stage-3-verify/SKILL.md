---
name: stage-3-verify
description: Verify, debug, and cache-clear Voxel + Elementor templates after building. Includes rendering checks, common pitfall diagnosis, and a quick-reference troubleshooting table.
---

# Stage 3: Verify & Debug

**Purpose:** Confirm templates render correctly, diagnose issues, and clear caches.

---

## Step 1: Verify Rendering

### Check that all widgets rendered

```bash
# Fetch the page and look for expected widget markers
curl -s "YOUR_URL?v=$(date +%s)" | grep -oE "theme-post-title|theme-post-content|ts-post-feed|author-box|post-info|heading|text-editor" | sort | uniq -c | sort -rn
```

### Check for dynamic tag output

```bash
# Verify dynamic content is populated (not showing raw @tags)
curl -s "YOUR_URL" | grep -c '@tags()'
# Should output 0 — any non-zero means tags aren't resolving
```

### Check for error indicators

```bash
# Look for Elementor errors or warnings
curl -s "YOUR_URL" | grep -iE "elementor-widget-empty|error|warning|placeholder"
```

---

## Step 2: Visual Verification

Open the page in a browser and check:

- [ ] Featured image renders (not placeholder)
- [ ] Title shows dynamic post title
- [ ] Content/description populates
- [ ] Images from custom fields load
- [ ] Post feed shows cards with data
- [ ] Links are clickable and correct
- [ ] Layout is responsive on mobile
- [ ] Colors match design tokens
- [ ] **Header:** Logo, nav links, and CTA render in a horizontal row
- [ ] **Mega Menu:** Hovering over "Practice Areas" opens the dropdown panel
- [ ] **Mega Menu:** Moving cursor into the dropdown keeps it open
- [ ] **Mega Menu:** Moving cursor away from both trigger and panel closes it
- [ ] **Sticky Header:** Header stays fixed at top on scroll

---

## Step 3: Cache Clearing

If changes don't appear, clear caches in order:

### Method 1: Programmatic (in build script — already handled by `save_template()`)

```php
delete_post_meta($id, '_elementor_css');
delete_post_meta($id, '_elementor_controls_usage');
clean_post_cache($id);
\Elementor\Plugin::$instance->files_manager->clear_cache();
$css = \Elementor\Core\Files\CSS\Post::create($id);
$css->update();
```

### Method 2: WP-CLI

```bash
# Regenerate all Elementor CSS
wp eval '\Elementor\Plugin::$instance->files_manager->clear_cache();' --path=wordpress

# Clear specific template cache
wp eval '
delete_post_meta(TEMPLATE_ID, "_elementor_css");
clean_post_cache(TEMPLATE_ID);
echo "Cache cleared for TEMPLATE_ID\n";
' --path=wordpress

# Clear WordPress object cache
wp cache flush --path=wordpress
```

### Method 3: Full nuclear clear

```bash
# Delete all Elementor CSS files
rm -rf path/to/wordpress/wp-content/uploads/elementor/css/*

# Then regenerate
wp eval '\Elementor\Plugin::$instance->files_manager->clear_cache();' --path=wordpress
```

---

## Step 4: Debug Common Issues

### Template changes don't show up

1. Wrong template ID — run `curl | grep data-elementor-id` to find the real active template
2. Elementor Pro Theme Builder override — blog posts use Elementor Pro, not Voxel templates
3. Cache not cleared — run nuclear clear above

### Featured image shows placeholder.png

- **Cause:** `position: absolute` CSS on `theme-post-featured-image`
- **Fix:** Remove absolute positioning. Use `max-height` + `object-fit: cover`

### Image widget shows broken image

- **Cause:** Missing `.id` suffix
- **Fix:** Set BOTH `url` and `id`: `field('logo')` and `field('logo.id')`

### Post feed shows no results

- **Cause:** Missing `ts_filter_list__<type>` empty arrays
- **Fix:** Add empty arrays for EVERY registered post type

### Gallery widget is empty

- **Cause:** Not using `.ids` suffix
- **Fix:** Use `@post(gallery.ids)` not `@post(gallery)`

### Post relation field renders nothing

- **Cause:** Object tag needs nested property
- **Fix:** `@post(field.:title)` not `@post(field)`

### Colors are wrong / ignored

- **Cause:** Missing `__globals__` override
- **Fix:** Add `'__globals__' => ['color_key' => '']` alongside color value

### Flex children stack vertically instead of side-by-side

- **Cause:** Missing explicit width on child containers
- **Fix:** Add `'width' => ['size' => 48, 'unit' => '%']` to each child

---

## Quick Reference: All Pitfalls

| # | Symptom | Root Cause | Fix |
|---|---------|-----------|-----|
| 1 | PHP warnings in controls-stack | Used `__dynamic__` | Use `@tags()@post()@endtags()` |
| 2 | JSON corruption after save | Missing `wp_slash()` | Always `wp_slash()` before `update_post_meta` |
| 3 | Button shows solid bg instead of transparent | Voxel overrides Elementor button | Use `text-editor` + styled `<a>` |
| 4 | Children stack vertically | No explicit width on inner() | Add `width` percentage to each child |
| 5 | Image shows placeholder | Missing both url AND id | Set `url` + `id` with dynamic tags |
| 6 | Post relation renders empty | Object tag, not value tag | Use `@post(field.:title)` |
| 7 | Gallery widget empty | Wrong suffix | Use `.ids` suffix: `@post(gallery.ids)` |
| 8 | Blog template not applying | Elementor Pro overrides Voxel | Check which template is active with curl |
| 9 | Custom colors ignored | Global kit override | Add `'__globals__' => ['key' => '']` |
| 10 | Featured image = placeholder.png | position:absolute CSS | Use max-height + object-fit |
| 11 | Feed shows zero results | Missing filter list arrays | Add empty `ts_filter_list__<type>` for ALL types |
| 12 | Header/footer broke after rebuild | Internal Voxel configs lost | NEVER rebuild — CSS overrides only |
| 13 | Background image from field won't load | `@tags()` unreliable in CSS bg | Use image widget instead |
| 14 | Mega menu not appearing on hover | Sibling `~` selector broken by `.e-con-inner` wrappers | Use **parent-child** DOM structure (`wrapper > panel`), not siblings |
| 15 | Mega menu clipped/cut off | Elementor `.e-con` sets `overflow: hidden` | Apply `overflow: visible !important` to **ALL** ancestor containers via mu-plugin |
| 16 | `custom_css` on containers not working | Elementor specificity overrides it | Inject CSS via **mu-plugin** (`add_action('wp_head', ..., 999)`) |
| 17 | Nav items vertical instead of horizontal | `.e-con-inner` wrapper breaks flex layout | Target both `.class` and `.class.e-con > .e-con-inner` with `flex-direction: row !important` |
| 18 | `WP_Query not found` error | Ran `php build_x.php` directly | Must use `wp eval-file build_x.php` for WP functions |
| 19 | WordPress redirects to wrong port | `siteurl`/`home` options mismatch | `wp option update siteurl 'http://localhost:PORT'` |
| 20 | Mu-plugin CSS not loading | Wrong file extension or syntax error | File must be `.php`, start with `<?php`, use `add_action('wp_head', ...)` |

---

## Step 5: Debug Header & Mega Menu

### Mega menu doesn't appear on hover

1. **Check DOM structure:** The mega panel must be a **child** of the trigger wrapper, not a sibling.
   ```bash
   # Inspect the DOM — look for the nesting relationship
   curl -s YOUR_URL | grep -A5 'mal-pa-wrapper'
   ```
2. **Check overflow:** All ancestor containers need `overflow: visible !important`.
   ```bash
   # Verify mu-plugin is loaded
   curl -s YOUR_URL | grep 'mal-mega-menu-css'
   ```
3. **Check mu-plugin is active:** Mu-plugins load automatically — no activation needed. But verify the file:
   ```bash
   ls -la wp-content/mu-plugins/mal-mega-menu.php
   wp eval 'var_dump(wp_get_mu_plugins());'
   ```

### Header nav items stacking vertically

- Target both the class and its Elementor wrapper:
  ```css
  .mal-nav-area, .mal-nav-area.e-con, .mal-nav-area > .e-con-inner {
      display: flex !important;
      flex-direction: row !important;
  }
  ```

### Mega menu appears but gets clipped

- Add `overflow: visible !important` to **every** ancestor from `.mal-header` down to `.mal-pa-wrapper`.
- Check with browser DevTools: right-click the mega panel → Inspect → look for any parent with `overflow: hidden`.

---

## Output

After Stage 3, your template should:
- ✅ Render all dynamic content from custom fields
- ✅ Display images correctly (not placeholders)
- ✅ Show post feeds with populated cards
- ✅ Have correct colors, fonts, and spacing
- ✅ Work on mobile/tablet viewports
- ✅ Pass the visual checklist above
- ✅ Header renders horizontally with working mega menu dropdown
- ✅ Mu-plugin CSS loads and overrides Elementor defaults

**Template is production-ready.**
