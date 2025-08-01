<?php
namespace HappyPlace\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Component Manager Class
 * 
 * Manages component loading, registration, and global component functionality
 * 
 * @package HappyPlace\Core
 * @version 2.0.0
 */
class Component_Manager {
    
    /**
     * Singleton instance
     * @var Component_Manager
     */
    private static $instance = null;
    
    /**
     * Registered components
     * @var array
     */
    private $registered_components = [];
    
    /**
     * Component directories to scan
     * @var array
     */
    private $component_directories = [
        'listing',
        'archive',
        'agent',
        'ui',
        'forms',
        'tools',
        'layout'
    ];
    
    /**
     * Performance tracking
     * @var array
     */
    private $performance_stats = [];
    
    /**
     * Get singleton instance
     * 
     * @return Component_Manager
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize component manager
     * 
     * @return Component_Manager
     */
    public static function init() {
        $instance = self::instance();
        $instance->load_components();
        $instance->ensure_components_loaded();
        $instance->setup_hooks();
        return $instance;
    }
    
    /**
     * Constructor (private for singleton)
     */
    private function __construct() {
        $this->load_base_component();
    }
    
    /**
     * Load base component class
     */
    private function load_base_component() {
        $base_component_path = get_template_directory() . '/inc/components/class-base-component.php';
        
        if (file_exists($base_component_path)) {
            require_once $base_component_path;
        } else {
            wp_die('Base Component class not found. Please ensure class-base-component.php exists in inc/components/');
        }
    }
    
