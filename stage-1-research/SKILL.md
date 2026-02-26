---
name: stage-1-research
description: Discover template IDs, custom fields, widget types, and active templates for a Voxel + Elementor WordPress site before building.
---

# Stage 1: Research & Discovery

**Purpose:** Map out the target site's template architecture, custom fields, and widget ecosystem before writing any build scripts.

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
' --path=path/to/wordpress
```

**Record all template IDs.** You'll need these in Stage 2.

---

## Step 2: Check for Elementor Pro Overrides

Elementor Pro Theme Builder templates override Voxel templates for standard WordPress post types (blog posts, pages). This is the #1 source of "my changes don't show up" bugs.

**Find what template actually renders on any page:**

```bash
curl -s YOUR_URL | grep 'data-elementor-type.*data-elementor-id'
```

Output shows all active templates:
```
data-elementor-type="header" data-elementor-id="1189"        ← Header
data-elementor-type="single-post" data-elementor-id="1108"   ← Content (Elementor Pro!)
data-elementor-type="footer" data-elementor-id="1193"        ← Footer
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
' --path=path/to/wordpress
```

### Field Type → Dynamic Tag Mapping

| Field Type | Tag Syntax | Notes |
|-----------|------------|-------|
| text, textarea, description | `@post(field-key)` | Direct value |
| number | `@post(field-key)` | Returns number as string |
| image | `@post(field-key)` for URL, `@post(field-key.id)` for attachment ID | **Both required for image widget** |
| image (gallery/list) | `@post(field-key.ids)` | Comma-separated IDs for gallery widgets |
| url | `@post(field-key)` | Full URL string |
| date | `@post(field-key)` | Formatted date |
| taxonomy | `@post(field-key)` | Renders term name(s) |
| select | `@post(field-key)` | Selected value |
| switcher | `@post(field-key)` | "Yes"/"No" |
| post-relation | `@post(field-key.:title)` | **Must use nested property** |
| post-relation URL | `@post(field-key.:url)` | Related post's permalink |
| post-relation field | `@post(field-key.sub-field)` | Access any field on related post |

---

## Step 4: Find Existing Template Structure

Inspect what widgets an existing template uses:

```bash
wp eval '
$data = json_decode(get_post_meta(TEMPLATE_ID, "_elementor_data", true), true);
function outline($elements, $depth=0) {
    foreach($elements as $el) {
        $prefix = str_repeat("  ", $depth);
        $wt = $el["widgetType"] ?? "";
        $info = $wt ? "widget=$wt" : "";
        if(isset($el["settings"]["title"])) $info .= " title=\"" . substr($el["settings"]["title"],0,40) . "\"";
        echo $prefix . ($el["elType"] ?? "?") . " $info\n";
        if(!empty($el["elements"])) outline($el["elements"], $depth+1);
    }
}
if($data) outline($data); else echo "Empty\n";
' --path=path/to/wordpress
```

---

## Step 5: Sample Data Check

Verify posts actually have data in the fields you plan to display:

```bash
wp eval '
$posts = get_posts(["post_type" => "YOUR_TYPE", "numberposts" => 1, "post_status" => "publish"]);
$p = $posts[0];
echo "Title: " . $p->post_title . "\n";
echo "URL: " . get_permalink($p) . "\n";
// Check specific fields
$meta = get_post_meta($p->ID);
foreach(["field1","field2","field3"] as $key) {
    $v = $meta[$key][0] ?? "EMPTY";
    echo "$key: " . substr($v, 0, 80) . "\n";
}
' --path=path/to/wordpress
```

---

## Output

After completing Stage 1 you should have documented:

| Item | Example |
|------|---------|
| Post type slug | `manufacturers` |
| Single template ID | `2226` |
| Card template ID | `2227` |
| Archive template ID | `2228` |
| Template type | `page` (Voxel) or `single-post` (Elementor Pro) |
| Custom fields list | `logo (image), website (url), year-founded (number)...` |
| Sample post URL | `http://localhost:8081/manufacturer/bambu-lab/` |
| Active header/footer IDs | `header=1189, footer=1193` |

---

## Next Step

→ **Stage 2: Build Templates** (`stage-2-build`)
