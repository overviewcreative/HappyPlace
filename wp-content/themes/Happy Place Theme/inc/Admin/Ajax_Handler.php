<?php
/**
 * Dashboard AJAX Handler Class
 * 
 * Handles all AJAX requests for the agent dashboard
 */

namespace HappyPlace\Dashboard;

if (!defined('ABSPATH')) {
    exit;
}

class Ajax_Handler {
    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Get instance of this class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Listing actions
        add_action('wp_ajax_save_listing', [$this, 'save_listing']);
        add_action('wp_ajax_delete_listing', [$this, 'delete_listing']);
        add_action('wp_ajax_update_listing_status', [$this, 'update_listing_status']);
        
        // Profile actions
        add_action('wp_ajax_update_agent_profile', [$this, 'update_agent_profile']);
        add_action('wp_ajax_upload_agent_photo', [$this, 'upload_agent_photo']);
    }

    /**
     * Verify nonce for AJAX requests
     */
    private function verify_nonce() {
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        if (!wp_verify_nonce($nonce, 'happy_place_dashboard_nonce')) {
            wp_send_json_error('Invalid security token');
        }
    }

    /**
     * Handle listing save request
     */
    public function save_listing() {
        $this->verify_nonce();

        // Ensure user has permission
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied');
        }

        $listing_data = isset($_POST['listing_data']) ? $_POST['listing_data'] : [];
        if (empty($listing_data)) {
            wp_send_json_error('No listing data provided');
        }

        // Sanitize and validate the data
        $listing_data = $this->sanitize_listing_data($listing_data);

        // Create or update the listing
        $listing_id = isset($listing_data['id']) ? intval($listing_data['id']) : 0;
        
        $post_data = [
            'post_type' => 'listing',
            'post_status' => 'publish',
            'post_title' => $listing_data['title'],
            'post_content' => $listing_data['description']
        ];

        if ($listing_id > 0) {
            $post_data['ID'] = $listing_id;
            $listing_id = wp_update_post($post_data);
        } else {
            $listing_id = wp_insert_post($post_data);
        }

        if (is_wp_error($listing_id)) {
            wp_send_json_error($listing_id->get_error_message());
        }

        // Update listing meta
        $this->update_listing_meta($listing_id, $listing_data);

        wp_send_json_success([
            'message' => __('Listing saved successfully', 'happy-place'),
            'listing_id' => $listing_id
        ]);
    }

    /**
     * Handle listing deletion
     */
    public function delete_listing() {
        $this->verify_nonce();

        if (!current_user_can('delete_posts')) {
            wp_send_json_error('Permission denied');
        }

        $listing_id = isset($_POST['listing_id']) ? intval($_POST['listing_id']) : 0;
        if ($listing_id <= 0) {
            wp_send_json_error('Invalid listing ID');
        }

        // Ensure the listing belongs to the current user
        $listing = get_post($listing_id);
        if (!$listing || $listing->post_author != get_current_user_id()) {
            wp_send_json_error('Permission denied');
        }

        if (wp_delete_post($listing_id, true)) {
            wp_send_json_success(__('Listing deleted successfully', 'happy-place'));
        } else {
            wp_send_json_error(__('Error deleting listing', 'happy-place'));
        }
    }

    /**
     * Update listing status
     */
    public function update_listing_status() {
        $this->verify_nonce();

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied');
        }

        $listing_id = isset($_POST['listing_id']) ? intval($_POST['listing_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        if ($listing_id <= 0 || empty($status)) {
            wp_send_json_error('Invalid parameters');
        }

        // Ensure the listing belongs to the current user
        $listing = get_post($listing_id);
        if (!$listing || $listing->post_author != get_current_user_id()) {
            wp_send_json_error('Permission denied');
        }

        $result = wp_update_post([
            'ID' => $listing_id,
            'post_status' => $status
        ]);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(__('Status updated successfully', 'happy-place'));
    }

    /**
     * Handle agent profile updates
     */
    public function update_agent_profile() {
        $this->verify_nonce();

        if (!is_user_logged_in()) {
            wp_send_json_error('Permission denied');
        }

        $profile_data = isset($_POST['profile_data']) ? $_POST['profile_data'] : [];
        if (empty($profile_data)) {
            wp_send_json_error('No profile data provided');
        }

        $user_id = get_current_user_id();
        $agent_id = get_user_meta($user_id, 'associated_agent_id', true);

        if (!$agent_id) {
            wp_send_json_error('No agent profile found');
        }

        // Update agent post
        $agent_post = [
            'ID' => $agent_id,
            'post_title' => sanitize_text_field($profile_data['name']),
            'post_content' => wp_kses_post($profile_data['bio'])
        ];

        $updated = wp_update_post($agent_post);

        if (is_wp_error($updated)) {
            wp_send_json_error($updated->get_error_message());
        }

        // Update agent meta
        update_post_meta($agent_id, 'phone', sanitize_text_field($profile_data['phone']));
        update_post_meta($agent_id, 'email', sanitize_email($profile_data['email']));
        update_post_meta($agent_id, 'specialties', sanitize_text_field($profile_data['specialties']));

        wp_send_json_success(__('Profile updated successfully', 'happy-place'));
    }

    /**
     * Handle agent photo upload
     */
    public function upload_agent_photo() {
        $this->verify_nonce();

        if (!is_user_logged_in()) {
            wp_send_json_error('Permission denied');
        }

        if (!isset($_FILES['photo'])) {
            wp_send_json_error('No file uploaded');
        }

        $user_id = get_current_user_id();
        $agent_id = get_user_meta($user_id, 'associated_agent_id', true);

        if (!$agent_id) {
            wp_send_json_error('No agent profile found');
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('photo', $agent_id);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error($attachment_id->get_error_message());
        }

        set_post_thumbnail($agent_id, $attachment_id);

        wp_send_json_success([
            'message' => __('Photo uploaded successfully', 'happy-place'),
            'thumbnail_url' => wp_get_attachment_image_url($attachment_id, 'thumbnail')
        ]);
    }

    /**
     * Sanitize listing data
     */
    private function sanitize_listing_data($data) {
        return [
            'id' => isset($data['id']) ? intval($data['id']) : 0,
            'title' => isset($data['title']) ? sanitize_text_field($data['title']) : '',
            'description' => isset($data['description']) ? wp_kses_post($data['description']) : '',
            'price' => isset($data['price']) ? floatval($data['price']) : 0,
            'bedrooms' => isset($data['bedrooms']) ? intval($data['bedrooms']) : 0,
            'bathrooms' => isset($data['bathrooms']) ? floatval($data['bathrooms']) : 0,
            'square_feet' => isset($data['square_feet']) ? intval($data['square_feet']) : 0,
            'address' => isset($data['address']) ? sanitize_text_field($data['address']) : '',
            'city' => isset($data['city']) ? sanitize_text_field($data['city']) : '',
            'state' => isset($data['state']) ? sanitize_text_field($data['state']) : '',
            'zip' => isset($data['zip']) ? sanitize_text_field($data['zip']) : '',
            'features' => isset($data['features']) ? array_map('sanitize_text_field', $data['features']) : []
        ];
    }

    /**
     * Update listing meta data
     */
    private function update_listing_meta($listing_id, $data) {
        update_post_meta($listing_id, 'price', $data['price']);
        update_post_meta($listing_id, 'bedrooms', $data['bedrooms']);
        update_post_meta($listing_id, 'bathrooms', $data['bathrooms']);
        update_post_meta($listing_id, 'square_feet', $data['square_feet']);
        update_post_meta($listing_id, 'address', $data['address']);
        update_post_meta($listing_id, 'city', $data['city']);
        update_post_meta($listing_id, 'state', $data['state']);
        update_post_meta($listing_id, 'zip', $data['zip']);
        update_post_meta($listing_id, 'features', $data['features']);
    }
}
