<?php
/**
 * Listing AJAX Handler - Comprehensive Listing Management
 *
 * Handles all listing-related AJAX operations including:
 * - Listing data retrieval and management
 * - Property search and filtering
 * - Listing CRUD operations
 * - Dynamic template content loading
 * - Listing status management
 *
 * @package HappyPlace
 * @subpackage Api\Ajax\Handlers
 * @since 2.0.0
 */

namespace HappyPlace\Api\Ajax\Handlers;

use HappyPlace\Api\Ajax\Base_Ajax_Handler;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Listing AJAX Handler Class
 *
 * Consolidates listing functionality including:
 * - Property data access via bridge functions
 * - Search and filtering operations
 * - Listing management tools
 * - Template dynamic content
 */
class Listing_Ajax extends Base_Ajax_Handler {

    /**
     * Listing configuration
     */
    private array $listing_config = [
        'cache_duration' => 600, // 10 minutes
        'search_cache_duration' => 300, // 5 minutes
        'max_search_results' => 100,
        'allowed_statuses' => ['publish', 'draft', 'pending', 'private'],
        'allowed_order_by' => ['date', 'title', 'price', 'modified', 'menu_order']
    ];

    /**
     * Define AJAX actions and their configurations
     */
    protected function get_actions(): array {
        return [
            // Core Listing Data
            'get_listing_data' => [
                'callback' => 'handle_get_listing_data',
                'capability' => 'read',
                'public' => true,
                'rate_limit' => 50,
                'cache' => 600
            ],
            'get_multiple_listings' => [
                'callback' => 'handle_get_multiple_listings',
                'capability' => 'read',
                'public' => true,
                'rate_limit' => 30,
                'cache' => 300
            ],
            
            // Search & Filtering
            'search_listings' => [
                'callback' => 'handle_search_listings',
                'capability' => 'read',
                'public' => true,
                'rate_limit' => 40,
                'cache' => 300
            ],
            'filter_listings' => [
                'callback' => 'handle_filter_listings',
                'capability' => 'read',
                'public' => true,
                'rate_limit' => 35
            ],
            'get_search_suggestions' => [
                'callback' => 'handle_get_search_suggestions',
                'capability' => 'read',
                'public' => true,
                'rate_limit' => 60,
                'cache' => 1800
            ],
            
            // Listing Management (Authenticated)
            'save_listing' => [
                'callback' => 'handle_save_listing',
                'capability' => 'edit_posts',
                'rate_limit' => 10
            ],
            'delete_listing' => [
                'callback' => 'handle_delete_listing',
                'capability' => 'delete_posts',
                'rate_limit' => 5
            ],
            'update_listing_status' => [
                'callback' => 'handle_update_status',
                'capability' => 'edit_posts',
                'rate_limit' => 15
            ],
            'duplicate_listing' => [
                'callback' => 'handle_duplicate_listing',
                'capability' => 'edit_posts',
                'rate_limit' => 5
            ],
            
            // Listing Features
            'toggle_listing_feature' => [
                'callback' => 'handle_toggle_feature',
                'capability' => 'edit_posts',
                'rate_limit' => 20
            ],
            'update_listing_gallery' => [
                'callback' => 'handle_update_gallery',
                'capability' => 'upload_files',
                'rate_limit' => 10
            ],
            'reorder_gallery_images' => [
                'callback' => 'handle_reorder_gallery',
                'capability' => 'edit_posts',
                'rate_limit' => 20
            ],
            
            // Template & Dynamic Content
            'load_listing_template_part' => [
                'callback' => 'handle_load_template_part',
                'capability' => 'read',
                'public' => true,
                'rate_limit' => 40,
                'cache' => 300
            ],
            'refresh_listing_cache' => [
                'callback' => 'handle_refresh_cache',
                'capability' => 'edit_posts',
                'rate_limit' => 10
            ],
            
            // Analytics & Stats
            'log_listing_view' => [
                'callback' => 'handle_log_view',
                'capability' => 'read',
                'public' => true,
                'rate_limit' => 100
            ],
            'get_listing_analytics' => [
                'callback' => 'handle_get_analytics',
                'capability' => 'edit_posts',
                'rate_limit' => 20,
                'cache' => 300
            ]
        ];
    }

