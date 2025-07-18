<?php

namespace HappyPlace\Utilities;

/**
 * QR Code Generator for Listing Flyer
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class QR_Code {
    
    /**
     * Generate QR code for listing
     *
     * @param int    $listing_id The listing post ID
     * @param int    $size       Size in pixels (default 150)
     * @return string URL of QR code image
     */
    public static function generate_listing_qr(int $listing_id, int $size = 150): string {
        $listing_url = get_permalink($listing_id);

        // Using Google Charts API for QR code generation
        $google_charts_url = 'https://chart.googleapis.com/chart?';
        $params = [
            'cht' => 'qr',
            'chs' => $size . 'x' . $size,
            'chl' => urlencode($listing_url),
            'choe' => 'UTF-8',
            'chld' => 'L|0' // Error correction level and margin
        ];

        return $google_charts_url . http_build_query($params);
    }

    /**
     * Generate QR code for any URL
     *
     * @param string $url  The URL to encode
     * @param int    $size Size in pixels
     * @return string URL of QR code image
     */
    public static function generate_url_qr(string $url, int $size = 150): string {
        $google_charts_url = 'https://chart.googleapis.com/chart?';
        $params = [
            'cht' => 'qr',
            'chs' => $size . 'x' . $size,
            'chl' => urlencode($url),
            'choe' => 'UTF-8',
            'chld' => 'L|0'
        ];

        return $google_charts_url . http_build_query($params);
    }

    /**
     * Generate QR code with custom data
     *
     * @param string $data The data to encode
     * @param int    $size Size in pixels
     * @return string URL of QR code image
     */
    public static function generate_data_qr(string $data, int $size = 150): string {
        $google_charts_url = 'https://chart.googleapis.com/chart?';
        $params = [
            'cht' => 'qr',
            'chs' => $size . 'x' . $size,
            'chl' => urlencode($data),
            'choe' => 'UTF-8',
            'chld' => 'L|0'
        ];

        return $google_charts_url . http_build_query($params);
    }

    /**
     * Generate vCard QR code for agent contact
     *
     * @param int $agent_id The agent post ID
     * @param int $size     Size in pixels
     * @return string URL of QR code image
     */
    public static function generate_agent_vcard_qr(int $agent_id, int $size = 150): string {
        $agent = get_post($agent_id);
        if (!$agent) {
            return '';
        }

        $phone = get_field('phone', $agent_id) ?: '';
        $email = get_field('email', $agent_id) ?: '';
        $company = get_field('company', $agent_id) ?: get_bloginfo('name');
        
        // Create vCard format
        $vcard = "BEGIN:VCARD\n";
        $vcard .= "VERSION:3.0\n";
        $vcard .= "FN:" . $agent->post_title . "\n";
        $vcard .= "ORG:" . $company . "\n";
        if ($phone) {
            $vcard .= "TEL:" . $phone . "\n";
        }
        if ($email) {
            $vcard .= "EMAIL:" . $email . "\n";
        }
        $vcard .= "END:VCARD";

        return self::generate_data_qr($vcard, $size);
    }
}

// Maintain backward compatibility
if (!function_exists('hph_generate_listing_qr')) {
    function hph_generate_listing_qr(int $listing_id, int $size = 150): string {
        return QR_Code::generate_listing_qr($listing_id, $size);
    }
}
