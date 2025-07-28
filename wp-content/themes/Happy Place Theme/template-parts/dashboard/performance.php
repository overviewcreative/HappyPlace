<?php
/**
 * Dashboard Performance Section Template
 * 
 * Analytics and performance metrics for agent business
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get current user
$current_user = wp_get_current_user();
$current_agent_id = $current_user->ID;

// Get time period filter
$time_period = sanitize_text_field($_GET['period'] ?? 'last_30_days');

// Mock performance data (replace with actual database queries)
$performance_data = [
    'listings' => [
        'total' => 25,
        'active' => 18,
        'sold' => 7,
        'average_days_market' => 34,
        'views_total' => 1250,
        'inquiries_total' => 89
    ],
    'revenue' => [
        'total' => 245000,
        'average_per_sale' => 35000,
        'commission_rate' => 2.5,
        'goal' => 300000,
        'goal_progress' => 82
    ],
    'marketing' => [
        'flyers_created' => 45,
        'flyers_downloaded' => 230,
        'social_shares' => 156,
        'website_clicks' => 890
    ],
    'leads' => [
        'total' => 67,
        'qualified' => 34,
        'conversion_rate' => 51,
        'new_this_month' => 12
    ]
];

// Chart data for visualizations
$monthly_sales = [
    ['month' => 'Jan', 'sales' => 2, 'revenue' => 70000],
    ['month' => 'Feb', 'sales' => 1, 'revenue' => 35000],
    ['month' => 'Mar', 'sales' => 3, 'revenue' => 105000],
    ['month' => 'Apr', 'sales' => 1, 'revenue' => 35000],
];

$listing_views = [
    ['date' => '2024-01-01', 'views' => 45],
    ['date' => '2024-01-02', 'views' => 52],
    ['date' => '2024-01-03', 'views' => 48],
    ['date' => '2024-01-04', 'views' => 61],
    ['date' => '2024-01-05', 'views' => 55],
    ['date' => '2024-01-06', 'views' => 59],
    ['date' => '2024-01-07', 'views' => 67]
];
?>

<div class="hph-dashboard-performance">
    
    <!-- Performance Header -->
    <div class="hph-performance-header">
        <div class="hph-performance-title-group">
            <h2><?php esc_html_e('Performance Analytics', 'happy-place'); ?></h2>
            <p class="hph-performance-description">
                <?php esc_html_e('Track your business metrics, analyze trends, and monitor your progress toward your goals.', 'happy-place'); ?>
            </p>
        </div>
        <div class="hph-performance-controls">
            <select id="time-period-filter" class="hph-form-select">
                <option value="last_7_days" <?php selected($time_period, 'last_7_days'); ?>><?php esc_html_e('Last 7 Days', 'happy-place'); ?></option>
                <option value="last_30_days" <?php selected($time_period, 'last_30_days'); ?>><?php esc_html_e('Last 30 Days', 'happy-place'); ?></option>
                <option value="last_90_days" <?php selected($time_period, 'last_90_days'); ?>><?php esc_html_e('Last 90 Days', 'happy-place'); ?></option>
                <option value="this_year" <?php selected($time_period, 'this_year'); ?>><?php esc_html_e('This Year', 'happy-place'); ?></option>
                <option value="last_year" <?php selected($time_period, 'last_year'); ?>><?php esc_html_e('Last Year', 'happy-place'); ?></option>
            </select>
            <button type="button" class="hph-btn hph-btn--outline" onclick="downloadReport()">
                <i class="fas fa-download"></i>
                <?php esc_html_e('Export Report', 'happy-place'); ?>
            </button>
        </div>
    </div>

    <!-- Key Performance Metrics -->
    <div class="hph-performance-kpis">
        <div class="hph-kpi-card">
            <div class="hph-kpi-icon">
                <i class="fas fa-home"></i>
            </div>
            <div class="hph-kpi-content">
                <h3><?php echo $performance_data['listings']['total']; ?></h3>
                <p><?php esc_html_e('Total Listings', 'happy-place'); ?></p>
                <div class="hph-kpi-detail">
                    <?php echo $performance_data['listings']['active']; ?> active • <?php echo $performance_data['listings']['sold']; ?> sold
                </div>
            </div>
            <div class="hph-kpi-trend hph-kpi-trend--positive">
                <i class="fas fa-arrow-up"></i>
                <span>+12%</span>
            </div>
        </div>

        <div class="hph-kpi-card">
            <div class="hph-kpi-icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="hph-kpi-content">
                <h3>$<?php echo number_format($performance_data['revenue']['total']); ?></h3>
                <p><?php esc_html_e('Total Revenue', 'happy-place'); ?></p>
                <div class="hph-kpi-detail">
                    $<?php echo number_format($performance_data['revenue']['average_per_sale']); ?> avg per sale
                </div>
            </div>
            <div class="hph-kpi-trend hph-kpi-trend--positive">
                <i class="fas fa-arrow-up"></i>
                <span>+18%</span>
            </div>
        </div>

        <div class="hph-kpi-card">
            <div class="hph-kpi-icon">
                <i class="fas fa-eye"></i>
            </div>
            <div class="hph-kpi-content">
                <h3><?php echo number_format($performance_data['listings']['views_total']); ?></h3>
                <p><?php esc_html_e('Total Views', 'happy-place'); ?></p>
                <div class="hph-kpi-detail">
                    <?php echo $performance_data['listings']['inquiries_total']; ?> inquiries generated
                </div>
            </div>
            <div class="hph-kpi-trend hph-kpi-trend--positive">
                <i class="fas fa-arrow-up"></i>
                <span>+25%</span>
            </div>
        </div>

        <div class="hph-kpi-card">
            <div class="hph-kpi-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="hph-kpi-content">
                <h3><?php echo $performance_data['leads']['conversion_rate']; ?>%</h3>
                <p><?php esc_html_e('Conversion Rate', 'happy-place'); ?></p>
                <div class="hph-kpi-detail">
                    <?php echo $performance_data['leads']['qualified']; ?> of <?php echo $performance_data['leads']['total']; ?> leads qualified
                </div>
            </div>
            <div class="hph-kpi-trend hph-kpi-trend--neutral">
                <i class="fas fa-minus"></i>
                <span>0%</span>
            </div>
        </div>
    </div>

    <!-- Charts and Analytics -->
    <div class="hph-performance-charts">
        
        <!-- Revenue Chart -->
        <div class="hph-chart-widget">
            <div class="hph-widget-header">
                <h3 class="hph-widget-title">
                    <i class="fas fa-chart-line"></i>
                    <?php esc_html_e('Revenue Trend', 'happy-place'); ?>
                </h3>
                <div class="hph-widget-actions">
                    <button type="button" class="hph-btn hph-btn--sm hph-btn--outline" onclick="toggleChartType('revenue')">
                        <i class="fas fa-chart-bar"></i>
                    </button>
                </div>
            </div>
            <div class="hph-widget-content">
                <canvas id="revenue-chart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Listing Views Chart -->
        <div class="hph-chart-widget">
            <div class="hph-widget-header">
                <h3 class="hph-widget-title">
                    <i class="fas fa-eye"></i>
                    <?php esc_html_e('Listing Views', 'happy-place'); ?>
                </h3>
                <div class="hph-widget-actions">
                    <button type="button" class="hph-btn hph-btn--sm hph-btn--outline" onclick="toggleChartType('views')">
                        <i class="fas fa-chart-area"></i>
                    </button>
                </div>
            </div>
            <div class="hph-widget-content">
                <canvas id="views-chart" width="400" height="200"></canvas>
            </div>
        </div>

    </div>

    <!-- Performance Details Grid -->
    <div class="hph-performance-details">
        
        <!-- Goal Progress -->
        <div class="hph-performance-widget">
            <div class="hph-widget-header">
                <h3 class="hph-widget-title">
                    <i class="fas fa-target"></i>
                    <?php esc_html_e('Annual Goals', 'happy-place'); ?>
                </h3>
                <button type="button" class="hph-btn hph-btn--sm hph-btn--outline" onclick="editGoals()">
                    <i class="fas fa-edit"></i>
                    <?php esc_html_e('Edit Goals', 'happy-place'); ?>
                </button>
            </div>
            <div class="hph-widget-content">
                <div class="hph-goal-item">
                    <div class="hph-goal-header">
                        <span class="hph-goal-label"><?php esc_html_e('Revenue Goal', 'happy-place'); ?></span>
                        <span class="hph-goal-value">
                            $<?php echo number_format($performance_data['revenue']['total']); ?> / $<?php echo number_format($performance_data['revenue']['goal']); ?>
                        </span>
                    </div>
                    <div class="hph-goal-progress">
                        <div class="hph-progress-bar">
                            <div class="hph-progress-fill" style="width: <?php echo $performance_data['revenue']['goal_progress']; ?>%"></div>
                        </div>
                        <span class="hph-progress-text"><?php echo $performance_data['revenue']['goal_progress']; ?>%</span>
                    </div>
                </div>
                
                <div class="hph-goal-item">
                    <div class="hph-goal-header">
                        <span class="hph-goal-label"><?php esc_html_e('Listings Goal', 'happy-place'); ?></span>
                        <span class="hph-goal-value">25 / 40</span>
                    </div>
                    <div class="hph-goal-progress">
                        <div class="hph-progress-bar">
                            <div class="hph-progress-fill" style="width: 62%"></div>
                        </div>
                        <span class="hph-progress-text">62%</span>
                    </div>
                </div>
                
                <div class="hph-goal-item">
                    <div class="hph-goal-header">
                        <span class="hph-goal-label"><?php esc_html_e('Clients Goal', 'happy-place'); ?></span>
                        <span class="hph-goal-value">34 / 50</span>
                    </div>
                    <div class="hph-goal-progress">
                        <div class="hph-progress-bar">
                            <div class="hph-progress-fill" style="width: 68%"></div>
                        </div>
                        <span class="hph-progress-text">68%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Performing Listings -->
        <div class="hph-performance-widget">
            <div class="hph-widget-header">
                <h3 class="hph-widget-title">
                    <i class="fas fa-star"></i>
                    <?php esc_html_e('Top Performing Listings', 'happy-place'); ?>
                </h3>
                <a href="<?php echo esc_url(add_query_arg('section', 'listings')); ?>" class="hph-widget-action">
                    <?php esc_html_e('View All', 'happy-place'); ?>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="hph-widget-content">
                <div class="hph-performance-list">
                    <div class="hph-performance-item">
                        <div class="hph-performance-item-info">
                            <h4>Beautiful Downtown Condo</h4>
                            <p>123 Main St</p>
                        </div>
                        <div class="hph-performance-item-stats">
                            <span class="hph-stat">245 views</span>
                            <span class="hph-stat">12 inquiries</span>
                        </div>
                    </div>
                    
                    <div class="hph-performance-item">
                        <div class="hph-performance-item-info">
                            <h4>Modern Family Home</h4>
                            <p>456 Oak Ave</p>
                        </div>
                        <div class="hph-performance-item-stats">
                            <span class="hph-stat">198 views</span>
                            <span class="hph-stat">8 inquiries</span>
                        </div>
                    </div>
                    
                    <div class="hph-performance-item">
                        <div class="hph-performance-item-info">
                            <h4>Luxury Waterfront Property</h4>
                            <p>789 Lake Dr</p>
                        </div>
                        <div class="hph-performance-item-stats">
                            <span class="hph-stat">156 views</span>
                            <span class="hph-stat">15 inquiries</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Marketing Performance -->
        <div class="hph-performance-widget">
            <div class="hph-widget-header">
                <h3 class="hph-widget-title">
                    <i class="fas fa-palette"></i>
                    <?php esc_html_e('Marketing Performance', 'happy-place'); ?>
                </h3>
                <a href="<?php echo esc_url(add_query_arg('section', 'marketing')); ?>" class="hph-widget-action">
                    <?php esc_html_e('Marketing Tools', 'happy-place'); ?>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="hph-widget-content">
                <div class="hph-marketing-stats-grid">
                    <div class="hph-marketing-stat">
                        <div class="hph-stat-icon">
                            <i class="fas fa-palette"></i>
                        </div>
                        <div class="hph-stat-content">
                            <h4><?php echo $performance_data['marketing']['flyers_created']; ?></h4>
                            <p><?php esc_html_e('Flyers Created', 'happy-place'); ?></p>
                        </div>
                    </div>
                    
                    <div class="hph-marketing-stat">
                        <div class="hph-stat-icon">
                            <i class="fas fa-download"></i>
                        </div>
                        <div class="hph-stat-content">
                            <h4><?php echo $performance_data['marketing']['flyers_downloaded']; ?></h4>
                            <p><?php esc_html_e('Downloads', 'happy-place'); ?></p>
                        </div>
                    </div>
                    
                    <div class="hph-marketing-stat">
                        <div class="hph-stat-icon">
                            <i class="fas fa-share-alt"></i>
                        </div>
                        <div class="hph-stat-content">
                            <h4><?php echo $performance_data['marketing']['social_shares']; ?></h4>
                            <p><?php esc_html_e('Social Shares', 'happy-place'); ?></p>
                        </div>
                    </div>
                    
                    <div class="hph-marketing-stat">
                        <div class="hph-stat-icon">
                            <i class="fas fa-mouse-pointer"></i>
                        </div>
                        <div class="hph-stat-content">
                            <h4><?php echo $performance_data['marketing']['website_clicks']; ?></h4>
                            <p><?php esc_html_e('Website Clicks', 'happy-place'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity Summary -->
        <div class="hph-performance-widget hph-performance-widget--full">
            <div class="hph-widget-header">
                <h3 class="hph-widget-title">
                    <i class="fas fa-clock"></i>
                    <?php esc_html_e('Performance Summary', 'happy-place'); ?>
                </h3>
                <div class="hph-widget-actions">
                    <button type="button" class="hph-btn hph-btn--sm hph-btn--outline" onclick="refreshData()">
                        <i class="fas fa-refresh"></i>
                        <?php esc_html_e('Refresh', 'happy-place'); ?>
                    </button>
                </div>
            </div>
            <div class="hph-widget-content">
                <div class="hph-summary-grid">
                    <div class="hph-summary-section">
                        <h4><?php esc_html_e('This Month\'s Highlights', 'happy-place'); ?></h4>
                        <ul class="hph-summary-list">
                            <li><i class="fas fa-check text-success"></i> Closed 3 listings worth $105,000</li>
                            <li><i class="fas fa-check text-success"></i> Generated 45 new leads</li>
                            <li><i class="fas fa-check text-success"></i> Achieved 89% of monthly goal</li>
                            <li><i class="fas fa-check text-success"></i> Listed 5 new properties</li>
                        </ul>
                    </div>
                    
                    <div class="hph-summary-section">
                        <h4><?php esc_html_e('Areas for Improvement', 'happy-place'); ?></h4>
                        <ul class="hph-summary-list">
                            <li><i class="fas fa-arrow-up text-warning"></i> Increase listing views by improving photos</li>
                            <li><i class="fas fa-arrow-up text-warning"></i> Follow up with 12 pending leads</li>
                            <li><i class="fas fa-arrow-up text-warning"></i> Schedule more open houses</li>
                            <li><i class="fas fa-arrow-up text-warning"></i> Update 3 stale listings</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// Performance section JavaScript
let revenueChart, viewsChart;

jQuery(document).ready(function($) {
    initializeCharts();
    
    // Time period filter
    $('#time-period-filter').on('change', function() {
        const period = $(this).val();
        updateDataForPeriod(period);
    });
});

function initializeCharts() {
    // Initialize revenue chart
    const revenueCtx = document.getElementById('revenue-chart').getContext('2d');
    
    if (typeof Chart !== 'undefined') {
        revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthly_sales, 'month')); ?>,
                datasets: [{
                    label: '<?php esc_html_e('Revenue', 'happy-place'); ?>',
                    data: <?php echo json_encode(array_column($monthly_sales, 'revenue')); ?>,
                    borderColor: 'rgb(81, 186, 224)',
                    backgroundColor: 'rgba(81, 186, 224, 0.1)',
                    tension: 0.1,
                    fill: true
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
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        
        // Initialize views chart
        const viewsCtx = document.getElementById('views-chart').getContext('2d');
        viewsChart = new Chart(viewsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($listing_views, 'date')); ?>,
                datasets: [{
                    label: '<?php esc_html_e('Views', 'happy-place'); ?>',
                    data: <?php echo json_encode(array_column($listing_views, 'views')); ?>,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.1,
                    fill: true
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
    } else {
        console.warn('Chart.js not loaded');
    }
}

function updateDataForPeriod(period) {
    // AJAX call to get updated data
    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
        action: 'hph_get_performance_data',
        period: period,
        agent_id: <?php echo $current_agent_id; ?>,
        nonce: '<?php echo wp_create_nonce('hph_ajax_nonce'); ?>'
    }, function(response) {
        if (response.success) {
            updateChartsWithNewData(response.data);
            updateKPIs(response.data.kpis);
        }
    });
}

function updateChartsWithNewData(data) {
    if (revenueChart && data.revenue_data) {
        revenueChart.data.labels = data.revenue_data.labels;
        revenueChart.data.datasets[0].data = data.revenue_data.values;
        revenueChart.update();
    }
    
    if (viewsChart && data.views_data) {
        viewsChart.data.labels = data.views_data.labels;
        viewsChart.data.datasets[0].data = data.views_data.values;
        viewsChart.update();
    }
}

function updateKPIs(kpis) {
    // Update KPI cards with new data
    Object.keys(kpis).forEach(function(key) {
        const element = document.querySelector(`[data-kpi="${key}"]`);
        if (element) {
            element.textContent = kpis[key];
        }
    });
}

function toggleChartType(chartId) {
    // Toggle between chart types (line, bar, area)
    const chart = chartId === 'revenue' ? revenueChart : viewsChart;
    if (!chart) return;
    
    const currentType = chart.config.type;
    const newType = currentType === 'line' ? 'bar' : 'line';
    
    chart.config.type = newType;
    chart.update();
}

function downloadReport() {
    // Generate and download performance report
    window.open('<?php echo admin_url('admin-ajax.php'); ?>?action=hph_download_performance_report&agent_id=<?php echo $current_agent_id; ?>&period=' + $('#time-period-filter').val() + '&nonce=<?php echo wp_create_nonce('hph_ajax_nonce'); ?>');
}

function editGoals() {
    // Open goals editing modal
    alert('<?php esc_html_e('Goal editing feature coming soon!', 'happy-place'); ?>');
}

function refreshData() {
    // Refresh all performance data
    const currentPeriod = $('#time-period-filter').val();
    updateDataForPeriod(currentPeriod);
    
    // Show loading indicators
    $('.hph-kpi-card').addClass('loading');
    setTimeout(() => {
        $('.hph-kpi-card').removeClass('loading');
    }, 1000);
}
</script>

<style>
/* Performance Section Specific Styles */
.hph-dashboard-performance {
    display: flex;
    flex-direction: column;
    gap: var(--hph-spacing-6);
}

