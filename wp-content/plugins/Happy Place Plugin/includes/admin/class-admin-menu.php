<?php
// File: includes/admin/class-admin-menu.php

namespace HPH\Admin;

class Admin_Menu
{
    private static ?self $instance = null;

    public static function get_instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        add_action('admin_menu', [$this, 'register_menu_pages']);
    }

    public function register_menu_pages(): void
    {
        add_menu_page(
            'Happy Place',
            'Happy Place',
            'read', // Most permissive capability - all logged in users
            'happy-place',
            [$this, 'render_dashboard'],
            'dashicons-building',
            30
        );

        // Add submenus
        add_submenu_page(
            'happy-place',
            'Settings',
            'Settings',
            'read',
            'happy-place-settings',
            [$this, 'render_settings']
        );

        // Add CSV Import submenu
        add_submenu_page(
            'happy-place',
            'CSV Import',
            'CSV Import',
            'read',
            'happy-place-csv-import',
            [$this, 'render_csv_import']
        );

        // Add Integrations submenu
        add_submenu_page(
            'happy-place',
            'Integrations',
            'Integrations',
            'read',
            'happy-place-integrations',
            [$this, 'render_integrations']
        );

        // Add Flyer Generator submenu
        add_submenu_page(
            'happy-place',
            'Flyer Generator',
            'Flyer Generator',
            'read',
            'flyer-generator',
            [$this, 'render_flyer_generator']
        );

        // Add Developer Tools submenu (only for administrators)
        if (current_user_can('manage_options')) {
            add_submenu_page(
                'happy-place',
                'Developer Tools',
                'Developer Tools',
                'manage_options',
                'happy-place-dev-tools',
                [$this, 'render_dev_tools']
            );
        }
    }

    public function render_dashboard(): void
    {
        // Use the comprehensive dashboard
        $dashboard = Admin_Dashboard::get_instance();
        $dashboard->render_main_dashboard();
    }

    public function render_settings(): void
    {
        // Use the comprehensive settings page
        $settings = Settings_Page::get_instance();
        $settings->render_settings_page();
    }

    public function render_csv_import(): void
    {
        // Include the CSV import template
        $template_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'admin/templates/csv-import.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="wrap">';
            echo '<h1>CSV Import</h1>';
            echo '<p>CSV Import template not found.</p>';
            echo '</div>';
        }
    }

    /**
     * Render the integrations page
     */
    public function render_integrations(): void
    {
        $integrations_manager = Integrations_Manager::get_instance();
        $integrations_manager->render_integrations_page();
    }

    /**
     * Render the flyer generator page
     */
    public function render_flyer_generator(): void
    {
        // Initialize the flyer generator class
        if (class_exists('HappyPlace\\Graphics\\Flyer_Generator')) {
            $flyer_generator = \HappyPlace\Graphics\Flyer_Generator::get_instance();
            echo '<div class="wrap">';
            echo '<h1>Flyer Generator</h1>';
            echo $flyer_generator->render_flyer_generator([]);
            echo '</div>';
        } else {
            echo '<div class="wrap">';
            echo '<h1>Flyer Generator</h1>';
            echo '<p>Flyer Generator class not found.</p>';
            echo '</div>';
        }
    }

    /**
     * Render the developer tools page
     */
    public function render_dev_tools(): void
    {
        // Handle actions
        if (isset($_POST['dev_action'])) {
            $this->handle_dev_actions();
        }

        echo '<div class="wrap">';
        echo '<h1>Happy Place Developer Tools</h1>';
        echo '<p>Development utilities for Happy Place theme and plugin.</p>';
        
        // Cache Management Section
        echo '<div class="card" style="max-width: none; margin: 20px 0;">';
        echo '<h2>Cache Management</h2>';
        echo '<table class="form-table">';
        
        echo '<tr>';
        echo '<th scope="row">WordPress Cache</th>';
        echo '<td>';
        echo '<form method="post" style="display: inline;">';
        wp_nonce_field('hph_dev_tools', 'hph_dev_nonce');
        echo '<input type="hidden" name="dev_action" value="flush_cache">';
        echo '<input type="submit" class="button button-secondary" value="Flush WordPress Cache">';
        echo '</form>';
        echo '<p class="description">Clears WordPress object cache and transients.</p>';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row">Rewrite Rules</th>';
        echo '<td>';
        echo '<form method="post" style="display: inline;">';
        wp_nonce_field('hph_dev_tools', 'hph_dev_nonce');
        echo '<input type="hidden" name="dev_action" value="flush_rewrite">';
        echo '<input type="submit" class="button button-secondary" value="Flush Rewrite Rules">';
        echo '</form>';
        echo '<p class="description">Regenerates permalink structure and rewrite rules.</p>';
        echo '</td>';
        echo '</tr>';

        echo '</table>';
        echo '</div>';

        // Build Tools Section
        echo '<div class="card" style="max-width: none; margin: 20px 0;">';
        echo '<h2>Build Tools</h2>';
        echo '<table class="form-table">';
        
        echo '<tr>';
        echo '<th scope="row">Theme Assets</th>';
        echo '<td>';
        echo '<form method="post" style="display: inline;">';
        wp_nonce_field('hph_dev_tools', 'hph_dev_nonce');
        echo '<input type="hidden" name="dev_action" value="build_sass">';
        echo '<input type="submit" class="button button-primary" value="Build Sass">';
        echo '</form>';
        echo ' ';
        echo '<form method="post" style="display: inline;">';
        wp_nonce_field('hph_dev_tools', 'hph_dev_nonce');
        echo '<input type="hidden" name="dev_action" value="build_webpack">';
        echo '<input type="submit" class="button button-primary" value="Build Webpack">';
        echo '</form>';
        echo '<p class="description">Compile theme Sass and JavaScript assets.</p>';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row">Plugin Assets</th>';
        echo '<td>';
        echo '<form method="post" style="display: inline;">';
        wp_nonce_field('hph_dev_tools', 'hph_dev_nonce');
        echo '<input type="hidden" name="dev_action" value="build_plugin">';
        echo '<input type="submit" class="button button-primary" value="Build Plugin Assets">';
        echo '</form>';
        echo '<p class="description">Compile plugin JavaScript and CSS assets.</p>';
        echo '</td>';
        echo '</tr>';

        echo '</table>';
        echo '</div>';

        // Database Tools Section
        echo '<div class="card" style="max-width: none; margin: 20px 0;">';
        echo '<h2>Database Tools</h2>';
        echo '<table class="form-table">';
        
        echo '<tr>';
        echo '<th scope="row">Database Optimization</th>';
        echo '<td>';
        echo '<form method="post" style="display: inline;">';
        wp_nonce_field('hph_dev_tools', 'hph_dev_nonce');
        echo '<input type="hidden" name="dev_action" value="optimize_db">';
        echo '<input type="submit" class="button button-secondary" value="Optimize Database" onclick="return confirm(\'Are you sure? This will optimize database tables.\');">';
        echo '</form>';
        echo '<p class="description">Optimize database tables for better performance.</p>';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row">Clear Expired Transients</th>';
        echo '<td>';
        echo '<form method="post" style="display: inline;">';
        wp_nonce_field('hph_dev_tools', 'hph_dev_nonce');
        echo '<input type="hidden" name="dev_action" value="clear_transients">';
        echo '<input type="submit" class="button button-secondary" value="Clear Transients">';
        echo '</form>';
        echo '<p class="description">Remove expired transient cache entries.</p>';
        echo '</td>';
        echo '</tr>';

        echo '</table>';
        echo '</div>';

        // Development Utilities Section
        echo '<div class="card" style="max-width: none; margin: 20px 0;">';
        echo '<h2>Development Utilities</h2>';
        echo '<table class="form-table">';
        
        echo '<tr>';
        echo '<th scope="row">Debug Mode</th>';
        echo '<td>';
        $debug_mode = defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled';
        echo '<span class="description">Current Status: <strong>' . $debug_mode . '</strong></span>';
        echo '<p class="description">Debug mode is controlled by WP_DEBUG in wp-config.php</p>';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row">Environment Info</th>';
        echo '<td>';
        echo '<form method="post" style="display: inline;">';
        wp_nonce_field('hph_dev_tools', 'hph_dev_nonce');
        echo '<input type="hidden" name="dev_action" value="show_env_info">';
        echo '<input type="submit" class="button button-secondary" value="Show Environment Info">';
        echo '</form>';
        echo '<p class="description">Display PHP version, WordPress version, and system information.</p>';
        echo '</td>';
        echo '</tr>';

        echo '</table>';
        echo '</div>';

        echo '</div>';
    }

    /**
     * Handle developer tool actions
     */
    private function handle_dev_actions(): void
    {
        if (!wp_verify_nonce($_POST['hph_dev_nonce'], 'hph_dev_tools')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $action = sanitize_text_field($_POST['dev_action']);
        $theme_path = get_template_directory();
        $plugin_path = plugin_dir_path(dirname(dirname(__FILE__)));

        switch ($action) {
            case 'flush_cache':
                // Clear WordPress object cache
                wp_cache_flush();
                
                // Clear transients
                $this->clear_expired_transients();
                
                // Clear W3 Total Cache if available
                if (function_exists('w3tc_flush_all')) {
                    \w3tc_flush_all();
                }
                
                // Clear WP Rocket cache if available  
                if (function_exists('rocket_clean_domain')) {
                    \rocket_clean_domain();
                }
                
                // Clear LiteSpeed cache if available
                if (class_exists('LiteSpeed\\Purge')) {
                    \LiteSpeed\Purge::purge_all();
                }
                
                $this->show_admin_notice('WordPress cache flushed successfully!', 'success');
                break;

            case 'flush_rewrite':
                flush_rewrite_rules(true);
                $this->show_admin_notice('Rewrite rules flushed successfully!', 'success');
                break;

            case 'build_sass':
                $output = $this->run_build_command($theme_path, 'npm run build:sass');
                $this->show_admin_notice('Sass build completed. ' . $output, 'success');
                break;

            case 'build_webpack':
                $output = $this->run_build_command($theme_path, 'npm run build');
                $this->show_admin_notice('Webpack build completed. ' . $output, 'success');
                break;

            case 'build_plugin':
                $output = $this->run_build_command($plugin_path, 'npm run build');
                $this->show_admin_notice('Plugin build completed. ' . $output, 'success');
                break;

            case 'optimize_db':
                global $wpdb;
                $tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);
                $optimized = 0;
                foreach ($tables as $table) {
                    $wpdb->query("OPTIMIZE TABLE {$table[0]}");
                    $optimized++;
                }
                $this->show_admin_notice("Database optimized! {$optimized} tables processed.", 'success');
                break;

            case 'clear_transients':
                $this->clear_expired_transients();
                $this->show_admin_notice('Expired transients cleared successfully!', 'success');
                break;

            case 'show_env_info':
                $this->display_environment_info();
                break;

            default:
                $this->show_admin_notice('Unknown action.', 'error');
        }
    }

    /**
     * Run build command in specified directory
     */
    private function run_build_command(string $directory, string $command): string
    {
        if (!is_dir($directory)) {
            return 'Directory not found.';
        }

        $old_dir = getcwd();
        chdir($directory);
        
        $output = [];
        $return_code = 0;
        exec($command . ' 2>&1', $output, $return_code);
        
        chdir($old_dir);
        
        if ($return_code === 0) {
            return 'Build successful.';
        } else {
            return 'Build failed: ' . implode(' ', array_slice($output, -3));
        }
    }

    /**
     * Clear expired transients from database
     */
    private function clear_expired_transients(): void
    {
        global $wpdb;
        
        // Clear expired transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%' AND option_value < UNIX_TIMESTAMP()");
        
        // Clear orphaned transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%' AND option_name NOT LIKE '_transient_timeout_%' AND NOT EXISTS (SELECT 1 FROM {$wpdb->options} t2 WHERE t2.option_name = CONCAT('_transient_timeout_', SUBSTRING({$wpdb->options}.option_name, 12)))");
    }

    /**
     * Display environment information
     */
    private function display_environment_info(): void
    {
        echo '<div class="notice notice-info"><p><strong>Environment Information:</strong></p>';
        echo '<ul>';
        echo '<li><strong>PHP Version:</strong> ' . PHP_VERSION . '</li>';
        echo '<li><strong>WordPress Version:</strong> ' . get_bloginfo('version') . '</li>';
        echo '<li><strong>Theme:</strong> ' . get_template() . '</li>';
        echo '<li><strong>Memory Limit:</strong> ' . ini_get('memory_limit') . '</li>';
        echo '<li><strong>Max Execution Time:</strong> ' . ini_get('max_execution_time') . 's</li>';
        echo '<li><strong>Upload Max Size:</strong> ' . ini_get('upload_max_filesize') . '</li>';
        echo '<li><strong>Server Software:</strong> ' . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . '</li>';
        echo '</ul></div>';
    }

    /**
     * Show admin notice
     */
    private function show_admin_notice(string $message, string $type = 'info'): void
    {
        add_action('admin_notices', function() use ($message, $type) {
            echo "<div class='notice notice-{$type} is-dismissible'><p>{$message}</p></div>";
        });
    }
}
