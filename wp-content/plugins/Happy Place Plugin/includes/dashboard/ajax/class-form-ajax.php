<?php

/**
 * Form AJAX Handler
 * 
 * Handles all form processing, validation, and auto-save functionality
 * for dashboard forms. Replaces form handling from the monolithic handler.
 * 
 * @package HappyPlace\Dashboard\Ajax
 * @since 3.0.0
 */

namespace HappyPlace\Dashboard\Ajax;

use Exception;
use WP_Error;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Form AJAX Handler Class
 * 
 * Handles:
 * - Form submissions with enhanced validation
 * - Auto-save functionality
 * - Draft management
 * - Real-time validation feedback
 * - File uploads within forms
 */
class HPH_Form_Ajax extends HPH_Base_Ajax
{
    /**
     * @var array Form validation rules
     */
    private array $form_rules = [];

    /**
     * @var array Auto-save settings
     */
    private array $auto_save_settings = [
        'interval' => 30, // seconds
        'max_drafts' => 5,
        'cleanup_after' => 604800 // 7 days
    ];

    /**
     * Constructor - Setup form handling
     */
    public function __construct()
    {
        parent::__construct();
        $this->setup_form_rules();
        $this->init_auto_save();
    }

    /**
     * Register AJAX actions for form handling
     */
    protected function register_ajax_actions(): void
    {
        // Form submission actions
        add_action('wp_ajax_hph_save_listing_form', [$this, 'save_listing_form']);
        add_action('wp_ajax_hph_save_lead_form', [$this, 'save_lead_form']);
        add_action('wp_ajax_hph_save_open_house_form', [$this, 'save_open_house_form']);
        add_action('wp_ajax_hph_save_agent_profile_form', [$this, 'save_agent_profile_form']);
        
        // Validation actions
        add_action('wp_ajax_hph_validate_field', [$this, 'validate_field_realtime']);
        add_action('wp_ajax_hph_validate_form', [$this, 'validate_form_complete']);
        
        // Auto-save actions
        add_action('wp_ajax_hph_auto_save_form', [$this, 'auto_save_form']);
        add_action('wp_ajax_hph_load_draft', [$this, 'load_form_draft']);
        add_action('wp_ajax_hph_delete_draft', [$this, 'delete_form_draft']);
        add_action('wp_ajax_hph_list_drafts', [$this, 'list_form_drafts']);
        
        // File upload actions
        add_action('wp_ajax_hph_upload_form_file', [$this, 'upload_form_file']);
        add_action('wp_ajax_hph_delete_form_file', [$this, 'delete_form_file']);
    }

