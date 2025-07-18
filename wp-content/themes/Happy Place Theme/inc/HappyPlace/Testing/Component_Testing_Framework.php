<?php

namespace HappyPlace\Testing;

/**
 * Component Testing Framework
 *
 * Automated testing system for component validation, performance, and accessibility
 *
 * @package HappyPlace\Testing
 * @since 2.0.0
 */
class Component_Testing_Framework {
    
    /**
     * Singleton instance
     * @var Component_Testing_Framework
     */
    private static $instance = null;
    
    /**
     * Test results
     * @var array
     */
    private $test_results = [];
    
    /**
     * Performance benchmarks
     * @var array
     */
    private $benchmarks = [
        'render_time' => 0.1,      // seconds
        'memory_usage' => 5 * 1024 * 1024, // 5MB
        'query_count' => 5,         // maximum queries per component
        'html_size' => 50 * 1024    // 50KB maximum HTML output
    ];
    
    /**
     * Accessibility tests
     * @var array
     */
    private $accessibility_tests = [
        'alt_text_images',
        'aria_labels',
        'heading_structure',
        'color_contrast',
        'keyboard_navigation',
        'focus_indicators'
    ];
    
    /**
     * Component registry
     * @var array
     */
    private $registered_components = [];
    
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
        $this->register_core_components();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('wp_loaded', [$this, 'discover_components']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_ajax_run_component_tests', [$this, 'ajax_run_component_tests']);
        add_action('wp_ajax_run_single_test', [$this, 'ajax_run_single_test']);
        
        // Schedule automated testing
        add_action('hph_automated_component_tests', [$this, 'run_automated_tests']);
        
        if (!wp_next_scheduled('hph_automated_component_tests')) {
            wp_schedule_event(time(), 'daily', 'hph_automated_component_tests');
        }
    }
    
    /**
     * Register core components for testing
     */
    private function register_core_components() {
        $this->register_component('Listing_Swipe_Card', [
            'class' => 'HappyPlace\\Components\\Listing_Swipe_Card',
            'test_data' => [
                'listing_id' => 123,
                'title' => 'Test Property',
                'price' => '$500,000',
                'image_url' => 'https://example.com/test.jpg'
            ]
        ]);
        
        $this->register_component('Base_Component', [
            'class' => 'HappyPlace\\Components\\Base_Component',
            'test_data' => [
                'content' => 'Test content',
                'css_classes' => 'test-class'
            ]
        ]);
        
        $this->register_component('Agent_Card', [
            'class' => 'HappyPlace\\Components\\Agent_Card',
            'test_data' => [
                'agent_id' => 456,
                'name' => 'Test Agent',
                'email' => 'test@example.com',
                'phone' => '555-1234'
            ]
        ]);
    }
    
    /**
     * Register a component for testing
     */
    public function register_component($name, $config) {
        $this->registered_components[$name] = array_merge([
            'name' => $name,
            'enabled' => true,
            'last_tested' => null,
            'test_results' => []
        ], $config);
    }
    
    /**
     * Discover components automatically
     */
    public function discover_components() {
        $component_dir = get_template_directory() . '/inc/HappyPlace/Components/';
        
        if (!is_dir($component_dir)) {
            return;
        }
        
        $files = glob($component_dir . '*.php');
        
        foreach ($files as $file) {
            $class_name = basename($file, '.php');
            $full_class = "HappyPlace\\Components\\{$class_name}";
            
            if (class_exists($full_class) && !isset($this->registered_components[$class_name])) {
                $this->register_component($class_name, [
                    'class' => $full_class,
                    'auto_discovered' => true
                ]);
            }
        }
    }
    
