<?php

namespace HappyPlace\Graphics;

use Exception;

/**
 * Simple Flyer Generator Class - Clean Rebuild
 * Handles real estate flyer generation using Fabric.js
 */
class Flyer_Generator_Clean {
    private static ?self $instance = null;

    public static function get_instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Register hooks
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_generate_flyer', [$this, 'ajax_generate_flyer']);
        add_action('wp_ajax_nopriv_generate_flyer', [$this, 'ajax_generate_flyer']);
        add_shortcode('listing_flyer_generator', [$this, 'render_flyer_generator']);
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Flyer Generator Clean: Class initialized');
        }
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts(): void {
        // Always enqueue for testing
        $assets_url = defined('HPH_ASSETS_URL') ? HPH_ASSETS_URL : plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/';
        $version = defined('HPH_VERSION') ? HPH_VERSION : time(); // Use timestamp for cache busting
        
        // CDN libraries
        wp_enqueue_script('fabric-js', 'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js', [], '5.3.0', true);
        wp_enqueue_script('qrcode-js', 'https://cdnjs.cloudflare.com/ajax/libs/qrcode/1.5.3/qrcode.min.js', [], '1.5.3', true);
        wp_enqueue_script('jspdf', 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js', [], '2.5.1', true);
        
        // Main script
        wp_enqueue_script('flyer-generator', $assets_url . 'js/flyer-generator.js', ['fabric-js', 'qrcode-js', 'jspdf', 'jquery'], $version, true);
        
        // Localize script
        wp_localize_script('flyer-generator', 'flyerGenerator', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flyer_generator_nonce'),
            'pluginUrl' => plugin_dir_url(dirname(dirname(__FILE__))),
            'assetsUrl' => $assets_url,
            'isDebug' => defined('WP_DEBUG') && WP_DEBUG,
            'strings' => [
                'selectListing' => 'Please select a listing.',
                'generating' => 'Generating...',
                'generateFlyer' => 'Generate Flyer',
                'configError' => 'Configuration error. Please refresh and try again.',
                'downloadError' => 'Error downloading flyer. Please try again.',
                'loadingData' => 'Loading listing data...',
            ]
        ]);
        
        // CSS
        wp_enqueue_style('flyer-generator-styles', $assets_url . 'css/flyer-generator.css', [], $version);
        wp_enqueue_style('fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', [], '6.4.0');
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Flyer Generator Clean: Scripts enqueued');
        }
    }

    /**
     * Simple AJAX handler for testing
     */
    public function ajax_generate_flyer(): void {
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Flyer Generator Clean: AJAX handler called');
            error_log('POST data: ' . print_r($_POST, true));
        }

        try {
            // Very basic validation first
            if (!isset($_POST['action']) || $_POST['action'] !== 'generate_flyer') {
                wp_send_json_error(['message' => 'Invalid action'], 400);
                return;
            }

            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'flyer_generator_nonce')) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Flyer Generator Clean: Nonce verification failed');
                    error_log('Received nonce: ' . ($_POST['nonce'] ?? 'NONE'));
                    error_log('Expected nonce for: flyer_generator_nonce');
                }
                wp_send_json_error(['message' => 'Security verification failed'], 403);
                return;
            }

            // Get and validate listing ID
            $listing_id = intval($_POST['listing_id'] ?? 0);
            if (!$listing_id) {
                wp_send_json_error(['message' => 'No listing ID provided'], 400);
                return;
            }

            // Check if listing exists
            $listing = get_post($listing_id);
            if (!$listing) {
                wp_send_json_error(['message' => 'Listing not found'], 404);
                return;
            }

            if ($listing->post_type !== 'listing') {
                wp_send_json_error(['message' => 'Post is not a listing'], 400);
                return;
            }

            if ($listing->post_status !== 'publish') {
                wp_send_json_error(['message' => 'Listing is not published'], 400);
                return;
            }

            // Get basic listing data
            $data = $this->get_basic_listing_data($listing_id);

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Flyer Generator Clean: Success - returning data for listing ' . $listing_id);
            }

            wp_send_json_success($data);

        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Flyer Generator Clean: Exception - ' . $e->getMessage());
            }
            wp_send_json_error(['message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get basic listing data without complex bridge functions
     */
    private function get_basic_listing_data(int $listing_id): array {
        $listing = get_post($listing_id);
        
        // Get basic ACF fields
        $price = get_field('price', $listing_id) ?: get_field('listing_price', $listing_id);
        $bedrooms = get_field('bedrooms', $listing_id) ?: get_field('beds', $listing_id);
        $bathrooms = get_field('bathrooms', $listing_id) ?: get_field('baths', $listing_id);
        $sqft = get_field('square_footage', $listing_id) ?: get_field('sqft', $listing_id);
        $address = get_field('address', $listing_id) ?: get_field('street_address', $listing_id);
        $city = get_field('city', $listing_id);
        $description = get_field('description', $listing_id) ?: get_field('property_description', $listing_id);

        return [
            'id' => $listing_id,
            'title' => get_the_title($listing_id),
            'price' => $price ? '$' . number_format($price) : null,
            'bedrooms' => $bedrooms,
            'bathrooms' => $bathrooms,
            'square_footage' => $sqft,
            'address' => $address,
            'city' => $city,
            'description' => $description,
            'url' => get_permalink($listing_id),
            'listing' => [
                'id' => $listing_id,
                'title' => get_the_title($listing_id),
                'price' => $price ? '$' . number_format($price) : null,
                'bedrooms' => $bedrooms,
                'bathrooms' => $bathrooms,
                'square_footage' => $sqft,
                'address' => $address,
                'city' => $city,
                'description' => $description,
            ],
            'agent' => [
                'name' => 'The Parker Group',
                'phone' => '302.217.6692',
                'email' => 'info@theparkergroup.com',
                'image' => null
            ]
        ];
    }

    /**
     * Render shortcode
     */
    public function render_flyer_generator($atts): string {
        $this->enqueue_scripts();
        
        $atts = shortcode_atts([
            'listing_id' => 0,
            'class' => '',
            'style' => ''
        ], $atts);

        ob_start();
        
        // Use simple template path
        $template_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/flyer-generator-simple.php';
        if (file_exists($template_path)) {
            $template_atts = $atts;
            $template_listing_id = intval($atts['listing_id']);
            $template_allowed_types = ['listing'];
            include $template_path;
        } else {
            echo '<div class="flyer-generator-error">Template not found</div>';
        }
        
        return ob_get_clean();
    }
}

// Initialize if this file is loaded directly by Plugin Manager
if (!class_exists('HappyPlace\\Graphics\\Flyer_Generator')) {
    class_alias('HappyPlace\\Graphics\\Flyer_Generator_Clean', 'HappyPlace\\Graphics\\Flyer_Generator');
}
