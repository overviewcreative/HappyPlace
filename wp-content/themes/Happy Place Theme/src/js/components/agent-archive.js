/**
 * Agent Archive Scripts
 */

(function($) {
    'use strict';

    // Initialize agent archive functionality
    function initAgentArchive() {
        // Add smooth hover effects
        $('.hph-agent-card').hover(
            function() {
                $(this).find('.hph-agent-image img').css('transform', 'scale(1.05)');
            },
            function() {
                $(this).find('.hph-agent-image img').css('transform', 'scale(1)');
            }
        );

        // Initialize contact form handlers if present
        $('.hph-agent-contact-form').on('submit', function(e) {
            e.preventDefault();
            // Add contact form handling here
        });
    }

    // Initialize when document is ready
    $(document).ready(function() {
        initAgentArchive();
    });

})(jQuery);
