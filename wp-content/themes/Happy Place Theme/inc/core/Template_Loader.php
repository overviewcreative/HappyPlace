<?php
/**
 * Template Loader Class - Enhanced Version
 *
 * Handles loading of template files with WordPress template hierarchy
 *
 * @package HappyPlace
 * @subpackage Core
 * @since 2.0.0
 */

namespace HappyPlace\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Template_Loader {
    /**
     * Instance of the class
     *
     * @var Template_Loader
     */
    private static $instance = null;
    
    /**
     * Template paths in order of priority
     *
     * @var array
     */
    private array $template_paths = [];
    
    /**
     * Current template being loaded
     *
     * @var string
     */
    private string $current_template = '';
    
    /**
     * Template context data
     *
     * @var array
     */
    private array $template_context = [];

    /**
     * Get instance of the class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Static instance method for compatibility
     */
    public static function instance(): self {
        return self::get_instance();
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->setup_template_paths();
        $this->setup_hooks();
    }
    
    /**
     * Setup template paths
     */
    private function setup_template_paths(): void {
        $this->template_paths = apply_filters('happy_place_template_paths', [
            'templates/listing/',
            'templates/agent/',
            'templates/community/',
            'templates/city/',
            'templates/dashboard/',
            'templates/open-house/',
            'templates/local-place/',
            'templates/transaction/',
            'templates/',
            '',
        ]);
    }
    
    /**
     * Setup hooks
     */
    private function setup_hooks(): void {
        add_filter('template_include', [$this, 'template_loader'], 5);
        add_action('template_redirect', [$this, 'set_template_context']);
    }

    /**
     * Load a template
     *
     * @param string $template Template to load
     * @return string
     */
    public function template_loader($template) {
        $this->current_template = $template;
        
        // Check for custom templates
        $custom_template = $this->locate_custom_template($template);
        
        if ($custom_template && file_exists($custom_template)) {
            $this->load_template_assets($custom_template);
            return $custom_template;
        }

        return $template;
    }
    
    /**
     * Locate custom template
     */
    private function locate_custom_template(string $default_template): ?string {
        $template_name = basename($default_template);
        $template_candidates = $this->get_template_candidates($template_name);

        foreach ($template_candidates as $candidate) {
            $full_path = get_template_directory() . '/' . $candidate;
            if (file_exists($full_path)) {
                return $full_path;
            }
        }

        return null;
    }
    
    /**
     * Get template candidates
     */
    private function get_template_candidates(string $template_name): array {
        $candidates = [];
        $post_type = get_post_type();
        $valid_post_types = ['listing', 'agent', 'community', 'city', 'place', 'open-house', 'local-place', 'transaction'];

        // Handle CPT-specific templates
        if ($post_type && in_array($post_type, $valid_post_types)) {
            if (is_post_type_archive()) {
                $candidates[] = "templates/{$post_type}/archive-{$post_type}.php";
                $candidates[] = "templates/archive-{$post_type}.php";
                $candidates[] = "archive-{$post_type}.php";
            } elseif (is_singular()) {
                // Check for specific template first
                $template = get_page_template_slug();
                if ($template) {
                    $candidates[] = $template;
                }
                
                // Then fallback to standard templates
                $candidates[] = "templates/{$post_type}/single-{$post_type}.php";
                $candidates[] = "templates/single-{$post_type}.php";
                $candidates[] = "single-{$post_type}.php";
            }
        }

        // Add generic paths
        foreach ($this->template_paths as $path) {
            if (!empty($path)) {
                $candidates[] = $path . $template_name;
            } else {
                $candidates[] = $template_name;
            }
        }

        return array_unique($candidates);
    }
    
    /**
     * Load template assets
     */
    private function load_template_assets(string $template_path): void {
        try {
            $template_name = basename($template_path);
            
            // Try to load assets only if Assets Manager is available
            if (class_exists('\\HappyPlace\\Core\\Assets_Manager')) {
                $assets_manager = \HappyPlace\Core\Assets_Manager::instance();
                if (method_exists($assets_manager, 'enqueue_template_assets_by_name')) {
                    $assets_manager->enqueue_template_assets_by_name($template_name);
                }
            }

            do_action('hph_after_template_assets_loaded', $template_name, $template_path);
        } catch (\Exception $e) {
            error_log('HPH Template Loader: Asset loading error - ' . $e->getMessage());
        }
    }
    
    /**
     * Set template context
     */
    public function set_template_context(): void {
        $this->template_context = [
            'is_dashboard' => $this->is_dashboard_page(),
            'post_type' => get_post_type(),
            'is_archive' => is_archive(),
            'is_single' => is_single(),
            'template_name' => basename($this->current_template),
        ];
    }
    
    /**
     * Get template context
     */
    public function get_template_context(): array {
        return $this->template_context;
    }
    