    /**
     * Setup form validation rules
     */
    private function setup_form_rules(): void
    {
        $this->form_rules = [
            'listing' => [
                'post_title' => ['type' => 'string', 'required' => true, 'max_length' => 200],
                'post_content' => ['type' => 'html', 'required' => false],
                '_price' => ['type' => 'decimal', 'required' => true, 'min' => 0],
                '_bedrooms' => ['type' => 'integer', 'required' => false, 'min' => 0, 'max' => 20],
                '_bathrooms' => ['type' => 'decimal', 'required' => false, 'min' => 0, 'max' => 20],
                '_square_feet' => ['type' => 'integer', 'required' => false, 'min' => 0],
                '_lot_size' => ['type' => 'decimal', 'required' => false, 'min' => 0],
                '_year_built' => ['type' => 'integer', 'required' => false, 'min' => 1800, 'max' => date('Y') + 2],
                '_property_type' => ['type' => 'string', 'required' => true, 'allowed' => ['residential', 'commercial', 'land']],
                '_listing_status' => ['type' => 'string', 'required' => true, 'allowed' => ['active', 'pending', 'sold', 'draft']],
                '_address' => ['type' => 'string', 'required' => true, 'max_length' => 500],
                '_city' => ['type' => 'string', 'required' => true, 'max_length' => 100],
                '_state' => ['type' => 'string', 'required' => true, 'max_length' => 2],
                '_zip_code' => ['type' => 'string', 'required' => true, 'pattern' => '/^\d{5}(-\d{4})?$/'],
                '_latitude' => ['type' => 'decimal', 'required' => false, 'min' => -90, 'max' => 90],
                '_longitude' => ['type' => 'decimal', 'required' => false, 'min' => -180, 'max' => 180]
            ],
            'lead' => [
                'post_title' => ['type' => 'string', 'required' => true, 'max_length' => 200],
                '_first_name' => ['type' => 'string', 'required' => true, 'max_length' => 50],
                '_last_name' => ['type' => 'string', 'required' => true, 'max_length' => 50],
                '_email' => ['type' => 'email', 'required' => true],
                '_phone' => ['type' => 'phone', 'required' => false],
                '_lead_source' => ['type' => 'string', 'required' => true, 'allowed' => ['website', 'referral', 'advertisement', 'social_media', 'other']],
                '_lead_status' => ['type' => 'string', 'required' => true, 'allowed' => ['new', 'contacted', 'qualified', 'converted', 'lost']],
                '_budget_min' => ['type' => 'decimal', 'required' => false, 'min' => 0],
                '_budget_max' => ['type' => 'decimal', 'required' => false, 'min' => 0],
                '_preferred_areas' => ['type' => 'string', 'required' => false, 'max_length' => 500],
                '_notes' => ['type' => 'html', 'required' => false]
            ],
            'open_house' => [
                'post_title' => ['type' => 'string', 'required' => true, 'max_length' => 200],
                '_listing_id' => ['type' => 'integer', 'required' => true, 'min' => 1],
                '_start_date' => ['type' => 'datetime', 'required' => true],
                '_end_date' => ['type' => 'datetime', 'required' => true],
                '_description' => ['type' => 'html', 'required' => false],
                '_max_attendees' => ['type' => 'integer', 'required' => false, 'min' => 1, 'max' => 500],
                '_registration_required' => ['type' => 'boolean', 'required' => false],
                '_contact_info' => ['type' => 'string', 'required' => false, 'max_length' => 500]
            ],
            'agent_profile' => [
                '_bio' => ['type' => 'html', 'required' => false],
                '_specialties' => ['type' => 'string', 'required' => false, 'max_length' => 500],
                '_certifications' => ['type' => 'string', 'required' => false, 'max_length' => 500],
                '_years_experience' => ['type' => 'integer', 'required' => false, 'min' => 0, 'max' => 100],
                '_office_phone' => ['type' => 'phone', 'required' => false],
                '_mobile_phone' => ['type' => 'phone', 'required' => false],
                '_website' => ['type' => 'url', 'required' => false],
                '_social_media' => ['type' => 'array', 'required' => false]
            ]
        ];
    }

    /**
     * Initialize auto-save functionality
     */
    private function init_auto_save(): void
    {
        // Schedule cleanup of old drafts
        if (!wp_next_scheduled('hph_cleanup_form_drafts')) {
            wp_schedule_event(time(), 'daily', 'hph_cleanup_form_drafts');
        }
        
        add_action('hph_cleanup_form_drafts', [$this, 'cleanup_old_drafts']);
    }

    /**
     * Save listing form with enhanced validation
     */
    public function save_listing_form(): void
    {
        // Security verification
        if (!$this->verify_nonce()) {
            $this->handle_security_failure('nonce');
            return;
        }

        // Check capabilities
        if (!$this->check_capabilities(['edit_posts'])) {
            $this->handle_security_failure('capabilities');
            return;
        }

        // Rate limiting
        if (!$this->check_rate_limit('save_listing', 5, 60)) {
            $this->send_error($this->error_messages['rate_limit'], [], 429);
            return;
        }

        try {
            // Validate form data
            $validation_result = $this->validate_data($_POST, $this->form_rules['listing']);
            
            if (!$validation_result->is_valid) {
                $this->send_error('Validation failed', [
                    'errors' => $validation_result->errors,
                    'field_errors' => $validation_result->field_errors
                ], 422);
                return;
            }

            // Auto-save draft first
            $draft_id = $this->save_draft('listing', $validation_result->data);

            // Process final submission
            $result = $this->process_listing_submission($validation_result->data);
            
            if (is_wp_error($result)) {
                $this->send_error($result->get_error_message());
                return;
            }

            // Clean up draft after successful save
            if ($draft_id) {
                $this->delete_draft($draft_id);
            }

            // Log activity
            $this->log_activity('save_listing', ['listing_id' => $result['listing_id']]);

            $this->send_success([
                'listing_id' => $result['listing_id'],
                'redirect_url' => $result['edit_url'],
                'status' => $result['status']
            ], 'Listing saved successfully!');

        } catch (Exception $e) {
            error_log('HPH Listing Save Error: ' . $e->getMessage());
            $this->send_error('Failed to save listing. Please try again.');
        }
    }

