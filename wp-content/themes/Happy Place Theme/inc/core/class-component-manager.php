<?php
/**
 * Component Manager Class
 *
 * Orchestrates component loading and management
 *
 * @package HappyPlace
 * @subpackage Core
 */

namespace HappyPlace\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Component_Manager {
    /**
     * Instance of this class
     *
     * @var Component_Manager
     */
    private static $instance = null;

    /**
     * Registered components
     *
     * @var array
     */
    private $components = [];

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
     * Constructor
     */
    private function __construct() {
        $this->load_base_component();
        $this->register_default_components();
    }

    /**
     * Load base component class
     */
    private function load_base_component() {
        $base_component_path = get_template_directory() . '/inc/components/class-base-component.php';
        if (file_exists($base_component_path)) {
            require_once $base_component_path;
        }
    }

    /**
     * Register default components
     */
    private function register_default_components() {
        // Auto-load component classes
        $component_dirs = [
            'listing',
            'agent', 
            'ui',
            'layout'
        ];

        foreach ($component_dirs as $dir) {
            $this->load_components_from_dir($dir);
        }
    }

    /**
     * Load components from directory
     *
     * @param string $dir_name
     */
    private function load_components_from_dir($dir_name) {
        $components_dir = get_template_directory() . '/inc/components/' . $dir_name;
        
        if (!is_dir($components_dir)) {
            return;
        }

        $files = glob($components_dir . '/class-*.php');
        
        foreach ($files as $file) {
            require_once $file;
            
            // Register component based on filename
            $component_name = $this->get_component_name_from_file($file);
            if ($component_name) {
                $this->register_component($component_name, $dir_name);
            }
        }
    }

    /**
     * Get component name from file path
     *
     * @param string $file_path
     * @return string|null
     */
    private function get_component_name_from_file($file_path) {
        $filename = basename($file_path, '.php');
        $component_name = str_replace('class-', '', $filename);
        return $component_name;
    }

    /**
     * Register a component
     *
     * @param string $name
     * @param string $category
     */
    public function register_component($name, $category = 'general') {
        $this->components[$category][$name] = [
            'name' => $name,
            'category' => $category,
            'loaded' => true
        ];
    }

    /**
     * Get registered components
     *
     * @param string|null $category
     * @return array
     */
    public function get_components($category = null) {
        if ($category) {
            return $this->components[$category] ?? [];
        }
        return $this->components;
    }

    /**
     * Check if component exists
     *
     * @param string $name
     * @param string $category
     * @return bool
     */
    public function component_exists($name, $category = null) {
        if ($category) {
            return isset($this->components[$category][$name]);
        }

        foreach ($this->components as $cat_components) {
            if (isset($cat_components[$name])) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Initialize component manager
     */
    public static function init() {
        return self::instance();
    }
}
