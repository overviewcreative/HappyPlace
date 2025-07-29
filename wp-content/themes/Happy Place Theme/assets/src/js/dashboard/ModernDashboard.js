/**
 * Modern Dashboard Core
 * 
 * Replaces placeholder implementations in dashboard-core.js with
 * production-ready components using the new modular architecture.
 * 
 * @since 3.0.0
 */

class ModernDashboard {
    constructor() {
        this.state = null;
        this.ajax = null;
        this.components = new Map();
        this.initialized = false;
        
        this.sections = {
            overview: null,
            listings: null,
            leads: null,
            analytics: null,
            tools: null,
            settings: null
        };
        
        this.router = null;
        this.eventBus = new EventTarget();
    }
    
    /**
     * Initialize the dashboard
     */
    async init() {
        if (this.initialized) return;
        
        try {
            // Initialize core systems
            this.initializeCore();
            
            // Setup routing
            this.setupRouting();
            
            // Initialize components
            await this.initializeComponents();
            
            // Setup global event listeners
            this.setupEventListeners();
            
            // Load initial data
            await this.loadInitialData();
            
            // Initialize sections
            this.initializeSections();
            
            // Navigate to initial section
            this.navigateToInitialSection();
            
            this.initialized = true;
            this.log('Dashboard initialized successfully');
            
        } catch (error) {
            console.error('Dashboard initialization failed:', error);
            this.showError('Failed to initialize dashboard');
        }
    }
    
    /**
     * Initialize core systems
     */
    initializeCore() {
        // Initialize state management
        this.state = new DashboardState();
        window.dashboardState = this.state;
        
        // Initialize AJAX handler
        this.ajax = new DashboardAjax();
        window.dashboardAjax = this.ajax;
        
        // Setup state middleware for logging and validation
        this.state.addMiddleware((path, newValue, oldValue, state) => {
            this.handleStateChange(path, newValue, oldValue, state);
        });
        
        this.log('Core systems initialized');
    }
    
    /**
     * Setup routing
     */
    setupRouting() {
        this.router = {
            currentSection: 'overview',
            navigate: (section, subsection = null) => {
                this.navigateToSection(section, subsection);
            },
            back: () => {
                window.history.back();
            }
        };
        
        // Listen for popstate events
        window.addEventListener('popstate', (event) => {
            if (event.state?.section) {
                this.state.setCurrentSection(event.state.section);
            }
        });
    }
    
    /**
     * Initialize components
     */
    async initializeComponents() {
        // Search and Filter Component
        const searchElement = document.querySelector('#dashboard-search');
        if (searchElement) {
            const searchFilter = new SearchFilter(searchElement);
            this.components.set('search', searchFilter);
        }
        
        // Flyer Generator Component
        const flyerElement = document.querySelector('#flyer-generator');
        if (flyerElement) {
            const flyerGenerator = new FlyerGenerator(flyerElement);
            this.components.set('flyer', flyerGenerator);
        }
        
        // Initialize other components from DOM
        window.ComponentRegistry.initFromDOM();
        
        this.log('Components initialized');
    }
    
    /**
     * Setup global event listeners
     */
    setupEventListeners() {
        // Navigation clicks
        document.addEventListener('click', (event) => {
            const navItem = event.target.closest('[data-section]');
            if (navItem) {
                event.preventDefault();
                const section = navItem.dataset.section;
                const subsection = navItem.dataset.subsection;
                this.navigateToSection(section, subsection);
            }
        });
        
        // Refresh button
        document.addEventListener('click', (event) => {
            if (event.target.closest('.refresh-data')) {
                event.preventDefault();
                this.refreshCurrentSection();
            }
        });
        
        // Global keyboard shortcuts
        document.addEventListener('keydown', (event) => {
            this.handleKeyboardShortcuts(event);
        });
        
        // Window resize
        window.addEventListener('resize', () => {
            this.handleWindowResize();
        });
        
        // Online/offline status
        window.addEventListener('online', () => {
            this.handleOnlineStatus(true);
        });
        
        window.addEventListener('offline', () => {
            this.handleOnlineStatus(false);
        });
        
        this.log('Event listeners setup complete');
    }
    
