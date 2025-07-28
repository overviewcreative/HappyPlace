<?php
/**
 * Hero Shortcode Component
 * 
 * @package HappyPlace
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HPH_Hero_Shortcode extends HPH_Shortcode_Base {
    
    protected $tag = 'hph_hero';
    
    protected $supports_content = true;
    
    protected $defaults = array(
        'title' => '',
        'subtitle' => '',
        'background' => '',
        'background_overlay' => 'dark',
        'size' => 'lg',
        'theme' => 'dark',
        'text_align' => 'center',
        'buttons' => '',
        'height' => 'auto',
        'parallax' => 'false',
        'animation' => 'fade-in'
    );
    
    protected function generate_output($atts, $content = null) {
        $id = $this->generate_id('hero');
        $classes = $this->get_css_classes('hph-hero', $atts);
        
        // Add text alignment class
        if (!empty($atts['text_align'])) {
            $classes .= ' hph-hero--text-' . $atts['text_align'];
        }
        
        // Add animation class
        if (!empty($atts['animation'])) {
            $classes .= ' animate-' . str_replace('_', '-', $atts['animation']);
        }
        
        // Build inline styles
        $styles = array();
        
        if (!empty($atts['background'])) {
            $styles[] = "background-image: url('" . esc_url($atts['background']) . "')";
        }
        
        if (!empty($atts['height']) && $atts['height'] !== 'auto') {
            $styles[] = "min-height: " . esc_attr($atts['height']);
        }
        
        $style_attr = !empty($styles) ? ' style="' . implode('; ', $styles) . '"' : '';
        
        // Parse buttons
        $buttons_html = $this->parse_buttons($atts['buttons']);
        
        ob_start();
        ?>
        <section id="<?php echo esc_attr($id); ?>" class="<?php echo esc_attr($classes); ?>"<?php echo $style_attr; ?>>
            <?php if (!empty($atts['background'])) : ?>
                <div class="hph-hero__background"<?php echo $atts['parallax'] === 'true' ? ' data-parallax="true"' : ''; ?>></div>
            <?php endif; ?>
            
            <div class="hph-hero__overlay hph-hero__overlay--<?php echo esc_attr($atts['background_overlay']); ?>"></div>
            
            <div class="hph-hero__container">
                <div class="hph-hero__content">
                    <?php if (!empty($atts['subtitle'])) : ?>
                        <div class="hph-hero__subtitle"><?php echo esc_html($atts['subtitle']); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($atts['title'])) : ?>
                        <h1 class="hph-hero__title"><?php echo esc_html($atts['title']); ?></h1>
                    <?php endif; ?>
                    
                    <?php if (!empty($content)) : ?>
                        <div class="hph-hero__description"><?php echo wp_kses_post($content); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($buttons_html)) : ?>
                        <div class="hph-hero__actions">
                            <?php echo $buttons_html; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Parse button string into HTML
     */
    private function parse_buttons($buttons_string) {
        if (empty($buttons_string)) {
            return '';
        }
        
        $buttons = array();
        $button_pairs = explode(',', $buttons_string);
        
        foreach ($button_pairs as $pair) {
            $parts = explode('|', trim($pair));
            if (count($parts) >= 2) {
                $text = trim($parts[0]);
                $url = trim($parts[1]);
                $style = isset($parts[2]) ? trim($parts[2]) : 'primary';
                
                $buttons[] = sprintf(
                    '<a href="%s" class="hph-btn hph-btn--%s">%s</a>',
                    esc_url($url),
                    esc_attr($style),
                    esc_html($text)
                );
            }
        }
        
        return implode(' ', $buttons);
    }
}