    /**
     * Load all components
     */
    private function load_components() {
        $start_time = microtime(true);
        
        foreach ($this->component_directories as $directory) {
            $this->load_components_from_directory($directory);
        }
        
        $this->performance_stats['load_time'] = microtime(true) - $start_time;
        $this->performance_stats['components_loaded'] = count($this->registered_components);
        
        // Log performance in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $load_time = round($this->performance_stats['load_time'] * 1000, 2);
            $count = $this->performance_stats['components_loaded'];
            error_log("HPH Component Manager: Loaded {$count} components in {$load_time}ms");
        }
    }
    
    /**
     * Ensure all required components are loaded with placeholders if missing
     */
    private function ensure_components_loaded() {
        $required_components = [
            'listing' => [
                'Listing_Hero',
                'Listing_Gallery', 
                'Listing_Card',
                'Listing_Details'
            ],
            'tools' => [
                'Mortgage_Calculator'
            ],
            'agent' => [
                'Agent_Card'
            ],
            'ui' => [
                'Button',
                'Modal'
            ]
        ];
        
        foreach ($required_components as $directory => $components) {
            foreach ($components as $component) {
                $class_name = "HappyPlace\\Components\\" . ucfirst($directory) . "\\{$component}";
                
                if (!class_exists($class_name)) {
                    $this->create_placeholder_component($class_name, $directory, $component);
                }
            }
        }
    }
    
    /**
     * Create placeholder component if missing
     */
    private function create_placeholder_component($class_name, $directory, $component) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("HPH: Creating placeholder for missing component: {$class_name}");
        }
        
        // For tools directory, use the actual namespace
        $namespace = ucfirst($directory);
        $component_type = strtolower(str_replace('_', '-', $component));
        
        // Create minimal working placeholder with proper eval
        $eval_code = "
        namespace HappyPlace\\Components\\{$namespace};
        
        if (!class_exists('{$class_name}')) {
            class {$component} extends \\HappyPlace\\Components\\Base_Component {
                protected function get_component_name() {
                    return '{$component_type}';
                }
                
                protected function get_defaults() {
                    return [];
                }
                
                protected function render() {
                    return '<div class=\"component-placeholder component-{$component_type}\">
                        <p>Component {$component} is not yet implemented</p>
                    </div>';
                }
            }
        }";
        
        eval($eval_code);
    }
    
    /**
     * Load components from a specific directory
     * 
     * @param string $directory Directory name
     */
    private function load_components_from_directory($directory) {
        $components_dir = get_template_directory() . '/inc/components/' . $directory;
        
        if (!is_dir($components_dir)) {
            return;
        }
        
        $component_files = glob($components_dir . '/class-*.php');
        
        foreach ($component_files as $file) {
            $this->load_component_file($file, $directory);
        }
    }
    
    /**
     * Load individual component file
     * 
     * @param string $file File path
     * @param string $directory Directory name
     */
    private function load_component_file($file, $directory) {
        try {
            require_once $file;
            
            // Extract class name from filename
            $filename = basename($file, '.php');
            $class_name = str_replace(['class-', '-'], ['', '_'], $filename);
            $class_name = str_replace('_', ' ', $class_name);
            $class_name = str_replace(' ', '_', ucwords($class_name));
            
            // Build full class name with namespace
            $full_class_name = $this->build_class_namespace($directory, $class_name);
            
            // Register component if class exists
            if (class_exists($full_class_name)) {
                $this->register_component($full_class_name, $directory);
            }
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("HPH Component Manager: Error loading {$file}: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Build class namespace based on directory
     * 
     * @param string $directory Directory name
     * @param string $class_name Class name
     * @return string Full class name with namespace
     */
    private function build_class_namespace($directory, $class_name) {
        $namespace_map = [
            'listing' => 'HappyPlace\\Components\\Listing',
            'archive' => 'HappyPlace\\Components\\Archive',
            'agent' => 'HappyPlace\\Components\\Agent',
            'ui' => 'HappyPlace\\Components\\UI',
            'forms' => 'HappyPlace\\Components\\Forms',
            'tools' => 'HappyPlace\\Components\\Tools',
            'layout' => 'HappyPlace\\Components\\Layout'
        ];
        
        $namespace = isset($namespace_map[$directory]) 
            ? $namespace_map[$directory] 
            : 'HappyPlace\\Components';
        
        return $namespace . '\\' . $class_name;
    }
    
    /**
     * Register a component
     * 
     * @param string $class_name Full class name
     * @param string $directory Directory name
     */
    private function register_component($class_name, $directory) {
        $this->registered_components[$class_name] = [
            'class' => $class_name,
            'directory' => $directory,
            'loaded_at' => microtime(true)
        ];
        
        if (defined('WP_DEBUG') && WP_DEBUG && isset($_GET['component_debug'])) {
            error_log("HPH Component Manager: Registered {$class_name}");
        }
    }
    
    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {
        // Clear component cache when posts are saved/updated
        add_action('save_post', [$this, 'clear_component_cache']);
        add_action('post_updated', [$this, 'clear_component_cache']);
        
        // Clear cache when theme options change
        add_action('customize_save_after', [$this, 'clear_all_component_cache']);
        
        // Add admin bar debug info
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('admin_bar_menu', [$this, 'add_debug_admin_bar'], 999);
        }
        
        // Enqueue component assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_component_assets'], 15);
        
        // Add component debug styles
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp_head', [$this, 'add_debug_styles']);
        }
    }
    
    /**
     * Check if component is registered
     * 
     * @param string $class_name Component class name
     * @return bool
     */
    public function is_component_registered($class_name) {
        return isset($this->registered_components[$class_name]);
    }
    
    /**
     * Get registered component info
     * 
     * @param string $class_name Component class name
     * @return array|null Component info or null if not found
     */
    public function get_component_info($class_name) {
        return isset($this->registered_components[$class_name]) 
            ? $this->registered_components[$class_name] 
            : null;
    }
    
    /**
     * Get all registered components
     * 
     * @return array All registered components
     */
    public function get_registered_components() {
        return $this->registered_components;
    }
    
    /**
     * Create component instance
     * 
     * @param string $class_name Component class name
     * @param array $props Component properties
     * @return object|null Component instance or null if class doesn't exist
     */
    public function create_component($class_name, $props = []) {
        if (!$this->is_component_registered($class_name)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("HPH Component Manager: Component not registered: {$class_name}");
            }
            return null;
        }
        
        try {
            return new $class_name($props);
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("HPH Component Manager: Error creating {$class_name}: " . $e->getMessage());
            }
            return null;
        }
    }
    
    /**
     * Render component
     * 
     * @param string $class_name Component class name
     * @param array $props Component properties
     * @param bool $echo Whether to echo output
     * @return string Component output
     */
    public function render_component($class_name, $props = [], $echo = true) {
        $component = $this->create_component($class_name, $props);
        
        if (!$component) {
            return '';
        }
        
        return $component->display($echo);
    }
    
    /**
     * Clear component cache for specific post
     * 
     * @param int $post_id Post ID
     */
    public function clear_component_cache($post_id = null) {
        // Clear object cache for components
        wp_cache_flush_group('hph_components');
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("HPH Component Manager: Cleared cache for post {$post_id}");
        }
    }
    
    /**
     * Clear all component cache
     */
    public function clear_all_component_cache() {
        wp_cache_flush_group('hph_components');
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("HPH Component Manager: Cleared all component cache");
        }
    }
    
    /**
     * Get component performance statistics
     * 
     * @return array Performance stats
     */
    public function get_performance_stats() {
        return $this->performance_stats;
    }
    
    /**
     * Enqueue component assets
     */
    public function enqueue_component_assets() {
        // This method can be extended to enqueue specific component assets
        // For now, we rely on the main asset system
        
        if (defined('WP_DEBUG') && WP_DEBUG && isset($_GET['component_debug'])) {
            wp_add_inline_script('hph-main-scripts', $this->get_debug_javascript());
        }
    }
    
    /**
     * Add debug admin bar info
     * 
     * @param WP_Admin_Bar $wp_admin_bar
     */
    public function add_debug_admin_bar($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $component_count = count($this->registered_components);
        $load_time = round($this->performance_stats['load_time'] * 1000, 2);
        
        $wp_admin_bar->add_node([
            'id' => 'hph-components',
            'title' => "HPH Components ({$component_count})",
            'href' => add_query_arg('component_debug', '1'),
            'meta' => [
                'title' => "Components loaded in {$load_time}ms. Click to toggle debug mode."
            ]
        ]);
        
        // Add submenu items for each component category
        $categories = [];
        foreach ($this->registered_components as $component) {
            $categories[$component['directory']][] = $component['class'];
        }
        
        foreach ($categories as $category => $components) {
            $wp_admin_bar->add_node([
                'id' => 'hph-components-' . $category,
                'parent' => 'hph-components',
                'title' => ucfirst($category) . ' (' . count($components) . ')',
                'href' => '#'
            ]);
        }
    }
    
    /**
     * Add debug styles
     */
    public function add_debug_styles() {
        if (!isset($_GET['component_debug'])) {
            return;
        }
        
        ?>
        <style>
        .hph-component {
            position: relative;
        }
        
        .hph-component::before {
            content: attr(data-component);
            position: absolute;
            top: 0;
            left: 0;
            background: rgba(255, 0, 0, 0.8);
            color: white;
            padding: 2px 6px;
            font-size: 10px;
            font-family: monospace;
            z-index: 10000;
            pointer-events: none;
        }
        
        .hph-component-error {
            border: 2px dashed #ff0000 !important;
            background: rgba(255, 0, 0, 0.1) !important;
        }
        </style>
        <?php
    }
    
    /**
     * Get debug JavaScript
     * 
     * @return string JavaScript code
     */
    private function get_debug_javascript() {
        return '
        console.group("HPH Component Manager Debug");
        console.log("Components loaded:", ' . json_encode($this->registered_components) . ');
        console.log("Performance stats:", ' . json_encode($this->performance_stats) . ');
        console.groupEnd();
        
        // Add component inspection
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".hph-component").forEach(function(el) {
                el.addEventListener("click", function(e) {
                    if (e.altKey) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log("Component:", el.dataset.component);
                        console.log("Element:", el);
                    }
                });
            });
            
            console.log("HPH Debug: Alt+click any component to inspect it");
        });
        ';
    }
    
    /**
     * Get component usage statistics
     * 
     * @return array Usage statistics
     */
    public function get_usage_statistics() {
        return [
            'total_components' => count($this->registered_components),
            'directories' => array_unique(array_column($this->registered_components, 'directory')),
            'load_time' => $this->performance_stats['load_time'] ?? 0,
            'memory_usage' => memory_get_usage(true),
            'cache_hits' => wp_cache_get_stats()['hph_components']['hits'] ?? 0,
            'cache_misses' => wp_cache_get_stats()['hph_components']['misses'] ?? 0
        ];
    }
}

