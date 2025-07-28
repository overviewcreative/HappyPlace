<?php
/**
 * City API Integration Test
 *
 * Simple test script to verify Google API integration for cities
 * 
 * Usage: Add ?test_city_api=1 to any city page URL
 */

// Only run if test parameter is present and user has admin capabilities
if (isset($_GET['test_city_api']) && current_user_can('manage_options')) {
    add_action('wp_footer', 'hph_test_city_api_integration');
}

function hph_test_city_api_integration() {
    if (get_post_type() !== 'city') {
        return;
    }
    
    $city_id = get_the_ID();
    echo '<div style="position: fixed; top: 32px; right: 20px; background: white; border: 2px solid #0073aa; padding: 15px; max-width: 400px; z-index: 9999; font-family: monospace; font-size: 12px;">';
    echo '<h3 style="margin: 0 0 10px 0; color: #0073aa;">City API Integration Test</h3>';
    
    // Test 1: Check if service class exists
    echo '<strong>1. Service Class:</strong> ';
    if (class_exists('HappyPlace\\Services\\City_API_Integration')) {
        echo '<span style="color: green;">✓ Available</span><br>';
    } else {
        echo '<span style="color: red;">✗ Missing</span><br>';
    }
    
    // Test 2: Check Google API key
    echo '<strong>2. Google API Key:</strong> ';
    $api_key = get_option('hph_google_maps_api_key');
    if (!empty($api_key)) {
        echo '<span style="color: green;">✓ Configured (' . substr($api_key, 0, 8) . '...)</span><br>';
    } else {
        echo '<span style="color: red;">✗ Missing</span><br>';
    }
    
    // Test 3: Check city coordinates
    echo '<strong>3. City Coordinates:</strong> ';
    $coordinates = hph_bridge_get_city_coordinates($city_id);
    if ($coordinates) {
        echo '<span style="color: green;">✓ Available (' . $coordinates['lat'] . ', ' . $coordinates['lng'] . ')</span><br>';
    } else {
        echo '<span style="color: red;">✗ Missing</span><br>';
    }
    
    // Test 4: Check places data
    echo '<strong>4. Places Data:</strong> ';
    $places = hph_bridge_get_city_places($city_id);
    if (!empty($places)) {
        echo '<span style="color: green;">✓ Available (' . count($places) . ' places)</span><br>';
    } else {
        echo '<span style="color: orange;">⚠ Empty</span><br>';
    }
    
    // Test 5: Check places source
    echo '<strong>5. Places Source:</strong> ';
    $places_source = get_field('places_source', $city_id);
    echo $places_source ?: 'not set';
    echo '<br>';
    
    // Test 6: Check API status
    echo '<strong>6. API Status:</strong> ';
    $api_status = hph_bridge_get_city_api_status($city_id);
    if ($api_status) {
        echo $api_status['status'] ?: 'unknown';
        if ($api_status['last_updated']) {
            echo ' (updated ' . wp_date('M j, Y g:i a', $api_status['last_updated']) . ')';
        }
    } else {
        echo 'not available';
    }
    echo '<br>';
    
    // Test 7: Bridge functions test
    echo '<strong>7. Bridge Functions:</strong> ';
    $bridge_functions = [
        'hph_bridge_get_city_data',
        'hph_bridge_get_city_places',
        'hph_bridge_get_city_places_by_category',
        'hph_bridge_get_city_coordinates',
        'hph_bridge_get_city_api_status'
    ];
    
    $available_functions = array_filter($bridge_functions, 'function_exists');
    echo '<span style="color: green;">✓ ' . count($available_functions) . '/' . count($bridge_functions) . ' available</span><br>';
    
    // Test 8: Check if admin scripts are loaded
    echo '<strong>8. Admin Scripts:</strong> ';
    if (is_admin()) {
        global $wp_scripts;
        if (isset($wp_scripts->registered['hph-city-admin'])) {
            echo '<span style="color: green;">✓ Registered</span><br>';
        } else {
            echo '<span style="color: orange;">⚠ Not registered (admin only)</span><br>';
        }
    } else {
        echo '<span style="color: blue;">ℹ Front-end (admin scripts not applicable)</span><br>';
    }
    
    // Test 9: JavaScript dependencies
    echo '<strong>9. Google Maps API:</strong> ';
    echo '<script>
        window.addEventListener("load", function() {
            var status = document.getElementById("gmap-api-status");
            if (typeof google !== "undefined" && google.maps) {
                status.innerHTML = "<span style=\"color: green;\">✓ Loaded</span>";
            } else {
                status.innerHTML = "<span style=\"color: red;\">✗ Not loaded</span>";
            }
        });
    </script>';
    echo '<span id="gmap-api-status">Checking...</span><br>';
    
    // Manual test buttons (admin only)
    if (is_admin() && current_user_can('edit_posts')) {
        echo '<hr style="margin: 10px 0;">';
        echo '<strong>Manual Tests:</strong><br>';
        echo '<button onclick="testGeocode()">Test Geocoding</button> ';
        echo '<button onclick="testPlacesRefresh()">Test Places Refresh</button><br>';
        
        echo '<script>
            function testGeocode() {
                if (typeof hph_city_ajax === "undefined") {
                    alert("Admin AJAX not available");
                    return;
                }
                
                jQuery.post(hph_city_ajax.ajax_url, {
                    action: "hph_geocode_city",
                    nonce: hph_city_ajax.nonce,
                    post_id: hph_city_ajax.post_id
                }, function(response) {
                    alert("Geocoding result: " + (response.success ? "Success" : "Failed"));
                });
            }
            
            function testPlacesRefresh() {
                if (typeof hph_city_ajax === "undefined") {
                    alert("Admin AJAX not available");
                    return;
                }
                
                jQuery.post(hph_city_ajax.ajax_url, {
                    action: "hph_refresh_city_places",
                    nonce: hph_city_ajax.nonce,
                    post_id: hph_city_ajax.post_id
                }, function(response) {
                    alert("Places refresh result: " + (response.success ? "Success" : "Failed"));
                });
            }
        </script>';
    }
    
    echo '<hr style="margin: 10px 0;">';
    echo '<small style="color: #666;">Add ?test_city_api=1 to any city URL to run this test</small>';
    echo '</div>';
}