    /**
     * Load initial data
     */
    async loadInitialData() {
        try {
            this.state.setLoading(true);
            
            // Load dashboard stats
            const statsResult = await this.ajax.getDashboardStats();
            if (statsResult.success) {
                this.updateDashboardStats(statsResult.data);
            }
            
            // Load listings
            const listingsResult = await this.ajax.getListings();
            if (listingsResult.success) {
                this.state.setListings(listingsResult.data.listings);
            }
            
            // Load recent leads
            const leadsResult = await this.ajax.getLeads({ limit: 10 });
            if (leadsResult.success) {
                this.state.setLeads(leadsResult.data.leads);
            }
            
            // Load user preferences
            const prefsResult = await this.ajax.getPreferences();
            if (prefsResult.success) {
                this.applyUserPreferences(prefsResult.data);
            }
            
            this.log('Initial data loaded');
            
        } catch (error) {
            console.error('Failed to load initial data:', error);
            this.state.addError('Failed to load dashboard data');
        } finally {
            this.state.setLoading(false);
        }
    }
    
    /**
     * Initialize sections
     */
    initializeSections() {
        // Overview Section
        this.sections.overview = {
            element: document.querySelector('#overview-section'),
            load: () => this.loadOverviewSection(),
            refresh: () => this.refreshOverviewSection()
        };
        
        // Listings Section
        this.sections.listings = {
            element: document.querySelector('#listings-section'),
            load: () => this.loadListingsSection(),
            refresh: () => this.refreshListingsSection()
        };
        
        // Leads Section
        this.sections.leads = {
            element: document.querySelector('#leads-section'),
            load: () => this.loadLeadsSection(),
            refresh: () => this.refreshLeadsSection()
        };
        
        // Analytics Section
        this.sections.analytics = {
            element: document.querySelector('#analytics-section'),
            load: () => this.loadAnalyticsSection(),
            refresh: () => this.refreshAnalyticsSection()
        };
        
        // Tools Section
        this.sections.tools = {
            element: document.querySelector('#tools-section'),
            load: () => this.loadToolsSection(),
            refresh: () => this.refreshToolsSection()
        };
        
        // Settings Section
        this.sections.settings = {
            element: document.querySelector('#settings-section'),
            load: () => this.loadSettingsSection(),
            refresh: () => this.refreshSettingsSection()
        };
        
        this.log('Sections initialized');
    }
    
    /**
     * Navigate to initial section
     */
    navigateToInitialSection() {
        // Check URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const section = urlParams.get('section') || this.state.getCurrentSection();
        
        this.navigateToSection(section);
    }
    
    /**
     * Navigate to specific section
     */
    navigateToSection(sectionName, subsection = null) {
        if (!this.sections[sectionName]) {
            console.warn(`Unknown section: ${sectionName}`);
            return;
        }
        
        try {
            // Hide current section
            this.hideCurrentSection();
            
            // Update state
            this.state.setCurrentSection(sectionName);
            if (subsection) {
                this.state.setCurrentSubsection(subsection);
            }
            
            // Update navigation UI
            this.updateNavigationUI(sectionName);
            
            // Load and show new section
            this.loadSection(sectionName);
            
            // Update browser history
            this.updateBrowserHistory(sectionName, subsection);
            
            this.log(`Navigated to section: ${sectionName}`);
            
        } catch (error) {
            console.error(`Failed to navigate to section ${sectionName}:`, error);
            this.state.addError(`Failed to load ${sectionName} section`);
        }
    }
    
    /**
     * Hide current section
     */
    hideCurrentSection() {
        document.querySelectorAll('.dashboard-section').forEach(section => {
            section.classList.add('hidden');
        });
    }
    
