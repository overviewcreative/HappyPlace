<?php
/**
 * AJAX Nonce Manager - Unified nonce handling for all AJAX requests
 * 
 * File: includes/api/ajax/class-nonce-manager.php
 */

namespace HappyPlace\Api\Ajax;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Nonce_Manager {
    
    private static ?self $instance = null;
    
    private array $nonce_actions = [
        'hph_ajax_nonce' => 'General AJAX requests',
        'hph_dashboard_nonce' => 'Dashboard operations', 
        'hph_theme_nonce' => 'Theme AJAX requests',
        'hph_form_submit' => 'Form submissions',
        'hph_config_nonce' => 'Configuration changes',
        'marketing_suite_nonce' => 'Marketing suite operations',
        'hph_location_intelligence' => 'Location API operations',
        'hph_city_ajax' => 'City data operations'
    ];
    
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_nonces']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_nonces']);
    }
    
    /**
     * Enqueue all nonces for JavaScript access
     */
    public function enqueue_nonces(): void {
        $nonces = [];
        foreach (array_keys($this->nonce_actions) as $action) {
            $nonces[$action] = wp_create_nonce($action);
        }
        
        wp_localize_script('jquery', 'hphNonces', $nonces);
    }
    
    /**
     * Verify nonce with fallback support for multiple nonce types
     */
    public static function verify_nonce(string $nonce, string $primary_action, array $fallback_actions = []): bool {
        // Try primary action first
        if (wp_verify_nonce($nonce, $primary_action)) {
            return true;
        }
        
        // Try fallback actions
        foreach ($fallback_actions as $action) {
            if (wp_verify_nonce($nonce, $action)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Enhanced nonce verification for AJAX handlers
     */
    public static function verify_ajax_nonce(array $allowed_actions = []): bool {
        $nonce = $_POST['nonce'] ?? $_REQUEST['nonce'] ?? '';
        
        if (empty($nonce)) {
            return false;
        }
        
        // If no specific actions provided, try common ones
        if (empty($allowed_actions)) {
            $allowed_actions = [
                'hph_ajax_nonce',
                'hph_dashboard_nonce',
                'marketing_suite_nonce',
                'hph_theme_nonce'
            ];
        }
        
        foreach ($allowed_actions as $action) {
            if (wp_verify_nonce($nonce, $action)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Create nonce with consistent naming
     */
    public static function create_nonce(string $action): string {
        return wp_create_nonce($action);
    }
    
    /**
     * Get all available nonce actions
     */
    public function get_nonce_actions(): array {
        return $this->nonce_actions;
    }
    
    /**
     * Validate nonce action exists
     */
    public function is_valid_action(string $action): bool {
        return array_key_exists($action, $this->nonce_actions);
    }
    
    /**
     * Security helper for AJAX handlers
     */
    public static function validate_ajax_security(
        array $allowed_nonces = [],
        string $required_capability = 'read',
        bool $allow_public = false
    ): array {
        $result = [
            'valid' => false,
            'nonce_verified' => false,
            'capability_verified' => false,
            'error_code' => '',
            'error_message' => ''
        ];
        
        // Skip nonce check for public endpoints that explicitly allow it
        if (!$allow_public || is_user_logged_in()) {
            if (!self::verify_ajax_nonce($allowed_nonces)) {
                $result['error_code'] = 'invalid_nonce';
                $result['error_message'] = __('Security check failed.', 'happy-place');
                return $result;
            }
            $result['nonce_verified'] = true;
        } else {
            $result['nonce_verified'] = true; // Skip for public
        }
        
        // Check capabilities for logged-in users
        if (is_user_logged_in()) {
            if (!current_user_can($required_capability)) {
                $result['error_code'] = 'insufficient_permissions';
                $result['error_message'] = __('You do not have permission to perform this action.', 'happy-place');
                return $result;
            }
            $result['capability_verified'] = true;
        } elseif (!$allow_public) {
            $result['error_code'] = 'login_required';
            $result['error_message'] = __('You must be logged in to perform this action.', 'happy-place');
            return $result;
        } else {
            $result['capability_verified'] = true; // Skip for public
        }
        
        $result['valid'] = true;
        return $result;
    }
    
    /**
     * Send standardized AJAX error response
     */
    public static function send_ajax_error(array $validation_result, int $status_code = 403): void {
        wp_send_json_error([
            'message' => $validation_result['error_message'],
            'code' => $validation_result['error_code']
        ], $status_code);
    }
    
    /**
     * Enhanced AJAX security validation wrapper
     */
    public static function secure_ajax_handler(
        callable $handler,
        array $allowed_nonces = [],
        string $required_capability = 'read',
        bool $allow_public = false
    ): void {
        try {
            $validation = self::validate_ajax_security(
                $allowed_nonces,
                $required_capability,
                $allow_public
            );
            
            if (!$validation['valid']) {
                self::send_ajax_error($validation);
                return;
            }
            
            // Call the actual handler
            call_user_func($handler);
            
        } catch (\Exception $e) {
            error_log('HPH AJAX Security Error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => __('An error occurred while processing your request.', 'happy-place'),
                'code' => 'exception_occurred'
            ], 500);
        }
    }
}
