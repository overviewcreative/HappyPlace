<?php
/**
 * Enhanced Airtable Two-Way Sync Engine
 * 
 * Complete rebuild with smart field classification, calculated field intelligence,
 * two-way media sync, and delta sync capabilities.
 * 
 * @package HappyPlace
 * @since 5.0.0
 */

namespace HappyPlace\Integrations;

use HappyPlace\Fields\Listing_Calculator;

class Enhanced_Airtable_Sync {
    
    private string $base_url = 'https://api.airtable.com/v0/';
    private string $access_token;
    private string $base_id;
    private string $table_name;
    private int $batch_size = 50;
    
    // Media sync manager
    private Media_Sync_Manager $media_manager;
    
    // Smart field classification system
    private array $field_categories = [
        'manual_sync' => [],
        'calculated_wp' => [],
        'calculated_airtable' => [],
        'media_sync' => [],
        'readonly' => []
    ];
    
    // Delta sync tracking
    private array $change_tracking = [
        'wp_last_modified' => [],
        'airtable_last_modified' => [],
        'field_checksums' => []
    ];
    
    // Sync statistics
    private array $sync_stats = [
        'total_processed' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
        'media_synced' => 0,
        'calculations_triggered' => 0
    ];
    
    // Enhanced field mapping with Phase 2+ support
    private array $enhanced_field_mapping = [];
    
    /**
     * Constructor
     */
    public function __construct(string $base_id, string $table_name = 'Listings', string $access_token = '') {
        $this->base_id = $base_id;
        $this->table_name = $table_name;
        $this->access_token = $access_token ?: $this->get_api_key();
        $this->media_manager = new Media_Sync_Manager();
        
        $this->initialize_enhanced_field_mapping();
        $this->categorize_fields();
    }
    
