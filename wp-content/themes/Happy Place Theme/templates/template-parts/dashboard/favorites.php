<?php

/**
 * Template part for displaying user favorites
 */

$current_user = wp_get_current_user();
$favorites = get_user_meta($current_user->ID, 'favorites', true);
?>

<div class="hph-favorites">
    <h1><?php esc_html_e('My Favorites', 'happy-place'); ?></h1>

    <?php if (!empty($favorites) && is_array($favorites)) : ?>
        <div class="hph-grid hph-grid-3">
            <?php
            $favorites_query = new WP_Query(array(
                'post_type' => 'listing',
                'post__in' => $favorites,
                'posts_per_page' => -1
            ));

            if ($favorites_query->have_posts()) :
                while ($favorites_query->have_posts()) :
                    $favorites_query->the_post();
                    get_template_part('templates/template-parts/cards/listing-swipe-card', null, [
                        'post_id' => get_the_ID(),
                        'size' => 'default'
                    ]);
                endwhile;
                wp_reset_postdata();
            endif;
            ?>
        </div>
    <?php else : ?>
        <div class="hph-no-favorites">
            <p><?php esc_html_e('You haven\'t saved any properties to your favorites yet.', 'happy-place'); ?></p>
            <a href="<?php echo esc_url(get_post_type_archive_link('listing')); ?>" class="hph-btn hph-btn-primary">
                <?php esc_html_e('Browse Listings', 'happy-place'); ?>
            </a>
        </div>
    <?php endif; ?>
</div>