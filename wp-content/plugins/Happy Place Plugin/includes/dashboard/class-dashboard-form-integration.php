<?php
/**
 * Dashboard Form Integration - Bridge between dashboard and form systems
 * 
 * Provides seamless integration between the dashboard and form management systems,
 * enabling dashboard sections to render and handle forms directly.
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

namespace HappyPlace\Dashboard;

use HappyPlace\Forms\Form_Manager;

if (!defined('ABSPATH')) {
    exit;
}

class Dashboard_Form_Integration {
    
    /**
     * @var Dashboard_Form_Integration Singleton instance
     */
    private static ?self $instance = null;
    
    /**
     * @var array Dashboard form configurations
     */
    private array $dashboard_forms = [];
    
    /**
     * @var array Form modal configurations
     */
    private array $modal_forms = [];
    
    /**
     * Get singleton instance
     */
    public static function get_instance(): self {
        return self::$instance ??= new self();
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->register_dashboard_forms();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        // Dashboard form AJAX handlers
        add_action('wp_ajax_hph_dashboard_form_submit', [$this, 'handle_dashboard_form_submit']);
        add_action('wp_ajax_hph_dashboard_form_load', [$this, 'handle_dashboard_form_load']);
        add_action('wp_ajax_hph_dashboard_form_validate', [$this, 'handle_dashboard_form_validate']);
        
        // Dashboard-specific form actions
        add_action('wp_ajax_hph_dashboard_quick_action', [$this, 'handle_quick_action']);
        
        // Enqueue dashboard form assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_dashboard_form_assets']);
        
        // Add dashboard form templates
        add_filter('hph_dashboard_section_data', [$this, 'add_form_data_to_sections'], 10, 2);
        
        // Register dashboard form shortcodes
        add_action('init', [$this, 'register_form_shortcodes']);
    }
    
    /**
     * Register dashboard-specific forms
     */
    private function register_dashboard_forms(): void {
        $this->dashboard_forms = [
            'quick-listing' => [
                'title' => __('Quick Add Listing', 'happy-place'),
                'type' => 'listing',
                'template' => 'dashboard-quick-listing',
                'modal' => true,
                'size' => 'large',
                'ajax' => true,
                'sections' => ['overview', 'listings'],
                'success_action' => 'refresh_section',
                'fields' => [
                    'post_title' => ['required' => true],
                    'price' => ['required' => true],
                    'bedrooms' => ['required' => true],
                    'bathrooms' => ['required' => true],
                    'square_feet' => ['required' => true],
                    'listing_agent' => ['auto_populate' => 'current_user_agent'],
                    'listing_status' => ['default' => 'active']
                ]
            ],
            
            'quick-agent' => [
                'title' => __('Quick Add Agent', 'happy-place'),
                'type' => 'agent',
                'template' => 'dashboard-quick-agent',
                'modal' => true,
                'size' => 'medium',
                'ajax' => true,
                'sections' => ['overview', 'agents'],
                'success_action' => 'refresh_section',
                'fields' => [
                    'post_title' => ['required' => true],
                    'first_name' => ['required' => true],
                    'last_name' => ['required' => true],
                    'email' => ['required' => true],
                    'phone' => ['required' => true],
                    'license_number' => ['required' => true]
                ]
            ],
            
            'open-house-form' => [
                'title' => __('Schedule Open House', 'happy-place'),
                'type' => 'open_house',
                'template' => 'dashboard-open-house',
                'modal' => true,
                'size' => 'large',
                'ajax' => true,
                'sections' => ['overview', 'calendar', 'listings'],
                'success_action' => 'refresh_section',
                'fields' => [
                    'post_title' => ['required' => true],
                    'listing' => ['required' => true, 'type' => 'listing_select'],
                    'hosting_agent' => ['required' => true, 'type' => 'agent_select', 'default' => 'current_user_agent'],
                    'open_house_date' => ['required' => true, 'type' => 'date'],
                    'start_time' => ['required' => true, 'type' => 'time'],
                    'end_time' => ['required' => true, 'type' => 'time'],
                    'description' => ['type' => 'textarea']
                ]
            ],
            
            'lead-capture' => [
                'title' => __('Add New Lead', 'happy-place'),
                'type' => 'lead',
                'template' => 'dashboard-lead-capture',
                'modal' => true,
                'size' => 'medium',
                'ajax' => true,
                'sections' => ['overview', 'leads'],
                'success_action' => 'refresh_section',
                'fields' => [
                    'first_name' => ['required' => true],
                    'last_name' => ['required' => true],
                    'email' => ['required' => true],
                    'phone' => ['required' => true],
                    'lead_source' => ['required' => true, 'type' => 'select'],
                    'interest_type' => ['required' => true, 'type' => 'select'],
                    'budget_min' => ['type' => 'number'],
                    'budget_max' => ['type' => 'number'],
                    'notes' => ['type' => 'textarea']
                ]
            ],
            
            'profile-update' => [
                'title' => __('Update Profile', 'happy-place'),
                'type' => 'user',
                'template' => 'dashboard-profile-update',
                'modal' => false,
                'inline' => true,
                'ajax' => true,
                'sections' => ['profile'],
                'success_action' => 'show_message',
                'fields' => [
                    'first_name' => ['required' => true],
                    'last_name' => ['required' => true],
                    'email' => ['required' => true],
                    'phone' => ['required' => true],
                    'bio' => ['type' => 'textarea'],
                    'profile_image' => ['type' => 'image']
                ]
            ]
        ];
        
        // Allow customization via filters
        $this->dashboard_forms = apply_filters('hph_dashboard_forms', $this->dashboard_forms);
    }
    
    /**
     * Handle dashboard form submission
     */
    public function handle_dashboard_form_submit(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        $form_id = sanitize_text_field($_POST['form_id'] ?? '');
        $form_data = $_POST['form_data'] ?? [];
        $section_id = sanitize_text_field($_POST['section_id'] ?? '');
        
        if (empty($form_id) || !isset($this->dashboard_forms[$form_id])) {
            wp_send_json_error(['message' => __('Invalid form', 'happy-place')]);
        }
        
        $form_config = $this->dashboard_forms[$form_id];
        
        // Check permissions
        if (!$this->can_submit_form($form_id)) {
            wp_send_json_error(['message' => __('Permission denied', 'happy-place')]);
        }
        
        // Pre-process form data for dashboard context
        $form_data = $this->preprocess_dashboard_form_data($form_id, $form_data);
        
        // Use existing form manager to handle submission
        $handler = Form_Manager::get_handler($form_config['type']);
        if (!$handler) {
            wp_send_json_error(['message' => __('Form handler not available', 'happy-place')]);
        }
        
        // Validate the form
        $validation_result = Form_Manager::validate_form($form_config['type'], $form_data);
        if (is_wp_error($validation_result)) {
            wp_send_json_error([
                'message' => __('Validation failed', 'happy-place'),
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
        
        // Handle success actions
        $response_data = $this->handle_form_success_action($form_config, $result, $section_id);
        
        wp_send_json_success($response_data);
    }
    
    /**
     * Handle dashboard form loading
     */
    public function handle_dashboard_form_load(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        $form_id = sanitize_text_field($_POST['form_id'] ?? '');
        $context = $_POST['context'] ?? [];
        
        if (empty($form_id) || !isset($this->dashboard_forms[$form_id])) {
            wp_send_json_error(['message' => __('Invalid form', 'happy-place')]);
        }
        
        if (!$this->can_access_form($form_id)) {
            wp_send_json_error(['message' => __('Permission denied', 'happy-place')]);
        }
        
        $form_html = $this->render_dashboard_form($form_id, $context);
        
        if (is_wp_error($form_html)) {
            wp_send_json_error(['message' => $form_html->get_error_message()]);
        }
        
        wp_send_json_success([
            'html' => $form_html,
            'config' => $this->dashboard_forms[$form_id]
        ]);
    }
    
    /**
     * Handle dashboard form validation
     */
    public function handle_dashboard_form_validate(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        $form_id = sanitize_text_field($_POST['form_id'] ?? '');
        $form_data = $_POST['form_data'] ?? [];
        
        if (empty($form_id) || !isset($this->dashboard_forms[$form_id])) {
            wp_send_json_error(['message' => __('Invalid form', 'happy-place')]);
        }
        
        $form_config = $this->dashboard_forms[$form_id];
        
        // Pre-process form data
        $form_data = $this->preprocess_dashboard_form_data($form_id, $form_data);
        
        // Validate using form manager
        $result = Form_Manager::validate_form($form_config['type'], $form_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => __('Validation failed', 'happy-place'),
                'errors' => $result->get_error_data()
            ]);
        }
        
        wp_send_json_success(['message' => __('Validation passed', 'happy-place')]);
    }
    
    /**
     * Handle quick actions
     */
    public function handle_quick_action(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        $action = sanitize_text_field($_POST['action_type'] ?? '');
        $data = $_POST['data'] ?? [];
        
        switch ($action) {
            case 'duplicate_listing':
                $this->handle_duplicate_listing($data);
                break;
                
            case 'mark_sold':
                $this->handle_mark_sold($data);
                break;
                
            case 'schedule_showing':
                $this->handle_schedule_showing($data);
                break;
                
            case 'update_status':
                $this->handle_update_status($data);
                break;
                
            default:
                do_action("hph_dashboard_quick_action_{$action}", $data);
                wp_send_json_error(['message' => __('Unknown action', 'happy-place')]);
        }
    }
    
    /**
     * Render dashboard form
     *
     * @param string $form_id Form identifier
     * @param array $context Form context data
     * @return string|WP_Error Form HTML or error
     */
    public function render_dashboard_form(string $form_id, array $context = []) {
        if (!isset($this->dashboard_forms[$form_id])) {
            return new \WP_Error('invalid_form', 'Form not found');
        }
        
        $form_config = $this->dashboard_forms[$form_id];
        
        // Check permissions
        if (!$this->can_access_form($form_id)) {
            return new \WP_Error('permission_denied', 'Access denied');
        }
        
        // Prepare form arguments
        $form_args = array_merge($context, [
            'dashboard_form' => true,
            'form_id' => $form_id,
            'form_config' => $form_config,
            'current_user_id' => get_current_user_id(),
            'ajax_enabled' => $form_config['ajax'] ?? true
        ]);
        
        // Get form template
        $template = $this->locate_form_template($form_config['template']);
        
        if (!$template) {
            // Use form manager as fallback
            return Form_Manager::render_form($form_config['type'], $form_args);
        }
        
        // Render custom dashboard template
        ob_start();
        
        // Make variables available to template
        extract($form_args);
        
        include $template;
        
        return ob_get_clean();
    }
    
    /**
     * Pre-process form data for dashboard context
     *
     * @param string $form_id Form identifier
     * @param array $form_data Form data
     * @return array Processed form data
     */
    private function preprocess_dashboard_form_data(string $form_id, array $form_data): array {
        $form_config = $this->dashboard_forms[$form_id];
        
        // Auto-populate fields based on configuration
        foreach ($form_config['fields'] as $field_name => $field_config) {
            if (isset($field_config['auto_populate'])) {
                $auto_value = $this->get_auto_populate_value($field_config['auto_populate']);
                if ($auto_value && empty($form_data[$field_name])) {
                    $form_data[$field_name] = $auto_value;
                }
            }
            
            if (isset($field_config['default']) && empty($form_data[$field_name])) {
                $form_data[$field_name] = $field_config['default'];
            }
        }
        
        // Add dashboard context
        $form_data['_dashboard_context'] = true;
        $form_data['_form_id'] = $form_id;
        $form_data['_submitted_by'] = get_current_user_id();
        $form_data['_submission_time'] = current_time('mysql');
        
        return apply_filters("hph_dashboard_preprocess_form_data_{$form_id}", $form_data, $form_config);
    }
    
    /**
     * Get auto-populate value
     *
     * @param string $auto_populate_type Auto-populate type
     * @return mixed Auto-populate value
     */
    private function get_auto_populate_value(string $auto_populate_type) {
        $user_id = get_current_user_id();
        
        switch ($auto_populate_type) {
            case 'current_user_agent':
                return get_user_meta($user_id, 'agent_post_id', true);
                
            case 'current_user_office':
                $agent_id = get_user_meta($user_id, 'agent_post_id', true);
                if ($agent_id && function_exists('hph_bridge_get_agent_data')) {
                    $agent_data = hph_bridge_get_agent_data($agent_id);
                    return $agent_data['office_id'] ?? '';
                }
                return '';
                
            case 'current_timestamp':
                return current_time('mysql');
                
            case 'current_date':
                return current_time('Y-m-d');
                
            default:
                return apply_filters("hph_dashboard_auto_populate_{$auto_populate_type}", '', $user_id);
        }
    }
    
    /**
     * Handle form success actions
     *
     * @param array $form_config Form configuration
     * @param mixed $result Form submission result
     * @param string $section_id Current section ID
     * @return array Response data
     */
    private function handle_form_success_action(array $form_config, $result, string $section_id): array {
        $success_action = $form_config['success_action'] ?? 'show_message';
        $response_data = [
            'message' => __('Form submitted successfully', 'happy-place'),
            'data' => $result
        ];
        
        switch ($success_action) {
            case 'refresh_section':
                $response_data['action'] = 'refresh_section';
                $response_data['section_id'] = $section_id;
                break;
                
            case 'redirect':
                $redirect_url = $form_config['redirect_url'] ?? '#';
                $response_data['action'] = 'redirect';
                $response_data['url'] = $redirect_url;
                break;
                
            case 'close_modal':
                $response_data['action'] = 'close_modal';
                break;
                
            case 'show_message':
            default:
                $response_data['action'] = 'show_message';
                break;
        }
        
        return apply_filters('hph_dashboard_form_success_response', $response_data, $form_config, $result);
    }
    
    /**
     * Check if user can access form
     *
     * @param string $form_id Form identifier
     * @return bool True if user can access form
     */
    private function can_access_form(string $form_id): bool {
        if (!isset($this->dashboard_forms[$form_id])) {
            return false;
        }
        
        $form_config = $this->dashboard_forms[$form_id];
        $capabilities = $form_config['capabilities'] ?? ['read'];
        
        foreach ($capabilities as $capability) {
            if (!current_user_can($capability)) {
                return false;
            }
        }
        
        return apply_filters("hph_dashboard_can_access_form_{$form_id}", true, $form_config);
    }
    
    /**
     * Check if user can submit form
     *
     * @param string $form_id Form identifier
     * @return bool True if user can submit form
     */
    private function can_submit_form(string $form_id): bool {
        if (!$this->can_access_form($form_id)) {
            return false;
        }
        
        $form_config = $this->dashboard_forms[$form_id];
        $submit_capabilities = $form_config['submit_capabilities'] ?? ['edit_posts'];
        
        foreach ($submit_capabilities as $capability) {
            if (!current_user_can($capability)) {
                return false;
            }
        }
        
        return apply_filters("hph_dashboard_can_submit_form_{$form_id}", true, $form_config);
    }
    
    /**
     * Locate form template
     *
     * @param string $template Template name
     * @return string|false Template path or false
     */
    private function locate_form_template(string $template) {
        $template_files = [
            "dashboard/forms/{$template}.php",
            "template-parts/dashboard/forms/{$template}.php",
            "forms/{$template}.php"
        ];
        
        // Check theme templates first
        $theme_template = locate_template($template_files);
        if ($theme_template) {
            return $theme_template;
        }
        
        // Check plugin templates
        $plugin_template = plugin_dir_path(__FILE__) . "../templates/forms/{$template}.php";
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
        
        return false;
    }
    
    /**
     * Add form data to dashboard sections
     *
     * @param array $data Section data
     * @param string $section_id Section identifier
     * @return array Modified section data
     */
    public function add_form_data_to_sections(array $data, string $section_id): array {
        // Add available forms for this section
        $section_forms = [];
        
        foreach ($this->dashboard_forms as $form_id => $form_config) {
            if (in_array($section_id, $form_config['sections'] ?? [])) {
                if ($this->can_access_form($form_id)) {
                    $section_forms[$form_id] = [
                        'title' => $form_config['title'],
                        'modal' => $form_config['modal'] ?? false,
                        'size' => $form_config['size'] ?? 'medium',
                        'ajax' => $form_config['ajax'] ?? true
                    ];
                }
            }
        }
        
        $data['forms'] = $section_forms;
        
        return $data;
    }
    
    /**
     * Register form shortcodes
     */
    public function register_form_shortcodes(): void {
        add_shortcode('hph_dashboard_form', [$this, 'dashboard_form_shortcode']);
    }
    
    /**
     * Dashboard form shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Form HTML
     */
    public function dashboard_form_shortcode($atts): string {
        $atts = shortcode_atts([
            'form' => '',
            'inline' => false
        ], $atts);
        
        if (empty($atts['form']) || !isset($this->dashboard_forms[$atts['form']])) {
            return '';
        }
        
        $form_html = $this->render_dashboard_form($atts['form'], ['inline' => $atts['inline']]);
        
        if (is_wp_error($form_html)) {
            return '';
        }
        
        return $form_html;
    }
    
    /**
     * Enqueue dashboard form assets
     */
    public function enqueue_dashboard_form_assets(): void {
        $dashboard_manager = Dashboard_Manager::get_instance();
        
        if (!$dashboard_manager->is_dashboard_request()) {
            return;
        }
        
        // Enqueue dashboard-specific form styles
        wp_enqueue_style(
            'hph-dashboard-forms',
            plugin_dir_url(__FILE__) . '../assets/css/dashboard-forms.css',
            ['hph-forms', 'hph-dashboard-core'],
            HPH_VERSION
        );
        
        // Enqueue dashboard-specific form scripts
        wp_enqueue_script(
            'hph-dashboard-forms',
            plugin_dir_url(__FILE__) . '../assets/js/dashboard-forms.js',
            ['hph-forms', 'hph-dashboard-core'],
            HPH_VERSION,
            true
        );
        
        // Localize dashboard form data
        wp_localize_script('hph-dashboard-forms', 'hph_dashboard_forms', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_dashboard_nonce'),
            'forms' => $this->get_accessible_forms(),
            'messages' => [
                'form_loading' => __('Loading form...', 'happy-place'),
                'form_submitting' => __('Submitting...', 'happy-place'),
                'form_success' => __('Form submitted successfully', 'happy-place'),
                'form_error' => __('An error occurred', 'happy-place'),
                'validation_error' => __('Please correct the errors below', 'happy-place')
            ]
        ]);
    }
    
    /**
     * Get forms accessible to current user
     *
     * @return array Accessible forms
     */
    private function get_accessible_forms(): array {
        $accessible_forms = [];
        
        foreach ($this->dashboard_forms as $form_id => $form_config) {
            if ($this->can_access_form($form_id)) {
                $accessible_forms[$form_id] = [
                    'title' => $form_config['title'],
                    'modal' => $form_config['modal'] ?? false,
                    'size' => $form_config['size'] ?? 'medium',
                    'ajax' => $form_config['ajax'] ?? true,
                    'sections' => $form_config['sections'] ?? []
                ];
            }
        }
        
        return $accessible_forms;
    }
    
    // Quick action handlers
    
    /**
     * Handle duplicate listing action
     */
    private function handle_duplicate_listing(array $data): void {
        $listing_id = intval($data['listing_id'] ?? 0);
        
        if (!$listing_id || !current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permission denied', 'happy-place')]);
        }
        
        // Use listing form handler to duplicate
        $handler = Form_Manager::get_handler('listing');
        if (!$handler || !method_exists($handler, 'duplicate_listing')) {
            wp_send_json_error(['message' => __('Duplicate function not available', 'happy-place')]);
        }
        
        $result = $handler->duplicate_listing($listing_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success([
            'message' => __('Listing duplicated successfully', 'happy-place'),
            'new_listing_id' => $result,
            'action' => 'refresh_section'
        ]);
    }
    
    /**
     * Handle mark sold action
     */
    private function handle_mark_sold(array $data): void {
        $listing_id = intval($data['listing_id'] ?? 0);
        $sale_price = floatval($data['sale_price'] ?? 0);
        
        if (!$listing_id || !current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permission denied', 'happy-place')]);
        }
        
        // Update listing status
        update_field('listing_status', 'sold', $listing_id);
        if ($sale_price > 0) {
            update_field('sale_price', $sale_price, $listing_id);
        }
        update_field('date_sold', current_time('Y-m-d'), $listing_id);
        
        wp_send_json_success([
            'message' => __('Listing marked as sold', 'happy-place'),
            'action' => 'refresh_section'
        ]);
    }
    
    /**
     * Handle schedule showing action
     */
    private function handle_schedule_showing(array $data): void {
        // This would integrate with a showing/calendar system
        wp_send_json_success([
            'message' => __('Showing scheduled', 'happy-place'),
            'action' => 'show_message'
        ]);
    }
    
    /**
     * Handle update status action
     */
    private function handle_update_status(array $data): void {
        $post_id = intval($data['post_id'] ?? 0);
        $status = sanitize_text_field($data['status'] ?? '');
        $post_type = sanitize_text_field($data['post_type'] ?? '');
        
        if (!$post_id || !$status || !current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permission denied', 'happy-place')]);
        }
        
        // Update status based on post type
        switch ($post_type) {
            case 'listing':
                update_field('listing_status', $status, $post_id);
                break;
            case 'agent':
                update_field('agent_status', $status, $post_id);
                break;
            default:
                wp_update_post(['ID' => $post_id, 'post_status' => $status]);
        }
        
        wp_send_json_success([
            'message' => __('Status updated successfully', 'happy-place'),
            'action' => 'refresh_section'
        ]);
    }
    
    /**
     * Get registered dashboard forms
     *
     * @return array Dashboard forms
     */
    public function get_dashboard_forms(): array {
        return $this->dashboard_forms;
    }
    
    /**
     * Register a custom dashboard form
     *
     * @param string $form_id Form identifier
     * @param array $config Form configuration
     */
    public function register_dashboard_form(string $form_id, array $config): void {
        $this->dashboard_forms[$form_id] = $config;
    }
}