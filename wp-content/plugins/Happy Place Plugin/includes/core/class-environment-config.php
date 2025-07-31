<?php
/**
 * Environment-Based Configuration Handler
 * 
 * Manages different configuration sets for development vs production
 *
 * @package HappyPlace
 * @subpackage Core
 */

namespace HappyPlace\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Environment_Config {
    
    /**
     * Instance
     */
    private static ?self $instance = null;
    
    /**
     * Current environment
     */
    private string $environment;
    
    /**
     * Environment-specific configurations
     */
    private array $env_configs = [];
    
    /**
     * Get instance
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->detect_environment();
        $this->init_environment_configs();
    }
    
    /**
     * Detect current environment
     */
    private function detect_environment(): void {
        // Check for explicit environment setting
        if (defined('HPH_ENVIRONMENT')) {
            $this->environment = constant('HPH_ENVIRONMENT');
            return;
        }
        
        // Check WordPress environment
        if (defined('WP_ENVIRONMENT_TYPE')) {
            $this->environment = constant('WP_ENVIRONMENT_TYPE');
            return;
        }
        
        // Check for development indicators
        if (defined('WP_DEBUG') && constant('WP_DEBUG')) {
            $this->environment = 'development';
            return;
        }
        
        // Check hostname patterns
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (preg_match('/\.(local|dev|test)$/', $host) || 
            strpos($host, 'localhost') !== false ||
            strpos($host, '127.0.0.1') !== false) {
            $this->environment = 'development';
            return;
        }
        
        // Default to production
        $this->environment = 'production';
    }
    
    /**
     * Initialize environment-specific configurations
     */
    private function init_environment_configs(): void {
        $this->env_configs = [
            'development' => [
                'general' => [
                    'debug_mode' => true,
                    'error_logging' => true
                ],
                'performance' => [
                    'caching_enabled' => false,
                    'cache_duration' => 5, // Short cache for development
                    'cache_strategy' => 'minimal',
                    'minify_assets' => false,
                    'performance_monitoring' => true
                ],
                'api' => [
                    'rate_limiting_enabled' => false,
                    'cache_duration' => 1, // Very short API cache
                    'log_api_requests' => true
                ],
                'security' => [
                    'api_rate_limiting' => false,
                    'log_api_requests' => true,
                    'validate_api_keys' => false // Less strict for development
                ],
                'advanced' => [
                    'error_logging' => true,
                    'performance_monitoring' => true,
                    'database_optimization' => false
                ]
            ],
            'staging' => [
                'general' => [
                    'debug_mode' => true,
                    'error_logging' => true
                ],
                'performance' => [
                    'caching_enabled' => true,
                    'cache_duration' => 15, // Medium cache for staging
                    'cache_strategy' => 'balanced',
                    'minify_assets' => true,
                    'performance_monitoring' => true
                ],
                'api' => [
                    'rate_limiting_enabled' => true,
                    'cache_duration' => 12,
                    'log_api_requests' => true
                ],
                'security' => [
                    'api_rate_limiting' => true,
                    'log_api_requests' => true,
                    'validate_api_keys' => true
                ],
                'advanced' => [
                    'error_logging' => true,
                    'performance_monitoring' => true,
                    'database_optimization' => true
                ]
            ],
            'production' => [
                'general' => [
                    'debug_mode' => false,
                    'error_logging' => false
                ],
                'performance' => [
                    'caching_enabled' => true,
                    'cache_duration' => 60, // Long cache for production
                    'cache_strategy' => 'aggressive',
                    'minify_assets' => true,
                    'performance_monitoring' => false
                ],
                'api' => [
                    'rate_limiting_enabled' => true,
                    'cache_duration' => 24,
                    'log_api_requests' => false
                ],
                'security' => [
                    'api_rate_limiting' => true,
                    'log_api_requests' => false,
                    'validate_api_keys' => true
                ],
                'advanced' => [
                    'error_logging' => false,
                    'performance_monitoring' => false,
                    'database_optimization' => true
                ]
            ]
        ];
    }
    
