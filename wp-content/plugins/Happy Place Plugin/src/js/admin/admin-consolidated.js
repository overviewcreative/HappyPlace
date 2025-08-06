/**
 * Consolidated Admin JavaScript - Happy Place Plugin
 *
 * Unified admin interface functionality consolidating:
 * - admin.js (27 lines)
 * - admin-sync.js (36 lines) 
 * - enhanced-admin.js (727 lines)
 * - admin-dashboard.js (709 lines)
 * - dashboard.js (441 lines)
 * Total: 1,940 lines → Consolidated unified system
 *
 * @package HappyPlace
 * @subpackage Assets\JavaScript
 * @since 3.0.0
 */

(function($) {
    'use strict';

    /**
     * Happy Place Admin - Consolidated Interface
     */
    window.HappyPlaceAdmin = {
        
        // Configuration
        config: {
            ajaxTimeout: 30000,
            maxRetries: 3,
            retryDelay: 1000,
            debounceDelay: 300
        },

        // State management
        state: {
            currentPage: '',
            isLoading: false,
            activeRequests: new Map(),
            cache: new Map()
        },

        /**
         * Initialize admin interface
         */
        init: function() {
            this.detectCurrentPage();
            this.initializeComponents();
            this.bindEvents();
            this.loadInitialData();
            console.log('HappyPlace Admin initialized');
        },

        /**
         * Detect current admin page
         */
        detectCurrentPage: function() {
            const urlParams = new URLSearchParams(window.location.search);
            this.state.currentPage = urlParams.get('page') || 'dashboard';
        },

        /**
         * Initialize page-specific components
         */
        initializeComponents: function() {
            // Settings management
            this.initSettingsManager();
            
            // Sync functionality
            this.initSyncManager();
            
            // Dashboard widgets
            this.initDashboard();
            
            // CSV operations
            this.initCSVManager();
            
            // System validation
            this.initSystemValidator();
            
            // Real-time features
            this.initRealTimeFeatures();
        },

        /**
         * Bind global events
         */
        bindEvents: function() {
            // Form submissions
            this.bindFormEvents();
            
            // Button clicks
            this.bindButtonEvents();
            
            // Tab switching
            this.bindTabEvents();
            
            // Search and filters
            this.bindSearchEvents();
            
            // File uploads
            this.bindUploadEvents();
        },

        /**
         * Enhanced AJAX request handler with retry logic
         */
        makeAjaxRequest: function(action, data, options) {
            options = $.extend({
                timeout: this.config.ajaxTimeout,
                maxRetries: this.config.maxRetries,
                retryDelay: this.config.retryDelay,
                cache: false,
                showLoader: true
            }, options);

            // Check cache first
            const cacheKey = action + '_' + JSON.stringify(data);
            if (options.cache && this.state.cache.has(cacheKey)) {
                return Promise.resolve(this.state.cache.get(cacheKey));
            }

            const requestId = this.generateRequestId();
            
            if (options.showLoader) {
                this.showLoader();
            }

            const attemptRequest = (attempt) => {
                return new Promise((resolve, reject) => {
                    const ajaxConfig = {
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: action,
                            nonce: hph_admin_vars?.nonce || '',
                            ...data
                        },
                        timeout: options.timeout,
                        success: (response) => {
                            if (response.success) {
                                // Cache successful responses
                                if (options.cache) {
                                    this.state.cache.set(cacheKey, response);
                                }
                                resolve(response);
                            } else {
                                const error = new Error(response.data?.message || 'Request failed');
                                error.response = response;
                                reject(error);
                            }
                        },
                        error: (xhr, status, error) => {
                            const errorObj = new Error(`AJAX Error: ${status} - ${error}`);
                            errorObj.xhr = xhr;
                            errorObj.status = status;
                            reject(errorObj);
                        }
                    };

                    this.state.activeRequests.set(requestId, $.ajax(ajaxConfig));
                });
            };

            const executeWithRetry = async (attempt = 1) => {
                try {
                    const result = await attemptRequest(attempt);
                    return result;
                } catch (error) {
                    if (attempt < options.maxRetries && this.shouldRetry(error)) {
                        console.warn(`Request failed, retrying... (${attempt}/${options.maxRetries})`);
                        await this.delay(options.retryDelay * attempt);
                        return executeWithRetry(attempt + 1);
                    }
                    throw error;
                }
            };

            return executeWithRetry()
                .finally(() => {
                    this.state.activeRequests.delete(requestId);
                    if (options.showLoader) {
                        this.hideLoader();
                    }
                });
        },

        /**
         * Settings Manager
         */
        initSettingsManager: function() {
            this.settings = {
                // Settings form handling
                bindFormSubmission: () => {
                    $('.happy-place-settings-form').on('submit', (e) => {
                        e.preventDefault();
                        this.saveSettings($(e.target));
                    });
                },

                // Settings save
                saveSettings: (form) => {
                    const formData = new FormData(form[0]);
                    const settings = {};
                    
                    // Convert FormData to object
                    for (let [key, value] of formData.entries()) {
                        settings[key] = value;
                    }

                    return this.makeAjaxRequest('hph_save_settings', { settings })
                        .then(response => {
                            this.showNotification('Settings saved successfully!', 'success');
                            return response;
                        })
                        .catch(error => {
                            this.showNotification('Failed to save settings: ' + error.message, 'error');
                            throw error;
                        });
                },

                // Reset settings
                resetSettings: () => {
                    if (!confirm('Are you sure you want to reset all settings to defaults?')) {
                        return;
                    }

                    return this.makeAjaxRequest('hph_reset_settings', {})
                        .then(response => {
                            this.showNotification('Settings reset to defaults', 'success');
                            location.reload();
                        })
                        .catch(error => {
                            this.showNotification('Failed to reset settings: ' + error.message, 'error');
                        });
                },

                // Export settings
                exportSettings: () => {
                    return this.makeAjaxRequest('hph_export_settings', {})
                        .then(response => {
                            const blob = new Blob([response.data.data], { type: 'application/json' });
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = response.data.filename;
                            a.click();
                            window.URL.revokeObjectURL(url);
                            this.showNotification('Settings exported successfully', 'success');
                        })
                        .catch(error => {
                            this.showNotification('Failed to export settings: ' + error.message, 'error');
                        });
                },

                // Import settings
                importSettings: (file) => {
                    return new Promise((resolve, reject) => {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            try {
                                const settingsData = e.target.result;
                                this.makeAjaxRequest('hph_import_settings', { settings_data: settingsData })
                                    .then(response => {
                                        this.showNotification('Settings imported successfully', 'success');
                                        location.reload();
                                        resolve(response);
                                    })
                                    .catch(reject);
                            } catch (error) {
                                reject(new Error('Invalid settings file'));
                            }
                        };
                        reader.readAsText(file);
                    });
                }
            };

            this.settings.bindFormSubmission();
        },

        /**
         * Sync Manager
         */
        initSyncManager: function() {
            this.sync = {
                // Manual sync
                triggerManualSync: () => {
                    const $button = $('#happy-place-manual-sync');
                    const $status = $('#sync-status');
                    
                    $button.prop('disabled', true);
                    $status.html('<div class="notice notice-info"><p>Synchronization in progress...</p></div>');

                    return this.makeAjaxRequest('hph_manual_sync', {})
                        .then(response => {
                            $status.html(`<div class="notice notice-success"><p>${response.data.message}</p></div>`);
                            this.updateSyncStatus(response.data);
                        })
                        .catch(error => {
                            $status.html(`<div class="notice notice-error"><p>Sync failed: ${error.message}</p></div>`);
                        })
                        .finally(() => {
                            $button.prop('disabled', false);
                        });
                },

                // Update sync status display
                updateSyncStatus: (data) => {
                    const statusElement = $('#last-sync-time');
                    if (statusElement.length) {
                        statusElement.text(data.timestamp || 'Just now');
                    }
                },

                // Auto-sync functionality
                initAutoSync: () => {
                    if (hph_admin_vars?.auto_sync_enabled) {
                        const interval = parseInt(hph_admin_vars.auto_sync_interval) || 300000; // 5 minutes
                        setInterval(() => {
                            this.sync.triggerManualSync();
                        }, interval);
                    }
                }
            };

            // Bind sync events
            $('#happy-place-manual-sync').on('click', (e) => {
                e.preventDefault();
                this.sync.triggerManualSync();
            });

            this.sync.initAutoSync();
        },

        /**
         * Dashboard Manager
         */
        initDashboard: function() {
            this.dashboard = {
                // Load dashboard widgets
                loadWidgets: () => {
                    const widgets = ['stats', 'recent_activity', 'sync_status', 'system_health'];
                    
                    widgets.forEach(widget => {
                        this.dashboard.loadWidget(widget);
                    });
                },

                // Load individual widget
                loadWidget: (widgetName) => {
                    const $widget = $(`.widget-${widgetName}`);
                    if (!$widget.length) return;

                    $widget.addClass('loading');

                    return this.makeAjaxRequest('hph_load_widget', { widget: widgetName }, { cache: true })
                        .then(response => {
                            $widget.find('.widget-content').html(response.data.content);
                        })
                        .catch(error => {
                            $widget.find('.widget-content').html(`<p class="error">Failed to load widget: ${error.message}</p>`);
                        })
                        .finally(() => {
                            $widget.removeClass('loading');
                        });
                },

                // Refresh all widgets
                refreshDashboard: () => {
                    this.state.cache.clear(); // Clear cache for fresh data
                    this.dashboard.loadWidgets();
                }
            };

            if (this.state.currentPage === 'dashboard' || this.state.currentPage === 'happy-place') {
                this.dashboard.loadWidgets();
            }
        },

        /**
         * CSV Manager
         */
        initCSVManager: function() {
            this.csv = {
                // CSV Export
                exportCSV: (dataType) => {
                    return this.makeAjaxRequest('hph_csv_export', { data_type: dataType })
                        .then(response => {
                            if (response.data.download_url) {
                                window.open(response.data.download_url, '_blank');
                            }
                            this.showNotification(`${dataType} data exported successfully`, 'success');
                        })
                        .catch(error => {
                            this.showNotification(`Failed to export ${dataType}: ${error.message}`, 'error');
                        });
                },

                // CSV Import
                importCSV: (file, importType) => {
                    return new Promise((resolve, reject) => {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            const csvData = e.target.result;
                            
                            // Validate first
                            this.makeAjaxRequest('hph_validate_csv', { csv_data: csvData, import_type: importType })
                                .then(validationResponse => {
                                    if (!validationResponse.data.validation.valid) {
                                        const errors = validationResponse.data.validation.errors.join(', ');
                                        throw new Error(`CSV validation failed: ${errors}`);
                                    }

                                    // If validation passes, import
                                    return this.makeAjaxRequest('hph_csv_import', { csv_data: csvData, import_type: importType });
                                })
                                .then(importResponse => {
                                    this.showNotification(`CSV import completed: ${importResponse.data.results.imported} records imported`, 'success');
                                    resolve(importResponse);
                                })
                                .catch(reject);
                        };
                        reader.readAsText(file);
                    });
                }
            };
        },

        /**
         * System Validator
         */
        initSystemValidator: function() {
            this.validator = {
                // Run system validation
                validateSystem: () => {
                    const $results = $('#system-validation-results');
                    $results.html('<div class="loading">Running system validation...</div>');

                    return this.makeAjaxRequest('hph_validate_system', {})
                        .then(response => {
                            this.validator.displayResults(response.data.results, response.data.status);
                        })
                        .catch(error => {
                            $results.html(`<div class="error">System validation failed: ${error.message}</div>`);
                        });
                },

                // Display validation results
                displayResults: (results, overallStatus) => {
                    const $results = $('#system-validation-results');
                    let html = `<div class="validation-summary status-${overallStatus}">
                        <h3>System Status: ${overallStatus.toUpperCase()}</h3>
                    </div>`;

                    Object.entries(results).forEach(([category, data]) => {
                        html += `<div class="validation-category">
                            <h4>${category.charAt(0).toUpperCase() + category.slice(1)}</h4>
                            <ul>`;
                        
                        Object.entries(data).forEach(([check, value]) => {
                            if (check !== 'issues') {
                                const status = value ? '✓' : '✗';
                                const statusClass = value ? 'pass' : 'fail';
                                html += `<li class="${statusClass}">${status} ${check.replace(/_/g, ' ')}</li>`;
                            }
                        });

                        if (data.issues && data.issues.length > 0) {
                            html += `<li class="issues">Issues: ${data.issues.join(', ')}</li>`;
                        }

                        html += '</ul></div>';
                    });

                    $results.html(html);
                }
            };
        },

        /**
         * Real-time Features
         */
        initRealTimeFeatures: function() {
            // Auto-refresh dashboard
            if (this.state.currentPage === 'dashboard') {
                setInterval(() => {
                    this.dashboard.refreshDashboard();
                }, 60000); // Refresh every minute
            }

            // Live form validation
            this.initLiveValidation();
        },

        /**
         * Live Form Validation
         */
        initLiveValidation: function() {
            $('form input, form textarea').on('blur', this.debounce((e) => {
                const $field = $(e.target);
                const fieldName = $field.attr('name');
                const fieldValue = $field.val();

                if (fieldName && fieldValue) {
                    this.validateField(fieldName, fieldValue, $field);
                }
            }, this.config.debounceDelay));
        },

        /**
         * Field Validation
         */
        validateField: function(fieldName, fieldValue, $field) {
            return this.makeAjaxRequest('hph_validate_field', {
                field_name: fieldName,
                field_value: fieldValue
            }, { showLoader: false })
                .then(response => {
                    this.updateFieldValidation($field, response.data.valid, response.data.message);
                })
                .catch(error => {
                    // Silently fail for live validation
                    console.warn('Field validation failed:', error);
                });
        },

        /**
         * Update Field Validation UI
         */
        updateFieldValidation: function($field, isValid, message) {
            $field.removeClass('valid invalid');
            $field.next('.validation-message').remove();

            if (isValid) {
                $field.addClass('valid');
            } else {
                $field.addClass('invalid');
                if (message) {
                    $field.after(`<span class="validation-message error">${message}</span>`);
                }
            }
        },

        /**
         * Event Binding Methods
         */
        bindFormEvents: function() {
            // Settings form
            $(document).on('submit', '.happy-place-settings-form', (e) => {
                e.preventDefault();
                this.settings.saveSettings($(e.target));
            });

            // CSV upload forms
            $(document).on('change', '.csv-upload-input', (e) => {
                const file = e.target.files[0];
                const importType = $(e.target).data('import-type');
                if (file && importType) {
                    this.csv.importCSV(file, importType);
                }
            });
        },

        bindButtonEvents: function() {
            // Reset settings
            $(document).on('click', '#reset-settings', (e) => {
                e.preventDefault();
                this.settings.resetSettings();
            });

            // Export settings
            $(document).on('click', '#export-settings', (e) => {
                e.preventDefault();
                this.settings.exportSettings();
            });

            // System validation
            $(document).on('click', '#validate-system', (e) => {
                e.preventDefault();
                this.validator.validateSystem();
            });

            // Refresh dashboard
            $(document).on('click', '#refresh-dashboard', (e) => {
                e.preventDefault();
                this.dashboard.refreshDashboard();
            });

            // CSV exports
            $(document).on('click', '.csv-export-btn', (e) => {
                e.preventDefault();
                const dataType = $(e.target).data('type');
                this.csv.exportCSV(dataType);
            });
        },

        bindTabEvents: function() {
            $(document).on('click', '.nav-tab', (e) => {
                e.preventDefault();
                const $tab = $(e.target);
                const targetPanel = $tab.attr('href');

                // Update tab states
                $('.nav-tab').removeClass('nav-tab-active');
                $tab.addClass('nav-tab-active');

                // Update panel visibility
                $('.tab-panel').hide();
                $(targetPanel).show();

                // Load panel content if needed
                this.loadTabContent(targetPanel);
            });
        },

        bindSearchEvents: function() {
            // Search with debouncing
            $(document).on('input', '.search-input', this.debounce((e) => {
                const query = $(e.target).val();
                this.performSearch(query);
            }, this.config.debounceDelay));
        },

        bindUploadEvents: function() {
            // Drag and drop
            $(document).on('dragover', '.upload-area', (e) => {
                e.preventDefault();
                $(e.target).addClass('dragover');
            });

            $(document).on('dragleave', '.upload-area', (e) => {
                e.preventDefault();
                $(e.target).removeClass('dragover');
            });

            $(document).on('drop', '.upload-area', (e) => {
                e.preventDefault();
                $(e.target).removeClass('dragover');
                
                const files = e.originalEvent.dataTransfer.files;
                this.handleFileUpload(files, $(e.target));
            });
        },

        /**
         * Utility Methods
         */
        generateRequestId: function() {
            return 'req_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        },

        shouldRetry: function(error) {
            // Retry on network errors, not on application errors
            return error.status !== 400 && error.status !== 403 && error.status !== 404;
        },

        delay: function(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },

        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func.apply(this, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        showLoader: function() {
            this.state.isLoading = true;
            $('.hph-loader').addClass('active');
        },

        hideLoader: function() {
            this.state.isLoading = false;
            $('.hph-loader').removeClass('active');
        },

        showNotification: function(message, type = 'info') {
            const $notification = $(`
                <div class="notice notice-${type} is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);

            $('.notices-container').append($notification);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                $notification.fadeOut(() => $notification.remove());
            }, 5000);
        },

        loadInitialData: function() {
            // Load any initial data needed for the current page
            if (this.state.currentPage === 'dashboard') {
                this.dashboard.loadWidgets();
            }
        },

        loadTabContent: function(tabId) {
            // Load content for specific tabs if needed
            const $panel = $(tabId);
            if ($panel.hasClass('lazy-load') && !$panel.hasClass('loaded')) {
                const contentType = $panel.data('content-type');
                if (contentType) {
                    this.makeAjaxRequest('hph_load_tab_content', { content_type: contentType })
                        .then(response => {
                            $panel.html(response.data.content);
                            $panel.addClass('loaded');
                        });
                }
            }
        },

        performSearch: function(query) {
            if (query.length < 3) return;

            const $results = $('.search-results');
            $results.html('<div class="loading">Searching...</div>');

            return this.makeAjaxRequest('hph_search', { query: query })
                .then(response => {
                    this.displaySearchResults(response.data.results);
                })
                .catch(error => {
                    $results.html(`<div class="error">Search failed: ${error.message}</div>`);
                });
        },

        displaySearchResults: function(results) {
            const $results = $('.search-results');
            
            if (results.length === 0) {
                $results.html('<div class="no-results">No results found</div>');
                return;
            }

            let html = '<ul class="search-results-list">';
            results.forEach(result => {
                html += `<li><a href="${result.url}">${result.title}</a></li>`;
            });
            html += '</ul>';

            $results.html(html);
        },

        handleFileUpload: function(files, $area) {
            const uploadType = $area.data('upload-type');
            
            Array.from(files).forEach(file => {
                this.uploadFile(file, uploadType);
            });
        },

        uploadFile: function(file, uploadType) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('upload_type', uploadType);
            formData.append('action', 'hph_upload_file');
            formData.append('nonce', hph_admin_vars?.nonce || '');

            return $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showNotification('File uploaded successfully', 'success');
                    } else {
                        this.showNotification('Upload failed: ' + response.data.message, 'error');
                    }
                },
                error: () => {
                    this.showNotification('Upload failed: Network error', 'error');
                }
            });
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        // Check if we have the required variables
        if (typeof hph_admin_vars === 'undefined') {
            console.warn('HPH Admin variables not loaded');
            return;
        }

        // Initialize the admin interface
        HappyPlaceAdmin.init();
    });

})(jQuery);
