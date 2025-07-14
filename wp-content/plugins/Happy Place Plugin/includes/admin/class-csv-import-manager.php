<?php

namespace HPH\Admin;

if (!defined('ABSPATH')) {
    exit;
}

use Exception;
use WP_Post;

/**
 * Comprehensive CSV Import Tool for All Post Types
 * 
 * Handles importing data for:
 * - Listings
 * - Agents
 * - Communities
 * - Cities
 * - Transactions
 * - Open Houses
 * - Local Places
 * - Team Members
 */
class CSV_Import_Manager
{
    private static ?self $instance = null;

    /**
     * Supported post types and their configurations
     */
    private array $supported_post_types = [
        'listing' => [
            'label' => 'Listings',
            'template' => 'listings-template.csv',
            'required_fields' => ['title', 'price', 'street_address', 'city'],
            'unique_field' => 'mls_number'
        ],
        'agent' => [
            'label' => 'Agents',
            'template' => 'agents-template.csv',
            'required_fields' => ['title', 'email'],
            'unique_field' => 'email'
        ],
        'community' => [
            'label' => 'Communities',
            'template' => 'communities-template.csv',
            'required_fields' => ['title', 'city'],
            'unique_field' => 'title'
        ],
        'city' => [
            'label' => 'Cities',
            'template' => 'cities-template.csv',
            'required_fields' => ['title', 'state'],
            'unique_field' => 'title'
        ],
        'transaction' => [
            'label' => 'Transactions',
            'template' => 'transactions-template.csv',
            'required_fields' => ['title', 'transaction_type', 'listing_id'],
            'unique_field' => 'transaction_id'
        ],
        'open-house' => [
            'label' => 'Open Houses',
            'template' => 'open-houses-template.csv',
            'required_fields' => ['title', 'listing_id', 'start_date', 'start_time'],
            'unique_field' => null
        ],
        'local-place' => [
            'label' => 'Local Places',
            'template' => 'local-places-template.csv',
            'required_fields' => ['title', 'place_type', 'address'],
            'unique_field' => 'title'
        ],
        'team' => [
            'label' => 'Team Members',
            'template' => 'team-template.csv',
            'required_fields' => ['title', 'email', 'role'],
            'unique_field' => 'email'
        ]
    ];

    public static function get_instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        add_action('admin_menu', [$this, 'add_import_pages']);
        add_action('wp_ajax_hph_import_csv', [$this, 'handle_csv_import']);
        add_action('wp_ajax_hph_preview_csv', [$this, 'handle_csv_preview']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_import_scripts']);
        add_action('init', [$this, 'create_csv_templates']);
    }

    /**
     * Add CSV import pages to admin menu
     */
    public function add_import_pages(): void
    {
        // Main CSV import page
        add_submenu_page(
            'happy-place',
            'CSV Import Manager',
            'CSV Import',
            'manage_options',
            'happy-place-csv-import',
            [$this, 'render_main_import_page']
        );

        // Individual import pages for each post type
        foreach ($this->supported_post_types as $post_type => $config) {
            add_submenu_page(
                null, // Hide from menu, accessible via main page
                "Import {$config['label']}",
                "Import {$config['label']}",
                'manage_options',
                "happy-place-import-{$post_type}",
                [$this, 'render_import_page']
            );
        }
    }

