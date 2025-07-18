(function($) {
    'use strict';

    function initGeocodeButton() {
        const button = `
            <div class="acf-field">
                <button type="button" class="button button-secondary" id="geocode-address">
                    Get Coordinates from Address
                </button>
                <span class="geocode-status"></span>
            </div>`;

        // Add button after the address fields
        $('.acf-field-zip-code').after(button);

        $('#geocode-address').on('click', function(e) {
            e.preventDefault();
            const $button = $(this);
            const $status = $('.geocode-status');
            
            $button.prop('disabled', true);
            $status.html('Geocoding address...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hph_geocode_address',
                    security: hphAdmin.geocodeNonce,
                    post_id: $('#post_ID').val()
                },
                success: function(response) {
                    if (response.success) {
                        $status.html('✓ Coordinates updated!');
                        // Update lat/lng fields if visible
                        $('[data-name="latitude"]').find('input').val(response.data.lat);
                        $('[data-name="longitude"]').find('input').val(response.data.lng);
                    } else {
                        $status.html('Error: ' + response.data);
                    }
                },
                error: function() {
                    $status.html('Failed to geocode address');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });
    }

    // Initialize when ACF is ready
    if (typeof acf !== 'undefined') {
        acf.addAction('ready_field/type=text', initGeocodeButton);
    }

})(jQuery);
