<?php
/**
 * AJAX Coordinator - Main System Manager
 * 
 * File: includes/api/ajax/class-ajax-coordinator.php
 */

namespace HappyPlace\Api\Ajax;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Ajax_Coordinator {
    
    private static ?self $instance = null;
    private array $handlers = [];
    private array $handler_config = [
        'dashboard' => [
            'class' => 'Dashboard_Ajax',
            'file' => 'class-dashboard-ajax.php',
            'priority' => 10
        ],
        'admin' => [
            'class' => 'Admin_Ajax',
            'file' => 'class-admin-ajax.php',
            'priority' => 15
        ],
        'flyer' => [
            'class' => 'Flyer_Ajax',
            'file' => 'class-flyer-ajax.php',
            'priority' => 20
        ],
        'integration' => [
            'class' => 'Integration_Ajax',
            'file' => 'class-integration-ajax.php',
            'priority' => 30
        ],
        'listing' => [
            'class' => 'Listing_Ajax',
            'file' => 'class-listing-ajax.php',
            'priority' => 40
        ],
        'form' => [
            'class' => 'Form_Ajax',
            'file' => 'class-form-ajax.php',
            'priority' => 50
        ],
        'system' => [
            'class' => 'System_Ajax',
            'file' => 'class-system-ajax.php',
            'priority' => 60
        ]
    ];
    
    private bool $initialized = false;
    
    public static function init(): self {
        return self::$instance ??= new self();
    }
    
    private function __construct() {
        add_action('init', [$this, 'initialize_system'], 5);
        add_action('wp_loaded', [$this, 'setup_handlers'], 10);
    }
    
    public function initialize_system(): void {
        if ($this->initialized) {
            return;
        }
        
        try {
            $this->load_base_classes();
            $this->setup_global_hooks();
            $this->initialized = true;
            error_log('HPH AJAX: System initialized successfully');
        } catch (\Exception $e) {
            error_log('HPH AJAX: System initialization failed: ' . $e->getMessage());
        }
    }
    
    public function setup_handlers(): void {
        if (!$this->initialized) {
            error_log('HPH AJAX: Cannot setup handlers - system not initialized');
            return;
        }
        
        uasort($this->handler_config, function($a, $b) {
            return ($a['priority'] ?? 50) <=> ($b['priority'] ?? 50);
        });
        
        foreach ($this->handler_config as $name => $config) {
            $this->load_handler($name, $config);
        }
        
        $this->setup_legacy_bridge();
        do_action('hph_ajax_handlers_loaded', $this->handlers);
    }
    
    private function load_base_classes(): void {
        $base_files = [
            'class-base-ajax-handler.php',
            'legacy/class-ajax-bridge.php'
        ];
        
        foreach ($base_files as $file) {
            $file_path = __DIR__ . '/' . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                throw new \Exception("Required base file not found: {$file}");
            }
        }
    }
    
    private function load_handler(string $name, array $config): void {
        $handler_file = __DIR__ . '/handlers/' . $config['file'];
        
        if (!file_exists($handler_file)) {
            error_log("HPH AJAX: Handler file not found: {$config['file']}");
            return;
        }
        
        require_once $handler_file;
        $class_name = __NAMESPACE__ . '\\Handlers\\' . $config['class'];
        
        if (!class_exists($class_name)) {
            error_log("HPH AJAX: Handler class not found: {$class_name}");
            return;
        }
        
        try {
            $this->handlers[$name] = new $class_name();
            error_log("HPH AJAX: Loaded handler '{$name}' successfully");
            do_action("hph_ajax_handler_loaded_{$name}", $this->handlers[$name]);
        } catch (\Exception $e) {
            error_log("HPH AJAX: Failed to initialize handler '{$name}': " . $e->getMessage());
        }
    }
    
    private function setup_legacy_bridge(): void {
        if (class_exists(__NAMESPACE__ . '\\Legacy\\Ajax_Bridge')) {
            $legacy_mappings = $this->get_legacy_endpoint_mappings();
            new Legacy\Ajax_Bridge($this->handlers, $legacy_mappings);
            error_log('HPH AJAX: Legacy bridge initialized');
        }
    }
    
    private function get_legacy_endpoint_mappings(): array {
        return [
            'generate_flyer' => [
                'handler' => 'flyer',
                'method' => 'handle_generate_flyer'
            ],
            'get_listing_data_for_flyer' => [
                'handler' => 'flyer',
                'method' => 'handle_get_listing_data'
            ],
            'hph_two_way_airtable_sync' => [
                'handler' => 'integration',
                'method' => 'handle_airtable_sync'
            ],
            'hph_test_airtable_connection' => [
                'handler' => 'integration',
                'method' => 'handle_test_connection'
            ],
            'happy_place_dashboard_action' => [
                'handler' => 'dashboard',
                'method' => 'handle_dashboard_action'
            ]
        ];
    }
    
    private function setup_global_hooks(): void {
        add_action('wp_ajax_hph_*', [$this, 'ajax_security_middleware'], 1);
        add_action('wp_ajax_nopriv_hph_*', [$this, 'ajax_security_middleware'], 1);
    }
    
    public function ajax_security_middleware(): void {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
    }
    
    public function get_handler(string $name) {
        return $this->handlers[$name] ?? null;
    }
    
    public function get_handlers(): array {
        return $this->handlers;
    }
    
    public function is_initialized(): bool {
        return $this->initialized;
    }
}