    /**
     * Save lead form
     */
    public function save_lead_form(): void
    {
        // Security verification
        if (!$this->verify_nonce()) {
            $this->handle_security_failure('nonce');
            return;
        }

        // Check capabilities
        if (!$this->check_capabilities(['edit_posts'])) {
            $this->handle_security_failure('capabilities');
            return;
        }

        try {
            // Validate form data
            $validation_result = $this->validate_data($_POST, $this->form_rules['lead']);
            
            if (!$validation_result->is_valid) {
                $this->send_error('Validation failed', [
                    'errors' => $validation_result->errors,
                    'field_errors' => $validation_result->field_errors
                ], 422);
                return;
            }

            // Process lead submission
            $result = $this->process_lead_submission($validation_result->data);
            
            if (is_wp_error($result)) {
                $this->send_error($result->get_error_message());
                return;
            }

            // Log activity
            $this->log_activity('save_lead', ['lead_id' => $result['lead_id']]);

            $this->send_success([
                'lead_id' => $result['lead_id'],
                'redirect_url' => $result['edit_url'],
                'status' => $result['status']
            ], 'Lead saved successfully!');

        } catch (Exception $e) {
            error_log('HPH Lead Save Error: ' . $e->getMessage());
            $this->send_error('Failed to save lead. Please try again.');
        }
    }

    /**
     * Validate single field for real-time validation
     */
    private function validate_single_field($value, array $rule, string $field_name): array
    {
        $base_rule = $this->validation_rules[$rule['type']] ?? $rule;
        
        switch ($base_rule['type']) {
            case 'integer':
                if (!is_numeric($value) || $value != (int)$value) {
                    return ['valid' => false, 'message' => "{$field_name} must be a whole number"];
                }
                $value = (int)$value;
                break;

            case 'decimal':
                if (!is_numeric($value)) {
                    return ['valid' => false, 'message' => "{$field_name} must be a number"];
                }
                $value = (float)$value;
                break;

            case 'email':
                if (!is_email($value)) {
                    return ['valid' => false, 'message' => "{$field_name} must be a valid email address"];
                }
                break;

            case 'phone':
                $cleaned = preg_replace('/[^\d]/', '', $value);
                if (strlen($cleaned) < 10) {
                    return ['valid' => false, 'message' => "{$field_name} must be a valid phone number"];
                }
                $value = $cleaned;
                break;

            case 'string':
                $value = sanitize_text_field($value);
                break;

            case 'html':
                $value = wp_kses_post($value);
                break;
        }

        // Check min/max constraints
        if (isset($base_rule['min']) && $value < $base_rule['min']) {
            return ['valid' => false, 'message' => "{$field_name} must be at least {$base_rule['min']}"];
        }

        if (isset($base_rule['max']) && $value > $base_rule['max']) {
            return ['valid' => false, 'message' => "{$field_name} must be no more than {$base_rule['max']}"];
        }

        return ['valid' => true, 'value' => $value];
    }

