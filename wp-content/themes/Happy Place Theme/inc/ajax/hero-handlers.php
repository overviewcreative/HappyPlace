<?php
/**
 * AJAX Handlers for Hero Component
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize hero AJAX handlers
 */
function hph_init_hero_ajax_handlers() {
    // Handle favorite toggle for logged-in and non-logged-in users
    add_action('wp_ajax_hph_toggle_favorite', 'hph_handle_toggle_favorite');
    add_action('wp_ajax_nopriv_hph_toggle_favorite', 'hph_handle_toggle_favorite');
    
    // Handle tour scheduling
    add_action('wp_ajax_hph_schedule_tour', 'hph_handle_schedule_tour');
    add_action('wp_ajax_nopriv_hph_schedule_tour', 'hph_handle_schedule_tour');
}
add_action('init', 'hph_init_hero_ajax_handlers');

/**
 * Handle favorite toggle AJAX request
 */
function hph_handle_toggle_favorite() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'hph_hero_nonce')) {
        wp_send_json_error('Invalid security token');
        return;
    }
    
    $listing_id = intval($_POST['listing_id']);
    
    if (!$listing_id) {
        wp_send_json_error('Invalid listing ID');
        return;
    }
    
    // Get current user
    $user_id = get_current_user_id();
    
    if (!$user_id) {
        // For non-logged-in users, use session/cookies
        if (!session_id()) {
            session_start();
        }
        
        if (!isset($_SESSION['hph_favorites'])) {
            $_SESSION['hph_favorites'] = [];
        }
        
        $is_favorite = in_array($listing_id, $_SESSION['hph_favorites']);
        
        if ($is_favorite) {
            // Remove from favorites
            $_SESSION['hph_favorites'] = array_diff($_SESSION['hph_favorites'], [$listing_id]);
            $action = 'removed';
        } else {
            // Add to favorites
            $_SESSION['hph_favorites'][] = $listing_id;
            $action = 'added';
        }
        
        wp_send_json_success([
            'action' => $action,
            'is_favorite' => !$is_favorite,
            'message' => $action === 'added' ? 'Added to favorites' : 'Removed from favorites'
        ]);
        
    } else {
        // For logged-in users, use user meta
        $favorites = get_user_meta($user_id, 'hph_favorite_listings', true);
        if (!is_array($favorites)) {
            $favorites = [];
        }
        
        $is_favorite = in_array($listing_id, $favorites);
        
        if ($is_favorite) {
            // Remove from favorites
            $favorites = array_diff($favorites, [$listing_id]);
            $action = 'removed';
        } else {
            // Add to favorites
            $favorites[] = $listing_id;
            $action = 'added';
        }
        
        // Update user meta
        update_user_meta($user_id, 'hph_favorite_listings', $favorites);
        
        wp_send_json_success([
            'action' => $action,
            'is_favorite' => !$is_favorite,
            'message' => $action === 'added' ? 'Added to favorites' : 'Removed from favorites'
        ]);
    }
}

/**
 * Handle tour scheduling AJAX request
 */
function hph_handle_schedule_tour() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'hph_hero_nonce')) {
        wp_send_json_error('Invalid security token');
        return;
    }
    
    $listing_id = intval($_POST['listing_id']);
    $user_name = sanitize_text_field($_POST['user_name'] ?? '');
    $user_email = sanitize_email($_POST['user_email'] ?? '');
    $user_phone = sanitize_text_field($_POST['user_phone'] ?? '');
    $preferred_date = sanitize_text_field($_POST['preferred_date'] ?? '');
    $message = sanitize_textarea_field($_POST['message'] ?? '');
    
    if (!$listing_id) {
        wp_send_json_error('Invalid listing ID');
        return;
    }
    
    if (empty($user_name) || empty($user_email)) {
        wp_send_json_error('Name and email are required');
        return;
    }
    
    // Get listing information
    $listing = get_post($listing_id);
    if (!$listing || $listing->post_type !== 'listing') {
        wp_send_json_error('Listing not found');
        return;
    }
    
    // Get agent information
    $agent_id = get_field('agent', $listing_id);
    $agent_email = '';
    $agent_name = '';
    
    if ($agent_id) {
        $agent_email = get_field('email', $agent_id);
        $agent_name = get_the_title($agent_id);
    }
    
    // Fallback to site admin if no agent
    if (!$agent_email) {
        $agent_email = get_option('admin_email');
        $agent_name = get_bloginfo('name');
    }
    
    // Create tour request entry (you may want to create a custom post type for this)
    $tour_request_id = wp_insert_post([
        'post_type' => 'tour_request',
        'post_status' => 'publish',
        'post_title' => "Tour Request for {$listing->post_title}",
        'meta_input' => [
            'listing_id' => $listing_id,
            'requester_name' => $user_name,
            'requester_email' => $user_email,
            'requester_phone' => $user_phone,
            'preferred_date' => $preferred_date,
            'message' => $message,
            'agent_id' => $agent_id,
            'request_date' => current_time('mysql')
        ]
    ]);
    
    // Send email notification to agent
    $subject = "New Tour Request for {$listing->post_title}";
    $email_message = "
        New tour request received:
        
        Property: {$listing->post_title}
        Requester: {$user_name}
        Email: {$user_email}
        Phone: {$user_phone}
        Preferred Date: {$preferred_date}
        Message: {$message}
        
        Please contact the requester to schedule the tour.
    ";
    
    $sent = wp_mail($agent_email, $subject, $email_message);
    
    if ($sent) {
        wp_send_json_success([
            'message' => 'Tour request sent successfully! The agent will contact you soon.',
            'tour_request_id' => $tour_request_id
        ]);
    } else {
        wp_send_json_error('Failed to send tour request. Please try again.');
    }
}
?>
