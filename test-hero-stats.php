<?php
/**
 * Test Hero Statistics Display
 * Test URL: /test-hero-stats.php
 */

// WordPress bootstrap
require_once('./wp-load.php');

// Get the lewes-colonial listing for testing
$post = get_page_by_path('lewes-colonial', OBJECT, 'listing');
if (!$post) {
    echo '<h1>Error: lewes-colonial listing not found</h1>';
    echo '<p>Please ensure the listing exists in your WordPress installation.</p>';
    exit;
}

$listing_id = $post->ID;

echo '<h1>Hero Statistics Test - Listing ID: ' . $listing_id . '</h1>';
echo '<h2>Bridge Function Results:</h2>';

// Test all bridge functions used in hero
$tests = [
    'Price (formatted)' => function_exists('hph_bridge_get_price_formatted') ? hph_bridge_get_price_formatted($listing_id, 'standard') : 'Function not found',
    'Price (raw)' => function_exists('hph_bridge_get_price') ? hph_bridge_get_price($listing_id, false) : 'Function not found',
    'Bedrooms' => function_exists('hph_bridge_get_bedrooms') ? hph_bridge_get_bedrooms($listing_id) : 'Function not found',
    'Bathrooms (formatted)' => function_exists('hph_bridge_get_bathrooms_formatted') ? hph_bridge_get_bathrooms_formatted($listing_id) : 'Function not found',
    'Square Feet (formatted)' => function_exists('hph_bridge_get_sqft_formatted') ? hph_bridge_get_sqft_formatted($listing_id, 'standard') : 'Function not found',
    'Lot Size (formatted)' => function_exists('hph_bridge_get_lot_size_formatted') ? hph_bridge_get_lot_size_formatted($listing_id, 'auto') : 'Function not found',
    'Lot Size (raw acres)' => function_exists('hph_bridge_get_lot_size') ? hph_bridge_get_lot_size($listing_id, false) : 'Function not found',
    'Lot Size (force acres)' => function_exists('hph_bridge_get_lot_size_formatted') ? hph_bridge_get_lot_size_formatted($listing_id, 'acres') : 'Function not found',
    'Lot Size (force sqft)' => function_exists('hph_bridge_get_lot_size_formatted') ? hph_bridge_get_lot_size_formatted($listing_id, 'sqft') : 'Function not found',
    'Address (street)' => function_exists('hph_bridge_get_address') ? hph_bridge_get_address($listing_id, 'street') : 'Function not found',
    'Address (city)' => function_exists('hph_bridge_get_address') ? hph_bridge_get_address($listing_id, 'city') : 'Function not found',
    'Address (state)' => function_exists('hph_bridge_get_address') ? hph_bridge_get_address($listing_id, 'state') : 'Function not found',
    'Address (zip)' => function_exists('hph_bridge_get_zip_code') ? hph_bridge_get_zip_code($listing_id) : 'Function not found',
    'Status' => function_exists('hph_bridge_get_status') ? hph_bridge_get_status($listing_id) : 'Function not found',
    'Property Type' => function_exists('hph_bridge_get_property_type') ? hph_bridge_get_property_type($listing_id) : 'Function not found',
    'Gallery' => function_exists('hph_bridge_get_gallery') ? count(hph_bridge_get_gallery($listing_id)) . ' images' : 'Function not found',
    'Price per Sq Ft' => function_exists('hph_bridge_get_price_per_sqft') ? hph_bridge_get_price_per_sqft($listing_id, true) : 'Function not found'
];

echo '<table border="1" cellpadding="10" style="border-collapse: collapse; width: 100%; margin: 20px 0;">';
echo '<tr style="background: #f0f0f0;"><th>Function</th><th>Result</th></tr>';

foreach ($tests as $function => $result) {
    $status_color = $result === 'Function not found' ? '#ffcccc' : '#ccffcc';
    echo '<tr style="background: ' . $status_color . ';"><td><strong>' . $function . '</strong></td><td>' . (is_array($result) ? json_encode($result) : $result) . '</td></tr>';
}

