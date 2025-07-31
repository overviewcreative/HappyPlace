#!/bin/bash

# INCLUDES CLEANUP SCRIPT - PHASE 1 (SAFE REMOVALS)
# Happy Place Plugin - File Consolidation
# Execute only after reviewing audit report

echo "=== HAPPY PLACE PLUGIN - INCLUDES CLEANUP PHASE 1 ==="
echo "Performing SAFE removals and initial consolidation"
echo

# Backup safety check
if [ ! -d "../Happy Place Plugin.backup.20250730_094956" ]; then
    echo "❌ ERROR: Backup not found! Aborting cleanup."
    echo "Please ensure backup exists before running cleanup."
    exit 1
fi

echo "✅ Backup verified. Proceeding with cleanup..."
echo

# PHASE 1A: Remove empty/obsolete files
echo "📋 PHASE 1A: Removing empty/obsolete files..."

SAFE_REMOVALS=(
    "includes/class-database.php"
    "includes/template-functions.php"
)

for file in "${SAFE_REMOVALS[@]}"; do
    if [ -f "$file" ]; then
        echo "🗑️  Removing: $file"
        rm "$file"
    else
        echo "⚠️  Not found: $file"
    fi
done

echo

# PHASE 1B: Remove legacy AJAX directories
echo "📋 PHASE 1B: Removing legacy AJAX directories..."

LEGACY_AJAX_DIRS=(
    "includes/dashboard/ajax"
)

for dir in "${LEGACY_AJAX_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        echo "🗑️  Removing directory: $dir/"
        rm -rf "$dir"
    else
        echo "⚠️  Not found: $dir/"
    fi
done

echo

# PHASE 1C: Remove duplicate/legacy AJAX files
echo "📋 PHASE 1C: Removing duplicate/legacy AJAX files..."

LEGACY_AJAX_FILES=(
    "includes/class-validation-ajax.php"
    "includes/admin/class-image-optimization-ajax.php"
)

for file in "${LEGACY_AJAX_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "🗑️  Removing: $file"
        rm "$file"
    else
        echo "⚠️  Not found: $file"
    fi
done

echo

# PHASE 1D: Remove duplicate bridge files
echo "📋 PHASE 1D: Removing duplicate bridge files..."

DUPLICATE_BRIDGE_FILES=(
    "includes/fields/class-bridge-function-manager.php"
    "includes/fields/enhanced-listing-bridge.php"
)

for file in "${DUPLICATE_BRIDGE_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "🗑️  Removing duplicate: $file"
        rm "$file"
    else
        echo "⚠️  Not found: $file"
    fi
done

echo

# PHASE 1E: Remove duplicate form handler files
echo "📋 PHASE 1E: Removing duplicate form handler files..."

DUPLICATE_FORM_FILES=(
    "includes/forms/agent_form_handler.php"
    "includes/forms/form_handler.php"
    "includes/forms/forms.php"
)

for file in "${DUPLICATE_FORM_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "🗑️  Removing duplicate: $file"
        rm "$file"
    else
        echo "⚠️  Not found: $file"
    fi
done

echo

# Summary
echo "=== CLEANUP PHASE 1 COMPLETE ==="
echo "✅ Removed empty/obsolete files"
echo "✅ Removed legacy AJAX directories"
echo "✅ Removed duplicate AJAX files"
echo "✅ Removed duplicate bridge files" 
echo "✅ Removed duplicate form handlers"
echo
echo "📊 NEXT STEPS:"
echo "1. Review remaining files with: find includes -name '*.php' | wc -l"
echo "2. Test plugin functionality"
echo "3. Proceed to Phase 2 (consolidation)"
echo
echo "🛑 SAFETY: Backup is available for rollback if needed"
echo "   Location: ../Happy Place Plugin.backup.20250730_094956"
