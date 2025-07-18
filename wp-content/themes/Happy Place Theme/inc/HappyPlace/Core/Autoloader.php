<?php
/**
 * Autoloader Class
 *
 * PSR-4 compliant autoloader for Happy Place theme classes
 *
 * @package HappyPlace
 * @subpackage Core
 */

namespace HappyPlace\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Autoloader {
    /**
     * Class instance
     *
     * @var Autoloader
     */
    private static $instance = null;

    /**
     * The root path for class files
     *
     * @var string
     */
    private $root_path;

    /**
     * Get class instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->root_path = get_template_directory() . '/inc/';
        spl_autoload_register([$this, 'autoload']);
    }

    /**
     * Autoload callback
     *
     * @param string $class The fully-qualified class name
     */
    public function autoload($class) {
        // Check if class uses our namespace
        if (strpos($class, 'HappyPlace\\') !== 0) {
            return;
        }

        // Build the file path from the namespace
        $file = $this->root_path . str_replace('\\', '/', $class) . '.php';

        // Include file if it exists
        if (file_exists($file)) {
            require_once $file;
        }
    }
}
