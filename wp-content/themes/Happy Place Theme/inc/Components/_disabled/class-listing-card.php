<?php
/**
 * Listing Card Component
 *
 * Standard listing card component following Phase 4 consolidation patterns
 *
 * @package HappyPlace\Components\Listing
 * @since 2.0.0
 */

namespace HappyPlace\Components\Listing;

use HappyPlace\Components\Base_Component;

if (!defined('ABSPATH')) {
    exit;
}

class Listing_Card extends Base_Component {
    
    /**
     * Listing data cache
     * @var array
     */
    protected $listing_data = [];
    
    /**
     * Get component name
     *
     * @return string
     */
    protected function get_component_name() {
        return 'listing_card';
    }
    
    /**
     * Get default properties
     *
     * @return array
     */
    protected function get_defaults() {
        return [
            'listing_id' => 0,
            'variant' => 'default',
            'context' => 'grid',
            'show_features' => true,
            'show_status' => true,
            'show_agent' => false,
            'link_title' => true,
            'image_size' => 'medium_large',
            'css_classes' => ''
        ];
    }
    
    /**
     * Validate component properties
     */
    protected function validate_props() {
        if (empty($this->get_prop('listing_id'))) {
            $this->add_validation_error('Listing ID is required');
        }
        
        $valid_variants = ['default', 'featured', 'compact', 'minimal'];
        if (!in_array($this->get_prop('variant'), $valid_variants)) {
            $this->add_validation_error('Invalid variant specified');
        }
    }
    
    /**
     * Initialize component
     */
    protected function init() {
        // Load listing data
        $this->listing_data = hph_get_template_listing_data($this->get_prop('listing_id'));
    }
    
    /**
     * Render the component
     *
     * @return string
     */
    protected function render() {
        if (empty($this->listing_data)) {
            return '';
        }
        
        $variant = $this->get_prop('variant');
        $css_classes = $this->get_css_classes() . ' hph-variant-' . $variant;
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($css_classes); ?>" 
             data-listing-id="<?php echo esc_attr($this->get_prop('listing_id')); ?>">
            
            <?php $this->render_image(); ?>
            
            <div class="hph-listing-content">
                <?php $this->render_status(); ?>
                <?php $this->render_title(); ?>
                <?php $this->render_price(); ?>
                <?php $this->render_address(); ?>
                <?php $this->render_features(); ?>
                <?php $this->render_agent(); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render listing image
     */
    protected function render_image() {
        $listing_id = $this->get_prop('listing_id');
        $image_size = $this->get_prop('image_size');
        
        if (has_post_thumbnail($listing_id)) {
            ?>
            <div class="hph-listing-image">
                <a href="<?php echo esc_url($this->listing_data['url']); ?>">
                    <?php echo get_the_post_thumbnail($listing_id, $image_size); ?>
                </a>
            </div>
            <?php
        }
    }
    
    /**
     * Render status badge
     */
    protected function render_status() {
        if (!$this->get_prop('show_status') || empty($this->listing_data['status'])) {
            return;
        }
        
        $status = $this->listing_data['status'];
        if ($status !== 'active') {
            ?>
            <div class="hph-listing-status hph-status-<?php echo esc_attr($status); ?>">
                <?php echo esc_html(ucfirst($status)); ?>
            </div>
            <?php
        }
    }
    
    /**
     * Render listing title
     */
    protected function render_title() {
        if (empty($this->listing_data['title'])) {
            return;
        }
        
        ?>
        <h3 class="hph-listing-title">
            <?php if ($this->get_prop('link_title')): ?>
                <a href="<?php echo esc_url($this->listing_data['url']); ?>">
                    <?php echo esc_html($this->listing_data['title']); ?>
                </a>
            <?php else: ?>
                <?php echo esc_html($this->listing_data['title']); ?>
            <?php endif; ?>
        </h3>
        <?php
    }
    
    /**
     * Render listing price
     */
    protected function render_price() {
        if (empty($this->listing_data['price'])) {
            return;
        }
        
        ?>
        <div class="hph-listing-price">
            <?php echo esc_html($this->listing_data['price']); ?>
        </div>
        <?php
    }
    
    /**
     * Render listing address
     */
    protected function render_address() {
        if (empty($this->listing_data['address'])) {
            return;
        }
        
        ?>
        <div class="hph-listing-address">
            <?php echo esc_html($this->listing_data['address']); ?>
        </div>
        <?php
    }
    
    /**
     * Render listing features
     */
    protected function render_features() {
        if (!$this->get_prop('show_features') || empty($this->listing_data['features'])) {
            return;
        }
        
        ?>
        <div class="hph-listing-features">
            <?php foreach ($this->listing_data['features'] as $feature => $value): ?>
                <?php if ($value): ?>
                    <span class="hph-feature hph-feature-<?php echo esc_attr($feature); ?>">
                        <?php echo esc_html($value); ?>
                    </span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render agent info
     */
    protected function render_agent() {
        if (!$this->get_prop('show_agent')) {
            return;
        }
        
        // Agent rendering would go here
        // This would integrate with hph_get_agent_data() function
    }
}
