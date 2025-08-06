<?php
/**
 * Marketing Section - Handles marketing tools and campaigns
 * 
 * @package HappyPlace
 * @subpackage Dashboard\Sections
 */

namespace HappyPlace\Dashboard\Sections;

use Exception;

if (!defined('ABSPATH')) {
    exit;
}

class Marketing_Section extends Base_Dashboard_Section {
    
    /**
     * Section configuration
     */
    protected array $config = [
        'id' => 'marketing',
        'title' => 'Marketing Tools',
        'icon' => 'fas fa-bullhorn',
        'priority' => 30,
        'capability' => 'read'
    ];
    
    /**
     * Initialize the section
     */
    public function __construct() {
        parent::__construct();
        
        // AJAX handlers for logged-in users
        add_action('wp_ajax_hph_generate_flyer', [$this, 'generate_flyer']);
        add_action('wp_ajax_hph_schedule_social_post', [$this, 'schedule_social_post']);
        add_action('wp_ajax_hph_create_email_campaign', [$this, 'create_email_campaign']);
        add_action('wp_ajax_hph_get_marketing_templates', [$this, 'get_marketing_templates']);
        add_action('wp_ajax_load_marketing_suite_interface', [$this, 'load_marketing_suite_interface']);
        
        // AJAX handlers for non-logged-in users (if needed)
        add_action('wp_ajax_nopriv_load_marketing_suite_interface', [$this, 'load_marketing_suite_interface']);
        
        // Marketing Suite Integration - Load assets on both admin and frontend dashboard
        add_action('admin_enqueue_scripts', [$this, 'enqueue_marketing_suite_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_marketing_suite_assets']); // Frontend
        add_action('admin_footer', [$this, 'render_marketing_suite_modal']);
        add_action('wp_footer', [$this, 'render_marketing_suite_modal']); // Frontend
    }
    
    /**
     * Get section identifier
     */
    protected function get_section_id(): string {
        return 'marketing';
    }
    
    /**
     * Enqueue marketing suite assets
     */
    public function enqueue_marketing_suite_assets() {
        if (!current_user_can('read')) {
            return;
        }
        
        // Load assets on admin pages OR frontend dashboard pages
        global $pagenow;
        $page = $_GET['page'] ?? '';
        
        // Check if we're on admin Happy Place pages
        $is_admin_dashboard = ($pagenow === 'admin.php' && strpos($page, 'happy-place') !== false);
        
        // Check if we're on frontend dashboard page
        $is_frontend_dashboard = !is_admin() && is_page() && get_page_template_slug() === 'page-templates/agent-dashboard-rebuilt.php';
        
        // Only enqueue on dashboard contexts
        if (!$is_admin_dashboard && !$is_frontend_dashboard) {
            return;
        }
        
        // Enqueue jQuery first (ensure it's loaded)
        wp_enqueue_script('jquery');
        
        // Enqueue Fabric.js for canvas manipulation - use consistent handle
        wp_enqueue_script('fabric-js', 'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js', ['jquery'], '5.3.0', true);
        
        // Enqueue JSZip for creating ZIP downloads
        wp_enqueue_script('jszip', 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js', ['jquery'], '3.10.1', true);
        
        // Enqueue marketing suite assets
        wp_enqueue_script(
            'marketing-suite-generator',
            HPH_PLUGIN_URL . 'assets/js/marketing-suite-generator.js',
            ['jquery', 'fabric-js', 'jszip'],
            HPH_VERSION,
            true
        );
        
        wp_enqueue_style(
            'marketing-suite-generator',
            HPH_PLUGIN_URL . 'assets/css/marketing-suite-generator.css',
            [],
            HPH_VERSION
        );
        
        // Localize script with AJAX URL and nonce - Use consistent variable names
        wp_localize_script('marketing-suite-generator', 'marketingSuiteAjax', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('marketing_suite_nonce'),
            'pluginUrl' => HPH_PLUGIN_URL,
            'dashboardNonce' => wp_create_nonce('hph_dashboard_nonce')
        ]);

        // Also provide flyerGenerator object for compatibility
        wp_localize_script('marketing-suite-generator', 'flyerGenerator', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('marketing_suite_nonce'),
            'pluginUrl' => HPH_PLUGIN_URL,
            'dashboardNonce' => wp_create_nonce('hph_dashboard_nonce')
        ]);
        
