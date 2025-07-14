<?php
/**
 * Agent Contact Card Template Part
 * 
 * @param array $args Contains agent_id
 */

if (!isset($args['agent_id'])) {
    return;
}

$agent = get_post($args['agent_id']);
if (!$agent) {
    return;
}

$phone = get_post_meta($agent->ID, '_phone', true);
$email = get_post_meta($agent->ID, '_email', true);
$license = get_post_meta($agent->ID, '_license_number', true);
?>

<div class="hph-card hph-card--agent-contact hph-max-w-2xl hph-mx-auto">
    <div class="hph-card__content hph-p-6">
        <div class="hph-flex hph-gap-6 hph-items-start">
            <?php if (has_post_thumbnail($agent)) : ?>
                <div class="hph-flex-shrink-0">
                    <div class="hph-avatar hph-avatar--lg">
                        <?php echo get_the_post_thumbnail($agent, 'thumbnail', [
                            'class' => 'hph-avatar__image'
                        ]); ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="hph-flex-grow">
                <h3 class="hph-text-2xl hph-font-bold hph-text-gray-900 hph-mb-2">
                    <?php echo esc_html($agent->post_title); ?>
                </h3>
                
                <?php if ($license) : ?>
                    <div class="hph-text-sm hph-text-gray-600 hph-mb-4">
                        <?php echo esc_html__('License #:', 'happy-place') . ' ' . esc_html($license); ?>
                    </div>
                <?php endif; ?>

                <div class="hph-space-y-4">
                    <div class="hph-flex hph-flex-wrap hph-gap-3">
                        <?php if ($phone) : ?>
                            <a href="tel:<?php echo esc_attr($phone); ?>" class="hph-btn hph-btn--primary">
                                <i class="fas fa-phone hph-mr-2"></i>
                                <?php esc_html_e('Call Agent', 'happy-place'); ?>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($email) : ?>
                            <a href="mailto:<?php echo esc_attr($email); ?>" class="hph-btn hph-btn--secondary">
                                <i class="fas fa-envelope hph-mr-2"></i>
                                <?php esc_html_e('Email Agent', 'happy-place'); ?>
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="hph-contact-details hph-text-sm hph-text-gray-600">
                        <?php if ($phone) : ?>
                            <div class="hph-contact-item">
                                <i class="fas fa-phone hph-text-primary hph-mr-2"></i>
                                <span><?php echo esc_html($phone); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($email) : ?>
                            <div class="hph-contact-item">
                                <i class="fas fa-envelope hph-text-primary hph-mr-2"></i>
                                <span><?php echo esc_html($email); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($bio = get_post_meta($agent->ID, '_bio', true)) : ?>
                    <div class="hph-mt-6 hph-text-gray-600">
                        <?php echo wp_kses_post(wp_trim_words($bio, 30)); ?>
                        <a href="<?php echo get_permalink($agent->ID); ?>" class="hph-link hph-text-sm">
                            <?php esc_html_e('Read More', 'happy-place'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
