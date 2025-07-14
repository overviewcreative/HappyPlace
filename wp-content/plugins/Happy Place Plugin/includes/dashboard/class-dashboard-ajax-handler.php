<?php

/**
 * Dashboard AJAX Handler - Plugin Version
 * 
 * Handles all AJAX requests for dashboard operations, data management,
 * and core platform functionality. This is the main AJAX controller
 * for the Happy Place Real Estate Platform.
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

namespace HappyPlace\Dashboard;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dashboard AJAX Handler Class
 * 
 * Manages:
 * - Dashboard section loading and navigation
 * - Form submissions and data validation
 * - Real-time data updates and statistics
 * - User management and permissions
 * - Integration with plugin data sources
 */
class HPH_Dashboard_Ajax_Handler
{
    /**
     * @var HPH_Dashboard_Ajax_Handler|null Singleton instance
     */
    private static ?self $instance = null;

    /**
     * @var array Dashboard section handlers
     */
    private array $section_handlers = [];

    /**
     * @var array Form handlers
     */
    private array $form_handlers = [];

    /**
     * Get singleton instance
     */
    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Constructor - Initialize AJAX handling
     */
    private function __construct()
    {
        $this->configure_handlers();
        $this->register_ajax_actions();
        $this->setup_plugin_integration();
        $this->ensure_dependencies();
    }

    /**
     * Configure section and form-specific handlers
     */
    private function configure_handlers(): void
    {
        // Dashboard section handlers
        $this->section_handlers = [
            'overview' => 'handle_overview_section',
            'listings' => 'handle_listings_section', 
            'leads' => 'handle_leads_section',
            'open-houses' => 'handle_open_houses_section',
            'performance' => 'handle_performance_section',
            'profile' => 'handle_profile_section',
            'settings' => 'handle_settings_section',
            'cache' => 'handle_cache_section'
        ];

        // Form handlers mapped to their post types
        $this->form_handlers = [
            'listing' => 'save_listing_form',
            'lead' => 'save_lead_form',
            'open_house' => 'save_open_house_form',
            'agent_profile' => 'save_agent_profile_form'
        ];
    }

    /**
     * Register all AJAX actions
     */
    private function register_ajax_actions(): void
    {
        // Core dashboard actions (authenticated users only)
        $auth_actions = [
            'hph_load_dashboard_section' => 'handle_dashboard_section_load',
            'hph_get_dashboard_stats' => 'get_dashboard_statistics',
            'hph_save_listing' => 'save_listing_form',
            'hph_save_lead' => 'save_lead_form',
            'hph_save_open_house' => 'save_open_house_form',
            'hph_save_agent_profile' => 'save_agent_profile_form',
            'hph_delete_listing' => 'delete_listing',
            'hph_delete_lead' => 'delete_lead',
            'hph_delete_open_house' => 'delete_open_house',
            'hph_toggle_listing_status' => 'toggle_listing_status',
            'hph_upload_listing_image' => 'upload_listing_image',
            'hph_save_draft' => 'save_form_draft',
            'hph_load_draft' => 'load_form_draft',
            'hph_clear_cache' => 'clear_cache_section',
            'hph_export_data' => 'export_user_data'
        ];

        foreach ($auth_actions as $action => $method) {
            add_action("wp_ajax_{$action}", [$this, $method]);
        }

        // Public actions (for logged out users too)
        $public_actions = [
            'hph_search_suggestions' => 'search_suggestions',
            'hph_filter_listings' => 'filter_listings',
            'hph_get_map_markers' => 'get_map_markers',
            'hph_contact_agent' => 'handle_contact_agent'
        ];

        foreach ($public_actions as $action => $method) {
            add_action("wp_ajax_{$action}", [$this, $method]);
            add_action("wp_ajax_nopriv_{$action}", [$this, $method]);
        }
    }

