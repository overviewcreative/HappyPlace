<?php
// Temporary debug file to test admin access
// Add this to the beginning of the integrations page to debug

add_action('admin_menu', function() {
    error_log('HPH Debug: Admin menu hook is running');
    error_log('HPH Debug: Current user can manage_options: ' . (current_user_can('manage_options') ? 'YES' : 'NO'));
    error_log('HPH Debug: Current user ID: ' . get_current_user_id());
    error_log('HPH Debug: Is admin: ' . (is_admin() ? 'YES' : 'NO'));
    
    $user = wp_get_current_user();
    error_log('HPH Debug: User roles: ' . implode(', ', $user->roles));
    
    // Test adding a simple menu to see if it works
    add_menu_page(
        'Debug Test',
        'Debug Test',
        'manage_options',
        'debug-test',
        function() {
            echo '<div class="wrap"><h1>Debug Test</h1><p>This page works!</p></div>';
        }
    );
});
