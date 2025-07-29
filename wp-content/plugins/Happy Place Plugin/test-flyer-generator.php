<?php
/**
 * Flyer Generator Test Page
 * Add this to any page or post to test the flyer generator
 */

// Test shortcode usage
echo '<h2>Flyer Generator Test</h2>';
echo '<p>This tests the flyer generator shortcode and functionality.</p>';

// Display debug info if WP_DEBUG is enabled
if (defined('WP_DEBUG') && WP_DEBUG) {
    echo '<div style="background: #f0f8ff; padding: 15px; margin: 20px 0; border: 1px solid #0073aa;">';
    echo '<strong>Debug Information:</strong><br>';
    echo 'WordPress Version: ' . get_bloginfo('version') . '<br>';
    echo 'Active Theme: ' . get_template() . '<br>';
    echo 'Plugin Constants:<br>';
    echo '- HPH_VERSION: ' . (defined('HPH_VERSION') ? HPH_VERSION : 'NOT DEFINED') . '<br>';
    echo '- HPH_ASSETS_URL: ' . (defined('HPH_ASSETS_URL') ? HPH_ASSETS_URL : 'NOT DEFINED') . '<br>';
    echo '- HPH_INCLUDES_PATH: ' . (defined('HPH_INCLUDES_PATH') ? HPH_INCLUDES_PATH : 'NOT DEFINED') . '<br>';
    echo '</div>';
}

// Test if the class exists
if (class_exists('HappyPlace\Graphics\Flyer_Generator')) {
    echo '<div style="background: #d4edda; padding: 10px; margin: 10px 0; border: 1px solid #28a745; color: #155724;">✅ Flyer_Generator class is loaded</div>';
    
    // Test shortcode
    echo '<div style="border: 2px solid #007cba; padding: 20px; margin: 20px 0;">';
    echo do_shortcode('[listing_flyer_generator]');
    echo '</div>';
    
} else {
    echo '<div style="background: #f8d7da; padding: 10px; margin: 10px 0; border: 1px solid #dc3545; color: #721c24;">❌ Flyer_Generator class is NOT loaded</div>';
}

// Test if shortcode exists
if (shortcode_exists('listing_flyer_generator')) {
    echo '<div style="background: #d4edda; padding: 10px; margin: 10px 0; border: 1px solid #28a745; color: #155724;">✅ listing_flyer_generator shortcode is registered</div>';
} else {
    echo '<div style="background: #f8d7da; padding: 10px; margin: 10px 0; border: 1px solid #dc3545; color: #721c24;">❌ listing_flyer_generator shortcode is NOT registered</div>';
}

// Test AJAX actions
if (has_action('wp_ajax_generate_flyer')) {
    echo '<div style="background: #d4edda; padding: 10px; margin: 10px 0; border: 1px solid #28a745; color: #155724;">✅ AJAX action wp_ajax_generate_flyer is registered</div>';
} else {
    echo '<div style="background: #f8d7da; padding: 10px; margin: 10px 0; border: 1px solid #dc3545; color: #721c24;">❌ AJAX action wp_ajax_generate_flyer is NOT registered</div>';
}

// Check for listings
$listings_count = wp_count_posts('listing');
if ($listings_count && $listings_count->publish > 0) {
    echo '<div style="background: #d4edda; padding: 10px; margin: 10px 0; border: 1px solid #28a745; color: #155724;">✅ Found ' . $listings_count->publish . ' published listings</div>';
} else {
    echo '<div style="background: #fff3cd; padding: 10px; margin: 10px 0; border: 1px solid #ffc107; color: #856404;">⚠️ No published listings found</div>';
}

?>