    /**
     * Setup integration with plugin data sources
     */
    private function setup_plugin_integration(): void
    {
        // Connect AJAX handler to plugin data managers
        add_filter('hph_get_dashboard_section_data', [$this, 'get_plugin_section_data'], 10, 2);
        add_filter('hph_get_filtered_listings', [$this, 'get_plugin_filtered_listings'], 10, 2);
        add_filter('hph_get_listing_markers', [$this, 'get_plugin_listing_markers'], 10, 2);
        add_filter('hph_save_listing_data', [$this, 'save_plugin_listing_data'], 10, 2);
        add_filter('hph_save_lead_data', [$this, 'save_plugin_lead_data'], 10, 2);
        add_filter('hph_save_open_house_data', [$this, 'save_plugin_open_house_data'], 10, 2);
        add_filter('hph_calculate_dashboard_stats', [$this, 'calculate_plugin_dashboard_stats'], 10, 2);
    }

    /**
     * Ensure required dependencies are loaded
     */
    private function ensure_dependencies(): void
    {
        // Load plugin core classes
        $required_classes = [
            'HappyPlace\\Core\\Post_Types',
            'HappyPlace\\Users\\User_Roles_Manager',
            'HappyPlace\\Utilities\\Data_Validator'
        ];

        foreach ($required_classes as $class) {
            if (!class_exists($class)) {
                error_log("HPH Dashboard: Required class {$class} not found");
            }
        }

        // Load theme helpers if available (for display functions)
        $theme_helpers = [
            get_template_directory() . '/inc/listing-helpers.php',
            get_template_directory() . '/inc/dashboard-helpers.php'
        ];

        foreach ($theme_helpers as $helper_path) {
            if (file_exists($helper_path)) {
                require_once $helper_path;
            }
        }
    }

    // =========================================================================
    // DASHBOARD SECTION LOADING
    // =========================================================================

    /**
     * Handle dashboard section loading
     * Main method called by dashboard navigation
     */
    public function handle_dashboard_section_load(): void
    {
        // Security checks
        if (!is_user_logged_in() || !$this->user_can_access_dashboard()) {
            wp_send_json_error([
                'message' => __('Unauthorized access', 'happy-place'),
                'code' => 'unauthorized'
            ]);
        }

        if (!check_ajax_referer('hph_dashboard_nonce', 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Security check failed', 'happy-place'),
                'code' => 'nonce_failed'
            ]);
        }

        // Get and validate section
        $section = sanitize_key($_POST['section'] ?? '');
        if (empty($section) || !$this->is_valid_section($section)) {
            wp_send_json_error([
                'message' => __('Invalid section requested', 'happy-place'),
                'code' => 'invalid_section'
            ]);
        }

