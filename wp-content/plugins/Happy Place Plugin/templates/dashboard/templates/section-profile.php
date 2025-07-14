<?php

/**
 * Dashboard Profile Section Template Part
 *
 * @package HappyPlace
 */

// Access the section data passed from the parent template
$section_data = $args['section_data'] ?? [];

// Get user data
$user_id = get_current_user_id();
$user = get_userdata($user_id);
$user_meta = [];

if ($user) {
    $user_meta = array_merge([
        'phone' => get_user_meta($user_id, 'phone', true),
        'title' => get_user_meta($user_id, 'title', true),
        'bio' => get_user_meta($user_id, 'description', true),
        'facebook' => get_user_meta($user_id, 'facebook', true),
        'twitter' => get_user_meta($user_id, 'twitter', true),
        'linkedin' => get_user_meta($user_id, 'linkedin', true),
        'instagram' => get_user_meta($user_id, 'instagram', true),
    ], $section_data['meta'] ?? []);
}
?>

<div class="hph-dashboard-profile">
    <!-- Profile Header -->
    <div class="hph-dashboard-profile-header">
        <div class="hph-profile-avatar">
            <?php echo get_avatar($user_id, 150, '', esc_attr($user->display_name), ['class' => 'hph-profile-avatar-img']); ?>
        </div>
        <div class="hph-profile-info">
            <h2><?php echo esc_html($user->display_name); ?></h2>
            <?php if (!empty($user_meta['title'])) : ?>
                <p class="hph-profile-title"><?php echo esc_html($user_meta['title']); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Profile Form -->
    <form id="hph-profile-form" class="hph-dashboard-form" method="post">
        <?php wp_nonce_field('hph_update_profile', 'hph_profile_nonce'); ?>

        <div class="hph-form-section">
            <h3><?php _e('Basic Information', 'happy-place'); ?></h3>

            <div class="hph-form-row">
                <div class="hph-form-group">
                    <label for="first_name"><?php _e('First Name', 'happy-place'); ?></label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($user->first_name); ?>">
                </div>

                <div class="hph-form-group">
                    <label for="last_name"><?php _e('Last Name', 'happy-place'); ?></label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($user->last_name); ?>">
                </div>
            </div>

            <div class="hph-form-row">
                <div class="hph-form-group">
                    <label for="display_name"><?php _e('Display Name', 'happy-place'); ?></label>
                    <input type="text" id="display_name" name="display_name" value="<?php echo esc_attr($user->display_name); ?>">
                </div>

                <div class="hph-form-group">
                    <label for="title"><?php _e('Title/Position', 'happy-place'); ?></label>
                    <input type="text" id="title" name="title" value="<?php echo esc_attr($user_meta['title']); ?>">
                </div>
            </div>
        </div>

        <div class="hph-form-section">
            <h3><?php _e('Contact Information', 'happy-place'); ?></h3>

            <div class="hph-form-row">
                <div class="hph-form-group">
                    <label for="email"><?php _e('Email', 'happy-place'); ?></label>
                    <input type="email" id="email" name="email" value="<?php echo esc_attr($user->user_email); ?>" required>
                </div>

                <div class="hph-form-group">
                    <label for="phone"><?php _e('Phone', 'happy-place'); ?></label>
                    <input type="tel" id="phone" name="phone" value="<?php echo esc_attr($user_meta['phone']); ?>">
                </div>
            </div>
        </div>

        <div class="hph-form-section">
            <h3><?php _e('Bio', 'happy-place'); ?></h3>

            <div class="hph-form-group">
                <label for="bio"><?php _e('About Me', 'happy-place'); ?></label>
                <textarea id="bio" name="bio" rows="5"><?php echo esc_textarea($user_meta['bio']); ?></textarea>
            </div>
        </div>

        <div class="hph-form-section">
            <h3><?php _e('Social Media', 'happy-place'); ?></h3>

            <div class="hph-form-row">
                <div class="hph-form-group">
                    <label for="facebook">
                        <i class="fab fa-facebook"></i>
                        <?php _e('Facebook URL', 'happy-place'); ?>
                    </label>
                    <input type="url" id="facebook" name="facebook" value="<?php echo esc_attr($user_meta['facebook']); ?>">
                </div>

                <div class="hph-form-group">
                    <label for="twitter">
                        <i class="fab fa-twitter"></i>
                        <?php _e('Twitter URL', 'happy-place'); ?>
                    </label>
                    <input type="url" id="twitter" name="twitter" value="<?php echo esc_attr($user_meta['twitter']); ?>">
                </div>
            </div>

            <div class="hph-form-row">
                <div class="hph-form-group">
                    <label for="linkedin">
                        <i class="fab fa-linkedin"></i>
                        <?php _e('LinkedIn URL', 'happy-place'); ?>
                    </label>
                    <input type="url" id="linkedin" name="linkedin" value="<?php echo esc_attr($user_meta['linkedin']); ?>">
                </div>

                <div class="hph-form-group">
                    <label for="instagram">
                        <i class="fab fa-instagram"></i>
                        <?php _e('Instagram URL', 'happy-place'); ?>
                    </label>
                    <input type="url" id="instagram" name="instagram" value="<?php echo esc_attr($user_meta['instagram']); ?>">
                </div>
            </div>
        </div>

        <div class="hph-form-actions">
            <button type="submit" class="hph-button hph-button--primary">
                <i class="fas fa-save"></i>
                <?php _e('Save Changes', 'happy-place'); ?>
            </button>
        </div>
    </form>
</div>