<?php
/**
 * Template part for city submission form
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="submit-city-form" enctype="multipart/form-data">
    <?php wp_nonce_field('submit_city_action', 'submit_city_nonce'); ?>
    <input type="hidden" name="action" value="submit_city">

    <div class="hph-form-group">
        <label for="city_name"><?php esc_html_e('City Name', 'happyplace'); ?> *</label>
        <input type="text" id="city_name" name="city_name" required>
    </div>

    <div class="hph-form-group">
        <label for="city_state"><?php esc_html_e('State', 'happyplace'); ?> *</label>
        <input type="text" id="city_state" name="city_state" required>
    </div>

    <div class="hph-form-group">
        <label for="city_description"><?php esc_html_e('Description', 'happyplace'); ?></label>
        <textarea id="city_description" name="city_description" rows="5"></textarea>
    </div>

    <div class="hph-form-group">
        <label for="city_population"><?php esc_html_e('Population', 'happyplace'); ?></label>
        <input type="number" id="city_population" name="city_population">
    </div>

    <div class="hph-form-group">
        <label for="city_featured_image"><?php esc_html_e('Featured Image', 'happyplace'); ?></label>
        <input type="file" id="city_featured_image" name="city_featured_image" accept="image/*">
    </div>

    <button type="submit" class="hph-btn hph-btn-primary"><?php esc_html_e('Submit City', 'happyplace'); ?></button>
</form>
