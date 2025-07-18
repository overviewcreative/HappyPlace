<?php
/**
 * Single listing gallery
 */

$listing_id = get_the_ID();
$gallery_images = get_post_meta($listing_id, 'listing_gallery', true);
$featured_image = get_the_post_thumbnail_url($listing_id, 'full');

// If no gallery, use featured image
if (empty($gallery_images) && $featured_image) {
    $gallery_images = array($featured_image);
}
?>

<div class="listing-gallery" data-listing-id="<?php echo esc_attr($listing_id); ?>">
    <?php if (!empty($gallery_images)) : ?>
        <button class="favorite-btn" data-listing-id="<?php echo esc_attr($listing_id); ?>">♡</button>
        
        <div class="gallery-main-container">
            <img src="<?php echo esc_url($gallery_images[0]); ?>" 
                 alt="<?php echo esc_attr(get_the_title()); ?>" 
                 class="gallery-main">
        </div>
        
        <div class="gallery-counter">
            <span class="current">1</span> / <span class="total"><?php echo count($gallery_images); ?></span>
        </div>
        
        <?php if (count($gallery_images) > 1) : ?>
            <div class="gallery-nav">
                <?php foreach ($gallery_images as $index => $image) : ?>
                    <div class="gallery-dot <?php echo $index === 0 ? 'active' : ''; ?>" 
                         data-index="<?php echo $index; ?>"
                         data-image="<?php echo esc_url($image); ?>"></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="gallery-data" style="display: none;">
            <?php echo wp_json_encode($gallery_images); ?>
        </div>
    <?php else : ?>
        <div class="no-gallery">
            <div class="placeholder-image">📷 No images available</div>
        </div>
    <?php endif; ?>
</div>
