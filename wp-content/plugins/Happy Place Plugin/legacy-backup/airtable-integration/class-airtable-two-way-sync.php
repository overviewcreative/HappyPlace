<?php
namespace HappyPlace\Integrations;

class Airtable_Two_Way_Sync {
    private string $base_url = 'https://api.airtable.com/v0/';
    private string $access_token;
    private string $base_id;
    private string $table_name;

    // Mapping between Airtable fields and WordPress meta keys with validation rules
    private array $field_mapping = [
        'listing_id' => [
            'airtable_field' => 'Record ID',
            'wp_field' => 'listing_id',
            'type' => 'string',
            'required' => false,
            'sanitize' => 'sanitize_text_field'
        ],
        'title' => [
            'airtable_field' => 'Property Name',
            'wp_field' => 'title',
            'type' => 'string',
            'required' => true,
            'sanitize' => 'sanitize_text_field',
            'max_length' => 200
        ],
        'address' => [
            'airtable_field' => 'Street Address',
            'wp_field' => 'address',
            'type' => 'string',
            'required' => false,
            'sanitize' => 'sanitize_text_field'
        ],
        'price' => [
            'airtable_field' => 'List Price',
            'wp_field' => 'price',
            'type' => 'number',
            'required' => false,
            'sanitize' => 'floatval',
            'min' => 0,
            'max' => 50000000
        ],
        'bedrooms' => [
            'airtable_field' => 'Bedrooms',
            'wp_field' => 'bedrooms',
            'type' => 'integer',
            'required' => false,
            'sanitize' => 'intval',
            'min' => 0,
            'max' => 20
        ],
        'bathrooms' => [
            'airtable_field' => 'Bathrooms',
            'wp_field' => 'bathrooms',
            'type' => 'number',
            'required' => false,
            'sanitize' => 'floatval',
            'min' => 0,
            'max' => 20
        ],
        'square_footage' => [
            'airtable_field' => 'Square Footage',
            'wp_field' => 'square_footage',
            'type' => 'integer',
            'required' => false,
            'sanitize' => 'intval',
            'min' => 0,
            'max' => 50000
        ],
        'property_type' => [
            'airtable_field' => 'Property Type',
            'wp_field' => 'property_type',
            'type' => 'select',
            'required' => false,
            'sanitize' => 'sanitize_text_field',
            'allowed_values' => ['Single Family', 'Condo', 'Townhouse', 'Multi-Family', 'Land', 'Commercial']
        ],
        'status' => [
            'airtable_field' => 'Listing Status',
            'wp_field' => 'status',
            'type' => 'select',
            'required' => false,
            'sanitize' => 'sanitize_text_field',
            'allowed_values' => ['active', 'pending', 'sold', 'withdrawn', 'expired']
        ],
        'description' => [
            'airtable_field' => 'Property Description',
            'wp_field' => 'description',
            'type' => 'text',
            'required' => false,
            'sanitize' => 'sanitize_textarea_field',
            'max_length' => 5000
        ],
        'latitude' => [
            'airtable_field' => 'Latitude',
            'wp_field' => 'latitude',
            'type' => 'number',
            'required' => false,
            'sanitize' => 'floatval',
            'min' => -90,
            'max' => 90
        ],
        'longitude' => [
            'airtable_field' => 'Longitude',
            'wp_field' => 'longitude',
            'type' => 'number',
            'required' => false,
            'sanitize' => 'floatval',
            'min' => -180,
            'max' => 180
        ],
        'city' => [
            'airtable_field' => 'City',
            'wp_field' => 'city',
            'type' => 'string',
            'required' => false,
            'sanitize' => 'sanitize_text_field'
        ],
        'state' => [
            'airtable_field' => 'State',
            'wp_field' => 'state',
            'type' => 'string',
            'required' => false,
            'sanitize' => 'sanitize_text_field',
            'max_length' => 2
        ],
        'zip_code' => [
            'airtable_field' => 'ZIP Code',
            'wp_field' => 'zip_code',
            'type' => 'string',
            'required' => false,
            'sanitize' => 'sanitize_text_field',
            'pattern' => '/^\d{5}(-\d{4})?$/'
        ],
        'mls_number' => [
            'airtable_field' => 'MLS Number',
            'wp_field' => 'mls_number',
            'type' => 'string',
            'required' => false,
            'sanitize' => 'sanitize_text_field'
        ],
        'lot_size' => [
            'airtable_field' => 'Lot Size',
            'wp_field' => 'lot_size_sqft',
            'type' => 'number',
            'required' => false,
            'sanitize' => 'floatval',
            'min' => 0
        ],
        'year_built' => [
            'airtable_field' => 'Year Built',
            'wp_field' => 'year_built',
            'type' => 'integer',
            'required' => false,
            'sanitize' => 'intval',
            'min' => 1800,
            'max' => 2030
        ],
        'garage_spaces' => [
            'airtable_field' => 'Garage Spaces',
            'wp_field' => 'garage_spaces',
            'type' => 'integer',
            'required' => false,
            'sanitize' => 'intval',
            'min' => 0,
            'max' => 10
        ],
        'main_photo' => [
            'airtable_field' => 'Main Photo',
            'wp_field' => 'main_photo',
            'type' => 'attachment',
            'required' => false,
            'sanitize' => 'attachment_field'
        ],
        'photo_gallery' => [
            'airtable_field' => 'Photo Gallery',
            'wp_field' => 'photo_gallery',
            'type' => 'attachment_multiple',
            'required' => false,
            'sanitize' => 'attachment_multiple_field'
        ],
        'floor_plans' => [
            'airtable_field' => 'Floor Plans',
            'wp_field' => 'floor_plans',
            'type' => 'attachment_multiple',
            'required' => false,
            'sanitize' => 'attachment_multiple_field'
        ],
        'virtual_tour_url' => [
            'airtable_field' => 'Virtual Tour URL',
            'wp_field' => 'virtual_tour_link',
            'type' => 'url',
            'required' => false,
            'sanitize' => 'esc_url_raw'
        ],
        
        // Additional Address Components
        'address' => [
            'airtable_field' => 'Street Address', // Alternative mapping for backwards compatibility
            'wp_field' => 'address',
            'type' => 'string',
            'required' => false,
            'sanitize' => 'sanitize_text_field'
        ],
        'city' => [
            'airtable_field' => 'City',
            'wp_field' => 'city',
            'type' => 'string',
            'required' => false,
            'sanitize' => 'sanitize_text_field'
        ],
        'state' => [
            'airtable_field' => 'State',
            'wp_field' => 'state',
            'type' => 'string',
            'required' => false,
            'sanitize' => 'sanitize_text_field',
            'max_length' => 2
        ],
        'zip_code' => [
            'airtable_field' => 'ZIP Code',
            'wp_field' => 'zip_code',
            'type' => 'string',
            'required' => false,
            'sanitize' => 'sanitize_text_field',
            'pattern' => '/^\d{5}(-\d{4})?$/'
        ],
        
        // Full Description Field
        'description' => [
            'airtable_field' => 'Property Description',
            'wp_field' => 'description',
            'type' => 'text',
            'required' => false,
            'sanitize' => 'sanitize_textarea_field',
            'max_length' => 5000
        ],
        'short_description' => [
            'airtable_field' => 'Short Description',
            'wp_field' => 'short_description',
            'type' => 'text',
            'required' => false,
            'sanitize' => 'sanitize_textarea_field',
            'max_length' => 500
        ],
        
        // Financial Fields for Calculations
        'property_tax_rate' => [
            'airtable_field' => 'Property Tax Rate',
            'wp_field' => 'property_tax_rate',
            'type' => 'number',
            'required' => false,
            'sanitize' => 'floatval',
            'min' => 0,
            'max' => 10
        ],
        'estimated_annual_taxes' => [
            'airtable_field' => 'Annual Property Taxes',
            'wp_field' => 'estimated_annual_taxes',
            'type' => 'number',
            'required' => false,
            'sanitize' => 'floatval',
            'min' => 0
        ],
        'estimated_monthly_insurance' => [
            'airtable_field' => 'Monthly Insurance',
            'wp_field' => 'estimated_monthly_insurance',
            'type' => 'number',
            'required' => false,
            'sanitize' => 'floatval',
            'min' => 0
        ],
        
        // HOA Fields
        'hoa_monthly' => [
            'airtable_field' => 'HOA Monthly',
            'wp_field' => 'hoa_monthly',
            'type' => 'number',
            'required' => false,
            'sanitize' => 'floatval',
            'min' => 0
        ],
        'hoa_quarterly' => [
            'airtable_field' => 'HOA Quarterly',
            'wp_field' => 'hoa_quarterly',
            'type' => 'number',
            'required' => false,
            'sanitize' => 'floatval',
            'min' => 0
        ],
        'hoa_annual' => [
            'airtable_field' => 'HOA Annual',
            'wp_field' => 'hoa_annual',
            'type' => 'number',
            'required' => false,
            'sanitize' => 'floatval',
            'min' => 0
        ],
        
        // Additional Square Footage Fields
        'living_square_footage' => [
            'airtable_field' => 'Living Square Footage',
            'wp_field' => 'living_square_footage',
            'type' => 'integer',
            'required' => false,
            'sanitize' => 'intval',
            'min' => 0,
            'max' => 50000
        ],
        'garage_square_footage' => [
            'airtable_field' => 'Garage Square Footage',
            'wp_field' => 'garage_square_footage',
            'type' => 'integer',
            'required' => false,
            'sanitize' => 'intval',
            'min' => 0,
            'max' => 5000
        ],
        'basement_square_footage' => [
            'airtable_field' => 'Basement Square Footage',
            'wp_field' => 'basement_square_footage',
            'type' => 'integer',
            'required' => false,
            'sanitize' => 'intval',
            'min' => 0,
            'max' => 10000
        ],
        
        // Lot Size (more detailed)
        'lot_size_sqft' => [
            'airtable_field' => 'Lot Size Sqft',
            'wp_field' => 'lot_size_sqft',
            'type' => 'number',
            'required' => false,
            'sanitize' => 'floatval',
            'min' => 0
        ],
        'lot_size_acres' => [
            'airtable_field' => 'Lot Size Acres',
            'wp_field' => 'lot_size_acres',
            'type' => 'number',
            'required' => false,
            'sanitize' => 'floatval',
            'min' => 0
        ],
        
        // Property Features as Boolean Fields
        'garage' => [
            'airtable_field' => 'Has Garage',
            'wp_field' => 'garage',
            'type' => 'boolean',
            'required' => false,
            'sanitize' => 'boolean_field'
        ],
        'pool' => [
            'airtable_field' => 'Has Pool',
            'wp_field' => 'pool',
            'type' => 'boolean',
            'required' => false,
            'sanitize' => 'boolean_field'
        ],
        'fireplace' => [
            'airtable_field' => 'Has Fireplace',
            'wp_field' => 'fireplace',
            'type' => 'boolean',
            'required' => false,
            'sanitize' => 'boolean_field'
        ],
        'basement' => [
            'airtable_field' => 'Has Basement',
            'wp_field' => 'basement',
            'type' => 'boolean',
            'required' => false,
            'sanitize' => 'boolean_field'
        ],
        'deck_patio' => [
            'airtable_field' => 'Has Deck/Patio',
            'wp_field' => 'deck_patio',
            'type' => 'boolean',
            'required' => false,
            'sanitize' => 'boolean_field'
        ],
        
        // Financing Fields
        'estimated_down_payment' => [
            'airtable_field' => 'Down Payment Percentage',
            'wp_field' => 'estimated_down_payment',
            'type' => 'number',
            'required' => false,
            'sanitize' => 'floatval',
            'min' => 0,
            'max' => 100
        ],
        'estimated_interest_rate' => [
            'airtable_field' => 'Interest Rate',
            'wp_field' => 'estimated_interest_rate',
            'type' => 'number',
            'required' => false,
            'sanitize' => 'floatval',
            'min' => 0,
            'max' => 20
        ],
        'estimated_monthly_rent' => [
            'airtable_field' => 'Estimated Monthly Rent',
            'wp_field' => 'estimated_monthly_rent',
            'type' => 'number',
            'required' => false,
            'sanitize' => 'floatval',
            'min' => 0
        ],
        
        // Additional Property Details
        'stories' => [
            'airtable_field' => 'Stories',
            'wp_field' => 'stories',
            'type' => 'integer',
            'required' => false,
            'sanitize' => 'intval',
            'min' => 1,
            'max' => 10
        ],
        'total_rooms' => [
            'airtable_field' => 'Total Rooms',
            'wp_field' => 'total_rooms',
            'type' => 'integer',
            'required' => false,
            'sanitize' => 'intval',
            'min' => 0,
            'max' => 50
        ],
        
        // Listing Dates
        'listing_date' => [
            'airtable_field' => 'Listing Date',
            'wp_field' => 'listing_date',
            'type' => 'date',
            'required' => false,
            'sanitize' => 'sanitize_text_field'
        ],
        
        // School District
        'school_district' => [
            'airtable_field' => 'School District',
            'wp_field' => 'school_district',
            'type' => 'string',
            'required' => false,
            'sanitize' => 'sanitize_text_field'
        ]
    ];

