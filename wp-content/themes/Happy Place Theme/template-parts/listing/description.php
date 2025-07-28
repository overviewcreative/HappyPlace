<?php
/**
 * Property Description Template Part - Fixed Version
 * 
 * Displays property description with safe bridge integration
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extract data from template args
$data = $args['data'] ?? [];
$listing_id = $args['listing_id'] ?? get_the_ID();

// Get description with multiple fallback methods
$description = '';
$data_source = 'none';

// Method 1: Check if bridge function exists and use it
if (function_exists('hph_get_listing_field')) {
    $description = hph_get_listing_field($listing_id, 'description');
    $data_source = 'bridge';
}

// Method 2: Try direct ACF access
if (empty($description) && function_exists('get_field')) {
    $description = get_field('description', $listing_id);
    $data_source = 'acf';
}

// Method 3: Try from passed data
if (empty($description) && isset($data['description'])) {
    $description = $data['description'];
    $data_source = 'data';
}

// Method 4: Try post content
if (empty($description)) {
    $post = get_post($listing_id);
    if ($post && !empty($post->post_content)) {
        $description = $post->post_content;
        $data_source = 'content';
    }
}

// Method 5: Generate a basic description as fallback
$generated_description = false;
if (empty($description)) {
    $bedrooms = 3; // Default fallback
    $bathrooms = 2; // Default fallback
    $address = get_the_title($listing_id);
    
    // Try to get real data if available
    if (function_exists('get_field')) {
        $bedrooms = (int) get_field('bedrooms', $listing_id) ?: 3;
        $bathrooms = (int) get_field('bathrooms', $listing_id) ?: 2;
    }
    
    $description = sprintf(
        __('This beautiful %d bedroom, %d bathroom home offers comfortable living space in a desirable location. Contact us to learn more about this exceptional property and schedule your viewing today.', 'happy-place'),
        $bedrooms,
        $bathrooms
    );
    $generated_description = true;
    $data_source = 'generated';
}

// Get some basic property details for highlights
$property_details = [];
if (function_exists('get_field')) {
    $property_details = [
        'bedrooms' => (int) get_field('bedrooms', $listing_id),
        'bathrooms' => (float) get_field('bathrooms', $listing_id),
        'sqft' => (int) get_field('square_footage', $listing_id),
        'year_built' => get_field('year_built', $listing_id),
        'lot_size' => get_field('lot_size', $listing_id)
    ];
}

// Get neighborhood info
$neighborhood = '';
if (function_exists('get_field')) {
    $city = get_field('city', $listing_id);
    if ($city) {
        $neighborhood = $city;
    }
}
?>

<section class="property-description-card" 
         data-template-part="description" 
         data-listing-id="<?php echo esc_attr($listing_id); ?>"
         data-bridge-source="<?php echo esc_attr($data_source); ?>">
    
    <div class="card-header">
        <h2 class="card-title">
            <i class="fas fa-home" aria-hidden="true"></i>
            <?php esc_html_e('About This Home', 'happy-place'); ?>
        </h2>
        <?php if ($neighborhood) : ?>
            <div class="card-subtitle">
                <?php 
                printf(
                    esc_html__('Located in %s', 'happy-place'),
                    esc_html($neighborhood)
                );
                ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card-body">
        
        <!-- Property Description -->
        <div class="description-text" 
             <?php if ($generated_description) : ?>data-generated="true"<?php endif; ?>
             data-bridge-source="<?php echo esc_attr($data_source); ?>">
            
            <div class="listing-content">
                <?php echo wp_kses_post(wpautop($description)); ?>
            </div>
        </div>

        <!-- Property Highlights -->
        <?php if (!empty($property_details['bedrooms']) || !empty($property_details['bathrooms']) || !empty($property_details['sqft'])) : ?>
            <div class="property-highlights">
                <h3 class="highlights-title">
                    <?php esc_html_e('Property Highlights', 'happy-place'); ?>
                </h3>
                
                <div class="highlights-list">
                    
                    <?php if ($property_details['bedrooms']) : ?>
                        <div class="highlight-item">
                            <i class="fas fa-bed highlight-icon" aria-hidden="true"></i>
                            <span>
                                <?php 
                                printf(
                                    _n('%d Spacious Bedroom', '%d Spacious Bedrooms', $property_details['bedrooms'], 'happy-place'),
                                    $property_details['bedrooms']
                                ); 
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($property_details['bathrooms']) : ?>
                        <div class="highlight-item">
                            <i class="fas fa-bath highlight-icon" aria-hidden="true"></i>
                            <span>
                                <?php 
                                if ($property_details['bathrooms'] == floor($property_details['bathrooms'])) {
                                    printf(
                                        _n('%d Full Bathroom', '%d Full Bathrooms', $property_details['bathrooms'], 'happy-place'),
                                        $property_details['bathrooms']
                                    );
                                } else {
                                    printf(
                                        esc_html__('%s Bathrooms Total', 'happy-place'),
                                        $property_details['bathrooms']
                                    );
                                }
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($property_details['sqft']) : ?>
                        <div class="highlight-item">
                            <i class="fas fa-ruler-combined highlight-icon" aria-hidden="true"></i>
                            <span>
                                <?php 
                                printf(
                                    esc_html__('%s Square Feet', 'happy-place'),
                                    number_format($property_details['sqft'])
                                ); 
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($property_details['year_built']) : ?>
                        <div class="highlight-item">
                            <i class="fas fa-calendar-alt highlight-icon" aria-hidden="true"></i>
                            <span>
                                <?php 
                                printf(
                                    esc_html__('Built in %s', 'happy-place'),
                                    esc_html($property_details['year_built'])
                                ); 
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($property_details['lot_size']) : ?>
                        <div class="highlight-item">
                            <i class="fas fa-expand-arrows-alt highlight-icon" aria-hidden="true"></i>
                            <span>
                                <?php 
                                printf(
                                    esc_html__('%s Lot Size', 'happy-place'),
                                    esc_html($property_details['lot_size'])
                                ); 
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                </div>
            </div>
        <?php endif; ?>

        <!-- Empty State Fallback -->
        <?php if (empty($description)) : ?>
            <div class="empty-state" data-empty="true">
                <div class="empty-icon">
                    <i class="fas fa-home" aria-hidden="true"></i>
                </div>
                <div class="empty-title">
                    <?php esc_html_e('Description Coming Soon', 'happy-place'); ?>
                </div>
                <div class="empty-message">
                    <?php esc_html_e('We\'re working on adding a detailed description for this property. Please contact us for more information.', 'happy-place'); ?>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- Debug Info (only in WP_DEBUG mode) -->
    <?php if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')) : ?>
        <div class="debug-info" style="margin-top: 1rem; padding: 0.5rem; background: #f0f0f0; font-size: 0.8rem; color: #666;">
            <strong>Debug:</strong> 
            Data Source: <?php echo esc_html($data_source); ?> | 
            Generated: <?php echo $generated_description ? 'Yes' : 'No'; ?> | 
            Description Length: <?php echo strlen($description); ?> chars |
            ACF Available: <?php echo function_exists('get_field') ? 'Yes' : 'No'; ?>
        </div>
    <?php endif; ?>

</section>