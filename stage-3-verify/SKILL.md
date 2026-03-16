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
curl -s "YOUR_URL?v=$(date +%s)" | grep -oE "theme-post-title|theme-post-content|ts-post-feed|author-box|post-info|heading|text-editor|ts-gallery" | sort | uniq -c | sort -rn
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

### Check Material Symbols loaded

```bash
curl -s "YOUR_URL" | grep -c "material-symbols"
# Should be ≥ 1 (stylesheet link)
```

---

## Step 2: Visual Verification

Open the page in a browser and check:

- [ ] Featured/cover image renders (not placeholder)
- [ ] Title shows dynamic post title (not raw `@tags`)
- [ ] Content/description populates
- [ ] Images from custom fields load
- [ ] Post feed shows cards with data
- [ ] Links are clickable and correct
- [ ] Layout is responsive on mobile
- [ ] Colors match design tokens
- [ ] Material Symbols icons render (not empty squares or raw text)
- [ ] Repeater loops render all entries (e.g., lesson structure steps)
- [ ] Gallery shows actual images (not empty grid)
- [ ] Taxonomy/select fields show labels (not raw slugs)
- [ ] Sidebar cards have box shadows and rounded corners
- [ ] Hero overlay gradient is visible (not too light or too dark)
- [ ] Badge backgrounds are visible against the hero image
- [ ] Header is sticky and stays on top when scrolling
- [ ] Footer columns are horizontal (not stacked vertically)

### Browser-Based Verification

Use a browser automation tool to capture screenshots at key scroll positions:

1. **Hero section** — verify image, overlay, badges, title, price
2. **Quick info bar** — verify stat boxes, icons, values
3. **Content area** — verify two-column layout, sidebar position
4. **Repeater sections** — verify timeline steps, inclusion lists
5. **Gallery** — verify images render in grid
6. **Related posts** — verify card feed renders
7. **Footer** — verify horizontal column layout

---

## Step 3: Cache Clearing

If changes don't appear, clear caches in order:

### Method 1: Programmatic (already handled by `save_template()`)

The `save_template()` function in helpers.php already clears:
- `_elementor_css` post meta
- `_elementor_controls_usage` post meta
- Post cache
- Elementor CSS files
- Old revisions

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

### Method 3: Nuclear clear

```bash
# Delete all Elementor CSS files
rm -rf wordpress/wp-content/uploads/elementor/css/*

# Then regenerate
wp eval '\Elementor\Plugin::$instance->files_manager->clear_cache();' --path=wordpress
```

### Method 4: Hard-refresh the browser

After clearing server caches, always hard-refresh the browser:
- **Mac:** `Cmd + Shift + R`
- **Windows:** `Ctrl + Shift + R`

Or append `?v=123` to the URL.

---

## Step 4: Debug Common Issues

### Template changes don't show up

1. **Wrong template ID** — run `curl | grep data-elementor-id` to find the real active template
2. **Elementor Pro Theme Builder override** — blog posts use Elementor Pro, not Voxel templates
3. **Cache not cleared** — run nuclear clear above
4. **Browser cache** — hard-refresh or use incognito mode

### Featured image shows placeholder.png

- **Cause:** `position: absolute` CSS on `theme-post-featured-image`
- **Fix:** Remove absolute positioning. Use `max-height` + `object-fit: cover`

### Image widget shows broken image

- **Cause:** Missing `.id` suffix
- **Fix:** Set BOTH `url` and `id`: `field('logo')` and `field('logo.id')`

### Post feed shows no results

- **Cause:** Missing `ts_filter_list__<type>` empty arrays
- **Fix:** Add empty arrays for EVERY registered post type (lessons, schools, post, page, etc.)

### Gallery widget is empty

- **Cause:** Not using `.ids` suffix
- **Fix:** Use `@post(gallery.ids)` not `@post(gallery)`

### Post relation field renders nothing

- **Cause:** Object tag needs nested property
- **Fix:** `@post(field.:title)` not `@post(field)`

### Colors are wrong / ignored

- **Cause:** Missing `__globals__` override
- **Fix:** Add `'__globals__' => ['color_key' => '']` alongside the color value
- **Also check:** Widget-level colors use `_background_color` (with underscore prefix). Must clear `'__globals__' => ['_background_color' => '']`

### Flex children stack vertically instead of side-by-side

- **Cause:** Missing explicit width on child containers
- **Fix:** Add `'width' => ['size' => 48, 'unit' => '%']` to each child
- **Alternative:** Use `custom_css` with explicit flex values

### Taxonomy/select shows raw slug instead of label

- **Cause:** Using `@post(field-key)` without `.label` suffix
- **Fix:** Use `@post(field-key.label)` for taxonomy and select fields

### Repeater loop renders nothing

