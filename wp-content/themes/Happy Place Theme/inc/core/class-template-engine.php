<?php
/**
 * Template Engine Review & Implementation Guide
 * 
 * This file contains the reviewed Template Engine implementation
 * with fixes and enhancements for robust template loading.
 */

namespace HappyPlace\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Template_Engine {
    /**
     * Instance of this class
     *
     * @var Template_Engine
     */
    private static $instance = null;

    /**
     * Template hierarchy for custom post types
     *
     * @var array
     */
    private $template_hierarchy = [];

    /**
     * Get instance of this class
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - Initialize template loading
     */
    private function __construct() {
        $this->setup_template_hierarchy();
        
        // Add template_include filter with high priority
        add_filter('template_include', [$this, 'template_loader'], 10);
        
        // Debug hook for development
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp_footer', [$this, 'debug_template_info']);
            error_log("HPH Template Engine: Initialized successfully");
            
            // Log all template includes for debugging
            add_action('template_redirect', function() {
                global $template;
                if ($template) {
                    error_log("HPH Template: Current template being used: " . basename($template));
                }
            });
        }
    }

    /**
     * Setup template hierarchy for custom post types
     */
    private function setup_template_hierarchy() {
        $this->template_hierarchy = [
            // Single templates
            'single' => [
                'listing' => [
                    'templates/listing/single-listing.php',
                    'templates/single-listing.php',
                    'single-listing.php'
                ],
                'agent' => [
                    'templates/agent/single-agent.php', 
                    'templates/single-agent.php',
                    'single-agent.php'
                ]
            ],
            // Archive templates
            'archive' => [
                'listing' => [
                    'templates/listing/archive-listing.php',
                    'templates/archive-listing.php',
                    'archive-listing.php'
                ],
                'agent' => [
                    'templates/agent/archive-agent.php',
                    'templates/archive-agent.php', 
                    'archive-agent.php'
                ]
            ]
        ];
    }

    /**
     * Main template loader with improved hierarchy
     *
     * @param string $template Current template path
     * @return string Modified template path
     */
    public function template_loader($template) {
        global $post;

        // Always log this to make sure the filter is being called
        $this->log_template_load("Template loader called - Current template: " . basename($template) . " | Post type: " . get_post_type() . " | Query: " . (is_singular() ? 'singular' : (is_archive() ? 'archive' : 'other')));

        // Single post templates
        if (is_singular()) {
            $post_type = get_post_type();
            $this->log_template_load("Processing singular post of type: {$post_type}");
            
            if (isset($this->template_hierarchy['single'][$post_type])) {
                $this->log_template_load("Found template hierarchy for {$post_type}");
                $custom_template = $this->locate_template_hierarchy(
                    $this->template_hierarchy['single'][$post_type]
                );
                if ($custom_template) {
                    $this->log_template_load("Single {$post_type}: {$custom_template}");
                    return $custom_template;
                } else {
                    $this->log_template_error("No custom template found for {$post_type}");
                }
            } else {
                $this->log_template_load("No template hierarchy defined for post type: {$post_type}");
            }
        }

        // Archive templates
        if (is_post_type_archive()) {
            $post_type = get_query_var('post_type');
            $this->log_template_load("Processing archive for post type: {$post_type}");
            
            if (isset($this->template_hierarchy['archive'][$post_type])) {
                $custom_template = $this->locate_template_hierarchy(
                    $this->template_hierarchy['archive'][$post_type]
                );
                if ($custom_template) {
                    $this->log_template_load("Archive {$post_type}: {$custom_template}");
                    return $custom_template;
                }
            }
        }

        // Special page templates
        if (is_search()) {
            $search_template = locate_template([
                'templates/search/search-results.php',
                'templates/search-results.php',
                'search-results.php'
            ]);
            if ($search_template) {
                $this->log_template_load("Search: {$search_template}");
                return $search_template;
            }
        }

        $this->log_template_load("Using default WordPress template: " . basename($template));
        return $template;
    }

    /**
     * Locate template from hierarchy array
     *
     * @param array $templates Template hierarchy
     * @return string|false Template path or false
     */
    private function locate_template_hierarchy($templates) {
        $this->log_template_load("Searching template hierarchy: " . implode(', ', $templates));
        
        foreach ($templates as $template) {
            $this->log_template_load("Checking template: {$template}");
            $located = locate_template($template);
            if ($located) {
                $this->log_template_load("Found template: {$located}");
                return $located;
            } else {
                $this->log_template_load("Template not found: {$template}");
            }
        }
        
        $this->log_template_error("No templates found in hierarchy");
        return false;
    }

    /**
     * Enhanced template part loader with error handling
     *
     * @param string $slug Template slug
     * @param string $name Template name variation
     * @param array $args Arguments to pass to template
     * @return bool Success status
     */
    public function load_template_part($slug, $name = null, $args = []) {
        // Set up template arguments
        if (!empty($args) && is_array($args)) {
            extract($args, EXTR_SKIP);
        }

        $templates = [];

        // Build template hierarchy
        if ($name) {
            $templates[] = "template-parts/{$slug}-{$name}.php";
        }
        $templates[] = "template-parts/{$slug}.php";

        // Locate template
        $template = locate_template($templates);

        if ($template) {
            // Load template-specific assets if needed
            $this->maybe_enqueue_template_assets($slug, $name);
            
            // Include template
            include $template;
            
            $this->log_template_load("Template part: {$slug}" . ($name ? "-{$name}" : ''));
            return true;
        }

        // Log missing template for debugging
        $this->log_template_error("Missing template part: {$slug}" . ($name ? "-{$name}" : ''));
        return false;
    }

    /**
     * Get template with buffered output
     *
     * @param string $template_name Template file name
     * @param array $args Template arguments
     * @return string Template output
     */
    public function get_template($template_name, $args = []) {
        if (!empty($args) && is_array($args)) {
            extract($args, EXTR_SKIP);
        }

        ob_start();
        
        $template = locate_template($template_name);
        if ($template) {
            include $template;
        } else {
            $this->log_template_error("Template not found: {$template_name}");
        }

        return ob_get_clean();
    }

    /**
     * Get template with component integration
     *
     * @param string $component_name Component class name
     * @param array $props Component properties
     * @return string Component output
     */
    public function get_component($component_name, $props = []) {
        // Convert component name to class name
        $class_name = $this->resolve_component_class($component_name);
        
        if (class_exists($class_name)) {
            try {
                $component = new $class_name($props);
                return $component->display(false); // Get output without echoing
            } catch (\Exception $e) {
                $this->log_template_error("Component error ({$component_name}): " . $e->getMessage());
                return '';
            }
        }

        $this->log_template_error("Component not found: {$component_name}");
        return '';
    }

    /**
     * Resolve component class name from component name
     *
     * @param string $component_name Component name
     * @return string Full class name
     */
    private function resolve_component_class($component_name) {
        // Handle different component naming patterns
        $component_map = [
            'listing-card' => 'HappyPlace\\Components\\Listing\\Listing_Card',
            'listing-grid' => 'HappyPlace\\Components\\Archive\\Listings_Grid',
            'search-form' => 'HappyPlace\\Components\\Archive\\Search_Form',
            'agent-card' => 'HappyPlace\\Components\\Agent\\Agent_Card',
            'pagination' => 'HappyPlace\\Components\\UI\\Pagination'
        ];

        if (isset($component_map[$component_name])) {
            return $component_map[$component_name];
        }

        // Try to auto-resolve based on naming convention
        $parts = explode('-', $component_name);
        $namespace = 'HappyPlace\\Components\\';
        
        if (count($parts) >= 2) {
            $category = ucfirst($parts[0]);
            $class = str_replace('-', '_', ucwords(implode('_', array_slice($parts, 1)), '_'));
            return $namespace . $category . '\\' . $class;
        }

        return $namespace . 'UI\\' . str_replace('-', '_', ucwords($component_name, '-'));
    }

    /**
     * Maybe enqueue template-specific assets
     *
     * @param string $slug Template slug
     * @param string $name Template name
     */
    private function maybe_enqueue_template_assets($slug, $name = null) {
        // Check if Asset Manager can handle template-specific assets
        if (class_exists('HappyPlace\\Core\\Asset_Manager') && method_exists('HappyPlace\\Core\\Asset_Manager', 'enqueue_template_assets')) {
            $template_id = $slug . ($name ? "-{$name}" : '');
            \HappyPlace\Core\Asset_Manager::enqueue_template_assets($template_id);
        }
    }

    /**
     * Log template loading for debugging
     *
     * @param string $message Log message
     */
    private function log_template_load($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("HPH Template: {$message}");
        }
    }

    /**
     * Log template errors
     *
     * @param string $message Error message
     */
    private function log_template_error($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("HPH Template ERROR: {$message}");
        }
    }

    /**
     * Debug template information in footer (development only)
     */
    public function debug_template_info() {
        if (!isset($_GET['template_debug'])) {
            return;
        }

        global $template;
        
        echo '<div id="template-debug" style="position:fixed;bottom:0;left:0;background:#000;color:#fff;padding:15px;font-family:monospace;font-size:12px;z-index:99999;max-width:100%;overflow:auto;">';
        echo '<strong>Template Debug Info:</strong><br>';
        echo 'Current Template: ' . basename($template) . '<br>';
        echo 'Post Type: ' . get_post_type() . '<br>';
        echo 'Query Type: ';
        
        if (is_singular()) echo 'Single ';
        if (is_archive()) echo 'Archive ';
        if (is_search()) echo 'Search ';
        if (is_home()) echo 'Home ';
        
        echo '<br>';
        echo 'Template Engine Active: ✅<br>';
        echo '<small>Add ?template_debug=1 to URL to see this info</small>';
        echo '</div>';
    }

    /**
     * Check if template loading is working correctly
     *
     * @return array Status report
     */
    public function get_status_report() {
        $report = [
            'template_engine_active' => true,
            'custom_templates' => [],
            'missing_templates' => [],
            'component_integration' => class_exists('HappyPlace\\Core\\Component_Manager')
        ];

        // Check for custom templates
        foreach ($this->template_hierarchy as $type => $post_types) {
            foreach ($post_types as $post_type => $templates) {
                $found = $this->locate_template_hierarchy($templates);
                if ($found) {
                    $report['custom_templates'][$type][$post_type] = basename($found);
                } else {
                    $report['missing_templates'][$type][] = $post_type;
                }
            }
        }

        return $report;
    }
}

