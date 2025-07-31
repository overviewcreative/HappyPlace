<?php

namespace HappyPlace\Graphics;

use Exception;
use function \wp_enqueue_script;
use function \wp_enqueue_style;
use function \wp_localize_script;
use function \get_field;
use function \get_the_title;
use function \get_permalink;
use function \plugin_dir_url;
use function \plugin_dir_path;
use const \HPH_VERSION;
use const \HPH_ASSETS_URL;

/**
 * Flyer Generator Class
 * Handles real estate flyer generation using Fabric.js
 */
class Flyer_Generator {
    private static ?self $instance = null;

    public static function get_instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
            
            // Debug logging for instance creation
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Flyer Generator: Instance created successfully');
            }
        }
        
        return self::$instance;
    }

    private function __construct() {
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Flyer Generator: Initializing class');
            error_log('Flyer Generator: HPH_ASSETS_URL = ' . (defined('HPH_ASSETS_URL') ? HPH_ASSETS_URL : 'NOT DEFINED'));
            error_log('Flyer Generator: HPH_VERSION = ' . (defined('HPH_VERSION') ? HPH_VERSION : 'NOT DEFINED'));
        }

        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        // AJAX actions handled by centralized AJAX system (class-ajax-coordinator.php -> class-flyer-ajax.php)
        add_shortcode('listing_flyer_generator', [$this, 'render_flyer_generator']);
        
        // Add admin notice for debugging (only in debug mode)
        if (defined('WP_DEBUG') && WP_DEBUG && is_admin()) {
            add_action('admin_notices', [$this, 'debug_admin_notice']);
            add_action('admin_menu', [$this, 'add_debug_menu']);
        }
        
        // Hook to verify the class is loaded
        add_action('init', function() {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Flyer Generator: WordPress init hook fired, class is active');
            }
        });
    }

    /**
     * Enqueue necessary scripts and styles
     */
    public function enqueue_scripts(): void {
        // Only enqueue on pages that need it
        if (!$this->should_enqueue_scripts()) {
            return;
        }

        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Flyer Generator: Enqueuing scripts and styles');
        }

        // Ensure we have the required constants
        $assets_url = defined('HPH_ASSETS_URL') ? HPH_ASSETS_URL : plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/';
        $version = defined('HPH_VERSION') ? HPH_VERSION : '1.0.0';
        $plugin_url = defined('HPH_PLUGIN_URL') ? HPH_PLUGIN_URL : plugin_dir_url(dirname(dirname(__FILE__)));

        // Enqueue Fabric.js from CDN with integrity check
        wp_enqueue_script(
            'fabric-js',
            'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js',
            [],
            '5.3.0',
            true
        );

        // Enqueue QR Code library from CDN
        wp_enqueue_script(
            'qrcode-js',
            'https://cdnjs.cloudflare.com/ajax/libs/qrcode/1.5.3/qrcode.min.js',
            [],
            '1.5.3',
            true
        );

        // Enqueue jsPDF library for PDF generation
        wp_enqueue_script(
            'jspdf',
            'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js',
            [],
            '2.5.1',
            true
        );

        // Enqueue custom flyer generator script
        wp_enqueue_script(
            'hph-marketing-suite',
            $assets_url . 'js/marketing-suite-generator.js',
            ['jquery', 'fabric'],
            $version,
            true
        );

        // Enhanced localized data
        wp_localize_script('hph-marketing-suite', 'flyerGenerator', [
            'ajaxUrl'     => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('hph_ajax_nonce'),
            'pluginUrl'   => $plugin_url,
            'assetsUrl'   => $assets_url,
            'templateUrl' => get_template_directory_uri(),
            'isDebug'     => defined('WP_DEBUG') && WP_DEBUG,
            'strings'     => [
                'selectListing'     => __('Please select a listing.', 'happy-place'),
                'generating'        => __('Generating...', 'happy-place'),
                'generateFlyer'     => __('Generate Flyer', 'happy-place'),
                'configError'       => __('Configuration error. Please refresh and try again.', 'happy-place'),
                'downloadError'     => __('Error downloading flyer. Please try again.', 'happy-place'),
                'loadingData'       => __('Loading listing data...', 'happy-place'),
            ]
        ]);

        // Enqueue marketing suite styles
        wp_enqueue_style(
            'hph-marketing-suite-styles',
            $assets_url . 'css/marketing-suite-generator.css',
            [],
            $version
        );

        // Enqueue Font Awesome for icons
        wp_enqueue_style(
            'fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            [],
            '6.4.0'
        );

        // Debug logging for enqueued assets
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Flyer Generator: Assets enqueued successfully');
            error_log('Flyer Generator: Assets URL = ' . $assets_url);
            error_log('Flyer Generator: Plugin URL = ' . $plugin_url);
        }
    }

    /**
     * Check if scripts should be enqueued
     */
    private function should_enqueue_scripts(): bool {
        global $post;
        
        // Always enqueue on admin pages
        if (is_admin()) {
            return true;
        }
        
        // Check if current page contains our shortcode
        if ($post && has_shortcode($post->post_content, 'listing_flyer_generator')) {
            return true;
        }
        
        // Check if it's a listing page
        if (is_singular('listing')) {
            return true;
        }
        
        // For debugging - always enqueue if WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return true;
        }
        
        // Allow themes/plugins to force enqueuing
        return apply_filters('hph_flyer_generator_should_enqueue', false);
    }


    /**
     * AJAX handler for generating flyer content
     */
    public function ajax_generate_flyer(): void {
        try {
            // Verify nonce
            if (!check_ajax_referer('hph_ajax_nonce', 'nonce', false)) {
                wp_send_json_error([
                    'message' => __('Security verification failed. Please refresh the page and try again.', 'happy-place'),
                    'code' => 'NONCE_FAILED'
                ], 403);
            }

            // Validate and sanitize input
            $listing_id = intval($_POST['listing_id'] ?? 0);
            $flyer_type = sanitize_text_field($_POST['flyer_type'] ?? 'listing');
            
            if (!$listing_id || $listing_id <= 0) {
                wp_send_json_error([
                    'message' => __('Invalid listing ID provided.', 'happy-place'),
                    'code' => 'INVALID_LISTING_ID'
                ], 400);
            }

            // Verify listing exists and is published
            $listing = get_post($listing_id);
            if (!$listing || $listing->post_type !== 'listing' || $listing->post_status !== 'publish') {
                wp_send_json_error([
                    'message' => __('Listing not found or not available.', 'happy-place'),
                    'code' => 'LISTING_NOT_FOUND'
                ], 404);
            }

            // Validate flyer type
            $allowed_types = ['listing', 'open_house', 'sold', 'coming_soon'];
            if (!in_array($flyer_type, $allowed_types, true)) {
                $flyer_type = 'listing'; // Default fallback
            }

            // Check user permissions if needed
            if (!$this->user_can_generate_flyer($listing_id)) {
                wp_send_json_error([
                    'message' => __('You do not have permission to generate flyers for this listing.', 'happy-place'),
                    'code' => 'INSUFFICIENT_PERMISSIONS'
                ], 403);
            }

            // Get listing data
            $data = $this->get_listing_data($listing_id, $flyer_type);
            
            if (empty($data)) {
                wp_send_json_error([
                    'message' => __('Unable to retrieve listing data.', 'happy-place'),
                    'code' => 'DATA_RETRIEVAL_FAILED'
                ], 500);
            }

            // Log successful generation for debugging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Flyer Generator: Successfully generated data for listing {$listing_id} (type: {$flyer_type})");
            }

            wp_send_json_success($data);

        } catch (Exception $e) {
            error_log("Flyer Generator Error: " . $e->getMessage());
            wp_send_json_error([
                'message' => __('An unexpected error occurred while generating the flyer.', 'happy-place'),
                'code' => 'INTERNAL_ERROR',
                'debug' => defined('WP_DEBUG') && WP_DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Check if current user can generate flyers for a listing
     */
    private function user_can_generate_flyer(int $listing_id): bool {
        // Always allow for logged-in users with appropriate capabilities
        if (current_user_can('edit_posts') || current_user_can('manage_options')) {
            return true;
        }

        // Allow listing authors to generate flyers for their own listings
        $listing = get_post($listing_id);
        if ($listing && get_current_user_id() === (int) $listing->post_author) {
            return true;
        }

        // Allow filtering for custom permission logic
        return apply_filters('hph_user_can_generate_flyer', false, $listing_id, get_current_user_id());
    }

    /**
     * Gather listing, agent, and community data using bridge functions
     * Public method to allow AJAX handler access
     */
    public function get_listing_data(int $listing_id, string $flyer_type = 'listing'): array {
        // Ensure bridge functions are available
        $this->ensure_bridge_functions_loaded();
        
        // Get basic listing data with safe function calls
        $listing_fields = $this->extract_listing_fields($listing_id);
        
        // Get agent data using bridge functions
        $agent_data = $this->get_agent_data($listing_id);
        
        // Get hosting agent data if this is an open house flyer
        $hosting_agent_data = null;
        if ($flyer_type === 'open_house') {
            $hosting_agent_data = $this->get_hosting_agent_data($listing_id);
        }
        
        // Get community data if available
        $community_data = $this->get_community_data($listing_id);

        return [
            'listing'       => $listing_fields,
            'agent'         => $agent_data,
            'hosting_agent' => $hosting_agent_data,
            'community'     => $community_data,
            'listing_title' => get_the_title($listing_id),
            'listing_url'   => get_permalink($listing_id),
            
            // Root level data for backward compatibility
            'id'            => $listing_id,
            'price'         => $listing_fields['price'] ?? null,
            'bedrooms'      => $listing_fields['bedrooms'] ?? null,
            'bathrooms'     => $listing_fields['bathrooms'] ?? null,
            'square_footage'=> $listing_fields['square_footage'] ?? null,
            'address'       => $listing_fields['address'] ?? null,
            'city'          => $listing_fields['city'] ?? null,
            'description'   => $listing_fields['description'] ?? null,
            'gallery'       => $listing_fields['gallery'] ?? [],
            'lot_size'      => $listing_fields['lot_size'] ?? null,
            'lot_acres'     => $listing_fields['lot_acres'] ?? null,
        ];
    }

    /**
     * Ensure bridge functions are loaded
     */
    private function ensure_bridge_functions_loaded(): void {
        if (!function_exists('hph_get_listing_price')) {
            // Include theme bridge functions if not already loaded
            $theme_dir = get_template_directory();
            $bridge_files = [
                $theme_dir . '/inc/bridge/listing-bridge.php',
                $theme_dir . '/inc/bridge/agent-bridge.php',
                $theme_dir . '/inc/bridge/template-bridge.php',
                $theme_dir . '/inc/template-bridge.php', // fallback location
            ];
            
            foreach ($bridge_files as $bridge_file) {
                if (file_exists($bridge_file)) {
                    include_once $bridge_file;
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Flyer Generator: Loaded bridge file: ' . $bridge_file);
                    }
                }
            }
        }
    }

    /**
     * Safely call a bridge function with fallback
     */
    private function safe_bridge_call(string $function_name, ...$args) {
        if (function_exists($function_name)) {
            try {
                return call_user_func_array($function_name, $args);
            } catch (Exception $e) {
                error_log("Flyer Generator: Error calling {$function_name}: " . $e->getMessage());
                return null;
            }
        }
        return null;
    }

    /**
     * Extract listing fields safely using existing bridge functions
     */
    private function extract_listing_fields(int $listing_id): array {
        $fields = [
            'id'               => $listing_id,
            'title'            => get_the_title($listing_id),
            'url'              => get_permalink($listing_id),
            'listing_url'      => get_permalink($listing_id),
            'permalink'        => get_permalink($listing_id),
        ];

        // Price data using existing bridge function
        $price = $this->safe_bridge_call('hph_get_listing_price', $listing_id, 'display');
        $price_raw = $this->safe_bridge_call('hph_get_listing_price', $listing_id, 'raw');
        $fields['price'] = $price;
        $fields['listing_price'] = $price;
        $fields['price_raw'] = $price_raw;

        // Address data using existing bridge function
        $address_array = $this->safe_bridge_call('hph_get_listing_address', $listing_id, false);
        $address_formatted = $this->safe_bridge_call('hph_get_listing_address', $listing_id, true);
        
        if (is_array($address_array)) {
            $fields['street_address'] = $address_array['street'] ?? '';
            $fields['city'] = $address_array['city'] ?? '';
            $fields['state'] = $address_array['state'] ?? '';
            $fields['region'] = $address_array['state'] ?? '';
            $fields['zip_code'] = $address_array['zip'] ?? '';
            $fields['zip'] = $address_array['zip'] ?? '';
            $fields['full_address'] = $address_array['full'] ?? '';
            $fields['address'] = $address_array['full'] ?: $fields['street_address'];
        }
        
        if ($address_formatted) {
            $fields['address'] = $address_formatted;
        }

        // Property features using existing bridge function
        $features = $this->safe_bridge_call('hph_get_listing_features', $listing_id) ?: [];
        
        $fields['bedrooms'] = $features['bedrooms'] ?? null;
        $fields['beds'] = $features['bedrooms'] ?? null;
        $fields['bathrooms'] = $features['bathrooms_total'] ?? $features['bathrooms'] ?? null;
        $fields['baths'] = $fields['bathrooms'];
        $fields['square_footage'] = $features['square_feet'] ?? null;
        $fields['sqft'] = $fields['square_footage'];
        $fields['living_area'] = $fields['square_footage'];
        $fields['lot_size'] = $features['lot_size'] ?? null;
        $fields['year_built'] = $features['year_built'] ?? null;

        // Property type from features
        $fields['property_type'] = $features['property_type'] ?? null;

        // Status using existing bridge function
        $status = $this->safe_bridge_call('hph_get_listing_status', $listing_id);
        $fields['listing_status'] = $status;
        $fields['status'] = $status;

        // Images using existing bridge function
        $images = $this->safe_bridge_call('hph_get_listing_images', $listing_id, 'large') ?: [];
        $fields['gallery'] = $images;
        $fields['photo_gallery'] = $images;
        
        // Main photo (first image or featured)
        $featured_image = get_the_post_thumbnail_url($listing_id, 'large');
        $main_photo = $featured_image ?: ($images[0]['url'] ?? null);
        $fields['main_photo'] = $main_photo;
        $fields['featured_image'] = $main_photo;

        // Description from ACF fields (direct access since no specific bridge function)
        $description = get_field('property_description', $listing_id) 
                      ?: get_field('description', $listing_id) 
                      ?: get_field('public_remarks', $listing_id)
                      ?: get_field('marketing_remarks', $listing_id);
        
        $fields['short_description'] = $description;
        $fields['description'] = $description;
        $fields['listing_description'] = $description;
        $fields['property_description'] = $description;
        $fields['public_remarks'] = $description;
        $fields['marketing_remarks'] = $description;
        $fields['brief_description'] = $description;
        $fields['remarks'] = $description;

        // MLS number (direct ACF access)
        $fields['mls_number'] = get_field('mls_number', $listing_id) ?: get_field('listing_number', $listing_id);

        // Lot acres calculation if lot size is available
        if (!empty($fields['lot_size'])) {
            // Convert square feet to acres (43,560 sq ft = 1 acre)
            $lot_sqft = floatval(preg_replace('/[^0-9.]/', '', $fields['lot_size']));
            $fields['lot_acres'] = $lot_sqft > 0 ? round($lot_sqft / 43560, 2) : null;
        } else {
            $fields['lot_acres'] = null;
        }

        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Flyer Generator - Listing fields for ' . $listing_id . ': ' . wp_json_encode([
                'price' => $fields['price'],
                'bedrooms' => $fields['bedrooms'], 
                'bathrooms' => $fields['bathrooms'],
                'sqft' => $fields['square_footage'],
                'address' => $fields['address'],
                'lot_size' => $fields['lot_size']
            ]));
        }

        return $fields;
    }

    /**
     * Get agent data with comprehensive field mapping using existing bridge functions
     */
    private function get_agent_data(int $listing_id): array {
        // First try to get agent from listing relationships
        $agent_post = get_field('listing_agent', $listing_id) ?: get_field('agent', $listing_id);
        
        if ($agent_post && is_object($agent_post) && isset($agent_post->ID)) {
            $agent_id = $agent_post->ID;
            
            // Use existing agent bridge functions
            $agent_data = $this->safe_bridge_call('hph_get_agent_data', $agent_id) ?: [];
            $agent_contact = $this->safe_bridge_call('hph_get_agent_contact', $agent_id) ?: [];
            $agent_photo = $this->safe_bridge_call('hph_get_agent_photo', $agent_id, 'medium');
            
            // Merge and normalize the data
            $combined_data = array_merge($agent_data, $agent_contact);
            $combined_data['photo'] = $agent_photo;
            $combined_data['image'] = $agent_photo;
            
            return $this->normalize_agent_data($combined_data);
        }
        
        // Fallback: try direct agent ID field
        $agent_id = get_field('agent_id', $listing_id);
        if ($agent_id) {
            $agent_data = $this->safe_bridge_call('hph_get_agent_data', $agent_id) ?: [];
            if (!empty($agent_data)) {
                return $this->normalize_agent_data($agent_data);
            }
        }
        
        // Final fallback
        return $this->get_fallback_agent_data();
    }

    /**
     * Get hosting agent data for open house flyers
     */
    private function get_hosting_agent_data(int $listing_id): ?array {
        if (!class_exists('HappyPlace\Core\Open_House_Bridge')) {
            return null;
        }

        try {
            $bridge = new \HappyPlace\Core\Open_House_Bridge();
            $open_house_data = $bridge->get_open_house_data($listing_id);
            
            if ($open_house_data && isset($open_house_data['hosting_agent_id'])) {
                $hosting_agent_data = $bridge->get_hosting_agent_data($open_house_data['hosting_agent_id']);
                
                if ($hosting_agent_data) {
                    return $this->normalize_agent_data($hosting_agent_data);
                }
            }
        } catch (Exception $e) {
            error_log('Flyer Generator: Error getting hosting agent data: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Normalize agent data with consistent field mapping
     */
    private function normalize_agent_data(array $agent_data): array {
        // Extract image URL from various possible formats
        $agent_image_url = '';
        if (isset($agent_data['image'])) {
            $agent_image_url = is_array($agent_data['image']) ? ($agent_data['image']['url'] ?? '') : $agent_data['image'];
        } elseif (isset($agent_data['photo'])) {
            $agent_image_url = is_array($agent_data['photo']) ? ($agent_data['photo']['url'] ?? '') : $agent_data['photo'];
        }

        // Normalize basic data
        $normalized = [
            'name'         => $agent_data['display_name'] ?? $agent_data['name'] ?? 'Agent Name Not Available',
            'display_name' => $agent_data['display_name'] ?? $agent_data['name'] ?? 'Agent Name Not Available',
            'email'        => $agent_data['email'] ?? $agent_data['contact_email'] ?? 'info@theparkergroup.com',
            'phone'        => $agent_data['phone'] ?? $agent_data['mobile_phone'] ?? $agent_data['office_phone'] ?? '302.217.6692',
        ];

        // Map image to all possible field names
        $image_fields = ['image', 'profile_photo', 'photo', 'agent_photo', 'headshot', 
                        'profile_image', 'profile_pic', 'user_photo', 'avatar', 'picture'];
        
        foreach ($image_fields as $field) {
            $normalized[$field] = $agent_image_url;
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Flyer Generator - Normalized agent data: ' . wp_json_encode($normalized));
        }

        return $normalized;
    }

    /**
     * Get fallback agent data
     */
    private function get_fallback_agent_data(): array {
        $fallback_data = [
            'name'           => 'Agent Name Not Available',
            'display_name'   => 'Agent Name Not Available', 
            'phone'          => '302.217.6692',
            'email'          => 'info@theparkergroup.com',
        ];

        // Add null image fields
        $image_fields = ['image', 'profile_photo', 'photo', 'agent_photo', 'headshot',
                        'profile_image', 'profile_pic', 'user_photo', 'avatar', 'picture'];
        
        foreach ($image_fields as $field) {
            $fallback_data[$field] = null;
        }

        return $fallback_data;
    }

    /**
     * Get community data if available
     */
    private function get_community_data(int $listing_id): array {
        $community = get_field('community', $listing_id);
        
        if (!$community || !is_object($community) || !isset($community->ID)) {
            return [];
        }

        return [
            'name'        => get_the_title($community->ID),
            'description' => get_field('community_description', $community->ID),
            'amenities'   => get_field('amenities', $community->ID),
            'hoa_fees'    => get_field('hoa_fees', $community->ID),
        ];
    }

    /**
     * Debug admin notice (only shown in debug mode)
     */
    public function debug_admin_notice(): void {
        if (current_user_can('manage_options')) {
            $assets_url = defined('HPH_ASSETS_URL') ? HPH_ASSETS_URL : 'NOT DEFINED';
            $version = defined('HPH_VERSION') ? HPH_VERSION : 'NOT DEFINED';
            
            // Check if required files exist
            $js_file = $assets_url . 'js/marketing-suite-generator.js';
            $css_file = $assets_url . 'css/marketing-suite-generator.css';
            $template_file = plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/flyer-generator.php';
            
            echo '<div class="notice notice-info is-dismissible">';
            echo '<p><strong>Flyer Generator Debug:</strong> Class loaded successfully</p>';
            echo '<p>Assets URL: ' . esc_html($assets_url) . '</p>';
            echo '<p>Version: ' . esc_html($version) . '</p>';
            echo '<p>JavaScript File: ' . (file_exists(str_replace($assets_url, plugin_dir_path(dirname(dirname(__FILE__))) . 'assets/', $js_file)) ? '✅ Found' : '❌ Missing') . '</p>';
            echo '<p>CSS File: ' . (file_exists(str_replace($assets_url, plugin_dir_path(dirname(dirname(__FILE__))) . 'assets/', $css_file)) ? '✅ Found' : '❌ Missing') . '</p>';
            echo '<p>Template File: ' . (file_exists($template_file) ? '✅ Found' : '❌ Missing') . '</p>';
            echo '<p>AJAX Actions: wp_ajax_generate_flyer, wp_ajax_nopriv_generate_flyer</p>';
            echo '<p>Shortcode: [listing_flyer_generator]</p>';
            echo '</div>';
        }
    }

    /**
     * Add debug menu for testing
     */
    public function add_debug_menu(): void {
        add_submenu_page(
            'tools.php',
            'Flyer Generator Test',
            'Flyer Generator Test',
            'manage_options',
            'flyer-generator-test',
            [$this, 'render_debug_page']
        );
    }

    /**
     * Render debug page
     */
    public function render_debug_page(): void {
        // Force enqueue scripts for this page
        $this->enqueue_scripts();
        
        echo '<div class="wrap">';
        echo '<h1>Flyer Generator Test</h1>';
        echo '<div class="notice notice-info"><p>This page tests the Flyer Generator functionality in debug mode.</p></div>';
        
        // Render the flyer generator
        echo $this->render_flyer_generator([]);
        
        echo '</div>';
    }

    /**
     * Shortcode output
     */
    public function render_flyer_generator($atts): string {
        $atts = shortcode_atts([
            'listing_id' => 0,
            'template'   => 'parker_group',
            'show_types' => 'listing,open_house', // Comma-separated list of flyer types to show
            'class'      => '',
            'style'      => ''
        ], $atts);

        // Force enqueue scripts - this ensures they always load with the shortcode
        add_action('wp_footer', function() {
            $this->enqueue_scripts();
        });
        
        // Also try enqueueing immediately
        $this->enqueue_scripts();

        // Validate listing ID if provided
        $listing_id = intval($atts['listing_id']);
        if ($listing_id > 0) {
            $listing = get_post($listing_id);
            if (!$listing || $listing->post_type !== 'listing' || $listing->post_status !== 'publish') {
                return '<div class="flyer-generator-error">' . 
                       __('Invalid listing specified.', 'happy-place') . 
                       '</div>';
            }
        }

        // Parse allowed flyer types
        $allowed_types = array_map('trim', explode(',', $atts['show_types']));
        $allowed_types = array_intersect($allowed_types, ['listing', 'open_house', 'sold', 'coming_soon']);
        
        if (empty($allowed_types)) {
            $allowed_types = ['listing']; // Default fallback
        }

        // Start output buffering
        ob_start();
        
        // Check if template exists
        $template_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/flyer-generator-simple.php';
        if (!file_exists($template_path)) {
            // Fallback to main template
            $template_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/flyer-generator.php';
            if (!file_exists($template_path)) {
                echo '<div class="flyer-generator-error">' . 
                     __('Flyer generator template not found.', 'happy-place') . 
                     '</div>';
                return ob_get_clean();
            }
        }

        // Make variables available to template
        $template_atts = $atts;
        $template_listing_id = $listing_id;
        $template_allowed_types = $allowed_types;

        try {
            include $template_path;
        } catch (Exception $e) {
            error_log("Flyer Generator Template Error: " . $e->getMessage());
            echo '<div class="flyer-generator-error">' . 
                 __('Error loading flyer generator.', 'happy-place') . 
                 '</div>';
        }

        return ob_get_clean();
    }
}

// Note: Class is instantiated by Plugin Manager
// No direct instantiation needed here
