<?php
/**
 * ACF Field Groups Auto-Migration
 * 
 * Automatically handles the migration from old field groups to new organized structure
 *
 * @package HappyPlace
 * @subpackage Migration
 */

namespace HappyPlace\Migration;

if (!defined('ABSPATH')) {
    exit;
}

class ACF_Auto_Migration {
    
    /**
     * Instance
     */
    private static ?self $instance = null;
    
    /**
     * Migration option key
     */
    private string $migration_option = 'hph_acf_migration_completed';
    
    /**
     * Get instance
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Check if migration is needed on admin init
        add_action('admin_init', [$this, 'check_and_run_migration']);
        
        // Add admin notice for migration status
        add_action('admin_notices', [$this, 'show_migration_notice']);
    }
    
    /**
     * Check if migration is needed and run it
     */
    public function check_and_run_migration(): void {
        // Skip if migration already completed
        if (get_option($this->migration_option, false)) {
            return;
        }
        
        // Skip if not on listings page or ACF is not active
        if (!function_exists('acf_get_field_groups') || !$this->should_run_migration()) {
            return;
        }
        
        $this->run_automatic_migration();
    }
    
    /**
     * Check if migration should run
     */
    private function should_run_migration(): bool {
        // Only run for admins
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        // Check if we have old field groups
        $old_groups = acf_get_field_groups(['post_type' => 'listing']);
        
        foreach ($old_groups as $group) {
            if ($group['key'] === 'group_listing_details') {
                return true; // Old structure detected
            }
        }
        
        return false;
    }
    
    /**
     * Run automatic migration
     */
    private function run_automatic_migration(): void {
        try {
            // Create backup first
            $this->create_field_backup();
            
            // Sync new field groups from JSON
            $this->sync_new_field_groups();
            
            // Update menu orders for proper display
            $this->update_field_group_orders();
            
            // Mark migration as completed
            update_option($this->migration_option, true);
            update_option($this->migration_option . '_date', current_time('mysql'));
            
            // Set success notice
            set_transient('hph_migration_success', true, 30);
            
        } catch (\Exception $e) {
            error_log('HPH Migration Error: ' . $e->getMessage());
            set_transient('hph_migration_error', $e->getMessage(), 30);
        }
    }
    
    /**
     * Create backup of current field data
     */
    private function create_field_backup(): void {
        $listings = get_posts([
            'post_type' => 'listing',
            'post_status' => 'any',
            'numberposts' => 100, // Limit for performance
            'fields' => 'ids'
        ]);
        
        $backup_data = [];
        
        foreach ($listings as $listing_id) {
            $fields = get_fields($listing_id);
            if ($fields) {
                $backup_data[$listing_id] = $fields;
            }
        }
        
        // Save backup
        $backup_file = WP_CONTENT_DIR . '/uploads/hph-auto-migration-backup-' . date('Y-m-d-H-i-s') . '.json';
        file_put_contents($backup_file, json_encode($backup_data, JSON_PRETTY_PRINT));
        
        update_option('hph_migration_backup_file', $backup_file);
    }
    
    /**
     * Sync new field groups from JSON files
     */
    private function sync_new_field_groups(): void {
        $new_groups = [
            'group_essential_listing_info',
            'group_property_details_features', 
            'group_location_intelligence_new',
            'group_advanced_analytics_relationships'
        ];
        
        foreach ($new_groups as $group_key) {
            $json_file = HPH_INCLUDES_PATH . "fields/acf-json/{$group_key}.json";
            
            if (file_exists($json_file)) {
                $field_group_data = json_decode(file_get_contents($json_file), true);
                
                if ($field_group_data) {
                    // Import or update the field group
                    if (function_exists('acf_import_field_group')) {
                        acf_import_field_group($field_group_data);
                    } else {
                        // Fallback: create field group manually
                        acf_update_field_group($field_group_data);
                    }
                }
            }
        }
    }
    
    /**
     * Update field group menu orders
     */
    private function update_field_group_orders(): void {
        $group_orders = [
            'group_essential_listing_info' => 1,
            'group_property_details_features' => 2,
            'group_location_intelligence_new' => 3,
            'group_advanced_analytics_relationships' => 4
        ];
        
        foreach ($group_orders as $group_key => $order) {
            $field_group = acf_get_field_group($group_key);
            if ($field_group) {
                acf_update_field_group([
                    'key' => $group_key,
                    'menu_order' => $order,
                    'active' => true
                ]);
            }
        }
        
        // Deactivate old main group (keep data)
        $old_group = acf_get_field_group('group_listing_details');
        if ($old_group) {
            acf_update_field_group([
                'key' => 'group_listing_details',
                'active' => false
            ]);
        }
    }
    
    /**
     * Show migration notice
     */
    public function show_migration_notice(): void {
        // Success notice
        if (get_transient('hph_migration_success')) {
            delete_transient('hph_migration_success');
            ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>✅ ACF Field Groups Migration Completed Successfully!</strong></p>
                <p>Your listing field groups have been reorganized into 4 clean, logical groups with improved usability. Only 4 fields are now required instead of 20+.</p>
                <p>Backup created at: <code><?php echo basename(get_option('hph_migration_backup_file', '')); ?></code></p>
            </div>
            <?php
        }
        
        // Error notice
        if ($error = get_transient('hph_migration_error')) {
            delete_transient('hph_migration_error');
            ?>
            <div class="notice notice-error is-dismissible">
                <p><strong>❌ ACF Field Groups Migration Error</strong></p>
                <p><?php echo esc_html($error); ?></p>
                <p>Please check error logs or contact support.</p>
            </div>
            <?php
        }
        
        // Show completion notice to admins
        if (get_option($this->migration_option, false) && current_user_can('manage_options')) {
            $migration_date = get_option($this->migration_option . '_date');
            if ($migration_date && (time() - strtotime($migration_date)) < DAY_IN_SECONDS) {
                ?>
                <div class="notice notice-info is-dismissible">
                    <p><strong>🎉 New ACF Field Organization Active!</strong></p>
                    <p>Your listing fields are now organized into 4 logical groups. <a href="<?php echo admin_url('post-new.php?post_type=listing'); ?>">Create a new listing</a> to see the improved interface.</p>
                </div>
                <?php
            }
        }
    }
    
    /**
     * Manual migration trigger (for admin use)
     */
    public function trigger_manual_migration(): bool {
        try {
            // Reset migration flag
            delete_option($this->migration_option);
            
            // Run migration
            $this->run_automatic_migration();
            
            return true;
        } catch (\Exception $e) {
            error_log('HPH Manual Migration Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get migration status
     */
    public function get_migration_status(): array {
        return [
            'completed' => get_option($this->migration_option, false),
            'completion_date' => get_option($this->migration_option . '_date'),
            'backup_file' => get_option('hph_migration_backup_file'),
            'new_groups_active' => $this->check_new_groups_active()
        ];
    }
    
    /**
     * Check if new field groups are active
     */
    private function check_new_groups_active(): bool {
        $new_groups = [
            'group_essential_listing_info',
            'group_property_details_features', 
            'group_location_intelligence_new',
            'group_advanced_analytics_relationships'
        ];
        
        foreach ($new_groups as $group_key) {
            $group = acf_get_field_group($group_key);
            if (!$group || !$group['active']) {
                return false;
            }
        }
        
        return true;
    }
}

// Auto-initialize if in admin
if (is_admin()) {
    ACF_Auto_Migration::get_instance();
}
