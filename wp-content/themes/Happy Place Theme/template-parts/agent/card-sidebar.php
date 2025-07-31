<?php
/**
 * Template part for displaying agent card in sidebar
 * 
 * This is a fallback template part used when the Agent Card component is not available.
 * 
 * @package HappyPlace
 * @subpackage TemplateParts
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get listing data
$listing_id = isset($listing_id) ? $listing_id : get_the_ID();
$agent_data = isset($agent_data) ? $agent_data : [];

// Fallback data if not provided
if (empty($agent_data)) {
    $agent_data = function_exists('hph_bridge_get_agent_data') 
        ? hph_bridge_get_agent_data($listing_id)
        : hph_fallback_get_agent_data($listing_id);
}

$agent_name = $agent_data['name'] ?? '';
$agent_photo = $agent_data['photo'] ?? '';
$agent_title = $agent_data['title'] ?? '';
$agent_phone = $agent_data['phone'] ?? '';
$agent_email = $agent_data['email'] ?? '';
$agent_bio = $agent_data['bio'] ?? '';
?>

<?php if (!empty($agent_name)): ?>
<div class="agent-card-sidebar widget">
    <h3 class="widget-title">Your Agent</h3>
    
    <div class="agent-card">
        <?php if (!empty($agent_photo)): ?>
            <div class="agent-photo">
                <img src="<?php echo esc_url($agent_photo); ?>" 
                     alt="<?php echo esc_attr($agent_name); ?>"
                     class="agent-image">
            </div>
        <?php endif; ?>
        
        <div class="agent-info">
            <h4 class="agent-name"><?php echo esc_html($agent_name); ?></h4>
            
            <?php if (!empty($agent_title)): ?>
                <p class="agent-title"><?php echo esc_html($agent_title); ?></p>
            <?php endif; ?>
            
            <?php if (!empty($agent_bio)): ?>
                <p class="agent-bio"><?php echo esc_html($agent_bio); ?></p>
            <?php endif; ?>
            
            <div class="agent-contact">
                <?php if (!empty($agent_phone)): ?>
                    <div class="contact-item">
                        <span class="contact-icon">📞</span>
                        <a href="tel:<?php echo esc_attr($agent_phone); ?>" class="contact-link">
                            <?php echo esc_html($agent_phone); ?>
                        </a>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($agent_email)): ?>
                    <div class="contact-item">
                        <span class="contact-icon">✉️</span>
                        <a href="mailto:<?php echo esc_attr($agent_email); ?>" class="contact-link">
                            <?php echo esc_html($agent_email); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="agent-actions">
                <button class="btn btn-primary btn-block contact-agent-btn" 
                        data-agent-id="<?php echo esc_attr($agent_data['id'] ?? ''); ?>">
                    Contact Agent
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
