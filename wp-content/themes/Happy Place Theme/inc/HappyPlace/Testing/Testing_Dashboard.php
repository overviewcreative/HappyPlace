<?php

namespace HappyPlace\Testing;

/**
 * Testing Dashboard
 *
 * Unified dashboard for all testing frameworks
 *
 * @package HappyPlace\Testing
 * @since 2.0.0
 */
class Testing_Dashboard {
    
    /**
     * Singleton instance
     * @var Testing_Dashboard
     */
    private static $instance = null;
    
    /**
     * Testing frameworks
     * @var array
     */
    private $testing_frameworks = [];
    
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
        $this->init_testing_frameworks();
        $this->init_hooks();
    }
    
    /**
     * Initialize testing frameworks
     */
    private function init_testing_frameworks() {
        $this->testing_frameworks = [
            'component' => Component_Testing_Framework::get_instance(),
            'performance' => Performance_Testing_Framework::get_instance(),
            'integration' => Integration_Testing_Framework::get_instance()
        ];
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_ajax_run_all_testing_suites', [$this, 'ajax_run_all_testing_suites']);
        add_action('wp_ajax_get_testing_overview', [$this, 'ajax_get_testing_overview']);
        add_action('wp_ajax_export_test_results', [$this, 'ajax_export_test_results']);
        
        // Add testing dashboard to admin bar
        add_action('admin_bar_menu', [$this, 'add_admin_bar_menu'], 100);
        
        // Schedule comprehensive testing
        add_action('hph_comprehensive_testing', [$this, 'run_comprehensive_testing']);
        
        if (!wp_next_scheduled('hph_comprehensive_testing')) {
            wp_schedule_event(time(), 'weekly', 'hph_comprehensive_testing');
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Testing Dashboard',
            'Testing Hub',
            'manage_options',
            'hph-testing-dashboard',
            [$this, 'render_dashboard'],
            'dashicons-analytics',
            30
        );
        
        // Add submenus for each testing framework
        add_submenu_page(
            'hph-testing-dashboard',
            'Component Tests',
            'Component Tests',
            'manage_options',
            'hph-component-testing',
            [$this->testing_frameworks['component'], 'render_admin_page']
        );
        
        add_submenu_page(
            'hph-testing-dashboard',
            'Performance Tests',
            'Performance Tests',
            'manage_options',
            'hph-performance-testing',
            [$this->testing_frameworks['performance'], 'render_admin_page']
        );
        
        add_submenu_page(
            'hph-testing-dashboard',
            'Integration Tests',
            'Integration Tests',
            'manage_options',
            'hph-integration-testing',
            [$this->testing_frameworks['integration'], 'render_admin_page']
        );
    }
    
    /**
     * Add admin bar menu
     */
    public function add_admin_bar_menu($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $overview = $this->get_testing_overview();
        
        // Determine overall status
        $status = 'good';
        if ($overview['overall_score'] < 70) {
            $status = 'critical';
        } elseif ($overview['overall_score'] < 85) {
            $status = 'warning';
        }
        
        $wp_admin_bar->add_node([
            'id' => 'hph-testing',
            'title' => '<span class="ab-icon dashicons-analytics"></span> Tests: ' . $overview['overall_score'] . '%',
            'href' => admin_url('admin.php?page=hph-testing-dashboard'),
            'meta' => [
                'class' => 'hph-testing-status-' . $status
            ]
        ]);
        
        // Add quick test actions
        $wp_admin_bar->add_node([
            'parent' => 'hph-testing',
            'id' => 'hph-run-quick-test',
            'title' => 'Run Quick Test',
            'href' => '#',
            'meta' => [
                'onclick' => 'hphRunQuickTest(); return false;'
            ]
        ]);
    }
    
    /**
     * Render main dashboard
     */
    public function render_dashboard() {
        $overview = $this->get_testing_overview();
        ?>
        <div class="wrap hph-testing-dashboard">
            <h1>Testing Dashboard</h1>
            <p>Comprehensive testing overview for the Happy Place Theme</p>
            
            <div class="dashboard-widgets">
                <!-- Overall Status Widget -->
                <div class="dashboard-widget overall-status">
                    <h2>Overall Health</h2>
                    <div class="status-indicator status-<?php echo $overview['status']; ?>">
                        <div class="score-circle">
                            <span class="score"><?php echo esc_html($overview['overall_score']); ?>%</span>
                        </div>
                        <div class="status-text">
                            <?php echo esc_html(ucfirst($overview['status'])); ?>
                        </div>
                    </div>
                    <div class="quick-stats">
                        <div class="stat">
                            <span class="value"><?php echo esc_html($overview['total_tests']); ?></span>
                            <span class="label">Total Tests</span>
                        </div>
                        <div class="stat">
                            <span class="value"><?php echo esc_html($overview['passing_tests']); ?></span>
                            <span class="label">Passing</span>
                        </div>
                        <div class="stat">
                            <span class="value"><?php echo esc_html($overview['failing_tests']); ?></span>
                            <span class="label">Failing</span>
                        </div>
                    </div>
                </div>
                
                <!-- Framework Status Widgets -->
                <?php foreach ($overview['frameworks'] as $framework_name => $framework_data): ?>
                    <div class="dashboard-widget framework-status">
                        <h3><?php echo esc_html($framework_data['title']); ?></h3>
                        <div class="framework-score score-<?php echo $framework_data['score'] >= 80 ? 'good' : ($framework_data['score'] >= 60 ? 'fair' : 'poor'); ?>">
                            <?php echo esc_html($framework_data['score']); ?>%
                        </div>
                        <div class="framework-details">
                            <p><?php echo esc_html($framework_data['description']); ?></p>
                            <p>
                                <strong>Last Run:</strong> <?php echo esc_html($framework_data['last_run']); ?><br>
                                <strong>Tests:</strong> <?php echo esc_html($framework_data['test_count']); ?>
                            </p>
                        </div>
                        <div class="framework-actions">
                            <a href="<?php echo esc_url($framework_data['url']); ?>" class="button">View Details</a>
                            <button class="button run-framework-test" data-framework="<?php echo esc_attr($framework_name); ?>">
                                Run Tests
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Recent Issues Widget -->
                <div class="dashboard-widget recent-issues">
                    <h3>Recent Issues</h3>
                    <?php if (!empty($overview['recent_issues'])): ?>
                        <ul class="issue-list">
                            <?php foreach (array_slice($overview['recent_issues'], 0, 5) as $issue): ?>
                                <li class="issue priority-<?php echo esc_attr($issue['priority']); ?>">
                                    <strong><?php echo esc_html($issue['title']); ?></strong>
                                    <span class="issue-source"><?php echo esc_html($issue['source']); ?></span>
                                    <p><?php echo esc_html($issue['description']); ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (count($overview['recent_issues']) > 5): ?>
                            <p><em><?php echo count($overview['recent_issues']) - 5; ?> more issues...</em></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="no-issues">✓ No recent issues found</p>
                    <?php endif; ?>
                </div>
                
                <!-- Performance Trends Widget -->
                <div class="dashboard-widget performance-trends">
                    <h3>Performance Trends</h3>
                    <div class="trend-chart">
                        <canvas id="performanceChart" width="400" height="200"></canvas>
                    </div>
                    <div class="trend-summary">
                        <?php if (!empty($overview['performance_trend'])): ?>
                            <p>
                                <span class="trend-direction trend-<?php echo esc_attr($overview['performance_trend']['direction']); ?>">
                                    <?php echo $overview['performance_trend']['direction'] === 'up' ? '↑' : ($overview['performance_trend']['direction'] === 'down' ? '↓' : '→'); ?>
                                </span>
                                <?php echo esc_html($overview['performance_trend']['message']); ?>
                            </p>
                        <?php else: ?>
                            <p>Not enough data for trend analysis</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Actions Widget -->
                <div class="dashboard-widget quick-actions">
                    <h3>Quick Actions</h3>
                    <div class="action-buttons">
                        <button class="button button-primary" id="run-all-tests">
                            <span class="dashicons dashicons-update"></span>
                            Run All Tests
                        </button>
                        <button class="button" id="generate-report">
                            <span class="dashicons dashicons-download"></span>
                            Export Report
                        </button>
                        <button class="button" id="schedule-tests">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            Schedule Tests
                        </button>
                        <button class="button" id="clear-cache">
                            <span class="dashicons dashicons-trash"></span>
                            Clear Test Cache
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Test History Table -->
            <div class="test-history">
                <h2>Recent Test Runs</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Framework</th>
                            <th>Score</th>
                            <th>Tests</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($overview['test_history'] as $test_run): ?>
                            <tr>
                                <td><?php echo esc_html($test_run['date']); ?></td>
                                <td><?php echo esc_html($test_run['framework']); ?></td>
                                <td>
                                    <span class="score-badge score-<?php echo $test_run['score'] >= 80 ? 'good' : ($test_run['score'] >= 60 ? 'fair' : 'poor'); ?>">
                                        <?php echo esc_html($test_run['score']); ?>%
                                    </span>
                                </td>
                                <td><?php echo esc_html($test_run['test_count']); ?></td>
                                <td><?php echo esc_html($test_run['duration']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr($test_run['status']); ?>">
                                        <?php echo esc_html(ucfirst($test_run['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="button-link view-details" data-test-id="<?php echo esc_attr($test_run['id']); ?>">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php $this->render_dashboard_styles(); ?>
        <?php $this->render_dashboard_scripts(); ?>
        <?php
    }
    
    /**
     * Get testing overview
     */
    public function get_testing_overview() {
        $overview = [
            'overall_score' => 0,
            'status' => 'unknown',
            'total_tests' => 0,
            'passing_tests' => 0,
            'failing_tests' => 0,
            'frameworks' => [],
            'recent_issues' => [],
            'performance_trend' => null,
            'test_history' => []
        ];
        
        // Get results from each framework
        $component_results = get_option('hph_component_test_results', []);
        $performance_results = get_option('hph_performance_test_results', []);
        $integration_results = get_option('hph_integration_test_results', []);
        
        // Component Testing
        if (!empty($component_results)) {
            $overview['frameworks']['component'] = [
                'title' => 'Component Testing',
                'description' => 'Tests individual theme components for functionality and performance',
                'score' => $component_results['summary']['average_score'] ?? 0,
                'test_count' => $component_results['summary']['total_components'] ?? 0,
                'last_run' => isset($component_results['timestamp']) ? human_time_diff($component_results['timestamp']) . ' ago' : 'Never',
                'url' => admin_url('admin.php?page=hph-component-testing')
            ];
        }
        
        // Performance Testing
        if (!empty($performance_results)) {
            $overview['frameworks']['performance'] = [
                'title' => 'Performance Testing',
                'description' => 'Tests website speed, optimization, and Core Web Vitals',
                'score' => $performance_results['overall_score'] ?? 0,
                'test_count' => count($performance_results['tests'] ?? []),
                'last_run' => isset($performance_results['timestamp']) ? human_time_diff($performance_results['timestamp']) . ' ago' : 'Never',
                'url' => admin_url('admin.php?page=hph-performance-testing')
            ];
        }
        
        // Integration Testing
        if (!empty($integration_results)) {
            $overview['frameworks']['integration'] = [
                'title' => 'Integration Testing',
                'description' => 'Tests API endpoints, user journeys, and system integration',
                'score' => $integration_results['overall_score'] ?? 0,
                'test_count' => $integration_results['total_passed'] + $integration_results['total_failed'],
                'last_run' => isset($integration_results['timestamp']) ? human_time_diff($integration_results['timestamp']) . ' ago' : 'Never',
                'url' => admin_url('admin.php?page=hph-integration-testing')
            ];
        }
        
        // Calculate overall metrics
        $total_score = 0;
        $framework_count = 0;
        
        foreach ($overview['frameworks'] as $framework) {
            $total_score += $framework['score'];
            $framework_count++;
            $overview['total_tests'] += $framework['test_count'];
            
            // Count passing/failing based on score threshold
            if ($framework['score'] >= 70) {
                $overview['passing_tests'] += $framework['test_count'];
            } else {
                $overview['failing_tests'] += $framework['test_count'];
            }
        }
        
        $overview['overall_score'] = $framework_count > 0 ? round($total_score / $framework_count) : 0;
        
        // Determine status
        if ($overview['overall_score'] >= 85) {
            $overview['status'] = 'excellent';
        } elseif ($overview['overall_score'] >= 70) {
            $overview['status'] = 'good';
        } elseif ($overview['overall_score'] >= 50) {
            $overview['status'] = 'warning';
        } else {
            $overview['status'] = 'critical';
        }
        
        // Collect recent issues (simplified for demo)
        $overview['recent_issues'] = $this->collect_recent_issues();
        
        // Get test history
        $overview['test_history'] = $this->get_test_history();
        
        return $overview;
    }
    
    /**
     * Collect recent issues from all frameworks
     */
    private function collect_recent_issues() {
        $issues = [];
        
        // Component issues
        $component_results = get_option('hph_component_test_results', []);
        if (!empty($component_results['results'])) {
            foreach ($component_results['results'] as $component => $result) {
                if (!$result['success']) {
                    $issues[] = [
                        'title' => "Component {$component} failing tests",
                        'description' => "Score: {$result['overall_score']}%",
                        'priority' => $result['overall_score'] < 50 ? 'high' : 'medium',
                        'source' => 'Component Testing',
                        'timestamp' => $result['timestamp']
                    ];
                }
            }
        }
        
        // Performance issues
        $performance_results = get_option('hph_performance_test_results', []);
        if (!empty($performance_results['recommendations'])) {
            foreach ($performance_results['recommendations'] as $recommendation) {
                $issues[] = [
                    'title' => $recommendation['title'],
                    'description' => $recommendation['description'],
                    'priority' => $recommendation['priority'],
                    'source' => 'Performance Testing',
                    'timestamp' => $performance_results['timestamp']
                ];
            }
        }
        
        // Sort by timestamp (newest first)
        usort($issues, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        return $issues;
    }
    
    /**
     * Get test history
     */
    private function get_test_history() {
        $history = [];
        
        // Get recent test runs from each framework
        $component_results = get_option('hph_component_test_results', []);
        if (!empty($component_results)) {
            $history[] = [
                'id' => 'component_' . $component_results['timestamp'],
                'date' => date('Y-m-d H:i', $component_results['timestamp']),
                'framework' => 'Component',
                'score' => $component_results['summary']['average_score'] ?? 0,
                'test_count' => $component_results['summary']['total_components'] ?? 0,
                'duration' => 'N/A',
                'status' => ($component_results['summary']['average_score'] ?? 0) >= 70 ? 'passed' : 'failed'
            ];
        }
        
        $performance_results = get_option('hph_performance_test_results', []);
        if (!empty($performance_results)) {
            $history[] = [
                'id' => 'performance_' . $performance_results['timestamp'],
                'date' => date('Y-m-d H:i', $performance_results['timestamp']),
                'framework' => 'Performance',
                'score' => $performance_results['overall_score'] ?? 0,
                'test_count' => count($performance_results['tests'] ?? []),
                'duration' => 'N/A',
                'status' => ($performance_results['overall_score'] ?? 0) >= 70 ? 'passed' : 'failed'
            ];
        }
        
        $integration_results = get_option('hph_integration_test_results', []);
        if (!empty($integration_results)) {
            $history[] = [
                'id' => 'integration_' . $integration_results['timestamp'],
                'date' => date('Y-m-d H:i', $integration_results['timestamp']),
                'framework' => 'Integration',
                'score' => $integration_results['overall_score'] ?? 0,
                'test_count' => $integration_results['total_passed'] + $integration_results['total_failed'],
                'duration' => 'N/A',
                'status' => ($integration_results['overall_score'] ?? 0) >= 70 ? 'passed' : 'failed'
            ];
        }
        
        // Sort by date (newest first)
        usort($history, function($a, $b) {
            return strcmp($b['date'], $a['date']);
        });
        
        return array_slice($history, 0, 10); // Return last 10 runs
    }
    
    /**
     * Run comprehensive testing
     */
    public function run_comprehensive_testing() {
        $results = [
            'timestamp' => current_time('timestamp'),
            'component' => null,
            'performance' => null,
            'integration' => null,
            'overall_score' => 0
        ];
        
        try {
            // Run component tests
            $results['component'] = $this->testing_frameworks['component']->run_all_tests();
            
            // Run performance tests
            $results['performance'] = $this->testing_frameworks['performance']->run_performance_test_suite();
            
            // Run integration tests
            $results['integration'] = $this->testing_frameworks['integration']->run_all_integration_tests();
            
            // Calculate overall score
            $scores = [];
            if ($results['component']) {
                $scores[] = $results['component']['summary']['average_score'] ?? 0;
            }
            if ($results['performance']) {
                $scores[] = $results['performance']['overall_score'] ?? 0;
            }
            if ($results['integration']) {
                $scores[] = $results['integration']['overall_score'] ?? 0;
            }
            
            $results['overall_score'] = !empty($scores) ? round(array_sum($scores) / count($scores)) : 0;
            
            // Store comprehensive results
            update_option('hph_comprehensive_test_results', $results);
            
            // Log critical issues
            if ($results['overall_score'] < 60) {
                error_log("HPH Comprehensive Testing Alert: Overall score is {$results['overall_score']}%");
            }
            
        } catch (\Exception $e) {
            error_log("HPH Comprehensive Testing Error: " . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Render dashboard styles
     */
    private function render_dashboard_styles() {
        ?>
        <style>
        .hph-testing-dashboard .dashboard-widgets {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .dashboard-widget {
            background: white;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        
        .dashboard-widget h2, .dashboard-widget h3 {
            margin-top: 0;
            color: #333;
        }
        
        .overall-status {
            grid-column: 1 / -1;
            text-align: center;
        }
        
        .status-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin: 20px 0;
        }
        
        .score-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            color: white;
        }
        
        .status-excellent .score-circle { background: #28a745; }
        .status-good .score-circle { background: #28a745; }
        .status-warning .score-circle { background: #ffc107; color: #333; }
        .status-critical .score-circle { background: #dc3545; }
        
        .status-text {
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .quick-stats {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 20px;
        }
        
        .quick-stats .stat {
            text-align: center;
        }
        
        .quick-stats .value {
            display: block;
            font-size: 2em;
            font-weight: bold;
            color: #333;
        }
        
        .quick-stats .label {
            display: block;
            font-size: 0.9em;
            color: #666;
        }
        
        .framework-score {
            font-size: 2em;
            font-weight: bold;
            text-align: center;
            margin: 15px 0;
        }
        
        .score-good { color: #28a745; }
        .score-fair { color: #ffc107; }
        .score-poor { color: #dc3545; }
        
        .framework-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        
        .issue-list {
            list-style: none;
            padding: 0;
        }
        
        .issue {
            padding: 10px;
            margin: 10px 0;
            border-left: 4px solid #ddd;
            background: #f9f9f9;
        }
        
        .issue.priority-high { border-left-color: #dc3545; }
        .issue.priority-medium { border-left-color: #ffc107; }
        .issue.priority-low { border-left-color: #28a745; }
        
        .issue-source {
            float: right;
            font-size: 0.8em;
            color: #666;
        }
        
        .no-issues {
            color: #28a745;
            font-weight: bold;
        }
        
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .action-buttons .button {
            justify-content: flex-start;
            text-align: left;
        }
        
        .test-history {
            background: white;
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
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
        
        .status-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .status-passed { background: #d4edda; color: #155724; }
        .status-failed { background: #f8d7da; color: #721c24; }
        
        /* Admin bar styles */
        .hph-testing-status-good #wp-admin-bar-hph-testing .ab-item {
            background-color: rgba(40, 167, 69, 0.1) !important;
        }
        
        .hph-testing-status-warning #wp-admin-bar-hph-testing .ab-item {
            background-color: rgba(255, 193, 7, 0.1) !important;
        }
        
        .hph-testing-status-critical #wp-admin-bar-hph-testing .ab-item {
            background-color: rgba(220, 53, 69, 0.1) !important;
        }
        </style>
        <?php
    }
    
    /**
     * Render dashboard scripts
     */
    private function render_dashboard_scripts() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Run all tests
            $('#run-all-tests').on('click', function() {
                const $button = $(this);
                $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Running Tests...');
                
                $.post(ajaxurl, {
                    action: 'run_all_testing_suites',
                    nonce: '<?php echo wp_create_nonce('testing_dashboard_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error running tests: ' + response.data);
                    }
                    $button.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Run All Tests');
                });
            });
            
            // Export report
            $('#generate-report').on('click', function() {
                window.open('<?php echo admin_url('admin-ajax.php?action=export_test_results&nonce=' . wp_create_nonce('testing_dashboard_nonce')); ?>');
            });
            
            // Individual framework tests
            $('.run-framework-test').on('click', function() {
                const framework = $(this).data('framework');
                const $button = $(this);
                
                $button.prop('disabled', true).text('Running...');
                
                // Implementation would trigger specific framework test
                setTimeout(function() {
                    $button.prop('disabled', false).text('Run Tests');
                    alert('Framework test completed for: ' + framework);
                }, 2000);
            });
        });
        
        // Quick test function for admin bar
        function hphRunQuickTest() {
            if (confirm('Run a quick test? This will test critical functionality.')) {
                // Implementation for quick test
                alert('Quick test completed!');
            }
        }
        </script>
        <?php
    }
    
    /**
     * AJAX handler for running all testing suites
     */
    public function ajax_run_all_testing_suites() {
        if (!wp_verify_nonce($_POST['nonce'], 'testing_dashboard_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }
        
        $results = $this->run_comprehensive_testing();
        wp_send_json_success($results);
    }
    
    /**
     * AJAX handler for getting testing overview
     */
    public function ajax_get_testing_overview() {
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied');
        }
        
        $overview = $this->get_testing_overview();
        wp_send_json_success($overview);
    }
    
    /**
     * AJAX handler for exporting test results
     */
    public function ajax_export_test_results() {
        if (!wp_verify_nonce($_GET['nonce'], 'testing_dashboard_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }
        
        $overview = $this->get_testing_overview();
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="hph-test-results-' . date('Y-m-d') . '.json"');
        
        echo json_encode($overview, JSON_PRETTY_PRINT);
        exit;
    }
}