    /**
     * Run tests for a specific component
     */
    public function test_component($component_name) {
        if (!isset($this->registered_components[$component_name])) {
            return [
                'success' => false,
                'error' => 'Component not registered'
            ];
        }
        
        $component_config = $this->registered_components[$component_name];
        $class_name = $component_config['class'];
        
        if (!class_exists($class_name)) {
            return [
                'success' => false,
                'error' => 'Component class not found'
            ];
        }
        
        $test_results = [
            'component' => $component_name,
            'timestamp' => current_time('timestamp'),
            'tests' => []
        ];
        
        // Performance Tests
        $test_results['tests']['performance'] = $this->run_performance_tests($class_name, $component_config);
        
        // Validation Tests
        $test_results['tests']['validation'] = $this->run_validation_tests($class_name, $component_config);
        
        // Accessibility Tests
        $test_results['tests']['accessibility'] = $this->run_accessibility_tests($class_name, $component_config);
        
        // HTML Output Tests
        $test_results['tests']['html_output'] = $this->run_html_output_tests($class_name, $component_config);
        
        // Error Handling Tests
        $test_results['tests']['error_handling'] = $this->run_error_handling_tests($class_name, $component_config);
        
        // Calculate overall score
        $test_results['overall_score'] = $this->calculate_test_score($test_results['tests']);
        $test_results['success'] = $test_results['overall_score'] >= 70;
        
        // Store results
        $this->registered_components[$component_name]['test_results'] = $test_results;
        $this->registered_components[$component_name]['last_tested'] = current_time('timestamp');
        
        return $test_results;
    }
    
