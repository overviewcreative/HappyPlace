<?php
/**
 * City Places Display Template
 * 
 * Template part for displaying city places with Google API integration
 */

// Get city data
$city_id = get_the_ID();
$places = hph_bridge_get_city_places_by_category($city_id);
$coordinates = hph_bridge_get_city_coordinates($city_id);
$api_status = hph_bridge_get_city_api_status($city_id);

?>

<div class="hph-city-places-section">
    <div class="hph-places-header">
        <h3><?php _e('Local Places & Amenities', 'happy-place'); ?></h3>
        
        <?php if ($api_status['status'] === 'active' && $api_status['last_updated']): ?>
            <p class="hph-api-status">
                <i class="fas fa-sync-alt"></i>
                <?php printf(
                    __('Updated %s via Google Places API', 'happy-place'),
                    wp_date('M j, Y g:i a', $api_status['last_updated'])
                ); ?>
            </p>
        <?php endif; ?>
    </div>

    <?php if (!empty($places)): ?>
        <div class="hph-places-grid">
            <?php foreach ($places as $category => $category_places): ?>
                <div class="hph-places-category">
                    <h4 class="hph-category-title">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo esc_html($category); ?>
                        <span class="hph-place-count">(<?php echo count($category_places); ?>)</span>
                    </h4>
                    
                    <div class="hph-places-list">
                        <?php foreach ($category_places as $place): ?>
                            <div class="hph-place-item">
                                <?php if (isset($place['place_icon']) && $place['place_icon']): ?>
                                    <div class="hph-place-icon">
                                        <?php if (filter_var($place['place_icon'], FILTER_VALIDATE_URL)): ?>
                                            <img src="<?php echo esc_url($place['place_icon']); ?>" 
                                                 alt="<?php echo esc_attr($place['place_name'] ?? $place['post_title'] ?? ''); ?>"
                                                 loading="lazy">
                                        <?php elseif (is_array($place['place_icon'])): ?>
                                            <?php echo wp_get_attachment_image(
                                                $place['place_icon']['ID'], 
                                                'thumbnail',
                                                false,
                                                ['loading' => 'lazy']
                                            ); ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="hph-place-details">
                                    <h5 class="hph-place-name">
                                        <?php echo esc_html($place['place_name'] ?? $place['post_title'] ?? __('Unnamed Place', 'happy-place')); ?>
                                    </h5>
                                    
                                    <?php if (isset($place['place_address']) && $place['place_address']): ?>
                                        <p class="hph-place-address">
                                            <i class="fas fa-map-pin"></i>
                                            <?php echo esc_html($place['place_address']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($place['place_rating']) && $place['place_rating'] > 0): ?>
                                        <div class="hph-place-rating">
                                            <span class="hph-rating-stars">
                                                <?php 
                                                $rating = floatval($place['place_rating']);
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= $rating) {
                                                        echo '<i class="fas fa-star"></i>';
                                                    } elseif ($i - 0.5 <= $rating) {
                                                        echo '<i class="fas fa-star-half-alt"></i>';
                                                    } else {
                                                        echo '<i class="far fa-star"></i>';
                                                    }
                                                }
                                                ?>
                                            </span>
                                            <span class="hph-rating-number"><?php echo number_format($rating, 1); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="hph-places-empty">
            <i class="fas fa-map-marked-alt"></i>
            <p><?php _e('No places data available yet.', 'happy-place'); ?></p>
            
            <?php if (current_user_can('edit_posts')): ?>
                <p class="hph-admin-note">
                    <?php _e('Edit this city to configure Google Places API integration.', 'happy-place'); ?>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($coordinates && !empty($places)): ?>
        <div class="hph-city-map-container">
            <div id="hph-city-places-map" 
                 class="hph-google-map"
                 data-lat="<?php echo esc_attr($coordinates['lat']); ?>"
                 data-lng="<?php echo esc_attr($coordinates['lng']); ?>"
                 data-zoom="<?php echo esc_attr($coordinates['zoom']); ?>"
                 data-places="<?php echo esc_attr(json_encode($places)); ?>">
            </div>
            
            <div class="hph-map-controls">
                <button type="button" class="hph-btn hph-btn-secondary" id="hph-reset-map-view">
                    <i class="fas fa-home"></i>
                    <?php _e('Reset View', 'happy-place'); ?>
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.hph-city-places-section {
    margin: 2rem 0;
}

.hph-places-header {
    margin-bottom: 1.5rem;
}

.hph-places-header h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
    color: #1a365d;
}

.hph-api-status {
    font-size: 0.875rem;
    color: #4a5568;
    margin: 0;
}

.hph-api-status i {
    color: #38a169;
    margin-right: 0.25rem;
}

.hph-places-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.hph-places-category {
    background: #f7fafc;
    border-radius: 8px;
    padding: 1rem;
    border: 1px solid #e2e8f0;
}

.hph-category-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #2d3748;
    margin: 0 0 1rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.hph-place-count {
    font-size: 0.875rem;
    font-weight: 400;
    color: #718096;
}

.hph-places-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.hph-place-item {
    display: flex;
    gap: 0.75rem;
    padding: 0.75rem;
    background: white;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
}

.hph-place-icon {
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    border-radius: 4px;
    overflow: hidden;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
}

.hph-place-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.hph-place-details {
    flex: 1;
}

.hph-place-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: #2d3748;
    margin: 0 0 0.25rem 0;
}

.hph-place-address {
    font-size: 0.75rem;
    color: #718096;
    margin: 0 0 0.25rem 0;
}

.hph-place-address i {
    margin-right: 0.25rem;
}

.hph-place-rating {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.hph-rating-stars {
    color: #f6ad55;
    font-size: 0.75rem;
}

.hph-rating-number {
    font-size: 0.75rem;
    color: #4a5568;
    font-weight: 500;
}

.hph-places-empty {
    text-align: center;
    padding: 3rem 1rem;
    color: #718096;
}

.hph-places-empty i {
    font-size: 3rem;
    color: #cbd5e0;
    margin-bottom: 1rem;
}

.hph-admin-note {
    font-size: 0.875rem;
    color: #4299e1;
    font-style: italic;
}

.hph-city-map-container {
    margin-top: 2rem;
}

.hph-google-map {
    width: 100%;
    height: 400px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.hph-map-controls {
    margin-top: 0.5rem;
    text-align: center;
}

@media (max-width: 768px) {
    .hph-places-grid {
        grid-template-columns: 1fr;
    }
    
    .hph-place-item {
        flex-direction: column;
        text-align: center;
    }
    
    .hph-place-icon {
        align-self: center;
    }
}
</style>
