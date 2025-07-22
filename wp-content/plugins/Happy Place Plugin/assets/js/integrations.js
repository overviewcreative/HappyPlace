/**
 * Happy Place Integrations Admin JavaScript
 */

(function($) {
    'use strict';

    var HPHIntegrations = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $('#hph-test-airtable').on('click', this.testAirtableConnection);
            $('#hph-manual-sync').on('click', this.triggerManualSync);
            $('#hph-integrations-form').on('submit', this.saveSettings);
        },

        testAirtableConnection: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $result = $('#hph-test-result');
            var accessToken = $('#airtable_access_token').val();
            var baseId = $('#airtable_base_id').val();
            var tableName = $('#airtable_table_name').val();

            if (!accessToken || !baseId) {
                HPHIntegrations.showResult($result, 'error', 'Personal Access Token and Base ID are required');
                return;
            }

            $button.prop('disabled', true).text(hphIntegrations.strings.testing);
            $result.hide();

            $.ajax({
                url: hphIntegrations.ajaxurl,
                type: 'POST',
                data: {
                    action: 'hph_test_airtable_connection',
                    nonce: hphIntegrations.nonce,
                    access_token: accessToken,
                    base_id: baseId,
                    table_name: tableName
                },
                success: function(response) {
                    if (response.success) {
                        HPHIntegrations.showResult($result, 'success', response.data);
                    } else {
                        HPHIntegrations.showResult($result, 'error', hphIntegrations.strings.error + ' ' + response.data);
                    }
                },
                error: function() {
                    HPHIntegrations.showResult($result, 'error', 'Connection failed due to network error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Test Connection');
                }
            });
        },

        triggerManualSync: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $progress = $('#hph-sync-progress');
            var $status = $('#hph-sync-status');
            var $fill = $('.hph-progress-fill');

            $button.prop('disabled', true).text(hphIntegrations.strings.syncing);
            $progress.show();
            $fill.css('width', '10%');
            $status.text('Initializing sync...');

            // Simulate progress updates
            var progressSteps = [
                { progress: 25, text: 'Fetching Airtable records...' },
                { progress: 50, text: 'Processing data...' },
                { progress: 75, text: 'Updating WordPress posts...' },
                { progress: 90, text: 'Finalizing sync...' }
            ];

            var stepIndex = 0;
            var progressInterval = setInterval(function() {
                if (stepIndex < progressSteps.length) {
                    var step = progressSteps[stepIndex];
                    $fill.css('width', step.progress + '%');
                    $status.text(step.text);
                    stepIndex++;
                } else {
                    clearInterval(progressInterval);
                }
            }, 1000);

            $.ajax({
                url: hphIntegrations.ajaxurl,
                type: 'POST',
                data: {
                    action: 'hph_trigger_manual_sync',
                    nonce: hphIntegrations.nonce
                },
                success: function(response) {
                    clearInterval(progressInterval);
                    $fill.css('width', '100%');
                    
                    if (response.success) {
                        $status.text(hphIntegrations.strings.sync_complete);
                        
                        // Show sync results
                        var results = response.data;
                        var resultText = 'Sync completed: ';
                        if (results.airtable_to_wp) {
                            resultText += results.airtable_to_wp.created + ' created, ' + 
                                         results.airtable_to_wp.updated + ' updated from Airtable. ';
                        }
                        if (results.wp_to_airtable) {
                            resultText += results.wp_to_airtable.created + ' created, ' + 
                                         results.wp_to_airtable.updated + ' updated to Airtable.';
                        }
                        
                        setTimeout(function() {
                            $status.text(resultText);
                        }, 1000);
                        
                        setTimeout(function() {
                            $progress.fadeOut();
                        }, 3000);
                        
                    } else {
                        $status.text(hphIntegrations.strings.sync_error + ' ' + response.data);
                        $fill.css('background', '#dc3545');
                    }
                },
                error: function() {
                    clearInterval(progressInterval);
                    $status.text('Sync failed due to network error');
                    $fill.css('background', '#dc3545');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Manual Sync');
                }
            });
        },

        saveSettings: function(e) {
            // Let the form submit normally, but show a saving indicator
            var $button = $(this).find('input[type="submit"]');
            $button.val('Saving...');
            
            setTimeout(function() {
                $button.val('Save Integration Settings');
            }, 2000);
        },

        showResult: function($element, type, message) {
            $element
                .removeClass('hph-test-success hph-test-error')
                .addClass('hph-test-' + type)
                .text(message)
                .show();
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        HPHIntegrations.init();
    });

})(jQuery);
