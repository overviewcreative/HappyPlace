<?php
/**
 * Hero Banner Component
 *
 * Flexible hero banner component for homepage and landing pages.
 * Supports background images, videos, call-to-action buttons, and search forms.
 *
 * @package HappyPlace\Components\UI
 * @since 2.0.0
 */

namespace HappyPlace\Components\UI;

use HappyPlace\Components\Base_Component;

if (!defined('ABSPATH')) {
    exit;
}

class Hero_Banner extends Base_Component {
    
    /**
     * Component name identifier
     *
     * @return string
     */
    protected function get_component_name() {
        return 'hero-banner';
    }
    
    /**
     * Default component properties
     *
     * @return array
     */
    protected function get_defaults() {
        return [
            // Content
            'title' => '',
            'subtitle' => '',
            'description' => '',
            'cta_text' => 'Get Started',
            'cta_url' => '#',
            'secondary_cta_text' => '',
            'secondary_cta_url' => '',
            
            // Background
            'background_type' => 'image', // image, video, color, gradient
            'background_image' => '',
            'background_video' => '',
            'background_color' => '#f8f9fa',
            'background_gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'overlay_opacity' => 0.4,
            'overlay_color' => '#000000',
            
            // Layout
            'height' => 'large', // small, medium, large, full, custom
            'custom_height' => '500px',
            'content_position' => 'center', // left, center, right
            'content_alignment' => 'center', // left, center, right
            'content_width' => 'standard', // narrow, standard, wide, full
            
            // Features
            'show_search_form' => false,
            'show_scroll_indicator' => false,
            'enable_parallax' => false,
            'enable_ken_burns' => false,
            
            // Animation
            'animation_type' => 'fade', // fade, slide, zoom, none
            'animation_delay' => 0,
            'animation_duration' => 1000,
            
            // Responsive
            'hide_on_mobile' => false,
            'mobile_height' => 'medium',
            'mobile_content_position' => 'center'
        ];
    }
    
    /**
     * Validate component properties
     */
    protected function validate_props() {
        // Title is recommended but not required
        if (empty($this->get_prop('title')) && empty($this->get_prop('background_image'))) {
            $this->add_validation_error('Either title or background_image should be provided');
        }
        
        // Validate background type
        $valid_bg_types = ['image', 'video', 'color', 'gradient'];
        if (!in_array($this->get_prop('background_type'), $valid_bg_types)) {
            $this->add_validation_error('Invalid background_type. Must be: ' . implode(', ', $valid_bg_types));
        }
        
        // Validate height
        $valid_heights = ['small', 'medium', 'large', 'full', 'custom'];
        if (!in_array($this->get_prop('height'), $valid_heights)) {
            $this->add_validation_error('Invalid height. Must be: ' . implode(', ', $valid_heights));
        }
    }
    
