/**
 * Agent Dashboard Form Handling
 */

(function($) {
    'use strict';

    const HappyPlaceForms = {
        init: function() {
            this.bindEvents();
            this.initializeFileUploads();
            this.initializeAutocomplete();
            this.initializeValidation();
        },

        bindEvents: function() {
            $('#listing-form').on('submit', (e) => this.handleListingSubmit(e));
            $('#transaction-form').on('submit', (e) => this.handleTransactionSubmit(e));
            
            // Dynamic form field handling
            $(document).on('change', '.field-dependency', this.handleFieldDependencies);
            $('.repeater-field').on('click', '.add-item', this.addRepeaterItem);
            $('.repeater-field').on('click', '.remove-item', this.removeRepeaterItem);
        },

        initializeFileUploads: function() {
            $('.image-upload').each(function() {
                const $field = $(this);
                const $preview = $field.find('.image-preview');
                const $input = $field.find('input[type="file"]');

                $input.on('change', function(e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    // Show preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $preview.html(`<img src="${e.target.result}">`);
                    };
                    reader.readAsDataURL(file);

                    // Upload file
                    const formData = new FormData();
                    formData.append('action', 'happy_place_upload_photo');
                    formData.append('nonce', happyPlaceDashboard.nonce);
                    formData.append('photo', file);
                    formData.append('type', 'listing_photo');

                    $.ajax({
                        url: happyPlaceDashboard.ajaxurl,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                $field.find('input[type="hidden"]').val(response.data.id);
                            } else {
                                alert('Upload failed: ' + response.data);
                            }
                        }
                    });
                });
            });
        },

        initializeAutocomplete: function() {
            if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
                return;
            }

            const addressInput = document.getElementById('listing-address');
            if (!addressInput) return;

            const autocomplete = new google.maps.places.Autocomplete(addressInput, {
                types: ['address']
            });

            autocomplete.addListener('place_changed', () => {
                const place = autocomplete.getPlace();
                if (!place.geometry) return;

                // Validate address
                this.validateAddress(place.formatted_address);

                // Fill in coordinates
                $('#listing-latitude').val(place.geometry.location.lat());
                $('#listing-longitude').val(place.geometry.location.lng());
            });
        },

        validateAddress: function(address) {
            $.post({
                url: happyPlaceDashboard.ajaxurl,
                data: {
                    action: 'happy_place_validate_address',
                    nonce: happyPlaceDashboard.nonce,
                    address: address
                },
                success: function(response) {
                    if (!response.success) {
                        alert(response.data);
                    }
                }
            });
        },

        initializeValidation: function() {
            // Add custom validation methods
            $.validator.addMethod('propertyAddress', function(value, element) {
                return this.optional(element) || /^[A-Za-z0-9\s,.-]+$/.test(value);
            }, 'Please enter a valid property address');

            // Initialize form validation
            $('#listing-form').validate({
                rules: this.getValidationRules(),
                messages: this.getValidationMessages(),
                errorPlacement: function(error, element) {
                    error.insertAfter(element).addClass('field-error');
                },
                highlight: function(element) {
                    $(element).addClass('error');
                },
                unhighlight: function(element) {
                    $(element).removeClass('error');
                }
            });
        },

        getValidationRules: function() {
            return {
                'address': {
                    required: true,
                    propertyAddress: true
                },
                'price': {
                    required: true,
                    number: true,
                    min: 0
                },
                'status': 'required',
                'bedrooms': {
                    number: true,
                    min: 0
                },
                'bathrooms': {
                    number: true,
                    min: 0
                },
                'square_footage': {
                    number: true,
                    min: 0
                }
            };
        },

        handleListingSubmit: function(e) {
            e.preventDefault();
            const $form = $(e.target);
            const $submit = $form.find('button[type="submit"]');
            
            if (!$form.valid()) return;

            const formData = new FormData($form[0]);
            $submit.prop('disabled', true).addClass('loading');

            wp.apiFetch({
                path: '/happy-place/v1/listings',
                method: 'POST',
                body: formData
            }).then(response => {
                this.closeModal();
                this.refreshListings();
                this.showNotification('Listing created successfully');
            }).catch(error => {
                alert('Error: ' + error.message);
            }).finally(() => {
                $submit.prop('disabled', false).removeClass('loading');
            });
        },

        handleFieldDependencies: function() {
            const $field = $(this);
            const dependentFields = $field.data('controls');
            const value = $field.val();

            if (!dependentFields) return;

            dependentFields.split(',').forEach(fieldId => {
                const $dependent = $(`#${fieldId}`);
                const showOn = $dependent.data('show-on');
                
                if (showOn === value) {
                    $dependent.closest('.form-group').show();
                } else {
                    $dependent.closest('.form-group').hide();
                }
            });
        },

        addRepeaterItem: function() {
            const $repeater = $(this).closest('.repeater-field');
            const template = $repeater.find('.repeater-template').html();
            const $items = $repeater.find('.repeater-items');
            const index = $items.children().length;

            $items.append(template.replace(/\{index\}/g, index));
        },

        removeRepeaterItem: function() {
            $(this).closest('.repeater-item').remove();
        },

        showNotification: function(message, type = 'success') {
            const notification = $(`
                <div class="notification ${type}">
                    ${message}
                    <button class="close">&times;</button>
                </div>
            `);

            $('.notifications').append(notification);
            setTimeout(() => notification.remove(), 5000);
        },

        refreshListings: function() {
            if (typeof HappyPlaceDashboard !== 'undefined') {
                HappyPlaceDashboard.loadListings();
            }
        },

        closeModal: function() {
            $('.modal').hide();
        }
    };

    $(document).ready(() => {
        HappyPlaceForms.init();
    });

})(jQuery);
