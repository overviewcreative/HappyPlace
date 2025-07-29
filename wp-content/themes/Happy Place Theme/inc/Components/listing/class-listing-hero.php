<?php
/**
 * Listing Hero Component
 *
 * Large hero section for single listing pages with image gallery and key information.
 *
 * @package HappyPlace\Components\Listing
 * @since 2.0.0
 */

namespace HappyPlace\Components\Listing;

use HappyPlace\Components\Base_Component;

if (!defined('ABSPATH')) {
    exit;
}

class Listing_Hero extends Base_Component {
    
    /**
     * Component name identifier
     *
     * @return string
     */
    protected function get_component_name() {
        return 'listing-hero';
    }
    
    /**
     * Default component properties
     *
     * @return array
     */
    protected function get_defaults() {
        return [
            'listing_id' => 0,
            'show_gallery' => true,
            'show_status' => true,
            'show_price' => true,
            'show_address' => true,
            'show_key_details' => true,
            'show_cta_buttons' => true,
            'gallery_type' => 'slider', // slider, grid, carousel
            'image_size' => 'full',
            'autoplay' => false,
            'show_thumbnails' => true,
            'height' => 'auto', // auto, full, custom
            'overlay_position' => 'bottom-left' // bottom-left, bottom-right, top-left, top-right, center
        ];
    }
    
    /**
     * Validate component properties
     */
    protected function validate_props() {
        if (empty($this->get_prop('listing_id'))) {
            $this->add_validation_error('listing_id is required');
        }
    }
    
