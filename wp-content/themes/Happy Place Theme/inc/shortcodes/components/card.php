<?php
/**
 * Card Shortcode Component
 * 
 * @package HappyPlace
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HPH_Card_Shortcode extends HPH_Shortcode_Base {
    
    protected $tag = 'hph_card';
    
    protected $supports_content = true;
    
    protected $defaults = array(
        'title' => '',
        'image' => '',
        'image_alt' => '',
        'link' => '',
        'style' => 'default',
        'size' => 'md',
        'text_align' => 'left',
        'target' => '_self',
        'class' => '',
        'animation' => '',
        'image_position' => 'top'
    );
    
    protected function generate_output($atts, $content = null) {
        $id = $this->generate_id('card');
        $classes = $this->get_css_classes('hph-card', $atts);
        
        // Add additional modifier classes
        if (!empty($atts['text_align'])) {
            $classes .= ' hph-card--text-' . $atts['text_align'];
        }
        
        if (!empty($atts['image_position'])) {
            $classes .= ' hph-card--image-' . $atts['image_position'];
        }
        
        if (!empty($atts['class'])) {
            $classes .= ' ' . $atts['class'];
        }
        
        if (!empty($atts['animation'])) {
            $classes .= ' animate-' . str_replace('_', '-', $atts['animation']);
        }
        
        // Determine if card should be clickable
        $is_clickable = !empty($atts['link']);
        $tag = $is_clickable ? 'a' : 'div';
        
        // Build attributes
        $attributes = array();
        $attributes[] = 'id="' . esc_attr($id) . '"';
        $attributes[] = 'class="' . esc_attr($classes) . '"';
        
        if ($is_clickable) {
            $attributes[] = 'href="' . esc_url($atts['link']) . '"';
            $attributes[] = 'target="' . esc_attr($atts['target']) . '"';
        }
        
        ob_start();
        ?>
        <<?php echo $tag; ?> <?php echo implode(' ', $attributes); ?>>
            <?php if (!empty($atts['image'])) : ?>
                <div class="hph-card__image">
                    <?php
                    $image_url = is_numeric($atts['image']) ? wp_get_attachment_url($atts['image']) : $atts['image'];
                    $image_alt = !empty($atts['image_alt']) ? $atts['image_alt'] : $atts['title'];
                    ?>
                    <img src="<?php echo esc_url($image_url); ?>" 
                         alt="<?php echo esc_attr($image_alt); ?>" 
                         class="hph-card__img" 
                         loading="lazy">
                </div>
            <?php endif; ?>
            
            <div class="hph-card__content">
                <?php if (!empty($atts['title'])) : ?>
                    <h3 class="hph-card__title"><?php echo esc_html($atts['title']); ?></h3>
                <?php endif; ?>
                
                <?php if (!empty($content)) : ?>
                    <div class="hph-card__description">
                        <?php echo wp_kses_post($content); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($is_clickable && !empty($atts['title'])) : ?>
                    <div class="hph-card__link-text">
                        <span><?php esc_html_e('Read More', 'happy-place'); ?></span>
                        <i class="fas fa-arrow-right hph-card__icon" aria-hidden="true"></i>
                    </div>
                <?php endif; ?>
            </div>
        </<?php echo $tag; ?>>
        <?php
        
        return ob_get_clean();
    }
}
