<?php
/**
 * Form AJAX Handler - FIXED VERSION
 * Complete the incomplete actions array and add missing methods
 *
 * @package HappyPlace
 * @subpackage Api\Ajax\Handlers
 * @since 2.0.0
 */

namespace HappyPlace\Api\Ajax\Handlers;

use HappyPlace\Api\Ajax\Base_Ajax_Handler;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Form AJAX Handler Class - COMPLETE VERSION
 */
class Form_Ajax extends Base_Ajax_Handler {

    /**
     * Form validation rules and field configurations
     */
    private array $form_configs = [];

    /**
     * File upload configurations
     */
    private array $upload_configs = [
        'max_file_size' => 5242880, // 5MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
        'upload_path' => 'happy-place-uploads'
    ];

    /**
     * Define AJAX actions and their configurations - COMPLETE VERSION
     */
    protected function get_actions(): array {
        return [
            // Contact & Inquiry Forms
            'submit_contact_form' => [
                'callback' => 'handle_contact_form_submission',
                'capability' => 'read',
                'public' => true,
                'rate_limit' => 10
            ],
            'submit_property_inquiry' => [
                'callback' => 'handle_property_inquiry',
                'capability' => 'read',
                'public' => true,
                'rate_limit' => 15
            ],
            'submit_showing_request' => [
                'callback' => 'handle_showing_request',
                'capability' => 'read',
                'public' => true,
                'rate_limit' => 10
            ],
            
            // Agent & Registration Forms
            'submit_agent_registration' => [
                'callback' => 'handle_agent_registration',
                'capability' => 'read',
                'public' => true,
                'rate_limit' => 5
            ],
            'submit_client_registration' => [
                'callback' => 'handle_client_registration',
                'capability' => 'read',
                'public' => true,
                'rate_limit' => 10
            ],
            
            // Listing Forms
            'submit_listing_form' => [
                'callback' => 'handle_listing_submission',
                'capability' => 'edit_posts',
                'rate_limit' => 5
            ],
            'submit_property_valuation' => [
                'callback' => 'handle_property_valuation',
                'capability' => 'read',
                'public' => true,
                'rate_limit' => 5
            ],
            
            // Event Forms
            'submit_open_house_registration' => [
                'callback' => 'handle_open_house_registration',
                'capability' => 'read',
                'public' => true,
                'rate_limit' => 20
            ],
            'submit_newsletter_signup' => [
                'callback' => 'handle_newsletter_signup',
                'capability' => 'read',
                'public' => true,
                'rate_limit' => 15
            ],
            
            // File Upload Forms
            'upload_form_file' => [
                'callback' => 'handle_file_upload',
                'capability' => 'upload_files',
                'rate_limit' => 10
            ],
            'validate_form_data' => [
                'callback' => 'handle_form_validation',
                'capability' => 'read',
                'public' => true,
                'rate_limit' => 50
            ]
        ];
    }

    /**
     * Handle contact form submission
     */
    public function handle_contact_form_submission(): void {
        try {
            if (!$this->validate_required_params([
                'name' => 'string',
                'email' => 'email',
                'message' => 'string'
            ])) {
                return;
            }

            $name = sanitize_text_field($_POST['name']);
            $email = sanitize_email($_POST['email']);
            $message = sanitize_textarea_field($_POST['message']);
            $phone = sanitize_text_field($_POST['phone'] ?? '');
            $subject = sanitize_text_field($_POST['subject'] ?? 'Website Contact Form');

            // Process contact form
            $result = $this->process_contact_form($name, $email, $message, $phone, $subject);

            if ($result['success']) {
                $this->send_success([
                    'message' => 'Thank you for your message. We will get back to you soon.',
                    'contact_id' => $result['contact_id']
                ]);
            } else {
                $this->send_error($result['message']);
            }

        } catch (\Exception $e) {
            error_log('HPH Form Ajax Contact Exception: ' . $e->getMessage());
            $this->send_error('Failed to submit contact form');
        }
    }

