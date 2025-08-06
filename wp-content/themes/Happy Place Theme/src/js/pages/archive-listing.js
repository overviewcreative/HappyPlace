/**
 * Archive Listing Page JavaScript
 * Happy Place Theme
 */

export function init() {
    console.log('Archive listing page initialized');
    
    // Import and initialize archive components
    Promise.all([
        import('../components/listing-filters'),
        import('../components/listing-map'),
        import('../utilities/filter-sidebar')
    ]).then(([filters, map, sidebar]) => {
        if (filters.init) filters.init();
        if (map.init) map.init();
        if (sidebar.init) sidebar.init();
    });
}