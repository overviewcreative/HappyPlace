<?php

/**
 * Dashboard Listing Form Template Part
 *
 * @package HappyPlace
 */

// Get current listing data if editing
$listing_id = $_GET['listing_id'] ?? 0;
$listing_data = [];

if ($listing_id) {
    // Get existing listing data
    $listing_post = get_post($listing_id);
    if ($listing_post && $listing_post->post_author == get_current_user_id()) {
        $listing_data = [
            'ID' => $listing_post->ID,
            'title' => $listing_post->post_title,
            'content' => $listing_post->post_content,
            'status' => $listing_post->post_status,
            'featured_image' => get_post_thumbnail_id($listing_post->ID),
        ];

        // Get custom fields
        if (function_exists('get_fields')) {
            $custom_fields = get_fields($listing_post->ID);
            if (is_array($custom_fields)) {
                $listing_data = array_merge($listing_data, $custom_fields);
            }
        }
    }
}

$is_editing = !empty($listing_data['ID']);
$form_title = $is_editing ? __('Edit Listing', 'happy-place') : __('Add New Listing', 'happy-place');
?>

<div class="hph-dashboard-form-container">
    <div class="hph-section-header">
        <h2 class="hph-section-title">
            <i class="fas fa-<?php echo $is_editing ? 'edit' : 'plus'; ?>"></i>
            <?php echo esc_html($form_title); ?>
        </h2>
        <p class="hph-section-description">
            <?php echo $is_editing
                ? __('Update your listing information and details.', 'happy-place')
                : __('Create a new property listing with all the essential details.', 'happy-place'); ?>
        </p>
    </div>

    <form id="hph-listing-form" class="hph-dashboard-form" enctype="multipart/form-data">
        <?php wp_nonce_field('hph_nonce', 'nonce'); ?>
        <input type="hidden" name="action" value="save_listing">
        <input type="hidden" name="listing_id" value="<?php echo esc_attr($listing_data['ID'] ?? ''); ?>">

        <div class="hph-form-grid">
            <!-- Basic Information -->
            <div class="hph-form-section">
                <h3 class="hph-form-section-title">
                    <i class="fas fa-info-circle"></i>
                    <?php _e('Basic Information', 'happy-place'); ?>
                </h3>

                <div class="hph-form-group">
                    <label for="listing_title" class="hph-form-label">
                        <?php _e('Property Title', 'happy-place'); ?> *
                    </label>
                    <input type="text"
                        id="listing_title"
                        name="listing_title"
                        class="hph-form-input"
                        value="<?php echo esc_attr($listing_data['title'] ?? ''); ?>"
                        placeholder="<?php esc_attr_e('e.g., Beautiful 3-Bedroom Home in Downtown', 'happy-place'); ?>"
                        required>
                </div>

                <div class="hph-form-group">
                    <label for="listing_description" class="hph-form-label">
                        <?php _e('Property Description', 'happy-place'); ?>
                    </label>
                    <textarea id="listing_description"
                        name="listing_description"
                        class="hph-form-textarea"
                        rows="6"
                        placeholder="<?php esc_attr_e('Describe the property features, amenities, and highlights...', 'happy-place'); ?>"><?php echo esc_textarea($listing_data['content'] ?? ''); ?></textarea>
                </div>

                <div class="hph-form-row">
                    <div class="hph-form-group">
                        <label for="listing_type" class="hph-form-label">
                            <?php _e('Property Type', 'happy-place'); ?> *
                        </label>
                        <select id="listing_type" name="listing_type" class="hph-form-select" required>
                            <option value=""><?php _e('Select Type', 'happy-place'); ?></option>
                            <option value="house" <?php selected($listing_data['listing_type'] ?? '', 'house'); ?>>
                                <?php _e('House', 'happy-place'); ?>
                            </option>
                            <option value="apartment" <?php selected($listing_data['listing_type'] ?? '', 'apartment'); ?>>
                                <?php _e('Apartment', 'happy-place'); ?>
                            </option>
                            <option value="condo" <?php selected($listing_data['listing_type'] ?? '', 'condo'); ?>>
                                <?php _e('Condominium', 'happy-place'); ?>
                            </option>
                            <option value="townhouse" <?php selected($listing_data['listing_type'] ?? '', 'townhouse'); ?>>
                                <?php _e('Townhouse', 'happy-place'); ?>
                            </option>
                            <option value="land" <?php selected($listing_data['listing_type'] ?? '', 'land'); ?>>
                                <?php _e('Land', 'happy-place'); ?>
                            </option>
                            <option value="commercial" <?php selected($listing_data['listing_type'] ?? '', 'commercial'); ?>>
                                <?php _e('Commercial', 'happy-place'); ?>
                            </option>
                        </select>
                    </div>

                    <div class="hph-form-group">
                        <label for="listing_status" class="hph-form-label">
                            <?php _e('Listing Status', 'happy-place'); ?>
                        </label>
                        <select id="listing_status" name="listing_status" class="hph-form-select">
                            <option value="active" <?php selected($listing_data['listing_status'] ?? 'active', 'active'); ?>>
                                <?php _e('Active', 'happy-place'); ?>
                            </option>
                            <option value="pending" <?php selected($listing_data['listing_status'] ?? '', 'pending'); ?>>
                                <?php _e('Pending', 'happy-place'); ?>
                            </option>
                            <option value="sold" <?php selected($listing_data['listing_status'] ?? '', 'sold'); ?>>
                                <?php _e('Sold', 'happy-place'); ?>
                            </option>
                            <option value="draft" <?php selected($listing_data['listing_status'] ?? '', 'draft'); ?>>
                                <?php _e('Draft', 'happy-place'); ?>
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Pricing Information -->
            <div class="hph-form-section">
                <h3 class="hph-form-section-title">
                    <i class="fas fa-dollar-sign"></i>
                    <?php _e('Pricing Information', 'happy-place'); ?>
                </h3>

                <div class="hph-form-row">
                    <div class="hph-form-group">
                        <label for="listing_price" class="hph-form-label">
                            <?php _e('Price ($)', 'happy-place'); ?> *
                        </label>
                        <input type="number"
                            id="listing_price"
                            name="listing_price"
                            class="hph-form-input"
                            value="<?php echo esc_attr($listing_data['listing_price'] ?? ''); ?>"
                            placeholder="450000"
                            min="0"
                            step="1000"
                            required>
                    </div>

                    <div class="hph-form-group">
                        <label for="price_type" class="hph-form-label">
                            <?php _e('Price Type', 'happy-place'); ?>
                        </label>
                        <select id="price_type" name="price_type" class="hph-form-select">
                            <option value="sale" <?php selected($listing_data['price_type'] ?? 'sale', 'sale'); ?>>
                                <?php _e('For Sale', 'happy-place'); ?>
                            </option>
                            <option value="rent" <?php selected($listing_data['price_type'] ?? '', 'rent'); ?>>
                                <?php _e('For Rent', 'happy-place'); ?>
                            </option>
                        </select>
                    </div>
                </div>

                <div class="hph-form-row">
                    <div class="hph-form-group">
                        <label for="hoa_fees" class="hph-form-label">
                            <?php _e('HOA Fees ($/month)', 'happy-place'); ?>
                        </label>
                        <input type="number"
                            id="hoa_fees"
                            name="hoa_fees"
                            class="hph-form-input"
                            value="<?php echo esc_attr($listing_data['hoa_fees'] ?? ''); ?>"
                            placeholder="250"
                            min="0">
                    </div>

                    <div class="hph-form-group">
                        <label for="property_taxes" class="hph-form-label">
                            <?php _e('Property Taxes ($/year)', 'happy-place'); ?>
                        </label>
                        <input type="number"
                            id="property_taxes"
                            name="property_taxes"
                            class="hph-form-input"
                            value="<?php echo esc_attr($listing_data['property_taxes'] ?? ''); ?>"
                            placeholder="8500"
                            min="0">
                    </div>
                </div>
            </div>

            <!-- Property Details -->
            <div class="hph-form-section">
                <h3 class="hph-form-section-title">
                    <i class="fas fa-home"></i>
                    <?php _e('Property Details', 'happy-place'); ?>
                </h3>

                <div class="hph-form-row">
                    <div class="hph-form-group">
                        <label for="bedrooms" class="hph-form-label">
                            <?php _e('Bedrooms', 'happy-place'); ?>
                        </label>
                        <select id="bedrooms" name="bedrooms" class="hph-form-select">
                            <option value=""><?php _e('Select', 'happy-place'); ?></option>
                            <?php for ($i = 0; $i <= 10; $i++) : ?>
                                <option value="<?php echo $i; ?>" <?php selected($listing_data['bedrooms'] ?? '', $i); ?>>
                                    <?php echo $i == 0 ? __('Studio', 'happy-place') : $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="hph-form-group">
                        <label for="bathrooms" class="hph-form-label">
                            <?php _e('Bathrooms', 'happy-place'); ?>
                        </label>
                        <select id="bathrooms" name="bathrooms" class="hph-form-select">
                            <option value=""><?php _e('Select', 'happy-place'); ?></option>
                            <?php for ($i = 1; $i <= 10; $i += 0.5) : ?>
                                <option value="<?php echo $i; ?>" <?php selected($listing_data['bathrooms'] ?? '', $i); ?>>
                                    <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="hph-form-group">
                        <label for="square_feet" class="hph-form-label">
                            <?php _e('Square Feet', 'happy-place'); ?>
                        </label>
                        <input type="number"
                            id="square_feet"
                            name="square_feet"
                            class="hph-form-input"
                            value="<?php echo esc_attr($listing_data['square_feet'] ?? ''); ?>"
                            placeholder="2500"
                            min="0">
                    </div>
                </div>

                <div class="hph-form-row">
                    <div class="hph-form-group">
                        <label for="lot_size" class="hph-form-label">
                            <?php _e('Lot Size (acres)', 'happy-place'); ?>
                        </label>
                        <input type="number"
                            id="lot_size"
                            name="lot_size"
                            class="hph-form-input"
                            value="<?php echo esc_attr($listing_data['lot_size'] ?? ''); ?>"
                            placeholder="0.25"
                            step="0.01"
                            min="0">
                    </div>

                    <div class="hph-form-group">
                        <label for="year_built" class="hph-form-label">
                            <?php _e('Year Built', 'happy-place'); ?>
                        </label>
                        <input type="number"
                            id="year_built"
                            name="year_built"
                            class="hph-form-input"
                            value="<?php echo esc_attr($listing_data['year_built'] ?? ''); ?>"
                            placeholder="2020"
                            min="1800"
                            max="<?php echo date('Y') + 5; ?>">
                    </div>

                    <div class="hph-form-group">
                        <label for="garage_spaces" class="hph-form-label">
                            <?php _e('Garage Spaces', 'happy-place'); ?>
                        </label>
                        <select id="garage_spaces" name="garage_spaces" class="hph-form-select">
                            <option value=""><?php _e('Select', 'happy-place'); ?></option>
                            <?php for ($i = 0; $i <= 5; $i++) : ?>
                                <option value="<?php echo $i; ?>" <?php selected($listing_data['garage_spaces'] ?? '', $i); ?>>
                                    <?php echo $i == 0 ? __('No Garage', 'happy-place') : $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Location Information -->
            <div class="hph-form-section">
                <h3 class="hph-form-section-title">
                    <i class="fas fa-map-marker-alt"></i>
                    <?php _e('Location Information', 'happy-place'); ?>
                </h3>

                <div class="hph-form-group">
                    <label for="property_address" class="hph-form-label">
                        <?php _e('Street Address', 'happy-place'); ?> *
                    </label>
                    <input type="text"
                        id="property_address"
                        name="property_address"
                        class="hph-form-input"
                        value="<?php echo esc_attr($listing_data['property_address'] ?? ''); ?>"
                        placeholder="<?php esc_attr_e('123 Main Street', 'happy-place'); ?>"
                        required>
                </div>

                <div class="hph-form-row">
                    <div class="hph-form-group">
                        <label for="property_city" class="hph-form-label">
                            <?php _e('City', 'happy-place'); ?> *
                        </label>
                        <input type="text"
                            id="property_city"
                            name="property_city"
                            class="hph-form-input"
                            value="<?php echo esc_attr($listing_data['property_city'] ?? ''); ?>"
                            placeholder="<?php esc_attr_e('City Name', 'happy-place'); ?>"
                            required>
                    </div>

                    <div class="hph-form-group">
                        <label for="property_state" class="hph-form-label">
                            <?php _e('State', 'happy-place'); ?> *
                        </label>
                        <input type="text"
                            id="property_state"
                            name="property_state"
                            class="hph-form-input"
                            value="<?php echo esc_attr($listing_data['property_state'] ?? ''); ?>"
                            placeholder="<?php esc_attr_e('State', 'happy-place'); ?>"
                            required>
                    </div>

                    <div class="hph-form-group">
                        <label for="property_zip" class="hph-form-label">
                            <?php _e('ZIP Code', 'happy-place'); ?> *
                        </label>
                        <input type="text"
                            id="property_zip"
                            name="property_zip"
                            class="hph-form-input"
                            value="<?php echo esc_attr($listing_data['property_zip'] ?? ''); ?>"
                            placeholder="12345"
                            required>
                    </div>
                </div>

                <div class="hph-form-group">
                    <label for="property_neighborhood" class="hph-form-label">
                        <?php _e('Neighborhood', 'happy-place'); ?>
                    </label>
                    <input type="text"
                        id="property_neighborhood"
                        name="property_neighborhood"
                        class="hph-form-input"
                        value="<?php echo esc_attr($listing_data['property_neighborhood'] ?? ''); ?>"
                        placeholder="<?php esc_attr_e('Neighborhood Name', 'happy-place'); ?>">
                </div>
            </div>

            <!-- Property Images -->
            <div class="hph-form-section">
                <h3 class="hph-form-section-title">
                    <i class="fas fa-images"></i>
                    <?php _e('Property Images', 'happy-place'); ?>
                </h3>

                <div class="hph-form-group">
                    <label for="featured_image" class="hph-form-label">
                        <?php _e('Featured Image', 'happy-place'); ?>
                    </label>
                    <div class="hph-file-upload" id="featured-image-upload">
                        <div class="hph-file-upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="hph-file-upload-text">
                            <?php _e('Click to upload featured image', 'happy-place'); ?>
                        </div>
                        <div class="hph-file-upload-hint">
                            <?php _e('Recommended size: 1200x800px', 'happy-place'); ?>
                        </div>
                        <input type="file"
                            id="featured_image"
                            name="featured_image"
                            accept="image/*"
                            style="display: none;">
                    </div>
                    <?php if (!empty($listing_data['featured_image'])) : ?>
                        <div class="hph-current-image">
                            <?php echo wp_get_attachment_image($listing_data['featured_image'], 'medium'); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="hph-form-group">
                    <label for="gallery_images" class="hph-form-label">
                        <?php _e('Gallery Images', 'happy-place'); ?>
                    </label>
                    <div class="hph-file-upload" id="gallery-images-upload">
                        <div class="hph-file-upload-icon">
                            <i class="fas fa-images"></i>
                        </div>
                        <div class="hph-file-upload-text">
                            <?php _e('Click to upload gallery images', 'happy-place'); ?>
                        </div>
                        <div class="hph-file-upload-hint">
                            <?php _e('You can select multiple images', 'happy-place'); ?>
                        </div>
                        <input type="file"
                            id="gallery_images"
                            name="gallery_images[]"
                            accept="image/*"
                            multiple
                            style="display: none;">
                    </div>
                    <div id="gallery-preview" class="hph-gallery-preview"></div>
                </div>
            </div>

            <!-- Property Features -->
            <div class="hph-form-section">
                <h3 class="hph-form-section-title">
                    <i class="fas fa-star"></i>
                    <?php _e('Property Features', 'happy-place'); ?>
                </h3>

                <div class="hph-form-group">
                    <label class="hph-form-label">
                        <?php _e('Select Features', 'happy-place'); ?>
                    </label>
                    <div class="hph-feature-grid">
                        <?php
                        $features = [
                            'air_conditioning' => __('Air Conditioning', 'happy-place'),
                            'heating' => __('Heating', 'happy-place'),
                            'fireplace' => __('Fireplace', 'happy-place'),
                            'pool' => __('Swimming Pool', 'happy-place'),
                            'hot_tub' => __('Hot Tub', 'happy-place'),
                            'deck_patio' => __('Deck/Patio', 'happy-place'),
                            'balcony' => __('Balcony', 'happy-place'),
                            'garden' => __('Garden', 'happy-place'),
                            'gym' => __('Gym/Fitness Center', 'happy-place'),
                            'laundry' => __('Laundry Room', 'happy-place'),
                            'walk_in_closet' => __('Walk-in Closet', 'happy-place'),
                            'hardwood_floors' => __('Hardwood Floors', 'happy-place'),
                            'security_system' => __('Security System', 'happy-place'),
                            'garage' => __('Garage', 'happy-place'),
                            'pet_friendly' => __('Pet Friendly', 'happy-place'),
                        ];

                        $selected_features = $listing_data['property_features'] ?? [];
                        if (is_string($selected_features)) {
                            $selected_features = explode(',', $selected_features);
                        }

                        foreach ($features as $key => $label) :
                        ?>
                            <label class="hph-feature-checkbox">
                                <input type="checkbox"
                                    name="property_features[]"
                                    value="<?php echo esc_attr($key); ?>"
                                    <?php checked(in_array($key, (array)$selected_features)); ?>>
                                <span class="hph-feature-label"><?php echo esc_html($label); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="hph-form-actions">
            <button type="button" class="hph-btn hph-btn-secondary" onclick="history.back();">
                <i class="fas fa-arrow-left"></i>
                <?php _e('Cancel', 'happy-place'); ?>
            </button>

            <button type="submit" class="hph-btn hph-btn-primary">
                <i class="fas fa-save"></i>
                <?php echo $is_editing ? __('Update Listing', 'happy-place') : __('Create Listing', 'happy-place'); ?>
            </button>
        </div>
    </form>
