<?php
/**
 * Mortgage Calculator Sidebar Template Part
 * 
 * Interactive mortgage calculator for listings
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

namespace HappyPlace\Templates;

if (!defined('ABSPATH')) {
    exit;
}

// Extract data from template args
$listing_data = $args['data'] ?? [];
$listing_id = $args['listing_id'] ?? get_the_ID();

// Use bridge functions for pricing data
$price = function_exists('hph_bridge_get_price') ? hph_bridge_get_price($listing_id, false) : 0;
$property_tax = function_exists('hph_get_listing_field') ? hph_get_listing_field($listing_id, 'property_tax', 0) : 0;
$hoa_fees = function_exists('hph_get_listing_field') ? hph_get_listing_field($listing_id, 'hoa_fees', 0) : 0;

// Fallback to legacy data structure if bridge functions not available
if ($price === 0 && !empty($listing_data['core']['price'])) {
    $price = $listing_data['core']['price'];
}
if ($property_tax === 0 && !empty($listing_data['details']['property_tax'])) {
    $property_tax = $listing_data['details']['property_tax'];
}
if ($hoa_fees === 0 && !empty($listing_data['details']['hoa_fees'])) {
    $hoa_fees = $listing_data['details']['hoa_fees'];
}

// Get mortgage data from bridge functions or ACF fields
$mortgage_data = function_exists('hph_get_listing_field') ? hph_get_listing_field($listing_id, 'mortgage_info', []) : [];
if (empty($mortgage_data) && !empty($listing_data['analytics']['mortgage'])) {
    $mortgage_data = $listing_data['analytics']['mortgage'];
}

$down_payment_percent = $mortgage_data['estimated_down_payment'] ?? 20;
$interest_rate = $mortgage_data['estimated_interest_rate'] ?? 6.5;
$loan_term = $mortgage_data['estimated_loan_term'] ?? 30;
$pmi_rate = $mortgage_data['estimated_pmi_rate'] ?? 0.5;
?>

<div class="sidebar-widget mortgage-calculator-widget">
    <div class="widget-header">
        <h3 class="widget-title">
            <i class="fas fa-calculator"></i>
            Mortgage Calculator
        </h3>
        <p class="widget-subtitle">Estimate your monthly payment</p>
    </div>

    <div class="widget-content">
        <form class="mortgage-form" id="mortgage-calculator">
            
            <!-- Home Price -->
            <div class="form-group">
                <label class="form-label" for="home-price">
                    Home Price
                    <span class="required">*</span>
                </label>
                <div class="input-group">
                    <span class="input-prefix">$</span>
                    <input type="number" 
                           id="home-price" 
                           name="home_price" 
                           value="<?php echo esc_attr($price); ?>"
                           min="0"
                           step="1000"
                           class="form-control"
                           readonly>
                </div>
            </div>

            <!-- Down Payment -->
            <div class="form-group">
                <label class="form-label" for="down-payment-percent">
                    Down Payment
                </label>
                <div class="input-group">
                    <input type="range" 
                           id="down-payment-range" 
                           name="down_payment_percent" 
                           value="<?php echo esc_attr($down_payment_percent); ?>"
                           min="0" 
                           max="50" 
                           step="5"
                           class="range-slider">
                    <div class="range-values">
                        <span class="range-percent" id="down-payment-display"><?php echo $down_payment_percent; ?>%</span>
                        <span class="range-amount" id="down-payment-amount">$<?php echo number_format(($price * $down_payment_percent) / 100); ?></span>
                    </div>
                </div>
            </div>

            <!-- Interest Rate -->
            <div class="form-group">
                <label class="form-label" for="interest-rate">
                    Interest Rate
                </label>
                <div class="input-group">
                    <input type="range" 
                           id="interest-rate-range" 
                           name="interest_rate" 
                           value="<?php echo esc_attr($interest_rate); ?>"
                           min="3" 
                           max="15" 
                           step="0.125"
                           class="range-slider">
                    <div class="range-values">
                        <span class="range-percent" id="interest-rate-display"><?php echo $interest_rate; ?>%</span>
                    </div>
                </div>
            </div>

            <!-- Loan Term -->
            <div class="form-group">
                <label class="form-label" for="loan-term">
                    Loan Term
                </label>
                <div class="form-tabs">
                    <input type="radio" id="term-15" name="loan_term" value="15" <?php checked($loan_term, 15); ?>>
                    <label for="term-15" class="tab-label">15 Years</label>
                    
                    <input type="radio" id="term-20" name="loan_term" value="20" <?php checked($loan_term, 20); ?>>
                    <label for="term-20" class="tab-label">20 Years</label>
                    
                    <input type="radio" id="term-30" name="loan_term" value="30" <?php checked($loan_term, 30); ?>>
                    <label for="term-30" class="tab-label">30 Years</label>
                </div>
            </div>

            <!-- Results Section -->
            <div class="mortgage-results">
                <div class="results-header">
                    <h4>Monthly Payment Breakdown</h4>
                </div>

                <div class="payment-breakdown">
                    <div class="payment-item primary">
                        <span class="payment-label">Principal & Interest</span>
                        <span class="payment-amount" id="principal-interest">$<?php echo number_format($mortgage_data['estimated_monthly_payment'] ?? 0); ?></span>
                    </div>
                    
                    <div class="payment-item">
                        <span class="payment-label">Property Tax</span>
                        <span class="payment-amount" id="property-tax-monthly">$<?php echo number_format(($property_tax ?? 0) / 12); ?></span>
                    </div>
                    
                    <div class="payment-item">
                        <span class="payment-label">Homeowner's Insurance</span>
                        <span class="payment-amount" id="insurance-monthly">$<?php echo number_format($mortgage_data['estimated_monthly_insurance'] ?? 0); ?></span>
                    </div>
                    
                    <?php if ($down_payment_percent < 20): ?>
                    <div class="payment-item pmi">
                        <span class="payment-label">PMI</span>
                        <span class="payment-amount" id="pmi-monthly">$<?php echo number_format($mortgage_data['estimated_pmi'] ?? 0); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($hoa_fees > 0): ?>
                    <div class="payment-item">
                        <span class="payment-label">HOA Fees</span>
                        <span class="payment-amount">$<?php echo number_format($hoa_fees); ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="total-payment">
                    <div class="payment-item total">
                        <span class="payment-label">Total Monthly Payment</span>
                        <span class="payment-amount" id="total-payment">$<?php echo number_format($mortgage_data['total_monthly_cost'] ?? 0); ?></span>
                    </div>
                </div>
            </div>

            <!-- Disclaimer -->
            <div class="calculator-disclaimer">
                <p><small><i class="fas fa-info-circle"></i> This is an estimate for informational purposes only. Actual payments may vary based on taxes, insurance, and other factors.</small></p>
            </div>

            <!-- Action Buttons -->
            <div class="calculator-actions">
                <button type="button" class="hph-btn hph-btn--primary hph-btn--block" id="get-pre-approved">
                    <i class="fas fa-check-circle"></i>
                    Get Pre-Approved
                </button>
                <button type="button" class="hph-btn hph-btn--outline hph-btn--block" id="contact-lender">
                    <i class="fas fa-phone"></i>
                    Contact a Lender
                </button>
            </div>

        </form>
    </div>
</div>

<style>
.mortgage-calculator-widget {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 2rem;
    overflow: hidden;
}

.widget-header {
    padding: 1.5rem;
    border-bottom: 1px solid #f0f0f0;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    color: white;
}

.widget-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.widget-subtitle {
    margin: 0;
    opacity: 0.9;
    font-size: 0.9rem;
}

.widget-content {
    padding: 1.5rem;
}

/* Form Styles */
.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    font-weight: 500;
    color: var(--text-dark);
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.required {
    color: var(--error-color);
}

