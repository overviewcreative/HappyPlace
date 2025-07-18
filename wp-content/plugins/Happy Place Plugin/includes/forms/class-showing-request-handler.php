<?php
/**
 * Showing Request Form Handler
 * 
 * @package HappyPlace
 * @subpackage Forms
 */

namespace HappyPlace\Forms;

if (!defined('ABSPATH')) {
    exit;
}

class Showing_Request_Handler extends Form_Handler {
    /**
     * Get form action name
     * 
     * @return string
     */
    protected function get_action() {
        return 'hph_request_showing';
    }

    /**
     * Validate form data
     * 
     * @param array $data Form data
     * @return bool
     */
    protected function validate($data) {
        // Check required fields
        $required_fields = [
            'listing_id',
            'preferred_date',
            'preferred_time',
            'name',
            'email',
            'phone'
        ];

        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $this->add_error(__('This field is required', 'happy-place'), $field);
                return false;
            }
        }

        // Validate email
        if (!is_email($data['email'])) {
            $this->add_error(__('Please enter a valid email address', 'happy-place'), 'email');
            return false;
        }

        // Validate date
        $date = $data['preferred_date'];
        if (strtotime($date) < strtotime('today')) {
            $this->add_error(__('Date must be in the future', 'happy-place'), 'preferred_date');
            return false;
        }

        // Validate listing exists
        $listing_id = intval($data['listing_id']);
        $listing = get_post($listing_id);
        
        if (!$listing || $listing->post_type !== 'listing') {
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
        return array(
            'listing_id' => absint($data['listing_id']),
            'preferred_date' => sanitize_text_field($data['preferred_date']),
            'preferred_time' => sanitize_text_field($data['preferred_time']),
            'alternate_date' => isset($data['alternate_date']) ? sanitize_text_field($data['alternate_date']) : '',
            'alternate_time' => isset($data['alternate_time']) ? sanitize_text_field($data['alternate_time']) : '',
            'name' => sanitize_text_field($data['name']),
            'email' => sanitize_email($data['email']),
            'phone' => sanitize_text_field($data['phone']),
            'message' => isset($data['message']) ? sanitize_textarea_field($data['message']) : '',
            'financing' => isset($data['financing']) ? sanitize_text_field($data['financing']) : '',
            'preapproved' => isset($data['preapproved']) ? (bool)$data['preapproved'] : false,
        );
    }

    /**
     * Save form data
     * 
     * @param array $data Sanitized form data
     * @return int Lead ID
     */
    protected function save($data) {
        // Create lead
        $lead_data = array(
            'post_title' => sprintf(
                __('Showing Request: %s - %s', 'happy-place'),
                get_the_title($data['listing_id']),
                $data['name']
            ),
            'post_type' => 'lead',
            'post_status' => 'publish',
            'meta_input' => array(
                'lead_type' => 'showing_request',
                'lead_name' => $data['name'],
                'lead_email' => $data['email'],
                'lead_phone' => $data['phone'],
                'lead_source' => 'showing_request',
                'listing_id' => $data['listing_id'],
                'preferred_date' => $data['preferred_date'],
                'preferred_time' => $data['preferred_time'],
                'alternate_date' => $data['alternate_date'],
                'alternate_time' => $data['alternate_time'],
                'message' => $data['message'],
                'financing_type' => $data['financing'],
                'is_preapproved' => $data['preapproved'],
                'status' => 'new'
            )
        );

        $lead_id = wp_insert_post($lead_data);

        if (is_wp_error($lead_id)) {
            throw new \Exception($lead_id->get_error_message());
        }

        // Send notifications
        $this->send_notifications($lead_id, $data);

        $this->set_success_message(
            __('Your showing request has been submitted. We will contact you shortly to confirm the details.', 'happy-place')
        );

        return $lead_id;
    }

    /**
     * Send notifications
     * 
     * @param int $lead_id Lead ID
     * @param array $data Form data
     */
    protected function send_notifications($lead_id, $data) {
        // Get listing agent
        $listing = get_post($data['listing_id']);
        $agent_id = $listing->post_author;
        $agent = get_userdata($agent_id);

        // Send agent notification
        $agent_message = sprintf(
            __('New showing request for %s
            
Name: %s
Email: %s
Phone: %s
Preferred Date: %s
Preferred Time: %s
            
View lead: %s', 'happy-place'),
            get_the_title($data['listing_id']),
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['preferred_date'],
            $data['preferred_time'],
            admin_url('post.php?post=' . $lead_id . '&action=edit')
        );

        wp_mail(
            $agent->user_email,
            __('New Showing Request', 'happy-place'),
            $agent_message
        );

        // Send requester confirmation
        $requester_message = sprintf(
            __('Thank you for requesting a showing of %s.

We have received your request for:
Date: %s
Time: %s

One of our agents will contact you shortly to confirm the showing details.

Best regards,
%s', 'happy-place'),
            get_the_title($data['listing_id']),
            $data['preferred_date'],
            $data['preferred_time'],
            get_bloginfo('name')
        );

        wp_mail(
            $data['email'],
            __('Showing Request Confirmation', 'happy-place'),
            $requester_message
        );
    }
}