    /**
     * Check if current page is dashboard
     */
    private function is_dashboard_page(): bool {
        if (!is_page()) {
            return false;
        }

        $template = get_page_template_slug();
        return $template === 'agent-dashboard.php' || 
               $template === 'templates/dashboard/agent-dashboard.php';
    }

    /**
     * Get a template part while maintaining the template hierarchy
     *
     * @param string $slug The slug name for the generic template
     * @param string $name Optional. The name of the specialized template
     * @param array  $args Optional. Additional arguments passed to the template
     * @return void
     */
    public function get_template_part(string $slug, string $name = '', array $args = []): void {
        $templates = [];
        $post_type = get_post_type();
        
        if ($name) {
            // Try post type specific template first
            if ($post_type) {
                $templates[] = "templates/{$post_type}/{$slug}-{$name}.php";
            }
            // Then try generic template-parts directory
            $templates[] = "templates/template-parts/{$slug}-{$name}.php";
            $templates[] = "template-parts/{$slug}-{$name}.php";
            $templates[] = "{$slug}-{$name}.php";
        }

        // Add fallback templates
        if ($post_type) {
            $templates[] = "templates/{$post_type}/{$slug}.php";
        }
        $templates[] = "templates/template-parts/{$slug}.php";
        $templates[] = "template-parts/{$slug}.php";
        $templates[] = "{$slug}.php";

        // Look for template file
        $template = locate_template($templates);

        if ($template) {
            if (!empty($args) && is_array($args)) {
                extract($args);
            }
            include($template);
        }
    }

    /**
     * Locate template file in theme hierarchy
     *
     * @param string|array $template_names Template file(s) to search for
     * @param bool $load Whether to load the template if found
     * @param bool $require_once Whether to require_once or require
     * @param array $args Variables to extract in scope
     * @return string The template filename if one is located
     */
    public function locate_template($template_names, bool $load = false, bool $require_once = true, array $args = []): string {
        $located = '';
        
        foreach ((array) $template_names as $template_name) {
            if (!$template_name) {
                continue;
            }
            
            // Try each template path
            foreach ($this->template_paths as $path) {
                $check_path = get_template_directory() . '/' . $path . $template_name;
                
                if (file_exists($check_path)) {
                    $located = $check_path;
                    break 2;
                }
            }
        }

        if ($load && $located) {
            if (!empty($args) && is_array($args)) {
                extract($args);
            }
            
            if ($require_once) {
                require_once($located);
            } else {
                require($located);
            }
        }

        return $located;
    }

    /**
     * Load a template part with custom arguments
     *
     * @param string $template_path Template path relative to theme
     * @param array $args Variables to extract in template scope
     * @param bool $return Whether to return output instead of echoing
     * @return string|void
     */
    public function load_template_with_args(string $template_path, array $args = [], bool $return = false) {
        $full_path = get_template_directory() . '/' . $template_path;
        
        if (!file_exists($full_path)) {
            return $return ? '' : null;
        }
        
        if ($return) {
            ob_start();
        }
        
        if (!empty($args)) {
            extract($args);
        }
        
        include $full_path;
        
        if ($return) {
            return ob_get_clean();
        }
    }

    /**
     * Legacy method compatibility
     */
    public function get_file_data($file_path) {
        $find = [];
        $file = '';

        // Get post type
        $post_type = get_post_type();

        if (is_singular('listing')) {
            $file = 'single-listing.php';
            $find[] = 'templates/listing/' . $file;
            $find[] = $file;
        } elseif (is_post_type_archive('listing')) {
            $file = 'archive-listing.php';
            $find[] = 'templates/listing/' . $file;
            $find[] = $file;
        } elseif (is_singular('agent')) {
            $file = 'single-agent.php';
            $find[] = 'templates/agent/' . $file;
            $find[] = $file;
        } elseif (is_post_type_archive('agent')) {
            $file = 'archive-agent.php';
            $find[] = 'templates/agent/' . $file;
            $find[] = $file;
        } elseif (is_page('agent-dashboard')) {
            $file = 'dashboard/agent-dashboard.php';
            $find[] = 'templates/' . $file;
            $find[] = $file;
        }

        if ($file) {
            $find = array_unique($find);
            foreach ($find as $template_file) {
                $located = locate_template($template_file);
                if ($located) {
                    return $located;
                }
            }
        }

        return $template;
    }
}

// Maintain backward compatibility
if (!function_exists('hph_get_template_part')) {
    function hph_get_template_part(string $slug, string $name = '', array $args = []): void {
        Template_Loader::instance()->get_template_part($slug, $name, $args);
    }
}

if (!function_exists('hph_locate_template')) {
    function hph_locate_template($template_names, bool $load = false, bool $require_once = true, array $args = []): string {
        return Template_Loader::instance()->locate_template($template_names, $load, $require_once, $args);
    }
}
