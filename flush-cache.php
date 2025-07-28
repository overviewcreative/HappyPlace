<?php
/**
 * Cache Flush Script
 * Comprehensive cache clearing for WordPress
 */

require_once('./wp-config.php');
require_once('./wp-load.php');

echo "<h2>WordPress Cache Flush</h2>";

// Flush WordPress object cache
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
    echo "<p>✅ WordPress object cache flushed</p>";
} else {
    echo "<p>⚠️ wp_cache_flush() not available</p>";
}

// Flush rewrite rules
flush_rewrite_rules();
echo "<p>✅ Rewrite rules flushed</p>";

// Clear transients
global $wpdb;
$transients = $wpdb->get_results(
    "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'"
);

$count = 0;
foreach ($transients as $transient) {
    delete_option($transient->option_name);
    $count++;
}
echo "<p>✅ Cleared {$count} transients</p>";

// Force plugin reinitialization
if (class_exists('HappyPlace\\Graphics\\Flyer_Generator')) {
    echo "<p>✅ Flyer Generator class is loaded</p>";
} else {
    echo "<p>❌ Flyer Generator class not found</p>";
}

// Check if admin menu is properly registered
global $menu, $submenu;
$happy_place_found = false;
foreach ($menu as $menu_item) {
    if (isset($menu_item[2]) && $menu_item[2] === 'happy-place') {
        $happy_place_found = true;
        break;
    }
}

if ($happy_place_found) {
    echo "<p>✅ Happy Place admin menu found</p>";
    
    if (isset($submenu['happy-place'])) {
        $flyer_found = false;
        foreach ($submenu['happy-place'] as $submenu_item) {
            if (isset($submenu_item[2]) && $submenu_item[2] === 'flyer-generator') {
                $flyer_found = true;
                break;
            }
        }
        
        if ($flyer_found) {
            echo "<p>✅ Flyer Generator submenu found</p>";
        } else {
            echo "<p>❌ Flyer Generator submenu not found</p>";
        }
    } else {
        echo "<p>❌ Happy Place submenus not found</p>";
    }
} else {
    echo "<p>❌ Happy Place admin menu not found</p>";
}

echo "<h3>Cache Flush Complete</h3>";
?>
