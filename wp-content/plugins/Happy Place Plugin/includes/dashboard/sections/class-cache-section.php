<?php

/**
 * Cache Section
 *
 * @package HappyPlace
 * @subpackage Dashboard\Sections
 */

namespace HappyPlace\Dashboard\Sections;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Cache Section Class
 */
class Cache_Section
{
    /**
     * Initialize the section
     */
    public function __construct()
    {
        add_action('wp_ajax_happy_place_get_cache_stats', array($this, 'get_cache_stats'));
        add_action('wp_ajax_happy_place_clear_cache', array($this, 'clear_cache'));
        add_action('wp_ajax_happy_place_optimize_cache', array($this, 'optimize_cache'));
    }

    /**
     * Get cache statistics
     */
    public function get_cache_stats()
    {
        // Verify nonce
        check_ajax_referer('happy_place_dashboard', 'nonce');

        // Check user capabilities
        if (! current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $stats = array(
            'object_cache'   => $this->get_object_cache_stats(),
            'transient'      => $this->get_transient_stats(),
            'filesystem'     => $this->get_filesystem_stats(),
            'database'       => $this->get_database_stats(),
        );

        wp_send_json_success($stats);
    }

    /**
     * Clear cache
     */
    public function clear_cache()
    {
        // Verify nonce
        check_ajax_referer('happy_place_dashboard', 'nonce');

        // Check user capabilities
        if (! current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'all';

        switch ($type) {
            case 'object':
                $this->clear_object_cache();
                break;

            case 'transient':
                $this->clear_transients();
                break;

            case 'filesystem':
                $this->clear_filesystem_cache();
                break;

            case 'database':
                $this->optimize_database();
                break;

            case 'all':
                $this->clear_object_cache();
                $this->clear_transients();
                $this->clear_filesystem_cache();
                $this->optimize_database();
                break;

            default:
                wp_send_json_error('Invalid cache type');
        }

        // Get updated stats
        $stats = array(
            'object_cache'   => $this->get_object_cache_stats(),
            'transient'      => $this->get_transient_stats(),
            'filesystem'     => $this->get_filesystem_stats(),
            'database'       => $this->get_database_stats(),
        );

        wp_send_json_success(array(
            'message' => 'Cache cleared successfully',
            'stats'   => $stats,
        ));
    }

    /**
     * Optimize cache
     */
    public function optimize_cache()
    {
        // Verify nonce
        check_ajax_referer('happy_place_dashboard', 'nonce');

        // Check user capabilities
        if (! current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Perform optimization tasks
        $this->optimize_database();
        $this->cleanup_transients();
        $this->optimize_filesystem_cache();

        // Get updated stats
        $stats = array(
            'object_cache'   => $this->get_object_cache_stats(),
            'transient'      => $this->get_transient_stats(),
            'filesystem'     => $this->get_filesystem_stats(),
            'database'       => $this->get_database_stats(),
        );

        wp_send_json_success(array(
            'message' => 'Cache optimized successfully',
            'stats'   => $stats,
        ));
    }

    /**
     * Get object cache statistics
     *
     * @return array
     */
    private function get_object_cache_stats()
    {
        global $wp_object_cache;

        $stats = array(
            'type'      => wp_using_ext_object_cache() ? 'External' : 'Internal',
            'size'      => 0,
            'hits'      => 0,
            'misses'    => 0,
            'groups'    => array(),
        );

        if (method_exists($wp_object_cache, 'getStats')) {
            $cache_stats = $wp_object_cache->getStats();
            $stats['hits'] = $cache_stats['hits'];
            $stats['misses'] = $cache_stats['misses'];
        }

        return $stats;
    }

    /**
     * Get transient statistics
     *
     * @return array
     */
    private function get_transient_stats()
    {
        global $wpdb;

        $total = $wpdb->get_var(
            "SELECT COUNT(*)
            FROM $wpdb->options
            WHERE option_name LIKE '_transient_%'"
        );

        $expired = $wpdb->get_var(
            "SELECT COUNT(*)
            FROM $wpdb->options
            WHERE option_name LIKE '_transient_timeout_%'
            AND option_value < " . time()
        );

        return array(
            'total'     => (int) $total,
            'expired'   => (int) $expired,
            'active'    => (int) $total - (int) $expired,
        );
    }

    /**
     * Get filesystem cache statistics
     *
     * @return array
     */
    private function get_filesystem_stats()
    {
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/cache';

        $stats = array(
            'exists'    => is_dir($cache_dir),
            'writable'  => is_writable($cache_dir),
            'size'      => 0,
            'files'     => 0,
        );

        if ($stats['exists']) {
            $stats['size'] = $this->get_directory_size($cache_dir);
            $stats['files'] = $this->count_files($cache_dir);
        }

        return $stats;
    }

    /**
     * Get database statistics
     *
     * @return array
     */
    private function get_database_stats()
    {
        global $wpdb;

        $tables = $wpdb->get_results("SHOW TABLE STATUS");
        $total_size = 0;
        $total_overhead = 0;

        foreach ($tables as $table) {
            $total_size += $table->Data_length + $table->Index_length;
            $total_overhead += $table->Data_free;
        }

        return array(
            'size'          => $total_size,
            'overhead'      => $total_overhead,
            'tables'        => count($tables),
            'last_optimized' => get_option('happy_place_last_db_optimization'),
        );
    }

    /**
     * Clear object cache
     */
    private function clear_object_cache()
    {
        wp_cache_flush();
    }

    /**
     * Clear transients
     */
    private function clear_transients()
    {
        global $wpdb;

        $wpdb->query(
            "DELETE FROM $wpdb->options
            WHERE option_name LIKE '_transient_%'"
        );
    }

    /**
     * Clear filesystem cache
     */
    private function clear_filesystem_cache()
    {
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/cache';

        if (is_dir($cache_dir)) {
            $this->remove_directory($cache_dir);
        }
    }

    /**
     * Optimize database
     */
    private function optimize_database()
    {
        global $wpdb;

        $tables = $wpdb->get_col("SHOW TABLES");

        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE $table");
        }

        update_option('happy_place_last_db_optimization', current_time('mysql'));
    }

    /**
     * Cleanup expired transients
     */
    private function cleanup_transients()
    {
        global $wpdb;

        $wpdb->query(
            "DELETE FROM $wpdb->options
            WHERE option_name LIKE '_transient_timeout_%'
            AND option_value < " . time()
        );

        $wpdb->query(
            "DELETE FROM $wpdb->options
            WHERE option_name LIKE '_transient_%'
            AND option_name NOT IN (
                SELECT CONCAT('_transient_', SUBSTRING(option_name, 20))
                FROM $wpdb->options
                WHERE option_name LIKE '_transient_timeout_%'
            )"
        );
    }

    /**
     * Optimize filesystem cache
     */
    private function optimize_filesystem_cache()
    {
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/cache';

        if (is_dir($cache_dir)) {
            $this->cleanup_old_files($cache_dir, 7 * DAY_IN_SECONDS);
        }
    }

    /**
     * Get directory size
     *
     * @param string $dir Directory path.
     * @return int
     */
    private function get_directory_size($dir)
    {
        $size = 0;

        foreach (glob(rtrim($dir, '/') . '/*', GLOB_NOSORT) as $each) {
            $size += is_file($each) ? filesize($each) : $this->get_directory_size($each);
        }

        return $size;
    }

    /**
     * Count files in directory
     *
     * @param string $dir Directory path.
     * @return int
     */
    private function count_files($dir)
    {
        $count = 0;

        foreach (glob(rtrim($dir, '/') . '/*', GLOB_NOSORT) as $each) {
            $count += is_file($each) ? 1 : $this->count_files($each);
        }

        return $count;
    }

    /**
     * Remove directory and its contents
     *
     * @param string $dir Directory path.
     * @return bool
     */
    private function remove_directory($dir)
    {
        if (! is_dir($dir)) {
            return false;
        }

        $objects = scandir($dir);

        foreach ($objects as $object) {
            if ($object === '.' || $object === '..') {
                continue;
            }

            $path = $dir . '/' . $object;

            if (is_dir($path)) {
                $this->remove_directory($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($dir);
    }

    /**
     * Cleanup old files
     *
     * @param string $dir Directory path.
     * @param int    $max_age Maximum age in seconds.
     */
    private function cleanup_old_files($dir, $max_age)
    {
        if (! is_dir($dir)) {
            return;
        }

        $objects = scandir($dir);
        $now = time();

        foreach ($objects as $object) {
            if ($object === '.' || $object === '..') {
                continue;
            }

            $path = $dir . '/' . $object;

            if (is_dir($path)) {
                $this->cleanup_old_files($path, $max_age);
            } else {
                $file_time = filemtime($path);
                if ($now - $file_time > $max_age) {
                    unlink($path);
                }
            }
        }
    }
}
