<?php
/**
 * Debug Flyer Generator Verification Script
 * Run this to verify the flyer generator is properly loaded
 */

// Only run if accessed via WordPress admin
if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

// Check if we're in WordPress admin context
if (!is_admin()) {
    return;
}

// Verify constants
echo "<h2>Happy Place Plugin Constants Verification</h2>\n";
echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
echo "<tr><th>Constant</th><th>Value</th><th>Status</th></tr>\n";

$constants_to_check = [
    'HPH_VERSION',
    'HPH_URL', 
    'HPH_ASSETS_URL',
    'HPH_PATH',
    'HPH_INCLUDES_PATH',
    'HPH_ASSETS_PATH'
];

foreach ($constants_to_check as $constant) {
    $defined = defined($constant);
    $value = $defined ? constant($constant) : 'NOT DEFINED';
    $status = $defined ? '✅ OK' : '❌ MISSING';
    
    echo "<tr><td>$constant</td><td>$value</td><td>$status</td></tr>\n";
}
echo "</table>\n";

// Check file existence
echo "<h2>File Existence Verification</h2>\n";
echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
echo "<tr><th>File</th><th>Path</th><th>Status</th></tr>\n";

$files_to_check = [
    'Flyer Generator Class' => HPH_INCLUDES_PATH . 'graphics/class-flyer-generator.php',
    'Plugin Manager' => HPH_INCLUDES_PATH . 'core/class-plugin-manager.php',
    'Flyer Template' => HPH_PATH . 'templates/flyer-generator.php',
    'Flyer JavaScript' => HPH_ASSETS_PATH . 'js/flyer-generator.js',
    'Flyer CSS' => HPH_ASSETS_PATH . 'css/flyer-generator.css'
];

foreach ($files_to_check as $name => $path) {
    $exists = file_exists($path);
    $status = $exists ? '✅ FOUND' : '❌ MISSING';
    
    echo "<tr><td>$name</td><td>$path</td><td>$status</td></tr>\n";
}
echo "</table>\n";

// Check if classes are loaded
echo "<h2>Class Loading Verification</h2>\n";
echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
echo "<tr><th>Class</th><th>Status</th></tr>\n";

$classes_to_check = [
    'HappyPlace\\Core\\Plugin_Manager',
    'HappyPlace\\Graphics\\Flyer_Generator'
];

foreach ($classes_to_check as $class) {
    $exists = class_exists($class);
    $status = $exists ? '✅ LOADED' : '❌ NOT LOADED';
    
    echo "<tr><td>$class</td><td>$status</td></tr>\n";
}
echo "</table>\n";

// Check AJAX actions
echo "<h2>AJAX Actions Verification</h2>\n";
if (function_exists('has_action')) {
    $ajax_actions = [
        'wp_ajax_generate_flyer',
        'wp_ajax_nopriv_generate_flyer'
    ];
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
    echo "<tr><th>AJAX Action</th><th>Status</th></tr>\n";
    
    foreach ($ajax_actions as $action) {
        $registered = has_action($action);
        $status = $registered ? '✅ REGISTERED' : '❌ NOT REGISTERED';
        
        echo "<tr><td>$action</td><td>$status</td></tr>\n";
    }
    echo "</table>\n";
}

// Check shortcodes
echo "<h2>Shortcode Verification</h2>\n";
if (function_exists('shortcode_exists')) {
    $shortcodes = [
        'listing_flyer_generator'
    ];
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
    echo "<tr><th>Shortcode</th><th>Status</th></tr>\n";
    
    foreach ($shortcodes as $shortcode) {
        $exists = shortcode_exists($shortcode);
        $status = $exists ? '✅ REGISTERED' : '❌ NOT REGISTERED';
        
        echo "<tr><td>[$shortcode]</td><td>$status</td></tr>\n";
    }
    echo "</table>\n";
}

echo "<p><strong>Verification completed!</strong> If any items show as missing or not loaded, there may be an issue with the plugin initialization.</p>\n";
?>
