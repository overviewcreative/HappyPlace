<?php

namespace HappyPlace\Components\Tools;

use HappyPlace\Components\Base_Component;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Mortgage Calculator Component
 * 
 * @package HappyPlace\Components\Tools
 */
class Mortgage_Calculator extends Base_Component {
    
    /**
     * Get component name
     */
    protected function get_component_name() {
        return 'mortgage-calculator';
    }
    
    /**
     * Get default properties
     */
    protected function get_defaults() {
        return [
            'listing_id' => 0,
            'financial_data' => [],
            'variant' => 'full', // full, sidebar, widget
            'show_taxes' => true,
            'show_insurance' => true,
            'show_pmi' => true,
            'currency_symbol' => '$',
            'class' => 'hph-mortgage-calculator'
        ];
    }
    
    /**
     * Validate properties
     */
    protected function validate_props() {
        if (empty($this->props['listing_id']) && empty($this->props['financial_data']['price'])) {
            $this->validation_errors[] = 'Either listing_id or financial_data with price is required';
        }
    }
    
    /**
     * Initialize component
     */
    protected function init() {
        // Get listing data if not provided
        if (!empty($this->props['listing_id']) && empty($this->props['financial_data'])) {
            $this->props['financial_data'] = $this->get_listing_financial_data();
        }
        
        // Set up default financial values
        $defaults = [
            'price' => 0,
            'down_payment_percent' => 20,
            'interest_rate' => 6.5,
            'loan_term_years' => 30,
            'property_tax_rate' => 1.2,
            'insurance_annual' => 1200,
            'hoa_monthly' => 0,
            'pmi_rate' => 0.5
        ];
        
        $this->props['financial_data'] = wp_parse_args($this->props['financial_data'], $defaults);
    }
    
    /**
     * Get listing financial data
     */
    private function get_listing_financial_data() {
        $listing_id = $this->props['listing_id'];
        
        if (function_exists('hph_bridge_get_financial_data')) {
            return hph_bridge_get_financial_data($listing_id);
        }
        
        // Fallback - get directly from post meta
        return [
            'price' => get_post_meta($listing_id, '_listing_price', true) ?: 0,
            'property_tax_rate' => get_post_meta($listing_id, '_listing_tax_rate', true) ?: 1.2,
            'hoa_monthly' => get_post_meta($listing_id, '_listing_hoa_fee', true) ?: 0
        ];
    }
    
