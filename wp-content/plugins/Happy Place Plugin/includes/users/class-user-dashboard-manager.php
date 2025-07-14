<?php

/**
 * User Dashboard Manager Class
 * Handles user dashboard functionality
 */

namespace HappyPlace\Users;

if (!defined('ABSPATH')) {
    exit;
}

class User_Dashboard_Manager
{
    private static ?self $instance = null;

    public static function get_instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // Initialize on init with lower priority than post types
        add_action('init', [$this, 'init'], 15);
    }

    public function init(): void
    {
        error_log('HPH: User_Dashboard_Manager initialized');
    }
}
