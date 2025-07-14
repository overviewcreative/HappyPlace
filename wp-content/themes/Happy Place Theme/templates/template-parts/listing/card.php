<?php
/**
 * Template part for displaying listing cards
 *
 * @package HappyPlace
 */

// Get template args
$listing_id = $args['listing_id'] ?? get_the_ID();
$is_map_view = $args['is_map_view'] ?? false;

// Get listing data
$price = get_field('price', $listing_id);
$beds = get_field('bedrooms', $listing_id);
$baths = hph_get_listing_bathrooms($listing_id);
$sqft = get_field('square_feet', $listing_id);
$address = hph_format_listing_address($listing_id);
$photo_url = hph_get_listing_photo($listing_id, 'listing-thumb');
$status_terms = get_the_terms($listing_id, 'listing_status');
$status = $status_terms ? $status_terms[0]->name : '';
?>

<article id="listing-<?php echo esc_attr($listing_id); ?>" class="hph-listing-card<?php echo $is_map_view ? ' is-map-view' : ''; ?>">
    <div class="hph-listing-card__media">
        <?php if ($status) : ?>
            <div class="hph-listing-card__status <?php echo sanitize_html_class(strtolower($status)); ?>">
                <?php echo esc_html($status); ?>
            </div>
        <?php endif; ?>
        
        <a href="<?php echo esc_url(get_permalink($listing_id)); ?>" class="hph-listing-card__image-link">
            <img src="<?php echo esc_url($photo_url); ?>" 
                 alt="<?php echo esc_attr(get_the_title($listing_id)); ?>"
                 class="hph-listing-card__image"
                 loading="lazy">
        </a>
    </div>

    <div class="hph-listing-card__content">
        <header class="hph-listing-card__header">
            <h3 class="hph-listing-card__price">
                <?php echo esc_html(HPH_Theme::format_price($price)); ?>
            </h3>
            <?php if ($address) : ?>
                <p class="hph-listing-card__address"><?php echo esc_html($address); ?></p>
            <?php endif; ?>
        </header>

        <div class="hph-listing-card__details">
            <?php if ($beds) : ?>
                <div class="hph-listing-card__detail">
                    <i class="fas fa-bed" aria-hidden="true"></i>
                    <span><?php echo esc_html($beds); ?> <?php echo _n('Bed', 'Beds', $beds, 'happy-place'); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($baths) : ?>
                <div class="hph-listing-card__detail">
                    <i class="fas fa-bath" aria-hidden="true"></i>
                    <span><?php echo esc_html($baths); ?> <?php echo _n('Bath', 'Baths', $baths, 'happy-place'); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($sqft) : ?>
                <div class="hph-listing-card__detail">
                    <i class="fas fa-ruler-combined" aria-hidden="true"></i>
                    <span><?php echo number_format($sqft); ?> <?php esc_html_e('sqft', 'happy-place'); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <footer class="hph-listing-card__footer">
            <a href="<?php echo esc_url(get_permalink($listing_id)); ?>" class="hph-listing-card__link">
                <?php esc_html_e('View Details', 'happy-place'); ?>
            </a>
        </footer>
    </div>
</article>
