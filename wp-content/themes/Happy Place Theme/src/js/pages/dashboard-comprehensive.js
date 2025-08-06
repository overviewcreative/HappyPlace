/**
 * Happy Place Real Estate Platform - Agent Dashboard JavaScript
 * 
 * Comprehensive JavaScript handler for all dashboard functionality including:
 * - Navigation and mobile menu
 * - Modal management
 * - Form handling and validation
 * - AJAX interactions
 * - Toast notifications
 * - Charts and data visualization
 * - Live updates and real-time features
 * 
 * @package HappyPlace
 * @version 2.0.0
 */

(function(window, document) {
    'use strict';

    const DashboardTabs = {
        init: function() {
            this.navItems = document.querySelectorAll('.hph-dashboard-nav-item');
            this.sections = document.querySelectorAll('.hph-dashboard-section');
            this.currentSection = this.getCurrentSection();
            
            this.bindEvents();
            this.showSection(this.currentSection);
        },

        bindEvents: function() {
            this.navItems.forEach(item => {
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    const section = item.dataset.section;
                    this.switchToSection(section);
                });
            });

            // Handle browser back/forward buttons
            window.addEventListener('popstate', (e) => {
                if (e.state && e.state.section) {
                    this.showSection(e.state.section, false);
                }
            });
        },

        getCurrentSection: function() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('section') || 'overview';
        },

        switchToSection: function(section) {
            if (section === this.currentSection) return;

            // Update URL without page reload
            const newUrl = this.updateURL(section);
            history.pushState({section: section}, '', newUrl);

            // Load the section
            this.showSection(section);
        },

        showSection: function(section, updateNav = true) {
            // Update navigation
            if (updateNav) {
                this.updateNavigation(section);
            }

            // Hide all sections
            this.sections.forEach(sec => {
                sec.classList.remove('hph-dashboard-section--active');
            });

            // Show target section
            const targetSection = document.getElementById(section);
            if (targetSection) {
                // Check if section content needs to be loaded
                const isLoaded = targetSection.dataset.loaded === 'true';
                const needsRefresh = targetSection.dataset.needsRefresh === 'true';
                const hasContent = targetSection.innerHTML.trim() !== '';
                
                if (!isLoaded && (!hasContent || needsRefresh)) {
                    this.loadSectionContent(section, targetSection);
                } else {
                    targetSection.classList.add('hph-dashboard-section--active');
                    // Mark as loaded if it has content
                    if (hasContent) {
                        targetSection.dataset.loaded = 'true';
                    }
                }
            }

            this.currentSection = section;

            // Update mobile header title
            const mobileTitle = document.querySelector('.hph-mobile-title');
            if (mobileTitle) {
                const navItem = document.querySelector(`[data-section="${section}"]`);
                mobileTitle.textContent = navItem ? navItem.querySelector('span').textContent : 'Dashboard';
            }

            // Close mobile menu if open
            HphDashboard.closeMobileMenu();
        },

        updateNavigation: function(activeSection) {
            this.navItems.forEach(item => {
                if (item.dataset.section === activeSection) {
                    item.classList.add('hph-dashboard-nav-item--active');
                    item.setAttribute('aria-current', 'page');
                } else {
                    item.classList.remove('hph-dashboard-nav-item--active');
                    item.setAttribute('aria-current', 'false');
                }
            });
        },

        loadSectionContent: function(section, targetElement) {
            // Show loading state
            targetElement.classList.add('hph-dashboard-section--loading');
            targetElement.classList.add('hph-dashboard-section--active');

            // Load content via AJAX
            fetch(window.hphAjax.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'hph_load_dashboard_section',
                    section: section,
                    nonce: window.hphAjax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    targetElement.innerHTML = data.data.content;
                    targetElement.classList.remove('hph-dashboard-section--loading');
                    
                    // Initialize any section-specific JavaScript
                    this.initSectionFeatures(section);

                    // Trigger section loaded event
                    HphDashboard.trigger('section:loaded', { section });
                } else {
                    this.showSectionError(targetElement);
                }
            })
            .catch(error => {
                console.error('Dashboard section load error:', error);
                this.showSectionError(targetElement);
            });
        },

        showSectionError: function(element) {
            element.innerHTML = `
                <div class="hph-empty-state">
                    <div class="hph-empty-state-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h2 class="hph-empty-state-title">Error Loading Section</h2>
                    <p class="hph-empty-state-description">
                        There was an error loading this section. Please try refreshing the page.
                    </p>
                    <button class="hph-btn hph-btn--primary" onclick="window.location.reload()">
                        <i class="fas fa-sync"></i> Refresh Page
                    </button>
                </div>
            `;
            element.classList.remove('hph-dashboard-section--loading');
        },

        initSectionFeatures: function(section) {
            if (HphDashboard['init' + section.charAt(0).toUpperCase() + section.slice(1) + 'Features']) {
                HphDashboard['init' + section.charAt(0).toUpperCase() + section.slice(1) + 'Features']();
            }
        },

        updateURL: function(section) {
            const url = new URL(window.location);
            url.searchParams.set('section', section);
            return url.toString();
        }
    };

    /**
     * Main Dashboard Object
     */
    const HphDashboard = {
        
        // Configuration
        config: {
            ajaxUrl: window.hphAjax?.ajaxUrl || '/wp-admin/admin-ajax.php',
            nonce: window.hphAjax?.nonce || '',
            debug: window.hphAjax?.debug || false,
            autoSaveInterval: 30000, // 30 seconds
            notificationDuration: 5000, // 5 seconds
            chartColors: {
                primary: '#51bae0',
                primaryGradient: ['#51bae0', '#38bdf8'],
                success: '#059669',
                warning: '#d97706',
                danger: '#dc2626',
                gray: '#6b7280'
            }
        },

        // State management
        state: {
            currentSection: 'overview',
            isMobile: window.innerWidth <= 768,
            isLoading: false,
            activeModals: new Set(),
            autoSaveTimer: null,
            notificationTimer: null
        },

        // Cache for DOM elements
        cache: {
            body: null,
            dashboard: null,
            sidebar: null,
            mainContent: null,
            navItems: null,
            sections: null,
            modals: null,
            toastContainer: null
        },

        /**
         * Initialize the dashboard
         */
        init() {
            this.log('Initializing Dashboard...');
            
            // Cache DOM elements
            this.cacheElements();
            
            // Initialize core features
            this.initNavigation();
            this.initMobileFeatures();
            this.initModals();
            this.initForms();
            this.initTooltips();
            this.initCharts();
            this.initRealTimeFeatures();
            this.initKeyboardShortcuts();
            
            // Setup event listeners
            this.bindEvents();
            
            // Initialize section-specific features
            this.initSectionFeatures();
            
            // Hide loading overlay
            this.hideLoadingOverlay();
            
            this.log('Dashboard initialized successfully');
            
            // Trigger custom event
            this.trigger('dashboard:initialized');
        },

        /**
         * Cache frequently used DOM elements
         */
        cacheElements() {
            this.cache.body = document.body;
            this.cache.dashboard = document.querySelector('.hph-dashboard');
            this.cache.sidebar = document.querySelector('.hph-dashboard-sidebar');
            this.cache.mainContent = document.querySelector('.hph-dashboard-main');
            this.cache.navItems = document.querySelectorAll('.hph-dashboard-nav-item');
            this.cache.sections = document.querySelectorAll('.hph-dashboard-section');
            this.cache.modals = document.querySelectorAll('.hph-modal-overlay');
            this.cache.toastContainer = document.getElementById('hph-toast-container');
        },

        /**
         * Initialize navigation functionality
         */
        initNavigation() {
            this.cache.navItems.forEach(item => {
                item.addEventListener('click', (e) => {
                    const section = item.dataset.section;
                    
                    // For AJAX navigation (optional)
                    if (window.hphAjaxEnabled && section) {
                        e.preventDefault();
                        this.loadSection(section);
                    }
                });
            });
        },

        /**
         * Initialize mobile-specific features
         */
        initMobileFeatures() {
            const mobileMenuBtn = document.querySelector('.hph-mobile-menu-btn');
            const mobileOverlay = document.querySelector('.hph-mobile-overlay');
            
            if (mobileMenuBtn && this.cache.sidebar && mobileOverlay) {
                mobileMenuBtn.addEventListener('click', () => {
                    this.toggleMobileMenu();
                });
                
                mobileOverlay.addEventListener('click', () => {
                    this.closeMobileMenu();
                });
                
                // Close on escape key
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && this.isMobileMenuOpen()) {
                        this.closeMobileMenu();
                    }
                });
            }
            
            // Handle window resize
            window.addEventListener('resize', () => {
                const wasMobile = this.state.isMobile;
                this.state.isMobile = window.innerWidth <= 768;
                
                if (wasMobile !== this.state.isMobile) {
                    this.handleResponsiveChange();
                }
            });
        },

        /**
         * Initialize modal functionality
         */
        initModals() {
            // Modal triggers
            document.addEventListener('click', (e) => {
                const trigger = e.target.closest('[data-modal]');
                if (trigger) {
                    e.preventDefault();
                    const modalId = trigger.dataset.modal;
                    this.openModal(modalId);
                }
            });

            // Modal close buttons
            document.addEventListener('click', (e) => {
                const closeBtn = e.target.closest('[data-dismiss="modal"]');
                if (closeBtn) {
                    const modal = closeBtn.closest('.hph-modal-overlay');
                    if (modal) {
                        this.closeModal(modal.id);
                    }
                }
            });

            // Close modal on overlay click
            this.cache.modals.forEach(modal => {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        this.closeModal(modal.id);
                    }
                });
            });

            // Close modals on escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.state.activeModals.size > 0) {
                    this.closeTopModal();
                }
            });
        },

        /**
         * Initialize form handling
         */
        initForms() {
            // Form validation
            document.addEventListener('submit', (e) => {
                const form = e.target;
                if (form.classList.contains('hph-dashboard-form') || 
                    form.closest('.hph-modal')) {
                    
                    if (!this.validateForm(form)) {
                        e.preventDefault();
                        this.showToast('Please fix the errors and try again.', 'error');
                    }
                }
            });

            // Auto-save functionality
            this.initAutoSave();

            // File upload handling
            this.initFileUploads();

            // Real-time validation
            this.initRealTimeValidation();
        },

        /**
         * Initialize tooltips
         */
        initTooltips() {
            const elementsWithTooltips = document.querySelectorAll('[title]');
            
            elementsWithTooltips.forEach(element => {
                // Create tooltip functionality if needed
                // This could be expanded based on requirements
            });
        },

        /**
         * Initialize charts and data visualization
         */
        initCharts() {
            // Initialize Chart.js charts if available
            if (typeof Chart !== 'undefined') {
                this.initPerformanceCharts();
                this.initLeadCharts();
            }

            // Initialize other data visualizations
            this.initProgressBars();
            this.initCounters();
        },

        /**
         * Initialize real-time features
         */
        initRealTimeFeatures() {
            // Live time updates
            this.initLiveTime();
            
            // Real-time notifications (if websocket available)
            if (window.WebSocket) {
                this.initWebSocketConnection();
            }
            
            // Periodic data refresh
            this.initPeriodicRefresh();
        },

        /**
         * Initialize keyboard shortcuts
         */
        initKeyboardShortcuts() {
            document.addEventListener('keydown', (e) => {
                // Only process shortcuts when not in input fields
                if (e.target.tagName === 'INPUT' || 
                    e.target.tagName === 'TEXTAREA' || 
                    e.target.contentEditable === 'true') {
                    return;
                }

                // Ctrl/Cmd + key shortcuts
                if (e.ctrlKey || e.metaKey) {
                    switch (e.key) {
                        case 's':
                            e.preventDefault();
                            this.saveCurrentForm();
                            break;
                        case 'k':
                            e.preventDefault();
                            this.focusSearch();
                            break;
                    }
                }

                // Number keys for section navigation
                if (e.key >= '1' && e.key <= '9' && !e.ctrlKey && !e.metaKey) {
                    const sectionIndex = parseInt(e.key) - 1;
                    const navItem = this.cache.navItems[sectionIndex];
                    if (navItem) {
                        navItem.click();
                    }
                }
            });
        },

        /**
         * Bind global event listeners
         */
        bindEvents() {
            // View toggles (grid, list, table)
            document.addEventListener('click', (e) => {
                const viewBtn = e.target.closest('.hph-view-btn');
                if (viewBtn) {
                    this.handleViewToggle(viewBtn);
                }
            });

            // Dropdown toggles
            document.addEventListener('click', (e) => {
                const dropdownToggle = e.target.closest('.hph-dropdown-toggle');
                if (dropdownToggle) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.toggleDropdown(dropdownToggle.closest('.hph-dropdown'));
                }
            });

            // Close dropdowns when clicking outside
            document.addEventListener('click', () => {
                this.closeAllDropdowns();
            });

            // Tab switching
            document.addEventListener('click', (e) => {
                const tab = e.target.closest('.hph-view-tab');
                if (tab) {
                    this.handleTabSwitch(tab);
                }
            });

            // Quick actions
            document.addEventListener('click', (e) => {
                const action = e.target.closest('[data-action]');
                if (action) {
                    this.handleQuickAction(action);
                }
            });
        },

        /**
         * Initialize section-specific features
         */
        initSectionFeatures() {
            // Overview section
            this.initOverviewFeatures();
            
            // Listings section
            this.initListingsFeatures();
            
            // Performance section
            this.initPerformanceFeatures();
            
            // Open houses section
            this.initOpenHousesFeatures();
            
            // Leads section
            this.initLeadsFeatures();
            
            // Profile section
            this.initProfileFeatures();
        },

        /**
         * AJAX section loading
         */
        loadSection(section) {
            if (this.state.isLoading) return;
            
            this.state.isLoading = true;
            this.showLoadingOverlay(`Loading ${section}...`);
            
            const data = {
                action: 'hph_load_dashboard_section',
                section: section,
                nonce: this.config.nonce
            };

            fetch(this.config.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    this.updateSection(section, result.data.html);
                    this.updateNavigation(section);
                    this.state.currentSection = section;
                    
                    // Update URL without page reload
                    history.pushState({section}, '', `?section=${section}`);
                    
                    this.trigger('section:loaded', {section});
                } else {
                    this.showToast('Error loading section. Please try again.', 'error');
                }
            })
            .catch(error => {
                this.log('AJAX Error:', error);
                this.showToast('Connection error. Please check your internet connection.', 'error');
            })
            .finally(() => {
                this.state.isLoading = false;
                this.hideLoadingOverlay();
            });
        },

        /**
         * Mobile menu functions
         */
        toggleMobileMenu() {
            if (this.isMobileMenuOpen()) {
                this.closeMobileMenu();
            } else {
                this.openMobileMenu();
            }
        },

        openMobileMenu() {
            this.cache.sidebar.classList.add('hph-dashboard-sidebar--open');
            document.querySelector('.hph-mobile-overlay').classList.add('hph-mobile-overlay--active');
            this.cache.body.classList.add('hph-modal-open');
        },

        closeMobileMenu() {
            this.cache.sidebar.classList.remove('hph-dashboard-sidebar--open');
            document.querySelector('.hph-mobile-overlay').classList.remove('hph-mobile-overlay--active');
            this.cache.body.classList.remove('hph-modal-open');
        },

        isMobileMenuOpen() {
            return this.cache.sidebar.classList.contains('hph-dashboard-sidebar--open');
        },

        /**
         * Modal management
         */
        openModal(modalId) {
            const modal = document.getElementById(modalId + '-modal') || 
                         document.getElementById(modalId);
            
            if (!modal) {
                this.log('Modal not found:', modalId);
                return;
            }

            modal.classList.add('hph-modal-overlay--active');
            this.cache.body.classList.add('hph-modal-open');
            this.state.activeModals.add(modalId);
            
            // Focus first focusable element
            const focusable = modal.querySelector('input, textarea, select, button');
            if (focusable) {
                setTimeout(() => focusable.focus(), 100);
            }
            
            this.trigger('modal:opened', {modalId});
        },

        closeModal(modalId) {
            const modal = document.getElementById(modalId + '-modal') || 
                         document.getElementById(modalId);
            
            if (!modal) return;

            modal.classList.remove('hph-modal-overlay--active');
            this.state.activeModals.delete(modalId);
            
            if (this.state.activeModals.size === 0) {
                this.cache.body.classList.remove('hph-modal-open');
            }
            
            this.trigger('modal:closed', {modalId});
        },

        closeTopModal() {
            if (this.state.activeModals.size > 0) {
                const modalIds = Array.from(this.state.activeModals);
                this.closeModal(modalIds[modalIds.length - 1]);
            }
        },

        /**
         * Toast notification system
         */
        showToast(message, type = 'info', duration = null) {
            if (!this.cache.toastContainer) {
                this.createToastContainer();
            }

            const toast = this.createToastElement(message, type);
            this.cache.toastContainer.appendChild(toast);
            
            // Trigger entrance animation
            setTimeout(() => {
                toast.classList.add('hph-toast--entering');
                toast.classList.add('hph-toast--entered');
            }, 10);

            // Auto remove
            const removeDuration = duration || this.config.notificationDuration;
            setTimeout(() => {
                this.removeToast(toast);
            }, removeDuration);

            return toast;
        },

        createToastContainer() {
            if (!this.cache.toastContainer) {
                this.cache.toastContainer = document.createElement('div');
                this.cache.toastContainer.id = 'hph-toast-container';
                this.cache.toastContainer.className = 'hph-toast-container';
                document.body.appendChild(this.cache.toastContainer);
            }
        },

        createToastElement(message, type) {
            const toast = document.createElement('div');
            toast.className = `hph-toast hph-toast--${type}`;
            
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                warning: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };
            
            toast.innerHTML = `
                <div class="hph-toast-icon">
                    <i class="fas ${icons[type] || icons.info}"></i>
                </div>
                <div class="hph-toast-content">
                    <div class="hph-toast-message">${message}</div>
                </div>
                <button class="hph-toast-close" aria-label="Close notification">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            // Add close functionality
            const closeBtn = toast.querySelector('.hph-toast-close');
            closeBtn.addEventListener('click', () => {
                this.removeToast(toast);
            });
            
            return toast;
        },

        removeToast(toast) {
            toast.classList.add('hph-toast--exiting');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        },

        /**
         * Form validation
         */
        validateForm(form) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!this.validateField(field)) {
                    isValid = false;
                }
            });
            
            return isValid;
        },

        validateField(field) {
            const value = field.value.trim();
            const type = field.type;
            let isValid = true;
            
            // Remove previous error states
            field.classList.remove('hph-form-input--error');
            this.removeFieldError(field);
            
            // Required validation
            if (field.required && !value) {
                isValid = false;
                this.addFieldError(field, 'This field is required.');
            }
            
            // Type-specific validation
            if (value && isValid) {
                switch (type) {
                    case 'email':
                        if (!this.isValidEmail(value)) {
                            isValid = false;
                            this.addFieldError(field, 'Please enter a valid email address.');
                        }
                        break;
                    case 'tel':
                        if (!this.isValidPhone(value)) {
                            isValid = false;
                            this.addFieldError(field, 'Please enter a valid phone number.');
                        }
                        break;
                    case 'url':
                        if (!this.isValidUrl(value)) {
                            isValid = false;
                            this.addFieldError(field, 'Please enter a valid URL.');
                        }
                        break;
                }
            }
            
            if (!isValid) {
                field.classList.add('hph-form-input--error');
            }
            
            return isValid;
        },

        addFieldError(field, message) {
            const errorElement = document.createElement('div');
            errorElement.className = 'hph-form-error';
            errorElement.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
            
            field.parentNode.appendChild(errorElement);
        },

        removeFieldError(field) {
            const existingError = field.parentNode.querySelector('.hph-form-error');
            if (existingError) {
                existingError.remove();
            }
        },

        /**
         * Auto-save functionality
         */
        initAutoSave() {
            const forms = document.querySelectorAll('.hph-dashboard-form[data-auto-save]');
            
            forms.forEach(form => {
                const inputs = form.querySelectorAll('input, textarea, select');
                
                inputs.forEach(input => {
                    input.addEventListener('input', () => {
                        this.scheduleAutoSave(form);
                    });
                });
            });
        },

        scheduleAutoSave(form) {
            if (this.state.autoSaveTimer) {
                clearTimeout(this.state.autoSaveTimer);
            }
            
            this.state.autoSaveTimer = setTimeout(() => {
                this.performAutoSave(form);
            }, this.config.autoSaveInterval);
        },

        performAutoSave(form) {
            const formData = new FormData(form);
            formData.append('action', 'hph_auto_save');
            formData.append('nonce', this.config.nonce);
            
            fetch(this.config.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    this.showAutoSaveIndicator();
                }
            })
            .catch(error => {
                this.log('Auto-save error:', error);
            });
        },

        showAutoSaveIndicator() {
            const indicator = document.querySelector('.hph-auto-save-indicator');
            if (indicator) {
                indicator.textContent = 'Draft saved';
                indicator.classList.add('hph-auto-save-indicator--visible');
                
                setTimeout(() => {
                    indicator.classList.remove('hph-auto-save-indicator--visible');
                }, 2000);
            }
        },

        /**
         * File upload handling
         */
        initFileUploads() {
            const fileInputs = document.querySelectorAll('input[type="file"]');
            
            fileInputs.forEach(input => {
                input.addEventListener('change', (e) => {
                    this.handleFileUpload(e.target);
                });
            });
            
            // Drag and drop functionality
            const dropZones = document.querySelectorAll('.hph-file-upload');
            
            dropZones.forEach(zone => {
                zone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    zone.classList.add('hph-file-upload--dragover');
                });
                
                zone.addEventListener('dragleave', () => {
                    zone.classList.remove('hph-file-upload--dragover');
                });
                
                zone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    zone.classList.remove('hph-file-upload--dragover');
                    
                    const files = e.dataTransfer.files;
                    const input = zone.querySelector('input[type="file"]');
                    
                    if (input && files.length > 0) {
                        input.files = files;
                        this.handleFileUpload(input);
                    }
                });
            });
        },

        handleFileUpload(input) {
            const files = input.files;
            const previewContainer = input.closest('.hph-file-upload').querySelector('.hph-file-preview');
            
            if (previewContainer) {
                this.createFilePreviews(files, previewContainer);
            }
            
            // Handle avatar uploads specifically
            if (input.dataset.preview) {
                this.handleAvatarPreview(input, input.dataset.preview);
            }
        },

        createFilePreviews(files, container) {
            container.innerHTML = '';
            
            Array.from(files).forEach(file => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const preview = document.createElement('div');
                        preview.className = 'hph-file-preview-item';
                        preview.innerHTML = `
                            <img src="${e.target.result}" class="hph-file-preview-image" alt="${file.name}">
                            <button type="button" class="hph-file-preview-remove" onclick="this.parentNode.remove()">
                                <i class="fas fa-times"></i>
                            </button>
                        `;
                        container.appendChild(preview);
                    };
                    reader.readAsDataURL(file);
                }
            });
        },

        handleAvatarPreview(input, previewId) {
            const file = input.files[0];
            const preview = document.getElementById(previewId);
            
            if (file && preview) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    if (preview.tagName === 'IMG') {
                        preview.src = e.target.result;
                    } else {
                        // Replace placeholder with image
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = preview.className;
                        img.id = preview.id;
                        preview.parentNode.replaceChild(img, preview);
                    }
                };
                reader.readAsDataURL(file);
            }
        },

        /**
         * Real-time validation
         */
        initRealTimeValidation() {
            const inputs = document.querySelectorAll('.hph-form-input, .hph-form-textarea, .hph-form-select');
            
            inputs.forEach(input => {
                input.addEventListener('blur', () => {
                    if (input.value.trim()) {
                        this.validateField(input);
                    }
                });
                
                input.addEventListener('input', () => {
                    if (input.classList.contains('hph-form-input--error')) {
                        this.validateField(input);
                    }
                });
            });
        },

        /**
         * Live time updates
         */
        initLiveTime() {
            const liveTimeElements = document.querySelectorAll('[data-live-time="true"]');
            
            if (liveTimeElements.length > 0) {
                setInterval(() => {
                    const now = new Date();
                    const timeString = now.toLocaleTimeString('en-US', {
                        hour: 'numeric',
                        minute: '2-digit',
                        hour12: true
                    });
                    
                    liveTimeElements.forEach(element => {
                        element.textContent = timeString;
                    });
                }, 60000); // Update every minute
            }
        },

        /**
         * Chart initialization
         */
        initPerformanceCharts() {
            const chartCanvas = document.getElementById('hph-views-chart');
            if (!chartCanvas) return;
            
            // Hide placeholder
            const placeholder = chartCanvas.parentNode.querySelector('.hph-chart-placeholder');
            if (placeholder) {
                placeholder.style.display = 'none';
            }
            
            // Sample data - replace with actual data
            const ctx = chartCanvas.getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: this.generateDateLabels(30),
                    datasets: [{
                        label: 'Views',
                        data: this.generateSampleData(30, 10, 100),
                        borderColor: this.config.chartColors.primary,
                        backgroundColor: this.config.chartColors.primary + '20',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f3f4f6'
                            }
                        },
                        x: {
                            grid: {
                                color: '#f3f4f6'
                            }
                        }
                    }
                }
            });
        },

        /**
         * Progress bar animations
         */
        initProgressBars() {
            const progressBars = document.querySelectorAll('.hph-progress-fill');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const progressBar = entry.target;
                        const width = progressBar.style.width;
                        progressBar.style.width = '0%';
                        
                        setTimeout(() => {
                            progressBar.style.width = width;
                        }, 100);
                        
                        observer.unobserve(progressBar);
                    }
                });
            });
            
            progressBars.forEach(bar => observer.observe(bar));
        },

        /**
         * Counter animations
         */
        initCounters() {
            const counters = document.querySelectorAll('.hph-stat-value, .hph-metric-value');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.animateCounter(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            });
            
            counters.forEach(counter => observer.observe(counter));
        },

        animateCounter(element) {
            const text = element.textContent.trim();
            const number = parseInt(text.replace(/[^\d]/g, ''));
            
            if (isNaN(number)) return;
            
            const duration = 1000;
            const steps = 20;
            const increment = number / steps;
            let current = 0;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= number) {
                    current = number;
                    clearInterval(timer);
                }
                
                element.textContent = text.replace(number.toString(), Math.floor(current).toLocaleString());
            }, duration / steps);
        },

        /**
         * Section-specific initializations
         */
        initOverviewFeatures() {
            // Quick action buttons
            const quickActions = document.querySelectorAll('.hph-quick-action');
            quickActions.forEach(action => {
                action.addEventListener('click', (e) => {
                    e.preventDefault();
                    // Handle quick actions
                });
            });
        },

        initListingsFeatures() {
            // View toggles
            this.initViewToggles();
            
            // Listing actions
            this.initListingActions();
        },

        initPerformanceFeatures() {
            // Period toggles
            const periodButtons = document.querySelectorAll('.hph-period-btn');
            periodButtons.forEach(button => {
                button.addEventListener('click', () => {
                    this.updateChartPeriod(button.dataset.period);
                });
            });
        },

        initOpenHousesFeatures() {
            // Calendar initialization would go here
            this.initCalendar();
        },

        initLeadsFeatures() {
            // Lead-specific functionality
            this.initLeadActions();
        },

        initProfileFeatures() {
            // Profile-specific functionality
            this.initProfileActions();
        },

        /**
         * Handle form submission
         */
        handleFormSubmit(form) {
            if (!form) return;
            
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.innerHTML : '';

            // Show loading state
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                submitBtn.disabled = true;
            }

            // Make AJAX request
            fetch(this.config.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showToast(data.data.message || 'Saved successfully!', 'success');
                    
                    // Handle redirect
                    if (data.data.redirect) {
                        window.location.href = data.data.redirect;
                    }
                    
                    // Trigger form submitted event
                    this.trigger('form:submitted', { form, data });
                } else {
                    this.showToast(data.data.message || 'Error saving form.', 'error');
                }
            })
            .catch(error => {
                console.error('Form submission error:', error);
                this.showToast('Failed to save. Please try again.', 'error');
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            });
        },

        /**
         * Utility functions
         */
        generateDateLabels(days) {
            const labels = [];
            for (let i = days - 1; i >= 0; i--) {
                const date = new Date();
                date.setDate(date.getDate() - i);
                labels.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
            }
            return labels;
        },

        generateSampleData(count, min, max) {
            return Array.from({ length: count }, () => 
                Math.floor(Math.random() * (max - min + 1)) + min
            );
        },

        isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },

        isValidPhone(phone) {
            return /^[\+]?[1-9][\d]{0,15}$/.test(phone.replace(/\D/g, ''));
        },

        isValidUrl(url) {
            try {
                new URL(url);
                return true;
            } catch {
                return false;
            }
        },

        log(...args) {
            if (this.config.debug) {
                console.log('[HPH Dashboard]', ...args);
            }
        },

        trigger(eventName, data = {}) {
            const event = new CustomEvent(eventName, { detail: data });
            document.dispatchEvent(event);
        },

        // Additional utility methods...
        showLoadingOverlay(message = 'Loading...') {
            const overlay = document.getElementById('hph-loading-overlay');
            if (overlay) {
                overlay.querySelector('.hph-loading-message').textContent = message;
                overlay.classList.add('hph-loading-overlay--active');
            }
        },

        hideLoadingOverlay() {
            const overlay = document.getElementById('hph-loading-overlay');
            if (overlay) {
                overlay.classList.remove('hph-loading-overlay--active');
            }
        },

        /**
         * Handle responsive breakpoint changes
         */
        handleResponsiveChange() {
            // Close mobile menu when switching to desktop
            if (!this.state.isMobile && this.isMobileMenuOpen()) {
                this.closeMobileMenu();
            }
            
            // Log responsive change for debugging
            this.log(`Responsive change: ${this.state.isMobile ? 'Mobile' : 'Desktop'} mode`);
            
            // Trigger responsive change event
            this.trigger('responsive:changed', {
                isMobile: this.state.isMobile,
                breakpoint: this.state.isMobile ? 'mobile' : 'desktop'
            });
        },

    };

    /**
     * Initialize when DOM is ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            HphDashboard.init();
            DashboardTabs.init(); // Also initialize the tabs system
        });
    } else {
        HphDashboard.init();
        DashboardTabs.init(); // Also initialize the tabs system
    }

    // Make both objects globally available
    window.HphDashboard = HphDashboard;
    window.DashboardTabs = DashboardTabs;

})(window, document);