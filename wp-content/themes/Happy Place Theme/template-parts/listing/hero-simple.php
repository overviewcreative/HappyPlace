<?php
/**
 * Simple Hero Template for Testing
 */

if (!defined('ABSPATH')) {
    exit;
}

$listing_id = $args['listing_id'] ?? get_the_ID();
echo '<div style="padding: 20px; background: #e3f2fd; border: 2px solid #1976d2; margin: 20px;">';
echo '<h2>Simple Hero Template Test</h2>';
echo '<p>Listing ID: ' . $listing_id . '</p>';
echo '<p>Current post type: ' . get_post_type($listing_id) . '</p>';
echo '<p>Post title: ' . get_the_title($listing_id) . '</p>';

// Test if hph_get_hero_data exists and works
if (function_exists('hph_get_hero_data')) {
    echo '<p style="color: green;">hph_get_hero_data function exists</p>';
    try {
        $hero_data = hph_get_hero_data($listing_id);
        echo '<p>Hero data loaded successfully</p>';
        echo '<p>Number of images: ' . count($hero_data['images'] ?? []) . '</p>';
    } catch (Exception $e) {
        echo '<p style="color: red;">Error calling hph_get_hero_data: ' . $e->getMessage() . '</p>';
    }
} else {
    echo '<p style="color: red;">hph_get_hero_data function does not exist</p>';
}

echo '</div>';
?>
