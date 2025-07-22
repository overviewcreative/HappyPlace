<?php
/**
 * ACF Field Groups Migration Script
 * 
 * This script handles the migration from the old scattered field groups
 * to the new organized structure while preserving all data.
 *
 * @package HappyPlace
 * @subpackage Migration
 */

namespace HappyPlace\Migration;

if (!defined('ABSPATH')) {
    exit;
}

class ACF_Field_Groups_Migration {
    
    /**
     * Instance
     */
    private static ?self $instance = null;
    
    /**
     * Field mapping from old to new structure
     */
    private array $field_mapping = [
        // Essential Listing Info (no changes needed)
        'price' => 'price',
        'status' => 'status',
        'mls_number' => 'mls_number',
        'list_date' => 'list_date',
        'property_type' => 'property_type',
        'property_style' => 'property_style',
        'square_footage' => 'square_footage',
        'bedrooms' => 'bedrooms',
        'bathrooms' => 'bathrooms',
        'half_baths' => 'half_baths',
        'year_built' => 'year_built',
        'lot_size' => 'lot_size',
        
        // Property Details & Features
        'street_number' => 'street_number',
        'street_name' => 'street_name',
        'unit_number' => 'unit_number',
        'city' => 'city',
        'state' => 'state',
        'zip_code' => 'zip_code',
        'county' => 'county',
        'latitude' => 'latitude',
        'longitude' => 'longitude',
        'full_address' => 'full_address',
        'interior_features' => 'interior_features',
        'exterior_features' => 'exterior_features',
        'utility_features' => 'utility_features',
        'custom_features' => 'custom_features',
        'property_tax' => 'property_tax',
        'hoa_fees' => 'hoa_fees',
        'estimated_payment' => 'estimated_payment',
        
        // Location Intelligence (no changes needed)
        'school_district' => 'school_district',
        'elementary_school' => 'elementary_school',
        'middle_school' => 'middle_school',
        'high_school' => 'high_school',
        'walk_score' => 'walk_score',
        'transit_score' => 'transit_score',
        'bike_score' => 'bike_score',
        'nearby_amenities' => 'nearby_amenities',
        'location_intelligence_last_updated' => 'location_intelligence_last_updated',
        
        // Advanced Analytics & Relationships
        'price_per_sqft' => 'price_per_sqft',
        'price_per_living_sqft' => 'price_per_living_sqft',
        'property_tax_rate' => 'property_tax_rate',
        'estimated_down_payment' => 'estimated_down_payment',
        'estimated_interest_rate' => 'estimated_interest_rate',
        'estimated_loan_term' => 'estimated_loan_term',
        'estimated_pmi_rate' => 'estimated_pmi_rate',
        'estimated_down_payment_amount' => 'estimated_down_payment_amount',
        'estimated_monthly_payment' => 'estimated_monthly_payment',
        'estimated_monthly_taxes' => 'estimated_monthly_taxes',
        'estimated_monthly_insurance' => 'estimated_monthly_insurance',
        'estimated_pmi' => 'estimated_pmi',
        'piti_payment' => 'piti_payment',
        'total_monthly_cost' => 'total_monthly_cost',
        'listing_agent' => 'listing_agent',
        'co_listing_agent' => 'co_listing_agent',
        'buyer_agent' => 'buyer_agent',
        'related_community' => 'related_community',
        'contract_date' => 'contract_date',
        'close_date' => 'close_date',
        'days_on_market' => 'days_on_market',
    ];
    
    /**
     * Old field groups to deactivate
     */
    private array $old_field_groups = [
        'group_listing_details',
        'group_property_features',
        'group_listing_dates',
        'group_calculated_fields',
        'group_enhanced_calculations',
        'group_location_intelligence',
        'group_listing_relationships',
        'group_custom_features',
        'group_listing_address_components',
        // Add other old groups as needed
    ];
    
    /**
     * Get instance
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_init', [$this, 'register_migration_hooks']);
    }
    
    /**
     * Register migration hooks
     */
    public function register_migration_hooks(): void {
        // Add admin menu for migration
        add_action('admin_menu', [$this, 'add_migration_menu']);
        
        // AJAX handlers
        add_action('wp_ajax_hph_backup_field_data', [$this, 'backup_field_data']);
        add_action('wp_ajax_hph_migrate_field_groups', [$this, 'migrate_field_groups']);
        add_action('wp_ajax_hph_cleanup_old_groups', [$this, 'cleanup_old_groups']);
    }
    
