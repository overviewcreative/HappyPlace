<?php
/**
 * Dashboard UI Manager Class
 * 
 * Handles the rendering of dashboard components
 */

namespace HappyPlace\Dashboard;

use HappyPlace\Core\Template_Helper;
use HappyPlace\Core\Template_Structure;

if (!defined('ABSPATH')) {
    exit;
}

class UI_Manager {
    /**
     * Instance of this class
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
        add_action('wp_loaded', [$this, 'init_dashboard']);
    }

    /**
     * Initialize dashboard
     */
    public function init_dashboard() {
        if (!is_page('agent-dashboard')) {
            return;
        }

        add_filter('body_class', [$this, 'add_dashboard_body_class']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_dashboard_assets']);
    }

    /**
     * Add dashboard body class
     */
    public function add_dashboard_body_class($classes) {
        $classes[] = 'agent-dashboard';
        return $classes;
    }

    /**
     * Render dashboard navigation
     */
    public function render_navigation() {
        Template_Helper::get_dashboard_component('navigation', [
            'current_tab' => $this->get_current_tab()
        ]);
    }

    /**
     * Render dashboard content
     */
    public function render_content() {
        $tab = $this->get_current_tab();
        $method = 'render_' . $tab . '_tab';

        if (method_exists($this, $method)) {
            $this->$method();
        } else {
            $this->render_listings_tab(); // Default tab
        }
    }

    /**
     * Get current dashboard tab
     */
    private function get_current_tab() {
        $default_tab = 'listings';
        $allowed_tabs = ['listings', 'profile', 'statistics', 'settings'];
        
        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : $default_tab;
        
        return in_array($tab, $allowed_tabs) ? $tab : $default_tab;
    }

    /**
     * Render listings tab
     */
    private function render_listings_tab() {
        $user_id = get_current_user_id();
        
        // Get user's listings
        $listings = get_posts([
            'post_type' => 'listing',
            'author' => $user_id,
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft', 'pending']
        ]);

        Template_Helper::get_dashboard_component('listings', [
            'listings' => $listings
        ]);
    }

    /**
     * Render profile tab
     */
    private function render_profile_tab() {
        $user_id = get_current_user_id();
        $agent_id = get_user_meta($user_id, 'associated_agent_id', true);
        
        if (!$agent_id) {
            Template_Helper::get_dashboard_component('profile-setup');
            return;
        }

        $agent = get_post($agent_id);
        
        Template_Helper::get_dashboard_component('profile', [
            'agent' => $agent,
            'phone' => get_post_meta($agent_id, 'phone', true),
            'email' => get_post_meta($agent_id, 'email', true),
            'specialties' => get_post_meta($agent_id, 'specialties', true)
        ]);
    }

    /**
     * Render statistics tab
     */
    private function render_statistics_tab() {
        $user_id = get_current_user_id();
        
        // Get listing statistics
        $total_listings = count_user_posts($user_id, 'listing');
        $active_listings = count_user_posts($user_id, 'listing', 'publish');
        $draft_listings = count_user_posts($user_id, 'listing', 'draft');
        
        // Get view statistics
        $view_stats = $this->get_listing_view_stats($user_id);
        
        Template_Helper::get_dashboard_component('statistics', [
            'total_listings' => $total_listings,
            'active_listings' => $active_listings,
            'draft_listings' => $draft_listings,
            'view_stats' => $view_stats
        ]);
    }

    /**
     * Render settings tab
     */
    private function render_settings_tab() {
        $user_id = get_current_user_id();
        $notification_settings = get_user_meta($user_id, 'notification_settings', true);
        
        Template_Helper::get_dashboard_component('settings', [
            'notification_settings' => $notification_settings ?: []
        ]);
    }

    /**
     * Get listing view statistics
     */
    private function get_listing_view_stats($user_id) {
        // Get user's listings
        $listings = get_posts([
            'post_type' => 'listing',
            'author' => $user_id,
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);

        $stats = [];
        foreach ($listings as $listing) {
            $views = get_post_meta($listing->ID, 'listing_views', true) ?: 0;
            $inquiries = get_post_meta($listing->ID, 'listing_inquiries', true) ?: 0;
            
            $stats[] = [
                'id' => $listing->ID,
                'title' => $listing->post_title,
                'views' => $views,
                'inquiries' => $inquiries
            ];
        }

        return $stats;
    }
}
