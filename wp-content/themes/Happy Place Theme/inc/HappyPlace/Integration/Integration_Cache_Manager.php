<?php

namespace HappyPlace\Integration;

/**
 * Integration Cache Manager
 *
 * Manages caching for integration data with invalidation strategies
 *
 * @package HappyPlace\Integration
 * @since 2.0.0
 */
class Integration_Cache_Manager {
    
    /**
     * Cache group for this integration
     * @var string
     */
    protected $cache_group;
    
    /**
     * Default cache expiration
     * @var int
     */
    protected $default_expiration = 3600; // 1 hour
    
    /**
     * Cache statistics
     * @var array
     */
    protected $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0
    ];
    
    /**
     * Constructor
     * 
     * @param string $integration_type Integration type for cache grouping
     */
    public function __construct($integration_type) {
        $this->cache_group = "hph_integration_{$integration_type}";
    }
    
    /**
     * Get cached data
     * 
     * @param string $key Cache key
     * @return mixed Cached data or false if not found
     */
    public function get($key) {
        $cache_key = $this->normalize_key($key);
        $cached = wp_cache_get($cache_key, $this->cache_group);
        
        if ($cached !== false) {
            $this->stats['hits']++;
            
            // Check if cached data has expiration metadata
            if (is_array($cached) && isset($cached['_cache_meta'])) {
                $meta = $cached['_cache_meta'];
                
                // Check if expired
                if (isset($meta['expires']) && $meta['expires'] < time()) {
                    $this->delete($key);
                    $this->stats['misses']++;
                    return false;
                }
                
                // Return actual data without metadata
                return $cached['data'];
            }
            
            return $cached;
        }
        
        $this->stats['misses']++;
        return false;
    }
    
    /**
     * Set cached data
     * 
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int $expiration Expiration in seconds (optional)
     * @return bool Success status
     */
    public function set($key, $data, $expiration = null) {
        $cache_key = $this->normalize_key($key);
        $expiration = $expiration ?: $this->default_expiration;
        
        // Wrap data with metadata
        $cache_data = [
            'data' => $data,
            '_cache_meta' => [
                'created' => time(),
                'expires' => time() + $expiration,
                'key' => $key,
                'group' => $this->cache_group
            ]
        ];
        
        $result = wp_cache_set($cache_key, $cache_data, $this->cache_group, $expiration);
        
        if ($result) {
            $this->stats['sets']++;
        }
        
        return $result;
    }
    
    /**
     * Delete cached data
     * 
     * @param string $key Cache key
     * @return bool Success status
     */
    public function delete($key) {
        $cache_key = $this->normalize_key($key);
        $result = wp_cache_delete($cache_key, $this->cache_group);
        
        if ($result) {
            $this->stats['deletes']++;
        }
        
        return $result;
    }
    
    /**
     * Flush all cache for this integration
     * 
     * @return bool Success status
     */
    public function flush() {
        // WordPress doesn't have a group-specific flush, so we track keys
        $keys = $this->get_tracked_keys();
        $success = true;
        
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }
        
        // Clear tracked keys
        $this->clear_tracked_keys();
        
        return $success;
    }
    
    /**
     * Get cached data with callback fallback
     * 
     * @param string $key Cache key
     * @param callable $callback Function to generate data if not cached
     * @param int $expiration Cache expiration
     * @return mixed Cached or generated data
     */
    public function remember($key, $callback, $expiration = null) {
        $cached = $this->get($key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        if (is_callable($callback)) {
            $data = $callback();
            $this->set($key, $data, $expiration);
            return $data;
        }
        
        return false;
    }
    
    /**
     * Increment a cached value
     * 
     * @param string $key Cache key
     * @param int $offset Increment amount
     * @return int|false New value or false on failure
     */
    public function increment($key, $offset = 1) {
        $cache_key = $this->normalize_key($key);
        return wp_cache_incr($cache_key, $offset, $this->cache_group);
    }
    
    /**
     * Decrement a cached value
     * 
     * @param string $key Cache key
     * @param int $offset Decrement amount
     * @return int|false New value or false on failure
     */
    public function decrement($key, $offset = 1) {
        $cache_key = $this->normalize_key($key);
        return wp_cache_decr($cache_key, $offset, $this->cache_group);
    }
    
    /**
     * Get cache statistics
     * 
     * @return array Cache stats
     */
    public function get_stats() {
        $total_requests = $this->stats['hits'] + $this->stats['misses'];
        $hit_ratio = $total_requests > 0 ? ($this->stats['hits'] / $total_requests) * 100 : 0;
        
        return array_merge($this->stats, [
            'total_requests' => $total_requests,
            'hit_ratio' => round($hit_ratio, 2),
            'cache_group' => $this->cache_group
        ]);
    }
    
    /**
     * Normalize cache key
     * 
     * @param string $key Raw key
     * @return string Normalized key
     */
    protected function normalize_key($key) {
        // Remove invalid characters and ensure reasonable length
        $normalized = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
        
        if (strlen($normalized) > 250) {
            $normalized = substr($normalized, 0, 200) . '_' . md5($key);
        }
        
        // Track key for group flushing
        $this->track_key($normalized);
        
        return $normalized;
    }
    
    /**
     * Track cache key for group operations
     * 
     * @param string $key Normalized key
     */
    protected function track_key($key) {
        $tracked_keys = get_option($this->cache_group . '_keys', []);
        
        if (!in_array($key, $tracked_keys)) {
            $tracked_keys[] = $key;
            
            // Limit tracked keys to prevent option bloat
            if (count($tracked_keys) > 1000) {
                $tracked_keys = array_slice($tracked_keys, -800);
            }
            
            update_option($this->cache_group . '_keys', $tracked_keys);
        }
    }
    
    /**
     * Get tracked cache keys
     * 
     * @return array Tracked keys
     */
    protected function get_tracked_keys() {
        return get_option($this->cache_group . '_keys', []);
    }
    
    /**
     * Clear tracked keys
     */
    protected function clear_tracked_keys() {
        delete_option($this->cache_group . '_keys');
    }
    
    /**
     * Get cache info for debugging
     * 
     * @return array Cache information
     */
    public function get_debug_info() {
        return [
            'cache_group' => $this->cache_group,
            'default_expiration' => $this->default_expiration,
            'tracked_keys_count' => count($this->get_tracked_keys()),
            'stats' => $this->get_stats()
        ];
    }
}
