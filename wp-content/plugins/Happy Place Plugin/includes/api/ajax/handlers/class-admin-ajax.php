<?php
/**
 * Admin AJAX Handler
 *
 * Handles all administrative AJAX operations including:
 * - Settings management
 * - User management
 * - CSV import/export
 * - System validation
 * - Data cleanup operations
 *
 * @package Happy_Place
 * @subpackage API\Ajax\Handlers
 * @since 2.0.0
 */

namespace HappyPlace\Api\Ajax\Handlers;

use HappyPlace\Api\Ajax\Base_Ajax_Handler;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin AJAX Handler Class
 *
 * Consolidates administrative functionality from:
 * - Enhanced_Admin (includes/admin/class-enhanced-admin.php)
 * - Settings_Ajax (includes/admin/class-settings-ajax.php)
 * - Various CSV handlers
 * - System validation tools
 */
class Admin_Ajax extends Base_Ajax_Handler {

    /**
     * Define AJAX actions and their configurations
     */
    protected function get_actions(): array {
        return [
            // Settings Management
            'save_settings' => [
                'callback' => 'handle_save_settings',
                'capability' => 'manage_options',
                'public' => false,
                'rate_limit' => 10
            ],
            'reset_settings' => [
                'callback' => 'handle_reset_settings',
                'capability' => 'manage_options',
                'public' => false,
                'rate_limit' => 5
            ],
            'export_settings' => [
                'callback' => 'handle_export_settings',
                'capability' => 'manage_options',
                'public' => false,
                'rate_limit' => 5
            ],
            'import_settings' => [
                'callback' => 'handle_import_settings',
                'capability' => 'manage_options',
                'public' => false,
                'rate_limit' => 5
            ],
            
            // User Management
            'manage_users' => [
                'callback' => 'handle_manage_users',
                'capability' => 'edit_users',
                'public' => false,
                'rate_limit' => 20
            ],
            'bulk_user_actions' => [
                'callback' => 'handle_bulk_user_actions',
                'capability' => 'edit_users',
                'public' => false,
                'rate_limit' => 5
            ],
            
            // CSV Operations
            'csv_export' => [
                'callback' => 'handle_csv_export',
                'capability' => 'export',
                'public' => false,
                'rate_limit' => 3
            ],
            'csv_import' => [
                'callback' => 'handle_csv_import',
                'capability' => 'import',
                'public' => false,
                'rate_limit' => 3
            ],
            'validate_csv' => [
                'callback' => 'handle_validate_csv',
                'capability' => 'import',
                'public' => false,
                'rate_limit' => 10
            ],
            
            // System Operations
            'validate_system' => [
                'callback' => 'handle_validate_system',
                'capability' => 'manage_options',
                'public' => false,
                'rate_limit' => 5
            ],
            'cleanup_data' => [
                'callback' => 'handle_cleanup_data',
                'capability' => 'manage_options',
                'public' => false,
                'rate_limit' => 3
            ],
            'refresh_cache' => [
                'callback' => 'handle_refresh_cache',
                'capability' => 'manage_options',
                'public' => false,
                'rate_limit' => 10
            ],
            
            // Debug Operations
            'debug_info' => [
                'callback' => 'handle_debug_info',
                'capability' => 'manage_options',
                'public' => false,
                'rate_limit' => 10
            ],
            'test_connection' => [
                'callback' => 'handle_test_connection',
                'capability' => 'manage_options',
                'public' => false,
                'rate_limit' => 5
            ]
        ];
    }

    /**
     * Handle settings save operation
     */
    public function handle_save_settings(): void {
        try {
            if (!$this->validate_required_params(['settings' => 'array'])) {
                return;
            }
            
            $settings = $_POST['settings'];
            
            // Sanitize and save settings
            $sanitized_settings = $this->sanitize_settings($settings);
            $result = update_option('happy_place_settings', $sanitized_settings);
            
            if (!$result) {
                $this->send_error('Failed to save settings');
                return;
            }
            
            $this->send_success([
                'message' => 'Settings saved successfully',
                'settings' => $sanitized_settings
            ]);
            
        } catch (\Exception $e) {
            $this->send_error('Failed to save settings: ' . $e->getMessage());
        }
    }

