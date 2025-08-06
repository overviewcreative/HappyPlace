/**
 * Happy Place Plugin - CSV Operations Manager
 * Unified CSV import/export functionality with enhanced features
 * 
 * Consolidates: csv-import.js, csv-import-clean.js
 * Features: File validation, progress tracking, error handling, preview, cleanup
 */

class CSVManager {
    constructor() {
        this.currentFile = null;
        this.parsedData = null;
        this.validationResults = null;
        this.importProgress = {
            total: 0,
            processed: 0,
            errors: 0,
            warnings: 0
        };
        
        this.init();
    }

    /**
     * Initialize CSV Manager
     */
    init() {
        this.bindEvents();
        this.setupProgressTracking();
        this.initializeValidation();
    }

    /**
     * Bind event handlers
     */
    bindEvents() {
        // File input handlers
        const fileInputs = document.querySelectorAll('input[type="file"][accept=".csv"]');
        fileInputs.forEach(input => {
            input.addEventListener('change', (e) => this.handleFileSelect(e));
        });

        // Import button handlers
        const importButtons = document.querySelectorAll('.csv-import-btn, .start-import');
        importButtons.forEach(btn => {
            btn.addEventListener('click', (e) => this.handleImportClick(e));
        });

        // Preview handlers
        const previewButtons = document.querySelectorAll('.csv-preview-btn');
        previewButtons.forEach(btn => {
            btn.addEventListener('click', (e) => this.handlePreviewClick(e));
        });

        // Validation handlers
        const validateButtons = document.querySelectorAll('.csv-validate-btn');
        validateButtons.forEach(btn => {
            btn.addEventListener('click', (e) => this.handleValidateClick(e));
        });

        // Export handlers
        const exportButtons = document.querySelectorAll('.csv-export-btn');
        exportButtons.forEach(btn => {
            btn.addEventListener('click', (e) => this.handleExportClick(e));
        });

        // Cleanup handlers
        const cleanupButtons = document.querySelectorAll('.csv-cleanup-btn');
        cleanupButtons.forEach(btn => {
            btn.addEventListener('click', (e) => this.handleCleanupClick(e));
        });

        // Cancel handlers
        const cancelButtons = document.querySelectorAll('.csv-cancel-btn');
        cancelButtons.forEach(btn => {
            btn.addEventListener('click', (e) => this.handleCancelClick(e));
        });
    }

    /**
     * Handle file selection
     */
    handleFileSelect(event) {
        const file = event.target.files[0];
        if (!file) return;

        if (!this.validateFileType(file)) {
            this.showError('Please select a valid CSV file.');
            return;
        }

        if (!this.validateFileSize(file)) {
            this.showError('File size is too large. Maximum allowed: 10MB');
            return;
        }

        this.currentFile = file;
        this.showFileInfo(file);
        this.enableImportButton();
    }

    /**
     * Validate file type
     */
    validateFileType(file) {
        const validTypes = [
            'text/csv',
            'application/csv',
            'text/comma-separated-values',
            'application/vnd.ms-excel'
        ];
        
        const validExtensions = ['.csv', '.txt'];
        const fileName = file.name.toLowerCase();
        const hasValidExtension = validExtensions.some(ext => fileName.endsWith(ext));
        
        return validTypes.includes(file.type) || hasValidExtension;
    }

    /**
     * Validate file size
     */
    validateFileSize(file) {
        const maxSize = 10 * 1024 * 1024; // 10MB
        return file.size <= maxSize;
    }

    /**
     * Show file information
     */
    showFileInfo(file) {
        const fileInfo = document.querySelector('.file-info');
        if (fileInfo) {
            fileInfo.innerHTML = `
                <div class="file-details">
                    <p><strong>File:</strong> ${file.name}</p>
                    <p><strong>Size:</strong> ${this.formatFileSize(file.size)}</p>
                    <p><strong>Type:</strong> ${file.type || 'CSV'}</p>
                    <p><strong>Last Modified:</strong> ${new Date(file.lastModified).toLocaleString()}</p>
                </div>
            `;
            fileInfo.style.display = 'block';
        }
    }

    /**
     * Format file size for display
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Enable import button
     */
    enableImportButton() {
        const importBtn = document.querySelector('.csv-import-btn');
        if (importBtn) {
            importBtn.disabled = false;
            importBtn.textContent = 'Import CSV';
        }
    }

