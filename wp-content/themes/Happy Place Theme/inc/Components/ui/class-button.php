<?php
/**
 * Button Component
 *
 * Provides reusable button functionality for the Happy Place theme.
 * 
 * @package HappyPlace
 * @subpackage Components\Ui
 * @since 1.0.0
 */

namespace HappyPlace\Components\Ui;

use HappyPlace\Components\Base_Component;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Button Component Class
 * 
 * Creates customizable buttons with various styles and states.
 */
class Button extends Base_Component {
    
    /**
     * Component name identifier
     */
    protected function get_component_name() {
        return 'button';
    }
    
    /**
     * Default component properties
     */
    protected function get_defaults() {
        return [
            'text' => '',
            'type' => 'button', // button, submit, reset
            'variant' => 'primary', // primary, secondary, success, danger, warning, info, light, dark, link
            'size' => 'medium', // small, medium, large
            'disabled' => false,
            'loading' => false,
            'block' => false,
            'outline' => false,
            'icon' => '',
            'icon_position' => 'left', // left, right
            'href' => '',
            'target' => '',
            'rel' => '',
            'class' => '',
            'id' => '',
            'name' => '',
            'value' => '',
            'data_attributes' => [],
            'aria_label' => '',
            'title' => '',
            'form' => '',
            'formaction' => '',
            'formmethod' => '',
            'formtarget' => '',
            'formnovalidate' => false,
            'onclick' => '',
            'badge' => '',
            'badge_variant' => 'light'
        ];
    }
    
    /**
     * Validate and sanitize properties
     */
    protected function validate_props() {
        // Sanitize text
        $this->props['text'] = sanitize_text_field($this->props['text']);
        
        // Validate type
        $valid_types = ['button', 'submit', 'reset'];
        if (!in_array($this->props['type'], $valid_types)) {
            $this->props['type'] = 'button';
        }
        
        // Validate variant
        $valid_variants = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark', 'link'];
        if (!in_array($this->props['variant'], $valid_variants)) {
            $this->props['variant'] = 'primary';
        }
        
        // Validate size
        $valid_sizes = ['small', 'medium', 'large'];
        if (!in_array($this->props['size'], $valid_sizes)) {
            $this->props['size'] = 'medium';
        }
        
        // Validate icon position
        $valid_positions = ['left', 'right'];
        if (!in_array($this->props['icon_position'], $valid_positions)) {
            $this->props['icon_position'] = 'left';
        }
        
        // Sanitize attributes
        $this->props['class'] = sanitize_html_class($this->props['class']);
        $this->props['id'] = sanitize_html_class($this->props['id']);
        $this->props['name'] = sanitize_key($this->props['name']);
        $this->props['value'] = sanitize_text_field($this->props['value']);
        $this->props['href'] = esc_url($this->props['href']);
        $this->props['target'] = sanitize_text_field($this->props['target']);
        $this->props['rel'] = sanitize_text_field($this->props['rel']);
        $this->props['aria_label'] = sanitize_text_field($this->props['aria_label']);
        $this->props['title'] = sanitize_text_field($this->props['title']);
        $this->props['form'] = sanitize_html_class($this->props['form']);
        $this->props['formaction'] = esc_url($this->props['formaction']);
        $this->props['formmethod'] = sanitize_text_field($this->props['formmethod']);
        $this->props['formtarget'] = sanitize_text_field($this->props['formtarget']);
        
        // Ensure boolean values
        $this->props['disabled'] = (bool) $this->props['disabled'];
        $this->props['loading'] = (bool) $this->props['loading'];
        $this->props['block'] = (bool) $this->props['block'];
        $this->props['outline'] = (bool) $this->props['outline'];
        $this->props['formnovalidate'] = (bool) $this->props['formnovalidate'];
    }
    
    /**
     * Generate button classes
     */
    private function get_button_classes() {
        $props = $this->get_props();
        $classes = ['hph-btn'];
        
        // Variant class
        if ($props['outline']) {
            $classes[] = 'hph-btn-outline-' . $props['variant'];
        } else {
            $classes[] = 'hph-btn-' . $props['variant'];
        }
        
        // Size class
        switch ($props['size']) {
            case 'small':
                $classes[] = 'hph-btn-sm';
                break;
            case 'large':
                $classes[] = 'hph-btn-lg';
                break;
            default:
                // Medium is default, no extra class needed
                break;
        }
        
        // Block class
        if ($props['block']) {
            $classes[] = 'hph-btn-block';
        }
        
        // Loading state
        if ($props['loading']) {
            $classes[] = 'hph-btn-loading';
        }
        
        // Custom class
        if (!empty($props['class'])) {
            $classes[] = $props['class'];
        }
        
        return implode(' ', array_unique($classes));
    }
    
    /**
     * Generate data attributes
     */
    private function get_data_attributes() {
        $props = $this->get_props();
        $attributes = [];
        
        // Custom data attributes
        if (!empty($props['data_attributes']) && is_array($props['data_attributes'])) {
            foreach ($props['data_attributes'] as $key => $value) {
                $key = sanitize_key($key);
                if (strpos($key, 'data-') !== 0) {
                    $key = 'data-' . $key;
                }
                $attributes[$key] = esc_attr($value);
            }
        }
        
        return $attributes;
    }
    
