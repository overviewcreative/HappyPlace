<?php

/**
 * Comprehensive Settings Page for Happy Place Plugin
 * 
 * @package Happy_Place_Plugin
 */

namespace HPH\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Settings_Page
{
    private static ?self $instance = null;

    public static function get_instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_settings()
    {
        // General Settings
        register_setting('hph_general_settings', 'hph_plugin_enabled', [
            'type' => 'boolean',
            'default' => true
        ]);

        register_setting('hph_general_settings', 'hph_debug_mode', [
            'type' => 'boolean',
            'default' => false
        ]);

        // Display Settings
        register_setting('hph_display_settings', 'hph_listings_per_page', [
            'type' => 'integer',
            'default' => 12
        ]);

        register_setting('hph_display_settings', 'hph_default_map_zoom', [
            'type' => 'integer',
            'default' => 13
        ]);

        // Integration Settings
        register_setting('hph_integration_settings', 'hph_airtable_token', [
            'type' => 'string',
            'default' => ''
        ]);

        register_setting('hph_integration_settings', 'hph_airtable_base', [
            'type' => 'string',
            'default' => ''
        ]);

        // Email Settings
        register_setting('hph_email_settings', 'hph_email_notifications', [
            'type' => 'boolean',
            'default' => true
        ]);

        register_setting('hph_email_settings', 'hph_admin_email', [
            'type' => 'string',
            'default' => get_option('admin_email')
        ]);
    }

    public function render_settings_page()
    {
        // Handle form submission
        if (isset($_POST['submit']) && check_admin_referer('hph_settings_nonce')) {
            $this->save_settings();
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'happy-place') . '</p></div>';
        }

        $current_tab = $_GET['tab'] ?? 'general';
?>
        <div class="wrap hph-settings-wrap">
            <h1><?php _e('Happy Place Settings', 'happy-place'); ?></h1>

            <!-- Settings Navigation -->
            <nav class="nav-tab-wrapper">
                <a href="?page=happy-place-settings&tab=general"
                    class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('General', 'happy-place'); ?>
                </a>
                <a href="?page=happy-place-settings&tab=display"
                    class="nav-tab <?php echo $current_tab === 'display' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Display', 'happy-place'); ?>
                </a>
                <a href="?page=happy-place-settings&tab=integrations"
                    class="nav-tab <?php echo $current_tab === 'integrations' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Integrations', 'happy-place'); ?>
                </a>
                <a href="?page=happy-place-settings&tab=email"
                    class="nav-tab <?php echo $current_tab === 'email' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Email', 'happy-place'); ?>
                </a>
                <a href="?page=happy-place-settings&tab=advanced"
                    class="nav-tab <?php echo $current_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Advanced', 'happy-place'); ?>
                </a>
            </nav>

            <form method="post" action="">
                <?php wp_nonce_field('hph_settings_nonce'); ?>

                <div class="settings-content">
                    <?php
                    switch ($current_tab) {
                        case 'general':
                            $this->render_general_settings();
                            break;
                        case 'display':
                            $this->render_display_settings();
                            break;
                        case 'integrations':
                            $this->render_integration_settings();
                            break;
                        case 'email':
                            $this->render_email_settings();
                            break;
                        case 'advanced':
                            $this->render_advanced_settings();
                            break;
                    }
                    ?>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>

        <style>
            .hph-settings-wrap {
                max-width: 1000px;
            }

            .settings-content {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 30px;
                margin: 20px 0;
            }

            .setting-group {
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 1px solid #f1f1f1;
            }

            .setting-group:last-child {
                border-bottom: none;
                margin-bottom: 0;
            }

            .setting-group h3 {
                margin: 0 0 15px 0;
                color: #23282d;
                font-size: 18px;
                font-weight: 600;
            }

            .setting-row {
                display: flex;
                align-items: center;
                margin-bottom: 15px;
                gap: 20px;
            }

            .setting-label {
                flex: 0 0 200px;
                font-weight: 600;
                color: #23282d;
            }