    /**
     * Handle import button click
     */
    async handleImportClick(event) {
        event.preventDefault();
        
        if (!this.currentFile) {
            this.showError('Please select a CSV file first.');
            return;
        }

        this.showProgress();
        this.disableControls();

        try {
            await this.parseCSV(this.currentFile);
            await this.validateData();
            await this.processImport();
        } catch (error) {
            this.showError(`Import failed: ${error.message}`);
            console.error('CSV Import Error:', error);
        } finally {
            this.enableControls();
        }
    }

    /**
     * Parse CSV file
     */
    parseCSV(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            
            reader.onload = (e) => {
                try {
                    const text = e.target.result;
                    this.parsedData = this.parseCSVText(text);
                    this.updateProgress(25, 'File parsed successfully');
                    resolve(this.parsedData);
                } catch (error) {
                    reject(new Error(`Failed to parse CSV: ${error.message}`));
                }
            };

            reader.onerror = () => {
                reject(new Error('Failed to read file'));
            };

            reader.readAsText(file);
        });
    }

    /**
     * Parse CSV text content
     */
    parseCSVText(text) {
        const lines = text.split('\n').filter(line => line.trim());
        if (lines.length === 0) {
            throw new Error('CSV file is empty');
        }

        const headers = this.parseCSVLine(lines[0]);
        const rows = [];

        for (let i = 1; i < lines.length; i++) {
            const row = this.parseCSVLine(lines[i]);
            if (row.length > 0) {
                const rowObject = {};
                headers.forEach((header, index) => {
                    rowObject[header] = row[index] || '';
                });
                rows.push(rowObject);
            }
        }

        return {
            headers: headers,
            rows: rows,
            totalRows: rows.length
        };
    }

    /**
     * Parse a single CSV line
     */
    parseCSVLine(line) {
        const result = [];
        let current = '';
        let inQuotes = false;
        
        for (let i = 0; i < line.length; i++) {
            const char = line[i];
            
            if (char === '"') {
                if (inQuotes && line[i + 1] === '"') {
                    current += '"';
                    i++; // Skip next quote
                } else {
                    inQuotes = !inQuotes;
                }
            } else if (char === ',' && !inQuotes) {
                result.push(current.trim());
                current = '';
            } else {
                current += char;
            }
        }
        
        result.push(current.trim());
        return result;
    }

    /**
     * Validate parsed data
     */
    async validateData() {
        this.updateProgress(50, 'Validating data...');
        
        const validation = {
            isValid: true,
            errors: [],
            warnings: [],
            summary: {}
        };

        // Check for required headers
        const requiredHeaders = this.getRequiredHeaders();
        const missingHeaders = requiredHeaders.filter(header => 
            !this.parsedData.headers.includes(header)
        );

        if (missingHeaders.length > 0) {
            validation.errors.push(`Missing required columns: ${missingHeaders.join(', ')}`);
            validation.isValid = false;
        }

        // Validate each row
        for (let i = 0; i < this.parsedData.rows.length; i++) {
            const row = this.parsedData.rows[i];
            const rowValidation = this.validateRow(row, i + 2); // +2 for header and 1-based indexing
            
            validation.errors.push(...rowValidation.errors);
            validation.warnings.push(...rowValidation.warnings);
        }

        // Generate summary
        validation.summary = {
            totalRows: this.parsedData.totalRows,
            validRows: this.parsedData.totalRows - validation.errors.length,
            errorCount: validation.errors.length,
            warningCount: validation.warnings.length
        };

        this.validationResults = validation;
        this.displayValidationResults();
        
        this.updateProgress(75, 'Validation complete');
        return validation;
    }

    /**
     * Get required headers based on import type
     */
    getRequiredHeaders() {
        // This should be customized based on your specific requirements
        const importType = this.getImportType();
        
        const headerSets = {
            'users': ['email', 'name'],
            'products': ['name', 'price'],
            'orders': ['order_id', 'customer_email'],
            'default': ['id', 'name']
        };

        return headerSets[importType] || headerSets.default;
    }

    /**
     * Get import type from UI or context
     */
    getImportType() {
        const typeSelect = document.querySelector('#import-type');
        return typeSelect ? typeSelect.value : 'default';
    }

    /**
     * Validate a single row
     */
    validateRow(row, rowNumber) {
        const validation = {
            errors: [],
            warnings: []
        };

        // Email validation
        if (row.email && !this.isValidEmail(row.email)) {
            validation.errors.push(`Row ${rowNumber}: Invalid email format`);
        }

        // Required field validation
        const requiredFields = this.getRequiredFields();
        requiredFields.forEach(field => {
            if (!row[field] || row[field].trim() === '') {
                validation.errors.push(`Row ${rowNumber}: Missing required field '${field}'`);
            }
        });

        // Data type validation
        if (row.price && isNaN(parseFloat(row.price))) {
            validation.warnings.push(`Row ${rowNumber}: Price is not a valid number`);
        }

        // Length validation
        if (row.name && row.name.length > 255) {
            validation.warnings.push(`Row ${rowNumber}: Name exceeds maximum length`);
        }

        return validation;
    }

    /**
     * Get required fields based on import type
     */
    getRequiredFields() {
        const importType = this.getImportType();
        
        const fieldSets = {
            'users': ['email', 'name'],
            'products': ['name'],
            'orders': ['order_id'],
            'default': ['name']
        };

        return fieldSets[importType] || fieldSets.default;
    }

    /**
     * Validate email format
     */
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Display validation results
     */
    displayValidationResults() {
        const resultsContainer = document.querySelector('.validation-results');
        if (!resultsContainer) return;

        const results = this.validationResults;
        let html = `
            <div class="validation-summary">
                <h4>Validation Results</h4>
                <p><strong>Total Rows:</strong> ${results.summary.totalRows}</p>
                <p><strong>Valid Rows:</strong> ${results.summary.validRows}</p>
                <p><strong>Errors:</strong> ${results.summary.errorCount}</p>
                <p><strong>Warnings:</strong> ${results.summary.warningCount}</p>
            </div>
        `;

        if (results.errors.length > 0) {
            html += `
                <div class="validation-errors">
                    <h5>Errors</h5>
                    <ul>
                        ${results.errors.map(error => `<li>${error}</li>`).join('')}
                    </ul>
                </div>
            `;
        }

        if (results.warnings.length > 0) {
            html += `
                <div class="validation-warnings">
                    <h5>Warnings</h5>
                    <ul>
                        ${results.warnings.map(warning => `<li>${warning}</li>`).join('')}
                    </ul>
                </div>
            `;
        }

        resultsContainer.innerHTML = html;
        resultsContainer.style.display = 'block';
    }

    /**
     * Process the import
     */
    async processImport() {
        if (!this.validationResults.isValid) {
            throw new Error('Cannot import data with validation errors. Please fix errors and try again.');
        }

        this.updateProgress(80, 'Processing import...');

        const batchSize = 50;
        const batches = this.createBatches(this.parsedData.rows, batchSize);

        this.importProgress.total = this.parsedData.rows.length;
        this.importProgress.processed = 0;
        this.importProgress.errors = 0;

        for (let i = 0; i < batches.length; i++) {
            const batch = batches[i];
            
            try {
                await this.processBatch(batch, i + 1, batches.length);
            } catch (error) {
                console.error(`Batch ${i + 1} failed:`, error);
                this.importProgress.errors += batch.length;
            }
        }

        this.updateProgress(100, 'Import complete');
        this.showImportSummary();
    }

    /**
     * Create batches for processing
     */
    createBatches(rows, batchSize) {
        const batches = [];
        for (let i = 0; i < rows.length; i += batchSize) {
            batches.push(rows.slice(i, i + batchSize));
        }
        return batches;
    }

    /**
     * Process a single batch
     */
    async processBatch(batch, batchNumber, totalBatches) {
        const response = await fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'hph_process_csv_batch',
                nonce: hph_ajax.nonce,
                batch: JSON.stringify(batch),
                import_type: this.getImportType()
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.data.message || 'Batch processing failed');
        }

        this.importProgress.processed += batch.length;
        const progressPercent = 80 + (20 * (this.importProgress.processed / this.importProgress.total));
        this.updateProgress(progressPercent, `Processing batch ${batchNumber}/${totalBatches}`);

        return result;
    }

    /**
     * Show import summary
     */
    showImportSummary() {
        const summary = {
            total: this.importProgress.total,
            processed: this.importProgress.processed,
            errors: this.importProgress.errors,
            success: this.importProgress.processed - this.importProgress.errors
        };

        const summaryHtml = `
            <div class="import-summary">
                <h4>Import Complete</h4>
                <p><strong>Total Records:</strong> ${summary.total}</p>
                <p><strong>Successfully Imported:</strong> ${summary.success}</p>
                <p><strong>Errors:</strong> ${summary.errors}</p>
                <p><strong>Success Rate:</strong> ${((summary.success / summary.total) * 100).toFixed(1)}%</p>
            </div>
        `;

        this.showMessage(summaryHtml, 'success');
    }

    /**
     * Handle preview click
     */
    handlePreviewClick(event) {
        event.preventDefault();
        
        if (!this.parsedData) {
            this.showError('Please select and parse a CSV file first.');
            return;
        }

        this.showPreview();
    }

    /**
     * Show data preview
     */
    showPreview() {
        const previewContainer = document.querySelector('.csv-preview');
        if (!previewContainer) return;

        const maxRows = 10;
        const data = this.parsedData;
        
        let html = `
            <div class="preview-header">
                <h4>CSV Preview (First ${Math.min(maxRows, data.totalRows)} rows)</h4>
                <p>Total rows: ${data.totalRows} | Columns: ${data.headers.length}</p>
            </div>
            <div class="preview-table-container">
                <table class="csv-preview-table">
                    <thead>
                        <tr>
                            ${data.headers.map(header => `<th>${header}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
        `;

        const rowsToShow = data.rows.slice(0, maxRows);
        rowsToShow.forEach(row => {
            html += '<tr>';
            data.headers.forEach(header => {
                const value = row[header] || '';
                const truncated = value.length > 50 ? value.substring(0, 50) + '...' : value;
                html += `<td title="${value}">${truncated}</td>`;
            });
            html += '</tr>';
        });

        html += `
                    </tbody>
                </table>
            </div>
        `;

        previewContainer.innerHTML = html;
        previewContainer.style.display = 'block';
    }

    /**
     * Handle validation click
     */
    async handleValidateClick(event) {
        event.preventDefault();
        
        if (!this.currentFile) {
            this.showError('Please select a CSV file first.');
            return;
        }

        try {
            if (!this.parsedData) {
                await this.parseCSV(this.currentFile);
            }
            await this.validateData();
        } catch (error) {
            this.showError(`Validation failed: ${error.message}`);
        }
    }

    /**
     * Handle export click
     */
    async handleExportClick(event) {
        event.preventDefault();
        
        const exportType = event.target.getAttribute('data-export-type') || 'all';
        await this.exportData(exportType);
    }

    /**
     * Export data to CSV
     */
    async exportData(exportType) {
        this.showProgress();
        this.updateProgress(20, 'Preparing export...');

        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'hph_export_csv',
                    nonce: hph_ajax.nonce,
                    export_type: exportType
                })
            });

            this.updateProgress(60, 'Generating CSV...');

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.data.message || 'Export failed');
            }

            this.updateProgress(80, 'Downloading file...');
            
            // Create and download the file
            const csvContent = result.data.csv;
            const filename = result.data.filename || `export-${new Date().toISOString().split('T')[0]}.csv`;
            
            this.downloadCSV(csvContent, filename);
            this.updateProgress(100, 'Export complete');
            
            setTimeout(() => this.hideProgress(), 2000);

        } catch (error) {
            this.showError(`Export failed: ${error.message}`);
            this.hideProgress();
        }
    }

    /**
     * Download CSV file
     */
    downloadCSV(content, filename) {
        const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        
        if (link.download !== undefined) {
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }

    /**
     * Handle cleanup click
     */
    async handleCleanupClick(event) {
        event.preventDefault();
        
        if (!confirm('Are you sure you want to clean up duplicate or invalid records? This action cannot be undone.')) {
            return;
        }

        await this.performCleanup();
    }

    /**
     * Perform data cleanup
     */
    async performCleanup() {
        this.showProgress();
        this.updateProgress(20, 'Starting cleanup...');

        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'hph_cleanup_data',
                    nonce: hph_ajax.nonce,
                    cleanup_type: 'duplicates'
                })
            });

            this.updateProgress(60, 'Processing cleanup...');

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.data.message || 'Cleanup failed');
            }

            this.updateProgress(100, 'Cleanup complete');
            this.showMessage(`Cleanup complete. ${result.data.cleaned} records processed.`, 'success');
            
            setTimeout(() => this.hideProgress(), 2000);

        } catch (error) {
            this.showError(`Cleanup failed: ${error.message}`);
            this.hideProgress();
        }
    }

    /**
     * Handle cancel click
     */
    handleCancelClick(event) {
        event.preventDefault();
        
        if (confirm('Are you sure you want to cancel the current operation?')) {
            this.resetImport();
        }
    }

    /**
     * Reset import state
     */
    resetImport() {
        this.currentFile = null;
        this.parsedData = null;
        this.validationResults = null;
        this.importProgress = {
            total: 0,
            processed: 0,
            errors: 0,
            warnings: 0
        };

        // Reset UI
        const fileInputs = document.querySelectorAll('input[type="file"]');
        fileInputs.forEach(input => input.value = '');

        const fileInfo = document.querySelector('.file-info');
        if (fileInfo) fileInfo.style.display = 'none';

        const preview = document.querySelector('.csv-preview');
        if (preview) preview.style.display = 'none';

        const validation = document.querySelector('.validation-results');
        if (validation) validation.style.display = 'none';

        this.hideProgress();
        this.enableControls();
        
        this.showMessage('Import cancelled', 'info');
    }

    /**
     * Setup progress tracking
     */
    setupProgressTracking() {
        // Create progress container if it doesn't exist
        let progressContainer = document.querySelector('.csv-progress');
        if (!progressContainer) {
            progressContainer = document.createElement('div');
            progressContainer.className = 'csv-progress';
            progressContainer.style.display = 'none';
            
            const targetContainer = document.querySelector('.csv-import-container') || document.body;
            targetContainer.appendChild(progressContainer);
        }
    }

    /**
     * Show progress indicator
     */
    showProgress() {
        const progressContainer = document.querySelector('.csv-progress');
        if (progressContainer) {
            progressContainer.innerHTML = `
                <div class="progress-wrapper">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 0%"></div>
                    </div>
                    <div class="progress-text">Initializing...</div>
                </div>
            `;
            progressContainer.style.display = 'block';
        }
    }

    /**
     * Update progress
     */
    updateProgress(percent, message) {
        const progressFill = document.querySelector('.progress-fill');
        const progressText = document.querySelector('.progress-text');
        
        if (progressFill) {
            progressFill.style.width = `${percent}%`;
        }
        
        if (progressText) {
            progressText.textContent = message;
        }
    }

    /**
     * Hide progress indicator
     */
    hideProgress() {
        const progressContainer = document.querySelector('.csv-progress');
        if (progressContainer) {
            progressContainer.style.display = 'none';
        }
    }

    /**
     * Disable controls during processing
     */
    disableControls() {
        const buttons = document.querySelectorAll('.csv-import-btn, .csv-preview-btn, .csv-validate-btn, .csv-export-btn');
        buttons.forEach(btn => btn.disabled = true);
    }

    /**
     * Enable controls after processing
     */
    enableControls() {
        const buttons = document.querySelectorAll('.csv-import-btn, .csv-preview-btn, .csv-validate-btn, .csv-export-btn');
        buttons.forEach(btn => btn.disabled = false);
    }

    /**
     * Initialize validation rules
     */
    initializeValidation() {
        // Set up any initial validation rules or configurations
        this.validationRules = {
            email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            phone: /^[\+]?[1-9][\d]{0,15}$/,
            url: /^https?:\/\/.+/,
            maxLength: 255
        };
    }

    /**
     * Show error message
     */
    showError(message) {
        this.showMessage(message, 'error');
    }

    /**
     * Show success message
     */
    showSuccess(message) {
        this.showMessage(message, 'success');
    }

    /**
     * Show message with type
     */
    showMessage(message, type = 'info') {
        let messageContainer = document.querySelector('.csv-messages');
        if (!messageContainer) {
            messageContainer = document.createElement('div');
            messageContainer.className = 'csv-messages';
            
            const targetContainer = document.querySelector('.csv-import-container') || document.body;
            targetContainer.insertBefore(messageContainer, targetContainer.firstChild);
        }

        const messageDiv = document.createElement('div');
        messageDiv.className = `message message-${type}`;
        messageDiv.innerHTML = `
            <span class="message-text">${message}</span>
            <button class="message-close" onclick="this.parentElement.remove()">&times;</button>
        `;

        messageContainer.appendChild(messageDiv);

        // Auto-remove after 5 seconds for non-error messages
        if (type !== 'error') {
            setTimeout(() => {
                if (messageDiv.parentElement) {
                    messageDiv.remove();
                }
            }, 5000);
        }
    }
}

// Initialize CSV Manager when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.csvManager = new CSVManager();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CSVManager;
}
