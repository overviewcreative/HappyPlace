<?php
/**
 * Template part for community submission form
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="submit-community-form" enctype="multipart/form-data">
    <?php wp_nonce_field('submit_community_action', 'submit_community_nonce'); ?>
    <input type="hidden" name="action" value="submit_community">

    <div class="hph-form-group">
        <label for="community_name"><?php esc_html_e('Community Name', 'happyplace'); ?> *</label>
        <input type="text" id="community_name" name="community_name" required>
    </div>

    <div class="hph-form-group">
        <label for="community_city"><?php esc_html_e('City', 'happyplace'); ?> *</label>
        <input type="text" id="community_city" name="community_city" required>
    </div>

    <div class="hph-form-group">
        <label for="community_description"><?php esc_html_e('Description', 'happyplace'); ?></label>
        <textarea id="community_description" name="community_description" rows="5"></textarea>
    </div>

    <div class="hph-form-group">
        <label for="community_amenities"><?php esc_html_e('Amenities', 'happyplace'); ?></label>
        <textarea id="community_amenities" name="community_amenities" rows="3" placeholder="<?php esc_attr_e('List community amenities, one per line', 'happyplace'); ?>"></textarea>
    </div>

    <div class="hph-form-group">
        <label for="community_featured_image"><?php esc_html_e('Featured Image', 'happyplace'); ?></label>
        <input type="file" id="community_featured_image" name="community_featured_image" accept="image/*">
    </div>

    <div class="hph-form-group">
        <label for="community_gallery"><?php esc_html_e('Image Gallery', 'happyplace'); ?></label>
        <input type="file" id="community_gallery" name="community_gallery[]" multiple accept="image/*">
    </div>

    <button type="submit" class="hph-btn hph-btn-primary"><?php esc_html_e('Submit Community', 'happyplace'); ?></button>
</form>