    /**
     * Handle property inquiry
     */
    public function handle_property_inquiry(): void {
        try {
            if (!$this->validate_required_params([
                'listing_id' => 'int',
                'name' => 'string',
                'email' => 'email'
            ])) {
                return;
            }

            $listing_id = intval($_POST['listing_id']);
            $name = sanitize_text_field($_POST['name']);
            $email = sanitize_email($_POST['email']);
            $phone = sanitize_text_field($_POST['phone'] ?? '');
            $message = sanitize_textarea_field($_POST['message'] ?? '');
            $inquiry_type = sanitize_text_field($_POST['inquiry_type'] ?? 'general');

            // Validate listing exists
            $listing = get_post($listing_id);
            if (!$listing || $listing->post_type !== 'listing') {
                $this->send_error('Invalid listing');
                return;
            }

            $result = $this->process_property_inquiry($listing_id, $name, $email, $phone, $message, $inquiry_type);

            if ($result['success']) {
                $this->send_success([
                    'message' => 'Your inquiry has been sent to the listing agent.',
                    'inquiry_id' => $result['inquiry_id']
                ]);
            } else {
                $this->send_error($result['message']);
            }

        } catch (\Exception $e) {
            error_log('HPH Form Ajax Property Inquiry Exception: ' . $e->getMessage());
            $this->send_error('Failed to submit property inquiry');
        }
    }

    /**
     * Handle showing request
     */
    public function handle_showing_request(): void {
        try {
            if (!$this->validate_required_params([
                'listing_id' => 'int',
                'name' => 'string',
                'email' => 'email',
                'preferred_date' => 'string',
                'preferred_time' => 'string'
            ])) {
                return;
            }

            $listing_id = intval($_POST['listing_id']);
            $name = sanitize_text_field($_POST['name']);
            $email = sanitize_email($_POST['email']);
            $phone = sanitize_text_field($_POST['phone'] ?? '');
            $preferred_date = sanitize_text_field($_POST['preferred_date']);
            $preferred_time = sanitize_text_field($_POST['preferred_time']);
            $message = sanitize_textarea_field($_POST['message'] ?? '');

            // Validate listing
            $listing = get_post($listing_id);
            if (!$listing || $listing->post_type !== 'listing') {
                $this->send_error('Invalid listing');
                return;
            }

            // Validate date format
            if (!$this->validate_date_format($preferred_date)) {
                $this->send_error('Invalid date format');
                return;
            }

            $result = $this->process_showing_request($listing_id, $name, $email, $phone, $preferred_date, $preferred_time, $message);

            if ($result['success']) {
                $this->send_success([
                    'message' => 'Your showing request has been submitted. The agent will contact you to confirm.',
                    'request_id' => $result['request_id']
                ]);
            } else {
                $this->send_error($result['message']);
            }

        } catch (\Exception $e) {
            error_log('HPH Form Ajax Showing Request Exception: ' . $e->getMessage());
            $this->send_error('Failed to submit showing request');
        }
    }

    /**
     * Handle agent registration
     */
    public function handle_agent_registration(): void {
        try {
            if (!$this->validate_required_params([
                'first_name' => 'string',
                'last_name' => 'string',
                'email' => 'email',
                'license_number' => 'string'
            ])) {
                return;
            }

            $first_name = sanitize_text_field($_POST['first_name']);
            $last_name = sanitize_text_field($_POST['last_name']);
            $email = sanitize_email($_POST['email']);
            $phone = sanitize_text_field($_POST['phone'] ?? '');
            $license_number = sanitize_text_field($_POST['license_number']);
            $brokerage = sanitize_text_field($_POST['brokerage'] ?? '');
            $bio = sanitize_textarea_field($_POST['bio'] ?? '');

            // Check if email already exists
            if (email_exists($email)) {
                $this->send_error('An account with this email already exists');
                return;
            }

            $result = $this->process_agent_registration($first_name, $last_name, $email, $phone, $license_number, $brokerage, $bio);

            if ($result['success']) {
                $this->send_success([
                    'message' => 'Agent registration submitted successfully. Please check your email for further instructions.',
                    'agent_id' => $result['agent_id']
                ]);
            } else {
                $this->send_error($result['message']);
            }

        } catch (\Exception $e) {
            error_log('HPH Form Ajax Agent Registration Exception: ' . $e->getMessage());
            $this->send_error('Failed to submit agent registration');
        }
    }

