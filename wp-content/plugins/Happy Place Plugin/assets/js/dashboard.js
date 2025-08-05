/**
 * Dashboard JavaScript Entry Point
 * 
 * Main entry point for dashboard functionality bundled by webpack
 */

// Load marketing suite functionality
require('./marketing-suite-generator.js');

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Dashboard initialization code
    console.log('Happy Place Dashboard loaded');
    
    // Initialize any dashboard-specific functionality here
    if (typeof HPH_Dashboard !== 'undefined') {
        // Dashboard is already initialized by the main file
        console.log('Dashboard system active');
    }
});

// Export for potential external use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        name: 'happy-place-dashboard',
        version: '1.0.0'
    };
}