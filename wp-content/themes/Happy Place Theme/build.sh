#!/bin/bash

# Happy Place Theme Build Script
# This script compiles SCSS to CSS and handles the build process

echo "🏗️  Happy Place Theme Build Process Starting..."

# Set paths
THEME_DIR="/Users/patrickgallagher/Local Sites/tpgv12/app/public/wp-content/themes/Happy Place Theme"
SCSS_DIR="$THEME_DIR/assets/src/scss"
CSS_DIR="$THEME_DIR/assets/dist/css"
JS_SRC_DIR="$THEME_DIR/assets/src/js"
JS_DIST_DIR="$THEME_DIR/assets/dist/js"

# Create dist directories if they don't exist
mkdir -p "$CSS_DIR"
mkdir -p "$JS_DIST_DIR"

echo "📁 Directories created"

# Compile main SCSS to CSS
echo "🎨 Compiling main.scss..."
npx sass "$SCSS_DIR/main.scss" "$CSS_DIR/main.css" --style=compressed --no-source-map

# Compile single-listing SCSS to CSS
echo "🎨 Compiling single-listing.scss..."
npx sass "$SCSS_DIR/single-listing.scss" "$CSS_DIR/single-listing.css" --style=compressed --no-source-map

# Copy JavaScript files
echo "📦 Copying JavaScript files..."
if [ -f "$JS_SRC_DIR/main.js" ]; then
    cp "$JS_SRC_DIR/main.js" "$JS_DIST_DIR/main.js"
    echo "✅ main.js copied"
fi

if [ -f "$JS_SRC_DIR/single-listing.js" ]; then
    cp "$JS_SRC_DIR/single-listing.js" "$JS_DIST_DIR/single-listing.js"
    echo "✅ single-listing.js copied"
fi

# Copy all JS modules
if [ -d "$JS_SRC_DIR/modules" ]; then
    cp -r "$JS_SRC_DIR/modules" "$JS_DIST_DIR/"
    echo "✅ JavaScript modules copied"
fi

if [ -d "$JS_SRC_DIR/components" ]; then
    cp -r "$JS_SRC_DIR/components" "$JS_DIST_DIR/"
    echo "✅ JavaScript components copied"
fi

# Create a simple manifest.json for asset versioning
echo "📝 Creating manifest.json..."
TIMESTAMP=$(date +%s)
cat > "$THEME_DIR/assets/dist/manifest.json" << EOF
{
  "main.css": "css/main.css?v=$TIMESTAMP",
  "main.js": "js/main.js?v=$TIMESTAMP",
  "single-listing.css": "css/single-listing.css?v=$TIMESTAMP",
  "single-listing.js": "js/single-listing.js?v=$TIMESTAMP"
}
EOF

echo "✅ Build completed successfully!"
echo "📊 Files generated:"
echo "   • $CSS_DIR/main.css"
echo "   • $CSS_DIR/single-listing.css"
echo "   • $JS_DIST_DIR/main.js"
echo "   • $JS_DIST_DIR/single-listing.js"
echo "   • assets/dist/manifest.json"

# Optional: Clear WordPress cache if WP-CLI is available
if command -v wp &> /dev/null; then
    echo "🧹 Clearing WordPress cache..."
    cd "$THEME_DIR/../../../.."
    wp cache flush 2>/dev/null || echo "ℹ️  Cache flush skipped (not in WordPress root or WP-CLI not available)"
fi

echo "🎉 Happy Place Theme build process complete!"