            .setting-control {
                flex: 1;
            }

            .setting-description {
                margin-top: 5px;
                color: #666;
                font-size: 13px;
                line-height: 1.4;
            }

            .setting-control input[type="text"],
            .setting-control input[type="email"],
            .setting-control input[type="number"],
            .setting-control select,
            .setting-control textarea {
                width: 100%;
                max-width: 400px;
            }

            .setting-control textarea {
                height: 100px;
                resize: vertical;
            }

            .checkbox-wrapper {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .status-indicator {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 4px 8px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 500;
            }

            .status-connected {
                background: #d4edda;
                color: #155724;
            }

            .status-disconnected {
                background: #f8d7da;
                color: #721c24;
            }

            .test-connection-btn {
                margin-left: 10px;
            }

            @media (max-width: 768px) {
                .setting-row {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 10px;
                }

                .setting-label {
                    flex: none;
                }

                .setting-control {
                    width: 100%;
                }

                .setting-control input[type="text"],
                .setting-control input[type="email"],
                .setting-control input[type="number"],
                .setting-control select,
                .setting-control textarea {
                    max-width: none;
                }
            }
        </style>
    <?php
    }

    private function render_general_settings()
    {
    ?>
        <div class="setting-group">
            <h3><?php _e('Plugin Configuration', 'happy-place'); ?></h3>

            <div class="setting-row">
                <div class="setting-label">
                    <?php _e('Enable Plugin', 'happy-place'); ?>
                </div>
                <div class="setting-control">
                    <div class="checkbox-wrapper">
                        <input type="checkbox"
                            id="hph_plugin_enabled"
                            name="hph_plugin_enabled"
                            value="1"
                            <?php checked(get_option('hph_plugin_enabled', true)); ?>>
                        <label for="hph_plugin_enabled"><?php _e('Enable Happy Place Plugin functionality', 'happy-place'); ?></label>
                    </div>
                    <div class="setting-description">
                        <?php _e('Disable this to temporarily turn off all plugin features.', 'happy-place'); ?>
                    </div>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <?php _e('Debug Mode', 'happy-place'); ?>
                </div>
                <div class="setting-control">
                    <div class="checkbox-wrapper">
                        <input type="checkbox"
                            id="hph_debug_mode"
                            name="hph_debug_mode"
                            value="1"
                            <?php checked(get_option('hph_debug_mode', false)); ?>>
                        <label for="hph_debug_mode"><?php _e('Enable debug logging', 'happy-place'); ?></label>
                    </div>
                    <div class="setting-description">
                        <?php _e('Enable detailed logging for troubleshooting. Only enable when needed.', 'happy-place'); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="setting-group">
            <h3><?php _e('Data Management', 'happy-place'); ?></h3>

            <div class="setting-row">
                <div class="setting-label">
                    <?php _e('Auto-cleanup', 'happy-place'); ?>
                </div>
                <div class="setting-control">
                    <div class="checkbox-wrapper">
                        <input type="checkbox"
                            id="hph_auto_cleanup"
                            name="hph_auto_cleanup"
                            value="1"
                            <?php checked(get_option('hph_auto_cleanup', false)); ?>>
                        <label for="hph_auto_cleanup"><?php _e('Automatically clean up expired listings', 'happy-place'); ?></label>
                    </div>
                    <div class="setting-description">
                        <?php _e('Automatically move expired listings to draft status after 90 days.', 'happy-place'); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }

