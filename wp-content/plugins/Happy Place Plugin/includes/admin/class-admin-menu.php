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
            'manage_options',
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
            'manage_options',
            'happy-place-settings',
            [$this, 'render_settings']
        );

        // Add CSV Import submenu
        add_submenu_page(
            'happy-place',
            'CSV Import',
            'CSV Import',
            'manage_options',
            'happy-place-csv-import',
            [$this, 'render_csv_import']
        );
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
}
