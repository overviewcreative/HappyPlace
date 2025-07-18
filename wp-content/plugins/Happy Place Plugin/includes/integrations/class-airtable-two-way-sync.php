<?php
namespace HappyPlace\Integrations;

class Airtable_Two_Way_Sync {
    private string $base_url = 'https://api.airtable.com/v0/';
    private string $api_token;
    private string $base_id;
    private string $table_name;

    // Mapping between Airtable fields and WordPress meta keys
    private array $field_mapping = [
        'listing_id' => 'Record ID',
        'title' => 'Property Name',
        'address' => 'Street Address',
        'price' => 'List Price',
        'bedrooms' => 'Bedrooms',
        'bathrooms' => 'Bathrooms',
        'square_footage' => 'Square Footage',
        'property_type' => 'Property Type',
        'status' => 'Listing Status',
        'description' => 'Property Description',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude'
    ];

    public function __construct(string $base_id, string $table_name) {
        $options = get_option('happy_place_options', []);
        $this->api_token = $options['airtable_api_key'] ?? '';
        $this->base_id = $base_id;
        $this->table_name = $table_name;
    }

    /**
     * Sync Listings from Airtable to WordPress
     */
    public function sync_airtable_to_wordpress(): array {
        try {
            $airtable_records = $this->fetch_all_airtable_records();
            $processed_records = $this->process_airtable_records($airtable_records);

            return [
                'total_records' => count($airtable_records),
                'processed_records' => $processed_records
            ];
        } catch (\Exception $e) {
            error_log('Airtable to WordPress Sync Error: ' . $e->getMessage());
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Sync Listings from WordPress to Airtable
     */
    public function sync_wordpress_to_airtable(): array {
        try {
            $wordpress_listings = $this->get_wordpress_listings();
            $processed_records = $this->update_airtable_records($wordpress_listings);

            return [
                'total_records' => count($wordpress_listings),
                'processed_records' => $processed_records
            ];
        } catch (\Exception $e) {
            error_log('WordPress to Airtable Sync Error: ' . $e->getMessage());
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Fetch all records from Airtable
     */
    private function fetch_all_airtable_records(): array {
        $records = [];
        $offset = null;

        do {
            $params = ['pageSize' => 100];
            if ($offset) {
                $params['offset'] = $offset;
            }

            $response = $this->make_airtable_request("{$this->base_id}/{$this->table_name}", $params);
            $data = json_decode($response, true);

            if (!isset($data['records'])) {
                throw new \Exception('Invalid response from Airtable');
            }

            $records = array_merge($records, $data['records']);
            $offset = $data['offset'] ?? null;
        } while ($offset);

        return $records;
    }

    /**
     * Process Airtable records and create/update WordPress listings
     */
    private function process_airtable_records(array $records): array {
        $processed = [];

        foreach ($records as $record) {
            $fields = $record['fields'];
            
            // Prepare post data
            $post_data = [
                'post_type' => 'listing',
                'post_title' => $fields[$this->field_mapping['title']] ?? '',
                'post_status' => 'publish'
            ];

            // Insert or update post
            $post_id = $this->find_or_create_listing($post_data, $fields);

            // Update custom fields
            foreach ($this->field_mapping as $wp_key => $airtable_key) {
                if (isset($fields[$airtable_key])) {
                    update_field($wp_key, $fields[$airtable_key], $post_id);
                }
            }

            // Store Airtable record ID for future sync
            update_post_meta($post_id, '_airtable_record_id', $record['id']);

            $processed[] = $post_id;
        }

        return $processed;
    }

    /**
     * Find existing listing or create new
     */
    private function find_or_create_listing(array $post_data, array $fields): int {
        // Try to find by Airtable Record ID
        $existing_post_query = new \WP_Query([
            'post_type' => 'listing',
            'meta_key' => '_airtable_record_id',
            'meta_value' => $fields['Record ID'] ?? null,
            'posts_per_page' => 1
        ]);

        if ($existing_post_query->have_posts()) {
            $existing_post_query->the_post();
            $post_data['ID'] = get_the_ID();
            return wp_update_post($post_data);
        }

        // If no existing post found, create new
        return wp_insert_post($post_data);
    }

    /**
     * Get WordPress listings to sync to Airtable
     */
    private function get_wordpress_listings(): array {
        $listings_query = new \WP_Query([
            'post_type' => 'listing',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft']
        ]);

        $listings = [];
        while ($listings_query->have_posts()) {
            $listings_query->the_post();
            $listing_data = [];
            
            foreach ($this->field_mapping as $wp_key => $airtable_key) {
                $value = get_field($wp_key);
                if ($value) {
                    $listing_data[$airtable_key] = $value;
                }
            }

            // Get Airtable Record ID if exists
            $airtable_record_id = get_post_meta(get_the_ID(), '_airtable_record_id', true);

            $listings[] = [
                'post_id' => get_the_ID(),
                'airtable_record_id' => $airtable_record_id,
                'fields' => $listing_data
            ];
        }

        wp_reset_postdata();
        return $listings;
    }

    /**
     * Update records in Airtable
     */
    private function update_airtable_records(array $listings): array {
        $processed = [];

        foreach ($listings as $listing) {
            $record_id = $listing['airtable_record_id'];
            $fields = $listing['fields'];

            // If no Airtable record ID, create new record
            if (!$record_id) {
                $response = $this->make_airtable_request(
                    "{$this->base_id}/{$this->table_name}", 
                    ['fields' => $fields],
                    'POST'
                );
                $result = json_decode($response, true);
                
                // Store new Airtable Record ID
                update_post_meta($listing['post_id'], '_airtable_record_id', $result['id']);
            } else {
                // Update existing record
                $this->make_airtable_request(
                    "{$this->base_id}/{$this->table_name}/{$record_id}", 
                    ['fields' => $fields],
                    'PATCH'
                );
            }

            $processed[] = $listing['post_id'];
        }

        return $processed;
    }

    /**
     * Make Airtable API Request
     */
    private function make_airtable_request(
        string $endpoint, 
        array $params = [], 
        string $method = 'GET'
    ): string {
        $url = $this->base_url . $endpoint;
        
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_token,
                'Content-Type' => 'application/json'
            ]
        ];

        if ($method !== 'GET') {
            $args['body'] = json_encode($params);
        } elseif (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            throw new \Exception("Airtable API error: $code");
        }

        return wp_remote_retrieve_body($response);
    }
}

// Usage example in another file or action hook
function hph_trigger_airtable_sync() {
    $sync = new Airtable_Two_Way_Sync(
        'YOUR_BASE_ID', 
        'Listings'
    );

    // Sync from Airtable to WordPress
    $airtable_to_wp_result = $sync->sync_airtable_to_wordpress();

    // Sync from WordPress to Airtable
    $wp_to_airtable_result = $sync->sync_wordpress_to_airtable();

    // Log results or handle as needed
    error_log('Airtable Sync Results: ' . print_r([
        'Airtable to WP' => $airtable_to_wp_result,
        'WP to Airtable' => $wp_to_airtable_result
    ], true));
}
// add_action('init', 'hph_trigger_airtable_sync');