    /**
     * Run performance tests
     */
    private function run_performance_tests($class_name, $config) {
        $results = [
            'passed' => 0,
            'failed' => 0,
            'details' => []
        ];
        
        try {
            // Measure render time
            $start_time = microtime(true);
            $start_memory = memory_get_usage(true);
            $start_queries = get_num_queries();
            
            // Create and render component
            $component = new $class_name($config['test_data'] ?? []);
            $html_output = $component->render();
            
            $end_time = microtime(true);
            $end_memory = memory_get_usage(true);
            $end_queries = get_num_queries();
            
            // Calculate metrics
            $render_time = $end_time - $start_time;
            $memory_used = $end_memory - $start_memory;
            $queries_used = $end_queries - $start_queries;
            $html_size = strlen($html_output);
            
            // Test render time
            if ($render_time <= $this->benchmarks['render_time']) {
                $results['passed']++;
                $results['details'][] = "✓ Render time: {$render_time}s (target: {$this->benchmarks['render_time']}s)";
            } else {
                $results['failed']++;
                $results['details'][] = "✗ Render time: {$render_time}s exceeds target of {$this->benchmarks['render_time']}s";
            }
            
            // Test memory usage
            if ($memory_used <= $this->benchmarks['memory_usage']) {
                $results['passed']++;
                $results['details'][] = "✓ Memory usage: " . size_format($memory_used) . " (target: " . size_format($this->benchmarks['memory_usage']) . ")";
            } else {
                $results['failed']++;
                $results['details'][] = "✗ Memory usage: " . size_format($memory_used) . " exceeds target of " . size_format($this->benchmarks['memory_usage']);
            }
            
            // Test query count
            if ($queries_used <= $this->benchmarks['query_count']) {
                $results['passed']++;
                $results['details'][] = "✓ Database queries: {$queries_used} (target: ≤{$this->benchmarks['query_count']})";
            } else {
                $results['failed']++;
                $results['details'][] = "✗ Database queries: {$queries_used} exceeds target of {$this->benchmarks['query_count']}";
            }
            
            // Test HTML size
            if ($html_size <= $this->benchmarks['html_size']) {
                $results['passed']++;
                $results['details'][] = "✓ HTML size: " . size_format($html_size) . " (target: " . size_format($this->benchmarks['html_size']) . ")";
            } else {
                $results['failed']++;
                $results['details'][] = "✗ HTML size: " . size_format($html_size) . " exceeds target of " . size_format($this->benchmarks['html_size']);
            }
            
        } catch (\Exception $e) {
            $results['failed']++;
            $results['details'][] = "✗ Performance test failed: " . $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Run validation tests
     */
    private function run_validation_tests($class_name, $config) {
        $results = [
            'passed' => 0,
            'failed' => 0,
            'details' => []
        ];
        
        try {
            // Test component instantiation
            $component = new $class_name($config['test_data'] ?? []);
            $results['passed']++;
            $results['details'][] = "✓ Component instantiation successful";
            
            // Test required methods exist
            $required_methods = ['render', 'validate'];
            foreach ($required_methods as $method) {
                if (method_exists($component, $method)) {
                    $results['passed']++;
                    $results['details'][] = "✓ Required method '{$method}' exists";
                } else {
                    $results['failed']++;
                    $results['details'][] = "✗ Required method '{$method}' missing";
                }
            }
            
            // Test validation method
            if (method_exists($component, 'validate')) {
                $validation_result = $component->validate();
                if (is_array($validation_result) && isset($validation_result['valid'])) {
                    $results['passed']++;
                    $results['details'][] = "✓ Validation method returns proper format";
                } else {
                    $results['failed']++;
                    $results['details'][] = "✗ Validation method returns invalid format";
                }
            }
            
            // Test with invalid data
            try {
                $invalid_component = new $class_name(['invalid' => 'data']);
                $invalid_html = $invalid_component->render();
                
                if (!empty($invalid_html)) {
                    $results['passed']++;
                    $results['details'][] = "✓ Component handles invalid data gracefully";
                } else {
                    $results['failed']++;
                    $results['details'][] = "✗ Component fails with invalid data";
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['details'][] = "✗ Component throws exception with invalid data: " . $e->getMessage();
            }
            
        } catch (\Exception $e) {
            $results['failed']++;
            $results['details'][] = "✗ Validation test failed: " . $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Run accessibility tests
     */
    private function run_accessibility_tests($class_name, $config) {
        $results = [
            'passed' => 0,
            'failed' => 0,
            'details' => []
        ];
        
        try {
            $component = new $class_name($config['test_data'] ?? []);
            $html_output = $component->render();
            
            // Test for alt text on images
            if (preg_match_all('/<img[^>]*>/i', $html_output, $img_matches)) {
                $images_with_alt = 0;
                foreach ($img_matches[0] as $img_tag) {
                    if (preg_match('/alt=["\'][^"\']*["\']/', $img_tag)) {
                        $images_with_alt++;
                    }
                }
                
                if ($images_with_alt === count($img_matches[0])) {
                    $results['passed']++;
                    $results['details'][] = "✓ All images have alt text";
                } else {
                    $results['failed']++;
                    $results['details'][] = "✗ Some images missing alt text ({$images_with_alt}/" . count($img_matches[0]) . ")";
                }
            } else {
                $results['passed']++;
                $results['details'][] = "✓ No images found (alt text test skipped)";
            }
            
            // Test for proper heading structure
            if (preg_match_all('/<h([1-6])[^>]*>/i', $html_output, $heading_matches)) {
                $heading_levels = array_map('intval', $heading_matches[1]);
                $proper_structure = true;
                
                for ($i = 1; $i < count($heading_levels); $i++) {
                    if ($heading_levels[$i] > $heading_levels[$i-1] + 1) {
                        $proper_structure = false;
                        break;
                    }
                }
                
                if ($proper_structure) {
                    $results['passed']++;
                    $results['details'][] = "✓ Proper heading hierarchy";
                } else {
                    $results['failed']++;
                    $results['details'][] = "✗ Improper heading hierarchy";
                }
            } else {
                $results['passed']++;
                $results['details'][] = "✓ No headings found (hierarchy test skipped)";
            }
            
            // Test for ARIA labels on interactive elements
            $interactive_elements = ['button', 'input', 'select', 'textarea', 'a'];
            $elements_with_labels = 0;
            $total_interactive = 0;
            
            foreach ($interactive_elements as $element) {
                if (preg_match_all("/<{$element}[^>]*>/i", $html_output, $element_matches)) {
                    $total_interactive += count($element_matches[0]);
                    
                    foreach ($element_matches[0] as $element_tag) {
                        if (preg_match('/(aria-label|aria-labelledby|title)=["\'][^"\']*["\']/', $element_tag)) {
                            $elements_with_labels++;
                        }
                    }
                }
            }
            
            if ($total_interactive === 0) {
                $results['passed']++;
                $results['details'][] = "✓ No interactive elements found (ARIA test skipped)";
            } elseif ($elements_with_labels >= $total_interactive * 0.8) { // 80% threshold
                $results['passed']++;
                $results['details'][] = "✓ Most interactive elements have ARIA labels ({$elements_with_labels}/{$total_interactive})";
            } else {
                $results['failed']++;
                $results['details'][] = "✗ Many interactive elements missing ARIA labels ({$elements_with_labels}/{$total_interactive})";
            }
            
            // Test for focus indicators (via CSS classes)
            if (strpos($html_output, 'focus:') !== false || strpos($html_output, 'focusable') !== false) {
                $results['passed']++;
                $results['details'][] = "✓ Focus indicators detected";
            } else {
                $results['failed']++;
                $results['details'][] = "✗ No focus indicators detected";
            }
            
        } catch (\Exception $e) {
            $results['failed']++;
            $results['details'][] = "✗ Accessibility test failed: " . $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Run HTML output tests
     */
    private function run_html_output_tests($class_name, $config) {
        $results = [
            'passed' => 0,
            'failed' => 0,
            'details' => []
        ];
        
        try {
            $component = new $class_name($config['test_data'] ?? []);
            $html_output = $component->render();
            
            // Test for valid HTML output
            if (!empty($html_output) && is_string($html_output)) {
                $results['passed']++;
                $results['details'][] = "✓ Component returns valid HTML string";
            } else {
                $results['failed']++;
                $results['details'][] = "✗ Component returns invalid or empty output";
            }
            
            // Test for proper HTML structure
            $tag_balance = $this->check_html_tag_balance($html_output);
            if ($tag_balance['balanced']) {
                $results['passed']++;
                $results['details'][] = "✓ HTML tags are properly balanced";
            } else {
                $results['failed']++;
                $results['details'][] = "✗ HTML tags are not balanced: " . implode(', ', $tag_balance['unbalanced']);
            }
            
            // Test for XSS vulnerabilities
            $xss_patterns = [
                '/<script[^>]*>/',
                '/on\w+\s*=/',
                '/javascript:/',
                '/<iframe[^>]*>/'
            ];
            
            $xss_found = false;
            foreach ($xss_patterns as $pattern) {
                if (preg_match($pattern, $html_output)) {
                    $xss_found = true;
                    break;
                }
            }
            
            if (!$xss_found) {
                $results['passed']++;
                $results['details'][] = "✓ No XSS vulnerabilities detected";
            } else {
                $results['failed']++;
                $results['details'][] = "✗ Potential XSS vulnerabilities detected";
            }
            
            // Test for semantic HTML
            $semantic_tags = ['article', 'section', 'header', 'footer', 'nav', 'main', 'aside'];
            $has_semantic = false;
            
            foreach ($semantic_tags as $tag) {
                if (strpos($html_output, "<{$tag}") !== false) {
                    $has_semantic = true;
                    break;
                }
            }
            
            if ($has_semantic || strlen($html_output) < 100) {
                $results['passed']++;
                $results['details'][] = "✓ Uses semantic HTML elements or is simple component";
            } else {
                $results['failed']++;
                $results['details'][] = "✗ Should consider using semantic HTML elements";
            }
            
        } catch (\Exception $e) {
            $results['failed']++;
            $results['details'][] = "✗ HTML output test failed: " . $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Run error handling tests
     */
    private function run_error_handling_tests($class_name, $config) {
        $results = [
            'passed' => 0,
            'failed' => 0,
            'details' => []
        ];
        
        // Test with null data
        try {
            $component = new $class_name(null);
            $html = $component->render();
            
            if (is_string($html)) {
                $results['passed']++;
                $results['details'][] = "✓ Handles null data gracefully";
            } else {
                $results['failed']++;
                $results['details'][] = "✗ Fails with null data";
            }
        } catch (\Exception $e) {
            $results['failed']++;
            $results['details'][] = "✗ Throws exception with null data: " . $e->getMessage();
        }
        
        // Test with empty array
        try {
            $component = new $class_name([]);
            $html = $component->render();
            
            if (is_string($html)) {
                $results['passed']++;
                $results['details'][] = "✓ Handles empty data gracefully";
            } else {
                $results['failed']++;
                $results['details'][] = "✗ Fails with empty data";
            }
        } catch (\Exception $e) {
            $results['failed']++;
            $results['details'][] = "✗ Throws exception with empty data: " . $e->getMessage();
        }
        
        // Test with malformed data
        try {
            $malformed_data = [
                'listing_id' => 'not_a_number',
                'price' => [],
                'image_url' => false
            ];
            
            $component = new $class_name($malformed_data);
            $html = $component->render();
            
            if (is_string($html)) {
                $results['passed']++;
                $results['details'][] = "✓ Handles malformed data gracefully";
            } else {
                $results['failed']++;
                $results['details'][] = "✗ Fails with malformed data";
            }
        } catch (\Exception $e) {
            $results['failed']++;
            $results['details'][] = "✗ Throws exception with malformed data: " . $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Check HTML tag balance
     */
    private function check_html_tag_balance($html) {
        $open_tags = [];
        $unbalanced = [];
        
        // Remove self-closing tags and comments
        $html = preg_replace('/<[^>]*\/>/s', '', $html);
        $html = preg_replace('/<!--.*?-->/s', '', $html);
        
        // Match all tags
        preg_match_all('/<\/?([a-zA-Z][a-zA-Z0-9]*)[^>]*>/i', $html, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $tag = strtolower($match[1]);
            $is_closing = $match[0][1] === '/';
            
            if ($is_closing) {
                if (empty($open_tags) || array_pop($open_tags) !== $tag) {
                    $unbalanced[] = $tag;
                }
            } else {
                // Skip void elements
                $void_elements = ['br', 'hr', 'img', 'input', 'meta', 'link', 'area', 'base', 'col', 'embed', 'source', 'track', 'wbr'];
                if (!in_array($tag, $void_elements)) {
                    $open_tags[] = $tag;
                }
            }
        }
        
        // Any remaining open tags are unbalanced
        $unbalanced = array_merge($unbalanced, $open_tags);
        
        return [
            'balanced' => empty($unbalanced),
            'unbalanced' => array_unique($unbalanced)
        ];
    }
    
    /**
     * Calculate overall test score
     */
    private function calculate_test_score($tests) {
        $total_passed = 0;
        $total_tests = 0;
        
        foreach ($tests as $test_category) {
            $total_passed += $test_category['passed'];
            $total_tests += $test_category['passed'] + $test_category['failed'];
        }
        
        return $total_tests > 0 ? round(($total_passed / $total_tests) * 100) : 0;
    }
    
    /**
     * Run all component tests
     */
    public function run_all_tests() {
        $results = [];
        
        foreach ($this->registered_components as $name => $component) {
            if ($component['enabled']) {
                $results[$name] = $this->test_component($name);
            }
        }
        
        // Store comprehensive results
        update_option('hph_component_test_results', [
            'timestamp' => current_time('timestamp'),
            'results' => $results,
            'summary' => $this->generate_test_summary($results)
        ]);
        
        return $results;
    }
    
    /**
     * Generate test summary
     */
    private function generate_test_summary($results) {
        $total_components = count($results);
        $passed_components = 0;
        $total_score = 0;
        
        foreach ($results as $result) {
            if ($result['success']) {
                $passed_components++;
            }
            $total_score += $result['overall_score'];
        }
        
        return [
            'total_components' => $total_components,
            'passed_components' => $passed_components,
            'failed_components' => $total_components - $passed_components,
            'pass_rate' => $total_components > 0 ? round(($passed_components / $total_components) * 100) : 0,
            'average_score' => $total_components > 0 ? round($total_score / $total_components) : 0
        ];
    }
    
    /**
     * Run automated tests
     */
    public function run_automated_tests() {
        $results = $this->run_all_tests();
        
        // Check if any critical failures
        $critical_failures = 0;
        foreach ($results as $result) {
            if ($result['overall_score'] < 50) {
                $critical_failures++;
            }
        }
        
        // Log critical failures
        if ($critical_failures > 0) {
            error_log("HPH Component Testing: {$critical_failures} components have critical test failures");
        }
        
        return $results;
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_management_page(
            'Component Testing',
            'Component Tests',
            'manage_options',
            'hph-component-testing',
            [$this, 'render_admin_page']
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $test_results = get_option('hph_component_test_results', []);
        ?>
        <div class="wrap">
            <h1>Component Testing Framework</h1>
            
            <div class="testing-dashboard">
                <?php if (!empty($test_results)): ?>
                    <div class="dashboard-section">
                        <h2>Test Summary</h2>
                        <div class="summary-stats">
                            <div class="stat">
                                <span class="value"><?php echo esc_html($test_results['summary']['total_components']); ?></span>
                                <span class="label">Components</span>
                            </div>
                            <div class="stat">
                                <span class="value"><?php echo esc_html($test_results['summary']['pass_rate']); ?>%</span>
                                <span class="label">Pass Rate</span>
                            </div>
                            <div class="stat">
                                <span class="value"><?php echo esc_html($test_results['summary']['average_score']); ?></span>
                                <span class="label">Avg Score</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dashboard-section">
                        <h2>Component Results</h2>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Component</th>
                                    <th>Score</th>
                                    <th>Status</th>
                                    <th>Last Tested</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($test_results['results'] as $name => $result): ?>
                                    <tr>
                                        <td><?php echo esc_html($name); ?></td>
                                        <td>
                                            <span class="score-badge score-<?php echo $result['overall_score'] >= 80 ? 'good' : ($result['overall_score'] >= 60 ? 'fair' : 'poor'); ?>">
                                                <?php echo esc_html($result['overall_score']); ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-<?php echo $result['success'] ? 'pass' : 'fail'; ?>">
                                                <?php echo $result['success'] ? 'PASS' : 'FAIL'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html(human_time_diff($result['timestamp'])); ?> ago</td>
                                        <td>
                                            <button class="button run-single-test" data-component="<?php echo esc_attr($name); ?>">
                                                Retest
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
                <div class="dashboard-section">
                    <h2>Actions</h2>
                    <p>
                        <button class="button button-primary" id="run-all-tests">
                            Run All Tests
                        </button>
                        <button class="button" id="discover-components">
                            Discover Components
                        </button>
                    </p>
                </div>
            </div>
        </div>
        
        <style>
        .summary-stats {
            display: flex;
            gap: 30px;
            margin: 20px 0;
        }
        .stat {
            text-align: center;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            min-width: 120px;
        }
        .stat .value {
            display: block;
            font-size: 2em;
            font-weight: bold;
            color: #333;
        }
        .stat .label {
            display: block;
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
        .score-badge {
            padding: 4px 8px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
        }
        .score-good { background: #28a745; }
        .score-fair { background: #ffc107; color: #333; }
        .score-poor { background: #dc3545; }
        .status-pass { color: #28a745; font-weight: bold; }
        .status-fail { color: #dc3545; font-weight: bold; }
        .dashboard-section {
            background: white;
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#run-all-tests').on('click', function() {
                const $button = $(this);
                $button.prop('disabled', true).text('Running Tests...');
                
                $.post(ajaxurl, {
                    action: 'run_component_tests',
                    nonce: '<?php echo wp_create_nonce('component_testing_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error running tests: ' + response.data);
                    }
                    $button.prop('disabled', false).text('Run All Tests');
                });
            });
            
            $('.run-single-test').on('click', function() {
                const $button = $(this);
                const component = $button.data('component');
                
                $button.prop('disabled', true).text('Testing...');
                
                $.post(ajaxurl, {
                    action: 'run_single_test',
                    component: component,
                    nonce: '<?php echo wp_create_nonce('component_testing_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error running test: ' + response.data);
                    }
                    $button.prop('disabled', false).text('Retest');
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for running all tests
     */
    public function ajax_run_component_tests() {
        if (!wp_verify_nonce($_POST['nonce'], 'component_testing_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }
        
        $results = $this->run_all_tests();
        wp_send_json_success($results);
    }
    
    /**
     * AJAX handler for running single test
     */
    public function ajax_run_single_test() {
        if (!wp_verify_nonce($_POST['nonce'], 'component_testing_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }
        
        $component = sanitize_text_field($_POST['component']);
        $result = $this->test_component($component);
        
        wp_send_json_success($result);
    }
}