    /**
     * Initialize enhanced field mapping with complete Phase 2+ support
     */
    private function initialize_enhanced_field_mapping(): void {
        $this->enhanced_field_mapping = [
            
            // ============================================================================
            // GROUP 1: ESSENTIAL LISTING INFORMATION
            // ============================================================================
            
            // Core Identifiers
            'mls_number' => [
                'airtable_field' => 'MLS Number',
                'wp_field' => 'mls_number',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'string',
                'sanitize' => 'sanitize_text_field'
            ],
            'list_date' => [
                'airtable_field' => 'List Date',
                'wp_field' => 'list_date',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'date',
                'triggers_calculation' => ['days_on_market'],
                'sanitize' => 'sanitize_text_field'
            ],
            'listing_status' => [
                'airtable_field' => 'Listing Status',
                'wp_field' => 'listing_status',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'select',
                'allowed_values' => ['Active', 'Pending', 'Sold', 'Expired', 'Withdrawn'],
                'triggers_calculation' => ['status_change_date'],
                'sanitize' => 'sanitize_text_field'
            ],
            'expiration_date' => [
                'airtable_field' => 'Expiration Date',
                'wp_field' => 'expiration_date',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'date',
                'sanitize' => 'sanitize_text_field'
            ],
            
            // Pricing & Market Position
            'price' => [
                'airtable_field' => 'Current Price',
                'wp_field' => 'price',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'number',
                'triggers_calculation' => ['price_per_sqft', 'price_change_count'],
                'sanitize' => 'floatval',
                'min' => 0,
                'max' => 50000000
            ],
            'original_price' => [
                'airtable_field' => 'Original Price',
                'wp_field' => 'original_price',
                'type' => 'calculated_wp',
                'sync_direction' => 'wp_to_airtable_only',
                'data_type' => 'number',
                'note' => 'Auto-set on first save, never changes',
                'sanitize' => 'floatval'
            ],
            'price_per_sqft' => [
                'airtable_field' => 'Price Per SqFt',
                'wp_field' => 'price_per_sqft',
                'type' => 'calculated_wp',
                'sync_direction' => 'wp_to_airtable_only',
                'data_type' => 'number',
                'calculation' => 'price ÷ square_footage',
                'sanitize' => 'floatval'
            ],
            'days_on_market' => [
                'airtable_field' => 'Days on Market',
                'wp_field' => 'days_on_market',
                'type' => 'calculated_wp',
                'sync_direction' => 'wp_to_airtable_only',
                'data_type' => 'number',
                'calculation' => 'today - list_date',
                'sanitize' => 'intval'
            ],
            'status_change_date' => [
                'airtable_field' => 'Status Change Date',
                'wp_field' => 'status_change_date',
                'type' => 'calculated_wp',
                'sync_direction' => 'wp_to_airtable_only',
                'data_type' => 'date',
                'auto_update' => 'when listing_status changes',
                'sanitize' => 'sanitize_text_field'
            ],
            'price_change_count' => [
                'airtable_field' => 'Price Changes',
                'wp_field' => 'price_change_count',
                'type' => 'calculated_wp',
                'sync_direction' => 'wp_to_airtable_only',
                'data_type' => 'number',
                'auto_track' => 'number of price modifications',
                'sanitize' => 'intval'
            ],
            
            // Agreement Details
            'listing_agreement_type' => [
                'airtable_field' => 'Agreement Type',
                'wp_field' => 'listing_agreement_type',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'select',
                'allowed_values' => ['Exclusive Right', 'Exclusive Agency', 'Open Listing'],
                'sanitize' => 'sanitize_text_field'
            ],
            'listing_service_level' => [
                'airtable_field' => 'Service Level',
                'wp_field' => 'listing_service_level',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'select',
                'allowed_values' => ['Full Service', 'Limited Service', 'Flat Fee'],
                'sanitize' => 'sanitize_text_field'
            ],
            
            // ============================================================================
            // GROUP 2: PROPERTY DETAILS & CLASSIFICATION
            // ============================================================================
            
            // Property Classification
            'property_type' => [
                'airtable_field' => 'Property Type',
                'wp_field' => 'property_type',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'select',
                'allowed_values' => ['Single Family Home', 'Townhouse', 'Condo', 'Multi-Family', 'Land', 'Commercial'],
                'sanitize' => 'sanitize_text_field'
            ],
            'property_style' => [
                'airtable_field' => 'Property Style',
                'wp_field' => 'property_style',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'select',
                'sanitize' => 'sanitize_text_field'
            ],
            'year_built' => [
                'airtable_field' => 'Year Built',
                'wp_field' => 'year_built',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'number',
                'sanitize' => 'intval',
                'min' => 1800,
                'max' => 2030
            ],
            'property_condition' => [
                'airtable_field' => 'Property Condition',
                'wp_field' => 'property_condition',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'select',
                'allowed_values' => ['Excellent', 'Good', 'Fair', 'Poor'],
                'sanitize' => 'sanitize_text_field'
            ],
            
            // Size & Space
            'square_footage' => [
                'airtable_field' => 'Square Footage',
                'wp_field' => 'square_footage',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'number',
                'triggers_calculation' => ['price_per_sqft'],
                'sanitize' => 'intval',
                'min' => 0,
                'max' => 50000
            ],
            'living_area' => [
                'airtable_field' => 'Living Area',
                'wp_field' => 'living_area',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'number',
                'sanitize' => 'intval'
            ],
            'lot_size' => [
                'airtable_field' => 'Lot Size (Acres)',
                'wp_field' => 'lot_size',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'number',
                'triggers_calculation' => ['lot_sqft'],
                'sanitize' => 'floatval',
                'min' => 0
            ],
            'lot_sqft' => [
                'airtable_field' => 'Lot Size (SqFt)',
                'wp_field' => 'lot_sqft',
                'type' => 'calculated_wp',
                'sync_direction' => 'wp_to_airtable_only',
                'data_type' => 'number',
                'calculation' => 'lot_size × 43,560',
                'sanitize' => 'intval'
            ],
            'sqft_source' => [
                'airtable_field' => 'SqFt Source',
                'wp_field' => 'sqft_source',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'select',
                'allowed_values' => ['Tax Assessor', 'Builder', 'Owner', 'Appraiser', 'Public Records'],
                'sanitize' => 'sanitize_text_field'
            ],
            'stories' => [
                'airtable_field' => 'Stories',
                'wp_field' => 'stories',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'number',
                'sanitize' => 'intval',
                'min' => 1,
                'max' => 10
            ],
            
            // Room Counts
            'bedrooms' => [
                'airtable_field' => 'Bedrooms',
                'wp_field' => 'bedrooms',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'number',
                'sanitize' => 'intval',
                'min' => 0,
                'max' => 20
            ],
            'bathrooms_full' => [
                'airtable_field' => 'Full Bathrooms',
                'wp_field' => 'bathrooms_full',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'number',
                'triggers_calculation' => ['bathrooms_total'],
                'sanitize' => 'intval',
                'min' => 0,
                'max' => 20
            ],
            'bathrooms_half' => [
                'airtable_field' => 'Half Bathrooms',
                'wp_field' => 'bathrooms_half',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'number',
                'triggers_calculation' => ['bathrooms_total'],
                'sanitize' => 'intval',
                'min' => 0,
                'max' => 20
            ],
            'bathrooms_total' => [
                'airtable_field' => 'Total Bathrooms',
                'wp_field' => 'bathrooms_total',
                'type' => 'calculated_wp',
                'sync_direction' => 'wp_to_airtable_only',
                'data_type' => 'number',
                'calculation' => 'bathrooms_full + (bathrooms_half × 0.5)',
                'sanitize' => 'floatval'
            ],
            'rooms_total' => [
                'airtable_field' => 'Total Rooms',
                'wp_field' => 'rooms_total',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'number',
                'sanitize' => 'intval',
                'min' => 0,
                'max' => 50
            ],
            'parking_spaces' => [
                'airtable_field' => 'Parking Spaces',
                'wp_field' => 'parking_spaces',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'number',
                'sanitize' => 'intval',
                'min' => 0,
                'max' => 20
            ],
            
            // Additional Features
            'garage_spaces' => [
                'airtable_field' => 'Garage Spaces',
                'wp_field' => 'garage_spaces',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'number',
                'sanitize' => 'intval',
                'min' => 0,
                'max' => 10
            ],
            'basement' => [
                'airtable_field' => 'Basement',
                'wp_field' => 'basement',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'select',
                'allowed_values' => ['None', 'Partial', 'Full', 'Finished'],
                'sanitize' => 'sanitize_text_field'
            ],
            'fireplace_count' => [
                'airtable_field' => 'Fireplaces',
                'wp_field' => 'fireplace_count',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'number',
                'sanitize' => 'intval',
                'min' => 0,
                'max' => 10
            ],
            'pool' => [
                'airtable_field' => 'Has Pool',
                'wp_field' => 'pool',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'boolean',
                'sanitize' => 'boolean_field'
            ],
            'hot_tub_spa' => [
                'airtable_field' => 'Hot Tub/Spa',
                'wp_field' => 'hot_tub_spa',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'boolean',
                'sanitize' => 'boolean_field'
            ],
            'waterfront' => [
                'airtable_field' => 'Waterfront',
                'wp_field' => 'waterfront',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'boolean',
                'sanitize' => 'boolean_field'
            ],
            
            // ============================================================================
            // GROUP 3: LOCATION & ADDRESS INTELLIGENCE
            // ============================================================================
            
            // Address Entry
            'street_address' => [
                'airtable_field' => 'Street Address',
                'wp_field' => 'street_address',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'string',
                'triggers_parsing' => true,
                'triggers_geocoding' => true,
                'sanitize' => 'sanitize_text_field'
            ],
            'unit_number' => [
                'airtable_field' => 'Unit Number',
                'wp_field' => 'unit_number',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'string',
                'sanitize' => 'sanitize_text_field'
            ],
            'city' => [
                'airtable_field' => 'City',
                'wp_field' => 'city',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'string',
                'triggers_geocoding' => true,
                'sanitize' => 'sanitize_text_field'
            ],
            'state' => [
                'airtable_field' => 'State',
                'wp_field' => 'state',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'select',
                'allowed_values' => ['DE', 'MD', 'PA', 'NJ', 'VA', 'DC'],
                'sanitize' => 'sanitize_text_field'
            ],
            'zip_code' => [
                'airtable_field' => 'ZIP Code',
                'wp_field' => 'zip_code',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'string',
                'triggers_calculation' => ['county'],
                'triggers_geocoding' => true,
                'sanitize' => 'sanitize_text_field',
                'pattern' => '/^\d{5}(-\d{4})?$/'
            ],
            'county' => [
                'airtable_field' => 'County',
                'wp_field' => 'county',
                'type' => 'calculated_wp',
                'sync_direction' => 'wp_to_airtable_only',
                'data_type' => 'string',
                'auto_populate' => 'from ZIP code lookup',
                'sanitize' => 'sanitize_text_field'
            ]
        ];
        
        // Add address components (auto-generated from parsing)
        $this->add_address_component_fields();
        
        // Add geographic intelligence fields
        $this->add_geographic_intelligence_fields();
        
        // Add media fields
        $this->add_media_fields();
        
        // Add relationship fields
        $this->add_relationship_fields();
    }
    