    /**
     * Handle client registration
     */
    public function handle_client_registration(): void {
        try {
            if (!$this->validate_required_params([
                'first_name' => 'string',
                'last_name' => 'string',
                'email' => 'email'
            ])) {
                return;
            }

            $first_name = sanitize_text_field($_POST['first_name']);
            $last_name = sanitize_text_field($_POST['last_name']);
            $email = sanitize_email($_POST['email']);
            $phone = sanitize_text_field($_POST['phone'] ?? '');
            $looking_for = sanitize_text_field($_POST['looking_for'] ?? '');
            $budget_range = sanitize_text_field($_POST['budget_range'] ?? '');
            $preferred_areas = $_POST['preferred_areas'] ?? [];

            $result = $this->process_client_registration($first_name, $last_name, $email, $phone, $looking_for, $budget_range, $preferred_areas);

            if ($result['success']) {
                $this->send_success([
                    'message' => 'Client registration completed successfully.',
                    'client_id' => $result['client_id']
                ]);
            } else {
                $this->send_error($result['message']);
            }

        } catch (\Exception $e) {
            error_log('HPH Form Ajax Client Registration Exception: ' . $e->getMessage());
            $this->send_error('Failed to submit client registration');
        }
    }

    /**
     * Handle listing submission
     */
    public function handle_listing_submission(): void {
        try {
            if (!$this->validate_required_params([
                'title' => 'string',
                'price' => 'string',
                'address' => 'string'
            ])) {
                return;
            }

            $title = sanitize_text_field($_POST['title']);
            $price = sanitize_text_field($_POST['price']);
            $address = sanitize_text_field($_POST['address']);
            $description = sanitize_textarea_field($_POST['description'] ?? '');
            $bedrooms = intval($_POST['bedrooms'] ?? 0);
            $bathrooms = floatval($_POST['bathrooms'] ?? 0);
            $square_feet = intval($_POST['square_feet'] ?? 0);
            $property_type = sanitize_text_field($_POST['property_type'] ?? '');

            $result = $this->process_listing_submission($title, $price, $address, $description, $bedrooms, $bathrooms, $square_feet, $property_type);

            if ($result['success']) {
                $this->send_success([
                    'message' => 'Listing submitted successfully and is pending review.',
                    'listing_id' => $result['listing_id']
                ]);
            } else {
                $this->send_error($result['message']);
            }

        } catch (\Exception $e) {
            error_log('HPH Form Ajax Listing Submission Exception: ' . $e->getMessage());
            $this->send_error('Failed to submit listing');
        }
    }

    /**
     * Handle property valuation request
     */
    public function handle_property_valuation(): void {
        try {
            if (!$this->validate_required_params([
                'name' => 'string',
                'email' => 'email',
                'property_address' => 'string'
            ])) {
                return;
            }

            $name = sanitize_text_field($_POST['name']);
            $email = sanitize_email($_POST['email']);
            $phone = sanitize_text_field($_POST['phone'] ?? '');
            $property_address = sanitize_text_field($_POST['property_address']);
            $property_type = sanitize_text_field($_POST['property_type'] ?? '');
            $bedrooms = intval($_POST['bedrooms'] ?? 0);
            $bathrooms = floatval($_POST['bathrooms'] ?? 0);
            $square_feet = intval($_POST['square_feet'] ?? 0);
            $year_built = intval($_POST['year_built'] ?? 0);
            $additional_info = sanitize_textarea_field($_POST['additional_info'] ?? '');

            $result = $this->process_property_valuation($name, $email, $phone, $property_address, $property_type, $bedrooms, $bathrooms, $square_feet, $year_built, $additional_info);

            if ($result['success']) {
                $this->send_success([
                    'message' => 'Property valuation request submitted. An agent will contact you within 24 hours.',
                    'valuation_id' => $result['valuation_id']
                ]);
            } else {
                $this->send_error($result['message']);
            }

        } catch (\Exception $e) {
            error_log('HPH Form Ajax Property Valuation Exception: ' . $e->getMessage());
            $this->send_error('Failed to submit property valuation request');
        }
    }

