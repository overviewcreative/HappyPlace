<?php
/**
 * Dashboard AJAX Handler - Comprehensive Dashboard Management
 *
 * Handles all dashboard-related AJAX operations including:
 * - Dashboard section loading
 * - Agent dashboard functionality
 * - Dashboard settings management
 * - Real-time dashboard updates
 * - Dashboard tool operations
 *
 * @package HappyPlace
 * @subpackage Api\Ajax\Handlers
 * @since 2.0.0
 */

namespace HappyPlace\Api\Ajax\Handlers;

use HappyPlace\Api\Ajax\Base_Ajax_Handler;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dashboard AJAX Handler Class
 *
 * Consolidates dashboard functionality from multiple sources:
 * - Dashboard section management
 * - Agent dashboard operations
 * - Dashboard widgets and tools
 * - Real-time data updates
 */
class Dashboard_Ajax extends Base_Ajax_Handler {

    /**
     * Dashboard configuration
     */
    private array $dashboard_config = [
        'cache_duration' => 300, // 5 minutes
        'max_items_per_section' => 50,
        'allowed_sections' => [
            'overview',
            'listings', 
            'clients',
            'transactions',
            'leads',
            'calendar',
            'reports',
            'settings'
        ]
    ];

    /**
     * Define AJAX actions and their configurations
     */
    protected function get_actions(): array {
        return [
            // Core Dashboard Actions
            'dashboard_action' => [
                'callback' => 'handle_dashboard_action',
                'capability' => 'edit_posts',
                'rate_limit' => 20,
                'cache' => 300
            ],
            // ✅ ADD MISSING ACTION - dashboard_quick_stats
            'dashboard_quick_stats' => [
                'callback' => 'handle_dashboard_quick_stats',
                'capability' => 'read',
                'rate_limit' => 30,
                'cache' => 300
            ],
            
            // ✅ ADD MISSING ACTION - dashboard_recent_activity  
            'dashboard_recent_activity' => [
                'callback' => 'handle_dashboard_recent_activity',
                'capability' => 'read',
                'rate_limit' => 20,
                'cache' => 600
            ],
            
            // ✅ ADD MISSING ACTIONS - Marketing Suite Dashboard
            'marketing_suite_config' => [
                'callback' => 'handle_marketing_suite_config',
                'capability' => 'edit_posts',
                'rate_limit' => 30,
                'cache' => 600
            ],
            'marketing_suite_templates' => [
                'callback' => 'handle_marketing_suite_templates',
                'capability' => 'edit_posts',
                'rate_limit' => 20,
                'cache' => 300
            ],
            'marketing_suite_generate_flyer' => [
                'callback' => 'handle_marketing_suite_generate_flyer',
                'capability' => 'edit_posts',
                'rate_limit' => 10
            ],
            'marketing_suite_save_template' => [
                'callback' => 'handle_marketing_suite_save_template',
                'capability' => 'edit_posts',
                'rate_limit' => 15
            ],
            'marketing_suite_get_listings' => [
                'callback' => 'handle_marketing_suite_get_listings',
                'capability' => 'read',
                'rate_limit' => 30,
                'cache' => 300
            ],
            'marketing_suite_upload_assets' => [
                'callback' => 'handle_marketing_suite_upload_assets',
                'capability' => 'upload_files',
                'rate_limit' => 5
            ],
            
            // ✅ ADD MISSING ACTIONS - Marketing Suite Initialization
            'get_marketing_suite_data' => [
                'callback' => 'handle_get_marketing_suite_data',
                'capability' => 'read',
                'rate_limit' => 20,
                'cache' => 300
            ],
            'initialize_marketing_suite' => [
                'callback' => 'handle_initialize_marketing_suite',
                'capability' => 'read',
                'rate_limit' => 30,
                'cache' => 600
            ],
            'load_dashboard_section' => [
                'callback' => 'handle_load_section',
                'capability' => 'read',
                'rate_limit' => 30,
                'cache' => 180
            ],
            'refresh_dashboard_data' => [
                'callback' => 'handle_refresh_data',
                'capability' => 'read',
                'rate_limit' => 15
            ],
            
            // Dashboard Settings
            'save_dashboard_settings' => [
                'callback' => 'handle_save_settings',
                'capability' => 'manage_options',
                'rate_limit' => 10
            ],
            'get_dashboard_settings' => [
                'callback' => 'handle_get_settings',
                'capability' => 'read',
                'rate_limit' => 20,
                'cache' => 600
            ],
            
            // Dashboard Widgets
            'load_widget_data' => [
                'callback' => 'handle_load_widget',
                'capability' => 'read',
                'rate_limit' => 40,
                'cache' => 300
            ],
            'save_widget_config' => [
                'callback' => 'handle_save_widget_config',
                'capability' => 'edit_posts',
                'rate_limit' => 10
            ],
            
            // Dashboard Tools
            'dashboard_quick_action' => [
                'callback' => 'handle_quick_action',
                'capability' => 'edit_posts',
                'rate_limit' => 25
            ],
            'export_dashboard_data' => [
                'callback' => 'handle_export_data',
                'capability' => 'export',
                'rate_limit' => 5
            ],
            
            // Real-time Updates
            'get_dashboard_notifications' => [
                'callback' => 'handle_get_notifications',
                'capability' => 'read',
                'rate_limit' => 60
            ],
            'mark_notification_read' => [
                'callback' => 'handle_mark_notification_read',
                'capability' => 'read',
                'rate_limit' => 100
            ],
            
            // ✅ NEW: Admin Menu AJAX Actions
            
            // Listings Management  
            'get_listings_overview' => [
                'callback' => 'handle_get_listings_overview',
                'capability' => 'edit_posts',
                'rate_limit' => 20,
                'cache' => 300
            ],
            'bulk_update_listings' => [
                'callback' => 'handle_bulk_update_listings',
                'capability' => 'edit_posts',
                'rate_limit' => 5
            ],
            
            // Integrations Hub
            'get_integration_status' => [
                'callback' => 'handle_get_integration_status',
                'capability' => 'manage_options',
                'rate_limit' => 15,
                'cache' => 600
            ],
            'test_integration_connection' => [
                'callback' => 'handle_test_integration_connection',
                'capability' => 'manage_options',
                'rate_limit' => 10
            ],
            
            // System Health & Metrics
            'get_system_metrics' => [
                'callback' => 'handle_get_system_metrics',
                'capability' => 'manage_options',
                'rate_limit' => 15,
                'cache' => 600
            ],
            
            // Tools & Maintenance
            'run_maintenance_task' => [
                'callback' => 'handle_run_maintenance_task',
                'capability' => 'manage_options',
                'rate_limit' => 5
            ]
        ];
    }

