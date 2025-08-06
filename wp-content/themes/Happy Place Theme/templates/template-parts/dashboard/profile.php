<?php
/**
 * Dashboard Profile Section Template
 * 
 * Allows agents to edit their profile information, contact details, and preferences
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get agent data (available from parent template)
$current_agent_id = $current_agent_id ?? get_current_user_id();
$current_user = wp_get_current_user();

// Handle form submission
if (isset($_POST['hph_update_profile']) && wp_verify_nonce($_POST['hph_profile_nonce'], 'update_agent_profile')) {
    $updated = hph_update_agent_profile($current_agent_id, $_POST);
    
    if ($updated) {
        echo '<script>document.addEventListener("DOMContentLoaded", function() { window.HphDashboard.showToast("Profile updated successfully!", "success"); });</script>';
    } else {
        echo '<script>document.addEventListener("DOMContentLoaded", function() { window.HphDashboard.showToast("Error updating profile. Please try again.", "error"); });</script>';
    }
}

// Get current profile data
$profile_data = [];
if (function_exists('get_fields')) {
    $user_fields = get_fields('user_' . $current_agent_id);
    $profile_data = is_array($user_fields) ? $user_fields : [];
}

// Merge with user data
$profile_data = array_merge([
    'first_name' => $current_user->first_name,
    'last_name' => $current_user->last_name,
    'email' => $current_user->user_email,
    'display_name' => $current_user->display_name,
    'description' => $current_user->description,
], $profile_data);

// Default values
$defaults = [
    'agent_title' => '',
    'agent_phone' => '',
    'agent_mobile' => '',
    'agent_office_phone' => '',
    'agent_fax' => '',
    'agent_website' => '',
    'agent_license' => '',
    'agent_specialties' => [],
    'agent_languages' => [],
    'agent_social_facebook' => '',
    'agent_social_twitter' => '',
    'agent_social_instagram' => '',
    'agent_social_linkedin' => '',
    'agent_social_youtube' => '',
    'agent_bio' => '',
    'agent_experience_years' => '',
    'agent_brokerage' => '',
    'agent_office_address' => '',
    'agent_service_areas' => [],
    'notification_new_leads' => true,
    'notification_listing_inquiries' => true,
    'notification_appointment_reminders' => true,
    'notification_marketing_updates' => false,
    'privacy_show_phone' => true,
    'privacy_show_email' => true,
    'privacy_allow_contact_form' => true,
];

$profile_data = array_merge($defaults, $profile_data);

// Get available options
$specialties = [
    'residential' => __('Residential Sales', 'happy-place'),
    'commercial' => __('Commercial Real Estate', 'happy-place'),
    'luxury' => __('Luxury Properties', 'happy-place'),
    'investment' => __('Investment Properties', 'happy-place'),
    'first_time_buyers' => __('First-Time Buyers', 'happy-place'),
    'relocation' => __('Relocation Services', 'happy-place'),
    'new_construction' => __('New Construction', 'happy-place'),
    'foreclosures' => __('Foreclosures', 'happy-place'),
    'condos' => __('Condominiums', 'happy-place'),
    'land' => __('Land/Lots', 'happy-place')
];

$languages = [
    'english' => __('English', 'happy-place'),
    'spanish' => __('Spanish', 'happy-place'),
    'french' => __('French', 'happy-place'),
    'german' => __('German', 'happy-place'),
    'italian' => __('Italian', 'happy-place'),
    'portuguese' => __('Portuguese', 'happy-place'),
    'mandarin' => __('Mandarin', 'happy-place'),
    'japanese' => __('Japanese', 'happy-place'),
    'korean' => __('Korean', 'happy-place'),
    'arabic' => __('Arabic', 'happy-place')
];
?>

<div class="hph-profile-section">
    
    <!-- Profile Header -->
    <div class="hph-profile-header">
        <div class="hph-profile-avatar-section">
            <?php 
            $current_avatar = $profile_data['agent_photo'] ?? '';
            if ($current_avatar && isset($current_avatar['url'])) : ?>
                <img src="<?php echo esc_url($current_avatar['url']); ?>" 
                     alt="<?php echo esc_attr($profile_data['display_name']); ?>" 
                     class="hph-profile-avatar"
                     id="hph-avatar-preview">
            <?php else : ?>
                <div class="hph-profile-avatar hph-profile-avatar--placeholder" id="hph-avatar-preview">
                    <i class="fas fa-user"></i>
                </div>
            <?php endif; ?>
            
            <label for="hph-avatar-upload" class="hph-avatar-upload">
                <i class="fas fa-camera"></i>
                <?php esc_html_e('Change Photo', 'happy-place'); ?>
                <input type="file" 
                       id="hph-avatar-upload" 
                       name="agent_photo" 
                       accept="image/*" 
                       style="display: none;"
                       data-preview="hph-avatar-preview">
            </label>
        </div>
        
        <div class="hph-profile-info">
            <h2 class="hph-profile-name">
                <?php echo esc_html($profile_data['display_name'] ?: ($profile_data['first_name'] . ' ' . $profile_data['last_name'])); ?>
            </h2>
            <p class="hph-profile-title">
                <?php echo esc_html($profile_data['agent_title'] ?: __('Real Estate Agent', 'happy-place')); ?>
            </p>
            <div class="hph-profile-meta">
                <span><i class="fas fa-envelope"></i> <?php echo esc_html($profile_data['email']); ?></span>
                <?php if (!empty($profile_data['agent_phone'])) : ?>
                    <span><i class="fas fa-phone"></i> <?php echo esc_html($profile_data['agent_phone']); ?></span>
                <?php endif; ?>
                <span><i class="fas fa-calendar"></i> <?php echo esc_html(human_time_diff(strtotime($current_user->user_registered))); ?> <?php esc_html_e('with Happy Place', 'happy-place'); ?></span>
            </div>
        </div>
    </div>

    <!-- Profile Form -->
    <form method="POST" action="" enctype="multipart/form-data" class="hph-dashboard-form">
        <?php wp_nonce_field('update_agent_profile', 'hph_profile_nonce'); ?>
        
        <!-- Personal Information Section -->
        <div class="hph-form-section">
            <h3 class="hph-form-section-title">
                <div class="hph-form-section-icon">
                    <i class="fas fa-user"></i>
                </div>
                <?php esc_html_e('Personal Information', 'happy-place'); ?>
            </h3>
            
            <div class="hph-form-grid">
                <div class="hph-form-group hph-form-group--col-6">
                    <label for="first_name" class="hph-form-label hph-form-label--required">
                        <?php esc_html_e('First Name', 'happy-place'); ?>
                    </label>
                    <input type="text" 
                           id="first_name" 
                           name="first_name" 
                           value="<?php echo esc_attr($profile_data['first_name']); ?>" 
                           class="hph-form-input" 
                           required>
                </div>
                
                <div class="hph-form-group hph-form-group--col-6">
                    <label for="last_name" class="hph-form-label hph-form-label--required">
                        <?php esc_html_e('Last Name', 'happy-place'); ?>
                    </label>
                    <input type="text" 
                           id="last_name" 
                           name="last_name" 
                           value="<?php echo esc_attr($profile_data['last_name']); ?>" 
                           class="hph-form-input" 
                           required>
                </div>
                
                <div class="hph-form-group hph-form-group--col-6">
                    <label for="display_name" class="hph-form-label">
                        <?php esc_html_e('Display Name', 'happy-place'); ?>
                    </label>
                    <input type="text" 
                           id="display_name" 
                           name="display_name" 
                           value="<?php echo esc_attr($profile_data['display_name']); ?>" 
                           class="hph-form-input">
                    <p class="hph-form-help">
                        <?php esc_html_e('This is how your name will appear to clients and on listings.', 'happy-place'); ?>
                    </p>
                </div>
                
                <div class="hph-form-group hph-form-group--col-6">
                    <label for="agent_title" class="hph-form-label">
                        <?php esc_html_e('Professional Title', 'happy-place'); ?>
                    </label>
                    <input type="text" 
                           id="agent_title" 
                           name="agent_title" 
                           value="<?php echo esc_attr($profile_data['agent_title']); ?>" 
                           class="hph-form-input"
                           placeholder="<?php esc_attr_e('e.g., Senior Real Estate Agent', 'happy-place'); ?>">
                </div>
                
                <div class="hph-form-group hph-form-group--col-12">
                    <label for="agent_bio" class="hph-form-label">
                        <?php esc_html_e('Professional Bio', 'happy-place'); ?>
                    </label>
                    <textarea id="agent_bio" 
                              name="agent_bio" 
                              class="hph-form-textarea" 
                              rows="5"
                              placeholder="<?php esc_attr_e('Tell clients about your experience, expertise, and what makes you unique...', 'happy-place'); ?>"><?php echo esc_textarea($profile_data['agent_bio']); ?></textarea>
                    <p class="hph-form-help">
                        <?php esc_html_e('This will appear on your agent profile and listings. Keep it professional and engaging.', 'happy-place'); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Contact Information Section -->
        <div class="hph-form-section">
            <h3 class="hph-form-section-title">
                <div class="hph-form-section-icon">
                    <i class="fas fa-phone"></i>
                </div>
                <?php esc_html_e('Contact Information', 'happy-place'); ?>
            </h3>
            
            <div class="hph-form-grid">
                <div class="hph-form-group hph-form-group--col-6">
                    <label for="email" class="hph-form-label hph-form-label--required">
                        <?php esc_html_e('Email Address', 'happy-place'); ?>
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="<?php echo esc_attr($profile_data['email']); ?>" 
                           class="hph-form-input" 
                           required>
                </div>
                
                <div class="hph-form-group hph-form-group--col-6">
                    <label for="agent_phone" class="hph-form-label">
                        <?php esc_html_e('Primary Phone', 'happy-place'); ?>
                    </label>
                    <input type="tel" 
                           id="agent_phone" 
                           name="agent_phone" 
                           value="<?php echo esc_attr($profile_data['agent_phone']); ?>" 
                           class="hph-form-input"
                           placeholder="(555) 123-4567">
                </div>
                
                <div class="hph-form-group hph-form-group--col-6">
                    <label for="agent_mobile" class="hph-form-label">
                        <?php esc_html_e('Mobile Phone', 'happy-place'); ?>
                    </label>
                    <input type="tel" 
                           id="agent_mobile" 
                           name="agent_mobile" 
                           value="<?php echo esc_attr($profile_data['agent_mobile']); ?>" 
                           class="hph-form-input"
                           placeholder="(555) 123-4567">
                </div>
                
                <div class="hph-form-group hph-form-group--col-6">
                    <label for="agent_office_phone" class="hph-form-label">
                        <?php esc_html_e('Office Phone', 'happy-place'); ?>
                    </label>
                    <input type="tel" 
                           id="agent_office_phone" 
                           name="agent_office_phone" 
                           value="<?php echo esc_attr($profile_data['agent_office_phone']); ?>" 
                           class="hph-form-input"
                           placeholder="(555) 123-4567">
                </div>
                
                <div class="hph-form-group hph-form-group--col-6">
                    <label for="agent_website" class="hph-form-label">
                        <?php esc_html_e('Personal Website', 'happy-place'); ?>
                    </label>
                    <input type="url" 
                           id="agent_website" 
                           name="agent_website" 
                           value="<?php echo esc_attr($profile_data['agent_website']); ?>" 
                           class="hph-form-input"
                           placeholder="https://yourwebsite.com">
                </div>
                
                <div class="hph-form-group hph-form-group--col-6">
                    <label for="agent_license" class="hph-form-label">
                        <?php esc_html_e('License Number', 'happy-place'); ?>
                    </label>
                    <input type="text" 
                           id="agent_license" 
                           name="agent_license" 
                           value="<?php echo esc_attr($profile_data['agent_license']); ?>" 
                           class="hph-form-input"
                           placeholder="RE123456789">
                </div>
            </div>
        </div>

        <!-- Professional Information Section -->
        <div class="hph-form-section">
            <h3 class="hph-form-section-title">
                <div class="hph-form-section-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <?php esc_html_e('Professional Information', 'happy-place'); ?>
            </h3>
            
            <div class="hph-form-grid">
                <div class="hph-form-group hph-form-group--col-6">
                    <label for="agent_experience_years" class="hph-form-label">
                        <?php esc_html_e('Years of Experience', 'happy-place'); ?>
                    </label>
                    <input type="number" 
                           id="agent_experience_years" 
                           name="agent_experience_years" 
                           value="<?php echo esc_attr($profile_data['agent_experience_years']); ?>" 
                           class="hph-form-input"
                           min="0" 
                           max="50">
                </div>
                
                <div class="hph-form-group hph-form-group--col-6">
                    <label for="agent_brokerage" class="hph-form-label">
                        <?php esc_html_e('Brokerage/Company', 'happy-place'); ?>
                    </label>
                    <input type="text" 
                           id="agent_brokerage" 
                           name="agent_brokerage" 
                           value="<?php echo esc_attr($profile_data['agent_brokerage']); ?>" 
                           class="hph-form-input"
                           placeholder="Happy Place Real Estate">
                </div>
                
                <div class="hph-form-group hph-form-group--col-12">
                    <label for="agent_office_address" class="hph-form-label">
                        <?php esc_html_e('Office Address', 'happy-place'); ?>
                    </label>
                    <textarea id="agent_office_address" 
                              name="agent_office_address" 
                              class="hph-form-textarea" 
                              rows="3"
                              placeholder="<?php esc_attr_e('123 Main Street, Suite 100, City, State 12345', 'happy-place'); ?>"><?php echo esc_textarea($profile_data['agent_office_address']); ?></textarea>
                </div>
                
                <div class="hph-form-group hph-form-group--col-6">
                    <label class="hph-form-label">
                        <?php esc_html_e('Specialties', 'happy-place'); ?>
                    </label>
                    <div class="hph-checkbox-group">
                        <?php foreach ($specialties as $key => $label) : 
                            $checked = in_array($key, (array)$profile_data['agent_specialties']);
                        ?>
                            <div class="hph-checkbox-item">
                                <input type="checkbox" 
                                       id="specialty_<?php echo esc_attr($key); ?>" 
                                       name="agent_specialties[]" 
                                       value="<?php echo esc_attr($key); ?>"
                                       class="hph-checkbox-input"
                                       <?php checked($checked); ?>>
                                <label for="specialty_<?php echo esc_attr($key); ?>" class="hph-checkbox-label">
                                    <?php echo esc_html($label); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="hph-form-group hph-form-group--col-6">
                    <label class="hph-form-label">
                        <?php esc_html_e('Languages Spoken', 'happy-place'); ?>
                    </label>
                    <div class="hph-checkbox-group">
                        <?php foreach ($languages as $key => $label) : 
                            $checked = in_array($key, (array)$profile_data['agent_languages']);
                        ?>
                            <div class="hph-checkbox-item">
                                <input type="checkbox" 
                                       id="language_<?php echo esc_attr($key); ?>" 
                                       name="agent_languages[]" 
                                       value="<?php echo esc_attr($key); ?>"
                                       class="hph-checkbox-input"
                                       <?php checked($checked); ?>>
                                <label for="language_<?php echo esc_attr($key); ?>" class="hph-checkbox-label">
                                    <?php echo esc_html($label); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Social Media Section -->
        <div class="hph-form-section">
            <h3 class="hph-form-section-title">
                <div class="hph-form-section-icon">
                    <i class="fas fa-share-alt"></i>
                </div>
                <?php esc_html_e('Social Media', 'happy-place'); ?>
            </h3>
            
            <div class="hph-form-grid">
                <div class="hph-form-group hph-form-group--col-6">
                    <label for="agent_social_facebook" class="hph-form-label">
                        <i class="fab fa-facebook"></i> <?php esc_html_e('Facebook', 'happy-place'); ?>
                    </label>
                    <input type="url" 
                           id="agent_social_facebook" 
                           name="agent_social_facebook" 
                           value="<?php echo esc_attr($profile_data['agent_social_facebook']); ?>" 
                           class="hph-form-input"
                           placeholder="https://facebook.com/yourprofile">
                </div>
                
                <div class="hph-form-group hph-form-group--col-6">
                    <label for="agent_social_twitter" class="hph-form-label">
                        <i class="fab fa-twitter"></i> <?php esc_html_e('Twitter', 'happy-place'); ?>
                    </label>
                    <input type="url" 
                           id="agent_social_twitter" 
                           name="agent_social_twitter" 
                           value="<?php echo esc_attr($profile_data['agent_social_twitter']); ?>" 
                           class="hph-form-input"
                           placeholder="https://twitter.com/yourhandle">
                </div>
                
                <div class="hph-form-group hph-form-group--col-6">
                    <label for="agent_social_instagram" class="hph-form-label">
                        <i class="fab fa-instagram"></i> <?php esc_html_e('Instagram', 'happy-place'); ?>
                    </label>
                    <input type="url" 
                           id="agent_social_instagram" 
                           name="agent_social_instagram" 
                           value="<?php echo esc_attr($profile_data['agent_social_instagram']); ?>" 
                           class="hph-form-input"
                           placeholder="https://instagram.com/yourprofile">
                </div>
                
                <div class="hph-form-group hph-form-group--col-6">
                    <label for="agent_social_linkedin" class="hph-form-label">
                        <i class="fab fa-linkedin"></i> <?php esc_html_e('LinkedIn', 'happy-place'); ?>
                    </label>
                    <input type="url" 
                           id="agent_social_linkedin" 
                           name="agent_social_linkedin" 
                           value="<?php echo esc_attr($profile_data['agent_social_linkedin']); ?>" 
                           class="hph-form-input"
                           placeholder="https://linkedin.com/in/yourprofile">
                </div>
            </div>
        </div>

        <!-- Notification Preferences Section -->
        <div class="hph-form-section">
            <h3 class="hph-form-section-title">
                <div class="hph-form-section-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <?php esc_html_e('Notification Preferences', 'happy-place'); ?>
            </h3>
            
            <div class="hph-form-grid">
                <div class="hph-form-group hph-form-group--col-12">
                    <div class="hph-notification-settings">
                        <div class="hph-notification-item">
                            <div class="hph-notification-info">
                                <h4 class="hph-notification-title"><?php esc_html_e('New Leads', 'happy-place'); ?></h4>
                                <p class="hph-notification-description"><?php esc_html_e('Get notified when new leads contact you', 'happy-place'); ?></p>
                            </div>
                            <label class="hph-toggle">
                                <input type="hidden" name="notification_new_leads" value="0">
                                <input type="checkbox" 
                                       name="notification_new_leads" 
                                       value="1" 
                                       <?php checked($profile_data['notification_new_leads']); ?>>
                                <span class="hph-toggle-slider"></span>
                            </label>
                        </div>
                        
                        <div class="hph-notification-item">
                            <div class="hph-notification-info">
                                <h4 class="hph-notification-title"><?php esc_html_e('Listing Inquiries', 'happy-place'); ?></h4>
                                <p class="hph-notification-description"><?php esc_html_e('Get notified about inquiries on your listings', 'happy-place'); ?></p>
                            </div>
                            <label class="hph-toggle">
                                <input type="hidden" name="notification_listing_inquiries" value="0">
                                <input type="checkbox" 
                                       name="notification_listing_inquiries" 
                                       value="1" 
                                       <?php checked($profile_data['notification_listing_inquiries']); ?>>
                                <span class="hph-toggle-slider"></span>
                            </label>
                        </div>
                        
                        <div class="hph-notification-item">
                            <div class="hph-notification-info">
                                <h4 class="hph-notification-title"><?php esc_html_e('Appointment Reminders', 'happy-place'); ?></h4>
                                <p class="hph-notification-description"><?php esc_html_e('Get reminders about upcoming appointments', 'happy-place'); ?></p>
                            </div>
                            <label class="hph-toggle">
                                <input type="hidden" name="notification_appointment_reminders" value="0">
                                <input type="checkbox" 
                                       name="notification_appointment_reminders" 
                                       value="1" 
                                       <?php checked($profile_data['notification_appointment_reminders']); ?>>
                                <span class="hph-toggle-slider"></span>
                            </label>
                        </div>
                        
                        <div class="hph-notification-item">
                            <div class="hph-notification-info">
                                <h4 class="hph-notification-title"><?php esc_html_e('Marketing Updates', 'happy-place'); ?></h4>
                                <p class="hph-notification-description"><?php esc_html_e('Receive tips and updates about marketing', 'happy-place'); ?></p>
                            </div>
                            <label class="hph-toggle">
                                <input type="hidden" name="notification_marketing_updates" value="0">
                                <input type="checkbox" 
                                       name="notification_marketing_updates" 
                                       value="1" 
                                       <?php checked($profile_data['notification_marketing_updates']); ?>>
                                <span class="hph-toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Privacy Settings Section -->
        <div class="hph-form-section">
            <h3 class="hph-form-section-title">
                <div class="hph-form-section-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <?php esc_html_e('Privacy Settings', 'happy-place'); ?>
            </h3>
            
            <div class="hph-form-grid">
                <div class="hph-form-group hph-form-group--col-12">
                    <div class="hph-privacy-settings">
                        <div class="hph-privacy-item">
                            <div class="hph-privacy-info">
                                <h4 class="hph-privacy-title"><?php esc_html_e('Show Phone Number', 'happy-place'); ?></h4>
                                <p class="hph-privacy-description"><?php esc_html_e('Display your phone number on public listings', 'happy-place'); ?></p>
                            </div>
                            <label class="hph-toggle">
                                <input type="hidden" name="privacy_show_phone" value="0">
                                <input type="checkbox" 
                                       name="privacy_show_phone" 
                                       value="1" 
                                       <?php checked($profile_data['privacy_show_phone']); ?>>
                                <span class="hph-toggle-slider"></span>
                            </label>
                        </div>
                        
                        <div class="hph-privacy-item">
                            <div class="hph-privacy-info">
                                <h4 class="hph-privacy-title"><?php esc_html_e('Show Email Address', 'happy-place'); ?></h4>
                                <p class="hph-privacy-description"><?php esc_html_e('Display your email address on public listings', 'happy-place'); ?></p>
                            </div>
                            <label class="hph-toggle">
                                <input type="hidden" name="privacy_show_email" value="0">
                                <input type="checkbox" 
                                       name="privacy_show_email" 
                                       value="1" 
                                       <?php checked($profile_data['privacy_show_email']); ?>>
                                <span class="hph-toggle-slider"></span>
                            </label>
                        </div>
                        
                        <div class="hph-privacy-item">
                            <div class="hph-privacy-info">
                                <h4 class="hph-privacy-title"><?php esc_html_e('Allow Contact Form', 'happy-place'); ?></h4>
                                <p class="hph-privacy-description"><?php esc_html_e('Allow clients to contact you through contact forms', 'happy-place'); ?></p>
                            </div>
                            <label class="hph-toggle">
                                <input type="hidden" name="privacy_allow_contact_form" value="0">
                                <input type="checkbox" 
                                       name="privacy_allow_contact_form" 
                                       value="1" 
                                       <?php checked($profile_data['privacy_allow_contact_form']); ?>>
                                <span class="hph-toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="hph-form-actions">
            <button type="button" class="hph-btn hph-btn--secondary" onclick="location.reload();">
                <i class="fas fa-undo"></i>
                <?php esc_html_e('Reset Changes', 'happy-place'); ?>
            </button>
            <button type="submit" name="hph_update_profile" class="hph-btn hph-btn--primary">
                <i class="fas fa-save"></i>
                <?php esc_html_e('Save Profile', 'happy-place'); ?>
            </button>
        </div>
    </form>

</div>

<style>
/* Profile Section Specific Styles */
.hph-notification-settings,
.hph-privacy-settings {
    display: flex;
    flex-direction: column;
    gap: var(--hph-spacing-4);
}

