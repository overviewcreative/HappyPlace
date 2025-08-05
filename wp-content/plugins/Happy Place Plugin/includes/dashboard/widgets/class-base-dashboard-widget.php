<?php
/**
 * Base Dashboard Widget - Abstract base class for dashboard widgets
 * 
 * Provides common functionality and structure for all dashboard widgets.
 * Handles data loading, caching, permissions, and standardized rendering.
 * 
 * @package HappyPlace
 * @subpackage Dashboard\Widgets
 */

namespace HappyPlace\Dashboard\Widgets;

if (!defined('ABSPATH')) {
    exit;
}

abstract class Base_Dashboard_Widget {
    
    /**
     * @var string Widget identifier
     */
    protected string $widget_id = '';
    
    /**
     * @var array Widget configuration
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
     * @var array Widget data cache
     */
    protected array $data_cache = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->widget_id = $this->get_widget_id();
        $this->config = $this->get_default_config();
        $this->init_hooks();
        $this->init_dependencies();
    }
    
    /**
     * Get widget identifier
     * Must be implemented by child classes
     *
     * @return string Widget ID
     */
    abstract protected function get_widget_id(): string;
    
    /**
     * Get widget title
     * Must be implemented by child classes
     *
     * @return string Widget title
     */
    abstract protected function get_widget_title(): string;
    
    /**
     * Render widget content
     * Must be implemented by child classes
     *
     * @param array $args Rendering arguments
     */
    abstract public function render(array $args = []): void;
    
    /**
     * Get default widget configuration
     *
     * @return array Default config
     */
    protected function get_default_config(): array {
        return [
            'title' => $this->get_widget_title(),
            'description' => '',
            'icon' => 'fas fa-widget',
            'sections' => ['overview'], // Which sections this widget appears in
            'priority' => 10,
            'capabilities' => ['read'],
            'size' => 'medium', // small, medium, large, full
            'collapsible' => true,
            'refreshable' => true,
            'configurable' => false,
            'cache_enabled' => true,
            'cache_duration' => 15 * MINUTE_IN_SECONDS,
            'ajax_enabled' => true,
            'css_class' => '',
            'settings' => []
        ];
    }
    
    /**
     * Initialize WordPress hooks
     */
    protected function init_hooks(): void {
        // Widget-specific AJAX handlers
        add_action("wp_ajax_hph_widget_{$this->widget_id}", [$this, 'handle_ajax_request']);
        
        // Widget data filters
        add_filter("hph_dashboard_widget_data_{$this->widget_id}", [$this, 'filter_widget_data'], 10, 2);
        
        // Widget settings
        add_filter("hph_dashboard_widget_settings_{$this->widget_id}", [$this, 'get_widget_settings'], 10, 2);
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
     * Check if current user can access this widget
     *
     * @param int $user_id User ID (optional)
     * @return bool True if user can access widget
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
        
        return $this->permissions->user_can_access_widget($user_id, $this->widget_id, $this->config);
    }
    
    /**
     * Get widget data
     *
     * @param array $args Data arguments
     * @return array Widget data
     */
    public function get_data(array $args = []): array {
        $cache_key = $this->get_cache_key($args);
        
        // Check memory cache first
        if (isset($this->data_cache[$cache_key])) {
            return $this->data_cache[$cache_key];
        }
        
        // Check WordPress cache
        if ($this->config['cache_enabled']) {
            $cached_data = wp_cache_get($cache_key, 'hph_dashboard_widgets');
            if ($cached_data !== false) {
                $this->data_cache[$cache_key] = $cached_data;
                return $cached_data;
            }
        }
        
        // Load fresh data
        $data = $this->load_widget_data($args);
        
        // Cache the data
        if ($this->config['cache_enabled']) {
            wp_cache_set($cache_key, $data, 'hph_dashboard_widgets', $this->config['cache_duration']);
        }
        
        $this->data_cache[$cache_key] = $data;
        
        return apply_filters("hph_dashboard_widget_data_{$this->widget_id}", $data, $args);
    }
    
    /**
     * Load widget data
     * Can be overridden by child classes for custom data loading
     *
     * @param array $args Data arguments
     * @return array Widget data
     */
    protected function load_widget_data(array $args = []): array {
        return [];
    }
    
    /**
     * Get cache key for widget data
     *
     * @param array $args Data arguments
     * @return string Cache key
     */
    protected function get_cache_key(array $args = []): string {
        $user_id = get_current_user_id();
        return "widget_{$this->widget_id}_{$user_id}_" . md5(serialize($args));
    }
    
    /**
     * Handle AJAX requests for this widget
     */
    public function handle_ajax_request(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        if (!$this->can_access()) {
            wp_send_json_error(['message' => __('Access denied', 'happy-place')]);
        }
        
        $action = sanitize_text_field($_POST['widget_action'] ?? '');
        $data = $_POST['data'] ?? [];
        
        switch ($action) {
            case 'refresh':
                $this->handle_refresh_request($data);
                break;
                
            case 'toggle_collapse':
                $this->handle_toggle_collapse($data);
                break;
                
            case 'update_settings':
                $this->handle_update_settings($data);
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
        $widget_data = $this->get_data($data);
        
        // Render fresh content
        ob_start();
        $this->render($data);
        $html = ob_get_clean();
        
        wp_send_json_success([
            'html' => $html,
            'data' => $widget_data
        ]);
    }
    
    /**
     * Handle toggle collapse
     *
     * @param array $data Request data
     */
    protected function handle_toggle_collapse(array $data): void {
        $collapsed = filter_var($data['collapsed'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $user_id = get_current_user_id();
        
        // Store user preference
        $user_widget_settings = get_user_meta($user_id, 'hph_widget_settings', true) ?: [];
        $user_widget_settings[$this->widget_id]['collapsed'] = $collapsed;
        update_user_meta($user_id, 'hph_widget_settings', $user_widget_settings);
        
        wp_send_json_success([
            'collapsed' => $collapsed,
            'message' => $collapsed ? __('Widget collapsed', 'happy-place') : __('Widget expanded', 'happy-place')
        ]);
    }
    
    /**
     * Handle update settings
     *
     * @param array $data Request data
     */
    protected function handle_update_settings(array $data): void {
        if (!$this->config['configurable']) {
            wp_send_json_error(['message' => __('Widget is not configurable', 'happy-place')]);
        }
        
        $settings = $data['settings'] ?? [];
        $user_id = get_current_user_id();
        
        // Validate settings
        $validated_settings = $this->validate_settings($settings);
        if (is_wp_error($validated_settings)) {
            wp_send_json_error(['message' => $validated_settings->get_error_message()]);
        }
        
        // Store user settings
        $user_widget_settings = get_user_meta($user_id, 'hph_widget_settings', true) ?: [];
        $user_widget_settings[$this->widget_id]['settings'] = $validated_settings;
        update_user_meta($user_id, 'hph_widget_settings', $user_widget_settings);
        
        // Clear cache
        $this->clear_cache();
        
        wp_send_json_success([
            'message' => __('Settings updated', 'happy-place'),
            'settings' => $validated_settings
        ]);
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
        do_action("hph_dashboard_widget_ajax_{$this->widget_id}_{$action}", $data);
    }
    
    /**
     * Validate widget settings
     *
     * @param array $settings Settings to validate
     * @return array|WP_Error Validated settings or error
     */
    protected function validate_settings(array $settings) {
        // Basic validation - child classes can override
        $allowed_settings = array_keys($this->config['settings']);
        $validated = [];
        
        foreach ($settings as $key => $value) {
            if (in_array($key, $allowed_settings)) {
                $validated[$key] = sanitize_text_field($value);
            }
        }
        
        return $validated;
    }
    
    /**
     * Get widget settings for current user
     *
     * @param array $settings Current settings
     * @param int $user_id User ID
     * @return array Widget settings
     */
    public function get_widget_settings(array $settings, int $user_id): array {
        $user_widget_settings = get_user_meta($user_id, 'hph_widget_settings', true) ?: [];
        $widget_settings = $user_widget_settings[$this->widget_id] ?? [];
        
        return array_merge($settings, $widget_settings);
    }
    
    /**
     * Filter widget data
     *
     * @param array $data Widget data
     * @param array $args Data arguments
     * @return array Filtered data
     */
    public function filter_widget_data(array $data, array $args): array {
        return $data;
    }
    
    /**
     * Render widget header
     *
     * @param array $args Rendering arguments
     */
    protected function render_widget_header(array $args = []): void {
        $title = $this->config['title'];
        $icon = $this->config['icon'];
        $user_settings = $this->get_user_settings();
        
        ?>
        <div class="hph-widget-header-content">
            <div class="hph-widget-info">
                <h3 class="hph-widget-title">
                    <i class="<?php echo esc_attr($icon); ?>"></i>
                    <?php echo esc_html($title); ?>
                </h3>
            </div>
            
            <div class="hph-widget-actions">
                <?php if ($this->config['refreshable']): ?>
                <button type="button" class="hph-widget-refresh" 
                        data-widget="<?php echo esc_attr($this->widget_id); ?>"
                        title="<?php esc_attr_e('Refresh widget', 'happy-place'); ?>">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <?php endif; ?>
                
                <?php if ($this->config['configurable']): ?>
                <button type="button" class="hph-widget-settings" 
                        data-widget="<?php echo esc_attr($this->widget_id); ?>"
                        title="<?php esc_attr_e('Widget settings', 'happy-place'); ?>">
                    <i class="fas fa-cog"></i>
                </button>
                <?php endif; ?>
                
                <?php if ($this->config['collapsible']): ?>
                <button type="button" class="hph-widget-toggle" 
                        data-widget="<?php echo esc_attr($this->widget_id); ?>"
                        data-collapsed="<?php echo esc_attr($user_settings['collapsed'] ?? false); ?>"
                        title="<?php esc_attr_e('Collapse/Expand', 'happy-place'); ?>">
                    <i class="fas <?php echo ($user_settings['collapsed'] ?? false) ? 'fa-chevron-down' : 'fa-chevron-up'; ?>"></i>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render widget footer
     *
     * @param array $args Rendering arguments
     */
    protected function render_widget_footer(array $args = []): void {
        // Check for widget-specific footer content
        $footer_content = apply_filters("hph_dashboard_widget_footer_{$this->widget_id}", '', $args);
        
        if (!empty($footer_content)) {
            echo '<div class="hph-widget-footer">' . $footer_content . '</div>';
        }
    }
    
    /**
     * Render empty state
     *
     * @param string $message Empty state message
     * @param array $actions Optional actions
     */
    protected function render_empty_state(string $message, array $actions = []): void {
        ?>
        <div class="hph-widget-empty-state">
            <div class="hph-empty-icon">
                <i class="<?php echo esc_attr($this->config['icon']); ?>"></i>
            </div>
            
            <p class="hph-empty-message">
                <?php echo esc_html($message); ?>
            </p>
            
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
        <div class="hph-widget-loading">
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
        <div class="hph-widget-error">
            <div class="hph-error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <p class="hph-error-message">
                <?php echo esc_html($message); ?>
            </p>
            <button type="button" class="button hph-widget-refresh" data-widget="<?php echo esc_attr($this->widget_id); ?>">
                <i class="fas fa-sync-alt"></i>
                <?php esc_html_e('Try Again', 'happy-place'); ?>
            </button>
        </div>
        <?php
    }
    
    /**
     * Get user settings for this widget
     *
     * @return array User settings
     */
    protected function get_user_settings(): array {
        $user_id = get_current_user_id();
        $user_widget_settings = get_user_meta($user_id, 'hph_widget_settings', true) ?: [];
        
        return $user_widget_settings[$this->widget_id] ?? [];
    }
    
    /**
     * Clear widget cache
     *
     * @param array $args Optional cache arguments
     */
    public function clear_cache(array $args = []): void {
        if (empty($args)) {
            // Clear all cache for this widget
            wp_cache_flush_group('hph_dashboard_widgets');
        } else {
            // Clear specific cache
            $cache_key = $this->get_cache_key($args);
            wp_cache_delete($cache_key, 'hph_dashboard_widgets');
        }
        
        // Clear memory cache
        $this->data_cache = [];
    }
    
    /**
     * Get widget configuration
     *
     * @return array Widget config
     */
    public function get_config(): array {
        return $this->config;
    }
    
    /**
     * Update widget configuration
     *
     * @param array $config New configuration
     */
    public function set_config(array $config): void {
        $this->config = wp_parse_args($config, $this->config);
    }
    
    /**
     * Get widget ID
     *
     * @return string Widget identifier
     */
    public function get_id(): string {
        return $this->widget_id;
    }
    
    /**
     * Check if widget is collapsible
     *
     * @return bool True if collapsible
     */
    public function is_collapsible(): bool {
        return $this->config['collapsible'] ?? true;
    }
    
    /**
     * Check if widget is refreshable
     *
     * @return bool True if refreshable
     */
    public function is_refreshable(): bool {
        return $this->config['refreshable'] ?? true;
    }
    
    /**
     * Check if widget is configurable
     *
     * @return bool True if configurable
     */
    public function is_configurable(): bool {
        return $this->config['configurable'] ?? false;
    }
    
    /**
     * Check if widget uses AJAX
     *
     * @return bool True if AJAX enabled
     */
    public function is_ajax_enabled(): bool {
        return $this->config['ajax_enabled'] ?? true;
    }
    
    /**
     * Check if widget uses caching
     *
     * @return bool True if cache enabled
     */
    public function is_cache_enabled(): bool {
        return $this->config['cache_enabled'] ?? true;
    }
    
    /**
     * Get widget size
     *
     * @return string Widget size (small, medium, large, full)
     */
    public function get_size(): string {
        return $this->config['size'] ?? 'medium';
    }
    
    /**
     * Get sections this widget appears in
     *
     * @return array Section IDs
     */
    public function get_sections(): array {
        return $this->config['sections'] ?? ['overview'];
    }
}