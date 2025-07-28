#!/bin/bash

# Happy Place Development Helper Script
# Quick access to common development tasks

THEME_DIR="wp-content/themes/Happy Place Theme"
PLUGIN_DIR="wp-content/plugins/Happy Place Plugin"
DEV_TOOLS_URL="wp-content/plugins/Happy%20Place%20Plugin/dev-tools.php"

echo "🏠 Happy Place Development Helper"
echo "=================================="

# Check if we're in the right directory
if [ ! -d "$THEME_DIR" ]; then
    echo "❌ Error: Not in WordPress root directory"
    echo "Please run this script from: /Users/patrickgallagher/Local Sites/tpgv12/app/public"
    exit 1
fi

# Function to run commands in theme directory
run_in_theme() {
    echo "📁 Working in theme directory..."
    cd "$THEME_DIR"
    $1
    cd - > /dev/null
}

# Function to run commands in plugin directory  
run_in_plugin() {
    echo "📁 Working in plugin directory..."
    cd "$PLUGIN_DIR"
    $1
    cd - > /dev/null
}

case "$1" in
    "build-sass")
        echo "🎨 Building Sass..."
        run_in_theme "npm run build:sass"
        ;;
    "watch-sass")
        echo "👀 Starting Sass watch mode..."
        run_in_theme "npm run watch:sass"
        ;;
    "build-webpack")
        echo "📦 Building with Webpack..."
        run_in_theme "npm run build"
        ;;
    "build-dev")
        echo "🔧 Building development assets..."
        run_in_theme "npm run dev"
        ;;
    "build-all")
        echo "🚀 Building all assets..."
        run_in_theme "npm run build:sass"
        run_in_theme "npm run build"
        echo "✅ All builds complete!"
        ;;
    "clean")
        echo "🧹 Cleaning build directories..."
        run_in_theme "npm run clean"
        ;;
    "install")
        echo "📦 Installing npm dependencies..."
        run_in_theme "npm install"
        run_in_plugin "npm install"
        ;;
    "lint")
        echo "🔍 Linting SCSS..."
        run_in_theme "npm run lint:scss"
        ;;
    "serve")
        echo "🌐 Starting development server..."
        if [ -f "$THEME_DIR/serve.sh" ]; then
            run_in_theme "./serve.sh"
        else
            echo "❌ serve.sh not found in theme directory"
        fi
        ;;
    "flush-cache")
        echo "💨 Flushing WordPress cache..."
        curl -s "http://localhost/$DEV_TOOLS_URL?action=flush_cache&key=dev123"
        echo "✅ Cache flushed!"
        ;;
    "flush-rewrite")
        echo "🔄 Flushing rewrite rules..."
        curl -s "http://localhost/$DEV_TOOLS_URL?action=flush_rewrite&key=dev123"
        echo "✅ Rewrite rules flushed!"
        ;;
    "env-info")
        echo "ℹ️  Environment information..."
        curl -s "http://localhost/$DEV_TOOLS_URL?action=env_info&key=dev123"
        ;;
    *)
        echo "Usage: $0 {command}"
        echo ""
        echo "Available commands:"
        echo "  build-sass      Build Sass files"
        echo "  watch-sass      Start Sass watch mode"
        echo "  build-webpack   Build with Webpack"
        echo "  build-dev       Build development assets"
        echo "  build-all       Build all assets"
        echo "  clean           Clean build directories"
        echo "  install         Install npm dependencies"
        echo "  lint            Lint SCSS files"
        echo "  serve           Start development server"
        echo "  flush-cache     Flush WordPress cache"
        echo "  flush-rewrite   Flush rewrite rules"
        echo "  env-info        Show environment info"
        echo ""
        echo "Examples:"
        echo "  $0 build-sass"
        echo "  $0 watch-sass"
        echo "  $0 build-all"
        ;;
esac
