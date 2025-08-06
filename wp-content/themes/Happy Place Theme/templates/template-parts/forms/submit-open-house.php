<?php
/**
 * Template part for open house submission form
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="submit-open-house-form">
    <?php wp_nonce_field('submit_open_house_action', 'submit_open_house_nonce'); ?>
    <input type="hidden" name="action" value="submit_open_house">

    <div class="hph-form-group">
        <label for="open_house_listing"><?php esc_html_e('Select Property', 'happyplace'); ?> *</label>
        <select id="open_house_listing" name="open_house_listing" required>
            <option value=""><?php esc_html_e('Choose a property', 'happyplace'); ?></option>
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
        <label for="open_house_date"><?php esc_html_e('Date', 'happyplace'); ?> *</label>
        <input type="date" id="open_house_date" name="open_house_date" required>
    </div>

    <div class="hph-form-group">
        <label for="open_house_start_time"><?php esc_html_e('Start Time', 'happyplace'); ?> *</label>
        <input type="time" id="open_house_start_time" name="open_house_start_time" required>
    </div>

    <div class="hph-form-group">
        <label for="open_house_end_time"><?php esc_html_e('End Time', 'happyplace'); ?> *</label>
        <input type="time" id="open_house_end_time" name="open_house_end_time" required>
    </div>

    <div class="hph-form-group">
        <label for="open_house_agent"><?php esc_html_e('Hosting Agent', 'happyplace'); ?> *</label>
        <select id="open_house_agent" name="open_house_agent" required>
            <option value=""><?php esc_html_e('Select an agent', 'happyplace'); ?></option>
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
        <label for="open_house_notes"><?php esc_html_e('Notes', 'happyplace'); ?></label>
        <textarea id="open_house_notes" name="open_house_notes" rows="3"></textarea>
    </div>

    <button type="submit" class="hph-btn hph-btn-primary"><?php esc_html_e('Submit Open House', 'happyplace'); ?></button>
</form>
