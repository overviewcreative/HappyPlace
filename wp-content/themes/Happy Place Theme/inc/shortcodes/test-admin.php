<?php
/**
 * Test shortcode admin functionality
 */

// Load WordPress
require_once '/Users/patrickgallagher/Local Sites/tpgv12/app/public/wp-load.php';

// Check if our shortcode manager is working
if (class_exists('HPH_Shortcode_Manager')) {
    $manager = HPH_Shortcode_Manager::get_instance();
    $shortcodes = $manager->get_registered_shortcodes();
    
    echo "✅ Shortcode Manager loaded\n";
    echo "Registered shortcodes: " . count($shortcodes) . "\n";
    
    foreach ($shortcodes as $tag => $instance) {
        echo "- $tag: " . get_class($instance) . "\n";
        
        // Test getting defaults
        $reflection = new ReflectionClass($instance);
        $defaults_property = $reflection->getProperty('defaults');
        $defaults_property->setAccessible(true);
        $defaults = $defaults_property->getValue($instance);
        
        echo "  Defaults: " . count($defaults) . " attributes\n";
    }
} else {
    echo "❌ HPH_Shortcode_Manager not found\n";
}

// Test the admin class
if (class_exists('HPH_Shortcode_Admin')) {
    echo "✅ Shortcode Admin class available\n";
} else {
    echo "❌ HPH_Shortcode_Admin not found\n";
}
