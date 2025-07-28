<?php
/**
 * Button Shortcode Component
 * 
 * @package HappyPlace
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HPH_Button_Shortcode extends HPH_Shortcode_Base {
    
    protected $tag = 'hph_button';
    
    protected $defaults = array(
        'text' => 'Click Here',
        'url' => '#',
        'style' => 'primary',
        'size' => 'md',
        'icon' => '',
        'icon_position' => 'left',
        'target' => '_self',
        'rel' => '',
        'class' => '',
        'id' => '',
        'loading' => 'false',
        'disabled' => 'false'
    );
    
    protected function generate_output($atts, $content = null) {
        // Generate unique ID if not provided
        $id = !empty($atts['id']) ? $atts['id'] : $this->generate_id('btn');
        
        // Build CSS classes
        $classes = array('hph-btn');
        $classes[] = 'hph-btn--' . $atts['style'];
        $classes[] = 'hph-btn--' . $atts['size'];
        
        if (!empty($atts['icon'])) {
            $classes[] = 'hph-btn--has-icon';
            $classes[] = 'hph-btn--icon-' . $atts['icon_position'];
        }
        
        if (!empty($atts['class'])) {
            $classes[] = $atts['class'];
        }
        
        if ($atts['loading'] === 'true') {
            $classes[] = 'hph-btn--loading';
        }
        
        if ($atts['disabled'] === 'true') {
            $classes[] = 'hph-btn--disabled';
        }
        
        // Build attributes
        $attributes = array();
        $attributes[] = 'id="' . esc_attr($id) . '"';
        $attributes[] = 'class="' . esc_attr(implode(' ', $classes)) . '"';
        $attributes[] = 'href="' . esc_url($atts['url']) . '"';
        
        if (!empty($atts['target'])) {
            $attributes[] = 'target="' . esc_attr($atts['target']) . '"';
        }
        
        if (!empty($atts['rel'])) {
            $attributes[] = 'rel="' . esc_attr($atts['rel']) . '"';
        }
        
        if ($atts['disabled'] === 'true') {
            $attributes[] = 'aria-disabled="true"';
        }
        
        // Build icon HTML
        $icon_html = '';
        if (!empty($atts['icon'])) {
            $icon_classes = 'hph-btn__icon';
            
            // Support both FontAwesome and custom icon classes
            if (strpos($atts['icon'], 'fa-') !== false) {
                $icon_classes .= ' fas ' . $atts['icon'];
            } else {
                $icon_classes .= ' ' . $atts['icon'];
            }
            
            $icon_html = '<i class="' . esc_attr($icon_classes) . '" aria-hidden="true"></i>';
        }
        
        // Build loading spinner
        $loading_html = '';
        if ($atts['loading'] === 'true') {
            $loading_html = '<span class="hph-btn__spinner" aria-hidden="true"></span>';
        }
        
        // Build button content
        $button_text = !empty($content) ? $content : $atts['text'];
        $text_html = '<span class="hph-btn__text">' . esc_html($button_text) . '</span>';
        
        // Assemble final HTML
        $html = '<a ' . implode(' ', $attributes) . '>';
        
        if ($atts['loading'] === 'true') {
            $html .= $loading_html;
        }
        
        if (!empty($icon_html) && $atts['icon_position'] === 'left') {
            $html .= $icon_html;
        }
        
        $html .= $text_html;
        
        if (!empty($icon_html) && $atts['icon_position'] === 'right') {
            $html .= $icon_html;
        }
        
        $html .= '</a>';
        
        return $html;
    }
}