    /**
     * Handle general dashboard actions
     */
    public function handle_dashboard_action(): void {
        try {
            if (!$this->validate_required_params(['action_type' => 'string'])) {
                return;
            }

            $action_type = sanitize_text_field($_POST['action_type']);
            $data = $_POST['data'] ?? [];

            switch ($action_type) {
                case 'get_overview_stats':
                    $stats = $this->get_overview_statistics();
                    $this->send_success($stats);
                    break;

                case 'get_recent_activity':
                    $activity = $this->get_recent_activity();
                    $this->send_success($activity);
                    break;

                case 'update_dashboard_layout':
                    $result = $this->update_dashboard_layout($data);
                    $this->send_success($result);
                    break;

                default:
                    $this->send_error('Unknown dashboard action');
            }

        } catch (\Exception $e) {
            error_log('HPH Dashboard Ajax Exception: ' . $e->getMessage());
            $this->send_error('Dashboard action failed');
        }
    }

    /**
     * Handle dashboard section loading
     */
    public function handle_load_section(): void {
        try {
            if (!$this->validate_required_params(['section' => 'string'])) {
                return;
            }

            $section = sanitize_text_field($_POST['section']);
            $page = intval($_POST['page'] ?? 1);
            $filters = $_POST['filters'] ?? [];

            // Validate section
            if (!in_array($section, $this->dashboard_config['allowed_sections'])) {
                $this->send_error('Invalid dashboard section');
                return;
            }

            $section_data = $this->load_section_data($section, $page, $filters);
            
            $this->send_success([
                'section' => $section,
                'data' => $section_data,
                'page' => $page,
                'timestamp' => current_time('timestamp')
            ]);

        } catch (\Exception $e) {
            error_log('HPH Dashboard Section Load Exception: ' . $e->getMessage());
            $this->send_error('Failed to load dashboard section');
        }
    }

    /**
     * Handle dashboard data refresh
     */
    public function handle_refresh_data(): void {
        try {
            $sections = $_POST['sections'] ?? ['overview'];
            $results = [];

            foreach ($sections as $section) {
                if (in_array($section, $this->dashboard_config['allowed_sections'])) {
                    $results[$section] = $this->refresh_section_data($section);
                }
            }

            $this->send_success([
                'refreshed_sections' => $results,
                'timestamp' => current_time('timestamp')
            ]);

        } catch (\Exception $e) {
            error_log('HPH Dashboard Refresh Exception: ' . $e->getMessage());
            $this->send_error('Failed to refresh dashboard data');
        }
    }

    /**
     * Handle dashboard settings save
     */
    public function handle_save_settings(): void {
        try {
            if (!$this->validate_required_params(['settings' => 'array'])) {
                return;
            }

            $settings = $_POST['settings'];
            $user_id = get_current_user_id();

            // Sanitize and validate settings
            $sanitized_settings = $this->sanitize_dashboard_settings($settings);
            
            // Save user-specific dashboard settings
            $saved = update_user_meta($user_id, 'hph_dashboard_settings', $sanitized_settings);

            if ($saved !== false) {
                $this->send_success([
                    'message' => 'Dashboard settings saved successfully',
                    'settings' => $sanitized_settings
                ]);
            } else {
                $this->send_error('Failed to save dashboard settings');
            }

        } catch (\Exception $e) {
            error_log('HPH Dashboard Settings Save Exception: ' . $e->getMessage());
            $this->send_error('Failed to save settings');
        }
    }

    /**
     * Handle get dashboard settings
     */
    public function handle_get_settings(): void {
        try {
            $user_id = get_current_user_id();
            $settings = get_user_meta($user_id, 'hph_dashboard_settings', true);

            // Provide defaults if no settings exist
            if (empty($settings)) {
                $settings = $this->get_default_dashboard_settings();
            }

            $this->send_success([
                'settings' => $settings,
                'user_id' => $user_id
            ]);

        } catch (\Exception $e) {
            error_log('HPH Dashboard Get Settings Exception: ' . $e->getMessage());
            $this->send_error('Failed to load settings');
        }
    }

    /**
     * Handle widget data loading
     */
    public function handle_load_widget(): void {
        try {
            if (!$this->validate_required_params(['widget_type' => 'string'])) {
                return;
            }

            $widget_type = sanitize_text_field($_POST['widget_type']);
            $widget_config = $_POST['config'] ?? [];

            $widget_data = $this->get_widget_data($widget_type, $widget_config);

            $this->send_success([
                'widget_type' => $widget_type,
                'data' => $widget_data,
                'config' => $widget_config
            ]);

        } catch (\Exception $e) {
            error_log('HPH Dashboard Widget Load Exception: ' . $e->getMessage());
            $this->send_error('Failed to load widget data');
        }
    }

    /**
     * Handle widget configuration save
     */
    public function handle_save_widget_config(): void {
        try {
            if (!$this->validate_required_params([
                'widget_id' => 'string',
                'config' => 'array'
            ])) {
                return;
            }

            $widget_id = sanitize_text_field($_POST['widget_id']);
            $config = $_POST['config'];
            $user_id = get_current_user_id();

            // Save widget configuration
            $widget_configs = get_user_meta($user_id, 'hph_widget_configs', true) ?: [];
            $widget_configs[$widget_id] = $config;
            
            update_user_meta($user_id, 'hph_widget_configs', $widget_configs);

            $this->send_success([
                'message' => 'Widget configuration saved',
                'widget_id' => $widget_id
            ]);

        } catch (\Exception $e) {
            error_log('HPH Dashboard Widget Config Exception: ' . $e->getMessage());
            $this->send_error('Failed to save widget configuration');
        }
    }

    /**
     * Handle quick actions
     */
    public function handle_quick_action(): void {
        try {
            if (!$this->validate_required_params(['quick_action' => 'string'])) {
                return;
            }

            $action = sanitize_text_field($_POST['quick_action']);
            $params = $_POST['params'] ?? [];

            $result = $this->execute_quick_action($action, $params);

            $this->send_success([
                'action' => $action,
                'result' => $result
            ]);

        } catch (\Exception $e) {
            error_log('HPH Dashboard Quick Action Exception: ' . $e->getMessage());
            $this->send_error('Quick action failed');
        }
    }

    /**
     * Handle data export
     */
    public function handle_export_data(): void {
        try {
            if (!$this->validate_required_params(['export_type' => 'string'])) {
                return;
            }

            $export_type = sanitize_text_field($_POST['export_type']);
            $date_range = $_POST['date_range'] ?? [];
            $filters = $_POST['filters'] ?? [];

            $export_data = $this->generate_export_data($export_type, $date_range, $filters);

            $this->send_success([
                'export_type' => $export_type,
                'data' => $export_data,
                'filename' => $this->generate_export_filename($export_type)
            ]);

        } catch (\Exception $e) {
            error_log('HPH Dashboard Export Exception: ' . $e->getMessage());
            $this->send_error('Export failed');
        }
    }

