/**
 * Dashboard Charts JavaScript
 * 
 * Handles chart rendering and data visualization for the agent dashboard,
 * including sales performance, market analytics, and property statistics.
 * 
 * @package HappyPlace
 */

(function($) {
    'use strict';

    class DashboardCharts {
        constructor() {
            this.charts = {};
            this.colors = {
                primary: '#3B82F6',
                secondary: '#10B981',
                accent: '#F59E0B',
                danger: '#EF4444',
                gray: '#6B7280'
            };
            this.init();
        }

        init() {
            this.initCharts();
            this.bindEvents();
            this.startAutoRefresh();
        }

        bindEvents() {
            // Date range picker
            $(document).on('change', '#chart-date-range', (e) => {
                this.updateChartsDateRange($(e.target).val());
            });

            // Chart type toggles
            $(document).on('click', '.hph-chart-toggle', (e) => {
                this.handleChartToggle(e);
            });

            // Refresh charts
            $(document).on('click', '.hph-refresh-charts', (e) => {
                e.preventDefault();
                this.refreshAllCharts();
            });

            // Export chart data
            $(document).on('click', '.hph-export-chart', (e) => {
                e.preventDefault();
                this.exportChartData($(e.target).data('chart'));
            });
        }

        initCharts() {
            // Check if Chart.js is available
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is required for dashboard charts');
                return;
            }

            this.initSalesChart();
            this.initListingsChart();
            this.initMarketTrendsChart();
            this.initPerformanceChart();
            this.initLeadsChart();
        }

        initSalesChart() {
            const $canvas = $('#sales-chart');
            if (!$canvas.length) return;

            const ctx = $canvas[0].getContext('2d');
            
            this.charts.sales = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: this.getLastTwelveMonths(),
                    datasets: [{
                        label: 'Sales Volume',
                        data: this.generateSampleData(12, 50000, 300000),
                        borderColor: this.colors.primary,
                        backgroundColor: this.colors.primary + '20',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Commission',
                        data: this.generateSampleData(12, 2000, 15000),
                        borderColor: this.colors.secondary,
                        backgroundColor: this.colors.secondary + '20',
                        tension: 0.4,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Sales Performance'
                        },
                        legend: {
                            position: 'top'
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
        }

        initListingsChart() {
            const $canvas = $('#listings-chart');
            if (!$canvas.length) return;

            const ctx = $canvas[0].getContext('2d');
            
            this.charts.listings = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Active', 'Pending', 'Sold', 'Expired'],
                    datasets: [{
                        data: [12, 5, 8, 2],
                        backgroundColor: [
                            this.colors.primary,
                            this.colors.accent,
                            this.colors.secondary,
                            this.colors.danger
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Listing Status'
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        initMarketTrendsChart() {
            const $canvas = $('#market-trends-chart');
            if (!$canvas.length) return;

            const ctx = $canvas[0].getContext('2d');
            
            this.charts.marketTrends = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: this.getLastSixMonths(),
                    datasets: [{
                        label: 'Average Sale Price',
                        data: this.generateSampleData(6, 400000, 600000),
                        backgroundColor: this.colors.primary,
                        borderColor: this.colors.primary,
                        borderWidth: 1
                    }, {
                        label: 'Days on Market',
                        data: this.generateSampleData(6, 20, 60),
                        backgroundColor: this.colors.secondary,
                        borderColor: this.colors.secondary,
                        borderWidth: 1,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Market Trends'
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            ticks: {
                                callback: function(value) {
                                    return '$' + (value / 1000) + 'K';
                                }
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false
                            },
                            ticks: {
                                callback: function(value) {
                                    return value + ' days';
                                }
                            }
                        }
                    }
                }
            });
        }

        initPerformanceChart() {
            const $canvas = $('#performance-chart');
            if (!$canvas.length) return;

            const ctx = $canvas[0].getContext('2d');
            
            this.charts.performance = new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: ['Sales Volume', 'Customer Satisfaction', 'Market Knowledge', 'Responsiveness', 'Negotiation', 'Marketing'],
                    datasets: [{
                        label: 'Your Performance',
                        data: [85, 92, 88, 95, 82, 90],
                        borderColor: this.colors.primary,
                        backgroundColor: this.colors.primary + '40',
                        pointBackgroundColor: this.colors.primary,
                        pointBorderColor: '#ffffff',
                        pointHoverBackgroundColor: '#ffffff',
                        pointHoverBorderColor: this.colors.primary
                    }, {
                        label: 'Team Average',
                        data: [78, 85, 82, 88, 79, 83],
                        borderColor: this.colors.gray,
                        backgroundColor: this.colors.gray + '20',
                        pointBackgroundColor: this.colors.gray,
                        pointBorderColor: '#ffffff',
                        pointHoverBackgroundColor: '#ffffff',
                        pointHoverBorderColor: this.colors.gray
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Performance Metrics'
                        }
                    },
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                stepSize: 20
                            }
                        }
                    }
                }
            });
        }

        initLeadsChart() {
            const $canvas = $('#leads-chart');
            if (!$canvas.length) return;

            const ctx = $canvas[0].getContext('2d');
            
            this.charts.leads = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Website', 'Referrals', 'Social Media', 'Open Houses', 'Cold Calls', 'Other'],
                    datasets: [{
                        label: 'Leads Generated',
                        data: [25, 18, 12, 8, 5, 3],
                        backgroundColor: [
                            this.colors.primary,
                            this.colors.secondary,
                            this.colors.accent,
                            this.colors.danger,
                            this.colors.gray,
                            '#8B5CF6'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Lead Sources'
                        },
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

        updateChartsDateRange(dateRange) {
            this.showLoadingState();
            
            const data = {
                action: 'hph_dashboard_chart_data',
                nonce: hph_ajax.nonce,
                date_range: dateRange
            };

            $.ajax({
                url: hph_ajax.ajax_url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.updateChartsWithData(response.data);
                    } else {
                        this.showErrorMessage('Failed to update charts');
                    }
                },
                error: () => {
                    this.showErrorMessage('Failed to load chart data');
                },
                complete: () => {
                    this.hideLoadingState();
                }
            });
        }

        updateChartsWithData(data) {
            Object.keys(data).forEach(chartType => {
                if (this.charts[chartType] && data[chartType]) {
                    const chart = this.charts[chartType];
                    const chartData = data[chartType];
                    
                    // Update chart data
                    chart.data.labels = chartData.labels || chart.data.labels;
                    chart.data.datasets.forEach((dataset, index) => {
                        if (chartData.datasets && chartData.datasets[index]) {
                            dataset.data = chartData.datasets[index].data;
                        }
                    });
                    
                    chart.update();
                }
            });
        }

        handleChartToggle(e) {
            e.preventDefault();
            
            const $button = $(e.target);
            const chartId = $button.data('chart');
            const chartType = $button.data('type');
            
            if (this.charts[chartId]) {
                this.charts[chartId].config.type = chartType;
                this.charts[chartId].update();
                
                // Update active button
                $button.siblings().removeClass('active');
                $button.addClass('active');
            }
        }

        refreshAllCharts() {
            this.showLoadingState();
            
            const data = {
                action: 'hph_refresh_dashboard_charts',
                nonce: hph_ajax.nonce
            };

            $.ajax({
                url: hph_ajax.ajax_url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.updateChartsWithData(response.data);
                        this.showSuccessMessage('Charts updated successfully');
                    } else {
                        this.showErrorMessage('Failed to refresh charts');
                    }
                },
                error: () => {
                    this.showErrorMessage('Failed to refresh charts');
                },
                complete: () => {
                    this.hideLoadingState();
                }
            });
        }

        exportChartData(chartId) {
            if (!this.charts[chartId]) return;
            
            const chart = this.charts[chartId];
            const chartData = chart.data;
            
            // Convert chart data to CSV
            let csv = 'Label,Value\n';
            chartData.labels.forEach((label, index) => {
                chartData.datasets.forEach(dataset => {
                    csv += `${label} (${dataset.label}),${dataset.data[index]}\n`;
                });
            });
            
            // Download CSV file
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${chartId}-data.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        }

        startAutoRefresh() {
            // Refresh charts every 5 minutes
            setInterval(() => {
                this.refreshAllCharts();
            }, 300000);
        }

        showLoadingState() {
            $('.hph-chart-container').addClass('loading');
            $('.hph-chart-loading').show();
        }

        hideLoadingState() {
            $('.hph-chart-container').removeClass('loading');
            $('.hph-chart-loading').hide();
        }

        getLastTwelveMonths() {
            const months = [];
            const now = new Date();
            
            for (let i = 11; i >= 0; i--) {
                const date = new Date(now.getFullYear(), now.getMonth() - i, 1);
                months.push(date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' }));
            }
            
            return months;
        }

        getLastSixMonths() {
            const months = [];
            const now = new Date();
            
            for (let i = 5; i >= 0; i--) {
                const date = new Date(now.getFullYear(), now.getMonth() - i, 1);
                months.push(date.toLocaleDateString('en-US', { month: 'short' }));
            }
            
            return months;
        }

        generateSampleData(count, min, max) {
            const data = [];
            for (let i = 0; i < count; i++) {
                data.push(Math.floor(Math.random() * (max - min + 1)) + min);
            }
            return data;
        }

        showSuccessMessage(message) {
            this.showMessage(message, 'success');
        }

        showErrorMessage(message) {
            this.showMessage(message, 'error');
        }

        showMessage(message, type) {
            const $message = $(`
                <div class="hph-message hph-message--${type}">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                    ${message}
                </div>
            `);

            $('body').append($message);
            $message.addClass('show');

            setTimeout(() => {
                $message.removeClass('show');
                setTimeout(() => $message.remove(), 300);
            }, 3000);
        }
    }

    // Initialize when DOM is ready
    $(document).ready(() => {
        if ($('.hph-dashboard-charts').length) {
            new DashboardCharts();
        }
    });

})(jQuery);
