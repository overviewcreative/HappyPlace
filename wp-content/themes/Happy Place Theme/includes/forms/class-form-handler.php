<?php
/**
 * Abstract Form Handler Base Class
 * 
 * Base class for handling form submissions in WordPress with validation,
 * sanitization, and error handling.
 *
 * @package HappyPlace
 */

abstract class HPH_Form_Handler {
    /**
     * Form action name
     *
     * @var string
     */
    protected $action = '';

    /**
     * Form errors
     *
     * @var array
     */
    protected $errors = array();

    /**
     * Initialize the handler
     */
    public function __construct() {
        // Hook into WordPress actions
        add_action('admin_post_' . $this->action, array($this, 'handle_submission'));
        add_action('admin_post_nopriv_' . $this->action, array($this, 'handle_submission'));

        // Hook into AJAX actions if needed
        add_action('wp_ajax_' . $this->action, array($this, 'handle_ajax_submission'));
        add_action('wp_ajax_nopriv_' . $this->action, array($this, 'handle_ajax_submission'));

        // Initialize session if not started
        if (!session_id()) {
            session_start();
        }
    }

    /**
     * Set the form action name
     *
     * @param string $action Action name
     */
    protected function set_action($action) {
        $this->action = $action;
    }

    /**
     * Handle form submission
     */
    public function handle_submission() {
        // Verify nonce
        $nonce_name = $this->action . '_nonce';
        if (!isset($_POST[$nonce_name]) || !wp_verify_nonce($_POST[$nonce_name], $this->action)) {
            wp_die(__('Security check failed', 'happy-place'));
        }

        // Get and sanitize form data
        $data = $this->sanitize($_POST);

        // Validate form data
        if (!$this->validate($data)) {
            $this->handle_error($data);
            return;
        }

        // Process form data
        $result = $this->process($data);

        if (is_wp_error($result)) {
            $this->add_error('general', $result->get_error_message());
            $this->handle_error($data);
            return;
        }

        $this->handle_success($data);
    }

    /**
     * Handle AJAX form submission
     */
    public function handle_ajax_submission() {
        check_ajax_referer($this->action, 'nonce');

        // Get and sanitize form data
        $data = $this->sanitize($_POST);

        // Validate form data
        if (!$this->validate($data)) {
            wp_send_json_error(array(
                'message' => __('Validation failed', 'happy-place'),
                'errors' => $this->errors
            ));
        }

        // Process form data
        $result = $this->process($data);

        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }

        wp_send_json_success(array(
            'message' => __('Form submitted successfully', 'happy-place')
        ));
    }

    /**
     * Add an error message
     *
     * @param string $field Field name
     * @param string $message Error message
     */
    protected function add_error($field, $message) {
        $this->errors[$field] = $message;
    }

    /**
     * Add a flash message to session
     *
     * @param string $message Message text
     * @param string $type Message type (success/error)
     */
    protected function add_message($message, $type = 'success') {
        if (!isset($_SESSION['hph_messages'])) {
            $_SESSION['hph_messages'] = array();
        }

        $_SESSION['hph_messages'][] = array(
            'message' => $message,
            'type' => $type
        );
    }

    /**
     * Get flash messages from session
     *
     * @return array Messages
     */
    public static function get_messages() {
        $messages = isset($_SESSION['hph_messages']) ? $_SESSION['hph_messages'] : array();
        unset($_SESSION['hph_messages']);
        return $messages;
    }

    /**
     * Handle successful submission
     *
     * @param array $data Processed form data
     */
    protected function handle_success($data) {
        wp_safe_redirect(wp_get_referer() ?: home_url());
        exit;
    }

    /**
     * Handle failed submission
     *
     * @param array $data Form data
     */
    protected function handle_error($data) {
        if (!empty($this->errors)) {
            $_SESSION[$this->action . '_errors'] = $this->errors;
            $_SESSION[$this->action . '_data'] = $data;
        }

        wp_safe_redirect(wp_get_referer() ?: home_url());
        exit;
    }

    /**
     * Validate form data
     *
     * @param array $data Form data
     * @return bool Whether validation passed
     */
    abstract protected function validate($data);

    /**
     * Sanitize form data
     *
     * @param array $data Raw form data
     * @return array Sanitized form data
     */
    abstract protected function sanitize($data);

    /**
     * Process the form submission
     *
     * @param array $data Sanitized form data
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    abstract protected function process($data);
}
