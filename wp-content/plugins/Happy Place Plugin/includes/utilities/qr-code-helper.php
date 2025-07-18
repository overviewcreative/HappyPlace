<?php

/**
 * QR Code Generator for Listing Flyer
 * 
 * @package HappyPlace
 */

/**
 * Generate QR code for listing
 *
 * @param int    $listing_id The listing post ID
 * @param string $size       Size in pixels (default 80)
 * @return string URL of QR code image
 */
function hph_generate_listing_qr(int $listing_id, int $size = 80): string
{
    $listing_url = get_permalink($listing_id);

    // Using Google Charts API for QR code generation
    $google_charts_url = 'https://chart.googleapis.com/chart?';
    $params = [
        'cht' => 'qr',
        'chs' => $size . 'x' . $size,
        'chl' => urlencode($listing_url),
        'choe' => 'UTF-8'
    ];

    return $google_charts_url . http_build_query($params);
}