    /**
     * Handle single listing data retrieval
     */
    public function handle_get_listing_data(): void {
        try {
            if (!$this->validate_required_params(['listing_id' => 'int'])) {
                return;
            }

            $listing_id = intval($_POST['listing_id']);
            $include_fields = $_POST['include_fields'] ?? 'all';
            $format = sanitize_text_field($_POST['format'] ?? 'detailed');

            // Check if listing exists and is accessible
            $listing = get_post($listing_id);
            if (!$listing || $listing->post_type !== 'listing') {
                $this->send_error('Listing not found');
                return;
            }

            // Check permissions for non-public listings
            if ($listing->post_status !== 'publish' && !current_user_can('edit_post', $listing_id)) {
                $this->send_error('Access denied');
                return;
            }

            // Use bridge functions to get comprehensive listing data
            $listing_data = $this->get_comprehensive_listing_data($listing_id, $include_fields, $format);

            $this->send_success([
                'listing_id' => $listing_id,
                'data' => $listing_data,
                'format' => $format,
                'timestamp' => current_time('timestamp')
            ]);

        } catch (\Exception $e) {
            error_log('HPH Listing Ajax Get Data Exception: ' . $e->getMessage());
            $this->send_error('Failed to retrieve listing data');
        }
    }

    /**
     * Handle multiple listings data retrieval
     */
    public function handle_get_multiple_listings(): void {
        try {
            if (!$this->validate_required_params(['listing_ids' => 'array'])) {
                return;
            }

            $listing_ids = array_map('intval', $_POST['listing_ids']);
            $include_fields = $_POST['include_fields'] ?? 'basic';
            $format = sanitize_text_field($_POST['format'] ?? 'summary');

            // Limit the number of listings that can be requested at once
            if (count($listing_ids) > 50) {
                $this->send_error('Too many listings requested (max 50)');
                return;
            }

            $listings_data = [];
            foreach ($listing_ids as $listing_id) {
                $listing = get_post($listing_id);
                if ($listing && $listing->post_type === 'listing' && 
                    ($listing->post_status === 'publish' || current_user_can('edit_post', $listing_id))) {
                    
                    $listings_data[$listing_id] = $this->get_comprehensive_listing_data($listing_id, $include_fields, $format);
                }
            }

            $this->send_success([
                'listings' => $listings_data,
                'count' => count($listings_data),
                'format' => $format
            ]);

        } catch (\Exception $e) {
            error_log('HPH Listing Ajax Get Multiple Exception: ' . $e->getMessage());
            $this->send_error('Failed to retrieve listings data');
        }
    }

    /**
     * Handle listing search
     */
    public function handle_search_listings(): void {
        try {
            $search_query = sanitize_text_field($_POST['query'] ?? '');
            $location = sanitize_text_field($_POST['location'] ?? '');
            $filters = $_POST['filters'] ?? [];
            $page = intval($_POST['page'] ?? 1);
            $per_page = intval($_POST['per_page'] ?? 12);
            $order_by = sanitize_text_field($_POST['order_by'] ?? 'date');
            $order = sanitize_text_field($_POST['order'] ?? 'DESC');

            // Validate parameters
            if (!in_array($order_by, $this->listing_config['allowed_order_by'])) {
                $order_by = 'date';
            }
            if (!in_array(strtoupper($order), ['ASC', 'DESC'])) {
                $order = 'DESC';
            }
            if ($per_page > $this->listing_config['max_search_results']) {
                $per_page = $this->listing_config['max_search_results'];
            }

            // Build search arguments
            $search_args = $this->build_search_args($search_query, $location, $filters, $page, $per_page, $order_by, $order);
            
            // Execute search
            $search_results = $this->execute_listing_search($search_args);

            $this->send_success([
                'results' => $search_results['listings'],
                'total' => $search_results['total'],
                'page' => $page,
                'per_page' => $per_page,
                'pages' => ceil($search_results['total'] / $per_page),
                'search_query' => $search_query,
                'filters_applied' => $filters
            ]);

        } catch (\Exception $e) {
            error_log('HPH Listing Ajax Search Exception: ' . $e->getMessage());
            $this->send_error('Search failed');
        }
    }

