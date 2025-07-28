<?php
/**
 * Test shortcode class loading
 */

echo "Starting class loading test...\n";

// Include the base class first
echo "Loading base class...\n";
require_once __DIR__ . '/class-shortcode-manager.php';
echo "Base class loaded\n";

// Test loading the features class
echo "Loading features class...\n";
require_once __DIR__ . '/components/features.php';
echo "Features class loaded\n";

// Check if class exists and can be instantiated
if (class_exists('HPH_Shortcode_Features')) {
    echo "✅ HPH_Shortcode_Features class exists\n";
    
    try {
        $instance = new HPH_Shortcode_Features();
        echo "✅ HPH_Shortcode_Features can be instantiated\n";
        
        // Test the required methods
        if (method_exists($instance, 'generate_output')) {
            echo "✅ generate_output method exists\n";
        } else {
            echo "❌ generate_output method missing\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error instantiating class: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ HPH_Shortcode_Features class not found\n";
}

// Check base class
if (class_exists('HPH_Shortcode_Base')) {
    echo "✅ HPH_Shortcode_Base class exists\n";
    
    $reflection = new ReflectionClass('HPH_Shortcode_Base');
    $abstract_methods = $reflection->getMethods(ReflectionMethod::IS_ABSTRACT);
    
    echo "Abstract methods in base class:\n";
    foreach ($abstract_methods as $method) {
        echo "  - " . $method->getName() . "\n";
    }
} else {
    echo "❌ HPH_Shortcode_Base class not found\n";
}
