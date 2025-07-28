<?php
/**
 * Map Section - Bridge Function Version
 * Interactive location and neighborhood map
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get listing ID from parent template
$listing_id = $listing_id ?? get_the_ID();

// Use bridge functions for what this template needs
$street_address = function_exists('hph_bridge_get_address') ? hph_bridge_get_address($listing_id, 'street') : '123 Oak Street';
$city = function_exists('hph_bridge_get_address') ? hph_bridge_get_address($listing_id, 'city') : 'Local City';
$state = function_exists('hph_bridge_get_address') ? hph_bridge_get_address($listing_id, 'state') : 'State';
$zip = function_exists('hph_bridge_get_address') ? hph_bridge_get_address($listing_id, 'zip') : '00000';
$full_address = function_exists('hph_bridge_get_address') ? hph_bridge_get_address($listing_id, 'full') : '';

$coordinates = function_exists('hph_get_listing_field') ? hph_get_listing_field($listing_id, 'coordinates', []) : [];
$nearby_places = function_exists('hph_get_listing_field') ? hph_get_listing_field($listing_id, 'nearby_places', []) : [];

// Add demo coordinates for lewes-colonial if no real data
if (empty($coordinates)) {
    $post = get_post($listing_id);
    if ($post && $post->post_name === 'lewes-colonial') {
        $coordinates = [
            'lat' => 38.7443,
            'lng' => -75.1398
        ];
    }
}

// Address components
$address_data = [
    'street' => $street_address,
    'city' => $city,
    'state' => $state,
    'zip' => $zip,
    'full' => $full_address ?: trim($street_address . ', ' . $city . ', ' . $state . ' ' . $zip, ', ')
];

// Only show map if we have coordinates and address
if (empty($coordinates['lat']) || empty($coordinates['lng']) || empty($full_address)) {
    return; // Don't show map without proper location data
}

// Use real nearby places only - don't show dummy data
$has_nearby_places = !empty($nearby_places) && is_array($nearby_places);

// Map configuration
$map_config = [
    'api_key' => get_option('google_maps_api_key', ''), // WordPress option for API key
    'zoom' => 15,
    'style' => 'roadmap' // roadmap, satellite, hybrid, terrain
];
?>

<section class="map-section" id="map-section">
    <div class="container">
        <!-- Map Header -->
        <div class="map-header">
            <div class="map-info">
                <h2 class="map-title">Location & Neighborhood</h2>
                <p class="map-subtitle">Discover what's nearby and explore the area</p>
            </div>
            
            <div class="map-address">
                <div class="address-icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="address-details">
                    <div class="address-street"><?php echo esc_html($address_data['street']); ?></div>
                    <div class="address-location"><?php echo esc_html($address_data['city'] . ', ' . $address_data['state'] . ' ' . $address_data['zip']); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Map Container -->
        <div class="map-container">
            <!-- Interactive Map -->
            <div class="map-area">
                <div class="map-canvas" id="property-map" 
                     data-lat="<?php echo esc_attr($coordinates['lat']); ?>"
                     data-lng="<?php echo esc_attr($coordinates['lng']); ?>"
                     data-address="<?php echo esc_attr($address_data['full']); ?>"
                     data-zoom="<?php echo esc_attr($map_config['zoom']); ?>">
                    
                    <!-- Map loading placeholder -->
                    <div class="map-placeholder" id="map-placeholder">
                        <div class="placeholder-content">
                            <i class="fas fa-map placeholder-icon"></i>
                            <h3 class="placeholder-title">Interactive Map</h3>
                            <p class="placeholder-address"><?php echo esc_html($address_data['full']); ?></p>
                            <button class="hph-btn hph-btn--primary" id="load-map-btn">
                                <i class="fas fa-map-marked-alt map-btn-icon"></i>
                                Load Interactive Map
                            </button>
                        </div>
                    </div>
                    
                    <!-- Map loading indicator -->
                    <div class="map-loading" id="map-loading" style="display: none;">
                        <div class="loading-content">
                            <i class="fas fa-spinner fa-spin loading-icon"></i>
                            <p>Loading map...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Map Controls -->
                <div class="map-controls" id="map-controls" style="display: none;">
                    <div class="control-group">
                        <button class="map-control-btn active" data-view="roadmap" title="Road Map">
                            <i class="fas fa-road"></i>
                        </button>
                        <button class="map-control-btn" data-view="satellite" title="Satellite">
                            <i class="fas fa-satellite"></i>
                        </button>
                        <button class="map-control-btn" data-view="terrain" title="Terrain">
                            <i class="fas fa-mountain"></i>
                        </button>
                    </div>
                    
                    <div class="control-group">
                        <button class="map-control-btn" id="zoom-in-btn" title="Zoom In">
                            <i class="fas fa-plus"></i>
                        </button>
                        <button class="map-control-btn" id="zoom-out-btn" title="Zoom Out">
                            <i class="fas fa-minus"></i>
                        </button>
                        <button class="map-control-btn" id="center-map-btn" title="Center on Property">
                            <i class="fas fa-crosshairs"></i>
                        </button>
                    </div>
                    
                    <div class="control-group">
                        <button class="map-control-btn" id="directions-btn" title="Get Directions">
                            <i class="fas fa-route"></i>
                        </button>
                        <button class="map-control-btn" id="street-view-btn" title="Street View">
                            <i class="fas fa-street-view"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Nearby Places -->
            <div class="nearby-places">
                <h3 class="nearby-title">What's Nearby</h3>
                
                <div class="places-filters">
                    <button class="filter-btn active" data-filter="all">All</button>
                    <button class="filter-btn" data-filter="food">Food & Drink</button>
                    <button class="filter-btn" data-filter="shopping">Shopping</button>
                    <button class="filter-btn" data-filter="schools">Schools</button>
                    <button class="filter-btn" data-filter="healthcare">Healthcare</button>
                    <button class="filter-btn" data-filter="recreation">Recreation</button>
                </div>
                
                <div class="places-list">
                    <?php foreach ($nearby_places as $place): ?>
                    <?php
                        // Determine place category for filtering
                        $category = '';
                        switch ($place['type']) {
                            case 'coffee':
                            case 'restaurant':
                                $category = 'food';
                                break;
                            case 'grocery':
                            case 'shopping':
                                $category = 'shopping';
                                break;
                            case 'school':
                                $category = 'schools';
                                break;
                            case 'hospital':
                            case 'clinic':
                                $category = 'healthcare';
                                break;
                            case 'park':
                            case 'gym':
                                $category = 'recreation';
                                break;
                            default:
                                $category = 'other';
                        }
                        
                        // Get appropriate icon
                        $icon_map = [
                            'coffee' => 'fas fa-coffee',
                            'grocery' => 'fas fa-shopping-basket',
                            'park' => 'fas fa-tree',
                            'school' => 'fas fa-graduation-cap',
                            'hospital' => 'fas fa-hospital',
                            'shopping' => 'fas fa-shopping-bag',
                            'restaurant' => 'fas fa-utensils',
                            'gym' => 'fas fa-dumbbell'
                        ];
                        $icon = $icon_map[$place['type']] ?? 'fas fa-map-marker-alt';
                    ?>
                    <div class="place-item" data-category="<?php echo esc_attr($category); ?>">
                        <div class="place-icon">
                            <i class="<?php echo esc_attr($icon); ?>"></i>
                        </div>
                        
                        <div class="place-details">
                            <h4 class="place-name"><?php echo esc_html($place['name']); ?></h4>
                            <div class="place-meta">
                                <span class="place-distance">
                                    <i class="fas fa-walking"></i>
                                    <?php echo esc_html($place['distance']); ?>
                                </span>
                                
                                <?php if (isset($place['rating']) && $place['rating']): ?>
                                <span class="place-rating">
                                    <i class="fas fa-star"></i>
                                    <?php echo esc_html($place['rating']); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="place-actions">
                            <button class="place-action-btn" 
                                    data-action="show-on-map" 
                                    data-place="<?php echo esc_attr($place['name']); ?>"
                                    title="Show on map">
                                <i class="fas fa-map-pin"></i>
                            </button>
                            <button class="place-action-btn" 
                                    data-action="get-directions" 
                                    data-place="<?php echo esc_attr($place['name']); ?>"
                                    title="Get directions">
                                <i class="fas fa-directions"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Quick Actions -->
                <div class="map-quick-actions">
                    <button class="hph-btn hph-btn--secondary quick-action-btn" id="transit-info-btn">
                        <i class="fas fa-bus quick-action-icon"></i>
                        Public Transit
                    </button>
                    
                    <button class="hph-btn hph-btn--secondary quick-action-btn" id="commute-times-btn">
                        <i class="fas fa-clock quick-action-icon"></i>
                        Commute Times
                    </button>
                    
                    <button class="hph-btn hph-btn--secondary quick-action-btn" id="area-stats-btn">
                        <i class="fas fa-chart-line quick-action-icon"></i>
                        Area Statistics
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>