    /**
     * Handle settings reset operation
     */
    public function handle_reset_settings(): void {
        try {
            $default_settings = $this->get_default_settings();
            $result = update_option('happy_place_settings', $default_settings);
            
            if (!$result) {
                $this->send_error('Failed to reset settings');
                return;
            }
            
            $this->send_success([
                'message' => 'Settings reset to defaults',
                'settings' => $default_settings
            ]);
            
        } catch (\Exception $e) {
            $this->send_error('Failed to reset settings: ' . $e->getMessage());
        }
    }

    /**
     * Handle CSV export operation
     */
    public function handle_csv_export(): void {
        try {
            if (!$this->validate_required_params(['data_type' => 'string'])) {
                return;
            }
            
            $data_type = $_POST['data_type'];
            
            // Validate export type
            $allowed_types = ['users', 'events', 'bookings', 'settings'];
            if (!in_array($data_type, $allowed_types)) {
                $this->send_error('Invalid export type');
                return;
            }
            
            // Generate CSV data
            $csv_data = $this->generate_csv_data($data_type);
            $filename = sprintf('happy_place_%s_%s.csv', $data_type, date('Y-m-d_H-i-s'));
            
            $this->send_success([
                'message' => 'CSV export generated successfully',
                'filename' => $filename,
                'data' => $csv_data,
                'download_url' => $this->create_download_url($filename, $csv_data)
            ]);
            
        } catch (\Exception $e) {
            $this->send_error('Failed to export CSV: ' . $e->getMessage());
        }
    }

