<?php
/**
 * Agent Profile Form Template Part
 * 
 * @package HappyPlace
 */

$current_user_id = get_current_user_id();
$agent_data = get_field('agent_details', 'user_' . $current_user_id);
?>

<form id="hph-profile-form" class="hph-form">
    <?php wp_nonce_field('hph_profile_form', 'hph_profile_nonce'); ?>
    
    <div class="hph-form-grid">
        <!-- Personal Information -->
        <div class="hph-form-section">
            <h3>Personal Information</h3>
            
            <div class="hph-form-row hph-form-row--columns">
                <div>
                    <label for="agent_first_name">First Name</label>
                    <input type="text" name="agent_first_name" id="agent_first_name" 
                        value="<?php echo esc_attr($agent_data['first_name'] ?? ''); ?>" required>
                </div>
                <div>
                    <label for="agent_last_name">Last Name</label>
                    <input type="text" name="agent_last_name" id="agent_last_name" 
                        value="<?php echo esc_attr($agent_data['last_name'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="hph-form-row">
                <label for="agent_title">Title</label>
                <input type="text" name="agent_title" id="agent_title" 
                    value="<?php echo esc_attr($agent_data['title'] ?? ''); ?>">
            </div>

            <div class="hph-form-row">
                <label for="agent_bio">Bio</label>
                <textarea name="agent_bio" id="agent_bio" rows="4"><?php 
                    echo esc_textarea($agent_data['bio'] ?? ''); 
                ?></textarea>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="hph-form-section">
            <h3>Contact Information</h3>
            
            <div class="hph-form-row">
                <label for="agent_email">Email</label>
                <input type="email" name="agent_email" id="agent_email" 
                    value="<?php echo esc_attr($agent_data['email'] ?? ''); ?>" required>
            </div>

            <div class="hph-form-row">
                <label for="agent_phone">Phone</label>
                <input type="tel" name="agent_phone" id="agent_phone" 
                    value="<?php echo esc_attr($agent_data['phone'] ?? ''); ?>" required>
            </div>

            <div class="hph-form-row">
                <label for="agent_office">Office Location</label>
                <select name="agent_office" id="agent_office">
                    <?php
                    $offices = get_posts([
                        'post_type' => 'office',
                        'posts_per_page' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC'
                    ]);
                    foreach ($offices as $office) :
                    ?>
                        <option value="<?php echo esc_attr($office->ID); ?>" 
                            <?php selected($office->ID, $agent_data['office'] ?? 0); ?>>
                            <?php echo esc_html($office->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Social Media -->
        <div class="hph-form-section">
            <h3>Social Media</h3>
            
            <div class="hph-form-row">
                <label for="agent_facebook">Facebook URL</label>
                <input type="url" name="agent_facebook" id="agent_facebook" 
                    value="<?php echo esc_attr($agent_data['social_media']['facebook'] ?? ''); ?>">
            </div>

            <div class="hph-form-row">
                <label for="agent_instagram">Instagram URL</label>
                <input type="url" name="agent_instagram" id="agent_instagram" 
                    value="<?php echo esc_attr($agent_data['social_media']['instagram'] ?? ''); ?>">
            </div>

            <div class="hph-form-row">
                <label for="agent_linkedin">LinkedIn URL</label>
                <input type="url" name="agent_linkedin" id="agent_linkedin" 
                    value="<?php echo esc_attr($agent_data['social_media']['linkedin'] ?? ''); ?>">
            </div>
        </div>

        <!-- Profile Photo -->
        <div class="hph-form-section">
            <h3>Profile Photo</h3>
            
            <div class="hph-form-row">
                <div class="hph-media-uploader" id="agent_photo_uploader">
                    <div class="hph-media-preview" id="agent_photo_preview">
                        <?php
                        $photo_id = $agent_data['photo'] ?? 0;
                        if ($photo_id) :
                            $img_url = wp_get_attachment_image_url($photo_id, 'medium');
                        ?>
                            <div class="hph-media-item" data-id="<?php echo esc_attr($photo_id); ?>">
                                <img src="<?php echo esc_url($img_url); ?>" alt="">
                                <button type="button" class="hph-remove-media">&times;</button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="hph-button hph-button--secondary" id="add_agent_photo">
                        <i class="fas fa-plus"></i> Update Profile Photo
                    </button>
                </div>
                <input type="hidden" name="agent_photo" id="agent_photo" 
                    value="<?php echo esc_attr($photo_id); ?>">
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="hph-form-actions">
        <button type="submit" class="hph-button hph-button--primary">
            Update Profile
        </button>
    </div>
</form>
