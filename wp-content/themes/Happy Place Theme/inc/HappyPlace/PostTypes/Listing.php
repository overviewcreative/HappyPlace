<?php
/**
 * Listing Post Type
 *
 * Handles the registration and functionality of the Listing post type
 *
 * @package HappyPlace
 * @subpackage PostTypes
 */

namespace HappyPlace\PostTypes;

if (!defined('ABSPATH')) {
    exit;
}

class Listing extends Base_Post_Type {
    /**
     * Post type name
     *
     * @var string
     */
    protected $post_type = 'listing';

    /**
     * Register post type
     */
    public function register() {
        $labels = [
            'name'                  => _x('Listings', 'Post type general name', 'happy-place'),
            'singular_name'         => _x('Listing', 'Post type singular name', 'happy-place'),
            'menu_name'            => _x('Listings', 'Admin Menu text', 'happy-place'),
            'name_admin_bar'       => _x('Listing', 'Add New on Toolbar', 'happy-place'),
            'add_new'              => __('Add New', 'happy-place'),
            'add_new_item'         => __('Add New Listing', 'happy-place'),
            'new_item'             => __('New Listing', 'happy-place'),
            'edit_item'            => __('Edit Listing', 'happy-place'),
            'view_item'            => __('View Listing', 'happy-place'),
            'all_items'            => __('All Listings', 'happy-place'),
            'search_items'         => __('Search Listings', 'happy-place'),
            'parent_item_colon'    => __('Parent Listings:', 'happy-place'),
            'not_found'            => __('No listings found.', 'happy-place'),
            'not_found_in_trash'   => __('No listings found in Trash.', 'happy-place'),
            'featured_image'       => __('Listing Cover Image', 'happy-place'),
            'set_featured_image'   => __('Set cover image', 'happy-place'),
            'remove_featured_image' => __('Remove cover image', 'happy-place'),
            'use_featured_image'   => __('Use as cover image', 'happy-place'),
            'archives'             => __('Listing archives', 'happy-place'),
            'insert_into_item'     => __('Insert into listing', 'happy-place'),
            'uploaded_to_this_item' => __('Uploaded to this listing', 'happy-place'),
            'filter_items_list'    => __('Filter listings list', 'happy-place'),
            'items_list_navigation' => __('Listings list navigation', 'happy-place'),
            'items_list'           => __('Listings list', 'happy-place'),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'           => true,
            'show_in_menu'      => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'listings'],
            'capability_type'   => 'post',
            'has_archive'       => true,
            'hierarchical'      => false,
            'menu_position'     => 5,
            'menu_icon'         => 'dashicons-admin-home',
            'supports'          => [
                'title',
                'editor',
                'thumbnail',
                'excerpt',
                'custom-fields',
                'revisions',
                'author'
            ],
            'show_in_rest'      => true,
        ];

        register_post_type($this->post_type, $args);
    }

    /**
     * Register taxonomies
     * 
     * Note: Taxonomies are now handled by the Happy Place Plugin
     * This method is kept for compatibility but does nothing
     */
    public function register_taxonomies() {
        // Taxonomies are now registered by the Happy Place Plugin
        // in /wp-content/plugins/Happy Place Plugin/includes/core/class-taxonomies.php
        // This prevents duplicate registrations and conflicts
    }

    /**
     * Get singular name
     */
    protected function get_singular_name() {
        return __('Listing', 'happy-place');
    }

    /**
     * Get plural name
     */
    protected function get_plural_name() {
        return __('Listings', 'happy-place');
    }
}
