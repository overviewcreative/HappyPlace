<?php
/**
 * Template Utility Functions
 * Additional helper functions for theme templates
 * 
 * @package Happy_Place_Theme
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get listing price formatted
 */
function hph_get_listing_price($listing_id = null) {
    if (!$listing_id) {
        $listing_id = get_the_ID();
    }
    
    $price = get_post_meta($listing_id, 'property_price', true);
    
    if (!$price) {
        return __('Price on Request', 'happy-place');
    }
    
    return '$' . number_format($price);
}

/**
 * Get listing details (beds, baths, sqft)
 */
function hph_get_listing_details($listing_id = null) {
    if (!$listing_id) {
        $listing_id = get_the_ID();
    }
    
    $details = [];
    
    $bedrooms = get_post_meta($listing_id, 'property_bedrooms', true);
    if ($bedrooms) {
        $details['bedrooms'] = $bedrooms . ' ' . _n('bed', 'beds', $bedrooms, 'happy-place');
    }
    
    $bathrooms = get_post_meta($listing_id, 'property_bathrooms', true);
    if ($bathrooms) {
        $details['bathrooms'] = $bathrooms . ' ' . _n('bath', 'baths', $bathrooms, 'happy-place');
    }
    
    $sqft = get_post_meta($listing_id, 'property_square_feet', true);
    if ($sqft) {
        $details['sqft'] = number_format($sqft) . ' sq ft';
    }
    
    return $details;
}

/**
 * Get listing status with proper styling class
 */
function hph_get_listing_status($listing_id = null) {
    if (!$listing_id) {
        $listing_id = get_the_ID();
    }
    
    $status = get_post_meta($listing_id, 'property_status', true);
    
    $statuses = [
        'active' => ['label' => __('For Sale', 'happy-place'), 'class' => 'status-active'],
        'sold' => ['label' => __('Sold', 'happy-place'), 'class' => 'status-sold'],
        'pending' => ['label' => __('Pending', 'happy-place'), 'class' => 'status-pending'],
        'withdrawn' => ['label' => __('Withdrawn', 'happy-place'), 'class' => 'status-withdrawn'],
    ];
    
    return $statuses[$status] ?? $statuses['active'];
}

/**
 * Get agent information
 */
function hph_get_agent_info($agent_id = null) {
    if (!$agent_id) {
        global $post;
        $agent_id = get_post_meta($post->ID, 'listing_agent', true);
    }
    
    if (!$agent_id) {
        return null;
    }
    
    $agent = get_userdata($agent_id);
    if (!$agent) {
        return null;
    }
    
    return [
        'id' => $agent_id,
        'name' => get_user_meta($agent_id, 'name', true) ?: $agent->display_name,
        'email' => get_user_meta($agent_id, 'email', true) ?: $agent->user_email,
        'phone' => get_user_meta($agent_id, 'phone', true),
        'photo' => get_user_meta($agent_id, 'agent_photo', true),
        'bio' => get_user_meta($agent_id, 'bio', true),
        'title' => get_user_meta($agent_id, 'title', true),
    ];
}

/**
 * Display formatted address
 */
function hph_get_formatted_address($listing_id = null) {
    if (!$listing_id) {
        $listing_id = get_the_ID();
    }
    
    $address_parts = [];
    
    $street = get_post_meta($listing_id, 'property_address', true);
    if ($street) {
        $address_parts[] = $street;
    }
    
    $city = get_post_meta($listing_id, 'property_city', true);
    $state = get_post_meta($listing_id, 'property_state', true);
    $zip = get_post_meta($listing_id, 'property_zip', true);
    
    $city_state_zip = [];
    if ($city) $city_state_zip[] = $city;
    if ($state) $city_state_zip[] = $state;
    if ($zip) $city_state_zip[] = $zip;
    
    if (!empty($city_state_zip)) {
        $address_parts[] = implode(', ', $city_state_zip);
    }
    
    return implode('<br>', $address_parts);
}

/**
 * Get listing gallery images
 */
function hph_get_listing_gallery($listing_id = null) {
    if (!$listing_id) {
        $listing_id = get_the_ID();
    }
    
    $gallery = get_post_meta($listing_id, 'property_gallery', true);
    
    if (empty($gallery)) {
        // Fallback to featured image
        $featured_id = get_post_thumbnail_id($listing_id);
        if ($featured_id) {
            return [['ID' => $featured_id]];
        }
        return [];
    }
    
    return is_array($gallery) ? $gallery : [];
}

/**
 * Generate breadcrumbs
 */
