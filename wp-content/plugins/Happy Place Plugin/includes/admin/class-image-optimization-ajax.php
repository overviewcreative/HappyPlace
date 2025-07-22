<?php

/**
 * Image Optimization AJAX Handler
 * 
 * @package Happy_Place_Plugin
 */

namespace HPH\Admin;

use HappyPlace\Utilities\Image_Processor;

if (!defined('ABSPATH')) {
    exit;
}

class Image_Optimization_Ajax
{
    private static ?self $instance = null;
    private $image_processor;

    public static function get_instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        add_action('wp_ajax_hph_get_image_stats', [$this, 'get_image_stats']);
        add_action('wp_ajax_hph_optimize_images', [$this, 'optimize_images']);
        add_action('wp_ajax_hph_clear_optimization_meta', [$this, 'clear_optimization_meta']);
        
        // Initialize image processor if available
        if (class_exists('HappyPlace\\Utilities\\Image_Processor')) {
            $this->image_processor = new Image_Processor();
        }
    }

    /**
     * Get image optimization statistics
     */
    public function get_image_stats()
    {
        check_ajax_referer('happy_place_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        if (!$this->image_processor) {
            wp_send_json_error('Image processor not available');
            return;
        }

        try {
            $stats = $this->image_processor->get_optimization_stats();
            
            wp_send_json_success([
                'stats' => $stats,
                'message' => sprintf(
                    'Found %d images, %d optimized (%.1f%%)',
                    $stats['total'],
                    $stats['optimized'],
                    $stats['percentage']
                )
            ]);
        } catch (\Exception $e) {
            wp_send_json_error('Error getting image stats: ' . $e->getMessage());
        }
    }

    /**
     * Optimize images in batches
     */
    public function optimize_images()
    {
        check_ajax_referer('happy_place_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        if (!$this->image_processor) {
            wp_send_json_error('Image processor not available');
            return;
        }

        $batch_size = intval($_POST['batch_size'] ?? 5);
        $batch_size = max(1, min(20, $batch_size)); // Limit between 1-20

        try {
            $optimized_count = $this->image_processor->bulk_optimize_images($batch_size);
            $stats = $this->image_processor->get_optimization_stats();
            
            wp_send_json_success([
                'optimized_count' => $optimized_count,
                'stats' => $stats,
                'message' => sprintf(
                    'Optimized %d images in this batch. Total progress: %d/%d (%.1f%%)',
                    $optimized_count,
                    $stats['optimized'],
                    $stats['total'],
                    $stats['percentage']
                ),
                'completed' => $stats['pending'] === 0
            ]);
        } catch (\Exception $e) {
            wp_send_json_error('Error optimizing images: ' . $e->getMessage());
        }
    }

    /**
     * Clear optimization metadata
     */
    public function clear_optimization_meta()
    {
        check_ajax_referer('happy_place_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        if (!$this->image_processor) {
            wp_send_json_error('Image processor not available');
            return;
        }

        try {
            $cleared_count = $this->image_processor->clear_optimization_meta();
            $stats = $this->image_processor->get_optimization_stats();
            
            wp_send_json_success([
                'cleared_count' => $cleared_count,
                'stats' => $stats,
                'message' => sprintf(
                    'Cleared optimization metadata for %d images. Ready to re-optimize.',
                    $cleared_count
                )
            ]);
        } catch (\Exception $e) {
            wp_send_json_error('Error clearing optimization metadata: ' . $e->getMessage());
        }
    }

    /**
     * Get image details for specific attachment
     */
    public function get_image_details()
    {
        check_ajax_referer('happy_place_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        $attachment_id = intval($_POST['attachment_id'] ?? 0);
        if (!$attachment_id) {
            wp_send_json_error('Invalid attachment ID');
            return;
        }

        $file = get_attached_file($attachment_id);
        if (!$file || !file_exists($file)) {
            wp_send_json_error('File not found');
            return;
        }

        $file_size = filesize($file);
        $image_meta = wp_get_attachment_metadata($attachment_id);
        $optimized = get_post_meta($attachment_id, '_hph_optimized', true);
        
        // Check for WebP version
        $webp_file = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $file);
        $has_webp = file_exists($webp_file);
        $webp_size = $has_webp ? filesize($webp_file) : 0;

        wp_send_json_success([
            'attachment_id' => $attachment_id,
            'file_path' => $file,
            'file_size' => $file_size,
            'file_size_formatted' => size_format($file_size),
            'dimensions' => [
                'width' => $image_meta['width'] ?? 0,
                'height' => $image_meta['height'] ?? 0
            ],
            'optimized' => !empty($optimized),
            'optimized_date' => $optimized ? date('Y-m-d H:i:s', $optimized) : null,
            'has_webp' => $has_webp,
            'webp_size' => $webp_size,
            'webp_size_formatted' => $has_webp ? size_format($webp_size) : 'N/A',
            'savings' => $has_webp && $webp_size < $file_size ? 
                round((($file_size - $webp_size) / $file_size) * 100, 1) : 0
        ]);
    }
}

// Initialize the AJAX handler
add_action('init', function() {
    Image_Optimization_Ajax::get_instance();
});
