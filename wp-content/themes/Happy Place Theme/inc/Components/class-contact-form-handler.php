<?php
/**
 * Contact Form Handler Class
 * 
 * @package HappyPlace
 */

class HPH_Contact_Form_Handler extends HPH_Form_Handler {
    /**
     * Initialize the handler
     */
    public function __construct() {
        parent::__construct();
        $this->set_action('hph_contact_form');
    }

    /**
     * Validate form data
     *
     * @param array $data Form data
     * @return bool Whether validation passed
     */
    protected function validate($data) {
        // Required fields
        if (empty($data['name'])) {
            $this->add_error('name', __('Please enter your name', 'happy-place'));
        }

        if (empty($data['email'])) {
            $this->add_error('email', __('Please enter your email address', 'happy-place'));
        } elseif (!is_email($data['email'])) {
            $this->add_error('email', __('Please enter a valid email address', 'happy-place'));
        }

        if (empty($data['message'])) {
            $this->add_error('message', __('Please enter your message', 'happy-place'));
        } elseif (strlen($data['message']) < 10) {
            $this->add_error('message', __('Message must be at least 10 characters long', 'happy-place'));
        }

        return empty($this->errors);
    }

    /**
     * Sanitize form data
     *
     * @param array $data Raw form data
     * @return array Sanitized form data
     */
    protected function sanitize($data) {
        return array(
            'name' => sanitize_text_field($data['name']),
            'email' => sanitize_email($data['email']),
            'phone' => isset($data['phone']) ? sanitize_text_field($data['phone']) : '',
            'subject' => isset($data['subject']) ? sanitize_text_field($data['subject']) : '',
            'message' => sanitize_textarea_field($data['message']),
            'interest' => isset($data['interest']) ? sanitize_text_field($data['interest']) : '',
            'newsletter_signup' => isset($data['newsletter_signup']) ? 1 : 0,
            'agent_id' => isset($data['agent_id']) ? absint($data['agent_id']) : 0
        );
    }

    /**
     * Process the form submission
     *
     * @param array $data Sanitized form data
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    protected function process($data) {
        // Create post object for contact entry
        $post_data = array(
            'post_title' => sprintf(
                __('Contact from %s', 'happy-place'),
                $data['name']
            ),
            'post_content' => $data['message'],
            'post_type' => 'hph_contact',
            'post_status' => 'private',
        );

        // Insert the post into the database
        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // Save contact metadata
        add_post_meta($post_id, '_contact_name', $data['name']);
        add_post_meta($post_id, '_contact_email', $data['email']);
        add_post_meta($post_id, '_contact_phone', $data['phone']);
        add_post_meta($post_id, '_contact_subject', $data['subject']);
        add_post_meta($post_id, '_contact_interest', $data['interest']);

        if ($data['agent_id']) {
            add_post_meta($post_id, '_agent_id', $data['agent_id']);
        }

        // Newsletter signup
        if ($data['newsletter_signup']) {
            do_action('hph_newsletter_signup', $data['email'], array(
                'name' => $data['name'],
                'phone' => $data['phone'],
                'interest' => $data['interest']
            ));
        }

        // Send notification emails
        $this->send_notification_emails($data, $post_id);

        return true;
    }

    /**
     * Send notification emails to appropriate recipients
     *
     * @param array $data Form data
     * @param int $post_id Contact entry ID
     */
    protected function send_notification_emails($data, $post_id) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        // Admin notification
        $admin_subject = sprintf(
            __('[%s] New Contact Form Submission from %s', 'happy-place'),
            $site_name,
            $data['name']
        );

        $admin_message = sprintf(
            __("New contact form submission:\n\nName: %s\nEmail: %s\nPhone: %s\nInterest: %s\n\nMessage:\n%s\n\nView contact: %s", 'happy-place'),
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['interest'],
            $data['message'],
            admin_url('post.php?post=' . $post_id . '&action=edit')
        );

        wp_mail($admin_email, $admin_subject, $admin_message);

        // Agent notification if applicable
        if ($data['agent_id']) {
            $agent = get_userdata($data['agent_id']);
            if ($agent) {
                $agent_subject = sprintf(
                    __('[%s] New Contact Form Message from %s', 'happy-place'),
                    $site_name,
                    $data['name']
                );

                $agent_message = sprintf(
                    __("You have received a new contact form message:\n\nName: %s\nEmail: %s\nPhone: %s\nInterest: %s\n\nMessage:\n%s\n\nYou can reply directly to this email to respond to %s.", 'happy-place'),
                    $data['name'],
                    $data['email'],
                    $data['phone'],
                    $data['interest'],
                    $data['message'],
                    $data['name']
                );

                $headers = array(
                    'Reply-To: ' . $data['name'] . ' <' . $data['email'] . '>'
                );

                wp_mail($agent->user_email, $agent_subject, $agent_message, $headers);
            }
        }

        // Sender confirmation
        $confirmation_subject = sprintf(
            __('Thank you for contacting %s', 'happy-place'),
            $site_name
        );

        $confirmation_message = sprintf(
            __("Dear %s,\n\nThank you for contacting %s. We have received your message and will get back to you shortly.\n\nBest regards,\nThe %s Team", 'happy-place'),
            $data['name'],
            $site_name,
            $site_name
        );

        wp_mail($data['email'], $confirmation_subject, $confirmation_message);
    }

    /**
     * Handle successful submission
     *
     * @param array $data Processed form data
     */
    protected function handle_success($data) {
        // Add success message to session
        $this->add_message(
            __('Thank you for your message. We will get back to you shortly.', 'happy-place'),
            'success'
        );

        // Redirect back to referrer or home
        $redirect_url = wp_get_referer();
        if (!$redirect_url) {
            $redirect_url = home_url();
        }

        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Handle failed submission
     *
     * @param array $data Form data
     */
    protected function handle_error($data) {
        // Add error message to session
        $this->add_message(
            __('There was a problem submitting your message. Please try again.', 'happy-place'),
            'error'
        );

        parent::handle_error($data);
    }
}

// Initialize the handler
new HPH_Contact_Form_Handler();
