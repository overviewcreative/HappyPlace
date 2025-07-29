<?php
/**
 * Cache Manager for Bridge Functions
 * Centralized caching system for all bridge data access
 * 
 * @package HappyPlace
 * @subpackage Bridge
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get cached data with callback fallback
 * 
 * @param string $key Cache key
 * @param callable $callback Function to generate data if not cached
 * @param string $group Cache group
 * @param int $expiry Cache expiration in seconds
 * @return mixed Cached or generated data
 */
function hph_get_cached_data($key, $callback, $group = 'hph_general', $expiry = 3600) {
    $data = wp_cache_get($key, $group);
    
    if (false === $data) {
        $data = $callback();
        wp_cache_set($key, $data, $group, $expiry);
    }
    
    return $data;
}

/**
 * Clear cache for specific group
 */
function hph_clear_cache_group($group) {
    wp_cache_flush_group($group);
}

/**
 * Clear all HPH cache groups
 */
function hph_clear_all_cache() {
    $groups = ['hph_listings', 'hph_agents', 'hph_financials', 'hph_general'];
    foreach ($groups as $group) {
        wp_cache_flush_group($group);
    }
}

/**
 * Get cache key for listing data
 */
function hph_get_listing_cache_key($listing_id, $type, $format = '') {
    return "listing_{$type}_{$listing_id}" . ($format ? "_{$format}" : '');
}

/**
 * Get cache key for agent data
 */
function hph_get_agent_cache_key($agent_id, $type, $format = '') {
    return "agent_{$type}_{$agent_id}" . ($format ? "_{$format}" : '');
}