function hph_breadcrumbs() {
    if (is_front_page()) {
        return;
    }
    
    $breadcrumbs = ['<a href="' . home_url('/') . '">' . __('Home', 'happy-place') . '</a>'];
    
    if (is_post_type_archive()) {
        $post_type = get_post_type_object(get_post_type());
        $breadcrumbs[] = '<span>' . $post_type->labels->name . '</span>';
    } elseif (is_singular()) {
        $post_type = get_post_type_object(get_post_type());
        if ($post_type->public && $post_type->name !== 'page') {
            $breadcrumbs[] = '<a href="' . get_post_type_archive_link(get_post_type()) . '">' . $post_type->labels->name . '</a>';
        }
        $breadcrumbs[] = '<span>' . get_the_title() . '</span>';
    } elseif (is_search()) {
        $breadcrumbs[] = '<span>' . sprintf(__('Search Results for "%s"', 'happy-place'), get_search_query()) . '</span>';
    }
    
    echo '<nav class="breadcrumbs" aria-label="Breadcrumb">';
    echo implode(' <span class="separator">/</span> ', $breadcrumbs);
    echo '</nav>';
}

/**
 * Get social sharing links
 */
function hph_get_social_shares($url = null, $title = null) {
    if (!$url) {
        $url = get_permalink();
    }
    if (!$title) {
        $title = get_the_title();
    }
    
    $encoded_url = urlencode($url);
    $encoded_title = urlencode($title);
    
    return [
        'facebook' => "https://www.facebook.com/sharer/sharer.php?u={$encoded_url}",
        'twitter' => "https://twitter.com/intent/tweet?url={$encoded_url}&text={$encoded_title}",
        'linkedin' => "https://www.linkedin.com/sharing/share-offsite/?url={$encoded_url}",
        'email' => "mailto:?subject={$encoded_title}&body={$encoded_url}",
    ];
}

/**
 * Format phone number for display
 */
function hph_format_phone($phone) {
    if (!$phone) {
        return '';
    }
    
    // Remove all non-numeric characters
    $cleaned = preg_replace('/[^0-9]/', '', $phone);
    
    // Format as (XXX) XXX-XXXX if 10 digits
    if (strlen($cleaned) === 10) {
        return sprintf('(%s) %s-%s',
            substr($cleaned, 0, 3),
            substr($cleaned, 3, 3),
            substr($cleaned, 6, 4)
        );
    }
    
    return $phone; // Return original if can't format
}

/**
 * Get relative time string
 */
function hph_get_relative_time($timestamp) {
    return sprintf(
        __('%s ago', 'happy-place'),
        human_time_diff($timestamp, current_time('timestamp'))
    );
}

/**
 * Check if user can edit listing
 */
function hph_user_can_edit_listing($listing_id = null, $user_id = null) {
    if (!$listing_id) {
        $listing_id = get_the_ID();
    }
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    // Admin can edit everything
    if (user_can($user_id, 'manage_options')) {
        return true;
    }
    
    // Check if user is the listing agent
    $listing_agent = get_post_meta($listing_id, 'listing_agent', true);
    if ($listing_agent && $listing_agent == $user_id) {
        return true;
    }
    
    // Check if user is the post author
    $post = get_post($listing_id);
    if ($post && $post->post_author == $user_id) {
        return true;
    }
    
    return false;
}

/**
 * Get listing contact form
 */
function hph_get_listing_contact_form($listing_id = null) {
    if (!$listing_id) {
        $listing_id = get_the_ID();
    }
    
    $agent = hph_get_agent_info();
    
    ob_start();
    ?>
    <form class="listing-contact-form" method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
        <input type="hidden" name="action" value="hph_listing_contact">
        <input type="hidden" name="listing_id" value="<?php echo esc_attr($listing_id); ?>">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('hph_listing_contact'); ?>">
        
        <div class="form-group">
            <label for="contact_name"><?php _e('Name', 'happy-place'); ?> *</label>
            <input type="text" id="contact_name" name="contact_name" required>
        </div>
        
        <div class="form-group">
            <label for="contact_email"><?php _e('Email', 'happy-place'); ?> *</label>
            <input type="email" id="contact_email" name="contact_email" required>
        </div>
        
        <div class="form-group">
            <label for="contact_phone"><?php _e('Phone', 'happy-place'); ?></label>
            <input type="tel" id="contact_phone" name="contact_phone">
        </div>
        
        <div class="form-group">
            <label for="contact_message"><?php _e('Message', 'happy-place'); ?></label>
            <textarea id="contact_message" name="contact_message" rows="4" placeholder="<?php esc_attr_e('I am interested in this property...', 'happy-place'); ?>"></textarea>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">
                <?php _e('Send Message', 'happy-place'); ?>
            </button>
        </div>
    </form>
    <?php
    return ob_get_clean();
}

/**
 * Get property features list
 */
function hph_get_property_features($listing_id = null) {
    if (!$listing_id) {
        $listing_id = get_the_ID();
    }
    
    $features = get_post_meta($listing_id, 'property_features', true);
    
    if (empty($features)) {
        return [];
    }
    
    return is_array($features) ? $features : explode(',', $features);
}