    // Validation errors collected during sync
    private array $validation_errors = [];
    
    // Sync statistics
    private array $sync_stats = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0
    ];

    public function __construct(string $base_id, string $table_name) {
        // Try new integration settings first, fallback to old format
        $integration_options = get_option('happy_place_integrations', []);
        $old_options = get_option('happy_place_options', []);
        
        $airtable_settings = $integration_options['airtable'] ?? [];
        
        // Use access token (new) or fallback to API key (legacy support)
        $this->access_token = $airtable_settings['access_token'] ?? $airtable_settings['api_key'] ?? $old_options['airtable_api_key'] ?? '';
        $this->base_id = $base_id;
        $this->table_name = $table_name;
        
        if (empty($this->access_token)) {
            throw new \Exception('Airtable Personal Access Token is required. Please configure in Happy Place → Integrations.');
        }
    }

    /**
     * Sync Listings from Airtable to WordPress
     */
    public function sync_airtable_to_wordpress(): array {
        try {
            $this->reset_sync_stats();
            
            error_log("HPH: Starting Airtable to WordPress sync");
            
            // First, test the connection and get available fields
            $connection_test = $this->test_field_mapping();
            if (!$connection_test['success']) {
                return [
                    'success' => false,
                    'error' => $connection_test['error'],
                    'stats' => $this->sync_stats,
                    'validation_errors' => $this->validation_errors
                ];
            }
            
            $airtable_records = $this->fetch_all_airtable_records();
            $processed_records = $this->process_airtable_records($airtable_records);

            return [
                'success' => true,
                'total_records' => count($airtable_records),
                'processed_records' => count($processed_records),
                'stats' => $this->sync_stats,
                'validation_errors' => $this->validation_errors,
                'field_mapping_info' => $connection_test['field_mapping_info'] ?? [],
                'warnings' => $connection_test['warnings'] ?? [],
                'message' => sprintf(
                    'Processed %d records: %d created, %d updated, %d skipped, %d errors',
                    count($airtable_records),
                    $this->sync_stats['created'],
                    $this->sync_stats['updated'],
                    $this->sync_stats['skipped'],
                    $this->sync_stats['errors']
                )
            ];
        } catch (\Exception $e) {
            error_log('HPH: Airtable to WordPress Sync Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'stats' => $this->sync_stats,
                'validation_errors' => $this->validation_errors
            ];
        }
    }

    /**
     * Test field mapping and provide feedback on field availability
     */
    private function test_field_mapping(): array {
        try {
            // Get a sample record to check field availability
            $url = "{$this->base_url}{$this->base_id}/{$this->table_name}?maxRecords=1";
            
            $response = wp_remote_get($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->access_token,
                    'Content-Type' => 'application/json'
                ],
                'timeout' => 30
            ]);

            if (is_wp_error($response)) {
                return [
                    'success' => false,
                    'error' => 'Connection failed: ' . $response->get_error_message()
                ];
            }

            $code = wp_remote_retrieve_response_code($response);
            if ($code !== 200) {
                $body = wp_remote_retrieve_body($response);
                $error_data = json_decode($body, true);
                return [
                    'success' => false,
                    'error' => 'HTTP ' . $code . ': ' . ($error_data['error']['message'] ?? 'Unknown error')
                ];
            }

            $data = json_decode(wp_remote_retrieve_body($response), true);
            $available_fields = [];
            
            if (!empty($data['records'][0]['fields'])) {
                $available_fields = array_keys($data['records'][0]['fields']);
            }

            error_log("HPH: Available Airtable fields: " . implode(', ', $available_fields));

            // Check field mapping
            $mapping_info = [];
            $missing_required = [];
            
            foreach ($this->field_mapping as $wp_key => $field_config) {
                $airtable_field = $field_config['airtable_field'];
                $matched_field = $this->find_matching_field($airtable_field, $available_fields);
                
                $mapping_info[$wp_key] = [
                    'expected' => $airtable_field,
                    'matched' => $matched_field,
                    'status' => $matched_field ? 'found' : 'missing',
                    'required' => $field_config['required'] ?? false
                ];
                
                if ($matched_field) {
                    error_log("HPH: Field mapping - {$wp_key}: '{$airtable_field}' -> '{$matched_field}'");
                } else {
                    error_log("HPH: Field mapping - {$wp_key}: '{$airtable_field}' -> NOT FOUND");
                }
                
                if (!$matched_field && ($field_config['required'] ?? false)) {
                    $missing_required[] = $airtable_field;
                }
            }

            $warnings = [];
            if (!empty($missing_required)) {
                $warnings[] = 'Missing required fields: ' . implode(', ', $missing_required);
                error_log("HPH: Warning - Missing required fields: " . implode(', ', $missing_required));
            }

            return [
                'success' => true,
                'available_fields' => $available_fields,
                'field_mapping_info' => $mapping_info,
                'warnings' => $warnings
            ];

        } catch (\Exception $e) {
            error_log("HPH: Field mapping test error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Field mapping test failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Sync Listings from WordPress to Airtable
     */
    public function sync_wordpress_to_airtable(): array {
        try {
            $this->reset_sync_stats();
            $wordpress_listings = $this->get_wordpress_listings();
            $processed_records = $this->update_airtable_records($wordpress_listings);

            return [
                'success' => true,
                'total_records' => count($wordpress_listings),
                'processed_records' => count($processed_records),
                'stats' => $this->sync_stats,
                'validation_errors' => $this->validation_errors,
                'message' => sprintf(
                    'Processed %d records: %d created, %d updated, %d skipped, %d errors',
                    count($wordpress_listings),
                    $this->sync_stats['created'],
                    $this->sync_stats['updated'],
                    $this->sync_stats['skipped'],
                    $this->sync_stats['errors']
                )
            ];
        } catch (\Exception $e) {
            error_log('WordPress to Airtable Sync Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'stats' => $this->sync_stats,
                'validation_errors' => $this->validation_errors
            ];
        }
    }

    /**
     * Reset sync statistics
     */
    private function reset_sync_stats(): void {
        $this->sync_stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0
        ];
        $this->validation_errors = [];
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
        
        error_log("HPH: Processing " . count($records) . " Airtable records");

        foreach ($records as $record) {
            try {
                $fields = $record['fields'] ?? [];
                
                // Log available fields for debugging
                error_log("HPH: Processing record {$record['id']} with fields: " . implode(', ', array_keys($fields)));
                
                // Validate and sanitize incoming data
                $validated_data = $this->validate_and_sanitize_airtable_data($fields, $record['id']);
                
                if (empty($validated_data)) {
                    error_log("HPH: Skipping record {$record['id']} - no valid data after validation");
                    $this->sync_stats['skipped']++;
                    continue;
                }
                
                // Check if title is provided (required for creating posts)
                $title = $validated_data['title'] ?? '';
                if (empty($title)) {
                    // Try to create a title from address or other fields
                    $title = $this->generate_fallback_title($validated_data, $fields);
                    if (empty($title)) {
                        $this->add_validation_error($record['id'], 'title', 'Title is required but could not be generated from available data');
                        $this->sync_stats['skipped']++;
                        continue;
                    }
                    $validated_data['title'] = $title;
                }
                
                // Prepare post data
                $post_data = [
                    'post_type' => 'listing',
                    'post_title' => $title,
                    'post_content' => $validated_data['description'] ?? '',
                    'post_status' => 'publish'
                ];

                // Insert or update post
                $post_id = $this->find_or_create_listing($post_data, $record['id']);
                
                if (is_wp_error($post_id)) {
                    $this->add_validation_error($record['id'], 'post_creation', $post_id->get_error_message());
                    $this->sync_stats['errors']++;
                    continue;
                }

                // Update custom fields with validated data
                $this->update_post_acf_fields($post_id, $validated_data);

                // Store Airtable record ID for future sync
                update_post_meta($post_id, '_airtable_record_id', $record['id']);
                update_post_meta($post_id, '_airtable_last_sync', current_time('mysql'));

                $processed[] = $post_id;
                error_log("HPH: Successfully processed record {$record['id']} -> WordPress post {$post_id}");
                
            } catch (\Exception $e) {
                error_log("HPH: Error processing record {$record['id']}: " . $e->getMessage());
                $this->add_validation_error($record['id'] ?? 'unknown', 'processing', $e->getMessage());
                $this->sync_stats['errors']++;
                continue;
            }
        }

        error_log("HPH: Finished processing. Created/Updated: " . count($processed) . ", Errors: " . $this->sync_stats['errors'] . ", Skipped: " . $this->sync_stats['skipped']);
        return $processed;
    }

    /**
     * Generate a fallback title when Property Name is missing
     */
    private function generate_fallback_title(array $validated_data, array $raw_fields): string {
        // Try different combinations to create a meaningful title
        $title_parts = [];
        
        // Try address components
        if (!empty($validated_data['address'])) {
            $title_parts[] = $validated_data['address'];
        }
        
        if (!empty($validated_data['city'])) {
            $title_parts[] = $validated_data['city'];
        }
        
        if (!empty($title_parts)) {
            $title = implode(', ', $title_parts);
        } else {
            // Try property type + bedroom/bathroom info
            $property_type = $validated_data['property_type'] ?? '';
            $bedrooms = $validated_data['bedrooms'] ?? '';
            $bathrooms = $validated_data['bathrooms'] ?? '';
            
            if ($property_type) {
                $title = $property_type;
                if ($bedrooms || $bathrooms) {
                    $title .= " ({$bedrooms}BR/{$bathrooms}BA)";
                }
            } else {
                // Last resort: use any available text field
                foreach ($raw_fields as $field_name => $value) {
                    if (is_string($value) && !empty(trim($value)) && strlen($value) > 3) {
                        $title = sanitize_text_field(substr($value, 0, 100));
                        break;
                    }
                }
                
                if (empty($title)) {
                    $title = 'Property Listing ' . date('Y-m-d H:i:s');
                }
            }
        }
        
        return $title;
    }

    /**
     * Validate and sanitize data from Airtable with flexible field mapping
     */
    private function validate_and_sanitize_airtable_data(array $fields, string $record_id): array {
        $validated_data = [];
        $available_fields = array_keys($fields);
        
        foreach ($this->field_mapping as $wp_key => $field_config) {
            $airtable_field = $field_config['airtable_field'];
            
            // Try to find the field with flexible matching
            $matched_field = $this->find_matching_field($airtable_field, $available_fields);
            
            if (!$matched_field) {
                // Log missing field but continue processing
                if ($field_config['required'] ?? false) {
                    $this->add_validation_error($record_id, $wp_key, "Required field '{$airtable_field}' not found. Available fields: " . implode(', ', $available_fields));
                    continue;
                } else {
                    // Set default value for non-required missing fields
                    $validated_data[$wp_key] = $this->get_default_value($field_config['type']);
                    continue;
                }
            }
            
            $raw_value = $fields[$matched_field];
            
            // Handle null/empty values
            if ($raw_value === null || $raw_value === '') {
                if ($field_config['required'] ?? false) {
                    $this->add_validation_error($record_id, $wp_key, "Required field '{$matched_field}' is empty");
                    continue;
                }
                // Set default value for non-required fields
                $validated_data[$wp_key] = $this->get_default_value($field_config['type']);
                continue;
            }
            
            // Sanitize and validate the value
            $sanitized_value = $this->sanitize_field_value($raw_value, $field_config, $record_id);
            
            if ($sanitized_value === false) {
                $this->add_validation_error($record_id, $wp_key, "Invalid value for field '{$matched_field}': " . var_export($raw_value, true));
                // Use default value instead of failing completely
                $validated_data[$wp_key] = $this->get_default_value($field_config['type']);
                continue;
            }
            
            // Additional validation checks
            if (!$this->validate_field_constraints($sanitized_value, $field_config)) {
                $this->add_validation_error($record_id, $wp_key, "Value '{$sanitized_value}' does not meet constraints for field '{$matched_field}'");
                // Use default value instead of failing completely
                $validated_data[$wp_key] = $this->get_default_value($field_config['type']);
                continue;
            }
            
            $validated_data[$wp_key] = $sanitized_value;
        }
        
        return $validated_data;
    }

    /**
     * Find matching field name with flexible matching
     */
    private function find_matching_field(string $target_field, array $available_fields): ?string {
        // Exact match first
        if (in_array($target_field, $available_fields)) {
            return $target_field;
        }
        
        // Case-insensitive match
        foreach ($available_fields as $field) {
            if (strcasecmp($target_field, $field) === 0) {
                return $field;
            }
        }
        
        // Try common variations and synonyms
        $field_variations = $this->get_field_variations($target_field);
        foreach ($field_variations as $variation) {
            foreach ($available_fields as $field) {
                if (strcasecmp($variation, $field) === 0) {
                    return $field;
                }
            }
        }
        
        // Try partial matching (contains)
        $normalized_target = strtolower(str_replace([' ', '_', '-'], '', $target_field));
        foreach ($available_fields as $field) {
            $normalized_field = strtolower(str_replace([' ', '_', '-'], '', $field));
            if (strpos($normalized_field, $normalized_target) !== false || 
                strpos($normalized_target, $normalized_field) !== false) {
                return $field;
            }
        }
        
        return null;
    }

    /**
     * Get common variations and synonyms for field names
     */
    private function get_field_variations(string $field_name): array {
        $variations = [];
        
        // Common field name mappings
        $synonyms = [
            'Property Name' => ['Title', 'Name', 'Property Title', 'Listing Title', 'Property'],
            'Street Address' => ['Address', 'Street', 'Property Address', 'Full Address'],
            'List Price' => ['Price', 'Listing Price', 'Property Price', 'Cost', 'Amount'],
            'Bedrooms' => ['Beds', 'Bedroom', 'Bed', 'BR', 'Bed Count'],
            'Bathrooms' => ['Baths', 'Bathroom', 'Bath', 'BA', 'Bath Count'],
            'Square Footage' => ['Sqft', 'Sq Ft', 'Square Feet', 'Size', 'Area', 'Living Area'],
            'Property Type' => ['Type', 'Property Category', 'Category', 'Building Type'],
            'Listing Status' => ['Status', 'Property Status', 'State', 'Availability'],
            'Property Description' => ['Description', 'Details', 'Notes', 'Comments', 'Summary'],
            'Year Built' => ['Built', 'Year', 'Construction Year', 'Built Year'],
            'Lot Size' => ['Lot', 'Lot Size Sqft', 'Land Size', 'Plot Size']
        ];
        
        // Add direct synonyms
        if (isset($synonyms[$field_name])) {
            $variations = array_merge($variations, $synonyms[$field_name]);
        }
        
        // Add case variations
        $variations[] = strtolower($field_name);
        $variations[] = strtoupper($field_name);
        $variations[] = ucwords(strtolower($field_name));
        
        // Add underscore/dash variations
        $variations[] = str_replace(' ', '_', $field_name);
        $variations[] = str_replace(' ', '-', $field_name);
        $variations[] = str_replace('_', ' ', $field_name);
        $variations[] = str_replace('-', ' ', $field_name);
        
        return array_unique($variations);
    }

    /**
     * Sanitize field value based on type and sanitization function
     */
    private function sanitize_field_value($value, array $field_config, string $record_id = '') {
        $sanitize_function = $field_config['sanitize'] ?? 'sanitize_text_field';
        $type = $field_config['type'] ?? 'string';
        
        try {
            switch ($type) {
                case 'integer':
                    // Handle various numeric formats
                    if (is_numeric($value)) {
                        return intval($value);
                    } elseif (is_string($value)) {
                        // Remove common currency/number formatting
                        $cleaned = preg_replace('/[^0-9.-]/', '', $value);
                        return is_numeric($cleaned) ? intval($cleaned) : 0;
                    }
                    return 0;
                    
                case 'number':
                    // Handle various numeric formats including decimals
                    if (is_numeric($value)) {
                        return floatval($value);
                    } elseif (is_string($value)) {
                        // Remove common currency/number formatting
                        $cleaned = preg_replace('/[^0-9.-]/', '', $value);
                        return is_numeric($cleaned) ? floatval($cleaned) : 0.0;
                    }
                    return 0.0;
                    
                case 'string':
                case 'text':
                    if (is_array($value)) {
                        // Handle array values (join with commas)
                        $value = implode(', ', $value);
                    } elseif (!is_string($value)) {
                        // Convert other types to string
                        $value = (string) $value;
                    }
                    
                    if (function_exists($sanitize_function)) {
                        return call_user_func($sanitize_function, $value);
                    }
                    return sanitize_text_field($value);
                    
                case 'select':
                    if (is_array($value)) {
                        // Take first value if array
                        $value = reset($value);
                    }
                    $sanitized = sanitize_text_field((string) $value);
                    $allowed_values = $field_config['allowed_values'] ?? [];
                    
                    if (!empty($allowed_values)) {
                        // Try exact match first
                        if (in_array($sanitized, $allowed_values)) {
                            return $sanitized;
                        }
                        
                        // Try case-insensitive match
                        foreach ($allowed_values as $allowed) {
                            if (strcasecmp($sanitized, $allowed) === 0) {
                                return $allowed;
                            }
                        }
                        
                        // Try partial match
                        foreach ($allowed_values as $allowed) {
                            if (stripos($allowed, $sanitized) !== false || stripos($sanitized, $allowed) !== false) {
                                return $allowed;
                            }
                        }
                        
                        // Return first allowed value as fallback
                        return reset($allowed_values);
                    }
                    
                    return $sanitized;
                    
                case 'attachment':
                    // Handle Airtable attachment field (single image)
                    return $this->process_airtable_attachment($value, $record_id);
                    
                case 'attachment_multiple':
                    // Handle Airtable attachment field (multiple images)
                    return $this->process_airtable_attachments($value, $record_id);
                    
                case 'boolean':
                    // Handle boolean/checkbox fields from Airtable
                    return $this->sanitize_boolean_field($value);
                    
                case 'date':
                    // Handle date fields
                    if (empty($value)) {
                        return '';
                    }
                    // Convert to WordPress date format if needed
                    $timestamp = strtotime($value);
                    return $timestamp ? date('Y-m-d', $timestamp) : '';
                    
                case 'url':
                    if (is_array($value)) {
                        $value = reset($value);
                    }
                    return esc_url_raw((string) $value);
                    
                default:
                    return sanitize_text_field((string) $value);
            }
        } catch (\Exception $e) {
            error_log("HPH: Sanitization error for value '" . var_export($value, true) . "': " . $e->getMessage());
            return $this->get_default_value($type);
        }
    }

    /**
     * Validate field constraints (min, max, pattern, etc.)
     */
    private function validate_field_constraints($value, array $field_config): bool {
        // Check min/max for numeric values
        if (isset($field_config['min']) && is_numeric($value) && $value < $field_config['min']) {
            return false;
        }
        
        if (isset($field_config['max']) && is_numeric($value) && $value > $field_config['max']) {
            return false;
        }
        
        // Check max_length for strings
        if (isset($field_config['max_length']) && is_string($value) && strlen($value) > $field_config['max_length']) {
            return false;
        }
        
        // Check pattern for strings
        if (isset($field_config['pattern']) && is_string($value) && !preg_match($field_config['pattern'], $value)) {
            return false;
        }
        
        return true;
    }

    /**
     * Get default value for field type
     */
    private function get_default_value(string $type) {
        switch ($type) {
            case 'integer':
                return 0;
            case 'number':
                return 0.0;
            case 'attachment':
                return null;
            case 'attachment_multiple':
                return [];
            case 'string':
            case 'text':
            case 'select':
            default:
                return '';
        }
    }

    /**
     * Process single Airtable attachment field
     * Airtable attachment fields contain: [{id, url, filename, size, type, thumbnails}]
     */
    private function process_airtable_attachment($value, string $record_id) {
        if (empty($value) || !is_array($value)) {
            return null;
        }

        // Get the first attachment if multiple exist
        $attachment = is_array($value[0]) ? $value[0] : $value;
        
        if (!isset($attachment['url'])) {
            error_log("HPH: No URL found in attachment for record {$record_id}");
            return null;
        }

        try {
            $attachment_id = $this->import_image_from_airtable($attachment, $record_id);
            return $attachment_id;
        } catch (\Exception $e) {
            error_log("HPH: Error importing single attachment for record {$record_id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Process multiple Airtable attachment fields
     */
    private function process_airtable_attachments($value, string $record_id) {
        if (empty($value) || !is_array($value)) {
            return [];
        }

        $attachment_ids = [];
        
        foreach ($value as $index => $attachment) {
            if (!is_array($attachment) || !isset($attachment['url'])) {
                error_log("HPH: Invalid attachment data at index {$index} for record {$record_id}");
                continue;
            }

            try {
                $attachment_id = $this->import_image_from_airtable($attachment, $record_id, $index);
                if ($attachment_id) {
                    $attachment_ids[] = $attachment_id;
                }
            } catch (\Exception $e) {
                error_log("HPH: Error importing attachment {$index} for record {$record_id}: " . $e->getMessage());
                continue;
            }
        }

        return $attachment_ids;
    }

    /**
     * Import image from Airtable attachment to WordPress Media Library
     */
    private function import_image_from_airtable(array $attachment, string $record_id, int $index = 0): ?int {
        $url = $attachment['url'] ?? '';
        $filename = $attachment['filename'] ?? '';
        $airtable_id = $attachment['id'] ?? '';

        if (empty($url)) {
            throw new \Exception("No URL provided for attachment");
        }

        // Check if we've already imported this attachment
        if (!empty($airtable_id)) {
            $existing_id = $this->find_existing_attachment($airtable_id);
            if ($existing_id) {
                error_log("HPH: Found existing attachment ID {$existing_id} for Airtable attachment {$airtable_id}");
                return $existing_id;
            }
        }

        // Generate filename if not provided
        if (empty($filename)) {
            $filename = 'property-image-' . $record_id . '-' . $index . '.jpg';
        }

        // Ensure safe filename
        $filename = sanitize_file_name($filename);

        try {
            // Download the image
            $response = wp_remote_get($url, [
                'timeout' => 30,
                'headers' => [
                    'User-Agent' => 'Happy Place Plugin/1.0'
                ]
            ]);

            if (is_wp_error($response)) {
                throw new \Exception("Failed to download image: " . $response->get_error_message());
            }

            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                throw new \Exception("HTTP {$response_code} error downloading image");
            }

            $image_data = wp_remote_retrieve_body($response);
            if (empty($image_data)) {
                throw new \Exception("Empty image data received");
            }

            // Upload to WordPress
            $upload = wp_upload_bits($filename, null, $image_data);
            if ($upload['error']) {
                throw new \Exception("Upload failed: " . $upload['error']);
            }

            // Create attachment post
            $attachment_data = [
                'post_mime_type' => $attachment['type'] ?? 'image/jpeg',
                'post_title' => pathinfo($filename, PATHINFO_FILENAME),
                'post_content' => '',
                'post_status' => 'inherit'
            ];

            $attachment_id = wp_insert_attachment($attachment_data, $upload['file']);
            if (is_wp_error($attachment_id)) {
                throw new \Exception("Failed to create attachment: " . $attachment_id->get_error_message());
            }

            // Generate metadata and thumbnails
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
            wp_update_attachment_metadata($attachment_id, $metadata);

            // Store Airtable reference
            if (!empty($airtable_id)) {
                update_post_meta($attachment_id, '_airtable_attachment_id', $airtable_id);
            }
            update_post_meta($attachment_id, '_airtable_record_id', $record_id);
            update_post_meta($attachment_id, '_airtable_original_url', $url);
            update_post_meta($attachment_id, '_imported_from_airtable', time());

            error_log("HPH: Successfully imported image {$filename} as attachment ID {$attachment_id}");
            return $attachment_id;

        } catch (\Exception $e) {
            error_log("HPH: Failed to import image from {$url}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Find existing attachment by Airtable ID
     */
    private function find_existing_attachment(string $airtable_attachment_id): ?int {
        global $wpdb;
        
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = '_airtable_attachment_id' 
             AND meta_value = %s 
             LIMIT 1",
            $airtable_attachment_id
        ));

        return $attachment_id ? (int) $attachment_id : null;
    }

    /**
     * Sanitize boolean field value
     */
    private function sanitize_boolean_field($value): bool {
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, ['true', '1', 'yes', 'on', 'checked'], true);
        }
        
        if (is_numeric($value)) {
            return (bool) $value;
        }
        
        // Airtable checkboxes return arrays when checked, null when unchecked
        if (is_array($value)) {
            return !empty($value);
        }
        
        return false;
    }

    /**
     * Update ACF fields for a post
     */
    private function update_post_acf_fields(int $post_id, array $validated_data): void {
        foreach ($validated_data as $wp_key => $value) {
            if (function_exists('update_field')) {
                $result = update_field($wp_key, $value, $post_id);
                if (!$result) {
                    error_log("Failed to update ACF field '{$wp_key}' for post {$post_id}");
                }
            } else {
                // Fallback to post meta if ACF not available
                update_post_meta($post_id, $wp_key, $value);
            }
        }
    }

    /**
     * Add validation error to the collection
     */
    private function add_validation_error(string $record_id, string $field, string $message): void {
        if (!isset($this->validation_errors[$record_id])) {
            $this->validation_errors[$record_id] = [];
        }
        $this->validation_errors[$record_id][$field] = $message;
    }

    /**
     * Find existing listing or create new
     */
    private function find_or_create_listing(array $post_data, string $airtable_record_id) {
        // Try to find by Airtable Record ID
        $existing_post_query = new \WP_Query([
            'post_type' => 'listing',
            'meta_key' => '_airtable_record_id',
            'meta_value' => $airtable_record_id,
            'posts_per_page' => 1,
            'post_status' => 'any'
        ]);

        if ($existing_post_query->have_posts()) {
            $existing_post_query->the_post();
            $existing_post_id = get_the_ID();
            wp_reset_postdata();
            
            // Update existing post
            $post_data['ID'] = $existing_post_id;
            $result = wp_update_post($post_data, true);
            
            if (is_wp_error($result)) {
                return $result;
            }
            
            $this->sync_stats['updated']++;
            return $existing_post_id;
        }

        // If no existing post found, create new
        $result = wp_insert_post($post_data, true);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $this->sync_stats['created']++;
        return $result;
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
            $post_id = get_the_ID();
            $listing_data = [];
            
            // Get and validate data for each mapped field
            foreach ($this->field_mapping as $wp_key => $field_config) {
                $airtable_field = $field_config['airtable_field'];
                
                // Special handling for title
                if ($wp_key === 'title') {
                    $value = get_the_title();
                } else {
                    $value = function_exists('get_field') ? get_field($wp_key) : get_post_meta($post_id, $wp_key, true);
                }
                
                // Validate and sanitize for Airtable
                if ($value !== null && $value !== '') {
                    $sanitized_value = $this->sanitize_field_value($value, $field_config, (string) $post_id);
                    if ($sanitized_value !== false && $this->validate_field_constraints($sanitized_value, $field_config)) {
                        $listing_data[$airtable_field] = $sanitized_value;
                    }
                }
            }

            // Only include listings that have at least a title
            if (!empty($listing_data['Property Name'])) {
                // Get Airtable Record ID if exists
                $airtable_record_id = get_post_meta($post_id, '_airtable_record_id', true);

                $listings[] = [
                    'post_id' => $post_id,
                    'airtable_record_id' => $airtable_record_id ?: null,
                    'fields' => $listing_data
                ];
            }
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
            try {
                $record_id = $listing['airtable_record_id'];
                $fields = $listing['fields'];
                $post_id = $listing['post_id'];

                // Skip if no fields to sync
                if (empty($fields)) {
                    $this->sync_stats['skipped']++;
                    continue;
                }

                // If no Airtable record ID, create new record
                if (!$record_id) {
                    $response = $this->make_airtable_request(
                        "{$this->base_id}/{$this->table_name}", 
                        ['fields' => $fields],
                        'POST'
                    );
                    $result = json_decode($response, true);
                    
                    if (!isset($result['id'])) {
                        $this->add_validation_error($post_id, 'airtable_create', 'Failed to create Airtable record');
                        $this->sync_stats['errors']++;
                        continue;
                    }
                    
                    // Store new Airtable Record ID
                    update_post_meta($post_id, '_airtable_record_id', $result['id']);
                    update_post_meta($post_id, '_airtable_last_sync', current_time('mysql'));
                    $this->sync_stats['created']++;
                    
                } else {
                    // Update existing record
                    $this->make_airtable_request(
                        "{$this->base_id}/{$this->table_name}/{$record_id}", 
                        ['fields' => $fields],
                        'PATCH'
                    );
                    update_post_meta($post_id, '_airtable_last_sync', current_time('mysql'));
                    $this->sync_stats['updated']++;
                }

                $processed[] = $post_id;
                
            } catch (\Exception $e) {
                $this->add_validation_error($listing['post_id'], 'airtable_sync', $e->getMessage());
                $this->sync_stats['errors']++;
                continue;
            }
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
    ) {
        $url = $this->base_url . $endpoint;
        
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30
        ];

        if ($method === 'POST' || $method === 'PATCH') {
            $args['body'] = json_encode($params);
        } else if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new \Exception('Network error: ' . $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($code < 200 || $code >= 300) {
            $error_message = "Airtable API error: HTTP $code";
            
            // Try to parse error response for more details
            $error_data = json_decode($body, true);
            if ($error_data && isset($error_data['error'])) {
                $error_message .= ' - ' . ($error_data['error']['message'] ?? $error_data['error']);
            }
            
            throw new \Exception($error_message);
        }

        // For the test_api_connection method, return the full response
        // For other methods, return just the body (maintain backward compatibility)
        if (strpos($endpoint, 'meta/bases') !== false) {
            return $response;
        }

        return $body;
    }

    /**
     * Test API connection
     */
    public function test_api_connection(): array {
        try {
            if (empty($this->access_token)) {
                return [
                    'success' => false,
                    'error' => 'Airtable access token is required',
                    'user_action' => 'Please configure your Airtable access token in Settings'
                ];
            }

            if (empty($this->base_id)) {
                return [
                    'success' => false,
                    'error' => 'Airtable base ID is required',
                    'user_action' => 'Please configure your Airtable base ID in Settings'
                ];
            }

            // Test connection by fetching table schema
            $response = $this->make_airtable_request(
                "meta/bases/{$this->base_id}/tables",
                [],
                'GET'
            );
            
            if (wp_remote_retrieve_response_code($response) === 200) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                
                $tables = $data['tables'] ?? [];
                $table_names = array_column($tables, 'name');
                
                // Check if our target table exists
                $table_exists = in_array($this->table_name, $table_names);
                
                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'tables_found' => count($tables),
                    'target_table_exists' => $table_exists,
                    'target_table' => $this->table_name,
                    'available_tables' => $table_names
                ];
            } else {
                $error_body = json_decode(wp_remote_retrieve_body($response), true);
                return [
                    'success' => false,
                    'error' => $error_body['error']['message'] ?? 'Unknown connection error',
                    'response_code' => wp_remote_retrieve_response_code($response)
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'user_action' => 'Please check your API credentials and try again'
            ];
        }
    }

    /**
     * Get field mapping for display purposes
     */
    public function get_field_mapping(): array {
        return $this->field_mapping;
    }

    /**
     * Get sync statistics
     */
    public function get_sync_stats(): array {
        return $this->sync_stats;
    }

    /**
     * Get validation errors
     */
    public function get_validation_errors(): array {
        return $this->validation_errors;
    }

    /**
     * Get Airtable table structure template
     * This provides the recommended field structure for creating a new Airtable table
     */
    public static function get_table_template(): array {
        return [
            'table_name' => 'Real Estate Listings',
            'description' => 'WordPress Happy Place Plugin compatible real estate listings table',
            'fields' => [
                [
                    'name' => 'Record ID',
                    'type' => 'singleLineText',
                    'description' => 'Unique identifier for WordPress sync',
                    'options' => [
                        'unique' => true
                    ]
                ],
                [
                    'name' => 'Property Name',
                    'type' => 'singleLineText',
                    'description' => 'Property title/name (required)',
                    'required' => true
                ],
                [
                    'name' => 'Street Address',
                    'type' => 'singleLineText',
                    'description' => 'Full street address'
                ],
                [
                    'name' => 'City',
                    'type' => 'singleLineText',
                    'description' => 'City name'
                ],
                [
                    'name' => 'State',
                    'type' => 'singleLineText',
                    'description' => 'State abbreviation (2 characters)'
                ],
                [
                    'name' => 'ZIP Code',
                    'type' => 'singleLineText',
                    'description' => 'ZIP code (format: 12345 or 12345-6789)'
                ],
                [
                    'name' => 'List Price',
                    'type' => 'currency',
                    'description' => 'Property listing price',
                    'options' => [
                        'precision' => 0,
                        'symbol' => '$'
                    ]
                ],
                [
                    'name' => 'Bedrooms',
                    'type' => 'number',
                    'description' => 'Number of bedrooms',
                    'options' => [
                        'precision' => 0
                    ]
                ],
                [
                    'name' => 'Bathrooms',
                    'type' => 'number',
                    'description' => 'Number of bathrooms',
                    'options' => [
                        'precision' => 1
                    ]
                ],
                [
                    'name' => 'Square Footage',
                    'type' => 'number',
                    'description' => 'Total square footage',
                    'options' => [
                        'precision' => 0
                    ]
                ],
                [
                    'name' => 'Lot Size',
                    'type' => 'number',
                    'description' => 'Lot size in square feet',
                    'options' => [
                        'precision' => 0
                    ]
                ],
                [
                    'name' => 'Year Built',
                    'type' => 'number',
                    'description' => 'Year the property was built',
                    'options' => [
                        'precision' => 0
                    ]
                ],
                [
                    'name' => 'Garage Spaces',
                    'type' => 'number',
                    'description' => 'Number of garage spaces',
                    'options' => [
                        'precision' => 0
                    ]
                ],
                [
                    'name' => 'Property Type',
                    'type' => 'singleSelect',
                    'description' => 'Type of property',
                    'options' => [
                        'choices' => [
                            ['name' => 'Single Family'],
                            ['name' => 'Condo'],
                            ['name' => 'Townhouse'],
                            ['name' => 'Multi-Family'],
                            ['name' => 'Land'],
                            ['name' => 'Commercial']
                        ]
                    ]
                ],
                [
                    'name' => 'Listing Status',
                    'type' => 'singleSelect',
                    'description' => 'Current listing status',
                    'options' => [
                        'choices' => [
                            ['name' => 'active'],
                            ['name' => 'pending'],
                            ['name' => 'sold'],
                            ['name' => 'withdrawn'],
                            ['name' => 'expired']
                        ]
                    ]
                ],
                [
                    'name' => 'MLS Number',
                    'type' => 'singleLineText',
                    'description' => 'MLS listing number'
                ],
                [
                    'name' => 'Property Description',
                    'type' => 'multilineText',
                    'description' => 'Detailed property description'
                ],
                [
                    'name' => 'Short Description',
                    'type' => 'multilineText',
                    'description' => 'Brief description for listings and marketing materials'
                ],
                
                // Financial Fields
                [
                    'name' => 'Property Tax Rate',
                    'type' => 'number',
                    'description' => 'Property tax rate as percentage',
                    'options' => [
                        'precision' => 3
                    ]
                ],
                [
                    'name' => 'Annual Property Taxes',
                    'type' => 'currency',
                    'description' => 'Estimated annual property taxes',
                    'options' => [
                        'precision' => 2,
                        'symbol' => '$'
                    ]
                ],
                [
                    'name' => 'Monthly Insurance',
                    'type' => 'currency',
                    'description' => 'Estimated monthly insurance cost',
                    'options' => [
                        'precision' => 2,
                        'symbol' => '$'
                    ]
                ],
                
                // HOA Fields
                [
                    'name' => 'HOA Monthly',
                    'type' => 'currency',
                    'description' => 'Monthly HOA fees',
                    'options' => [
                        'precision' => 2,
                        'symbol' => '$'
                    ]
                ],
                [
                    'name' => 'HOA Quarterly',
                    'type' => 'currency',
                    'description' => 'Quarterly HOA fees',
                    'options' => [
                        'precision' => 2,
                        'symbol' => '$'
                    ]
                ],
                [
                    'name' => 'HOA Annual',
                    'type' => 'currency',
                    'description' => 'Annual HOA fees',
                    'options' => [
                        'precision' => 2,
                        'symbol' => '$'
                    ]
                ],
                
                // Additional Square Footage Fields
                [
                    'name' => 'Living Square Footage',
                    'type' => 'number',
                    'description' => 'Living area square footage',
                    'options' => [
                        'precision' => 0
                    ]
                ],
                [
                    'name' => 'Garage Square Footage',
                    'type' => 'number',
                    'description' => 'Garage square footage',
                    'options' => [
                        'precision' => 0
                    ]
                ],
                [
                    'name' => 'Basement Square Footage',
                    'type' => 'number',
                    'description' => 'Basement square footage',
                    'options' => [
                        'precision' => 0
                    ]
                ],
                
                // Detailed Lot Size
                [
                    'name' => 'Lot Size Sqft',
                    'type' => 'number',
                    'description' => 'Lot size in square feet',
                    'options' => [
                        'precision' => 0
                    ]
                ],
                [
                    'name' => 'Lot Size Acres',
                    'type' => 'number',
                    'description' => 'Lot size in acres',
                    'options' => [
                        'precision' => 3
                    ]
                ],
                
                // Property Features (Boolean)
                [
                    'name' => 'Has Garage',
                    'type' => 'checkbox',
                    'description' => 'Property has garage'
                ],
                [
                    'name' => 'Has Pool',
                    'type' => 'checkbox',
                    'description' => 'Property has pool'
                ],
                [
                    'name' => 'Has Fireplace',
                    'type' => 'checkbox',
                    'description' => 'Property has fireplace'
                ],
                [
                    'name' => 'Has Basement',
                    'type' => 'checkbox',
                    'description' => 'Property has basement'
                ],
                [
                    'name' => 'Has Deck/Patio',
                    'type' => 'checkbox',
                    'description' => 'Property has deck or patio'
                ],
                
                // Financing Fields
                [
                    'name' => 'Down Payment Percentage',
                    'type' => 'number',
                    'description' => 'Estimated down payment percentage',
                    'options' => [
                        'precision' => 1
                    ]
                ],
                [
                    'name' => 'Interest Rate',
                    'type' => 'number',
                    'description' => 'Estimated interest rate',
                    'options' => [
                        'precision' => 2
                    ]
                ],
                [
                    'name' => 'Estimated Monthly Rent',
                    'type' => 'currency',
                    'description' => 'Estimated monthly rental income',
                    'options' => [
                        'precision' => 2,
                        'symbol' => '$'
                    ]
                ],
                
                // Additional Property Details
                [
                    'name' => 'Stories',
                    'type' => 'number',
                    'description' => 'Number of stories/levels',
                    'options' => [
                        'precision' => 0
                    ]
                ],
                [
                    'name' => 'Total Rooms',
                    'type' => 'number',
                    'description' => 'Total number of rooms',
                    'options' => [
                        'precision' => 0
                    ]
                ],
                [
                    'name' => 'Listing Date',
                    'type' => 'date',
                    'description' => 'Date property was listed'
                ],
                [
                    'name' => 'School District',
                    'type' => 'singleLineText',
                    'description' => 'School district name'
                ],
                
                [
                    'name' => 'Latitude',
                    'type' => 'number',
                    'description' => 'Geographic latitude',
                    'options' => [
                        'precision' => 6
                    ]
                ],
                [
                    'name' => 'Longitude',
                    'type' => 'number',
                    'description' => 'Geographic longitude',
                    'options' => [
                        'precision' => 6
                    ]
                ],
                [
                    'name' => 'Created Date',
                    'type' => 'createdTime',
                    'description' => 'When record was created'
                ],
                [
                    'name' => 'Last Modified',
                    'type' => 'lastModifiedTime',
                    'description' => 'When record was last updated'
                ],
                [
                    'name' => 'Main Photo',
                    'type' => 'multipleAttachments',
                    'description' => 'Primary property photo'
                ],
                [
                    'name' => 'Photo Gallery',
                    'type' => 'multipleAttachments',
                    'description' => 'Additional property photos'
                ],
                [
                    'name' => 'Floor Plans',
                    'type' => 'multipleAttachments',
                    'description' => 'Property floor plan images'
                ],
                [
                    'name' => 'Virtual Tour URL',
                    'type' => 'url',
                    'description' => 'Link to virtual tour or 3D walkthrough'
                ],
                [
                    'name' => 'WordPress Post ID',
                    'type' => 'number',
                    'description' => 'WordPress post ID for sync reference',
                    'options' => [
                        'precision' => 0
                    ]
                ],
                [
                    'name' => 'Last Sync',
                    'type' => 'dateTime',
                    'description' => 'Last sync timestamp from WordPress'
                ]
            ]
        ];
    }

    /**
     * Create a new Airtable table with the recommended structure
     * Note: This requires Airtable's Web API v0 which doesn't support table creation
     * This method provides the data structure for manual table creation
     */
    public function create_table_instructions(): array {
        $template = self::get_table_template();
        
        return [
            'instructions' => [
                'step_1' => 'Log into your Airtable account at https://airtable.com',
                'step_2' => 'Create a new base or open an existing base',
                'step_3' => 'Add a new table or rename an existing table to: ' . $template['table_name'],
                'step_4' => 'Configure the fields according to the field_structure below',
                'step_5' => 'Copy your Base ID from the URL (starts with app...)',
                'step_6' => 'Generate a personal access token in your Airtable account settings',
                'step_7' => 'Configure the Happy Place plugin with your Base ID and access token'
            ],
            'field_structure' => $template['fields'],
            'base_url_example' => 'https://airtable.com/app1234567890abcd/tbl1234567890abcd',
            'base_id_location' => 'The Base ID is the part after airtable.com/ that starts with "app"',
            'table_name' => $template['table_name'],
            'compatibility_notes' => [
                'All field names must match exactly for proper sync',
                'Property Name is required and should not be empty',
                'Record ID will be auto-populated by WordPress during sync',
                'Single select options must match the allowed values in the sync'
            ]
        ];
    }

    /**
     * Validate existing Airtable table structure
     */
    public function validate_table_structure(): array {
        try {
            // Get table metadata (this would require Airtable Metadata API)
            // For now, we'll validate by attempting to fetch records and checking field presence
            $response = $this->make_airtable_request($this->base_id . '/' . $this->table_name, ['maxRecords' => 1]);
            $data = json_decode($response, true);
            
            if (!isset($data['records'])) {
                return [
                    'valid' => false,
                    'message' => 'Unable to access table or invalid response'
                ];
            }
            
            $validation_results = [
                'valid' => true,
                'table_accessible' => true,
                'field_validation' => [],
                'missing_fields' => [],
                'extra_fields' => [],
                'recommendations' => []
            ];
            
            // If we have records, check field structure
            if (!empty($data['records'])) {
                $first_record = $data['records'][0];
                $airtable_fields = array_keys($first_record['fields'] ?? []);
                
                // Get expected fields from our mapping
                $expected_fields = array_column($this->field_mapping, 'airtable_field');
                
                // Check for missing fields
                $missing_fields = array_diff($expected_fields, $airtable_fields);
                if (!empty($missing_fields)) {
                    $validation_results['missing_fields'] = $missing_fields;
                    $validation_results['recommendations'][] = 'Add missing fields: ' . implode(', ', $missing_fields);
                }
                
                // Check for extra fields (informational only)
                $extra_fields = array_diff($airtable_fields, $expected_fields);
                if (!empty($extra_fields)) {
                    $validation_results['extra_fields'] = $extra_fields;
                    $validation_results['recommendations'][] = 'Extra fields found (not synced): ' . implode(', ', $extra_fields);
                }
                
                // Validate critical fields
                $required_fields = ['Property Name'];
                $missing_required = array_intersect($required_fields, $missing_fields);
                if (!empty($missing_required)) {
                    $validation_results['valid'] = false;
                    $validation_results['recommendations'][] = 'Required fields missing: ' . implode(', ', $missing_required);
                }
            } else {
                $validation_results['recommendations'][] = 'Table is empty - add sample data to validate field structure';
            }
            
            return $validation_results;
            
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'table_accessible' => false,
                'error' => $e->getMessage(),
                'recommendations' => [
                    'Check your access token and permissions',
                    'Verify the Base ID and table name are correct',
                    'Ensure the table exists in your Airtable base'
                ]
            ];
        }
    }
}

