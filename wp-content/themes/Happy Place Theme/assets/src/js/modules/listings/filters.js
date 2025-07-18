/**
 * Filters functionality
 */

(function($) {
    'use strict';

    class FiltersManager {
        constructor() {
            this.form = $('.hph-filters__form');
            this.rangeInputs = this.form.find('.hph-filters__range-input');
            this.locationInput = this.form.find('#location');
            this.initializeComponents();
            this.bindEvents();
        }

        initializeComponents() {
            // Initialize range sliders
            this.rangeInputs.each((_, input) => {
                const $input = $(input);
                const min = $input.data('min');
                const max = $input.data('max');
                const step = $input.data('step') || 1;

                $input.on('input', function() {
                    const $display = $(`#${this.id}-display`);
                    if ($display.length) {
                        $display.text(this.value);
                    }
                });
            });

            // Initialize location autocomplete if Google Maps is loaded
            if (typeof google !== 'undefined' && this.locationInput.length) {
                this.initializeAutocomplete();
            }

            // Load saved filters from URL parameters
            this.loadSavedFilters();
        }

        initializeAutocomplete() {
            const autocomplete = new google.maps.places.Autocomplete(
                this.locationInput[0],
                { types: ['(cities)'] }
            );

            autocomplete.addListener('place_changed', () => {
                const place = autocomplete.getPlace();
                if (place.geometry) {
                    this.form.find('#lat').val(place.geometry.location.lat());
                    this.form.find('#lng').val(place.geometry.location.lng());
                }
            });
        }

        bindEvents() {
            // Handle form submission
            this.form.on('submit', this.handleSubmit.bind(this));

            // Handle reset
            this.form.find('.hph-filters__button--reset').on('click', this.handleReset.bind(this));

            // Handle range input changes
            this.form.find('.hph-filters__range-input').on('change', this.updateRangeDisplay.bind(this));

            // Handle checkbox groups
            this.form.find('.hph-filters__checkbox-group').on('change', 'input[type="checkbox"]', this.handleCheckboxChange.bind(this));
        }

        handleSubmit(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const queryParams = new URLSearchParams(formData).toString();

            // Update URL without reloading
            const newUrl = `${window.location.pathname}?${queryParams}`;
            window.history.pushState({ path: newUrl }, '', newUrl);

            // Trigger the listings update
            $(document).trigger('hphFiltersUpdated', [Object.fromEntries(formData)]);
        }

        handleReset(e) {
            e.preventDefault();
            this.form[0].reset();

            // Reset range displays
            this.rangeInputs.each((_, input) => {
                const $input = $(input);
                const $display = $(`#${input.id}-display`);
                if ($display.length) {
                    $display.text($input.val());
                }
            });

            // Clear location coordinates
            this.form.find('#lat, #lng').val('');

            // Trigger form submission to update listings
            this.form.submit();
        }

        updateRangeDisplay(e) {
            const $input = $(e.target);
            const $display = $(`#${e.target.id}-display`);
            if ($display.length) {
                $display.text($input.val());
            }
        }

        handleCheckboxChange(e) {
            const $checkbox = $(e.target);
            const $group = $checkbox.closest('.hph-filters__checkbox-group');
            const maxAllowed = $group.data('max-select');

            if (maxAllowed) {
                const $checked = $group.find('input[type="checkbox"]:checked');
                if ($checked.length > maxAllowed) {
                    $checkbox.prop('checked', false);
                }
            }
        }

        loadSavedFilters() {
            const params = new URLSearchParams(window.location.search);
            
            params.forEach((value, key) => {
                const $input = this.form.find(`[name="${key}"]`);
                if ($input.length) {
                    if ($input.is(':checkbox')) {
                        $input.prop('checked', value === 'on' || value === '1');
                    } else {
                        $input.val(value);
                    }
                }
            });

            // Update range displays
            this.rangeInputs.each((_, input) => {
                const $input = $(input);
                const $display = $(`#${input.id}-display`);
                if ($display.length) {
                    $display.text($input.val());
                }
            });
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        new FiltersManager();
    });

})(jQuery);
