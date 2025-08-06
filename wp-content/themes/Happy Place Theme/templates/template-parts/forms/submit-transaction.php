<?php
/**
 * Template part for transaction submission form
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="submit-transaction-form">
    <?php wp_nonce_field('submit_transaction_action', 'submit_transaction_nonce'); ?>
    <input type="hidden" name="action" value="submit_transaction">

    <div class="hph-form-group">
        <label for="transaction_type"><?php esc_html_e('Transaction Type', 'happyplace'); ?> *</label>
        <select id="transaction_type" name="transaction_type" required>
            <option value=""><?php esc_html_e('Select type', 'happyplace'); ?></option>
            <option value="sale"><?php esc_html_e('Sale', 'happyplace'); ?></option>
            <option value="purchase"><?php esc_html_e('Purchase', 'happyplace'); ?></option>
            <option value="lease"><?php esc_html_e('Lease', 'happyplace'); ?></option>
        </select>
    </div>

    <div class="hph-form-group">
        <label for="transaction_property"><?php esc_html_e('Property', 'happyplace'); ?> *</label>
        <select id="transaction_property" name="transaction_property" required>
            <option value=""><?php esc_html_e('Select property', 'happyplace'); ?></option>
            <?php
            $listings = get_posts([
                'post_type' => 'listing',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC'
            ]);
            
            foreach ($listings as $listing) {
                printf(
                    '<option value="%d">%s</option>',
                    esc_attr($listing->ID),
                    esc_html($listing->post_title)
                );
            }
            ?>
        </select>
    </div>

    <div class="hph-form-group">
        <label for="transaction_client"><?php esc_html_e('Client', 'happyplace'); ?> *</label>
        <select id="transaction_client" name="transaction_client" required>
            <option value=""><?php esc_html_e('Select client', 'happyplace'); ?></option>
            <?php
            $clients = get_posts([
                'post_type' => 'client',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC'
            ]);
            
            foreach ($clients as $client) {
                printf(
                    '<option value="%d">%s</option>',
                    esc_attr($client->ID),
                    esc_html($client->post_title)
                );
            }
            ?>
        </select>
    </div>

    <div class="hph-form-group">
        <label for="transaction_agent"><?php esc_html_e('Agent', 'happyplace'); ?> *</label>
        <select id="transaction_agent" name="transaction_agent" required>
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

    <div class="hph-form-group">
        <label for="transaction_amount"><?php esc_html_e('Amount', 'happyplace'); ?> *</label>
        <input type="number" id="transaction_amount" name="transaction_amount" step="0.01" required>
    </div>

    <div class="hph-form-group">
        <label for="transaction_date"><?php esc_html_e('Date', 'happyplace'); ?> *</label>
        <input type="date" id="transaction_date" name="transaction_date" required>
    </div>

    <div class="hph-form-group">
        <label for="transaction_status"><?php esc_html_e('Status', 'happyplace'); ?> *</label>
        <select id="transaction_status" name="transaction_status" required>
            <option value=""><?php esc_html_e('Select status', 'happyplace'); ?></option>
            <option value="pending"><?php esc_html_e('Pending', 'happyplace'); ?></option>
            <option value="in_progress"><?php esc_html_e('In Progress', 'happyplace'); ?></option>
            <option value="completed"><?php esc_html_e('Completed', 'happyplace'); ?></option>
            <option value="cancelled"><?php esc_html_e('Cancelled', 'happyplace'); ?></option>
        </select>
    </div>

    <div class="hph-form-group">
        <label for="transaction_notes"><?php esc_html_e('Notes', 'happyplace'); ?></label>
        <textarea id="transaction_notes" name="transaction_notes" rows="3"></textarea>
    </div>

    <button type="submit" class="hph-btn hph-btn-primary"><?php esc_html_e('Submit Transaction', 'happyplace'); ?></button>
</form>
