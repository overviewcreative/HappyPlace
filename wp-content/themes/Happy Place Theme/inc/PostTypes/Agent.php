<?php
/**
 * Agent Post Type
 *
 * Handles the registration and functionality of the Agent post type
 *
 * @package HappyPlace
 * @subpackage PostTypes
 */

namespace HappyPlace\PostTypes;

if (!defined('ABSPATH')) {
    exit;
}

class Agent extends Base_Post_Type {
    /**
     * Post type name
     *
     * @var string
     */
    protected $post_type = 'agent';

    /**
     * Register post type
     */
    public function register() {
        $labels = [
            'name'                  => _x('Agents', 'Post type general name', 'happy-place'),
            'singular_name'         => _x('Agent', 'Post type singular name', 'happy-place'),
            'menu_name'            => _x('Agents', 'Admin Menu text', 'happy-place'),
            'name_admin_bar'       => _x('Agent', 'Add New on Toolbar', 'happy-place'),
            'add_new'              => __('Add New', 'happy-place'),
            'add_new_item'         => __('Add New Agent', 'happy-place'),
            'new_item'             => __('New Agent', 'happy-place'),
            'edit_item'            => __('Edit Agent', 'happy-place'),
            'view_item'            => __('View Agent', 'happy-place'),
            'all_items'            => __('All Agents', 'happy-place'),
            'search_items'         => __('Search Agents', 'happy-place'),
            'parent_item_colon'    => __('Parent Agents:', 'happy-place'),
            'not_found'            => __('No agents found.', 'happy-place'),
            'not_found_in_trash'   => __('No agents found in Trash.', 'happy-place'),
            'featured_image'       => __('Agent Profile Image', 'happy-place'),
            'set_featured_image'   => __('Set profile image', 'happy-place'),
            'remove_featured_image' => __('Remove profile image', 'happy-place'),
            'use_featured_image'   => __('Use as profile image', 'happy-place'),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'           => true,
            'show_in_menu'      => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'agents'],
            'capability_type'   => 'post',
            'has_archive'       => true,
            'hierarchical'      => false,
            'menu_position'     => 6,
            'menu_icon'         => 'dashicons-businessperson',
            'supports'          => [
                'title',
                'editor',
                'thumbnail',
                'excerpt',
                'custom-fields',
                'author'
            ],
            'show_in_rest'      => true,
        ];

        register_post_type($this->post_type, $args);
    }

    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        // Specialties Taxonomy
        $specialty_labels = [
            'name'              => _x('Specialties', 'taxonomy general name', 'happy-place'),
            'singular_name'     => _x('Specialty', 'taxonomy singular name', 'happy-place'),
            'search_items'      => __('Search Specialties', 'happy-place'),
            'all_items'         => __('All Specialties', 'happy-place'),
            'parent_item'       => __('Parent Specialty', 'happy-place'),
            'parent_item_colon' => __('Parent Specialty:', 'happy-place'),
            'edit_item'         => __('Edit Specialty', 'happy-place'),
            'update_item'       => __('Update Specialty', 'happy-place'),
            'add_new_item'      => __('Add New Specialty', 'happy-place'),
            'new_item_name'     => __('New Specialty Name', 'happy-place'),
            'menu_name'         => __('Specialties', 'happy-place'),
        ];

        register_taxonomy('agent_specialty', [$this->post_type], [
            'labels'            => $specialty_labels,
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'specialty'],
        ]);

        // Service Areas Taxonomy
        $area_labels = [
            'name'              => _x('Service Areas', 'taxonomy general name', 'happy-place'),
            'singular_name'     => _x('Service Area', 'taxonomy singular name', 'happy-place'),
            'search_items'      => __('Search Service Areas', 'happy-place'),
            'all_items'         => __('All Service Areas', 'happy-place'),
            'parent_item'       => __('Parent Service Area', 'happy-place'),
            'parent_item_colon' => __('Parent Service Area:', 'happy-place'),
            'edit_item'         => __('Edit Service Area', 'happy-place'),
            'update_item'       => __('Update Service Area', 'happy-place'),
            'add_new_item'      => __('Add New Service Area', 'happy-place'),
            'new_item_name'     => __('New Service Area Name', 'happy-place'),
            'menu_name'         => __('Service Areas', 'happy-place'),
        ];

        register_taxonomy('service_area', [$this->post_type], [
            'labels'            => $area_labels,
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'service-area'],
        ]);
    }

    /**
     * Get singular name
     */
    protected function get_singular_name() {
        return __('Agent', 'happy-place');
    }

    /**
     * Get plural name
     */
    protected function get_plural_name() {
        return __('Agents', 'happy-place');
    }
}
