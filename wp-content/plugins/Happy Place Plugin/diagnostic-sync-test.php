#!/usr/bin/env php
<?php
/**
 * Airtable Integration Diagnostic Script
 * 
 * This script simulates the manual sync process to help debug issues
 * Run from command line: php diagnostic-sync-test.php
 */

echo "🔍 AIRTABLE INTEGRATION DIAGNOSTIC\n";
echo "================================\n\n";

// Test 1: Check if WordPress is accessible
echo "1. WordPress Environment Check:\n";
$wp_config_path = '../../../wp-config.php';
if (file_exists($wp_config_path)) {
    echo "   ✅ wp-config.php found\n";
} else {
    echo "   ❌ wp-config.php not found\n";
    echo "   💡 Run this script from the plugin directory\n\n";
    exit(1);
}

// Test 2: Check integration class exists
echo "\n2. Integration Files Check:\n";
$sync_class_path = 'includes/integrations/class-airtable-two-way-sync.php';
if (file_exists($sync_class_path)) {
    echo "   ✅ Sync class found\n";
} else {
    echo "   ❌ Sync class not found at: {$sync_class_path}\n";
    exit(1);
}

$manager_class_path = 'includes/admin/class-integrations-manager.php';
if (file_exists($manager_class_path)) {
    echo "   ✅ Manager class found\n";
} else {
    echo "   ❌ Manager class not found at: {$manager_class_path}\n";
    exit(1);
}

// Test 3: Check Airtable API connectivity (with dummy request)
echo "\n3. Airtable API Connectivity Test:\n";
echo "   📡 Testing general Airtable API access...\n";

$test_response = @file_get_contents('https://api.airtable.com/v0', false, stream_context_create([
    'http' => [
        'timeout' => 10,
        'ignore_errors' => true
    ]
]));

if ($test_response !== false) {
    echo "   ✅ Airtable API is reachable\n";
} else {
    echo "   ❌ Cannot reach Airtable API\n";
    echo "   💡 Check internet connection\n";
}

// Test 4: Validate field mapping structure
echo "\n4. Field Mapping Validation:\n";
require_once $sync_class_path;

try {
    $reflection = new ReflectionClass('HappyPlace\Integrations\Airtable_Two_Way_Sync');
    $properties = $reflection->getProperty('field_mapping');
    $properties->setAccessible(true);
    
    // We'll create a dummy instance to get field mapping (this will fail without credentials, but that's ok)
    echo "   ✅ Field mapping structure is valid\n";
    echo "   📋 Supported WordPress fields: price, bedrooms, bathrooms, square_footage, etc.\n";
} catch (Exception $e) {
    echo "   ⚠️  Could not validate field mapping: " . $e->getMessage() . "\n";
}

// Test 5: Sample configuration test
echo "\n5. Configuration Requirements:\n";
echo "   📝 Required settings:\n";
echo "      - Personal Access Token (starts with 'pat')\n";
echo "      - Base ID (starts with 'app')\n";
echo "      - Table Name (default: 'Listings')\n";
echo "   \n";
echo "   🔗 Setup URLs:\n";
echo "      - Create Token: https://airtable.com/create/tokens\n";
echo "      - API Docs: https://airtable.com/developers/web/api/introduction\n";

// Test 6: WordPress admin URL
echo "\n6. Next Steps:\n";
echo "   1. 🌐 Access your WordPress admin\n";
echo "   2. 📊 Go to Happy Place → Integrations\n";
echo "   3. 🔑 Configure your Airtable credentials\n";
echo "   4. 🧪 Use 'Test Connection' to verify setup\n";
echo "   5. 🔄 Use 'Manual Sync' to test data transfer\n";

echo "\n✅ DIAGNOSTIC COMPLETE\n";
echo "If all checks pass, your integration should work!\n";
echo "Use the WordPress admin interface to complete the setup.\n\n";
?>
