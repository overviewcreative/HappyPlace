<?php
/**
 * Happy Place Theme - Shortcode Manager
 * 
 * Manages registration and functionality of all theme shortcodes
 * Integrates with existing SCSS component system
 * 
 * @package HappyPlace
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HPH_Shortcode_Manager {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Array of registered shortcodes
     */
    private $shortcodes = array();
    
    /**
     * Array of detected shortcodes on current page
     */
    private $page_shortcodes = array();
    
    /**
     * Whether assets have been enqueued
     */
    private $assets_enqueued = false;
    
    /**
     * Get instance
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
        add_action('init', array($this, 'register_shortcodes'));
        // Asset loading now handled by Asset_Loader per Phase 5 consolidation
        add_action('wp_head', array($this, 'detect_shortcodes_in_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_filter('widget_text', 'do_shortcode');
        add_filter('the_excerpt', 'do_shortcode');
    }
    
    /**
     * Register all shortcodes
     */
    public function register_shortcodes() {
        $shortcodes = array(
            'hph_hero' => 'HPH_Hero_Shortcode',
            'hph_button' => 'HPH_Button_Shortcode', 
            'hph_card' => 'HPH_Card_Shortcode',
            'hph_grid' => 'HPH_Grid_Shortcode',
            'hph_cta' => 'HPH_CTA_Shortcode',
            'hph_features' => 'HPH_Shortcode_Features',
            'hph_feature' => 'HPH_Feature_Shortcode',
            'hph_testimonials' => 'HPH_Shortcode_Testimonials',
            'hph_testimonial' => 'HPH_Testimonial_Shortcode',
            'hph_pricing' => 'HPH_Shortcode_Pricing',
            'hph_stats' => 'HPH_Stats_Shortcode',
            'hph_stat' => 'HPH_Stat_Shortcode',
            'hph_accordion' => 'HPH_Accordion_Shortcode',
            'hph_accordion_item' => 'HPH_Accordion_Item_Shortcode',
            'hph_spacer' => 'HPH_Spacer_Shortcode',
            'hph_divider' => 'HPH_Divider_Shortcode',
        );
        
        // Include shortcode component files
        foreach ($shortcodes as $tag => $class) {
            $file = get_template_directory() . '/inc/shortcodes/components/' . str_replace('_', '-', strtolower(str_replace('HPH_', '', str_replace('_Shortcode', '', $class)))) . '.php';
            
            if (file_exists($file)) {
                require_once $file;
                
                if (class_exists($class)) {
                    $instance = new $class();
                    add_shortcode($tag, array($instance, 'render'));
                    $this->shortcodes[$tag] = $instance; // Store instance instead of class name
                }
            }
        }
        
        do_action('hph_shortcodes_registered', $this->shortcodes);
    }
    
    /**
     * Detect shortcodes in content to determine which assets to load
     */
    public function detect_shortcodes_in_content() {
        global $post;
        
        if (!is_object($post) || empty($post->post_content)) {
            return;
        }
        
        // Check post content for shortcodes
        $content = $post->post_content;
        
        // Also check widgets if available
        if (is_active_sidebar('sidebar-1')) {
            ob_start();
            dynamic_sidebar('sidebar-1');
            $content .= ob_get_clean();
        }
        
        // Detect which shortcodes are present
        foreach ($this->shortcodes as $tag => $class) {
            if (has_shortcode($content, $tag)) {
                $this->page_shortcodes[] = $tag;
            }
        }
        
        // Store in post meta for performance
        if (!empty($this->page_shortcodes)) {
            update_post_meta($post->ID, '_hph_page_shortcodes', $this->page_shortcodes);
        }
    }
    
    /**
     * Conditionally enqueue assets based on detected shortcodes
     */
    public function maybe_enqueue_assets() {
        global $post;
        
        if ($this->assets_enqueued) {
            return;
        }
        
        // Get detected shortcodes from post meta or detection
        $page_shortcodes = !empty($this->page_shortcodes) ? $this->page_shortcodes : 
                          (is_object($post) ? get_post_meta($post->ID, '_hph_page_shortcodes', true) : array());
        
        if (empty($page_shortcodes)) {
            return;
        }
        
        // Shortcode styles are now included in main CSS bundle via Asset_Loader
        // wp_enqueue_style(
        //     'hph-shortcodes',
        //     get_template_directory_uri() . '/assets/dist/css/shortcodes.css',
        //     array('happy-place-main'),
        //     HPH_THEME_VERSION
        // );
        
        // Enqueue component-specific styles
        $this->enqueue_component_assets($page_shortcodes);
        
        // Enqueue shortcode JavaScript
        wp_enqueue_script(
            'hph-shortcodes',
            get_template_directory_uri() . '/assets/dist/js/shortcodes.js',
            array('jquery'),
            HPH_THEME_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('hph-shortcodes', 'hphShortcodes', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_shortcodes_nonce'),
            'components' => $page_shortcodes
        ));
        
        $this->assets_enqueued = true;
    }
    
    /**
     * Enqueue component-specific assets
     */
    private function enqueue_component_assets($shortcodes) {
        $component_map = array(
            'hph_hero' => 'hero',
            'hph_testimonials' => 'testimonials',
            'hph_accordion' => 'accordion',
            'hph_stats' => 'stats'
        );
        
        foreach ($shortcodes as $shortcode) {
            if (isset($component_map[$shortcode])) {
                $component = $component_map[$shortcode];
                
                // Check if component-specific CSS exists
                $css_file = get_template_directory() . '/assets/dist/css/components/' . $component . '.css';
                if (file_exists($css_file)) {
                    wp_enqueue_style(
                        'hph-' . $component,
                        get_template_directory_uri() . '/assets/dist/css/components/' . $component . '.css',
                        array('hph-shortcodes'),
                        HPH_THEME_VERSION
                    );
                }
                
                // Check if component-specific JS exists
                $js_file = get_template_directory() . '/assets/dist/js/components/' . $component . '.js';
                if (file_exists($js_file)) {
                    wp_enqueue_script(
                        'hph-' . $component,
                        get_template_directory_uri() . '/assets/dist/js/components/' . $component . '.js',
                        array('hph-shortcodes'),
                        HPH_THEME_VERSION,
                        true
                    );
                }
            }
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php', 'widgets.php'))) {
            return;
        }
        
        wp_enqueue_style(
            'hph-shortcodes-admin',
            get_template_directory_uri() . '/inc/shortcodes/assets/admin.css',
            array(),
            HPH_THEME_VERSION
        );
        
        wp_enqueue_script(
            'hph-shortcodes-admin',
            get_template_directory_uri() . '/inc/shortcodes/assets/admin.js',
            array('jquery'),
            HPH_THEME_VERSION,
            true
        );
        
        wp_localize_script('hph-shortcodes-admin', 'hphShortcodesAdmin', array(
            'shortcodes' => $this->get_shortcode_definitions(),
            'nonce' => wp_create_nonce('hph_shortcodes_admin_nonce')
        ));
    }
    
    /**
     * Get shortcode definitions for admin interface
     */
    public function get_shortcode_definitions() {
        return array(
            'hph_hero' => array(
                'label' => 'Hero Section',
                'description' => 'Large hero section with background image and call-to-action',
                'category' => 'layout',
                'attributes' => array(
                    'title' => array('type' => 'text', 'label' => 'Title'),
                    'subtitle' => array('type' => 'text', 'label' => 'Subtitle'),
                    'background' => array('type' => 'media', 'label' => 'Background Image'),
                    'size' => array('type' => 'select', 'label' => 'Size', 'options' => array('sm', 'md', 'lg', 'xl')),
                    'theme' => array('type' => 'select', 'label' => 'Theme', 'options' => array('light', 'dark', 'primary'))
                )
            ),
            'hph_button' => array(
                'label' => 'Button',
                'description' => 'Styled button with various options',
                'category' => 'content',
                'attributes' => array(
                    'text' => array('type' => 'text', 'label' => 'Button Text'),
                    'url' => array('type' => 'url', 'label' => 'Link URL'),
                    'style' => array('type' => 'select', 'label' => 'Style', 'options' => array('primary', 'secondary', 'outline')),
                    'size' => array('type' => 'select', 'label' => 'Size', 'options' => array('sm', 'md', 'lg')),
                    'icon' => array('type' => 'text', 'label' => 'Icon Class')
                )
            ),
            'hph_card' => array(
                'label' => 'Card',
                'description' => 'Content card with image and text',
                'category' => 'content',
                'attributes' => array(
                    'title' => array('type' => 'text', 'label' => 'Title'),
                    'image' => array('type' => 'media', 'label' => 'Image'),
                    'link' => array('type' => 'url', 'label' => 'Link URL'),
                    'style' => array('type' => 'select', 'label' => 'Style', 'options' => array('default', 'hover-lift', 'hover-scale'))
                )
            ),
            'hph_grid' => array(
                'label' => 'Grid Layout',
                'description' => 'Responsive grid container for other components',
                'category' => 'layout',
                'attributes' => array(
                    'columns' => array('type' => 'number', 'label' => 'Columns', 'default' => 3),
                    'gap' => array('type' => 'select', 'label' => 'Gap', 'options' => array('sm', 'md', 'lg')),
                    'responsive' => array('type' => 'checkbox', 'label' => 'Responsive')
                )
            ),
            'hph_cta' => array(
                'label' => 'Call to Action',
                'description' => 'Call-to-action section with title, description, and buttons',
                'category' => 'layout',
                'attributes' => array(
                    'title' => array('type' => 'text', 'label' => 'Title'),
                    'description' => array('type' => 'textarea', 'label' => 'Description'),
                    'layout' => array('type' => 'select', 'label' => 'Layout', 'options' => array('centered', 'split')),
                    'theme' => array('type' => 'select', 'label' => 'Theme', 'options' => array('light', 'primary', 'dark'))
                )
            )
        );
    }
    
    /**
     * Get registered shortcodes
     */
    public function get_shortcodes() {
        return $this->shortcodes;
    }
    
    /**
     * Get registered shortcode instances (for admin interface)
     */
    public function get_registered_shortcodes() {
        return $this->shortcodes;
    }
    
    /**
     * Check if a shortcode is registered
     */
    public function is_shortcode_registered($tag) {
        return isset($this->shortcodes[$tag]);
    }
    
    /**
     * Add custom shortcode
     */
    public function add_shortcode($tag, $callback, $class = null) {
        add_shortcode($tag, $callback);
        $this->shortcodes[$tag] = $class ?: 'Custom';
    }
    
    /**
     * Remove shortcode
     */
    public function remove_shortcode($tag) {
        remove_shortcode($tag);
        unset($this->shortcodes[$tag]);
    }
}

