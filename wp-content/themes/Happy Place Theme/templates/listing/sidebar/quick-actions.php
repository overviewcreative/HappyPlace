<?php
/**
 * Quick Actions Sidebar Template Part
 * 
 * Quick action buttons for listing interactions
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

// Get listing details
$price = $listing_data['core']['price'] ?? 0;
$address = $listing_data['details']['full_address'] ?? '';
$mls_number = $listing_data['core']['mls_number'] ?? '';
$virtual_tour_url = hph_get_listing_field($listing_id, 'virtual_tour_url');

// Get agent info for contact
$agent = $listing_data['relationships']['listing_agent'] ?? null;
$agent_phone = $agent['phone'] ?? '';
$agent_email = $agent['email'] ?? '';
?>

<div class="sidebar-widget quick-actions-widget">
    <div class="widget-header">
        <h3 class="widget-title">
            <i class="fas fa-bolt"></i>
            Quick Actions
        </h3>
    </div>

    <div class="widget-content">
        <div class="action-buttons">
            
            <!-- Schedule Tour -->
            <button class="action-btn primary-action" data-action="schedule-tour">
                <div class="btn-content">
                    <div class="btn-icon">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <div class="btn-text">
                        <span class="btn-title">Schedule Tour</span>
                        <span class="btn-subtitle">See this home in person</span>
                    </div>
                </div>
                <i class="fas fa-chevron-right btn-arrow"></i>
            </button>

            <!-- Request Info -->
            <button class="action-btn" data-action="request-info">
                <div class="btn-content">
                    <div class="btn-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="btn-text">
                        <span class="btn-title">Request Information</span>
                        <span class="btn-subtitle">Get detailed property info</span>
                    </div>
                </div>
                <i class="fas fa-chevron-right btn-arrow"></i>
            </button>

            <?php if ($virtual_tour_url): ?>
            <!-- Virtual Tour -->
            <button class="action-btn" data-action="virtual-tour" data-url="<?php echo esc_url($virtual_tour_url); ?>">
                <div class="btn-content">
                    <div class="btn-icon">
                        <i class="fas fa-video"></i>
                    </div>
                    <div class="btn-text">
                        <span class="btn-title">Virtual Tour</span>
                        <span class="btn-subtitle">Explore from your device</span>
                    </div>
                </div>
                <i class="fas fa-external-link-alt btn-arrow"></i>
            </button>
            <?php endif; ?>

            <!-- Save/Favorite -->
            <button class="action-btn favorite-btn" data-action="toggle-favorite" data-listing-id="<?php echo esc_attr($listing_id); ?>">
                <div class="btn-content">
                    <div class="btn-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <div class="btn-text">
                        <span class="btn-title">Save Listing</span>
                        <span class="btn-subtitle">Add to your favorites</span>
                    </div>
                </div>
                <i class="fas fa-chevron-right btn-arrow"></i>
            </button>

            <!-- Share -->
            <button class="action-btn" data-action="share">
                <div class="btn-content">
                    <div class="btn-icon">
                        <i class="fas fa-share-alt"></i>
                    </div>
                    <div class="btn-text">
                        <span class="btn-title">Share Property</span>
                        <span class="btn-subtitle">Send to friends & family</span>
                    </div>
                </div>
                <i class="fas fa-chevron-right btn-arrow"></i>
            </button>

            <!-- Print -->
            <button class="action-btn" data-action="print">
                <div class="btn-content">
                    <div class="btn-icon">
                        <i class="fas fa-print"></i>
                    </div>
                    <div class="btn-text">
                        <span class="btn-title">Print Details</span>
                        <span class="btn-subtitle">Property information sheet</span>
                    </div>
                </div>
                <i class="fas fa-chevron-right btn-arrow"></i>
            </button>

        </div>

        <!-- Contact Methods -->
        <div class="contact-methods">
            <h4 class="contact-title">Get In Touch</h4>
            <div class="contact-buttons">
                <?php if ($agent_phone): ?>
                <a href="tel:<?php echo esc_attr($agent_phone); ?>" 
                   class="contact-btn phone-btn">
                    <i class="fas fa-phone"></i>
                    <span>Call Now</span>
                </a>
                <?php endif; ?>

                <?php if ($agent_email): ?>
                <a href="mailto:<?php echo esc_attr($agent_email); ?>?subject=Inquiry about <?php echo esc_attr($address); ?>" 
                   class="contact-btn email-btn">
                    <i class="fas fa-envelope"></i>
                    <span>Email</span>
                </a>
                <?php endif; ?>

                <button class="contact-btn message-btn" data-action="send-message">
                    <i class="fas fa-comment"></i>
                    <span>Message</span>
                </button>
            </div>
        </div>

        <!-- Property Stats -->
        <div class="property-quick-stats">
            <div class="stat-item">
                <span class="stat-label">MLS #</span>
                <span class="stat-value"><?php echo esc_html($mls_number); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Price</span>
                <span class="stat-value">$<?php echo number_format($price); ?></span>
            </div>
            <?php
            $bedrooms = $listing_data['core']['bedrooms'] ?? 0;
            $bathrooms = $listing_data['core']['bathrooms'] ?? 0;
            $sqft = $listing_data['core']['square_footage'] ?? 0;
            ?>
            <?php if ($bedrooms || $bathrooms): ?>
            <div class="stat-item">
                <span class="stat-label">Bed/Bath</span>
                <span class="stat-value"><?php echo $bedrooms; ?>bd / <?php echo $bathrooms; ?>ba</span>
            </div>
            <?php endif; ?>
            <?php if ($sqft): ?>
            <div class="stat-item">
                <span class="stat-label">Square Feet</span>
                <span class="stat-value"><?php echo number_format($sqft); ?> sqft</span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal for actions -->
<div id="action-modal" class="action-modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <button class="modal-close">&times;</button>
        <div class="modal-body">
            <!-- Content loaded dynamically -->
        </div>
    </div>
</div>

<style>
.quick-actions-widget {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 2rem;
    overflow: hidden;
}

.widget-header {
    padding: 1.5rem;
    border-bottom: 1px solid #f0f0f0;
    background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
    color: white;
}

.widget-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.widget-content {
    padding: 1.5rem;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 2rem;
}

.action-btn {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;
    width: 100%;
}

.action-btn:hover {
    border-color: var(--primary-color);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.action-btn.primary-action {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    color: white;
    border-color: var(--primary-color);
}

.action-btn.primary-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(var(--primary-rgb), 0.25);
}

.btn-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
}

.btn-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

.action-btn:not(.primary-action) .btn-icon {
    background: var(--primary-light);
    color: var(--primary-color);
}

.btn-text {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.btn-title {
    font-weight: 600;
    font-size: 1rem;
}

.btn-subtitle {
    font-size: 0.85rem;
    opacity: 0.8;
}

.action-btn:not(.primary-action) .btn-subtitle {
    color: var(--text-muted);
}

.btn-arrow {
    color: var(--primary-color);
    font-size: 0.9rem;
}

.action-btn.primary-action .btn-arrow {
    color: white;
}

/* Favorite Button States */
.favorite-btn.favorited .btn-icon {
    background: #fecaca;
    color: #dc2626;
}

