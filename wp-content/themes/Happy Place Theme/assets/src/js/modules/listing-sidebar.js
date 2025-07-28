/**
 * Listing Sidebar Module
 * Handles functionality for the listing sidebar including open houses, mortgage calculator, and market info
 */

class ListingSidebar {
    constructor() {
        this.initMortgageCalculator();
        this.initOpenHouseActions();
        this.initMarketInfo();
    }

    /**
     * Initialize mortgage calculator functionality
     */
    initMortgageCalculator() {
        const calculator = document.querySelector('.mortgage-calculator');
        if (!calculator) return;

        const inputs = {
            loanAmount: calculator.querySelector('#loan-amount'),
            downPayment: calculator.querySelector('#down-payment'),
            interestRate: calculator.querySelector('#interest-rate'),
            loanTerm: calculator.querySelector('#loan-term')
        };

        const monthlyPaymentDisplay = calculator.querySelector('#monthly-payment');

        // Calculate mortgage payment
        const calculatePayment = () => {
            const principal = parseFloat(inputs.loanAmount.value) || 0;
            const downPaymentPercent = parseFloat(inputs.downPayment.value) || 0;
            const annualRate = parseFloat(inputs.interestRate.value) || 0;
            const years = parseInt(inputs.loanTerm.value) || 30;

            if (principal <= 0 || annualRate <= 0) {
                monthlyPaymentDisplay.textContent = '$0';
                return;
            }

            const downPaymentAmount = principal * (downPaymentPercent / 100);
            const loanAmount = principal - downPaymentAmount;
            const monthlyRate = annualRate / 100 / 12;
            const numberOfPayments = years * 12;

            let monthlyPayment = 0;
            if (monthlyRate > 0) {
                monthlyPayment = loanAmount * (monthlyRate * Math.pow(1 + monthlyRate, numberOfPayments)) / 
                                (Math.pow(1 + monthlyRate, numberOfPayments) - 1);
            } else {
                monthlyPayment = loanAmount / numberOfPayments;
            }

            monthlyPaymentDisplay.textContent = this.formatCurrency(monthlyPayment);
        };

        // Bind events
        Object.values(inputs).forEach(input => {
            if (input) {
                input.addEventListener('input', calculatePayment);
                input.addEventListener('change', calculatePayment);
            }
        });

        // Initial calculation
        calculatePayment();
    }

