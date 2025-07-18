<?php
namespace HappyPlace\Theme\Debug;

/**
 * Debug Helper Class
 * Handles debugging, logging, and error tracking
 */
class Debug {
    private static bool $enabled = false;
    private static string $log_file = '';
    private static array $errors = [];
    private static array $queries = [];
    private static float $start_time;
    private static array $memory_usage = [];

    /**
     * Initialize debugging
     */
    public static function init(): void {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            self::$enabled = true;
            self::$log_file = WP_CONTENT_DIR . '/debug.log';
            self::$start_time = microtime(true);

            // Register error handler
            set_error_handler([self::class, 'error_handler']);
            
            // Register shutdown function
            register_shutdown_function([self::class, 'shutdown_handler']);
            
            // Add debug hooks
            add_action('init', [self::class, 'track_initialization'], 0);
            add_action('wp_loaded', [self::class, 'track_loaded'], 0);
            add_filter('template_include', [self::class, 'track_template'], 0);
            add_filter('query', [self::class, 'track_query']);

            // Track memory usage
            self::track_memory('start');
        }
    }

    /**
     * Custom error handler
     */
    public static function error_handler(int $errno, string $errstr, string $errfile, int $errline): bool {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $error_type = match($errno) {
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict Standards',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated',
            default => 'Unknown Error'
        };

        $error = [
            'type' => $error_type,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'time' => microtime(true),
            'memory' => memory_get_usage(),
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
        ];

        self::$errors[] = $error;
        self::log_error($error);

        return true;
    }

    /**
     * Shutdown handler to catch fatal errors
     */
    public static function shutdown_handler(): void {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR])) {
            self::error_handler($error['type'], $error['message'], $error['file'], $error['line']);
        }

        // Log final memory usage
        self::track_memory('end');

        // Save debug log
        self::save_debug_summary();
    }

    /**
     * Track memory usage
     */
    private static function track_memory(string $checkpoint): void {
        self::$memory_usage[$checkpoint] = [
            'memory' => memory_get_usage(),
            'peak' => memory_get_peak_usage(),
            'time' => microtime(true)
        ];
    }

    /**
     * Track template loading
     */
    public static function track_template(string $template): string {
        if (self::$enabled) {
            self::log(sprintf(
                'Loading template: %s',
                str_replace(ABSPATH, '', $template)
            ));
        }
        return $template;
    }

    /**
     * Track database queries
     */
    public static function track_query(string $query): string {
        if (self::$enabled) {
            self::$queries[] = [
                'query' => $query,
                'time' => microtime(true),
                'caller' => self::get_query_caller()
            ];
        }
        return $query;
    }

    /**
     * Get the caller of a database query
     */
    private static function get_query_caller(): string {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $caller = '';
        
        foreach ($trace as $call) {
            if (isset($call['file']) && !strpos($call['file'], 'wp-includes') && !strpos($call['file'], 'wp-admin')) {
                $caller = str_replace(ABSPATH, '', $call['file']) . ':' . $call['line'];
                break;
            }
        }
        
        return $caller;
    }

    /**
     * Track theme initialization
     */
    public static function track_initialization(): void {
        if (self::$enabled) {
            self::log('Theme initialization started');
            self::track_memory('init');
        }
    }

    /**
     * Track WordPress loaded
     */
    public static function track_loaded(): void {
        if (self::$enabled) {
            self::log('WordPress fully loaded');
            self::track_memory('wp_loaded');
        }
    }

    /**
     * Log a message
     */
    public static function log(string $message, string $level = 'info'): void {
        if (!self::$enabled) {
            return;
        }

        $log_entry = sprintf(
            "[%s] [%s] %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message
        );

        file_put_contents(self::$log_file, $log_entry, FILE_APPEND);
    }

    /**
     * Log an error
     */
    private static function log_error(array $error): void {
        $message = sprintf(
            "%s: %s in %s on line %d\n%s\n",
            $error['type'],
            $error['message'],
            $error['file'],
            $error['line'],
            self::format_backtrace($error['backtrace'])
        );

        self::log($message, 'error');
    }

    /**
     * Format backtrace for logging
     */
    private static function format_backtrace(array $backtrace): string {
        $output = "Stack trace:\n";
        foreach ($backtrace as $idx => $call) {
            $file = $call['file'] ?? 'unknown';
            $line = $call['line'] ?? 0;
            $function = $call['function'] ?? 'unknown';
            $class = isset($call['class']) ? $call['class'] . $call['type'] : '';
            
            $output .= sprintf(
                "#%d %s(%d): %s%s()\n",
                $idx,
                str_replace(ABSPATH, '', $file),
                $line,
                $class,
                $function
            );
        }
        return $output;
    }

    /**
     * Save debug summary
     */
    private static function save_debug_summary(): void {
        if (!self::$enabled) {
            return;
        }

        $summary = [
            'execution_time' => microtime(true) - self::$start_time,
            'memory_usage' => self::$memory_usage,
            'error_count' => count(self::$errors),
            'query_count' => count(self::$queries)
        ];

        $message = "\n=== Debug Summary ===\n";
        $message .= sprintf("Execution Time: %.4f seconds\n", $summary['execution_time']);
        $message .= sprintf("Memory Usage: %s\n", size_format($summary['memory_usage']['end']['memory']));
        $message .= sprintf("Peak Memory: %s\n", size_format($summary['memory_usage']['end']['peak']));
        $message .= sprintf("Errors: %d\n", $summary['error_count']);
        $message .= sprintf("Queries: %d\n", $summary['query_count']);
        $message .= "==================\n";

        self::log($message, 'summary');
    }

    /**
     * Get all logged errors
     */
    public static function get_errors(): array {
        return self::$errors;
    }

    /**
     * Get all tracked queries
     */
    public static function get_queries(): array {
        return self::$queries;
    }

    /**
     * Get memory usage statistics
     */
    public static function get_memory_usage(): array {
        return self::$memory_usage;
    }

    /**
     * Check if debugging is enabled
     */
    public static function is_enabled(): bool {
        return self::$enabled;
    }
}
