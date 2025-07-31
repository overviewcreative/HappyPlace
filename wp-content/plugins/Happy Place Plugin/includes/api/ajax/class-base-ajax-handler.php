<?php
/**
 * Base AJAX Handler - Foundation Class
 * 
 * File: includes/api/ajax/class-base-ajax-handler.php
 */

namespace HappyPlace\Api\Ajax;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

abstract class Base_Ajax_Handler {
    
    protected string $nonce_action = 'hph_ajax_nonce';
    protected array $rate_limits = [];
    protected array $capabilities = [];
    protected array $cache_config = [];
    protected string $handler_name;
    
    public function __construct() {
        $this->handler_name = $this->get_handler_name();
        $this->setup_actions();
        $this->setup_hooks();
        error_log("HPH AJAX: Handler '{$this->handler_name}' initialized");
    }
    
    private function setup_actions(): void {
        $actions = $this->get_actions();
        
        if (empty($actions)) {
            error_log("HPH AJAX: No actions defined for handler '{$this->handler_name}'");
            return;
        }
        
        foreach ($actions as $action => $config) {
            $this->register_action($action, $config);
        }
        
        error_log("HPH AJAX: Registered " . count($actions) . " actions for '{$this->handler_name}'");
    }
    
    private function register_action(string $action, array $config): void {
        $full_action = "hph_{$action}";
        
        add_action("wp_ajax_{$full_action}", function() use ($action, $config) {
            $this->handle_ajax_request($action, $config);
        });
        
        if ($config['public'] ?? false) {
            add_action("wp_ajax_nopriv_{$full_action}", function() use ($action, $config) {
                $this->handle_ajax_request($action, $config);
            });
        }
        
        $this->capabilities[$action] = $config['capability'] ?? 'read';
        $this->rate_limits[$action] = $config['rate_limit'] ?? null;
        $this->cache_config[$action] = $config['cache'] ?? null;
    }
    
    private function handle_ajax_request(string $action, array $config): void {
        try {
            if (!$this->validate_security($action, $config)) {
                return;
            }
            
            if (!$this->check_rate_limit($action)) {
                wp_send_json_error([
                    'message' => 'Rate limit exceeded',
                    'code' => 'rate_limit_exceeded'
                ], 429);
                return;
            }
            
            $callback = $config['callback'] ?? "handle_{$action}";
            if (!method_exists($this, $callback)) {
                wp_send_json_error([
                    'message' => 'Handler method not found',
                    'code' => 'method_not_found'
                ]);
                return;
            }
            
            do_action("hph_ajax_before_{$action}", $_POST);
            $this->$callback();
            do_action("hph_ajax_after_{$action}", $_POST);
            
        } catch (\Exception $e) {
            $this->handle_exception($e, $action);
        }
    }
    
    protected function validate_security(string $action, array $config): bool {
        $skip_nonce = ($config['skip_nonce'] ?? false) && ($config['public'] ?? false);
        
        if (!$skip_nonce) {
            if (!check_ajax_referer($this->nonce_action, 'nonce', false)) {
                wp_send_json_error([
                    'message' => 'Security check failed',
                    'code' => 'invalid_nonce'
                ], 403);
                return false;
            }
        }
        
        $required_capability = $this->capabilities[$action] ?? 'read';
        if (!current_user_can($required_capability)) {
            wp_send_json_error([
                'message' => 'Insufficient permissions',
                'code' => 'insufficient_permissions'
            ], 403);
            return false;
        }
        
        return $this->validate_custom_security($action, $config);
    }
    
    protected function check_rate_limit(string $action): bool {
        $limit = $this->rate_limits[$action];
        if (!$limit) {
            return true;
        }
        
        $user_id = get_current_user_id();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = "hph_rate_limit_{$this->handler_name}_{$action}_{$user_id}_{$ip}";
        
        $current_count = get_transient($key) ?: 0;
        if ($current_count >= $limit) {
            return false;
        }
        
        set_transient($key, $current_count + 1, 60);
        return true;
    }
    
    protected function handle_exception(\Exception $e, string $action): void {
        error_log("HPH AJAX Exception [{$this->handler_name}::{$action}]: " . $e->getMessage());
        
        $user_message = $this->get_user_friendly_error_message($e, $action);
        wp_send_json_error([
            'message' => $user_message,
            'code' => 'exception_occurred'
        ], 500);
    }
    
    protected function get_user_friendly_error_message(\Exception $e, string $action): string {
        return apply_filters('hph_ajax_user_error_message', 
            'An error occurred while processing your request. Please try again.',
            $e, $action, $this->handler_name
        );
    }
    
    protected function setup_hooks(): void {
        // Override in child classes for additional hooks
    }
    
    protected function validate_custom_security(string $action, array $config): bool {
        return true;
    }
    
    protected function send_success($data, ?string $action = null): void {
        wp_send_json_success($data);
    }
    
    protected function send_error(string $message, array $data = [], int $status_code = 400): void {
        wp_send_json_error(array_merge([
            'message' => $message,
            'code' => 'handler_error'
        ], $data), $status_code);
    }
    
    protected function validate_required_params(array $required_params): bool {
        foreach ($required_params as $param => $type) {
            if (!isset($_POST[$param])) {
                $this->send_error("Missing required parameter: {$param}");
                return false;
            }
            
            if (!$this->validate_param_type($_POST[$param], $type)) {
                $this->send_error("Invalid parameter type for: {$param}");
                return false;
            }
        }
        
        return true;
    }
    
    protected function validate_param_type($value, string $type): bool {
        switch ($type) {
            case 'int':
                return is_numeric($value);
            case 'string':
                return is_string($value);
            case 'array':
                return is_array($value);
            case 'email':
                return is_email($value);
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) !== false;
            default:
                return true;
        }
    }
    
    protected function get_handler_name(): string {
        $class_name = get_class($this);
        $parts = explode('\\', $class_name);
        $handler_class = end($parts);
        return strtolower(str_replace('_Ajax', '', $handler_class));
    }
    
    abstract protected function get_actions(): array;
}
