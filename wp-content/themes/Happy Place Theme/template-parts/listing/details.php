<?php
/**
 * Template part for displaying listing details section
 * 
 * This is a fallback template part used when the Details component is not available.
 * 
 * @package HappyPlace
 * @subpackage TemplateParts
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get listing data
$listing_id = isset($listing_id) ? $listing_id : get_the_ID();
$property_details = isset($property_details) ? $property_details : [];

// Fallback data if not provided
if (empty($property_details)) {
    $property_details = function_exists('hph_bridge_get_property_details') 
        ? hph_bridge_get_property_details($listing_id)
        : hph_fallback_get_property_details($listing_id);
}

$bedrooms = $property_details['bedrooms'] ?? '';
$bathrooms = $property_details['bathrooms'] ?? '';
$sqft = $property_details['sqft'] ?? '';
$lot_size = $property_details['lot_size'] ?? '';
$year_built = $property_details['year_built'] ?? '';
$property_type = $property_details['property_type'] ?? '';
$description = $property_details['description'] ?? get_the_content();
?>

<section class="hph-listing-details fallback-details">
    <div class="container">
        <div class="details-grid">
            <!-- Property Stats -->
            <div class="property-stats">
                <h2 class="section-title">Property Details</h2>
                <div class="stats-grid">
                    <?php if (!empty($bedrooms)): ?>
                        <div class="stat-item">
                            <span class="stat-icon">🛏</span>
                            <div class="stat-content">
                                <span class="stat-value"><?php echo esc_html($bedrooms); ?></span>
                                <span class="stat-label">Bedrooms</span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($bathrooms)): ?>
                        <div class="stat-item">
                            <span class="stat-icon">🚿</span>
                            <div class="stat-content">
                                <span class="stat-value"><?php echo esc_html($bathrooms); ?></span>
                                <span class="stat-label">Bathrooms</span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($sqft)): ?>
                        <div class="stat-item">
                            <span class="stat-icon">📐</span>
                            <div class="stat-content">
                                <span class="stat-value"><?php echo esc_html(number_format($sqft)); ?></span>
                                <span class="stat-label">Sq Ft</span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($lot_size)): ?>
                        <div class="stat-item">
                            <span class="stat-icon">🏞</span>
                            <div class="stat-content">
                                <span class="stat-value"><?php echo esc_html($lot_size); ?></span>
                                <span class="stat-label">Lot Size</span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($year_built)): ?>
                        <div class="stat-item">
                            <span class="stat-icon">🏗</span>
                            <div class="stat-content">
                                <span class="stat-value"><?php echo esc_html($year_built); ?></span>
                                <span class="stat-label">Year Built</span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($property_type)): ?>
                        <div class="stat-item">
                            <span class="stat-icon">🏡</span>
                            <div class="stat-content">
                                <span class="stat-value"><?php echo esc_html($property_type); ?></span>
                                <span class="stat-label">Property Type</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Description -->
            <?php if (!empty($description)): ?>
                <div class="property-description">
                    <h3 class="description-title">About This Property</h3>
                    <div class="description-content">
                        <?php echo wp_kses_post($description); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
