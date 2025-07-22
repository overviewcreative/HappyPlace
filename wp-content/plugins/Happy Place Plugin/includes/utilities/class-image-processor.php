<?php

/**
 * Image Processor
 *
 * @package HappyPlace
 * @subpackage Utilities
 */

namespace HappyPlace\Utilities;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Image Processor Class
 */
class Image_Processor
{
    /**
     * Image quality setting
     *
     * @var int
     */
    private $quality = 85;

    /**
     * Maximum image dimensions
     *
     * @var array
     */
    private $max_dimensions = array(
        'width'  => 2048,
        'height' => 2048,
    );

    /**
     * Enable WebP conversion
     *
     * @var bool
     */
    private $enable_webp = true;

    /**
     * WebP quality setting
     *
     * @var int
     */
    private $webp_quality = 80;

    /**
     * Initialize the image processor
     */
    public function __construct()
    {
        add_filter('wp_handle_upload', array($this, 'process_uploaded_image'));
        add_filter('wp_generate_attachment_metadata', array($this, 'generate_webp_versions'), 10, 2);
        add_action('init', array($this, 'register_image_sizes'));
        add_filter('wp_get_attachment_image_src', array($this, 'maybe_use_webp'), 10, 4);
        
        // Add WebP mime type support
        add_filter('upload_mimes', array($this, 'add_webp_support'));
        add_filter('wp_check_filetype_and_ext', array($this, 'webp_file_type_check'), 10, 5);
    }

    /**
     * Process uploaded image
     *
     * @param array $file Array of upload data.
     * @return array
     */
    public function process_uploaded_image($file)
    {
        // Only process image files
        if (strpos($file['type'], 'image') === false) {
            return $file;
        }

        $image_path = $file['file'];

        // Optimize the image
        $this->optimize_image($image_path);

        return $file;
    }

    /**
     * Optimize image
     *
     * @param string $image_path Path to image file.
     * @return bool
     */
    public function optimize_image($image_path)
    {
        if (! file_exists($image_path)) {
            return false;
        }

        // Get image info
        $image_size = getimagesize($image_path);
        if (! $image_size) {
            return false;
        }

        // Load image based on type
        $source = $this->load_image($image_path, $image_size['mime']);
        if (! $source) {
            return false;
        }

        // Get dimensions
        $width = imagesx($source);
        $height = imagesy($source);

        // Calculate new dimensions if needed
        list($new_width, $new_height) = $this->calculate_dimensions($width, $height);

        // Create new image if resizing is needed
        if ($new_width !== $width || $new_height !== $height) {
            $new_image = imagecreatetruecolor($new_width, $new_height);

            // Preserve transparency for PNG images
            if ($image_size['mime'] === 'image/png') {
                imagealphablending($new_image, false);
                imagesavealpha($new_image, true);
                $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
                imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
            }

            // Resize
            imagecopyresampled(
                $new_image,     // Destination image
                $source,        // Source image
                0,
                0,          // Destination x, y
                0,
                0,          // Source x, y
                $new_width,    // Destination width
                $new_height,   // Destination height
                $width,        // Source width
                $height        // Source height
            );
        } else {
            $new_image = $source;
        }

        // Save optimized image
        $success = $this->save_image($new_image, $image_path, $image_size['mime']);

        // Clean up
        imagedestroy($source);
        if ($new_image !== $source) {
            imagedestroy($new_image);
        }

        return $success;
    }

    /**
     * Load image from path
     *
     * @param string $path Image path.
     * @param string $mime_type Image mime type.
     * @return resource|false
     */
    private function load_image($path, $mime_type)
    {
        switch ($mime_type) {
            case 'image/jpeg':
                return imagecreatefromjpeg($path);

            case 'image/png':
                return imagecreatefrompng($path);

            case 'image/gif':
                return imagecreatefromgif($path);

            default:
                return false;
        }
    }

    /**
     * Save image
     *
     * @param resource $image Image resource.
     * @param string   $path Path to save image.
     * @param string   $mime_type Image mime type.
     * @return bool
     */
    private function save_image($image, $path, $mime_type)
    {
        switch ($mime_type) {
            case 'image/jpeg':
                return imagejpeg($image, $path, $this->quality);

            case 'image/png':
                // PNG quality is 0-9, convert from 0-100
                $png_quality = floor((100 - $this->quality) * 9 / 100);
                return imagepng($image, $path, $png_quality);

            case 'image/gif':
                return imagegif($image, $path);

            default:
                return false;
        }
    }

