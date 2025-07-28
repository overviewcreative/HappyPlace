<?php
/**
 * Spacer Shortcode Component
 * 
 * @package HappyPlace
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HPH_Spacer_Shortcode extends HPH_Shortcode_Base {
    
    protected $tag = 'hph_spacer';
    
    protected $defaults = array(
        'size' => 'md',
        'height' => '',
        'class' => ''
    );
    
    protected function generate_output($atts, $content = null) {
        $classes = array('hph-spacer');
        
        // Add size modifier
        if (!empty($atts['size'])) {
            $classes[] = 'hph-spacer--' . $atts['size'];
        }
        
        // Add custom class
        if (!empty($atts['class'])) {
            $classes[] = $atts['class'];
        }
        
        // Handle custom height
        $style_attr = '';
        if (!empty($atts['height'])) {
            $height = is_numeric($atts['height']) ? $atts['height'] . 'px' : $atts['height'];
            $style_attr = ' style="height: ' . esc_attr($height) . '"';
        }
        
        return '<div class="' . esc_attr(implode(' ', $classes)) . '"' . $style_attr . '></div>';
    }
}

/**
 * Divider Shortcode Component
 * 
 * @package HappyPlace
 * @since 2.1.0
 */
class HPH_Divider_Shortcode extends HPH_Shortcode_Base {
    
    protected $tag = 'hph_divider';
    
    protected $supports_content = true;
    
    protected $defaults = array(
        'style' => 'solid',
        'size' => 'md',
        'color' => '',
        'text' => '',
        'align' => 'center',
        'class' => ''
    );
    
    protected function generate_output($atts, $content = null) {
        $classes = array('hph-divider');
        
        // Add style modifier
        $classes[] = 'hph-divider--' . $atts['style'];
        
        // Add size modifier
        $classes[] = 'hph-divider--' . $atts['size'];
        
        // Add custom class
        if (!empty($atts['class'])) {
            $classes[] = $atts['class'];
        }
        
        // Handle custom color
        $style_attr = '';
        if (!empty($atts['color'])) {
            $style_attr = ' style="border-color: ' . esc_attr($atts['color']) . '"';
        }
        
        // Use content or text attribute
        $divider_text = !empty($content) ? $content : $atts['text'];
        
        if (!empty($divider_text)) {
            $classes[] = 'hph-divider--text';
            $classes[] = 'hph-divider--text-' . $atts['align'];
            
            return '<div class="' . esc_attr(implode(' ', $classes)) . '"' . $style_attr . '><span class="hph-divider__text">' . esc_html($divider_text) . '</span></div>';
        } else {
            return '<hr class="' . esc_attr(implode(' ', $classes)) . '"' . $style_attr . '>';
        }
    }
}