.input-group {
    position: relative;
}

.input-prefix {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    font-weight: 500;
    z-index: 2;
}

.form-control {
    width: 100%;
    padding: 0.75rem 0.75rem 0.75rem 2rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s ease;
    background: #f9fafb;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    background: white;
}

/* Range Sliders */
.range-slider {
    width: 100%;
    height: 8px;
    border-radius: 4px;
    background: #e5e7eb;
    outline: none;
    -webkit-appearance: none;
    margin-bottom: 1rem;
}

.range-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--primary-color);
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.range-slider::-moz-range-thumb {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--primary-color);
    cursor: pointer;
    border: none;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.range-values {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.range-percent {
    font-weight: 600;
    color: var(--primary-color);
}

.range-amount {
    font-size: 0.9rem;
    color: var(--text-muted);
}

/* Form Tabs */
.form-tabs {
    display: flex;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
}

.form-tabs input[type="radio"] {
    display: none;
}

.tab-label {
    flex: 1;
    padding: 0.75rem 1rem;
    text-align: center;
    cursor: pointer;
    background: #f9fafb;
    border-right: 1px solid #e5e7eb;
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.tab-label:last-child {
    border-right: none;
}

.form-tabs input[type="radio"]:checked + .tab-label {
    background: var(--primary-color);
    color: white;
}

/* Results */
.mortgage-results {
    margin: 2rem 0;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
}

.results-header {
    padding: 1rem 1.5rem;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

.results-header h4 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-dark);
}

.payment-breakdown {
    padding: 1rem 0;
}

.payment-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 1.5rem;
}