    /**
     * Add address component fields (auto-parsed)
     */
    private function add_address_component_fields(): void {
        $address_components = [
            'street_number' => 'Street Number',
            'street_dir_prefix' => 'Street Direction Prefix',
            'street_name' => 'Street Name',
            'street_suffix' => 'Street Suffix',
            'street_dir_suffix' => 'Street Direction Suffix'
        ];
        
        foreach ($address_components as $wp_field => $airtable_field) {
            $this->enhanced_field_mapping[$wp_field] = [
                'airtable_field' => $airtable_field,
                'wp_field' => $wp_field,
                'type' => 'calculated_wp',
                'sync_direction' => 'wp_to_airtable_only',
                'data_type' => 'string',
                'auto_parse' => 'from street_address',
                'sanitize' => 'sanitize_text_field'
            ];
        }
    }
    
    /**
     * Add geographic intelligence fields
     */
    private function add_geographic_intelligence_fields(): void {
        $geographic_fields = [
            'latitude' => [
                'airtable_field' => 'Latitude',
                'wp_field' => 'latitude',
                'type' => 'calculated_wp',
                'sync_direction' => 'wp_to_airtable_only',
                'data_type' => 'number',
                'auto_populate' => 'Google Maps geocoding',
                'sanitize' => 'floatval',
                'min' => -90,
                'max' => 90
            ],
            'longitude' => [
                'airtable_field' => 'Longitude',
                'wp_field' => 'longitude',
                'type' => 'calculated_wp',
                'sync_direction' => 'wp_to_airtable_only',
                'data_type' => 'number',
                'auto_populate' => 'Google Maps geocoding',
                'sanitize' => 'floatval',
                'min' => -180,
                'max' => 180
            ],
            'walkability_score' => [
                'airtable_field' => 'Walkability Score',
                'wp_field' => 'walkability_score',
                'type' => 'calculated_wp',
                'sync_direction' => 'wp_to_airtable_only',
                'data_type' => 'number',
                'auto_calc' => 'Walk Score API or estimation',
                'sanitize' => 'intval',
                'min' => 0,
                'max' => 100
            ],
            'geocoding_accuracy' => [
                'airtable_field' => 'Geocoding Accuracy',
                'wp_field' => 'geocoding_accuracy',
                'type' => 'calculated_wp',
                'sync_direction' => 'wp_to_airtable_only',
                'data_type' => 'string',
                'sanitize' => 'sanitize_text_field'
            ],
            'geocoding_source' => [
                'airtable_field' => 'Geocoding Source',
                'wp_field' => 'geocoding_source',
                'type' => 'calculated_wp',
                'sync_direction' => 'wp_to_airtable_only',
                'data_type' => 'string',
                'sanitize' => 'sanitize_text_field'
            ],
            'parcel_number' => [
                'airtable_field' => 'Parcel Number',
                'wp_field' => 'parcel_number',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'string',
                'sanitize' => 'sanitize_text_field'
            ]
        ];
        
        $this->enhanced_field_mapping = array_merge($this->enhanced_field_mapping, $geographic_fields);
    }
    
