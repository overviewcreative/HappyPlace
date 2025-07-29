<?php
/**
 * Data Provider Registry
 *
 * Manages plugin-theme communication through data contracts
 * Handles registration and access to data providers
 *
 * @package HappyPlace
 * @subpackage Bridge
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register data provider for plugin-theme communication
 *
 * @param HPH_Data_Contract $provider Data provider implementing the contract
 * @throws InvalidArgumentException If provider doesn't implement contract
 */
function hph_register_data_provider($provider) {
    if (!$provider instanceof HPH_Data_Contract) {
        throw new InvalidArgumentException('Provider must implement HPH_Data_Contract');
    }
    
    $GLOBALS['hph_data_provider'] = $provider;
}

/**
 * Get the registered data provider or fallback
 *
 * @return HPH_Data_Contract Data provider instance
 */
function hph_get_data_provider() {
    // Return registered provider or fallback
    return $GLOBALS['hph_data_provider'] ?? new HPH_Fallback_Data_Provider();
}

/**
 * Check if plugin data provider is available
 *
 * @return bool True if plugin provider is registered
 */
function hph_has_plugin_provider() {
    return isset($GLOBALS['hph_data_provider']) && !($GLOBALS['hph_data_provider'] instanceof HPH_Fallback_Data_Provider);
}

/**
 * Initialize data provider system
 * Called during theme initialization
 */
function hph_init_data_provider_system() {
    // Allow plugins to register their data providers
    do_action('hph_register_data_providers');
    
    // Ensure we have a provider (fallback if none registered)
    if (!isset($GLOBALS['hph_data_provider'])) {
        $GLOBALS['hph_data_provider'] = new HPH_Fallback_Data_Provider();
    }
}

// Initialize the system after plugins are loaded
add_action('plugins_loaded', 'hph_init_data_provider_system', 20);