        // Debug logging
        error_log('HPH Marketing Suite: Assets enqueued for page: ' . $page);
    }
    
    /**
     * Render marketing suite modal
     */
    /**
     * Render marketing suite modal
     */
    public function render_marketing_suite_modal() {
        if (!current_user_can('read')) {
            return;
        }
        
        // Render on admin dashboard pages OR frontend dashboard pages
        global $pagenow;
        $page = $_GET['page'] ?? '';
        
        // Check contexts
        $is_admin_dashboard = is_admin() && ($pagenow === 'admin.php' && strpos($page, 'happy-place') !== false);
        $is_frontend_dashboard = !is_admin() && is_page() && get_page_template_slug() === 'page-templates/agent-dashboard-rebuilt.php';
        
        if (!$is_admin_dashboard && !$is_frontend_dashboard) {
            return;
        }
        
        // Debug logging
        error_log('HPH Marketing Suite: Rendering modal for page: ' . $page);
        ?>
        <div id="marketing-suite-modal" class="marketing-suite-modal" style="display: none;">
            <div class="marketing-suite-modal-content">
                <div class="marketing-suite-modal-header">
                    <h2>Marketing Suite Generator</h2>
                    <button class="marketing-suite-modal-close">&times;</button>
                </div>
                <div class="marketing-suite-modal-body">
                    <div id="marketing-suite-container">
                        <p>Loading Marketing Suite Generator...</p>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .marketing-suite-modal {
            position: fixed;
            z-index: 999999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .marketing-suite-modal-content {
            background-color: #fefefe;
            margin: 2% auto;
            padding: 0;
            border: none;
            border-radius: 8px;
            width: 95%;
            max-width: 1200px;
            height: 90vh;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .marketing-suite-modal-header {
            padding: 20px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 8px 8px 0 0;
        }
        
        .marketing-suite-modal-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .marketing-suite-modal-close {
            background: none;
            border: none;
            font-size: 32px;
            color: white;
            cursor: pointer;
            padding: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .marketing-suite-modal-close:hover {
            background-color: rgba(255,255,255,0.2);
            transform: rotate(90deg);
        }
        
        .marketing-suite-modal-body {
            padding: 0;
            height: calc(90vh - 80px);
            overflow-y: auto;
        }
        
        .marketing-suite-modal-body .marketing-suite-generator {
            margin: 0;
            padding: 30px;
            background: none;
            border-radius: 0;
            box-shadow: none;
        }
        </style>
        
    /**
     * Render marketing suite modal
     */
    public function render_marketing_suite_modal() {
        if (!current_user_can('read') || !is_admin()) {
            return;
        }
        
        // Always render on Happy Place dashboard pages
        global $pagenow;
        $page = $_GET['page'] ?? '';
        
        if (!($pagenow === 'admin.php' && strpos($page, 'happy-place') !== false)) {
            return;
        }
        
        // Debug logging
        error_log('HPH Marketing Suite: Rendering modal for page: ' . $page);
        ?>
        <div id="marketing-suite-modal" class="marketing-suite-modal" style="display: none;">
            <div class="marketing-suite-modal-content">
                <div class="marketing-suite-modal-header">
                    <h2>Marketing Suite Generator</h2>
                    <button class="marketing-suite-modal-close">&times;</button>
                </div>
                <div class="marketing-suite-modal-body">
                    <div id="marketing-suite-container">
                        <div style="padding: 30px; text-align: center;">
                            <div class="spinner" style="float: none; margin: 20px auto;"></div>
                            <p>Loading Marketing Suite Generator...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .marketing-suite-modal {
            position: fixed;
            z-index: 999999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: none;
        }
        
        .marketing-suite-modal-content {
            background-color: #fefefe;
            margin: 2% auto;
            padding: 0;
            border: none;
            border-radius: 8px;
            width: 95%;
            max-width: 1200px;
            height: 90vh;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .marketing-suite-modal-header {
            padding: 20px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 8px 8px 0 0;
        }
        
        .marketing-suite-modal-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .marketing-suite-modal-close {
            background: none;
            border: none;
            font-size: 32px;
            color: white;
            cursor: pointer;
            padding: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .marketing-suite-modal-close:hover {
            background-color: rgba(255,255,255,0.2);
            transform: rotate(90deg);
        }
        
        .marketing-suite-modal-body {
            padding: 0;
            height: calc(90vh - 80px);
            overflow-y: auto;
        }
        
        .marketing-suite-modal-body .marketing-suite-generator {
            margin: 0;
            padding: 30px;
            background: none;
            border-radius: 0;
            box-shadow: none;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            console.log('HPH Marketing Suite: DOM ready, setting up event handlers...');
            
            // Debug check for required variables
            if (typeof marketingSuiteAjax === 'undefined') {
                console.error('HPH Marketing Suite: marketingSuiteAjax not defined');
            } else {
                console.log('HPH Marketing Suite: AJAX config loaded', marketingSuiteAjax);
            }
            
            // Open marketing suite modal - Multiple selectors for reliability
            $(document).on('click', '#open-marketing-suite, .hph-action-btn[data-action="open-marketing-suite"], #create-campaign-btn, #create-first-campaign-btn', function(e) {
                e.preventDefault();
                console.log('HPH Marketing Suite: Modal open button clicked');
                
                // Show modal immediately
                $('#marketing-suite-modal').fadeIn(300);
                $('body').css('overflow', 'hidden');
                
                // Get AJAX URL with fallbacks
                var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
                if (typeof ajaxurl !== 'undefined') {
                    ajaxUrl = ajaxurl;
                } else if (typeof marketingSuiteAjax !== 'undefined' && marketingSuiteAjax.ajaxUrl) {
                    ajaxUrl = marketingSuiteAjax.ajaxUrl;
                }
                
                console.log('HPH Marketing Suite: Using AJAX URL:', ajaxUrl);
                
                // Load the marketing suite interface via AJAX
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'load_marketing_suite_interface',
                        nonce: '<?php echo wp_create_nonce('marketing_suite_nonce'); ?>'
                    },
                    success: function(response) {
                        console.log('HPH Marketing Suite: AJAX response received', response);
                        
                        if (response.success) {
                            $('#marketing-suite-container').html(response.data.html);
                            
                            // Initialize marketing suite functionality after loading
                            setTimeout(function() {
                                if (typeof window.initializeMarketingSuite === 'function') {
                                    console.log('HPH Marketing Suite: Initializing...');
                                    window.initializeMarketingSuite();
                                } else {
                                    console.warn('HPH Marketing Suite: initializeMarketingSuite function not found');
                                }
                            }, 500);
                        } else {
                            $('#marketing-suite-container').html('<div style="padding: 30px; text-align: center;"><p>Error loading Marketing Suite: ' + (response.data ? response.data.message || 'Unknown error' : 'Unknown error') + '</p></div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('HPH Marketing Suite: AJAX error', error, xhr);
                        $('#marketing-suite-container').html('<div style="padding: 30px; text-align: center;"><p>Connection error. Please check your internet connection and try again.</p><p>Error: ' + error + '</p><p>Status: ' + status + '</p></div>');
                    }
                });
            });
            
            // Close marketing suite modal
            $(document).on('click', '.marketing-suite-modal-close, .marketing-suite-modal', function(e) {
                if (e.target === this) {
                    console.log('HPH Marketing Suite: Closing modal');
                    $('#marketing-suite-modal').fadeOut(300);
                    $('body').css('overflow', 'auto');
                }
            });
            
            // Prevent modal content clicks from closing modal
            $(document).on('click', '.marketing-suite-modal-content', function(e) {
                e.stopPropagation();
            });
            
            // ESC key to close modal
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27 && $('#marketing-suite-modal').is(':visible')) {
                    console.log('HPH Marketing Suite: Closing modal with ESC key');
                    $('#marketing-suite-modal').fadeOut(300);
                    $('body').css('overflow', 'auto');
                }
            });
            
            console.log('HPH Marketing Suite: Event handlers registered');
        });
        </script>
        <?php
    }
    
    /**
     * Load marketing suite interface via AJAX
     */
    public function load_marketing_suite_interface(): void {
        try {
            // Enhanced security validation - check multiple nonce types for compatibility
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
            
            // Verify user permissions
            if (!current_user_can('read')) {
                wp_send_json_error([
                    'message' => __('You do not have permission to perform this action.', 'happy-place'),
                    'code' => 'insufficient_permissions'
                ], 403);
                return;
            }
            
            // Debug logging
            error_log('HPH Marketing Suite: Loading interface via AJAX...');
            
            // Ensure the marketing suite generator class is loaded
            $generator_file = HPH_PATH . 'includes/dashboard/class-marketing-suite-generator.php';
            if (file_exists($generator_file)) {
                require_once $generator_file;
                error_log('HPH Marketing Suite: Generator file loaded');
            } else {
                error_log('HPH Marketing Suite: Generator file not found at: ' . $generator_file);
                wp_send_json_error([
                    'message' => __('Marketing suite generator not found.', 'happy-place'),
                    'code' => 'generator_not_found'
                ], 500);
                return;
            }
            
            // Check if marketing suite generator class exists
            if (class_exists('HappyPlace\\Dashboard\\Marketing_Suite_Generator')) {
                error_log('HPH Marketing Suite: Generator class found, creating instance...');
                
                $generator = \HappyPlace\Dashboard\Marketing_Suite_Generator::get_instance();
                
                // Get listings and agents data
                $listings = $generator->get_listings();
                $agents = $generator->get_agents();
                
                error_log('HPH Marketing Suite: Found ' . count($listings) . ' listings and ' . count($agents) . ' agents');
                
                ob_start();
                $generator->render_generator_interface($listings, $agents);
                $html = ob_get_clean();
                
                error_log('HPH Marketing Suite: Interface rendered successfully, length: ' . strlen($html));
                
                wp_send_json_success([
                    'html' => $html,
                    'debug' => [
                        'listings_count' => count($listings),
                        'agents_count' => count($agents),
                        'html_length' => strlen($html)
                    ]
                ]);
                
            } else {
                error_log('HPH Marketing Suite: Generator class not found, using fallback');
                // Fallback: provide a simple interface or redirect message
                $html = '<div class="marketing-suite-fallback" style="padding: 30px; text-align: center;">
                    <h3>Marketing Suite Generator</h3>
                    <p>The Marketing Suite Generator is available in the admin menu.</p>
                    <a href="' . admin_url('admin.php?page=marketing-suite-generator') . '" class="button button-primary" target="_blank">
                        Open Marketing Suite Generator
                    </a>
                    <details style="margin-top: 20px; text-align: left;">
                        <summary>Debug Information</summary>
                        <p><strong>Generator file path:</strong> ' . $generator_file . '</p>
                        <p><strong>File exists:</strong> ' . (file_exists($generator_file) ? 'Yes' : 'No') . '</p>
                        <p><strong>Class exists:</strong> No</p>
                    </details>
                </div>';
                
                wp_send_json_success([
                    'html' => $html
                ]);
            }
            
        } catch (\Exception $e) {
            error_log('HPH Marketing Suite: Error in load_marketing_suite_interface: ' . $e->getMessage());
            wp_send_json_error([
                'message' => __('Error loading Marketing Suite: ', 'happy-place') . $e->getMessage(),
                'code' => 'exception_occurred'
            ], 500);
        }
    }    /**
     * Get section title
     */
    protected function get_section_title(): string {
        return __('Marketing Tools', 'happy-place');
    }
    
    /**
     * Render section content
     */
    public function render(array $args = []): void {
        $data = $this->get_section_data($args);
        ?>
        <div class="hph-section-modern">
            
            <!-- Section Header -->
            <div class="hph-section-header">
                <div class="hph-section-title">
                    <div class="title-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <h2 class="title-text"><?php echo esc_html($this->config['title']); ?></h2>
                </div>
                <p class="hph-section-subtitle">Create and manage your marketing campaigns</p>
                <div class="hph-section-actions">
                    <button type="button" class="hph-btn hph-btn--modern hph-btn--gradient" id="create-campaign-btn">
                        <i class="fas fa-plus"></i> <?php _e('New Campaign', 'happy-place'); ?>
                    </button>
                </div>
            </div>
            
            <!-- Marketing Tools Grid -->
            <div class="hph-section-body">
                <div class="hph-content-grid hph-content-grid--3-col">
                    
                    <!-- Flyer Generator -->
                    <div class="hph-content-card hph-content-card--interactive">
                        <div class="hph-content-header">
                            <div class="content-title">
                                <div class="title-icon">
                                    <i class="fas fa-image"></i>
                                </div>
                                <h3 class="title-text"><?php _e('Flyer Generator', 'happy-place'); ?></h3>
                            </div>
                            <p class="content-subtitle"><?php _e('Create professional marketing flyers', 'happy-place'); ?></p>
                        </div>
                        <div class="hph-content-body">
                            <p><?php _e('Generate stunning property flyers with professional templates and automatic listing data integration.', 'happy-place'); ?></p>
                        </div>
                        <div class="hph-content-footer">
                            <div class="footer-actions">
                                <button type="button" class="hph-btn hph-btn--modern hph-btn--gradient" id="open-marketing-suite">
                                    <i class="fas fa-magic"></i> <?php _e('Open Marketing Suite', 'happy-place'); ?>
                                </button>
                            </div>
                        </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Social Media -->
                    <div class="hph-content-card hph-content-card--interactive">
                        <div class="hph-content-header">
                            <div class="content-title">
                                <div class="title-icon">
                                    <i class="fas fa-share-alt"></i>
                                </div>
                                <h3 class="title-text"><?php _e('Social Media', 'happy-place'); ?></h3>
                            </div>
                            <p class="content-subtitle"><?php _e('Schedule and manage posts', 'happy-place'); ?></p>
                        </div>
                        <div class="hph-content-body">
                            <p><?php _e('Schedule posts across multiple platforms and track engagement to maximize your listings\'s reach.', 'happy-place'); ?></p>
                        </div>
                        <div class="hph-content-footer">
                            <div class="footer-actions">
                                <button type="button" class="hph-btn hph-btn--modern hph-btn--gradient" id="schedule-post-btn">
                                    <i class="fas fa-calendar-plus"></i> <?php _e('Schedule Post', 'happy-place'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Email Campaigns -->
                    <div class="hph-content-card hph-content-card--interactive">
                        <div class="hph-content-header">
                            <div class="content-title">
                                <div class="title-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <h3 class="title-text"><?php _e('Email Campaigns', 'happy-place'); ?></h3>
                            </div>
                            <p class="content-subtitle"><?php _e('Reach your lead database', 'happy-place'); ?></p>
                        </div>
                        <div class="hph-content-body">
                            <p><?php _e('Create targeted email campaigns to nurture leads and showcase your latest listings to potential buyers.', 'happy-place'); ?></p>
                        </div>
                        <div class="hph-content-footer">
                            <div class="footer-actions">
                                <button type="button" class="hph-btn hph-btn--modern hph-btn--gradient" id="create-email-btn">
                                    <i class="fas fa-paper-plane"></i> <?php _e('Create Campaign', 'happy-place'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                </div>
            
                <!-- Recent Campaigns -->
                <div class="hph-content-card">
                    <div class="hph-content-header">
                        <div class="content-title">
                            <div class="title-icon">
                                <i class="fas fa-history"></i>
                            </div>
                            <h3 class="title-text"><?php _e('Recent Campaigns', 'happy-place'); ?></h3>
                        </div>
                        <p class="content-subtitle"><?php _e('Track your marketing campaign performance', 'happy-place'); ?></p>
                    </div>
                    <div class="hph-content-body hph-content-body--compact">
                        <div id="marketing-campaigns-body">
                            <!-- Campaign data will be loaded via AJAX -->
                            <div class="hph-empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-bullhorn"></i>
                                </div>
                                <h4 class="empty-title"><?php _e('No campaigns yet', 'happy-place'); ?></h4>
                                <p class="empty-description"><?php _e('Create your first marketing campaign to reach more potential buyers.', 'happy-place'); ?></p>
                                <div class="empty-actions">
                                    <button type="button" class="hph-btn hph-btn--modern hph-btn--gradient" id="create-first-campaign-btn">
                                        <i class="fas fa-plus"></i> <?php _e('Create Campaign', 'happy-place'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        <?php
    }
    
    /**
     * Generate marketing flyer
     */
    public function generate_flyer(): void {
        try {
            // Security validation
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'hph_dashboard_nonce') && 
                !wp_verify_nonce($_POST['nonce'] ?? '', 'hph_ajax_nonce')) {
                wp_send_json_error([
                    'message' => __('Security check failed.', 'happy-place'),
                    'code' => 'invalid_nonce'
                ], 403);
                return;
            }
            
            if (!current_user_can('edit_posts')) {
                wp_send_json_error([
                    'message' => __('You do not have permission to perform this action.', 'happy-place'),
                    'code' => 'insufficient_permissions'
                ], 403);
                return;
            }
            
            $listing_id = absint($_POST['listing_id'] ?? 0);
            $template_id = sanitize_text_field($_POST['template_id'] ?? 'default');
            
            if (!$listing_id || get_post_type($listing_id) !== 'listing') {
                wp_send_json_error([
                    'message' => __('Invalid listing ID provided.', 'happy-place'),
                    'code' => 'invalid_listing'
                ], 400);
                return;
            }
            
            // Check if marketing suite generator class exists
            if (class_exists('HappyPlace\\Dashboard\\Marketing_Suite_Generator')) {
                wp_send_json_success([
                    'message' => __('Marketing suite available - use the full generator interface', 'happy-place'),
                    'redirect' => admin_url('admin.php?page=marketing-suite-generator'),
                    'listing_id' => $listing_id,
                    'template_id' => $template_id
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Marketing suite generator not available', 'happy-place'),
                    'code' => 'generator_unavailable'
                ], 500);
            }
            
        } catch (\Exception $e) {
            error_log('HPH Marketing Section: Error in generate_flyer: ' . $e->getMessage());
            wp_send_json_error([
                'message' => __('Error generating flyer.', 'happy-place'),
                'code' => 'exception_occurred'
            ], 500);
        }
    }
    
    /**
     * Schedule social media post
     */
    public function schedule_social_post(): void {
        try {
            // Security validation
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'hph_dashboard_nonce') && 
                !wp_verify_nonce($_POST['nonce'] ?? '', 'hph_ajax_nonce')) {
                wp_send_json_error([
                    'message' => __('Security check failed.', 'happy-place'),
                    'code' => 'invalid_nonce'
                ], 403);
                return;
            }
            
            if (!current_user_can('edit_posts')) {
                wp_send_json_error([
                    'message' => __('You do not have permission to perform this action.', 'happy-place'),
                    'code' => 'insufficient_permissions'
                ], 403);
                return;
            }
            
            $post_data = [
                'content' => sanitize_textarea_field($_POST['content'] ?? ''),
                'platforms' => array_map('sanitize_text_field', $_POST['platforms'] ?? []),
                'schedule_date' => sanitize_text_field($_POST['schedule_date'] ?? ''),
                'listing_id' => absint($_POST['listing_id'] ?? 0)
            ];
            
            // Validate required fields
            if (empty($post_data['content']) || empty($post_data['platforms'])) {
                wp_send_json_error([
                    'message' => __('Content and platforms are required', 'happy-place'),
                    'code' => 'missing_required_fields'
                ], 400);
                return;
            }
            
            // Validate listing ID if provided
            if ($post_data['listing_id'] && get_post_type($post_data['listing_id']) !== 'listing') {
                wp_send_json_error([
                    'message' => __('Invalid listing ID provided.', 'happy-place'),
                    'code' => 'invalid_listing'
                ], 400);
                return;
            }
            
            // TODO: Implement social media scheduling logic
            wp_send_json_success([
                'message' => __('Social media post scheduled successfully', 'happy-place'),
                'data' => $post_data
            ]);
            
        } catch (\Exception $e) {
            error_log('HPH Marketing Section: Error in schedule_social_post: ' . $e->getMessage());
            wp_send_json_error([
                'message' => __('Error scheduling social media post.', 'happy-place'),
                'code' => 'exception_occurred'
            ], 500);
        }
    }
    
    /**
     * Create email campaign
     */
    public function create_email_campaign(): void {
        try {
            // Security validation
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'hph_dashboard_nonce') && 
                !wp_verify_nonce($_POST['nonce'] ?? '', 'hph_ajax_nonce')) {
                wp_send_json_error([
                    'message' => __('Security check failed.', 'happy-place'),
                    'code' => 'invalid_nonce'
                ], 403);
                return;
            }
            
            if (!current_user_can('edit_posts')) {
                wp_send_json_error([
                    'message' => __('You do not have permission to perform this action.', 'happy-place'),
                    'code' => 'insufficient_permissions'
                ], 403);
                return;
            }
            
            $campaign_data = [
                'name' => sanitize_text_field($_POST['name'] ?? ''),
                'subject' => sanitize_text_field($_POST['subject'] ?? ''),
                'content' => wp_kses_post($_POST['content'] ?? ''),
                'recipients' => array_map('sanitize_email', $_POST['recipients'] ?? []),
                'template_id' => sanitize_text_field($_POST['template_id'] ?? 'default')
            ];
            
            // Validate required fields
            if (empty($campaign_data['name']) || empty($campaign_data['subject']) || empty($campaign_data['content'])) {
                wp_send_json_error([
                    'message' => __('Campaign name, subject, and content are required', 'happy-place'),
                    'code' => 'missing_required_fields'
                ], 400);
                return;
            }
            
            // Validate email addresses
            $invalid_emails = array_filter($campaign_data['recipients'], function($email) {
                return !is_email($email);
            });
            
            if (!empty($invalid_emails)) {
                wp_send_json_error([
                    'message' => __('Some email addresses are invalid.', 'happy-place'),
                    'code' => 'invalid_emails',
                    'invalid_emails' => $invalid_emails
                ], 400);
                return;
            }
            
            // TODO: Implement email campaign creation logic
            wp_send_json_success([
                'message' => __('Email campaign created successfully', 'happy-place'),
                'data' => $campaign_data
            ]);
            
        } catch (\Exception $e) {
            error_log('HPH Marketing Section: Error in create_email_campaign: ' . $e->getMessage());
            wp_send_json_error([
                'message' => __('Error creating email campaign.', 'happy-place'),
                'code' => 'exception_occurred'
            ], 500);
        }
    }
    
    /**
     * Get marketing templates
     */
    public function get_marketing_templates(): void {
        try {
            // Security validation
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'hph_dashboard_nonce') && 
                !wp_verify_nonce($_POST['nonce'] ?? '', 'hph_ajax_nonce')) {
                wp_send_json_error([
                    'message' => __('Security check failed.', 'happy-place'),
                    'code' => 'invalid_nonce'
                ], 403);
                return;
            }
            
            if (!current_user_can('edit_posts')) {
                wp_send_json_error([
                    'message' => __('You do not have permission to access this resource.', 'happy-place'),
                    'code' => 'insufficient_permissions'
                ], 403);
                return;
            }
            
            $template_type = sanitize_text_field($_POST['template_type'] ?? 'flyer');
            
            $templates = [
                'flyer' => [
                    ['id' => 'modern', 'name' => 'Modern Style', 'preview' => ''],
                    ['id' => 'classic', 'name' => 'Classic Style', 'preview' => ''],
                    ['id' => 'luxury', 'name' => 'Luxury Style', 'preview' => '']
                ],
                'email' => [
                    ['id' => 'newsletter', 'name' => 'Newsletter', 'preview' => ''],
                    ['id' => 'listing-alert', 'name' => 'New Listing Alert', 'preview' => ''],
                    ['id' => 'market-update', 'name' => 'Market Update', 'preview' => '']
                ]
            ];
            
            wp_send_json_success([
                'templates' => $templates[$template_type] ?? []
            ]);
            
        } catch (\Exception $e) {
            error_log('HPH Marketing Section: Error in get_marketing_templates: ' . $e->getMessage());
            wp_send_json_error([
                'message' => __('Error retrieving marketing templates.', 'happy-place'),
                'code' => 'exception_occurred'
            ], 500);
        }
    }
    
    /**
     * Get section data
     */
    protected function get_section_data(array $args = []): array {
        $user_id = get_current_user_id();
        
        return [
            'user_id' => $user_id,
            'campaigns' => $this->get_recent_campaigns($user_id),
            'stats' => $this->get_marketing_stats($user_id),
            'templates' => $this->get_available_templates()
        ];
    }
    
    /**
     * Get recent marketing campaigns
     */
    private function get_recent_campaigns(int $user_id): array {
        // TODO: Implement campaign retrieval
        return [];
    }
    
    /**
     * Get marketing statistics
     */
    private function get_marketing_stats(int $user_id): array {
        // TODO: Implement marketing stats
        return [
            'campaigns_sent' => 0,
            'open_rate' => 0,
            'click_rate' => 0,
            'flyers_generated' => 0
        ];
    }
    
    /**
     * Get available templates
     */
    private function get_available_templates(): array {
        return [
            'flyer' => 3,
            'email' => 3,
            'social' => 5
        ];
    }
}