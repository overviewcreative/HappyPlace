/**
 * Happy Place Plugin Admin Dashboard JavaScript
 */
(function($) {
    'use strict';

    const HPH_Admin = {
        init: function() {
            this.bindEvents();
            this.initTabs();
            this.checkSystemStatus();
        },

        bindEvents: function() {
            // Tab navigation
            $('.nav-tab').on('click', this.handleTabClick.bind(this));
            
            // Action buttons
            $('#export-data').on('click', this.handleExportData.bind(this));
            $('#cleanup-duplicates').on('click', this.handleCleanupDuplicates.bind(this));
            $('#validate-data').on('click', this.handleValidateData.bind(this));
            $('#create-backup').on('click', this.handleCreateBackup.bind(this));
            $('#restore-backup').on('click', this.handleRestoreBackup.bind(this));
            $('#sync-airtable').on('click', this.handleSyncAirtable.bind(this));
            $('#sync-mls').on('click', this.handleSyncMLS.bind(this));
            $('#sync-contacts').on('click', this.handleSyncContacts.bind(this));
            $('#optimize-images').on('click', this.handleOptimizeImages.bind(this));
            $('#check-images').on('click', this.handleCheckImages.bind(this));
            $('#optimize-seo').on('click', this.handleOptimizeSEO.bind(this));
            $('#generate-sitemaps').on('click', this.handleGenerateSitemaps.bind(this));
            $('#run-diagnostics').on('click', this.handleRunDiagnostics.bind(this));
            $('#clear-cache').on('click', this.handleClearCache.bind(this));
            $('#listing-report').on('click', this.handleListingReport.bind(this));
            $('#export-report').on('click', this.handleExportReport.bind(this));
            $('#agent-report').on('click', this.handleAgentReport.bind(this));
            $('#export-agent-data').on('click', this.handleExportAgentData.bind(this));
            $('#lead-analytics').on('click', this.handleLeadAnalytics.bind(this));
            $('#export-leads').on('click', this.handleExportLeads.bind(this));
        },

        initTabs: function() {
            // Show active tab content
            const activeTab = $('.nav-tab.nav-tab-active').attr('href');
            if (activeTab) {
                $(activeTab).addClass('active');
            }
        },

        handleTabClick: function(e) {
            e.preventDefault();
            
            const $tab = $(e.currentTarget);
            const target = $tab.attr('href');
            
            // Update active states
            $('.nav-tab').removeClass('nav-tab-active');
            $tab.addClass('nav-tab-active');
            
            $('.tab-panel').removeClass('active');
            $(target).addClass('active');
        },

        checkSystemStatus: function() {
            // Periodically check system status
            setInterval(() => {
                this.updateSystemStatus();
            }, 30000); // Check every 30 seconds
        },

        updateSystemStatus: function() {
            $.ajax({
                url: hphAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hph_check_system_status',
                    nonce: hphAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update status indicators
                        // Implementation would go here
                    }
                }
            });
        },

        showLoading: function($button) {
            const originalText = $button.text();
            $button.data('original-text', originalText);
            $button.prop('disabled', true);
            $button.html('<span class="hph-loading"></span> ' + hphAdmin.strings.saving);
        },

        hideLoading: function($button, newText = null) {
            const originalText = $button.data('original-text');
            $button.prop('disabled', false);
            $button.text(newText || originalText);
        },

        showMessage: function(message, type = 'success') {
            const messageHtml = `
                <div class="hph-message ${type}">
                    ${message}
                </div>
            `;
            
            $('.hph-admin-wrap h1').after(messageHtml);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                $('.hph-message').fadeOut(() => {
                    $('.hph-message').remove();
                });
            }, 5000);
        },

        // Data Management Actions
        handleExportData: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            
            this.showLoading($button);
            
            $.ajax({
                url: hphAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hph_export_data',
                    nonce: hphAdmin.nonce
                },
                success: (response) => {
                    this.hideLoading($button);
                    if (response.success) {
                        // Trigger download
                        window.location.href = response.data.download_url;
                        this.showMessage('Data export completed successfully!');
                    } else {
                        this.showMessage(response.data || 'Export failed', 'error');
                    }
                },
                error: () => {
                    this.hideLoading($button);
                    this.showMessage(hphAdmin.strings.error, 'error');
                }
            });
        },

        handleCleanupDuplicates: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            
            if (!confirm('This will scan for and remove duplicate entries. Continue?')) {
                return;
            }
            
            this.showLoading($button);
            
            $.ajax({
                url: hphAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hph_cleanup_duplicates',
                    nonce: hphAdmin.nonce
                },
                success: (response) => {
                    this.hideLoading($button);
                    if (response.success) {
                        this.showMessage(`Found and cleaned ${response.data.cleaned} duplicates`);
                    } else {
                        this.showMessage(response.data || 'Cleanup failed', 'error');
                    }
                },
                error: () => {
                    this.hideLoading($button);
                    this.showMessage(hphAdmin.strings.error, 'error');
                }
            });
        },

        handleValidateData: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            
            this.showLoading($button);
            
            $.ajax({
                url: hphAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hph_validate_data',
                    nonce: hphAdmin.nonce
                },
                success: (response) => {
                    this.hideLoading($button);
                    if (response.success) {
                        const issues = response.data.issues;
                        if (issues === 0) {
                            this.showMessage('Data validation completed - no issues found!');
                        } else {
                            this.showMessage(`Data validation found ${issues} issues`, 'warning');
                        }
                    } else {
                        this.showMessage(response.data || 'Validation failed', 'error');
                    }
                },
                error: () => {
                    this.hideLoading($button);
                    this.showMessage(hphAdmin.strings.error, 'error');
                }
            });
        },

        handleCreateBackup: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            
            this.showLoading($button);
            
            $.ajax({
                url: hphAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hph_create_backup',
                    nonce: hphAdmin.nonce
                },
                success: (response) => {
                    this.hideLoading($button);
                    if (response.success) {
                        this.showMessage('Backup created successfully!');
                    } else {
                        this.showMessage(response.data || 'Backup failed', 'error');
                    }
                },
                error: () => {
                    this.hideLoading($button);
                    this.showMessage(hphAdmin.strings.error, 'error');
                }
            });
        },

        handleRestoreBackup: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            
            if (!confirm('This will restore from the latest backup. All current data changes will be lost. Continue?')) {
                return;
            }
            
            this.showLoading($button);
            
            $.ajax({
                url: hphAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hph_restore_backup',
                    nonce: hphAdmin.nonce
                },
                success: (response) => {
                    this.hideLoading($button);
                    if (response.success) {
                        this.showMessage('Backup restored successfully!');
                    } else {
                        this.showMessage(response.data || 'Restore failed', 'error');
                    }
                },
                error: () => {
                    this.hideLoading($button);
                    this.showMessage(hphAdmin.strings.error, 'error');
                }
            });
        },

        // Integration Actions
        handleSyncAirtable: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            
            this.showLoading($button);
            
            $.ajax({
                url: hphAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hph_sync_airtable',
                    nonce: hphAdmin.nonce
                },
                success: (response) => {
                    this.hideLoading($button);
                    if (response.success) {
                        this.showMessage('Airtable sync completed successfully!');
                    } else {
                        this.showMessage(response.data || 'Sync failed', 'error');
                    }
                },
                error: () => {
                    this.hideLoading($button);
                    this.showMessage(hphAdmin.strings.error, 'error');
                }
            });
        },

        handleSyncMLS: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            
            this.showLoading($button);
            
            $.ajax({
                url: hphAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hph_sync_mls',
                    nonce: hphAdmin.nonce
                },
                success: (response) => {
                    this.hideLoading($button);
                    if (response.success) {
                        this.showMessage('MLS sync completed successfully!');
                    } else {
                        this.showMessage(response.data || 'MLS sync failed', 'error');
                    }
                },
                error: () => {
                    this.hideLoading($button);
                    this.showMessage(hphAdmin.strings.error, 'error');
                }
            });
        },

        handleSyncContacts: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            
            this.showLoading($button);
            
            $.ajax({
                url: hphAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hph_sync_contacts',
                    nonce: hphAdmin.nonce
                },
                success: (response) => {
                    this.hideLoading($button);
                    if (response.success) {
                        this.showMessage('Contact sync completed successfully!');
                    } else {
                        this.showMessage(response.data || 'Contact sync failed', 'error');
                    }
                },
                error: () => {
                    this.hideLoading($button);
                    this.showMessage(hphAdmin.strings.error, 'error');
                }
            });
        },

        // Tool Actions
        handleOptimizeImages: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            
            this.showLoading($button);
            
            $.ajax({
                url: hphAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hph_optimize_images',
                    nonce: hphAdmin.nonce
                },
                success: (response) => {
                    this.hideLoading($button);
                    if (response.success) {
                        this.showMessage(`Optimized ${response.data.count} images`);
                    } else {
                        this.showMessage(response.data || 'Image optimization failed', 'error');
                    }
                },
                error: () => {
                    this.hideLoading($button);
                    this.showMessage(hphAdmin.strings.error, 'error');
                }
            });
        },

        handleCheckImages: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            
            this.showLoading($button);
            
            $.ajax({
                url: hphAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hph_check_images',
                    nonce: hphAdmin.nonce
                },
                success: (response) => {
                    this.hideLoading($button);
                    if (response.success) {
                        this.showMessage(`Found ${response.data.total} images, ${response.data.optimized} optimized`);
                    } else {
                        this.showMessage(response.data || 'Image check failed', 'error');
                    }
                },
                error: () => {
                    this.hideLoading($button);
                    this.showMessage(hphAdmin.strings.error, 'error');
                }
            });
        },

        handleOptimizeSEO: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            
            this.showLoading($button);
            
            $.ajax({
                url: hphAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hph_optimize_seo',
                    nonce: hphAdmin.nonce
                },
                success: (response) => {
                    this.hideLoading($button);
                    if (response.success) {
                        this.showMessage('SEO optimization completed!');
                    } else {
                        this.showMessage(response.data || 'SEO optimization failed', 'error');
                    }
                },
                error: () => {
                    this.hideLoading($button);
                    this.showMessage(hphAdmin.strings.error, 'error');
                }
            });
        },

        handleGenerateSitemaps: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            
            this.showLoading($button);
            
            $.ajax({
                url: hphAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hph_generate_sitemaps',
                    nonce: hphAdmin.nonce
                },
                success: (response) => {
                    this.hideLoading($button);
                    if (response.success) {
                        this.showMessage('Sitemaps generated successfully!');
                    } else {
                        this.showMessage(response.data || 'Sitemap generation failed', 'error');
                    }
                },
                error: () => {
                    this.hideLoading($button);
                    this.showMessage(hphAdmin.strings.error, 'error');
                }
            });
        },

        handleRunDiagnostics: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            
            this.showLoading($button);
            
            $.ajax({
                url: hphAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hph_run_diagnostics',
                    nonce: hphAdmin.nonce
                },
                success: (response) => {
                    this.hideLoading($button);
                    if (response.success) {
                        this.showMessage('Diagnostics completed - all systems healthy!');
                    } else {
                        this.showMessage(response.data || 'Diagnostics found issues', 'warning');
                    }
                },
                error: () => {
                    this.hideLoading($button);
                    this.showMessage(hphAdmin.strings.error, 'error');
                }
            });
        },

        handleClearCache: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            
            this.showLoading($button);
            
            $.ajax({
                url: hphAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hph_clear_cache',
                    nonce: hphAdmin.nonce
                },
                success: (response) => {
                    this.hideLoading($button);
                    if (response.success) {
                        this.showMessage('Cache cleared successfully!');
                    } else {
                        this.showMessage(response.data || 'Cache clear failed', 'error');
                    }
                },
                error: () => {
                    this.hideLoading($button);
                    this.showMessage(hphAdmin.strings.error, 'error');
                }
            });
        },

        // Report Actions
        handleListingReport: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            
            this.showLoading($button);
            
            $.ajax({
                url: hphAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hph_listing_report',
                    nonce: hphAdmin.nonce
                },
                success: (response) => {
                    this.hideLoading($button);
                    if (response.success) {
                        // Open report in new window/tab
                        window.open(response.data.report_url, '_blank');
                        this.showMessage('Listing report generated!');
                    } else {
                        this.showMessage(response.data || 'Report generation failed', 'error');
                    }
                },
                error: () => {
                    this.hideLoading($button);
                    this.showMessage(hphAdmin.strings.error, 'error');
                }
            });
        },

        handleExportReport: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            
            this.showLoading($button);
            
            $.ajax({
                url: hphAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hph_export_report',
                    nonce: hphAdmin.nonce
                },
                success: (response) => {
                    this.hideLoading($button);
                    if (response.success) {
                        window.location.href = response.data.download_url;
                        this.showMessage('Report exported successfully!');
                    } else {
                        this.showMessage(response.data || 'Export failed', 'error');
                    }
                },
                error: () => {
                    this.hideLoading($button);
                    this.showMessage(hphAdmin.strings.error, 'error');
                }
            });
        },

        handleAgentReport: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            
            this.showLoading($button);
            
            $.ajax({
                url: hphAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hph_agent_report',
                    nonce: hphAdmin.nonce
                },
                success: (response) => {
                    this.hideLoading($button);
                    if (response.success) {
                        window.open(response.data.report_url, '_blank');
                        this.showMessage('Agent report generated!');
                    } else {
                        this.showMessage(response.data || 'Report generation failed', 'error');
                    }
                },
                error: () => {
                    this.hideLoading($button);
                    this.showMessage(hphAdmin.strings.error, 'error');
                }
            });
        },

        handleExportAgentData: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            
            this.showLoading($button);
            
            $.ajax({
                url: hphAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hph_export_agent_data',
                    nonce: hphAdmin.nonce
                },
                success: (response) => {
                    this.hideLoading($button);
                    if (response.success) {
                        window.location.href = response.data.download_url;
                        this.showMessage('Agent data exported successfully!');
                    } else {
                        this.showMessage(response.data || 'Export failed', 'error');
                    }
                },
                error: () => {
                    this.hideLoading($button);
                    this.showMessage(hphAdmin.strings.error, 'error');
                }
            });
        },

        handleLeadAnalytics: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            
            this.showLoading($button);
            
            $.ajax({
                url: hphAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hph_lead_analytics',
                    nonce: hphAdmin.nonce
                },
                success: (response) => {
                    this.hideLoading($button);
                    if (response.success) {
                        window.open(response.data.analytics_url, '_blank');
                        this.showMessage('Lead analytics generated!');
                    } else {
                        this.showMessage(response.data || 'Analytics generation failed', 'error');
                    }
                },
                error: () => {
                    this.hideLoading($button);
                    this.showMessage(hphAdmin.strings.error, 'error');
                }
            });
        },

        handleExportLeads: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            
            this.showLoading($button);
            
            $.ajax({
                url: hphAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hph_export_leads',
                    nonce: hphAdmin.nonce
                },
                success: (response) => {
                    this.hideLoading($button);
                    if (response.success) {
                        window.location.href = response.data.download_url;
                        this.showMessage('Lead data exported successfully!');
                    } else {
                        this.showMessage(response.data || 'Export failed', 'error');
                    }
                },
                error: () => {
                    this.hideLoading($button);
                    this.showMessage(hphAdmin.strings.error, 'error');
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        HPH_Admin.init();
    });

})(jQuery);
