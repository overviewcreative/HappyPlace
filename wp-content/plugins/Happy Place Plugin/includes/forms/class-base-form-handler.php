<?php
/**
 * Base Form Handler - Unified foundation for all form handlers
 * 
 * Provides standardized form handling patterns for all CPT forms
 * in the Happy Place system with ACF integration.
 * 
 * @package HappyPlace
 * @subpackage Forms
 */

namespace HappyPlace\Forms;

if (!defined('ABSPATH')) {
    exit;
}

abstract class Base_Form_Handler {
    
    /**
     * Form type identifier
     *
     * @var string
     */
    protected $form_type;
    
    /**
     * Post type for this form
     *
     * @var string
     */
    protected $post_type;
    
    /**
     * Required fields for validation
     *
     * @var array
     */
    protected $required_fields = [];
    
    /**
     * Field validation rules
     *
     * @var array
     */
    protected $validation_rules = [];
    
    /**
     * ACF field groups for this form
     *
     * @var array
     */
    protected $acf_field_groups = [];
    
    /**
     * Form errors
     *
     * @var array
     */
    protected $errors = [];
    
    /**
     * Constructor
     *
     * @param string $form_type Form type identifier
     * @param string $post_type WordPress post type
     */
    public function __construct($form_type, $post_type) {
        $this->form_type = $form_type;
        $this->post_type = $post_type;
        
        $this->setup_validation_rules();
        $this->setup_acf_integration();
    }
    
    /**
     * Initialize the form handler
     */
    public function init() {
        // Hook into WordPress actions
        add_action('wp_loaded', [$this, 'register_post_type_support']);
        
        error_log("HPH Forms: Initialized {$this->form_type} form handler");
    }
    
    /**
     * Handle form submission
     *
     * @param array $form_data Form data
     * @return array|WP_Error Result of submission
     */
    public function handle_submission($form_data) {
        // Sanitize data
        $sanitized_data = $this->sanitize_form_data($form_data);
        
        // Validate data
        $validation_result = $this->validate_form_data($sanitized_data);
        if (is_wp_error($validation_result)) {
            return $validation_result;
        }
        
        // Process the submission
        $result = $this->process_submission($sanitized_data);
        
        if (!is_wp_error($result)) {
            // Handle success actions
            $this->handle_success($result, $sanitized_data);
        }
        
        return $result;
    }
    
    /**
     * Sanitize form data
     *
     * @param array $form_data Raw form data
     * @return array Sanitized form data
     */
    protected function sanitize_form_data($form_data) {
        $sanitized = [];
        
        foreach ($form_data as $field => $value) {
            $sanitized[$field] = $this->sanitize_field($field, $value);
        }
        
        return apply_filters("hph_sanitize_form_data_{$this->form_type}", $sanitized, $form_data);
    }
    
    /**
     * Sanitize individual field
     *
     * @param string $field Field name
     * @param mixed $value Field value
     * @return mixed Sanitized value
     */
    protected function sanitize_field($field, $value) {
        // Check if field has specific sanitization rule
        if (isset($this->validation_rules[$field]['sanitize'])) {
            $sanitize_callback = $this->validation_rules[$field]['sanitize'];
            return call_user_func($sanitize_callback, $value);
        }
        
        // Default sanitization based on field type
        if (is_array($value)) {
            return array_map('sanitize_text_field', $value);
        }
        
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return sanitize_email($value);
        }
        
        if (is_numeric($value)) {
            return sanitize_text_field($value);
        }
        
        // Check for textarea fields
        if (strpos($field, 'description') !== false || strpos($field, 'message') !== false) {
            return sanitize_textarea_field($value);
        }
        