    /**
     * Handle notifications retrieval
     */
    public function handle_get_notifications(): void {
        try {
            $user_id = get_current_user_id();
            $limit = intval($_POST['limit'] ?? 10);
            $offset = intval($_POST['offset'] ?? 0);

            $notifications = $this->get_user_notifications($user_id, $limit, $offset);

            $this->send_success([
                'notifications' => $notifications,
                'unread_count' => $this->get_unread_count($user_id)
            ]);

        } catch (\Exception $e) {
            error_log('HPH Dashboard Notifications Exception: ' . $e->getMessage());
            $this->send_error('Failed to load notifications');
        }
    }

    /**
     * Handle mark notification as read
     */
    public function handle_mark_notification_read(): void {
        try {
            if (!$this->validate_required_params(['notification_id' => 'int'])) {
                return;
            }

            $notification_id = intval($_POST['notification_id']);
            $user_id = get_current_user_id();

            $result = $this->mark_notification_read($notification_id, $user_id);

            if ($result) {
                $this->send_success([
                    'message' => 'Notification marked as read',
                    'notification_id' => $notification_id
                ]);
            } else {
                $this->send_error('Failed to mark notification as read');
            }

        } catch (\Exception $e) {
            error_log('HPH Dashboard Mark Read Exception: ' . $e->getMessage());
            $this->send_error('Failed to update notification');
        }
    }

    /**
     * Handle dashboard quick stats (MISSING METHOD)
     */
    public function handle_dashboard_quick_stats(): void {
        try {
            $stats = $this->get_overview_statistics();
            
            // Format stats for frontend
            $formatted_stats = [
                'total_listings' => $stats['total_listings'] ?? 0,
                'active_listings' => $stats['active_listings'] ?? 0,
                'total_views' => $stats['monthly_revenue'] ?? 0, // Or actual view count
                'active_integrations' => $this->count_active_integrations(),
                'health_status' => $this->get_system_health_status(),
                'leads_this_month' => $stats['leads_this_month'] ?? 0,
                'pending_transactions' => $stats['pending_transactions'] ?? 0
            ];
            
            $this->send_success([
                'stats' => $formatted_stats,
                'timestamp' => current_time('timestamp'),
                'cache_duration' => 300
            ]);
            
        } catch (\Exception $e) {
            error_log('HPH Dashboard Quick Stats Exception: ' . $e->getMessage());
            
            // Send fallback data instead of error
            $this->send_success([
                'stats' => [
                    'total_listings' => 0,
                    'active_listings' => 0,
                    'total_views' => 0,
                    'active_integrations' => 0,
                    'health_status' => 'unknown',
                    'leads_this_month' => 0,
                    'pending_transactions' => 0
                ],
                'fallback' => true,
                'message' => 'Using fallback data due to system issue'
            ]);
        }
    }

    /**
     * Handle dashboard recent activity (MISSING METHOD)
     */
    public function handle_dashboard_recent_activity(): void {
        try {
            $activity = $this->get_recent_activity();
            
            $this->send_success([
                'activity' => $activity,
                'timestamp' => current_time('timestamp')
            ]);
            
        } catch (\Exception $e) {
            error_log('HPH Dashboard Recent Activity Exception: ' . $e->getMessage());
            
            // Send empty activity instead of error
            $this->send_success([
                'activity' => [
                    'recent_listings' => [],
                    'recent_clients' => [],
                    'recent_transactions' => []
                ],
                'fallback' => true
            ]);
        }
    }

    /**
     * Handle marketing suite configuration (MISSING METHOD)
     */
    public function handle_marketing_suite_config(): void {
        try {
            $config = [
                'fabric_js_url' => 'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js',
                'canvas_config' => [
                    'width' => 1080,
                    'height' => 1080,
                    'background_color' => '#ffffff'
                ],
                'templates' => $this->get_marketing_templates(),
                'fonts' => $this->get_available_fonts(),
                'brand_colors' => $this->get_brand_colors(),
                'default_elements' => $this->get_default_marketing_elements(),
                'upload_limits' => [
                    'max_file_size' => wp_max_upload_size(),
                    'allowed_types' => ['jpg', 'jpeg', 'png', 'svg']
                ]
            ];
            
            $this->send_success([
                'config' => $config,
                'timestamp' => current_time('timestamp')
            ]);
            
        } catch (\Exception $e) {
            error_log('HPH Marketing Suite Config Exception: ' . $e->getMessage());
            $this->send_error('Failed to load marketing suite configuration');
        }
    }

    /**
     * Handle marketing suite templates (MISSING METHOD)
     */
    public function handle_marketing_suite_templates(): void {
        try {
            $category = sanitize_text_field($_POST['category'] ?? 'all');
            $templates = $this->get_marketing_templates($category);
            
            $this->send_success([
                'templates' => $templates,
                'category' => $category,
                'total' => count($templates)
            ]);
            
        } catch (\Exception $e) {
            error_log('HPH Marketing Suite Templates Exception: ' . $e->getMessage());
            $this->send_error('Failed to load marketing templates');
        }
    }

    /**
     * Handle marketing suite flyer generation (MISSING METHOD)
     */
    public function handle_marketing_suite_generate_flyer(): void {
        try {
            if (!$this->validate_required_params(['listing_id' => 'int'])) {
                return;
            }

            $listing_id = intval($_POST['listing_id']);
            $template_id = sanitize_text_field($_POST['template_id'] ?? 'default');
            $options = $_POST['options'] ?? [];

            // Generate flyer using the flyer generator class
            if (class_exists('\Happy_Place_Flyer_Generator')) {
                $generator = new \Happy_Place_Flyer_Generator();
                $result = $generator->generate_flyer($listing_id, $template_id, $options);
                
                $this->send_success([
                    'flyer_data' => $result,
                    'listing_id' => $listing_id,
                    'template_id' => $template_id
                ]);
            } elseif (function_exists('\hph_generate_flyer')) {
                // Fallback to function-based approach
                $result = \hph_generate_flyer($listing_id, $template_id, $options);
                $this->send_success([
                    'flyer_data' => $result,
                    'listing_id' => $listing_id,
                    'template_id' => $template_id
                ]);
            } else {
                // Return basic flyer data for frontend to handle
                $listing_data = get_post($listing_id);
                $this->send_success([
                    'flyer_data' => [
                        'listing' => [
                            'id' => $listing_id,
                            'title' => $listing_data->post_title,
                            'price' => get_field('price', $listing_id),
                            'address' => get_field('address', $listing_id),
                            'image' => get_the_post_thumbnail_url($listing_id, 'large')
                        ],
                        'template_id' => $template_id
                    ],
                    'listing_id' => $listing_id,
                    'template_id' => $template_id,
                    'frontend_generation' => true
                ]);
            }
            
        } catch (\Exception $e) {
            error_log('HPH Marketing Suite Generate Flyer Exception: ' . $e->getMessage());
            $this->send_error('Failed to generate flyer');
        }
    }

