<?php
/**
 * Listing AJAX Handler - Listing Operations
 * 
 * File: includes/api/ajax/handlers/class-listing-ajax.php
 */

namespace HappyPlace\Api\Ajax\Handlers;

use HappyPlace\Api\Ajax\Base_Ajax_Handler;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Listing_Ajax extends Base_Ajax_Handler {
    
    protected function get_actions(): array {
        return [
            'get_listings' => [
                'callback' => 'handle_get_listings',
                'capability' => 'read',
                'rate_limit' => 20,
                'cache' => 300 // 5 minutes
            ],
            'search_listings' => [
                'callback' => 'handle_search_listings',
                'capability' => 'read',
                'rate_limit' => 30,
                'public' => true
            ],
            'get_listing_details' => [
                'callback' => 'handle_get_listing_details',
                'capability' => 'read',
                'rate_limit' => 50,
                'cache' => 600 // 10 minutes
            ],
            'update_listing_status' => [
                'callback' => 'handle_update_status',
                'capability' => 'edit_posts',
                'rate_limit' => 10
            ],
            'toggle_listing_featured' => [
                'callback' => 'handle_toggle_featured',
                'capability' => 'edit_posts',
                'rate_limit' => 15
            ],
            'bulk_update_listings' => [
                'callback' => 'handle_bulk_update',
                'capability' => 'edit_posts',
                'rate_limit' => 5
            ]
        ];
    }
    
    public function handle_get_listings(): void {
        try {
            $page = intval($_POST['page'] ?? 1);
            $per_page = intval($_POST['per_page'] ?? 10);
            $status = sanitize_text_field($_POST['status'] ?? 'publish');
            $agent_id = intval($_POST['agent_id'] ?? 0);

            $args = [
                'post_type' => 'listing',
                'post_status' => $status,
                'posts_per_page' => $per_page,
                'paged' => $page,
                'orderby' => 'date',
                'order' => 'DESC'
            ];

            if ($agent_id) {
                $args['author'] = $agent_id;
            }

            $query = new \WP_Query($args);
            $listings = [];

            foreach ($query->posts as $post) {
                $listings[] = $this->format_listing_data($post);
            }

            $this->send_success([
                'listings' => $listings,
                'total' => $query->found_posts,
                'pages' => $query->max_num_pages,
                'current_page' => $page
            ]);

        } catch (\Exception $e) {
            error_log('HPH Listing Ajax Exception: ' . $e->getMessage());
            $this->send_error('Error retrieving listings');
        }
    }

    public function handle_search_listings(): void {
        try {
            if (!$this->validate_required_params(['search_term' => 'string'])) {
                return;
            }

            $search_term = sanitize_text_field($_POST['search_term']);
            $filters = $_POST['filters'] ?? [];
            $page = intval($_POST['page'] ?? 1);
            $per_page = intval($_POST['per_page'] ?? 12);

            $args = [
                'post_type' => 'listing',
                'post_status' => 'publish',
                's' => $search_term,
                'posts_per_page' => $per_page,
                'paged' => $page
            ];

            // Apply filters
            if (!empty($filters['price_min'])) {
                $args['meta_query'][] = [
                    'key' => 'price',
                    'value' => intval($filters['price_min']),
                    'type' => 'NUMERIC',
                    'compare' => '>='
                ];
            }

            if (!empty($filters['price_max'])) {
                $args['meta_query'][] = [
                    'key' => 'price',
                    'value' => intval($filters['price_max']),
                    'type' => 'NUMERIC',
                    'compare' => '<='
                ];
            }

            if (!empty($filters['bedrooms'])) {
                $args['meta_query'][] = [
                    'key' => 'bedrooms',
                    'value' => intval($filters['bedrooms']),
                    'type' => 'NUMERIC',
                    'compare' => '>='
                ];
            }

            if (!empty($filters['property_type'])) {
                $args['meta_query'][] = [
                    'key' => 'property_type',
                    'value' => sanitize_text_field($filters['property_type']),
                    'compare' => '='
                ];
            }

            $query = new \WP_Query($args);
            $listings = [];

            foreach ($query->posts as $post) {
                $listings[] = $this->format_listing_data($post, true); // Include search relevance
            }

            $this->send_success([
                'listings' => $listings,
                'total' => $query->found_posts,
                'pages' => $query->max_num_pages,
                'current_page' => $page,
                'search_term' => $search_term
            ]);

        } catch (\Exception $e) {
            error_log('HPH Listing Search Exception: ' . $e->getMessage());
            $this->send_error('Error searching listings');
        }
    }

    public function handle_get_listing_details(): void {
        try {
            if (!$this->validate_required_params(['listing_id' => 'int'])) {
                return;
            }

            $listing_id = intval($_POST['listing_id']);
            $listing = get_post($listing_id);

            if (!$listing || $listing->post_type !== 'listing') {
                $this->send_error('Listing not found');
                return;
            }

            $data = $this->format_listing_data($listing, false, true); // Full details
            $this->send_success($data);

        } catch (\Exception $e) {
            error_log('HPH Listing Details Exception: ' . $e->getMessage());
            $this->send_error('Error retrieving listing details');
        }
    }
    
    public function handle_update_status(): void {
        try {
            if (!$this->validate_required_params(['listing_id' => 'int', 'status' => 'string'])) {
                return;
            }

            $listing_id = intval($_POST['listing_id']);
            $new_status = sanitize_text_field($_POST['status']);

            // Validate status
            $allowed_statuses = ['publish', 'pending', 'draft', 'sold', 'withdrawn'];
            if (!in_array($new_status, $allowed_statuses)) {
                $this->send_error('Invalid status');
                return;
            }

            $result = wp_update_post([
                'ID' => $listing_id,
                'post_status' => $new_status
            ]);

            if (is_wp_error($result)) {
                $this->send_error('Failed to update listing status');
                return;
            }

            // Log the status change
            $this->log_listing_activity($listing_id, 'status_changed', [
                'new_status' => $new_status,
                'user_id' => get_current_user_id()
            ]);

            $this->send_success([
                'listing_id' => $listing_id,
                'new_status' => $new_status,
                'message' => 'Status updated successfully'
            ]);

        } catch (\Exception $e) {
            error_log('HPH Listing Status Update Exception: ' . $e->getMessage());
            $this->send_error('Error updating listing status');
        }
    }

    public function handle_toggle_featured(): void {
        try {
            if (!$this->validate_required_params(['listing_id' => 'int'])) {
                return;
            }

            $listing_id = intval($_POST['listing_id']);
            $current_featured = get_post_meta($listing_id, 'featured', true);
            $new_featured = $current_featured ? false : true;

            update_post_meta($listing_id, 'featured', $new_featured);

            $this->send_success([
                'listing_id' => $listing_id,
                'featured' => $new_featured,
                'message' => $new_featured ? 'Listing featured' : 'Listing unfeatured'
            ]);

        } catch (\Exception $e) {
            error_log('HPH Toggle Featured Exception: ' . $e->getMessage());
            $this->send_error('Error toggling featured status');
        }
    }

    public function handle_bulk_update(): void {
        try {
            if (!$this->validate_required_params(['listing_ids' => 'array', 'action' => 'string'])) {
                return;
            }

            $listing_ids = array_map('intval', $_POST['listing_ids']);
            $action = sanitize_text_field($_POST['action']);
            $updated = 0;

            foreach ($listing_ids as $listing_id) {
                switch ($action) {
                    case 'publish':
                        wp_update_post(['ID' => $listing_id, 'post_status' => 'publish']);
                        $updated++;
                        break;
                    case 'draft':
                        wp_update_post(['ID' => $listing_id, 'post_status' => 'draft']);
                        $updated++;
                        break;
                    case 'delete':
                        wp_delete_post($listing_id, true);
                        $updated++;
                        break;
                    case 'feature':
                        update_post_meta($listing_id, 'featured', true);
                        $updated++;
                        break;
                    case 'unfeature':
                        update_post_meta($listing_id, 'featured', false);
                        $updated++;
                        break;
                }
            }

            $this->send_success([
                'updated' => $updated,
                'action' => $action,
                'message' => "Bulk action completed: {$updated} listings updated"
            ]);

        } catch (\Exception $e) {
            error_log('HPH Bulk Update Exception: ' . $e->getMessage());
            $this->send_error('Error performing bulk update');
        }
    }

    // Helper methods
    private function format_listing_data(\WP_Post $post, bool $search_context = false, bool $full_details = false): array {
        $data = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'status' => $post->post_status,
            'date' => $post->post_date,
            'author' => $post->post_author,
            'featured' => (bool) get_post_meta($post->ID, 'featured', true)
        ];

        // Basic fields
        $fields = ['price', 'bedrooms', 'bathrooms', 'square_footage', 'property_type', 'address', 'city'];
        foreach ($fields as $field) {
            $data[$field] = get_field($field, $post->ID);
        }

        if ($full_details) {
            // Add additional details for full view
            $data['description'] = $post->post_content;
            $data['gallery'] = get_field('gallery', $post->ID);
            $data['amenities'] = get_field('amenities', $post->ID);
            $data['permalink'] = get_permalink($post->ID);
            $data['edit_link'] = get_edit_post_link($post->ID);
        }

        return $data;
    }

    private function log_listing_activity(int $listing_id, string $action, array $data = []): void {
        // Log activity for audit trail - implement based on your logging system
        error_log("HPH Listing Activity: {$action} for listing {$listing_id} by user " . get_current_user_id());
    }
}
