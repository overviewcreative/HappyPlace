/**
 * Happy Place Plugin - Field Calculations & Admin Enhancements
 * Real-time calculations and improved admin UX
 */

(function($) {
    'use strict';

    // Initialize when ACF is ready
    $(document).ready(function() {
        initializeCalculations();
        initializeAdminEnhancements();
        initializePhotoGallery();
        initializeFeatureTags();
    });

    /**
     * Initialize field calculations
     */
    function initializeCalculations() {
        // Price per square foot calculation
        $('input[name*="price"], input[name*="square_footage"]').on('input', function() {
            calculatePricePerSqft();
        });

        // Total bathrooms calculation
        $('input[name*="bathrooms"], input[name*="half_bathrooms"]').on('input', function() {
            calculateTotalBathrooms();
        });

        // Monthly taxes calculation
        $('input[name*="annual_taxes"]').on('input', function() {
            calculateMonthlyTaxes();
        });

        // Mortgage calculations
        $('input[name*="price"], input[name*="estimated_down_payment"], input[name*="interest_rate"]').on('input', function() {
            calculateMortgagePayments();
        });

        // Investment calculations
        $('input[name*="estimated_monthly_rent"], input[name*="price"]').on('input', function() {
            calculateInvestmentMetrics();
        });

        // Days on market calculation
        $('input[name*="listing_date"]').on('change', function() {
            calculateDaysOnMarket();
        });

        // Total monthly cost calculation
        $('input[name*="estimated_monthly_payment"], input[name*="estimated_monthly_taxes"], input[name*="estimated_monthly_insurance"], input[name*="hoa_fee"]').on('input', function() {
            calculateTotalMonthlyCost();
        });
    }

    /**
     * Calculate price per square foot
     */
    function calculatePricePerSqft() {
        const price = parseFloat($('input[name*="price"]').val()) || 0;
        const sqft = parseFloat($('input[name*="square_footage"]').val()) || 0;
        
        if (price > 0 && sqft > 0) {
            const pricePerSqft = (price / sqft).toFixed(2);
            $('input[name*="price_per_sqft"]').val(pricePerSqft).addClass('calculated-updated');
        }
    }

    /**
     * Calculate total bathrooms
     */
    function calculateTotalBathrooms() {
        const fullBaths = parseFloat($('input[name*="bathrooms"]:not([name*="half"]):not([name*="total"])').val()) || 0;
        const halfBaths = parseFloat($('input[name*="half_bathrooms"]').val()) || 0;
        
        const totalBaths = fullBaths + (halfBaths * 0.5);
        $('input[name*="bathrooms_total"]').val(totalBaths.toFixed(1)).addClass('calculated-updated');
    }

    /**
     * Calculate monthly taxes
     */
    function calculateMonthlyTaxes() {
        const annualTaxes = parseFloat($('input[name*="annual_taxes"]').val()) || 0;
        
        if (annualTaxes > 0) {
            const monthlyTaxes = (annualTaxes / 12).toFixed(2);
            $('input[name*="estimated_monthly_taxes"]').val(monthlyTaxes).addClass('calculated-updated');
        }
    }

    /**
     * Calculate mortgage payments
     */
    function calculateMortgagePayments() {
        const price = parseFloat($('input[name*="price"]').val()) || 0;
        const downPaymentPercent = parseFloat($('input[name*="estimated_down_payment"]').val()) || 20;
        const interestRate = parseFloat($('input[name*="interest_rate"]').val()) || 7.0;
        const loanTerm = parseInt($('select[name*="loan_term"]').val()) || 30;

        if (price > 0) {
            // Down payment amount
            const downPaymentAmount = price * (downPaymentPercent / 100);
            $('input[name*="estimated_down_payment_amount"]').val(downPaymentAmount.toFixed(2)).addClass('calculated-updated');

            // Loan amount
            const loanAmount = price - downPaymentAmount;
            $('input[name*="estimated_loan_amount"]').val(loanAmount.toFixed(2)).addClass('calculated-updated');

            // Monthly payment (P&I)
            if (loanAmount > 0 && interestRate > 0) {
                const monthlyRate = (interestRate / 100) / 12;
                const numPayments = loanTerm * 12;
                
                const monthlyPayment = loanAmount * (
                    monthlyRate * Math.pow(1 + monthlyRate, numPayments)
                ) / (
                    Math.pow(1 + monthlyRate, numPayments) - 1
                );
                
                $('input[name*="estimated_monthly_payment"]').val(monthlyPayment.toFixed(2)).addClass('calculated-updated');
            }
        }
    }

    /**
     * Calculate investment metrics
     */
    function calculateInvestmentMetrics() {
        const price = parseFloat($('input[name*="price"]').val()) || 0;
        const monthlyRent = parseFloat($('input[name*="estimated_monthly_rent"]').val()) || 0;

        if (price > 0 && monthlyRent > 0) {
            // Annual rent
            const annualRent = monthlyRent * 12;
            $('input[name*="estimated_annual_rent"]').val(annualRent.toFixed(2)).addClass('calculated-updated');

            // Gross rental yield
            const grossYield = (annualRent / price) * 100;
            $('input[name*="gross_rental_yield"]').val(grossYield.toFixed(2)).addClass('calculated-updated');

            // 1% rule ratio
            const onePercentRatio = (monthlyRent / price) * 100;
            $('input[name*="one_percent_rule_ratio"]').val(onePercentRatio.toFixed(2)).addClass('calculated-updated');
        }
    }

    /**
     * Calculate days on market
     */
    function calculateDaysOnMarket() {
        const listingDate = $('input[name*="listing_date"]').val();
        
        if (listingDate) {
            const listDate = new Date(listingDate);
            const today = new Date();
            const timeDiff = today.getTime() - listDate.getTime();
            const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
            
            $('input[name*="days_on_market"]').val(Math.max(0, daysDiff)).addClass('calculated-updated');
        }
    }

    /**
     * Calculate total monthly cost
     */
    function calculateTotalMonthlyCost() {
        const monthlyPayment = parseFloat($('input[name*="estimated_monthly_payment"]').val()) || 0;
        const monthlyTaxes = parseFloat($('input[name*="estimated_monthly_taxes"]').val()) || 0;
        const monthlyInsurance = parseFloat($('input[name*="estimated_monthly_insurance"]').val()) || 0;
        const hoaFee = parseFloat($('input[name*="hoa_fee"]').val()) || 0;

        const totalMonthlyCost = monthlyPayment + monthlyTaxes + monthlyInsurance + hoaFee;
        $('input[name*="total_monthly_cost"]').val(totalMonthlyCost.toFixed(2)).addClass('calculated-updated');
    }

    /**
     * Initialize admin enhancements
     */
    function initializeAdminEnhancements() {
        // Add visual feedback for calculated fields
        $('.calculated-updated').each(function() {
            $(this).addClass('success');
            setTimeout(() => {
                $(this).removeClass('success calculated-updated');
            }, 2000);
        });

        // Readonly field enforcement
        $('.hph-calculated-field input, .hph-calculated-field textarea, .hph-calculated-field select').attr('readonly', true);
        $('.hph-auto-populated input, .hph-auto-populated textarea, .hph-auto-populated select').attr('readonly', true);

        // Character counter for short description
        $('textarea[name*="listing_description_short"]').on('input', function() {
            const maxLength = 250;
            const currentLength = $(this).val().length;
            const remaining = maxLength - currentLength;
            
            let counter = $(this).siblings('.char-counter');
            if (counter.length === 0) {
                counter = $('<div class="char-counter"></div>');
                $(this).after(counter);
            }
            
            counter.text(remaining + ' characters remaining');
            counter.toggleClass('over-limit', remaining < 0);
        });
    }

    /**
     * Initialize photo gallery enhancements
     */
    function initializePhotoGallery() {
        // Auto-sort photos by order
        $(document).on('change', 'input[name*="photo_order"]', function() {
            sortPhotosByOrder();
        });

        // Featured photo management (only one featured)
        $(document).on('change', 'input[name*="photo_featured"]', function() {
            if ($(this).is(':checked')) {
                // Uncheck all other featured photos
                $('input[name*="photo_featured"]').not(this).prop('checked', false);
                
                // Move this photo to the top order
                $(this).closest('.acf-row').find('input[name*="photo_order"]').val(1);
                sortPhotosByOrder();
            }
        });

        // Room type color coding
        $(document).on('change', 'select[name*="photo_room_type"]', function() {
            const roomType = $(this).val();
            const row = $(this).closest('.acf-row');
            
            // Remove existing room type classes
            row.removeClass(function(index, className) {
                return (className.match(/(^|\s)room-type-\S+/g) || []).join(' ');
            });
            
            // Add new room type class
            if (roomType) {
                row.addClass('room-type-' + roomType);
            }
        });
    }

    /**
     * Sort photos by order field
     */
    function sortPhotosByOrder() {
        const photoRepeater = $('.acf-field[data-name="listing_photos"] .acf-repeater');
        const rows = photoRepeater.find('.acf-row').not('.acf-clone').get();
        
        rows.sort(function(a, b) {
            const orderA = parseInt($(a).find('input[name*="photo_order"]').val()) || 999;
            const orderB = parseInt($(b).find('input[name*="photo_order"]').val()) || 999;
            return orderA - orderB;
        });

        $.each(rows, function(index, row) {
            photoRepeater.find('.acf-tbody').append(row);
        });
    }

    /**
     * Initialize feature tags with Select2
     */
    function initializeFeatureTags() {
        // Initialize Select2 with tagging for custom features
        $('select[name*="custom_features"]').select2({
            tags: true,
            tokenSeparators: [','],
            placeholder: 'Select or type custom features...',
            allowClear: true,
            createTag: function(params) {
                const term = $.trim(params.term);
                
                if (term === '') {
                    return null;
                }
                
                return {
                    id: term.toLowerCase().replace(/\s+/g, '_'),
                    text: term,
                    newTag: true
                };
            }
        });

        // Handle new tag creation
        $('select[name*="custom_features"]').on('select2:select', function(e) {
            if (e.params.data.newTag) {
                console.log('New feature added:', e.params.data.text);
            }
        });
    }

    /**
     * Auto-populate community HOA data when community is selected
     */
    $(document).on('change', 'select[name*="community"]', function() {
        const communityId = $(this).val();
        
        if (communityId && typeof hphCalc !== 'undefined') {
            $.ajax({
                url: hphCalc.ajaxurl,
                type: 'POST',
                data: {
                    action: 'hph_get_community_hoa_data',
                    community_id: communityId,
                    nonce: hphCalc.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        // Populate HOA fee based on property type
                        const propertyType = $('select[name*="property_type"]').val();
                        let hoaFee = 0;
                        
                        switch(propertyType) {
                            case 'Single Family Home':
                                hoaFee = response.data.hoa_fee_single_family || 0;
                                break;
                            case 'Townhouse':
                                hoaFee = response.data.hoa_fee_townhouse || 0;
                                break;
                            case 'Condo':
                                hoaFee = response.data.hoa_fee_condo || 0;
                                break;
                        }
                        
                        if (hoaFee > 0) {
                            $('input[name*="hoa_fee"]').val(hoaFee).addClass('auto-populated');
                        }
                        
                        showNotification('HOA information auto-populated from community data', 'success');
                    }
                }
            });
        }
    });

    /**
     * Show admin notifications
     */
    function showNotification(message, type = 'info') {
        const notification = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap h1').after(notification);
        
        setTimeout(function() {
            notification.fadeOut();
        }, 5000);
    }

    // Add dynamic CSS for visual enhancements
    const style = $('<style>').text(`
        .char-counter {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .char-counter.over-limit {
            color: #dc3232;
            font-weight: bold;
        }
        .calculated-updated {
            background-color: #f0fff4 !important;
            border-color: #46b450 !important;
        }
        .auto-populated {
            background-color: #f0f8ff !important;
            border-color: #0073aa !important;
        }
        .room-type-exterior { border-left: 4px solid #2ecc71; }
        .room-type-kitchen { border-left: 4px solid #e74c3c; }
        .room-type-living_room { border-left: 4px solid #3498db; }
        .room-type-master_bedroom { border-left: 4px solid #9b59b6; }
        .room-type-bathroom { border-left: 4px solid #1abc9c; }
        .room-type-pool { border-left: 4px solid #00bcd4; }
    `);
    
    $('head').append(style);

})(jQuery);