---
description: Set up a new Voxel + Elementor project with programmatic template building. Discovers site structure, scaffolds build scripts, and generates templates.
---

# Voxel Project Setup Workflow

This workflow sets up programmatic template building for a Voxel + Elementor WordPress site. Follow each stage in order.

## Prerequisites

Ask the user for:
1. **WordPress path** — where is the WordPress installation? (e.g., `./wordpress`, `../site/wordpress`)
2. **Which post types** to build templates for (or "all")
3. **Design preferences** — brand colors, fonts, and general style direction

## Stage 0: Environment Setup

// turbo
1. Verify PHP is installed:
```bash
php --version
```

// turbo
2. Verify WP-CLI is installed:
```bash
wp --version
```

// turbo
3. Verify WordPress is accessible:
```bash
wp core is-installed --path=WORDPRESS_PATH
```

// turbo
4. Copy the skill's helpers.php and config.php to the project root:
```bash
cp .agent/skills/voxel-theme-skill/boilerplate/helpers.php ./helpers.php
cp .agent/skills/voxel-theme-skill/boilerplate/config.php ./config.php
```

// turbo
5. Copy all build script templates:
```bash
cp .agent/skills/voxel-theme-skill/boilerplate/build-single.php ./
cp .agent/skills/voxel-theme-skill/boilerplate/build-card.php ./
cp .agent/skills/voxel-theme-skill/boilerplate/build-archive.php ./
cp .agent/skills/voxel-theme-skill/boilerplate/build-blog.php ./
cp .agent/skills/voxel-theme-skill/boilerplate/discover.php ./
```

// turbo
6. Install the MU-plugin for smart cleanup:
```bash
mkdir -p WORDPRESS_PATH/wp-content/mu-plugins
cp .agent/skills/voxel-theme-skill/boilerplate/mu-plugins/smart-cleanup.php WORDPRESS_PATH/wp-content/mu-plugins/
```

## Stage 1: Auto-Discovery

7. Run the discovery script to scan the entire site:
```bash
php -d memory_limit=512M $(which wp) eval-file discover.php --path=WORDPRESS_PATH
```

8. Read the discovery output carefully. It will show:
   - All Voxel post types and their template IDs (single, card, archive, form)
   - All custom fields per post type with their dynamic tag syntax
   - Elementor Pro Theme Builder templates (header, footer, single-post for blogs)
   - Search filter configuration status
   - A ready-to-paste config snippet

9. Update `config.php` with the discovered values:
   - Paste the `$TEMPLATE_IDS` array
   - Paste the `$ALL_POST_TYPES` array
   - Paste the `$POST_TYPES` array
   - Update design tokens (colors, fonts) based on user preferences

## Stage 2: Build Templates

Before building, read the Stage 2 SKILL.md for all patterns and pitfalls:
```
.agent/skills/voxel-theme-skill/stage-2-build/SKILL.md
```

10. For each post type the user wants to build, customize the build scripts:
    - Open the appropriate `build-*.php` file
    - Set the `$TEMPLATE_ID` from the discovery output
    - Set the `$POST_TYPE` slug
    - Replace all field keys (marked with `← CUSTOMIZE` comments) with the actual field keys from discovery
    - Adjust sections, add/remove as needed

11. Run each build script:
```bash
php -d memory_limit=512M $(which wp) eval-file build-single.php --path=WORDPRESS_PATH
php -d memory_limit=512M $(which wp) eval-file build-card.php --path=WORDPRESS_PATH
php -d memory_limit=512M $(which wp) eval-file build-archive.php --path=WORDPRESS_PATH
```

12. For blog templates (if site has a blog):
```bash
php -d memory_limit=512M $(which wp) eval-file build-blog.php --path=WORDPRESS_PATH
```

## Stage 3: Verify

Before verifying, read Stage 3 SKILL.md for the full debug guide:
```
.agent/skills/voxel-theme-skill/stage-3-verify/SKILL.md
```

// turbo
13. Check that templates render correctly:
```bash
curl -s "SITE_URL/sample-post/?v=$(date +%s)" | grep -oE "theme-post-title|ts-post-feed|heading|text-editor|image" | sort | uniq -c | sort -rn
```

// turbo
14. Check that no raw @tags() remain (should output 0):
```bash
curl -s "SITE_URL/sample-post/" | grep -c '@tags()'
```

15. Open the site in a browser and visually verify:
    - Images render (not placeholders)
    - Titles show dynamic content
    - Post feeds populate with cards
    - Colors and fonts match design tokens
    - Layout is responsive

16. If issues found, refer to the pitfalls table in `stage-3-verify/SKILL.md` and fix. Common fixes:
    - **No results in feed?** → Add missing `ts_filter_list__<type>` empty arrays
    - **Colors wrong?** → Add `'__globals__' => ['key' => '']`
    - **Image placeholder?** → Set BOTH `url` and `id` with dynamic tags
    - **Changes not showing?** → Clear cache: `wp eval '\Elementor\Plugin::$instance->files_manager->clear_cache();' --path=WORDPRESS_PATH`

## Done

All templates are built and verified. The user can now:
- Continue customizing individual sections
- Add new post types by duplicating build scripts
- Push to production
