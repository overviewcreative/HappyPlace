<?php
/**
 * AJAX Handler Class
 *
 * @package HappyPlace
 * @subpackage Core
 */

namespace HappyPlace\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Ajax_Handler {
    private static ?self $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->register_ajax_actions();
    }

    private function register_ajax_actions(): void {
        // UI and presentation-only actions
        $ajax_actions = [
            'hph_load_template_part' => 'load_template_part',
            'hph_toggle_view_mode' => 'toggle_view_mode',
            'hph_update_ui_state' => 'update_ui_state',
            'hph_load_more_listings' => 'load_more_listings_display',
            'hph_get_listing_card' => 'get_listing_card_html',
            'hph_refresh_map_view' => 'refresh_map_view',
            'hph_validate_form_field' => 'validate_form_field_display',
            
            // LISTING ACTIONS
            'hph_schedule_tour' => 'schedule_tour',
            'hph_request_info' => 'request_info',
            'hph_toggle_favorite' => 'toggle_favorite',
            'hph_contact_agent' => 'contact_agent',
            'hph_share_property' => 'share_property',
            'hph_download_photos' => 'download_photos',
            'hph_print_listing' => 'print_listing',
            
            // Agent actions
            'hph_filter_agents' => 'filter_agents',
            'hph_agent_contact' => 'agent_contact',
            'hph_agent_property_inquiry' => 'agent_property_inquiry',
            'hph_save_agent' => 'save_agent',
            'hph_unsave_agent' => 'unsave_agent',
            'hph_schedule_callback' => 'schedule_callback'
        ];

        foreach ($ajax_actions as $action => $method) {
            add_action("wp_ajax_{$action}", [$this, $method]);
            add_action("wp_ajax_nopriv_{$action}", [$this, $method]);
        }
    }

    public function load_template_part(): void {
        check_ajax_referer('hph_ajax_nonce', 'nonce');

        $slug = sanitize_text_field($_POST['slug'] ?? '');
        $name = sanitize_text_field($_POST['name'] ?? '');
        $args = isset($_POST['args']) ? $this->sanitize_template_args($_POST['args']) : [];

        if (!$slug) {
            wp_send_json_error('Invalid template parameters');
        }

        ob_start();
        // Use Template_Loader instead of Template_Manager for consistency
        $template_loader = \HappyPlace\Core\Template_Loader::get_instance();
        $template_loader->get_template_part($slug, $name, $args);
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    public function toggle_view_mode(): void {
        check_ajax_referer('hph_ajax_nonce', 'nonce');

        $mode = sanitize_text_field($_POST['mode'] ?? '');
        if (!in_array($mode, ['grid', 'list', 'map'])) {
            wp_send_json_error('Invalid view mode');
        }

        // Store in user meta if logged in, otherwise in session
        if (is_user_logged_in()) {
            update_user_meta(get_current_user_id(), 'hph_view_mode', $mode);
        } else {
            if (!session_id()) {
                session_start();
            }
            $_SESSION['hph_view_mode'] = $mode;
        }

        wp_send_json_success();
    }

    public function update_ui_state(): void {
        check_ajax_referer('hph_ajax_nonce', 'nonce');

        $state_key = sanitize_text_field($_POST['key'] ?? '');
        $state_value = sanitize_text_field($_POST['value'] ?? '');

        if (!$state_key) {
            wp_send_json_error('Invalid state parameters');
        }

        if (is_user_logged_in()) {
            update_user_meta(get_current_user_id(), "hph_ui_state_{$state_key}", $state_value);
        } else {
            if (!session_id()) {
                session_start();
            }
            $_SESSION["hph_ui_state_{$state_key}"] = $state_value;
        }

        wp_send_json_success();
    }

    public function load_more_listings_display(): void {
        check_ajax_referer('hph_ajax_nonce', 'nonce');

        $page = intval($_POST['page'] ?? 1);
        $query_args = isset($_POST['query']) ? $this->sanitize_template_args($_POST['query']) : [];

        $query_args = wp_parse_args($query_args, [
            'post_type' => 'listing',
            'paged' => $page,
            'posts_per_page' => get_option('posts_per_page'),
        ]);

        $listings_query = new \WP_Query($query_args);

        ob_start();
        if ($listings_query->have_posts()) {
            while ($listings_query->have_posts()) {
                $listings_query->the_post();
                $template_loader = \HappyPlace\Core\Template_Loader::get_instance();
                $template_loader->get_template_part('template-parts/cards/listing-card', '', [
                    'post_id' => get_the_ID(),
                    'size' => 'medium',
                    'show_agent' => true
                ]);
            }
        }
        $html = ob_get_clean();

        wp_reset_postdata();

        wp_send_json_success([
            'html' => $html,
            'hasMore' => $listings_query->max_num_pages > $page,
            'nextPage' => $page + 1
        ]);
    }

    public function get_listing_card_html(): void {
        check_ajax_referer('hph_ajax_nonce', 'nonce');

        $listing_id = intval($_POST['listing_id'] ?? 0);
        if (!$listing_id) {
            wp_send_json_error('Invalid listing ID');
        }

        ob_start();
        $template_loader = \HappyPlace\Core\Template_Loader::get_instance();
        $template_loader->get_template_part('template-parts/cards/listing-card', '', [
            'post_id' => $listing_id,
            'size' => sanitize_text_field($_POST['size'] ?? 'medium'),
            'show_agent' => (bool)($_POST['show_agent'] ?? true)
        ]);
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    public function refresh_map_view(): void {
        check_ajax_referer('hph_ajax_nonce', 'nonce');

        $bounds = $_POST['bounds'] ?? null;
        if (!$bounds || !is_array($bounds)) {
            wp_send_json_error('Invalid map bounds');
        }

        $query_args = [
            'post_type' => 'listing',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'latitude',
                    'value' => [$bounds['south'], $bounds['north']],
                    'type' => 'NUMERIC',
                    'compare' => 'BETWEEN'
                ],
                [
                    'key' => 'longitude',
                    'value' => [$bounds['west'], $bounds['east']],
                    'type' => 'NUMERIC',
                    'compare' => 'BETWEEN'
                ]
            ]
        ];

        $listings_query = new \WP_Query($query_args);
        $markers = [];

        while ($listings_query->have_posts()) {
            $listings_query->the_post();
            $markers[] = [
                'id' => get_the_ID(),
                'lat' => get_post_meta(get_the_ID(), 'latitude', true),
                'lng' => get_post_meta(get_the_ID(), 'longitude', true),
                'title' => get_the_title(),
                'price' => get_post_meta(get_the_ID(), 'price', true),
                'thumbnail' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'),
                'url' => get_permalink()
            ];
        }

        wp_reset_postdata();

        wp_send_json_success(['markers' => $markers]);
    }

    public function validate_form_field_display(): void {
        check_ajax_referer('hph_ajax_nonce', 'nonce');

        $field_name = sanitize_text_field($_POST['field_name'] ?? '');
        $field_value = sanitize_text_field($_POST['field_value'] ?? '');

        if (!$field_name) {
            wp_send_json_error('Invalid field parameters');
        }

        $validation = $this->validate_field_for_display($field_name, $field_value);
        wp_send_json_success($validation);
    }

    private function sanitize_template_args(array $args): array {
        $sanitized = [];
        foreach ($args as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize_template_args($value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        return $sanitized;
    }

    private function validate_field_for_display(string $field_name, string $field_value): array {
        $validation = [
            'isValid' => true,
            'message' => ''
        ];

        switch ($field_name) {
            case 'email':
                if (!is_email($field_value)) {
                    $validation['isValid'] = false;
                    $validation['message'] = __('Please enter a valid email address.', 'happy-place');
                }
                break;
            case 'phone':
                if (!preg_match('/^[\d\s\-\(\)]+$/', $field_value)) {
                    $validation['isValid'] = false;
                    $validation['message'] = __('Please enter a valid phone number.', 'happy-place');
                }
                break;
            // Add more field validations as needed
        }

        return $validation;
    }

    public static function get_current_view_mode(): string {
        if (is_user_logged_in()) {
            $mode = get_user_meta(get_current_user_id(), 'hph_view_mode', true);
        } else {
            if (!session_id()) {
                session_start();
            }
            $mode = $_SESSION['hph_view_mode'] ?? '';
        }

        return $mode ?: 'grid';
    }

    public static function get_ui_state(string $state_key, $default = null) {
        if (is_user_logged_in()) {
            return get_user_meta(get_current_user_id(), "hph_ui_state_{$state_key}", true) ?: $default;
        } else {
            if (!session_id()) {
                session_start();
            }
            return $_SESSION["hph_ui_state_{$state_key}"] ?? $default;
        }
    }

    // =============================================================================
    // NEW LISTING ACTION METHODS
    // =============================================================================

    public function schedule_tour(): void {
        check_ajax_referer('hph_ajax_nonce', 'nonce');

        $listing_id = intval($_POST['listing_id'] ?? 0);
        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $date = sanitize_text_field($_POST['date'] ?? '');
        $time = sanitize_text_field($_POST['time'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');

        // Validate required fields
        if (!$listing_id || !$name || !$email) {
            wp_send_json_error(__('Please fill in all required fields', 'happy-place'));
        }

        if (!is_email($email)) {
            wp_send_json_error(__('Please enter a valid email address', 'happy-place'));
        }

        // Create tour request
        $tour_id = wp_insert_post([
            'post_type' => 'tour_request',
            'post_title' => sprintf(__('Tour Request: %s', 'happy-place'), get_the_title($listing_id)),
            'post_status' => 'pending',
            'meta_input' => [
                'listing_id' => $listing_id,
                'requester_name' => $name,
                'requester_email' => $email,
                'requester_phone' => $phone,
                'preferred_date' => $date,
                'preferred_time' => $time,
                'message' => $message,
                'request_date' => current_time('mysql'),
                'status' => 'pending'
            ]
        ]);

        if (is_wp_error($tour_id)) {
            wp_send_json_error(__('Failed to schedule tour. Please try again.', 'happy-place'));
        }

        // Send notifications
        $this->send_tour_notifications($tour_id, $listing_id);

        wp_send_json_success([
            'message' => __('Tour request sent successfully! We\'ll contact you soon.', 'happy-place'),
            'tour_id' => $tour_id
        ]);
    }

    public function request_info(): void {
        check_ajax_referer('hph_ajax_nonce', 'nonce');

        $listing_id = intval($_POST['listing_id'] ?? 0);
        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $info_type = sanitize_text_field($_POST['info_type'] ?? 'general');

        // Validate required fields
        if (!$listing_id || !$name || !$email || !$message) {
            wp_send_json_error(__('Please fill in all required fields', 'happy-place'));
        }

        if (!is_email($email)) {
            wp_send_json_error(__('Please enter a valid email address', 'happy-place'));
        }

        // Create info request
        $request_id = wp_insert_post([
            'post_type' => 'info_request',
            'post_title' => sprintf(__('Info Request: %s', 'happy-place'), get_the_title($listing_id)),
            'post_status' => 'pending',
            'meta_input' => [
                'listing_id' => $listing_id,
                'requester_name' => $name,
                'requester_email' => $email,
                'requester_phone' => $phone,
                'message' => $message,
                'info_type' => $info_type,
                'request_date' => current_time('mysql'),
                'status' => 'pending'
            ]
        ]);

        if (is_wp_error($request_id)) {
            wp_send_json_error(__('Failed to send request. Please try again.', 'happy-place'));
        }

        // Send notifications
        $this->send_info_request_notifications($request_id, $listing_id);

        wp_send_json_success([
            'message' => __('Information request sent successfully!', 'happy-place'),
            'request_id' => $request_id
        ]);
    }

    public function toggle_favorite(): void {
        check_ajax_referer('hph_ajax_nonce', 'nonce');

        $listing_id = intval($_POST['listing_id'] ?? 0);
        if (!$listing_id) {
            wp_send_json_error(__('Invalid listing ID', 'happy-place'));
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Please log in to save favorites', 'happy-place'));
        }

        $user_id = get_current_user_id();
        $favorites = get_user_meta($user_id, 'favorite_listings', true) ?: [];

        if (in_array($listing_id, $favorites)) {
            $favorites = array_diff($favorites, [$listing_id]);
            $action = 'removed';
            $message = __('Removed from favorites', 'happy-place');
        } else {
            $favorites[] = $listing_id;
            $action = 'added';
            $message = __('Added to favorites', 'happy-place');
        }

        update_user_meta($user_id, 'favorite_listings', array_values($favorites));

        // Clear related caches
        wp_cache_delete('hph_user_favorites_' . $user_id, 'hph_users');

        wp_send_json_success([
            'action' => $action,
            'message' => $message,
            'count' => count($favorites),
            'is_favorited' => $action === 'added'
        ]);
    }

    public function contact_agent(): void {
        check_ajax_referer('hph_ajax_nonce', 'nonce');

        $listing_id = intval($_POST['listing_id'] ?? 0);
        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');

        // Validate required fields
        if (!$listing_id || !$name || !$email || !$message) {
            wp_send_json_error(__('Please fill in all required fields', 'happy-place'));
        }

        if (!is_email($email)) {
            wp_send_json_error(__('Please enter a valid email address', 'happy-place'));
        }

        // Get agent info
        $agent = $this->get_listing_agent($listing_id);
        if (!$agent) {
            wp_send_json_error(__('Agent information not available', 'happy-place'));
        }

        // Create contact request
        $contact_id = wp_insert_post([
            'post_type' => 'agent_contact',
            'post_title' => sprintf(__('Contact: %s', 'happy-place'), get_the_title($listing_id)),
            'post_status' => 'pending',
            'meta_input' => [
                'listing_id' => $listing_id,
                'agent_id' => $agent['id'],
                'contact_name' => $name,
                'contact_email' => $email,
                'contact_phone' => $phone,
                'message' => $message,
                'contact_date' => current_time('mysql'),
                'status' => 'new'
            ]
        ]);

        if (is_wp_error($contact_id)) {
            wp_send_json_error(__('Failed to send message. Please try again.', 'happy-place'));
        }

        // Send notifications
        $this->send_agent_contact_notifications($contact_id, $agent);

        wp_send_json_success([
            'message' => __('Message sent to agent successfully!', 'happy-place'),
            'contact_id' => $contact_id
        ]);
    }

    public function share_property(): void {
        check_ajax_referer('hph_ajax_nonce', 'nonce');

        $listing_id = intval($_POST['listing_id'] ?? 0);
        $method = sanitize_text_field($_POST['method'] ?? '');
        
        if (!$listing_id) {
            wp_send_json_error(__('Invalid listing ID', 'happy-place'));
        }

        $listing_url = get_permalink($listing_id);
        $listing_title = get_the_title($listing_id);

        $share_data = [
            'url' => $listing_url,
            'title' => $listing_title,
            'description' => wp_trim_words(get_post_field('post_content', $listing_id), 30),
            'image' => get_the_post_thumbnail_url($listing_id, 'large')
        ];

        // Track sharing
        $this->track_listing_share($listing_id, $method);

        wp_send_json_success([
            'share_data' => $share_data,
            'message' => __('Property shared successfully!', 'happy-place')
        ]);
    }

    public function download_photos(): void {
        check_ajax_referer('hph_ajax_nonce', 'nonce');

        $listing_id = intval($_POST['listing_id'] ?? 0);
        $email = sanitize_email($_POST['email'] ?? '');
        
        if (!$listing_id) {
            wp_send_json_error(__('Invalid listing ID', 'happy-place'));
        }

        if (!is_email($email)) {
            wp_send_json_error(__('Please enter a valid email address', 'happy-place'));
        }

        // Create download request
        $request_id = wp_insert_post([
            'post_type' => 'photo_request',
            'post_title' => sprintf(__('Photo Request: %s', 'happy-place'), get_the_title($listing_id)),
            'post_status' => 'pending',
            'meta_input' => [
                'listing_id' => $listing_id,
                'requester_email' => $email,
                'request_date' => current_time('mysql'),
                'status' => 'pending'
            ]
        ]);

        if (is_wp_error($request_id)) {
            wp_send_json_error(__('Failed to process request. Please try again.', 'happy-place'));
        }

        // Send high-res photos via email
        $this->send_high_res_photos($listing_id, $email);

        wp_send_json_success([
            'message' => __('High-resolution photos will be sent to your email shortly!', 'happy-place'),
            'request_id' => $request_id
        ]);
    }

    public function print_listing(): void {
        check_ajax_referer('hph_ajax_nonce', 'nonce');

        $listing_id = intval($_POST['listing_id'] ?? 0);
        
        if (!$listing_id) {
            wp_send_json_error(__('Invalid listing ID', 'happy-place'));
        }

        // Track print action
        $this->track_listing_print($listing_id);

        // Generate print-friendly URL
        $print_url = add_query_arg('print', '1', get_permalink($listing_id));

        wp_send_json_success([
            'print_url' => $print_url,
            'message' => __('Print version prepared', 'happy-place')
        ]);
    }

    // =============================================================================
    // HELPER METHODS
    // =============================================================================

    private function send_tour_notifications($tour_id, $listing_id): void {
        // Get agent and send notification
        $agent = $this->get_listing_agent($listing_id);
        if ($agent && !empty($agent['email'])) {
            // Send email to agent
            wp_mail(
                $agent['email'],
                __('New Tour Request', 'happy-place'),
                $this->get_tour_notification_body($tour_id, $listing_id)
            );
        }
        
        // Send confirmation to requester
        $tour_data = get_post($tour_id);
        if ($tour_data) {
            $requester_email = get_post_meta($tour_id, 'requester_email', true);
            if ($requester_email) {
                wp_mail(
                    $requester_email,
                    __('Tour Request Confirmation', 'happy-place'),
                    $this->get_tour_confirmation_body($tour_id, $listing_id)
                );
            }
        }
    }

    private function send_info_request_notifications($request_id, $listing_id): void {
        $agent = $this->get_listing_agent($listing_id);
        if ($agent && !empty($agent['email'])) {
            wp_mail(
                $agent['email'],
                __('New Information Request', 'happy-place'),
                $this->get_info_request_notification_body($request_id, $listing_id)
            );
        }
        
        // Send confirmation to requester
        $request_data = get_post($request_id);
        if ($request_data) {
            $requester_email = get_post_meta($request_id, 'requester_email', true);
            if ($requester_email) {
                wp_mail(
                    $requester_email,
                    __('Information Request Confirmation', 'happy-place'),
                    $this->get_info_request_confirmation_body($request_id, $listing_id)
                );
            }
        }
    }

    private function send_agent_contact_notifications($contact_id, $agent): void {
        if (!empty($agent['email'])) {
            wp_mail(
                $agent['email'],
                __('New Contact Message', 'happy-place'),
                $this->get_agent_contact_notification_body($contact_id)
            );
        }
        
        // Send confirmation to contact
        $contact_data = get_post($contact_id);
        if ($contact_data) {
            $contact_email = get_post_meta($contact_id, 'contact_email', true);
            if ($contact_email) {
                wp_mail(
                    $contact_email,
                    __('Message Sent Confirmation', 'happy-place'),
                    $this->get_contact_confirmation_body($contact_id)
                );
            }
        }
    }

    private function get_listing_agent($listing_id) {
        // Use existing bridge function if available
        if (function_exists('hph_get_listing_agent')) {
            return hph_get_listing_agent($listing_id);
        }
        
        // Fallback to direct ACF access
        $agent_id = get_field('listing_agent', $listing_id) ?: get_field('agent', $listing_id);
        if ($agent_id) {
            return [
                'id' => $agent_id,
                'name' => get_the_title($agent_id),
                'email' => get_field('email', $agent_id) ?: get_field('agent_email', $agent_id),
                'phone' => get_field('phone', $agent_id) ?: get_field('agent_phone', $agent_id)
            ];
        }
        
        return null;
    }

    private function track_listing_share($listing_id, $method): void {
        // Track sharing analytics
        $count = get_post_meta($listing_id, 'share_count', true) ?: 0;
        update_post_meta($listing_id, 'share_count', $count + 1);
        
        // Track by method
        $method_count = get_post_meta($listing_id, "share_count_{$method}", true) ?: 0;
        update_post_meta($listing_id, "share_count_{$method}", $method_count + 1);
    }

    private function track_listing_print($listing_id): void {
        // Track print analytics
        $count = get_post_meta($listing_id, 'print_count', true) ?: 0;
        update_post_meta($listing_id, 'print_count', $count + 1);
    }

    private function send_high_res_photos($listing_id, $email): void {
        // Get all listing photos
        $photos = [];
        
        // Featured image
        if (has_post_thumbnail($listing_id)) {
            $photos[] = wp_get_attachment_url(get_post_thumbnail_id($listing_id));
        }
        
        // Gallery images
        $gallery = get_field('photo_gallery', $listing_id);
        if (is_array($gallery)) {
            foreach ($gallery as $image) {
                if (is_array($image) && isset($image['url'])) {
                    $photos[] = $image['url'];
                } elseif (is_numeric($image)) {
                    $photos[] = wp_get_attachment_url($image);
                }
            }
        }
        
        if (!empty($photos)) {
            // Create ZIP file or send download links
            $listing_title = get_the_title($listing_id);
            $message = sprintf(
                __('Here are the high-resolution photos for %s:', 'happy-place'),
                $listing_title
            );
            
            $message .= "\n\n";
            foreach ($photos as $photo) {
                $message .= $photo . "\n";
            }
            
            wp_mail(
                $email,
                sprintf(__('High-Resolution Photos: %s', 'happy-place'), $listing_title),
                $message
            );
        }
    }

    private function get_tour_notification_body($tour_id, $listing_id): string {
        $tour_data = get_post_meta($tour_id);
        $listing_title = get_the_title($listing_id);
        
        return sprintf(
            __('New tour request for %s from %s (%s). Preferred date: %s at %s. Message: %s', 'happy-place'),
            $listing_title,
            $tour_data['requester_name'][0] ?? '',
            $tour_data['requester_email'][0] ?? '',
            $tour_data['preferred_date'][0] ?? '',
            $tour_data['preferred_time'][0] ?? '',
            $tour_data['message'][0] ?? ''
        );
    }

    private function get_tour_confirmation_body($tour_id, $listing_id): string {
        $listing_title = get_the_title($listing_id);
        return sprintf(
            __('Thank you for your tour request for %s. We\'ll contact you soon to confirm the details.', 'happy-place'),
            $listing_title
        );
    }

    private function get_info_request_notification_body($request_id, $listing_id): string {
        $request_data = get_post_meta($request_id);
        $listing_title = get_the_title($listing_id);
        
        return sprintf(
            __('New information request for %s from %s (%s). Message: %s', 'happy-place'),
            $listing_title,
            $request_data['requester_name'][0] ?? '',
            $request_data['requester_email'][0] ?? '',
            $request_data['message'][0] ?? ''
        );
    }

    private function get_info_request_confirmation_body($request_id, $listing_id): string {
        $listing_title = get_the_title($listing_id);
        return sprintf(
            __('Thank you for your information request about %s. We\'ll get back to you soon.', 'happy-place'),
            $listing_title
        );
    }

    private function get_agent_contact_notification_body($contact_id): string {
        $contact_data = get_post_meta($contact_id);
        $listing_id = $contact_data['listing_id'][0] ?? 0;
        $listing_title = $listing_id ? get_the_title($listing_id) : '';
        
        return sprintf(
            __('New contact message about %s from %s (%s). Message: %s', 'happy-place'),
            $listing_title,
            $contact_data['contact_name'][0] ?? '',
            $contact_data['contact_email'][0] ?? '',
            $contact_data['message'][0] ?? ''
        );
    }

    private function get_contact_confirmation_body($contact_id): string {
        $contact_data = get_post_meta($contact_id);
        $listing_id = $contact_data['listing_id'][0] ?? 0;
        $listing_title = $listing_id ? get_the_title($listing_id) : '';
        
        return sprintf(
            __('Thank you for contacting us about %s. We\'ll respond to your message soon.', 'happy-place'),
            $listing_title
        );
    }
}
