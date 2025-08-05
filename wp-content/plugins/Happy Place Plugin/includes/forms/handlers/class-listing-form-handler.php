<?php
/**
 * Listing Form Handler - Handles listing creation and editing forms
 * 
 * Manages frontend and admin forms for creating and editing real estate listings
 * with full ACF integration and bridge function compatibility.
 * 
 * @package HappyPlace
 * @subpackage Forms\Handlers
 */

namespace HappyPlace\Forms\Handlers;

use HappyPlace\Forms\Base_Form_Handler;

if (!defined('ABSPATH')) {
    exit;
}

class Listing_Form_Handler extends Base_Form_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('listing', 'listing');
    }
    
    /**
     * Setup validation rules for listing forms
     */
    protected function setup_validation_rules() {
        $this->required_fields = [
            'post_title',
            'price',
            'listing_agent',
            'street_address',
            'city',
            'state',
            'zip_code'
        ];
        
        $this->validation_rules = [
            'price' => [
                'numeric' => true,
                'sanitize' => 'floatval'
            ],
            'square_footage' => [
                'numeric' => true,
                'sanitize' => 'intval'
            ],
            'bedrooms' => [
                'numeric' => true,
                'sanitize' => 'intval'
            ],
            'bathrooms' => [
                'numeric' => true,
                'sanitize' => 'floatval'
            ],
            'year_built' => [
                'numeric' => true,
                'min_length' => 4,
                'max_length' => 4,
                'sanitize' => 'intval'
            ],
            'listing_agent' => [
                'post_exists' => true,
                'sanitize' => 'absint'
            ],
            'office' => [
                'post_exists' => true,
                'sanitize' => 'absint'
            ],
            'community' => [
                'post_exists' => true,
                'sanitize' => 'absint'
            ],
            'email' => [
                'email' => true,
                'sanitize' => 'sanitize_email'
            ],
            'mls_number' => [
                'max_length' => 20,
                'sanitize' => 'sanitize_text_field'
            ],
            'lot_size' => [
                'numeric' => true,
                'sanitize' => 'floatval'
            ],
            'hoa_fee' => [
                'numeric' => true,
                'sanitize' => 'floatval'
            ]
        ];
    }
    
    /**
     * Process listing form submission
     *
     * @param array $form_data Validated form data
     * @return int|WP_Error Post ID on success, WP_Error on failure
     */
    protected function process_submission($form_data) {
        $post_id = isset($form_data['post_id']) ? absint($form_data['post_id']) : 0;
        
        // Prepare post data
        $post_data = [
            'post_title' => $form_data['post_title'],
            'post_content' => $form_data['full_description'] ?? '',
            'post_excerpt' => $form_data['short_description'] ?? '',
            'post_type' => 'listing',
            'post_status' => $this->determine_post_status($form_data),
            'meta_input' => []
        ];
        
        if ($post_id) {
            // Update existing post
            $post_data['ID'] = $post_id;
            $result = wp_update_post($post_data);
        } else {
            // Create new post
            $result = wp_insert_post($post_data);
        }
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $post_id = $result;
        
        // Save ACF fields
        $this->save_acf_fields($post_id, $form_data);
        
        // Save taxonomies
        $this->save_taxonomies($post_id, $form_data);
        
        // Handle photo gallery
        if (isset($form_data['photo_gallery']) && !empty($form_data['photo_gallery'])) {
            $this->process_photo_gallery($post_id, $form_data['photo_gallery']);
        }
        
        // Calculate derived fields
        $this->calculate_derived_fields($post_id, $form_data);
        
        // Send notifications
        $this->send_notifications($post_id, $form_data, !$post_id);
        
        return $post_id;
    }
    
    /**
     * Custom validation for listing data
     *
     * @param array $form_data Form data
     * @return array Validation errors
     */
    protected function custom_validation($form_data) {
        $errors = [];
        
        // Price validation
        if (isset($form_data['price'])) {
            $price = floatval($form_data['price']);
            if ($price < 1000) {
                $errors['price'] = __('Price must be at least $1,000', 'happy-place');
            }
            if ($price > 50000000) {
                $errors['price'] = __('Price cannot exceed $50,000,000', 'happy-place');
            }
        }
        
        // Square footage validation
        if (isset($form_data['square_footage'])) {
            $sqft = intval($form_data['square_footage']);
            if ($sqft < 200) {
                $errors['square_footage'] = __('Square footage must be at least 200', 'happy-place');
            }
        }
        
        // Year built validation
        if (isset($form_data['year_built'])) {
            $year = intval($form_data['year_built']);
            $current_year = date('Y');
            if ($year < 1800 || $year > $current_year + 2) {
                $errors['year_built'] = sprintf(__('Year built must be between 1800 and %d', 'happy-place'), $current_year + 2);
            }
        }
        
        // MLS number uniqueness
        if (isset($form_data['mls_number']) && !empty($form_data['mls_number'])) {
            $existing_post = get_posts([
                'post_type' => 'listing',
                'meta_key' => 'mls_number',
                'meta_value' => $form_data['mls_number'],
                'post__not_in' => [absint($form_data['post_id'] ?? 0)],
                'posts_per_page' => 1
            ]);
            
            if (!empty($existing_post)) {
                $errors['mls_number'] = __('MLS number must be unique', 'happy-place');
            }
        }
        
        // Agent validation
        if (isset($form_data['listing_agent'])) {
            $agent = get_post($form_data['listing_agent']);
            if (!$agent || $agent->post_type !== 'agent') {
                $errors['listing_agent'] = __('Please select a valid agent', 'happy-place');
            }
        }
        
        // Bedroom/bathroom logic
        if (isset($form_data['bedrooms'], $form_data['bathrooms'])) {
            $bedrooms = intval($form_data['bedrooms']);
            $bathrooms = floatval($form_data['bathrooms']);
            
            if ($bedrooms > 0 && $bathrooms === 0) {
                $errors['bathrooms'] = __('Properties with bedrooms must have at least one bathroom', 'happy-place');
            }
        }
        
        return $errors;
    }
    
    /**
     * Render listing preview
     *
     * @param array $form_data Form data
     */
    protected function render_preview($form_data) {
        $price = isset($form_data['price']) ? number_format(floatval($form_data['price'])) : 'N/A';
        $bedrooms = $form_data['bedrooms'] ?? 'N/A';
        $bathrooms = $form_data['bathrooms'] ?? 'N/A';
        $sqft = isset($form_data['square_footage']) ? number_format(intval($form_data['square_footage'])) : 'N/A';
        
        ?>
        <div class="listing-preview">
            <div class="preview-header">
                <h4><?php echo esc_html($form_data['post_title'] ?? __('New Listing', 'happy-place')); ?></h4>
                <div class="preview-price">$<?php echo esc_html($price); ?></div>
            </div>
            
            <div class="preview-details">
                <div class="preview-stats">
                    <span class="bedrooms"><?php echo esc_html($bedrooms); ?> beds</span>
                    <span class="bathrooms"><?php echo esc_html($bathrooms); ?> baths</span>
                    <span class="square-footage"><?php echo esc_html($sqft); ?> sq ft</span>
                </div>
                
                <div class="preview-address">
                    <?php
                    $address_parts = [
                        $form_data['street_address'] ?? '',
                        $form_data['city'] ?? '',
                        $form_data['state'] ?? '',
                        $form_data['zip_code'] ?? ''
                    ];
                    echo esc_html(implode(' ', array_filter($address_parts)));
                    ?>
                </div>
                
                <?php if (!empty($form_data['short_description'])): ?>
                    <div class="preview-description">
                        <?php echo wp_kses_post(wp_trim_words($form_data['short_description'], 25)); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($form_data['custom_features'])): ?>
                <div class="preview-features">
                    <strong><?php _e('Features:', 'happy-place'); ?></strong>
                    <?php
                    $features = is_array($form_data['custom_features']) ? $form_data['custom_features'] : [$form_data['custom_features']];
                    echo esc_html(implode(', ', array_slice($features, 0, 5)));
                    if (count($features) > 5) {
                        echo ' <em>+' . (count($features) - 5) . ' more</em>';
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Determine post status based on form data and user permissions
     *
     * @param array $form_data Form data
     * @return string Post status
     */
    private function determine_post_status($form_data) {
        // Check if user can publish posts
        if (!current_user_can('publish_posts')) {
            return 'pending';
        }
        
        // Check if this is a draft
        if (isset($form_data['save_as_draft']) && $form_data['save_as_draft']) {
            return 'draft';
        }
        
        // Check for required fields - if missing, save as draft
        foreach ($this->required_fields as $field) {
            if (empty($form_data[$field])) {
                return 'draft';
            }
        }
        
        return $form_data['post_status'] ?? 'publish';
    }
    
    /**
     * Save ACF fields
     *
     * @param int $post_id Post ID
     * @param array $form_data Form data
     */
    private function save_acf_fields($post_id, $form_data) {
        $acf_fields = [
            // Core fields
            'price', 'square_footage', 'bedrooms', 'bathrooms', 'year_built',
            'listing_agent', 'office', 'community', 'mls_number',
            
            // Location fields
            'street_address', 'city', 'state', 'zip_code', 'neighborhood',
            'latitude', 'longitude',
            
            // Financial fields
            'list_price', 'sold_price', 'price_per_sqft', 'hoa_fee',
            'property_taxes', 'days_on_market',
            
            // Features
            'custom_features', 'garage_spaces', 'lot_size', 'pool',
            'waterfront', 'view_type',
            
            // Descriptions
            'full_description', 'short_description', 'agent_remarks',
            
            // Media
            'photo_gallery', 'virtual_tour_url', 'video_tour_url',
            
            // Relationships
            'similar_listings', 'neighborhood_listings'
        ];
        
        foreach ($acf_fields as $field) {
            if (isset($form_data[$field])) {
                update_field($field, $form_data[$field], $post_id);
            }
        }
    }
    
    /**
     * Save taxonomies
     *
     * @param int $post_id Post ID
     * @param array $form_data Form data
     */
    private function save_taxonomies($post_id, $form_data) {
        $taxonomies = [
            'property_type' => $form_data['property_type'] ?? '',
            'property_status' => $form_data['property_status'] ?? 'active',
            'property_features' => $form_data['property_features'] ?? []
        ];
        
        foreach ($taxonomies as $taxonomy => $value) {
            if (!empty($value)) {
                wp_set_post_terms($post_id, $value, $taxonomy);
            }
        }
    }
    
    /**
     * Process photo gallery with room tagging
     *
     * @param int $post_id Post ID
     * @param array $gallery_data Gallery data
     */
    private function process_photo_gallery($post_id, $gallery_data) {
        if (!is_array($gallery_data)) {
            return;
        }
        
        $processed_gallery = [];
        
        foreach ($gallery_data as $index => $photo) {
            if (isset($photo['id']) && is_numeric($photo['id'])) {
                $photo_data = [
                    'id' => absint($photo['id']),
                    'room_type' => sanitize_text_field($photo['room_type'] ?? ''),
                    'caption' => sanitize_text_field($photo['caption'] ?? ''),
                    'is_featured' => !empty($photo['is_featured'])
                ];
                
                $processed_gallery[] = $photo_data;
            }
        }
        
        update_field('photo_gallery', $processed_gallery, $post_id);
        
        // Set featured image if specified
        foreach ($processed_gallery as $photo) {
            if ($photo['is_featured']) {
                set_post_thumbnail($post_id, $photo['id']);
                break;
            }
        }
    }
    
    /**
     * Calculate derived fields
     *
     * @param int $post_id Post ID
     * @param array $form_data Form data
     */
    private function calculate_derived_fields($post_id, $form_data) {
        // Calculate price per square foot
        if (isset($form_data['price'], $form_data['square_footage'])) {
            $price = floatval($form_data['price']);
            $sqft = intval($form_data['square_footage']);
            
            if ($sqft > 0) {
                $price_per_sqft = round($price / $sqft, 2);
                update_field('price_per_sqft', $price_per_sqft, $post_id);
            }
        }
        
        // Auto-populate community HOA fee if community is selected
        if (isset($form_data['community']) && !isset($form_data['hoa_fee'])) {
            $community_id = absint($form_data['community']);
            if ($community_id && function_exists('hph_bridge_get_community_data')) {
                $community_data = hph_bridge_get_community_data($community_id);
                $property_type = $form_data['property_type'] ?? 'single_family';
                
                $hoa_fee_field = "hoa_fee_{$property_type}";
                if (isset($community_data[$hoa_fee_field])) {
                    update_field('hoa_fee', $community_data[$hoa_fee_field], $post_id);
                }
            }
        }
        
        // Calculate days on market
        $list_date = get_field('list_date', $post_id);
        if ($list_date) {
            $list_timestamp = strtotime($list_date);
            $days_on_market = floor((time() - $list_timestamp) / DAY_IN_SECONDS);
            update_field('days_on_market', max(0, $days_on_market), $post_id);
        }
    }
    
    /**
     * Send notifications for new/updated listings
     *
     * @param int $post_id Post ID
     * @param array $form_data Form data
     * @param bool $is_new Whether this is a new listing
     */
    private function send_notifications($post_id, $form_data, $is_new) {
        $listing_title = get_the_title($post_id);
        $listing_url = get_permalink($post_id);
        
        // Notify agent
        if (isset($form_data['listing_agent'])) {
            $agent = get_post($form_data['listing_agent']);
            $agent_email = get_field('email', $form_data['listing_agent']);
            
            if ($agent_email) {
                $subject = sprintf(
                    __('[%s] %s Listing: %s', 'happy-place'),
                    get_bloginfo('name'),
                    $is_new ? 'New' : 'Updated',
                    $listing_title
                );
                
                $message = sprintf(
                    __("Hello %s,\n\nA listing has been %s:\n\nListing: %s\nAddress: %s\nPrice: $%s\n\nView listing: %s\n\nBest regards,\nThe %s Team", 'happy-place'),
                    get_the_title($form_data['listing_agent']),
                    $is_new ? 'created' : 'updated',
                    $listing_title,
                    implode(' ', array_filter([
                        $form_data['street_address'] ?? '',
                        $form_data['city'] ?? '',
                        $form_data['state'] ?? ''
                    ])),
                    number_format(floatval($form_data['price'] ?? 0)),
                    $listing_url,
                    get_bloginfo('name')
                );
                
                wp_mail($agent_email, $subject, $message);
            }
        }
        
        // Notify admin for new listings
        if ($is_new) {
            $admin_email = get_option('admin_email');
            $subject = sprintf(__('[%s] New Listing Created: %s', 'happy-place'), get_bloginfo('name'), $listing_title);
            
            $message = sprintf(
                __("A new listing has been created:\n\nListing: %s\nAgent: %s\nPrice: $%s\n\nView in admin: %s", 'happy-place'),
                $listing_title,
                isset($form_data['listing_agent']) ? get_the_title($form_data['listing_agent']) : 'Unknown',
                number_format(floatval($form_data['price'] ?? 0)),
                admin_url('post.php?post=' . $post_id . '&action=edit')
            );
            
            wp_mail($admin_email, $subject, $message);
        }
        
        // Fire action hooks for integrations
        do_action('hph_listing_saved', $post_id, $form_data, $is_new);
        
        if ($is_new) {
            do_action('hph_new_listing_created', $post_id, $form_data);
        } else {
            do_action('hph_listing_updated', $post_id, $form_data);
        }
    }
}