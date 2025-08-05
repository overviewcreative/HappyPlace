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

// Use the Analytics Section class from the plugin
if (class_exists('HappyPlace\Dashboard\Sections\Analytics_Section')) {
    $analytics_section = new \HappyPlace\Dashboard\Sections\Analytics_Section();
    $analytics_section->render();
} else {
    ?>
    <div class="hph-empty-state">
        <div class="empty-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h4 class="empty-title"><?php _e('Analytics Section Not Available', 'happy-place'); ?></h4>
        <p class="empty-description"><?php _e('The analytics section is not properly loaded. Please check plugin configuration.', 'happy-place'); ?></p>
    </div>
    <?php
}
?>

<script>
// Analytics Section JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Analytics Period Selector
    const periodSelector = document.getElementById('analytics-period');
    if (periodSelector) {
        periodSelector.addEventListener('change', function() {
            updateAnalyticsData(this.value);
        });
    }
    
    // Export Report Button
    const exportReportBtn = document.getElementById('export-report-btn');
    if (exportReportBtn) {
        exportReportBtn.addEventListener('click', function() {
            exportReport();
        });
    }
    
    // Load initial charts
    loadAnalyticsCharts();
});

// Analytics Functions
function updateAnalyticsData(period) {
    if (!window.HphDashboard) {
        console.error('Dashboard object not available');
        return;
    }
    
    HphDashboard.showLoading();
    
    fetch(HphDashboard.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=hph_get_analytics_data&period=${period}&nonce=${HphDashboard.nonce}`
    })
    .then(response => response.json())
    .then(data => {
        HphDashboard.hideLoading();
        
        if (data.success) {
            updateAnalyticsDashboard(data.data);
            HphDashboard.showToast('Analytics updated successfully', 'success');
        } else {
            HphDashboard.showToast(data.data.message || 'Failed to update analytics', 'error');
        }
    })
    .catch(error => {
        HphDashboard.hideLoading();
        console.error('Error:', error);
        HphDashboard.showToast('An error occurred while updating analytics', 'error');
    });
}

function exportReport() {
    if (!window.HphDashboard) {
        console.error('Dashboard object not available');
        return;
    }
    
    const period = document.getElementById('analytics-period')?.value || '30d';
    
    HphDashboard.showLoading();
    
    fetch(HphDashboard.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=hph_export_report&period=${period}&format=csv&nonce=${HphDashboard.nonce}`
    })
    .then(response => response.json())
    .then(data => {
        HphDashboard.hideLoading();
        
        if (data.success) {
            if (data.data.download_url) {
                // Create a temporary link to download the file
                const link = document.createElement('a');
                link.href = data.data.download_url;
                link.download = data.data.filename || 'analytics-report.csv';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                HphDashboard.showToast('Report exported successfully', 'success');
            } else {
                HphDashboard.showToast('Export completed, but no download link provided', 'warning');
            }
        } else {
            HphDashboard.showToast(data.data.message || 'Failed to export report', 'error');
        }
    })
    .catch(error => {
        HphDashboard.hideLoading();
        console.error('Error:', error);
        HphDashboard.showToast('An error occurred while exporting the report', 'error');
    });
}

function loadAnalyticsCharts() {
    // Load chart data for traffic and listings performance
    if (!window.HphDashboard) {
        console.log('Dashboard object not available - charts will be static');
        return;
    }
    
    fetch(HphDashboard.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=hph_get_performance_metrics&nonce=${HphDashboard.nonce}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderCharts(data.data);
        } else {
            console.warn('Failed to load chart data:', data.data?.message);
        }
    })
    .catch(error => {
        console.error('Error loading chart data:', error);
    });
}

function updateAnalyticsDashboard(data) {
    // Update stat cards
    if (data.metrics) {
        updateStatCard('page_views', data.metrics.page_views);
        updateStatCard('unique_visitors', data.metrics.unique_visitors);
        updateStatCard('inquiries', data.metrics.inquiries);
        updateStatCard('conversion_rate', data.metrics.conversion_rate + '%');
    }
    
    // Update top listings if available
    if (data.top_listings) {
        updateTopListings(data.top_listings);
    }
    
    // Refresh charts
    if (data.charts) {
        renderCharts(data.charts);
    }
}

function updateStatCard(metric, value) {
    const statCard = document.querySelector(`[data-metric="${metric}"] .hph-stat-value`);
    if (statCard) {
        // Animate value change
        statCard.style.transform = 'scale(1.1)';
        setTimeout(() => {
            statCard.textContent = typeof value === 'number' ? number_format(value) : value;
            statCard.style.transform = 'scale(1)';
        }, 150);
    }
}

function updateTopListings(listings) {
    const topListingsContainer = document.getElementById('top-listings-body');
    if (!topListingsContainer || !listings.length) return;
    
    // Update with new listings data
    // This would involve rebuilding the list items with new data
    console.log('Updating top listings:', listings);
}

function renderCharts(chartData) {
    // This would integrate with a charting library like Chart.js or D3.js
    console.log('Rendering charts with data:', chartData);
    
    // For now, just update the placeholder text
    const trafficChart = document.getElementById('traffic-chart');
    const listingsChart = document.getElementById('listings-chart');
    
    if (trafficChart) {
        trafficChart.innerHTML = `
            <div class="hph-chart-placeholder" style="text-align: center; color: var(--hph-gray-500);">
                <i class="fas fa-chart-line" style="font-size: 48px; margin-bottom: var(--hph-spacing-4); color: var(--hph-primary-400);"></i>
                <p style="margin: 0; font-size: var(--hph-font-size-base);">Traffic data loaded successfully</p>
            </div>
        `;
    }
    
    if (listingsChart) {
        listingsChart.innerHTML = `
            <div class="hph-chart-placeholder" style="text-align: center; color: var(--hph-gray-500);">
                <i class="fas fa-chart-bar" style="font-size: 48px; margin-bottom: var(--hph-spacing-4); color: var(--hph-success-400);"></i>
                <p style="margin: 0; font-size: var(--hph-font-size-base);">Listings data loaded successfully</p>
            </div>
        `;
    }
}

// Utility function for number formatting
function number_format(num) {
    return new Intl.NumberFormat().format(num);
}
</script>
