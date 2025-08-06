/**
 * Happy Place Listing Map Integration
 * 
 * @package HappyPlace
 */

class HPHListingMap {
    constructor(options) {
        console.log('Initializing map with options:', options);
        
        this.mapContainer = document.getElementById(options.containerId);
        this.markers = [];
        this.infoWindow = null;
        this.map = null;
        this.clusterer = null;
        this.properties = options.properties || [];
        this.useClusterer = options.clusterer || false;
        this.fitBounds = options.fitBounds || false;
        this.selectedListingId = null;
        this.mapStyle = [
            {
                "featureType": "administrative",
                "elementType": "labels.text.fill",
                "stylers": [{"color": "#444444"}]
            },
            {
                "featureType": "landscape",
                "elementType": "all",
                "stylers": [{"color": "#f2f2f2"}]
            },
            {
                "featureType": "poi",
                "elementType": "all",
                "stylers": [{"visibility": "off"}]
            },
            {
                "featureType": "road",
                "elementType": "all",
                "stylers": [{"saturation": -100}, {"lightness": 45}]
            },
            {
                "featureType": "road.highway",
                "elementType": "all",
                "stylers": [{"visibility": "simplified"}]
            },
            {
                "featureType": "transit",
                "elementType": "all",
                "stylers": [{"visibility": "off"}]
            },
            {
                "featureType": "water",
                "elementType": "all",
                "stylers": [{"color": "#0ea5e9"}, {"visibility": "on"}]
            }
        ];
        
        this.bounds = null;
        this.isInitialized = false;
        
        // Add new properties for tracking marker animations
        this.markerAnimationTimeouts = new Map();
        this.currentInfoWindow = null;

        // Initialize the map
        this.initialize();
    }
    
    /**
     * Initialize the map
     */
    initialize() {
        console.log('Starting map initialization');
        
        if (!this.mapContainer) {
            console.error('Map container not found');
            return;
        }
        
        if (!this.properties || !this.properties.length) {
            console.error('No properties provided for map');
            this.mapContainer.innerHTML = '<div class="hph-map-error">No properties to display</div>';
            return;
        }
        
        // Filter out properties without valid coordinates
        this.properties = this.properties.filter(property => {
            return property.latitude && property.longitude && 
                   !isNaN(parseFloat(property.latitude)) && 
                   !isNaN(parseFloat(property.longitude));
        });
        
        console.log('Filtered properties:', this.properties.length);
        
        if (!this.properties.length) {
            console.error('No properties with valid coordinates');
            this.mapContainer.innerHTML = '<div class="hph-map-error">No properties with valid locations to display</div>';
            return;
        }
        
        this.initializeMap();
    }
    
