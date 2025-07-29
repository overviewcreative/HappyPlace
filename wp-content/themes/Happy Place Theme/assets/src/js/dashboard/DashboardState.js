/**
 * Dashboard State Management System
 * 
 * Centralized state management for the agent dashboard with 
 * subscription-based updates and data synchronization.
 * 
 * @since 3.0.0
 */

class DashboardState {
    constructor() {
        this.state = {
            // Current navigation state
            currentSection: 'overview',
            currentSubsection: null,
            
            // User context
            user: {
                id: window.hphAjax?.user_id || 0,
                is_admin: window.hphAjax?.is_admin || false,
                capabilities: []
            },
            
            // Data collections
            listings: [],
            leads: [],
            openHouses: [],
            
            // Analytics data
            performance: {
                views: [],
                inquiries: [],
                conversions: []
            },
            
            // UI state
            ui: {
                loading: false,
                errors: [],
                notifications: [],
                modals: new Set(),
                filters: {},
                searchQuery: '',
                sortBy: 'date',
                sortOrder: 'DESC',
                viewMode: 'grid'
            },
            
            // Cache metadata
            cache: {
                lastUpdated: {},
                ttl: {}
            }
        };
        
        this.subscribers = new Map();
        this.middleware = [];
        this.history = [];
        
        this.init();
    }
    
    /**
     * Initialize state management
     */
    init() {
        this.setupPersistence();
        this.restoreState();
        this.bindEvents();
        
        // Log state initialization
        this.logStateChange('init', null, this.state);
    }
    
    /**
     * Setup state persistence
     */
    setupPersistence() {
        // Save state periodically
        setInterval(() => {
            this.persistState();
        }, 30000); // Every 30 seconds
        
        // Save state on page unload
        window.addEventListener('beforeunload', () => {
            this.persistState();
        });
    }
    
    /**
     * Restore state from localStorage
     */
    restoreState() {
        try {
            const saved = localStorage.getItem('hph_dashboard_state');
            if (saved) {
                const parsed = JSON.parse(saved);
                
                // Only restore certain parts of state
                const restorableKeys = ['currentSection', 'ui.filters', 'ui.viewMode', 'ui.sortBy', 'ui.sortOrder'];
                
                restorableKeys.forEach(keyPath => {
                    const value = this.getNestedValue(parsed, keyPath);
                    if (value !== undefined) {
                        this.setNestedValue(this.state, keyPath, value);
                    }
                });
                
                console.log('Dashboard state restored from localStorage');
            }
        } catch (error) {
            console.warn('Failed to restore dashboard state:', error);
        }
    }
    
    /**
     * Persist state to localStorage
     */
    persistState() {
        try {
            const persistable = {
                currentSection: this.state.currentSection,
                currentSubsection: this.state.currentSubsection,
                ui: {
                    filters: this.state.ui.filters,
                    searchQuery: this.state.ui.searchQuery,
                    sortBy: this.state.ui.sortBy,
                    sortOrder: this.state.ui.sortOrder,
                    viewMode: this.state.ui.viewMode
                },
                timestamp: Date.now()
            };
            
            localStorage.setItem('hph_dashboard_state', JSON.stringify(persistable));
        } catch (error) {
            console.warn('Failed to persist dashboard state:', error);
        }
    }
    
