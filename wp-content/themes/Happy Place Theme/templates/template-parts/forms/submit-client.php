<?php
/**
 * Template part for client submission form
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="submit-client-form" enctype="multipart/form-data">
    <?php wp_nonce_field('submit_client_action', 'submit_client_nonce'); ?>
    <input type="hidden" name="action" value="submit_client">

    <div class="hph-form-group">
        <label for="client_first_name"><?php esc_html_e('First Name', 'happyplace'); ?> *</label>
        <input type="text" id="client_first_name" name="client_first_name" required>
    </div>

    <div class="hph-form-group">
        <label for="client_last_name"><?php esc_html_e('Last Name', 'happyplace'); ?> *</label>
        <input type="text" id="client_last_name" name="client_last_name" required>
    </div>

    <div class="hph-form-group">
        <label for="client_email"><?php esc_html_e('Email', 'happyplace'); ?> *</label>
        <input type="email" id="client_email" name="client_email" required>
    </div>

    <div class="hph-form-group">
        <label for="client_phone"><?php esc_html_e('Phone', 'happyplace'); ?> *</label>
        <input type="tel" id="client_phone" name="client_phone" required>
    </div>

    <div class="hph-form-group">
        <label for="client_address"><?php esc_html_e('Address', 'happyplace'); ?></label>
        <input type="text" id="client_address" name="client_address">
    </div>

    <div class="hph-form-group">
        <label for="client_city"><?php esc_html_e('City', 'happyplace'); ?></label>
        <input type="text" id="client_city" name="client_city">
    </div>

    <div class="hph-form-group">
        <label for="client_state"><?php esc_html_e('State', 'happyplace'); ?></label>
        <input type="text" id="client_state" name="client_state">
    </div>

    <div class="hph-form-group">
        <label for="client_zip"><?php esc_html_e('ZIP Code', 'happyplace'); ?></label>
        <input type="text" id="client_zip" name="client_zip">
    </div>

    <div class="hph-form-group">
        <label for="client_type"><?php esc_html_e('Client Type', 'happyplace'); ?> *</label>
        <select id="client_type" name="client_type" required>
            <option value=""><?php esc_html_e('Select type', 'happyplace'); ?></option>
            <option value="buyer"><?php esc_html_e('Buyer', 'happyplace'); ?></option>
            <option value="seller"><?php esc_html_e('Seller', 'happyplace'); ?></option>
            <option value="both"><?php esc_html_e('Both', 'happyplace'); ?></option>
        </select>
    </div>

    <div class="hph-form-group">
        <label for="client_notes"><?php esc_html_e('Notes', 'happyplace'); ?></label>
        <textarea id="client_notes" name="client_notes" rows="3"></textarea>
    </div>

    <div class="hph-form-group">
        <label for="client_agent"><?php esc_html_e('Assigned Agent', 'happyplace'); ?></label>
        <select id="client_agent" name="client_agent">
            <option value=""><?php esc_html_e('Select agent', 'happyplace'); ?></option>
            <?php
            $agents = get_posts([
                'post_type' => 'agent',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC'
            ]);
            
            foreach ($agents as $agent) {
                printf(
                    '<option value="%d">%s</option>',
                    esc_attr($agent->ID),
                    esc_html($agent->post_title)
                );
            }
            ?>
        </select>
    </div>

    <button type="submit" class="hph-btn hph-btn-primary"><?php esc_html_e('Submit Client', 'happyplace'); ?></button>
</form>
