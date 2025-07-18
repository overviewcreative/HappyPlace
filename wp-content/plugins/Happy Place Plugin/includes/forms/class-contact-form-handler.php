<?php
/**
 * Contact Form Handler
 * 
 * @package HappyPlace
 * @subpackage Forms
 */

namespace HappyPlace\Forms;

if (!defined('ABSPATH')) {
    exit;
}

class Contact_Form_Handler extends Form_Handler {
    /**
     * Get form action name
     * 
     * @return string
     */
    protected function get_action() {
        return 'hph_contact_form';
    }

    /**
     * Validate form data
     * 
     * @param array $data Form data
     * @return bool
     */
    protected function validate($data) {
        // Check required fields
        $required_fields = ['name', 'email', 'message'];
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

        // Validate message length
        if (strlen($data['message']) < 10) {
            $this->add_error(__('Please enter a message of at least 10 characters', 'happy-place'), 'message');
            return false;
        }

        // If agent_id is set, validate agent exists
        if (!empty($data['agent_id'])) {
            $agent = get_user_by('id', $data['agent_id']);
            if (!$agent || !in_array('agent', (array)$agent->roles)) {
                $this->add_error(__('Invalid agent selected', 'happy-place'), 'agent_id');
                return false;
            }
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
            'name' => sanitize_text_field($data['name']),
            'email' => sanitize_email($data['email']),
            'phone' => isset($data['phone']) ? sanitize_text_field($data['phone']) : '',
            'subject' => isset($data['subject']) ? sanitize_text_field($data['subject']) : '',
            'message' => sanitize_textarea_field($data['message']),
            'agent_id' => isset($data['agent_id']) ? absint($data['agent_id']) : 0,
            'interest' => isset($data['interest']) ? sanitize_text_field($data['interest']) : '',
            'newsletter_signup' => isset($data['newsletter_signup']) ? (bool)$data['newsletter_signup'] : false,
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
                __('Contact Form: %s', 'happy-place'),
                $data['name']
            ),
            'post_type' => 'lead',
            'post_status' => 'publish',
            'post_content' => $data['message'],
            'meta_input' => array(
                'lead_type' => 'contact_form',
                'lead_name' => $data['name'],
                'lead_email' => $data['email'],
                'lead_phone' => $data['phone'],
                'lead_source' => 'contact_form',
                'subject' => $data['subject'],
                'interest' => $data['interest'],
                'newsletter_signup' => $data['newsletter_signup'],
                'status' => 'new'
            )
        );

        // If agent selected, assign to them
        if ($data['agent_id']) {
            $lead_data['post_author'] = $data['agent_id'];
        }

        $lead_id = wp_insert_post($lead_data);

        if (is_wp_error($lead_id)) {
            throw new \Exception($lead_id->get_error_message());
        }

        // Send notifications
        $this->send_notifications($lead_id, $data);

        $this->set_success_message(
            __('Thank you for your message. We will get back to you shortly.', 'happy-place')
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
        // Get recipient
        $to = $data['agent_id'] 
            ? get_user_by('id', $data['agent_id'])->user_email
            : get_option('admin_email');

        // Send admin/agent notification
        $admin_message = sprintf(
            __('New contact form submission
            
From: %s
Email: %s
Phone: %s
Subject: %s
Interest: %s
Message:
%s
            
View lead: %s', 'happy-place'),
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['subject'],
            $data['interest'],
            $data['message'],
            admin_url('post.php?post=' . $lead_id . '&action=edit')
        );

        wp_mail(
            $to,
            __('New Contact Form Submission', 'happy-place'),
            $admin_message
        );

        // Send submitter confirmation
        $submitter_message = sprintf(
            __('Thank you for contacting %s.

We have received your message and will get back to you shortly.

Your message:
%s

Best regards,
%s', 'happy-place'),
            get_bloginfo('name'),
            $data['message'],
            get_bloginfo('name')
        );

        wp_mail(
            $data['email'],
            __('Contact Form Confirmation', 'happy-place'),
            $submitter_message
        );
    }
}
