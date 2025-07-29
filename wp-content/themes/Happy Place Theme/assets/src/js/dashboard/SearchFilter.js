/**
 * Search and Filter Component
 * 
 * Provides advanced search and filtering functionality for dashboard listings
 * with real-time updates, saved searches, and intelligent suggestions.
 * 
 * @since 3.0.0
 */

class SearchFilter extends DashboardComponent {
    constructor(element, options = {}) {
        super(element, {
            stateSubscriptions: ['listings', 'ui.filteredListings'],
            ...options
        });
        
        this.searchTimeout = null;
        this.searchDelay = 300; // ms
        this.minSearchLength = 2;
        this.maxSuggestions = 10;
        
        this.savedSearches = JSON.parse(localStorage.getItem('hph_saved_searches') || '[]');
        this.recentSearches = JSON.parse(localStorage.getItem('hph_recent_searches') || '[]');
        
        this.currentFilters = {};
        this.currentSearch = '';
        
        this.ajax = new DashboardAjax();
    }
    
    /**
     * Initialize component
     */
    onInit() {
        this.setupDOM();
        this.loadSavedFilters();
        this.updateUI();
    }
    
    /**
     * Setup DOM elements
     */
    setupDOM() {
        // Create search input if not exists
        if (!this.$('.search-input')) {
            this.createSearchInput();
        }
        
        // Create filter controls if not exists
        if (!this.$('.filter-controls')) {
            this.createFilterControls();
        }
        
        // Create suggestions dropdown
        this.createSuggestionsDropdown();
        
        // Create saved searches panel
        this.createSavedSearchesPanel();
    }
    
    /**
     * Create search input
     */
    createSearchInput() {
        const searchContainer = document.createElement('div');
        searchContainer.className = 'search-container';
        searchContainer.innerHTML = `
            <div class="search-input-wrapper">
                <input type="text" class="search-input" placeholder="Search listings..." autocomplete="off">
                <button type="button" class="search-clear" title="Clear search">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
                <button type="button" class="search-submit" title="Search">
                    <span class="dashicons dashicons-search"></span>
                </button>
            </div>
            <div class="search-suggestions"></div>
        `;
        
        this.element.appendChild(searchContainer);
    }
    
    /**
     * Create filter controls
     */
    createFilterControls() {
        const filterContainer = document.createElement('div');
        filterContainer.className = 'filter-controls';
        filterContainer.innerHTML = `
            <div class="filter-group">
                <label for="filter-status">Status:</label>
                <select id="filter-status" class="filter-select" data-filter="status">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="pending">Pending</option>
                    <option value="sold">Sold</option>
                    <option value="expired">Expired</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filter-type">Property Type:</label>
                <select id="filter-type" class="filter-select" data-filter="property_type">
                    <option value="">All Types</option>
                    <option value="single-family">Single Family</option>
                    <option value="condo">Condo</option>
                    <option value="townhouse">Townhouse</option>
                    <option value="multi-family">Multi-Family</option>
                    <option value="land">Land</option>
                    <option value="commercial">Commercial</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filter-price-min">Min Price:</label>
                <input type="number" id="filter-price-min" class="filter-input" 
                       data-filter="price_min" placeholder="$0">
            </div>
            
            <div class="filter-group">
                <label for="filter-price-max">Max Price:</label>
                <input type="number" id="filter-price-max" class="filter-input" 
                       data-filter="price_max" placeholder="Any">
            </div>
            
            <div class="filter-group">
                <label for="filter-bedrooms">Bedrooms:</label>
                <select id="filter-bedrooms" class="filter-select" data-filter="bedrooms">
                    <option value="">Any</option>
                    <option value="1">1+</option>
                    <option value="2">2+</option>
                    <option value="3">3+</option>
                    <option value="4">4+</option>
                    <option value="5">5+</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filter-bathrooms">Bathrooms:</label>
                <select id="filter-bathrooms" class="filter-select" data-filter="bathrooms">
                    <option value="">Any</option>
                    <option value="1">1+</option>
                    <option value="2">2+</option>
                    <option value="3">3+</option>
                    <option value="4">4+</option>
                </select>
            </div>
            
            <div class="filter-actions">
                <button type="button" class="filter-clear">Clear Filters</button>
                <button type="button" class="filter-save">Save Search</button>
            </div>
        `;
        
        this.element.appendChild(filterContainer);
    }
    
    /**
     * Create suggestions dropdown
     */
    createSuggestionsDropdown() {
        const dropdown = document.createElement('div');
        dropdown.className = 'search-suggestions-dropdown hidden';
        dropdown.innerHTML = `
            <div class="suggestions-section">
                <h4>Recent Searches</h4>
                <ul class="recent-searches"></ul>
            </div>
            <div class="suggestions-section">
                <h4>Suggestions</h4>
                <ul class="search-suggestions-list"></ul>
            </div>
        `;
        
        this.element.appendChild(dropdown);
    }
    
