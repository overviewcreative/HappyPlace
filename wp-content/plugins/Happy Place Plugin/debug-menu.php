<?php
/**
 * Debug script to check admin menu registration
 * Run this from wp-admin to see current menu state
 */

// Add this to wp-admin/admin.php temporarily or use as debug snippet

add_action('admin_menu', function() {
    error_log('=== ADMIN MENU DEBUG ===');
    error_log('Current user can manage_options: ' . (current_user_can('manage_options') ? 'YES' : 'NO'));
    
    global $menu, $submenu;
    
    // Log main menus
    error_log('=== MAIN MENUS ===');
    foreach ($menu as $key => $item) {
        if (is_array($item) && isset($item[2])) {
            error_log("Menu: {$item[0]} -> {$item[2]}");
        }
    }
    
    // Log submenus for happy-place
    error_log('=== HAPPY PLACE SUBMENUS ===');
    if (isset($submenu['happy-place'])) {
        foreach ($submenu['happy-place'] as $item) {
            error_log("Submenu: {$item[0]} -> {$item[2]}");
        }
    } else {
        error_log('No submenus found for happy-place');
    }
    
    // Check if External API Settings class exists
    error_log('External_API_Settings class exists: ' . (class_exists('HappyPlace\\Admin\\External_API_Settings') ? 'YES' : 'NO'));
    
}, 999); // Late priority to see final menu state
