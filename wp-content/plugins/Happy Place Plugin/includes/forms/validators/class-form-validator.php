<?php
/**
 * Form Validator - Advanced validation for all form types
 * 
 * Provides comprehensive validation rules and custom validators
 * for the Happy Place form system.
 * 
 * @package HappyPlace
 * @subpackage Forms\Validators
 */

namespace HappyPlace\Forms\Validators;

if (!defined('ABSPATH')) {
    exit;
}

class Form_Validator {
    
    /**
     * Registered validation rules
     *
     * @var array
     */
    private static $rules = [];
    
    /**
     * Initialize validator
     */
    public static function init() {
        self::register_default_rules();
        error_log('HPH Form Validator: Initialized with validation rules');
    }
    
    /**
     * Register default validation rules
     */
    private static function register_default_rules() {
        // Basic validation rules
        self::register_rule('required', [self::class, 'validate_required']);
        self::register_rule('email', [self::class, 'validate_email']);
        self::register_rule('phone', [self::class, 'validate_phone']);
        self::register_rule('numeric', [self::class, 'validate_numeric']);
        self::register_rule('min_length', [self::class, 'validate_min_length']);
        self::register_rule('max_length', [self::class, 'validate_max_length']);
        self::register_rule('url', [self::class, 'validate_url']);
        self::register_rule('date', [self::class, 'validate_date']);
        self::register_rule('time', [self::class, 'validate_time']);
        
        // Real estate specific rules
        self::register_rule('price', [self::class, 'validate_price']);
        self::register_rule('square_footage', [self::class, 'validate_square_footage']);
        self::register_rule('year_built', [self::class, 'validate_year_built']);
        self::register_rule('zip_code', [self::class, 'validate_zip_code']);
        self::register_rule('mls_number', [self::class, 'validate_mls_number']);
        self::register_rule('license_number', [self::class, 'validate_license_number']);
        self::register_rule('post_exists', [self::class, 'validate_post_exists']);
        self::register_rule('user_exists', [self::class, 'validate_user_exists']);
        
        // Security rules
        self::register_rule('no_html', [self::class, 'validate_no_html']);
        self::register_rule('safe_text', [self::class, 'validate_safe_text']);
        self::register_rule('password_strength', [self::class, 'validate_password_strength']);
        
        // Custom business rules
        self::register_rule('business_hours', [self::class, 'validate_business_hours']);
        self::register_rule('future_date', [self::class, 'validate_future_date']);
        self::register_rule('age_verification', [self::class, 'validate_age_verification']);
    }
    
    /**
     * Register a validation rule
     *
     * @param string $name Rule name
     * @param callable $callback Validation callback
     */
    public static function register_rule($name, $callback) {
        self::$rules[$name] = $callback;
    }
    
    /**
     * Validate a field value against a rule
     *
     * @param string $rule Rule name
     * @param mixed $value Field value
     * @param mixed $params Rule parameters
     * @param string $field_name Field name for context
     * @return array Validation result [valid => bool, message => string]
     */
    public static function validate($rule, $value, $params = null, $field_name = '') {
        if (!isset(self::$rules[$rule])) {
            return [
                'valid' => true,
                'message' => ''
            ];
        }
        
        $callback = self::$rules[$rule];
        
        try {
            return call_user_func($callback, $value, $params, $field_name);
        } catch (Exception $e) {
            error_log('HPH Form Validator: Error in rule ' . $rule . ': ' . $e->getMessage());
            return [
                'valid' => false,
                'message' => __('Validation error occurred', 'happy-place')
            ];
        }
    }
    
    // Basic validation rules
    
    /**
     * Validate required field
     */
    public static function validate_required($value, $params = null, $field_name = '') {
        $is_valid = !empty($value) || $value === '0' || $value === 0;
        
        return [
            'valid' => $is_valid,
            'message' => $is_valid ? '' : sprintf(__('%s is required', 'happy-place'), ucfirst(str_replace('_', ' ', $field_name)))
        ];
    }
    
    /**
     * Validate email format
     */
    public static function validate_email($value, $params = null, $field_name = '') {
        if (empty($value)) {
            return ['valid' => true, 'message' => ''];
        }
        
        $is_valid = is_email($value);
        
        return [
            'valid' => $is_valid,
            'message' => $is_valid ? '' : __('Please enter a valid email address', 'happy-place')
        ];
    }
    
