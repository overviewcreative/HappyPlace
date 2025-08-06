<!-- Enhanced Mortgage Calculator -->
<div class="hph-card hph-mortgage-calculator">
    <div class="hph-card-header">
        <h3><i class="fas fa-calculator"></i> Mortgage Calculator</h3>
    </div>
    
    <form class="hph-mortgage-calculator-form" id="mortgage-calculator-form">
        <input type="hidden" id="property-price" value="<?php echo esc_attr($price); ?>">
        
        <div class="hph-form-group">
            <label for="down-payment">Down Payment</label>
            <div class="hph-input-group">
                <span class="hph-input-group-text">$</span>
                <input type="number" id="down-payment" min="0" max="<?php echo esc_attr($price); ?>">
            </div>
            <div class="hph-range-slider-container">
                <input type="range" id="down-payment-slider" min="0" max="<?php echo esc_attr($price); ?>" step="1000">
                <span class="hph-range-value" id="down-payment-percentage">20%</span>
            </div>
        </div>
        
        <div class="hph-form-group">
            <label for="interest-rate">Interest Rate</label>
            <div class="hph-input-group">
                <input type="number" id="interest-rate" value="5.5" min="0" max="15" step="0.1">
                <span class="hph-input-group-text">%</span>
            </div>
            <div class="hph-range-slider-container">
                <input type="range" id="interest-rate-slider" min="0" max="15" value="5.5" step="0.1">
            </div>
        </div>
        
        <div class="hph-form-group">
            <label for="loan-term">Loan Term</label>
            <div class="hph-input-group">
                <input type="number" id="loan-term" value="30" min="5" max="30" step="1">
                <span class="hph-input-group-text">years</span>
            </div>
            <div class="hph-range-slider-container">
                <input type="range" id="loan-term-slider" min="5" max="30" value="30" step="1">
            </div>
        </div>
        
        <div class="hph-mortgage-result">
            <div class="hph-mortgage-payment">
                <span>Monthly Payment:</span>
                <span class="hph-mortgage-payment-value" id="monthly-payment">$0.00</span>
            </div>
        </div>
        
        <div class="hph-mortgage-details">
            <div class="hph-mortgage-detail">
                <span class="hph-mortgage-detail-label">Loan Amount:</span>
                <span class="hph-mortgage-detail-value" id="loan-amount">$0.00</span>
            </div>
            <div class="hph-mortgage-detail">
                <span class="hph-mortgage-detail-label">Total Interest:</span>
                <span class="hph-mortgage-detail-value" id="total-interest">$0.00</span>
            </div>
            <div class="hph-mortgage-detail">
                <span class="hph-mortgage-detail-label">Total Payment:</span>
                <span class="hph-mortgage-detail-value" id="total-payment">$0.00</span>
            </div>
        </div>
        
        <div class="hph-mortgage-actions">
            <a href="#" id="show-amortization" class="hph-link">Show Amortization Schedule</a>
            
            <div id="amortization-container" style="display: none;">
                <div class="hph-amortization-table-container">
                    <table id="amortization-table" class="hph-table">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th>Payment</th>
                                <th>Principal</th>
                                <th>Interest</th>
                                <th>Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Generated dynamically by JavaScript -->
                        </tbody>
                    </table>
                </div>
                <div class="hph-amortization-note">
                    <p><small>* Showing monthly detail for first year, then every 5 years, then final payment.</small></p>
                </div>
            </div>
        </div>
        
        <div class="hph-mortgage-disclaimer">
            <p><small>This calculator provides estimates only and should not be considered financial advice. Consult a mortgage professional for accurate rates and terms.</small></p>
        </div>
    </form>
</div>

<!-- CSS for Mortgage Calculator Enhancements -->
<style>
    .hph-range-slider-container {
        position: relative;
        padding: var(--hph-spacing-3) 0 var(--hph-spacing-3) 0;
    }
    
    .hph-range-value {
        position: absolute;
        right: 0;
        top: 0;
        font-size: var(--hph-font-size-xs);
        color: var(--hph-color-primary-600);
        font-weight: var(--hph-font-semibold);
    }
    
    .hph-mortgage-details {
        margin-top: var(--hph-spacing-6);
        border-top: 1px solid var(--hph-color-gray-200);
        padding-top: var(--hph-spacing-4);
    }
    
    .hph-mortgage-detail {
        display: flex;
        justify-content: space-between;
        margin-bottom: var(--hph-spacing-2);
        font-size: var(--hph-font-size-sm);
    }
    
    .hph-mortgage-detail-label {
        color: var(--hph-color-gray-600);
    }
    
    .hph-mortgage-detail-value {
        font-weight: var(--hph-font-medium);
        color: var(--hph-color-gray-900);
    }
    
    .hph-mortgage-actions {
        margin-top: var(--hph-spacing-6);
        text-align: center;
    }
    
    .hph-link {
        color: var(--hph-color-primary-600);
        text-decoration: none;
        font-size: var(--hph-font-size-sm);
        font-weight: var(--hph-font-medium);
        transition: color var(--hph-transition-fast);
    }
    
    .hph-link:hover {
        color: var(--hph-color-primary-700);
        text-decoration: underline;
    }
    
    .hph-amortization-table-container {
        margin-top: var(--hph-spacing-4);
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid var(--hph-color-gray-200);
        border-radius: var(--hph-radius-md);
    }
    
    .hph-table {
        width: 100%;
        border-collapse: collapse;
        font-size: var(--hph-font-size-xs);
    }
    
    .hph-table th, 
    .hph-table td {
        padding: var(--hph-spacing-2) var(--hph-spacing-3);
        text-align: right;
    }
    
    .hph-table th {
        background-color: var(--hph-color-gray-100);
        font-weight: var(--hph-font-semibold);
        color: var(--hph-color-gray-700);
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .hph-table th:first-child,
    .hph-table td:first-child {
        text-align: left;
    }
    
    .hph-table tr:nth-child(even) {
        background-color: var(--hph-color-gray-50);
    }
    
    .hph-table tr:hover {
        background-color: var(--hph-color-primary-50);
    }
    
    .hph-amortization-note {
        margin-top: var(--hph-spacing-2);
        text-align: left;
        color: var(--hph-color-gray-500);
    }
    
    .hph-mortgage-disclaimer {
        margin-top: var(--hph-spacing-6);
        text-align: center;
        color: var(--hph-color-gray-500);
    }
    
    @keyframes highlight {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .hph-mortgage-payment-value.highlight {
        animation: highlight 0.7s ease-in-out;
    }
    
    @media (prefers-color-scheme: dark) {
        .hph-mortgage-details {
            border-top-color: var(--hph-color-gray-700);
        }
        
        .hph-mortgage-detail-label {
            color: var(--hph-color-gray-400);
        }
        
        .hph-mortgage-detail-value {
            color: var(--hph-color-gray-200);
        }
        
        .hph-amortization-table-container {
            border-color: var(--hph-color-gray-700);
        }
        
        .hph-table th {
            background-color: var(--hph-color-gray-800);
            color: var(--hph-color-gray-300);
        }
        
        .hph-table tr:nth-child(even) {
            background-color: var(--hph-color-gray-900);
        }
        
        .hph-table tr:hover {
            background-color: var(--hph-color-primary-900);
        }
    }
</style>