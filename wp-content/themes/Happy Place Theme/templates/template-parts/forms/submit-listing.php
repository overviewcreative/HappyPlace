<?php
/**
 * Template part for displaying the listing submission form
 */

if (!defined('ABSPATH')) exit;

// Verify nonce and user permissions
if (!is_user_logged_in()) {
    wp_die(__('You must be logged in to submit listings.', 'hph'));
}

$nonce = wp_create_nonce('hph_submit_listing');
?>

<form id="submit-listing" class="hph-form" method="post" enctype="multipart/form-data">
    <?php wp_nonce_field('hph_submit_listing', 'listing_nonce'); ?>
    
    <div class="hph-form-group">
        <label class="hph-form-label" for="title"><?php _e('Property Title', 'hph'); ?> *</label>
        <input type="text" id="title" name="title" class="hph-form-input" required>
    </div>

    <div class="hph-form-group">
        <label class="hph-form-label" for="price"><?php _e('Price', 'hph'); ?> *</label>
        <input type="number" id="price" name="price" class="hph-form-input" required>
    </div>

    <div class="hph-grid hph-grid-2">
        <div class="hph-form-group">
            <label class="hph-form-label" for="bedrooms"><?php _e('Bedrooms', 'hph'); ?> *</label>
            <input type="number" id="bedrooms" name="bedrooms" class="hph-form-input" required>
        </div>
        <div class="hph-form-group">
            <label class="hph-form-label" for="bathrooms"><?php _e('Bathrooms', 'hph'); ?> *</label>
            <input type="number" id="bathrooms" name="bathrooms" class="hph-form-input" step="0.5" required>
        </div>
    </div>

    <div class="hph-form-group">
        <label class="hph-form-label" for="description"><?php _e('Description', 'hph'); ?> *</label>
        <textarea id="description" name="description" class="hph-form-textarea" rows="5" required></textarea>
    </div>

    <div class="hph-form-group">
        <label class="hph-form-label" for="property_images"><?php _e('Property Images', 'hph'); ?> *</label>
        <input type="file" id="property_images" name="property_images[]" class="hph-form-upload" multiple accept="image/*" required>
        <small class="hph-text-muted"><?php _e('You can upload multiple images. First image will be the featured image.', 'hph'); ?></small>
    </div>

    <div class="hph-form-group">
        <label for="address"><?php _e('Address', 'hph'); ?> *</label>
        <input type="text" id="address" name="address" required>
    </div>

    <div class="hph-form-group">
        <label for="features"><?php _e('Features', 'hph'); ?></label>
        <textarea id="features" name="features" rows="3"></textarea>
        <small class="form-text text-muted"><?php _e('Enter features separated by commas', 'hph'); ?></small>
    </div>

    <button type="submit" class="button button-primary"><?php _e('Submit Listing', 'hph'); ?></button>
</form>