.hph-performance-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: var(--hph-spacing-4);
}

.hph-performance-title-group h2 {
    margin: 0 0 var(--hph-spacing-2) 0;
    font-size: var(--hph-font-size-2xl);
    font-weight: var(--hph-font-bold);
}

.hph-performance-description {
    margin: 0;
    color: var(--hph-color-gray-600);
}

.hph-performance-controls {
    display: flex;
    gap: var(--hph-spacing-3);
    align-items: center;
}

.hph-performance-kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--hph-spacing-4);
}

.hph-kpi-card {
    background: var(--hph-color-white);
    border-radius: var(--hph-radius-xl);
    padding: var(--hph-spacing-5);
    box-shadow: var(--hph-shadow-sm);
    border: 1px solid var(--hph-color-gray-200);
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-4);
    position: relative;
    transition: all 0.3s ease;
}

.hph-kpi-card:hover {
    box-shadow: var(--hph-shadow-md);
}

.hph-kpi-card.loading {
    opacity: 0.6;
}

.hph-kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--hph-radius-lg);
    background: var(--hph-color-primary-100);
    color: var(--hph-color-primary-600);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--hph-font-size-lg);
    flex-shrink: 0;
}

.hph-kpi-content {
    flex: 1;
}

.hph-kpi-content h3 {
    font-size: var(--hph-font-size-2xl);
    font-weight: var(--hph-font-bold);
    margin: 0 0 var(--hph-spacing-1) 0;
    color: var(--hph-color-gray-900);
}

