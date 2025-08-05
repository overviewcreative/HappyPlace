<?php
/**
 * Admin Dashboard Stats Component
 *
 * Comprehensive dashboard statistics and metrics display for agents,
 * including listings performance, inquiries, views, and financial data.
 *
 * @package HappyPlace\Components\Dashboard
 * @since 2.0.0
 */

namespace HappyPlace\Components\Dashboard;

use HappyPlace\Components\Base_Component;

if (!defined('ABSPATH')) {
    exit;
}

class Admin_Dashboard_Stats extends Base_Component {
    
    /**
     * Component name identifier
     *
     * @return string
     */
    protected function get_component_name() {
        return 'admin-dashboard-stats';
    }
    
    /**
     * Default component properties
     *
     * @return array
     */
    protected function get_defaults() {
        return [
            // Display Options
            'layout' => 'grid', // grid, list, cards, compact
            'columns' => 4, // Number of columns for grid layout
            'style' => 'modern', // modern, classic, minimal
            'show_charts' => true,
            'show_trends' => true,
            'show_comparisons' => true,
            
            // Data Options
            'agent_id' => 0, // Current user if 0
            'date_range' => '30days', // 7days, 30days, 90days, year, custom
            'custom_start_date' => '',
            'custom_end_date' => '',
            'include_team_stats' => false,
            
            // Stats to Show
            'show_listings_stats' => true,
            'show_inquiries_stats' => true,
            'show_financial_stats' => true,
            'show_performance_stats' => true,
            'show_activity_stats' => true,
            
            // Chart Options
            'chart_type' => 'line', // line, bar, pie, donut, area
            'chart_height' => '200px',
            'chart_colors' => ['#007cba', '#28a745', '#ffc107', '#dc3545'],
            'enable_interactive_charts' => true,
            
            // Refresh
            'auto_refresh' => false,
            'refresh_interval' => 300000, // 5 minutes in ms
            
            // Customization
            'custom_metrics' => [],
            'hide_zero_stats' => false,
            'show_goals' => true,
            'goals' => [
                'monthly_listings' => 10,
                'monthly_sales' => 5,
                'monthly_revenue' => 100000
            ]
        ];
    }
    
    /**
     * Validate component properties
     */
    protected function validate_props() {
        $valid_layouts = ['grid', 'list', 'cards', 'compact'];
        if (!in_array($this->get_prop('layout'), $valid_layouts)) {
            $this->add_validation_error('Invalid layout. Must be: ' . implode(', ', $valid_layouts));
        }
        
        $valid_date_ranges = ['7days', '30days', '90days', 'year', 'custom'];
        if (!in_array($this->get_prop('date_range'), $valid_date_ranges)) {
            $this->add_validation_error('Invalid date_range. Must be: ' . implode(', ', $valid_date_ranges));
        }
        
        // Validate custom date range
        if ($this->get_prop('date_range') === 'custom') {
            if (empty($this->get_prop('custom_start_date')) || empty($this->get_prop('custom_end_date'))) {
                $this->add_validation_error('custom_start_date and custom_end_date are required when date_range is custom');
            }
        }
    }
    