    /**
     * Handle marketing suite template save (MISSING METHOD)
     */
    public function handle_marketing_suite_save_template(): void {
        try {
            if (!$this->validate_required_params([
                'template_name' => 'string',
                'template_data' => 'string'
            ])) {
                return;
            }

            $template_name = sanitize_text_field($_POST['template_name']);
            $template_data = wp_unslash($_POST['template_data']);
            $category = sanitize_text_field($_POST['category'] ?? 'custom');
            $user_id = get_current_user_id();

            // Save template to database
            $template_id = wp_insert_post([
                'post_title' => $template_name,
                'post_content' => $template_data,
                'post_status' => 'private',
                'post_type' => 'marketing_template',
                'post_author' => $user_id,
                'meta_input' => [
                    'template_category' => $category,
                    'created_date' => current_time('mysql')
                ]
            ]);

            if ($template_id && !is_wp_error($template_id)) {
                $this->send_success([
                    'message' => 'Template saved successfully',
                    'template_id' => $template_id,
                    'template_name' => $template_name
                ]);
            } else {
                $this->send_error('Failed to save template');
            }
            
        } catch (\Exception $e) {
            error_log('HPH Marketing Suite Save Template Exception: ' . $e->getMessage());
            $this->send_error('Failed to save template');
        }
    }

    /**
     * Handle marketing suite get listings (MISSING METHOD)
     */
    public function handle_marketing_suite_get_listings(): void {
        try {
            $search = sanitize_text_field($_POST['search'] ?? '');
            $status = sanitize_text_field($_POST['status'] ?? 'publish');
            $limit = intval($_POST['limit'] ?? 20);

            $args = [
                'post_type' => 'listing',
                'post_status' => $status,
                'posts_per_page' => $limit,
                'orderby' => 'date',
                'order' => 'DESC'
            ];

            if (!empty($search)) {
                $args['s'] = $search;
            }

            $listings = get_posts($args);
            $formatted_listings = [];

            foreach ($listings as $listing) {
                $formatted_listings[] = [
                    'id' => $listing->ID,
                    'title' => $listing->post_title,
                    'price' => get_field('price', $listing->ID),
                    'address' => get_field('address', $listing->ID),
                    'featured_image' => get_the_post_thumbnail_url($listing->ID, 'medium'),
                    'bedrooms' => get_field('bedrooms', $listing->ID),
                    'bathrooms' => get_field('bathrooms', $listing->ID),
                    'sqft' => get_field('square_feet', $listing->ID)
                ];
            }
            
            $this->send_success([
                'listings' => $formatted_listings,
                'total' => count($formatted_listings),
                'search' => $search
            ]);
            
        } catch (\Exception $e) {
            error_log('HPH Marketing Suite Get Listings Exception: ' . $e->getMessage());
            $this->send_error('Failed to load listings');
        }
    }

    /**
     * Handle marketing suite asset upload (MISSING METHOD)
     */
    public function handle_marketing_suite_upload_assets(): void {
        try {
            if (empty($_FILES['file'])) {
                $this->send_error('No file uploaded');
                return;
            }

            $file = $_FILES['file'];
            $upload_overrides = [
                'test_form' => false,
                'unique_filename_callback' => function($dir, $name, $ext) {
                    return 'marketing_' . uniqid() . $ext;
                }
            ];

            $uploaded_file = wp_handle_upload($file, $upload_overrides);

            if ($uploaded_file && !isset($uploaded_file['error'])) {
                $this->send_success([
                    'file_url' => $uploaded_file['url'],
                    'file_path' => $uploaded_file['file'],
                    'file_type' => $uploaded_file['type']
                ]);
            } else {
                $this->send_error($uploaded_file['error'] ?? 'Upload failed');
            }
            
        } catch (\Exception $e) {
            error_log('HPH Marketing Suite Upload Exception: ' . $e->getMessage());
            $this->send_error('Failed to upload asset');
        }
    }

    /**
     * Handle get marketing suite data (comprehensive data endpoint)
     */
    public function handle_get_marketing_suite_data(): void {
        try {
            $data = [
                'config' => [
                    'fabric_js_url' => 'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js',
                    'canvas_config' => [
                        'width' => 1080,
                        'height' => 1080,
                        'background_color' => '#ffffff'
                    ],
                    'upload_limits' => [
                        'max_file_size' => wp_max_upload_size(),
                        'allowed_types' => ['jpg', 'jpeg', 'png', 'svg']
                    ]
                ],
                'templates' => $this->get_marketing_templates(),
                'fonts' => $this->get_available_fonts(),
                'brand_colors' => $this->get_brand_colors(),
                'default_elements' => $this->get_default_marketing_elements(),
                'listings' => $this->get_recent_listings(get_current_user_id(), 10),
                'flyer_generator' => [
                    'available' => class_exists('\Happy_Place_Flyer_Generator'),
                    'version' => '1.0.0',
                    'supported_formats' => ['png', 'jpg', 'pdf'],
                    'default_template' => 'default_listing'
                ]
            ];
            
            $this->send_success([
                'marketing_suite' => $data,
                'timestamp' => current_time('timestamp'),
                'user_id' => get_current_user_id()
            ]);
            
        } catch (\Exception $e) {
            error_log('HPH Marketing Suite Data Exception: ' . $e->getMessage());
            $this->send_error('Failed to load marketing suite data');
        }
    }

    /**
     * Handle getting listings overview data
     */
    public function handle_get_listings_overview(): void {
        try {
            $page = intval($_POST['page'] ?? 1);
            $per_page = intval($_POST['per_page'] ?? 20);
            $filters = $_POST['filters'] ?? [];
            
            $args = [
                'post_type' => 'listing',
                'post_status' => 'publish',
                'posts_per_page' => $per_page,
                'paged' => $page,
                'meta_query' => []
            ];
            
            // Apply filters
            if (!empty($filters['status'])) {
                $args['meta_query'][] = [
                    'key' => 'listing_status',
                    'value' => sanitize_text_field($filters['status']),
                    'compare' => '='
                ];
            }
            
            if (!empty($filters['price_range'])) {
                $price_range = explode('-', $filters['price_range']);
                if (count($price_range) === 2) {
                    $args['meta_query'][] = [
                        'key' => 'listing_price',
                        'value' => [intval($price_range[0]), intval($price_range[1])],
                        'type' => 'NUMERIC',
                        'compare' => 'BETWEEN'
                    ];
                }
            }
            
            $query = new \WP_Query($args);
            $listings = [];
            
            foreach ($query->posts as $post) {
                $listings[] = [
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'status' => get_post_meta($post->ID, 'listing_status', true),
                    'price' => get_post_meta($post->ID, 'listing_price', true),
                    'address' => get_post_meta($post->ID, 'listing_address', true),
                    'date_added' => $post->post_date,
                    'edit_url' => admin_url('post.php?post=' . $post->ID . '&action=edit')
                ];
            }
            
            $this->send_success([
                'listings' => $listings,
                'total' => $query->found_posts,
                'pages' => $query->max_num_pages,
                'current_page' => $page
            ]);
            
        } catch (\Exception $e) {
            error_log('HPH Listings Overview Exception: ' . $e->getMessage());
            $this->send_error('Failed to load listings overview');
        }
    }