.hph-kpi-content p {
    margin: 0 0 var(--hph-spacing-1) 0;
    color: var(--hph-color-gray-600);
    font-size: var(--hph-font-size-sm);
}

.hph-kpi-detail {
    font-size: var(--hph-font-size-xs);
    color: var(--hph-color-gray-500);
}

.hph-kpi-trend {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-1);
    font-size: var(--hph-font-size-sm);
    font-weight: var(--hph-font-medium);
}

.hph-kpi-trend--positive {
    color: var(--hph-color-success-600);
}

.hph-kpi-trend--negative {
    color: var(--hph-color-danger-600);
}

.hph-kpi-trend--neutral {
    color: var(--hph-color-gray-500);
}

.hph-performance-charts {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: var(--hph-spacing-5);
}

.hph-chart-widget {
    background: var(--hph-color-white);
    border-radius: var(--hph-radius-xl);
    box-shadow: var(--hph-shadow-sm);
    border: 1px solid var(--hph-color-gray-200);
    overflow: hidden;
}

.hph-chart-widget .hph-widget-content {
    padding: var(--hph-spacing-4);
    height: 300px;
    position: relative;
}

#revenue-chart,
#views-chart {
    max-width: 100%;
    max-height: 100%;
}

.hph-performance-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: var(--hph-spacing-5);
    align-items: start;
}

