/**
 * Admin JavaScript Entry Point
 * 
 * Main entry point for admin functionality bundled by webpack
 */

// Load existing admin functionality
require('./admin-enhanced.js');
require('./admin-consolidated.js');
require('./modern-admin.js');
require('./config-admin.js');

// Initialize admin functionality
document.addEventListener('DOMContentLoaded', function() {
    console.log('Happy Place Admin loaded');
    
    // Initialize admin-specific features
    initAdminFeatures();
});

/**
 * Initialize admin features
 */
function initAdminFeatures() {
    // Enhanced field functionality
    if (typeof initEnhancedFields === 'function') {
        initEnhancedFields();
    }
    
    // Modern admin UI
    if (typeof modernAdminInit === 'function') {
        modernAdminInit();
    }
    
    // Configuration management
    if (typeof configAdminInit === 'function') {
        configAdminInit();
    }
}

// Export for external use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        name: 'happy-place-admin',
        version: '1.0.0',
        initAdminFeatures: initAdminFeatures
    };
}