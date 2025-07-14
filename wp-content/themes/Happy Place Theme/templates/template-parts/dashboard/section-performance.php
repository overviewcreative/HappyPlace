<?php

/**
 * Dashboard Performance Section Template
 * 
 * Displays analytics and performance metrics for the agent
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get agent data (available from parent template)
$current_agent_id = $current_agent_id ?? get_current_user_id();
$stats = $stats ?? [];

// Get performance data
$performance_data = function_exists('hph_get_agent_performance') ?
    hph_get_agent_performance($current_agent_id) : [];

// Chart periods
$chart_periods = [
    '7d' => __('Last 7 Days', 'happy-place'),
    '30d' => __('Last 30 Days', 'happy-place'),
    '90d' => __('Last 3 Months', 'happy-place'),
    '1y' => __('Last Year', 'happy-place')
];

$current_period = $_GET['period'] ?? '30d';
?>

<div class="hph-performance-section">

    <!-- Section Header -->
    <div class="hph-dashboard-section-header">
        <div class="hph-section-title-group">
            <h2 class="hph-dashboard-section-title">
                <i class="fas fa-chart-line"></i>
                <?php esc_html_e('Performance Analytics', 'happy-place'); ?>
            </h2>
            <p class="hph-dashboard-section-subtitle">
                <?php esc_html_e('Track your performance metrics and business insights', 'happy-place'); ?>
            </p>
        </div>
        <div class="hph-section-actions">
            <div class="hph-period-selector">
                <label for="chart-period" class="hph-sr-only"><?php esc_html_e('Select time period', 'happy-place'); ?></label>
                <select id="chart-period" class="hph-select hph-select--sm" data-period-selector>
                    <?php foreach ($chart_periods as $period => $label) : ?>
                        <option value="<?php echo esc_attr($period); ?>" <?php selected($current_period, $period); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- Key Performance Indicators -->
    <div class="hph-dashboard-stats">
        <div class="hph-stat-card">
            <div class="hph-stat-card-header">
                <h4 class="hph-stat-card-title"><?php esc_html_e('Total Views', 'happy-place'); ?></h4>
                <div class="hph-stat-card-icon">
                    <i class="fas fa-eye"></i>
                </div>
            </div>
            <div class="hph-stat-card-value"><?php echo number_format($stats['total_views'] ?? 0); ?></div>
            <?php if (isset($stats['views_change']) && $stats['views_change'] != 0) : ?>
                <div class="hph-stat-card-change <?php echo $stats['views_change'] > 0 ? 'hph-stat-card-change--positive' : 'hph-stat-card-change--negative'; ?>">
                    <i class="fas <?php echo $stats['views_change'] > 0 ? 'fa-arrow-up' : 'fa-arrow-down'; ?>"></i>
                    <?php echo esc_html(abs($stats['views_change']) . '%'); ?>
                    <span class="hph-text-xs"><?php esc_html_e('vs last period', 'happy-place'); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <div class="hph-stat-card">
            <div class="hph-stat-card-header">
                <h4 class="hph-stat-card-title"><?php esc_html_e('Inquiries', 'happy-place'); ?></h4>
                <div class="hph-stat-card-icon">
                    <i class="fas fa-envelope"></i>
                </div>
            </div>
            <div class="hph-stat-card-value"><?php echo number_format($stats['total_inquiries'] ?? 0); ?></div>
            <?php if (isset($stats['inquiries_change']) && $stats['inquiries_change'] != 0) : ?>
                <div class="hph-stat-card-change <?php echo $stats['inquiries_change'] > 0 ? 'hph-stat-card-change--positive' : 'hph-stat-card-change--negative'; ?>">
                    <i class="fas <?php echo $stats['inquiries_change'] > 0 ? 'fa-arrow-up' : 'fa-arrow-down'; ?>"></i>
                    <?php echo esc_html(abs($stats['inquiries_change']) . '%'); ?>
                    <span class="hph-text-xs"><?php esc_html_e('vs last period', 'happy-place'); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <div class="hph-stat-card">
            <div class="hph-stat-card-header">
                <h4 class="hph-stat-card-title"><?php esc_html_e('Avg. Days on Market', 'happy-place'); ?></h4>
                <div class="hph-stat-card-icon">
                    <i class="fas fa-calendar"></i>
                </div>
            </div>
            <div class="hph-stat-card-value"><?php echo number_format($stats['avg_days_on_market'] ?? 0); ?></div>
        </div>

        <div class="hph-stat-card">
            <div class="hph-stat-card-header">
                <h4 class="hph-stat-card-title"><?php esc_html_e('Conversion Rate', 'happy-place'); ?></h4>
                <div class="hph-stat-card-icon">
                    <i class="fas fa-percentage"></i>
                </div>
            </div>
            <div class="hph-stat-card-value"><?php echo number_format($stats['conversion_rate'] ?? 0, 1); ?>%</div>
        </div>
    </div>

    <!-- Charts and Analytics -->
    <div class="hph-dashboard-content">

        <!-- Views Chart -->
        <div class="hph-dashboard-widget hph-dashboard-widget--chart">
            <div class="hph-dashboard-widget-header">
                <h3 class="hph-dashboard-widget-title">
                    <i class="fas fa-chart-area"></i>
                    <?php esc_html_e('Listing Views Over Time', 'happy-place'); ?>
                </h3>
                <p class="hph-dashboard-widget-subtitle">
                    <?php esc_html_e('Track how your listings are performing with potential buyers', 'happy-place'); ?>
                </p>
            </div>
            <div class="hph-dashboard-widget-content">
                <div class="hph-chart-container">
                    <canvas id="hph-views-chart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Inquiries Chart -->
        <div class="hph-dashboard-widget hph-dashboard-widget--chart">
            <div class="hph-dashboard-widget-header">
                <h3 class="hph-dashboard-widget-title">
                    <i class="fas fa-chart-line"></i>
                    <?php esc_html_e('Inquiries & Leads', 'happy-place'); ?>
                </h3>
                <p class="hph-dashboard-widget-subtitle">
                    <?php esc_html_e('Monitor lead generation and inquiry trends', 'happy-place'); ?>
                </p>
            </div>
            <div class="hph-dashboard-widget-content">
                <div class="hph-chart-container">
                    <canvas id="hph-inquiries-chart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Performing Listings -->
        <div class="hph-dashboard-widget">
            <div class="hph-dashboard-widget-header">
                <h3 class="hph-dashboard-widget-title">
                    <i class="fas fa-trophy"></i>
                    <?php esc_html_e('Top Performing Listings', 'happy-place'); ?>
                </h3>
                <p class="hph-dashboard-widget-subtitle">
                    <?php esc_html_e('Your most viewed and inquired about properties', 'happy-place'); ?>
                </p>
            </div>
            <div class="hph-dashboard-widget-content">
                <?php
                $top_listings = function_exists('hph_get_top_performing_listings') ?
                    hph_get_top_performing_listings($current_agent_id, 5) : [];

                if (!empty($top_listings)) : ?>
                    <div class="hph-performance-listings">
                        <?php foreach ($top_listings as $listing) : ?>
                            <div class="hph-performance-listing-item">
                                <div class="hph-performance-listing-thumbnail">
                                    <?php if (!empty($listing['thumbnail'])) : ?>
                                        <img src="<?php echo esc_url($listing['thumbnail']); ?>"
                                            alt="<?php echo esc_attr($listing['title']); ?>">
                                    <?php else : ?>
                                        <div class="hph-placeholder-image">
                                            <i class="fas fa-home"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="hph-performance-listing-details">
                                    <h4 class="hph-performance-listing-title">
                                        <a href="<?php echo esc_url($listing['permalink']); ?>" target="_blank">
                                            <?php echo esc_html($listing['title']); ?>
                                        </a>
                                    </h4>
                                    <p class="hph-performance-listing-address">
                                        <?php echo esc_html($listing['address']); ?>
                                    </p>
                                    <div class="hph-performance-listing-price">
                                        <?php echo esc_html($listing['price_formatted']); ?>
                                    </div>
                                </div>
                                <div class="hph-performance-listing-stats">
                                    <div class="hph-performance-stat">
                                        <i class="fas fa-eye"></i>
                                        <span><?php echo number_format($listing['views'] ?? 0); ?></span>
                                        <small><?php esc_html_e('views', 'happy-place'); ?></small>
                                    </div>
                                    <div class="hph-performance-stat">
                                        <i class="fas fa-envelope"></i>
                                        <span><?php echo number_format($listing['inquiries'] ?? 0); ?></span>
                                        <small><?php esc_html_e('inquiries', 'happy-place'); ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="hph-empty-state">
                        <div class="hph-empty-state-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4 class="hph-empty-state-title"><?php esc_html_e('No Performance Data Yet', 'happy-place'); ?></h4>
                        <p class="hph-empty-state-message">
                            <?php esc_html_e('Add some listings to start tracking performance metrics.', 'happy-place'); ?>
                        </p>
                        <a href="<?php echo esc_url(add_query_arg('section', 'listings')); ?>"
                            class="hph-btn hph-btn--primary">
                            <i class="fas fa-plus"></i>
                            <?php esc_html_e('Add Listing', 'happy-place'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Integration -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize charts when performance section is active
        if (typeof Chart !== 'undefined') {
            initPerformanceCharts();
        }

        // Handle period selector changes
        const periodSelector = document.querySelector('[data-period-selector]');
        if (periodSelector) {
            periodSelector.addEventListener('change', function() {
                const period = this.value;
                // Update URL and reload charts
                const url = new URL(window.location);
                url.searchParams.set('period', period);
                window.history.pushState({}, '', url);

                if (typeof Chart !== 'undefined') {
                    updateCharts(period);
                }
            });
        }
    });

    function initPerformanceCharts() {
        // Views Chart
        const viewsCtx = document.getElementById('hph-views-chart');
        if (viewsCtx) {
            new Chart(viewsCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($performance_data['labels'] ?? []); ?>,
                    datasets: [{
                        label: '<?php esc_html_e('Views', 'happy-place'); ?>',
                        data: <?php echo json_encode($performance_data['views'] ?? []); ?>,
                        borderColor: 'rgba(81, 186, 224, 1)',
                        backgroundColor: 'rgba(81, 186, 224, 0.1)',
                        fill: true,
                        tension: 0.4
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

        // Inquiries Chart
        const inquiriesCtx = document.getElementById('hph-inquiries-chart');
        if (inquiriesCtx) {
            new Chart(inquiriesCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($performance_data['labels'] ?? []); ?>,
                    datasets: [{
                        label: '<?php esc_html_e('Inquiries', 'happy-place'); ?>',
                        data: <?php echo json_encode($performance_data['inquiries'] ?? []); ?>,
                        backgroundColor: 'rgba(34, 197, 94, 0.8)',
                        borderColor: 'rgba(34, 197, 94, 1)',
                        borderWidth: 1
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

    function updateCharts(period) {
        // Fetch new data for the selected period
        fetch(dashboardAjax.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'hph_get_performance_data',
                    period: period,
                    nonce: dashboardAjax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update chart data
                    // Implementation depends on Chart.js version and requirements
                    console.log('Performance data updated for period:', period);
                }
            })
            .catch(error => {
                console.error('Error updating charts:', error);
            });
    }
</script>