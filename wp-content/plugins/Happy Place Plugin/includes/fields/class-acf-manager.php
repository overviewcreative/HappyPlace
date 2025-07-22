<?php
/**
 * Consolidated ACF Manager
 * Handles all ACF field groups, calculations, and enhancements in one place
 */

namespace HappyPlace\Fields;

if (!defined('ABSPATH')) {
    exit;
}

class ACF_Manager
{
    private static ?self $instance = null;
    private array $field_groups = [];
    private array $calculated_fields = [];

    public static function get_instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        // Initialize hooks
        add_action('acf/init', [$this, 'register_field_groups']);
        add_action('acf/save_post', [$this, 'handle_field_calculations'], 20);
        add_filter('acf/prepare_field', [$this, 'enhance_field_display']);
        add_filter('acf/validate_value', [$this, 'validate_field_values'], 10, 4);
        
        // Enqueue scripts for real-time calculations
        add_action('admin_enqueue_scripts', [$this, 'enqueue_calculation_scripts']);
        
        // AJAX handlers
        add_action('wp_ajax_hph_calculate_metrics', [$this, 'ajax_calculate_metrics']);
        
        // Add ACF JSON save/load paths for both old and new structure
        add_filter('acf/settings/save_json', [$this, 'set_acf_json_save_point']);
        add_filter('acf/settings/load_json', [$this, 'add_acf_json_load_points']);
        
