<?php

/**
 * AJAX Loader and Manager
 * 
 * Initializes and manages all modular AJAX handlers, replacing
 * the monolithic Dashboard_Ajax_Handler with focused controllers.
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
 * AJAX Manager Class
 * 
 * Coordinates all AJAX handlers and provides centralized
 * initialization and configuration management.
 */
class HPH_Ajax_Manager
{
    /**
     * @var HPH_Ajax_Manager|null Singleton instance
     */
    private static ?self $instance = null;

    /**
     * @var array Active AJAX handlers
     */
    private array $handlers = [];

    /**
     * @var array Handler classes and their dependencies
     */
    private array $handler_classes = [
        'base' => HPH_Base_Ajax::class,
        'section' => HPH_Section_Ajax::class,
        'form' => HPH_Form_Ajax::class,
        'listing' => HPH_Listing_Ajax::class,
        'analytics' => HPH_Analytics_Ajax::class
    ];

    /**
     * Get singleton instance
     */
    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Initialize AJAX management
     */
    private function __construct()
    {
        $this->load_handler_classes();
        $this->initialize_handlers();
        $this->setup_global_hooks();
        $this->register_database_tables();
    }

    /**
     * Load all handler class files
     */
    private function load_handler_classes(): void
    {
        $ajax_dir = dirname(__FILE__);
        
        $handler_files = [
            'class-base-ajax.php',
            'class-section-ajax.php',
            'class-form-ajax.php',
            'class-listing-ajax.php',
            'class-analytics-ajax.php'
        ];

        foreach ($handler_files as $file) {
            $file_path = $ajax_dir . '/' . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                error_log("HPH AJAX: Handler file not found: {$file}");
            }
        }
    }

    /**
     * Initialize all AJAX handlers
     */
    private function initialize_handlers(): void
    {
        // Skip base handler as it's abstract
        $handler_classes = array_slice($this->handler_classes, 1);
        
        foreach ($handler_classes as $key => $class) {
            if (class_exists($class)) {
                try {
                    $this->handlers[$key] = new $class();
                    do_action("hph_ajax_handler_loaded_{$key}", $this->handlers[$key]);
                } catch (Exception $e) {
                    error_log("HPH AJAX: Failed to initialize {$class}: " . $e->getMessage());
                }
            } else {
                error_log("HPH AJAX: Handler class not found: {$class}");
            }
        }

        do_action('hph_ajax_handlers_initialized', $this->handlers);
    }

    /**
     * Setup global AJAX hooks and middleware
     */
    private function setup_global_hooks(): void
    {
        // Global AJAX middleware
        add_action('wp_ajax_*', [$this, 'ajax_middleware'], 1);
        add_action('wp_ajax_nopriv_*', [$this, 'ajax_middleware'], 1);

        // Error handling
        add_action('wp_ajax_hph_*', [$this, 'ajax_error_handler'], 999);

        // CORS headers for API requests
        add_action('wp_ajax_hph_*', [$this, 'setup_cors_headers'], 0);
        add_action('wp_ajax_nopriv_hph_*', [$this, 'setup_cors_headers'], 0);

        // Enqueue AJAX configuration
        add_action('wp_enqueue_scripts', [$this, 'enqueue_ajax_config']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_ajax_config']);
    }

    /**
     * Register custom database tables for AJAX handlers
     */
    private function register_database_tables(): void
    {
        add_action('init', [$this, 'create_ajax_tables']);
    }

    /**
     * Create database tables for analytics and form drafts
     */
    public function create_ajax_tables(): void
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Analytics events table
        $analytics_table = $wpdb->prefix . 'hph_analytics_events';
        $analytics_sql = "CREATE TABLE $analytics_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_type varchar(100) NOT NULL,
            event_data text,
            user_id bigint(20) UNSIGNED NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_event_type (event_type),
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at)
        ) $charset_collate;";

        // Page views table
        $views_table = $wpdb->prefix . 'hph_page_views';
        $views_sql = "CREATE TABLE $views_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            page_id bigint(20) UNSIGNED NOT NULL,
            page_type varchar(50) NOT NULL,
            referrer text,
            user_id bigint(20) UNSIGNED NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            viewed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_page_id (page_id),
            INDEX idx_page_type (page_type),
            INDEX idx_user_id (user_id),
            INDEX idx_viewed_at (viewed_at)
        ) $charset_collate;";

        // Form drafts table
        $drafts_table = $wpdb->prefix . 'hph_form_drafts';
        $drafts_sql = "CREATE TABLE $drafts_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            draft_id varchar(100) NOT NULL UNIQUE,
            user_id bigint(20) UNSIGNED NOT NULL,
            form_type varchar(50) NOT NULL,
            form_data longtext NOT NULL,
            is_auto_save tinyint(1) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_draft_id (draft_id),
            INDEX idx_user_id (user_id),
            INDEX idx_form_type (form_type),
            INDEX idx_created_date (created_date)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        dbDelta($analytics_sql);
        dbDelta($views_sql);
        dbDelta($drafts_sql);

        // Store database version
        update_option('hph_ajax_db_version', '1.0.0');
    }

    /**
     * AJAX middleware for global processing
     */
    public function ajax_middleware(): void
    {
        // Only process HPH AJAX requests
        $action = $_REQUEST['action'] ?? '';
        if (strpos($action, 'hph_') !== 0) {
            return;
        }

        // Add global headers
        $this->add_security_headers();

        // Log AJAX requests in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("HPH AJAX Request: {$action} by user " . get_current_user_id());
        }

        // Rate limiting for non-logged-in users
        if (!is_user_logged_in()) {
            $this->apply_guest_rate_limiting($action);
        }
    }

    /**
     * Global AJAX error handler
     */
    public function ajax_error_handler(): void
    {
        // Only handle if no other output has been sent
        if (!headers_sent() && ob_get_length() === false) {
            $action = $_REQUEST['action'] ?? '';
            
            // Log unhandled AJAX requests
            error_log("HPH AJAX: Unhandled action: {$action}");
            
            wp_send_json_error([
                'message' => 'Action not implemented or handler not found',
                'action' => $action
            ], 501);
        }
    }

    /**
     * Setup CORS headers for API requests
     */
    public function setup_cors_headers(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowed_origins = apply_filters('hph_ajax_allowed_origins', [
            home_url(),
            admin_url()
        ]);

        if (in_array($origin, $allowed_origins)) {
            header("Access-Control-Allow-Origin: {$origin}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            status_header(200);
            exit;
        }
    }

    /**
     * Enqueue AJAX configuration for JavaScript
     */
    public function enqueue_ajax_config(): void
    {
        $config = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_dashboard_nonce'),
            'user_id' => get_current_user_id(),
            'is_admin' => current_user_can('manage_options'),
            'endpoints' => $this->get_ajax_endpoints(),
            'rate_limits' => $this->get_rate_limits(),
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        ];

        wp_localize_script('hph-dashboard-core', 'hphAjax', $config);
    }

    /**
     * Add security headers
     */
    private function add_security_headers(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    /**
     * Apply rate limiting for guest users
     */
    private function apply_guest_rate_limiting(string $action): void
    {
        $ip = $this->get_client_ip();
        $key = "hph_guest_rate_limit_{$ip}";
        
        $current_count = get_transient($key) ?: 0;
        $limit = apply_filters('hph_guest_rate_limit', 30); // 30 requests per hour
        
        if ($current_count >= $limit) {
            wp_send_json_error([
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => 3600
            ], 429);
        }

        set_transient($key, $current_count + 1, 3600); // 1 hour
    }

    /**
     * Get client IP address
     */
    private function get_client_ip(): string
    {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get available AJAX endpoints
     */
    private function get_ajax_endpoints(): array
    {
        return apply_filters('hph_ajax_endpoints', [
            'section' => [
                'load_section' => 'hph_load_dashboard_section',
                'get_data' => 'hph_get_section_data',
                'refresh' => 'hph_refresh_section'
            ],
            'listing' => [
                'get_listings' => 'hph_get_listings',
                'get_data' => 'hph_get_listing_data',
                'duplicate' => 'hph_duplicate_listing',
                'toggle_status' => 'hph_toggle_listing_status'
            ],
            'form' => [
                'save_listing' => 'hph_save_listing_form',
                'save_lead' => 'hph_save_lead_form',
                'auto_save' => 'hph_auto_save_form',
                'validate_field' => 'hph_validate_field'
            ],
            'analytics' => [
                'dashboard_stats' => 'hph_get_dashboard_stats',
                'performance_data' => 'hph_get_performance_data',
                'chart_data' => 'hph_get_chart_data'
            ]
        ]);
    }

    /**
     * Get rate limit configuration
     */
    private function get_rate_limits(): array
    {
        return apply_filters('hph_ajax_rate_limits', [
            'default' => ['limit' => 60, 'window' => 60], // 60 requests per minute
            'search' => ['limit' => 30, 'window' => 60],   // 30 searches per minute
            'save' => ['limit' => 10, 'window' => 60],      // 10 saves per minute
            'upload' => ['limit' => 5, 'window' => 300]     // 5 uploads per 5 minutes
        ]);
    }

    /**
     * Get specific handler instance
     */
    public function get_handler(string $handler_key): ?object
    {
        return $this->handlers[$handler_key] ?? null;
    }

    /**
     * Get all active handlers
     */
    public function get_handlers(): array
    {
        return $this->handlers;
    }

    /**
     * Check if handler is loaded
     */
    public function is_handler_loaded(string $handler_key): bool
    {
        return isset($this->handlers[$handler_key]);
    }

    /**
     * Get system status for debugging
     */
    public function get_system_status(): array
    {
        return [
            'handlers_loaded' => array_keys($this->handlers),
            'handlers_count' => count($this->handlers),
            'database_version' => get_option('hph_ajax_db_version', 'not_set'),
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'memory_usage' => memory_get_usage(true),
            'memory_limit' => ini_get('memory_limit')
        ];
    }
}

// Initialize the AJAX manager
add_action('plugins_loaded', function() {
    HPH_Ajax_Manager::instance();
}, 10);
