(function($) {
    'use strict';

    const mapStyles = [
        // Add your custom map styles here
    ];

    function initMaps() {
        // Initialize archive map
        const archiveMap = document.getElementById('listingsMap');
        if (archiveMap) {
            initArchiveMap(archiveMap);
        }

        // Initialize single listing map
        const propertyMap = document.getElementById('property-map');
        if (propertyMap) {
            initPropertyMap(propertyMap);
        }
    }

    function initArchiveMap(element) {
        const markers = JSON.parse(element.dataset.markers || '[]');
        if (!markers.length) return;

        const map = new google.maps.Map(element, {
            zoom: 12,
            styles: mapStyles,
            mapTypeControl: false,
            fullscreenControl: true,
            streetViewControl: true
        });

        const bounds = new google.maps.LatLngBounds();
        const markerObjects = [];

        markers.forEach(markerData => {
            const position = new google.maps.LatLng(markerData.lat, markerData.lng);
            bounds.extend(position);

            const marker = new google.maps.Marker({
                position,
                map,
                icon: happyplace.markerIcon || null,
                title: markerData.title
            });

            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div class="hph-map-info">
                        <h4>${markerData.title}</h4>
                        <p>${markerData.address}</p>
                        <p><strong>$${Number(markerData.price).toLocaleString()}</strong></p>
                        <p>${markerData.beds} bd | ${markerData.baths} ba | ${Number(markerData.sqft).toLocaleString()} sq ft</p>
                        <a href="${markerData.url}" class="hph-btn hph-btn--primary">View Details</a>
                    </div>
                `
            });

            marker.addListener('click', () => {
                infoWindow.open(map, marker);
                highlightListing(markerData.id);
            });

            markerObjects.push(marker);
        });

        if (element.dataset.fitBounds === 'true') {
            map.fitBounds(bounds);
        }

        if (element.dataset.clusterer === 'true' && window.MarkerClusterer) {
            new MarkerClusterer(map, markerObjects, {
                imagePath: 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m'
            });
        }
    }

    function initPropertyMap(element) {
        const lat = parseFloat(element.dataset.lat);
        const lng = parseFloat(element.dataset.lng);
        const title = element.dataset.title;
        const address = element.dataset.address;

        if (!lat || !lng) return;

        const map = new google.maps.Map(element, {
            center: { lat, lng },
            zoom: 15,
            styles: mapStyles,
            mapTypeControl: false
        });

        const marker = new google.maps.Marker({
            position: { lat, lng },
            map,
            icon: happyplace.markerIcon || null,
            title
        });

        const infoWindow = new google.maps.InfoWindow({
            content: `
                <div class="hph-map-info">
                    <h4>${title}</h4>
                    <p>${address}</p>
                </div>
            `
        });

        marker.addListener('click', () => {
            infoWindow.open(map, marker);
        });

        setupNearbyPlaces(map, { lat, lng });
    }

    function setupNearbyPlaces(map, location) {
        const service = new google.maps.places.PlacesService(map);
        let activeMarkers = [];

        $('.hph-nearby-places-btn').on('click', function() {
            const type = $(this).data('type');
            $('.hph-nearby-places-btn').removeClass('active');
            $(this).addClass('active');

            // Clear existing markers
            activeMarkers.forEach(marker => marker.setMap(null));
            activeMarkers = [];

            service.nearbySearch({
                location,
                radius: 1500,
                type
            }, (results, status) => {
                if (status === google.maps.places.PlacesServiceStatus.OK) {
                    results.forEach(place => {
                        const marker = new google.maps.Marker({
                            map,
                            position: place.geometry.location,
                            title: place.name,
                            animation: google.maps.Animation.DROP
                        });

                        const infoWindow = new google.maps.InfoWindow({
                            content: `
                                <div class="hph-map-info">
                                    <h4>${place.name}</h4>
                                    <p>${place.vicinity}</p>
                                    ${place.rating ? `<p>Rating: ${place.rating} ⭐</p>` : ''}
                                </div>
                            `
                        });

                        marker.addListener('click', () => {
                            infoWindow.open(map, marker);
                        });

                        activeMarkers.push(marker);
                    });
                }
            });
        });
    }

    function highlightListing(id) {
        $('.hph-map-listing-card').removeClass('active');
        $(`.hph-map-listing-card[data-listing-id="${id}"]`)
            .addClass('active')
            .get(0)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // Initialize on document ready
    $(document).ready(initMaps);

})(jQuery);