    /**
     * Handle bulk listing operations
     */
    public function handle_bulk_update_listings(): void {
        try {
            $action = sanitize_text_field($_POST['bulk_action'] ?? '');
            $listing_ids = array_map('intval', $_POST['listing_ids'] ?? []);
            
            if (empty($action) || empty($listing_ids)) {
                $this->send_error('Missing required parameters');
                return;
            }
            
            $updated = 0;
            
            switch ($action) {
                case 'update_status':
                    $new_status = sanitize_text_field($_POST['new_status'] ?? '');
                    foreach ($listing_ids as $id) {
                        if (update_post_meta($id, 'listing_status', $new_status)) {
                            $updated++;
                        }
                    }
                    break;
                    
                case 'delete':
                    foreach ($listing_ids as $id) {
                        if (wp_delete_post($id, true)) {
                            $updated++;
                        }
                    }
                    break;
                    
                case 'update_agent':
                    $agent_id = intval($_POST['agent_id'] ?? 0);
                    foreach ($listing_ids as $id) {
                        if (update_post_meta($id, 'listing_agent', $agent_id)) {
                            $updated++;
                        }
                    }
                    break;
            }
            
            $this->send_success([
                'updated' => $updated,
                'total' => count($listing_ids),
                'action' => $action
            ]);
            
        } catch (\Exception $e) {
            error_log('HPH Bulk Update Exception: ' . $e->getMessage());
            $this->send_error('Failed to perform bulk update');
        }
    }

    /**
     * Handle getting integration status
     */
    public function handle_get_integration_status(): void {
        try {
            $integrations = [
                'airtable' => [
                    'name' => 'Airtable',
                    'status' => $this->test_airtable_connection(),
                    'last_sync' => get_option('hph_airtable_last_sync', 'Never'),
                    'config_url' => admin_url('admin.php?page=happy-place-airtable-settings')
                ],
                'mls' => [
                    'name' => 'MLS Integration',
                    'status' => $this->test_mls_connection(),
                    'last_sync' => get_option('hph_mls_last_sync', 'Never'),
                    'config_url' => admin_url('admin.php?page=happy-place-mls-settings')
                ],
                'google_maps' => [
                    'name' => 'Google Maps',
                    'status' => $this->test_google_maps_connection(),
                    'last_sync' => 'Real-time',
                    'config_url' => admin_url('admin.php?page=happy-place-settings')
                ],
                'email_marketing' => [
                    'name' => 'Email Marketing',
                    'status' => $this->test_email_marketing_connection(),
                    'last_sync' => get_option('hph_email_last_sync', 'Never'),
                    'config_url' => admin_url('admin.php?page=happy-place-email-settings')
                ]
            ];
            
            $this->send_success(['integrations' => $integrations]);
            
        } catch (\Exception $e) {
            error_log('HPH Integration Status Exception: ' . $e->getMessage());
            $this->send_error('Failed to load integration status');
        }
    }

    /**
     * Handle testing individual integration
     */
    public function handle_test_integration_connection(): void {
        try {
            $integration = sanitize_text_field($_POST['integration'] ?? '');
            
            $result = match($integration) {
                'airtable' => $this->test_airtable_connection(),
                'mls' => $this->test_mls_connection(),
                'google_maps' => $this->test_google_maps_connection(),
                'email_marketing' => $this->test_email_marketing_connection(),
                default => ['status' => 'error', 'message' => 'Unknown integration']
            };
            
            $this->send_success($result);
            
        } catch (\Exception $e) {
            error_log('HPH Test Integration Exception: ' . $e->getMessage());
            $this->send_error('Failed to test integration');
        }
    }

    /**
     * Handle getting system metrics
     */
    public function handle_get_system_metrics(): void {
        try {
            $metrics = [
                'wordpress' => [
                    'version' => get_bloginfo('version'),
                    'status' => version_compare(get_bloginfo('version'), '6.0', '>=') ? 'good' : 'warning'
                ],
                'php' => [
                    'version' => PHP_VERSION,
                    'status' => version_compare(PHP_VERSION, '8.0', '>=') ? 'good' : 'warning'
                ],
                'database' => [
                    'status' => $this->test_database_connection(),
                    'size' => $this->get_database_size()
                ],
                'plugin' => [
                    'version' => '1.0.0',
                    'status' => 'good',
                    'active_handlers' => $this->get_active_ajax_handlers()
                ],
                'performance' => [
                    'memory_usage' => memory_get_usage(true),
                    'memory_limit' => wp_convert_hr_to_bytes(ini_get('memory_limit')),
                    'cache_status' => $this->get_cache_status()
                ]
            ];
            
            $this->send_success(['metrics' => $metrics]);
            
        } catch (\Exception $e) {
            error_log('HPH System Metrics Exception: ' . $e->getMessage());
            $this->send_error('Failed to load system metrics');
        }
    }

    /**
     * Handle running maintenance tasks
     */
    public function handle_run_maintenance_task(): void {
        try {
            $task = sanitize_text_field($_POST['task'] ?? '');
            $result = [];
            
            switch ($task) {
                case 'clear_cache':
                    $result = $this->clear_plugin_cache();
                    break;
                    
                case 'optimize_database':
                    $result = $this->optimize_database();
                    break;
                    
                case 'clean_uploads':
                    $result = $this->clean_unused_uploads();
                    break;
                    
                case 'validate_data':
                    $result = $this->validate_listing_data();
                    break;
                    
                default:
                    $this->send_error('Unknown maintenance task');
                    return;
            }
            
            $this->send_success($result);
            
        } catch (\Exception $e) {
            error_log('HPH Maintenance Task Exception: ' . $e->getMessage());
            $this->send_error('Failed to run maintenance task');
        }
    }
    public function handle_initialize_marketing_suite(): void {
        try {
            // Return initialization data specifically for the frontend
            $init_data = [
                'status' => 'ready',
                'fabric_js_url' => 'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js',
                'canvas_ready' => true,
                'templates_loaded' => true,
                'user_permissions' => [
                    'can_create' => current_user_can('edit_posts'),
                    'can_upload' => current_user_can('upload_files'),
                    'can_save_templates' => current_user_can('edit_posts')
                ],
                'flyerGenerator' => [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('hph_ajax_nonce'),
                    'generateFlyer' => 'marketing_suite_generate_flyer',
                    'saveTemplate' => 'marketing_suite_save_template',
                    'loadTemplates' => 'marketing_suite_templates',
                    'uploadAsset' => 'marketing_suite_upload_assets',
                    'getListings' => 'marketing_suite_get_listings',
                    'config' => [
                        'fabric_js_url' => 'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js',
                        'canvas_config' => [
                            'width' => 1080,
                            'height' => 1080,
                            'background_color' => '#ffffff'
                        ]
                    ],
                    'endpoints' => [
                        'config' => 'marketing_suite_config',
                        'templates' => 'marketing_suite_templates',
                        'generate_flyer' => 'marketing_suite_generate_flyer',
                        'save_template' => 'marketing_suite_save_template',
                        'get_listings' => 'marketing_suite_get_listings',
                        'upload_assets' => 'marketing_suite_upload_assets'
                    ]
                ],
                'ajax_endpoints' => [
                    'url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('hph_ajax_nonce'),
                    'actions' => [
                        'config' => 'marketing_suite_config',
                        'templates' => 'marketing_suite_templates',
                        'generate' => 'marketing_suite_generate_flyer',
                        'save_template' => 'marketing_suite_save_template',
                        'get_listings' => 'marketing_suite_get_listings',
                        'upload' => 'marketing_suite_upload_assets'
                    ]
                ]
            ];
            
            $this->send_success($init_data);
            
        } catch (\Exception $e) {
            error_log('HPH Marketing Suite Init Exception: ' . $e->getMessage());
            $this->send_error('Failed to initialize marketing suite');
        }
    }

