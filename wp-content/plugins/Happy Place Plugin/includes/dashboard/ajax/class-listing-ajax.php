<?php

/**
 * Listing AJAX Handler
 * 
 * Handles listing-specific AJAX operations including management,
 * filtering, searching, and bulk operations.
 * 
 * @package HappyPlace\Dashboard\Ajax
 * @since 3.0.0
 */

namespace HappyPlace\Dashboard\Ajax;

use Exception;
use WP_Query;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Listing AJAX Handler Class
 * 
 * Handles:
 * - Listing management operations
 * - Listing filtering and searching
 * - Bulk operations on listings
 * - Listing status management
 * - Listing analytics and tracking
 */
class HPH_Listing_Ajax extends HPH_Base_Ajax
{
    /**
     * @var array Allowed listing statuses
     */
    private array $allowed_statuses = ['publish', 'draft', 'pending', 'sold', 'expired'];

    /**
     * @var array Sortable fields
     */
    private array $sortable_fields = ['date', 'title', 'price', 'bedrooms', 'bathrooms', 'square_feet'];

    /**
     * Register AJAX actions for listing management
     */
    protected function register_ajax_actions(): void
    {
        // Listing data actions
        add_action('wp_ajax_hph_get_listings', [$this, 'get_listings']);
        add_action('wp_ajax_hph_get_listing_data', [$this, 'get_listing_data']);
        add_action('wp_ajax_hph_search_listings', [$this, 'search_listings']);
        add_action('wp_ajax_hph_filter_listings', [$this, 'filter_listings']);
        
        // Listing management actions
        add_action('wp_ajax_hph_duplicate_listing', [$this, 'duplicate_listing']);
        add_action('wp_ajax_hph_delete_listing', [$this, 'delete_listing']);
        add_action('wp_ajax_hph_toggle_listing_status', [$this, 'toggle_listing_status']);
        add_action('wp_ajax_hph_bulk_listing_action', [$this, 'bulk_listing_action']);
        
        // Listing media actions
        add_action('wp_ajax_hph_upload_listing_image', [$this, 'upload_listing_image']);
        add_action('wp_ajax_hph_delete_listing_image', [$this, 'delete_listing_image']);
        add_action('wp_ajax_hph_reorder_listing_images', [$this, 'reorder_listing_images']);
        
        // Listing analytics
        add_action('wp_ajax_hph_get_listing_analytics', [$this, 'get_listing_analytics']);
        add_action('wp_ajax_hph_track_listing_view', [$this, 'track_listing_view']);
        
        // Public actions (for frontend)
        add_action('wp_ajax_hph_get_featured_listings', [$this, 'get_featured_listings']);
        add_action('wp_ajax_nopriv_hph_get_featured_listings', [$this, 'get_featured_listings']);
        add_action('wp_ajax_hph_get_listing_details', [$this, 'get_listing_details']);
        add_action('wp_ajax_nopriv_hph_get_listing_details', [$this, 'get_listing_details']);
    }

    /**
     * Get listings with filtering, sorting, and pagination
     */
    public function get_listings(): void
    {
        // Security verification for authenticated actions
        if (!$this->verify_nonce()) {
            $this->handle_security_failure('nonce');
            return;
        }

        if (!$this->check_capabilities(['edit_posts'])) {
            $this->handle_security_failure('capabilities');
            return;
        }

        // Get parameters
        $page = max(1, intval($_POST['page'] ?? 1));
        $per_page = min(50, max(1, intval($_POST['per_page'] ?? 12)));
        $status = sanitize_key($_POST['status'] ?? '');
        $search = sanitize_text_field($_POST['search'] ?? '');
        $sort_by = sanitize_key($_POST['sort_by'] ?? 'date');
        $sort_order = sanitize_key($_POST['sort_order'] ?? 'DESC');
        $filters = $_POST['filters'] ?? [];

        // Validate sort parameters
        if (!in_array($sort_by, $this->sortable_fields)) {
            $sort_by = 'date';
        }
        if (!in_array($sort_order, ['ASC', 'DESC'])) {
            $sort_order = 'DESC';
        }

        try {
            $user_context = $this->get_user_context();
            $listings_data = $this->fetch_listings([
                'page' => $page,
                'per_page' => $per_page,
                'status' => $status,
                'search' => $search,
                'sort_by' => $sort_by,
                'sort_order' => $sort_order,
                'filters' => $filters,
                'user_id' => $user_context['user_id']
            ]);

            $this->send_success([
                'listings' => $listings_data['listings'],
                'pagination' => $listings_data['pagination'],
                'total_count' => $listings_data['total'],
                'filters_applied' => $listings_data['filters_applied'],
                'cache_time' => 300
            ]);

        } catch (Exception $e) {
            error_log('HPH Listings Fetch Error: ' . $e->getMessage());
            $this->send_error('Failed to fetch listings');
        }
    }

