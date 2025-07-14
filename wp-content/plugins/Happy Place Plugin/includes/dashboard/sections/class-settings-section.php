<?php

/**
 * Settings Section
 *
 * @package HappyPlace
 * @subpackage Dashboard\Sections
 */

namespace HappyPlace\Dashboard\Sections;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Settings Section Class
 */
class Settings_Section
{
    /**
     * Initialize the section
     */
    public function __construct()
    {
        add_action('wp_ajax_happy_place_update_settings', array($this, 'update_settings'));
        add_action('wp_ajax_happy_place_get_settings', array($this, 'get_settings'));
    }

    /**
     * Update settings
     */
    public function update_settings()
    {
        // Verify nonce
        check_ajax_referer('happy_place_dashboard', 'nonce');

        // Check user capabilities
        if (! current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $settings = array();

        // Email Notifications
        $settings['notifications'] = array(
            'new_inquiry'     => isset($_POST['notifications']['new_inquiry']),
            'new_review'      => isset($_POST['notifications']['new_review']),
            'listing_update'  => isset($_POST['notifications']['listing_update']),
            'daily_summary'   => isset($_POST['notifications']['daily_summary']),
            'weekly_report'   => isset($_POST['notifications']['weekly_report']),
        );

        // API Integration Settings
        $settings['api'] = array(
            'mls_id'         => sanitize_text_field($_POST['api']['mls_id']),
            'mls_key'        => sanitize_text_field($_POST['api']['mls_key']),
            'followup_boss'  => sanitize_text_field($_POST['api']['followup_boss']),
            'dotloop'        => sanitize_text_field($_POST['api']['dotloop']),
        );

        // Listing Display Settings
        $settings['listing'] = array(
            'default_view'   => sanitize_text_field($_POST['listing']['default_view']),
            'items_per_page' => absint($_POST['listing']['items_per_page']),
            'sort_order'     => sanitize_text_field($_POST['listing']['sort_order']),
            'show_map'       => isset($_POST['listing']['show_map']),
        );

        // Social Media Settings
        $settings['social'] = array(
            'auto_share'     => isset($_POST['social']['auto_share']),
            'platforms'      => array_map('sanitize_text_field', $_POST['social']['platforms']),
            'message_format' => wp_kses_post($_POST['social']['message_format']),
        );

        // Brand Settings
        $settings['brand'] = array(
            'primary_color'   => sanitize_hex_color($_POST['brand']['primary_color']),
            'secondary_color' => sanitize_hex_color($_POST['brand']['secondary_color']),
            'logo_id'        => absint($_POST['brand']['logo_id']),
            'company_info'    => wp_kses_post($_POST['brand']['company_info']),
        );

        // Update settings
        update_option('happy_place_settings', $settings);

        // Maybe flush rewrite rules if necessary settings changed
        if (isset($_POST['flush_rules']) && $_POST['flush_rules']) {
            flush_rewrite_rules();
        }

        wp_send_json_success(array(
            'message'  => 'Settings updated successfully',
            'settings' => $settings,
        ));
    }

    /**
     * Get settings
     */
    public function get_settings()
    {
        // Verify nonce
        check_ajax_referer('happy_place_dashboard', 'nonce');

        // Check user capabilities
        if (! current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $settings = get_option('happy_place_settings', array());

        // Ensure all setting groups exist with defaults
        $settings = wp_parse_args($settings, array(
            'notifications' => array(
                'new_inquiry'     => true,
                'new_review'      => true,
                'listing_update'  => true,
                'daily_summary'   => false,
                'weekly_report'   => true,
            ),
            'api' => array(
                'mls_id'         => '',
                'mls_key'        => '',
                'followup_boss'  => '',
                'dotloop'        => '',
            ),
            'listing' => array(
                'default_view'   => 'grid',
                'items_per_page' => 12,
                'sort_order'     => 'date_desc',
                'show_map'       => true,
            ),
            'social' => array(
                'auto_share'     => false,
                'platforms'      => array(),
                'message_format' => '',
            ),
            'brand' => array(
                'primary_color'   => '#000000',
                'secondary_color' => '#ffffff',
                'logo_id'        => 0,
                'company_info'    => '',
            ),
        ));

        // Add computed settings
        $settings['system'] = array(
            'wp_version'     => get_bloginfo('version'),
            'php_version'    => PHP_VERSION,
            'memory_limit'   => ini_get('memory_limit'),
            'upload_max'     => ini_get('upload_max_filesize'),
            'timezone'       => get_option('timezone_string'),
        );

        // Add capability checks
        $settings['capabilities'] = array(
            'uploads'        => wp_is_writable(wp_upload_dir()['basedir']),
            'permalinks'     => get_option('permalink_structure') !== '',
            'ssl'           => is_ssl(),
        );

        wp_send_json_success($settings);
    }

    /**
     * Reset settings to default
     */
    public function reset_settings()
    {
        // Verify nonce
        check_ajax_referer('happy_place_dashboard', 'nonce');

        // Check user capabilities
        if (! current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Delete settings option
        delete_option('happy_place_settings');

        // Get fresh settings with defaults
        $settings = $this->get_settings();

        wp_send_json_success(array(
            'message'  => 'Settings reset to default values',
            'settings' => $settings,
        ));
    }
}