.hph-notification-item,
.hph-privacy-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--hph-spacing-4);
    background: var(--hph-color-gray-25);
    border-radius: var(--hph-radius-lg);
    border: 1px solid var(--hph-color-gray-200);
}

.hph-notification-info,
.hph-privacy-info {
    flex: 1;
}

.hph-notification-title,
.hph-privacy-title {
    font-size: var(--hph-font-size-base);
    font-weight: var(--hph-font-semibold);
    color: var(--hph-color-gray-900);
    margin: 0 0 var(--hph-spacing-1);
}

.hph-notification-description,
.hph-privacy-description {
    font-size: var(--hph-font-size-sm);
    color: var(--hph-color-gray-600);
    margin: 0;
    line-height: 1.4;
}

/* Custom Toggle Switch */
.hph-toggle {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
    cursor: pointer;
}

.hph-toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}

.hph-toggle-slider {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: var(--hph-color-gray-300);
    transition: var(--hph-transition-base);
    border-radius: 24px;
}

.hph-toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: var(--hph-color-white);
    transition: var(--hph-transition-base);
    border-radius: 50%;
    box-shadow: var(--hph-shadow-sm);
}

.hph-toggle input:checked + .hph-toggle-slider {
    background-color: var(--hph-color-primary-500);
}

.hph-toggle input:checked + .hph-toggle-slider:before {
    transform: translateX(26px);
}

