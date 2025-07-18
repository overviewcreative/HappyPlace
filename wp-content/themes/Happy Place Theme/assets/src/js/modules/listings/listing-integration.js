/**
 * Listing API Integration
 */

(function($) {
    'use strict';

    const HPH_Listing = {
        init: function() {
            this.setupEventListeners();
        },

        setupEventListeners: function() {
            $('.hph-listing-load').on('click', this.loadListingData);
        },

        loadListingData: function(e) {
            e.preventDefault();
            const listingId = $(this).data('listing-id');

            $.ajax({
                url: happyplace_vars.rest_url + 'happy-place/v1/listings/' + listingId,
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', happyplace_vars.nonce);
                },
                success: function(response) {
                    // Handle the listing data
                    if (response && response.id) {
                        HPH_Listing.updateListingUI(response);
                    }
                },
                error: function(xhr) {
                    console.error('Error loading listing:', xhr.responseText);
                }
            });
        },

        updateListingUI: function(listing) {
            // Update listing data in the UI
            const $container = $('#listing-' + listing.id);
            if ($container.length) {
                $container.find('.listing-title').text(listing.title);
                $container.find('.listing-content').html(listing.content);
                // Add more UI updates as needed
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        HPH_Listing.init();
    });

})(jQuery);