    /**
     * Initialize map with Google Maps
     */
    initializeMap() {
        console.log('Creating map instance');
        
        try {
            // Create map with custom styling and center on Sussex County, DE
            this.map = new google.maps.Map(this.mapContainer, {
                zoom: 10,
                center: { lat: 38.6851, lng: -75.3557 }, // Sussex County, DE
                styles: this.mapStyle,
                mapTypeControl: false,
                streetViewControl: true,
                fullscreenControl: true,
                zoomControl: true,
                zoomControlOptions: {
                    position: google.maps.ControlPosition.RIGHT_BOTTOM
                }
            });
            
            // Create info window
            this.infoWindow = new google.maps.InfoWindow({
                maxWidth: 320
            });
            
            // Create bounds object
            this.bounds = new google.maps.LatLngBounds();
            
            console.log('Adding markers to map');
            
            // Add markers for each property
            this.properties.forEach(property => {
                const position = new google.maps.LatLng(
                    parseFloat(property.latitude),
                    parseFloat(property.longitude)
                );
                
                const marker = new google.maps.Marker({
                    position: position,
                    map: this.map,
                    title: property.title,
                    animation: google.maps.Animation.DROP,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        fillColor: property.status === 'Sold' ? '#d32f2f' : '#4CAF50',
                        fillOpacity: 0.9,
                        strokeWeight: 1,
                        strokeColor: "#FFFFFF",
                        scale: 8
                    }
                });
                
                // Store property ID on marker
                marker.propertyId = property.id;
                
                // Add click event
                marker.addListener('click', () => {
                    this.showInfoWindow(marker, property);
                });
                
                this.markers.push(marker);
                this.bounds.extend(position);
            });
            
            // Setup marker clustering if enabled
            if (this.useClusterer && window.markerClusterer) {
                console.log('Initializing marker clusterer');
                this.clusterer = new markerClusterer.MarkerClusterer({
                    map: this.map,
                    markers: this.markers,
                    algorithm: new markerClusterer.SuperClusterAlgorithm({
                        radius: 100,
                        maxZoom: 16
                    }),
                    renderer: {
                        render: ({ count, position }) => {
                            return new google.maps.Marker({
                                position,
                                label: {
                                    text: String(count),
                                    color: "white",
                                    fontSize: "13px",
                                    fontWeight: "bold"
                                },
                                icon: {
                                    path: google.maps.SymbolPath.CIRCLE,
                                    fillColor: "#4285F4",
                                    fillOpacity: 0.9,
                                    strokeWeight: 1,
                                    strokeColor: "#FFFFFF",
                                    scale: 18
                                },
                                zIndex: Number(google.maps.Marker.MAX_ZINDEX) + count
                            });
                        }
                    }
                });
            }
            
            // Only fit to bounds if we have markers and fitBounds is enabled
            if (this.fitBounds && !this.bounds.isEmpty() && this.markers.length > 1) {
                console.log('Fitting map to bounds');
                this.map.fitBounds(this.bounds, {
                    padding: {
                        top: 50,
                        right: 50,
                        bottom: 50,
                        left: 50
                    }
                });
            } else {
                // If we're not fitting to bounds or only have one marker,
                // keep the default center and zoom level for Sussex County
                console.log('Using default Sussex County center');
            }
            
            this.isInitialized = true;
            console.log('Map initialization complete');
            
        } catch (error) {
            console.error('Error initializing map:', error);
            this.mapContainer.innerHTML = '<div class="hph-map-error">Error loading map</div>';
        }
    }
    
    /**
     * Update map with new properties without reinitializing
     * 
     * @param {Array} newProperties Array of new property data
     * @param {boolean} animate Whether to animate the marker changes
     */
    updateProperties(newProperties, animate = true) {
        console.log('Updating map properties:', newProperties.length);

        // Clear any existing animation timeouts
        this.markerAnimationTimeouts.forEach(timeout => clearTimeout(timeout));
        this.markerAnimationTimeouts.clear();

        // Close any open info window
        if (this.infoWindow) {
            this.infoWindow.close();
        }

        // Create a map of existing markers by property ID for quick lookup
        const existingMarkers = new Map();
        this.markers.forEach(marker => {
            existingMarkers.set(marker.propertyId, marker);
        });

        // Create a map of new properties by ID
        const newPropertiesMap = new Map();
        newProperties.forEach(property => {
            if (property.latitude && property.longitude && 
                !isNaN(parseFloat(property.latitude)) && 
                !isNaN(parseFloat(property.longitude))) {
                newPropertiesMap.set(property.id, property);
            }
        });

        // Remove markers that aren't in the new properties
        this.markers = this.markers.filter(marker => {
            if (!newPropertiesMap.has(marker.propertyId)) {
                if (this.clusterer) {
                    this.clusterer.removeMarker(marker);
                }
                marker.setMap(null);
                return false;
            }
            return true;
        });

        // Create or update markers
        const newMarkers = [];
        const bounds = new google.maps.LatLngBounds();
        let delay = 0;

        newProperties.forEach(property => {
            if (!property.latitude || !property.longitude || 
                isNaN(parseFloat(property.latitude)) || 
                isNaN(parseFloat(property.longitude))) {
                return;
            }

            const position = new google.maps.LatLng(
                parseFloat(property.latitude),
                parseFloat(property.longitude)
            );

            let marker = existingMarkers.get(property.id);

            if (marker) {
                // Update existing marker
                marker.setPosition(position);
                marker.setIcon({
                    path: google.maps.SymbolPath.CIRCLE,
                    fillColor: property.status === 'Sold' ? '#d32f2f' : '#4CAF50',
                    fillOpacity: 0.9,
                    strokeWeight: 1,
                    strokeColor: "#FFFFFF",
                    scale: 8
                });
            } else {
                // Create new marker
                marker = new google.maps.Marker({
                    position: position,
                    map: this.map,
                    title: property.title,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        fillColor: property.status === 'Sold' ? '#d32f2f' : '#4CAF50',
                        fillOpacity: 0.9,
                        strokeWeight: 1,
                        strokeColor: "#FFFFFF",
                        scale: 8
                    }
                });

                marker.propertyId = property.id;

                // Add click event
                marker.addListener('click', () => {
                    this.showInfoWindow(marker, property);
                });

                if (animate) {
                    marker.setOpacity(0);
                    this.markerAnimationTimeouts.set(property.id, setTimeout(() => {
                        marker.setAnimation(google.maps.Animation.DROP);
                        marker.setOpacity(1);
                    }, delay));
                    delay += 50;
                }
            }

            newMarkers.push(marker);
            bounds.extend(position);
        });

        // Update clusterer if enabled
        if (this.clusterer) {
            this.clusterer.clearMarkers();
            this.clusterer.addMarkers(newMarkers);
        }

        // Update class properties
        this.markers = newMarkers;
        this.properties = newProperties;
        this.bounds = bounds;

        // Update map viewport
        if (!bounds.isEmpty() && this.markers.length > 1) {
            this.map.fitBounds(bounds, {
                padding: {
                    top: 50,
                    right: 50,
                    bottom: 50,
                    left: 50
                }
            });
        } else if (this.markers.length === 1) {
            this.map.setCenter(this.markers[0].getPosition());
            this.map.setZoom(15);
        }
    }

    /**
     * Clear all markers from the map
     */
    clearMarkers() {
        this.markers.forEach(marker => {
            marker.setMap(null);
        });
        
        if (this.clusterer) {
            this.clusterer.clearMarkers();
        }
        
        this.markers = [];
        this.properties = [];
        
        if (this.infoWindow) {
            this.infoWindow.close();
        }
    }
    
    /**
     * Show info window for property
     * 
     * @param {google.maps.Marker} marker Map marker
     * @param {Object} property Property data
     */
    showInfoWindow(marker, property) {
        // Format status for display
        const statusClass = property.status === 'Sold' ? 'sold' : 'active';
        const statusDisplay = property.status || 'Active';

        // Create content with more detailed structure
        const content = `
            <div class="hph-map-info-window">
                <div class="hph-info-image">
                    ${property.photo ? `
                        <a href="${property.permalink}">
                            <img src="${property.photo}" alt="${property.title}" loading="lazy">
                        </a>
                    ` : `
                        <div class="hph-info-no-image">
                            <i class="fas fa-home"></i>
                        </div>
                    `}
                    <div class="hph-info-status ${statusClass}">${statusDisplay}</div>
                </div>
                <div class="hph-info-content">
                    <h3 class="hph-info-title">
                        <a href="${property.permalink}">${property.title}</a>
                    </h3>
                    <div class="hph-info-price">
                        ${property.price ? `$${this.formatNumber(property.price)}` : 'Price Upon Request'}
                    </div>
                    <div class="hph-info-address" title="${property.address}">
                        ${property.address}
                    </div>
                    <div class="hph-info-details">
                        ${property.bedrooms ? `
                            <span class="hph-info-beds" title="${property.bedrooms} Bedrooms">
                                <i class="fas fa-bed"></i>${property.bedrooms} bd
                            </span>
                        ` : ''}
                        ${property.bathrooms ? `
                            <span class="hph-info-baths" title="${property.bathrooms} Bathrooms">
                                <i class="fas fa-bath"></i>${property.bathrooms} ba
                            </span>
                        ` : ''}
                        ${property.square_footage ? `
                            <span class="hph-info-sqft" title="${this.formatNumber(property.square_footage)} Square Feet">
                                <i class="fas fa-ruler-combined"></i>${this.formatNumber(property.square_footage)} sf
                            </span>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
        
        // Close any open info window
        if (this.infoWindow) {
            this.infoWindow.close();
        }
        
        // Create new info window with fixed width and pixelOffset for better positioning
        this.infoWindow = new google.maps.InfoWindow({
            content: content,
            maxWidth: 320,
            pixelOffset: new google.maps.Size(0, -30)
        });
        
        // Open the info window
        this.infoWindow.open(this.map, marker);
        
        // Track this window as the currently open one
        this.currentInfoWindow = this.infoWindow;
    }
    
    /**
     * Format number with commas
     * 
     * @param {number} number Number to format
     * @returns {string} Formatted number
     */
    formatNumber(number) {
        return number ? number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") : '';
    }
    
    /**
     * Highlight marker for property
     * 
     * @param {number} propertyId Property ID
     */
    highlightProperty(propertyId) {
        // Reset all markers
        this.markers.forEach(marker => {
            marker.setAnimation(null);
        });
        
        // Find and highlight the matching marker
        const marker = this.markers.find(marker => marker.propertyId === propertyId);
        if (marker) {
            this.map.panTo(marker.getPosition());
            marker.setAnimation(google.maps.Animation.BOUNCE);
            
            // Stop animation after a short time
            setTimeout(() => {
                marker.setAnimation(null);
            }, 1400);
        }
    }
}

