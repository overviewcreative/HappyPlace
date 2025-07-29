<?php
/**
 * Formatting Functions
 * 
 * Text, number, and data formatting utilities
 *
 * @package HappyPlace
 * @subpackage Utilities
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Format price for display
 *
 * @param mixed $price Price value
 * @param bool $include_currency Include currency symbol
 * @return string Formatted price
 */
function hph_format_price($price, $include_currency = true) {
    if (empty($price) || !is_numeric($price)) {
        return $include_currency ? 'Price on Request' : '';
    }
    
    $formatted = number_format($price, 0);
    
    return $include_currency ? '$' . $formatted : $formatted;
}

/**
 * Format square footage
 *
 * @param mixed $sqft Square footage value
 * @return string Formatted square footage
 */
function hph_format_sqft($sqft) {
    if (empty($sqft) || !is_numeric($sqft)) {
        return '';
    }
    
    return number_format($sqft, 0) . ' sq ft';
}

/**
 * Format phone number
 *
 * @param string $phone Phone number
 * @return string Formatted phone number
 */
function hph_format_phone($phone) {
    if (empty($phone)) {
        return '';
    }
    
    // Remove all non-digit characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Format based on length
    if (strlen($phone) === 10) {
        return sprintf('(%s) %s-%s', 
            substr($phone, 0, 3),
            substr($phone, 3, 3),
            substr($phone, 6, 4)
        );
    } elseif (strlen($phone) === 11 && substr($phone, 0, 1) === '1') {
        return sprintf('+1 (%s) %s-%s', 
            substr($phone, 1, 3),
            substr($phone, 4, 3),
            substr($phone, 7, 4)
        );
    }
    
    return $phone; // Return as-is if format not recognized
}

/**
 * Truncate text with proper word boundaries
 *
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @param string $suffix Suffix to append
 * @return string Truncated text
 */
function hph_truncate_text($text, $length = 150, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    $truncated = substr($text, 0, $length);
    $last_space = strrpos($truncated, ' ');
    
    if ($last_space !== false) {
        $truncated = substr($truncated, 0, $last_space);
    }
    
    return $truncated . $suffix;
}

/**
 * Format bedrooms/bathrooms display
 *
 * @param mixed $bedrooms Number of bedrooms
 * @param mixed $bathrooms Number of bathrooms
 * @return string Formatted bed/bath string
 */
function hph_format_bed_bath($bedrooms, $bathrooms) {
    $parts = array();
    
    if (!empty($bedrooms) && is_numeric($bedrooms)) {
        $bed_text = $bedrooms == 1 ? 'bed' : 'beds';
        $parts[] = $bedrooms . ' ' . $bed_text;
    }
    
    if (!empty($bathrooms) && is_numeric($bathrooms)) {
        $bath_text = $bathrooms == 1 ? 'bath' : 'baths';
        $parts[] = $bathrooms . ' ' . $bath_text;
    }
    
    return implode(', ', $parts);
}

/**
 * Format address for display
 *
 * @param array $address_parts Address components
 * @return string Formatted address
 */
function hph_format_address($address_parts) {
    if (empty($address_parts) || !is_array($address_parts)) {
        return '';
    }
    
    $formatted_parts = array();
    
    // Street address
    if (!empty($address_parts['street'])) {
        $formatted_parts[] = $address_parts['street'];
    }
    
    // City, State ZIP
    $city_state_zip = array();
    if (!empty($address_parts['city'])) {
        $city_state_zip[] = $address_parts['city'];
    }
    if (!empty($address_parts['state'])) {
        $city_state_zip[] = $address_parts['state'];
    }
    if (!empty($address_parts['zip'])) {
        $city_state_zip[] = $address_parts['zip'];
    }
    
    if (!empty($city_state_zip)) {
        $formatted_parts[] = implode(', ', $city_state_zip);
    }
    
    return implode('<br>', $formatted_parts);
}

/**
 * Sanitize and format HTML content
 *
 * @param string $content HTML content
 * @param bool $allow_html Allow HTML tags
 * @return string Sanitized content
 */
function hph_format_content($content, $allow_html = true) {
    if (empty($content)) {
        return '';
    }
    
    if ($allow_html) {
        // Allow basic HTML tags
        $allowed_tags = '<p><br><strong><b><em><i><a><ul><ol><li><h3><h4><h5><h6>';
        return strip_tags($content, $allowed_tags);
    } else {
        return wp_strip_all_tags($content);
    }
}
