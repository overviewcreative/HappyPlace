<?php
/**
 * Modal Component
 *
 * Provides reusable modal functionality for the Happy Place theme.
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
 * Modal Component Class
 * 
 * Creates customizable modal dialogs with various sizes and content types.
 */
class Modal extends Base_Component {
    
    /**
     * Component name identifier
     */
    protected function get_component_name() {
        return 'modal';
    }
    
    /**
     * Default component properties
     */
    protected function get_defaults() {
        return [
            'id' => '',
            'title' => '',
            'content' => '',
            'size' => 'medium', // small, medium, large, extra-large
            'footer' => '',
            'backdrop' => true,
            'keyboard' => true,
            'focus' => true,
            'show' => false,
            'centered' => true,
            'scrollable' => false,
            'fade' => true,
            'class' => '',
            'header_class' => '',
            'body_class' => '',
            'footer_class' => '',
            'close_button' => true,
            'data_attributes' => [],
            'aria_label' => '',
            'role' => 'dialog'
        ];
    }
    
    /**
     * Validate and sanitize properties
     */
    protected function validate_props() {
        // Generate unique ID if not provided
        if (empty($this->props['id'])) {
            $this->props['id'] = 'modal-' . wp_generate_uuid4();
        }
        
        // Sanitize ID
        $this->props['id'] = sanitize_html_class($this->props['id']);
        
        // Sanitize title
        $this->props['title'] = sanitize_text_field($this->props['title']);
        
        // Validate size
        $valid_sizes = ['small', 'medium', 'large', 'extra-large'];
        if (!in_array($this->props['size'], $valid_sizes)) {
            $this->props['size'] = 'medium';
        }
        
        // Sanitize classes
        $this->props['class'] = sanitize_html_class($this->props['class']);
        $this->props['header_class'] = sanitize_html_class($this->props['header_class']);
        $this->props['body_class'] = sanitize_html_class($this->props['body_class']);
        $this->props['footer_class'] = sanitize_html_class($this->props['footer_class']);
        
        // Ensure boolean values
        $this->props['backdrop'] = (bool) $this->props['backdrop'];
        $this->props['keyboard'] = (bool) $this->props['keyboard'];
        $this->props['focus'] = (bool) $this->props['focus'];
        $this->props['show'] = (bool) $this->props['show'];
        $this->props['centered'] = (bool) $this->props['centered'];
        $this->props['scrollable'] = (bool) $this->props['scrollable'];
        $this->props['fade'] = (bool) $this->props['fade'];
        $this->props['close_button'] = (bool) $this->props['close_button'];
    }
    
    /**
     * Generate modal classes
     */
    private function get_modal_classes() {
        $props = $this->get_props();
        $classes = ['hph-modal'];
        
        if ($props['fade']) {
            $classes[] = 'fade';
        }
        
        if ($props['show']) {
            $classes[] = 'show';
        }
        
        if (!empty($props['class'])) {
            $classes[] = $props['class'];
        }
        
        return implode(' ', array_unique($classes));
    }
    
    /**
     * Generate modal dialog classes
     */
    private function get_dialog_classes() {
        $props = $this->get_props();
        $classes = ['hph-modal-dialog'];
        
        // Size classes
        switch ($props['size']) {
            case 'small':
                $classes[] = 'hph-modal-sm';
                break;
            case 'large':
                $classes[] = 'hph-modal-lg';
                break;
            case 'extra-large':
                $classes[] = 'hph-modal-xl';
                break;
            default:
                // Medium is default, no extra class needed
                break;
        }
        
        if ($props['centered']) {
            $classes[] = 'hph-modal-dialog-centered';
        }
        
        if ($props['scrollable']) {
            $classes[] = 'hph-modal-dialog-scrollable';
        }
        
        return implode(' ', array_unique($classes));
    }
    
