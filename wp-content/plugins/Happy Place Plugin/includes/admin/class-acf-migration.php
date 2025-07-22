<?php
/**
 * ACF Migration Helper
 *
 * Helps migrate data from old ACF structure to new reorganized structure
 *
 * @package HappyPlace
 * @subpackage Admin
 */

namespace HappyPlace\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class ACF_Migration {
    
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
        add_action('admin_menu', [$this, 'add_migration_page']);
    }
    
    /**
     * Add migration admin page
     */
    public function add_migration_page(): void {
        add_submenu_page(
            'edit.php?post_type=listing',
            __('ACF Migration', 'happy-place'),
            __('ACF Migration', 'happy-place'),
            'manage_options',
            'hph-acf-migration',
            [$this, 'render_migration_page']
        );
    }
    
    /**
     * Render migration page
     */
    public function render_migration_page(): void {
        if (isset($_POST['migrate_school_data'])) {
            $this->migrate_school_data();
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('ACF Field Migration', 'happy-place'); ?></h1>
            
            <div class="card">
                <h2><?php _e('School Data Migration', 'happy-place'); ?></h2>
                <p><?php _e('Migrate school data from individual fields to new grouped structure.', 'happy-place'); ?></p>
                
                <form method="post">
                    <?php wp_nonce_field('hph_migrate_school_data'); ?>
                    <input type="hidden" name="migrate_school_data" value="1">
                    <p class="submit">
                        <input type="submit" class="button button-primary" value="<?php esc_attr_e('Migrate School Data', 'happy-place'); ?>">
                    </p>
                </form>
            </div>
            
            <div class="card">
                <h2><?php _e('Migration Status', 'happy-place'); ?></h2>
                <p><?php _e('Check the status of field migrations:', 'happy-place'); ?></p>
                
                <?php $this->show_migration_status(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Migrate school data from individual fields to grouped structure
     */
    private function migrate_school_data(): void {
        check_admin_referer('hph_migrate_school_data');
        
        $listings = get_posts([
            'post_type' => 'listing',
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'elementary_school',
                    'compare' => 'EXISTS'
                ],
                [
                    'key' => 'middle_school',
                    'compare' => 'EXISTS'
                ],
                [
                    'key' => 'high_school',
                    'compare' => 'EXISTS'
                ]
            ]
        ]);
        
        $migrated = 0;
        
        foreach ($listings as $listing) {
            $elementary = get_field('elementary_school', $listing->ID);
            $middle = get_field('middle_school', $listing->ID);
            $high = get_field('high_school', $listing->ID);
            
            if ($elementary || $middle || $high) {
                $assigned_schools = [];
                
                if ($elementary) {
                    $assigned_schools['elementary_school'] = $elementary;
                    $assigned_schools['elementary_rating'] = get_field('elementary_rating', $listing->ID) ?: null;
                }
                
                if ($middle) {
                    $assigned_schools['middle_school'] = $middle;
                    $assigned_schools['middle_rating'] = get_field('middle_rating', $listing->ID) ?: null;
                }
                
                if ($high) {
                    $assigned_schools['high_school'] = $high;
                    $assigned_schools['high_rating'] = get_field('high_rating', $listing->ID) ?: null;
                }
                
                update_field('assigned_schools', $assigned_schools, $listing->ID);
                $migrated++;
            }
        }
        
        echo '<div class="notice notice-success"><p>' . 
             sprintf(__('Successfully migrated school data for %d listings.', 'happy-place'), $migrated) . 
             '</p></div>';
    }
    
    /**
     * Show migration status
     */
    private function show_migration_status(): void {
        // Count listings with old structure
        $old_school_count = count(get_posts([
            'post_type' => 'listing',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'elementary_school',
                    'compare' => 'EXISTS'
                ]
            ]
        ]));
        
        // Count listings with new structure
        $new_school_count = count(get_posts([
            'post_type' => 'listing',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'assigned_schools',
                    'compare' => 'EXISTS'
                ]
            ]
        ]));
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Migration Item', 'happy-place'); ?></th>
                    <th><?php _e('Old Structure', 'happy-place'); ?></th>
                    <th><?php _e('New Structure', 'happy-place'); ?></th>
                    <th><?php _e('Status', 'happy-place'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php _e('School Data', 'happy-place'); ?></td>
                    <td><?php echo $old_school_count; ?> listings</td>
                    <td><?php echo $new_school_count; ?> listings</td>
                    <td>
                        <?php if ($new_school_count > 0): ?>
                            <span class="dashicons dashicons-yes" style="color: green;"></span>
                            <?php _e('Migrated', 'happy-place'); ?>
                        <?php else: ?>
                            <span class="dashicons dashicons-warning" style="color: orange;"></span>
                            <?php _e('Needs Migration', 'happy-place'); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }
}

// Initialize migration helper
ACF_Migration::get_instance();