        // Load section content
        try {
            $content = $this->get_dashboard_section_content($section);
            
            if (is_wp_error($content)) {
                wp_send_json_error([
                    'message' => $content->get_error_message(),
                    'code' => $content->get_error_code()
                ]);
            }

            if (empty($content)) {
                wp_send_json_error([
                    'message' => __('Section content could not be loaded.', 'happy-place'),
                    'code' => 'empty_content'
                ]);
            }

            // Success response
            wp_send_json_success([
                'content' => $content,
                'section' => $section,
                'timestamp' => current_time('timestamp'),
                'debug' => defined('WP_DEBUG') && WP_DEBUG ? [
                    'user_id' => get_current_user_id(),
                    'section_requested' => $section,
                    'template_used' => $this->get_section_template_path($section)
                ] : null
            ]);

        } catch (Exception $e) {
            wp_send_json_error([
                'message' => __('An error occurred loading the section.', 'happy-place'),
                'code' => 'exception',
                'debug' => defined('WP_DEBUG') && WP_DEBUG ? $e->getMessage() : null
            ]);
        }
    }

    /**
     * Get dashboard section content by loading appropriate template
     */
    private function get_dashboard_section_content(string $section)
    {
        // Get section data from plugin
        $section_data = $this->get_plugin_section_data([], $section);
        
        // Try multiple template locations in order of preference
        $template_paths = [
            // Theme templates (first priority)
            get_template_directory() . "/template-parts/dashboard/section-{$section}.php",
            get_template_directory() . "/templates/template-parts/dashboard/section-{$section}.php",
            
            // Plugin templates (fallback)
            HPH_PLUGIN_DIR . "templates/dashboard/section-{$section}.php",
            
            // Default fallback
            get_template_directory() . "/template-parts/dashboard/section-default.php"
        ];

        $template_found = false;
        foreach ($template_paths as $template_path) {
            if (file_exists($template_path)) {
                $template_found = true;
                break;
            }
        }

        if (!$template_found) {
            return new WP_Error(
                'template_not_found', 
                sprintf(__('No template found for section: %s', 'happy-place'), $section)
            );
        }

        // Load template with section data
        ob_start();
        
        // Make variables available to template
        $args = [
            'section' => $section,
            'section_data' => $section_data,
            'current_user' => wp_get_current_user(),
            'user_id' => get_current_user_id()
        ];

        // Extract args for template
        extract($args);
        
        include $template_path;
        
        return ob_get_clean();
    }

    /**
     * Check if section is valid
     */
    private function is_valid_section(string $section): bool
    {
        $allowed_sections = array_keys($this->section_handlers);
        
        // Add cache section for administrators
        if (current_user_can('manage_options')) {
            $allowed_sections[] = 'cache';
        }

        return in_array($section, $allowed_sections, true);
    }

    /**
     * Get template path for debugging
     */
    private function get_section_template_path(string $section): string
    {
        $paths = [
            get_template_directory() . "/template-parts/dashboard/section-{$section}.php",
            get_template_directory() . "/templates/template-parts/dashboard/section-{$section}.php",
            HPH_PLUGIN_DIR . "templates/dashboard/section-{$section}.php",
            get_template_directory() . "/template-parts/dashboard/section-default.php"
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return 'not_found';
    }

    // =========================================================================
    // FORM HANDLING
    // =========================================================================

    /**
     * Save listing form
     */
    public function save_listing_form(): void
    {
        if (!$this->verify_form_submission('edit_posts')) {
            return;
        }

        $listing_data = $this->sanitize_listing_data($_POST);
        $validation_errors = $this->validate_listing_data($listing_data);

        if (!empty($validation_errors)) {
            wp_send_json_error([
                'message' => __('Please correct the errors below', 'happy-place'),
                'errors' => $validation_errors
            ]);
        }

        $listing_id = $this->save_plugin_listing_data(null, $listing_data);
        
        if (is_wp_error($listing_id)) {
            wp_send_json_error([
                'message' => $listing_id->get_error_message(),
                'code' => $listing_id->get_error_code()
            ]);
        }

        wp_send_json_success([
            'listing_id' => $listing_id,
            'message' => __('Listing saved successfully', 'happy-place'),
            'redirect_url' => add_query_arg(['section' => 'listings'], $this->get_dashboard_url())
        ]);
    }

    /**
     * Save lead form
     */
    public function save_lead_form(): void
    {
        if (!$this->verify_form_submission('manage_leads')) {
            return;
        }

        $lead_data = $this->sanitize_lead_data($_POST);
        $validation_errors = $this->validate_lead_data($lead_data);

        if (!empty($validation_errors)) {
            wp_send_json_error([
                'message' => __('Please correct the errors below', 'happy-place'),
                'errors' => $validation_errors
            ]);
        }

        $lead_id = $this->save_plugin_lead_data(null, $lead_data);
        
        if (is_wp_error($lead_id)) {
            wp_send_json_error([
                'message' => $lead_id->get_error_message(),
                'code' => $lead_id->get_error_code()
            ]);
        }

        wp_send_json_success([
            'lead_id' => $lead_id,
            'message' => __('Lead saved successfully', 'happy-place'),
            'redirect_url' => add_query_arg(['section' => 'leads'], $this->get_dashboard_url())
        ]);
    }

    /**
     * Save open house form
     */
    public function save_open_house_form(): void
    {
        if (!$this->verify_form_submission('edit_posts')) {
            return;
        }

        $open_house_data = $this->sanitize_open_house_data($_POST);
        $validation_errors = $this->validate_open_house_data($open_house_data);

        if (!empty($validation_errors)) {
            wp_send_json_error([
                'message' => __('Please correct the errors below', 'happy-place'),
                'errors' => $validation_errors
            ]);
        }

        $open_house_id = $this->save_plugin_open_house_data(null, $open_house_data);
        
        if (is_wp_error($open_house_id)) {
            wp_send_json_error([
                'message' => $open_house_id->get_error_message(),
                'code' => $open_house_id->get_error_code()
            ]);
        }

        wp_send_json_success([
            'open_house_id' => $open_house_id,
            'message' => __('Open house scheduled successfully', 'happy-place'),
            'redirect_url' => add_query_arg(['section' => 'open-houses'], $this->get_dashboard_url())
        ]);
    }

    // =========================================================================
    // PUBLIC AJAX ACTIONS (Theme Integration)
    // =========================================================================

    /**
     * Search suggestions for autocomplete
     */
    public function search_suggestions(): void
    {
        if (!check_ajax_referer('hph_search_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'happy-place'));
        }

        $term = sanitize_text_field($_POST['term'] ?? '');
        $suggestions = [];

        if (strlen($term) >= 2) {
            $suggestions = array_merge(
                $this->search_cities($term),
                $this->search_neighborhoods($term),
                $this->search_zip_codes($term)
            );
            $suggestions = array_slice($suggestions, 0, 10);
        }

        wp_send_json_success($suggestions);
    }

    /**
     * Filter listings (for archive pages)
     */
    public function filter_listings(): void
    {
        if (!check_ajax_referer('hph_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'happy-place'));
        }

        $filters = $this->sanitize_listing_filters($_POST);
        $listings = $this->get_plugin_filtered_listings([], $filters);

        wp_send_json_success([
            'listings' => $listings,
            'count' => count($listings),
            'filters_applied' => $filters
        ]);
    }

    /**
     * Get map markers
     */
    public function get_map_markers(): void
    {
        if (!check_ajax_referer('hph_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'happy-place'));
        }

        $filters = $this->sanitize_listing_filters($_POST);
        $markers = $this->get_plugin_listing_markers([], $filters);

        wp_send_json_success([
            'markers' => $markers,
            'count' => count($markers)
        ]);
    }

    /**
     * Handle contact agent
     */
    public function handle_contact_agent(): void
    {
        if (!check_ajax_referer('hph_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'happy-place'));
        }

        $contact_data = $this->sanitize_contact_data($_POST);
        $validation_errors = $this->validate_contact_data($contact_data);

        if (!empty($validation_errors)) {
            wp_send_json_error([
                'message' => __('Please check your information', 'happy-place'),
                'errors' => $validation_errors
            ]);
        }

        $result = $this->send_contact_email($contact_data);
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        do_action('hph_log_contact_attempt', $contact_data);

        wp_send_json_success([
            'message' => __('Your message has been sent successfully!', 'happy-place')
        ]);
    }

    // =========================================================================
    // PLUGIN DATA INTEGRATION METHODS
    // =========================================================================

    /**
     * Get section data from plugin
     */
    public function get_plugin_section_data(array $default, string $section): array
    {
        $user_id = get_current_user_id();
        
        switch ($section) {
            case 'overview':
                return $this->get_overview_data($user_id);
            case 'listings':
                return $this->get_listings_data($user_id);
            case 'leads':
                return $this->get_leads_data($user_id);
            case 'open-houses':
                return $this->get_open_houses_data($user_id);
            case 'performance':
                return $this->get_performance_data($user_id);
            case 'profile':
                return $this->get_profile_data($user_id);
            case 'settings':
                return $this->get_settings_data($user_id);
            default:
                return $default;
        }
    }

    /**
     * Get filtered listings from plugin
     */
    public function get_plugin_filtered_listings(array $default, array $filters): array
    {
        // Integration with plugin's listing manager
        if (class_exists('HappyPlace\\Core\\Post_Types')) {
            return \HappyPlace\Core\Post_Types::get_filtered_listings($filters);
        }
        
        return $this->fallback_get_listings($filters);
    }

    /**
     * Save listing data to plugin
     */
    public function save_plugin_listing_data($default, array $data)
    {
        // Integration with plugin's listing manager
        if (class_exists('HappyPlace\\Core\\Post_Types')) {
            return \HappyPlace\Core\Post_Types::save_listing($data);
        }
        
        return new WP_Error('plugin_not_found', 'Listing plugin not available');
    }

    // =========================================================================
    // UTILITY METHODS
    // =========================================================================

    /**
     * Verify form submission with security checks
     */
    private function verify_form_submission(string $capability): bool
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to perform this action', 'happy-place'));
            return false;
        }

        if (!current_user_can($capability)) {
            wp_send_json_error(__('You do not have permission to perform this action', 'happy-place'));
            return false;
        }

        if (!check_ajax_referer('hph_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'happy-place'));
            return false;
        }

        return true;
    }

    /**
     * Check if user can access dashboard
     */
    private function user_can_access_dashboard(): bool
    {
        return current_user_can('agent') || 
               current_user_can('administrator') || 
               current_user_can('edit_posts');
    }

    /**
     * Get dashboard URL
     */
    private function get_dashboard_url(): string
    {
        // Try to get dashboard page URL
        $dashboard_page = get_page_by_path('agent-dashboard');
        if ($dashboard_page) {
            return get_permalink($dashboard_page->ID);
        }
        
        return home_url('/agent-dashboard/');
    }

    /**
     * Sanitize listing filters
     */
    private function sanitize_listing_filters(array $data): array
    {
        return [
            'location' => sanitize_text_field($data['location'] ?? ''),
            'property_type' => sanitize_text_field($data['property_type'] ?? ''),
            'min_price' => intval($data['min_price'] ?? 0),
            'max_price' => intval($data['max_price'] ?? 0),
            'bedrooms' => intval($data['bedrooms'] ?? 0),
            'bathrooms' => intval($data['bathrooms'] ?? 0),
            'status' => sanitize_text_field($data['status'] ?? 'active'),
            'per_page' => min(intval($data['per_page'] ?? 12), 50),
            'page' => max(intval($data['page'] ?? 1), 1),
            'agent_id' => intval($data['agent_id'] ?? get_current_user_id())
        ];
    }

    /**
     * Sanitize listing data
     */
    private function sanitize_listing_data(array $data): array
    {
        return [
            'title' => sanitize_text_field($data['title'] ?? ''),
            'description' => wp_kses_post($data['description'] ?? ''),
            'price' => floatval($data['price'] ?? 0),
            'address' => sanitize_text_field($data['address'] ?? ''),
            'city' => sanitize_text_field($data['city'] ?? ''),
            'state' => sanitize_text_field($data['state'] ?? ''),
            'zip_code' => sanitize_text_field($data['zip_code'] ?? ''),
            'property_type' => sanitize_text_field($data['property_type'] ?? ''),
            'bedrooms' => intval($data['bedrooms'] ?? 0),
            'bathrooms' => floatval($data['bathrooms'] ?? 0),
            'square_feet' => intval($data['square_feet'] ?? 0),
            'status' => sanitize_text_field($data['status'] ?? 'active'),
            'listing_id' => intval($data['listing_id'] ?? 0),
            'agent_id' => get_current_user_id()
        ];
    }

    /**
     * Validate listing data
     */
    private function validate_listing_data(array $data): array
    {
        $errors = [];

        if (empty($data['title'])) {
            $errors['title'] = __('Title is required', 'happy-place');
        }

        if (empty($data['address'])) {
            $errors['address'] = __('Address is required', 'happy-place');
        }

        if ($data['price'] <= 0) {
            $errors['price'] = __('Valid price is required', 'happy-place');
        }

        if (empty($data['property_type'])) {
            $errors['property_type'] = __('Property type is required', 'happy-place');
        }

        return $errors;
    }

    // Add more sanitization and validation methods as needed...
    // [Additional methods for leads, open houses, contacts, etc.]
    
    // Static method for template compatibility
    public static function get_section_data(string $section): array
    {
        return self::instance()->get_plugin_section_data([], $section);
    }
}

// Initialize the dashboard AJAX handler
HPH_Dashboard_Ajax_Handler::instance();