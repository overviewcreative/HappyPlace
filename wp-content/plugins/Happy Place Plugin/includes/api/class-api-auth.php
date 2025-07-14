<?php

/**
 * API Authentication
 *
 * @package HappyPlace
 * @subpackage API
 */

namespace HappyPlace\API;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * API Authentication Class
 */
class API_Auth
{
    /**
     * Initialize the authentication
     */
    public function __construct()
    {
        add_filter('rest_authentication_errors', array($this, 'authenticate_request'));
    }

    /**
     * Authenticate API request
     *
     * @param \WP_Error|null|bool $result Error from another authentication handler,
     *                                    null if we should handle it, or another value if not.
     * @return \WP_Error|null|bool
     */
    public function authenticate_request($result)
    {
        // Pass through other authentications
        if (null !== $result) {
            return $result;
        }

        // Only authenticate our namespace
        if (! $this->is_happy_place_request()) {
            return null;
        }

        // Check for API key in headers
        $api_key = $this->get_api_key_from_request();
        if (! $api_key) {
            return new \WP_Error(
                'rest_forbidden',
                'API key required.',
                array('status' => 401)
            );
        }

        // Validate API key
        $user_id = $this->validate_api_key($api_key);
        if (! $user_id) {
            return new \WP_Error(
                'rest_forbidden',
                'Invalid API key.',
                array('status' => 401)
            );
        }

        // Set the current user
        wp_set_current_user($user_id);
        return true;
    }

    /**
     * Check if this is a Happy Place API request
     *
     * @return bool
     */
    private function is_happy_place_request()
    {
        if (! isset($_SERVER['REQUEST_URI'])) {
            return false;
        }

        return strpos($_SERVER['REQUEST_URI'], '/wp-json/happy-place/') !== false;
    }

    /**
     * Get API key from request headers
     *
     * @return string|false
     */
    private function get_api_key_from_request()
    {
        if (isset($_SERVER['HTTP_X_HP_API_KEY'])) {
            return sanitize_text_field($_SERVER['HTTP_X_HP_API_KEY']);
        }

        return false;
    }

    /**
     * Validate API key and return associated user ID
     *
     * @param string $api_key The API key to validate.
     * @return int|false
     */
    private function validate_api_key($api_key)
    {
        global $wpdb;

        $user_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->prefix}happy_place_api_keys WHERE api_key = %s AND active = 1",
                $api_key
            )
        );

        return $user_id ? (int) $user_id : false;
    }

    /**
     * Generate API key for user
     *
     * @param int $user_id User ID.
     * @return string|false
     */
    public function generate_api_key($user_id)
    {
        global $wpdb;

        // Generate a unique key
        $api_key = wp_generate_password(32, false);

        // Insert into database
        $result = $wpdb->insert(
            $wpdb->prefix . 'happy_place_api_keys',
            array(
                'user_id'    => $user_id,
                'api_key'    => $api_key,
                'created'    => current_time('mysql'),
                'last_used'  => null,
                'active'     => 1,
            ),
            array(
                '%d',
                '%s',
                '%s',
                '%s',
                '%d',
            )
        );

        return $result ? $api_key : false;
    }

    /**
     * Revoke API key
     *
     * @param string $api_key API key to revoke.
     * @return bool
     */
    public function revoke_api_key($api_key)
    {
        global $wpdb;

        $result = $wpdb->update(
            $wpdb->prefix . 'happy_place_api_keys',
            array('active' => 0),
            array('api_key' => $api_key),
            array('%d'),
            array('%s')
        );

        return $result !== false;
    }
}
