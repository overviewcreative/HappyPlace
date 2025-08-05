<?php
/**
 * Form handlers initialization
 */

namespace HappyPlace\Forms;

if (!defined('ABSPATH')) {
    exit;
}

class Forms {
    /**
     * Initialize form handlers
     */
    public function init() {
        // TODO: Implement CPT-specific form handlers
        // Form handlers will be created in Phase 2 of form system rebuild
        
        // For now, initialize basic form functionality
        add_action('wp_footer', [$this, 'display_messages']);
        
        // Initialize base form validation
        add_action('wp_ajax_hph_validate_form', [$this, 'ajax_validate_form']);
        add_action('wp_ajax_nopriv_hph_validate_form', [$this, 'ajax_validate_form']);
        
        error_log('HPH Forms: Basic form system initialized. CPT-specific handlers pending implementation.');
    }

    /**
     * Display form messages
     */
    public function display_messages() {
        if (!session_id()) {
            session_start();
        }

        if (!empty($_SESSION['form_success_message'])) {
            echo '<div class="form-message success">' . esc_html($_SESSION['form_success_message']) . '</div>';
            unset($_SESSION['form_success_message']);
        }

        if (!empty($_SESSION['form_error_message'])) {
            echo '<div class="form-message error">' . esc_html($_SESSION['form_error_message']) . '</div>';
            unset($_SESSION['form_error_message']);
        }
    }
    
    /**
     * AJAX form validation handler
     */
    public function ajax_validate_form() {
        check_ajax_referer('hph_form_validation', 'nonce');
        
        $form_type = sanitize_text_field($_POST['form_type'] ?? '');
        $form_data = $_POST['form_data'] ?? [];
        
        // Basic validation for now
        $errors = [];
        
        // Validate required fields
        foreach ($form_data as $field => $value) {
            if (empty($value) && isset($_POST['required_fields']) && in_array($field, $_POST['required_fields'])) {
                $errors[$field] = 'This field is required';
            }
        }
        
        if (empty($errors)) {
            wp_send_json_success(['message' => 'Validation passed']);
        } else {
            wp_send_json_error(['errors' => $errors]);
        }
    }
}