    /**
     * Real-time field validation
     */
    public function validate_field_realtime(): void
    {
        $form_type = sanitize_key($_POST['form_type'] ?? '');
        $field_name = sanitize_key($_POST['field_name'] ?? '');
        $field_value = $_POST['field_value'] ?? '';

        // Check if form type and field are valid
        if (!isset($this->form_rules[$form_type][$field_name])) {
            $this->send_error('Invalid form field');
            return;
        }

        $field_rule = $this->form_rules[$form_type][$field_name];
        $validation_result = $this->validate_single_field($field_value, $field_rule, $field_name);

        if ($validation_result['valid']) {
            $this->send_success([
                'field_name' => $field_name,
                'is_valid' => true,
                'formatted_value' => $validation_result['value']
            ]);
        } else {
            $this->send_success([
                'field_name' => $field_name,
                'is_valid' => false,
                'error_message' => $validation_result['message']
            ]);
        }
    }

    /**
     * Auto-save form data
     */
    public function auto_save_form(): void
    {
        // Security verification
        if (!$this->verify_nonce()) {
            $this->handle_security_failure('nonce');
            return;
        }

        $form_type = sanitize_key($_POST['form_type'] ?? '');
        $form_data = $_POST['form_data'] ?? [];

        if (!isset($this->form_rules[$form_type])) {
            $this->send_error('Invalid form type');
            return;
        }

        try {
            $draft_id = $this->save_draft($form_type, $form_data, true); // Auto-save flag

            $this->send_success([
                'draft_id' => $draft_id,
                'auto_save' => true,
                'timestamp' => current_time('mysql')
            ], 'Draft auto-saved');

        } catch (Exception $e) {
            error_log('HPH Auto-save Error: ' . $e->getMessage());
            // Don't send error for auto-save failures, just log them
            $this->send_success(['auto_save' => false]);
        }
    }

    /**
     * Load form draft
     */
    public function load_form_draft(): void
    {
        // Security verification
        if (!$this->verify_nonce()) {
            $this->handle_security_failure('nonce');
            return;
        }

        $draft_id = sanitize_key($_POST['draft_id'] ?? '');
        
        if (empty($draft_id)) {
            $this->send_error('Draft ID required');
            return;
        }

        $draft = $this->get_draft($draft_id);
        
        if (!$draft) {
            $this->send_error('Draft not found');
            return;
        }

        // Verify user owns this draft
        if ($draft['user_id'] !== get_current_user_id()) {
            $this->handle_security_failure('capabilities');
            return;
        }

        $this->send_success([
            'draft_id' => $draft_id,
            'form_type' => $draft['form_type'],
            'form_data' => $draft['form_data'],
            'created_date' => $draft['created_date'],
            'updated_date' => $draft['updated_date']
        ]);
    }

    /**
     * Save form draft
     */
    private function save_draft(string $form_type, array $form_data, bool $is_auto_save = false): string
    {
        $user_id = get_current_user_id();
        $draft_id = uniqid('draft_', true);
        
        $draft_data = [
            'draft_id' => $draft_id,
            'user_id' => $user_id,
            'form_type' => $form_type,
            'form_data' => $form_data,
            'is_auto_save' => $is_auto_save,
            'created_date' => current_time('mysql'),
            'updated_date' => current_time('mysql')
        ];

        // Store draft in database
        global $wpdb;
        $table_name = $wpdb->prefix . 'hph_form_drafts';
        
        $wpdb->insert($table_name, $draft_data, [
            '%s', '%d', '%s', '%s', '%d', '%s', '%s'
        ]);

        // Clean up old drafts for this user and form type
        $this->cleanup_user_drafts($user_id, $form_type);

        return $draft_id;
    }

    /**
     * Process listing submission
     */
    private function process_listing_submission(array $data)
    {
        $listing_id = $data['post_id'] ?? 0;
        $is_update = $listing_id > 0;

        // Prepare post data
        $post_data = [
            'post_title' => $data['post_title'],
            'post_content' => $data['post_content'] ?? '',
            'post_type' => 'listing',
            'post_status' => $data['_listing_status'] ?? 'draft',
            'meta_input' => []
        ];

        if ($is_update) {
            $post_data['ID'] = $listing_id;
        }

        // Add meta fields
        $meta_fields = [
            '_price', '_bedrooms', '_bathrooms', '_square_feet', '_lot_size', 
            '_year_built', '_property_type', '_address', '_city', '_state', 
            '_zip_code', '_latitude', '_longitude'
        ];

        foreach ($meta_fields as $field) {
            if (isset($data[$field])) {
                $post_data['meta_input'][$field] = $data[$field];
            }
        }

        // Save or update post
        if ($is_update) {
            $result_id = wp_update_post($post_data, true);
        } else {
            $result_id = wp_insert_post($post_data, true);
        }

        if (is_wp_error($result_id)) {
            return $result_id;
        }

        return [
            'listing_id' => $result_id,
            'edit_url' => get_edit_post_link($result_id),
            'status' => get_post_status($result_id)
        ];
    }

