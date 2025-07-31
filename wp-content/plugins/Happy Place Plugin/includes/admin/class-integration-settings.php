<?php
/**
 * Integration Settings Page for Happy Place Plugin
 * 
 * @package Happy_Place_Plugin
 */

namespace HappyPlace\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Integration_Settings {
    
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
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu(): void {
        add_submenu_page(
            'happy-place-admin',
            __('Integration Settings', 'happy-place'),
            __('Integrations', 'happy-place'),
            'manage_options',
            'happy-place-integrations',
            [$this, 'render_page']
        );
    }
    
    /**
     * Render the integration settings page
     */
    public function render_page(): void {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Integration Settings', 'happy-place'); ?></h1>
            <p><?php echo esc_html__('Configure integrations with external services.', 'happy-place'); ?></p>
            
            <div class="card">
                <h2><?php echo esc_html__('Available Integrations', 'happy-place'); ?></h2>
                <p><?php echo esc_html__('Integration settings are managed through the main Integrations Manager.', 'happy-place'); ?></p>
                <p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=happy-place-integrations-manager')); ?>" class="button button-primary">
                        <?php echo esc_html__('Go to Integrations Manager', 'happy-place'); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }
}
