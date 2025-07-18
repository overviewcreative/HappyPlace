<?php
/**
 * Mortgage calculator component
 */

$listing_id = get_the_ID();
$price = get_post_meta($listing_id, 'listing_price', true);
$down_payment = $price ? ($price * 0.2) : 0;
?>

<div class="sidebar-widget mortgage-calculator">
    <h3 class="widget-title">Mortgage Calculator</h3>
    
    <form class="calculator-form" id="mortgage-calculator">
        <div class="form-group">
            <label for="home-price">Home Price</label>
            <input type="text" 
                   id="home-price" 
                   name="home_price" 
                   value="<?php echo $price ? '$' . number_format($price) : ''; ?>" 
                   readonly>
        </div>
        
        <div class="form-group">
            <label for="down-payment">Down Payment</label>
            <input type="text" 
                   id="down-payment" 
                   name="down_payment" 
                   value="<?php echo $down_payment ? '$' . number_format($down_payment) : ''; ?>" 
                   placeholder="20%">
        </div>
        
        <div class="form-group">
            <label for="interest-rate">Interest Rate</label>
            <input type="text" 
                   id="interest-rate" 
                   name="interest_rate" 
                   value="6.5%" 
                   placeholder="6.5%">
        </div>
        
        <div class="form-group">
            <label for="loan-term">Loan Term</label>
            <select id="loan-term" name="loan_term">
                <option value="30">30 years</option>
                <option value="15">15 years</option>
            </select>
        </div>
        
        <button type="button" class="calculate-btn" id="calculate-payment">
            Calculate Payment
        </button>
        
        <div class="monthly-payment" id="payment-result" style="display: none;">
            <div class="payment-label">Estimated Monthly Payment</div>
            <div class="payment-amount" id="payment-amount">$0</div>
        </div>
    </form>
</div>