</div>

<script>
    jQuery(document).ready(function($) {
        // Handle file uploads
        $('#featured-image-upload').on('click', function() {
            $('#featured_image').click();
        });

        $('#gallery-images-upload').on('click', function() {
            $('#gallery_images').click();
        });

        // Preview featured image
        $('#featured_image').on('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = $('<div class="hph-image-preview"><img src="' + e.target.result + '" alt="Preview"><button type="button" class="hph-remove-image"><i class="fas fa-times"></i></button></div>');
                    $('#featured-image-upload').after(preview);
                };
                reader.readAsDataURL(file);
            }
        });

        // Preview gallery images
        $('#gallery_images').on('change', function() {
            const files = this.files;
            $('#gallery-preview').empty();

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = $('<div class="hph-gallery-item"><img src="' + e.target.result + '" alt="Gallery"><button type="button" class="hph-remove-image"><i class="fas fa-times"></i></button></div>');
                    $('#gallery-preview').append(preview);
                };
                reader.readAsDataURL(file);
            }
        });

        // Remove image previews
        $(document).on('click', '.hph-remove-image', function() {
            $(this).parent().remove();
        });

        // Handle form submission
        $('#hph-listing-form').on('submit', function(e) {
            e.preventDefault();

            // Use centralized dashboard form handler if available
            if (window.HphDashboard && window.HphDashboard.handleFormSubmit) {
                window.HphDashboard.handleFormSubmit(this);
                return;
            }

            // Fallback form submission
            const formData = new FormData(this);

            // Show loading state
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.html();
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> <?php _e("Saving...", "happy-place"); ?>').prop('disabled', true);

            $.ajax({
                url: '<?php echo admin_url("admin-ajax.php"); ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        if (window.HphDashboard && window.HphDashboard.showToast) {
                            window.HphDashboard.showToast(response.data.message || '<?php _e("Listing saved successfully!", "happy-place"); ?>', 'success');
                        } else {
                            alert(response.data.message || '<?php _e("Listing saved successfully!", "happy-place"); ?>');
                        }

                        // Redirect if needed
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        } else {
                            window.location.href = '<?php echo esc_url(remove_query_arg(["action", "listing_id"])); ?>';
                        }
                    } else {
                        if (window.HphDashboard && window.HphDashboard.showToast) {
                            window.HphDashboard.showToast(response.data.message || '<?php _e("Error saving listing.", "happy-place"); ?>', 'error');
                        } else {
                            alert(response.data.message || '<?php _e("An error occurred while saving.", "happy-place"); ?>');
                        }
                    }
                },
                error: function() {
                    if (window.HphDashboard && window.HphDashboard.showToast) {
                        window.HphDashboard.showToast('<?php _e("Network error. Please try again.", "happy-place"); ?>', 'error');
                    } else {
                        alert('<?php _e("An error occurred while saving.", "happy-place"); ?>');
                    }
                },
                complete: function() {
                    submitBtn.html(originalText).prop('disabled', false);
                }
            });
        });
    });
</script>