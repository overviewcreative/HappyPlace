<?php
/**
 * Minimal Single Listing Template for Testing
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$listing_id = get_the_ID();
?>

<div id="primary" class="content-area single-listing">
    <div class="listing-page" data-listing-id="<?php echo esc_attr($listing_id); ?>">
        
        <div style="padding: 40px; background: #fff; margin: 20px; border: 2px solid #333;">
            <h1 style="color: #333; font-size: 2em; margin-bottom: 20px;">
                MINIMAL TEMPLATE TEST
            </h1>
            
            <div style="background: #f9f9f9; padding: 20px; margin: 20px 0;">
                <h2>Basic Information</h2>
                <p><strong>Listing ID:</strong> <?php echo $listing_id; ?></p>
                <p><strong>Post Type:</strong> <?php echo get_post_type($listing_id); ?></p>
                <p><strong>Post Status:</strong> <?php echo get_post_status($listing_id); ?></p>
                <p><strong>Title:</strong> <?php echo get_the_title($listing_id); ?></p>
                <p><strong>Template File:</strong> <?php echo __FILE__; ?></p>
            </div>
            
            <div style="background: #e8f5e8; padding: 20px; margin: 20px 0;">
                <h2>Bridge Functions Test</h2>
                <p><strong>hph_bridge_get_price exists:</strong> <?php echo function_exists('hph_bridge_get_price') ? 'YES' : 'NO'; ?></p>
                <?php if (function_exists('hph_bridge_get_price')) : ?>
                    <p><strong>Price:</strong> <?php echo hph_bridge_get_price($listing_id, true); ?></p>
                <?php endif; ?>
                
                <p><strong>hph_bridge_get_address exists:</strong> <?php echo function_exists('hph_bridge_get_address') ? 'YES' : 'NO'; ?></p>
                <?php if (function_exists('hph_bridge_get_address')) : ?>
                    <p><strong>Address:</strong> <?php echo hph_bridge_get_address($listing_id, 'full'); ?></p>
                <?php endif; ?>
            </div>
            
            <div style="background: #fff3cd; padding: 20px; margin: 20px 0;">
                <h2>ACF Fields Test</h2>
                <?php if (function_exists('get_field')) : ?>
                    <p><strong>ACF is available</strong></p>
                    <p><strong>Price field:</strong> <?php echo get_field('price', $listing_id) ?: 'Not set'; ?></p>
                    <p><strong>Address field:</strong> <?php echo get_field('address_street', $listing_id) ?: 'Not set'; ?></p>
                    <p><strong>Bedrooms:</strong> <?php echo get_field('bedrooms', $listing_id) ?: 'Not set'; ?></p>
                <?php else : ?>
                    <p><strong>ACF is NOT available</strong></p>
                <?php endif; ?>
            </div>
            
            <?php if (have_posts()) : ?>
                <?php while (have_posts()) : the_post(); ?>
                    <div style="background: #f0f8ff; padding: 20px; margin: 20px 0;">
                        <h2>Post Content</h2>
                        <?php the_content(); ?>
                    </div>
                <?php endwhile; ?>
            <?php else : ?>
                <div style="background: #ffe6e6; padding: 20px; margin: 20px 0;">
                    <p>No posts found</p>
                </div>
            <?php endif; ?>
            
        </div>
        
    </div>
</div>

<?php get_footer(); ?>
