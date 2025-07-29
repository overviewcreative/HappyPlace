/**
 * Agent Dashboard Core JavaScript
 * Handles all dashboard functionality including section loading,
 * form submissions, AJAX interactions, and UI management.
 */

class AgentDashboard {
    constructor() {
        this.currentSection = 'overview';
        this.isLoading = false;
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadSection(this.currentSection);
        this.initializeComponents();
    }

    bindEvents() {
        // Section navigation
        $(document).on('click', '.dashboard-nav a', (e) => {
            e.preventDefault();
            const section = $(e.target).data('section');
            this.loadSection(section);
        });

        // Listing actions
        $(document).on('click', '.listing-action', (e) => {
            e.preventDefault();
            this.handleListingAction(e.target);
        });

        // Form submissions
        $(document).on('submit', '.dashboard-form', (e) => {
            e.preventDefault();
            this.handleFormSubmission(e.target);
        });

        // Flyer generator
        $(document).on('click', '.generate-flyer', (e) => {
            e.preventDefault();
            this.openFlyerGenerator($(e.target).data('listing-id'));
        });

        // View toggle buttons
        $(document).on('click', '.view-toggle', (e) => {
            e.preventDefault();
            this.toggleView($(e.target).data('view'));
        });

        // Search and filter
        $(document).on('input', '.dashboard-search', this.debounce(this.handleSearch.bind(this), 300));
        $(document).on('change', '.dashboard-filter', this.handleFilter.bind(this));

        // Modal events
        $(document).on('click', '.modal-close, .modal-backdrop', this.closeModal.bind(this));
        $(document).on('click', '.modal-content', (e) => e.stopPropagation());
    }