        return sanitize_text_field($value);
    }
    
    /**
     * Validate form data
     *
     * @param array $form_data Sanitized form data
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    protected function validate_form_data($form_data) {
        $errors = [];
        
        // Check required fields
        foreach ($this->required_fields as $field) {
            if (empty($form_data[$field])) {
                $errors[$field] = sprintf(__('%s is required', 'happy-place'), $this->get_field_label($field));
            }
        }
        
        // Run field-specific validation
        foreach ($this->validation_rules as $field => $rules) {
            if (isset($form_data[$field]) && !empty($form_data[$field])) {
                $value = $form_data[$field];
                
                foreach ($rules as $rule => $params) {
                    if ($rule === 'sanitize') continue;
                    
                    $validation_result = $this->validate_field($field, $value, $rule, $params);
                    if (!$validation_result['valid']) {
                        $errors[$field] = $validation_result['message'];
                        break;
                    }
                }
            }
        }
        
        // Run custom validation
        $custom_errors = $this->custom_validation($form_data);
        if (!empty($custom_errors)) {
            $errors = array_merge($errors, $custom_errors);
        }
        
        if (!empty($errors)) {
            return new \WP_Error('validation_failed', 'Form validation failed', $errors);
        }
        
        return true;
    }
    
    /**
     * Validate individual field
     *
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $rule Validation rule
     * @param mixed $params Rule parameters
     * @return array Validation result
     */
    protected function validate_field($field, $value, $rule, $params) {
        switch ($rule) {
            case 'email':
                return [
                    'valid' => is_email($value),
                    'message' => __('Please enter a valid email address', 'happy-place')
                ];
                
            case 'phone':
                return [
                    'valid' => preg_match('/^[\d\s\-\(\)]+$/', $value),
                    'message' => __('Please enter a valid phone number', 'happy-place')
                ];
                
            case 'numeric':
                return [
                    'valid' => is_numeric($value),
                    'message' => __('Please enter a valid number', 'happy-place')
                ];
                
            case 'min_length':
                return [
                    'valid' => strlen($value) >= $params,
                    'message' => sprintf(__('Must be at least %d characters long', 'happy-place'), $params)
                ];
                
            case 'max_length':
                return [
                    'valid' => strlen($value) <= $params,
                    'message' => sprintf(__('Must be no more than %d characters long', 'happy-place'), $params)
                ];
                
            case 'post_exists':
                return [
                    'valid' => get_post($value) !== null,
                    'message' => __('Invalid selection', 'happy-place')
                ];
                
            default:
                return ['valid' => true, 'message' => ''];
        }
    }
    
    /**
     * Get field label for error messages
     *
     * @param string $field Field name
     * @return string Field label
     */
    protected function get_field_label($field) {
        // Try to get ACF field label
        $field_object = get_field_object($field);
        if ($field_object && isset($field_object['label'])) {
            return $field_object['label'];
        }
        
        // Generate label from field name
        return ucwords(str_replace(['_', '-'], ' ', $field));
    }
    
    /**
     * Setup ACF integration
     */
    protected function setup_acf_integration() {
        if (function_exists('acf_get_field_groups')) {
            $field_groups = acf_get_field_groups(['post_type' => $this->post_type]);
            foreach ($field_groups as $group) {
                $this->acf_field_groups[] = $group['key'];
            }
        }
    }
    
    /**
     * Register post type support
     */
    public function register_post_type_support() {
        // Add form support to post type
        add_post_type_support($this->post_type, 'hph-forms');
    }
    
    /**
     * Generate form preview
     *
     * @param array $form_data Form data
     * @return string Preview HTML
     */
    public function generate_preview($form_data) {
        ob_start();
        ?>
        <div class="hph-form-preview hph-form-preview-<?php echo esc_attr($this->form_type); ?>">
            <h3><?php _e('Form Preview', 'happy-place'); ?></h3>
            <?php $this->render_preview($form_data); ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle successful submission
     *
     * @param mixed $result Submission result
     * @param array $form_data Form data
     */
    protected function handle_success($result, $form_data) {
        // Log success
        error_log("HPH Forms: Successful {$this->form_type} form submission");
        
        // Fire action hook
        do_action("hph_form_success_{$this->form_type}", $result, $form_data);
        do_action('hph_form_success', $this->form_type, $result, $form_data);
    }
    
    // Abstract methods that must be implemented by child classes
    
    /**
     * Setup validation rules for this form type
     */
    abstract protected function setup_validation_rules();
    
    /**
     * Process the form submission
     *
     * @param array $form_data Validated form data
     * @return mixed|WP_Error Result of processing
     */
    abstract protected function process_submission($form_data);
    
    /**
     * Custom validation logic
     *
     * @param array $form_data Form data
     * @return array Validation errors
     */
    abstract protected function custom_validation($form_data);
    
    /**
     * Render form preview
     *
     * @param array $form_data Form data
     */
    abstract protected function render_preview($form_data);
}