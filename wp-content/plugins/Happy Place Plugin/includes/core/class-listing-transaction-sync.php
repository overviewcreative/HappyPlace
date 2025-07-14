<?php

/**
 * Handle relationships between listings and transactions
 */

namespace HappyPlace\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Listing_Transaction_Sync
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
        // Hook into ACF saves for transaction status changes
        add_action('acf/save_post', [$this, 'sync_transaction_status_to_listing'], 20);
    }

    /**
     * Map transaction stages to listing statuses
     */
    private function get_status_mapping(): array
    {
        return [
            'pre_contract' => 'Active',
            'under_contract' => 'Pending',
            'contingency' => 'Pending',
            'closed' => 'Sold',
            'cancelled' => 'Active',
            // Coming Soon status is handled separately as it's a manual setting
        ];
    }

    /**
     * Update listing status when transaction status changes
     */
    public function sync_transaction_status_to_listing($post_id): void
    {
        // Handle ACF's user field format (e.g., 'user_1')
        if (is_string($post_id) && strpos($post_id, 'user_') === 0) {
            return;
        }

        // Cast to int for regular posts
        $post_id = (int) $post_id;

        // Only process for transaction post type
        if (get_post_type($post_id) !== 'transaction') {
            return;
        }

        // Get the related listing
        $listing_id = get_field('related_listing', $post_id);
        if (!$listing_id) {
            return;
        }

        // Get the transaction stage
        $transaction_stage = get_field('transaction_stage', $post_id);
        if (!$transaction_stage) {
            return;
        }

        // Get the mapped listing status
        $status_mapping = $this->get_status_mapping();
        $new_listing_status = $status_mapping[$transaction_stage] ?? null;

        if (!$new_listing_status) {
            error_log('HPH: Unknown transaction stage: ' . $transaction_stage);
            return;
        }

        // Get current listing status
        $current_status = get_field('status', $listing_id);

        // Only update if status is different
        if ($current_status !== $new_listing_status) {
            // Update the listing status
            update_field('status', $new_listing_status, $listing_id);

            // Update corresponding dates based on status
            $dates = get_field('listing_dates', $listing_id) ?: [];

            if ($new_listing_status === 'Pending' && empty($dates['date_pending'])) {
                $dates['date_pending'] = date('Y-m-d');
                update_field('listing_dates', $dates, $listing_id);
            } elseif ($new_listing_status === 'Sold' && empty($dates['date_sold'])) {
                $dates['date_sold'] = date('Y-m-d');
                update_field('listing_dates', $dates, $listing_id);
            }

            error_log(sprintf(
                'HPH: Updated listing #%d status from %s to %s based on transaction #%d stage %s',
                $listing_id,
                $current_status,
                $new_listing_status,
                $post_id,
                $transaction_stage
            ));
        }
    }

    /**
     * Handle listing status changes, including Coming Soon transitions
     *
     * @param int    $post_id The listing post ID.
     * @param string $old_status Previous listing status.
     * @param string $new_status New listing status.
     * @return void
     */
    public function handle_listing_status_change($post_id, $old_status, $new_status)
    {
        if (!$post_id || empty($new_status)) {
            return;
        }

        // If transitioning from Coming Soon to Active
        if ($old_status === 'Coming Soon' && $new_status === 'Active') {
            $intended_date = get_field('listing_dates_intended_list_date', $post_id);
            $today = current_time('Y-m-d');

            // If intended date is today or in the past, use it as the list date
            // Otherwise use today's date
            $list_date = $intended_date && $intended_date <= $today ? $intended_date : $today;

            update_field('listing_dates_date_listed', $list_date, $post_id);

            // Log the transition
            error_log(sprintf(
                'Listing #%d transitioned from Coming Soon to Active. Set list date to: %s',
                $post_id,
                $list_date
            ));
        }

        // Continue with existing transaction sync logic
        $this->sync_transaction_status_to_listing($post_id);
    }

    /**
     * Update listing badges based on dates and conditions
     *
     * @param int $post_id The listing post ID.
     * @return void
     */
    public function update_listing_badges($post_id)
    {
        if (!$post_id || get_post_type($post_id) !== 'listing') {
            return;
        }

        // Get current badges
        $highlight_badges = get_field('highlight_badges', $post_id) ?: array();

        // Check for new listing (within 14 days)
        $date_listed = get_field('listing_dates_date_listed', $post_id);
        if ($date_listed) {
            $list_date = new \DateTime($date_listed);
            $now = new \DateTime(current_time('Y-m-d'));
            $days_listed = $now->diff($list_date)->days;

            // Add or remove "new_listing" badge based on age
            $new_listing_key = array_search('new_listing', $highlight_badges);
            if ($days_listed <= 14 && $new_listing_key === false) {
                $highlight_badges[] = 'new_listing';
            } elseif ($days_listed > 14 && $new_listing_key !== false) {
                unset($highlight_badges[$new_listing_key]);
                $highlight_badges = array_values($highlight_badges); // Reindex array
            }

            // Update the badges if they've changed
            update_field('highlight_badges', $highlight_badges, $post_id);
        }
    }

    /**
     * Initialize hooks for badge management
     */
    public function init()
    {
        // Existing hooks
        // ...existing code...

        // Add hooks for badge management
        add_action('acf/save_post', array($this, 'update_listing_badges'), 20);
        add_action('the_post', array($this, 'update_listing_badges'));
    }
}