    private function render_display_settings()
    {
    ?>
        <div class="setting-group">
            <h3><?php _e('Listing Display', 'happy-place'); ?></h3>

            <div class="setting-row">
                <div class="setting-label">
                    <?php _e('Listings Per Page', 'happy-place'); ?>
                </div>
                <div class="setting-control">
                    <input type="number"
                        id="hph_listings_per_page"
                        name="hph_listings_per_page"
                        value="<?php echo esc_attr(get_option('hph_listings_per_page', 12)); ?>"
                        min="1"
                        max="50">
                    <div class="setting-description">
                        <?php _e('Number of listings to display per page in listing archives.', 'happy-place'); ?>
                    </div>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <?php _e('Default Sort Order', 'happy-place'); ?>
                </div>
                <div class="setting-control">
                    <select id="hph_default_sort" name="hph_default_sort">
                        <option value="date" <?php selected(get_option('hph_default_sort', 'date'), 'date'); ?>>
                            <?php _e('Date Added (Newest First)', 'happy-place'); ?>
                        </option>
                        <option value="price_asc" <?php selected(get_option('hph_default_sort'), 'price_asc'); ?>>
                            <?php _e('Price (Low to High)', 'happy-place'); ?>
                        </option>
                        <option value="price_desc" <?php selected(get_option('hph_default_sort'), 'price_desc'); ?>>
                            <?php _e('Price (High to Low)', 'happy-place'); ?>
                        </option>
                        <option value="title" <?php selected(get_option('hph_default_sort'), 'title'); ?>>
                            <?php _e('Title (A-Z)', 'happy-place'); ?>
                        </option>
                    </select>
                    <div class="setting-description">
                        <?php _e('Default sorting order for listing pages.', 'happy-place'); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="setting-group">
            <h3><?php _e('Map Settings', 'happy-place'); ?></h3>

            <div class="setting-row">
                <div class="setting-label">
                    <?php _e('Default Map Zoom', 'happy-place'); ?>
                </div>
                <div class="setting-control">
                    <input type="number"
                        id="hph_default_map_zoom"
                        name="hph_default_map_zoom"
                        value="<?php echo esc_attr(get_option('hph_default_map_zoom', 13)); ?>"
                        min="1"
                        max="20">
                    <div class="setting-description">
                        <?php _e('Default zoom level for maps (1-20, where 20 is closest).', 'happy-place'); ?>
                    </div>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <?php _e('Map Provider', 'happy-place'); ?>
                </div>
                <div class="setting-control">
                    <select id="hph_map_provider" name="hph_map_provider">
                        <option value="google" <?php selected(get_option('hph_map_provider', 'google'), 'google'); ?>>
                            <?php _e('Google Maps', 'happy-place'); ?>
                        </option>
                        <option value="mapbox" <?php selected(get_option('hph_map_provider'), 'mapbox'); ?>>
                            <?php _e('Mapbox', 'happy-place'); ?>
                        </option>
                        <option value="openstreet" <?php selected(get_option('hph_map_provider'), 'openstreet'); ?>>
                            <?php _e('OpenStreetMap', 'happy-place'); ?>
                        </option>
                    </select>
                    <div class="setting-description">
                        <?php _e('Choose your preferred map service provider.', 'happy-place'); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }

    private function render_integration_settings()
    {
        $airtable_status = $this->check_airtable_connection();
    ?>
        <div class="setting-group">
            <h3><?php _e('Airtable Integration', 'happy-place'); ?></h3>

            <div class="setting-row">
                <div class="setting-label">
                    <?php _e('Connection Status', 'happy-place'); ?>
                </div>
                <div class="setting-control">
                    <span class="status-indicator <?php echo $airtable_status ? 'status-connected' : 'status-disconnected'; ?>">
                        <?php echo $airtable_status ? __('Connected', 'happy-place') : __('Disconnected', 'happy-place'); ?>
                    </span>
                    <button type="button" class="button test-connection-btn" id="test-airtable">
                        <?php _e('Test Connection', 'happy-place'); ?>
                    </button>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <?php _e('API Token', 'happy-place'); ?>
                </div>
                <div class="setting-control">
                    <input type="password"
                        id="hph_airtable_token"
                        name="hph_airtable_token"
                        value="<?php echo esc_attr(get_option('hph_airtable_token', '')); ?>">
                    <div class="setting-description">
                        <?php _e('Your Airtable API token for data synchronization.', 'happy-place'); ?>
                    </div>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <?php _e('Base ID', 'happy-place'); ?>
                </div>
                <div class="setting-control">
                    <input type="text"
                        id="hph_airtable_base"
                        name="hph_airtable_base"
                        value="<?php echo esc_attr(get_option('hph_airtable_base', '')); ?>">
                    <div class="setting-description">
                        <?php _e('The ID of your Airtable base to sync with.', 'happy-place'); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="setting-group">
            <h3><?php _e('MLS Integration', 'happy-place'); ?></h3>

            <div class="setting-row">
                <div class="setting-label">
                    <?php _e('MLS Provider', 'happy-place'); ?>
                </div>
                <div class="setting-control">
                    <select id="hph_mls_provider" name="hph_mls_provider">
                        <option value=""><?php _e('Select MLS Provider...', 'happy-place'); ?></option>
                        <option value="crmls" <?php selected(get_option('hph_mls_provider'), 'crmls'); ?>>
                            <?php _e('CRMLS', 'happy-place'); ?>
                        </option>
                        <option value="mlslistings" <?php selected(get_option('hph_mls_provider'), 'mlslistings'); ?>>
                            <?php _e('MLSListings', 'happy-place'); ?>
                        </option>
                        <option value="other" <?php selected(get_option('hph_mls_provider'), 'other'); ?>>
                            <?php _e('Other', 'happy-place'); ?>
                        </option>
                    </select>
                    <div class="setting-description">
                        <?php _e('Choose your MLS provider for listing synchronization.', 'happy-place'); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }

