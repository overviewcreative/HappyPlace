<?php
/**
 * Community Form Handler - Handles community/subdivision management
 * 
 * Manages frontend and admin forms for community profiles including
 * HOA information, amenities, and building management.
 * 
 * @package HappyPlace
 * @subpackage Forms\Handlers
 */

namespace HappyPlace\Forms\Handlers;

use HappyPlace\Forms\Base_Form_Handler;

if (!defined('ABSPATH')) {
    exit;
}

class Community_Form_Handler extends Base_Form_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('community', 'community');
    }
    
    /**
     * Setup validation rules for community forms
     */
    protected function setup_validation_rules() {
        $this->required_fields = [
            'post_title',
            'community_type',
            'city',
            'state'
        ];
        
        $this->validation_rules = [
            'hoa_fee_single_family' => [
                'numeric' => true,
                'sanitize' => 'floatval'
            ],
            'hoa_fee_townhouse' => [
                'numeric' => true,
                'sanitize' => 'floatval'
            ],
            'hoa_fee_condo' => [
                'numeric' => true,
                'sanitize' => 'floatval'
            ],
            'total_units' => [
                'numeric' => true,
                'sanitize' => 'intval'
            ],
            'year_built' => [
                'numeric' => true,
                'sanitize' => 'intval'
            ],
            'total_acreage' => [
                'numeric' => true,
                'sanitize' => 'floatval'
            ],
            'hoa_contact_email' => [
                'email' => true,
                'sanitize' => 'sanitize_email'
            ],
            'hoa_contact_phone' => [
                'phone' => true,
                'sanitize' => 'sanitize_text_field'
            ],
            'management_company_phone' => [
                'phone' => true,
                'sanitize' => 'sanitize_text_field'
            ],
            'management_company_email' => [
                'email' => true,
                'sanitize' => 'sanitize_email'
            ],
            'zip_code' => [
                'max_length' => 10,
                'sanitize' => 'sanitize_text_field'
            ],
            'hoa_includes' => [
                'sanitize' => [$this, 'sanitize_array_field']
            ],
            'amenities' => [
                'sanitize' => [$this, 'sanitize_array_field']
            ],
            'buildings' => [
                'sanitize' => [$this, 'sanitize_buildings_repeater']
            ]
        ];
    }
    
    /**
     * Process community form submission
     *
     * @param array $form_data Validated form data
     * @return int|WP_Error Post ID on success, WP_Error on failure
     */
    protected function process_submission($form_data) {
        $post_id = isset($form_data['post_id']) ? absint($form_data['post_id']) : 0;
        $is_new = !$post_id;
        
        // Prepare post data
        $post_data = [
            'post_title' => $form_data['post_title'],
            'post_content' => $form_data['description'] ?? '',
            'post_excerpt' => $form_data['short_description'] ?? '',
            'post_type' => 'community',
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
        
        // Process buildings repeater field
        if (isset($form_data['buildings']) && is_array($form_data['buildings'])) {
            $this->process_buildings($post_id, $form_data['buildings']);
        }
        
        // Calculate derived statistics
        $this->calculate_community_statistics($post_id, $form_data);
        
        // Update related listings with new HOA fees
        if (!$is_new) {
            $this->update_related_listings_hoa($post_id, $form_data);
        }
        
        // Send notifications
        $this->send_notifications($post_id, $form_data, $is_new);
        
        return $post_id;
    }
    
    /**
     * Custom validation for community data
     *
     * @param array $form_data Form data
     * @return array Validation errors
     */
    protected function custom_validation($form_data) {
        $errors = [];
        
        // HOA fee validation
        $hoa_fees = ['hoa_fee_single_family', 'hoa_fee_townhouse', 'hoa_fee_condo'];
        foreach ($hoa_fees as $fee_field) {
            if (isset($form_data[$fee_field])) {
                $fee = floatval($form_data[$fee_field]);
                if ($fee < 0 || $fee > 2000) {
                    $errors[$fee_field] = __('HOA fee must be between $0 and $2,000', 'happy-place');
                }
            }
        }
        
        // Year built validation
        if (isset($form_data['year_built'])) {
            $year = intval($form_data['year_built']);
            $current_year = date('Y');
            if ($year < 1800 || $year > $current_year + 5) {
                $errors['year_built'] = sprintf(__('Year built must be between 1800 and %d', 'happy-place'), $current_year + 5);
            }
        }
        
        // Total units validation
        if (isset($form_data['total_units'])) {
            $units = intval($form_data['total_units']);
            if ($units < 1 || $units > 10000) {
                $errors['total_units'] = __('Total units must be between 1 and 10,000', 'happy-place');
            }
        }
        
        // Acreage validation
        if (isset($form_data['total_acreage'])) {
            $acreage = floatval($form_data['total_acreage']);
            if ($acreage < 0.1 || $acreage > 10000) {
                $errors['total_acreage'] = __('Total acreage must be between 0.1 and 10,000', 'happy-place');
            }
        }
        
        // Community name uniqueness check
        if (isset($form_data['post_title'])) {
            $existing_community = get_posts([
                'post_type' => 'community',
                'title' => $form_data['post_title'],
                'post__not_in' => [absint($form_data['post_id'] ?? 0)],
                'posts_per_page' => 1
            ]);
            
            if (!empty($existing_community)) {
                // Additional check for same city to allow same names in different cities
                if (isset($form_data['city'])) {
                    $existing_city = get_field('city', $existing_community[0]->ID);
                    if ($existing_city === $form_data['city']) {
                        $errors['post_title'] = __('A community with this name already exists in this city', 'happy-place');
                    }
                }
            }
        }
        
        // Buildings validation
        if (isset($form_data['buildings']) && is_array($form_data['buildings'])) {
            foreach ($form_data['buildings'] as $index => $building) {
                if (isset($building['units']) && intval($building['units']) > 1000) {
                    $errors["buildings_{$index}_units"] = __('Building cannot have more than 1,000 units', 'happy-place');
                }
                
                if (isset($building['floors']) && intval($building['floors']) > 200) {
                    $errors["buildings_{$index}_floors"] = __('Building cannot have more than 200 floors', 'happy-place');
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Render community preview
     *
     * @param array $form_data Form data
     */
    protected function render_preview($form_data) {
        $community_type = $form_data['community_type'] ?? 'subdivision';
        $total_units = $form_data['total_units'] ?? 0;
        $location = implode(', ', array_filter([
            $form_data['city'] ?? '',
            $form_data['state'] ?? ''
        ]));
        
        $hoa_fees = [];
        if (!empty($form_data['hoa_fee_single_family'])) {
            $hoa_fees[] = 'Single Family: $' . number_format($form_data['hoa_fee_single_family']);
        }
        if (!empty($form_data['hoa_fee_townhouse'])) {
            $hoa_fees[] = 'Townhouse: $' . number_format($form_data['hoa_fee_townhouse']);
        }
        if (!empty($form_data['hoa_fee_condo'])) {
            $hoa_fees[] = 'Condo: $' . number_format($form_data['hoa_fee_condo']);
        }
        
        ?>
        <div class="community-preview">
            <div class="preview-header">
                <h4><?php echo esc_html($form_data['post_title'] ?? __('New Community', 'happy-place')); ?></h4>
                <div class="preview-type"><?php echo esc_html(ucfirst($community_type)); ?></div>
            </div>
            
            <div class="preview-details">
                <?php if ($location): ?>
                    <div class="preview-location">
                        <strong><?php _e('Location:', 'happy-place'); ?></strong>
                        <?php echo esc_html($location); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($total_units): ?>
                    <div class="preview-units">
                        <strong><?php _e('Total Units:', 'happy-place'); ?></strong>
                        <?php echo esc_html(number_format($total_units)); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($form_data['year_built'])): ?>
                    <div class="preview-year">
                        <strong><?php _e('Year Built:', 'happy-place'); ?></strong>
                        <?php echo esc_html($form_data['year_built']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($hoa_fees)): ?>
                    <div class="preview-hoa-fees">
                        <strong><?php _e('HOA Fees:', 'happy-place'); ?></strong>
                        <?php echo esc_html(implode(', ', $hoa_fees)); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($form_data['amenities'])): ?>
                    <div class="preview-amenities">
                        <strong><?php _e('Amenities:', 'happy-place'); ?></strong>
                        <?php
                        $amenities = is_array($form_data['amenities']) ? $form_data['amenities'] : [$form_data['amenities']];
                        echo esc_html(implode(', ', array_slice($amenities, 0, 5)));
                        if (count($amenities) > 5) {
                            echo ' <em>+' . (count($amenities) - 5) . ' more</em>';
                        }
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($form_data['buildings'])): ?>
                    <div class="preview-buildings">
                        <strong><?php _e('Buildings:', 'happy-place'); ?></strong>
                        <?php echo esc_html(count($form_data['buildings'])); ?> buildings
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($form_data['short_description'])): ?>
                    <div class="preview-description">
                        <?php echo wp_kses_post(wp_trim_words($form_data['short_description'], 25)); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Determine post status
     *
     * @param array $form_data Form data
     * @return string Post status
     */
    private function determine_post_status($form_data) {
        // Check for required fields
        foreach ($this->required_fields as $field) {
            if (empty($form_data[$field])) {
                return 'draft';
            }
        }
        
        // Community management typically requires admin approval
        if (!current_user_can('manage_options')) {
            return 'pending';
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
            // Basic info
            'community_type', 'description', 'short_description',
            
            // Location
            'address', 'city', 'state', 'zip_code', 'county',
            'neighborhood', 'latitude', 'longitude',
            
            // Community details
            'year_built', 'total_units', 'total_acreage', 'gated_community',
            
            // HOA Information
            'hoa_fee_single_family', 'hoa_fee_townhouse', 'hoa_fee_condo',
            'hoa_includes', 'hoa_frequency', 'hoa_contact_name',
            'hoa_contact_phone', 'hoa_contact_email',
            
            // Management company
            'management_company', 'management_company_phone',
            'management_company_email', 'management_company_address',
            
            // Amenities and features
            'amenities', 'community_features', 'pet_policy',
            'parking_info', 'guest_policy',
            
            // Buildings (repeater field)
            'buildings',
            
            // Documents and media
            'community_documents', 'photo_gallery', 'virtual_tour_url',
            
            // Statistics (calculated fields)
            'active_listings_count', 'average_listing_price',
            'average_hoa_fee', 'occupancy_rate'
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
            'city' => $form_data['city'] ?? '',
            'neighborhood' => $form_data['neighborhood'] ?? ''
        ];
        
        foreach ($taxonomies as $taxonomy => $value) {
            if (!empty($value) && taxonomy_exists($taxonomy)) {
                wp_set_post_terms($post_id, $value, $taxonomy);
            }
        }
    }
    
    /**
     * Process buildings repeater field
     *
     * @param int $post_id Post ID
     * @param array $buildings Buildings data
     */
    private function process_buildings($post_id, $buildings) {
        $processed_buildings = [];
        
        foreach ($buildings as $building) {
            if (!empty($building['name']) || !empty($building['address'])) {
                $processed_building = [
                    'name' => sanitize_text_field($building['name'] ?? ''),
                    'address' => sanitize_text_field($building['address'] ?? ''),
                    'floors' => absint($building['floors'] ?? 0),
                    'units' => absint($building['units'] ?? 0),
                    'year_built' => absint($building['year_built'] ?? 0),
                    'building_type' => sanitize_text_field($building['building_type'] ?? ''),
                    'amenities' => is_array($building['amenities'] ?? []) ? 
                        array_map('sanitize_text_field', $building['amenities']) : 
                        [],
                    'notes' => sanitize_textarea_field($building['notes'] ?? '')
                ];
                
                $processed_buildings[] = $processed_building;
            }
        }
        
        update_field('buildings', $processed_buildings, $post_id);
    }
    
    /**
     * Calculate community statistics
     *
     * @param int $post_id Post ID
     * @param array $form_data Form data
     */
    private function calculate_community_statistics($post_id, $form_data) {
        // Calculate average HOA fee
        $hoa_fees = array_filter([
            floatval($form_data['hoa_fee_single_family'] ?? 0),
            floatval($form_data['hoa_fee_townhouse'] ?? 0),
            floatval($form_data['hoa_fee_condo'] ?? 0)
        ]);
        
        if (!empty($hoa_fees)) {
            $average_hoa_fee = array_sum($hoa_fees) / count($hoa_fees);
            update_field('average_hoa_fee', round($average_hoa_fee, 2), $post_id);
        }
        
        // Count active listings in this community
        $active_listings = get_posts([
            'post_type' => 'listing',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'community',
                    'value' => $post_id,
                    'compare' => '='
                ],
                [
                    'key' => 'status',
                    'value' => ['active', 'pending', 'contingent'],
                    'compare' => 'IN'
                ]
            ],
            'posts_per_page' => -1
        ]);
        
        $active_count = count($active_listings);
        update_field('active_listings_count', $active_count, $post_id);
        
        // Calculate average listing price
        if ($active_count > 0) {
            $total_price = 0;
            foreach ($active_listings as $listing) {
                $price = floatval(get_field('price', $listing->ID));
                $total_price += $price;
            }
            
            $average_price = $total_price / $active_count;
            update_field('average_listing_price', round($average_price, 2), $post_id);
        }
        
        // Calculate occupancy rate based on total units vs available listings
        if (!empty($form_data['total_units'])) {
            $total_units = intval($form_data['total_units']);
            $occupancy_rate = $total_units > 0 ? 
                round((($total_units - $active_count) / $total_units) * 100, 1) : 
                0;
            update_field('occupancy_rate', $occupancy_rate, $post_id);
        }
    }
    
    /**
     * Update related listings with new HOA fees
     *
     * @param int $post_id Post ID
     * @param array $form_data Form data
     */
    private function update_related_listings_hoa($post_id, $form_data) {
        // Get all listings in this community
        $community_listings = get_posts([
            'post_type' => 'listing',
            'meta_key' => 'community',
            'meta_value' => $post_id,
            'posts_per_page' => -1
        ]);
        
        foreach ($community_listings as $listing) {
            $property_type = get_field('property_type', $listing->ID);
            $hoa_fee_field = "hoa_fee_{$property_type}";
            
            if (isset($form_data[$hoa_fee_field])) {
                $new_hoa_fee = floatval($form_data[$hoa_fee_field]);
                update_field('hoa_fee', $new_hoa_fee, $listing->ID);
            }
        }
    }
    
    /**
     * Send notifications for new/updated communities
     *
     * @param int $post_id Post ID
     * @param array $form_data Form data
     * @param bool $is_new Whether this is a new community
     */
    private function send_notifications($post_id, $form_data, $is_new) {
        $community_name = $form_data['post_title'] ?? 'Unknown Community';
        
        // Notify admin
        if ($is_new) {
            $admin_email = get_option('admin_email');
            $subject = sprintf(__('[%s] New Community Added: %s', 'happy-place'), get_bloginfo('name'), $community_name);
            
            $message = sprintf(
                __("A new community has been added:\n\nName: %s\nType: %s\nLocation: %s, %s\nTotal Units: %s\n\nView community: %s", 'happy-place'),
                $community_name,
                ucfirst($form_data['community_type'] ?? 'subdivision'),
                $form_data['city'] ?? '',
                $form_data['state'] ?? '',
                $form_data['total_units'] ?? 'Not specified',
                admin_url('post.php?post=' . $post_id . '&action=edit')
            );
            
            wp_mail($admin_email, $subject, $message);
        }
        
        // Notify agents with listings in this community about HOA fee changes
        if (!$is_new) {
            $this->notify_agents_of_hoa_changes($post_id, $form_data);
        }
        
        // Fire action hooks
        do_action('hph_community_saved', $post_id, $form_data, $is_new);
        
        if ($is_new) {
            do_action('hph_new_community_added', $post_id, $form_data);
        } else {
            do_action('hph_community_updated', $post_id, $form_data);
        }
    }
    
    /**
     * Notify agents of HOA fee changes
     *
     * @param int $post_id Post ID
     * @param array $form_data Form data
     */
    private function notify_agents_of_hoa_changes($post_id, $form_data) {
        // Get listings in this community
        $community_listings = get_posts([
            'post_type' => 'listing',
            'meta_key' => 'community',
            'meta_value' => $post_id,
            'posts_per_page' => -1
        ]);
        
        $notified_agents = [];
        
        foreach ($community_listings as $listing) {
            $agent_id = get_field('listing_agent', $listing->ID);
            
            if ($agent_id && !in_array($agent_id, $notified_agents)) {
                if (function_exists('hph_bridge_get_agent_data')) {
                    $agent_data = hph_bridge_get_agent_data($agent_id);
                    $agent_email = $agent_data['email'] ?? '';
                    
                    if ($agent_email) {
                        $subject = sprintf(
                            __('[%s] HOA Fee Update for %s', 'happy-place'),
                            get_bloginfo('name'),
                            $form_data['post_title']
                        );
                        
                        $message = sprintf(
                            __("The HOA fees for %s have been updated.\n\nPlease review your listings in this community to ensure pricing information is current.\n\nView community: %s", 'happy-place'),
                            $form_data['post_title'],
                            get_permalink($post_id)
                        );
                        
                        wp_mail($agent_email, $subject, $message);
                        $notified_agents[] = $agent_id;
                    }
                }
            }
        }
    }
    
    /**
     * Sanitize array fields
     *
     * @param mixed $value Field value
     * @return array Sanitized array
     */
    public function sanitize_array_field($value) {
        if (!is_array($value)) {
            return [];
        }
        
        return array_map('sanitize_text_field', array_filter($value));
    }
    
    /**
     * Sanitize buildings repeater field
     *
     * @param mixed $value Buildings data
     * @return array Sanitized buildings array
     */
    public function sanitize_buildings_repeater($value) {
        if (!is_array($value)) {
            return [];
        }
        
        $sanitized = [];
        
        foreach ($value as $building) {
            if (is_array($building)) {
                $sanitized[] = [
                    'name' => sanitize_text_field($building['name'] ?? ''),
                    'address' => sanitize_text_field($building['address'] ?? ''),
                    'floors' => absint($building['floors'] ?? 0),
                    'units' => absint($building['units'] ?? 0),
                    'year_built' => absint($building['year_built'] ?? 0),
                    'building_type' => sanitize_text_field($building['building_type'] ?? ''),
                    'amenities' => $this->sanitize_array_field($building['amenities'] ?? []),
                    'notes' => sanitize_textarea_field($building['notes'] ?? '')
                ];
            }
        }
        
        return $sanitized;
    }
}