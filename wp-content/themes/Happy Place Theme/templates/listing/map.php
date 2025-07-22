<?php
/**
 * Map Template Part
 * 
 * Interactive Google Maps section for listings
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

namespace HappyPlace\Templates;

if (!defined('ABSPATH')) {
    exit;
}

// Extract data from template args
$listing_data = $args['data'] ?? [];
$listing_id = $args['listing_id'] ?? 0;

// Get coordinates and address
$coordinates = $listing_data['details']['coordinates'] ?? [];
$latitude = $coordinates['latitude'] ?? '';
$longitude = $coordinates['longitude'] ?? '';
$address = $listing_data['details']['full_address'] ?? '';
$price = $listing_data['core']['price'] ?? 0;

// Get nearby amenities for map markers
$nearby_amenities = $listing_data['location_intelligence']['nearby_amenities'] ?? [];

// Get walk/transit scores for display
$walk_score = $listing_data['location_intelligence']['walk_score'] ?? 0;
$transit_score = $listing_data['location_intelligence']['transit_score'] ?? 0;

// Don't render if no coordinates
if (empty($latitude) || empty($longitude)) {
    return;
}

// Get Google Maps API key
$google_maps_api_key = get_option('hph_google_maps_api_key', '');
if (empty($google_maps_api_key)) {
    return; // No API key available
}
?>

<section class="map-section" data-listing-id="<?php echo esc_attr($listing_id); ?>">
    <div class="container">
        <div class="map-header">
            <div class="header-content">
                <div class="map-info">
                    <h2 class="map-title">
                        <i class="fas fa-map-marker-alt"></i>
                        Location & Neighborhood
                    </h2>
                    <p class="map-description">Explore the area and discover nearby amenities</p>
                </div>
                <div class="map-controls">
                    <button class="map-btn active" data-view="satellite">
                        <i class="fas fa-satellite"></i>
                        Satellite
                    </button>
                    <button class="map-btn" data-view="roadmap">
                        <i class="fas fa-road"></i>
                        Map
                    </button>
                    <button class="map-btn" data-view="terrain">
                        <i class="fas fa-mountain"></i>
                        Terrain
                    </button>
                </div>
            </div>
        </div>

        <div class="map-container">
            <!-- Map Canvas -->
            <div class="map-canvas" 
                 id="listing-map"
                 data-lat="<?php echo esc_attr($latitude); ?>"
                 data-lng="<?php echo esc_attr($longitude); ?>"
                 data-address="<?php echo esc_attr($address); ?>"
                 data-price="<?php echo esc_attr($price); ?>">
                
                <!-- Loading State -->
                <div class="map-loading" id="map-loading">
                    <div class="loading-spinner"></div>
                    <p>Loading interactive map...</p>
                </div>
            </div>

            <!-- Map Sidebar -->
            <div class="map-sidebar">
                <!-- Property Info Card -->
                <div class="map-card property-card">
                    <div class="card-header">
                        <h3>Property Location</h3>
                    </div>
                    <div class="card-content">
                        <div class="property-marker">
                            <i class="fas fa-home"></i>
                            <div class="marker-info">
                                <strong><?php echo esc_html($address); ?></strong>
                                <?php if ($price): ?>
                                <span class="price">$<?php echo number_format($price); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($walk_score || $transit_score): ?>
                        <div class="mobility-scores">
                            <?php if ($walk_score): ?>
                            <div class="score-item">
                                <span class="score-icon">🚶</span>
                                <span class="score-label">Walk Score</span>
                                <span class="score-value"><?php echo $walk_score; ?>/100</span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($transit_score): ?>
                            <div class="score-item">
                                <span class="score-icon">🚌</span>
                                <span class="score-label">Transit Score</span>
                                <span class="score-value"><?php echo $transit_score; ?>/100</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Amenities Filter -->
                <?php if (!empty($nearby_amenities)): ?>
                <div class="map-card amenities-card">
                    <div class="card-header">
                        <h3>Nearby Places</h3>
                        <button class="toggle-amenities" data-action="toggle-all">
                            <i class="fas fa-eye"></i>
                            Show All
                        </button>
                    </div>
                    <div class="card-content">
                        <div class="amenity-filters">
                            <button class="filter-btn active" data-filter="all">
                                All Places
                            </button>
                            <button class="filter-btn" data-filter="restaurant">
                                <i class="fas fa-utensils"></i>
                                Dining
                            </button>
                            <button class="filter-btn" data-filter="shopping">
                                <i class="fas fa-shopping-bag"></i>
                                Shopping
                            </button>
                            <button class="filter-btn" data-filter="entertainment">
                                <i class="fas fa-ticket-alt"></i>
                                Entertainment
                            </button>
                            <button class="filter-btn" data-filter="park">
                                <i class="fas fa-tree"></i>
                                Parks
                            </button>
                        </div>
                        
                        <div class="amenities-list" id="amenities-list">
                            <?php foreach (array_slice($nearby_amenities, 0, 8) as $amenity): ?>
                            <?php
                                $amenity_name = $amenity['amenity_name'] ?? '';
                                $amenity_type = $amenity['amenity_type'] ?? '';
                                $distance = $amenity['amenity_distance'] ?? '';
                                $rating = $amenity['amenity_rating'] ?? '';
                            ?>
                            <div class="amenity-item" 
                                 data-type="<?php echo esc_attr($amenity_type); ?>"
                                 data-name="<?php echo esc_attr($amenity_name); ?>">
                                <div class="amenity-icon">
                                    <?php echo hph_get_amenity_icon($amenity_type); ?>
                                </div>
                                <div class="amenity-info">
                                    <span class="amenity-name"><?php echo esc_html($amenity_name); ?></span>
                                    <div class="amenity-meta">
                                        <?php if ($distance): ?>
                                        <span class="distance"><?php echo esc_html($distance); ?> mi</span>
                                        <?php endif; ?>
                                        <?php if ($rating): ?>
                                        <span class="rating">⭐ <?php echo esc_html($rating); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <button class="locate-btn" data-action="locate" title="Show on map">
                                    <i class="fas fa-crosshairs"></i>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Directions Card -->
                <div class="map-card directions-card">
                    <div class="card-header">
                        <h3>Get Directions</h3>
                    </div>
                    <div class="card-content">
                        <div class="directions-form">
                            <div class="input-group">
                                <input type="text" 
                                       id="directions-from" 
                                       placeholder="Enter your location"
                                       class="form-control">
                                <button class="current-location-btn" 
                                        data-action="use-current-location"
                                        title="Use current location">
                                    <i class="fas fa-location-arrow"></i>
                                </button>
                            </div>
                            <div class="directions-buttons">
                                <button class="btn btn-primary" data-action="get-directions">
                                    <i class="fas fa-directions"></i>
                                    Get Directions
                                </button>
                                <button class="btn btn-outline" data-action="open-google-maps">
                                    <i class="fab fa-google"></i>
                                    Open in Google Maps
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Hidden data for JavaScript -->
<script type="application/json" id="map-data">
{
    "property": {
        "lat": <?php echo json_encode($latitude); ?>,
        "lng": <?php echo json_encode($longitude); ?>,
        "address": <?php echo json_encode($address); ?>,
        "price": <?php echo json_encode($price); ?>
    },
    "amenities": <?php echo json_encode($nearby_amenities); ?>,
    "apiKey": <?php echo json_encode($google_maps_api_key); ?>
}
</script>

<style>
.map-section {
    background: #f8fafc;
    padding: 4rem 0;
    margin: 3rem 0;
}

/* Map Header */
.map-header {
    margin-bottom: 2rem;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 2rem;
}

