/**
 * City Admin Interface JavaScript
 * 
 * Handles Google API integration for city data management
 */

jQuery(document).ready(function($) {
    'use strict';

    // Initialize city admin features
    initCityAdmin();

    function initCityAdmin() {
        // Add control buttons to city fields
        addCityApiControls();
        
        // Handle places source toggle
        handlePlacesSourceToggle();
        
        // Monitor map field changes for auto-population
        monitorMapFieldChanges();
    }

    /**
     * Add API control buttons to relevant city fields
     */
    function addCityApiControls() {
        // Add geocoding button to map field
        const mapField = $('[data-name="city_google_map"]');
        if (mapField.length) {
            const geocodeButton = $(`
                <div class="hph-city-api-controls" style="margin: 10px 0;">
                    <button type="button" class="button hph-geocode-city" data-loading-text="Geocoding...">
                        📍 Geocode City Location
                    </button>
                    <p class="description">Automatically find coordinates for this city</p>
                </div>
            `);
            mapField.find('.acf-input').prepend(geocodeButton);
        }

        // Add places refresh button
        const placesField = $('[data-name="places_api"]');
        if (placesField.length) {
            const refreshButton = $(`
                <div class="hph-city-api-controls" style="margin: 10px 0;">
                    <button type="button" class="button hph-refresh-places" data-loading-text="Refreshing...">
                        🔄 Refresh Places from Google API
                    </button>
                    <p class="description">Update places data from Google Places API</p>
                </div>
            `);
            placesField.find('.acf-input').prepend(refreshButton);
        }

        // Bind button events
        $(document).on('click', '.hph-geocode-city', handleGeocodeCity);
        $(document).on('click', '.hph-refresh-places', handleRefreshPlaces);
    }

    /**
     * Handle places source toggle functionality
     */
    function handlePlacesSourceToggle() {
        const placesSourceField = $('[data-name="places_source"]');
        
        if (placesSourceField.length) {
            // Monitor changes to places source
            placesSourceField.on('change', 'input[type="radio"]', function() {
                const selectedValue = $(this).val();
                
                if (selectedValue === 'api') {
                    // Show auto-population notice
                    showNotice('API mode selected. Places will be auto-populated from Google Places API when you save.', 'info');
                    
                    // Trigger refresh if coordinates are available
                    const mapData = getMapFieldData();
                    if (mapData && mapData.lat && mapData.lng) {
                        setTimeout(() => {
                            $('.hph-refresh-places').trigger('click');
                        }, 1000);
                    }
                } else {
                    showNotice('Manual mode selected. Choose places from your Local Places library.', 'info');
                }
            });
        }
    }

    /**
     * Monitor Google Map field changes
     */
    function monitorMapFieldChanges() {
        // Listen for ACF map field updates
        $(document).on('acf/fields/google_map/changed', function(e, $field) {
            if ($field.data('name') === 'city_google_map') {
                const placesSource = $('[data-name="places_source"] input:checked').val();
                
                if (placesSource === 'api') {
                    showNotice('Map location updated. Refreshing places data...', 'info');
                    
                    setTimeout(() => {
                        $('.hph-refresh-places').trigger('click');
                    }, 1500);
                }
            }
        });
    }

    /**
     * Handle geocode city button click
     */
    function handleGeocodeCity(e) {
        e.preventDefault();
        
        const $button = $(this);
        const originalText = $button.text();
        const loadingText = $button.data('loading-text');
        
        // Set loading state
        $button.text(loadingText).prop('disabled', true);
        
        // Make AJAX request
        $.ajax({
            url: hph_city_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'hph_geocode_city',
                nonce: hph_city_ajax.nonce,
                post_id: hph_city_ajax.post_id
            },
            success: function(response) {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    
                    // Update map field if coordinates were found
                    if (response.data.coordinates) {
                        updateMapField(response.data.coordinates);
                    }
                    
                    // Refresh page to show updated data
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showNotice('Geocoding failed: ' + (response.data || 'Unknown error'), 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotice('Error: ' + error, 'error');
            },
            complete: function() {
                // Reset button state
                $button.text(originalText).prop('disabled', false);
            }
        });
    }

    /**
     * Handle refresh places button click
     */
    function handleRefreshPlaces(e) {
        e.preventDefault();
        
        const $button = $(this);
        const originalText = $button.text();
        const loadingText = $button.data('loading-text');
        
        // Check if coordinates are available
        const mapData = getMapFieldData();
        if (!mapData || !mapData.lat || !mapData.lng) {
            showNotice('Please set the city location on the map first.', 'warning');
            return;
        }
        
        // Set loading state
        $button.text(loadingText).prop('disabled', true);
        
        // Make AJAX request
        $.ajax({
            url: hph_city_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'hph_refresh_city_places',
                nonce: hph_city_ajax.nonce,
                post_id: hph_city_ajax.post_id
            },
            success: function(response) {
                if (response.success) {
                    showNotice(response.data || 'Places refreshed successfully!', 'success');
                    
                    // Refresh page to show updated data
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showNotice('Refresh failed: ' + (response.data || 'Unknown error'), 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotice('Error: ' + error, 'error');
            },
            complete: function() {
                // Reset button state
                $button.text(originalText).prop('disabled', false);
            }
        });
    }

    /**
     * Get current map field data
     */
    function getMapFieldData() {
        const mapField = $('[data-name="city_google_map"]');
        
        if (mapField.length) {
            const mapInput = mapField.find('input[type="hidden"]');
            
            if (mapInput.length && mapInput.val()) {
                try {
                    return JSON.parse(mapInput.val());
                } catch (e) {
                    console.warn('Error parsing map data:', e);
                }
            }
        }
        
        return null;
    }

    /**
     * Update map field with new coordinates
     */
    function updateMapField(coordinates) {
        const mapField = $('[data-name="city_google_map"]');
        
        if (mapField.length) {
            const currentData = getMapFieldData() || {};
            const newData = {
                ...currentData,
                lat: coordinates.lat,
                lng: coordinates.lng,
                zoom: currentData.zoom || 12
            };
            
            const mapInput = mapField.find('input[type="hidden"]');
            if (mapInput.length) {
                mapInput.val(JSON.stringify(newData)).trigger('change');
            }
        }
    }

    /**
     * Show admin notice
     */
    function showNotice(message, type = 'info') {
        const noticeClass = type === 'error' ? 'notice-error' : 
                           type === 'warning' ? 'notice-warning' :
                           type === 'success' ? 'notice-success' : 'notice-info';
        
        const $notice = $(`
            <div class="notice ${noticeClass} is-dismissible hph-city-notice">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `);
        
        // Remove existing notices
        $('.hph-city-notice').remove();
        
        // Add new notice
        $('.wrap > h1').after($notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            $notice.fadeOut(() => $notice.remove());
        }, 5000);
        
        // Handle dismiss button
        $notice.on('click', '.notice-dismiss', function() {
            $notice.fadeOut(() => $notice.remove());
        });
    }

    /**
     * Auto-save functionality for city data
     */
    function initAutoSave() {
        // Auto-save when key fields change
        const keyFields = [
            '[data-name="city_google_map"]',
            '[data-name="places_source"]'
        ];
        
        keyFields.forEach(selector => {
            $(document).on('change', selector + ' input', function() {
                // Debounced auto-save
                clearTimeout(window.hphCityAutoSaveTimeout);
                window.hphCityAutoSaveTimeout = setTimeout(() => {
                    if (typeof acf !== 'undefined' && acf.form) {
                        acf.form.submit();
                    }
                }, 2000);
            });
        });
    }

    // Initialize auto-save
    initAutoSave();
});
