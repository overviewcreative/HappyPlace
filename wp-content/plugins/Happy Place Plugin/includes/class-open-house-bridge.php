<?php
/**
 * Open House Bridge Functions
 * Provides standardized access to open house data including hosting agent info
 * 
 * @package HappyPlace
 */

namespace HappyPlace\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Open_House_Bridge {
    
    /**
     * Get open house data for a listing
     * 
     * @param int $listing_id
     * @return array|false
     */
    public static function get_open_house_data($listing_id) {
        if (!$listing_id) {
            return false;
        }
        
        // Get ACF fields
        $open_house_date = get_field('open_house_date', $listing_id);
        $start_time = get_field('open_house_start_time', $listing_id);
        $end_time = get_field('open_house_end_time', $listing_id);
        $hosting_agent_id = get_field('hosting_agent', $listing_id);
        $notes = get_field('open_house_notes', $listing_id);
        $status = get_field('open_house_status', $listing_id);
        
        // Return false if no open house scheduled
        if (!$open_house_date || !$start_time || !$end_time) {
            return false;
        }
        
        // Get hosting agent data
        $hosting_agent = self::get_hosting_agent_data($hosting_agent_id);
        
        // Fallback to listing agent if no hosting agent specified
        if (!$hosting_agent) {
            $listing_author = get_post_field('post_author', $listing_id);
            $hosting_agent = self::get_hosting_agent_data($listing_author);
        }
        
        return array(
            'date' => $open_house_date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'formatted_date' => self::format_open_house_date($open_house_date),
            'formatted_time' => self::format_open_house_time($start_time, $end_time),
            'hosting_agent' => $hosting_agent,
            'notes' => $notes,
            'status' => $status ?: 'scheduled',
            'is_active' => self::is_open_house_active($open_house_date, $start_time, $end_time),
            'is_upcoming' => self::is_open_house_upcoming($open_house_date, $start_time),
        );
    }
    
    /**
     * Get hosting agent data
     * 
     * @param int $agent_id
     * @return array|false
     */
    public static function get_hosting_agent_data($agent_id) {
        if (!$agent_id) {
            return false;
        }
        
        $agent = get_userdata($agent_id);
        if (!$agent) {
            return false;
        }
        
        // Get agent profile data
        $first_name = get_user_meta($agent_id, 'first_name', true);
        $last_name = get_user_meta($agent_id, 'last_name', true);
        $phone = get_user_meta($agent_id, 'agent_phone', true);
        $email = $agent->user_email;
        $license_number = get_user_meta($agent_id, 'license_number', true);
        $bio = get_user_meta($agent_id, 'agent_bio', true);
        $photo_id = get_user_meta($agent_id, 'agent_photo', true);
        
        // Get agent photo URL
        $photo_url = '';
        if ($photo_id) {
            $photo_url = wp_get_attachment_image_url($photo_id, 'medium');
        }
        
        // Fallback to default avatar if no photo
        if (!$photo_url) {
            $photo_url = get_avatar_url($agent_id, array('size' => 150));
        }
        
        return array(
            'id' => $agent_id,
            'name' => trim($first_name . ' ' . $last_name) ?: $agent->display_name,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'license_number' => $license_number,
            'bio' => $bio,
            'photo_url' => $photo_url,
            'display_name' => $agent->display_name,
        );
    }
    
    /**
     * Format open house date for display
     * 
     * @param string $date
     * @return string
     */
    public static function format_open_house_date($date) {
        if (!$date) {
            return '';
        }
        
        $date_obj = \DateTime::createFromFormat('Y-m-d', $date);
        if (!$date_obj) {
            return $date;
        }
        
        return $date_obj->format('l, F j, Y'); // e.g., "Saturday, March 15, 2024"
    }
    
    /**
     * Format open house time range for display
     * 
     * @param string $start_time
     * @param string $end_time
     * @return string
     */
    public static function format_open_house_time($start_time, $end_time) {
        if (!$start_time || !$end_time) {
            return '';
        }
        
        $start = \DateTime::createFromFormat('H:i:s', $start_time);
        $end = \DateTime::createFromFormat('H:i:s', $end_time);
        
        if (!$start || !$end) {
            return $start_time . ' - ' . $end_time;
        }
        
        return $start->format('g:i A') . ' - ' . $end->format('g:i A'); // e.g., "1:00 PM - 4:00 PM"
    }
    
