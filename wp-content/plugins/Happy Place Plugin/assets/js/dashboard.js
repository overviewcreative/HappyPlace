/**
 * Happy Place Plugin - Dashboard JavaScript Entry Point
 * 
 * This file serves as the main entry point for dashboard functionality
 * across both admin and frontend contexts.
 */

// Import or include the main admin dashboard functionality
// Note: In production, this would import the admin-dashboard module
// For now, we'll create a lightweight frontend dashboard controller

const HPH_Dashboard = {
    /**
     * Initialize dashboard functionality
     */
    init: function() {
        console.log('HPH Dashboard module initialized');
        this.bindEvents();
        this.initWidgets();
        this.loadDashboardData();
    },

    /**
     * Bind dashboard events
     */
    bindEvents: function() {
        // Widget refresh buttons
        jQuery(document).on('click', '.dashboard-widget-refresh', this.handleWidgetRefresh.bind(this));
        
        // Dashboard tab switching
        jQuery(document).on('click', '.dashboard-tab', this.handleTabSwitch.bind(this));
        
        // Quick action buttons
        jQuery(document).on('click', '.dashboard-quick-action', this.handleQuickAction.bind(this));
        
        // Data export buttons
        jQuery(document).on('click', '.dashboard-export', this.handleDataExport.bind(this));
        
        // Real-time updates toggle
        jQuery(document).on('change', '.dashboard-realtime-toggle', this.handleRealtimeToggle.bind(this));
    },

    /**
     * Initialize dashboard widgets
     */
    initWidgets: function() {
        // Initialize charts
        this.initCharts();
        
        // Initialize stats counters
        this.initStatsCounters();
        
        // Initialize activity feed
        this.initActivityFeed();
        
        // Initialize quick stats
        this.initQuickStats();
    },

    /**
     * Load dashboard data
     */
    loadDashboardData: function() {
        // Load data for each widget
        this.loadListingsStats();
        this.loadLeadStats();
        this.loadAgentStats();
        this.loadRecentActivity();
    },

    /**
     * Initialize charts
     */
    initCharts: function() {
        // Check if Chart.js is available
        if (typeof Chart === 'undefined') {
            console.warn('Chart.js not loaded, skipping chart initialization');
            return;
        }

        // Initialize listings chart
        const listingsChartCtx = document.getElementById('listingsChart');
        if (listingsChartCtx) {
            this.initListingsChart(listingsChartCtx);
        }

        // Initialize leads chart
        const leadsChartCtx = document.getElementById('leadsChart');
        if (leadsChartCtx) {
            this.initLeadsChart(leadsChartCtx);
        }

        // Initialize performance chart
        const performanceChartCtx = document.getElementById('performanceChart');
        if (performanceChartCtx) {
            this.initPerformanceChart(performanceChartCtx);
        }
    },

    /**
     * Initialize stats counters with animation
     */
    initStatsCounters: function() {
        document.querySelectorAll('.stat-counter').forEach(counter => {
            const target = parseInt(counter.dataset.count);
            const duration = 2000; // 2 seconds
            const increment = target / (duration / 16); // 60fps
            let current = 0;

            const updateCounter = () => {
                current += increment;
                if (current < target) {
                    counter.textContent = Math.floor(current).toLocaleString();
                    requestAnimationFrame(updateCounter);
                } else {
                    counter.textContent = target.toLocaleString();
                }
            };

            // Start animation when element is visible
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        updateCounter();
                        observer.unobserve(entry.target);
                    }
                });
            });

            observer.observe(counter);
        });
    },

    /**
     * Initialize activity feed
     */
    initActivityFeed: function() {
        const activityFeed = document.querySelector('.dashboard-activity-feed');
        if (!activityFeed) return;

        // Load initial activities
        this.loadActivities(activityFeed);

        // Auto-refresh every 30 seconds
        setInterval(() => {
            this.loadActivities(activityFeed, true);
        }, 30000);
    },

    /**
     * Initialize quick stats
     */
    initQuickStats: function() {
        const quickStats = document.querySelectorAll('.quick-stat');
        quickStats.forEach(stat => {
            const type = stat.dataset.statType;
            this.loadQuickStat(stat, type);
        });
    },

    /**
     * Handle widget refresh
     */
    handleWidgetRefresh: function(e) {
        e.preventDefault();
        const button = e.currentTarget;
        const widget = button.closest('.dashboard-widget');
        const widgetType = widget.dataset.widgetType;

        // Add loading state
        widget.classList.add('loading');
        button.disabled = true;

        // Refresh widget data
        this.refreshWidget(widgetType).then(() => {
            widget.classList.remove('loading');
            button.disabled = false;
        }).catch(() => {
            widget.classList.remove('loading');
            button.disabled = false;
            alert('Error refreshing widget data');
        });
    },

    /**
     * Handle tab switching
     */
    handleTabSwitch: function(e) {
        e.preventDefault();
        const tab = e.currentTarget;
        const tabId = tab.dataset.tab;

        // Update active tab
        document.querySelectorAll('.dashboard-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');

        // Show corresponding content
        document.querySelectorAll('.dashboard-tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(tabId).classList.add('active');

        // Load tab-specific data
        this.loadTabData(tabId);
    },

    /**
     * Handle quick actions
     */
    handleQuickAction: function(e) {
        e.preventDefault();
        const button = e.currentTarget;
        const action = button.dataset.action;

        switch (action) {
            case 'create-listing':
                this.createNewListing();
                break;
            case 'import-data':
                this.showImportDialog();
                break;
            case 'export-data':
                this.exportData();
                break;
            case 'sync-data':
                this.syncData();
                break;
            case 'generate-report':
                this.generateReport();
                break;
            default:
                console.warn('Unknown quick action:', action);
        }
    },

    /**
     * Handle data export
     */
    handleDataExport: function(e) {
        e.preventDefault();
        const button = e.currentTarget;
        const exportType = button.dataset.exportType;
        const format = button.dataset.format || 'csv';

        this.exportData(exportType, format);
    },

    /**
     * Handle real-time updates toggle
     */
    handleRealtimeToggle: function(e) {
        const toggle = e.currentTarget;
        const isEnabled = toggle.checked;

        if (isEnabled) {
            this.enableRealtimeUpdates();
        } else {
            this.disableRealtimeUpdates();
        }

        // Save preference
        localStorage.setItem('dashboard-realtime', isEnabled);
    },

    /**
     * Load listings statistics
     */
    loadListingsStats: function() {
        jQuery.ajax({
            url: ajaxurl || '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'get_listings_stats',
                nonce: jQuery('#dashboard_nonce').val()
            },
            success: (response) => {
                if (response.success) {
                    this.updateListingsStats(response.data);
                }
            }
        });
    },

    /**
     * Load lead statistics
     */
    loadLeadStats: function() {
        jQuery.ajax({
            url: ajaxurl || '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'get_lead_stats',
                nonce: jQuery('#dashboard_nonce').val()
            },
            success: (response) => {
                if (response.success) {
                    this.updateLeadStats(response.data);
                }
            }
        });
    },

    /**
     * Load agent statistics
     */
    loadAgentStats: function() {
        jQuery.ajax({
            url: ajaxurl || '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'get_agent_stats',
                nonce: jQuery('#dashboard_nonce').val()
            },
            success: (response) => {
                if (response.success) {
                    this.updateAgentStats(response.data);
                }
            }
        });
    },

    /**
     * Load recent activity
     */
    loadRecentActivity: function() {
        jQuery.ajax({
            url: ajaxurl || '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'get_recent_activity',
                nonce: jQuery('#dashboard_nonce').val()
            },
            success: (response) => {
                if (response.success) {
                    this.updateActivityFeed(response.data);
                }
            }
        });
    },

    /**
     * Update listings statistics display
     */
    updateListingsStats: function(data) {
        document.querySelectorAll('[data-stat="total-listings"]').forEach(el => {
            el.textContent = data.total || 0;
        });
        document.querySelectorAll('[data-stat="active-listings"]').forEach(el => {
            el.textContent = data.active || 0;
        });
        document.querySelectorAll('[data-stat="sold-listings"]').forEach(el => {
            el.textContent = data.sold || 0;
        });
        document.querySelectorAll('[data-stat="pending-listings"]').forEach(el => {
            el.textContent = data.pending || 0;
        });
    },

    /**
     * Enable real-time updates
     */
    enableRealtimeUpdates: function() {
        console.log('Real-time updates enabled');
        
        // Set up periodic refresh
        this.realtimeInterval = setInterval(() => {
            this.loadDashboardData();
        }, 30000); // Update every 30 seconds
    },

    /**
     * Disable real-time updates
     */
    disableRealtimeUpdates: function() {
        console.log('Real-time updates disabled');
        
        if (this.realtimeInterval) {
            clearInterval(this.realtimeInterval);
            this.realtimeInterval = null;
        }
    },

    /**
     * Export data in specified format
     */
    exportData: function(type = 'all', format = 'csv') {
        const exportUrl = new URL('/wp-admin/admin-ajax.php', window.location.origin);
        exportUrl.searchParams.set('action', 'export_dashboard_data');
        exportUrl.searchParams.set('type', type);
        exportUrl.searchParams.set('format', format);
        exportUrl.searchParams.set('nonce', jQuery('#dashboard_nonce').val());

        // Create download link
        const link = document.createElement('a');
        link.href = exportUrl.toString();
        link.download = `dashboard-${type}-${new Date().toISOString().split('T')[0]}.${format}`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    },

    /**
     * Utility function to format numbers
     */
    formatNumber: function(num) {
        return new Intl.NumberFormat().format(num);
    },

    /**
     * Utility function to format currency
     */
    formatCurrency: function(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    },

    /**
     * Utility function to format dates
     */
    formatDate: function(date) {
        return new Intl.DateTimeFormat('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        }).format(new Date(date));
    }
};

// Initialize when DOM is ready
jQuery(document).ready(function() {
    HPH_Dashboard.init();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = HPH_Dashboard;
}

// Global namespace
window.HPH_Dashboard = HPH_Dashboard;