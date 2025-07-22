/**
 * Image Optimization JavaScript
 * Handles the admin interface for image optimization
 */

(function($) {
    'use strict';

    const ImageOptimization = {
        init: function() {
            this.bindEvents();
            this.loadImageStats();
        },

        bindEvents: function() {
            $(document).on('click', '#optimize-images', this.startOptimization.bind(this));
            $(document).on('click', '#check-image-stats', this.loadImageStats.bind(this));
            $(document).on('click', '#clear-optimization-meta', this.clearOptimizationMeta.bind(this));
        },

        loadImageStats: function(e) {
            if (e) e.preventDefault();
            
            this.showLoading('#check-image-stats');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hph_get_image_stats',
                    nonce: happyPlaceAdmin?.nonce || ''
                },
                success: (response) => {
                    this.hideLoading('#check-image-stats');
                    
                    if (response.success && response.data.stats) {
                        this.updateStatsDisplay(response.data.stats);
                        this.showNotice(response.data.message, 'success');
                    } else {
                        this.showNotice(response.data || 'Failed to load image stats', 'error');
                    }
                },
                error: () => {
                    this.hideLoading('#check-image-stats');
                    this.showNotice('Failed to load image stats', 'error');
                }
            });
        },

        startOptimization: function(e) {
            e.preventDefault();
            
            if (confirm('This will optimize all unoptimized images. This may take some time. Continue?')) {
                this.runOptimizationBatch();
            }
        },

        runOptimizationBatch: function() {
            const $button = $('#optimize-images');
            const $progress = $('#optimization-progress');
            const $progressFill = $('.hph-progress-fill');
            const $progressText = $('.hph-progress-text');
            
            $button.prop('disabled', true).text('Optimizing...');
            $progress.show();
            $progressText.text('Starting optimization...');
            
            this.optimizeBatch(0);
        },

        optimizeBatch: function(currentBatch = 0) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hph_optimize_images',
                    nonce: happyPlaceAdmin?.nonce || '',
                    batch_size: 5 // Optimize 5 images at a time
                },
                success: (response) => {
                    if (response.success) {
                        const data = response.data;
                        
                        // Update progress
                        this.updateStatsDisplay(data.stats);
                        this.updateProgress(data.stats.percentage, data.message);
                        
                        if (data.completed) {
                            this.completeOptimization(data.stats);
                        } else {
                            // Continue with next batch
                            setTimeout(() => {
                                this.optimizeBatch(currentBatch + 1);
                            }, 500); // Small delay between batches
                        }
                    } else {
                        this.handleOptimizationError(response.data || 'Optimization failed');
                    }
                },
                error: () => {
                    this.handleOptimizationError('Network error during optimization');
                }
            });
        },

        updateProgress: function(percentage, message) {
            const $progressFill = $('.hph-progress-fill');
            const $progressText = $('.hph-progress-text');
            
            $progressFill.css('width', percentage + '%');
            $progressText.text(message);
        },

        completeOptimization: function(stats) {
            const $button = $('#optimize-images');
            const $progress = $('#optimization-progress');
            
            $button.prop('disabled', false).text('Optimize Images');
            
            setTimeout(() => {
                $progress.hide();
            }, 2000);
            
            this.showNotice(
                `Optimization complete! Optimized ${stats.optimized} of ${stats.total} images.`,
                'success'
            );
        },

        handleOptimizationError: function(message) {
            const $button = $('#optimize-images');
            const $progress = $('#optimization-progress');
            
            $button.prop('disabled', false).text('Optimize Images');
            $progress.hide();
            
            this.showNotice('Optimization error: ' + message, 'error');
        },

        clearOptimizationMeta: function(e) {
            e.preventDefault();
            
            if (confirm('This will reset all optimization metadata. Images will be marked as unoptimized. Continue?')) {
                this.showLoading('#clear-optimization-meta');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'hph_clear_optimization_meta',
                        nonce: happyPlaceAdmin?.nonce || ''
                    },
                    success: (response) => {
                        this.hideLoading('#clear-optimization-meta');
                        
                        if (response.success) {
                            this.updateStatsDisplay(response.data.stats);
                            this.showNotice(response.data.message, 'success');
                        } else {
                            this.showNotice(response.data || 'Failed to clear optimization metadata', 'error');
                        }
                    },
                    error: () => {
                        this.hideLoading('#clear-optimization-meta');
                        this.showNotice('Failed to clear optimization metadata', 'error');
                    }
                });
            }
        },

        updateStatsDisplay: function(stats) {
            $('#total-images').text(stats.total || 0);
            $('#optimized-images').text(stats.optimized || 0);
            $('#optimization-percentage').text((stats.percentage || 0) + '%');
        },

        showLoading: function(selector) {
            const $element = $(selector);
            $element.data('original-text', $element.text());
            $element.prop('disabled', true).text('Loading...');
        },

        hideLoading: function(selector) {
            const $element = $(selector);
            const originalText = $element.data('original-text') || $element.text();
            $element.prop('disabled', false).text(originalText);
        },

        showNotice: function(message, type = 'info') {
            // Create notice element
            const $notice = $(`
                <div class="notice notice-${type} is-dismissible" style="margin: 15px 0;">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);
            
            // Add to page
            $('#image-optimization-stats').after($notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                $notice.fadeOut(() => $notice.remove());
            }, 5000);
            
            // Handle manual dismiss
            $notice.find('.notice-dismiss').on('click', function() {
                $notice.fadeOut(() => $notice.remove());
            });
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        if ($('#optimize-images').length) {
            ImageOptimization.init();
        }
    });

    // Make available globally if needed
    window.HPH_ImageOptimization = ImageOptimization;

})(jQuery);
