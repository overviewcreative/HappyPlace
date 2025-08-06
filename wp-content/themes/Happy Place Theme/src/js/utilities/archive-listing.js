/**
 * Happy Place Archive Listing JavaScript
 * 
 * @package HappyPlace
 */

(function($) {
    'use strict';
    
    // Store DOM elements and state
    const archiveListing = {
        // DOM Elements
        elements: {
            mapContainer: $('#listingsMap'),
            viewToggle: $('.hph-view-btn'),
            sortSelect: $('#sortBy'),
            filterForm: $('.hph-filters-form'),
            searchForm: $('.hph-search-form'),
            resultsContent: $('.hph-results-content'),
            filterToggles: $('.hph-filter-toggle'),
            mapSidebar: $('.hph-map-sidebar'),
            bedroomOptions: $('.hph-bedroom-option input'),
            bathroomOptions: $('.hph-bathroom-option input'),
            featureOptions: $('.hph-feature-option input')
        },
        
        // State
        viewMode: $('input[name="view_mode"]').val() || 'cards',
        isLoading: false,
        map: null,
        markers: [],
        
        /**
         * Initialize the listing archive
         */
        init: function() {
            // Initialize view toggles
            this.elements.viewToggle.on('click', this.handleViewToggle.bind(this));
            
            // Initialize sort select
            this.elements.sortSelect.on('change', this.handleSortChange.bind(this));
            
            // Initialize filter form
            this.elements.filterForm.on('submit', this.handleFilterSubmit.bind(this));
            
            // Initialize auto-submit fields
            this.initAutoSubmitFields();
            
            // Initialize filter toggles
            this.initFilterToggles();
            
            // Initialize map if in map view
            if (this.viewMode === 'map') {
                this.initMapFilterToggle();
            }
            
            // Initialize responsive behavior
            this.initResponsive();
            
            // Initialize Google Places autocomplete for search input
            this.initAddressAutocomplete();
        },
        
        /**
         * Initialize map filter toggle functionality
         */
        initMapFilterToggle: function() {
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
        },
        
        /**
         * Handle view toggle click
         * 
         * @param {Event} e Click event
         */
        handleViewToggle: function(e) {
            e.preventDefault();
            
            // Get view mode from URL
            const url = new URL(e.currentTarget.href);
            const viewMode = url.searchParams.get('view_mode');
            
            // Update view mode and redirect
            window.location.href = e.currentTarget.href;
        },
        
        /**
         * Handle sort select change
         * 
         * @param {Event} e Change event
         */
        handleSortChange: function(e) {
            const sortValue = $(e.currentTarget).val();
            const currentUrl = new URL(window.location.href);
            
            // Update sort parameter
            currentUrl.searchParams.set('sort_by', sortValue);
            
            // Redirect to new URL
            window.location.href = currentUrl.toString();
        },
        
        /**
         * Handle filter form submit
         * 
         * @param {Event} e Submit event
         */
        handleFilterSubmit: function(e) {
            if (this.isLoading) {
                e.preventDefault();
                return;
            }
            
            // Always clear existing loading states
            this.hideLoading();
            
            // Show loading only if we're not already showing no results
            if (!$('.no-properties-found').length) {
                this.isLoading = true;
                this.showLoading();
            }
        },
        
        /**
         * Initialize auto-submit fields
         */
        initAutoSubmitFields: function() {
            // Radio buttons for beds and baths
            this.elements.bedroomOptions.on('change', function() {
                $(this).closest('form').submit();
            });
            
            this.elements.bathroomOptions.on('change', function() {
                $(this).closest('form').submit();
            });
            
            // Feature options on the map view (not on the regular view)
            if (this.viewMode === 'map') {
                this.elements.featureOptions.on('change', function() {
                    $(this).closest('form').submit();
                });
            }
        },
        
        /**
         * Initialize filter toggles
         */
        initFilterToggles: function() {
            this.elements.filterToggles.on('click', function() {
                const $toggle = $(this);
                const $content = $toggle.next('.hph-filter-content');
                
                // Toggle classes
                $toggle.toggleClass('open');
                $content.toggleClass('hph-filter-content--collapsed');
                
                // Store state in localStorage
                const sectionId = $toggle.find('span').text().trim().toLowerCase().replace(/\s+/g, '-');
                localStorage.setItem(`hph_filter_${sectionId}`, $toggle.hasClass('open') ? 'open' : 'closed');
            });
            
            // Check if we should open sections based on localStorage or active filters
            this.elements.filterToggles.each(function() {
                const $toggle = $(this);
                const $content = $toggle.next('.hph-filter-content');
                const sectionId = $toggle.find('span').text().trim().toLowerCase().replace(/\s+/g, '-');
                const sectionState = localStorage.getItem(`hph_filter_${sectionId}`);
                
                // Check if any filter in this section is active
                const hasActiveFilter = ($content.find('input:checked').length > 0 || 
                                         $content.find('select').val() !== '' && 
                                         $content.find('select').val() !== null);
                
                if (sectionState === 'open' || hasActiveFilter) {
                    $toggle.addClass('open');
                    $content.removeClass('hph-filter-content--collapsed');
                }
            });
        },
        
        /**
         * Initialize Google Maps for map view
         */
        initializeMap: function() {
            // Ensure the Google Maps API is loaded
            if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
                console.error('Google Maps API is not loaded');
                return;
            }
            
            // Get map container
            const mapContainer = this.elements.mapContainer[0];
            
            // Check if map has already been initialized
            if (mapContainer.hphMap) {
                return;
            }
            
            // Get properties data from data attribute
            let properties = [];
            try {
                properties = JSON.parse(mapContainer.dataset.properties || '[]');
            } catch (error) {
                console.error('Error parsing properties data:', error);
            }
            
            // Initialize map
            const mapOptions = {
                containerId: mapContainer.id,
                apiKey: window.hphMapConfig?.apiKey || '',
                properties: properties,
                callbacks: {
                    onMarkerClick: (propertyId) => {
                        this.highlightMapListing(propertyId);
                    }
                }
            };
            
            // Create map instance (using the HPHListingMap class from listing-map.js)
            if (typeof HPHListingMap === 'function') {
                mapContainer.hphMap = new HPHListingMap(mapOptions);
            }
            
            // Add click event to listing cards
            $('.hph-map-listing-card').on('click', this.handleMapListingClick.bind(this));
        },
        
        /**
         * Initialize responsive behavior
         */
        initResponsive: function() {
            // Handle window resize
            $(window).on('resize', this.handleResize.bind(this));
            
            // Initial call
            this.handleResize();
        },
        
        /**
         * Initialize Google Places autocomplete for search input
         */
        initAddressAutocomplete: function() {
            const searchInput = document.getElementById('hph-location-search');
            
            if (!searchInput) {
                return;
            }
            
            // Check if Google Maps API is loaded
            if (typeof google === 'undefined' || typeof google.maps === 'undefined' || typeof google.maps.places === 'undefined') {
                console.warn('Google Maps Places API not loaded');
                return;
            }
            
            // Create autocomplete
            const autocomplete = new google.maps.places.Autocomplete(searchInput, {
                types: ['address', 'geocode'],
                componentRestrictions: { country: 'us' },
                fields: ['address_components', 'geometry', 'name']
            });
            
            // Handle place changed
            autocomplete.addListener('place_changed', () => {
                const place = autocomplete.getPlace();
                
                if (!place.geometry) {
                    return;
                }
                // Add to archive-listing.js
function initMapFilterToggle() {
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
}

// Call this in your init function
if (this.viewMode === 'map') {
    initMapFilterToggle();
}
                // Get location data
                const lat = place.geometry.location.lat();
                const lng = place.geometry.location.lng();
                
                // Update hidden fields
                document.getElementById('hph-search-latitude').value = lat;
                document.getElementById('hph-search-longitude').value = lng;
                
                // Extract address components
                if (place.address_components) {
                    place.address_components.forEach(component => {
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
                                stateInput.value = component.short_name;
                            }
                        } else if (type === 'postal_code') {
                            // Zip code
                            const zipInput = document.getElementById('hph-search-zip');
                            if (zipInput) {
                                zipInput.value = component.long_name;
                            }
                        }
                    });
                }
            });
        },
        
        /**
         * Handle map listing card click
         * 
         * @param {Event} e Click event
         */
        handleMapListingClick: function(e) {
            const $card = $(e.currentTarget);
            const listingId = parseInt($card.data('listing-id'), 10);
            
            // Highlight card
            $('.hph-map-listing-card').removeClass('highlighted');
            $card.addClass('highlighted');
            
            // Trigger map highlight
            const mapContainer = this.elements.mapContainer[0];
            if (mapContainer && mapContainer.hphMap) {
                mapContainer.hphMap.highlightProperty(listingId);
            }
        },
        
        /**
         * Highlight a listing in the map sidebar
         * 
         * @param {number} listingId The listing ID to highlight
         */
        highlightMapListing: function(listingId) {
            const $cards = $('.hph-map-listing-card');
            
            $cards.removeClass('highlighted');
            
            const $matchingCard = $cards.filter(function() {
                return $(this).data('listing-id') === listingId;
            });
            
            if ($matchingCard.length) {
                $matchingCard.addClass('highlighted');
                
                // Scroll card into view
                const $mapListings = $('.hph-map-listings');
                
                if ($mapListings.length) {
                    $mapListings.animate({
                        scrollTop: $matchingCard.position().top + $mapListings.scrollTop() - $mapListings.height()/2 + $matchingCard.height()/2
                    }, 300);
                }
            }
        },
        
        /**
         * Handle window resize
         */
        handleResize: function() {
            // Resize map if in map view
            if (this.viewMode === 'map' && this.elements.mapContainer.length > 0) {
                const mapContainer = this.elements.mapContainer[0];
                
                if (mapContainer.hphMap) {
                    mapContainer.hphMap.resizeMap();
                }
            }
            
            // Adjust map sidebar on mobile
            if (window.innerWidth <= 768 && this.elements.mapSidebar.length > 0) {
                const windowHeight = window.innerHeight;
                const headerHeight = $('.hph-archive-hero').outerHeight() || 0;
                const mapHeight = Math.min(400, windowHeight * 0.4);
                const filterHeight = $('.hph-map-filters').outerHeight() || 0;
                
                const availableHeight = windowHeight - headerHeight - mapHeight - filterHeight - 40;
                
                $('.hph-map-listings').css('max-height', `${Math.max(200, availableHeight)}px`);
            } else {
                // Reset on desktop
                $('.hph-map-listings').css('max-height', '');
            }
        },
        
        /**
         * Show loading indicator
         */
        showLoading: function() {
            // Never show loading if we're already on a no-results state
            if ($('.no-properties-found').length > 0) {
                this.isLoading = false;
                this.hideLoading();
                return;
            }

            if (!this.isLoading) {
                this.isLoading = true;
                
                // Remove any existing overlay first
                $('.hph-loading-overlay').remove();
                
                // Add full page loading overlay
                const $overlay = $('<div class="hph-loading-overlay"><div class="hph-loading-spinner"></div></div>');
                $('body').append($overlay);

                // Set a shorter timeout for the loading state
                setTimeout(() => {
                    if (this.isLoading) {
                        this.hideLoading();
                        // If we still don't have results, show no results message
                        if ($('.hph-results-content').length === 0 || $('.hph-results-content').is(':empty')) {
                            $('.hph-results-content').html('<div class="no-properties-found">No properties found matching your criteria.</div>');
                        }
                    }
                }, 5000);
            }
        },
        
        /**
         * Hide loading indicator
         */
        hideLoading: function() {
            this.isLoading = false;
            this.elements.resultsContent.removeClass('loading');
            
            // Remove loading overlay
            $('.hph-loading-overlay').remove();
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Ensure any stuck loading overlays are removed
        $('.hph-loading-overlay').remove();
        
        // Force remove loading state
        $('.loading').removeClass('loading');
        
        // Add global AJAX error handler
        $(document).ajaxError(function() {
            archiveListing.hideLoading();
        });
        
        // Add handler for page load completion
        $(window).on('load', function() {
            archiveListing.hideLoading();
            $('.hph-loading-overlay').remove();
        });
        
        // Initialize
        archiveListing.init();
    });
    
})(jQuery);