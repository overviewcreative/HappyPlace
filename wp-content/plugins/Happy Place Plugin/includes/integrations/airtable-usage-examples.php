<?php
/**
 * Airtable Integration Usage Examples
 * 
 * This file demonstrates how to use the Airtable sync features
 * and table setup functionality.
 */

// Don't run this file directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Example 1: Get Airtable Table Template
 */
function example_get_airtable_template() {
    $template = \HappyPlace\Integrations\Airtable_Two_Way_Sync::get_table_template();
    
    echo "<h3>Airtable Table Template</h3>";
    echo "<p>Table Name: " . esc_html($template['table_name']) . "</p>";
    echo "<p>Total Fields: " . count($template['fields']) . "</p>";
    
    echo "<h4>Required Fields:</h4>";
    echo "<ul>";
    foreach ($template['fields'] as $field) {
        if (isset($field['required']) && $field['required']) {
            echo "<li>" . esc_html($field['name']) . " (" . esc_html($field['type']) . ")</li>";
        }
    }
    echo "</ul>";
    
    return $template;
}

/**
 * Example 2: Test Airtable Connection
 */
function example_test_airtable_connection($base_id, $table_name = 'Real Estate Listings') {
    try {
        $sync = new \HappyPlace\Integrations\Airtable_Two_Way_Sync($base_id, $table_name);
        $result = $sync->test_api_connection();
        
        if ($result['success']) {
            echo "<div style='color: green;'>✓ Connection successful!</div>";
            echo "<p>Base ID: " . esc_html($result['base_id']) . "</p>";
            echo "<p>Table: " . esc_html($result['table_name']) . "</p>";
        } else {
            echo "<div style='color: red;'>✗ Connection failed:</div>";
            echo "<p>" . esc_html($result['message']) . "</p>";
        }
        
        return $result;
        
    } catch (Exception $e) {
        echo "<div style='color: red;'>Error: " . esc_html($e->getMessage()) . "</div>";
        return false;
    }
}

/**
 * Example 3: Validate Table Structure
 */
function example_validate_table_structure($base_id, $table_name = 'Real Estate Listings') {
    try {
        $sync = new \HappyPlace\Integrations\Airtable_Two_Way_Sync($base_id, $table_name);
        $validation = $sync->validate_table_structure();
        
        echo "<h3>Table Structure Validation</h3>";
        
        if ($validation['valid']) {
            echo "<div style='color: green;'>✓ Table structure is valid</div>";
        } else {
            echo "<div style='color: red;'>✗ Table structure has issues</div>";
        }
        
        if (!empty($validation['missing_fields'])) {
            echo "<h4>Missing Fields:</h4>";
            echo "<ul>";
            foreach ($validation['missing_fields'] as $field) {
                echo "<li style='color: red;'>" . esc_html($field) . "</li>";
            }
            echo "</ul>";
        }
        
        if (!empty($validation['extra_fields'])) {
            echo "<h4>Extra Fields (not synced):</h4>";
            echo "<ul>";
            foreach ($validation['extra_fields'] as $field) {
                echo "<li style='color: orange;'>" . esc_html($field) . "</li>";
            }
            echo "</ul>";
        }
        
        if (!empty($validation['recommendations'])) {
            echo "<h4>Recommendations:</h4>";
            echo "<ul>";
            foreach ($validation['recommendations'] as $rec) {
                echo "<li>" . esc_html($rec) . "</li>";
            }
            echo "</ul>";
        }
        
        return $validation;
        
    } catch (Exception $e) {
        echo "<div style='color: red;'>Error: " . esc_html($e->getMessage()) . "</div>";
        return false;
    }
}

/**
 * Example 4: Perform Full Sync
 */
function example_full_sync($base_id, $table_name = 'Real Estate Listings') {
    echo "<h3>Running Full Airtable Sync</h3>";
    
    $results = hph_trigger_airtable_sync($base_id, $table_name);
    
    if ($results['success']) {
        echo "<div style='color: green;'>✓ Sync completed successfully</div>";
        
        // Airtable to WordPress results
        $airtable_to_wp = $results['airtable_to_wp'];
        echo "<h4>Airtable → WordPress:</h4>";
        echo "<ul>";
        echo "<li>Total Records: " . esc_html($airtable_to_wp['total_records']) . "</li>";
        echo "<li>Created: " . esc_html($airtable_to_wp['stats']['created']) . "</li>";
        echo "<li>Updated: " . esc_html($airtable_to_wp['stats']['updated']) . "</li>";
        echo "<li>Skipped: " . esc_html($airtable_to_wp['stats']['skipped']) . "</li>";
        echo "<li>Errors: " . esc_html($airtable_to_wp['stats']['errors']) . "</li>";
        echo "</ul>";
        
        // WordPress to Airtable results
        $wp_to_airtable = $results['wp_to_airtable'];
        echo "<h4>WordPress → Airtable:</h4>";
        echo "<ul>";
        echo "<li>Total Records: " . esc_html($wp_to_airtable['total_records']) . "</li>";
        echo "<li>Created: " . esc_html($wp_to_airtable['stats']['created']) . "</li>";
        echo "<li>Updated: " . esc_html($wp_to_airtable['stats']['updated']) . "</li>";
        echo "<li>Skipped: " . esc_html($wp_to_airtable['stats']['skipped']) . "</li>";
        echo "<li>Errors: " . esc_html($wp_to_airtable['stats']['errors']) . "</li>";
        echo "</ul>";
        
        // Show validation errors if any
        if (!empty($airtable_to_wp['validation_errors'])) {
            echo "<h4>Validation Errors (Airtable → WordPress):</h4>";
            echo "<pre>" . esc_html(print_r($airtable_to_wp['validation_errors'], true)) . "</pre>";
        }
        
    } else {
        echo "<div style='color: red;'>✗ Sync failed:</div>";
        echo "<p>" . esc_html($results['error']) . "</p>";
    }
    
    return $results;
}

