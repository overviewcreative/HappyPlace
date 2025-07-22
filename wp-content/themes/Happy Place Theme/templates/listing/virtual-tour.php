<?php
/**
 * Virtual Tour Template Part
 * 
 * Embedded virtual tour section for listings
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

namespace HappyPlace\Templates;

if (!defined('ABSPATH')) {
    exit;
}

// Extract data from template args
$listing_data = $args['data'] ?? [];
$listing_id = $args['listing_id'] ?? 0;
$tour_url = $args['tour_url'] ?? '';

if (empty($tour_url)) {
    return; // No tour URL provided
}

// Get property info
$address = $listing_data['details']['full_address'] ?? '';
$price = $listing_data['core']['price'] ?? 0;

// Parse tour URL to determine provider and optimize embed
$tour_info = hph_parse_virtual_tour_url($tour_url);
$embed_url = $tour_info['embed_url'] ?? $tour_url;
$provider = $tour_info['provider'] ?? 'generic';
$tour_id = $tour_info['tour_id'] ?? '';
?>

<section class="virtual-tour-section" data-listing-id="<?php echo esc_attr($listing_id); ?>">
    <div class="container">
        <div class="tour-header">
            <div class="header-content">
                <div class="tour-info">
                    <h2 class="tour-title">
                        <i class="fas fa-video tour-icon"></i>
                        Virtual Tour
                    </h2>
                    <p class="tour-description">Take a 360° walk-through of this property from the comfort of your home</p>
                </div>
                <div class="tour-actions">
                    <button class="tour-btn fullscreen-btn" data-action="fullscreen">
                        <i class="fas fa-expand"></i>
                        Fullscreen
                    </button>
                    <button class="tour-btn share-btn" data-action="share-tour">
                        <i class="fas fa-share-alt"></i>
                        Share Tour
                    </button>
                </div>
            </div>
        </div>

        <div class="tour-container" id="tour-container">
            <!-- Loading State -->
            <div class="tour-loading" id="tour-loading">
                <div class="loading-spinner"></div>
                <p>Loading virtual tour...</p>
                <div class="loading-tips">
                    <small>💡 Use arrow keys or mouse to navigate • Click and drag to look around</small>
                </div>
            </div>

            <!-- Tour Embed -->
            <div class="tour-embed" id="tour-embed" style="display: none;">
                <?php if ($provider === 'matterport'): ?>
                    <!-- Matterport Embed -->
                    <iframe src="<?php echo esc_url($embed_url); ?>"
                            width="100%" 
                            height="100%"
                            frameborder="0" 
                            allowfullscreen
                            allow="vr; xr; accelerometer; magnetometer; gyroscope"
                            id="tour-iframe"
                            title="Matterport Virtual Tour">
                    </iframe>
                
                <?php elseif ($provider === 'zillow'): ?>
                    <!-- Zillow 3D Home -->
                    <iframe src="<?php echo esc_url($embed_url); ?>"
                            width="100%" 
                            height="100%"
                            frameborder="0" 
                            allowfullscreen
                            id="tour-iframe"
                            title="Zillow 3D Home Tour">
                    </iframe>
                
                <?php elseif ($provider === 'ricoh'): ?>
                    <!-- Ricoh Tours -->
                    <iframe src="<?php echo esc_url($embed_url); ?>"
                            width="100%" 
                            height="100%"
                            frameborder="0" 
                            allowfullscreen
                            id="tour-iframe"
                            title="Ricoh Virtual Tour">
                    </iframe>
                
                <?php elseif ($provider === 'youtube'): ?>
                    <!-- YouTube Video Tour -->
                    <iframe src="<?php echo esc_url($embed_url); ?>"
                            width="100%" 
                            height="100%"
                            frameborder="0" 
                            allowfullscreen
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            id="tour-iframe"
                            title="Video Tour">
                    </iframe>
                
                <?php else: ?>
                    <!-- Generic Embed -->
                    <iframe src="<?php echo esc_url($embed_url); ?>"
                            width="100%" 
                            height="100%"
                            frameborder="0" 
                            allowfullscreen
                            id="tour-iframe"
                            title="Virtual Tour">
                    </iframe>
                <?php endif; ?>
            </div>

            <!-- Fallback Content -->
            <div class="tour-fallback" id="tour-fallback" style="display: none;">
                <div class="fallback-content">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Unable to Load Virtual Tour</h3>
                    <p>The virtual tour could not be loaded. Please try refreshing the page or visit the tour directly.</p>
                    <div class="fallback-actions">
                        <a href="<?php echo esc_url($tour_url); ?>" 
                           target="_blank" 
                           class="btn btn-primary">
                            <i class="fas fa-external-link-alt"></i>
                            Open Tour in New Window
                        </a>
                        <button class="btn btn-outline retry-btn" data-action="retry">
                            <i class="fas fa-redo"></i>
                            Retry
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tour Features -->
        <div class="tour-features">
            <div class="features-grid">
                <div class="feature-item">
                    <i class="fas fa-mouse-pointer"></i>
                    <span>Click & Drag</span>
                    <small>Navigate through rooms</small>
                </div>
                <div class="feature-item">
                    <i class="fas fa-search-plus"></i>
                    <span>Zoom In/Out</span>
                    <small>Get a closer look</small>
                </div>
                <div class="feature-item">
                    <i class="fas fa-expand-arrows-alt"></i>
                    <span>360° View</span>
                    <small>Look in all directions</small>
                </div>
                <div class="feature-item">
                    <i class="fas fa-mobile-alt"></i>
                    <span>Mobile Friendly</span>
                    <small>Works on all devices</small>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="tour-cta">
            <div class="cta-content">
                <h3>Ready to See More?</h3>
                <p>Schedule an in-person tour to experience all this property has to offer.</p>
                <div class="cta-actions">
                    <button class="btn btn-primary btn-large schedule-tour-btn" data-action="schedule-tour">
                        <i class="fas fa-calendar-plus"></i>
                        Schedule In-Person Tour
                    </button>
                    <button class="btn btn-outline btn-large contact-agent-btn" data-action="contact-agent">
                        <i class="fas fa-user-tie"></i>
                        Contact Agent
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.virtual-tour-section {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: white;
    padding: 4rem 0;
    margin: 3rem 0;
}

/* Tour Header */
.tour-header {
    margin-bottom: 2rem;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 2rem;
}

