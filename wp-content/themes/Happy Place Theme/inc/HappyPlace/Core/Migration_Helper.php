<?php
/**
 * Theme Migration Helper
 * 
 * This script helps transition from old class structure to new namespace-based structure
 */

namespace HappyPlace\Core;

class Migration_Helper {
    private static bool $migrated = false;
    
    private static array $deprecated_files = [
        'class-listing-helper.php',
        'class-geocoding.php',
        'class-listing-admin.php',
        'class-template-loader.php',
        'class-asset-loader.php',
        'class-assets-manager.php',
        'listings/class-listing-display.php',
        'listings/class-listing-module.php'
    ];

    private static array $class_mappings = [
        'HPH_Listing_Helper' => 'HappyPlace\\Listings\\Helper',
        'HPH_Geocoding' => 'HappyPlace\\Utilities\\Geocoding',
        'HPH_QR_Code' => 'HappyPlace\\Utilities\\QR_Code',
        'HPH_Listing_Admin' => 'HappyPlace\\Admin\\Listing_Tools',
        'Listing_Display' => 'HappyPlace\\Listings\\Display',
        'Listing_Module' => 'HappyPlace\\Listings\\Module',
        'Template_Loader' => 'HappyPlace\\Core\\Template_Loader',
        'HPH_Asset_Loader' => 'HappyPlace\\Core\\Asset_Loader',
        'HPH_Theme_Assets' => 'HappyPlace\\Core\\Assets',
        'HPH_Theme_Template_Loader' => 'HappyPlace\\Core\\Template_Manager',
        'HPH_Theme_Ajax_Handler' => 'HappyPlace\\Core\\Ajax_Handler'
    ];

    public static function migrate(): void {
        // Prevent multiple migrations
        if (self::$migrated) {
            return;
        }
        
        self::$migrated = true;
        
        // 1. Display migration notice
        add_action('admin_notices', [self::class, 'show_migration_notice']);

        // 2. Add backwards compatibility
        self::add_backwards_compatibility();
    }

    public static function show_migration_notice(): void {
        $screen = get_current_screen();
        if ($screen->id !== 'themes') {
            return;
        }

        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong><?php _e('Happy Place Theme Update Notice', 'happy-place'); ?></strong>
            </p>
            <p>
                <?php _e('The theme structure has been updated to use modern PHP practices. The following files are now deprecated:', 'happy-place'); ?>
            </p>
            <ul style="list-style-type: disc; margin-left: 2em;">
                <?php foreach (self::$deprecated_files as $file): ?>
                    <li><?php echo esc_html($file); ?></li>
                <?php endforeach; ?>
            </ul>
            <p>
                <?php _e('Please update any custom code to use the new namespace-based classes in the inc/HappyPlace directory.', 'happy-place'); ?>
            </p>
        </div>
        <?php
    }

    private static function add_backwards_compatibility(): void {
        // Prevent multiple executions of function declarations
        static $functions_declared = false;
        if ($functions_declared) {
            return;
        }
        $functions_declared = true;
        
        // Add class aliases for backwards compatibility
        foreach (self::$class_mappings as $legacy => $new) {
            if (class_exists($new) && !class_exists($legacy)) {
                class_alias($new, $legacy);
            }
        }

        // Initialize new classes
        self::initialize_new_classes();

        // Add deprecated notices
        foreach (self::$deprecated_files as $file) {
            $path = get_template_directory() . '/inc/' . $file;
            if (file_exists($path)) {
                trigger_error(
                    sprintf(
                        __('The file %s is deprecated and will be removed in a future version. Please update your code to use the new namespace-based classes.', 'happy-place'),
                        $file
                    ),
                    E_USER_DEPRECATED
                );
            }
        }

        // Add legacy function wrappers
        self::add_legacy_functions();
    }

    private static function initialize_new_classes(): void {
        // Initialize listing module
        if (class_exists('HappyPlace\\Listings\\Module')) {
            \HappyPlace\Listings\Module::instance();
        }

        // Initialize utilities
        if (class_exists('HappyPlace\\Utilities\\Geocoding')) {
            \HappyPlace\Utilities\Geocoding::instance();
        }

        // Initialize template loader
        if (class_exists('HappyPlace\\Core\\Template_Loader')) {
            \HappyPlace\Core\Template_Loader::instance();
        }

        // Initialize asset loader
        if (class_exists('HappyPlace\\Core\\Asset_Loader')) {
            \HappyPlace\Core\Asset_Loader::instance();
        }

        // Initialize FontAwesome icons utility
        if (class_exists('HappyPlace\\Utilities\\FontAwesome_Icons')) {
            \HappyPlace\Utilities\FontAwesome_Icons::instance();
        }

        // Initialize listing template helper
        if (class_exists('HappyPlace\\Listings\\Template_Helper')) {
            \HappyPlace\Listings\Template_Helper::instance();
        }

        // Initialize admin tools
        if (is_admin() && class_exists('HappyPlace\\Admin\\Listing_Tools')) {
            \HappyPlace\Admin\Listing_Tools::instance();
        }
    }

