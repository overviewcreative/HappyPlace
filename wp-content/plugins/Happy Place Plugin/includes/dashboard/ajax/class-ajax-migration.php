<?php

/**
 * Dashboard AJAX Migration Script
 * 
 * Handles the transition from the monolithic Dashboard_Ajax_Handler
 * to the new modular AJAX system. Provides backwards compatibility
 * and migration utilities.
 * 
 * @package HappyPlace\Dashboard\Ajax
 * @since 3.0.0
 */

namespace HappyPlace\Dashboard\Ajax;

use Exception;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Migration Manager Class
 * 
 * Coordinates the migration from old to new AJAX system
 * while maintaining backwards compatibility.
 */
class HPH_Ajax_Migration
{
    /**
     * @var string Migration status option key
     */
    private const MIGRATION_STATUS_KEY = 'hph_ajax_migration_status';

    /**
     * @var string Migration version option key
     */
    private const MIGRATION_VERSION_KEY = 'hph_ajax_migration_version';

    /**
     * @var array Old AJAX actions that need to be migrated
     */
    private array $legacy_actions = [
        'hph_load_dashboard_section' => 'section',
        'hph_get_overview_stats' => 'analytics',
        'hph_get_recent_activity' => 'section',
        'hph_get_notifications' => 'section',
        'hph_get_listing_data' => 'listing',
        'hph_get_agent_activity' => 'analytics',
        'hph_duplicate_listing' => 'listing',
        'hph_delete_listing' => 'listing',
        'hph_get_performance_data' => 'analytics',
        'hph_save_listing' => 'form',
        'hph_save_lead' => 'form',
        'hph_save_open_house' => 'form',
        'hph_save_agent_profile' => 'form',
        'hph_toggle_listing_status' => 'listing',
        'hph_upload_listing_image' => 'listing'
    ];

    /**
     * Initialize migration
     */
    public static function init(): void
    {
        $migration = new self();
        $migration->check_migration_status();
        $migration->setup_migration_hooks();
    }

    /**
     * Check current migration status
     */
    private function check_migration_status(): void
    {
        $status = get_option(self::MIGRATION_STATUS_KEY, 'not_started');
        $version = get_option(self::MIGRATION_VERSION_KEY, '0.0.0');

        switch ($status) {
            case 'not_started':
                $this->start_migration();
                break;
            case 'in_progress':
                $this->continue_migration();
                break;
            case 'completed':
                if (version_compare($version, '3.0.0', '<')) {
                    $this->upgrade_migration();
                }
                break;
        }
    }

    /**
     * Setup migration hooks
     */
    private function setup_migration_hooks(): void
    {
        // Admin notices for migration status
        add_action('admin_notices', [$this, 'show_migration_notices']);

        // AJAX hook for manual migration trigger
        add_action('wp_ajax_hph_trigger_migration', [$this, 'trigger_manual_migration']);

        // Backwards compatibility hooks
        add_action('wp_ajax_hph_legacy_fallback', [$this, 'handle_legacy_fallback']);

        // Migration cleanup hook
        add_action('wp_ajax_hph_cleanup_migration', [$this, 'cleanup_migration']);
    }

    /**
     * Start the migration process
     */
    private function start_migration(): void
    {
        update_option(self::MIGRATION_STATUS_KEY, 'in_progress');
        update_option('hph_migration_start_time', current_time('mysql'));

        // Log migration start
        error_log('HPH AJAX Migration: Starting migration from monolithic to modular system');

        // Create backup of old handler settings
        $this->backup_old_settings();

        // Initialize new system
        $this->initialize_new_system();

        // Mark as completed if successful
        update_option(self::MIGRATION_STATUS_KEY, 'completed');
        update_option(self::MIGRATION_VERSION_KEY, '3.0.0');
        update_option('hph_migration_end_time', current_time('mysql'));

        error_log('HPH AJAX Migration: Migration completed successfully');
    }

    /**
     * Continue interrupted migration
     */
    private function continue_migration(): void
    {
        error_log('HPH AJAX Migration: Continuing interrupted migration');
        
        // Check what steps are incomplete
        $this->verify_migration_steps();
        
        // Complete any missing steps
        $this->complete_migration();
    }