    /**
     * Get current environment
     */
    public function get_environment(): string {
        return $this->environment;
    }
    
    /**
     * Check if current environment matches
     */
    public function is_environment(string $env): bool {
        return $this->environment === $env;
    }
    
    /**
     * Check if development environment
     */
    public function is_development(): bool {
        return $this->is_environment('development');
    }
    
    /**
     * Check if staging environment
     */
    public function is_staging(): bool {
        return $this->is_environment('staging');
    }
    
    /**
     * Check if production environment
     */
    public function is_production(): bool {
        return $this->is_environment('production');
    }
    
    /**
     * Get environment-specific configuration
     */
    public function get_env_config(string $group): array {
        return $this->env_configs[$this->environment][$group] ?? [];
    }
    
    /**
     * Apply environment configuration overrides
     */
    public function apply_env_overrides(Config_Manager $config_manager): void {
        foreach ($this->env_configs[$this->environment] ?? [] as $group => $overrides) {
            $current_config = $config_manager->get_group($group);
            $merged_config = array_merge($current_config, $overrides);
            $config_manager->set_group($group, $merged_config);
        }
    }
    
    /**
     * Get recommended settings for current environment
     */
    public function get_recommended_settings(): array {
        $recommendations = [];
        
        switch ($this->environment) {
            case 'development':
                $recommendations = [
                    'Enable debug mode for troubleshooting',
                    'Disable caching for real-time changes',
                    'Enable API request logging',
                    'Disable asset minification for debugging',
                    'Enable performance monitoring'
                ];
                break;
                
            case 'staging':
                $recommendations = [
                    'Enable caching with balanced strategy',
                    'Enable asset minification for testing',
                    'Enable API rate limiting',
                    'Monitor performance metrics',
                    'Test database optimization'
                ];
                break;
                
            case 'production':
                $recommendations = [
                    'Disable debug mode for security',
                    'Enable aggressive caching strategy',
                    'Enable all security features',
                    'Disable verbose logging',
                    'Enable database optimization'
                ];
                break;
        }
        
        return $recommendations;
    }
    
    /**
     * Get environment-specific constants
     */
    public function get_env_constants(): array {
        $constants = [
            'HPH_ENV' => $this->environment,
            'HPH_IS_DEV' => $this->is_development(),
            'HPH_IS_STAGING' => $this->is_staging(),
            'HPH_IS_PROD' => $this->is_production()
        ];
        
        // Environment-specific URLs and paths
        switch ($this->environment) {
            case 'development':
                $constants['HPH_API_TIMEOUT'] = 30; // Longer timeout for development
                $constants['HPH_CACHE_PREFIX'] = 'hph_dev_';
                break;
                
            case 'staging':
                $constants['HPH_API_TIMEOUT'] = 15;
                $constants['HPH_CACHE_PREFIX'] = 'hph_stage_';
                break;
                
            case 'production':
                $constants['HPH_API_TIMEOUT'] = 10; // Shorter timeout for production
                $constants['HPH_CACHE_PREFIX'] = 'hph_prod_';
                break;
        }
        
        return $constants;
    }
    
    /**
     * Log environment information
     */
    public function log_environment_info(): void {
        if (!$this->is_development()) {
            return;
        }
        
        $info = [
            'Environment' => $this->environment,
            'WP_DEBUG' => defined('WP_DEBUG') ? (constant('WP_DEBUG') ? 'true' : 'false') : 'undefined',
            'WP_ENVIRONMENT_TYPE' => defined('WP_ENVIRONMENT_TYPE') ? constant('WP_ENVIRONMENT_TYPE') : 'undefined',
            'Host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
            'Recommended Settings' => $this->get_recommended_settings()
        ];
        
        error_log('HPH Environment Info: ' . print_r($info, true));
    }
}
