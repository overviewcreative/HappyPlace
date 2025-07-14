<?php

/**
 * Agent Dashboard Class
 *
 * Main class for handling the agent dashboard functionality.
 */

namespace HappyPlace\Dashboard;

if (!defined('ABSPATH')) {
    exit;
}

class Agent_Dashboard
{
    private static ?self $instance = null;

    public static function get_instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // Initialize dashboard components
        add_action('init', [$this, 'init']);
        add_filter('template_include', [$this, 'maybe_load_dashboard_template']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function init(): void
    {
        // Register any necessary post types or taxonomies
        $this->register_rewrite_rules();
    }

    /**
     * Register rewrite rules for dashboard URLs
     */
    private function register_rewrite_rules(): void
    {
        add_rewrite_rule(
            '^agent-dashboard/?$',
            'index.php?pagename=agent-dashboard',
            'top'
        );

        add_rewrite_rule(
            '^agent-dashboard/([^/]+)/?$',
            'index.php?pagename=agent-dashboard&section=$matches[1]',
            'top'
        );

        // Add query vars
        add_filter('query_vars', function ($vars) {
            $vars[] = 'section';
            return $vars;
        });
    }

    /**
     * Maybe load the dashboard template
     */
    public function maybe_load_dashboard_template($template): string
    {
        if (is_page()) {
            $page_template = get_post_meta(get_the_ID(), '_wp_page_template', true);

            // Check for both template names
            if ($page_template === 'agent-dashboard.php' || $page_template === 'templates/agent-dashboard.php') {
                // First check theme template
                $theme_template = get_template_directory() . '/templates/agent-dashboard.php';
                if (file_exists($theme_template)) {
                    return $theme_template;
                }

                // Fallback to plugin template
                $plugin_template = HPH_PATH . 'templates/agent-dashboard.php';
                if (file_exists($plugin_template)) {
                    return $plugin_template;
                }
            }
        }

        return $template;
    }

    /**
     * Enqueue dashboard assets
     */
    public function enqueue_assets(): void
    {
        if (!$this->is_dashboard_page()) {
            return;
        }

        // Let theme handle asset loading
        do_action('hph_before_dashboard');
    }

    /**
     * Check if current page is dashboard
     */
    private function is_dashboard_page(): bool
    {
        global $post;

        if (!is_page()) {
            return false;
        }

        $template = get_post_meta($post->ID, '_wp_page_template', true);
        return $template === 'agent-dashboard.php' || $template === 'templates/agent-dashboard.php';
    }

    /**
     * Get current dashboard section
     */
    public static function get_section(): string
    {
        return get_query_var('section', 'overview');
    }

    /**
     * Get URL for dashboard section
     */
    public static function get_url(string $section = 'overview'): string
    {
        $base_url = home_url('agent-dashboard');
        return $section === 'overview' ? $base_url : trailingslashit($base_url) . $section;
    }
}
