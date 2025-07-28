/**
 * Happy Place Plugin - Listing JavaScript Entry Point
 * 
 * This file serves as the main entry point for listing-related functionality
 * that needs to be available across both plugin admin and theme frontend.
 */

// Core listing functionality that can be used in admin and frontend
const HPH_Listing = {
    /**
     * Initialize listing functionality
     */
    init: function() {
        console.log('HPH Listing module initialized');
        this.bindEvents();
        this.initValidation();
    },

    /**
     * Bind global listing events
     */
    bindEvents: function() {
        // Status change handlers
        jQuery(document).on('change', '.listing-status-select', this.handleStatusChange);
        
        // Price update handlers
        jQuery(document).on('input', '.listing-price-input', this.handlePriceUpdate);
        
        // Image upload handlers
        jQuery(document).on('click', '.upload-listing-image', this.handleImageUpload);
        
        // Feature toggle handlers
        jQuery(document).on('change', '.listing-feature-checkbox', this.handleFeatureToggle);
    },

    /**
     * Initialize form validation
     */
    initValidation: function() {
        // Price validation
        jQuery('.listing-price-input').on('blur', function() {
            const price = parseFloat(jQuery(this).val().replace(/[^\d.]/g, ''));
            if (isNaN(price) || price < 0) {
                jQuery(this).addClass('error').after('<span class="error-msg">Please enter a valid price</span>');
            } else {
                jQuery(this).removeClass('error').siblings('.error-msg').remove();
            }
        });

        // Required field validation
        jQuery('.required-listing-field').on('blur', function() {
            if (!jQuery(this).val().trim()) {
                jQuery(this).addClass('error').after('<span class="error-msg">This field is required</span>');
            } else {
                jQuery(this).removeClass('error').siblings('.error-msg').remove();
            }
        });
    },

    /**
     * Handle listing status changes
     */
    handleStatusChange: function(e) {
        const status = jQuery(this).val();
        const listingId = jQuery(this).data('listing-id');
        
        console.log(`Listing ${listingId} status changed to: ${status}`);
        
        // Trigger status-specific actions
        if (status === 'sold') {
            HPH_Listing.handleSoldStatus(listingId);
        } else if (status === 'pending') {
            HPH_Listing.handlePendingStatus(listingId);
        }
    },

    /**
     * Handle price updates with formatting
     */
    handlePriceUpdate: function(e) {
        const input = jQuery(this);
        let value = input.val().replace(/[^\d.]/g, '');
        
        // Format as currency
        if (value) {
            const formatted = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 0
            }).format(value);
            
            input.val(formatted);
        }
    },

    /**
     * Handle image upload for listings
     */
    handleImageUpload: function(e) {
        e.preventDefault();
        
        const button = jQuery(this);
        const listingId = button.data('listing-id');
        
        // WordPress media uploader
        if (typeof wp !== 'undefined' && wp.media) {
            const mediaUploader = wp.media({
                title: 'Select Listing Images',
                button: { text: 'Use These Images' },
                multiple: true,
                library: { type: 'image' }
            });

            mediaUploader.on('select', function() {
                const attachments = mediaUploader.state().get('selection').toJSON();
                HPH_Listing.attachImagesToListing(listingId, attachments);
            });

            mediaUploader.open();
        }
    },

    /**
     * Handle feature toggle changes
     */
    handleFeatureToggle: function(e) {
        const checkbox = jQuery(this);
        const feature = checkbox.val();
        const listingId = checkbox.data('listing-id');
        const isChecked = checkbox.is(':checked');
        
        console.log(`Feature ${feature} ${isChecked ? 'enabled' : 'disabled'} for listing ${listingId}`);
        
        // Update feature display
        HPH_Listing.updateFeatureDisplay(listingId, feature, isChecked);
    },

    /**
     * Handle sold status
     */
    handleSoldStatus: function(listingId) {
        // Add sold styling
        jQuery(`[data-listing-id="${listingId}"]`).addClass('listing-sold');
        
        // Show sold date picker if not set
        const soldDateField = jQuery(`#sold-date-${listingId}`);
        if (soldDateField.length && !soldDateField.val()) {
            soldDateField.val(new Date().toISOString().split('T')[0]).focus();
        }
    },

    /**
     * Handle pending status
     */
    handlePendingStatus: function(listingId) {
        // Add pending styling
        jQuery(`[data-listing-id="${listingId}"]`).addClass('listing-pending');
        
        // Show pending date picker if not set
        const pendingDateField = jQuery(`#pending-date-${listingId}`);
        if (pendingDateField.length && !pendingDateField.val()) {
            pendingDateField.val(new Date().toISOString().split('T')[0]).focus();
        }
    },

    /**
     * Attach images to listing
     */
    attachImagesToListing: function(listingId, attachments) {
        const imageIds = attachments.map(att => att.id);
        
        // AJAX call to update listing images
        jQuery.ajax({
            url: ajaxurl || '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'update_listing_images',
                listing_id: listingId,
                image_ids: imageIds,
                nonce: jQuery('#listing_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    console.log('Images attached successfully');
                    location.reload(); // Refresh to show new images
                } else {
                    alert('Error attaching images: ' + response.data);
                }
            },
            error: function() {
                alert('Error attaching images. Please try again.');
            }
        });
    },

    /**
     * Update feature display
     */
    updateFeatureDisplay: function(listingId, feature, isEnabled) {
        const featureElement = jQuery(`[data-listing-id="${listingId}"] .feature-${feature}`);
        
        if (isEnabled) {
            featureElement.addClass('feature-enabled').removeClass('feature-disabled');
        } else {
            featureElement.addClass('feature-disabled').removeClass('feature-enabled');
        }
    },

    /**
     * Utility function to format listing data
     */
    formatListingData: function(listing) {
        return {
            id: listing.id,
            title: listing.title,
            price: this.formatPrice(listing.price),
            status: listing.status,
            address: listing.address,
            features: listing.features || [],
            images: listing.images || []
        };
    },

    /**
     * Format price for display
     */
    formatPrice: function(price) {
        if (!price) return 'Price on Request';
        
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 0
        }).format(price);
    }
};

// Initialize when DOM is ready
jQuery(document).ready(function() {
    HPH_Listing.init();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = HPH_Listing;
}

// Global namespace
window.HPH_Listing = HPH_Listing;