    /**
     * Add migration admin menu
     */
    public function add_migration_menu(): void {
        add_submenu_page(
            'happy-place',
            __('Field Groups Migration', 'happy-place'),
            __('Field Migration', 'happy-place'),
            'manage_options',
            'hph-field-migration',
            [$this, 'render_migration_page']
        );
    }
    
    /**
     * Render migration page
     */
    public function render_migration_page(): void {
        ?>
        <div class="wrap">
            <h1><?php _e('ACF Field Groups Migration', 'happy-place'); ?></h1>
            <p><?php _e('This tool will migrate your listing field groups to the new organized structure.', 'happy-place'); ?></p>
            
            <div class="hph-migration-steps">
                <div class="step" id="step-1">
                    <h3>Step 1: Backup Current Data</h3>
                    <p>Create a backup of all current field data before migration.</p>
                    <button type="button" class="button button-primary" onclick="backupFieldData()">Create Backup</button>
                    <div id="backup-status"></div>
                </div>
                
                <div class="step" id="step-2" style="display: none;">
                    <h3>Step 2: Migrate Field Groups</h3>
                    <p>Apply the new field group structure and migrate existing data.</p>
                    <button type="button" class="button button-primary" onclick="migrateFieldGroups()">Start Migration</button>
                    <div id="migration-status"></div>
                </div>
                
                <div class="step" id="step-3" style="display: none;">
                    <h3>Step 3: Cleanup Old Groups</h3>
                    <p>Remove old field groups after successful migration.</p>
                    <button type="button" class="button button-secondary" onclick="cleanupOldGroups()">Cleanup Old Groups</button>
                    <div id="cleanup-status"></div>
                </div>
            </div>
            
            <div class="hph-migration-log" id="migration-log" style="display: none;">
                <h3>Migration Log</h3>
                <textarea id="log-content" rows="20" style="width: 100%; font-family: monospace;"></textarea>
            </div>
        </div>
        
        <style>
        .hph-migration-steps .step {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .step h3 {
            margin-top: 0;
            color: #333;
        }
        
        .step.completed {
            border-color: #4CAF50;
            background: #f0f8f0;
        }
        
        .step.error {
            border-color: #d63638;
            background: #fef0f0;
        }
        
        .hph-migration-log {
            margin-top: 30px;
        }
        </style>
        
        <script>
        function backupFieldData() {
            const button = event.target;
            const status = document.getElementById('backup-status');
            
            button.disabled = true;
            button.textContent = 'Creating Backup...';
            status.innerHTML = '<p>Backing up field data...</p>';
            
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hph_backup_field_data',
                    nonce: '<?php echo wp_create_nonce('hph_migration'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        status.innerHTML = '<p style="color: green;">✓ Backup completed successfully</p>';
                        document.getElementById('step-1').classList.add('completed');
                        document.getElementById('step-2').style.display = 'block';
                        addToLog('Backup completed: ' + response.data);
                    } else {
                        status.innerHTML = '<p style="color: red;">✗ Backup failed: ' + response.data + '</p>';
                        document.getElementById('step-1').classList.add('error');
                    }
                },
                error: function() {
                    status.innerHTML = '<p style="color: red;">✗ Backup failed - network error</p>';
                    document.getElementById('step-1').classList.add('error');
                },
                complete: function() {
                    button.disabled = false;
                    button.textContent = 'Create Backup';
                }
            });
        }
        
        function migrateFieldGroups() {
            const button = event.target;
            const status = document.getElementById('migration-status');
            
            button.disabled = true;
            button.textContent = 'Migrating...';
            status.innerHTML = '<p>Migrating field groups...</p>';
            
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hph_migrate_field_groups',
                    nonce: '<?php echo wp_create_nonce('hph_migration'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        status.innerHTML = '<p style="color: green;">✓ Migration completed successfully</p>';
                        document.getElementById('step-2').classList.add('completed');
                        document.getElementById('step-3').style.display = 'block';
                        addToLog('Migration completed: ' + response.data);
                    } else {
                        status.innerHTML = '<p style="color: red;">✗ Migration failed: ' + response.data + '</p>';
                        document.getElementById('step-2').classList.add('error');
                    }
                },
                error: function() {
                    status.innerHTML = '<p style="color: red;">✗ Migration failed - network error</p>';
                    document.getElementById('step-2').classList.add('error');
                },
                complete: function() {
                    button.disabled = false;
                    button.textContent = 'Start Migration';
                }
            });
        }
        
        function cleanupOldGroups() {
            const button = event.target;
            const status = document.getElementById('cleanup-status');
            
            button.disabled = true;
            button.textContent = 'Cleaning up...';
            status.innerHTML = '<p>Removing old field groups...</p>';
            
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hph_cleanup_old_groups',
                    nonce: '<?php echo wp_create_nonce('hph_migration'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        status.innerHTML = '<p style="color: green;">✓ Cleanup completed successfully</p>';
                        document.getElementById('step-3').classList.add('completed');
                        addToLog('Cleanup completed: ' + response.data);
                    } else {
                        status.innerHTML = '<p style="color: red;">✗ Cleanup failed: ' + response.data + '</p>';
                        document.getElementById('step-3').classList.add('error');
                    }
                },
                error: function() {
                    status.innerHTML = '<p style="color: red;">✗ Cleanup failed - network error</p>';
                    document.getElementById('step-3').classList.add('error');
                },
                complete: function() {
                    button.disabled = false;
                    button.textContent = 'Cleanup Old Groups';
                }
            });
        }
        
        function addToLog(message) {
            const log = document.getElementById('migration-log');
            const content = document.getElementById('log-content');
            const timestamp = new Date().toLocaleString();
            
            log.style.display = 'block';
            content.value += `[${timestamp}] ${message}\n`;
            content.scrollTop = content.scrollHeight;
        }
        </script>
        <?php
    }
    
    /**
     * Backup field data
     */
    public function backup_field_data(): void {
        check_ajax_referer('hph_migration', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        try {
            $listings = get_posts([
                'post_type' => 'listing',
                'post_status' => 'any',
                'numberposts' => -1,
                'fields' => 'ids'
            ]);
            
            $backup_data = [];
            
            foreach ($listings as $listing_id) {
                $fields = get_fields($listing_id);
                if ($fields) {
                    $backup_data[$listing_id] = $fields;
                }
            }
            
            // Save backup to file
            $backup_file = WP_CONTENT_DIR . '/uploads/hph-field-backup-' . date('Y-m-d-H-i-s') . '.json';
            file_put_contents($backup_file, json_encode($backup_data, JSON_PRETTY_PRINT));
            
            wp_send_json_success("Backup created: " . basename($backup_file) . " (" . count($listings) . " listings)");
            
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Migrate field groups
     */
    public function migrate_field_groups(): void {
        check_ajax_referer('hph_migration', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        try {
            // Activate new field groups
            $new_groups = [
                'group_essential_listing_info',
                'group_property_details_features',
                'group_location_intelligence_new',
                'group_advanced_analytics_relationships'
            ];
            
            foreach ($new_groups as $group_key) {
                // Sync from JSON if available
                if (function_exists('acf_import_field_group')) {
                    $json_file = HPH_INCLUDES_PATH . "fields/acf-json/{$group_key}.json";
                    if (file_exists($json_file)) {
                        $field_group = json_decode(file_get_contents($json_file), true);
                        acf_import_field_group($field_group);
                    }
                }
            }
            
            // Migrate any field mappings if needed
            $this->migrate_field_data();
            
            wp_send_json_success("Field groups migrated successfully");
            
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Migrate field data
     */
    private function migrate_field_data(): void {
        // Most field names remain the same, so no data migration needed
        // This method is here for future field name changes
    }
    
    /**
     * Cleanup old groups
     */
    public function cleanup_old_groups(): void {
        check_ajax_referer('hph_migration', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        try {
            $removed_count = 0;
            
            foreach ($this->old_field_groups as $group_key) {
                $field_group = acf_get_field_group($group_key);
                if ($field_group) {
                    // Deactivate instead of delete to preserve data
                    acf_update_field_group([
                        'key' => $group_key,
                        'active' => false
                    ]);
                    $removed_count++;
                }
            }
            
            wp_send_json_success("Deactivated {$removed_count} old field groups");
            
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
}

// Initialize migration if in admin
if (is_admin()) {
    ACF_Field_Groups_Migration::get_instance();
}
