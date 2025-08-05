<?php
/**
 * Marketing Suite Integration for Dashboard
 * 
 * Handles integration with marketing tools like flyer generation,
 * social media post creation, and email campaigns.
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

namespace HappyPlace\Dashboard;

if (!defined('ABSPATH')) {
    exit;
}

class Marketing_Suite_Integration {
    
    /**
     * Marketing tool handlers
     *
     * @var array
     */
    private $tool_handlers = [];
    
    /**
     * Data provider instance
     *
     * @var Dashboard_Data_Provider
     */
    private $data_provider;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Data provider will be set by the dashboard manager
        $this->data_provider = null;
    }
    
    /**
     * Set data provider instance
     *
     * @param Dashboard_Data_Provider $data_provider Data provider instance
     */
    public function set_data_provider($data_provider): void {
        $this->data_provider = $data_provider;
    }
    
    /**
     * Initialize marketing suite integration
     */
    public function init(): void {
        // Register AJAX handlers
        $this->register_ajax_handlers();
        
        // Register tool handlers
        $this->register_tool_handlers();
        
        // Add hooks
        add_filter('hph_dashboard_form_definitions', [$this, 'add_marketing_forms']);
        add_action('hph_dashboard_enqueue_scripts', [$this, 'enqueue_marketing_scripts']);
    }
    
    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers(): void {
        // Flyer generator
        add_action('wp_ajax_hph_marketing_flyer', [$this, 'ajax_flyer_generator']);
        
        // Social media tool
        add_action('wp_ajax_hph_marketing_social', [$this, 'ajax_social_media_tool']);
        
        // Email campaign
        add_action('wp_ajax_hph_marketing_email', [$this, 'ajax_email_campaign']);
        
        // Virtual tour
        add_action('wp_ajax_hph_marketing_virtual_tour', [$this, 'ajax_virtual_tour']);
        
        // Generate marketing material
        add_action('wp_ajax_hph_generate_marketing_material', [$this, 'ajax_generate_material']);
    }
    
    /**
     * Register tool handlers
     */
    private function register_tool_handlers(): void {
        // Include handler files
        $handler_files = [
            'flyer' => plugin_dir_path(__FILE__) . 'handlers/class-flyer-generator-handler.php',
            'social' => plugin_dir_path(__FILE__) . 'handlers/class-social-media-handler.php', 
            'email' => plugin_dir_path(__FILE__) . 'handlers/class-email-campaign-handler.php',
            'virtual_tour' => plugin_dir_path(__FILE__) . 'handlers/class-virtual-tour-handler.php'
        ];
        
        foreach ($handler_files as $type => $file) {
            if (file_exists($file)) {
                require_once $file;
            } else {
                error_log("HPH Marketing Suite: Handler file not found: {$file}");
            }
        }
        
        // Initialize handlers with proper namespace
        $handler_classes = [
            'flyer' => '\\HappyPlace\\Dashboard\\Handlers\\Flyer_Generator_Handler',
            'social' => '\\HappyPlace\\Dashboard\\Handlers\\Social_Media_Handler',
            'email' => '\\HappyPlace\\Dashboard\\Handlers\\Email_Campaign_Handler',
            'virtual_tour' => '\\HappyPlace\\Dashboard\\Handlers\\Virtual_Tour_Handler'
        ];
        
        foreach ($handler_classes as $type => $class) {
            if (class_exists($class)) {
                $this->tool_handlers[$type] = new $class();
                if (method_exists($this->tool_handlers[$type], 'init')) {
                    $this->tool_handlers[$type]->init();
                }
                error_log("HPH Marketing Suite: Initialized {$type} handler");
            } else {
                error_log("HPH Marketing Suite: Handler class {$class} not found");
            }
        }
    }
    
    /**
     * Handle AJAX flyer generator request
     */
    public function ajax_flyer_generator(): void {
        check_ajax_referer('hph_dashboard', 'nonce');
        
        $listing_id = intval($_POST['listing_id'] ?? 0);
        
        if (!$listing_id) {
            wp_send_json_error(['message' => __('Listing ID required', 'happy-place')]);
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $listing_id)) {
            wp_send_json_error(['message' => __('Permission denied', 'happy-place')]);
        }
        
        // Get listing data
        $listing_data = $this->get_listing_data($listing_id);
        
        if (!$listing_data) {
            wp_send_json_error(['message' => __('Listing not found', 'happy-place')]);
        }
        
        // Get flyer templates
        $templates = $this->get_flyer_templates();
        
        ob_start();
        ?>
        <div class="hph-flyer-generator">
            <div class="hph-tool-header">
                <h3><?php esc_html_e('Create Marketing Flyer', 'happy-place'); ?></h3>
                <p><?php echo esc_html($listing_data['address']); ?></p>
            </div>
            
            <form class="hph-marketing-form" data-tool="flyer" data-listing="<?php echo esc_attr($listing_id); ?>">
                
                <div class="hph-form-section">
                    <h4><?php esc_html_e('Choose Template', 'happy-place'); ?></h4>
                    <div class="hph-template-grid">
                        <?php foreach ($templates as $template_id => $template): ?>
                            <label class="hph-template-option">
                                <input type="radio" name="template" value="<?php echo esc_attr($template_id); ?>" 
                                       <?php checked($template_id, 'modern'); ?>>
                                <div class="hph-template-preview">
                                    <img src="<?php echo esc_url($template['preview']); ?>" 
                                         alt="<?php echo esc_attr($template['name']); ?>">
                                    <span class="hph-template-name"><?php echo esc_html($template['name']); ?></span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="hph-form-section">
                    <h4><?php esc_html_e('Flyer Content', 'happy-place'); ?></h4>
                    
                    <div class="hph-form-group">
                        <label for="flyer-headline"><?php esc_html_e('Headline', 'happy-place'); ?></label>
                        <input type="text" id="flyer-headline" name="headline" 
                               value="<?php echo esc_attr($this->generate_headline($listing_data)); ?>" 
                               class="hph-form-control">
                    </div>
                    
                    <div class="hph-form-group">
                        <label for="flyer-description"><?php esc_html_e('Description', 'happy-place'); ?></label>
                        <textarea id="flyer-description" name="description" 
                                  class="hph-form-control" rows="4"><?php 
                            echo esc_textarea($listing_data['description'] ?? ''); 
                        ?></textarea>
                    </div>
                    
                    <div class="hph-form-group">
                        <label><?php esc_html_e('Features to Include', 'happy-place'); ?></label>
                        <div class="hph-checkbox-group">
                            <?php 
                            $features = [
                                'price' => __('Price', 'happy-place'),
                                'bedrooms' => __('Bedrooms', 'happy-place'),
                                'bathrooms' => __('Bathrooms', 'happy-place'),
                                'square_feet' => __('Square Feet', 'happy-place'),
                                'lot_size' => __('Lot Size', 'happy-place'),
                                'year_built' => __('Year Built', 'happy-place'),
                                'garage' => __('Garage', 'happy-place'),
                                'hoa' => __('HOA Info', 'happy-place')
                            ];
                            
                            foreach ($features as $key => $label): 
                                if (!empty($listing_data[$key])):
                            ?>
                                <label>
                                    <input type="checkbox" name="features[]" value="<?php echo esc_attr($key); ?>" checked>
                                    <?php echo esc_html($label); ?>
                                </label>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                    
                    <div class="hph-form-group">
                        <label><?php esc_html_e('Photos to Include', 'happy-place'); ?></label>
                        <div class="hph-photo-selector">
                            <?php 
                            $photos = $listing_data['gallery'] ?? [];
                            $max_photos = 6;
                            
                            for ($i = 0; $i < min(count($photos), $max_photos); $i++): 
                                $photo = $photos[$i];
                            ?>
                                <label class="hph-photo-option">
                                    <input type="checkbox" name="photos[]" value="<?php echo esc_attr($photo['id']); ?>" 
                                           <?php checked($i < 4); ?>>
                                    <img src="<?php echo esc_url($photo['thumbnail']); ?>" 
                                         alt="<?php echo esc_attr($photo['alt']); ?>">
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                
                <div class="hph-form-section">
                    <h4><?php esc_html_e('Contact Information', 'happy-place'); ?></h4>
                    
                    <div class="hph-form-group">
                        <label>
                            <input type="checkbox" name="include_agent_photo" value="1" checked>
                            <?php esc_html_e('Include Agent Photo', 'happy-place'); ?>
                        </label>
                    </div>
                    
                    <div class="hph-form-group">
                        <label>
                            <input type="checkbox" name="include_office_logo" value="1" checked>
                            <?php esc_html_e('Include Office Logo', 'happy-place'); ?>
                        </label>
                    </div>
                    
                    <div class="hph-form-group">
                        <label for="flyer-call-to-action"><?php esc_html_e('Call to Action', 'happy-place'); ?></label>
                        <input type="text" id="flyer-call-to-action" name="call_to_action" 
                               value="<?php esc_attr_e('Schedule Your Private Showing Today!', 'happy-place'); ?>" 
                               class="hph-form-control">
                    </div>
                </div>
                
                <div class="hph-form-actions">
                    <button type="button" class="button hph-preview-flyer" data-action="preview">
                        <i class="fas fa-eye"></i> <?php esc_html_e('Preview', 'happy-place'); ?>
                    </button>
                    <button type="submit" class="button button-primary">
                        <i class="fas fa-download"></i> <?php esc_html_e('Generate & Download', 'happy-place'); ?>
                    </button>
                </div>
                
            </form>
        </div>
        <?php
        
        $html = ob_get_clean();
        
        wp_send_json_success(['html' => $html]);
    }
    
    /**
     * Handle AJAX social media tool request
     */
    public function ajax_social_media_tool(): void {
        check_ajax_referer('hph_dashboard', 'nonce');
        
        $listing_id = intval($_POST['listing_id'] ?? 0);
        
        if (!$listing_id) {
            wp_send_json_error(['message' => __('Listing ID required', 'happy-place')]);
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $listing_id)) {
            wp_send_json_error(['message' => __('Permission denied', 'happy-place')]);
        }
        
        // Get listing data
        $listing_data = $this->get_listing_data($listing_id);
        
        if (!$listing_data) {
            wp_send_json_error(['message' => __('Listing not found', 'happy-place')]);
        }
        
        // Get social platforms
        $platforms = $this->get_social_platforms();
        
        ob_start();
        ?>
        <div class="hph-social-media-tool">
            <div class="hph-tool-header">
                <h3><?php esc_html_e('Create Social Media Posts', 'happy-place'); ?></h3>
                <p><?php echo esc_html($listing_data['address']); ?></p>
            </div>
            
            <form class="hph-marketing-form" data-tool="social" data-listing="<?php echo esc_attr($listing_id); ?>">
                
                <div class="hph-form-section">
                    <h4><?php esc_html_e('Select Platforms', 'happy-place'); ?></h4>
                    <div class="hph-platform-selector">
                        <?php foreach ($platforms as $platform => $config): ?>
                            <label class="hph-platform-option">
                                <input type="checkbox" name="platforms[]" value="<?php echo esc_attr($platform); ?>" 
                                       <?php checked(in_array($platform, ['facebook', 'instagram'])); ?>>
                                <i class="<?php echo esc_attr($config['icon']); ?>"></i>
                                <span><?php echo esc_html($config['name']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="hph-form-section">
                    <h4><?php esc_html_e('Post Content', 'happy-place'); ?></h4>
                    
                    <div class="hph-form-group">
                        <label><?php esc_html_e('Post Type', 'happy-place'); ?></label>
                        <select name="post_type" class="hph-form-control" id="social-post-type">
                            <option value="new_listing"><?php esc_html_e('New Listing', 'happy-place'); ?></option>
                            <option value="open_house"><?php esc_html_e('Open House', 'happy-place'); ?></option>
                            <option value="price_reduction"><?php esc_html_e('Price Reduction', 'happy-place'); ?></option>
                            <option value="just_sold"><?php esc_html_e('Just Sold', 'happy-place'); ?></option>
                            <option value="feature_highlight"><?php esc_html_e('Feature Highlight', 'happy-place'); ?></option>
                        </select>
                    </div>
                    
                    <div class="hph-form-group">
                        <label for="social-caption"><?php esc_html_e('Caption', 'happy-place'); ?></label>
                        <textarea id="social-caption" name="caption" class="hph-form-control" rows="5"><?php 
                            echo esc_textarea($this->generate_social_caption($listing_data, 'new_listing'));
                        ?></textarea>
                        <div class="hph-character-count">
                            <span class="current">0</span> / <span class="limit">280</span> <?php esc_html_e('characters', 'happy-place'); ?>
                        </div>
                    </div>
                    
                    <div class="hph-form-group">
                        <label><?php esc_html_e('Hashtags', 'happy-place'); ?></label>
                        <input type="text" name="hashtags" class="hph-form-control" 
                               value="<?php echo esc_attr($this->generate_hashtags($listing_data)); ?>"
                               placeholder="<?php esc_attr_e('#realestate #newhome #forsale', 'happy-place'); ?>">
                    </div>
                    
                    <div class="hph-form-group">
                        <label><?php esc_html_e('Images', 'happy-place'); ?></label>
                        <div class="hph-image-selector">
                            <?php 
                            $photos = $listing_data['gallery'] ?? [];
                            for ($i = 0; $i < min(count($photos), 4); $i++): 
                                $photo = $photos[$i];
                            ?>
                                <label class="hph-image-option">
                                    <input type="checkbox" name="images[]" value="<?php echo esc_attr($photo['id']); ?>" 
                                           <?php checked($i === 0); ?>>
                                    <img src="<?php echo esc_url($photo['thumbnail']); ?>" 
                                         alt="<?php echo esc_attr($photo['alt']); ?>">
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                
                <div class="hph-form-section">
                    <h4><?php esc_html_e('Scheduling', 'happy-place'); ?></h4>
                    
                    <div class="hph-form-group">
                        <label>
                            <input type="radio" name="schedule" value="now" checked>
                            <?php esc_html_e('Post Now', 'happy-place'); ?>
                        </label>
                        <label>
                            <input type="radio" name="schedule" value="later">
                            <?php esc_html_e('Schedule for Later', 'happy-place'); ?>
                        </label>
                    </div>
                    
                    <div class="hph-schedule-options" style="display: none;">
                        <div class="hph-form-group">
                            <label><?php esc_html_e('Date & Time', 'happy-place'); ?></label>
                            <input type="datetime-local" name="schedule_time" class="hph-form-control">
                        </div>
                    </div>
                </div>
                
                <div class="hph-form-actions">
                    <button type="button" class="button hph-preview-post" data-action="preview">
                        <i class="fas fa-eye"></i> <?php esc_html_e('Preview', 'happy-place'); ?>
                    </button>
                    <button type="submit" class="button button-primary">
                        <i class="fas fa-share"></i> <?php esc_html_e('Create Posts', 'happy-place'); ?>
                    </button>
                </div>
                
            </form>
        </div>
        <?php
        
        $html = ob_get_clean();
        
        wp_send_json_success(['html' => $html]);
    }
    
    /**
     * Handle AJAX email campaign request
     */
    public function ajax_email_campaign(): void {
        check_ajax_referer('hph_dashboard', 'nonce');
        
        $listing_id = intval($_POST['listing_id'] ?? 0);
        
        // Get email templates
        $templates = $this->get_email_templates();
        
        ob_start();
        ?>
        <div class="hph-email-campaign-tool">
            <div class="hph-tool-header">
                <h3><?php esc_html_e('Create Email Campaign', 'happy-place'); ?></h3>
                <?php if ($listing_id): ?>
                    <p><?php esc_html_e('For listing:', 'happy-place'); ?> <?php echo esc_html($this->get_listing_title($listing_id)); ?></p>
                <?php endif; ?>
            </div>
            
            <form class="hph-marketing-form" data-tool="email" data-listing="<?php echo esc_attr($listing_id); ?>">
                
                <div class="hph-form-section">
                    <h4><?php esc_html_e('Campaign Type', 'happy-place'); ?></h4>
                    
                    <div class="hph-campaign-types">
                        <?php 
                        $campaign_types = [
                            'new_listing' => [
                                'icon' => 'fas fa-home',
                                'title' => __('New Listing Announcement', 'happy-place'),
                                'description' => __('Announce a new property to your contact list', 'happy-place')
                            ],
                            'open_house' => [
                                'icon' => 'fas fa-calendar',
                                'title' => __('Open House Invitation', 'happy-place'),
                                'description' => __('Invite contacts to an upcoming open house', 'happy-place')
                            ],
                            'newsletter' => [
                                'icon' => 'fas fa-newspaper',
                                'title' => __('Market Newsletter', 'happy-place'),
                                'description' => __('Share market updates and featured listings', 'happy-place')
                            ],
                            'nurture' => [
                                'icon' => 'fas fa-heart',
                                'title' => __('Lead Nurture', 'happy-place'),
                                'description' => __('Stay in touch with past clients and leads', 'happy-place')
                            ]
                        ];
                        
                        foreach ($campaign_types as $type => $config): ?>
                            <label class="hph-campaign-type-option">
                                <input type="radio" name="campaign_type" value="<?php echo esc_attr($type); ?>"
                                       <?php checked($type, 'new_listing'); ?>>
                                <div class="hph-campaign-type-content">
                                    <i class="<?php echo esc_attr($config['icon']); ?>"></i>
                                    <h5><?php echo esc_html($config['title']); ?></h5>
                                    <p><?php echo esc_html($config['description']); ?></p>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="hph-form-section">
                    <h4><?php esc_html_e('Recipients', 'happy-place'); ?></h4>
                    
                    <div class="hph-form-group">
                        <label><?php esc_html_e('Send To', 'happy-place'); ?></label>
                        <select name="recipient_list" class="hph-form-control">
                            <option value="all_contacts"><?php esc_html_e('All Contacts', 'happy-place'); ?></option>
                            <option value="active_leads"><?php esc_html_e('Active Leads', 'happy-place'); ?></option>
                            <option value="past_clients"><?php esc_html_e('Past Clients', 'happy-place'); ?></option>
                            <option value="sphere"><?php esc_html_e('Sphere of Influence', 'happy-place'); ?></option>
                            <option value="custom"><?php esc_html_e('Custom List', 'happy-place'); ?></option>
                        </select>
                    </div>
                    
                    <div class="hph-recipient-stats">
                        <p><?php esc_html_e('Estimated recipients:', 'happy-place'); ?> <strong class="recipient-count">0</strong></p>
                    </div>
                </div>
                
                <div class="hph-form-section">
                    <h4><?php esc_html_e('Email Content', 'happy-place'); ?></h4>
                    
                    <div class="hph-form-group">
                        <label for="email-subject"><?php esc_html_e('Subject Line', 'happy-place'); ?></label>
                        <input type="text" id="email-subject" name="subject" class="hph-form-control"
                               placeholder="<?php esc_attr_e('Your Next Dream Home Awaits!', 'happy-place'); ?>">
                    </div>
                    
                    <div class="hph-form-group">
                        <label><?php esc_html_e('Template', 'happy-place'); ?></label>
                        <div class="hph-template-selector">
                            <?php foreach ($templates as $template_id => $template): ?>
                                <label class="hph-template-option">
                                    <input type="radio" name="template" value="<?php echo esc_attr($template_id); ?>">
                                    <div class="hph-template-info">
                                        <img src="<?php echo esc_url($template['thumbnail']); ?>" 
                                             alt="<?php echo esc_attr($template['name']); ?>">
                                        <span><?php echo esc_html($template['name']); ?></span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="hph-form-group">
                        <label for="email-preheader"><?php esc_html_e('Preview Text', 'happy-place'); ?></label>
                        <input type="text" id="email-preheader" name="preheader" class="hph-form-control"
                               placeholder="<?php esc_attr_e('Check out this amazing property...', 'happy-place'); ?>">
                    </div>
                </div>
                
                <div class="hph-form-actions">
                    <button type="button" class="button hph-test-email" data-action="test">
                        <i class="fas fa-paper-plane"></i> <?php esc_html_e('Send Test', 'happy-place'); ?>
                    </button>
                    <button type="submit" class="button button-primary">
                        <i class="fas fa-rocket"></i> <?php esc_html_e('Launch Campaign', 'happy-place'); ?>
                    </button>
                </div>
                
            </form>
        </div>
        <?php
        
        $html = ob_get_clean();
        
        wp_send_json_success(['html' => $html]);
    }
    
    /**
     * Handle AJAX virtual tour request
     */
    public function ajax_virtual_tour(): void {
        check_ajax_referer('hph_dashboard', 'nonce');
        
        $listing_id = intval($_POST['listing_id'] ?? 0);
        
        if (!$listing_id) {
            wp_send_json_error(['message' => __('Listing ID required', 'happy-place')]);
        }
        
        ob_start();
        ?>
        <div class="hph-virtual-tour-tool">
            <div class="hph-tool-header">
                <h3><?php esc_html_e('Create Virtual Tour', 'happy-place'); ?></h3>
            </div>
            
            <div class="hph-tool-content">
                <p><?php esc_html_e('Virtual tour creation coming soon!', 'happy-place'); ?></p>
            </div>
        </div>
        <?php
        
        $html = ob_get_clean();
        
        wp_send_json_success(['html' => $html]);
    }
    
    /**
     * Handle AJAX generate material request
     */
    public function ajax_generate_material(): void {
        check_ajax_referer('hph_dashboard', 'nonce');
        
        $tool = sanitize_text_field($_POST['tool'] ?? '');
        $form_data = $_POST['form_data'] ?? [];
        
        if (!isset($this->tool_handlers[$tool])) {
            wp_send_json_error(['message' => __('Invalid tool specified', 'happy-place')]);
        }
        
        $handler = $this->tool_handlers[$tool];
        
        try {
            $result = $handler->generate($form_data);
            
            if (is_wp_error($result)) {
                wp_send_json_error([
                    'message' => $result->get_error_message()
                ]);
            }
            
            wp_send_json_success($result);
            
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => __('Error generating material: ', 'happy-place') . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get listing data for marketing
     *
     * @param int $listing_id Listing ID
     * @return array|null Listing data
     */
    private function get_listing_data(int $listing_id): ?array {
        if (function_exists('hph_bridge_get_listing_data')) {
            return hph_bridge_get_listing_data($listing_id);
        }
        
        // Fallback to direct query
        $post = get_post($listing_id);
        if (!$post || $post->post_type !== 'listing') {
            return null;
        }
        
        return [
            'id' => $listing_id,
            'title' => $post->post_title,
            'address' => get_post_meta($listing_id, 'listing_address', true),
            'price' => get_post_meta($listing_id, 'listing_price', true),
            'description' => $post->post_content,
            'bedrooms' => get_post_meta($listing_id, 'bedrooms', true),
            'bathrooms' => get_post_meta($listing_id, 'bathrooms', true),
            'square_feet' => get_post_meta($listing_id, 'square_feet', true),
            'gallery' => $this->get_listing_gallery($listing_id)
        ];
    }
    
    /**
     * Get listing gallery images
     *
     * @param int $listing_id Listing ID
     * @return array Gallery images
     */
    private function get_listing_gallery(int $listing_id): array {
        $gallery = [];
        $gallery_ids = get_post_meta($listing_id, 'listing_gallery', true);
        
        if (!empty($gallery_ids) && is_array($gallery_ids)) {
            foreach ($gallery_ids as $attachment_id) {
                $gallery[] = [
                    'id' => $attachment_id,
                    'url' => wp_get_attachment_url($attachment_id),
                    'thumbnail' => wp_get_attachment_image_url($attachment_id, 'thumbnail'),
                    'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true)
                ];
            }
        }
        
        return $gallery;
    }
    
    /**
     * Get listing title
     *
     * @param int $listing_id Listing ID
     * @return string Listing title
     */
    private function get_listing_title(int $listing_id): string {
        return get_the_title($listing_id) ?: __('Unknown Listing', 'happy-place');
    }
    
    /**
     * Get flyer templates
     *
     * @return array Available templates
     */
    private function get_flyer_templates(): array {
        return apply_filters('hph_flyer_templates', [
            'modern' => [
                'name' => __('Modern', 'happy-place'),
                'preview' => plugin_dir_url(__FILE__) . 'assets/img/flyer-modern.jpg'
            ],
            'classic' => [
                'name' => __('Classic', 'happy-place'),
                'preview' => plugin_dir_url(__FILE__) . 'assets/img/flyer-classic.jpg'
            ],
            'luxury' => [
                'name' => __('Luxury', 'happy-place'),
                'preview' => plugin_dir_url(__FILE__) . 'assets/img/flyer-luxury.jpg'
            ],
            'minimal' => [
                'name' => __('Minimal', 'happy-place'),
                'preview' => plugin_dir_url(__FILE__) . 'assets/img/flyer-minimal.jpg'
            ]
        ]);
    }
    
    /**
     * Get social platforms
     *
     * @return array Available platforms
     */
    private function get_social_platforms(): array {
        return [
            'facebook' => [
                'name' => __('Facebook', 'happy-place'),
                'icon' => 'fab fa-facebook-f',
                'char_limit' => null
            ],
            'instagram' => [
                'name' => __('Instagram', 'happy-place'),
                'icon' => 'fab fa-instagram',
                'char_limit' => 2200
            ],
            'twitter' => [
                'name' => __('Twitter/X', 'happy-place'),
                'icon' => 'fab fa-twitter',
                'char_limit' => 280
            ],
            'linkedin' => [
                'name' => __('LinkedIn', 'happy-place'),
                'icon' => 'fab fa-linkedin-in',
                'char_limit' => 3000
            ]
        ];
    }
    
    /**
     * Get email templates
     *
     * @return array Available templates
     */
    private function get_email_templates(): array {
        return apply_filters('hph_email_templates', [
            'default' => [
                'name' => __('Default', 'happy-place'),
                'thumbnail' => plugin_dir_url(__FILE__) . 'assets/img/email-default.jpg'
            ],
            'elegant' => [
                'name' => __('Elegant', 'happy-place'),
                'thumbnail' => plugin_dir_url(__FILE__) . 'assets/img/email-elegant.jpg'
            ],
            'modern' => [
                'name' => __('Modern', 'happy-place'),
                'thumbnail' => plugin_dir_url(__FILE__) . 'assets/img/email-modern.jpg'
            ]
        ]);
    }
    
    /**
     * Generate headline for listing
     *
     * @param array $listing_data Listing data
     * @return string Generated headline
     */
    private function generate_headline(array $listing_data): string {
        $templates = [
            __('Stunning %s in %s', 'happy-place'),
            __('Beautiful %s - %s', 'happy-place'),
            __('Your Dream Home Awaits in %s', 'happy-place'),
            __('Exceptional %s Property', 'happy-place')
        ];
        
        $property_type = $listing_data['property_type'] ?? __('Home', 'happy-place');
        $location = $listing_data['city'] ?? $listing_data['neighborhood'] ?? '';
        
        $template = $templates[array_rand($templates)];
        
        return sprintf($template, $property_type, $location);
    }
    
    /**
     * Generate social media caption
     *
     * @param array $listing_data Listing data
     * @param string $post_type Post type
     * @return string Generated caption
     */
    private function generate_social_caption(array $listing_data, string $post_type): string {
        $price = '$' . number_format($listing_data['price'] ?? 0);
        $beds = $listing_data['bedrooms'] ?? 0;
        $baths = $listing_data['bathrooms'] ?? 0;
        $address = $listing_data['address'] ?? '';
        
        switch ($post_type) {
            case 'new_listing':
                return sprintf(
                    __("🏡 NEW LISTING! %s\n%s bed | %s bath\n%s\n\nDon't miss this incredible opportunity!", 'happy-place'),
                    $price, $beds, $baths, $address
                );
                
            case 'open_house':
                return sprintf(
                    __("🏠 OPEN HOUSE this weekend!\n📍 %s\n💰 %s\n\nCome see this beautiful %s bed, %s bath home!", 'happy-place'),
                    $address, $price, $beds, $baths
                );
                
            case 'price_reduction':
                return sprintf(
                    __("💥 PRICE REDUCED! 💥\nNow only %s!\n📍 %s\n\nThis won't last long at this price!", 'happy-place'),
                    $price, $address
                );
                
            default:
                return '';
        }
    }
    
    /**
     * Generate hashtags for listing
     *
     * @param array $listing_data Listing data
     * @return string Generated hashtags
     */
    private function generate_hashtags(array $listing_data): string {
        $hashtags = ['#realestate', '#forsale', '#newhome'];
        
        if (!empty($listing_data['city'])) {
            $city = str_replace(' ', '', $listing_data['city']);
            $hashtags[] = '#' . $city . 'RealEstate';
            $hashtags[] = '#' . $city . 'Homes';
        }
        
        if (!empty($listing_data['property_type'])) {
            $type = strtolower($listing_data['property_type']);
            if ($type === 'condo' || $type === 'condominium') {
                $hashtags[] = '#condoforsale';
            } elseif ($type === 'townhouse') {
                $hashtags[] = '#townhouseforsale';
            }
        }
        
        $hashtags[] = '#realtor';
        $hashtags[] = '#homesforsale';
        
        return implode(' ', array_slice($hashtags, 0, 10));
    }
    
    /**
     * Add marketing forms to dashboard
     *
     * @param array $forms Existing forms
     * @return array Modified forms
     */
    public function add_marketing_forms(array $forms): array {
        $forms['flyer-generator'] = [
            'title' => __('Flyer Generator', 'happy-place'),
            'type' => 'marketing',
            'template' => 'dashboard-flyer-generator',
            'modal' => true,
            'size' => 'large'
        ];
        
        $forms['social-media'] = [
            'title' => __('Social Media Posts', 'happy-place'),
            'type' => 'marketing',
            'template' => 'dashboard-social-media',
            'modal' => true,
            'size' => 'medium'
        ];
        
        $forms['email-campaign'] = [
            'title' => __('Email Campaign', 'happy-place'),
            'type' => 'marketing',
            'template' => 'dashboard-email-campaign',
            'modal' => true,
            'size' => 'large'
        ];
        
        return $forms;
    }
    
    /**
     * Enqueue marketing scripts
     */
    public function enqueue_marketing_scripts(): void {
        wp_enqueue_script(
            'hph-marketing-suite',
            plugin_dir_url(__FILE__) . 'assets/js/marketing-suite.js',
            ['jquery', 'hph-dashboard-core'],
            '1.0.0',
            true
        );
        
        wp_localize_script('hph-marketing-suite', 'hph_marketing', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_marketing'),
            'messages' => [
                'generating' => __('Generating...', 'happy-place'),
                'error' => __('An error occurred', 'happy-place'),
                'success' => __('Successfully generated!', 'happy-place')
            ]
        ]);
    }
}