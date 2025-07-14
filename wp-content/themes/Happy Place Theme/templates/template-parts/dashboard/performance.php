<?php
/**
 * Dashboard Performance Section Template
 * 
 * Displays performance analytics, charts, and metrics for the agent
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get agent data (available from parent template)
$current_agent_id = $current_agent_id ?? get_current_user_id();

// Get date range from URL parameters
$date_range = isset($_GET['range']) ? sanitize_text_field($_GET['range']) : '30';
$start_date = date('Y-m-d', strtotime("-{$date_range} days"));
$end_date = date('Y-m-d');

// Calculate performance metrics
$performance_data = hph_get_agent_performance($current_agent_id, $start_date, $end_date);
$previous_period_data = hph_get_agent_performance($current_agent_id, 
    date('Y-m-d', strtotime("-" . ($date_range * 2) . " days")), 
    date('Y-m-d', strtotime("-{$date_range} days"))
);

// Performance metrics with comparisons
$metrics = [
    'total_views' => [
        'title' => __('Total Views', 'happy-place'),
        'current' => $performance_data['total_views'] ?? 0,
        'previous' => $previous_period_data['total_views'] ?? 0,
        'icon' => 'fa-eye',
        'format' => 'number'
    ],
    'unique_visitors' => [
        'title' => __('Unique Visitors', 'happy-place'),
        'current' => $performance_data['unique_visitors'] ?? 0,
        'previous' => $previous_period_data['unique_visitors'] ?? 0,
        'icon' => 'fa-users',
        'format' => 'number'
    ],
    'inquiries' => [
        'title' => __('Inquiries', 'happy-place'),
        'current' => $performance_data['inquiries'] ?? 0,
        'previous' => $previous_period_data['inquiries'] ?? 0,
        'icon' => 'fa-envelope',
        'format' => 'number'
    ],
    'conversion_rate' => [
        'title' => __('Conversion Rate', 'happy-place'),
        'current' => $performance_data['conversion_rate'] ?? 0,
        'previous' => $previous_period_data['conversion_rate'] ?? 0,
        'icon' => 'fa-percentage',
        'format' => 'percentage'
    ],
    'avg_time_on_listing' => [
        'title' => __('Avg. Time on Listing', 'happy-place'),
        'current' => $performance_data['avg_time_on_listing'] ?? 0,
        'previous' => $previous_period_data['avg_time_on_listing'] ?? 0,
        'icon' => 'fa-clock',
        'format' => 'time'
    ],
    'social_shares' => [
        'title' => __('Social Shares', 'happy-place'),
        'current' => $performance_data['social_shares'] ?? 0,
        'previous' => $previous_period_data['social_shares'] ?? 0,
        'icon' => 'fa-share-alt',
        'format' => 'number'
    ]
];

// Top performing listings
$top_listings = hph_get_top_performing_listings($current_agent_id, $start_date, $end_date, 5);

// Traffic sources
$traffic_sources = hph_get_traffic_sources($current_agent_id, $start_date, $end_date);

// Lead sources
$lead_sources = hph_get_lead_sources($current_agent_id, $start_date, $end_date);
?>

<div class="hph-performance-section">
    
    <!-- Section Header -->
    <div class="hph-section-header hph-d-flex hph-justify-between hph-items-center hph-mb-6">
        <div>
            <h2 class="hph-text-2xl hph-font-bold hph-text-gray-900 hph-mb-2">
                <?php esc_html_e('Performance Analytics', 'happy-place'); ?>
            </h2>
            <p class="hph-text-gray-600">
                <?php 
                printf(
                    /* translators: %s: date range */
                    esc_html__('Your performance metrics for the last %s days', 'happy-place'),
                    esc_html($date_range)
                ); 
                ?>
            </p>
        </div>
        
        <!-- Date Range Selector -->
        <div class="hph-date-range-selector">
            <form method="GET" class="hph-range-form">
                <input type="hidden" name="section" value="performance">
                <select name="range" class="hph-range-select" onchange="this.form.submit()">
                    <option value="7" <?php selected($date_range, '7'); ?>><?php esc_html_e('Last 7 days', 'happy-place'); ?></option>
                    <option value="30" <?php selected($date_range, '30'); ?>><?php esc_html_e('Last 30 days', 'happy-place'); ?></option>
                    <option value="90" <?php selected($date_range, '90'); ?>><?php esc_html_e('Last 3 months', 'happy-place'); ?></option>
                    <option value="365" <?php selected($date_range, '365'); ?>><?php esc_html_e('Last year', 'happy-place'); ?></option>
                </select>
            </form>
        </div>
    </div>

    <!-- Performance Metrics Grid -->
    <div class="hph-stats-overview">
        <?php foreach ($metrics as $key => $metric) : 
            $change = 0;
            $change_type = 'neutral';
            
            if ($metric['previous'] > 0) {
                $change = (($metric['current'] - $metric['previous']) / $metric['previous']) * 100;
                $change_type = $change > 0 ? 'positive' : ($change < 0 ? 'negative' : 'neutral');
            } elseif ($metric['current'] > 0) {
                $change_type = 'positive';
            }
            
            // Format the display value
            $display_value = $metric['current'];
            switch ($metric['format']) {
                case 'percentage':
                    $display_value = number_format($metric['current'], 1) . '%';
                    break;
                case 'time':
                    $minutes = floor($metric['current'] / 60);
                    $seconds = $metric['current'] % 60;
                    $display_value = $minutes . ':' . str_pad($seconds, 2, '0', STR_PAD_LEFT);
                    break;
                case 'number':
                default:
                    $display_value = number_format($metric['current']);
                    break;
            }
        ?>
            <div class="hph-metric-card">
                <div class="hph-metric-icon">
                    <i class="fas <?php echo esc_attr($metric['icon']); ?>"></i>
                </div>
                <div class="hph-metric-value"><?php echo esc_html($display_value); ?></div>
                <div class="hph-metric-label"><?php echo esc_html($metric['title']); ?></div>
                <?php if ($change != 0) : ?>
                    <div class="hph-metric-change hph-metric-change--<?php echo esc_attr($change_type); ?>">
                        <i class="fas <?php echo $change > 0 ? 'fa-arrow-up' : 'fa-arrow-down'; ?>"></i>
                        <?php echo esc_html(number_format(abs($change), 1) . '%'); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Charts and Analytics Grid -->
    <div class="hph-performance-grid">
        
        <!-- Main Chart Widget -->
        <div class="hph-chart-widget">
            <div class="hph-chart-header">
                <h3 class="hph-chart-title">
                    <i class="fas fa-chart-line"></i>
                    <?php esc_html_e('Views Over Time', 'happy-place'); ?>
                </h3>
                <div class="hph-chart-period">
                    <button class="hph-period-btn hph-period-btn--active" data-period="daily">
                        <?php esc_html_e('Daily', 'happy-place'); ?>
                    </button>
                    <button class="hph-period-btn" data-period="weekly">
                        <?php esc_html_e('Weekly', 'happy-place'); ?>
                    </button>
                    <button class="hph-period-btn" data-period="monthly">
                        <?php esc_html_e('Monthly', 'happy-place'); ?>
                    </button>
                </div>
            </div>
            <div class="hph-chart-container">
                <canvas id="hph-views-chart" data-agent-id="<?php echo esc_attr($current_agent_id); ?>" data-range="<?php echo esc_attr($date_range); ?>"></canvas>
                <div class="hph-chart-placeholder">
                    <i class="fas fa-chart-line"></i>
                    <p><?php esc_html_e('Loading chart data...', 'happy-place'); ?></p>
                </div>
            </div>
        </div>

        <!-- Traffic Sources -->
        <div class="hph-dashboard-widget">
            <div class="hph-dashboard-widget-header">
                <h3 class="hph-dashboard-widget-title">
                    <i class="fas fa-globe"></i>
                    <?php esc_html_e('Traffic Sources', 'happy-place'); ?>
                </h3>
                <p class="hph-dashboard-widget-subtitle">
                    <?php esc_html_e('Where your visitors are coming from', 'happy-place'); ?>
                </p>
            </div>
            <div class="hph-dashboard-widget-content">
                <?php if (!empty($traffic_sources)) : ?>
                    <div class="hph-traffic-sources">
                        <?php foreach ($traffic_sources as $source) : ?>
                            <div class="hph-traffic-source-item">
                                <div class="hph-traffic-source-info">
                                    <div class="hph-traffic-source-name"><?php echo esc_html($source['name']); ?></div>
                                    <div class="hph-traffic-source-visits"><?php echo esc_html(number_format($source['visits'])); ?> visits</div>
                                </div>
                                <div class="hph-traffic-source-percentage">
                                    <?php echo esc_html(number_format($source['percentage'], 1)); ?>%
                                </div>
                                <div class="hph-traffic-source-bar">
                                    <div class="hph-traffic-source-fill" style="width: <?php echo esc_attr($source['percentage']); ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="hph-empty-state hph-empty-state--small">
                        <div class="hph-empty-state-icon">
                            <i class="fas fa-globe"></i>
                        </div>
                        <p class="hph-empty-state-description">
                            <?php esc_html_e('No traffic data available for this period.', 'happy-place'); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Lead Sources -->
        <div class="hph-dashboard-widget">
            <div class="hph-dashboard-widget-header">
                <h3 class="hph-dashboard-widget-title">
                    <i class="fas fa-user-plus"></i>
                    <?php esc_html_e('Lead Sources', 'happy-place'); ?>
                </h3>
                <p class="hph-dashboard-widget-subtitle">
                    <?php esc_html_e('How leads are finding you', 'happy-place'); ?>
                </p>
            </div>
            <div class="hph-dashboard-widget-content">
                <?php if (!empty($lead_sources)) : ?>
                    <div class="hph-lead-sources">
                        <?php foreach ($lead_sources as $source) : ?>
                            <div class="hph-lead-source-item">
                                <div class="hph-lead-source-icon">
                                    <i class="fas <?php echo esc_attr($source['icon'] ?? 'fa-circle'); ?>"></i>
                                </div>
                                <div class="hph-lead-source-info">
                                    <div class="hph-lead-source-name"><?php echo esc_html($source['name']); ?></div>
                                    <div class="hph-lead-source-count"><?php echo esc_html($source['count']); ?> leads</div>
                                </div>
                                <div class="hph-lead-source-percentage">
                                    <?php echo esc_html(number_format($source['percentage'], 1)); ?>%
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="hph-empty-state hph-empty-state--small">
                        <div class="hph-empty-state-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <p class="hph-empty-state-description">
                            <?php esc_html_e('No lead data available for this period.', 'happy-place'); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Top Performing Listings -->
    <?php if (!empty($top_listings)) : ?>
    <div class="hph-dashboard-widget">
        <div class="hph-dashboard-widget-header">
            <h3 class="hph-dashboard-widget-title">
                <i class="fas fa-trophy"></i>
                <?php esc_html_e('Top Performing Listings', 'happy-place'); ?>
            </h3>
            <p class="hph-dashboard-widget-subtitle">
                <?php esc_html_e('Your most viewed and engaged listings', 'happy-place'); ?>
            </p>
        </div>
        <div class="hph-dashboard-widget-content">
            <div class="hph-top-listings">
                <?php foreach ($top_listings as $index => $listing) : 
                    $listing_id = $listing['ID'];
                    $listing_price = get_field('listing_price', $listing_id);
                    $listing_address = get_field('listing_address', $listing_id);
                    $listing_images = get_field('listing_images', $listing_id);
                    $featured_image = !empty($listing_images) ? $listing_images[0] : get_the_post_thumbnail_url($listing_id, 'thumbnail');
                ?>
                    <div class="hph-top-listing-item">
                        <div class="hph-top-listing-rank">
                            <?php if ($index === 0) : ?>
                                <i class="fas fa-crown hph-rank-crown"></i>
                            <?php else : ?>
                                <span class="hph-rank-number"><?php echo esc_html($index + 1); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($featured_image) : ?>
                            <img src="<?php echo esc_url($featured_image); ?>" 
                                 alt="<?php echo esc_attr($listing['post_title']); ?>" 
                                 class="hph-top-listing-image"
                                 loading="lazy">
                        <?php else : ?>
                            <div class="hph-top-listing-image-placeholder">
                                <i class="fas fa-home"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="hph-top-listing-info">
                            <div class="hph-top-listing-title">
                                <a href="<?php echo esc_url(get_edit_post_link($listing_id)); ?>">
                                    <?php echo esc_html($listing_address ?: $listing['post_title']); ?>
                                </a>
                            </div>
                            <?php if ($listing_price) : ?>
                                <div class="hph-top-listing-price">
                                    $<?php echo esc_html(number_format($listing_price)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="hph-top-listing-metrics">
                            <div class="hph-top-listing-metric">
                                <span class="hph-metric-value"><?php echo esc_html(number_format($listing['views'])); ?></span>
                                <span class="hph-metric-label"><?php esc_html_e('Views', 'happy-place'); ?></span>
                            </div>
                            <div class="hph-top-listing-metric">
                                <span class="hph-metric-value"><?php echo esc_html($listing['inquiries']); ?></span>
                                <span class="hph-metric-label"><?php esc_html_e('Inquiries', 'happy-place'); ?></span>
                            </div>
                        </div>
                        
                        <div class="hph-top-listing-actions">
                            <a href="<?php echo esc_url(get_permalink($listing_id)); ?>" 
                               class="hph-btn hph-btn--outline hph-btn--sm"
                               target="_blank">
                                <i class="fas fa-external-link-alt"></i>
                                <?php esc_html_e('View', 'happy-place'); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="hph-dashboard-widget-footer">
            <a href="<?php echo esc_url(add_query_arg('section', 'listings')); ?>" class="hph-btn hph-btn--outline">
                <?php esc_html_e('View All Listings', 'happy-place'); ?>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Performance Summary -->
    <div class="hph-performance-summary-widget">
        <div class="hph-dashboard-widget">
            <div class="hph-dashboard-widget-header">
                <h3 class="hph-dashboard-widget-title">
                    <i class="fas fa-chart-pie"></i>
                    <?php esc_html_e('Performance Summary', 'happy-place'); ?>
                </h3>
            </div>
            <div class="hph-dashboard-widget-content">
                <div class="hph-performance-insights">
                    <div class="hph-insight-item">
                        <div class="hph-insight-icon">
                            <i class="fas fa-trending-up"></i>
                        </div>
                        <div class="hph-insight-content">
                            <h4 class="hph-insight-title"><?php esc_html_e('Best Performing Day', 'happy-place'); ?></h4>
                            <p class="hph-insight-description">
                                <?php echo esc_html($performance_data['best_day'] ?? __('No data available', 'happy-place')); ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="hph-insight-item">
                        <div class="hph-insight-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="hph-insight-content">
                            <h4 class="hph-insight-title"><?php esc_html_e('Peak Hours', 'happy-place'); ?></h4>
                            <p class="hph-insight-description">
                                <?php echo esc_html($performance_data['peak_hours'] ?? __('No data available', 'happy-place')); ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="hph-insight-item">
                        <div class="hph-insight-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div class="hph-insight-content">
                            <h4 class="hph-insight-title"><?php esc_html_e('Mobile Traffic', 'happy-place'); ?></h4>
                            <p class="hph-insight-description">
                                <?php 
                                $mobile_percentage = $performance_data['mobile_percentage'] ?? 0;
                                printf(
                                    /* translators: %s: percentage of mobile traffic */
                                    esc_html__('%s%% of your traffic comes from mobile devices', 'happy-place'),
                                    esc_html(number_format($mobile_percentage, 1))
                                ); 
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
/* Performance Section Specific Styles */
.hph-date-range-selector {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-3);
}