.payment-item.primary {
    background: #fef3f2;
}

.payment-item.pmi {
    background: #fff7ed;
}

.payment-label {
    font-size: 0.9rem;
    color: var(--text-dark);
}

.payment-amount {
    font-weight: 600;
    color: var(--text-dark);
}

.total-payment {
    border-top: 2px solid #e5e7eb;
    background: #f9fafb;
}

.payment-item.total .payment-label {
    font-weight: 600;
    font-size: 1rem;
}

.payment-item.total .payment-amount {
    font-size: 1.25rem;
    color: var(--primary-color);
}

/* Disclaimer */
.calculator-disclaimer {
    margin: 1.5rem 0;
    padding: 1rem;
    background: #f0f9ff;
    border-radius: 8px;
    border-left: 4px solid var(--primary-color);
}

.calculator-disclaimer p {
    margin: 0;
    color: var(--text-muted);
    line-height: 1.4;
}

.calculator-disclaimer i {
    color: var(--primary-color);
    margin-right: 0.5rem;
}

/* Action Buttons */
.calculator-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.btn-block {
    width: 100%;
    justify-content: center;
}

.btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
    cursor: pointer;
    border: none;
    font-size: 0.9rem;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
}

.btn-outline {
    background: white;
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}

.btn-outline:hover {
    background: var(--primary-color);
    color: white;
}

