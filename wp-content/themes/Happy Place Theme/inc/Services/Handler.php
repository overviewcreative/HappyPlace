<?php
/**
 * Media Handler Class
 *
 * Handles image uploads, processing, and media management
 *
 * @package HappyPlace
 * @subpackage Media
 */

namespace HappyPlace\Media;

if (!defined('ABSPATH')) {
    exit;
}

class Handler {
    /**
     * Instance of this class
     *
     * @var Handler
     */
    private static $instance = null;

    /**
     * Allowed image types
     *
     * @var array
     */
    private $allowed_image_types = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * Image sizes
     *
     * @var array
     */
    private $image_sizes = [
        'listing_thumbnail' => [300, 200, true],
        'listing_gallery' => [800, 600, true],
        'listing_full' => [1200, 800, true],
        'agent_thumbnail' => [200, 200, true],
        'agent_profile' => [400, 400, true]
    ];

    /**
     * Get instance of this class
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
        add_action('after_setup_theme', [$this, 'add_image_sizes']);
        add_filter('upload_mimes', [$this, 'allowed_mime_types']);
        add_filter('wp_handle_upload_prefilter', [$this, 'validate_image_upload']);
    }

    /**
     * Add custom image sizes
     */
    public function add_image_sizes() {
        foreach ($this->image_sizes as $name => $size) {
            add_image_size($name, $size[0], $size[1], $size[2]);
        }
    }

    /**
     * Filter allowed mime types
     */
    public function allowed_mime_types($mimes) {
        return array_intersect_key($mimes, array_flip([
            'jpg|jpeg|jpe',
            'png',
            'webp'
        ]));
    }

    /**
     * Validate image upload
     */
    public function validate_image_upload($file) {
        if (!in_array($file['type'], $this->allowed_image_types)) {
            $file['error'] = __('Only JPEG, PNG and WebP images are allowed.', 'happy-place');
        }
        return $file;
    }

    /**
     * Get image size dimensions
     */
    public function get_image_size($size_name) {
        return $this->image_sizes[$size_name] ?? null;
    }
}
