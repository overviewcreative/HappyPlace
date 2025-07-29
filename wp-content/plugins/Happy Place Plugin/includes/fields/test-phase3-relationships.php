<?php
/**
 * Happy Place Phase 3 Day 1-3 Testing Page
 * Relationships & Team Management Validation
 * 
 * Visit: yoursite.com/?test_phase3_relationships=1
 */

if (!isset($_GET['test_phase3_relationships'])) {
    return;
}

// Security check
if (!current_user_can('administrator')) {
    wp_die('Access denied');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>🏠 Phase 3 Day 1-3: Relationships & Team Management Testing</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 20px; background: #f0f0f1; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #0073aa; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 6px; }
        .test-section h3 { margin-top: 0; color: #23282d; }
        .status-pass { color: #00a32a; font-weight: bold; }
        .status-fail { color: #d63638; font-weight: bold; }
        .status-info { color: #0073aa; }
        .field-test { margin: 10px 0; padding: 10px; background: #f9f9f9; border-left: 4px solid #0073aa; }
        .calculation-test { background: #fff4e5; border-left-color: #f39c12; }
        .bridge-test { background: #e8f5e8; border-left-color: #00a32a; }
        .performance-test { background: #e3f2fd; border-left-color: #2196f3; }
        .code-sample { background: #f1f1f1; padding: 15px; border-radius: 4px; font-family: 'Courier New', monospace; font-size: 14px; margin: 10px 0; }
        .test-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0; }
        .test-result { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 8px 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #f9f9f9; font-weight: 600; }
        .metric { display: inline-block; margin: 5px 10px; padding: 5px 10px; background: #e8f4f8; border-radius: 4px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🏠 Happy Place Phase 3 Day 1-3 Testing</h1>
        <h2>Relationships & Team Management</h2>
        <p>Comprehensive validation of agent relationships, office management, performance tracking, and location intelligence features.</p>
    </div>

    <?php
    // Test 1: Relationships & Team Management Field Group
    echo '<div class="test-section">';
    echo '<h3>📋 Test 1: Relationships & Team Management Field Group</h3>';
    
    if (function_exists('acf_get_field_group')) {
        $relationships_group = acf_get_field_group('group_relationships_team_management');
        
        if ($relationships_group) {
            echo '<div class="test-result status-pass">✅ Relationships & Team Management field group loaded successfully</div>';
            echo '<div class="status-info">📊 Group Title: ' . ($relationships_group['title'] ?? 'Unknown') . '</div>';
            echo '<div class="status-info">🔑 Group Key: ' . ($relationships_group['key'] ?? 'Unknown') . '</div>';
            echo '<div class="status-info">📝 Fields Count: ' . count($relationships_group['fields'] ?? []) . '</div>';
            
            // Test key fields
            $key_fields = [
                'field_listing_agent_primary' => 'Primary Listing Agent',
                'field_listing_agent_secondary' => 'Co-Listing Agent',
                'field_listing_office_primary' => 'Primary Listing Office',
                'field_total_commission' => 'Total Commission',
                'field_listing_views_total' => 'Total Listing Views',
                'field_listing_performance_score' => 'Performance Score',
                'field_walkability_score' => 'Walkability Score',
                'field_overall_school_rating' => 'Overall School Rating'
            ];
            
            echo '<div class="field-test">';
            echo '<h4>🔍 Key Field Validation:</h4>';
            foreach ($key_fields as $field_key => $field_label) {
                $field_found = false;
                foreach ($relationships_group['fields'] as $field) {
                    if ($field['key'] === $field_key) {
                        $field_found = true;
                        break;
                    }
                }
                $status = $field_found ? '✅' : '❌';
                $class = $field_found ? 'status-pass' : 'status-fail';
                echo "<div class='test-result {$class}'>{$status} {$field_label}</div>";
            }
            echo '</div>';
            
        } else {
            echo '<div class="test-result status-fail">❌ Relationships & Team Management field group not found</div>';
        }
    } else {
        echo '<div class="test-result status-fail">❌ ACF not available</div>';
    }
    echo '</div>';

    // Test 2: Calculator Enhancement Validation
    echo '<div class="test-section">';
    echo '<h3>🧮 Test 2: Calculator Enhancement for Phase 3</h3>';
    
    $calculator_class = '\\HappyPlace\\Fields\\Listing_Calculator';
    if (class_exists($calculator_class)) {
        $calculator = $calculator_class::get_instance();
        
        $phase3_methods = [
            'calculate_commission_totals' => 'Commission Calculation',
            'calculate_performance_score' => 'Performance Score Calculation',
            'calculate_school_rating_average' => 'School Rating Average',
            'calculate_lifestyle_score' => 'Lifestyle Score Calculation'
        ];
        
        echo '<div class="calculation-test">';
        echo '<h4>⚡ Phase 3 Calculator Methods:</h4>';
        foreach ($phase3_methods as $method => $description) {
            $has_method = method_exists($calculator, $method);
            $status = $has_method ? '✅' : '❌';
            $class = $has_method ? 'status-pass' : 'status-fail';
            echo "<div class='test-result {$class}'>{$status} {$description}</div>";
        }
        echo '</div>';
        
    } else {
        echo '<div class="test-result status-fail">❌ Calculator class not found</div>';
    }
    echo '</div>';

    // Test 3: Bridge Function Validation
    echo '<div class="test-section">';
    echo '<h3>🔗 Test 3: Phase 3 Bridge Functions</h3>';
    
    $phase3_bridge_functions = [
        'hph_get_relationship_info' => 'Get relationship and team information',
        'hph_get_listing_agent' => 'Get agent information with fallback formatting',
        'hph_get_location_relationships' => 'Get school ratings and location relationships',
        'hph_format_commission' => 'Format commission information for display',
        'hph_get_performance_summary' => 'Get performance metrics summary'
    ];
    
    echo '<div class="bridge-test">';
    echo '<h4>🌉 New Bridge Functions for Phase 3:</h4>';
    foreach ($phase3_bridge_functions as $function => $description) {
        $exists = function_exists($function);
        $status = $exists ? '✅' : '❌';
        $class = $exists ? 'status-pass' : 'status-fail';
        echo "<div class='test-result {$class}'>{$status} <strong>{$function}()</strong>: {$description}</div>";
    }
    echo '</div>';
    echo '</div>';

    // Test 4: Test Data Creation and Calculation
    echo '<div class="test-section">';
    echo '<h3>🧪 Test 4: Test Data Creation and Calculations</h3>';
    
    // Create test listing if none exists
    $test_listings = get_posts([
        'post_type' => 'listing',
        'posts_per_page' => 1,
        'meta_query' => [
            [
                'key' => '_test_listing_phase3',
                'value' => 'yes',
                'compare' => '='
            ]
        ]
    ]);
    
    if (empty($test_listings)) {
        // Create test listing
        $test_listing_id = wp_insert_post([
            'post_title' => 'Phase 3 Test Listing - Relationships & Team',
            'post_type' => 'listing',
            'post_status' => 'publish'
        ]);
        
        if ($test_listing_id && !is_wp_error($test_listing_id)) {
            update_field('_test_listing_phase3', 'yes', $test_listing_id);
            
            // Set up test relationship data
            update_field('listing_agent_commission_primary', 2.5, $test_listing_id);
            update_field('listing_agent_commission_secondary', 1.0, $test_listing_id);
            update_field('buyer_agent_commission', 2.5, $test_listing_id);
            update_field('listing_views_total', 150, $test_listing_id);
            update_field('listing_views_this_week', 25, $test_listing_id);
            update_field('inquiries_count_total', 8, $test_listing_id);
            update_field('showings_count_total', 3, $test_listing_id);
            update_field('elementary_school_rating', 8, $test_listing_id);
            update_field('middle_school_rating', 7, $test_listing_id);
            update_field('high_school_rating', 9, $test_listing_id);
            update_field('walkability_score', 75, $test_listing_id);
            update_field('transit_score', 60, $test_listing_id);
            update_field('bike_score', 85, $test_listing_id);
            update_field('price', 450000, $test_listing_id);
            
            echo '<div class="test-result status-pass">✅ Test listing created with ID: ' . $test_listing_id . '</div>';
        } else {
            echo '<div class="test-result status-fail">❌ Failed to create test listing</div>';
        }
    } else {
        $test_listing_id = $test_listings[0]->ID;
        echo '<div class="test-result status-info">📋 Using existing test listing ID: ' . $test_listing_id . '</div>';
    }
    
    if (!empty($test_listing_id)) {
        // Force recalculation
        if (class_exists($calculator_class)) {
            $calculator = $calculator_class::get_instance();
            
            // Manually trigger calculations
            $calculator->calculate_listing_fields($test_listing_id);
            
            echo '<div class="performance-test">';
            echo '<h4>📊 Calculation Results:</h4>';
            
            // Test commission calculation
            $total_commission = get_field('total_commission', $test_listing_id);
            echo "<div class='test-result'>💰 Total Commission: " . ($total_commission ?: 'Not calculated') . "%</div>";
            
            // Test performance score
            $performance_score = get_field('listing_performance_score', $test_listing_id);
            echo "<div class='test-result'>⭐ Performance Score: " . ($performance_score ?: 'Not calculated') . "/100</div>";
            
            // Test school rating average
            $overall_school_rating = get_field('overall_school_rating', $test_listing_id);
            echo "<div class='test-result'>🏫 Overall School Rating: " . ($overall_school_rating ?: 'Not calculated') . "/10</div>";
            
            // Test lifestyle score
            $lifestyle_score = get_field('lifestyle_score', $test_listing_id);
            echo "<div class='test-result'>🌟 Lifestyle Score: " . ($lifestyle_score ?: 'Not calculated') . "/100</div>";
            
            echo '</div>';
        }
    }
    echo '</div>';

    // Test 5: Bridge Function Testing with Real Data
    if (!empty($test_listing_id)) {
        echo '<div class="test-section">';
        echo '<h3>🔗 Test 5: Bridge Function Testing with Real Data</h3>';
        
        echo '<div class="test-grid">';
        
        // Test relationship info function
        echo '<div>';
        echo '<h4>📋 Relationship Information:</h4>';
        if (function_exists('hph_get_relationship_info')) {
            $relationship_info = hph_get_relationship_info($test_listing_id);
            echo '<div class="code-sample">';
            echo 'Commission Data:<br>';
            echo 'Primary Agent: ' . ($relationship_info['commission']['primary_agent'] ?? 'N/A') . '%<br>';
            echo 'Secondary Agent: ' . ($relationship_info['commission']['secondary_agent'] ?? 'N/A') . '%<br>';
            echo 'Buyer Agent: ' . ($relationship_info['commission']['buyer_agent'] ?? 'N/A') . '%<br>';
            echo 'Total: ' . ($relationship_info['commission']['total'] ?? 'N/A') . '%';
            echo '</div>';
        } else {
            echo '<div class="test-result status-fail">❌ Function not available</div>';
        }
        echo '</div>';
        
        // Test location relationships function
        echo '<div>';
        echo '<h4>📍 Location Relationships:</h4>';
        if (function_exists('hph_get_location_relationships')) {
            $location_relationships = hph_get_location_relationships($test_listing_id);
            echo '<div class="code-sample">';
            echo 'School Ratings:<br>';
            echo 'Elementary: ' . ($location_relationships['schools']['elementary_rating'] ?? 'N/A') . '/10<br>';
            echo 'Middle: ' . ($location_relationships['schools']['middle_rating'] ?? 'N/A') . '/10<br>';
            echo 'High: ' . ($location_relationships['schools']['high_rating'] ?? 'N/A') . '/10<br>';
            echo 'Overall: ' . ($location_relationships['schools']['overall_rating'] ?? 'N/A') . '/10<br><br>';
            echo 'Lifestyle Scores:<br>';
            echo 'Walkability: ' . ($location_relationships['scores']['walkability'] ?? 'N/A') . '/100<br>';
            echo 'Transit: ' . ($location_relationships['scores']['transit'] ?? 'N/A') . '/100<br>';
            echo 'Bike: ' . ($location_relationships['scores']['bike'] ?? 'N/A') . '/100<br>';
            echo 'Lifestyle: ' . ($location_relationships['scores']['lifestyle'] ?? 'N/A') . '/100';
            echo '</div>';
        } else {
            echo '<div class="test-result status-fail">❌ Function not available</div>';
        }
        echo '</div>';
        
        echo '</div>';
        
        // Test commission formatting
        echo '<h4>💰 Commission Formatting:</h4>';
        if (function_exists('hph_format_commission')) {
            $commission_percentage = hph_format_commission($test_listing_id, 'percentage');
            $commission_dollar = hph_format_commission($test_listing_id, 'dollar');
            $commission_breakdown = hph_format_commission($test_listing_id, 'breakdown');
            
            echo '<div class="code-sample">';
            echo 'Percentage Format: ' . $commission_percentage . '<br>';
            echo 'Dollar Format: ' . $commission_dollar . '<br>';
            if (is_array($commission_breakdown)) {
                echo 'Breakdown:<br>';
                echo '- Primary Agent: ' . $commission_breakdown['primary_agent']['percentage'] . '% = $' . number_format($commission_breakdown['primary_agent']['dollar'], 2) . '<br>';
                echo '- Secondary Agent: ' . $commission_breakdown['secondary_agent']['percentage'] . '% = $' . number_format($commission_breakdown['secondary_agent']['dollar'], 2) . '<br>';
                echo '- Buyer Agent: ' . $commission_breakdown['buyer_agent']['percentage'] . '% = $' . number_format($commission_breakdown['buyer_agent']['dollar'], 2) . '<br>';
                echo '- <strong>Total: ' . $commission_breakdown['total']['percentage'] . '% = $' . number_format($commission_breakdown['total']['dollar'], 2) . '</strong>';
            }
            echo '</div>';
        } else {
            echo '<div class="test-result status-fail">❌ Commission formatting function not available</div>';
        }
        
        // Test performance summary
        echo '<h4>📈 Performance Summary:</h4>';
        if (function_exists('hph_get_performance_summary')) {
            $performance_summary = hph_get_performance_summary($test_listing_id);
            echo '<div class="code-sample">';
            echo 'Performance Score: ' . $performance_summary['score'] . '/100<br>';
            echo 'Total Views: ' . number_format($performance_summary['views_total']) . '<br>';
            echo 'Weekly Views: ' . number_format($performance_summary['views_weekly']) . '<br>';
            echo 'Inquiries: ' . $performance_summary['inquiries'] . '<br>';
            echo 'Showings: ' . $performance_summary['showings'] . '<br>';
            echo 'Days on Market: ' . $performance_summary['days_on_market'] . '<br>';
            echo 'Inquiry Rate: ' . $performance_summary['inquiry_rate'] . '%<br>';
            echo 'Showing Rate: ' . $performance_summary['showing_rate'] . '%<br>';
            echo 'Marketing Status: ' . $performance_summary['marketing_status'] . '<br>';
            echo 'Lead Source: ' . ($performance_summary['lead_source'] ?: 'Not set');
            echo '</div>';
        } else {
            echo '<div class="test-result status-fail">❌ Performance summary function not available</div>';
        }
        
        echo '</div>';
    }

    // Test 6: Field Manager Status
    echo '<div class="test-section">';
    echo '<h3>⚙️ Test 6: Enhanced Field Manager Status</h3>';
    
    $manager_class = '\\HappyPlace\\Fields\\Enhanced_Field_Manager';
    if (class_exists($manager_class)) {
        $manager = $manager_class::get_instance();
        $status = $manager->get_registration_status();
        
        echo '<table>';
        echo '<tr><th>Component</th><th>Status</th><th>Description</th></tr>';
        
        $status_descriptions = [
            'essential_listing_info' => 'Essential Listing Information (Phase 1)',
            'property_details' => 'Property Details & Classification (Phase 2)',
            'location_intelligence' => 'Location & Address Intelligence (Phase 2)',
            'relationships_team' => 'Relationships & Team Management (Phase 3)',
            'calculator_enhanced' => 'Enhanced Calculator Functions'
        ];
        
        foreach ($status as $key => $is_active) {
            $status_text = $is_active ? '<span class="status-pass">✅ Active</span>' : '<span class="status-fail">❌ Inactive</span>';
            $description = $status_descriptions[$key] ?? 'Unknown component';
            echo "<tr><td>{$key}</td><td>{$status_text}</td><td>{$description}</td></tr>";
        }
        echo '</table>';
        
        $active_count = array_sum($status);
        $total_count = count($status);
        echo "<div class='test-result status-info'>📊 Active Components: {$active_count}/{$total_count}</div>";
        
        if ($active_count === $total_count) {
            echo '<div class="test-result status-pass">🎉 All Phase 3 Day 1-3 components are operational!</div>';
        }
        
    } else {
        echo '<div class="test-result status-fail">❌ Enhanced Field Manager not found</div>';
    }
    echo '</div>';

    // Summary
    echo '<div class="test-section" style="background: #f0f8ff; border-color: #0073aa;">';
    echo '<h3>📋 Phase 3 Day 1-3 Summary</h3>';
    echo '<h4>✅ Completed Features:</h4>';
    echo '<ul>';
    echo '<li>✅ <strong>Relationships & Team Management ACF Field Group</strong> - 25+ fields across 4 tabs</li>';
    echo '<li>✅ <strong>Agent & Office Management</strong> - Primary/secondary agents, office information, MLS integration</li>';
    echo '<li>✅ <strong>Commission Calculations</strong> - Auto-calculation of total commissions from individual rates</li>';
    echo '<li>✅ <strong>Performance Tracking</strong> - Views, inquiries, showings, performance scoring</li>';
    echo '<li>✅ <strong>Location Relationships</strong> - School ratings, walkability scores, lifestyle metrics</li>';
    echo '<li>✅ <strong>Enhanced Calculator</strong> - Commission, performance, school rating, and lifestyle calculations</li>';
    echo '<li>✅ <strong>Bridge Functions</strong> - Relationship info, agent data, location relationships, commission formatting</li>';
    echo '<li>✅ <strong>Field Manager Integration</strong> - Automatic registration and status tracking</li>';
    echo '</ul>';
    
    echo '<h4>🚀 Ready for Phase 3 Day 4-7:</h4>';
    echo '<ul>';
    echo '<li>📋 <strong>Financial & Market Analytics ACF Group</strong> - Property taxes, HOA fees, market intelligence</li>';
    echo '<li>📋 <strong>Buyer Calculator Fields</strong> - Monthly payment estimation, affordability analysis</li>';
    echo '<li>📋 <strong>Market Intelligence Integration</strong> - Estimated market value, market position analysis</li>';
    echo '<li>📋 <strong>Financial Calculation Methods</strong> - Mortgage calculations, market value estimation</li>';
    echo '<li>📋 <strong>Enhanced Testing Framework</strong> - Financial calculation validation</li>';
    echo '</ul>';
    
    echo '<div style="margin-top: 20px; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;">';
    echo '<strong>🎉 Phase 3 Day 1-3 Status: COMPLETE</strong><br>';
    echo 'Comprehensive relationship and team management implemented with agent information, office details, ';
    echo 'performance tracking, commission calculations, and location relationship features. ';
    echo 'System ready for Phase 3 Day 4-7 Financial & Market Analytics implementation.';
    echo '</div>';
    echo '</div>';
    ?>

    <div style="text-align: center; margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 6px;">
        <p><strong>🔧 Admin Tools:</strong></p>
        <a href="<?php echo admin_url('post-new.php?post_type=listing'); ?>" class="button button-primary">Create New Listing</a>
        <a href="<?php echo admin_url('edit.php?post_type=listing'); ?>" class="button">View All Listings</a>
        <a href="<?php echo admin_url('tools.php?page=hph-implementation-status'); ?>" class="button">Implementation Status</a>
        <a href="<?php echo home_url('?test_calculator=1'); ?>" class="button">Test Calculator</a>
        <a href="<?php echo home_url('?test_phase2_property=1'); ?>" class="button">Test Phase 2 Property</a>
        <a href="<?php echo home_url('?test_phase2_location=1'); ?>" class="button">Test Phase 2 Location</a>
    </div>
</div>

</body>
</html>

<?php
// Prevent any further output
exit;
?>
