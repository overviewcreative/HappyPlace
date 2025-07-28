<?php
/**
 * Testimonials Shortcode Component
 * 
 * Renders testimonials in various layouts (slider, grid, single)
 * Uses existing .testimonials-section and .testimonial-item classes
 */

class HPH_Shortcode_Testimonials extends HPH_Shortcode_Base {
    
    /**
     * Initialize the shortcode
     */
    protected function init() {
        $this->tag = 'hph_testimonials';
        $this->defaults = [
            'layout' => 'slider',
            'items' => '',
            'columns' => '3',
            'autoplay' => 'true',
            'autoplay_speed' => '5000',
            'show_avatars' => 'true',
            'show_ratings' => 'true',
            'background' => 'light',
            'style' => 'default',
            'css_class' => ''
        ];
    }
    
    /**
     * Get shortcode tag
     */
    public function get_tag() {
        return 'hph_testimonials';
    }
    
    /**
     * Get required assets
     */
    public function get_assets() {
        return [
            'css' => ['components'],
            'js' => ['testimonials']
        ];
    }
    
    /**
     * Generate output
     */
    protected function generate_output($atts, $content = null) {
        $testimonials = $this->parse_testimonials($atts['items']);
        
        if (empty($testimonials)) {
            return '';
        }
        
        $css_classes = [
            'testimonials-section',
            'testimonials-' . $atts['layout'],
            'testimonials-' . $atts['style'],
            'bg-' . $atts['background']
        ];
        
        if (!empty($atts['css_class'])) {
            $css_classes[] = $atts['css_class'];
        }
        
        if ($atts['layout'] === 'grid') {
            $css_classes[] = 'columns-' . $atts['columns'];
        }
        
        ob_start();
        ?>
        <section class="<?php echo esc_attr(implode(' ', $css_classes)); ?>" 
                 data-component="testimonials"
                 <?php if ($atts['layout'] === 'slider'): ?>
                 data-autoplay="<?php echo esc_attr($atts['autoplay']); ?>"
                 data-autoplay-speed="<?php echo esc_attr($atts['autoplay_speed']); ?>"
                 <?php endif; ?>>
            <div class="container">
                <?php if ($atts['layout'] === 'slider'): ?>
                <div class="testimonials-slider">
                    <?php foreach ($testimonials as $testimonial): ?>
                    <div class="testimonial-slide">
                        <?php echo $this->render_testimonial($testimonial, $atts); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($testimonials) > 1): ?>
                <div class="testimonials-navigation">
                    <button class="testimonials-prev" aria-label="Previous testimonial">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <div class="testimonials-dots"></div>
                    <button class="testimonials-next" aria-label="Next testimonial">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="testimonials-grid">
                    <?php foreach ($testimonials as $testimonial): ?>
                    <div class="testimonial-item">
                        <?php echo $this->render_testimonial($testimonial, $atts); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render individual testimonial
     */
    private function render_testimonial($testimonial, $atts) {
        ob_start();
        ?>
        <div class="testimonial-content">
            <?php if (!empty($testimonial['quote'])): ?>
            <blockquote class="testimonial-quote">
                "<?php echo wp_kses_post($testimonial['quote']); ?>"
            </blockquote>
            <?php endif; ?>
            
            <?php if ($atts['show_ratings'] === 'true' && !empty($testimonial['rating'])): ?>
            <div class="testimonial-rating">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="<?php echo ($i <= intval($testimonial['rating'])) ? 'fas' : 'far'; ?> fa-star"></i>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
            
            <div class="testimonial-author">
                <?php if ($atts['show_avatars'] === 'true' && !empty($testimonial['avatar'])): ?>
                <div class="testimonial-avatar">
                    <img src="<?php echo esc_url($testimonial['avatar']); ?>" 
                         alt="<?php echo esc_attr($testimonial['name']); ?>"
                         loading="lazy">
                </div>
                <?php endif; ?>
                
                <div class="testimonial-meta">
                    <?php if (!empty($testimonial['name'])): ?>
                    <div class="testimonial-name"><?php echo esc_html($testimonial['name']); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($testimonial['title'])): ?>
                    <div class="testimonial-title"><?php echo esc_html($testimonial['title']); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($testimonial['company'])): ?>
                    <div class="testimonial-company"><?php echo esc_html($testimonial['company']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Parse testimonials from items attribute
     */
    private function parse_testimonials($items_string) {
        if (empty($items_string)) {
            return [];
        }
        
        $testimonials = [];
        $items = explode('|', $items_string);
        
        foreach ($items as $item) {
            $parts = explode(':', $item);
            if (count($parts) >= 2) {
                $testimonial = [
                    'quote' => trim($parts[0]) ?: '',
                    'name' => trim($parts[1]) ?: '',
                    'title' => isset($parts[2]) ? trim($parts[2]) : '',
                    'company' => isset($parts[3]) ? trim($parts[3]) : '',
                    'avatar' => isset($parts[4]) ? trim($parts[4]) : '',
                    'rating' => isset($parts[5]) ? intval(trim($parts[5])) : 5
                ];
                $testimonials[] = $testimonial;
            }
        }
        
        return $testimonials;
    }
}
