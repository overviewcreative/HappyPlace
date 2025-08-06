<?php
/**
 * Template part for displaying the listing modal
 *
 * @package Happy_Place_Theme
 */
?>
<div id="listing-modal" class="dashboard-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title"><?php echo esc_html__('Add New Listing', 'happy-place'); ?></h2>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="listing-form" class="dashboard-form">
                <div class="form-group">
                    <label for="address"><?php echo esc_html__('Property Address', 'happy-place'); ?></label>
                    <input type="text" id="address" name="address" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="price"><?php echo esc_html__('Price', 'happy-place'); ?></label>
                        <input type="number" id="price" name="price" min="0" step="1000" required>
                    </div>
                    <div class="form-group">
                        <label for="status"><?php echo esc_html__('Status', 'happy-place'); ?></label>
                        <select id="status" name="status" required>
                            <option value="Coming Soon"><?php echo esc_html__('Coming Soon', 'happy-place'); ?></option>
                            <option value="Active"><?php echo esc_html__('Active', 'happy-place'); ?></option>
                            <option value="Pending"><?php echo esc_html__('Pending', 'happy-place'); ?></option>
                            <option value="Sold"><?php echo esc_html__('Sold', 'happy-place'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="bedrooms"><?php echo esc_html__('Bedrooms', 'happy-place'); ?></label>
                        <input type="number" id="bedrooms" name="bedrooms" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="bathrooms"><?php echo esc_html__('Bathrooms', 'happy-place'); ?></label>
                        <input type="number" id="bathrooms" name="bathrooms" min="0" step="0.5" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="square_footage"><?php echo esc_html__('Square Footage', 'happy-place'); ?></label>
                        <input type="number" id="square_footage" name="square_footage" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="lot_size"><?php echo esc_html__('Lot Size', 'happy-place'); ?></label>
                        <input type="text" id="lot_size" name="lot_size">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="year_built"><?php echo esc_html__('Year Built', 'happy-place'); ?></label>
                        <input type="number" id="year_built" name="year_built" min="1800" max="<?php echo date('Y'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="property_type"><?php echo esc_html__('Property Type', 'happy-place'); ?></label>
                        <select id="property_type" name="property_type" required>
                            <option value="Single Family"><?php echo esc_html__('Single Family', 'happy-place'); ?></option>
                            <option value="Condo"><?php echo esc_html__('Condo', 'happy-place'); ?></option>
                            <option value="Townhouse"><?php echo esc_html__('Townhouse', 'happy-place'); ?></option>
                            <option value="Multi-Family"><?php echo esc_html__('Multi-Family', 'happy-place'); ?></option>
                            <option value="Land"><?php echo esc_html__('Land', 'happy-place'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="description"><?php echo esc_html__('Description', 'happy-place'); ?></label>
                    <textarea id="description" name="description" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label for="main_photo"><?php echo esc_html__('Main Photo', 'happy-place'); ?></label>
                    <input type="file" id="main_photo" name="main_photo" accept="image/*">
                </div>
                <div class="form-group">
                    <label for="features"><?php echo esc_html__('Features', 'happy-place'); ?></label>
                    <input type="text" id="features" name="features" class="feature-tags" placeholder="<?php echo esc_attr__('Start typing to add features...', 'happy-place'); ?>">
                </div>
                <input type="hidden" name="id" value="">
                <?php wp_nonce_field('happy_place_listing_nonce', 'listing_nonce'); ?>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="button cancel-btn"><?php echo esc_html__('Cancel', 'happy-place'); ?></button>
            <button type="submit" form="listing-form" class="button button-primary save-btn"><?php echo esc_html__('Save Listing', 'happy-place'); ?></button>
        </div>
    </div>
</div>
