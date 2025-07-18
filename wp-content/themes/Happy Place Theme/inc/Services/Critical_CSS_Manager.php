<?php

namespace HappyPlace\Performance;

/**
 * Critical CSS Manager
 *
 * Generates and inlines critical CSS for above-the-fold content optimization
 *
 * @package HappyPlace\Performance
 * @since 2.0.0
 */
class Critical_CSS_Manager {
    
    /**
     * Singleton instance
     * @var Critical_CSS_Manager
     */
    private static $instance = null;
    
    /**
     * Critical CSS cache
     * @var array
     */
    private $critical_css_cache = [];
    
    /**
     * Performance metrics
     * @var array
     */
    private $performance_metrics = [];
    
    /**
     * Configuration options
     * @var array
     */
    private $config = [
        'viewport_width' => 1200,
        'viewport_height' => 800,
        'cache_duration' => 7 * DAY_IN_SECONDS,
        'enable_mobile_critical' => true,
        'mobile_viewport_width' => 375,
        'mobile_viewport_height' => 667,
        'defer_non_critical' => true,
        'optimize_fonts' => true
    ];
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_config();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('wp_head', [$this, 'inject_critical_css'], 1);
        add_action('wp_footer', [$this, 'load_non_critical_css'], 1);
        add_action('wp_enqueue_scripts', [$this, 'dequeue_non_critical_styles'], 100);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_ajax_generate_critical_css', [$this, 'ajax_generate_critical_css']);
        add_action('wp_ajax_clear_critical_cache', [$this, 'ajax_clear_cache']);
        
        // Auto-generate critical CSS for new posts
        add_action('save_post', [$this, 'schedule_critical_generation'], 10, 3);
        
