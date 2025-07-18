<?php
/**
 * Plugin Integration Class
 *
 * Handles integration with third-party plugins
 *
 * @package HappyPlace
 * @subpackage Integration
 */

namespace HappyPlace\Integration;

if (!defined('ABSPATH')) {
    exit;
}

class Plugin_Integration {
    /**
     * Instance of this class
     *
     * @var Plugin_Integration
     */
    private static $instance = null;

    /**
     * Get instance of this class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // ACF Integration
        add_filter('acf/get_post_types', [$this, 'acf_get_post_types'], 20);
        add_filter('acf/location/rule_types', [$this, 'acf_location_rules_types']);
        add_filter('acf/location/rule_values/listing', [$this, 'acf_location_rules_values_listing']);
        
        // Other plugin integrations can be added here
    }

    /**
     * Make post types available in ACF UI
     */
    public function acf_get_post_types($post_types) {
        if (post_type_exists('listing')) {
            $list_type = get_post_type_object('listing');
            $post_types['listing'] = $list_type->labels->singular_name;
        }
        if (post_type_exists('agent')) {
            $agent_type = get_post_type_object('agent');
            $post_types['agent'] = $agent_type->labels->singular_name;
        }
        return $post_types;
    }

    /**
     * Include post types in ACF location rules
     */
    public function acf_location_rules_types($choices) {
        if (post_type_exists('listing')) {
            $choices['Post']['listing'] = 'Listing';
        }
        if (post_type_exists('agent')) {
            $choices['Post']['agent'] = 'Agent';
        }
        return $choices;
    }

    /**
     * Add custom fields to ACF location rules
     */
    public function acf_location_rules_values_listing($choices) {
        $choices = [
            'featured' => 'Featured Listings',
            'new' => 'New Listings',
            'sold' => 'Sold Listings'
        ];
        return $choices;
    }
}