@media (max-width: 768px) {
    .widget-header,
    .widget-content {
        padding: 1rem;
    }
    
    .form-tabs {
        flex-direction: column;
    }
    
    .tab-label {
        border-right: none;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .tab-label:last-child {
        border-bottom: none;
    }
    
    .payment-item {
        padding: 0.75rem 1rem;
    }
    
    .results-header {
        padding: 0.75rem 1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const calculator = {
        init() {
            this.bindEvents();
            this.calculate();
        },

        bindEvents() {
            const downPaymentRange = document.getElementById('down-payment-range');
            const interestRateRange = document.getElementById('interest-rate-range');
            const loanTermInputs = document.querySelectorAll('input[name="loan_term"]');
            
            downPaymentRange?.addEventListener('input', () => this.calculate());
            interestRateRange?.addEventListener('input', () => this.calculate());
            loanTermInputs.forEach(input => input.addEventListener('change', () => this.calculate()));
            
            // Action buttons
            document.getElementById('get-pre-approved')?.addEventListener('click', this.handlePreApproval);
            document.getElementById('contact-lender')?.addEventListener('click', this.handleContactLender);
        },

        calculate() {
            const homePrice = parseFloat(document.getElementById('home-price')?.value || 0);
            const downPaymentPercent = parseFloat(document.getElementById('down-payment-range')?.value || 20);
            const interestRate = parseFloat(document.getElementById('interest-rate-range')?.value || 6.5);
            const loanTerm = parseInt(document.querySelector('input[name="loan_term"]:checked')?.value || 30);
            
            // Update displays
            document.getElementById('down-payment-display').textContent = downPaymentPercent + '%';
            document.getElementById('interest-rate-display').textContent = interestRate + '%';
            
            // Calculate loan amount
            const downPaymentAmount = (homePrice * downPaymentPercent) / 100;
            const loanAmount = homePrice - downPaymentAmount;
            
            document.getElementById('down-payment-amount').textContent = ' + this.formatNumber(downPaymentAmount);
            
            // Calculate monthly payment (P&I)
            const monthlyRate = (interestRate / 100) / 12;
            const numPayments = loanTerm * 12;
            
            let monthlyPI = 0;
            if (monthlyRate > 0) {
                monthlyPI = loanAmount * (monthlyRate * Math.pow(1 + monthlyRate, numPayments)) / 
                           (Math.pow(1 + monthlyRate, numPayments) - 1);
            } else {
                monthlyPI = loanAmount / numPayments;
            }
            
            // Calculate other components
            const propertyTax = parseFloat(<?php echo json_encode($property_tax ?? 0); ?>);
            const monthlyTax = propertyTax / 12;
            const monthlyInsurance = (homePrice * 0.0035) / 12; // Estimate 0.35% annually
            
            // PMI calculation (if down payment < 20%)
            let monthlyPMI = 0;
            if (downPaymentPercent < 20) {
                const pmiRate = parseFloat(<?php echo json_encode($pmi_rate ?? 0.5); ?>) / 100;
                monthlyPMI = (loanAmount * pmiRate) / 12;
                
                // Show PMI row
                const pmiRow = document.querySelector('.payment-item.pmi');
                if (pmiRow) {
                    pmiRow.style.display = 'flex';
                    document.getElementById('pmi-monthly').textContent = ' + this.formatNumber(monthlyPMI);
                }
            } else {
                // Hide PMI row
                const pmiRow = document.querySelector('.payment-item.pmi');
                if (pmiRow) pmiRow.style.display = 'none';
            }
            
            // HOA fees
            const hoaFees = parseFloat(<?php echo json_encode($hoa_fees ?? 0); ?>);
            
            // Update display
            document.getElementById('principal-interest').textContent = ' + this.formatNumber(monthlyPI);
            document.getElementById('property-tax-monthly').textContent = ' + this.formatNumber(monthlyTax);
            document.getElementById('insurance-monthly').textContent = ' + this.formatNumber(monthlyInsurance);
            
            // Calculate total
            const totalMonthly = monthlyPI + monthlyTax + monthlyInsurance + monthlyPMI + hoaFees;
            document.getElementById('total-payment').textContent = ' + this.formatNumber(totalMonthly);
        },

        formatNumber(num) {
            return Math.round(num).toLocaleString();
        },

        handlePreApproval() {
            // Track event
            if (typeof gtag !== 'undefined') {
                gtag('event', 'mortgage_pre_approval_click', {
                    event_category: 'mortgage_calculator',
                    event_label: 'pre_approval_button'
                });
            }
            
            // Open contact form or redirect to lender
            window.open('https://example-lender.com/preapproval', '_blank');
        },

        handleContactLender() {
            // Track event
            if (typeof gtag !== 'undefined') {
                gtag('event', 'contact_lender_click', {
                    event_category: 'mortgage_calculator',
                    event_label: 'contact_lender_button'
                });
            }
            
            // Show contact modal or form
            console.log('Contact lender clicked');
        }
    };

    calculator.init();
});
</script>