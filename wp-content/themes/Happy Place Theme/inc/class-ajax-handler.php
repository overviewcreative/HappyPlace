<?php

/**
 * Theme AJAX Handler - Presentation Layer Only
 * 
 * Handles AJAX requests that are purely presentation-related
 * and don't involve data manipulation or storage.
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme AJAX Handler Class
 * 
 * Manages presentation-specific AJAX operations:
 * - UI state changes
 * - Template fragment loading
 * - Asset loading
 * - Non-persistent user preferences
 */
class HPH_Theme_Ajax_Handler
{
    /**
     * @var HPH_Theme_Ajax_Handler|null Singleton instance
     */
    private static ?self $instance = null;

    /**
     * Get singleton instance
     */
    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Constructor - Initialize theme-specific AJAX handling
     */
    private function __construct()
    {
        $this->register_theme_ajax_actions();
    }

    /**
     * Register theme-specific AJAX actions
     */
    private function register_theme_ajax_actions(): void
    {
        // UI and presentation-only actions
        $theme_actions = [
            'hph_load_template_part' => 'load_template_part',
            'hph_toggle_view_mode' => 'toggle_view_mode',
            'hph_update_ui_state' => 'update_ui_state',
            'hph_load_more_listings' => 'load_more_listings_display',
            'hph_get_listing_card' => 'get_listing_card_html',
            'hph_refresh_map_view' => 'refresh_map_view',
            'hph_validate_form_field' => 'validate_form_field_display'
        ];

        foreach ($theme_actions as $action => $method) {
            add_action("wp_ajax_{$action}", [$this, $method]);
            add_action("wp_ajax_nopriv_{$action}", [$this, $method]);
        }

        // Register additional agent AJAX handlers
        $this->register_ajax_handlers();
    }

    // ADD TO register_ajax_handlers() method:
    private function register_ajax_handlers(): void
    {
        $handlers = [
            // Existing handlers...
            'hph_search_suggestions' => 'search_suggestions',
            'hph_filter_listings' => 'filter_listings',
            'hph_toggle_favorite' => 'toggle_favorite',
            'hph_get_favorites' => 'get_favorites',
            'hph_save_search' => 'save_search',
            'hph_contact_agent' => 'contact_agent',
            'hph_get_map_markers' => 'get_map_markers',

            // NEW AGENT HANDLERS - ADD THESE:
            'hph_filter_agents' => 'filter_agents',
            'hph_agent_contact' => 'agent_contact',
            'hph_agent_property_inquiry' => 'agent_property_inquiry',
            'hph_save_agent' => 'save_agent',
            'hph_unsave_agent' => 'unsave_agent',
            'hph_buyer_registration' => 'buyer_registration',
            'hph_schedule_callback' => 'schedule_callback'
        ];

        foreach ($handlers as $action => $method) {
            add_action("wp_ajax_{$action}", [$this, $method]);
            add_action("wp_ajax_nopriv_{$action}", [$this, $method]);
        }
    }