/**
 * Base class for all shortcode components
 */
abstract class HPH_Shortcode_Base {
    
    /**
     * Default attributes for the shortcode
     */
    protected $defaults = array();
    
    /**
     * Shortcode tag
     */
    protected $tag = '';
    
    /**
     * Whether this shortcode supports content
     */
    protected $supports_content = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }
    
    /**
     * Initialize the shortcode
     */
    protected function init() {
        // Override in child classes
    }
    
    /**
     * Render the shortcode
     */
    public function render($atts = array(), $content = null) {
        $atts = shortcode_atts($this->defaults, $atts, $this->tag);
        
        // Sanitize attributes
        $atts = $this->sanitize_attributes($atts);
        
        // Process content if supported
        if ($this->supports_content && !empty($content)) {
            $content = do_shortcode($content);
        }
        
        // Generate output
        return $this->generate_output($atts, $content);
    }
    
    /**
     * Sanitize attributes
     */
    protected function sanitize_attributes($atts) {
        foreach ($atts as $key => $value) {
            switch ($key) {
                case 'url':
                case 'link':
                    $atts[$key] = esc_url($value);
                    break;
                case 'title':
                case 'text':
                case 'label':
                    $atts[$key] = sanitize_text_field($value);
                    break;
                case 'description':
                case 'content':
                    $atts[$key] = wp_kses_post($value);
                    break;
                case 'image':
                case 'background':
                    $atts[$key] = is_numeric($value) ? wp_get_attachment_url($value) : esc_url($value);
                    break;
                default:
                    $atts[$key] = sanitize_text_field($value);
            }
        }
        
        return $atts;
    }
    
    /**
     * Generate the HTML output - must be implemented by child classes
     */
    abstract protected function generate_output($atts, $content = null);
    
    /**
     * Get CSS classes for the shortcode
     */
    protected function get_css_classes($base_class, $atts) {
        $classes = array($base_class);
        
        // Add modifier classes based on attributes
        foreach ($atts as $key => $value) {
            if (!empty($value) && in_array($key, array('style', 'size', 'theme', 'layout', 'variant'))) {
                $classes[] = $base_class . '--' . $value;
            }
        }
        
        return implode(' ', array_filter($classes));
    }
    
    /**
     * Generate unique ID for the component
     */
    protected function generate_id($prefix = 'hph') {
        return $prefix . '-' . uniqid();
    }
}

// Initialize the shortcode manager
function hph_init_shortcodes() {
    return HPH_Shortcode_Manager::get_instance();
}

add_action('after_setup_theme', 'hph_init_shortcodes');

/**
 * Helper function to get shortcode manager instance
 */
function hph_shortcodes() {
    return HPH_Shortcode_Manager::get_instance();
}