    /**
     * Private helper methods
     */

    private function get_overview_statistics(): array {
        $user_id = get_current_user_id();
        
        return [
            'total_listings' => $this->count_user_listings($user_id),
            'active_listings' => $this->count_active_listings($user_id),
            'total_clients' => $this->count_user_clients($user_id),
            'pending_transactions' => $this->count_pending_transactions($user_id),
            'monthly_revenue' => $this->get_monthly_revenue($user_id),
            'leads_this_month' => $this->count_monthly_leads($user_id)
        ];
    }

    private function get_recent_activity(): array {
        $user_id = get_current_user_id();
        
        return [
            'recent_listings' => $this->get_recent_listings($user_id, 5),
            'recent_clients' => $this->get_recent_clients($user_id, 5),
            'recent_transactions' => $this->get_recent_transactions($user_id, 5)
        ];
    }

    private function load_section_data(string $section, int $page, array $filters): array {
        switch ($section) {
            case 'listings':
                return $this->get_listings_data($page, $filters);
            case 'clients':
                return $this->get_clients_data($page, $filters);
            case 'transactions':
                return $this->get_transactions_data($page, $filters);
            case 'reports':
                return $this->get_reports_data($filters);
            default:
                return [];
        }
    }

    private function sanitize_dashboard_settings(array $settings): array {
        $sanitized = [];
        
        // Define allowed settings keys and their types
        $allowed_settings = [
            'layout' => 'string',
            'widgets' => 'array',
            'theme' => 'string',
            'notifications' => 'array',
            'refresh_interval' => 'int'
        ];

        foreach ($allowed_settings as $key => $type) {
            if (isset($settings[$key])) {
                switch ($type) {
                    case 'string':
                        $sanitized[$key] = sanitize_text_field($settings[$key]);
                        break;
                    case 'int':
                        $sanitized[$key] = intval($settings[$key]);
                        break;
                    case 'array':
                        $sanitized[$key] = is_array($settings[$key]) ? $settings[$key] : [];
                        break;
                }
            }
        }

        return $sanitized;
    }

    private function get_default_dashboard_settings(): array {
        return [
            'layout' => 'grid',
            'widgets' => ['overview', 'recent_listings', 'notifications'],
            'theme' => 'light',
            'notifications' => ['email' => true, 'browser' => true],
            'refresh_interval' => 300
        ];
    }

    private function count_user_listings(int $user_id): int {
        $listings = get_posts([
            'post_type' => 'listing',
            'author' => $user_id,
            'post_status' => 'any',
            'numberposts' => -1,
            'fields' => 'ids'
        ]);
        
        return count($listings);
    }

    private function count_active_listings(int $user_id): int {
        $listings = get_posts([
            'post_type' => 'listing',
            'author' => $user_id,
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids'
        ]);
        
        return count($listings);
    }

    private function count_user_clients(int $user_id): int {
        // This would integrate with your client management system
        return 0; // Placeholder
    }

    private function count_pending_transactions(int $user_id): int {
        // This would integrate with your transaction management system
        return 0; // Placeholder
    }

    private function get_monthly_revenue(int $user_id): float {
        // This would integrate with your transaction/commission system
        return 0.0; // Placeholder
    }

    private function count_monthly_leads(int $user_id): int {
        // This would integrate with your lead management system
        return 0; // Placeholder
    }

    private function get_listings_data(int $page, array $filters): array {
        $per_page = $this->dashboard_config['max_items_per_section'];
        $offset = ($page - 1) * $per_page;

        $args = [
            'post_type' => 'listing',
            'post_status' => 'any',
            'posts_per_page' => $per_page,
            'offset' => $offset
        ];

        // Apply filters
        if (!empty($filters['status'])) {
            $args['post_status'] = sanitize_text_field($filters['status']);
        }

        $listings = get_posts($args);
        $formatted_listings = [];

        foreach ($listings as $listing) {
            $formatted_listings[] = [
                'id' => $listing->ID,
                'title' => $listing->post_title,
                'status' => $listing->post_status,
                'date' => $listing->post_date,
                'price' => get_field('price', $listing->ID),
                'address' => get_field('address', $listing->ID)
            ];
        }

        return $formatted_listings;
    }

    private function get_clients_data(int $page, array $filters): array {
        // Placeholder for client data - integrate with your client management
        return [];
    }

    private function get_transactions_data(int $page, array $filters): array {
        // Placeholder for transaction data - integrate with your transaction management
        return [];
    }

    private function get_reports_data(array $filters): array {
        // Placeholder for reports data
        return [];
    }

    /**
     * Count active integrations
     */
    private function count_active_integrations(): int {
        $integrations = 0;
        
        // Check if configuration exists for various integrations
        $google_maps_key = get_option('hph_google_maps_api_key') ?: get_field('google_maps_api_key', 'option');
        $airtable_key = get_option('hph_airtable_api_key') ?: get_field('airtable_api_key', 'option');
        $walk_score_key = get_option('hph_walk_score_api_key') ?: get_field('walk_score_api_key', 'option');
        
        if (!empty($google_maps_key)) $integrations++;
        if (!empty($airtable_key)) $integrations++;
        if (!empty($walk_score_key)) $integrations++;
        
        return $integrations;
    }

    /**
     * Get system health status
     */
    private function get_system_health_status(): string {
        // Basic health checks
        $checks = [
            'php_version' => version_compare(PHP_VERSION, '7.4', '>='),
            'wp_version' => version_compare(get_bloginfo('version'), '5.0', '>='),
            'memory_limit' => $this->check_memory_limit(),
            'database' => $this->check_database_connection()
        ];
        
        $passed = array_filter($checks);
        $percentage = count($passed) / count($checks);
        
        if ($percentage >= 0.9) return 'healthy';
        if ($percentage >= 0.7) return 'warning';
        return 'critical';
    }