/**
 * Example 5: Display Setup Instructions
 */
function example_show_setup_instructions() {
    require_once plugin_dir_path(__FILE__) . 'class-airtable-setup-helper.php';
    echo \HappyPlace\Integrations\Airtable_Setup_Helper::get_setup_instructions_html();
}

/**
 * Example 6: JavaScript for Admin Interface
 */
function example_admin_javascript() {
    ?>
    <script>
    // Test Airtable Connection
    function testAirtableConnection() {
        const baseId = document.getElementById('airtable_base_id').value;
        const tableName = document.getElementById('airtable_table_name').value || 'Real Estate Listings';
        
        if (!baseId) {
            alert('Please enter a Base ID');
            return;
        }
        
        jQuery.post(ajaxurl, {
            action: 'hph_test_airtable_connection',
            nonce: '<?php echo wp_create_nonce('happy_place_admin_nonce'); ?>',
            base_id: baseId,
            table_name: tableName
        }, function(response) {
            if (response.success) {
                alert('✓ Connection successful!\n' + response.message);
            } else {
                alert('✗ Connection failed:\n' + response.message);
            }
        }).fail(function() {
            alert('Request failed. Check your network connection.');
        });
    }
    
    // Download Template
    function downloadAirtableTemplate() {
        window.location.href = ajaxurl + '?action=hph_download_airtable_template&nonce=<?php echo wp_create_nonce('happy_place_admin_nonce'); ?>';
    }
    
    // Get Template Structure
    function getAirtableTemplate() {
        jQuery.post(ajaxurl, {
            action: 'hph_get_airtable_template',
            nonce: '<?php echo wp_create_nonce('happy_place_admin_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                console.log('Airtable Template:', response.template);
                alert('Template loaded! Check console for details.');
            }
        });
    }
    
    // Validate Table Structure
    function validateAirtableTable() {
        const baseId = document.getElementById('airtable_base_id').value;
        const tableName = document.getElementById('airtable_table_name').value || 'Real Estate Listings';
        
        if (!baseId) {
            alert('Please enter a Base ID');
            return;
        }
        
        jQuery.post(ajaxurl, {
            action: 'hph_validate_airtable_table',
            nonce: '<?php echo wp_create_nonce('happy_place_admin_nonce'); ?>',
            base_id: baseId,
            table_name: tableName
        }, function(response) {
            if (response.success) {
                const validation = response.validation;
                let message = validation.valid ? 
                    '✓ Table structure is valid' : 
                    '✗ Table structure has issues';
                
                if (validation.missing_fields && validation.missing_fields.length > 0) {
                    message += '\n\nMissing fields: ' + validation.missing_fields.join(', ');
                }
                
                if (validation.recommendations && validation.recommendations.length > 0) {
                    message += '\n\nRecommendations:\n' + validation.recommendations.join('\n');
                }
                
                alert(message);
            } else {
                alert('Validation failed: ' + response.message);
            }
        });
    }
    
    // Manual Sync
    function runManualSync() {
        if (!confirm('Run manual sync? This may take a few moments.')) {
            return;
        }
        
        const baseId = document.getElementById('airtable_base_id').value;
        const tableName = document.getElementById('airtable_table_name').value || 'Real Estate Listings';
        
        jQuery.post(ajaxurl, {
            action: 'hph_airtable_sync',
            nonce: '<?php echo wp_create_nonce('happy_place_admin_nonce'); ?>',
            base_id: baseId,
            table_name: tableName
        }, function(response) {
            if (response.success) {
                let message = '✓ Sync completed!\n\n';
                message += 'Airtable → WordPress:\n';
                message += '  Created: ' + response.airtable_to_wp.stats.created + '\n';
                message += '  Updated: ' + response.airtable_to_wp.stats.updated + '\n';
                message += '  Errors: ' + response.airtable_to_wp.stats.errors + '\n\n';
                message += 'WordPress → Airtable:\n';
                message += '  Created: ' + response.wp_to_airtable.stats.created + '\n';
                message += '  Updated: ' + response.wp_to_airtable.stats.updated + '\n';
                message += '  Errors: ' + response.wp_to_airtable.stats.errors;
                
                alert(message);
            } else {
                alert('Sync failed: ' + response.error);
            }
        });
    }
    </script>
    <?php
}

// Usage in admin pages:
// add_action('admin_footer', 'example_admin_javascript');
