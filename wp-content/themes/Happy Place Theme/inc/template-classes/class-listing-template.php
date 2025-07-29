<?php
/**
 * Listing Template Class
 *
 * Handles display logic for listing templates
 *
 * @package HappyPlace\TemplateClasses
 * @since 2.0.0
 */

namespace HappyPlace\TemplateClasses;

if (!defined('ABSPATH')) {
    exit;
}

class Listing_Template {
    
    /**
     * Listing ID
     * @var int
     */
    protected $listing_id;
    
    /**
     * Template data
     * @var array
     */
    protected $data = [];
    
    /**
     * Constructor
     *
     * @param int $listing_id Listing post ID
     */
    public function __construct($listing_id = null) {
        $this->listing_id = $listing_id ?: get_the_ID();
        $this->load_data();
    }
    
    /**
     * Load template data
     */
    protected function load_data() {
        $this->data = hph_get_template_listing_data($this->listing_id);
    }
    
    /**
     * Get template data
     *
     * @return array
     */
    public function get_data() {
        return $this->data;
    }
    
    /**
     * Get listing title
     *
     * @return string
     */
    public function get_title() {
        return $this->data['title'] ?? '';
    }
    
    /**
     * Get formatted price
     *
     * @return string
     */
    public function get_price() {
        return $this->data['price'] ?? '';
    }
    
    /**
     * Get listing status
     *
     * @return string
     */
    public function get_status() {
        return $this->data['status'] ?? 'active';
    }
    
    /**
     * Get formatted address
     *
     * @return string
     */
    public function get_address() {
        return $this->data['address'] ?? '';
    }
    
    /**
     * Get listing URL
     *
     * @return string
     */
    public function get_url() {
        return $this->data['url'] ?? '';
    }
    
    /**
     * Get listing features
     *
     * @return array
     */
    public function get_features() {
        return $this->data['features'] ?? [];
    }
    
    /**
     * Display listing card
     *
     * @param array $args Display arguments
     * @return string
     */
    public function render_card($args = []) {
        $defaults = [
            'show_status' => true,
            'show_features' => true,
            'link_title' => true,
            'css_classes' => ''
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        ob_start();
        ?>
        <div class="hph-listing-card <?php echo esc_attr($args['css_classes']); ?>">
            <?php if ($args['show_status'] && $this->get_status() !== 'active'): ?>
                <div class="hph-listing-status hph-status-<?php echo esc_attr($this->get_status()); ?>">
                    <?php echo esc_html(ucfirst($this->get_status())); ?>
                </div>
            <?php endif; ?>
            
            <div class="hph-listing-content">
                <h3 class="hph-listing-title">
                    <?php if ($args['link_title']): ?>
                        <a href="<?php echo esc_url($this->get_url()); ?>">
                            <?php echo esc_html($this->get_title()); ?>
                        </a>
                    <?php else: ?>
                        <?php echo esc_html($this->get_title()); ?>
                    <?php endif; ?>
                </h3>
                
                <?php if ($this->get_price()): ?>
                    <div class="hph-listing-price">
                        <?php echo esc_html($this->get_price()); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($this->get_address()): ?>
                    <div class="hph-listing-address">
                        <?php echo esc_html($this->get_address()); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($args['show_features'] && !empty($this->get_features())): ?>
                    <div class="hph-listing-features">
                        <?php foreach ($this->get_features() as $feature => $value): ?>
                            <?php if ($value): ?>
                                <span class="hph-feature hph-feature-<?php echo esc_attr($feature); ?>">
                                    <?php echo esc_html($value); ?>
                                </span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