    /**
     * Bind global events
     */
    bindEvents() {
        // Listen for popstate (browser back/forward)
        window.addEventListener('popstate', (event) => {
            if (event.state?.section) {
                this.setCurrentSection(event.state.section);
            }
        });
        
        // Listen for visibility changes
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                // Page became visible, refresh data if needed
                this.checkForStaleData();
            }
        });
    }
    
    // ===================
    // GETTERS
    // ===================
    
    /**
     * Get current section
     */
    getCurrentSection() {
        return this.state.currentSection;
    }
    
    /**
     * Get current subsection
     */
    getCurrentSubsection() {
        return this.state.currentSubsection;
    }
    
    /**
     * Get user context
     */
    getUser() {
        return { ...this.state.user };
    }
    
    /**
     * Get listings
     */
    getListings() {
        return [...this.state.listings];
    }
    
    /**
     * Get specific listing by ID
     */
    getListing(id) {
        return this.state.listings.find(listing => listing.id === parseInt(id));
    }
    
    /**
     * Get leads
     */
    getLeads() {
        return [...this.state.leads];
    }
    
    /**
     * Get UI state
     */
    getUI() {
        return { ...this.state.ui };
    }
    
    /**
     * Get performance data
     */
    getPerformance() {
        return { ...this.state.performance };
    }
    
    /**
     * Get filtered and sorted listings
     */
    getFilteredListings() {
        let filtered = [...this.state.listings];
        
        // Apply search filter
        if (this.state.ui.searchQuery) {
            const query = this.state.ui.searchQuery.toLowerCase();
            filtered = filtered.filter(listing => 
                listing.title.toLowerCase().includes(query) ||
                listing.address.toLowerCase().includes(query) ||
                (listing.description && listing.description.toLowerCase().includes(query))
            );
        }
        
        // Apply other filters
        Object.entries(this.state.ui.filters).forEach(([key, value]) => {
            if (value && value !== '') {
                filtered = filtered.filter(listing => {
                    if (Array.isArray(value)) {
                        return value.includes(listing[key]);
                    }
                    return listing[key] === value;
                });
            }
        });
        
        // Apply sorting
        filtered.sort((a, b) => {
            const aVal = a[this.state.ui.sortBy];
            const bVal = b[this.state.ui.sortBy];
            
            let comparison = 0;
            if (aVal > bVal) comparison = 1;
            if (aVal < bVal) comparison = -1;
            
            return this.state.ui.sortOrder === 'DESC' ? -comparison : comparison;
        });
        
        return filtered;
    }
    
    // ===================
    // SETTERS
    // ===================
    
    /**
     * Set current section
     */
    setCurrentSection(section) {
        const oldSection = this.state.currentSection;
        this.state.currentSection = section;
        
        // Update browser history
        const url = new URL(window.location);
        url.searchParams.set('section', section);
        window.history.pushState({ section }, '', url.toString());
        
        this.notify('currentSection', section, oldSection);
        this.logStateChange('setCurrentSection', { section }, { currentSection: section });
    }
    
    /**
     * Set current subsection
     */
    setCurrentSubsection(subsection) {
        const oldSubsection = this.state.currentSubsection;
        this.state.currentSubsection = subsection;
        this.notify('currentSubsection', subsection, oldSubsection);
    }
    
    /**
     * Set listings data
     */
    setListings(listings) {
        this.state.listings = listings;
        this.updateCache('listings');
        this.notify('listings', listings);
        this.logStateChange('setListings', { count: listings.length }, null);
    }
    
    /**
     * Update single listing
     */
    updateListing(listing) {
        const index = this.state.listings.findIndex(l => l.id === listing.id);
        
        if (index !== -1) {
            this.state.listings[index] = { ...this.state.listings[index], ...listing };
        } else {
            this.state.listings.push(listing);
        }
        
        this.updateCache('listings');
        this.notify('listings', this.state.listings);
        this.notify('listingUpdated', listing);
        this.logStateChange('updateListing', { id: listing.id }, null);
    }
    
    /**
     * Remove listing
     */
    removeListing(listingId) {
        const index = this.state.listings.findIndex(l => l.id === listingId);
        
        if (index !== -1) {
            const removed = this.state.listings.splice(index, 1)[0];
            this.updateCache('listings');
            this.notify('listings', this.state.listings);
            this.notify('listingRemoved', removed);
            this.logStateChange('removeListing', { id: listingId }, null);
        }
    }
    
    /**
     * Set leads data
     */
    setLeads(leads) {
        this.state.leads = leads;
        this.updateCache('leads');
        this.notify('leads', leads);
    }
    
    /**
     * Add new lead
     */
    addLead(lead) {
        this.state.leads.unshift(lead);
        this.updateCache('leads');
        this.notify('leads', this.state.leads);
        this.notify('newLead', lead);
        
        // Add notification for new lead
        this.addNotification({
            type: 'success',
            message: `New lead from ${lead.name}`,
            action: {
                label: 'View Lead',
                callback: () => this.setCurrentSection('leads')
            }
        });
    }
    
    /**
     * Set performance data
     */
    setPerformance(performance) {
        this.state.performance = { ...this.state.performance, ...performance };
        this.updateCache('performance');
        this.notify('performance', this.state.performance);
    }
    
    /**
     * Set loading state
     */
    setLoading(loading) {
        this.state.ui.loading = loading;
        this.notify('ui.loading', loading);
    }
    
    /**
     * Add error
     */
    addError(error) {
        const errorObj = {
            id: Date.now(),
            message: error.message || error,
            type: error.type || 'error',
            timestamp: new Date(),
            dismissed: false
        };
        
        this.state.ui.errors.push(errorObj);
        this.notify('ui.errors', this.state.ui.errors);
        
        // Auto-remove errors after 10 seconds
        setTimeout(() => {
            this.removeError(errorObj.id);
        }, 10000);
    }
    
    /**
     * Remove error
     */
    removeError(errorId) {
        const index = this.state.ui.errors.findIndex(e => e.id === errorId);
        if (index !== -1) {
            this.state.ui.errors.splice(index, 1);
            this.notify('ui.errors', this.state.ui.errors);
        }
    }
    
    /**
     * Add notification
     */
    addNotification(notification) {
        const notificationObj = {
            id: Date.now(),
            type: 'info',
            ...notification,
            timestamp: new Date(),
            read: false
        };
        
        this.state.ui.notifications.unshift(notificationObj);
        this.notify('ui.notifications', this.state.ui.notifications);
        
        // Auto-remove notifications after 30 seconds if no action
        if (!notification.persistent) {
            setTimeout(() => {
                this.removeNotification(notificationObj.id);
            }, 30000);
        }
    }
    
    /**
     * Remove notification
     */
    removeNotification(notificationId) {
        const index = this.state.ui.notifications.findIndex(n => n.id === notificationId);
        if (index !== -1) {
            this.state.ui.notifications.splice(index, 1);
            this.notify('ui.notifications', this.state.ui.notifications);
        }
    }
    
    /**
     * Set search query
     */
    setSearchQuery(query) {
        this.state.ui.searchQuery = query;
        this.notify('ui.searchQuery', query);
        this.notify('ui.filteredListings', this.getFilteredListings());
    }
    
    /**
     * Set filter
     */
    setFilter(key, value) {
        this.state.ui.filters[key] = value;
        this.notify('ui.filters', this.state.ui.filters);
        this.notify('ui.filteredListings', this.getFilteredListings());
    }
    
    /**
     * Clear all filters
     */
    clearFilters() {
        this.state.ui.filters = {};
        this.state.ui.searchQuery = '';
        this.notify('ui.filters', this.state.ui.filters);
        this.notify('ui.searchQuery', '');
        this.notify('ui.filteredListings', this.getFilteredListings());
    }
    
    /**
     * Set sort parameters
     */
    setSort(sortBy, sortOrder = 'DESC') {
        this.state.ui.sortBy = sortBy;
        this.state.ui.sortOrder = sortOrder;
        this.notify('ui.sort', { sortBy, sortOrder });
        this.notify('ui.filteredListings', this.getFilteredListings());
    }
    
    /**
     * Set view mode
     */
    setViewMode(mode) {
        this.state.ui.viewMode = mode;
        this.notify('ui.viewMode', mode);
    }
    
    // ===================
    // SUBSCRIPTION SYSTEM
    // ===================
    
    /**
     * Subscribe to state changes
     */
    subscribe(path, callback) {
        if (!this.subscribers.has(path)) {
            this.subscribers.set(path, new Set());
        }
        
        this.subscribers.get(path).add(callback);
        
        // Return unsubscribe function
        return () => {
            const subscribers = this.subscribers.get(path);
            if (subscribers) {
                subscribers.delete(callback);
            }
        };
    }
    
    /**
     * Notify subscribers of state changes
     */
    notify(path, newValue, oldValue = null) {
        // Apply middleware first
        this.middleware.forEach(middleware => {
            middleware(path, newValue, oldValue, this.state);
        });
        
        // Notify path-specific subscribers
        const subscribers = this.subscribers.get(path);
        if (subscribers) {
            subscribers.forEach(callback => {
                try {
                    callback(newValue, oldValue, path, this.state);
                } catch (error) {
                    console.error('State subscription error:', error);
                }
            });
        }
        
        // Notify wildcard subscribers
        const wildcardSubscribers = this.subscribers.get('*');
        if (wildcardSubscribers) {
            wildcardSubscribers.forEach(callback => {
                try {
                    callback(newValue, oldValue, path, this.state);
                } catch (error) {
                    console.error('Wildcard subscription error:', error);
                }
            });
        }
    }
    
    /**
     * Add middleware
     */
    addMiddleware(middleware) {
        this.middleware.push(middleware);
    }
    
    // ===================
    // UTILITY METHODS
    // ===================
    
    /**
     * Update cache metadata
     */
    updateCache(key) {
        this.state.cache.lastUpdated[key] = Date.now();
        this.state.cache.ttl[key] = Date.now() + (5 * 60 * 1000); // 5 minutes TTL
    }
    
    /**
     * Check if cached data is stale
     */
    isCacheStale(key) {
        const ttl = this.state.cache.ttl[key];
        return !ttl || Date.now() > ttl;
    }
    
    /**
     * Check for stale data and refresh if needed
     */
    checkForStaleData() {
        const staleKeys = Object.keys(this.state.cache.ttl).filter(key => this.isCacheStale(key));
        
        if (staleKeys.length > 0) {
            this.notify('refreshNeeded', staleKeys);
        }
    }
    
    /**
     * Get nested value from object
     */
    getNestedValue(obj, path) {
        return path.split('.').reduce((current, key) => current?.[key], obj);
    }
    
    /**
     * Set nested value in object
     */
    setNestedValue(obj, path, value) {
        const keys = path.split('.');
        const lastKey = keys.pop();
        const target = keys.reduce((current, key) => {
            if (!current[key]) current[key] = {};
            return current[key];
        }, obj);
        target[lastKey] = value;
    }
    
    /**
     * Log state changes for debugging
     */
    logStateChange(action, payload, newState) {
        if (window.hphAjax?.debug) {
            const logEntry = {
                action,
                payload,
                newState,
                timestamp: new Date().toISOString()
            };
            
            this.history.push(logEntry);
            
            // Keep only last 100 entries
            if (this.history.length > 100) {
                this.history.shift();
            }
            
            console.log('State Change:', logEntry);
        }
    }
    
    /**
     * Get state history for debugging
     */
    getStateHistory() {
        return [...this.history];
    }
    
    /**
     * Get current state snapshot
     */
    getStateSnapshot() {
        return JSON.parse(JSON.stringify(this.state));
    }
    
    /**
     * Reset state to initial values
     */
    reset() {
        const initialState = {
            currentSection: 'overview',
            currentSubsection: null,
            listings: [],
            leads: [],
            openHouses: [],
            performance: { views: [], inquiries: [], conversions: [] },
            ui: {
                loading: false,
                errors: [],
                notifications: [],
                modals: new Set(),
                filters: {},
                searchQuery: '',
                sortBy: 'date',
                sortOrder: 'DESC',
                viewMode: 'grid'
            },
            cache: { lastUpdated: {}, ttl: {} }
        };
        
        this.state = { ...this.state, ...initialState };
        this.notify('reset', this.state);
        this.logStateChange('reset', null, this.state);
    }
}

// Export for use in other modules
window.DashboardState = DashboardState;