    /**
     * Render the component
     *
     * @return string
     */
    protected function render() {
        $listing_id = $this->get_prop('listing_id');
        
        // Use bridge functions for data access
        $listing_data = hph_get_template_listing_data($listing_id);
        $gallery = hph_get_listing_gallery($listing_id);
        $price = hph_get_listing_price($listing_id, 'display');
        $status = hph_get_listing_status($listing_id);
        $address = hph_get_listing_address($listing_id, 'full');
        
        // Get key details
        $bedrooms = hph_get_listing_bedrooms($listing_id);
        $bathrooms = hph_get_listing_bathrooms($listing_id);
        $sqft = hph_get_listing_square_footage($listing_id);
        
        $height_class = 'hph-listing-hero--' . $this->get_prop('height');
        $overlay_class = 'hph-listing-hero--overlay-' . str_replace('-', '_', $this->get_prop('overlay_position'));
        
        ob_start();
        ?>
        <section class="hph-listing-hero <?php echo esc_attr($height_class . ' ' . $overlay_class); ?>" 
                 data-component="listing-hero"
                 data-listing-id="<?php echo esc_attr($listing_id); ?>">
            
            <!-- Image Gallery -->
            <?php if ($this->get_prop('show_gallery') && !empty($gallery)): ?>
                <div class="hph-listing-hero__gallery">
                    <?php $this->render_gallery($gallery); ?>
                </div>
            <?php else: ?>
                <div class="hph-listing-hero__featured-image">
                    <?php 
                    $featured_image = hph_get_listing_featured_image($listing_id, $this->get_prop('image_size'));
                    if ($featured_image): 
                    ?>
                        <img src="<?php echo esc_url($featured_image); ?>" 
                             alt="<?php echo esc_attr($listing_data['title']); ?>"
                             class="hph-listing-hero__image">
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Content Overlay -->
            <div class="hph-listing-hero__overlay">
                <div class="hph-listing-hero__content">
                    
                    <!-- Status Badge -->
                    <?php if ($this->get_prop('show_status') && $status): ?>
                        <div class="hph-listing-hero__status">
                            <span class="hph-status-badge hph-status-badge--<?php echo esc_attr(strtolower($status)); ?>">
                                <?php echo esc_html($status); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Price -->
                    <?php if ($this->get_prop('show_price') && $price): ?>
                        <div class="hph-listing-hero__price">
                            <span class="hph-price hph-price--large">
                                <?php echo esc_html($price); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Address -->
                    <?php if ($this->get_prop('show_address') && $address): ?>
                        <div class="hph-listing-hero__address">
                            <span class="hph-address hph-address--hero">
                                <?php echo esc_html($address); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Key Details -->
                    <?php if ($this->get_prop('show_key_details')): ?>
                        <div class="hph-listing-hero__details">
                            <div class="hph-key-details hph-key-details--hero">
                                <?php if ($bedrooms): ?>
                                    <span class="hph-detail hph-detail--bedrooms">
                                        <i class="hph-icon hph-icon--bed" aria-hidden="true"></i>
                                        <?php echo esc_html($bedrooms); ?> 
                                        <?php echo esc_html(_n('Bed', 'Beds', $bedrooms, 'happy-place')); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($bathrooms): ?>
                                    <span class="hph-detail hph-detail--bathrooms">
                                        <i class="hph-icon hph-icon--bath" aria-hidden="true"></i>
                                        <?php echo esc_html($bathrooms); ?> 
                                        <?php echo esc_html(_n('Bath', 'Baths', $bathrooms, 'happy-place')); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($sqft): ?>
                                    <span class="hph-detail hph-detail--sqft">
                                        <i class="hph-icon hph-icon--ruler" aria-hidden="true"></i>
                                        <?php echo esc_html(number_format($sqft)); ?> sq ft
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- CTA Buttons -->
                    <?php if ($this->get_prop('show_cta_buttons')): ?>
                        <div class="hph-listing-hero__actions">
                            <div class="hph-hero-actions">
                                <button type="button" 
                                        class="hph-btn hph-btn--primary hph-btn--large"
                                        data-modal-trigger="contact-agent"
                                        data-listing-id="<?php echo esc_attr($listing_id); ?>">
                                    <i class="hph-icon hph-icon--message" aria-hidden="true"></i>
                                    <?php esc_html_e('Contact Agent', 'happy-place'); ?>
                                </button>
                                
                                <button type="button" 
                                        class="hph-btn hph-btn--secondary hph-btn--large"
                                        data-modal-trigger="schedule-tour"
                                        data-listing-id="<?php echo esc_attr($listing_id); ?>">
                                    <i class="hph-icon hph-icon--calendar" aria-hidden="true"></i>
                                    <?php esc_html_e('Schedule Tour', 'happy-place'); ?>
                                </button>
                                
                                <button type="button" 
                                        class="hph-btn hph-btn--outline hph-btn--large hph-btn--favorite"
                                        data-action="toggle-favorite"
                                        data-listing-id="<?php echo esc_attr($listing_id); ?>">
                                    <i class="hph-icon hph-icon--heart" aria-hidden="true"></i>
                                    <span class="hph-sr-only"><?php esc_html_e('Add to Favorites', 'happy-place'); ?></span>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                </div>
            </div>
            
            <!-- Gallery Navigation (if slider) -->
            <?php if ($this->get_prop('show_gallery') && $this->get_prop('gallery_type') === 'slider' && count($gallery) > 1): ?>
                <div class="hph-listing-hero__nav">
                    <button type="button" class="hph-gallery-nav hph-gallery-nav--prev" data-action="prev-slide">
                        <i class="hph-icon hph-icon--chevron-left" aria-hidden="true"></i>
                        <span class="hph-sr-only"><?php esc_html_e('Previous Image', 'happy-place'); ?></span>
                    </button>
                    <button type="button" class="hph-gallery-nav hph-gallery-nav--next" data-action="next-slide">
                        <i class="hph-icon hph-icon--chevron-right" aria-hidden="true"></i>
                        <span class="hph-sr-only"><?php esc_html_e('Next Image', 'happy-place'); ?></span>
                    </button>
                </div>
                
                <!-- Gallery Counter -->
                <div class="hph-listing-hero__counter">
                    <span class="hph-gallery-counter">
                        <span class="hph-gallery-counter__current">1</span>
                        <span class="hph-gallery-counter__separator"> / </span>
                        <span class="hph-gallery-counter__total"><?php echo count($gallery); ?></span>
                    </span>
                </div>
            <?php endif; ?>
            
        </section>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render the image gallery based on type
     *
     * @param array $gallery Gallery images
     */
    private function render_gallery($gallery) {
        if (empty($gallery)) {
            return;
        }
        
        $gallery_type = $this->get_prop('gallery_type');
        $image_size = $this->get_prop('image_size');
        
        switch ($gallery_type) {
            case 'slider':
                $this->render_slider_gallery($gallery, $image_size);
                break;
            case 'grid':
                $this->render_grid_gallery($gallery, $image_size);
                break;
            case 'carousel':
                $this->render_carousel_gallery($gallery, $image_size);
                break;
            default:
                $this->render_slider_gallery($gallery, $image_size);
        }
    }
    
    /**
     * Render slider gallery
     */
    private function render_slider_gallery($gallery, $image_size) {
        $autoplay = $this->get_prop('autoplay') ? 'true' : 'false';
        $show_thumbnails = $this->get_prop('show_thumbnails');
        
        echo '<div class="hph-gallery-slider" data-autoplay="' . esc_attr($autoplay) . '">';
        echo '<div class="hph-gallery-slider__main">';
        
        foreach ($gallery as $index => $image) {
            $active_class = $index === 0 ? 'hph-gallery-slide--active' : '';
            echo '<div class="hph-gallery-slide ' . esc_attr($active_class) . '" data-slide="' . esc_attr($index) . '">';
            
            if (is_array($image)) {
                $image_url = $image['sizes'][$image_size] ?? $image['url'];
                $alt_text = $image['alt'] ?? '';
            } else {
                $image_url = wp_get_attachment_image_url($image, $image_size);
                $alt_text = get_post_meta($image, '_wp_attachment_image_alt', true);
            }
            
            echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($alt_text) . '" class="hph-gallery-slide__image">';
            echo '</div>';
        }
        
        echo '</div>'; // .hph-gallery-slider__main
        
        // Thumbnails
        if ($show_thumbnails && count($gallery) > 1) {
            echo '<div class="hph-gallery-slider__thumbnails">';
            foreach ($gallery as $index => $image) {
                $active_class = $index === 0 ? 'hph-thumbnail--active' : '';
                
                if (is_array($image)) {
                    $thumb_url = $image['sizes']['thumbnail'] ?? $image['url'];
                } else {
                    $thumb_url = wp_get_attachment_image_url($image, 'thumbnail');
                }
                
                echo '<button type="button" class="hph-gallery-thumbnail ' . esc_attr($active_class) . '" data-slide="' . esc_attr($index) . '">';
                echo '<img src="' . esc_url($thumb_url) . '" alt="" class="hph-thumbnail__image">';
                echo '</button>';
            }
            echo '</div>';
        }
        
        echo '</div>'; // .hph-gallery-slider
    }
    
    /**
     * Render grid gallery
     */
    private function render_grid_gallery($gallery, $image_size) {
        echo '<div class="hph-gallery-grid">';
        
        foreach ($gallery as $index => $image) {
            if (is_array($image)) {
                $image_url = $image['sizes'][$image_size] ?? $image['url'];
                $alt_text = $image['alt'] ?? '';
            } else {
                $image_url = wp_get_attachment_image_url($image, $image_size);
                $alt_text = get_post_meta($image, '_wp_attachment_image_alt', true);
            }
            
            $featured_class = $index === 0 ? 'hph-gallery-grid__item--featured' : '';
            
            echo '<div class="hph-gallery-grid__item ' . esc_attr($featured_class) . '" data-index="' . esc_attr($index) . '">';
            echo '<button type="button" class="hph-gallery-grid__button" data-action="open-lightbox" data-index="' . esc_attr($index) . '">';
            echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($alt_text) . '" class="hph-gallery-grid__image">';
            echo '</button>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render carousel gallery
     */
    private function render_carousel_gallery($gallery, $image_size) {
        echo '<div class="hph-gallery-carousel">';
        echo '<div class="hph-gallery-carousel__track">';
        
        foreach ($gallery as $index => $image) {
            if (is_array($image)) {
                $image_url = $image['sizes'][$image_size] ?? $image['url'];
                $alt_text = $image['alt'] ?? '';
            } else {
                $image_url = wp_get_attachment_image_url($image, $image_size);
                $alt_text = get_post_meta($image, '_wp_attachment_image_alt', true);
            }
            
            echo '<div class="hph-gallery-carousel__item" data-index="' . esc_attr($index) . '">';
            echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($alt_text) . '" class="hph-gallery-carousel__image">';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
    }
}
