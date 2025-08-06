/**
 * Enhanced Mortgage Calculator
 * Happy Place Real Estate Platform
 */

(function($) {
    'use strict';

    // Initialize calculator when DOM is ready
    $(document).ready(function() {
        initMortgageCalculator();
    });

    /**
     * Initialize the mortgage calculator with enhanced functionality
     */
    function initMortgageCalculator() {
        const $calculator = $('#mortgage-calculator-form');
        
        if ($calculator.length === 0) {
            return;
        }
        
        const $propertyPrice = $('#property-price');
        const $downPayment = $('#down-payment');
        const $downPaymentSlider = $('#down-payment-slider');
        const $downPaymentPercentage = $('#down-payment-percentage');
        const $interestRate = $('#interest-rate');
        const $interestRateSlider = $('#interest-rate-slider');
        const $loanTerm = $('#loan-term');
        const $loanTermSlider = $('#loan-term-slider');
        const $monthlyPayment = $('#monthly-payment');
        const $loanAmount = $('#loan-amount');
        const $totalInterest = $('#total-interest');
        const $totalPayment = $('#total-payment');
        const $amortizationTable = $('#amortization-table');
        const $amortizationToggle = $('#show-amortization');
        
        // Initialize values
        const propertyPrice = parseFloat($propertyPrice.val());
        let downPaymentPercent = 20; // Default 20%
        
        // Set initial values
        $downPaymentSlider.attr('max', propertyPrice);
        $downPayment.val(Math.round(propertyPrice * (downPaymentPercent / 100)));
        $downPaymentSlider.val($downPayment.val());
        $downPaymentPercentage.text(downPaymentPercent + '%');
        
        // Initial calculation
        calculateMortgage();
        
        // Bind events
        $downPayment.on('input', function() {
            const value = parseFloat($(this).val()) || 0;
            const percent = Math.round((value / propertyPrice) * 100);
            
            $downPaymentSlider.val(value);
            $downPaymentPercentage.text(percent + '%');
            calculateMortgage();
        });
        
        $downPaymentSlider.on('input', function() {
            const value = parseFloat($(this).val()) || 0;
            const percent = Math.round((value / propertyPrice) * 100);
            
            $downPayment.val(value);
            $downPaymentPercentage.text(percent + '%');
            calculateMortgage();
        });
        
        $interestRate.on('input', function() {
            $interestRateSlider.val($(this).val());
            calculateMortgage();
        });
        
        $interestRateSlider.on('input', function() {
            $interestRate.val($(this).val());
            calculateMortgage();
        });
        
        $loanTerm.on('input', function() {
            $loanTermSlider.val($(this).val());
            calculateMortgage();
        });
        
        $loanTermSlider.on('input', function() {
            $loanTerm.val($(this).val());
            calculateMortgage();
        });
        
        // Toggle amortization table
        $amortizationToggle.on('click', function(e) {
            e.preventDefault();
            
            const $table = $('#amortization-container');
            
            if ($table.is(':visible')) {
                $table.slideUp();
                $(this).text('Show Amortization Schedule');
            } else {
                // Generate the amortization table if it doesn't exist
                if ($amortizationTable.find('tbody').children().length === 0) {
                    generateAmortizationTable();
                }
                
                $table.slideDown();
                $(this).text('Hide Amortization Schedule');
            }
        });

        /**
         * Calculate mortgage payment and update UI
         */
        function calculateMortgage() {
            const downPayment = parseFloat($downPayment.val()) || 0;
            const interestRate = parseFloat($interestRate.val()) || 0;
            const loanTerm = parseInt($loanTerm.val()) || 30;
            
            const loanAmount = propertyPrice - downPayment;
            const monthlyInterest = interestRate / 100 / 12;
            const totalPayments = loanTerm * 12;
            
            let monthlyPayment = 0;
            let totalInterestPaid = 0;
            
            if (loanAmount > 0 && interestRate > 0) {
                monthlyPayment = loanAmount * 
                    (monthlyInterest * Math.pow(1 + monthlyInterest, totalPayments)) / 
                    (Math.pow(1 + monthlyInterest, totalPayments) - 1);
                
                totalInterestPaid = (monthlyPayment * totalPayments) - loanAmount;
            } else if (loanAmount > 0) {
                // Simple division if interest rate is 0
                monthlyPayment = loanAmount / totalPayments;
                totalInterestPaid = 0;
            }
            
            // Update UI
            $monthlyPayment.text('$' + formatNumber(monthlyPayment));
            $loanAmount.text('$' + formatNumber(loanAmount));
            $totalInterest.text('$' + formatNumber(totalInterestPaid));
            $totalPayment.text('$' + formatNumber(monthlyPayment * totalPayments));
            
            // Add animation effect
            $monthlyPayment.addClass('highlight');
            setTimeout(function() {
                $monthlyPayment.removeClass('highlight');
            }, 700);
            
            // Store the values for the amortization table
            $calculator.data('calculation', {
                loanAmount: loanAmount,
                monthlyInterest: monthlyInterest,
                monthlyPayment: monthlyPayment,
                totalPayments: totalPayments
            });
        }
        
        /**
         * Generate amortization table
         */
        function generateAmortizationTable() {
            const calculation = $calculator.data('calculation');
            
            if (!calculation) return;
            
            const { loanAmount, monthlyInterest, monthlyPayment, totalPayments } = calculation;
            
            // Clear existing rows
            $amortizationTable.find('tbody').empty();
            
            let remainingBalance = loanAmount;
            let totalInterestPaid = 0;
            
            // Generate rows for the first year, then every 5 years, then the last year
            for (let i = 1; i <= totalPayments; i++) {
                const interestPayment = remainingBalance * monthlyInterest;
                const principalPayment = monthlyPayment - interestPayment;
                
                remainingBalance -= principalPayment;
                totalInterestPaid += interestPayment;
                
                // Show monthly detail for first year
                if (i <= 12 || i % 60 === 0 || i === totalPayments || remainingBalance <= 0) {
                    const year = Math.ceil(i / 12);
                    const month = ((i - 1) % 12) + 1;
                    
                    const row = `
                        <tr>
                            <td>${year}y ${month}m</td>
                            <td>$${formatNumber(monthlyPayment)}</td>
                            <td>$${formatNumber(principalPayment)}</td>
                            <td>$${formatNumber(interestPayment)}</td>
                            <td>$${formatNumber(Math.max(0, remainingBalance))}</td>
                        </tr>
                    `;
                    
                    $amortizationTable.find('tbody').append(row);
                }
                
                // Stop if balance is paid off
                if (remainingBalance <= 0) {
                    break;
                }
            }
        }
        
        /**
         * Format number with commas and 2 decimal places
         */
        function formatNumber(number) {
            return number.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
    }
    
})(jQuery);