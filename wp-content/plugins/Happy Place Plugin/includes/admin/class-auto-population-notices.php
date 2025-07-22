<?php
/**
 * Auto-Population Admin Notices
 *
 * Displays admin notices about auto-population functionality
 *
 * @package HappyPlace
 * @subpackage Admin
 */

namespace HappyPlace\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Auto_Population_Notices {
    
    /**
     * Instance
     */
    private static ?self $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_notices', [$this, 'display_setup_notice']);
        add_action('wp_ajax_hph_dismiss_auto_population_notice', [$this, 'dismiss_notice']);
    }
    
    /**
     * Display setup notice for auto-population
     */
    public function display_setup_notice(): void {
        // Only show on listing edit pages and plugin pages
        $screen = get_current_screen();
        if (!$screen || (!in_array($screen->id, ['listing', 'happy-place_page_hph-external-apis']) && $screen->post_type !== 'listing')) {
            return;
        }
        
        // Check if user dismissed the notice
        if (get_user_meta(get_current_user_id(), 'hph_auto_population_notice_dismissed', true)) {
            return;
        }
        
        // Check if Google Maps API is configured
        $google_api_key = get_option('hph_google_maps_api_key', '');
        if (empty($google_api_key)) {
            $this->show_setup_required_notice();
        } else {
            $this->show_feature_info_notice();
        }
    }
    
    /**
     * Show setup required notice
     */
    private function show_setup_required_notice(): void {
        ?>
        <div class="notice notice-warning is-dismissible hph-auto-population-notice">
            <h3><?php _e('Location Intelligence Auto-Population Available', 'happy-place'); ?></h3>
            <p>
                <?php _e('Automatically populate school information, walkability scores, nearby amenities, and property tax data for your listings!', 'happy-place'); ?>
            </p>
            <p>
                <strong><?php _e('Setup Required:', 'happy-place'); ?></strong>
                <?php _e('Configure your Google Maps API key to enable auto-population features.', 'happy-place'); ?>
            </p>
            <p>
                <a href="<?php echo admin_url('admin.php?page=hph-external-apis'); ?>" class="button button-primary">
                    <?php _e('Configure External APIs', 'happy-place'); ?>
                </a>
                <button type="button" class="button button-secondary hph-dismiss-notice" data-notice="auto_population">
                    <?php _e('Dismiss', 'happy-place'); ?>
                </button>
            </p>
        </div>
        <?php
    }
    
    /**
     * Show feature info notice
     */
    private function show_feature_info_notice(): void {
        ?>
        <div class="notice notice-info is-dismissible hph-auto-population-notice">
            <h3><?php _e('Location Intelligence Auto-Population Active', 'happy-place'); ?></h3>
            <p>
                <?php _e('Your listings will automatically have location intelligence data populated including schools, walkability scores, and nearby amenities.', 'happy-place'); ?>
            </p>
            <p>
                <strong><?php _e('How it works:', 'happy-place'); ?></strong>
                <?php _e('When you save a listing with an address, the system automatically populates readonly fields with location data from Google APIs.', 'happy-place'); ?>
            </p>
            <p>
                <a href="<?php echo admin_url('admin.php?page=hph-external-apis'); ?>" class="button button-secondary">
                    <?php _e('Manage API Settings', 'happy-place'); ?>
                </a>
                <button type="button" class="button button-secondary hph-dismiss-notice" data-notice="auto_population">
                    <?php _e('Got it', 'happy-place'); ?>
                </button>
            </p>
        </div>
        <?php
    }
    
    /**
     * Dismiss notice via AJAX
     */
    public function dismiss_notice(): void {
        check_ajax_referer('hph_auto_population_notice', 'nonce');
        
        $notice_type = sanitize_text_field($_POST['notice']);
        if ($notice_type === 'auto_population') {
            update_user_meta(get_current_user_id(), 'hph_auto_population_notice_dismissed', true);
            wp_send_json_success();
        }
        
        wp_send_json_error();
    }
}

// Add JavaScript for dismissing notices
add_action('admin_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.hph-dismiss-notice').on('click', function(e) {
            e.preventDefault();
            
            const notice = $(this).data('notice');
            const noticeDiv = $(this).closest('.notice');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hph_dismiss_auto_population_notice',
                    notice: notice,
                    nonce: '<?php echo wp_create_nonce('hph_auto_population_notice'); ?>'
                },
                success: function() {
                    noticeDiv.fadeOut();
                }
            });
        });
    });
    </script>
    <?php
});
