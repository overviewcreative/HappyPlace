<?php

/**
 * Data Validator Utility Class
 * 
 * Simple data validation utility for Happy Place plugin
 * 
 * @package HappyPlace
 */

namespace HappyPlace\Utilities;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Data Validator Class
 */
class Data_Validator
{
    /**
     * @var Data_Validator|null Singleton instance
     */
    private static ?self $instance = null;

    /**
     * Get singleton instance
     */
    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        // Initialize validator
    }

    /**
     * Validate email address
     */
    public function validate_email(string $email): bool
    {
        return is_email($email) !== false;
    }

    /**
     * Validate phone number (basic validation)
     */
    public function validate_phone(string $phone): bool
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        return strlen($cleaned) >= 10;
    }

    /**
     * Validate required field
     */
    public function validate_required($value): bool
    {
        if (is_string($value)) {
            return !empty(trim($value));
        }
        return !empty($value);
    }

    /**
     * Validate numeric value
     */
    public function validate_numeric($value): bool
    {
        return is_numeric($value);
    }

    /**
     * Validate price (positive number)
     */
    public function validate_price($value): bool
    {
        return is_numeric($value) && floatval($value) >= 0;
    }

    /**
     * Validate listing data
     */
    public function validate_listing_data(array $data): array
    {
        $errors = [];

        // Required fields
        if (!$this->validate_required($data['title'] ?? '')) {
            $errors['title'] = 'Title is required';
        }

        if (!$this->validate_required($data['address'] ?? '')) {
            $errors['address'] = 'Address is required';
        }

        if (!$this->validate_price($data['price'] ?? 0)) {
            $errors['price'] = 'Valid price is required';
        }

        // Optional validations
        if (!empty($data['email']) && !$this->validate_email($data['email'])) {
            $errors['email'] = 'Invalid email address';
        }

        if (!empty($data['phone']) && !$this->validate_phone($data['phone'])) {
            $errors['phone'] = 'Invalid phone number';
        }

        return $errors;
    }

    /**
     * Validate lead data
     */
    public function validate_lead_data(array $data): array
    {
        $errors = [];

        // Required fields
        if (!$this->validate_required($data['name'] ?? '')) {
            $errors['name'] = 'Name is required';
        }

        if (!$this->validate_required($data['email'] ?? '')) {
            $errors['email'] = 'Email is required';
        } elseif (!$this->validate_email($data['email'])) {
            $errors['email'] = 'Invalid email address';
        }

        // Optional phone validation
        if (!empty($data['phone']) && !$this->validate_phone($data['phone'])) {
            $errors['phone'] = 'Invalid phone number';
        }

        return $errors;
    }

    /**
     * Validate open house data
     */
    public function validate_open_house_data(array $data): array
    {
        $errors = [];

        // Required fields
        if (!$this->validate_required($data['listing_id'] ?? '')) {
            $errors['listing_id'] = 'Listing is required';
        }

        if (!$this->validate_required($data['date'] ?? '')) {
            $errors['date'] = 'Date is required';
        }

        if (!$this->validate_required($data['start_time'] ?? '')) {
            $errors['start_time'] = 'Start time is required';
        }

        if (!$this->validate_required($data['end_time'] ?? '')) {
            $errors['end_time'] = 'End time is required';
        }

        return $errors;
    }

    /**
     * Sanitize text input
     */
    public function sanitize_text(string $input): string
    {
        return sanitize_text_field($input);
    }

    /**
     * Sanitize email
     */
    public function sanitize_email(string $email): string
    {
        return sanitize_email($email);
    }

    /**
     * Sanitize phone number
     */
    public function sanitize_phone(string $phone): string
    {
        return preg_replace('/[^0-9\-\+\(\)\s]/', '', $phone);
    }

    /**
     * Sanitize price
     */
    public function sanitize_price($price): float
    {
        return floatval(preg_replace('/[^0-9\.]/', '', $price));
    }
}

// Initialize
Data_Validator::instance();