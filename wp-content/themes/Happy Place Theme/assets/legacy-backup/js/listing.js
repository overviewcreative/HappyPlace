/**
 * Happy Place Theme - Listing Scripts
 * 
 * JavaScript functionality for listing pages
 * 
 * @package HappyPlace
 */

(function($) {
    'use strict';

    // Initialize when DOM is ready
    $(document).ready(function() {
        
        // Initialize gallery functionality
        if (typeof initializeGallery === 'function') {
            initializeGallery();
        }
        
        // Initialize map functionality
        if (typeof initializeListingMap === 'function') {
            initializeListingMap();
        }
        
        // Initialize agent contact form
        initializeAgentContact();
        
        // Initialize mortgage calculator
        if (typeof initializeMortgageCalculator === 'function') {
            initializeMortgageCalculator();
        }
    });

    /**
     * Initialize agent contact functionality
     */
    function initializeAgentContact() {
        // Handle contact form submission
        $('.hph-agent-contact form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $button = $form.find('button[type="submit"]');
            const originalText = $button.text();
            
            // Show loading state
            $button.text('Sending...').prop('disabled', true);
            
            // Submit form via AJAX
            $.ajax({
                url: hphAjax.ajaxurl,
                type: 'POST',
                data: $form.serialize() + '&action=hph_contact_agent&nonce=' + hphAjax.nonce,
                success: function(response) {
                    if (response.success) {
                        $form.html('<div class="hph-success-message">' + hphAjax.strings.success + '</div>');
                    } else {
                        alert(response.data || hphAjax.strings.error);
                        $button.text(originalText).prop('disabled', false);
                    }
                },
                error: function() {
                    alert(hphAjax.strings.error);
                    $button.text(originalText).prop('disabled', false);
                }
            });
        });
        
        // Handle phone link clicks
        $('.hph-agent-contact a[href^="tel:"]').on('click', function() {
            // Track phone click event
            if (typeof gtag !== 'undefined') {
                gtag('event', 'phone_click', {
                    'event_category': 'contact',
                    'event_label': 'agent_card'
                });
            }
        });
        
        // Handle email link clicks
        $('.hph-agent-contact a[href^="mailto:"]').on('click', function() {
            // Track email click event
            if (typeof gtag !== 'undefined') {
                gtag('event', 'email_click', {
                    'event_category': 'contact',
                    'event_label': 'agent_card'
                });
            }
        });
    }

})(jQuery);
