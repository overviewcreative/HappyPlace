<?php
/**
 * Listings Section Handler
 * 
 * Handles backend logic for the dashboard listings section
 * 
 * @package HappyPlace
 * @subpackage Dashboard\Sections
 * @since 1.0.0
 */

namespace HappyPlace\Dashboard\Sections;

if (!defined('ABSPATH')) {
    exit;
}

class Listings_Section {
    
    /**
     * Section identifier
     */
    const SECTION_ID = 'listings';
    
    /**
     * Initialize the section
     */
    public function __construct() {
        add_action('wp_ajax_hph_get_listings', [$this, 'get_listings']);
        add_action('wp_ajax_hph_update_listing_status', [$this, 'update_listing_status']);
        add_action('wp_ajax_hph_duplicate_listing', [$this, 'duplicate_listing']);
        add_action('wp_ajax_hph_delete_listing', [$this, 'delete_listing']);
        add_action('wp_ajax_hph_bulk_listing_actions', [$this, 'bulk_listing_actions']);
        add_action('wp_ajax_hph_get_listing_stats', [$this, 'get_listing_stats']);
        add_action('wp_ajax_hph_update_listing_views', [$this, 'update_listing_views']);
    }
    
    /**
     * Get listings with filters and pagination
     */
    public function get_listings(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id || !current_user_can('agent')) {
            wp_send_json_error(['message' => __('Unauthorized access', 'happy-place')]);
        }
        
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 20);
        $status = sanitize_text_field($_POST['status'] ?? '');
        $type = sanitize_text_field($_POST['type'] ?? '');
        $search = sanitize_text_field($_POST['search'] ?? '');
        $sort = sanitize_text_field($_POST['sort'] ?? 'date_desc');
        
        $listings = $this->query_listings($user_id, [
            'page' => $page,
            'per_page' => $per_page,
            'status' => $status,
            'type' => $type,
            'search' => $search,
            'sort' => $sort
        ]);
        
        wp_send_json_success($listings);
    }
    
    /**
     * Query listings with filters
     */
    private function query_listings(int $user_id, array $args): array {
        $defaults = [
            'page' => 1,
            'per_page' => 20,
            'status' => '',
            'type' => '',
            'search' => '',
            'sort' => 'date_desc'
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Build query arguments
        $query_args = [
            'post_type' => 'listing',
            'author' => $user_id,
            'post_status' => ['publish', 'draft'],
            'posts_per_page' => $args['per_page'],
            'paged' => $args['page'],
            'meta_query' => [],
            'tax_query' => []
        ];
        
        // Add status filter
        if ($args['status']) {
            if ($args['status'] === 'draft') {
                $query_args['post_status'] = 'draft';
            } else {
                $query_args['post_status'] = 'publish';
                $query_args['meta_query'][] = [
                    'key' => 'listing_status',
                    'value' => $args['status'],
                    'compare' => '='
                ];
            }
        }
        
        // Add property type filter
        if ($args['type']) {
            $query_args['tax_query'][] = [
                'taxonomy' => 'property_type',
                'field' => 'slug',
                'terms' => $args['type']
            ];
        }
        
        // Add search
        if ($args['search']) {
            $query_args['s'] = $args['search'];
        }
        
        // Add sorting
        switch ($args['sort']) {
            case 'price_asc':
                $query_args['meta_key'] = 'listing_price';
                $query_args['orderby'] = 'meta_value_num';
                $query_args['order'] = 'ASC';
                break;
            case 'price_desc':
                $query_args['meta_key'] = 'listing_price';
                $query_args['orderby'] = 'meta_value_num';
                $query_args['order'] = 'DESC';
                break;
            case 'title_asc':
                $query_args['orderby'] = 'title';
                $query_args['order'] = 'ASC';
                break;
            case 'date_asc':
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'ASC';
                break;
            case 'date_desc':
            default:
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'DESC';
                break;
        }
        
        $query = new \WP_Query($query_args);
        
        $listings = [];
        foreach ($query->posts as $post) {
            $listings[] = $this->format_listing_data($post);
        }
        
        return [
            'listings' => $listings,
            'pagination' => [
                'total' => $query->found_posts,
                'pages' => $query->max_num_pages,
                'current_page' => $args['page'],
                'per_page' => $args['per_page']
            ]
        ];
    }
    
    /**
     * Format listing data for API response
     */
    private function format_listing_data(\WP_Post $post): array {
        $listing_fields = get_fields($post->ID);
        
        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'status' => $post->post_status,
            'listing_status' => $listing_fields['listing_status'] ?? 'active',
            'address' => $listing_fields['listing_address'] ?? '',
            'price' => $listing_fields['listing_price'] ?? 0,
            'bedrooms' => $listing_fields['listing_bedrooms'] ?? 0,
            'bathrooms' => $listing_fields['listing_bathrooms'] ?? 0,
            'sqft' => $listing_fields['listing_sqft'] ?? 0,
            'property_type' => $this->get_property_types($post->ID),
            'featured_image' => get_the_post_thumbnail_url($post->ID, 'medium'),
            'gallery' => $listing_fields['listing_gallery'] ?? [],
            'views' => (int) get_post_meta($post->ID, '_listing_views', true),
            'inquiries' => (int) get_post_meta($post->ID, '_listing_inquiries', true),
            'created' => $post->post_date,
            'modified' => $post->post_modified,
            'edit_link' => get_edit_post_link($post->ID),
            'view_link' => get_permalink($post->ID),
            'mls_number' => $listing_fields['listing_mls_number'] ?? '',
            'listing_date' => $listing_fields['listing_date'] ?? '',
            'days_on_market' => $this->calculate_days_on_market($post->ID)
        ];
    }
    
    /**
     * Get property types for listing
     */
    private function get_property_types(int $listing_id): array {
        $terms = get_the_terms($listing_id, 'property_type');
        if (!$terms || is_wp_error($terms)) {
            return [];
        }
        
        return array_map(function($term) {
            return [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug
            ];
        }, $terms);
    }
    
    /**
     * Calculate days on market
     */
    private function calculate_days_on_market(int $listing_id): int {
        $listing_date = get_field('listing_date', $listing_id);
        if (!$listing_date) {
            $listing_date = get_post_field('post_date', $listing_id);
        }
        
        if ($listing_date) {
            return ceil((current_time('timestamp') - strtotime($listing_date)) / DAY_IN_SECONDS);
        }
        
        return 0;
    }
    
    /**
     * Update listing status
     */
    public function update_listing_status(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $listing_id = intval($_POST['listing_id'] ?? 0);
        $new_status = sanitize_text_field($_POST['new_status'] ?? '');
        $note = sanitize_textarea_field($_POST['note'] ?? '');
        
        if (!$user_id || !current_user_can('agent')) {
            wp_send_json_error(['message' => __('Unauthorized access', 'happy-place')]);
        }
        
        if (!$listing_id || !$new_status) {
            wp_send_json_error(['message' => __('Invalid parameters', 'happy-place')]);
        }
        
        // Verify ownership
        $listing = get_post($listing_id);
        if (!$listing || $listing->post_author != $user_id) {
            wp_send_json_error(['message' => __('Listing not found or access denied', 'happy-place')]);
        }
        
        // Validate status
        $valid_statuses = ['active', 'pending', 'sold', 'withdrawn', 'expired'];
        if (!in_array($new_status, $valid_statuses)) {
            wp_send_json_error(['message' => __('Invalid status', 'happy-place')]);
        }
        
        // Update status
        $old_status = get_field('listing_status', $listing_id);
        update_field('listing_status', $new_status, $listing_id);
        
        // Add status change date if sold
        if ($new_status === 'sold') {
            update_field('listing_sold_date', current_time('Y-m-d'), $listing_id);
        }
        
        // Log status change
        $this->log_status_change($listing_id, $old_status, $new_status, $note);
        
        // Send notifications if needed
        $this->send_status_change_notifications($listing_id, $old_status, $new_status);
        
        wp_send_json_success([
            'message' => sprintf(__('Listing status updated to %s', 'happy-place'), ucfirst($new_status)),
            'new_status' => $new_status
        ]);
    }
    
    /**
     * Log status change
     */
    private function log_status_change(int $listing_id, string $old_status, string $new_status, string $note): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hph_listing_activity';
        
        $wpdb->insert(
            $table_name,
            [
                'listing_id' => $listing_id,
                'agent_id' => get_current_user_id(),
                'activity_type' => 'status_change',
                'old_value' => $old_status,
                'new_value' => $new_status,
                'notes' => $note,
                'created_date' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%s']
        );
    }
    
    /**
     * Send status change notifications
     */
    private function send_status_change_notifications(int $listing_id, string $old_status, string $new_status): void {
        // Get interested parties (leads, collaborators, etc.)
        $collaborators = get_field('listing_collaborators', $listing_id);
        
        if ($collaborators && is_array($collaborators)) {
            foreach ($collaborators as $collaborator_id) {
                $collaborator = get_user_by('ID', $collaborator_id);
                if ($collaborator) {
                    // Send email notification
                    $this->send_status_notification_email($collaborator->user_email, $listing_id, $new_status);
                }
            }
        }
    }
    
    /**
     * Send status notification email
     */
    private function send_status_notification_email(string $email, int $listing_id, string $status): void {
        $listing = get_post($listing_id);
        $subject = sprintf(__('Listing Status Update: %s', 'happy-place'), $listing->post_title);
        
        $message = sprintf(
            __('The listing "%s" has been updated to %s status.', 'happy-place'),
            $listing->post_title,
            ucfirst($status)
        );
        
        wp_mail($email, $subject, $message);
    }
    
    /**
     * Duplicate listing
     */
    public function duplicate_listing(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $listing_id = intval($_POST['listing_id'] ?? 0);
        
        if (!$user_id || !current_user_can('agent')) {
            wp_send_json_error(['message' => __('Unauthorized access', 'happy-place')]);
        }
        
        if (!$listing_id) {
            wp_send_json_error(['message' => __('Invalid listing ID', 'happy-place')]);
        }
        
        // Verify ownership
        $listing = get_post($listing_id);
        if (!$listing || $listing->post_author != $user_id) {
            wp_send_json_error(['message' => __('Listing not found or access denied', 'happy-place')]);
        }
        
        // Create duplicate
        $new_listing_data = [
            'post_title' => $listing->post_title . ' (Copy)',
            'post_content' => $listing->post_content,
            'post_status' => 'draft',
            'post_type' => 'listing',
            'post_author' => $user_id
        ];
        
        $new_listing_id = wp_insert_post($new_listing_data);
        
        if (is_wp_error($new_listing_id)) {
            wp_send_json_error(['message' => __('Failed to duplicate listing', 'happy-place')]);
        }
        
        // Copy custom fields
        $this->copy_listing_fields($listing_id, $new_listing_id);
        
        // Copy taxonomies
        $this->copy_listing_taxonomies($listing_id, $new_listing_id);
        
        wp_send_json_success([
            'message' => __('Listing duplicated successfully', 'happy-place'),
            'new_listing_id' => $new_listing_id,
            'edit_link' => get_edit_post_link($new_listing_id)
        ]);
    }
    
    /**
     * Copy listing custom fields
     */
    private function copy_listing_fields(int $source_id, int $target_id): void {
        $fields = get_fields($source_id);
        
        if ($fields) {
            foreach ($fields as $key => $value) {
                // Skip certain fields that should be unique
                if (in_array($key, ['listing_mls_number', 'listing_sold_date'])) {
                    continue;
                }
                
                update_field($key, $value, $target_id);
            }
        }
        
        // Reset status to draft
        update_field('listing_status', 'draft', $target_id);
        update_field('listing_date', current_time('Y-m-d'), $target_id);
    }
    
    /**
     * Copy listing taxonomies
     */
    private function copy_listing_taxonomies(int $source_id, int $target_id): void {
        $taxonomies = get_object_taxonomies('listing');
        
        foreach ($taxonomies as $taxonomy) {
            $terms = get_the_terms($source_id, $taxonomy);
            if ($terms && !is_wp_error($terms)) {
                $term_ids = array_map(function($term) {
                    return $term->term_id;
                }, $terms);
                
                wp_set_object_terms($target_id, $term_ids, $taxonomy);
            }
        }
    }
    
    /**
     * Delete listing
     */
    public function delete_listing(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $listing_id = intval($_POST['listing_id'] ?? 0);
        $force_delete = boolval($_POST['force_delete'] ?? false);
        
        if (!$user_id || !current_user_can('agent')) {
            wp_send_json_error(['message' => __('Unauthorized access', 'happy-place')]);
        }
        
        if (!$listing_id) {
            wp_send_json_error(['message' => __('Invalid listing ID', 'happy-place')]);
        }
        
        // Verify ownership
        $listing = get_post($listing_id);
        if (!$listing || $listing->post_author != $user_id) {
            wp_send_json_error(['message' => __('Listing not found or access denied', 'happy-place')]);
        }
        
        // Check if listing has dependencies (open houses, leads, etc.)
        if (!$force_delete && $this->has_listing_dependencies($listing_id)) {
            wp_send_json_error([
                'message' => __('Listing has open houses or leads associated. Use force delete to proceed.', 'happy-place'),
                'requires_force' => true
            ]);
        }
        
        // Delete listing
        $result = wp_delete_post($listing_id, $force_delete);
        
        if (!$result) {
            wp_send_json_error(['message' => __('Failed to delete listing', 'happy-place')]);
        }
        
        // Clean up dependencies if force delete
        if ($force_delete) {
            $this->cleanup_listing_dependencies($listing_id);
        }
        
        wp_send_json_success([
            'message' => __('Listing deleted successfully', 'happy-place')
        ]);
    }
    
    /**
     * Check if listing has dependencies
     */
    private function has_listing_dependencies(int $listing_id): bool {
        global $wpdb;
        
        // Check open houses
        $open_houses_table = $wpdb->prefix . 'hph_open_houses';
        $open_houses_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$open_houses_table} WHERE listing_id = %d",
            $listing_id
        ));
        
        if ($open_houses_count > 0) {
            return true;
        }
        
        // Check leads
        $leads_table = $wpdb->prefix . 'hph_leads';
        $leads_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$leads_table} WHERE listing_id = %d",
            $listing_id
        ));
        
        if ($leads_count > 0) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Clean up listing dependencies
     */
    private function cleanup_listing_dependencies(int $listing_id): void {
        global $wpdb;
        
        // Delete open houses
        $open_houses_table = $wpdb->prefix . 'hph_open_houses';
        $wpdb->delete($open_houses_table, ['listing_id' => $listing_id], ['%d']);
        
        // Update leads to remove listing association
        $leads_table = $wpdb->prefix . 'hph_leads';
        $wpdb->update(
            $leads_table,
            ['listing_id' => 0],
            ['listing_id' => $listing_id],
            ['%d'],
            ['%d']
        );
        
        // Delete activity logs
        $activity_table = $wpdb->prefix . 'hph_listing_activity';
        $wpdb->delete($activity_table, ['listing_id' => $listing_id], ['%d']);
    }
    
    /**
     * Handle bulk listing actions
     */
    public function bulk_listing_actions(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $action = sanitize_text_field($_POST['action'] ?? '');
        $listing_ids = array_map('intval', $_POST['listing_ids'] ?? []);
        
        if (!$user_id || !current_user_can('agent')) {
            wp_send_json_error(['message' => __('Unauthorized access', 'happy-place')]);
        }
        
        if (!$action || empty($listing_ids)) {
            wp_send_json_error(['message' => __('Invalid parameters', 'happy-place')]);
        }
        
        $results = [];
        $success_count = 0;
        $error_count = 0;
        
        foreach ($listing_ids as $listing_id) {
            // Verify ownership
            $listing = get_post($listing_id);
            if (!$listing || $listing->post_author != $user_id) {
                $results[] = [
                    'id' => $listing_id,
                    'success' => false,
                    'message' => __('Access denied', 'happy-place')
                ];
                $error_count++;
                continue;
            }
            
            $result = $this->execute_bulk_action($action, $listing_id);
            $results[] = $result;
            
            if ($result['success']) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
        
        wp_send_json_success([
            'message' => sprintf(
                __('%d listings processed successfully, %d failed', 'happy-place'),
                $success_count,
                $error_count
            ),
            'results' => $results,
            'success_count' => $success_count,
            'error_count' => $error_count
        ]);
    }
    
    /**
     * Execute bulk action on single listing
     */
    private function execute_bulk_action(string $action, int $listing_id): array {
        switch ($action) {
            case 'activate':
                update_field('listing_status', 'active', $listing_id);
                return [
                    'id' => $listing_id,
                    'success' => true,
                    'message' => __('Activated', 'happy-place')
                ];
                
            case 'deactivate':
                update_field('listing_status', 'inactive', $listing_id);
                return [
                    'id' => $listing_id,
                    'success' => true,
                    'message' => __('Deactivated', 'happy-place')
                ];
                
            case 'mark_pending':
                update_field('listing_status', 'pending', $listing_id);
                return [
                    'id' => $listing_id,
                    'success' => true,
                    'message' => __('Marked as pending', 'happy-place')
                ];
                
            case 'delete':
                $result = wp_delete_post($listing_id, false);
                return [
                    'id' => $listing_id,
                    'success' => (bool) $result,
                    'message' => $result ? __('Deleted', 'happy-place') : __('Delete failed', 'happy-place')
                ];
                
            default:
                return [
                    'id' => $listing_id,
                    'success' => false,
                    'message' => __('Unknown action', 'happy-place')
                ];
        }
    }
    
    /**
     * Get listing statistics
     */
    public function get_listing_stats(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id || !current_user_can('agent')) {
            wp_send_json_error(['message' => __('Unauthorized access', 'happy-place')]);
        }
        
        $stats = $this->calculate_listing_statistics($user_id);
        
        wp_send_json_success($stats);
    }
    
    /**
     * Calculate comprehensive listing statistics
     */
    private function calculate_listing_statistics(int $user_id): array {
        $cache_key = "hph_comprehensive_listing_stats_{$user_id}";
        $cached = wp_cache_get($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Get all listings
        $all_listings = get_posts([
            'post_type' => 'listing',
            'author' => $user_id,
            'post_status' => ['publish', 'draft'],
            'posts_per_page' => -1
        ]);
        
        $stats = [
            'total' => count($all_listings),
            'by_status' => [
                'active' => 0,
                'pending' => 0,
                'sold' => 0,
                'withdrawn' => 0,
                'expired' => 0,
                'draft' => 0
            ],
            'by_type' => [],
            'price_ranges' => [
                'under_250k' => 0,
                '250k_500k' => 0,
                '500k_750k' => 0,
                '750k_1m' => 0,
                'over_1m' => 0
            ],
            'performance' => [
                'total_value' => 0,
                'avg_price' => 0,
                'avg_days_on_market' => 0,
                'total_views' => 0,
                'total_inquiries' => 0,
                'view_to_inquiry_rate' => 0
            ]
        ];
        
        $total_views = 0;
        $total_inquiries = 0;
        $total_value = 0;
        $total_dom = 0;
        $dom_count = 0;
        
        foreach ($all_listings as $listing) {
            // Count by status
            if ($listing->post_status === 'draft') {
                $stats['by_status']['draft']++;
            } else {
                $status = get_field('listing_status', $listing->ID) ?: 'active';
                $stats['by_status'][$status]++;
            }
            
            // Count by property type
            $property_types = get_the_terms($listing->ID, 'property_type');
            if ($property_types && !is_wp_error($property_types)) {
                foreach ($property_types as $type) {
                    if (!isset($stats['by_type'][$type->name])) {
                        $stats['by_type'][$type->name] = 0;
                    }
                    $stats['by_type'][$type->name]++;
                }
            }
            
            // Price ranges
            $price = get_field('listing_price', $listing->ID);
            if ($price) {
                $total_value += $price;
                
                if ($price < 250000) {
                    $stats['price_ranges']['under_250k']++;
                } elseif ($price < 500000) {
                    $stats['price_ranges']['250k_500k']++;
                } elseif ($price < 750000) {
                    $stats['price_ranges']['500k_750k']++;
                } elseif ($price < 1000000) {
                    $stats['price_ranges']['750k_1m']++;
                } else {
                    $stats['price_ranges']['over_1m']++;
                }
            }
            
            // Performance metrics
            $views = (int) get_post_meta($listing->ID, '_listing_views', true);
            $inquiries = (int) get_post_meta($listing->ID, '_listing_inquiries', true);
            
            $total_views += $views;
            $total_inquiries += $inquiries;
            
            // Days on market
            $dom = $this->calculate_days_on_market($listing->ID);
            if ($dom > 0) {
                $total_dom += $dom;
                $dom_count++;
            }
        }
        
        // Calculate performance metrics
        $stats['performance']['total_value'] = $total_value;
        $stats['performance']['avg_price'] = $stats['total'] > 0 ? round($total_value / $stats['total']) : 0;
        $stats['performance']['avg_days_on_market'] = $dom_count > 0 ? round($total_dom / $dom_count) : 0;
        $stats['performance']['total_views'] = $total_views;
        $stats['performance']['total_inquiries'] = $total_inquiries;
        $stats['performance']['view_to_inquiry_rate'] = $total_views > 0 ? 
            round(($total_inquiries / $total_views) * 100, 1) : 0;
        
        wp_cache_set($cache_key, $stats, '', HOUR_IN_SECONDS);
        
        return $stats;
    }
    
    /**
     * Update listing views count
     */
    public function update_listing_views(): void {
        $listing_id = intval($_POST['listing_id'] ?? 0);
        
        if (!$listing_id) {
            wp_send_json_error(['message' => __('Invalid listing ID', 'happy-place')]);
        }
        
        $current_views = (int) get_post_meta($listing_id, '_listing_views', true);
        update_post_meta($listing_id, '_listing_views', $current_views + 1);
        
        // Log the view
        $this->log_listing_view($listing_id);
        
        wp_send_json_success([
            'views' => $current_views + 1
        ]);
    }
    
    /**
     * Log listing view for analytics
     */
    private function log_listing_view(int $listing_id): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hph_listing_views';
        
        $wpdb->insert(
            $table_name,
            [
                'listing_id' => $listing_id,
                'user_id' => get_current_user_id() ?: 0,
                'ip_address' => $this->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
                'view_date' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s']
        );
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip(): string {
        $ip_fields = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_fields as $field) {
            if (!empty($_SERVER[$field])) {
                return sanitize_text_field($_SERVER[$field]);
            }
        }
        
        return '';
    }
}