/**
 * Usage example - Trigger Airtable sync manually or via cron
 * 
 * @param string $base_id The Airtable base ID
 * @param string $table_name The table name in Airtable
 * @return array Sync results
 */
function hph_trigger_airtable_sync(string $base_id = '', string $table_name = 'Listings'): array {
    try {
        // Get base ID from settings if not provided
        if (empty($base_id)) {
            $integration_options = get_option('happy_place_integrations', []);
            $base_id = $integration_options['airtable']['base_id'] ?? '';
            
            if (empty($base_id)) {
                throw new \Exception('Airtable base ID not configured');
            }
        }
        
        $sync = new \HappyPlace\Integrations\Airtable_Two_Way_Sync($base_id, $table_name);

        // Test connection first
        $connection_test = $sync->test_api_connection();
        if (!$connection_test['success']) {
            return [
                'success' => false,
                'error' => 'Connection test failed: ' . $connection_test['message']
            ];
        }

        // Sync from Airtable to WordPress
        $airtable_to_wp_result = $sync->sync_airtable_to_wordpress();

        // Sync from WordPress to Airtable
        $wp_to_airtable_result = $sync->sync_wordpress_to_airtable();

        $results = [
            'success' => true,
            'airtable_to_wp' => $airtable_to_wp_result,
            'wp_to_airtable' => $wp_to_airtable_result,
            'timestamp' => current_time('mysql'),
            'connection_test' => $connection_test
        ];

        // Log detailed results
        error_log('Airtable Sync Results: ' . wp_json_encode($results, JSON_PRETTY_PRINT));
        
        return $results;
        
    } catch (\Exception $e) {
        $error_result = [
            'success' => false,
            'error' => $e->getMessage(),
            'timestamp' => current_time('mysql')
        ];
        
        error_log('Airtable Sync Error: ' . wp_json_encode($error_result, JSON_PRETTY_PRINT));
        return $error_result;
    }
}

