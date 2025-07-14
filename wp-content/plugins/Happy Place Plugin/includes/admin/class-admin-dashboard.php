<?php

/**
 * Comprehensive Admin Dashboard for Happy Place Plugin
 * 
 * @package Happy_Place_Plugin
 */

namespace HPH\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Admin_Dashboard
{
    private static ?self $instance = null;

    public static function get_instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function enqueue_admin_assets($hook_suffix)
    {
        // Only load on our admin pages
        if (strpos($hook_suffix, 'happy-place') === false) {
            return;
        }

        wp_enqueue_style(
            'hph-admin-dashboard',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/admin-dashboard.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'hph-admin-dashboard',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/admin-dashboard.js',
            ['jquery'],
            '1.0.0',
            true
        );

        // Localize script with data
        wp_localize_script('hph-admin-dashboard', 'hphAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_admin_nonce'),
            'strings' => [
                'confirm_delete' => __('Are you sure you want to delete this item?', 'happy-place'),
                'saving' => __('Saving...', 'happy-place'),
                'saved' => __('Saved!', 'happy-place'),
                'error' => __('Error occurred. Please try again.', 'happy-place'),
            ]
        ]);
    }

    public function render_main_dashboard()
    {
        $post_type_counts = $this->get_post_type_counts();
        $recent_activity = $this->get_recent_activity();
        $system_status = $this->get_system_status();
?>
        <div class="wrap hph-admin-wrap">
            <h1><?php _e('Happy Place Plugin Dashboard', 'happy-place'); ?></h1>

            <!-- Dashboard Overview -->
            <div class="hph-dashboard-grid">

                <!-- Stats Cards -->
                <div class="hph-stats-grid">
                    <?php foreach ($post_type_counts as $post_type => $data): ?>
                        <div class="hph-stat-card">
                            <div class="stat-icon">
                                <span class="dashicons <?php echo esc_attr($data['icon']); ?>"></span>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?php echo number_format($data['count']); ?></div>
                                <div class="stat-label"><?php echo esc_html($data['label']); ?></div>
                            </div>
                            <div class="stat-actions">
                                <a href="<?php echo admin_url('edit.php?post_type=' . $post_type); ?>" class="button button-small">
                                    <?php _e('View All', 'happy-place'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Quick Actions -->
                <div class="hph-quick-actions">
                    <h2><?php _e('Quick Actions', 'happy-place'); ?></h2>
                    <div class="action-buttons">
                        <a href="<?php echo admin_url('admin.php?page=happy-place-csv-import'); ?>" class="hph-action-btn csv-import">
                            <span class="dashicons dashicons-upload"></span>
                            <div class="btn-content">
                                <strong><?php _e('Import CSV Data', 'happy-place'); ?></strong>
                                <span><?php _e('Bulk import listings, agents, and more', 'happy-place'); ?></span>
                            </div>
                        </a>

                        <a href="<?php echo admin_url('post-new.php?post_type=listing'); ?>" class="hph-action-btn add-listing">
                            <span class="dashicons dashicons-building"></span>
                            <div class="btn-content">
                                <strong><?php _e('Add New Listing', 'happy-place'); ?></strong>
                                <span><?php _e('Create a new property listing', 'happy-place'); ?></span>
                            </div>
                        </a>

                        <a href="<?php echo admin_url('post-new.php?post_type=agent'); ?>" class="hph-action-btn add-agent">
                            <span class="dashicons dashicons-businessperson"></span>
                            <div class="btn-content">
                                <strong><?php _e('Add New Agent', 'happy-place'); ?></strong>
                                <span><?php _e('Add a real estate agent profile', 'happy-place'); ?></span>
                            </div>
                        </a>

                        <a href="<?php echo admin_url('admin.php?page=happy-place-settings'); ?>" class="hph-action-btn settings">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <div class="btn-content">
                                <strong><?php _e('Plugin Settings', 'happy-place'); ?></strong>
                                <span><?php _e('Configure plugin options', 'happy-place'); ?></span>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="hph-recent-activity">
                    <h2><?php _e('Recent Activity', 'happy-place'); ?></h2>
                    <div class="activity-list">
                        <?php if (!empty($recent_activity)): ?>
                            <?php foreach ($recent_activity as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <span class="dashicons <?php echo esc_attr($activity['icon']); ?>"></span>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title"><?php echo esc_html($activity['title']); ?></div>
                                        <div class="activity-meta">
                                            <?php echo esc_html($activity['meta']); ?>
                                            <span class="activity-time"><?php echo esc_html($activity['time']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-activity"><?php _e('No recent activity found.', 'happy-place'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- System Status -->
                <div class="hph-system-status">
                    <h2><?php _e('System Status', 'happy-place'); ?></h2>
                    <div class="status-list">
                        <?php foreach ($system_status as $item): ?>
                            <div class="status-item status-<?php echo esc_attr($item['status']); ?>">
                                <div class="status-indicator">
                                    <span class="dashicons <?php echo esc_attr($item['icon']); ?>"></span>
                                </div>
                                <div class="status-content">
                                    <div class="status-label"><?php echo esc_html($item['label']); ?></div>
                                    <div class="status-value"><?php echo esc_html($item['value']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Feature Tabs -->
            <div class="hph-feature-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#data-management" class="nav-tab nav-tab-active"><?php _e('Data Management', 'happy-place'); ?></a>
                    <a href="#integrations" class="nav-tab"><?php _e('Integrations', 'happy-place'); ?></a>
                    <a href="#tools" class="nav-tab"><?php _e('Tools', 'happy-place'); ?></a>
                    <a href="#reports" class="nav-tab"><?php _e('Reports', 'happy-place'); ?></a>
                </nav>

                <div class="tab-content">
                    <!-- Data Management Tab -->
                    <div id="data-management" class="tab-panel active">
                        <div class="feature-grid">
                            <div class="feature-card">
                                <h3><?php _e('CSV Import/Export', 'happy-place'); ?></h3>
                                <p><?php _e('Bulk import and export data for all post types.', 'happy-place'); ?></p>
                                <div class="feature-actions">
                                    <a href="<?php echo admin_url('admin.php?page=happy-place-csv-import'); ?>" class="button button-primary"><?php _e('Import Data', 'happy-place'); ?></a>
                                    <a href="#" class="button" id="export-data"><?php _e('Export Data', 'happy-place'); ?></a>
                                </div>
                            </div>

                            <div class="feature-card">
                                <h3><?php _e('Data Cleanup', 'happy-place'); ?></h3>
                                <p><?php _e('Clean up duplicate entries and validate data integrity.', 'happy-place'); ?></p>
                                <div class="feature-actions">
                                    <a href="#" class="button" id="cleanup-duplicates"><?php _e('Find Duplicates', 'happy-place'); ?></a>
                                    <a href="#" class="button" id="validate-data"><?php _e('Validate Data', 'happy-place'); ?></a>
                                </div>
                            </div>

                            <div class="feature-card">
                                <h3><?php _e('Backup & Restore', 'happy-place'); ?></h3>
                                <p><?php _e('Create backups of your data and restore when needed.', 'happy-place'); ?></p>
                                <div class="feature-actions">
                                    <a href="#" class="button" id="create-backup"><?php _e('Create Backup', 'happy-place'); ?></a>
                                    <a href="#" class="button" id="restore-backup"><?php _e('Restore Backup', 'happy-place'); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Integrations Tab -->
                    <div id="integrations" class="tab-panel">
                        <div class="feature-grid">
                            <div class="feature-card">
                                <h3><?php _e('Airtable Integration', 'happy-place'); ?></h3>
                                <p><?php _e('Sync data with Airtable for external collaboration.', 'happy-place'); ?></p>
                                <div class="feature-actions">
                                    <a href="<?php echo admin_url('admin.php?page=happy-place-airtable-settings'); ?>" class="button button-primary"><?php _e('Configure', 'happy-place'); ?></a>
                                    <a href="#" class="button" id="sync-airtable"><?php _e('Sync Now', 'happy-place'); ?></a>
                                </div>
                            </div>

                            <div class="feature-card">
                                <h3><?php _e('MLS Integration', 'happy-place'); ?></h3>
                                <p><?php _e('Connect with MLS services for real-time listing data.', 'happy-place'); ?></p>
                                <div class="feature-actions">
                                    <a href="#" class="button button-primary"><?php _e('Setup MLS', 'happy-place'); ?></a>
                                    <a href="#" class="button" id="sync-mls"><?php _e('Sync Listings', 'happy-place'); ?></a>
                                </div>
                            </div>

                            <div class="feature-card">
                                <h3><?php _e('Email Marketing', 'happy-place'); ?></h3>
                                <p><?php _e('Connect with email marketing services for lead nurturing.', 'happy-place'); ?></p>
                                <div class="feature-actions">
                                    <a href="#" class="button button-primary"><?php _e('Connect Service', 'happy-place'); ?></a>
                                    <a href="#" class="button" id="sync-contacts"><?php _e('Sync Contacts', 'happy-place'); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tools Tab -->
                    <div id="tools" class="tab-panel">
                        <div class="feature-grid">
                            <div class="feature-card">
                                <h3><?php _e('Image Optimization', 'happy-place'); ?></h3>
                                <p><?php _e('Optimize listing images for better performance.', 'happy-place'); ?></p>
                                <div class="feature-actions">
                                    <a href="#" class="button button-primary" id="optimize-images"><?php _e('Optimize All', 'happy-place'); ?></a>
                                    <a href="#" class="button" id="check-images"><?php _e('Check Status', 'happy-place'); ?></a>
                                </div>
                            </div>

                            <div class="feature-card">
                                <h3><?php _e('SEO Optimization', 'happy-place'); ?></h3>
                                <p><?php _e('Optimize listing pages for search engines.', 'happy-place'); ?></p>
                                <div class="feature-actions">
                                    <a href="#" class="button button-primary" id="optimize-seo"><?php _e('Run SEO Check', 'happy-place'); ?></a>
                                    <a href="#" class="button" id="generate-sitemaps"><?php _e('Generate Sitemaps', 'happy-place'); ?></a>
                                </div>
                            </div>

                            <div class="feature-card">
                                <h3><?php _e('Performance Tools', 'happy-place'); ?></h3>
                                <p><?php _e('Monitor and improve plugin performance.', 'happy-place'); ?></p>
                                <div class="feature-actions">
                                    <a href="#" class="button button-primary" id="run-diagnostics"><?php _e('Run Diagnostics', 'happy-place'); ?></a>
                                    <a href="#" class="button" id="clear-cache"><?php _e('Clear Cache', 'happy-place'); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reports Tab -->
                    <div id="reports" class="tab-panel">
                        <div class="feature-grid">
                            <div class="feature-card">
                                <h3><?php _e('Listing Reports', 'happy-place'); ?></h3>
                                <p><?php _e('Generate reports on listing performance and analytics.', 'happy-place'); ?></p>
                                <div class="feature-actions">
                                    <a href="#" class="button button-primary" id="listing-report"><?php _e('Generate Report', 'happy-place'); ?></a>
                                    <a href="#" class="button" id="export-report"><?php _e('Export Data', 'happy-place'); ?></a>
                                </div>
                            </div>

                            <div class="feature-card">
                                <h3><?php _e('Agent Performance', 'happy-place'); ?></h3>
                                <p><?php _e('Track agent activity and performance metrics.', 'happy-place'); ?></p>
                                <div class="feature-actions">
                                    <a href="#" class="button button-primary" id="agent-report"><?php _e('View Metrics', 'happy-place'); ?></a>
                                    <a href="#" class="button" id="export-agent-data"><?php _e('Export Data', 'happy-place'); ?></a>
                                </div>
                            </div>

                            <div class="feature-card">
                                <h3><?php _e('Lead Analytics', 'happy-place'); ?></h3>
                                <p><?php _e('Analyze lead sources and conversion rates.', 'happy-place'); ?></p>
                                <div class="feature-actions">
                                    <a href="#" class="button button-primary" id="lead-analytics"><?php _e('View Analytics', 'happy-place'); ?></a>
                                    <a href="#" class="button" id="export-leads"><?php _e('Export Leads', 'happy-place'); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php
    }

    private function get_post_type_counts()
    {
        $post_types = [
            'listing' => ['label' => 'Listings', 'icon' => 'dashicons-building'],
            'agent' => ['label' => 'Agents', 'icon' => 'dashicons-businessperson'],
            'community' => ['label' => 'Communities', 'icon' => 'dashicons-location-alt'],
            'city' => ['label' => 'Cities', 'icon' => 'dashicons-admin-site-alt3'],
            'transaction' => ['label' => 'Transactions', 'icon' => 'dashicons-money-alt'],
            'open_house' => ['label' => 'Open Houses', 'icon' => 'dashicons-calendar-alt'],
            'local_place' => ['label' => 'Local Places', 'icon' => 'dashicons-store'],
            'team' => ['label' => 'Team Members', 'icon' => 'dashicons-groups']
        ];

        foreach ($post_types as $post_type => &$data) {
            $count = wp_count_posts($post_type);
            $data['count'] = $count->publish ?? 0;
        }

        return $post_types;
    }

    private function get_recent_activity()
    {
        $activities = [];

        // Get recent posts from all custom post types
        $recent_posts = get_posts([
            'numberposts' => 10,
            'post_type' => ['listing', 'agent', 'community', 'city', 'transaction', 'open_house', 'local_place', 'team'],
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ]);

        foreach ($recent_posts as $post) {
            $post_type_obj = get_post_type_object($post->post_type);
            $activities[] = [
                'title' => sprintf(__('New %s: %s', 'happy-place'), $post_type_obj->labels->singular_name, $post->post_title),
                'meta' => sprintf(__('By %s', 'happy-place'), get_the_author_meta('display_name', $post->post_author)),
                'time' => human_time_diff(strtotime($post->post_date), current_time('timestamp')) . ' ago',
                'icon' => $this->get_post_type_icon($post->post_type)
            ];
        }

        return array_slice($activities, 0, 5);
    }

    private function get_system_status()
    {
        return [
            [
                'label' => __('Plugin Version', 'happy-place'),
                'value' => '1.0.0',
                'status' => 'good',
                'icon' => 'dashicons-plugins-checked'
            ],
            [
                'label' => __('WordPress Version', 'happy-place'),
                'value' => get_bloginfo('version'),
                'status' => version_compare(get_bloginfo('version'), '6.0', '>=') ? 'good' : 'warning',
                'icon' => 'dashicons-wordpress'
            ],
            [
                'label' => __('PHP Version', 'happy-place'),
                'value' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, '8.0', '>=') ? 'good' : 'warning',
                'icon' => 'dashicons-admin-tools'
            ],
            [
                'label' => __('Database Status', 'happy-place'),
                'value' => __('Connected', 'happy-place'),
                'status' => 'good',
                'icon' => 'dashicons-database'
            ]
        ];
    }

    private function get_post_type_icon($post_type)
    {
        $icons = [
            'listing' => 'dashicons-building',
            'agent' => 'dashicons-businessperson',
            'community' => 'dashicons-location-alt',
            'city' => 'dashicons-admin-site-alt3',
            'transaction' => 'dashicons-money-alt',
            'open_house' => 'dashicons-calendar-alt',
            'local_place' => 'dashicons-store',
            'team' => 'dashicons-groups'
        ];

        return $icons[$post_type] ?? 'dashicons-admin-post';
    }
}
