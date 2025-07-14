/**
 * Agent Contact JavaScript
 * 
 * Handles agent contact forms, messaging, and interaction functionality
 * for agent profiles and contact pages.
 * 
 * @package HappyPlace
 */

(function($) {
    'use strict';

    class AgentContact {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
            this.initContactModal();
            this.initPropertyInquiry();
            this.initBuyerRegistration();
        }

        bindEvents() {
            // Contact agent form
            $(document).on('submit', '#agent-contact-form', (e) => {
                this.handleContactForm(e);
            });

            // Property inquiry from agent page
            $(document).on('submit', '#agent-property-inquiry', (e) => {
                this.handlePropertyInquiry(e);
            });

            // Buyer registration form
            $(document).on('submit', '#buyer-registration-form', (e) => {
                this.handleBuyerRegistration(e);
            });

            // Contact modal triggers
            $(document).on('click', '.hph-contact-agent-modal', (e) => {
                this.openContactModal(e);
            });

            // Property inquiry modal
            $(document).on('click', '.hph-property-inquiry-modal', (e) => {
                this.openPropertyInquiryModal(e);
            });

            // Modal close
            $(document).on('click', '.hph-modal-close, .hph-modal-overlay', (e) => {
                this.closeModal(e);
            });

            // Phone number formatting
            $(document).on('input', 'input[type="tel"]', (e) => {
                this.formatPhoneNumber(e);
            });

            // Email validation
            $(document).on('blur', 'input[type="email"]', (e) => {
                this.validateEmail(e);
            });
        }

        handleContactForm(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.text();

            if (!this.validateForm($form)) {
                return;
            }

            // Show loading state
            $submitBtn.prop('disabled', true).text('Sending...');

            const formData = {
                action: 'hph_agent_contact',
                nonce: hph_ajax.nonce,
                agent_id: $form.find('input[name="agent_id"]').val(),
                name: $form.find('input[name="contact_name"]').val(),
                email: $form.find('input[name="contact_email"]').val(),
                phone: $form.find('input[name="contact_phone"]').val(),
                subject: $form.find('select[name="contact_subject"]').val(),
                message: $form.find('textarea[name="contact_message"]').val(),
                preferred_contact: $form.find('select[name="preferred_contact"]').val(),
                best_time: $form.find('select[name="best_time"]').val()
            };

            $.ajax({
                url: hph_ajax.ajax_url,
                type: 'POST',
                data: formData,
                success: (response) => {
                    if (response.success) {
                        this.showSuccessMessage('Your message has been sent successfully! The agent will contact you soon.');
                        $form[0].reset();
                        this.closeModal();
                    } else {
                        this.showErrorMessage(response.data.message || 'There was an error sending your message.');
                    }
                },
                error: () => {
                    this.showErrorMessage('There was an error sending your message. Please try again.');
                },
                complete: () => {
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        }

        handlePropertyInquiry(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const formData = {
                action: 'hph_agent_property_inquiry',
                nonce: hph_ajax.nonce,
                agent_id: $form.find('input[name="agent_id"]').val(),
                inquiry_type: $form.find('select[name="inquiry_type"]').val(),
                price_range_min: $form.find('input[name="price_range_min"]').val(),
                price_range_max: $form.find('input[name="price_range_max"]').val(),
                property_type: $form.find('select[name="property_type"]').val(),
                bedrooms: $form.find('select[name="bedrooms"]').val(),
                bathrooms: $form.find('select[name="bathrooms"]').val(),
                location: $form.find('input[name="location"]').val(),
                timeline: $form.find('select[name="timeline"]').val(),
                additional_requirements: $form.find('textarea[name="additional_requirements"]').val()
            };

            $.ajax({
                url: hph_ajax.ajax_url,
                type: 'POST',
                data: formData,
                success: (response) => {
                    if (response.success) {
                        this.showSuccessMessage('Your property inquiry has been submitted! The agent will search for matching properties.');
                        this.closeModal();
                    } else {
                        this.showErrorMessage(response.data.message || 'There was an error submitting your inquiry.');
                    }
                },
                error: () => {
                    this.showErrorMessage('There was an error submitting your inquiry. Please try again.');
                }
            });
        }

        handleBuyerRegistration(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            if (!this.validateBuyerForm($form)) {
                return;
            }

            const formData = {
                action: 'hph_buyer_registration',
                nonce: hph_ajax.nonce,
                first_name: $form.find('input[name="first_name"]').val(),
                last_name: $form.find('input[name="last_name"]').val(),
                email: $form.find('input[name="email"]').val(),
                phone: $form.find('input[name="phone"]').val(),
                agent_id: $form.find('input[name="preferred_agent"]').val(),
                prequalified: $form.find('input[name="prequalified"]').is(':checked'),
                budget_min: $form.find('input[name="budget_min"]').val(),
                budget_max: $form.find('input[name="budget_max"]').val(),
                financing_type: $form.find('select[name="financing_type"]').val(),
                areas_of_interest: $form.find('textarea[name="areas_of_interest"]').val()
            };

            $.ajax({
                url: hph_ajax.ajax_url,
                type: 'POST',
                data: formData,
                success: (response) => {
                    if (response.success) {
                        this.showSuccessMessage('Welcome! Your buyer profile has been created. You will receive property updates that match your criteria.');
                        window.location.href = response.data.redirect_url || '/dashboard/';
                    } else {
                        this.showErrorMessage(response.data.message || 'There was an error creating your profile.');
                    }
                },
                error: () => {
                    this.showErrorMessage('There was an error creating your profile. Please try again.');
                }
            });
        }

        initContactModal() {
            // Pre-populate modal with agent information if available
            $(document).on('show', '.hph-modal', (e) => {
                const $modal = $(e.target);
                const agentId = $modal.data('agent-id');
                
                if (agentId && $modal.find('input[name="agent_id"]').length) {
                    $modal.find('input[name="agent_id"]').val(agentId);
                }
            });
        }

        initPropertyInquiry() {
            // Price range slider functionality
            const $priceMin = $('input[name="price_range_min"]');
            const $priceMax = $('input[name="price_range_max"]');
            
            if ($priceMin.length && $priceMax.length) {
                $priceMin.on('input', () => {
                    const minVal = parseInt($priceMin.val());
                    const maxVal = parseInt($priceMax.val());
                    
                    if (minVal >= maxVal) {
                        $priceMax.val(minVal + 50000);
                    }
                    
                    this.updatePriceDisplay();
                });

                $priceMax.on('input', () => {
                    const minVal = parseInt($priceMin.val());
                    const maxVal = parseInt($priceMax.val());
                    
                    if (maxVal <= minVal) {
                        $priceMin.val(maxVal - 50000);
                    }
                    
                    this.updatePriceDisplay();
                });

                this.updatePriceDisplay();
            }
        }

        updatePriceDisplay() {
            const $priceMin = $('input[name="price_range_min"]');
            const $priceMax = $('input[name="price_range_max"]');
            const $display = $('.hph-price-range-display');
            
            if ($display.length) {
                const minFormatted = parseInt($priceMin.val()).toLocaleString();
                const maxFormatted = parseInt($priceMax.val()).toLocaleString();
                $display.text(`$${minFormatted} - $${maxFormatted}`);
            }
        }

        initBuyerRegistration() {
            // Pre-qualification status handling
            $(document).on('change', 'input[name="prequalified"]', (e) => {
                const $financingSection = $('.hph-financing-details');
                
                if ($(e.target).is(':checked')) {
                    $financingSection.slideDown();
                } else {
                    $financingSection.slideUp();
                }
            });
        }

        openContactModal(e) {
            e.preventDefault();
            
            const $trigger = $(e.currentTarget);
            const agentId = $trigger.data('agent-id');
            const agentName = $trigger.data('agent-name');
            
            let $modal = $('#agent-contact-modal');
            
            if (!$modal.length) {
                $modal = this.createContactModal();
            }
            
            // Populate agent information
            if (agentId) {
                $modal.find('input[name="agent_id"]').val(agentId);
                $modal.find('.hph-agent-name').text(agentName || 'Agent');
            }
            
            $modal.addClass('active');
        }

        openPropertyInquiryModal(e) {
            e.preventDefault();
            
            const $trigger = $(e.currentTarget);
            const agentId = $trigger.data('agent-id');
            
            let $modal = $('#property-inquiry-modal');
            
            if (!$modal.length) {
                $modal = this.createPropertyInquiryModal();
            }
            
            if (agentId) {
                $modal.find('input[name="agent_id"]').val(agentId);
            }
            
            $modal.addClass('active');
        }

        createContactModal() {
            const modalHTML = `
                <div class="hph-modal" id="agent-contact-modal">
                    <div class="hph-modal-overlay"></div>
                    <div class="hph-modal-content">
                        <div class="hph-modal-header">
                            <h3>Contact <span class="hph-agent-name">Agent</span></h3>
                            <button class="hph-modal-close">&times;</button>
                        </div>
                        <form id="agent-contact-form" class="hph-modal-body">
                            <input type="hidden" name="agent_id" value="">
                            
                            <div class="hph-form-row">
                                <div class="hph-form-group">
                                    <label for="contact_name">Name *</label>
                                    <input type="text" id="contact_name" name="contact_name" required>
                                </div>
                                <div class="hph-form-group">
                                    <label for="contact_email">Email *</label>
                                    <input type="email" id="contact_email" name="contact_email" required>
                                </div>
                            </div>
                            
                            <div class="hph-form-group">
                                <label for="contact_phone">Phone</label>
                                <input type="tel" id="contact_phone" name="contact_phone">
                            </div>
                            
                            <div class="hph-form-row">
                                <div class="hph-form-group">
                                    <label for="contact_subject">Subject</label>
                                    <select id="contact_subject" name="contact_subject">
                                        <option value="general">General Inquiry</option>
                                        <option value="buying">Buying a Home</option>
                                        <option value="selling">Selling a Home</option>
                                        <option value="investing">Investment Properties</option>
                                        <option value="market_analysis">Market Analysis</option>
                                    </select>
                                </div>
                                <div class="hph-form-group">
                                    <label for="preferred_contact">Preferred Contact</label>
                                    <select id="preferred_contact" name="preferred_contact">
                                        <option value="email">Email</option>
                                        <option value="phone">Phone</option>
                                        <option value="text">Text Message</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="hph-form-group">
                                <label for="best_time">Best Time to Contact</label>
                                <select id="best_time" name="best_time">
                                    <option value="anytime">Anytime</option>
                                    <option value="morning">Morning (9am-12pm)</option>
                                    <option value="afternoon">Afternoon (12pm-5pm)</option>
                                    <option value="evening">Evening (5pm-8pm)</option>
                                    <option value="weekend">Weekends</option>
                                </select>
                            </div>
                            
                            <div class="hph-form-group">
                                <label for="contact_message">Message *</label>
                                <textarea id="contact_message" name="contact_message" rows="4" required placeholder="How can this agent help you?"></textarea>
                            </div>
                            
                            <button type="submit" class="hph-btn hph-btn-primary">Send Message</button>
                        </form>
                    </div>
                </div>
            `;
            
            $('body').append(modalHTML);
            return $('#agent-contact-modal');
        }

        createPropertyInquiryModal() {
            const modalHTML = `
                <div class="hph-modal" id="property-inquiry-modal">
                    <div class="hph-modal-overlay"></div>
                    <div class="hph-modal-content hph-modal-large">
                        <div class="hph-modal-header">
                            <h3>Property Search Inquiry</h3>
                            <button class="hph-modal-close">&times;</button>
                        </div>
                        <form id="agent-property-inquiry" class="hph-modal-body">
                            <input type="hidden" name="agent_id" value="">
                            
                            <div class="hph-form-group">
                                <label for="inquiry_type">I'm looking to:</label>
                                <select id="inquiry_type" name="inquiry_type" required>
                                    <option value="">Select an option</option>
                                    <option value="buy">Buy a home</option>
                                    <option value="sell">Sell my home</option>
                                    <option value="invest">Invest in property</option>
                                    <option value="rent">Rent a property</option>
                                </select>
                            </div>
                            
                            <div class="hph-form-section">
                                <h4>Budget & Property Details</h4>
                                <div class="hph-form-row">
                                    <div class="hph-form-group">
                                        <label for="price_range_min">Min Price</label>
                                        <input type="range" id="price_range_min" name="price_range_min" min="100000" max="2000000" value="300000" step="25000">
                                    </div>
                                    <div class="hph-form-group">
                                        <label for="price_range_max">Max Price</label>
                                        <input type="range" id="price_range_max" name="price_range_max" min="100000" max="2000000" value="600000" step="25000">
                                    </div>
                                </div>
                                <div class="hph-price-range-display"></div>
                            </div>
                            
                            <div class="hph-form-row">
                                <div class="hph-form-group">
                                    <label for="property_type">Property Type</label>
                                    <select id="property_type" name="property_type">
                                        <option value="">Any</option>
                                        <option value="single-family">Single Family</option>
                                        <option value="condo">Condo</option>
                                        <option value="townhouse">Townhouse</option>
                                        <option value="multi-family">Multi-Family</option>
                                        <option value="land">Land</option>
                                    </select>
                                </div>
                                <div class="hph-form-group">
                                    <label for="bedrooms">Bedrooms</label>
                                    <select id="bedrooms" name="bedrooms">
                                        <option value="">Any</option>
                                        <option value="1">1+</option>
                                        <option value="2">2+</option>
                                        <option value="3">3+</option>
                                        <option value="4">4+</option>
                                        <option value="5">5+</option>
                                    </select>
                                </div>
                                <div class="hph-form-group">
                                    <label for="bathrooms">Bathrooms</label>
                                    <select id="bathrooms" name="bathrooms">
                                        <option value="">Any</option>
                                        <option value="1">1+</option>
                                        <option value="2">2+</option>
                                        <option value="3">3+</option>
                                        <option value="4">4+</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="hph-form-group">
                                <label for="location">Preferred Areas</label>
                                <input type="text" id="location" name="location" placeholder="City, neighborhood, or ZIP code">
                            </div>
                            
                            <div class="hph-form-group">
                                <label for="timeline">Timeline</label>
                                <select id="timeline" name="timeline">
                                    <option value="immediately">Ready to buy immediately</option>
                                    <option value="1-3-months">1-3 months</option>
                                    <option value="3-6-months">3-6 months</option>
                                    <option value="6-12-months">6-12 months</option>
                                    <option value="1-year-plus">More than a year</option>
                                </select>
                            </div>
                            
                            <div class="hph-form-group">
                                <label for="additional_requirements">Additional Requirements</label>
                                <textarea id="additional_requirements" name="additional_requirements" rows="3" placeholder="Any specific features, amenities, or requirements you're looking for?"></textarea>
                            </div>
                            
                            <button type="submit" class="hph-btn hph-btn-primary">Submit Inquiry</button>
                        </form>
                    </div>
                </div>
            `;
            
            $('body').append(modalHTML);
            return $('#property-inquiry-modal');
        }

        closeModal(e) {
            if (e && !$(e.target).hasClass('hph-modal-close') && !$(e.target).hasClass('hph-modal-overlay')) {
                return;
            }
            
            $('.hph-modal').removeClass('active');
        }

        validateForm($form) {
            let isValid = true;
            const requiredFields = $form.find('[required]');
            
            requiredFields.each((index, field) => {
                const $field = $(field);
                if (!$field.val().trim()) {
                    $field.addClass('error');
                    isValid = false;
                } else {
                    $field.removeClass('error');
                }
            });
            
            return isValid;
        }

        validateBuyerForm($form) {
            return this.validateForm($form);
        }

        validateEmail(e) {
            const $input = $(e.target);
            const email = $input.val();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && !emailRegex.test(email)) {
                $input.addClass('error');
                this.showFieldError($input, 'Please enter a valid email address');
            } else {
                $input.removeClass('error');
                this.hideFieldError($input);
            }
        }

        formatPhoneNumber(e) {
            const $input = $(e.target);
            let value = $input.val().replace(/\D/g, '');
            
            if (value.length >= 6) {
                value = `(${value.slice(0, 3)}) ${value.slice(3, 6)}-${value.slice(6, 10)}`;
            } else if (value.length >= 3) {
                value = `(${value.slice(0, 3)}) ${value.slice(3)}`;
            }
            
            $input.val(value);
        }

        showFieldError($field, message) {
            const $error = $field.siblings('.hph-field-error');
            if ($error.length) {
                $error.text(message);
            } else {
                $field.after(`<div class="hph-field-error">${message}</div>`);
            }
        }

        hideFieldError($field) {
            $field.siblings('.hph-field-error').remove();
        }

        showSuccessMessage(message) {
            this.showMessage(message, 'success');
        }

        showErrorMessage(message) {
            this.showMessage(message, 'error');
        }

        showMessage(message, type) {
            const $message = $(`
                <div class="hph-message hph-message--${type}">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                    ${message}
                </div>
            `);

            $('body').append($message);
            $message.addClass('show');

            setTimeout(() => {
                $message.removeClass('show');
                setTimeout(() => $message.remove(), 300);
            }, 3000);
        }
    }

    // Initialize when DOM is ready
    $(document).ready(() => {
        new AgentContact();
    });

})(jQuery);