    /**
     * Check memory limit
     */
    private function check_memory_limit(): bool {
        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        $current_usage = memory_get_usage(true);
        
        return ($current_usage / $memory_limit) < 0.8; // Less than 80% usage
    }

    /**
     * Check database connection
     */
    private function check_database_connection(): bool {
        global $wpdb;
        
        $result = $wpdb->get_var("SELECT 1");
        return $result === '1';
    }

    /**
     * Enhanced refresh section data with error handling
     */
    private function refresh_section_data(string $section): array {
        try {
            switch ($section) {
                case 'overview':
                    return $this->get_overview_statistics();
                case 'listings':
                    return $this->get_listings_data(1, []);
                case 'activity':
                    return $this->get_recent_activity();
                default:
                    return ['refreshed' => true, 'timestamp' => current_time('timestamp')];
            }
        } catch (\Exception $e) {
            error_log("HPH Dashboard: Error refreshing section {$section}: " . $e->getMessage());
            return ['error' => true, 'message' => 'Section refresh failed'];
        }
    }

    /**
     * Get recent listings for a user
     */
    private function get_recent_listings(int $user_id, int $limit): array {
        $listings = get_posts([
            'post_type' => 'listing',
            'author' => $user_id,
            'post_status' => 'publish',
            'numberposts' => $limit,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);

        $formatted_listings = [];
        foreach ($listings as $listing) {
            $formatted_listings[] = [
                'id' => $listing->ID,
                'title' => $listing->post_title,
                'date' => $listing->post_date,
                'permalink' => get_permalink($listing->ID)
            ];
        }

        return $formatted_listings;
    }

    /**
     * Get recent clients for a user (placeholder)
     */
    private function get_recent_clients(int $user_id, int $limit): array {
        // Placeholder - integrate with client management system
        return [];
    }

    /**
     * Get recent transactions for a user (placeholder)
     */
    private function get_recent_transactions(int $user_id, int $limit): array {
        // Placeholder - integrate with transaction management system
        return [];
    }

    /**
     * Update dashboard layout (placeholder)
     */
    private function update_dashboard_layout(array $data): array {
        $user_id = get_current_user_id();
        $layout_data = [
            'layout' => sanitize_text_field($data['layout'] ?? 'grid'),
            'widgets' => $data['widgets'] ?? [],
            'updated' => current_time('timestamp')
        ];
        
        update_user_meta($user_id, 'hph_dashboard_layout', $layout_data);
        
        return ['success' => true, 'layout' => $layout_data];
    }

    /**
     * Get widget data (placeholder)
     */
    private function get_widget_data(string $widget_type, array $config): array {
        switch ($widget_type) {
            case 'stats':
                return $this->get_overview_statistics();
            case 'recent_listings':
                return $this->get_recent_listings(get_current_user_id(), 5);
            case 'activity':
                return $this->get_recent_activity();
            default:
                return ['widget_type' => $widget_type, 'data' => []];
        }
    }

    /**
     * Execute quick action (placeholder)
     */
    private function execute_quick_action(string $action, array $params): array {
        switch ($action) {
            case 'create_listing':
                return ['action' => 'create_listing', 'redirect' => admin_url('post-new.php?post_type=listing')];
            case 'sync_data':
                return ['action' => 'sync_data', 'status' => 'initiated'];
            default:
                return ['action' => $action, 'status' => 'unknown'];
        }
    }

    /**
     * Generate export data (placeholder)
     */
    private function generate_export_data(string $export_type, array $date_range, array $filters): array {
        return [
            'export_type' => $export_type,
            'date_range' => $date_range,
            'filters' => $filters,
            'data' => []
        ];
    }

    /**
     * Generate export filename
     */
    private function generate_export_filename(string $export_type): string {
        return 'hph_' . $export_type . '_export_' . date('Y-m-d_H-i-s') . '.csv';
    }

    /**
     * Get user notifications (placeholder)
     */
    private function get_user_notifications(int $user_id, int $limit, int $offset): array {
        // Placeholder - integrate with notification system
        return [];
    }

    /**
     * Get unread notification count (placeholder)
     */
    private function get_unread_count(int $user_id): int {
        // Placeholder - integrate with notification system
        return 0;
    }

    /**
     * Mark notification as read (placeholder)
     */
    private function mark_notification_read(int $notification_id, int $user_id): bool {
        // Placeholder - integrate with notification system
        return true;
    }

    /**
     * Get marketing templates
     */
    private function get_marketing_templates(string $category = 'all'): array {
        $args = [
            'post_type' => 'marketing_template',
            'post_status' => 'private',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ];

        if ($category !== 'all') {
            $args['meta_query'] = [
                [
                    'key' => 'template_category',
                    'value' => $category,
                    'compare' => '='
                ]
            ];
        }

        $templates = get_posts($args);
        $formatted_templates = [];

        foreach ($templates as $template) {
            $formatted_templates[] = [
                'id' => $template->ID,
                'name' => $template->post_title,
                'category' => get_post_meta($template->ID, 'template_category', true),
                'data' => $template->post_content,
                'preview_url' => wp_get_attachment_url(get_post_thumbnail_id($template->ID)),
                'created_date' => $template->post_date
            ];
        }

        // Add default templates if none exist
        if (empty($formatted_templates)) {
            $formatted_templates = $this->get_default_templates();
        }

        return $formatted_templates;
    }

    /**
     * Get available fonts for marketing suite
     */
    private function get_available_fonts(): array {
        return [
            'Arial' => ['family' => 'Arial', 'category' => 'sans-serif'],
            'Helvetica' => ['family' => 'Helvetica', 'category' => 'sans-serif'],
            'Times New Roman' => ['family' => 'Times New Roman', 'category' => 'serif'],
            'Georgia' => ['family' => 'Georgia', 'category' => 'serif'],
            'Roboto' => ['family' => 'Roboto', 'category' => 'sans-serif', 'google' => true],
            'Open Sans' => ['family' => 'Open Sans', 'category' => 'sans-serif', 'google' => true],
            'Lato' => ['family' => 'Lato', 'category' => 'sans-serif', 'google' => true],
            'Montserrat' => ['family' => 'Montserrat', 'category' => 'sans-serif', 'google' => true],
            'Playfair Display' => ['family' => 'Playfair Display', 'category' => 'serif', 'google' => true]
        ];
    }

    /**
     * Get brand colors for marketing suite
     */
    private function get_brand_colors(): array {
        // Get brand colors from theme options or provide defaults
        $custom_colors = get_field('brand_colors', 'option') ?: [];
        
        $default_colors = [
            'primary' => '#2c3e50',
            'secondary' => '#3498db',
            'accent' => '#e74c3c',
            'neutral' => '#95a5a6',
            'light' => '#ecf0f1',
            'dark' => '#2c3e50'
        ];

        return array_merge($default_colors, $custom_colors);
    }

    /**
     * Get default marketing elements
     */
    private function get_default_marketing_elements(): array {
        return [
            'text_elements' => [
                'heading' => ['fontSize' => 36, 'fontWeight' => 'bold', 'color' => '#2c3e50'],
                'subheading' => ['fontSize' => 24, 'fontWeight' => 'normal', 'color' => '#34495e'],
                'body' => ['fontSize' => 16, 'fontWeight' => 'normal', 'color' => '#2c3e50'],
                'caption' => ['fontSize' => 12, 'fontWeight' => 'normal', 'color' => '#7f8c8d']
            ],
            'shapes' => [
                'rectangle' => ['fill' => '#3498db', 'stroke' => 'transparent'],
                'circle' => ['fill' => '#e74c3c', 'stroke' => 'transparent'],
                'line' => ['stroke' => '#2c3e50', 'strokeWidth' => 2]
            ],
            'backgrounds' => [
                'solid' => ['type' => 'solid', 'color' => '#ffffff'],
                'gradient' => ['type' => 'gradient', 'colors' => ['#3498db', '#2980b9']]
            ]
        ];
    }

    /**
     * Get default templates when none exist
     */
    private function get_default_templates(): array {
        return [
            [
                'id' => 'default_listing',
                'name' => 'Default Listing Flyer',
                'category' => 'listing',
                'data' => json_encode([
                    'version' => '1.0',
                    'width' => 1080,
                    'height' => 1080,
                    'objects' => []
                ]),
                'preview_url' => '',
                'created_date' => current_time('mysql')
            ],
            [
                'id' => 'default_social',
                'name' => 'Social Media Post',
                'category' => 'social',
                'data' => json_encode([
                    'version' => '1.0',
                    'width' => 1080,
                    'height' => 1080,
                    'objects' => []
                ]),
                'preview_url' => '',
                'created_date' => current_time('mysql')
            ]
        ];
    }

    // Helper methods for admin menu AJAX actions

    /**
     * Test Airtable connection
     */
    private function test_airtable_connection(): array {
        $api_key = get_option('hph_airtable_api_key');
        $base_id = get_option('hph_airtable_base_id');
        
        if (empty($api_key) || empty($base_id)) {
            return ['status' => 'warning', 'message' => 'Not configured'];
        }
        
        // Test connection
        $response = wp_remote_get("https://api.airtable.com/v0/{$base_id}", [
            'headers' => ['Authorization' => "Bearer {$api_key}"],
            'timeout' => 10
        ]);
        
        if (is_wp_error($response)) {
            return ['status' => 'error', 'message' => 'Connection failed'];
        }
        
        $code = wp_remote_retrieve_response_code($response);
        return $code === 200 ? 
            ['status' => 'good', 'message' => 'Connected'] : 
            ['status' => 'error', 'message' => 'Authentication failed'];
    }

    /**
     * Test MLS connection
     */
    private function test_mls_connection(): array {
        // Placeholder for MLS integration test
        return ['status' => 'warning', 'message' => 'Not configured'];
    }

    /**
     * Test Google Maps connection
     */
    private function test_google_maps_connection(): array {
        $api_key = get_option('hph_google_maps_api_key');
        
        if (empty($api_key)) {
            return ['status' => 'warning', 'message' => 'API key not set'];
        }
        
        return ['status' => 'good', 'message' => 'API key configured'];
    }

    /**
     * Test email marketing connection
     */
    private function test_email_marketing_connection(): array {
        // Placeholder for email marketing integration test
        return ['status' => 'warning', 'message' => 'Not configured'];
    }

    /**
     * Test database connection
     */
    private function test_database_connection(): string {
        global $wpdb;
        
        $result = $wpdb->get_var("SELECT 1");
        return $result === '1' ? 'good' : 'error';
    }

    /**
     * Get database size
     */
    private function get_database_size(): string {
        global $wpdb;
        
        $size = $wpdb->get_var("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'DB Size in MB' 
            FROM information_schema.tables 
            WHERE table_schema='{$wpdb->dbname}'
        ");
        
        return $size ? $size . ' MB' : 'Unknown';
    }

    /**
     * Get active AJAX handlers count
     */
    private function get_active_ajax_handlers(): int {
        // Return count of registered actions
        return count($this->get_actions());
    }

    /**
     * Get cache status
     */
    private function get_cache_status(): string {
        if (wp_using_ext_object_cache()) {
            return 'External cache active';
        }
        return 'WordPress default cache';
    }

    /**
     * Clear plugin cache
     */
    private function clear_plugin_cache(): array {
        // Clear WordPress transients
        $cleared = 0;
        
        $transients = [
            'hph_dashboard_stats',
            'hph_recent_activity', 
            'hph_integration_status',
            'hph_system_metrics'
        ];
        
        foreach ($transients as $transient) {
            if (delete_transient($transient)) {
                $cleared++;
            }
        }
        
        // Clear any object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        return [
            'status' => 'success',
            'message' => "Cleared {$cleared} cache entries",
            'cleared_count' => $cleared
        ];
    }

    /**
     * Optimize database tables
     */
    private function optimize_database(): array {
        global $wpdb;
        
        $tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}%'");
        $optimized = 0;
        
        foreach ($tables as $table) {
            $result = $wpdb->query("OPTIMIZE TABLE `{$table}`");
            if ($result !== false) {
                $optimized++;
            }
        }
        
        return [
            'status' => 'success',
            'message' => "Optimized {$optimized} database tables",
            'optimized_count' => $optimized
        ];
    }

