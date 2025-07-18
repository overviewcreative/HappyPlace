<?php
namespace HappyPlace\Theme\Media;

/**
 * Media Handler Class
 * Handles image uploads, processing, and media management
 */
class Media_Handler {
    private static array $allowed_image_types = ['image/jpeg', 'image/png', 'image/webp'];
    private static array $image_sizes = [
        'listing_thumbnail' => [300, 200, true],
        'listing_gallery' => [800, 600, true],
        'listing_full' => [1200, 800, true],
        'agent_thumbnail' => [200, 200, true],
        'agent_profile' => [400, 400, true]
    ];

    /**
     * Initialize media handling
     */
    public static function init(): void {
        add_action('after_setup_theme', [self::class, 'register_image_sizes']);
        add_filter('upload_mimes', [self::class, 'filter_mime_types']);
        add_filter('wp_handle_upload_prefilter', [self::class, 'validate_image_upload']);
        add_filter('wp_generate_attachment_metadata', [self::class, 'process_image_metadata'], 10, 2);
        add_action('delete_attachment', [self::class, 'cleanup_image_files']);
    }

    /**
     * Register custom image sizes
     */
    public static function register_image_sizes(): void {
        foreach (self::$image_sizes as $name => [$width, $height, $crop]) {
            add_image_size($name, $width, $height, $crop);
        }
    }

    /**
     * Filter allowed mime types
     */
    public static function filter_mime_types(array $mimes): array {
        // Only allow specific image types
        foreach ($mimes as $ext => $mime) {
            if (strpos($mime, 'image/') === 0 && !in_array($mime, self::$allowed_image_types)) {
                unset($mimes[$ext]);
            }
        }
        return $mimes;
    }

    /**
     * Validate image upload
     */
    public static function validate_image_upload(array $file): array {
        if (strpos($file['type'], 'image/') === 0) {
            $image = getimagesize($file['tmp_name']);
            
            // Check minimum dimensions
            if ($image[0] < 800 || $image[1] < 600) {
                $file['error'] = sprintf(
                    __('Image dimensions must be at least %dx%d pixels.', 'happy-place-theme'),
                    800, 600
                );
                return $file;
            }

            // Check file size (max 5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                $file['error'] = __('Image file size must be less than 5MB.', 'happy-place-theme');
                return $file;
            }
        }
        
        return $file;
    }

    /**
     * Process image metadata after upload
     */
    public static function process_image_metadata(array $metadata, int $attachment_id): array {
        if (strpos(get_post_mime_type($attachment_id), 'image/') === 0) {
            // Add additional metadata
            $metadata['custom'] = [
                'processed' => current_time('mysql'),
                'optimized' => false
            ];

            // Queue image for optimization
            wp_schedule_single_event(
                time() + 60,
                'happy_place_optimize_image',
                [$attachment_id]
            );
        }
        
        return $metadata;
    }

    /**
     * Clean up image files when attachment is deleted
     */
    public static function cleanup_image_files(int $attachment_id): void {
        $metadata = wp_get_attachment_metadata($attachment_id);
        
        if (!empty($metadata['file'])) {
            $upload_dir = wp_upload_dir();
            $file_path = $upload_dir['basedir'] . '/' . $metadata['file'];
            
            // Delete original file
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            // Delete all generated sizes
            if (!empty($metadata['sizes'])) {
                foreach ($metadata['sizes'] as $size) {
                    $size_file = $upload_dir['basedir'] . '/' . dirname($metadata['file']) . '/' . $size['file'];
                    if (file_exists($size_file)) {
                        unlink($size_file);
                    }
                }
            }
        }
    }

    /**
     * Get image URL for specific size
     */
    public static function get_image_url(int $attachment_id, string $size = 'full'): ?string {
        $image = wp_get_attachment_image_src($attachment_id, $size);
        return $image ? $image[0] : null;
    }

    /**
     * Get image HTML with srcset and sizes
     */
    public static function get_responsive_image(int $attachment_id, string $size = 'full', array $attr = []): string {
        return wp_get_attachment_image($attachment_id, $size, false, $attr);
    }

    /**
     * Get image gallery for a post
     */
    public static function get_gallery_images(int $post_id, string $size = 'full'): array {
        $gallery_images = [];
        $attachments = get_posts([
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            'post_parent' => $post_id,
            'post_mime_type' => self::$allowed_image_types,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ]);

        foreach ($attachments as $attachment) {
            $gallery_images[] = [
                'id' => $attachment->ID,
                'title' => $attachment->post_title,
                'caption' => $attachment->post_excerpt,
                'description' => $attachment->post_content,
                'url' => self::get_image_url($attachment->ID, $size),
                'thumbnail' => self::get_image_url($attachment->ID, 'thumbnail'),
                'full' => self::get_image_url($attachment->ID, 'full'),
                'meta' => wp_get_attachment_metadata($attachment->ID)
            ];
        }

        return $gallery_images;
    }

    /**
     * Check if image is optimized
     */
    public static function is_image_optimized(int $attachment_id): bool {
        $metadata = wp_get_attachment_metadata($attachment_id);
        return !empty($metadata['custom']['optimized']);
    }

    /**
     * Set image as optimized
     */
    public static function set_image_optimized(int $attachment_id): void {
        $metadata = wp_get_attachment_metadata($attachment_id);
        if ($metadata) {
            $metadata['custom']['optimized'] = true;
            $metadata['custom']['optimized_date'] = current_time('mysql');
            wp_update_attachment_metadata($attachment_id, $metadata);
        }
    }
}
