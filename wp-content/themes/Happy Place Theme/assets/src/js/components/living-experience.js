// assets/js/components/living-experience.js

class LivingExperience {
  constructor(element) {
    this.element = element;
    this.config = this.getConfig();
    this.init();
  }

  init() {
    this.animateScoreCircles();
    this.bindEvents();
  }

  getConfig() {
    const configElement = this.element.querySelector('[data-component-config="living-experience"]');
    if (configElement) {
      try {
        return JSON.parse(configElement.textContent);
      } catch (e) {
        console.warn('Failed to parse living experience config:', e);
      }
    }
    return {};
  }

  animateScoreCircles() {
    const scoreCircles = this.element.querySelectorAll('.score-circle');
    
    if (!scoreCircles.length) return;

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const circle = entry.target;
          const score = parseInt(circle.dataset.score) || 0;
          
          // Animate the CSS custom property
          this.animateScore(circle, 0, score, 1500);
          
          // Only animate once
          observer.unobserve(circle);
        }
      });
    }, { 
      threshold: 0.5,
      rootMargin: '-10% 0px'
    });
    
    scoreCircles.forEach(circle => observer.observe(circle));
  }

  animateScore(element, start, end, duration) {
    const startTime = performance.now();
    
    const animate = (currentTime) => {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);
      
      // Easing function for smooth animation
      const easeOutCubic = 1 - Math.pow(1 - progress, 3);
      const currentScore = start + (end - start) * easeOutCubic;
      
      element.style.setProperty('--score', currentScore);
      
      if (progress < 1) {
        requestAnimationFrame(animate);
      }
    };
    
    requestAnimationFrame(animate);
  }

  bindEvents() {
    // Show more amenities button
    const showMoreBtn = this.element.querySelector('.show-more-amenities');
    if (showMoreBtn) {
      showMoreBtn.addEventListener('click', () => this.showMoreAmenities());
    }

    // Community link tracking
    const communityLinks = this.element.querySelectorAll('.community-card a');
    communityLinks.forEach(link => {
      link.addEventListener('click', (e) => {
        this.trackEvent('community_link_clicked', {
          community_name: e.target.textContent.trim()
        });
      });
    });
  }

  showMoreAmenities() {
    const { amenities = [] } = this.config;
    
    if (amenities.length <= 8) return;

    // Create modal with all amenities
    this.createAmenitiesModal(amenities);
    this.trackEvent('show_more_amenities_clicked');
  }

  createAmenitiesModal(amenities) {
    const modal = document.createElement('div');
    modal.className = 'amenities-modal';
    modal.innerHTML = `
      <div class="modal-overlay"></div>
      <div class="modal-content">
        <div class="modal-header">
          <h3>All Nearby Places</h3>
          <button class="modal-close" type="button">&times;</button>
        </div>
        <div class="modal-body">
          <div class="amenities-grid">
            ${amenities.map(amenity => `
              <div class="amenity-card">
                <div class="amenity-info">
                  <h4 class="amenity-name">${this.escapeHtml(amenity.amenity_name || '')}</h4>
                  <p class="amenity-type">${this.escapeHtml(amenity.amenity_type || '')}</p>
                </div>
                <div class="amenity-meta">
                  ${amenity.amenity_distance ? `<span class="distance">${amenity.amenity_distance} mi</span>` : ''}
                  ${amenity.amenity_rating ? `<span class="rating">⭐ ${amenity.amenity_rating}</span>` : ''}
                </div>
              </div>
            `).join('')}
          </div>
        </div>
      </div>
    `;

    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';

    // Bind close events
    const closeBtn = modal.querySelector('.modal-close');
    const overlay = modal.querySelector('.modal-overlay');
    
    const closeModal = () => {
      document.body.removeChild(modal);
      document.body.style.overflow = '';
    };

    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', closeModal);
    
    // ESC key to close
    const handleKeydown = (e) => {
      if (e.key === 'Escape') {
        closeModal();
        document.removeEventListener('keydown', handleKeydown);
      }
    };
    document.addEventListener('keydown', handleKeydown);
  }

  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  trackEvent(eventName, parameters = {}) {
    if (typeof gtag !== 'undefined') {
      gtag('event', eventName, {
        event_category: 'living_experience',
        event_label: window.location.pathname,
        listing_id: this.config.listingId,
        ...parameters
      });
    }

    // Also dispatch custom event for other tracking systems
    const customEvent = new CustomEvent('hph:living-experience', {
      detail: { eventName, parameters, config: this.config }
    });
    this.element.dispatchEvent(customEvent);
  }
}

// Export for manual initialization
export default LivingExperience;
