<?php

namespace HappyPlace\Components;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base Component Class
 * 
 * All components in the Happy Place theme extend this base class.
 * Provides consistent interface, validation, caching, and error handling.
 * 
 * @package HappyPlace\Components
 * @version 2.0.0
 */
abstract class Base_Component {
    
    /**
     * Component properties
     * @var array
     */
    protected $props = [];
    
    /**
     * Validation errors
     * @var array
     */
    protected $validation_errors = [];
    
    /**
     * Component cache key
     * @var string
     */
    protected $cache_key = '';
    
    /**
     * Whether component is initialized
     * @var bool
     */
    protected $initialized = false;
    
    /**
     * Component performance tracking
     * @var array
     */
    protected $performance = [
        'start_time' => 0,
        'end_time' => 0,
        'memory_start' => 0,
        'memory_end' => 0
    ];
    
    /**
     * Constructor
     * 
     * @param array $props Component properties
     */
    public function __construct($props = []) {
        $this->start_performance_tracking();
        
        // Merge with defaults
        $this->props = wp_parse_args($props, $this->get_defaults());
        
        // Validate properties
        $this->validate_props();
        
        // Initialize if validation passed
        if (empty($this->validation_errors)) {
            $this->init();
            $this->initialized = true;
        }
        
        $this->end_performance_tracking();
    }
    
    /**
     * Get component name (must be implemented by child classes)
     * 
     * @return string
     */
    abstract protected function get_component_name();
    
    /**
     * Get default properties (must be implemented by child classes)
     * 
     * @return array
     */
    abstract protected function get_defaults();
    
    /**
     * Render component (must be implemented by child classes)
     * 
     * @return string
     */
    abstract protected function render();
    
    /**
     * Validate component properties (can be overridden by child classes)
     */
    protected function validate_props() {
        // Base validation - override in child classes for specific validation
    }
    
    /**
     * Initialize component (can be overridden by child classes)
     */
    protected function init() {
        // Base initialization - override in child classes for specific setup
    }
    
    /**
     * Display component output
     * 
     * @param bool $echo Whether to echo output or return it
     * @return string Component HTML output
     */
    public function display($echo = true) {
        if (!$this->initialized) {
            $error_output = $this->render_error_state();
            
            if ($echo) {
                echo $error_output;
            }
            
            return $error_output;
        }
        
        $output = $this->get_cached_output();
        
        if ($output === false) {
            $output = $this->render();
            $this->cache_output($output);
        }
        
        // Add debug information if in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG && isset($_GET['component_debug'])) {
            $output = $this->add_debug_info($output);
        }
        
        if ($echo) {
            echo $output;
        }
        