.map-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--text-dark);
    margin: 0 0 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.map-title i {
    color: var(--primary-color);
    font-size: 2rem;
}

.map-description {
    font-size: 1.1rem;
    color: var(--text-muted);
    margin: 0;
}

.map-controls {
    display: flex;
    gap: 0.5rem;
    background: white;
    padding: 0.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.map-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: transparent;
    border: none;
    color: var(--text-muted);
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.map-btn:hover,
.map-btn.active {
    background: var(--primary-color);
    color: white;
}

/* Map Container */
.map-container {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 2rem;
    height: 600px;
}

.map-canvas {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.map-loading {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: white;
    z-index: 2;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #f3f4f6;
    border-left: 3px solid var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Map Sidebar */
.map-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    overflow-y: auto;
    max-height: 600px;
}

.map-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.card-header {
    padding: 1.5rem;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0;
}

.toggle-amenities {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: var(--primary-light);
    color: var(--primary-color);
    border: none;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.toggle-amenities:hover {
    background: var(--primary-color);
    color: white;
}

.card-content {
    padding: 1.5rem;
}

/* Property Card */
.property-marker {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.property-marker i {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.marker-info strong {
    display: block;
    color: var(--text-dark);
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.marker-info .price {
    color: var(--primary-color);
    font-weight: 700;
    font-size: 1.1rem;
}

.mobility-scores {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.score-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: #f9fafb;
    border-radius: 8px;
}

.score-icon {
    font-size: 1.2rem;
}

.score-label {
    font-size: 0.85rem;
    color: var(--text-muted);
    margin-right: auto;
}

.score-value {
    font-weight: 600;
    color: var(--text-dark);
}

/* Amenities Filter */
.amenity-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.filter-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: #f3f4f6;
    color: var(--text-muted);
    border: none;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.filter-btn:hover,
.filter-btn.active {
    background: var(--primary-color);
    color: white;
}

.amenities-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    max-height: 300px;
    overflow-y: auto;
}

.amenity-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    border: 1px solid #f0f0f0;
    border-radius: 8px;
    transition: all 0.2s ease;
    cursor: pointer;
}

.amenity-item:hover {
    border-color: var(--primary-color);
    background: #f8fafc;
}

.amenity-icon {
    width: 35px;
    height: 35px;
    background: var(--primary-light);
    color: var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}

.amenity-info {
    flex: 1;
}

.amenity-name {
    display: block;
    font-weight: 500;
    color: var(--text-dark);
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.amenity-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.8rem;
}

.distance {
    color: var(--primary-color);
    font-weight: 500;
}

.rating {
    color: var(--text-muted);
}

.locate-btn {
    width: 30px;
    height: 30px;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    color: var(--text-muted);
}

.locate-btn:hover {
    border-color: var(--primary-color);
    color: var(--primary-color);
    background: var(--primary-light);
}

/* Directions Card */
.directions-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.input-group {
    position: relative;
    display: flex;
}

.form-control {
    flex: 1;
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
}

.current-location-btn {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    width: 35px;
    height: 35px;
    background: var(--primary-light);
    color: var(--primary-color);
    border: none;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.current-location-btn:hover {
    background: var(--primary-color);
    color: white;
}

.directions-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
    cursor: pointer;
    border: none;
    font-size: 0.9rem;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
}

.btn-outline {
    background: white;
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}

.btn-outline:hover {
    background: var(--primary-color);
    color: white;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .map-container {
        grid-template-columns: 1fr 300px;
    }
}

@media (max-width: 768px) {
    .map-section {
        padding: 3rem 0;
    }
    
    .header-content {
        flex-direction: column;
        text-align: center;
    }
    
    .map-title {
        font-size: 2rem;
    }
    
    .map-container {
        grid-template-columns: 1fr;
        height: auto;
        gap: 1.5rem;
    }
    
    .map-canvas {
        height: 400px;
    }
    
    .map-sidebar {
        max-height: none;
        overflow-y: visible;
    }
    
    .mobility-scores {
        grid-template-columns: 1fr;
    }
    
    .directions-buttons {
        flex-direction: row;
    }
    
    .map-controls {
        flex-wrap: wrap;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .map-canvas {
        height: 300px;
    }
    
    .card-content {
        padding: 1rem;
    }
    
    .card-header {
        padding: 1rem;
    }
    
    .directions-buttons {
        flex-direction: column;
    }
    
    .amenity-filters {
        justify-content: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load Google Maps API dynamically if not already loaded
    if (typeof google === 'undefined') {
        const mapData = JSON.parse(document.getElementById('map-data').textContent);
        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${mapData.apiKey}&libraries=places&callback=initListingMap`;
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
        
        // Set callback function globally
        window.initListingMap = function() {
            if (document.querySelector('.map-section')) {
                listingMap.init();
            }
        };
    } else {
        // Google Maps already loaded
        if (document.querySelector('.map-section')) {
            listingMap.init();
        }
    }
});

const listingMap = {
    map: null,
    propertyMarker: null,
    amenityMarkers: [],
    directionsService: null,
    directionsRenderer: null,
    infoWindow: null,
    
    init() {
        const mapData = JSON.parse(document.getElementById('map-data').textContent);
        this.mapData = mapData;
        
        this.initializeMap();
        this.bindEvents();
        this.addPropertyMarker();
        this.addAmenityMarkers();
    },
    
    initializeMap() {
        const mapCanvas = document.getElementById('listing-map');
        const loading = document.getElementById('map-loading');
        
        if (!mapCanvas) return;
        
        const { property } = this.mapData;
        
        // Initialize map
        this.map = new google.maps.Map(mapCanvas, {
            center: { lat: parseFloat(property.lat), lng: parseFloat(property.lng) },
            zoom: 14,
            mapTypeId: 'satellite',
            styles: this.getMapStyles(),
            mapTypeControl: false,
            streetViewControl: true,
            fullscreenControl: true,
            zoomControl: true,
            scrollwheel: true,
            gestureHandling: 'greedy'
        });
        
        // Initialize services
        this.directionsService = new google.maps.DirectionsService();
        this.directionsRenderer = new google.maps.DirectionsRenderer({
            suppressMarkers: true,
            polylineOptions: {
                strokeColor: 'var(--primary-color)',
                strokeWeight: 4
            }
        });
        this.directionsRenderer.setMap(this.map);
        
        this.infoWindow = new google.maps.InfoWindow();
        
        // Hide loading when map is ready
        google.maps.event.addListenerOnce(this.map, 'tilesloaded', () => {
            loading.style.display = 'none';
        });
        
        // Track map interactions
        google.maps.event.addListener(this.map, 'drag', () => {
            this.trackEvent('map_dragged');
        });
        
        google.maps.event.addListener(this.map, 'zoom_changed', () => {
            this.trackEvent('map_zoomed', { zoom_level: this.map.getZoom() });
        });
    },
    
    bindEvents() {
        // Map view controls
        document.querySelectorAll('.map-btn[data-view]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const view = e.currentTarget.dataset.view;
                this.changeMapView(view, e.currentTarget);
            });
        });
        
        // Amenity filters
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const filter = e.currentTarget.dataset.filter;
                this.filterAmenities(filter, e.currentTarget);
            });
        });
        
        // Locate buttons
        document.querySelectorAll('.locate-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const amenityItem = e.currentTarget.closest('.amenity-item');
                const amenityName = amenityItem.dataset.name;
                this.locateAmenity(amenityName);
            });
        });
        
        // Directions
        document.querySelector('[data-action="get-directions"]')?.addEventListener('click', () => {
            this.getDirections();
        });
        
        document.querySelector('[data-action="use-current-location"]')?.addEventListener('click', () => {
            this.useCurrentLocation();
        });
        
        document.querySelector('[data-action="open-google-maps"]')?.addEventListener('click', () => {
            this.openInGoogleMaps();
        });
        
        // Toggle amenities visibility
        document.querySelector('[data-action="toggle-all"]')?.addEventListener('click', (e) => {
            this.toggleAllAmenities(e.currentTarget);
        });
    },
    
    addPropertyMarker() {
        const { property } = this.mapData;
        
        // Custom property marker icon
        const propertyIcon = {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                <svg width="40" height="40" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="20" cy="20" r="18" fill="var(--primary-color)" stroke="white" stroke-width="2"/>
                    <path d="M20 10 L28 16 L28 28 L12 28 L12 16 Z" fill="white"/>
                    <path d="M14 20 L26 20 M14 24 L26 24" stroke="var(--primary-color)" stroke-width="1"/>
                </svg>
            `),
            scaledSize: new google.maps.Size(40, 40),
            anchor: new google.maps.Point(20, 20)
        };
        
        this.propertyMarker = new google.maps.Marker({
            position: { lat: parseFloat(property.lat), lng: parseFloat(property.lng) },
            map: this.map,
            icon: propertyIcon,
            title: property.address,
            zIndex: 1000
        });
        
        // Property info window
        const propertyInfoContent = `
            <div class="map-info-window property-info">
                <h3>${property.address}</h3>
                ${property.price ? `<p class="price">${parseInt(property.price).toLocaleString()}</p>` : ''}
                <div class="info-actions">
                    <button class="info-btn" onclick="listingMap.getDirectionsToProperty()">
                        <i class="fas fa-directions"></i> Directions
                    </button>
                    <button class="info-btn" onclick="listingMap.streetViewProperty()">
                        <i class="fas fa-street-view"></i> Street View
                    </button>
                </div>
            </div>
        `;
        
        this.propertyMarker.addListener('click', () => {
            this.infoWindow.setContent(propertyInfoContent);
            this.infoWindow.open(this.map, this.propertyMarker);
            this.trackEvent('property_marker_clicked');
        });
    },
    
    addAmenityMarkers() {
        const { amenities } = this.mapData;
        
        if (!amenities || !Array.isArray(amenities)) return;
        
        amenities.forEach((amenity, index) => {
            const amenityType = amenity.amenity_type || 'other';
            const amenityName = amenity.amenity_name || `Amenity ${index + 1}`;
            
            // Use property coordinates + some offset as placeholder
            // In real implementation, you'd geocode the amenity addresses
            const lat = parseFloat(this.mapData.property.lat) + (Math.random() - 0.5) * 0.01;
            const lng = parseFloat(this.mapData.property.lng) + (Math.random() - 0.5) * 0.01;
            
            const amenityIcon = this.getAmenityIcon(amenityType);
            
            const marker = new google.maps.Marker({
                position: { lat, lng },
                map: this.map,
                icon: amenityIcon,
                title: amenityName,
                zIndex: 100
            });
            
            // Amenity info window
            const amenityInfoContent = `
                <div class="map-info-window amenity-info">
                    <h4>${amenityName}</h4>
                    <p class="amenity-type">${this.formatAmenityType(amenityType)}</p>
                    ${amenity.amenity_distance ? `<p class="distance">${amenity.amenity_distance} miles away</p>` : ''}
                    ${amenity.amenity_rating ? `<p class="rating">⭐ ${amenity.amenity_rating}/5</p>` : ''}
                    <button class="info-btn" onclick="listingMap.getDirectionsToAmenity('${amenityName}')">
                        <i class="fas fa-directions"></i> Directions
                    </button>
                </div>
            `;
            
            marker.addListener('click', () => {
                this.infoWindow.setContent(amenityInfoContent);
                this.infoWindow.open(this.map, marker);
                this.trackEvent('amenity_marker_clicked', { amenity_type: amenityType });
            });
            
            this.amenityMarkers.push({
                marker,
                type: amenityType,
                name: amenityName,
                data: amenity
            });
        });
    },
    
    getAmenityIcon(type) {
        const iconMap = {
            restaurant: '🍽️',
            shopping: '🛍️',
            entertainment: '🎭',
            park: '🌳',
            healthcare: '🏥',
            education: '🏫',
            fitness: '💪',
            other: '📍'
        };
        
        const emoji = iconMap[type] || iconMap.other;
        
        return {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                <svg width="30" height="30" viewBox="0 0 30 30" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="15" cy="15" r="12" fill="white" stroke="var(--primary-color)" stroke-width="2"/>
                    <text x="15" y="20" text-anchor="middle" font-size="12">${emoji}</text>
                </svg>
            `),
            scaledSize: new google.maps.Size(30, 30),
            anchor: new google.maps.Point(15, 15)
        };
    },
    
    changeMapView(view, button) {
        // Update active button
        document.querySelectorAll('.map-btn[data-view]').forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');
        
        // Change map type
        this.map.setMapTypeId(view);
        this.trackEvent('map_view_changed', { view_type: view });
    },
    
    filterAmenities(filter, button) {
        // Update active filter
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');
        
        // Show/hide markers
        this.amenityMarkers.forEach(({ marker, type }) => {
            const visible = filter === 'all' || type === filter;
            marker.setVisible(visible);
        });
        
        // Show/hide list items
        document.querySelectorAll('.amenity-item').forEach(item => {
            const itemType = item.dataset.type;
            const visible = filter === 'all' || itemType === filter;
            item.style.display = visible ? 'flex' : 'none';
        });
        
        this.trackEvent('amenity_filter_changed', { filter_type: filter });
    },
    
    locateAmenity(amenityName) {
        const amenity = this.amenityMarkers.find(a => a.name === amenityName);
        if (amenity) {
            this.map.setCenter(amenity.marker.getPosition());
            this.map.setZoom(16);
            
            // Bounce animation
            amenity.marker.setAnimation(google.maps.Animation.BOUNCE);
            setTimeout(() => {
                amenity.marker.setAnimation(null);
            }, 1400);
            
            this.trackEvent('amenity_located', { amenity_name: amenityName });
        }
    },
    
    toggleAllAmenities(button) {
        const isVisible = button.textContent.includes('Show');
        
        this.amenityMarkers.forEach(({ marker }) => {
            marker.setVisible(!isVisible);
        });
        
        // Update button text
        button.innerHTML = isVisible ? 
            '<i class="fas fa-eye-slash"></i> Hide All' : 
            '<i class="fas fa-eye"></i> Show All';
            
        this.trackEvent('amenities_toggled', { visible: !isVisible });
    },
    
    getDirections() {
        const fromInput = document.getElementById('directions-from');
        const fromAddress = fromInput.value.trim();
        
        if (!fromAddress) {
            fromInput.focus();
            return;
        }
        
        const { property } = this.mapData;
        const destination = `${property.lat},${property.lng}`;
        
        const request = {
            origin: fromAddress,
            destination: destination,
            travelMode: google.maps.TravelMode.DRIVING
        };
        
        this.directionsService.route(request, (result, status) => {
            if (status === 'OK') {
                this.directionsRenderer.setDirections(result);
                this.trackEvent('directions_requested', { 
                    from: fromAddress,
                    travel_mode: 'driving'
                });
            } else {
                alert('Could not get directions: ' + status);
            }
        });
    },
    
    useCurrentLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const userLocation = `${position.coords.latitude},${position.coords.longitude}`;
                    document.getElementById('directions-from').value = 'Current Location';
                    this.trackEvent('current_location_used');
                },
                (error) => {
                    alert('Unable to get your location: ' + error.message);
                }
            );
        } else {
            alert('Geolocation is not supported by this browser.');
        }
    },
    
    openInGoogleMaps() {
        const { property } = this.mapData;
        const url = `https://www.google.com/maps/dir/?api=1&destination=${property.lat},${property.lng}`;
        window.open(url, '_blank');
        this.trackEvent('opened_in_google_maps');
    },
    
    getDirectionsToProperty() {
        document.getElementById('directions-from').focus();
    },
    
    streetViewProperty() {
        const { property } = this.mapData;
        const streetViewUrl = `https://www.google.com/maps/@?api=1&map_action=pano&viewpoint=${property.lat},${property.lng}`;
        window.open(streetViewUrl, '_blank');
        this.trackEvent('street_view_opened');
    },
    
    getDirectionsToAmenity(amenityName) {
        const amenity = this.amenityMarkers.find(a => a.name === amenityName);
        if (amenity) {
            const position = amenity.marker.getPosition();
            const url = `https://www.google.com/maps/dir/?api=1&destination=${position.lat()},${position.lng()}`;
            window.open(url, '_blank');
            this.trackEvent('directions_to_amenity', { amenity_name: amenityName });
        }
    },
    
    formatAmenityType(type) {
        return type.charAt(0).toUpperCase() + type.slice(1).replace(/_/g, ' ');
    },
    
    getMapStyles() {
        // Custom map styling for better integration
        return [
            {
                "featureType": "poi",
                "elementType": "labels",
                "stylers": [{ "visibility": "off" }]
            },
            {
                "featureType": "road",
                "elementType": "labels.icon",
                "stylers": [{ "visibility": "off" }]
            }
        ];
    },
    
    trackEvent(eventName, parameters = {}) {
        if (typeof gtag !== 'undefined') {
            gtag('event', eventName, {
                event_category: 'interactive_map',
                event_label: window.location.pathname,
                listing_id: document.querySelector('.map-section')?.dataset.listingId,
                ...parameters
            });
        }
    }
};

// Global helper functions for info window buttons
window.listingMap = listingMap;
</script>

<?php
/**
 * Helper function to get amenity icon
 */
if (!function_exists('hph_get_amenity_icon')) {
    function hph_get_amenity_icon($type) {
        $icons = [
            'restaurant' => '<i class="fas fa-utensils"></i>',
            'shopping' => '<i class="fas fa-shopping-bag"></i>',
            'entertainment' => '<i class="fas fa-ticket-alt"></i>',
            'park' => '<i class="fas fa-tree"></i>',
            'healthcare' => '<i class="fas fa-hospital"></i>',
            'education' => '<i class="fas fa-school"></i>',
            'fitness' => '<i class="fas fa-dumbbell"></i>',
            'gas_station' => '<i class="fas fa-gas-pump"></i>',
            'grocery' => '<i class="fas fa-shopping-cart"></i>',
            'bank' => '<i class="fas fa-university"></i>',
            'other' => '<i class="fas fa-map-marker-alt"></i>'
        ];
        
        return $icons[$type] ?? $icons['other'];
    }
}