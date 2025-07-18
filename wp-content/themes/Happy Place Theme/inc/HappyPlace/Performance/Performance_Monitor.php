<?php

namespace HappyPlace\Performance;

/**
 * Performance Monitor
 *
 * Tracks, analyzes, and optimizes website performance metrics
 *
 * @package HappyPlace\Performance
 * @since 2.0.0
 */
class Performance_Monitor {
    
    /**
     * Singleton instance
     * @var Performance_Monitor
     */
    private static $instance = null;
    
    /**
     * Performance metrics
     * @var array
     */
    private $metrics = [];
    
    /**
     * Start time for current request
     * @var float
     */
    private $start_time;
    
    /**
     * Memory usage at start
     * @var int
     */
    private $start_memory;
    
    /**
     * Database queries count at start
     * @var int
     */
    private $start_queries;
    
    /**
     * Performance thresholds
     * @var array
     */
    private $thresholds = [
        'page_load_time' => 3.0,      // seconds
        'database_queries' => 30,      // count
        'memory_usage' => 64 * 1024 * 1024, // 64MB
        'largest_contentful_paint' => 2.5,  // seconds
        'first_input_delay' => 0.1,          // seconds
        'cumulative_layout_shift' => 0.1     // score
    ];
    
    /**
     * Configuration
     * @var array
     */
    private $config = [
        'enable_monitoring' => true,
        'track_user_interactions' => true,
        'sample_rate' => 10, // Percentage of requests to track
        'store_detailed_metrics' => true,
        'enable_alerts' => true,
        'alert_threshold_multiplier' => 1.5
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
        $this->load_config();
        $this->init_monitoring();
        $this->init_hooks();
    }
    
    /**
     * Load configuration
     */
    private function load_config() {
        $saved_config = get_option('hph_performance_monitor_config', []);
        $this->config = array_merge($this->config, $saved_config);
    }
    
    /**
     * Initialize performance monitoring
     */
    private function init_monitoring() {
        if (!$this->config['enable_monitoring']) {
            return;
        }
        
        // Track only sample percentage of requests
        if (rand(1, 100) > $this->config['sample_rate']) {
            return;
        }
        
        $this->start_time = microtime(true);
        $this->start_memory = memory_get_usage(true);
        $this->start_queries = get_num_queries();
        
        // Track specific metrics
        $this->metrics['request_start'] = $this->start_time;
        $this->metrics['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $this->metrics['page_type'] = $this->get_page_type();
        $this->metrics['is_mobile'] = wp_is_mobile();
        $this->metrics['is_admin'] = is_admin();
        $this->metrics['request_uri'] = $_SERVER['REQUEST_URI'] ?? '';
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Core performance tracking
        add_action('wp_head', [$this, 'inject_performance_script'], 1);
        add_action('wp_footer', [$this, 'track_server_metrics'], 999);
        add_action('wp_footer', [$this, 'inject_client_tracking'], 999);
        
        // Database query tracking
        add_filter('query', [$this, 'track_slow_queries']);
        
        // Admin interface
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_ajax_get_performance_data', [$this, 'ajax_get_performance_data']);
        add_action('wp_ajax_clear_performance_data', [$this, 'ajax_clear_performance_data']);
        
        // AJAX tracking for user interactions
        add_action('wp_ajax_track_user_metric', [$this, 'ajax_track_user_metric']);
        add_action('wp_ajax_nopriv_track_user_metric', [$this, 'ajax_track_user_metric']);
        
        // Scheduled analysis
        add_action('hph_performance_analysis', [$this, 'run_performance_analysis']);
        
        // Hook into WordPress performance events
        add_action('wp_loaded', [$this, 'track_wp_loaded']);
        add_action('template_redirect', [$this, 'track_template_redirect']);
    }
    
    /**
     * Inject performance tracking script
     */
    public function inject_performance_script() {
        if (!$this->config['enable_monitoring'] || is_admin()) {
            return;
        }
        
        ?>
        <script>
        (function() {
            // Track Core Web Vitals
            window.hphPerformance = {
                metrics: {},
                startTime: performance.now(),
                
                // Track Largest Contentful Paint
                trackLCP: function() {
                    if ('PerformanceObserver' in window) {
                        new PerformanceObserver((entryList) => {
                            const entries = entryList.getEntries();
                            const lastEntry = entries[entries.length - 1];
                            this.metrics.lcp = lastEntry.startTime;
                        }).observe({entryTypes: ['largest-contentful-paint']});
                    }
                },
                
                // Track First Input Delay
                trackFID: function() {
                    if ('PerformanceObserver' in window) {
                        new PerformanceObserver((entryList) => {
                            for (const entry of entryList.getEntries()) {
                                this.metrics.fid = entry.processingStart - entry.startTime;
                                break;
                            }
                        }).observe({entryTypes: ['first-input']});
                    }
                },
                
                // Track Cumulative Layout Shift
                trackCLS: function() {
                    if ('PerformanceObserver' in window) {
                        let clsValue = 0;
                        new PerformanceObserver((entryList) => {
                            for (const entry of entryList.getEntries()) {
                                if (!entry.hadRecentInput) {
                                    clsValue += entry.value;
                                }
                            }
                            this.metrics.cls = clsValue;
                        }).observe({entryTypes: ['layout-shift']});
                    }
                },
                
                // Track Time to First Byte
                trackTTFB: function() {
                    if ('navigation' in performance) {
                        const navTiming = performance.getEntriesByType('navigation')[0];
                        this.metrics.ttfb = navTiming.responseStart - navTiming.requestStart;
                    }
                },
                
                // Track DOM Content Loaded
                trackDOMReady: function() {
                    document.addEventListener('DOMContentLoaded', () => {
                        this.metrics.domReady = performance.now() - this.startTime;
                    });
                },
                
                // Track window load
                trackWindowLoad: function() {
                    window.addEventListener('load', () => {
                        this.metrics.windowLoad = performance.now() - this.startTime;
                        this.sendMetrics();
                    });
                },
                
                // Send metrics to server
                sendMetrics: function() {
                    <?php if ($this->config['track_user_interactions']): ?>
                    setTimeout(() => {
                        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                action: 'track_user_metric',
                                metrics: JSON.stringify(this.metrics),
                                nonce: '<?php echo wp_create_nonce('performance_tracking'); ?>'
                            })
                        }).catch(() => {}); // Fail silently
                    }, 1000);
                    <?php endif; ?>
                }
            };
            