        return $output;
    }
    
    /**
     * Get component property
     * 
     * @param string $key Property key
     * @param mixed $default Default value if property doesn't exist
     * @return mixed Property value
     */
    protected function get_prop($key, $default = null) {
        return isset($this->props[$key]) ? $this->props[$key] : $default;
    }
    
    /**
     * Set component property
     * 
     * @param string $key Property key
     * @param mixed $value Property value
     */
    protected function set_prop($key, $value) {
        $this->props[$key] = $value;
    }
    
    /**
     * Get all component properties
     * 
     * @return array All properties
     */
    protected function get_props() {
        return $this->props;
    }
    
    /**
     * Add validation error
     * 
     * @param string $message Error message
     */
    protected function add_validation_error($message) {
        $this->validation_errors[] = $message;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("HPH Component Validation Error ({$this->get_component_name()}): {$message}");
        }
    }
    
    /**
     * Check if component has validation errors
     * 
     * @return bool
     */
    public function has_errors() {
        return !empty($this->validation_errors);
    }
    
    /**
     * Get validation errors
     * 
     * @return array
     */
    public function get_errors() {
        return $this->validation_errors;
    }
    
    /**
     * Render error state
     * 
     * @return string Error HTML
     */
    protected function render_error_state() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $component_name = $this->get_component_name();
            $errors = implode(', ', $this->validation_errors);
            
            return sprintf(
                '<div class="hph-component-error" data-component="%s" style="background: #fee; border: 1px solid #fcc; padding: 1rem; border-radius: 4px; color: #c33; font-family: monospace; font-size: 0.875rem;">
                    <strong>Component Error:</strong> %s<br>
                    <strong>Errors:</strong> %s
                </div>',
                esc_attr($component_name),
                esc_html($component_name),
                esc_html($errors)
            );
        }
        
        // In production, return empty string or fallback content
        return '';
    }
    
    /**
     * Get cache key for component
     * 
     * @return string
     */
    protected function get_cache_key() {
        if (empty($this->cache_key)) {
            $component_name = $this->get_component_name();
            $props_hash = md5(serialize($this->props));
            $this->cache_key = "hph_component_{$component_name}_{$props_hash}";
        }
        
        return $this->cache_key;
    }
    
    /**
     * Get cached component output
     * 
     * @return string|false Cached output or false if not cached
     */
    protected function get_cached_output() {
        // Only cache if component supports it and caching is enabled
        if (!$this->supports_caching()) {
            return false;
        }
        
        $cache_key = $this->get_cache_key();
        return wp_cache_get($cache_key, 'hph_components');
    }
    
    /**
     * Cache component output
     * 
     * @param string $output Component output to cache
     */
    protected function cache_output($output) {
        if (!$this->supports_caching()) {
            return;
        }
        
        $cache_key = $this->get_cache_key();
        $cache_duration = $this->get_cache_duration();
        
        wp_cache_set($cache_key, $output, 'hph_components', $cache_duration);
    }
    
    /**
     * Check if component supports caching
     * 
     * @return bool
     */
    protected function supports_caching() {
        return $this->get_prop('enable_caching', true);
    }
    
    /**
     * Get cache duration in seconds
     * 
     * @return int
     */
    protected function get_cache_duration() {
        return $this->get_prop('cache_duration', 3600); // 1 hour default
    }
    
    /**
     * Clear component cache
     */
    public function clear_cache() {
        $cache_key = $this->get_cache_key();
        wp_cache_delete($cache_key, 'hph_components');
    }
    
    /**
     * Start performance tracking
     */
    protected function start_performance_tracking() {
        $this->performance['start_time'] = microtime(true);
        $this->performance['memory_start'] = memory_get_usage(true);
    }
    
    /**
     * End performance tracking
     */
    protected function end_performance_tracking() {
        $this->performance['end_time'] = microtime(true);
        $this->performance['memory_end'] = memory_get_usage(true);
    }
    
    /**
     * Get performance metrics
     * 
     * @return array Performance data
     */
    public function get_performance_metrics() {
        return [
            'execution_time' => round(($this->performance['end_time'] - $this->performance['start_time']) * 1000, 2),
            'memory_usage' => round(($this->performance['memory_end'] - $this->performance['memory_start']) / 1024 / 1024, 2),
            'component_name' => $this->get_component_name(),
            'props_count' => count($this->props),
            'cached' => $this->get_cached_output() !== false
        ];
    }
    
    /**
     * Add debug information to component output
     * 
     * @param string $output Original component output
     * @return string Output with debug info
     */
    protected function add_debug_info($output) {
        $metrics = $this->get_performance_metrics();
        $debug_info = sprintf(
            '<!-- HPH Component Debug: %s | %sms | %sMB | Props: %d | Cached: %s -->',
            $metrics['component_name'],
            $metrics['execution_time'],
            $metrics['memory_usage'],
            $metrics['props_count'],
            $metrics['cached'] ? 'Yes' : 'No'
        );
        
        return $debug_info . "\n" . $output . "\n" . str_replace('<!--', '<!-- End ', $debug_info);
    }
    
    /**
     * Sanitize and escape component output
     * 
     * @param string $output Raw output
     * @param string $context Escaping context (html, attr, url, etc.)
     * @return string Sanitized output
     */
    protected function sanitize_output($output, $context = 'html') {
        switch ($context) {
            case 'attr':
                return esc_attr($output);
            case 'url':
                return esc_url($output);
            case 'js':
                return esc_js($output);
            case 'textarea':
                return esc_textarea($output);
            case 'html':
            default:
                return wp_kses_post($output);
        }
    }
    
    /**
     * Log component activity (for debugging)
     * 
     * @param string $message Log message
     * @param string $level Log level (error, warning, info, debug)
     */
    protected function log($message, $level = 'debug') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $component_name = $this->get_component_name();
            error_log("HPH Component {$level} ({$component_name}): {$message}");
        }
    }
    
    /**
     * Generate unique ID for component instance
     * 
     * @return string Unique ID
     */
    protected function get_unique_id() {
        static $instance_counter = 0;
        $instance_counter++;
        
        return $this->get_component_name() . '_' . $instance_counter;
    }
    
    /**
     * Check if running in admin context
     * 
     * @return bool
     */
    protected function is_admin() {
        return is_admin() && !wp_doing_ajax();
    }
    
    /**
     * Check if running in AJAX context
     * 
     * @return bool
     */
    protected function is_ajax() {
        return wp_doing_ajax();
    }
    
    /**
     * Check if running in REST API context
     * 
     * @return bool
     */
    protected function is_rest() {
        return defined('REST_REQUEST') && REST_REQUEST;
    }
    
    /**
     * Magic method to handle property access
     * 
     * @param string $name Property name
     * @return mixed Property value
     */
    public function __get($name) {
        return $this->get_prop($name);
    }
    
    /**
     * Magic method to handle property setting
     * 
     * @param string $name Property name
     * @param mixed $value Property value
     */
    public function __set($name, $value) {
        $this->set_prop($name, $value);
    }
    
    /**
     * Magic method to check if property exists
     * 
     * @param string $name Property name
     * @return bool
     */
    public function __isset($name) {
        return isset($this->props[$name]);
    }
    
    /**
     * Convert component to string (calls display)
     * 
     * @return string
     */
    public function __toString() {
        try {
            return $this->display(false);
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                return $this->render_error_state();
            }
            return '';
        }
    }
}
