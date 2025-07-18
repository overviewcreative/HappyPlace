<?php

namespace HappyPlace\Integration;

/**
 * Enhanced Airtable Integration
 *
 * Real-time synchronization with Airtable including field mapping,
 * validation, and webhook support for instant updates.
 *
 * @package HappyPlace\Integration
 * @since 2.0.0
 */
class Airtable_Integration extends Base_Integration {
    
    /**
     * Airtable API client
     * @var Airtable_API_Client
     */
    protected $airtable_client;
    
    /**
     * Field mapping configuration
     * @var array
     */
    protected $field_mappings = [];
    
    /**
     * Get integration type
     * 
     * @return string
     */
    protected function get_integration_type() {
        return 'airtable';
    }
    
    /**
     * Get default configuration
     * 
     * @return array
     */
    protected function get_defaults() {
        return [
            'api_key' => get_option('hph_airtable_api_key', ''),
            'base_id' => get_option('hph_airtable_base_id', ''),
            'tables' => [
                'listings' => 'Listings',
                'agents' => 'Agents',
                'transactions' => 'Transactions',
                'communities' => 'Communities'
            ],
            'sync_interval' => 300, // 5 minutes
            'batch_size' => 100,
            'field_mappings' => [
                'listings' => [
                    // Airtable field => WordPress meta key
                    'Property ID' => 'listing_id',
                    'Address' => 'listing_address',
                    'Price' => 'listing_price',
                    'Bedrooms' => 'bedrooms',
                    'Bathrooms' => 'bathrooms',
                    'Square Footage' => 'square_footage',
                    'Agent' => 'listing_agent',
                    'Status' => 'listing_status',
                    'Images' => 'listing_images',
                    'Description' => 'listing_description',
                    'MLS Number' => 'mls_number',
                    'Year Built' => 'year_built',
                    'Lot Size' => 'lot_size',
                    'Property Type' => 'property_type',
                    'Features' => 'property_features'
                ],
                'agents' => [
                    'Agent ID' => 'agent_id',
                    'First Name' => 'first_name',
                    'Last Name' => 'last_name',
                    'Email' => 'agent_email',
                    'Phone' => 'agent_phone',
                    'License Number' => 'license_number',
                    'Bio' => 'agent_bio',
                    'Photo' => 'agent_photo',
                    'Specialties' => 'agent_specialties'
                ]
            ],
            'webhook_endpoint' => '/airtable-webhook',
            'auto_sync_enabled' => true,
            'conflict_resolution' => 'airtable_wins', // 'airtable_wins', 'wordpress_wins', 'manual'
            'sync_direction' => 'bidirectional' // 'incoming', 'outgoing', 'bidirectional'
        ];
    }
    
    /**
     * Initialize Airtable API client
     */
    protected function init_api_client() {
        if (empty($this->config['api_key']) || empty($this->config['base_id'])) {
            throw new Integration_Exception('Airtable API key and Base ID are required');
        }
        
        $this->airtable_client = new Airtable_API_Client([
            'api_key' => $this->config['api_key'],
            'base_id' => $this->config['base_id']
        ]);
        
        $this->field_mappings = $this->config['field_mappings'];
    }
    
    /**
     * Get rate limits for Airtable API
     * 
     * @return array
     */
    protected function get_rate_limits() {
        return [
            'requests_per_second' => 5,
            'requests_per_hour' => 1000
        ];
    }
    
    /**
     * Get webhook configuration
     * 
     * @return array
     */
    protected function get_webhook_config() {
        return [
            'endpoint' => $this->config['webhook_endpoint'],
            'secret' => get_option('hph_airtable_webhook_secret', ''),
            'events' => ['record_created', 'record_updated', 'record_deleted']
        ];
    }
    
