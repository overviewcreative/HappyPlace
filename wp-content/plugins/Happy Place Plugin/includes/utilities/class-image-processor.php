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
    private $quality = 90;

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
     * Initialize the image processor
     */
    public function __construct()
    {
        add_filter('wp_handle_upload', array($this, 'process_uploaded_image'));
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
}
