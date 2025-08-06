<?php
/**
 * Template part for displaying agent cards
 *
 * @package HappyPlace
 */

$agent_id = get_the_ID();
$phone = get_post_meta($agent_id, '_phone', true);
$email = get_post_meta($agent_id, '_email', true);
$listings_count = count_user_posts($agent_id, 'listing');
?>

<div class="hph-card hph-card--agent">
    <div class="hph-card__media">
        <a href="<?php the_permalink(); ?>" class="hph-card__media-link">
            <?php if (has_post_thumbnail()) : ?>
                <?php the_post_thumbnail('agent-thumbnail', ['class' => 'hph-card__image']); ?>
            <?php else : ?>
                <img src="<?php echo get_theme_file_uri('assets/images/placeholder-agent.jpg'); ?>" 
                     alt="Agent placeholder" 
                     class="hph-card__image">
            <?php endif; ?>
        </a>
    </div>

    <div class="hph-card__content">
        <h3 class="hph-card__title hph-text-xl">
            <a href="<?php the_permalink(); ?>" class="hph-link hph-link--primary"><?php the_title(); ?></a>
        </h3>

        <?php if ($license = get_post_meta($agent_id, '_license_number', true)) : ?>
            <div class="hph-card__meta hph-text-sm hph-text-gray-600">
                <?php echo esc_html__('License #:', 'happy-place') . ' ' . esc_html($license); ?>
            </div>
        <?php endif; ?>

        <div class="hph-card__details hph-space-y-2">
            <?php if ($phone) : ?>
                <div class="hph-contact-item">
                    <i class="fas fa-phone hph-icon hph-text-primary"></i>
                    <a href="tel:<?php echo esc_attr($phone); ?>" class="hph-link"><?php echo esc_html($phone); ?></a>
                </div>
            <?php endif; ?>

            <?php if ($email) : ?>
                <div class="hph-contact-item">
                    <i class="fas fa-envelope hph-icon hph-text-primary"></i>
                    <a href="mailto:<?php echo esc_attr($email); ?>" class="hph-link"><?php echo esc_html($email); ?></a>
                </div>
            <?php endif; ?>

            <div class="hph-contact-item">
                <i class="fas fa-home hph-icon hph-text-primary"></i>
                <span class="hph-text-sm">
                    <?php echo sprintf(_n('%d Active Listing', '%d Active Listings', $listings_count, 'happy-place'), $listings_count); ?>
                </span>
            </div>
        </div>
    </div>
</div>