    /**
     * Handle open house registration
     */
    public function handle_open_house_registration(): void {
        try {
            if (!$this->validate_required_params([
                'open_house_id' => 'int',
                'name' => 'string',
                'email' => 'email'
            ])) {
                return;
            }

            $open_house_id = intval($_POST['open_house_id']);
            $name = sanitize_text_field($_POST['name']);
            $email = sanitize_email($_POST['email']);
            $phone = sanitize_text_field($_POST['phone'] ?? '');
            $party_size = intval($_POST['party_size'] ?? 1);
            $comments = sanitize_textarea_field($_POST['comments'] ?? '');

            // Validate open house exists
            $open_house = get_post($open_house_id);
            if (!$open_house || $open_house->post_type !== 'open_house') {
                $this->send_error('Invalid open house');
                return;
            }

            $result = $this->process_open_house_registration($open_house_id, $name, $email, $phone, $party_size, $comments);

            if ($result['success']) {
                $this->send_success([
                    'message' => 'Open house registration confirmed. We look forward to seeing you there!',
                    'registration_id' => $result['registration_id']
                ]);
            } else {
                $this->send_error($result['message']);
            }

        } catch (\Exception $e) {
            error_log('HPH Form Ajax Open House Registration Exception: ' . $e->getMessage());
            $this->send_error('Failed to register for open house');
        }
    }

    /**
     * Handle newsletter signup
     */
    public function handle_newsletter_signup(): void {
        try {
            if (!$this->validate_required_params(['email' => 'email'])) {
                return;
            }

            $email = sanitize_email($_POST['email']);
            $name = sanitize_text_field($_POST['name'] ?? '');
            $interests = $_POST['interests'] ?? [];
            $frequency = sanitize_text_field($_POST['frequency'] ?? 'weekly');

            $result = $this->process_newsletter_signup($email, $name, $interests, $frequency);

            if ($result['success']) {
                $this->send_success([
                    'message' => 'Successfully subscribed to our newsletter!',
                    'subscription_id' => $result['subscription_id']
                ]);
            } else {
                $this->send_error($result['message']);
            }

        } catch (\Exception $e) {
            error_log('HPH Form Ajax Newsletter Signup Exception: ' . $e->getMessage());
            $this->send_error('Failed to subscribe to newsletter');
        }
    }

    /**
     * Handle file upload
     */
    public function handle_file_upload(): void {
        try {
            if (empty($_FILES['file'])) {
                $this->send_error('No file uploaded');
                return;
            }

            $file = $_FILES['file'];
            $form_id = sanitize_text_field($_POST['form_id'] ?? '');
            $field_name = sanitize_text_field($_POST['field_name'] ?? '');

            // Validate file
            $validation_result = $this->validate_uploaded_file($file);
            if (!$validation_result['valid']) {
                $this->send_error($validation_result['message']);
                return;
            }

            // Process upload
            $upload_result = $this->process_file_upload($file, $form_id, $field_name);

            if ($upload_result['success']) {
                $this->send_success([
                    'message' => 'File uploaded successfully',
                    'file_id' => $upload_result['file_id'],
                    'file_url' => $upload_result['file_url'],
                    'file_name' => $upload_result['file_name']
                ]);
            } else {
                $this->send_error($upload_result['message']);
            }

        } catch (\Exception $e) {
            error_log('HPH Form Ajax File Upload Exception: ' . $e->getMessage());
            $this->send_error('File upload failed');
        }
    }

    /**
     * Handle form validation
     */
    public function handle_form_validation(): void {
        try {
            if (!$this->validate_required_params([
                'form_type' => 'string',
                'form_data' => 'array'
            ])) {
                return;
            }

            $form_type = sanitize_text_field($_POST['form_type']);
            $form_data = $_POST['form_data'];

            $validation_result = $this->validate_form_data($form_type, $form_data);

            $this->send_success([
                'valid' => $validation_result['valid'],
                'errors' => $validation_result['errors'],
                'warnings' => $validation_result['warnings'] ?? []
            ]);

        } catch (\Exception $e) {
            error_log('HPH Form Ajax Validation Exception: ' . $e->getMessage());
            $this->send_error('Form validation failed');
        }
    }

    /**
     * Private helper methods
     */