.hph-toggle:hover .hph-toggle-slider {
    box-shadow: 0 0 0 8px rgba(81, 186, 224, 0.1);
}

/* Avatar Upload */
.hph-avatar-upload {
    display: inline-flex;
    align-items: center;
    gap: var(--hph-spacing-2);
    padding: var(--hph-spacing-2) var(--hph-spacing-4);
    font-size: var(--hph-font-size-sm);
    color: var(--hph-color-primary-600);
    border: 1px solid var(--hph-color-primary-300);
    border-radius: var(--hph-radius-lg);
    background: var(--hph-color-primary-25);
    cursor: pointer;
    transition: all var(--hph-transition-base);
    text-decoration: none;
}

.hph-avatar-upload:hover {
    background: var(--hph-color-primary-50);
    border-color: var(--hph-color-primary-400);
    transform: translateY(-1px);
}

.hph-profile-avatar--placeholder {
    background: linear-gradient(135deg, var(--hph-color-gray-200), var(--hph-color-gray-300));
    color: var(--hph-color-gray-500);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--hph-font-size-2xl);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .hph-notification-item,
    .hph-privacy-item {
        flex-direction: column;
        gap: var(--hph-spacing-3);
        align-items: flex-start;
    }
    
    .hph-toggle {
        align-self: flex-end;
    }
    
    .hph-profile-header {
        flex-direction: column;
        text-align: center;
        gap: var(--hph-spacing-4);
    }
    
    .hph-profile-meta {
        flex-direction: column;
        gap: var(--hph-spacing-2);
        align-items: center;
    }
}

