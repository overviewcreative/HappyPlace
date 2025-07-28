<?php
/**
 * AJAX Handlers for Advanced Multistep Listing Form
 * 
 * This file contains all WordPress AJAX endpoints to handle:
 * - Listing form submission (save & publish)
 * - Draft saving (auto-save)
 * - Media upload integration
 * - Media deletion
 * - Google Places API integration
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class HPH_Advanced_Form_AJAX {
    
    public function __construct() {
        // Form submission handlers
        add_action('wp_ajax_hph_save_listing', array($this, 'save_listing'));
        add_action('wp_ajax_hph_save_listing_draft', array($this, 'save_listing_draft'));
        
        // Media handlers
        add_action('wp_ajax_hph_upload_media', array($this, 'upload_media'));
        add_action('wp_ajax_hph_delete_media', array($this, 'delete_media'));
        add_action('wp_ajax_hph_update_media', array($this, 'update_media'));
        add_action('wp_ajax_hph_set_featured_media', array($this, 'set_featured_media'));
        
        // Google Places integration
        add_action('wp_ajax_hph_google_places_autocomplete', array($this, 'google_places_autocomplete'));
        
        // Auto-save handler
        add_action('wp_ajax_hph_auto_save_listing', array($this, 'auto_save_listing'));
        
        // Validation handlers
        add_action('wp_ajax_hph_validate_listing_step', array($this, 'validate_listing_step'));
    }
    
    /**
     * Save and publish listing
     */
    public function save_listing() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'hph_advanced_form_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('publish_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $listing_data = $this->sanitize_listing_data($_POST['listingData']);
        $listing_id = isset($_POST['listingId']) ? intval($_POST['listingId']) : 0;
        
        try {
            // Create or update listing post
            $post_data = array(
                'post_title'    => sanitize_text_field($listing_data['title']),
                'post_content'  => wp_kses_post($listing_data['description']),
                'post_status'   => 'publish',
                'post_type'     => 'listing',
                'post_author'   => get_current_user_id(),
            );
            
            if ($listing_id > 0) {
                $post_data['ID'] = $listing_id;
                $listing_id = wp_update_post($post_data);
            } else {
                $listing_id = wp_insert_post($post_data);
            }
            
            if (is_wp_error($listing_id)) {
                throw new Exception('Failed to save listing: ' . $listing_id->get_error_message());
            }
            
            // Save ACF fields using the existing plugin's field mappings
            $this->save_acf_fields($listing_id, $listing_data);
            
            // Handle media attachments
            $this->handle_media_attachments($listing_id, $listing_data['media']);
            
            // Set featured image
            if (!empty($listing_data['featuredImage'])) {
                set_post_thumbnail($listing_id, intval($listing_data['featuredImage']));
            }
            
            // Use existing plugin services if available
            if (class_exists('HPH_Listing_Calculations')) {
                $calculator = new HPH_Listing_Calculations();
                $calculator->update_listing_calculations($listing_id);
            }
            
            // Trigger geocoding if address changed
            if (class_exists('HPH_Geocoding_Service')) {
                $geocoding = new HPH_Geocoding_Service();
                $geocoding->geocode_listing($listing_id);
            }
            
            wp_send_json_success(array(
                'message' => 'Listing published successfully!',
                'listingId' => $listing_id,
                'redirectUrl' => admin_url('edit.php?post_type=listing')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Error saving listing: ' . $e->getMessage());
        }
    }
    
    /**
     * Save listing as draft
     */
    public function save_listing_draft() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'hph_advanced_form_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $listing_data = $this->sanitize_listing_data($_POST['listingData']);
        $listing_id = isset($_POST['listingId']) ? intval($_POST['listingId']) : 0;
        
        try {
            // Create or update listing post as draft
            $post_data = array(
                'post_title'    => sanitize_text_field($listing_data['title'] ?: 'Draft Listing'),
                'post_content'  => wp_kses_post($listing_data['description']),
                'post_status'   => 'draft',
                'post_type'     => 'listing',
                'post_author'   => get_current_user_id(),
            );
            
            if ($listing_id > 0) {
                $post_data['ID'] = $listing_id;
                $listing_id = wp_update_post($post_data);
            } else {
                $listing_id = wp_insert_post($post_data);
            }
            
            if (is_wp_error($listing_id)) {
                throw new Exception('Failed to save draft: ' . $listing_id->get_error_message());
            }
            
            // Save ACF fields
            $this->save_acf_fields($listing_id, $listing_data);
            
            // Handle media attachments
            if (!empty($listing_data['media'])) {
                $this->handle_media_attachments($listing_id, $listing_data['media']);
            }
            
            wp_send_json_success(array(
                'message' => 'Draft saved successfully!',
                'listingId' => $listing_id,
                'timestamp' => current_time('mysql')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Error saving draft: ' . $e->getMessage());
        }
    }
    
    /**
     * Auto-save functionality
     */
    public function auto_save_listing() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'hph_advanced_form_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $listing_data = $this->sanitize_listing_data($_POST['listingData']);
        $listing_id = isset($_POST['listingId']) ? intval($_POST['listingId']) : 0;
        
        try {
            // Create or update auto-save
            if ($listing_id > 0) {
                // Update existing post
                wp_update_post(array(
                    'ID' => $listing_id,
                    'post_title' => sanitize_text_field($listing_data['title'] ?: 'Auto-save'),
                    'post_content' => wp_kses_post($listing_data['description']),
                ));
            } else {
                // Create new draft
                $listing_id = wp_insert_post(array(
                    'post_title'    => 'Auto-save',
                    'post_content'  => wp_kses_post($listing_data['description']),
                    'post_status'   => 'auto-draft',
                    'post_type'     => 'listing',
                    'post_author'   => get_current_user_id(),
                ));
            }
            
            if (!is_wp_error($listing_id)) {
                // Save form data to post meta for auto-save
                update_post_meta($listing_id, '_hph_auto_save_data', $listing_data);
                update_post_meta($listing_id, '_hph_auto_save_timestamp', current_time('timestamp'));
            }
            
            wp_send_json_success(array(
                'listingId' => $listing_id,
                'timestamp' => current_time('mysql')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Auto-save failed');
        }
    }
    
    /**
     * Upload media files
     */
    public function upload_media() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'hph_advanced_form_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('upload_files')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        if (empty($_FILES['file'])) {
            wp_send_json_error('No file uploaded');
            return;
        }
        
        $file = $_FILES['file'];
        $category = sanitize_text_field($_POST['category'] ?? 'general');
        
        // Validate file type
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp');
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error('Invalid file type. Only JPEG, PNG, GIF, and WebP files are allowed.');
            return;
        }
        
        // Validate file size (10MB max)
        $max_size = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $max_size) {
            wp_send_json_error('File too large. Maximum size is 10MB.');
            return;
        }
        
        try {
            // Handle the upload
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            
            $upload = wp_handle_upload($file, array('test_form' => false));
            
            if (isset($upload['error'])) {
                throw new Exception($upload['error']);
            }
            
            // Create attachment
            $attachment_data = array(
                'post_mime_type' => $upload['type'],
                'post_title'     => sanitize_file_name(pathinfo($upload['file'], PATHINFO_FILENAME)),
                'post_content'   => '',
                'post_status'    => 'inherit'
            );
            
            $attachment_id = wp_insert_attachment($attachment_data, $upload['file']);
            
            if (is_wp_error($attachment_id)) {
                throw new Exception('Failed to create attachment');
            }
            
            // Generate metadata
            $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
            wp_update_attachment_metadata($attachment_id, $attachment_metadata);
            
            // Save category
            update_post_meta($attachment_id, '_hph_media_category', $category);
            
            // Get image data
            $image_data = $this->get_media_data($attachment_id);
            
            wp_send_json_success($image_data);
            
        } catch (Exception $e) {
            wp_send_json_error('Upload failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete media file
     */
    public function delete_media() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'hph_advanced_form_nonce')) {
            wp_die('Security check failed');
        }
        
        $media_id = intval($_POST['mediaId']);
        
        // Check if user owns the media or has delete permissions
        if (!current_user_can('delete_posts') && get_post_field('post_author', $media_id) != get_current_user_id()) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        if (wp_delete_attachment($media_id, true)) {
            wp_send_json_success('Media deleted successfully');
        } else {
            wp_send_json_error('Failed to delete media');
        }
    }
    
    /**
     * Update media metadata
     */
    public function update_media() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'hph_advanced_form_nonce')) {
            wp_die('Security check failed');
        }
        
        $media_id = intval($_POST['mediaId']);
        $updates = $_POST['updates'];
        
        // Check permissions
        if (!current_user_can('edit_posts') && get_post_field('post_author', $media_id) != get_current_user_id()) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        try {
            // Update title if provided
            if (!empty($updates['title'])) {
                wp_update_post(array(
                    'ID' => $media_id,
                    'post_title' => sanitize_text_field($updates['title'])
                ));
            }
            
            // Update category if provided
            if (!empty($updates['category'])) {
                update_post_meta($media_id, '_hph_media_category', sanitize_text_field($updates['category']));
            }
            
            // Update alt text if provided
            if (isset($updates['alt'])) {
                update_post_meta($media_id, '_wp_attachment_image_alt', sanitize_text_field($updates['alt']));
            }
            
            wp_send_json_success('Media updated successfully');
            
        } catch (Exception $e) {
            wp_send_json_error('Failed to update media: ' . $e->getMessage());
        }
    }
    
    /**
     * Set featured media
     */
    public function set_featured_media() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'hph_advanced_form_nonce')) {
            wp_die('Security check failed');
        }
        
        $media_id = intval($_POST['mediaId']);
        $listing_id = intval($_POST['listingId']);
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        if (set_post_thumbnail($listing_id, $media_id)) {
            wp_send_json_success('Featured image set successfully');
        } else {
            wp_send_json_error('Failed to set featured image');
        }
    }
    
    /**
     * Google Places autocomplete
     */
    public function google_places_autocomplete() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'hph_advanced_form_nonce')) {
            wp_die('Security check failed');
        }
        
        $query = sanitize_text_field($_POST['query']);
        $api_key = get_option('hph_google_places_api_key');
        
        if (empty($api_key)) {
            wp_send_json_error('Google Places API key not configured');
            return;
        }
        
        if (empty($query)) {
            wp_send_json_error('Query parameter required');
            return;
        }
        
        try {
            $url = 'https://maps.googleapis.com/maps/api/place/autocomplete/json?' . http_build_query(array(
                'input' => $query,
                'types' => 'address',
                'key' => $api_key,
                'sessiontoken' => wp_create_nonce('google_places_session_' . get_current_user_id())
            ));
            
            $response = wp_remote_get($url, array(
                'timeout' => 10,
                'sslverify' => true
            ));
            
            if (is_wp_error($response)) {
                throw new Exception('Failed to connect to Google Places API');
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if ($data['status'] === 'OK') {
                wp_send_json_success($data['predictions']);
            } else {
                throw new Exception('Google Places API error: ' . $data['status']);
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Autocomplete failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Validate listing step
     */
    public function validate_listing_step() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'hph_advanced_form_nonce')) {
            wp_die('Security check failed');
        }
        
        $step = intval($_POST['step']);
        $data = $this->sanitize_listing_data($_POST['data']);
        
        $errors = array();
        
        switch ($step) {
            case 1: // Property Basics
                if (empty($data['title'])) {
                    $errors['title'] = 'Property title is required';
                }
                if (empty($data['address'])) {
                    $errors['address'] = 'Address is required';
                }
                if (empty($data['propertyType'])) {
                    $errors['propertyType'] = 'Property type is required';
                }
                break;
                
            case 2: // Property Details
                if (empty($data['bedrooms']) || intval($data['bedrooms']) < 0) {
                    $errors['bedrooms'] = 'Valid number of bedrooms is required';
                }
                if (empty($data['bathrooms']) || intval($data['bathrooms']) < 0) {
                    $errors['bathrooms'] = 'Valid number of bathrooms is required';
                }
                if (empty($data['squareFootage']) || intval($data['squareFootage']) <= 0) {
                    $errors['squareFootage'] = 'Valid square footage is required';
                }
                break;
                
            case 5: // Pricing
                if (empty($data['price']) || floatval($data['price']) <= 0) {
                    $errors['price'] = 'Valid price is required';
                }
                break;
        }
        
        if (empty($errors)) {
            wp_send_json_success('Step validation passed');
        } else {
            wp_send_json_error($errors);
        }
    }
    
    /**
     * Sanitize listing data
     */
    private function sanitize_listing_data($data) {
        if (is_string($data)) {
            $data = json_decode(stripslashes($data), true);
        }
        
        if (!is_array($data)) {
            return array();
        }
        
        return array(
            // Basic Info
            'title' => sanitize_text_field($data['title'] ?? ''),
            'description' => wp_kses_post($data['description'] ?? ''),
            'address' => sanitize_text_field($data['address'] ?? ''),
            'city' => sanitize_text_field($data['city'] ?? ''),
            'state' => sanitize_text_field($data['state'] ?? ''),
            'zipCode' => sanitize_text_field($data['zipCode'] ?? ''),
            'country' => sanitize_text_field($data['country'] ?? ''),
            'latitude' => floatval($data['latitude'] ?? 0),
            'longitude' => floatval($data['longitude'] ?? 0),
            
            // Property Details
            'propertyType' => sanitize_text_field($data['propertyType'] ?? ''),
            'bedrooms' => intval($data['bedrooms'] ?? 0),
            'bathrooms' => floatval($data['bathrooms'] ?? 0),
            'squareFootage' => intval($data['squareFootage'] ?? 0),
            'lotSize' => floatval($data['lotSize'] ?? 0),
            'yearBuilt' => intval($data['yearBuilt'] ?? 0),
            'garageSpaces' => intval($data['garageSpaces'] ?? 0),
            'stories' => intval($data['stories'] ?? 1),
            
            // Features
            'features' => $this->sanitize_features($data['features'] ?? array()),
            
            // Pricing
            'price' => floatval($data['price'] ?? 0),
            'priceType' => sanitize_text_field($data['priceType'] ?? 'sale'),
            'currency' => sanitize_text_field($data['currency'] ?? 'USD'),
            'downPayment' => floatval($data['downPayment'] ?? 0),
            'monthlyPayment' => floatval($data['monthlyPayment'] ?? 0),
            'hoaFees' => floatval($data['hoaFees'] ?? 0),
            'propertyTax' => floatval($data['propertyTax'] ?? 0),
            'insurance' => floatval($data['insurance'] ?? 0),
            
            // Status
            'status' => sanitize_text_field($data['status'] ?? 'available'),
            
            // Media
            'media' => $this->sanitize_media_array($data['media'] ?? array()),
            'featuredImage' => intval($data['featuredImage'] ?? 0),
        );
    }
    
    /**
     * Sanitize features array
     */
    private function sanitize_features($features) {
        $sanitized = array();
        
        if (is_array($features)) {
            foreach ($features as $category => $items) {
                if (is_array($items)) {
                    $sanitized[sanitize_key($category)] = array_map('sanitize_text_field', $items);
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize media array
     */
    private function sanitize_media_array($media) {
        if (!is_array($media)) {
            return array();
        }
        
        return array_map('intval', $media);
    }
    
    /**
     * Save ACF fields - Updated to work with existing plugin structure
     */
    private function save_acf_fields($listing_id, $data) {
        // Ensure ACF is available
        if (!function_exists('update_field')) {
            return;
        }
        
        // Map form data to existing ACF field names based on CSV import structure
        $field_mappings = array(
            // Basic property info
            'title' => 'property_title',
            'description' => 'listing_description',
            'address' => 'street_address',  // Plugin uses 'street_address'
            'city' => 'city',
            'state' => 'region',  // Plugin uses 'region' for state
            'zipCode' => 'zip_code',
            'propertyType' => 'property_type',
            
            // Property details (matching CSV import structure)
            'bedrooms' => 'bedrooms',
            'bathrooms' => 'bathrooms',
            'squareFootage' => 'square_footage',
            'lotSize' => 'lot_size',
            'yearBuilt' => 'year_built',
            'garageSpaces' => 'garage_spaces',
            'stories' => 'stories',
            
            // Financial
            'price' => 'price',  // Plugin uses simple 'price' field
            'priceType' => 'listing_type',
            'propertyTax' => 'property_tax',
            'hoaFees' => 'hoa_fees',
            'status' => 'listing_status',
        );
        
        // Update fields based on mapping
        foreach ($field_mappings as $form_key => $acf_key) {
            if (isset($data[$form_key]) && !empty($data[$form_key])) {
                update_field($acf_key, $data[$form_key], $listing_id);
            }
        }
        
        // Handle features separately if they exist
        if (!empty($data['features'])) {
            foreach ($data['features'] as $category => $items) {
                update_field('features_' . $category, $items, $listing_id);
            }
        }
        
        // Handle media gallery
        if (!empty($data['media'])) {
            update_field('property_images', $data['media'], $listing_id);
        }
        
        // Set coordinates for mapping (used by existing plugin services)
        if (!empty($data['latitude']) && !empty($data['longitude'])) {
            update_field('latitude', $data['latitude'], $listing_id);
            update_field('longitude', $data['longitude'], $listing_id);
        }
        
        // Add any additional plugin-specific fields
        update_field('listing_source', 'manual_form', $listing_id);
        update_field('form_version', 'advanced_multistep', $listing_id);
        update_field('last_updated', current_time('mysql'), $listing_id);
    }
    
    /**
     * Handle media attachments
     */
    private function handle_media_attachments($listing_id, $media_ids) {
        if (empty($media_ids) || !is_array($media_ids)) {
            return;
        }
        
        foreach ($media_ids as $media_id) {
            // Attach media to the listing
            wp_update_post(array(
                'ID' => intval($media_id),
                'post_parent' => $listing_id
            ));
        }
    }
    
    /**
     * Get media data for response
     */
    private function get_media_data($attachment_id) {
        $attachment = get_post($attachment_id);
        $metadata = wp_get_attachment_metadata($attachment_id);
        
        return array(
            'id' => $attachment_id,
            'title' => $attachment->post_title,
            'filename' => basename(get_attached_file($attachment_id)),
            'url' => wp_get_attachment_url($attachment_id),
            'thumbnail' => wp_get_attachment_image_url($attachment_id, 'thumbnail'),
            'medium' => wp_get_attachment_image_url($attachment_id, 'medium'),
            'category' => get_post_meta($attachment_id, '_hph_media_category', true) ?: 'general',
            'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
            'filesize' => isset($metadata['filesize']) ? $metadata['filesize'] : filesize(get_attached_file($attachment_id)),
            'dimensions' => isset($metadata['width']) ? $metadata['width'] . ' × ' . $metadata['height'] : '',
            'uploaded' => get_the_date('Y-m-d H:i:s', $attachment_id)
        );
    }
}

// Initialize the AJAX handlers only if we're in an AJAX context or admin area
if (defined('DOING_AJAX') && DOING_AJAX || is_admin()) {
    new HPH_Advanced_Form_AJAX();
}
