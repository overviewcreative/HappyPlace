<?php
/**
 * Template part for displaying listing gallery section
 * 
 * This is a fallback template part used when the Gallery component is not available.
 * 
 * @package HappyPlace
 * @subpackage TemplateParts
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get listing data
$listing_id = isset($listing_id) ? $listing_id : get_the_ID();
$gallery_data = isset($gallery_data) ? $gallery_data : [];

// Fallback data if not provided
if (empty($gallery_data)) {
    $gallery_data = function_exists('hph_bridge_get_gallery_data') 
        ? hph_bridge_get_gallery_data($listing_id)
        : hph_fallback_get_gallery_data($listing_id);
}

$images = $gallery_data['images'] ?? [];
$videos = $gallery_data['videos'] ?? [];
?>

<?php if (!empty($images) || !empty($videos)): ?>
<section class="hph-listing-gallery fallback-gallery">
    <div class="container">
        <h2 class="section-title">Photo Gallery</h2>
        
        <?php if (!empty($images)): ?>
            <div class="gallery-grid">
                <?php foreach ($images as $index => $image): ?>
                    <div class="gallery-item <?php echo $index === 0 ? 'featured' : ''; ?>">
                        <img src="<?php echo esc_url($image['url']); ?>" 
                             alt="<?php echo esc_attr($image['alt'] ?? 'Property image'); ?>"
                             loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>"
                             data-gallery-index="<?php echo $index; ?>">
                        <div class="gallery-overlay">
                            <button class="gallery-expand" data-index="<?php echo $index; ?>">
                                <span class="expand-icon">🔍</span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($videos)): ?>
            <div class="gallery-videos">
                <h3 class="videos-title">Property Videos</h3>
                <div class="videos-grid">
                    <?php foreach ($videos as $video): ?>
                        <div class="video-item">
                            <?php if ($video['type'] === 'youtube'): ?>
                                <div class="video-embed">
                                    <iframe src="<?php echo esc_url($video['embed_url']); ?>" 
                                            title="<?php echo esc_attr($video['title'] ?? 'Property video'); ?>"
                                            frameborder="0" 
                                            allowfullscreen></iframe>
                                </div>
                            <?php else: ?>
                                <video controls>
                                    <source src="<?php echo esc_url($video['url']); ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Gallery Modal for lightbox functionality -->
    <div class="gallery-modal" id="gallery-modal" style="display: none;">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <button class="modal-close">×</button>
            <div class="modal-image-container">
                <img class="modal-image" src="" alt="">
                <button class="modal-prev">‹</button>
                <button class="modal-next">›</button>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>
