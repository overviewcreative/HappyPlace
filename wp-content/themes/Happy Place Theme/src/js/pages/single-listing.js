/**
 * Single Listing Page JavaScript
 * Happy Place Theme
 */

export function init() {
    console.log('Single listing page initialized');
    
    // Import and initialize listing components
    Promise.all([
        import('../components/listing-gallery'),
        import('../components/listing-map'),
        import('../components/listing-contact'),
        import('../utilities/mortgage-calculator')
    ]).then(([gallery, map, contact, calculator]) => {
        if (gallery.init) gallery.init();
        if (map.init) map.init();
        if (contact.init) contact.init();
        if (calculator.init) calculator.init();
    });
}