<?php
/**
 * Shortcode Admin Interface
 * 
 * Provides admin interface for managing and inserting shortcodes
 */

class HPH_Shortcode_Admin {
    
    private $shortcode_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('media_buttons', array($this, 'add_shortcode_button'));
        add_action('wp_ajax_hph_get_shortcode_form', array($this, 'ajax_get_shortcode_form'));
        add_action('wp_ajax_hph_generate_shortcode', array($this, 'ajax_generate_shortcode'));
        
        $this->shortcode_manager = HPH_Shortcode_Manager::get_instance();
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'themes.php',
            'Shortcodes',
            'Shortcodes',
            'edit_theme_options',
            'hph-shortcodes',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook === 'appearance_page_hph-shortcodes' || $hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_script(
                'hph-shortcode-admin',
                get_template_directory_uri() . '/assets/src/js/admin/shortcode-admin.js',
                array('jquery', 'wp-util'),
                '1.0.0',
                true
            );
            
            wp_enqueue_style(
                'hph-shortcode-admin',
                get_template_directory_uri() . '/assets/src/css/admin/shortcode-admin.css',
                array(),
                '1.0.0'
            );
            
            wp_localize_script('hph-shortcode-admin', 'hphShortcodeAdmin', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('hph_shortcode_admin'),
                'shortcodes' => $this->get_shortcode_list()
            ));
        }
    }
    
    /**
     * Add shortcode button to editor
     */
    public function add_shortcode_button() {
        global $post;
        if (!$post || !in_array($post->post_type, array('post', 'page'))) {
            return;
        }
        
        echo '<button type="button" class="button hph-shortcode-button" data-editor="content">
                <span class="dashicons dashicons-layout"></span> HPH Shortcodes
              </button>';
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Happy Place Shortcodes</h1>
            
            <div class="hph-shortcode-admin">
                <div class="hph-shortcode-tabs">
                    <nav class="nav-tab-wrapper">
                        <a href="#generator" class="nav-tab nav-tab-active">Shortcode Generator</a>
                        <a href="#documentation" class="nav-tab">Documentation</a>
                        <a href="#examples" class="nav-tab">Examples</a>
                    </nav>
                </div>
                
                <div class="hph-shortcode-content">
                    <!-- Generator Tab -->
                    <div id="generator" class="hph-tab-content active">
                        <div class="hph-shortcode-generator">
                            <div class="hph-generator-sidebar">
                                <h3>Available Shortcodes</h3>
                                <div class="hph-shortcode-list">
                                    <?php $this->render_shortcode_list(); ?>
                                </div>
                            </div>
                            
                            <div class="hph-generator-main">
                                <div class="hph-generator-form">
                                    <h3>Configure Shortcode</h3>
                                    <div id="hph-shortcode-form-container">
                                        <p>Select a shortcode from the left to configure it.</p>
                                    </div>
                                </div>
                                
                                <div class="hph-generator-output">
                                    <h3>Generated Shortcode</h3>
                                    <textarea id="hph-generated-shortcode" readonly placeholder="Your shortcode will appear here..."></textarea>
                                    <div class="hph-generator-actions">
                                        <button type="button" class="button button-primary" id="hph-copy-shortcode">Copy Shortcode</button>
                                        <button type="button" class="button" id="hph-preview-shortcode">Preview</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div id="hph-shortcode-preview" class="hph-preview-area" style="display: none;">
                            <h3>Preview</h3>
                            <div class="hph-preview-content"></div>
                        </div>
                    </div>
                    
                    <!-- Documentation Tab -->
                    <div id="documentation" class="hph-tab-content">
                        <h2>Shortcode Documentation</h2>
                        <?php $this->render_documentation(); ?>
                    </div>
                    
                    <!-- Examples Tab -->
                    <div id="examples" class="hph-tab-content">
                        <h2>Example Shortcodes</h2>
                        <?php $this->render_examples(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render shortcode list
     */
    private function render_shortcode_list() {
        $shortcodes = $this->get_shortcode_list();
        
        foreach ($shortcodes as $tag => $shortcode) {
            echo '<div class="hph-shortcode-item" data-shortcode="' . esc_attr($tag) . '">';
            echo '<h4>' . esc_html($shortcode['name']) . '</h4>';
            echo '<p>' . esc_html($shortcode['description']) . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Get shortcode list
     */
    private function get_shortcode_list() {
        return array(
            'hph_features' => array(
                'name' => 'Features',
                'description' => 'Showcase features with icons and descriptions in a grid layout'
            ),
            'hph_testimonials' => array(
                'name' => 'Testimonials',
                'description' => 'Display customer testimonials in slider or grid format'
            ),
            'hph_pricing' => array(
                'name' => 'Pricing Table',
                'description' => 'Create pricing tables with multiple plans and features'
            ),
            'hph_hero' => array(
                'name' => 'Hero Section',
                'description' => 'Create hero sections with background images and call-to-action buttons'
            ),
            'hph_button' => array(
                'name' => 'Button',
                'description' => 'Customizable buttons with various styles and sizes'
            ),
            'hph_card' => array(
                'name' => 'Card',
                'description' => 'Content cards with images, titles, and descriptions'
            ),
            'hph_grid' => array(
                'name' => 'Grid',
                'description' => 'Responsive grid layouts for organizing content'
            ),
            'hph_cta' => array(
                'name' => 'Call to Action',
                'description' => 'Attention-grabbing call-to-action sections'
            ),
            'hph_spacer' => array(
                'name' => 'Spacer',
                'description' => 'Add custom spacing between content sections'
            )
        );
    }
    
    /**
     * Render documentation
     */
    private function render_documentation() {
        $shortcode_list = $this->get_shortcode_list();
        
        foreach ($shortcode_list as $tag => $info) {
            echo '<div class="hph-doc-section">';
            echo '<h3>[' . esc_html($tag) . ']</h3>';
            echo '<p>' . esc_html($info['description']) . '</p>';
            
            // Get defaults from instance if available
            $shortcodes = $this->shortcode_manager->get_registered_shortcodes();
            if (isset($shortcodes[$tag])) {
                $instance = $shortcodes[$tag];
                $reflection = new ReflectionClass($instance);
                $defaults_property = $reflection->getProperty('defaults');
                $defaults_property->setAccessible(true);
                $defaults = $defaults_property->getValue($instance);
                
                if (!empty($defaults)) {
                    echo '<h4>Attributes:</h4>';
                    echo '<ul>';
                    foreach ($defaults as $attr => $default_value) {
                        $label = ucwords(str_replace('_', ' ', $attr));
                        echo '<li><strong>' . esc_html($attr) . '</strong>: ' . esc_html($label) . ' (default: ' . esc_html($default_value) . ')</li>';
                    }
                    echo '</ul>';
                }
            }
            
            echo '</div>';
        }
    }
    
    /**
     * Render examples
     */
    private function render_examples() {
        $examples = array(
            'Hero Section' => '[hph_hero title="Welcome to Our Website" subtitle="We create amazing experiences" background_image="hero-bg.jpg" button_text="Get Started" button_url="#contact"]',
            'Feature Grid' => '[hph_features title="Why Choose Us" columns="3" items="fas fa-rocket:Fast:Lightning fast performance|fas fa-shield-alt:Secure:Bank-level security|fas fa-heart:Reliable:99.9% uptime guarantee"]',
            'Testimonials Slider' => '[hph_testimonials layout="slider" items="Amazing service and support!:John Doe:CEO:Company Inc:avatar1.jpg:5|Great experience working with them:Jane Smith:Manager:Business LLC:avatar2.jpg:5"]',
            'Pricing Table' => '[hph_pricing title="Choose Your Plan" plans="Basic|9|Perfect for starters|Get Started|#|_blank||Free support||Feature 1,Feature 2,Feature 3||Premium|29|Best for growing businesses|Start Free Trial|#|_blank|Most Popular|Priority support||All Basic features,Advanced analytics,Custom integrations"]',
            'Call to Action' => '[hph_cta title="Ready to Get Started?" subtitle="Join thousands of satisfied customers" button_text="Sign Up Now" button_url="#signup" background="primary"]'
        );
        
        foreach ($examples as $name => $shortcode) {
            echo '<div class="hph-example-section">';
            echo '<h3>' . esc_html($name) . '</h3>';
            echo '<div class="hph-example-code">';
            echo '<textarea readonly>' . esc_textarea($shortcode) . '</textarea>';
            echo '<button type="button" class="button hph-copy-example">Copy</button>';
            echo '</div>';
            echo '</div>';
        }
    }
    
    /**
     * AJAX: Get shortcode form
     */
    public function ajax_get_shortcode_form() {
        // Add debug logging
        error_log('HPH Shortcode Admin: AJAX form request received');
        
        if (!check_ajax_referer('hph_shortcode_admin', 'nonce', false)) {
            error_log('HPH Shortcode Admin: Nonce verification failed');
            wp_send_json_error('Invalid nonce');
        }
        
        $shortcode_tag = sanitize_text_field($_POST['shortcode']);
        error_log('HPH Shortcode Admin: Requesting form for ' . $shortcode_tag);
        
        $shortcodes = $this->shortcode_manager->get_registered_shortcodes();
        
        if (!isset($shortcodes[$shortcode_tag])) {
            error_log('HPH Shortcode Admin: Shortcode not found: ' . $shortcode_tag);
            wp_send_json_error('Invalid shortcode');
        }
        
        $instance = $shortcodes[$shortcode_tag];
        error_log('HPH Shortcode Admin: Instance found: ' . get_class($instance));
        
        try {
            // Get defaults from the instance (they're set in the init() method)
            $reflection = new ReflectionClass($instance);
            $defaults_property = $reflection->getProperty('defaults');
            $defaults_property->setAccessible(true);
            $defaults = $defaults_property->getValue($instance);
            
            if (!empty($defaults)) {
                error_log('HPH Shortcode Admin: Found ' . count($defaults) . ' defaults');
                
                ob_start();
                $this->render_shortcode_form($shortcode_tag, $defaults, array());
                $form_html = ob_get_clean();
                
                error_log('HPH Shortcode Admin: Form generated successfully');
                wp_send_json_success(array('form' => $form_html));
            }
            
            error_log('HPH Shortcode Admin: No defaults found');
            wp_send_json_error('Shortcode form not available');
            
        } catch (Exception $e) {
            error_log('HPH Shortcode Admin: Exception: ' . $e->getMessage());
            wp_send_json_error('Error generating form: ' . $e->getMessage());
        }
    }
    
    /**
     * Render shortcode form
     */
    private function render_shortcode_form($tag, $defaults, $help) {
        echo '<form class="hph-shortcode-form" data-shortcode="' . esc_attr($tag) . '">';
        
        foreach ($defaults as $attr => $default_value) {
            $label = ucwords(str_replace('_', ' ', $attr));
            $description = isset($help['attributes'][$attr]) ? $help['attributes'][$attr] : '';
            
            echo '<div class="hph-form-field">';
            echo '<label for="' . esc_attr($attr) . '">' . esc_html($label) . '</label>';
            
            if ($attr === 'content' || stripos($attr, 'description') !== false || stripos($attr, 'items') !== false) {
                echo '<textarea name="' . esc_attr($attr) . '" id="' . esc_attr($attr) . '" rows="4">' . esc_textarea($default_value) . '</textarea>';
            } elseif (stripos($attr, 'url') !== false) {
                echo '<input type="url" name="' . esc_attr($attr) . '" id="' . esc_attr($attr) . '" value="' . esc_attr($default_value) . '">';
            } elseif (stripos($attr, 'color') !== false) {
                echo '<input type="color" name="' . esc_attr($attr) . '" id="' . esc_attr($attr) . '" value="' . esc_attr($default_value) . '">';
            } else {
                echo '<input type="text" name="' . esc_attr($attr) . '" id="' . esc_attr($attr) . '" value="' . esc_attr($default_value) . '">';
            }
            
            if ($description) {
                echo '<small>' . esc_html($description) . '</small>';
            }
            echo '</div>';
        }
        
        echo '</form>';
    }
    
    /**
     * AJAX: Generate shortcode
     */
    public function ajax_generate_shortcode() {
        check_ajax_referer('hph_shortcode_admin', 'nonce');
        
        $shortcode_tag = sanitize_text_field($_POST['shortcode']);
        $attributes = $_POST['attributes'];
        
        $shortcode = '[' . $shortcode_tag;
        
        foreach ($attributes as $attr => $value) {
            if (!empty($value)) {
                $shortcode .= ' ' . sanitize_key($attr) . '="' . esc_attr($value) . '"';
            }
        }
        
        $shortcode .= ']';
        
        wp_send_json_success(array('shortcode' => $shortcode));
    }
}

// Initialize admin interface
if (is_admin()) {
    new HPH_Shortcode_Admin();
}
