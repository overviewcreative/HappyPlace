<?php

/**
 * Base AJAX Handler Class
 * 
 * Provides shared functionality for all dashboard AJAX handlers including
 * security validation, error handling, and response formatting.
 * 
 * @package HappyPlace\Dashboard\Ajax
 * @since 3.0.0
 */

namespace HappyPlace\Dashboard\Ajax;

use Exception;
use WP_Error;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base AJAX Handler
 * 
 * All specific AJAX handlers should extend this class to inherit
 * common security, validation, and response formatting functionality.
 */
abstract class HPH_Base_Ajax
{
    /**
     * @var array Validation rules for different data types
     */
    protected array $validation_rules = [];

    /**
     * @var array Error messages for validation failures
     */
    protected array $error_messages = [];

    /**
     * Constructor - Initialize base functionality
     */
    public function __construct()
    {
        $this->setup_validation_rules();
        $this->setup_error_messages();
        $this->register_ajax_actions();
    }

    /**
     * Register AJAX actions - to be implemented by child classes
     */
    abstract protected function register_ajax_actions(): void;

    /**
     * Setup validation rules for common data types
     */
    protected function setup_validation_rules(): void
    {
        $this->validation_rules = [
            'listing_id' => ['type' => 'integer', 'min' => 1],
            'post_id' => ['type' => 'integer', 'min' => 1],
            'user_id' => ['type' => 'integer', 'min' => 1],
            'email' => ['type' => 'email'],
            'phone' => ['type' => 'phone'],
            'price' => ['type' => 'decimal', 'min' => 0],
            'bedrooms' => ['type' => 'integer', 'min' => 0, 'max' => 20],
            'bathrooms' => ['type' => 'decimal', 'min' => 0, 'max' => 20],
            'square_feet' => ['type' => 'integer', 'min' => 0],
            'lot_size' => ['type' => 'decimal', 'min' => 0],
            'year_built' => ['type' => 'integer', 'min' => 1800, 'max' => date('Y') + 2],
            'latitude' => ['type' => 'decimal', 'min' => -90, 'max' => 90],
            'longitude' => ['type' => 'decimal', 'min' => -180, 'max' => 180]
        ];
    }

    /**
     * Setup error messages
     */
    protected function setup_error_messages(): void
    {
        $this->error_messages = [
            'invalid_nonce' => 'Security verification failed. Please refresh and try again.',
            'missing_permissions' => 'You do not have permission to perform this action.',
            'invalid_data' => 'The submitted data is invalid or incomplete.',
            'missing_required' => 'Required field is missing: %s',
            'database_error' => 'A database error occurred. Please try again later.',
            'file_upload_error' => 'File upload failed: %s',
            'rate_limit' => 'Too many requests. Please wait before trying again.'
        ];
    }

    /**
     * Verify nonce for security
     */
    protected function verify_nonce(string $action = 'hph_dashboard_nonce'): bool
    {
        $nonce = $_POST['nonce'] ?? $_GET['nonce'] ?? '';
        return wp_verify_nonce($nonce, $action);
    }

