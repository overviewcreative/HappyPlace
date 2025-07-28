<?php
/**
 * Living Experience Section - Bridge Function Version
 * Shows walkability, schools, and local amenities
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get listing ID from parent template
$listing_id = $listing_id ?? get_the_ID();

// Use bridge functions for what this template needs
$walk_score = function_exists('hph_get_listing_field') ? hph_get_listing_field($listing_id, 'walk_score', 0) : 0;
$transit_score = function_exists('hph_get_listing_field') ? hph_get_listing_field($listing_id, 'transit_score', 0) : 0;
$bike_score = function_exists('hph_get_listing_field') ? hph_get_listing_field($listing_id, 'bike_score', 0) : 0;
$schools = function_exists('hph_get_listing_field') ? hph_get_listing_field($listing_id, 'schools', []) : [];
$amenities = function_exists('hph_get_listing_field') ? hph_get_listing_field($listing_id, 'amenities', []) : [];

// Only show sections with real data
$has_schools = !empty($schools) && is_array($schools);
$has_amenities = !empty($amenities) && is_array($amenities);
$has_walk_score = !empty($walk_score) && is_numeric($walk_score);

// Don't show section if no data
if (!$has_schools && !$has_amenities && !$has_walk_score) {
    return;
}
?>

<section class="living-experience">
    <div class="experience-header">
        <h2 class="experience-title">Living Experience</h2>
        <p class="experience-subtitle">Discover what makes this neighborhood special</p>
    </div>
    
    <div class="experience-grid">
        
        <?php if ($has_walk_score) : ?>
        <!-- Walkability Scores Card -->
        <div class="experience-card walkability-scores">
            <div class="card-header">
                <h3 class="card-title">
                    <span class="icon">🚶</span>
                    Walkability & Transit
                </h3>
            </div>
            <div class="card-content">
                <div class="score-grid">
                    <div class="score-item">
                        <div class="score-circle" style="--score: <?php echo esc_attr($walk_score); ?>">
                            <span class="score-number"><?php echo esc_html($walk_score); ?></span>
                        </div>
                        <div class="score-label">Walk Score</div>
                        <div class="score-description"><?php 
                        if ($walk_score >= 90) echo 'Walker\'s Paradise';
                        elseif ($walk_score >= 70) echo 'Very Walkable';
                        elseif ($walk_score >= 50) echo 'Somewhat Walkable';
                        else echo 'Car-Dependent';
                        ?></div>
                    </div>
                    <?php if ($transit_score > 0) : ?>
                    <div class="score-item">
                        <div class="score-circle" style="--score: <?php echo esc_attr($transit_score); ?>">
                            <span class="score-number"><?php echo esc_html($transit_score); ?></span>
                        </div>
                        <div class="score-label">Transit Score</div>
                    </div>
                    <?php endif; ?>
                    <?php if ($bike_score > 0) : ?>
                    <div class="score-item">
                        <div class="score-circle" style="--score: <?php echo esc_attr($bike_score); ?>">
                            <span class="score-number"><?php echo esc_html($bike_score); ?></span>
                        </div>
                        <div class="score-label">Bike Score</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($has_schools) : ?>
        <!-- Schools Card -->
        <div class="experience-card schools">
            <div class="card-header">
                <h3 class="card-title">
                    <span class="icon">🎓</span>
                    Schools
                </h3>
            </div>
            <div class="card-content">
                <div class="schools-list">
                    <?php foreach ($schools as $school) : ?>
                        <?php if (!empty($school['name'])) : ?>
                        <div class="school-item">
                            <div class="school-info">
                                <h4 class="school-name"><?php echo esc_html($school['name']); ?></h4>
                                <?php if (!empty($school['type'])) : ?>
                                <span class="school-type"><?php echo esc_html($school['type']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($school['grades'])) : ?>
                                <span class="school-grades"><?php echo esc_html($school['grades']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="school-stats">
                                <?php if (!empty($school['distance'])) : ?>
                                <span class="school-distance"><?php echo esc_html($school['distance']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($school['rating'])) : ?>
                                <span class="school-rating"><?php echo esc_html($school['rating']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($has_amenities) : ?>
        <!-- Local Amenities Card -->
        <div class="experience-card amenities">
            <div class="card-header">
                <h3 class="card-title">
                    <span class="icon">🏪</span>
                    Local Amenities
                </h3>
            </div>
            <div class="card-content">
                <div class="amenities-list">
                    <?php foreach ($amenities as $amenity) : ?>
                        <?php if (!empty($amenity['name'])) : ?>
                        <div class="amenity-item">
                            <div class="amenity-info">
                                <h4 class="amenity-name"><?php echo esc_html($amenity['name']); ?></h4>
                                <?php if (!empty($amenity['type'])) : ?>
                                <span class="amenity-type"><?php echo esc_html($amenity['type']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="amenity-stats">
                                <?php if (!empty($amenity['distance'])) : ?>
                                <span class="amenity-distance"><?php echo esc_html($amenity['distance']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($amenity['rating'])) : ?>
                                <span class="amenity-rating"><?php echo esc_html($amenity['rating']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
</section>
