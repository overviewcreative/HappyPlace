/**
 * Happy Place Plugin - Map JavaScript Entry Point
 * 
 * This file serves as the main entry point for map-related functionality
 * that needs to be available across both plugin admin and theme frontend.
 */

// Core map functionality that can be used in admin and frontend
const HPH_Maps = {
    /**
     * Map instances storage
     */
    maps: {},
    
    /**
     * Default map configuration
     */
    defaultConfig: {
        zoom: 12,
        center: { lat: 33.7490, lng: -84.3880 }, // Atlanta, GA default
        styles: [
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
                "stylers": [{"color": "#46bcec"}, {"visibility": "on"}]
            }
        ]
    },

    /**
     * Initialize map functionality
     */
    init: function() {
        console.log('HPH Maps module initialized');
        this.loadGoogleMaps();
        this.bindEvents();
    },

    /**
     * Load Google Maps API if not already loaded
     */
    loadGoogleMaps: function() {
        if (typeof google !== 'undefined' && google.maps) {
            this.initializeMaps();
            return;
        }

        // Check if API key is available
        const apiKey = this.getGoogleMapsApiKey();
        if (!apiKey) {
            console.warn('Google Maps API key not found');
            return;
        }

        // Load Google Maps API
        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places,geometry&callback=HPH_Maps.initializeMaps`;
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    },

    /**
     * Get Google Maps API key from WordPress localized data
     */
    getGoogleMapsApiKey: function() {
        // Try to get from WordPress localized data
        if (typeof hph_maps_data !== 'undefined' && hph_maps_data.google_maps_api_key) {
            return hph_maps_data.google_maps_api_key;
        }
        
        // Fallback to data attribute
        const mapContainer = document.querySelector('[data-google-maps-key]');
        if (mapContainer) {
            return mapContainer.getAttribute('data-google-maps-key');
        }
        
        return null;
    },

    /**
     * Initialize all maps on the page
     */
    initializeMaps: function() {
        console.log('Initializing maps...');
        
        // Initialize single listing maps
        document.querySelectorAll('.hph-single-listing-map').forEach(container => {
            this.initSingleListingMap(container);
        });

        // Initialize listing archive maps
        document.querySelectorAll('.hph-listings-map').forEach(container => {
            this.initListingsMap(container);
        });

        // Initialize admin maps
        document.querySelectorAll('.hph-admin-map').forEach(container => {
            this.initAdminMap(container);
        });

        // Initialize neighborhood maps
        document.querySelectorAll('.hph-neighborhood-map').forEach(container => {
            this.initNeighborhoodMap(container);
        });
    },

    /**
     * Bind map-related events
     */
    bindEvents: function() {
        // Map style toggle
        jQuery(document).on('click', '.map-style-toggle', this.handleStyleToggle.bind(this));
        
        // Map zoom controls
        jQuery(document).on('click', '.map-zoom-in', this.handleZoomIn.bind(this));
        jQuery(document).on('click', '.map-zoom-out', this.handleZoomOut.bind(this));
        
        // Map fullscreen toggle
        jQuery(document).on('click', '.map-fullscreen', this.handleFullscreen.bind(this));
        
        // Location search
        jQuery(document).on('submit', '.map-location-search', this.handleLocationSearch.bind(this));
    },

    /**
     * Initialize single listing map
     */
    initSingleListingMap: function(container) {
        const listingData = this.getListingData(container);
        if (!listingData.lat || !listingData.lng) {
            console.warn('No coordinates found for listing map');
            return;
        }

        const mapId = container.id || 'single-listing-map-' + Date.now();
        const mapConfig = Object.assign({}, this.defaultConfig, {
            center: { lat: parseFloat(listingData.lat), lng: parseFloat(listingData.lng) },
            zoom: 15
        });

        const map = new google.maps.Map(container, mapConfig);
        
        // Add listing marker
        const marker = new google.maps.Marker({
            position: mapConfig.center,
            map: map,
            title: listingData.title || 'Property Location',
            icon: this.getCustomMarkerIcon('listing')
        });

        // Add info window
        const infoWindow = new google.maps.InfoWindow({
            content: this.createListingInfoWindow(listingData)
        });

        marker.addListener('click', () => {
            infoWindow.open(map, marker);
        });

        this.maps[mapId] = { map, marker, infoWindow };
    },

    /**
     * Initialize listings archive map
     */
    initListingsMap: function(container) {
        const listings = this.getListingsData(container);
        if (!listings.length) {
            console.warn('No listings data found for map');
            return;
        }

        const mapId = container.id || 'listings-map-' + Date.now();
        const map = new google.maps.Map(container, this.defaultConfig);
        
        const markers = [];
        const bounds = new google.maps.LatLngBounds();

        // Add markers for each listing
        listings.forEach(listing => {
            if (!listing.lat || !listing.lng) return;

            const position = { lat: parseFloat(listing.lat), lng: parseFloat(listing.lng) };
            const marker = new google.maps.Marker({
                position: position,
                map: map,
                title: listing.title,
                icon: this.getCustomMarkerIcon('listing')
            });

            const infoWindow = new google.maps.InfoWindow({
                content: this.createListingInfoWindow(listing)
            });

            marker.addListener('click', () => {
                infoWindow.open(map, marker);
                this.highlightListingCard(listing.id);
            });

            markers.push(marker);
            bounds.extend(position);
        });

        // Fit map to show all markers
        if (markers.length > 1) {
            map.fitBounds(bounds);
        } else if (markers.length === 1) {
            map.setCenter(markers[0].getPosition());
            map.setZoom(15);
        }

        this.maps[mapId] = { map, markers, bounds };
    },

    /**
     * Initialize admin map for listing editing
     */
    initAdminMap: function(container) {
        const mapId = container.id || 'admin-map-' + Date.now();
        const map = new google.maps.Map(container, this.defaultConfig);
        
        let marker = null;
        const addressInput = document.querySelector('#listing-address');
        
        // Add click listener to set marker
        map.addListener('click', (event) => {
            this.setAdminMarker(map, event.latLng, marker);
            this.updateAddressFromCoordinates(event.latLng, addressInput);
        });

        // Add places autocomplete to address input
        if (addressInput) {
            const autocomplete = new google.maps.places.Autocomplete(addressInput);
            autocomplete.addListener('place_changed', () => {
                const place = autocomplete.getPlace();
                if (place.geometry) {
                    map.setCenter(place.geometry.location);
                    map.setZoom(15);
                    this.setAdminMarker(map, place.geometry.location, marker);
                }
            });
        }

        this.maps[mapId] = { map, marker };
    },

    /**
     * Initialize neighborhood map
     */
    initNeighborhoodMap: function(container) {
        const neighborhoodData = this.getNeighborhoodData(container);
        const mapId = container.id || 'neighborhood-map-' + Date.now();
        
        const mapConfig = Object.assign({}, this.defaultConfig, {
            center: neighborhoodData.center || this.defaultConfig.center,
            zoom: neighborhoodData.zoom || 13
        });

        const map = new google.maps.Map(container, mapConfig);

        // Add neighborhood boundary if available
        if (neighborhoodData.boundary) {
            const polygon = new google.maps.Polygon({
                paths: neighborhoodData.boundary,
                strokeColor: '#0066CC',
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: '#0066CC',
                fillOpacity: 0.2
            });
            polygon.setMap(map);
        }

        this.maps[mapId] = { map };
    },

    /**
     * Get listing data from container
     */
    getListingData: function(container) {
        return {
            id: container.dataset.listingId,
            title: container.dataset.listingTitle,
            lat: container.dataset.lat,
            lng: container.dataset.lng,
            price: container.dataset.price,
            address: container.dataset.address,
            image: container.dataset.image
        };
    },

    /**
     * Get listings data from container
     */
    getListingsData: function(container) {
        try {
            const listingsJson = container.dataset.listings;
            return listingsJson ? JSON.parse(listingsJson) : [];
        } catch (e) {
            console.error('Error parsing listings data:', e);
            return [];
        }
    },

    /**
     * Get neighborhood data from container
     */
    getNeighborhoodData: function(container) {
        try {
            const neighborhoodJson = container.dataset.neighborhood;
            return neighborhoodJson ? JSON.parse(neighborhoodJson) : {};
        } catch (e) {
            console.error('Error parsing neighborhood data:', e);
            return {};
        }
    },

    /**
     * Create custom marker icon
     */
    getCustomMarkerIcon: function(type) {
        const icons = {
            listing: {
                url: '/wp-content/plugins/Happy Place Plugin/assets/images/marker-listing.png',
                scaledSize: new google.maps.Size(40, 40),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(20, 40)
            },
            sold: {
                url: '/wp-content/plugins/Happy Place Plugin/assets/images/marker-sold.png',
                scaledSize: new google.maps.Size(40, 40),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(20, 40)
            }
        };

        return icons[type] || icons.listing;
    },

    /**
     * Create info window content for listing
     */
    createListingInfoWindow: function(listing) {
        return `
            <div class="hph-map-info-window">
                ${listing.image ? `<img src="${listing.image}" alt="${listing.title}" class="listing-image">` : ''}
                <h4>${listing.title}</h4>
                ${listing.price ? `<p class="price">${listing.price}</p>` : ''}
                ${listing.address ? `<p class="address">${listing.address}</p>` : ''}
                <a href="/listing/${listing.id}" class="view-listing">View Details</a>
            </div>
        `;
    },

    /**
     * Highlight corresponding listing card
     */
    highlightListingCard: function(listingId) {
        document.querySelectorAll('.listing-card').forEach(card => {
            card.classList.remove('highlighted');
        });
        
        const targetCard = document.querySelector(`[data-listing-id="${listingId}"]`);
        if (targetCard) {
            targetCard.classList.add('highlighted');
            targetCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    },

    /**
     * Set marker in admin map
     */
    setAdminMarker: function(map, position, currentMarker) {
        if (currentMarker) {
            currentMarker.setMap(null);
        }

        const marker = new google.maps.Marker({
            position: position,
            map: map,
            draggable: true
        });

        // Update hidden form fields
        document.querySelector('#listing-latitude').value = position.lat();
        document.querySelector('#listing-longitude').value = position.lng();

        return marker;
    },

    /**
     * Update address from coordinates using reverse geocoding
     */
    updateAddressFromCoordinates: function(latLng, addressInput) {
        const geocoder = new google.maps.Geocoder();
        
        geocoder.geocode({ location: latLng }, (results, status) => {
            if (status === 'OK' && results[0] && addressInput) {
                addressInput.value = results[0].formatted_address;
            }
        });
    },

    /**
     * Handle map style toggle
     */
    handleStyleToggle: function(e) {
        e.preventDefault();
        const button = e.currentTarget;
        const mapId = button.dataset.mapId;
        const map = this.maps[mapId]?.map;
        
        if (map) {
            const currentStyle = map.getMapTypeId();
            const newStyle = currentStyle === 'roadmap' ? 'satellite' : 'roadmap';
            map.setMapTypeId(newStyle);
        }
    },

    /**
     * Handle zoom in
     */
    handleZoomIn: function(e) {
        e.preventDefault();
        const button = e.currentTarget;
        const mapId = button.dataset.mapId;
        const map = this.maps[mapId]?.map;
        
        if (map) {
            map.setZoom(map.getZoom() + 1);
        }
    },

    /**
     * Handle zoom out
     */
    handleZoomOut: function(e) {
        e.preventDefault();
        const button = e.currentTarget;
        const mapId = button.dataset.mapId;
        const map = this.maps[mapId]?.map;
        
        if (map) {
            map.setZoom(map.getZoom() - 1);
        }
    },

    /**
     * Handle fullscreen toggle
     */
    handleFullscreen: function(e) {
        e.preventDefault();
        const button = e.currentTarget;
        const mapContainer = button.closest('.map-container');
        
        if (mapContainer.classList.contains('fullscreen')) {
            mapContainer.classList.remove('fullscreen');
            button.textContent = 'Fullscreen';
        } else {
            mapContainer.classList.add('fullscreen');
            button.textContent = 'Exit Fullscreen';
        }
    },

    /**
     * Handle location search
     */
    handleLocationSearch: function(e) {
        e.preventDefault();
        const form = e.currentTarget;
        const searchInput = form.querySelector('.location-search-input');
        const mapId = form.dataset.mapId;
        const map = this.maps[mapId]?.map;
        
        if (!map || !searchInput.value) return;

        const geocoder = new google.maps.Geocoder();
        geocoder.geocode({ address: searchInput.value }, (results, status) => {
            if (status === 'OK' && results[0]) {
                map.setCenter(results[0].geometry.location);
                map.setZoom(15);
            } else {
                alert('Location not found. Please try a different search term.');
            }
        });
    }
};

// Make functions available globally for Google Maps callbacks
window.HPH_Maps = HPH_Maps;

// Initialize when DOM is ready
jQuery(document).ready(function() {
    HPH_Maps.init();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = HPH_Maps;
}