    /**
     * Add media fields with enhanced support
     */
    private function add_media_fields(): void {
        $media_fields = [
            'featured_photo' => [
                'airtable_field' => 'Featured Photo',
                'wp_field' => 'featured_photo',
                'type' => 'media',
                'sync_direction' => 'bidirectional',
                'data_type' => 'attachment',
                'media_type' => 'image',
                'max_files' => 1
            ],
            'listing_photos' => [
                'airtable_field' => 'Listing Photos',
                'wp_field' => 'listing_photos',
                'type' => 'media',
                'sync_direction' => 'bidirectional',
                'data_type' => 'attachment_multiple',
                'media_type' => 'image',
                'max_files' => 50,
                'maintain_order' => true
            ],
            'floor_plan_images' => [
                'airtable_field' => 'Floor Plans',
                'wp_field' => 'floor_plan_images',
                'type' => 'media',
                'sync_direction' => 'bidirectional',
                'data_type' => 'attachment_multiple',
                'media_type' => 'image',
                'max_files' => 10
            ],
            'virtual_tour_url' => [
                'airtable_field' => 'Virtual Tour URL',
                'wp_field' => 'virtual_tour_url',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'url',
                'sanitize' => 'esc_url_raw'
            ],
            'video_tour_url' => [
                'airtable_field' => 'Video Tour URL',
                'wp_field' => 'video_tour_url',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'url',
                'sanitize' => 'esc_url_raw'
            ],
            'photo_count' => [
                'airtable_field' => 'Photo Count',
                'wp_field' => 'photo_count',
                'type' => 'calculated_wp',
                'sync_direction' => 'wp_to_airtable_only',
                'data_type' => 'number',
                'auto_count' => 'total photos',
                'sanitize' => 'intval'
            ]
        ];
        
        $this->enhanced_field_mapping = array_merge($this->enhanced_field_mapping, $media_fields);
    }
    