    /**
     * Render the main CSV import page
     */
    public function render_main_import_page(): void
    {
?>
        <div class="wrap">
            <h1>CSV Import Manager</h1>
            <p>Import data for all post types using CSV files. Choose a post type below to get started.</p>

            <div class="hph-import-types-grid">
                <?php foreach ($this->supported_post_types as $post_type => $config): ?>
                    <div class="hph-import-type-card">
                        <div class="card-header">
                            <h3><?php echo esc_html($config['label']); ?></h3>
                            <span class="post-type"><?php echo esc_html($post_type); ?></span>
                        </div>
                        <div class="card-content">
                            <p>Import <?php echo esc_html(strtolower($config['label'])); ?> from CSV file.</p>
                            <div class="card-stats">
                                <?php
                                $count = wp_count_posts($post_type);
                                $total = $count->publish + $count->draft + $count->private;
                                ?>
                                <span class="stat">
                                    <strong><?php echo esc_html($total); ?></strong> existing
                                </span>
                            </div>
                        </div>
                        <div class="card-actions">
                            <a href="<?php echo esc_url(admin_url("admin.php?page=happy-place-import-{$post_type}")); ?>"
                                class="button button-primary">
                                Import <?php echo esc_html($config['label']); ?>
                            </a>
                            <a href="<?php echo esc_url(HPH_URL . "templates/{$config['template']}"); ?>"
                                class="button" download>
                                Download Template
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="hph-import-help">
                <h2>Getting Started</h2>
                <ol>
                    <li><strong>Choose a post type</strong> from the options above</li>
                    <li><strong>Download the CSV template</strong> for that post type</li>
                    <li><strong>Fill in your data</strong> following the template format</li>
                    <li><strong>Upload and import</strong> your completed CSV file</li>
                </ol>

                <div class="notice notice-info">
                    <p><strong>Important Notes:</strong></p>
                    <ul>
                        <li>Always backup your site before importing large amounts of data</li>
                        <li>Test with a small sample first to ensure proper formatting</li>
                        <li>Required fields must be filled for successful import</li>
                        <li>Images should be uploaded separately and referenced by URL</li>
                    </ul>
                </div>
            </div>
        </div>

        <style>
            .hph-import-types-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 20px;
                margin: 30px 0;
            }

            .hph-import-type-card {
                background: white;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 0;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .hph-import-type-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }

            .card-header {
                background: #f8f9fa;
                padding: 20px;
                border-bottom: 1px solid #eee;
                border-radius: 8px 8px 0 0;
            }

            .card-header h3 {
                margin: 0 0 5px 0;
                color: #2c3e50;
            }

            .post-type {
                background: #e3f2fd;
                color: #1976d2;
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 12px;
                font-weight: 500;
            }

            .card-content {
                padding: 20px;
            }

            .card-content p {
                margin: 0 0 15px 0;
                color: #666;
            }

            .card-stats {
                display: flex;
                gap: 15px;
            }

            .stat {
                color: #666;
                font-size: 14px;
            }

            .card-actions {
                padding: 20px;
                border-top: 1px solid #eee;
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }

            .hph-import-help {
                background: #f9f9f9;
                border: 1px solid #ddd;
                padding: 25px;
                border-radius: 8px;
                margin-top: 30px;
            }

            .hph-import-help h2 {
                margin-top: 0;
                color: #2c3e50;
            }

            .hph-import-help ol {
                padding-left: 20px;
            }

