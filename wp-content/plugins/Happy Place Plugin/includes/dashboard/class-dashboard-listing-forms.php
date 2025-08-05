<?php
/**
 * Dashboard Listing Forms - Comprehensive form builder for listing management
 * 
 * Provides frontend form rendering and handling for listing creation/editing
 * with full ACF integration and modern UI components.
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

namespace HappyPlace\Dashboard;

use HappyPlace\Forms\Handlers\Listing_Form_Handler;
use HappyPlace\Forms\Handlers\Open_House_Form_Handler;

if (!defined('ABSPATH')) {
    exit;
}

class Dashboard_Listing_Forms {
    
    /**
     * @var Listing_Form_Handler
     */
    private $listing_handler;
    
    /**
     * @var Open_House_Form_Handler
     */
    private $open_house_handler;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->listing_handler = new Listing_Form_Handler();
        $this->open_house_handler = new Open_House_Form_Handler();
        
        add_action('wp_ajax_hph_render_listing_form', [$this, 'ajax_render_listing_form']);
        add_action('wp_ajax_hph_submit_listing_form', [$this, 'ajax_submit_listing_form']);
        add_action('wp_ajax_hph_render_open_house_form', [$this, 'ajax_render_open_house_form']);
        add_action('wp_ajax_hph_submit_open_house_form', [$this, 'ajax_submit_open_house_form']);
        add_action('wp_ajax_hph_get_listing_form_data', [$this, 'ajax_get_listing_form_data']);
    }
    
    /**
     * AJAX: Render listing form
     */
    public function ajax_render_listing_form() {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        $listing_id = isset($_POST['listing_id']) ? absint($_POST['listing_id']) : 0;
        $form_type = sanitize_text_field($_POST['form_type'] ?? 'edit');
        
        // Check permissions
        if ($listing_id && !$this->can_edit_listing($listing_id)) {
            wp_send_json_error(__('You do not have permission to edit this listing.', 'happy-place'));
        }
        
        ob_start();
        $this->render_listing_form($listing_id, $form_type);
        $form_html = ob_get_clean();
        
        wp_send_json_success([
            'form_html' => $form_html,
            'listing_id' => $listing_id,
            'form_type' => $form_type
        ]);
    }
    
    /**
     * AJAX: Submit listing form
     */
    public function ajax_submit_listing_form() {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        $form_data = $_POST;
        $listing_id = isset($form_data['post_id']) ? absint($form_data['post_id']) : 0;
        
        // Check permissions
        if ($listing_id && !$this->can_edit_listing($listing_id)) {
            wp_send_json_error(__('You do not have permission to edit this listing.', 'happy-place'));
        }
        
        // Set current user as author for new listings
        if (!$listing_id) {
            $form_data['post_author'] = get_current_user_id();
        }
        
        // Process form submission
        $result = $this->listing_handler->handle_submission($form_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        $listing = get_post($result);
        $listing_data = $this->get_listing_display_data($result);
        
        wp_send_json_success([
            'listing_id' => $result,
            'message' => sprintf(__('Listing "%s" saved successfully.', 'happy-place'), $listing->post_title),
            'listing_data' => $listing_data,
            'redirect_url' => get_permalink($result)
        ]);
    }
    
    /**
     * AJAX: Render open house form
     */
    public function ajax_render_open_house_form() {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        $listing_id = isset($_POST['listing_id']) ? absint($_POST['listing_id']) : 0;
        $open_house_id = isset($_POST['open_house_id']) ? absint($_POST['open_house_id']) : 0;
        
        // Check permissions
        if ($listing_id && !$this->can_edit_listing($listing_id)) {
            wp_send_json_error(__('You do not have permission to schedule open houses for this listing.', 'happy-place'));
        }
        
        ob_start();
        $this->render_open_house_form($listing_id, $open_house_id);
        $form_html = ob_get_clean();
        
        wp_send_json_success([
            'form_html' => $form_html,
            'listing_id' => $listing_id,
            'open_house_id' => $open_house_id
        ]);
    }
    
    /**
     * AJAX: Submit open house form
     */
    public function ajax_submit_open_house_form() {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        $form_data = $_POST;
        $listing_id = isset($form_data['listing']) ? absint($form_data['listing']) : 0;
        
        // Check permissions
        if ($listing_id && !$this->can_edit_listing($listing_id)) {
            wp_send_json_error(__('You do not have permission to schedule open houses for this listing.', 'happy-place'));
        }
        
        // Set current user as hosting agent if not specified
        if (empty($form_data['hosting_agent'])) {
            // Get current user's agent post
            $agent_posts = get_posts([
                'post_type' => 'agent',
                'meta_key' => 'user_id',
                'meta_value' => get_current_user_id(),
                'posts_per_page' => 1
            ]);
            
            if (!empty($agent_posts)) {
                $form_data['hosting_agent'] = $agent_posts[0]->ID;
            }
        }
        
        // Process form submission
        $result = $this->open_house_handler->handle_submission($form_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        $open_house = get_post($result);
        
        wp_send_json_success([
            'open_house_id' => $result,
            'message' => sprintf(__('Open house "%s" scheduled successfully.', 'happy-place'), $open_house->post_title),
            'open_house_data' => $this->get_open_house_display_data($result)
        ]);
    }
    
    /**
     * AJAX: Get listing form data for editing
     */
    public function ajax_get_listing_form_data() {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        $listing_id = absint($_POST['listing_id']);
        
        if (!$this->can_edit_listing($listing_id)) {
            wp_send_json_error(__('You do not have permission to edit this listing.', 'happy-place'));
        }
        
        $listing_data = $this->get_listing_form_data($listing_id);
        
        wp_send_json_success($listing_data);
    }
    
    /**
     * Render comprehensive listing form
     */
    public function render_listing_form($listing_id = 0, $form_type = 'edit') {
        $listing_data = $listing_id ? $this->get_listing_form_data($listing_id) : [];
        $is_new = !$listing_id;
        
        ?>
        <div class="hph-listing-form-container">
            <form id="hph-listing-form" class="hph-form-modern" method="post">
                <?php wp_nonce_field('hph_dashboard_nonce', 'nonce'); ?>
                <input type="hidden" name="action" value="hph_submit_listing_form">
                <input type="hidden" name="post_id" value="<?php echo $listing_id; ?>">
                <input type="hidden" name="form_type" value="<?php echo esc_attr($form_type); ?>">
                
                <!-- Form Header -->
                <div class="hph-form-header">
                    <h3 class="form-title">
                        <?php echo $is_new ? __('Add New Listing', 'happy-place') : __('Edit Listing', 'happy-place'); ?>
                    </h3>
                    <p class="form-subtitle">
                        <?php echo $is_new ? __('Create a new property listing with all the details.', 'happy-place') : __('Update your property listing information.', 'happy-place'); ?>
                    </p>
                </div>
                
                <!-- Form Progress -->
                <div class="hph-form-progress">
                    <div class="progress-steps">
                        <div class="step active" data-step="1">
                            <span class="step-number">1</span>
                            <span class="step-label"><?php _e('Basic Info', 'happy-place'); ?></span>
                        </div>
                        <div class="step" data-step="2">
                            <span class="step-number">2</span>
                            <span class="step-label"><?php _e('Location', 'happy-place'); ?></span>
                        </div>
                        <div class="step" data-step="3">
                            <span class="step-number">3</span>
                            <span class="step-label"><?php _e('Features', 'happy-place'); ?></span>
                        </div>
                        <div class="step" data-step="4">
                            <span class="step-number">4</span>
                            <span class="step-label"><?php _e('Media', 'happy-place'); ?></span>
                        </div>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 25%"></div>
                    </div>
                </div>
                
                <!-- Step 1: Basic Information -->
                <div class="hph-form-step active" data-step="1">
                    <div class="step-header">
                        <h4><?php _e('Basic Information', 'happy-place'); ?></h4>
                        <p><?php _e('Enter the core details about this property.', 'happy-place'); ?></p>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required"><?php _e('Property Title', 'happy-place'); ?></label>
                            <input type="text" name="post_title" class="form-control" 
                                   value="<?php echo esc_attr($listing_data['post_title'] ?? ''); ?>" 
                                   placeholder="<?php _e('e.g., Beautiful 3BR Home in Downtown', 'happy-place'); ?>" required>
                            <div class="form-help"><?php _e('This will be the main title displayed to buyers', 'happy-place'); ?></div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required"><?php _e('MLS Number', 'happy-place'); ?></label>
                            <input type="text" name="mls_number" class="form-control" 
                                   value="<?php echo esc_attr($listing_data['mls_number'] ?? ''); ?>" 
                                   placeholder="<?php _e('MLS123456', 'happy-place'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required"><?php _e('List Price', 'happy-place'); ?></label>
                            <div class="input-group">
                                <span class="input-prefix">$</span>
                                <input type="number" name="price" class="form-control hph-trigger-calculation" 
                                       value="<?php echo esc_attr($listing_data['price'] ?? ''); ?>" 
                                       min="1000" step="1000" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php _e('Listing Status', 'happy-place'); ?></label>
                            <select name="post_status" class="form-control">
                                <option value="draft" <?php selected($listing_data['post_status'] ?? 'draft', 'draft'); ?>><?php _e('Draft', 'happy-place'); ?></option>
                                <option value="publish" <?php selected($listing_data['post_status'] ?? '', 'publish'); ?>><?php _e('Active', 'happy-place'); ?></option>
                                <option value="pending" <?php selected($listing_data['post_status'] ?? '', 'pending'); ?>><?php _e('Pending Review', 'happy-place'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required"><?php _e('Bedrooms', 'happy-place'); ?></label>
                            <input type="number" name="bedrooms" class="form-control" 
                                   value="<?php echo esc_attr($listing_data['bedrooms'] ?? ''); ?>" 
                                   min="0" max="50" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required"><?php _e('Full Bathrooms', 'happy-place'); ?></label>
                            <input type="number" name="bathrooms" class="form-control hph-trigger-calculation" 
                                   value="<?php echo esc_attr($listing_data['bathrooms'] ?? ''); ?>" 
                                   min="0" max="20" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php _e('Half Bathrooms', 'happy-place'); ?></label>
                            <input type="number" name="half_bathrooms" class="form-control hph-trigger-calculation" 
                                   value="<?php echo esc_attr($listing_data['half_bathrooms'] ?? '0'); ?>" 
                                   min="0" max="10">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php _e('Total Bathrooms', 'happy-place'); ?></label>
                            <input type="number" name="bathrooms_total" class="form-control hph-calculated-field" 
                                   value="<?php echo esc_attr($listing_data['bathrooms_total'] ?? ''); ?>" 
                                   readonly step="0.5">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required"><?php _e('Square Footage', 'happy-place'); ?></label>
                            <div class="input-group">
                                <input type="number" name="square_footage" class="form-control hph-trigger-calculation" 
                                       value="<?php echo esc_attr($listing_data['square_footage'] ?? ''); ?>" 
                                       min="200" required>
                                <span class="input-suffix">sq ft</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php _e('Year Built', 'happy-place'); ?></label>
                            <input type="number" name="year_built" class="form-control" 
                                   value="<?php echo esc_attr($listing_data['year_built'] ?? ''); ?>" 
                                   min="1800" max="<?php echo date('Y') + 2; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php _e('Price per Sq Ft', 'happy-place'); ?></label>
                            <div class="input-group">
                                <span class="input-prefix">$</span>
                                <input type="number" name="price_per_sqft" class="form-control hph-calculated-field" 
                                       value="<?php echo esc_attr($listing_data['price_per_sqft'] ?? ''); ?>" 
                                       readonly step="0.01">
                                <span class="input-suffix">/sqft</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Step 2: Location -->
                <div class="hph-form-step" data-step="2">
                    <div class="step-header">
                        <h4><?php _e('Location & Address', 'happy-place'); ?></h4>
                        <p><?php _e('Provide the property location details.', 'happy-place'); ?></p>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label class="form-label required"><?php _e('Street Address', 'happy-place'); ?></label>
                            <input type="text" name="street_address" class="form-control" 
                                   value="<?php echo esc_attr($listing_data['street_address'] ?? ''); ?>" 
                                   placeholder="<?php _e('123 Main Street', 'happy-place'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php _e('Unit/Apt', 'happy-place'); ?></label>
                            <input type="text" name="unit_number" class="form-control" 
                                   value="<?php echo esc_attr($listing_data['unit_number'] ?? ''); ?>" 
                                   placeholder="<?php _e('Unit #', 'happy-place'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required"><?php _e('City', 'happy-place'); ?></label>
                            <input type="text" name="city" class="form-control" 
                                   value="<?php echo esc_attr($listing_data['city'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required"><?php _e('State', 'happy-place'); ?></label>
                            <select name="state" class="form-control" required>
                                <option value=""><?php _e('Select State', 'happy-place'); ?></option>
                                <?php
                                $states = [
                                    'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
                                    'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
                                    'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho',
                                    'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
                                    'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
                                    'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
                                    'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
                                    'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
                                    'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
                                    'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
                                    'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
                                    'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia',
                                    'WI' => 'Wisconsin', 'WY' => 'Wyoming'
                                ];
                                foreach ($states as $code => $name) {
                                    printf('<option value="%s" %s>%s</option>', 
                                        $code, 
                                        selected($listing_data['state'] ?? '', $code, false), 
                                        $name
                                    );
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required"><?php _e('ZIP Code', 'happy-place'); ?></label>
                            <input type="text" name="zip_code" class="form-control" 
                                   value="<?php echo esc_attr($listing_data['zip_code'] ?? ''); ?>" 
                                   placeholder="<?php _e('12345', 'happy-place'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><?php _e('Address Display', 'happy-place'); ?></label>
                            <select name="address_visibility" class="form-control">
                                <option value="full" <?php selected($listing_data['address_visibility'] ?? 'full', 'full'); ?>><?php _e('Show Full Address', 'happy-place'); ?></option>
                                <option value="street_only" <?php selected($listing_data['address_visibility'] ?? '', 'street_only'); ?>><?php _e('Street Name Only', 'happy-place'); ?></option>
                                <option value="neighborhood" <?php selected($listing_data['address_visibility'] ?? '', 'neighborhood'); ?>><?php _e('Neighborhood Only', 'happy-place'); ?></option>
                                <option value="hidden" <?php selected($listing_data['address_visibility'] ?? '', 'hidden'); ?>><?php _e('Do Not Display', 'happy-place'); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php _e('Lot Size (Acres)', 'happy-place'); ?></label>
                            <div class="input-group">
                                <input type="number" name="lot_size" class="form-control" 
                                       value="<?php echo esc_attr($listing_data['lot_size'] ?? ''); ?>" 
                                       min="0" step="0.01">
                                <span class="input-suffix">acres</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Step 3: Features & Amenities -->
                <div class="hph-form-step" data-step="3">
                    <div class="step-header">
                        <h4><?php _e('Features & Amenities', 'happy-place'); ?></h4>
                        <p><?php _e('Highlight what makes this property special.', 'happy-place'); ?></p>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><?php _e('Property Type', 'happy-place'); ?></label>
                            <select name="property_type" class="form-control">
                                <option value=""><?php _e('Select Type', 'happy-place'); ?></option>
                                <option value="single_family" <?php selected($listing_data['property_type'] ?? '', 'single_family'); ?>><?php _e('Single Family', 'happy-place'); ?></option>
                                <option value="condo" <?php selected($listing_data['property_type'] ?? '', 'condo'); ?>><?php _e('Condo', 'happy-place'); ?></option>
                                <option value="townhouse" <?php selected($listing_data['property_type'] ?? '', 'townhouse'); ?>><?php _e('Townhouse', 'happy-place'); ?></option>
                                <option value="multi_family" <?php selected($listing_data['property_type'] ?? '', 'multi_family'); ?>><?php _e('Multi-Family', 'happy-place'); ?></option>
                                <option value="land" <?php selected($listing_data['property_type'] ?? '', 'land'); ?>><?php _e('Land', 'happy-place'); ?></option>
                                <option value="commercial" <?php selected($listing_data['property_type'] ?? '', 'commercial'); ?>><?php _e('Commercial', 'happy-place'); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php _e('Garage Spaces', 'happy-place'); ?></label>
                            <input type="number" name="garage_spaces" class="form-control" 
                                   value="<?php echo esc_attr($listing_data['garage_spaces'] ?? '0'); ?>" 
                                   min="0" max="10">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php _e('Stories', 'happy-place'); ?></label>
                            <input type="number" name="stories" class="form-control" 
                                   value="<?php echo esc_attr($listing_data['stories'] ?? ''); ?>" 
                                   min="1" max="10" step="0.5">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label"><?php _e('Property Description', 'happy-place'); ?></label>
                        <textarea name="post_content" class="form-control" rows="6" 
                                  placeholder="<?php _e('Describe the property features, neighborhood, and what makes it special...', 'happy-place'); ?>"><?php echo esc_textarea($listing_data['post_content'] ?? ''); ?></textarea>
                        <div class="form-help"><?php _e('This description will be displayed to potential buyers', 'happy-place'); ?></div>
                    </div>
                </div>
                
                <!-- Step 4: Media & Virtual Tour -->
                <div class="hph-form-step" data-step="4">
                    <div class="step-header">
                        <h4><?php _e('Photos & Media', 'happy-place'); ?></h4>
                        <p><?php _e('Add photos and virtual tour links to showcase the property.', 'happy-place'); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label"><?php _e('Photo Gallery', 'happy-place'); ?></label>
                        <div id="photo-gallery-uploader" class="hph-media-uploader">
                            <div class="upload-area">
                                <div class="upload-placeholder">
                                    <i class="fas fa-camera"></i>
                                    <p><?php _e('Click to upload photos or drag them here', 'happy-place'); ?></p>
                                    <button type="button" class="hph-btn hph-btn--modern" id="upload-photos-btn">
                                        <?php _e('Choose Photos', 'happy-place'); ?>
                                    </button>
                                </div>
                            </div>
                            <div id="photo-gallery-preview" class="gallery-preview"></div>
                        </div>
                        <div class="form-help"><?php _e('Upload high-quality photos of the property. First photo will be the featured image.', 'happy-place'); ?></div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><?php _e('Virtual Tour URL', 'happy-place'); ?></label>
                            <input type="url" name="virtual_tour_url" class="form-control" 
                                   value="<?php echo esc_attr($listing_data['virtual_tour_url'] ?? ''); ?>" 
                                   placeholder="<?php _e('https://example.com/virtual-tour', 'happy-place'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php _e('Video Tour URL', 'happy-place'); ?></label>
                            <input type="url" name="video_tour_url" class="form-control" 
                                   value="<?php echo esc_attr($listing_data['video_tour_url'] ?? ''); ?>" 
                                   placeholder="<?php _e('https://youtube.com/watch?v=...', 'happy-place'); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="hph-form-actions">
                    <div class="form-actions-left">
                        <button type="button" class="hph-btn hph-btn--ghost" id="prev-step-btn" style="display: none;">
                            <i class="fas fa-arrow-left"></i> <?php _e('Previous', 'happy-place'); ?>
                        </button>
                    </div>
                    
                    <div class="form-actions-center">
                        <button type="button" class="hph-btn hph-btn--secondary" id="save-draft-btn">
                            <i class="fas fa-save"></i> <?php _e('Save as Draft', 'happy-place'); ?>
                        </button>
                    </div>
                    
                    <div class="form-actions-right">
                        <button type="button" class="hph-btn hph-btn--modern" id="next-step-btn">
                            <?php _e('Next', 'happy-place'); ?> <i class="fas fa-arrow-right"></i>
                        </button>
                        
                        <button type="submit" class="hph-btn hph-btn--gradient" id="submit-listing-btn" style="display: none;">
                            <i class="fas fa-check"></i> <?php echo $is_new ? __('Create Listing', 'happy-place') : __('Update Listing', 'happy-place'); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render open house scheduling form
     */
    public function render_open_house_form($listing_id = 0, $open_house_id = 0) {
        $listing = $listing_id ? get_post($listing_id) : null;
        $open_house_data = $open_house_id ? $this->get_open_house_form_data($open_house_id) : [];
        $is_new = !$open_house_id;
        
        ?>
        <div class="hph-open-house-form-container">
            <form id="hph-open-house-form" class="hph-form-modern" method="post">
                <?php wp_nonce_field('hph_dashboard_nonce', 'nonce'); ?>
                <input type="hidden" name="action" value="hph_submit_open_house_form">
                <input type="hidden" name="post_id" value="<?php echo $open_house_id; ?>">
                <input type="hidden" name="listing" value="<?php echo $listing_id; ?>">
                
                <!-- Form Header -->
                <div class="hph-form-header">
                    <h3 class="form-title">
                        <?php echo $is_new ? __('Schedule Open House', 'happy-place') : __('Edit Open House', 'happy-place'); ?>
                    </h3>
                    <?php if ($listing): ?>
                        <p class="form-subtitle">
                            <?php printf(__('For: %s', 'happy-place'), '<strong>' . esc_html($listing->post_title) . '</strong>'); ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label required"><?php _e('Open House Date', 'happy-place'); ?></label>
                        <input type="date" name="open_house_date" class="form-control" 
                               value="<?php echo esc_attr($open_house_data['open_house_date'] ?? ''); ?>" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required"><?php _e('Start Time', 'happy-place'); ?></label>
                        <input type="time" name="start_time" class="form-control" 
                               value="<?php echo esc_attr($open_house_data['start_time'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required"><?php _e('End Time', 'happy-place'); ?></label>
                        <input type="time" name="end_time" class="form-control" 
                               value="<?php echo esc_attr($open_house_data['end_time'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?php _e('Status', 'happy-place'); ?></label>
                        <select name="status" class="form-control">
                            <option value="scheduled" <?php selected($open_house_data['status'] ?? 'scheduled', 'scheduled'); ?>><?php _e('Scheduled', 'happy-place'); ?></option>
                            <option value="confirmed" <?php selected($open_house_data['status'] ?? '', 'confirmed'); ?>><?php _e('Confirmed', 'happy-place'); ?></option>
                            <option value="cancelled" <?php selected($open_house_data['status'] ?? '', 'cancelled'); ?>><?php _e('Cancelled', 'happy-place'); ?></option>
                            <option value="postponed" <?php selected($open_house_data['status'] ?? '', 'postponed'); ?>><?php _e('Postponed', 'happy-place'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label"><?php _e('Maximum Attendees', 'happy-place'); ?></label>
                        <input type="number" name="max_attendees" class="form-control" 
                               value="<?php echo esc_attr($open_house_data['max_attendees'] ?? ''); ?>" 
                               min="1" max="500" placeholder="<?php _e('No limit', 'happy-place'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label"><?php _e('Registration Required', 'happy-place'); ?></label>
                        <div class="toggle-switch">
                            <input type="checkbox" name="registration_required" value="1" 
                                   <?php checked($open_house_data['registration_required'] ?? false, true); ?>>
                            <span class="toggle-label"><?php _e('Require visitor registration', 'happy-place'); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label"><?php _e('Description', 'happy-place'); ?></label>
                    <textarea name="description" class="form-control" rows="4" 
                              placeholder="<?php _e('Special details about this open house...', 'happy-place'); ?>"><?php echo esc_textarea($open_house_data['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label"><?php _e('Special Instructions', 'happy-place'); ?></label>
                    <textarea name="special_instructions" class="form-control" rows="3" 
                              placeholder="<?php _e('Parking instructions, entry details, etc...', 'happy-place'); ?>"><?php echo esc_textarea($open_house_data['special_instructions'] ?? ''); ?></textarea>
                </div>
                
                <!-- Form Actions -->
                <div class="hph-form-actions">
                    <button type="button" class="hph-btn hph-btn--ghost" onclick="closeModal()">
                        <?php _e('Cancel', 'happy-place'); ?>
                    </button>
                    
                    <button type="submit" class="hph-btn hph-btn--gradient">
                        <i class="fas fa-calendar-plus"></i> 
                        <?php echo $is_new ? __('Schedule Open House', 'happy-place') : __('Update Open House', 'happy-place'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }
    
    /**
     * Get listing form data for editing
     */
    private function get_listing_form_data($listing_id) {
        $listing = get_post($listing_id);
        if (!$listing) return [];
        
        $acf_fields = get_fields($listing_id) ?: [];
        
        return array_merge([
            'post_title' => $listing->post_title,
            'post_content' => $listing->post_content,
            'post_status' => $listing->post_status
        ], $acf_fields);
    }
    
    /**
     * Get open house form data for editing
     */
    private function get_open_house_form_data($open_house_id) {
        $open_house = get_post($open_house_id);
        if (!$open_house) return [];
        
        $acf_fields = get_fields($open_house_id) ?: [];
        
        return array_merge([
            'post_title' => $open_house->post_title,
            'post_content' => $open_house->post_content,
            'post_status' => $open_house->post_status
        ], $acf_fields);
    }
    
    /**
     * Get listing display data for dashboard
     */
    private function get_listing_display_data($listing_id) {
        $listing = get_post($listing_id);
        if (!$listing) return null;
        
        return [
            'id' => $listing_id,
            'title' => $listing->post_title,
            'status' => $listing->post_status,
            'price' => get_field('price', $listing_id),
            'bedrooms' => get_field('bedrooms', $listing_id),
            'bathrooms' => get_field('bathrooms', $listing_id),
            'square_footage' => get_field('square_footage', $listing_id),
            'address' => get_field('full_address', $listing_id) ?: 
                        get_field('street_address', $listing_id) . ', ' . 
                        get_field('city', $listing_id) . ', ' . 
                        get_field('state', $listing_id),
            'url' => get_permalink($listing_id),
            'edit_url' => admin_url('post.php?post=' . $listing_id . '&action=edit'),
            'featured_image' => get_the_post_thumbnail_url($listing_id, 'thumbnail')
        ];
    }
    
    /**
     * Get open house display data for dashboard
     */
    private function get_open_house_display_data($open_house_id) {
        $open_house = get_post($open_house_id);
        if (!$open_house) return null;
        
        return [
            'id' => $open_house_id,
            'title' => $open_house->post_title,
            'status' => get_field('status', $open_house_id),
            'date' => get_field('open_house_date', $open_house_id),
            'start_time' => get_field('start_time', $open_house_id),
            'end_time' => get_field('end_time', $open_house_id),
            'listing_id' => get_field('listing', $open_house_id),
            'listing_title' => get_the_title(get_field('listing', $open_house_id)),
            'url' => get_permalink($open_house_id)
        ];
    }
    
    /**
     * Check if user can edit listing
     */
    private function can_edit_listing($listing_id) {
        if (!$listing_id) return true; // New listing
        
        $listing = get_post($listing_id);
        if (!$listing) return false;
        
        // Check if user is the author or has admin privileges
        return current_user_can('edit_post', $listing_id) || 
               $listing->post_author == get_current_user_id();
    }
}