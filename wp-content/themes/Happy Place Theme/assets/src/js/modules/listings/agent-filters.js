/**
 * Agent Contact JavaScript - Updated to match listing patterns
 * 
 * Handles agent contact forms, messaging, and interaction functionality
 * for agent profiles and contact pages.
 * Following the established design system and patterns.
 * 
 * @package HappyPlace
 */

(function($) {
    'use strict';

    class HPHAgentContact {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
            this.initContactValidation();
            this.initPhoneFormatting();
            this.initFormEnhancements();
        }

        bindEvents() {
            // Contact agent form submissions
            $(document).on('submit', '.hph-quick-contact-form, #agent-contact-form', (e) => {
                this.handleContactForm(e);
            });

            // Property inquiry form
            $(document).on('submit', '#agent-property-inquiry', (e) => {
                this.handlePropertyInquiry(e);
            });

            // Buyer registration form
            $(document).on('submit', '#buyer-registration-form', (e) => {
                this.handleBuyerRegistration(e);
            });

            // Contact modal triggers - handled by main filter script
            // but we can enhance them here if needed

            // Real-time form validation
            $(document).on('blur', 'input[type="email"]', (e) => {
                this.validateEmail(e);
            });

            $(document).on('input', 'input[type="tel"]', (e) => {
                this.formatPhoneNumber(e);
            });

            // Character counter for textareas
            $(document).on('input', 'textarea[maxlength]', (e) => {
                this.updateCharacterCount(e);
            });

            // Enhanced form interactions
            $(document).on('focus', '.hph-form-input, .hph-form-textarea', (e) => {
                this.handleFormFocus(e);
            });

            $(document).on('blur', '.hph-form-input, .hph-form-textarea', (e) => {
                this.handleFormBlur(e);
            });

            // Schedule callback functionality
            $(document).on('click', '.hph-schedule-callback', (e) => {
                this.handleScheduleCallback(e);
            });

            // Save contact for later
            $(document).on('click', '.hph-save-agent', (e) => {
                this.handleSaveAgent(e);
            });
        }

        handleContactForm(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.html();

            // Validate form before submission
            if (!this.validateContactForm($form)) {
                this.showMessage('Please fill in all required fields correctly.', 'error');
                return;
            }

            // Show loading state
            $submitBtn.prop('disabled', true).html(this.getLoadingButton('Sending...'));

            // Collect form data
            const formData = this.collectContactFormData($form);

            $.ajax({
                url: hph_ajax.ajax_url,
                type: 'POST',
                data: formData,
                success: (response) => {
                    if (response.success) {
                        this.handleContactSuccess($form, response.data);
                    } else {
                        this.showMessage(response.data?.message || 'There was an error sending your message.', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Contact form error:', error);
                    this.showMessage('There was an error sending your message. Please try again.', 'error');
                },
                complete: () => {
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            });
        }

        handlePropertyInquiry(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.html();

            if (!this.validatePropertyInquiryForm($form)) {
                this.showMessage('Please complete all required fields.', 'error');
                return;
            }

            $submitBtn.prop('disabled', true).html(this.getLoadingButton('Submitting...'));

            const formData = this.collectPropertyInquiryData($form);

            $.ajax({
                url: hph_ajax.ajax_url,
                type: 'POST',
                data: formData,
                success: (response) => {
                    if (response.success) {
                        this.handleInquirySuccess($form, response.data);
                    } else {
                        this.showMessage(response.data?.message || 'There was an error submitting your inquiry.', 'error');
                    }
                },
                error: () => {
                    this.showMessage('There was an error submitting your inquiry. Please try again.', 'error');
                },
                complete: () => {
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            });
        }

        handleBuyerRegistration(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.html();

            if (!this.validateBuyerRegistrationForm($form)) {
                this.showMessage('Please complete all required fields correctly.', 'error');
                return;
            }

            $submitBtn.prop('disabled', true).html(this.getLoadingButton('Creating Profile...'));

            const formData = this.collectBuyerRegistrationData($form);

            $.ajax({
                url: hph_ajax.ajax_url,
                type: 'POST',
                data: formData,
                success: (response) => {
                    if (response.success) {
                        this.handleRegistrationSuccess(response.data);
                    } else {
                        this.showMessage(response.data?.message || 'There was an error creating your profile.', 'error');
                    }
                },
                error: () => {
                    this.showMessage('There was an error creating your profile. Please try again.', 'error');
                },
                complete: () => {
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            });
        }

        handleScheduleCallback(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const agentId = $button.data('agent-id');
            
            // Create callback scheduling modal
            this.openCallbackScheduler(agentId);
        }

        handleSaveAgent(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const agentId = $button.data('agent-id');
            const isSaved = $button.hasClass('saved');

            $button.prop('disabled', true);

            $.ajax({
                url: hph_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: isSaved ? 'hph_unsave_agent' : 'hph_save_agent',
                    nonce: hph_ajax.nonce,
                    agent_id: agentId
                },
                success: (response) => {
                    if (response.success) {
                        $button.toggleClass('saved');
                        const newText = isSaved ? 'Save Agent' : 'Saved';
                        const newIcon = isSaved ? 'far fa-heart' : 'fas fa-heart';
                        $button.html(`<i class="${newIcon}"></i> ${newText}`);
                        
                        this.showMessage(response.data.message, 'success');
                    } else {
                        this.showMessage('Error saving agent preference.', 'error');
                    }
                },
                error: () => {
                    this.showMessage('Error saving agent preference.', 'error');
                },
                complete: () => {
                    $button.prop('disabled', false);
                }
            });
        }

        // Form validation methods
        validateContactForm($form) {
            let isValid = true;
            
            // Check required fields
            $form.find('[required]').each((index, field) => {
                const $field = $(field);
                const value = $field.val().trim();
                
                if (!value) {
                    this.showFieldError($field, 'This field is required');
                    isValid = false;
                } else {
                    this.hideFieldError($field);
                }
            });

            // Validate email format
            const $email = $form.find('input[type="email"]');
            if ($email.length && $email.val()) {
                if (!this.isValidEmail($email.val())) {
                    this.showFieldError($email, 'Please enter a valid email address');
                    isValid = false;
                }
            }

            // Validate phone format
            const $phone = $form.find('input[type="tel"]');
            if ($phone.length && $phone.val()) {
                if (!this.isValidPhone($phone.val())) {
                    this.showFieldError($phone, 'Please enter a valid phone number');
                    isValid = false;
                }
            }

            return isValid;
        }

        validatePropertyInquiryForm($form) {
            let isValid = true;
            
            // Check required fields
            $form.find('[required]').each((index, field) => {
                const $field = $(field);
                if (!$field.val().trim()) {
                    this.showFieldError($field, 'This field is required');
                    isValid = false;
                } else {
                    this.hideFieldError($field);
                }
            });

            // Validate price range
            const minPrice = parseInt($form.find('input[name="price_range_min"]').val()) || 0;
            const maxPrice = parseInt($form.find('input[name="price_range_max"]').val()) || 0;
            
            if (maxPrice > 0 && minPrice >= maxPrice) {
                this.showMessage('Maximum price must be greater than minimum price.', 'error');
                isValid = false;
            }

            return isValid;
        }

        validateBuyerRegistrationForm($form) {
            let isValid = true;
            
            // Check required fields
            $form.find('[required]').each((index, field) => {
                const $field = $(field);
                if (!$field.val().trim()) {
                    this.showFieldError($field, 'This field is required');
                    isValid = false;
                } else {
                    this.hideFieldError($field);
                }
            });

            // Validate email
            const $email = $form.find('input[name="email"]');
            if ($email.length && !this.isValidEmail($email.val())) {
                this.showFieldError($email, 'Please enter a valid email address');
                isValid = false;
            }

            // Validate budget range
            const minBudget = parseInt($form.find('input[name="budget_min"]').val()) || 0;
            const maxBudget = parseInt($form.find('input[name="budget_max"]').val()) || 0;
            
            if (maxBudget > 0 && minBudget >= maxBudget) {
                this.showMessage('Maximum budget must be greater than minimum budget.', 'error');
                isValid = false;
            }

            return isValid;
        }

        // Data collection methods
        collectContactFormData($form) {
            return {
                action: 'hph_agent_contact',
                nonce: hph_ajax.nonce,
                agent_id: $form.find('input[name="agent_id"]').val(),
                name: $form.find('input[name="contact_name"]').val(),
                email: $form.find('input[name="contact_email"]').val(),
                phone: $form.find('input[name="contact_phone"]').val(),
                subject: $form.find('select[name="contact_subject"]').val(),
                message: $form.find('textarea[name="contact_message"]').val(),
                preferred_contact: $form.find('select[name="preferred_contact"]').val(),
                best_time: $form.find('select[name="best_time"]').val(),
                source: 'agent_profile'
            };
        }

        collectPropertyInquiryData($form) {
            return {
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
                additional_requirements: $form.find('textarea[name="additional_requirements"]').val(),
                contact_name: $form.find('input[name="contact_name"]').val(),
                contact_email: $form.find('input[name="contact_email"]').val(),
                contact_phone: $form.find('input[name="contact_phone"]').val()
            };
        }

        collectBuyerRegistrationData($form) {
            return {
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
                areas_of_interest: $form.find('textarea[name="areas_of_interest"]').val(),
                timeline: $form.find('select[name="timeline"]').val(),
                property_type_preferences: this.getSelectedCheckboxValues($form, 'property_types[]')
            };
        }

        // Success handlers
        handleContactSuccess($form, data) {
            this.showMessage('Your message has been sent successfully! The agent will contact you soon.', 'success');
            
            // Reset form
            $form[0].reset();
            
            // Close modal if in modal
            if ($form.closest('.hph-modal').length) {
                $('.hph-modal').removeClass('active');
                $('body').removeClass('modal-open');
            }

            // Track conversion event
            this.trackEvent('agent_contact_submitted', {
                agent_id: data.agent_id,
                contact_method: 'form'
            });

            // Show follow-up options
            if (data.show_followup) {
                this.showFollowUpOptions(data);
            }
        }

        handleInquirySuccess($form, data) {
            this.showMessage('Your property inquiry has been submitted! The agent will search for matching properties and contact you soon.', 'success');
            
            // Close modal
            $('.hph-modal').removeClass('active');
            $('body').removeClass('modal-open');

            // Track conversion
            this.trackEvent('property_inquiry_submitted', {
                agent_id: data.agent_id,
                inquiry_type: data.inquiry_type
            });

            // Redirect to search results if available
            if (data.search_url) {
                setTimeout(() => {
                    window.location.href = data.search_url;
                }, 2000);
            }
        }

        handleRegistrationSuccess(data) {
            this.showMessage('Welcome! Your buyer profile has been created. You will receive property updates that match your criteria.', 'success');
            
            // Track registration
            this.trackEvent('buyer_registration_completed', {
                user_id: data.user_id
            });

            // Redirect to dashboard
            setTimeout(() => {
                window.location.href = data.redirect_url || '/dashboard/';
            }, 2000);
        }

        // Enhanced form interactions
        initContactValidation() {
            // Real-time validation feedback
            $(document).on('input', '.hph-form-input, .hph-form-textarea', (e) => {
                const $field = $(e.target);
                if ($field.hasClass('error')) {
                    this.validateField($field);
                }
            });
        }

        initPhoneFormatting() {
            // Enhanced phone number formatting
            $(document).on('input', 'input[type="tel"]', (e) => {
                this.formatPhoneNumber(e);
            });

            // International phone support
            if (typeof intlTelInput !== 'undefined') {
                $('input[type="tel"]').each((index, element) => {
                    intlTelInput(element, {
                        preferredCountries: ['us', 'ca'],
                        separateDialCode: true,
                        utilsScript: '/assets/js/utils.js'
                    });
                });
            }
        }

        initFormEnhancements() {
            // Add character counters to textareas
            $('textarea[maxlength]').each((index, element) => {
                this.addCharacterCounter($(element));
            });

            // Add form progress indicators for multi-step forms
            $('.hph-multi-step-form').each((index, element) => {
                this.initMultiStepForm($(element));
            });

            // Add smart form suggestions
            this.initFormSuggestions();
        }

        // Validation helpers
        validateField($field) {
            const fieldType = $field.attr('type');
            const fieldName = $field.attr('name');
            const value = $field.val().trim();

            // Clear previous errors
            this.hideFieldError($field);

            // Required field check
            if ($field.prop('required') && !value) {
                this.showFieldError($field, 'This field is required');
                return false;
            }

            // Type-specific validation
            switch (fieldType) {
                case 'email':
                    if (value && !this.isValidEmail(value)) {
                        this.showFieldError($field, 'Please enter a valid email address');
                        return false;
                    }
                    break;
                    
                case 'tel':
                    if (value && !this.isValidPhone(value)) {
                        this.showFieldError($field, 'Please enter a valid phone number');
                        return false;
                    }
                    break;
                    
                case 'number':
                    const min = parseFloat($field.attr('min'));
                    const max = parseFloat($field.attr('max'));
                    const numValue = parseFloat(value);
                    
                    if (value && isNaN(numValue)) {
                        this.showFieldError($field, 'Please enter a valid number');
                        return false;
                    }
                    
                    if (!isNaN(min) && numValue < min) {
                        this.showFieldError($field, `Value must be at least ${min}`);
                        return false;
                    }
                    
                    if (!isNaN(max) && numValue > max) {
                        this.showFieldError($field, `Value must be no more than ${max}`);
                        return false;
                    }
                    break;
            }

            // Field-specific validation
            if (fieldName === 'zip_code' && value) {
                if (!/^\d{5}(-\d{4})?$/.test(value)) {
                    this.showFieldError($field, 'Please enter a valid ZIP code');
                    return false;
                }
            }

            return true;
        }

        isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        isValidPhone(phone) {
            const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
            const cleaned = phone.replace(/\D/g, '');
            return cleaned.length >= 10 && phoneRegex.test(cleaned);
        }

        validateEmail(e) {
            const $input = $(e.target);
            this.validateField($input);
        }

        formatPhoneNumber(e) {
            const $input = $(e.target);
            let value = $input.val().replace(/\D/g, '');
            
            // Limit to 10 digits for US format
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            
            // Format as (XXX) XXX-XXXX
            if (value.length >= 6) {
                value = `(${value.slice(0, 3)}) ${value.slice(3, 6)}-${value.slice(6)}`;
            } else if (value.length >= 3) {
                value = `(${value.slice(0, 3)}) ${value.slice(3)}`;
            }
            
            $input.val(value);
        }

        // UI enhancement methods
        handleFormFocus(e) {
            const $field = $(e.target);
            $field.closest('.hph-form-group').addClass('focused');
            this.hideFieldError($field);
        }

        handleFormBlur(e) {
            const $field = $(e.target);
            $field.closest('.hph-form-group').removeClass('focused');
            
            if ($field.val().trim()) {
                $field.closest('.hph-form-group').addClass('filled');
            } else {
                $field.closest('.hph-form-group').removeClass('filled');
            }
            
            // Validate on blur if field has content
            if ($field.val().trim()) {
                this.validateField($field);
            }
        }

        updateCharacterCount(e) {
            const $textarea = $(e.target);
            const maxLength = parseInt($textarea.attr('maxlength'));
            const currentLength = $textarea.val().length;
            const remaining = maxLength - currentLength;
            
            let $counter = $textarea.siblings('.hph-char-counter');
            if (!$counter.length) {
                $counter = $('<div class="hph-char-counter"></div>');
                $textarea.after($counter);
            }
            
            $counter.text(`${remaining} characters remaining`);
            
            if (remaining < 20) {
                $counter.addClass('warning');
            } else {
                $counter.removeClass('warning');
            }
        }

        addCharacterCounter($textarea) {
            const maxLength = parseInt($textarea.attr('maxlength'));
            if (maxLength) {
                const $counter = $('<div class="hph-char-counter"></div>');
                $textarea.after($counter);
                this.updateCharacterCount({ target: $textarea[0] });
            }
        }

        // Advanced features
        openCallbackScheduler(agentId) {
            const modalHTML = `
                <div class="hph-modal" id="callback-scheduler-modal">
                    <div class="hph-modal-overlay"></div>
                    <div class="hph-modal-content">
                        <div class="hph-modal-header">
                            <h3>Schedule a Callback</h3>
                            <button class="hph-modal-close">&times;</button>
                        </div>
                        <form id="callback-scheduler-form" class="hph-modal-body">
                            <input type="hidden" name="agent_id" value="${agentId}">
                            
                            <div class="hph-form-group">
                                <label for="callback_name">Your Name *</label>
                                <input type="text" id="callback_name" name="callback_name" class="hph-form-input" required>
                            </div>
                            
                            <div class="hph-form-group">
                                <label for="callback_phone">Phone Number *</label>
                                <input type="tel" id="callback_phone" name="callback_phone" class="hph-form-input" required>
                            </div>
                            
                            <div class="hph-form-row">
                                <div class="hph-form-group">
                                    <label for="callback_date">Preferred Date *</label>
                                    <input type="date" id="callback_date" name="callback_date" class="hph-form-input" required>
                                </div>
                                <div class="hph-form-group">
                                    <label for="callback_time">Preferred Time *</label>
                                    <select id="callback_time" name="callback_time" class="hph-form-select" required>
                                        <option value="">Select time</option>
                                        <option value="09:00">9:00 AM</option>
                                        <option value="10:00">10:00 AM</option>
                                        <option value="11:00">11:00 AM</option>
                                        <option value="12:00">12:00 PM</option>
                                        <option value="13:00">1:00 PM</option>
                                        <option value="14:00">2:00 PM</option>
                                        <option value="15:00">3:00 PM</option>
                                        <option value="16:00">4:00 PM</option>
                                        <option value="17:00">5:00 PM</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="hph-form-group">
                                <label for="callback_topic">What would you like to discuss?</label>
                                <textarea id="callback_topic" name="callback_topic" class="hph-form-textarea" rows="3" placeholder="Brief description of your real estate needs..."></textarea>
                            </div>
                            
                            <button type="submit" class="hph-btn hph-btn-primary hph-btn-block">
                                <i class="fas fa-calendar-check"></i>
                                Schedule Callback
                            </button>
                        </form>
                    </div>
                </div>
            `;
            
            $('body').append(modalHTML);
            $('#callback-scheduler-modal').addClass('active');
            $('body').addClass('modal-open');
            
            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            $('#callback_date').attr('min', today);
        }

        initFormSuggestions() {
            // Auto-suggest common inquiries
            $('#contact_message').on('focus', function() {
                if (!$(this).val()) {
                    const suggestions = [
                        "I'm interested in buying a home and would like to discuss my options.",
                        "I'm thinking about selling my property and need a market analysis.",
                        "I'd like to schedule a viewing for one of your listings.",
                        "I'm a first-time buyer and need guidance through the process.",
                        "I'm looking for investment properties in the area."
                    ];
                    
                    const suggestionHTML = suggestions.map(text => 
                        `<button type="button" class="hph-suggestion-btn" data-text="${text}">${text}</button>`
                    ).join('');
                    
                    if (!$('.hph-message-suggestions').length) {
                        $(this).after(`<div class="hph-message-suggestions">${suggestionHTML}</div>`);
                    }
                }
            });
            
            $(document).on('click', '.hph-suggestion-btn', function() {
                const text = $(this).data('text');
                $('#contact_message').val(text).trigger('input');
                $('.hph-message-suggestions').remove();
            });
            
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#contact_message, .hph-message-suggestions').length) {
                    $('.hph-message-suggestions').remove();
                }
            });
        }

        // Utility methods
        getSelectedCheckboxValues($form, name) {
            const values = [];
            $form.find(`input[name="${name}"]:checked`).each(function() {
                values.push($(this).val());
            });
            return values;
        }

        getLoadingButton(text) {
            return `<i class="fas fa-spinner fa-spin"></i> ${text}`;
        }

        showFieldError($field, message) {
            $field.addClass('error');
            
            let $error = $field.siblings('.hph-field-error');
            if (!$error.length) {
                $error = $('<div class="hph-field-error"></div>');
                $field.after($error);
            }
            
            $error.text(message).show();
        }

        hideFieldError($field) {
            $field.removeClass('error');
            $field.siblings('.hph-field-error').hide();
        }

        showMessage(message, type = 'info') {
            const $message = $(`
                <div class="hph-toast hph-toast--${type}">
                    <div class="hph-toast-content">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                        <span>${message}</span>
                    </div>
                    <button class="hph-toast-close" aria-label="Close">&times;</button>
                </div>
            `);

            $('body').append($message);
            
            setTimeout(() => $message.addClass('show'), 100);

            // Auto-remove
            setTimeout(() => {
                $message.removeClass('show');
                setTimeout(() => $message.remove(), 300);
            }, 5000);

            // Manual close
            $message.on('click', '.hph-toast-close', () => {
                $message.removeClass('show');
                setTimeout(() => $message.remove(), 300);
            });
        }

        trackEvent(eventName, data) {
            // Google Analytics tracking
            if (typeof gtag !== 'undefined') {
                gtag('event', eventName, data);
            }
            
            // Custom tracking
            if (typeof hph_tracking !== 'undefined') {
                hph_tracking.track(eventName, data);
            }
        }
    }

    // Initialize when DOM is ready
    $(document).ready(() => {
        new HPHAgentContact();
    });

})(jQuery);