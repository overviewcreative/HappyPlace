<?php

/**
 * Plugin Template Loader
 *
 * Handles loading of plugin templates while maintaining theme overridability.
 *
 * @package HappyPlace
 * @subpackage TemplateLoader
 */

namespace HappyPlace;

if (!defined('ABSPATH')) {
    exit;
}

class Template_Loader
{
    /**
     * Template paths in order of priority
     *
     * @var array
     */
    private $template_paths;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->template_paths = [
            'theme'  => get_template_directory() . '/happy-place/',
            'plugin' => HPH_PLUGIN_DIR . '/templates/'
        ];

        // Add filters for template loading
        add_filter('template_include', [$this, 'maybe_load_plugin_template'], 20);
    }

    /**
     * Check and load plugin template if needed
     *
     * @param string $template Current template path
     * @return string Modified template path
     */
    public function maybe_load_plugin_template($template)
    {
        // Get current page template
        $page_template = get_post_meta(get_the_ID(), '_wp_page_template', true);

        // List of templates that should be handled by the plugin
        $plugin_templates = [
            'templates/template-dashboard.php',
            'agent-dashboard.php',
            'templates/template-listing-search.php',
            'templates/template-agent-profile.php'
        ];

        if (in_array($page_template, $plugin_templates)) {
            $template_name = basename($page_template);
            $template = $this->locate_template($template_name);
        }

        return $template;
    }

    /**
     * Locate a template file
     *
     * @param string $template_name Template file to locate
     * @return string Path to the template file
     */
    public function locate_template($template_name)
    {
        $template = '';

        // First check in theme's happy-place directory
        if (file_exists($this->template_paths['theme'] . $template_name)) {
            $template = $this->template_paths['theme'] . $template_name;
        }
        // Then check in plugin's templates directory
        elseif (file_exists($this->template_paths['plugin'] . $template_name)) {
            $template = $this->template_paths['plugin'] . $template_name;
        }

        return apply_filters('happy_place_locate_template', $template, $template_name);
    }

    /**
     * Load a template part
     *
     * @param string $slug Template slug
     * @param string $name Optional. Template variation
     * @param array  $args Optional. Variables to pass to the template
     */
    public function get_template_part($slug, $name = '', $args = [])
    {
        $template = '';

        // Look for template in theme first
        if ($name) {
            $template = $this->locate_template("{$slug}-{$name}.php");
        }

        if (!$template) {
            $template = $this->locate_template("{$slug}.php");
        }

        // Allow 3rd party plugins to modify template
        $template = apply_filters('happy_place_get_template_part', $template, $slug, $name);

        if ($template) {
            if ($args && is_array($args)) {
                extract($args);
            }

            include $template;
        }
    }
}
