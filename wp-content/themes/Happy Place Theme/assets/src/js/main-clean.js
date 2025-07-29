/**
 * Main site-wide JavaScript
 * 
 * General functionality for the entire site (non-dashboard pages)
 * @since 3.0.0
 */

// Import site-wide styles
import '../scss/main.scss';

// Import hero carousel for global use
import { initHeroCarousels, observeHeroCarousels } from './components/hero-carousel.js';

// Import property stats module for enhanced lot size display
import { PropertyStats } from './modules/property-stats.js';

// Import listing sidebar module for open house and mortgage calculator functionality
import './modules/listing-sidebar.js';

// Initialize site-wide functionality
document.addEventListener('DOMContentLoaded', () => {
    console.log('TPG Site v3.0.0 loaded');
    
    // Initialize hero carousels globally
    initHeroCarousels();
    observeHeroCarousels();
    
    // Initialize property stats
    if (window.PropertyStats || PropertyStats) {
        new PropertyStats();
    }
    
    // Initialize modal functionality
    initModals();
    
    // Initialize site-wide components
    initMobileMenu();
    initScrollEffects();
    initContactForms();
    initPropertySearch();
});

/**
 * Initialize mobile menu
 */
function initMobileMenu() {
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');
    
    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('active');
            menuToggle.classList.toggle('active');
        });
    }
}

/**
 * Initialize scroll effects
 */
function initScrollEffects() {
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Add scroll-based animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        observer.observe(el);
    });
}

/**
 * Initialize contact forms
 */
function initContactForms() {
    document.querySelectorAll('.contact-form').forEach(form => {
        form.addEventListener('submit', handleFormSubmit);
    });
    
    // Initialize lead capture forms
    document.querySelectorAll('.lead-form').forEach(form => {
        form.addEventListener('submit', handleLeadFormSubmit);
    });
}

/**
 * Initialize property search functionality
 */
function initPropertySearch() {
    const searchForm = document.querySelector('.property-search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', handlePropertySearch);
        
        // Initialize search filters
        initSearchFilters();
    }
}

/**
 * Initialize search filters
 */
function initSearchFilters() {
    // Price range slider
    const priceSlider = document.querySelector('.price-range-slider');
    if (priceSlider) {
        // Initialize dual range slider
        initDualRangeSlider(priceSlider);
    }
    
    // Bed/bath selectors
    document.querySelectorAll('.bed-bath-selector').forEach(selector => {
        selector.addEventListener('change', updateSearchResults);
    });
    
    // Property type checkboxes
    document.querySelectorAll('.property-type-filter').forEach(checkbox => {
        checkbox.addEventListener('change', updateSearchResults);
    });
}

/**
 * Handle form submission
 */
async function handleFormSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const submitButton = form.querySelector('button[type="submit"]');
    
    // Add WordPress AJAX action
    formData.append('action', 'handle_contact_form');
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
            showMessage('Thank you! Your message has been sent.', 'success');
            form.reset();
        } else {
            showMessage(result.data?.message || 'Something went wrong. Please try again.', 'error');
        }
        
    } catch (error) {
        console.error('Form submission error:', error);
        showMessage('Something went wrong. Please try again.', 'error');
    }
    
    // Re-enable submit button
    if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = 'Send Message';
    }
}

/**
 * Handle lead form submission
 */
async function handleLeadFormSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const submitButton = form.querySelector('button[type="submit"]');
    
    // Add WordPress AJAX action
    formData.append('action', 'handle_lead_form');
    formData.append('nonce', window.tpgAjax?.nonce || '');
    
    // Disable submit button
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Submitting...';
    }
    
    try {
        const response = await fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage('Thank you for your interest! We will contact you soon.', 'success');
            form.reset();
            
            // Track lead conversion
            if (window.gtag) {
                gtag('event', 'generate_lead', {
                    'event_category': 'Lead',
                    'event_label': 'Contact Form',
                    'value': 1
                });
            }
        } else {
            showMessage(result.data?.message || 'Something went wrong. Please try again.', 'error');
        }
        
    } catch (error) {
        console.error('Lead form submission error:', error);
        showMessage('Something went wrong. Please try again.', 'error');
    }
    
    // Re-enable submit button
    if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = 'Get Information';
    }
}

/**
 * Handle property search
 */
async function handlePropertySearch(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const resultsContainer = document.querySelector('.search-results');
    
    if (!resultsContainer) return;
    
    // Show loading state
    resultsContainer.innerHTML = '<div class="loading">Searching properties...</div>';
    
    // Add WordPress AJAX action
    formData.append('action', 'search_properties');
    formData.append('nonce', window.tpgAjax?.nonce || '');
    
    try {
        const response = await fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            resultsContainer.innerHTML = result.data.html;
            
            // Re-initialize any components in the results
            initHeroCarousels();
            
            // Update URL without page reload
            const url = new URL(window.location);
            for (const [key, value] of formData.entries()) {
                if (key !== 'action' && key !== 'nonce') {
                    url.searchParams.set(key, value);
                }
            }
            window.history.pushState({}, '', url);
            
        } else {
            resultsContainer.innerHTML = '<div class="no-results">No properties found matching your criteria.</div>';
        }
        
    } catch (error) {
        console.error('Property search error:', error);
        resultsContainer.innerHTML = '<div class="error">Search temporarily unavailable. Please try again.</div>';
    }
}

