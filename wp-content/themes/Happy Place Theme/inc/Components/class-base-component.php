<?php
/**
 * Base Component Class
 *
 * Abstract base class for all theme components
 *
 * @package HappyPlace\Components
 * @since 2.0.0
 */

namespace HappyPlace\Components;

use HappyPlace\Components\Props\Component_Validation_Exception;

if (!defined('ABSPATH')) {
    exit;
}

abstract class Base_Component {
    
    /**
     * Component properties
     * @var array
     */
    protected $props = [];
    
    /**
     * Default properties
     * @var array
     */
    protected $defaults = [];
    
    /**
     * Component validation errors
     * @var array
     */
    protected $validation_errors = [];
    
    /**
     * Constructor
     *
     * @param array $props Component properties
     * @throws Component_Validation_Exception
     */
    public function __construct($props = []) {
        $this->props = wp_parse_args($props, $this->get_defaults());
        $this->validate_props();
        
        if (!empty($this->validation_errors)) {
            throw new Component_Validation_Exception(
                'Component validation failed',
                $this->validation_errors
            );
        }
        
        $this->init();
    }
    
    /**
     * Get default properties
     *
     * @return array
     */
    abstract protected function get_defaults();
    
    /**
     * Render the component
     *
     * @return string
     */
    abstract protected function render();
    
    /**
     * Get component name
     *
     * @return string
     */
    abstract protected function get_component_name();
    
    /**
     * Initialize component (override in child classes)
     */
    protected function init() {
        // Override in child classes
    }
    
    /**
     * Validate component properties
     */
    protected function validate_props() {
        // Override in child classes for specific validation
    }
    
    /**
     * Display the component
     *
     * @param bool $echo Whether to echo the output
     * @return string
     */
    public function display($echo = true) {
        $output = $this->render();
        
        if ($echo) {
            echo $output;
            return '';
        }
        
        return $output;
    }
    
    /**
     * Get a property value
     *
     * @param string $key Property key
     * @param mixed $default Default value
     * @return mixed
     */
    protected function get_prop($key, $default = null) {
        return isset($this->props[$key]) ? $this->props[$key] : $default;
    }
    
    /**
     * Set a property value
     *
     * @param string $key Property key
     * @param mixed $value Property value
     */
    protected function set_prop($key, $value) {
        $this->props[$key] = $value;
    }
    
    /**
     * Add validation error
     *
     * @param string $error Error message
     */
    protected function add_validation_error($error) {
        $this->validation_errors[] = $error;
    }
    
    /**
     * Get CSS classes for component
     *
     * @return string
     */
    protected function get_css_classes() {
        $base_class = 'hph-' . str_replace('_', '-', strtolower($this->get_component_name()));
        $custom_classes = $this->get_prop('css_classes', '');
        
        return trim($base_class . ' ' . $custom_classes);
    }
    
    /**
     * Generate HTML attributes
     *
     * @param array $attributes
     * @return string
     */
    protected function render_attributes($attributes) {
        $output = '';
        
        foreach ($attributes as $key => $value) {
            if (is_array($value)) {
                $value = implode(' ', $value);
            }
            $output .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
        }
        
        return $output;
    }
}