    /**
     * Render the component
     *
     * @return string
     */
    protected function render() {
        $agent_id = $this->get_prop('agent_id') ?: get_current_user_id();
        $stats_data = $this->get_stats_data($agent_id);
        $classes = $this->build_css_classes();
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($classes); ?>" 
             data-component="admin-dashboard-stats"
             data-agent-id="<?php echo esc_attr($agent_id); ?>"
             data-auto-refresh="<?php echo $this->get_prop('auto_refresh') ? 'true' : 'false'; ?>"
             data-refresh-interval="<?php echo esc_attr($this->get_prop('refresh_interval')); ?>">
            
            <!-- Dashboard Header -->
            <div class="hph-dashboard-stats__header">
                <div class="hph-dashboard-stats__title">
                    <h2><?php esc_html_e('Dashboard Overview', 'happy-place'); ?></h2>
                    <span class="hph-dashboard-stats__period">
                        <?php echo esc_html($this->get_period_label()); ?>
                    </span>
                </div>
                
                <div class="hph-dashboard-stats__controls">
                    <!-- Date Range Selector -->
                    <select class="hph-dashboard-stats__date-range" data-action="change-date-range">
                        <option value="7days" <?php selected($this->get_prop('date_range'), '7days'); ?>><?php esc_html_e('Last 7 Days', 'happy-place'); ?></option>
                        <option value="30days" <?php selected($this->get_prop('date_range'), '30days'); ?>><?php esc_html_e('Last 30 Days', 'happy-place'); ?></option>
                        <option value="90days" <?php selected($this->get_prop('date_range'), '90days'); ?>><?php esc_html_e('Last 90 Days', 'happy-place'); ?></option>
                        <option value="year" <?php selected($this->get_prop('date_range'), 'year'); ?>><?php esc_html_e('This Year', 'happy-place'); ?></option>
                        <option value="custom" <?php selected($this->get_prop('date_range'), 'custom'); ?>><?php esc_html_e('Custom Range', 'happy-place'); ?></option>
                    </select>
                    
                    <!-- Refresh Button -->
                    <button type="button" 
                            class="hph-btn hph-btn--secondary hph-dashboard-stats__refresh"
                            data-action="refresh-stats"
                            title="<?php esc_attr_e('Refresh Stats', 'happy-place'); ?>">
                        <i class="hph-icon hph-icon--refresh" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            
            <!-- Main Stats Grid -->
            <div class="hph-dashboard-stats__grid hph-dashboard-stats__grid--<?php echo esc_attr($this->get_prop('columns')); ?>-col">
                
                <!-- Listings Stats -->
                <?php if ($this->get_prop('show_listings_stats')): ?>
                    <?php $this->render_listings_stats($stats_data['listings'] ?? []); ?>
                <?php endif; ?>
                
                <!-- Inquiries Stats -->
                <?php if ($this->get_prop('show_inquiries_stats')): ?>
                    <?php $this->render_inquiries_stats($stats_data['inquiries'] ?? []); ?>
                <?php endif; ?>
                
                <!-- Financial Stats -->
                <?php if ($this->get_prop('show_financial_stats')): ?>
                    <?php $this->render_financial_stats($stats_data['financial'] ?? []); ?>
                <?php endif; ?>
                
                <!-- Performance Stats -->
                <?php if ($this->get_prop('show_performance_stats')): ?>
                    <?php $this->render_performance_stats($stats_data['performance'] ?? []); ?>
                <?php endif; ?>
                
                <!-- Activity Stats -->
                <?php if ($this->get_prop('show_activity_stats')): ?>
                    <?php $this->render_activity_stats($stats_data['activity'] ?? []); ?>
                <?php endif; ?>
                
                <!-- Custom Metrics -->
                <?php $this->render_custom_metrics($stats_data['custom'] ?? []); ?>
                
            </div>
            
            <!-- Charts Section -->
            <?php if ($this->get_prop('show_charts')): ?>
                <div class="hph-dashboard-stats__charts">
                    <?php $this->render_charts($stats_data); ?>
                </div>
            <?php endif; ?>
            
            <!-- Goals Section -->
            <?php if ($this->get_prop('show_goals')): ?>
                <div class="hph-dashboard-stats__goals">
                    <?php $this->render_goals($stats_data); ?>
                </div>
            <?php endif; ?>
            
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Build CSS classes
     */
    private function build_css_classes() {
        $classes = ['hph-dashboard-stats'];
        
        $classes[] = 'hph-dashboard-stats--' . $this->get_prop('layout');
        $classes[] = 'hph-dashboard-stats--' . $this->get_prop('style');
        
        if ($this->get_prop('show_charts')) {
            $classes[] = 'hph-dashboard-stats--with-charts';
        }
        
        if ($this->get_prop('show_trends')) {
            $classes[] = 'hph-dashboard-stats--with-trends';
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * Get stats data for agent
     */
    private function get_stats_data($agent_id) {
        $date_range = $this->get_date_range();
        
        return [
            'listings' => $this->get_listings_stats($agent_id, $date_range),
            'inquiries' => $this->get_inquiries_stats($agent_id, $date_range),
            'financial' => $this->get_financial_stats($agent_id, $date_range),
            'performance' => $this->get_performance_stats($agent_id, $date_range),
            'activity' => $this->get_activity_stats($agent_id, $date_range),
            'custom' => $this->get_custom_metrics($agent_id, $date_range)
        ];
    }
    
    /**
     * Get date range array
     */
    private function get_date_range() {
        $range = $this->get_prop('date_range');
        $end_date = current_time('Y-m-d');
        
        switch ($range) {
            case '7days':
                $start_date = date('Y-m-d', strtotime('-7 days'));
                break;
            case '30days':
                $start_date = date('Y-m-d', strtotime('-30 days'));
                break;
            case '90days':
                $start_date = date('Y-m-d', strtotime('-90 days'));
                break;
            case 'year':
                $start_date = date('Y-01-01');
                break;
            case 'custom':
                $start_date = $this->get_prop('custom_start_date');
                $end_date = $this->get_prop('custom_end_date');
                break;
            default:
                $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        
        return [
            'start' => $start_date,
            'end' => $end_date
        ];
    }
    
    /**
     * Get period label
     */
    private function get_period_label() {
        $range = $this->get_prop('date_range');
        
        switch ($range) {
            case '7days':
                return __('Last 7 Days', 'happy-place');
            case '30days':
                return __('Last 30 Days', 'happy-place');
            case '90days':
                return __('Last 90 Days', 'happy-place');
            case 'year':
                return __('This Year', 'happy-place');
            case 'custom':
                $start = $this->get_prop('custom_start_date');
                $end = $this->get_prop('custom_end_date');
                return sprintf(__('%s to %s', 'happy-place'), $start, $end);
            default:
                return __('Last 30 Days', 'happy-place');
        }
    }
    
    /**
     * Get listings statistics
     */
    private function get_listings_stats($agent_id, $date_range) {
        // Get data from plugin's data provider
        $dashboard_manager = \HappyPlace\Dashboard\Dashboard_Manager::get_instance();
        $data_provider = $dashboard_manager->get_data_provider();
        
        if ($data_provider) {
            $stats = $data_provider->get_user_stats($agent_id);
            return $stats['listings'] ?? [];
        }
        
        return [];
    }
    
    /**
     * Get inquiries statistics
     */
    private function get_inquiries_stats($agent_id, $date_range) {
        // Get data from plugin's data provider
        $dashboard_manager = \HappyPlace\Dashboard\Dashboard_Manager::get_instance();
        $data_provider = $dashboard_manager->get_data_provider();
        
        if ($data_provider) {
            $stats = $data_provider->get_user_stats($agent_id);
            return $stats['inquiries'] ?? [];
        }
        
        return [];
    }
    
    /**
     * Get financial statistics
     */
    private function get_financial_stats($agent_id, $date_range) {
        // Get data from plugin's data provider
        $dashboard_manager = \HappyPlace\Dashboard\Dashboard_Manager::get_instance();
        $data_provider = $dashboard_manager->get_data_provider();
        
        if ($data_provider) {
            $stats = $data_provider->get_user_stats($agent_id);
            return $stats['financial'] ?? [];
        }
        
        return [];
    }
    
    /**
     * Get performance statistics
     */
    private function get_performance_stats($agent_id, $date_range) {
        // Get data from plugin's data provider
        $dashboard_manager = \HappyPlace\Dashboard\Dashboard_Manager::get_instance();
        $data_provider = $dashboard_manager->get_data_provider();
        
        if ($data_provider) {
            $performance_data = $data_provider->get_performance_data($agent_id);
            return $performance_data['metrics'] ?? [];
        }
        
        return [];
    }
    
    /**
     * Get activity statistics
     */
    private function get_activity_stats($agent_id, $date_range) {
        // Get data from plugin's data provider
        $dashboard_manager = \HappyPlace\Dashboard\Dashboard_Manager::get_instance();
        $data_provider = $dashboard_manager->get_data_provider();
        
        if ($data_provider) {
            $stats = $data_provider->get_user_stats($agent_id);
            return $stats['activity'] ?? [];
        }
        
        return [];
    }
    
    /**
     * Get custom metrics
     */
    private function get_custom_metrics($agent_id, $date_range) {
        $custom_metrics = $this->get_prop('custom_metrics');
        $metrics = [];
        
        foreach ($custom_metrics as $metric) {
            // TODO: Implement custom metric calculation
            $metrics[$metric['key']] = $metric['value'] ?? 0;
        }
        
        return $metrics;
    }
    
    /**
     * Render listings stats
     */
    private function render_listings_stats($stats) {
        ?>
        <div class="hph-dashboard-stat hph-dashboard-stat--listings">
            <div class="hph-dashboard-stat__header">
                <h3 class="hph-dashboard-stat__title"><?php esc_html_e('Listings', 'happy-place'); ?></h3>
                <i class="hph-icon hph-icon--home hph-dashboard-stat__icon" aria-hidden="true"></i>
            </div>
            <div class="hph-dashboard-stat__content">
                <div class="hph-dashboard-stat__main">
                    <span class="hph-dashboard-stat__number"><?php echo esc_html($stats['total_listings'] ?? 0); ?></span>
                    <span class="hph-dashboard-stat__label"><?php esc_html_e('Total Listings', 'happy-place'); ?></span>
                </div>
                <div class="hph-dashboard-stat__details">
                    <div class="hph-stat-detail">
                        <span class="hph-stat-detail__value"><?php echo esc_html($stats['active_listings'] ?? 0); ?></span>
                        <span class="hph-stat-detail__label"><?php esc_html_e('Active', 'happy-place'); ?></span>
                    </div>
                    <div class="hph-stat-detail">
                        <span class="hph-stat-detail__value"><?php echo esc_html($stats['sold_listings'] ?? 0); ?></span>
                        <span class="hph-stat-detail__label"><?php esc_html_e('Sold', 'happy-place'); ?></span>
                    </div>
                    <div class="hph-stat-detail">
                        <span class="hph-stat-detail__value"><?php echo esc_html($stats['pending_listings'] ?? 0); ?></span>
                        <span class="hph-stat-detail__label"><?php esc_html_e('Pending', 'happy-place'); ?></span>
                    </div>
                </div>
                <?php if ($this->get_prop('show_trends')): ?>
                    <div class="hph-dashboard-stat__trend">
                        <span class="hph-trend hph-trend--up">
                            <i class="hph-icon hph-icon--trend-up" aria-hidden="true"></i>
                            <span>+<?php echo esc_html($stats['new_listings'] ?? 0); ?></span>
                        </span>
                        <span class="hph-trend__label"><?php esc_html_e('new this period', 'happy-place'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render inquiries stats
     */
    private function render_inquiries_stats($stats) {
        ?>
        <div class="hph-dashboard-stat hph-dashboard-stat--inquiries">
            <div class="hph-dashboard-stat__header">
                <h3 class="hph-dashboard-stat__title"><?php esc_html_e('Inquiries', 'happy-place'); ?></h3>
                <i class="hph-icon hph-icon--message hph-dashboard-stat__icon" aria-hidden="true"></i>
            </div>
            <div class="hph-dashboard-stat__content">
                <div class="hph-dashboard-stat__main">
                    <span class="hph-dashboard-stat__number"><?php echo esc_html($stats['total_inquiries'] ?? 0); ?></span>
                    <span class="hph-dashboard-stat__label"><?php esc_html_e('Total Inquiries', 'happy-place'); ?></span>
                </div>
                <div class="hph-dashboard-stat__details">
                    <div class="hph-stat-detail">
                        <span class="hph-stat-detail__value"><?php echo esc_html($stats['response_rate'] ?? 0); ?>%</span>
                        <span class="hph-stat-detail__label"><?php esc_html_e('Response Rate', 'happy-place'); ?></span>
                    </div>
                    <div class="hph-stat-detail">
                        <span class="hph-stat-detail__value"><?php echo esc_html($stats['avg_response_time'] ?? 0); ?>h</span>
                        <span class="hph-stat-detail__label"><?php esc_html_e('Avg Response', 'happy-place'); ?></span>
                    </div>
                </div>
                <?php if ($this->get_prop('show_trends')): ?>
                    <div class="hph-dashboard-stat__trend">
                        <span class="hph-trend hph-trend--up">
                            <i class="hph-icon hph-icon--trend-up" aria-hidden="true"></i>
                            <span>+<?php echo esc_html($stats['new_inquiries'] ?? 0); ?></span>
                        </span>
                        <span class="hph-trend__label"><?php esc_html_e('new inquiries', 'happy-place'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render financial stats
     */
    private function render_financial_stats($stats) {
        ?>
        <div class="hph-dashboard-stat hph-dashboard-stat--financial">
            <div class="hph-dashboard-stat__header">
                <h3 class="hph-dashboard-stat__title"><?php esc_html_e('Financial', 'happy-place'); ?></h3>
                <i class="hph-icon hph-icon--dollar hph-dashboard-stat__icon" aria-hidden="true"></i>
            </div>
            <div class="hph-dashboard-stat__content">
                <div class="hph-dashboard-stat__main">
                    <span class="hph-dashboard-stat__number">$<?php echo esc_html(number_format($stats['commission_earned'] ?? 0)); ?></span>
                    <span class="hph-dashboard-stat__label"><?php esc_html_e('Commission Earned', 'happy-place'); ?></span>
                </div>
                <div class="hph-dashboard-stat__details">
                    <div class="hph-stat-detail">
                        <span class="hph-stat-detail__value">$<?php echo esc_html(number_format($stats['total_sales_volume'] ?? 0)); ?></span>
                        <span class="hph-stat-detail__label"><?php esc_html_e('Sales Volume', 'happy-place'); ?></span>
                    </div>
                    <div class="hph-stat-detail">
                        <span class="hph-stat-detail__value">$<?php echo esc_html(number_format($stats['avg_sale_price'] ?? 0)); ?></span>
                        <span class="hph-stat-detail__label"><?php esc_html_e('Avg Sale Price', 'happy-place'); ?></span>
                    </div>
                </div>
                <?php if ($this->get_prop('show_trends')): ?>
                    <div class="hph-dashboard-stat__trend">
                        <span class="hph-trend hph-trend--pending">
                            <i class="hph-icon hph-icon--clock" aria-hidden="true"></i>
                            <span>$<?php echo esc_html(number_format($stats['pending_commission'] ?? 0)); ?></span>
                        </span>
                        <span class="hph-trend__label"><?php esc_html_e('pending commission', 'happy-place'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render performance stats
     */
    private function render_performance_stats($stats) {
        ?>
        <div class="hph-dashboard-stat hph-dashboard-stat--performance">
            <div class="hph-dashboard-stat__header">
                <h3 class="hph-dashboard-stat__title"><?php esc_html_e('Performance', 'happy-place'); ?></h3>
                <i class="hph-icon hph-icon--chart hph-dashboard-stat__icon" aria-hidden="true"></i>
            </div>
            <div class="hph-dashboard-stat__content">
                <div class="hph-dashboard-stat__main">
                    <span class="hph-dashboard-stat__number"><?php echo esc_html($stats['conversion_rate'] ?? 0); ?>%</span>
                    <span class="hph-dashboard-stat__label"><?php esc_html_e('Conversion Rate', 'happy-place'); ?></span>
                </div>
                <div class="hph-dashboard-stat__details">
                    <div class="hph-stat-detail">
                        <span class="hph-stat-detail__value"><?php echo esc_html($stats['customer_satisfaction'] ?? 0); ?>/5</span>
                        <span class="hph-stat-detail__label"><?php esc_html_e('Satisfaction', 'happy-place'); ?></span>
                    </div>
                    <div class="hph-stat-detail">
                        <span class="hph-stat-detail__value"><?php echo esc_html($stats['referrals'] ?? 0); ?></span>
                        <span class="hph-stat-detail__label"><?php esc_html_e('Referrals', 'happy-place'); ?></span>
                    </div>
                </div>
                <?php if ($this->get_prop('show_trends')): ?>
                    <div class="hph-dashboard-stat__trend">
                        <span class="hph-trend hph-trend--score">
                            <i class="hph-icon hph-icon--star" aria-hidden="true"></i>
                            <span><?php echo esc_html($stats['lead_score'] ?? 0); ?></span>
                        </span>
                        <span class="hph-trend__label"><?php esc_html_e('lead score', 'happy-place'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render activity stats
     */
    private function render_activity_stats($stats) {
        ?>
        <div class="hph-dashboard-stat hph-dashboard-stat--activity">
            <div class="hph-dashboard-stat__header">
                <h3 class="hph-dashboard-stat__title"><?php esc_html_e('Activity', 'happy-place'); ?></h3>
                <i class="hph-icon hph-icon--activity hph-dashboard-stat__icon" aria-hidden="true"></i>
            </div>
            <div class="hph-dashboard-stat__content">
                <div class="hph-dashboard-stat__main">
                    <span class="hph-dashboard-stat__number"><?php echo esc_html($stats['property_visits'] ?? 0); ?></span>
                    <span class="hph-dashboard-stat__label"><?php esc_html_e('Property Visits', 'happy-place'); ?></span>
                </div>
                <div class="hph-dashboard-stat__details">
                    <div class="hph-stat-detail">
                        <span class="hph-stat-detail__value"><?php echo esc_html($stats['open_houses'] ?? 0); ?></span>
                        <span class="hph-stat-detail__label"><?php esc_html_e('Open Houses', 'happy-place'); ?></span>
                    </div>
                    <div class="hph-stat-detail">
                        <span class="hph-stat-detail__value"><?php echo esc_html($stats['appointments_scheduled'] ?? 0); ?></span>
                        <span class="hph-stat-detail__label"><?php esc_html_e('Appointments', 'happy-place'); ?></span>
                    </div>
                </div>
                <?php if ($this->get_prop('show_trends')): ?>
                    <div class="hph-dashboard-stat__trend">
                        <span class="hph-trend hph-trend--neutral">
                            <i class="hph-icon hph-icon--calendar" aria-hidden="true"></i>
                            <span><?php echo esc_html($stats['follow_ups_completed'] ?? 0); ?></span>
                        </span>
                        <span class="hph-trend__label"><?php esc_html_e('follow-ups completed', 'happy-place'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render custom metrics
     */
    private function render_custom_metrics($stats) {
        $custom_metrics = $this->get_prop('custom_metrics');
        
        foreach ($custom_metrics as $metric) {
            $value = $stats[$metric['key']] ?? 0;
            
            if ($this->get_prop('hide_zero_stats') && empty($value)) {
                continue;
            }
            ?>
            <div class="hph-dashboard-stat hph-dashboard-stat--custom">
                <div class="hph-dashboard-stat__header">
                    <h3 class="hph-dashboard-stat__title"><?php echo esc_html($metric['title'] ?? ''); ?></h3>
                    <?php if (!empty($metric['icon'])): ?>
                        <i class="hph-icon hph-icon--<?php echo esc_attr($metric['icon']); ?> hph-dashboard-stat__icon" aria-hidden="true"></i>
                    <?php endif; ?>
                </div>
                <div class="hph-dashboard-stat__content">
                    <div class="hph-dashboard-stat__main">
                        <span class="hph-dashboard-stat__number"><?php echo esc_html($metric['format'] === 'currency' ? '$' . number_format($value) : $value); ?></span>
                        <span class="hph-dashboard-stat__label"><?php echo esc_html($metric['label'] ?? ''); ?></span>
                    </div>
                </div>
            </div>
            <?php
        }
    }
    
    /**
     * Render charts
     */
    private function render_charts($stats_data) {
        ?>
        <div class="hph-dashboard-charts">
            <h3 class="hph-dashboard-charts__title"><?php esc_html_e('Performance Charts', 'happy-place'); ?></h3>
            
            <div class="hph-dashboard-charts__grid">
                <!-- Listings Chart -->
                <div class="hph-dashboard-chart">
                    <h4 class="hph-dashboard-chart__title"><?php esc_html_e('Listings Over Time', 'happy-place'); ?></h4>
                    <div class="hph-dashboard-chart__container" 
                         style="height: <?php echo esc_attr($this->get_prop('chart_height')); ?>;">
                        <canvas id="listings-chart" data-chart-type="<?php echo esc_attr($this->get_prop('chart_type')); ?>"></canvas>
                    </div>
                </div>
                
                <!-- Revenue Chart -->
                <div class="hph-dashboard-chart">
                    <h4 class="hph-dashboard-chart__title"><?php esc_html_e('Revenue Over Time', 'happy-place'); ?></h4>
                    <div class="hph-dashboard-chart__container" 
                         style="height: <?php echo esc_attr($this->get_prop('chart_height')); ?>;">
                        <canvas id="revenue-chart" data-chart-type="<?php echo esc_attr($this->get_prop('chart_type')); ?>"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render goals
     */
    private function render_goals($stats_data) {
        $goals = $this->get_prop('goals');
        
        if (empty($goals)) {
            return;
        }
        ?>
        <div class="hph-dashboard-goals">
            <h3 class="hph-dashboard-goals__title"><?php esc_html_e('Goals Progress', 'happy-place'); ?></h3>
            
            <div class="hph-dashboard-goals__grid">
                <?php foreach ($goals as $goal_key => $goal_value): ?>
                    <?php
                    $current_value = $this->get_current_goal_value($goal_key, $stats_data);
                    $progress = $goal_value > 0 ? min(100, ($current_value / $goal_value) * 100) : 0;
                    ?>
                    <div class="hph-dashboard-goal">
                        <div class="hph-dashboard-goal__header">
                            <h4 class="hph-dashboard-goal__title"><?php echo esc_html($this->get_goal_title($goal_key)); ?></h4>
                            <span class="hph-dashboard-goal__progress"><?php echo esc_html(round($progress)); ?>%</span>
                        </div>
                        <div class="hph-dashboard-goal__bar">
                            <div class="hph-dashboard-goal__fill" style="width: <?php echo esc_attr($progress); ?>%;"></div>
                        </div>
                        <div class="hph-dashboard-goal__details">
                            <span class="hph-dashboard-goal__current"><?php echo esc_html($current_value); ?></span>
                            <span class="hph-dashboard-goal__target"> / <?php echo esc_html($goal_value); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get current goal value
     */
    private function get_current_goal_value($goal_key, $stats_data) {
        switch ($goal_key) {
            case 'monthly_listings':
                return $stats_data['listings']['new_listings'] ?? 0;
            case 'monthly_sales':
                return $stats_data['listings']['sold_listings'] ?? 0;
            case 'monthly_revenue':
                return $stats_data['financial']['commission_earned'] ?? 0;
            default:
                return 0;
        }
    }
    
    /**
     * Get goal title
     */
    private function get_goal_title($goal_key) {
        switch ($goal_key) {
            case 'monthly_listings':
                return __('Monthly Listings', 'happy-place');
            case 'monthly_sales':
                return __('Monthly Sales', 'happy-place');
            case 'monthly_revenue':
                return __('Monthly Revenue', 'happy-place');
            default:
                return ucwords(str_replace('_', ' ', $goal_key));
        }
    }
}