echo '</table>';

// Test the hero data structure
echo '<h2>Hero Data Structure Test:</h2>';

// Simulate what the hero template does
$bedrooms = function_exists('hph_bridge_get_bedrooms') ? hph_bridge_get_bedrooms($listing_id) : 0;
$bathrooms_formatted = function_exists('hph_bridge_get_bathrooms_formatted') ? hph_bridge_get_bathrooms_formatted($listing_id) : '';
$sqft_formatted = function_exists('hph_bridge_get_sqft_formatted') ? hph_bridge_get_sqft_formatted($listing_id, 'standard') : '';
$lot_size_formatted = function_exists('hph_bridge_get_lot_size_formatted') ? hph_bridge_get_lot_size_formatted($listing_id, 'auto') : '';

$stats = [
    'bedrooms' => [
        'value' => $bedrooms > 0 ? $bedrooms : '',
        'label' => $bedrooms == 1 ? 'Bedroom' : 'Bedrooms',
        'icon' => 'fas fa-bed'
    ],
    'bathrooms' => [
        'value' => $bathrooms_formatted ?: '',
        'label' => 'Baths',
        'icon' => 'fas fa-bath'
    ],
    'sqft' => [
        'value' => $sqft_formatted ?: '',
        'label' => 'Sq Ft',
        'icon' => 'fas fa-ruler-combined'
    ],
    'lot_size' => [
        'value' => $lot_size_formatted ?: '',
        'label' => 'Lot Size',
        'icon' => 'fas fa-expand-arrows-alt'
    ]
];

echo '<h3>Formatted Stats Array:</h3>';
echo '<pre style="background: #f5f5f5; padding: 15px; border-radius: 5px;">';
print_r($stats);
echo '</pre>';

// Test actual hero display
echo '<h2>Hero Quick Facts Preview:</h2>';
echo '<div style="display: flex; gap: 20px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9;">';

foreach ($stats as $stat_key => $stat) {
    if (!empty($stat['value'])) {
        echo '<div style="display: flex; align-items: center; gap: 8px;">';
        echo '<i class="' . esc_attr($stat['icon']) . '" style="color: #007cba;"></i>';
        echo '<span style="font-weight: 600; font-size: 1.1rem;">' . esc_html($stat['value']) . '</span>';
        echo '<span style="font-size: 0.9rem; color: #666; margin-left: 4px;">' . esc_html($stat['label']) . '</span>';
        echo '</div>';
    }
}

echo '</div>';

echo '<h2>Address Display Test:</h2>';
$street_full = function_exists('hph_bridge_get_address') ? hph_bridge_get_address($listing_id, 'street') : '';
$city = function_exists('hph_bridge_get_address') ? hph_bridge_get_address($listing_id, 'city') : '';
$state = function_exists('hph_bridge_get_address') ? hph_bridge_get_address($listing_id, 'state') : '';
$zip = function_exists('hph_bridge_get_zip_code') ? hph_bridge_get_zip_code($listing_id) : '';

$main_address = $street_full ?: get_the_title($listing_id);
$location_parts = array_filter([$city, $state, $zip]);
$sub_address = !empty($location_parts) ? implode(', ', $location_parts) : '';

echo '<div style="padding: 20px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9;">';
echo '<h1 style="margin: 0 0 10px 0; font-size: 2rem;">' . esc_html($main_address) . '</h1>';
if ($sub_address) {
    echo '<div style="color: #666; font-size: 1.1rem;"><i class="fas fa-map-marker-alt" style="margin-right: 8px; color: #007cba;"></i>' . esc_html($sub_address) . '</div>';
}
echo '</div>';

echo '<p style="margin-top: 30px;"><a href="/">← Back to Site</a></p>';
?>
