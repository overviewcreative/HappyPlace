<?php
/**
 * Property Story Section - Bridge Function Version
 * Description + Key Features Combined
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get listing ID from parent template
$listing_id = $listing_id ?? get_the_ID();

// Use bridge functions for what this template needs
$description = get_the_content($listing_id) ?: get_field('description', $listing_id) ?: '';

// Get features using improved bridge function with formatting
$features = [];
if (function_exists('hph_bridge_get_features_formatted')) {
    $features = hph_bridge_get_features_formatted($listing_id);
} elseif (function_exists('hph_bridge_get_features')) {
    $features = hph_bridge_get_features($listing_id);
}

// Add demo description for lewes-colonial if empty
if (empty($description)) {
    $post = get_post($listing_id);
    if ($post && $post->post_name === 'lewes-colonial') {
        $description = 'This beautiful colonial home features a spacious open floor plan with stunning hardwood floors throughout. The updated kitchen boasts granite countertops and stainless steel appliances, perfect for entertaining. The master suite includes a walk-in closet and ensuite bathroom. Located in a quiet neighborhood with mature trees and close to schools and shopping.';
    }
}

// Always show the section - features are now populated by bridge function

// Get property basics for subtitle using enhanced bridge functions
$bedrooms = function_exists('hph_bridge_get_bedrooms') ? hph_bridge_get_bedrooms($listing_id) : 0;
$bathrooms_formatted = function_exists('hph_bridge_get_bathrooms_formatted') ? hph_bridge_get_bathrooms_formatted($listing_id) : '';
$bathrooms_raw = function_exists('hph_bridge_get_bathrooms') ? hph_bridge_get_bathrooms($listing_id) : 0;
$sqft_formatted = function_exists('hph_bridge_get_sqft_formatted') ? hph_bridge_get_sqft_formatted($listing_id, 'standard') : '';
$sqft_raw = function_exists('hph_bridge_get_sqft') ? hph_bridge_get_sqft($listing_id) : 0;
$lot_size_formatted = function_exists('hph_bridge_get_lot_size_formatted') ? hph_bridge_get_lot_size_formatted($listing_id) : '';
$property_type = function_exists('hph_get_listing_field') ? hph_get_listing_field($listing_id, 'property_type') : '';
$city = function_exists('hph_bridge_get_address') ? hph_bridge_get_address($listing_id, 'city') : '';
$year_built = function_exists('hph_get_listing_field') ? hph_get_listing_field($listing_id, 'year_built') : '';
$list_date_formatted = function_exists('hph_bridge_get_list_date') ? hph_bridge_get_list_date($listing_id, 'relative') : '';
?>

<section class="property-story">
    <div class="story-header">
        <h2 class="story-title">About This Home</h2>
        <?php if ($property_type || $bedrooms || $bathrooms || $sqft || $city) : ?>
        <p class="story-subtitle">
            <i class="fas fa-info-circle" aria-hidden="true"></i>
            <?php 
            $subtitle_parts = [];
            if ($property_type) $subtitle_parts[] = esc_html($property_type);
            if ($bedrooms || $bathrooms_raw) {
                $bed_bath = '';
                if ($bedrooms) $bed_bath .= $bedrooms . ' bed';
                if ($bedrooms && $bathrooms_raw) $bed_bath .= ', ';
                if ($bathrooms_formatted) {
                    $bed_bath .= strtolower($bathrooms_formatted);
                } elseif ($bathrooms_raw) {
                    $bed_bath .= $bathrooms_raw . ' bath';
                }
                if ($bed_bath) $subtitle_parts[] = $bed_bath;
            }
            if ($sqft_formatted) {
                $subtitle_parts[] = $sqft_formatted;
            } elseif ($sqft_raw) {
                $subtitle_parts[] = number_format($sqft_raw) . ' sq ft';
            }
            if ($lot_size_formatted) $subtitle_parts[] = $lot_size_formatted;
            if ($city) $subtitle_parts[] = esc_html($city);
            echo implode(' • ', $subtitle_parts);
            ?>
        </p>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($description)) : ?>
    <div class="story-content">
        <?php 
        // Split description into paragraphs if it's a single block
        $paragraphs = explode("\n\n", $description);
        if (count($paragraphs) === 1) {
            // If no double line breaks, split by periods and group into paragraphs
            $sentences = explode('. ', $description);
            $paragraphs = [];
            $current_paragraph = '';
            
            foreach ($sentences as $i => $sentence) {
                $current_paragraph .= $sentence;
                if ($i < count($sentences) - 1) {
                    $current_paragraph .= '. ';
                }
                
                // Start new paragraph every 2-3 sentences
                if (($i + 1) % 3 === 0 || $i === count($sentences) - 1) {
                    $paragraphs[] = trim($current_paragraph);
                    $current_paragraph = '';
                }
            }
        }
        
        foreach ($paragraphs as $paragraph) {
            if (!empty(trim($paragraph))) {
                echo '<p>' . esc_html(trim($paragraph)) . '</p>';
            }
        }
        ?>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($features)) : ?>
    <div class="key-features">
        <h3 class="features-title">
            <i class="fas fa-star" aria-hidden="true"></i> Key Features & Amenities
        </h3>
        <div class="features-grid">
            <?php foreach ($features as $feature) : ?>
                <?php 
                // Handle both string and array formats for ACF data
                if (is_array($feature)) {
                    $feature_text = $feature['label'] ?? $feature['value'] ?? $feature;
                } else {
                    $feature_text = $feature;
                }
                
                // Apply formatting if not already formatted
                if (function_exists('hph_format_feature_label') && !function_exists('hph_bridge_get_features_formatted')) {
                    $feature_text = hph_format_feature_label($feature_text);
                }
                ?>
                <?php if (!empty($feature_text)) : ?>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-check-circle" aria-hidden="true"></i>
                    </div>
                    <span class="feature-text"><?php echo esc_html($feature_text); ?></span>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</section>
