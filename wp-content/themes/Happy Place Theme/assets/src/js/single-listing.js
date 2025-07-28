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
import HeroCarousel from './components/hero-carousel.js';

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
    this.initHeroCarousel();
    this.initLivingExperience();
    this.initMortgageCalculator();
    this.initPhotoGallery();
    
    // Setup global interactions
    this.bindGlobalEvents();
    this.initActionButtons();
    this.initFormValidation();
    
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

  initHeroCarousel() {
    const heroElements = document.querySelectorAll('[data-component="hero"]');
    heroElements.forEach(element => {
      const component = new HeroCarousel(element);
      this.components.set('hero-carousel', component);
    });
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
    // Send page view analytics
    if (typeof gtag !== 'undefined' && this.listingId) {
      gtag('event', 'page_view', {
        'custom_map': {
          'listing_id': this.listingId
        }
      });
    }
  }
  
  initActionButtons() {
    // Schedule Tour button
    const scheduleTourBtns = document.querySelectorAll('[data-action="schedule-tour"]');
    scheduleTourBtns.forEach(btn => {
      btn.addEventListener('click', this.handleScheduleTour.bind(this));
    });
    
    // Apply Now button
    const applyNowBtns = document.querySelectorAll('[data-action="apply-now"]');
    applyNowBtns.forEach(btn => {
      btn.addEventListener('click', this.handleApplyNow.bind(this));
    });
    
    // Contact buttons
    const contactBtns = document.querySelectorAll('[data-action="contact"]');
    contactBtns.forEach(btn => {
      btn.addEventListener('click', this.handleContact.bind(this));
    });
    
    // Favorite button
    const favoriteBtns = document.querySelectorAll('[data-action="favorite"]');
    favoriteBtns.forEach(btn => {
      btn.addEventListener('click', this.handleFavorite.bind(this));
    });
    
    // Share button
    const shareBtns = document.querySelectorAll('[data-action="share"]');
    shareBtns.forEach(btn => {
      btn.addEventListener('click', this.handleShare.bind(this));
    });
  }
  
  handleScheduleTour(event) {
    event.preventDefault();
    const btn = event.currentTarget;
    
    // Show tour scheduling modal or redirect
    if (window.HPH && window.HPH.modal) {
      window.HPH.modal.open('schedule-tour');
    } else {
      // Fallback: scroll to contact form
      const contactForm = document.querySelector('#contact-form');
      if (contactForm) {
        contactForm.scrollIntoView({ behavior: 'smooth' });
      }
    }
    
    this.showNotification('Opening tour scheduler...', 'info');
  }
  
  handleApplyNow(event) {
    event.preventDefault();
    const btn = event.currentTarget;
    
    // Show application modal or redirect
    if (window.HPH && window.HPH.modal) {
      window.HPH.modal.open('apply-now');
    } else {
      // Fallback: redirect to application page
      const applicationUrl = btn.dataset.applicationUrl || '/apply';
      window.location.href = applicationUrl;
    }
    
    this.showNotification('Opening application...', 'info');
  }
  
  handleContact(event) {
    event.preventDefault();
    const btn = event.currentTarget;
    
    // Show contact modal or scroll to form
    if (window.HPH && window.HPH.modal) {
      window.HPH.modal.open('contact');
    } else {
      // Fallback: scroll to contact section
      const contactSection = document.querySelector('#contact-section');
      if (contactSection) {
        contactSection.scrollIntoView({ behavior: 'smooth' });
      }
    }
  }
  
  handleFavorite(event) {
    event.preventDefault();
    const btn = event.currentTarget;
    const isFavorited = btn.classList.contains('is-favorite');
    
    // Toggle favorite state
    if (isFavorited) {
      btn.classList.remove('is-favorite');
      this.removeFavorite(this.listingId);
      this.showNotification('Removed from favorites', 'success');
    } else {
      btn.classList.add('is-favorite');
      this.addFavorite(this.listingId);
      this.showNotification('Added to favorites', 'success');
    }
    
    // Update icon
    const icon = btn.querySelector('i');
    if (icon) {
      icon.className = isFavorited ? 'fas fa-heart-o' : 'fas fa-heart';
    }
  }
  
  handleShare(event) {
    event.preventDefault();
    const btn = event.currentTarget;
    
    // Use Web Share API if available
    if (navigator.share) {
      navigator.share({
        title: document.title,
        text: 'Check out this property',
        url: window.location.href
      }).then(() => {
        this.showNotification('Shared successfully!', 'success');
      }).catch((error) => {
        console.log('Error sharing:', error);
        this.fallbackShare();
      });
    } else {
      this.fallbackShare();
    }
  }
  
  fallbackShare() {
    // Copy URL to clipboard
    navigator.clipboard.writeText(window.location.href).then(() => {
      this.showNotification('Link copied to clipboard!', 'success');
    }).catch(() => {
      this.showNotification('Unable to copy link', 'error');
    });
  }
  
  addFavorite(listingId) {
    const favorites = JSON.parse(localStorage.getItem('hph_favorites') || '[]');
    if (!favorites.includes(listingId)) {
      favorites.push(listingId);
      localStorage.setItem('hph_favorites', JSON.stringify(favorites));
    }
  }
  
  removeFavorite(listingId) {
    const favorites = JSON.parse(localStorage.getItem('hph_favorites') || '[]');
    const index = favorites.indexOf(listingId);
    if (index > -1) {
      favorites.splice(index, 1);
      localStorage.setItem('hph_favorites', JSON.stringify(favorites));
    }
  }
  
  initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
      form.addEventListener('submit', this.handleFormSubmit.bind(this));
      
      // Real-time validation
      const inputs = form.querySelectorAll('input, select, textarea');
      inputs.forEach(input => {
        input.addEventListener('blur', this.validateField.bind(this));
        input.addEventListener('input', this.clearFieldError.bind(this));
      });
    });
  }
  
  handleFormSubmit(event) {
    const form = event.target;
    const isValid = this.validateForm(form);
    
    if (!isValid) {
      event.preventDefault();
      this.showNotification('Please fix the errors and try again', 'error');
    }
  }
  
  validateForm(form) {
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
      if (!this.validateField({ target: input })) {
        isValid = false;
      }
    });
    
    return isValid;
  }
  
  validateField(event) {
    const field = event.target;
    const value = field.value.trim();
    const type = field.type;
    let isValid = true;
    let message = '';
    
    // Clear previous errors
    this.clearFieldError(event);
    
    // Required field validation
    if (field.hasAttribute('required') && !value) {
      isValid = false;
      message = 'This field is required';
    }
    
    // Email validation
    if (type === 'email' && value) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(value)) {
        isValid = false;
        message = 'Please enter a valid email address';
      }
    }
    
    // Phone validation
    if (type === 'tel' && value) {
      const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
      if (!phoneRegex.test(value.replace(/\D/g, ''))) {
        isValid = false;
        message = 'Please enter a valid phone number';
      }
    }
    
    if (!isValid) {
      this.showFieldError(field, message);
    }
    
    return isValid;
  }
  
  showFieldError(field, message) {
    field.classList.add('error');
    
    // Remove existing error message
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
      existingError.remove();
    }
    
    // Add new error message
    const errorElement = document.createElement('div');
    errorElement.className = 'field-error';
    errorElement.textContent = message;
    field.parentNode.appendChild(errorElement);
  }
  
  clearFieldError(event) {
    const field = event.target;
    field.classList.remove('error');
    
    const errorElement = field.parentNode.querySelector('.field-error');
    if (errorElement) {
      errorElement.remove();
    }
  }
  
  showNotification(message, type = 'info') {
    // Remove any existing notification
    const existingNotification = document.querySelector('.hph-notification');
    if (existingNotification) {
      existingNotification.remove();
    }
    
    // Create new notification
    const notification = document.createElement('div');
    notification.className = `hph-notification hph-notification--${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Show notification
    requestAnimationFrame(() => {
      notification.classList.add('hph-notification--show');
    });
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
      notification.classList.remove('hph-notification--show');
      setTimeout(() => {
        if (notification.parentNode) {
          notification.remove();
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