/* Loading state for avatar upload */
.hph-avatar-uploading {
    opacity: 0.7;
    pointer-events: none;
}

.hph-avatar-uploading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    border: 2px solid var(--hph-color-primary-200);
    border-top: 2px solid var(--hph-color-primary-600);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    transform: translate(-50%, -50%);
}

@keyframes spin {
    0% { transform: translate(-50%, -50%) rotate(0deg); }
    100% { transform: translate(-50%, -50%) rotate(360deg); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Avatar upload preview
    const avatarUpload = document.getElementById('hph-avatar-upload');
    const avatarPreview = document.getElementById('hph-avatar-preview');
    
    if (avatarUpload && avatarPreview) {
        avatarUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (avatarPreview.tagName === 'IMG') {
                        avatarPreview.src = e.target.result;
                    } else {
                        // Replace placeholder div with img
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'hph-profile-avatar';
                        img.id = 'hph-avatar-preview';
                        avatarPreview.parentNode.replaceChild(img, avatarPreview);
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Form validation
    const form = document.querySelector('.hph-dashboard-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('hph-form-input--error');
                    isValid = false;
                } else {
                    field.classList.remove('hph-form-input--error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                if (window.HphDashboard && window.HphDashboard.showToast) {
                    window.HphDashboard.showToast('Please fill in all required fields.', 'error');
                }
            }
        });
    }
    
    // Auto-save draft (optional)
    let saveTimeout;
    const formInputs = form.querySelectorAll('input, textarea, select');
    
    formInputs.forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => {
                // Auto-save draft functionality
                console.log('Auto-saving draft...');
            }, 2000);
        });
    });
});
</script>

