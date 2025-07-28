<?php
/**
 * Pricing Table Shortcode Component
 * 
 * Renders pricing tables with multiple plans
 * Uses existing .pricing-section and .pricing-plan classes
 */

class HPH_Shortcode_Pricing extends HPH_Shortcode_Base {
    
    /**
     * Initialize the shortcode
     */
    protected function init() {
        $this->tag = 'hph_pricing';
        $this->defaults = [
            'title' => '',
            'subtitle' => '',
            'plans' => '',
            'columns' => '3',
            'style' => 'default',
            'currency' => '$',
            'period' => 'month',
            'highlight_plan' => '',
            'background' => 'light',
            'css_class' => ''
        ];
    }
    
    /**
     * Get shortcode tag
     */
    public function get_tag() {
        return 'hph_pricing';
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
        $pricing_plans = $this->parse_pricing_plans($atts['plans']);
        
        if (empty($pricing_plans)) {
            return '';
        }
        
        $css_classes = [
            'pricing-section',
            'pricing-' . $atts['style'],
            'bg-' . $atts['background'],
            'columns-' . $atts['columns']
        ];
        
        if (!empty($atts['css_class'])) {
            $css_classes[] = $atts['css_class'];
        }
        
        ob_start();
        ?>
        <section class="<?php echo esc_attr(implode(' ', $css_classes)); ?>" data-component="pricing">
            <div class="container">
                <?php if (!empty($atts['title']) || !empty($atts['subtitle'])): ?>
                <div class="pricing-header text-center">
                    <?php if (!empty($atts['title'])): ?>
                    <h2 class="pricing-title"><?php echo wp_kses_post($atts['title']); ?></h2>
                    <?php endif; ?>
                    
                    <?php if (!empty($atts['subtitle'])): ?>
                    <p class="pricing-subtitle"><?php echo wp_kses_post($atts['subtitle']); ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="pricing-grid">
                    <?php foreach ($pricing_plans as $plan): ?>
                    <?php
                    $plan_classes = ['pricing-plan'];
                    if (!empty($atts['highlight_plan']) && $plan['name'] === $atts['highlight_plan']) {
                        $plan_classes[] = 'pricing-plan-featured';
                    }
                    ?>
                    <div class="<?php echo esc_attr(implode(' ', $plan_classes)); ?>">
                        <div class="pricing-plan-header">
                            <?php if (!empty($plan['name'])): ?>
                            <h3 class="pricing-plan-name"><?php echo esc_html($plan['name']); ?></h3>
                            <?php endif; ?>
                            
                            <?php if (!empty($plan['description'])): ?>
                            <p class="pricing-plan-description"><?php echo wp_kses_post($plan['description']); ?></p>
                            <?php endif; ?>
                            
                            <div class="pricing-plan-price">
                                <span class="pricing-currency"><?php echo esc_html($atts['currency']); ?></span>
                                <span class="pricing-amount"><?php echo esc_html($plan['price']); ?></span>
                                <span class="pricing-period">/ <?php echo esc_html($atts['period']); ?></span>
                            </div>
                            
                            <?php if (!empty($plan['badge'])): ?>
                            <div class="pricing-plan-badge"><?php echo esc_html($plan['badge']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($plan['features'])): ?>
                        <div class="pricing-plan-features">
                            <ul class="pricing-features-list">
                                <?php foreach ($plan['features'] as $feature): ?>
                                <li class="pricing-feature">
                                    <i class="fas fa-check pricing-feature-icon"></i>
                                    <span class="pricing-feature-text"><?php echo wp_kses_post($feature); ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                        <div class="pricing-plan-footer">
                            <?php if (!empty($plan['button_text']) && !empty($plan['button_url'])): ?>
                            <a href="<?php echo esc_url($plan['button_url']); ?>" 
                               class="btn btn-primary pricing-plan-button"
                               <?php if (!empty($plan['button_target'])): ?>target="<?php echo esc_attr($plan['button_target']); ?>"<?php endif; ?>>
                                <?php echo esc_html($plan['button_text']); ?>
                            </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($plan['note'])): ?>
                            <p class="pricing-plan-note"><?php echo wp_kses_post($plan['note']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Parse pricing plans from plans attribute
     */
    private function parse_pricing_plans($plans_string) {
        if (empty($plans_string)) {
            return [];
        }
        
        $plans = [];
        $plan_items = explode('||', $plans_string);
        
        foreach ($plan_items as $plan_item) {
            $parts = explode('|', $plan_item);
            if (count($parts) >= 3) {
                $plan = [
                    'name' => trim($parts[0]) ?: '',
                    'price' => trim($parts[1]) ?: '',
                    'features' => [],
                    'description' => isset($parts[2]) ? trim($parts[2]) : '',
                    'button_text' => isset($parts[3]) ? trim($parts[3]) : 'Get Started',
                    'button_url' => isset($parts[4]) ? trim($parts[4]) : '#',
                    'button_target' => isset($parts[5]) ? trim($parts[5]) : '',
                    'badge' => isset($parts[6]) ? trim($parts[6]) : '',
                    'note' => isset($parts[7]) ? trim($parts[7]) : ''
                ];
                
                // Parse features (from parts[8] onwards)
                if (isset($parts[8])) {
                    $features_string = implode('|', array_slice($parts, 8));
                    $plan['features'] = array_filter(array_map('trim', explode(',', $features_string)));
                }
                
                $plans[] = $plan;
            }
        }
        
        return $plans;
    }
}
