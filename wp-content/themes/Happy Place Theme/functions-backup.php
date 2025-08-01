<?php
/**
 * Happy Place Theme - Streamlined Functions
 * Modern architecture with single Asset_Manager system
 * 
<?php
/**
 * Happy Place Theme Functions
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define theme constants
define('HPH_THEME_VERSION', '2.0.0');
define('HPH_THEME_PATH', get_template_directory());
define('HPH_THEME_URL', get_template_directory_uri());

// Load theme manager (single point of initialization)
require_once HPH_THEME_PATH . '/inc/core/class-theme-manager.php';

// Initialize theme
add_action('after_setup_theme', function() {
    if (class_exists('HappyPlace\Core\Theme_Manager')) {
        HappyPlace\Core\Theme_Manager::get_instance();
    }
});

// Emergency fallback for critical functions when plugin is inactive
if (!function_exists('hph_bridge_get_listing_data')) {
    function hph_bridge_get_listing_data($listing_id) {
        return [
            'id' => $listing_id,
            'title' => get_the_title($listing_id),
            'price' => 0,
            'status' => 'available'
        ];
    }
}

if (!function_exists('hph_bridge_get_agent_profile')) {
    function hph_bridge_get_agent_profile($agent_id) {
        return [
            'id' => $agent_id,
            'name' => get_the_title($agent_id),
            'email' => '',
            'phone' => ''
        ];
    }
}

// Asset management emergency fallback
if (!function_exists('hph_enqueue_template_assets')) {
    function hph_enqueue_template_assets($template_name) {
        // Fallback - load main styles at minimum
        wp_enqueue_style('hph-main', HPH_THEME_URL . '/assets/dist/css/main.css', [], HPH_THEME_VERSION);
        wp_enqueue_script('hph-main', HPH_THEME_URL . '/assets/dist/js/main.js', ['jquery'], HPH_THEME_VERSION, true);
    }
}
    add_image_size('listing-medium', 600, 400, true);
    add_image_size('listing-large', 1200, 800, true);
    add_image_size('agent-profile', 250, 250, true);
    
    // Navigation menus
    register_nav_menus([
        'primary' => __('Primary Menu', 'happy-place'),
        'footer' => __('Footer Menu', 'happy-place'),
        'dashboard' => __('Dashboard Menu', 'happy-place'),
    ]);
});

// Initialize core managers (UPDATED ORDER - Asset_Manager first)
add_action('init', function() {
    // Debug log
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('HPH: Initializing core managers');
    }
    
    // 1. Initialize Asset_Manager FIRST (handles all CSS/JS loading)
    HappyPlace\Core\Asset_Manager::init();
    
    // 2. Initialize other managers
    HappyPlace\Core\Theme_Manager::get_instance();
    
    // 3. Initialize Template Engine with error checking
    if (class_exists('HappyPlace\\Core\\Template_Engine')) {
        try {
            $template_engine = HappyPlace\Core\Template_Engine::instance();
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('HPH: Template Engine initialized successfully');
            }
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('HPH: Template Engine initialization failed: ' . $e->getMessage());
            }
        }
    } else {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('HPH: Template Engine class not found');
        }
    }
    
    HappyPlace\Core\Component_Manager::init();
    
    // 4. Initialize optional components
    if (class_exists('HPH_Shortcode_Manager')) {
        HPH_Shortcode_Manager::get_instance();
    }
}, 5); // Earlier priority to ensure assets load before other theme features

// Clean up legacy asset loading (CRITICAL - prevents conflicts)
add_action('init', function() {
    // Remove any old asset loading functions that might conflict
    remove_action('wp_enqueue_scripts', 'happy_place_enqueue_assets');
    remove_action('wp_enqueue_scripts', 'hph_enqueue_assets');
    remove_action('wp_enqueue_scripts', 'hph_bridge_enqueue_template_assets');
    
    // Remove style.css loading (Asset_Manager handles all CSS)
    add_action('wp_enqueue_scripts', function() {
        wp_dequeue_style('happy-place-style');
        wp_deregister_style('happy-place-style');
    }, 5);
}, 1); // Very early priority

// Content width for media
if (!isset($content_width)) {
    $content_width = 1200;
}

// Widget areas
add_action('widgets_init', function() {
    // Sidebar widgets
    register_sidebar([
        'name' => __('Primary Sidebar', 'happy-place'),
        'id' => 'sidebar-primary',
        'description' => __('Main sidebar for pages and posts', 'happy-place'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h3 class="widget-title">',
        'after_title' => '</h3>',
    ]);
    
    // Listing sidebar
    register_sidebar([
        'name' => __('Listing Sidebar', 'happy-place'),
        'id' => 'sidebar-listing',
        'description' => __('Sidebar for individual listing pages', 'happy-place'),
        'before_widget' => '<div id="%1$s" class="widget %2$s hph-card">',
        'after_widget' => '</div>',
        'before_title' => '<h3 class="widget-title hph-card__title">',
        'after_title' => '</h3>',
    ]);
    
    // Footer widgets
    for ($i = 1; $i <= 4; $i++) {
        register_sidebar([
            'name' => sprintf(__('Footer Widget %d', 'happy-place'), $i),
            'id' => "footer-widget-{$i}",
            'description' => sprintf(__('Footer widget area %d', 'happy-place'), $i),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget' => '</div>',
            'before_title' => '<h4 class="widget-title">',
            'after_title' => '</h4>',
        ]);
    }
});

// Security enhancements
add_action('init', function() {
    // Remove WordPress version from head
    remove_action('wp_head', 'wp_generator');
    
    // Remove REST API links from head (if not needed)
    remove_action('wp_head', 'rest_output_link_wp_head');
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    
    // Remove shortlink
    remove_action('wp_head', 'wp_shortlink_wp_head');
});

// Performance optimizations
add_action('wp_enqueue_scripts', function() {
    // Remove jQuery migrate in production
    if (!is_admin() && !WP_DEBUG) {
        wp_deregister_script('jquery');
        wp_register_script('jquery', includes_url('/js/jquery/jquery.js'), false, null, true);
        wp_enqueue_script('jquery');
    }
});

// Add body classes for better styling control
add_filter('body_class', function($classes) {
    // Add page template class
    if (is_page_template()) {
        $template = get_page_template_slug();
        $template_name = basename($template, '.php');
        $classes[] = 'page-template-' . $template_name;
    }
    
    // Add post type classes
    if (is_singular()) {
        $classes[] = 'single-' . get_post_type();
    }
    
    if (is_post_type_archive()) {
        $classes[] = 'archive-' . get_post_type();
    }
    
    // Add mobile detection class (if needed)
    if (wp_is_mobile()) {
        $classes[] = 'is-mobile';
    }
    
    return $classes;
});

// Customize excerpt
add_filter('excerpt_length', function() {
    return 30; // 30 words
});

add_filter('excerpt_more', function() {
    return '...';
});

// Disable file editing in admin (security)
if (!defined('DISALLOW_FILE_EDIT')) {
    define('DISALLOW_FILE_EDIT', true);
}

// Development helpers (only in debug mode)
if (defined('WP_DEBUG') && WP_DEBUG) {
    // Add template name to body class for debugging
    add_filter('body_class', function($classes) {
        global $template;
        if ($template) {
            $template_name = basename($template, '.php');
            $classes[] = 'template-' . $template_name;
        }
        return $classes;
    });
    
    // Add debug info to admin bar
    add_action('admin_bar_menu', function($wp_admin_bar) {
        if (current_user_can('manage_options')) {
            $wp_admin_bar->add_node([
                'id' => 'theme-debug',
                'title' => 'Theme: v' . HPH_THEME_VERSION,
                'meta' => ['class' => 'theme-debug-info']
            ]);
        }
    }, 100);
}

// All other features handled by managers - DO NOT ADD MORE FUNCTIONALITY HERE
// Keep functions.php focused on initialization only