<?php
/**
 * Template part for displaying agent cards
 * Updated to use consolidated agent card classes
 *
 * @package HappyPlace
 */

$agent_id = get_the_ID();
$phone = get_post_meta($agent_id, '_phone', true);
$email = get_post_meta($agent_id, '_email', true);
$license = get_post_meta($agent_id, '_license_number', true);
$listings_count = count_user_posts($agent_id, 'listing');

// Determine card variant based on context
$card_variant = 'grid'; // default
if (isset($args['variant'])) {
    $card_variant = $args['variant'];
} elseif (is_page_template('archive-agent.php') || is_post_type_archive('agent')) {
    $card_variant = 'grid';
}
?>

<div class="hph-agent-card hph-agent-card--<?php echo esc_attr($card_variant); ?>">
    <div class="hph-agent-image">
        <a href="<?php the_permalink(); ?>">
            <?php if (has_post_thumbnail()) : ?>
                <?php the_post_thumbnail('agent-thumbnail', ['class' => 'hph-agent-image']); ?>
            <?php else : ?>
                <div class="hph-agent-image-placeholder">
                    <?php echo strtoupper(substr(get_the_title(), 0, 2)); ?>
                </div>
            <?php endif; ?>
        </a>
    </div>

    <div class="hph-agent-content">
        <div class="hph-agent-header">
            <h3 class="hph-agent-name">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h3>
            
            <?php if ($license) : ?>
                <div class="hph-agent-title">
                    <?php echo esc_html__('License #:', 'happy-place') . ' ' . esc_html($license); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="hph-agent-contact">
            <?php if ($phone) : ?>
                <a href="tel:<?php echo esc_attr($phone); ?>" class="hph-agent-phone">
                    <i class="fas fa-phone"></i>
                    <?php echo esc_html($phone); ?>
                </a>
            <?php endif; ?>

            <?php if ($email) : ?>
                <a href="mailto:<?php echo esc_attr($email); ?>" class="hph-agent-email">
                    <i class="fas fa-envelope"></i>
                    <?php echo esc_html($email); ?>
                </a>
            <?php endif; ?>
        </div>

        <?php if ($card_variant !== 'compact') : ?>
            <div class="hph-agent-stats">
                <div class="hph-stat-item">
                    <span class="hph-stat-number"><?php echo esc_html($listings_count); ?></span>
                    <span class="hph-stat-label"><?php esc_html_e('Active Listings', 'happy-place'); ?></span>
                </div>
                <div class="hph-stat-item">
                    <span class="hph-stat-number">15</span>
                    <span class="hph-stat-label"><?php esc_html_e('Sales YTD', 'happy-place'); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <div class="hph-agent-actions">
            <a href="<?php the_permalink(); ?>" class="action-btn action-btn--primary">
                <?php esc_html_e('View Profile', 'happy-place'); ?>
            </a>
            <?php if ($card_variant !== 'compact') : ?>
                <a href="#contact-modal" class="action-btn action-btn--outline" data-agent-id="<?php echo esc_attr($agent_id); ?>">
                    <?php esc_html_e('Contact', 'happy-place'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