.favorite-btn.favorited .btn-title::after {
    content: " ❤️";
}

/* Contact Methods */
.contact-methods {
    margin-bottom: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #f0f0f0;
}

.contact-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0 0 1rem 0;
}

.contact-buttons {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
}

.contact-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 0.5rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    background: white;
    color: var(--text-dark);
    text-decoration: none;
    transition: all 0.2s ease;
    font-size: 0.85rem;
    font-weight: 500;
}

.contact-btn:hover {
    border-color: var(--primary-color);
    color: var(--primary-color);
    transform: translateY(-1px);
}

.contact-btn i {
    font-size: 1.2rem;
}

.phone-btn:hover {
    border-color: #10b981;
    color: #10b981;
}

.email-btn:hover {
    border-color: #3b82f6;
    color: #3b82f6;
}

.message-btn:hover {
    border-color: #8b5cf6;
    color: #8b5cf6;
}

/* Property Stats */
.property-quick-stats {
    padding-top: 2rem;
    border-top: 1px solid #f0f0f0;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f5f5f5;
}

.stat-item:last-child {
    border-bottom: none;
}

.stat-label {
    font-size: 0.9rem;
    color: var(--text-muted);
}

.stat-value {
    font-weight: 600;
    color: var(--text-dark);
}

/* Modal */
.action-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
}

.modal-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow: hidden;
}

.modal-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 40px;
    height: 40px;
    border: none;
    background: #f3f4f6;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: var(--text-muted);
    z-index: 1;
}

.modal-close:hover {
    background: #e5e7eb;
}

.modal-body {
    padding: 2rem;
}