    /**
     * Add relationship fields
     */
    private function add_relationship_fields(): void {
        $relationship_fields = [
            'neighborhood' => [
                'airtable_field' => 'Neighborhood',
                'wp_field' => 'neighborhood',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'string',
                'sanitize' => 'sanitize_text_field'
            ],
            'school_district' => [
                'airtable_field' => 'School District',
                'wp_field' => 'school_district',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'string',
                'sanitize' => 'sanitize_text_field'
            ],
            'mls_area_code' => [
                'airtable_field' => 'MLS Area Code',
                'wp_field' => 'mls_area_code',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'string',
                'sanitize' => 'sanitize_text_field'
            ],
            'zoning' => [
                'airtable_field' => 'Zoning',
                'wp_field' => 'zoning',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'string',
                'sanitize' => 'sanitize_text_field'
            ],
            'flood_zone' => [
                'airtable_field' => 'Flood Zone',
                'wp_field' => 'flood_zone',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'string',
                'sanitize' => 'sanitize_text_field'
            ],
            'hoa_name' => [
                'airtable_field' => 'HOA Name',
                'wp_field' => 'hoa_name',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'string',
                'sanitize' => 'sanitize_text_field'
            ],
            'address_visibility' => [
                'airtable_field' => 'Address Visibility',
                'wp_field' => 'address_visibility',
                'type' => 'manual',
                'sync_direction' => 'bidirectional',
                'data_type' => 'select',
                'allowed_values' => ['full', 'street_only', 'neighborhood', 'city_only', 'hidden'],
                'sanitize' => 'sanitize_text_field'
            ]
        ];
        
        $this->enhanced_field_mapping = array_merge($this->enhanced_field_mapping, $relationship_fields);
    }
    
    /**
     * Categorize fields by sync behavior
     */
    private function categorize_fields(): void {
        foreach ($this->enhanced_field_mapping as $field_key => $config) {
            $type = $config['type'] ?? 'manual';
            
            switch ($type) {
                case 'manual':
                    $this->field_categories['manual_sync'][] = $field_key;
                    break;
                case 'calculated_wp':
                    $this->field_categories['calculated_wp'][] = $field_key;
                    break;
                case 'calculated_airtable':
                    $this->field_categories['calculated_airtable'][] = $field_key;
                    break;
                case 'media':
                    $this->field_categories['media_sync'][] = $field_key;
                    break;
                case 'readonly':
                    $this->field_categories['readonly'][] = $field_key;
                    break;
            }
        }
    }
    
    /**
     * Get API key from settings
     */
    private function get_api_key(): string {
        $options = get_option('happy_place_settings', []);
        return $options['airtable']['api_key'] ?? '';
    }
    
    /**
     * Enhanced sync from Airtable to WordPress with calculated field intelligence
     */
    public function sync_airtable_to_wordpress(): array {
        try {
            $this->reset_sync_stats();
            
            error_log("HPH Enhanced Sync: Starting Airtable to WordPress sync");
            
            // Get changed records since last sync (delta sync)
            $airtable_records = $this->fetch_changed_airtable_records();
            
            if (empty($airtable_records)) {
                return [
                    'success' => true,
                    'message' => 'No changes detected in Airtable since last sync',
                    'stats' => $this->sync_stats
                ];
            }
            
            foreach ($airtable_records as $record) {
                $this->process_airtable_record_enhanced($record);
            }
            
            return [
                'success' => true,
                'total_records' => count($airtable_records),
                'stats' => $this->sync_stats,
                'message' => sprintf(
                    'Enhanced sync completed: %d processed, %d created, %d updated, %d calculations triggered',
                    $this->sync_stats['total_processed'],
                    $this->sync_stats['created'],
                    $this->sync_stats['updated'],
                    $this->sync_stats['calculations_triggered']
                )
            ];
            
        } catch (\Exception $e) {
            error_log('HPH Enhanced Sync Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'stats' => $this->sync_stats
            ];
        }
    }
    
