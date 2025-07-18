<?php
/**
 * Dashboard Data Functions
 * 
 * Functions to support dashboard functionality for agent data,
 * inquiries, open houses, and performance metrics.
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get agent inquiries
 * 
 * @param int $agent_id Agent user ID
 * @param string $status Status filter (pending, responded, closed)
 * @return array Array of inquiry objects
 */
if (!function_exists('hph_get_agent_inquiries')) {
    function hph_get_agent_inquiries($agent_id, $status = 'all') {
        global $wpdb;
        
        // For now, simulate inquiry data since the table doesn't exist yet
        // This will be replaced with actual database queries
        $sample_inquiries = [
            [
                'id' => 1,
                'listing_id' => 123,
                'listing_title' => '123 Main Street',
                'client_name' => 'John Smith',
                'client_email' => 'john@example.com',
                'message' => 'Interested in viewing this property',
                'status' => 'pending',
                'created_date' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ],
            [
                'id' => 2,
                'listing_id' => 124,
                'listing_title' => '456 Oak Avenue',
                'client_name' => 'Sarah Johnson',
                'client_email' => 'sarah@example.com',
                'message' => 'What is the price range for this neighborhood?',
                'status' => 'pending',
                'created_date' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ],
            [
                'id' => 3,
                'listing_id' => 125,
                'listing_title' => '789 Pine Street',
                'client_name' => 'Mike Davis',
                'client_email' => 'mike@example.com',
                'message' => 'Is this property still available?',
                'status' => 'responded',
                'created_date' => date('Y-m-d H:i:s', strtotime('-3 days'))
            ]
        ];
        
        // Filter by status if specified
        if ($status !== 'all') {
            $sample_inquiries = array_filter($sample_inquiries, function($inquiry) use ($status) {
                return $inquiry['status'] === $status;
            });
        }
        
        return $sample_inquiries;
    }
}

/**
 * Get agent open houses
 * 
 * @param int $agent_id Agent user ID
 * @param string $type Type filter (upcoming, past, all)
 * @return array Array of open house objects
 */
if (!function_exists('hph_get_agent_open_houses')) {
    function hph_get_agent_open_houses($agent_id, $type = 'all') {
        global $wpdb;
        
        // For now, simulate open house data
        $sample_open_houses = [
            [
                'id' => 1,
                'listing_id' => 123,
                'listing_title' => '123 Main Street',
                'listing_address' => '123 Main Street, Anytown, ST 12345',
                'event_date' => date('Y-m-d H:i:s', strtotime('+3 days 14:00')),
                'end_time' => date('Y-m-d H:i:s', strtotime('+3 days 17:00')),
                'status' => 'upcoming',
                'expected_attendance' => 25,
                'actual_attendance' => null
            ],
            [
                'id' => 2,
                'listing_id' => 124,
                'listing_title' => '456 Oak Avenue',
                'listing_address' => '456 Oak Avenue, Anytown, ST 12345',
                'event_date' => date('Y-m-d H:i:s', strtotime('+7 days 13:00')),
                'end_time' => date('Y-m-d H:i:s', strtotime('+7 days 16:00')),
                'status' => 'upcoming',
                'expected_attendance' => 30,
                'actual_attendance' => null
            ],
            [
                'id' => 3,
                'listing_id' => 125,
                'listing_title' => '789 Pine Street',
                'listing_address' => '789 Pine Street, Anytown, ST 12345',
                'event_date' => date('Y-m-d H:i:s', strtotime('-5 days 14:00')),
                'end_time' => date('Y-m-d H:i:s', strtotime('-5 days 17:00')),
                'status' => 'completed',
                'expected_attendance' => 20,
                'actual_attendance' => 18
            ]
        ];
        
        // Filter by type if specified
        if ($type !== 'all') {
            $current_time = current_time('timestamp');
            $sample_open_houses = array_filter($sample_open_houses, function($open_house) use ($type, $current_time) {
                $event_time = strtotime($open_house['event_date']);
                
                switch ($type) {
                    case 'upcoming':
                        return $event_time > $current_time;
                    case 'past':
                        return $event_time <= $current_time;
                    default:
                        return true;
                }
            });
        }
        
        return $sample_open_houses;
    }
}

/**
 * Get agent performance data
 * 
 * @param int $agent_id Agent user ID
 * @param string $period Period (monthly, quarterly, yearly)
 * @return array Performance metrics
 */
