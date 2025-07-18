<?php
/**
 * Base Component Class - Enhanced
 * 
 * Provides the foundation for all theme components with props validation,
 * performance optimization, and integration capabilities.
 *
 * @package HappyPlace\Components
 * @since 2.0.0
 */

namespace HappyPlace\Components;

use HappyPlace\Components\Props\Component_Validator;
use HappyPlace\Components\Props\Component_Validation_Exception;
use HappyPlace\Performance\Asset_Manager;
use HappyPlace\Analytics\Component_Analytics;

abstract class Base_Component {
    
    /**
     * Component data (typically from ACF or post meta)
     * @var array
     */
    protected $data;
    
    /**
     * Component properties/configuration
     * @var array
     */
    protected $props;
    
    /**
     * CSS classes for the component
     * @var array
     */
    protected $css_classes;
    
    /**
     * Validation rules for props
     * @var array
     */
    protected $validation_rules;
    
    /**
     * Performance metrics for this component
     * @var array
     */
    protected $performance_metrics;
    
    /**
     * Component cache key
     * @var string
     */
    protected $cache_key;
    
    /**
     * Whether this component should be cached
     * @var bool
     */
    protected $cacheable = true;
    
    /**
     * Cache duration in seconds
     * @var int
     */
    protected $cache_duration = 3600; // 1 hour
    
    /**
     * Constructor
     *
     * @param array $data Component data
     * @param array $props Component properties
     */
    public function __construct($data = [], $props = []) {
        $this->data = $this->process_acf_data($data);
        $this->props = $this->merge_with_defaults($props);
        
        // Validate props if validation rules exist
        if (!empty($this->get_prop_definitions())) {
            $this->validate_props($this->props);
        }
        
        $this->css_classes = $this->build_css_classes();
        $this->cache_key = $this->generate_cache_key();
        
        // Track component usage for analytics
        $this->track_component_usage();
    }
    
    /**
     * Abstract methods that must be implemented by child classes
     */
    abstract protected function render_content();
    abstract protected function get_defaults();
    abstract protected function get_prop_definitions();
    
    /**
     * Get ACF field mapping for data transformation
     * Override in child classes to define field mappings
     *
     * @return array
     */
    protected function get_acf_mapping() {
        return [];
    }
    
    /**
     * Validate component props against defined rules
     *
     * @param array $props
     * @throws Component_Validation_Exception
     */
    protected function validate_props($props) {
        try {
            $validator = new Component_Validator($this->get_prop_definitions());
            $validator->validate($props);
        } catch (Component_Validation_Exception $e) {
            if (WP_DEBUG) {
                error_log("Component validation error in " . static::class . ": " . $e->getMessage());
            }
            throw $e;
        }
    }
    
    /**
     * Process ACF data and transform according to mapping
     *
     * @param array $data
     * @return array
     */
    protected function process_acf_data($data) {
        $mapping = $this->get_acf_mapping();
        
        if (empty($mapping)) {
            return $data;
        }
        
        $processed = [];
        
        foreach ($mapping as $acf_field => $component_key) {
            if (isset($data[$acf_field])) {
                $processed[$component_key] = $data[$acf_field];
            }
        }
        
        // Merge with original data (processed takes precedence)
        return array_merge($data, $processed);
    }
    
    /**
     * Merge props with defaults
     *
     * @param array $props
     * @return array
     */
    protected function merge_with_defaults($props) {
        $defaults = $this->get_defaults();
        return wp_parse_args($props, $defaults);
    }
    
    /**
     * Build CSS classes array for the component
     *
     * @return array
     */
    protected function build_css_classes() {
        $component_name = $this->get_component_name();
        $classes = [$component_name];
        
        // Add variant classes
        if (!empty($this->props['variant'])) {
            $classes[] = "{$component_name}--{$this->props['variant']}";
        }
        
        // Add context classes
        if (!empty($this->props['context'])) {
            $classes[] = "{$component_name}--context-{$this->props['context']}";
        }
        
        // Add size classes
        if (!empty($this->props['size'])) {
            $classes[] = "{$component_name}--{$this->props['size']}";
        }
        
        // Add custom classes
        if (!empty($this->props['css_class'])) {
            if (is_array($this->props['css_class'])) {
                $classes = array_merge($classes, $this->props['css_class']);
            } else {
                $classes[] = $this->props['css_class'];
            }
        }
        
        return $classes;
    }
    