    /**
     * Render the component
     *
     * @return string
     */
    protected function render() {
        $classes = $this->build_css_classes();
        $styles = $this->build_inline_styles();
        $data_attrs = $this->build_data_attributes();
        
        ob_start();
        ?>
        <section class="<?php echo esc_attr($classes); ?>" 
                 <?php echo $styles; ?>
                 <?php echo $data_attrs; ?>>
            
            <!-- Background -->
            <?php $this->render_background(); ?>
            
            <!-- Overlay -->
            <?php if ($this->get_prop('overlay_opacity') > 0): ?>
                <div class="hph-hero-banner__overlay" 
                     style="background-color: <?php echo esc_attr($this->get_prop('overlay_color')); ?>; 
                            opacity: <?php echo esc_attr($this->get_prop('overlay_opacity')); ?>;"></div>
            <?php endif; ?>
            
            <!-- Content Container -->
            <div class="hph-hero-banner__container">
                <div class="hph-hero-banner__content">
                    
                    <!-- Text Content -->
                    <?php $this->render_content(); ?>
                    
                    <!-- Search Form -->
                    <?php if ($this->get_prop('show_search_form')): ?>
                        <div class="hph-hero-banner__search">
                            <?php $this->render_search_form(); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- CTA Buttons -->
                    <?php $this->render_cta_buttons(); ?>
                    
                </div>
            </div>
            
            <!-- Scroll Indicator -->
            <?php if ($this->get_prop('show_scroll_indicator')): ?>
                <div class="hph-hero-banner__scroll-indicator">
                    <button type="button" class="hph-scroll-indicator" aria-label="<?php esc_attr_e('Scroll down', 'happy-place'); ?>">
                        <span class="hph-scroll-indicator__icon"></span>
                        <span class="hph-scroll-indicator__text"><?php esc_html_e('Scroll', 'happy-place'); ?></span>
                    </button>
                </div>
            <?php endif; ?>
            
        </section>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Build CSS classes for the hero banner
     *
     * @return string
     */
    private function build_css_classes() {
        $classes = ['hph-hero-banner'];
        
        // Height classes
        $classes[] = 'hph-hero-banner--' . $this->get_prop('height');
        
        // Content position
        $classes[] = 'hph-hero-banner--content-' . str_replace('-', '_', $this->get_prop('content_position'));
        
        // Content alignment
        $classes[] = 'hph-hero-banner--align-' . $this->get_prop('content_alignment');
        
        // Content width
        $classes[] = 'hph-hero-banner--width-' . str_replace('-', '_', $this->get_prop('content_width'));
        
        // Background type
        $classes[] = 'hph-hero-banner--bg-' . str_replace('-', '_', $this->get_prop('background_type'));
        
        // Features
        if ($this->get_prop('enable_parallax')) {
            $classes[] = 'hph-hero-banner--parallax';
        }
        
        if ($this->get_prop('enable_ken_burns')) {
            $classes[] = 'hph-hero-banner--ken-burns';
        }
        
        if ($this->get_prop('show_search_form')) {
            $classes[] = 'hph-hero-banner--with-search';
        }
        
        // Animation
        if ($this->get_prop('animation_type') !== 'none') {
            $classes[] = 'hph-hero-banner--animated';
            $classes[] = 'hph-hero-banner--anim-' . str_replace('-', '_', $this->get_prop('animation_type'));
        }
        
        // Responsive
        if ($this->get_prop('hide_on_mobile')) {
            $classes[] = 'hph-hero-banner--hide-mobile';
        }
        
        $classes[] = 'hph-hero-banner--mobile-' . str_replace('-', '_', $this->get_prop('mobile_height'));
        $classes[] = 'hph-hero-banner--mobile-content-' . str_replace('-', '_', $this->get_prop('mobile_content_position'));
        
        return implode(' ', $classes);
    }
    
    /**
     * Build inline styles
     *
     * @return string
     */
    private function build_inline_styles() {
        $styles = [];
        
        // Custom height
        if ($this->get_prop('height') === 'custom' && $this->get_prop('custom_height')) {
            $styles[] = 'height: ' . $this->get_prop('custom_height');
        }
        
        // Background color/gradient
        if ($this->get_prop('background_type') === 'color') {
            $styles[] = 'background-color: ' . $this->get_prop('background_color');
        } elseif ($this->get_prop('background_type') === 'gradient') {
            $styles[] = 'background: ' . $this->get_prop('background_gradient');
        }
        
        return !empty($styles) ? 'style="' . esc_attr(implode('; ', $styles)) . '"' : '';
    }
    
    /**
     * Build data attributes
     *
     * @return string
     */
    private function build_data_attributes() {
        $attrs = [
            'data-component="hero-banner"'
        ];
        
        if ($this->get_prop('animation_type') !== 'none') {
            $attrs[] = 'data-animation="' . esc_attr($this->get_prop('animation_type')) . '"';
            $attrs[] = 'data-animation-delay="' . esc_attr($this->get_prop('animation_delay')) . '"';
            $attrs[] = 'data-animation-duration="' . esc_attr($this->get_prop('animation_duration')) . '"';
        }
        
        if ($this->get_prop('enable_parallax')) {
            $attrs[] = 'data-parallax="true"';
        }
        
        return implode(' ', $attrs);
    }
    
    /**
     * Render background element
     */
    private function render_background() {
        $bg_type = $this->get_prop('background_type');
        
        switch ($bg_type) {
            case 'image':
                $this->render_background_image();
                break;
            case 'video':
                $this->render_background_video();
                break;
        }
    }
    
    /**
     * Render background image
     */
    private function render_background_image() {
        $image = $this->get_prop('background_image');
        if (!$image) return;
        
        // Handle different image input types
        if (is_numeric($image)) {
            $image_url = wp_get_attachment_image_url($image, 'full');
            $alt_text = get_post_meta($image, '_wp_attachment_image_alt', true);
        } elseif (is_array($image)) {
            $image_url = $image['url'] ?? '';
            $alt_text = $image['alt'] ?? '';
        } else {
            $image_url = $image;
            $alt_text = '';
        }
        
        if ($image_url): ?>
            <div class="hph-hero-banner__background">
                <img src="<?php echo esc_url($image_url); ?>" 
                     alt="<?php echo esc_attr($alt_text); ?>" 
                     class="hph-hero-banner__bg-image">
            </div>
        <?php endif;
    }
    
    /**
     * Render background video
     */
    private function render_background_video() {
        $video = $this->get_prop('background_video');
        if (!$video) return;
        
        // Handle different video input types
        if (is_numeric($video)) {
            $video_url = wp_get_attachment_url($video);
        } elseif (is_array($video)) {
            $video_url = $video['url'] ?? '';
        } else {
            $video_url = $video;
        }
        
        if ($video_url): ?>
            <div class="hph-hero-banner__background">
                <video class="hph-hero-banner__bg-video" autoplay muted loop playsinline>
                    <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                    <?php esc_html_e('Your browser does not support the video tag.', 'happy-place'); ?>
                </video>
            </div>
        <?php endif;
    }
    
    /**
     * Render text content
     */
    private function render_content() {
        $title = $this->get_prop('title');
        $subtitle = $this->get_prop('subtitle');
        $description = $this->get_prop('description');
        
        if (!$title && !$subtitle && !$description) return;
        ?>
        
        <div class="hph-hero-banner__text">
            <?php if ($subtitle): ?>
                <div class="hph-hero-banner__subtitle">
                    <span class="hph-subtitle"><?php echo wp_kses_post($subtitle); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($title): ?>
                <h1 class="hph-hero-banner__title">
                    <?php echo wp_kses_post($title); ?>
                </h1>
            <?php endif; ?>
            
            <?php if ($description): ?>
                <div class="hph-hero-banner__description">
                    <p class="hph-description"><?php echo wp_kses_post($description); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php
    }
    
    /**
     * Render search form
     */
    private function render_search_form() {
        // Check if search form component exists
        if (class_exists('HappyPlace\Components\UI\Search_Form')) {
            $search_form = new \HappyPlace\Components\UI\Search_Form([
                'style' => 'hero',
                'show_advanced' => false,
                'placeholder' => __('Search properties...', 'happy-place')
            ]);
            echo $search_form->render();
        } else {
            // Fallback simple search form
            ?>
            <div class="hph-hero-search-form">
                <form method="get" action="<?php echo esc_url(home_url('/properties/')); ?>" class="hph-search-form hph-search-form--hero">
                    <div class="hph-search-form__field">
                        <input type="text" 
                               name="search" 
                               placeholder="<?php esc_attr_e('Search properties...', 'happy-place'); ?>" 
                               class="hph-search-form__input">
                    </div>
                    <button type="submit" class="hph-search-form__submit hph-btn hph-btn--primary">
                        <i class="hph-icon hph-icon--search" aria-hidden="true"></i>
                        <span><?php esc_html_e('Search', 'happy-place'); ?></span>
                    </button>
                </form>
            </div>
            <?php
        }
    }
    
    /**
     * Render CTA buttons
     */
    private function render_cta_buttons() {
        $cta_text = $this->get_prop('cta_text');
        $cta_url = $this->get_prop('cta_url');
        $secondary_cta_text = $this->get_prop('secondary_cta_text');
        $secondary_cta_url = $this->get_prop('secondary_cta_url');
        
        if (!$cta_text && !$secondary_cta_text) return;
        ?>
        
        <div class="hph-hero-banner__actions">
            <div class="hph-hero-actions">
                <?php if ($cta_text && $cta_url): ?>
                    <a href="<?php echo esc_url($cta_url); ?>" 
                       class="hph-btn hph-btn--primary hph-btn--large hph-hero-actions__primary">
                        <?php echo esc_html($cta_text); ?>
                    </a>
                <?php endif; ?>
                
                <?php if ($secondary_cta_text && $secondary_cta_url): ?>
                    <a href="<?php echo esc_url($secondary_cta_url); ?>" 
                       class="hph-btn hph-btn--secondary hph-btn--large hph-hero-actions__secondary">
                        <?php echo esc_html($secondary_cta_text); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php
    }
}