    /**
     * Initialize open house actions
     */
    initOpenHouseActions() {
        // RSVP button functionality
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="rsvp-open-house"]') || 
                e.target.closest('[data-action="rsvp-open-house"]')) {
                
                const button = e.target.matches('[data-action="rsvp-open-house"]') ? 
                               e.target : e.target.closest('[data-action="rsvp-open-house"]');
                
                const openHouseId = button.dataset.openHouseId;
                this.handleRSVP(openHouseId, button);
            }
        });

        // Virtual tour links
        document.addEventListener('click', (e) => {
            if (e.target.matches('a[href*="virtual"]') || 
                e.target.closest('a[href*="virtual"]')) {
                
                const link = e.target.matches('a[href*="virtual"]') ? 
                           e.target : e.target.closest('a[href*="virtual"]');
                
                // Track virtual tour click
                this.trackEvent('virtual_tour_click', {
                    url: link.href,
                    source: 'open_house_sidebar'
                });
            }
        });
    }

    /**
     * Initialize market information features
     */
    initMarketInfo() {
        const marketInfo = document.querySelector('.days-on-market');
        if (!marketInfo) return;

        // Add tooltips for market stats
        const statItems = marketInfo.querySelectorAll('.stat-item');
        statItems.forEach(item => {
            const label = item.querySelector('.stat-label');
            const value = item.querySelector('.stat-value');
            
            if (label && value) {
                // Add hover effects and potential tooltip information
                item.addEventListener('mouseenter', () => {
                    this.showMarketTooltip(item, label.textContent, value.textContent);
                });
                
                item.addEventListener('mouseleave', () => {
                    this.hideMarketTooltip();
                });
            }
        });
    }

    /**
     * Handle RSVP functionality
     */
    async handleRSVP(openHouseId, button) {
        if (!openHouseId) {
            console.error('No open house ID provided');
            return;
        }

        // Disable button during request
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

        try {
            // Check if user is logged in
            const isLoggedIn = document.body.classList.contains('logged-in');
            
            if (!isLoggedIn) {
                // Show login modal or redirect to login
                this.showLoginPrompt('rsvp');
                return;
            }

            // Show RSVP modal with form
            this.showRSVPModal(openHouseId);
            
        } catch (error) {
            console.error('RSVP error:', error);
            this.showError('Sorry, there was an error processing your RSVP. Please try again.');
        } finally {
            // Re-enable button
            button.disabled = false;
            button.innerHTML = originalText;
        }
    }

    /**
     * Show RSVP modal
     */
    showRSVPModal(openHouseId) {
        // Create modal HTML
        const modalHTML = `
            <div class="hph-modal open-house-rsvp-modal" id="rsvp-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>RSVP for Open House</h3>
                        <button class="modal-close" aria-label="Close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form id="rsvp-form" data-open-house-id="${openHouseId}">
                            <div class="form-group">
                                <label for="rsvp-name">Full Name *</label>
                                <input type="text" id="rsvp-name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="rsvp-email">Email *</label>
                                <input type="email" id="rsvp-email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="rsvp-phone">Phone</label>
                                <input type="tel" id="rsvp-phone" name="phone">
                            </div>
                            <div class="form-group">
                                <label for="rsvp-attendees">Number of Attendees</label>
                                <select id="rsvp-attendees" name="attendees">
                                    <option value="1">1 person</option>
                                    <option value="2">2 people</option>
                                    <option value="3">3 people</option>
                                    <option value="4">4 people</option>
                                    <option value="5">5+ people</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="rsvp-notes">Special Notes</label>
                                <textarea id="rsvp-notes" name="notes" rows="3" placeholder="Any special requirements or questions?"></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary" id="cancel-rsvp">Cancel</button>
                                <button type="submit" class="btn btn-primary">Confirm RSVP</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        // Add modal to page
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        const modal = document.getElementById('rsvp-modal');
        modal.style.display = 'flex';

        // Bind modal events
        this.bindRSVPModalEvents(modal);
    }

    /**
     * Bind RSVP modal events
     */
    bindRSVPModalEvents(modal) {
        const closeBtn = modal.querySelector('.modal-close');
        const cancelBtn = modal.querySelector('#cancel-rsvp');
        const form = modal.querySelector('#rsvp-form');

        // Close modal events
        [closeBtn, cancelBtn].forEach(btn => {
            if (btn) {
                btn.addEventListener('click', () => {
                    modal.style.display = 'none';
                    document.body.removeChild(modal);
                });
            }
        });

        // Close on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
                document.body.removeChild(modal);
            }
        });

        // Form submission
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.submitRSVP(form, modal);
            });
        }
    }

    /**
     * Submit RSVP form
     */
    async submitRSVP(form, modal) {
        const formData = new FormData(form);
        const openHouseId = form.dataset.openHouseId;
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

        try {
            const response = await fetch(hph_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'hph_rsvp_open_house',
                    nonce: hph_ajax.nonce,
                    open_house_id: openHouseId,
                    name: formData.get('name'),
                    email: formData.get('email'),
                    phone: formData.get('phone'),
                    attendees: formData.get('attendees'),
                    notes: formData.get('notes')
                })
            });

            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('RSVP confirmed! You will receive a confirmation email shortly.');
                modal.style.display = 'none';
                document.body.removeChild(modal);
                
                // Update RSVP button to show confirmed state
                const rsvpButton = document.querySelector(`[data-open-house-id="${openHouseId}"]`);
                if (rsvpButton) {
                    rsvpButton.innerHTML = '<i class="fas fa-check"></i> RSVP Confirmed';
                    rsvpButton.classList.add('rsvp-confirmed');
                    rsvpButton.disabled = true;
                }
            } else {
                throw new Error(result.data || 'RSVP submission failed');
            }
            
        } catch (error) {
            console.error('RSVP submission error:', error);
            this.showError('Sorry, there was an error submitting your RSVP. Please try again.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    /**
     * Show market information tooltip
     */
    showMarketTooltip(element, label, value) {
        // Add contextual information based on the stat type
        let tooltipText = '';
        
        if (label.includes('Days on Market')) {
            const days = parseInt(value);
            if (days < 30) {
                tooltipText = 'This property is relatively new to the market';
            } else if (days < 90) {
                tooltipText = 'This property has been on the market for a moderate time';
            } else {
                tooltipText = 'This property has been on the market for an extended period';
            }
        } else if (label.includes('Listed Date')) {
            tooltipText = 'The date this property was first listed for sale';
        }

        if (tooltipText) {
            this.showTooltip(element, tooltipText);
        }
    }

    /**
     * Show login prompt
     */
    showLoginPrompt(action) {
        const message = action === 'rsvp' ? 
            'Please log in to RSVP for open houses.' : 
            'Please log in to continue.';
            
        if (confirm(message + ' Would you like to log in now?')) {
            window.location.href = '/wp-login.php?redirect_to=' + encodeURIComponent(window.location.href);
        }
    }

    /**
     * Utility methods
     */
    formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount);
    }

    showTooltip(element, text) {
        // Simple tooltip implementation
        const tooltip = document.createElement('div');
        tooltip.className = 'hph-tooltip';
        tooltip.textContent = text;
        tooltip.style.cssText = `
            position: absolute;
            background: #333;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 14px;
            z-index: 1000;
            max-width: 200px;
            pointer-events: none;
        `;
        
        document.body.appendChild(tooltip);
        
        const rect = element.getBoundingClientRect();
        tooltip.style.left = rect.left + 'px';
        tooltip.style.top = (rect.bottom + 5) + 'px';
        
        this.currentTooltip = tooltip;
    }

    hideMarketTooltip() {
        if (this.currentTooltip) {
            document.body.removeChild(this.currentTooltip);
            this.currentTooltip = null;
        }
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `hph-notification ${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 20px;
            border-radius: 4px;
            z-index: 9999;
            max-width: 300px;
            font-weight: 500;
            ${type === 'success' ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : ''}
            ${type === 'error' ? 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;' : ''}
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 5000);
    }

    trackEvent(eventName, data = {}) {
        // Google Analytics or other tracking
        if (typeof gtag !== 'undefined') {
            gtag('event', eventName, data);
        }
        
        // Console log for development
        console.log('Event tracked:', eventName, data);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new ListingSidebar();
});

// Export for use in other modules
window.ListingSidebar = ListingSidebar;
