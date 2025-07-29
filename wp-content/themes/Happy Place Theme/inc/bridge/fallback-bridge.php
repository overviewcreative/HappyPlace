<?php
/**
 * Template Bridge Compatibility Layer
 * 
 * This file provides backward compatibility for any code that still includes
 * the old template-bridge.php file. All functionality has been moved to the
 * new modular bridge system in inc/bridge/
 *
 * @package HappyPlace
 * @subpackage Bridge
 * @deprecated Use individual bridge files instead
 */

if (!defined('ABSPATH')) {
    exit;
}

// Bridge system is now loaded via functions.php
// This file provides fallback functionality when bridge data is unavailable

/**
 * Fallback function for when listing data is not available
 */
