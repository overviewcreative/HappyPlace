<?php

/**
 * Template Functions
 *
 * Core template functions for Happy Place Plugin
 *
 * @package HappyPlace
 * @subpackage Core
 */

namespace HappyPlace\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get template file with fallback
 *
 * @param string $template_name Template file to load
 * @param array  $args Variables to pass to template
 * @param string $template_path Path to check first
 * @param string $default_path Fallback path
 */
function get_template($template_name, $args = [], $template_path = '', $default_path = '')
{
    // Try using Theme's Template_Loader if available
    if (class_exists('\\HappyPlace\\Core\\Template_Loader')) {
        $template_loader = Template_Loader::instance();
        $template_loader->get_template_part($template_name, $args);
        return;
    }

    // Fallback to traditional template loading
    if ($args && is_array($args)) {
        extract($args);
    }

    $located = locate_template(
        [
            trailingslashit($template_path) . $template_name,
            $template_name,
        ]
    );

    if (!$located && $default_path) {
        $located = $default_path . $template_name;
    }

    if ($located && file_exists($located)) {
        include $located;
    }
}

/**
 * Load a template part into a template
 *
 * @param string $slug The slug name for the generic template
 * @param string $name The name of the specialized template
 * @param array  $args Additional arguments passed to the template
 */
function get_template_part($slug, $name = '', $args = [])
{
    // Try using Theme's Template_Loader if available
    if (class_exists('\\HappyPlace\\Core\\Template_Loader')) {
        $template_loader = Template_Loader::instance();
        $template_part = $name ? "{$slug}-{$name}" : $slug;
        $template_loader->get_template_part($template_part, $args);
        return;
    }

    // Fallback to traditional template loading
    $template = '';

    // Look in theme/slug-name.php and theme/template-parts/slug-name.php
    if ($name) {
        $template = locate_template([
            "templates/{$slug}-{$name}.php",
            "template-parts/{$slug}-{$name}.php",
            "{$slug}-{$name}.php",
        ]);
    }

    // If template is not found, look for theme/slug.php and theme/template-parts/slug.php
    if (!$template) {
        $template = locate_template([
            "templates/{$slug}.php",
            "template-parts/{$slug}.php",
            "{$slug}.php",
        ]);
    }

    // Allow plugins/themes to override the default template
    $template = apply_filters('hph_get_template_part', $template, $slug, $name);

    if ($template) {
        load_template($template, false, $args);
    }
}

/**
 * Get other templates passing attributes and including the file
 *
 * @param string $template_name Template file to load
 * @param array  $args         Args passed for the template file
 * @param string $template_path Path to templates
 * @param string $default_path Default path to template files
 */
function get_template_html($template_name, $args = [], $template_path = '', $default_path = '')
{
    ob_start();
    get_template($template_name, $args, $template_path, $default_path);
    return ob_get_clean();
}

/**
 * Like get_template_part but returns HTML instead of outputting
 *
 * @param string $slug The slug name for the generic template
 * @param string $name The name of the specialized template
 * @param array  $args Additional arguments passed to the template
 * @return string
 */
function get_template_part_html($slug, $name = '', $args = [])
{
    ob_start();
    get_template_part($slug, $name, $args);
    return ob_get_clean();
}
