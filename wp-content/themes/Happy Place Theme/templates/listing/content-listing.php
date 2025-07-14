<?php

/**
 * Template part for displaying listing content in the archive
 *
 * @package Happy_Place_Theme
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

$price = get_field('price');
$bedrooms = get_field('bedrooms');
$bathrooms = get_field('bathrooms');
$square_footage = get_field('square_footage');
$status = get_field('status');
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('hph-listing-card'); ?>>
    <div class="hph-card-image">
        <?php if (has_post_thumbnail()) : ?>
            <?php the_post_thumbnail('medium_large'); ?>
        <?php endif; ?>

        <?php if ($status) : ?>
            <div class="hph-listing-status hph-listing-status--<?php echo esc_attr($status); ?>">
                <?php echo esc_html($status); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="hph-card-content">
        <?php if ($price) : ?>
            <div class="hph-listing-price">
                <?php echo esc_html('$' . number_format($price)); ?>
            </div>
        <?php endif; ?>

        <h2 class="hph-listing-title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h2>

        <?php if (function_exists('get_field')) : ?>
            <?php if ($address = get_field('address')) : ?>
                <p class="hph-listing-address">
                    <i class="fas fa-map-marker-alt"></i>
                    <?php echo esc_html($address); ?>
                </p>
            <?php endif; ?>
        <?php endif; ?>

        <div class="hph-listing-details">
            <?php if ($bedrooms) : ?>
                <span class="hph-detail">
                    <i class="fas fa-bed"></i>
                    <?php echo esc_html($bedrooms); ?> beds
                </span>
            <?php endif; ?>

            <?php if ($bathrooms) : ?>
                <span class="hph-detail">
                    <i class="fas fa-bath"></i>
                    <?php echo esc_html($bathrooms); ?> baths
                </span>
            <?php endif; ?>

            <?php if ($square_footage) : ?>
                <span class="hph-detail">
                    <i class="fas fa-vector-square"></i>
                    <?php echo number_format($square_footage); ?> sq ft
                </span>
            <?php endif; ?>
        </div>
    </div>
</article>