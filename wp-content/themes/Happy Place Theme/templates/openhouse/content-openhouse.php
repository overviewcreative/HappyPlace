<?php

/**
 * Content Template for Open Houses
 *
 * @package HappyPlace
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('hph-card hph-card--openhouse'); ?>>
    <?php if (has_post_thumbnail()) : ?>
        <div class="hph-card__media">
            <a href="<?php the_permalink(); ?>" class="hph-card__media-link">
                <?php the_post_thumbnail('medium_large', ['class' => 'hph-card__image']); ?>
            </a>
        </div>
    <?php endif; ?>

    <div class="hph-card__content">
        <h3 class="hph-card__title">
            <a href="<?php the_permalink(); ?>" class="hph-link hph-link--primary"><?php the_title(); ?></a>
        </h3>

        <?php
        // Get open house details
        $date = get_post_meta(get_the_ID(), 'open_house_date', true);
        $start_time = get_post_meta(get_the_ID(), 'start_time', true);
        $end_time = get_post_meta(get_the_ID(), 'end_time', true);
        $host = get_post_meta(get_the_ID(), 'host_agent', true);
        ?>

        <div class="hph-card__meta">
            <?php if ($date) : ?>
                <div class="hph-meta-item">
                    <i class="fas fa-calendar hph-icon hph-text-primary"></i>
                    <span><?php echo esc_html($date); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($start_time && $end_time) : ?>
                <div class="hph-meta-item">
                    <i class="fas fa-clock hph-icon hph-text-primary"></i>
                    <span><?php printf(
                                esc_html__('%s to %s', 'happy-place'),
                                esc_html($start_time),
                                esc_html($end_time)
                            ); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($host) : ?>
            <div class="hph-card__footer">
                <div class="hph-meta-item">
                    <i class="fas fa-user hph-icon hph-text-primary"></i>
                    <span class="hph-text-sm"><?php echo esc_html($host); ?></span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</article>