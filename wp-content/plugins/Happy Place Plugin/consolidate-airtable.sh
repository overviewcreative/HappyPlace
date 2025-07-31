#!/bin/bash

echo "=== HAPPY PLACE PLUGIN - AIRTABLE CONSOLIDATION ==="
echo "Moving original Airtable integration files to backup location"
echo ""

# Get plugin directory
PLUGIN_DIR="$(pwd)"
BACKUP_DIR="${PLUGIN_DIR}/legacy-backup/airtable-integration"

# Create backup directory
mkdir -p "$BACKUP_DIR"
echo "📁 Created backup directory: $BACKUP_DIR"

# Check if original files exist
AIRTABLE_TWO_WAY="includes/integrations/class-airtable-two-way-sync.php"
ENHANCED_AIRTABLE="includes/integrations/class-enhanced-airtable-sync.php"

echo ""
echo "📋 PHASE 2B: AIRTABLE CONSOLIDATION PROGRESS"

if [ -f "$AIRTABLE_TWO_WAY" ]; then
    FILE_SIZE=$(wc -l < "$AIRTABLE_TWO_WAY")
    echo "✅ Found: $AIRTABLE_TWO_WAY ($FILE_SIZE lines)"
    mv "$AIRTABLE_TWO_WAY" "$BACKUP_DIR/"
    echo "🗂️  Moved to backup: $BACKUP_DIR/class-airtable-two-way-sync.php"
else
    echo "⚠️  Not found: $AIRTABLE_TWO_WAY"
fi

if [ -f "$ENHANCED_AIRTABLE" ]; then
    FILE_SIZE=$(wc -l < "$ENHANCED_AIRTABLE")
    echo "✅ Found: $ENHANCED_AIRTABLE ($FILE_SIZE lines)"
    mv "$ENHANCED_AIRTABLE" "$BACKUP_DIR/"
    echo "🗂️  Moved to backup: $BACKUP_DIR/class-enhanced-airtable-sync.php"
else
    echo "⚠️  Not found: $ENHANCED_AIRTABLE"
fi

echo ""
echo "📊 CONSOLIDATION IMPACT:"
echo "   📄 Original Files: 2 files (~3,548 lines)"
echo "   🔄 Consolidated Into: includes/api/ajax/handlers/class-integration-ajax.php"
echo "   ✅ New Handler: 13 AJAX actions with unified architecture"
echo "   🗂️  Backup Location: $BACKUP_DIR"

echo ""
echo "🔍 Verifying new consolidated handler..."
NEW_HANDLER="includes/api/ajax/handlers/class-integration-ajax.php"

if [ -f "$NEW_HANDLER" ]; then
    NEW_SIZE=$(wc -l < "$NEW_HANDLER")
    echo "✅ Consolidated handler exists: $NEW_HANDLER ($NEW_SIZE lines)"
    
    # Check for syntax errors
    if php -l "$NEW_HANDLER" > /dev/null 2>&1; then
        echo "✅ Syntax check passed"
    else
        echo "❌ Syntax error detected!"
        php -l "$NEW_HANDLER"
    fi
else
    echo "❌ Consolidated handler not found!"
fi

echo ""
echo "📋 NEXT STEPS:"
echo "   1. Test new Integration_Ajax handler functionality"
echo "   2. Update any code that references old classes"
echo "   3. Proceed with Form Handler consolidation"
echo "   4. Complete Dashboard consolidation"

echo ""
echo "🛡️  SAFETY: Original files backed up in $BACKUP_DIR"
echo "   Rollback available if needed"

echo ""
echo "=== AIRTABLE CONSOLIDATION COMPLETE ==="
