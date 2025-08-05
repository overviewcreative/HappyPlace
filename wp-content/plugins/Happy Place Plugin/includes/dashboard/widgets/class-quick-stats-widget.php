<?php
/**
 * Quick Stats Widget - Display key performance metrics
 * 
 * Shows important statistics like listings count, sales, leads, etc.
 * in a compact, easy-to-read format with trend indicators.
 * 
 * @package HappyPlace
 * @subpackage Dashboard\Widgets
 */

namespace HappyPlace\Dashboard\Widgets;

if (!defined('ABSPATH')) {
    exit;
}

class Quick_Stats_Widget extends Base_Dashboard_Widget {
    
    /**
     * Get widget identifier
     *
     * @return string Widget ID
     */
    protected function get_widget_id(): string {
        return 'quick-stats';
    }
    
    /**
     * Get widget title
     *
     * @return string Widget title
     */
    protected function get_widget_title(): string {
        return __('Quick Stats', 'happy-place');
    }
    
    /**
     * Get default widget configuration
     *
     * @return array Default config
     */
    protected function get_default_config(): array {
        $config = parent::get_default_config();
        
        return array_merge($config, [
            'description' => __('Key performance metrics at a glance', 'happy-place'),
            'icon' => 'fas fa-chart-bar',
            'sections' => ['overview', 'analytics'],
            'size' => 'large',
            'configurable' => true,
            'settings' => [
                'show_trends' => true,
                'time_period' => '30_days',
                'stats_to_show' => ['listings', 'sales', 'leads', 'revenue']
            ]
        ]);
    }
    
    /**
     * Load widget data
     *
     * @param array $args Data arguments
     * @return array Widget data
     */
    protected function load_widget_data(array $args = []): array {
        if (!$this->data_provider) {
            return $this->get_fallback_stats();
        }
        
        $user_id = get_current_user_id();
        $user_settings = $this->get_user_settings();
        $stats_settings = $user_settings['settings'] ?? $this->config['settings'];
        
        // Get comprehensive stats
        $stats = $this->data_provider->get_user_stats($user_id);
        
        // Add trending data if enabled
        if ($stats_settings['show_trends'] ?? true) {
            $stats['trends'] = $this->calculate_trends($user_id, $stats_settings['time_period'] ?? '30_days');
        }
        
        return [
            'stats' => $stats,
            'settings' => $stats_settings,
            'formatted_stats' => $this->format_stats($stats),
            'chart_data' => $this->get_chart_data($user_id, $stats_settings['time_period'] ?? '30_days')
        ];
    }
    
