<?php
/**
 * Open House Form Handler
 * 
 * @package HappyPlace
 * @subpackage Forms
 */

namespace HappyPlace\Forms;

if (!defined('ABSPATH')) {
    exit;
}

class Open_House_Handler extends Form_Handler {
    /**
     * Get form action name
     * 
     * @return string
     */
    protected function get_action() {
        return 'hph_save_open_house';
    }

    /**
     * Validate form data
     * 
     * @param array $data Form data
     * @return bool
     */
    protected function validate($data) {
        // Check required fields
        $required_fields = ['listing_id', 'open_house_date', 'open_house_start_time', 'open_house_end_time'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $this->add_error(__('This field is required', 'happy-place'), $field);
                return false;
            }
        }

        // Validate date
        $date = $data['open_house_date'];
        if (strtotime($date) < strtotime('today')) {
            $this->add_error(__('Date must be in the future', 'happy-place'), 'open_house_date');
            return false;
        }

        // Validate time range
        $start_time = strtotime($date . ' ' . $data['open_house_start_time']);
        $end_time = strtotime($date . ' ' . $data['open_house_end_time']);
        
        if ($start_time >= $end_time) {
            $this->add_error(__('End time must be after start time', 'happy-place'), 'open_house_end_time');
            return false;
        }

        // Validate listing ownership
        $listing_id = intval($data['listing_id']);
        $listing = get_post($listing_id);
        
        if (!$listing || $listing->post_type !== 'listing' || $listing->post_author != get_current_user_id()) {
            $this->add_error(__('Invalid listing selected', 'happy-place'), 'listing_id');
            return false;
        }

        return true;
    }

    /**
     * Sanitize form data
     * 
     * @param array $data Form data
     * @return array
     */
    protected function sanitize($data) {
        $sanitized = [];

        // Text fields
        $text_fields = ['open_house_title', 'open_house_host', 'open_house_notes'];
        foreach ($text_fields as $field) {
            $sanitized[$field] = isset($data[$field]) ? sanitize_text_field($data[$field]) : '';
        }

        // Integer fields
        $int_fields = ['listing_id', 'open_house_max_visitors'];
        foreach ($int_fields as $field) {
            $sanitized[$field] = isset($data[$field]) ? absint($data[$field]) : 0;
        }

        // Date/time fields
        $sanitized['open_house_date'] = sanitize_text_field($data['open_house_date']);
        $sanitized['open_house_start_time'] = sanitize_text_field($data['open_house_start_time']);
        $sanitized['open_house_end_time'] = sanitize_text_field($data['open_house_end_time']);

        // Boolean fields
        $bool_fields = ['open_house_virtual_option', 'open_house_refreshments', 'open_house_registration_required'];
        foreach ($bool_fields as $field) {
            $sanitized[$field] = isset($data[$field]) ? (bool)$data[$field] : false;
        }

        return $sanitized;
    }

    /**
     * Save form data
     * 
     * @param array $data Sanitized form data
     * @return int Post ID
     */
    protected function save($data) {
        $listing_id = $data['listing_id'];
        $open_house_id = isset($data['open_house_id']) ? absint($data['open_house_id']) : 0;

        // Get listing info
        $listing = get_post($listing_id);
        $listing_title = $listing->post_title;
        $listing_address = get_post_meta($listing_id, 'listing_address', true);

        // Prepare event title
        $event_title = !empty($data['open_house_title']) 
            ? $data['open_house_title']
            : sprintf(
                __('Open House: %s - %s', 'happy-place'),
                $listing_address ?: $listing_title,
                $data['open_house_date']
            );

        // Prepare post data
        $post_data = array(
            'post_title' => $event_title,
            'post_content' => $data['open_house_notes'],
            'post_type' => 'listing', // We're storing as listing meta now
            'post_status' => 'publish',
        );

        // Update existing or create new meta
        $events = (array)get_post_meta($listing_id, 'listing_open_houses', true);
        
        $event_data = [
            'id' => $open_house_id ?: uniqid('oh_'),
            'date' => $data['open_house_date'],
            'start_time' => $data['open_house_start_time'],
            'end_time' => $data['open_house_end_time'],
            'host' => $data['open_house_host'],
            'max_visitors' => $data['open_house_max_visitors'],
            'virtual_option' => $data['open_house_virtual_option'],
            'refreshments' => $data['open_house_refreshments'],
            'registration_required' => $data['open_house_registration_required'],
            'title' => $event_title,
            'notes' => $data['open_house_notes'],
            'created_at' => current_time('mysql'),
            'created_by' => get_current_user_id(),
        ];

        if ($open_house_id) {
            // Update existing event
            foreach ($events as &$event) {
                if ($event['id'] === $open_house_id) {
                    $event = array_merge($event, $event_data);
                    break;
                }
            }
        } else {
            // Add new event
            $events[] = $event_data;
        }

        // Sort events by date/time
        usort($events, function($a, $b) {
            $date_a = strtotime($a['date'] . ' ' . $a['start_time']);
            $date_b = strtotime($b['date'] . ' ' . $b['start_time']);
            return $date_a - $date_b;
        });

        // Save to listing meta
        update_post_meta($listing_id, 'listing_open_houses', $events);

        // Set success message
        $this->set_success_message(
            $open_house_id 
                ? __('Open house updated successfully', 'happy-place')
                : __('Open house scheduled successfully', 'happy-place')
        );

        // Set redirect URL
        $this->set_redirect_url(
            add_query_arg(
                ['section' => 'open-houses', 'listing_id' => $listing_id],
                admin_url('admin.php?page=happy-place-dashboard')
            )
        );

        return $listing_id;
    }
}
