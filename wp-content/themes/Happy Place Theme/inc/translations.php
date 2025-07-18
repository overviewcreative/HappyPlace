<?php

/**
 * Theme Setup
 *
 * Handles theme initialization and setup
 *
 * @package HappyPlace
 * @subpackage Core
 */

namespace HappyPlace\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize theme translations
 */
function init_theme_translations()
{
    load_theme_textdomain('happy-place', get_template_directory() . '/languages');
}
add_action('init', 'HappyPlace\\Core\\init_theme_translations');

/**
 * Initialize plugin translations if plugin is active
 */
function init_plugin_translations()
{
    if (defined('HPH_PLUGIN_PATH')) {
        load_plugin_textdomain('happy-place', false, plugin_basename(HPH_PLUGIN_PATH) . '/languages');
    }
}
add_action('init', 'HappyPlace\\Core\\init_plugin_translations');
