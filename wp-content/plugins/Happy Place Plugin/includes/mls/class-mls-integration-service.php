<?php
/**
 * MLS Integration Service
 * Phase 4 Day 4-7: MLS Data Synchronization
 * 
 * Handles real-time MLS data synchronization, IDX compliance, and listing updates
 * 
 * @package HappyPlace
 * @subpackage MLS
 * @since 4.4.0
 */

namespace HappyPlace\MLS;

use WP_Post;
use Exception;

if (!defined('ABSPATH')) {
    exit;
}

class MLS_Integration_Service
{
    private static ?self $instance = null;
    private array $mls_config = [];
    private bool $sync_enabled = false;
    private string $last_sync_time = '';

    public static function get_instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->init_mls_config();
        $this->init_hooks();
        $this->setup_sync_schedule();
    }

    /**
     * Initialize MLS configuration
     */
    private function init_mls_config(): void
    {
        $this->sync_enabled = get_field('api_mls_integration_enabled', 'options') ?: false;
        
        if ($this->sync_enabled) {
            $this->mls_config = [
                'source' => get_field('api_mls_source', 'options') ?: 'local',
                'sync_frequency' => get_field('api_mls_sync_frequency', 'options') ?: 'hourly',
                'endpoint_url' => get_option('hph_mls_api_url', ''),
                'api_key' => get_option('hph_mls_api_key', ''),
                'username' => get_option('hph_mls_username', ''),
                'password' => get_option('hph_mls_password', ''),
                'client_id' => get_option('hph_mls_client_id', ''),
                'client_secret' => get_option('hph_mls_client_secret', '')
            ];
        }
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void
    {
        if (!$this->sync_enabled) return;
        
        // Sync hooks
        add_action('hph_mls_sync_listings', [$this, 'sync_listings_from_mls']);
        add_action('hph_mls_sync_agents', [$this, 'sync_agents_from_mls']);
        add_action('hph_mls_sync_offices', [$this, 'sync_offices_from_mls']);
        
        // AJAX handlers
        add_action('wp_ajax_hph_manual_mls_sync', [$this, 'manual_mls_sync']);
        add_action('wp_ajax_hph_mls_status', [$this, 'get_mls_status']);
        
        // Webhook handlers (for real-time updates)
        add_action('wp_ajax_nopriv_hph_mls_webhook', [$this, 'handle_mls_webhook']);
        add_action('wp_ajax_hph_mls_webhook', [$this, 'handle_mls_webhook']);
        
        // Post save hooks for MLS updates
        add_action('acf/save_post', [$this, 'maybe_sync_to_mls'], 30);
    }

    /**
     * Setup synchronization schedule
     */
    private function setup_sync_schedule(): void
    {
        if (!$this->sync_enabled) return;
        
        $frequency = $this->mls_config['sync_frequency'];
        
        // Clear existing schedules first
        wp_clear_scheduled_hook('hph_mls_sync_listings');
        wp_clear_scheduled_hook('hph_mls_sync_agents');
        
        // Schedule based on frequency setting
        switch ($frequency) {
            case 'realtime':
                // Real-time sync uses webhooks, no cron needed
                break;
                
            case '15min':
                if (!wp_next_scheduled('hph_mls_sync_listings')) {
                    wp_schedule_event(time(), 'hph_15min', 'hph_mls_sync_listings');
                }
                break;
                
            case 'hourly':
                if (!wp_next_scheduled('hph_mls_sync_listings')) {
                    wp_schedule_event(time(), 'hourly', 'hph_mls_sync_listings');
                }
                break;
                
            case 'daily':
                if (!wp_next_scheduled('hph_mls_sync_listings')) {
                    wp_schedule_event(time(), 'daily', 'hph_mls_sync_listings');
                }
                break;
        }
        
        // Schedule agent sync daily
        if (!wp_next_scheduled('hph_mls_sync_agents')) {
            wp_schedule_event(time(), 'daily', 'hph_mls_sync_agents');
        }
    }

    /**
     * Sync listings from MLS
     */
    public function sync_listings_from_mls(): void
    {
        if (!$this->sync_enabled) return;
        
        $start_time = microtime(true);
        error_log("🔄 Starting MLS listing sync");
        
        try {
            // Get the last sync timestamp
            $last_sync = get_option('hph_mls_last_listing_sync', '');
            $sync_params = [
                'modified_since' => $last_sync,
                'status' => ['Active', 'Pending', 'Under Contract'],
                'limit' => 100
            ];
            
            $listings = $this->fetch_mls_listings($sync_params);
            
            if (empty($listings)) {
                error_log("✅ MLS sync completed - no new listings");
                return;
            }
            
            $created = 0;
            $updated = 0;
            $errors = 0;
            
            foreach ($listings as $mls_listing) {
                try {
                    $result = $this->process_mls_listing($mls_listing);
                    
                    if ($result['action'] === 'created') {
                        $created++;
                    } elseif ($result['action'] === 'updated') {
                        $updated++;
                    }
                    
                } catch (Exception $e) {
                    $errors++;
                    error_log("❌ Error processing MLS listing {$mls_listing['ListingKey']}: " . $e->getMessage());
                }
            }
            
            // Update last sync time
            update_option('hph_mls_last_listing_sync', current_time('Y-m-d H:i:s'));
            
            $duration = round(microtime(true) - $start_time, 2);
            error_log("✅ MLS sync completed: {$created} created, {$updated} updated, {$errors} errors in {$duration}s");
            
            // Store sync statistics
            $this->store_sync_stats([
                'type' => 'listings',
                'created' => $created,
                'updated' => $updated,
                'errors' => $errors,
                'duration' => $duration,
                'timestamp' => current_time('mysql')
            ]);
            
        } catch (Exception $e) {
            error_log("❌ MLS sync failed: " . $e->getMessage());
        }
    }

    /**
     * Fetch listings from MLS API
     */
    private function fetch_mls_listings(array $params): array
    {
        $endpoint = $this->mls_config['endpoint_url'] . '/odata/Property';
        
        // Build OData filter
        $filters = [];
        
        if (!empty($params['modified_since'])) {
            $filters[] = "ModificationTimestamp gt {$params['modified_since']}";
        }
        
        if (!empty($params['status'])) {
            $status_filters = array_map(function($status) {
                return "StandardStatus eq '{$status}'";
            }, $params['status']);
            $filters[] = '(' . implode(' or ', $status_filters) . ')';
        }
        
        $query_params = [
            '$filter' => implode(' and ', $filters),
            '$orderby' => 'ModificationTimestamp asc',
            '$top' => $params['limit'] ?? 100
        ];
        
        $url = $endpoint . '?' . http_build_query($query_params);
        
        $response = $this->make_mls_request($url);
        
        if (!$response['success']) {
            throw new Exception("MLS API request failed: " . $response['error']);
        }
        
        return $response['data']['value'] ?? [];
    }

    /**
     * Process individual MLS listing
     */
    private function process_mls_listing(array $mls_listing): array
    {
        $mls_id = $mls_listing['ListingKey'];
        
        // Check if listing already exists
        $existing_post = $this->find_listing_by_mls_id($mls_id);
        
        if ($existing_post) {
            // Update existing listing
            $post_id = $this->update_listing_from_mls($existing_post->ID, $mls_listing);
            return ['action' => 'updated', 'post_id' => $post_id];
        } else {
            // Create new listing
            $post_id = $this->create_listing_from_mls($mls_listing);
            return ['action' => 'created', 'post_id' => $post_id];
        }
    }

    /**
     * Find listing by MLS ID
     */
    private function find_listing_by_mls_id(string $mls_id): ?WP_Post
    {
        $posts = get_posts([
            'post_type' => 'listing',
            'meta_query' => [
                [
                    'key' => 'mls_id',
                    'value' => $mls_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1,
            'post_status' => 'any'
        ]);
        
        return $posts[0] ?? null;
    }

    /**
     * Create new listing from MLS data
     */
    private function create_listing_from_mls(array $mls_listing): int
    {
        $post_data = [
            'post_type' => 'listing',
            'post_status' => $this->map_mls_status($mls_listing['StandardStatus']),
            'post_title' => $this->generate_listing_title($mls_listing),
            'post_content' => $mls_listing['PublicRemarks'] ?? '',
            'meta_input' => $this->map_mls_fields($mls_listing)
        ];
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            throw new Exception("Failed to create listing: " . $post_id->get_error_message());
        }
        
        // Download and attach images
        $this->process_mls_images($post_id, $mls_listing['ListingKey']);
        
        // Add MLS metadata
        update_post_meta($post_id, 'mls_id', $mls_listing['ListingKey']);
        update_post_meta($post_id, 'mls_source', $this->mls_config['source']);
        update_post_meta($post_id, 'mls_last_updated', current_time('mysql'));
        
        do_action('hph_listing_created_from_mls', $post_id, $mls_listing);
        
        return $post_id;
    }

    /**
     * Update existing listing from MLS data
     */
    private function update_listing_from_mls(int $post_id, array $mls_listing): int
    {
        $post_data = [
            'ID' => $post_id,
            'post_status' => $this->map_mls_status($mls_listing['StandardStatus']),
            'post_title' => $this->generate_listing_title($mls_listing),
            'post_content' => $mls_listing['PublicRemarks'] ?? '',
            'meta_input' => $this->map_mls_fields($mls_listing)
        ];
        
        $result = wp_update_post($post_data);
        
        if (is_wp_error($result)) {
            throw new Exception("Failed to update listing: " . $result->get_error_message());
        }
        
        // Update images if needed
        $this->process_mls_images($post_id, $mls_listing['ListingKey']);
        
        // Update MLS metadata
        update_post_meta($post_id, 'mls_last_updated', current_time('mysql'));
        
        do_action('hph_listing_updated_from_mls', $post_id, $mls_listing);
        
        return $post_id;
    }

    /**
     * Map MLS fields to WordPress fields
     */
    private function map_mls_fields(array $mls_listing): array
    {
        $field_mapping = [
            'price' => $mls_listing['ListPrice'] ?? 0,
            'street_address' => $mls_listing['UnparsedAddress'] ?? '',
            'city' => $mls_listing['City'] ?? '',
            'state' => $mls_listing['StateOrProvince'] ?? '',
            'zip_code' => $mls_listing['PostalCode'] ?? '',
            'county' => $mls_listing['CountyOrParish'] ?? '',
            'bedrooms' => $mls_listing['BedroomsTotal'] ?? 0,
            'bathrooms_full' => $mls_listing['BathroomsFull'] ?? 0,
            'bathrooms_half' => $mls_listing['BathroomsHalf'] ?? 0,
            'square_footage' => $mls_listing['LivingAreaSqFt'] ?? 0,
            'lot_size' => $mls_listing['LotSizeSquareFeet'] ?? 0,
            'year_built' => $mls_listing['YearBuilt'] ?? '',
            'property_type' => $mls_listing['PropertyType'] ?? '',
            'property_subtype' => $mls_listing['PropertySubType'] ?? '',
            'listing_date' => $mls_listing['ListingContractDate'] ?? '',
            'days_on_market' => $this->calculate_days_on_market($mls_listing['ListingContractDate'] ?? ''),
            'latitude' => $mls_listing['Latitude'] ?? 0,
            'longitude' => $mls_listing['Longitude'] ?? 0,
            'listing_agent_name' => $mls_listing['ListAgentFullName'] ?? '',
            'listing_office' => $mls_listing['ListOfficeName'] ?? '',
            'mls_number' => $mls_listing['ListingId'] ?? ''
        ];
        
        // Calculate derived fields
        if ($field_mapping['price'] && $field_mapping['square_footage']) {
            $field_mapping['price_per_sqft'] = round($field_mapping['price'] / $field_mapping['square_footage'], 2);
        }
        
        if ($field_mapping['bathrooms_full'] || $field_mapping['bathrooms_half']) {
            $field_mapping['bathrooms_total'] = $field_mapping['bathrooms_full'] + ($field_mapping['bathrooms_half'] * 0.5);
        }
        
        return $field_mapping;
    }

    /**
     * Map MLS status to WordPress post status
     */
    private function map_mls_status(string $mls_status): string
    {
        $status_mapping = [
            'Active' => 'publish',
            'Pending' => 'pending',
            'Under Contract' => 'pending',
            'Sold' => 'sold',
            'Expired' => 'expired',
            'Withdrawn' => 'draft',
            'Cancelled' => 'draft'
        ];
        
        return $status_mapping[$mls_status] ?? 'draft';
    }

    /**
     * Generate listing title from MLS data
     */
    private function generate_listing_title(array $mls_listing): string
    {
        $address = $mls_listing['UnparsedAddress'] ?? '';
        $city = $mls_listing['City'] ?? '';
        $state = $mls_listing['StateOrProvince'] ?? '';
        
        if ($address) {
            return trim("{$address}, {$city}, {$state}");
        }
        
        $price = number_format($mls_listing['ListPrice'] ?? 0);
        $bedrooms = $mls_listing['BedroomsTotal'] ?? 0;
        $bathrooms = $mls_listing['BathroomsTotalInteger'] ?? 0;
        
        return "{$bedrooms}BR/{$bathrooms}BA Home - {$price} in {$city}, {$state}";
    }

    /**
     * Calculate days on market
     */
    private function calculate_days_on_market(string $listing_date): int
    {
        if (empty($listing_date)) return 0;
        
        $list_date = strtotime($listing_date);
        $current_date = current_time('timestamp');
        
        return max(0, floor(($current_date - $list_date) / DAY_IN_SECONDS));
    }

    /**
     * Process MLS images
     */
    private function process_mls_images(int $post_id, string $mls_id): void
    {
        try {
            $images = $this->fetch_mls_images($mls_id);
            
            if (empty($images)) return;
            
            $uploaded_images = [];
            
            foreach ($images as $image_data) {
                $attachment_id = $this->download_and_attach_image(
                    $image_data['MediaURL'],
                    $post_id,
                    $image_data['ShortDescription'] ?? ''
                );
                
                if ($attachment_id) {
                    $uploaded_images[] = $attachment_id;
                    
                    // Set first image as featured image
                    if (count($uploaded_images) === 1) {
                        set_post_thumbnail($post_id, $attachment_id);
                    }
                }
            }
            
            // Store gallery images
            if (!empty($uploaded_images)) {
                update_post_meta($post_id, 'gallery_images', $uploaded_images);
            }
            
        } catch (Exception $e) {
            error_log("❌ Error processing images for listing {$mls_id}: " . $e->getMessage());
        }
    }

    /**
     * Fetch images from MLS
     */
    private function fetch_mls_images(string $mls_id): array
    {
        $endpoint = $this->mls_config['endpoint_url'] . '/odata/Media';
        $url = $endpoint . "?\$filter=ResourceRecordKey eq '{$mls_id}'&\$orderby=Order";
        
        $response = $this->make_mls_request($url);
        
        if (!$response['success']) {
            throw new Exception("Failed to fetch MLS images: " . $response['error']);
        }
        
        return $response['data']['value'] ?? [];
    }

    /**
     * Download and attach image
     */
    private function download_and_attach_image(string $image_url, int $post_id, string $description = ''): ?int
    {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Download image
        $temp_file = download_url($image_url);
        
        if (is_wp_error($temp_file)) {
            return null;
        }
        
        // Prepare file array
        $file_array = [
            'name' => basename($image_url),
            'tmp_name' => $temp_file
        ];
        
        // Import image to media library
        $attachment_id = media_handle_sideload($file_array, $post_id, $description);
        
        // Clean up temp file
        if (file_exists($temp_file)) {
            unlink($temp_file);
        }
        
        if (is_wp_error($attachment_id)) {
            return null;
        }
        
        return $attachment_id;
    }

    /**
     * Make authenticated MLS API request
     */
    private function make_mls_request(string $url, array $options = []): array
    {
        $headers = [
            'Accept' => 'application/json',
            'User-Agent' => 'WordPress-HPH/4.4.0'
        ];
        
        // Add authentication
        if (!empty($this->mls_config['api_key'])) {
            $headers['Authorization'] = 'Bearer ' . $this->mls_config['api_key'];
        } elseif (!empty($this->mls_config['username']) && !empty($this->mls_config['password'])) {
            $headers['Authorization'] = 'Basic ' . base64_encode(
                $this->mls_config['username'] . ':' . $this->mls_config['password']
            );
        }
        
        $args = [
            'headers' => $headers,
            'timeout' => $options['timeout'] ?? 60,
            'method' => $options['method'] ?? 'GET'
        ];
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message(),
                'data' => null
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code !== 200) {
            return [
                'success' => false,
                'error' => "HTTP {$status_code}: " . $body,
                'data' => null
            ];
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Invalid JSON response',
                'data' => null
            ];
        }
        
        return [
            'success' => true,
            'error' => null,
            'data' => $data
        ];
    }

    /**
     * Store sync statistics
     */
    private function store_sync_stats(array $stats): void
    {
        $existing_stats = get_option('hph_mls_sync_stats', []);
        $existing_stats[] = $stats;
        
        // Keep only last 30 sync records
        if (count($existing_stats) > 30) {
            $existing_stats = array_slice($existing_stats, -30);
        }
        
        update_option('hph_mls_sync_stats', $existing_stats);
    }

    /**
     * Handle MLS webhook for real-time updates
     */
    public function handle_mls_webhook(): void
    {
        // Verify webhook signature/token
        $webhook_token = $_SERVER['HTTP_X_MLS_TOKEN'] ?? '';
        $expected_token = get_option('hph_mls_webhook_token', '');
        
        if (empty($expected_token) || !hash_equals($expected_token, $webhook_token)) {
            wp_die('Unauthorized', 'Webhook Error', 401);
        }
        
        $input = file_get_contents('php://input');
        $webhook_data = json_decode($input, true);
        
        if (!$webhook_data) {
            wp_die('Invalid JSON', 'Webhook Error', 400);
        }
        
        try {
            // Process webhook data
            foreach ($webhook_data['listings'] ?? [] as $listing_data) {
                $this->process_mls_listing($listing_data);
            }
            
            wp_send_json_success('Webhook processed successfully');
            
        } catch (Exception $e) {
            error_log("❌ MLS webhook error: " . $e->getMessage());
            wp_die('Processing error: ' . $e->getMessage(), 'Webhook Error', 500);
        }
    }

    /**
     * Manual MLS sync (AJAX)
     */
    public function manual_mls_sync(): void
    {
        if (!current_user_can('administrator')) {
            wp_die('Access denied');
        }
        
        try {
            $this->sync_listings_from_mls();
            wp_send_json_success('MLS sync completed successfully');
        } catch (Exception $e) {
            wp_send_json_error('MLS sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Get MLS status (AJAX)
     */
    public function get_mls_status(): void
    {
        if (!current_user_can('administrator')) {
            wp_die('Access denied');
        }
        
        $stats = get_option('hph_mls_sync_stats', []);
        $last_sync = get_option('hph_mls_last_listing_sync', '');
        
        wp_send_json_success([
            'enabled' => $this->sync_enabled,
            'last_sync' => $last_sync,
            'sync_frequency' => $this->mls_config['sync_frequency'] ?? 'Not configured',
            'recent_stats' => array_slice($stats, -5), // Last 5 syncs
            'total_listings' => wp_count_posts('listing')->publish ?? 0
        ]);
    }

    /**
     * Sync agents from MLS
     */
    public function sync_agents_from_mls(): void
    {
        if (!$this->sync_enabled) return;
        
        error_log("🔄 Starting MLS agent sync");
        
        try {
            $agents = $this->fetch_mls_agents();
            
            $created = 0;
            $updated = 0;
            
            foreach ($agents as $mls_agent) {
                $result = $this->process_mls_agent($mls_agent);
                
                if ($result['action'] === 'created') {
                    $created++;
                } elseif ($result['action'] === 'updated') {
                    $updated++;
                }
            }
            
            error_log("✅ MLS agent sync completed: {$created} created, {$updated} updated");
            
        } catch (Exception $e) {
            error_log("❌ MLS agent sync failed: " . $e->getMessage());
        }
    }

    /**
     * Fetch agents from MLS
     */
    private function fetch_mls_agents(): array
    {
        $endpoint = $this->mls_config['endpoint_url'] . '/odata/Member';
        $url = $endpoint . '?$filter=MemberStatus eq \'Active\'';
        
        $response = $this->make_mls_request($url);
        
        if (!$response['success']) {
            throw new Exception("Failed to fetch MLS agents: " . $response['error']);
        }
        
        return $response['data']['value'] ?? [];
    }

    /**
     * Process MLS agent
     */
    private function process_mls_agent(array $mls_agent): array
    {
        // Implementation would go here for agent sync
        // Similar pattern to listing sync but for agent post type
        
        return ['action' => 'skipped']; // Placeholder
    }

    /**
     * Sync offices from MLS
     */
    public function sync_offices_from_mls(): void
    {
        // Similar implementation for office sync
        error_log("🔄 MLS office sync not yet implemented");
    }
}

// Initialize the MLS Integration Service
add_action('init', function() {
    MLS_Integration_Service::get_instance();
});

// Add custom cron intervals
add_filter('cron_schedules', function($schedules) {
    $schedules['hph_15min'] = [
        'interval' => 15 * MINUTE_IN_SECONDS,
        'display' => '15 Minutes'
    ];
    
    return $schedules;
});
