<?php
/**
 * Single listing map
 */

$listing_id = get_the_ID();
$latitude = get_post_meta($listing_id, 'latitude', true);
$longitude = get_post_meta($listing_id, 'longitude', true);
$walk_score = get_post_meta($listing_id, 'walk_score', true);
$school_rating = get_post_meta($listing_id, 'school_rating', true);
?>

<div class="details-section">
    <h3 class="section-title">Location &amp; Neighborhood</h3>
    
    <div class="map-container" 
         data-lat="<?php echo esc_attr($latitude); ?>" 
         data-lng="<?php echo esc_attr($longitude); ?>"
         data-listing-id="<?php echo esc_attr($listing_id); ?>">
        <div class="map-placeholder">📍 Loading Map...</div>
    </div>
    
    <div class="neighborhood-info">
        <?php if ($walk_score) : ?>
            <div class="info-item">
                <span class="label">Walk Score:</span>
                <span class="value"><?php echo esc_html($walk_score); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($school_rating) : ?>
            <div class="info-item">
                <span class="label">School Rating:</span>
                <span class="value"><?php echo esc_html($school_rating); ?>/10</span>
            </div>
        <?php endif; ?>
        
        <div class="info-item">
            <span class="label">Crime Rate:</span>
            <span class="value">Low</span>
        </div>
    </div>
</div>