    /**
     * Check if open house is currently active
     * 
     * @param string $date
     * @param string $start_time
     * @param string $end_time
     * @return bool
     */
    public static function is_open_house_active($date, $start_time, $end_time) {
        if (!$date || !$start_time || !$end_time) {
            return false;
        }
        
        $now = new \DateTime();
        $today = $now->format('Y-m-d');
        $current_time = $now->format('H:i:s');
        
        return ($date === $today && $current_time >= $start_time && $current_time <= $end_time);
    }
    
    /**
     * Check if open house is upcoming
     * 
     * @param string $date
     * @param string $start_time
     * @return bool
     */
    public static function is_open_house_upcoming($date, $start_time) {
        if (!$date || !$start_time) {
            return false;
        }
        
        $now = new \DateTime();
        $open_house_datetime = \DateTime::createFromFormat('Y-m-d H:i:s', $date . ' ' . $start_time);
        
        return ($open_house_datetime && $open_house_datetime > $now);
    }
    
    /**
     * Get all upcoming open houses for an agent
     * 
     * @param int $agent_id
     * @return array
     */
    public static function get_agent_open_houses($agent_id) {
        $meta_query = array(
            'relation' => 'AND',
            array(
                'key' => 'hosting_agent',
                'value' => $agent_id,
                'compare' => '='
            ),
            array(
                'key' => 'open_house_date',
                'value' => date('Y-m-d'),
                'compare' => '>='
            )
        );
        
        $listings = get_posts(array(
            'post_type' => 'listing',
            'posts_per_page' => -1,
            'meta_query' => $meta_query,
            'meta_key' => 'open_house_date',
            'orderby' => 'meta_value',
            'order' => 'ASC'
        ));
        
        $open_houses = array();
        foreach ($listings as $listing) {
            $open_house_data = self::get_open_house_data($listing->ID);
            if ($open_house_data) {
                $open_house_data['listing_id'] = $listing->ID;
                $open_house_data['listing_title'] = $listing->post_title;
                $open_house_data['listing_address'] = get_field('street_address', $listing->ID);
                $open_houses[] = $open_house_data;
            }
        }
        
        return $open_houses;
    }
    
    /**
     * Get flyer data for open house flyer generation
     * 
     * @param int $listing_id
     * @return array|false
     */
    public static function get_open_house_flyer_data($listing_id) {
        $open_house_data = self::get_open_house_data($listing_id);
        if (!$open_house_data) {
            return false;
        }
        
        // Get listing data
        $listing = get_post($listing_id);
        if (!$listing) {
            return false;
        }
        
        // Get listing fields
        $address = get_field('street_address', $listing_id);
        $city = get_field('city', $listing_id);
        $state = get_field('state', $listing_id);
        $zip = get_field('zip_code', $listing_id);
        $price = get_field('price', $listing_id);
        $bedrooms = get_field('bedrooms', $listing_id);
        $bathrooms = get_field('bathrooms', $listing_id);
        $square_footage = get_field('square_footage', $listing_id);
        $featured_image_id = get_post_thumbnail_id($listing_id);
        
        // Format full address
        $full_address = $address;
        if ($city) $full_address .= ', ' . $city;
        if ($state) $full_address .= ', ' . $state;
        if ($zip) $full_address .= ' ' . $zip;
        
        return array(
            'listing' => array(
                'id' => $listing_id,
                'title' => $listing->post_title,
                'address' => $full_address,
                'price' => $price,
                'bedrooms' => $bedrooms,
                'bathrooms' => $bathrooms,
                'square_footage' => $square_footage,
                'featured_image' => $featured_image_id ? wp_get_attachment_image_url($featured_image_id, 'large') : '',
            ),
            'openHouse' => $open_house_data,
            'hostingAgent' => $open_house_data['hosting_agent']
        );
    }
}