    /**
     * Create saved searches panel
     */
    createSavedSearchesPanel() {
        const panel = document.createElement('div');
        panel.className = 'saved-searches-panel';
        panel.innerHTML = `
            <h4>Saved Searches</h4>
            <div class="saved-searches-list"></div>
        `;
        
        this.element.appendChild(panel);
        this.updateSavedSearches();
    }
    
    /**
     * Bind component events
     */
    bindEvents() {
        // Search input events
        const searchInput = this.$('.search-input');
        if (searchInput) {
            this.addEventListener(searchInput, 'input', this.handleSearchInput);
            this.addEventListener(searchInput, 'focus', this.showSuggestions);
            this.addEventListener(searchInput, 'keydown', this.handleSearchKeydown);
        }
        
        // Search buttons
        const clearBtn = this.$('.search-clear');
        if (clearBtn) {
            this.addEventListener(clearBtn, 'click', this.clearSearch);
        }
        
        const submitBtn = this.$('.search-submit');
        if (submitBtn) {
            this.addEventListener(submitBtn, 'click', this.submitSearch);
        }
        
        // Filter controls
        this.$$('.filter-select, .filter-input').forEach(element => {
            this.addEventListener(element, 'change', this.handleFilterChange);
        });
        
        // Filter actions
        const clearFiltersBtn = this.$('.filter-clear');
        if (clearFiltersBtn) {
            this.addEventListener(clearFiltersBtn, 'click', this.clearFilters);
        }
        
        const saveSearchBtn = this.$('.filter-save');
        if (saveSearchBtn) {
            this.addEventListener(saveSearchBtn, 'click', this.saveCurrentSearch);
        }
        
        // Click outside to hide suggestions
        this.addEventListener(document, 'click', this.handleDocumentClick);
        
        // Suggestions clicks
        this.addEventListener(this.element, 'click', this.handleSuggestionClick);
    }
    
    /**
     * Handle search input
     */
    handleSearchInput(event) {
        const query = event.target.value;
        this.currentSearch = query;
        
        // Clear previous timeout
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
        
        // Debounce search
        this.searchTimeout = setTimeout(() => {
            this.performSearch(query);
        }, this.searchDelay);
        
        // Update suggestions
        if (query.length >= this.minSearchLength) {
            this.updateSuggestions(query);
        } else {
            this.hideSuggestions();
        }
    }
    
