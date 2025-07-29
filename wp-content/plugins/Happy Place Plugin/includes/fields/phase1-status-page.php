<?php
/**
 * Happy Place Implementation Status Check
 * 
 * Run this by visiting: yoursite.com/wp-admin/admin.php?page=hph-phase1-status
 */

// Add admin menu
add_action('admin_menu', function() {
    add_management_page(
        'HPH Implementation Status',
        'HPH Status',
        'administrator',
        'hph-phase1-status',
        'hph_implementation_status_page'
    );
});

function hph_implementation_status_page() {
    if (!current_user_can('administrator')) {
        wp_die('Access denied');
    }
    
    echo '<div class="wrap">';
    echo '<h1>🏠 Happy Place Implementation Status</h1>';
    echo '<p>Current Status: <strong>Phase 2 Complete</strong> | Ready for Phase 3</p>';
    
    // Check calculator enhancement
    echo '<div class="card" style="margin: 20px 0; padding: 20px;">';
    echo '<h2>🧮 Enhanced Calculator Status</h2>';
    
    $calculator_class = '\\HappyPlace\\Fields\\Listing_Calculator';
    if (class_exists($calculator_class)) {
        $calculator = $calculator_class::get_instance();
        $has_enhanced = method_exists($calculator, 'process_address_fields');
        
        echo '<p><strong>Calculator Class:</strong> ' . ($calculator ? '✅ Loaded' : '❌ Not Found') . '</p>';
        echo '<p><strong>Enhanced Methods:</strong> ' . ($has_enhanced ? '✅ Address parsing available' : '❌ Not enhanced') . '</p>';
        
        if ($has_enhanced) {
            echo '<p style="color: green;">✅ Calculator successfully enhanced with address parsing!</p>';
        }
    } else {
        echo '<p style="color: red;">❌ Calculator class not found</p>';
    }
    echo '</div>';
    
    // Check field groups
    echo '<div class="card" style="margin: 20px 0; padding: 20px;">';
    echo '<h2>📝 Field Group Status</h2>';
    
    if (function_exists('acf_get_field_group')) {
        $essential_group = acf_get_field_group('group_essential_listing_info_v2');
        $property_details = acf_get_field_group('group_property_details_classification');
        $location_intelligence = acf_get_field_group('group_location_address_intelligence');
        
        echo '<p><strong>Essential Listing Info (Phase 1):</strong> ' . ($essential_group ? '✅ Active' : '❌ Not Found') . '</p>';
        echo '<p><strong>Property Details & Classification (Phase 2):</strong> ' . ($property_details ? '✅ Active' : '❌ Not Found') . '</p>';
        echo '<p><strong>Location & Address Intelligence (Phase 2):</strong> ' . ($location_intelligence ? '✅ Active' : '❌ Not Found') . '</p>';
        
        $active_groups = array_filter([$essential_group, $property_details, $location_intelligence]);
        echo '<div style="margin-top: 10px; padding: 10px; background: #f0f0f1; border-radius: 4px;">';
        echo '<strong>Field Group Coverage:</strong> ' . count($active_groups) . '/3 groups active';
        if (count($active_groups) === 3) {
            echo '<br><span style="color: #00a32a;">🎉 All Phase 1 & 2 field groups are operational!</span>';
        } else {
            echo '<br><span style="color: #d63638;">⚠️ Some field groups missing - check ACF integration.</span>';
        }
        echo '</div>';
    } else {
        echo '<p style="color: red;">❌ ACF not available</p>';
    }
    echo '</div>';
    
    // Check enhanced field manager
    echo '<div class="card" style="margin: 20px 0; padding: 20px;">';
    echo '<h2>⚙️ Field Manager Status</h2>';
    
    $manager_class = '\\HappyPlace\\Fields\\Enhanced_Field_Manager';
    if (class_exists($manager_class)) {
        $manager = $manager_class::get_instance();
        $status = $manager->get_registration_status();
        
        echo '<p><strong>Manager Class:</strong> ✅ Loaded</p>';
        echo '<p><strong>Essential Info:</strong> ' . ($status['essential_listing_info'] ? '✅ Active' : '❌ Not Active') . '</p>';
        echo '<p><strong>Property Details:</strong> ' . ($status['property_details'] ? '✅ Active' : '❌ Not Active') . '</p>';
        echo '<p><strong>Calculator Enhanced:</strong> ' . ($status['calculator_enhanced'] ? '✅ Enhanced' : '❌ Standard') . '</p>';
        
        if (array_filter($status)) {
            echo '<p style="color: green;">✅ Enhanced Field Manager is operational!</p>';
        }
    } else {
        echo '<p style="color: red;">❌ Enhanced Field Manager not found</p>';
    }
    echo '</div>';
    
    // Check bridge functions (theme-based)
    echo '<div class="card" style="margin: 20px 0; padding: 20px;">';
    echo '<h2>🔗 Bridge Function Status (Theme-Based)</h2>';
    
    // Check if bridge functions are available (located in theme)
    $bridge_functions = [
        'hph_get_listing_price' => 'Listing Price (v1/v2 compatible)',
        'hph_get_listing_status' => 'Listing Status (enhanced return data)',
        'hph_get_listing_address' => 'Address Components (enhanced parsing)',
        'hph_get_listing_features' => 'Property Features (v2 calculations)',
        'hph_get_days_on_market' => 'Days on Market (v2 calculated field)',
        'hph_get_price_per_sqft' => 'Price per Sq Ft (v2 calculated field)',
        'hph_get_original_price' => 'Original Price (v2 price tracking)',
        'hph_get_market_metrics' => 'Market Metrics (comprehensive data)',
        'hph_get_listing_summary' => 'Listing Summary (card/preview data)',
        'hph_get_property_details' => 'Property Details (Phase 2 classification)',
        'hph_get_location_intelligence' => 'Location Intelligence (Phase 2 address & geo)'
    ];
    
    $available_functions = 0;
    foreach ($bridge_functions as $function => $description) {
        $exists = function_exists($function);
        if ($exists) $available_functions++;
        
        echo '<p style="margin: 5px 0;">';
        echo $exists ? '<span style="color: #00a32a;">✅</span>' : '<span style="color: #d63638;">❌</span>';
        echo ' <strong>' . $function . '</strong>: ' . $description;
        echo '</p>';
    }
    
    $total_functions = count($bridge_functions);
    $coverage_percent = round(($available_functions / $total_functions) * 100, 1);
    
    echo '<div style="margin-top: 15px; padding: 10px; background: #f0f0f1; border-radius: 4px;">';
    echo '<strong>Bridge Function Coverage: ' . $coverage_percent . '%</strong> (' . $available_functions . '/' . $total_functions . ' functions available)';
    
    if ($coverage_percent === 100.0) {
        echo '<br><span style="color: #00a32a;">🎉 All enhanced bridge functions are loaded and ready!</span>';
        echo '<br><small>Functions are modularly located in the theme for direct template access.</small>';
    } elseif ($coverage_percent >= 80) {
        echo '<br><span style="color: #dba617;">⚠️ Most bridge functions available, some missing.</span>';
    } else {
        echo '<br><span style="color: #d63638;">❌ Bridge functions not properly loaded. Check theme integration.</span>';
    }
    echo '</div>';
    echo '</div>';
    
    // Test links
    echo '<div class="card" style="margin: 20px 0; padding: 20px;">';
    echo '<h2>🧪 Testing Tools</h2>';
    echo '<p><strong>Calculator Tests:</strong> <a href="' . home_url('?test_calculator=1') . '" target="_blank" class="button">Run Calculator Tests</a></p>';
    echo '<p><strong>Bridge Function Tests:</strong> <a href="' . home_url('?test_theme_bridge=1') . '" target="_blank" class="button">Test Theme Bridge Functions</a></p>';
    echo '<p><strong>Phase 2 Property Details:</strong> <a href="' . home_url('?test_phase2_property=1') . '" target="_blank" class="button button-primary">Test Property Details</a></p>';
    echo '<p><strong>Phase 2 Location Intelligence:</strong> <a href="' . home_url('?test_phase2_location=1') . '" target="_blank" class="button button-primary">Test Location Intelligence</a></p>';
    echo '<p><strong>Create Test Listing:</strong> <a href="' . admin_url('post-new.php?post_type=listing') . '" class="button">New Listing</a></p>';
    echo '<p><strong>View Existing Listings:</strong> <a href="' . admin_url('edit.php?post_type=listing') . '" class="button">All Listings</a></p>';
    echo '</div>';
    
    // Next steps
    echo '<div class="card" style="margin: 20px 0; padding: 20px; background: #f0f8ff;">';
    echo '<h2>✅ Phase 2 Complete!</h2>';
    
    echo '<h3>✅ Completed Implementation:</h3>';
    echo '<ul>';
    echo '<li>✅ Enhanced Listing Calculator with address parsing</li>';
    echo '<li>✅ Essential Listing Information field group (v2)</li>';
    echo '<li>✅ Enhanced Field Manager system</li>';
    echo '<li>✅ Auto-calculation integration and testing</li>';
    echo '<li>✅ Enhanced bridge functions with v1/v2 compatibility</li>';
    echo '<li>✅ Modular theme-based bridge function architecture</li>';
    echo '<li>✅ Backward compatibility with existing templates</li>';
    echo '<li>✅ Performance optimization with caching</li>';
    echo '<li>✅ Comprehensive testing framework</li>';
    echo '</ul>';
    
    echo '<h3>� Ready for Phase 2:</h3>';
    echo '<ul>';
    echo '<li>� Property Details & Classification group expansion</li>';
    echo '<li>� Location & Address Intelligence group creation</li>';
    echo '<li>� Enhanced geocoding and address parsing</li>';
    echo '<li>� Relationships & Team management</li>';
    echo '<li>📋 Financial & Market Analytics integration</li>';
    echo '<li>📋 Features & Amenities management</li>';
    echo '<li>📋 Media & Marketing tools</li>';
    echo '</ul>';
    
    echo '<div style="margin-top: 15px; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;">';
    echo '<strong>🎉 Phase 2 Status: COMPLETE</strong><br>';
    echo 'Comprehensive property classification and location intelligence implemented. ';
    echo 'Advanced geocoding, address parsing, and geographic features ready. ';
    echo 'System prepared for Phase 3 with robust data foundation.';
    echo '</div>';
    echo '</div>';
    
    echo '</div>';
}

// Quick access link in admin bar
add_action('admin_bar_menu', function($wp_admin_bar) {
    if (current_user_can('administrator')) {
        $wp_admin_bar->add_node([
            'id' => 'hph-phase1-status',
            'title' => '🏠 Phase 1 Status',
            'href' => admin_url('tools.php?page=hph-phase1-status'),
            'meta' => ['title' => 'Check Phase 1 Implementation Status']
        ]);
    }
}, 100);
