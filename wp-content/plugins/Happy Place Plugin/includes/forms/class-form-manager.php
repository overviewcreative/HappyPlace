<?php
/**
 * Form Manager - Central coordinator for all form operations
 * 
 * Manages form handlers, validation, rendering, and AJAX operations
 * for all CPT-related forms in the Happy Place system.
 * 
 * @package HappyPlace
 * @subpackage Forms
 */

namespace HappyPlace\Forms;

if (!defined('ABSPATH')) {
    exit;
}

class Form_Manager {
    
    /**
     * Registered form handlers
     *
     * @var array
     */
    private static $handlers = [];
    
    /**
     * Registered form validators
     *
     * @var array
     */
    private static $validators = [];
    
    /**
     * Form renderer instance
     *
     * @var Form_Renderer
     */
    private static $renderer;
    
    /**
     * Initialize the form management system
     */
    public static function init() {
        self::$renderer = new Form_Renderer();
        
        // Register core form handlers
        self::register_core_handlers();
        
        // Initialize AJAX handlers
        self::init_ajax_handlers();
        
        // Initialize frontend form scripts
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_form_scripts']);
        
        // Initialize admin form scripts
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_form_scripts']);
        
        // Add form messages display
        add_action('wp_footer', [self::class, 'display_form_messages']);
        
        error_log('HPH Form Manager: Form management system initialized');
    }
    
    /**
     * Register core form handlers
     */
    private static function register_core_handlers() {
        // Include handler files
        $handler_files = [
            'listing' => plugin_dir_path(__FILE__) . 'handlers/class-listing-form-handler.php',
            'agent' => plugin_dir_path(__FILE__) . 'handlers/class-agent-form-handler.php',
            'community' => plugin_dir_path(__FILE__) . 'handlers/class-community-form-handler.php',
            'office' => plugin_dir_path(__FILE__) . 'handlers/class-office-form-handler.php',
            'user' => plugin_dir_path(__FILE__) . 'handlers/class-user-form-handler.php',
            'open_house' => plugin_dir_path(__FILE__) . 'handlers/class-open-house-form-handler.php'
        ];
        
        foreach ($handler_files as $type => $file) {
            if (file_exists($file)) {
                require_once $file;
            }
        }
        
        // Register handlers
        $handler_classes = [
            'listing' => '\\HappyPlace\\Forms\\Handlers\\Listing_Form_Handler',
            'agent' => '\\HappyPlace\\Forms\\Handlers\\Agent_Form_Handler',
            'community' => '\\HappyPlace\\Forms\\Handlers\\Community_Form_Handler',
            'office' => '\\HappyPlace\\Forms\\Handlers\\Office_Form_Handler',
            'user' => '\\HappyPlace\\Forms\\Handlers\\User_Form_Handler',
            'open_house' => '\\HappyPlace\\Forms\\Handlers\\Open_House_Form_Handler'
        ];
        
        foreach ($handler_classes as $type => $class) {
            if (class_exists($class)) {
                $handler = new $class();
                $handler->init();
                self::register_handler($type, $handler);
                error_log("HPH Form Manager: Successfully registered {$type} form handler");
            } else {
                error_log("HPH Form Manager: Handler class {$class} not found");
            }
        }
    }
    
    /**
     * Register a form handler
     *
     * @param string $type Form type identifier
     * @param object $handler Form handler instance
     */
    public static function register_handler($type, $handler) {
        self::$handlers[$type] = $handler;
        
        // Initialize the handler
        if (method_exists($handler, 'init')) {
            $handler->init();
        }
        
        error_log("HPH Form Manager: Registered {$type} form handler");
    }
    
    /**
     * Get a registered form handler
     *
     * @param string $type Form type identifier
     * @return object|null Form handler or null if not found
     */
    public static function get_handler($type) {
        return self::$handlers[$type] ?? null;
    }
    
