<?php
/**
 * Agent card component
 */

$listing_id = get_the_ID();
$agent_id = get_post_meta($listing_id, 'listing_agent', true);

if ($agent_id) {
    $agent_name = get_the_title($agent_id);
    $agent_title = get_post_meta($agent_id, 'agent_title', true);
    $agent_phone = get_post_meta($agent_id, 'agent_phone', true);
    $agent_email = get_post_meta($agent_id, 'agent_email', true);
    $agent_photo = get_the_post_thumbnail_url($agent_id, 'thumbnail');
} else {
    // Default agent info
    $agent_name = 'Sarah Johnson';
    $agent_title = 'Senior Real Estate Agent';
    $agent_phone = '(555) 123-4567';
    $agent_email = 'sarah@happyplace.com';
    $agent_photo = get_template_directory_uri() . '/assets/images/default-agent.jpg';
}
?>

<div class="sidebar-widget agent-card">
    <h3 class="widget-title">Listed By</h3>
    
    <div class="agent-info">
        <?php if ($agent_photo) : ?>
            <img src="<?php echo esc_url($agent_photo); ?>" 
                 alt="<?php echo esc_attr($agent_name); ?>" 
                 class="agent-photo">
        <?php endif; ?>
        
        <div class="agent-name"><?php echo esc_html($agent_name); ?></div>
        
        <?php if ($agent_title) : ?>
            <div class="agent-title"><?php echo esc_html($agent_title); ?></div>
        <?php endif; ?>
    </div>
    
    <div class="agent-contact">
        <?php if ($agent_phone) : ?>
            <a href="tel:<?php echo esc_attr($agent_phone); ?>" class="contact-btn">
                📞 Call Agent
            </a>
        <?php endif; ?>
        
        <?php if ($agent_email) : ?>
            <a href="mailto:<?php echo esc_attr($agent_email); ?>" class="contact-btn secondary">
                ✉️ Email Agent
            </a>
        <?php endif; ?>
    </div>
</div>
