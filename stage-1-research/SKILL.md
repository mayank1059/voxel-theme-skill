---
name: stage-1-research
description: Discover template IDs, custom fields, widget types, and active templates for a Voxel + Elementor WordPress site before building.
---

# Stage 1: Research & Discovery

**Purpose:** Map out the target site's template architecture, custom fields, and widget ecosystem before writing any build scripts. Also analyze the HTML demo to extract the exact design patterns and section order needed.

> ⚠️ **This stage is READ-ONLY.** No templates are modified — only information is gathered.

---

## Step 1: Discover Post Types & Template IDs

Every Voxel custom post type has 4 templates (single, card, archive, form):

```bash
wp eval '
$pt = json_decode(get_option("voxel:post_types", "{}"), true);
foreach ($pt as $key => $config) {
    if (!isset($config["templates"])) continue;
    echo "$key:\n";
    foreach ($config["templates"] as $tpl => $id) echo "  $tpl => $id\n";
}
' --path=wordpress
```

**Record all template IDs.** You'll need these in Stage 2.

---

## Step 2: Check for Elementor Pro Overrides

Elementor Pro Theme Builder templates override Voxel templates for standard WordPress post types (blog posts, pages). This is the #1 source of "my changes don't show up" bugs.

```bash
curl -s YOUR_URL | grep 'data-elementor-type.*data-elementor-id'
```

> **If you see `single-post` type:** This page uses Elementor Pro Theme Builder, NOT Voxel templates. Target that ID.

---

## Step 3: Discover Custom Fields

List all fields for a Voxel post type:

```bash
wp eval '
$pt = json_decode(get_option("voxel:post_types", "{}"), true);
$fields = $pt["YOUR_POST_TYPE"]["fields"] ?? [];
foreach ($fields as $f) {
    $key = $f["key"] ?? "?";
    $type = $f["type"] ?? "?";
    $label = $f["label"] ?? $key;
    echo "$type | $key | $label\n";
}
' --path=wordpress
```

### Field Type → Dynamic Tag Mapping (Complete Reference)

| Field Type | Tag Syntax | Notes |
|-----------|------------|-------|
| text, textarea, description | `@post(field-key)` | Direct value |
| number | `@post(field-key)` | Returns number as string |
| image | `@post(field-key)` for URL, `@post(field-key.id)` for attachment ID | **Both required for image widget** |
| image (gallery) | `@post(field-key.ids)` | Comma-separated IDs for gallery widgets |
| url | `@post(field-key)` | Full URL string |
| date | `@post(field-key)` | Formatted date |
| **taxonomy** | `@post(field-key.label)` | ⚠️ Must use `.label` to display the human-readable term name |
| **select** | `@post(field-key.label)` | ⚠️ Must use `.label` — without it you get the raw value slug |
| **switcher** | `@post(field-key).is_checked().then(X).else(Y)` | Conditional rendering |
| **location** | `@post(field-key.address)` | Full address string |
| location (short) | `@post(field-key.short_address)` | City + Country only |
| location (lat/lng) | `@post(field-key.lat)`, `@post(field-key.lng)` | Coordinates |
| post-relation | `@post(field-key.:title)` | **Must use nested property** |
| post-relation URL | `@post(field-key.:url)` | Related post's permalink |
| post-relation field | `@post(field-key.sub-field)` | Access any field on related post |
| **repeater** | `_voxel_loop` setting on container | See Stage 2 for loop pattern |
| repeater sub-field | `@post(repeater-key.sub-field-key)` | Inside a looped container |

### Critical `.label` Gotcha

**Taxonomy and select fields REQUIRE `.label` to display readable values:**

```php
// ❌ WRONG — renders raw slug like "group" or term ID
tag('@post(lesson-type)')
tag('@post(skill-level)')

// ✅ CORRECT — renders "Group" or "Advanced"
tag('@post(lesson-type.label)')  
tag('@post(skill-level.label)')
```

### Switcher Conditional Pattern

```php
// Renders "Available" if checked, "Not available" if unchecked
tag('@post(pickup-service).is_checked().then(Available).else(Not available)')
```

