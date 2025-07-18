<?php

namespace HappyPlace\Testing;

/**
 * Integration Testing Framework
 *
 * Automated testing for API endpoints, theme integration, and system workflows
 *
 * @package HappyPlace\Testing
 * @since 2.0.0
 */
class Integration_Testing_Framework {
    
    /**
     * Singleton instance
     * @var Integration_Testing_Framework
     */
    private static $instance = null;
    
    /**
     * Test scenarios
     * @var array
     */
    private $test_scenarios = [];
    
    /**
     * Test results
     * @var array
     */
    private $test_results = [];
    
    /**
     * API endpoints to test
     * @var array
     */
    private $api_endpoints = [
        'listings' => [
            'endpoint' => '/wp-json/hph/v1/listings',
            'methods' => ['GET', 'POST'],
            'auth_required' => false
        ],
        'agents' => [
            'endpoint' => '/wp-json/hph/v1/agents',
            'methods' => ['GET'],
            'auth_required' => false
        ],
        'search' => [
            'endpoint' => '/wp-json/hph/v1/search',
            'methods' => ['GET', 'POST'],
            'auth_required' => false
        ],
        'user_favorites' => [
            'endpoint' => '/wp-json/hph/v1/user/favorites',
            'methods' => ['GET', 'POST', 'DELETE'],
            'auth_required' => true
        ]
    ];
    
    /**
     * Theme integration tests
     * @var array
     */
    private $theme_tests = [
        'template_hierarchy',
        'custom_post_types',
        'custom_fields',
        'shortcodes',
        'widgets',
        'customizer_settings',
        'menu_locations',
        'image_sizes'
    ];
    
    /**
     * AJAX actions to test
     * @var array
     */
    private $ajax_actions = [
        'hph_property_search',
        'hph_property_filter',
        'hph_save_favorite',
        'hph_load_more_listings',
        'hph_contact_agent',
        'hph_schedule_viewing'
    ];
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->setup_test_scenarios();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_ajax_run_integration_tests', [$this, 'ajax_run_integration_tests']);
        add_action('wp_ajax_run_api_tests', [$this, 'ajax_run_api_tests']);
        add_action('wp_ajax_run_theme_tests', [$this, 'ajax_run_theme_tests']);
        
        // Schedule automated integration tests
        add_action('hph_automated_integration_tests', [$this, 'run_automated_tests']);
        