.tour-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.tour-icon {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
    padding: 1rem;
    border-radius: 12px;
    font-size: 1.5rem;
}

.tour-description {
    font-size: 1.1rem;
    opacity: 0.9;
    margin: 0;
    max-width: 600px;
}

.tour-actions {
    display: flex;
    gap: 1rem;
}

.tour-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid rgba(255, 255, 255, 0.2);
    color: white;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s ease;
    backdrop-filter: blur(10px);
}

.tour-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.4);
    transform: translateY(-2px);
}

/* Tour Container */
.tour-container {
    position: relative;
    width: 100%;
    height: 600px;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
    margin-bottom: 3rem;
}

.tour-embed {
    width: 100%;
    height: 100%;
    background: #000;
}

.tour-embed iframe {
    width: 100%;
    height: 100%;
    border: none;
}

/* Loading State */
.tour-loading {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    text-align: center;
    z-index: 2;
}

.loading-spinner {
    width: 60px;
    height: 60px;
    border: 4px solid rgba(255, 255, 255, 0.1);
    border-left: 4px solid var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.tour-loading p {
    font-size: 1.1rem;
    margin: 0 0 1rem 0;
    color: white;
}

.loading-tips {
    margin-top: 1rem;
    opacity: 0.7;
}

/* Fallback Content */
.tour-fallback {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    text-align: center;
    padding: 2rem;
    z-index: 2;
}

.fallback-content {
    max-width: 400px;
}

.fallback-content i {
    font-size: 3rem;
    color: #fbbf24;
    margin-bottom: 1rem;
}

.fallback-content h3 {
    font-size: 1.5rem;
    margin: 0 0 1rem 0;
    color: white;
}

.fallback-content p {
    opacity: 0.9;
    margin: 0 0 2rem 0;
    line-height: 1.6;
}

.fallback-actions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* Tour Features */
.tour-features {
    margin-bottom: 3rem;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
}

.feature-item {
    text-align: center;
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    transition: all 0.2s ease;
    backdrop-filter: blur(10px);
}

.feature-item:hover {
    transform: translateY(-4px);
    background: rgba(255, 255, 255, 0.1);
}

.feature-item i {
    font-size: 2rem;
    color: var(--primary-light);
    margin-bottom: 1rem;
}

.feature-item span {
    display: block;
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
}

.feature-item small {
    opacity: 0.8;
    font-size: 0.9rem;
}

/* Call to Action */
.tour-cta {
    text-align: center;
    padding: 3rem 2rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    backdrop-filter: blur(10px);
}

.cta-content h3 {
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
}

.cta-content p {
    font-size: 1.1rem;
    opacity: 0.9;
    margin: 0 0 2rem 0;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.cta-actions {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    cursor: pointer;
    border: none;
    font-size: 1rem;
}

.btn-large {
    padding: 1.25rem 2.5rem;
    font-size: 1.1rem;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(var(--primary-rgb), 0.4);
}

.btn-outline {
    background: transparent;
    border: 2px solid rgba(255, 255, 255, 0.3);
    color: white;
}

.btn-outline:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateY(-2px);
}

/* Fullscreen Styles */
.tour-container.fullscreen {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 9999;
    border-radius: 0;
    margin: 0;
}

.tour-container.fullscreen .tour-embed {
    height: 100vh;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .tour-container {
        height: 500px;
    }
    
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .virtual-tour-section {
        padding: 3rem 0;
    }
    
    .header-content {
        flex-direction: column;
        text-align: center;
    }
    
    .tour-title {
        font-size: 2rem;
    }
    
    .tour-actions {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .tour-container {
        height: 400px;
    }
    
    .features-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .cta-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .btn {
        width: 100%;
        max-width: 300px;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .tour-container {
        height: 300px;
        border-radius: 12px;
    }
    
    .tour-cta {
        padding: 2rem 1rem;
    }
    
    .fallback-actions {
        align-items: center;
    }
    
    .fallback-actions .btn {
        width: 100%;
        max-width: 250px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const virtualTour = {
        isLoaded: false,
        isFullscreen: false,
        loadTimeout: null,
        
        init() {
            this.bindEvents();
            this.loadTour();
            this.setupResizeHandler();
        },
        
        bindEvents() {
            // Action buttons
            document.querySelector('.fullscreen-btn')?.addEventListener('click', () => {
                this.toggleFullscreen();
            });
            
            document.querySelector('.share-btn')?.addEventListener('click', () => {
                this.shareTour();
            });
            
            document.querySelector('.retry-btn')?.addEventListener('click', () => {
                this.retryLoad();
            });
            
            document.querySelector('.schedule-tour-btn')?.addEventListener('click', () => {
                this.scheduleTour();
            });
            
            document.querySelector('.contact-agent-btn')?.addEventListener('click', () => {
                this.contactAgent();
            });
            
            // Escape key to exit fullscreen
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isFullscreen) {
                    this.exitFullscreen();
                }
            });
        },
        
        loadTour() {
            const iframe = document.getElementById('tour-iframe');
            const loading = document.getElementById('tour-loading');
            const embed = document.getElementById('tour-embed');
            const fallback = document.getElementById('tour-fallback');
            
            if (!iframe) return;
            
            // Set loading timeout
            this.loadTimeout = setTimeout(() => {
                this.showFallback();
            }, 15000); // 15 seconds timeout
            
            // Listen for iframe load
            iframe.addEventListener('load', () => {
                this.onTourLoaded();
            });
            
            iframe.addEventListener('error', () => {
                this.showFallback();
            });
            
            // Show embed immediately (iframe will load in background)
            setTimeout(() => {
                loading.style.display = 'none';
                embed.style.display = 'block';
            }, 2000); // Show after 2 seconds
        },
        
        onTourLoaded() {
            if (this.loadTimeout) {
                clearTimeout(this.loadTimeout);
            }
            
            this.isLoaded = true;
            this.trackEvent('virtual_tour_loaded');
        },
        
        showFallback() {
            if (this.loadTimeout) {
                clearTimeout(this.loadTimeout);
            }
            
            const loading = document.getElementById('tour-loading');
            const embed =