    /**
     * Upgrade existing migration
     */
    private function upgrade_migration(): void
    {
        error_log('HPH AJAX Migration: Upgrading migration to version 3.0.0');
        
        $this->update_database_schema();
        $this->migrate_settings();
        
        update_option(self::MIGRATION_VERSION_KEY, '3.0.0');
    }

    /**
     * Show admin notices about migration
     */
    public function show_migration_notices(): void
    {
        $status = get_option(self::MIGRATION_STATUS_KEY, 'not_started');
        
        switch ($status) {
            case 'in_progress':
                ?>
                <div class="notice notice-warning">
                    <p><strong>Happy Place Dashboard:</strong> AJAX system migration is in progress. Some features may be temporarily unavailable.</p>
                </div>
                <?php
                break;
                
            case 'completed':
                $migration_time = get_option('hph_migration_end_time');
                if ($migration_time && (time() - strtotime($migration_time)) < 86400) { // Show for 24 hours
                    ?>
                    <div class="notice notice-success is-dismissible">
                        <p><strong>Happy Place Dashboard:</strong> AJAX system has been successfully upgraded to the new modular architecture!</p>
                        <p>Benefits: Improved performance, better error handling, and enhanced security.</p>
                    </div>
                    <?php
                }
                break;
                
            case 'failed':
                ?>
                <div class="notice notice-error">
                    <p><strong>Happy Place Dashboard:</strong> AJAX migration failed. Please contact support or try manual migration.</p>
                    <p><a href="#" class="button" onclick="hphTriggerMigration()">Retry Migration</a></p>
                </div>
                <script>
                function hphTriggerMigration() {
                    jQuery.post(ajaxurl, {
                        action: 'hph_trigger_migration',
                        nonce: '<?php echo wp_create_nonce('hph_migration'); ?>'
                    }, function(response) {
                        location.reload();
                    });
                }
                </script>
                <?php
                break;
        }
    }

