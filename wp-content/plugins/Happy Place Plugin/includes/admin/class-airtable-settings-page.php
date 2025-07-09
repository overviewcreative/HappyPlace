<?php
namespace HappyPlace\Admin;

if (!defined('ABSPATH')) exit;

class Airtable_Settings_Page {
    public static function register_menu() {
        add_submenu_page(
            'toplevel_page_happy-place',
            __('Airtable Settings', 'happy-place'),
            __('Airtable Settings', 'happy-place'),
            'manage_options',
            'happy-place-airtable-settings',
            [__CLASS__, 'render_page']
        );
    }

    public static function render_page() {
        if (!current_user_can('manage_options')) return;
        if (isset($_POST['hph_airtable_save']) && check_admin_referer('hph_airtable_settings')) {
            update_option('hph_airtable_token', sanitize_text_field($_POST['hph_airtable_token']));
            update_option('hph_airtable_base', sanitize_text_field($_POST['hph_airtable_base']));
            update_option('hph_airtable_table', sanitize_text_field($_POST['hph_airtable_table']));
            echo '<div class="updated"><p>' . esc_html__('Settings saved.', 'happy-place') . '</p></div>';
        }
        $token = esc_attr(get_option('hph_airtable_token', ''));
        $base = esc_attr(get_option('hph_airtable_base', ''));
        $table = esc_attr(get_option('hph_airtable_table', ''));
        ?>
        <div class="wrap">
            <h1><?php _e('Airtable API Settings', 'happy-place'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('hph_airtable_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="hph_airtable_token"><?php _e('API Token', 'happy-place'); ?></label></th>
                        <td><input type="text" id="hph_airtable_token" name="hph_airtable_token" value="<?php echo $token; ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="hph_airtable_base"><?php _e('Base ID', 'happy-place'); ?></label></th>
                        <td><input type="text" id="hph_airtable_base" name="hph_airtable_base" value="<?php echo $base; ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="hph_airtable_table"><?php _e('Table Name', 'happy-place'); ?></label></th>
                        <td><input type="text" id="hph_airtable_table" name="hph_airtable_table" value="<?php echo $table; ?>" class="regular-text" required></td>
                    </tr>
                </table>
                <?php submit_button(__('Save Settings', 'happy-place'), 'primary', 'hph_airtable_save'); ?>
            </form>
        </div>
        <?php
    }
}

add_action('admin_menu', ['HappyPlace\Admin\Airtable_Settings_Page', 'register_menu']);