if (!function_exists('hph_get_agent_performance')) {
    function hph_get_agent_performance($agent_id, $period = 'monthly') {
        // Simulate performance data
        $base_metrics = [
            'listings_viewed' => rand(150, 500),
            'inquiries_received' => rand(10, 35),
            'open_houses_held' => rand(3, 12),
            'properties_sold' => rand(1, 8),
            'revenue_generated' => rand(50000, 200000),
            'conversion_rate' => rand(15, 35),
            'avg_time_to_close' => rand(30, 90)
        ];
        
        // Add period-specific data
        $months = $period === 'monthly' ? 1 : ($period === 'quarterly' ? 3 : 12);
        $chart_data = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} months"));
            $chart_data[] = [
                'date' => $date,
                'views' => rand(120, 400),
                'inquiries' => rand(8, 25),
                'conversions' => rand(1, 5)
            ];
        }
        
        return [
            'metrics' => $base_metrics,
            'chart_data' => $chart_data,
            'period' => $period,
            'last_updated' => current_time('mysql')
        ];
    }
}

/**
 * Get top performing listings for agent
 * 
 * @param int $agent_id Agent user ID
 * @param int $limit Number of listings to return
 * @return array Array of listing objects with performance data
 */
if (!function_exists('hph_get_top_performing_listings')) {
    function hph_get_top_performing_listings($agent_id, $limit = 5) {
        // Get agent's published listings
        $listings = get_posts([
            'author' => $agent_id,
            'post_type' => 'listing',
            'post_status' => 'publish',
            'numberposts' => $limit * 2, // Get more to simulate filtering
            'meta_key' => '_listing_date',
            'orderby' => 'meta_value',
            'order' => 'DESC'
        ]);
        
        $top_listings = [];
        
        foreach ($listings as $listing) {
            // Simulate performance metrics
            $views = rand(50, 500);
            $inquiries = rand(2, 20);
            $favorites = rand(1, 15);
            
            $top_listings[] = [
                'id' => $listing->ID,
                'title' => $listing->post_title,
                'url' => get_permalink($listing->ID),
                'thumbnail' => get_the_post_thumbnail_url($listing->ID, 'medium'),
                'price' => get_post_meta($listing->ID, '_listing_price', true),
                'views' => $views,
                'inquiries' => $inquiries,
                'favorites' => $favorites,
                'performance_score' => ($views * 0.1) + ($inquiries * 5) + ($favorites * 2)
            ];
        }
        
        // Sort by performance score
        usort($top_listings, function($a, $b) {
            return $b['performance_score'] <=> $a['performance_score'];
        });
        
        return array_slice($top_listings, 0, $limit);
    }
}

/**
 * Update agent performance metrics
 * 
 * @param int $agent_id Agent user ID
 * @param string $metric Metric name
 * @param mixed $value Metric value
 * @return bool Success status
 */
if (!function_exists('hph_update_agent_metric')) {
    function hph_update_agent_metric($agent_id, $metric, $value) {
        return update_user_meta($agent_id, "_agent_metric_{$metric}", $value);
    }
}

/**
 * Track listing view for agent
 * 
 * @param int $listing_id Listing post ID
 * @param int $agent_id Agent user ID (optional, will get from listing author)
 * @return bool Success status
 */
if (!function_exists('hph_track_listing_view')) {
    function hph_track_listing_view($listing_id, $agent_id = null) {
        if (!$agent_id) {
            $listing = get_post($listing_id);
            $agent_id = $listing ? $listing->post_author : null;
        }
        
        if (!$agent_id) {
            return false;
        }
        
        // Update total views for agent
        $total_views = get_user_meta($agent_id, '_total_listing_views', true) ?: 0;
        $total_views++;
        update_user_meta($agent_id, '_total_listing_views', $total_views);
        
        // Update monthly views
        $monthly_key = '_monthly_views_' . date('Y_m');
        $monthly_views = get_user_meta($agent_id, $monthly_key, true) ?: 0;
        $monthly_views++;
        update_user_meta($agent_id, $monthly_key, $monthly_views);
        
        return true;
    }
}

/**
 * Get agent dashboard statistics
 * 
 * @param int $agent_id Agent user ID
 * @return array Dashboard statistics
 */
