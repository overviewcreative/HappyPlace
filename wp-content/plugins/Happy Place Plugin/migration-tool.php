<?php
/**
 * Manual ACF Field Groups Migration Trigger
 * 
 * This script can be run to manually trigger the ACF field groups migration
 * Run this file directly to execute the migration
 *
 * @package HappyPlace
 * @subpackage Migration
 */

// Prevent direct access unless we're intentionally running it
if (!defined('ABSPATH') && !isset($_GET['run_migration'])) {
    // Load WordPress if running directly
    $wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
    if (file_exists($wp_load_path)) {
        require_once $wp_load_path;
    } else {
        die('WordPress not found. Please run this from WordPress admin or with proper access.');
    }
}

// Only allow for administrators
if (!current_user_can('manage_options')) {
    die('Access denied. Administrator privileges required.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>ACF Field Groups Migration</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 40px; }
        .container { max-width: 800px; margin: 0 auto; }
        .notice { padding: 15px; border-left: 4px solid #00a0d2; background: #f7fcfe; margin: 20px 0; }
        .notice.success { border-color: #46b450; background: #f7fff7; }
        .notice.error { border-color: #dc3232; background: #fdf2f2; }
        .button { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; text-decoration: none; display: inline-block; }
        .button:hover { background: #005177; }
        .code { background: #f1f1f1; padding: 15px; border-radius: 4px; font-family: Consolas, Monaco, monospace; margin: 15px 0; }
        pre { white-space: pre-wrap; word-wrap: break-word; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 ACF Field Groups Migration Tool</h1>
        
        <?php if (isset($_POST['run_migration']) || isset($_GET['run_migration'])): ?>
            
            <?php
            // Load the migration class
            if (file_exists(HPH_INCLUDES_PATH . 'admin/class-acf-auto-migration.php')) {
                require_once HPH_INCLUDES_PATH . 'admin/class-acf-auto-migration.php';
                
                if (class_exists('HappyPlace\\Migration\\ACF_Auto_Migration')) {
                    $migration = HappyPlace\Migration\ACF_Auto_Migration::get_instance();
                    $success = $migration->trigger_manual_migration();
                    
                    if ($success) {
                        echo '<div class="notice success">';
                        echo '<h3>✅ Migration Completed Successfully!</h3>';
                        echo '<p>Your ACF field groups have been reorganized from 50+ scattered groups into 4 clean, logical groups:</p>';
                        echo '<ul>';
                        echo '<li><strong>Essential Listing Info</strong> - Only 4 required fields (price, status, MLS number, list date)</li>';
                        echo '<li><strong>Property Details & Features</strong> - Address, features, financial info with organized tabs</li>';
                        echo '<li><strong>Location Intelligence</strong> - Auto-populated external API data (Google Places, Walk Score, etc.)</li>';
                        echo '<li><strong>Advanced Analytics & Relationships</strong> - Calculations, mortgage calculator, relationships</li>';
                        echo '</ul>';
                        echo '<p><strong>Backup created:</strong> ' . basename(get_option('hph_migration_backup_file', 'backup-file.json')) . '</p>';
                        echo '<p><a href="' . admin_url('post-new.php?post_type=listing') . '" class="button">Create New Listing to Test</a></p>';
                        echo '</div>';
                    } else {
                        echo '<div class="notice error">';
                        echo '<h3>❌ Migration Failed</h3>';
                        echo '<p>There was an error during migration. Please check the error logs.</p>';
                        echo '</div>';
                    }
                    
                    // Show status
                    $status = $migration->get_migration_status();
                    echo '<div class="code">';
                    echo '<h4>Migration Status:</h4>';
                    echo '<pre>' . print_r($status, true) . '</pre>';
                    echo '</div>';
                    
                } else {
                    echo '<div class="notice error"><p>Migration class not found.</p></div>';
                }
            } else {
                echo '<div class="notice error"><p>Migration file not found.</p></div>';
            }
            ?>
            
        <?php else: ?>
            
            <div class="notice">
                <h3>🎯 Migration Overview</h3>
                <p>This tool will migrate your ACF field groups from the current scattered structure to a new organized system:</p>
                
                <h4>Current Structure (50+ scattered groups):</h4>
                <ul>
                    <li>Many separate field groups with no logical organization</li>
                    <li>20+ required fields making listing creation difficult</li>
                    <li>Confusing interface with fields spread across multiple locations</li>
                </ul>
                
                <h4>New Structure (4 logical groups):</h4>
                <ul>
                    <li><strong>Essential Listing Info:</strong> Only 4 required fields (80% reduction!)</li>
                    <li><strong>Property Details & Features:</strong> Organized with tabs for better UX</li>
                    <li><strong>Location Intelligence:</strong> Auto-populated external data</li>
                    <li><strong>Advanced Analytics:</strong> Calculations and relationships</li>
                </ul>
                
                <h4>What this migration does:</h4>
                <ul>
                    <li>✅ Creates backup of all current listing data</li>
                    <li>✅ Activates new organized field groups</li>
                    <li>✅ Preserves all existing field data (no data loss)</li>
                    <li>✅ Deactivates old scattered groups (but keeps data)</li>
                    <li>✅ Updates field group display order</li>
                </ul>
            </div>
            
            <form method="post" onsubmit="return confirm('Are you sure you want to run the migration? A backup will be created first.');">
                <input type="hidden" name="run_migration" value="1">
                <button type="submit" class="button">🚀 Run Migration Now</button>
            </form>
            
            <p><small><strong>Safe to run:</strong> This migration creates a full backup before making any changes. All your data will be preserved.</small></p>
            
        <?php endif; ?>
        
        <hr style="margin: 40px 0;">
        
        <h3>📋 Quick Actions</h3>
        <p>
            <a href="<?php echo admin_url('edit.php?post_type=listing'); ?>" class="button">View All Listings</a>
            <a href="<?php echo admin_url('post-new.php?post_type=listing'); ?>" class="button">Create New Listing</a>
            <a href="<?php echo admin_url('edit.php?post_type=acf-field-group'); ?>" class="button">Manage Field Groups</a>
        </p>
        
        <h3>🎯 Post-Migration Benefits</h3>
        <ul>
            <li><strong>Faster Listing Creation:</strong> Only 4 required fields instead of 20+</li>
            <li><strong>Better Organization:</strong> Logical tabs and groupings</li>
            <li><strong>Auto-Population:</strong> External data filled automatically</li>
            <li><strong>Cleaner Interface:</strong> No more scattered fields</li>
        </ul>
    </div>
</body>
</html>