    /**
     * Get detailed data for a specific listing
     */
    public function get_listing_data(): void
    {
        if (!$this->verify_nonce()) {
            $this->handle_security_failure('nonce');
            return;
        }

        $listing_id = intval($_POST['listing_id'] ?? 0);
        
        if ($listing_id <= 0) {
            $this->send_error('Valid listing ID required');
            return;
        }

        // Check if user can view this listing
        if (!$this->user_can_view_listing($listing_id)) {
            $this->handle_security_failure('capabilities');
            return;
        }

        try {
            $listing_data = $this->get_detailed_listing_data($listing_id);
            
            if (!$listing_data) {
                $this->send_error('Listing not found');
                return;
            }

            $this->send_success([
                'listing' => $listing_data,
                'can_edit' => current_user_can('edit_post', $listing_id),
                'can_delete' => current_user_can('delete_post', $listing_id)
            ]);

        } catch (Exception $e) {
            error_log('HPH Listing Data Error: ' . $e->getMessage());
            $this->send_error('Failed to fetch listing data');
        }
    }

    /**
     * Search listings with autocomplete support
     */
    public function search_listings(): void
    {
        $search_term = sanitize_text_field($_POST['search'] ?? '');
        $limit = min(20, max(1, intval($_POST['limit'] ?? 10)));
        $include_meta = (bool)($_POST['include_meta'] ?? false);

        if (strlen($search_term) < 2) {
            $this->send_success(['listings' => []]);
            return;
        }

        try {
            $results = $this->perform_listing_search($search_term, $limit, $include_meta);
            
            $this->send_success([
                'listings' => $results,
                'search_term' => $search_term,
                'total_found' => count($results)
            ]);

        } catch (Exception $e) {
            error_log('HPH Listing Search Error: ' . $e->getMessage());
            $this->send_error('Search failed');
        }
    }

    /**
     * Duplicate an existing listing
     */
    public function duplicate_listing(): void
    {
        if (!$this->verify_nonce()) {
            $this->handle_security_failure('nonce');
            return;
        }

        if (!$this->check_capabilities(['edit_posts'])) {
            $this->handle_security_failure('capabilities');
            return;
        }

        $listing_id = intval($_POST['listing_id'] ?? 0);
        
        if ($listing_id <= 0) {
            $this->send_error('Valid listing ID required');
            return;
        }

        // Check if user can edit this listing
        if (!current_user_can('edit_post', $listing_id)) {
            $this->handle_security_failure('capabilities');
            return;
        }

        try {
            $new_listing_id = $this->duplicate_listing_data($listing_id);
            
            if (!$new_listing_id) {
                $this->send_error('Failed to duplicate listing');
                return;
            }

            // Log activity
            $this->log_activity('duplicate_listing', [
                'original_id' => $listing_id,
                'new_id' => $new_listing_id
            ]);

            $this->send_success([
                'new_listing_id' => $new_listing_id,
                'edit_url' => get_edit_post_link($new_listing_id),
                'view_url' => get_permalink($new_listing_id)
            ], 'Listing duplicated successfully');

        } catch (Exception $e) {
            error_log('HPH Listing Duplicate Error: ' . $e->getMessage());
            $this->send_error('Failed to duplicate listing');
        }
    }

    /**
     * Toggle listing status
     */
    public function toggle_listing_status(): void
    {
        if (!$this->verify_nonce()) {
            $this->handle_security_failure('nonce');
            return;
        }

        $listing_id = intval($_POST['listing_id'] ?? 0);
        $new_status = sanitize_key($_POST['status'] ?? '');
        
        if ($listing_id <= 0) {
            $this->send_error('Valid listing ID required');
            return;
        }

        if (!in_array($new_status, $this->allowed_statuses)) {
            $this->send_error('Invalid status');
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $listing_id)) {
            $this->handle_security_failure('capabilities');
            return;
        }

