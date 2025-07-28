// Happy Place Theme - Main JavaScript
console.log('Happy Place Theme JS loaded');

// Import SCSS
import '../scss/main.scss';

// Import hero carousel for global use
import { initHeroCarousels, observeHeroCarousels } from './components/hero-carousel.js';

// Import property stats module for enhanced lot size display
import { PropertyStats } from './modules/property-stats.js';

// Import listing sidebar module for open house and mortgage calculator functionality
import './modules/listing-sidebar.js';

// Simple DOM ready function
document.addEventListener('DOMContentLoaded', function() {
    console.log('Happy Place Theme initialized');
    
    // Initialize hero carousels globally
    initHeroCarousels();
    observeHeroCarousels();
    
    // Initialize property stats enhancements
    new PropertyStats();
    
    // Initialize modal functionality
    initModals();
});

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
