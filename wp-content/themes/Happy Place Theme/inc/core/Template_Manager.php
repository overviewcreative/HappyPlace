<?php
/**
 * Template Manager Class
 *
 * @package HappyPlace
 * @subpackage Core
 */

namespace HappyPlace\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Template_Manager {
    private static ?self $instance = null;
    private array $template_paths = [];
    private string $current_template = '';
    private array $template_context = [];

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->setup_template_paths();
        $this->setup_hooks();
    }

    private function setup_template_paths(): void {
        $this->template_paths = apply_filters('happy_place_template_paths', [
            'global'    => 'template-parts/global/',
            'listing'   => 'template-parts/listing/',
            'agent'     => 'template-parts/agent/',
            'dashboard' => 'template-parts/dashboard/',
            'cards'     => 'template-parts/cards/',
            'forms'     => 'template-parts/forms/',
        ]);
    }

    private function setup_hooks(): void {
        add_filter('template_include', [$this, 'template_loader'], 10);
        add_action('template_redirect', [$this, 'set_template_context']);
    }

    public function template_loader($template): string {
        $find = [];
        $file = '';

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

        if ($file && $find) {
            $located_template = $this->locate_template($find);
            if ($located_template) {
                $template = $located_template;
            }
        }

        $this->current_template = $template;
        return $template;
    }

    public function locate_template($templates): ?string {
        if (empty($templates)) {
            return null;
        }

        $located = null;

        foreach ((array)$templates as $template) {
            if (!$template) {
                continue;
            }

            if (file_exists(get_stylesheet_directory() . '/' . $template)) {
                $located = get_stylesheet_directory() . '/' . $template;
                break;
            } elseif (file_exists(get_template_directory() . '/' . $template)) {
                $located = get_template_directory() . '/' . $template;
                break;
            }
        }

        return $located;
    }

    public function get_template_part(string $slug, string $name = '', array $args = []): void {
        $templates = [];
        if (!empty($name)) {
            $templates[] = "{$slug}-{$name}.php";
        }
        $templates[] = "{$slug}.php";

        $located = $this->locate_template($templates);
        if ($located) {
            $this->load_template($located, $args);
        }
    }

    private function load_template(string $template_path, array $args = []): void {
        if (!empty($args) && is_array($args)) {
            extract($args);
        }

        include($template_path);
    }

    public function set_template_context(): void {
        $this->template_context = [
            'post_type' => get_post_type(),
            'is_archive' => is_archive(),
            'is_singular' => is_singular(),
            'is_dashboard' => is_page('agent-dashboard'),
            'template_path' => $this->current_template
        ];
    }

    public function get_template_context(): array {
        return $this->template_context;
    }

    public function get_template_paths(): array {
        return $this->template_paths;
    }
}

// Initialize the template manager with lower priority
add_action('init', function() {
    Template_Manager::instance();
}, 5);
