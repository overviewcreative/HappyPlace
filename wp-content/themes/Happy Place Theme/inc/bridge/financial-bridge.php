<?php
/**
 * Financial Bridge Functions
 * Pure financial calculations and property value operations
 * 
 * @package HappyPlace
 * @subpackage Bridge
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Calculate monthly mortgage payment
 * @param float $principal Loan amount
 * @param float $interest_rate Annual interest rate (as percentage)
 * @param int $term_years Loan term in years
 * @return float Monthly payment amount
 */
function hph_calculate_mortgage_payment($principal, $interest_rate, $term_years = 30) {
    if ($principal <= 0 || $interest_rate < 0 || $term_years <= 0) {
        return 0;
    }

    $cache_key = 'mortgage_' . md5($principal . '_' . $interest_rate . '_' . $term_years);
    $cached_payment = wp_cache_get($cache_key, 'hph_financial');
    
    if ($cached_payment !== false) {
        return $cached_payment;
    }

    // Convert annual rate to monthly decimal
    $monthly_rate = ($interest_rate / 100) / 12;
    $num_payments = $term_years * 12;

    // Calculate monthly payment using amortization formula
    if ($monthly_rate > 0) {
        $payment = $principal * (
            ($monthly_rate * pow(1 + $monthly_rate, $num_payments)) /
            (pow(1 + $monthly_rate, $num_payments) - 1)
        );
    } else {
        // Handle 0% interest rate
        $payment = $principal / $num_payments;
    }

    // Cache for 24 hours
    wp_cache_set($cache_key, $payment, 'hph_financial', 86400);
    
    return round($payment, 2);
}

/**
 * Calculate property taxes (estimated)
 * @param float $property_value Property value
 * @param float $tax_rate Annual tax rate (as percentage)
 * @return float Annual property tax amount
 */
function hph_calculate_property_tax($property_value, $tax_rate = 1.2) {
    if ($property_value <= 0 || $tax_rate < 0) {
        return 0;
    }

    return round(($property_value * $tax_rate) / 100, 2);
}

/**
 * Calculate monthly property tax
 * @param float $property_value Property value
 * @param float $tax_rate Annual tax rate (as percentage)
 * @return float Monthly property tax amount
 */
function hph_calculate_monthly_property_tax($property_value, $tax_rate = 1.2) {
    $annual_tax = hph_calculate_property_tax($property_value, $tax_rate);
    return round($annual_tax / 12, 2);
}

/**
 * Calculate homeowners insurance (estimated)
 * @param float $property_value Property value
 * @param float $insurance_rate Annual insurance rate (as percentage of value)
 * @return float Annual insurance cost
 */
function hph_calculate_homeowners_insurance($property_value, $insurance_rate = 0.35) {
    if ($property_value <= 0 || $insurance_rate < 0) {
        return 0;
    }

    return round(($property_value * $insurance_rate) / 100, 2);
}

/**
 * Calculate monthly homeowners insurance
 * @param float $property_value Property value
 * @param float $insurance_rate Annual insurance rate (as percentage of value)
 * @return float Monthly insurance cost
 */
function hph_calculate_monthly_insurance($property_value, $insurance_rate = 0.35) {
    $annual_insurance = hph_calculate_homeowners_insurance($property_value, $insurance_rate);
    return round($annual_insurance / 12, 2);
}

/**
 * Calculate PMI (Private Mortgage Insurance)
 * @param float $loan_amount Loan amount
 * @param float $down_payment Down payment amount
 * @param float $pmi_rate Annual PMI rate (as percentage)
 * @return float Annual PMI cost
 */
function hph_calculate_pmi($loan_amount, $down_payment, $pmi_rate = 0.5) {
    $loan_to_value = $loan_amount / ($loan_amount + $down_payment);
    
    // PMI typically required if LTV > 80%
    if ($loan_to_value <= 0.8) {
        return 0;
    }

    return round(($loan_amount * $pmi_rate) / 100, 2);
}

/**
 * Calculate monthly PMI
 * @param float $loan_amount Loan amount
 * @param float $down_payment Down payment amount
 * @param float $pmi_rate Annual PMI rate (as percentage)
 * @return float Monthly PMI cost
 */
function hph_calculate_monthly_pmi($loan_amount, $down_payment, $pmi_rate = 0.5) {
    $annual_pmi = hph_calculate_pmi($loan_amount, $down_payment, $pmi_rate);
    return round($annual_pmi / 12, 2);
}

/**
 * Calculate total monthly payment (PITI + PMI)
 * @param array $params Array with keys: principal, interest_rate, term_years, property_value, down_payment
 * @return array Breakdown of monthly payment components
 */
