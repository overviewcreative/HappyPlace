(function($) {
    'use strict';

    class AirtableTwoWaySync {
        constructor() {
            this.initializeElements();
            this.bindEvents();
        }

        initializeElements() {
            this.$syncContainer = $('#hph-airtable-sync-container');
            this.$syncButton = $('#hph-trigger-two-way-sync');
            this.$syncStatus = $('#hph-sync-status');
            this.$syncProgress = $('#hph-sync-progress');
            this.$configForm = $('#hph-airtable-config-form');
        }

        bindEvents() {
            // Sync button event
            this.$syncButton.on('click', (e) => {
                e.preventDefault();
                this.performTwoWaySync();
            });

            // Optional: Config form submission
            if (this.$configForm.length) {
                this.$configForm.on('submit', (e) => {
                    e.preventDefault();
                    this.saveConfiguration();
                });
            }
        }

        async performTwoWaySync() {
            // Reset UI
            this.resetSyncUI();

            try {
                // Disable sync button during process
                this.$syncButton.prop('disabled', true);
                
                // Trigger sync via AJAX
                const response = await $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'hph_two_way_airtable_sync',
                        nonce: happyPlaceAirtable.nonce
                    },
                    xhr: () => {
                        const xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener('progress', (evt) => {
                            if (evt.lengthComputable) {
                                const percentComplete = evt.loaded / evt.total * 100;
                                this.$syncProgress.css('width', `${percentComplete}%`);
                            }
                        }, false);
                        return xhr;
                    }
                });

                // Handle successful sync
                if (response.success) {
                    this.displaySyncResults(response.data);
                } else {
                    throw new Error(response.data || 'Sync failed');
                }
            } catch (error) {
                this.displaySyncError(error);
            } finally {
                // Re-enable sync button
                this.$syncButton.prop('disabled', false);
            }
        }

        async saveConfiguration() {
            try {
                const formData = this.$configForm.serialize();
                
                const response = await $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'hph_save_airtable_config',
                        nonce: happyPlaceAirtable.nonce,
                        config_data: formData
                    }
                });

                if (response.success) {
                    this.showNotification('Configuration saved successfully!', 'success');
                } else {
                    throw new Error(response.data || 'Failed to save configuration');
                }
            } catch (error) {
                this.showNotification(error.message, 'error');
            }
        }

        resetSyncUI() {
            this.$syncStatus.empty();
            this.$syncProgress.css('width', '0%');
            this.showNotification('Sync started...', 'info');
        }

        displaySyncResults(results) {
            const $results = $('<div class="sync-results"></div>');

            // Airtable to WordPress Results
            $results.append(`
                <h4>Airtable to WordPress</h4>
                <p>Total Records: ${results.airtable_to_wp.total_records}</p>
                <p>Processed Records: ${results.airtable_to_wp.processed_records.length}</p>
            `);

            // WordPress to Airtable Results
            $results.append(`
                <h4>WordPress to Airtable</h4>
                <p>Total Records: ${results.wp_to_airtable.total_records}</p>
                <p>Processed Records: ${results.wp_to_airtable.processed_records.length}</p>
            `);

            this.$syncStatus.html($results);
            this.showNotification('Sync completed successfully!', 'success');
        }

        displaySyncError(error) {
            this.showNotification(`Sync Error: ${error.message}`, 'error');
            console.error('Sync Error:', error);
        }

        showNotification(message, type = 'info') {
            const $notification = $(`
                <div class="notice notice-${type} is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);

            // Add close functionality
            $notification.find('.notice-dismiss').on('click', () => {
                $notification.fadeTo(100, 0, () => {
                    $notification.slideUp(100, () => {
                        $notification.remove();
                    });
                });
            });

            // Append to sync status or appropriate container
            this.$syncStatus.prepend($notification);
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        new AirtableTwoWaySync();
    });
})(jQuery);