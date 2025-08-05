<?php
/**
 * Agent Form Handler - Handles agent profile creation and editing
 * 
 * Manages frontend and admin forms for agent profile management
 * with comprehensive profile data and team relationships.
 * 
 * @package HappyPlace
 * @subpackage Forms\Handlers
 */

namespace HappyPlace\Forms\Handlers;

use HappyPlace\Forms\Base_Form_Handler;

if (!defined('ABSPATH')) {
    exit;
}

class Agent_Form_Handler extends Base_Form_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('agent', 'agent');
    }
    
    /**
     * Setup validation rules for agent forms
     */
    protected function setup_validation_rules() {
        $this->required_fields = [
            'post_title',
            'first_name',
            'last_name',
            'email',
            'phone',
            'office'
        ];
        
        $this->validation_rules = [
            'email' => [
                'email' => true,
                'sanitize' => 'sanitize_email'
            ],
            'phone' => [
                'phone' => true,
                'sanitize' => 'sanitize_text_field'
            ],
            'office' => [
                'post_exists' => true,
                'sanitize' => 'absint'
            ],
            'license_number' => [
                'max_length' => 50,
                'sanitize' => 'sanitize_text_field'
            ],
            'years_experience' => [
                'numeric' => true,
                'sanitize' => 'intval'
            ],
            'website' => [
                'sanitize' => 'esc_url_raw'
            ],
            'linkedin_url' => [
                'sanitize' => 'esc_url_raw'
            ],
            'facebook_url' => [
                'sanitize' => 'esc_url_raw'
            ],
            'twitter_url' => [
                'sanitize' => 'esc_url_raw'
            ],
            'instagram_url' => [
                'sanitize' => 'esc_url_raw'
            ],
            'specializations' => [
                'sanitize' => [$this, 'sanitize_array_field']
            ],
            'service_areas' => [
                'sanitize' => [$this, 'sanitize_array_field']
            ],
            'languages' => [
                'sanitize' => [$this, 'sanitize_array_field']
            ],
            'certifications' => [
                'sanitize' => [$this, 'sanitize_array_field']
            ]
        ];
    }
    
    /**
     * Process agent form submission
     *
     * @param array $form_data Validated form data
     * @return int|WP_Error Post ID on success, WP_Error on failure
     */
    protected function process_submission($form_data) {
        $post_id = isset($form_data['post_id']) ? absint($form_data['post_id']) : 0;
        $is_new = !$post_id;
        
        // Generate full name for title if not provided
        if (empty($form_data['post_title']) && isset($form_data['first_name'], $form_data['last_name'])) {
            $form_data['post_title'] = trim($form_data['first_name'] . ' ' . $form_data['last_name']);
        }
        
        // Prepare post data
        $post_data = [
            'post_title' => $form_data['post_title'],
            'post_content' => $form_data['bio'] ?? '',
            'post_excerpt' => $form_data['short_bio'] ?? '',
            'post_type' => 'agent',
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
        
        // Handle profile photo
        if (isset($form_data['profile_photo']) && !empty($form_data['profile_photo'])) {
            $this->process_profile_photo($post_id, $form_data['profile_photo']);
        }
        
        // Create/update WordPress user if email provided and user creation is enabled
        if ($is_new && !empty($form_data['create_user_account'])) {
            $user_result = $this->create_wordpress_user($post_id, $form_data);
            if (is_wp_error($user_result)) {
                error_log('HPH Agent Form: Failed to create user account: ' . $user_result->get_error_message());
            }
        }
        
        // Send notifications
        $this->send_notifications($post_id, $form_data, $is_new);
        
        // Update agent statistics
        $this->update_agent_statistics($post_id);
        
        return $post_id;
    }
    
    /**
     * Custom validation for agent data
     *
     * @param array $form_data Form data
     * @return array Validation errors
     */
    protected function custom_validation($form_data) {
        $errors = [];
        
        // Email uniqueness check
        if (isset($form_data['email'])) {
            $existing_agent = get_posts([
                'post_type' => 'agent',
                'meta_key' => 'email',
                'meta_value' => $form_data['email'],
                'post__not_in' => [absint($form_data['post_id'] ?? 0)],
                'posts_per_page' => 1
            ]);
            
            if (!empty($existing_agent)) {
                $errors['email'] = __('This email address is already associated with another agent', 'happy-place');
            }
        }
        
        // License number validation
        if (isset($form_data['license_number']) && !empty($form_data['license_number'])) {
            // Check uniqueness
            $existing_license = get_posts([
                'post_type' => 'agent',
                'meta_key' => 'license_number',
                'meta_value' => $form_data['license_number'],
                'post__not_in' => [absint($form_data['post_id'] ?? 0)],
                'posts_per_page' => 1
            ]);
            
            if (!empty($existing_license)) {
                $errors['license_number'] = __('This license number is already in use', 'happy-place');
            }
            
            // Basic format validation (adjust regex based on local requirements)
            if (!preg_match('/^[A-Z0-9\-]{5,20}$/i', $form_data['license_number'])) {
                $errors['license_number'] = __('Please enter a valid license number format', 'happy-place');
            }
        }
        
        // Years of experience validation
        if (isset($form_data['years_experience'])) {
            $years = intval($form_data['years_experience']);
            if ($years < 0 || $years > 70) {
                $errors['years_experience'] = __('Years of experience must be between 0 and 70', 'happy-place');
            }
        }
        
        // Office validation
        if (isset($form_data['office'])) {
            $office = get_post($form_data['office']);
            if (!$office || $office->post_type !== 'office') {
                $errors['office'] = __('Please select a valid office', 'happy-place');
            }
        }
        
        // URL validation for social media links
        $url_fields = ['website', 'linkedin_url', 'facebook_url', 'twitter_url', 'instagram_url'];
        foreach ($url_fields as $field) {
            if (isset($form_data[$field]) && !empty($form_data[$field])) {
                if (!filter_var($form_data[$field], FILTER_VALIDATE_URL)) {
                    $label = $this->get_field_label($field);
                    $errors[$field] = sprintf(__('%s must be a valid URL', 'happy-place'), $label);
                }
            }
        }
        
        // Team lead validation (cannot be team lead of themselves)
        if (isset($form_data['team_lead']) && !empty($form_data['team_lead'])) {
            $current_agent_id = absint($form_data['post_id'] ?? 0);
            if ($current_agent_id && $form_data['team_lead'] == $current_agent_id) {
                $errors['team_lead'] = __('An agent cannot be their own team lead', 'happy-place');
            }
        }
        
        return $errors;
    }
    
    /**
     * Render agent preview
     *
     * @param array $form_data Form data
     */
    protected function render_preview($form_data) {
        $full_name = trim(($form_data['first_name'] ?? '') . ' ' . ($form_data['last_name'] ?? ''));
        $office_name = '';
        
        if (isset($form_data['office'])) {
            $office_name = get_the_title($form_data['office']);
        }
        
        ?>
        <div class="agent-preview">
            <div class="preview-header">
                <div class="preview-avatar">
                    <?php if (!empty($form_data['profile_photo'])): ?>
                        <img src="<?php echo esc_url(wp_get_attachment_url($form_data['profile_photo'])); ?>" 
                             alt="<?php echo esc_attr($full_name); ?>" class="agent-photo">
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            <?php echo esc_html(substr($form_data['first_name'] ?? 'A', 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="preview-info">
                    <h4><?php echo esc_html($full_name ?: __('New Agent', 'happy-place')); ?></h4>
                    
                    <?php if ($office_name): ?>
                        <div class="preview-office"><?php echo esc_html($office_name); ?></div>
                    <?php endif; ?>
                    
                    <div class="preview-contact">
                        <?php if (!empty($form_data['email'])): ?>
                            <span class="email"><?php echo esc_html($form_data['email']); ?></span>
                        <?php endif; ?>
                        
                        <?php if (!empty($form_data['phone'])): ?>
                            <span class="phone"><?php echo esc_html($form_data['phone']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="preview-details">
                <?php if (!empty($form_data['license_number'])): ?>
                    <div class="preview-license">
                        <strong><?php _e('License:', 'happy-place'); ?></strong>
                        <?php echo esc_html($form_data['license_number']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($form_data['years_experience'])): ?>
                    <div class="preview-experience">
                        <strong><?php _e('Experience:', 'happy-place'); ?></strong>
                        <?php echo esc_html($form_data['years_experience']); ?> years
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
                
                <?php if (!empty($form_data['short_bio'])): ?>
                    <div class="preview-bio">
                        <?php echo wp_kses_post(wp_trim_words($form_data['short_bio'], 25)); ?>
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
        
        // New agents may need approval
        if (!isset($form_data['post_id']) && !current_user_can('publish_posts')) {
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
            'first_name', 'last_name', 'email', 'phone', 'office',
            'license_number', 'license_state', 'years_experience',
            
            // Contact & social
            'website', 'linkedin_url', 'facebook_url', 'twitter_url', 'instagram_url',
            
            // Professional info
            'specializations', 'service_areas', 'languages', 'certifications',
            'bio', 'short_bio', 'achievements',
            
            // Team relationships
            'team_lead', 'team_members', 'assistant',
            
            // Media
            'profile_photo', 'cover_photo',
            
            // Settings
            'status', 'featured_agent', 'show_on_website',
            'email_notifications', 'text_notifications',
            
            // Statistics (admin only)
            'total_listings', 'active_listings', 'sold_listings',
            'total_sales_volume', 'average_days_on_market'
        ];
        
        foreach ($acf_fields as $field) {
            if (isset($form_data[$field])) {
                update_field($field, $form_data[$field], $post_id);
            }
        }
        
        // Handle nested social media fields
        if (isset($form_data['social_media'])) {
            update_field('social_media', $form_data['social_media'], $post_id);
        }
        
        // Handle nested contact preferences
        if (isset($form_data['contact_preferences'])) {
            update_field('contact_preferences', $form_data['contact_preferences'], $post_id);
        }
    }
    
    /**
     * Process profile photo upload
     *
     * @param int $post_id Post ID
     * @param int $photo_id Photo attachment ID
     */
    private function process_profile_photo($post_id, $photo_id) {
        if (is_numeric($photo_id)) {
            update_field('profile_photo', absint($photo_id), $post_id);
            
            // Set as featured image if no featured image exists
            if (!has_post_thumbnail($post_id)) {
                set_post_thumbnail($post_id, $photo_id);
            }
        }
    }
    
    /**
     * Create WordPress user account for new agent
     *
     * @param int $post_id Post ID
     * @param array $form_data Form data
     * @return int|WP_Error User ID on success, WP_Error on failure
     */
    private function create_wordpress_user($post_id, $form_data) {
        if (empty($form_data['email'])) {
            return new \WP_Error('no_email', 'Email required for user creation');
        }
        
        // Check if user already exists
        if (email_exists($form_data['email'])) {
            return new \WP_Error('email_exists', 'User with this email already exists');
        }
        
        // Generate username from email
        $username = sanitize_user(current(explode('@', $form_data['email'])));
        $original_username = $username;
        $counter = 1;
        
        // Ensure username is unique
        while (username_exists($username)) {
            $username = $original_username . $counter;
            $counter++;
        }
        
        // Generate secure password
        $password = wp_generate_password(12, true);
        
        // Create user
        $user_id = wp_create_user($username, $password, $form_data['email']);
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        // Set user role
        $user = new \WP_User($user_id);
        $user->set_role('estate_agent'); // Assuming this role exists
        
        // Update user meta
        wp_update_user([
            'ID' => $user_id,
            'first_name' => $form_data['first_name'] ?? '',
            'last_name' => $form_data['last_name'] ?? '',
            'display_name' => trim(($form_data['first_name'] ?? '') . ' ' . ($form_data['last_name'] ?? ''))
        ]);
        
        // Link agent post to user
        update_field('wordpress_user', $user_id, $post_id);
        update_user_meta($user_id, 'agent_post_id', $post_id);
        
        // Send welcome email
        $this->send_welcome_email($user_id, $username, $password, $form_data);
        
        return $user_id;
    }
    
    /**
     * Send welcome email to new agent
     *
     * @param int $user_id User ID
     * @param string $username Username
     * @param string $password Password
     * @param array $form_data Form data
     */
    private function send_welcome_email($user_id, $username, $password, $form_data) {
        $user = get_user_by('id', $user_id);
        $site_name = get_bloginfo('name');
        $login_url = wp_login_url();
        
        $subject = sprintf(__('Welcome to %s - Your Agent Account', 'happy-place'), $site_name);
        
        $message = sprintf(
            __("Welcome to %s!\n\nYour agent account has been created successfully.\n\nLogin Details:\nUsername: %s\nPassword: %s\n\nLogin URL: %s\n\nPlease login and change your password as soon as possible.\n\nIf you have any questions, please contact us.\n\nBest regards,\nThe %s Team", 'happy-place'),
            $site_name,
            $username,
            $password,
            $login_url,
            $site_name
        );
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Update agent statistics
     *
     * @param int $post_id Post ID
     */
    private function update_agent_statistics($post_id) {
        // Get current listings for this agent
        $listings = get_posts([
            'post_type' => 'listing',
            'meta_key' => 'listing_agent',
            'meta_value' => $post_id,
            'posts_per_page' => -1,
            'post_status' => ['publish', 'pending', 'draft']
        ]);
        
        $total_listings = count($listings);
        $active_listings = 0;
        $sold_listings = 0;
        $total_sales_volume = 0;
        $total_days_on_market = 0;
        $sold_count = 0;
        
        foreach ($listings as $listing) {
            $status = get_field('status', $listing->ID);
            $price = floatval(get_field('price', $listing->ID));
            
            if (in_array($status, ['active', 'pending', 'contingent'])) {
                $active_listings++;
            } elseif ($status === 'sold') {
                $sold_listings++;
                $sold_price = floatval(get_field('sold_price', $listing->ID)) ?: $price;
                $total_sales_volume += $sold_price;
                
                $days_on_market = intval(get_field('days_on_market', $listing->ID));
                if ($days_on_market > 0) {
                    $total_days_on_market += $days_on_market;
                    $sold_count++;
                }
            }
        }
        
        // Calculate average days on market
        $average_days_on_market = $sold_count > 0 ? round($total_days_on_market / $sold_count) : 0;
        
        // Update statistics
        update_field('total_listings', $total_listings, $post_id);
        update_field('active_listings', $active_listings, $post_id);
        update_field('sold_listings', $sold_listings, $post_id);
        update_field('total_sales_volume', $total_sales_volume, $post_id);
        update_field('average_days_on_market', $average_days_on_market, $post_id);
    }
    
    /**
     * Send notifications for new/updated agents
     *
     * @param int $post_id Post ID
     * @param array $form_data Form data
     * @param bool $is_new Whether this is a new agent
     */
    private function send_notifications($post_id, $form_data, $is_new) {
        $agent_name = trim(($form_data['first_name'] ?? '') . ' ' . ($form_data['last_name'] ?? ''));
        
        // Notify admin
        if ($is_new) {
            $admin_email = get_option('admin_email');
            $subject = sprintf(__('[%s] New Agent Registration: %s', 'happy-place'), get_bloginfo('name'), $agent_name);
            
            $message = sprintf(
                __("A new agent has been registered:\n\nName: %s\nEmail: %s\nPhone: %s\nOffice: %s\nLicense: %s\n\nView agent: %s", 'happy-place'),
                $agent_name,
                $form_data['email'] ?? '',
                $form_data['phone'] ?? '',
                isset($form_data['office']) ? get_the_title($form_data['office']) : 'Not specified',
                $form_data['license_number'] ?? 'Not provided',
                admin_url('post.php?post=' . $post_id . '&action=edit')
            );
            
            wp_mail($admin_email, $subject, $message);
        }
        
        // Notify office manager if office is specified
        if (isset($form_data['office']) && function_exists('hph_bridge_get_office_data')) {
            $office_data = hph_bridge_get_office_data($form_data['office']);
            $manager_email = $office_data['manager_email'] ?? '';
            
            if ($manager_email) {
                $subject = sprintf(
                    __('[%s] New Agent Assigned to Your Office: %s', 'happy-place'),
                    get_bloginfo('name'),
                    $agent_name
                );
                
                $message = sprintf(
                    __("A new agent has been assigned to your office:\n\n%s\nEmail: %s\nPhone: %s\n\nView agent profile: %s", 'happy-place'),
                    $agent_name,
                    $form_data['email'] ?? '',
                    $form_data['phone'] ?? '',
                    get_permalink($post_id)
                );
                
                wp_mail($manager_email, $subject, $message);
            }
        }
        
        // Fire action hooks
        do_action('hph_agent_saved', $post_id, $form_data, $is_new);
        
        if ($is_new) {
            do_action('hph_new_agent_registered', $post_id, $form_data);
        } else {
            do_action('hph_agent_updated', $post_id, $form_data);
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
}