    /**
     * Render icon if specified
     */
    private function render_icon() {
        $props = $this->get_props();
        
        if (empty($props['icon'])) {
            return '';
        }
        
        $icon_class = 'hph-btn-icon hph-btn-icon-' . $props['icon_position'];
        
        // Support for different icon formats
        if (strpos($props['icon'], '<') === 0) {
            // Raw HTML icon
            return sprintf('<span class="%s">%s</span>', esc_attr($icon_class), $props['icon']);
        } elseif (strpos($props['icon'], 'fa-') === 0 || strpos($props['icon'], 'fas ') === 0) {
            // Font Awesome icon
            return sprintf('<i class="%s %s"></i>', esc_attr($icon_class), esc_attr($props['icon']));
        } else {
            // SVG or other icon
            return sprintf('<span class="%s hph-icon-%s"></span>', esc_attr($icon_class), esc_attr($props['icon']));
        }
    }
    
    /**
     * Render badge if specified
     */
    private function render_badge() {
        $props = $this->get_props();
        
        if (empty($props['badge'])) {
            return '';
        }
        
        $badge_class = 'hph-badge hph-badge-' . $props['badge_variant'];
        return sprintf('<span class="%s">%s</span>', esc_attr($badge_class), esc_html($props['badge']));
    }
    
    /**
     * Render the button component
     */
    protected function render() {
        $props = $this->get_props();
        
        // Determine if this should be a link or button element
        $is_link = !empty($props['href']);
        $tag = $is_link ? 'a' : 'button';
        
        $classes = $this->get_button_classes();
        $data_attributes = $this->get_data_attributes();
        $icon_html = $this->render_icon();
        $badge_html = $this->render_badge();
        
        // Build attributes array
        $attributes = [];
        
        // Common attributes
        if (!empty($props['id'])) {
            $attributes['id'] = $props['id'];
        }
        
        if (!empty($props['aria_label'])) {
            $attributes['aria-label'] = $props['aria_label'];
        }
        
        if (!empty($props['title'])) {
            $attributes['title'] = $props['title'];
        }
        
        if (!empty($props['onclick'])) {
            $attributes['onclick'] = $props['onclick'];
        }
        
        // Button-specific attributes
        if (!$is_link) {
            $attributes['type'] = $props['type'];
            
            if (!empty($props['name'])) {
                $attributes['name'] = $props['name'];
            }
            
            if (!empty($props['value'])) {
                $attributes['value'] = $props['value'];
            }
            
            if (!empty($props['form'])) {
                $attributes['form'] = $props['form'];
            }
            
            if (!empty($props['formaction'])) {
                $attributes['formaction'] = $props['formaction'];
            }
            
            if (!empty($props['formmethod'])) {
                $attributes['formmethod'] = $props['formmethod'];
            }
            
            if (!empty($props['formtarget'])) {
                $attributes['formtarget'] = $props['formtarget'];
            }
            
            if ($props['formnovalidate']) {
                $attributes['formnovalidate'] = 'formnovalidate';
            }
            
            if ($props['disabled'] || $props['loading']) {
                $attributes['disabled'] = 'disabled';
            }
        }
        
        // Link-specific attributes
        if ($is_link) {
            $attributes['href'] = $props['href'];
            
            if (!empty($props['target'])) {
                $attributes['target'] = $props['target'];
            }
            
            if (!empty($props['rel'])) {
                $attributes['rel'] = $props['rel'];
            }
            
            if ($props['disabled']) {
                $attributes['aria-disabled'] = 'true';
                $attributes['tabindex'] = '-1';
            }
        }
        
        // Merge data attributes
        $attributes = array_merge($attributes, $data_attributes);
        
        // Convert attributes to string
        $attr_string = '';
        foreach ($attributes as $key => $value) {
            $attr_string .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
        }
        
        // Build content
        $content_parts = [];
        
        if (!empty($icon_html) && $props['icon_position'] === 'left') {
            $content_parts[] = $icon_html;
        }
        
        if (!empty($props['text'])) {
            $text_class = $props['loading'] ? 'hph-btn-text hph-btn-text-loading' : 'hph-btn-text';
            $content_parts[] = sprintf('<span class="%s">%s</span>', esc_attr($text_class), esc_html($props['text']));
        }
        
        if (!empty($icon_html) && $props['icon_position'] === 'right') {
            $content_parts[] = $icon_html;
        }
        
        if (!empty($badge_html)) {
            $content_parts[] = $badge_html;
        }
        
        if ($props['loading']) {
            $content_parts[] = '<span class="hph-btn-spinner" aria-hidden="true"></span>';
        }
        
        $content = implode(' ', $content_parts);
        
        printf(
            '<%s class="%s"%s>%s</%s>',
            esc_attr($tag),
            esc_attr($classes),
            $attr_string,
            $content,
            esc_attr($tag)
        );
    }
    
    /**
     * Static method to create and render a button
     */
    public static function create($props = []) {
        $button = new self($props);
        return $button->display();
    }
    
    /**
     * Static method to get button HTML without rendering
     */
    public static function get_html($props = []) {
        $button = new self($props);
        ob_start();
        $button->display();
        return ob_get_clean();
    }
}
