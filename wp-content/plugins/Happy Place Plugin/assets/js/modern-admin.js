/**
 * Modern Admin JavaScript for Happy Place Plugin
 * Integrates with optimized AJAX system and provides clean UI interactions
 * Version 3.0.0
 */

(function($) {
    'use strict';

    // Modern Admin Object
    window.hphAdmin = {
        
        // Configuration
        config: {
            ajaxUrl: hphModernAdmin.ajaxUrl,
            nonce: hphModernAdmin.nonce,
            endpoints: hphModernAdmin.endpoints,
            capabilities: hphModernAdmin.capabilities,
            i18n: hphModernAdmin.i18n
        },

        // Cache for AJAX responses
        cache: new Map(),
        
        // Active requests to prevent duplicates
        activeRequests: new Set(),

        /**
         * Initialize modern admin interface
         */
        init: function() {
            console.log('HPH Modern Admin: Initializing...');
            console.log('HPH Modern Admin: Config:', this.config);
            
            this.bindEvents();
            this.loadPageData();
            this.setupAutoRefresh();
            console.log('HPH Modern Admin: Initialization complete');
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Format selection for marketing suite
            $(document).on('click', '.hph-format-option', this.handleFormatSelection.bind(this));
            
            // Tool launches
            $(document).on('click', '[data-tool]', this.handleToolLaunch.bind(this));
            
            // Refresh buttons
            $(document).on('click', '[onclick*="refresh"]', this.handleRefresh.bind(this));
            
            // Tab navigation
            $(document).on('click', '.hph-tab-nav a', this.handleTabSwitch.bind(this));
            
            // Form submissions
            $(document).on('submit', '.hph-ajax-form', this.handleFormSubmit.bind(this));
        },

        /**
         * Load page-specific data
         */
        loadPageData: function() {
            const page = $('.hph-modern-admin').data('page');
            console.log('HPH Modern Admin: Loading data for page:', page);
            
            switch(page) {
                case 'dashboard':
                    this.loadDashboardData();
                    break;
                case 'listings':
                    this.loadListingsData();
                    break;
                case 'marketing-suite':
                    this.initMarketingSuite();
                    break;
                case 'integrations':
                    this.loadIntegrationsData();
                    break;
                case 'system-health':
                    this.loadSystemHealthData();
                    break;
                default:
                    console.log('HPH Modern Admin: No specific data loading for page:', page);
            }
        },

        /**
         * Load dashboard statistics
         */
        loadDashboardData: function() {
            this.ajaxRequest('dashboard_quick_stats', {})
                .then(response => {
                    this.renderDashboardStats(response.data);
                })
                .catch(error => {
                    console.error('Dashboard stats failed:', error);
                    // Show fallback data
                    this.renderDashboardStats({
                        total_listings: '--',
                        total_views: '--',
                        active_integrations: '--',
                        health_status: 'unknown'
                    });
                    this.showError('Dashboard data unavailable (using fallback)');
                });

            // Try system validation but don't fail if unavailable
            this.ajaxRequest('validate_system', {})
                .then(response => {
                    this.renderSystemStatus(response.data);
                })
                .catch(error => {
                    console.warn('System status unavailable:', error);
                    this.renderSystemStatus({overall: 'unknown'});
                });
        },

        /**
         * Load listings overview
         */
        loadListingsData: function() {
            this.ajaxRequest('get_listings', {})
                .then(response => {
                    this.renderListingsOverview(response.data);
                })
                .catch(error => {
                    console.error('Listings data failed:', error);
                    // Show fallback - try to get listings from WordPress directly
                    this.renderListingsOverview([]);
                    this.showError('Listings data unavailable (using fallback)');
                });
        },

        /**
         * Initialize marketing suite
         */
        initMarketingSuite: function() {
            // Marketing suite is handled by existing marketing-suite-generator.js
            // We just need to ensure proper integration
            if (typeof window.marketingSuiteGenerator !== 'undefined') {
                console.log('Marketing Suite Generator already loaded');
            } else {
                // Load marketing suite generator if not already loaded
                this.loadMarketingSuiteGenerator();
            }
        },

        /**
         * Load marketing suite generator
         */
        loadMarketingSuiteGenerator: function() {
            const script = document.createElement('script');
            script.src = hphModernAdmin.pluginUrl + '/assets/js/marketing-suite-generator.js';
            script.onload = () => {
                console.log('Marketing Suite Generator loaded');
            };
            document.head.appendChild(script);

            const style = document.createElement('link');
            style.rel = 'stylesheet';
            style.href = hphModernAdmin.pluginUrl + '/assets/css/marketing-suite-generator.css';
            document.head.appendChild(style);
        },

        /**
         * Load integrations data
         */
        loadIntegrationsData: function() {
            this.ajaxRequest('integration_status', {})
                .then(response => {
                    this.renderIntegrationStatus(response.data);
                })
                .catch(error => {
                    this.showError('Failed to load integrations data');
                });
        },

        /**
         * Load system health data
         */
        loadSystemHealthData: function() {
            this.ajaxRequest('system_health', {})
                .then(response => {
                    this.renderSystemHealth(response.data);
                })
                .catch(error => {
                    this.showError('Failed to load system health data');
                });
        },

        /**
         * Handle format selection in marketing suite
         */
        handleFormatSelection: function(e) {
            e.preventDefault();
            
            const $option = $(e.currentTarget);
            const format = $option.data('format');
            
            // Update selection
            $('.hph-format-option').removeClass('selected');
            $option.addClass('selected');
            
            // Show generator interface
            this.showGeneratorInterface(format);
        },

        /**
         * Show marketing generator interface
         */
        showGeneratorInterface: function(format) {
            const $interface = $('#hph-generator-interface');
            $interface.show().addClass('hph-fade-in');
            
            // Initialize generator for specific format
            if (typeof window.marketingSuiteGenerator !== 'undefined') {
                window.marketingSuiteGenerator.initFormat(format);
            }
        },

        /**
         * Handle tool launches
         */
        handleToolLaunch: function(e) {
            e.preventDefault();
            
            const $tool = $(e.currentTarget);
            const toolId = $tool.data('tool');
            
            this.launchTool(toolId);
        },

        /**
         * Launch specific tool
         */
        launchTool: function(toolId) {
            console.log('Launching tool:', toolId);
            
            // Show loading state
            this.showNotice(this.config.i18n.loading + ' ' + toolId, 'info');
            
            // Load tool interface
            this.loadToolInterface(toolId);
        },

        /**
         * Load tool interface
         */
        loadToolInterface: function(toolId) {
            const $container = $('#hph-tool-interfaces');
            
            // Create modal or inline interface based on tool
            const $modal = this.createModal(toolId);
            $container.append($modal);
            
            // Load tool-specific content via AJAX
            this.ajaxRequest('load_tool', { tool: toolId })
                .then(response => {
                    $modal.find('.hph-modal-content').html(response.data.html);
                })
                .catch(error => {
                    this.showError('Failed to load tool: ' + toolId);
                });
        },

        /**
         * Create modal for tools
         */
        createModal: function(toolId) {
            const $modal = $(`
                <div class="hph-modal" id="hph-modal-${toolId}">
                    <div class="hph-modal-overlay"></div>
                    <div class="hph-modal-dialog">
                        <div class="hph-modal-header">
                            <h3>${toolId.replace('-', ' ').toUpperCase()}</h3>
                            <button class="hph-modal-close">&times;</button>
                        </div>
                        <div class="hph-modal-content">
                            <div class="hph-loading-placeholder">Loading tool...</div>
                        </div>
                    </div>
                </div>
            `);
            
            // Bind close events
            $modal.find('.hph-modal-close, .hph-modal-overlay').on('click', () => {
                $modal.remove();
            });
            
            return $modal;
        },

        /**
         * Handle refresh actions
         */
        handleRefresh: function(e) {
            e.preventDefault();
            
            const action = $(e.currentTarget).attr('onclick');
            
            if (action.includes('refreshListings')) {
                this.refreshListings();
            } else if (action.includes('refreshDashboard')) {
                this.loadDashboardData();
            }
        },

        /**
         * Refresh listings data
         */
        refreshListings: function() {
            this.showNotice(this.config.i18n.loading, 'info');
            this.cache.delete('get_listings');
            this.loadListingsData();
        },

        /**
         * Handle form submissions
         */
        handleFormSubmit: function(e) {
            e.preventDefault();
            
            const $form = $(e.currentTarget);
            const action = $form.data('action');
            const formData = new FormData($form[0]);
            
            this.ajaxRequest(action, formData)
                .then(response => {
                    this.showNotice(this.config.i18n.saved, 'success');
                })
                .catch(error => {
                    this.showError('Form submission failed');
                });
        },

        /**
         * Handle tab navigation
         */
        handleTabSwitch: function(e) {
            e.preventDefault();
            
            const $link = $(e.currentTarget);
            const targetTab = $link.attr('href');
            
            // Update active tab
            $link.closest('.hph-tab-nav').find('a').removeClass('nav-tab-active');
            $link.addClass('nav-tab-active');
            
            // Show target tab content
            $('.hph-tab-content').hide();
            $(targetTab).show();
            
            console.log('HPH Admin: Switched to tab:', targetTab);
        },

        /**
         * Centralized AJAX request handler
         */
        ajaxRequest: function(action, data = {}) {
            const cacheKey = action + JSON.stringify(data);
            
            // Debug logging
            console.log('HPH Admin: Making AJAX request for action:', action);
            
            // Check cache first (for GET-like requests)
            if (this.cache.has(cacheKey) && typeof data === 'object' && !data instanceof FormData) {
                console.log('HPH Admin: Using cached response for:', action);
                return Promise.resolve(this.cache.get(cacheKey));
            }
            
            // Prevent duplicate requests
            if (this.activeRequests.has(cacheKey)) {
                console.log('HPH Admin: Request already in progress for:', action);
                return Promise.reject(new Error('Request already in progress'));
            }
            
            this.activeRequests.add(cacheKey);
            
            const requestData = {
                action: 'hph_' + action,
                nonce: this.config.nonce,
                ...data
            };
            
            console.log('HPH Admin: Request data:', requestData);
            
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: this.config.ajaxUrl,
                    type: 'POST',
                    data: requestData,
                    dataType: 'json',
                    success: (response) => {
                        this.activeRequests.delete(cacheKey);
                        console.log('HPH Admin: Response for', action, ':', response);
                        
                        if (response.success) {
                            // Cache successful responses
                            this.cache.set(cacheKey, response);
                            resolve(response);
                        } else {
                            console.error('HPH Admin: AJAX error for', action, ':', response.data);
                            reject(new Error(response.data || 'Unknown error'));
                        }
                    },
                    error: (jqXHR, textStatus, errorThrown) => {
                        this.activeRequests.delete(cacheKey);
                        console.error('HPH Admin: AJAX request failed for', action, ':', textStatus, errorThrown);
                        reject(new Error(textStatus || errorThrown));
                    }
                });
            });
        },

        /**
         * Render dashboard statistics
         */
        renderDashboardStats: function(data) {
            const $container = $('#hph-dashboard-stats');
            
            if (!data) {
                $container.html('<div class="hph-stats-container"><p>Loading statistics...</p></div>');
                return;
            }
            
            // Handle both direct stats object and nested stats object
            const stats = data.stats || data;
            
            const html = `
                <div class="hph-stats-container">
                    <div class="hph-stat-card">
                        <div class="hph-stat-number">${stats.total_listings || stats.listings || 0}</div>
                        <div class="hph-stat-label">Active Listings</div>
                    </div>
                    <div class="hph-stat-card">
                        <div class="hph-stat-number">${stats.total_views || stats.views || 0}</div>
                        <div class="hph-stat-label">Total Views</div>
                    </div>
                    <div class="hph-stat-card">
                        <div class="hph-stat-number">${stats.active_integrations || stats.integrations || 0}</div>
                        <div class="hph-stat-label">Active Integrations</div>
                    </div>
                    <div class="hph-stat-card">
                        <div class="hph-stat-indicator hph-status-${stats.health_status || 'healthy'}">
                            <span class="dashicons dashicons-${stats.health_status === 'healthy' ? 'yes-alt' : 'warning'}"></span>
                        </div>
                        <div class="hph-stat-label">System Health</div>
                    </div>
                </div>
            `;
            
            $container.html(html).addClass('hph-fade-in');
        },

        /**
         * Render system status
         */
        renderSystemStatus: function(data) {
            const $container = $('#hph-system-status');
            
            if (!data) {
                $container.html('<p>System status unavailable</p>');
                return;
            }
            
            // Basic system status display
            const html = `
                <div class="hph-system-overview">
                    <div class="hph-status-item">
                        <span class="hph-status-indicator hph-status-${data.overall || 'healthy'}"></span>
                        <span>Overall Status: ${data.overall || 'Healthy'}</span>
                    </div>
                </div>
            `;
            
            $container.html(html).addClass('hph-fade-in');
        },

        /**
         * Render listings overview
         */
        renderListingsOverview: function(data) {
            const $container = $('#hph-listings-overview');
            
            if (!data) {
                $container.html('<p>Loading listings...</p>');
                return;
            }
            
            // Handle different data structures
            const listings = data.listings || data.data || data;
            
            if (!Array.isArray(listings) || listings.length === 0) {
                $container.html('<p>No listings found</p>');
                return;
            }
            
            // Basic listings display
            let html = '<div class="hph-listings-grid">';
            
            listings.forEach(listing => {
                html += `
                    <div class="hph-listing-card">
                        <h4>${listing.title || listing.post_title || 'Untitled Listing'}</h4>
                        <p>Status: ${listing.status || listing.post_status || 'Unknown'}</p>
                        <div class="hph-listing-actions">
                            <a href="${listing.edit_url || '#'}" class="button button-small">Edit</a>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            
            $container.html(html).addClass('hph-fade-in');
        },

        /**
         * Render integration status
         */
        renderIntegrationStatus: function(data) {
            const $container = $('#hph-integration-status');
            
            if (!data || !data.integrations) {
                $container.html('<p>No integrations configured</p>');
                return;
            }
            
            let html = '<div class="hph-integration-grid">';
            
            data.integrations.forEach(integration => {
                html += `
                    <div class="hph-integration-card">
                        <div class="hph-integration-icon">
                            <span class="dashicons dashicons-${integration.icon || 'admin-plugins'}"></span>
                        </div>
                        <div class="hph-integration-info">
                            <div class="hph-integration-name">${integration.name}</div>
                            <div class="hph-integration-status-text hph-status-${integration.status}">
                                ${integration.status_text}
                            </div>
                        </div>
                        <div class="hph-integration-actions">
                            <button class="button button-small" onclick="hphAdmin.toggleIntegration('${integration.id}')">
                                ${integration.active ? 'Disable' : 'Enable'}
                            </button>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            
            $container.html(html).addClass('hph-fade-in');
        },

        /**
         * Toggle integration
         */
        toggleIntegration: function(integrationId) {
            this.ajaxRequest('toggle_integration', { integration_id: integrationId })
                .then(response => {
                    this.showNotice('Integration toggled successfully', 'success');
                    this.loadIntegrationsData(); // Refresh
                })
                .catch(error => {
                    this.showError('Failed to toggle integration');
                });
        },

        /**
         * Setup auto-refresh for certain data
         */
        setupAutoRefresh: function() {
            // Refresh dashboard stats every 5 minutes
            setInterval(() => {
                if ($('.hph-modern-admin[data-page="dashboard"]').length) {
                    this.cache.delete('dashboard_quick_stats');
                    this.loadDashboardData();
                }
            }, 300000); // 5 minutes
        },

        /**
         * Developer Tools Functions
         */
        runSystemTest: function() {
            this.showNotice('Running system test...', 'info');
            
            this.ajaxRequest('validate_system', {})
                .then(response => {
                    this.displayTestResults(response.data);
                })
                .catch(error => {
                    this.showError('System test failed');
                });
        },

        /**
         * Clear all caches
         */
        clearAllCaches: function() {
            this.showNotice('Clearing caches...', 'info');
            
            this.ajaxRequest('refresh_cache', {})
                .then(response => {
                    this.showNotice('Caches cleared successfully', 'success');
                    this.cache.clear(); // Clear local cache too
                })
                .catch(error => {
                    this.showError('Failed to clear caches');
                });
        },

        regenerateAssets: function() {
            this.showNotice('Regenerating assets...', 'info');
            
            this.ajaxRequest('regenerate_assets', {})
                .then(response => {
                    this.showNotice('Assets regenerated successfully', 'success');
                })
                .catch(error => {
                    this.showError('Failed to regenerate assets');
                });
        },

        /**
         * Display test results
         */
        displayTestResults: function(results) {
            const $container = $('#hph-test-results');
            $container.show();
            
            let html = '<h3>System Test Results</h3>';
            html += '<div class="hph-test-results-grid">';
            
            Object.entries(results).forEach(([test, result]) => {
                const status = result.success ? 'success' : 'error';
                html += `
                    <div class="hph-test-result hph-test-${status}">
                        <strong>${test}</strong>: ${result.message}
                    </div>
                `;
            });
            
            html += '</div>';
            
            $container.html(html).addClass('hph-fade-in');
        },

        /**
         * Show notification
         */
        showNotice: function(message, type = 'info') {
            const $notice = $(`
                <div class="hph-notice notice-${type} is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);
            
            $('.hph-modern-admin').prepend($notice);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                $notice.fadeOut(() => $notice.remove());
            }, 5000);
            
            // Manual dismiss
            $notice.find('.notice-dismiss').on('click', () => {
                $notice.fadeOut(() => $notice.remove());
            });
        },

        /**
         * Show error notification
         */
        showError: function(message) {
            this.showNotice(message, 'error');
            console.error('HPH Admin Error:', message);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        hphAdmin.init();
    });

})(jQuery);
