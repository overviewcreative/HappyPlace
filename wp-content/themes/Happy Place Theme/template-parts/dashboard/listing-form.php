<?php
/**
 * Listing Form Template
 * 
 * Modal form for creating and editing listings
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$listing_id = isset($_GET['listing_id']) ? intval($_GET['listing_id']) : 0;
$is_editing = $listing_id > 0;

// If editing, load existing data
$listing_data = [];
if ($is_editing) {
    $listing_post = get_post($listing_id);
    if ($listing_post && $listing_post->post_author == $current_user->ID) {
        $listing_data = [
            'title' => $listing_post->post_title,
            'description' => $listing_post->post_content,
            'status' => $listing_post->post_status,
            'price' => get_post_meta($listing_id, '_listing_price', true),
            'bedrooms' => get_post_meta($listing_id, '_listing_bedrooms', true),
            'bathrooms' => get_post_meta($listing_id, '_listing_bathrooms', true),
            'square_feet' => get_post_meta($listing_id, '_listing_square_feet', true),
            'lot_size' => get_post_meta($listing_id, '_listing_lot_size', true),
            'property_type' => get_post_meta($listing_id, '_listing_property_type', true),
            'listing_type' => get_post_meta($listing_id, '_listing_type', true),
            'street_address' => get_post_meta($listing_id, '_listing_street_address', true),
            'city' => get_post_meta($listing_id, '_listing_city', true),
            'state' => get_post_meta($listing_id, '_listing_state', true),
            'zip_code' => get_post_meta($listing_id, '_listing_zip_code', true),
            'year_built' => get_post_meta($listing_id, '_listing_year_built', true),
            'garage_spaces' => get_post_meta($listing_id, '_listing_garage_spaces', true),
            'features' => get_post_meta($listing_id, '_listing_features', true),
            'virtual_tour_url' => get_post_meta($listing_id, '_listing_virtual_tour_url', true),
            'listing_agent_notes' => get_post_meta($listing_id, '_listing_agent_notes', true),
        ];
    } else {
        $is_editing = false;
        $listing_id = 0;
    }
}
?>

<!-- Listing Form Modal -->
<div id="hph-listing-form-modal" class="hph-dashboard-modal hph-dashboard-modal--large hph-dashboard-modal--hidden">
    <div class="hph-dashboard-modal-content">
        <div class="hph-dashboard-modal-header">
            <h3>
                <?php if ($is_editing): ?>
                    <i class="fas fa-edit"></i>
                    <?php esc_html_e('Edit Listing', 'happy-place'); ?>
                <?php else: ?>
                    <i class="fas fa-plus"></i>
                    <?php esc_html_e('Add New Listing', 'happy-place'); ?>
                <?php endif; ?>
            </h3>
            <button type="button" class="hph-dashboard-modal-close" onclick="HphListingForm.closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="hph-dashboard-modal-body">
            <form id="hph-listing-form" class="hph-listing-form" enctype="multipart/form-data">
                
                <!-- Form Tabs -->
                <div class="hph-form-tabs">
                    <nav class="hph-form-tabs-nav">
                        <button type="button" class="hph-form-tab-btn hph-form-tab-btn--active" data-tab="basic">
                            <i class="fas fa-home"></i>
                            <span><?php esc_html_e('Basic Details', 'happy-place'); ?></span>
                        </button>
                        <button type="button" class="hph-form-tab-btn" data-tab="location">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php esc_html_e('Location', 'happy-place'); ?></span>
                        </button>
                        <button type="button" class="hph-form-tab-btn" data-tab="features">
                            <i class="fas fa-list"></i>
                            <span><?php esc_html_e('Features', 'happy-place'); ?></span>
                        </button>
                        <button type="button" class="hph-form-tab-btn" data-tab="media">
                            <i class="fas fa-images"></i>
                            <span><?php esc_html_e('Photos & Media', 'happy-place'); ?></span>
                        </button>
                        <button type="button" class="hph-form-tab-btn" data-tab="marketing">
                            <i class="fas fa-palette"></i>
                            <span><?php esc_html_e('Marketing', 'happy-place'); ?></span>
                        </button>
                    </nav>
                    
                    <!-- Basic Details Tab -->
                    <div class="hph-form-tab-content hph-form-tab-content--active" id="tab-basic">
                        <div class="hph-form-section">
                            <h4 class="hph-form-section-title">
                                <i class="fas fa-info-circle"></i>
                                <?php esc_html_e('Property Information', 'happy-place'); ?>
                            </h4>
                            
                            <div class="hph-form-grid">
                                <div class="hph-form-group hph-form-group--full">
                                    <label for="listing-title" class="hph-form-label hph-form-label--required">
                                        <?php esc_html_e('Listing Title', 'happy-place'); ?>
                                    </label>
                                    <input type="text" id="listing-title" name="listing_title" 
                                           class="hph-form-input" required
                                           value="<?php echo esc_attr($listing_data['title'] ?? ''); ?>"
                                           placeholder="<?php esc_attr_e('e.g., Beautiful 3BR Home in Downtown', 'happy-place'); ?>">
                                </div>
                                
                                <div class="hph-form-group">
                                    <label for="listing-price" class="hph-form-label hph-form-label--required">
                                        <?php esc_html_e('Price ($)', 'happy-place'); ?>
                                    </label>
                                    <input type="number" id="listing-price" name="listing_price" 
                                           class="hph-form-input" required min="0" step="1000"
                                           value="<?php echo esc_attr($listing_data['price'] ?? ''); ?>"
                                           placeholder="350000">
                                </div>
                                
                                <div class="hph-form-group">
                                    <label for="listing-type" class="hph-form-label hph-form-label--required">
                                        <?php esc_html_e('Listing Type', 'happy-place'); ?>
                                    </label>
                                    <select id="listing-type" name="listing_type" class="hph-form-select" required>
                                        <option value=""><?php esc_html_e('Select Type', 'happy-place'); ?></option>
                                        <option value="sale" <?php selected($listing_data['listing_type'] ?? '', 'sale'); ?>>
                                            <?php esc_html_e('For Sale', 'happy-place'); ?>
                                        </option>
                                        <option value="rent" <?php selected($listing_data['listing_type'] ?? '', 'rent'); ?>>
                                            <?php esc_html_e('For Rent', 'happy-place'); ?>
                                        </option>
                                        <option value="sold" <?php selected($listing_data['listing_type'] ?? '', 'sold'); ?>>
                                            <?php esc_html_e('Sold', 'happy-place'); ?>
                                        </option>
                                        <option value="rented" <?php selected($listing_data['listing_type'] ?? '', 'rented'); ?>>
                                            <?php esc_html_e('Rented', 'happy-place'); ?>
                                        </option>
                                    </select>
                                </div>
                                
                                <div class="hph-form-group">
                                    <label for="property-type" class="hph-form-label hph-form-label--required">
                                        <?php esc_html_e('Property Type', 'happy-place'); ?>
                                    </label>
                                    <select id="property-type" name="property_type" class="hph-form-select" required>
                                        <option value=""><?php esc_html_e('Select Type', 'happy-place'); ?></option>
                                        <option value="single-family" <?php selected($listing_data['property_type'] ?? '', 'single-family'); ?>>
                                            <?php esc_html_e('Single Family Home', 'happy-place'); ?>
                                        </option>
                                        <option value="condo" <?php selected($listing_data['property_type'] ?? '', 'condo'); ?>>
                                            <?php esc_html_e('Condominium', 'happy-place'); ?>
                                        </option>
                                        <option value="townhouse" <?php selected($listing_data['property_type'] ?? '', 'townhouse'); ?>>
                                            <?php esc_html_e('Townhouse', 'happy-place'); ?>
                                        </option>
                                        <option value="multi-family" <?php selected($listing_data['property_type'] ?? '', 'multi-family'); ?>>
                                            <?php esc_html_e('Multi-Family', 'happy-place'); ?>
                                        </option>
                                        <option value="land" <?php selected($listing_data['property_type'] ?? '', 'land'); ?>>
                                            <?php esc_html_e('Land/Lot', 'happy-place'); ?>
                                        </option>
                                        <option value="commercial" <?php selected($listing_data['property_type'] ?? '', 'commercial'); ?>>
                                            <?php esc_html_e('Commercial', 'happy-place'); ?>
                                        </option>
                                    </select>
                                </div>
                                
                                <div class="hph-form-group">
                                    <label for="bedrooms" class="hph-form-label">
                                        <?php esc_html_e('Bedrooms', 'happy-place'); ?>
                                    </label>
                                    <input type="number" id="bedrooms" name="bedrooms" 
                                           class="hph-form-input" min="0" max="20"
                                           value="<?php echo esc_attr($listing_data['bedrooms'] ?? ''); ?>">
                                </div>
                                
                                <div class="hph-form-group">
                                    <label for="bathrooms" class="hph-form-label">
                                        <?php esc_html_e('Bathrooms', 'happy-place'); ?>
                                    </label>
                                    <input type="number" id="bathrooms" name="bathrooms" 
                                           class="hph-form-input" min="0" max="20" step="0.5"
                                           value="<?php echo esc_attr($listing_data['bathrooms'] ?? ''); ?>">
                                </div>
                                
                                <div class="hph-form-group">
                                    <label for="square-feet" class="hph-form-label">
                                        <?php esc_html_e('Square Feet', 'happy-place'); ?>
                                    </label>
                                    <input type="number" id="square-feet" name="square_feet" 
                                           class="hph-form-input" min="0"
                                           value="<?php echo esc_attr($listing_data['square_feet'] ?? ''); ?>">
                                </div>
                                
                                <div class="hph-form-group">
                                    <label for="lot-size" class="hph-form-label">
                                        <?php esc_html_e('Lot Size (sq ft)', 'happy-place'); ?>
                                    </label>
                                    <input type="number" id="lot-size" name="lot_size" 
                                           class="hph-form-input" min="0"
                                           value="<?php echo esc_attr($listing_data['lot_size'] ?? ''); ?>">
                                </div>
                                
                                <div class="hph-form-group hph-form-group--full">
                                    <label for="listing-description" class="hph-form-label">
                                        <?php esc_html_e('Description', 'happy-place'); ?>
                                    </label>
                                    <textarea id="listing-description" name="listing_description" 
                                              class="hph-form-textarea" rows="6"
                                              placeholder="<?php esc_attr_e('Describe the property features, neighborhood, and unique selling points...', 'happy-place'); ?>"><?php echo esc_textarea($listing_data['description'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Location Tab -->
                    <div class="hph-form-tab-content" id="tab-location">
                        <div class="hph-form-section">
                            <h4 class="hph-form-section-title">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php esc_html_e('Property Address', 'happy-place'); ?>
                            </h4>
                            
                            <div class="hph-form-grid">
                                <div class="hph-form-group hph-form-group--full">
                                    <label for="street-address" class="hph-form-label hph-form-label--required">
                                        <?php esc_html_e('Street Address', 'happy-place'); ?>
                                    </label>
                                    <input type="text" id="street-address" name="street_address" 
                                           class="hph-form-input" required
                                           value="<?php echo esc_attr($listing_data['street_address'] ?? ''); ?>"
                                           placeholder="<?php esc_attr_e('123 Main Street', 'happy-place'); ?>">
                                </div>
                                
                                <div class="hph-form-group">
                                    <label for="city" class="hph-form-label hph-form-label--required">
                                        <?php esc_html_e('City', 'happy-place'); ?>
                                    </label>
                                    <input type="text" id="city" name="city" 
                                           class="hph-form-input" required
                                           value="<?php echo esc_attr($listing_data['city'] ?? ''); ?>"
                                           placeholder="<?php esc_attr_e('Anytown', 'happy-place'); ?>">
                                </div>
                                
                                <div class="hph-form-group">
                                    <label for="state" class="hph-form-label hph-form-label--required">
                                        <?php esc_html_e('State', 'happy-place'); ?>
                                    </label>
                                    <input type="text" id="state" name="state" 
                                           class="hph-form-input" required maxlength="2"
                                           value="<?php echo esc_attr($listing_data['state'] ?? ''); ?>"
                                           placeholder="<?php esc_attr_e('CA', 'happy-place'); ?>">
                                </div>
                                
                                <div class="hph-form-group">
                                    <label for="zip-code" class="hph-form-label hph-form-label--required">
                                        <?php esc_html_e('ZIP Code', 'happy-place'); ?>
                                    </label>
                                    <input type="text" id="zip-code" name="zip_code" 
                                           class="hph-form-input" required maxlength="10"
                                           value="<?php echo esc_attr($listing_data['zip_code'] ?? ''); ?>"
                                           placeholder="<?php esc_attr_e('12345', 'happy-place'); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Features Tab -->
                    <div class="hph-form-tab-content" id="tab-features">
                        <div class="hph-form-section">
                            <h4 class="hph-form-section-title">
                                <i class="fas fa-list"></i>
                                <?php esc_html_e('Additional Features', 'happy-place'); ?>
                            </h4>
                            
                            <div class="hph-form-grid">
                                <div class="hph-form-group">
                                    <label for="year-built" class="hph-form-label">
                                        <?php esc_html_e('Year Built', 'happy-place'); ?>
                                    </label>
                                    <input type="number" id="year-built" name="year_built" 
                                           class="hph-form-input" min="1800" max="<?php echo date('Y') + 5; ?>"
                                           value="<?php echo esc_attr($listing_data['year_built'] ?? ''); ?>">
                                </div>
                                
                                <div class="hph-form-group">
                                    <label for="garage-spaces" class="hph-form-label">
                                        <?php esc_html_e('Garage Spaces', 'happy-place'); ?>
                                    </label>
                                    <input type="number" id="garage-spaces" name="garage_spaces" 
                                           class="hph-form-input" min="0" max="10"
                                           value="<?php echo esc_attr($listing_data['garage_spaces'] ?? ''); ?>">
                                </div>
                                
                                <div class="hph-form-group hph-form-group--full">
                                    <label class="hph-form-label">
                                        <?php esc_html_e('Property Features', 'happy-place'); ?>
                                    </label>
                                    <div class="hph-checkbox-grid">
                                        <?php
                                        $current_features = $listing_data['features'] ?? [];
                                        if (!is_array($current_features)) {
                                            $current_features = explode(',', $current_features);
                                        }
                                        
                                        $feature_options = [
                                            'pool' => __('Swimming Pool', 'happy-place'),
                                            'spa' => __('Spa/Hot Tub', 'happy-place'),
                                            'fireplace' => __('Fireplace', 'happy-place'),
                                            'hardwood-floors' => __('Hardwood Floors', 'happy-place'),
                                            'granite-counters' => __('Granite Countertops', 'happy-place'),
                                            'stainless-appliances' => __('Stainless Steel Appliances', 'happy-place'),
                                            'walk-in-closet' => __('Walk-in Closet', 'happy-place'),
                                            'master-suite' => __('Master Suite', 'happy-place'),
                                            'air-conditioning' => __('Air Conditioning', 'happy-place'),
                                            'heating' => __('Central Heating', 'happy-place'),
                                            'laundry-room' => __('Laundry Room', 'happy-place'),
                                            'dishwasher' => __('Dishwasher', 'happy-place'),
                                            'microwave' => __('Microwave', 'happy-place'),
                                            'deck-patio' => __('Deck/Patio', 'happy-place'),
                                            'fenced-yard' => __('Fenced Yard', 'happy-place'),
                                            'garden' => __('Garden/Landscaping', 'happy-place'),
                                        ];
                                        
                                        foreach ($feature_options as $value => $label) :
                                        ?>
                                            <label class="hph-form-checkbox">
                                                <input type="checkbox" name="features[]" value="<?php echo esc_attr($value); ?>"
                                                       <?php checked(in_array($value, $current_features)); ?>>
                                                <span class="hph-form-checkbox-text"><?php echo esc_html($label); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div class="hph-form-group hph-form-group--full">
                                    <label for="virtual-tour-url" class="hph-form-label">
                                        <?php esc_html_e('Virtual Tour URL', 'happy-place'); ?>
                                    </label>
                                    <input type="url" id="virtual-tour-url" name="virtual_tour_url" 
                                           class="hph-form-input"
                                           value="<?php echo esc_attr($listing_data['virtual_tour_url'] ?? ''); ?>"
                                           placeholder="<?php esc_attr_e('https://example.com/virtual-tour', 'happy-place'); ?>">
                                </div>
                                
                                <div class="hph-form-group hph-form-group--full">
                                    <label for="agent-notes" class="hph-form-label">
                                        <?php esc_html_e('Agent Notes (Private)', 'happy-place'); ?>
                                    </label>
                                    <textarea id="agent-notes" name="listing_agent_notes" 
                                              class="hph-form-textarea" rows="4"
                                              placeholder="<?php esc_attr_e('Private notes about the listing, showing instructions, etc...', 'happy-place'); ?>"><?php echo esc_textarea($listing_data['listing_agent_notes'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Media Tab -->
                    <div class="hph-form-tab-content" id="tab-media">
                        <div class="hph-form-section">
                            <h4 class="hph-form-section-title">
                                <i class="fas fa-images"></i>
                                <?php esc_html_e('Photos & Media', 'happy-place'); ?>
                            </h4>
                            
                            <div class="hph-form-grid">
                                <div class="hph-form-group hph-form-group--full">
                                    <label class="hph-form-label">
                                        <?php esc_html_e('Featured Image', 'happy-place'); ?>
                                    </label>
                                    <div class="hph-media-upload" id="featured-image-upload">
                                        <div class="hph-media-upload-area" data-type="featured">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <p><?php esc_html_e('Click to upload featured image', 'happy-place'); ?></p>
                                            <input type="file" class="hph-media-input" accept="image/*" name="featured_image">
                                        </div>
                                        <div class="hph-media-preview" id="featured-preview"></div>
                                    </div>
                                </div>
                                
                                <div class="hph-form-group hph-form-group--full">
                                    <label class="hph-form-label">
                                        <?php esc_html_e('Gallery Images', 'happy-place'); ?>
                                    </label>
                                    <div class="hph-media-upload" id="gallery-upload">
                                        <div class="hph-media-upload-area" data-type="gallery">
                                            <i class="fas fa-images"></i>
                                            <p><?php esc_html_e('Click to upload multiple images', 'happy-place'); ?></p>
                                            <input type="file" class="hph-media-input" accept="image/*" multiple name="gallery_images[]">
                                        </div>
                                        <div class="hph-media-gallery" id="gallery-preview"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Marketing Tab -->
                    <div class="hph-form-tab-content" id="tab-marketing">
                        <div class="hph-form-section">
                            <h4 class="hph-form-section-title">
                                <i class="fas fa-palette"></i>
                                <?php esc_html_e('Marketing Materials', 'happy-place'); ?>
                            </h4>
                            
                            <div class="hph-marketing-tools-grid">
                                
                                <!-- Generate Flyer -->
                                <div class="hph-marketing-tool-card">
                                    <div class="hph-marketing-tool-icon">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <div class="hph-marketing-tool-content">
                                        <h5><?php esc_html_e('Property Flyer', 'happy-place'); ?></h5>
                                        <p><?php esc_html_e('Generate a professional property flyer', 'happy-place'); ?></p>
                                        <button type="button" class="action-btn action-btn--outline action-btn--sm" 
                                                onclick="HphListingForm.generateFlyer()" 
                                                id="generate-flyer-btn"
                                                <?php echo !$is_editing ? 'disabled' : ''; ?>>
                                            <i class="fas fa-magic"></i>
                                            <?php esc_html_e('Generate Flyer', 'happy-place'); ?>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Social Media Graphics -->
                                <div class="hph-marketing-tool-card">
                                    <div class="hph-marketing-tool-icon">
                                        <i class="fas fa-share-alt"></i>
                                    </div>
                                    <div class="hph-marketing-tool-content">
                                        <h5><?php esc_html_e('Social Media Graphics', 'happy-place'); ?></h5>
                                        <p><?php esc_html_e('Create social media post graphics', 'happy-place'); ?></p>
                                        <button type="button" class="action-btn action-btn--outline action-btn--sm" 
                                                onclick="HphListingForm.generateSocialMedia()" 
                                                id="generate-social-btn"
                                                <?php echo !$is_editing ? 'disabled' : ''; ?>>
                                            <i class="fas fa-hashtag"></i>
                                            <?php esc_html_e('Create Graphics', 'happy-place'); ?>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Open House Graphics -->
                                <div class="hph-marketing-tool-card">
                                    <div class="hph-marketing-tool-icon">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div class="hph-marketing-tool-content">
                                        <h5><?php esc_html_e('Open House Graphics', 'happy-place'); ?></h5>
                                        <p><?php esc_html_e('Design open house promotional materials', 'happy-place'); ?></p>
                                        <button type="button" class="action-btn action-btn--outline action-btn--sm" 
                                                onclick="HphListingForm.generateOpenHouse()" 
                                                id="generate-openhouse-btn"
                                                <?php echo !$is_editing ? 'disabled' : ''; ?>>
                                            <i class="fas fa-home"></i>
                                            <?php esc_html_e('Create Graphics', 'happy-place'); ?>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Virtual Tour Integration -->
                                <div class="hph-marketing-tool-card">
                                    <div class="hph-marketing-tool-icon">
                                        <i class="fas fa-vr-cardboard"></i>
                                    </div>
                                    <div class="hph-marketing-tool-content">
                                        <h5><?php esc_html_e('Virtual Tour', 'happy-place'); ?></h5>
                                        <p><?php esc_html_e('Add or update virtual tour link', 'happy-place'); ?></p>
                                        <button type="button" class="action-btn action-btn--outline action-btn--sm" 
                                                onclick="HphListingForm.focusVirtualTour()">
                                            <i class="fas fa-link"></i>
                                            <?php esc_html_e('Add Tour Link', 'happy-place'); ?>
                                        </button>
                                    </div>
                                </div>
                                
                            </div>
                            
                            <div class="hph-marketing-note">
                                <div class="hph-alert hph-alert--info">
                                    <i class="fas fa-info-circle"></i>
                                    <span>
                                        <?php if (!$is_editing): ?>
                                            <?php esc_html_e('Marketing tools will be available after you save the listing.', 'happy-place'); ?>
                                        <?php else: ?>
                                            <?php esc_html_e('Marketing materials will use the current listing information. Save any changes first.', 'happy-place'); ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
                
                <!-- Hidden Fields -->
                <input type="hidden" name="listing_id" value="<?php echo esc_attr($listing_id); ?>">
                <input type="hidden" name="action" value="hph_save_listing">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('hph_listing_nonce'); ?>">
                
            </form>
        </div>
        
        <div class="hph-dashboard-modal-footer">
            <div class="hph-form-actions-left">
                <button type="button" class="action-btn action-btn--outline" onclick="HphListingForm.closeModal()">
                    <?php esc_html_e('Cancel', 'happy-place'); ?>
                </button>
            </div>
            
            <div class="hph-form-actions-right">
                <button type="button" class="action-btn action-btn--secondary" onclick="HphListingForm.saveDraft()">
                    <i class="fas fa-save"></i>
                    <?php esc_html_e('Save Draft', 'happy-place'); ?>
                </button>
                <button type="button" class="action-btn action-btn--primary" onclick="HphListingForm.saveAndPublish()">
                    <i class="fas fa-upload"></i>
                    <?php if ($is_editing): ?>
                        <?php esc_html_e('Update Listing', 'happy-place'); ?>
                    <?php else: ?>
                        <?php esc_html_e('Publish Listing', 'happy-place'); ?>
                    <?php endif; ?>
                </button>
            </div>
        </div>
    </div>
</div>
