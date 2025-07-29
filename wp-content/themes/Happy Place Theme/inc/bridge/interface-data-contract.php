<?php
/**
 * HPH Data Contract Interface
 *
 * Defines the communication protocol between plugin and theme
 * Plugin implements this interface, theme uses it for data access
 *
 * @package HappyPlace
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin-Theme Data Contract Interface
 * 
 * Plugin provides data contract interface
 * Theme uses interface, never direct plugin methods
 */
interface HPH_Data_Contract {
    
    /**
     * Get listing data
     *
     * @param int $listing_id Listing post ID
     * @return array Listing data array
     */
    public function get_listing_data($listing_id);
    
    /**
     * Get agent data
     *
     * @param int $agent_id Agent post ID
     * @return array Agent data array
     */
    public function get_agent_data($agent_id);
    
    /**
     * Get dashboard data
     *
     * @param int $user_id User ID
     * @return array Dashboard data array
     */
    public function get_dashboard_data($user_id);
    
    /**
     * Search listings
     *
     * @param array $criteria Search criteria
     * @return array Search results
     */
    public function search_listings($criteria);
    
    /**
     * Get financial data
     *
     * @param int $listing_id Listing post ID
     * @return array Financial data array
     */
    public function get_financial_data($listing_id);
    
    /**
     * Get agent listings
     *
     * @param int $agent_id Agent post ID
     * @return array Agent's listings
     */
    public function get_agent_listings($agent_id);
}