    /**
     * Clean unused upload files
     */
    private function clean_unused_uploads(): array {
        // This is a placeholder - implementing file cleanup requires careful consideration
        return [
            'status' => 'info',
            'message' => 'Upload cleanup feature coming soon',
            'cleaned_count' => 0
        ];
    }

    /**
     * Validate listing data integrity
     */
    private function validate_listing_data(): array {
        $listings = get_posts([
            'post_type' => 'listing',
            'post_status' => 'any',
            'numberposts' => -1,
            'fields' => 'ids'
        ]);
        
        $issues = [];
        $checked = 0;
        
        foreach ($listings as $listing_id) {
            $checked++;
            
            // Check required fields
            $price = get_post_meta($listing_id, 'listing_price', true);
            $address = get_post_meta($listing_id, 'listing_address', true);
            
            if (empty($price)) {
                $issues[] = [
                    'id' => $listing_id,
                    'issue' => 'Missing price',
                    'severity' => 'warning'
                ];
            }
            
            if (empty($address)) {
                $issues[] = [
                    'id' => $listing_id,
                    'issue' => 'Missing address',
                    'severity' => 'error'
                ];
            }
        }
        
        return [
            'status' => 'success',
            'message' => "Validated {$checked} listings, found " . count($issues) . " issues",
            'checked_count' => $checked,
            'issues_count' => count($issues),
            'issues' => array_slice($issues, 0, 10) // Return first 10 issues
        ];
    }
}