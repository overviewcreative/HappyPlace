<?php
/**
 * Admin tools for listings
 */

if (!defined('ABSPATH')) {
    exit;
}

class HPH_Listing_Admin {
    private static ?self $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'add_tools_page']);
        add_action('admin_post_hph_geocode_all_listings', [$this, 'handle_geocode_all']);
    }

    /**
     * Add tools page
     */
    public function add_tools_page(): void {
        add_management_page(
            __('Listing Tools', 'happy-place'),
            __('Listing Tools', 'happy-place'),
            'manage_options',
            'listing-tools',
            [$this, 'render_tools_page']
        );
    }

    /**
     * Render tools page
     */
    public function render_tools_page(): void {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Listing Tools', 'happy-place'); ?></h1>

            <div class="card">
                <h2><?php esc_html_e('Geocode All Listings', 'happy-place'); ?></h2>
                <p><?php esc_html_e('This will attempt to geocode all listings that don\'t have coordinates.', 'happy-place'); ?></p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('hph_geocode_all_listings'); ?>
                    <input type="hidden" name="action" value="hph_geocode_all_listings">
                    <?php submit_button(__('Geocode All Listings', 'happy-place'), 'primary'); ?>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Handle geocoding all listings
     */
    public function handle_geocode_all(): void {
        // Verify permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'happy-place'));
        }

        // Verify nonce
        check_admin_referer('hph_geocode_all_listings');

        // Get all listings without coordinates
        $args = [
            'post_type' => 'listing',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'latitude',
                    'value' => '',
                    'compare' => '='
                ],
                [
                    'key' => 'longitude',
                    'value' => '',
                    'compare' => '='
                ]
            ]
        ];

        $listings = get_posts($args);
        $geocoded = 0;

        foreach ($listings as $listing) {
            if (hph_geocoding()->force_geocode_listing($listing->ID)) {
                $geocoded++;
            }
        }

        // Redirect back with message
        wp_redirect(add_query_arg([
            'page' => 'listing-tools',
            'geocoded' => $geocoded
        ], admin_url('tools.php')));
        exit;
    }
}

// Initialize the admin tools
function hph_listing_admin(): HPH_Listing_Admin {
    return HPH_Listing_Admin::instance();
}