    /**
     * Enhanced sync from WordPress to Airtable
     */
    public function sync_wordpress_to_airtable(): array {
        try {
            $this->reset_sync_stats();
            
            error_log("HPH Enhanced Sync: Starting WordPress to Airtable sync");
            
            // Get changed WordPress listings since last sync
            $wp_listings = $this->fetch_changed_wp_listings();
            
            if (empty($wp_listings)) {
                return [
                    'success' => true,
                    'message' => 'No changes detected in WordPress since last sync',
                    'stats' => $this->sync_stats
                ];
            }
            
            foreach ($wp_listings as $listing) {
                $this->process_wp_listing_enhanced($listing);
            }
            
            return [
                'success' => true,
                'total_listings' => count($wp_listings),
                'stats' => $this->sync_stats,
                'message' => sprintf(
                    'Enhanced sync completed: %d processed, %d updated in Airtable, %d media files synced',
                    $this->sync_stats['total_processed'],
                    $this->sync_stats['updated'],
                    $this->sync_stats['media_synced']
                )
            ];
            
        } catch (\Exception $e) {
            error_log('HPH Enhanced Sync Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'stats' => $this->sync_stats
            ];
        }
    }
    
    /**
     * Process Airtable record with enhanced field intelligence
     */
    private function process_airtable_record_enhanced(array $record): void {
        $this->sync_stats['total_processed']++;
        
        $airtable_id = $record['id'];
        $fields = $record['fields'] ?? [];
        
        // Find or create WordPress listing
        $listing_id = $this->find_or_create_wp_listing($airtable_id, $fields);
        
        if (!$listing_id) {
            $this->sync_stats['errors']++;
            return;
        }
        
        $changes_made = false;
        $triggered_calculations = [];
        
        // Process manual sync fields only (skip calculated fields)
        foreach ($this->field_categories['manual_sync'] as $field_key) {
            $field_config = $this->enhanced_field_mapping[$field_key];
            $airtable_field = $field_config['airtable_field'];
            
            if (!isset($fields[$airtable_field])) {
                continue;
            }
            
            $airtable_value = $fields[$airtable_field];
            $wp_field = $field_config['wp_field'];
            
            // Process and sanitize value
            $processed_value = $this->process_field_value($airtable_value, $field_config);
            
            // Update WordPress field
            $current_value = get_field($wp_field, $listing_id);
            
            if ($current_value !== $processed_value) {
                update_field($wp_field, $processed_value, $listing_id);
                $changes_made = true;
                
                // Track calculation triggers
                if (!empty($field_config['triggers_calculation'])) {
                    $triggered_calculations = array_merge(
                        $triggered_calculations,
                        $field_config['triggers_calculation']
                    );
                }
            }
        }
        
        // Process media fields
        foreach ($this->field_categories['media_sync'] as $field_key) {
            $field_config = $this->enhanced_field_mapping[$field_key];
            $airtable_field = $field_config['airtable_field'];
            
            if (isset($fields[$airtable_field])) {
                $this->process_media_field($fields[$airtable_field], $field_config, $listing_id);
                $this->sync_stats['media_synced']++;
            }
        }
        
        // Trigger calculations if needed
        if (!empty($triggered_calculations)) {
            $this->trigger_wp_calculations($listing_id, array_unique($triggered_calculations));
            $this->sync_stats['calculations_triggered'] += count(array_unique($triggered_calculations));
        }
        
        // Sync calculated fields back to Airtable
        $this->sync_calculated_fields_to_airtable($listing_id, $airtable_id);
        
        if ($changes_made) {
            $this->sync_stats['updated']++;
        }
        
        // Update sync timestamp
        $this->update_sync_timestamp($listing_id, 'airtable_to_wp');
    }
    