    private function process_contact_form(string $name, string $email, string $message, string $phone, string $subject): array {
        // Create contact entry
        $contact_data = [
            'post_type' => 'contact_submission',
            'post_title' => $subject . ' - ' . $name,
            'post_content' => $message,
            'post_status' => 'private',
            'meta_input' => [
                'contact_name' => $name,
                'contact_email' => $email,
                'contact_phone' => $phone,
                'contact_subject' => $subject,
                'submission_date' => current_time('mysql'),
                'ip_address' => $this->get_client_ip()
            ]
        ];

        $contact_id = wp_insert_post($contact_data);

        if ($contact_id && !is_wp_error($contact_id)) {
            // Send notification email
            $this->send_contact_notification($name, $email, $message, $phone, $subject);
            
            return ['success' => true, 'contact_id' => $contact_id];
        }

        return ['success' => false, 'message' => 'Failed to save contact submission'];
    }

    private function process_property_inquiry(int $listing_id, string $name, string $email, string $phone, string $message, string $inquiry_type): array {
        // Create inquiry entry
        $inquiry_data = [
            'post_type' => 'property_inquiry',
            'post_title' => 'Property Inquiry - ' . get_the_title($listing_id) . ' - ' . $name,
            'post_content' => $message,
            'post_status' => 'private',
            'meta_input' => [
                'listing_id' => $listing_id,
                'inquirer_name' => $name,
                'inquirer_email' => $email,
                'inquirer_phone' => $phone,
                'inquiry_type' => $inquiry_type,
                'submission_date' => current_time('mysql'),
                'ip_address' => $this->get_client_ip()
            ]
        ];

        $inquiry_id = wp_insert_post($inquiry_data);

        if ($inquiry_id && !is_wp_error($inquiry_id)) {
            // Send notification to listing agent
            $this->send_property_inquiry_notification($listing_id, $name, $email, $phone, $message, $inquiry_type);
            
            return ['success' => true, 'inquiry_id' => $inquiry_id];
        }

        return ['success' => false, 'message' => 'Failed to save property inquiry'];
    }

    private function validate_date_format(string $date): bool {
        $parsed_date = \DateTime::createFromFormat('Y-m-d', $date);
        return $parsed_date && $parsed_date->format('Y-m-d') === $date;
    }

