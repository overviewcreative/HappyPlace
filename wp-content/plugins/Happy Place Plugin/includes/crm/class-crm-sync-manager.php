<?php
namespace HappyPlace\CRM;

use HappyPlace\Integrations\Airtable_Sync;
use HappyPlace\Integrations\FollowUpBoss_Sync;

class CRM_Sync_Manager {
    private static ?self $instance = null;

    // Sync configurations
    private $sync_configs = [
        'listings' => [
            'airtable_base' => null,
            'airtable_table' => 'Listings',
            'sync_frequency' => 'daily',
            'last_sync' => null,
            'sync_fields' => [
                'title' => 'Title',
                'price' => 'Price',
                'address' => 'Street Address',
                // Add more mappings
            ]
        ],
        'agents' => [
            'airtable_base' => null,
            'airtable_table' => 'Agents',
            'sync_frequency' => 'daily',
            'last_sync' => null,
            'sync_fields' => [
                'name' => 'Agent Name',
                'email' => 'Email',
                'phone' => 'Phone Number',
                // Add more mappings
            ]
        ],
        'clients' => [
            'airtable_base' => null,
            'airtable_table' => 'Clients',
            'sync_frequency' => 'hourly',
            'last_sync' => null,
            'sync_fields' => [
                'name' => 'Client Name',
                'email' => 'Email',
                'phone' => 'Phone Number',
                'status' => 'Client Status'
            ]
        ]
    ];

    // Sync services
    private $sync_services = [];

    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        $this->init_sync_services();
        $this->schedule_sync_tasks();
    }

    /**
     * Initialize sync services
     */
    private function init_sync_services(): void {
        $this->sync_services = [
            'airtable' => new Airtable_Sync(),
            'followupboss' => new FollowUpBoss_Sync()
        ];
    }

    /**
     * Schedule sync tasks
     */
    private function schedule_sync_tasks(): void {
        foreach ($this->sync_configs as $type => $config) {
            $this->schedule_individual_sync($type, $config);
        }
    }

    /**
     * Schedule individual sync task
     */
    private function schedule_individual_sync(string $type, array $config): void {
        if (!wp_next_scheduled("hph_sync_{$type}")) {
            wp_schedule_event(
                time(), 
                $config['sync_frequency'], 
                "hph_sync_{$type}"
            );
        }

        add_action("hph_sync_{$type}", function() use ($type, $config) {
            $this->perform_sync($type, $config);
        });
    }

    /**
     * Perform synchronization
     */
    public function perform_sync(string $type, array $config): void {
        // Validate sync configuration
        if (empty($config['airtable_base']) || empty($config['airtable_table'])) {
            error_log("Sync configuration missing for {$type}");
            return;
        }

        try {
            // Sync with Airtable
            $airtable_data = $this->sync_services['airtable']->fetch_records(
                $config['airtable_base'], 
                $config['airtable_table']
            );

            // Transform and import data
            $imported_count = $this->import_records($type, $airtable_data, $config['sync_fields']);

            // Sync with Follow Up Boss
            $this->sync_services['followupboss']->import_records($type, $airtable_data);

            // Log sync details
            $this->log_sync_activity($type, $imported_count);
        } catch (\Exception $e) {
            error_log("Sync error for {$type}: " . $e->getMessage());
        }
    }

    /**
     * Import records to WordPress
     */
    private function import_records(string $type, array $records, array $field_mapping): int {
        $imported_count = 0;

        foreach ($records as $record) {
            // Prepare post data
            $post_data = [
                'post_type' => $type,
                'post_title' => $record[$field_mapping['name']] ?? '',
                'post_status' => 'publish'
            ];

            // Insert or update post
            $post_id = $this->find_or_create_post($type, $post_data);

            // Update custom fields
            foreach ($field_mapping as $wp_field => $airtable_field) {
                if (isset($record[$airtable_field])) {
                    update_field($wp_field, $record[$airtable_field], $post_id);
                }
            }

            $imported_count++;
        }

        return $imported_count;
    }

    /**
     * Find existing post or create new
     */
    private function find_or_create_post(string $type, array $post_data): int {
        // Try to find existing post
        $existing_post = get_page_by_title($post_data['post_title'], OBJECT, $type);

        if ($existing_post) {
            $post_data['ID'] = $existing_post->ID;
            return wp_update_post($post_data);
        }

        return wp_insert_post($post_data);
    }

    /**
     * Log sync activity
     */
    private function log_sync_activity(string $type, int $imported_count): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'hph_sync_logs';

        $wpdb->insert(
            $table_name,
            [
                'sync_type' => $type,
                'records_imported' => $imported_count,
                'sync_timestamp' => current_time('mysql')
            ],
            ['%s', '%d', '%s']
        );
    }

    /**
     * Create sync logs table
     */
    public function create_sync_logs_table(): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'hph_sync_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            sync_type varchar(50) NOT NULL,
            records_imported int NOT NULL,
            sync_timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Manual sync trigger
     */
    public function manual_sync(?string $type = null): array {
        $results = [];

        if ($type) {
            // Sync specific type
            $config = $this->sync_configs[$type] ?? null;
            if ($config) {
                $this->perform_sync($type, $config);
                $results[$type] = 'Sync completed';
            }
        } else {
            // Sync all configured types
            foreach ($this->sync_configs as $sync_type => $config) {
                $this->perform_sync($sync_type, $config);
                $results[$sync_type] = 'Sync completed';
            }
        }

        return $results;
    }
}

// Initialize CRM Sync Manager
$crm_sync_manager = CRM_Sync_Manager::get_instance();