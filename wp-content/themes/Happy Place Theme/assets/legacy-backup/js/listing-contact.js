/**
 * Listing Contact JavaScript
 * 
 * Handles contact forms, agent interactions, and inquiry submissions
 * for individual listing pages.
 * 
 * @package HappyPlace
 */

(function($) {
    'use strict';

    class ListingContact {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
            this.initMortgageCalculator();
            this.initScheduleShowing();
            this.initFavorites();
        }

        bindEvents() {
            // Property inquiry form
            $(document).on('submit', '#property-inquiry-form', (e) => {
                this.handleInquiryForm(e);
            });

            // Schedule showing form
            $(document).on('submit', '#schedule-showing-form', (e) => {
                this.handleScheduleForm(e);
            });

            // Contact agent buttons
            $(document).on('click', '.hph-contact-agent', (e) => {
                this.handleContactAgent(e);
            });

            // Mortgage calculator
            $(document).on('input', '#mortgage-calculator-form input', () => {
                this.calculateMortgage();
            });

            // Share functionality
            $(document).on('click', '.hph-btn-share', (e) => {
                this.handleShare(e);
            });
        }

        handleInquiryForm(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.text();

            // Show loading state
            $submitBtn.prop('disabled', true).text('Sending...');

            const formData = {
                action: 'hph_property_inquiry',
                nonce: hph_ajax.nonce,
                property_id: $form.find('input[name="property_id"]').val(),
                name: $form.find('input[name="inquiry_name"]').val(),
                email: $form.find('input[name="inquiry_email"]').val(),
                phone: $form.find('input[name="inquiry_phone"]').val(),
                message: $form.find('textarea[name="inquiry_message"]').val(),
                tour_request: $form.find('input[name="tour_request"]').is(':checked')
            };

            $.ajax({
                url: hph_ajax.ajax_url,
                type: 'POST',
                data: formData,
                success: (response) => {
                    if (response.success) {
                        this.showSuccessMessage('Your inquiry has been sent successfully!');
                        $form[0].reset();
                    } else {
                        this.showErrorMessage(response.data.message || 'There was an error sending your inquiry.');
                    }
                },
                error: () => {
                    this.showErrorMessage('There was an error sending your inquiry. Please try again.');
                },
                complete: () => {
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        }

        handleScheduleForm(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const formData = {
                action: 'hph_schedule_showing',
                nonce: hph_ajax.nonce,
                property_id: $form.find('input[name="property_id"]').val(),
                preferred_date: $form.find('input[name="preferred_date"]').val(),
                preferred_time: $form.find('select[name="preferred_time"]').val(),
                contact_method: $form.find('select[name="contact_method"]').val(),
                notes: $form.find('textarea[name="notes"]').val()
            };

            $.ajax({
                url: hph_ajax.ajax_url,
                type: 'POST',
                data: formData,
                success: (response) => {
                    if (response.success) {
                        this.showSuccessMessage('Your showing has been scheduled successfully!');
                        this.closeModal();
                    } else {
                        this.showErrorMessage(response.data.message || 'There was an error scheduling your showing.');
                    }
                },
                error: () => {
                    this.showErrorMessage('There was an error scheduling your showing. Please try again.');
                }
            });
        }

        handleContactAgent(e) {
            e.preventDefault();
            
            const $btn = $(e.currentTarget);
            const agentId = $btn.data('agent-id');
            const propertyId = $btn.data('property-id');

            // Open contact modal or redirect to agent page
            if ($('#agent-contact-modal').length) {
                this.openAgentContactModal(agentId, propertyId);
            } else {
                // Fallback to agent page
                window.location.href = $btn.attr('href');
            }
        }

        initMortgageCalculator() {
            const $calculator = $('#mortgage-calculator-form');
            if (!$calculator.length) return;

            // Set default values
            const propertyPrice = $('#property-price').val();
            if (propertyPrice) {
                $calculator.find('input[name="home_price"]').val(propertyPrice);
                this.calculateMortgage();
            }
        }

        calculateMortgage() {
            const $form = $('#mortgage-calculator-form');
            if (!$form.length) return;

            const homePrice = parseFloat($form.find('input[name="home_price"]').val()) || 0;
            const downPayment = parseFloat($form.find('input[name="down_payment"]').val()) || 0;
            const interestRate = parseFloat($form.find('input[name="interest_rate"]').val()) || 0;
            const loanTerm = parseFloat($form.find('input[name="loan_term"]').val()) || 30;

            if (homePrice > 0 && interestRate > 0) {
                const loanAmount = homePrice - downPayment;
                const monthlyRate = interestRate / 100 / 12;
                const numberOfPayments = loanTerm * 12;

                const monthlyPayment = loanAmount * 
                    (monthlyRate * Math.pow(1 + monthlyRate, numberOfPayments)) / 
                    (Math.pow(1 + monthlyRate, numberOfPayments) - 1);

                this.displayMortgageResults(monthlyPayment, loanAmount, homePrice);
            }
        }

        displayMortgageResults(monthlyPayment, loanAmount, homePrice) {
            const $result = $('.hph-mortgage-result');
            if (!$result.length) return;

            const resultHTML = `
                <div class="hph-mortgage-summary">
                    <div class="hph-mortgage-payment">
                        <span class="hph-payment-label">Est. Monthly Payment</span>
                        <span class="hph-payment-amount">$${monthlyPayment.toLocaleString('en-US', {maximumFractionDigits: 0})}</span>
                    </div>
                    <div class="hph-mortgage-details">
                        <div class="hph-detail-item">
                            <span>Loan Amount:</span>
                            <span>$${loanAmount.toLocaleString()}</span>
                        </div>
                        <div class="hph-detail-item">
                            <span>Home Price:</span>
                            <span>$${homePrice.toLocaleString()}</span>
                        </div>
                    </div>
                </div>
            `;

            $result.html(resultHTML);
        }

        initScheduleShowing() {
            $(document).on('click', '.hph-btn-schedule', (e) => {
                e.preventDefault();
                this.openScheduleModal();
            });
        }

        openScheduleModal() {
            // If modal exists, show it
            if ($('#schedule-showing-modal').length) {
                $('#schedule-showing-modal').addClass('active');
                return;
            }

            // Create modal if it doesn't exist
            const modalHTML = `
                <div class="hph-modal" id="schedule-showing-modal">
                    <div class="hph-modal-content">
                        <div class="hph-modal-header">
                            <h3>Schedule a Showing</h3>
                            <button class="hph-modal-close">&times;</button>
                        </div>
                        <form id="schedule-showing-form" class="hph-modal-body">
                            <input type="hidden" name="property_id" value="${$('input[name="property_id"]').val()}">
                            
                            <div class="hph-form-group">
                                <label for="preferred_date">Preferred Date</label>
                                <input type="date" id="preferred_date" name="preferred_date" required>
                            </div>
                            
                            <div class="hph-form-group">
                                <label for="preferred_time">Preferred Time</label>
                                <select id="preferred_time" name="preferred_time" required>
                                    <option value="">Select a time</option>
                                    <option value="9:00 AM">9:00 AM</option>
                                    <option value="10:00 AM">10:00 AM</option>
                                    <option value="11:00 AM">11:00 AM</option>
                                    <option value="12:00 PM">12:00 PM</option>
                                    <option value="1:00 PM">1:00 PM</option>
                                    <option value="2:00 PM">2:00 PM</option>
                                    <option value="3:00 PM">3:00 PM</option>
                                    <option value="4:00 PM">4:00 PM</option>
                                    <option value="5:00 PM">5:00 PM</option>
                                </select>
                            </div>
                            
                            <div class="hph-form-group">
                                <label for="contact_method">Preferred Contact Method</label>
                                <select id="contact_method" name="contact_method">
                                    <option value="phone">Phone</option>
                                    <option value="email">Email</option>
                                    <option value="text">Text Message</option>
                                </select>
                            </div>
                            
                            <div class="hph-form-group">
                                <label for="notes">Additional Notes</label>
                                <textarea id="notes" name="notes" rows="3" placeholder="Any special requests or questions?"></textarea>
                            </div>
                            
                            <button type="submit" class="hph-btn hph-btn-primary">Schedule Showing</button>
                        </form>
                    </div>
                </div>
            `;

            $('body').append(modalHTML);
            $('#schedule-showing-modal').addClass('active');
        }

        initFavorites() {
            $(document).on('click', '.hph-btn-favorite', (e) => {
                e.preventDefault();
                
                const $btn = $(e.currentTarget);
                const propertyId = $btn.data('id');
                const nonce = $btn.data('nonce');

                $.ajax({
                    url: hph_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'hph_toggle_favorite',
                        property_id: propertyId,
                        nonce: nonce
                    },
                    success: (response) => {
                        if (response.success) {
                            $btn.toggleClass('is-favorite');
                            const $icon = $btn.find('i');
                            const $text = $btn.find('span');
                            
                            if ($btn.hasClass('is-favorite')) {
                                $icon.removeClass('far').addClass('fas');
                                $text.text('Saved');
                            } else {
                                $icon.removeClass('fas').addClass('far');
                                $text.text('Save');
                            }
                        }
                    }
                });
            });
        }

        handleShare(e) {
            e.preventDefault();
            
            if (navigator.share) {
                navigator.share({
                    title: document.title,
                    url: window.location.href
                });
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(window.location.href).then(() => {
                    this.showSuccessMessage('Link copied to clipboard!');
                });
            }
        }

        openAgentContactModal(agentId, propertyId) {
            // Implementation for agent contact modal
            console.log('Opening agent contact modal', { agentId, propertyId });
        }

        closeModal() {
            $('.hph-modal').removeClass('active');
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
        new ListingContact();
    });

})(jQuery);