---

## Step 4: Discover Repeater Fields

Repeater fields are the most complex. Check their sub-field structure:

```bash
wp eval '
$pt = json_decode(get_option("voxel:post_types", "{}"), true);
$fields = $pt["YOUR_POST_TYPE"]["fields"] ?? [];
foreach ($fields as $f) {
    if ($f["type"] !== "repeater") continue;
    echo "Repeater: {$f["key"]} ({$f["label"]})\n";
    foreach ($f["fields"] ?? [] as $sf) {
        echo "  - {$sf["type"]} | {$sf["key"]} | {$sf["label"]}\n";
    }
}
' --path=wordpress
```

Verify that repeater data exists in the database:

```bash
wp eval '
$posts = get_posts(["post_type" => "YOUR_TYPE", "numberposts" => 1]);
$val = get_post_meta($posts[0]->ID, "YOUR_REPEATER_KEY", true);
echo $val . "\n";
' --path=wordpress
```

Repeater data is stored as a JSON array: `[{"heading":"Step 1","content":"...","minutes":"20"},...]`

---

## Step 5: Analyze the HTML Demo

If you have a Stitch HTML export or any reference design:

### 5a. Document the Section Order

Scroll through the demo and record every section from top to bottom:

```
1. Header (sticky, white bg, logo | nav | auth buttons)
2. Hero (full-width bg image, overlay gradient, badges, title, price)
3. Quick Info Bar (4 stat boxes with icons)
4. Two-Column Content (65% left / 35% right sidebar)
   Left: About → Lesson Structure → What's Included/Bring → Notice
   Right: Booking Card → Need Help Card
5. Photo Gallery
6. Reviews
7. Related Lessons (post feed)
8. Footer
```

### 5b. Extract Design Tokens

```bash
# Colors from demo
grep -oP 'color:\s*#[0-9a-fA-F]{3,8}' demo/index.html | sort -u
grep -oP 'background:\s*#[0-9a-fA-F]{3,8}' demo/index.html | sort -u

# Font families
grep -oP "font-family:[^;]+" demo/index.html | sort -u

# Border radius values  
grep -oP "border-radius:\s*[^;]+" demo/index.html | sort -u
```

### 5c. Identify Icon System

Check which icons the demo uses:
- Material Symbols Outlined (`material-symbols-outlined`)
- FontAwesome (`fa-solid`, `fa-regular`)  
- SVG inline icons
- Emoji (avoid — looks unprofessional)

---

## Step 6: Sample Data Check

Verify posts actually have data in the fields you plan to display:

```bash
wp eval '
$posts = get_posts(["post_type" => "YOUR_TYPE", "numberposts" => 1, "post_status" => "publish"]);
$p = $posts[0];
echo "Title: " . $p->post_title . "\n";
echo "URL: " . get_permalink($p) . "\n";
$meta = get_post_meta($p->ID);
foreach(["field1","field2","field3"] as $key) {
    $v = $meta[$key][0] ?? "EMPTY";
    echo "$key: " . substr($v, 0, 80) . "\n";
}
' --path=wordpress
```

---

## Output

After completing Stage 1 you should have documented:

| Item | Example |
|------|---------|
| Post type slug | `lessons` |
| Single template ID | `16` |
| Card template ID | `17` |
| Archive template ID | `18` |
| Template type | `page` (Voxel) or `single-post` (Elementor Pro) |
| Custom fields list | `cover-image (image), price (number), lesson-type (select)...` |
| Repeater fields | `lesson-structure: heading (text), content (text), minutes (text)` |
| Taxonomy fields | `skill-level → lesson_skill_level, activity → lesson_activity` |
| Sample post URL | `http://localhost:8082/?lessons=advanced-wingfoil-masterclass-tarifa` |
| Active header/footer IDs | `header=5, footer=6` |
| Demo section order | `Hero → Quick Info → Content (2-col) → Gallery → Reviews → Related` |
| Design tokens | Colors, fonts, spacing, border-radius |

---

## Next Step

→ **Stage 2: Build Templates** (`stage-2-build`)