    /**
     * Handle manual migration trigger
     */
    public function trigger_manual_migration(): void
    {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'hph_migration')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        try {
            update_option(self::MIGRATION_STATUS_KEY, 'not_started');
            $this->start_migration();
            
            wp_send_json_success([
                'message' => 'Migration completed successfully',
                'status' => 'completed'
            ]);
        } catch (Exception $e) {
            error_log('HPH AJAX Migration Error: ' . $e->getMessage());
            update_option(self::MIGRATION_STATUS_KEY, 'failed');
            
            wp_send_json_error([
                'message' => 'Migration failed: ' . $e->getMessage(),
                'status' => 'failed'
            ]);
        }
    }

    /**
     * Handle legacy AJAX fallback
     */
    public function handle_legacy_fallback(): void
    {
        $original_action = $_POST['original_action'] ?? '';
        
        if (!isset($this->legacy_actions[$original_action])) {
            wp_send_json_error('Unknown legacy action');
            return;
        }

        $handler_type = $this->legacy_actions[$original_action];
        $ajax_manager = HPH_Ajax_Manager::instance();
        $handler = $ajax_manager->get_handler($handler_type);

        if (!$handler) {
            wp_send_json_error('Handler not available');
            return;
        }

        // Map legacy action to new method
        $new_method = $this->map_legacy_method($original_action);
        
        if (method_exists($handler, $new_method)) {
            // Temporarily change the action for the handler
            $_POST['action'] = $original_action;
            call_user_func([$handler, $new_method]);
        } else {
            wp_send_json_error('Method not implemented in new system');
        }
    }

    /**
     * Backup old handler settings
     */
    private function backup_old_settings(): void
    {
        $backup = [
            'timestamp' => current_time('mysql'),
            'old_actions' => $this->legacy_actions,
            'wp_version' => get_bloginfo('version'),
            'plugin_version' => get_option('hph_plugin_version', 'unknown')
        ];

        update_option('hph_ajax_migration_backup', $backup);
        error_log('HPH AJAX Migration: Backup created');
    }

    /**
     * Initialize new modular system
     */
    private function initialize_new_system(): void
    {
        // Ensure new system is loaded
        if (!class_exists('HappyPlace\\Dashboard\\Ajax\\HPH_Ajax_Manager')) {
            throw new Exception('New AJAX system not available');
        }

        // Initialize manager
        $manager = HPH_Ajax_Manager::instance();

        // Verify all handlers are loaded
        $required_handlers = ['section', 'form', 'listing', 'analytics'];
        foreach ($required_handlers as $handler) {
            if (!$manager->is_handler_loaded($handler)) {
                throw new Exception("Handler '{$handler}' failed to load");
            }
        }

        error_log('HPH AJAX Migration: New system initialized successfully');
    }

    /**
     * Verify migration steps are complete
     */
    private function verify_migration_steps(): void
    {
        $steps = [
            'backup_created' => get_option('hph_ajax_migration_backup') !== false,
            'database_updated' => get_option('hph_ajax_db_version') !== false,
            'handlers_loaded' => class_exists('HappyPlace\\Dashboard\\Ajax\\HPH_Ajax_Manager')
        ];

        foreach ($steps as $step => $completed) {
            if (!$completed) {
                error_log("HPH AJAX Migration: Step '{$step}' incomplete");
            }
        }
    }

    /**
     * Complete migration process
     */
    private function complete_migration(): void
    {
        // Re-run initialization
        $this->initialize_new_system();
        
        // Update status
        update_option(self::MIGRATION_STATUS_KEY, 'completed');
        update_option(self::MIGRATION_VERSION_KEY, '3.0.0');
    }

    /**
     * Update database schema for new system
     */
    private function update_database_schema(): void
    {
        $manager = HPH_Ajax_Manager::instance();
        $manager->create_ajax_tables();
    }

    /**
     * Migrate settings from old to new system
     */
    private function migrate_settings(): void
    {
        // Migrate any relevant settings
        $old_settings = get_option('hph_ajax_settings', []);
        
        if (!empty($old_settings)) {
            update_option('hph_ajax_new_settings', $old_settings);
            error_log('HPH AJAX Migration: Settings migrated');
        }
    }

    /**
     * Map legacy action to new method name
     */
    private function map_legacy_method(string $legacy_action): string
    {
        $method_map = [
            'hph_load_dashboard_section' => 'load_section',
            'hph_get_overview_stats' => 'get_overview_stats',
            'hph_get_listing_data' => 'get_listing_data',
            'hph_duplicate_listing' => 'duplicate_listing',
            'hph_delete_listing' => 'delete_listing',
            'hph_get_performance_data' => 'get_performance_data',
            'hph_save_listing' => 'save_listing_form',
            'hph_save_lead' => 'save_lead_form',
            'hph_toggle_listing_status' => 'toggle_listing_status'
        ];

        return $method_map[$legacy_action] ?? str_replace('hph_', '', $legacy_action);
    }

    /**
     * Cleanup migration data
     */
    public function cleanup_migration(): void
    {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'hph_migration')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        // Remove migration-related options
        delete_option('hph_ajax_migration_backup');
        delete_option('hph_migration_start_time');
        delete_option('hph_migration_end_time');

        wp_send_json_success('Migration cleanup completed');
    }

    /**
     * Get migration status for admin dashboard
     */
    public static function get_migration_status(): array
    {
        return [
            'status' => get_option(self::MIGRATION_STATUS_KEY, 'not_started'),
            'version' => get_option(self::MIGRATION_VERSION_KEY, '0.0.0'),
            'start_time' => get_option('hph_migration_start_time'),
            'end_time' => get_option('hph_migration_end_time'),
            'backup_exists' => get_option('hph_ajax_migration_backup') !== false
        ];
    }

    /**
     * Check if migration is needed
     */
    public static function needs_migration(): bool
    {
        $status = get_option(self::MIGRATION_STATUS_KEY, 'not_started');
        return in_array($status, ['not_started', 'failed']);
    }

    /**
     * Check if migration is complete
     */
    public static function is_migration_complete(): bool
    {
        $status = get_option(self::MIGRATION_STATUS_KEY, 'not_started');
        $version = get_option(self::MIGRATION_VERSION_KEY, '0.0.0');
        
        return $status === 'completed' && version_compare($version, '3.0.0', '>=');
    }
}

// Initialize migration on plugin load
add_action('plugins_loaded', function() {
    HPH_Ajax_Migration::init();
}, 15); // Load after the main AJAX manager