    /**
     * Generate data attributes
     */
    private function get_data_attributes() {
        $props = $this->get_props();
        $attributes = [];
        
        if ($props['backdrop'] === false) {
            $attributes['data-backdrop'] = 'false';
        } elseif ($props['backdrop'] === 'static') {
            $attributes['data-backdrop'] = 'static';
        }
        
        if (!$props['keyboard']) {
            $attributes['data-keyboard'] = 'false';
        }
        
        if (!$props['focus']) {
            $attributes['data-focus'] = 'false';
        }
        
        // Merge custom data attributes
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
     * Render the modal component
     */
    protected function render() {
        $props = $this->get_props();
        
        $modal_classes = $this->get_modal_classes();
        $dialog_classes = $this->get_dialog_classes();
        $data_attributes = $this->get_data_attributes();
        
        // Convert data attributes to string
        $data_attr_string = '';
        foreach ($data_attributes as $key => $value) {
            $data_attr_string .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
        }
        
        $aria_label = !empty($props['aria_label']) ? $props['aria_label'] : $props['title'];
        ?>
        
        <div class="<?php echo esc_attr($modal_classes); ?>" 
             id="<?php echo esc_attr($props['id']); ?>" 
             tabindex="-1" 
             role="<?php echo esc_attr($props['role']); ?>"
             aria-labelledby="<?php echo esc_attr($props['id']); ?>-title"
             aria-label="<?php echo esc_attr($aria_label); ?>"
             aria-hidden="true"
             <?php echo $data_attr_string; ?>>
            
            <div class="<?php echo esc_attr($dialog_classes); ?>">
                <div class="hph-modal-content">
                    
                    <?php if (!empty($props['title']) || $props['close_button']) : ?>
                    <div class="hph-modal-header <?php echo esc_attr($props['header_class']); ?>">
                        <?php if (!empty($props['title'])) : ?>
                        <h5 class="hph-modal-title" id="<?php echo esc_attr($props['id']); ?>-title">
                            <?php echo wp_kses_post($props['title']); ?>
                        </h5>
                        <?php endif; ?>
                        
                        <?php if ($props['close_button']) : ?>
                        <button type="button" 
                                class="hph-modal-close" 
                                data-dismiss="modal" 
                                aria-label="<?php esc_attr_e('Close', 'happy-place'); ?>">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="hph-modal-body <?php echo esc_attr($props['body_class']); ?>">
                        <?php 
                        if (is_callable($props['content'])) {
                            call_user_func($props['content']);
                        } else {
                            echo wp_kses_post($props['content']);
                        }
                        ?>
                    </div>
                    
                    <?php if (!empty($props['footer'])) : ?>
                    <div class="hph-modal-footer <?php echo esc_attr($props['footer_class']); ?>">
                        <?php 
                        if (is_callable($props['footer'])) {
                            call_user_func($props['footer']);
                        } else {
                            echo wp_kses_post($props['footer']);
                        }
                        ?>
                    </div>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
        
        <?php
    }
    
    /**
     * Static method to create and render a modal
     */
    public static function create($props = []) {
        $modal = new self($props);
        return $modal->render();
    }
    
    /**
     * Static method to get modal HTML without rendering
     */
    public static function get_html($props = []) {
        $modal = new self($props);
        ob_start();
        $modal->render();
        return ob_get_clean();
    }
    
    /**
     * Enqueue modal assets
     */
    public static function enqueue_assets() {
        // Modal CSS is included in the main theme stylesheet
        // Modal JavaScript functionality
        wp_add_inline_script('happy-place-main', self::get_modal_js(), 'after');
    }
    
    /**
     * Get modal JavaScript functionality
     */
    private static function get_modal_js() {
        return "
        (function($) {
            'use strict';
            
            // Modal functionality
            $(document).ready(function() {
                // Open modal
                $('[data-toggle=\"modal\"]').on('click', function(e) {
                    e.preventDefault();
                    var target = $(this).data('target') || $(this).attr('href');
                    if (target) {
                        $(target).addClass('show').attr('aria-hidden', 'false');
                        $('body').addClass('hph-modal-open');
                    }
                });
                
                // Close modal
                $('[data-dismiss=\"modal\"], .hph-modal').on('click', function(e) {
                    if (e.target === this || $(e.target).is('[data-dismiss=\"modal\"]')) {
                        var modal = $(this).closest('.hph-modal');
                        modal.removeClass('show').attr('aria-hidden', 'true');
                        $('body').removeClass('hph-modal-open');
                    }
                });
                
                // Keyboard support
                $(document).on('keydown', function(e) {
                    if (e.key === 'Escape') {
                        $('.hph-modal.show').each(function() {
                            if ($(this).data('keyboard') !== false) {
                                $(this).removeClass('show').attr('aria-hidden', 'true');
                                $('body').removeClass('hph-modal-open');
                            }
                        });
                    }
                });
                
                // Focus management
                $('.hph-modal').on('shown.hph.modal', function() {
                    if ($(this).data('focus') !== false) {
                        $(this).find('[autofocus]').first().focus();
                        if (!$(this).find('[autofocus]').length) {
                            $(this).find('input, select, textarea, button').first().focus();
                        }
                    }
                });
            });
            
        })(jQuery);
        ";
    }
}
