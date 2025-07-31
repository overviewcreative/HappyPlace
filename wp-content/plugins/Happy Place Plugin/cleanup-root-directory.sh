#!/bin/bash

# ROOT DIRECTORY CLEANUP SCRIPT  
# Happy Place Plugin - Safe Legacy File Removal
# Execute only after reviewing COMPLETE_AUDIT_PHASE2.md

echo "=== HAPPY PLACE PLUGIN - ROOT DIRECTORY CLEANUP ==="
echo "Removing legacy testing and cleanup files from plugin root"
echo

# Backup safety check
if [ ! -d "../Happy Place Plugin.backup.20250730_094956" ]; then
    echo "❌ ERROR: Backup not found! Aborting cleanup."
    echo "Please ensure backup exists before running cleanup."
    exit 1
fi

echo "✅ Backup verified. Proceeding with root cleanup..."
echo

# Count files before cleanup
BEFORE_COUNT=$(ls -1 *.php 2>/dev/null | wc -l)
echo "📊 PHP files before cleanup: $BEFORE_COUNT"
echo

# PHASE 1: Remove legacy AJAX cleanup tools
echo "📋 PHASE 1: Removing legacy AJAX cleanup tools..."

AJAX_CLEANUP_FILES=(
    "ajax-cleanup-admin.php"
    "ajax-cleanup-migration.php"
    "ajax-cleanup-test.php"
    "cleanup.php"
    "simple-ajax-cleanup.php"
)

for file in "${AJAX_CLEANUP_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "🗑️  Removing: $file"
        rm "$file"
    else
        echo "⚠️  Not found: $file"
    fi
done

echo

# PHASE 2: Remove testing/debug files
echo "📋 PHASE 2: Removing testing and debug files..."

TEST_DEBUG_FILES=(
    "debug-flyer-verification.php"
    "test-enhanced-admin.php"
    "test-enhanced-systems.php"
    "test-flyer-generator.php"
    "verify-complete-admin.php"
    "verify-flyer-fix.php"
)

for file in "${TEST_DEBUG_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "🗑️  Removing: $file"
        rm "$file"
    else
        echo "⚠️  Not found: $file"
    fi
done

echo

# Count files after cleanup
AFTER_COUNT=$(ls -1 *.php 2>/dev/null | wc -l)
REMOVED_COUNT=$((BEFORE_COUNT - AFTER_COUNT))

echo "=== ROOT DIRECTORY CLEANUP COMPLETE ==="
echo "📊 PHP files after cleanup: $AFTER_COUNT"
echo "🗑️  Total files removed: $REMOVED_COUNT"
echo
echo "✅ REMAINING ESSENTIAL FILES:"
ls -1 *.php 2>/dev/null | while read file; do
    echo "   ✓ $file"
done
echo
echo "✅ REMAINING AUDIT FILES:"
ls -1 *.md *.sh 2>/dev/null | while read file; do
    echo "   📄 $file"
done
echo
echo "🛑 SAFETY: Backup available for rollback if needed"
echo "   Location: ../Happy Place Plugin.backup.20250730_094956"
echo
echo "📋 NEXT: Review includes directory with COMPLETE_AUDIT_PHASE2.md"
echo "   Focus on consolidating large files and duplicate functionality"
