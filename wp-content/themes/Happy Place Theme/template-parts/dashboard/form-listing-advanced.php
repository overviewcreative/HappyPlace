<?php
/**
 * Advanced Multistep Listing Form
 * 
 * @package HappyPlace
 */

// Get current listing data if editing
$listing_id = $_GET['listing_id'] ?? 0;
$listing_data = [];

if ($listing_id) {
    // Get existing listing data
    $listing_post = get_post($listing_id);
    if ($listing_post && $listing_post->post_author == get_current_user_id()) {
        $listing_data = [
            'ID' => $listing_post->ID,
            'title' => $listing_post->post_title,
            'content' => $listing_post->post_content,
            'status' => $listing_post->post_status,
            'featured_image' => get_post_thumbnail_id($listing_post->ID),
        ];

        // Get ACF fields - map to multistep form structure
        if (function_exists('get_fields')) {
            $acf_fields = get_fields($listing_post->ID);
            if (is_array($acf_fields)) {
                // Map ACF fields to form data structure
                $listing_data = array_merge($listing_data, [
                    // Step 1: Property Basics (using plugin field names)
                    'property_type' => $acf_fields['property_type'] ?? '',
                    'street_address' => $acf_fields['street_address'] ?? '',
                    'city' => $acf_fields['city'] ?? '',
                    'state' => $acf_fields['region'] ?? 'DE',  // Plugin uses 'region'
                    'zip_code' => $acf_fields['zip_code'] ?? '',
                    
                    // Step 2: Property Details
                    'bedrooms' => $acf_fields['bedrooms'] ?? '',
                    'bathrooms' => $acf_fields['bathrooms'] ?? '',
                    'square_footage' => $acf_fields['square_footage'] ?? '',
                    'lot_size' => $acf_fields['lot_size'] ?? '',
                    'year_built' => $acf_fields['year_built'] ?? '',
                    'garage_spaces' => $acf_fields['garage_spaces'] ?? '',
                    'stories' => $acf_fields['stories'] ?? '',
                    
                    // Step 3: Features & Amenities
                    'interior_features' => $acf_fields['interior_features'] ?? [],
                    'exterior_features' => $acf_fields['exterior_features'] ?? [],
                    'utility_features' => $acf_fields['utility_features'] ?? [],
                    'custom_features' => $acf_fields['custom_features'] ?? [],
                    
                    // Step 4: Media & Marketing
                    'property_images' => $acf_fields['property_images'] ?? [],
                    'property_description' => $acf_fields['listing_description'] ?? $listing_post->post_content ?? '',
                    'listing_remarks' => $acf_fields['listing_remarks'] ?? '',
                    'showing_instructions' => $acf_fields['showing_instructions'] ?? '',
                    
                    // Step 5: Pricing & Availability
                    'price' => $acf_fields['price'] ?? '',
                    'list_date' => $acf_fields['list_date'] ?? '',
                    'listing_status' => $acf_fields['listing_status'] ?? 'draft',
                    'mls_number' => $acf_fields['mls_number'] ?? '',
                    'property_tax' => $acf_fields['property_tax'] ?? '',
                    'hoa_fees' => $acf_fields['hoa_fees'] ?? '',
                    
                    // Coordinates for map integration
                    'latitude' => $acf_fields['latitude'] ?? '',
                    'longitude' => $acf_fields['longitude'] ?? '',
                ]);
            }
        }
    }
}

$is_editing = !empty($listing_data['ID']);
$form_title = $is_editing ? __('Edit Listing', 'happy-place') : __('Add New Listing', 'happy-place');
?>

<div class="hph-dashboard-form-container">
    <div class="hph-section-header">
        <h2 class="hph-section-title">
            <i class="fas fa-<?php echo $is_editing ? 'edit' : 'plus'; ?>"></i>
            <?php echo esc_html($form_title); ?>
        </h2>
        <p class="hph-section-description">
            <?php echo $is_editing
                ? __('Update your listing information with our advanced form.', 'happy-place')
                : __('Create a new property listing with our step-by-step process.', 'happy-place'); ?>
        </p>
    </div>

    <!-- React Multistep Form Container -->
    <div id="hph-multistep-form-root" 
         data-listing-data="<?php echo esc_attr(wp_json_encode($listing_data)); ?>"
         data-is-editing="<?php echo esc_attr($is_editing ? 'true' : 'false'); ?>"
         data-nonce="<?php echo esc_attr(wp_create_nonce('hph_advanced_form_nonce')); ?>"
         data-ajax-url="<?php echo esc_attr(admin_url('admin-ajax.php')); ?>"
         data-user-id="<?php echo esc_attr(get_current_user_id()); ?>">
        
        <!-- Loading fallback while React loads -->
        <div class="hph-form-loading">
            <div class="hph-spinner"></div>
            <p><?php _e('Loading form...', 'happy-place'); ?></p>
        </div>
    </div>
</div>

<!-- Load Google Places API for address autocomplete -->
<script>
window.hphFormConfig = {
    googleMapsApiKey: '<?php echo esc_js(get_option('hph_google_maps_api_key', '')); ?>',
    ajaxUrl: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
    nonce: '<?php echo esc_js(wp_create_nonce('hph_advanced_form_nonce')); ?>',
    userId: <?php echo get_current_user_id(); ?>,
    isEditing: <?php echo $is_editing ? 'true' : 'false'; ?>,
    listingId: <?php echo $listing_id; ?>,
    // ACF field mappings for form submission (updated for plugin compatibility)
    fieldMappings: {
        // Step 1: Property Basics
        'property_type': 'property_type',
        'street_address': 'street_address',  // Plugin uses street_address
        'city': 'city',
        'state': 'region',  // Plugin uses 'region' for state
        'zip_code': 'zip_code',
        
        // Step 2: Property Details  
        'bedrooms': 'bedrooms',
        'bathrooms': 'bathrooms',
        'square_footage': 'square_footage',
        'lot_size': 'lot_size',
        'year_built': 'year_built',
        'garage_spaces': 'garage_spaces',
        'stories': 'stories',
        
        // Step 3: Features
        'interior_features': 'interior_features',
        'exterior_features': 'exterior_features', 
        'utility_features': 'utility_features',
        'custom_features': 'custom_features',
        
        // Step 4: Media & Marketing
        'property_images': 'property_images',
        'property_description': 'listing_description',  // Plugin uses listing_description
        'listing_remarks': 'listing_remarks',
        'showing_instructions': 'showing_instructions',
        
        // Step 5: Pricing & Availability
        'price': 'price',
        'list_date': 'list_date', 
        'listing_status': 'listing_status',
        'mls_number': 'mls_number',
        'property_tax': 'property_tax',
        'hoa_fees': 'hoa_fees',
        
        // Coordinates
        'latitude': 'latitude',
        'longitude': 'longitude'
    }
};
</script>

<style>
.hph-form-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 400px;
    color: var(--color-text-light);
}

.hph-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid var(--color-border);
    border-top: 4px solid var(--hph-primary-400);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* React form will replace these styles */
#hph-multistep-form-root {
    min-height: 500px;
}
</style>
