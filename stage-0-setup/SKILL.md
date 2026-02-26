---
name: stage-0-setup
description: Prerequisites and environment setup for Voxel + Elementor programmatic template building. Installs helpers.php, verifies WP-CLI and PHP access.
---

# Stage 0: Environment Setup

**Purpose:** Install all prerequisites and copy the helpers.php build library before starting template work.

> ⚠️ **This stage does NOT create any templates.** It only prepares the environment.

---

## Prerequisites Checklist

### 1. PHP 8.0+

```bash
# Check PHP version
php --version

# macOS with Homebrew
brew install php@8.2
/opt/homebrew/opt/php@8.2/bin/php --version
```

### 2. WP-CLI

```bash
# Check WP-CLI
wp --version

# If missing (macOS)
brew install wp-cli
```

### 3. WordPress + Voxel + Elementor

Verify your WordPress install has:
- Voxel Theme activated
- Elementor (free) installed
- Elementor Pro installed

```bash
# Verify plugins
wp plugin list --path=path/to/wordpress | grep -E "voxel|elementor"
```

### 4. Database Access

```bash
# Test database connection
wp db check --path=path/to/wordpress
```

### 5. Copy Helpers Library

```bash
# Copy helpers.php into your project
cp .agent/skills/voxel-theme-agentic-skill/stage-0-setup/scripts/helpers.php ./helpers.php
```

---

## Environment Verification

```bash
#!/bin/bash
echo "=== Stage 0: Environment Check ==="

# PHP
if command -v php &> /dev/null; then
    echo "✅ PHP: $(php --version | head -1)"
else
    echo "❌ PHP not found"
fi

# WP-CLI
if command -v wp &> /dev/null; then
    echo "✅ WP-CLI: $(wp --version)"
else
    echo "❌ WP-CLI not found"
fi

# WordPress
if wp core is-installed --path=path/to/wordpress 2>/dev/null; then
    echo "✅ WordPress installed"
else
    echo "❌ WordPress not found at specified path"
fi

echo "=== Check Complete ==="
```

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

---

## Next Step

→ **Stage 1: Research & Discovery** (`stage-1-research`)