.hph-performance-widget--full {
    grid-column: 1 / -1;
}

.hph-goal-item {
    margin-bottom: var(--hph-spacing-4);
}

.hph-goal-item:last-child {
    margin-bottom: 0;
}

.hph-goal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--hph-spacing-2);
}

.hph-goal-label {
    font-weight: var(--hph-font-medium);
    color: var(--hph-color-gray-700);
}

.hph-goal-value {
    font-size: var(--hph-font-size-sm);
    color: var(--hph-color-gray-600);
}

.hph-goal-progress {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-3);
}

.hph-progress-bar {
    flex: 1;
    height: 8px;
    background: var(--hph-color-gray-200);
    border-radius: var(--hph-radius-full);
    overflow: hidden;
}

.hph-progress-fill {
    height: 100%;
    background: var(--hph-color-primary-500);
    border-radius: var(--hph-radius-full);
    transition: width 0.3s ease;
}

.hph-progress-text {
    font-size: var(--hph-font-size-sm);
    font-weight: var(--hph-font-medium);
    color: var(--hph-color-gray-700);
    min-width: 35px;
    text-align: right;
}

.hph-performance-list {
    display: flex;
    flex-direction: column;
    gap: var(--hph-spacing-3);
}

.hph-performance-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--hph-spacing-3);
    border: 1px solid var(--hph-color-gray-200);
    border-radius: var(--hph-radius-lg);
    transition: all 0.2s ease;
}