    /**
     * Render component
     */
    protected function render() {
        $financial = $this->props['financial_data'];
        $variant = $this->props['variant'];
        $class = $this->props['class'];
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($class); ?> calculator-variant-<?php echo esc_attr($variant); ?>" 
             data-component="mortgage-calculator" 
             data-listing-id="<?php echo esc_attr($this->props['listing_id']); ?>">
            
            <?php if ($variant === 'full') : ?>
                <div class="calculator-header">
                    <h3 class="calculator-title">Mortgage Calculator</h3>
                    <p class="calculator-description">Calculate your estimated monthly payment</p>
                </div>
            <?php elseif ($variant === 'sidebar') : ?>
                <h4 class="calculator-title">Mortgage Calculator</h4>
            <?php endif; ?>
            
            <form class="calculator-form" onsubmit="return false;">
                
                <!-- Primary Inputs -->
                <div class="calculator-section primary-inputs">
                    <div class="input-group">
                        <label for="calc-home-price" class="input-label">Home Price</label>
                        <div class="input-wrapper">
                            <span class="input-prefix"><?php echo esc_html($this->props['currency_symbol']); ?></span>
                            <input type="number" 
                                   id="calc-home-price" 
                                   name="home_price" 
                                   class="calculator-input"
                                   value="<?php echo esc_attr($financial['price']); ?>"
                                   placeholder="Enter home price"
                                   min="0"
                                   step="1000"
                                   onchange="calculatePayment()">
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label for="calc-down-payment" class="input-label">Down Payment</label>
                        <div class="input-wrapper input-wrapper--percentage">
                            <input type="number" 
                                   id="calc-down-payment" 
                                   name="down_payment" 
                                   class="calculator-input"
                                   value="<?php echo esc_attr($financial['down_payment_percent']); ?>"
                                   placeholder="20"
                                   min="0"
                                   max="100"
                                   step="1"
                                   onchange="calculatePayment()">
                            <span class="input-suffix">%</span>
                        </div>
                        <div class="input-help">
                            <span id="down-payment-amount">$<?php echo number_format($financial['price'] * ($financial['down_payment_percent'] / 100)); ?></span>
                        </div>
                    </div>
                    
                    <div class="input-row">
                        <div class="input-group">
                            <label for="calc-interest-rate" class="input-label">Interest Rate</label>
                            <div class="input-wrapper">
                                <input type="number" 
                                       id="calc-interest-rate" 
                                       name="interest_rate" 
                                       class="calculator-input"
                                       value="<?php echo esc_attr($financial['interest_rate']); ?>"
                                       placeholder="6.5"
                                       min="0"
                                       max="30"
                                       step="0.01"
                                       onchange="calculatePayment()">
                                <span class="input-suffix">%</span>
                            </div>
                        </div>
                        
                        <div class="input-group">
                            <label for="calc-loan-term" class="input-label">Loan Term</label>
                            <div class="input-wrapper">
                                <input type="number" 
                                       id="calc-loan-term" 
                                       name="loan_term" 
                                       class="calculator-input"
                                       value="<?php echo esc_attr($financial['loan_term_years']); ?>"
                                       placeholder="30"
                                       min="1"
                                       max="50"
                                       step="1"
                                       onchange="calculatePayment()">
                                <span class="input-suffix">years</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($variant === 'full' || $this->props['show_taxes']) : ?>
                <!-- Advanced Inputs -->
                <div class="calculator-section advanced-inputs">
                    <?php if ($variant === 'full') : ?>
                        <button type="button" class="advanced-toggle" onclick="toggleAdvancedInputs()">
                            <span class="toggle-text">Show Advanced Options</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    <?php endif; ?>
                    
                    <div class="advanced-fields" <?php echo $variant !== 'full' ? '' : 'style="display: none;"'; ?>>
                        
                        <?php if ($this->props['show_taxes']) : ?>
                        <div class="input-group">
                            <label for="calc-property-tax" class="input-label">Property Tax (Annual)</label>
                            <div class="input-wrapper">
                                <span class="input-prefix"><?php echo esc_html($this->props['currency_symbol']); ?></span>
                                <input type="number" 
                                       id="calc-property-tax" 
                                       name="property_tax" 
                                       class="calculator-input"
                                       value="<?php echo esc_attr($financial['price'] * ($financial['property_tax_rate'] / 100)); ?>"
                                       placeholder="Enter annual tax"
                                       min="0"
                                       step="100"
                                       onchange="calculatePayment()">
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($this->props['show_insurance']) : ?>
                        <div class="input-group">
                            <label for="calc-insurance" class="input-label">Home Insurance (Annual)</label>
                            <div class="input-wrapper">
                                <span class="input-prefix"><?php echo esc_html($this->props['currency_symbol']); ?></span>
                                <input type="number" 
                                       id="calc-insurance" 
                                       name="insurance" 
                                       class="calculator-input"
                                       value="<?php echo esc_attr($financial['insurance_annual']); ?>"
                                       placeholder="1200"
                                       min="0"
                                       step="100"
                                       onchange="calculatePayment()">
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="input-group">
                            <label for="calc-hoa" class="input-label">HOA Fee (Monthly)</label>
                            <div class="input-wrapper">
                                <span class="input-prefix"><?php echo esc_html($this->props['currency_symbol']); ?></span>
                                <input type="number" 
                                       id="calc-hoa" 
                                       name="hoa" 
                                       class="calculator-input"
                                       value="<?php echo esc_attr($financial['hoa_monthly']); ?>"
                                       placeholder="0"
                                       min="0"
                                       step="10"
                                       onchange="calculatePayment()">
                            </div>
                        </div>
                        
                        <?php if ($this->props['show_pmi']) : ?>
                        <div class="input-group">
                            <label for="calc-pmi" class="input-label">PMI (If down payment &lt; 20%)</label>
                            <div class="input-wrapper">
                                <input type="number" 
                                       id="calc-pmi-rate" 
                                       name="pmi_rate" 
                                       class="calculator-input"
                                       value="<?php echo esc_attr($financial['pmi_rate']); ?>"
                                       placeholder="0.5"
                                       min="0"
                                       max="5"
                                       step="0.1"
                                       onchange="calculatePayment()">
                                <span class="input-suffix">%</span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Results -->
                <div class="calculator-results">
                    <div class="result-primary">
                        <div class="result-label">Estimated Monthly Payment</div>
                        <div class="result-value" id="monthly-payment">$0</div>
                    </div>
                    
                    <?php if ($variant === 'full') : ?>
                    <div class="result-breakdown">
                        <div class="breakdown-item">
                            <span class="breakdown-label">Principal & Interest</span>
                            <span class="breakdown-value" id="principal-interest">$0</span>
                        </div>
                        <div class="breakdown-item">
                            <span class="breakdown-label">Property Tax</span>
                            <span class="breakdown-value" id="property-tax-monthly">$0</span>
                        </div>
                        <div class="breakdown-item">
                            <span class="breakdown-label">Home Insurance</span>
                            <span class="breakdown-value" id="insurance-monthly">$0</span>
                        </div>
                        <div class="breakdown-item">
                            <span class="breakdown-label">HOA Fee</span>
                            <span class="breakdown-value" id="hoa-monthly">$0</span>
                        </div>
                        <div class="breakdown-item pmi-item" style="display: none;">
                            <span class="breakdown-label">PMI</span>
                            <span class="breakdown-value" id="pmi-monthly">$0</span>
                        </div>
                    </div>
                    
                    <div class="result-summary">
                        <div class="summary-item">
                            <span class="summary-label">Loan Amount</span>
                            <span class="summary-value" id="loan-amount">$0</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Total Interest</span>
                            <span class="summary-value" id="total-interest">$0</span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($variant === 'full') : ?>
                <div class="calculator-actions">
                    <button type="button" class="action-btn action-btn--secondary" onclick="resetCalculator()">
                        Reset
                    </button>
                    <button type="button" class="action-btn action-btn--primary" onclick="saveCalculation()">
                        Save Calculation
                    </button>
                </div>
                <?php endif; ?>
                
            </form>
        </div>
        
        <script>
        // Initialize calculator
        document.addEventListener('DOMContentLoaded', function() {
            calculatePayment();
        });
        
        function calculatePayment() {
            const homePrice = parseFloat(document.getElementById('calc-home-price').value) || 0;
            const downPaymentPercent = parseFloat(document.getElementById('calc-down-payment').value) || 0;
            const interestRate = parseFloat(document.getElementById('calc-interest-rate').value) || 0;
            const loanTermYears = parseFloat(document.getElementById('calc-loan-term').value) || 30;
            
            const downPaymentAmount = homePrice * (downPaymentPercent / 100);
            const loanAmount = homePrice - downPaymentAmount;
            
            // Update down payment amount display
            document.getElementById('down-payment-amount').textContent = '$' + downPaymentAmount.toLocaleString();
            
            // Calculate monthly principal and interest
            const monthlyRate = (interestRate / 100) / 12;
            const numPayments = loanTermYears * 12;
            
            let monthlyPI = 0;
            if (monthlyRate > 0 && numPayments > 0) {
                monthlyPI = loanAmount * (monthlyRate * Math.pow(1 + monthlyRate, numPayments)) / 
                           (Math.pow(1 + monthlyRate, numPayments) - 1);
            } else if (loanAmount > 0) {
                monthlyPI = loanAmount / numPayments;
            }
            
            // Calculate other monthly costs
            const propertyTax = parseFloat(document.getElementById('calc-property-tax')?.value) || 0;
            const insurance = parseFloat(document.getElementById('calc-insurance')?.value) || 0;
            const hoa = parseFloat(document.getElementById('calc-hoa')?.value) || 0;
            const pmiRate = parseFloat(document.getElementById('calc-pmi-rate')?.value) || 0;
            
            const monthlyPropertyTax = propertyTax / 12;
            const monthlyInsurance = insurance / 12;
            const monthlyHOA = hoa;
            
            // PMI calculation (if down payment < 20%)
            let monthlyPMI = 0;
            if (downPaymentPercent < 20 && pmiRate > 0) {
                monthlyPMI = (loanAmount * (pmiRate / 100)) / 12;
            }
            
            const totalMonthly = monthlyPI + monthlyPropertyTax + monthlyInsurance + monthlyHOA + monthlyPMI;
            
            // Update displays
            document.getElementById('monthly-payment').textContent = '$' + Math.round(totalMonthly).toLocaleString();
            
            // Update breakdown if exists (full version)
            const piElement = document.getElementById('principal-interest');
            if (piElement) {
                piElement.textContent = '$' + Math.round(monthlyPI).toLocaleString();
                document.getElementById('property-tax-monthly').textContent = '$' + Math.round(monthlyPropertyTax).toLocaleString();
                document.getElementById('insurance-monthly').textContent = '$' + Math.round(monthlyInsurance).toLocaleString();
                document.getElementById('hoa-monthly').textContent = '$' + Math.round(monthlyHOA).toLocaleString();
                document.getElementById('pmi-monthly').textContent = '$' + Math.round(monthlyPMI).toLocaleString();
                document.getElementById('loan-amount').textContent = '$' + Math.round(loanAmount).toLocaleString();
                document.getElementById('total-interest').textContent = '$' + Math.round((monthlyPI * numPayments) - loanAmount).toLocaleString();
                
                // Show/hide PMI row
                const pmiItem = document.querySelector('.pmi-item');
                if (pmiItem) {
                    pmiItem.style.display = monthlyPMI > 0 ? 'flex' : 'none';
                }
            }
        }
        
        function toggleAdvancedInputs() {
            const fields = document.querySelector('.advanced-fields');
            const toggle = document.querySelector('.advanced-toggle');
            const icon = toggle.querySelector('i');
            const text = toggle.querySelector('.toggle-text');
            
            if (fields.style.display === 'none') {
                fields.style.display = 'block';
                icon.className = 'fas fa-chevron-up';
                text.textContent = 'Hide Advanced Options';
            } else {
                fields.style.display = 'none';
                icon.className = 'fas fa-chevron-down';
                text.textContent = 'Show Advanced Options';
            }
        }
        
        function resetCalculator() {
            document.querySelector('.calculator-form').reset();
            setTimeout(calculatePayment, 100);
        }
        
        function saveCalculation() {
            // Implementation for saving calculation
            alert('Save functionality would be implemented here');
        }
        </script>
        <?php
        return ob_get_clean();
    }
}