if (!function_exists('hph_get_agent_dashboard_stats')) {
    function hph_get_agent_dashboard_stats($agent_id) {
        $recent_listings = get_posts([
            'author' => $agent_id,
            'post_type' => 'listing',
            'post_status' => 'publish',
            'numberposts' => -1
        ]);
        
        $pending_inquiries = hph_get_agent_inquiries($agent_id, 'pending');
        $upcoming_open_houses = hph_get_agent_open_houses($agent_id, 'upcoming');
        
        return [
            'active_listings' => count($recent_listings),
            'pending_inquiries' => count($pending_inquiries),
            'upcoming_open_houses' => count($upcoming_open_houses),
            'total_views' => get_user_meta($agent_id, '_total_listing_views', true) ?: 0,
            'leads_this_month' => get_user_meta($agent_id, '_leads_this_month', true) ?: 0,
            'listings_change' => 0, // Calculate month-over-month change
            'inquiries_change' => 0 // Calculate month-over-month change
        ];
    }
}

/**
 * AJAX handler for saving agent settings
 */
if (!function_exists('hph_ajax_save_agent_settings')) {
    function hph_ajax_save_agent_settings() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'hph_dashboard_nonce')) {
            wp_send_json_error(__('Security check failed.', 'happy-place'));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to save settings.', 'happy-place'));
        }
        
        $user_id = get_current_user_id();
        $errors = [];
        $updated = [];
        
        // Update display name
        if (isset($_POST['display_name']) && !empty($_POST['display_name'])) {
            $display_name = sanitize_text_field($_POST['display_name']);
            if (wp_update_user(['ID' => $user_id, 'display_name' => $display_name])) {
                $updated[] = 'display_name';
            } else {
                $errors[] = __('Failed to update display name.', 'happy-place');
            }
        }
        
        // Update email
        if (isset($_POST['user_email']) && !empty($_POST['user_email'])) {
            $email = sanitize_email($_POST['user_email']);
            if (is_email($email)) {
                if (wp_update_user(['ID' => $user_id, 'user_email' => $email])) {
                    $updated[] = 'email';
                } else {
                    $errors[] = __('Failed to update email address.', 'happy-place');
                }
            } else {
                $errors[] = __('Invalid email address.', 'happy-place');
            }
        }
        
        // Update bio/description
        if (isset($_POST['description'])) {
            $description = sanitize_textarea_field($_POST['description']);
            if (wp_update_user(['ID' => $user_id, 'description' => $description])) {
                $updated[] = 'bio';
            }
        }
        
        // Update user meta fields
        $meta_fields = [
            'agent_phone' => 'sanitize_text_field',
            'default_dashboard_section' => 'sanitize_text_field',
            'email_notifications' => 'absint',
            'dashboard_tips' => 'absint'
        ];
        
        foreach ($meta_fields as $field => $sanitize_func) {
            if (isset($_POST[$field])) {
                $value = $sanitize_func($_POST[$field]);
                update_user_meta($user_id, $field, $value);
                $updated[] = $field;
            }
        }
        
        // Handle password change
        if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // Verify current password
            $user = wp_get_current_user();
            if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
                $errors[] = __('Current password is incorrect.', 'happy-place');
            } elseif ($new_password !== $confirm_password) {
                $errors[] = __('New passwords do not match.', 'happy-place');
            } elseif (strlen($new_password) < 8) {
                $errors[] = __('New password must be at least 8 characters long.', 'happy-place');
            } else {
                wp_set_password($new_password, $user_id);
                $updated[] = 'password';
            }
        }
        
        if (empty($errors)) {
            wp_send_json_success([
                'message' => __('Settings saved successfully!', 'happy-place'),
                'updated' => $updated
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Some settings could not be saved.', 'happy-place'),
                'errors' => $errors,
                'updated' => $updated
            ]);
        }
    }
    
    add_action('wp_ajax_hph_save_agent_settings', 'hph_ajax_save_agent_settings');
}

/**
 * AJAX handler for loading listing form
 */
if (!function_exists('hph_ajax_load_listing_form')) {
    function hph_ajax_load_listing_form() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'hph_dashboard_nonce')) {
            wp_die('Invalid nonce');
        }
        
        // Check user permissions
        if (!current_user_can('edit_posts')) {
            wp_die('Insufficient permissions');
        }
        
        // Set listing ID if editing
        if (isset($_POST['listing_id'])) {
            $_GET['listing_id'] = intval($_POST['listing_id']);
        }
        
        // Include the listing form template
        include get_template_directory() . '/templates/template-parts/dashboard/listing-form.php';
        
        wp_die();
    }
    
    add_action('wp_ajax_hph_load_listing_form', 'hph_ajax_load_listing_form');
}

