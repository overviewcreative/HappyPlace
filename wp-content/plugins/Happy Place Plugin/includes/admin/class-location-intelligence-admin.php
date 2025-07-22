<?php
/**
 * Location Intelligence Admin Interface
 *
 * Adds refresh buttons and controls to ACF location intelligence fields
 *
 * @package HappyPlace
 * @subpackage Admin
 */

namespace HappyPlace\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Location_Intelligence_Admin {
    
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
        add_action('acf/input/admin_head', [$this, 'add_location_intelligence_controls']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        // Add meta box to listing edit screen
        add_action('add_meta_boxes', [$this, 'add_location_intelligence_meta_box']);
    }
    
    /**
     * Add location intelligence meta box
     */
    public function add_location_intelligence_meta_box(): void {
        add_meta_box(
            'hph-location-intelligence-controls',
            __('Location Intelligence Controls', 'happy-place'),
            [$this, 'render_location_intelligence_meta_box'],
            'listing',
            'side',
            'high'
        );
    }
    
    /**
     * Render location intelligence meta box
     */
    public function render_location_intelligence_meta_box($post): void {
        $lat = get_field('latitude', $post->ID);
        $lng = get_field('longitude', $post->ID);
        $last_updated = get_field('location_intelligence_last_updated', $post->ID);
        
        wp_nonce_field('hph_location_intelligence', 'hph_location_intelligence_nonce');
        
        ?>
        <div id="hph-location-intelligence-controls">
            <?php if ($lat && $lng): ?>
                <p><strong><?php _e('Coordinates:', 'happy-place'); ?></strong><br>
                <?php echo esc_html($lat . ', ' . $lng); ?></p>
                
                <?php if ($last_updated): ?>
                    <p><strong><?php _e('Last Updated:', 'happy-place'); ?></strong><br>
                    <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_updated); ?></p>
                <?php endif; ?>
                
                <div class="hph-refresh-buttons">
                    <button type="button" class="button button-secondary hph-refresh-btn" data-action="refresh_all" data-post-id="<?php echo $post->ID; ?>">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Refresh All Data', 'happy-place'); ?>
                    </button>
                    
                    <button type="button" class="button button-secondary hph-refresh-btn" data-action="refresh_schools" data-post-id="<?php echo $post->ID; ?>">
                        <span class="dashicons dashicons-building"></span>
                        <?php _e('Refresh Schools', 'happy-place'); ?>
                    </button>
                    
                    <button type="button" class="button button-secondary hph-refresh-btn" data-action="refresh_walkability" data-post-id="<?php echo $post->ID; ?>">
                        <span class="dashicons dashicons-chart-line"></span>
                        <?php _e('Refresh Walkability', 'happy-place'); ?>
                    </button>
                    
                    <button type="button" class="button button-secondary hph-refresh-btn" data-action="refresh_amenities" data-post-id="<?php echo $post->ID; ?>">
                        <span class="dashicons dashicons-location"></span>
                        <?php _e('Refresh Amenities', 'happy-place'); ?>
                    </button>
                </div>
                
                <div id="hph-refresh-status"></div>
                
                <p class="description">
                    <?php _e('Use these buttons to manually refresh location intelligence data from external APIs.', 'happy-place'); ?>
                </p>
                
            <?php else: ?>
                <p class="description">
                    <?php _e('Location coordinates are required for auto-population. Please save the listing with a valid address first.', 'happy-place'); ?>
                </p>
            <?php endif; ?>
        </div>
        
        <style>
        #hph-location-intelligence-controls .hph-refresh-buttons {
            display: grid;
            gap: 10px;
            margin: 15px 0;
        }
        
        #hph-location-intelligence-controls .hph-refresh-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 8px 12px;
        }
        
        #hph-location-intelligence-controls .hph-refresh-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        #hph-refresh-status {
            margin: 10px 0;
            padding: 10px;
            border-radius: 4px;
            display: none;
        }
        
        #hph-refresh-status.success {
            background: #d1edff;
            border: 1px solid #0073aa;
            color: #0073aa;
        }
        
        #hph-refresh-status.error {
            background: #fbeaea;
            border: 1px solid #d63638;
            color: #d63638;
        }
        
        #hph-refresh-status.loading {
            background: #fff3cd;
            border: 1px solid #856404;
            color: #856404;
        }
        </style>
        <?php
    }
    
    /**
     * Add location intelligence controls to ACF fields
     */
    public function add_location_intelligence_controls(): void {
        global $post;
        
        if (!$post || $post->post_type !== 'listing') {
            return;
        }
        
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Add refresh buttons to readonly location intelligence fields
            const readonlyFields = [
                'field_school_district',
                'field_elementary_school', 
                'field_middle_school',
                'field_high_school',
                'field_walk_score',
                'field_transit_score',
                'field_bike_score'
            ];
            
            readonlyFields.forEach(function(fieldKey) {
                const field = $('.acf-field[data-key="' + fieldKey + '"]');
                if (field.length) {
                    const refreshBtn = $('<button type="button" class="button button-small acf-refresh-btn" data-field="' + fieldKey + '"><span class="dashicons dashicons-update"></span></button>');
                    field.find('.acf-input').append(refreshBtn);
                }
            });
            
            // Add refresh button to nearby amenities repeater
            const amenitiesField = $('.acf-field[data-key="field_nearby_amenities"]');
            if (amenitiesField.length) {
                const refreshBtn = $('<button type="button" class="button button-small acf-refresh-amenities-btn"><span class="dashicons dashicons-update"></span> Refresh Amenities</button>');
                amenitiesField.find('.acf-actions').prepend(refreshBtn);
            }
        });
        </script>
        
        <style>
        .acf-refresh-btn,
        .acf-refresh-amenities-btn {
            margin-left: 10px;
            vertical-align: top;
        }
        
        .acf-refresh-btn .dashicons,
        .acf-refresh-amenities-btn .dashicons {
            font-size: 14px;
            width: 14px;
            height: 14px;
        }
        
        .acf-field[data-readonly="1"] .acf-input {
            position: relative;
        }
        </style>
        <?php
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook): void {
        global $post;
        
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        
        if (!$post || $post->post_type !== 'listing') {
            return;
        }
        
        wp_enqueue_script(
            'hph-location-intelligence-admin',
            plugin_dir_url(dirname(__FILE__)) . '../assets/js/location-intelligence-admin.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        wp_localize_script('hph-location-intelligence-admin', 'hphLocationIntelligence', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_location_intelligence'),
            'postId' => $post->ID,
            'messages' => [
                'refreshing' => __('Refreshing...', 'happy-place'),
                'success' => __('Data refreshed successfully', 'happy-place'),
                'error' => __('Error refreshing data', 'happy-place'),
                'confirm' => __('This will refresh all location intelligence data. Continue?', 'happy-place')
            ]
        ]);
    }
}

// Initialize the admin interface
Location_Intelligence_Admin::get_instance();
