#!/bin/bash

# Happy Place Theme Enhanced Build Script
# Alternative to webpack with watch mode support

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Get the directory where the script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo -e "${BLUE}🏗️  Happy Place Theme Build Process Starting...${NC}"

# Check if watch mode is requested
WATCH_MODE=false
if [[ "$1" == "--watch" ]]; then
    WATCH_MODE=true
    echo -e "${YELLOW}👀 Watch mode enabled - monitoring SCSS files for changes...${NC}"
fi

build_assets() {
    echo -e "${BLUE}📁 Creating directories...${NC}"
    mkdir -p assets/dist/css
    mkdir -p assets/dist/js

    echo -e "${BLUE}🎨 Compiling main.scss...${NC}"
    npx sass assets/src/scss/main.scss assets/dist/css/main.css --style=compressed --no-source-map
    
    echo -e "${BLUE}🎨 Compiling single-listing.scss...${NC}"
    npx sass assets/src/scss/single-listing.scss assets/dist/css/single-listing.css --style=compressed --no-source-map

    echo -e "${BLUE}📦 Copying JavaScript files...${NC}"
    
    # Copy main JavaScript files
    if [ -f "assets/src/js/main.js" ]; then
        cp assets/src/js/main.js assets/dist/js/main.js
        echo -e "${GREEN}✅ main.js copied${NC}"
    fi
    
    if [ -f "assets/src/js/single-listing.js" ]; then
        cp assets/src/js/single-listing.js assets/dist/js/single-listing.js
        echo -e "${GREEN}✅ single-listing.js copied${NC}"
    fi
    
    # Copy JavaScript modules and components if they exist
    if [ -d "assets/src/js/modules" ]; then
        cp -r assets/src/js/modules assets/dist/js/
        echo -e "${GREEN}✅ JavaScript modules copied${NC}"
    fi
    
    if [ -d "assets/src/js/components" ]; then
        cp -r assets/src/js/components assets/dist/js/
        echo -e "${GREEN}✅ JavaScript components copied${NC}"
    fi

    # Generate simple manifest with cache busting
    echo -e "${BLUE}📝 Creating manifest.json...${NC}"
    TIMESTAMP=$(date +%s)
    cat > assets/dist/manifest.json << EOF
{
  "main.css": "css/main.css?v=$TIMESTAMP",
  "main.js": "js/main.js?v=$TIMESTAMP",
  "single-listing.css": "css/single-listing.css?v=$TIMESTAMP",
  "single-listing.js": "js/single-listing.js?v=$TIMESTAMP"
}
EOF

    echo -e "${GREEN}✅ Build completed successfully!${NC}"
    echo -e "${BLUE}📊 Files generated:${NC}"
    echo -e "   • $(pwd)/assets/dist/css/main.css"
    echo -e "   • $(pwd)/assets/dist/css/single-listing.css"
    echo -e "   • $(pwd)/assets/dist/js/main.js"
    echo -e "   • $(pwd)/assets/dist/js/single-listing.js"
    echo -e "   • assets/dist/manifest.json"
    
    # Optional: Clear WordPress cache if WP-CLI is available
    if command -v wp &> /dev/null; then
        echo -e "${BLUE}🧹 Clearing WordPress cache...${NC}"
        cd "$SCRIPT_DIR/../../../.."
        wp cache flush 2>/dev/null || echo -e "${YELLOW}ℹ️  Cache flush skipped (not in WordPress root or WP-CLI not available)${NC}"
        cd "$SCRIPT_DIR"
    fi
    
    echo -e "${GREEN}🎉 Happy Place Theme build process complete!${NC}"
}

if [ "$WATCH_MODE" = true ]; then
    # Initial build
    build_assets
    
    echo -e "${YELLOW}👀 Watching for changes in assets/src/scss/...${NC}"
    echo -e "${YELLOW}Press Ctrl+C to stop watching${NC}"
    
    # Watch for SCSS changes using development mode (expanded CSS with source maps)
    npx sass assets/src/scss/main.scss assets/dist/css/main.css --watch --style=expanded --source-map &
    SASS_MAIN_PID=$!
    
    npx sass assets/src/scss/single-listing.scss assets/dist/css/single-listing.css --watch --style=expanded --source-map &
    SASS_SINGLE_PID=$!
    
    # Trap to cleanup background processes
    trap "echo -e '\n${YELLOW}Stopping watch mode...${NC}'; kill $SASS_MAIN_PID $SASS_SINGLE_PID 2>/dev/null; exit" INT TERM
    
    # Keep script running
    wait
else
    # Single build
    build_assets
fi
