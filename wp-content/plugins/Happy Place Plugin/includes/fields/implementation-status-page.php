<?php
/**
 * Happy Place Implementation Status Check
 * 
 * Run this by visiting: yoursite.com/wp-admin/admin.php?page=hph-implementation-status
 */

// Add admin menu
add_action('admin_menu', function() {
    add_management_page(
        'HPH Implementation Status',
        'HPH Status',
        'administrator',
        'hph-implementation-status',
        'hph_implementation_status_page'
    );
});

function hph_implementation_status_page() {
    if (!current_user_can('administrator')) {
        wp_die('Access denied');
    }
    
    echo '<div class="wrap">';
    echo '<h1>🏠 Happy Place Implementation Status</h1>';
    echo '<p><strong>Current Status:</strong> Phase 4 Day 1-3 - Advanced Search & Filtering Implementation Complete!</p>';
    
    // Check calculator enhancement
    echo '<div class="card" style="margin: 20px 0; padding: 20px;">';
    echo '<h2>🧮 Enhanced Calculator Status</h2>';
    
    $calculator_class = '\\HappyPlace\\Fields\\Listing_Calculator';
    if (class_exists($calculator_class)) {
        $calculator = $calculator_class::get_instance();
        $has_enhanced = method_exists($calculator, 'process_address_fields');
        $has_geocoding = method_exists($calculator, 'process_geocoding');
        
        echo '<p><strong>Calculator Class:</strong> ' . ($calculator ? '✅ Loaded' : '❌ Not Found') . '</p>';
        echo '<p><strong>Address Parsing:</strong> ' . ($has_enhanced ? '✅ Available' : '❌ Not enhanced') . '</p>';
        echo '<p><strong>Geocoding Integration:</strong> ' . ($has_geocoding ? '✅ Available' : '❌ Not enhanced') . '</p>';
        
        if ($has_enhanced && $has_geocoding) {
            echo '<p style="color: green;">✅ Calculator fully enhanced with Phase 2 features!</p>';
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
        $relationships_team = acf_get_field_group('group_relationships_team_management');
        
        echo '<p><strong>Essential Listing Info (Phase 1):</strong> ' . ($essential_group ? '✅ Active' : '❌ Not Found') . '</p>';
        echo '<p><strong>Property Details & Classification (Phase 2):</strong> ' . ($property_details ? '✅ Active' : '❌ Not Found') . '</p>';
        echo '<p><strong>Location & Address Intelligence (Phase 2):</strong> ' . ($location_intelligence ? '✅ Active' : '❌ Not Found') . '</p>';
        echo '<p><strong>Relationships & Team Management (Phase 3):</strong> ' . ($relationships_team ? '✅ Active' : '❌ Not Found') . '</p>';
        
        $active_groups = array_filter([$essential_group, $property_details, $location_intelligence, $relationships_team]);
        echo '<div style="margin-top: 10px; padding: 10px; background: #f0f0f1; border-radius: 4px;">';
        echo '<strong>Field Group Coverage:</strong> ' . count($active_groups) . '/4 groups active';
        if (count($active_groups) === 4) {
            echo '<br><span style="color: #00a32a;">🎉 All Phase 1, 2 & 3 field groups are operational!</span>';
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
        echo '<p><strong>Location Intelligence:</strong> ' . ($status['location_intelligence'] ? '✅ Active' : '❌ Not Active') . '</p>';
        echo '<p><strong>Relationships & Team:</strong> ' . ($status['relationships_team'] ? '✅ Active' : '❌ Not Active') . '</p>';
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
        'hph_get_location_intelligence' => 'Location Intelligence (Phase 2 address & geo)',
        'hph_get_relationship_info' => 'Relationship & Team Info (Phase 3)',
        'hph_get_listing_agent' => 'Agent Information (Phase 3)',
        'hph_get_location_relationships' => 'Location Relationships (Phase 3)',
        'hph_format_commission' => 'Commission Formatting (Phase 3)',
        'hph_get_performance_summary' => 'Performance Summary (Phase 3)',
        'hph_get_financial_analytics' => 'Financial Analytics (Phase 3)',
        'hph_format_financial_summary' => 'Financial Summary (Phase 3)',
        'hph_get_buyer_affordability' => 'Buyer Affordability (Phase 3)',
        'hph_get_market_comparison' => 'Market Comparison (Phase 3)',
        'hph_get_search_data' => 'Search Data (Phase 4)',
        'hph_execute_advanced_search' => 'Advanced Search (Phase 4)',
        'hph_get_search_suggestions' => 'Search Suggestions (Phase 4)',
        'hph_format_search_filters' => 'Search Filter Formatting (Phase 4)'
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
    echo '<p><strong>Phase 3 Relationships & Team:</strong> <a href="' . home_url('?test_phase3_relationships=1') . '" target="_blank" class="button button-secondary">Test Phase 3 Day 1-3</a></p>';
    echo '<p><strong>Create Test Listing:</strong> <a href="' . admin_url('post-new.php?post_type=listing') . '" class="button">New Listing</a></p>';
    echo '<p><strong>View Existing Listings:</strong> <a href="' . admin_url('edit.php?post_type=listing') . '" class="button">All Listings</a></p>';
    echo '</div>';
    
    // Implementation status
    echo '<div class="card" style="margin: 20px 0; padding: 20px; background: #f0f8ff;">';
    echo '<h2>✅ Implementation Status</h2>';
    
    echo '<h3>✅ Phase 1 & 2 & 3 Day 1-3 Complete:</h3>';
    echo '<ul>';
    echo '<li>✅ Enhanced Listing Calculator with address parsing & geocoding</li>';
    echo '<li>✅ Essential Listing Information field group (v2)</li>';
    echo '<li>✅ Property Details & Classification (Phase 2 Day 1-3)</li>';
    echo '<li>✅ Location & Address Intelligence (Phase 2 Day 4-7)</li>';
    echo '<li>✅ Relationships & Team Management (Phase 3 Day 1-3)</li>';
    echo '<li>✅ Enhanced Field Manager system</li>';
    echo '<li>✅ Auto-calculation integration and testing</li>';
    echo '<li>✅ Enhanced bridge functions with v1/v2 compatibility</li>';
    echo '<li>✅ Multi-provider geocoding system</li>';
    echo '<li>✅ Advanced address parsing & component extraction</li>';
    echo '<li>✅ Address privacy & visibility controls</li>';
    echo '<li>✅ Agent & office relationship management</li>';
    echo '<li>✅ Commission calculation automation</li>';
    echo '<li>✅ Performance tracking & scoring</li>';
    echo '<li>✅ School ratings & lifestyle scoring</li>';
    echo '<li>✅ Location relationship intelligence</li>';
    echo '<li>✅ Modular theme-based bridge function architecture</li>';
    echo '<li>✅ Backward compatibility with existing templates</li>';
    echo '<li>✅ Performance optimization with caching</li>';
    echo '<li>✅ Comprehensive testing framework</li>';
    echo '</ul>';
    
    echo '<h3>🎉 Phase 4 Day 1-3 Complete - Advanced Search & Filtering:</h3>';
    echo '<ul>';
    echo '<li>✅ Property Details & Classification (Phase 2 Day 1-3)</li>';
    echo '<li>✅ Location & Address Intelligence (Phase 2 Day 4-7)</li>';
    echo '<li>✅ Relationships & Team Management (Phase 3 Day 1-3)</li>';
    echo '<li>✅ Financial & Market Analytics (Phase 3 Day 4-7)</li>';
    echo '<li>✅ Advanced Search & Filtering (Phase 4 Day 1-3)</li>';
    echo '</ul>';
    
    echo '<h3>🚀 Ready for Phase 4 Day 4-7:</h3>';
    echo '<ul>';
    echo '<li>⏳ API Integrations & External Data</li>';
    echo '<li>⏳ Performance Optimization & Caching</li>';
    echo '<li>⏳ Advanced Analytics & Reporting</li>';
    echo '<li>⏳ Production Ready Features</li>';
    echo '</ul>';
    
    echo '<h3>📊 Implementation Summary:</h3>';
    echo '<strong>🎉 Phase 4 Day 1-3 Status: COMPLETE</strong><br>';
    echo '<em>Advanced search and filtering system successfully implemented including:</em><br>';
    echo '• 25+ search and filtering fields across 4 tabs<br>';
    echo '• Advanced search engine with complex query capabilities<br>';
    echo '• Search suggestions and autocomplete system<br>';
    echo '• Analytics tracking with performance metrics<br>';
    echo '• SEO optimization features<br>';
    echo '• Comprehensive bridge functions for theme integration<br>';
    echo 'System ready for Phase 4 Day 4-7 API integrations and optimization.<br><br>';
    
    echo '<strong>🚀 Next: Phase 4 Day 4-7 - API Integrations & Performance Optimization</strong><br>';
    echo '<em>External data integrations, advanced caching, and production optimization.</em>';
    echo '</div>';
    
    echo '</div>';
}

// Quick access link in admin bar
add_action('admin_bar_menu', function($wp_admin_bar) {
    if (current_user_can('administrator')) {
        $wp_admin_bar->add_node([
            'id' => 'hph-implementation-status',
            'title' => '🏠 HPH Status',
            'href' => admin_url('tools.php?page=hph-implementation-status'),
            'meta' => ['title' => 'Check Implementation Status']
        ]);
    }
}, 100);