    private static function add_legacy_functions(): void {
        // Only define if not already defined to avoid conflicts
        if (!function_exists('hph_listing')) {
            function hph_listing(): \HappyPlace\Listings\Helper {
                return \HappyPlace\Listings\Helper::instance();
            }
        }

        if (!function_exists('hph_geocoding')) {
            function hph_geocoding(): \HappyPlace\Utilities\Geocoding {
                return \HappyPlace\Utilities\Geocoding::instance();
            }
        }

        if (!function_exists('hph_listing_admin')) {
            function hph_listing_admin(): \HappyPlace\Admin\Listing_Tools {
                return \HappyPlace\Admin\Listing_Tools::instance();
            }
        }

        // Add legacy template functions
        if (!function_exists('hph_render_listing_card')) {
            function hph_render_listing_card($listing_id, $style = 'default'): void {
                \HappyPlace\Listings\Display::render_listing_card($listing_id, $style);
            }
        }

        if (!function_exists('hph_render_property_details')) {
            function hph_render_property_details($listing_id = null): void {
                \HappyPlace\Listings\Display::render_property_details($listing_id);
            }
        }

        // QR Code functions
        if (!function_exists('hph_generate_listing_qr')) {
            function hph_generate_listing_qr(int $listing_id, int $size = 150): string {
                return \HappyPlace\Utilities\QR_Code::generate_listing_qr($listing_id, $size);
            }
        }

        if (!function_exists('hph_generate_agent_vcard_qr')) {
            function hph_generate_agent_vcard_qr(int $agent_id, int $size = 150): string {
                return \HappyPlace\Utilities\QR_Code::generate_agent_vcard_qr($agent_id, $size);
            }
        }

        // Asset loading functions
        if (!function_exists('hph_enqueue_template_assets')) {
            function hph_enqueue_template_assets(string $template_type): void {
                \HappyPlace\Core\Asset_Loader::instance()->enqueue_template_assets($template_type);
            }
        }

        // Listing template helper functions
        if (!function_exists('hph_format_price')) {
            function hph_format_price($price): string {
                return \HappyPlace\Listings\Template_Helper::instance()->format_price($price);
            }
        }

        if (!function_exists('hph_get_formatted_address')) {
            function hph_get_formatted_address($listing_id): string {
                return \HappyPlace\Listings\Template_Helper::instance()->get_formatted_address($listing_id);
            }
        }

        if (!function_exists('hph_get_property_features')) {
            function hph_get_property_features($listing_id): array {
                return \HappyPlace\Listings\Template_Helper::instance()->get_property_features($listing_id);
            }
        }

        if (!function_exists('hph_get_main_image')) {
            function hph_get_main_image($listing_id): string {
                return \HappyPlace\Listings\Template_Helper::instance()->get_main_image($listing_id);
            }
        }

        if (!function_exists('hph_get_status_display')) {
            function hph_get_status_display($listing_id): array {
                return \HappyPlace\Listings\Template_Helper::instance()->get_status_display($listing_id);
            }
        }

        if (!function_exists('hph_get_property_details')) {
            function hph_get_property_details($listing_id): array {
                return \HappyPlace\Listings\Template_Helper::instance()->get_property_details($listing_id);
            }
        }

        if (!function_exists('hph_is_favorite')) {
            function hph_is_favorite($listing_id): bool {
                return \HappyPlace\Listings\Template_Helper::instance()->is_favorite($listing_id);
            }
        }

        if (!function_exists('hph_get_listing_agent')) {
            function hph_get_listing_agent($listing_id): ?array {
                return \HappyPlace\Listings\Template_Helper::instance()->get_listing_agent($listing_id);
            }
        }

        if (!function_exists('hph_get_similar_listings')) {
            function hph_get_similar_listings($listing_id, $limit = 3): array {
                return \HappyPlace\Listings\Template_Helper::instance()->get_similar_listings($listing_id, $limit);
            }
        }

        if (!function_exists('hph_format_square_feet')) {
            function hph_format_square_feet($sqft): string {
                return \HappyPlace\Listings\Template_Helper::instance()->format_square_feet($sqft);
            }
        }
    }
}

// Initialize migration helper
add_action('init', ['HappyPlace\Core\Migration_Helper', 'migrate']);
