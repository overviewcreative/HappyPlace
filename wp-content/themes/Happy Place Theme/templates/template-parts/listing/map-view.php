<?php
/**
 * Template part for displaying the map view
 * 
 * @package HappyPlace
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="hph-map-container hph-relative hph-h-screen">
    <div id="listings-map" class="hph-map hph-w-full hph-h-full"></div>
    
    <div id="map-listings-preview" class="hph-map-preview hph-absolute hph-top-4 hph-right-4 hph-w-80 hph-max-h-[calc(100vh-2rem)] hph-overflow-y-auto hph-bg-white hph-rounded-lg hph-shadow-lg">
        <?php if (have_posts()) : while (have_posts()) : the_post(); 
            $price = get_field('price');
            $beds = get_field('bedrooms');
            $baths = get_field('bathrooms');
            $sqft = get_field('square_footage');
            $lat = get_field('latitude');
            $lng = get_field('longitude');

            $thumbnail = '';
            if (function_exists('has_post_thumbnail') && has_post_thumbnail()) {
                $thumbnail = get_the_post_thumbnail(get_the_ID(), 'thumbnail', ['class' => 'hph-w-full hph-h-32 hph-object-cover']);
            }
        ?>
            <div class="hph-map-card hph-p-4 hph-border-b hph-border-gray-200 hover:hph-bg-gray-50 hph-transition-colors" 
                 data-id="<?php echo get_the_ID(); ?>" 
                 data-lat="<?php echo esc_attr($lat); ?>" 
                 data-lng="<?php echo esc_attr($lng); ?>">
                
                <div class="hph-map-card__media hph-mb-3">
                    <?php if ($thumbnail) : ?>
                        <?php echo $thumbnail; ?>
                    <?php else : ?>
                        <img src="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'assets/images/placeholder.jpg'); ?>" 
                             alt="<?php esc_attr_e('No image available', 'happy-place'); ?>"
                             class="hph-w-full hph-h-32 hph-object-cover">
                    <?php endif; ?>
                </div>

                <div class="hph-map-card__content">
                    <h4 class="hph-text-lg hph-font-semibold hph-mb-2"><?php echo get_the_title(); ?></h4>
                    
                    <?php if ($price) : ?>
                        <div class="hph-badge hph-badge--primary hph-mb-3">
                            <?php echo HPH_Theme::format_price($price); ?>
                        </div>
                    <?php endif; ?>

                    <div class="hph-flex hph-items-center hph-space-x-4 hph-text-sm hph-text-gray-600">
                        <?php if ($beds) : ?>
                            <span class="hph-flex hph-items-center">
                                <i class="fas fa-bed hph-mr-1"></i>
                                <?php echo esc_html($beds); ?> <?php echo _n('Bed', 'Beds', $beds, 'happy-place'); ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($baths) : ?>
                            <span class="hph-flex hph-items-center">
                                <i class="fas fa-bath hph-mr-1"></i>
                                <?php echo esc_html($baths); ?> <?php echo _n('Bath', 'Baths', $baths, 'happy-place'); ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($sqft) : ?>
                            <span class="hph-flex hph-items-center">
                                <i class="fas fa-ruler-combined hph-mr-1"></i>
                                <?php echo number_format($sqft); ?> <?php esc_html_e('sq ft', 'happy-place'); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <a href="<?php the_permalink(); ?>" class="hph-btn hph-btn--text hph-mt-3">
                        <?php esc_html_e('View Details', 'happy-place'); ?>
                        <i class="fas fa-arrow-right hph-ml-1"></i>
                    </a>
                </div>
            </div>
        <?php endwhile; endif; ?>
    </div>

    <div id="map-controls" class="hph-map-controls hph-absolute hph-top-4 hph-left-4 hph-space-y-2">
        <button class="hph-btn hph-btn--white hph-shadow-lg" id="map-center">
            <i class="fas fa-crosshairs"></i>
        </button>
        <button class="hph-btn hph-btn--white hph-shadow-lg" id="map-zoom-in">
            <i class="fas fa-plus"></i>
        </button>
        <button class="hph-btn hph-btn--white hph-shadow-lg" id="map-zoom-out">
            <i class="fas fa-minus"></i>
        </button>
    </div>
</div>
