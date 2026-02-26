# Voxel Theme Agentic Skill

Build WordPress templates programmatically with Voxel Theme + Elementor.
**No clicking. Pure code. Full automation.**

An [Antigravity](https://developers.google.com/gemini/antigravity) skill for building Voxel + Elementor sites using:
- PHP build scripts for template generation
- WP-CLI for database operations
- Voxel dynamic tags for content binding
- Elementor JSON for layout structure

---

## 🚀 Installation

```bash
# Clone into your project
mkdir -p .agent/skills
git clone https://github.com/mayank1059/voxel-theme-agentic-skill.git .agent/skills/voxel-theme-agentic-skill
```

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

| Stage | Skill | Purpose | Time |
|-------|-------|---------|------|
| **0** | `stage-0-setup` | Install WP-CLI, PHP, create helpers.php | 15 min |
| **1** | `stage-1-research` | Discover template IDs, fields, widgets | 30-60 min |
| **2** | `stage-2-build` | Write build scripts, generate templates | 2-6 hrs |
| **3** | `stage-3-verify` | Test rendering, debug, clear caches | 30-60 min |

**Total per template: 3-8 hours**

## 📁 Repository Structure

```
voxel-theme-agentic-skill/
├── README.md                           # This file
│
├── stage-0-setup/                      # Prerequisites
│   ├── SKILL.md                        # Setup instructions
│   ├── scripts/helpers.php             # Core build functions
│   └── resources/checklist.md          # Environment checklist
│
├── stage-1-research/                   # Discovery
│   ├── SKILL.md                        # How to discover template info
│   └── resources/
│       ├── field-discovery.md          # WP-CLI commands for fields
│       └── widget-reference.md         # All widget types + settings
│
├── stage-2-build/                      # Template building
│   ├── SKILL.md                        # Build patterns + pitfalls
│   ├── scripts/helpers.php             # Copy of helpers (convenience)
│   └── examples/
│       └── build-example.php           # Example build script
│
└── stage-3-verify/                     # Testing
    ├── SKILL.md                        # Debug + cache clearing
    └── resources/pitfalls.md           # Common pitfalls quick reference
```

## 🔧 Prerequisites

- PHP 8.0+ (Homebrew or system)
- WP-CLI
- WordPress with Voxel Theme + Elementor Pro
- Local or remote MySQL database

## 📖 Usage

### Start a New Template

```bash
# 1. Copy helpers.php into your project
cp .agent/skills/voxel-theme-agentic-skill/stage-0-setup/scripts/helpers.php ./

# 2. Discover template IDs (Stage 1)
wp eval 'echo json_encode(json_decode(get_option("voxel:post_types","{}"),true));' --path=wordpress | jq

# 3. Write your build script (Stage 2)
# Follow patterns in stage-2-build/examples/

# 4. Run and verify (Stage 3)
wp eval-file build-my-template.php --path=wordpress
```

## 📄 License

MIT

## 🔗 Related

- [Voxel Theme](https://getvoxel.io/)
- [Elementor](https://elementor.com/)
- [Stitch Delivery Skill](https://github.com/mayank1059/stitch-delivery-skill)
