<?php
/**
 * Single Listing Template
 * 
 * Template for displaying single listing posts
 * This file provides the WordPress template hierarchy fallback
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

// Check if we should use the organized template instead
$organized_template = get_template_directory() . '/templates/listing/single-listing.php';

if (file_exists($organized_template)) {
    // Use the organized template structure
    include $organized_template;
    return;
}

// Fallback template if organized structure doesn't exist
get_header(); ?>

<div class="listing-page listing-page--fallback" data-listing-id="<?php echo esc_attr(get_the_ID()); ?>">
    <?php while (have_posts()) : the_post(); ?>
        
        <main class="listing-single">
            <div class="container">
                <article id="post-<?php the_ID(); ?>" <?php post_class('listing-single__article'); ?>>
                    
                    <header class="listing-single__header">
                        <h1 class="listing-single__title"><?php the_title(); ?></h1>
                        
                        <?php if (function_exists('hph_get_listing_data')) : ?>
                            <?php $listing_data = hph_get_listing_data(get_the_ID()); ?>
                            <?php if ($listing_data && isset($listing_data['price'])) : ?>
                                <div class="listing-single__price">
                                    <?php echo esc_html($listing_data['price']); ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </header>
                    
                    <div class="listing-single__content">
                        
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="listing-single__featured-image">
                                <?php the_post_thumbnail('listing-hero', ['class' => 'listing-single__image']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="listing-single__description">
                            <?php the_content(); ?>
                        </div>
                        
                        <?php 
                        // Try to load template parts if Template_Loader is available
                        if (function_exists('hph_get_template_part')) {
                            hph_get_template_part('quick-facts', '', ['listing_id' => get_the_ID()]);
                            hph_get_template_part('photo-gallery', '', ['listing_id' => get_the_ID()]);
                        }
                        ?>
                        
                    </div>
                    
                </article>
            </div>
        </main>
        
    <?php endwhile; ?>
</div>

<?php get_footer(); ?>