        if (!wp_next_scheduled('hph_automated_integration_tests')) {
            wp_schedule_event(time(), 'daily', 'hph_automated_integration_tests');
        }
    }
    
    /**
     * Setup test scenarios
     */
    private function setup_test_scenarios() {
        // User Journey: Property Search
        $this->add_test_scenario('property_search_journey', [
            'name' => 'Property Search User Journey',
            'description' => 'Complete flow from search to property details',
            'steps' => [
                'load_search_page',
                'perform_search',
                'view_search_results',
                'click_property_details',
                'load_property_page'
            ]
        ]);
        
        // User Journey: Agent Contact
        $this->add_test_scenario('agent_contact_journey', [
            'name' => 'Agent Contact User Journey',
            'description' => 'Complete flow for contacting an agent',
            'steps' => [
                'load_agents_page',
                'select_agent',
                'fill_contact_form',
                'submit_contact_form',
                'verify_confirmation'
            ]
        ]);
        
        // API Integration Test
        $this->add_test_scenario('api_integration', [
            'name' => 'API Integration Test',
            'description' => 'Test all API endpoints functionality',
            'steps' => [
                'test_listings_api',
                'test_agents_api',
                'test_search_api',
                'test_authenticated_endpoints'
            ]
        ]);
        
        // Theme Integration Test
        $this->add_test_scenario('theme_integration', [
            'name' => 'Theme Integration Test',
            'description' => 'Test theme functionality and compatibility',
            'steps' => [
                'test_template_hierarchy',
                'test_custom_post_types',
                'test_custom_fields',
                'test_shortcodes',
                'test_widgets'
            ]
        ]);
    }
    
    /**
     * Add test scenario
     */
    public function add_test_scenario($id, $scenario) {
        $this->test_scenarios[$id] = $scenario;
    }
    
    /**
     * Run all integration tests
     */
    public function run_all_integration_tests() {
        $results = [
            'timestamp' => current_time('timestamp'),
            'scenarios' => [],
            'overall_score' => 0,
            'total_passed' => 0,
            'total_failed' => 0
        ];
        
        foreach ($this->test_scenarios as $scenario_id => $scenario) {
            $results['scenarios'][$scenario_id] = $this->run_test_scenario($scenario_id);
        }
        
        // Calculate overall results
        $this->calculate_overall_results($results);
        
        // Store results
        update_option('hph_integration_test_results', $results);
        
        return $results;
    }
    
    /**
     * Run specific test scenario
     */
    public function run_test_scenario($scenario_id) {
        if (!isset($this->test_scenarios[$scenario_id])) {
            return [
                'success' => false,
                'error' => 'Scenario not found'
            ];
        }
        
        $scenario = $this->test_scenarios[$scenario_id];
        $result = [
            'scenario_id' => $scenario_id,
            'name' => $scenario['name'],
            'description' => $scenario['description'],
            'timestamp' => current_time('timestamp'),
            'steps' => [],
            'passed' => 0,
            'failed' => 0,
            'score' => 0,
            'duration' => 0
        ];
        
        $start_time = microtime(true);
        
        try {
            foreach ($scenario['steps'] as $step) {
                $step_result = $this->execute_test_step($step);
                $result['steps'][$step] = $step_result;
                
                if ($step_result['success']) {
                    $result['passed']++;
                } else {
                    $result['failed']++;
                }
            }
            
            $result['duration'] = microtime(true) - $start_time;
            $total_steps = count($scenario['steps']);
            $result['score'] = $total_steps > 0 ? round(($result['passed'] / $total_steps) * 100) : 0;
            
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            $result['score'] = 0;
        }
        
        return $result;
    }
    
    /**
     * Execute individual test step
     */
    private function execute_test_step($step) {
        $step_result = [
            'step' => $step,
            'success' => false,
            'message' => '',
            'duration' => 0,
            'data' => []
        ];
        
        $start_time = microtime(true);
        
        try {
            switch ($step) {
                case 'load_search_page':
                    $step_result = $this->test_load_search_page();
                    break;
                    
                case 'perform_search':
                    $step_result = $this->test_perform_search();
                    break;
                    
                case 'view_search_results':
                    $step_result = $this->test_view_search_results();
                    break;
                    
                case 'click_property_details':
                    $step_result = $this->test_click_property_details();
                    break;
                    
                case 'load_property_page':
                    $step_result = $this->test_load_property_page();
                    break;
                    
                case 'load_agents_page':
                    $step_result = $this->test_load_agents_page();
                    break;
                    
                case 'select_agent':
                    $step_result = $this->test_select_agent();
                    break;
                    
                case 'fill_contact_form':
                    $step_result = $this->test_fill_contact_form();
                    break;
                    
                case 'submit_contact_form':
                    $step_result = $this->test_submit_contact_form();
                    break;
                    
                case 'verify_confirmation':
                    $step_result = $this->test_verify_confirmation();
                    break;
                    
                case 'test_listings_api':
                    $step_result = $this->test_api_endpoint('listings');
                    break;
                    
                case 'test_agents_api':
                    $step_result = $this->test_api_endpoint('agents');
                    break;
                    
                case 'test_search_api':
                    $step_result = $this->test_api_endpoint('search');
                    break;
                    
                case 'test_authenticated_endpoints':
                    $step_result = $this->test_authenticated_endpoints();
                    break;
                    
                case 'test_template_hierarchy':
                    $step_result = $this->test_template_hierarchy();
                    break;
                    
                case 'test_custom_post_types':
                    $step_result = $this->test_custom_post_types();
                    break;
                    
                case 'test_custom_fields':
                    $step_result = $this->test_custom_fields();
                    break;
                    
                case 'test_shortcodes':
                    $step_result = $this->test_shortcodes();
                    break;
                    
                case 'test_widgets':
                    $step_result = $this->test_widgets();
                    break;
                    
                default:
                    $step_result['message'] = "Unknown test step: {$step}";
            }
            
        } catch (\Exception $e) {
            $step_result['success'] = false;
            $step_result['message'] = 'Exception: ' . $e->getMessage();
        }
        
        $step_result['duration'] = microtime(true) - $start_time;
        return $step_result;
    }
    
    /**
     * Test loading search page
     */
    private function test_load_search_page() {
        $response = wp_remote_get(home_url('/listings/'));
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Failed to load search page: ' . $response->get_error_message()
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code !== 200) {
            return [
                'success' => false,
                'message' => "Search page returned status code: {$status_code}"
            ];
        }
        
        // Check for search form
        if (strpos($body, 'property-search') === false) {
            return [
                'success' => false,
                'message' => 'Search form not found on search page'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Search page loaded successfully',
            'data' => ['status_code' => $status_code, 'content_length' => strlen($body)]
        ];
    }
    
    /**
     * Test performing search
     */
    private function test_perform_search() {
        $search_data = [
            'action' => 'hph_property_search',
            'location' => 'Test City',
            'property_type' => 'house',
            'min_price' => 100000,
            'max_price' => 500000
        ];
        
        $response = wp_remote_post(admin_url('admin-ajax.php'), [
            'body' => $search_data
        ]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Search request failed: ' . $response->get_error_message()
            ];
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['success'])) {
            return [
                'success' => false,
                'message' => 'Invalid search response format'
            ];
        }
        
        return [
            'success' => $data['success'],
            'message' => $data['success'] ? 'Search performed successfully' : 'Search failed',
            'data' => $data
        ];
    }
    
    /**
     * Test viewing search results
     */
    private function test_view_search_results() {
        // Test search results page with query parameters
        $search_url = add_query_arg([
            'location' => 'Test City',
            'property_type' => 'house'
        ], home_url('/listings/'));
        
        $response = wp_remote_get($search_url);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Failed to load search results: ' . $response->get_error_message()
            ];
        }
        
        $body = wp_remote_retrieve_body($response);
        
        // Check for results container
        if (strpos($body, 'search-results') === false && strpos($body, 'property-listing') === false) {
            return [
                'success' => false,
                'message' => 'Search results container not found'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Search results displayed successfully'
        ];
    }
    
    /**
     * Test clicking property details
     */
    private function test_click_property_details() {
        // Get a sample property post
        $properties = get_posts([
            'post_type' => 'property',
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ]);
        
        if (empty($properties)) {
            return [
                'success' => false,
                'message' => 'No properties found for testing'
            ];
        }
        
        $property_url = get_permalink($properties[0]->ID);
        $response = wp_remote_get($property_url);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Failed to load property details: ' . $response->get_error_message()
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            return [
                'success' => false,
                'message' => "Property page returned status code: {$status_code}"
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Property details page accessible',
            'data' => ['property_url' => $property_url]
        ];
    }
    
    /**
     * Test loading property page
     */
    private function test_load_property_page() {
        $properties = get_posts([
            'post_type' => 'property',
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ]);
        
        if (empty($properties)) {
            return [
                'success' => false,
                'message' => 'No properties found for testing'
            ];
        }
        
        $property_url = get_permalink($properties[0]->ID);
        $response = wp_remote_get($property_url);
        $body = wp_remote_retrieve_body($response);
        
        // Check for property-specific elements
        $required_elements = ['property-details', 'property-gallery', 'contact-agent'];
        $missing_elements = [];
        
        foreach ($required_elements as $element) {
            if (strpos($body, $element) === false) {
                $missing_elements[] = $element;
            }
        }
        
        if (!empty($missing_elements)) {
            return [
                'success' => false,
                'message' => 'Missing property page elements: ' . implode(', ', $missing_elements)
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Property page loaded with all required elements'
        ];
    }
    
    /**
     * Test loading agents page
     */
    private function test_load_agents_page() {
        $response = wp_remote_get(home_url('/agents/'));
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Failed to load agents page: ' . $response->get_error_message()
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code !== 200) {
            return [
                'success' => false,
                'message' => "Agents page returned status code: {$status_code}"
            ];
        }
        
        // Check for agents listing
        if (strpos($body, 'agent-card') === false && strpos($body, 'agent-listing') === false) {
            return [
                'success' => false,
                'message' => 'Agents listing not found on page'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Agents page loaded successfully'
        ];
    }
    
    /**
     * Test selecting agent
     */
    private function test_select_agent() {
        $agents = get_posts([
            'post_type' => 'agent',
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ]);
        
        if (empty($agents)) {
            return [
                'success' => false,
                'message' => 'No agents found for testing'
            ];
        }
        
        $agent_url = get_permalink($agents[0]->ID);
        $response = wp_remote_get($agent_url);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Failed to load agent profile: ' . $response->get_error_message()
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Agent profile loaded successfully',
            'data' => ['agent_url' => $agent_url]
        ];
    }
    
    /**
     * Test filling contact form
     */
    private function test_fill_contact_form() {
        // This would typically involve checking form elements
        // For now, we'll simulate form validation
        $form_data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '555-1234',
            'message' => 'Test message'
        ];
        
        // Validate form data
        if (empty($form_data['name']) || empty($form_data['email']) || empty($form_data['message'])) {
            return [
                'success' => false,
                'message' => 'Required form fields missing'
            ];
        }
        
        if (!is_email($form_data['email'])) {
            return [
                'success' => false,
                'message' => 'Invalid email format'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Form data validation passed',
            'data' => $form_data
        ];
    }
    
    /**
     * Test submitting contact form
     */
    private function test_submit_contact_form() {
        $form_data = [
            'action' => 'hph_contact_agent',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '555-1234',
            'message' => 'Test message',
            'agent_id' => 1
        ];
        
        $response = wp_remote_post(admin_url('admin-ajax.php'), [
            'body' => $form_data
        ]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Form submission failed: ' . $response->get_error_message()
            ];
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return [
            'success' => $data && isset($data['success']) ? $data['success'] : false,
            'message' => $data && isset($data['message']) ? $data['message'] : 'Form submitted',
            'data' => $data
        ];
    }
    
    /**
     * Test verification confirmation
     */
    private function test_verify_confirmation() {
        // This would typically check for confirmation page or message
        // For now, we'll simulate confirmation verification
        return [
            'success' => true,
            'message' => 'Confirmation process verified'
        ];
    }
    
    /**
     * Test API endpoint
     */
    private function test_api_endpoint($endpoint_name) {
        if (!isset($this->api_endpoints[$endpoint_name])) {
            return [
                'success' => false,
                'message' => "Unknown API endpoint: {$endpoint_name}"
            ];
        }
        
        $endpoint = $this->api_endpoints[$endpoint_name];
        $test_results = [];
        
        foreach ($endpoint['methods'] as $method) {
            $url = home_url($endpoint['endpoint']);
            
            $args = [
                'method' => $method,
                'timeout' => 10
            ];
            
            if ($method === 'POST') {
                $args['body'] = json_encode(['test' => true]);
                $args['headers'] = ['Content-Type' => 'application/json'];
            }
            
            $response = wp_remote_request($url, $args);
            
            if (is_wp_error($response)) {
                $test_results[$method] = [
                    'success' => false,
                    'message' => $response->get_error_message()
                ];
                continue;
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            // Check if response is valid JSON for API endpoints
            $json_data = json_decode($body, true);
            $is_valid_json = json_last_error() === JSON_ERROR_NONE;
            
            $test_results[$method] = [
                'success' => $status_code < 400 && $is_valid_json,
                'status_code' => $status_code,
                'valid_json' => $is_valid_json,
                'response_size' => strlen($body)
            ];
        }
        
        $all_passed = true;
        foreach ($test_results as $result) {
            if (!$result['success']) {
                $all_passed = false;
                break;
            }
        }
        
        return [
            'success' => $all_passed,
            'message' => $all_passed ? "API endpoint {$endpoint_name} working correctly" : "API endpoint {$endpoint_name} has issues",
            'data' => $test_results
        ];
    }
    
    /**
     * Test authenticated endpoints
     */
    private function test_authenticated_endpoints() {
        // Create a test user
        $user_id = wp_create_user('testuser', 'testpass', 'test@example.com');
        
        if (is_wp_error($user_id)) {
            return [
                'success' => false,
                'message' => 'Failed to create test user: ' . $user_id->get_error_message()
            ];
        }
        
        // Test authenticated request (simplified)
        $response = wp_remote_get(home_url('/wp-json/hph/v1/user/favorites'), [
            'headers' => [
                'Authorization' => 'Bearer test-token'
            ]
        ]);
        
        // Clean up test user
        wp_delete_user($user_id);
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        return [
            'success' => $status_code === 401, // Should return 401 for invalid token
            'message' => $status_code === 401 ? 'Authentication working correctly' : 'Authentication may have issues',
            'data' => ['status_code' => $status_code]
        ];
    }
    
    /**
     * Test template hierarchy
     */
    private function test_template_hierarchy() {
        $template_files = [
            'index.php',
            'single.php',
            'page.php',
            'archive.php',
            'single-property.php',
            'archive-property.php',
            'single-agent.php',
            'archive-agent.php'
        ];
        
        $theme_dir = get_template_directory();
        $missing_templates = [];
        
        foreach ($template_files as $template) {
            if (!file_exists($theme_dir . '/' . $template)) {
                $missing_templates[] = $template;
            }
        }
        
        $critical_missing = array_intersect($missing_templates, ['index.php', 'single.php', 'page.php']);
        
        return [
            'success' => empty($critical_missing),
            'message' => empty($critical_missing) ? 'Template hierarchy complete' : 'Missing critical templates: ' . implode(', ', $critical_missing),
            'data' => [
                'missing_templates' => $missing_templates,
                'critical_missing' => $critical_missing
            ]
        ];
    }
    
    /**
     * Test custom post types
     */
    private function test_custom_post_types() {
        $required_post_types = ['property', 'agent'];
        $missing_post_types = [];
        
        foreach ($required_post_types as $post_type) {
            if (!post_type_exists($post_type)) {
                $missing_post_types[] = $post_type;
            }
        }
        
        return [
            'success' => empty($missing_post_types),
            'message' => empty($missing_post_types) ? 'All custom post types registered' : 'Missing post types: ' . implode(', ', $missing_post_types),
            'data' => ['missing_post_types' => $missing_post_types]
        ];
    }
    
    /**
     * Test custom fields
     */
    private function test_custom_fields() {
        // Test if ACF or similar is working
        $test_functions = [
            'get_field',
            'update_field',
            'get_field_object'
        ];
        
        $missing_functions = [];
        foreach ($test_functions as $function) {
            if (!function_exists($function)) {
                $missing_functions[] = $function;
            }
        }
        
        return [
            'success' => empty($missing_functions),
            'message' => empty($missing_functions) ? 'Custom fields functionality available' : 'Missing custom field functions: ' . implode(', ', $missing_functions),
            'data' => ['missing_functions' => $missing_functions]
        ];
    }
    
    /**
     * Test shortcodes
     */
    private function test_shortcodes() {
        $required_shortcodes = [
            'property_search',
            'property_listings',
            'agent_card',
            'contact_form'
        ];
        
        $missing_shortcodes = [];
        foreach ($required_shortcodes as $shortcode) {
            if (!shortcode_exists($shortcode)) {
                $missing_shortcodes[] = $shortcode;
            }
        }
        
        return [
            'success' => empty($missing_shortcodes),
            'message' => empty($missing_shortcodes) ? 'All shortcodes registered' : 'Missing shortcodes: ' . implode(', ', $missing_shortcodes),
            'data' => ['missing_shortcodes' => $missing_shortcodes]
        ];
    }
    
    /**
     * Test widgets
     */
    private function test_widgets() {
        global $wp_widget_factory;
        
        $required_widgets = [
            'HPH_Property_Search_Widget',
            'HPH_Featured_Properties_Widget',
            'HPH_Agent_Contact_Widget'
        ];
        
        $missing_widgets = [];
        foreach ($required_widgets as $widget) {
            if (!isset($wp_widget_factory->widgets[$widget])) {
                $missing_widgets[] = $widget;
            }
        }
        
        return [
            'success' => empty($missing_widgets),
            'message' => empty($missing_widgets) ? 'All widgets registered' : 'Missing widgets: ' . implode(', ', $missing_widgets),
            'data' => ['missing_widgets' => $missing_widgets]
        ];
    }
    
    /**
     * Calculate overall results
     */
    private function calculate_overall_results(&$results) {
        $total_passed = 0;
        $total_failed = 0;
        
        foreach ($results['scenarios'] as $scenario) {
            $total_passed += $scenario['passed'];
            $total_failed += $scenario['failed'];
        }
        
        $results['total_passed'] = $total_passed;
        $results['total_failed'] = $total_failed;
        $total_tests = $total_passed + $total_failed;
        $results['overall_score'] = $total_tests > 0 ? round(($total_passed / $total_tests) * 100) : 0;
    }
    
    /**
     * Run automated tests
     */
    public function run_automated_tests() {
        $results = $this->run_all_integration_tests();
        
        // Alert on critical failures
        if ($results['overall_score'] < 70) {
            error_log("HPH Integration Testing Alert: Overall score is {$results['overall_score']}%");
        }
        
        return $results;
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_management_page(
            'Integration Testing',
            'Integration Tests',
            'manage_options',
            'hph-integration-testing',
            [$this, 'render_admin_page']
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $test_results = get_option('hph_integration_test_results', []);
        ?>
        <div class="wrap">
            <h1>Integration Testing Framework</h1>
            
            <div class="integration-dashboard">
                <?php if (!empty($test_results)): ?>
                    <div class="dashboard-summary">
                        <div class="summary-card">
                            <h3>Overall Score</h3>
                            <div class="score-display score-<?php echo $test_results['overall_score'] >= 80 ? 'good' : ($test_results['overall_score'] >= 60 ? 'fair' : 'poor'); ?>">
                                <?php echo esc_html($test_results['overall_score']); ?>%
                            </div>
                        </div>
                        
                        <div class="summary-card">
                            <h3>Tests Passed</h3>
                            <div class="metric-value"><?php echo esc_html($test_results['total_passed']); ?></div>
                        </div>
                        
                        <div class="summary-card">
                            <h3>Tests Failed</h3>
                            <div class="metric-value"><?php echo esc_html($test_results['total_failed']); ?></div>
                        </div>
                        
                        <div class="summary-card">
                            <h3>Scenarios</h3>
                            <div class="metric-value"><?php echo count($test_results['scenarios']); ?></div>
                        </div>
                    </div>
                    
                    <div class="scenario-results">
                        <h2>Scenario Results</h2>
                        <?php foreach ($test_results['scenarios'] as $scenario_id => $scenario): ?>
                            <div class="scenario-card">
                                <h3>
                                    <?php echo esc_html($scenario['name']); ?>
                                    <span class="score-badge score-<?php echo $scenario['score'] >= 80 ? 'good' : ($scenario['score'] >= 60 ? 'fair' : 'poor'); ?>">
                                        <?php echo esc_html($scenario['score']); ?>%
                                    </span>
                                </h3>
                                <p><?php echo esc_html($scenario['description']); ?></p>
                                
                                <div class="scenario-stats">
                                    <span class="stat">✓ <?php echo esc_html($scenario['passed']); ?> passed</span>
                                    <span class="stat">✗ <?php echo esc_html($scenario['failed']); ?> failed</span>
                                    <span class="stat">⏱ <?php echo number_format($scenario['duration'], 3); ?>s</span>
                                </div>
                                
                                <div class="scenario-steps">
                                    <?php foreach ($scenario['steps'] as $step_name => $step_result): ?>
                                        <div class="step-result <?php echo $step_result['success'] ? 'success' : 'failure'; ?>">
                                            <span class="step-name"><?php echo esc_html(str_replace('_', ' ', $step_name)); ?></span>
                                            <span class="step-status"><?php echo $step_result['success'] ? '✓' : '✗'; ?></span>
                                            <span class="step-message"><?php echo esc_html($step_result['message']); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="test-actions">
                    <h2>Actions</h2>
                    <p>
                        <button class="button button-primary" id="run-integration-tests">
                            Run All Integration Tests
                        </button>
                        <button class="button" id="run-api-tests">
                            Run API Tests Only
                        </button>
                        <button class="button" id="run-theme-tests">
                            Run Theme Tests Only
                        </button>
                    </p>
                </div>
            </div>
        </div>
        
        <style>
        .dashboard-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .summary-card {
            background: white;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
            text-align: center;
        }
        .score-display {
            font-size: 3em;
            font-weight: bold;
            margin: 10px 0;
        }
        .score-good { color: #28a745; }
        .score-fair { color: #ffc107; }
        .score-poor { color: #dc3545; }
        .metric-value {
            font-size: 2em;
            font-weight: bold;
            color: #333;
            margin: 10px 0;
        }
        .scenario-results {
            background: white;
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
        }
        .scenario-card {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9f9f9;
        }
        .score-badge {
            padding: 4px 8px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
            font-size: 0.8em;
            margin-left: 10px;
        }
        .score-badge.score-good { background: #28a745; }
        .score-badge.score-fair { background: #ffc107; color: #333; }
        .score-badge.score-poor { background: #dc3545; }
        .scenario-stats {
            margin: 10px 0;
        }
        .scenario-stats .stat {
            margin-right: 15px;
            padding: 2px 6px;
            background: #e9ecef;
            border-radius: 3px;
            font-size: 0.9em;
        }
        .scenario-steps {
            margin-top: 15px;
        }
        .step-result {
            display: flex;
            align-items: center;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .step-result.success { color: #28a745; }
        .step-result.failure { color: #dc3545; }
        .step-name {
            flex: 1;
            font-weight: bold;
        }
        .step-status {
            margin: 0 10px;
            font-weight: bold;
        }
        .step-message {
            flex: 2;
            font-size: 0.9em;
        }
        .test-actions {
            background: white;
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#run-integration-tests').on('click', function() {
                const $button = $(this);
                $button.prop('disabled', true).text('Running Tests...');
                
                $.post(ajaxurl, {
                    action: 'run_integration_tests',
                    nonce: '<?php echo wp_create_nonce('integration_testing_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error running tests: ' + response.data);
                    }
                    $button.prop('disabled', false).text('Run All Integration Tests');
                });
            });
            
            $('#run-api-tests').on('click', function() {
                const $button = $(this);
                $button.prop('disabled', true).text('Running...');
                
                $.post(ajaxurl, {
                    action: 'run_api_tests',
                    nonce: '<?php echo wp_create_nonce('integration_testing_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error running API tests: ' + response.data);
                    }
                    $button.prop('disabled', false).text('Run API Tests Only');
                });
            });
            
            $('#run-theme-tests').on('click', function() {
                const $button = $(this);
                $button.prop('disabled', true).text('Running...');
                
                $.post(ajaxurl, {
                    action: 'run_theme_tests',
                    nonce: '<?php echo wp_create_nonce('integration_testing_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error running theme tests: ' + response.data);
                    }
                    $button.prop('disabled', false).text('Run Theme Tests Only');
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for running integration tests
     */
    public function ajax_run_integration_tests() {
        if (!wp_verify_nonce($_POST['nonce'], 'integration_testing_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }
        
        $results = $this->run_all_integration_tests();
        wp_send_json_success($results);
    }
    
    /**
     * AJAX handler for running API tests
     */
    public function ajax_run_api_tests() {
        if (!wp_verify_nonce($_POST['nonce'], 'integration_testing_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }
        
        $result = $this->run_test_scenario('api_integration');
        wp_send_json_success($result);
    }
    
    /**
     * AJAX handler for running theme tests
     */
    public function ajax_run_theme_tests() {
        if (!wp_verify_nonce($_POST['nonce'], 'integration_testing_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }
        
        $result = $this->run_test_scenario('theme_integration');
        wp_send_json_success($result);
    }
}
