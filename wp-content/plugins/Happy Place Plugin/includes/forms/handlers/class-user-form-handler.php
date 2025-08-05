<?php
/**
 * User Form Handler - Handles user registration and profile management
 * 
 * Manages user registration, login, profile updates, and role-based
 * account management for the Happy Place system.
 * 
 * @package HappyPlace
 * @subpackage Forms\Handlers
 */

namespace HappyPlace\Forms\Handlers;

use HappyPlace\Forms\Base_Form_Handler;

if (!defined('ABSPATH')) {
    exit;
}

class User_Form_Handler extends Base_Form_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('user', 'user');
    }
    
    /**
     * Setup validation rules for user forms
     */
    protected function setup_validation_rules() {
        $this->required_fields = [
            'user_email',
            'first_name',
            'last_name'
        ];
        
        $this->validation_rules = [
            'user_email' => [
                'email' => true,
                'sanitize' => 'sanitize_email'
            ],
            'user_login' => [
                'sanitize' => 'sanitize_user'
            ],
            'user_pass' => [
                'min_length' => 8,
                'sanitize' => [$this, 'sanitize_password']
            ],
            'confirm_password' => [
                'sanitize' => [$this, 'sanitize_password']
            ],
            'phone' => [
                'phone' => true,
                'sanitize' => 'sanitize_text_field'
            ],
            'website' => [
                'sanitize' => 'esc_url_raw'
            ],
            'date_of_birth' => [
                'sanitize' => [$this, 'sanitize_date']
            ],
            'interests' => [
                'sanitize' => [$this, 'sanitize_array_field']
            ],
            'preferred_contact_method' => [
                'sanitize' => 'sanitize_text_field'
            ],
            'price_range_min' => [
                'numeric' => true,
                'sanitize' => 'floatval'
            ],
            'price_range_max' => [
                'numeric' => true,
                'sanitize' => 'floatval'
            ]
        ];
    }
    
    /**
     * Process user form submission
     *
     * @param array $form_data Validated form data
     * @return int|WP_Error User ID on success, WP_Error on failure
     */
    protected function process_submission($form_data) {
        $user_id = isset($form_data['user_id']) ? absint($form_data['user_id']) : 0;
        $is_new = !$user_id;
        $form_type = $form_data['form_subtype'] ?? 'profile';
        
        if ($is_new) {
            // Handle new user registration
            $result = $this->process_user_registration($form_data);
        } else {
            // Handle user profile update
            $result = $this->process_user_update($user_id, $form_data);
        }
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $user_id = $result;
        
        // Save user meta fields
        $this->save_user_meta($user_id, $form_data);
        
        // Handle role assignment for new users
        if ($is_new && isset($form_data['user_role'])) {
            $this->assign_user_role($user_id, $form_data['user_role']);
        }
        
        // Handle profile photo
        if (isset($form_data['profile_photo']) && !empty($form_data['profile_photo'])) {
            $this->process_profile_photo($user_id, $form_data['profile_photo']);
        }
        
        // Send notifications
        $this->send_notifications($user_id, $form_data, $is_new);
        
        // Handle special actions based on form type
        $this->handle_form_type_actions($user_id, $form_data, $form_type);
        
        return $user_id;
    }
    
    /**
     * Custom validation for user data
     *
     * @param array $form_data Form data
     * @return array Validation errors
     */
    protected function custom_validation($form_data) {
        $errors = [];
        $is_new = !isset($form_data['user_id']) || empty($form_data['user_id']);
        
        // Email uniqueness check for new users
        if ($is_new && isset($form_data['user_email'])) {
            if (email_exists($form_data['user_email'])) {
                $errors['user_email'] = __('This email address is already registered', 'happy-place');
            }
        }
        
        // Username uniqueness check for new users
        if ($is_new && isset($form_data['user_login']) && !empty($form_data['user_login'])) {
            if (username_exists($form_data['user_login'])) {
                $errors['user_login'] = __('This username is already taken', 'happy-place');
            }
            
            // Username format validation
            if (!validate_username($form_data['user_login'])) {
                $errors['user_login'] = __('Username contains invalid characters', 'happy-place');
            }
        }
        
        // Password validation for new users
        if ($is_new && isset($form_data['user_pass'])) {
            $password = $form_data['user_pass'];
            
            // Minimum length check
            if (strlen($password) < 8) {
                $errors['user_pass'] = __('Password must be at least 8 characters long', 'happy-place');
            }
            
            // Strength validation
            if (!$this->validate_password_strength($password)) {
                $errors['user_pass'] = __('Password must contain at least one uppercase letter, one lowercase letter, and one number', 'happy-place');
            }
        }
        
        // Confirm password validation
        if (isset($form_data['user_pass'], $form_data['confirm_password'])) {
            if ($form_data['user_pass'] !== $form_data['confirm_password']) {
                $errors['confirm_password'] = __('Passwords do not match', 'happy-place');
            }
        }
        
        // Age validation (must be 18+ for real estate)
        if (isset($form_data['date_of_birth'])) {
            $birth_date = strtotime($form_data['date_of_birth']);
            $age = floor((time() - $birth_date) / (365.25 * 24 * 3600));
            
            if ($age < 18) {
                $errors['date_of_birth'] = __('You must be at least 18 years old to register', 'happy-place');
            }
            
            if ($age > 120) {
                $errors['date_of_birth'] = __('Please enter a valid birth date', 'happy-place');
            }
        }
        
        // Price range validation
        if (isset($form_data['price_range_min'], $form_data['price_range_max'])) {
            $min_price = floatval($form_data['price_range_min']);
            $max_price = floatval($form_data['price_range_max']);
            
            if ($min_price > 0 && $max_price > 0 && $min_price >= $max_price) {
                $errors['price_range_max'] = __('Maximum price must be greater than minimum price', 'happy-place');
            }
        }
        
        // Terms acceptance for new registrations
        if ($is_new && empty($form_data['accept_terms'])) {
            $errors['accept_terms'] = __('You must accept the terms and conditions to register', 'happy-place');
        }
        
        // Privacy policy acceptance for new registrations
        if ($is_new && empty($form_data['accept_privacy'])) {
            $errors['accept_privacy'] = __('You must accept the privacy policy to register', 'happy-place');
        }
        
        return $errors;
    }
    
    /**
     * Render user preview
     *
     * @param array $form_data Form data
     */
    protected function render_preview($form_data) {
        $full_name = trim(($form_data['first_name'] ?? '') . ' ' . ($form_data['last_name'] ?? ''));
        $user_role = $form_data['user_role'] ?? 'client';
        
        ?>
        <div class="user-preview">
            <div class="preview-header">
                <div class="preview-avatar">
                    <?php if (!empty($form_data['profile_photo'])): ?>
                        <img src="<?php echo esc_url(wp_get_attachment_url($form_data['profile_photo'])); ?>" 
                             alt="<?php echo esc_attr($full_name); ?>" class="user-photo">
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            <?php echo esc_html(substr($form_data['first_name'] ?? 'U', 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="preview-info">
                    <h4><?php echo esc_html($full_name ?: __('New User', 'happy-place')); ?></h4>
                    <div class="preview-role"><?php echo esc_html(ucfirst($user_role)); ?></div>
                    
                    <?php if (!empty($form_data['user_email'])): ?>
                        <div class="preview-email"><?php echo esc_html($form_data['user_email']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="preview-details">
                <?php if (!empty($form_data['phone'])): ?>
                    <div class="preview-phone">
                        <strong><?php _e('Phone:', 'happy-place'); ?></strong>
                        <?php echo esc_html($form_data['phone']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($form_data['interests'])): ?>
                    <div class="preview-interests">
                        <strong><?php _e('Interests:', 'happy-place'); ?></strong>
                        <?php
                        $interests = is_array($form_data['interests']) ? $form_data['interests'] : [$form_data['interests']];
                        echo esc_html(implode(', ', array_slice($interests, 0, 3)));
                        if (count($interests) > 3) {
                            echo ' <em>+' . (count($interests) - 3) . ' more</em>';
                        }
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($form_data['price_range_min']) || !empty($form_data['price_range_max'])): ?>
                    <div class="preview-price-range">
                        <strong><?php _e('Price Range:', 'happy-place'); ?></strong>
                        $<?php echo esc_html(number_format($form_data['price_range_min'] ?? 0)); ?> - 
                        $<?php echo esc_html(number_format($form_data['price_range_max'] ?? 0)); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($form_data['bio'])): ?>
                    <div class="preview-bio">
                        <?php echo wp_kses_post(wp_trim_words($form_data['bio'], 25)); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Process new user registration
     *
     * @param array $form_data Form data
     * @return int|WP_Error User ID on success, WP_Error on failure
     */
    private function process_user_registration($form_data) {
        // Generate username if not provided
        $username = $form_data['user_login'] ?? '';
        if (empty($username)) {
            $username = sanitize_user(current(explode('@', $form_data['user_email'])));
            $original_username = $username;
            $counter = 1;
            
            while (username_exists($username)) {
                $username = $original_username . $counter;
                $counter++;
            }
        }
        
        // Generate password if not provided
        $password = !empty($form_data['user_pass']) ? 
            $form_data['user_pass'] : 
            wp_generate_password(12, true);
        
        // Create user data array
        $user_data = [
            'user_login' => $username,
            'user_email' => $form_data['user_email'],
            'user_pass' => $password,
            'first_name' => $form_data['first_name'] ?? '',
            'last_name' => $form_data['last_name'] ?? '',
            'display_name' => trim(($form_data['first_name'] ?? '') . ' ' . ($form_data['last_name'] ?? '')),
            'description' => $form_data['bio'] ?? '',
            'user_url' => $form_data['website'] ?? '',
            'role' => $form_data['user_role'] ?? 'client'
        ];
        
        // Create the user
        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        // Send welcome email if password was generated
        if (empty($form_data['user_pass'])) {
            $this->send_welcome_email($user_id, $username, $password);
        }
        
        return $user_id;
    }
    
    /**
     * Process user profile update
     *
     * @param int $user_id User ID
     * @param array $form_data Form data
     * @return int|WP_Error User ID on success, WP_Error on failure
     */
    private function process_user_update($user_id, $form_data) {
        // Check if current user can edit this profile
        if (!current_user_can('edit_user', $user_id) && get_current_user_id() !== $user_id) {
            return new \WP_Error('permission_denied', __('You do not have permission to edit this profile', 'happy-place'));
        }
        
        // Prepare update data
        $user_data = [
            'ID' => $user_id,
            'first_name' => $form_data['first_name'] ?? '',
            'last_name' => $form_data['last_name'] ?? '',
            'display_name' => trim(($form_data['first_name'] ?? '') . ' ' . ($form_data['last_name'] ?? '')),
            'description' => $form_data['bio'] ?? '',
            'user_url' => $form_data['website'] ?? ''
        ];
        
        // Only allow email changes by admins or if user confirms with password
        if (current_user_can('edit_users') || !empty($form_data['current_password'])) {
            if (isset($form_data['user_email'])) {
                $user_data['user_email'] = $form_data['user_email'];
            }
        }
        
        // Handle password change
        if (!empty($form_data['new_password'])) {
            // Verify current password for security
            if (!current_user_can('edit_users')) {
                $user = get_user_by('id', $user_id);
                if (!wp_check_password($form_data['current_password'] ?? '', $user->user_pass, $user_id)) {
                    return new \WP_Error('invalid_password', __('Current password is incorrect', 'happy-place'));
                }
            }
            
            $user_data['user_pass'] = $form_data['new_password'];
        }
        
        // Update the user
        $result = wp_update_user($user_data);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return $user_id;
    }
    
    /**
     * Save user meta fields
     *
     * @param int $user_id User ID
     * @param array $form_data Form data
     */
    private function save_user_meta($user_id, $form_data) {
        $meta_fields = [
            // Contact info
            'phone', 'mobile_phone', 'work_phone',
            
            // Personal info
            'date_of_birth', 'gender', 'marital_status',
            
            // Address
            'address', 'city', 'state', 'zip_code', 'country',
            
            // Preferences
            'preferred_contact_method', 'contact_time_preference',
            'email_notifications', 'sms_notifications',
            
            // Real estate interests
            'interests', 'property_types', 'price_range_min', 'price_range_max',
            'preferred_locations', 'timeline', 'financing_preapproved',
            
            // Agent assignment
            'preferred_agent', 'assigned_agent',
            
            // Social media
            'facebook_url', 'twitter_url', 'linkedin_url', 'instagram_url',
            
            // Emergency contact
            'emergency_contact_name', 'emergency_contact_phone',
            
            // Professional info (for agent/broker roles)
            'license_number', 'license_state', 'brokerage',
            
            // Privacy settings
            'profile_visibility', 'show_contact_info', 'allow_agent_contact'
        ];
        
        foreach ($meta_fields as $field) {
            if (isset($form_data[$field])) {
                update_user_meta($user_id, $field, $form_data[$field]);
            }
        }
        
        // Handle preferences array
        if (isset($form_data['preferences']) && is_array($form_data['preferences'])) {
            update_user_meta($user_id, 'user_preferences', $form_data['preferences']);
        }
        
        // Save registration source
        if (!get_user_meta($user_id, 'registration_source', true)) {
            update_user_meta($user_id, 'registration_source', $form_data['registration_source'] ?? 'website');
            update_user_meta($user_id, 'registration_date', current_time('mysql'));
        }
    }
    
    /**
     * Assign user role
     *
     * @param int $user_id User ID
     * @param string $role User role
     */
    private function assign_user_role($user_id, $role) {
        $user = new \WP_User($user_id);
        
        // Validate role exists
        $valid_roles = ['client', 'agent', 'broker', 'office_manager', 'administrator'];
        if (in_array($role, $valid_roles)) {
            $user->set_role($role);
        }
    }
    
    /**
     * Process profile photo upload
     *
     * @param int $user_id User ID
     * @param int $photo_id Photo attachment ID
     */
    private function process_profile_photo($user_id, $photo_id) {
        if (is_numeric($photo_id)) {
            update_user_meta($user_id, 'profile_photo', absint($photo_id));
        }
    }
    
    /**
     * Handle form type specific actions
     *
     * @param int $user_id User ID
     * @param array $form_data Form data
     * @param string $form_type Form type
     */
    private function handle_form_type_actions($user_id, $form_data, $form_type) {
        switch ($form_type) {
            case 'client_registration':
                // Auto-assign to available agent
                $this->auto_assign_agent($user_id, $form_data);
                break;
                
            case 'agent_application':
                // Set status to pending review
                update_user_meta($user_id, 'application_status', 'pending');
                // Notify admin of new agent application
                $this->notify_admin_of_agent_application($user_id, $form_data);
                break;
                
            case 'newsletter_signup':
                // Add to newsletter list
                update_user_meta($user_id, 'newsletter_subscriber', true);
                update_user_meta($user_id, 'newsletter_signup_date', current_time('mysql'));
                break;
        }
    }
    
    /**
     * Auto-assign agent to new client
     *
     * @param int $user_id User ID
     * @param array $form_data Form data
     */
    private function auto_assign_agent($user_id, $form_data) {
        // Get available agents based on location or specialization
        $preferred_location = $form_data['preferred_locations'] ?? '';
        
        $agent_query = [
            'post_type' => 'agent',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'orderby' => 'rand'
        ];
        
        if (!empty($preferred_location)) {
            $agent_query['meta_query'] = [
                [
                    'key' => 'service_areas',
                    'value' => $preferred_location,
                    'compare' => 'LIKE'
                ]
            ];
        }
        
        $agents = get_posts($agent_query);
        
        if (!empty($agents)) {
            update_user_meta($user_id, 'assigned_agent', $agents[0]->ID);
        }
    }
    
    /**
     * Notify admin of new agent application
     *
     * @param int $user_id User ID
     * @param array $form_data Form data
     */
    private function notify_admin_of_agent_application($user_id, $form_data) {
        $admin_email = get_option('admin_email');
        $user = get_user_by('id', $user_id);
        
        $subject = sprintf(__('[%s] New Agent Application: %s', 'happy-place'), get_bloginfo('name'), $user->display_name);
        
        $message = sprintf(
            __("A new agent application has been submitted:\n\nName: %s\nEmail: %s\nPhone: %s\nLicense: %s\n\nReview application: %s", 'happy-place'),
            $user->display_name,
            $user->user_email,
            get_user_meta($user_id, 'phone', true),
            get_user_meta($user_id, 'license_number', true),
            admin_url('user-edit.php?user_id=' . $user_id)
        );
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Send welcome email to new user
     *
     * @param int $user_id User ID
     * @param string $username Username
     * @param string $password Password
     */
    private function send_welcome_email($user_id, $username, $password) {
        $user = get_user_by('id', $user_id);
        $site_name = get_bloginfo('name');
        $login_url = wp_login_url();
        
        $subject = sprintf(__('Welcome to %s', 'happy-place'), $site_name);
        
        $message = sprintf(
            __("Welcome to %s!\n\nYour account has been created successfully.\n\nLogin Details:\nUsername: %s\nPassword: %s\n\nLogin URL: %s\n\nPlease login and update your profile as needed.\n\nBest regards,\nThe %s Team", 'happy-place'),
            $site_name,
            $username,
            $password,
            $login_url,
            $site_name
        );
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Send notifications for new/updated users
     *
     * @param int $user_id User ID
     * @param array $form_data Form data
     * @param bool $is_new Whether this is a new user
     */
    private function send_notifications($user_id, $form_data, $is_new) {
        $user = get_user_by('id', $user_id);
        
        // Fire action hooks
        do_action('hph_user_saved', $user_id, $form_data, $is_new);
        
        if ($is_new) {
            do_action('hph_new_user_registered', $user_id, $form_data);
        } else {
            do_action('hph_user_profile_updated', $user_id, $form_data);
        }
    }
    
    /**
     * Validate password strength
     *
     * @param string $password Password
     * @return bool True if strong enough
     */
    private function validate_password_strength($password) {
        // At least one uppercase, one lowercase, one number
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $password);
    }
    
    /**
     * Sanitize password field
     *
     * @param string $password Password
     * @return string Sanitized password
     */
    public function sanitize_password($password) {
        // Don't sanitize passwords, just return as-is for validation
        return $password;
    }
    
    /**
     * Sanitize date field
     *
     * @param string $date Date string
     * @return string Sanitized date
     */
    public function sanitize_date($date) {
        $timestamp = strtotime($date);
        return $timestamp ? date('Y-m-d', $timestamp) : '';
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