function hph_calculate_total_monthly_payment($params) {
    $defaults = [
        'principal' => 0,
        'interest_rate' => 4.5,
        'term_years' => 30,
        'property_value' => 0,
        'down_payment' => 0,
        'tax_rate' => 1.2,
        'insurance_rate' => 0.35,
        'pmi_rate' => 0.5
    ];

    $params = wp_parse_args($params, $defaults);

    $cache_key = 'total_payment_' . md5(serialize($params));
    $cached_payment = wp_cache_get($cache_key, 'hph_financial');
    
    if ($cached_payment !== false) {
        return $cached_payment;
    }

    $payment_breakdown = [
        'principal_interest' => hph_calculate_mortgage_payment(
            $params['principal'], 
            $params['interest_rate'], 
            $params['term_years']
        ),
        'property_tax' => hph_calculate_monthly_property_tax(
            $params['property_value'], 
            $params['tax_rate']
        ),
        'insurance' => hph_calculate_monthly_insurance(
            $params['property_value'], 
            $params['insurance_rate']
        ),
        'pmi' => hph_calculate_monthly_pmi(
            $params['principal'], 
            $params['down_payment'], 
            $params['pmi_rate']
        )
    ];

    $payment_breakdown['total'] = array_sum($payment_breakdown);

    // Cache for 6 hours
    wp_cache_set($cache_key, $payment_breakdown, 'hph_financial', 21600);
    
    return $payment_breakdown;
}

/**
 * Calculate affordability based on income
 * @param float $monthly_income Gross monthly income
 * @param float $monthly_debts Existing monthly debt payments
 * @param float $debt_to_income_ratio Maximum DTI ratio (default 0.43)
 * @return float Maximum affordable monthly payment
 */
function hph_calculate_affordability($monthly_income, $monthly_debts = 0, $debt_to_income_ratio = 0.43) {
    if ($monthly_income <= 0) {
        return 0;
    }

    $max_total_debt = $monthly_income * $debt_to_income_ratio;
    $available_for_housing = $max_total_debt - $monthly_debts;
    
    return max(0, round($available_for_housing, 2));
}

/**
 * Calculate maximum loan amount based on payment
 * @param float $monthly_payment Desired monthly payment
 * @param float $interest_rate Annual interest rate (as percentage)
 * @param int $term_years Loan term in years
 * @return float Maximum loan amount
 */
function hph_calculate_max_loan_amount($monthly_payment, $interest_rate, $term_years = 30) {
    if ($monthly_payment <= 0 || $interest_rate < 0 || $term_years <= 0) {
        return 0;
    }

    $monthly_rate = ($interest_rate / 100) / 12;
    $num_payments = $term_years * 12;

    if ($monthly_rate > 0) {
        $max_loan = $monthly_payment * (
            (pow(1 + $monthly_rate, $num_payments) - 1) /
            ($monthly_rate * pow(1 + $monthly_rate, $num_payments))
        );
    } else {
        // Handle 0% interest rate
        $max_loan = $monthly_payment * $num_payments;
    }

    return round($max_loan, 2);
}

/**
 * Calculate down payment percentage
 * @param float $down_payment Down payment amount
 * @param float $purchase_price Purchase price
 * @return float Down payment percentage
 */
function hph_calculate_down_payment_percentage($down_payment, $purchase_price) {
    if ($purchase_price <= 0) {
        return 0;
    }

    return round(($down_payment / $purchase_price) * 100, 2);
}

/**
 * Calculate loan-to-value ratio
 * @param float $loan_amount Loan amount
 * @param float $property_value Property value
 * @return float LTV ratio as percentage
 */
function hph_calculate_ltv_ratio($loan_amount, $property_value) {
    if ($property_value <= 0) {
        return 0;
    }

    return round(($loan_amount / $property_value) * 100, 2);
}

/**
 * Get interest rate trends (placeholder for future API integration)
 * @return array Interest rate information
 */
function hph_get_interest_rate_trends() {
    $cache_key = 'interest_rate_trends';
    $cached_rates = wp_cache_get($cache_key, 'hph_financial');
    
    if ($cached_rates !== false) {
        return $cached_rates;
    }

    // Placeholder data - would integrate with real rate API
    $rates = [
        'current_rate' => 4.5,
        'trend' => 'stable',
        'last_updated' => current_time('Y-m-d H:i:s'),
        'rates_30_year' => 4.5,
        'rates_15_year' => 4.0,
        'rates_5_1_arm' => 4.2
    ];

    // Cache for 1 hour
    wp_cache_set($cache_key, $rates, 'hph_financial', 3600);
    
    return $rates;
}
