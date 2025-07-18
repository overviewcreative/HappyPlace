<?php
/**
 * Dashboard Loader Class
 * 
 * Initializes all dashboard components
 */

namespace HappyPlace\Dashboard;

if (!defined('ABSPATH')) {
    exit;
}

class Loader {
    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Dashboard components
     */
    private $components = [];

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
        $this->init_components();
        add_action('init', [$this, 'initialize']);
    }

    /**
     * Initialize dashboard components
     */
    private function init_components() {
        $this->components = [
            'ajax' => Ajax_Handler::get_instance(),
            'ui' => UI_Manager::get_instance()
        ];
    }

    /**
     * Initialize dashboard functionality
     */
    public function initialize() {
        if (!is_user_logged_in()) {
            return;
        }

        // Check if user is an agent
        $user_id = get_current_user_id();
        $is_agent = get_user_meta($user_id, 'is_agent', true);

        if (!$is_agent) {
            return;
        }

        // Initialize components
        foreach ($this->components as $component) {
            if (method_exists($component, 'initialize')) {
                $component->initialize();
            }
        }

        // Add dashboard rewrite rules
        $this->add_rewrite_rules();

        // Add dashboard menu items
        add_action('admin_menu', [$this, 'add_menu_items']);
    }

    /**
     * Add rewrite rules for dashboard pages
     */
    private function add_rewrite_rules() {
        add_rewrite_rule(
            '^agent-dashboard/([^/]*)/?',
            'index.php?page_id=' . get_option('agent_dashboard_page_id') . '&tab=$matches[1]',
            'top'
        );
    }

    /**
     * Add dashboard menu items
     */
    public function add_menu_items() {
        add_menu_page(
            __('Agent Dashboard', 'happy-place'),
            __('Agent Dashboard', 'happy-place'),
            'read',
            'agent-dashboard',
            [$this, 'redirect_to_frontend_dashboard'],
            'dashicons-building',
            30
        );
    }

    /**
     * Redirect to frontend dashboard
     */
    public function redirect_to_frontend_dashboard() {
        wp_safe_redirect(home_url('agent-dashboard'));
        exit;
    }
}
