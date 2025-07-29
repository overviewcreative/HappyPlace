#!/bin/bash
# Consolidated build script for Happy Place Theme
# Handles all asset compilation and optimization

set -e  # Exit on any error

echo "🚀 Building Happy Place Theme Assets..."
echo ""

# Parse command line arguments
WATCH_MODE=false
PRODUCTION_MODE=true

for arg in "$@"; do
    case $arg in
        --watch)
            WATCH_MODE=true
            PRODUCTION_MODE=false
            shift
            ;;
        --dev|--development)
            PRODUCTION_MODE=false
            shift
            ;;
        *)
            # Unknown option
            ;;
    esac
done

# Set build mode
if [ "$PRODUCTION_MODE" = true ]; then
    BUILD_MODE="production"
    echo "🏗️  Production Build Mode"
else
    BUILD_MODE="development"
    echo "🔧 Development Build Mode"
fi

echo ""

# Create dist directory if it doesn't exist
mkdir -p assets/dist/js
mkdir -p assets/dist/css

# Check if node_modules exists, if not create symlink to root
if [ ! -d "node_modules" ] && [ -d "../../../node_modules" ]; then
    echo "🔗 Creating symlink to root node_modules..."
    ln -sf ../../../node_modules node_modules
fi

# Validate syntax of key files first
echo "📋 Validating JavaScript syntax..."

# Core files validation
validate_js() {
    if [ -f "$1" ]; then
        if node -c "$1" 2>/dev/null; then
            echo "✅ $(basename $1) - Valid"
        else
            echo "❌ $(basename $1) - Invalid syntax"
            return 1
        fi
    else
        echo "⚠️  $(basename $1) - Not found"
    fi
}

# Validate main files
validate_js "assets/src/js/main.js"
validate_js "assets/src/js/single-listing.js"
validate_js "assets/src/js/dashboard-entry.js"

# Validate components if they exist
[ -f "assets/src/js/components/NotificationSystem.js" ] && validate_js "assets/src/js/components/NotificationSystem.js"
[ -f "assets/src/js/integrations/BaseIntegration.js" ] && validate_js "assets/src/js/integrations/BaseIntegration.js"

echo ""

# Simple concatenation function
simple_build() {
    # Simple concatenation fallback
    echo "🔨 Concatenating JavaScript files..."
    
    # Main bundle
    {
        echo "/* Happy Place Theme - Main Bundle - Built $(date) */"
        echo ""
        [ -f "assets/src/js/main.js" ] && cat "assets/src/js/main.js"
        echo ""
    } > assets/dist/js/main.js
    
    # Single listing bundle
    {
        echo "/* Happy Place Theme - Single Listing Bundle - Built $(date) */"
        echo ""
        [ -f "assets/src/js/single-listing.js" ] && cat "assets/src/js/single-listing.js"
        echo ""
    } > assets/dist/js/single-listing.js
    
    # Dashboard bundle if exists
    if [ -f "assets/src/js/dashboard-entry.js" ]; then
        {
            echo "/* Happy Place Theme - Dashboard Bundle - Built $(date) */"
            echo ""
            cat "assets/src/js/dashboard-entry.js"
            echo ""
        } > assets/dist/js/dashboard-entry.js
    fi
    
    echo "✅ JavaScript concatenation completed"
}

# Use simple concatenation for reliable builds
echo "📄 Using simple concatenation build..."
simple_build

# Build SCSS using npx sass
if command -v npx >/dev/null 2>&1; then
    echo ""
    echo "🎨 Building SCSS..."
    
    if [ -f "assets/src/scss/main.scss" ]; then
        if [ "$BUILD_MODE" = "production" ]; then
            npx sass assets/src/scss/main.scss assets/dist/css/main.css --style=compressed --no-source-map
        else
            npx sass assets/src/scss/main.scss assets/dist/css/main.css --style=expanded --source-map
        fi
        echo "✅ SCSS build completed"
    else
        echo "⚠️  assets/src/scss/main.scss not found, skipping SCSS build"
    fi
else
    echo "⚠️  npx command not found, skipping SCSS build"
fi

echo ""
echo "🎉 Build Complete!"
echo ""

# Show build summary
echo "📊 Build Summary:"
echo "   ✅ Mode: $BUILD_MODE"
echo "   ✅ JavaScript assets compiled"
[ -f "assets/dist/css/main.css" ] && echo "   ✅ SCSS assets compiled"
echo "   ✅ Assets available in assets/dist/"
echo ""

if [ "$PRODUCTION_MODE" = true ]; then
    echo "🚀 Ready for production deployment!"
else
    echo "🔧 Development build ready for testing"
fi
echo ""