    /**
     * Validate phone number format
     */
    public static function validate_phone($value, $params = null, $field_name = '') {
        if (empty($value)) {
            return ['valid' => true, 'message' => ''];
        }
        
        // Allow various phone formats
        $is_valid = preg_match('/^[\+]?[\d\s\-\(\)\.]{10,20}$/', $value);
        
        return [
            'valid' => $is_valid,
            'message' => $is_valid ? '' : __('Please enter a valid phone number', 'happy-place')
        ];
    }
    
    /**
     * Validate numeric value
     */
    public static function validate_numeric($value, $params = null, $field_name = '') {
        if (empty($value)) {
            return ['valid' => true, 'message' => ''];
        }
        
        $is_valid = is_numeric($value);
        
        return [
            'valid' => $is_valid,
            'message' => $is_valid ? '' : __('Please enter a valid number', 'happy-place')
        ];
    }
    
    /**
     * Validate minimum length
     */
    public static function validate_min_length($value, $params = null, $field_name = '') {
        if (empty($value)) {
            return ['valid' => true, 'message' => ''];
        }
        
        $min_length = is_array($params) ? $params[0] : $params;
        $is_valid = strlen($value) >= $min_length;
        
        return [
            'valid' => $is_valid,
            'message' => $is_valid ? '' : sprintf(__('Must be at least %d characters long', 'happy-place'), $min_length)
        ];
    }
    
    /**
     * Validate maximum length
     */
    public static function validate_max_length($value, $params = null, $field_name = '') {
        if (empty($value)) {
            return ['valid' => true, 'message' => ''];
        }
        
        $max_length = is_array($params) ? $params[0] : $params;
        $is_valid = strlen($value) <= $max_length;
        
        return [
            'valid' => $is_valid,
            'message' => $is_valid ? '' : sprintf(__('Must be no more than %d characters long', 'happy-place'), $max_length)
        ];
    }
    
    /**
     * Validate URL format
     */
    public static function validate_url($value, $params = null, $field_name = '') {
        if (empty($value)) {
            return ['valid' => true, 'message' => ''];
        }
        
        $is_valid = filter_var($value, FILTER_VALIDATE_URL) !== false;
        
        return [
            'valid' => $is_valid,
            'message' => $is_valid ? '' : __('Please enter a valid URL', 'happy-place')
        ];
    }
    
    /**
     * Validate date format
     */
    public static function validate_date($value, $params = null, $field_name = '') {
        if (empty($value)) {
            return ['valid' => true, 'message' => ''];
        }
        
        $timestamp = strtotime($value);
        $is_valid = $timestamp !== false;
        
        return [
            'valid' => $is_valid,
            'message' => $is_valid ? '' : __('Please enter a valid date', 'happy-place')
        ];
    }
    
    /**
     * Validate time format
     */
    public static function validate_time($value, $params = null, $field_name = '') {
        if (empty($value)) {
            return ['valid' => true, 'message' => ''];
        }
        
        $timestamp = strtotime($value);
        $is_valid = $timestamp !== false;
        
        return [
            'valid' => $is_valid,
            'message' => $is_valid ? '' : __('Please enter a valid time', 'happy-place')
        ];
    }
    
    // Real estate specific validation rules
    
    /**
     * Validate price value
     */
    public static function validate_price($value, $params = null, $field_name = '') {
        if (empty($value)) {
            return ['valid' => true, 'message' => ''];
        }
        
        $price = floatval($value);
        $min_price = isset($params['min']) ? $params['min'] : 1000;
        $max_price = isset($params['max']) ? $params['max'] : 50000000;
        
        if ($price < $min_price) {
            return [
                'valid' => false,
                'message' => sprintf(__('Price must be at least $%s', 'happy-place'), number_format($min_price))
            ];
        }
        
        if ($price > $max_price) {
            return [
                'valid' => false,
                'message' => sprintf(__('Price cannot exceed $%s', 'happy-place'), number_format($max_price))
            ];
        }
        
        return ['valid' => true, 'message' => ''];
    }
    
