<?php
/**
 * Listing Agent Card
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

namespace HappyPlace\Templates;

if (!defined('ABSPATH')) {
    exit;
}

$data = $args['data'] ?? [];
$listing_id = $args['listing_id'] ?? get_the_ID();

// Use bridge function to get agent data
$agent = null;
if (function_exists('hph_bridge_get_listing_agent')) {
    $agent = hph_bridge_get_listing_agent($listing_id);
} elseif (function_exists('hph_get_listing_agent')) {
    $agent = hph_get_listing_agent($listing_id);
} elseif (!empty($data['agent'])) {
    $agent = $data['agent'];
}

// Don't show if no agent data
if (!$agent || empty($agent['name'])) {
    return;
}

// Sanitize agent data
$agent_id = (int) ($agent['id'] ?? 0);
$agent_name = sanitize_text_field($agent['name'] ?? '');
$agent_title = sanitize_text_field($agent['title'] ?? '');
$agent_phone = sanitize_text_field($agent['phone'] ?? '');
$agent_email = sanitize_email($agent['email'] ?? '');
$agent_license = sanitize_text_field($agent['license'] ?? '');
$agent_photo = esc_url($agent['photo'] ?? '');
$agent_bio = wp_kses_post($agent['bio'] ?? '');

// Clean phone number for tel: link
$clean_phone = preg_replace('/[^0-9+]/', '', $agent_phone);
?>

<section class="card agent-card-container">
    <div class="card-header">
        <h3 class="card-title"><?php esc_html_e('Your Agent', 'happy-place'); ?></h3>
    </div>
    <div class="card-body">
        <div class="agent-card" data-agent-id="<?php echo esc_attr($agent_id); ?>">
            
            <?php if ($agent_photo) : ?>
                <div class="agent-avatar-wrapper">
                    <img 
                        src="<?php echo esc_url($agent_photo); ?>" 
                        alt="<?php echo esc_attr($agent_name); ?>"
                        class="agent-avatar"
                        loading="lazy"
                    >
                </div>
            <?php endif; ?>
            
            <div class="agent-info">
                <div class="agent-name"><?php echo esc_html($agent_name); ?></div>
                
                <?php if ($agent_title) : ?>
                    <div class="agent-title"><?php echo esc_html($agent_title); ?></div>
                <?php endif; ?>
                
                <?php if ($agent_bio && strlen($agent_bio) > 0) : ?>
                    <div class="agent-bio" data-full-bio="<?php echo esc_attr(wp_strip_all_tags($agent_bio)); ?>">
                        <?php 
                        // Show truncated bio
                        $short_bio = wp_trim_words($agent_bio, 15, '...');
                        echo wp_kses_post($short_bio);
                        
                        if (str_word_count(wp_strip_all_tags($agent_bio)) > 15) : ?>
                            <button class="bio-toggle" type="button" data-action="expand-bio">
                                <?php esc_html_e('Read more', 'happy-place'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="contact-info">
                
                <?php if ($agent_phone) : ?>
                    <div class="contact-item">
                        <i class="fas fa-phone" aria-hidden="true"></i>
                        <a href="tel:<?php echo esc_attr($clean_phone); ?>" class="contact-link">
                            <?php echo esc_html($agent_phone); ?>
                        </a>
                    </div>
                <?php endif; ?>
                
                <?php if ($agent_email) : ?>
                    <div class="contact-item">
                        <i class="fas fa-envelope" aria-hidden="true"></i>
                        <a href="mailto:<?php echo esc_attr($agent_email); ?>" class="contact-link">
                            <?php echo esc_html($agent_email); ?>
                        </a>
                    </div>
                <?php endif; ?>
                
                <?php if ($agent_license) : ?>
                    <div class="contact-item">
                        <i class="fas fa-id-card" aria-hidden="true"></i>
                        <span class="contact-text">
                            <?php esc_html_e('License:', 'happy-place'); ?> <?php echo esc_html($agent_license); ?>
                        </span>
                    </div>
                <?php endif; ?>
                
            </div>
            
            <div class="agent-actions">
                <button 
                    class="hph-btn hph-btn--primary" 
                    type="button"
                    data-action="call-agent" 
                    data-phone="<?php echo esc_attr($clean_phone); ?>"
                    <?php echo empty($agent_phone) ? 'disabled' : ''; ?>
                >
                    <i class="fas fa-phone" aria-hidden="true"></i>
                    <?php esc_html_e('Call Agent', 'happy-place'); ?>
                </button>
                
                <button 
                    class="hph-btn hph-btn--secondary" 
                    type="button"
                    data-action="email-agent" 
                    data-email="<?php echo esc_attr($agent_email); ?>"
                    data-listing-id="<?php echo esc_attr($listing_id); ?>"
                    data-agent-id="<?php echo esc_attr($agent_id); ?>"
                    <?php echo empty($agent_email) ? 'disabled' : ''; ?>
                >
                    <i class="fas fa-envelope" aria-hidden="true"></i>
                    <?php esc_html_e('Send Message', 'happy-place'); ?>
                </button>
            </div>
            
        </div>
    </div>
</section>

<?php
// Pass agent data to JavaScript for forms/interactions
?>
<script type="application/json" id="agent-data">
    <?php echo wp_json_encode([
        'agent_id' => $agent_id,
        'name' => $agent_name,
        'email' => $agent_email,
        'phone' => $agent_phone,
        'listing_id' => $listing_id,
        'contact_form_nonce' => wp_create_nonce('hph_contact_agent_' . $agent_id)
    ]); ?>
</script>