    /**
     * Load dashboard section
     */
    loadSection(section) {
        if (this.isLoading || section === this.currentSection) return;

        this.isLoading = true;
        this.showLoader();

        // Update navigation
        $('.dashboard-nav a').removeClass('active');
        $(`.dashboard-nav a[data-section="${section}"]`).addClass('active');

        // Load section content
        $.ajax({
            url: wpAjax.ajaxUrl,
            type: 'POST',
            data: {
                action: 'hph_load_dashboard_section',
                section: section,
                nonce: wpAjax.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.renderSection(section, response.data);
                    this.currentSection = section;
                    this.initializeSectionComponents(section);
                } else {
                    this.showError('Failed to load section: ' + response.data.message);
                }
            },
            error: () => {
                this.showError('Network error while loading section');
            },
            complete: () => {
                this.isLoading = false;
                this.hideLoader();
            }
        });
    }

    /**
     * Render section content
     */
    renderSection(section, data) {
        const content = $('.dashboard-content');
        
        switch (section) {
            case 'overview':
                this.renderOverview(content, data);
                break;
            case 'listings':
                this.renderListings(content, data);
                break;
            case 'marketing':
                this.renderMarketing(content, data);
                break;
            case 'open-houses':
                this.renderOpenHouses(content, data);
                break;
            case 'performance':
                this.renderPerformance(content, data);
                break;
            default:
                content.html('<div class="dashboard-error">Section not found</div>');
        }
    }

    /**
     * Render overview section
     */
    renderOverview(container, data) {
        const stats = data.stats || {};
        const recentListings = data.recent_listings || [];
        const activity = data.activity || [];

        const html = `
            <div class="overview-content">
                <div class="welcome-section">
                    <h2>Welcome back!</h2>
                    <p>Here's what's happening with your listings today.</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">${stats.total_listings || 0}</div>
                        <div class="stat-label">Total Listings</div>
                    </div>
                    <div class="stat-card active">
                        <div class="stat-number">${stats.active_listings || 0}</div>
                        <div class="stat-label">Active</div>
                    </div>
                    <div class="stat-card pending">
                        <div class="stat-number">${stats.pending_listings || 0}</div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card sold">
                        <div class="stat-number">${stats.sold_listings || 0}</div>
                        <div class="stat-label">Sold</div>
                    </div>
                </div>

                <div class="overview-grid">
                    <div class="recent-listings">
                        <h3>Recent Listings</h3>
                        <div class="listings-preview">
                            ${this.renderRecentListings(recentListings)}
                        </div>
                    </div>

                    <div class="quick-actions">
                        <h3>Quick Actions</h3>
                        <div class="action-buttons">
                            <button class="btn btn-primary" data-action="add-listing">
                                <i class="fas fa-plus"></i> Add New Listing
                            </button>
                            <button class="btn btn-secondary" data-action="schedule-open-house">
                                <i class="fas fa-calendar"></i> Schedule Open House
                            </button>
                            <button class="btn btn-secondary" data-action="generate-report">
                                <i class="fas fa-chart-bar"></i> Generate Report
                            </button>
                        </div>
                    </div>
                </div>

                <div class="activity-feed">
                    <h3>Recent Activity</h3>
                    <div class="activity-list">
                        ${this.renderActivityFeed(activity)}
                    </div>
                </div>
            </div>
        `;

        container.html(html);
    }

    /**
     * Render recent listings preview
     */
    renderRecentListings(listings) {
        if (!listings.length) {
            return '<div class="no-listings">No recent listings</div>';
        }

        return listings.map(listing => `
            <div class="listing-preview">
                <div class="listing-info">
                    <h4>${listing.title}</h4>
                    <p class="price">$${this.formatPrice(listing.price)}</p>
                    <p class="status status-${listing.status}">${listing.status}</p>
                </div>
                <div class="listing-actions">
                    <button class="btn btn-sm generate-flyer" data-listing-id="${listing.id}">
                        <i class="fas fa-file-pdf"></i> Flyer
                    </button>
                    <a href="${listing.edit_url}" class="btn btn-sm btn-secondary">Edit</a>
                </div>
            </div>
        `).join('');
    }

    /**
     * Render activity feed
     */
    renderActivityFeed(activities) {
        if (!activities.length) {
            return '<div class="no-activity">No recent activity</div>';
        }

        return activities.map(activity => `
            <div class="activity-item">
                <div class="activity-content">
                    <p>${activity.title}</p>
                    <span class="activity-date">${activity.date}</span>
                </div>
            </div>
        `).join('');
    }

    /**
     * Handle listing actions (duplicate, delete, etc.)
     */
    handleListingAction(element) {
        const action = $(element).data('action');
        const listingId = $(element).data('listing-id');

        if (!listingId) {
            this.showError('No listing ID provided');
            return;
        }

        switch (action) {
            case 'duplicate':
                this.duplicateListing(listingId);
                break;
            case 'delete':
                this.deleteListing(listingId);
                break;
            case 'generate-flyer':
                this.openFlyerGenerator(listingId);
                break;
            default:
                console.warn('Unknown listing action:', action);
        }
    }

    /**
     * Duplicate listing
     */
    duplicateListing(listingId) {
        if (!confirm('Are you sure you want to duplicate this listing?')) return;

        $.ajax({
            url: wpAjax.ajaxUrl,
            type: 'POST',
            data: {
                action: 'hph_duplicate_listing',
                listing_id: listingId,
                nonce: wpAjax.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.showSuccess('Listing duplicated successfully');
                    this.refreshCurrentSection();
                } else {
                    this.showError(response.data.message);
                }
            },
            error: () => {
                this.showError('Failed to duplicate listing');
            }
        });
    }

    /**
     * Delete listing
     */
    deleteListing(listingId) {
        if (!confirm('Are you sure you want to delete this listing? This action cannot be undone.')) return;

        $.ajax({
            url: wpAjax.ajaxUrl,
            type: 'POST',
            data: {
                action: 'hph_delete_listing',
                listing_id: listingId,
                nonce: wpAjax.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.showSuccess('Listing deleted successfully');
                    this.refreshCurrentSection();
                } else {
                    this.showError(response.data.message);
                }
            },
            error: () => {
                this.showError('Failed to delete listing');
            }
        });
    }

    /**
     * Open flyer generator modal
     */
    openFlyerGenerator(listingId) {
        if (!listingId) {
            this.showError('No listing selected');
            return;
        }

        // Load listing data first
        $.ajax({
            url: wpAjax.ajaxUrl,
            type: 'POST',
            data: {
                action: 'hph_get_listing_data',
                listing_id: listingId,
                nonce: wpAjax.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.showFlyerModal(response.data);
                } else {
                    this.showError('Failed to load listing data');
                }
            },
            error: () => {
                this.showError('Network error while loading listing');
            }
        });
    }

    /**
     * Show flyer generator modal
     */
    showFlyerModal(listingData) {
        const modalHtml = `
            <div class="modal flyer-modal">
                <div class="modal-backdrop"></div>
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Generate Flyer - ${listingData.title}</h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="flyer-generator">
                            <div class="template-selector">
                                <h4>Choose Template</h4>
                                <div class="template-grid">
                                    <div class="template-option active" data-template="modern">
                                        <img src="/assets/templates/modern-preview.jpg" alt="Modern">
                                        <span>Modern</span>
                                    </div>
                                    <div class="template-option" data-template="classic">
                                        <img src="/assets/templates/classic-preview.jpg" alt="Classic">
                                        <span>Classic</span>
                                    </div>
                                    <div class="template-option" data-template="luxury">
                                        <img src="/assets/templates/luxury-preview.jpg" alt="Luxury">
                                        <span>Luxury</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flyer-preview">
                                <canvas id="flyerCanvas" width="600" height="800"></canvas>
                            </div>
                            <div class="flyer-controls">
                                <button class="btn btn-primary" id="downloadFlyer">
                                    <i class="fas fa-download"></i> Download PDF
                                </button>
                                <button class="btn btn-secondary" id="printFlyer">
                                    <i class="fas fa-print"></i> Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
        this.initializeFlyerGenerator(listingData);
    }

    /**
     * Initialize flyer generator with Fabric.js
     */
    initializeFlyerGenerator(listingData) {
        // This would initialize the Fabric.js canvas and flyer generation
        // For now, we'll create a basic implementation
        const canvas = new fabric.Canvas('flyerCanvas');
        
        // Add listing data to canvas
        const title = new fabric.Text(listingData.title, {
            left: 50,
            top: 50,
            fontSize: 24,
            fontWeight: 'bold'
        });
        
        const price = new fabric.Text(`$${this.formatPrice(listingData.price)}`, {
            left: 50,
            top: 90,
            fontSize: 20,
            fill: '#007cba'
        });
        
        canvas.add(title, price);
        
        // Handle template selection
        $('.template-option').on('click', function() {
            $('.template-option').removeClass('active');
            $(this).addClass('active');
            // Update canvas based on template
        });

        // Handle download
        $('#downloadFlyer').on('click', () => {
            this.downloadFlyer(canvas, listingData.id);
        });
    }

    /**
     * Download flyer as PDF
     */
    downloadFlyer(canvas, listingId) {
        const dataURL = canvas.toDataURL('image/png');
        
        // Track download
        $.ajax({
            url: wpAjax.ajaxUrl,
            type: 'POST',
            data: {
                action: 'hph_track_flyer_download',
                listing_id: listingId,
                flyer_type: $('.template-option.active').data('template'),
                nonce: wpAjax.nonce
            }
        });

        // Create download link
        const link = document.createElement('a');
        link.download = `listing-${listingId}-flyer.png`;
        link.href = dataURL;
        link.click();
    }

    /**
     * Handle form submissions
     */
    handleFormSubmission(form) {
        const $form = $(form);
        const formData = new FormData(form);
        const action = $form.data('action');

        if (!action) {
            this.showError('No form action specified');
            return;
        }

        // Add nonce
        formData.append('nonce', wpAjax.nonce);

        $.ajax({
            url: wpAjax.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: (response) => {
                if (response.success) {
                    this.showSuccess(response.data.message || 'Form submitted successfully');
                    $form[0].reset();
                    this.refreshCurrentSection();
                } else {
                    this.showError(response.data.message || 'Form submission failed');
                    if (response.data.errors) {
                        this.showFormErrors($form, response.data.errors);
                    }
                }
            },
            error: () => {
                this.showError('Network error during form submission');
            }
        });
    }

    /**
     * Show form validation errors
     */
    showFormErrors(form, errors) {
        // Clear previous errors
        form.find('.field-error').remove();

        // Show new errors
        errors.forEach(error => {
            const errorDiv = $('<div class="field-error"></div>').text(error);
            form.prepend(errorDiv);
        });
    }

    /**
     * Utility functions
     */
    formatPrice(price) {
        return new Intl.NumberFormat('en-US').format(price);
    }

    debounce(func, wait) {
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

    showLoader() {
        $('.dashboard-content').addClass('loading');
    }

    hideLoader() {
        $('.dashboard-content').removeClass('loading');
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showNotification(message, type = 'info') {
        const notification = $(`
            <div class="notification notification-${type}">
                <span>${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `);

        $('.notifications-container').append(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.fadeOut(() => notification.remove());
        }, 5000);

        // Manual close
        notification.find('.notification-close').on('click', () => {
            notification.fadeOut(() => notification.remove());
        });
    }

    closeModal() {
        $('.modal').fadeOut(() => $('.modal').remove());
    }

    refreshCurrentSection() {
        this.loadSection(this.currentSection);
    }

    initializeComponents() {
        // Initialize any global components here
        if (!$('.notifications-container').length) {
            $('body').append('<div class="notifications-container"></div>');
        }
    }

    initializeSectionComponents(section) {
        // Initialize section-specific components
        switch (section) {
            case 'performance':
                break;
            case 'marketing':
                // Marketing section initialization would go here
                break;
        }
    }

    // Search and filter methods
    handleSearch(e) {
        const query = $(e.target).val();
        // Implement search functionality based on current section
        this.filterContent({ search: query });
    }

    handleFilter(e) {
        const filter = $(e.target);
        const filterType = filter.data('filter');
        const value = filter.val();
        
        // Implement filtering based on current section
        this.filterContent({ [filterType]: value });
    }

    filterContent(filters) {
        // This would implement the actual filtering logic
        // based on the current section and filter criteria
    }

    toggleView(view) {
        $('.view-toggle').removeClass('active');
        $(`.view-toggle[data-view="${view}"]`).addClass('active');
        
        // Toggle between grid and list views
        if (view === 'grid') {
            $('.listings-container').removeClass('list-view').addClass('grid-view');
        } else if (view === 'list') {
            $('.listings-container').removeClass('grid-view').addClass('list-view');
        }
    }
}

// Initialize dashboard when DOM is ready
$(document).ready(() => {
    window.agentDashboard = new AgentDashboard();
});
