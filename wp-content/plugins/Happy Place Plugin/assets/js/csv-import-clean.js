/**
 * CSV Import JavaScript for Happy Place Plugin
 */
(function($) {
    'use strict';

    let csvData = null;
    let validationResults = null;

    const HPH_CSV_Import = {
        init: function() {
            this.bindEvents();
            this.initFileUpload();
        },

        bindEvents: function() {
            $(document).on('submit', '#hph-csv-import-form', this.handleImport.bind(this));
            $(document).on('click', '#preview-csv', this.handlePreview.bind(this));
            $(document).on('change', '#csv_file', this.handleFileChange.bind(this));
            $(document).on('change', '#import_post_type', this.handlePostTypeChange.bind(this));
        },

        initFileUpload: function() {
            const $dropZone = $('.csv-drop-zone');
            const $fileInput = $('#csv_file');

            // Drag and drop functionality
            $dropZone.on('dragover dragenter', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('drag-over');
            });

            $dropZone.on('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('drag-over');
            });

            $dropZone.on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('drag-over');

                const files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    $fileInput[0].files = files;
                    $fileInput.trigger('change');
                }
            });

            $dropZone.on('click', function() {
                $fileInput.click();
            });
        },

        handleFileChange: function() {
            const $fileInput = $('#csv_file');
            const $fileName = $('.selected-file-name');
            const $fileSize = $('.selected-file-size');
            const $previewBtn = $('#preview-csv');

            if ($fileInput[0].files.length > 0) {
                const file = $fileInput[0].files[0];
                $fileName.text(file.name);
                $fileSize.text(this.formatFileSize(file.size));
                $('.file-selected').show();
                $previewBtn.prop('disabled', false);
            } else {
                $('.file-selected').hide();
                $previewBtn.prop('disabled', true);
            }

            // Reset preview and results
            $('.csv-preview, .import-progress, .import-results').hide();
            csvData = null;
            validationResults = null;
        },

        handlePostTypeChange: function() {
            // Reset form when post type changes
            $('#csv_file').val('');
            $('.file-selected').hide();
            $('.csv-preview, .import-progress, .import-results').hide();
            csvData = null;
            validationResults = null;
        },

        handlePreview: function(e) {
            e.preventDefault();

            if (!this.validateForm($('#hph-csv-import-form'))) {
                return;
            }

            const $btn = $(e.currentTarget);
            const $form = $('#hph-csv-import-form');
            const formData = new FormData($form[0]);
            formData.append('action', 'hph_import_csv');
            formData.append('action_type', 'preview');
            formData.append('nonce', hphImport.nonce);

            $btn.prop('disabled', true).text('Processing...');

            $.ajax({
                url: hphImport.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        csvData = response.data;
                        validationResults = response.data.validation;
                        HPH_CSV_Import.renderPreview(response.data);
                        $('#import-csv-btn').prop('disabled', false);
                    } else {
                        HPH_CSV_Import.showError(response.data || 'Failed to preview CSV file.');
                    }
                },
                error: function() {
                    HPH_CSV_Import.showError('Failed to process CSV file. Please try again.');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Preview CSV Data');
                }
            });
        },

        handleImport: function(e) {
            e.preventDefault();

            if (!csvData) {
                this.showError('Please preview the CSV data first.');
                return;
            }

            // Show validation warnings
            if (validationResults && validationResults.errors && validationResults.errors.length > 0) {
                const proceed = confirm(
                    `Found ${validationResults.errors.length} errors in your CSV. ` +
                    'These rows will be skipped. Continue with import?'
                );
                if (!proceed) return;
            }

            const $form = $('#hph-csv-import-form');
            const formData = new FormData($form[0]);
            formData.append('action', 'hph_import_csv');
            formData.append('action_type', 'import');
            formData.append('nonce', hphImport.nonce);

            this.showProgress();
            $('#import-csv-btn').prop('disabled', true);

            $.ajax({
                url: hphImport.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    HPH_CSV_Import.updateProgress(100, 'Import completed!');
                    
                    setTimeout(() => {
                        $('.import-progress').hide();
                        if (response.success) {
                            HPH_CSV_Import.renderResults(response.data);
                        } else {
                            HPH_CSV_Import.showError(response.data || 'Import failed.');
                        }
                    }, 1000);
                },
                error: function() {
                    HPH_CSV_Import.updateProgress(100, 'Import failed!');
                    HPH_CSV_Import.showError('Import failed. Please try again.');
                },
                complete: function() {
                    $('#import-csv-btn').prop('disabled', false);
                }
            });
        },

        showProgress: function() {
            $('.import-progress').show();
            this.updateProgress(0, 'Starting import...');
        },

        renderPreview: function(data) {
            const $preview = $('.csv-preview');
            const $content = $preview.find('.preview-content');

            let html = `
                <div class="preview-summary">
                    <div class="summary-stats">
                        <div class="stat">
                            <span class="number">${data.total_rows}</span>
                            <span class="label">Total Rows</span>
                        </div>
                        <div class="stat">
                            <span class="number">${data.validation.valid_rows}</span>
                            <span class="label">Valid Rows</span>
                        </div>
                        ${data.validation.errors && data.validation.errors.length > 0 ? 
                            `<div class="stat error">
                                <span class="number">${data.validation.errors.length}</span>
                                <span class="label">Errors</span>
                            </div>` : 
                            ''
                        }
                        ${data.validation.warnings && data.validation.warnings.length > 0 ? 
                            `<div class="stat warning">
                                <span class="number">${data.validation.warnings.length}</span>
                                <span class="label">Warnings</span>
                            </div>` : 
                            ''
                        }
                    </div>
                </div>
            `;

            // Show validation messages
            if ((data.validation.errors && data.validation.errors.length > 0) || 
                (data.validation.warnings && data.validation.warnings.length > 0)) {
                html += '<div class="validation-messages">';
                
                if (data.validation.errors && data.validation.errors.length > 0) {
                    html += `
                        <div class="message-group error">
                            <h4>Errors (${data.validation.errors.length})</h4>
                            <ul>
                    `;
                    data.validation.errors.forEach(error => {
                        html += `<li>${error}</li>`;
                    });
                    html += '</ul></div>';
                }

                if (data.validation.warnings && data.validation.warnings.length > 0) {
                    html += `
                        <div class="message-group warning">
                            <h4>Warnings (${data.validation.warnings.length})</h4>
                            <ul>
                    `;
                    data.validation.warnings.forEach(warning => {
                        html += `<li>${warning}</li>`;
                    });
                    html += '</ul></div>';
                }
                
                html += '</div>';
            }

            // Preview table
            if (data.preview && data.preview.length > 0) {
                html += `
                    <div class="preview-table-container">
                        <h4>Data Preview (First ${Math.min(5, data.preview.length)} Rows)</h4>
                        <div class="table-wrapper">
                            <table class="preview-table">
                                <thead>
                                    <tr>
                `;
                
                // Headers
                if (data.headers && data.headers.length > 0) {
                    data.headers.forEach(header => {
                        html += `<th>${header}</th>`;
                    });
                } else if (data.preview[0]) {
                    Object.keys(data.preview[0]).forEach(key => {
                        html += `<th>${key}</th>`;
                    });
                }
                
                html += '</tr></thead><tbody>';

                // Preview rows
                data.preview.slice(0, 5).forEach(row => {
                    html += '<tr>';
                    if (data.headers && data.headers.length > 0) {
                        data.headers.forEach(header => {
                            const value = row[header] || '';
                            html += `<td>${this.escapeHtml(String(value).substring(0, 50))}${value.length > 50 ? '...' : ''}</td>`;
                        });
                    } else {
                        Object.values(row).forEach(value => {
                            html += `<td>${this.escapeHtml(String(value || '').substring(0, 50))}${(value || '').length > 50 ? '...' : ''}</td>`;
                        });
                    }
                    html += '</tr>';
                });

                html += '</tbody></table></div></div>';
            }

            $content.html(html);
            $preview.show();

            // Scroll to preview
            $('html, body').animate({
                scrollTop: $preview.offset().top - 50
            }, 500);
        },

        renderResults: function(data) {
            const $results = $('.import-results');
            const $content = $results.find('.results-content');

            let html = `
                <div class="result-summary">
                    <div class="result-stat">
                        <div class="number">${data.total_processed || 0}</div>
                        <div class="label">Total Processed</div>
                    </div>
                    <div class="result-stat">
                        <div class="number">${data.successful_imports || 0}</div>
                        <div class="label">Successfully Imported</div>
                    </div>
                    <div class="result-stat">
                        <div class="number">${data.failed_imports || 0}</div>
                        <div class="label">Failed</div>
                    </div>
                    <div class="result-stat">
                        <div class="number">${data.skipped || 0}</div>
                        <div class="label">Skipped</div>
                    </div>
                </div>
            `;

            if (data.errors && data.errors.length > 0) {
                html += `
                    <div class="import-errors">
                        <h3>Import Errors (${data.errors.length})</h3>
                        <div class="error-list">
                `;
                
                data.errors.forEach(error => {
                    html += `
                        <div class="error-item">
                            <strong>Row ${error.row}:</strong> ${error.message}
                            <pre>${JSON.stringify(error.data, null, 2)}</pre>
                        </div>
                    `;
                });
                
                html += '</div></div>';
            }

            $content.html(html);
            $results.show();

            // Scroll to results
            $('html, body').animate({
                scrollTop: $results.offset().top - 50
            }, 500);
        },

        updateProgress: function(percent, text) {
            $('.progress-fill').css('width', percent + '%');
            $('.progress-text').text(text);
        },

        validateForm: function($form) {
            const fileInput = $('#csv_file')[0];
            
            if (!fileInput.files || fileInput.files.length === 0) {
                this.showError('Please select a CSV file to import.');
                return false;
            }

            const file = fileInput.files[0];
            if (file.type !== 'text/csv' && !file.name.toLowerCase().endsWith('.csv')) {
                this.showError('Please select a valid CSV file.');
                return false;
            }

            return true;
        },

        showError: function(message) {
            const errorHtml = `
                <div class="notice notice-error is-dismissible">
                    <p><strong>Error:</strong> ${message}</p>
                </div>
            `;
            $('.wrap h1').after(errorHtml);
        },

        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('#hph-csv-import-form').length > 0) {
            HPH_CSV_Import.init();
        }
    });

})(jQuery);
