<?php
/**
 * Enhanced Airtable Sync Initialization
 * 
 * Initializes the complete enhanced sync system with all components.
 * 
 * @package HappyPlace
 * @since 5.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Enhanced Airtable Sync System Initialization
add_action('plugins_loaded', function() {
    // Only initialize if the Happy Place Plugin is active
    if (!class_exists('Happy_Place_Plugin')) {
        return;
    }
    
    // Load enhanced sync classes
    require_once plugin_dir_path(__FILE__) . 'class-media-sync-manager.php';
    require_once plugin_dir_path(__FILE__) . 'class-enhanced-airtable-sync.php';
    require_once plugin_dir_path(__FILE__) . 'class-sync-orchestrator.php';
    
    // Initialize the sync orchestrator
    $GLOBALS['hph_sync_orchestrator'] = new \HappyPlace\Integrations\Sync_Orchestrator();
    
    // Register cleanup hook for single listing sync
    add_action('hph_sync_single_listing', function($listing_id) {
        $orchestrator = $GLOBALS['hph_sync_orchestrator'] ?? null;
        if (!$orchestrator) {
            return;
        }
        
        // Get sync settings
        $settings = get_option('hph_airtable_sync_settings', []);
        if (empty($settings['sync_enabled']) || empty($settings['base_id']) || empty($settings['api_key'])) {
            return;
        }
        
        try {
            // Create sync engine for single listing
            $sync_engine = new \HappyPlace\Integrations\Enhanced_Airtable_Sync(
                $settings['base_id'],
                $settings['table_name'] ?? 'Listings',
                $settings['api_key']
            );
            
            // Sync this specific listing to Airtable
            $listing = get_post($listing_id);
            if ($listing && $listing->post_type === 'happy_place_listing') {
                // Create a temporary array to mimic the sync process
                $result = $sync_engine->sync_wordpress_to_airtable();
                error_log("HPH Single Listing Sync: " . json_encode($result));
            }
            
        } catch (\Exception $e) {
            error_log('HPH Single Listing Sync Error: ' . $e->getMessage());
        }
    });
});

// Add admin notice if sync is not configured
add_action('admin_notices', function() {
    // Only show to administrators on listing pages
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'happy_place_listing') === false) {
        return;
    }
    
    $settings = get_option('hph_airtable_sync_settings', []);
    $is_configured = !empty($settings['base_id']) && !empty($settings['api_key']);
    
    if (!$is_configured) {
        ?>
        <div class="notice notice-info is-dismissible">
            <p>
                <strong>Enhanced Airtable Sync Available!</strong> 
                Configure your Airtable integration to enable two-way sync with smart field mapping, 
                calculated field intelligence, and media management.
                <a href="<?= admin_url('edit.php?post_type=happy_place_listing&page=hph-airtable-sync') ?>" class="button button-primary" style="margin-left: 10px;">
                    Configure Now
                </a>
            </p>
        </div>
        <?php
    } elseif (empty($settings['sync_enabled'])) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong>Airtable Sync Configured but Disabled.</strong> 
                <a href="<?= admin_url('edit.php?post_type=happy_place_listing&page=hph-airtable-sync') ?>">
                    Enable sync
                </a> to start synchronizing your listings.
            </p>
        </div>
        <?php
    }
});

// Register activation hook to set up default settings
register_activation_hook(__FILE__, function() {
    // Set default sync settings if they don't exist
    $default_settings = [
        'base_id' => '',
        'table_name' => 'Listings',
        'api_key' => '',
        'sync_enabled' => false,
        'auto_sync_interval' => 'hourly',
        'sync_direction' => 'bidirectional',
        'conflict_resolution' => 'wp_wins',
        'media_sync_enabled' => true,
        'batch_size' => 50,
        'max_retries' => 3,
        'debug_mode' => false
    ];
    
    $existing_settings = get_option('hph_airtable_sync_settings', []);
    $settings = array_merge($default_settings, $existing_settings);
    
    update_option('hph_airtable_sync_settings', $settings);
    
    // Clear any existing scheduled events
    wp_clear_scheduled_hook('hph_auto_sync_hourly');
    wp_clear_scheduled_hook('hph_auto_sync_twicedaily');
    wp_clear_scheduled_hook('hph_auto_sync_daily');
    
    // Set up cleanup cron
    if (!wp_next_scheduled('hph_sync_cleanup')) {
        wp_schedule_event(time(), 'daily', 'hph_sync_cleanup');
    }
});

// Register deactivation hook to clean up
register_deactivation_hook(__FILE__, function() {
    // Clear scheduled events
    wp_clear_scheduled_hook('hph_auto_sync_hourly');
    wp_clear_scheduled_hook('hph_auto_sync_twicedaily');
    wp_clear_scheduled_hook('hph_auto_sync_daily');
    wp_clear_scheduled_hook('hph_sync_cleanup');
    wp_clear_scheduled_hook('hph_sync_single_listing');
});

// Add sync status to listing list table
add_filter('manage_happy_place_listing_posts_columns', function($columns) {
    $columns['airtable_sync'] = 'Airtable Sync';
    return $columns;
});

add_action('manage_happy_place_listing_posts_custom_column', function($column, $post_id) {
    if ($column === 'airtable_sync') {
        $sync_enabled = get_post_meta($post_id, '_airtable_sync_enabled', true);
        $airtable_id = get_post_meta($post_id, '_airtable_record_id', true);
        $last_sync = get_post_meta($post_id, '_last_sync_wp_to_airtable', true);
        
        if ($sync_enabled) {
            $status_class = $airtable_id ? 'sync-enabled' : 'sync-pending';
            $status_text = $airtable_id ? 'Synced' : 'Pending';
            
            echo '<span class="' . esc_attr($status_class) . '">' . esc_html($status_text) . '</span>';
            
            if ($last_sync) {
                echo '<br><small>Last: ' . esc_html(date('M j, Y g:i A', strtotime($last_sync))) . '</small>';
            }
        } else {
            echo '<span class="sync-disabled">Disabled</span>';
        }
    }
}, 10, 2);

// Add CSS for sync status indicators
add_action('admin_head', function() {
    ?>
    <style>
    .sync-enabled {
        color: #46b450;
        font-weight: bold;
    }
    .sync-pending {
        color: #ffb900;
        font-weight: bold;
    }
    .sync-disabled {
        color: #dc3232;
    }
    </style>
    <?php
});

// Add sync toggle to listing edit screen
add_action('add_meta_boxes', function() {
    add_meta_box(
        'hph_airtable_sync_meta',
        'Airtable Sync',
        function($post) {
            $sync_enabled = get_post_meta($post->ID, '_airtable_sync_enabled', true);
            $airtable_id = get_post_meta($post->ID, '_airtable_record_id', true);
            $last_sync_wp = get_post_meta($post->ID, '_last_sync_wp_to_airtable', true);
            $last_sync_at = get_post_meta($post->ID, '_last_sync_airtable_to_wp', true);
            
            wp_nonce_field('hph_sync_meta', 'hph_sync_meta_nonce');
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Enable Sync</th>
                    <td>
                        <label>
                            <input type="checkbox" name="airtable_sync_enabled" value="1" 
                                   <?= checked($sync_enabled, '1', false) ?> />
                            Sync this listing with Airtable
                        </label>
                    </td>
                </tr>
                <?php if ($airtable_id): ?>
                <tr>
                    <th scope="row">Airtable Record</th>
                    <td>
                        <code><?= esc_html($airtable_id) ?></code>
                        <p class="description">This listing is linked to an Airtable record.</p>
                    </td>
                </tr>
                <?php endif; ?>
                <?php if ($last_sync_wp): ?>
                <tr>
                    <th scope="row">Last Sync to Airtable</th>
                    <td><?= esc_html(date('M j, Y g:i A', strtotime($last_sync_wp))) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($last_sync_at): ?>
                <tr>
                    <th scope="row">Last Sync from Airtable</th>
                    <td><?= esc_html(date('M j, Y g:i A', strtotime($last_sync_at))) ?></td>
                </tr>
                <?php endif; ?>
            </table>
            <?php
        },
        'happy_place_listing',
        'side',
        'default'
    );
});

// Save sync meta
add_action('save_post', function($post_id) {
    if (!isset($_POST['hph_sync_meta_nonce']) || !wp_verify_nonce($_POST['hph_sync_meta_nonce'], 'hph_sync_meta')) {
        return;
    }
    
    if (get_post_type($post_id) !== 'happy_place_listing') {
        return;
    }
    
    $sync_enabled = isset($_POST['airtable_sync_enabled']) ? '1' : '0';
    update_post_meta($post_id, '_airtable_sync_enabled', $sync_enabled);
});

// Add bulk action for enabling/disabling sync
add_filter('bulk_actions-edit-happy_place_listing', function($actions) {
    $actions['enable_airtable_sync'] = 'Enable Airtable Sync';
    $actions['disable_airtable_sync'] = 'Disable Airtable Sync';
    return $actions;
});

add_filter('handle_bulk_actions-edit-happy_place_listing', function($redirect_to, $action, $post_ids) {
    if ($action === 'enable_airtable_sync') {
        foreach ($post_ids as $post_id) {
            update_post_meta($post_id, '_airtable_sync_enabled', '1');
        }
        $redirect_to = add_query_arg('bulk_sync_enabled', count($post_ids), $redirect_to);
    } elseif ($action === 'disable_airtable_sync') {
        foreach ($post_ids as $post_id) {
            update_post_meta($post_id, '_airtable_sync_enabled', '0');
        }
        $redirect_to = add_query_arg('bulk_sync_disabled', count($post_ids), $redirect_to);
    }
    
    return $redirect_to;
}, 10, 3);

// Show bulk action notices
add_action('admin_notices', function() {
    if (!empty($_REQUEST['bulk_sync_enabled'])) {
        $count = intval($_REQUEST['bulk_sync_enabled']);
        echo '<div class="notice notice-success is-dismissible"><p>';
        printf(_n('%d listing sync enabled.', '%d listings sync enabled.', $count), $count);
        echo '</p></div>';
    }
    
    if (!empty($_REQUEST['bulk_sync_disabled'])) {
        $count = intval($_REQUEST['bulk_sync_disabled']);
        echo '<div class="notice notice-success is-dismissible"><p>';
        printf(_n('%d listing sync disabled.', '%d listings sync disabled.', $count), $count);
        echo '</p></div>';
    }
});

// Debug information for developers
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('wp_footer', function() {
        if (current_user_can('manage_options') && !is_admin()) {
            $settings = get_option('hph_airtable_sync_settings', []);
            echo '<!-- HPH Enhanced Airtable Sync Debug -->';
            echo '<!-- Sync Enabled: ' . ($settings['sync_enabled'] ? 'Yes' : 'No') . ' -->';
            echo '<!-- Base ID: ' . (!empty($settings['base_id']) ? 'Configured' : 'Not Set') . ' -->';
            echo '<!-- API Key: ' . (!empty($settings['api_key']) ? 'Configured' : 'Not Set') . ' -->';
            echo '<!-- Last Sync: ' . get_option('hph_last_airtable_sync', 'Never') . ' -->';
        }
    });
}
