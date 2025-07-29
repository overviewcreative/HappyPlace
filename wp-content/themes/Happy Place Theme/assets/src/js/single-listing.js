/**
 * Single Listing Page JavaScript
 * 
 * Enhanced functionality for individual property listing pages
 * @since 3.0.0
 */

// Import SCSS for this page (includes all needed styles)
import '../scss/single-listing.scss';

// Import specific components for listing pages
import { initHeroCarousels } from './components/hero-carousel.js';
import { PropertyStats } from './modules/property-stats.js';
import './modules/listing-sidebar.js';

// Import all component modules for enhanced functionality
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
    console.log('Single Listing v3.0.0 loaded');
    
    // Get listing data
    this.extractListingData();
    
    // Initialize all components
    this.initializeComponents();
    
    // Initialize enhanced functionality
    this.initEnhancedFeatures();
    
    // Setup event listeners
    this.setupEventListeners();
    
    // Track listing view
    this.trackListingView();
  }

  extractListingData() {
    const listingElement = document.querySelector('[data-listing-id]');
    if (listingElement) {
      this.listingId = listingElement.getAttribute('data-listing-id');
      
      // Extract other listing data from data attributes or meta tags
      this.listingData = {
        id: this.listingId,
        title: document.title,
        price: document.querySelector('[data-price]')?.getAttribute('data-price'),
        address: document.querySelector('[data-address]')?.getAttribute('data-address'),
        lat: parseFloat(document.querySelector('[data-lat]')?.getAttribute('data-lat')),
        lng: parseFloat(document.querySelector('[data-lng]')?.getAttribute('data-lng'))
      };
    }
  }

  initializeComponents() {
    // Initialize existing components
    if (window.LivingExperience || LivingExperience) {
      this.components.set('livingExperience', new LivingExperience());
    }
    
    if (window.MortgageCalculator || MortgageCalculator) {
      this.components.set('mortgageCalculator', new MortgageCalculator());
    }
    
    if (window.PhotoGallery || PhotoGallery) {
      this.components.set('photoGallery', new PhotoGallery());
    }
    
    if (window.HeroCarousel || HeroCarousel) {
      this.components.set('heroCarousel', new HeroCarousel());
    }
    
    // Initialize hero carousels globally
    initHeroCarousels();
    
    // Initialize property stats
    if (PropertyStats) {
      this.components.set('propertyStats', new PropertyStats());
    }
  }

  initEnhancedFeatures() {
    // Initialize listing-specific components
    this.initListingGallery();
    this.initListingMap();
    this.initListingStats();
    this.initListingInquiry();
    this.initSocialSharing();
    this.initVirtualTour();
    this.initListingComparison();
    this.initPropertyDetails();
  }

  setupEventListeners() {
    // Global event listeners for the listing page
    document.addEventListener('click', this.handleGlobalClick.bind(this));
    document.addEventListener('keydown', this.handleKeydown.bind(this));
    window.addEventListener('resize', this.handleResize.bind(this));
  }

  handleGlobalClick(event) {
    // Handle global click events
    const target = event.target;
    
    // Handle modal triggers
    if (target.matches('[data-modal]')) {
      event.preventDefault();
      const modalId = target.getAttribute('data-modal');
      this.openModal(modalId);
    }
    
    // Handle share buttons
    if (target.matches('.share-button')) {
      event.preventDefault();
      const platform = target.getAttribute('data-platform');
      this.handleShare(platform);
    }
  }

  handleKeydown(event) {
    // Handle keyboard shortcuts
    if (event.key === 'Escape') {
      this.closeAllModals();
    }
  }

  handleResize() {
    // Handle responsive adjustments
    this.components.forEach(component => {
      if (component.handleResize) {
        component.handleResize();
      }
    });
  }

  /**
   * Initialize listing photo gallery
   */
  initListingGallery() {
    const galleryContainer = document.querySelector('.listing-gallery');
    const mainImage = document.querySelector('.listing-main-image');
    const thumbnails = document.querySelectorAll('.listing-thumbnail');
    const lightboxTrigger = document.querySelector('.listing-lightbox-trigger');
    
    if (!galleryContainer) return;
    
    // Handle thumbnail clicks
    thumbnails.forEach((thumbnail, index) => {
      thumbnail.addEventListener('click', (e) => {
        e.preventDefault();
        
        // Update main image
        const newImageSrc = thumbnail.getAttribute('data-full-image') || thumbnail.src;
        if (mainImage) {
          mainImage.src = newImageSrc;
          mainImage.alt = thumbnail.alt;
        }
        
        // Update active thumbnail
        thumbnails.forEach(t => t.classList.remove('active'));
        thumbnail.classList.add('active');
        
        // Update lightbox trigger
        if (lightboxTrigger) {
          lightboxTrigger.setAttribute('data-image-index', index);
        }
      });
    });
    
    // Initialize lightbox if available
    if (lightboxTrigger) {
      lightboxTrigger.addEventListener('click', this.openLightbox.bind(this));
    }
    
    // Initialize touch/swipe gestures for mobile
    this.initGallerySwipe();
  }

  /**
   * Initialize listing map
   */
  initListingMap() {
    const mapContainer = document.querySelector('.listing-map');
    
    if (!mapContainer || !this.listingData.lat || !this.listingData.lng) return;
    
    const { lat, lng, address } = this.listingData;
    
    // Initialize map based on available libraries
    if (window.google && window.google.maps) {
      this.initGoogleMap(mapContainer, lat, lng, address);
    } else if (window.L) {
      this.initLeafletMap(mapContainer, lat, lng, address);
    } else {
      // Fallback to static map
      this.initStaticMap(mapContainer, lat, lng, address);
    }
    
    // Initialize neighborhood info
    this.initNeighborhoodInfo(lat, lng);
  }

  /**
   * Initialize listing statistics and analytics
   */
  initListingStats() {
    const statsContainer = document.querySelector('.listing-stats');
    
    if (!statsContainer) return;
    
    // Update view counter
    this.updateViewCounter();
    
    // Initialize price history chart
    this.initPriceHistory();
    
    // Initialize market comparison
    this.initMarketComparison();
  }

  /**
   * Initialize listing inquiry form
   */
  initListingInquiry() {
    const inquiryForms = document.querySelectorAll('.listing-inquiry-form');
    
    inquiryForms.forEach(form => {
      form.addEventListener('submit', this.handleListingInquiry.bind(this));
      
      // Initialize form enhancements
      this.initFormEnhancements(form);
    });
    
    // Initialize contact buttons
    const phoneButtons = document.querySelectorAll('.contact-phone');
    const emailButtons = document.querySelectorAll('.contact-email');
    
    phoneButtons.forEach(button => {
      button.addEventListener('click', () => this.trackContactAction('phone'));
    });
    
    emailButtons.forEach(button => {
      button.addEventListener('click', () => this.trackContactAction('email'));
    });
  }

  /**
   * Initialize social sharing
   */
  initSocialSharing() {
    const shareButtons = document.querySelectorAll('.social-share-button');
    
    shareButtons.forEach(button => {
      button.addEventListener('click', (e) => {
        e.preventDefault();
        
        const platform = button.getAttribute('data-platform');
        this.handleShare(platform);
      });
    });
    
    // Initialize copy link functionality
    const copyLinkButton = document.querySelector('.copy-link-button');
    if (copyLinkButton) {
      copyLinkButton.addEventListener('click', this.copyLink.bind(this));
    }
  }

  /**
   * Initialize virtual tour
   */
  initVirtualTour() {
    const tourButton = document.querySelector('.virtual-tour-button');
    const tourModal = document.querySelector('.virtual-tour-modal');
    
    if (!tourButton || !tourModal) return;
    
    tourButton.addEventListener('click', (e) => {
      e.preventDefault();
      
      // Show virtual tour modal
      this.openModal('virtual-tour');
      
      // Initialize 360 viewer or iframe
      const tourContent = tourModal.querySelector('.tour-content');
      const tourUrl = tourButton.getAttribute('data-tour-url');
      
      if (tourUrl && tourContent) {
        tourContent.innerHTML = `<iframe src="${tourUrl}" frameborder="0" allowfullscreen></iframe>`;
      }
      
      // Track virtual tour interaction
      this.trackVirtualTourView();
    });
  }

  /**
   * Initialize listing comparison
   */
  initListingComparison() {
    const compareButton = document.querySelector('.add-to-compare');
    
    if (!compareButton) return;
    
    compareButton.addEventListener('click', (e) => {
      e.preventDefault();
      
      const listingId = this.listingId;
      const listingTitle = this.listingData.title;
      
      // Add to comparison (localStorage)
      this.addToComparison(listingId, listingTitle);
      
      // Update button state
      compareButton.classList.add('added');
      compareButton.textContent = 'Added to Compare';
      
      // Show comparison notification
      this.showComparisonNotification();
    });
    
    // Initialize comparison widget
    this.initComparisonWidget();
  }

  /**
   * Initialize property details enhancements
   */
  initPropertyDetails() {
    // Enhance property feature lists
    const featureLists = document.querySelectorAll('.property-features-list');
    featureLists.forEach(list => {
      this.enhanceFeatureList(list);
    });
    
    // Initialize interactive floor plans
    const floorPlanTriggers = document.querySelectorAll('.floor-plan-trigger');
    floorPlanTriggers.forEach(trigger => {
      trigger.addEventListener('click', this.showFloorPlan.bind(this));
    });
    
    // Initialize school district information
    this.initSchoolInfo();
    
    // Initialize nearby amenities
    this.initNearbyAmenities();
  }

  /**
   * Handle listing inquiry form submission
   */
  async handleListingInquiry(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const submitButton = form.querySelector('button[type="submit"]');
    
    // Add listing-specific data
    if (this.listingId) {
      formData.append('listing_id', this.listingId);
    }
    
    // Add WordPress AJAX action
    formData.append('action', 'handle_listing_inquiry');
    formData.append('nonce', window.tpgAjax?.nonce || '');
    
    // Disable submit button
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = 'Sending...';
    }
    
    try {
      const response = await fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        body: formData
      });
      
      const result = await response.json();
      
      if (result.success) {
        this.showToast('Thank you! Your inquiry has been sent to the listing agent.', 'success');
        form.reset();
        
        // Track lead generation
        this.trackLeadGeneration(this.listingId);
        
      } else {
        this.showToast(result.data?.message || 'Something went wrong. Please try again.', 'error');
      }
      
    } catch (error) {
      console.error('Listing inquiry error:', error);
      this.showToast('Something went wrong. Please try again.', 'error');
    }
    
    // Re-enable submit button
    if (submitButton) {
      submitButton.disabled = false;
      submitButton.textContent = 'Send Inquiry';
    }
  }

  /**
   * Handle social sharing
   */
  handleShare(platform) {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent(this.listingData.title);
    const imageUrl = encodeURIComponent(this.getListingFeaturedImage());
    
    let shareUrl = '';
    
    switch (platform) {
      case 'facebook':
        shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
        break;
      case 'twitter':
        shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${title}`;
        break;
      case 'linkedin':
        shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${url}`;
        break;
      case 'pinterest':
        shareUrl = `https://pinterest.com/pin/create/button/?url=${url}&media=${imageUrl}&description=${title}`;
        break;
      case 'email':
        shareUrl = `mailto:?subject=${title}&body=Check out this property: ${url}`;
        break;
    }
    
    if (shareUrl) {
      if (platform === 'email') {
        window.location.href = shareUrl;
      } else {
        window.open(shareUrl, '_blank', 'width=600,height=400');
      }
      
      // Track sharing action
      this.trackSocialShare(platform);
    }
  }

  /**
   * Copy link to clipboard
   */
  copyLink() {
    navigator.clipboard.writeText(window.location.href).then(() => {
      this.showToast('Link copied to clipboard!', 'success');
      this.trackSocialShare('copy-link');
    });
  }

  /**
   * Track listing view
   */
  trackListingView() {
    if (!this.listingId) return;
    
    // Send view tracking to server
    fetch('/wp-admin/admin-ajax.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({
        action: 'track_listing_view',
        listing_id: this.listingId,
        nonce: window.tpgAjax?.nonce || ''
      })
    }).catch(error => {
      console.warn('Failed to track listing view:', error);
    });
    
    // Google Analytics tracking
    if (window.gtag) {
      gtag('event', 'view_item', {
        'item_id': this.listingId,
        'item_name': this.listingData.title,
        'item_category': 'Property Listing'
      });
    }
  }

  /**
   * Show toast notification
   */
  showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    
    Object.assign(toast.style, {
      position: 'fixed',
      bottom: '20px',
      right: '20px',
      padding: '12px 20px',
      borderRadius: '4px',
      zIndex: '10000',
      maxWidth: '400px',
      boxShadow: '0 4px 12px rgba(0, 0, 0, 0.15)',
      fontSize: '14px',
      fontWeight: '500',
      transform: 'translateY(100px)',
      transition: 'transform 0.3s ease'
    });
    
    // Set colors based on type
    switch (type) {
      case 'success':
        toast.style.backgroundColor = '#10b981';
        toast.style.color = 'white';
        break;
      case 'error':
        toast.style.backgroundColor = '#ef4444';
        toast.style.color = 'white';
        break;
      default:
        toast.style.backgroundColor = '#3b82f6';
        toast.style.color = 'white';
    }
    
    document.body.appendChild(toast);
    
    // Show animation
    setTimeout(() => {
      toast.style.transform = 'translateY(0)';
    }, 100);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
      toast.style.transform = 'translateY(100px)';
      setTimeout(() => {
        if (toast.parentNode) {
          toast.parentNode.removeChild(toast);
        }
      }, 300);
    }, 5000);
  }

  /**
   * Utility methods
   */
  getListingFeaturedImage() {
    const mainImage = document.querySelector('.listing-main-image');
    return mainImage ? mainImage.src : '';
  }

  addToComparison(listingId, listingTitle) {
    let comparison = JSON.parse(localStorage.getItem('property_comparison') || '[]');
    
    // Check if already added
    if (comparison.find(item => item.id === listingId)) {
      return;
    }
    
    // Add to comparison (max 3 items)
    comparison.push({
      id: listingId,
      title: listingTitle,
      url: window.location.href,
      image: this.getListingFeaturedImage()
    });
    
    if (comparison.length > 3) {
      comparison = comparison.slice(-3);
    }
    
    localStorage.setItem('property_comparison', JSON.stringify(comparison));
    
    // Update comparison widget
    this.updateComparisonWidget(comparison);
  }

  openModal(modalId) {
    const modal = document.getElementById(`${modalId}-modal`);
    if (modal) {
      modal.classList.add('active');
      document.body.classList.add('modal-open');
    }
  }

  closeAllModals() {
    const modals = document.querySelectorAll('.modal.active');
    modals.forEach(modal => {
      modal.classList.remove('active');
    });
    document.body.classList.remove('modal-open');
  }

  /**
   * Analytics tracking methods
   */
  trackContactAction(type) {
    if (window.gtag) {
      gtag('event', 'contact_action', {
        'method': type,
        'event_category': 'Lead Generation'
      });
    }
  }

  trackSocialShare(platform) {
    if (window.gtag) {
      gtag('event', 'share', {
        'method': platform,
        'content_type': 'property_listing'
      });
    }
  }

  trackVirtualTourView() {
    if (window.gtag) {
      gtag('event', 'virtual_tour_view', {
        'event_category': 'Engagement'
      });
    }
  }

  trackLeadGeneration(listingId) {
    if (window.gtag) {
      gtag('event', 'generate_lead', {
        'event_category': 'Lead Generation',
        'event_label': 'Listing Inquiry',
        'custom_parameters': {
          'listing_id': listingId
        }
      });
    }
  }

  // Placeholder methods for additional functionality
  initGoogleMap(container, lat, lng, address) { /* Implementation */ }
  initLeafletMap(container, lat, lng, address) { /* Implementation */ }
  initStaticMap(container, lat, lng, address) { /* Implementation */ }
  initGallerySwipe() { /* Implementation */ }
  initNeighborhoodInfo() { /* Implementation */ }
  updateViewCounter() { /* Implementation */ }
  initPriceHistory() { /* Implementation */ }
  initMarketComparison() { /* Implementation */ }
  initFormEnhancements() { /* Implementation */ }
  openLightbox() { /* Implementation */ }
  showComparisonNotification() { /* Implementation */ }
  initComparisonWidget() { /* Implementation */ }
  updateComparisonWidget() { /* Implementation */ }
  enhanceFeatureList() { /* Implementation */ }
  showFloorPlan() { /* Implementation */ }
  initSchoolInfo() { /* Implementation */ }
  initNearbyAmenities() { /* Implementation */ }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  window.singleListingPage = new SingleListingPage();
});

// Export for external use
export default SingleListingPage;
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