/**
 * Global template functions for easy access
 */

if (!function_exists('hph_get_template_part')) {
    /**
     * Load template part through Template Engine
     *
     * @param string $slug Template slug
     * @param string $name Template name
     * @param array $args Template arguments
     */
    function hph_get_template_part($slug, $name = null, $args = []) {
        $template_engine = \HappyPlace\Core\Template_Engine::instance();
        return $template_engine->load_template_part($slug, $name, $args);
    }
}

if (!function_exists('hph_get_template')) {
    /**
     * Get template output through Template Engine
     *
     * @param string $template_name Template file name
     * @param array $args Template arguments
     * @return string Template output
     */
    function hph_get_template($template_name, $args = []) {
        $template_engine = \HappyPlace\Core\Template_Engine::instance();
        return $template_engine->get_template($template_name, $args);
    }
}

if (!function_exists('hph_get_component')) {
    /**
     * Get component output through Template Engine
     *
     * @param string $component_name Component name
     * @param array $props Component properties
     * @return string Component output
     */
    function hph_get_component($component_name, $props = []) {
        $template_engine = \HappyPlace\Core\Template_Engine::instance();
        return $template_engine->get_component($component_name, $props);
    }
}

/* 
 * IMPLEMENTATION NOTES:
 * 
 * 1. IMMEDIATE FIXES NEEDED:
 *    - Replace your current Template_Engine with this enhanced version
 *    - Add component integration support
 *    - Improved error handling and debugging
 * 
 * 2. VERIFICATION STEPS:
 *    - Add ?template_debug=1 to any URL to see debug info
 *    - Check error logs for template loading messages
 *    - Use get_status_report() method to check template availability
 * 
 * 3. TESTING:
 *    - Test single listing pages load custom template
 *    - Test archive listing pages work
 *    - Test template parts load with arguments
 *    - Test component integration works
 * 
 * 4. NEXT STEPS:
 *    - Create missing templates identified by status report
 *    - Enable disabled components in /Components/_disabled/
 *    - Build out component integration in templates
 */