    /**
     * Transform incoming Airtable data to WordPress format
     * 
     * @param array $data Airtable record data
     * @return array WordPress-formatted data
     */
    protected function transform_incoming_data($data) {
        $table_type = $this->determine_table_type($data);
        
        if (!isset($this->field_mappings[$table_type])) {
            throw new Integration_Exception("No field mapping found for table type: {$table_type}");
        }
        
        $mapping = $this->field_mappings[$table_type];
        $wp_data = [
            'airtable_id' => $data['id'],
            'table_type' => $table_type,
            'last_sync' => current_time('mysql')
        ];
        
        foreach ($mapping as $airtable_field => $wp_field) {
            if (isset($data['fields'][$airtable_field])) {
                $value = $data['fields'][$airtable_field];
                $wp_data[$wp_field] = $this->transform_field_value($value, $wp_field, $table_type);
            }
        }
        
        // Validate transformed data
        $this->validate_transformed_data($wp_data, $table_type);
        
        return $wp_data;
    }
    
    /**
     * Transform outgoing WordPress data to Airtable format
     * 
     * @param array $data WordPress post/meta data
     * @return array Airtable-formatted data
     */
    protected function transform_outgoing_data($data) {
        $table_type = $this->determine_wp_post_type($data);
        
        if (!isset($this->field_mappings[$table_type])) {
            throw new Integration_Exception("No field mapping found for post type: {$table_type}");
        }
        
        $mapping = array_flip($this->field_mappings[$table_type]); // Reverse mapping
        $airtable_data = [];
        
        foreach ($mapping as $wp_field => $airtable_field) {
            if (isset($data[$wp_field])) {
                $value = $data[$wp_field];
                $airtable_data[$airtable_field] = $this->transform_wp_field_value($value, $airtable_field, $table_type);
            }
        }
        
        return [
            'fields' => $airtable_data
        ];
    }
    
