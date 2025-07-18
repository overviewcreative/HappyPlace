<?php

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
    if (!$template_path) {
        $template_path = 'templates/';
    }

    if (!$default_path) {
        $default_path = HPH_THEME_DIR . '/templates/';
    }

    // Look within passed path within the theme
    $template = locate_template(
        array(
            trailingslashit($template_path) . $template_name,
            $template_name
        )
    );

    // Get default template
    if (!$template && $default_path) {
        $template = $default_path . $template_name;
    }

    // Allow 3rd party plugin filter template file from their plugin.
    $template = apply_filters('hph_get_template', $template, $template_name, $args, $template_path, $default_path);

    if ($template) {
        $template = wp_normalize_path($template);
        
        if ($args && is_array($args)) {
            extract($args);
        }

        include($template);
    }
}
