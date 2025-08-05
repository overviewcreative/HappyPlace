<?php
/**
 * Office Form Handler - Handles real estate office management
 * 
 * Manages frontend and admin forms for real estate office profiles
 * including agent management, contact information, and office statistics.
 * 
 * @package HappyPlace
 * @subpackage Forms\Handlers
 */

namespace HappyPlace\Forms\Handlers;

use HappyPlace\Forms\Base_Form_Handler;

if (!defined('ABSPATH')) {
    exit;
}

class Office_Form_Handler extends Base_Form_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('office', 'office');
    }
    
    /**
     * Setup validation rules for office forms
     */
    protected function setup_validation_rules() {
        $this->required_fields = [
            'post_title',
            'office_type',
            'phone',
            'email',
            'address',
            'city',
            'state',
            'zip_code'
        ];
        
        $this->validation_rules = [
            'phone' => [
                'phone' => true,
                'sanitize' => 'sanitize_text_field'
            ],
            'fax' => [
                'phone' => true,
                'sanitize' => 'sanitize_text_field'
            ],
            'email' => [
                'email' => true,
                'sanitize' => 'sanitize_email'
            ],
            'website' => [
                'sanitize' => 'esc_url_raw'
            ],
            'zip_code' => [
                'max_length' => 10,
                'sanitize' => 'sanitize_text_field'
            ],
            'total_agents' => [
                'numeric' => true,
                'sanitize' => 'intval'
            ],
            'year_established' => [
                'numeric' => true,
                'sanitize' => 'intval'
            ],
            'square_footage' => [
                'numeric' => true,
                'sanitize' => 'intval'
            ],
            'manager_email' => [
                'email' => true,
                'sanitize' => 'sanitize_email'
            ],
            'manager_phone' => [
                'phone' => true,
                'sanitize' => 'sanitize_text_field'
            ],
            'mls_access' => [
                'sanitize' => [$this, 'sanitize_array_field']
            ],
            'specializations' => [
                'sanitize' => [$this, 'sanitize_array_field']
            ],
            'service_areas' => [
                'sanitize' => [$this, 'sanitize_array_field']
            ],
            'office_hours' => [
                'sanitize' => [$this, 'sanitize_office_hours']
            ]
        ];
    }
    
    /**
     * Process office form submission
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
            'post_type' => 'office',
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
        
        // Handle office logo
        if (isset($form_data['office_logo']) && !empty($form_data['office_logo'])) {
            $this->process_office_logo($post_id, $form_data['office_logo']);
        }
        
        // Calculate office statistics
        $this->calculate_office_statistics($post_id);
        
        // Update agent assignments if specified
        if (isset($form_data['agent_assignments']) && is_array($form_data['agent_assignments'])) {
            $this->update_agent_assignments($post_id, $form_data['agent_assignments']);
        }
        
        // Send notifications
        $this->send_notifications($post_id, $form_data, $is_new);
        
        return $post_id;
    }
    
    /**
     * Custom validation for office data
     *
     * @param array $form_data Form data
     * @return array Validation errors
     */
    protected function custom_validation($form_data) {
        $errors = [];
        
        // Office name uniqueness check within same city
        if (isset($form_data['post_title'], $form_data['city'])) {
            $existing_office = get_posts([
                'post_type' => 'office',
                'title' => $form_data['post_title'],
                'meta_query' => [
                    [
                        'key' => 'city',
                        'value' => $form_data['city'],
                        'compare' => '='
                    ]
                ],
                'post__not_in' => [absint($form_data['post_id'] ?? 0)],
                'posts_per_page' => 1
            ]);
            
            if (!empty($existing_office)) {
                $errors['post_title'] = __('An office with this name already exists in this city', 'happy-place');
            }
        }
        
        // Phone number uniqueness
        if (isset($form_data['phone'])) {
            $existing_phone = get_posts([
                'post_type' => 'office',
                'meta_key' => 'phone',
                'meta_value' => $form_data['phone'],
                'post__not_in' => [absint($form_data['post_id'] ?? 0)],
                'posts_per_page' => 1
            ]);
            
            if (!empty($existing_phone)) {
                $errors['phone'] = __('This phone number is already registered to another office', 'happy-place');
            }
        }
        
        // Email uniqueness
        if (isset($form_data['email'])) {
            $existing_email = get_posts([
                'post_type' => 'office',
                'meta_key' => 'email',
                'meta_value' => $form_data['email'],
                'post__not_in' => [absint($form_data['post_id'] ?? 0)],
                'posts_per_page' => 1
            ]);
            
            if (!empty($existing_email)) {
                $errors['email'] = __('This email address is already registered to another office', 'happy-place');
            }
        }
        
        // Year established validation
        if (isset($form_data['year_established'])) {
            $year = intval($form_data['year_established']);
            $current_year = date('Y');
            if ($year < 1800 || $year > $current_year) {
                $errors['year_established'] = sprintf(__('Year established must be between 1800 and %d', 'happy-place'), $current_year);
            }
        }
        
        // Total agents validation
        if (isset($form_data['total_agents'])) {
            $total_agents = intval($form_data['total_agents']);
            if ($total_agents < 0 || $total_agents > 1000) {
                $errors['total_agents'] = __('Total agents must be between 0 and 1,000', 'happy-place');
            }
        }
        
        // Square footage validation
        if (isset($form_data['square_footage'])) {
            $sqft = intval($form_data['square_footage']);
            if ($sqft < 100 || $sqft > 100000) {
                $errors['square_footage'] = __('Square footage must be between 100 and 100,000', 'happy-place');
            }
        }
        
        // Website URL validation
        if (isset($form_data['website']) && !empty($form_data['website'])) {
            if (!filter_var($form_data['website'], FILTER_VALIDATE_URL)) {
                $errors['website'] = __('Please enter a valid website URL', 'happy-place');
            }
        }
        
        // Office hours validation
        if (isset($form_data['office_hours']) && is_array($form_data['office_hours'])) {
            foreach ($form_data['office_hours'] as $day => $hours) {
                if (isset($hours['open'], $hours['close']) && !empty($hours['open']) && !empty($hours['close'])) {
                    $open_time = strtotime($hours['open']);
                    $close_time = strtotime($hours['close']);
                    
                    if ($close_time <= $open_time) {
                        $errors["office_hours_{$day}"] = sprintf(__('%s closing time must be after opening time', 'happy-place'), ucfirst($day));
                    }
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Render office preview
     *
     * @param array $form_data Form data
     */
    protected function render_preview($form_data) {
        $office_type = $form_data['office_type'] ?? 'branch';
        $location = implode(', ', array_filter([
            $form_data['city'] ?? '',
            $form_data['state'] ?? ''
        ]));
        
        $total_agents = $form_data['total_agents'] ?? 0;
        
        ?>
        <div class="office-preview">
            <div class="preview-header">
                <div class="preview-logo">
                    <?php if (!empty($form_data['office_logo'])): ?>
                        <img src="<?php echo esc_url(wp_get_attachment_url($form_data['office_logo'])); ?>" 
                             alt="<?php echo esc_attr($form_data['post_title']); ?>" class="office-logo">
                    <?php else: ?>
                        <div class="logo-placeholder">
                            <?php echo esc_html(substr($form_data['post_title'] ?? 'O', 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="preview-info">
                    <h4><?php echo esc_html($form_data['post_title'] ?? __('New Office', 'happy-place')); ?></h4>
                    <div class="preview-type"><?php echo esc_html(ucfirst($office_type)); ?> Office</div>
                    
                    <?php if ($location): ?>
                        <div class="preview-location"><?php echo esc_html($location); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="preview-contact">
                <?php if (!empty($form_data['phone'])): ?>
                    <div class="preview-phone">
                        <strong><?php _e('Phone:', 'happy-place'); ?></strong>
                        <?php echo esc_html($form_data['phone']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($form_data['email'])): ?>
                    <div class="preview-email">
                        <strong><?php _e('Email:', 'happy-place'); ?></strong>
                        <?php echo esc_html($form_data['email']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($form_data['address'])): ?>
                    <div class="preview-address">
                        <strong><?php _e('Address:', 'happy-place'); ?></strong>
                        <?php echo esc_html($form_data['address']); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="preview-details">
                <?php if ($total_agents > 0): ?>
                    <div class="preview-agents">
                        <strong><?php _e('Agents:', 'happy-place'); ?></strong>
                        <?php echo esc_html($total_agents); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($form_data['year_established'])): ?>
                    <div class="preview-established">
                        <strong><?php _e('Established:', 'happy-place'); ?></strong>
                        <?php echo esc_html($form_data['year_established']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($form_data['specializations'])): ?>
                    <div class="preview-specializations">
                        <strong><?php _e('Specializations:', 'happy-place'); ?></strong>
                        <?php
                        $specializations = is_array($form_data['specializations']) ? 
                            $form_data['specializations'] : 
                            [$form_data['specializations']];
                        echo esc_html(implode(', ', array_slice($specializations, 0, 3)));
                        if (count($specializations) > 3) {
                            echo ' <em>+' . (count($specializations) - 3) . ' more</em>';
                        }
                        ?>
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
        
        // Office management typically requires admin approval
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
            'office_type', 'description', 'short_description',
            'year_established', 'total_agents',
            
            // Contact information
            'phone', 'fax', 'email', 'website',
            
            // Address
            'address', 'city', 'state', 'zip_code', 'county',
            'latitude', 'longitude',
            
            // Management
            'manager_name', 'manager_email', 'manager_phone',
            'assistant_name', 'assistant_email', 'assistant_phone',
            
            // Office details
            'square_footage', 'parking_spaces', 'conference_rooms',
            'private_offices', 'open_workspace',
            
            // Business info
            'license_number', 'mls_access', 'specializations',
            'service_areas', 'languages_spoken',
            
            // Hours and availability
            'office_hours', 'after_hours_contact', 'emergency_contact',
            
            // Media
            'office_logo', 'office_photos', 'virtual_tour_url',
            
            // Statistics (calculated)
            'active_agent_count', 'total_listings', 'total_sales_volume',
            'average_sale_price', 'market_share'
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
            'office_type' => $form_data['office_type'] ?? ''
        ];
        
        foreach ($taxonomies as $taxonomy => $value) {
            if (!empty($value) && taxonomy_exists($taxonomy)) {
                wp_set_post_terms($post_id, $value, $taxonomy);
            }
        }
    }
    
    /**
     * Process office logo upload
     *
     * @param int $post_id Post ID
     * @param int $logo_id Logo attachment ID
     */
    private function process_office_logo($post_id, $logo_id) {
        if (is_numeric($logo_id)) {
            update_field('office_logo', absint($logo_id), $post_id);
            
            // Set as featured image if no featured image exists
            if (!has_post_thumbnail($post_id)) {
                set_post_thumbnail($post_id, $logo_id);
            }
        }
    }
    
    /**
     * Calculate office statistics
     *
     * @param int $post_id Post ID
     */
    private function calculate_office_statistics($post_id) {
        // Get all agents assigned to this office
        $office_agents = get_posts([
            'post_type' => 'agent',
            'meta_key' => 'office',
            'meta_value' => $post_id,
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        $active_agent_count = 0;
        $total_listings = 0;
        $total_sales_volume = 0;
        $sale_prices = [];
        
        foreach ($office_agents as $agent) {
            $agent_status = get_field('status', $agent->ID);
            if ($agent_status === 'active') {
                $active_agent_count++;
            }
            
            // Get agent's listings
            $agent_listings = get_posts([
                'post_type' => 'listing',
                'meta_key' => 'listing_agent',
                'meta_value' => $agent->ID,
                'posts_per_page' => -1
            ]);
            
            $total_listings += count($agent_listings);
            
            // Calculate sales volume from sold listings
            foreach ($agent_listings as $listing) {
                $status = get_field('status', $listing->ID);
                if ($status === 'sold') {
                    $sold_price = floatval(get_field('sold_price', $listing->ID));
                    if ($sold_price > 0) {
                        $total_sales_volume += $sold_price;
                        $sale_prices[] = $sold_price;
                    }
                }
            }
        }
        
        // Calculate average sale price
        $average_sale_price = !empty($sale_prices) ? array_sum($sale_prices) / count($sale_prices) : 0;
        
        // Update statistics
        update_field('active_agent_count', $active_agent_count, $post_id);
        update_field('total_listings', $total_listings, $post_id);
        update_field('total_sales_volume', $total_sales_volume, $post_id);
        update_field('average_sale_price', round($average_sale_price, 2), $post_id);
        
        // Update total_agents field to match actual count
        update_field('total_agents', count($office_agents), $post_id);
    }
    
    /**
     * Update agent assignments to this office
     *
     * @param int $post_id Post ID
     * @param array $agent_assignments Agent assignment data
     */
    private function update_agent_assignments($post_id, $agent_assignments) {
        foreach ($agent_assignments as $assignment) {
            if (isset($assignment['agent_id'], $assignment['action'])) {
                $agent_id = absint($assignment['agent_id']);
                $action = sanitize_text_field($assignment['action']);
                
                if ($action === 'assign' && get_post($agent_id)) {
                    update_field('office', $post_id, $agent_id);
                } elseif ($action === 'unassign' && get_post($agent_id)) {
                    update_field('office', '', $agent_id);
                }
            }
        }
    }
    
    /**
     * Send notifications for new/updated offices
     *
     * @param int $post_id Post ID
     * @param array $form_data Form data
     * @param bool $is_new Whether this is a new office
     */
    private function send_notifications($post_id, $form_data, $is_new) {
        $office_name = $form_data['post_title'] ?? 'Unknown Office';
        
        // Notify admin
        if ($is_new) {
            $admin_email = get_option('admin_email');
            $subject = sprintf(__('[%s] New Office Added: %s', 'happy-place'), get_bloginfo('name'), $office_name);
            
            $message = sprintf(
                __("A new office has been added:\n\nName: %s\nType: %s\nLocation: %s, %s\nPhone: %s\nEmail: %s\n\nView office: %s", 'happy-place'),
                $office_name,
                ucfirst($form_data['office_type'] ?? 'branch'),
                $form_data['city'] ?? '',
                $form_data['state'] ?? '',
                $form_data['phone'] ?? '',
                $form_data['email'] ?? '',
                admin_url('post.php?post=' . $post_id . '&action=edit')
            );
            
            wp_mail($admin_email, $subject, $message);
        }
        
        // Notify office manager if email provided
        if (isset($form_data['manager_email']) && !empty($form_data['manager_email'])) {
            $subject = sprintf(
                __('[%s] Office Profile %s: %s', 'happy-place'),
                get_bloginfo('name'),
                $is_new ? 'Created' : 'Updated',
                $office_name
            );
            
            $message = sprintf(
                __("The office profile for %s has been %s.\n\nYou can view and manage the office profile at: %s\n\nIf you have any questions, please contact support.", 'happy-place'),
                $office_name,
                $is_new ? 'created' : 'updated',
                get_permalink($post_id)
            );
            
            wp_mail($form_data['manager_email'], $subject, $message);
        }
        
        // Fire action hooks
        do_action('hph_office_saved', $post_id, $form_data, $is_new);
        
        if ($is_new) {
            do_action('hph_new_office_added', $post_id, $form_data);
        } else {
            do_action('hph_office_updated', $post_id, $form_data);
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
     * Sanitize office hours field
     *
     * @param mixed $value Office hours data
     * @return array Sanitized office hours
     */
    public function sanitize_office_hours($value) {
        if (!is_array($value)) {
            return [];
        }
        
        $sanitized = [];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        foreach ($days as $day) {
            if (isset($value[$day]) && is_array($value[$day])) {
                $sanitized[$day] = [
                    'open' => sanitize_text_field($value[$day]['open'] ?? ''),
                    'close' => sanitize_text_field($value[$day]['close'] ?? ''),
                    'closed' => !empty($value[$day]['closed'])
                ];
            }
        }
        
        return $sanitized;
    }
}