/**
 * AJAX handler for saving listings
 */
if (!function_exists('hph_ajax_save_listing')) {
    function hph_ajax_save_listing() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'hph_listing_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check user permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $current_user = wp_get_current_user();
        $listing_id = isset($_POST['listing_id']) ? intval($_POST['listing_id']) : 0;
        $is_editing = $listing_id > 0;
        $post_status = isset($_POST['post_status']) ? sanitize_text_field($_POST['post_status']) : 'draft';
        
        // Validate required fields
        $required_fields = ['listing_title', 'listing_price', 'listing_type', 'property_type', 'street_address', 'city', 'state', 'zip_code'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(sprintf('Field %s is required', str_replace('_', ' ', $field)));
            }
        }
        
        // Prepare post data
        $post_data = [
            'post_title' => sanitize_text_field($_POST['listing_title']),
            'post_content' => wp_kses_post($_POST['listing_description'] ?? ''),
            'post_status' => $post_status,
            'post_type' => 'listing',
            'post_author' => $current_user->ID,
        ];
        
        if ($is_editing) {
            // Verify ownership
            $existing_post = get_post($listing_id);
            if (!$existing_post || $existing_post->post_author != $current_user->ID) {
                wp_send_json_error('Invalid listing or insufficient permissions');
            }
            
            $post_data['ID'] = $listing_id;
            $result = wp_update_post($post_data);
        } else {
            $result = wp_insert_post($post_data);
            $listing_id = $result;
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error('Failed to save listing: ' . $result->get_error_message());
        }
        
        // Save listing meta
        $meta_fields = [
            '_listing_price' => 'floatval',
            '_listing_bedrooms' => 'intval',
            '_listing_bathrooms' => 'floatval',
            '_listing_square_feet' => 'intval',
            '_listing_lot_size' => 'intval',
            '_listing_property_type' => 'sanitize_text_field',
            '_listing_type' => 'sanitize_text_field',
            '_listing_street_address' => 'sanitize_text_field',
            '_listing_city' => 'sanitize_text_field',
            '_listing_state' => 'sanitize_text_field',
            '_listing_zip_code' => 'sanitize_text_field',
            '_listing_year_built' => 'intval',
            '_listing_garage_spaces' => 'intval',
            '_listing_virtual_tour_url' => 'esc_url_raw',
            '_listing_agent_notes' => 'sanitize_textarea_field',
        ];
        
        foreach ($meta_fields as $meta_key => $sanitize_func) {
            $field_name = ltrim($meta_key, '_');
            if (isset($_POST[$field_name])) {
                $value = call_user_func($sanitize_func, $_POST[$field_name]);
                update_post_meta($listing_id, $meta_key, $value);
            }
        }
        
        // Handle features
        if (isset($_POST['features']) && is_array($_POST['features'])) {
            $features = array_map('sanitize_text_field', $_POST['features']);
            update_post_meta($listing_id, '_listing_features', $features);
        } else {
            delete_post_meta($listing_id, '_listing_features');
        }
        
        // Handle file uploads
        if (!empty($_FILES)) {
            $upload_result = hph_handle_listing_file_uploads($listing_id, $_FILES);
            if (is_wp_error($upload_result)) {
                wp_send_json_error('File upload failed: ' . $upload_result->get_error_message());
            }
        }
        
        // Set listing date
        update_post_meta($listing_id, '_listing_date', current_time('mysql'));
        
        wp_send_json_success([
            'message' => $is_editing ? 'Listing updated successfully' : 'Listing created successfully',
            'listing_id' => $listing_id,
            'post_status' => $post_status,
            'edit_url' => admin_url('post.php?post=' . $listing_id . '&action=edit')
        ]);
    }
    
    add_action('wp_ajax_hph_save_listing', 'hph_ajax_save_listing');
}

/**
 * Handle file uploads for listings
 */
