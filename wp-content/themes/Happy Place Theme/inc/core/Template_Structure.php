<?php
/**
 * Template Structure Class
 * 
 * Defines the structure and locations of template parts
 */

namespace HappyPlace\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Template_Structure {
    /**
     * Base paths for different template types
     */
    const PATHS = [
        'global'    => 'template-parts/global/',
        'listing'   => 'template-parts/listing/',
        'agent'     => 'template-parts/agent/',
        'dashboard' => 'template-parts/dashboard/',
        'cards'     => 'template-parts/cards/',
        'forms'     => 'template-parts/forms/',
    ];

    /**
     * Get the path for a template part
     */
    public static function get_template_path($type, $name = '') {
        if (!isset(self::PATHS[$type])) {
            return false;
        }

        $base_path = get_template_directory() . '/' . self::PATHS[$type];
        
        if (empty($name)) {
            return $base_path;
        }

        return $base_path . $name . '.php';
    }

    /**
     * Get the URI for a template part
     */
    public static function get_template_uri($type, $name = '') {
        if (!isset(self::PATHS[$type])) {
            return false;
        }

        $base_uri = get_template_directory_uri() . '/' . self::PATHS[$type];
        
        if (empty($name)) {
            return $base_uri;
        }

        return $base_uri . $name . '.php';
    }

    /**
     * Check if a template part exists
     */
    public static function template_exists($type, $name) {
        $path = self::get_template_path($type, $name);
        return $path && file_exists($path);
    }

    /**
     * Get all template parts of a specific type
     */
    public static function get_all_templates($type) {
        if (!isset(self::PATHS[$type])) {
            return [];
        }

        $path = self::get_template_path($type);
        if (!$path || !is_dir($path)) {
            return [];
        }

        $templates = [];
        $files = glob($path . '*.php');
        
        foreach ($files as $file) {
            $templates[] = basename($file, '.php');
        }

        return $templates;
    }
}