        error_log('HPH: Consolidated ACF Manager initialized with NEW FIELD STRUCTURE support');
    }

    /**
     * Register all field groups
     */
    public function register_field_groups(): void
    {
        // Use the new field structure directory
        $field_groups_path = plugin_dir_path(__FILE__) . 'acf-new/';
        
        // Load all available JSON field groups automatically
        if (is_dir($field_groups_path)) {
            $group_files = glob($field_groups_path . 'group_*.json');
            
            foreach ($group_files as $file_path) {
                $group_file = basename($file_path);
                
                if (file_exists($file_path)) {
                    $group_data = json_decode(file_get_contents($file_path), true);
                    
                    if ($group_data && is_array($group_data)) {
                        // Only register if active
                        if (!isset($group_data['active']) || $group_data['active'] === true) {
                            acf_add_local_field_group($group_data);
                            error_log("HPH: Loaded active field group: {$group_file}");
                        } else {
                            error_log("HPH: Skipped inactive field group: {$group_file}");
                        }
                    } else {
                        error_log("HPH: Invalid field group JSON: {$group_file}");
                    }
                } else {
                    error_log("HPH: Field group file not found: {$group_file}");
                }
            }
            
            error_log("HPH: All ACF field groups loaded from NEW STRUCTURE directory: {$field_groups_path}");
        } else {
            error_log("HPH: ACF NEW STRUCTURE directory not found: {$field_groups_path}");
            
            // Fallback to old directory if new doesn't exist
            $fallback_path = plugin_dir_path(__FILE__) . 'acf-json/';
            if (is_dir($fallback_path)) {
                error_log("HPH: Falling back to old structure directory: {$fallback_path}");
                $group_files = glob($fallback_path . 'group_*.json');
                
                foreach ($group_files as $file_path) {
                    $group_file = basename($file_path);
                    
                    if (file_exists($file_path)) {
                        $group_data = json_decode(file_get_contents($file_path), true);
                        
                        if ($group_data && is_array($group_data)) {
                            // Only register if active (many old files are now disabled)
                            if (!isset($group_data['active']) || $group_data['active'] === true) {
                                acf_add_local_field_group($group_data);
                                error_log("HPH: Loaded fallback field group: {$group_file}");
                            } else {
                                error_log("HPH: Skipped disabled fallback field group: {$group_file}");
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Handle all field calculations when a post is saved
     */
    public function handle_field_calculations($post_id): void
    {
        if (get_post_type($post_id) !== 'listing') {
            return;
        }

        // Prevent infinite loops
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Financial calculations
        $this->calculate_financial_metrics($post_id);
        
        // Investment calculations
        $this->calculate_investment_metrics($post_id);
        
        // Property analytics
        $this->calculate_property_analytics($post_id);
        
        // Generate derived fields
        $this->generate_derived_fields($post_id);
        
        error_log("HPH: Field calculations completed for listing {$post_id}");
    }

    /**
     * Calculate financial metrics
     */
    private function calculate_financial_metrics($post_id): void
    {
        $price = floatval(get_field('price', $post_id) ?: 0);
        $sqft = floatval(get_field('square_footage', $post_id) ?: 0);
        $down_payment_percent = floatval(get_field('estimated_down_payment', $post_id) ?: 20);
        $interest_rate = floatval(get_field('interest_rate', $post_id) ?: 7.0);

        // Price per square foot
        if ($price > 0 && $sqft > 0) {
            $price_per_sqft = $price / $sqft;
            update_field('price_per_sqft', round($price_per_sqft, 2), $post_id);
        }

        // Loan calculations
        if ($price > 0) {
            $down_payment = $price * ($down_payment_percent / 100);
            $loan_amount = $price - $down_payment;
            
            update_field('estimated_down_payment_amount', round($down_payment, 2), $post_id);
            update_field('estimated_loan_amount', round($loan_amount, 2), $post_id);

            // Monthly payment calculation
            if ($loan_amount > 0 && $interest_rate > 0) {
                $monthly_rate = ($interest_rate / 100) / 12;
                $num_payments = 30 * 12; // 30 years
                
                $monthly_payment = $loan_amount * (
                    $monthly_rate * pow(1 + $monthly_rate, $num_payments)
                ) / (
                    pow(1 + $monthly_rate, $num_payments) - 1
                );
                
                update_field('estimated_monthly_payment', round($monthly_payment, 2), $post_id);
            }
        }

        // Total monthly costs
        $monthly_payment = floatval(get_field('estimated_monthly_payment', $post_id) ?: 0);
        $monthly_taxes = floatval(get_field('estimated_monthly_taxes', $post_id) ?: 0);
        $monthly_insurance = floatval(get_field('estimated_monthly_insurance', $post_id) ?: 0);
        $monthly_hoa = floatval(get_field('total_monthly_hoa', $post_id) ?: 0);
        $monthly_pmi = floatval(get_field('estimated_monthly_pmi', $post_id) ?: 0);

        $total_monthly_cost = $monthly_payment + $monthly_taxes + $monthly_insurance + $monthly_hoa + $monthly_pmi;
        update_field('total_monthly_cost', round($total_monthly_cost, 2), $post_id);
    }

    /**
     * Calculate investment metrics
     */
    private function calculate_investment_metrics($post_id): void
    {
        $price = floatval(get_field('price', $post_id) ?: 0);
        $estimated_rent = floatval(get_field('estimated_monthly_rent', $post_id) ?: 0);
        $down_payment_percent = floatval(get_field('estimated_down_payment', $post_id) ?: 20);

        if ($price > 0 && $estimated_rent > 0) {
            // Annual rent
            $annual_rent = $estimated_rent * 12;
            update_field('estimated_annual_rent', round($annual_rent, 2), $post_id);

            // Gross yield
            $gross_yield = ($annual_rent / $price) * 100;
            update_field('gross_rental_yield', round($gross_yield, 2), $post_id);

            // 1% rule check
            $one_percent_rule = ($estimated_rent / $price) * 100;
            $meets_one_percent = $one_percent_rule >= 1.0;
            update_field('meets_one_percent_rule', $meets_one_percent, $post_id);
            update_field('one_percent_rule_ratio', round($one_percent_rule, 2), $post_id);

            // Cash-on-cash return
            if ($down_payment_percent > 0) {
                $down_payment = $price * ($down_payment_percent / 100);
                $annual_cash_flow = $this->calculate_annual_cash_flow($post_id);
                
                if ($down_payment > 0) {
                    $cash_on_cash_return = ($annual_cash_flow / $down_payment) * 100;
                    update_field('cash_on_cash_return', round($cash_on_cash_return, 2), $post_id);
                }
            }

            // Cap rate
            $annual_expenses = $this->calculate_annual_expenses($post_id);
            $net_operating_income = $annual_rent - $annual_expenses;
            
            if ($price > 0) {
                $cap_rate = ($net_operating_income / $price) * 100;
                update_field('cap_rate', round($cap_rate, 2), $post_id);
            }
        }
    }

    /**
     * Calculate property analytics
     */
    private function calculate_property_analytics($post_id): void
    {
        // Days on market
        $listing_date = get_field('listing_date', $post_id);
        if ($listing_date) {
            $listing_timestamp = strtotime($listing_date);
            $days_on_market = floor((time() - $listing_timestamp) / (60 * 60 * 24));
            update_field('days_on_market', max(0, $days_on_market), $post_id);
        }

        // Market position analysis
        $this->calculate_market_position($post_id);
    }

    /**
     * Generate derived fields (address, SEO, etc.)
     */
    private function generate_derived_fields($post_id): void
    {
        // Generate full address
        $address_components = [
            get_field('street_address', $post_id),
            get_field('unit_number', $post_id),
            get_field('city', $post_id),
            get_field('state', $post_id),
            get_field('zip_code', $post_id)
        ];
        
        $full_address = implode(', ', array_filter($address_components));
        update_field('full_address', $full_address, $post_id);

        // Generate SEO title
        $price = get_field('price', $post_id);
        $bedrooms = get_field('bedrooms', $post_id);
        $bathrooms = get_field('bathrooms', $post_id);
        $city = get_field('city', $post_id);
        
        if ($city && $price) {
            $seo_title = sprintf(
                '%s, %s - %s Bed, %s Bath - $%s',
                get_field('street_address', $post_id),
                $city,
                $bedrooms ?: '?',
                $bathrooms ?: '?',
                number_format($price)
            );
            update_field('seo_title', $seo_title, $post_id);
        }
    }

    /**
     * Enhance field display in admin
     */
    public function enhance_field_display($field): array
    {
        if (!is_admin() || !isset($field['name'])) {
            return $field;
        }

        // Add helpful instructions and readonly status for calculated fields
        $calculated_fields = [
            'price_per_sqft' => 'Automatically calculated from price and square footage.',
            'estimated_monthly_payment' => 'Calculated based on price, down payment, and interest rate.',
            'total_monthly_cost' => 'Total of payment, taxes, insurance, HOA, and PMI.',
            'cap_rate' => 'Calculated from estimated rent and operating expenses.',
            'cash_on_cash_return' => 'Annual cash flow divided by initial cash investment.',
            'days_on_market' => 'Automatically calculated from listing date.',
            'gross_rental_yield' => 'Annual rent divided by purchase price.',
            'one_percent_rule_ratio' => 'Monthly rent as percentage of purchase price.'
        ];

        if (isset($calculated_fields[$field['name']])) {
            $field['instructions'] = ($field['instructions'] ? $field['instructions'] . ' ' : '') . $calculated_fields[$field['name']];
            $field['readonly'] = 1;
            $field['wrapper']['class'] = ($field['wrapper']['class'] ?? '') . ' hph-calculated-field';
        }

        return $field;
    }

    /**
     * Validate field values
     */
    public function validate_field_values($valid, $value, $field, $input_name)
    {
        if (!$valid || empty($value)) {
            return $valid;
        }

        switch ($field['name']) {
            case 'price':
                if (!is_numeric($value) || $value < 0) {
                    return 'Price must be a positive number.';
                }
                if ($value > 50000000) {
                    return 'Price seems unusually high. Please verify.';
                }
                break;

            case 'square_footage':
                if (!is_numeric($value) || $value < 0) {
                    return 'Square footage must be a positive number.';
                }
                if ($value > 50000) {
                    return 'Square footage seems unusually large. Please verify.';
                }
                break;

            case 'bedrooms':
            case 'bathrooms':
                if (!is_numeric($value) || $value < 0) {
                    return ucfirst($field['name']) . ' must be a positive number.';
                }
                if ($value > 20) {
                    return ucfirst($field['name']) . ' count seems unusually high. Please verify.';
                }
                break;

            case 'year_built':
                $current_year = date('Y');
                if (!is_numeric($value) || $value < 1800 || $value > $current_year + 2) {
                    return 'Year built must be between 1800 and ' . ($current_year + 2) . '.';
                }
                break;
        }

        return $valid;
    }

    /**
     * Enqueue calculation scripts
     */
    public function enqueue_calculation_scripts($hook): void
    {
        if (!in_array($hook, ['post.php', 'post-new.php'])) {
            return;
        }
        
        global $post;
        if (!$post || $post->post_type !== 'listing') {
            return;
        }
        
        wp_enqueue_script(
            'hph-field-calculations',
            plugin_dir_url(__FILE__) . '../assets/js/field-calculations.js',
            ['jquery', 'acf-input'],
            '1.0.0',
            true
        );
        
        wp_localize_script('hph-field-calculations', 'hphCalc', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_calculations'),
            'post_id' => $post->ID
        ]);
    }

    /**
     * AJAX handler for real-time calculations
     */
    public function ajax_calculate_metrics(): void
    {
        check_ajax_referer('hph_calculations', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $field_data = $_POST['field_data'];
        
        $calculations = [];
        
        // Real-time calculations
        if (isset($field_data['price']) && isset($field_data['square_footage'])) {
            $price = floatval($field_data['price']);
            $sqft = floatval($field_data['square_footage']);
            
            if ($sqft > 0) {
                $calculations['price_per_sqft'] = round($price / $sqft, 2);
            }
        }
        
        if (isset($field_data['estimated_monthly_rent']) && isset($field_data['price'])) {
            $rent = floatval($field_data['estimated_monthly_rent']);
            $price = floatval($field_data['price']);
            
            if ($price > 0) {
                $calculations['one_percent_rule_ratio'] = round(($rent / $price) * 100, 2);
            }
        }
        
        wp_send_json_success($calculations);
    }

    /**
     * Helper: Calculate annual cash flow
     */
    private function calculate_annual_cash_flow($post_id): float
    {
        $annual_rent = floatval(get_field('estimated_annual_rent', $post_id) ?: 0);
        $annual_expenses = $this->calculate_annual_expenses($post_id);
        $annual_debt_service = floatval(get_field('estimated_monthly_payment', $post_id) ?: 0) * 12;
        
        return $annual_rent - $annual_expenses - $annual_debt_service;
    }

    /**
     * Helper: Calculate annual expenses
     */
    private function calculate_annual_expenses($post_id): float
    {
        $monthly_taxes = floatval(get_field('estimated_monthly_taxes', $post_id) ?: 0);
        $monthly_insurance = floatval(get_field('estimated_monthly_insurance', $post_id) ?: 0);
        $monthly_hoa = floatval(get_field('total_monthly_hoa', $post_id) ?: 0);
        $monthly_maintenance = floatval(get_field('estimated_monthly_maintenance', $post_id) ?: 0);
        $monthly_management = floatval(get_field('estimated_monthly_management', $post_id) ?: 0);
        
        return ($monthly_taxes + $monthly_insurance + $monthly_hoa + $monthly_maintenance + $monthly_management) * 12;
    }

    /**
     * Helper: Calculate market position
     */
    private function calculate_market_position($post_id): void
    {
        $price = floatval(get_field('price', $post_id) ?: 0);
        $city = get_field('city', $post_id);
        
        if (!$price || !$city) return;
        
        // Simple market position based on price ranges for the city
        // This could be enhanced with actual market data
        if ($price < 300000) {
            $position = 'below_market';
        } elseif ($price > 800000) {
            $position = 'above_market';
        } else {
            $position = 'market_rate';
        }
        
        update_field('market_position', $position, $post_id);
    }

    /**
     * Set ACF JSON save point to new structure directory
     */
    public function set_acf_json_save_point($path): string
    {
        // Save to the new structure directory
        return plugin_dir_path(__FILE__) . 'acf-new/';
    }

    /**
     * Add ACF JSON load points for both new and old structure
     */
    public function add_acf_json_load_points($paths): array
    {
        // Load from new structure first (priority)
        $paths[] = plugin_dir_path(__FILE__) . 'acf-new/';
        
        // Also load from old structure for compatibility
        $paths[] = plugin_dir_path(__FILE__) . 'acf-json/';
        
        return $paths;
    }
}