.hph-performance-item:hover {
    border-color: var(--hph-color-primary-300);
    background: var(--hph-color-primary-25);
}

.hph-performance-item-info h4 {
    margin: 0 0 var(--hph-spacing-1) 0;
    font-size: var(--hph-font-size-sm);
    font-weight: var(--hph-font-medium);
}

.hph-performance-item-info p {
    margin: 0;
    font-size: var(--hph-font-size-xs);
    color: var(--hph-color-gray-600);
}

.hph-performance-item-stats {
    display: flex;
    gap: var(--hph-spacing-2);
}

.hph-stat {
    font-size: var(--hph-font-size-xs);
    color: var(--hph-color-gray-600);
    background: var(--hph-color-gray-100);
    padding: var(--hph-spacing-1) var(--hph-spacing-2);
    border-radius: var(--hph-radius-md);
}

.hph-marketing-stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--hph-spacing-4);
}

.hph-marketing-stat {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-3);
    padding: var(--hph-spacing-3);
    border: 1px solid var(--hph-color-gray-200);
    border-radius: var(--hph-radius-lg);
    transition: all 0.2s ease;
}

.hph-marketing-stat:hover {
    border-color: var(--hph-color-primary-300);
    background: var(--hph-color-primary-25);
}

.hph-marketing-stat .hph-stat-icon {
    width: 32px;
    height: 32px;
    border-radius: var(--hph-radius-md);
    background: var(--hph-color-primary-100);
    color: var(--hph-color-primary-600);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--hph-font-size-sm);
}

