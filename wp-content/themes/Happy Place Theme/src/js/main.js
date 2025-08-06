/**
 * Main JavaScript Entry Point
 * Happy Place Theme
 */

// Import styles
import '../scss/main.scss';

// Core functionality
import './utilities/main';

// Initialize theme
document.addEventListener('DOMContentLoaded', () => {
    console.log('Happy Place Theme initialized');
    
    // Initialize components based on page context
    initializeComponents();
});

/**
 * Initialize components based on current page
 */
function initializeComponents() {
    const body = document.body;
    
    // Single listing page
    if (body.classList.contains('single-listing')) {
        import('./pages/single-listing').then(module => {
            module.init();
        });
    }
    
    // Archive listing page
    if (body.classList.contains('archive-listing') || body.classList.contains('page-template-archive-listing')) {
        import('./pages/archive-listing').then(module => {
            module.init();
        });
    }
    
    // Agent dashboard
    if (body.classList.contains('page-template-agent-dashboard')) {
        import('./pages/dashboard').then(module => {
            module.init();
        });
    }
}