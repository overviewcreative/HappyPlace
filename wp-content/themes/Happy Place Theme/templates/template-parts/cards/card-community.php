<?php
/**
 * Template part for displaying community cards
 *
 * @package HappyPlace
 */

$community_id = get_the_ID();
$location = get_post_meta($community_id, '_location', true);
$listings_count = count_posts_in_community($community_id);
$stats = get_community_stats($community_id); // Function to get average price, total homes, etc.
?>

<div class="hph-card hph-card--community">
    <div class="hph-card__media">
        <a href="<?php the_permalink(); ?>" class="hph-card__media-link">
            <?php if (has_post_thumbnail()) : ?>
                <?php the_post_thumbnail('community-card', ['class' => 'hph-card__image']); ?>
            <?php else : ?>
                <img src="<?php echo get_theme_file_uri('assets/images/placeholder-community.jpg'); ?>" 
                     alt="<?php echo esc_attr(get_the_title()); ?> community image"
                     class="hph-card__image">
            <?php endif; ?>
        </a>

        <?php if ($listings_count > 0) : ?>
            <div class="hph-badge hph-badge--primary">
                <?php echo sprintf(_n('%d Listing', '%d Listings', $listings_count, 'happy-place'), $listings_count); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="hph-card__content">
        <h3 class="hph-card__title hph-text-xl">
            <a href="<?php the_permalink(); ?>" class="hph-link hph-link--primary"><?php the_title(); ?></a>
        </h3>

        <?php if ($location) : ?>
            <div class="hph-card__meta hph-text-sm hph-space-x-2">
                <i class="fas fa-map-marker-alt hph-icon hph-text-primary"></i>
                <span class="hph-text-gray-600"><?php echo esc_html($location); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($stats) : ?>
            <div class="hph-card__stats hph-grid hph-grid-cols-2 hph-gap-4 hph-mt-4">
                <?php if (!empty($stats['avg_price'])) : ?>
                    <div class="hph-stat-item">
                        <i class="fas fa-home hph-icon hph-text-primary"></i>
                        <span class="hph-text-sm">Avg. <?php echo esc_html($stats['avg_price']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($stats['total_homes'])) : ?>
                    <div class="hph-stat-item">
                        <i class="fas fa-building hph-icon hph-text-primary"></i>
                        <span class="hph-text-sm"><?php echo sprintf(_n('%d Home', '%d Homes', $stats['total_homes'], 'happy-place'), $stats['total_homes']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($stats['avg_sqft'])) : ?>
                    <div class="hph-stat-item">
                        <i class="fas fa-ruler-combined hph-icon hph-text-primary"></i>
                        <span class="hph-text-sm">Avg. <?php echo number_format($stats['avg_sqft']); ?> sq.ft.</span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
