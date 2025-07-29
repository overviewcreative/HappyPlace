<?php
/**
 * Enhanced ACF Field Group Manager
 * 
 * Loads the new v2 field groups for Phase 1 implementation
 * 
 * Save this as: wp-content/plugins/Happy Place Plugin/includes/fields/class-enhanced-field-manager.php
 */

namespace HappyPlace\Fields;

if (!defined('ABSPATH')) {
    exit;
}

class Enhanced_Field_Manager
{
    private static ?self $instance = null;

    public static function get_instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        // Register field groups on ACF init
        add_action('acf/init', [$this, 'register_enhanced_field_groups']);
        
        // Add custom CSS for readonly fields
        add_action('admin_head', [$this, 'add_readonly_field_styles']);
        
        // Handle field group imports
        add_action('init', [$this, 'maybe_import_field_groups']);
    }

    /**
     * Register enhanced field groups
     */
    public function register_enhanced_field_groups(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        // Phase 1: Load Essential Listing Information v2
        $this->load_direct_json_group('group-essential-listing-info-v2.json');
        
        // Phase 1: Load Property Details v2
        $this->load_direct_json_group('group-property-details-v2.json');
        
        // Phase 2: Load Property Details Classification
        $this->load_direct_json_group('group_property_details_classification.json');
        
        // Phase 2: Load Location & Address Intelligence
        $this->load_direct_json_group('group_location_address_intelligence.json');
        
        // Phase 3: Load Relationships & Team Management
        $this->load_direct_json_group('group_relationships_team_management.json');
        
        // Phase 3 Day 4-7: Load Financial & Market Analytics
        $this->load_direct_json_group('group_financial_market_analytics.json');
        
        // Phase 4 Day 1-3: Load Advanced Search & Filtering
        $this->load_direct_json_group('group_advanced_search_filtering.json');
        
        // Phase 4 Day 4-7: Load API Integrations & Performance Optimization
        $this->load_direct_json_group('group_api_integrations_performance.json');
    }

    /**
     * Load field group directly from JSON file in current directory
     * Used for all Phase field groups
     */
    private function load_direct_json_group(string $filename): void
    {
        $json_file = plugin_dir_path(__FILE__) . $filename;
        
        if (file_exists($json_file)) {
            $json_data = file_get_contents($json_file);
            $field_group = json_decode($json_data, true);
            
            if (is_array($field_group) && isset($field_group['key'])) {
                acf_add_local_field_group($field_group);
                
                // Log successful registration
                error_log("✅ Field Group Registered: {$field_group['title']}");
            }
        } else {
            error_log("❌ Field group file not found: {$json_file}");
        }
    }

    /**
     * Add custom styles for readonly fields
     */
    public function add_readonly_field_styles(): void
    {
        if (get_current_screen()->post_type !== 'listing') {
            return;
        }
        ?>
        <style>
        .acf-readonly .acf-input input[type="text"],
        .acf-readonly .acf-input input[type="number"],
        .acf-readonly .acf-input input[type="date"],
        .acf-readonly .acf-input textarea,
        .acf-readonly .acf-input select {
            background-color: #f9f9f9 !important;
            color: #666 !important;
            border-color: #ddd !important;
            cursor: not-allowed !important;
        }
        
        .acf-readonly .acf-label {
            color: #666;
        }
        
        .acf-readonly .acf-label::after {
            content: " (Auto-calculated)";
            font-size: 11px;
            color: #999;
            font-weight: normal;
            font-style: italic;
        }
        
        /* Calculator status indicators */
        .acf-field[data-name="price_per_sqft"] .acf-label::before,
        .acf-field[data-name="bathrooms_total"] .acf-label::before,
        .acf-field[data-name="days_on_market"] .acf-label::before,
        .acf-field[data-name="lot_sqft"] .acf-label::before {
            content: "🧮 ";
            font-size: 12px;
        }
        
        /* Address parsing indicators */
        .acf-field[data-name="street_number"] .acf-label::before,
        .acf-field[data-name="street_name"] .acf-label::before,
        .acf-field[data-name="street_suffix"] .acf-label::before,
        .acf-field[data-name="unparsed_address"] .acf-label::before {
            content: "📍 ";
            font-size: 12px;
        }
        
        /* Status tracking indicators */
        .acf-field[data-name="status_change_date"] .acf-label::before,
        .acf-field[data-name="price_change_count"] .acf-label::before,
        .acf-field[data-name="original_price"] .acf-label::before {
            content: "📊 ";
            font-size: 12px;
        }
        
        /* Enhanced tab styling */
        .acf-tab-wrap .acf-tab-group li.active a {
            background: #0073aa;
            color: white;
        }
        
        .acf-fields > .acf-tab-wrap {
            margin-bottom: 20px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Add calculator status to fields
            function addCalculatorStatus() {
                // Add status indicators for calculated fields
                $('[data-name="price_per_sqft"] .acf-input').append('<small style="color: #666; font-style: italic;">Updates when price or square footage changes</small>');
                $('[data-name="bathrooms_total"] .acf-input').append('<small style="color: #666; font-style: italic;">Updates when full or half bathrooms change</small>');
                $('[data-name="days_on_market"] .acf-input').append('<small style="color: #666; font-style: italic;">Updates daily based on list date</small>');
                $('[data-name="lot_sqft"] .acf-input').append('<small style="color: #666; font-style: italic;">Updates when lot size changes</small>');
                
                // Make calculated fields truly readonly
                $('.acf-readonly input, .acf-readonly select, .acf-readonly textarea').attr('readonly', true).attr('disabled', false);
            }
            
            // Run on page load and ACF field updates
            addCalculatorStatus();
            $(document).on('acf/setup_fields', addCalculatorStatus);
            
            // Show calculator working indicator
            function showCalculatorWorking() {
                const $indicator = $('<div class="calculator-working" style="position: fixed; top: 32px; right: 20px; background: #0073aa; color: white; padding: 10px 15px; border-radius: 4px; z-index: 9999; font-size: 12px;"><span class="dashicons dashicons-update" style="animation: rotation 1s infinite linear; margin-right: 5px;"></span>Calculator working...</div>');
                $('body').append($indicator);
                
                setTimeout(function() {
                    $indicator.fadeOut(500, function() {
                        $(this).remove();
                    });
                }, 2000);
            }
            
            // Trigger calculator working indicator on key field changes
            $('[data-name="price"] input, [data-name="square_footage"] input, [data-name="bathrooms_full"] input, [data-name="bathrooms_half"] input, [data-name="lot_size"] input, [data-name="street_address"] input').on('change', function() {
                showCalculatorWorking();
            });
        });
        </script>
        
        <style>
        @keyframes rotation {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(359deg);
            }
        }
        </style>
        <?php
    }

    /**
     * Import field groups from JSON files if needed
     */
    public function maybe_import_field_groups(): void
    {
        // Only run for admins and if ACF is available
        if (!current_user_can('administrator') || !function_exists('acf_import_field_group')) {
            return;
        }

        // Check if we need to import (add a transient to prevent repeated imports)
        $import_check = get_transient('hph_field_groups_imported_v3');
        if ($import_check) {
            return;
        }

        // Import the field groups using the new naming convention
        $this->import_field_group_from_json('group-essential-listing-info-v2.json');
        $this->import_field_group_from_json('group-property-details-v2.json');
        $this->import_field_group_from_json('group_property_details_classification.json');
        $this->import_field_group_from_json('group_location_address_intelligence.json');

        // Set transient to prevent repeated imports
        set_transient('hph_field_groups_imported_v3', true, WEEK_IN_SECONDS);
        
        error_log("✅ All field groups imported successfully");
    }

    /**
     * Import individual field group from JSON
     */
    private function import_field_group_from_json(string $filename): bool
    {
        $json_file = plugin_dir_path(__FILE__) . $filename;
        
        if (!file_exists($json_file)) {
            error_log("❌ Import failed - file not found: {$json_file}");
            return false;
        }

        $json_data = file_get_contents($json_file);
        $field_group = json_decode($json_data, true);
        
        if (!is_array($field_group) || !isset($field_group['key'])) {
            error_log("❌ Import failed - invalid JSON: {$filename}");
            return false;
        }

        // Check if field group already exists
        $existing = acf_get_field_group($field_group['key']);
        if (!$existing) {
            $result = acf_import_field_group($field_group);
            if ($result) {
                error_log("✅ Field group imported: {$field_group['title']}");
            } else {
                error_log("❌ Field group import failed: {$field_group['title']}");
            }
        }

        return true;
    }

    /**
     * Get field group registration status
     */
    public function get_registration_status(): array
    {
        $status = [
            'essential_listing_info' => false,
            'property_details' => false,
            'location_intelligence' => false,
            'relationships_team' => false,
            'financial_analytics' => false,
            'advanced_search' => false,
            'calculator_enhanced' => false
        ];

        // Check if field groups are registered
        if (function_exists('acf_get_field_group')) {
            $status['essential_listing_info'] = acf_get_field_group('group_essential_listing_info_v2') !== false;
            $status['property_details'] = acf_get_field_group('group_property_details_classification') !== false;
            $status['location_intelligence'] = acf_get_field_group('group_location_address_intelligence') !== false;
            $status['relationships_team'] = acf_get_field_group('group_relationships_team_management') !== false;
            $status['financial_analytics'] = acf_get_field_group('group_financial_market_analytics') !== false;
            $status['advanced_search'] = acf_get_field_group('group_advanced_search_filtering') !== false;
        }

        // Check if calculator is enhanced
        $calculator = \HappyPlace\Fields\Listing_Calculator::get_instance();
        $status['calculator_enhanced'] = method_exists($calculator, 'process_address_fields');

        return $status;
    }
}

