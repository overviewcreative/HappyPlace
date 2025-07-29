<?php
/**
 * Theme Manager Class
 *
 * Main theme orchestrator - handles core setup, feature support, and initialization
 *
 * @package HappyPlace
 * @subpackage Core
 */

namespace HappyPlace\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Theme_Manager {
    /**
     * Instance of this class
     *
     * @var Theme_Manager
     */
    private static $instance = null;

    /**
     * Get instance of this class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('after_setup_theme', [$this, 'theme_setup']);
        add_action('widgets_init', [$this, 'register_sidebars']);
        add_filter('excerpt_length', [$this, 'custom_excerpt_length'], 999);
        add_filter('excerpt_more', [$this, 'custom_excerpt_more']);
    }

    /**
     * Setup theme features and support
     */
    public function theme_setup() {
        // Theme support
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('custom-logo');
        add_theme_support('html5', [
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script'
        ]);

        // Menu locations
        register_nav_menus([
            'primary' => __('Primary Menu', 'happy-place'),
            'footer' => __('Footer Menu', 'happy-place'),
            'social' => __('Social Links Menu', 'happy-place'),
        ]);

        // Image sizes
        add_image_size('listing-thumbnail', 400, 300, true);
        add_image_size('listing-gallery', 800, 600, true);
        add_image_size('agent-photo', 300, 300, true);
    }

    /**
     * Register widget areas
     */
    public function register_sidebars() {
        register_sidebar([
            'name'          => __('Primary Sidebar', 'happy-place'),
            'id'            => 'sidebar-1',
            'description'   => __('Main sidebar that appears on the right.', 'happy-place'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h3 class="widget-title">',
            'after_title'   => '</h3>',
        ]);

        register_sidebar([
            'name'          => __('Footer Widget Area', 'happy-place'),
            'id'            => 'footer-widgets',
            'description'   => __('Appears in the footer section of the site.', 'happy-place'),
            'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h4 class="footer-widget-title">',
            'after_title'   => '</h4>',
        ]);
    }

    /**
     * Custom excerpt length
     */
    public function custom_excerpt_length($length) {
        return 20;
    }

    /**
     * Custom excerpt more
     */
    public function custom_excerpt_more($more) {
        return '...';
    }
}
