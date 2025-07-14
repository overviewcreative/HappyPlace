<?php

/**
 * Data Sanitizer
 *
 * @package HappyPlace
 * @subpackage Utilities
 */

namespace HappyPlace\Utilities;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Data Sanitizer Class
 */
class Data_Sanitizer
{
    /**
     * Sanitize a phone number
     *
     * @param string $phone Phone number to sanitize.
     * @return string
     */
    public static function sanitize_phone($phone)
    {
        // Remove everything except digits
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Format as (XXX) XXX-XXXX if 10 digits
        if (strlen($phone) === 10) {
            return sprintf(
                '(%s) %s-%s',
                substr($phone, 0, 3),
                substr($phone, 3, 3),
                substr($phone, 6)
            );
        }

        return $phone;
    }

    /**
     * Sanitize price
     *
     * @param mixed $price Price to sanitize.
     * @return float
     */
    public static function sanitize_price($price)
    {
        // Remove any currency symbols and commas
        $price = preg_replace('/[^0-9.]/', '', $price);
        return (float) $price;
    }

    /**
     * Sanitize address
     *
     * @param string $address Address to sanitize.
     * @return string
     */
    public static function sanitize_address($address)
    {
        // Basic sanitization
        $address = sanitize_text_field($address);

        // Standardize abbreviations
        $replacements = array(
            '/\bApt\b/i'     => 'Apartment',
            '/\bSt\b/i'      => 'Street',
            '/\bRd\b/i'      => 'Road',
            '/\bDr\b/i'      => 'Drive',
            '/\bLn\b/i'      => 'Lane',
            '/\bAve\b/i'     => 'Avenue',
            '/\bBlvd\b/i'    => 'Boulevard',
            '/\bCt\b/i'      => 'Court',
            '/\bCir\b/i'     => 'Circle',
            '/\bTer\b/i'     => 'Terrace',
            '/\bPkwy\b/i'    => 'Parkway',
            '/\bHwy\b/i'     => 'Highway',
        );

        return preg_replace(array_keys($replacements), array_values($replacements), $address);
    }

    /**
     * Sanitize ZIP code
     *
     * @param string $zip ZIP code to sanitize.
     * @return string
     */
    public static function sanitize_zip($zip)
    {
        // Remove everything except digits and hyphen
        $zip = preg_replace('/[^0-9-]/', '', $zip);

        // Format as XXXXX or XXXXX-XXXX
        if (strlen($zip) === 5) {
            return $zip;
        } elseif (strlen($zip) === 9) {
            return substr($zip, 0, 5) . '-' . substr($zip, 5);
        }

        return $zip;
    }

    /**
     * Sanitize email
     *
     * @param string $email Email to sanitize.
     * @return string
     */
    public static function sanitize_email($email)
    {
        $email = sanitize_email($email);
        return strtolower($email);
    }

    /**
     * Sanitize URL
     *
     * @param string $url URL to sanitize.
     * @return string
     */
    public static function sanitize_url($url)
    {
        // Ensure URL starts with http:// or https://
        if (! empty($url) && strpos($url, 'http') !== 0) {
            $url = 'https://' . $url;
        }

        return esc_url_raw($url);
    }

    /**
     * Sanitize dimensions (square feet, lot size, etc.)
     *
     * @param mixed  $value Value to sanitize.
     * @param string $unit  Unit of measurement.
     * @return float
     */
    public static function sanitize_dimension($value, $unit = 'sqft')
    {
        // Remove any non-numeric characters except decimal point
        $value = preg_replace('/[^0-9.]/', '', $value);

        // Convert to float
        $value = (float) $value;

        // Apply unit-specific rules
        switch ($unit) {
            case 'acres':
                // Limit to 2 decimal places
                $value = round($value, 2);
                break;

            case 'sqft':
                // Round to nearest whole number
                $value = round($value);
                break;
        }

        return $value;
    }

    /**
     * Sanitize state
     *
     * @param string $state State to sanitize.
     * @return string
     */
    public static function sanitize_state($state)
    {
        // Convert to uppercase
        $state = strtoupper($state);

        // If it's a valid state abbreviation, return it
        $states = array(
            'AL',
            'AK',
            'AZ',
            'AR',
            'CA',
            'CO',
            'CT',
            'DE',
            'FL',
            'GA',
            'HI',
            'ID',
            'IL',
            'IN',
            'IA',
            'KS',
            'KY',
            'LA',
            'ME',
            'MD',
            'MA',
            'MI',
            'MN',
            'MS',
            'MO',
            'MT',
            'NE',
            'NV',
            'NH',
            'NJ',
            'NM',
            'NY',
            'NC',
            'ND',
            'OH',
            'OK',
            'OR',
            'PA',
            'RI',
            'SC',
            'SD',
            'TN',
            'TX',
            'UT',
            'VT',
            'VA',
            'WA',
            'WV',
            'WI',
            'WY',
            'DC',
        );

        if (in_array($state, $states, true)) {
            return $state;
        }

        // If it's a full state name, convert to abbreviation
        $state_names = array(
            'ALABAMA' => 'AL',
            'ALASKA' => 'AK',
            'ARIZONA' => 'AZ',
            // ... Add all state names
        );

        $state = array_key_exists($state, $state_names) ? $state_names[$state] : $state;

        return sanitize_text_field($state);
    }

    /**
     * Sanitize HTML content with specific allowed tags
     *
     * @param string $content Content to sanitize.
     * @return string
     */
    public static function sanitize_html_content($content)
    {
        $allowed_html = array(
            'p'      => array(),
            'br'     => array(),
            'strong' => array(),
            'em'     => array(),
            'ul'     => array(),
            'ol'     => array(),
            'li'     => array(),
            'h2'     => array(),
            'h3'     => array(),
            'h4'     => array(),
            'a'      => array(
                'href'   => array(),
                'title'  => array(),
                'target' => array(),
            ),
        );

        return wp_kses($content, $allowed_html);
    }
}
