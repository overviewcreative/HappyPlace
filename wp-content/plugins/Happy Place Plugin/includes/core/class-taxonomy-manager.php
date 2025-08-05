<?php
/**
 * Taxonomy Manager
 * 
 * Registers and manages custom taxonomies for the Happy Place plugin
 */

namespace HappyPlace\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Taxonomy_Manager {
    private static ?self $instance = null;

    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        add_action('init', [$this, 'register_taxonomies'], 5);
    }

    /**
     * Register all custom taxonomies
     */
    public function register_taxonomies(): void {
        // Property Type Taxonomy
        register_taxonomy('property_type', ['listing'], [
            'labels' => [
                'name' => 'Property Types',
                'singular_name' => 'Property Type',
                'menu_name' => 'Property Types',
                'all_items' => 'All Property Types',
                'edit_item' => 'Edit Property Type',
                'view_item' => 'View Property Type',
                'update_item' => 'Update Property Type',
                'add_new_item' => 'Add New Property Type',
                'new_item_name' => 'New Property Type Name',
                'search_items' => 'Search Property Types',
                'not_found' => 'No property types found'
            ],
            'public' => true,
            'publicly_queryable' => true,
            'hierarchical' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'property-type', 'with_front' => false],
            'capabilities' => [
                'manage_terms' => 'manage_categories',
                'edit_terms' => 'manage_categories',
                'delete_terms' => 'manage_categories',
                'assign_terms' => 'edit_posts'
            ]
        ]);

        // Property Status Taxonomy
        register_taxonomy('property_status', ['listing'], [
            'labels' => [
                'name' => 'Property Status',
                'singular_name' => 'Status',
                'menu_name' => 'Property Status',
                'all_items' => 'All Statuses',
                'edit_item' => 'Edit Status',
                'view_item' => 'View Status',
                'update_item' => 'Update Status',
                'add_new_item' => 'Add New Status',
                'new_item_name' => 'New Status Name',
                'search_items' => 'Search Statuses',
                'not_found' => 'No statuses found'
            ],
            'public' => true,
            'publicly_queryable' => true,
            'hierarchical' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'status', 'with_front' => false],
            'capabilities' => [
                'manage_terms' => 'manage_categories',
                'edit_terms' => 'manage_categories',
                'delete_terms' => 'manage_categories',
                'assign_terms' => 'edit_posts'
            ]
        ]);

        // Neighborhood Taxonomy
        register_taxonomy('neighborhood', ['listing'], [
            'labels' => [
                'name' => 'Neighborhoods',
                'singular_name' => 'Neighborhood',
                'menu_name' => 'Neighborhoods',
                'all_items' => 'All Neighborhoods',
                'edit_item' => 'Edit Neighborhood',
                'view_item' => 'View Neighborhood',
                'update_item' => 'Update Neighborhood',
                'add_new_item' => 'Add New Neighborhood',
                'new_item_name' => 'New Neighborhood Name',
                'search_items' => 'Search Neighborhoods',
                'not_found' => 'No neighborhoods found'
            ],
            'public' => true,
            'publicly_queryable' => true,
            'hierarchical' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'neighborhood', 'with_front' => false],
            'capabilities' => [
                'manage_terms' => 'manage_categories',
                'edit_terms' => 'manage_categories',
                'delete_terms' => 'manage_categories',
                'assign_terms' => 'edit_posts'
            ]
        ]);

        // City Taxonomy
        register_taxonomy('city', ['listing'], [
            'labels' => [
                'name' => 'Cities',
                'singular_name' => 'City',
                'menu_name' => 'Cities',
                'all_items' => 'All Cities',
                'edit_item' => 'Edit City',
                'view_item' => 'View City',
                'update_item' => 'Update City',
                'add_new_item' => 'Add New City',
                'new_item_name' => 'New City Name',
                'search_items' => 'Search Cities',
                'not_found' => 'No cities found'
            ],
            'public' => true,
            'publicly_queryable' => true,
            'hierarchical' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'city', 'with_front' => false],
            'capabilities' => [
                'manage_terms' => 'manage_categories',
                'edit_terms' => 'manage_categories',
                'delete_terms' => 'manage_categories',
                'assign_terms' => 'edit_posts'
            ]
        ]);

        // Property Features Taxonomy
        register_taxonomy('property_features', ['listing'], [
            'labels' => [
                'name' => 'Property Features',
                'singular_name' => 'Feature',
                'menu_name' => 'Features',
                'all_items' => 'All Features',
                'edit_item' => 'Edit Feature',
                'view_item' => 'View Feature',
                'update_item' => 'Update Feature',
                'add_new_item' => 'Add New Feature',
                'new_item_name' => 'New Feature Name',
                'search_items' => 'Search Features',
                'not_found' => 'No features found',
                'popular_items' => 'Popular Features',
                'separate_items_with_commas' => 'Separate features with commas',
                'add_or_remove_items' => 'Add or remove features',
                'choose_from_most_used' => 'Choose from most used features'
            ],
            'public' => true,
            'publicly_queryable' => true,
            'hierarchical' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'show_tagcloud' => true,
            'rewrite' => ['slug' => 'features', 'with_front' => false],
            'capabilities' => [
                'manage_terms' => 'manage_categories',
                'edit_terms' => 'manage_categories',
                'delete_terms' => 'manage_categories',
                'assign_terms' => 'edit_posts'
            ]
        ]);

        // Insert default terms
        $this->insert_default_terms();
    }

    /**
     * Insert default taxonomy terms
     */
    private function insert_default_terms(): void {
        // Property Types
        $property_types = [
            'Single Family Home',
            'Townhouse',
            'Condo',
            'Multi-Family',
            'Land',
            'Commercial',
            'Mobile Home',
            'Co-op',
            'Farm/Ranch'
        ];

        foreach ($property_types as $type) {
            if (!term_exists($type, 'property_type')) {
                wp_insert_term($type, 'property_type');
            }
        }

        // Property Status
        $statuses = [
            'Active',
            'Pending',
            'Under Contract',
            'Sold',
            'Coming Soon',
            'Withdrawn',
            'Expired',
            'Canceled'
        ];

        foreach ($statuses as $status) {
            if (!term_exists($status, 'property_status')) {
                wp_insert_term($status, 'property_status');
            }
        }

        // Common Property Features
        $features = [
            'Pool',
            'Hot Tub',
            'Waterfront',
            'View',
            'Fireplace',
            'Hardwood Floors',
            'Granite Counters',
            'Stainless Appliances',
            'Walk-in Closet',
            'Master Suite',
            'Home Office',
            'Basement',
            'Garage',
            'Central Air',
            'Forced Air Heat',
            'Solar Panels',
            'Smart Home',
            'Security System',
            'Fenced Yard',
            'Corner Lot'
        ];

        foreach ($features as $feature) {
            if (!term_exists($feature, 'property_features')) {
                wp_insert_term($feature, 'property_features');
            }
        }
    }
}