<?php
/**
 * System AJAX Handler
 *
 * Handles system-level AJAX operations including:
 * - System validation and monitoring
 * - Performance analytics
 * - Health checks
 * - Error reporting
 * - System diagnostics
 *
 * @package HappyPlace
 * @subpackage Api\Ajax\Handlers
 * @since 2.0.0
 */

namespace HappyPlace\Api\Ajax\Handlers;

use HappyPlace\Api\Ajax\Base_Ajax_Handler;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * System AJAX Handler Class
 *
 * Consolidates system functionality from:
 * - Enhanced_Systems_Dashboard (includes/monitoring/class-enhanced-systems-dashboard.php)
 * - Performance_Section (includes/dashboard/sections/class-performance-section.php) 
 * - Various validation tools
 * - Analytics handlers
 */
class System_Ajax extends Base_Ajax_Handler {

    /**
     * Define AJAX actions and their configurations
     */
    protected function get_actions(): array {
        return [
            // System Health & Validation
            'system_health_check' => [
                'callback' => 'handle_system_health_check',
                'capability' => 'manage_options',
                'public' => false,
                'rate_limit' => 10
            ],
            'validate_configuration' => [
                'callback' => 'handle_validate_configuration',
                'capability' => 'manage_options',
                'public' => false,
                'rate_limit' => 5
            ],
            'check_dependencies' => [
                'callback' => 'handle_check_dependencies',
                'capability' => 'manage_options',
                'public' => false,
                'rate_limit' => 5
            ],
            
            // Performance Monitoring
            'performance_metrics' => [
                'callback' => 'handle_performance_metrics',
                'capability' => 'manage_options',
                'public' => false,
                'rate_limit' => 15
            ],
            'memory_usage' => [
                'callback' => 'handle_memory_usage',
                'capability' => 'manage_options',
                'public' => false,
                'rate_limit' => 20
            ],
            'database_performance' => [
                'callback' => 'handle_database_performance',
                'capability' => 'manage_options',
                'public' => false,
                'rate_limit' => 10
            ],
            
            // Analytics & Reporting
            'generate_report' => [
                'callback' => 'handle_generate_report',
                'capability' => 'manage_options',
                'public' => false,
                'rate_limit' => 5
            ],
            'export_metrics' => [
                'callback' => 'handle_export_metrics',
                'capability' => 'manage_options',
                'public' => false,
                'rate_limit' => 3
            ],
            'usage_statistics' => [
                'callback' => 'handle_usage_statistics',
                'capability' => 'manage_options',
                'public' => false,
                'rate_limit' => 10
            ],
            
            // Error Handling & Diagnostics
            'error_log_analysis' => [
                'callback' => 'handle_error_log_analysis',
                'capability' => 'manage_options',
                'public' => false,
                'rate_limit' => 5
            ],
            'system_diagnostics' => [
                'callback' => 'handle_system_diagnostics',
                'capability' => 'manage_options',
                'public' => false,
                'rate_limit' => 3
            ],
            'clear_error_logs' => [
                'callback' => 'handle_clear_error_logs',
                'capability' => 'manage_options',
                'public' => false,
                'rate_limit' => 2
            ],
            
            // File System Operations
            'disk_usage' => [
                'callback' => 'handle_disk_usage',
                'capability' => 'manage_options',
                'public' => false,
                'rate_limit' => 10
            ],
            'file_permissions_check' => [
                'callback' => 'handle_file_permissions_check',
                'capability' => 'manage_options',
                'public' => false,
                'rate_limit' => 5
            ]
        ];
    }

