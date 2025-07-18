<?php

namespace HappyPlace\Listings;

use HappyPlace\Listings\Helper;
use HappyPlace\Listings\Display;

/**
 * Listing Module Class
 * 
 * Central controller for all listing-related functionality
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Module {
    private static ?self $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }

    public function init_hooks(): void {
        add_action('pre_get_posts', [$this, 'modify_listings_query']);
        add_filter('single_template', [$this, 'load_listing_template']);
        add_filter('archive_template', [$this, 'load_listings_archive_template']);
        
        // Initialize components
        add_action('init', [$this, 'init_components'], 20);
    }

    private function load_dependencies(): void {
        // These are now autoloaded via namespace
        Helper::instance();
        
        // Legacy compatibility is handled by Migration_Helper
    }

    public function init_components(): void {
        // Initialize utilities
        if (class_exists('HappyPlace\\Utilities\\Geocoding')) {
            \HappyPlace\Utilities\Geocoding::instance();
        }
        
        // Initialize admin tools
        if (is_admin() && class_exists('HappyPlace\\Admin\\Listing_Tools')) {
            \HappyPlace\Admin\Listing_Tools::instance();
        }
    }

    public function modify_listings_query($query): void {
        if (!is_admin() && $query->is_main_query() && $query->is_post_type_archive('listing')) {
            $query->set('posts_per_page', 12);
            $query->set('orderby', 'date');
            $query->set('order', 'DESC');
            
            // Handle custom filtering
            $this->apply_listing_filters($query);
        }
    }

    private function apply_listing_filters($query): void {
        $meta_query = [];
        
        // Price range filter
        if (!empty($_GET['price_min']) || !empty($_GET['price_max'])) {
            $price_query = ['key' => 'price', 'type' => 'NUMERIC'];
            
            if (!empty($_GET['price_min'])) {
                $price_query['value'][] = intval($_GET['price_min']);
                $price_query['compare'] = '>=';
            }
            
            if (!empty($_GET['price_max'])) {
                if (!empty($_GET['price_min'])) {
                    $price_query['value'] = [intval($_GET['price_min']), intval($_GET['price_max'])];
                    $price_query['compare'] = 'BETWEEN';
                } else {
                    $price_query['value'] = intval($_GET['price_max']);
                    $price_query['compare'] = '<=';
                }
            }
            
            $meta_query[] = $price_query;
        }
        
        // Bedrooms filter
        if (!empty($_GET['bedrooms'])) {
            $meta_query[] = [
                'key' => 'bedrooms',
                'value' => intval($_GET['bedrooms']),
                'compare' => '>='
            ];
        }
        
        // Bathrooms filter
        if (!empty($_GET['bathrooms'])) {
            $meta_query[] = [
                'key' => 'full_bathrooms',
                'value' => intval($_GET['bathrooms']),
                'compare' => '>='
            ];
        }
        
        if (!empty($meta_query)) {
            $query->set('meta_query', $meta_query);
        }
        
        // Handle taxonomy filters
        $tax_query = [];
        
        if (!empty($_GET['property_type'])) {
            $tax_query[] = [
                'taxonomy' => 'property_type',
                'field' => 'slug',
                'terms' => sanitize_text_field($_GET['property_type'])
            ];
        }
        
        if (!empty($_GET['location'])) {
            $tax_query[] = [
                'taxonomy' => 'location',
                'field' => 'slug',
                'terms' => sanitize_text_field($_GET['location'])
            ];
        }
        
        if (!empty($tax_query)) {
            $query->set('tax_query', $tax_query);
        }
    }

    public function load_listing_template($single_template): string {
        global $post;

        if ($post->post_type === 'listing') {
            $template = locate_template([
                'templates/listing/single-listing.php',
                'single-listing.php'
            ]);
            
            if ($template) {
                return $template;
            }
        }

        return $single_template;
    }

    public function load_listings_archive_template($archive_template): string {
        if (is_post_type_archive('listing')) {
            $template = locate_template([
                'templates/listing/archive-listing.php',
                'archive-listing.php'
            ]);
            
            if ($template) {
                return $template;
            }
        }

        return $archive_template;
    }

    /**
     * Get listings for display
     */
    public function get_listings(array $args = []): array {
        $defaults = [
            'post_type' => 'listing',
            'post_status' => 'publish',
            'posts_per_page' => 12,
            'orderby' => 'date',
            'order' => 'DESC'
        ];

        $args = wp_parse_args($args, $defaults);
        $query = new \WP_Query($args);

        $listings = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $listings[] = Helper::instance()->get_listing_data(get_the_ID());
            }
            wp_reset_postdata();
        }

        return $listings;
    }

    /**
     * Get map markers for all published listings
     */
    public function get_map_markers(array $filters = []): array {
        $args = [
            'post_type' => 'listing',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'latitude',
                    'compare' => 'EXISTS'
                ],
                [
                    'key' => 'longitude',
                    'compare' => 'EXISTS'
                ],
                [
                    'key' => 'latitude',
                    'value' => '',
                    'compare' => '!='
                ],
                [
                    'key' => 'longitude',
                    'value' => '',
                    'compare' => '!='
                ]
            ]
        ];

        // Apply filters if provided
        if (!empty($filters)) {
            // Add filter logic here similar to modify_listings_query
        }

        $query = new \WP_Query($args);
        $markers = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $marker_data = Helper::instance()->get_map_data(get_the_ID());
                if ($marker_data) {
                    $markers[] = $marker_data;
                }
            }
            wp_reset_postdata();
        }

        return $markers;
    }
}
