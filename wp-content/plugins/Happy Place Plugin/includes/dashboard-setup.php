<?php

/**
 * Dashboard Setup Functions
 */

function hph_setup_dashboard()
{
    // Create dashboard page if it doesn't exist
    $dashboard_page = array(
        'post_title'    => __('Agent Dashboard', 'happy-place'),
        'post_content'  => __('Agent dashboard page - content managed by template.', 'happy-place'),
        'post_status'   => 'publish',
        'post_type'     => 'page',
        'post_name'     => 'agent-dashboard',
        'page_template' => 'page-templates/agent-dashboard-rebuilt.php'
    );

    $existing_page = get_page_by_path('agent-dashboard');
    if (!$existing_page) {
        $page_id = wp_insert_post($dashboard_page);
        if ($page_id) {
            // Set page template
            update_post_meta($page_id, '_wp_page_template', 'page-templates/agent-dashboard-rebuilt.php');
            // Save page ID for reference
            update_option('hph_dashboard_page_id', $page_id);
        }
    } else {
        // Update existing page to use correct template
        update_post_meta($existing_page->ID, '_wp_page_template', 'page-templates/agent-dashboard-rebuilt.php');
    }

    // Create database tables
    hph_create_dashboard_tables();

    // Set up user roles and capabilities
    hph_setup_user_roles();

    // Register post types and taxonomies
    hph_register_post_types();
    hph_register_taxonomies();

    // Flush rewrite rules
    flush_rewrite_rules();
}

function hph_create_dashboard_tables()
{
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Leads table
    $table_name = $wpdb->prefix . 'hph_leads';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        agent_id bigint(20) NOT NULL,
        name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        phone varchar(50) DEFAULT '',
        status varchar(50) DEFAULT 'new',
        source varchar(50) DEFAULT 'website',
        message text,
        listing_id bigint(20) DEFAULT 0,
        created_date datetime DEFAULT CURRENT_TIMESTAMP,
        updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY agent_id (agent_id),
        KEY status (status),
        KEY created_date (created_date)
    ) $charset_collate;";

    // Open houses table
    $table_name_events = $wpdb->prefix . 'hph_open_houses';
    $sql_events = "CREATE TABLE IF NOT EXISTS $table_name_events (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        agent_id bigint(20) NOT NULL,
        listing_id bigint(20) NOT NULL,
        start_date date NOT NULL,
        start_time time NOT NULL,
        end_time time NOT NULL,
        expected_visitors int DEFAULT 0,
        actual_visitors int DEFAULT 0,
        status varchar(50) DEFAULT 'scheduled',
        notes text,
        created_date datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY agent_id (agent_id),
        KEY listing_id (listing_id),
        KEY start_date (start_date)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    dbDelta($sql_events);
}

function hph_setup_user_roles()
{
    // Add agent role if it doesn't exist
    if (!get_role('agent')) {
        add_role('agent', __('Real Estate Agent', 'happy-place'), [
            'read' => true,
            'edit_posts' => true,
            'edit_published_posts' => true,
            'publish_posts' => true,
            'delete_posts' => true,
            'delete_published_posts' => true,
            'upload_files' => true,
            'edit_listings' => true,
            'publish_listings' => true,
            'delete_listings' => true,
            'manage_open_houses' => true,
            'view_analytics' => true,
            'manage_leads' => true
        ]);
    }

    // Add capabilities to administrator
    // User capabilities are now handled by the core Plugin_Manager
    // This function is kept for compatibility
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_role->add_cap('edit_listings');
        $admin_role->add_cap('publish_listings');
        $admin_role->add_cap('delete_listings');
        $admin_role->add_cap('manage_open_houses');
        $admin_role->add_cap('view_analytics');
        $admin_role->add_cap('manage_leads');
    }
}

function hph_register_post_types()
{
    // Post types are now handled by the core Post_Types class
    // in /includes/core/class-post-types.php
    // This function is kept for compatibility but does nothing
}

function hph_register_taxonomies()
{
    // Taxonomies are now handled by the core Taxonomies class
    // in /includes/core/class-taxonomies.php
    // This function is kept for compatibility but does nothing
}
