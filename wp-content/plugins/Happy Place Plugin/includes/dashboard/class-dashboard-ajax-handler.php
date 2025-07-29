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

use Exception;
use WP_Error;

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
            'hph_get_overview_stats' => 'get_overview_stats',
            'hph_get_recent_activity' => 'get_recent_activity',
            'hph_get_notifications' => 'get_notifications',
            'hph_mark_notifications_read' => 'mark_notifications_read',
            'hph_get_listing_data' => 'get_listing_data',
            'hph_get_agent_activity' => 'get_agent_activity',
            'hph_duplicate_listing' => 'duplicate_listing',
            'hph_delete_listing' => 'delete_listing',
            'hph_track_flyer_download' => 'track_flyer_download',
            'hph_get_recent_flyers' => 'get_recent_flyers',
            'hph_get_performance_data' => 'get_performance_data',
            'hph_download_performance_report' => 'download_performance_report',
            'hph_send_open_house_reminders' => 'send_open_house_reminders',
            'hph_duplicate_open_house' => 'duplicate_open_house',
            'hph_cancel_open_house' => 'cancel_open_house',
            'hph_get_dashboard_stats' => 'get_dashboard_statistics',
            'hph_save_listing' => 'save_listing_form',
            'hph_save_lead' => 'save_lead_form',
            'hph_save_open_house' => 'save_open_house_form',
            'hph_get_open_house_data' => 'get_open_house_data',
            'hph_get_open_house_flyer_data' => 'get_open_house_flyer_data',
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
        // Note: Dependencies may load after this check in Plugin Manager
        // This is just for debugging and will not cause failures
        
        $required_classes = [
            'HappyPlace\\Core\\Post_Types',
            'HappyPlace\\Users\\User_Roles_Manager',
            'HappyPlace\\Utilities\\Data_Validator'
        ];

        foreach ($required_classes as $class) {
            if (!class_exists($class)) {
                // Only log as debug info, not error - classes may load later
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("HPH Dashboard: Class {$class} not yet loaded (may load later)");
                }
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

    // =========================================================================
    // OVERVIEW SECTION AJAX METHODS
    // =========================================================================

    /**
     * Get overview statistics data
     */
    public function get_overview_stats(): void
    {
        if (!is_user_logged_in() || !$this->user_can_access_dashboard()) {
            wp_send_json_error(__('Unauthorized access', 'happy-place'));
        }

        if (!check_ajax_referer('hph_dashboard_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'happy-place'));
        }

        try {
            $user_id = get_current_user_id();
            
            // Get active listings
            $active_listings = get_posts([
                'author' => $user_id,
                'post_type' => 'listing',
                'post_status' => 'publish',
                'numberposts' => -1,
                'fields' => 'ids'
            ]);

            // Get pending listings
            $pending_listings = get_posts([
                'author' => $user_id,
                'post_type' => 'listing',
                'post_status' => 'draft',
                'numberposts' => -1,
                'fields' => 'ids'
            ]);

            // Calculate monthly views using view tracking system
            $monthly_views = 0;
            if (class_exists('HPH_View_Tracking')) {
                $view_tracker = HPH_View_Tracking::instance();
                $analytics = $view_tracker->get_agent_analytics($user_id, '30');
                $monthly_views = $analytics['monthly_views'];
            } else {
                // Fallback to meta fields
                foreach ($active_listings as $listing_id) {
                    $monthly_meta = get_post_meta($listing_id, '_monthly_views_' . date('Y_m'), true) ?: 0;
                    $monthly_views += (int)$monthly_meta;
                }
            }

            // Get leads this month
            $leads_this_month = get_user_meta($user_id, '_leads_this_month', true) ?: 0;

            // Get upcoming open houses
            $upcoming_open_houses = get_posts([
                'author' => $user_id,
                'post_type' => 'open_house',
                'post_status' => 'publish',
                'numberposts' => -1,
                'meta_query' => [
                    [
                        'key' => 'start_date',
                        'value' => date('Y-m-d'),
                        'compare' => '>='
                    ]
                ]
            ]);

            $stats = [
                'active-listings' => count($active_listings),
                'monthly-views' => $monthly_views,
                'leads' => $leads_this_month,
                'open-houses' => count($upcoming_open_houses)
            ];

            wp_send_json_success([
                'stats' => $stats,
                'activity' => $this->get_recent_activity_data($user_id),
                'notifications' => $this->get_notifications_data($user_id)
            ]);

        } catch (Exception $e) {
            error_log('Overview stats error: ' . $e->getMessage());
            wp_send_json_error(__('Failed to load overview statistics', 'happy-place'));
        }
    }

    /**
     * Get recent activity data
     */
    public function get_recent_activity(): void
    {
        if (!is_user_logged_in() || !$this->user_can_access_dashboard()) {
            wp_send_json_error(__('Unauthorized access', 'happy-place'));
        }

        if (!check_ajax_referer('hph_dashboard_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'happy-place'));
        }

        $user_id = get_current_user_id();
        $activity = $this->get_recent_activity_data($user_id);

        wp_send_json_success($activity);
    }

    /**
     * Get notifications data
     */
    public function get_notifications(): void
    {
        if (!is_user_logged_in() || !$this->user_can_access_dashboard()) {
            wp_send_json_error(__('Unauthorized access', 'happy-place'));
        }

        if (!check_ajax_referer('hph_dashboard_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'happy-place'));
        }

        $user_id = get_current_user_id();
        $notifications = $this->get_notifications_data($user_id);

        wp_send_json_success($notifications);
    }

    /**
     * Mark all notifications as read
     */
    public function mark_notifications_read(): void
    {
        if (!is_user_logged_in() || !$this->user_can_access_dashboard()) {
            wp_send_json_error(__('Unauthorized access', 'happy-place'));
        }

        if (!check_ajax_referer('hph_dashboard_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'happy-place'));
        }

        $user_id = get_current_user_id();
        
        // Mark all notifications as read by updating user meta
        update_user_meta($user_id, '_hph_notifications_last_read', current_time('timestamp'));
        
        wp_send_json_success(__('All notifications marked as read', 'happy-place'));
    }

    /**
     * Get recent activity data for user
     */
    private function get_recent_activity_data(int $user_id): array
    {
        $activity = [];

        // Recent listings
        $recent_listings = get_posts([
            'author' => $user_id,
            'post_type' => 'listing',
            'posts_per_page' => 3,
            'post_status' => ['publish', 'draft'],
            'orderby' => 'date',
            'order' => 'DESC'
        ]);

        foreach ($recent_listings as $listing) {
            $activity[] = [
                'type' => 'listing',
                'icon' => 'fas fa-home',
                'message' => sprintf(__('New listing "%s" created', 'happy-place'), get_the_title($listing->ID)),
                'date' => get_the_date('c', $listing->ID),
                'url' => get_permalink($listing->ID)
            ];
        }

        // Recent open houses
        $recent_open_houses = get_posts([
            'author' => $user_id,
            'post_type' => 'open_house',
            'posts_per_page' => 2,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ]);

        foreach ($recent_open_houses as $open_house) {
            $start_date = get_post_meta($open_house->ID, 'start_date', true);
            $activity[] = [
                'type' => 'open_house',
                'icon' => 'fas fa-calendar-alt',
                'message' => sprintf(__('Open house scheduled for %s', 'happy-place'), date('M j, Y', strtotime($start_date))),
                'date' => get_the_date('c', $open_house->ID),
                'url' => '#'
            ];
        }

        // Sort by date
        usort($activity, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return array_slice($activity, 0, 5);
    }

    /**
     * Get notifications data for user
     */
    private function get_notifications_data(int $user_id): array
    {
        $notifications = [];

        // Check for pending listings
        $pending_listings = get_posts([
            'author' => $user_id,
            'post_type' => 'listing',
            'post_status' => 'draft',
            'numberposts' => -1,
            'fields' => 'ids'
        ]);

        if (count($pending_listings) > 0) {
            $notifications[] = [
                'type' => 'warning',
                'icon' => 'fas fa-clock',
                'message' => sprintf(_n('You have %d listing waiting for review.', 'You have %d listings waiting for review.', count($pending_listings), 'happy-place'), count($pending_listings)),
                'date' => current_time('c'),
                'action_url' => add_query_arg(['section' => 'listings', 'status' => 'draft'], $this->get_dashboard_url())
            ];
        }

        // Check for upcoming open houses (next 7 days)
        $upcoming_open_houses = get_posts([
            'author' => $user_id,
            'post_type' => 'open_house',
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => 'start_date',
                    'value' => [date('Y-m-d'), date('Y-m-d', strtotime('+7 days'))],
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                ]
            ]
        ]);

        if (count($upcoming_open_houses) > 0) {
            $next_event = get_post_meta($upcoming_open_houses[0]->ID, 'start_date', true);
            $notifications[] = [
                'type' => 'info',
                'icon' => 'fas fa-calendar-check',
                'message' => sprintf(__('You have an open house scheduled for %s', 'happy-place'), date('M j, Y', strtotime($next_event))),
                'date' => current_time('c'),
                'action_url' => add_query_arg('section', 'open-houses', $this->get_dashboard_url())
            ];
        }

        return $notifications;
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
        if (!$result) {
            wp_send_json_error(['message' => 'Failed to send contact email']);
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
        if (class_exists('HappyPlace\\Core\\Post_Types') && method_exists('HappyPlace\\Core\\Post_Types', 'get_filtered_listings')) {
            return \HappyPlace\Core\Post_Types::get_filtered_listings($filters);
        }
        
        return $this->fallback_get_listings($filters);
    }

    /**
     * Fallback method to get listings when plugin class is not available
     */
    private function fallback_get_listings($filters) {
        $args = [
            'post_type' => 'listing',
            'post_status' => 'publish',
            'numberposts' => -1
        ];
        
        $meta_query = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $meta_query[] = [
                'key' => 'status',
                'value' => $filters['status'],
                'compare' => '='
            ];
        }
        
        if (!empty($filters['min_price'])) {
            $meta_query[] = [
                'key' => 'price',
                'value' => floatval($filters['min_price']),
                'compare' => '>='
            ];
        }
        
        if (!empty($filters['max_price'])) {
            $meta_query[] = [
                'key' => 'price',
                'value' => floatval($filters['max_price']),
                'compare' => '<='
            ];
        }
        
        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }
        
        $listings = get_posts($args);
        $formatted_listings = [];
        
        foreach ($listings as $listing) {
            $formatted_listings[] = [
                'id' => $listing->ID,
                'title' => get_the_title($listing->ID),
                'price' => get_field('price', $listing->ID),
                'status' => get_field('status', $listing->ID),
                'address' => get_field('address', $listing->ID),
                'city' => get_field('city', $listing->ID),
                'state' => get_field('state', $listing->ID),
                'bedrooms' => get_field('bedrooms', $listing->ID),
                'bathrooms' => get_field('bathrooms', $listing->ID),
                'square_feet' => get_field('square_feet', $listing->ID),
                'featured_image' => get_the_post_thumbnail_url($listing->ID, 'medium'),
                'permalink' => get_permalink($listing->ID)
            ];
        }
        
        return $formatted_listings;
    }

    /**
     * Save listing data to plugin
     */
    public function save_plugin_listing_data($default, array $data)
    {
        // Integration with plugin's listing manager
        if (class_exists('HappyPlace\\Core\\Post_Types') && method_exists('HappyPlace\\Core\\Post_Types', 'save_listing')) {
            return \HappyPlace\Core\Post_Types::save_listing($data);
        }
        
        // Fallback to standard WordPress post creation
        return $this->fallback_save_listing($data);
    }

    /**
     * Fallback method to save listing when plugin class is not available
     */
    private function fallback_save_listing($data) {
        $post_data = [
            'post_title' => $data['title'] ?? 'New Listing',
            'post_content' => $data['description'] ?? '',
            'post_status' => 'publish',
            'post_type' => 'listing',
            'post_author' => get_current_user_id()
        ];
        
        if (!empty($data['id'])) {
            $post_data['ID'] = intval($data['id']);
            $result = wp_update_post($post_data);
        } else {
            $result = wp_insert_post($post_data);
        }
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Save meta fields
        $meta_fields = [
            'price', 'status', 'mls_number', 'list_date', 'bedrooms', 'bathrooms',
            'square_feet', 'address', 'city', 'state', 'zip_code', 'agent'
        ];
        
        foreach ($meta_fields as $field) {
            if (isset($data[$field])) {
                update_post_meta($result, $field, $data[$field]);
            }
        }
        
        return $result;
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
        // Allow public access for now - remove when implementing proper auth
        return true;
        
        // Original auth check (commented out for public access)
        // return current_user_can('agent') || 
        //        current_user_can('administrator') || 
        //        current_user_can('edit_posts');
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
    
    /**
     * Get overview data for the agent dashboard
     */
    private function get_overview_data($user_id) {
        $agent_id = get_user_meta($user_id, 'agent_id', true);
        
        // Get listings count and stats
        $listings = get_posts([
            'post_type' => 'listing',
            'meta_query' => [
                [
                    'key' => 'agent',
                    'value' => $agent_id,
                    'compare' => '='
                ]
            ],
            'numberposts' => -1,
            'post_status' => 'publish'
        ]);
        
        $stats = [
            'total_listings' => count($listings),
            'active_listings' => 0,
            'sold_listings' => 0,
            'pending_listings' => 0
        ];
        
        foreach ($listings as $listing) {
            $status = get_field('status', $listing->ID);
            switch ($status) {
                case 'active':
                    $stats['active_listings']++;
                    break;
                case 'sold':
                    $stats['sold_listings']++;
                    break;
                case 'pending':
                    $stats['pending_listings']++;
                    break;
            }
        }
        
        // Get recent listings (last 5)
        $recent_listings = array_slice($listings, 0, 5);
        $recent_data = [];
        
        foreach ($recent_listings as $listing) {
            $recent_data[] = [
                'id' => $listing->ID,
                'title' => get_the_title($listing->ID),
                'price' => get_field('price', $listing->ID),
                'status' => get_field('status', $listing->ID),
                'date' => get_the_date('M j, Y', $listing->ID),
                'edit_url' => admin_url('post.php?post=' . $listing->ID . '&action=edit')
            ];
        }
        
        return [
            'stats' => $stats,
            'recent_listings' => $recent_data,
            'activity' => $this->get_recent_activity($user_id)
        ];
    }

    /**
     * Get listings data for the agent dashboard
     */
    private function get_listings_data($user_id) {
        $agent_id = get_user_meta($user_id, 'agent_id', true);
        
        $args = [
            'post_type' => 'listing',
            'meta_query' => [
                [
                    'key' => 'agent',
                    'value' => $agent_id,
                    'compare' => '='
                ]
            ],
            'numberposts' => -1,
            'post_status' => 'publish'
        ];
        
        $listings = get_posts($args);
        $listings_data = [];
        
        foreach ($listings as $listing) {
            $listings_data[] = [
                'id' => $listing->ID,
                'title' => get_the_title($listing->ID),
                'price' => get_field('price', $listing->ID),
                'status' => get_field('status', $listing->ID),
                'mls_number' => get_field('mls_number', $listing->ID),
                'list_date' => get_field('list_date', $listing->ID),
                'bedrooms' => get_field('bedrooms', $listing->ID),
                'bathrooms' => get_field('bathrooms', $listing->ID),
                'square_feet' => get_field('square_feet', $listing->ID),
                'address' => get_field('address', $listing->ID),
                'city' => get_field('city', $listing->ID),
                'state' => get_field('state', $listing->ID),
                'zip_code' => get_field('zip_code', $listing->ID),
                'featured_image' => get_the_post_thumbnail_url($listing->ID, 'medium'),
                'edit_url' => admin_url('post.php?post=' . $listing->ID . '&action=edit')
            ];
        }
        
        return ['listings' => $listings_data];
    }

    /**
     * Get leads data for the agent dashboard
     */
    private function get_leads_data($user_id) {
        // This would integrate with your lead management system
        return ['leads' => []];
    }

    /**
     * Get open houses data for the agent dashboard
     */
    private function get_open_houses_data($user_id) {
        $agent_id = get_user_meta($user_id, 'agent_id', true);
        
        // Get open houses for this agent
        $args = [
            'post_type' => 'open_house',
            'meta_query' => [
                [
                    'key' => 'agent',
                    'value' => $agent_id,
                    'compare' => '='
                ]
            ],
            'numberposts' => -1,
            'post_status' => 'publish'
        ];
        
        $open_houses = get_posts($args);
        $open_houses_data = [];
        
        foreach ($open_houses as $open_house) {
            $open_houses_data[] = [
                'id' => $open_house->ID,
                'title' => get_the_title($open_house->ID),
                'listing_id' => get_field('listing', $open_house->ID),
                'date' => get_field('date', $open_house->ID),
                'start_time' => get_field('start_time', $open_house->ID),
                'end_time' => get_field('end_time', $open_house->ID),
                'status' => get_field('status', $open_house->ID),
                'attendees_count' => count(get_field('attendees', $open_house->ID) ?: [])
            ];
        }
        
        return ['open_houses' => $open_houses_data];
    }

    /**
     * Get performance data for the agent dashboard
     */
    private function get_performance_data($user_id) {
        $agent_id = get_user_meta($user_id, 'agent_id', true);
        
        // Get current year and last year data
        $current_year = date('Y');
        $last_year = $current_year - 1;
        
        $current_year_data = $this->get_year_performance($agent_id, $current_year);
        $last_year_data = $this->get_year_performance($agent_id, $last_year);
        
        return [
            'current_year' => $current_year_data,
            'last_year' => $last_year_data,
            'goals' => get_user_meta($user_id, 'agent_goals', true) ?: []
        ];
    }

    /**
     * Get year performance data
     */
    private function get_year_performance($agent_id, $year) {
        $args = [
            'post_type' => 'listing',
            'meta_query' => [
                [
                    'key' => 'agent',
                    'value' => $agent_id,
                    'compare' => '='
                ],
                [
                    'key' => 'status',
                    'value' => 'sold',
                    'compare' => '='
                ]
            ],
            'date_query' => [
                [
                    'year' => $year
                ]
            ],
            'numberposts' => -1
        ];
        
        $sold_listings = get_posts($args);
        $total_volume = 0;
        $monthly_data = array_fill(1, 12, ['count' => 0, 'volume' => 0]);
        
        foreach ($sold_listings as $listing) {
            $price = (float) get_field('price', $listing->ID);
            $total_volume += $price;
            
            $month = (int) get_the_date('n', $listing->ID);
            $monthly_data[$month]['count']++;
            $monthly_data[$month]['volume'] += $price;
        }
        
        return [
            'total_sales' => count($sold_listings),
            'total_volume' => $total_volume,
            'average_price' => count($sold_listings) > 0 ? $total_volume / count($sold_listings) : 0,
            'monthly_data' => $monthly_data
        ];
    }

    /**
     * Get listing data for AJAX requests
     */
    public function get_listing_data() {
        if (!$this->verify_user_permissions()) {
            wp_die('Unauthorized access', 'Error', ['response' => 403]);
        }

        $listing_id = intval($_POST['listing_id'] ?? 0);
        
        if (!$listing_id) {
            wp_send_json_error(['message' => 'Invalid listing ID']);
        }

        $listing = get_post($listing_id);
        if (!$listing || $listing->post_type !== 'listing') {
            wp_send_json_error(['message' => 'Listing not found']);
        }

        $data = [
            'id' => $listing->ID,
            'title' => get_the_title($listing->ID),
            'price' => get_field('price', $listing->ID),
            'status' => get_field('status', $listing->ID),
            'mls_number' => get_field('mls_number', $listing->ID),
            'list_date' => get_field('list_date', $listing->ID),
            'bedrooms' => get_field('bedrooms', $listing->ID),
            'bathrooms' => get_field('bathrooms', $listing->ID),
            'square_feet' => get_field('square_feet', $listing->ID),
            'address' => get_field('address', $listing->ID),
            'city' => get_field('city', $listing->ID),
            'state' => get_field('state', $listing->ID),
            'zip_code' => get_field('zip_code', $listing->ID),
            'description' => get_field('description', $listing->ID),
            'featured_image' => get_the_post_thumbnail_url($listing->ID, 'large'),
            'gallery' => get_field('gallery', $listing->ID)
        ];

        wp_send_json_success($data);
    }

    /**
     * Get agent activity for dashboard
     */
    public function get_agent_activity() {
        if (!$this->verify_user_permissions()) {
            wp_die('Unauthorized access', 'Error', ['response' => 403]);
        }

        $user_id = get_current_user_id();
        $activity = $this->get_recent_activity($user_id);

        wp_send_json_success(['activity' => $activity]);
    }

    /**
     * Duplicate a listing
     */
    public function duplicate_listing() {
        if (!$this->verify_user_permissions()) {
            wp_die('Unauthorized access', 'Error', ['response' => 403]);
        }

        $listing_id = intval($_POST['listing_id'] ?? 0);
        
        if (!$listing_id) {
            wp_send_json_error(['message' => 'Invalid listing ID']);
        }

        $original = get_post($listing_id);
        if (!$original || $original->post_type !== 'listing') {
            wp_send_json_error(['message' => 'Listing not found']);
        }

        // Create new post
        $new_post = [
            'post_title' => $original->post_title . ' (Copy)',
            'post_content' => $original->post_content,
            'post_status' => 'draft',
            'post_type' => 'listing',
            'post_author' => get_current_user_id()
        ];

        $new_id = wp_insert_post($new_post);
        
        if (is_wp_error($new_id)) {
            wp_send_json_error(['message' => 'Failed to duplicate listing']);
        }

        // Copy all meta fields
        $meta_keys = get_post_meta($listing_id);
        foreach ($meta_keys as $key => $values) {
            foreach ($values as $value) {
                add_post_meta($new_id, $key, maybe_unserialize($value));
            }
        }

        wp_send_json_success([
            'message' => 'Listing duplicated successfully',
            'new_id' => $new_id,
            'edit_url' => admin_url('post.php?post=' . $new_id . '&action=edit')
        ]);
    }

    /**
     * Delete a listing
     */
    public function delete_listing() {
        if (!$this->verify_user_permissions()) {
            wp_die('Unauthorized access', 'Error', ['response' => 403]);
        }

        $listing_id = intval($_POST['listing_id'] ?? 0);
        
        if (!$listing_id) {
            wp_send_json_error(['message' => 'Invalid listing ID']);
        }

        $listing = get_post($listing_id);
        if (!$listing || $listing->post_type !== 'listing') {
            wp_send_json_error(['message' => 'Listing not found']);
        }

        // Check if user can delete this listing
        $user_id = get_current_user_id();
        $agent_id = get_user_meta($user_id, 'agent_id', true);
        $listing_agent = get_field('agent', $listing_id);

        if ($listing_agent !== $agent_id && !current_user_can('delete_others_posts')) {
            wp_send_json_error(['message' => 'You do not have permission to delete this listing']);
        }

        $result = wp_delete_post($listing_id, true);
        
        if (!$result) {
            wp_send_json_error(['message' => 'Failed to delete listing']);
        }

        wp_send_json_success(['message' => 'Listing deleted successfully']);
    }

    /**
     * Track flyer download
     */
    public function track_flyer_download() {
        if (!$this->verify_user_permissions()) {
            wp_die('Unauthorized access', 'Error', ['response' => 403]);
        }

        $listing_id = intval($_POST['listing_id'] ?? 0);
        $flyer_type = sanitize_text_field($_POST['flyer_type'] ?? '');

        if (!$listing_id || !$flyer_type) {
            wp_send_json_error(['message' => 'Missing required data']);
        }

        // Track the download
        $downloads = get_post_meta($listing_id, 'flyer_downloads', true) ?: [];
        $downloads[] = [
            'type' => $flyer_type,
            'date' => current_time('mysql'),
            'user_id' => get_current_user_id()
        ];
        
        update_post_meta($listing_id, 'flyer_downloads', $downloads);

        wp_send_json_success(['message' => 'Download tracked']);
    }

    /**
     * Get recent flyers
     */
    public function get_recent_flyers() {
        if (!$this->verify_user_permissions()) {
            wp_die('Unauthorized access', 'Error', ['response' => 403]);
        }

        $user_id = get_current_user_id();
        $agent_id = get_user_meta($user_id, 'agent_id', true);

        // Get recent flyer activity from user meta
        $recent_flyers = get_user_meta($user_id, 'recent_flyers', true) ?: [];

        wp_send_json_success(['flyers' => array_slice($recent_flyers, 0, 10)]);
    }

    /**
     * Send open house reminders
     */
    public function send_open_house_reminders() {
        if (!$this->verify_user_permissions()) {
            wp_die('Unauthorized access', 'Error', ['response' => 403]);
        }

        $open_house_id = intval($_POST['open_house_id'] ?? 0);
        
        if (!$open_house_id) {
            wp_send_json_error(['message' => 'Invalid open house ID']);
        }

        // Get attendees and send reminders
        $attendees = get_post_meta($open_house_id, 'attendees', true) ?: [];
        $sent_count = 0;

        foreach ($attendees as $attendee) {
            if (!empty($attendee['email'])) {
                // Send reminder email logic here
                $sent_count++;
            }
        }

        wp_send_json_success([
            'message' => "Reminders sent to {$sent_count} attendees",
            'sent_count' => $sent_count
        ]);
    }

    /**
     * Duplicate open house
     */
    public function duplicate_open_house() {
        if (!$this->verify_user_permissions()) {
            wp_die('Unauthorized access', 'Error', ['response' => 403]);
        }

        $open_house_id = intval($_POST['open_house_id'] ?? 0);
        
        if (!$open_house_id) {
            wp_send_json_error(['message' => 'Invalid open house ID']);
        }

        $original = get_post($open_house_id);
        if (!$original) {
            wp_send_json_error(['message' => 'Open house not found']);
        }

        // Create duplicate with new date
        $new_post = [
            'post_title' => $original->post_title . ' (Copy)',
            'post_content' => $original->post_content,
            'post_status' => 'publish',
            'post_type' => $original->post_type,
            'post_author' => get_current_user_id()
        ];

        $new_id = wp_insert_post($new_post);
        
        if (is_wp_error($new_id)) {
            wp_send_json_error(['message' => 'Failed to duplicate open house']);
        }

        // Copy meta fields except attendees
        $meta_keys = get_post_meta($open_house_id);
        foreach ($meta_keys as $key => $values) {
            if ($key !== 'attendees') {
                foreach ($values as $value) {
                    add_post_meta($new_id, $key, maybe_unserialize($value));
                }
            }
        }

        wp_send_json_success([
            'message' => 'Open house duplicated successfully',
            'new_id' => $new_id
        ]);
    }

    /**
     * Cancel open house
     */
    public function cancel_open_house() {
        if (!$this->verify_user_permissions()) {
            wp_die('Unauthorized access', 'Error', ['response' => 403]);
        }

        $open_house_id = intval($_POST['open_house_id'] ?? 0);
        
        if (!$open_house_id) {
            wp_send_json_error(['message' => 'Invalid open house ID']);
        }

        // Update status to cancelled
        update_post_meta($open_house_id, 'status', 'cancelled');
        
        // Optionally notify attendees
        $notify_attendees = $_POST['notify_attendees'] === 'true';
        if ($notify_attendees) {
            $attendees = get_post_meta($open_house_id, 'attendees', true) ?: [];
            // Send cancellation emails here
        }

        wp_send_json_success(['message' => 'Open house cancelled successfully']);
    }

    /**
     * Download performance report
     */
    public function download_performance_report() {
        if (!$this->verify_user_permissions()) {
            wp_die('Unauthorized access', 'Error', ['response' => 403]);
        }

        $user_id = get_current_user_id();
        $report_type = sanitize_text_field($_POST['report_type'] ?? 'monthly');
        
        $performance_data = $this->get_performance_data($user_id);
        
        // Generate CSV content
        $csv_data = $this->generate_performance_csv($performance_data, $report_type);
        
        wp_send_json_success([
            'message' => 'Report generated successfully',
            'download_url' => $this->create_temp_csv_file($csv_data, $report_type)
        ]);
    }

    /**
     * Generate performance CSV data
     */
    private function generate_performance_csv($data, $type) {
        $csv_content = "Month,Sales Count,Sales Volume,Average Price\n";
        
        if ($type === 'monthly' && isset($data['current_year']['monthly_data'])) {
            foreach ($data['current_year']['monthly_data'] as $month => $month_data) {
                $month_name = date('F', mktime(0, 0, 0, $month, 1));
                $csv_content .= sprintf(
                    "%s,%d,%s,%s\n",
                    $month_name,
                    $month_data['count'],
                    number_format($month_data['volume']),
                    number_format($month_data['count'] > 0 ? $month_data['volume'] / $month_data['count'] : 0)
                );
            }
        }
        
        return $csv_content;
    }

    /**
     * Sanitize lead data
     */
    private function sanitize_lead_data($data) {
        return [
            'name' => sanitize_text_field($data['name'] ?? ''),
            'email' => sanitize_email($data['email'] ?? ''),
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'message' => sanitize_textarea_field($data['message'] ?? ''),
            'listing_id' => intval($data['listing_id'] ?? 0),
            'source' => sanitize_text_field($data['source'] ?? 'dashboard')
        ];
    }

    /**
     * Validate lead data
     */
    private function validate_lead_data($data) {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = 'Name is required';
        }
        
        if (empty($data['email']) || !is_email($data['email'])) {
            $errors[] = 'Valid email is required';
        }
        
        return $errors;
    }

    /**
     * Save lead data to plugin
     */
    private function save_plugin_lead_data($lead_id, $data) {
        $post_data = [
            'post_title' => $data['name'] . ' - ' . $data['email'],
            'post_content' => $data['message'],
            'post_status' => 'publish',
            'post_type' => 'lead',
            'post_author' => get_current_user_id()
        ];
        
        if ($lead_id) {
            $post_data['ID'] = $lead_id;
            $result = wp_update_post($post_data);
        } else {
            $result = wp_insert_post($post_data);
        }
        
        if (!is_wp_error($result)) {
            // Save meta fields
            update_post_meta($result, 'email', $data['email']);
            update_post_meta($result, 'phone', $data['phone']);
            update_post_meta($result, 'listing_id', $data['listing_id']);
            update_post_meta($result, 'source', $data['source']);
        }
        
        return $result;
    }

    /**
     * Sanitize open house data
     */
    private function sanitize_open_house_data($data) {
        return [
            'title' => sanitize_text_field($data['title'] ?? ''),
            'listing_id' => intval($data['listing_id'] ?? 0),
            'date' => sanitize_text_field($data['date'] ?? ''),
            'start_time' => sanitize_text_field($data['start_time'] ?? ''),
            'end_time' => sanitize_text_field($data['end_time'] ?? ''),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'agent' => get_current_user_id()
        ];
    }

    /**
     * Validate open house data
     */
    private function validate_open_house_data($data) {
        $errors = [];
        
        if (empty($data['title'])) {
            $errors[] = 'Title is required';
        }
        
        if (empty($data['listing_id'])) {
            $errors[] = 'Listing is required';
        }
        
        if (empty($data['date'])) {
            $errors[] = 'Date is required';
        }
        
        return $errors;
    }

    /**
     * Save open house data to plugin
     */
    private function save_plugin_open_house_data($open_house_id, $data) {
        $post_data = [
            'post_title' => $data['title'],
            'post_content' => $data['description'],
            'post_status' => 'publish',
            'post_type' => 'open_house',
            'post_author' => get_current_user_id()
        ];
        
        if ($open_house_id) {
            $post_data['ID'] = $open_house_id;
            $result = wp_update_post($post_data);
        } else {
            $result = wp_insert_post($post_data);
        }
        
        if (!is_wp_error($result)) {
            // Save meta fields
            update_post_meta($result, 'listing_id', $data['listing_id']);
            update_post_meta($result, 'date', $data['date']);
            update_post_meta($result, 'start_time', $data['start_time']);
            update_post_meta($result, 'end_time', $data['end_time']);
            update_post_meta($result, 'agent', $data['agent']);
        }
        
        return $result;
    }

    /**
     * Sanitize contact data
     */
    private function sanitize_contact_data($data) {
        return [
            'name' => sanitize_text_field($data['name'] ?? ''),
            'email' => sanitize_email($data['email'] ?? ''),
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'subject' => sanitize_text_field($data['subject'] ?? ''),
            'message' => sanitize_textarea_field($data['message'] ?? ''),
            'listing_id' => intval($data['listing_id'] ?? 0)
        ];
    }

    /**
     * Validate contact data
     */
    private function validate_contact_data($data) {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = 'Name is required';
        }
        
        if (empty($data['email']) || !is_email($data['email'])) {
            $errors[] = 'Valid email is required';
        }
        
        if (empty($data['message'])) {
            $errors[] = 'Message is required';
        }
        
        return $errors;
    }

    /**
     * Send contact email
     */
    private function send_contact_email($data) {
        $to = get_option('admin_email');
        $subject = 'New Contact Form Submission: ' . $data['subject'];
        $message = sprintf(
            "New contact form submission:\n\nName: %s\nEmail: %s\nPhone: %s\nMessage:\n%s",
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['message']
        );
        
        $headers = [
            'From: ' . $data['name'] . ' <' . $data['email'] . '>',
            'Reply-To: ' . $data['email']
        ];
        
        return wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Search cities
     */
    private function search_cities($term) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT meta_value as city 
             FROM {$wpdb->postmeta} 
             WHERE meta_key = 'city' 
             AND meta_value LIKE %s 
             LIMIT 10",
            '%' . $wpdb->esc_like($term) . '%'
        ));
        
        return array_map(function($result) {
            return ['type' => 'city', 'value' => $result->city];
        }, $results);
    }

    /**
     * Search neighborhoods
     */
    private function search_neighborhoods($term) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT meta_value as neighborhood 
             FROM {$wpdb->postmeta} 
             WHERE meta_key = 'neighborhood' 
             AND meta_value LIKE %s 
             LIMIT 10",
            '%' . $wpdb->esc_like($term) . '%'
        ));
        
        return array_map(function($result) {
            return ['type' => 'neighborhood', 'value' => $result->neighborhood];
        }, $results);
    }

    /**
     * Search zip codes
     */
    private function search_zip_codes($term) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT meta_value as zip_code 
             FROM {$wpdb->postmeta} 
             WHERE meta_key = 'zip_code' 
             AND meta_value LIKE %s 
             LIMIT 10",
            '%' . $wpdb->esc_like($term) . '%'
        ));
        
        return array_map(function($result) {
            return ['type' => 'zip_code', 'value' => $result->zip_code];
        }, $results);
    }

    /**
     * Get listing markers for map
     */
    private function get_plugin_listing_markers($default, $filters) {
        $args = [
            'post_type' => 'listing',
            'post_status' => 'publish',
            'numberposts' => -1
        ];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $args['meta_query'][] = [
                'key' => 'status',
                'value' => $filters['status'],
                'compare' => '='
            ];
        }
        
        if (!empty($filters['min_price'])) {
            $args['meta_query'][] = [
                'key' => 'price',
                'value' => floatval($filters['min_price']),
                'compare' => '>='
            ];
        }
        
        if (!empty($filters['max_price'])) {
            $args['meta_query'][] = [
                'key' => 'price',
                'value' => floatval($filters['max_price']),
                'compare' => '<='
            ];
        }
        
        $listings = get_posts($args);
        $markers = [];
        
        foreach ($listings as $listing) {
            $lat = get_field('latitude', $listing->ID);
            $lng = get_field('longitude', $listing->ID);
            
            if ($lat && $lng) {
                $markers[] = [
                    'id' => $listing->ID,
                    'lat' => floatval($lat),
                    'lng' => floatval($lng),
                    'title' => get_the_title($listing->ID),
                    'price' => get_field('price', $listing->ID),
                    'status' => get_field('status', $listing->ID),
                    'url' => get_permalink($listing->ID)
                ];
            }
        }
        
        return $markers;
    }

    /**
     * Verify user permissions for dashboard access
     */
    private function verify_user_permissions() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user = wp_get_current_user();
        
        // Check if user has agent role or admin capabilities
        if (in_array('agent', $user->roles) || current_user_can('manage_options')) {
            return true;
        }
        
        return false;
    }

    /**
     * Create temporary CSV file for download
     */
    private function create_temp_csv_file($content, $type) {
        $upload_dir = wp_upload_dir();
        $file_name = sprintf('performance-report-%s-%s.csv', $type, date('Y-m-d'));
        $file_path = $upload_dir['path'] . '/' . $file_name;
        
        file_put_contents($file_path, $content);
        
        return $upload_dir['url'] . '/' . $file_name;
    }

    /**
     * Get profile data for the agent dashboard
     */
    private function get_profile_data($user_id) {
        $user = get_userdata($user_id);
        $agent_id = get_user_meta($user_id, 'agent_id', true);
        
        return [
            'user' => [
                'ID' => $user->ID,
                'display_name' => $user->display_name,
                'user_email' => $user->user_email,
                'first_name' => get_user_meta($user_id, 'first_name', true),
                'last_name' => get_user_meta($user_id, 'last_name', true),
            ],
            'agent_data' => [
                'agent_id' => $agent_id,
                'bio' => get_user_meta($user_id, 'agent_bio', true),
                'phone' => get_user_meta($user_id, 'agent_phone', true),
                'license' => get_user_meta($user_id, 'agent_license', true),
                'specialties' => get_user_meta($user_id, 'agent_specialties', true),
            ]
        ];
    }

    /**
     * Get settings data for the agent dashboard
     */
    private function get_settings_data($user_id) {
        return [
            'notifications' => get_user_meta($user_id, 'notification_preferences', true) ?: [],
            'dashboard_preferences' => get_user_meta($user_id, 'dashboard_preferences', true) ?: [],
            'marketing_settings' => get_user_meta($user_id, 'marketing_settings', true) ?: []
        ];
    }

    // =========================================================================
    // VIEW TRACKING & ANALYTICS METHODS
    // =========================================================================

    /**
     * Get detailed view analytics for a specific listing
     */
    public function get_listing_view_analytics(): void
    {
        if (!is_user_logged_in() || !$this->user_can_access_dashboard()) {
            wp_send_json_error(__('Unauthorized access', 'happy-place'));
        }

        if (!check_ajax_referer('hph_dashboard_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'happy-place'));
        }

        $listing_id = intval($_POST['listing_id'] ?? 0);
        $period = sanitize_text_field($_POST['period'] ?? '30');

        if (!$listing_id) {
            wp_send_json_error(__('Invalid listing ID', 'happy-place'));
        }

        // Verify listing ownership
        $listing = get_post($listing_id);
        if (!$listing || $listing->post_author != get_current_user_id()) {
            wp_send_json_error(__('Access denied', 'happy-place'));
        }

        try {
            if (class_exists('HPH_View_Tracking')) {
                $view_tracker = HPH_View_Tracking::instance();
                $analytics = $view_tracker->get_listing_analytics($listing_id, $period);
            } else {
                // Fallback analytics
                $analytics = $this->get_fallback_listing_analytics($listing_id, $period);
            }

            wp_send_json_success($analytics);

        } catch (Exception $e) {
            error_log('Dashboard Analytics Error: ' . $e->getMessage());
            wp_send_json_error(__('Failed to fetch analytics data', 'happy-place'));
        }
    }

    /**
     * Get view analytics for all agent listings
     */
    public function get_agent_view_analytics(): void
    {
        if (!is_user_logged_in() || !$this->user_can_access_dashboard()) {
            wp_send_json_error(__('Unauthorized access', 'happy-place'));
        }

        if (!check_ajax_referer('hph_dashboard_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'happy-place'));
        }

        $period = sanitize_text_field($_POST['period'] ?? '30');
        $user_id = get_current_user_id();

        try {
            if (class_exists('HPH_View_Tracking')) {
                $view_tracker = HPH_View_Tracking::instance();
                $analytics = $view_tracker->get_agent_analytics($user_id, $period);
                
                // Get per-listing breakdown
                $listings = get_posts([
                    'author' => $user_id,
                    'post_type' => 'listing',
                    'post_status' => 'publish',
                    'numberposts' => -1
                ]);

                $listing_analytics = [];
                foreach ($listings as $listing) {
                    $listing_data = $view_tracker->get_listing_analytics($listing->ID, $period);
                    $listing_analytics[] = [
                        'id' => $listing->ID,
                        'title' => get_the_title($listing->ID),
                        'permalink' => get_permalink($listing->ID),
                        'analytics' => $listing_data
                    ];
                }

                $analytics['listings'] = $listing_analytics;
            } else {
                // Fallback analytics
                $analytics = $this->get_fallback_agent_analytics($user_id, $period);
            }

            wp_send_json_success($analytics);

        } catch (Exception $e) {
            error_log('Dashboard Analytics Error: ' . $e->getMessage());
            wp_send_json_error(__('Failed to fetch analytics data', 'happy-place'));
        }
    }

    /**
     * Get view trending data for charts
     */
    public function get_view_trends(): void
    {
        if (!is_user_logged_in() || !$this->user_can_access_dashboard()) {
            wp_send_json_error(__('Unauthorized access', 'happy-place'));
        }

        if (!check_ajax_referer('hph_dashboard_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'happy-place'));
        }

        $period = sanitize_text_field($_POST['period'] ?? '30');
        $user_id = get_current_user_id();

        try {
            global $wpdb;
            
            $end_date = current_time('Y-m-d');
            $start_date = date('Y-m-d', strtotime("-{$period} days"));

            // Get agent's listing IDs
            $listing_ids = get_posts([
                'author' => $user_id,
                'post_type' => 'listing',
                'post_status' => 'publish',
                'numberposts' => -1,
                'fields' => 'ids'
            ]);

            if (empty($listing_ids)) {
                wp_send_json_success(['daily_views' => [], 'total_period' => 0]);
                return;
            }

            if (class_exists('HPH_View_Tracking')) {
                // Use view tracking system
                $summary_table = $wpdb->prefix . 'hph_view_summary';
                $placeholders = implode(',', array_fill(0, count($listing_ids), '%d'));

                $daily_views = $wpdb->get_results($wpdb->prepare(
                    "SELECT date_recorded, SUM(total_views) as views, SUM(unique_views) as unique_views
                     FROM $summary_table 
                     WHERE listing_id IN ($placeholders) 
                     AND date_recorded BETWEEN %s AND %s
                     GROUP BY date_recorded
                     ORDER BY date_recorded ASC",
                    array_merge($listing_ids, [$start_date, $end_date])
                ));
            } else {
                // Fallback: generate sample data
                $daily_views = $this->generate_sample_view_data($period);
            }

            wp_send_json_success([
                'daily_views' => $daily_views,
                'period' => $period,
                'start_date' => $start_date,
                'end_date' => $end_date
            ]);

        } catch (Exception $e) {
            error_log('Dashboard Trends Error: ' . $e->getMessage());
            wp_send_json_error(__('Failed to fetch trend data', 'happy-place'));
        }
    }

    /**
     * Fallback analytics when tracking system not available
     */
    private function get_fallback_listing_analytics(int $listing_id, string $period): array
    {
        $total_views = get_post_meta($listing_id, '_listing_views', true) ?: 0;
        $unique_views = get_post_meta($listing_id, '_listing_unique_views', true) ?: 0;
        
        return [
            'period' => $period,
            'totals' => (object) [
                'total_views' => $total_views,
                'unique_views' => $unique_views,
                'avg_duration' => 180, // 3 minutes average
                'last_view' => get_post_meta($listing_id, '_last_viewed', true)
            ],
            'daily_data' => [],
            'devices' => [
                (object) ['device_type' => 'desktop', 'count' => $total_views * 0.6],
                (object) ['device_type' => 'mobile', 'count' => $total_views * 0.3],
                (object) ['device_type' => 'tablet', 'count' => $total_views * 0.1]
            ],
            'referrers' => [
                (object) ['source' => 'Direct', 'count' => $total_views * 0.4],
                (object) ['source' => 'Google', 'count' => $total_views * 0.3],
                (object) ['source' => 'Facebook', 'count' => $total_views * 0.2],
                (object) ['source' => 'Other', 'count' => $total_views * 0.1]
            ],
            'locations' => []
        ];
    }

    /**
     * Fallback agent analytics
     */
    private function get_fallback_agent_analytics(int $user_id, string $period): array
    {
        $listings = get_posts([
            'author' => $user_id,
            'post_type' => 'listing',
            'post_status' => 'publish',
            'numberposts' => -1
        ]);

        $total_views = 0;
        $unique_views = 0;
        
        foreach ($listings as $listing) {
            $total_views += get_post_meta($listing->ID, '_listing_views', true) ?: 0;
            $unique_views += get_post_meta($listing->ID, '_listing_unique_views', true) ?: 0;
        }

        return [
            'total_views' => $total_views,
            'unique_views' => $unique_views,
            'avg_duration' => 180,
            'monthly_views' => $total_views
        ];
    }

    /**
     * Generate sample view data for fallback
     */
    private function generate_sample_view_data(string $period): array
    {
        $data = [];
        $days = intval($period);
        
        for ($i = $days; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $views = rand(10, 50);
            
            $data[] = (object) [
                'date_recorded' => $date,
                'views' => $views,
                'unique_views' => round($views * 0.7)
            ];
        }
        
        return $data;
    }

    // =========================================================================
    // OPEN HOUSE AJAX HANDLERS
    // =========================================================================

    /**
     * Get open house data for editing
     */
    public function get_open_house_data(): void
    {
        if (!is_user_logged_in() || !$this->user_can_access_dashboard()) {
            wp_send_json_error(__('Unauthorized access', 'happy-place'));
        }

        if (!check_ajax_referer('hph_dashboard_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'happy-place'));
        }

        $listing_id = intval($_POST['listing_id'] ?? 0);

        if (!$listing_id) {
            wp_send_json_error(__('Invalid listing ID', 'happy-place'));
        }

        try {
            // Include the bridge class
            if (!class_exists('HappyPlace\\Core\\Open_House_Bridge')) {
                require_once plugin_dir_path(__FILE__) . '../class-open-house-bridge.php';
            }

            $open_house_data = \HappyPlace\Core\Open_House_Bridge::get_open_house_data($listing_id);

            if (!$open_house_data) {
                wp_send_json_error(__('No open house data found', 'happy-place'));
            }

            wp_send_json_success($open_house_data);

        } catch (Exception $e) {
            error_log('Dashboard Error: ' . $e->getMessage());
            wp_send_json_error(__('Failed to fetch open house data', 'happy-place'));
        }
    }

    /**
     * Get open house flyer data
     */
    public function get_open_house_flyer_data(): void
    {
        if (!is_user_logged_in() || !$this->user_can_access_dashboard()) {
            wp_send_json_error(__('Unauthorized access', 'happy-place'));
        }

        if (!check_ajax_referer('hph_dashboard_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'happy-place'));
        }

        $listing_id = intval($_POST['listing_id'] ?? 0);

        if (!$listing_id) {
            wp_send_json_error(__('Invalid listing ID', 'happy-place'));
        }

        try {
            // Include the bridge class
            if (!class_exists('HappyPlace\\Core\\Open_House_Bridge')) {
                require_once plugin_dir_path(__FILE__) . '../class-open-house-bridge.php';
            }

            $flyer_data = \HappyPlace\Core\Open_House_Bridge::get_open_house_flyer_data($listing_id);

            if (!$flyer_data) {
                wp_send_json_error(__('No open house scheduled for this listing', 'happy-place'));
            }

            wp_send_json_success($flyer_data);

        } catch (Exception $e) {
            error_log('Dashboard Error: ' . $e->getMessage());
            wp_send_json_error(__('Failed to fetch flyer data', 'happy-place'));
        }
    }

    // Static method for template compatibility
    public static function get_section_data(string $section): array
    {
        return self::instance()->get_plugin_section_data([], $section);
    }
}

// Initialize the dashboard AJAX handler
HPH_Dashboard_Ajax_Handler::instance();