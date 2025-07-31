<?php
/**
 * Template part for displaying similar listings section
 * 
 * This is a fallback template part used when the Similar Listings component is not available.
 * 
 * @package HappyPlace
 * @subpackage TemplateParts
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get listing data
$listing_id = isset($listing_id) ? $listing_id : get_the_ID();
$similar_listings = isset($similar_listings) ? $similar_listings : [];

// Fallback data if not provided
if (empty($similar_listings)) {
    $similar_listings = function_exists('hph_bridge_get_similar_listings') 
        ? hph_bridge_get_similar_listings($listing_id)
        : hph_fallback_get_similar_listings($listing_id);
}
?>

<?php if (!empty($similar_listings)): ?>
<section class="hph-similar-listings fallback-similar">
    <div class="container">
        <h2 class="section-title">Similar Properties</h2>
        
        <div class="similar-listings-grid">
            <?php foreach ($similar_listings as $listing): ?>
                <div class="similar-listing-card">
                    <div class="card-image">
                        <?php if (!empty($listing['featured_image'])): ?>
                            <img src="<?php echo esc_url($listing['featured_image']); ?>" 
                                 alt="<?php echo esc_attr($listing['title']); ?>"
                                 loading="lazy">
                        <?php endif; ?>
                        
                        <?php if (!empty($listing['price'])): ?>
                            <div class="card-price">
                                <?php echo esc_html($listing['price']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-content">
                        <h3 class="card-title">
                            <a href="<?php echo esc_url($listing['permalink']); ?>">
                                <?php echo esc_html($listing['title']); ?>
                            </a>
                        </h3>
                        
                        <?php if (!empty($listing['address'])): ?>
                            <p class="card-address"><?php echo esc_html($listing['address']); ?></p>
                        <?php endif; ?>
                        
                        <div class="card-details">
                            <?php if (!empty($listing['bedrooms'])): ?>
                                <span class="detail-item">
                                    <span class="detail-icon">🛏</span>
                                    <?php echo esc_html($listing['bedrooms']); ?> beds
                                </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($listing['bathrooms'])): ?>
                                <span class="detail-item">
                                    <span class="detail-icon">🚿</span>
                                    <?php echo esc_html($listing['bathrooms']); ?> baths
                                </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($listing['sqft'])): ?>
                                <span class="detail-item">
                                    <span class="detail-icon">📐</span>
                                    <?php echo esc_html(number_format($listing['sqft'])); ?> sqft
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-actions">
                            <a href="<?php echo esc_url($listing['permalink']); ?>" class="btn btn-primary">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
