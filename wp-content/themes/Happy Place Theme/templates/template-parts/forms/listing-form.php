<?php
/**
 * Listing Form Template Part
 * 
 * @package HappyPlace
 */

$listing_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$listing = $listing_id ? get_post($listing_id) : null;
$listing_data = $listing ? get_fields($listing_id) : [];
?>

<form id="hph-listing-form" class="hph-form" data-listing-id="<?php echo esc_attr($listing_id); ?>">
    <?php wp_nonce_field('hph_listing_form', 'hph_listing_nonce'); ?>
    
    <div class="hph-form-grid">
        <!-- Basic Information -->
        <div class="hph-form-section">
            <h3>Basic Information</h3>
            
            <div class="hph-form-row">
                <label for="listing_status">Status</label>
                <select name="listing_status" id="listing_status" required>
                    <option value="draft">Draft</option>
                    <option value="coming-soon">Coming Soon</option>
                    <option value="active">Active</option>
                    <option value="pending">Pending</option>
                    <option value="sold">Sold</option>
                </select>
            </div>

            <div class="hph-form-row">
                <label for="listing_price">Price</label>
                <input type="number" name="listing_price" id="listing_price" 
                    value="<?php echo esc_attr($listing_data['price'] ?? ''); ?>" required>
            </div>

            <div class="hph-form-row">
                <label for="listing_address">Address</label>
                <input type="text" name="listing_address" id="listing_address" 
                    value="<?php echo esc_attr($listing_data['address'] ?? ''); ?>" required>
            </div>
        </div>

        <!-- Property Details -->
        <div class="hph-form-section">
            <h3>Property Details</h3>
            
            <div class="hph-form-row hph-form-row--columns">
                <div>
                    <label for="listing_beds">Bedrooms</label>
                    <input type="number" name="listing_beds" id="listing_beds" 
                        value="<?php echo esc_attr($listing_data['bedrooms'] ?? ''); ?>" min="0">
                </div>
                <div>
                    <label for="listing_baths">Bathrooms</label>
                    <input type="number" name="listing_baths" id="listing_baths" 
                        value="<?php echo esc_attr($listing_data['bathrooms'] ?? ''); ?>" min="0" step="0.5">
                </div>
                <div>
                    <label for="listing_sqft">Square Feet</label>
                    <input type="number" name="listing_sqft" id="listing_sqft" 
                        value="<?php echo esc_attr($listing_data['square_feet'] ?? ''); ?>" min="0">
                </div>
            </div>

            <div class="hph-form-row">
                <label for="listing_type">Property Type</label>
                <select name="listing_type" id="listing_type">
                    <option value="single-family">Single Family</option>
                    <option value="condo">Condo</option>
                    <option value="townhouse">Townhouse</option>
                    <option value="multi-family">Multi-Family</option>
                    <option value="land">Land</option>
                </select>
            </div>
        </div>

        <!-- Features & Amenities -->
        <div class="hph-form-section">
            <h3>Features & Amenities</h3>
            
            <div class="hph-form-row">
                <label for="listing_features">Features</label>
                <div class="hph-features-tags" id="listing_features_container">
                    <?php
                    $features = $listing_data['features'] ?? [];
                    foreach ($features as $feature) :
                    ?>
                        <span class="hph-feature-tag">
                            <?php echo esc_html($feature); ?>
                            <button type="button" class="hph-remove-feature">&times;</button>
                        </span>
                    <?php endforeach; ?>
                </div>
                <input type="text" id="listing_features_input" placeholder="Type a feature and press Enter">
                <input type="hidden" name="listing_features" id="listing_features" 
                    value="<?php echo esc_attr(json_encode($features)); ?>">
            </div>
        </div>

        <!-- Photos & Media -->
        <div class="hph-form-section">
            <h3>Photos & Media</h3>
            
            <div class="hph-form-row">
                <label>Property Photos</label>
                <div class="hph-media-uploader" id="listing_photos_uploader">
                    <div class="hph-media-preview" id="listing_photos_preview">
                        <?php
                        $photos = $listing_data['photos'] ?? [];
                        foreach ($photos as $photo) :
                            $img_url = wp_get_attachment_image_url($photo, 'thumbnail');
                        ?>
                            <div class="hph-media-item" data-id="<?php echo esc_attr($photo); ?>">
                                <img src="<?php echo esc_url($img_url); ?>" alt="">
                                <button type="button" class="hph-remove-media">&times;</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="hph-button hph-button--secondary" id="add_listing_photos">
                        <i class="fas fa-plus"></i> Add Photos
                    </button>
                </div>
                <input type="hidden" name="listing_photos" id="listing_photos" 
                    value="<?php echo esc_attr(json_encode($photos)); ?>">
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="hph-form-actions">
        <button type="button" class="hph-button hph-button--secondary" data-action="save-draft">
            Save as Draft
        </button>
        <button type="submit" class="hph-button hph-button--primary">
            <?php echo $listing_id ? 'Update Listing' : 'Publish Listing'; ?>
        </button>
    </div>
</form>
