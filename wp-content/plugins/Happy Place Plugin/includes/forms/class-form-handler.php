<?php
/**
 * Base form handler class
 */

namespace HappyPlace\Forms;

if (!defined('ABSPATH')) {
    exit;
}

abstract class Form_Handler {
    /**
     * Initialize the form handler
     */
    public function init() {
        \add_action('admin_post_' . $this->get_action(), [$this, 'handle_submission']);
        \add_action('admin_post_nopriv_' . $this->get_action(), [$this, 'handle_submission']);
    }

    /**
     * Get the form action name
     *
     * @return string
     */
    abstract protected function get_action();

    /**
     * Validate the nonce
     *
     * @param string $nonce
     * @param string $action
     * @return bool
     */
    protected function validate_nonce($nonce, $action) {
        if (!isset($_POST[$nonce]) || !\wp_verify_nonce($_POST[$nonce], $action)) {
            \wp_die('Invalid nonce specified', 'Error', [
                'response' => 403,
                'back_link' => true,
            ]);
        }
        return true;
    }

    /**
     * Upload file and return attachment ID
     *
     * @param string $file_key
     * @return int|WP_Error
     */
    protected function handle_file_upload($file_key) {
        if (!isset($_FILES[$file_key]) || empty($_FILES[$file_key]['name'])) {
            return 0;
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload($file_key, 0);

        if (is_wp_error($attachment_id)) {
            return 0;
        }

        return $attachment_id;
    }

    /**
     * Handle multiple file uploads
     *
     * @param string $file_key
     * @return array
     */
    protected function handle_multiple_file_uploads($file_key) {
        $attachment_ids = [];

        if (!isset($_FILES[$file_key])) {
            return $attachment_ids;
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $files = $_FILES[$file_key];
        $upload_overrides = ['test_form' => false];

        foreach ($files['name'] as $key => $value) {
            if ($files['name'][$key]) {
                $file = [
                    'name' => $files['name'][$key],
                    'type' => $files['type'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'error' => $files['error'][$key],
                    'size' => $files['size'][$key]
                ];

                $_FILES = array('upload_file' => $file);
                $attachment_id = media_handle_upload('upload_file', 0);

                if (!is_wp_error($attachment_id)) {
                    $attachment_ids[] = $attachment_id;
                }
            }
        }

        return $attachment_ids;
    }

    /**
     * Sanitize form data
     *
     * @param array $data
     * @return array
     */
    protected function sanitize_form_data($data) {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = array_map('sanitize_text_field', $value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        return $sanitized;
    }

    /**
     * Set success message in session
     *
     * @param string $message
     */
    protected function set_success_message($message) {
        if (!session_id() && headers_sent() === false) {
            session_start();
        }
        $_SESSION['form_success_message'] = $message;
    }

    /**
     * Set error message in session
     *
     * @param string $message
     */
    protected function set_error_message($message) {
        if (!session_id() && headers_sent() === false) {
            session_start();
        }
        $_SESSION['form_error_message'] = $message;
    }

    /**
     * Redirect back to the form
     *
     * @param string $url
     */
    protected function redirect_back($url = '') {
        if (empty($url)) {
            $url = wp_get_referer();
        }
        wp_safe_redirect($url);
        exit;
    }
}
