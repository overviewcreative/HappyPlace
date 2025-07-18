/**
 * Open House Form Handling
 */

(function($) {
    'use strict';

    const OpenHouseForm = {
        init() {
            this.form = $('#hph-open-house-form');
            if (!this.form.length) return;

            this.initDateTimeValidation();
            this.initListingPreview();
            this.initSubmitHandler();
        },

        initDateTimeValidation() {
            const form = this.form;
            const dateInput = form.find('#open_house_date');
            const startTimeInput = form.find('#open_house_start_time');
            const endTimeInput = form.find('#open_house_end_time');

            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            dateInput.attr('min', today);

            // Validate times when either changes
            const validateTimes = () => {
                const startTime = startTimeInput.val();
                const endTime = endTimeInput.val();

                if (startTime && endTime && endTime <= startTime) {
                    alert(hphLocalized.strings.endTimeError || 'End time must be after start time');
                    endTimeInput.val('').focus();
                }
            };

            startTimeInput.on('change', validateTimes);
            endTimeInput.on('change', validateTimes);

            // Validate date is not in past
            dateInput.on('change', function() {
                const selectedDate = new Date($(this).val());
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                if (selectedDate < today) {
                    alert(hphLocalized.strings.pastDateError || 'Please select a future date');
                    $(this).val('').focus();
                }
            });
        },

        initListingPreview() {
            const form = this.form;
            const listingSelect = form.find('#listing_id');

            listingSelect.on('change', function() {
                const listingId = $(this).val();
                if (!listingId) return;

                $.ajax({
                    url: hphLocalized.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'hph_get_listing_preview',
                        listing_id: listingId,
                        nonce: hphLocalized.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.html) {
                            form.find('.listing-preview').html(response.data.html);
                            
                            // Auto-fill title if empty
                            const titleInput = form.find('#open_house_title');
                            if (!titleInput.val()) {
                                titleInput.val(response.data.suggested_title || '');
                            }
                        }
                    }
                });
            });
        },

        initSubmitHandler() {
            this.form.on('submit', (e) => {
                const form = $(e.target);

                // Basic validation
                const requiredFields = form.find('[required]');
                let isValid = true;

                requiredFields.each(function() {
                    if (!$(this).val()) {
                        isValid = false;
                        const fieldName = $(this).prev('label').text() || 'This field';
                        alert(fieldName + ' is required');
                        $(this).focus();
                        return false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    return false;
                }

                // Additional validation for max visitors
                const maxVisitors = form.find('#open_house_max_visitors').val();
                if (maxVisitors && parseInt(maxVisitors) < 1) {
                    e.preventDefault();
                    alert(hphLocalized.strings.invalidMaxVisitors || 'Maximum visitors must be at least 1');
                    return false;
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(() => OpenHouseForm.init());

})(jQuery);
