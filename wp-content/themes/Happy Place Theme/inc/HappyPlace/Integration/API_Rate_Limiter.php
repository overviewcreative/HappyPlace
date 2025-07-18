<?php

namespace HappyPlace\Integration;

/**
 * API Rate Limiter
 *
 * Handles rate limiting for API requests to prevent hitting limits
 *
 * @package HappyPlace\Integration
 * @since 2.0.0
 */
class API_Rate_Limiter {
    
    /**
     * Rate limit configuration
     * @var array
     */
    protected $limits;
    
    /**
     * Cache key prefix
     * @var string
     */
    protected $cache_prefix = 'hph_rate_limit_';
    
    /**
     * Constructor
     * 
     * @param array $limits Rate limit configuration
     */
    public function __construct($limits = []) {
        $this->limits = wp_parse_args($limits, [
            'requests_per_second' => 10,
            'requests_per_minute' => 600,
            'requests_per_hour' => 10000,
            'requests_per_day' => 100000
        ]);
    }
    
    /**
     * Check if request is allowed based on rate limits
     * 
     * @param string $identifier Optional identifier for separate rate limiting
     * @throws Integration_Exception If rate limit exceeded
     * @return bool True if allowed
     */
    public function check_limits($identifier = 'default') {
        $current_time = time();
        
        // Check each time window
        foreach ($this->limits as $window => $limit) {
            if (!$this->is_allowed($identifier, $window, $limit, $current_time)) {
                $reset_time = $this->get_reset_time($identifier, $window, $current_time);
                
                throw new Integration_Exception(
                    "Rate limit exceeded for {$window}. Limit: {$limit}. Reset in: " . 
                    ($reset_time - $current_time) . " seconds",
                    429, // HTTP Too Many Requests
                    null,
                    'rate_limiter',
                    [
                        'window' => $window,
                        'limit' => $limit,
                        'reset_time' => $reset_time,
                        'identifier' => $identifier
                    ]
                );
            }
        }
        
        // All checks passed, increment counters
        $this->increment_counters($identifier, $current_time);
        
        return true;
    }
    
    /**
     * Check if request is allowed for specific window
     * 
     * @param string $identifier Rate limit identifier
     * @param string $window Time window (requests_per_second, etc.)
     * @param int $limit Request limit for window
     * @param int $current_time Current timestamp
     * @return bool True if allowed
     */
    protected function is_allowed($identifier, $window, $limit, $current_time) {
        $window_seconds = $this->get_window_seconds($window);
        $cache_key = $this->get_cache_key($identifier, $window);
        
        // Get current count for this window
        $count_data = wp_cache_get($cache_key, 'hph_rate_limits');
        
        if (!$count_data) {
            return true; // No data means no previous requests
        }
        
        // Clean up old entries
        $count_data = $this->cleanup_old_entries($count_data, $current_time, $window_seconds);
        
        // Check if under limit
        return count($count_data) < $limit;
    }
    
    /**
     * Increment request counters
     * 
     * @param string $identifier Rate limit identifier
     * @param int $current_time Current timestamp
     */
    protected function increment_counters($identifier, $current_time) {
        foreach ($this->limits as $window => $limit) {
            $cache_key = $this->get_cache_key($identifier, $window);
            $window_seconds = $this->get_window_seconds($window);
            
            // Get current data
            $count_data = wp_cache_get($cache_key, 'hph_rate_limits') ?: [];
            
            // Add current request
            $count_data[] = $current_time;
            
            // Clean up old entries
            $count_data = $this->cleanup_old_entries($count_data, $current_time, $window_seconds);
            
            // Store updated data
            wp_cache_set($cache_key, $count_data, 'hph_rate_limits', $window_seconds);
        }
    }
    
    /**
     * Get window duration in seconds
     * 
     * @param string $window Window type
     * @return int Seconds
     */
    protected function get_window_seconds($window) {
        switch ($window) {
            case 'requests_per_second':
                return 1;
            case 'requests_per_minute':
                return 60;
            case 'requests_per_hour':
                return 3600;
            case 'requests_per_day':
                return 86400;
            default:
                return 3600;
        }
    }
    