    /**
     * Load specific section
     */
    async loadSection(sectionName) {
        const section = this.sections[sectionName];
        if (!section) return;
        
        try {
            // Show section element
            if (section.element) {
                section.element.classList.remove('hidden');
            }
            
            // Load section data
            if (section.load) {
                await section.load();
            }
            
        } catch (error) {
            console.error(`Failed to load section ${sectionName}:`, error);
            this.state.addError(`Failed to load ${sectionName} section`);
        }
    }
    
    /**
     * Update navigation UI
     */
    updateNavigationUI(activeSection) {
        // Update navigation menu
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
        });
        
        const activeNavItem = document.querySelector(`[data-section="${activeSection}"]`);
        if (activeNavItem) {
            activeNavItem.classList.add('active');
        }
        
        // Update page title
        this.updatePageTitle(activeSection);
        
        // Update breadcrumbs
        this.updateBreadcrumbs(activeSection);
    }
    
    /**
     * Update page title
     */
    updatePageTitle(section) {
        const titles = {
            overview: 'Dashboard Overview',
            listings: 'Property Listings',
            leads: 'Lead Management',
            analytics: 'Analytics & Reports',
            tools: 'Marketing Tools',
            settings: 'Settings'
        };
        
        const title = titles[section] || 'Dashboard';
        document.title = `${title} - Happy Place Housing`;
        
        const pageTitle = document.querySelector('.page-title');
        if (pageTitle) {
            pageTitle.textContent = title;
        }
    }
    
    /**
     * Update breadcrumbs
     */
    updateBreadcrumbs(section) {
        const breadcrumbs = document.querySelector('.breadcrumbs');
        if (!breadcrumbs) return;
        
        const sectionNames = {
            overview: 'Overview',
            listings: 'Listings',
            leads: 'Leads',
            analytics: 'Analytics',
            tools: 'Tools',
            settings: 'Settings'
        };
        
        breadcrumbs.innerHTML = `
            <span class="breadcrumb-item">
                <a href="?section=overview">Dashboard</a>
            </span>
            <span class="breadcrumb-separator">›</span>
            <span class="breadcrumb-item active">
                ${sectionNames[section] || section}
            </span>
        `;
    }
    
    /**
     * Update browser history
     */
    updateBrowserHistory(section, subsection) {
        const url = new URL(window.location);
        url.searchParams.set('section', section);
        
        if (subsection) {
            url.searchParams.set('subsection', subsection);
        } else {
            url.searchParams.delete('subsection');
        }
        
        window.history.pushState(
            { section, subsection },
            '',
            url.toString()
        );
    }
    
    // ===================
    // SECTION LOADERS
    // ===================
    
    /**
     * Load overview section
     */
    async loadOverviewSection() {
        try {
            // Load dashboard stats
            const result = await this.ajax.getDashboardStats();
            if (result.success) {
                this.updateDashboardStats(result.data);
            }
            
            // Update quick stats
            this.updateQuickStats();
            
            // Load recent activity
            this.loadRecentActivity();
            
        } catch (error) {
            console.error('Failed to load overview section:', error);
        }
    }
    
    /**
     * Load listings section
     */
    async loadListingsSection() {
        try {
            // Refresh listings if data is stale
            if (this.state.isCacheStale('listings')) {
                const result = await this.ajax.getListings();
                if (result.success) {
                    this.state.setListings(result.data.listings);
                }
            }
            
            // Update listings display
            this.updateListingsDisplay();
            
        } catch (error) {
            console.error('Failed to load listings section:', error);
        }
    }
    
    /**
     * Load leads section
     */
    async loadLeadsSection() {
        try {
            // Refresh leads if data is stale
            if (this.state.isCacheStale('leads')) {
                const result = await this.ajax.getLeads();
                if (result.success) {
                    this.state.setLeads(result.data.leads);
                }
            }
            
            // Update leads display
            this.updateLeadsDisplay();
            
        } catch (error) {
            console.error('Failed to load leads section:', error);
        }
    }
    
    /**
     * Load analytics section
     */
    async loadAnalyticsSection() {
        try {
            // Load analytics data
            const result = await this.ajax.getAnalytics();
            if (result.success) {
                this.state.setPerformance(result.data);
                this.updateAnalyticsDisplay();
            }
            
        } catch (error) {
            console.error('Failed to load analytics section:', error);
        }
    }
    
    /**
     * Load tools section
     */
    async loadToolsSection() {
        // Tools section is mostly static components
        // Initialize any dynamic tool components here
    }
    
    /**
     * Load settings section
     */
    async loadSettingsSection() {
        try {
            // Load user preferences
            const result = await this.ajax.getPreferences();
            if (result.success) {
                this.updateSettingsForm(result.data);
            }
            
        } catch (error) {
            console.error('Failed to load settings section:', error);
        }
    }
    
    // ===================
    // DISPLAY UPDATERS
    // ===================
    
    /**
     * Update dashboard stats
     */
    updateDashboardStats(stats) {
        // Update stat cards
        const statElements = {
            'total-listings': stats.total_listings || 0,
            'active-listings': stats.active_listings || 0,
            'pending-leads': stats.pending_leads || 0,
            'monthly-views': stats.monthly_views || 0
        };
        
        Object.entries(statElements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                this.animateNumber(element, value);
            }
        });
    }
    
    /**
     * Update quick stats
     */
    updateQuickStats() {
        const listings = this.state.getListings();
        const leads = this.state.getLeads();
        
        // Calculate quick stats
        const activeListings = listings.filter(l => l.status === 'active').length;
        const newLeads = leads.filter(l => {
            const leadDate = new Date(l.created_at);
            const yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);
            return leadDate > yesterday;
        }).length;
        
        // Update display
        this.updateStatCard('active-listings-quick', activeListings);
        this.updateStatCard('new-leads-quick', newLeads);
    }
    
    /**
     * Update stat card with animation
     */
    updateStatCard(id, value) {
        const element = document.getElementById(id);
        if (element) {
            this.animateNumber(element, value);
        }
    }
    
    /**
     * Animate number change
     */
    animateNumber(element, targetValue) {
        const currentValue = parseInt(element.textContent) || 0;
        const increment = (targetValue - currentValue) / 20;
        let current = currentValue;
        
        const animate = () => {
            current += increment;
            
            if ((increment > 0 && current >= targetValue) || 
                (increment < 0 && current <= targetValue)) {
                element.textContent = targetValue;
            } else {
                element.textContent = Math.round(current);
                requestAnimationFrame(animate);
            }
        };
        
        if (increment !== 0) {
            animate();
        }
    }
    
    /**
     * Update listings display
     */
    updateListingsDisplay() {
        const listings = this.state.getFilteredListings();
        const container = document.querySelector('#listings-container');
        
        if (!container) return;
        
        if (listings.length === 0) {
            container.innerHTML = this.getEmptyListingsHTML();
            return;
        }
        
        container.innerHTML = listings.map(listing => 
            this.getListingCardHTML(listing)
        ).join('');
        
        // Initialize listing card interactions
        this.initializeListingCards();
    }
    
    /**
     * Update leads display
     */
    updateLeadsDisplay() {
        const leads = this.state.getLeads();
        const container = document.querySelector('#leads-container');
        
        if (!container) return;
        
        if (leads.length === 0) {
            container.innerHTML = this.getEmptyLeadsHTML();
            return;
        }
        
        container.innerHTML = leads.map(lead => 
            this.getLeadCardHTML(lead)
        ).join('');
        
        // Initialize lead card interactions
        this.initializeLeadCards();
    }
    
    /**
     * Update analytics display
     */
    updateAnalyticsDisplay() {
        const performance = this.state.getPerformance();
        
        // Update charts
        this.updateViewsChart(performance.views);
        this.updateInquiriesChart(performance.inquiries);
        this.updateConversionsChart(performance.conversions);
    }
    
    /**
     * Update settings form
     */
    updateSettingsForm(preferences) {
        // Populate form fields with current preferences
        Object.entries(preferences).forEach(([key, value]) => {
            const field = document.querySelector(`[name="${key}"]`);
            if (field) {
                if (field.type === 'checkbox') {
                    field.checked = value;
                } else {
                    field.value = value;
                }
            }
        });
    }
    
    // ===================
    // EVENT HANDLERS
    // ===================
    
    /**
     * Handle state changes
     */
    handleStateChange(path, newValue, oldValue, state) {
        // Log state changes in debug mode
        if (window.hphAjax?.debug) {
            console.log('State changed:', { path, newValue, oldValue });
        }
        
        // Handle specific state changes
        switch (path) {
            case 'listings':
                this.updateListingsDisplay();
                this.updateQuickStats();
                break;
            case 'leads':
                this.updateLeadsDisplay();
                this.updateQuickStats();
                break;
            case 'ui.loading':
                this.updateLoadingState(newValue);
                break;
            case 'ui.errors':
                this.updateErrorDisplay(newValue);
                break;
            case 'ui.notifications':
                this.updateNotificationDisplay(newValue);
                break;
        }
        
        // Emit custom event
        this.eventBus.dispatchEvent(new CustomEvent('stateChange', {
            detail: { path, newValue, oldValue, state }
        }));
    }
    
    /**
     * Handle keyboard shortcuts
     */
    handleKeyboardShortcuts(event) {
        // Only handle shortcuts when not in input fields
        if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA') {
            return;
        }
        
        if (event.ctrlKey || event.metaKey) {
            switch (event.key) {
                case '1':
                    event.preventDefault();
                    this.navigateToSection('overview');
                    break;
                case '2':
                    event.preventDefault();
                    this.navigateToSection('listings');
                    break;
                case '3':
                    event.preventDefault();
                    this.navigateToSection('leads');
                    break;
                case '4':
                    event.preventDefault();
                    this.navigateToSection('analytics');
                    break;
                case 'r':
                    event.preventDefault();
                    this.refreshCurrentSection();
                    break;
            }
        }
    }
    
    /**
     * Handle window resize
     */
    handleWindowResize() {
        // Update responsive layout
        this.updateResponsiveLayout();
        
        // Refresh charts if analytics section is active
        if (this.state.getCurrentSection() === 'analytics') {
            setTimeout(() => {
                this.updateAnalyticsDisplay();
            }, 100);
        }
    }
    
    /**
     * Handle online/offline status
     */
    handleOnlineStatus(isOnline) {
        if (isOnline) {
            this.state.addNotification({
                type: 'success',
                message: 'Connection restored',
                timeout: 3000
            });
            
            // Refresh stale data
            this.state.checkForStaleData();
        } else {
            this.state.addNotification({
                type: 'warning',
                message: 'Connection lost - working offline',
                persistent: true
            });
        }
    }
    
    // ===================
    // HELPER METHODS
    // ===================
    
    /**
     * Refresh current section
     */
    async refreshCurrentSection() {
        const currentSection = this.state.getCurrentSection();
        const section = this.sections[currentSection];
        
        if (section?.refresh) {
            try {
                this.state.setLoading(true);
                await section.refresh();
                
                this.state.addNotification({
                    type: 'success',
                    message: 'Section refreshed',
                    timeout: 2000
                });
            } catch (error) {
                console.error('Failed to refresh section:', error);
                this.state.addError('Failed to refresh section');
            } finally {
                this.state.setLoading(false);
            }
        }
    }
    
    /**
     * Apply user preferences
     */
    applyUserPreferences(preferences) {
        // Apply theme
        if (preferences.theme) {
            document.body.className = document.body.className.replace(/theme-\w+/g, '');
            document.body.classList.add(`theme-${preferences.theme}`);
        }
        
        // Apply view mode
        if (preferences.defaultViewMode) {
            this.state.setViewMode(preferences.defaultViewMode);
        }
        
        // Apply other preferences
        if (preferences.autoRefresh) {
            this.enableAutoRefresh(preferences.autoRefreshInterval || 300000);
        }
    }
    
    /**
     * Enable auto-refresh
     */
    enableAutoRefresh(interval) {
        setInterval(() => {
            if (!document.hidden) {
                this.state.checkForStaleData();
            }
        }, interval);
    }
    
    /**
     * Update loading state
     */
    updateLoadingState(loading) {
        const loadingIndicator = document.querySelector('.loading-indicator');
        if (loadingIndicator) {
            loadingIndicator.style.display = loading ? 'block' : 'none';
        }
        
        // Update section loading states
        document.querySelectorAll('.dashboard-section').forEach(section => {
            if (loading) {
                section.classList.add('loading');
            } else {
                section.classList.remove('loading');
            }
        });
    }
    
    /**
     * Update error display
     */
    updateErrorDisplay(errors) {
        const container = document.querySelector('#error-container');
        if (!container) return;
        
        container.innerHTML = errors.map(error => `
            <div class="error-item" data-error-id="${error.id}">
                <span class="error-message">${error.message}</span>
                <button type="button" class="error-dismiss" onclick="dashboardInstance.dismissError('${error.id}')">
                    ×
                </button>
            </div>
        `).join('');
        
        container.style.display = errors.length > 0 ? 'block' : 'none';
    }
    
    /**
     * Update notification display
     */
    updateNotificationDisplay(notifications) {
        const container = document.querySelector('#notification-container');
        if (!container) return;
        
        container.innerHTML = notifications.map(notification => `
            <div class="notification-item notification-${notification.type}" data-notification-id="${notification.id}">
                <span class="notification-message">${notification.message}</span>
                ${notification.action ? `
                    <button type="button" class="notification-action" onclick="(${notification.action.callback})()">
                        ${notification.action.label}
                    </button>
                ` : ''}
                <button type="button" class="notification-dismiss" onclick="dashboardInstance.dismissNotification('${notification.id}')">
                    ×
                </button>
            </div>
        `).join('');
        
        container.style.display = notifications.length > 0 ? 'block' : 'none';
    }
    
    /**
     * Dismiss error
     */
    dismissError(errorId) {
        this.state.removeError(errorId);
    }
    
    /**
     * Dismiss notification
     */
    dismissNotification(notificationId) {
        this.state.removeNotification(notificationId);
    }
    
    /**
     * Show error message
     */
    showError(message) {
        this.state.addError(message);
    }
    
    /**
     * Log message
     */
    log(message, level = 'info') {
        if (window.hphAjax?.debug) {
            console[level](`[ModernDashboard] ${message}`);
        }
    }
    
    // ===================
    // HTML GENERATORS
    // ===================
    
    /**
     * Get empty listings HTML
     */
    getEmptyListingsHTML() {
        return `
            <div class="empty-state">
                <div class="empty-icon">🏠</div>
                <h3>No Listings Found</h3>
                <p>Add your first property listing to get started.</p>
                <button type="button" class="btn btn-primary" onclick="location.href='?page=add-listing'">
                    Add Listing
                </button>
            </div>
        `;
    }
    
    /**
     * Get listing card HTML
     */
    getListingCardHTML(listing) {
        return `
            <div class="listing-card" data-listing-id="${listing.id}">
                <div class="listing-image">
                    <img src="${listing.featured_image || '/wp-content/themes/Happy Place Theme/assets/images/placeholder-home.jpg'}" 
                         alt="${listing.address}" loading="lazy">
                    <div class="listing-status status-${listing.status}">
                        ${listing.status}
                    </div>
                </div>
                <div class="listing-info">
                    <h3 class="listing-address">${listing.address}</h3>
                    <p class="listing-price">$${parseInt(listing.price || 0).toLocaleString()}</p>
                    <div class="listing-details">
                        <span>${listing.bedrooms || 0} bed</span>
                        <span>${listing.bathrooms || 0} bath</span>
                        <span>${listing.sqft ? parseInt(listing.sqft).toLocaleString() + ' sq ft' : ''}</span>
                    </div>
                    <div class="listing-actions">
                        <button type="button" class="btn btn-small btn-outline" onclick="window.open('${listing.permalink}', '_blank')">
                            View
                        </button>
                        <button type="button" class="btn btn-small" onclick="location.href='?page=edit-listing&id=${listing.id}'">
                            Edit
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * Get empty leads HTML
     */
    getEmptyLeadsHTML() {
        return `
            <div class="empty-state">
                <div class="empty-icon">👥</div>
                <h3>No Leads Yet</h3>
                <p>Leads will appear here when people contact you about your listings.</p>
            </div>
        `;
    }
    
    /**
     * Get lead card HTML
     */
    getLeadCardHTML(lead) {
        return `
            <div class="lead-card" data-lead-id="${lead.id}">
                <div class="lead-header">
                    <h4 class="lead-name">${lead.name}</h4>
                    <span class="lead-date">${new Date(lead.created_at).toLocaleDateString()}</span>
                </div>
                <div class="lead-info">
                    <p class="lead-contact">
                        📧 ${lead.email}
                        ${lead.phone ? `<br>📞 ${lead.phone}` : ''}
                    </p>
                    <p class="lead-property">
                        Interested in: <strong>${lead.property_address}</strong>
                    </p>
                    ${lead.message ? `<p class="lead-message">"${lead.message}"</p>` : ''}
                </div>
                <div class="lead-actions">
                    <button type="button" class="btn btn-small btn-primary" onclick="location.href='mailto:${lead.email}'">
                        Email
                    </button>
                    ${lead.phone ? `
                        <button type="button" class="btn btn-small btn-outline" onclick="location.href='tel:${lead.phone}'">
                            Call
                        </button>
                    ` : ''}
                </div>
            </div>
        `;
    }
    
    // ===================
    // CHART METHODS (Stubs)
    // ===================
    
    updateViewsChart(data) {
        // Chart implementation would go here
        console.log('Updating views chart with:', data);
    }
    
    updateInquiriesChart(data) {
        // Chart implementation would go here
        console.log('Updating inquiries chart with:', data);
    }
    
    updateConversionsChart(data) {
        // Chart implementation would go here
        console.log('Updating conversions chart with:', data);
    }
    
    updateResponsiveLayout() {
        // Responsive layout updates
        console.log('Updating responsive layout');
    }
    
    initializeListingCards() {
        // Initialize listing card interactions
        console.log('Initializing listing cards');
    }
    
    initializeLeadCards() {
        // Initialize lead card interactions
        console.log('Initializing lead cards');
    }
    
    loadRecentActivity() {
        // Load recent activity for overview
        console.log('Loading recent activity');
    }
    
    // Refresh methods for sections
    refreshOverviewSection() { return this.loadOverviewSection(); }
    refreshListingsSection() { return this.loadListingsSection(); }
    refreshLeadsSection() { return this.loadLeadsSection(); }
    refreshAnalyticsSection() { return this.loadAnalyticsSection(); }
    refreshToolsSection() { return this.loadToolsSection(); }
    refreshSettingsSection() { return this.loadSettingsSection(); }
}

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.dashboardInstance = new ModernDashboard();
    window.dashboardInstance.init();
});

// Export for module use
window.ModernDashboard = ModernDashboard;