<?php
/**
 * Handle profile update
 */
function hph_update_agent_profile($user_id, $data) {
    try {
        // Update WordPress user data
        $user_data = [
            'ID' => $user_id,
            'first_name' => sanitize_text_field($data['first_name'] ?? ''),
            'last_name' => sanitize_text_field($data['last_name'] ?? ''),
            'display_name' => sanitize_text_field($data['display_name'] ?? ''),
            'user_email' => sanitize_email($data['email'] ?? ''),
            'description' => sanitize_textarea_field($data['agent_bio'] ?? '')
        ];
        
        wp_update_user($user_data);
        
        // Update ACF fields if available
        if (function_exists('update_field')) {
            $acf_fields = [
                'agent_title',
                'agent_phone',
                'agent_mobile', 
                'agent_office_phone',
                'agent_website',
                'agent_license',
                'agent_bio',
                'agent_experience_years',
                'agent_brokerage',
                'agent_office_address',
                'agent_social_facebook',
                'agent_social_twitter',
                'agent_social_instagram',
                'agent_social_linkedin',
                'notification_new_leads',
                'notification_listing_inquiries',
                'notification_appointment_reminders',
                'notification_marketing_updates',
                'privacy_show_phone',
                'privacy_show_email',
                'privacy_allow_contact_form'
            ];
            
            foreach ($acf_fields as $field) {
                if (isset($data[$field])) {
                    $value = $data[$field];
                    
                    // Handle checkboxes and arrays
                    if (in_array($field, ['agent_specialties', 'agent_languages'])) {
                        $value = is_array($value) ? array_map('sanitize_text_field', $value) : [];
                    } elseif (strpos($field, 'notification_') === 0 || strpos($field, 'privacy_') === 0) {
                        $value = !empty($value);
                    } else {
                        $value = sanitize_text_field($value);
                    }
                    
                    update_field($field, $value, 'user_' . $user_id);
                }
            }
            
            // Handle specialties and languages separately
            if (isset($data['agent_specialties'])) {
                update_field('agent_specialties', array_map('sanitize_text_field', (array)$data['agent_specialties']), 'user_' . $user_id);
            }
            
            if (isset($data['agent_languages'])) {
                update_field('agent_languages', array_map('sanitize_text_field', (array)$data['agent_languages']), 'user_' . $user_id);
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log('Profile update error: ' . $e->getMessage());
        return false;
    }
}
?>