    /**
     * Calculate new dimensions
     *
     * @param int $width Original width.
     * @param int $height Original height.
     * @return array
     */
    private function calculate_dimensions($width, $height)
    {
        $max_width = $this->max_dimensions['width'];
        $max_height = $this->max_dimensions['height'];

        // If image is smaller than maximum dimensions, keep original size
        if ($width <= $max_width && $height <= $max_height) {
            return array($width, $height);
        }

        // Calculate aspect ratio
        $ratio = min($max_width / $width, $max_height / $height);

        return array(
            round($width * $ratio),
            round($height * $ratio),
        );
    }

    /**
     * Generate image sizes
     *
     * @param int $attachment_id Attachment ID.
     * @return bool
     */
    public function generate_image_sizes($attachment_id)
    {
        if (! wp_attachment_is_image($attachment_id)) {
            return false;
        }

        $file = get_attached_file($attachment_id);

        // Generate thumbnail size
        $this->generate_size($file, 'thumbnail', 150, 150, true);

        // Generate medium size
        $this->generate_size($file, 'medium', 300, 300, false);

        // Generate large size
        $this->generate_size($file, 'large', 1024, 1024, false);

        // Generate custom sizes
        $this->generate_size($file, 'listing-thumbnail', 400, 300, true);
        $this->generate_size($file, 'listing-gallery', 800, 600, false);
        $this->generate_size($file, 'agent-profile', 300, 300, true);

        return true;
    }

    /**
     * Generate specific image size
     *
     * @param string $file File path.
     * @param string $size_name Size name.
     * @param int    $width Width.
     * @param int    $height Height.
     * @param bool   $crop Whether to crop or not.
     * @return bool
     */
    private function generate_size($file, $size_name, $width, $height, $crop = false)
    {
        $editor = wp_get_image_editor($file);

        if (is_wp_error($editor)) {
            return false;
        }

        $editor->set_quality($this->quality);

        $resized = $editor->resize($width, $height, $crop);

        if (is_wp_error($resized)) {
            return false;
        }

        $saved = $editor->save($editor->generate_filename($size_name));

        return ! is_wp_error($saved);
    }

    /**
     * Set image quality
     *
     * @param int $quality Quality value (0-100).
     */
    public function set_quality($quality)
    {
        $this->quality = max(0, min(100, $quality));
    }

    /**
     * Set maximum dimensions
     *
     * @param int $width Maximum width.
     * @param int $height Maximum height.
     */
    public function set_max_dimensions($width, $height)
    {
        $this->max_dimensions = array(
            'width'  => max(0, $width),
            'height' => max(0, $height),
        );
    }

    /**
     * Register additional image sizes for real estate
     */
    public function register_image_sizes()
    {
        // Real estate specific image sizes
        add_image_size('listing-thumbnail', 400, 300, true);
        add_image_size('listing-medium', 600, 450, true);
        add_image_size('listing-large', 1200, 800, true);
        add_image_size('listing-hero', 1920, 1080, true);
        add_image_size('listing-gallery', 800, 600, false);
        
        // Agent profile sizes
        add_image_size('agent-thumbnail', 200, 200, true);
        add_image_size('agent-medium', 400, 400, true);
        add_image_size('agent-large', 600, 600, true);
        
        // Square formats for social media
        add_image_size('square-small', 300, 300, true);
        add_image_size('square-medium', 600, 600, true);
        add_image_size('square-large', 1200, 1200, true);
        
        // Flyer and marketing sizes
        add_image_size('flyer-portrait', 612, 792, true); // 8.5x11 aspect ratio
        add_image_size('flyer-landscape', 792, 612, true);
        add_image_size('social-facebook', 1200, 630, true);
        add_image_size('social-instagram', 1080, 1080, true);
    }

    /**
     * Add WebP support to WordPress
     */
    public function add_webp_support($mimes)
    {
        $mimes['webp'] = 'image/webp';
        return $mimes;
    }

    /**
     * Check WebP file type
     */
    public function webp_file_type_check($data, $file, $filename, $mimes, $real_mime)
    {
        if (!empty($data['ext']) && !empty($data['type'])) {
            return $data;
        }

        $wp_file_type = wp_check_filetype($filename, $mimes);

        if ($wp_file_type['ext'] === 'webp') {
            $data['ext'] = 'webp';
            $data['type'] = 'image/webp';
        }

        return $data;
    }

    /**
     * Generate WebP versions of uploaded images
     */
    public function generate_webp_versions($metadata, $attachment_id)
    {
        if (!$this->enable_webp || !function_exists('imagewebp')) {
            return $metadata;
        }

        $file = get_attached_file($attachment_id);
        if (!$file || !wp_attachment_is_image($attachment_id)) {
            return $metadata;
        }

        // Generate WebP for main image
        $this->create_webp_version($file);

        // Generate WebP for all image sizes
        if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
            $upload_dir = wp_upload_dir();
            $base_dir = dirname($file);

            foreach ($metadata['sizes'] as $size_name => $size_data) {
                $size_file = $base_dir . '/' . $size_data['file'];
                if (file_exists($size_file)) {
                    $this->create_webp_version($size_file);
                }
            }
        }

