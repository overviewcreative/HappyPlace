/**
 * Happy Place Listing Filters AJAX Handler
 */
class HPHListingFilters {
    constructor() {
        this.form = document.querySelector('.hph-filters-form');
        this.mapContainer = document.querySelector('.hph-listings-map');
        this.listingsContainer = document.querySelector('.hph-listings-grid');
        this.loadingOverlay = this.createLoadingOverlay();
        this.debounceTimeout = null;
        this.map = this.mapContainer?.hphMap;

        console.log('HPHListingFilters initialized:', {
            form: !!this.form,
            mapContainer: !!this.mapContainer,
            listingsContainer: !!this.listingsContainer,
            hphAjax: typeof hphAjax !== 'undefined'
        });

        this.initialize();
    }

    createLoadingOverlay() {
        const overlay = document.createElement('div');
        overlay.className = 'hph-loading-overlay';
        overlay.innerHTML = `
            <div class="hph-loading-spinner">
                <div class="spinner"></div>
                <span>Updating results...</span>
            </div>
        `;
        document.body.appendChild(overlay);
        return overlay;
    }

    initialize() {
        if (!this.form) return;

        // Handle all filter changes
        this.form.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('change', () => this.handleFilterChange());
        });

        // Handle price range changes with debounce
        this.form.querySelectorAll('.hph-price-select').forEach(select => {
            select.addEventListener('change', () => this.handleFilterChange());
        });

        // Prevent default form submission
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleFilterChange();
        });

        // Handle browser back/forward
        window.addEventListener('popstate', (e) => {
            if (e.state && e.state.filters) {
                this.applyFiltersFromState(e.state.filters);
            }
        });
    }

    handleFilterChange() {
        clearTimeout(this.debounceTimeout);
        this.debounceTimeout = setTimeout(() => {
            this.fetchFilteredResults();
        }, 300);
    }

    showLoading() {
        this.loadingOverlay.classList.add('active');
    }

    hideLoading() {
        this.loadingOverlay.classList.remove('active');
    }

    async fetchFilteredResults() {
        const formData = new FormData(this.form);
        
        // Add action and nonce for security
        formData.append('action', 'hph_filter_listings');
        formData.append('nonce', hphAjax.nonce);

        try {
            this.showLoading();

            const response = await fetch(hphAjax.ajaxUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            if (!response.ok) throw new Error('Network response was not ok');

            const data = await response.json();

            if (data.success) {
                // Create query string for URL update
                const queryString = new URLSearchParams(formData).toString();
                const newUrl = `${window.location.pathname}?${queryString}`;
                window.history.pushState({ filters: formData }, '', newUrl);

                // Update the listings grid
                if (this.listingsContainer && data.listings_html) {
                    this.listingsContainer.innerHTML = data.listings_html;
                }

                // Update map markers if map exists
                if (this.map && data.properties) {
                    this.updateMapMarkers(data.properties);
                }

                // Update counts and any other UI elements
                this.updateUIElements(data);
            }
        } catch (error) {
            console.error('Error fetching filtered results:', error);
        } finally {
            this.hideLoading();
        }
    }

    updateMapMarkers(properties) {
        if (!this.map || !this.map.updateProperties) return;
        
        // Use the new updateProperties method
        this.map.updateProperties(properties, true);
    }

    updateUIElements(data) {
        // Update result count
        const countElement = document.querySelector('.hph-listings-count');
        if (countElement && data.total_count !== undefined) {
            countElement.textContent = `${data.total_count} Properties Found`;
        }

        // Reinitialize listing card click handlers
        document.querySelectorAll('.hph-map-listing-card').forEach(card => {
            card.addEventListener('click', () => {
                const listingId = parseInt(card.dataset.listingId, 10);
                if (this.map) {
                    this.map.highlightProperty(listingId);
                }
            });
        });
    }

    applyFiltersFromState(filters) {
        // Reset form to match URL state
        for (const [key, value] of Object.entries(filters)) {
            const input = this.form.querySelector(`[name="${key}"]`);
            if (input) {
                input.value = value;
            }
        }
        this.fetchFilteredResults();
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    new HPHListingFilters();
});

// Initialize filters when document is ready
jQuery(document).ready(() => {
    new HPHListingFilters();
});
