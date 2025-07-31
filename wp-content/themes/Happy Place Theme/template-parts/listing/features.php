<?php
/**
 * Template part for displaying listing features section
 * 
 * This is a fallback template part used when the Features component is not available.
 * 
 * @package HappyPlace
 * @subpackage TemplateParts
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get listing data
$listing_id = isset($listing_id) ? $listing_id : get_the_ID();
$features = isset($features) ? $features : [];

// Fallback data if not provided
if (empty($features)) {
    $features = function_exists('hph_bridge_get_features') 
        ? hph_bridge_get_features($listing_id)
        : hph_fallback_get_features($listing_id);
}

$interior_features = $features['interior'] ?? [];
$exterior_features = $features['exterior'] ?? [];
$amenities = $features['amenities'] ?? [];
?>

<?php if (!empty($interior_features) || !empty($exterior_features) || !empty($amenities)): ?>
<section class="hph-listing-features fallback-features">
    <div class="container">
        <h2 class="section-title">Property Features</h2>
        
        <div class="features-grid">
            <?php if (!empty($interior_features)): ?>
                <div class="feature-group">
                    <h3 class="feature-group-title">Interior Features</h3>
                    <ul class="feature-list">
                        <?php foreach ($interior_features as $feature): ?>
                            <li class="feature-item">
                                <span class="feature-icon">✓</span>
                                <span class="feature-text"><?php echo esc_html($feature); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($exterior_features)): ?>
                <div class="feature-group">
                    <h3 class="feature-group-title">Exterior Features</h3>
                    <ul class="feature-list">
                        <?php foreach ($exterior_features as $feature): ?>
                            <li class="feature-item">
                                <span class="feature-icon">✓</span>
                                <span class="feature-text"><?php echo esc_html($feature); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($amenities)): ?>
                <div class="feature-group">
                    <h3 class="feature-group-title">Amenities</h3>
                    <ul class="feature-list">
                        <?php foreach ($amenities as $amenity): ?>
                            <li class="feature-item">
                                <span class="feature-icon">✓</span>
                                <span class="feature-text"><?php echo esc_html($amenity); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>
