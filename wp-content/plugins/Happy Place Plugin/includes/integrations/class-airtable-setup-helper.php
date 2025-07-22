<?php
namespace HappyPlace\Integrations;

/**
 * Airtable Setup Helper
 * 
 * Provides tools and documentation for setting up Airtable tables
 * compatible with the Happy Place Plugin sync functionality.
 */
class Airtable_Setup_Helper {
    
    /**
     * Generate HTML setup instructions
     */
    public static function get_setup_instructions_html(): string {
        $template = Airtable_Two_Way_Sync::get_table_template();
        
        ob_start();
        ?>
        <div class="airtable-setup-guide">
            <h2>🗂️ Airtable Table Setup Guide</h2>
            
            <div class="setup-overview">
                <p><strong>Follow these steps to create an Airtable table compatible with the Happy Place Plugin:</strong></p>
            </div>

            <div class="setup-steps">
                <div class="step">
                    <h3>Step 1: Create Airtable Account & Base</h3>
                    <ol>
                        <li>Go to <a href="https://airtable.com" target="_blank">Airtable.com</a> and create an account</li>
                        <li>Create a new base or open an existing base</li>
                        <li>Add a new table named: <code><?php echo esc_html($template['table_name']); ?></code></li>
                    </ol>
                </div>

                <div class="step">
                    <h3>Step 2: Configure Table Fields</h3>
                    <p>Add the following fields to your table in this exact order:</p>
                    
                    <div class="fields-table">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Field Name</th>
                                    <th>Field Type</th>
                                    <th>Description</th>
                                    <th>Options/Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($template['fields'] as $field): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($field['name']); ?></strong></td>
                                    <td><?php echo esc_html(ucfirst($field['type'])); ?></td>
                                    <td><?php echo esc_html($field['description']); ?></td>
                                    <td>
                                        <?php if (isset($field['options'])): ?>
                                            <?php if (isset($field['options']['choices'])): ?>
                                                <strong>Options:</strong>
                                                <?php 
                                                $choices = array_column($field['options']['choices'], 'name');
                                                echo esc_html(implode(', ', $choices));
                                                ?>
                                            <?php elseif (isset($field['options']['precision'])): ?>
                                                <strong>Precision:</strong> <?php echo esc_html($field['options']['precision']); ?>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if (isset($field['required']) && $field['required']): ?>
                                            <span class="required">*Required</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="step">
                    <h3>Step 3: Get Your Airtable Credentials</h3>
                    <ol>
                        <li><strong>Base ID:</strong> Copy from your Airtable URL<br>
                            <code>https://airtable.com/<mark>app1234567890abcd</mark>/tbl...</code><br>
                            <small>The Base ID starts with "app" and is found in your table URL</small>
                        </li>
                        <li><strong>Access Token:</strong> 
                            <a href="https://airtable.com/create/tokens" target="_blank">Create Personal Access Token</a><br>
                            <small>Required permissions: data.records:read, data.records:write</small>
                        </li>
                        <li><strong>Table Name:</strong> Use exactly <code><?php echo esc_html($template['table_name']); ?></code></li>
                    </ol>
                </div>

                <div class="step">
                    <h3>Step 4: Configure Happy Place Plugin</h3>
                    <ol>
                        <li>Go to <strong>Happy Place → Integrations</strong> in your WordPress admin</li>
                        <li>Enter your Airtable Base ID and Access Token</li>
                        <li>Test the connection</li>
                        <li>Configure sync settings and schedule</li>
                    </ol>
                </div>
            </div>

            <div class="sample-data">
                <h3>💡 Sample Data</h3>
                <p>Add this sample record to test your setup:</p>
                <div class="sample-record">
                    <ul>
                        <li><strong>Property Name:</strong> Beautiful Family Home</li>
                        <li><strong>Street Address:</strong> 123 Main Street</li>
                        <li><strong>City:</strong> Anytown</li>
                        <li><strong>State:</strong> CA</li>
                        <li><strong>ZIP Code:</strong> 90210</li>
                        <li><strong>List Price:</strong> $750,000</li>
                        <li><strong>Bedrooms:</strong> 4</li>
                        <li><strong>Bathrooms:</strong> 2.5</li>
                        <li><strong>Square Footage:</strong> 2,100</li>
                        <li><strong>Property Type:</strong> Single Family</li>
                        <li><strong>Listing Status:</strong> active</li>
                    </ul>
                </div>
            </div>

            <div class="troubleshooting">
                <h3>🔧 Troubleshooting</h3>
                <div class="troubleshooting-item">
                    <h4>Connection Issues:</h4>
                    <ul>
                        <li>Verify your Access Token has the correct permissions</li>
                        <li>Check that the Base ID is copied correctly (starts with "app")</li>
                        <li>Ensure the table name matches exactly: "<?php echo esc_html($template['table_name']); ?>"</li>
                    </ul>
                </div>
                
                <div class="troubleshooting-item">
                    <h4>Sync Issues:</h4>
                    <ul>
                        <li>Make sure all required field names match exactly</li>
                        <li>Check that "Property Name" field is not empty</li>
                        <li>Verify select field options match the allowed values</li>
                    </ul>
                </div>
            </div>

            <div class="helpful-links">
                <h3>📚 Helpful Links</h3>
                <ul>
                    <li><a href="https://airtable.com/developers/web/api/introduction" target="_blank">Airtable API Documentation</a></li>
                    <li><a href="https://airtable.com/create/tokens" target="_blank">Create Personal Access Token</a></li>
                    <li><a href="https://support.airtable.com/docs/creating-a-new-table" target="_blank">Creating Tables in Airtable</a></li>
                </ul>
            </div>
        </div>

        <style>
            .airtable-setup-guide {
                max-width: 1000px;
                margin: 20px 0;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            
            .setup-steps .step {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 6px;
                margin-bottom: 20px;
                padding: 20px;
            }
            
            .step h3 {
                margin-top: 0;
                color: #2271b1;
                border-bottom: 2px solid #f0f0f1;
                padding-bottom: 10px;
            }
            
            .fields-table {
                margin: 15px 0;
                overflow-x: auto;
            }
            
            .fields-table table {
                width: 100%;
                font-size: 13px;
            }
            
            .fields-table th {
                background: #f9f9f9;
                font-weight: 600;
            }
            
            .required {
                color: #d63638;
                font-weight: bold;
            }
            
            .sample-data {
                background: #f8f9fa;
                border-left: 4px solid #2271b1;
                padding: 15px 20px;
                margin: 20px 0;
            }
            
            .sample-record ul {
                columns: 2;
                column-gap: 30px;
            }
            
            .troubleshooting {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 6px;
                padding: 20px;
                margin: 20px 0;
            }
            
            .troubleshooting h3 {
                margin-top: 0;
                color: #856404;
            }
            
            .troubleshooting-item {
                margin-bottom: 15px;
            }
            
            .troubleshooting-item h4 {
                margin-bottom: 8px;
                color: #856404;
            }
            
            .helpful-links {
                background: #d1ecf1;
                border: 1px solid #b8daff;
                border-radius: 6px;
                padding: 20px;
                margin: 20px 0;
            }
            
            .helpful-links h3 {
                margin-top: 0;
                color: #0c5460;
            }
            
            code, mark {
                background: #f1f3f4;
                padding: 2px 6px;
                border-radius: 3px;
                font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
                font-size: 13px;
            }
            
            mark {
                background: #fff3cd;
                color: #856404;
            }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate downloadable JSON template
     */
    public static function get_json_template(): string {
        $template_file = plugin_dir_path(__FILE__) . 'airtable-table-template.json';
        
        if (file_exists($template_file)) {
            return file_get_contents($template_file);
        }
        
        // Fallback to generated template
        return wp_json_encode(Airtable_Two_Way_Sync::get_table_template(), JSON_PRETTY_PRINT);
    }

    /**
     * Export template as downloadable file
     */
    public static function download_template() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $template_json = self::get_json_template();
        $filename = 'airtable-real-estate-template-' . date('Y-m-d') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($template_json));
        
        echo $template_json;
        exit;
    }

    /**
     * Validate field mapping compatibility
     */
    public static function validate_field_mapping(): array {
        $template = Airtable_Two_Way_Sync::get_table_template();
        $sync_mapping = (new Airtable_Two_Way_Sync('dummy', 'dummy'))->get_field_mapping();
        
        $template_fields = array_column($template['fields'], 'name');
        $sync_fields = array_column($sync_mapping, 'airtable_field');
        
        $missing_in_template = array_diff($sync_fields, $template_fields);
        $missing_in_sync = array_diff($template_fields, $sync_fields);
        
        return [
            'compatible' => empty($missing_in_template) && empty($missing_in_sync),
            'template_fields' => $template_fields,
            'sync_fields' => $sync_fields,
            'missing_in_template' => $missing_in_template,
            'missing_in_sync' => $missing_in_sync
        ];
    }
}
