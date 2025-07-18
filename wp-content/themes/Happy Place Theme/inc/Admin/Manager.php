<?php
/**
 * Dashboard Manager Class
 *
 * Handles agent dashboard functionality
 *
 * @package HappyPlace
 * @subpackage Dashboard
 */

namespace HappyPlace\Dashboard;

if (!defined('ABSPATH')) {
    exit;
}

class Manager {
    /**
     * Instance of this class
     *
     * @var Manager
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
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('template_include', [$this, 'load_dashboard_template'], 5);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_dashboard_assets']);
    }

    /**
     * Add rewrite rules for dashboard pages
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            'agent-dashboard/([^/]+)/?$',
            'index.php?pagename=agent-dashboard&dashboard_page=$matches[1]',
            'top'
        );
        add_rewrite_tag('%dashboard_page%', '([^&]+)');
    }

    /**
     * Load dashboard template
     */
    public function load_dashboard_template($template) {
        if (is_page('agent-dashboard')) {
            // Try multiple template locations
            $templates = [
                'page-templates/agent-dashboard-rebuilt.php',
                'templates/dashboard/agent-dashboard.php',
                'agent-dashboard-rebuilt.php',
                'agent-dashboard.php'
            ];
            
            $new_template = locate_template($templates);
            if (!empty($new_template)) {
                return $new_template;
            }
        }
        return $template;
    }

    /**
     * Enqueue dashboard assets
     */
    public function enqueue_dashboard_assets() {
        if (is_page('agent-dashboard') || is_page_template('agent-dashboard-rebuilt.php')) {
            // Load variables.css first as dependency
            wp_enqueue_style(
                'happy-place-variables',
                HPH_ASSETS_URI . '/css/variables.css',
                [],
                HPH_THEME_VERSION
            );
            
            // Load core.css with variables dependency
            wp_enqueue_style(
                'happy-place-core',
                HPH_ASSETS_URI . '/css/core.css',
                ['happy-place-variables'],
                HPH_THEME_VERSION
            );

            wp_enqueue_style(
                'happy-place-dashboard',
                HPH_ASSETS_URI . '/css/dashboard-rebuilt-scoped.css',
                ['happy-place-core'],
                HPH_THEME_VERSION
            );

            wp_enqueue_script(
                'happy-place-dashboard',
                HPH_ASSETS_URI . '/js/dashboard.js',
                ['jquery'],
                HPH_THEME_VERSION,
                true
            );

            wp_localize_script('happy-place-dashboard', 'happyPlaceDashboard', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('happy_place_dashboard_nonce')
            ]);
        }
    }
}