    /**
     * Validate square footage
     */
    public static function validate_square_footage($value, $params = null, $field_name = '') {
        if (empty($value)) {
            return ['valid' => true, 'message' => ''];
        }
        
        $sqft = intval($value);
        $min_sqft = isset($params['min']) ? $params['min'] : 100;
        $max_sqft = isset($params['max']) ? $params['max'] : 50000;
        
        if ($sqft < $min_sqft || $sqft > $max_sqft) {
            return [
                'valid' => false,
                'message' => sprintf(__('Square footage must be between %d and %d', 'happy-place'), $min_sqft, $max_sqft)
            ];
        }
        
        return ['valid' => true, 'message' => ''];
    }
    
    /**
     * Validate year built
     */
    public static function validate_year_built($value, $params = null, $field_name = '') {
        if (empty($value)) {
            return ['valid' => true, 'message' => ''];
        }
        
        $year = intval($value);
        $current_year = date('Y');
        $min_year = isset($params['min']) ? $params['min'] : 1800;
        $max_year = isset($params['max']) ? $params['max'] : $current_year + 2;
        
        if ($year < $min_year || $year > $max_year) {
            return [
                'valid' => false,
                'message' => sprintf(__('Year built must be between %d and %d', 'happy-place'), $min_year, $max_year)
            ];
        }
        
        return ['valid' => true, 'message' => ''];
    }
    
    /**
     * Validate ZIP code format
     */
    public static function validate_zip_code($value, $params = null, $field_name = '') {
        if (empty($value)) {
            return ['valid' => true, 'message' => ''];
        }
        
        // US ZIP code format (5 digits or 5+4 format)
        $is_valid = preg_match('/^\d{5}(-\d{4})?$/', $value);
        
        return [
            'valid' => $is_valid,
            'message' => $is_valid ? '' : __('Please enter a valid ZIP code', 'happy-place')
        ];
    }
    
    /**
     * Validate MLS number format
     */
    public static function validate_mls_number($value, $params = null, $field_name = '') {
        if (empty($value)) {
            return ['valid' => true, 'message' => ''];
        }
        
        // Basic MLS number format (alphanumeric, 5-20 characters)
        $is_valid = preg_match('/^[A-Z0-9\-]{5,20}$/i', $value);
        
        return [
            'valid' => $is_valid,
            'message' => $is_valid ? '' : __('Please enter a valid MLS number', 'happy-place')
        ];
    }
    
    /**
     * Validate license number format
     */
    public static function validate_license_number($value, $params = null, $field_name = '') {
        if (empty($value)) {
            return ['valid' => true, 'message' => ''];
        }
        
        // Basic license number format (alphanumeric, 5-20 characters)
        $is_valid = preg_match('/^[A-Z0-9\-]{5,20}$/i', $value);
        
        return [
            'valid' => $is_valid,
            'message' => $is_valid ? '' : __('Please enter a valid license number', 'happy-place')
        ];
    }
    
    /**
     * Validate post exists
     */
    public static function validate_post_exists($value, $params = null, $field_name = '') {
        if (empty($value)) {
            return ['valid' => true, 'message' => ''];
        }
        
        $post = get_post($value);
        $post_type = is_array($params) ? $params[0] : $params;
        
        if (!$post) {
            return [
                'valid' => false,
                'message' => __('Invalid selection', 'happy-place')
            ];
        }
        
        if ($post_type && $post->post_type !== $post_type) {
            return [
                'valid' => false,
                'message' => sprintf(__('Invalid %s selection', 'happy-place'), $post_type)
            ];
        }
        
        return ['valid' => true, 'message' => ''];
    }
    
    /**
     * Validate user exists
     */
    public static function validate_user_exists($value, $params = null, $field_name = '') {
        if (empty($value)) {
            return ['valid' => true, 'message' => ''];
        }
        
        $user = get_user_by('id', $value);
        
        return [
            'valid' => $user !== false,
            'message' => $user ? '' : __('Invalid user selection', 'happy-place')
        ];
    }
    
    // Security validation rules
    
    /**
     * Validate no HTML content
     */
    public static function validate_no_html($value, $params = null, $field_name = '') {
        if (empty($value)) {
            return ['valid' => true, 'message' => ''];
        }
        
        $is_valid = $value === strip_tags($value);
        
        return [
            'valid' => $is_valid,
            'message' => $is_valid ? '' : __('HTML tags are not allowed', 'happy-place')
        ];
    }
    