    /**
     * Real-time sync for specific listings
     * 
     * @param array $listing_ids Optional specific listing IDs
     * @return array Sync results
     */
    public function sync_listings_realtime($listing_ids = []) {
        try {
            $this->rate_limiter->check_limits('listings_sync');
            
            $listings = empty($listing_ids) 
                ? $this->get_all_airtable_listings() 
                : $this->get_airtable_listings_by_ids($listing_ids);
            
            $results = [
                'processed' => 0,
                'created' => 0,
                'updated' => 0,
                'errors' => []
            ];
            
            foreach ($listings as $listing) {
                try {
                    $wp_listing = $this->transform_incoming_data($listing);
                    $result = $this->upsert_wp_listing($wp_listing);
                    
                    if ($result['created']) {
                        $results['created']++;
                    } else {
                        $results['updated']++;
                    }
                    
                    $results['processed']++;
                    
                    // Invalidate component cache
                    $this->invalidate_listing_cache($result['id']);
                    
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'airtable_id' => $listing['id'],
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            return $results;
            
        } catch (\Exception $e) {
            $this->log_error('Real-time listing sync failed', $e);
            throw $e;
        }
    }
    
    /**
     * Transform field value based on type and context
     * 
     * @param mixed $value Field value
     * @param string $wp_field WordPress field name
     * @param string $table_type Table type
     * @return mixed Transformed value
     */
    protected function transform_field_value($value, $wp_field, $table_type) {
        switch ($wp_field) {
            case 'listing_price':
                return $this->transform_price_field($value);
                
            case 'listing_images':
                return $this->transform_images_field($value);
                
            case 'listing_address':
                return $this->transform_address_field($value);
                
            case 'property_features':
                return $this->transform_array_field($value);
                
            case 'agent_specialties':
                return $this->transform_array_field($value);
                
            case 'agent_email':
                return sanitize_email($value);
                
            case 'agent_phone':
                return $this->format_phone_number($value);
                
            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * Transform price field
     * 
     * @param mixed $value Price value
     * @return float Formatted price
     */
    protected function transform_price_field($value) {
        // Remove currency symbols and commas
        $price = preg_replace('/[^0-9.]/', '', $value);
        return floatval($price);
    }
    
    /**
     * Transform images field
     * 
     * @param array $value Airtable images array
     * @return array WordPress images format
     */
    protected function transform_images_field($value) {
        if (!is_array($value)) {
            return [];
        }
        
        return array_map(function($img) {
            return [
                'url' => $img['url'],
                'filename' => $img['filename'] ?? '',
                'type' => $img['type'] ?? 'image/jpeg',
                'size' => $img['size'] ?? 0,
                'thumbnails' => $img['thumbnails'] ?? []
            ];
        }, $value);
    }
    
    /**
     * Transform address field with geocoding
     * 
     * @param string $value Address string
     * @return array Address with geocoding data
     */
    protected function transform_address_field($value) {
        $address_data = [
            'address' => sanitize_text_field($value),
            'latitude' => null,
            'longitude' => null,
            'formatted_address' => $value
        ];
        
        // Attempt geocoding
        if (!empty($value)) {
            $geocoding_result = $this->geocode_address($value);
            if ($geocoding_result) {
                $address_data['latitude'] = $geocoding_result['lat'];
                $address_data['longitude'] = $geocoding_result['lng'];
                $address_data['formatted_address'] = $geocoding_result['formatted_address'] ?? $value;
            }
        }
        
        return $address_data;
    }
    
    /**
     * Transform WordPress field value to Airtable format
     * 
     * @param mixed $value WordPress field value
     * @param string $airtable_field Airtable field name
     * @param string $table_type Table type
     * @return mixed Transformed value
     */
    protected function transform_wp_field_value($value, $airtable_field, $table_type) {
        switch ($airtable_field) {
            case 'Price':
                return is_numeric($value) ? floatval($value) : 0;
                
            case 'Images':
                return $this->transform_wp_images_field($value);
                
            case 'Address':
                return is_array($value) ? $value['address'] : $value;
                
            case 'Features':
            case 'Specialties':
                return is_array($value) ? implode(', ', $value) : $value;
                
            default:
                return is_string($value) ? $value : strval($value);
        }
    }
    
    /**
     * Transform WordPress images to Airtable format
     * 
     * @param mixed $value WordPress images data
     * @return array Airtable images format
     */
    protected function transform_wp_images_field($value) {
        if (!is_array($value)) {
            return [];
        }
        
        return array_map(function($img) {
            return [
                'url' => $img['url'] ?? ''
            ];
        }, $value);
    }
    
    /**
     * Transform array field
     * 
     * @param mixed $value Array or comma-separated string
     * @return array Clean array
     */
    protected function transform_array_field($value) {
        if (is_array($value)) {
            return array_map('sanitize_text_field', $value);
        }
        
        if (is_string($value)) {
            return array_map('trim', array_map('sanitize_text_field', explode(',', $value)));
        }
        
        return [];
    }
    
    /**
     * Format phone number
     * 
     * @param string $value Phone number
     * @return string Formatted phone number
     */
    protected function format_phone_number($value) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $value);
        
        // Format US phone numbers
        if (strlen($phone) === 10) {
            return sprintf('(%s) %s-%s', 
                substr($phone, 0, 3),
                substr($phone, 3, 3),
                substr($phone, 6, 4)
            );
        }
        
        return $value; // Return original if not standard format
    }
    
    /**
     * Geocode address using Google Maps API or similar
     * 
     * @param string $address Address to geocode
     * @return array|false Geocoding result or false
     */
    protected function geocode_address($address) {
        // Check cache first
        $cache_key = 'geocode_' . md5($address);
        $cached = $this->get_cached_data($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Use WordPress geocoding service or Google Maps API
        $geocoding_service = new Geocoding_Service();
        $result = $geocoding_service->geocode($address);
        
        if ($result) {
            // Cache for 30 days
            $this->cache_manager->set($cache_key, $result, 30 * DAY_IN_SECONDS);
        }
        
        return $result;
    }
    
    /**
     * Validate transformed data
     * 
     * @param array $data Transformed data
     * @param string $table_type Table type
     * @throws Integration_Exception If validation fails
     */
    protected function validate_transformed_data($data, $table_type) {
        $validation_rules = $this->get_validation_rules($table_type);
        
        foreach ($validation_rules as $field => $rules) {
            if (isset($rules['required']) && $rules['required'] && empty($data[$field])) {
                throw new Integration_Exception("Required field '{$field}' is missing or empty");
            }
            
            if (isset($data[$field]) && isset($rules['type'])) {
                $this->validate_field_type($data[$field], $rules['type'], $field);
            }
        }
    }
    
    /**
     * Get validation rules for table type
     * 
     * @param string $table_type Table type
     * @return array Validation rules
     */
    protected function get_validation_rules($table_type) {
        $rules = [
            'listings' => [
                'listing_price' => ['type' => 'numeric', 'required' => true],
                'listing_address' => ['type' => 'string', 'required' => true],
                'bedrooms' => ['type' => 'integer'],
                'bathrooms' => ['type' => 'numeric'],
                'square_footage' => ['type' => 'integer']
            ],
            'agents' => [
                'agent_email' => ['type' => 'email', 'required' => true],
                'first_name' => ['type' => 'string', 'required' => true],
                'last_name' => ['type' => 'string', 'required' => true]
            ]
        ];
        
        return $rules[$table_type] ?? [];
    }
    
    /**
     * Validate field type
     * 
     * @param mixed $value Field value
     * @param string $expected_type Expected type
     * @param string $field_name Field name for error reporting
     * @throws Integration_Exception If type validation fails
     */
    protected function validate_field_type($value, $expected_type, $field_name) {
        switch ($expected_type) {
            case 'numeric':
                if (!is_numeric($value)) {
                    throw new Integration_Exception("Field '{$field_name}' must be numeric");
                }
                break;
                
            case 'integer':
                if (!is_int($value) && !ctype_digit($value)) {
                    throw new Integration_Exception("Field '{$field_name}' must be an integer");
                }
                break;
                
            case 'email':
                if (!is_email($value)) {
                    throw new Integration_Exception("Field '{$field_name}' must be a valid email");
                }
                break;
                
            case 'string':
                if (!is_string($value)) {
                    throw new Integration_Exception("Field '{$field_name}' must be a string");
                }
                break;
        }
    }
    
    /**
     * Determine table type from Airtable record
     * 
     * @param array $data Airtable record
     * @return string Table type
     */
    protected function determine_table_type($data) {
        // Logic to determine table type from record structure or metadata
        // This could be based on table ID, field names, or other indicators
        
        if (isset($data['fields']['Property ID']) || isset($data['fields']['MLS Number'])) {
            return 'listings';
        }
        
        if (isset($data['fields']['Agent ID']) || isset($data['fields']['License Number'])) {
            return 'agents';
        }
        
        // Default to listings for now
        return 'listings';
    }
    
    /**
     * Determine WordPress post type
     * 
     * @param array $data WordPress data
     * @return string Post type
     */
    protected function determine_wp_post_type($data) {
        if (isset($data['post_type'])) {
            return $data['post_type'] === 'listing' ? 'listings' : $data['post_type'];
        }
        
        return 'listings'; // Default
    }
    
    /**
     * Upsert WordPress listing
     * 
     * @param array $listing_data Transformed listing data
     * @return array Result with created status
     */
    protected function upsert_wp_listing($listing_data) {
        // Check if listing exists by Airtable ID
        $existing_post = $this->find_existing_wp_post($listing_data['airtable_id'], 'listing');
        
        $post_data = [
            'post_type' => 'listing',
            'post_status' => 'publish',
            'post_title' => $this->generate_listing_title($listing_data),
            'post_content' => $listing_data['listing_description'] ?? '',
            'meta_input' => $this->prepare_meta_data($listing_data)
        ];
        
        if ($existing_post) {
            // Update existing post
            $post_data['ID'] = $existing_post->ID;
            $post_id = wp_update_post($post_data);
            $created = false;
        } else {
            // Create new post
            $post_id = wp_insert_post($post_data);
            $created = true;
        }
        
        if (is_wp_error($post_id)) {
            throw new Integration_Exception('Failed to save WordPress listing: ' . $post_id->get_error_message());
        }
        
        return [
            'id' => $post_id,
            'created' => $created,
            'airtable_id' => $listing_data['airtable_id']
        ];
    }
    
    /**
     * Find existing WordPress post by Airtable ID
     * 
     * @param string $airtable_id Airtable record ID
     * @param string $post_type Post type
     * @return \WP_Post|null Existing post or null
     */
    protected function find_existing_wp_post($airtable_id, $post_type) {
        $posts = get_posts([
            'post_type' => $post_type,
            'meta_key' => 'airtable_id',
            'meta_value' => $airtable_id,
            'posts_per_page' => 1
        ]);
        
        return !empty($posts) ? $posts[0] : null;
    }
    
    /**
     * Generate listing title from data
     * 
     * @param array $listing_data Listing data
     * @return string Generated title
     */
    protected function generate_listing_title($listing_data) {
        $address = $listing_data['listing_address']['address'] ?? 'Unknown Address';
        $price = isset($listing_data['listing_price']) ? '$' . number_format($listing_data['listing_price']) : '';
        
        return trim("{$address} {$price}");
    }
    
    /**
     * Prepare meta data for WordPress
     * 
     * @param array $listing_data Listing data
     * @return array Meta data
     */
    protected function prepare_meta_data($listing_data) {
        $meta_data = $listing_data;
        
        // Remove non-meta fields
        unset($meta_data['table_type']);
        
        return $meta_data;
    }
    
    /**
     * Invalidate listing-related cache
     * 
     * @param int $listing_id WordPress post ID
     */
    protected function invalidate_listing_cache($listing_id) {
        $cache_keys = [
            "listing_card_{$listing_id}",
            "listing_data_{$listing_id}",
            "listing_archive_page",
            "featured_listings",
            "recent_listings"
        ];
        
        foreach ($cache_keys as $key) {
            $this->cache_manager->delete($key);
        }
        
        // Trigger action for other systems
        do_action('hph_listing_cache_invalidated', $listing_id);
    }
    
    /**
     * Get all Airtable listings
     * 
     * @return array Airtable records
     */
    protected function get_all_airtable_listings() {
        return $this->airtable_client->get_records($this->config['tables']['listings']);
    }
    
    /**
     * Get Airtable listings by IDs
     * 
     * @param array $ids Airtable record IDs
     * @return array Airtable records
     */
    protected function get_airtable_listings_by_ids($ids) {
        return $this->airtable_client->get_records_by_ids($this->config['tables']['listings'], $ids);
    }
    
    /**
     * Get cache keys for data invalidation
     * 
     * @param array $data Updated data
     * @return array Cache keys to invalidate
     */
    protected function get_cache_keys_for_data($data) {
        $keys = [
            'airtable_listings',
            'airtable_agents',
            'sync_status'
        ];
        
        if (isset($data['airtable_id'])) {
            $keys[] = "airtable_record_{$data['airtable_id']}";
        }
        
        return $keys;
    }
    
    /**
     * Validate configuration
     * 
     * @return bool Configuration is valid
     */
    protected function validate_config() {
        return !empty($this->config['api_key']) && 
               !empty($this->config['base_id']) &&
               !empty($this->config['tables']);
    }
    
    /**
     * Test API connection
     * 
     * @return bool API is reachable
     */
    protected function test_api_connection() {
        try {
            $this->airtable_client->test_connection();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
