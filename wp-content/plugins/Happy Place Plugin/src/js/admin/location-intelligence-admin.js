/**
 * Location Intelligence Admin JavaScript
 *
 * Handles manual refresh of location intelligence data in admin
 *
 * @package HappyPlace
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Handle refresh button clicks in meta box
    $(document).on('click', '.hph-refresh-btn', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const action = button.data('action');
        const postId = button.data('post-id');
        
        // Confirm for refresh all
        if (action === 'refresh_all') {
            if (!confirm(hphLocationIntelligence.messages.confirm)) {
                return;
            }
        }
        
        refreshLocationData(action, postId, button);
    });
    
    // Handle ACF field refresh buttons
    $(document).on('click', '.acf-refresh-btn', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const fieldKey = button.data('field');
        
        refreshFieldData(fieldKey, button);
    });
    
    // Handle amenities refresh button
    $(document).on('click', '.acf-refresh-amenities-btn', function(e) {
        e.preventDefault();
        
        const button = $(this);
        refreshFieldData('nearby_amenities', button);
    });
    
    /**
     * Refresh location data
     */
    function refreshLocationData(action, postId, button) {
        const statusDiv = $('#hph-refresh-status');
        const originalText = button.text();
        
        // Disable all buttons
        $('.hph-refresh-btn').prop('disabled', true);
        
        // Update button state
        button.find('.dashicons').addClass('spin');
        button.contents().last()[0].textContent = ' ' + hphLocationIntelligence.messages.refreshing;
        
        // Show loading status
        statusDiv.removeClass('success error').addClass('loading')
            .text(hphLocationIntelligence.messages.refreshing)
            .show();
        
        // Determine AJAX action
        let ajaxAction;
        switch (action) {
            case 'refresh_all':
                ajaxAction = 'hph_refresh_location_data';
                break;
            case 'refresh_schools':
                ajaxAction = 'hph_refresh_school_data';
                break;
            case 'refresh_walkability':
                ajaxAction = 'hph_refresh_walkability_data';
                break;
            case 'refresh_amenities':
                ajaxAction = 'hph_refresh_nearby_amenities';
                break;
            default:
                ajaxAction = 'hph_refresh_location_data';
        }
        
        $.ajax({
            url: hphLocationIntelligence.ajaxUrl,
            type: 'POST',
            data: {
                action: ajaxAction,
                post_id: postId,
                nonce: hphLocationIntelligence.nonce
            },
            success: function(response) {
                if (response.success) {
                    statusDiv.removeClass('loading error').addClass('success')
                        .text(hphLocationIntelligence.messages.success);
                    
                    // Reload the page to show updated data
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    statusDiv.removeClass('loading success').addClass('error')
                        .text(response.data || hphLocationIntelligence.messages.error);
                }
            },
            error: function() {
                statusDiv.removeClass('loading success').addClass('error')
                    .text(hphLocationIntelligence.messages.error);
            },
            complete: function() {
                // Restore button state
                $('.hph-refresh-btn').prop('disabled', false);
                button.find('.dashicons').removeClass('spin');
                button.contents().last()[0].textContent = ' ' + originalText.trim();
                
                // Hide status after delay
                setTimeout(function() {
                    statusDiv.fadeOut();
                }, 3000);
            }
        });
    }
    
    /**
     * Refresh individual field data
     */
    function refreshFieldData(fieldKey, button) {
        const originalHtml = button.html();
        
        // Update button state
        button.prop('disabled', true)
            .html('<span class="dashicons dashicons-update spin"></span>');
        
        // Determine action based on field
        let ajaxAction = 'hph_refresh_location_data';
        
        if (fieldKey.includes('school')) {
            ajaxAction = 'hph_refresh_school_data';
        } else if (fieldKey.includes('score')) {
            ajaxAction = 'hph_refresh_walkability_data';
        } else if (fieldKey === 'nearby_amenities') {
            ajaxAction = 'hph_refresh_nearby_amenities';
        }
        
        $.ajax({
            url: hphLocationIntelligence.ajaxUrl,
            type: 'POST',
            data: {
                action: ajaxAction,
                post_id: hphLocationIntelligence.postId,
                nonce: hphLocationIntelligence.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Show success indicator
                    button.html('<span class="dashicons dashicons-yes-alt" style="color: green;"></span>');
                    
                    // Reload specific field data
                    setTimeout(function() {
                        refreshACFField(fieldKey);
                    }, 500);
                } else {
                    // Show error indicator
                    button.html('<span class="dashicons dashicons-dismiss" style="color: red;"></span>');
                }
            },
            error: function() {
                // Show error indicator
                button.html('<span class="dashicons dashicons-dismiss" style="color: red;"></span>');
            },
            complete: function() {
                // Restore button after delay
                setTimeout(function() {
                    button.prop('disabled', false).html(originalHtml);
                }, 2000);
            }
        });
    }
    
    /**
     * Refresh ACF field display
     */
    function refreshACFField(fieldKey) {
        // For now, we'll just reload the page for simplicity
        // In a more advanced implementation, we could use AJAX to reload just the field
        if (fieldKey === 'nearby_amenities') {
            // For repeater fields, we need to reload the page
            location.reload();
        } else {
            // For simple fields, try to update the value
            $.ajax({
                url: hphLocationIntelligence.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hph_get_field_value',
                    post_id: hphLocationIntelligence.postId,
                    field_key: fieldKey,
                    nonce: hphLocationIntelligence.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        const field = $('.acf-field[data-key="' + fieldKey + '"] input, .acf-field[data-key="' + fieldKey + '"] textarea');
                        if (field.length) {
                            field.val(response.data);
                        }
                    }
                }
            });
        }
    }
    
    /**
     * Geocode address from address fields
     */
    function geocodeAddress() {
        if (!hphLocationIntelligence.postId) {
            alert('Error: No post ID found');
            return;
        }
        
        const statusDiv = $('#hph-refresh-status');
        
        // Show loading status
        statusDiv.removeClass('success error').addClass('loading')
            .text('Geocoding address...')
            .show();
        
        $.ajax({
            url: hphLocationIntelligence.ajaxUrl,
            type: 'POST',
            data: {
                action: 'hph_geocode_listing',
                post_id: hphLocationIntelligence.postId,
                nonce: hphLocationIntelligence.nonce
            },
            success: function(response) {
                if (response.success) {
                    statusDiv.removeClass('loading error').addClass('success')
                        .text('✓ Address geocoded successfully! Coordinates: ' + 
                              response.data.coordinates.lat + ', ' + response.data.coordinates.lng);
                    
                    // Update latitude/longitude fields if they exist
                    const latField = $('[data-name="latitude"] input');
                    const lngField = $('[data-name="longitude"] input');
                    
                    if (latField.length) {
                        latField.val(response.data.coordinates.lat);
                    }
                    if (lngField.length) {
                        lngField.val(response.data.coordinates.lng);
                    }
                    
                    // Hide status after delay
                    setTimeout(function() {
                        statusDiv.fadeOut();
                    }, 5000);
                } else {
                    statusDiv.removeClass('loading success').addClass('error')
                        .text('Error: ' + (response.data || 'Failed to geocode address'));
                    
                    // Hide status after delay
                    setTimeout(function() {
                        statusDiv.fadeOut();
                    }, 5000);
                }
            },
            error: function() {
                statusDiv.removeClass('loading success').addClass('error')
                    .text('Error: Failed to contact server');
                
                // Hide status after delay
                setTimeout(function() {
                    statusDiv.fadeOut();
                }, 5000);
            }
        });
    }
    
    // Make functions available globally for inline onclick handlers
    window.hphGeocodeAddress = geocodeAddress;
    window.hphRefreshLocationData = function() {
        const button = $('.hph-refresh-btn[data-action="refresh_all"]').first();
        if (button.length) {
            button.click();
        }
    };
    window.hphRefreshSchoolData = function() {
        const button = $('.hph-refresh-btn[data-action="refresh_schools"]').first();
        if (button.length) {
            button.click();
        }
    };
    window.hphRefreshWalkabilityData = function() {
        const button = $('.hph-refresh-btn[data-action="refresh_walkability"]').first();
        if (button.length) {
            button.click();
        }
    };
    window.hphRefreshAmenities = function() {
        const button = $('.hph-refresh-btn[data-action="refresh_amenities"]').first();
        if (button.length) {
            button.click();
        }
    };
    
    // Add CSS for spinning animation
    if (!$('#hph-location-intelligence-css').length) {
        $('<style id="hph-location-intelligence-css">')
            .text(`
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                .spin {
                    animation: spin 1s linear infinite;
                }
                .hph-refresh-btn:disabled {
                    opacity: 0.6;
                    cursor: not-allowed;
                }
            `)
            .appendTo('head');
    }
});