    /**
     * Validate safe text (no scripts, etc.)
     */
    public static function validate_safe_text($value, $params = null, $field_name = '') {
        if (empty($value)) {
            return ['valid' => true, 'message' => ''];
        }
        
        // Check for potentially dangerous content
        $dangerous_patterns = [
            '/<script/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe/i',
            '/<object/i',
            '/<embed/i'
        ];
        
        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return [
                    'valid' => false,
                    'message' => __('Content contains potentially unsafe elements', 'happy-place')
                ];
            }
        }
        
        return ['valid' => true, 'message' => ''];
    }
    
    /**
     * Validate password strength
     */
    public static function validate_password_strength($value, $params = null, $field_name = '') {
        if (empty($value)) {
            return ['valid' => true, 'message' => ''];
        }
        
        $min_length = isset($params['min_length']) ? $params['min_length'] : 8;
        $require_mixed_case = isset($params['mixed_case']) ? $params['mixed_case'] : true;
        $require_numbers = isset($params['numbers']) ? $params['numbers'] : true;
        $require_special = isset($params['special']) ? $params['special'] : false;
        
        if (strlen($value) < $min_length) {
            return [
                'valid' => false,
                'message' => sprintf(__('Password must be at least %d characters long', 'happy-place'), $min_length)
            ];
        }
        
        if ($require_mixed_case && (!preg_match('/[a-z]/', $value) || !preg_match('/[A-Z]/', $value))) {
            return [
                'valid' => false,
                'message' => __('Password must contain both uppercase and lowercase letters', 'happy-place')
            ];
        }
        
        if ($require_numbers && !preg_match('/\d/', $value)) {
            return [
                'valid' => false,
                'message' => __('Password must contain at least one number', 'happy-place')
            ];
        }
        
        if ($require_special && !preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $value)) {
            return [
                'valid' => false,
                'message' => __('Password must contain at least one special character', 'happy-place')
            ];
        }
        
        return ['valid' => true, 'message' => ''];
    }
    
    // Custom business rules
    
    /**
     * Validate business hours format
     */
    public static function validate_business_hours($value, $params = null, $field_name = '') {
        if (empty($value) || !is_array($value)) {
            return ['valid' => true, 'message' => ''];
        }
        
        foreach ($value as $day => $hours) {
            if (isset($hours['open'], $hours['close']) && !empty($hours['open']) && !empty($hours['close'])) {
                $open_time = strtotime($hours['open']);
                $close_time = strtotime($hours['close']);
                
                if ($close_time <= $open_time) {
                    return [
                        'valid' => false,
                        'message' => sprintf(__('%s closing time must be after opening time', 'happy-place'), ucfirst($day))
                    ];
                }
            }
        }
        
        return ['valid' => true, 'message' => ''];
    }
    
    /**
     * Validate future date
     */
    public static function validate_future_date($value, $params = null, $field_name = '') {
        if (empty($value)) {
            return ['valid' => true, 'message' => ''];
        }
        
        $date_timestamp = strtotime($value);
        $today_timestamp = strtotime('today');
        
        $is_valid = $date_timestamp >= $today_timestamp;
        
        return [
            'valid' => $is_valid,
            'message' => $is_valid ? '' : __('Date must be in the future', 'happy-place')
        ];
    }
    
    /**
     * Validate age verification (18+)
     */
    public static function validate_age_verification($value, $params = null, $field_name = '') {
        if (empty($value)) {
            return ['valid' => true, 'message' => ''];
        }
        
        $birth_date = strtotime($value);
        $age = floor((time() - $birth_date) / (365.25 * 24 * 3600));
        $min_age = isset($params['min_age']) ? $params['min_age'] : 18;
        
        $is_valid = $age >= $min_age;
        
        return [
            'valid' => $is_valid,
            'message' => $is_valid ? '' : sprintf(__('You must be at least %d years old', 'happy-place'), $min_age)
        ];
    }
    
    /**
     * Get all registered validation rules
     *
     * @return array
     */
    public static function get_registered_rules() {
        return array_keys(self::$rules);
    }
}