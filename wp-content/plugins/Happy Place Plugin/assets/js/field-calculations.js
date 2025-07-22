/**
 * Real-time ACF Field Calculations
 */

(function($) {
    'use strict';

    var HPHFieldCalculations = {
        
        init: function() {
            this.bindEvents();
            this.initCalculations();
        },

        bindEvents: function() {
            // Bind to ACF field changes
            $(document).on('change', '.acf-field-number input', this.handleFieldChange);
            $(document).on('keyup', '.acf-field-number input', this.debounce(this.handleFieldChange, 500));
            
            // Specific calculation triggers
            $(document).on('change', '[data-name="price"], [data-name="square_footage"]', this.calculatePricePerSqft);
            $(document).on('change', '[data-name="price"], [data-name="estimated_down_payment"], [data-name="estimated_interest_rate"]', this.calculateMonthlyPayment);
            $(document).on('change', '[data-name="hoa_monthly"], [data-name="hoa_quarterly"], [data-name="hoa_annual"]', this.calculateHOATotal);
            
            // Investment calculations
            $(document).on('change', '[data-name="estimated_monthly_rent"], [data-name="price"]', this.calculateInvestmentMetrics);
        },

        initCalculations: function() {
            // Run initial calculations on page load
            this.calculatePricePerSqft();
            this.calculateMonthlyPayment();
            this.calculateHOATotal();
            this.calculateInvestmentMetrics();
        },

        handleFieldChange: function(e) {
            var $field = $(e.target);
            var fieldName = $field.closest('.acf-field').data('name');
            
            // Trigger specific calculations based on field
            switch(fieldName) {
                case 'price':
                case 'square_footage':
                    HPHFieldCalculations.calculatePricePerSqft();
                    HPHFieldCalculations.calculateInvestmentMetrics();
                    break;
                case 'bedrooms':
                case 'bathrooms':
                    HPHFieldCalculations.calculateRoomRatios();
                    break;
                case 'living_square_footage':
                case 'garage_square_footage':
                case 'basement_square_footage':
                    HPHFieldCalculations.calculateTotalSquareFootage();
                    break;
            }
        },

        calculatePricePerSqft: function() {
            var price = HPHFieldCalculations.getFieldValue('price');
            var sqft = HPHFieldCalculations.getFieldValue('square_footage');
            
            if (price && sqft && sqft > 0) {
                var pricePerSqft = Math.round((price / sqft) * 100) / 100;
                HPHFieldCalculations.setFieldValue('price_per_sqft', pricePerSqft);
                HPHFieldCalculations.updateCalculatedField('price_per_sqft', '$' + pricePerSqft.toFixed(2) + ' per sq ft');
            }
        },

        calculateMonthlyPayment: function() {
            var price = HPHFieldCalculations.getFieldValue('price');
            var downPaymentPercent = HPHFieldCalculations.getFieldValue('estimated_down_payment') || 20;
            var interestRate = HPHFieldCalculations.getFieldValue('estimated_interest_rate') || 7.0;
            var loanTermYears = HPHFieldCalculations.getFieldValue('loan_term_years') || 30;
            
            if (!price) return;
            
            var downPayment = price * (downPaymentPercent / 100);
            var loanAmount = price - downPayment;
            var monthlyRate = (interestRate / 100) / 12;
            var numPayments = loanTermYears * 12;
            
            var monthlyPayment;
            if (monthlyRate > 0) {
                monthlyPayment = loanAmount * 
                    (monthlyRate * Math.pow(1 + monthlyRate, numPayments)) / 
                    (Math.pow(1 + monthlyRate, numPayments) - 1);
            } else {
                monthlyPayment = loanAmount / numPayments;
            }
            
            HPHFieldCalculations.setFieldValue('estimated_monthly_payment', Math.round(monthlyPayment * 100) / 100);
            HPHFieldCalculations.setFieldValue('estimated_down_payment_amount', downPayment);
            HPHFieldCalculations.setFieldValue('estimated_loan_amount', loanAmount);
            
            // Update display
            HPHFieldCalculations.updateCalculatedField('estimated_monthly_payment', '$' + monthlyPayment.toFixed(2) + '/mo');
            HPHFieldCalculations.updateCalculatedField('estimated_down_payment_amount', '$' + downPayment.toLocaleString());
            
            // Calculate total monthly cost
            HPHFieldCalculations.calculateTotalMonthlyCost();
        },

        calculateHOATotal: function() {
            var monthlyHOA = HPHFieldCalculations.getFieldValue('hoa_monthly') || 0;
            var quarterlyHOA = HPHFieldCalculations.getFieldValue('hoa_quarterly') || 0;
            var annualHOA = HPHFieldCalculations.getFieldValue('hoa_annual') || 0;
            
            var totalMonthlyHOA = monthlyHOA + (quarterlyHOA / 3) + (annualHOA / 12);
            var totalAnnualHOA = totalMonthlyHOA * 12;
            
            HPHFieldCalculations.setFieldValue('total_monthly_hoa', Math.round(totalMonthlyHOA * 100) / 100);
            HPHFieldCalculations.setFieldValue('total_annual_hoa', Math.round(totalAnnualHOA * 100) / 100);
            
            HPHFieldCalculations.updateCalculatedField('total_monthly_hoa', '$' + totalMonthlyHOA.toFixed(2) + '/mo');
            
            // Recalculate total monthly cost
            HPHFieldCalculations.calculateTotalMonthlyCost();
        },

        calculateTotalMonthlyCost: function() {
            var payment = HPHFieldCalculations.getFieldValue('estimated_monthly_payment') || 0;
            var taxes = HPHFieldCalculations.getFieldValue('estimated_monthly_taxes') || 0;
            var insurance = HPHFieldCalculations.getFieldValue('estimated_monthly_insurance') || 0;
            var hoa = HPHFieldCalculations.getFieldValue('total_monthly_hoa') || 0;
            var pmi = HPHFieldCalculations.getFieldValue('estimated_monthly_pmi') || 0;
            
            var total = payment + taxes + insurance + hoa + pmi;
            var piti = payment + taxes + insurance;
            
            HPHFieldCalculations.setFieldValue('total_monthly_cost', Math.round(total * 100) / 100);
            HPHFieldCalculations.setFieldValue('piti', Math.round(piti * 100) / 100);
            
            HPHFieldCalculations.updateCalculatedField('total_monthly_cost', '$' + total.toFixed(2) + '/mo');
            HPHFieldCalculations.updateCalculatedField('piti', '$' + piti.toFixed(2) + '/mo (PITI)');
        },

        calculateInvestmentMetrics: function() {
            var price = HPHFieldCalculations.getFieldValue('price');
            var monthlyRent = HPHFieldCalculations.getFieldValue('estimated_monthly_rent');
            
            if (!price || !monthlyRent) return;
            
            var annualRent = monthlyRent * 12;
            
            // Gross Rent Multiplier
            var grm = price / annualRent;
            HPHFieldCalculations.setFieldValue('gross_rent_multiplier', Math.round(grm * 10) / 10);
            HPHFieldCalculations.updateCalculatedField('gross_rent_multiplier', grm.toFixed(1) + 'x');
            
            // Monthly rent ratio (1% rule)
            var rentRatio = (monthlyRent / price) * 100;
            HPHFieldCalculations.setFieldValue('monthly_rent_ratio', Math.round(rentRatio * 1000) / 1000);
            
            var meetsOnePercent = rentRatio >= 1.0;
            HPHFieldCalculations.setFieldValue('meets_one_percent_rule', meetsOnePercent);
            
            var rentRatioText = rentRatio.toFixed(3) + '%';
            if (meetsOnePercent) {
                rentRatioText += ' ✓ (Meets 1% Rule)';
            }
            HPHFieldCalculations.updateCalculatedField('monthly_rent_ratio', rentRatioText);
            
            // Cash flow calculation
            var totalMonthlyCost = HPHFieldCalculations.getFieldValue('total_monthly_cost') || 0;
            if (totalMonthlyCost > 0) {
                var monthlyCashFlow = monthlyRent - totalMonthlyCost;
                var annualCashFlow = monthlyCashFlow * 12;
                
                HPHFieldCalculations.setFieldValue('monthly_cash_flow', Math.round(monthlyCashFlow * 100) / 100);
                HPHFieldCalculations.setFieldValue('annual_cash_flow', Math.round(annualCashFlow * 100) / 100);
                
                var cashFlowColor = monthlyCashFlow >= 0 ? 'green' : 'red';
                HPHFieldCalculations.updateCalculatedField('monthly_cash_flow', 
                    '<span style="color: ' + cashFlowColor + '">$' + monthlyCashFlow.toFixed(2) + '/mo</span>');
                
                // Cash-on-cash return
                var downPayment = HPHFieldCalculations.getFieldValue('estimated_down_payment_amount');
                if (downPayment > 0) {
                    var cashOnCash = (annualCashFlow / downPayment) * 100;
                    HPHFieldCalculations.setFieldValue('cash_on_cash_return', Math.round(cashOnCash * 100) / 100);
                    HPHFieldCalculations.updateCalculatedField('cash_on_cash_return', cashOnCash.toFixed(2) + '%');
                }
            }
        },

        calculateRoomRatios: function() {
            var bedrooms = HPHFieldCalculations.getFieldValue('bedrooms');
            var bathrooms = HPHFieldCalculations.getFieldValue('bathrooms');
            var sqft = HPHFieldCalculations.getFieldValue('square_footage');
            
            if (bedrooms && bathrooms) {
                var ratio = Math.round((bedrooms / bathrooms) * 100) / 100;
                HPHFieldCalculations.setFieldValue('bedroom_bathroom_ratio', ratio);
                HPHFieldCalculations.updateCalculatedField('bedroom_bathroom_ratio', ratio + ':1');
            }
            
            if (bedrooms && sqft) {
                var sqftPerBedroom = Math.round(sqft / bedrooms);
                HPHFieldCalculations.setFieldValue('sqft_per_bedroom', sqftPerBedroom);
                HPHFieldCalculations.updateCalculatedField('sqft_per_bedroom', sqftPerBedroom + ' sq ft per bedroom');
            }
        },

        calculateTotalSquareFootage: function() {
            var living = HPHFieldCalculations.getFieldValue('living_square_footage') || 0;
            var garage = HPHFieldCalculations.getFieldValue('garage_square_footage') || 0;
            var basement = HPHFieldCalculations.getFieldValue('basement_square_footage') || 0;
            var other = HPHFieldCalculations.getFieldValue('other_square_footage') || 0;
            
            var total = living + garage + basement + other;
            
            if (total > 0) {
                HPHFieldCalculations.setFieldValue('square_footage', total);
                HPHFieldCalculations.setFieldValue('calculated_from_components', true);
                
                // Update efficiency ratio
                if (living > 0) {
                    var efficiency = Math.round((living / total) * 1000) / 10;
                    HPHFieldCalculations.setFieldValue('living_space_efficiency', efficiency);
                    HPHFieldCalculations.updateCalculatedField('living_space_efficiency', efficiency + '% living space');
                }
            }
        },

        // Utility functions
        getFieldValue: function(fieldName) {
            var $field = $('[data-name="' + fieldName + '"] input, [data-name="' + fieldName + '"] select');
            if ($field.length) {
                var value = $field.val();
                return value ? parseFloat(value) : 0;
            }
            return 0;
        },

        setFieldValue: function(fieldName, value) {
            var $field = $('[data-name="' + fieldName + '"] input');
            if ($field.length) {
                $field.val(value).trigger('change');
            }
        },

        updateCalculatedField: function(fieldName, displayValue) {
            var $field = $('[data-name="' + fieldName + '"]');
            var $calculated = $field.find('.calculated-value');
            
            if ($calculated.length === 0) {
                $calculated = $('<div class="calculated-value" style="margin-top: 5px; font-size: 12px; color: #666; font-style: italic;"></div>');
                $field.find('.acf-input').append($calculated);
            }
            
            $calculated.html('Calculated: ' + displayValue);
        },

        // Debounce function to limit calculation frequency
        debounce: function(func, wait) {
            var timeout;
            return function executedFunction() {
                var context = this;
                var args = arguments;
                var later = function() {
                    timeout = null;
                    func.apply(context, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        // Add visual indicators for calculated fields
        addCalculationIndicators: function() {
            var calculatedFields = [
                'price_per_sqft',
                'estimated_monthly_payment',
                'total_monthly_hoa',
                'total_monthly_cost',
                'monthly_cash_flow',
                'gross_rent_multiplier',
                'bedroom_bathroom_ratio'
            ];
            
            calculatedFields.forEach(function(fieldName) {
                var $field = $('[data-name="' + fieldName + '"]');
                if ($field.length) {
                    $field.find('.acf-label label').append(' <span style="color: #0073aa;">📊</span>');
                    $field.addClass('calculated-field');
                }
            });
        },

        // Show/hide advanced calculations
        toggleAdvancedCalculations: function() {
            var $button = $('<button type="button" class="button" style="margin: 10px 0;">Toggle Advanced Calculations</button>');
            var $advancedFields = $('.acf-field[data-name*="cash_flow"], .acf-field[data-name*="cap_rate"], .acf-field[data-name*="roi"]');
            
            $button.on('click', function() {
                $advancedFields.slideToggle();
                $(this).text($advancedFields.is(':visible') ? 'Hide Advanced Calculations' : 'Show Advanced Calculations');
            });
            
            $('.acf-field[data-name="price"]').after($button);
            $advancedFields.hide();
        }
    };

    // Initialize when ACF is ready
    $(document).ready(function() {
        // Wait for ACF to be fully loaded
        setTimeout(function() {
            if (typeof acf !== 'undefined') {
                HPHFieldCalculations.init();
                HPHFieldCalculations.addCalculationIndicators();
                HPHFieldCalculations.toggleAdvancedCalculations();
            }
        }, 1000);
    });

    // Also initialize on ACF ready event if available
    if (typeof acf !== 'undefined') {
        acf.addAction('ready', function() {
            HPHFieldCalculations.init();
            HPHFieldCalculations.addCalculationIndicators();
        });
    }

})(jQuery);
