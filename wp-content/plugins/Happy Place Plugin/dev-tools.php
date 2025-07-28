<?php
/**
 * Standalone Developer Tools Script
 * 
 * Direct access to development functions for Happy Place theme and plugin.
 * Can be accessed directly via URL for quick development tasks.
 * 
 * Usage: /wp-content/plugins/Happy Place Plugin/dev-tools.php?action=build_sass&key=dev123
 */

// Security check - require a simple key for direct access
$dev_key = $_GET['key'] ?? '';
if ($dev_key !== 'dev123') {
    die('Access denied. Invalid key.');
}

// Load WordPress
require_once('../../../../wp-load.php');

// Check if user is admin when WordPress is loaded
if (!current_user_can('manage_options')) {
    die('Access denied. Admin privileges required.');
}

$action = $_GET['action'] ?? '';
$theme_path = get_template_directory();
$plugin_path = plugin_dir_path(__FILE__);

echo "<h1>Happy Place Developer Tools</h1>";
echo "<p>Action: <strong>$action</strong></p>";

switch ($action) {
    case 'build_sass':
        echo "<h2>Building Sass...</h2>";
        $output = run_build_command($theme_path, 'npm run build:sass');
        echo "<pre>$output</pre>";
        break;
        
    case 'build_webpack':
        echo "<h2>Building Webpack...</h2>";
        $output = run_build_command($theme_path, 'npm run build');
        echo "<pre>$output</pre>";
        break;
        
    case 'build_plugin':
        echo "<h2>Building Plugin Assets...</h2>";
        $output = run_build_command($plugin_path, 'npm run build');
        echo "<pre>$output</pre>";
        break;
        
    case 'flush_cache':
        echo "<h2>Flushing Cache...</h2>";
        wp_cache_flush();
        clear_expired_transients();
        echo "Cache flushed successfully!";
        break;
        
    case 'flush_rewrite':
        echo "<h2>Flushing Rewrite Rules...</h2>";
        flush_rewrite_rules(true);
        echo "Rewrite rules flushed successfully!";
        break;
        
    case 'env_info':
        echo "<h2>Environment Information</h2>";
        display_environment_info();
        break;
        
    case 'watch_sass':
        echo "<h2>Starting Sass Watch Mode...</h2>";
        echo "<p>This will start a background process. Check your terminal for output.</p>";
        $output = run_build_command($theme_path, 'npm run watch:sass > /dev/null 2>&1 &');
        echo "<pre>$output</pre>";
        break;
        
    default:
        echo "<h2>Available Actions:</h2>";
        echo "<ul>";
        echo "<li><a href='?action=build_sass&key=dev123'>build_sass</a> - Build theme Sass</li>";
        echo "<li><a href='?action=build_webpack&key=dev123'>build_webpack</a> - Build theme with Webpack</li>";
        echo "<li><a href='?action=build_plugin&key=dev123'>build_plugin</a> - Build plugin assets</li>";
        echo "<li><a href='?action=flush_cache&key=dev123'>flush_cache</a> - Clear WordPress cache</li>";
        echo "<li><a href='?action=flush_rewrite&key=dev123'>flush_rewrite</a> - Flush rewrite rules</li>";
        echo "<li><a href='?action=env_info&key=dev123'>env_info</a> - Show environment info</li>";
        echo "<li><a href='?action=watch_sass&key=dev123'>watch_sass</a> - Start Sass watch mode</li>";
        echo "</ul>";
}

/**
 * Run build command in specified directory
 */
function run_build_command(string $directory, string $command): string
{
    if (!is_dir($directory)) {
        return 'Error: Directory not found - ' . $directory;
    }

    $old_dir = getcwd();
    chdir($directory);
    
    $output = [];
    $return_code = 0;
    exec($command . ' 2>&1', $output, $return_code);
    
    chdir($old_dir);
    
    $output_text = implode("\n", $output);
    
    if ($return_code === 0) {
        return "Build successful!\n\n" . $output_text;
    } else {
        return "Build failed (exit code: $return_code)\n\n" . $output_text;
    }
}

/**
 * Clear expired transients from database
 */
function clear_expired_transients(): void
{
    global $wpdb;
    
    // Clear expired transients
    $expired = $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%' AND option_value < UNIX_TIMESTAMP()");
    
    // Clear orphaned transients
    $orphaned = $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%' AND option_name NOT LIKE '_transient_timeout_%' AND NOT EXISTS (SELECT 1 FROM {$wpdb->options} t2 WHERE t2.option_name = CONCAT('_transient_timeout_', SUBSTRING({$wpdb->options}.option_name, 12)))");
    
    echo "Cleared $expired expired transients and $orphaned orphaned transients.\n";
}

/**
 * Display environment information
 */
function display_environment_info(): void
{
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Setting</th><th>Value</th></tr>";
    echo "<tr><td>PHP Version</td><td>" . PHP_VERSION . "</td></tr>";
    echo "<tr><td>WordPress Version</td><td>" . get_bloginfo('version') . "</td></tr>";
    echo "<tr><td>Theme</td><td>" . get_template() . "</td></tr>";
    echo "<tr><td>Memory Limit</td><td>" . ini_get('memory_limit') . "</td></tr>";
    echo "<tr><td>Max Execution Time</td><td>" . ini_get('max_execution_time') . "s</td></tr>";
    echo "<tr><td>Upload Max Size</td><td>" . ini_get('upload_max_filesize') . "</td></tr>";
    echo "<tr><td>Server Software</td><td>" . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</td></tr>";
    echo "<tr><td>WP Debug</td><td>" . (defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled') . "</td></tr>";
    echo "<tr><td>Current Directory</td><td>" . getcwd() . "</td></tr>";
    echo "<tr><td>Theme Directory</td><td>" . get_template_directory() . "</td></tr>";
    echo "<tr><td>Plugin Directory</td><td>" . plugin_dir_path(__FILE__) . "</td></tr>";
    echo "</table>";
}

echo "<hr>";
echo "<p><small>Happy Place Developer Tools | Direct access script</small></p>";
?>