    /**
     * Handle listing filtering
     */
    public function handle_filter_listings(): void {
        try {
            $filters = $_POST['filters'] ?? [];
            $current_listings = $_POST['current_listings'] ?? [];
            $reset_filters = $_POST['reset_filters'] ?? false;

            if ($reset_filters) {
                $filters = [];
            }

            // Apply filters to current listing set or perform new search
            $filtered_results = $this->apply_listing_filters($filters, $current_listings);

            $this->send_success([
                'filtered_listings' => $filtered_results['listings'],
                'total' => $filtered_results['total'],
                'filters_applied' => $filters,
                'filter_counts' => $filtered_results['filter_counts']
            ]);

        } catch (\Exception $e) {
            error_log('HPH Listing Ajax Filter Exception: ' . $e->getMessage());
            $this->send_error('Filtering failed');
        }
    }

    /**
     * Handle search suggestions
     */
    public function handle_get_search_suggestions(): void {
        try {
            if (!$this->validate_required_params(['query' => 'string'])) {
                return;
            }

            $query = sanitize_text_field($_POST['query']);
            $suggestion_type = sanitize_text_field($_POST['type'] ?? 'all');

            if (strlen($query) < 2) {
                $this->send_success(['suggestions' => []]);
                return;
            }

            $suggestions = $this->get_search_suggestions($query, $suggestion_type);

            $this->send_success([
                'suggestions' => $suggestions,
                'query' => $query,
                'type' => $suggestion_type
            ]);

        } catch (\Exception $e) {
            error_log('HPH Listing Ajax Suggestions Exception: ' . $e->getMessage());
            $this->send_error('Failed to get suggestions');
        }
    }

    /**
     * Handle listing save
     */
    public function handle_save_listing(): void {
        try {
            if (!$this->validate_required_params(['listing_data' => 'array'])) {
                return;
            }

            $listing_data = $_POST['listing_data'];
            $listing_id = intval($_POST['listing_id'] ?? 0);
            $is_update = $listing_id > 0;

            // Validate user permissions
            if ($is_update && !current_user_can('edit_post', $listing_id)) {
                $this->send_error('Permission denied');
                return;
            } elseif (!$is_update && !current_user_can('publish_posts')) {
                $this->send_error('Permission denied');
                return;
            }

            // Sanitize and validate listing data
            $sanitized_data = $this->sanitize_listing_data($listing_data);
            
            // Save listing
            $result = $this->save_listing_data($listing_id, $sanitized_data);

            if ($result['success']) {
                $this->send_success([
                    'listing_id' => $result['listing_id'],
                    'message' => $is_update ? 'Listing updated successfully' : 'Listing created successfully',
                    'listing_data' => $this->get_comprehensive_listing_data($result['listing_id'], 'all', 'detailed')
                ]);
            } else {
                $this->send_error($result['message']);
            }

        } catch (\Exception $e) {
            error_log('HPH Listing Ajax Save Exception: ' . $e->getMessage());
            $this->send_error('Failed to save listing');
        }
    }

    /**
     * Handle listing deletion
     */
    public function handle_delete_listing(): void {
        try {
            if (!$this->validate_required_params(['listing_id' => 'int'])) {
                return;
            }

            $listing_id = intval($_POST['listing_id']);
            $permanent = $_POST['permanent'] ?? false;

            // Validate permissions
            if (!current_user_can('delete_post', $listing_id)) {
                $this->send_error('Permission denied');
                return;
            }

            // Check if listing exists
            $listing = get_post($listing_id);
            if (!$listing || $listing->post_type !== 'listing') {
                $this->send_error('Listing not found');
                return;
            }

            // Delete listing
            if ($permanent) {
                $result = wp_delete_post($listing_id, true);
            } else {
                $result = wp_trash_post($listing_id);
            }

            if ($result) {
                // Clear related caches
                $this->clear_listing_caches($listing_id);
                
                $this->send_success([
                    'listing_id' => $listing_id,
                    'message' => $permanent ? 'Listing permanently deleted' : 'Listing moved to trash',
                    'permanent' => $permanent
                ]);
            } else {
                $this->send_error('Failed to delete listing');
            }

        } catch (\Exception $e) {
            error_log('HPH Listing Ajax Delete Exception: ' . $e->getMessage());
            $this->send_error('Failed to delete listing');
        }
    }