        return $metadata;
    }

    /**
     * Create WebP version of an image
     */
    private function create_webp_version($image_path)
    {
        if (!function_exists('imagewebp')) {
            return false;
        }

        $image_info = getimagesize($image_path);
        if (!$image_info) {
            return false;
        }

        $source = $this->load_image($image_path, $image_info['mime']);
        if (!$source) {
            return false;
        }

        $webp_path = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $image_path);
        
        $success = imagewebp($source, $webp_path, $this->webp_quality);
        imagedestroy($source);

        return $success;
    }

    /**
     * Maybe use WebP version if available and supported
     */
    public function maybe_use_webp($image, $attachment_id, $size, $icon)
    {
        if (!$this->enable_webp || !$this->browser_supports_webp()) {
            return $image;
        }

        if (!$image || !is_array($image)) {
            return $image;
        }

        $webp_url = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $image[0]);
        $webp_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $webp_url);

        if (file_exists($webp_path)) {
            $image[0] = $webp_url;
        }

        return $image;
    }

    /**
     * Check if browser supports WebP
     */
    private function browser_supports_webp()
    {
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Optimize image for specific use case
     */
    public function optimize_for_use_case($image_path, $use_case = 'listing')
    {
        $settings = $this->get_optimization_settings($use_case);
        
        // Save original settings
        $original_quality = $this->quality;
        $original_dimensions = $this->max_dimensions;
        
        // Apply use case specific settings
        $this->quality = $settings['quality'];
        $this->max_dimensions = $settings['dimensions'];
        
        // Optimize
        $result = $this->optimize_image($image_path);
        
        // Restore original settings
        $this->quality = $original_quality;
        $this->max_dimensions = $original_dimensions;
        
        return $result;
    }

    /**
     * Get optimization settings for different use cases
     */
    private function get_optimization_settings($use_case)
    {
        $settings = array(
            'listing' => array(
                'quality' => 85,
                'dimensions' => array('width' => 2048, 'height' => 2048)
            ),
            'gallery' => array(
                'quality' => 80,
                'dimensions' => array('width' => 1600, 'height' => 1600)
            ),
            'thumbnail' => array(
                'quality' => 90,
                'dimensions' => array('width' => 800, 'height' => 800)
            ),
            'agent_profile' => array(
                'quality' => 90,
                'dimensions' => array('width' => 1000, 'height' => 1000)
            ),
            'social_media' => array(
                'quality' => 85,
                'dimensions' => array('width' => 1200, 'height' => 1200)
            )
        );

        return isset($settings[$use_case]) ? $settings[$use_case] : $settings['listing'];
    }

    /**
     * Bulk optimize existing images
     */
    public function bulk_optimize_images($limit = 10)
    {
        $args = array(
            'post_type' => 'attachment',
            'post_mime_type' => array('image/jpeg', 'image/png', 'image/gif'),
            'post_status' => 'inherit',
            'posts_per_page' => $limit,
            'meta_query' => array(
                array(
                    'key' => '_hph_optimized',
                    'compare' => 'NOT EXISTS'
                )
            )
        );

        $images = get_posts($args);
        $optimized = 0;

        foreach ($images as $image) {
            $file = get_attached_file($image->ID);
            if ($file && file_exists($file)) {
                if ($this->optimize_image($file)) {
                    update_post_meta($image->ID, '_hph_optimized', time());
                    $optimized++;
                }
            }
        }

        return $optimized;
    }

    /**
     * Get image optimization statistics
     */
    public function get_optimization_stats()
    {
        global $wpdb;

        $total_images = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
             WHERE post_type = 'attachment' 
             AND post_mime_type LIKE 'image/%'"
        );

        $optimized_images = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'attachment' 
             AND p.post_mime_type LIKE 'image/%'
             AND pm.meta_key = '_hph_optimized'"
        );

        return array(
            'total' => (int) $total_images,
            'optimized' => (int) $optimized_images,
            'pending' => (int) $total_images - (int) $optimized_images,
            'percentage' => $total_images > 0 ? round(($optimized_images / $total_images) * 100, 1) : 0
        );
    }

    /**
     * Clear optimization metadata (for re-optimization)
     */
    public function clear_optimization_meta()
    {
        global $wpdb;
        
        return $wpdb->delete(
            $wpdb->postmeta,
            array('meta_key' => '_hph_optimized')
        );
    }
}
