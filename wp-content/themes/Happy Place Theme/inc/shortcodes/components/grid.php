<?php
/**
 * Grid Shortcode Component
 * 
 * @package HappyPlace
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HPH_Grid_Shortcode extends HPH_Shortcode_Base {
    
    protected $tag = 'hph_grid';
    
    protected $supports_content = true;
    
    protected $defaults = array(
        'columns' => '3',
        'columns_tablet' => '',
        'columns_mobile' => '1',
        'gap' => 'md',
        'align_items' => 'stretch',
        'justify_items' => 'stretch',
        'responsive' => 'true',
        'class' => '',
        'style' => 'default'
    );
    
    protected function generate_output($atts, $content = null) {
        $id = $this->generate_id('grid');
        
        // Build CSS classes
        $classes = array('hph-grid');
        
        // Add column classes
        $classes[] = 'hph-grid--' . $atts['columns'];
        
        // Add gap class
        $classes[] = 'hph-grid--gap-' . $atts['gap'];
        
        // Add alignment classes
        if (!empty($atts['align_items']) && $atts['align_items'] !== 'stretch') {
            $classes[] = 'hph-grid--items-' . $atts['align_items'];
        }
        
        if (!empty($atts['justify_items']) && $atts['justify_items'] !== 'stretch') {
            $classes[] = 'hph-grid--justify-' . $atts['justify_items'];
        }
        
        // Add responsive classes
        if ($atts['responsive'] === 'true') {
            $classes[] = 'hph-grid--responsive';
        }
        
        // Add custom class
        if (!empty($atts['class'])) {
            $classes[] = $atts['class'];
        }
        
        // Build inline styles for custom column counts
        $styles = array();
        
        // Desktop columns
        if (is_numeric($atts['columns'])) {
            $styles[] = '--hph-grid-columns: ' . intval($atts['columns']);
        }
        
        // Tablet columns
        if (!empty($atts['columns_tablet']) && is_numeric($atts['columns_tablet'])) {
            $styles[] = '--hph-grid-columns-tablet: ' . intval($atts['columns_tablet']);
        }
        
        // Mobile columns
        if (!empty($atts['columns_mobile']) && is_numeric($atts['columns_mobile'])) {
            $styles[] = '--hph-grid-columns-mobile: ' . intval($atts['columns_mobile']);
        }
        
        $style_attr = !empty($styles) ? ' style="' . implode('; ', $styles) . '"' : '';
        
        // Build attributes
        $attributes = array();
        $attributes[] = 'id="' . esc_attr($id) . '"';
        $attributes[] = 'class="' . esc_attr(implode(' ', $classes)) . '"';
        
        ob_start();
        ?>
        <div <?php echo implode(' ', $attributes); ?><?php echo $style_attr; ?>>
            <?php echo do_shortcode($content); ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
}