    /**
     * Render widget content
     *
     * @param array $args Rendering arguments
     */
    public function render(array $args = []): void {
        // Check access
        if (!$this->can_access()) {
            $this->render_error_state(__('You do not have permission to view this widget.', 'happy-place'));
            return;
        }
        
        // Get widget data
        $data = $this->get_data($args);
        
        if (empty($data['stats'])) {
            $this->render_loading_state();
            return;
        }
        
        $stats = $data['stats'];
        $formatted_stats = $data['formatted_stats'];
        $settings = $data['settings'];
        $chart_data = $data['chart_data'];
        
        ?>
        <div class="hph-quick-stats-widget">
            
            <div class="hph-stats-grid">
                
                <?php if (in_array('listings', $settings['stats_to_show'] ?? [])): ?>
                <div class="hph-stat-item hph-stat-listings">
                    <div class="hph-stat-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="hph-stat-content">
                        <div class="hph-stat-number"><?php echo esc_html($formatted_stats['total_listings']); ?></div>
                        <div class="hph-stat-label"><?php esc_html_e('Total Listings', 'happy-place'); ?></div>
                        <?php if ($settings['show_trends'] && isset($stats['trends']['listings'])): ?>
                            <div class="hph-stat-trend <?php echo esc_attr($stats['trends']['listings']['direction']); ?>">
                                <i class="fas fa-<?php echo $stats['trends']['listings']['direction'] === 'up' ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                <?php echo esc_html($stats['trends']['listings']['percentage']); ?>%
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="hph-stat-detail">
                        <?php 
                        printf(
                            esc_html__('%d Active', 'happy-place'), 
                            $stats['active_listings'] ?? 0
                        ); 
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (in_array('sales', $settings['stats_to_show'] ?? [])): ?>
                <div class="hph-stat-item hph-stat-sales">
                    <div class="hph-stat-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div class="hph-stat-content">
                        <div class="hph-stat-number"><?php echo esc_html($formatted_stats['sold_listings']); ?></div>
                        <div class="hph-stat-label"><?php esc_html_e('Sales', 'happy-place'); ?></div>
                        <?php if ($settings['show_trends'] && isset($stats['trends']['sales'])): ?>
                            <div class="hph-stat-trend <?php echo esc_attr($stats['trends']['sales']['direction']); ?>">
                                <i class="fas fa-<?php echo $stats['trends']['sales']['direction'] === 'up' ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                <?php echo esc_html($stats['trends']['sales']['percentage']); ?>%
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="hph-stat-detail">
                        <?php esc_html_e('This Month', 'happy-place'); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (in_array('leads', $settings['stats_to_show'] ?? [])): ?>
                <div class="hph-stat-item hph-stat-leads">
                    <div class="hph-stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="hph-stat-content">
                        <div class="hph-stat-number"><?php echo esc_html($formatted_stats['total_leads']); ?></div>
                        <div class="hph-stat-label"><?php esc_html_e('Leads', 'happy-place'); ?></div>
                        <?php if ($settings['show_trends'] && isset($stats['trends']['leads'])): ?>
                            <div class="hph-stat-trend <?php echo esc_attr($stats['trends']['leads']['direction']); ?>">
                                <i class="fas fa-<?php echo $stats['trends']['leads']['direction'] === 'up' ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                <?php echo esc_html($stats['trends']['leads']['percentage']); ?>%
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="hph-stat-detail">
                        <?php 
                        printf(
                            esc_html__('%d Active', 'happy-place'), 
                            $stats['active_leads'] ?? 0
                        ); 
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (in_array('revenue', $settings['stats_to_show'] ?? [])): ?>
                <div class="hph-stat-item hph-stat-revenue">
                    <div class="hph-stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="hph-stat-content">
                        <div class="hph-stat-number"><?php echo esc_html($formatted_stats['total_commission']); ?></div>
                        <div class="hph-stat-label"><?php esc_html_e('Commission', 'happy-place'); ?></div>
                        <?php if ($settings['show_trends'] && isset($stats['trends']['revenue'])): ?>
                            <div class="hph-stat-trend <?php echo esc_attr($stats['trends']['revenue']['direction']); ?>">
                                <i class="fas fa-<?php echo $stats['trends']['revenue']['direction'] === 'up' ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                <?php echo esc_html($stats['trends']['revenue']['percentage']); ?>%
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="hph-stat-detail">
                        <?php esc_html_e('YTD', 'happy-place'); ?>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
            
            <?php if (!empty($chart_data) && ($settings['show_chart'] ?? false)): ?>
            <div class="hph-stats-chart">
                <canvas id="hph-stats-chart-<?php echo esc_attr($this->widget_id); ?>" 
                        data-chart='<?php echo esc_attr(wp_json_encode($chart_data)); ?>'></canvas>
            </div>
            <?php endif; ?>
            
            <div class="hph-stats-actions">
                <a href="#analytics" class="hph-view-detailed-link">
                    <?php esc_html_e('View Detailed Analytics', 'happy-place'); ?>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
        </div>
        <?php
    }
    
    /**
     * Format statistics for display
     *
     * @param array $stats Raw statistics
     * @return array Formatted statistics
     */
    private function format_stats(array $stats): array {
        return [
            'total_listings' => number_format($stats['total_listings'] ?? 0),
            'active_listings' => number_format($stats['active_listings'] ?? 0),
            'sold_listings' => number_format($stats['sold_listings'] ?? 0),
            'pending_listings' => number_format($stats['pending_listings'] ?? 0),
            'total_leads' => number_format($stats['total_leads'] ?? 0),
            'active_leads' => number_format($stats['active_leads'] ?? 0),
            'converted_leads' => number_format($stats['converted_leads'] ?? 0),
            'total_commission' => '$' . number_format($stats['total_commission'] ?? 0),
            'scheduled_showings' => number_format($stats['scheduled_showings'] ?? 0)
        ];
    }
    
    /**
     * Calculate trend data
     *
     * @param int $user_id User ID
     * @param string $time_period Time period for comparison
     * @return array Trend data
     */
    private function calculate_trends(int $user_id, string $time_period): array {
        // This would integrate with historical data
        // For now, return placeholder trends
        return [
            'listings' => [
                'direction' => 'up',
                'percentage' => '12.5'
            ],
            'sales' => [
                'direction' => 'up',
                'percentage' => '8.3'
            ],
            'leads' => [
                'direction' => 'down',
                'percentage' => '2.1'
            ],
            'revenue' => [
                'direction' => 'up',
                'percentage' => '15.7'
            ]
        ];
    }
    
    /**
     * Get chart data for visualization
     *
     * @param int $user_id User ID
     * @param string $time_period Time period
     * @return array Chart data
     */
    private function get_chart_data(int $user_id, string $time_period): array {
        // This would generate chart data based on historical records
        // For now, return placeholder data
        return [
            'type' => 'line',
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'datasets' => [
                [
                    'label' => 'Listings',
                    'data' => [12, 19, 3, 5, 2, 3],
                    'borderColor' => '#3498db',
                    'backgroundColor' => 'rgba(52, 152, 219, 0.1)'
                ],
                [
                    'label' => 'Sales',
                    'data' => [2, 3, 20, 5, 1, 4],
                    'borderColor' => '#2ecc71',
                    'backgroundColor' => 'rgba(46, 204, 113, 0.1)'
                ]
            ]
        ];
    }
    
