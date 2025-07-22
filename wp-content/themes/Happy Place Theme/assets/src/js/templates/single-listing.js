/**
 * Single Listing Template JavaScript
 * Loaded by Asset_Loader when single-listing.php template loads
 */

import { PhotoCarousel } from '../components/photo-carousel';
import { MortgageCalculator } from '../components/mortgage-calculator';
import { ListingMap } from '../components/listing-map';
import { ContactForms } from '../components/contact-forms';

class SingleListing {
    constructor() {
        this.init();
    }

    init() {
        // Initialize components when DOM is ready
        document.addEventListener('DOMContentLoaded', () => {
            this.initPhotoCarousel();
            this.initMortgageCalculator();
            this.initMap();
            this.initContactForms();
            this.initScrollEffects();
        });
    }

    initPhotoCarousel() {
        const carousel = document.querySelector('.hero-carousel');
        if (carousel) {
            new PhotoCarousel(carousel);
        }
    }

    initMortgageCalculator() {
        const calculator = document.querySelector('#hph-mortgage-calculator');
        if (calculator) {
            new MortgageCalculator(calculator, window.hphMortgageData || {});
        }
    }

    initMap() {
        const mapContainer = document.querySelector('.map-container');
        if (mapContainer && window.google) {
            new ListingMap(mapContainer);
        }
    }

    initContactForms() {
        const forms = document.querySelectorAll('.contact-form');
        forms.forEach(form => new ContactForms(form));
    }

    initScrollEffects() {
        // Sticky quick facts bar
        const quickFacts = document.querySelector('.quick-facts');
        if (quickFacts) {
            this.handleStickyBar(quickFacts);
        }
    }

    handleStickyBar(element) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    element.classList.add('is-sticky');
                } else {
                    element.classList.remove('is-sticky');
                }
            });
        });
        
        observer.observe(element);
    }
}

// Initialize
new SingleListing();

// Global functions for backward compatibility
window.hph_nextImage = () => {
    const carousel = document.querySelector('.hero-carousel');
    if (carousel && carousel.photoCarousel) {
        carousel.photoCarousel.next();
    }
};

window.hph_previousImage = () => {
    const carousel = document.querySelector('.hero-carousel');
    if (carousel && carousel.photoCarousel) {
        carousel.photoCarousel.previous();
    }
};