<?php

namespace HappyPlace\Testing;

use HappyPlace\Performance\Performance_Monitor;

/**
 * Performance Testing Framework
 *
 * Automated performance testing and optimization recommendations
 *
 * @package HappyPlace\Testing
 * @since 2.0.0
 */
class Performance_Testing_Framework {
    
    /**
     * Singleton instance
     * @var Performance_Testing_Framework
     */
    private static $instance = null;
    
    /**
     * Performance Monitor instance
     * @var Performance_Monitor
     */
    private $performance_monitor;
    
    /**
     * Test configurations
     * @var array
     */
    private $test_configs = [
        'page_load' => [
            'max_time' => 3.0,          // seconds
            'max_queries' => 20,        // database queries
            'max_memory' => 50 * 1024 * 1024, // 50MB
            'max_dom_size' => 2000      // DOM nodes
        ],
        'api_response' => [
            'max_time' => 1.0,          // seconds
            'max_memory' => 10 * 1024 * 1024, // 10MB
            'max_queries' => 5          // database queries
        ],
        'ajax_request' => [
            'max_time' => 0.5,          // seconds
            'max_memory' => 5 * 1024 * 1024,  // 5MB
            'max_queries' => 3          // database queries
        ]
    ];
    
    /**
     * Core Web Vitals targets
     * @var array
     */
    private $cwv_targets = [
        'LCP' => 2.5,    // Largest Contentful Paint (seconds)
        'FID' => 0.1,    // First Input Delay (seconds)
        'CLS' => 0.1,    // Cumulative Layout Shift
        'FCP' => 1.8,    // First Contentful Paint (seconds)
        'TTFB' => 0.6    // Time to First Byte (seconds)
    ];
    
    /**
     * Test results storage
     * @var array
     */
    private $test_results = [];
    
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
        $this->performance_monitor = Performance_Monitor::get_instance();
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_ajax_run_performance_tests', [$this, 'ajax_run_performance_tests']);
        add_action('wp_ajax_run_page_speed_test', [$this, 'ajax_run_page_speed_test']);
        add_action('wp_ajax_generate_performance_report', [$this, 'ajax_generate_performance_report']);
        
        // Schedule automated performance tests
        add_action('hph_automated_performance_tests', [$this, 'run_automated_tests']);
        
