<?php
/**
 * Dashboard Marketing Section Template
 * 
 * Displays marketing tools including graphics generation, social media content,
 * and promotional materials for real estate agents.
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get agent data (available from parent template)
$current_agent_id = $current_agent_id ?? get_current_user_id();
$current_user = $current_user ?? wp_get_current_user();

// Get agent's listings for graphic generation
$agent_listings = get_posts([
    'author' => $current_agent_id,
    'post_type' => 'listing',
    'post_status' => 'publish',
    'numberposts' => -1,
    'orderby' => 'date',
    'order' => 'DESC'
]);

// Check if graphics classes are available
$graphics_available = class_exists('HappyPlace\Graphics\Social_Media_Graphics') && 
                     class_exists('HappyPlace\Graphics\Flyer_Generator');

?>

<div class="hph-marketing-section">
    
    <!-- Marketing Tools Header -->
    <div class="hph-marketing-header">
        <div class="hph-marketing-title-group">
            <h2 class="hph-section-title">
                <i class="fas fa-palette"></i>
                <?php esc_html_e('Marketing Tools', 'happy-place'); ?>
            </h2>
            <p class="hph-section-description">
                <?php esc_html_e('Create professional marketing materials for your listings and brand.', 'happy-place'); ?>
            </p>
        </div>
        
        <?php if ($graphics_available): ?>
        <div class="hph-marketing-stats">
            <div class="hph-quick-stat">
                <div class="hph-quick-stat-value"><?php echo count($agent_listings); ?></div>
                <div class="hph-quick-stat-label"><?php esc_html_e('Properties', 'happy-place'); ?></div>
            </div>
            <div class="hph-quick-stat">
                <div class="hph-quick-stat-value">8</div>
                <div class="hph-quick-stat-label"><?php esc_html_e('Templates', 'happy-place'); ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!$graphics_available): ?>
    
    <!-- Graphics System Not Available -->
    <div class="hph-marketing-unavailable">
        <div class="hph-empty-state">
            <div class="hph-empty-state-icon">
                <i class="fas fa-tools"></i>
            </div>
            <h3 class="hph-empty-state-title">
                <?php esc_html_e('Graphics Tools Setup Required', 'happy-place'); ?>
            </h3>
            <p class="hph-empty-state-description">
                <?php esc_html_e('The marketing graphics system needs to be configured. Please contact your administrator to enable these features.', 'happy-place'); ?>
            </p>
            <div class="hph-empty-state-actions">
                <a href="#" class="action-btn action-btn--primary" onclick="HphDashboard.showToast('Please contact your administrator to enable marketing tools.', 'info')">
                    <i class="fas fa-envelope"></i>
                    <?php esc_html_e('Contact Administrator', 'happy-place'); ?>
                </a>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    
    <!-- Marketing Tools Grid -->
    <div class="hph-marketing-tools">
        
        <!-- Flyer Generator -->
        <div class="hph-marketing-tool-card">
            <div class="hph-marketing-tool-header">
                <div class="hph-marketing-tool-icon">
                    <i class="fas fa-file-image"></i>
                </div>
                <div class="hph-marketing-tool-info">
                    <h3><?php esc_html_e('Property Flyers', 'happy-place'); ?></h3>
                    <p><?php esc_html_e('Create professional listing flyers with customizable layouts', 'happy-place'); ?></p>
                </div>
            </div>
            
            <div class="hph-marketing-tool-content">
                <div class="hph-listing-selector">
                    <label for="flyer-listing-select" class="hph-form-label">
                        <?php esc_html_e('Select Property:', 'happy-place'); ?>
                    </label>
                    <select id="flyer-listing-select" class="hph-form-select">
                        <option value=""><?php esc_html_e('Choose a listing...', 'happy-place'); ?></option>
                        <?php foreach ($agent_listings as $listing): ?>
                            <option value="<?php echo esc_attr($listing->ID); ?>">
                                <?php echo esc_html($listing->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="hph-template-selector">
                    <label class="hph-form-label"><?php esc_html_e('Template Style:', 'happy-place'); ?></label>
                    <div class="hph-template-grid">
                        <div class="hph-template-option" data-template="modern">
                            <div class="hph-template-preview">
                                <div class="hph-template-thumb hph-template-thumb--modern"></div>
                            </div>
                            <span><?php esc_html_e('Modern', 'happy-place'); ?></span>
                        </div>
                        <div class="hph-template-option" data-template="classic">
                            <div class="hph-template-preview">
                                <div class="hph-template-thumb hph-template-thumb--classic"></div>
                            </div>
                            <span><?php esc_html_e('Classic', 'happy-place'); ?></span>
                        </div>
                        <div class="hph-template-option" data-template="luxury">
                            <div class="hph-template-preview">
                                <div class="hph-template-thumb hph-template-thumb--luxury"></div>
                            </div>
                            <span><?php esc_html_e('Luxury', 'happy-place'); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="hph-marketing-tool-actions">
                    <button type="button" class="action-btn action-btn--primary" onclick="HphMarketing.generateFlyer()">
                        <i class="fas fa-magic"></i>
                        <?php esc_html_e('Generate Flyer', 'happy-place'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Social Media Graphics -->
        <div class="hph-marketing-tool-card">
            <div class="hph-marketing-tool-header">
                <div class="hph-marketing-tool-icon">
                    <i class="fas fa-share-alt"></i>
                </div>
                <div class="hph-marketing-tool-info">
                    <h3><?php esc_html_e('Social Media Posts', 'happy-place'); ?></h3>
                    <p><?php esc_html_e('Create optimized graphics for Facebook, Instagram, and Twitter', 'happy-place'); ?></p>
                </div>
            </div>
            
            <div class="hph-marketing-tool-content">
                <div class="hph-listing-selector">
                    <label for="social-listing-select" class="hph-form-label">
                        <?php esc_html_e('Select Property:', 'happy-place'); ?>
                    </label>
                    <select id="social-listing-select" class="hph-form-select">
                        <option value=""><?php esc_html_e('Choose a listing...', 'happy-place'); ?></option>
                        <?php foreach ($agent_listings as $listing): ?>
                            <option value="<?php echo esc_attr($listing->ID); ?>">
                                <?php echo esc_html($listing->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="hph-platform-selector">
                    <label class="hph-form-label"><?php esc_html_e('Platform:', 'happy-place'); ?></label>
                    <div class="hph-platform-grid">
                        <div class="hph-platform-option" data-platform="facebook">
                            <i class="fab fa-facebook-f"></i>
                            <span><?php esc_html_e('Facebook', 'happy-place'); ?></span>
                        </div>
                        <div class="hph-platform-option" data-platform="instagram">
                            <i class="fab fa-instagram"></i>
                            <span><?php esc_html_e('Instagram', 'happy-place'); ?></span>
                        </div>
                        <div class="hph-platform-option" data-platform="twitter">
                            <i class="fab fa-twitter"></i>
                            <span><?php esc_html_e('Twitter', 'happy-place'); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="hph-marketing-tool-actions">
                    <button type="button" class="action-btn action-btn--primary" onclick="HphMarketing.generateSocialPost()">
                        <i class="fas fa-share"></i>
                        <?php esc_html_e('Create Post', 'happy-place'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Open House Announcements -->
        <div class="hph-marketing-tool-card">
            <div class="hph-marketing-tool-header">
                <div class="hph-marketing-tool-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="hph-marketing-tool-info">
                    <h3><?php esc_html_e('Open House Graphics', 'happy-place'); ?></h3>
                    <p><?php esc_html_e('Promotional graphics for upcoming open house events', 'happy-place'); ?></p>
                </div>
            </div>
            
            <div class="hph-marketing-tool-content">
                <?php 
                $upcoming_events = hph_get_agent_open_houses($current_agent_id, 'upcoming');
                if (!empty($upcoming_events)): 
                ?>
                <div class="hph-event-selector">
                    <label for="event-select" class="hph-form-label">
                        <?php esc_html_e('Select Open House:', 'happy-place'); ?>
                    </label>
                    <select id="event-select" class="hph-form-select">
                        <option value=""><?php esc_html_e('Choose an event...', 'happy-place'); ?></option>
                        <?php foreach ($upcoming_events as $event): ?>
                            <option value="<?php echo esc_attr($event['id']); ?>">
                                <?php echo esc_html($event['listing_title'] . ' - ' . date('M j, Y', strtotime($event['event_date']))); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="hph-marketing-tool-actions">
                    <button type="button" class="action-btn action-btn--primary" onclick="HphMarketing.generateOpenHouseGraphic()">
                        <i class="fas fa-bullhorn"></i>
                        <?php esc_html_e('Create Announcement', 'happy-place'); ?>
                    </button>
                </div>
                <?php else: ?>
                <div class="hph-empty-state hph-empty-state--small">
                    <p><?php esc_html_e('No upcoming open houses scheduled.', 'happy-place'); ?></p>
                    <a href="<?php echo esc_url(add_query_arg('section', 'open-houses')); ?>" class="action-btn action-btn--outline action-btn--sm">
                        <?php esc_html_e('Schedule Open House', 'happy-place'); ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Brand Materials -->
        <div class="hph-marketing-tool-card">
            <div class="hph-marketing-tool-header">
                <div class="hph-marketing-tool-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="hph-marketing-tool-info">
                    <h3><?php esc_html_e('Personal Branding', 'happy-place'); ?></h3>
                    <p><?php esc_html_e('Business cards, profile graphics, and branded materials', 'happy-place'); ?></p>
                </div>
            </div>
            
            <div class="hph-marketing-tool-content">
                <div class="hph-brand-options">
                    <div class="hph-brand-option" data-type="business-card">
                        <i class="fas fa-id-card"></i>
                        <span><?php esc_html_e('Business Card', 'happy-place'); ?></span>
                    </div>
                    <div class="hph-brand-option" data-type="profile-banner">
                        <i class="fas fa-image"></i>
                        <span><?php esc_html_e('Profile Banner', 'happy-place'); ?></span>
                    </div>
                    <div class="hph-brand-option" data-type="email-signature">
                        <i class="fas fa-signature"></i>
                        <span><?php esc_html_e('Email Signature', 'happy-place'); ?></span>
                    </div>
                </div>
                
                <div class="hph-marketing-tool-actions">
                    <button type="button" class="action-btn action-btn--primary" onclick="HphMarketing.generateBrandMaterial()">
                        <i class="fas fa-paint-brush"></i>
                        <?php esc_html_e('Create Material', 'happy-place'); ?>
                    </button>
                </div>
            </div>
        </div>
        
    </div>
    
    <!-- Graphics Preview Modal -->
    <div id="hph-graphics-modal" class="hph-marketing-modal hph-marketing-modal--hidden">
        <div class="hph-marketing-modal-content">
            <div class="hph-marketing-modal-header">
                <h3 id="hph-graphics-modal-title"><?php esc_html_e('Graphics Preview', 'happy-place'); ?></h3>
                <button type="button" class="hph-marketing-modal-close" onclick="HphMarketing.closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="hph-marketing-modal-body">
                <div id="hph-graphics-preview" class="hph-graphics-preview">
                    <!-- Preview content will be loaded here -->
                </div>
                
                <div class="hph-graphics-customization">
                    <div class="hph-customization-panel">
                        <h4><?php esc_html_e('Customization', 'happy-place'); ?></h4>
                        
                        <div class="hph-customization-option">
                            <label for="graphics-color-scheme"><?php esc_html_e('Color Scheme:', 'happy-place'); ?></label>
                            <select id="graphics-color-scheme" class="hph-form-select">
                                <option value="brand"><?php esc_html_e('Brand Colors', 'happy-place'); ?></option>
                                <option value="blue"><?php esc_html_e('Professional Blue', 'happy-place'); ?></option>
                                <option value="green"><?php esc_html_e('Nature Green', 'happy-place'); ?></option>
                                <option value="gold"><?php esc_html_e('Luxury Gold', 'happy-place'); ?></option>
                            </select>
                        </div>
                        
                        <div class="hph-customization-option">
                            <label for="graphics-text-overlay"><?php esc_html_e('Text Overlay:', 'happy-place'); ?></label>
                            <textarea id="graphics-text-overlay" class="hph-form-textarea" rows="3" placeholder="<?php esc_attr_e('Add custom text...', 'happy-place'); ?>"></textarea>
                        </div>
                        
                        <div class="hph-customization-option">
                            <label>
                                <input type="checkbox" id="graphics-include-contact" checked>
                                <?php esc_html_e('Include Contact Information', 'happy-place'); ?>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="hph-marketing-modal-footer">
                <button type="button" class="action-btn action-btn--outline" onclick="HphMarketing.closeModal()">
                    <?php esc_html_e('Cancel', 'happy-place'); ?>
                </button>
                <button type="button" class="action-btn action-btn--secondary" onclick="HphMarketing.regeneratePreview()">
                    <i class="fas fa-sync"></i>
                    <?php esc_html_e('Update Preview', 'happy-place'); ?>
                </button>
                <button type="button" class="action-btn action-btn--primary" onclick="HphMarketing.downloadGraphic()">
                    <i class="fas fa-download"></i>
                    <?php esc_html_e('Download', 'happy-place'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <?php endif; ?>
    
</div>

<!-- Marketing JavaScript -->
<script>
// Marketing tools functionality
window.HphMarketing = {
    
    // Initialize marketing tools
    init: function() {
        // Setup template selection
        document.querySelectorAll('.hph-template-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.hph-template-option').forEach(o => o.classList.remove('hph-template-option--selected'));
                this.classList.add('hph-template-option--selected');
            });
        });
        
        // Setup platform selection
        document.querySelectorAll('.hph-platform-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.hph-platform-option').forEach(o => o.classList.remove('hph-platform-option--selected'));
                this.classList.add('hph-platform-option--selected');
            });
        });
        
        // Setup brand option selection
        document.querySelectorAll('.hph-brand-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.hph-brand-option').forEach(o => o.classList.remove('hph-brand-option--selected'));
                this.classList.add('hph-brand-option--selected');
            });
        });
    },
    
    // Generate flyer
    generateFlyer: function() {
        const listingId = document.getElementById('flyer-listing-select').value;
        const template = document.querySelector('.hph-template-option--selected')?.dataset.template;
        
        if (!listingId) {
            HphDashboard.showToast('Please select a listing first.', 'warning');
            return;
        }
        
        if (!template) {
            HphDashboard.showToast('Please select a template style.', 'warning');
            return;
        }
        
        HphDashboard.showLoading();
        
        // Simulate API call
        setTimeout(() => {
            this.showGraphicsModal('Property Flyer', {
                type: 'flyer',
                listing_id: listingId,
                template: template
            });
            HphDashboard.hideLoading();
        }, 1500);
    },
    
    // Generate social media post
    generateSocialPost: function() {
        const listingId = document.getElementById('social-listing-select').value;
        const platform = document.querySelector('.hph-platform-option--selected')?.dataset.platform;
        
        if (!listingId) {
            HphDashboard.showToast('Please select a listing first.', 'warning');
            return;
        }
        
        if (!platform) {
            HphDashboard.showToast('Please select a platform.', 'warning');
            return;
        }
        
        HphDashboard.showLoading();
        
        // Simulate API call
        setTimeout(() => {
            this.showGraphicsModal('Social Media Post', {
                type: 'social',
                listing_id: listingId,
                platform: platform
            });
            HphDashboard.hideLoading();
        }, 1200);
    },
    
    // Generate open house graphic
    generateOpenHouseGraphic: function() {
        const eventId = document.getElementById('event-select').value;
        
        if (!eventId) {
            HphDashboard.showToast('Please select an open house event.', 'warning');
            return;
        }
        
        HphDashboard.showLoading();
        
        // Simulate API call
        setTimeout(() => {
            this.showGraphicsModal('Open House Announcement', {
                type: 'open-house',
                event_id: eventId
            });
            HphDashboard.hideLoading();
        }, 1000);
    },
    
    // Generate brand material
    generateBrandMaterial: function() {
        const brandType = document.querySelector('.hph-brand-option--selected')?.dataset.type;
        
        if (!brandType) {
            HphDashboard.showToast('Please select a brand material type.', 'warning');
            return;
        }
        
        HphDashboard.showLoading();
        
        // Simulate API call
        setTimeout(() => {
            this.showGraphicsModal('Brand Material', {
                type: 'brand',
                brand_type: brandType
            });
            HphDashboard.hideLoading();
        }, 800);
    },
    
    // Show graphics modal
    showGraphicsModal: function(title, options) {
        document.getElementById('hph-graphics-modal-title').textContent = title;
        document.getElementById('hph-graphics-modal').classList.remove('hph-marketing-modal--hidden');
        
        // Generate preview content based on type
        this.generatePreview(options);
    },
    
    // Generate preview content
    generatePreview: function(options) {
        const preview = document.getElementById('hph-graphics-preview');
        
        // Create a mock preview based on type
        let previewContent = '';
        
        switch(options.type) {
            case 'flyer':
                previewContent = `
                    <div class="hph-graphics-preview-flyer">
                        <div class="hph-preview-image">
                            <div class="hph-preview-placeholder">
                                <i class="fas fa-image"></i>
                                <p>Property Image</p>
                            </div>
                        </div>
                        <div class="hph-preview-content">
                            <h3>Property Details</h3>
                            <p>Price, beds, baths, etc.</p>
                            <div class="hph-preview-agent">Agent Contact Info</div>
                        </div>
                    </div>
                `;
                break;
            
            case 'social':
                previewContent = `
                    <div class="hph-graphics-preview-social hph-graphics-preview--${options.platform}">
                        <div class="hph-preview-header">${options.platform.charAt(0).toUpperCase() + options.platform.slice(1)} Post</div>
                        <div class="hph-preview-image">
                            <div class="hph-preview-placeholder">
                                <i class="fas fa-home"></i>
                                <p>Listing Photo</p>
                            </div>
                        </div>
                        <div class="hph-preview-caption">Property caption and hashtags</div>
                    </div>
                `;
                break;
            
            default:
                previewContent = `
                    <div class="hph-graphics-preview-generic">
                        <div class="hph-preview-placeholder">
                            <i class="fas fa-magic"></i>
                            <p>Preview will appear here</p>
                        </div>
                    </div>
                `;
        }
        
        preview.innerHTML = previewContent;
    },
    
    // Regenerate preview with current settings
    regeneratePreview: function() {
        HphDashboard.showToast('Preview updated with new settings.', 'success');
    },
    
    // Download graphic
    downloadGraphic: function() {
        HphDashboard.showToast('Download started. Check your downloads folder.', 'success');
        this.closeModal();
    },
    
    // Close modal
    closeModal: function() {
        document.getElementById('hph-graphics-modal').classList.add('hph-marketing-modal--hidden');
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (HphDashboard.currentSection === 'marketing') {
        HphMarketing.init();
    }
});
</script>
