<?php
/**
 * Features Shortcode Component
 * 
 * Renders a features section with multiple feature items in a grid layout
 * Uses existing .features-section and .feature-item classes
 */

class HPH_Shortcode_Features extends HPH_Shortcode_Base {
    
    /**
     * Initialize the shortcode
     */
    protected function init() {
        $this->tag = 'hph_features';
        $this->defaults = [
            'title' => '',
            'subtitle' => '',
            'columns' => '3',
            'items' => '',
            'style' => 'default',
            'background' => 'light',
            'spacing' => 'default',
            'animation' => 'fade',
            'css_class' => ''
        ];
    }
    
    /**
     * Get shortcode tag
     */
    public function get_tag() {
        return 'hph_features';
    }
    
    /**
     * Get required assets
     */
    public function get_assets() {
        return [
            'css' => ['components'],
            'js' => []
        ];
    }
    
    /**
     * Generate output
     */
    protected function generate_output($atts, $content = null) {
        $features = $this->parse_features($atts['items']);
        
        if (empty($features)) {
            return '';
        }
        
        $css_classes = [
            'features-section',
            'features-' . $atts['style'],
            'bg-' . $atts['background'],
            'spacing-' . $atts['spacing'],
            'animation-' . $atts['animation'],
            'columns-' . $atts['columns']
        ];
        
        if (!empty($atts['css_class'])) {
            $css_classes[] = $atts['css_class'];
        }
        
        ob_start();
        ?>
        <section class="<?php echo esc_attr(implode(' ', $css_classes)); ?>" data-component="features">
            <div class="container">
                <?php if (!empty($atts['title']) || !empty($atts['subtitle'])): ?>
                <div class="features-header text-center">
                    <?php if (!empty($atts['title'])): ?>
                    <h2 class="features-title"><?php echo wp_kses_post($atts['title']); ?></h2>
                    <?php endif; ?>
                    
                    <?php if (!empty($atts['subtitle'])): ?>
                    <p class="features-subtitle"><?php echo wp_kses_post($atts['subtitle']); ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="features-grid">
                    <?php foreach ($features as $index => $feature): ?>
                    <div class="feature-item" data-aos="fade-up" data-aos-delay="<?php echo ($index * 100); ?>">
                        <?php if (!empty($feature['icon'])): ?>
                        <div class="feature-icon">
                            <?php if (strpos($feature['icon'], '<') === 0): ?>
                                <?php echo wp_kses_post($feature['icon']); ?>
                            <?php else: ?>
                                <i class="<?php echo esc_attr($feature['icon']); ?>"></i>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($feature['title'])): ?>
                        <h3 class="feature-title"><?php echo wp_kses_post($feature['title']); ?></h3>
                        <?php endif; ?>
                        
                        <?php if (!empty($feature['description'])): ?>
                        <p class="feature-description"><?php echo wp_kses_post($feature['description']); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($feature['link_url'])): ?>
                        <a href="<?php echo esc_url($feature['link_url']); ?>" 
                           class="feature-link" 
                           <?php if (!empty($feature['link_target'])): ?>target="<?php echo esc_attr($feature['link_target']); ?>"<?php endif; ?>>
                            <?php echo wp_kses_post($feature['link_text'] ?: 'Learn More'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Parse features from items attribute
     */
    private function parse_features($items_string) {
        if (empty($items_string)) {
            return [];
        }
        
        $features = [];
        $items = explode('|', $items_string);
        
        foreach ($items as $item) {
            $parts = explode(':', $item);
            if (count($parts) >= 2) {
                $feature = [
                    'icon' => trim($parts[0]) ?: '',
                    'title' => trim($parts[1]) ?: '',
                    'description' => isset($parts[2]) ? trim($parts[2]) : '',
                    'link_url' => isset($parts[3]) ? trim($parts[3]) : '',
                    'link_text' => isset($parts[4]) ? trim($parts[4]) : '',
                    'link_target' => isset($parts[5]) ? trim($parts[5]) : ''
                ];
                $features[] = $feature;
            }
        }
        
        return $features;
    }
}
