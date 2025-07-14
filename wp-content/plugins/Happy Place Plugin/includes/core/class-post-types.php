<?php

/**
 * File: includes/core/class-post-types.php
 */

namespace HappyPlace\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Post_Types
{
    private static ?self $instance = null;

    public static function get_instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // Initialize template support
        $this->init_template_support();

        // If init has already fired, register immediately
        if (did_action('init')) {
            $this->register_post_types();
        } else {
            // Register post types on init with priority 5 (after taxonomies typically register at 0)
            add_action('init', [$this, 'register_post_types'], 5);
        }

        // Set up activation hook handler
        add_action('happy_place_activated', [$this, 'flush_rules_on_activation']);

        error_log('HPH: Post_Types constructor completed');
    }

    public static function initialize(): void
    {
        error_log('HPH: Post_Types::initialize() called');
        self::get_instance();
    }

    /**
     * Handle rewrite rules flushing on activation
     */
    public function flush_rules_on_activation(): void
    {
        // Register post types first
        $this->register_post_types();
        // Then flush the rules
        error_log('HPH: Flushing rewrite rules on activation');
        flush_rewrite_rules();
    }

    public function register_post_types(): void
    {
        if (!did_action('init')) {
            error_log('HPH ERROR: register_post_types() called too early, before init!');
            return;
        }

        error_log('HPH: register_post_types() called');

        if (post_type_exists('listing')) {
            error_log('HPH: Listing post type already registered');
            return;
        }

        // MAIN: Listing post type (primary property listings)
        $listing_registered = register_post_type('listing', [
            'labels' => [
                'name'               => __('Listings', 'happy-place'),
                'singular_name'      => __('Listing', 'happy-place'),
                'menu_name'         => __('Listings', 'happy-place'),
                'add_new'           => __('Add New', 'happy-place'),
                'add_new_item'      => __('Add New Listing', 'happy-place'),
                'edit_item'         => __('Edit Listing', 'happy-place'),
                'new_item'          => __('New Listing', 'happy-place'),
                'view_item'         => __('View Listing', 'happy-place'),
                'search_items'      => __('Search Listings', 'happy-place'),
                'not_found'         => __('No listings found', 'happy-place'),
                'not_found_in_trash' => __('No listings found in Trash', 'happy-place'),
            ],
            'public'              => true,
            'has_archive'         => true,
            'show_in_rest'        => true,
            'supports'            => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
            'menu_icon'           => 'dashicons-building',
            'rewrite'            => ['slug' => 'listings'],
            'capability_type'    => 'post',
            'show_in_nav_menus'  => true,
            'show_in_admin_bar'  => true,
            'menu_position'      => 5,
        ]);

        // Agent post type
        $agent_registered = register_post_type('agent', [
            'labels' => [
                'name'               => __('Agents', 'happy-place'),
                'singular_name'      => __('Agent', 'happy-place'),
                'menu_name'         => __('Agents', 'happy-place'),
                'add_new'           => __('Add New', 'happy-place'),
                'add_new_item'      => __('Add New Agent', 'happy-place'),
                'edit_item'         => __('Edit Agent', 'happy-place'),
                'new_item'          => __('New Agent', 'happy-place'),
                'view_item'         => __('View Agent', 'happy-place'),
                'search_items'      => __('Search Agents', 'happy-place'),
                'not_found'         => __('No agents found', 'happy-place'),
                'not_found_in_trash' => __('No agents found in Trash', 'happy-place'),
            ],
            'public'              => true,
            'has_archive'         => true,
            'show_in_rest'        => true,
            'supports'            => ['title', 'editor', 'thumbnail', 'excerpt'],
            'menu_icon'           => 'dashicons-businessperson',
            'rewrite'            => ['slug' => 'agents'],
            'capability_type'    => 'post',
            'show_in_nav_menus'  => true,
            'show_in_admin_bar'  => true,
            'menu_position'      => 6,
        ]);

        // Community post type
        $community_registered = register_post_type('community', [
            'labels' => [
                'name'               => __('Communities', 'happy-place'),
                'singular_name'      => __('Community', 'happy-place'),
                'menu_name'         => __('Communities', 'happy-place'),
                'add_new'           => __('Add New', 'happy-place'),
                'add_new_item'      => __('Add New Community', 'happy-place'),
                'edit_item'         => __('Edit Community', 'happy-place'),
                'new_item'          => __('New Community', 'happy-place'),
                'view_item'         => __('View Community', 'happy-place'),
                'search_items'      => __('Search Communities', 'happy-place'),
                'not_found'         => __('No communities found', 'happy-place'),
                'not_found_in_trash' => __('No communities found in Trash', 'happy-place'),
            ],
            'public'              => true,
            'has_archive'         => true,
            'show_in_rest'        => true,
            'supports'            => ['title', 'editor', 'thumbnail', 'excerpt'],
            'menu_icon'           => 'dashicons-location',
            'rewrite'            => ['slug' => 'communities'],
            'capability_type'    => 'post',
            'show_in_nav_menus'  => true,
            'show_in_admin_bar'  => true,
            'menu_position'      => 7,
        ]);

        // City post type
        $city_registered = register_post_type('city', [
            'labels' => [
                'name'               => __('Cities', 'happy-place'),
                'singular_name'      => __('City', 'happy-place'),
                'menu_name'         => __('Cities', 'happy-place'),
                'add_new'           => __('Add New', 'happy-place'),
                'add_new_item'      => __('Add New City', 'happy-place'),
                'edit_item'         => __('Edit City', 'happy-place'),
                'new_item'          => __('New City', 'happy-place'),
                'view_item'         => __('View City', 'happy-place'),
                'search_items'      => __('Search Cities', 'happy-place'),
                'not_found'         => __('No cities found', 'happy-place'),
                'not_found_in_trash' => __('No cities found in Trash', 'happy-place'),
            ],
            'public'              => true,
            'has_archive'         => true,
            'show_in_rest'        => true,
            'supports'            => ['title', 'editor', 'thumbnail', 'excerpt'],
            'menu_icon'           => 'dashicons-location-alt',
            'rewrite'            => ['slug' => 'cities'],
            'capability_type'    => 'post',
            'show_in_nav_menus'  => true,
            'show_in_admin_bar'  => true,
            'menu_position'      => 8,
            'capabilities'       => $this->get_post_type_capabilities('city'),
        ]);

        // Transaction post type (admin only)
        $transaction_registered = register_post_type('transaction', [
            'labels' => [
                'name'               => __('Transactions', 'happy-place'),
                'singular_name'      => __('Transaction', 'happy-place'),
                'menu_name'         => __('Transactions', 'happy-place'),
                'add_new'           => __('Add New', 'happy-place'),
                'add_new_item'      => __('Add New Transaction', 'happy-place'),
                'edit_item'         => __('Edit Transaction', 'happy-place'),
                'new_item'          => __('New Transaction', 'happy-place'),
                'view_item'         => __('View Transaction', 'happy-place'),
                'search_items'      => __('Search Transactions', 'happy-place'),
                'not_found'         => __('No transactions found', 'happy-place'),
                'not_found_in_trash' => __('No transactions found in Trash', 'happy-place'),
            ],
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_rest'        => true,
            'supports'            => ['title', 'editor'],
            'menu_icon'           => 'dashicons-money-alt',
            'rewrite'            => ['slug' => 'transactions'],
            'capability_type'    => 'post',
            'menu_position'      => 9,
        ]);

        // Open Houses post type
        $open_house_registered = register_post_type('open-house', [
            'labels' => [
                'name'               => __('Open Houses', 'happy-place'),
                'singular_name'      => __('Open House', 'happy-place'),
                'menu_name'         => __('Open Houses', 'happy-place'),
                'add_new'           => __('Add New', 'happy-place'),
                'add_new_item'      => __('Add New Open House', 'happy-place'),
                'edit_item'         => __('Edit Open House', 'happy-place'),
                'new_item'          => __('New Open House', 'happy-place'),
                'view_item'         => __('View Open House', 'happy-place'),
                'search_items'      => __('Search Open Houses', 'happy-place'),
                'not_found'         => __('No open houses found', 'happy-place'),
                'not_found_in_trash' => __('No open houses found in Trash', 'happy-place'),
            ],
            'public'              => true,
            'has_archive'         => true,
            'show_in_rest'        => true,
            'supports'            => ['title', 'editor', 'thumbnail'],
            'menu_icon'           => 'dashicons-calendar-alt',
            'rewrite'            => ['slug' => 'open-houses'],
            'capability_type'    => 'post',
            'show_in_nav_menus'  => true,
            'show_in_admin_bar'  => true,
            'menu_position'      => 9,
        ]);

        // Local Places post type (businesses, restaurants, etc.)
        $local_place_registered = register_post_type('local-place', [
            'labels' => [
                'name'               => __('Local Places', 'happy-place'),
                'singular_name'      => __('Local Place', 'happy-place'),
                'menu_name'         => __('Local Places', 'happy-place'),
                'add_new'           => __('Add New', 'happy-place'),
                'add_new_item'      => __('Add New Local Place', 'happy-place'),
                'edit_item'         => __('Edit Local Place', 'happy-place'),
                'new_item'          => __('New Local Place', 'happy-place'),
                'view_item'         => __('View Local Place', 'happy-place'),
                'search_items'      => __('Search Local Places', 'happy-place'),
                'not_found'         => __('No local places found', 'happy-place'),
                'not_found_in_trash' => __('No local places found in Trash', 'happy-place'),
            ],
            'public'              => true,
            'has_archive'         => true,
            'show_in_rest'        => true,
            'supports'            => ['title', 'editor', 'thumbnail', 'excerpt'],
            'menu_icon'           => 'dashicons-location',
            'rewrite'            => ['slug' => 'local-places'],
            'capability_type'    => 'post',
            'show_in_nav_menus'  => true,
            'show_in_admin_bar'  => true,
            'menu_position'      => 10,
        ]);

        // Team post type
        $team_registered = register_post_type('team', [
            'labels' => [
                'name'               => __('Teams', 'happy-place'),
                'singular_name'      => __('Team', 'happy-place'),
                'menu_name'         => __('Teams', 'happy-place'),
                'add_new'           => __('Add New', 'happy-place'),
                'add_new_item'      => __('Add New Team', 'happy-place'),
                'edit_item'         => __('Edit Team', 'happy-place'),
                'new_item'          => __('New Team', 'happy-place'),
                'view_item'         => __('View Team', 'happy-place'),
                'search_items'      => __('Search Teams', 'happy-place'),
                'not_found'         => __('No teams found', 'happy-place'),
                'not_found_in_trash' => __('No teams found in Trash', 'happy-place'),
            ],
            'public'              => true,
            'has_archive'         => true,
            'show_in_rest'        => true,
            'supports'            => ['title', 'editor', 'thumbnail', 'excerpt'],
            'menu_icon'           => 'dashicons-groups',
            'rewrite'            => ['slug' => 'teams'],
            'capability_type'    => 'post',
            'show_in_nav_menus'  => true,
            'show_in_admin_bar'  => true,
            'menu_position'      => 11,
        ]);

        // Log registration results with detailed information
        $registered_types = get_post_types(['_builtin' => false], 'names');
        error_log('HPH Debug: Currently registered post types: ' . print_r($registered_types, true));

        // Check each post type registration
        $post_types_to_check = [
            'listing',
            'agent',
            'community',
            'city',
            'transaction',
            'open-house',
            'local-place',
            'team'
        ];

        foreach ($post_types_to_check as $type) {
            if (post_type_exists($type)) {
                error_log(sprintf('HPH: Post type %s registered successfully', $type));
            } else {
                error_log(sprintf('HPH ERROR: Post type %s failed to register', $type));
            }
        }

        // Verify post types are registered
        $registered_types = get_post_types(['_builtin' => false], 'objects');
        // Removed loop over undefined $registration_results variable.

        // Log current post type capabilities
        foreach ($registered_types as $type => $object) {
            $post_type_obj = get_post_type_object($type);
            if ($post_type_obj) {
                error_log(sprintf(
                    'HPH Debug: Post type %s capabilities: %s',
                    $type,
                    print_r($post_type_obj->cap, true)
                ));
            }
        }

        if (is_wp_error($community_registered)) {
            error_log('HPH: Error registering community post type: ' . $community_registered->get_error_message());
        } else {
            error_log('HPH: Community post type registered successfully');
        }

        if (is_wp_error($city_registered)) {
            error_log('HPH: Error registering city post type: ' . $city_registered->get_error_message());
        } else {
            error_log('HPH: City post type registered successfully');
        }

        if (is_wp_error($transaction_registered)) {
            error_log('HPH: Error registering transaction post type: ' . $transaction_registered->get_error_message());
        } else {
            error_log('HPH: Transaction post type registered successfully');
        }

        if (is_wp_error($open_house_registered)) {
            error_log('HPH: Error registering open house post type: ' . $open_house_registered->get_error_message());
        } else {
            error_log('HPH: Open house post type registered successfully');
        }

        if (is_wp_error($local_place_registered)) {
            error_log('HPH: Error registering local place post type: ' . $local_place_registered->get_error_message());
        } else {
            error_log('HPH: Local place post type registered successfully');
        }

        if (is_wp_error($team_registered)) {
            error_log('HPH: Error registering team post type: ' . $team_registered->get_error_message());
        } else {
            error_log('HPH: Team post type registered successfully');
        }

        // Log all registered post types
        $registered_types = get_post_types(['_builtin' => false], 'names');
        error_log('HPH: All registered custom post types: ' . implode(', ', $registered_types));
    }

    /**
     * Get post type template configuration
     */
    private function get_post_type_template_config(): array
    {
        return [
            'listing' => [
                'has_archive' => true,
                'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions'],
                'template_path' => 'templates/listing/'
            ],
            'agent' => [
                'has_archive' => true,
                'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
                'template_path' => 'templates/agent/'
            ],
            'community' => [
                'has_archive' => true,
                'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
                'template_path' => 'templates/community/'
            ],
            'city' => [
                'has_archive' => true,
                'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
                'template_path' => 'templates/city/'
            ],
            'transaction' => [
                'has_archive' => true,
                'supports' => ['title', 'custom-fields', 'revisions'],
                'template_path' => 'templates/transaction/'
            ],
            'open-house' => [
                'has_archive' => true,
                'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
                'template_path' => 'templates/open-house/'
            ],
            'local-place' => [
                'has_archive' => true,
                'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
                'template_path' => 'templates/local-place/'
            ],
            'team' => [
                'has_archive' => true,
                'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
                'template_path' => 'templates/team/'
            ]
        ];
    }

    /**
     * Add template paths to template loader
     */
    public function add_template_paths($paths)
    {
        $config = $this->get_post_type_template_config();
        foreach ($config as $post_type => $settings) {
            if (isset($settings['template_path'])) {
                $paths[] = $settings['template_path'];
            }
        }
        return $paths;
    }

    /**
     * Initialize template support
     */
    private function init_template_support(): void
    {
        add_filter('happy_place_template_paths', [$this, 'add_template_paths']);
    }

    /**
     * Get default capabilities for post types
     */
    private function get_default_capabilities(): array
    {
        return [
            'edit_post' => 'edit_post',
            'read_post' => 'read_post',
            'delete_post' => 'delete_post',
            'edit_posts' => 'edit_posts',
            'edit_others_posts' => 'edit_others_posts',
            'delete_posts' => 'delete_posts',
            'publish_posts' => 'publish_posts',
            'read_private_posts' => 'read_private_posts',
            'read' => 'read',
            'delete_private_posts' => 'delete_private_posts',
            'delete_published_posts' => 'delete_published_posts',
            'delete_others_posts' => 'delete_others_posts',
            'edit_private_posts' => 'edit_private_posts',
            'edit_published_posts' => 'edit_published_posts',
            'create_posts' => 'edit_posts'
        ];
    }

    /**
     * Get capabilities for a specific post type
     */
    private function get_post_type_capabilities(string $post_type): array
    {
        $default_caps = $this->get_default_capabilities();

        // For ACF post types, restrict to admins
        if (strpos($post_type, 'acf-') === 0) {
            return array_map(function ($cap) {
                return 'manage_options';
            }, $default_caps);
        }

        // Apply filters to allow customization
        return apply_filters(
            "hph_{$post_type}_capabilities",
            $default_caps,
            $post_type
        );
    }
}
