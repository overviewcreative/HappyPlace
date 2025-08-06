<?php
/**
 * Test AJAX Handlers
 * Run this in the browser to test AJAX handler registration
 */

// Load WordPress
require_once 'wp-config.php';
require_once 'wp-load.php';

echo "<h1>AJAX Handlers Test</h1>\n";

// Test marketing section AJAX handlers
echo "<h2>Marketing Section AJAX Handlers</h2>\n";
$marketing_actions = [
    'wp_ajax_hph_generate_flyer',
    'wp_ajax_hph_schedule_social_post', 
    'wp_ajax_hph_create_email_campaign',
    'wp_ajax_hph_get_marketing_templates',
    'wp_ajax_load_marketing_suite_interface',
    'wp_ajax_nopriv_load_marketing_suite_interface'
];

echo "<ul>\n";
foreach ($marketing_actions as $action) {
    $has_action = has_action($action);
    $status = $has_action ? '<span style="color: green;">REGISTERED</span>' : '<span style="color: red;">NOT REGISTERED</span>';
    echo "<li>{$action}: {$status}</li>\n";
}
echo "</ul>\n";

// Test nonce creation
echo "<h2>Nonce Creation Test</h2>\n";
echo "<ul>\n";
echo '<li>marketing_suite_nonce: ' . wp_create_nonce('marketing_suite_nonce') . "</li>\n";
echo '<li>hph_dashboard_nonce: ' . wp_create_nonce('hph_dashboard_nonce') . "</li>\n";
echo '<li>hph_ajax_nonce: ' . wp_create_nonce('hph_ajax_nonce') . "</li>\n";
echo "</ul>\n";

// Test class availability
echo "<h2>Class Availability Test</h2>\n";
echo "<ul>\n";
echo '<li>Marketing_Section class: ' . (class_exists('HappyPlace\\Dashboard\\Sections\\Marketing_Section') ? '<span style="color: green;">EXISTS</span>' : '<span style="color: red;">NOT FOUND</span>') . "</li>\n";
echo '<li>Marketing_Suite_Generator class: ' . (class_exists('HappyPlace\\Dashboard\\Marketing_Suite_Generator') ? '<span style="color: green;">EXISTS</span>' : '<span style="color: red;">NOT FOUND</span>') . "</li>\n";
echo "</ul>\n";

// Test plugin status
echo "<h2>Plugin Status</h2>\n";
if (function_exists('is_plugin_active')) {
    $plugin_file = 'Happy Place Plugin/happy-place.php';
    $is_active = is_plugin_active($plugin_file);
    echo "<p>Happy Place Plugin: " . ($is_active ? '<span style="color: green;">ACTIVE</span>' : '<span style="color: red;">INACTIVE</span>') . "</p>\n";
}

// Test current user capabilities
echo "<h2>Current User Capabilities</h2>\n";
$user = wp_get_current_user();
echo "<p>User ID: " . $user->ID . "</p>\n";
echo "<p>User login: " . $user->user_login . "</p>\n";
echo "<ul>\n";
echo "<li>can 'read': " . (current_user_can('read') ? 'YES' : 'NO') . "</li>\n";
echo "<li>can 'edit_posts': " . (current_user_can('edit_posts') ? 'YES' : 'NO') . "</li>\n";
echo "<li>can 'manage_options': " . (current_user_can('manage_options') ? 'YES' : 'NO') . "</li>\n";
echo "</ul>\n";

echo "<p><em>Test completed at " . date('Y-m-d H:i:s') . "</em></p>\n";
?>
