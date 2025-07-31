#!/bin/bash
# Happy Place Plugin - Development File Cleanup Script
# This script removes all temporary and development files that are not needed for production

echo "🧹 Starting Happy Place Plugin cleanup..."
echo "======================================="

# Change to the WordPress root directory
cd "/Users/patrickgallagher/Local Sites/tpgv12/app/public"

# Count files before cleanup
INITIAL_COUNT=$(find . -type f | wc -l)
echo "📊 Initial file count: $INITIAL_COUNT"

# Array to track removed files
REMOVED_FILES=()

# Function to remove file and track it
remove_file() {
    if [ -f "$1" ]; then
        echo "🗑️  Removing: $1"
        rm "$1"
        REMOVED_FILES+=("$1")
    fi
}

# Function to remove directory and track it
remove_directory() {
    if [ -d "$1" ]; then
        echo "📁 Removing directory: $1"
        rm -rf "$1"
        REMOVED_FILES+=("$1")
    fi
}

echo ""
echo "🔍 Removing development documentation files..."
echo "============================================="

# Remove development documentation from root
remove_file "AJAX_CLEANUP_REPORT.md"
remove_file "PHASE_4_DAY_4-7_IMPLEMENTATION_COMPLETE.md"
remove_file "PLUGIN_OPTIMIZATION_COMPLETE.md"
remove_file "PLUGIN_OPTIMIZATION_PLAN.md"
remove_file "final_complete_structure.md"
remove_file "hph-implementation-checklist.md"
remove_file "hph_restructure.log"
remove_file "copilot_instructions.md"

# Remove development scripts
remove_file "hph-dev.sh"
remove_file "setup-hph-structure.sh"
remove_file "fix-namespace.php"

# Remove test files from root
remove_file "config-test.php"
remove_file "test-config.php"
remove_file "local-xdebuginfo.php"

echo ""
echo "🧪 Removing test and debug files..."
echo "=================================="

# Remove all phase-related test files
remove_file "wp-content/themes/Happy Place Theme/testing/phase3-day4-7-financial-analytics-test.php"
remove_file "wp-content/themes/Happy Place Theme/testing/phase4-day1-3-advanced-search-test.php"
remove_file "wp-content/plugins/Happy Place Plugin/templates/phase4-day4-7-testing.php"
remove_file "wp-content/plugins/Happy Place Plugin/includes/fields/phase1-status-page.php"

# Remove test files from plugin
remove_file "wp-content/plugins/Happy Place Plugin/simple-structure-test.php"
remove_file "wp-content/plugins/Happy Place Plugin/test-ajax-structure.php"
remove_file "wp-content/plugins/Happy Place Plugin/test-ajax-structure-simple.php"
remove_file "wp-content/plugins/Happy Place Plugin/test-plugin-load.php"

# Remove debug files
remove_file "wp-content/plugins/Happy Place Plugin/debug-admin-assets.php"
remove_file "wp-content/plugins/Happy Place Plugin/debug-marketing-suite.php"

# Remove emergency test files
remove_file "wp-content/plugins/Happy Place Plugin/assets/js/emergency-admin-test.js"
remove_file "wp-content/plugins/Happy Place Plugin/emergency-ajax-handlers.php"

# Remove API test files from theme
remove_file "wp-content/themes/Happy Place Theme/page-api-test.php"
remove_file "wp-content/themes/Happy Place Theme/inc/bridge/api-testing-utilities.php"

echo ""
echo "📋 Removing development documentation..."
echo "======================================"

# Remove all PHASE documentation from plugin
remove_file "wp-content/plugins/Happy Place Plugin/PHASE2_CONSOLIDATION_PLAN.md"
remove_file "wp-content/plugins/Happy Place Plugin/COMPLETE_AUDIT_PHASE2.md"
remove_file "wp-content/plugins/Happy Place Plugin/PHASE_3B_CSV_CONSOLIDATION_COMPLETE.md"
remove_file "wp-content/plugins/Happy Place Plugin/PHASE_3A_ADMIN_ASSETS_COMPLETE.md"
remove_file "wp-content/plugins/Happy Place Plugin/PROGRESS_REPORT_PHASE2B.md"
remove_file "wp-content/plugins/Happy Place Plugin/PHASE_2B_FORM_CONSOLIDATION_COMPLETE.md"
remove_file "wp-content/plugins/Happy Place Plugin/ADMIN_MODERNIZATION_COMPLETE.md"

# Remove development documentation from theme
remove_file "wp-content/themes/Happy Place Theme/BRIDGE_FUNCTIONS_STATUS_COMPLETE.md"

# Remove archive documentation directory
remove_directory "wp-content/plugins/Happy Place Plugin/includes/fields/documentation/archive"

# Remove simple build files from theme (development artifacts)
remove_file "wp-content/themes/Happy Place Theme/build-simple.sh"
remove_file "wp-content/themes/Happy Place Theme/webpack.simple.config.js"

echo ""
echo "🧽 Removing empty testing directories..."
echo "======================================"

# Remove testing directory if it exists and is now empty
if [ -d "wp-content/themes/Happy Place Theme/testing" ]; then
    if [ -z "$(ls -A "wp-content/themes/Happy Place Theme/testing")" ]; then
        remove_directory "wp-content/themes/Happy Place Theme/testing"
    else
        echo "⚠️  Testing directory not empty, keeping it"
    fi
fi

echo ""
echo "✨ Cleanup Summary"
echo "=================="
echo "📊 Files/directories removed: ${#REMOVED_FILES[@]}"

if [ ${#REMOVED_FILES[@]} -gt 0 ]; then
    echo ""
    echo "🗑️  Removed files:"
    for file in "${REMOVED_FILES[@]}"; do
        echo "   - $file"
    done
fi

# Count files after cleanup
FINAL_COUNT=$(find . -type f | wc -l)
REMOVED_COUNT=$((INITIAL_COUNT - FINAL_COUNT))

echo ""
echo "📊 Final file count: $FINAL_COUNT"
echo "📉 Files removed: $REMOVED_COUNT"
echo ""
echo "✅ Cleanup complete! Your Happy Place Plugin is now production-ready."
echo "🚀 All temporary and development files have been removed."