        if (!wp_next_scheduled('hph_automated_performance_tests')) {
            wp_schedule_event(time(), 'twicedaily', 'hph_automated_performance_tests');
        }
    }
    
    /**
     * Run comprehensive performance test suite
     */
    public function run_performance_test_suite() {
        $results = [
            'timestamp' => current_time('timestamp'),
            'tests' => []
        ];
        
        // Test homepage performance
        $results['tests']['homepage'] = $this->test_page_performance(home_url('/'));
        
        // Test key pages
        $key_pages = [
            'listings' => home_url('/listings/'),
            'agents' => home_url('/agents/'),
            'contact' => home_url('/contact/')
        ];
        
        foreach ($key_pages as $page_name => $url) {
            $results['tests'][$page_name] = $this->test_page_performance($url);
        }
        
        // Test API endpoints
        $results['tests']['api_listings'] = $this->test_api_performance('/wp-json/hph/v1/listings');
        $results['tests']['api_agents'] = $this->test_api_performance('/wp-json/hph/v1/agents');
        
        // Test AJAX requests
        $results['tests']['ajax_search'] = $this->test_ajax_performance('hph_property_search');
        $results['tests']['ajax_filter'] = $this->test_ajax_performance('hph_property_filter');
        
        // Generate recommendations
        $results['recommendations'] = $this->generate_performance_recommendations($results['tests']);
        
        // Calculate overall score
        $results['overall_score'] = $this->calculate_performance_score($results['tests']);
        
        // Store results
        update_option('hph_performance_test_results', $results);
        
        return $results;
    }
    
    /**
     * Test page performance
     */
    private function test_page_performance($url) {
        $test_result = [
            'url' => $url,
            'timestamp' => current_time('timestamp'),
            'metrics' => [],
            'issues' => [],
            'score' => 0
        ];
        
        try {
            // Start monitoring
            $start_time = microtime(true);
            $start_memory = memory_get_usage(true);
            $start_queries = get_num_queries();
            
            // Make request to page
            $response = wp_remote_get($url, [
                'timeout' => 30,
                'user-agent' => 'HPH Performance Tester'
            ]);
            
            $end_time = microtime(true);
            $end_memory = memory_get_usage(true);
            $end_queries = get_num_queries();
            
            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }
            
            $body = wp_remote_retrieve_body($response);
            $headers = wp_remote_retrieve_headers($response);
            
            // Calculate metrics
            $test_result['metrics'] = [
                'load_time' => $end_time - $start_time,
                'memory_usage' => $end_memory - $start_memory,
                'query_count' => $end_queries - $start_queries,
                'response_size' => strlen($body),
                'http_status' => wp_remote_retrieve_response_code($response),
                'ttfb' => $this->extract_ttfb_from_headers($headers)
            ];
            
            // Analyze HTML content
            $html_analysis = $this->analyze_html_content($body);
            $test_result['metrics'] = array_merge($test_result['metrics'], $html_analysis);
            
            // Check against benchmarks
            $this->evaluate_page_performance($test_result);
            
        } catch (\Exception $e) {
            $test_result['issues'][] = 'Test failed: ' . $e->getMessage();
            $test_result['score'] = 0;
        }
        
        return $test_result;
    }
    
    /**
     * Test API performance
     */
    private function test_api_performance($endpoint) {
        $test_result = [
            'endpoint' => $endpoint,
            'timestamp' => current_time('timestamp'),
            'metrics' => [],
            'issues' => [],
            'score' => 0
        ];
        
        try {
            $start_time = microtime(true);
            $start_memory = memory_get_usage(true);
            $start_queries = get_num_queries();
            
            $response = wp_remote_get(home_url($endpoint), [
                'timeout' => 10,
                'headers' => [
                    'Accept' => 'application/json'
                ]
            ]);
            
            $end_time = microtime(true);
            $end_memory = memory_get_usage(true);
            $end_queries = get_num_queries();
            
            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }
            
            $body = wp_remote_retrieve_body($response);
            $json_data = json_decode($body, true);
            
            $test_result['metrics'] = [
                'response_time' => $end_time - $start_time,
                'memory_usage' => $end_memory - $start_memory,
                'query_count' => $end_queries - $start_queries,
                'response_size' => strlen($body),
                'http_status' => wp_remote_retrieve_response_code($response),
                'json_valid' => json_last_error() === JSON_ERROR_NONE,
                'record_count' => is_array($json_data) ? count($json_data) : 0
            ];
            
            // Evaluate against API benchmarks
            $this->evaluate_api_performance($test_result);
            
        } catch (\Exception $e) {
            $test_result['issues'][] = 'API test failed: ' . $e->getMessage();
            $test_result['score'] = 0;
        }
        
        return $test_result;
    }
    
    /**
     * Test AJAX performance
     */
    private function test_ajax_performance($action) {
        $test_result = [
            'action' => $action,
            'timestamp' => current_time('timestamp'),
            'metrics' => [],
            'issues' => [],
            'score' => 0
        ];
        
        try {
            $start_time = microtime(true);
            $start_memory = memory_get_usage(true);
            $start_queries = get_num_queries();
            
            // Simulate AJAX request
            $response = wp_remote_post(admin_url('admin-ajax.php'), [
                'body' => [
                    'action' => $action,
                    'test_mode' => 1
                ],
                'timeout' => 10
            ]);
            
            $end_time = microtime(true);
            $end_memory = memory_get_usage(true);
            $end_queries = get_num_queries();
            
            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }
            
            $body = wp_remote_retrieve_body($response);
            
            $test_result['metrics'] = [
                'response_time' => $end_time - $start_time,
                'memory_usage' => $end_memory - $start_memory,
                'query_count' => $end_queries - $start_queries,
                'response_size' => strlen($body),
                'http_status' => wp_remote_retrieve_response_code($response)
            ];
            
            // Evaluate against AJAX benchmarks
            $this->evaluate_ajax_performance($test_result);
            
        } catch (\Exception $e) {
            $test_result['issues'][] = 'AJAX test failed: ' . $e->getMessage();
            $test_result['score'] = 0;
        }
        
        return $test_result;
    }
    
    /**
     * Analyze HTML content
     */
    private function analyze_html_content($html) {
        $analysis = [
            'dom_size' => 0,
            'image_count' => 0,
            'script_count' => 0,
            'style_count' => 0,
            'external_requests' => 0,
            'unoptimized_images' => 0
        ];
        
        // Count DOM elements
        $analysis['dom_size'] = substr_count($html, '<') - substr_count($html, '<!--');
        
        // Count images
        preg_match_all('/<img[^>]*>/i', $html, $img_matches);
        $analysis['image_count'] = count($img_matches[0]);
        
        // Check for unoptimized images
        foreach ($img_matches[0] as $img_tag) {
            if (!preg_match('/\.(webp|avif)/i', $img_tag)) {
                $analysis['unoptimized_images']++;
            }
        }
        
        // Count scripts
        preg_match_all('/<script[^>]*>/i', $html, $script_matches);
        $analysis['script_count'] = count($script_matches[0]);
        
        // Count stylesheets
        preg_match_all('/<link[^>]*rel=["\']stylesheet["\'][^>]*>/i', $html, $style_matches);
        $analysis['style_count'] = count($style_matches[0]);
        
        // Count external requests
        $analysis['external_requests'] = $this->count_external_requests($html);
        
        return $analysis;
    }
    
    /**
     * Count external requests in HTML
     */
    private function count_external_requests($html) {
        $external_count = 0;
        $site_domain = parse_url(home_url(), PHP_URL_HOST);
        
        // Check scripts
        preg_match_all('/src=["\']([^"\']*)["\']/', $html, $src_matches);
        foreach ($src_matches[1] as $src) {
            if (filter_var($src, FILTER_VALIDATE_URL)) {
                $src_domain = parse_url($src, PHP_URL_HOST);
                if ($src_domain && $src_domain !== $site_domain) {
                    $external_count++;
                }
            }
        }
        
        // Check stylesheets
        preg_match_all('/href=["\']([^"\']*\.css[^"\']*)["\']/', $html, $css_matches);
        foreach ($css_matches[1] as $href) {
            if (filter_var($href, FILTER_VALIDATE_URL)) {
                $href_domain = parse_url($href, PHP_URL_HOST);
                if ($href_domain && $href_domain !== $site_domain) {
                    $external_count++;
                }
            }
        }
        
        return $external_count;
    }
    
    /**
     * Extract TTFB from headers
     */
    private function extract_ttfb_from_headers($headers) {
        // Look for server timing header
        if (isset($headers['server-timing'])) {
            if (preg_match('/ttfb;dur=([0-9.]+)/', $headers['server-timing'], $matches)) {
                return floatval($matches[1]) / 1000; // Convert ms to seconds
            }
        }
        
        return null;
    }
    
    /**
     * Evaluate page performance
     */
    private function evaluate_page_performance(&$test_result) {
        $metrics = $test_result['metrics'];
        $config = $this->test_configs['page_load'];
        $score = 100;
        
        // Check load time
        if ($metrics['load_time'] > $config['max_time']) {
            $test_result['issues'][] = sprintf(
                'Page load time (%.2fs) exceeds target (%.2fs)',
                $metrics['load_time'],
                $config['max_time']
            );
            $score -= 20;
        }
        
        // Check memory usage
        if ($metrics['memory_usage'] > $config['max_memory']) {
            $test_result['issues'][] = sprintf(
                'Memory usage (%s) exceeds target (%s)',
                size_format($metrics['memory_usage']),
                size_format($config['max_memory'])
            );
            $score -= 15;
        }
        
        // Check query count
        if ($metrics['query_count'] > $config['max_queries']) {
            $test_result['issues'][] = sprintf(
                'Database queries (%d) exceed target (%d)',
                $metrics['query_count'],
                $config['max_queries']
            );
            $score -= 15;
        }
        
        // Check DOM size
        if (isset($metrics['dom_size']) && $metrics['dom_size'] > $config['max_dom_size']) {
            $test_result['issues'][] = sprintf(
                'DOM size (%d elements) exceeds target (%d)',
                $metrics['dom_size'],
                $config['max_dom_size']
            );
            $score -= 10;
        }
        
        // Check for unoptimized images
        if (isset($metrics['unoptimized_images']) && $metrics['unoptimized_images'] > 0) {
            $test_result['issues'][] = sprintf(
                '%d images could be optimized (WebP format)',
                $metrics['unoptimized_images']
            );
            $score -= 10;
        }
        
        // Check external requests
        if (isset($metrics['external_requests']) && $metrics['external_requests'] > 10) {
            $test_result['issues'][] = sprintf(
                'Too many external requests (%d) - consider reducing',
                $metrics['external_requests']
            );
            $score -= 10;
        }
        
        $test_result['score'] = max(0, $score);
    }
    
    /**
     * Evaluate API performance
     */
    private function evaluate_api_performance(&$test_result) {
        $metrics = $test_result['metrics'];
        $config = $this->test_configs['api_response'];
        $score = 100;
        
        if ($metrics['response_time'] > $config['max_time']) {
            $test_result['issues'][] = sprintf(
                'API response time (%.3fs) exceeds target (%.3fs)',
                $metrics['response_time'],
                $config['max_time']
            );
            $score -= 30;
        }
        
        if ($metrics['memory_usage'] > $config['max_memory']) {
            $test_result['issues'][] = sprintf(
                'Memory usage (%s) exceeds target (%s)',
                size_format($metrics['memory_usage']),
                size_format($config['max_memory'])
            );
            $score -= 25;
        }
        
        if ($metrics['query_count'] > $config['max_queries']) {
            $test_result['issues'][] = sprintf(
                'Database queries (%d) exceed target (%d)',
                $metrics['query_count'],
                $config['max_queries']
            );
            $score -= 25;
        }
        
        if (!$metrics['json_valid']) {
            $test_result['issues'][] = 'Invalid JSON response';
            $score -= 20;
        }
        
        $test_result['score'] = max(0, $score);
    }
    
    /**
     * Evaluate AJAX performance
     */
    private function evaluate_ajax_performance(&$test_result) {
        $metrics = $test_result['metrics'];
        $config = $this->test_configs['ajax_request'];
        $score = 100;
        
        if ($metrics['response_time'] > $config['max_time']) {
            $test_result['issues'][] = sprintf(
                'AJAX response time (%.3fs) exceeds target (%.3fs)',
                $metrics['response_time'],
                $config['max_time']
            );
            $score -= 40;
        }
        
        if ($metrics['memory_usage'] > $config['max_memory']) {
            $test_result['issues'][] = sprintf(
                'Memory usage (%s) exceeds target (%s)',
                size_format($metrics['memory_usage']),
                size_format($config['max_memory'])
            );
            $score -= 30;
        }
        
        if ($metrics['query_count'] > $config['max_queries']) {
            $test_result['issues'][] = sprintf(
                'Database queries (%d) exceed target (%d)',
                $metrics['query_count'],
                $config['max_queries']
            );
            $score -= 30;
        }
        
        $test_result['score'] = max(0, $score);
    }
    
    /**
     * Generate performance recommendations
     */
    private function generate_performance_recommendations($test_results) {
        $recommendations = [];
        $issues_by_type = [];
        
        // Categorize issues
        foreach ($test_results as $test_name => $result) {
            if (!empty($result['issues'])) {
                foreach ($result['issues'] as $issue) {
                    if (strpos($issue, 'load time') !== false) {
                        $issues_by_type['slow_loading'][] = $test_name;
                    } elseif (strpos($issue, 'Memory') !== false) {
                        $issues_by_type['high_memory'][] = $test_name;
                    } elseif (strpos($issue, 'queries') !== false) {
                        $issues_by_type['too_many_queries'][] = $test_name;
                    } elseif (strpos($issue, 'images') !== false) {
                        $issues_by_type['unoptimized_images'][] = $test_name;
                    } elseif (strpos($issue, 'external') !== false) {
                        $issues_by_type['external_requests'][] = $test_name;
                    }
                }
            }
        }
        
        // Generate recommendations based on issues
        if (!empty($issues_by_type['slow_loading'])) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'Performance',
                'title' => 'Optimize Page Load Times',
                'description' => 'Multiple pages have slow load times. Consider implementing caching, optimizing database queries, and reducing resource sizes.',
                'affected_pages' => $issues_by_type['slow_loading'],
                'actions' => [
                    'Enable object caching (Redis/Memcached)',
                    'Implement page caching',
                    'Optimize database queries',
                    'Minify CSS and JavaScript',
                    'Use CDN for static assets'
                ]
            ];
        }
        
        if (!empty($issues_by_type['high_memory'])) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'Memory',
                'title' => 'Reduce Memory Usage',
                'description' => 'High memory usage detected. Review code for memory leaks and optimize data processing.',
                'affected_pages' => $issues_by_type['high_memory'],
                'actions' => [
                    'Review and optimize loops',
                    'Implement data pagination',
                    'Use lazy loading for large datasets',
                    'Clear unused variables',
                    'Optimize image processing'
                ]
            ];
        }
        
        if (!empty($issues_by_type['too_many_queries'])) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'Database',
                'title' => 'Optimize Database Queries',
                'description' => 'Excessive database queries detected. Implement query optimization and caching.',
                'affected_pages' => $issues_by_type['too_many_queries'],
                'actions' => [
                    'Use WP_Query efficiently',
                    'Implement query result caching',
                    'Add database indexes',
                    'Use get_posts() instead of WP_Query when appropriate',
                    'Consider using transients for repeated queries'
                ]
            ];
        }
        
        if (!empty($issues_by_type['unoptimized_images'])) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'Images',
                'title' => 'Optimize Image Formats',
                'description' => 'Many images could benefit from modern formats like WebP.',
                'affected_pages' => $issues_by_type['unoptimized_images'],
                'actions' => [
                    'Convert images to WebP format',
                    'Implement responsive images',
                    'Add lazy loading for images',
                    'Compress existing images',
                    'Use appropriate image dimensions'
                ]
            ];
        }
        
        if (!empty($issues_by_type['external_requests'])) {
            $recommendations[] = [
                'priority' => 'low',
                'category' => 'Network',
                'title' => 'Reduce External Requests',
                'description' => 'Too many external requests can slow down page loading.',
                'affected_pages' => $issues_by_type['external_requests'],
                'actions' => [
                    'Host assets locally when possible',
                    'Combine multiple external resources',
                    'Use async/defer for non-critical scripts',
                    'Remove unused external resources',
                    'Implement resource hints (preconnect, dns-prefetch)'
                ]
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Calculate overall performance score
     */
    private function calculate_performance_score($test_results) {
        $total_score = 0;
        $test_count = 0;
        
        foreach ($test_results as $result) {
            if (isset($result['score'])) {
                $total_score += $result['score'];
                $test_count++;
            }
        }
        
        return $test_count > 0 ? round($total_score / $test_count) : 0;
    }
    
    /**
     * Run automated tests
     */
    public function run_automated_tests() {
        $results = $this->run_performance_test_suite();
        
        // Alert on critical performance issues
        if ($results['overall_score'] < 60) {
            error_log("HPH Performance Alert: Overall performance score is {$results['overall_score']}%");
        }
        
        return $results;
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_management_page(
            'Performance Testing',
            'Performance Tests',
            'manage_options',
            'hph-performance-testing',
            [$this, 'render_admin_page']
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $test_results = get_option('hph_performance_test_results', []);
        ?>
        <div class="wrap">
            <h1>Performance Testing Framework</h1>
            
            <div class="performance-dashboard">
                <?php if (!empty($test_results)): ?>
                    <div class="dashboard-summary">
                        <div class="summary-card">
                            <h3>Overall Score</h3>
                            <div class="score-display score-<?php echo $test_results['overall_score'] >= 80 ? 'good' : ($test_results['overall_score'] >= 60 ? 'fair' : 'poor'); ?>">
                                <?php echo esc_html($test_results['overall_score']); ?>%
                            </div>
                            <small>Last tested: <?php echo esc_html(human_time_diff($test_results['timestamp'])); ?> ago</small>
                        </div>
                        
                        <div class="summary-card">
                            <h3>Tests Run</h3>
                            <div class="metric-value"><?php echo count($test_results['tests']); ?></div>
                            <small>Different test scenarios</small>
                        </div>
                        
                        <div class="summary-card">
                            <h3>Issues Found</h3>
                            <div class="metric-value">
                                <?php 
                                $total_issues = 0;
                                foreach ($test_results['tests'] as $test) {
                                    $total_issues += count($test['issues'] ?? []);
                                }
                                echo $total_issues;
                                ?>
                            </div>
                            <small>Performance issues</small>
                        </div>
                        
                        <div class="summary-card">
                            <h3>Recommendations</h3>
                            <div class="metric-value"><?php echo count($test_results['recommendations'] ?? []); ?></div>
                            <small>Optimization suggestions</small>
                        </div>
                    </div>
                    
                    <div class="test-results">
                        <h2>Test Results</h2>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Test</th>
                                    <th>Score</th>
                                    <th>Load Time</th>
                                    <th>Memory</th>
                                    <th>Queries</th>
                                    <th>Issues</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($test_results['tests'] as $test_name => $result): ?>
                                    <tr>
                                        <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $test_name))); ?></td>
                                        <td>
                                            <span class="score-badge score-<?php echo $result['score'] >= 80 ? 'good' : ($result['score'] >= 60 ? 'fair' : 'poor'); ?>">
                                                <?php echo esc_html($result['score']); ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $time_key = isset($result['metrics']['load_time']) ? 'load_time' : 'response_time';
                                            echo isset($result['metrics'][$time_key]) ? sprintf('%.3fs', $result['metrics'][$time_key]) : 'N/A';
                                            ?>
                                        </td>
                                        <td>
                                            <?php echo isset($result['metrics']['memory_usage']) ? size_format($result['metrics']['memory_usage']) : 'N/A'; ?>
                                        </td>
                                        <td>
                                            <?php echo isset($result['metrics']['query_count']) ? $result['metrics']['query_count'] : 'N/A'; ?>
                                        </td>
                                        <td>
                                            <span class="issue-count"><?php echo count($result['issues'] ?? []); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (!empty($test_results['recommendations'])): ?>
                        <div class="recommendations">
                            <h2>Performance Recommendations</h2>
                            <?php foreach ($test_results['recommendations'] as $recommendation): ?>
                                <div class="recommendation priority-<?php echo esc_attr($recommendation['priority']); ?>">
                                    <h3>
                                        <span class="priority-badge"><?php echo esc_html(ucfirst($recommendation['priority'])); ?></span>
                                        <?php echo esc_html($recommendation['title']); ?>
                                    </h3>
                                    <p><?php echo esc_html($recommendation['description']); ?></p>
                                    <div class="actions">
                                        <h4>Recommended Actions:</h4>
                                        <ul>
                                            <?php foreach ($recommendation['actions'] as $action): ?>
                                                <li><?php echo esc_html($action); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <div class="test-actions">
                    <h2>Actions</h2>
                    <p>
                        <button class="button button-primary" id="run-performance-tests">
                            Run Performance Tests
                        </button>
                        <button class="button" id="generate-report">
                            Generate Report
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
        .score-badge {
            padding: 4px 8px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
            font-size: 0.9em;
        }
        .score-badge.score-good { background: #28a745; }
        .score-badge.score-fair { background: #ffc107; color: #333; }
        .score-badge.score-poor { background: #dc3545; }
        .test-results, .recommendations, .test-actions {
            background: white;
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
        }
        .recommendation {
            margin: 20px 0;
            padding: 15px;
            border-left: 4px solid #ddd;
            background: #f9f9f9;
        }
        .recommendation.priority-high { border-left-color: #dc3545; }
        .recommendation.priority-medium { border-left-color: #ffc107; }
        .recommendation.priority-low { border-left-color: #28a745; }
        .priority-badge {
            background: #666;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.8em;
            margin-right: 10px;
        }
        .priority-high .priority-badge { background: #dc3545; }
        .priority-medium .priority-badge { background: #ffc107; color: #333; }
        .priority-low .priority-badge { background: #28a745; }
        .issue-count {
            font-weight: bold;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#run-performance-tests').on('click', function() {
                const $button = $(this);
                $button.prop('disabled', true).text('Running Tests...');
                
                $.post(ajaxurl, {
                    action: 'run_performance_tests',
                    nonce: '<?php echo wp_create_nonce('performance_testing_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error running tests: ' + response.data);
                    }
                    $button.prop('disabled', false).text('Run Performance Tests');
                });
            });
            
            $('#generate-report').on('click', function() {
                const $button = $(this);
                $button.prop('disabled', true).text('Generating...');
                
                $.post(ajaxurl, {
                    action: 'generate_performance_report',
                    nonce: '<?php echo wp_create_nonce('performance_testing_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        // Download the report
                        const blob = new Blob([response.data], { type: 'text/html' });
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'performance-report.html';
                        a.click();
                        window.URL.revokeObjectURL(url);
                    } else {
                        alert('Error generating report: ' + response.data);
                    }
                    $button.prop('disabled', false).text('Generate Report');
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for running performance tests
     */
    public function ajax_run_performance_tests() {
        if (!wp_verify_nonce($_POST['nonce'], 'performance_testing_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }
        
        $results = $this->run_performance_test_suite();
        wp_send_json_success($results);
    }
    
    /**
     * AJAX handler for generating performance report
     */
    public function ajax_generate_performance_report() {
        if (!wp_verify_nonce($_POST['nonce'], 'performance_testing_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }
        
        $test_results = get_option('hph_performance_test_results', []);
        
        if (empty($test_results)) {
            wp_send_json_error('No test results available. Please run tests first.');
        }
        
        $report_html = $this->generate_html_report($test_results);
        wp_send_json_success($report_html);
    }
    
    /**
     * Generate HTML report
     */
    private function generate_html_report($test_results) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Performance Test Report - <?php echo get_bloginfo('name'); ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; }
                .header { border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
                .summary { display: flex; gap: 20px; margin: 20px 0; }
                .metric { background: #f5f5f5; padding: 15px; border-radius: 5px; text-align: center; }
                .score-good { color: #28a745; }
                .score-fair { color: #ffc107; }
                .score-poor { color: #dc3545; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
                th { background: #f5f5f5; }
                .recommendation { margin: 15px 0; padding: 15px; border-left: 4px solid #007cba; background: #f9f9f9; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Performance Test Report</h1>
                <p>Generated: <?php echo current_time('F j, Y g:i a'); ?></p>
                <p>Site: <?php echo home_url(); ?></p>
            </div>
            
            <div class="summary">
                <div class="metric">
                    <h3>Overall Score</h3>
                    <div class="score-<?php echo $test_results['overall_score'] >= 80 ? 'good' : ($test_results['overall_score'] >= 60 ? 'fair' : 'poor'); ?>">
                        <strong><?php echo $test_results['overall_score']; ?>%</strong>
                    </div>
                </div>
                <div class="metric">
                    <h3>Tests Run</h3>
                    <strong><?php echo count($test_results['tests']); ?></strong>
                </div>
                <div class="metric">
                    <h3>Issues Found</h3>
                    <strong>
                        <?php 
                        $total_issues = 0;
                        foreach ($test_results['tests'] as $test) {
                            $total_issues += count($test['issues'] ?? []);
                        }
                        echo $total_issues;
                        ?>
                    </strong>
                </div>
            </div>
            
            <h2>Test Results</h2>
            <table>
                <thead>
                    <tr>
                        <th>Test</th>
                        <th>Score</th>
                        <th>Load Time</th>
                        <th>Memory Usage</th>
                        <th>DB Queries</th>
                        <th>Issues</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($test_results['tests'] as $test_name => $result): ?>
                        <tr>
                            <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $test_name))); ?></td>
                            <td class="score-<?php echo $result['score'] >= 80 ? 'good' : ($result['score'] >= 60 ? 'fair' : 'poor'); ?>">
                                <?php echo $result['score']; ?>%
                            </td>
                            <td>
                                <?php 
                                $time_key = isset($result['metrics']['load_time']) ? 'load_time' : 'response_time';
                                echo isset($result['metrics'][$time_key]) ? sprintf('%.3fs', $result['metrics'][$time_key]) : 'N/A';
                                ?>
                            </td>
                            <td><?php echo isset($result['metrics']['memory_usage']) ? size_format($result['metrics']['memory_usage']) : 'N/A'; ?></td>
                            <td><?php echo isset($result['metrics']['query_count']) ? $result['metrics']['query_count'] : 'N/A'; ?></td>
                            <td><?php echo count($result['issues'] ?? []); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (!empty($test_results['recommendations'])): ?>
                <h2>Recommendations</h2>
                <?php foreach ($test_results['recommendations'] as $recommendation): ?>
                    <div class="recommendation">
                        <h3><?php echo esc_html($recommendation['title']); ?> (<?php echo esc_html($recommendation['priority']); ?> priority)</h3>
                        <p><?php echo esc_html($recommendation['description']); ?></p>
                        <h4>Recommended Actions:</h4>
                        <ul>
                            <?php foreach ($recommendation['actions'] as $action): ?>
                                <li><?php echo esc_html($action); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
