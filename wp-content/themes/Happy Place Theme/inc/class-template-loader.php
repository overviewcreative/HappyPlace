<?php

/**
 * Template Loader Class - Fixed Version
 * 
 * @package HappyPlace
 */

namespace HappyPlace\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Template_Loader
{
    private static ?self $instance = null;
    private array $template_paths = [];
    private string $current_template = '';
    private array $template_context = [];

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->setup_template_paths();
        $this->setup_hooks();
    }

    private function setup_template_paths(): void
    {
        $this->template_paths = apply_filters('happy_place_template_paths', [
            'templates/listing/',
            'templates/agent/',
            'templates/community/',
            'templates/city/',
            'templates/dashboard/',
            'templates/',
            '',
        ]);
    }

    private function setup_hooks(): void
    {
        // Lower priority to not interfere with dashboard
        add_filter('template_include', [$this, 'custom_template_include'], 5);
        add_action('template_redirect', [$this, 'set_template_context']);
    }

    public function custom_template_include(string $template): string
    {
        // Skip template loading for dashboard pages to avoid conflicts
        if ($this->is_dashboard_page()) {
            return $template;
        }

        $custom_template = $this->locate_custom_template($template);

        if ($custom_template) {
            $this->current_template = $custom_template;
            $this->load_template_assets($custom_template);
            return $custom_template;
        }

        return $template;
    }

    private function is_dashboard_page(): bool
    {
        // Check multiple ways a dashboard might be identified
        if (is_page()) {
            $template = get_page_template_slug();
            
            // Check for various dashboard template patterns
            return $template === 'agent-dashboard.php' || 
                   $template === 'templates/dashboard/agent-dashboard.php' ||
                   strpos($template, 'dashboard') !== false ||
                   (function_exists('hph_is_dashboard') && hph_is_dashboard());
        }
        
        return false;
    }

    private function locate_custom_template(string $default_template): ?string
    {
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

    private function get_template_candidates(string $template_name): array
    {
        $candidates = [];
        $post_type = get_post_type();

        // Handle CPT-specific templates
        if ($post_type && in_array($post_type, ['listing', 'agent', 'community', 'city'])) {
            if (is_post_type_archive()) {
                $candidates[] = "templates/{$post_type}/archive-{$post_type}.php";
                $candidates[] = "templates/archive-{$post_type}.php";
                $candidates[] = "archive-{$post_type}.php";
            } elseif (is_singular()) {
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

    private function load_template_assets(string $template_path): void
    {
        try {
            $template_name = basename($template_path);
            
            // Try to load assets only if Assets Manager is available
            if (class_exists('HappyPlace\\Core\\Assets_Manager')) {
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

    public function set_template_context(): void
    {
        $this->template_context = [
            'is_dashboard' => $this->is_dashboard_page(),
            'post_type' => get_post_type(),
            'is_archive' => is_archive(),
            'is_single' => is_single(),
            'template_name' => basename($this->current_template),
        ];
    }

    public function get_template_context(): array
    {
        return $this->template_context;
    }
}

// Initialize with lower priority to avoid conflicts
add_action('init', function () {
    Template_Loader::instance();
}, 15);