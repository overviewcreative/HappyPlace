<?php
/**
 * Marketing Suite Generator - Comprehensive multi-format marketing generator
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

namespace HappyPlace\Dashboard;

use Exception;

if (!defined('ABSPATH')) {
    exit;
}

class Marketing_Suite_Generator {
    
    /**
     * Singleton instance
     */
    private static ?self $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Format configurations
     */
    private array $format_configs = [
        'full_flyer' => [
            'width' => 850,
            'height' => 1100,
            'name' => 'Full Flyer',
            'description' => '8.5" x 11" print flyer',
            'category' => 'print'
        ],
        'instagram_post' => [
            'width' => 1080,
            'height' => 1080,
            'name' => 'Instagram Post',
            'description' => 'Square format for Instagram',
            'category' => 'social'
        ],
        'instagram_story' => [
            'width' => 1080,
            'height' => 1920,
            'name' => 'Instagram Story',
            'description' => 'Vertical story format',
            'category' => 'social'
        ],
        'facebook_post' => [
            'width' => 1200,
            'height' => 630,
            'name' => 'Facebook Post',
            'description' => 'Optimized for Facebook sharing',
            'category' => 'social'
        ],
        'twitter_post' => [
            'width' => 1200,
            'height' => 675,
            'name' => 'Twitter Post',
            'description' => 'Twitter card format',
            'category' => 'social'
        ],
        'web_banner' => [
            'width' => 1200,
            'height' => 400,
            'name' => 'Web Banner',
            'description' => 'Website hero banner',
            'category' => 'web'
        ],
        'featured_listing' => [
            'width' => 600,
            'height' => 400,
            'name' => 'Featured Listing',
            'description' => 'Website featured property',
            'category' => 'web'
        ],
        'email_header' => [
            'width' => 800,
            'height' => 300,
            'name' => 'Email Header',
            'description' => 'Email newsletter header',
            'category' => 'email'
        ],
        'postcard' => [
            'width' => 600,
            'height' => 400,
            'name' => 'Postcard',
            'description' => '6" x 4" postcard format',
            'category' => 'print'
        ],
        'business_card' => [
            'width' => 350,
            'height' => 200,
            'name' => 'Business Card',
            'description' => 'Quick reference card',
            'category' => 'print'
        ]
    ];
    
    /**
     * Campaign types
     */
    private array $campaign_types = [
        'listing' => 'For Sale',
        'open_house' => 'Open House',
        'price_change' => 'Price Reduced',
        'under_contract' => 'Under Contract',
        'sold' => 'Sold',
        'coming_soon' => 'Coming Soon'
    ];
    
    /**
     * Initialize the generator
     */
    public function __construct() {
        add_action('wp_ajax_hph_generate_marketing_suite', [$this, 'handle_ajax_generate']);
        add_action('wp_ajax_hph_get_marketing_data', [$this, 'handle_ajax_get_data']);
        add_action('wp_ajax_nopriv_hph_generate_marketing_suite', [$this, 'handle_ajax_generate']);
        add_action('wp_ajax_nopriv_hph_get_marketing_data', [$this, 'handle_ajax_get_data']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // Debug log to confirm initialization
        error_log('HPH Marketing Suite Generator: Initialized successfully');
    }
    
    /**
     * Add admin menu item
     */
    public function add_admin_menu(): void {
        error_log('HPH Marketing Suite: Adding admin menu...');
        
        // First try as submenu under Happy Place Dashboard
        if (menu_page_url('happy-place-dashboard', false)) {
            error_log('HPH Marketing Suite: Adding as submenu under happy-place-dashboard');
            add_submenu_page(
                'happy-place-dashboard',
                __('Marketing Suite', 'happy-place'),
                __('Marketing Suite', 'happy-place'),
                'read',
                'marketing-suite-generator',
                [$this, 'render_admin_page']
            );
        } else {
            error_log('HPH Marketing Suite: Adding as top-level menu (fallback)');
            // Fallback: Add as top-level menu item
            add_menu_page(
                __('Marketing Suite', 'happy-place'),
                __('Marketing Suite', 'happy-place'),
                'read',
                'marketing-suite-generator',
                [$this, 'render_admin_page'],
                'dashicons-images-alt2',
                30
            );
        }
        
        error_log('HPH Marketing Suite: Menu added successfully');
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook): void {
        if (strpos($hook, 'marketing-suite') === false && !$this->is_dashboard_page()) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('read')) {
            return;
        }
        
        // Fabric.js for canvas manipulation
        wp_enqueue_script(
            'fabric-js',
            'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js',
            [],
            '5.3.0',
            true
        );
        
        // JSZip for zip file generation
        wp_enqueue_script(
            'jszip',
            'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js',
            [],
            '3.10.1',
            true
        );
        
        // Marketing Suite Generator script
        wp_enqueue_script(
            'marketing-suite-generator',
            HPH_PLUGIN_URL . 'assets/js/marketing-suite-generator.js',
            ['jquery', 'fabric-js', 'jszip'],
            HPH_VERSION,
            true
        );
        
        // Localize script with comprehensive configuration
        wp_localize_script('marketing-suite-generator', 'marketingSuite', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('marketing_suite_nonce'),
            'dashboardNonce' => wp_create_nonce('hph_dashboard_nonce'),
            'formatConfigs' => $this->format_configs,
            'campaignTypes' => $this->campaign_types,
            'strings' => [
                'generating' => __('Generating marketing suite...', 'happy-place'),
                'complete' => __('Marketing suite generated successfully!', 'happy-place'),
                'error' => __('Error generating marketing suite.', 'happy-place'),
                'selectListing' => __('Please select a listing.', 'happy-place'),
                'selectFormats' => __('Please select at least one format.', 'happy-place'),
            ]
        ]);

        // Also provide flyerGenerator object for compatibility
        wp_localize_script('marketing-suite-generator', 'flyerGenerator', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('marketing_suite_nonce'),
            'dashboardNonce' => wp_create_nonce('hph_dashboard_nonce'),
            'pluginUrl' => HPH_PLUGIN_URL,
            'formatConfigs' => $this->format_configs,
            'campaignTypes' => $this->campaign_types
        ]);
        
        // Styles
        wp_enqueue_style(
            'marketing-suite-generator',
            HPH_PLUGIN_URL . 'assets/css/marketing-suite-generator.css',
            [],
            HPH_VERSION
        );
    }
    
    /**
     * Check if current page is dashboard
     */
    private function is_dashboard_page(): bool {
        global $pagenow;
        return $pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === 'happy-place-dashboard';
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page(): void {
        error_log('HPH Marketing Suite: Rendering admin page...');
        error_log('HPH Marketing Suite: Current user can read: ' . (current_user_can('read') ? 'YES' : 'NO'));
        error_log('HPH Marketing Suite: Current user can edit_posts: ' . (current_user_can('edit_posts') ? 'YES' : 'NO'));
        error_log('HPH Marketing Suite: Current user can manage_options: ' . (current_user_can('manage_options') ? 'YES' : 'NO'));
        
        $listings = $this->get_listings();
        $agents = $this->get_agents();
        
        error_log('HPH Marketing Suite: Found ' . count($listings) . ' listings and ' . count($agents) . ' agents');
        ?>
        <div class="wrap marketing-suite-wrap">
            <h1><?php _e('Marketing Suite Generator', 'happy-place'); ?></h1>
            
            <?php $this->render_generator_interface($listings, $agents); ?>
        </div>
        <?php
    }
    
    /**
     * Render the main generator interface
     */
    public function render_generator_interface(array $listings, array $agents): void {
        ?>
        <div id="marketing-suite-generator" class="marketing-suite-container">
            
            <!-- Configuration Panel -->
            <div class="config-panel">
                <div class="panel-section">
                    <h3><?php _e('Campaign Configuration', 'happy-place'); ?></h3>
                    
                    <!-- Campaign Type Selection -->
                    <div class="campaign-types">
                        <label class="section-label"><?php _e('Campaign Type', 'happy-place'); ?></label>
                        <div class="campaign-options">
                            <?php foreach ($this->campaign_types as $type => $label): ?>
                            <div class="campaign-option">
                                <input type="radio" name="campaign_type" value="<?php echo esc_attr($type); ?>" id="campaign-<?php echo esc_attr($type); ?>" <?php checked($type, 'listing'); ?>>
                                <label for="campaign-<?php echo esc_attr($type); ?>">
                                    <span class="option-title"><?php echo esc_html($label); ?></span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Listing Selection -->
                    <div class="listing-selection">
                        <label for="listing-select" class="section-label"><?php _e('Select Listing', 'happy-place'); ?></label>
                        <select id="listing-select" name="listing_id" required>
                            <option value=""><?php _e('Choose a listing...', 'happy-place'); ?></option>
                            <?php foreach ($listings as $listing): ?>
                            <option value="<?php echo esc_attr($listing->ID); ?>" 
                                    data-price="<?php echo esc_attr(get_post_meta($listing->ID, 'price', true)); ?>"
                                    data-address="<?php echo esc_attr(get_post_meta($listing->ID, 'address', true)); ?>">
                                <?php echo esc_html(get_post_meta($listing->ID, 'address', true) ?: $listing->post_title); ?> 
                                - $<?php echo number_format(get_post_meta($listing->ID, 'price', true)); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Open House Section (conditional) -->
                    <div class="open-house-section" style="display: none;">
                        <h4><?php _e('Open House Details', 'happy-place'); ?></h4>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="open-house-date"><?php _e('Date', 'happy-place'); ?></label>
                                <input type="date" id="open-house-date" name="open_house_date">
                            </div>
                            <div class="form-group">
                                <label for="open-house-day"><?php _e('Day', 'happy-place'); ?></label>
                                <input type="text" id="open-house-day" readonly>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="open-house-start"><?php _e('Start Time', 'happy-place'); ?></label>
                                <input type="time" id="open-house-start" name="start_time" value="14:00">
                            </div>
                            <div class="form-group">
                                <label for="open-house-end"><?php _e('End Time', 'happy-place'); ?></label>
                                <input type="time" id="open-house-end" name="end_time" value="16:00">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="hosting-agent"><?php _e('Hosting Agent', 'happy-place'); ?></label>
                            <select id="hosting-agent" name="hosting_agent">
                                <option value=""><?php _e('Select agent...', 'happy-place'); ?></option>
                                <?php foreach ($agents as $agent): ?>
                                <option value="<?php echo esc_attr($agent->ID); ?>">
                                    <?php echo esc_html($agent->post_title); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="open-house-preview">
                            <div class="oh-date">Date: <span>Not set</span></div>
                            <div class="oh-time">Time: <span>Not set</span></div>
                            <div class="oh-agent">Host: <span>Not set</span></div>
                        </div>
                    </div>
                    
                    <!-- Price Change Section (conditional) -->
                    <div class="price-change-section" style="display: none;">
                        <h4><?php _e('Price Change Details', 'happy-place'); ?></h4>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="old-price"><?php _e('Original Price', 'happy-place'); ?></label>
                                <input type="number" id="old-price" name="old_price" step="1000" min="0">
                            </div>
                            <div class="form-group">
                                <label for="new-price"><?php _e('New Price', 'happy-place'); ?></label>
                                <input type="number" id="new-price" name="new_price" step="1000" min="0">
                            </div>
                        </div>
                        
                        <div class="price-change-preview">
                            <div class="price-reduction">Reduction: <span>$0 (0%)</span></div>
                            <div class="price-savings">Save: <span>$0</span></div>
                        </div>
                    </div>
                </div>
                
                <!-- Format Selection -->
                <div class="panel-section">
                    <h3><?php _e('Format Selection', 'happy-place'); ?></h3>
                    
                    <div class="quick-select-buttons">
                        <button type="button" onclick="selectFormats('social')" class="quick-select-btn">
                            <?php _e('Social Media', 'happy-place'); ?>
                        </button>
                        <button type="button" onclick="selectFormats('print')" class="quick-select-btn">
                            <?php _e('Print Materials', 'happy-place'); ?>
                        </button>
                        <button type="button" onclick="selectFormats('web')" class="quick-select-btn">
                            <?php _e('Web Graphics', 'happy-place'); ?>
                        </button>
                        <button type="button" onclick="selectFormats('all')" class="quick-select-btn">
                            <?php _e('Select All', 'happy-place'); ?>
                        </button>
                    </div>
                    
                    <div class="format-grid">
                        <?php foreach ($this->format_configs as $key => $config): ?>
                        <div class="format-option" data-category="<?php echo esc_attr($config['category']); ?>">
                            <input type="checkbox" name="formats[]" value="<?php echo esc_attr($key); ?>" id="format-<?php echo esc_attr($key); ?>">
                            <label for="format-<?php echo esc_attr($key); ?>">
                                <div class="format-preview">
                                    <div class="format-dimensions" style="aspect-ratio: <?php echo $config['width']; ?>/<?php echo $config['height']; ?>;">
                                        <?php echo esc_html($config['width']); ?>×<?php echo esc_html($config['height']); ?>
                                    </div>
                                </div>
                                <div class="format-info">
                                    <div class="format-name"><?php echo esc_html($config['name']); ?></div>
                                    <div class="format-description"><?php echo esc_html($config['description']); ?></div>
                                    <div class="format-category"><?php echo esc_html(ucfirst($config['category'])); ?></div>
                                </div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="format-count">
                        <?php _e('No formats selected', 'happy-place'); ?>
                    </div>
                </div>
                
                <!-- Template Selection -->
                <div class="panel-section">
                    <h3><?php _e('Template Style', 'happy-place'); ?></h3>
                    
                    <div class="template-options">
                        <div class="template-option active">
                            <input type="radio" name="template" value="parker_group" id="template-parker" checked>
                            <label for="template-parker">
                                <div class="template-preview">
                                    <div class="template-sample parker-sample"></div>
                                </div>
                                <div class="template-name">Parker Group</div>
                                <div class="template-description">Professional blue design</div>
                            </label>
                        </div>
                        <div class="template-option">
                            <input type="radio" name="template" value="modern" id="template-modern">
                            <label for="template-modern">
                                <div class="template-preview">
                                    <div class="template-sample modern-sample"></div>
                                </div>
                                <div class="template-name">Modern</div>
                                <div class="template-description">Clean, minimal design</div>
                            </label>
                        </div>
                        <div class="template-option">
                            <input type="radio" name="template" value="luxury" id="template-luxury">
                            <label for="template-luxury">
                                <div class="template-preview">
                                    <div class="template-sample luxury-sample"></div>
                                </div>
                                <div class="template-name">Luxury</div>
                                <div class="template-description">Elegant, upscale design</div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Generate Button -->
                <div class="panel-section">
                    <button type="button" id="generate-marketing-suite" class="generate-btn" disabled>
                        <i class="fas fa-magic"></i>
                        <?php _e('Generate Marketing Suite', 'happy-place'); ?>
                    </button>
                </div>
            </div>
            
            <!-- Progress Panel -->
            <div class="generation-progress" style="display: none;">
                <div class="progress-header">
                    <h3><?php _e('Generating Marketing Suite', 'happy-place'); ?></h3>
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <div class="progress-text"><?php _e('Starting generation...', 'happy-place'); ?></div>
                </div>
                
                <div class="format-progress">
                    <!-- Format progress items will be added here dynamically -->
                </div>
            </div>
            
            <!-- Results Panel -->
            <div class="generation-results" style="display: none;">
                <div class="results-header">
                    <h3><?php _e('Marketing Suite Generated', 'happy-place'); ?></h3>
                    <div class="results-stats">
                        <div class="stat-item">
                            <div class="stat-number">0</div>
                            <div class="stat-label"><?php _e('Formats', 'happy-place'); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">0</div>
                            <div class="stat-label"><?php _e('Successful', 'happy-place'); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">0KB</div>
                            <div class="stat-label"><?php _e('Avg Size', 'happy-place'); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">0s</div>
                            <div class="stat-label"><?php _e('Duration', 'happy-place'); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="results-actions">
                    <button type="button" id="download-all-zip" class="download-all-btn">
                        <i class="fas fa-download"></i>
                        <?php _e('Download All (ZIP)', 'happy-place'); ?>
                    </button>
                    <button type="button" id="download-individual" class="download-individual-btn">
                        <i class="fas fa-images"></i>
                        <?php _e('Individual Downloads', 'happy-place'); ?>
                    </button>
                    <button type="button" id="generate-new" class="generate-new-btn">
                        <i class="fas fa-plus"></i>
                        <?php _e('Generate New', 'happy-place'); ?>
                    </button>
                </div>
                
                <div class="preview-grid">
                    <!-- Preview items will be added here dynamically -->
                </div>
            </div>
            
            <!-- Error Display -->
            <div class="flyer-error" style="display: none;">
                <div class="error-content">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span class="error-message"></span>
                    <button type="button" class="dismiss-error">&times;</button>
                </div>
            </div>
        </div>
        
        <!-- Hidden canvases for generation -->
        <div id="canvas-container" style="display: none;">
            <!-- Canvases will be created dynamically -->
        </div>
        <?php
    }
    
    /**
     * Handle AJAX request for marketing suite generation
     */
    public function handle_ajax_generate(): void {
        try {
            // Enhanced security validation
            $nonce_valid = false;
            if (isset($_POST['nonce'])) {
                if (wp_verify_nonce($_POST['nonce'], 'marketing_suite_nonce')) {
                    $nonce_valid = true;
                } elseif (wp_verify_nonce($_POST['nonce'], 'hph_dashboard_nonce')) {
                    $nonce_valid = true;
                } elseif (wp_verify_nonce($_POST['nonce'], 'hph_ajax_nonce')) {
                    $nonce_valid = true;
                }
            }
            
            if (!$nonce_valid) {
                wp_send_json_error([
                    'message' => __('Security check failed.', 'happy-place'),
                    'code' => 'invalid_nonce'
                ], 403);
                return;
            }
            
            if (!current_user_can('read')) {
                wp_send_json_error([
                    'message' => __('Insufficient permissions.', 'happy-place'),
                    'code' => 'insufficient_permissions'
                ], 403);
                return;
            }
            
            $listing_id = intval($_POST['listing_id'] ?? 0);
            $campaign_type = sanitize_text_field($_POST['campaign_type'] ?? 'listing');
            $formats = array_map('sanitize_text_field', $_POST['formats'] ?? []);
            $template = sanitize_text_field($_POST['template'] ?? 'parker_group');
            
            if (!$listing_id || empty($formats)) {
                wp_send_json_error([
                    'message' => __('Invalid parameters. Listing ID and formats are required.', 'happy-place'),
                    'code' => 'missing_parameters'
                ], 400);
                return;
            }
            
            // Validate listing exists
            $listing = get_post($listing_id);
            if (!$listing || $listing->post_type !== 'listing') {
                wp_send_json_error([
                    'message' => __('Invalid listing ID.', 'happy-place'),
                    'code' => 'invalid_listing'
                ], 400);
                return;
            }
            
            error_log("HPH Marketing Suite: Generating for listing {$listing_id}, formats: " . implode(', ', $formats));
            
            $listing_data = $this->prepare_listing_data($listing_id, $campaign_type, $_POST);
            
            wp_send_json_success([
                'listing' => $listing_data,
                'formats' => $formats,
                'template' => $template,
                'campaign_type' => $campaign_type,
                'message' => __('Marketing suite data prepared successfully.', 'happy-place')
            ]);
            
        } catch (\Exception $e) {
            error_log('HPH Marketing Suite Generate Error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => __('Error generating marketing suite: ', 'happy-place') . $e->getMessage(),
                'code' => 'generation_error'
            ], 500);
        }
    }
    
    /**
     * Handle AJAX request for marketing data
     */
    public function handle_ajax_get_data(): void {
        // Flexible nonce checking for compatibility
        $nonce_valid = false;
        if (isset($_POST['nonce'])) {
            if (wp_verify_nonce($_POST['nonce'], 'marketing_suite_nonce')) {
                $nonce_valid = true;
            } elseif (wp_verify_nonce($_POST['nonce'], 'hph_dashboard_nonce')) {
                $nonce_valid = true;
            }
        }
        
        if (!$nonce_valid) {
            wp_die(__('Security check failed.', 'happy-place'));
        }
        
        $data_type = sanitize_text_field($_POST['data_type'] ?? '');
        
        switch ($data_type) {
            case 'listings':
                wp_send_json_success(['listings' => $this->get_listings()]);
                break;
                
            case 'agents':
                wp_send_json_success(['agents' => $this->get_agents()]);
                break;
                
            default:
                wp_send_json_error(['message' => __('Invalid data type.', 'happy-place')]);
        }
    }
    
    /**
     * Prepare listing data for generation
     */
    private function prepare_listing_data(int $listing_id, string $campaign_type, array $request_data): array {
        $listing = get_post($listing_id);
        if (!$listing || $listing->post_type !== 'listing') {
            throw new Exception(__('Invalid listing.', 'happy-place'));
        }
        
        $meta = get_post_meta($listing_id);
        $gallery = get_post_meta($listing_id, 'gallery', true);
        
        // Process gallery images
        $gallery_urls = [];
        if (!empty($gallery)) {
            if (is_array($gallery)) {
                foreach ($gallery as $attachment_id) {
                    $url = wp_get_attachment_image_url($attachment_id, 'large');
                    if ($url) {
                        $gallery_urls[] = $url;
                    }
                }
            }
        }
        
        // Get featured image if no gallery
        if (empty($gallery_urls)) {
            $featured_id = get_post_thumbnail_id($listing_id);
            if ($featured_id) {
                $featured_url = wp_get_attachment_image_url($featured_id, 'large');
                if ($featured_url) {
                    $gallery_urls[] = $featured_url;
                }
            }
        }
        
        // Get agent data
        $agent_id = get_post_meta($listing_id, 'agent_id', true);
        $agent_data = [];
        if ($agent_id) {
            $agent = get_post($agent_id);
            if ($agent) {
                $agent_data = [
                    'id' => $agent->ID,
                    'name' => $agent->post_title,
                    'display_name' => $agent->post_title,
                    'email' => get_post_meta($agent_id, 'email', true),
                    'phone' => get_post_meta($agent_id, 'phone', true),
                    'license' => get_post_meta($agent_id, 'license_number', true),
                ];
            }
        }
        
        $listing_data = [
            'id' => $listing_id,
            'title' => $listing->post_title,
            'description' => $listing->post_content,
            'price' => intval($meta['price'][0] ?? 0),
            'address' => $meta['address'][0] ?? '',
            'street_address' => $meta['street_address'][0] ?? $meta['address'][0] ?? '',
            'city' => $meta['city'][0] ?? '',
            'state' => $meta['state'][0] ?? '',
            'zip' => $meta['zip_code'][0] ?? $meta['zip'][0] ?? '',
            'bedrooms' => intval($meta['bedrooms'][0] ?? 0),
            'bathrooms' => floatval($meta['bathrooms'][0] ?? 0),
            'square_feet' => intval($meta['square_feet'][0] ?? $meta['sqft'][0] ?? 0),
            'lot_size' => $meta['lot_size'][0] ?? '',
            'property_type' => $meta['property_type'][0] ?? '',
            'year_built' => $meta['year_built'][0] ?? '',
            'gallery' => $gallery_urls,
            'agent' => $agent_data,
            'permalink' => get_permalink($listing_id),
        ];
        
        // Add campaign-specific data
        if ($campaign_type === 'open_house') {
            $listing_data['open_house'] = [
                'date' => sanitize_text_field($request_data['open_house_data']['date'] ?? ''),
                'start_time' => sanitize_text_field($request_data['open_house_data']['startTime'] ?? ''),
                'end_time' => sanitize_text_field($request_data['open_house_data']['endTime'] ?? ''),
                'agent_id' => intval($request_data['open_house_data']['agentId'] ?? 0),
                'agent_name' => sanitize_text_field($request_data['open_house_data']['agentName'] ?? ''),
            ];
        } elseif ($campaign_type === 'price_change') {
            $listing_data['price_change'] = [
                'old_price' => intval($request_data['price_change_data']['old_price'] ?? 0),
                'new_price' => intval($request_data['price_change_data']['new_price'] ?? 0),
            ];
        }
        
        return $listing_data;
    }
    
    /**
     * Get available listings
     */
    public function get_listings(): array {
        $args = [
            'post_type' => 'listing',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [
                [
                    'key' => 'address',
                    'compare' => 'EXISTS'
                ]
            ]
        ];
        
        return get_posts($args);
    }
    
    /**
     * Get available agents
     */
    public function get_agents(): array {
        $args = [
            'post_type' => 'agent',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ];
        
        return get_posts($args);
    }
    
    /**
     * Get format configurations
     */
    public function get_format_configs(): array {
        return $this->format_configs;
    }
    
    /**
     * Get campaign types
     */
    public function get_campaign_types(): array {
        return $this->campaign_types;
    }
}