    /**
     * Get component name from class name
     *
     * @return string
     */
    protected function get_component_name() {
        $class_name = basename(str_replace('\\', '/', static::class));
        return strtolower(str_replace('_', '-', $class_name));
    }
    
    /**
     * Enqueue component-specific assets
     */
    protected function enqueue_assets() {
        if (class_exists('HappyPlace\\Performance\\Asset_Manager')) {
            $asset_manager = new Asset_Manager();
            $asset_manager->enqueue_for_component(static::class);
        }
    }
    
    /**
     * Track component usage for analytics
     */
    protected function track_component_usage() {
        if (WP_DEBUG && class_exists('HappyPlace\\Analytics\\Component_Analytics')) {
            Component_Analytics::track_usage(static::class, $this->props);
        }
    }
    
    /**
     * Main render method with caching and performance tracking
     *
     * @return string
     */
    public function render() {
        // Start performance tracking
        $start_time = microtime(true);
        do_action('hph_component_render_start', static::class);
        
        // Check cache first
        if ($this->cacheable) {
            $cached = $this->get_cached_output();
            if ($cached !== false) {
                $this->track_cache_hit();
                return $cached;
            }
        }
        
        // Enqueue assets
        $this->enqueue_assets();
        
        // Render content
        $output = $this->render_content();
        
        // Cache the output
        if ($this->cacheable && !empty($output)) {
            $this->cache_output($output);
        }
        
        // End performance tracking
        $render_time = microtime(true) - $start_time;
        do_action('hph_component_render_end', static::class, $render_time);
        
        return $output;
    }
    
    /**
     * Generate unique cache key for this component instance
     *
     * @return string
     */
    protected function generate_cache_key() {
        $key_data = [
            'class' => static::class,
            'data' => $this->data,
            'props' => $this->props,
            'version' => '2.0.0'
        ];
        
        return 'hph_component_' . md5(serialize($key_data));
    }
    
    /**
     * Get cached component output
     *
     * @return string|false
     */
    protected function get_cached_output() {
        return wp_cache_get($this->cache_key, 'hph_components');
    }
    
    /**
     * Cache component output
     *
     * @param string $output
     */
    protected function cache_output($output) {
        wp_cache_set($this->cache_key, $output, 'hph_components', $this->cache_duration);
    }
    
    /**
     * Track cache hit for analytics
     */
    protected function track_cache_hit() {
        if (WP_DEBUG) {
            do_action('hph_component_cache_hit', static::class);
        }
    }
    
    /**
     * Invalidate component cache
     */
    public function invalidate_cache() {
        wp_cache_delete($this->cache_key, 'hph_components');
    }
    
    /**
     * Build HTML attributes from array
     *
     * @param array $attributes
     * @return string
     */
    protected function build_attributes($attributes) {
        $html_attributes = [];
        
        foreach ($attributes as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            
            if ($value === true) {
                $html_attributes[] = esc_attr($key);
            } else {
                $html_attributes[] = esc_attr($key) . '="' . esc_attr($value) . '"';
            }
        }
        
        return implode(' ', $html_attributes);
    }
    
    /**
     * Get CSS classes as string
     *
     * @return string
     */
    protected function get_css_classes() {
        return esc_attr(implode(' ', array_filter($this->css_classes)));
    }
    
    /**
     * Render component with error handling
     *
     * @return string
     */
    public function safe_render() {
        try {
            return $this->render();
        } catch (\Exception $e) {
            if (WP_DEBUG) {
                error_log("Component render error in " . static::class . ": " . $e->getMessage());
                return "<!-- Component Error: " . esc_html($e->getMessage()) . " -->";
            }
            return '';
        }
    }
    
    /**
     * Get component data for JSON output
     *
     * @return array
     */
    public function to_array() {
        return [
            'component' => static::class,
            'data' => $this->data,
            'props' => $this->props,
            'cache_key' => $this->cache_key
        ];
    }
    
    /**
     * Static factory method for easy instantiation
     *
     * @param array $data
     * @param array $props
     * @return static
     */
    public static function create($data = [], $props = []) {
        return new static($data, $props);
    }
    
    /**
     * Static render method for quick rendering
     *
     * @param array $data
     * @param array $props
     * @return string
     */
    public static function render_static($data = [], $props = []) {
        $component = static::create($data, $props);
        return $component->render();
    }
}