    /**
     * Handle system validation operation
     */
    public function handle_validate_system(): void {
        try {
            $validation_results = [
                'database' => $this->validate_database(),
                'files' => $this->validate_files(),
                'permissions' => $this->validate_permissions(),
                'integrations' => $this->validate_integrations(),
                'cache' => $this->validate_cache()
            ];
            
            $overall_status = $this->calculate_overall_status($validation_results);
            
            $this->send_success([
                'message' => 'System validation completed',
                'status' => $overall_status,
                'results' => $validation_results,
                'timestamp' => current_time('mysql')
            ]);
            
        } catch (\Exception $e) {
            $this->send_error('System validation failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle data cleanup operation
     */
    public function handle_cleanup_data(): void {
        try {
            if (!$this->validate_required_params(['cleanup_type' => 'string'])) {
                return;
            }
            
            $cleanup_type = $_POST['cleanup_type'];
            $cleanup_results = [];
            
            switch ($cleanup_type) {
                case 'cache':
                    $cleanup_results = $this->cleanup_cache();
                    break;
                case 'logs':
                    $cleanup_results = $this->cleanup_logs();
                    break;
                case 'temp_files':
                    $cleanup_results = $this->cleanup_temp_files();
                    break;
                case 'orphaned_data':
                    $cleanup_results = $this->cleanup_orphaned_data();
                    break;
                default:
                    $this->send_error('Invalid cleanup type');
                    return;
            }
            
            $this->send_success([
                'message' => 'Data cleanup completed successfully',
                'type' => $cleanup_type,
                'results' => $cleanup_results
            ]);
            
        } catch (\Exception $e) {
            $this->send_error('Data cleanup failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle settings import operation
     */
    public function handle_import_settings(): void {
        try {
            if (!$this->validate_required_params(['settings_data' => 'string'])) {
                return;
            }
            
            $settings_data = json_decode($_POST['settings_data'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->send_error('Invalid JSON data provided');
                return;
            }
            
            $sanitized_settings = $this->sanitize_settings($settings_data);
            $result = update_option('happy_place_settings', $sanitized_settings);
            
            if (!$result) {
                $this->send_error('Failed to import settings');
                return;
            }
            
            $this->send_success([
                'message' => 'Settings imported successfully',
                'settings' => $sanitized_settings
            ]);
            
        } catch (\Exception $e) {
            $this->send_error('Failed to import settings: ' . $e->getMessage());
        }
    }

    /**
     * Handle settings export operation
     */
    public function handle_export_settings(): void {
        try {
            $settings = get_option('happy_place_settings', $this->get_default_settings());
            $export_data = json_encode($settings, JSON_PRETTY_PRINT);
            $filename = 'happy_place_settings_' . date('Y-m-d_H-i-s') . '.json';
            
            $this->send_success([
                'message' => 'Settings exported successfully',
                'filename' => $filename,
                'data' => $export_data,
                'download_url' => $this->create_download_url($filename, $export_data)
            ]);
            
        } catch (\Exception $e) {
            $this->send_error('Failed to export settings: ' . $e->getMessage());
        }
    }

    /**
     * Handle user management operations
     */
    public function handle_manage_users(): void {
        try {
            if (!$this->validate_required_params(['action' => 'string'])) {
                return;
            }
            
            $action = $_POST['action'];
            
            switch ($action) {
                case 'list':
                    $users = $this->get_users_list();
                    $this->send_success(['users' => $users]);
                    break;
                case 'update':
                    $this->update_user();
                    break;
                case 'delete':
                    $this->delete_user();
                    break;
                default:
                    $this->send_error('Invalid user management action');
            }
            
        } catch (\Exception $e) {
            $this->send_error('User management failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle bulk user actions
     */
    public function handle_bulk_user_actions(): void {
        try {
            if (!$this->validate_required_params(['action' => 'string', 'user_ids' => 'array'])) {
                return;
            }
            
            $action = $_POST['action'];
            $user_ids = $_POST['user_ids'];
            $results = [];
            
            foreach ($user_ids as $user_id) {
                $results[$user_id] = $this->perform_bulk_user_action($action, $user_id);
            }
            
            $this->send_success([
                'message' => 'Bulk user action completed',
                'action' => $action,
                'results' => $results
            ]);
            
        } catch (\Exception $e) {
            $this->send_error('Bulk user action failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle CSV import operation
     */
    public function handle_csv_import(): void {
        try {
            if (!$this->validate_required_params(['csv_data' => 'string', 'import_type' => 'string'])) {
                return;
            }
            
            $csv_data = $_POST['csv_data'];
            $import_type = $_POST['import_type'];
            
            $results = $this->process_csv_import($csv_data, $import_type);
            
            $this->send_success([
                'message' => 'CSV import completed',
                'type' => $import_type,
                'results' => $results
            ]);
            
        } catch (\Exception $e) {
            $this->send_error('CSV import failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle CSV validation operation
     */
    public function handle_validate_csv(): void {
        try {
            if (!$this->validate_required_params(['csv_data' => 'string', 'import_type' => 'string'])) {
                return;
            }
            
            $csv_data = $_POST['csv_data'];
            $import_type = $_POST['import_type'];
            
            $validation_results = $this->validate_csv_data($csv_data, $import_type);
            
            $this->send_success([
                'message' => 'CSV validation completed',
                'type' => $import_type,
                'validation' => $validation_results
            ]);
            
        } catch (\Exception $e) {
            $this->send_error('CSV validation failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle cache refresh operation
     */
    public function handle_refresh_cache(): void {
        try {
            $cache_types = $_POST['cache_types'] ?? ['all'];
            $results = [];
            
            foreach ($cache_types as $cache_type) {
                $results[$cache_type] = $this->refresh_cache_type($cache_type);
            }
            
            $this->send_success([
                'message' => 'Cache refresh completed',
                'results' => $results
            ]);
            
        } catch (\Exception $e) {
            $this->send_error('Cache refresh failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle debug info operation
     */
    public function handle_debug_info(): void {
        try {
            $debug_info = [
                'php_version' => PHP_VERSION,
                'wordpress_version' => get_bloginfo('version'),
                'plugin_version' => $this->get_plugin_version(),
                'memory_usage' => memory_get_usage(true),
                'memory_limit' => ini_get('memory_limit'),
                'active_plugins' => get_option('active_plugins'),
                'theme' => wp_get_theme()->get('Name'),
                'database_version' => $this->get_database_version(),
                'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
            ];
            
            $this->send_success([
                'message' => 'Debug information retrieved',
                'debug_info' => $debug_info
            ]);
            
        } catch (\Exception $e) {
            $this->send_error('Failed to retrieve debug info: ' . $e->getMessage());
        }
    }

    /**
     * Handle connection test operation
     */
    public function handle_test_connection(): void {
        try {
            if (!$this->validate_required_params(['connection_type' => 'string'])) {
                return;
            }
            
            $connection_type = $_POST['connection_type'];
            $test_results = [];
            
            switch ($connection_type) {
                case 'database':
                    $test_results = $this->test_database_connection();
                    break;
                case 'airtable':
                    $test_results = $this->test_airtable_connection_full();
                    break;
                case 'email':
                    $test_results = $this->test_email_connection();
                    break;
                default:
                    $this->send_error('Invalid connection type');
                    return;
            }
            
            $this->send_success([
                'message' => 'Connection test completed',
                'type' => $connection_type,
                'results' => $test_results
            ]);
            
        } catch (\Exception $e) {
            $this->send_error('Connection test failed: ' . $e->getMessage());
        }
    }

    /**
     * Sanitize settings array
     */
    private function sanitize_settings(array $settings): array {
        $sanitized = [];
        
        foreach ($settings as $key => $value) {
            $sanitized_key = sanitize_key($key);
            
            if (is_array($value)) {
                $sanitized[$sanitized_key] = $this->sanitize_settings($value);
            } elseif (is_bool($value)) {
                $sanitized[$sanitized_key] = (bool) $value;
            } elseif (is_numeric($value)) {
                $sanitized[$sanitized_key] = is_int($value) ? intval($value) : floatval($value);
            } else {
                $sanitized[$sanitized_key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }

    /**
     * Get default settings
     */
    private function get_default_settings(): array {
        return [
            'general' => [
                'enabled' => true,
                'debug_mode' => false,
                'cache_enabled' => true
            ],
            'integrations' => [
                'airtable_enabled' => false,
                'email_enabled' => true
            ],
            'performance' => [
                'cache_duration' => 3600,
                'max_file_size' => 5242880 // 5MB
            ]
        ];
    }

    /**
     * Generate CSV data based on type
     */
    private function generate_csv_data(string $type): string {
        // Implementation will be extracted from existing CSV handlers
        // This is a placeholder for the consolidation process
        return "CSV data for {$type} - Implementation pending consolidation";
    }

    /**
     * Validate database integrity
     */
    private function validate_database(): array {
        global $wpdb;
        
        $results = [
            'connection' => true,
            'tables_exist' => true,
            'indexes_valid' => true,
            'issues' => []
        ];
        
        try {
            // Test database connection
            $wpdb->get_var("SELECT 1");
            
            // Check required tables
            $required_tables = ['happy_place_events', 'happy_place_bookings'];
            foreach ($required_tables as $table) {
                if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) !== $table) {
                    $results['tables_exist'] = false;
                    $results['issues'][] = "Missing table: {$table}";
                }
            }
            
        } catch (\Exception $e) {
            $results['connection'] = false;
            $results['issues'][] = 'Database connection failed: ' . $e->getMessage();
        }
        
        return $results;
    }

    /**
     * Validate file system
     */
    private function validate_files(): array {
        $upload_dir = wp_upload_dir();
        $plugin_dir = plugin_dir_path(__FILE__);
        
        return [
            'upload_writable' => is_writable($upload_dir['basedir']),
            'plugin_readable' => is_readable($plugin_dir),
            'temp_dir_exists' => is_dir($upload_dir['basedir'] . '/happy-place-temp'),
            'issues' => []
        ];
    }

    /**
     * Validate permissions
     */
    private function validate_permissions(): array {
        return [
            'admin_access' => current_user_can('manage_options'),
            'edit_posts' => current_user_can('edit_posts'),
            'upload_files' => current_user_can('upload_files'),
            'issues' => []
        ];
    }

    /**
     * Validate integrations
     */
    private function validate_integrations(): array {
        return [
            'airtable' => $this->test_airtable_connection(),
            'email' => $this->test_email_functionality(),
            'issues' => []
        ];
    }

    /**
     * Validate cache system
     */
    private function validate_cache(): array {
        return [
            'enabled' => wp_using_ext_object_cache(),
            'writeable' => is_writable(WP_CONTENT_DIR . '/cache'),
            'issues' => []
        ];
    }

    /**
     * Calculate overall system status
     */
    private function calculate_overall_status(array $results): string {
        $total_checks = 0;
        $passed_checks = 0;
        
        foreach ($results as $category => $data) {
            foreach ($data as $key => $value) {
                if ($key !== 'issues' && is_bool($value)) {
                    $total_checks++;
                    if ($value) $passed_checks++;
                }
            }
        }
        
        $percentage = $total_checks > 0 ? ($passed_checks / $total_checks) * 100 : 0;
        
        if ($percentage >= 90) return 'excellent';
        if ($percentage >= 75) return 'good';
        if ($percentage >= 50) return 'warning';
        return 'critical';
    }

    /**
     * Test Airtable connection
     */
    private function test_airtable_connection(): bool {
        // Placeholder - will be implemented during Airtable consolidation
        return true;
    }

    /**
     * Test email functionality
     */
    private function test_email_functionality(): bool {
        return function_exists('wp_mail');
    }

    /**
     * Cleanup cache
     */
    private function cleanup_cache(): array {
        $deleted = 0;
        
        // Clear WordPress object cache
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
            $deleted++;
        }
        
        // Clear transients
        $this->clear_happy_place_transients();
        $deleted++;
        
        return [
            'items_deleted' => $deleted,
            'space_freed' => 'Unknown'
        ];
    }

    /**
     * Cleanup logs
     */
    private function cleanup_logs(): array {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/happy-place-logs';
        
        if (!is_dir($log_dir)) {
            return ['items_deleted' => 0, 'space_freed' => 0];
        }
        
        $deleted = 0;
        $space_freed = 0;
        
        $files = glob($log_dir . '/*.log');
        foreach ($files as $file) {
            if (filemtime($file) < strtotime('-30 days')) {
                $space_freed += filesize($file);
                unlink($file);
                $deleted++;
            }
        }
        
        return [
            'items_deleted' => $deleted,
            'space_freed' => $space_freed
        ];
    }

    /**
     * Cleanup temporary files
     */
    private function cleanup_temp_files(): array {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/happy-place-temp';
        
        if (!is_dir($temp_dir)) {
            return ['items_deleted' => 0, 'space_freed' => 0];
        }
        
        $deleted = 0;
        $space_freed = 0;
        
        $files = glob($temp_dir . '/*');
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < strtotime('-1 day')) {
                $space_freed += filesize($file);
                unlink($file);
                $deleted++;
            }
        }
        
        return [
            'items_deleted' => $deleted,
            'space_freed' => $space_freed
        ];
    }

    /**
     * Cleanup orphaned data
     */
    private function cleanup_orphaned_data(): array {
        global $wpdb;
        
        $deleted = 0;
        
        // Clean up orphaned post meta
        $orphaned_meta = $wpdb->query("
            DELETE pm FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.ID IS NULL
        ");
        
        $deleted += $orphaned_meta;
        
        return [
            'items_deleted' => $deleted,
            'space_freed' => 'Unknown'
        ];
    }

    /**
     * Clear Happy Place specific transients
     */
    private function clear_happy_place_transients(): void {
        global $wpdb;
        
        $wpdb->query("
            DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_happy_place_%'
            OR option_name LIKE '_transient_timeout_happy_place_%'
        ");
    }

    /**
     * Create download URL for file
     */
    private function create_download_url(string $filename, string $data): string {
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/happy-place-exports/' . $filename;
        
        // Ensure directory exists
        wp_mkdir_p(dirname($file_path));
        
        // Write file
        file_put_contents($file_path, $data);
        
        // Return URL
        return $upload_dir['baseurl'] . '/happy-place-exports/' . $filename;
    }

    /**
     * Get users list
     */
    private function get_users_list(): array {
        $users = get_users(['fields' => ['ID', 'user_login', 'user_email', 'display_name']]);
        return array_map(function($user) {
            return [
                'id' => $user->ID,
                'login' => $user->user_login,
                'email' => $user->user_email,
                'name' => $user->display_name
            ];
        }, $users);
    }

    /**
     * Update user
     */
    private function update_user(): void {
        if (!$this->validate_required_params(['user_id' => 'int', 'user_data' => 'array'])) {
            return;
        }

        $user_id = $_POST['user_id'];
        $user_data = $_POST['user_data'];
        
        $result = wp_update_user(array_merge(['ID' => $user_id], $user_data));
        
        if (is_wp_error($result)) {
            $this->send_error('Failed to update user: ' . $result->get_error_message());
            return;
        }
        
        $this->send_success(['message' => 'User updated successfully', 'user_id' => $user_id]);
    }

    /**
     * Delete user
     */
    private function delete_user(): void {
        if (!$this->validate_required_params(['user_id' => 'int'])) {
            return;
        }

        $user_id = $_POST['user_id'];
        
        if (!wp_delete_user($user_id)) {
            $this->send_error('Failed to delete user');
            return;
        }
        
        $this->send_success(['message' => 'User deleted successfully', 'user_id' => $user_id]);
    }

    /**
     * Perform bulk user action
     */
    private function perform_bulk_user_action(string $action, int $user_id): array {
        switch ($action) {
            case 'activate':
                // Implementation for user activation
                return ['status' => 'activated', 'user_id' => $user_id];
            case 'deactivate':
                // Implementation for user deactivation
                return ['status' => 'deactivated', 'user_id' => $user_id];
            case 'delete':
                $success = wp_delete_user($user_id);
                return ['status' => $success ? 'deleted' : 'failed', 'user_id' => $user_id];
            default:
                return ['status' => 'unknown_action', 'user_id' => $user_id];
        }
    }

    /**
     * Process CSV import
     */
    private function process_csv_import(string $csv_data, string $import_type): array {
        $lines = str_getcsv($csv_data, "\n");
        $headers = str_getcsv(array_shift($lines));
        $imported = 0;
        $errors = [];
        
        foreach ($lines as $line) {
            $data = str_getcsv($line);
            $record = array_combine($headers, $data);
            
            try {
                $this->import_single_record($record, $import_type);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        
        return [
            'imported' => $imported,
            'errors' => $errors,
            'total_lines' => count($lines)
        ];
    }

    /**
     * Import single record
     */
    private function import_single_record(array $record, string $import_type): void {
        switch ($import_type) {
            case 'users':
                wp_insert_user($record);
                break;
            case 'events':
                // Implementation for event import
                break;
            case 'bookings':
                // Implementation for booking import
                break;
            default:
                throw new \InvalidArgumentException('Unsupported import type');
        }
    }

    /**
     * Validate CSV data
     */
    private function validate_csv_data(string $csv_data, string $import_type): array {
        $lines = str_getcsv($csv_data, "\n");
        $headers = str_getcsv(array_shift($lines));
        $required_fields = $this->get_required_fields_for_import($import_type);
        
        $missing_fields = array_diff($required_fields, $headers);
        $validation_errors = [];
        
        if (!empty($missing_fields)) {
            $validation_errors[] = 'Missing required fields: ' . implode(', ', $missing_fields);
        }
        
        return [
            'valid' => empty($validation_errors),
            'errors' => $validation_errors,
            'headers' => $headers,
            'required_fields' => $required_fields,
            'total_rows' => count($lines)
        ];
    }

    /**
     * Get required fields for import type
     */
    private function get_required_fields_for_import(string $import_type): array {
        switch ($import_type) {
            case 'users':
                return ['user_login', 'user_email'];
            case 'events':
                return ['title', 'date', 'time'];
            case 'bookings':
                return ['event_id', 'user_id', 'booking_date'];
            default:
                return [];
        }
    }

    /**
     * Refresh cache type
     */
    private function refresh_cache_type(string $cache_type): array {
        switch ($cache_type) {
            case 'all':
                wp_cache_flush();
                return ['status' => 'flushed', 'type' => 'all_caches'];
            case 'object':
                wp_cache_flush();
                return ['status' => 'flushed', 'type' => 'object_cache'];
            case 'transients':
                $this->clear_happy_place_transients();
                return ['status' => 'cleared', 'type' => 'transients'];
            default:
                return ['status' => 'unknown', 'type' => $cache_type];
        }
    }

    /**
     * Get plugin version
     */
    private function get_plugin_version(): string {
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $plugin_file = plugin_dir_path(__FILE__) . '../../../happy-place.php';
        $plugin_data = get_plugin_data($plugin_file);
        
        return $plugin_data['Version'] ?? 'Unknown';
    }

    /**
     * Get database version
     */
    private function get_database_version(): string {
        global $wpdb;
        return $wpdb->get_var("SELECT VERSION()") ?? 'Unknown';
    }

    /**
     * Test database connection
     */
    private function test_database_connection(): array {
        global $wpdb;
        
        try {
            $result = $wpdb->get_var("SELECT 1");
            return [
                'connected' => $result === '1',
                'version' => $this->get_database_version(),
                'charset' => $wpdb->charset,
                'collate' => $wpdb->collate
            ];
        } catch (\Exception $e) {
            return [
                'connected' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test full Airtable connection
     */
    private function test_airtable_connection_full(): array {
        // This will be implemented when Airtable classes are consolidated
        return [
            'connected' => false,
            'message' => 'Airtable integration pending consolidation'
        ];
    }

    /**
     * Test email connection
     */
    private function test_email_connection(): array {
        $test_email = get_option('admin_email');
        $subject = 'Happy Place Plugin - Email Test';
        $message = 'This is a test email from the Happy Place Plugin.';
        
        $sent = wp_mail($test_email, $subject, $message);
        
        return [
            'email_function_available' => function_exists('wp_mail'),
            'test_email_sent' => $sent,
            'test_email_address' => $test_email
        ];
    }
}
