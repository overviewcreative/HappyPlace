/**
 * Search Filters Component
 * Happy Place Theme
 */

import AjaxHandler from '../utilities/ajax-handler.js';

class SearchFilters {
    constructor(container) {
        this.container = container;
        this.ajax = new AjaxHandler();
        this.filters = {};
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadInitialFilters();
    }

    bindEvents() {
        // Filter form submission
        const filterForm = this.container.querySelector('.search-filters-form');
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.applyFilters();
            });
        }

        // Individual filter changes
        this.container.addEventListener('change', (e) => {
            if (e.target.matches('.filter-input')) {
                this.updateFilter(e.target);
            }
        });

        // Clear filters
        const clearBtn = this.container.querySelector('.clear-filters');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                this.clearFilters();
            });
        }

        // Price range sliders
        const priceInputs = this.container.querySelectorAll('.price-range input');
        priceInputs.forEach(input => {
            input.addEventListener('input', () => {
                this.updatePriceRange();
            });
        });
    }

    loadInitialFilters() {
        // Load filters from URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        
        urlParams.forEach((value, key) => {
            if (this.isValidFilter(key)) {
                this.filters[key] = value;
                this.updateFormField(key, value);
            }
        });
    }

    updateFilter(input) {
        const name = input.name;
        const value = input.type === 'checkbox' ? 
            (input.checked ? input.value : '') : 
            input.value;

        if (value) {
            this.filters[name] = value;
        } else {
            delete this.filters[name];
        }

        // Auto-apply filters with debounce
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
            this.applyFilters();
        }, 500);
    }

    async applyFilters() {
        try {
            // Show loading state
            this.showLoading(true);

            // Make AJAX request
            const results = await this.ajax.loadListings(this.filters);

            // Update results display
            this.updateResults(results);

            // Update URL
            this.updateUrl();

            // Update filter counts
            this.updateFilterCounts(results.counts);

        } catch (error) {
            console.error('Filter error:', error);
            this.showError('Failed to load results. Please try again.');
        } finally {
            this.showLoading(false);
        }
    }

    updateResults(results) {
        const resultsContainer = document.querySelector('.listings-results');
        if (resultsContainer && results.html) {
            resultsContainer.innerHTML = results.html;
        }

        // Update results count
        const countElement = document.querySelector('.results-count');
        if (countElement && results.total !== undefined) {
            countElement.textContent = `${results.total} properties found`;
        }
    }

    updateUrl() {
        const params = new URLSearchParams(this.filters);
        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        
        window.history.pushState({ filters: this.filters }, '', newUrl);
    }

    updateFilterCounts(counts) {
        if (!counts) return;

        Object.entries(counts).forEach(([key, count]) => {
            const element = this.container.querySelector(`[data-count="${key}"]`);
            if (element) {
                element.textContent = count;
            }
        });
    }

    updateFormField(name, value) {
        const field = this.container.querySelector(`[name="${name}"]`);
        if (!field) return;

        if (field.type === 'checkbox') {
            field.checked = field.value === value;
        } else {
            field.value = value;
        }
    }

    updatePriceRange() {
        const minInput = this.container.querySelector('input[name="price_min"]');
        const maxInput = this.container.querySelector('input[name="price_max"]');
        const display = this.container.querySelector('.price-range-display');

        if (minInput && maxInput && display) {
            const min = parseInt(minInput.value);
            const max = parseInt(maxInput.value);
            
            display.textContent = `$${min.toLocaleString()} - $${max.toLocaleString()}`;
        }
    }

    clearFilters() {
        this.filters = {};
        
        // Reset form fields
        const form = this.container.querySelector('.search-filters-form');
        if (form) {
            form.reset();
        }

        // Apply empty filters
        this.applyFilters();
    }

    showLoading(show) {
        const resultsContainer = document.querySelector('.listings-results');
        if (resultsContainer) {
            resultsContainer.classList.toggle('loading', show);
        }
    }

    showError(message) {
        if (window.notifications) {
            window.notifications.error(message);
        } else {
            alert(message);
        }
    }

    isValidFilter(key) {
        const validFilters = [
            'location', 'city', 'community', 'price_min', 'price_max',
            'bedrooms', 'bathrooms', 'property_type', 'features', 'sort'
        ];
        return validFilters.includes(key);
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    const filterContainers = document.querySelectorAll('.search-filters');
    filterContainers.forEach(container => {
        new SearchFilters(container);
    });
});

export default SearchFilters;