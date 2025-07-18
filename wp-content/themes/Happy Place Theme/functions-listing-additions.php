<?php
/**
 * Add these functions to your functions.php file
 */

// Enqueue listing-specific assets
function enqueue_listing_assets() {
    if (is_singular('listing')) {
        wp_enqueue_script(
            'single-listing',
            get_template_directory_uri() . '/assets/dist/js/main.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('single-listing', 'wpAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('listing_ajax_nonce'),
        ));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_listing_assets');

// AJAX handler for contact form
function handle_listing_contact_form() {
    if (!wp_verify_nonce($_POST['listing_contact_nonce'], 'listing_contact_form')) {
        wp_send_json_error('Security check failed.');
    }
    
    $listing_id = intval($_POST['listing_id']);
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone']);
    $message = sanitize_textarea_field($_POST['message']);
    
    if (empty($first_name) || empty($last_name) || empty($email)) {
        wp_send_json_error('Please fill in all required fields.');
    }
    
    $listing_title = get_the_title($listing_id);
    $agent_id = get_post_meta($listing_id, 'listing_agent', true);
    $agent_email = get_post_meta($agent_id, 'agent_email', true);
    
    if (!$agent_email) {
        $agent_email = get_option('admin_email');
    }
    
    $subject = 'Listing Inquiry: ' . $listing_title;
    $email_message = "
New inquiry for listing: {$listing_title}

Name: {$first_name} {$last_name}
Email: {$email}
Phone: {$phone}

Message:
{$message}

Listing URL: " . get_permalink($listing_id);
    
    $sent = wp_mail($agent_email, $subject, $email_message);
    
    if ($sent) {
        wp_send_json_success('Message sent successfully!');
    } else {
        wp_send_json_error('Failed to send message. Please try again.');
    }
}
add_action('wp_ajax_listing_contact_form', 'handle_listing_contact_form');
add_action('wp_ajax_nopriv_listing_contact_form', 'handle_listing_contact_form');

// AJAX handler for saving favorites
function handle_save_favorite() {
    $listing_id = intval($_POST['listing_id']);
    $is_favorite = $_POST['is_favorite'] === 'true';
    
    if (!is_user_logged_in()) {
        wp_send_json_error('You must be logged in to save favorites.');
    }
    
    $user_id = get_current_user_id();
    $favorites = get_user_meta($user_id, 'favorite_listings', true);
    
    if (!is_array($favorites)) {
        $favorites = array();
    }
    
    if ($is_favorite) {
        if (!in_array($listing_id, $favorites)) {
            $favorites[] = $listing_id;
        }
    } else {
        $favorites = array_filter($favorites, function($id) use ($listing_id) {
            return $id != $listing_id;
        });
    }
    
    update_user_meta($user_id, 'favorite_listings', $favorites);
    wp_send_json_success();
}
add_action('wp_ajax_save_favorite', 'handle_save_favorite');

// AJAX handler for saving listings
function handle_save_listing() {
    $listing_id = intval($_POST['listing_id']);
    
    if (!is_user_logged_in()) {
        wp_send_json_error('You must be logged in to save listings.');
    }
    
    $user_id = get_current_user_id();
    $saved_listings = get_user_meta($user_id, 'saved_listings', true);
    
    if (!is_array($saved_listings)) {
        $saved_listings = array();
    }
    
    if (!in_array($listing_id, $saved_listings)) {
        $saved_listings[] = $listing_id;
        update_user_meta($user_id, 'saved_listings', $saved_listings);
    }
    
    wp_send_json_success('Listing saved successfully!');
}
add_action('wp_ajax_save_listing', 'handle_save_listing');

// Add custom meta boxes for listing fields
function add_listing_meta_boxes() {
    add_meta_box(
        'listing_details',
        'Listing Details',
        'listing_details_callback',
        'listing',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_listing_meta_boxes');

function listing_details_callback($post) {
    wp_nonce_field('listing_details_nonce', 'listing_details_nonce');
    
    $fields = array(
        'listing_price' => 'Price',
        'listing_status' => 'Status',
        'listing_address' => 'Address',
        'mls_number' => 'MLS Number',
        'bedrooms' => 'Bedrooms',
        'bathrooms' => 'Bathrooms',
        'square_feet' => 'Square Feet',
        'lot_size' => 'Lot Size',
        'year_built' => 'Year Built',
        'listing_agent' => 'Agent ID',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude',
        'walk_score' => 'Walk Score',
        'school_rating' => 'School Rating',
    );
    
    echo '<table class="form-table">';
    foreach ($fields as $key => $label) {
        $value = get_post_meta($post->ID, $key, true);
        echo '<tr>';
        echo '<th><label for="' . $key . '">' . $label . '</label></th>';
        echo '<td><input type="text" id="' . $key . '" name="' . $key . '" value="' . esc_attr($value) . '" style="width: 100%;" /></td>';
        echo '</tr>';
    }
    echo '</table>';
}

// Save listing meta
function save_listing_meta($post_id) {
    if (!isset($_POST['listing_details_nonce']) || !wp_verify_nonce($_POST['listing_details_nonce'], 'listing_details_nonce')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    $fields = array(
        'listing_price', 'listing_status', 'listing_address', 'mls_number',
        'bedrooms', 'bathrooms', 'square_feet', 'lot_size', 'year_built',
        'listing_agent', 'latitude', 'longitude', 'walk_score', 'school_rating'
    );
    
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
}
add_action('save_post', 'save_listing_meta');

// Register listing post type if not already registered
function register_listing_post_type() {
    if (!post_type_exists('listing')) {
        register_post_type('listing', array(
            'labels' => array(
                'name' => 'Listings',
                'singular_name' => 'Listing',
                'menu_name' => 'Listings',
                'add_new' => 'Add New',
                'add_new_item' => 'Add New Listing',
                'edit_item' => 'Edit Listing',
                'new_item' => 'New Listing',
                'view_item' => 'View Listing',
                'search_items' => 'Search Listings',
                'not_found' => 'No listings found',
                'not_found_in_trash' => 'No listings found in trash'
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'listings'),
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'menu_position' => 5,
            'menu_icon' => 'dashicons-admin-home',
            'show_in_rest' => true,
        ));
    }
}
add_action('init', 'register_listing_post_type');
