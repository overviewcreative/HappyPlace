<?php
/**
 * Base Component Class
 * 
 * @package HappyPlace\Components
 */

namespace HappyPlace\Components;

if (!defined('ABSPATH')) {
    exit;
}

abstract class Base_Component {
    
    /**
     * Component configuration
     */
    protected $config = [];
    
    /**
     * Constructor
     */
    public function __construct($config = []) {
        $this->config = array_merge($this->get_defaults(), $config);
    }
    
    /**
     * Get component defaults - must be implemented by child classes
     */
    protected function get_defaults() {
        return [];
    }
    
    /**
     * Render the component - must be implemented by child classes
     */
    abstract public function render($data = []);
    
    /**
     * Get component name - must be implemented by child classes
     */
    abstract public function get_component_name();
    
    /**
     * Get configuration value
     */
    protected function get_config($key, $default = null) {
        return $this->config[$key] ?? $default;
    }
    
    /**
     * Set configuration value
     */
    protected function set_config($key, $value) {
        $this->config[$key] = $value;
    }
}
