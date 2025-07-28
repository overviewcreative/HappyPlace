/**
 * Single Listing Page Initialization Script
 * Ensures all components are properly loaded and initialized
 */

(function() {
    'use strict';
    
    // Debug flag
    const DEBUG = true;
    
    function debugLog(message, ...args) {
        if (DEBUG) {
            console.log(`[SingleListing Init] ${message}`, ...args);
        }
    }
    
    // Wait for DOM to be ready
    function onDOMReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }
    
    // Initialize the page
    function initializePage() {
        debugLog('Starting page initialization...');
        
        // Check if we're on a single listing page
        const listingPage = document.querySelector('.listing-page, .single-listing');
        if (!listingPage) {
            debugLog('Not a listing page, skipping initialization');
            return;
        }
        
        // Initialize hero carousel immediately if elements exist
        initializeHeroCarousel();
        
        // Initialize other components
        initializeActionButtons();
        initializeFormValidation();
        initializeNotifications();
        
        // Load and initialize main SingleListing class
        loadSingleListingModule();
        
        debugLog('Page initialization complete');
    }
    
    // Initialize hero carousel with fallback
    function initializeHeroCarousel() {
        const heroElement = document.querySelector('[data-component="hero"], .hph-hero');
        if (!heroElement) {
            debugLog('No hero element found');
            return;
        }
        
        debugLog('Initializing hero carousel...');
        
        // Basic carousel functionality as fallback
        const slides = heroElement.querySelectorAll('.hph-hero__slide');
        const prevBtn = heroElement.querySelector('.hph-hero__nav-btn--prev');
        const nextBtn = heroElement.querySelector('.hph-hero__nav-btn--next');
        const photoCounter = heroElement.querySelector('.hph-hero__current-photo');
        
        if (slides.length === 0) {
            debugLog('No slides found in hero');
            return;
        }
        
        let currentSlide = 0;
        
        function updateSlide(index) {
            // Remove active class from all slides
            slides.forEach(slide => slide.classList.remove('hph-hero__slide--active'));
            
            // Add active class to current slide
            if (slides[index]) {
                slides[index].classList.add('hph-hero__slide--active');
            }
            
            // Update photo counter
            if (photoCounter) {
                photoCounter.textContent = index + 1;
            }
            
            debugLog(`Switched to slide ${index + 1}/${slides.length}`);
        }
        
        function nextSlide() {
            currentSlide = (currentSlide + 1) % slides.length;
            updateSlide(currentSlide);
        }
        
        function prevSlide() {
            currentSlide = (currentSlide - 1 + slides.length) % slides.length;
            updateSlide(currentSlide);
        }
        
        // Bind navigation buttons
        if (nextBtn) {
            nextBtn.addEventListener('click', nextSlide);
            debugLog('Next button bound');
        }
        
        if (prevBtn) {
            prevBtn.addEventListener('click', prevSlide);
            debugLog('Previous button bound');
        }
        
        // Initialize first slide
        updateSlide(0);
        
        // Auto-play functionality
        let autoplayInterval;
        
        function startAutoplay() {
            autoplayInterval = setInterval(nextSlide, 5000);
        }
        
        function stopAutoplay() {
            if (autoplayInterval) {
                clearInterval(autoplayInterval);
                autoplayInterval = null;
            }
        }
        
        // Start autoplay if more than one slide
        if (slides.length > 1) {
            startAutoplay();
            
            // Pause on hover
            heroElement.addEventListener('mouseenter', stopAutoplay);
            heroElement.addEventListener('mouseleave', startAutoplay);
        }
        
        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') {
                prevSlide();
                stopAutoplay();
            } else if (e.key === 'ArrowRight') {
                nextSlide();
                stopAutoplay();
            }
        });
        
        debugLog(`Hero carousel initialized with ${slides.length} slides`);
    }
    
    // Initialize action buttons
    function initializeActionButtons() {
        debugLog('Initializing action buttons...');
        
        // Schedule Tour buttons
        const scheduleTourBtns = document.querySelectorAll('[data-action="schedule-tour"]');
        scheduleTourBtns.forEach(btn => {
            btn.addEventListener('click', handleScheduleTour);
        });
        
        // Apply Now buttons
        const applyNowBtns = document.querySelectorAll('[data-action="apply-now"]');
        applyNowBtns.forEach(btn => {
            btn.addEventListener('click', handleApplyNow);
        });
        
        // Contact buttons
        const contactBtns = document.querySelectorAll('[data-action="contact"]');
        contactBtns.forEach(btn => {
            btn.addEventListener('click', handleContact);
        });
        
        // Favorite buttons
        const favoriteBtns = document.querySelectorAll('[data-action="favorite"]');
        favoriteBtns.forEach(btn => {
            btn.addEventListener('click', handleFavorite);
            updateFavoriteState(btn);
        });
        
        // Share buttons
        const shareBtns = document.querySelectorAll('[data-action="share"]');
        shareBtns.forEach(btn => {
            btn.addEventListener('click', handleShare);
        });
        
        debugLog(`Initialized ${scheduleTourBtns.length + applyNowBtns.length + contactBtns.length + favoriteBtns.length + shareBtns.length} action buttons`);
    }
    
    // Button handlers
    function handleScheduleTour(event) {
        event.preventDefault();
        debugLog('Schedule tour clicked');
        showNotification('Opening tour scheduler...', 'info');
        
        // Scroll to contact form as fallback
        const contactForm = document.querySelector('#contact-form, .contact-form');
        if (contactForm) {
            contactForm.scrollIntoView({ behavior: 'smooth' });
        }
    }
    
    function handleApplyNow(event) {
        event.preventDefault();
        debugLog('Apply now clicked');
        showNotification('Opening application...', 'info');
        
        // Could redirect to application page
        const applicationUrl = event.currentTarget.dataset.applicationUrl;
        if (applicationUrl) {
            window.location.href = applicationUrl;
        }
    }
    
    function handleContact(event) {
        event.preventDefault();
        debugLog('Contact clicked');
        
        // Scroll to contact section
        const contactSection = document.querySelector('#contact-section, .contact-section');
        if (contactSection) {
            contactSection.scrollIntoView({ behavior: 'smooth' });
        }
    }
    
    function handleFavorite(event) {
        event.preventDefault();
        const btn = event.currentTarget;
        const listingId = getListingId();
        
        if (!listingId) {
            showNotification('Unable to favorite this property', 'error');
            return;
        }
        
        const isFavorited = btn.classList.contains('is-favorite');
        
        if (isFavorited) {
            btn.classList.remove('is-favorite');
            removeFavorite(listingId);
            showNotification('Removed from favorites', 'success');
        } else {
            btn.classList.add('is-favorite');
            addFavorite(listingId);
            showNotification('Added to favorites', 'success');
        }
        
        // Update icon
        const icon = btn.querySelector('i');
        if (icon) {
            icon.className = isFavorited ? 'fas fa-heart-o' : 'fas fa-heart';
        }
        
        debugLog(`Favorite toggled for listing ${listingId}: ${!isFavorited}`);
    }
    
    function handleShare(event) {
        event.preventDefault();
        debugLog('Share clicked');
        
        if (navigator.share) {
            navigator.share({
                title: document.title,
                text: 'Check out this property',
                url: window.location.href
            }).then(() => {
                showNotification('Shared successfully!', 'success');
            }).catch((error) => {
                debugLog('Share failed:', error);
                fallbackShare();
            });
        } else {
            fallbackShare();
        }
    }
    
    function fallbackShare() {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(window.location.href).then(() => {
                showNotification('Link copied to clipboard!', 'success');
            }).catch(() => {
                showNotification('Unable to copy link', 'error');
            });
        } else {
            showNotification('Sharing not supported', 'error');
        }
    }
    
    // Favorite management
    function getListingId() {
        const listingContainer = document.querySelector('[data-listing-id]');
        return listingContainer ? listingContainer.dataset.listingId : null;
    }
    
    function addFavorite(listingId) {
        const favorites = JSON.parse(localStorage.getItem('hph_favorites') || '[]');
        if (!favorites.includes(listingId)) {
            favorites.push(listingId);
            localStorage.setItem('hph_favorites', JSON.stringify(favorites));
        }
    }
    
    function removeFavorite(listingId) {
        const favorites = JSON.parse(localStorage.getItem('hph_favorites') || '[]');
        const index = favorites.indexOf(listingId);
        if (index > -1) {
            favorites.splice(index, 1);
            localStorage.setItem('hph_favorites', JSON.stringify(favorites));
        }
    }
    
    function updateFavoriteState(btn) {
        const listingId = getListingId();
        if (!listingId) return;
        
        const favorites = JSON.parse(localStorage.getItem('hph_favorites') || '[]');
        if (favorites.includes(listingId)) {
            btn.classList.add('is-favorite');
            const icon = btn.querySelector('i');
            if (icon) {
                icon.className = 'fas fa-heart';
            }
        }
    }
    
    // Form validation
    function initializeFormValidation() {
        debugLog('Initializing form validation...');
        
        const forms = document.querySelectorAll('form[data-validate]');
        forms.forEach(form => {
            form.addEventListener('submit', handleFormSubmit);
            
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('blur', validateField);
                input.addEventListener('input', clearFieldError);
            });
        });
        
        debugLog(`Initialized validation for ${forms.length} forms`);
    }
    
    function handleFormSubmit(event) {
        const form = event.target;
        const isValid = validateForm(form);
        
        if (!isValid) {
            event.preventDefault();
            showNotification('Please fix the errors and try again', 'error');
        }
    }
    
    function validateForm(form) {
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!validateField({ target: input })) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    function validateField(event) {
        const field = event.target;
        const value = field.value.trim();
        const type = field.type;
        let isValid = true;
        let message = '';
        
        clearFieldError(event);
        
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            message = 'This field is required';
        }
        
        if (type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                message = 'Please enter a valid email address';
            }
        }
        
        if (type === 'tel' && value) {
            const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
            if (!phoneRegex.test(value.replace(/\\D/g, ''))) {
                isValid = false;
                message = 'Please enter a valid phone number';
            }
        }
        
        if (!isValid) {
            showFieldError(field, message);
        }
        
        return isValid;
    }
    
    function showFieldError(field, message) {
        field.classList.add('error');
        
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
        
        const errorElement = document.createElement('div');
        errorElement.className = 'field-error';
        errorElement.textContent = message;
        errorElement.style.color = '#dc2626';
        errorElement.style.fontSize = '0.875rem';
        errorElement.style.marginTop = '0.25rem';
        field.parentNode.appendChild(errorElement);
    }
    
    function clearFieldError(event) {
        const field = event.target;
        field.classList.remove('error');
        
        const errorElement = field.parentNode.querySelector('.field-error');
        if (errorElement) {
            errorElement.remove();
        }
    }
    
    // Notification system
    function initializeNotifications() {
        // Add notification styles if not present
        if (!document.querySelector('#hph-notification-styles')) {
            const styles = document.createElement('style');
            styles.id = 'hph-notification-styles';
            styles.textContent = `
                .hph-notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 12px 16px;
                    border-radius: 8px;
                    font-size: 14px;
                    font-weight: 500;
                    color: white;
                    z-index: 9999;
                    transform: translateX(100%);
                    opacity: 0;
                    transition: all 0.3s ease;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                    max-width: 300px;
                }
                .hph-notification--show {
                    transform: translateX(0);
                    opacity: 1;
                }
                .hph-notification--success { background: #059669; }
                .hph-notification--error { background: #dc2626; }
                .hph-notification--warning { background: #d97706; }
                .hph-notification--info { background: #2563eb; }
            `;
            document.head.appendChild(styles);
        }
    }
    
    function showNotification(message, type = 'info') {
        const existingNotification = document.querySelector('.hph-notification');
        if (existingNotification) {
            existingNotification.remove();
        }
        
        const notification = document.createElement('div');
        notification.className = `hph-notification hph-notification--${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        requestAnimationFrame(() => {
            notification.classList.add('hph-notification--show');
        });
        
        setTimeout(() => {
            notification.classList.remove('hph-notification--show');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }, 3000);
        
        debugLog(`Notification shown: ${message} (${type})`);
    }
    
    // Load main SingleListing module if available
    function loadSingleListingModule() {
        if (window.HPH && window.HPH.listingPage) {
            debugLog('SingleListing module already loaded');
            return;
        }
        
        // Try to initialize if module is available
        setTimeout(() => {
            if (window.HPH && window.HPH.listingPage) {
                debugLog('SingleListing module found, initializing...');
                // Module handles its own initialization
            } else {
                debugLog('SingleListing module not found, using fallback functionality');
            }
        }, 100);
    }
    
    // Initialize everything when DOM is ready
    onDOMReady(initializePage);
    
    // Export for debugging
    window.HPH = window.HPH || {};
    window.HPH.initDebug = {
        debugLog,
        showNotification,
        initializeHeroCarousel,
        initializeActionButtons
    };
    
})();
