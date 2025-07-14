<?php

/**
 * Performance Section
 *
 * @package HappyPlace
 * @subpackage Dashboard\Sections
 */

namespace HappyPlace\Dashboard\Sections;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Performance Section Class
 */
class Performance_Section
{
    /**
     * Initialize the section
     */
    public function __construct()
    {
        add_action('wp_ajax_happy_place_get_performance_data', array($this, 'get_performance_data'));
    }

    /**
     * Get performance data
     */
    public function get_performance_data()
    {
        // Verify nonce
        check_ajax_referer('happy_place_dashboard', 'nonce');

        // Check user capabilities
        if (! current_user_can('access_happy_place_dashboard')) {
            wp_send_json_error('Insufficient permissions');
        }

        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : '30days';
        $user_id = get_current_user_id();

        $data = array(
            'listing_views'    => $this->get_listing_views($user_id, $period),
            'inquiries'        => $this->get_inquiries($user_id, $period),
            'conversion_rate'  => $this->get_conversion_rate($user_id, $period),
            'popular_listings' => $this->get_popular_listings($user_id, $period),
            'chart_data'      => $this->get_chart_data($user_id, $period),
        );

        wp_send_json_success($data);
    }

    /**
     * Get listing views
     *
     * @param int    $user_id User ID.
     * @param string $period Time period.
     * @return array
     */
    private function get_listing_views($user_id, $period)
    {
        global $wpdb;

        $date_condition = $this->get_date_condition($period);

        $views = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT listing_id, COUNT(*) as view_count
                FROM {$wpdb->prefix}happy_place_listing_views
                WHERE user_id = %d
                AND {$date_condition}
                GROUP BY listing_id",
                $user_id
            )
        );

        return array(
            'total'     => array_sum(wp_list_pluck($views, 'view_count')),
            'by_listing' => $views,
        );
    }

    /**
     * Get inquiries data
     *
     * @param int    $user_id User ID.
     * @param string $period Time period.
     * @return array
     */
    private function get_inquiries($user_id, $period)
    {
        global $wpdb;

        $date_condition = $this->get_date_condition($period);

        $inquiries = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT listing_id, COUNT(*) as inquiry_count
                FROM {$wpdb->prefix}happy_place_inquiries
                WHERE agent_id = %d
                AND {$date_condition}
                GROUP BY listing_id",
                $user_id
            )
        );

        return array(
            'total'     => array_sum(wp_list_pluck($inquiries, 'inquiry_count')),
            'by_listing' => $inquiries,
        );
    }

    /**
     * Get conversion rate
     *
     * @param int    $user_id User ID.
     * @param string $period Time period.
     * @return float
     */
    private function get_conversion_rate($user_id, $period)
    {
        $views = $this->get_listing_views($user_id, $period);
        $inquiries = $this->get_inquiries($user_id, $period);

        if ($views['total'] === 0) {
            return 0;
        }

        return round(($inquiries['total'] / $views['total']) * 100, 2);
    }

    /**
     * Get popular listings
     *
     * @param int    $user_id User ID.
     * @param string $period Time period.
     * @return array
     */
    private function get_popular_listings($user_id, $period)
    {
        global $wpdb;

        $date_condition = $this->get_date_condition($period);

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT l.listing_id,
                        p.post_title as listing_title,
                        COUNT(l.id) as view_count,
                        COUNT(DISTINCT i.id) as inquiry_count
                FROM {$wpdb->prefix}happy_place_listing_views l
                LEFT JOIN {$wpdb->posts} p ON l.listing_id = p.ID
                LEFT JOIN {$wpdb->prefix}happy_place_inquiries i 
                    ON l.listing_id = i.listing_id 
                    AND i.{$date_condition}
                WHERE l.user_id = %d
                AND l.{$date_condition}
                GROUP BY l.listing_id
                ORDER BY view_count DESC
                LIMIT 5",
                $user_id
            )
        );
    }

    /**
     * Get chart data
     *
     * @param int    $user_id User ID.
     * @param string $period Time period.
     * @return array
     */
    private function get_chart_data($user_id, $period)
    {
        global $wpdb;

        $interval = $this->get_chart_interval($period);
        $date_condition = $this->get_date_condition($period);

        // Get views by date
        $views = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE_FORMAT(created_at, %s) as date,
                        COUNT(*) as count
                FROM {$wpdb->prefix}happy_place_listing_views
                WHERE user_id = %d
                AND {$date_condition}
                GROUP BY date
                ORDER BY created_at ASC",
                $interval,
                $user_id
            )
        );

        // Get inquiries by date
        $inquiries = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE_FORMAT(created_at, %s) as date,
                        COUNT(*) as count
                FROM {$wpdb->prefix}happy_place_inquiries
                WHERE agent_id = %d
                AND {$date_condition}
                GROUP BY date
                ORDER BY created_at ASC",
                $interval,
                $user_id
            )
        );

        return array(
            'labels' => wp_list_pluck($views, 'date'),
            'views'  => wp_list_pluck($views, 'count'),
            'inquiries' => wp_list_pluck($inquiries, 'count'),
        );
    }

    /**
     * Get date condition for SQL query
     *
     * @param string $period Time period.
     * @return string
     */
    private function get_date_condition($period)
    {
        switch ($period) {
            case '7days':
                return "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case '30days':
                return "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            case '90days':
                return "created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
            case 'year':
                return "created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            default:
                return "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        }
    }

    /**
     * Get chart interval format
     *
     * @param string $period Time period.
     * @return string
     */
    private function get_chart_interval($period)
    {
        switch ($period) {
            case '7days':
                return '%Y-%m-%d'; // Daily
            case '30days':
                return '%Y-%m-%d'; // Daily
            case '90days':
                return '%Y-%m-%W'; // Weekly
            case 'year':
                return '%Y-%m'; // Monthly
            default:
                return '%Y-%m-%d';
        }
    }
}
