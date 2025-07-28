/**
 * Virtual Tour & Floor Plans JavaScript
 * Handles tab switching, tour loading, and modal functionality
 */

class HPHVirtualExperience {
    constructor() {
        this.currentTab = 'virtual-tour';
        this.tourLoaded = false;
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeTabs();
    }

    bindEvents() {
        // Tab switching
        document.addEventListener('click', (e) => {
            if (e.target.matches('.hph-tab-btn') || e.target.closest('.hph-tab-btn')) {
                this.handleTabSwitch(e);
            }
        });

        // Virtual tour controls
        document.addEventListener('click', (e) => {
            if (e.target.matches('#start-tour-btn') || e.target.closest('#start-tour-btn')) {
                this.startVirtualTour(e);
            }
            if (e.target.matches('#fullscreen-btn')) {
                this.toggleFullscreen();
            }
            if (e.target.matches('#close-tour-btn')) {
                this.closeTour();
            }
        });

        // Floor plan modal
        document.addEventListener('click', (e) => {
            if (e.target.matches('.hph-view-plan-btn')) {
                this.openFloorPlanModal(e);
            }
            if (e.target.matches('#modal-close-btn') || e.target.matches('#modal-backdrop')) {
                this.closeModal();
            }
        });

        // Action buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('#schedule-tour-btn')) {
                this.handleScheduleTour();
            }
            if (e.target.matches('#download-plans-btn')) {
                this.handleDownloadPlans();
            }
            if (e.target.matches('#request-info-btn')) {
                this.handleRequestInfo();
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
                if (this.tourLoaded) {
                    this.closeTour();
                }
            }
        });

        // Fullscreen change events
        document.addEventListener('fullscreenchange', () => {
            this.updateFullscreenButton();
        });
    }

    initializeTabs() {
        const firstTab = document.querySelector('.hph-tab-btn.active');
        if (firstTab) {
            this.currentTab = firstTab.dataset.tab;
        }
    }

    handleTabSwitch(e) {
        e.preventDefault();
        const button = e.target.matches('.hph-tab-btn') ? e.target : e.target.closest('.hph-tab-btn');
        const tabId = button.dataset.tab;
        
        if (tabId === this.currentTab) return;

        // Update button states
        document.querySelectorAll('.hph-tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        button.classList.add('active');

        // Update panel states
        document.querySelectorAll('.hph-tab-panel').forEach(panel => {
            panel.classList.remove('active');
        });
        
        const targetPanel = document.getElementById(`${tabId}-panel`);
        if (targetPanel) {
            targetPanel.classList.add('active');
        }

        this.currentTab = tabId;

        // Analytics tracking
        this.trackEvent('tab_switch', { tab: tabId });
    }

    startVirtualTour(e) {
        const button = e.target.matches('#start-tour-btn') ? e.target : e.target.closest('#start-tour-btn');
        const tourUrl = button.dataset.tourUrl;
        const tourType = button.dataset.tourType || 'matterport';

        if (!tourUrl) {
            console.error('No tour URL provided');
            return;
        }

        // Show loading state
        const preview = document.getElementById('tour-preview');
        const loading = document.getElementById('tour-loading');
        const iframeContainer = document.getElementById('tour-iframe-container');
        const iframe = document.getElementById('tour-iframe');

        if (preview) preview.style.display = 'none';
        if (loading) loading.style.display = 'flex';

        // Process tour URL for embedding
        const embedUrl = this.processEmbedUrl(tourUrl, tourType);

        // Load the tour
        iframe.src = embedUrl;
        
        iframe.onload = () => {
            if (loading) loading.style.display = 'none';
            if (iframeContainer) iframeContainer.style.display = 'block';
            this.tourLoaded = true;
            
            // Analytics tracking
            this.trackEvent('virtual_tour_started', { 
                tour_type: tourType,
                tour_url: tourUrl 
            });
        };

        iframe.onerror = () => {
            this.handleTourError();
        };
    }

    processEmbedUrl(url, type) {
        switch (type) {
            case 'matterport':
                // Convert Matterport URLs to embed format
                if (url.includes('my.matterport.com/show/')) {
                    return url.replace('/show/', '/show/?play=1&');
                }
                return url;
            
            case 'youtube':
                // Convert YouTube URLs to embed format
                const videoId = this.extractYouTubeId(url);
                return videoId ? `https://www.youtube.com/embed/${videoId}?autoplay=1` : url;
            
            case 'vimeo':
                // Convert Vimeo URLs to embed format
                const vimeoId = this.extractVimeoId(url);
                return vimeoId ? `https://player.vimeo.com/video/${vimeoId}?autoplay=1` : url;
            
            default:
                return url;
        }
    }

    extractYouTubeId(url) {
        const regex = /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/;
        const match = url.match(regex);
        return match ? match[1] : null;
    }

    extractVimeoId(url) {
        const regex = /vimeo\.com\/(\d+)/;
        const match = url.match(regex);
        return match ? match[1] : null;
    }

    handleTourError() {
        const loading = document.getElementById('tour-loading');
        const preview = document.getElementById('tour-preview');
        
        if (loading) {
            loading.innerHTML = `
                <div class="hph-error-state">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: var(--color-warning); margin-bottom: 1rem;"></i>
                    <p style="margin: 0; color: var(--color-text);">Unable to load virtual tour</p>
                    <button class="hph-btn hph-btn--outline" onclick="location.reload()" style="margin-top: 1rem;">
                        <i class="fas fa-redo"></i> Try Again
                    </button>
                </div>
            `;
        }

        console.error('Failed to load virtual tour');
        this.trackEvent('virtual_tour_error');
    }

    toggleFullscreen() {
        const container = document.getElementById('tour-iframe-container');
        
        if (!document.fullscreenElement) {
            container.requestFullscreen().catch(err => {
                console.error('Error attempting to enable fullscreen:', err);
            });
        } else {
            document.exitFullscreen();
        }
    }

    updateFullscreenButton() {
        const button = document.getElementById('fullscreen-btn');
        const icon = button?.querySelector('i');
        
        if (icon) {
            if (document.fullscreenElement) {
                icon.className = 'fas fa-compress';
                button.title = 'Exit fullscreen';
            } else {
                icon.className = 'fas fa-expand';
                button.title = 'Fullscreen';
            }
        }
    }

    closeTour() {
        const preview = document.getElementById('tour-preview');
        const loading = document.getElementById('tour-loading');
        const iframeContainer = document.getElementById('tour-iframe-container');
        const iframe = document.getElementById('tour-iframe');

        // Reset states
        if (iframeContainer) iframeContainer.style.display = 'none';
        if (loading) loading.style.display = 'none';
        if (preview) preview.style.display = 'flex';
        
        // Clear iframe
        if (iframe) iframe.src = '';
        
        this.tourLoaded = false;

        // Exit fullscreen if active
        if (document.fullscreenElement) {
            document.exitFullscreen();
        }

        this.trackEvent('virtual_tour_closed');
    }

    openFloorPlanModal(e) {
        const button = e.target;
        const planIndex = button.dataset.planIndex;
        const planItem = document.querySelector(`[data-plan="${planIndex}"]`);
        
        if (!planItem) return;

        const img = planItem.querySelector('img');
        const title = planItem.querySelector('.hph-plan-title');
        
        const modal = document.getElementById('floor-plan-modal');
        const modalImage = document.getElementById('modal-plan-image');
        const modalTitle = document.getElementById('modal-plan-title');

        if (modal && modalImage && img) {
            modalImage.src = img.src;
            modalImage.alt = img.alt;
            
            if (modalTitle && title) {
                modalTitle.textContent = title.textContent;
            }
            
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';

            // Analytics tracking
            this.trackEvent('floor_plan_viewed', { plan_index: planIndex });
        }
    }

    closeModal() {
        const modal = document.getElementById('floor-plan-modal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    handleScheduleTour() {
        // Trigger contact form or redirect to scheduling page
        const event = new CustomEvent('hph:schedule-tour', {
            detail: { 
                source: 'virtual_tour_section',
                listing_id: this.getListingId()
            }
        });
        document.dispatchEvent(event);
        
        this.trackEvent('schedule_tour_clicked', { source: 'virtual_tour' });
    }

    handleDownloadPlans() {
        // Trigger floor plan download
        const event = new CustomEvent('hph:download-floor-plans', {
            detail: { 
                listing_id: this.getListingId()
            }
        });
        document.dispatchEvent(event);
        
        this.trackEvent('floor_plans_download', { source: 'virtual_tour' });
    }

    handleRequestInfo() {
        // Trigger contact form
        const event = new CustomEvent('hph:request-info', {
            detail: { 
                source: 'virtual_tour_section',
                listing_id: this.getListingId()
            }
        });
        document.dispatchEvent(event);
        
        this.trackEvent('request_info_clicked', { source: 'virtual_tour' });
    }

    getListingId() {
        const section = document.querySelector('[data-listing-id]');
        return section ? section.dataset.listingId : null;
    }

    trackEvent(eventName, data = {}) {
        // Google Analytics 4 tracking
        if (typeof gtag !== 'undefined') {
            gtag('event', eventName, {
                event_category: 'virtual_tour',
                ...data
            });
        }

        // Custom analytics hook
        if (typeof window.hphAnalytics !== 'undefined') {
            window.hphAnalytics.track(eventName, data);
        }

        // Debug logging
        if (window.location.hostname === 'localhost' || window.location.search.includes('debug=1')) {
            console.log('HPH Analytics:', eventName, data);
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.hph-virtual-experience')) {
        new HPHVirtualExperience();
    }
});

// Export for use in other modules
window.HPHVirtualExperience = HPHVirtualExperience;