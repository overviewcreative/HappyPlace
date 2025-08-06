/**
 * Admin JavaScript Entry Point
 * Happy Place Theme
 */

// Admin-specific functionality
document.addEventListener('DOMContentLoaded', () => {
    console.log('Happy Place Admin initialized');
    
    // Initialize admin components
    initializeAdminComponents();
});

/**
 * Initialize admin components
 */
function initializeAdminComponents() {
    // Geocoding functionality
    if (document.querySelector('.listing-geocode')) {
        import('./listing-geocode').then(module => {
            module.init();
        });
    }
}

// Export for use in other admin modules
export { initializeAdminComponents };