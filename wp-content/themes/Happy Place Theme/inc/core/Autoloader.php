<?php
/**
 * Simple Autoloader for HappyPlace namespace
 */

namespace HappyPlace\Core;

class Autoloader {
    
    public static function register() {
        spl_autoload_register([__CLASS__, 'autoload']);
    }
    
    public static function autoload($class) {
        // Check if this is a HappyPlace class
        if (strpos($class, 'HappyPlace\\') !== 0) {
            return;
        }
        
        // Remove namespace prefix
        $class = substr($class, 11);
        
        // Convert namespace separators to directory separators
        $class = str_replace('\\', '/', $class);
        
        // Build file path
        $file = get_template_directory() . '/inc/' . $class . '.php';
        
        // Include file if it exists
        if (file_exists($file)) {
            require_once $file;
        }
    }
}

// Register autoloader
Autoloader::register();
