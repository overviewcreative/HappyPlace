<?php
/**
 * View Tracking System Initialization Script
 * Run this once to set up the view tracking system
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class HPH_View_Tracking_Setup {
    
    public static function initialize() {
        self::create_database_tables();
        self::schedule_cleanup_task();
        self::set_default_options();
        self::add_meta_fields();
        
        // Log initialization
        error_log('HPH View Tracking System: Initialized successfully');
    }
    
    private static function create_database_tables() {
        global $wpdb;

        // Enable error reporting for debugging
        $wpdb->show_errors();
        
        $charset_collate = $wpdb->get_charset_collate();

        // Main tracking table
        $tracking_table = $wpdb->prefix . 'hph_view_tracking';
        $tracking_sql = "CREATE TABLE IF NOT EXISTS $tracking_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            listing_id bigint(20) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            session_id varchar(64) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            referrer text,
            view_date datetime DEFAULT CURRENT_TIMESTAMP,
            view_duration int(11) DEFAULT 0,
            page_type varchar(50) DEFAULT 'single',
            device_type varchar(20) DEFAULT 'desktop',
            is_unique_session tinyint(1) DEFAULT 1,
            latitude decimal(10,8) DEFAULT NULL,
            longitude decimal(11,8) DEFAULT NULL,
            city varchar(100) DEFAULT NULL,
            country varchar(100) DEFAULT NULL,
            interactions text DEFAULT NULL,
            scroll_depth int(3) DEFAULT 0,
            viewport_time int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY listing_id (listing_id),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY view_date (view_date),
            KEY is_unique_session (is_unique_session),
            KEY device_type (device_type)
        ) $charset_collate;";

        // Summary table for fast queries
        $summary_table = $wpdb->prefix . 'hph_view_summary';
        $summary_sql = "CREATE TABLE IF NOT EXISTS $summary_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            listing_id bigint(20) NOT NULL,
            date_recorded date NOT NULL,
            total_views int(11) DEFAULT 0,
            unique_views int(11) DEFAULT 0,
            avg_duration decimal(8,2) DEFAULT 0,
            bounce_rate decimal(5,2) DEFAULT 0,
            total_interactions int(11) DEFAULT 0,
            avg_scroll_depth decimal(5,2) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY listing_date (listing_id, date_recorded),
            KEY listing_id (listing_id),
            KEY date_recorded (date_recorded)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $result1 = dbDelta($tracking_sql);
        $result2 = dbDelta($summary_sql);
        
        // Log results
        error_log('HPH View Tracking: Main table creation - ' . print_r($result1, true));
        error_log('HPH View Tracking: Summary table creation - ' . print_r($result2, true));
    }
    
    private static function schedule_cleanup_task() {
        if (!wp_next_scheduled('hph_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'hph_daily_cleanup');
        }
    }
    
    private static function set_default_options() {
        $default_options = [
            'hph_view_tracking_enabled' => true,
            'hph_track_anonymous_users' => true,
            'hph_track_agent_own_views' => false,
            'hph_session_timeout' => 1800, // 30 minutes
            'hph_min_view_duration' => 5, // 5 seconds
            'hph_heartbeat_interval' => 30, // 30 seconds
            'hph_data_retention_days' => 365,
            'hph_geographic_tracking' => false // Disabled by default for privacy
        ];
        
        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }
    
    private static function add_meta_fields() {
        // Get all published listings and initialize view counters
        $listings = get_posts([
            'post_type' => 'listing',
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids'
        ]);
        
        foreach ($listings as $listing_id) {
            // Initialize view counters if they don't exist
            if (!get_post_meta($listing_id, '_listing_views', true)) {
                update_post_meta($listing_id, '_listing_views', 0);
            }
            
            if (!get_post_meta($listing_id, '_listing_unique_views', true)) {
                update_post_meta($listing_id, '_listing_unique_views', 0);
            }
            
            // Initialize monthly view counter
            $monthly_key = '_monthly_views_' . date('Y_m');
            if (!get_post_meta($listing_id, $monthly_key, true)) {
                update_post_meta($listing_id, $monthly_key, 0);
            }
        }
    }
    
    public static function uninstall() {
        global $wpdb;
        
        // Remove tables
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}hph_view_tracking");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}hph_view_summary");
        
        // Remove scheduled tasks
        wp_clear_scheduled_hook('hph_daily_cleanup');
        
        // Remove options
        $options_to_remove = [
            'hph_view_tracking_enabled',
            'hph_track_anonymous_users', 
            'hph_track_agent_own_views',
            'hph_session_timeout',
            'hph_min_view_duration',
            'hph_heartbeat_interval',
            'hph_data_retention_days',
            'hph_geographic_tracking'
        ];
        
        foreach ($options_to_remove as $option) {
            delete_option($option);
        }
        
        error_log('HPH View Tracking System: Uninstalled successfully');
    }
}

// Auto-initialize if this is included
if (defined('WP_INSTALLING') && WP_INSTALLING) {
    // Don't run during WordPress installation
} else {
    // Initialize the tracking system
    add_action('init', function() {
        if (get_option('hph_view_tracking_initialized') !== 'yes') {
            HPH_View_Tracking_Setup::initialize();
            update_option('hph_view_tracking_initialized', 'yes');
        }
    });
}
