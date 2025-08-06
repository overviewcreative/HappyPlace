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
        'page_template' => 'agent-dashboard.php'
    );

    $existing_page = get_page_by_path('agent-dashboard');
    if (!$existing_page) {
        $page_id = wp_insert_post($dashboard_page);
        if ($page_id) {
            // Set page template
            update_post_meta($page_id, '_wp_page_template', 'agent-dashboard.php');
            // Save page ID for reference
            update_option('hph_dashboard_page_id', $page_id);
        }
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
    // Only register post types if they don't exist (plugin takes precedence)

    // Listing post type - only register if plugin hasn't already done so
    if (!post_type_exists('listing')) {
        register_post_type('listing', [
            'labels' => [
                'name' => __('Listings', 'happy-place'),
                'singular_name' => __('Listing', 'happy-place'),
                'add_new' => __('Add New Listing', 'happy-place'),
                'add_new_item' => __('Add New Listing', 'happy-place'),
                'edit_item' => __('Edit Listing', 'happy-place'),
                'new_item' => __('New Listing', 'happy-place'),
                'view_item' => __('View Listing', 'happy-place'),
                'search_items' => __('Search Listings', 'happy-place'),
                'not_found' => __('No listings found', 'happy-place'),
                'not_found_in_trash' => __('No listings found in trash', 'happy-place'),
                'menu_name' => __('Listings', 'happy-place')
            ],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-admin-home',
            'supports' => ['title', 'editor', 'thumbnail', 'custom-fields', 'author'],
            'has_archive' => true,
            'rewrite' => ['slug' => 'listings'],
            'capability_type' => 'listing',
            'map_meta_cap' => true
        ]);
    }

    // Open House post type - skip if plugin already registered as 'open-house'
    if (!post_type_exists('open-house') && !post_type_exists('open_house')) {
        // Register as open-house to match plugin convention
        register_post_type('open-house', [
            'labels' => [
                'name' => __('Open Houses', 'happy-place'),
                'singular_name' => __('Open House', 'happy-place'),
                'add_new' => __('Schedule Open House', 'happy-place'),
                'add_new_item' => __('Schedule New Open House', 'happy-place'),
                'edit_item' => __('Edit Open House', 'happy-place'),
                'view_item' => __('View Open House', 'happy-place'),
                'menu_name' => __('Open Houses', 'happy-place')
            ],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=listing',
            'supports' => ['title', 'custom-fields', 'author'],
            'has_archive' => true,
            'rewrite' => ['slug' => 'open-houses'],
            'capability_type' => 'post',
            'map_meta_cap' => true
        ]);
    }
}

function hph_register_taxonomies()
{
    // Property type taxonomy
    register_taxonomy('property_type', 'listing', [
        'labels' => [
            'name' => __('Property Types', 'happy-place'),
            'singular_name' => __('Property Type', 'happy-place'),
            'add_new_item' => __('Add New Property Type', 'happy-place'),
            'edit_item' => __('Edit Property Type', 'happy-place'),
            'menu_name' => __('Property Types', 'happy-place')
        ],
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'property-type']
    ]);

    // Listing location taxonomy
    register_taxonomy('listing_location', 'listing', [
        'labels' => [
            'name' => __('Locations', 'happy-place'),
            'singular_name' => __('Location', 'happy-place'),
            'add_new_item' => __('Add New Location', 'happy-place'),
            'edit_item' => __('Edit Location', 'happy-place'),
            'menu_name' => __('Locations', 'happy-place')
        ],
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'location']
    ]);
}
