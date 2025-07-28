/**
 * Debug Script for Hero Carousel Issues
 * Helps identify and fix common problems
 */

(function() {
    'use strict';
    
    function debugCarousel() {
        console.log('=== HERO CAROUSEL DEBUG ===');
        
        // Check hero element
        const heroElement = document.querySelector('[data-component="hero"], .hph-hero');
        console.log('Hero element found:', !!heroElement);
        
        if (heroElement) {
            // Check slides
            const slides = heroElement.querySelectorAll('.hph-hero__slide');
            console.log('Slides found:', slides.length);
            
            // Check active slide
            const activeSlide = heroElement.querySelector('.hph-hero__slide--active');
            console.log('Active slide found:', !!activeSlide);
            
            // Check navigation buttons
            const prevBtn = heroElement.querySelector('.hph-hero__nav-btn--prev');
            const nextBtn = heroElement.querySelector('.hph-hero__nav-btn--next');
            console.log('Previous button found:', !!prevBtn);
            console.log('Next button found:', !!nextBtn);
            
            // Check photo counter
            const photoCounter = heroElement.querySelector('.hph-hero__current-photo');
            console.log('Photo counter found:', !!photoCounter);
            
            // Check if slides have background images
            slides.forEach((slide, index) => {
                const bgImage = getComputedStyle(slide).backgroundImage;
                console.log(`Slide ${index + 1} background image:`, bgImage !== 'none');
            });
            
            // Add test click handlers
            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    console.log('Previous button clicked');
                });
            }
            
            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    console.log('Next button clicked');
                });
            }
        }
        
        // Check action buttons
        const actionButtons = document.querySelectorAll('[data-action]');
        console.log('Action buttons found:', actionButtons.length);
        
        actionButtons.forEach(btn => {
            const action = btn.dataset.action;
            console.log(`Action button: ${action}`);
            
            btn.addEventListener('click', () => {
                console.log(`Action button clicked: ${action}`);
            });
        });
        
        // Check listing ID
        const listingContainer = document.querySelector('[data-listing-id]');
        console.log('Listing ID container found:', !!listingContainer);
        if (listingContainer) {
            console.log('Listing ID:', listingContainer.dataset.listingId);
        }
        
        // Check JavaScript modules
        console.log('HPH object:', window.HPH);
        console.log('SingleListing class:', window.HPH?.listingPage);
        
        console.log('=== END DEBUG ===');
    }
    
    // Run debug when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', debugCarousel);
    } else {
        debugCarousel();
    }
    
    // Also run after a delay to catch dynamically loaded content
    setTimeout(debugCarousel, 1000);
    
})();