        // Performance monitoring
        add_action('wp_footer', [$this, 'track_performance_metrics']);
    }
    
    /**
     * Load configuration from WordPress options
     */
    private function load_config() {
        $saved_config = get_option('hph_critical_css_config', []);
        $this->config = array_merge($this->config, $saved_config);
    }
    
    /**
     * Inject critical CSS into page head
     */
    public function inject_critical_css() {
        $page_type = $this->get_page_type();
        $device_type = $this->get_device_type();
        
        $critical_css = $this->get_critical_css($page_type, $device_type);
        
        if ($critical_css) {
            echo "<style id='critical-css' type='text/css'>\n" . $critical_css . "\n</style>\n";
            
            // Track performance
            $this->performance_metrics['critical_css_size'] = strlen($critical_css);
            $this->performance_metrics['critical_css_loaded'] = microtime(true);
        }
    }
    
    /**
     * Load non-critical CSS asynchronously
     */
    public function load_non_critical_css() {
        if (!$this->config['defer_non_critical']) {
            return;
        }
        
        $manifest = $this->get_webpack_manifest();
        
        if (isset($manifest['main.css'])) {
            $css_url = get_template_directory_uri() . '/assets/dist/' . $manifest['main.css'];
            
            echo "<script>
            (function() {
                var link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = '{$css_url}';
                link.media = 'print';
                link.onload = function() { this.media = 'all'; };
                document.head.appendChild(link);
                
                // Fallback for browsers that don't support onload
                setTimeout(function() {
                    if (link.media !== 'all') {
                        link.media = 'all';
                    }
                }, 300);
            })();
            </script>\n";
        }
    }
    
    /**
     * Dequeue non-critical styles when critical CSS is active
     */
    public function dequeue_non_critical_styles() {
        // TEMPORARILY DISABLED - Allow normal CSS loading during development
        // TODO: Re-enable after critical CSS is properly generated and tested
        return;
        
        if (!$this->config['defer_non_critical']) {
            return;
        }
        
        $page_type = $this->get_page_type();
        $device_type = $this->get_device_type();
        
        // Only dequeue if critical CSS is actually generated AND working
        if ($this->has_critical_css($page_type, $device_type)) {
            // Additional validation can be added here when re-enabling
            wp_dequeue_style('hph-core-styles');
        }
    }
    
    /**
     * Get critical CSS for page type and device
     */
    public function get_critical_css($page_type, $device_type = 'desktop') {
        $cache_key = "critical_css_{$page_type}_{$device_type}";
        
        // Check memory cache first
        if (isset($this->critical_css_cache[$cache_key])) {
            return $this->critical_css_cache[$cache_key];
        }
        
        // Check persistent cache
        $cached_css = get_transient($cache_key);
        if ($cached_css !== false) {
            $this->critical_css_cache[$cache_key] = $cached_css;
            return $cached_css;
        }
        
        // Generate if not cached
        return $this->generate_critical_css($page_type, $device_type);
    }
    
    /**
     * Generate critical CSS for page type
     */
    public function generate_critical_css($page_type, $device_type = 'desktop') {
        $viewport_width = $device_type === 'mobile' ? 
            $this->config['mobile_viewport_width'] : 
            $this->config['viewport_width'];
        
        $viewport_height = $device_type === 'mobile' ? 
            $this->config['mobile_viewport_height'] : 
            $this->config['viewport_height'];
        
        // Get sample URL for page type
        $sample_url = $this->get_sample_url($page_type);
        
        if (!$sample_url) {
            return '';
        }
        
        // Extract critical CSS rules
        $critical_css = $this->extract_critical_css_rules($page_type, $device_type);
        
        if ($critical_css) {
            // Cache the result
            $cache_key = "critical_css_{$page_type}_{$device_type}";
            set_transient($cache_key, $critical_css, $this->config['cache_duration']);
            $this->critical_css_cache[$cache_key] = $critical_css;
            
            // Store generation metadata
            $this->store_generation_metadata($page_type, $device_type, $critical_css);
        }
        
        return $critical_css;
    }
    
    /**
     * Extract critical CSS rules for page type
     */
    private function extract_critical_css_rules($page_type, $device_type) {
        // Define critical CSS rules based on page type
        $critical_rules = $this->get_critical_css_rules($page_type);
        
        // Get full CSS content
        $manifest = $this->get_webpack_manifest();
        $css_file = get_template_directory() . '/assets/dist/' . $manifest['main.css'];
        
        if (!file_exists($css_file)) {
            return '';
        }
        
        $full_css = file_get_contents($css_file);
        
        // Extract critical rules from full CSS
        $critical_css = $this->match_critical_rules($full_css, $critical_rules);
        
        // Optimize critical CSS
        $critical_css = $this->optimize_critical_css($critical_css, $device_type);
        
        return $critical_css;
    }
    
    /**
     * Get critical CSS rules for specific page types
     */
    private function get_critical_css_rules($page_type) {
        $rules = [
            'common' => [
                // Reset and base styles
                '*', 'html', 'body',
                // Header and navigation
                'header', '.site-header', '.main-nav', '.nav-menu',
                // Hero sections
                '.hero', '.banner', '.intro',
                // Critical typography
                'h1', 'h2', '.page-title', '.entry-title',
                // Layout containers
                '.container', '.wrapper', '.main-content',
                // CSS variables (always critical)
                ':root'
            ],
            'home' => [
                '.hero-section', '.featured-listings', '.search-form',
                '.listing-grid', '.listing-card', '.cta-section'
            ],
            'listing' => [
                '.listing-header', '.listing-gallery', '.listing-details',
                '.property-info', '.agent-info', '.listing-description'
            ],
            'listings' => [
                '.listings-header', '.filters-bar', '.search-results',
                '.pagination', '.listing-grid', '.listing-card'
            ],
            'agent' => [
                '.agent-header', '.agent-bio', '.agent-listings',
                '.contact-form', '.agent-stats'
            ],
            'contact' => [
                '.contact-header', '.contact-form', '.contact-info',
                '.map-container'
            ]
        ];
        
        $page_rules = isset($rules[$page_type]) ? $rules[$page_type] : [];
        
        return array_merge($rules['common'], $page_rules);
    }
    
    /**
     * Match critical rules in CSS content
     */
    private function match_critical_rules($css_content, $critical_selectors) {
        $critical_css = '';
        
        // Extract CSS variables first (always critical)
        if (preg_match('/:root\s*\{[^}]+\}/s', $css_content, $matches)) {
            $critical_css .= $matches[0] . "\n";
        }
        
        // Process each critical selector
        foreach ($critical_selectors as $selector) {
            $pattern = $this->create_css_selector_pattern($selector);
            
            if (preg_match_all($pattern, $css_content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $critical_css .= $match[0] . "\n";
                }
            }
        }
        
        return $critical_css;
    }
    
    /**
     * Create regex pattern for CSS selector
     */
    private function create_css_selector_pattern($selector) {
        $escaped_selector = preg_quote($selector, '/');
        
        // Handle different selector types
        if ($selector === '*') {
            return '/\*\s*\{[^}]+\}/s';
        } elseif (strpos($selector, ':root') !== false) {
            return '/:root\s*\{[^}]+\}/s';
        } elseif (strpos($selector, '.') === 0) {
            // Class selector
            $class = substr($escaped_selector, 2); // Remove \.
            return '/\.' . $class . '(?:[,\s\.\#\:\[\>]|$)[^{]*\{[^}]+\}/s';
        } elseif (strpos($selector, '#') === 0) {
            // ID selector
            $id = substr($escaped_selector, 2); // Remove \#
            return '/\#' . $id . '(?:[,\s\.\#\:\[\>]|$)[^{]*\{[^}]+\}/s';
        } else {
            // Element selector
            return '/' . $escaped_selector . '(?:[,\s\.\#\:\[\>]|$)[^{]*\{[^}]+\}/s';
        }
    }
    
    /**
     * Optimize critical CSS content
     */
    private function optimize_critical_css($css, $device_type) {
        // Remove comments
        $css = preg_replace('/\/\*.*?\*\//s', '', $css);
        
        // Remove extra whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        $css = str_replace(['; ', ' {', '{ ', ' }'], [';', '{', '{', '}'], $css);
        
        // Remove media queries for opposite device type
        if ($device_type === 'desktop') {
            $css = preg_replace('/@media[^{]*\(max-width:\s*768px\)[^{]*\{[^{}]*\{[^}]*\}[^}]*\}/i', '', $css);
        } elseif ($device_type === 'mobile') {
            $css = preg_replace('/@media[^{]*\(min-width:\s*769px\)[^{]*\{[^{}]*\{[^}]*\}[^}]*\}/i', '', $css);
        }
        
        // Optimize font loading if enabled
        if ($this->config['optimize_fonts']) {
            $css = $this->optimize_font_loading($css);
        }
        
        return trim($css);
    }
    
    /**
     * Optimize font loading in critical CSS
     */
    private function optimize_font_loading($css) {
        // Add font-display: swap to @font-face rules
        $css = preg_replace('/(@font-face\s*\{[^}]*)(font-display\s*:\s*[^;]+;)?([^}]*\})/i', 
            '$1font-display:swap;$3', $css);
        
        return $css;
    }
    
    /**
     * Get sample URL for page type
     */
    private function get_sample_url($page_type) {
        switch ($page_type) {
            case 'home':
                return home_url('/');
            
            case 'listing':
                $sample_listing = get_posts([
                    'post_type' => 'listing',
                    'posts_per_page' => 1,
                    'post_status' => 'publish'
                ]);
                return $sample_listing ? get_permalink($sample_listing[0]) : null;
            
            case 'listings':
                return get_post_type_archive_link('listing');
            
            case 'agent':
                $sample_agent = get_posts([
                    'post_type' => 'agent',
                    'posts_per_page' => 1,
                    'post_status' => 'publish'
                ]);
                return $sample_agent ? get_permalink($sample_agent[0]) : null;
            
            default:
                return home_url('/');
        }
    }
    
    /**
     * Get current page type
     */
    public function get_page_type() {
        if (is_home() || is_front_page()) {
            return 'home';
        } elseif (is_singular('listing')) {
            return 'listing';
        } elseif (is_post_type_archive('listing') || is_tax('listing_category') || is_tax('listing_location')) {
            return 'listings';
        } elseif (is_singular('agent')) {
            return 'agent';
        } elseif (is_page('contact')) {
            return 'contact';
        } else {
            return 'page';
        }
    }
    
    /**
     * Get device type based on user agent
     */
    public function get_device_type() {
        if (!$this->config['enable_mobile_critical']) {
            return 'desktop';
        }
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $mobile_patterns = [
            '/Mobile/i', '/Android/i', '/iPhone/i', '/iPad/i',
            '/Windows Phone/i', '/BlackBerry/i'
        ];
        
        foreach ($mobile_patterns as $pattern) {
            if (preg_match($pattern, $user_agent)) {
                return 'mobile';
            }
        }
        
        return 'desktop';
    }
    
    /**
     * Check if critical CSS exists for page type
     */
    public function has_critical_css($page_type, $device_type = 'desktop') {
        $cache_key = "critical_css_{$page_type}_{$device_type}";
        return get_transient($cache_key) !== false;
    }
    
    /**
     * Clear critical CSS cache
     */
    public function clear_cache($page_type = null, $device_type = null) {
        if ($page_type && $device_type) {
            // Clear specific cache
            $cache_key = "critical_css_{$page_type}_{$device_type}";
            delete_transient($cache_key);
            unset($this->critical_css_cache[$cache_key]);
        } else {
            // Clear all critical CSS cache
            global $wpdb;
            
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_critical_css_%'");
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_critical_css_%'");
            
            $this->critical_css_cache = [];
        }
        
        // Clear generation metadata
        delete_option('hph_critical_css_metadata');
    }
    
    /**
     * Store generation metadata
     */
    private function store_generation_metadata($page_type, $device_type, $css) {
        $metadata = get_option('hph_critical_css_metadata', []);
        
        $metadata["{$page_type}_{$device_type}"] = [
            'generated_at' => current_time('timestamp'),
            'size' => strlen($css),
            'rules_count' => substr_count($css, '}'),
            'page_type' => $page_type,
            'device_type' => $device_type
        ];
        
        update_option('hph_critical_css_metadata', $metadata);
    }
    
    /**
     * Get webpack manifest
     */
    private function get_webpack_manifest() {
        static $manifest = null;
        
        if ($manifest === null) {
            $manifest_path = get_template_directory() . '/assets/dist/manifest.json';
            
            if (file_exists($manifest_path)) {
                $manifest_content = file_get_contents($manifest_path);
                $manifest = json_decode($manifest_content, true) ?: [];
            } else {
                $manifest = [];
                error_log('HPH Critical CSS: Webpack manifest not found at ' . $manifest_path);
            }
        }
        
        return $manifest;
    }
    
    /**
     * Schedule critical CSS generation for new content
     */
    public function schedule_critical_generation($post_id, $post, $update) {
        if (!$update && in_array($post->post_type, ['listing', 'agent', 'page'])) {
            wp_schedule_single_event(time() + 60, 'hph_generate_critical_css', [$post->post_type]);
        }
    }
    
    /**
     * Track performance metrics
     */
    public function track_performance_metrics() {
        if (!empty($this->performance_metrics)) {
            $metrics = $this->performance_metrics;
            $metrics['page_type'] = $this->get_page_type();
            $metrics['device_type'] = $this->get_device_type();
            $metrics['timestamp'] = current_time('timestamp');
            
            // Store metrics for analysis (sample 1% of requests to avoid overload)
            if (rand(1, 100) === 1) {
                $stored_metrics = get_option('hph_critical_css_metrics', []);
                $stored_metrics[] = $metrics;
                
                // Keep only last 100 samples
                if (count($stored_metrics) > 100) {
                    $stored_metrics = array_slice($stored_metrics, -100);
                }
                
                update_option('hph_critical_css_metrics', $stored_metrics);
            }
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            'Critical CSS Manager',
            'Critical CSS',
            'manage_options',
            'hph-critical-css',
            [$this, 'render_admin_page']
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $metadata = get_option('hph_critical_css_metadata', []);
        $metrics = get_option('hph_critical_css_metrics', []);
        
        ?>
        <div class="wrap">
            <h1>Critical CSS Manager</h1>
            
            <div class="critical-css-dashboard">
                <div class="dashboard-section">
                    <h2>Generated Critical CSS</h2>
                    
                    <?php if (!empty($metadata)): ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Page Type</th>
                                    <th>Device</th>
                                    <th>Size</th>
                                    <th>Rules</th>
                                    <th>Generated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($metadata as $key => $data): ?>
                                    <tr>
                                        <td><?php echo esc_html(ucfirst($data['page_type'])); ?></td>
                                        <td><?php echo esc_html(ucfirst($data['device_type'])); ?></td>
                                        <td><?php echo esc_html(size_format($data['size'])); ?></td>
                                        <td><?php echo esc_html($data['rules_count']); ?></td>
                                        <td><?php echo esc_html(human_time_diff($data['generated_at'])) . ' ago'; ?></td>
                                        <td>
                                            <button class="button regenerate-critical" 
                                                    data-page-type="<?php echo esc_attr($data['page_type']); ?>"
                                                    data-device-type="<?php echo esc_attr($data['device_type']); ?>">
                                                Regenerate
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No critical CSS generated yet.</p>
                    <?php endif; ?>
                </div>
                
                <div class="dashboard-section">
                    <h2>Actions</h2>
                    <p>
                        <button class="button button-primary" id="generate-all-critical">
                            Generate All Critical CSS
                        </button>
                        <button class="button" id="clear-critical-cache">
                            Clear Cache
                        </button>
                    </p>
                </div>
                
                <?php if (!empty($metrics)): ?>
                    <div class="dashboard-section">
                        <h2>Performance Metrics</h2>
                        <p>Average critical CSS size: <?php echo esc_html(size_format(array_sum(array_column($metrics, 'critical_css_size')) / count($metrics))); ?></p>
                        <p>Samples collected: <?php echo esc_html(count($metrics)); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#generate-all-critical').on('click', function() {
                $(this).prop('disabled', true).text('Generating...');
                
                $.post(ajaxurl, {
                    action: 'generate_critical_css',
                    nonce: '<?php echo wp_create_nonce('critical_css_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });
            
            $('#clear-critical-cache').on('click', function() {
                if (confirm('Clear all critical CSS cache?')) {
                    $.post(ajaxurl, {
                        action: 'clear_critical_cache',
                        nonce: '<?php echo wp_create_nonce('critical_css_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for generating critical CSS
     */
    public function ajax_generate_critical_css() {
        if (!wp_verify_nonce($_POST['nonce'], 'critical_css_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }
        
        $page_types = ['home', 'listing', 'listings', 'agent', 'contact'];
        $device_types = $this->config['enable_mobile_critical'] ? ['desktop', 'mobile'] : ['desktop'];
        
        $generated = 0;
        
        foreach ($page_types as $page_type) {
            foreach ($device_types as $device_type) {
                $css = $this->generate_critical_css($page_type, $device_type);
                if ($css) {
                    $generated++;
                }
            }
        }
        
        wp_send_json_success("Generated critical CSS for {$generated} combinations");
    }
    
    /**
     * AJAX handler for clearing cache
     */
    public function ajax_clear_cache() {
        if (!wp_verify_nonce($_POST['nonce'], 'critical_css_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }
        
        $this->clear_cache();
        wp_send_json_success('Cache cleared successfully');
    }
}
