<?php
/**
 * Base Dashboard Section - Abstract base class for dashboard sections
 * 
 * Provides common functionality and structure for all dashboard sections.
 * Handles permissions, data loading, caching, and standardized rendering.
 * 
 * @package HappyPlace
 * @subpackage Dashboard\Sections
 */

namespace HappyPlace\Dashboard\Sections;

if (!defined('ABSPATH')) {
    exit;
}

abstract class Base_Dashboard_Section {
    
    /**
     * @var string Section identifier
     */
    protected string $section_id = '';
    
    /**
     * @var array Section configuration
     */
    protected array $config = [];
    
    /**
     * @var \HappyPlace\Dashboard\Dashboard_Data_Provider Data provider instance
     */
    protected $data_provider = null;
    
    /**
     * @var \HappyPlace\Dashboard\Dashboard_Permissions Permissions manager
     */
    protected $permissions = null;
    
    /**
     * @var array Section data cache
     */
    protected array $data_cache = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->section_id = $this->get_section_id();
        $this->config = $this->get_default_config();
        $this->init_hooks();
        $this->init_dependencies();
    }
    
    /**
     * Get section identifier
     * Must be implemented by child classes
     *
     * @return string Section ID
     */
    abstract protected function get_section_id(): string;
    
    /**
     * Get section title
     * Must be implemented by child classes
     *
     * @return string Section title
     */
    abstract protected function get_section_title(): string;
    
    /**
     * Render section content
     * Must be implemented by child classes
     *
     * @param array $args Rendering arguments
     */
    abstract public function render(array $args = []): void;
    
    /**
     * Get default section configuration
     *
     * @return array Default config
     */
    protected function get_default_config(): array {
        return [
            'title' => $this->get_section_title(),
            'description' => '',
            'icon' => 'fas fa-dashboard',
            'priority' => 10,
            'capabilities' => ['read'],
            'menu_position' => null,
            'parent_section' => null,
            'visible' => true,
            'ajax_enabled' => true,
            'cache_enabled' => true,
            'cache_duration' => 15 * MINUTE_IN_SECONDS,
            'widgets_enabled' => true,
            'max_widgets' => 6,
            'css_class' => ''
        ];
    }
    
    /**
     * Initialize WordPress hooks
     */
    protected function init_hooks(): void {
        // Section-specific AJAX handlers
        add_action("wp_ajax_hph_section_{$this->section_id}", [$this, 'handle_ajax_request']);
        
        // Section data filters
        add_filter("hph_dashboard_section_data_{$this->section_id}", [$this, 'filter_section_data'], 10, 2);
        
        // Section actions
        add_filter("hph_dashboard_section_actions_{$this->section_id}", [$this, 'get_section_actions'], 10, 2);
        
        // Widget registration for this section
        add_action('hph_dashboard_register_widgets', [$this, 'register_section_widgets']);
    }
    
    /**
     * Initialize dependencies
     */
    protected function init_dependencies(): void {
        // Get dashboard manager instance
        $dashboard_manager = \HappyPlace\Dashboard\Dashboard_Manager::get_instance();
        
        // Get data provider
        $this->data_provider = $dashboard_manager->get_data_provider();
        
        // Get permissions manager
        $this->permissions = $dashboard_manager->get_permissions();
    }
    
    /**
     * Check if current user can access this section
     *
     * @param int $user_id User ID (optional)
     * @return bool True if user can access section
     */
    public function can_access(int $user_id = 0): bool {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$this->permissions) {
            // Fallback to basic capability check
            $capabilities = $this->config['capabilities'] ?? ['read'];
            foreach ($capabilities as $capability) {
                if (!user_can($user_id, $capability)) {
                    return false;
                }
            }
            return true;
        }
        
        return $this->permissions->user_can_access_section($user_id, $this->section_id, $this->config);
    }
    
    /**
     * Get section data
     *
     * @param array $args Data arguments
     * @return array Section data
     */
    public function get_data(array $args = []): array {
        $cache_key = $this->get_cache_key($args);
        
        // Check memory cache first
        if (isset($this->data_cache[$cache_key])) {
            return $this->data_cache[$cache_key];
        }
        
        // Check WordPress cache
        if ($this->config['cache_enabled']) {
            $cached_data = wp_cache_get($cache_key, 'hph_dashboard_sections');
            if ($cached_data !== false) {
                $this->data_cache[$cache_key] = $cached_data;
                return $cached_data;
            }
        }
        
        // Load fresh data
        $data = $this->load_section_data($args);
        
        // Cache the data
        if ($this->config['cache_enabled']) {
            wp_cache_set($cache_key, $data, 'hph_dashboard_sections', $this->config['cache_duration']);
        }
        
        $this->data_cache[$cache_key] = $data;
        
        return apply_filters("hph_dashboard_section_data_{$this->section_id}", $data, $args);
    }
    
    /**
     * Load section data
     * Can be overridden by child classes for custom data loading
     *
     * @param array $args Data arguments
     * @return array Section data
     */
    protected function load_section_data(array $args = []): array {
        return [];
    }
    
    /**
     * Get cache key for section data
     *
     * @param array $args Data arguments
     * @return string Cache key
     */
    protected function get_cache_key(array $args = []): string {
        $user_id = get_current_user_id();
        return "section_{$this->section_id}_{$user_id}_" . md5(serialize($args));
    }
    
    /**
     * Handle AJAX requests for this section
     */
    public function handle_ajax_request(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        if (!$this->can_access()) {
            wp_send_json_error(['message' => __('Access denied', 'happy-place')]);
        }
        
        $action = sanitize_text_field($_POST['section_action'] ?? '');
        $data = $_POST['data'] ?? [];
        
        switch ($action) {
            case 'refresh':
                $this->handle_refresh_request($data);
                break;
                
            case 'load_widget':
                $this->handle_widget_request($data);
                break;
                
            default:
                $this->handle_custom_ajax_action($action, $data);
                break;
        }
        
        wp_send_json_error(['message' => __('Unknown action', 'happy-place')]);
    }
    
    /**
     * Handle refresh request
     *
     * @param array $data Request data
     */
    protected function handle_refresh_request(array $data): void {
        // Clear cache
        $this->clear_cache();
        
        // Get fresh data
        $section_data = $this->get_data($data);
        
        // Render fresh content
        ob_start();
        $this->render($data);
        $html = ob_get_clean();
        
        wp_send_json_success([
            'html' => $html,
            'data' => $section_data
        ]);
    }
    
    /**
     * Handle widget request
     *
     * @param array $data Request data
     */
    protected function handle_widget_request(array $data): void {
        $widget_id = sanitize_text_field($data['widget_id'] ?? '');
        
        if (empty($widget_id)) {
            wp_send_json_error(['message' => __('Widget ID required', 'happy-place')]);
        }
        
        // Use dashboard manager to render widget
        $dashboard_manager = \HappyPlace\Dashboard\Dashboard_Manager::get_instance();
        $html = $dashboard_manager->render_widget($widget_id, $data);
        
        wp_send_json_success(['html' => $html]);
    }
    
    /**
     * Handle custom AJAX actions
     * Can be overridden by child classes
     *
     * @param string $action Action name
     * @param array $data Request data
     */
    protected function handle_custom_ajax_action(string $action, array $data): void {
        // Default implementation - child classes can override
        do_action("hph_dashboard_section_ajax_{$this->section_id}_{$action}", $data);
    }
    
    /**
     * Get section actions
     *
     * @param array $actions Current actions
     * @param array $config Section config
     * @return array Section actions
     */
    public function get_section_actions(array $actions, array $config): array {
        // Add default refresh action
        $actions['refresh'] = [
            'title' => __('Refresh', 'happy-place'),
            'icon' => 'fas fa-sync-alt',
            'url' => '#',
            'class' => 'hph-section-refresh',
            'ajax' => true
        ];
        
        return $actions;
    }
    
    /**
     * Register widgets for this section
     * Can be overridden by child classes
     */
    public function register_section_widgets(): void {
        // Default implementation - child classes can override
    }
    
    /**
     * Filter section data
     *
     * @param array $data Section data
     * @param array $args Data arguments
     * @return array Filtered data
     */
    public function filter_section_data(array $data, array $args): array {
        return $data;
    }
    
    /**
     * Render section header
     *
     * @param array $args Rendering arguments
     */
    protected function render_section_header(array $args = []): void {
        $title = $this->config['title'];
        $icon = $this->config['icon'];
        $description = $this->config['description'];
        
        ?>
        <div class="hph-section-header-content">
            <div class="hph-section-info">
                <h2 class="hph-section-title">
                    <i class="<?php echo esc_attr($icon); ?>"></i>
                    <?php echo esc_html($title); ?>
                </h2>
                
                <?php if (!empty($description)): ?>
                <p class="hph-section-description">
                    <?php echo esc_html($description); ?>
                </p>
                <?php endif; ?>
            </div>
            
            <div class="hph-section-meta">
                <?php $this->render_section_meta($args); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render section meta information
     *
     * @param array $args Rendering arguments
     */
    protected function render_section_meta(array $args = []): void {
        // Show last updated time
        if ($this->config['cache_enabled']) {
            $cache_key = $this->get_cache_key($args);
            $cache_time = wp_cache_get($cache_key . '_time', 'hph_dashboard_sections');
            
            if ($cache_time) {
                printf(
                    '<span class="hph-section-updated">%s: %s</span>',
                    esc_html__('Updated', 'happy-place'),
                    esc_html(human_time_diff($cache_time))
                );
            }
        }
    }
    
    /**
     * Render section footer
     *
     * @param array $args Rendering arguments
     */
    protected function render_section_footer(array $args = []): void {
        // Default implementation - child classes can override
        do_action("hph_dashboard_section_footer_{$this->section_id}", $args);
    }
    
    /**
     * Render empty state
     *
     * @param string $message Empty state message
     * @param array $actions Optional actions
     */
    protected function render_empty_state(string $message, array $actions = []): void {
        ?>
        <div class="hph-section-empty-state">
            <div class="hph-empty-icon">
                <i class="<?php echo esc_attr($this->config['icon']); ?>"></i>
            </div>
            
            <h3 class="hph-empty-title">
                <?php echo esc_html($message); ?>
            </h3>
            
            <?php if (!empty($actions)): ?>
            <div class="hph-empty-actions">
                <?php foreach ($actions as $action): ?>
                    <a href="<?php echo esc_url($action['url']); ?>" 
                       class="<?php echo esc_attr($action['class'] ?? 'button'); ?>">
                        <?php if (!empty($action['icon'])): ?>
                            <i class="<?php echo esc_attr($action['icon']); ?>"></i>
                        <?php endif; ?>
                        <?php echo esc_html($action['title']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render loading state
     */
    protected function render_loading_state(): void {
        ?>
        <div class="hph-section-loading">
            <div class="hph-loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p><?php esc_html_e('Loading...', 'happy-place'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Render error state
     *
     * @param string $message Error message
     */
    protected function render_error_state(string $message): void {
        ?>
        <div class="hph-section-error">
            <div class="hph-error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="hph-error-title">
                <?php esc_html_e('Something went wrong', 'happy-place'); ?>
            </h3>
            <p class="hph-error-message">
                <?php echo esc_html($message); ?>
            </p>
            <button type="button" class="button hph-section-refresh" data-section="<?php echo esc_attr($this->section_id); ?>">
                <i class="fas fa-sync-alt"></i>
                <?php esc_html_e('Try Again', 'happy-place'); ?>
            </button>
        </div>
        <?php
    }
    
    /**
     * Clear section cache
     *
     * @param array $args Optional cache arguments
     */
    public function clear_cache(array $args = []): void {
        if (empty($args)) {
            // Clear all cache for this section
            wp_cache_flush_group('hph_dashboard_sections');
        } else {
            // Clear specific cache
            $cache_key = $this->get_cache_key($args);
            wp_cache_delete($cache_key, 'hph_dashboard_sections');
            wp_cache_delete($cache_key . '_time', 'hph_dashboard_sections');
        }
        
        // Clear memory cache
        $this->data_cache = [];
    }
    
    /**
     * Get section configuration
     *
     * @return array Section config
     */
    public function get_config(): array {
        return $this->config;
    }
    
    /**
     * Update section configuration
     *
     * @param array $config New configuration
     */
    public function set_config(array $config): void {
        $this->config = wp_parse_args($config, $this->config);
    }
    
    /**
     * Get section ID
     *
     * @return string Section identifier
     */
    public function get_id(): string {
        return $this->section_id;
    }
    
    /**
     * Check if section has widgets enabled
     *
     * @return bool True if widgets enabled
     */
    public function has_widgets_enabled(): bool {
        return $this->config['widgets_enabled'] ?? true;
    }
    
    /**
     * Get maximum number of widgets for this section
     *
     * @return int Max widgets
     */
    public function get_max_widgets(): int {
        return $this->config['max_widgets'] ?? 6;
    }
    
    /**
     * Check if section uses AJAX
     *
     * @return bool True if AJAX enabled
     */
    public function is_ajax_enabled(): bool {
        return $this->config['ajax_enabled'] ?? true;
    }
    
    /**
     * Check if section uses caching
     *
     * @return bool True if cache enabled
     */
    public function is_cache_enabled(): bool {
        return $this->config['cache_enabled'] ?? true;
    }
}