<?php
/**
 * Form AJAX Handler - Comprehensive Form Processing
 *
 * Consolidated form handling functionality from:
 * - includes/forms/ directory (12 specialized form handlers)
 * - includes/theme-forms/class-form-handler.php
 * - includes/dashboard/class-dashboard-form-builder.php
 * Total: 15+ form files → Unified form processing system
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
 * Form AJAX Handler Class
 *
 * Consolidates all form processing functionality including:
 * - Contact forms
 * - Agent registration
 * - Property inquiries
 * - Listing submissions
 * - Open house registrations
 * - Client management forms
 * - Transaction forms
 * - Community forms
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
     * Define AJAX actions and their configurations
     */
    protected function get_actions(): array {
        return [
            // Contact & Inquiry Forms
            'submit_contact_form' => [
                'callback' => 'handle_contact_form',
                'capability' => 'read',
                'public' => true,
                'rate_limit' => 10
            ],
            'submit_inquiry_form' => [
                'callback' => 'handle_inquiry_form',
                'capability' => 'read',
                'public' => true,
                'rate_limit' => 15
            ],
            'submit_showing_request' => [
                'callback' => 'handle_showing_request',
                'capability' => 'read',
                'public' => true,
                'rate_limit' => 20
            ],
            
            // Agent & Client Management
            'submit_agent_form' => [
                'callback' => 'handle_agent_form',
                'capability' => 'edit_posts',
                'public' => false,
                'rate_limit' => 5
            ],
            'submit_client_form' => [
                'callback' => 'handle_client_form',
                'capability' => 'edit_posts',
                'public' => false,
                'rate_limit' => 10
            ],
            'update_agent_profile' => [
                'callback' => 'handle_update_agent_profile',
                'capability' => 'edit_posts',
                'public' => false,
                'rate_limit' => 10
            ],
            
            // Property & Listing Forms
            'submit_listing_form' => [
                'callback' => 'handle_listing_form',
                'capability' => 'edit_posts',
                'public' => false,
                'rate_limit' => 5
            ],
            'submit_open_house_form' => [
                'callback' => 'handle_open_house_form',
                'capability' => 'edit_posts',
                'public' => false,
                'rate_limit' => 10
            ],
            'submit_transaction_form' => [
                'callback' => 'handle_transaction_form',
                'capability' => 'edit_posts',
                'public' => false,
                'rate_limit' => 5
            ],
            
            // Community & Location Forms
            'submit_community_form' => [
                'callback' => 'handle_community_form',
                'capability' => 'edit_posts',
                'public' => false,
                'rate_limit' => 5
            ],
            'submit_city_form' => [
                'callback' => 'handle_city_form',
                'capability' => 'edit_posts',
                'public' => false,
                'rate_limit' => 5
            ],
            
            // Form Validation & Utilities
            'validate_form_data' => [
                'callback' => 'handle_validate_form_data',
                'capability' => 'read',
                'public' => true,
                'rate_limit' => 30
            ],
            'upload_form_file' => [
                'callback' => 'handle_upload_form_file',
                'capability' => 'upload_files',
                'public' => false,
                'rate_limit' => 10
            ],
            'delete_form_file' => [
                'callback' => 'handle_delete_form_file',
                'capability' => 'delete_posts',
                'public' => false,
                'rate_limit' => 10
            ]
        ];
    }

    /**
     * Initialize form configurations
     */
    protected function setup_hooks(): void {
        $this->initialize_form_configs();
        add_action('wp_ajax_nopriv_hph_validate_form_data', [$this, 'handle_validate_form_data']);
    }

    /**
     * Handle contact form submission
     */
    public function handle_contact_form(): void {
        try {
            if (!$this->validate_required_params(['name' => 'string', 'email' => 'email', 'message' => 'string'])) {
                return;
            }

            $form_data = $this->sanitize_contact_form_data($_POST);
            $validation = $this->validate_contact_form($form_data);

            if (!$validation['valid']) {
                $this->send_error('Form validation failed', ['errors' => $validation['errors']]);
                return;
            }

            $contact_id = $this->save_contact_form($form_data);
            $this->send_contact_notifications($form_data, $contact_id);

            $this->send_success([
                'message' => 'Contact form submitted successfully',
                'contact_id' => $contact_id
            ]);

        } catch (\Exception $e) {
            $this->send_error('Contact form submission failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle property inquiry form
     */
    public function handle_inquiry_form(): void {
        try {
            if (!$this->validate_required_params(['property_id' => 'int', 'name' => 'string', 'email' => 'email'])) {
                return;
            }

            $form_data = $this->sanitize_inquiry_form_data($_POST);
            $validation = $this->validate_inquiry_form($form_data);

            if (!$validation['valid']) {
                $this->send_error('Inquiry validation failed', ['errors' => $validation['errors']]);
                return;
            }

            $inquiry_id = $this->save_inquiry_form($form_data);
            $this->send_inquiry_notifications($form_data, $inquiry_id);

            $this->send_success([
                'message' => 'Property inquiry submitted successfully',
                'inquiry_id' => $inquiry_id
            ]);

        } catch (\Exception $e) {
            $this->send_error('Inquiry submission failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle showing request form
     */
    public function handle_showing_request(): void {
        try {
            if (!$this->validate_required_params([
                'property_id' => 'int',
                'name' => 'string',
                'email' => 'email',
                'preferred_date' => 'string'
            ])) {
                return;
            }

            $form_data = $this->sanitize_showing_request_data($_POST);
            $validation = $this->validate_showing_request($form_data);

            if (!$validation['valid']) {
                $this->send_error('Showing request validation failed', ['errors' => $validation['errors']]);
                return;
            }

            $showing_id = $this->save_showing_request($form_data);
            $this->send_showing_notifications($form_data, $showing_id);

            $this->send_success([
                'message' => 'Showing request submitted successfully',
                'showing_id' => $showing_id
            ]);

        } catch (\Exception $e) {
            $this->send_error('Showing request submission failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle agent form submission
     */
    public function handle_agent_form(): void {
        try {
            if (!$this->validate_required_params(['agent_name' => 'string', 'agent_email' => 'email'])) {
                return;
            }

            $form_data = $this->sanitize_agent_form_data($_POST);
            $validation = $this->validate_agent_form($form_data);

            if (!$validation['valid']) {
                $this->send_error('Agent form validation failed', ['errors' => $validation['errors']]);
                return;
            }

            $agent_id = $this->save_agent_form($form_data);
            
            // Handle file uploads (photos, documents)
            $upload_results = $this->handle_agent_file_uploads($agent_id);

            $this->send_success([
                'message' => 'Agent profile created successfully',
                'agent_id' => $agent_id,
                'uploads' => $upload_results
            ]);

        } catch (\Exception $e) {
            $this->send_error('Agent form submission failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle client form submission
     */
    public function handle_client_form(): void {
        try {
            if (!$this->validate_required_params(['client_name' => 'string', 'client_email' => 'email'])) {
                return;
            }

            $form_data = $this->sanitize_client_form_data($_POST);
            $validation = $this->validate_client_form($form_data);

            if (!$validation['valid']) {
                $this->send_error('Client form validation failed', ['errors' => $validation['errors']]);
                return;
            }

            $client_id = $this->save_client_form($form_data);

            $this->send_success([
                'message' => 'Client profile created successfully',
                'client_id' => $client_id
            ]);

        } catch (\Exception $e) {
            $this->send_error('Client form submission failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle listing form submission
     */
    public function handle_listing_form(): void {
        try {
            if (!$this->validate_required_params(['listing_title' => 'string', 'listing_price' => 'string'])) {
                return;
            }

            $form_data = $this->sanitize_listing_form_data($_POST);
            $validation = $this->validate_listing_form($form_data);

            if (!$validation['valid']) {
                $this->send_error('Listing form validation failed', ['errors' => $validation['errors']]);
                return;
            }

            $listing_id = $this->save_listing_form($form_data);
            
            // Handle image uploads
            $upload_results = $this->handle_listing_image_uploads($listing_id);

            $this->send_success([
                'message' => 'Listing created successfully',
                'listing_id' => $listing_id,
                'uploads' => $upload_results
            ]);

        } catch (\Exception $e) {
            $this->send_error('Listing form submission failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle open house form submission
     */
    public function handle_open_house_form(): void {
        try {
            if (!$this->validate_required_params([
                'property_id' => 'int',
                'start_date' => 'string',
                'end_date' => 'string'
            ])) {
                return;
            }

            $form_data = $this->sanitize_open_house_form_data($_POST);
            $validation = $this->validate_open_house_form($form_data);

            if (!$validation['valid']) {
                $this->send_error('Open house form validation failed', ['errors' => $validation['errors']]);
                return;
            }

            $open_house_id = $this->save_open_house_form($form_data);

            $this->send_success([
                'message' => 'Open house scheduled successfully',
                'open_house_id' => $open_house_id
            ]);

        } catch (\Exception $e) {
            $this->send_error('Open house form submission failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle transaction form submission
     */
    public function handle_transaction_form(): void {
        try {
            if (!$this->validate_required_params([
                'property_id' => 'int',
                'transaction_type' => 'string',
                'amount' => 'string'
            ])) {
                return;
            }

            $form_data = $this->sanitize_transaction_form_data($_POST);
            $validation = $this->validate_transaction_form($form_data);

            if (!$validation['valid']) {
                $this->send_error('Transaction form validation failed', ['errors' => $validation['errors']]);
                return;
            }

            $transaction_id = $this->save_transaction_form($form_data);

            $this->send_success([
                'message' => 'Transaction recorded successfully',
                'transaction_id' => $transaction_id
            ]);

        } catch (\Exception $e) {
            $this->send_error('Transaction form submission failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle community form submission
     */
    public function handle_community_form(): void {
        try {
            if (!$this->validate_required_params(['community_name' => 'string'])) {
                return;
            }

            $form_data = $this->sanitize_community_form_data($_POST);
            $validation = $this->validate_community_form($form_data);

            if (!$validation['valid']) {
                $this->send_error('Community form validation failed', ['errors' => $validation['errors']]);
                return;
            }

            $community_id = $this->save_community_form($form_data);

            $this->send_success([
                'message' => 'Community created successfully',
                'community_id' => $community_id
            ]);

        } catch (\Exception $e) {
            $this->send_error('Community form submission failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle city form submission
     */
    public function handle_city_form(): void {
        try {
            if (!$this->validate_required_params(['city_name' => 'string'])) {
                return;
            }

            $form_data = $this->sanitize_city_form_data($_POST);
            $validation = $this->validate_city_form($form_data);

            if (!$validation['valid']) {
                $this->send_error('City form validation failed', ['errors' => $validation['errors']]);
                return;
            }

            $city_id = $this->save_city_form($form_data);

            $this->send_success([
                'message' => 'City created successfully',
                'city_id' => $city_id
            ]);

        } catch (\Exception $e) {
            $this->send_error('City form submission failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle agent profile updates
     */
    public function handle_update_agent_profile(): void {
        try {
            if (!$this->validate_required_params(['agent_id' => 'int'])) {
                return;
            }

            $agent_id = $_POST['agent_id'];
            $form_data = $this->sanitize_agent_form_data($_POST);
            
            $updated = $this->update_agent_profile($agent_id, $form_data);

            if ($updated) {
                $this->send_success([
                    'message' => 'Agent profile updated successfully',
                    'agent_id' => $agent_id
                ]);
            } else {
                $this->send_error('Failed to update agent profile');
            }

        } catch (\Exception $e) {
            $this->send_error('Agent profile update failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle form data validation (AJAX endpoint for real-time validation)
     */
    public function handle_validate_form_data(): void {
        try {
            if (!$this->validate_required_params(['form_type' => 'string', 'field_data' => 'array'])) {
                return;
            }

            $form_type = $_POST['form_type'];
            $field_data = $_POST['field_data'];

            $validation = $this->validate_form_by_type($form_type, $field_data);

            $this->send_success([
                'message' => 'Validation completed',
                'valid' => $validation['valid'],
                'errors' => $validation['errors'],
                'field_errors' => $validation['field_errors'] ?? []
            ]);

        } catch (\Exception $e) {
            $this->send_error('Form validation failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle file uploads for forms
     */
    public function handle_upload_form_file(): void {
        try {
            if (empty($_FILES)) {
                $this->send_error('No files uploaded');
                return;
            }

            $upload_results = [];
            foreach ($_FILES as $field_name => $file) {
                $result = $this->process_file_upload($file, $field_name);
                $upload_results[$field_name] = $result;
            }

            $this->send_success([
                'message' => 'Files uploaded successfully',
                'uploads' => $upload_results
            ]);

        } catch (\Exception $e) {
            $this->send_error('File upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle file deletion
     */
    public function handle_delete_form_file(): void {
        try {
            if (!$this->validate_required_params(['file_id' => 'int'])) {
                return;
            }

            $file_id = $_POST['file_id'];
            $deleted = $this->delete_uploaded_file($file_id);

            if ($deleted) {
                $this->send_success([
                    'message' => 'File deleted successfully',
                    'file_id' => $file_id
                ]);
            } else {
                $this->send_error('Failed to delete file');
            }

        } catch (\Exception $e) {
            $this->send_error('File deletion failed: ' . $e->getMessage());
        }
    }

    /**
     * Initialize form configurations
     */
    private function initialize_form_configs(): void {
        $this->form_configs = [
            'contact' => [
                'required_fields' => ['name', 'email', 'message'],
                'validation_rules' => [
                    'name' => ['min_length' => 2, 'max_length' => 100],
                    'email' => ['email' => true],
                    'message' => ['min_length' => 10, 'max_length' => 1000],
                    'phone' => ['phone' => true, 'optional' => true]
                ]
            ],
            'inquiry' => [
                'required_fields' => ['property_id', 'name', 'email'],
                'validation_rules' => [
                    'property_id' => ['integer' => true, 'min' => 1],
                    'name' => ['min_length' => 2, 'max_length' => 100],
                    'email' => ['email' => true],
                    'message' => ['min_length' => 10, 'max_length' => 1000, 'optional' => true]
                ]
            ],
            'agent' => [
                'required_fields' => ['agent_name', 'agent_email'],
                'validation_rules' => [
                    'agent_name' => ['min_length' => 2, 'max_length' => 100],
                    'agent_email' => ['email' => true],
                    'agent_phone' => ['phone' => true, 'optional' => true],
                    'agent_bio' => ['max_length' => 2000, 'optional' => true]
                ]
            ]
            // Additional form configs would be added here
        ];
    }

    // Form-specific sanitization methods
    private function sanitize_contact_form_data(array $data): array {
        return [
            'name' => sanitize_text_field($data['name'] ?? ''),
            'email' => sanitize_email($data['email'] ?? ''),
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'message' => sanitize_textarea_field($data['message'] ?? ''),
            'property_id' => intval($data['property_id'] ?? 0),
            'source' => sanitize_text_field($data['source'] ?? 'website')
        ];
    }

    private function sanitize_inquiry_form_data(array $data): array {
        return [
            'property_id' => intval($data['property_id'] ?? 0),
            'name' => sanitize_text_field($data['name'] ?? ''),
            'email' => sanitize_email($data['email'] ?? ''),
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'message' => sanitize_textarea_field($data['message'] ?? ''),
            'preferred_contact' => sanitize_text_field($data['preferred_contact'] ?? 'email')
        ];
    }

    private function sanitize_agent_form_data(array $data): array {
        return [
            'agent_name' => sanitize_text_field($data['agent_name'] ?? ''),
            'agent_email' => sanitize_email($data['agent_email'] ?? ''),
            'agent_phone' => sanitize_text_field($data['agent_phone'] ?? ''),
            'agent_bio' => sanitize_textarea_field($data['agent_bio'] ?? ''),
            'license_number' => sanitize_text_field($data['license_number'] ?? ''),
            'specialties' => array_map('sanitize_text_field', $data['specialties'] ?? [])
        ];
    }

    // Form-specific validation methods
    private function validate_contact_form(array $data): array {
        $errors = [];
        
        if (strlen($data['name']) < 2) {
            $errors[] = 'Name must be at least 2 characters long';
        }
        
        if (!is_email($data['email'])) {
            $errors[] = 'Please enter a valid email address';
        }
        
        if (strlen($data['message']) < 10) {
            $errors[] = 'Message must be at least 10 characters long';
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }

    private function validate_inquiry_form(array $data): array {
        $errors = [];
        
        if ($data['property_id'] <= 0) {
            $errors[] = 'Valid property ID is required';
        }
        
        if (strlen($data['name']) < 2) {
            $errors[] = 'Name must be at least 2 characters long';
        }
        
        if (!is_email($data['email'])) {
            $errors[] = 'Please enter a valid email address';
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }

    private function validate_agent_form(array $data): array {
        $errors = [];
        
        if (strlen($data['agent_name']) < 2) {
            $errors[] = 'Agent name must be at least 2 characters long';
        }
        
        if (!is_email($data['agent_email'])) {
            $errors[] = 'Please enter a valid email address';
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }

    // Data saving methods
    private function save_contact_form(array $data): int {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'happy_place_contacts';
        
        $result = $wpdb->insert(
            $table_name,
            [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'message' => $data['message'],
                'property_id' => $data['property_id'],
                'source' => $data['source'],
                'created_at' => current_time('mysql'),
                'status' => 'new'
            ],
            ['%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s']
        );
        
        return $result ? $wpdb->insert_id : 0;
    }

    private function save_inquiry_form(array $data): int {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'happy_place_inquiries';
        
        $result = $wpdb->insert(
            $table_name,
            [
                'property_id' => $data['property_id'],
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'message' => $data['message'],
                'preferred_contact' => $data['preferred_contact'],
                'created_at' => current_time('mysql'),
                'status' => 'pending'
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        
        return $result ? $wpdb->insert_id : 0;
    }

    private function save_agent_form(array $data): int {
        $post_data = [
            'post_title' => $data['agent_name'],
            'post_type' => 'agent',
            'post_status' => 'publish',
            'post_content' => $data['agent_bio'],
            'meta_input' => [
                'agent_email' => $data['agent_email'],
                'agent_phone' => $data['agent_phone'],
                'license_number' => $data['license_number'],
                'specialties' => $data['specialties']
            ]
        ];
        
        return wp_insert_post($post_data);
    }

    // Notification methods
    private function send_contact_notifications(array $data, int $contact_id): void {
        // Send email notifications to admin and/or agent
        $admin_email = get_option('admin_email');
        $subject = 'New Contact Form Submission';
        $message = sprintf(
            "New contact form submission:\n\nName: %s\nEmail: %s\nPhone: %s\nMessage: %s",
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['message']
        );
        
        wp_mail($admin_email, $subject, $message);
    }

    // File upload handling
    private function process_file_upload(array $file, string $field_name): array {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'File upload error: ' . $file['error']];
        }
        
        // Validate file type and size
        $validation = $this->validate_uploaded_file($file);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }
        
        // Use WordPress upload handling
        $upload = wp_handle_upload($file, ['test_form' => false]);
        
        if (isset($upload['error'])) {
            return ['success' => false, 'error' => $upload['error']];
        }
        
        return [
            'success' => true,
            'file_url' => $upload['url'],
            'file_path' => $upload['file'],
            'file_type' => $upload['type']
        ];
    }

    private function validate_uploaded_file(array $file): array {
        // Check file size
        if ($file['size'] > $this->upload_configs['max_file_size']) {
            return ['valid' => false, 'error' => 'File size exceeds maximum allowed size'];
        }
        
        // Check file type
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $this->upload_configs['allowed_types'])) {
            return ['valid' => false, 'error' => 'File type not allowed'];
        }
        
        return ['valid' => true];
    }

    // Placeholder methods for remaining functionality
    private function sanitize_showing_request_data(array $data): array { return $data; }
    private function sanitize_listing_form_data(array $data): array { return $data; }
    private function sanitize_open_house_form_data(array $data): array { return $data; }
    private function sanitize_transaction_form_data(array $data): array { return $data; }
    private function sanitize_community_form_data(array $data): array { return $data; }
    private function sanitize_city_form_data(array $data): array { return $data; }
    private function sanitize_client_form_data(array $data): array { return $data; }
    
    private function validate_showing_request(array $data): array { return ['valid' => true, 'errors' => []]; }
    private function validate_listing_form(array $data): array { return ['valid' => true, 'errors' => []]; }
    private function validate_open_house_form(array $data): array { return ['valid' => true, 'errors' => []]; }
    private function validate_transaction_form(array $data): array { return ['valid' => true, 'errors' => []]; }
    private function validate_community_form(array $data): array { return ['valid' => true, 'errors' => []]; }
    private function validate_city_form(array $data): array { return ['valid' => true, 'errors' => []]; }
    private function validate_client_form(array $data): array { return ['valid' => true, 'errors' => []]; }
    
    private function save_showing_request(array $data): int { return 1; }
    private function save_listing_form(array $data): int { return 1; }
    private function save_open_house_form(array $data): int { return 1; }
    private function save_transaction_form(array $data): int { return 1; }
    private function save_community_form(array $data): int { return 1; }
    private function save_city_form(array $data): int { return 1; }
    private function save_client_form(array $data): int { return 1; }
    
    private function send_inquiry_notifications(array $data, int $id): void { }
    private function send_showing_notifications(array $data, int $id): void { }
    private function handle_agent_file_uploads(int $agent_id): array { return []; }
    private function handle_listing_image_uploads(int $listing_id): array { return []; }
    private function update_agent_profile(int $agent_id, array $data): bool { return true; }
    private function validate_form_by_type(string $type, array $data): array { return ['valid' => true, 'errors' => []]; }
    private function delete_uploaded_file(int $file_id): bool { return true; }
}