    /**
     * Check user capabilities
     */
    protected function check_capabilities(array $required_caps = ['edit_posts']): bool
    {
        foreach ($required_caps as $cap) {
            if (!current_user_can($cap)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Validate input data against rules
     */
    protected function validate_data(array $data, array $rules = []): object
    {
        $result = (object) [
            'is_valid' => true,
            'errors' => [],
            'field_errors' => [],
            'data' => []
        ];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            // Check required fields
            if (($rule['required'] ?? false) && empty($value)) {
                $result->is_valid = false;
                $result->field_errors[$field] = sprintf(
                    $this->error_messages['missing_required'], 
                    $field
                );
                continue;
            }

            // Skip validation if field is empty and not required
            if (empty($value)) {
                continue;
            }

            // Validate based on type
            $validation_result = $this->validate_field($value, $rule, $field);
            if (!$validation_result['valid']) {
                $result->is_valid = false;
                $result->field_errors[$field] = $validation_result['message'];
            } else {
                $result->data[$field] = $validation_result['value'];
            }
        }

        return $result;
    }

    /**
     * Validate individual field
     */
    private function validate_field($value, array $rule, string $field_name): array
    {
        $base_rule = $this->validation_rules[$rule['type']] ?? $rule;
        
        switch ($base_rule['type']) {
            case 'integer':
                if (!is_numeric($value) || $value != (int)$value) {
                    return ['valid' => false, 'message' => "{$field_name} must be a whole number"];
                }
                $value = (int)$value;
                break;

            case 'decimal':
                if (!is_numeric($value)) {
                    return ['valid' => false, 'message' => "{$field_name} must be a number"];
                }
                $value = (float)$value;
                break;

            case 'email':
                if (!is_email($value)) {
                    return ['valid' => false, 'message' => "{$field_name} must be a valid email address"];
                }
                break;

            case 'phone':
                $cleaned = preg_replace('/[^\d]/', '', $value);
                if (strlen($cleaned) < 10) {
                    return ['valid' => false, 'message' => "{$field_name} must be a valid phone number"];
                }
                $value = $cleaned;
                break;

            case 'string':
                $value = sanitize_text_field($value);
                break;

            case 'html':
                $value = wp_kses_post($value);
                break;
        }

        // Check min/max constraints
        if (isset($base_rule['min']) && $value < $base_rule['min']) {
            return ['valid' => false, 'message' => "{$field_name} must be at least {$base_rule['min']}"];
        }

        if (isset($base_rule['max']) && $value > $base_rule['max']) {
            return ['valid' => false, 'message' => "{$field_name} must be no more than {$base_rule['max']}"];
        }

        return ['valid' => true, 'value' => $value];
    }

    /**
     * Send success response
     */
    protected function send_success(array $data = [], string $message = ''): void
    {
        $response = ['success' => true];
        
        if (!empty($data)) {
            $response['data'] = $data;
        }
        
        if (!empty($message)) {
            $response['message'] = $message;
        }

        wp_send_json($response);
    }

    /**
     * Send error response
     */
    protected function send_error(string $message, array $data = [], int $code = 400): void
    {
        $response = [
            'success' => false,
            'message' => $message,
            'code' => $code
        ];

        if (!empty($data)) {
            $response['data'] = $data;
        }

        wp_send_json($response, $code);
    }

    /**
     * Handle security check failure
     */
    protected function handle_security_failure(string $type = 'nonce'): void
    {
        $message = $type === 'nonce' 
            ? $this->error_messages['invalid_nonce']
            : $this->error_messages['missing_permissions'];
            
        $this->send_error($message, [], 403);
    }

    /**
     * Rate limiting check
     */
    protected function check_rate_limit(string $action, int $limit = 10, int $window = 60): bool
    {
        $user_id = get_current_user_id();
        $key = "hph_rate_limit_{$action}_{$user_id}";
        
        $current_count = get_transient($key) ?: 0;
        
        if ($current_count >= $limit) {
            return false;
        }

        set_transient($key, $current_count + 1, $window);
        return true;
    }

    /**
     * Log AJAX activity for debugging and monitoring
     */
    protected function log_activity(string $action, array $data = []): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'HPH AJAX: %s by user %d with data: %s',
                $action,
                get_current_user_id(),
                json_encode($data)
            ));
        }
    }

    /**
     * Get user context for personalized responses
     */
    protected function get_user_context(): array
    {
        $current_user = wp_get_current_user();
        
        return [
            'user_id' => $current_user->ID,
            'user_login' => $current_user->user_login,
            'user_email' => $current_user->user_email,
            'user_roles' => $current_user->roles,
            'is_agent' => in_array('hph_agent', $current_user->roles),
            'is_admin' => in_array('administrator', $current_user->roles),
            'agent_id' => get_user_meta($current_user->ID, 'hph_agent_id', true)
        ];
    }

    /**
     * Format response data consistently
     */
    protected function format_response_data(array $data, string $type = 'default'): array
    {
        switch ($type) {
            case 'listing':
                return $this->format_listing_data($data);
            case 'lead':
                return $this->format_lead_data($data);
            case 'performance':
                return $this->format_performance_data($data);
            default:
                return $data;
        }
    }

    /**
     * Format listing data for consistent API responses
     */
    private function format_listing_data(array $listing): array
    {
        return [
            'id' => (int)$listing['ID'],
            'title' => $listing['post_title'],
            'status' => $listing['post_status'],
            'price' => (float)($listing['_price'] ?? 0),
            'bedrooms' => (int)($listing['_bedrooms'] ?? 0),
            'bathrooms' => (float)($listing['_bathrooms'] ?? 0),
            'square_feet' => (int)($listing['_square_feet'] ?? 0),
            'featured_image' => get_the_post_thumbnail_url($listing['ID'], 'medium'),
            'permalink' => get_permalink($listing['ID']),
            'edit_url' => get_edit_post_link($listing['ID']),
            'created_date' => $listing['post_date'],
            'modified_date' => $listing['post_modified']
        ];
    }

    /**
     * Format lead data for API responses
     */
    private function format_lead_data(array $lead): array
    {
        return [
            'id' => (int)$lead['ID'],
            'name' => $lead['post_title'],
            'email' => $lead['_email'] ?? '',
            'phone' => $lead['_phone'] ?? '',
            'status' => $lead['post_status'],
            'source' => $lead['_source'] ?? 'unknown',
            'created_date' => $lead['post_date'],
            'last_contact' => $lead['_last_contact'] ?? null
        ];
    }

    /**
     * Format performance data for API responses
     */
    private function format_performance_data(array $data): array
    {
        return [
            'labels' => $data['labels'] ?? [],
            'values' => array_map('intval', $data['values'] ?? []),
            'total' => array_sum($data['values'] ?? []),
            'period' => $data['period'] ?? '30d',
            'updated' => current_time('mysql')
        ];
    }
}