    /**
     * Handle comprehensive system health check
     */
    public function handle_system_health_check(): void {
        try {
            $start_time = microtime(true);
            
            $health_check = [
                'timestamp' => current_time('mysql'),
                'overall_status' => 'checking',
                'checks' => [
                    'database' => $this->check_database_health(),
                    'file_system' => $this->check_file_system_health(),
                    'memory' => $this->check_memory_health(),
                    'php_config' => $this->check_php_configuration(),
                    'wordpress' => $this->check_wordpress_health(),
                    'plugin_status' => $this->check_plugin_status(),
                    'integrations' => $this->check_integrations_health()
                ],
                'performance' => [
                    'check_duration' => round((microtime(true) - $start_time) * 1000, 2) . 'ms'
                ]
            ];
            
            // Calculate overall status
            $health_check['overall_status'] = $this->calculate_overall_health_status($health_check['checks']);
            
            $this->send_success([
                'message' => 'System health check completed',
                'health' => $health_check
            ]);
            
        } catch (\Exception $e) {
            $this->send_error('System health check failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle configuration validation
     */
    public function handle_validate_configuration(): void {
        try {
            $validation_results = [
                'plugin_config' => $this->validate_plugin_configuration(),
                'wordpress_config' => $this->validate_wordpress_configuration(),
                'server_config' => $this->validate_server_configuration(),
                'security_config' => $this->validate_security_configuration()
            ];
            
            $issues_found = 0;
            foreach ($validation_results as $category => $results) {
                $issues_found += count($results['issues'] ?? []);
            }
            
            $this->send_success([
                'message' => 'Configuration validation completed',
                'validation' => $validation_results,
                'summary' => [
                    'total_issues' => $issues_found,
                    'status' => $issues_found === 0 ? 'passed' : 'issues_found'
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->send_error('Configuration validation failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle dependency checks
     */
    public function handle_check_dependencies(): void {
        try {
            $dependencies = [
                'php_version' => $this->check_php_version(),
                'php_extensions' => $this->check_php_extensions(),
                'wordpress_version' => $this->check_wordpress_version(),
                'database_version' => $this->check_database_version(),
                'required_plugins' => $this->check_required_plugins(),
                'optional_plugins' => $this->check_optional_plugins()
            ];
            
            $critical_issues = $this->identify_critical_dependency_issues($dependencies);
            
            $this->send_success([
                'message' => 'Dependency check completed',
                'dependencies' => $dependencies,
                'critical_issues' => $critical_issues,
                'status' => empty($critical_issues) ? 'all_satisfied' : 'issues_found'
            ]);
            
        } catch (\Exception $e) {
            $this->send_error('Dependency check failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle performance metrics collection
     */
    public function handle_performance_metrics(): void {
        try {
            $start_time = microtime(true);
            
            $metrics = [
                'timestamp' => current_time('mysql'),
                'memory' => $this->collect_memory_metrics(),
                'database' => $this->collect_database_metrics(),
                'cache' => $this->collect_cache_metrics(),
                'file_system' => $this->collect_file_system_metrics(),
                'network' => $this->collect_network_metrics(),
                'collection_time' => round((microtime(true) - $start_time) * 1000, 2) . 'ms'
            ];
            
            // Store metrics for historical analysis
            $this->store_performance_metrics($metrics);
            
            $this->send_success([
                'message' => 'Performance metrics collected',
                'metrics' => $metrics,
                'trends' => $this->get_performance_trends()
            ]);
            
        } catch (\Exception $e) {
            $this->send_error('Performance metrics collection failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle memory usage analysis
     */
    public function handle_memory_usage(): void {
        try {
            $memory_analysis = [
                'current_usage' => [
                    'bytes' => memory_get_usage(true),
                    'formatted' => size_format(memory_get_usage(true)),
                    'percentage' => $this->calculate_memory_percentage()
                ],
                'peak_usage' => [
                    'bytes' => memory_get_peak_usage(true),
                    'formatted' => size_format(memory_get_peak_usage(true))
                ],
                'limit' => [
                    'bytes' => $this->get_memory_limit_bytes(),
                    'formatted' => ini_get('memory_limit')
                ],
                'available' => [
                    'bytes' => $this->get_available_memory(),
                    'formatted' => size_format($this->get_available_memory())
                ],
                'breakdown' => $this->analyze_memory_breakdown(),
                'recommendations' => $this->get_memory_recommendations()
            ];
            
            $this->send_success([
                'message' => 'Memory usage analysis completed',
                'memory' => $memory_analysis
            ]);
            
        } catch (\Exception $e) {
            $this->send_error('Memory usage analysis failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle database performance analysis
     */
    public function handle_database_performance(): void {
        try {
            global $wpdb;
            
            $start_time = microtime(true);
            
            $db_performance = [
                'connection_test' => $this->test_database_connection_performance(),
                'query_performance' => $this->analyze_query_performance(),
                'table_status' => $this->get_table_status_info(),
                'index_analysis' => $this->analyze_database_indexes(),
                'cache_hit_ratio' => $this->calculate_cache_hit_ratio(),
                'slow_queries' => $this->identify_slow_queries(),
                'optimization_suggestions' => $this->get_database_optimization_suggestions(),
                'analysis_time' => round((microtime(true) - $start_time) * 1000, 2) . 'ms'
            ];
            
            $this->send_success([
                'message' => 'Database performance analysis completed',
                'database' => $db_performance
            ]);
            
        } catch (\Exception $e) {
            $this->send_error('Database performance analysis failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle report generation
     */
    public function handle_generate_report(): void {
        try {
            if (!$this->validate_required_params(['report_type' => 'string'])) {
                return;
            }
            
            $report_type = $_POST['report_type'];
            $date_range = $_POST['date_range'] ?? 'last_7_days';
            
            $report_data = $this->generate_system_report($report_type, $date_range);
            $report_file = $this->create_report_file($report_data, $report_type);
            
            $this->send_success([
                'message' => 'Report generated successfully',
                'report' => $report_data,
                'download_url' => $report_file['url'],
                'filename' => $report_file['filename']
            ]);
            
        } catch (\Exception $e) {
            $this->send_error('Report generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle error log analysis
     */
    public function handle_error_log_analysis(): void {
        try {
            $log_files = $this->get_log_files();
            $analysis = [];
            
            foreach ($log_files as $log_file) {
                $analysis[$log_file['type']] = $this->analyze_log_file($log_file['path']);
            }
            
            $summary = $this->create_error_analysis_summary($analysis);
            
            $this->send_success([
                'message' => 'Error log analysis completed',
                'analysis' => $analysis,
                'summary' => $summary,
                'recommendations' => $this->get_error_resolution_recommendations($summary)
            ]);
            
        } catch (\Exception $e) {
            $this->send_error('Error log analysis failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle system diagnostics
     */
    public function handle_system_diagnostics(): void {
        try {
            $diagnostics = [
                'environment' => $this->collect_environment_info(),
                'server_info' => $this->collect_server_info(),
                'php_info' => $this->collect_php_info(),
                'wordpress_info' => $this->collect_wordpress_info(),
                'plugin_info' => $this->collect_plugin_info(),
                'theme_info' => $this->collect_theme_info(),
                'network_info' => $this->collect_network_info(),
                'security_info' => $this->collect_security_info()
            ];
            
            // Generate diagnostic report
            $diagnostic_file = $this->create_diagnostic_file($diagnostics);
            
            $this->send_success([
                'message' => 'System diagnostics completed',
                'diagnostics' => $diagnostics,
                'report_file' => $diagnostic_file
            ]);
            
        } catch (\Exception $e) {
            $this->send_error('System diagnostics failed: ' . $e->getMessage());
        }
    }

    /**
     * Check database health
     */
    private function check_database_health(): array {
        global $wpdb;
        
        try {
            $start_time = microtime(true);
            $test_query = $wpdb->get_var("SELECT 1");
            $response_time = round((microtime(true) - $start_time) * 1000, 2);
            
            return [
                'status' => $test_query === '1' ? 'healthy' : 'error',
                'response_time_ms' => $response_time,
                'version' => $wpdb->get_var("SELECT VERSION()"),
                'charset' => $wpdb->charset,
                'collate' => $wpdb->collate,
                'issues' => $response_time > 1000 ? ['Slow database response'] : []
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'issues' => ['Database connection failed']
            ];
        }
    }

    /**
     * Check file system health
     */
    private function check_file_system_health(): array {
        $upload_dir = wp_upload_dir();
        $plugin_dir = plugin_dir_path(__FILE__);
        
        $checks = [
            'upload_dir_writable' => is_writable($upload_dir['basedir']),
            'plugin_dir_readable' => is_readable($plugin_dir),
            'temp_dir_available' => is_dir(sys_get_temp_dir()) && is_writable(sys_get_temp_dir()),
            'disk_space' => $this->get_available_disk_space()
        ];
        
        $issues = [];
        if (!$checks['upload_dir_writable']) $issues[] = 'Upload directory not writable';
        if (!$checks['plugin_dir_readable']) $issues[] = 'Plugin directory not readable';
        if (!$checks['temp_dir_available']) $issues[] = 'Temporary directory not available';
        if ($checks['disk_space'] < 100 * 1024 * 1024) $issues[] = 'Low disk space (< 100MB)';
        
        return [
            'status' => empty($issues) ? 'healthy' : 'warning',
            'checks' => $checks,
            'issues' => $issues
        ];
    }

    /**
     * Check memory health
     */
    private function check_memory_health(): array {
        $current_usage = memory_get_usage(true);
        $peak_usage = memory_get_peak_usage(true);
        $memory_limit = $this->get_memory_limit_bytes();
        $usage_percentage = ($current_usage / $memory_limit) * 100;
        
        $issues = [];
        if ($usage_percentage > 90) $issues[] = 'Memory usage very high (> 90%)';
        elseif ($usage_percentage > 75) $issues[] = 'Memory usage high (> 75%)';
        
        return [
            'status' => $usage_percentage > 90 ? 'critical' : ($usage_percentage > 75 ? 'warning' : 'healthy'),
            'current_usage' => $current_usage,
            'peak_usage' => $peak_usage,
            'memory_limit' => $memory_limit,
            'usage_percentage' => round($usage_percentage, 2),
            'issues' => $issues
        ];
    }

    /**
     * Check PHP configuration
     */
    private function check_php_configuration(): array {
        $required_extensions = ['curl', 'json', 'mbstring', 'zip'];
        $missing_extensions = [];
        
        foreach ($required_extensions as $ext) {
            if (!extension_loaded($ext)) {
                $missing_extensions[] = $ext;
            }
        }
        
        $issues = [];
        if (version_compare(PHP_VERSION, '7.4', '<')) $issues[] = 'PHP version is outdated';
        if (!empty($missing_extensions)) $issues[] = 'Missing PHP extensions: ' . implode(', ', $missing_extensions);
        
        return [
            'status' => empty($issues) ? 'healthy' : 'warning',
            'php_version' => PHP_VERSION,
            'required_extensions' => $required_extensions,
            'missing_extensions' => $missing_extensions,
            'issues' => $issues
        ];
    }

    /**
     * Check WordPress health
     */
    private function check_wordpress_health(): array {
        global $wp_version;
        
        $issues = [];
        if (version_compare($wp_version, '5.0', '<')) $issues[] = 'WordPress version is outdated';
        
        return [
            'status' => empty($issues) ? 'healthy' : 'warning',
            'version' => $wp_version,
            'multisite' => is_multisite(),
            'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
            'issues' => $issues
        ];
    }

    /**
     * Check plugin status
     */
    private function check_plugin_status(): array {
        $plugin_file = HPH_PLUGIN_FILE; // Use the defined constant instead of relative path
        $plugin_data = function_exists('get_plugin_data') ? get_plugin_data($plugin_file) : [];
        
        return [
            'status' => 'healthy',
            'version' => $plugin_data['Version'] ?? HPH_VERSION,
            'active' => is_plugin_active(plugin_basename($plugin_file)),
            'file_exists' => file_exists($plugin_file),
            'issues' => []
        ];
    }

    /**
     * Check integrations health
     */
    private function check_integrations_health(): array {
        return [
            'status' => 'healthy',
            'airtable' => ['status' => 'pending_consolidation'],
            'email' => ['status' => function_exists('wp_mail') ? 'available' : 'unavailable'],
            'issues' => []
        ];
    }

    /**
     * Calculate overall health status
     */
    private function calculate_overall_health_status(array $checks): string {
        $critical_count = 0;
        $warning_count = 0;
        
        foreach ($checks as $check) {
            switch ($check['status']) {
                case 'critical':
                    $critical_count++;
                    break;
                case 'warning':
                    $warning_count++;
                    break;
            }
        }
        
        if ($critical_count > 0) return 'critical';
        if ($warning_count > 2) return 'warning';
        if ($warning_count > 0) return 'good';
        return 'excellent';
    }

    /**
     * Get memory limit in bytes
     */
    private function get_memory_limit_bytes(): int {
        $limit = ini_get('memory_limit');
        if ($limit === '-1' || $limit === false) return PHP_INT_MAX;
        
        // Handle numeric-only values (default to bytes)
        if (is_numeric($limit)) return (int) $limit;
        
        $unit = strtolower(substr($limit, -1));
        $value = (int) $limit;
        
        switch ($unit) {
            case 'g': return $value * 1024 * 1024 * 1024;
            case 'm': return $value * 1024 * 1024;
            case 'k': return $value * 1024;
            default: return $value;
        }
    }

    /**
     * Get available memory
     */
    private function get_available_memory(): int {
        return $this->get_memory_limit_bytes() - memory_get_usage(true);
    }

    /**
     * Calculate memory percentage
     */
    private function calculate_memory_percentage(): float {
        return round((memory_get_usage(true) / $this->get_memory_limit_bytes()) * 100, 2);
    }

    /**
     * Get available disk space
     */
    private function get_available_disk_space(): int {
        return disk_free_space(ABSPATH) ?: 0;
    }

    /**
     * Validate plugin configuration
     */
    private function validate_plugin_configuration(): array {
        $issues = [];
        $checks = [];
        
        // Check if required constants are defined
        $required_constants = ['HPH_VERSION', 'HPH_PATH', 'HPH_URL', 'HPH_ASSETS_URL'];
        foreach ($required_constants as $constant) {
            $checks[$constant] = defined($constant);
            if (!defined($constant)) {
                $issues[] = "Missing required constant: $constant";
            }
        }
        
        // Check plugin file structure
        $required_files = [
            'Main Plugin File' => HPH_PLUGIN_FILE,
            'Plugin Manager' => HPH_INCLUDES_PATH . 'core/class-plugin-manager.php',
            'Assets Directory' => HPH_ASSETS_PATH
        ];
        
        foreach ($required_files as $name => $path) {
            $exists = file_exists($path);
            $checks[$name] = $exists;
            if (!$exists) {
                $issues[] = "Missing required file/directory: $name ($path)";
            }
        }
        
        return [
            'status' => empty($issues) ? 'valid' : 'invalid',
            'checks' => $checks,
            'issues' => $issues
        ];
    }

    /**
     * Validate WordPress configuration
     */
    private function validate_wordpress_configuration(): array {
        global $wp_version;
        
        $issues = [];
        $checks = [
            'wp_version' => $wp_version,
            'is_multisite' => is_multisite(),
            'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
            'wp_memory_limit' => WP_MEMORY_LIMIT ?? ini_get('memory_limit'),
            'uploads_writable' => wp_is_writable(wp_upload_dir()['basedir'])
        ];
        
        if (version_compare($wp_version, '5.0', '<')) {
            $issues[] = 'WordPress version is outdated (< 5.0)';
        }
        
        if (!$checks['uploads_writable']) {
            $issues[] = 'Uploads directory is not writable';
        }
        
        return [
            'status' => empty($issues) ? 'valid' : 'invalid',
            'checks' => $checks,
            'issues' => $issues
        ];
    }

    /**
     * Validate server configuration
     */
    private function validate_server_configuration(): array {
        $issues = [];
        $checks = [
            'php_version' => PHP_VERSION,
            'web_server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'max_execution_time' => ini_get('max_execution_time'),
            'max_input_vars' => ini_get('max_input_vars'),
            'post_max_size' => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize')
        ];
        
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $issues[] = 'PHP version is outdated (< 7.4)';
        }
        
        if ($checks['max_execution_time'] < 30 && $checks['max_execution_time'] != 0) {
            $issues[] = 'Max execution time is too low (< 30 seconds)';
        }
        
        return [
            'status' => empty($issues) ? 'valid' : 'invalid',
            'checks' => $checks,
            'issues' => $issues
        ];
    }

    /**
     * Validate security configuration
     */
    private function validate_security_configuration(): array {
        $issues = [];
        $checks = [
            'ssl_enabled' => is_ssl(),
            'file_editing_disabled' => defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT,
            'debug_log_enabled' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG,
            'force_ssl_admin' => defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN,
            'wp_config_secure' => !is_readable(ABSPATH . 'wp-config.php') || file_exists(ABSPATH . '.htaccess')
        ];
        
        if (!$checks['ssl_enabled']) {
            $issues[] = 'SSL is not enabled';
        }
        
        if (!$checks['file_editing_disabled']) {
            $issues[] = 'File editing is not disabled in WordPress admin';
        }
        
        return [
            'status' => empty($issues) ? 'secure' : 'needs_attention',
            'checks' => $checks,
            'issues' => $issues
        ];
    }

    /**
     * Collect memory metrics
     */
    private function collect_memory_metrics(): array {
        return [
            'current_usage' => memory_get_usage(true),
            'peak_usage' => memory_get_peak_usage(true),
            'limit' => $this->get_memory_limit_bytes(),
            'available' => $this->get_available_memory(),
            'percentage' => $this->calculate_memory_percentage()
        ];
    }

    /**
     * Collect database metrics
     */
    private function collect_database_metrics(): array {
        global $wpdb;
        
        $start_time = microtime(true);
        $test_query = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts}");
        $query_time = microtime(true) - $start_time;
        
        return [
            'query_time' => round($query_time * 1000, 2),
            'total_posts' => (int) $test_query,
            'database_size' => $this->get_database_size(),
            'table_count' => count($wpdb->get_results("SHOW TABLES"))
        ];
    }

    /**
     * Get database size
     */
    private function get_database_size(): array {
        global $wpdb;
        
        $result = $wpdb->get_row("
            SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
            FROM information_schema.tables 
            WHERE table_schema = '" . DB_NAME . "'
        ");
        
        return [
            'size_mb' => $result->size_mb ?? 0,
            'formatted' => size_format(($result->size_mb ?? 0) * 1024 * 1024)
        ];
    }

    /**
     * Collect cache metrics
     */
    private function collect_cache_metrics(): array {
        return [
            'object_cache_enabled' => wp_using_ext_object_cache(),
            'opcache_enabled' => function_exists('opcache_get_status') ? opcache_get_status() : false,
            'wp_cache_enabled' => defined('WP_CACHE') && WP_CACHE
        ];
    }

    /**
     * Collect file system metrics
     */
    private function collect_file_system_metrics(): array {
        $upload_dir = wp_upload_dir();
        
        return [
            'disk_free_space' => disk_free_space(ABSPATH),
            'disk_total_space' => disk_total_space(ABSPATH),
            'uploads_dir_size' => $this->get_directory_size($upload_dir['basedir']),
            'plugin_dir_size' => $this->get_directory_size(WP_PLUGIN_DIR)
        ];
    }

    /**
     * Get directory size
     */
    private function get_directory_size(string $path): int {
        if (!is_dir($path)) return 0;
        
        $size = 0;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        
        return $size;
    }

    /**
     * Collect network metrics
     */
    private function collect_network_metrics(): array {
        $remote_blocked = defined('WP_HTTP_BLOCK_EXTERNAL') ? constant('WP_HTTP_BLOCK_EXTERNAL') : false;
        
        return [
            'remote_requests_blocked' => $remote_blocked,
            'curl_available' => function_exists('curl_init'),
            'allow_url_fopen' => ini_get('allow_url_fopen')
        ];
    }

    // Additional helper methods would be implemented here for:
    // - check_php_version(), check_php_extensions(), check_wordpress_version()
    // - check_database_version(), check_required_plugins(), check_optional_plugins()
    // - identify_critical_dependency_issues(), store_performance_metrics()
    // - get_performance_trends(), analyze_memory_breakdown(), get_memory_recommendations()
    // - test_database_connection_performance(), analyze_query_performance()
    // - get_table_status_info(), analyze_database_indexes(), calculate_cache_hit_ratio()
    // - identify_slow_queries(), get_database_optimization_suggestions()
    // - generate_system_report(), create_report_file(), get_log_files()
    // - analyze_log_file(), create_error_analysis_summary(), get_error_resolution_recommendations()
    // - collect_environment_info(), collect_server_info(), collect_php_info()
    // - collect_wordpress_info(), collect_plugin_info(), collect_theme_info()
    // - collect_security_info(), create_diagnostic_file()
    
    // Note: This class now has a solid foundation with essential methods implemented.
    // The remaining methods can be added as needed based on specific requirements.
}