            // Initialize tracking
            window.hphPerformance.trackLCP();
            window.hphPerformance.trackFID();
            window.hphPerformance.trackCLS();
            window.hphPerformance.trackTTFB();
            window.hphPerformance.trackDOMReady();
            window.hphPerformance.trackWindowLoad();
        })();
        </script>
        <?php
    }
    
    /**
     * Track server-side metrics
     */
    public function track_server_metrics() {
        if (!$this->config['enable_monitoring'] || is_admin()) {
            return;
        }
        
        $end_time = microtime(true);
        $end_memory = memory_get_usage(true);
        $end_queries = get_num_queries();
        
        $this->metrics['server_response_time'] = $end_time - $this->start_time;
        $this->metrics['memory_usage'] = $end_memory;
        $this->metrics['memory_delta'] = $end_memory - $this->start_memory;
        $this->metrics['database_queries'] = $end_queries - $this->start_queries;
        $this->metrics['peak_memory'] = memory_get_peak_usage(true);
        
        // Check for performance issues
        $this->check_performance_thresholds();
        
        // Store metrics
        $this->store_metrics();
    }
    
    /**
     * Inject client-side tracking
     */
    public function inject_client_tracking() {
        if (!$this->config['enable_monitoring'] || is_admin()) {
            return;
        }
        
        ?>
        <script>
        // Additional client-side performance tracking
        (function() {
            // Track resource loading times
            window.addEventListener('load', function() {
                if ('performance' in window && 'getEntriesByType' in performance) {
                    const resources = performance.getEntriesByType('resource');
                    let slowResources = 0;
                    let totalResourceTime = 0;
                    
                    resources.forEach(resource => {
                        const loadTime = resource.responseEnd - resource.startTime;
                        totalResourceTime += loadTime;
                        
                        if (loadTime > 1000) { // Slow resource threshold: 1 second
                            slowResources++;
                        }
                    });
                    
                    window.hphPerformance.metrics.resourceCount = resources.length;
                    window.hphPerformance.metrics.slowResources = slowResources;
                    window.hphPerformance.metrics.averageResourceTime = resources.length > 0 ? 
                        totalResourceTime / resources.length : 0;
                }
            });
            
            // Track JavaScript errors
            window.addEventListener('error', function(e) {
                // Count JS errors as performance impact
                if (!window.hphPerformance.metrics.jsErrors) {
                    window.hphPerformance.metrics.jsErrors = 0;
                }
                window.hphPerformance.metrics.jsErrors++;
            });
        })();
        </script>
        <?php
    }
    
    /**
     * Track slow database queries
     */
    public function track_slow_queries($query) {
        static $query_times = [];
        static $query_count = 0;
        
        $start_time = microtime(true);
        
        // Execute the query and measure time
        add_filter('query', function($result) use ($start_time, $query, &$query_times, &$query_count) {
            $execution_time = microtime(true) - $start_time;
            $query_count++;
            
            // Track slow queries (>1 second)
            if ($execution_time > 1.0) {
                $query_times[] = [
                    'query' => substr($query, 0, 200) . '...',
                    'time' => $execution_time,
                    'timestamp' => current_time('timestamp')
                ];
                
                // Store slow queries for analysis
                $slow_queries = get_option('hph_slow_queries', []);
                $slow_queries[] = [
                    'query' => $query,
                    'time' => $execution_time,
                    'page' => $_SERVER['REQUEST_URI'] ?? '',
                    'timestamp' => current_time('timestamp')
                ];
                
                // Keep only last 50 slow queries
                if (count($slow_queries) > 50) {
                    $slow_queries = array_slice($slow_queries, -50);
                }
                
                update_option('hph_slow_queries', $slow_queries);
            }
            
            return $result;
        }, 10, 1);
        
        return $query;
    }
    
    /**
     * Check performance against thresholds
     */
    private function check_performance_thresholds() {
        $issues = [];
        
        foreach ($this->thresholds as $metric => $threshold) {
            if (isset($this->metrics[$metric])) {
                $value = $this->metrics[$metric];
                $alert_threshold = $threshold * $this->config['alert_threshold_multiplier'];
                
                if ($value > $alert_threshold) {
                    $issues[] = [
                        'metric' => $metric,
                        'value' => $value,
                        'threshold' => $threshold,
                        'severity' => $value > ($threshold * 2) ? 'critical' : 'warning'
                    ];
                }
            }
        }
        
        if (!empty($issues) && $this->config['enable_alerts']) {
            $this->log_performance_issues($issues);
        }
        
        return $issues;
    }
    
    /**
     * Log performance issues
     */
    private function log_performance_issues($issues) {
        $log_entry = [
            'timestamp' => current_time('timestamp'),
            'page' => $_SERVER['REQUEST_URI'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'issues' => $issues,
            'all_metrics' => $this->metrics
        ];
        
        $performance_log = get_option('hph_performance_issues', []);
        $performance_log[] = $log_entry;
        
        // Keep only last 100 entries
        if (count($performance_log) > 100) {
            $performance_log = array_slice($performance_log, -100);
        }
        
        update_option('hph_performance_issues', $performance_log);
        
        // Log critical issues
        foreach ($issues as $issue) {
            if ($issue['severity'] === 'critical') {
                error_log("HPH Performance Critical Issue: {$issue['metric']} = {$issue['value']} (threshold: {$issue['threshold']})");
            }
        }
    }
    
    /**
     * Store performance metrics
     */
    private function store_metrics() {
        if (!$this->config['store_detailed_metrics']) {
            return;
        }
        
        $stored_metrics = get_option('hph_performance_metrics', []);
        
        $metric_entry = array_merge($this->metrics, [
            'timestamp' => current_time('timestamp'),
            'date' => current_time('Y-m-d'),
            'hour' => current_time('H')
        ]);
        
        $stored_metrics[] = $metric_entry;
        
        // Keep only last 1000 entries
        if (count($stored_metrics) > 1000) {
            $stored_metrics = array_slice($stored_metrics, -1000);
        }
        
        update_option('hph_performance_metrics', $stored_metrics);
        
        // Update aggregated statistics
        $this->update_aggregated_stats($metric_entry);
    }
    
    /**
     * Update aggregated performance statistics
     */
    private function update_aggregated_stats($metrics) {
        $stats = get_option('hph_performance_stats', [
            'total_requests' => 0,
            'avg_response_time' => 0,
            'avg_memory_usage' => 0,
            'avg_queries' => 0,
            'by_page_type' => [],
            'by_hour' => [],
            'by_date' => []
        ]);
        
        $stats['total_requests']++;
        
        // Update averages
        $stats['avg_response_time'] = $this->calculate_running_average(
            $stats['avg_response_time'],
            $metrics['server_response_time'],
            $stats['total_requests']
        );
        
        $stats['avg_memory_usage'] = $this->calculate_running_average(
            $stats['avg_memory_usage'],
            $metrics['memory_usage'],
            $stats['total_requests']
        );
        
        $stats['avg_queries'] = $this->calculate_running_average(
            $stats['avg_queries'],
            $metrics['database_queries'],
            $stats['total_requests']
        );
        
        // Update by page type
        $page_type = $metrics['page_type'];
        if (!isset($stats['by_page_type'][$page_type])) {
            $stats['by_page_type'][$page_type] = [
                'count' => 0,
                'avg_response_time' => 0
            ];
        }
        
        $stats['by_page_type'][$page_type]['count']++;
        $stats['by_page_type'][$page_type]['avg_response_time'] = $this->calculate_running_average(
            $stats['by_page_type'][$page_type]['avg_response_time'],
            $metrics['server_response_time'],
            $stats['by_page_type'][$page_type]['count']
        );
        
        // Update by hour
        $hour = $metrics['hour'];
        if (!isset($stats['by_hour'][$hour])) {
            $stats['by_hour'][$hour] = 0;
        }
        $stats['by_hour'][$hour]++;
        
        // Update by date
        $date = $metrics['date'];
        if (!isset($stats['by_date'][$date])) {
            $stats['by_date'][$date] = 0;
        }
        $stats['by_date'][$date]++;
        
        update_option('hph_performance_stats', $stats);
    }
    
    /**
     * Calculate running average
     */
    private function calculate_running_average($current_avg, $new_value, $count) {
        return (($current_avg * ($count - 1)) + $new_value) / $count;
    }
    
    /**
     * Get current page type
     */
    private function get_page_type() {
        if (is_home() || is_front_page()) {
            return 'home';
        } elseif (is_singular('listing')) {
            return 'listing';
        } elseif (is_post_type_archive('listing')) {
            return 'listings';
        } elseif (is_singular('agent')) {
            return 'agent';
        } elseif (is_search()) {
            return 'search';
        } elseif (is_404()) {
            return '404';
        } elseif (is_page()) {
            return 'page';
        } elseif (is_single()) {
            return 'post';
        } else {
            return 'other';
        }
    }
    
    /**
     * Track WordPress loaded event
     */
    public function track_wp_loaded() {
        $this->metrics['wp_loaded_time'] = microtime(true) - $this->start_time;
    }
    
    /**
     * Track template redirect
     */
    public function track_template_redirect() {
        $this->metrics['template_redirect_time'] = microtime(true) - $this->start_time;
    }
    
    /**
     * Run scheduled performance analysis
     */
    public function run_performance_analysis() {
        $stats = get_option('hph_performance_stats', []);
        $issues = get_option('hph_performance_issues', []);
        
        // Analyze recent performance trends
        $analysis = [
            'timestamp' => current_time('timestamp'),
            'total_requests_analyzed' => $stats['total_requests'] ?? 0,
            'performance_score' => $this->calculate_performance_score($stats),
            'top_issues' => $this->get_top_performance_issues($issues),
            'recommendations' => $this->generate_recommendations($stats, $issues)
        ];
        
        update_option('hph_performance_analysis', $analysis);
        
        // Schedule next analysis
        wp_schedule_single_event(time() + (24 * 3600), 'hph_performance_analysis');
    }
    
    /**
     * Calculate overall performance score
     */
    private function calculate_performance_score($stats) {
        if (empty($stats)) {
            return 100;
        }
        
        $score = 100;
        
        // Deduct points for slow response times
        if (isset($stats['avg_response_time'])) {
            if ($stats['avg_response_time'] > $this->thresholds['page_load_time']) {
                $score -= 20;
            } elseif ($stats['avg_response_time'] > $this->thresholds['page_load_time'] * 0.7) {
                $score -= 10;
            }
        }
        
        // Deduct points for high memory usage
        if (isset($stats['avg_memory_usage'])) {
            if ($stats['avg_memory_usage'] > $this->thresholds['memory_usage']) {
                $score -= 15;
            }
        }
        
        // Deduct points for excessive database queries
        if (isset($stats['avg_queries'])) {
            if ($stats['avg_queries'] > $this->thresholds['database_queries']) {
                $score -= 15;
            }
        }
        
        return max(0, $score);
    }
    
    /**
     * Get top performance issues
     */
    private function get_top_performance_issues($issues) {
        if (empty($issues)) {
            return [];
        }
        
        // Count issue types
        $issue_counts = [];
        
        foreach ($issues as $log_entry) {
            foreach ($log_entry['issues'] as $issue) {
                $metric = $issue['metric'];
                if (!isset($issue_counts[$metric])) {
                    $issue_counts[$metric] = 0;
                }
                $issue_counts[$metric]++;
            }
        }
        
        arsort($issue_counts);
        
        return array_slice($issue_counts, 0, 5, true);
    }
    
    /**
     * Generate performance recommendations
     */
    private function generate_recommendations($stats, $issues) {
        $recommendations = [];
        
        if (isset($stats['avg_response_time']) && $stats['avg_response_time'] > $this->thresholds['page_load_time']) {
            $recommendations[] = 'Enable caching to reduce page load times';
            $recommendations[] = 'Optimize images and enable WebP conversion';
            $recommendations[] = 'Consider using a Content Delivery Network (CDN)';
        }
        
        if (isset($stats['avg_queries']) && $stats['avg_queries'] > $this->thresholds['database_queries']) {
            $recommendations[] = 'Optimize database queries and add proper indexes';
            $recommendations[] = 'Enable object caching (Redis/Memcached)';
            $recommendations[] = 'Review and optimize plugin database usage';
        }
        
        if (isset($stats['avg_memory_usage']) && $stats['avg_memory_usage'] > $this->thresholds['memory_usage']) {
            $recommendations[] = 'Increase PHP memory limit or optimize memory usage';
            $recommendations[] = 'Review plugins for memory leaks';
            $recommendations[] = 'Enable PHP OPcache for better performance';
        }
        
        $slow_queries = get_option('hph_slow_queries', []);
        if (count($slow_queries) > 10) {
            $recommendations[] = 'Optimize slow database queries';
            $recommendations[] = 'Add database indexes for frequently queried fields';
        }
        
        return $recommendations;
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_management_page(
            'Performance Monitor',
            'Performance',
            'manage_options',
            'hph-performance-monitor',
            [$this, 'render_admin_page']
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $stats = get_option('hph_performance_stats', []);
        $analysis = get_option('hph_performance_analysis', []);
        $slow_queries = get_option('hph_slow_queries', []);
        $issues = get_option('hph_performance_issues', []);
        
        ?>
        <div class="wrap">
            <h1>Performance Monitor</h1>
            
            <div class="performance-dashboard">
                <?php if (!empty($analysis)): ?>
                    <div class="dashboard-section">
                        <h2>Performance Score</h2>
                        <div class="performance-score">
                            <div class="score-circle">
                                <span class="score"><?php echo esc_html($analysis['performance_score']); ?></span>
                                <span class="label">Score</span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($stats)): ?>
                    <div class="dashboard-section">
                        <h2>Key Metrics</h2>
                        <div class="metrics-grid">
                            <div class="metric">
                                <h3>Average Response Time</h3>
                                <span class="value"><?php echo esc_html(number_format($stats['avg_response_time'], 3)); ?>s</span>
                            </div>
                            <div class="metric">
                                <h3>Average Memory Usage</h3>
                                <span class="value"><?php echo esc_html(size_format($stats['avg_memory_usage'])); ?></span>
                            </div>
                            <div class="metric">
                                <h3>Average Database Queries</h3>
                                <span class="value"><?php echo esc_html(number_format($stats['avg_queries'])); ?></span>
                            </div>
                            <div class="metric">
                                <h3>Total Requests Tracked</h3>
                                <span class="value"><?php echo esc_html(number_format($stats['total_requests'])); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($analysis['recommendations'])): ?>
                    <div class="dashboard-section">
                        <h2>Recommendations</h2>
                        <ul class="recommendations">
                            <?php foreach ($analysis['recommendations'] as $recommendation): ?>
                                <li><?php echo esc_html($recommendation); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($slow_queries)): ?>
                    <div class="dashboard-section">
                        <h2>Recent Slow Queries</h2>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Query</th>
                                    <th>Time</th>
                                    <th>Page</th>
                                    <th>When</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($slow_queries, -10) as $query): ?>
                                    <tr>
                                        <td><code><?php echo esc_html(substr($query['query'], 0, 100)); ?>...</code></td>
                                        <td><?php echo esc_html(number_format($query['time'], 3)); ?>s</td>
                                        <td><?php echo esc_html($query['page']); ?></td>
                                        <td><?php echo esc_html(human_time_diff($query['timestamp'])); ?> ago</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
                <div class="dashboard-section">
                    <h2>Actions</h2>
                    <p>
                        <button class="button button-primary" id="refresh-analysis">
                            Refresh Analysis
                        </button>
                        <button class="button" id="clear-performance-data">
                            Clear All Data
                        </button>
                    </p>
                </div>
            </div>
        </div>
        
        <style>
        .performance-score {
            text-align: center;
            margin: 20px 0;
        }
        .score-circle {
            display: inline-block;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            line-height: 120px;
            position: relative;
        }
        .score-circle .score {
            display: block;
            font-size: 2em;
            font-weight: bold;
            line-height: 1;
            margin-top: 35px;
        }
        .score-circle .label {
            display: block;
            font-size: 0.8em;
            line-height: 1;
        }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .metric {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .metric h3 {
            margin: 0 0 10px 0;
            font-size: 0.9em;
            color: #666;
        }
        .metric .value {
            font-size: 1.5em;
            font-weight: bold;
            color: #333;
        }
        .recommendations {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 15px 30px;
        }
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
            $('#refresh-analysis').on('click', function() {
                $(this).prop('disabled', true).text('Refreshing...');
                
                $.post(ajaxurl, {
                    action: 'get_performance_data',
                    nonce: '<?php echo wp_create_nonce('performance_monitor_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error refreshing data');
                    }
                });
            });
            
            $('#clear-performance-data').on('click', function() {
                if (confirm('Clear all performance data? This cannot be undone.')) {
                    $.post(ajaxurl, {
                        action: 'clear_performance_data',
                        nonce: '<?php echo wp_create_nonce('performance_monitor_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for user metric tracking
     */
    public function ajax_track_user_metric() {
        if (!wp_verify_nonce($_POST['nonce'], 'performance_tracking')) {
            wp_die('Security check failed');
        }
        
        $metrics = json_decode(stripslashes($_POST['metrics']), true);
        
        if ($metrics) {
            // Store client-side metrics
            $client_metrics = get_option('hph_client_metrics', []);
            $client_metrics[] = array_merge($metrics, [
                'timestamp' => current_time('timestamp'),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'page' => $_SERVER['HTTP_REFERER'] ?? ''
            ]);
            
            // Keep only last 500 entries
            if (count($client_metrics) > 500) {
                $client_metrics = array_slice($client_metrics, -500);
            }
            
            update_option('hph_client_metrics', $client_metrics);
        }
        
        wp_die();
    }
    
    /**
     * AJAX handler for getting performance data
     */
    public function ajax_get_performance_data() {
        if (!wp_verify_nonce($_POST['nonce'], 'performance_monitor_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }
        
        $this->run_performance_analysis();
        wp_send_json_success('Analysis refreshed');
    }
    
    /**
     * AJAX handler for clearing performance data
     */
    public function ajax_clear_performance_data() {
        if (!wp_verify_nonce($_POST['nonce'], 'performance_monitor_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }
        
        delete_option('hph_performance_metrics');
        delete_option('hph_performance_stats');
        delete_option('hph_performance_analysis');
        delete_option('hph_performance_issues');
        delete_option('hph_slow_queries');
        delete_option('hph_client_metrics');
        
        wp_send_json_success('All performance data cleared');
    }
}