    /**
     * 1. FILTER AGENTS - For archive page filtering
     */
    public function filter_agents(): void
    {
        check_ajax_referer('hph_ajax_nonce', 'nonce');

        $filters = [
            'search' => sanitize_text_field($_POST['filters']['search'] ?? ''),
            'location' => sanitize_text_field($_POST['filters']['location'] ?? ''),
            'specialization' => sanitize_text_field($_POST['filters']['specialization'] ?? ''),
            'language' => sanitize_text_field($_POST['filters']['language'] ?? ''),
            'experience' => intval($_POST['filters']['experience'] ?? 0),
            'rating' => intval($_POST['filters']['rating'] ?? 0),
            'sort' => sanitize_text_field($_POST['filters']['sort'] ?? 'name')
        ];

        $page = intval($_POST['page'] ?? 1);
        $view_mode = sanitize_text_field($_POST['view_mode'] ?? 'grid');

        $args = [
            'post_type' => 'agent',
            'post_status' => 'publish',
            'posts_per_page' => 12,
            'paged' => $page,
            'meta_query' => ['relation' => 'AND']
        ];

        // Apply filters
        if (!empty($filters['search'])) {
            $args['s'] = $filters['search'];
        }

        if (!empty($filters['location'])) {
            $args['meta_query'][] = [
                'key' => 'office_location',
                'value' => $filters['location'],
                'compare' => 'LIKE'
            ];
        }

        if (!empty($filters['specialization'])) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'agent_specialty',
                    'field' => 'slug',
                    'terms' => $filters['specialization']
                ]
            ];
        }

        // Apply sorting
        switch ($filters['sort']) {
            case 'name_desc':
                $args['orderby'] = 'title';
                $args['order'] = 'DESC';
                break;
            case 'listings':
                $args['meta_key'] = '_listing_count'; // You'd need to set this
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            default:
                $args['orderby'] = 'title';
                $args['order'] = 'ASC';
        }

        $query = new WP_Query($args);

        $grid_html = '';
        $list_html = '';

        if ($query->have_posts()) {
            // Generate grid view HTML
            ob_start();
            while ($query->have_posts()) {
                $query->the_post();
                include(get_template_directory() . '/template-parts/cards/agent-card-grid.php');
            }
            $grid_html = ob_get_clean();

            // Generate list view HTML
            ob_start();
            $query->rewind_posts();
            while ($query->have_posts()) {
                $query->the_post();
                include(get_template_directory() . '/template-parts/cards/agent-card-list.php');
            }
            $list_html = ob_get_clean();
            wp_reset_postdata();
        }

        wp_send_json_success([
            'grid_html' => $grid_html,
            'list_html' => $list_html,
            'total' => $query->found_posts,
            'current_page' => $page,
            'has_more' => $page < $query->max_num_pages
        ]);
    }

    /**
     * 2. AGENT CONTACT - Handle contact form submissions
     */
    public function agent_contact(): void
    {
        check_ajax_referer('hph_ajax_nonce', 'nonce');

        $data = [
            'agent_id' => intval($_POST['agent_id'] ?? 0),
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'subject' => sanitize_text_field($_POST['subject'] ?? ''),
            'message' => sanitize_textarea_field($_POST['message'] ?? ''),
            'preferred_contact' => sanitize_text_field($_POST['preferred_contact'] ?? 'email'),
            'best_time' => sanitize_text_field($_POST['best_time'] ?? 'anytime')
        ];

        // Validation
        $errors = [];
        if (empty($data['name'])) $errors[] = 'Name is required';
        if (empty($data['email']) || !is_email($data['email'])) $errors[] = 'Valid email is required';
        if (empty($data['message'])) $errors[] = 'Message is required';
        if (!$data['agent_id']) $errors[] = 'Invalid agent';

        if (!empty($errors)) {
            wp_send_json_error(['message' => implode('. ', $errors)]);
        }

        // Get agent email using correct field name
        $agent_email = get_field('email', $data['agent_id']);
        if (!$agent_email) {
            wp_send_json_error(['message' => 'Unable to contact agent.']);
        }

        // Send email
        $subject = 'New Contact Inquiry - ' . $data['subject'];
        $message = $this->format_agent_contact_email($data);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
            'Reply-To: ' . $data['name'] . ' <' . $data['email'] . '>'
        ];

        $sent = wp_mail($agent_email, $subject, $message, $headers);

        if ($sent) {
            // Log the contact
            $this->log_agent_contact($data);
            wp_send_json_success(['message' => 'Your message has been sent successfully!']);
        } else {
            wp_send_json_error(['message' => 'There was an error sending your message.']);
        }
    }

    /**
     * 3. PROPERTY INQUIRY - Handle property search requests
     */
    public function agent_property_inquiry(): void
    {
        check_ajax_referer('hph_ajax_nonce', 'nonce');

        $data = [
            'agent_id' => intval($_POST['agent_id'] ?? 0),
            'inquiry_type' => sanitize_text_field($_POST['inquiry_type'] ?? ''),
            'price_range_min' => intval($_POST['price_range_min'] ?? 0),
            'price_range_max' => intval($_POST['price_range_max'] ?? 0),
            'property_type' => sanitize_text_field($_POST['property_type'] ?? ''),
            'bedrooms' => sanitize_text_field($_POST['bedrooms'] ?? ''),
            'bathrooms' => sanitize_text_field($_POST['bathrooms'] ?? ''),
            'location' => sanitize_text_field($_POST['location'] ?? ''),
            'timeline' => sanitize_text_field($_POST['timeline'] ?? ''),
            'additional_requirements' => sanitize_textarea_field($_POST['additional_requirements'] ?? '')
        ];

        // Save the inquiry
        $inquiry_id = wp_insert_post([
            'post_type' => 'property_inquiry',
            'post_title' => 'Property Inquiry - ' . date('Y-m-d H:i:s'),
            'post_status' => 'publish',
            'meta_input' => $data
        ]);

        if ($inquiry_id) {
            // Send notification to agent
            $agent_email = get_field('email', $data['agent_id']);
            if ($agent_email) {
                $subject = 'New Property Search Inquiry';
                $message = $this->format_property_inquiry_email($data);
                wp_mail($agent_email, $subject, $message);
            }

            wp_send_json_success(['message' => 'Your inquiry has been submitted!']);
        } else {
            wp_send_json_error(['message' => 'There was an error submitting your inquiry.']);
        }
    }

    /**
     * 4. SAVE/UNSAVE AGENT - For favorites functionality
     */
    public function save_agent(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'You must be logged in to save agents.']);
        }

        check_ajax_referer('hph_ajax_nonce', 'nonce');

        $agent_id = intval($_POST['agent_id'] ?? 0);
        $user_id = get_current_user_id();

        $saved_agents = get_user_meta($user_id, 'saved_agents', true) ?: [];

        if (!in_array($agent_id, $saved_agents)) {
            $saved_agents[] = $agent_id;
            update_user_meta($user_id, 'saved_agents', $saved_agents);
            wp_send_json_success(['message' => 'Agent saved to your favorites!']);
        } else {
            wp_send_json_error(['message' => 'Agent is already in your favorites.']);
        }
    }

    public function unsave_agent(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'You must be logged in.']);
        }

        check_ajax_referer('hph_ajax_nonce', 'nonce');

        $agent_id = intval($_POST['agent_id'] ?? 0);
        $user_id = get_current_user_id();

        $saved_agents = get_user_meta($user_id, 'saved_agents', true) ?: [];
        $saved_agents = array_diff($saved_agents, [$agent_id]);

        update_user_meta($user_id, 'saved_agents', $saved_agents);
        wp_send_json_success(['message' => 'Agent removed from favorites.']);
    }

    /**
     * 5. SCHEDULE CALLBACK - Handle callback requests
     */
    public function schedule_callback(): void
    {
        check_ajax_referer('hph_ajax_nonce', 'nonce');

        $data = [
            'agent_id' => intval($_POST['agent_id'] ?? 0),
            'name' => sanitize_text_field($_POST['callback_name'] ?? ''),
            'phone' => sanitize_text_field($_POST['callback_phone'] ?? ''),
            'date' => sanitize_text_field($_POST['callback_date'] ?? ''),
            'time' => sanitize_text_field($_POST['callback_time'] ?? ''),
            'topic' => sanitize_textarea_field($_POST['callback_topic'] ?? '')
        ];

        // Save callback request
        $callback_id = wp_insert_post([
            'post_type' => 'callback_request',
            'post_title' => 'Callback Request - ' . $data['name'] . ' - ' . date('Y-m-d H:i:s'),
            'post_status' => 'publish',
            'meta_input' => $data
        ]);

        if ($callback_id) {
            // Send notification to agent
            $agent_email = get_field('email', $data['agent_id']);
            if ($agent_email) {
                $subject = 'New Callback Request';
                $message = $this->format_callback_email($data);
                wp_mail($agent_email, $subject, $message);
            }

            wp_send_json_success(['message' => 'Your callback has been scheduled!']);
        } else {
            wp_send_json_error(['message' => 'There was an error scheduling your callback.']);
        }
    }

    // Helper methods for agent AJAX handlers
    private function format_agent_contact_email($data): string
    {
        $agent_name = get_the_title($data['agent_id']);

        return "
        <h2>New Contact Inquiry</h2>
        <p><strong>Agent:</strong> {$agent_name}</p>
        <p><strong>From:</strong> {$data['name']}</p>
        <p><strong>Email:</strong> {$data['email']}</p>
        <p><strong>Phone:</strong> {$data['phone']}</p>
        <p><strong>Subject:</strong> {$data['subject']}</p>
        <p><strong>Preferred Contact:</strong> {$data['preferred_contact']}</p>
        <p><strong>Best Time:</strong> {$data['best_time']}</p>
        <hr>
        <p><strong>Message:</strong></p>
        <p>" . nl2br(esc_html($data['message'])) . "</p>
        ";
    }

    private function format_property_inquiry_email($data): string
    {
        return "
        <h2>New Property Search Inquiry</h2>
        <p><strong>Type:</strong> {$data['inquiry_type']}</p>
        <p><strong>Price Range:</strong> $" . number_format($data['price_range_min']) . " - $" . number_format($data['price_range_max']) . "</p>
        <p><strong>Property Type:</strong> {$data['property_type']}</p>
        <p><strong>Bedrooms:</strong> {$data['bedrooms']}</p>
        <p><strong>Bathrooms:</strong> {$data['bathrooms']}</p>
        <p><strong>Location:</strong> {$data['location']}</p>
        <p><strong>Timeline:</strong> {$data['timeline']}</p>
        <hr>
        <p><strong>Additional Requirements:</strong></p>
        <p>" . nl2br(esc_html($data['additional_requirements'])) . "</p>
        ";
    }

    private function format_callback_email($data): string
    {
        return "
        <h2>New Callback Request</h2>
        <p><strong>Name:</strong> {$data['name']}</p>
        <p><strong>Phone:</strong> {$data['phone']}</p>
        <p><strong>Preferred Date:</strong> {$data['date']}</p>
        <p><strong>Preferred Time:</strong> {$data['time']}</p>
        <hr>
        <p><strong>Topic:</strong></p>
        <p>" . nl2br(esc_html($data['topic'])) . "</p>
        ";
    }

    private function log_agent_contact($data): void
    {
        // Log contact attempt for CRM tracking
        $log_data = [
            'post_type' => 'agent_contact_log',
            'post_title' => 'Contact: ' . $data['name'] . ' - ' . date('Y-m-d H:i:s'),
            'post_status' => 'publish',
            'meta_input' => [
                'agent_id' => $data['agent_id'],
                'contact_name' => $data['name'],
                'contact_email' => $data['email'],
                'contact_phone' => $data['phone'],
                'contact_subject' => $data['subject'],
                'contact_message' => $data['message'],
                'contact_date' => current_time('mysql'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]
        ];

        wp_insert_post($log_data);
    }

    /**
     * Load template part dynamically
     * For loading template fragments without full page reload
     */
    public function load_template_part(): void
    {
        if (!check_ajax_referer('hph_theme_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'happy-place'));
        }

        $template_part = sanitize_file_name($_POST['template_part'] ?? '');
        $template_args = $_POST['args'] ?? [];

        // Allowed template parts for security
        $allowed_templates = [
            'cards/listing-list-card',
            'cards/listing-swipe-card',
            'listing/no-results',
            'filters/filter-chips',
            'pagination/load-more'
        ];

        if (!in_array($template_part, $allowed_templates)) {
            wp_send_json_error(__('Invalid template requested', 'happy-place'));
        }

        ob_start();

        // Sanitize args
        $sanitized_args = $this->sanitize_template_args($template_args);

        get_template_part("template-parts/{$template_part}", null, $sanitized_args);

        $content = ob_get_clean();

        wp_send_json_success([
            'html' => $content,
            'template' => $template_part
        ]);
    }

    /**
     * Toggle view mode (list/grid/map)
     * Stores preference in session, not database
     */
    public function toggle_view_mode(): void
    {
        if (!check_ajax_referer('hph_theme_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'happy-place'));
        }

        $view_mode = sanitize_key($_POST['view_mode'] ?? '');

        $allowed_modes = ['list', 'grid', 'swipe', 'map'];
        if (!in_array($view_mode, $allowed_modes)) {
            wp_send_json_error(__('Invalid view mode', 'happy-place'));
        }

        // Store in session (not database)
        if (!session_id()) {
            session_start();
        }
        $_SESSION['hph_view_mode'] = $view_mode;

        wp_send_json_success([
            'view_mode' => $view_mode,
            'message' => sprintf(__('Switched to %s view', 'happy-place'), $view_mode)
        ]);
    }

    /**
     * Update UI state (temporary/session-based changes)
     */
    public function update_ui_state(): void
    {
        if (!check_ajax_referer('hph_theme_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'happy-place'));
        }

        $state_key = sanitize_key($_POST['state_key'] ?? '');
        $state_value = sanitize_text_field($_POST['state_value'] ?? '');

        // Allowed UI states
        $allowed_states = [
            'sidebar_collapsed',
            'map_zoom_level',
            'filter_panel_open',
            'sort_preference',
            'results_per_page'
        ];

        if (!in_array($state_key, $allowed_states)) {
            wp_send_json_error(__('Invalid state key', 'happy-place'));
        }

        // Store in session
        if (!session_id()) {
            session_start();
        }
        $_SESSION["hph_ui_{$state_key}"] = $state_value;

        wp_send_json_success([
            'state_key' => $state_key,
            'state_value' => $state_value
        ]);
    }

    /**
     * Load more listings for infinite scroll
     * Returns HTML for display, doesn't query database directly
     */
    public function load_more_listings_display(): void
    {
        if (!check_ajax_referer('hph_theme_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'happy-place'));
        }

        $page = intval($_POST['page'] ?? 1);
        $filters = $_POST['filters'] ?? [];

        // Get listings from plugin via filter hook
        $listings = apply_filters('hph_get_filtered_listings', [], $filters, [
            'page' => $page,
            'posts_per_page' => 12
        ]);

        if (empty($listings)) {
            wp_send_json_success([
                'html' => '',
                'has_more' => false,
                'message' => __('No more listings to load', 'happy-place')
            ]);
        }

        // Generate HTML for listings
        ob_start();
        foreach ($listings as $listing) {
            get_template_part('template-parts/cards/listing-list-card', null, [
                'listing' => $listing
            ]);
        }
        $html = ob_get_clean();

        wp_send_json_success([
            'html' => $html,
            'has_more' => count($listings) >= 12,
            'page' => $page,
            'count' => count($listings)
        ]);
    }

    /**
     * Get single listing card HTML
     * For dynamic card updates or replacements
     */
    public function get_listing_card_html(): void
    {
        if (!check_ajax_referer('hph_theme_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'happy-place'));
        }

        $listing_id = intval($_POST['listing_id'] ?? 0);
        $card_type = sanitize_key($_POST['card_type'] ?? 'list');

        if (!$listing_id) {
            wp_send_json_error(__('Invalid listing ID', 'happy-place'));
        }

        $allowed_card_types = ['list', 'swipe', 'mini'];
        if (!in_array($card_type, $allowed_card_types)) {
            $card_type = 'list';
        }

        // Get listing data from plugin
        $listing = apply_filters('hph_get_listing_by_id', null, $listing_id);

        if (!$listing) {
            wp_send_json_error(__('Listing not found', 'happy-place'));
        }

        ob_start();
        get_template_part("template-parts/cards/listing-{$card_type}-card", null, [
            'listing' => $listing
        ]);
        $html = ob_get_clean();

        wp_send_json_success([
            'html' => $html,
            'listing_id' => $listing_id,
            'card_type' => $card_type
        ]);
    }

    /**
     * Refresh map view
     * Returns map markers and updated view state
     */
    public function refresh_map_view(): void
    {
        if (!check_ajax_referer('hph_theme_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'happy-place'));
        }

        $bounds = $_POST['bounds'] ?? [];
        $zoom = intval($_POST['zoom'] ?? 10);
        $filters = $_POST['filters'] ?? [];

        // Get markers from plugin
        $markers = apply_filters('hph_get_listing_markers', [], array_merge($filters, [
            'bounds' => $bounds,
            'zoom' => $zoom
        ]));

        // Generate info window HTML for each marker
        foreach ($markers as &$marker) {
            if (isset($marker['listing_id'])) {
                ob_start();
                get_template_part('template-parts/listing/map-info-window', null, [
                    'listing_id' => $marker['listing_id']
                ]);
                $marker['info_window_html'] = ob_get_clean();
            }
        }

        wp_send_json_success([
            'markers' => $markers,
            'count' => count($markers),
            'bounds' => $bounds,
            'zoom' => $zoom
        ]);
    }

    /**
     * Validate form field and return display feedback
     * For real-time form validation UI
     */
    public function validate_form_field_display(): void
    {
        if (!check_ajax_referer('hph_theme_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'happy-place'));
        }

        $field_name = sanitize_key($_POST['field_name'] ?? '');
        $field_value = sanitize_text_field($_POST['field_value'] ?? '');

        $validation_result = $this->validate_field_for_display($field_name, $field_value);

        wp_send_json_success([
            'field_name' => $field_name,
            'is_valid' => $validation_result['is_valid'],
            'message' => $validation_result['message'],
            'css_class' => $validation_result['is_valid'] ? 'valid' : 'invalid'
        ]);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Sanitize template arguments
     */
    private function sanitize_template_args(array $args): array
    {
        $sanitized = [];

        foreach ($args as $key => $value) {
            $key = sanitize_key($key);

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize_template_args($value);
            } elseif (is_numeric($value)) {
                $sanitized[$key] = intval($value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }

        return $sanitized;
    }

    /**
     * Validate field for display purposes only
     */
    private function validate_field_for_display(string $field_name, string $field_value): array
    {
        $result = ['is_valid' => true, 'message' => ''];

        switch ($field_name) {
            case 'email':
                if (!is_email($field_value)) {
                    $result = [
                        'is_valid' => false,
                        'message' => __('Please enter a valid email address', 'happy-place')
                    ];
                }
                break;

            case 'phone':
                if (!preg_match('/^[\d\s\-\(\)\+]+$/', $field_value)) {
                    $result = [
                        'is_valid' => false,
                        'message' => __('Please enter a valid phone number', 'happy-place')
                    ];
                }
                break;

            case 'price':
                if (!is_numeric($field_value) || floatval($field_value) <= 0) {
                    $result = [
                        'is_valid' => false,
                        'message' => __('Please enter a valid price', 'happy-place')
                    ];
                }
                break;

            case 'zip_code':
                if (!preg_match('/^\d{5}(-\d{4})?$/', $field_value)) {
                    $result = [
                        'is_valid' => false,
                        'message' => __('Please enter a valid ZIP code', 'happy-place')
                    ];
                }
                break;

            default:
                // Basic required field check
                if (empty(trim($field_value))) {
                    $result = [
                        'is_valid' => false,
                        'message' => __('This field is required', 'happy-place')
                    ];
                }
                break;
        }

        return $result;
    }

    /**
     * Get current view mode from session
     */
    public static function get_current_view_mode(): string
    {
        if (!session_id()) {
            session_start();
        }

        return $_SESSION['hph_view_mode'] ?? 'list';
    }

    /**
     * Get UI state from session
     */
    public static function get_ui_state(string $state_key, $default = null)
    {
        if (!session_id()) {
            session_start();
        }

        return $_SESSION["hph_ui_{$state_key}"] ?? $default;
    }

    /**
     * Check if this is a theme AJAX request
     */
    public static function is_theme_ajax_request(): bool
    {
        return defined('DOING_AJAX') &&
            DOING_AJAX &&
            isset($_POST['action']) &&
            strpos($_POST['action'], 'hph_') === 0 &&
            !in_array($_POST['action'], [
                'hph_load_dashboard_section',
                'hph_save_listing',
                'hph_save_lead',
                'hph_save_open_house'
            ]);
    }
}

// Initialize the theme AJAX handler
HPH_Theme_Ajax_Handler::instance();