.hph-range-select {
    padding: var(--hph-spacing-2) var(--hph-spacing-3);
    border: 1px solid var(--hph-color-gray-300);
    border-radius: var(--hph-radius-lg);
    font-size: var(--hph-font-size-sm);
    background: var(--hph-color-white);
    min-width: 150px;
}

.hph-metric-change {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-1);
    font-size: var(--hph-font-size-xs);
    font-weight: var(--hph-font-medium);
    margin-top: var(--hph-spacing-2);
}

.hph-metric-change--positive {
    color: var(--hph-color-success);
}

.hph-metric-change--negative {
    color: var(--hph-color-danger);
}

.hph-metric-change--neutral {
    color: var(--hph-color-gray-500);
}

.hph-traffic-sources,
.hph-lead-sources {
    display: flex;
    flex-direction: column;
    gap: var(--hph-spacing-4);
}

.hph-traffic-source-item {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-3);
}

.hph-traffic-source-info {
    flex: 1;
    min-width: 0;
}

.hph-traffic-source-name {
    font-size: var(--hph-font-size-sm);
    font-weight: var(--hph-font-medium);
    color: var(--hph-color-gray-900);
    margin-bottom: var(--hph-spacing-1);
}

.hph-traffic-source-visits {
    font-size: var(--hph-font-size-xs);
    color: var(--hph-color-gray-600);
}