@media (max-width: 768px) {
    .contact-buttons {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    
    .contact-btn {
        flex-direction: row;
        justify-content: center;
        padding: 0.75rem;
    }
    
    .btn-content {
        gap: 0.75rem;
    }
    
    .btn-icon {
        width: 35px;
        height: 35px;
    }
    
    .widget-content {
        padding: 1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const quickActions = {
        init() {
            this.bindEvents();
            this.loadFavoriteState();
        },

        bindEvents() {
            // Action buttons
            document.querySelectorAll('.action-btn[data-action]').forEach(btn => {
                btn.addEventListener('click', (e) => this.handleAction(e));
            });

            // Contact buttons
            document.querySelectorAll('.contact-btn[data-action]').forEach(btn => {
                btn.addEventListener('click', (e) => this.handleAction(e));
            });

            // Modal close
            document.querySelector('.modal-close')?.addEventListener('click', () => this.closeModal());
            document.querySelector('.modal-overlay')?.addEventListener('click', () => this.closeModal());
        },

        handleAction(e) {
            const action = e.currentTarget.dataset.action;
            const listingId = e.currentTarget.dataset.listingId;
            const url = e.currentTarget.dataset.url;

            switch(action) {
                case 'schedule-tour':
                    this.scheduleTour();
                    break;
                case 'request-info':
                    this.requestInfo();
                    break;
                case 'virtual-tour':
                    if (url) window.open(url, '_blank');
                    break;
                case 'toggle-favorite':
                    this.toggleFavorite(listingId, e.currentTarget);
                    break;
                case 'share':
                    this.shareProperty();
                    break;
                case 'print':
                    this.printProperty();
                    break;
                case 'send-message':
                    this.sendMessage();
                    break;
            }
        },

        scheduleTour() {
            this.showModal('Schedule Tour', this.getTourForm());
            this.trackEvent('schedule_tour_click');
        },

        requestInfo() {
            this.showModal('Request Information', this.getInfoForm());
            this.trackEvent('request_info_click');
        },

        toggleFavorite(listingId, button) {
            const isFavorited = button.classList.contains('favorited');
            
            // Toggle UI immediately for better UX
            button.classList.toggle('favorited');
            
            // Update button text
            const titleSpan = button.querySelector('.btn-title');
            titleSpan.textContent = button.classList.contains('favorited') ? 'Saved!' : 'Save Listing';
            
            // Make AJAX call
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'hph_toggle_favorite',
                    listing_id: listingId,
                    nonce: hphAjax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    // Revert on failure
                    button.classList.toggle('favorited');
                    titleSpan.textContent = button.classList.contains('favorited') ? 'Saved!' : 'Save Listing';
                }
                this.trackEvent(button.classList.contains('favorited') ? 'favorite_added' : 'favorite_removed');
            })
            .catch(() => {
                // Revert on error
                button.classList.toggle('favorited');
                titleSpan.textContent = button.classList.contains('favorited') ? 'Saved!' : 'Save Listing';
            });
        },

        shareProperty() {
            if (navigator.share) {
                navigator.share({
                    title: document.title,
                    url: window.location.href
                });
            } else {
                this.showModal('Share Property', this.getShareForm());
            }
            this.trackEvent('share_property_click');
        },

        printProperty() {
            window.print();
            this.trackEvent('print_property_click');
        },

        sendMessage() {
            this.showModal('Send Message', this.getMessageForm());
            this.trackEvent('send_message_click');
        },

        showModal(title, content) {
            const modal = document.getElementById('action-modal');
            const modalBody = modal.querySelector('.modal-body');
            
            modalBody.innerHTML = `<h3>${title}</h3>${content}`;
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        },

        closeModal() {
            const modal = document.getElementById('action-modal');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        },

        loadFavoriteState() {
            const listingId = <?php echo json_encode($listing_id); ?>;
            const favoriteBtn = document.querySelector(`[data-listing-id="${listingId}"]`);
            
            if (favoriteBtn && this.isLoggedIn()) {
                // Check if favorited (simplified - you'd implement actual check)
                // favoriteBtn.classList.toggle('favorited', userHasFavorited);
            }
        },

        isLoggedIn() {
            return document.body.classList.contains('logged-in');
        },

        trackEvent(eventName) {
            if (typeof gtag !== 'undefined') {
                gtag('event', eventName, {
                    event_category: 'listing_actions',
                    event_label: window.location.pathname
                });
            }
        },

        getTourForm() {
            return `
                <form class="action-form">
                    <div class="form-group">
                        <label for="tour-name">Your Name *</label>
                        <input type="text" id="tour-name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="tour-email">Email *</label>
                        <input type="email" id="tour-email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="tour-phone">Phone</label>
                        <input type="tel" id="tour-phone" name="phone">
                    </div>
                    <div class="form-group">
                        <label for="tour-date">Preferred Date</label>
                        <input type="date" id="tour-date" name="date" min="${new Date().toISOString().split('T')[0]}">
                    </div>
                    <div class="form-group">
                        <label for="tour-time">Preferred Time</label>
                        <select id="tour-time" name="time">
                            <option value="">Select time...</option>
                            <option value="morning">Morning (9 AM - 12 PM)</option>
                            <option value="afternoon">Afternoon (12 PM - 5 PM)</option>
                            <option value="evening">Evening (5 PM - 8 PM)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tour-message">Message (Optional)</label>
                        <textarea id="tour-message" name="message" rows="3" placeholder="Any specific questions or requirements?"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Schedule Tour</button>
                </form>
            `;
        },

        getInfoForm() {
            return `
                <form class="action-form">
                    <div class="form-group">
                        <label for="info-name">Your Name *</label>
                        <input type="text" id="info-name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="info-email">Email *</label>
                        <input type="email" id="info-email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="info-phone">Phone</label>
                        <input type="tel" id="info-phone" name="phone">
                    </div>
                    <div class="form-group">
                        <label>Information Requested:</label>
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="info_type[]" value="property_details"> Property Details</label>
                            <label><input type="checkbox" name="info_type[]" value="neighborhood_info"> Neighborhood Information</label>
                            <label><input type="checkbox" name="info_type[]" value="market_analysis"> Market Analysis</label>
                            <label><input type="checkbox" name="info_type[]" value="financing_options"> Financing Options</label>
                            <label><input type="checkbox" name="info_type[]" value="similar_properties"> Similar Properties</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="info-message">Additional Questions</label>
                        <textarea id="info-message" name="message" rows="3" placeholder="What would you like to know about this property?"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Request Information</button>
                </form>
            `;
        },

        getMessageForm() {
            return `
                <form class="action-form">
                    <div class="form-group">
                        <label for="msg-name">Your Name *</label>
                        <input type="text" id="msg-name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="msg-email">Email *</label>
                        <input type="email" id="msg-email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="msg-phone">Phone</label>
                        <input type="tel" id="msg-phone" name="phone">
                    </div>
                    <div class="form-group">
                        <label for="msg-subject">Subject</label>
                        <select id="msg-subject" name="subject">
                            <option value="general">General Inquiry</option>
                            <option value="tour">Schedule a Tour</option>
                            <option value="offer">Making an Offer</option>
                            <option value="financing">Financing Questions</option>
                            <option value="neighborhood">Neighborhood Info</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="msg-content">Message *</label>
                        <textarea id="msg-content" name="message" rows="4" required placeholder="Hi, I'm interested in this property and would like to learn more..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Send Message</button>
                </form>
            `;
        },

        getShareForm() {
            const url = window.location.href;
            return `
                <div class="share-options">
                    <p>Share this property:</p>
                    <div class="share-buttons">
                        <button class="share-btn facebook" onclick="window.open('https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}', '_blank')">
                            <i class="fab fa-facebook-f"></i> Facebook
                        </button>
                        <button class="share-btn twitter" onclick="window.open('https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}', '_blank')">
                            <i class="fab fa-twitter"></i> Twitter
                        </button>
                        <button class="share-btn linkedin" onclick="window.open('https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}', '_blank')">
                            <i class="fab fa-linkedin-in"></i> LinkedIn
                        </button>
                        <button class="share-btn email" onclick="window.location.href='mailto:?subject=${encodeURIComponent(document.title)}&body=${encodeURIComponent(url)}'">
                            <i class="fas fa-envelope"></i> Email
                        </button>
                    </div>
                    <div class="share-link">
                        <label for="share-url">Direct Link:</label>
                        <div class="link-input-group">
                            <input type="text" id="share-url" value="${url}" readonly>
                            <button class="copy-btn" onclick="navigator.clipboard.writeText('${url}').then(() => this.textContent='Copied!')">
                                Copy
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }
    };

    quickActions.init();
});
</script>

<style>
/* Modal Form Styles */
.action-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    font-weight: 500;
    color: var(--text-dark);
    font-size: 0.9rem;
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.2s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary-color);
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.checkbox-group label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 400;
    cursor: pointer;
}

.checkbox-group input[type="checkbox"] {
    margin: 0;
    width: auto;
}

/* Share Form Styles */
.share-options {
    text-align: center;
}

.share-buttons {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
    margin: 1rem 0;
}

.share-btn {
    padding: 0.75rem 1rem;
    border: none;
    border-radius: 6px;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.share-btn.facebook { background: #1877f2; }
.share-btn.twitter { background: #1da1f2; }
.share-btn.linkedin { background: #0a66c2; }
.share-btn.email { background: #6b7280; }

.share-btn:hover {
    transform: translateY(-1px);
    opacity: 0.9;
}

.share-link {
    margin-top: 1.5rem;
    text-align: left;
}

.share-link label {
    font-weight: 500;
    color: var(--text-dark);
    margin-bottom: 0.5rem;
    display: block;
}

.link-input-group {
    display: flex;
    gap: 0.5rem;
}

.link-input-group input {
    flex: 1;
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
    font-size: 0.9rem;
    background: #f9fafb;
}

.copy-btn {
    padding: 0.75rem 1rem;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    white-space: nowrap;
    transition: background 0.2s ease;
}

.copy-btn:hover {
    background: var(--primary-dark);
}

@media (max-width: 480px) {
    .share-buttons {
        grid-template-columns: 1fr;
    }
    
    .link-input-group {
        flex-direction: column;
    }
}