- **Cause 1:** `_voxel_loop` value is wrong — must be exact field key
- **Cause 2:** Repeater data not properly saved (must be JSON array, `wp_slash`'d)
- **Fix:** Check `wp eval 'echo get_post_meta(POST_ID, "field-key", true);'` — should output valid JSON array
- **Fix:** When saving repeater data, always use `wp_slash(wp_json_encode($data))`

### Repeater sub-fields render empty

- **Cause:** Wrong sub-field key. Inside a loop, access as `@post(repeater-key.sub-field-key)`
- **Fix:** Verify sub-field keys match the field configuration exactly

### Material Symbols icons don't render (show empty or squares)

- **Cause:** Font not loaded
- **Fix:** Ensure the MU plugin is in place or add the `@import` in header/footer `custom_css`
- **Font URL:** `https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0`

### Header/footer style broken after rebuild 

- **Cause:** Voxel stores internal configs in header/footer templates
- **Fix:** If the header/footer already has Voxel-specific internal settings, use CSS-only overrides via MU plugin rather than rebuilding the template. For fresh sites, full rebuild is safe.

### Location field renders empty

- **Cause:** Using `@post(location)` — renders raw JSON
- **Fix:** Use `@post(location.address)` for full address or `@post(location.short_address)` for city

### Badge backgrounds invisible against hero

- **Cause:** Using light/white transparent backgrounds on dark overlays
- **Fix:** Use `rgba(0,0,0,0.45)` for dark semi-transparent badges, or solid teal for primary badges

### Hero overlay too light or too dark

- **Cause:** Wrong gradient values in `custom_css`
- **Fix:** Adjust the `::before` gradient. For a dramatic hero effect:
  ```css
  selector::before { background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.60) 50%, rgba(0,0,0,0.50) 100%) !important; }
  ```

### Footer columns stacking vertically

- **Cause:** No explicit width on column children
- **Fix:** Add `width` to each child inner container AND add safety CSS:
  ```php
  'custom_css' => 'selector { display: flex !important; flex-direction: row !important; }'
  ```

---

## Quick Reference: All Pitfalls

| # | Symptom | Root Cause | Fix |
|---|---------|-----------|-----|
| 1 | PHP warnings in controls-stack | Used `__dynamic__` | Use `@tags()@post()@endtags()` |
| 2 | JSON corruption after save | Missing `wp_slash()` | Always `wp_slash()` before `update_post_meta` |
| 3 | Button shows solid bg instead of themed | Voxel overrides Elementor button | Use `text-editor` + styled `<a>` or `custom_css` gradient override |
| 4 | Children stack vertically | No explicit width on inner() | Add `width` percentage or `custom_css` flex |
| 5 | Image shows placeholder | Missing both url AND id | Set `url` + `id` with dynamic tags |
| 6 | Post relation renders empty | Object tag, not value tag | Use `@post(field.:title)` |
| 7 | Gallery widget empty | Wrong suffix | Use `.ids` suffix: `@post(gallery.ids)` |
| 8 | Blog template not applying | Elementor Pro overrides Voxel | Check which template is active with curl |
| 9 | Custom colors ignored | Global kit override | Add `'__globals__' => ['key' => '']` |
| 10 | Featured image = placeholder.png | position:absolute CSS | Use max-height + object-fit |
| 11 | Feed shows zero results | Missing filter list arrays | Add empty `ts_filter_list__<type>` for ALL types |
| 12 | Header/footer broke after rebuild | Internal Voxel configs lost | CSS overrides via MU plugin instead |
| 13 | Background image from field won't load | `@tags()` unreliable in CSS bg | Use image widget or background_image setting |
| 14 | Taxonomy shows raw slug | Missing `.label` suffix | Use `@post(field.label)` |
| 15 | Select shows raw value | Missing `.label` suffix | Use `@post(field.label)` |
| 16 | Repeater loop empty | Bad `_voxel_loop` value or bad JSON | Verify field key + `wp_slash(wp_json_encode())` |
| 17 | Location shows JSON | Using `@post(location)` | Use `@post(location.address)` |
| 18 | Icons are empty squares | Material Symbols font not loaded | Add MU plugin or `@import` in custom_css |
| 19 | Badges invisible on hero | Light bg on dark overlay | Use `rgba(0,0,0,0.45)` for dark badges |
| 20 | Hero overlay wrong intensity | Bad gradient values | Adjust `::before` linear-gradient stops |

---

## Recommended Build Order

For a complete site from an HTML demo:

1. **Setup** — `setup-post-types.php` (fields, taxonomies, search filters)
2. **Images** — Generate/find images, sideload into WordPress
3. **Demo Content** — `create-demo-content.php` (posts with all fields populated)
4. **Card Template** — Build first (simplest, feeds into other pages)
5. **Single Template** — Build second (references card template indirectly via related feed)
6. **Archive Template** — Build third (uses card template via post feed)
7. **Header** — Build with sticky + nav links
8. **Footer** — Build with dark bg + multi-column
9. **Verify All** — Check every page renders, clear caches

---

## Output

After Stage 3, your template should:
- ✅ Render all dynamic content from custom fields
- ✅ Display images correctly (not placeholders)
- ✅ Show post feeds with populated cards
- ✅ Render repeater loops (timelines, lists)
- ✅ Display taxonomy/select labels (not slugs)
- ✅ Have correct colors, fonts, and spacing
- ✅ Show Material Symbols icons correctly
- ✅ Work on mobile/tablet viewports
- ✅ Pass the visual checklist above

**Template is production-ready.**
