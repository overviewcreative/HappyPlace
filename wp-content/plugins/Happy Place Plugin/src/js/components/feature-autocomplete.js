/**
 * Feature Autocomplete for Happy Place Theme
 * Adds autocomplete functionality to custom feature fields
 */

(function($) {
    'use strict';

    // Feature autocomplete initialization
    function initFeatureAutocomplete() {
        $('.hph-feature-autocomplete').each(function() {
            $(this).autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: hph_autocomplete.ajax_url,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'hph_feature_autocomplete',
                            nonce: hph_autocomplete.nonce,
                            term: request.term
                        },
                        success: function(data) {
                            if (data.success && data.data) {
                                response(data.data);
                            } else {
                                response([]);
                            }
                        },
                        error: function() {
                            response([]);
                        }
                    });
                },
                minLength: 2,
                delay: 300,
                position: {
                    my: 'left top+2',
                    at: 'left bottom'
                },
                classes: {
                    'ui-autocomplete': 'hph-feature-suggestions'
                },
                select: function(event, ui) {
                    // After selection, check if this is a highlighted feature
                    const $row = $(this).closest('tr');
                    const category = ui.item.category || 'amenity';
                    
                    // Update category if available
                    $row.find('select[name*="[feature_category]"]').val(category).trigger('change');
                    
                    // If it's a premium feature, auto-check the highlight box
                    if (ui.item.premium) {
                        $row.find('input[name*="[is_highlight]"]').prop('checked', true).trigger('change');
                    }
                }
            });
        });
    }

    // Handle dynamic addition of new feature rows
    function handleDynamicRows() {
        // When ACF adds a new row
        acf.add_action('append', function($el) {
            if ($el.find('.hph-feature-autocomplete').length) {
                initFeatureAutocomplete();
            }
        });
    }

    // Feature category color coding
    function initCategoryColors() {
        const categoryColors = {
            'interior': '#e6f3ff',
            'exterior': '#e6ffe6',
            'location': '#fff2e6',
            'amenity': '#f9e6ff',
            'view': '#ffe6e6',
            'luxury': '#fff2cc'
        };

        function updateRowColor($row) {
            const category = $row.find('select[name*="[feature_category]"]').val();
            const color = categoryColors[category] || '#ffffff';
            $row.css('background-color', color);
        }

        // Update colors when category changes
        $(document).on('change', 'select[name*="[feature_category]"]', function() {
            updateRowColor($(this).closest('tr'));
        });

        // Initial color setup
        $('.acf-field-custom-features tr.acf-row').each(function() {
            updateRowColor($(this));
        });
    }

    // Validation and formatting
    function initValidation() {
        $(document).on('input', '.hph-feature-autocomplete', function() {
            const $input = $(this);
            const value = $input.val();
            
            // Convert to title case
            const formatted = value.replace(/\w\S*/g, function(txt) {
                return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
            });
            
            if (value !== formatted) {
                $input.val(formatted);
            }
            
            // Length validation
            if (value.length > 100) {
                $input.addClass('hph-validation-error');
            } else {
                $input.removeClass('hph-validation-error');
            }
        });
    }

    // Initialize on document ready
    $(document).ready(function() {
        initFeatureAutocomplete();
        handleDynamicRows();
        initCategoryColors();
        initValidation();
    });

})(jQuery);
