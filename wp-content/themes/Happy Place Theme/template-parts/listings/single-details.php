<?php
/**
 * Single listing details
 */

$listing_id = get_the_ID();
$bedrooms = get_post_meta($listing_id, 'bedrooms', true);
$bathrooms = get_post_meta($listing_id, 'bathrooms', true);
$square_feet = get_post_meta($listing_id, 'square_feet', true);
$lot_size = get_post_meta($listing_id, 'lot_size', true);
$year_built = get_post_meta($listing_id, 'year_built', true);
?>

<div class="property-details">
    <!-- Property Stats -->
    <div class="property-stats">
        <?php if ($bedrooms) : ?>
            <div class="stat-item">
                <div class="stat-number"><?php echo esc_html($bedrooms); ?></div>
                <div class="stat-label">Bedrooms</div>
            </div>
        <?php endif; ?>
        
        <?php if ($bathrooms) : ?>
            <div class="stat-item">
                <div class="stat-number"><?php echo esc_html($bathrooms); ?></div>
                <div class="stat-label">Bathrooms</div>
            </div>
        <?php endif; ?>
        
        <?php if ($square_feet) : ?>
            <div class="stat-item">
                <div class="stat-number"><?php echo number_format($square_feet); ?></div>
                <div class="stat-label">Sq Ft</div>
            </div>
        <?php endif; ?>
        
        <?php if ($lot_size) : ?>
            <div class="stat-item">
                <div class="stat-number"><?php echo esc_html($lot_size); ?></div>
                <div class="stat-label">Acres</div>
            </div>
        <?php endif; ?>
        
        <?php if ($year_built) : ?>
            <div class="stat-item">
                <div class="stat-number"><?php echo esc_html($year_built); ?></div>
                <div class="stat-label">Year Built</div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Description -->
    <div class="details-section">
        <h3 class="section-title">Property Description</h3>
        <div class="property-description">
            <?php the_content(); ?>
        </div>
    </div>
</div>