if (!function_exists('hph_handle_listing_file_uploads')) {
    function hph_handle_listing_file_uploads($listing_id, $files) {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $upload_overrides = ['test_form' => false];
        
        // Handle featured image
        if (isset($files['featured_image']) && !empty($files['featured_image']['name'])) {
            $uploaded_file = wp_handle_upload($files['featured_image'], $upload_overrides);
            
            if (!isset($uploaded_file['error'])) {
                $attachment_id = wp_insert_attachment([
                    'post_mime_type' => $uploaded_file['type'],
                    'post_title' => preg_replace('/\.[^.]+$/', '', basename($uploaded_file['file'])),
                    'post_content' => '',
                    'post_status' => 'inherit'
                ], $uploaded_file['file'], $listing_id);
                
                if (!is_wp_error($attachment_id)) {
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    $attachment_data = wp_generate_attachment_metadata($attachment_id, $uploaded_file['file']);
                    wp_update_attachment_metadata($attachment_id, $attachment_data);
                    set_post_thumbnail($listing_id, $attachment_id);
                }
            }
        }
        
        // Handle gallery images
        if (isset($files['gallery_images']) && !empty($files['gallery_images']['name'][0])) {
            $gallery_ids = [];
            
            foreach ($files['gallery_images']['name'] as $key => $value) {
                if (!empty($value)) {
                    $file = [
                        'name' => $files['gallery_images']['name'][$key],
                        'type' => $files['gallery_images']['type'][$key],
                        'tmp_name' => $files['gallery_images']['tmp_name'][$key],
                        'error' => $files['gallery_images']['error'][$key],
                        'size' => $files['gallery_images']['size'][$key]
                    ];
                    
                    $uploaded_file = wp_handle_upload($file, $upload_overrides);
                    
                    if (!isset($uploaded_file['error'])) {
                        $attachment_id = wp_insert_attachment([
                            'post_mime_type' => $uploaded_file['type'],
                            'post_title' => preg_replace('/\.[^.]+$/', '', basename($uploaded_file['file'])),
                            'post_content' => '',
                            'post_status' => 'inherit'
                        ], $uploaded_file['file'], $listing_id);
                        
                        if (!is_wp_error($attachment_id)) {
                            require_once(ABSPATH . 'wp-admin/includes/image.php');
                            $attachment_data = wp_generate_attachment_metadata($attachment_id, $uploaded_file['file']);
                            wp_update_attachment_metadata($attachment_id, $attachment_data);
                            $gallery_ids[] = $attachment_id;
                        }
                    }
                }
            }
            
            if (!empty($gallery_ids)) {
                update_post_meta($listing_id, '_listing_gallery', $gallery_ids);
            }
        }
        
        return true;
    }
}

/**
 * AJAX handler for generating listing marketing materials
 */
if (!function_exists('hph_ajax_generate_listing_marketing')) {
    function hph_ajax_generate_listing_marketing() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'hph_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check user permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $listing_id = isset($_POST['listing_id']) ? intval($_POST['listing_id']) : 0;
        $marketing_type = isset($_POST['marketing_type']) ? sanitize_text_field($_POST['marketing_type']) : '';
        
        if (!$listing_id || !$marketing_type) {
            wp_send_json_error('Missing required parameters');
        }
        
        // Verify listing ownership
        $listing = get_post($listing_id);
        if (!$listing || $listing->post_author != get_current_user_id()) {
            wp_send_json_error('Invalid listing or insufficient permissions');
        }
        
        try {
            switch ($marketing_type) {
                case 'flyer':
                    if (class_exists('HappyPlace\Graphics\Flyer_Generator')) {
                        $generator = \HappyPlace\Graphics\Flyer_Generator::get_instance();
                        $result = $generator->generate_flyer($listing_id);
                        wp_send_json_success(['flyer_url' => $result]);
                    } else {
                        wp_send_json_error('Flyer generator not available');
                    }
                    break;
                    
                case 'social_media':
                    if (class_exists('HappyPlace\Graphics\Social_Media_Graphics')) {
                        $platform = isset($_POST['platform']) ? sanitize_text_field($_POST['platform']) : 'facebook';
                        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'listing';
                        
                        $generator = new \HappyPlace\Graphics\Social_Media_Graphics();
                        $result = $generator->generate_listing_graphic($listing_id, $platform, $type);
                        
                        if (is_wp_error($result)) {
                            wp_send_json_error($result->get_error_message());
                        } else {
                            wp_send_json_success(['graphic_url' => $result]);
                        }
                    } else {
                        wp_send_json_error('Social media generator not available');
                    }
                    break;
                    
                default:
                    wp_send_json_error('Invalid marketing type');
            }
        } catch (Exception $e) {
            wp_send_json_error('Generation failed: ' . $e->getMessage());
        }
    }
    
    add_action('wp_ajax_hph_generate_listing_marketing', 'hph_ajax_generate_listing_marketing');
}
