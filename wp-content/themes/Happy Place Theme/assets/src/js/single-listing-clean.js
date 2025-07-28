/**
 * Single Listing Page Controller
 * Manages all components and interactions on the listing page
 */

// Import SCSS for this page (includes all needed styles)
import '../scss/single-listing.scss';

// Import all component modules
import LivingExperience from './components/living-experience.js';
import MortgageCalculator from './components/mortgage-calculator.js';
import PhotoGallery from './components/photo-gallery.js';

class SingleListingPage {
  constructor() {
    this.components = new Map();
    this.listingId = null;
    this.listingData = null;
    this.init();
  }

  init() {
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => this.initializeComponents());
    } else {
      this.initializeComponents();
    }
  }

  initializeComponents() {
    // Get listing data from page
    this.extractListingData();
    
    // Initialize all components
    this.initLivingExperience();
    this.initMortgageCalculator();
    this.initPhotoGallery();
    
    // Setup global interactions
    this.bindGlobalEvents();
    
    // Track page view
    this.trackPageView();
  }

  extractListingData() {
    const listingContainer = document.querySelector('.listing-page');
    if (listingContainer) {
      this.listingId = listingContainer.dataset.listingId;
    }
    
    // Extract any global listing data from script tags
    const dataElement = document.querySelector('[data-listing-data]');
    if (dataElement) {
      try {
        this.listingData = JSON.parse(dataElement.textContent);
      } catch (e) {
        console.warn('Failed to parse listing data:', e);
      }
    }
  }

  initLivingExperience() {
    const elements = document.querySelectorAll('[data-component="living-experience"]');
    elements.forEach(element => {
      const component = new LivingExperience(element);
      this.components.set('living-experience', component);
    });
  }

  initMortgageCalculator() {
    const elements = document.querySelectorAll('[data-component="mortgage-calculator"]');
    elements.forEach(element => {
      const component = new MortgageCalculator(element);
      this.components.set('mortgage-calculator', component);
    });
  }

  initPhotoGallery() {
    const elements = document.querySelectorAll('[data-component="photo-gallery"]');
    elements.forEach(element => {
      const component = new PhotoGallery(element);
      this.components.set('photo-gallery', component);
    });
  }

  bindGlobalEvents() {
    // Cross-component interactions
    this.setupStickyQuickFacts();
    this.setupScrollToSection();
    this.setupFavoriteSync();
    this.setupShareFunctionality();
  }

  setupStickyQuickFacts() {
    const quickFacts = document.querySelector('.quick-facts');
    if (!quickFacts) return;

    let isSticky = false;
    const header = document.querySelector('header');
    const headerHeight = header ? header.offsetHeight : 0;

    const handleScroll = () => {
      const scrollY = window.pageYOffset;
      const quickFactsTop = quickFacts.offsetTop - headerHeight;
      
      if (scrollY > quickFactsTop && !isSticky) {
        quickFacts.classList.add('sticky');
        isSticky = true;
      } else if (scrollY <= quickFactsTop && isSticky) {
        quickFacts.classList.remove('sticky');
        isSticky = false;
      }
    };

    window.addEventListener('scroll', this.throttle(handleScroll, 16));
  }

  setupScrollToSection() {
    // Handle internal navigation links
    document.querySelectorAll('a[href^="#"]').forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const targetId = link.getAttribute('href').slice(1);
        const target = document.getElementById(targetId);
        if (target) {
          this.scrollToSection(target);
        }
      });
    });
  }

  scrollToSection(target, offset = 100) {
    const targetPosition = target.offsetTop - offset;
    
    window.scrollTo({
      top: targetPosition,
      behavior: 'smooth'
    });
  }

  setupFavoriteSync() {
    // Listen for favorite events and sync across components
    document.addEventListener('hph:favorite-toggled', (e) => {
      const { listingId, isFavorited } = e.detail;
      console.log(`Listing ${listingId} favorite status: ${isFavorited}`);
      
      // Update UI across all components
      this.components.forEach(component => {
        if (component.updateFavoriteStatus) {
          component.updateFavoriteStatus(listingId, isFavorited);
        }
      });
    });
  }

  setupShareFunctionality() {
    // Global share functionality
    window.shareProperty = (method = 'native') => {
      const url = window.location.href;
      const title = document.title;
      
      if (method === 'native' && navigator.share) {
        navigator.share({
          title,
          url,
          text: 'Check out this amazing property!'
        }).then(() => {
          this.trackEvent('property_shared', { method: 'native' });
        }).catch(err => {
          console.log('Share failed:', err);
          this.fallbackShare(url, title);
        });
      } else {
        this.fallbackShare(url, title);
      }
    };
  }

  fallbackShare(url, title) {
    // Copy URL to clipboard as fallback
    if (navigator.clipboard) {
      navigator.clipboard.writeText(url).then(() => {
        this.showToast('Property link copied to clipboard!');
        this.trackEvent('property_shared', { method: 'clipboard' });
      });
    } else {
      // Legacy fallback
      const textArea = document.createElement('textarea');
      textArea.value = url;
      document.body.appendChild(textArea);
      textArea.select();
      document.execCommand('copy');
      document.body.removeChild(textArea);
      this.showToast('Property link copied!');
      this.trackEvent('property_shared', { method: 'clipboard_legacy' });
    }
  }

  showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type} show`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    // Remove after 3 seconds
    setTimeout(() => {
      toast.classList.remove('show');
      setTimeout(() => {
        if (document.body.contains(toast)) {
          document.body.removeChild(toast);
        }
      }, 300);
    }, 3000);
  }

  trackPageView() {
    if (typeof gtag !== 'undefined') {
      gtag('event', 'page_view', {
        page_title: document.title,
        page_location: window.location.href,
        listing_id: this.listingId
      });
    }
  }

  trackEvent(eventName, parameters = {}) {
    if (typeof gtag !== 'undefined') {
      gtag('event', eventName, {
        event_category: 'single_listing',
        event_label: window.location.pathname,
        listing_id: this.listingId,
        ...parameters
      });
    }
  }

  throttle(func, limit) {
    let inThrottle;
    return function() {
      const args = arguments;
      const context = this;
      if (!inThrottle) {
        func.apply(context, args);
        inThrottle = true;
        setTimeout(() => inThrottle = false, limit);
      }
    };
  }

  // Public API for other scripts
  getComponent(name) {
    return this.components.get(name);
  }

  getAllComponents() {
    return Array.from(this.components.values());
  }
}

// Initialize the listing page
const listingPage = new SingleListingPage();

// Make available globally for debugging/external access
window.HPH = window.HPH || {};
window.HPH.listingPage = listingPage;

// Export for module usage
export default SingleListingPage;

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