    /**
     * Process lead submission
     */
    private function process_lead_submission(array $data)
    {
        $lead_id = $data['post_id'] ?? 0;
        $is_update = $lead_id > 0;

        // Prepare post data
        $post_data = [
            'post_title' => $data['_first_name'] . ' ' . $data['_last_name'],
            'post_type' => 'lead',
            'post_status' => 'private', // Leads are always private
            'meta_input' => []
        ];

        if ($is_update) {
            $post_data['ID'] = $lead_id;
        }

        // Add meta fields
        $meta_fields = [
            '_first_name', '_last_name', '_email', '_phone', '_lead_source',
            '_lead_status', '_budget_min', '_budget_max', '_preferred_areas', '_notes'
        ];

        foreach ($meta_fields as $field) {
            if (isset($data[$field])) {
                $post_data['meta_input'][$field] = $data[$field];
            }
        }

        // Save or update post
        if ($is_update) {
            $result_id = wp_update_post($post_data, true);
        } else {
            $result_id = wp_insert_post($post_data, true);
        }

        if (is_wp_error($result_id)) {
            return $result_id;
        }

        return [
            'lead_id' => $result_id,
            'edit_url' => get_edit_post_link($result_id),
            'status' => get_post_status($result_id)
        ];
    }

    /**
     * Get draft by ID
     */
    private function get_draft(string $draft_id): ?array
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hph_form_drafts';
        
        $draft = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE draft_id = %s",
            $draft_id
        ), ARRAY_A);

        if ($draft) {
            $draft['form_data'] = json_decode($draft['form_data'], true);
        }

        return $draft;
    }

    /**
     * Delete draft
     */
    private function delete_draft(string $draft_id): bool
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hph_form_drafts';
        
        return $wpdb->delete($table_name, ['draft_id' => $draft_id], ['%s']) !== false;
    }

    /**
     * Clean up old drafts for user and form type
     */
    private function cleanup_user_drafts(int $user_id, string $form_type): void
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hph_form_drafts';
        
        // Keep only the most recent drafts
        $wpdb->query($wpdb->prepare("
            DELETE FROM {$table_name} 
            WHERE user_id = %d 
            AND form_type = %s 
            AND draft_id NOT IN (
                SELECT draft_id FROM (
                    SELECT draft_id 
                    FROM {$table_name} 
                    WHERE user_id = %d AND form_type = %s 
                    ORDER BY updated_date DESC 
                    LIMIT %d
                ) as keep_drafts
            )
        ", $user_id, $form_type, $user_id, $form_type, $this->auto_save_settings['max_drafts']));
    }

    /**
     * Clean up old drafts (scheduled task)
     */
    public function cleanup_old_drafts(): void
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hph_form_drafts';
        
        $cleanup_date = date('Y-m-d H:i:s', time() - $this->auto_save_settings['cleanup_after']);
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name} WHERE created_date < %s",
            $cleanup_date
        ));
    }

    // Placeholder methods for other form types
    public function save_open_house_form(): void { $this->send_error('Not implemented yet'); }
    public function save_agent_profile_form(): void { $this->send_error('Not implemented yet'); }
    public function validate_form_complete(): void { $this->send_error('Not implemented yet'); }
    public function delete_form_draft(): void { $this->send_error('Not implemented yet'); }
    public function list_form_drafts(): void { $this->send_error('Not implemented yet'); }
    public function upload_form_file(): void { $this->send_error('Not implemented yet'); }
    public function delete_form_file(): void { $this->send_error('Not implemented yet'); }
}
