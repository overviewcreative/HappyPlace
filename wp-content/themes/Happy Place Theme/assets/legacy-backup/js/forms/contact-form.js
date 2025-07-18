/**
 * Contact Form Handling
 */

(function($) {
    'use strict';

    const ContactForm = {
        init() {
            this.form = $('#hph-contact-form');
            if (!this.form.length) return;

            this.initValidation();
            this.initAgentPreview();
        },

        initValidation() {
            this.form.on('submit', (e) => {
                const message = $('#message').val().trim();
                
                if (message.length < 10) {
                    e.preventDefault();
                    alert(hphContactForm.strings.messageTooShort);
                    $('#message').focus();
                    return false;
                }

                // Validate email
                const email = $('#email').val().trim();
                if (email && !this.isValidEmail(email)) {
                    e.preventDefault();
                    alert(hphContactForm.strings.invalidEmail);
                    $('#email').focus();
                    return false;
                }
            });
        },

        initAgentPreview() {
            const urlParams = new URLSearchParams(window.location.search);
            const agentId = urlParams.get('agent_id');

            if (agentId) {
                $.ajax({
                    url: hphContactForm.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'hph_get_agent_preview',
                        agent_id: agentId,
                        nonce: hphContactForm.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.html) {
                            $('.hph-agent-preview').html(response.data.html);
                        }
                    }
                });
            }
        },

        isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }
    };

    // Initialize when document is ready
    $(document).ready(() => ContactForm.init());

})(jQuery);