    /**
     * Handle listing status update
     */
    public function handle_update_status(): void {
        try {
            if (!$this->validate_required_params([
                'listing_id' => 'int',
                'status' => 'string'
            ])) {
                return;
            }

            $listing_id = intval($_POST['listing_id']);
            $new_status = sanitize_text_field($_POST['status']);

            // Validate status
            if (!in_array($new_status, $this->listing_config['allowed_statuses'])) {
                $this->send_error('Invalid status');
                return;
            }

            // Validate permissions
            if (!current_user_can('edit_post', $listing_id)) {
                $this->send_error('Permission denied');
                return;
            }

            // Update status
            $result = wp_update_post([
                'ID' => $listing_id,
                'post_status' => $new_status
            ]);

            if (!is_wp_error($result)) {
                // Clear caches
                $this->clear_listing_caches($listing_id);
                
                $this->send_success([
                    'listing_id' => $listing_id,
                    'new_status' => $new_status,
                    'message' => 'Listing status updated successfully'
                ]);
            } else {
                $this->send_error('Failed to update status');
            }

        } catch (\Exception $e) {
            error_log('HPH Listing Ajax Status Exception: ' . $e->getMessage());
            $this->send_error('Failed to update status');
        }
    }

    /**
     * Handle listing duplication
     */
    public function handle_duplicate_listing(): void {
        try {
            if (!$this->validate_required_params(['listing_id' => 'int'])) {
                return;
            }

            $listing_id = intval($_POST['listing_id']);
            $new_title_suffix = sanitize_text_field($_POST['title_suffix'] ?? ' (Copy)');

            // Validate permissions
            if (!current_user_can('edit_post', $listing_id)) {
                $this->send_error('Permission denied');
                return;
            }

            // Get original listing
            $original_listing = get_post($listing_id);
            if (!$original_listing || $original_listing->post_type !== 'listing') {
                $this->send_error('Original listing not found');
                return;
            }

            // Duplicate listing
            $duplicate_id = $this->duplicate_listing($listing_id, $new_title_suffix);

            if ($duplicate_id) {
                $this->send_success([
                    'original_id' => $listing_id,
                    'duplicate_id' => $duplicate_id,
                    'message' => 'Listing duplicated successfully',
                    'duplicate_data' => $this->get_comprehensive_listing_data($duplicate_id, 'basic', 'summary')
                ]);
            } else {
                $this->send_error('Failed to duplicate listing');
            }

        } catch (\Exception $e) {
            error_log('HPH Listing Ajax Duplicate Exception: ' . $e->getMessage());
            $this->send_error('Failed to duplicate listing');
        }
    }

    /**
     * Handle template part loading
     */
    public function handle_load_template_part(): void {
        try {
            if (!$this->validate_required_params([
                'template_part' => 'string',
                'listing_id' => 'int'
            ])) {
                return;
            }

            $template_part = sanitize_text_field($_POST['template_part']);
            $listing_id = intval($_POST['listing_id']);
            $args = $_POST['args'] ?? [];

            // Validate listing access
            $listing = get_post($listing_id);
            if (!$listing || $listing->post_type !== 'listing') {
                $this->send_error('Listing not found');
                return;
            }

            if ($listing->post_status !== 'publish' && !current_user_can('edit_post', $listing_id)) {
                $this->send_error('Access denied');
                return;
            }

            // Load template part
            $template_content = $this->load_listing_template_part($template_part, $listing_id, $args);

            $this->send_success([
                'template_part' => $template_part,
                'listing_id' => $listing_id,
                'content' => $template_content,
                'args' => $args
            ]);

        } catch (\Exception $e) {
            error_log('HPH Listing Ajax Template Exception: ' . $e->getMessage());
            $this->send_error('Failed to load template part');
        }
    }

