<?php
/**
 * Agent Template Class
 *
 * Handles display logic for agent templates
 *
 * @package HappyPlace\TemplateClasses
 * @since 2.0.0
 */

namespace HappyPlace\TemplateClasses;

if (!defined('ABSPATH')) {
    exit;
}

class Agent_Template {
    
    /**
     * Agent ID
     * @var int
     */
    protected $agent_id;
    
    /**
     * Template data
     * @var array
     */
    protected $data = [];
    
    /**
     * Constructor
     *
     * @param int $agent_id Agent post ID
     */
    public function __construct($agent_id = null) {
        $this->agent_id = $agent_id ?: get_the_ID();
        $this->load_data();
    }
    
    /**
     * Load template data
     */
    protected function load_data() {
        $this->data = hph_get_agent_data($this->agent_id);
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
     * Get agent name
     *
     * @return string
     */
    public function get_name() {
        return $this->data['name'] ?? get_the_title($this->agent_id);
    }
    
    /**
     * Get agent email
     *
     * @return string
     */
    public function get_email() {
        return $this->data['email'] ?? '';
    }
    
    /**
     * Get agent phone
     *
     * @return string
     */
    public function get_phone() {
        return $this->data['phone'] ?? '';
    }
    
    /**
     * Get agent photo URL
     *
     * @return string
     */
    public function get_photo() {
        return $this->data['photo'] ?? '';
    }
    
    /**
     * Get agent bio
     *
     * @return string
     */
    public function get_bio() {
        return $this->data['bio'] ?? get_the_content(null, false, $this->agent_id);
    }
    
    /**
     * Display agent card
     *
     * @param array $args Display arguments
     * @return string
     */
    public function render_card($args = []) {
        $defaults = [
            'show_contact' => true,
            'show_bio' => false,
            'link_name' => true,
            'css_classes' => ''
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        ob_start();
        ?>
        <div class="hph-agent-card <?php echo esc_attr($args['css_classes']); ?>">
            <?php if ($this->get_photo()): ?>
                <div class="hph-agent-photo">
                    <img src="<?php echo esc_url($this->get_photo()); ?>" 
                         alt="<?php echo esc_attr($this->get_name()); ?>" />
                </div>
            <?php endif; ?>
            
            <div class="hph-agent-content">
                <h3 class="hph-agent-name">
                    <?php if ($args['link_name']): ?>
                        <a href="<?php echo esc_url(get_permalink($this->agent_id)); ?>">
                            <?php echo esc_html($this->get_name()); ?>
                        </a>
                    <?php else: ?>
                        <?php echo esc_html($this->get_name()); ?>
                    <?php endif; ?>
                </h3>
                
                <?php if ($args['show_bio'] && $this->get_bio()): ?>
                    <div class="hph-agent-bio">
                        <?php echo wp_kses_post(wp_trim_words($this->get_bio(), 20)); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($args['show_contact']): ?>
                    <div class="hph-agent-contact">
                        <?php if ($this->get_email()): ?>
                            <a href="mailto:<?php echo esc_attr($this->get_email()); ?>" 
                               class="hph-agent-email">
                                <?php echo esc_html($this->get_email()); ?>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($this->get_phone()): ?>
                            <a href="tel:<?php echo esc_attr($this->get_phone()); ?>" 
                               class="hph-agent-phone">
                                <?php echo esc_html($this->get_phone()); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
