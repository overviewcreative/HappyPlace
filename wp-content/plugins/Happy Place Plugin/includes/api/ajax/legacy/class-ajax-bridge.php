<?php
/**
 * Legacy AJAX Bridge - Backward Compatibility
 * 
 * File: includes/api/ajax/legacy/class-ajax-bridge.php
 */

namespace HappyPlace\Api\Ajax\Legacy;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Ajax_Bridge {
    
    private array $handlers;
    private array $legacy_mappings;
    
    public function __construct(array $handlers, array $legacy_mappings) {
        $this->handlers = $handlers;
        $this->legacy_mappings = $legacy_mappings;
        $this->register_legacy_endpoints();
    }
    
    private function register_legacy_endpoints(): void {
        foreach ($this->legacy_mappings as $old_action => $config) {
            add_action("wp_ajax_{$old_action}", [$this, 'route_legacy_request']);
            
            if ($this->was_public_action($old_action)) {
                add_action("wp_ajax_nopriv_{$old_action}", [$this, 'route_legacy_request']);
            }
        }
        
        error_log('HPH AJAX: Legacy bridge registered ' . count($this->legacy_mappings) . ' endpoints');
    }
    
    public function route_legacy_request(): void {
        $current_action = current_action();
        $old_action = str_replace(['wp_ajax_', 'wp_ajax_nopriv_'], '', $current_action);
        
        if (!isset($this->legacy_mappings[$old_action])) {
            wp_send_json_error('Unknown legacy action: ' . $old_action);
            return;
        }
        
        $config = $this->legacy_mappings[$old_action];
        $handler = $this->handlers[$config['handler']] ?? null;
        
        if (!$handler || !method_exists($handler, $config['method'])) {
            wp_send_json_error('Handler not available for legacy action: ' . $old_action);
            return;
        }
        
        $this->log_legacy_usage($old_action);
        
        try {
            call_user_func([$handler, $config['method']]);
        } catch (\Exception $e) {
            error_log("HPH Legacy Bridge Error [{$old_action}]: " . $e->getMessage());
            wp_send_json_error('Legacy handler failed');
        }
    }
    
    private function was_public_action(string $action): bool {
        $public_actions = [
            'get_listing_data_for_flyer',
            'hph_get_flyer_templates'
        ];
        
        return in_array($action, $public_actions);
    }
    
    private function log_legacy_usage(string $action): void {
        $usage_data = get_option('hph_legacy_ajax_usage', []);
        $usage_data[$action] = [
            'count' => ($usage_data[$action]['count'] ?? 0) + 1,
            'last_used' => current_time('mysql'),
            'user_id' => get_current_user_id()
        ];
        update_option('hph_legacy_ajax_usage', $usage_data);
    }
}