        try {
            $result = wp_update_post([
                'ID' => $listing_id,
                'post_status' => $new_status
            ]);

            if (is_wp_error($result)) {
                $this->send_error($result->get_error_message());
                return;
            }

            // Log activity
            $this->log_activity('toggle_listing_status', [
                'listing_id' => $listing_id,
                'new_status' => $new_status
            ]);

            $this->send_success([
                'listing_id' => $listing_id,
                'status' => $new_status,
                'status_label' => $this->get_status_label($new_status)
            ], "Listing status updated to {$new_status}");

        } catch (Exception $e) {
            error_log('HPH Listing Status Error: ' . $e->getMessage());
            $this->send_error('Failed to update listing status');
        }
    }

    /**
     * Bulk actions on multiple listings
     */
    public function bulk_listing_action(): void
    {
        if (!$this->verify_nonce()) {
            $this->handle_security_failure('nonce');
            return;
        }

        if (!$this->check_capabilities(['edit_posts'])) {
            $this->handle_security_failure('capabilities');
            return;
        }

        $action = sanitize_key($_POST['action'] ?? '');
        $listing_ids = array_map('intval', $_POST['listing_ids'] ?? []);
        
        if (empty($listing_ids)) {
            $this->send_error('No listings selected');
            return;
        }

        // Limit bulk operations
        if (count($listing_ids) > 50) {
            $this->send_error('Too many listings selected. Maximum 50 allowed.');
            return;
        }

        try {
            $results = $this->perform_bulk_action($action, $listing_ids);
            
            $this->send_success([
                'action' => $action,
                'processed' => $results['processed'],
                'failed' => $results['failed'],
                'total' => count($listing_ids)
            ], "Bulk action '{$action}' completed");

        } catch (Exception $e) {
            error_log('HPH Bulk Action Error: ' . $e->getMessage());
            $this->send_error('Bulk action failed');
        }
    }

    /**
     * Fetch listings based on parameters
     */
    private function fetch_listings(array $params): array
    {
        $query_args = [
            'post_type' => 'listing',
            'post_status' => $params['status'] ?: ['publish', 'draft', 'pending'],
            'posts_per_page' => $params['per_page'],
            'paged' => $params['page'],
            'orderby' => $this->get_orderby_field($params['sort_by']),
            'order' => $params['sort_order']
        ];

        // Add search if provided
        if (!empty($params['search'])) {
            $query_args['s'] = $params['search'];
        }

        // Add author filter for non-admin users
        if (!current_user_can('edit_others_posts')) {
            $query_args['author'] = $params['user_id'];
        }

        // Add meta query for custom filters
        if (!empty($params['filters'])) {
            $query_args['meta_query'] = $this->build_meta_query($params['filters']);
        }

        $query = new WP_Query($query_args);
        
        $listings = [];
        foreach ($query->posts as $post) {
            $listings[] = $this->format_response_data($this->get_listing_array($post), 'listing');
        }

        return [
            'listings' => $listings,
            'total' => $query->found_posts,
            'pagination' => [
                'current_page' => $params['page'],
                'total_pages' => $query->max_num_pages,
                'per_page' => $params['per_page'],
                'total_items' => $query->found_posts
            ],
            'filters_applied' => !empty($params['filters']) || !empty($params['search'])
        ];
    }

    /**
     * Get detailed listing data including all metadata
     */
    private function get_detailed_listing_data(int $listing_id): ?array
    {
        $post = get_post($listing_id);
        
        if (!$post || $post->post_type !== 'listing') {
            return null;
        }

        $listing_data = $this->get_listing_array($post);
        
        // Add additional metadata
        $listing_data['gallery'] = $this->get_listing_gallery($listing_id);
        $listing_data['views'] = get_post_meta($listing_id, '_listing_views', true) ?: 0;
        $listing_data['inquiries'] = $this->get_listing_inquiry_count($listing_id);
        $listing_data['last_updated'] = $post->post_modified;
        
        return $this->format_response_data($listing_data, 'listing');
    }

    /**
     * Convert post object to listing array
     */
    private function get_listing_array($post): array
    {
        $meta = get_post_meta($post->ID);
        
        return [
            'ID' => $post->ID,
            'post_title' => $post->post_title,
            'post_content' => $post->post_content,
            'post_status' => $post->post_status,
            'post_date' => $post->post_date,
            'post_modified' => $post->post_modified,
            '_price' => $meta['_price'][0] ?? '',
            '_bedrooms' => $meta['_bedrooms'][0] ?? '',
            '_bathrooms' => $meta['_bathrooms'][0] ?? '',
            '_square_feet' => $meta['_square_feet'][0] ?? '',
            '_lot_size' => $meta['_lot_size'][0] ?? '',
            '_year_built' => $meta['_year_built'][0] ?? '',
            '_property_type' => $meta['_property_type'][0] ?? '',
            '_address' => $meta['_address'][0] ?? '',
            '_city' => $meta['_city'][0] ?? '',
            '_state' => $meta['_state'][0] ?? '',
            '_zip_code' => $meta['_zip_code'][0] ?? ''
        ];
    }

    /**
     * Perform listing search
     */
    private function perform_listing_search(string $search_term, int $limit, bool $include_meta): array
    {
        $query_args = [
            'post_type' => 'listing',
            'post_status' => ['publish', 'draft'],
            'posts_per_page' => $limit,
            's' => $search_term,
            'orderby' => 'relevance'
        ];

        // Restrict to user's own listings if not admin
        if (!current_user_can('edit_others_posts')) {
            $query_args['author'] = get_current_user_id();
        }

        $query = new WP_Query($query_args);
        
        $results = [];
        foreach ($query->posts as $post) {
            $result = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'status' => $post->post_status,
                'permalink' => get_permalink($post->ID)
            ];

            if ($include_meta) {
                $result['price'] = get_post_meta($post->ID, '_price', true);
                $result['address'] = get_post_meta($post->ID, '_address', true);
                $result['featured_image'] = get_the_post_thumbnail_url($post->ID, 'thumbnail');
            }

            $results[] = $result;
        }

        return $results;
    }

    /**
     * Duplicate listing with all metadata
     */
    private function duplicate_listing_data(int $listing_id): ?int
    {
        $original_post = get_post($listing_id);
        
        if (!$original_post) {
            return null;
        }

        // Create new post
        $new_post_data = [
            'post_title' => $original_post->post_title . ' (Copy)',
            'post_content' => $original_post->post_content,
            'post_type' => $original_post->post_type,
            'post_status' => 'draft', // Always create as draft
            'post_author' => get_current_user_id()
        ];

        $new_post_id = wp_insert_post($new_post_data);
        
        if (is_wp_error($new_post_id)) {
            return null;
        }

        // Copy all metadata
        $meta_data = get_post_meta($listing_id);
        foreach ($meta_data as $key => $values) {
            foreach ($values as $value) {
                add_post_meta($new_post_id, $key, maybe_unserialize($value));
            }
        }

        // Copy featured image
        $featured_image_id = get_post_thumbnail_id($listing_id);
        if ($featured_image_id) {
            set_post_thumbnail($new_post_id, $featured_image_id);
        }

        return $new_post_id;
    }

    /**
     * Perform bulk action on listings
     */
    private function perform_bulk_action(string $action, array $listing_ids): array
    {
        $processed = [];
        $failed = [];

        foreach ($listing_ids as $listing_id) {
            // Check permissions for each listing
            if (!current_user_can('edit_post', $listing_id)) {
                $failed[] = $listing_id;
                continue;
            }

            $success = match($action) {
                'delete' => wp_delete_post($listing_id, true) !== false,
                'trash' => wp_trash_post($listing_id) !== false,
                'publish' => wp_update_post(['ID' => $listing_id, 'post_status' => 'publish']) !== 0,
                'draft' => wp_update_post(['ID' => $listing_id, 'post_status' => 'draft']) !== 0,
                default => false
            };

            if ($success) {
                $processed[] = $listing_id;
            } else {
                $failed[] = $listing_id;
            }
        }

        return ['processed' => $processed, 'failed' => $failed];
    }

    /**
     * Get orderby field for WP_Query
     */
    private function get_orderby_field(string $sort_by): string
    {
        return match($sort_by) {
            'price' => 'meta_value_num',
            'bedrooms' => 'meta_value_num',
            'bathrooms' => 'meta_value_num', 
            'square_feet' => 'meta_value_num',
            'title' => 'title',
            default => 'date'
        };
    }

    /**
     * Build meta query from filters
     */
    private function build_meta_query(array $filters): array
    {
        $meta_query = [];

        foreach ($filters as $key => $value) {
            if (empty($value)) continue;

            $meta_query[] = [
                'key' => "_{$key}",
                'value' => $value,
                'compare' => is_array($value) ? 'IN' : '='
            ];
        }

        return $meta_query;
    }

    /**
     * Get status label for display
     */
    private function get_status_label(string $status): string
    {
        return match($status) {
            'publish' => 'Active',
            'draft' => 'Draft',
            'pending' => 'Pending',
            'sold' => 'Sold',
            'expired' => 'Expired',
            default => ucfirst($status)
        };
    }

    /**
     * Check if user can view specific listing
     */
    private function user_can_view_listing(int $listing_id): bool
    {
        return current_user_can('edit_post', $listing_id) || 
               current_user_can('read_post', $listing_id);
    }

    // Placeholder methods for features to be implemented
    private function get_listing_gallery(int $listing_id): array { return []; }
    private function get_listing_inquiry_count(int $listing_id): int { return 0; }
    
    public function filter_listings(): void { $this->send_error('Not implemented yet'); }
    public function delete_listing(): void { $this->send_error('Not implemented yet'); }
    public function upload_listing_image(): void { $this->send_error('Not implemented yet'); }
    public function delete_listing_image(): void { $this->send_error('Not implemented yet'); }
    public function reorder_listing_images(): void { $this->send_error('Not implemented yet'); }
    public function get_listing_analytics(): void { $this->send_error('Not implemented yet'); }
    public function track_listing_view(): void { $this->send_error('Not implemented yet'); }
    public function get_featured_listings(): void { $this->send_error('Not implemented yet'); }
    public function get_listing_details(): void { $this->send_error('Not implemented yet'); }
}