    /**
     * Process WordPress listing for sync to Airtable
     */
    private function process_wp_listing_enhanced(\WP_Post $listing): void {
        $this->sync_stats['total_processed']++;
        
        $listing_id = $listing->ID;
        
        // Get Airtable record ID
        $airtable_record_id = get_post_meta($listing_id, '_airtable_record_id', true);
        
        if (!$airtable_record_id) {
            // Create new Airtable record
            $airtable_record_id = $this->create_airtable_record($listing_id);
            if (!$airtable_record_id) {
                $this->sync_stats['errors']++;
                return;
            }
            $this->sync_stats['created']++;
        }
        
        // Prepare update data
        $update_data = [];
        
        // Include manual sync fields
        foreach ($this->field_categories['manual_sync'] as $field_key) {
            $field_config = $this->enhanced_field_mapping[$field_key];
            $wp_value = get_field($field_config['wp_field'], $listing_id);
            
            if ($wp_value !== null && $wp_value !== '') {
                $update_data[$field_config['airtable_field']] = $this->format_value_for_airtable($wp_value, $field_config);
            }
        }
        
        // Include calculated fields (WordPress is source of truth)
        foreach ($this->field_categories['calculated_wp'] as $field_key) {
            $field_config = $this->enhanced_field_mapping[$field_key];
            $wp_value = get_field($field_config['wp_field'], $listing_id);
            
            if ($wp_value !== null && $wp_value !== '') {
                $update_data[$field_config['airtable_field']] = $this->format_value_for_airtable($wp_value, $field_config);
            }
        }
        
        // Process media fields for upload to Airtable
        foreach ($this->field_categories['media_sync'] as $field_key) {
            $field_config = $this->enhanced_field_mapping[$field_key];
            $wp_media = get_field($field_config['wp_field'], $listing_id);
            
            if (!empty($wp_media)) {
                $airtable_media = $this->prepare_media_for_airtable($wp_media, $field_config);
                if (!empty($airtable_media)) {
                    $update_data[$field_config['airtable_field']] = $airtable_media;
                    $this->sync_stats['media_synced']++;
                }
            }
        }
        
        // Update Airtable record
        if (!empty($update_data)) {
            $success = $this->update_airtable_record($airtable_record_id, $update_data);
            if ($success) {
                $this->sync_stats['updated']++;
            } else {
                $this->sync_stats['errors']++;
            }
        }
        
        // Update sync timestamp
        $this->update_sync_timestamp($listing_id, 'wp_to_airtable');
    }
    
    /**
     * Trigger WordPress calculations for specific fields
     */
    private function trigger_wp_calculations(int $listing_id, array $calculation_fields): void {
        if (class_exists('\\HappyPlace\\Fields\\Listing_Calculator')) {
            $calculator = new Listing_Calculator();
            
            foreach ($calculation_fields as $calc_field) {
                switch ($calc_field) {
                    case 'price_per_sqft':
                        $calculator->calculate_price_per_sqft($listing_id);
                        break;
                    case 'days_on_market':
                        $calculator->calculate_days_on_market($listing_id);
                        break;
                    case 'bathrooms_total':
                        $calculator->calculate_total_bathrooms($listing_id);
                        break;
                    case 'lot_sqft':
                        $calculator->calculate_lot_sqft($listing_id);
                        break;
                    case 'status_change_date':
                        $calculator->update_status_change_date($listing_id);
                        break;
                    case 'price_change_count':
                        $calculator->track_price_change($listing_id);
                        break;
                }
            }
        }
    }
    
    /**
     * Sync calculated fields back to Airtable
     */
    private function sync_calculated_fields_to_airtable(int $listing_id, string $airtable_id): void {
        $calculated_data = [];
        
        foreach ($this->field_categories['calculated_wp'] as $field_key) {
            $field_config = $this->enhanced_field_mapping[$field_key];
            $wp_value = get_field($field_config['wp_field'], $listing_id);
            
            if ($wp_value !== null && $wp_value !== '') {
                $calculated_data[$field_config['airtable_field']] = $this->format_value_for_airtable($wp_value, $field_config);
            }
        }
        
        if (!empty($calculated_data)) {
            $this->update_airtable_record($airtable_id, $calculated_data);
        }
    }
    