.hph-traffic-source-percentage {
    font-size: var(--hph-font-size-sm);
    font-weight: var(--hph-font-semibold);
    color: var(--hph-color-primary-600);
    min-width: 50px;
    text-align: right;
}

.hph-traffic-source-bar {
    width: 100px;
    height: 6px;
    background: var(--hph-color-gray-200);
    border-radius: var(--hph-radius-full);
    overflow: hidden;
}

.hph-traffic-source-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--hph-color-primary-400), var(--hph-color-primary-600));
    border-radius: var(--hph-radius-full);
    transition: width var(--hph-transition-base);
}

.hph-lead-source-item {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-3);
    padding: var(--hph-spacing-3);
    background: var(--hph-color-gray-25);
    border-radius: var(--hph-radius-lg);
}

.hph-lead-source-icon {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, var(--hph-color-primary-100), var(--hph-color-primary-200));
    color: var(--hph-color-primary-600);
    border-radius: var(--hph-radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--hph-font-size-base);
    flex-shrink: 0;
}

.hph-lead-source-info {
    flex: 1;
}

.hph-lead-source-name {
    font-size: var(--hph-font-size-sm);
    font-weight: var(--hph-font-medium);
    color: var(--hph-color-gray-900);
    margin-bottom: var(--hph-spacing-1);
}