    private function render_email_settings()
    {
    ?>
        <div class="setting-group">
            <h3><?php _e('Email Notifications', 'happy-place'); ?></h3>

            <div class="setting-row">
                <div class="setting-label">
                    <?php _e('Enable Notifications', 'happy-place'); ?>
                </div>
                <div class="setting-control">
                    <div class="checkbox-wrapper">
                        <input type="checkbox"
                            id="hph_email_notifications"
                            name="hph_email_notifications"
                            value="1"
                            <?php checked(get_option('hph_email_notifications', true)); ?>>
                        <label for="hph_email_notifications"><?php _e('Send email notifications', 'happy-place'); ?></label>
                    </div>
                    <div class="setting-description">
                        <?php _e('Enable email notifications for new leads and listing updates.', 'happy-place'); ?>
                    </div>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <?php _e('Admin Email', 'happy-place'); ?>
                </div>
                <div class="setting-control">
                    <input type="email"
                        id="hph_admin_email"
                        name="hph_admin_email"
                        value="<?php echo esc_attr(get_option('hph_admin_email', get_option('admin_email'))); ?>">
                    <div class="setting-description">
                        <?php _e('Email address to receive notifications.', 'happy-place'); ?>
                    </div>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <?php _e('From Name', 'happy-place'); ?>
                </div>
                <div class="setting-control">
                    <input type="text"
                        id="hph_email_from_name"
                        name="hph_email_from_name"
                        value="<?php echo esc_attr(get_option('hph_email_from_name', get_bloginfo('name'))); ?>">
                    <div class="setting-description">
                        <?php _e('Name to use in the "From" field of emails.', 'happy-place'); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }

