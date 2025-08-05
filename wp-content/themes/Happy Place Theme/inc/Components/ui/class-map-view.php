<?php
/**
 * Map View Component
 *
 * Interactive map component for displaying property listings with
 * clustering, filtering, and detailed popups.
 *
 * @package HappyPlace\Components\UI
 * @since 2.0.0
 */

namespace HappyPlace\Components\UI;

use HappyPlace\Components\Base_Component;

if (!defined('ABSPATH')) {
    exit;
}

class Map_View extends Base_Component {
    
    /**
     * Component name identifier
     *
     * @return string
     */
    protected function get_component_name() {
        return 'map-view';
    }
    
    /**
     * Default component properties
     *
     * @return array
     */
    protected function get_defaults() {
        return [
            // Map Configuration
            'center_lat' => 40.7128,
            'center_lng' => -74.0060,
            'zoom' => 12,
            'min_zoom' => 8,
            'max_zoom' => 18,
            'map_type' => 'roadmap', // roadmap, satellite, hybrid, terrain
            
            // Data
            'listings' => [],
            'listing_ids' => [],
            'auto_center' => true, // Auto-center based on listings
            'auto_zoom' => true,   // Auto-zoom to fit all listings
            
            // Features
            'enable_clustering' => true,
            'enable_drawing' => false,
            'enable_street_view' => true,
            'enable_fullscreen' => true,
            'enable_geolocation' => true,
            'enable_search' => true,
            
            // Clustering
            'cluster_max_zoom' => 15,
            'cluster_grid_size' => 60,
            'cluster_styles' => [],
            
            // Appearance
            'height' => '500px',
            'width' => '100%',
            'controls_position' => 'top-right', // top-left, top-right, bottom-left, bottom-right
            'show_controls' => true,
            'show_legend' => true,
            'show_listings_count' => true,
            
            // Popup/Info Window
            'popup_style' => 'card', // card, minimal, detailed
            'show_popup_images' => true,
            'show_popup_details' => true,
            'show_popup_actions' => true,
            
            // Markers
            'marker_style' => 'default', // default, custom, price-based
            'custom_marker_icon' => '',
            'price_based_colors' => [
                'low' => '#28a745',    // Green for lower prices
                'medium' => '#ffc107', // Yellow for medium prices  
                'high' => '#dc3545'    // Red for higher prices
            ],
            'price_thresholds' => [
                'low' => 500000,
                'medium' => 1000000
            ],
            
            // Behavior
            'fit_bounds_padding' => 50,
            'animation_duration' => 300,
            'popup_auto_close' => true,
            'popup_max_width' => 300,
            
            // Integration
            'sync_with_filters' => true,
            'update_url_on_move' => false,
            'load_listings_on_move' => false,
            
            // Responsive
            'mobile_height' => '400px',
            'mobile_controls' => 'minimal'
        ];
    }
    
    /**
     * Validate component properties
     */
    protected function validate_props() {
        // Validate coordinates
        $lat = $this->get_prop('center_lat');
        $lng = $this->get_prop('center_lng');
        
        if (!is_numeric($lat) || $lat < -90 || $lat > 90) {
            $this->add_validation_error('center_lat must be a valid latitude (-90 to 90)');
        }
        
        if (!is_numeric($lng) || $lng < -180 || $lng > 180) {
            $this->add_validation_error('center_lng must be a valid longitude (-180 to 180)');
        }
        
        // Validate zoom levels
        $zoom = $this->get_prop('zoom');
        $min_zoom = $this->get_prop('min_zoom');
        $max_zoom = $this->get_prop('max_zoom');
        
        if ($zoom < $min_zoom || $zoom > $max_zoom) {
            $this->add_validation_error('zoom must be between min_zoom and max_zoom');
        }
    }
    