            .hph-import-help li {
                margin-bottom: 8px;
                line-height: 1.5;
            }
        </style>
    <?php
    }

    /**
     * Render individual post type import page
     */
    public function render_import_page(): void
    {
        $post_type = str_replace('happy-place-import-', '', $_GET['page']);

        if (!isset($this->supported_post_types[$post_type])) {
            wp_die('Invalid post type for import.');
        }

        $config = $this->supported_post_types[$post_type];
    ?>
        <div class="wrap">
            <h1>Import <?php echo esc_html($config['label']); ?></h1>

            <div class="hph-breadcrumb">
                <a href="<?php echo esc_url(admin_url('admin.php?page=happy-place-csv-import')); ?>">CSV Import Manager</a>
                <span class="separator"> › </span>
                <span>Import <?php echo esc_html($config['label']); ?></span>
            </div>

            <div class="hph-import-instructions">
                <h2>Instructions</h2>
                <ol>
                    <li>Download the <a href="<?php echo esc_url(HPH_URL . 'templates/' . $config['template']); ?>" download>CSV template for <?php echo esc_html($config['label']); ?></a></li>
                    <li>Fill in your data following the template format</li>
                    <li>Required fields: <strong><?php echo esc_html(implode(', ', $config['required_fields'])); ?></strong></li>
                    <li>Upload your completed CSV file below</li>
                    <li>Review the preview and click "Import <?php echo esc_html($config['label']); ?>"</li>
                </ol>

                <div class="notice notice-info">
                    <p><strong>Important:</strong>
                        <?php if ($config['unique_field']): ?>
                            Make sure <?php echo esc_html($config['unique_field']); ?> values are unique to avoid duplicates.
                        <?php else: ?>
                            Each row will create a new entry. Make sure your data is accurate.
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <form id="hph-csv-import-form" enctype="multipart/form-data" data-post-type="<?php echo esc_attr($post_type); ?>">
                <?php wp_nonce_field('hph_import_csv', 'hph_csv_import_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="csv_file">CSV File</label>
                        </th>
                        <td>
                            <input type="file"
                                name="import_csv"
                                id="csv_file"
                                accept=".csv"
                                required />
                            <p class="description">Select your CSV file containing <?php echo esc_html(strtolower($config['label'])); ?> data.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="import_mode">Import Mode</label>
                        </th>
                        <td>
                            <select name="import_mode" id="import_mode">
                                <option value="create_only">Create New Entries Only</option>
                                <?php if ($config['unique_field']): ?>
                                    <option value="update_existing">Update Existing by <?php echo esc_html($config['unique_field']); ?></option>
                                    <option value="create_and_update">Create New & Update Existing</option>
                                <?php endif; ?>
                            </select>
                            <p class="description">Choose how to handle existing entries.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="default_status">Default Status</label>
                        </th>
                        <td>
                            <select name="default_status" id="default_status">
                                <option value="draft">Draft (Review Required)</option>
                                <option value="publish">Publish Immediately</option>
                            </select>
                            <p class="description">Status for imported <?php echo esc_html(strtolower($config['label'])); ?>.</p>
                        </td>
                    </tr>
                </table>

                <div class="hph-import-actions">
                    <button type="button" id="preview-csv" class="button">Preview CSV Data</button>
                    <button type="submit" id="import-csv" class="button button-primary" disabled>Import <?php echo esc_html($config['label']); ?></button>
                </div>
            </form>

            <div id="csv-preview" class="hph-csv-preview" style="display: none;">
                <h2>CSV Preview</h2>
                <div id="preview-content"></div>
            </div>

            <div id="import-progress" class="hph-import-progress" style="display: none;">
                <h2>Import Progress</h2>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 0%;"></div>
                </div>
                <div class="progress-text">Preparing import...</div>
            </div>

            <div id="import-results" class="hph-import-results" style="display: none;">
                <h2>Import Results</h2>
                <div id="results-content"></div>
            </div>
        </div>
    <?php
        $this->render_import_styles();
    }

    /**
     * Render import page styles
     */
    private function render_import_styles(): void
    {
    ?>
        <style>
            .hph-breadcrumb {
                margin-bottom: 20px;
                color: #666;
            }

            .hph-breadcrumb a {
                color: #0073aa;
                text-decoration: none;
            }

            .separator {
                margin: 0 8px;
            }

            .hph-import-instructions {
                background: #f9f9f9;
                border: 1px solid #ddd;
                padding: 20px;
                margin: 20px 0;
                border-radius: 4px;
            }

            .hph-csv-preview {
                margin-top: 30px;
                border: 1px solid #ddd;
                border-radius: 4px;
                overflow: hidden;
            }

            .hph-csv-preview h2 {
                background: #f1f1f1;
                margin: 0;
                padding: 15px 20px;
                border-bottom: 1px solid #ddd;
            }

            .preview-table {
                width: 100%;
                border-collapse: collapse;
                margin: 0;
            }

            .preview-table th,
            .preview-table td {
                padding: 10px;
                border-bottom: 1px solid #eee;
                text-align: left;
                font-size: 12px;
            }

            .preview-table th {
                background: #f9f9f9;
                font-weight: 600;
            }

            .preview-table tr:nth-child(even) {
                background: #f9f9f9;
            }

            .hph-import-progress {
                margin-top: 30px;
            }

            .progress-bar {
                width: 100%;
                height: 30px;
                background: #f1f1f1;
                border-radius: 4px;
                overflow: hidden;
                margin: 15px 0;
            }

            .progress-fill {
                height: 100%;
                background: #0073aa;
                transition: width 0.3s ease;
            }

            .progress-text {
                text-align: center;
                font-weight: 600;
            }

            .hph-import-results {
                margin-top: 30px;
            }

            .result-summary {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin: 20px 0;
            }

            .result-stat {
                background: white;
                border: 1px solid #ddd;
                padding: 15px;
                border-radius: 4px;
                text-align: center;
            }

            .result-stat .number {
                font-size: 24px;
                font-weight: bold;
                color: #0073aa;
            }

            .result-stat .label {
                color: #666;
                font-size: 14px;
            }

            .import-errors {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 4px;
                margin-top: 20px;
            }

            .import-errors h3 {
                background: #f1f1f1;
                margin: 0;
                padding: 15px 20px;
                border-bottom: 1px solid #ddd;
            }

            .error-list {
                padding: 20px;
                max-height: 300px;
                overflow-y: auto;
            }

            .error-item {
                background: #fff2f2;
                border: 1px solid #f5c6cb;
                padding: 10px;
                margin-bottom: 10px;
                border-radius: 4px;
            }
        </style>
<?php
    }

    /**
     * Handle CSV preview
     */
    public function handle_csv_preview(): void
    {
        check_ajax_referer('hph_import_csv', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('No file uploaded or upload error');
        }

        $post_type = sanitize_key($_POST['post_type']);
        if (!isset($this->supported_post_types[$post_type])) {
            wp_send_json_error('Invalid post type');
        }

        $file_path = $_FILES['csv_file']['tmp_name'];
        $preview_data = $this->parse_csv_preview($file_path, $post_type);

        wp_send_json_success($preview_data);
    }

    /**
     * Parse CSV file for preview
     */
    private function parse_csv_preview(string $file_path, string $post_type): array
    {
        $config = $this->supported_post_types[$post_type];
        $data = [];
        $errors = [];

        if (($handle = fopen($file_path, 'r')) !== false) {
            $headers = fgetcsv($handle);
            $row_count = 0;
            $preview_limit = 5; // Show first 5 rows in preview

            while (($row = fgetcsv($handle)) !== false && $row_count < $preview_limit) {
                $row_data = array_combine($headers, $row);

                // Validate required fields
                $row_errors = [];
                foreach ($config['required_fields'] as $field) {
                    if (empty($row_data[$field])) {
                        $row_errors[] = "Missing required field: {$field}";
                    }
                }

                $data[] = [
                    'data' => $row_data,
                    'errors' => $row_errors,
                    'row_number' => $row_count + 2 // +2 because CSV rows start at 2 (after header)
                ];

                $row_count++;
            }

            fclose($handle);
        }

        return [
            'headers' => $headers ?? [],
            'rows' => $data,
            'total_rows' => $row_count,
            'required_fields' => $config['required_fields'],
            'post_type' => $post_type
        ];
    }

    /**
     * Handle CSV import
     */
    public function handle_csv_import(): void
    {
        check_ajax_referer('hph_import_csv', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $post_type = sanitize_key($_POST['post_type']);
        $import_mode = sanitize_key($_POST['import_mode']);
        $default_status = sanitize_key($_POST['default_status']);

        if (!isset($this->supported_post_types[$post_type])) {
            wp_send_json_error('Invalid post type');
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('No file uploaded or upload error');
        }

        $file_path = $_FILES['csv_file']['tmp_name'];
        $results = $this->process_csv_import($file_path, $post_type, $import_mode, $default_status);

        wp_send_json_success($results);
    }

    /**
     * Process CSV import
     */
    private function process_csv_import(string $file_path, string $post_type, string $import_mode, string $default_status): array
    {
        $config = $this->supported_post_types[$post_type];
        $results = [
            'total_rows' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        if (($handle = fopen($file_path, 'r')) !== false) {
            $headers = fgetcsv($handle);
            $row_number = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $row_number++;
                $results['total_rows']++;

                $row_data = array_combine($headers, $row);

                try {
                    $result = $this->import_single_row($row_data, $post_type, $config, $import_mode, $default_status);

                    if ($result['action'] === 'created') {
                        $results['created']++;
                    } elseif ($result['action'] === 'updated') {
                        $results['updated']++;
                    } else {
                        $results['skipped']++;
                    }
                } catch (Exception $e) {
                    $results['errors'][] = [
                        'row' => $row_number,
                        'message' => $e->getMessage(),
                        'data' => $row_data
                    ];
                }
            }

            fclose($handle);
        }

        return $results;
    }

    /**
     * Import a single row of data
     */
    private function import_single_row(array $row_data, string $post_type, array $config, string $import_mode, string $default_status): array
    {
        // Validate required fields
        foreach ($config['required_fields'] as $field) {
            if (empty($row_data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        // Check for existing entry if unique field is specified
        $existing_post = null;
        if ($config['unique_field'] && !empty($row_data[$config['unique_field']])) {
            $existing_post = $this->find_existing_post($post_type, $config['unique_field'], $row_data[$config['unique_field']]);
        }

        // Determine action based on import mode
        if ($existing_post && $import_mode === 'create_only') {
            return ['action' => 'skipped', 'reason' => 'Entry already exists'];
        }

        if (!$existing_post && $import_mode === 'update_existing') {
            return ['action' => 'skipped', 'reason' => 'Entry not found for update'];
        }

        // Prepare post data
        $post_data = $this->prepare_post_data($row_data, $post_type, $default_status);

        if ($existing_post) {
            // Update existing post
            $post_data['ID'] = $existing_post->ID;
            $post_id = wp_update_post($post_data);
            $action = 'updated';
        } else {
            // Create new post
            $post_id = wp_insert_post($post_data);
            $action = 'created';
        }

        if (is_wp_error($post_id)) {
            throw new Exception("Failed to save post: " . $post_id->get_error_message());
        }

        // Save custom fields
        $this->save_custom_fields($post_id, $row_data, $post_type);

        return ['action' => $action, 'post_id' => $post_id];
    }

    /**
     * Find existing post by unique field
     */
    private function find_existing_post(string $post_type, string $field, string $value): ?WP_Post
    {
        $posts = get_posts([
            'post_type' => $post_type,
            'meta_query' => [
                [
                    'key' => $field,
                    'value' => $value,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1,
            'post_status' => 'any'
        ]);

        return $posts ? $posts[0] : null;
    }

    /**
     * Prepare post data from CSV row
     */
    private function prepare_post_data(array $row_data, string $post_type, string $default_status): array
    {
        return [
            'post_title' => sanitize_text_field($row_data['title'] ?? ''),
            'post_content' => wp_kses_post($row_data['description'] ?? $row_data['content'] ?? ''),
            'post_excerpt' => sanitize_textarea_field($row_data['excerpt'] ?? $row_data['short_description'] ?? ''),
            'post_type' => $post_type,
            'post_status' => $default_status,
            'post_author' => get_current_user_id(),
            'meta_input' => $this->prepare_meta_fields($row_data, $post_type)
        ];
    }

    /**
     * Prepare meta fields from CSV row
     */
    private function prepare_meta_fields(array $row_data, string $post_type): array
    {
        $meta_fields = [];

        // Remove standard post fields to avoid duplication
        $standard_fields = ['title', 'content', 'description', 'excerpt', 'short_description'];

        foreach ($row_data as $key => $value) {
            if (!in_array($key, $standard_fields) && !empty($value)) {
                $meta_fields[$key] = sanitize_text_field($value);
            }
        }

        return $meta_fields;
    }

    /**
     * Save custom fields for post
     */
    private function save_custom_fields(int $post_id, array $row_data, string $post_type): void
    {
        // Remove standard post fields
        $standard_fields = ['title', 'content', 'description', 'excerpt', 'short_description'];

        foreach ($row_data as $key => $value) {
            if (!in_array($key, $standard_fields) && !empty($value)) {
                update_post_meta($post_id, $key, sanitize_text_field($value));
            }
        }

        // Post-type specific field handling
        if ($post_type === 'agent' && !empty($row_data['email'])) {
            // Try to link agent to WordPress user
            $user = get_user_by('email', $row_data['email']);
            if ($user) {
                update_post_meta($post_id, 'user_id', $user->ID);
            }
        }
    }

    /**
     * Create CSV templates for all post types
     */
    public function create_csv_templates(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $templates_dir = HPH_PATH . 'templates/';

        // Ensure templates directory exists
        if (!file_exists($templates_dir)) {
            wp_mkdir_p($templates_dir);
        }

        // Create templates for each post type if they don't exist
        foreach ($this->supported_post_types as $post_type => $config) {
            $template_path = $templates_dir . $config['template'];

            if (!file_exists($template_path)) {
                $this->generate_csv_template($post_type, $template_path);
            }
        }
    }

    /**
     * Generate CSV template for post type
     */
    private function generate_csv_template(string $post_type, string $file_path): void
    {
        $templates = [
            'agent' => [
                'headers' => ['title', 'email', 'phone', 'bio', 'license_number', 'specialties', 'years_experience', 'office_address', 'website', 'social_facebook', 'social_instagram', 'social_linkedin'],
                'sample' => ['John Smith', 'john@example.com', '555-0123', 'Experienced real estate agent...', 'RE123456', 'Residential, Commercial', '10', '123 Main St, City, State 12345', 'https://johnsmith.realtor', 'facebook.com/johnsmith', 'instagram.com/johnsmith', 'linkedin.com/in/johnsmith']
            ],
            'community' => [
                'headers' => ['title', 'description', 'city', 'state', 'zip_code', 'community_type', 'hoa_fee', 'amenities', 'school_district', 'average_home_price'],
                'sample' => ['Sunset Hills', 'Beautiful suburban community...', 'Wilmington', 'DE', '19803', 'Subdivision', '150', 'Pool, Playground, Tennis Court', 'Red Clay School District', '425000']
            ],
            'city' => [
                'headers' => ['title', 'state', 'county', 'population', 'description', 'zip_codes', 'area_sq_miles', 'median_income', 'school_districts'],
                'sample' => ['Wilmington', 'Delaware', 'New Castle', '70851', 'The largest city in Delaware...', '19801,19802,19803', '17.0', '65000', 'Red Clay, Brandywine']
            ],
            'transaction' => [
                'headers' => ['title', 'transaction_type', 'listing_id', 'buyer_agent_id', 'seller_agent_id', 'sale_price', 'commission', 'closing_date', 'transaction_status'],
                'sample' => ['123 Main St Sale', 'Sale', '123', '456', '789', '450000', '27000', '2024-12-01', 'Closed']
            ],
            'open-house' => [
                'headers' => ['title', 'listing_id', 'start_date', 'start_time', 'end_time', 'description', 'registration_required', 'max_attendees'],
                'sample' => ['Open House - 123 Main St', '123', '2024-12-15', '14:00', '16:00', 'Come see this beautiful home...', 'no', '20']
            ],
            'local-place' => [
                'headers' => ['title', 'place_type', 'address', 'city', 'state', 'zip_code', 'phone', 'website', 'description', 'rating'],
                'sample' => ['Riverfront Park', 'Park', '100 River Rd', 'Wilmington', 'DE', '19801', '555-0199', 'https://city.gov/parks', 'Beautiful waterfront park...', '4.5']
            ],
            'team' => [
                'headers' => ['title', 'email', 'phone', 'role', 'department', 'bio', 'hire_date', 'status'],
                'sample' => ['Jane Doe', 'jane@example.com', '555-0198', 'Senior Agent', 'Sales', 'Top performing agent...', '2020-01-15', 'active']
            ]
        ];

        if (!isset($templates[$post_type])) {
            return;
        }

        $template = $templates[$post_type];

        $csv_content = [];
        $csv_content[] = $template['headers'];
        $csv_content[] = $template['sample'];

        $file = fopen($file_path, 'w');
        if ($file) {
            foreach ($csv_content as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        }
    }

    /**
     * Enqueue import scripts
     */
    public function enqueue_import_scripts($hook): void
    {
        if (strpos($hook, 'happy-place-') === false || strpos($hook, 'import') === false) {
            return;
        }

        wp_enqueue_script(
            'hph-csv-import',
            HPH_URL . 'assets/js/csv-import.js',
            ['jquery'],
            HPH_VERSION,
            true
        );

        wp_localize_script('hph-csv-import', 'hphCsvImport', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_import_csv'),
            'strings' => [
                'processing' => __('Processing...', 'happy-place'),
                'error' => __('An error occurred during import.', 'happy-place'),
                'success' => __('Import completed successfully!', 'happy-place')
            ]
        ]);
    }
}

// Initialize the CSV Import Manager
CSV_Import_Manager::get_instance();
