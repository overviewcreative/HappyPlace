<?php
/**
 * Template part for agent submission form
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="submit-agent-form" enctype="multipart/form-data">
    <?php wp_nonce_field('submit_agent_action', 'submit_agent_nonce'); ?>
    <input type="hidden" name="action" value="submit_agent">

    <div class="hph-form-group">
        <label for="agent_name"><?php esc_html_e('Full Name', 'happyplace'); ?> *</label>
        <input type="text" id="agent_name" name="agent_name" required>
    </div>

    <div class="hph-form-group">
        <label for="agent_email"><?php esc_html_e('Email', 'happyplace'); ?> *</label>
        <input type="email" id="agent_email" name="agent_email" required>
    </div>

    <div class="hph-form-group">
        <label for="agent_phone"><?php esc_html_e('Phone', 'happyplace'); ?> *</label>
        <input type="tel" id="agent_phone" name="agent_phone" required>
    </div>

    <div class="hph-form-group">
        <label for="agent_title"><?php esc_html_e('Title/Position', 'happyplace'); ?></label>
        <input type="text" id="agent_title" name="agent_title">
    </div>

    <div class="hph-form-group">
        <label for="agent_bio"><?php esc_html_e('Biography', 'happyplace'); ?></label>
        <textarea id="agent_bio" name="agent_bio" rows="5"></textarea>
    </div>

    <div class="hph-form-group">
        <label for="agent_photo"><?php esc_html_e('Profile Photo', 'happyplace'); ?></label>
        <input type="file" id="agent_photo" name="agent_photo" accept="image/*">
    </div>

    <div class="hph-form-group">
        <label for="agent_social_facebook"><?php esc_html_e('Facebook Profile', 'happyplace'); ?></label>
        <input type="url" id="agent_social_facebook" name="agent_social_facebook">
    </div>

    <div class="hph-form-group">
        <label for="agent_social_linkedin"><?php esc_html_e('LinkedIn Profile', 'happyplace'); ?></label>
        <input type="url" id="agent_social_linkedin" name="agent_social_linkedin">
    </div>

    <button type="submit" class="hph-btn hph-btn-primary"><?php esc_html_e('Submit Agent', 'happyplace'); ?></button>
</form>