.hph-marketing-stat .hph-stat-content h4 {
    margin: 0 0 var(--hph-spacing-1) 0;
    font-size: var(--hph-font-size-lg);
    font-weight: var(--hph-font-bold);
}

.hph-marketing-stat .hph-stat-content p {
    margin: 0;
    font-size: var(--hph-font-size-xs);
    color: var(--hph-color-gray-600);
}

.hph-summary-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--hph-spacing-6);
}

.hph-summary-section h4 {
    margin: 0 0 var(--hph-spacing-3) 0;
    font-size: var(--hph-font-size-lg);
    font-weight: var(--hph-font-semibold);
}

.hph-summary-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: var(--hph-spacing-2);
}

.hph-summary-list li {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-2);
    font-size: var(--hph-font-size-sm);
    color: var(--hph-color-gray-700);
}

.text-success {
    color: var(--hph-color-success-600) !important;
}

.text-warning {
    color: var(--hph-color-warning-600) !important;
}

@media (max-width: 1024px) {
    .hph-performance-charts {
        grid-template-columns: 1fr;
    }
    
    .hph-performance-details {
        grid-template-columns: 1fr;
    }
    
    .hph-summary-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .hph-performance-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .hph-performance-kpis {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .hph-kpi-card {
        flex-direction: column;
        text-align: center;
    }
    
    .hph-marketing-stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>
