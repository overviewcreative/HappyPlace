<?php
/**
 * Template part for displaying mortgage calculator in sidebar
 * 
 * This is a fallback template part used when the Mortgage Calculator component is not available.
 * 
 * @package HappyPlace
 * @subpackage TemplateParts
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get listing data
$listing_id = isset($listing_id) ? $listing_id : get_the_ID();
$property_price = isset($listing_data['price']) ? $listing_data['price'] : '';

// Parse price if it's a string
if (!empty($property_price) && is_string($property_price)) {
    $property_price = preg_replace('/[^0-9]/', '', $property_price);
}
?>

<div class="mortgage-calculator-sidebar widget">
    <h3 class="widget-title">Mortgage Calculator</h3>
    
    <form class="mortgage-calculator-form">
        <div class="calculator-fields">
            <div class="field-group">
                <label for="home-price" class="field-label">Home Price</label>
                <input type="number" 
                       id="home-price" 
                       name="home_price" 
                       class="field-input"
                       value="<?php echo esc_attr($property_price); ?>"
                       placeholder="Enter home price"
                       min="0"
                       step="1000">
            </div>
            
            <div class="field-group">
                <label for="down-payment" class="field-label">Down Payment</label>
                <input type="number" 
                       id="down-payment" 
                       name="down_payment" 
                       class="field-input"
                       value="20"
                       placeholder="Percentage"
                       min="0"
                       max="100"
                       step="1">
                <span class="field-suffix">%</span>
            </div>
            
            <div class="field-group">
                <label for="interest-rate" class="field-label">Interest Rate</label>
                <input type="number" 
                       id="interest-rate" 
                       name="interest_rate" 
                       class="field-input"
                       value="6.5"
                       placeholder="Interest rate"
                       min="0"
                       max="30"
                       step="0.01">
                <span class="field-suffix">%</span>
            </div>
            
            <div class="field-group">
                <label for="loan-term" class="field-label">Loan Term</label>
                <select id="loan-term" name="loan_term" class="field-select">
                    <option value="15">15 years</option>
                    <option value="20">20 years</option>
                    <option value="25">25 years</option>
                    <option value="30" selected>30 years</option>
                </select>
            </div>
            
            <button type="button" class="btn btn-primary calculate-btn">
                Calculate Payment
            </button>
        </div>
        
        <div class="calculator-results" style="display: none;">
            <h4 class="results-title">Monthly Payment</h4>
            <div class="payment-breakdown">
                <div class="payment-item">
                    <span class="payment-label">Principal & Interest</span>
                    <span class="payment-value" id="principal-interest">$0</span>
                </div>
                <div class="payment-item">
                    <span class="payment-label">Property Tax (est.)</span>
                    <span class="payment-value" id="property-tax">$0</span>
                </div>
                <div class="payment-item">
                    <span class="payment-label">Insurance (est.)</span>
                    <span class="payment-value" id="insurance">$0</span>
                </div>
                <div class="payment-item total">
                    <span class="payment-label">Total Monthly Payment</span>
                    <span class="payment-value" id="total-payment">$0</span>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.mortgage-calculator-form');
    const calculateBtn = form.querySelector('.calculate-btn');
    const resultsDiv = form.querySelector('.calculator-results');
    
    calculateBtn.addEventListener('click', function() {
        const homePrice = parseFloat(form.querySelector('#home-price').value) || 0;
        const downPaymentPercent = parseFloat(form.querySelector('#down-payment').value) || 0;
        const interestRate = parseFloat(form.querySelector('#interest-rate').value) || 0;
        const loanTerm = parseInt(form.querySelector('#loan-term').value) || 30;
        
        if (homePrice <= 0) {
            alert('Please enter a valid home price');
            return;
        }
        
        // Calculate loan amount
        const downPayment = homePrice * (downPaymentPercent / 100);
        const loanAmount = homePrice - downPayment;
        
        // Calculate monthly payment
        const monthlyRate = (interestRate / 100) / 12;
        const numPayments = loanTerm * 12;
        
        let monthlyPayment = 0;
        if (monthlyRate > 0) {
            monthlyPayment = loanAmount * (monthlyRate * Math.pow(1 + monthlyRate, numPayments)) / (Math.pow(1 + monthlyRate, numPayments) - 1);
        } else {
            monthlyPayment = loanAmount / numPayments;
        }
        
        // Estimate property tax and insurance
        const propertyTax = (homePrice * 0.0125) / 12; // 1.25% annually
        const insurance = (homePrice * 0.0035) / 12; // 0.35% annually
        const totalPayment = monthlyPayment + propertyTax + insurance;
        
        // Update results
        form.querySelector('#principal-interest').textContent = '$' + monthlyPayment.toFixed(0);
        form.querySelector('#property-tax').textContent = '$' + propertyTax.toFixed(0);
        form.querySelector('#insurance').textContent = '$' + insurance.toFixed(0);
        form.querySelector('#total-payment').textContent = '$' + totalPayment.toFixed(0);
        
        resultsDiv.style.display = 'block';
    });
});
</script>