// Initialize the enhanced field manager
add_action('init', function() {
    Enhanced_Field_Manager::get_instance();
});

// Add admin dashboard widget for Phase 1 status
add_action('wp_dashboard_setup', function() {
    if (current_user_can('administrator')) {
        wp_add_dashboard_widget(
            'hph_phase1_status',
            '🏠 Happy Place Phase 1 Status',
            function() {
                $manager = Enhanced_Field_Manager::get_instance();
                $status = $manager->get_registration_status();
                
                echo '<div style="padding: 10px;">';
                echo '<h4>Enhanced Calculator & Field Groups</h4>';
                echo '<ul style="margin: 0;">';
                echo '<li>Essential Listing Info: ' . ($status['essential_listing_info'] ? '✅ Active' : '❌ Not Found') . '</li>';
                echo '<li>Property Details: ' . ($status['property_details'] ? '✅ Active' : '❌ Not Found') . '</li>';
                echo '<li>Enhanced Calculator: ' . ($status['calculator_enhanced'] ? '✅ Active' : '❌ Not Enhanced') . '</li>';
                echo '</ul>';
                
                if (array_filter($status)) {
                    echo '<p style="color: green; font-weight: bold;">🎉 Phase 1 is operational!</p>';
                    echo '<p><small>Test with: <code>yoursite.com?test_calculator=1</code></small></p>';
                } else {
                    echo '<p style="color: red;">⚠️ Phase 1 setup incomplete</p>';
                }
                echo '</div>';
            }
        );
    }
});