    /**
     * Render the component
     *
     * @return string
     */
    protected function render() {
        $map_id = 'hph-map-' . uniqid();
        $classes = $this->build_css_classes();
        $config = $this->build_map_config();
        $listings_data = $this->prepare_listings_data();
        
        // Enqueue map scripts (Google Maps API)
        $this->enqueue_map_scripts();
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($classes); ?>" 
             data-component="map-view"
             data-map-id="<?php echo esc_attr($map_id); ?>">
            
            <!-- Map Controls -->
            <?php if ($this->get_prop('show_controls')): ?>
                <div class="hph-map-view__controls hph-map-view__controls--<?php echo esc_attr($this->get_prop('controls_position')); ?>">
                    
                    <!-- Search Box -->
                    <?php if ($this->get_prop('enable_search')): ?>
                        <div class="hph-map-control hph-map-control--search">
                            <input type="text" 
                                   class="hph-map-search" 
                                   placeholder="<?php esc_attr_e('Search location...', 'happy-place'); ?>"
                                   data-map-search="<?php echo esc_attr($map_id); ?>">
                        </div>
                    <?php endif; ?>
                    
                    <!-- Map Type Toggle -->
                    <div class="hph-map-control hph-map-control--type">
                        <button type="button" 
                                class="hph-map-control__button" 
                                data-action="toggle-map-type"
                                title="<?php esc_attr_e('Change map type', 'happy-place'); ?>">
                            <i class="hph-icon hph-icon--layers" aria-hidden="true"></i>
                        </button>
                    </div>
                    
                    <!-- Fullscreen Toggle -->
                    <?php if ($this->get_prop('enable_fullscreen')): ?>
                        <div class="hph-map-control hph-map-control--fullscreen">
                            <button type="button" 
                                    class="hph-map-control__button" 
                                    data-action="toggle-fullscreen"
                                    title="<?php esc_attr_e('Toggle fullscreen', 'happy-place'); ?>">
                                <i class="hph-icon hph-icon--expand" aria-hidden="true"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Geolocation -->
                    <?php if ($this->get_prop('enable_geolocation')): ?>
                        <div class="hph-map-control hph-map-control--location">
                            <button type="button" 
                                    class="hph-map-control__button" 
                                    data-action="find-location"
                                    title="<?php esc_attr_e('Find my location', 'happy-place'); ?>">
                                <i class="hph-icon hph-icon--location" aria-hidden="true"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Drawing Tools -->
                    <?php if ($this->get_prop('enable_drawing')): ?>
                        <div class="hph-map-control hph-map-control--drawing">
                            <button type="button" 
                                    class="hph-map-control__button" 
                                    data-action="toggle-drawing"
                                    title="<?php esc_attr_e('Draw search area', 'happy-place'); ?>">
                                <i class="hph-icon hph-icon--draw" aria-hidden="true"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                </div>
            <?php endif; ?>
            
            <!-- Map Container -->
            <div class="hph-map-view__container">
                <div id="<?php echo esc_attr($map_id); ?>" 
                     class="hph-map-view__map"
                     style="height: <?php echo esc_attr($this->get_prop('height')); ?>; width: <?php echo esc_attr($this->get_prop('width')); ?>;">
                </div>
                
                <!-- Loading Overlay -->
                <div class="hph-map-view__loading" id="<?php echo esc_attr($map_id); ?>-loading">
                    <div class="hph-map-loading">
                        <i class="hph-icon hph-icon--spinner hph-icon--spin" aria-hidden="true"></i>
                        <span><?php esc_html_e('Loading map...', 'happy-place'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Legend -->
            <?php if ($this->get_prop('show_legend')): ?>
                <div class="hph-map-view__legend">
                    <?php $this->render_legend(); ?>
                </div>
            <?php endif; ?>
            
            <!-- Listings Count -->
            <?php if ($this->get_prop('show_listings_count')): ?>
                <div class="hph-map-view__count">
                    <span class="hph-map-count">
                        <span class="hph-map-count__number"><?php echo count($listings_data); ?></span>
                        <span class="hph-map-count__text">
                            <?php echo _n('property', 'properties', count($listings_data), 'happy-place'); ?>
                        </span>
                    </span>
                </div>
            <?php endif; ?>
            
        </div>
        
        <!-- Map Configuration Script -->
        <script type="application/json" id="<?php echo esc_attr($map_id); ?>-config">
            <?php echo json_encode($config); ?>
        </script>
        
        <!-- Listings Data Script -->
        <script type="application/json" id="<?php echo esc_attr($map_id); ?>-listings">
            <?php echo json_encode($listings_data); ?>
        </script>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Build CSS classes
     */
    private function build_css_classes() {
        $classes = ['hph-map-view'];
        
        $classes[] = 'hph-map-view--' . str_replace('-', '_', $this->get_prop('popup_style'));
        $classes[] = 'hph-map-view--marker-' . str_replace('-', '_', $this->get_prop('marker_style'));
        
        if ($this->get_prop('enable_clustering')) {
            $classes[] = 'hph-map-view--clustering';
        }
        
        if ($this->get_prop('enable_drawing')) {
            $classes[] = 'hph-map-view--drawing';
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * Build map configuration object
     */
    private function build_map_config() {
        $config = [
            'center' => [
                'lat' => (float) $this->get_prop('center_lat'),
                'lng' => (float) $this->get_prop('center_lng')
            ],
            'zoom' => (int) $this->get_prop('zoom'),
            'minZoom' => (int) $this->get_prop('min_zoom'),
            'maxZoom' => (int) $this->get_prop('max_zoom'),
            'mapTypeId' => $this->get_prop('map_type'),
            'autoCenter' => $this->get_prop('auto_center'),
            'autoZoom' => $this->get_prop('auto_zoom'),
            'fitBoundsPadding' => (int) $this->get_prop('fit_bounds_padding'),
            'animationDuration' => (int) $this->get_prop('animation_duration'),
            
            // Features
            'enableClustering' => $this->get_prop('enable_clustering'),
            'enableDrawing' => $this->get_prop('enable_drawing'),
            'enableStreetView' => $this->get_prop('enable_street_view'),
            'enableFullscreen' => $this->get_prop('enable_fullscreen'),
            'enableGeolocation' => $this->get_prop('enable_geolocation'),
            'enableSearch' => $this->get_prop('enable_search'),
            
            // Clustering
            'clusterMaxZoom' => (int) $this->get_prop('cluster_max_zoom'),
            'clusterGridSize' => (int) $this->get_prop('cluster_grid_size'),
            'clusterStyles' => $this->get_cluster_styles(),
            
            // Popup
            'popupStyle' => $this->get_prop('popup_style'),
            'popupAutoClose' => $this->get_prop('popup_auto_close'),
            'popupMaxWidth' => (int) $this->get_prop('popup_max_width'),
            'showPopupImages' => $this->get_prop('show_popup_images'),
            'showPopupDetails' => $this->get_prop('show_popup_details'),
            'showPopupActions' => $this->get_prop('show_popup_actions'),
            
            // Markers
            'markerStyle' => $this->get_prop('marker_style'),
            'customMarkerIcon' => $this->get_prop('custom_marker_icon'),
            'priceBasedColors' => $this->get_prop('price_based_colors'),
            'priceThresholds' => $this->get_prop('price_thresholds'),
            
            // Behavior
            'syncWithFilters' => $this->get_prop('sync_with_filters'),
            'updateUrlOnMove' => $this->get_prop('update_url_on_move'),
            'loadListingsOnMove' => $this->get_prop('load_listings_on_move'),
            
            // Responsive
            'mobileHeight' => $this->get_prop('mobile_height'),
            'mobileControls' => $this->get_prop('mobile_controls')
        ];
        
        return $config;
    }
    
    /**
     * Prepare listings data for map
     */
    private function prepare_listings_data() {
        $listings = $this->get_prop('listings');
        $listing_ids = $this->get_prop('listing_ids');
        
        // If listing IDs provided but no listings data, fetch listings
        if (empty($listings) && !empty($listing_ids)) {
            $listings = $this->fetch_listings_by_ids($listing_ids);
        }
        
        $map_listings = [];
        
        foreach ($listings as $listing) {
            $listing_data = $this->prepare_single_listing($listing);
            if ($listing_data) {
                $map_listings[] = $listing_data;
            }
        }
        
        return $map_listings;
    }
    
    /**
     * Prepare single listing data for map
     */
    private function prepare_single_listing($listing) {
        // Handle different input formats (post object, array, ID)
        if (is_numeric($listing)) {
            $post = get_post($listing);
            $listing_id = $listing;
        } elseif (is_array($listing)) {
            $post = null;
            $listing_id = $listing['id'] ?? 0;
        } else {
            $post = $listing;
            $listing_id = $post->ID ?? 0;
        }
        
        if (!$listing_id) {
            return null;
        }
        
        // Get coordinates
        $lat = get_post_meta($listing_id, '_listing_latitude', true);
        $lng = get_post_meta($listing_id, '_listing_longitude', true);
        
        if (empty($lat) || empty($lng)) {
            return null; // Skip listings without coordinates
        }
        
        // Prepare listing data
        $data = [
            'id' => $listing_id,
            'lat' => (float) $lat,
            'lng' => (float) $lng,
            'title' => is_array($listing) ? ($listing['title'] ?? '') : get_the_title($listing_id),
            'price' => get_post_meta($listing_id, '_listing_price', true),
            'address' => get_post_meta($listing_id, '_listing_address', true),
            'bedrooms' => get_post_meta($listing_id, '_listing_bedrooms', true),
            'bathrooms' => get_post_meta($listing_id, '_listing_bathrooms', true),
            'square_footage' => get_post_meta($listing_id, '_listing_square_footage', true),
            'status' => get_post_meta($listing_id, '_listing_status', true) ?: 'For Sale',
            'type' => get_post_meta($listing_id, '_listing_type', true),
            'url' => get_permalink($listing_id),
            'image' => $this->get_listing_image($listing_id),
            'marker' => $this->get_marker_data($listing_id)
        ];
        
        return $data;
    }
    
    /**
     * Get listing featured image
     */
    private function get_listing_image($listing_id) {
        if (has_post_thumbnail($listing_id)) {
            return [
                'url' => get_the_post_thumbnail_url($listing_id, 'medium'),
                'alt' => get_post_meta(get_post_thumbnail_id($listing_id), '_wp_attachment_image_alt', true)
            ];
        }
        
        return null;
    }
    
    /**
     * Get marker data for listing
     */
    private function get_marker_data($listing_id) {
        $marker_style = $this->get_prop('marker_style');
        $marker = ['style' => $marker_style];
        
        switch ($marker_style) {
            case 'price-based':
                $price = (float) get_post_meta($listing_id, '_listing_price', true);
                $marker['color'] = $this->get_price_based_color($price);
                break;
                
            case 'custom':
                $marker['icon'] = $this->get_prop('custom_marker_icon');
                break;
                
            default:
                // Use default marker
                break;
        }
        
        return $marker;
    }
    
    /**
     * Get color based on price
     */
    private function get_price_based_color($price) {
        $thresholds = $this->get_prop('price_thresholds');
        $colors = $this->get_prop('price_based_colors');
        
        if ($price <= $thresholds['low']) {
            return $colors['low'];
        } elseif ($price <= $thresholds['medium']) {
            return $colors['medium'];
        } else {
            return $colors['high'];
        }
    }
    
    /**
     * Get cluster styles
     */
    private function get_cluster_styles() {
        $custom_styles = $this->get_prop('cluster_styles');
        
        if (!empty($custom_styles)) {
            return $custom_styles;
        }
        
        // Default cluster styles
        return [
            [
                'textColor' => 'white',
                'url' => '', // Will be set via CSS
                'height' => 40,
                'width' => 40,
                'className' => 'hph-cluster hph-cluster--small'
            ],
            [
                'textColor' => 'white',
                'url' => '',
                'height' => 50,
                'width' => 50,
                'className' => 'hph-cluster hph-cluster--medium'
            ],
            [
                'textColor' => 'white',
                'url' => '',
                'height' => 60,
                'width' => 60,
                'className' => 'hph-cluster hph-cluster--large'
            ]
        ];
    }
    
    /**
     * Fetch listings by IDs
     */
    private function fetch_listings_by_ids($listing_ids) {
        $args = [
            'post_type' => 'listing',
            'post__in' => $listing_ids,
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ];
        
        $query = new \WP_Query($args);
        return $query->posts;
    }
    
    /**
     * Render legend
     */
    private function render_legend() {
        $marker_style = $this->get_prop('marker_style');
        
        ?>
        <div class="hph-map-legend">
            <h4 class="hph-map-legend__title"><?php esc_html_e('Legend', 'happy-place'); ?></h4>
            
            <?php if ($marker_style === 'price-based'): ?>
                <div class="hph-map-legend__items">
                    <?php
                    $colors = $this->get_prop('price_based_colors');
                    $thresholds = $this->get_prop('price_thresholds');
                    
                    $legend_items = [
                        'low' => sprintf(__('Under $%s', 'happy-place'), number_format($thresholds['low'])),
                        'medium' => sprintf(__('$%s - $%s', 'happy-place'), number_format($thresholds['low']), number_format($thresholds['medium'])),
                        'high' => sprintf(__('Over $%s', 'happy-place'), number_format($thresholds['medium']))
                    ];
                    
                    foreach ($legend_items as $level => $label):
                    ?>
                        <div class="hph-map-legend__item">
                            <span class="hph-map-legend__marker" style="background-color: <?php echo esc_attr($colors[$level]); ?>"></span>
                            <span class="hph-map-legend__label"><?php echo esc_html($label); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="hph-map-legend__items">
                    <div class="hph-map-legend__item">
                        <span class="hph-map-legend__marker hph-map-legend__marker--default"></span>
                        <span class="hph-map-legend__label"><?php esc_html_e('Available Properties', 'happy-place'); ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Enqueue map scripts
     */
    private function enqueue_map_scripts() {
        // TODO: Add Google Maps API key from theme options
        $api_key = get_theme_mod('google_maps_api_key', '');
        
        if (!$api_key) {
            // For development, use a placeholder or load without key (limited functionality)
            $api_key = 'YOUR_API_KEY_HERE';
        }
        
        $google_maps_url = add_query_arg([
            'key' => $api_key,
            'libraries' => 'places,drawing,geometry',
            'callback' => 'initializeHappyPlaceMaps'
        ], 'https://maps.googleapis.com/maps/api/js');
        
        // Enqueue Google Maps API
        wp_enqueue_script(
            'google-maps-api',
            $google_maps_url,
            [],
            null,
            true
        );
        
        // Enqueue map component script
        wp_enqueue_script(
            'hph-map-view',
            get_template_directory_uri() . '/assets/js/components/map-view.js',
            ['google-maps-api'],
            wp_get_theme()->get('Version'),
            true
        );
        
        // Enqueue marker clusterer if clustering is enabled
        if ($this->get_prop('enable_clustering')) {
            wp_enqueue_script(
                'marker-clusterer',
                'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js',
                ['google-maps-api'],
                null,
                true
            );
        }
    }
}