    /**
     * Register a form validator
     *
     * @param string $type Form type identifier
     * @param object $validator Validator instance
     */
    public static function register_validator($type, $validator) {
        self::$validators[$type] = $validator;
        error_log("HPH Form Manager: Registered {$type} form validator");
    }
    
    /**
     * Get a registered form validator
     *
     * @param string $type Form type identifier
     * @return object|null Validator or null if not found
     */
    public static function get_validator($type) {
        return self::$validators[$type] ?? null;
    }
    
    /**
     * Initialize AJAX handlers
     */
    private static function init_ajax_handlers() {
        // Form submission
        add_action('wp_ajax_hph_submit_form', [self::class, 'ajax_submit_form']);
        add_action('wp_ajax_nopriv_hph_submit_form', [self::class, 'ajax_submit_form']);
        
        // Form validation
        add_action('wp_ajax_hph_validate_form', [self::class, 'ajax_validate_form']);
        add_action('wp_ajax_nopriv_hph_validate_form', [self::class, 'ajax_validate_form']);
        
        // Dynamic form loading
        add_action('wp_ajax_hph_load_form', [self::class, 'ajax_load_form']);
        add_action('wp_ajax_nopriv_hph_load_form', [self::class, 'ajax_load_form']);
        
        // Field auto-population
        add_action('wp_ajax_hph_populate_field', [self::class, 'ajax_populate_field']);
        add_action('wp_ajax_nopriv_hph_populate_field', [self::class, 'ajax_populate_field']);
        
        // Form preview
        add_action('wp_ajax_hph_preview_form', [self::class, 'ajax_preview_form']);
        add_action('wp_ajax_nopriv_hph_preview_form', [self::class, 'ajax_preview_form']);
    }
    