.hph-lead-source-count {
    font-size: var(--hph-font-size-xs);
    color: var(--hph-color-gray-600);
}

.hph-lead-source-percentage {
    font-size: var(--hph-font-size-sm);
    font-weight: var(--hph-font-semibold);
    color: var(--hph-color-primary-600);
}

.hph-top-listings {
    display: flex;
    flex-direction: column;
    gap: var(--hph-spacing-4);
}

.hph-top-listing-item {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-4);
    padding: var(--hph-spacing-4);
    background: var(--hph-color-gray-25);
    border-radius: var(--hph-radius-lg);
    transition: all var(--hph-transition-base);
}

.hph-top-listing-item:hover {
    background: var(--hph-color-primary-25);
    transform: translateY(-2px);
}

.hph-top-listing-rank {
    width: 40px;
    height: 40px;
    border-radius: var(--hph-radius-full);
    background: linear-gradient(135deg, var(--hph-color-primary-500), var(--hph-color-primary-600));
    color: var(--hph-color-white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: var(--hph-font-bold);
    flex-shrink: 0;
}

.hph-rank-crown {
    color: #ffd700;
    font-size: var(--hph-font-size-lg);
}

.hph-top-listing-image {
    width: 60px;
    height: 60px;
    border-radius: var(--hph-radius-lg);
    object-fit: cover;
    flex-shrink: 0;
}

.hph-top-listing-image-placeholder {
    width: 60px;
    height: 60px;
    background: var(--hph-color-gray-200);
    border-radius: var(--hph-radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--hph-color-gray-400);
    flex-shrink: 0;
}

.hph-top-listing-info {
    flex: 1;
    min-width: 0;
}

.hph-top-listing-title {
    margin-bottom: var(--hph-spacing-1);
}

.hph-top-listing-title a {
    font-size: var(--hph-font-size-base);
    font-weight: var(--hph-font-medium);
    color: var(--hph-color-gray-900);
    text-decoration: none;
    transition: color var(--hph-transition-fast);
}

.hph-top-listing-title a:hover {
    color: var(--hph-color-primary-600);
}

.hph-top-listing-price {
    font-size: var(--hph-font-size-sm);
    font-weight: var(--hph-font-semibold);
    color: var(--hph-color-primary-600);
}

.hph-top-listing-metrics {
    display: flex;
    gap: var(--hph-spacing-4);
    flex-shrink: 0;
}

.hph-top-listing-metric {
    text-align: center;
}

.hph-top-listing-metric .hph-metric-value {
    font-size: var(--hph-font-size-base);
    font-weight: var(--hph-font-bold);
    color: var(--hph-color-gray-900);
    display: block;
}

.hph-top-listing-metric .hph-metric-label {
    font-size: var(--hph-font-size-xs);
    color: var(--hph-color-gray-600);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.hph-performance-insights {
    display: flex;
    flex-direction: column;
    gap: var(--hph-spacing-4);
}

.hph-insight-item {
    display: flex;
    gap: var(--hph-spacing-3);
    padding: var(--hph-spacing-4);
    background: var(--hph-color-gray-25);
    border-radius: var(--hph-radius-lg);
}

.hph-insight-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, var(--hph-color-primary-100), var(--hph-color-primary-200));
    color: var(--hph-color-primary-600);
    border-radius: var(--hph-radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--hph-font-size-base);
    flex-shrink: 0;
}