// Initialize map when document is ready
jQuery(document).ready(function($) {
    // Get map containers
    const mapContainers = document.querySelectorAll('.hph-listings-map');
    
    if (mapContainers.length === 0) {
        console.log('No map containers found on page');
        return;
    }
    
    mapContainers.forEach(container => {
        // Check if container has properties data
        if (container.dataset.properties) {
            try {
                // Parse properties data
                const properties = JSON.parse(container.dataset.properties);
                
                // Log properties data in debug mode
                if (window.location.search.includes('debug=1')) {
                    console.log('Properties data:', properties);
                }

                // Validate properties data
                if (!Array.isArray(properties)) {
                    console.error('Invalid properties data:', properties);
                    container.innerHTML = '<div class="hph-map-error">Error: Invalid property data</div>';
                    return;
                }

                // Filter out invalid properties
                const validProperties = properties.filter(prop => {
                    const isValid = prop && 
                        typeof prop === 'object' &&
                        prop.latitude && 
                        prop.longitude &&
                        !isNaN(parseFloat(prop.latitude)) &&
                        !isNaN(parseFloat(prop.longitude));
                    
                    if (!isValid && window.location.search.includes('debug=1')) {
                        console.warn('Invalid property:', prop);
                    }
                    return isValid;
                });

                if (validProperties.length === 0) {
                    console.warn('No valid properties to display on map');
                    container.innerHTML = '<div class="hph-map-error">No properties with valid locations to display</div>';
                    return;
                }

                // Create map instance
                const map = new HPHListingMap({
                    containerId: container.id,
                    apiKey: window.hphMapConfig?.apiKey || '',
                    properties: validProperties,
                    useClusterer: container.dataset.clusterer === 'true',
                    fitBounds: container.dataset.fitBounds === 'true',
                    callbacks: {
                        onMarkerClick: (propertyId) => {
                            // Highlight property in sidebar
                            const listingCards = document.querySelectorAll('.hph-map-listing-card');
                            
                            listingCards.forEach(card => {
                                const cardId = parseInt(card.dataset.listingId, 10);
                                
                                if (cardId === propertyId) {
                                    card.classList.add('highlighted');
                                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                } else {
                                    card.classList.remove('highlighted');
                                }
                            });
                        },
                        onInfoWindowClose: () => {
                            // Remove all highlights
                            document.querySelectorAll('.hph-map-listing-card').forEach(card => {
                                card.classList.remove('highlighted');
                            });
                        }
                    }
                });
                
                // Store map instance in container
                container.hphMap = map;
                
                // Add click event to listing cards
                document.querySelectorAll('.hph-map-listing-card').forEach(card => {
                    card.addEventListener('click', () => {
                        const listingId = parseInt(card.dataset.listingId, 10);
                        if (map) {
                            map.highlightProperty(listingId);
                        }
                    });
                });
            } catch (error) {
                console.error('Error initializing map:', error);
            }
        } else {
            console.warn('No properties data found for map container:', container.id);
        }
    });
    
    // Initialize map filter toggle
    const filterHeader = document.querySelector('.hph-filters-header');
    const filterSection = document.querySelector('.hph-map-filters');
    
    if (filterHeader && filterSection) {
        // Start collapsed by default
        filterSection.classList.add('collapsed');
        
        filterHeader.addEventListener('click', function() {
            filterSection.classList.toggle('collapsed');
        });
        
        // Toggle button click
        const toggleBtn = document.querySelector('.hph-filters-toggle-btn');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent firing the header click event
                filterSection.classList.toggle('collapsed');
            });
        }
    }
});