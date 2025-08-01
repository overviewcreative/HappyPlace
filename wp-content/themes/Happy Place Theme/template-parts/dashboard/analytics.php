<?php
/**
 * Dashboard Analytics Section
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

// Mock analytics data - replace with real data sources
$analytics_data = [
    'views_total' => 2847,
    'views_change' => 12.5,
    'inquiries_total' => 23,
    'inquiries_change' => -5.2,
    'leads_total' => 8,
    'leads_change' => 33.3,
    'conversion_rate' => 34.8
];
?>

<div class="hph-dashboard-analytics">
    
    <!-- Analytics Header -->
    <div class="analytics-header">
        <div class="header-content">
            <h2 class="page-title">Analytics & Performance</h2>
            <p class="page-subtitle">Track your listing performance and lead generation</p>
        </div>
        <div class="header-actions">
            <div class="date-range-selector">
                <select class="filter-control" onchange="updateAnalyticsData(this.value)">
                    <option value="7">Last 7 days</option>
                    <option value="30" selected>Last 30 days</option>
                    <option value="90">Last 90 days</option>
                    <option value="365">Last year</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="analytics-metrics">
        <div class="metric-card metric-card--views">
            <div class="metric-icon">
                <i class="fas fa-eye"></i>
            </div>
            <div class="metric-content">
                <div class="metric-value"><?php echo number_format($analytics_data['views_total']); ?></div>
                <div class="metric-label">Total Views</div>
                <div class="metric-change metric-change--positive">
                    <i class="fas fa-arrow-up"></i>
                    <span><?php echo $analytics_data['views_change']; ?>% vs last period</span>
                </div>
            </div>
        </div>
        
        <div class="metric-card metric-card--inquiries">
            <div class="metric-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="metric-content">
                <div class="metric-value"><?php echo number_format($analytics_data['inquiries_total']); ?></div>
                <div class="metric-label">Inquiries</div>
                <div class="metric-change metric-change--negative">
                    <i class="fas fa-arrow-down"></i>
                    <span><?php echo abs($analytics_data['inquiries_change']); ?>% vs last period</span>
                </div>
            </div>
        </div>
        
        <div class="metric-card metric-card--leads">
            <div class="metric-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="metric-content">
                <div class="metric-value"><?php echo number_format($analytics_data['leads_total']); ?></div>
                <div class="metric-label">Qualified Leads</div>
                <div class="metric-change metric-change--positive">
                    <i class="fas fa-arrow-up"></i>
                    <span><?php echo $analytics_data['leads_change']; ?>% vs last period</span>
                </div>
            </div>
        </div>
        
        <div class="metric-card metric-card--conversion">
            <div class="metric-icon">
                <i class="fas fa-percentage"></i>
            </div>
            <div class="metric-content">
                <div class="metric-value"><?php echo $analytics_data['conversion_rate']; ?>%</div>
                <div class="metric-label">Conversion Rate</div>
                <div class="metric-change metric-change--positive">
                    <i class="fas fa-arrow-up"></i>
                    <span>2.1% vs last period</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="analytics-charts">
        <div class="main-chart">
            <div class="chart-header">
                <h3 class="chart-title">Performance Over Time</h3>
                <div class="chart-filters">
                    <button class="filter-btn is-active" onclick="showChart('views')">Views</button>
                    <button class="filter-btn" onclick="showChart('inquiries')">Inquiries</button>
                    <button class="filter-btn" onclick="showChart('leads')">Leads</button>
                </div>
            </div>
            <div class="chart-body">
                <div class="chart-placeholder" id="main-chart">
                    <div class="placeholder-content">
                        <i class="fas fa-chart-line placeholder-icon"></i>
                        <p class="placeholder-text">Chart visualization would appear here</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="side-chart">
            <div class="chart-header">
                <h3 class="chart-title">Top Performing Listings</h3>
            </div>
            <div class="chart-body">
                <div class="top-listings">
                    <div class="listing-rank-item">
                        <div class="rank-position">1</div>
                        <div class="rank-content">
                            <h4>123 Main Street</h4>
                            <span class="rank-metric">487 views</span>
                        </div>
                    </div>
                    
                    <div class="listing-rank-item">
                        <div class="rank-position">2</div>
                        <div class="rank-content">
                            <h4>456 Oak Avenue</h4>
                            <span class="rank-metric">324 views</span>
                        </div>
                    </div>
                    
                    <div class="listing-rank-item">
                        <div class="rank-position">3</div>
                        <div class="rank-content">
                            <h4>789 Pine Road</h4>
                            <span class="rank-metric">298 views</span>
                        </div>
                    </div>
                    
                    <div class="listing-rank-item">
                        <div class="rank-position">4</div>
                        <div class="rank-content">
                            <h4>321 Elm Street</h4>
                            <span class="rank-metric">234 views</span>
                        </div>
                    </div>
                    
                    <div class="listing-rank-item">
                        <div class="rank-position">5</div>
                        <div class="rank-content">
                            <h4>654 Maple Drive</h4>
                            <span class="rank-metric">189 views</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Analytics Table -->
    <div class="analytics-table-section">
        <div class="section-header">
            <h3>Listing Performance Details</h3>
            <div class="table-actions">
                <button class="action-btn action-btn--secondary" onclick="exportAnalytics()">
                    <i class="fas fa-download"></i> Export Data
                </button>
            </div>
        </div>
        
        <div class="table-container">
            <table class="hph-dashboard-table analytics-table">
                <thead>
                    <tr>
                        <th>Listing</th>
                        <th>Views</th>
                        <th>Inquiries</th>
                        <th>Leads</th>
                        <th>Conversion Rate</th>
                        <th>Avg. Time on Page</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="listing-cell">
                                <strong>123 Main Street</strong>
                                <span class="address">Wilmington, DE</span>
                            </div>
                        </td>
                        <td><span class="metric-value">487</span></td>
                        <td><span class="metric-value">12</span></td>
                        <td><span class="metric-value">4</span></td>
                        <td><span class="conversion-rate">33.3%</span></td>
                        <td><span class="time-value">2:34</span></td>
                        <td>
                            <a href="#" class="action-link">View Details</a>
                        </td>
                    </tr>
                    
                    <tr>
                        <td>
                            <div class="listing-cell">
                                <strong>456 Oak Avenue</strong>
                                <span class="address">Newark, DE</span>
                            </div>
                        </td>
                        <td><span class="metric-value">324</span></td>
                        <td><span class="metric-value">8</span></td>
                        <td><span class="metric-value">2</span></td>
                        <td><span class="conversion-rate">25.0%</span></td>
                        <td><span class="time-value">1:47</span></td>
                        <td>
                            <a href="#" class="action-link">View Details</a>
                        </td>
                    </tr>
                    
                    <tr>
                        <td>
                            <div class="listing-cell">
                                <strong>789 Pine Road</strong>
                                <span class="address">Dover, DE</span>
                            </div>
                        </td>
                        <td><span class="metric-value">298</span></td>
                        <td><span class="metric-value">6</span></td>
                        <td><span class="metric-value">3</span></td>
                        <td><span class="conversion-rate">50.0%</span></td>
                        <td><span class="time-value">3:12</span></td>
                        <td>
                            <a href="#" class="action-link">View Details</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function updateAnalyticsData(period) {
    // Implementation for updating analytics based on selected time period
    console.log('Updating analytics for period:', period);
}

function showChart(type) {
    // Update chart based on selected metric
    document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('is-active'));
    event.target.classList.add('is-active');
    
    console.log('Showing chart for:', type);
}

function exportAnalytics() {
    // Implementation for exporting analytics data
    alert('Export functionality would be implemented here');
}
</script>