    private function render_advanced_settings()
    {
    ?>
        <div class="setting-group">
            <h3><?php _e('Performance', 'happy-place'); ?></h3>

            <div class="setting-row">
                <div class="setting-label">
                    <?php _e('Enable Caching', 'happy-place'); ?>
                </div>
                <div class="setting-control">
                    <div class="checkbox-wrapper">
                        <input type="checkbox"
                            id="hph_enable_caching"
                            name="hph_enable_caching"
                            value="1"
                            <?php checked(get_option('hph_enable_caching', true)); ?>>
                        <label for="hph_enable_caching"><?php _e('Enable object caching for better performance', 'happy-place'); ?></label>
                    </div>
                    <div class="setting-description">
                        <?php _e('Cache database queries and API responses to improve site speed.', 'happy-place'); ?>
                    </div>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <?php _e('Cache Duration', 'happy-place'); ?>
                </div>
                <div class="setting-control">
                    <select id="hph_cache_duration" name="hph_cache_duration">
                        <option value="300" <?php selected(get_option('hph_cache_duration', '3600'), '300'); ?>>
                            <?php _e('5 minutes', 'happy-place'); ?>
                        </option>
                        <option value="1800" <?php selected(get_option('hph_cache_duration', '3600'), '1800'); ?>>
                            <?php _e('30 minutes', 'happy-place'); ?>
                        </option>
                        <option value="3600" <?php selected(get_option('hph_cache_duration', '3600'), '3600'); ?>>
                            <?php _e('1 hour', 'happy-place'); ?>
                        </option>
                        <option value="21600" <?php selected(get_option('hph_cache_duration', '3600'), '21600'); ?>>
                            <?php _e('6 hours', 'happy-place'); ?>
                        </option>
                        <option value="86400" <?php selected(get_option('hph_cache_duration', '3600'), '86400'); ?>>
                            <?php _e('24 hours', 'happy-place'); ?>
                        </option>
                    </select>
                    <div class="setting-description">
                        <?php _e('How long to cache data before refreshing.', 'happy-place'); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="setting-group">
            <h3><?php _e('Security', 'happy-place'); ?></h3>

            <div class="setting-row">
                <div class="setting-label">
                    <?php _e('API Rate Limiting', 'happy-place'); ?>
                </div>
                <div class="setting-control">
                    <div class="checkbox-wrapper">
                        <input type="checkbox"
                            id="hph_rate_limiting"
                            name="hph_rate_limiting"
                            value="1"
                            <?php checked(get_option('hph_rate_limiting', true)); ?>>
                        <label for="hph_rate_limiting"><?php _e('Enable API rate limiting', 'happy-place'); ?></label>
                    </div>
                    <div class="setting-description">
                        <?php _e('Limit API requests to prevent abuse.', 'happy-place'); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="setting-group">
            <h3><?php _e('Data Export/Import', 'happy-place'); ?></h3>

            <div class="setting-row">
                <div class="setting-label">
                    <?php _e('Export All Data', 'happy-place'); ?>
                </div>
                <div class="setting-control">
                    <button type="button" class="button" id="export-all-data">
                        <?php _e('Export Plugin Data', 'happy-place'); ?>
                    </button>
                    <div class="setting-description">
                        <?php _e('Export all plugin data for backup or migration.', 'happy-place'); ?>
                    </div>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <?php _e('Reset Plugin', 'happy-place'); ?>
                </div>
                <div class="setting-control">
                    <button type="button" class="button button-delete" id="reset-plugin">
                        <?php _e('Reset All Data', 'happy-place'); ?>
                    </button>
                    <div class="setting-description">
                        <?php _e('WARNING: This will delete all plugin data and cannot be undone.', 'happy-place'); ?>
                    </div>
                </div>
            </div>
        </div>
<?php
    }

    private function save_settings()
    {
        $settings = [
            'hph_plugin_enabled',
            'hph_debug_mode',
            'hph_auto_cleanup',
            'hph_listings_per_page',
            'hph_default_sort',
            'hph_default_map_zoom',
            'hph_map_provider',
            'hph_airtable_token',
            'hph_airtable_base',
            'hph_mls_provider',
            'hph_email_notifications',
            'hph_admin_email',
            'hph_email_from_name',
            'hph_enable_caching',
            'hph_cache_duration',
            'hph_rate_limiting'
        ];

        foreach ($settings as $setting) {
            if (isset($_POST[$setting])) {
                update_option($setting, sanitize_text_field($_POST[$setting]));
            } else {
                // Handle checkboxes that aren't checked
                if (in_array($setting, ['hph_plugin_enabled', 'hph_debug_mode', 'hph_auto_cleanup', 'hph_email_notifications', 'hph_enable_caching', 'hph_rate_limiting'])) {
                    update_option($setting, false);
                }
            }
        }
    }

    private function check_airtable_connection()
    {
        $token = get_option('hph_airtable_token', '');
        $base = get_option('hph_airtable_base', '');

        return !empty($token) && !empty($base);
    }
}
