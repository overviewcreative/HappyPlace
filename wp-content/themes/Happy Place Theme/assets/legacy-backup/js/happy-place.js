// Main module pattern for Happy Place Theme
const HappyPlace = {
    // Initialize all modules
    init: function() {
        this.Common.init();
        if (document.body.classList.contains('post-type-archive-listing')) {
            this.ListingArchive.init();
        }
        if (document.body.classList.contains('single-listing')) {
            this.SingleListing.init();
        }
        if (document.querySelector('.agent-dashboard')) {
            this.Dashboard.init();
        }
    },

    // Common functionality across all pages
    Common: {
        init: function() {
            this.setupMobileMenu();
            this.setupForms();
            this.setupModals();
        },

        setupMobileMenu: function() {
            const menuToggle = document.querySelector('.menu-toggle');
            const mobileMenu = document.querySelector('.mobile-menu');
            
            if (menuToggle && mobileMenu) {
                menuToggle.addEventListener('click', () => {
                    mobileMenu.classList.toggle('active');
                    menuToggle.setAttribute('aria-expanded', 
                        menuToggle.getAttribute('aria-expanded') === 'true' ? 'false' : 'true'
                    );
                });
            }
        },

        setupForms: function() {
            document.querySelectorAll('form.ajax-form').forEach(form => {
                form.addEventListener('submit', this.handleFormSubmit.bind(this));
            });
        },

        setupModals: function() {
            document.querySelectorAll('[data-modal]').forEach(trigger => {
                trigger.addEventListener('click', (e) => {
                    e.preventDefault();
                    const modalId = trigger.dataset.modal;
                    this.openModal(modalId);
                });
            });
        },

        handleFormSubmit: async function(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            
            try {
                const response = await fetch(happyPlaceData.ajaxUrl, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                if (data.success) {
                    form.reset();
                    this.showMessage(data.message, 'success');
                } else {
                    this.showMessage(data.message, 'error');
                }
            } catch (error) {
                this.showMessage('An error occurred. Please try again.', 'error');
            }
        },

        showMessage: function(message, type = 'info') {
            const messageEl = document.createElement('div');
            messageEl.className = `message message-${type}`;
            messageEl.textContent = message;
            
            document.body.appendChild(messageEl);
            setTimeout(() => messageEl.remove(), 5000);
        },

        openModal: function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
                
                modal.querySelector('.modal-close')?.addEventListener('click', () => {
                    this.closeModal(modal);
                });
                
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        this.closeModal(modal);
                    }
                });
            }
        },

        closeModal: function(modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    },

    // Listing archive page functionality
    ListingArchive: {
        init: function() {
            this.setupFilters();
            this.setupMap();
            this.setupInfiniteScroll();
        },

        setupFilters: function() {
            const filterForm = document.querySelector('.listing-filters');
            if (filterForm) {
                filterForm.addEventListener('change', this.handleFilterChange.bind(this));
                this.setupPriceRange();
            }
        },

        setupMap: function() {
            const mapContainer = document.getElementById('listings-map');
            if (mapContainer && window.google) {
                this.initMap(mapContainer);
            }
        },

        setupInfiniteScroll: function() {
            // Implementation
        }
    },

    // Single listing page functionality
    SingleListing: {
        init: function() {
            this.setupGallery();
            this.setupContactForm();
            this.setupMap();
        }
    },

    // Dashboard functionality
    Dashboard: {
        init: function() {
            this.setupTabs();
            this.setupCharts();
            this.setupDataTables();
        }
    },

    // Utilities
    Utils: {
        formatPrice: function(price) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 0
            }).format(price);
        },

        formatDate: function(date) {
            return new Intl.DateTimeFormat('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }).format(new Date(date));
        },

        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => HappyPlace.init());
