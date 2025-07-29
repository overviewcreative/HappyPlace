<?php
/**
 * Asset Manager Verification Script
 * Quick test to ensure the Asset_Manager is working correctly
 */

// Load WordPress environment
require_once('/Users/patrickgallagher/Local Sites/tpgv12/app/public/wp-load.php');

echo "=== Happy Place Theme Asset Manager Verification ===\n\n";

// Test 1: Check if Asset_Manager class exists
if (class_exists('HappyPlace\Core\Asset_Manager')) {
    echo "✅ Asset_Manager class is available\n";
    
    // Test 2: Initialize Asset_Manager
    try {
        $asset_manager = HappyPlace\Core\Asset_Manager::init();
        echo "✅ Asset_Manager initialized successfully\n";
        
        // Test 3: Check singleton pattern
        $asset_manager2 = HappyPlace\Core\Asset_Manager::init();
        if ($asset_manager === $asset_manager2) {
            echo "✅ Singleton pattern working correctly\n";
        } else {
            echo "❌ Singleton pattern failed\n";
        }
        
        // Test 4: Check if methods exist
        $methods_to_check = [
            'enqueue_frontend_assets',
            'enqueue_admin_assets', 
            'enqueue_login_assets',
            'asset',
            'has_asset'
        ];
        
        foreach ($methods_to_check as $method) {
            if (method_exists($asset_manager, $method)) {
                echo "✅ Method '$method' exists\n";
            } else {
                echo "❌ Method '$method' missing\n";
            }
        }
        
        // Test 5: Check asset loading info
        if (method_exists($asset_manager, 'get_loaded_assets')) {
            $loaded_assets = $asset_manager->get_loaded_assets();
            echo "✅ Asset loading info available\n";
            echo "   - Assets directory: " . $loaded_assets['assets_dir'] . "\n";
            echo "   - Assets URI: " . $loaded_assets['assets_uri'] . "\n";
            echo "   - Theme version: " . $loaded_assets['theme_version'] . "\n";
            echo "   - Manifest loaded: " . (empty($loaded_assets['manifest']) ? 'No' : 'Yes') . "\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error initializing Asset_Manager: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "❌ Asset_Manager class not found\n";
}

echo "\n=== Legacy Asset Loading Check ===\n";

// Check for legacy functions
$legacy_functions = [
    'happy_place_enqueue_assets',
    'hph_enqueue_assets', 
    'hph_bridge_enqueue_template_assets'
];

foreach ($legacy_functions as $func) {
    if (function_exists($func)) {
        echo "⚠️  Legacy function '$func' still exists\n";
    } else {
        echo "✅ Legacy function '$func' removed\n";
    }
}

echo "\n=== WordPress Hooks Check ===\n";

// Check if hooks are properly registered
global $wp_filter;
if (isset($wp_filter['wp_enqueue_scripts'])) {
    $asset_manager_found = false;
    foreach ($wp_filter['wp_enqueue_scripts']->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $callback_key => $callback_data) {
            if (strpos($callback_key, 'Asset_Manager') !== false || 
                (is_array($callback_data['function']) && 
                 isset($callback_data['function'][0]) && 
                 is_object($callback_data['function'][0]) && 
                 get_class($callback_data['function'][0]) === 'HappyPlace\Core\Asset_Manager')) {
                $asset_manager_found = true;
                echo "✅ Asset_Manager hook registered at priority $priority\n";
                break 2;
            }
        }
    }
    if (!$asset_manager_found) {
        echo "❌ Asset_Manager hook not found\n";
    }
} else {
    echo "❌ wp_enqueue_scripts hook not found\n";
}

echo "\n=== Verification Complete ===\n";