    /**
     * Handle search keydown
     */
    handleSearchKeydown(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            this.submitSearch();
        } else if (event.key === 'Escape') {
            this.hideSuggestions();
        }
    }
    
    /**
     * Handle filter change
     */
    handleFilterChange(event) {
        const filterKey = event.target.dataset.filter;
        const filterValue = event.target.value;
        
        if (filterValue) {
            this.currentFilters[filterKey] = filterValue;
        } else {
            delete this.currentFilters[filterKey];
        }
        
        this.applyFilters();
    }
    
    /**
     * Handle document click (hide suggestions)
     */
    handleDocumentClick(event) {
        if (!this.element.contains(event.target)) {
            this.hideSuggestions();
        }
    }
    
    /**
     * Handle suggestion click
     */
    handleSuggestionClick(event) {
        const suggestion = event.target.closest('[data-suggestion]');
        if (suggestion) {
            const query = suggestion.dataset.suggestion;
            this.applySearchQuery(query);
            this.hideSuggestions();
        }
        
        const savedSearch = event.target.closest('[data-saved-search]');
        if (savedSearch) {
            const searchId = savedSearch.dataset.savedSearch;
            this.applySavedSearch(searchId);
        }
        
        const deleteSaved = event.target.closest('.delete-saved-search');
        if (deleteSaved) {
            event.stopPropagation();
            const searchId = deleteSaved.closest('[data-saved-search]').dataset.savedSearch;
            this.deleteSavedSearch(searchId);
        }
    }
    
    /**
     * Perform search
     */
    async performSearch(query) {
        this.state.setSearchQuery(query);
        
        // Add to recent searches
        this.addToRecentSearches(query);
        
        // If query is complex, perform server search
        if (query.length >= this.minSearchLength) {
            try {
                this.setLoading(true);
                
                const result = await this.ajax.searchListings(query, this.currentFilters);
                
                if (result.success) {
                    this.state.setListings(result.data.listings);
                }
            } catch (error) {
                this.state.addError(`Search failed: ${error.message}`);
            } finally {
                this.setLoading(false);
            }
        }
    }
    
    /**
     * Apply search query
     */
    applySearchQuery(query) {
        const searchInput = this.$('.search-input');
        if (searchInput) {
            searchInput.value = query;
        }
        
        this.currentSearch = query;
        this.performSearch(query);
    }
    
    /**
     * Apply filters
     */
    applyFilters() {
        this.state.setFilter('combined', this.currentFilters);
        this.saveCurrentFilters();
    }
    
    /**
     * Clear search
     */
    clearSearch() {
        const searchInput = this.$('.search-input');
        if (searchInput) {
            searchInput.value = '';
        }
        
        this.currentSearch = '';
        this.state.setSearchQuery('');
        this.hideSuggestions();
    }
    
    /**
     * Clear filters
     */
    clearFilters() {
        // Clear form controls
        this.$$('.filter-select').forEach(select => {
            select.value = '';
        });
        
        this.$$('.filter-input').forEach(input => {
            input.value = '';
        });
        
        // Clear internal state
        this.currentFilters = {};
        
        // Update state
        this.state.clearFilters();
        
        // Clear saved filters
        localStorage.removeItem('hph_current_filters');
    }
    
    /**
     * Submit search
     */
    submitSearch() {
        const searchInput = this.$('.search-input');
        if (searchInput) {
            this.performSearch(searchInput.value);
        }
        
        this.hideSuggestions();
    }
    
    /**
     * Show suggestions
     */
    showSuggestions() {
        const dropdown = this.$('.search-suggestions-dropdown');
        if (dropdown) {
            dropdown.classList.remove('hidden');
            this.updateRecentSearches();
        }
    }
    
    /**
     * Hide suggestions
     */
    hideSuggestions() {
        const dropdown = this.$('.search-suggestions-dropdown');
        if (dropdown) {
            dropdown.classList.add('hidden');
        }
    }
    
    /**
     * Update suggestions
     */
    async updateSuggestions(query) {
        try {
            // Get listings for suggestions
            const listings = this.state.getListings();
            const suggestions = this.generateSuggestions(query, listings);
            
            const suggestionsList = this.$('.search-suggestions-list');
            if (suggestionsList) {
                suggestionsList.innerHTML = suggestions.map(suggestion => `
                    <li data-suggestion="${suggestion.text}" class="suggestion-item">
                        <span class="suggestion-text">${this.highlightMatch(suggestion.text, query)}</span>
                        <span class="suggestion-type">${suggestion.type}</span>
                    </li>
                `).join('');
            }
            
            this.showSuggestions();
        } catch (error) {
            console.error('Failed to update suggestions:', error);
        }
    }
    
    /**
     * Generate search suggestions
     */
    generateSuggestions(query, listings) {
        const suggestions = [];
        const queryLower = query.toLowerCase();
        
        // Address suggestions
        listings.forEach(listing => {
            if (listing.address && listing.address.toLowerCase().includes(queryLower)) {
                suggestions.push({
                    text: listing.address,
                    type: 'address',
                    score: this.calculateRelevanceScore(listing.address, query)
                });
            }
        });
        
        // City suggestions
        const cities = [...new Set(listings.map(l => l.city).filter(Boolean))];
        cities.forEach(city => {
            if (city.toLowerCase().includes(queryLower)) {
                suggestions.push({
                    text: city,
                    type: 'city',
                    score: this.calculateRelevanceScore(city, query)
                });
            }
        });
        
        // Property type suggestions
        const propertyTypes = [...new Set(listings.map(l => l.property_type).filter(Boolean))];
        propertyTypes.forEach(type => {
            if (type.toLowerCase().includes(queryLower)) {
                suggestions.push({
                    text: type,
                    type: 'property type',
                    score: this.calculateRelevanceScore(type, query)
                });
            }
        });
        
        // Sort by relevance and limit
        return suggestions
            .sort((a, b) => b.score - a.score)
            .slice(0, this.maxSuggestions);
    }
    
    /**
     * Calculate relevance score for suggestions
     */
    calculateRelevanceScore(text, query) {
        const textLower = text.toLowerCase();
        const queryLower = query.toLowerCase();
        
        // Exact match gets highest score
        if (textLower === queryLower) return 100;
        
        // Starts with query gets high score
        if (textLower.startsWith(queryLower)) return 80;
        
        // Contains query gets medium score
        if (textLower.includes(queryLower)) return 60;
        
        // Default score
        return 0;
    }
    
    /**
     * Highlight matching text
     */
    highlightMatch(text, query) {
        const regex = new RegExp(`(${query})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }
    
    /**
     * Update recent searches display
     */
    updateRecentSearches() {
        const recentList = this.$('.recent-searches');
        if (recentList && this.recentSearches.length > 0) {
            recentList.innerHTML = this.recentSearches.slice(0, 5).map(search => `
                <li data-suggestion="${search}" class="recent-search-item">
                    <span class="recent-search-text">${search}</span>
                    <span class="recent-search-icon dashicons dashicons-clock"></span>
                </li>
            `).join('');
        }
    }
    
    /**
     * Add to recent searches
     */
    addToRecentSearches(query) {
        if (!query || query.length < this.minSearchLength) return;
        
        // Remove if already exists
        const index = this.recentSearches.indexOf(query);
        if (index > -1) {
            this.recentSearches.splice(index, 1);
        }
        
        // Add to beginning
        this.recentSearches.unshift(query);
        
        // Keep only 10 recent searches
        this.recentSearches = this.recentSearches.slice(0, 10);
        
        // Save to localStorage
        localStorage.setItem('hph_recent_searches', JSON.stringify(this.recentSearches));
    }
    
    /**
     * Save current search
     */
    saveCurrentSearch() {
        const name = prompt('Enter a name for this search:');
        if (!name) return;
        
        const search = {
            id: Date.now().toString(),
            name,
            query: this.currentSearch,
            filters: { ...this.currentFilters },
            created: new Date().toISOString()
        };
        
        this.savedSearches.push(search);
        localStorage.setItem('hph_saved_searches', JSON.stringify(this.savedSearches));
        
        this.updateSavedSearches();
        this.state.addNotification({
            type: 'success',
            message: `Search "${name}" saved successfully`
        });
    }
    
    /**
     * Apply saved search
     */
    applySavedSearch(searchId) {
        const search = this.savedSearches.find(s => s.id === searchId);
        if (!search) return;
        
        // Apply search query
        if (search.query) {
            this.applySearchQuery(search.query);
        }
        
        // Apply filters
        this.currentFilters = { ...search.filters };
        this.updateFilterControls();
        this.applyFilters();
    }
    
    /**
     * Delete saved search
     */
    deleteSavedSearch(searchId) {
        if (!confirm('Are you sure you want to delete this saved search?')) return;
        
        this.savedSearches = this.savedSearches.filter(s => s.id !== searchId);
        localStorage.setItem('hph_saved_searches', JSON.stringify(this.savedSearches));
        
        this.updateSavedSearches();
    }
    
    /**
     * Update saved searches display
     */
    updateSavedSearches() {
        const list = this.$('.saved-searches-list');
        if (!list) return;
        
        if (this.savedSearches.length === 0) {
            list.innerHTML = '<p class="no-saved-searches">No saved searches yet.</p>';
            return;
        }
        
        list.innerHTML = this.savedSearches.map(search => `
            <div class="saved-search-item" data-saved-search="${search.id}">
                <div class="saved-search-info">
                    <h5 class="saved-search-name">${search.name}</h5>
                    <p class="saved-search-details">
                        ${search.query ? `Query: "${search.query}"` : 'No query'}
                        ${Object.keys(search.filters).length > 0 ? 
                            ` • ${Object.keys(search.filters).length} filters` : ''}
                    </p>
                    <span class="saved-search-date">${new Date(search.created).toLocaleDateString()}</span>
                </div>
                <div class="saved-search-actions">
                    <button type="button" class="apply-saved-search" title="Apply Search">
                        <span class="dashicons dashicons-search"></span>
                    </button>
                    <button type="button" class="delete-saved-search" title="Delete Search">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>
        `).join('');
    }
    
    /**
     * Update filter controls to match current filters
     */
    updateFilterControls() {
        Object.entries(this.currentFilters).forEach(([key, value]) => {
            const control = this.$(`[data-filter="${key}"]`);
            if (control) {
                control.value = value;
            }
        });
    }
    
    /**
     * Save current filters to localStorage
     */
    saveCurrentFilters() {
        localStorage.setItem('hph_current_filters', JSON.stringify(this.currentFilters));
    }
    
    /**
     * Load saved filters from localStorage
     */
    loadSavedFilters() {
        try {
            const saved = localStorage.getItem('hph_current_filters');
            if (saved) {
                this.currentFilters = JSON.parse(saved);
                this.updateFilterControls();
                this.applyFilters();
            }
        } catch (error) {
            console.warn('Failed to load saved filters:', error);
        }
    }
    
    /**
     * Handle state changes
     */
    onStateChange(path, value) {
        if (path === 'listings') {
            this.updateUI();
        }
    }
    
    /**
     * Update UI
     */
    updateUI() {
        // Update filter counts, etc.
        this.updateFilterCounts();
    }
    
    /**
     * Update filter counts
     */
    updateFilterCounts() {
        const listings = this.state.getListings();
        
        // Update status filter counts
        const statusCounts = {};
        listings.forEach(listing => {
            const status = listing.status || 'unknown';
            statusCounts[status] = (statusCounts[status] || 0) + 1;
        });
        
        // Update property type counts
        const typeCounts = {};
        listings.forEach(listing => {
            const type = listing.property_type || 'unknown';
            typeCounts[type] = (typeCounts[type] || 0) + 1;
        });
        
        // You could update the UI to show these counts next to filter options
    }
}

// Register component
window.ComponentRegistry.register('search-filter', SearchFilter);
