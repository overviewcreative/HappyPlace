<?php
/**
 * Theme Setup Class
 *
 * @package HappyPlace
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Happy_Place_Theme_Setup {
    public function __construct() {
        add_action( 'after_setup_theme', array( $this, 'setup_theme' ) );
    }

    public function setup_theme() {
        // Add theme support
        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );
        add_theme_support( 'html5', array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script'
        ) );

        // Register navigation menus
        register_nav_menus( array(
            'primary' => esc_html__( 'Primary Menu', 'happy-place' ),
            'footer'  => esc_html__( 'Footer Menu', 'happy-place' ),
        ) );
    }
}
