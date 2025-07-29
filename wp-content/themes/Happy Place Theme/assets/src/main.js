// Happy Place Theme - Main JavaScript Entry Point
// Single entry point for all frontend JavaScript functionality

// Import main SCSS (webpack will extract to CSS)
import './scss/main.scss';

// Import all JavaScript components
import './js/components/carousel';
import './js/components/forms';
import './js/components/modals';
import './js/components/search';
import './js/components/listing-card';
import './js/components/agent-card';

// Import template-specific functionality
import './js/templates/listing-single';
import './js/templates/listing-archive';
import './js/templates/agent-profile';
import './js/templates/search-page';

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize global components
    initializeGlobalComponents();
    
    // Initialize page-specific functionality based on body classes
    initializePageSpecific();
});

/**
 * Initialize global components that are used across all pages
 */
function initializeGlobalComponents() {
    // Mobile menu toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');
    
    if (mobileMenuToggle && mobileMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            mobileMenu.classList.toggle('active');
            this.classList.toggle('active');
        });
    }
    
    // Close mobile menu on outside click
    document.addEventListener('click', function(e) {
        if (mobileMenu && !mobileMenu.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
            mobileMenu.classList.remove('active');
            mobileMenuToggle.classList.remove('active');
        }
    });
    
    // Smooth scrolling for anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                e.preventDefault();
                targetElement.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
}

/**
 * Initialize page-specific functionality based on context
 */
function initializePageSpecific() {
    const body = document.body;
    
    // Listing-specific functionality
    if (body.classList.contains('single-listing') || 
        body.classList.contains('post-type-archive-listing')) {
        initializeListingFeatures();
    }
    
    // Agent-specific functionality
    if (body.classList.contains('single-agent') || 
        body.classList.contains('post-type-archive-agent')) {
        initializeAgentFeatures();
    }
    
    // Search page functionality
    if (body.classList.contains('search') || 
        body.classList.contains('page-template-search-listings')) {
        initializeSearchFeatures();
    }
    
    // Dashboard functionality
    if (body.classList.contains('page-template-agent-dashboard') || 
        body.classList.contains('page-template-dashboard')) {
        initializeDashboardFeatures();
    }
}

/**
 * Initialize listing-specific features
 */
function initializeListingFeatures() {
    // Photo gallery
    const gallery = document.querySelector('.hph-photo-gallery');
    if (gallery) {
        initializePhotoGallery(gallery);
    }
    
    // Contact form
    const contactForm = document.querySelector('.listing-contact-form');
    if (contactForm) {
        initializeContactForm(contactForm);
    }
    
    // Map functionality
    const mapContainer = document.querySelector('.listing-map');
    if (mapContainer) {
        initializeListingMap(mapContainer);
    }
}

/**
 * Initialize agent-specific features
 */
function initializeAgentFeatures() {
    // Agent listings carousel
    const agentListings = document.querySelector('.agent-listings-carousel');
    if (agentListings) {
        initializeAgentListingsCarousel(agentListings);
    }
}

/**
 * Initialize search-specific features
 */
function initializeSearchFeatures() {
    // Search filters
    const searchFilters = document.querySelector('.search-filters');
    if (searchFilters) {
        initializeSearchFilters(searchFilters);
    }
    
    // Map view toggle
    const mapToggle = document.querySelector('.map-view-toggle');
    if (mapToggle) {
        initializeMapViewToggle(mapToggle);
    }
}

/**
 * Initialize dashboard features
 */
function initializeDashboardFeatures() {
    // Dashboard tabs
    const dashboardTabs = document.querySelector('.dashboard-tabs');
    if (dashboardTabs) {
        initializeDashboardTabs(dashboardTabs);
    }
    
    // Listing management
    const listingManager = document.querySelector('.listing-manager');
    if (listingManager) {
        initializeListingManager(listingManager);
    }
}

// Placeholder functions for component initialization
// These would be implemented in the respective component files

function initializePhotoGallery(gallery) {
    // Initialize photo gallery functionality
}

function initializeContactForm(form) {
    // Initialize contact form functionality
}

function initializeListingMap(map) {
    // Initialize listing map functionality
}

function initializeAgentListingsCarousel(carousel) {
    // Initialize agent listings carousel functionality
}

function initializeSearchFilters(filters) {
    // Initialize search filters functionality
}

function initializeMapViewToggle(toggle) {
    // Initialize map view toggle functionality
}

function initializeDashboardTabs(tabs) {
    // Initialize dashboard tabs functionality
}

function initializeListingManager(manager) {
    // Initialize listing manager functionality
}