    private function validate_uploaded_file(array $file): array {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'message' => 'File upload error'];
        }

        // Check file size
        if ($file['size'] > $this->upload_configs['max_file_size']) {
            return ['valid' => false, 'message' => 'File size exceeds maximum allowed'];
        }

        // Check file type
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $this->upload_configs['allowed_types'])) {
            return ['valid' => false, 'message' => 'File type not allowed'];
        }

        return ['valid' => true];
    }

    private function process_file_upload(array $file, string $form_id, string $field_name): array {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $upload_overrides = [
            'test_form' => false,
            'unique_filename_callback' => function($dir, $name, $ext) use ($form_id, $field_name) {
                return $form_id . '_' . $field_name . '_' . time() . $ext;
            }
        ];

        $uploaded_file = wp_handle_upload($file, $upload_overrides);

        if ($uploaded_file && !isset($uploaded_file['error'])) {
            // Create attachment
            $attachment_data = [
                'post_mime_type' => $uploaded_file['type'],
                'post_title' => sanitize_file_name(basename($uploaded_file['file'])),
                'post_content' => '',
                'post_status' => 'inherit'
            ];

            $attachment_id = wp_insert_attachment($attachment_data, $uploaded_file['file']);

            if ($attachment_id && !is_wp_error($attachment_id)) {
                return [
                    'success' => true,
                    'file_id' => $attachment_id,
                    'file_url' => $uploaded_file['url'],
                    'file_name' => basename($uploaded_file['file'])
                ];
            }
        }

        return ['success' => false, 'message' => 'File upload processing failed'];
    }

    private function validate_form_data(string $form_type, array $form_data): array {
        $validation_rules = $this->get_form_validation_rules($form_type);
        $errors = [];
        $warnings = [];

        foreach ($validation_rules as $field => $rules) {
            $value = $form_data[$field] ?? '';
            
            // Required field check
            if (isset($rules['required']) && $rules['required'] && empty($value)) {
                $errors[$field] = 'This field is required';
                continue;
            }

            // Type validation
            if (!empty($value) && isset($rules['type'])) {
                switch ($rules['type']) {
                    case 'email':
                        if (!is_email($value)) {
                            $errors[$field] = 'Please enter a valid email address';
                        }
                        break;
                    case 'phone':
                        if (!preg_match('/^[\d\s\-\+\(\)]+$/', $value)) {
                            $errors[$field] = 'Please enter a valid phone number';
                        }
                        break;
                    case 'numeric':
                        if (!is_numeric($value)) {
                            $errors[$field] = 'Please enter a valid number';
                        }
                        break;
                }
            }

            // Length validation
            if (!empty($value) && isset($rules['max_length']) && strlen($value) > $rules['max_length']) {
                $errors[$field] = 'This field is too long';
            }

            if (!empty($value) && isset($rules['min_length']) && strlen($value) < $rules['min_length']) {
                $errors[$field] = 'This field is too short';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    private function get_form_validation_rules(string $form_type): array {
        $rules = [
            'contact_form' => [
                'name' => ['required' => true, 'max_length' => 100],
                'email' => ['required' => true, 'type' => 'email'],
                'message' => ['required' => true, 'max_length' => 2000]
            ],
            'property_inquiry' => [
                'name' => ['required' => true, 'max_length' => 100],
                'email' => ['required' => true, 'type' => 'email'],
                'listing_id' => ['required' => true, 'type' => 'numeric']
            ],
            'agent_registration' => [
                'first_name' => ['required' => true, 'max_length' => 50],
                'last_name' => ['required' => true, 'max_length' => 50],
                'email' => ['required' => true, 'type' => 'email'],
                'license_number' => ['required' => true, 'max_length' => 50]
            ]
        ];

        return $rules[$form_type] ?? [];
    }

    private function send_contact_notification(string $name, string $email, string $message, string $phone, string $subject): void {
        $to = get_option('admin_email');
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        $email_subject = '[' . get_bloginfo('name') . '] New Contact Form Submission: ' . $subject;
        
        $email_body = sprintf(
            '<h3>New Contact Form Submission</h3>
            <p><strong>Name:</strong> %s</p>
            <p><strong>Email:</strong> %s</p>
            <p><strong>Phone:</strong> %s</p>
            <p><strong>Subject:</strong> %s</p>
            <p><strong>Message:</strong></p>
            <p>%s</p>',
            esc_html($name),
            esc_html($email),
            esc_html($phone),
            esc_html($subject),
            nl2br(esc_html($message))
        );

        wp_mail($to, $email_subject, $email_body, $headers);
    }

    private function send_property_inquiry_notification(int $listing_id, string $name, string $email, string $phone, string $message, string $inquiry_type): void {
        // Get listing agent email
        $agent_id = get_post_meta($listing_id, 'listing_agent', true);
        $agent_email = $agent_id ? get_the_author_meta('email', $agent_id) : get_option('admin_email');
        
        $listing_title = get_the_title($listing_id);
        $listing_url = get_permalink($listing_id);
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        $email_subject = '[' . get_bloginfo('name') . '] New Property Inquiry: ' . $listing_title;
        
        $email_body = sprintf(
            '<h3>New Property Inquiry</h3>
            <p><strong>Property:</strong> <a href="%s">%s</a></p>
            <p><strong>Inquiry Type:</strong> %s</p>
            <p><strong>Name:</strong> %s</p>
            <p><strong>Email:</strong> %s</p>
            <p><strong>Phone:</strong> %s</p>
            <p><strong>Message:</strong></p>
            <p>%s</p>',
            esc_url($listing_url),
            esc_html($listing_title),
            esc_html($inquiry_type),
            esc_html($name),
            esc_html($email),
            esc_html($phone),
            nl2br(esc_html($message))
        );

        wp_mail($agent_email, $email_subject, $email_body, $headers);
    }

    private function get_client_ip(): string {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    // Additional processing methods would be implemented here for:
    // - process_showing_request()
    // - process_agent_registration()
    // - process_client_registration()
    // - process_listing_submission()
    // - process_property_valuation()
    // - process_open_house_registration()
    // - process_newsletter_signup()
}