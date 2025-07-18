/**
 * Showing Request Form Handling
 */

(function($) {
    'use strict';

    const ShowingRequestForm = {
        init() {
            this.form = $('#hph-showing-form');
            if (!this.form.length) return;

            this.initDateTimeValidation();
            this.initPropertyPreview();
            this.initFormValidation();
        },

        initDateTimeValidation() {
            const today = new Date().toISOString().split('T')[0];
            
            // Set minimum date to today
            $('#preferred_date, #alternate_date').attr('min', today);

            // Validate dates are not in past
            $('#preferred_date, #alternate_date').on('change', function() {
                const selectedDate = new Date($(this).val());
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                if (selectedDate < today) {
                    alert(hphShowingForm.strings.pastDateError);
                    $(this).val('').focus();
                }
            });
        },

        initPropertyPreview() {
            const urlParams = new URLSearchParams(window.location.search);
            const listingId = urlParams.get('listing_id');

            if (listingId) {
                $.ajax({
                    url: hphShowingForm.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'hph_get_listing_preview',
                        listing_id: listingId,
                        nonce: hphShowingForm.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.html) {
                            $('.hph-property-preview').html(response.data.html);
                        }
                    }
                });
            }
        },

        initFormValidation() {
            this.form.on('submit', (e) => {
                const form = $(e.target);
                
                // Check required date/time
                const preferredDate = $('#preferred_date').val();
                const preferredTime = $('#preferred_time').val();
                
                if (!preferredDate || !preferredTime) {
                    e.preventDefault();
                    alert(hphShowingForm.strings.noTimeSelected);
                    return false;
                }

                // Validate email
                const email = $('#email').val().trim();
                if (!this.isValidEmail(email)) {
                    e.preventDefault();
                    alert(hphContactForm.strings.invalidEmail);
                    $('#email').focus();
                    return false;
                }

                // Validate phone
                const phone = $('#phone').val().trim();
                if (!phone) {
                    e.preventDefault();
                    alert(hphShowingForm.strings.phoneRequired);
                    $('#phone').focus();
                    return false;
                }
            });
        },

        isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }
    };

    // Initialize when document is ready
    $(document).ready(() => ShowingRequestForm.init());

})(jQuery);