    /**
     * Handle cache refresh
     */
    public function handle_refresh_cache(): void {
        try {
            $listing_id = intval($_POST['listing_id'] ?? 0);
            $cache_types = $_POST['cache_types'] ?? ['all'];

            if ($listing_id > 0) {
                // Refresh specific listing cache
                $this->clear_listing_caches($listing_id);
                $message = 'Listing cache refreshed';
            } else {
                // Refresh all listing caches
                $this->clear_all_listing_caches();
                $message = 'All listing caches refreshed';
            }

            $this->send_success([
                'message' => $message,
                'listing_id' => $listing_id,
                'cache_types' => $cache_types
            ]);

        } catch (\Exception $e) {
            error_log('HPH Listing Ajax Cache Exception: ' . $e->getMessage());
            $this->send_error('Failed to refresh cache');
        }
    }

    /**
     * Handle view logging
     */
    public function handle_log_view(): void {
        try {
            if (!$this->validate_required_params(['listing_id' => 'int'])) {
                return;
            }

            $listing_id = intval($_POST['listing_id']);
            $user_agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '');
            $ip_address = $this->get_client_ip();

            // Log the view
            $logged = $this->log_listing_view($listing_id, $ip_address, $user_agent);

            $this->send_success([
                'listing_id' => $listing_id,
                'logged' => $logged,
                'view_count' => $this->get_listing_view_count($listing_id)
            ]);

        } catch (\Exception $e) {
            error_log('HPH Listing Ajax Log View Exception: ' . $e->getMessage());
            $this->send_error('Failed to log view');
        }
    }

    /**
     * Private helper methods
     */

    private function get_comprehensive_listing_data(int $listing_id, string $include_fields, string $format): array {
        // Use bridge functions for data access
        if (function_exists('hph_get_template_listing_data')) {
            $base_data = hph_get_template_listing_data($listing_id);
        } else {
            $base_data = $this->get_basic_listing_data($listing_id);
        }

        // Enhance with additional data based on format
        switch ($format) {
            case 'detailed':
                return $this->enhance_listing_data_detailed($base_data, $listing_id);
            case 'summary':
                return $this->enhance_listing_data_summary($base_data, $listing_id);
            case 'minimal':
                return $this->enhance_listing_data_minimal($base_data, $listing_id);
            default:
                return $base_data;
        }
    }

    private function get_basic_listing_data(int $listing_id): array {
        $listing = get_post($listing_id);
        if (!$listing) {
            return [];
        }

        return [
            'id' => $listing_id,
            'title' => $listing->post_title,
            'content' => $listing->post_content,
            'excerpt' => $listing->post_excerpt,
            'status' => $listing->post_status,
            'date' => $listing->post_date,
            'modified' => $listing->post_modified,
            'author' => $listing->post_author,
            'featured_image' => get_the_post_thumbnail_url($listing_id, 'large'),
            'permalink' => get_permalink($listing_id)
        ];
    }

    private function enhance_listing_data_detailed(array $base_data, int $listing_id): array {
        // Add comprehensive field data
        $enhanced = $base_data;
        
        // Use bridge functions if available
        if (function_exists('hph_bridge_get_price')) {
            $enhanced['price'] = hph_bridge_get_price($listing_id);
        }
        if (function_exists('hph_bridge_get_address')) {
            $enhanced['address'] = hph_bridge_get_address($listing_id);
        }
        if (function_exists('hph_bridge_get_bedrooms')) {
            $enhanced['bedrooms'] = hph_bridge_get_bedrooms($listing_id);
        }
        if (function_exists('hph_bridge_get_bathrooms')) {
            $enhanced['bathrooms'] = hph_bridge_get_bathrooms($listing_id);
        }

        // Add gallery
        $enhanced['gallery'] = $this->get_listing_gallery($listing_id);
        
        // Add analytics
        $enhanced['analytics'] = [
            'views' => $this->get_listing_view_count($listing_id),
            'favorites' => $this->get_listing_favorite_count($listing_id)
        ];

        return $enhanced;
    }

    private function enhance_listing_data_summary(array $base_data, int $listing_id): array {
        $enhanced = $base_data;
        
        // Add essential fields only
        if (function_exists('hph_bridge_get_price')) {
            $enhanced['price'] = hph_bridge_get_price($listing_id);
        }
        if (function_exists('hph_bridge_get_address')) {
            $enhanced['address'] = hph_bridge_get_address($listing_id);
        }

        return $enhanced;
    }

    private function enhance_listing_data_minimal(array $base_data, int $listing_id): array {
        // Return only essential fields
        return [
            'id' => $base_data['id'],
            'title' => $base_data['title'],
            'permalink' => $base_data['permalink'],
            'featured_image' => $base_data['featured_image']
        ];
    }

    private function build_search_args(string $query, string $location, array $filters, int $page, int $per_page, string $order_by, string $order): array {
        $args = [
            'post_type' => 'listing',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => $order_by,
            'order' => $order
        ];

        // Add search query
        if (!empty($query)) {
            $args['s'] = $query;
        }

        // Add meta query for filters
        $meta_query = [];
        
        if (!empty($filters['price_min'])) {
            $meta_query[] = [
                'key' => 'price',
                'value' => floatval($filters['price_min']),
                'compare' => '>='
            ];
        }

        if (!empty($filters['price_max'])) {
            $meta_query[] = [
                'key' => 'price',
                'value' => floatval($filters['price_max']),
                'compare' => '<='
            ];
        }

        if (!empty($filters['bedrooms'])) {
            $meta_query[] = [
                'key' => 'bedrooms',
                'value' => intval($filters['bedrooms']),
                'compare' => '>='
            ];
        }

        if (!empty($filters['bathrooms'])) {
            $meta_query[] = [
                'key' => 'bathrooms',
                'value' => floatval($filters['bathrooms']),
                'compare' => '>='
            ];
        }

        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }

        // Add taxonomy query for location
        if (!empty($location)) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'listing_location',
                    'field' => 'slug',
                    'terms' => sanitize_title($location)
                ]
            ];
        }

        return $args;
    }

    private function execute_listing_search(array $args): array {
        $query = new \WP_Query($args);
        
        $results = [
            'listings' => [],
            'total' => $query->found_posts
        ];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $listing_id = get_the_ID();
                
                $results['listings'][] = $this->get_comprehensive_listing_data($listing_id, 'basic', 'summary');
            }
            wp_reset_postdata();
        }

        return $results;
    }

    private function get_search_suggestions(string $query, string $type): array {
        $suggestions = [];

        switch ($type) {
            case 'locations':
                $suggestions = $this->get_location_suggestions($query);
                break;
            case 'properties':
                $suggestions = $this->get_property_suggestions($query);
                break;
            default:
                $suggestions = array_merge(
                    $this->get_location_suggestions($query),
                    $this->get_property_suggestions($query)
                );
        }

        return array_slice($suggestions, 0, 10);
    }

    private function get_location_suggestions(string $query): array {
        $terms = get_terms([
            'taxonomy' => 'listing_location',
            'name__like' => $query,
            'hide_empty' => true,
            'number' => 5
        ]);

        $suggestions = [];
        foreach ($terms as $term) {
            $suggestions[] = [
                'type' => 'location',
                'value' => $term->name,
                'slug' => $term->slug,
                'count' => $term->count
            ];
        }

        return $suggestions;
    }

    private function get_property_suggestions(string $query): array {
        $listings = get_posts([
            'post_type' => 'listing',
            'post_status' => 'publish',
            's' => $query,
            'posts_per_page' => 5,
            'fields' => 'ids'
        ]);

        $suggestions = [];
        foreach ($listings as $listing_id) {
            $suggestions[] = [
                'type' => 'property',
                'value' => get_the_title($listing_id),
                'id' => $listing_id,
                'url' => get_permalink($listing_id)
            ];
        }

        return $suggestions;
    }

    private function clear_listing_caches(int $listing_id): void {
        // Clear WordPress caches
        wp_cache_delete("template_listing_data_{$listing_id}", 'hph_listing_data');
        wp_cache_delete("listing_analytics_{$listing_id}", 'hph_listings');
        
        // Clear object cache groups
        wp_cache_flush_group('hph_listings');
        wp_cache_flush_group('hph_templates');
    }

    private function clear_all_listing_caches(): void {
        wp_cache_flush_group('hph_listings');
        wp_cache_flush_group('hph_templates');
        wp_cache_flush_group('hph_listing_data');
    }

    private function get_client_ip(): string {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}