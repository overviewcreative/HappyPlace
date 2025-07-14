<?php

/**
 * Admin template for CSV Import
 * 
 * @package Happy_Place_Plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$import_manager = HPH\Admin\CSV_Import_Manager::get_instance();
$supported_types = $import_manager->get_supported_post_types();
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="hph-csv-import-container">
        <form id="hph-csv-import-form" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('hph_csv_import', 'hph_csv_import_nonce'); ?>

            <div class="import-settings">
                <h2>Import Settings</h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="import_post_type">Post Type</label>
                        </th>
                        <td>
                            <select name="import_post_type" id="import_post_type" required>
                                <option value="">Select a post type...</option>
                                <?php foreach ($supported_types as $post_type => $label): ?>
                                    <option value="<?php echo esc_attr($post_type); ?>">
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Choose the type of content you want to import.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="import_mode">Import Mode</label>
                        </th>
                        <td>
                            <select name="import_mode" id="import_mode">
                                <option value="create">Create new posts only</option>
                                <option value="update">Update existing posts (match by title)</option>
                                <option value="create_or_update">Create new or update existing</option>
                            </select>
                            <p class="description">Choose how to handle existing content.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="default_status">Default Status</label>
                        </th>
                        <td>
                            <select name="default_status" id="default_status">
                                <option value="draft">Draft</option>
                                <option value="publish">Published</option>
                                <option value="private">Private</option>
                            </select>
                            <p class="description">Default status for imported posts.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="file-upload-section">
                <h2>Upload CSV File</h2>

                <div class="csv-drop-zone">
                    <div class="drop-zone-content">
                        <span class="dashicons dashicons-upload"></span>
                        <p>Drop your CSV file here or <strong>click to browse</strong></p>
                        <p class="file-types">Accepts: .csv files only</p>
                    </div>
                    <input type="file" name="csv_file" id="csv_file" accept=".csv" style="display: none;">
                </div>

                <div class="file-selected" style="display: none;">
                    <div class="selected-file-info">
                        <span class="dashicons dashicons-media-spreadsheet"></span>
                        <div class="file-details">
                            <div class="selected-file-name"></div>
                            <div class="selected-file-size"></div>
                        </div>
                    </div>
                </div>

                <div class="upload-actions">
                    <button type="button" id="preview-csv" class="button button-secondary" disabled>
                        Preview CSV Data
                    </button>
                    <button type="button" id="download-template" class="button button-secondary">
                        Download Template
                    </button>
                </div>
            </div>
        </form>

        <!-- CSV Preview Section -->
        <div class="csv-preview" style="display: none;">
            <h2>CSV Preview</h2>
            <div class="preview-content">
                <!-- Preview content will be loaded here -->
            </div>

            <div class="preview-actions">
                <button type="submit" form="hph-csv-import-form" id="import-csv-btn" class="button button-primary" disabled>
                    Import CSV Data
                </button>
            </div>
        </div>

        <!-- Import Progress Section -->
        <div class="import-progress" style="display: none;">
            <h2>Import Progress</h2>
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 0%;"></div>
                </div>
                <div class="progress-text">Preparing import...</div>
            </div>
        </div>

        <!-- Import Results Section -->
        <div class="import-results" style="display: none;">
            <h2>Import Results</h2>
            <div class="results-content">
                <!-- Results will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
    .hph-csv-import-container {
        max-width: 1000px;
    }

    .import-settings {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .file-upload-section {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .csv-drop-zone {
        border: 2px dashed #ddd;
        border-radius: 8px;
        padding: 40px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-bottom: 20px;
    }

    .csv-drop-zone:hover,
    .csv-drop-zone.drag-over {
        border-color: #0073aa;
        background-color: #f8f9fa;
    }

    .drop-zone-content .dashicons {
        font-size: 48px;
        color: #ddd;
        margin-bottom: 10px;
    }

    .drop-zone-content p {
        margin: 5px 0;
        color: #666;
    }

    .file-types {
        font-size: 12px;
        color: #999;
    }

    .file-selected {
        background: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 15px;
        margin-bottom: 20px;
    }

    .selected-file-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .selected-file-info .dashicons {
        font-size: 24px;
        color: #0073aa;
    }

    .selected-file-name {
        font-weight: 600;
        color: #333;
    }

    .selected-file-size {
        color: #666;
        font-size: 12px;
    }

    .upload-actions {
        display: flex;
        gap: 10px;
    }

    .csv-preview,
    .import-progress,
    .import-results {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .preview-summary {
        margin-bottom: 20px;
    }

    .summary-stats {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }

    .stat {
        text-align: center;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 4px;
        min-width: 80px;
    }

    .stat.error {
        background: #ffeaea;
        color: #d63638;
    }

    .stat.warning {
        background: #fff8e5;
        color: #dba617;
    }

    .stat .number {
        display: block;
        font-size: 24px;
        font-weight: bold;
        line-height: 1;
    }

    .stat .label {
        display: block;
        font-size: 12px;
        margin-top: 5px;
        opacity: 0.7;
    }

    .validation-messages {
        margin: 20px 0;
    }

    .message-group {
        margin-bottom: 15px;
    }

    .message-group.error h4 {
        color: #d63638;
    }

    .message-group.warning h4 {
        color: #dba617;
    }

    .message-group ul {
        margin: 10px 0 0 20px;
    }

    .preview-table-container {
        margin-top: 20px;
    }

    .table-wrapper {
        overflow-x: auto;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .preview-table {
        width: 100%;
        border-collapse: collapse;
    }

    .preview-table th,
    .preview-table td {
        padding: 8px 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    .preview-table th {
        background: #f8f9fa;
        font-weight: 600;
        position: sticky;
        top: 0;
    }

    .preview-table td {
        font-size: 13px;
        max-width: 200px;
        word-wrap: break-word;
    }

    .progress-container {
        margin: 20px 0;
    }

    .progress-bar {
        background: #f0f0f1;
        border-radius: 3px;
        height: 20px;
        overflow: hidden;
        margin-bottom: 10px;
    }

    .progress-fill {
        background: #0073aa;
        height: 100%;
        transition: width 0.3s ease;
    }

    .progress-text {
        text-align: center;
        color: #666;
        font-size: 14px;
    }

    .result-summary {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }

    .result-stat {
        text-align: center;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 4px;
        flex: 1;
    }

    .result-stat .number {
        display: block;
        font-size: 28px;
        font-weight: bold;
        color: #0073aa;
        line-height: 1;
    }

    .result-stat .label {
        display: block;
        margin-top: 5px;
        color: #666;
        font-size: 14px;
    }

    .import-errors {
        margin-top: 20px;
    }

    .error-list {
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .error-item {
        padding: 10px;
        border-bottom: 1px solid #eee;
    }

    .error-item:last-child {
        border-bottom: none;
    }

    .error-item strong {
        color: #d63638;
    }

    .error-item pre {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 3px;
        margin-top: 5px;
        font-size: 12px;
        overflow-x: auto;
    }

    .preview-actions {
        margin-top: 20px;
        text-align: center;
    }
</style>