    /**
     * Handle AJAX form submission
     */
    public static function ajax_submit_form() {
        check_ajax_referer('hph_form_submit', 'nonce');
        
        $form_type = sanitize_text_field($_POST['form_type'] ?? '');
        $form_data = $_POST['form_data'] ?? [];
        
        // Get the appropriate handler
        $handler = self::get_handler($form_type);
        if (!$handler) {
            wp_send_json_error([
                'message' => "Form handler for type '{$form_type}' not found"
            ]);
        }
        
        // Validate the form
        $validation_result = self::validate_form($form_type, $form_data);
        if (is_wp_error($validation_result)) {
            wp_send_json_error([
                'message' => 'Validation failed',
                'errors' => $validation_result->get_error_data()
            ]);
        }
        
        // Submit the form
        $result = $handler->handle_submission($form_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message(),
                'errors' => $result->get_error_data()
            ]);
        }
        
        wp_send_json_success([
            'message' => 'Form submitted successfully',
            'data' => $result
        ]);
    }
    
    /**
     * Handle AJAX form validation
     */
    public static function ajax_validate_form() {
        check_ajax_referer('hph_form_validate', 'nonce');
        
        $form_type = sanitize_text_field($_POST['form_type'] ?? '');
        $form_data = $_POST['form_data'] ?? [];
        
        $result = self::validate_form($form_type, $form_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => 'Validation failed',
                'errors' => $result->get_error_data()
            ]);
        }
        
        wp_send_json_success([
            'message' => 'Validation passed'
        ]);
    }
    
    /**
     * Handle AJAX form loading
     */
    public static function ajax_load_form() {
        check_ajax_referer('hph_form_load', 'nonce');
        
        $form_type = sanitize_text_field($_POST['form_type'] ?? '');
        $form_args = $_POST['form_args'] ?? [];
        
        $form_html = self::render_form($form_type, $form_args);
        
        if (is_wp_error($form_html)) {
            wp_send_json_error([
                'message' => $form_html->get_error_message()
            ]);
        }
        
        wp_send_json_success([
            'html' => $form_html
        ]);
    }
    
    /**
     * Handle AJAX field auto-population
     */
    public static function ajax_populate_field() {
        check_ajax_referer('hph_form_populate', 'nonce');
        
        $field_type = sanitize_text_field($_POST['field_type'] ?? '');
        $trigger_value = sanitize_text_field($_POST['trigger_value'] ?? '');
        $target_field = sanitize_text_field($_POST['target_field'] ?? '');
        
        // Handle auto-population based on field type
        $populated_value = '';
        
        switch ($field_type) {
            case 'address_components':
                // Populate city/state/zip from full address
                $populated_value = self::populate_address_components($trigger_value);
                break;
                
            case 'community_hoa':
                // Populate HOA fee from community selection
                $populated_value = self::populate_community_hoa($trigger_value);
                break;
                
            case 'agent_office':
                // Populate office info from agent selection
                $populated_value = self::populate_agent_office($trigger_value);
                break;
                
            default:
                $populated_value = apply_filters("hph_populate_field_{$field_type}", '', $trigger_value, $target_field);
        }
        
        wp_send_json_success([
            'value' => $populated_value
        ]);
    }
    
    /**
     * Handle AJAX form preview
     */
    public static function ajax_preview_form() {
        check_ajax_referer('hph_form_preview', 'nonce');
        
        $form_type = sanitize_text_field($_POST['form_type'] ?? '');
        $form_data = $_POST['form_data'] ?? [];
        
        $handler = self::get_handler($form_type);
        if (!$handler || !method_exists($handler, 'generate_preview')) {
            wp_send_json_error([
                'message' => 'Preview not available for this form type'
            ]);
        }
        
        $preview_html = $handler->generate_preview($form_data);
        
        wp_send_json_success([
            'html' => $preview_html
        ]);
    }
    
    /**
     * Validate form data
     *
     * @param string $form_type Form type identifier
     * @param array $form_data Form data to validate
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    public static function validate_form($form_type, $form_data) {
        $validator = self::get_validator($form_type);
        
        if (!$validator) {
            // Use basic validation
            return self::basic_validation($form_data);
        }
        
        return $validator->validate($form_data);
    }
    
    /**
     * Basic form validation
     *
     * @param array $form_data Form data
     * @return bool|WP_Error
     */
    private static function basic_validation($form_data) {
        $errors = [];
        
        // Check for required fields (if specified)
        if (isset($form_data['_required_fields'])) {
            $required_fields = $form_data['_required_fields'];
            foreach ($required_fields as $field) {
                if (empty($form_data[$field])) {
                    $errors[$field] = "This field is required";
                }
            }
        }
        
        // Basic email validation
        foreach ($form_data as $field => $value) {
            if (strpos($field, 'email') !== false && !empty($value) && !is_email($value)) {
                $errors[$field] = "Please enter a valid email address";
            }
        }
        
        if (!empty($errors)) {
            return new \WP_Error('validation_failed', 'Form validation failed', $errors);
        }
        
        return true;
    }
    
    /**
     * Render a form
     *
     * @param string $form_type Form type identifier
     * @param array $args Form arguments
     * @return string|WP_Error Form HTML or error
     */
    public static function render_form($form_type, $args = []) {
        if (!self::$renderer) {
            return new \WP_Error('no_renderer', 'Form renderer not available');
        }
        
        return self::$renderer->render($form_type, $args);
    }
    
    /**
     * Auto-populate address components
     *
     * @param string $address Full address
     * @return array Address components
     */
    private static function populate_address_components($address) {
        // Use Google Geocoding API if available
        if (function_exists('hph_bridge_get_address')) {
            return hph_bridge_get_address(0, 'components'); // TODO: Implement address parsing
        }
        
        return [];
    }
    
    /**
     * Auto-populate community HOA information
     *
     * @param string $community_id Community ID
     * @return array HOA information
     */
    private static function populate_community_hoa($community_id) {
        if (function_exists('hph_bridge_get_community_data')) {
            $community_data = hph_bridge_get_community_data($community_id);
            return [
                'hoa_fee_single_family' => $community_data['hoa_fee_single_family'] ?? 0,
                'hoa_fee_townhouse' => $community_data['hoa_fee_townhouse'] ?? 0,
                'hoa_fee_condo' => $community_data['hoa_fee_condo'] ?? 0,
                'hoa_includes' => $community_data['hoa_includes'] ?? []
            ];
        }
        
        return [];
    }
    
    /**
     * Auto-populate agent office information
     *
     * @param string $agent_id Agent ID
     * @return array Office information
     */
    private static function populate_agent_office($agent_id) {
        if (function_exists('hph_bridge_get_agent_data')) {
            $agent_data = hph_bridge_get_agent_data($agent_id);
            return $agent_data['office'] ?? [];
        }
        
        return [];
    }
    
    /**
     * Enqueue frontend form scripts
     */
    public static function enqueue_form_scripts() {
        wp_enqueue_script(
            'hph-forms',
            plugin_dir_url(__FILE__) . '../../assets/js/forms.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        wp_localize_script('hph-forms', 'hph_forms', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonces' => [
                'submit' => wp_create_nonce('hph_form_submit'),
                'validate' => wp_create_nonce('hph_form_validate'),
                'load' => wp_create_nonce('hph_form_load'),
                'populate' => wp_create_nonce('hph_form_populate'),
                'preview' => wp_create_nonce('hph_form_preview')
            ],
            'messages' => [
                'validation_failed' => __('Please correct the errors below', 'happy-place'),
                'submit_success' => __('Form submitted successfully', 'happy-place'),
                'submit_error' => __('There was an error submitting the form', 'happy-place'),
                'loading' => __('Loading...', 'happy-place')
            ]
        ]);
        
        wp_enqueue_style(
            'hph-forms',
            plugin_dir_url(__FILE__) . '../../assets/css/forms.css',
            [],
            '1.0.0'
        );
    }
    
    /**
     * Enqueue admin form scripts
     */
    public static function enqueue_admin_form_scripts() {
        $screen = get_current_screen();
        
        // Only load on relevant admin pages
        if ($screen && in_array($screen->post_type, ['listing', 'agent', 'community', 'office'])) {
            wp_enqueue_script(
                'hph-admin-forms',
                plugin_dir_url(__FILE__) . '../../assets/js/admin-forms.js',
                ['jquery'],
                '1.0.0',
                true
            );
        }
    }
    
    /**
     * Display form messages
     */
    public static function display_form_messages() {
        // Use WordPress transients instead of sessions to avoid header conflicts
        if (!session_id() && headers_sent() === false) {
            session_start();
        }
        
        if (!empty($_SESSION['hph_form_success'])) {
            echo '<div class="hph-form-message hph-form-success">' . esc_html($_SESSION['hph_form_success']) . '</div>';
            unset($_SESSION['hph_form_success']);
        }
        
        if (!empty($_SESSION['hph_form_error'])) {
            echo '<div class="hph-form-message hph-form-error">' . esc_html($_SESSION['hph_form_error']) . '</div>';
            unset($_SESSION['hph_form_error']);
        }
    }
    
    /**
     * Set form success message
     *
     * @param string $message Success message
     */
    public static function set_success_message($message) {
        if (!session_id() && headers_sent() === false) {
            session_start();
        }
        $_SESSION['hph_form_success'] = $message;
    }
    
    /**
     * Set form error message
     *
     * @param string $message Error message
     */
    public static function set_error_message($message) {
        if (!session_id() && headers_sent() === false) {
            session_start();
        }
        $_SESSION['hph_form_error'] = $message;
    }
    
    /**
     * Get list of registered form types
     *
     * @return array Form types
     */
    public static function get_registered_form_types() {
        return array_keys(self::$handlers);
    }
    
    /**
     * Check if a form type is registered
     *
     * @param string $form_type Form type
     * @return bool
     */
    public static function is_form_type_registered($form_type) {
        return isset(self::$handlers[$form_type]);
    }
}