    /**
     * Get cache key for rate limiting
     * 
     * @param string $identifier Rate limit identifier
     * @param string $window Time window
     * @return string Cache key
     */
    protected function get_cache_key($identifier, $window) {
        return $this->cache_prefix . $identifier . '_' . $window;
    }
    
    /**
     * Clean up old entries from count data
     * 
     * @param array $count_data Request timestamps
     * @param int $current_time Current timestamp
     * @param int $window_seconds Window duration
     * @return array Cleaned data
     */
    protected function cleanup_old_entries($count_data, $current_time, $window_seconds) {
        $cutoff_time = $current_time - $window_seconds;
        
        return array_filter($count_data, function($timestamp) use ($cutoff_time) {
            return $timestamp > $cutoff_time;
        });
    }
    
    /**
     * Get reset time for rate limit window
     * 
     * @param string $identifier Rate limit identifier
     * @param string $window Time window
     * @param int $current_time Current timestamp
     * @return int Reset timestamp
     */
    protected function get_reset_time($identifier, $window, $current_time) {
        $window_seconds = $this->get_window_seconds($window);
        $cache_key = $this->get_cache_key($identifier, $window);
        
        $count_data = wp_cache_get($cache_key, 'hph_rate_limits') ?: [];
        
        if (empty($count_data)) {
            return $current_time;
        }
        
        // Find oldest entry
        $oldest_timestamp = min($count_data);
        
        return $oldest_timestamp + $window_seconds;
    }
    
    /**
     * Get current rate limit status
     * 
     * @param string $identifier Rate limit identifier
     * @return array Status information
     */
    public function get_status($identifier = 'default') {
        $current_time = time();
        $status = [];
        
        foreach ($this->limits as $window => $limit) {
            $cache_key = $this->get_cache_key($identifier, $window);
            $window_seconds = $this->get_window_seconds($window);
            
            $count_data = wp_cache_get($cache_key, 'hph_rate_limits') ?: [];
            $count_data = $this->cleanup_old_entries($count_data, $current_time, $window_seconds);
            
            $current_count = count($count_data);
            $remaining = max(0, $limit - $current_count);
            $reset_time = $this->get_reset_time($identifier, $window, $current_time);
            
            $status[$window] = [
                'limit' => $limit,
                'used' => $current_count,
                'remaining' => $remaining,
                'reset_time' => $reset_time,
                'reset_in' => max(0, $reset_time - $current_time)
            ];
        }
        
        return $status;
    }
    
    /**
     * Wait until rate limit allows next request
     * 
     * @param string $identifier Rate limit identifier
     * @param int $max_wait_seconds Maximum seconds to wait
     * @return bool True if wait successful, false if max wait exceeded
     */
    public function wait_for_availability($identifier = 'default', $max_wait_seconds = 60) {
        $wait_start = time();
        
        while ((time() - $wait_start) < $max_wait_seconds) {
            try {
                $this->check_limits($identifier);
                return true; // Rate limit allows request
            } catch (Integration_Exception $e) {
                // Extract wait time from exception context
                $context = $e->getContext();
                $wait_time = min($context['reset_time'] - time(), 5); // Max 5 second chunks
                
                if ($wait_time > 0) {
                    sleep($wait_time);
                }
            }
        }
        
        return false; // Max wait time exceeded
    }
    
    /**
     * Reset rate limits for identifier
     * 
     * @param string $identifier Rate limit identifier
     * @return bool Success status
     */
    public function reset($identifier = 'default') {
        $success = true;
        
        foreach ($this->limits as $window => $limit) {
            $cache_key = $this->get_cache_key($identifier, $window);
            
            if (!wp_cache_delete($cache_key, 'hph_rate_limits')) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Get all active rate limit identifiers
     * 
     * @return array Active identifiers
     */
    public function get_active_identifiers() {
        // This would require tracking identifiers separately
        // For now, return default identifier
        return ['default'];
    }
    
    /**
     * Update rate limits configuration
     * 
     * @param array $new_limits New rate limits
     */
    public function update_limits($new_limits) {
        $this->limits = wp_parse_args($new_limits, $this->limits);
    }
}