// =============================================================================
// GLOBAL HELPER FUNCTIONS
// =============================================================================

if (!function_exists('hph_component')) {
    /**
     * Create and display component
     * 
     * @param string $component_name Component class name or short name
     * @param array $props Component properties
     * @param bool $echo Whether to echo output
     * @return string Component output
     */
    function hph_component($component_name, $props = [], $echo = true) {
        $component_manager = \HappyPlace\Core\Component_Manager::instance();
        
        // Try to resolve short names to full class names
        $full_class_name = hph_resolve_component_name($component_name);
        
        return $component_manager->render_component($full_class_name, $props, $echo);
    }
}

if (!function_exists('hph_resolve_component_name')) {
    /**
     * Resolve short component name to full class name
     * 
     * @param string $name Short component name
     * @return string Full class name
     */
    function hph_resolve_component_name($name) {
        // If already a full class name, return as-is
        if (strpos($name, '\\') !== false) {
            return $name;
        }
        
        // Common component name mappings
        $name_map = [
            'listing-card' => 'HappyPlace\\Components\\Listing\\Listing_Card',
            'listing-hero' => 'HappyPlace\\Components\\Listing\\Hero',
            'listing-gallery' => 'HappyPlace\\Components\\Listing\\Gallery',
            'listing-details' => 'HappyPlace\\Components\\Listing\\Details',
            'listings-grid' => 'HappyPlace\\Components\\Archive\\Listings_Grid',
            'search-form' => 'HappyPlace\\Components\\Archive\\Search_Form',
            'pagination' => 'HappyPlace\\Components\\UI\\Pagination',
            'agent-card' => 'HappyPlace\\Components\\Agent\\Card',
            'contact-form' => 'HappyPlace\\Components\\Forms\\Contact_Form',
            'mortgage-calculator' => 'HappyPlace\\Components\\Tools\\Mortgage_Calculator'
        ];
        
        if (isset($name_map[$name])) {
            return $name_map[$name];
        }
        
        // Try to auto-resolve based on naming convention
        $parts = explode('-', $name);
        if (count($parts) >= 2) {
            $namespace = 'HappyPlace\\Components\\' . ucfirst($parts[0]);
            $class = implode('_', array_map('ucfirst', array_slice($parts, 1)));
            return $namespace . '\\' . $class;
        }
        
        // Default to UI namespace
        return 'HappyPlace\\Components\\UI\\' . str_replace('-', '_', ucwords($name, '-'));
    }
}

if (!function_exists('hph_component_exists')) {
    /**
     * Check if component exists
     * 
     * @param string $component_name Component name
     * @return bool
     */
    function hph_component_exists($component_name) {
        $full_class_name = hph_resolve_component_name($component_name);
        $component_manager = \HappyPlace\Core\Component_Manager::instance();
        
        return $component_manager->is_component_registered($full_class_name);
    }
}

if (!function_exists('hph_clear_component_cache')) {
    /**
     * Clear component cache
     * 
     * @param int $post_id Optional post ID
     */
    function hph_clear_component_cache($post_id = null) {
        $component_manager = \HappyPlace\Core\Component_Manager::instance();
        
        if ($post_id) {
            $component_manager->clear_component_cache($post_id);
        } else {
            $component_manager->clear_all_component_cache();
        }
    }
}