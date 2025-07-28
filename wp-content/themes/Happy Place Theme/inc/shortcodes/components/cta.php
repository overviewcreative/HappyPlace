<?php
/**
 * CTA (Call to Action) Shortcode Component
 * 
 * @package HappyPlace
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HPH_CTA_Shortcode extends HPH_Shortcode_Base {
    
    protected $tag = 'hph_cta';
    
    protected $supports_content = true;
    
    protected $defaults = array(
        'title' => '',
        'description' => '',
        'layout' => 'centered',
        'theme' => 'primary',
        'size' => 'lg',
        'text_align' => 'center',
        'background' => '',
        'buttons' => '',
        'animation' => 'fade-in-up',
        'class' => ''
    );
    
    protected function generate_output($atts, $content = null) {
        $id = $this->generate_id('cta');
        $classes = $this->get_css_classes('hph-cta', $atts);
        
        // Add additional modifier classes
        if (!empty($atts['text_align'])) {
            $classes .= ' hph-cta--text-' . $atts['text_align'];
        }
        
        if (!empty($atts['class'])) {
            $classes .= ' ' . $atts['class'];
        }
        
        if (!empty($atts['animation'])) {
            $classes .= ' animate-' . str_replace('_', '-', $atts['animation']);
        }
        
        // Build inline styles
        $styles = array();
        
        if (!empty($atts['background'])) {
            $background_url = is_numeric($atts['background']) ? wp_get_attachment_url($atts['background']) : $atts['background'];
            $styles[] = "background-image: url('" . esc_url($background_url) . "')";
        }
        
        $style_attr = !empty($styles) ? ' style="' . implode('; ', $styles) . '"' : '';
        
        // Parse buttons
        $buttons_html = $this->parse_buttons($atts['buttons']);
        
        // Use content or description
        $description = !empty($content) ? $content : $atts['description'];
        
        ob_start();
        ?>
        <section id="<?php echo esc_attr($id); ?>" class="<?php echo esc_attr($classes); ?>"<?php echo $style_attr; ?>>
            <?php if (!empty($atts['background'])) : ?>
                <div class="hph-cta__background"></div>
                <div class="hph-cta__overlay"></div>
            <?php endif; ?>
            
            <div class="hph-cta__container">
                <?php if ($atts['layout'] === 'split') : ?>
                    <div class="hph-cta__content">
                        <?php if (!empty($atts['title'])) : ?>
                            <h2 class="hph-cta__title"><?php echo esc_html($atts['title']); ?></h2>
                        <?php endif; ?>
                        
                        <?php if (!empty($description)) : ?>
                            <div class="hph-cta__description">
                                <?php echo wp_kses_post($description); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($buttons_html)) : ?>
                        <div class="hph-cta__actions">
                            <?php echo $buttons_html; ?>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <div class="hph-cta__content">
                        <?php if (!empty($atts['title'])) : ?>
                            <h2 class="hph-cta__title"><?php echo esc_html($atts['title']); ?></h2>
                        <?php endif; ?>
                        
                        <?php if (!empty($description)) : ?>
                            <div class="hph-cta__description">
                                <?php echo wp_kses_post($description); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($buttons_html)) : ?>
                            <div class="hph-cta__actions">
                                <?php echo $buttons_html; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
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
                    '<a href="%s" class="hph-btn hph-btn--%s hph-btn--lg">%s</a>',
                    esc_url($url),
                    esc_attr($style),
                    esc_html($text)
                );
            }
        }
        
        return implode(' ', $buttons);
    }
}
