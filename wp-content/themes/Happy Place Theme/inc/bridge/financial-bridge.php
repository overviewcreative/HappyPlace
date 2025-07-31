<?php
/**
 * Get financial data for mortgage calculations
 * 
 * @param int $listing_id Listing ID
 * @return array Financial data
 */
function hph_bridge_get_financial_data($listing_id) {
    $cache_key = "hph_financial_data_{$listing_id}";
    $cached_data = wp_cache_get($cache_key, 'hph_financial');
    
    if ($cached_data !== false) {
        return $cached_data;
    }
    
    $listing_data = hph_bridge_get_listing_data($listing_id);
    $price = is_numeric($listing_data['price']) ? floatval($listing_data['price']) : 0;
    
    if ($price <= 0) {
        return [];
    }
    
    // Get or calculate financial details
    $financial_data = [
        'price' => $price,
        'down_payment_percent' => 20, // Default 20%
        'interest_rate' => hph_get_current_interest_rate(),
        'loan_term' => 30, // Default 30 years
        'property_taxes' => hph_calculate_property_taxes($price, $listing_id),
        'insurance' => hph_calculate_insurance($price, $listing_id),
        'hoa_fees' => floatval($listing_data['hoa_fees'] ?? 0),
        'pmi_rate' => 0.5 // Default PMI rate percentage
    ];
    
    // Calculate various down payment scenarios
    $financial_data['payment_scenarios'] = hph_calculate_payment_scenarios($financial_data);
    
    wp_cache_set($cache_key, $financial_data, 'hph_financial', 3600);
    
    return $financial_data;
}

/**
 * Get current interest rate (fallback to default)
 * 
 * @return float Current interest rate
 */
function hph_get_current_interest_rate() {
    // Try to get from theme options or plugin settings
    $rate = get_option('hph_current_interest_rate', 6.5);
    
    // Fallback to reasonable default
    return is_numeric($rate) ? floatval($rate) : 6.5;
}

/**
 * Calculate property taxes
 * 
 * @param float $price Property price
 * @param int $listing_id Listing ID
 * @return float Annual property tax estimate
 */
function hph_calculate_property_taxes($price, $listing_id) {
    // Try to get actual property tax data
    $actual_taxes = function_exists('get_field') 
        ? get_field('property_taxes', $listing_id) 
        : get_post_meta($listing_id, 'property_taxes', true);
    
    if (!empty($actual_taxes) && is_numeric($actual_taxes)) {
        return floatval($actual_taxes);
    }
    
    // Estimate based on location and price
    // Default rate of 1.2% annually
    $tax_rate = hph_get_tax_rate_by_location($listing_id);
    
    return $price * ($tax_rate / 100);
}

/**
 * Get tax rate by location
 * 
 * @param int $listing_id Listing ID
 * @return float Tax rate percentage
 */
function hph_get_tax_rate_by_location($listing_id) {
    $listing_data = hph_bridge_get_listing_data($listing_id);
    $state = strtoupper($listing_data['state'] ?? '');
    
    // State-based tax rate estimates
    $state_rates = [
        'CA' => 0.75,
        'TX' => 1.8,
        'NY' => 1.4,
        'FL' => 0.9,
        'IL' => 2.3,
        'NJ' => 2.5,
        'DE' => 0.6, // Delaware
        // Add more states as needed
    ];
    
    return isset($state_rates[$state]) ? $state_rates[$state] : 1.2; // Default
}

/**
 * Calculate homeowner's insurance
 * 
 * @param float $price Property price
 * @param int $listing_id Listing ID
 * @return float Annual insurance estimate
 */
function hph_calculate_insurance($price, $listing_id) {
    // Try to get actual insurance data
    $actual_insurance = function_exists('get_field') 
        ? get_field('insurance_cost', $listing_id) 
        : get_post_meta($listing_id, 'insurance_cost', true);
    
    if (!empty($actual_insurance) && is_numeric($actual_insurance)) {
        return floatval($actual_insurance);
    }
    
    // Estimate: typically 0.3% to 0.5% of home value annually
    return $price * 0.004; // 0.4% default
}

/**
 * Calculate payment scenarios with different down payments
 * 
 * @param array $financial_data Base financial data
 * @return array Payment scenarios
 */
function hph_calculate_payment_scenarios($financial_data) {
    $scenarios = [];
    $down_payment_options = [5, 10, 15, 20, 25, 30];
    
    foreach ($down_payment_options as $down_percent) {
        $scenario = hph_calculate_monthly_payment($financial_data, $down_percent);
        $scenarios[$down_percent] = $scenario;
    }
    
    return $scenarios;
}

/**
 * Calculate monthly payment
 * 
 * @param array $financial_data Financial data
 * @param float $down_payment_percent Down payment percentage
 * @return array Payment breakdown
 */
function hph_calculate_monthly_payment($financial_data, $down_payment_percent = null) {
    $price = $financial_data['price'];
    $down_percent = $down_payment_percent ?? $financial_data['down_payment_percent'];
    $interest_rate = $financial_data['interest_rate'];
    $loan_term = $financial_data['loan_term'];
    
    $down_payment = $price * ($down_percent / 100);
    $loan_amount = $price - $down_payment;
    
    // Calculate principal and interest
    $monthly_rate = ($interest_rate / 100) / 12;
    $num_payments = $loan_term * 12;
    
    if ($monthly_rate > 0) {
        $monthly_pi = $loan_amount * ($monthly_rate * pow(1 + $monthly_rate, $num_payments)) / (pow(1 + $monthly_rate, $num_payments) - 1);
    } else {
        $monthly_pi = $loan_amount / $num_payments;
    }
    
    // Add taxes, insurance, HOA
    $monthly_taxes = ($financial_data['property_taxes'] ?? 0) / 12;
    $monthly_insurance = ($financial_data['insurance'] ?? 0) / 12;
    $monthly_hoa = $financial_data['hoa_fees'] ?? 0;
    
    // PMI if down payment < 20%
    $monthly_pmi = 0;
    if ($down_percent < 20) {
        $pmi_rate = $financial_data['pmi_rate'] ?? 0.5;
        $monthly_pmi = ($loan_amount * ($pmi_rate / 100)) / 12;
    }
    
    $total_monthly = $monthly_pi + $monthly_taxes + $monthly_insurance + $monthly_hoa + $monthly_pmi;
    
    return [
        'down_payment' => $down_payment,
        'loan_amount' => $loan_amount,
        'monthly_pi' => $monthly_pi,
        'monthly_taxes' => $monthly_taxes,
        'monthly_insurance' => $monthly_insurance,
        'monthly_hoa' => $monthly_hoa,
        'monthly_pmi' => $monthly_pmi,
        'total_monthly' => $total_monthly,
        'down_payment_percent' => $down_percent,
        'total_interest' => ($monthly_pi * $num_payments) - $loan_amount
    ];
}