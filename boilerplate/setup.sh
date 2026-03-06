#!/bin/bash
# ╔══════════════════════════════════════════════╗
# ║   VOXEL PROJECT SETUP — One-Command Start    ║
# ╚══════════════════════════════════════════════╝
#
# Usage: bash setup.sh path/to/wordpress
#
# What it does:
#   1. Verifies PHP + WP-CLI are installed
#   2. Checks WordPress is accessible
#   3. Copies helpers.php
#   4. Copies config.php template
#   5. Copies all build script templates
#   6. Runs discover.php to auto-detect site structure
#   7. Installs the MU-plugin for smart cleanup

set -e

WP_PATH="${1:-.}"

echo "╔══════════════════════════════════════════════╗"
echo "║           Voxel Build System Setup            ║"
echo "╚══════════════════════════════════════════════╝"
echo ""

# ── 1. Check PHP ──
if command -v php &> /dev/null; then
    echo "✅ PHP: $(php --version | head -1)"
else
    echo "❌ PHP not found. Install with: brew install php@8.2"
    exit 1
fi

# ── 2. Check WP-CLI ──
if command -v wp &> /dev/null; then
    echo "✅ WP-CLI: $(wp --version)"
else
    echo "❌ WP-CLI not found. Install with: brew install wp-cli"
    exit 1
fi

# ── 3. Check WordPress ──
if wp core is-installed --path="$WP_PATH" 2>/dev/null; then
    echo "✅ WordPress: $(wp core version --path="$WP_PATH")"
else
    echo "❌ WordPress not found at: $WP_PATH"
    echo "   Usage: bash setup.sh path/to/wordpress"
    exit 1
fi

# ── 4. Check Voxel ──
VOXEL=$(wp theme list --status=active --path="$WP_PATH" --format=csv 2>/dev/null | grep -i voxel || true)
if [ -n "$VOXEL" ]; then
    echo "✅ Voxel Theme: active"
else
    echo "⚠️  Voxel Theme not detected as active theme"
fi

# ── 5. Check Elementor ──
ELEM=$(wp plugin list --status=active --path="$WP_PATH" --format=csv 2>/dev/null | grep elementor || true)
if [ -n "$ELEM" ]; then
    echo "✅ Elementor: active"
else
    echo "⚠️  Elementor not detected"
fi

echo ""
echo "── Setting up project files ──"

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

# Copy helpers
cp "$SCRIPT_DIR/helpers.php" ./helpers.php 2>/dev/null || cp "$SCRIPT_DIR/../stage-0-setup/scripts/helpers.php" ./helpers.php
echo "✅ helpers.php copied"

# Copy config template (don't overwrite if exists)
if [ ! -f "./config.php" ]; then
    cp "$SCRIPT_DIR/config.php" ./config.php
    echo "✅ config.php template created"
else
    echo "⏭️  config.php already exists (skipping)"
fi

# Copy build scripts (don't overwrite)
for f in build-single.php build-card.php build-archive.php build-blog.php; do
    if [ ! -f "./$f" ]; then
        cp "$SCRIPT_DIR/$f" ./ 2>/dev/null && echo "✅ $f copied" || true
    else
        echo "⏭️  $f already exists (skipping)"
    fi
done

# Copy discover.php
cp "$SCRIPT_DIR/discover.php" ./discover.php 2>/dev/null && echo "✅ discover.php copied" || true

# Install MU-plugin (don't overwrite)
MU_DIR="$WP_PATH/wp-content/mu-plugins"
mkdir -p "$MU_DIR"
if [ ! -f "$MU_DIR/smart-cleanup.php" ]; then
    cp "$SCRIPT_DIR/mu-plugins/smart-cleanup.php" "$MU_DIR/"
    echo "✅ MU-plugin installed: smart-cleanup.php"
else
    echo "⏭️  MU-plugin already installed (skipping)"
fi

echo ""
echo "── Running auto-discovery ──"
echo ""

# Run discovery
wp eval-file ./discover.php --path="$WP_PATH" 2>/dev/null || php -d memory_limit=512M $(which wp) eval-file ./discover.php --path="$WP_PATH"

echo ""
echo "╔══════════════════════════════════════════════╗"
echo "║               Setup Complete!                 ║"
echo "╠══════════════════════════════════════════════╣"
echo "║  Next steps:                                  ║"
echo "║  1. Edit config.php with discovered values    ║"
echo "║  2. Customize build-single.php field keys     ║"
echo "║  3. Run: wp eval-file build-single.php        ║"
echo "╚══════════════════════════════════════════════╝"