.hph-insight-title {
    font-size: var(--hph-font-size-sm);
    font-weight: var(--hph-font-semibold);
    color: var(--hph-color-gray-900);
    margin: 0 0 var(--hph-spacing-1);
}

.hph-insight-description {
    font-size: var(--hph-font-size-sm);
    color: var(--hph-color-gray-600);
    margin: 0;
    line-height: 1.4;
}

.hph-empty-state--small {
    padding: var(--hph-spacing-6) var(--hph-spacing-4);
    text-align: center;
}

.hph-empty-state--small .hph-empty-state-icon {
    width: 48px;
    height: 48px;
    font-size: var(--hph-font-size-lg);
}

.hph-empty-state--small .hph-empty-state-description {
    font-size: var(--hph-font-size-sm);
    margin: 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .hph-performance-grid {
        grid-template-columns: 1fr;
    }
    
    .hph-stats-overview {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .hph-top-listing-item {
        flex-direction: column;
        text-align: center;
        gap: var(--hph-spacing-3);
    }
    
    .hph-top-listing-metrics {
        justify-content: center;
    }
    
    .hph-insight-item {
        flex-direction: column;
        text-align: center;
        gap: var(--hph-spacing-2);
    }
    
    .hph-date-range-selector {
        flex-direction: column;
        align-items: stretch;
        gap: var(--hph-spacing-2);
    }
}

@media (max-width: 480px) {
    .hph-stats-overview {
        grid-template-columns: 1fr;
    }
    
    .hph-traffic-source-item {
        flex-direction: column;
        gap: var(--hph-spacing-2);
        align-items: flex-start;
    }
    
    .hph-traffic-source-bar {
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart initialization (placeholder - integrate with your preferred chart library)
    const chartCanvas = document.getElementById('hph-views-chart');
    if (chartCanvas) {
        // Initialize chart here with your preferred library (Chart.js, etc.)
        // For now, hide the placeholder
        const placeholder = chartCanvas.parentNode.querySelector('.hph-chart-placeholder');
        if (placeholder) {
            placeholder.style.display = 'none';
        }
        
        // Example with Chart.js (if available)
        if (typeof Chart !== 'undefined') {
            new Chart(chartCanvas, {
                type: 'line',
                data: {
                    labels: [], // Add your date labels
                    datasets: [{
                        label: 'Views',
                        data: [], // Add your data
                        borderColor: 'rgb(81, 186, 224)',
                        backgroundColor: 'rgba(81, 186, 224, 0.1)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }
    
    // Period toggle functionality
    const periodButtons = document.querySelectorAll('.hph-period-btn');
    periodButtons.forEach(button => {
        button.addEventListener('click', function() {
            periodButtons.forEach(btn => btn.classList.remove('hph-period-btn--active'));
            this.classList.add('hph-period-btn--active');
            
            // Here you would update the chart data based on the selected period
            const period = this.dataset.period;
            console.log('Update chart for period:', period);
        });
    });
});
</script>

<?php
/**
 * Helper functions for performance data
 * These would typically be implemented in your plugin/theme
 */

function hph_get_agent_performance($agent_id, $start_date, $end_date) {
    // This is a placeholder - implement your actual analytics logic
    return [
        'total_views' => rand(1000, 5000),
        'unique_visitors' => rand(500, 2000),
        'inquiries' => rand(10, 50),
        'conversion_rate' => rand(2, 8),
        'avg_time_on_listing' => rand(120, 300),
        'social_shares' => rand(5, 25),
        'best_day' => wp_date('l', strtotime('-' . rand(1, 7) . ' days')),
        'peak_hours' => '2:00 PM - 4:00 PM',
        'mobile_percentage' => rand(60, 80)
    ];
}

function hph_get_top_performing_listings($agent_id, $start_date, $end_date, $limit = 5) {
    // Get agent's listings with mock performance data
    $listings = get_posts([
        'author' => $agent_id,
        'post_type' => 'listing',
        'post_status' => 'publish',
        'numberposts' => $limit
    ]);
    
    $performance_listings = [];
    foreach ($listings as $listing) {
        $performance_listings[] = [
            'ID' => $listing->ID,
            'post_title' => $listing->post_title,
            'views' => rand(500, 2000),
            'inquiries' => rand(5, 25)
        ];
    }
    
    // Sort by views
    usort($performance_listings, function($a, $b) {
        return $b['views'] - $a['views'];
    });
    
    return $performance_listings;
}

function hph_get_traffic_sources($agent_id, $start_date, $end_date) {
    // Mock traffic source data
    return [
        [
            'name' => 'Google Search',
            'visits' => rand(500, 1500),
            'percentage' => rand(40, 60)
        ],
        [
            'name' => 'Facebook',
            'visits' => rand(200, 800),
            'percentage' => rand(15, 25)
        ],
        [
            'name' => 'Direct Traffic',
            'visits' => rand(100, 400),
            'percentage' => rand(10, 20)
        ],
        [
            'name' => 'Instagram',
            'visits' => rand(50, 200),
            'percentage' => rand(5, 15)
        ]
    ];
}

function hph_get_lead_sources($agent_id, $start_date, $end_date) {
    // Mock lead source data
    return [
        [
            'name' => 'Website Contact Form',
            'count' => rand(10, 30),
            'percentage' => rand(40, 60),
            'icon' => 'fa-envelope'
        ],
        [
            'name' => 'Phone Calls',
            'count' => rand(5, 15),
            'percentage' => rand(20, 30),
            'icon' => 'fa-phone'
        ],
        [
            'name' => 'Social Media',
            'count' => rand(3, 10),
            'percentage' => rand(10, 20),
            'icon' => 'fa-share-alt'
        ],
        [
            'name' => 'Referrals',
            'count' => rand(2, 8),
            'percentage' => rand(5, 15),
            'icon' => 'fa-users'
        ]
    ];
}
?>