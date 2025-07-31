/**
 * Enhanced Admin JavaScript for Happy Place Plugin
 * 
 * Modern, responsive admin interface functionality
 * 
 * @package HappyPlace
 * @version 2.0.0
 */

(function($) {
    'use strict';

    /**
     * Main Admin Class
     */
    const HappyPlaceAdmin = {
        
        /**
         * Enhanced AJAX request with retry logic
         */
        makeAjaxRequest: function(action, data, options) {
            options = options || {};
            const maxRetries = options.maxRetries || 2;
            const retryDelay = options.retryDelay || 1000;
            
            const attemptRequest = (attempt) => {
                return new Promise((resolve, reject) => {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: action,
                            nonce: hph_admin_vars.nonce,
                            ...data
                        },
                        timeout: options.timeout || 30000,
                        success: function(response) {
                            if (response.success) {
                                resolve(response);
                            } else {
                                const error = new Error(response.data?.message || 'Request failed');
                                error.response = response;
                                reject(error);
                            }
                        },
                        error: function(xhr, status, error) {
                            const errorObj = new Error('AJAX Error: ' + status + ' - ' + error);
                            errorObj.xhr = xhr;
                            errorObj.status = status;
                            reject(errorObj);
                        }
                    });
                });
            };
            
            const executeWithRetry = async (attempt = 1) => {
                try {
                    return await attemptRequest(attempt);
                } catch (error) {
                    if (attempt < maxRetries && this.shouldRetry(error)) {
                        console.warn('AJAX request failed (attempt ' + attempt + '), retrying in ' + retryDelay + 'ms...', error);
                        await new Promise(resolve => setTimeout(resolve, retryDelay));
                        return executeWithRetry(attempt + 1);
                    } else {
                        throw error;
                    }
                }
            };
            
            return executeWithRetry();
        },
        
        /**
         * Determine if an error should trigger a retry
         */
        shouldRetry: function(error) {
            // Retry on network errors, timeouts, and 5xx server errors
            if (error.xhr) {
                const status = error.xhr.status;
                return status === 0 || status >= 500 || error.status === 'timeout';
            }
            return false;
        },
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initDashboard();
            this.initIntegrations();
            this.initSystemHealth();
            this.initDeveloperTools();
            this.initAjaxHandlers();
            this.loadDashboardData();
        },

        /**
         * Bind global events
         */
        bindEvents: function() {
            // Modal handling
            $(document).on('click', '.hph-modal-close, .hph-modal', function(e) {
                if (e.target === this) {
                    $('.hph-modal').fadeOut(300);
                }
            });

            // Quick actions
            $(document).on('click', '.hph-quick-action', this.handleQuickAction);
            
            // Refresh buttons
            $(document).on('click', '.hph-refresh', this.refreshSection);
            
            // Test actions
            $(document).on('click', '.hph-test-action', this.handleTestAction);
            
            // Integration actions
            $(document).on('click', '.hph-integration-action', this.handleIntegrationAction);
            
            // Developer actions
            $(document).on('click', '.hph-dev-action', this.handleDeveloperAction);
            
            // Real-time updates (every 30 seconds)
            setInterval(this.updateLiveData.bind(this), 30000);
        },

        /**
         * Initialize dashboard functionality
         */
        initDashboard: function() {
            // Animate stats on load
            this.animateStats();
            
            // Load recent activity
            this.loadRecentActivity();
            
            // Initialize dashboard widgets
            this.initDashboardWidgets();
        },

        /**
         * Animate dashboard statistics
         */
        animateStats: function() {
            $('.hph-stat-number').each(function() {
                const $this = $(this);
                const target = parseInt($this.text()) || 0;
                let current = 0;
                const increment = target / 50;
                
                const timer = setInterval(function() {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    $this.text(Math.floor(current));
                }, 20);
            });
        },

        /**
         * Load recent activity
         */
        loadRecentActivity: function() {
            const $container = $('.hph-activity-list');
            
            if (!$container.length) return;
            
            this.makeAjaxRequest('hph_get_recent_activity', {})
                .then(response => {
                    $container.html(response.data.html);
                })
                .catch(error => {
                    console.error('Failed to load recent activity:', error);
                    $container.html('<div class="hph-empty-state"><p>Failed to load recent activity</p></div>');
                });
        },

        /**
         * Initialize dashboard widgets
         */
        initDashboardWidgets: function() {
            // Make widgets sortable
            if ($('.hph-widget-area').length) {
                $('.hph-widget-area').sortable({
                    handle: '.hph-widget-header',
                    placeholder: 'hph-widget-placeholder',
                    update: this.saveWidgetOrder
                });
            }
        },

        /**
         * Save widget order
         */
        saveWidgetOrder: function(event, ui) {
            const order = $(this).sortable('toArray');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hph_save_widget_order',
                    nonce: hph_admin_vars.nonce,
                    order: order
                }
            });
        },

        /**
         * Initialize integrations functionality
         */
        initIntegrations: function() {
            // Auto-refresh integration status
            setInterval(this.refreshIntegrationStatus.bind(this), 60000);
        },

        /**
         * Refresh integration status
         */
        refreshIntegrationStatus: function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hph_get_integration_status',
                    nonce: hph_admin_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('.hph-integration-card').each(function() {
                            const $card = $(this);
                            const integration = $card.data('integration');
                            const status = response.data[integration];
                            
                            if (status) {
                                $card.removeClass('healthy warning critical')
                                     .addClass(status.status);
                                $card.find('.hph-integration-status').text(status.message);
                            }
                        });
                    }
                }
            });
        },

        /**
         * Initialize system health monitoring
         */
        initSystemHealth: function() {
            // Real-time health monitoring
            this.startHealthMonitoring();
        },

        /**
         * Start health monitoring
         */
        startHealthMonitoring: function() {
            setInterval(this.updateSystemHealth.bind(this), 15000);
        },

        /**
         * Update system health display
         */
        updateSystemHealth: function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hph_get_system_health',
                    nonce: hph_admin_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update health indicators
                        $.each(response.data.health, function(key, value) {
                            const $indicator = $('.hph-health-item[data-key="' + key + '"] .hph-status-indicator');
                            $indicator.removeClass('hph-ok hph-warning hph-critical')
                                     .addClass('hph-' + value.status);
                            
                            const $value = $('.hph-health-item[data-key="' + key + '"] .hph-health-value');
                            $value.text(value.message);
                        });
                        
                        // Update circuit breaker status
                        if (response.data.circuit_breakers) {
                            this.updateCircuitBreakers(response.data.circuit_breakers);
                        }
                    }
                }.bind(this)
            });
        },

        /**
         * Update circuit breaker displays
         */
        updateCircuitBreakers: function(breakers) {
            $.each(breakers, function(name, status) {
                const $breaker = $('.hph-circuit-breaker[data-breaker="' + name + '"]');
                if ($breaker.length) {
                    $breaker.removeClass('closed open half-open')
                           .addClass(status.state.toLowerCase().replace('_', '-'));
                    $breaker.find('.hph-breaker-status').text(status.state);
                    $breaker.find('.hph-failure-count').text(status.failure_count);
                }
            });
        },

        /**
         * Initialize developer tools
         */
        initDeveloperTools: function() {
            // Code highlighting for logs
            if (typeof Prism !== 'undefined') {
                Prism.highlightAll();
            }
            
            // Console output handling
            this.initConsoleOutput();
        },

        /**
         * Initialize console output
         */
        initConsoleOutput: function() {
            const $console = $('.hph-console-output');
            if ($console.length) {
                // Auto-scroll to bottom
                $console.scrollTop($console[0].scrollHeight);
            }
        },

        /**
         * Initialize AJAX handlers
         */
        initAjaxHandlers: function() {
            // Global AJAX error handling
            $(document).ajaxError(function(event, xhr, settings, error) {
                console.error('AJAX Error:', error);
                this.showNotification('AJAX request failed: ' + error, 'error');
            }.bind(this));
            
            // Global AJAX loading indicator
            $(document).ajaxStart(function() {
                $('.hph-ajax-loader').show();
            }).ajaxStop(function() {
                $('.hph-ajax-loader').hide();
            });
        },

        /**
         * Load dashboard data
         */
        loadDashboardData: function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hph_get_dashboard_data',
                    nonce: hph_admin_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        this.updateDashboardStats(response.data.stats);
                        this.updateSystemOverview(response.data.system);
                    }
                }.bind(this)
            });
        },

        /**
         * Update dashboard statistics
         */
        updateDashboardStats: function(stats) {
            $.each(stats, function(key, value) {
                const $stat = $('.hph-stat-card[data-stat="' + key + '"] .hph-stat-number');
                if ($stat.length) {
                    $stat.text(value);
                }
            });
        },

        /**
         * Update system overview
         */
        updateSystemOverview: function(system) {
            $.each(system, function(key, value) {
                const $item = $('.hph-status-item[data-key="' + key + '"]');
                if ($item.length) {
                    $item.find('.hph-status-value').text(value.value);
                    $item.find('.hph-status-indicator')
                         .removeClass('hph-ok hph-warning hph-critical')
                         .addClass('hph-' + value.status);
                }
            });
        },

        /**
         * Handle quick actions
         */
        handleQuickAction: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const action = $button.data('action');
            const originalText = $button.text();
            
            $button.prop('disabled', true).text('Processing...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hph_quick_action',
                    quick_action: action,
                    nonce: hph_admin_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        HappyPlaceAdmin.showNotification(response.data.message, 'success');
                        if (response.data.refresh) {
                            location.reload();
                        }
                    } else {
                        HappyPlaceAdmin.showNotification(response.data.message || 'Action failed', 'error');
                    }
                },
                error: function() {
                    HappyPlaceAdmin.showNotification('Action failed', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Refresh section data
         */
        refreshSection: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const section = $button.data('section');
            const $container = $button.closest('.hph-card');
            
            $button.addClass('hph-spinning');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hph_refresh_section',
                    section: section,
                    nonce: hph_admin_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $container.html(response.data.html);
                        HappyPlaceAdmin.showNotification('Section refreshed', 'success');
                    }
                },
                complete: function() {
                    $button.removeClass('hph-spinning');
                }
            });
        },

        /**
         * Handle test actions
         */
        handleTestAction: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const test = $button.data('test');
            const $results = $button.siblings('.hph-test-results');
            
            $button.prop('disabled', true).text('Running...');
            $results.html('<div class="hph-loading">Running test...</div>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hph_run_test',
                    test: test,
                    nonce: hph_admin_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $results.html('<pre>' + response.data.output + '</pre>');
                        const status = response.data.success ? 'success' : 'error';
                        HappyPlaceAdmin.showNotification('Test completed', status);
                    } else {
                        $results.html('<pre class="error">Test failed: ' + (response.data.message || 'Unknown error') + '</pre>');
                    }
                },
                error: function() {
                    $results.html('<pre class="error">Test failed: Network error</pre>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Run Test');
                }
            });
        },

        /**
         * Handle integration actions
         */
        handleIntegrationAction: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const integration = $button.data('integration');
            const action = $button.data('action');
            
            $button.prop('disabled', true);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hph_integration_action',
                    integration: integration,
                    integration_action: action,
                    nonce: hph_admin_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        HappyPlaceAdmin.showNotification(response.data.message, 'success');
                        HappyPlaceAdmin.refreshIntegrationStatus();
                    } else {
                        HappyPlaceAdmin.showNotification(response.data.message || 'Action failed', 'error');
                    }
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        },

        /**
         * Handle developer actions
         */
        handleDeveloperAction: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const action = $button.data('action');
            const $output = $button.siblings('.hph-dev-output');
            
            if ($output.length) {
                $output.html('<div class="hph-loading">Processing...</div>');
            }
            
            $button.prop('disabled', true);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hph_developer_action',
                    dev_action: action,
                    nonce: hph_admin_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        if ($output.length && response.data.output) {
                            $output.html('<pre>' + response.data.output + '</pre>');
                        }
                        HappyPlaceAdmin.showNotification(response.data.message, 'success');
                    } else {
                        if ($output.length) {
                            $output.html('<pre class="error">' + (response.data.message || 'Action failed') + '</pre>');
                        }
                        HappyPlaceAdmin.showNotification(response.data.message || 'Action failed', 'error');
                    }
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        },

        /**
         * Update live data
         */
        updateLiveData: function() {
            // Only update if user is active (tab is visible)
            if (document.hidden) return;
            
            this.updateSystemHealth();
            this.refreshIntegrationStatus();
        },

        /**
         * Show notification
         */
        showNotification: function(message, type) {
            type = type || 'info';
            
            const $notification = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            
            $('.hph-admin-wrap').prepend($notification);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Show modal
         */
        showModal: function(content, title) {
            const modalHtml = `
                <div class="hph-modal">
                    <div class="hph-modal-content">
                        <div class="hph-modal-header">
                            <h2>${title || 'Modal'}</h2>
                            <button class="hph-modal-close">&times;</button>
                        </div>
                        <div class="hph-modal-body">
                            ${content}
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            $('.hph-modal').fadeIn(300);
        },

        /**
         * Format bytes to human readable
         */
        formatBytes: function(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        },

        /**
         * Format time ago
         */
        timeAgo: function(date) {
            const now = new Date();
            const diffInSeconds = Math.floor((now - new Date(date)) / 1000);
            
            if (diffInSeconds < 60) return 'just now';
            if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + ' minutes ago';
            if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + ' hours ago';
            
            return Math.floor(diffInSeconds / 86400) + ' days ago';
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        HappyPlaceAdmin.init();
    });

    /**
     * Expose to global scope for external access
     */
    window.HappyPlaceAdmin = HappyPlaceAdmin;

})(jQuery);

/**
 * CSS utilities for dynamic styling
 */
const HPH_Styles = {
    /**
     * Add spinning animation
     */
    addSpinning: function(element) {
        element.style.animation = 'hph-spin 1s linear infinite';
    },

    /**
     * Remove spinning animation
     */
    removeSpinning: function(element) {
        element.style.animation = '';
    },

    /**
     * Pulse effect
     */
    pulse: function(element) {
        element.style.animation = 'hph-pulse 0.5s ease-in-out';
        setTimeout(() => {
            element.style.animation = '';
        }, 500);
    }
};

/**
 * Add CSS animations
 */
const style = document.createElement('style');
style.textContent = `
    @keyframes hph-pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .hph-spinning {
        animation: hph-spin 1s linear infinite !important;
    }
`;
document.head.appendChild(style);
