<?php

/**
 * Content Template for Places
 *
 * @package HappyPlace
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('hph-card hph-card--place'); ?>>
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
        // Get place details
        $address = get_post_meta(get_the_ID(), 'place_address', true);
        $phone = get_post_meta(get_the_ID(), 'place_phone', true);
        $rating = get_post_meta(get_the_ID(), 'place_rating', true);
        ?>

        <div class="hph-card__meta">
            <?php if ($address) : ?>
                <div class="hph-meta-item">
                    <i class="fas fa-map-marker-alt hph-icon hph-text-primary"></i>
                    <span><?php echo esc_html($address); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($phone) : ?>
                <div class="hph-meta-item">
                    <i class="fas fa-phone hph-icon hph-text-primary"></i>
                    <a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($phone); ?></a>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($rating) : ?>
            <div class="hph-card__footer">
                <div class="hph-rating">
                    <i class="fas fa-star hph-icon hph-text-primary"></i>
                    <span><?php echo esc_html($rating); ?></span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</article>