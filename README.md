# Voxel Theme Agentic Skill

Build WordPress templates programmatically with Voxel Theme + Elementor.
**No clicking. Pure code. Full automation.**

An [Antigravity](https://developers.google.com/gemini/antigravity) skill for building Voxel + Elementor sites using:
- PHP build scripts for template generation
- WP-CLI for database operations
- Voxel dynamic tags for content binding
- Elementor JSON for layout structure

---

## 🚀 Quick Start (New Project)

```bash
# 1. Clone the skill
git clone https://github.com/mayank1059/voxel-theme-skill.git .agent/skills/voxel-theme-skill

# 2. Run one-command setup
bash .agent/skills/voxel-theme-skill/boilerplate/setup.sh path/to/wordpress

# 3. Edit config.php with discovered values, then build!
wp eval-file build-single.php --path=path/to/wordpress
```

---

## 📋 The 4 Stages

```
┌─────────────────────────────────────────────────────────────┐
│                VOXEL TEMPLATE BUILD SYSTEM                   │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  STAGE 0          STAGE 1          STAGE 2       STAGE 3    │
│  ┌──────────┐    ┌──────────┐    ┌──────────┐  ┌────────┐  │
│  │  SETUP   │───▶│ RESEARCH │───▶│  BUILD   │─▶│ VERIFY │  │
│  │          │    │          │    │          │  │        │  │
│  │ WP-CLI   │    │ Templates│    │ PHP      │  │ Render │  │
│  │ PHP      │    │ Fields   │    │ Scripts  │  │ Debug  │  │
│  │ Helpers  │    │ Widgets  │    │ JSON     │  │ Cache  │  │
│  └──────────┘    └──────────┘    └──────────┘  └────────┘  │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

| Stage | Folder | Purpose | Time |
|-------|--------|---------|------|
| **0** | `stage-0-setup/` | Install WP-CLI, PHP, create helpers.php | 15 min |
| **1** | `stage-1-research/` | Discover template IDs, fields, widgets | 30-60 min |
| **2** | `stage-2-build/` | Write build scripts, generate templates | 2-6 hrs |
| **3** | `stage-3-verify/` | Test rendering, debug, clear caches | 30-60 min |

---

## 📦 Boilerplate (Start Any Project Instantly)

The `boilerplate/` folder contains everything needed to start a new Voxel project:

| File | Purpose |
|------|---------|
| `setup.sh` | **One-command setup** — verifies env, copies files, runs discovery |
| `discover.php` | **Auto-discovery** — scans site for post types, fields, template IDs |
| `config.php` | **Design tokens** — colors, fonts, spacing + shorthand helpers |
| `helpers.php` | **Core library** — container(), inner(), widget(), tag(), save_template() |
| `build-single.php` | Generic single page template (hero, specs, gallery, related) |
| `build-card.php` | Generic card template (image, title, description, meta, pills) |
| `build-archive.php` | Generic archive/listing page (hero + search + feed grid) |
| `build-blog.php` | Blog post template (Elementor Pro — hero, article, author, comments) |
| `mu-plugins/smart-cleanup.php` | Hide N/A values, collapse duplicate prices |

### Using the Boilerplate

```bash
# Copy everything to your project and run setup
bash boilerplate/setup.sh path/to/wordpress

# Or manually:
cp boilerplate/helpers.php ./
cp boilerplate/config.php ./
cp boilerplate/build-single.php ./
wp eval-file discover.php --path=path/to/wordpress
# Edit config.php with discovered values
wp eval-file build-single.php --path=path/to/wordpress
```

---

## 📁 Repository Structure

```
voxel-theme-skill/
├── README.md                              # This file
├── REFERENCE.md                           # Full knowledge base (793 lines)
│
├── stage-0-setup/                         # Stage 0: Prerequisites
│   ├── SKILL.md
│   └── scripts/helpers.php
│
├── stage-1-research/                      # Stage 1: Discovery
│   └── SKILL.md
│
├── stage-2-build/                         # Stage 2: Build patterns
│   ├── SKILL.md
│   ├── scripts/helpers.php
│   └── examples/build-example.php
│
├── stage-3-verify/                        # Stage 3: Testing & debug
│   └── SKILL.md
│
└── boilerplate/                           # 🚀 Project starter kit
    ├── setup.sh                           # One-command setup
    ├── discover.php                       # Auto-discovery
    ├── config.php                         # Design tokens + helpers
    ├── helpers.php                        # Core build functions
    ├── build-single.php                   # Single page template
    ├── build-card.php                     # Card template
    ├── build-archive.php                  # Archive/listing page
    ├── build-blog.php                     # Blog post template
    └── mu-plugins/
        └── smart-cleanup.php             # N/A hiding + price cleanup
```

---

## 🔧 Prerequisites

- PHP 8.0+
- WP-CLI
- WordPress with Voxel Theme + Elementor Pro

---

## 📄 License

MIT

## 🔗 Related

- [Voxel Theme](https://getvoxel.io/)
- [Elementor](https://elementor.com/)
- [Stitch Delivery Skill](https://github.com/mayank1059/stitch-delivery-skill)