    /**
     * Fetch changed Airtable records (delta sync)
     */
    private function fetch_changed_airtable_records(): array {
        $last_sync = get_option('hph_last_airtable_sync', '');
        
        $url = "{$this->base_url}{$this->base_id}/{$this->table_name}";
        
        // Add filter for last modified if we have a timestamp
        if ($last_sync) {
            $filter = "LAST_MODIFIED_TIME() > '{$last_sync}'";
            $url .= '?filterByFormula=' . urlencode($filter);
        }
        
        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            throw new \Exception('Failed to fetch Airtable records: ' . $response->get_error_message());
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        // Update last sync timestamp
        update_option('hph_last_airtable_sync', current_time('c'));
        
        return $data['records'] ?? [];
    }
    
    /**
     * Fetch changed WordPress listings (delta sync)
     */
    private function fetch_changed_wp_listings(): array {
        $last_sync = get_option('hph_last_wp_sync', '');
        
        $args = [
            'post_type' => 'happy_place_listing',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_airtable_sync_enabled',
                    'value' => '1',
                    'compare' => '='
                ]
            ]
        ];
        
        // Add date filter if we have a timestamp
        if ($last_sync) {
            $args['date_query'] = [
                [
                    'after' => $last_sync,
                    'column' => 'post_modified',
                    'inclusive' => false
                ]
            ];
        }
        
        $query = new \WP_Query($args);
        
        // Update last sync timestamp
        update_option('hph_last_wp_sync', current_time('c'));
        
        return $query->posts;
    }
    
    /**
     * Helper method implementations
     */
    private function reset_sync_stats(): void {
        $this->sync_stats = [
            'total_processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'media_synced' => 0,
            'calculations_triggered' => 0
        ];
    }
    
    private function process_field_value($value, array $config) {
        $sanitize_function = $config['sanitize'] ?? 'sanitize_text_field';
        
        if ($sanitize_function === 'boolean_field') {
            return (bool) $value;
        }
        
        if (function_exists($sanitize_function)) {
            return $sanitize_function($value);
        }
        
        return $value;
    }
    
    private function process_media_field($airtable_media, array $config, int $listing_id): void {
        $processed_files = $this->media_manager->process_airtable_media($airtable_media, $config, $listing_id);
        
        if (!empty($processed_files)) {
            $wp_field = $config['wp_field'];
            
            if ($config['max_files'] === 1) {
                // Single file field
                update_field($wp_field, $processed_files[0], $listing_id);
            } else {
                // Multiple files field
                update_field($wp_field, $processed_files, $listing_id);
            }
        }
    }
    
    private function find_or_create_wp_listing(string $airtable_id, array $fields): ?int {
        // Find existing listing by Airtable ID
        $existing = get_posts([
            'post_type' => 'happy_place_listing',
            'meta_key' => '_airtable_record_id',
            'meta_value' => $airtable_id,
            'posts_per_page' => 1
        ]);
        
        if (!empty($existing)) {
            return $existing[0]->ID;
        }
        
        // Create new listing
        $title = $fields['Property Name'] ?? $fields['Street Address'] ?? 'Listing from Airtable';
        
        $listing_id = wp_insert_post([
            'post_type' => 'happy_place_listing',
            'post_title' => $title,
            'post_status' => 'publish'
        ]);
        
        if ($listing_id) {
            update_post_meta($listing_id, '_airtable_record_id', $airtable_id);
            update_post_meta($listing_id, '_airtable_sync_enabled', '1');
            $this->sync_stats['created']++;
        }
        
        return $listing_id;
    }
    
    private function format_value_for_airtable($value, array $config) {
        switch ($config['data_type']) {
            case 'boolean':
                return (bool) $value;
            case 'number':
                return is_numeric($value) ? (float) $value : null;
            case 'date':
                return is_string($value) ? $value : null;
            default:
                return (string) $value;
        }
    }
    
    private function prepare_media_for_airtable($wp_media, array $config): array {
        return $this->media_manager->prepare_wp_media_for_airtable($wp_media, $config);
    }
    
    private function create_airtable_record(int $listing_id): ?string {
        // Implementation for creating new Airtable records
        return null;
    }
    
    private function update_airtable_record(string $record_id, array $data): bool {
        $url = "{$this->base_url}{$this->base_id}/{$this->table_name}/{$record_id}";
        
        $response = wp_remote_request($url, [
            'method' => 'PATCH',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode(['fields' => $data]),
            'timeout' => 30
        ]);
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    private function update_sync_timestamp(int $listing_id, string $direction): void {
        update_post_meta($listing_id, "_last_sync_{$direction}", current_time('c'));
    }
}
