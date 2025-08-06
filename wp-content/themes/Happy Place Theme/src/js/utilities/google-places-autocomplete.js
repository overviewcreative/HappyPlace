/**
 * Happy Place Address Autocomplete
 * 
 * @package HappyPlace
 */

jQuery(document).ready(function($) {
    // Initialize address autocomplete
    initAddressAutocomplete();
    
    /**
     * Initialize Google Places autocomplete for search input
     */
    function initAddressAutocomplete() {
        const searchInput = document.getElementById('hph-location-search');
        
        if (!searchInput) {
            console.log('Search input not found');
            return;
        }
        
        // Check if Google Maps API is loaded
        if (typeof google === 'undefined' || typeof google.maps === 'undefined' || typeof google.maps.places === 'undefined') {
            console.warn('Google Maps Places API not loaded');
            return;
        }
        
        try {
            // Create PlaceAutocompleteElement
            const placeAutocomplete = new google.maps.places.PlaceAutocompleteElement({
                inputElement: searchInput,
                types: 'address', // String instead of array for new API
                componentRestrictions: { country: 'us' }
            });

            // Add class to the generated input for styling
            const generatedInput = searchInput.parentElement.querySelector('gmp-place-autocomplete input');
            if (generatedInput) {
                generatedInput.classList.add('hph-search-input');
            }
            
            // Listen for place selection
            placeAutocomplete.addEventListener('gmp-placeselect', (event) => {
                const place = event.detail.place;
                handlePlaceSelection(place);
            });
        } catch (error) {
            console.error('Error initializing autocomplete:', error);
            
            // Fallback to basic search input if autocomplete fails
            searchInput.setAttribute('type', 'text');
            searchInput.setAttribute('placeholder', 'Search by location...');
        }
    }
    
    /**
     * Handle place selection
     * 
     * @param {Object} place Selected place
     */
    function handlePlaceSelection(place) {
        if (!place) {
            console.warn('No place details available');
            return;
        }
        
        // Get location data from the Place object
        place.fetchFields(['geometry.location', 'address_components']).then(() => {
            const location = place.geometry?.location;
            if (location) {
                // Update hidden fields
                const latitudeInput = document.getElementById('hph-search-latitude');
                const longitudeInput = document.getElementById('hph-search-longitude');
                
                if (latitudeInput) latitudeInput.value = location.lat;
                if (longitudeInput) longitudeInput.value = location.lng;
            }
            
            // Extract address components
            const addressComponents = place.addressComponents;
            if (addressComponents) {
                addressComponents.forEach(component => {
                    const type = component.types[0];
                    
                    if (type === 'locality') {
                        // City
                        const cityInput = document.getElementById('hph-search-city');
                        if (cityInput) {
                            cityInput.value = component.long_name;
                        }
                    } else if (type === 'administrative_area_level_1') {
                        // State
                        const stateInput = document.getElementById('hph-search-state');
                        if (stateInput) {
                            stateInput.value = component.long_name;
                        }
                    }
                });
            }
        });
    }
});