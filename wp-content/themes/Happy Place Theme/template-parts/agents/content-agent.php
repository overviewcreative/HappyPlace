<?php
/**
 * Template part for displaying agent posts
 *
 * @package HappyPlace
 */

// Get agent data
$agent_id = get_the_ID();
$phone = get_post_meta($agent_id, 'agent_phone', true);
$email = get_post_meta($agent_id, 'agent_email', true);
$license = get_post_meta($agent_id, 'agent_license', true);
$specialties = get_post_meta($agent_id, 'agent_specialties', true);
$years_experience = get_post_meta($agent_id, 'agent_years_experience', true);
?>

<article id="agent-<?php the_ID(); ?>" <?php post_class('hph-agent-card'); ?> itemscope itemtype="https://schema.org/Person">
    <div class="hph-agent-card-inner">
        
        <?php if (has_post_thumbnail()) : ?>
            <div class="hph-agent-photo">
                <a href="<?php echo esc_url(get_permalink()); ?>">
                    <?php the_post_thumbnail('medium', [
                        'class' => 'hph-agent-thumbnail',
                        'itemprop' => 'image'
                    ]); ?>
                </a>
            </div>
        <?php endif; ?>

        <div class="hph-agent-content">
            <header class="hph-agent-header">
                <h3 class="hph-agent-name" itemprop="name">
                    <a href="<?php echo esc_url(get_permalink()); ?>">
                        <?php the_title(); ?>
                    </a>
                </h3>
                
                <?php if ($license) : ?>
                    <div class="hph-agent-license">
                        <i class="fas fa-id-card"></i>
                        <?php esc_html_e('License:', 'happy-place'); ?> <?php echo esc_html($license); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($years_experience) : ?>
                    <div class="hph-agent-experience">
                        <i class="fas fa-star"></i>
                        <?php echo esc_html($years_experience); ?> 
                        <?php echo esc_html(_n('Year', 'Years', $years_experience, 'happy-place')); ?> 
                        <?php esc_html_e('Experience', 'happy-place'); ?>
                    </div>
                <?php endif; ?>
            </header>

            <?php if ($specialties) : ?>
                <div class="hph-agent-specialties">
                    <strong><?php esc_html_e('Specialties:', 'happy-place'); ?></strong>
                    <span><?php echo esc_html($specialties); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!is_singular()) : ?>
                <div class="hph-agent-excerpt" itemprop="description">
                    <?php the_excerpt(); ?>
                </div>
            <?php endif; ?>

            <div class="hph-agent-contact">
                <?php if ($phone) : ?>
                    <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9]/', '', $phone)); ?>" 
                       class="hph-contact-item hph-phone" itemprop="telephone">
                        <i class="fas fa-phone"></i>
                        <?php echo esc_html($phone); ?>
                    </a>
                <?php endif; ?>

                <?php if ($email) : ?>
                    <a href="mailto:<?php echo esc_attr($email); ?>" 
                       class="hph-contact-item hph-email" itemprop="email">
                        <i class="fas fa-envelope"></i>
                        <?php echo esc_html($email); ?>
                    </a>
                <?php endif; ?>
            </div>

            <div class="hph-agent-actions">
                <a href="<?php echo esc_url(get_permalink()); ?>" class="action-btn action-btn--primary">
                    <?php esc_html_e('View Profile', 'happy-place'); ?>
                </a>
                
                <a href="<?php echo esc_url(home_url('/contact')); ?>?agent=<?php echo esc_attr($agent_id); ?>" 
                   class="action-btn action-btn--outline">
                    <?php esc_html_e('Contact Agent', 'happy-place'); ?>
                </a>
            </div>
        </div>
    </div>
</article>
