<?php
/**
 * Template Engine Class
 *
 * Handles template loading and template part management
 *
 * @package HappyPlace
 * @subpackage Core
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
        add_filter('template_include', [$this, 'template_loader'], 10);
    }

    /**
     * Main template loader
     *
     * @param string $template
     * @return string
     */
    public function template_loader($template) {
        global $post;

        if (!$post) {
            return $template;
        }

        // Handle listing single templates
        if (is_singular('listing')) {
            $custom_template = locate_template('templates/single-listing.php');
            if ($custom_template) {
                return $custom_template;
            }
        }

        // Handle agent single templates
        if (is_singular('agent')) {
            $custom_template = locate_template('templates/single-agent.php');
            if ($custom_template) {
                return $custom_template;
            }
        }

        // Handle listing archive templates
        if (is_post_type_archive('listing')) {
            $custom_template = locate_template('templates/archive-listing.php');
            if ($custom_template) {
                return $custom_template;
            }
        }

        return $template;
    }

    /**
     * Load a template part
     *
     * @param string $slug
     * @param string $name
     * @param array $args
     * @return bool
     */
    public function load_template_part($slug, $name = null, $args = []) {
        if (!empty($args) && is_array($args)) {
            extract($args);
        }

        $template = '';

        // Look for template parts in order of priority
        if ($name) {
            $template = locate_template([
                "template-parts/{$slug}-{$name}.php",
                "template-parts/{$slug}.php"
            ]);
        } else {
            $template = locate_template("template-parts/{$slug}.php");
        }

        if ($template) {
            include $template;
            return true;
        }

        return false;
    }

    /**
     * Get template with data
     *
     * @param string $template_name
     * @param array $args
     * @return string
     */
    public function get_template($template_name, $args = []) {
        if (!empty($args) && is_array($args)) {
            extract($args);
        }

        ob_start();
        
        $template = locate_template($template_name);
        if ($template) {
            include $template;
        }

        return ob_get_clean();
    }
}
