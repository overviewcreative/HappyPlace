<?php
namespace HappyPlace\Theme;

/**
 * Main Theme Class
 * Handles core theme functionality and component initialization
 */
class Theme {
    private static ?Theme $instance = null;
    private array $components = [];
    
    /**
     * Get the singleton instance
     */
    public static function get_instance(): Theme {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Initialize theme components and setup
     */
    private function init(): void {
        // Add theme support
        $this->add_theme_support();
        
        // Initialize components
        $this->init_components();

        // Register hooks
        $this->register_hooks();
    }

    /**
     * Initialize components
     */
    private function init_components(): void {
        // Initialize core components first
        $this->components = [
            'assets' => \HappyPlace\Core\Assets::instance(),
            // Template_Loader is initialized in functions.php, no need to duplicate here
        ];

        // Initialize optional components
        $this->init_form_handler();
        $this->init_media_handler();
        $this->init_utils();
    }

    /**
     * Initialize form handler component
     */
    private function init_form_handler(): void {
        if (class_exists(\HappyPlace\Theme\Forms\Form_Handler::class)) {
            \HappyPlace\Theme\Forms\Form_Handler::init();
            $this->components['forms'] = \HappyPlace\Theme\Forms\Form_Handler::class;
        }
    }

    /**
     * Initialize media handler component
     */
    private function init_media_handler(): void {
        if (class_exists(Media\Media_Handler::class)) {
            if (method_exists(Media\Media_Handler::class, 'init')) {
                Media\Media_Handler::init();
            }
            $this->components['media'] = Media\Media_Handler::class;
        }
    }

    /**
     * Initialize utils component
     */
    private function init_utils(): void {
        if (class_exists(Utils\Utils::class)) {
            $this->components['utils'] = Utils\Utils::class;
        }
    }

    /**
     * Get a component instance by name
     */
    public function get_component(string $name) {
        return $this->components[$name] ?? null;
    }

    /**
     * Add WordPress theme support
     */
    /**
     * Add WordPress theme support
     */
    private function add_theme_support(): void {
        add_action('after_setup_theme', function() {
            // Add default posts and comments RSS feed links to head
            add_theme_support('automatic-feed-links');

            // Let WordPress manage the document title
            add_theme_support('title-tag');

            // Enable support for Post Thumbnails
            add_theme_support('post-thumbnails');

            // Enable support for HTML5 markup
            add_theme_support('html5', [
                'search-form',
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
                'style',
                'script',
                'navigation-widgets',
            ]);

            // Add custom logo support
            add_theme_support('custom-logo', [
                'height'      => 250,
                'width'       => 250,
                'flex-width'  => true,
                'flex-height' => true,
            ]);

            // Add excerpt support for pages
            add_post_type_support('page', 'excerpt');

            // Add wide alignment support
            add_theme_support('align-wide');

            // Add support for responsive embeds
            add_theme_support('responsive-embeds');

            // Add support for editor styles
            add_theme_support('editor-styles');

            // Add support for custom line height controls
            add_theme_support('custom-line-height');

            // Add support for experimental link color control
            add_theme_support('experimental-link-color');

            // Add support for custom units
            add_theme_support('custom-units');

            // Add support for custom spacing control
            add_theme_support('custom-spacing');
        });
    }

    /**
     * Register theme hooks
     */
    private function register_hooks(): void {
        // Register menus
        add_action('after_setup_theme', function() {
            register_nav_menus([
                'primary' => __('Primary Menu', 'happy-place'),
                'footer' => __('Footer Menu', 'happy-place'),
            ]);
        });

        // Register sidebars
        add_action('widgets_init', function() {
            register_sidebar([
                'name'          => __('Primary Sidebar', 'happy-place'),
                'id'            => 'sidebar-1',
                'description'   => __('Add widgets here to appear in your sidebar.', 'happy-place'),
                'before_widget' => '<section id="%1$s" class="widget %2$s">',
                'after_widget'  => '</section>',
                'before_title'  => '<h2 class="widget-title">',
                'after_title'   => '</h2>',
            ]);
        });
    }
}
