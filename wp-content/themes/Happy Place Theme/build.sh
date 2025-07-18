#!/bin/bash

# Happy Place Theme Build Script
# Manual build script for SCSS compilation

echo "🏗️  Building Happy Place Theme Assets..."

# Check if sass is available
if ! command -v sass &> /dev/null; then
    echo "❌ Sass not found. Installing sass globally..."
    npm install -g sass
fi

# Create dist directories
mkdir -p assets/dist/css
mkdir -p assets/dist/js

# Compile SCSS to CSS
echo "📦 Compiling SCSS..."
npx sass assets/src/scss/main.scss assets/dist/css/main.css --style=compressed --no-source-map

# Check if compilation was successful
if [ -f "assets/dist/css/main.css" ]; then
    echo "✅ CSS compiled successfully"
    echo "   → assets/dist/css/main.css"
else
    echo "❌ CSS compilation failed"
    exit 1
fi

# Copy JavaScript file
echo "📦 Processing JavaScript..."
cp assets/src/js/main.js assets/dist/js/main.js

# Create a simple manifest file
echo "📦 Creating manifest..."
cat > assets/dist/manifest.json << EOF
{
  "main.css": "css/main.css",
  "main.js": "js/main.js"
}
EOF

echo "🎉 Build complete!"
echo ""
echo "Generated files:"
echo "   - assets/dist/css/main.css"
echo "   - assets/dist/js/main.js"
echo "   - assets/dist/manifest.json"
echo ""
echo "Run this script again after making changes to SCSS or JS files."
