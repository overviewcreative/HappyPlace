<?php
/**
 * Template part for displaying listing neighborhood section
 * 
 * This is a fallback template part used when the Neighborhood component is not available.
 * 
 * @package HappyPlace
 * @subpackage TemplateParts
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get listing data
$listing_id = isset($listing_id) ? $listing_id : get_the_ID();
$neighborhood_data = isset($neighborhood_data) ? $neighborhood_data : [];

// Fallback data if not provided
if (empty($neighborhood_data)) {
    $neighborhood_data = function_exists('hph_bridge_get_neighborhood_data') 
        ? hph_bridge_get_neighborhood_data($listing_id)
        : hph_fallback_get_neighborhood_data($listing_id);
}

$neighborhood = $neighborhood_data['name'] ?? '';
$description = $neighborhood_data['description'] ?? '';
$nearby_places = $neighborhood_data['nearby_places'] ?? [];
$schools = $neighborhood_data['schools'] ?? [];
$walk_score = $neighborhood_data['walk_score'] ?? '';
?>

<?php if (!empty($neighborhood) || !empty($description) || !empty($nearby_places)): ?>
<section class="hph-listing-neighborhood fallback-neighborhood">
    <div class="container">
        <h2 class="section-title">
            <?php echo !empty($neighborhood) ? 'About ' . esc_html($neighborhood) : 'About the Neighborhood'; ?>
        </h2>
        
        <div class="neighborhood-content">
            <?php if (!empty($description)): ?>
                <div class="neighborhood-description">
                    <p><?php echo esc_html($description); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($walk_score)): ?>
                <div class="walk-score">
                    <div class="score-item">
                        <span class="score-label">Walk Score</span>
                        <span class="score-value"><?php echo esc_html($walk_score); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="neighborhood-features">
                <?php if (!empty($nearby_places)): ?>
                    <div class="nearby-places">
                        <h3 class="feature-title">Nearby Places</h3>
                        <ul class="places-list">
                            <?php foreach ($nearby_places as $place): ?>
                                <li class="place-item">
                                    <span class="place-name"><?php echo esc_html($place['name']); ?></span>
                                    <?php if (!empty($place['distance'])): ?>
                                        <span class="place-distance"><?php echo esc_html($place['distance']); ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($schools)): ?>
                    <div class="nearby-schools">
                        <h3 class="feature-title">Schools</h3>
                        <ul class="schools-list">
                            <?php foreach ($schools as $school): ?>
                                <li class="school-item">
                                    <div class="school-info">
                                        <span class="school-name"><?php echo esc_html($school['name']); ?></span>
                                        <span class="school-type"><?php echo esc_html($school['type'] ?? ''); ?></span>
                                    </div>
                                    <?php if (!empty($school['rating'])): ?>
                                        <span class="school-rating"><?php echo esc_html($school['rating']); ?>/10</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>