    /**
     * Get fallback statistics when data provider is unavailable
     *
     * @return array Fallback stats
     */
    private function get_fallback_stats(): array {
        return [
            'total_listings' => 0,
            'active_listings' => 0,
            'sold_listings' => 0,
            'pending_listings' => 0,
            'total_leads' => 0,
            'active_leads' => 0,
            'converted_leads' => 0,
            'total_commission' => 0,
            'scheduled_showings' => 0
        ];
    }
    
    /**
     * Handle custom AJAX actions
     *
     * @param string $action Action name
     * @param array $data Request data
     */
    protected function handle_custom_ajax_action(string $action, array $data): void {
        switch ($action) {
            case 'update_time_period':
                $this->handle_update_time_period($data);
                break;
                
            case 'toggle_stat':
                $this->handle_toggle_stat($data);
                break;
                
            default:
                parent::handle_custom_ajax_action($action, $data);
                break;
        }
    }
    
    /**
     * Handle update time period
     *
     * @param array $data Request data
     */
    private function handle_update_time_period(array $data): void {
        $time_period = sanitize_text_field($data['time_period'] ?? '30_days');
        $allowed_periods = ['7_days', '30_days', '90_days', '1_year'];
        
        if (!in_array($time_period, $allowed_periods)) {
            wp_send_json_error(['message' => __('Invalid time period', 'happy-place')]);
        }
        
        // Update user settings
        $user_id = get_current_user_id();
        $user_widget_settings = get_user_meta($user_id, 'hph_widget_settings', true) ?: [];
        $user_widget_settings[$this->widget_id]['settings']['time_period'] = $time_period;
        update_user_meta($user_id, 'hph_widget_settings', $user_widget_settings);
        
        // Clear cache and return fresh data
        $this->clear_cache();
        $fresh_data = $this->get_data(['time_period' => $time_period]);
        
        wp_send_json_success([
            'message' => __('Time period updated', 'happy-place'),
            'data' => $fresh_data
        ]);
    }
    
    /**
     * Handle toggle stat display
     *
     * @param array $data Request data
     */
    private function handle_toggle_stat(array $data): void {
        $stat_type = sanitize_text_field($data['stat_type'] ?? '');
        $enabled = filter_var($data['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);
        
        $allowed_stats = ['listings', 'sales', 'leads', 'revenue'];
        if (!in_array($stat_type, $allowed_stats)) {
            wp_send_json_error(['message' => __('Invalid stat type', 'happy-place')]);
        }
        
        // Update user settings
        $user_id = get_current_user_id();
        $user_widget_settings = get_user_meta($user_id, 'hph_widget_settings', true) ?: [];
        
        if (!isset($user_widget_settings[$this->widget_id]['settings']['stats_to_show'])) {
            $user_widget_settings[$this->widget_id]['settings']['stats_to_show'] = $this->config['settings']['stats_to_show'];
        }
        
        $stats_to_show = $user_widget_settings[$this->widget_id]['settings']['stats_to_show'];
        
        if ($enabled && !in_array($stat_type, $stats_to_show)) {
            $stats_to_show[] = $stat_type;
        } elseif (!$enabled && in_array($stat_type, $stats_to_show)) {
            $stats_to_show = array_diff($stats_to_show, [$stat_type]);
        }
        
        $user_widget_settings[$this->widget_id]['settings']['stats_to_show'] = array_values($stats_to_show);
        update_user_meta($user_id, 'hph_widget_settings', $user_widget_settings);
        
        wp_send_json_success([
            'message' => __('Stat display updated', 'happy-place'),
            'stats_to_show' => $stats_to_show
        ]);
    }
    
    /**
     * Validate widget settings
     *
     * @param array $settings Settings to validate
     * @return array|WP_Error Validated settings or error
     */
    protected function validate_settings(array $settings) {
        $validated = [];
        
        // Validate time period
        if (isset($settings['time_period'])) {
            $allowed_periods = ['7_days', '30_days', '90_days', '1_year'];
            if (in_array($settings['time_period'], $allowed_periods)) {
                $validated['time_period'] = $settings['time_period'];
            }
        }
        
        // Validate show trends
        if (isset($settings['show_trends'])) {
            $validated['show_trends'] = filter_var($settings['show_trends'], FILTER_VALIDATE_BOOLEAN);
        }
        
        // Validate stats to show
        if (isset($settings['stats_to_show']) && is_array($settings['stats_to_show'])) {
            $allowed_stats = ['listings', 'sales', 'leads', 'revenue'];
            $validated['stats_to_show'] = array_intersect($settings['stats_to_show'], $allowed_stats);
        }
        
        return $validated;
    }
}