/**
 * Update search results (for filter changes)
 */
function updateSearchResults() {
    const searchForm = document.querySelector('.property-search-form');
    if (searchForm) {
        // Trigger form submission with current filter values
        searchForm.dispatchEvent(new Event('submit'));
    }
}

/**
 * Initialize dual range slider
 */
function initDualRangeSlider(slider) {
    // This would be a more complex implementation
    // For now, just handle basic range input
    const minInput = slider.querySelector('.range-min');
    const maxInput = slider.querySelector('.range-max');
    const display = slider.querySelector('.range-display');
    
    if (minInput && maxInput && display) {
        function updateDisplay() {
            const min = parseInt(minInput.value);
            const max = parseInt(maxInput.value);
            display.textContent = `$${min.toLocaleString()} - $${max.toLocaleString()}`;
        }
        
        minInput.addEventListener('input', updateDisplay);
        maxInput.addEventListener('input', updateDisplay);
        updateDisplay();
    }
}

/**
 * Initialize modal functionality
 */
function initModals() {
    // Get all modal triggers
    const modalTriggers = document.querySelectorAll('[class*="modal"], [data-modal]');
    const modals = document.querySelectorAll('.hph-modal');
    
    // Handle contact agent modal triggers
    const contactTriggers = document.querySelectorAll('.hph-contact-agent-modal');
    const contactModal = document.getElementById('contact-modal');
    
    contactTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            
            const agentId = this.getAttribute('data-agent-id');
            const agentName = this.getAttribute('data-agent-name') || 'Agent';
            
            if (contactModal) {
                // Update modal content
                const agentNameSpan = contactModal.querySelector('.hph-agent-name');
                const agentIdInput = contactModal.querySelector('input[name="agent_id"]');
                
                if (agentNameSpan) agentNameSpan.textContent = agentName;
                if (agentIdInput) agentIdInput.value = agentId;
                
                // Show modal
                showModal(contactModal);
            }
        });
    });
    
    // Handle property inquiry modal triggers
    const inquiryTriggers = document.querySelectorAll('.hph-property-inquiry-modal');
    const inquiryModal = document.getElementById('property-inquiry-modal');
    
    inquiryTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (inquiryModal) {
                showModal(inquiryModal);
            }
        });
    });
    
    // Handle modal close buttons
    const closeButtons = document.querySelectorAll('.hph-modal__close');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.hph-modal');
            if (modal) {
                hideModal(modal);
            }
        });
    });
    
    // Handle overlay clicks to close modals
    const overlays = document.querySelectorAll('.hph-modal__overlay');
    overlays.forEach(overlay => {
        overlay.addEventListener('click', function() {
            const modal = this.closest('.hph-modal');
            if (modal) {
                hideModal(modal);
            }
        });
    });
    
    // Handle ESC key to close modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const activeModal = document.querySelector('.hph-modal.hph-modal--active');
            if (activeModal) {
                hideModal(activeModal);
            }
        }
    });
}

/**
 * Show modal
 */
function showModal(modal) {
    if (!modal) return;
    
    modal.classList.add('hph-modal--active');
    document.body.classList.add('modal-open');
    
    // Focus management
    const firstFocusable = modal.querySelector('input, button, select, textarea, [tabindex]:not([tabindex="-1"])');
    if (firstFocusable) {
        setTimeout(() => firstFocusable.focus(), 100);
    }
}

/**
 * Hide modal
 */
function hideModal(modal) {
    if (!modal) return;
    
    modal.classList.remove('hph-modal--active');
    document.body.classList.remove('modal-open');
    
    // Clear form if it exists
    const form = modal.querySelector('form');
    if (form) {
        form.reset();
    }
}

/**
 * Show message to user
 */
function showMessage(message, type = 'info') {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message message-${type}`;
    messageDiv.textContent = message;
    
    // Style the message
    Object.assign(messageDiv.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        padding: '12px 20px',
        borderRadius: '4px',
        zIndex: '10000',
        maxWidth: '400px',
        boxShadow: '0 4px 12px rgba(0, 0, 0, 0.15)',
        fontSize: '14px',
        fontWeight: '500'
    });
    
    // Set colors based on type
    switch (type) {
        case 'success':
            messageDiv.style.backgroundColor = '#10b981';
            messageDiv.style.color = 'white';
            break;
        case 'error':
            messageDiv.style.backgroundColor = '#ef4444';
            messageDiv.style.color = 'white';
            break;
        default:
            messageDiv.style.backgroundColor = '#3b82f6';
            messageDiv.style.color = 'white';
    }
    
    document.body.appendChild(messageDiv);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (messageDiv.parentNode) {
            messageDiv.parentNode.removeChild(messageDiv);
        }
    }, 5000);
    
    // Allow manual dismiss
    messageDiv.addEventListener('click', () => {
        if (messageDiv.parentNode) {
            messageDiv.parentNode.removeChild(messageDiv);
        }
    });
}
