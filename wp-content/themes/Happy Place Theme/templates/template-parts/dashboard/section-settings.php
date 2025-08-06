<?php

/**
 * Dashboard Settings Section Template Part
 *
 * @package HappyPlace
 */

// Access the section data passed from the parent template
$section_data = $args['section_data'] ?? [];

// Get settings with fallbacks
$notification_settings = $section_data['notification_settings'] ?? [
    'email' => true,
    'sms' => false,
    'push' => true
];

$privacy_settings = $section_data['privacy_settings'] ?? [
    'profile_visibility' => 'public',
    'contact_visibility' => 'registered'
];
?>

<div class="hph-dashboard-settings">
    <form id="hph-settings-form" class="hph-dashboard-form" method="post">
        <?php wp_nonce_field('hph_update_settings', 'hph_settings_nonce'); ?>

        <!-- Notification Settings -->
        <div class="hph-form-section">
            <h3>
                <i class="fas fa-bell"></i>
                <?php _e('Notification Settings', 'happy-place'); ?>
            </h3>

            <div class="hph-form-group">
                <label class="hph-checkbox">
                    <input type="checkbox"
                        name="notifications[email]"
                        value="1"
                        <?php checked($notification_settings['email'], true); ?>>
                    <span class="hph-checkbox-label">
                        <?php _e('Email Notifications', 'happy-place'); ?>
                    </span>
                    <span class="hph-checkbox-description">
                        <?php _e('Receive notifications about new leads, listing updates, and important alerts via email.', 'happy-place'); ?>
                    </span>
                </label>
            </div>

            <div class="hph-form-group">
                <label class="hph-checkbox">
                    <input type="checkbox"
                        name="notifications[sms]"
                        value="1"
                        <?php checked($notification_settings['sms'], true); ?>>
                    <span class="hph-checkbox-label">
                        <?php _e('SMS Notifications', 'happy-place'); ?>
                    </span>
                    <span class="hph-checkbox-description">
                        <?php _e('Get instant text messages for urgent updates and new lead inquiries.', 'happy-place'); ?>
                    </span>
                </label>
            </div>

            <div class="hph-form-group">
                <label class="hph-checkbox">
                    <input type="checkbox"
                        name="notifications[push]"
                        value="1"
                        <?php checked($notification_settings['push'], true); ?>>
                    <span class="hph-checkbox-label">
                        <?php _e('Browser Push Notifications', 'happy-place'); ?>
                    </span>
                    <span class="hph-checkbox-description">
                        <?php _e('Receive desktop notifications when someone interacts with your listings.', 'happy-place'); ?>
                    </span>
                </label>
            </div>
        </div>

        <!-- Privacy Settings -->
        <div class="hph-form-section">
            <h3>
                <i class="fas fa-shield-alt"></i>
                <?php _e('Privacy Settings', 'happy-place'); ?>
            </h3>

            <div class="hph-form-group">
                <label for="profile_visibility"><?php _e('Profile Visibility', 'happy-place'); ?></label>
                <select id="profile_visibility" name="privacy[profile_visibility]" class="hph-select">
                    <option value="public" <?php selected($privacy_settings['profile_visibility'], 'public'); ?>>
                        <?php _e('Public - Anyone can view', 'happy-place'); ?>
                    </option>
                    <option value="registered" <?php selected($privacy_settings['profile_visibility'], 'registered'); ?>>
                        <?php _e('Registered Users Only', 'happy-place'); ?>
                    </option>
                    <option value="private" <?php selected($privacy_settings['profile_visibility'], 'private'); ?>>
                        <?php _e('Private - Only you', 'happy-place'); ?>
                    </option>
                </select>
                <p class="hph-form-help">
                    <?php _e('Control who can view your agent profile and listings.', 'happy-place'); ?>
                </p>
            </div>

            <div class="hph-form-group">
                <label for="contact_visibility"><?php _e('Contact Information Visibility', 'happy-place'); ?></label>
                <select id="contact_visibility" name="privacy[contact_visibility]" class="hph-select">
                    <option value="public" <?php selected($privacy_settings['contact_visibility'], 'public'); ?>>
                        <?php _e('Public - Show to everyone', 'happy-place'); ?>
                    </option>
                    <option value="registered" <?php selected($privacy_settings['contact_visibility'], 'registered'); ?>>
                        <?php _e('Registered Users Only', 'happy-place'); ?>
                    </option>
                    <option value="leads" <?php selected($privacy_settings['contact_visibility'], 'leads'); ?>>
                        <?php _e('Leads Only', 'happy-place'); ?>
                    </option>
                </select>
                <p class="hph-form-help">
                    <?php _e('Control who can see your contact information.', 'happy-place'); ?>
                </p>
            </div>
        </div>

        <!-- Email Preferences -->
        <div class="hph-form-section">
            <h3>
                <i class="fas fa-envelope"></i>
                <?php _e('Email Preferences', 'happy-place'); ?>
            </h3>

            <div class="hph-form-group">
                <label class="hph-checkbox">
                    <input type="checkbox"
                        name="email_prefs[newsletter]"
                        value="1"
                        <?php checked($section_data['email_prefs']['newsletter'] ?? false, true); ?>>
                    <span class="hph-checkbox-label">
                        <?php _e('Newsletter Subscription', 'happy-place'); ?>
                    </span>
                    <span class="hph-checkbox-description">
                        <?php _e('Receive our monthly newsletter with market updates and tips.', 'happy-place'); ?>
                    </span>
                </label>
            </div>

            <div class="hph-form-group">
                <label class="hph-checkbox">
                    <input type="checkbox"
                        name="email_prefs[marketing]"
                        value="1"
                        <?php checked($section_data['email_prefs']['marketing'] ?? false, true); ?>>
                    <span class="hph-checkbox-label">
                        <?php _e('Marketing Emails', 'happy-place'); ?>
                    </span>
                    <span class="hph-checkbox-description">
                        <?php _e('Receive updates about new features, promotions, and special offers.', 'happy-place'); ?>
                    </span>
                </label>
            </div>
        </div>

        <div class="hph-form-actions">
            <button type="submit" class="hph-button hph-button--primary">
                <i class="fas fa-save"></i>
                <?php _e('Save Settings', 'happy-place'); ?>
            </button>
        </div>
    </form>
</div>