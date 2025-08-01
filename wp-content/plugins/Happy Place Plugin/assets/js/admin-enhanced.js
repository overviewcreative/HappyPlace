/**
 * Enhanced Admin JavaScript for Happy Place Plugin
 * Provides AJAX functionality for admin pages
 * 
 * @package HappyPlace
 * @version 1.0.0
 */

(function($) {
    'use strict';

    // Main admin object
    window.hphAdmin = {
        
        /**
         * Make AJAX request to backend
         */
        ajax: function(action, data, callbacks) {
            const requestData = {
                action: action,
                nonce: hphEnhancedAdmin.nonce,
                ...data
            };

            const defaultCallbacks = {
                beforeSend: function() {
                    console.log('AJAX Request:', action, data);
                },
                success: function(response) {
                    console.log('AJAX Success:', response);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error, xhr.responseText);
                    this.showNotice('AJAX request failed: ' + error, 'error');
                }.bind(this)
            };

            $.ajax({
                url: hphEnhancedAdmin.ajaxUrl,
                type: 'POST',
                data: requestData,
                dataType: 'json',
                ...defaultCallbacks,
                ...callbacks
            });
        },

        /**
         * Test AJAX connectivity
         */
        testAjax: function() {
            console.log('Testing AJAX connectivity...');
            console.log('hphEnhancedAdmin config:', hphEnhancedAdmin);
            
            this.ajax('hph_dashboard_quick_stats', {}, {
                success: function(response) {
                    console.log('✅ AJAX Test Success:', response);
                    this.showNotice('AJAX connectivity test passed!', 'success');
                }.bind(this),
                error: function(xhr, status, error) {
                    console.error('❌ AJAX Test Failed:', error, xhr.responseText);
                    this.showNotice('AJAX connectivity test failed: ' + error, 'error');
                }.bind(this)
            });
        },

        /**
         * Load dashboard quick stats
         */
        loadDashboardStats: function() {
            this.ajax('hph_dashboard_quick_stats', {}, {
                success: function(response) {
                    if (response.success) {
                        this.renderDashboardStats(response.data);
                    }
                }.bind(this)
            });
        },

        /**
         * Load listings overview
         */
        /**
         * Load listings overview
         */
        loadListingsOverview: function(filters = {}) {
            this.ajax('hph_get_listings_overview', {
                filters: filters,
                page: 1,
                per_page: 20
            }, {
                success: function(response) {
                    if (response.success) {
                        this.renderListingsOverview(response.data);
                    }
                }.bind(this)
            });
        },

        /**
         * Load integration status  
         */
        loadIntegrationStatus: function(refresh = false) {
            this.ajax('hph_get_integration_status', { refresh: refresh }, {
                success: function(response) {
                    if (response.success) {
                        this.renderIntegrationStatus(response.data);
                    }
                }.bind(this)
            });
        },

        /**
         * Test integration connection
         */
        testIntegrationConnection: function(integration) {
            this.ajax('hph_test_integration_connection', {
                integration: integration
            }, {
                beforeSend: function() {
                    this.showLoading(`Testing ${integration} connection...`);
                }.bind(this),
                success: function(response) {
                    this.hideLoading();
                    if (response.success) {
                        this.showNotice(`${integration} connection successful!`, 'success');
                    } else {
                        this.showNotice(`${integration} connection failed: ${response.data.message}`, 'error');
                    }
                }.bind(this)
            });
        },

        /**
         * Test all integrations
         */
        testAllIntegrations: function() {
            const integrations = ['airtable', 'mls', 'google_maps', 'email_marketing'];
            let completed = 0;
            
            this.showNotice('Testing all integrations...', 'info');
            
            integrations.forEach(integration => {
                this.ajax('hph_test_integration_connection', { integration: integration }, {
                    success: function(response) {
                        completed++;
                        this.updateIntegrationStatus(integration, response.data);
                        
                        if (completed === integrations.length) {
                            this.showNotice('All integration tests completed', 'success');
                        }
                    }.bind(this)
                });
            });
        },

        /**
         * Load system metrics
         */
        loadSystemMetrics: function() {
            this.ajax('hph_get_system_metrics', {}, {
                success: function(response) {
                    if (response.success) {
                        this.renderSystemMetrics(response.data);
                    }
                }.bind(this)
            });
        },

        /**
         * Run maintenance task
         */
        runMaintenanceTask: function(task) {
            this.ajax('hph_run_maintenance_task', {
                task: task
            }, {
                beforeSend: function() {
                    this.showLoading(`Running ${task} task...`);
                }.bind(this),
                success: function(response) {
                    this.hideLoading();
                    if (response.success) {
                        this.showNotice(`${task} task completed successfully!`, 'success');
                        this.loadSystemMetrics(); // Refresh metrics
                    } else {
                        this.showNotice(`${task} task failed: ${response.data.message}`, 'error');
                    }
                }.bind(this)
            });
        },

        /**
         * Open tool interface
         */
        openTool: function(toolName) {
            console.log('Opening tool:', toolName);
            
            // Hide all tool interfaces first
            $('.hph-modal').hide();
            
            // Show the specific tool interface - map tool names to modal IDs
            const modalMap = {
                'csv-import': 'hph-csv-import-modal',
                'flyer-generator': 'hph-flyer-generator-modal',
                'image-optimization': 'hph-image-optimization-modal'
            };
            
            const modalId = modalMap[toolName];
            if (modalId) {
                const modal = $('#' + modalId);
                if (modal.length) {
                    modal.show();
                    this.showNotice(`Opened ${toolName} tool`, 'info');
                    
                    // Load tool content if needed
                    this.loadToolContent(toolName);
                } else {
                    this.showNotice(`Modal for ${toolName} not found`, 'error');
                    console.error('Modal not found:', modalId);
                }
            } else {
                this.showNotice(`Tool ${toolName} not configured`, 'error');
                console.error('Tool not mapped:', toolName);
            }
        },

        /**
         * Close tool interface
         */
        closeTool: function(toolName) {
            if (toolName) {
                const modalMap = {
                    'csv-import': 'hph-csv-import-modal',
                    'flyer-generator': 'hph-flyer-generator-modal',
                    'image-optimization': 'hph-image-optimization-modal'
                };
                
                const modalId = modalMap[toolName];
                if (modalId) {
                    $('#' + modalId).hide();
                }
            } else {
                // Close all modals
                $('.hph-modal').hide();
            }
            this.showNotice(`Closed tool`, 'info');
        },

        /**
         * Load tool content
         */
        loadToolContent: function(toolName) {
            console.log('Loading content for tool:', toolName);
            
            switch(toolName) {
                case 'csv-import':
                    this.loadCsvImportTool();
                    break;
                case 'flyer-generator':
                    this.loadFlyerGeneratorTool();
                    break;
                case 'image-optimization':
                    this.loadImageOptimizationTool();
                    break;
                default:
                    console.log('No specific content loader for tool:', toolName);
            }
        },

        /**
         * Load CSV import tool content
         */
        loadCsvImportTool: function() {
            const content = $('#hph-csv-import-content');
            content.html('<p>Loading CSV import tool...</p>');
            
            // This would normally load the CSV import interface via AJAX
            // For now, we'll show a placeholder
            setTimeout(() => {
                content.html(`
                    <div class="hph-csv-import-tool">
                        <h3>CSV Import Tool</h3>
                        <p>Upload a CSV file to import listings:</p>
                        <input type="file" accept=".csv" id="csv-file-input">
                        <button class="button button-primary" onclick="hphAdmin.processCsvImport()">Import CSV</button>
                    </div>
                `);
            }, 500);
        },

        /**
         * Load flyer generator tool content
         */
        loadFlyerGeneratorTool: function() {
            const content = $('#hph-flyer-generator-content');
            content.html('<p>Loading flyer generator...</p>');
            
            // This would normally redirect to the marketing suite
            setTimeout(() => {
                content.html(`
                    <div class="hph-flyer-generator-tool">
                        <h3>Flyer Generator</h3>
                        <p>The flyer generator is available in the Marketing Suite.</p>
                        <button class="button button-primary" onclick="window.location.href='admin.php?page=happy-place-marketing-suite'">Open Marketing Suite</button>
                    </div>
                `);
            }, 500);
        },

        /**
         * Load image optimization tool content
         */
        loadImageOptimizationTool: function() {
            const content = $('#hph-image-optimization-content');
            if (content.length === 0) {
                this.showNotice('Image optimization tool not available yet', 'warning');
                return;
            }
            
            content.html('<p>Loading image optimization tool...</p>');
            
            setTimeout(() => {
                content.html(`
                    <div class="hph-image-optimization-tool">
                        <h3>Image Optimization</h3>
                        <p>Tool coming soon...</p>
                    </div>
                `);
            }, 500);
        },

        /**
         * Process CSV import
         */
        processCsvImport: function() {
            const fileInput = document.getElementById('csv-file-input');
            if (!fileInput.files.length) {
                this.showNotice('Please select a CSV file first', 'error');
                return;
            }
            
            this.showNotice('CSV import functionality coming soon...', 'info');
        },

        // Rendering methods

        /**
         * Render dashboard stats
         */
        renderDashboardStats: function(stats) {
            let html = '<div class="hph-stats-grid">';
            
            Object.keys(stats).forEach(key => {
                const stat = stats[key];
                html += `
                    <div class="hph-stat-card">
                        <div class="stat-icon">
                            <span class="dashicons ${stat.icon || 'dashicons-chart-area'}"></span>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number">${stat.count || stat.value || 0}</div>
                            <div class="stat-label">${stat.label || key}</div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            $('#hph-dashboard-stats').html(html);
        },

        /**
         * Render listings overview
         */
        renderListingsOverview: function(data) {
            let html = '';
            
            if (data.listings && data.listings.length > 0) {
                // Stats summary
                html += `
                    <div class="hph-listings-summary">
                        <div class="hph-stat">
                            <span class="number">${data.total}</span>
                            <span class="label">Total Listings</span>
                        </div>
                        <div class="hph-stat">
                            <span class="number">${data.current_page}</span>
                            <span class="label">Current Page</span>
                        </div>
                        <div class="hph-stat">
                            <span class="number">${data.pages}</span>
                            <span class="label">Total Pages</span>
                        </div>
                    </div>
                `;

                // Listings table
                html += '<div class="hph-listings-table">';
                html += '<table class="wp-list-table widefat fixed striped">';
                html += '<thead><tr>';
                html += '<th><input type="checkbox" id="select-all-listings"></th>';
                html += '<th>Title</th>';
                html += '<th>Status</th>';
                html += '<th>Price</th>';
                html += '<th>Address</th>';
                html += '<th>Date</th>';
                html += '<th>Actions</th>';
                html += '</tr></thead>';
                html += '<tbody>';

                data.listings.forEach(listing => {
                    html += `
                        <tr>
                            <td><input type="checkbox" name="listing_ids[]" value="${listing.id}"></td>
                            <td><strong>${listing.title}</strong></td>
                            <td><span class="status-${listing.status}">${listing.status || 'N/A'}</span></td>
                            <td>${listing.price ? '$' + Number(listing.price).toLocaleString() : 'N/A'}</td>
                            <td>${listing.address || 'N/A'}</td>
                            <td>${listing.date_added || 'N/A'}</td>
                            <td><a href="${listing.edit_url}" class="button button-small">Edit</a></td>
                        </tr>
                    `;
                });

                html += '</tbody></table></div>';
            } else {
                html = `
                    <div class="hph-empty-state">
                        <span class="dashicons dashicons-admin-home"></span>
                        <h3>No listings found</h3>
                        <p>No listings match your current filters.</p>
                    </div>
                `;
            }

            $('#hph-listings-overview').html(html);
        },

        /**
         * Render integration status
         */
        renderIntegrationStatus: function(integrations) {
            let html = '';
            
            Object.keys(integrations).forEach(key => {
                const integration = integrations[key];
                const statusClass = integration.status?.status || 'warning';
                const statusText = integration.status?.message || 'Unknown';
                
                html += `
                    <div class="hph-integration-card" data-integration="${key}">
                        <div class="integration-header">
                            <h3>${integration.name}</h3>
                            <span class="integration-status status-${statusClass}">${statusText}</span>
                        </div>
                        <div class="integration-meta">
                            <div class="last-sync">Last sync: ${integration.last_sync}</div>
                        </div>
                        <div class="integration-actions">
                            <button class="button button-secondary test-integration" data-integration="${key}">
                                Test Connection
                            </button>
                            <a href="${integration.config_url}" class="button button-primary">Configure</a>
                        </div>
                    </div>
                `;
            });
            
            $('#hph-integration-status').html(html);
            
            // Bind test buttons
            $('.test-integration').click(function() {
                const integration = $(this).data('integration');
                hphAdmin.testIntegration(integration);
            });
        },

        /**
         * Update integration status
         */
        updateIntegrationStatus: function(integration, status) {
            const $card = $('[data-integration="' + integration + '"]');
            const statusClass = status.status || 'warning';
            const statusText = status.message || 'Unknown';
            
            $card.find('.integration-status')
                .removeClass('status-good status-warning status-error')
                .addClass('status-' + statusClass)
                .text(statusText);
        },

        /**
         * Render system metrics
         */
        renderSystemMetrics: function(metrics) {
            let html = '<div class="hph-metrics-grid">';
            
            Object.keys(metrics).forEach(key => {
                const metric = metrics[key];
                html += `
                    <div class="hph-metric-card">
                        <h3>${key.replace('_', ' ').toUpperCase()}</h3>
                        <div class="metric-details">
                `;
                
                if (typeof metric === 'object') {
                    Object.keys(metric).forEach(subKey => {
                        html += `<div class="metric-item">
                            <span class="label">${subKey.replace('_', ' ')}:</span>
                            <span class="value">${metric[subKey]}</span>
                        </div>`;
                    });
                } else {
                    html += `<div class="metric-value">${metric}</div>`;
                }
                
                html += `</div></div>`;
            });
            
            html += '</div>';
            $('#hph-system-metrics').html(html);
        },

        /**
         * Show admin notice
         */
        showNotice: function(message, type = 'info') {
            const $notice = $(`
                <div class="notice notice-${type} is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);
            
            $('.wrap h1').after($notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                $notice.fadeOut(() => $notice.remove());
            }, 5000);
            
            // Handle dismiss button
            $notice.find('.notice-dismiss').click(function() {
                $notice.fadeOut(() => $notice.remove());
            });
        },

        /**
         * Show loading indicator
         */
        showLoading: function(message = 'Loading...') {
            // Remove any existing loading indicators
            $('.hph-loading').remove();
            
            const $loading = $(`
                <div class="hph-loading" style="
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    background: rgba(255, 255, 255, 0.95);
                    padding: 20px;
                    border-radius: 5px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    z-index: 9999;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                ">
                    <div style="
                        width: 20px;
                        height: 20px;
                        border: 2px solid #ccc;
                        border-top-color: #007cba;
                        border-radius: 50%;
                        animation: spin 1s linear infinite;
                    "></div>
                    <span>${message}</span>
                </div>
            `);
            
            $('body').append($loading);
            
            // Add CSS animation if not already present
            if (!$('#hph-loading-styles').length) {
                $('head').append(`
                    <style id="hph-loading-styles">
                        @keyframes spin {
                            0% { transform: rotate(0deg); }
                            100% { transform: rotate(360deg); }
                        }
                    </style>
                `);
            }
        },

        /**
         * Hide loading indicator
         */
        hideLoading: function() {
            $('.hph-loading').fadeOut(300, function() {
                $(this).remove();
            });
        },

        /**
         * Initialize admin functionality
         */
        init: function() {
            console.log('Happy Place Enhanced Admin initialized');
            
            // Global handlers for bulk actions
            $(document).on('click', '#select-all-listings', function() {
                $('input[name="listing_ids[]"]').prop('checked', this.checked);
            });

            // Auto-load data based on current page
            const currentPage = hphEnhancedAdmin.currentPage;
            
            if (currentPage.includes('happy-place-dashboard')) {
                this.loadDashboardStats();
            }
            
            if (currentPage.includes('happy-place-system-health')) {
                this.loadSystemMetrics();
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if (typeof hphEnhancedAdmin !== 'undefined') {
            hphAdmin.init();
        }
    });

})(jQuery);