/**
 * AJAX handler for manual Airtable sync from admin
 */
function hph_ajax_airtable_sync() {
    // Verify nonce and permissions
    if (!check_ajax_referer('happy_place_admin_nonce', 'nonce', false) || !current_user_can('manage_options')) {
        wp_die(wp_json_encode(['success' => false, 'error' => 'Unauthorized']), 403);
    }
    
    $base_id = sanitize_text_field($_POST['base_id'] ?? '');
    $table_name = sanitize_text_field($_POST['table_name'] ?? 'Listings');
    
    $results = hph_trigger_airtable_sync($base_id, $table_name);
    
    wp_send_json($results);
}
add_action('wp_ajax_hph_airtable_sync', 'hph_ajax_airtable_sync');

/**
 * Test Airtable connection via AJAX
 */
function hph_ajax_test_airtable_connection() {
    if (!check_ajax_referer('happy_place_admin_nonce', 'nonce', false) || !current_user_can('manage_options')) {
        wp_die(wp_json_encode(['success' => false, 'error' => 'Unauthorized']), 403);
    }
    
    try {
        $base_id = sanitize_text_field($_POST['base_id'] ?? '');
        $table_name = sanitize_text_field($_POST['table_name'] ?? 'Listings');
        
        if (empty($base_id)) {
            wp_send_json(['success' => false, 'message' => 'Base ID is required']);
            return;
        }
        
        $sync = new \HappyPlace\Integrations\Airtable_Two_Way_Sync($base_id, $table_name);
        $result = $sync->test_api_connection();
        
        wp_send_json($result);
        
    } catch (\Exception $e) {
        wp_send_json([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
add_action('wp_ajax_hph_test_airtable_connection', 'hph_ajax_test_airtable_connection');

/**
 * AJAX handler to get Airtable table creation template
 */
function hph_ajax_get_airtable_template() {
    if (!check_ajax_referer('happy_place_admin_nonce', 'nonce', false) || !current_user_can('manage_options')) {
        wp_die(wp_json_encode(['success' => false, 'error' => 'Unauthorized']), 403);
    }
    
    $template = \HappyPlace\Integrations\Airtable_Two_Way_Sync::get_table_template();
    
    wp_send_json([
        'success' => true,
        'template' => $template,
        'instructions' => [
            'Manual Setup Instructions:',
            '1. Go to https://airtable.com and create a new base',
            '2. Create a table with the name: ' . $template['table_name'],
            '3. Add fields according to the template structure provided',
            '4. Copy your Base ID from the Airtable URL',
            '5. Generate a personal access token in your account settings',
            '6. Configure these in Happy Place → Integrations'
        ]
    ]);
}
add_action('wp_ajax_hph_get_airtable_template', 'hph_ajax_get_airtable_template');

/**
 * AJAX handler to validate Airtable table structure
 */
function hph_ajax_validate_airtable_table() {
    if (!check_ajax_referer('happy_place_admin_nonce', 'nonce', false) || !current_user_can('manage_options')) {
        wp_die(wp_json_encode(['success' => false, 'error' => 'Unauthorized']), 403);
    }
    
    try {
        $base_id = sanitize_text_field($_POST['base_id'] ?? '');
        $table_name = sanitize_text_field($_POST['table_name'] ?? 'Listings');
        
        if (empty($base_id)) {
            wp_send_json(['success' => false, 'message' => 'Base ID is required']);
            return;
        }
        
        $sync = new \HappyPlace\Integrations\Airtable_Two_Way_Sync($base_id, $table_name);
        $validation = $sync->validate_table_structure();
        
        wp_send_json([
            'success' => true,
            'validation' => $validation
        ]);
        
    } catch (\Exception $e) {
        wp_send_json([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
add_action('wp_ajax_hph_validate_airtable_table', 'hph_ajax_validate_airtable_table');

/**
 * AJAX handler to download Airtable template
 */
function hph_ajax_download_airtable_template() {
    if (!check_ajax_referer('happy_place_admin_nonce', 'nonce', false) || !current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    require_once plugin_dir_path(__FILE__) . 'class-airtable-setup-helper.php';
    \HappyPlace\Integrations\Airtable_Setup_Helper::download_template();
}
add_action('wp_ajax_hph_download_airtable_template', 'hph_ajax_download_airtable_template');

// Example scheduled sync - runs twice daily
// add_action('happy_place_airtable_sync_cron', 'hph_trigger_airtable_sync');