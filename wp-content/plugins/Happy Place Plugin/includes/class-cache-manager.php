<?php

/**
 * Cache Manager Class
 * 
 * Handles various caching operations for the Happy Place Plugin
 */

namespace HappyPlace\Includes;

if (!defined('ABSPATH')) {
    exit;
}

class Cache_Manager
{

    /**
     * Clear all WordPress transients
     */
    public function clear_transients()
    {
        global $wpdb;

        $result = $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_%'"
        );

        return $result !== false;
    }

    /**
     * Clear object cache if enabled
     */
    public function clear_object_cache()
    {
        return wp_cache_flush();
    }

    /**
     * Clear page cache (if any page caching plugin is active)
     */
    public function clear_page_cache()
    {
        $cleared = false;

        // WP Super Cache
        if (function_exists('\wp_cache_clean_cache')) {
            global $file_prefix;
            \wp_cache_clean_cache($file_prefix, true);
            $cleared = true;
        }

        // W3 Total Cache
        if (function_exists('\w3tc_flush_all')) {
            \w3tc_flush_all();
            $cleared = true;
        }

        // WP Rocket
        if (function_exists('\rocket_clean_domain')) {
            \rocket_clean_domain();
            $cleared = true;
        }

        // WP Fastest Cache
        if (class_exists('WpFastestCache')) {
            do_action('wpfc_clear_all_cache');
            $cleared = true;
        }

        return $cleared;
    }

    /**
     * Clear all caches
     */
    public function clear_all_caches()
    {
        $results = [
            'transients' => $this->clear_transients(),
            'object_cache' => $this->clear_object_cache(),
            'page_cache' => $this->clear_page_cache()
        ];

        return $results;
    }

    /**
     * Get cache statistics
     */
    public function get_cache_stats()
    {
        global $wpdb;

        $stats = [];

        // Count transients
        $transient_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '%_transient_%'"
        );
        $stats['transients'] = (int) $transient_count;

        // Object cache info
        $stats['object_cache_enabled'] = wp_using_ext_object_cache();

        // Detect active caching plugins
        $active_cache_plugins = [];

        if (function_exists('wp_cache_clean_cache')) {
            $active_cache_plugins[] = 'WP Super Cache';
        }
        if (function_exists('w3tc_flush_all')) {
            $active_cache_plugins[] = 'W3 Total Cache';
        }
        if (function_exists('rocket_clean_domain')) {
            $active_cache_plugins[] = 'WP Rocket';
        }
        if (class_exists('WpFastestCache')) {
            $active_cache_plugins[] = 'WP Fastest Cache';
        }

        $stats['active_cache_plugins'] = $active_cache_plugins;

        return $stats;
    }
}
