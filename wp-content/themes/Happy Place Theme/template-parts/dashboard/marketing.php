<?php
/**
 * Dashboard Marketing Section
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

// Use the Marketing Section class from the plugin
if (class_exists('HappyPlace\Dashboard\Sections\Marketing_Section')) {
    $marketing_section = new \HappyPlace\Dashboard\Sections\Marketing_Section();
    $marketing_section->render();
} else {
    ?>
    <div class="hph-empty-state">
        <div class="empty-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h4 class="empty-title"><?php _e('Marketing Section Not Available', 'happy-place'); ?></h4>
        <p class="empty-description"><?php _e('The marketing section is not properly loaded. Please check plugin configuration.', 'happy-place'); ?></p>
    </div>
    <?php
}
?>

<script>
// Marketing Section JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Generate Flyer Button
    const generateFlyerBtn = document.getElementById('generate-flyer-btn');
    if (generateFlyerBtn) {
        generateFlyerBtn.addEventListener('click', function() {
            openFlyerModal();
        });
    }
    
    // Schedule Post Button
    const schedulePostBtn = document.getElementById('schedule-post-btn');
    if (schedulePostBtn) {
        schedulePostBtn.addEventListener('click', function() {
            openSocialMediaModal();
        });
    }
    
    // Create Email Campaign Button
    const createEmailBtn = document.getElementById('create-email-btn');  
    if (createEmailBtn) {
        createEmailBtn.addEventListener('click', function() {
            openEmailCampaignModal();
        });          
    }
    
    // Create Campaign Button
    const createCampaignBtn = document.getElementById('create-campaign-btn');
    if (createCampaignBtn) {
        createCampaignBtn.addEventListener('click', function() {
            openCampaignModal();
        });
    }
    
    // Create First Campaign Button  
    const createFirstCampaignBtn = document.getElementById('create-first-campaign-btn');
    if (createFirstCampaignBtn) {
        createFirstCampaignBtn.addEventListener('click', function() {
            openCampaignModal();
        });
    }
});

// Modal Functions - Production Implementation
function openFlyerModal() {
    // Create and show flyer generation modal
    const modal = createFlyerModal();
    document.body.appendChild(modal);
    modal.style.display = 'flex';
    
    // Focus on first input
    const firstInput = modal.querySelector('input, select');
    if (firstInput) firstInput.focus();
}

function openSocialMediaModal() {
    // Create and show social media scheduling modal
    const modal = createSocialMediaModal();
    document.body.appendChild(modal);
    modal.style.display = 'flex';
    
    // Focus on first input
    const firstInput = modal.querySelector('input, select, textarea');
    if (firstInput) firstInput.focus();
}

function openEmailCampaignModal() {
    // Create and show email campaign creation modal
    const modal = createEmailCampaignModal();
    document.body.appendChild(modal);
    modal.style.display = 'flex';
    
    // Focus on first input
    const firstInput = modal.querySelector('input, select, textarea');
    if (firstInput) firstInput.focus();
}

function openCampaignModal() {
    // Create and show general campaign creation modal
    const modal = createCampaignModal();
    document.body.appendChild(modal);
    modal.style.display = 'flex';
    
    // Focus on first input
    const firstInput = modal.querySelector('input, select, textarea');
    if (firstInput) firstInput.focus();
}

// Modal Creation Functions
function createFlyerModal() {
    const modal = document.createElement('div');
    modal.className = 'hph-modal-overlay';
    modal.innerHTML = `
        <div class="hph-modal hph-flyer-modal">
            <div class="hph-modal-header">
                <h3><?php esc_html_e('Generate Property Flyer', 'happy-place'); ?></h3>
                <button type="button" class="hph-modal-close" onclick="closeModal(this)">&times;</button>
            </div>
            <div class="hph-modal-body">
                <form id="flyer-generation-form">
                    <div class="hph-form-group">
                        <label for="flyer-listing"><?php esc_html_e('Select Listing', 'happy-place'); ?></label>
                        <select id="flyer-listing" name="listing_id" required>
                            <option value=""><?php esc_html_e('Choose a listing...', 'happy-place'); ?></option>
                            <?php
                            $user_listings = get_posts(array(
                                'post_type' => 'listing',
                                'author' => get_current_user_id(),
                                'post_status' => 'publish',
                                'numberposts' => -1
                            ));
                            foreach ($user_listings as $listing) {
                                echo '<option value="' . esc_attr($listing->ID) . '">' . esc_html($listing->post_title) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="hph-form-group">
                        <label for="flyer-template"><?php esc_html_e('Flyer Template', 'happy-place'); ?></label>
                        <select id="flyer-template" name="template_id" required>
                            <option value="modern"><?php esc_html_e('Modern Layout', 'happy-place'); ?></option>
                            <option value="classic"><?php esc_html_e('Classic Layout', 'happy-place'); ?></option>
                            <option value="luxury"><?php esc_html_e('Luxury Layout', 'happy-place'); ?></option>
                        </select>
                    </div>
                    <div class="hph-form-group">
                        <label for="flyer-size"><?php esc_html_e('Size', 'happy-place'); ?></label>
                        <select id="flyer-size" name="size">
                            <option value="letter"><?php esc_html_e('Letter (8.5" x 11")', 'happy-place'); ?></option>
                            <option value="legal"><?php esc_html_e('Legal (8.5" x 14")', 'happy-place'); ?></option>
                            <option value="tabloid"><?php esc_html_e('Tabloid (11" x 17")', 'happy-place'); ?></option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="hph-modal-footer">
                <button type="button" class="hph-btn hph-btn-secondary" onclick="closeModal(this)"><?php esc_html_e('Cancel', 'happy-place'); ?></button>
                <button type="button" class="hph-btn hph-btn-primary" onclick="handleFlyerGeneration()"><?php esc_html_e('Generate Flyer', 'happy-place'); ?></button>
            </div>
        </div>
    `;
    return modal;
}

// Modal Utility Functions
function closeModal(element) {
    // Find the modal overlay from the button or element
    const modal = element.closest('.hph-modal-overlay') || element.closest('.hph-modal');
    if (modal) {
        modal.style.display = 'none';
        // Remove from DOM after fade animation
        setTimeout(() => {
            if (modal.parentNode) {
                modal.parentNode.removeChild(modal);
            }
        }, 300);
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('hph-modal-overlay')) {
        closeModal(e.target);
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const openModals = document.querySelectorAll('.hph-modal-overlay');
        openModals.forEach(modal => closeModal(modal));
    }
});

// Modal Creation Functions
function createFlyerModal() {
    const modal = document.createElement('div');
    modal.className = 'hph-modal-overlay';
    modal.innerHTML = `
        <div class="hph-modal hph-flyer-modal">
            <div class="hph-modal-header">
                <h3><?php esc_html_e('Generate Property Flyer', 'happy-place'); ?></h3>
                <button type="button" class="hph-modal-close" onclick="closeModal(this)">&times;</button>
            </div>
            <div class="hph-modal-body">
                <form id="flyer-generation-form">
                    <div class="hph-form-group">
                        <label for="flyer-listing"><?php esc_html_e('Select Listing', 'happy-place'); ?></label>
                        <select id="flyer-listing" name="listing_id" required>
                            <option value=""><?php esc_html_e('Choose a listing...', 'happy-place'); ?></option>
                            <?php
                            $user_listings = get_posts(array(
                                'post_type' => 'listing',
                                'author' => get_current_user_id(),
                                'post_status' => 'publish',
                                'numberposts' => -1
                            ));
                            foreach ($user_listings as $listing) {
                                echo '<option value="' . esc_attr($listing->ID) . '">' . esc_html($listing->post_title) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="hph-form-group">
                        <label for="flyer-template"><?php esc_html_e('Flyer Template', 'happy-place'); ?></label>
                        <select id="flyer-template" name="template_id" required>
                            <option value="modern"><?php esc_html_e('Modern Layout', 'happy-place'); ?></option>
                            <option value="classic"><?php esc_html_e('Classic Layout', 'happy-place'); ?></option>
                            <option value="luxury"><?php esc_html_e('Luxury Layout', 'happy-place'); ?></option>
                        </select>
                    </div>
                    <div class="hph-form-group">
                        <label for="flyer-size"><?php esc_html_e('Size', 'happy-place'); ?></label>
                        <select id="flyer-size" name="size">
                            <option value="letter"><?php esc_html_e('Letter (8.5" x 11")', 'happy-place'); ?></option>
                            <option value="legal"><?php esc_html_e('Legal (8.5" x 14")', 'happy-place'); ?></option>
                            <option value="tabloid"><?php esc_html_e('Tabloid (11" x 17")', 'happy-place'); ?></option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="hph-modal-footer">
                <button type="button" class="hph-btn hph-btn-secondary" onclick="closeModal(this)"><?php esc_html_e('Cancel', 'happy-place'); ?></button>
                <button type="button" class="hph-btn hph-btn-primary" onclick="handleFlyerGeneration()"><?php esc_html_e('Generate Flyer', 'happy-place'); ?></button>
            </div>
        </div>
    `;
    return modal;
}

function createSocialMediaModal() {
    const modal = document.createElement('div');
    modal.className = 'hph-modal-overlay';
    modal.innerHTML = `
        <div class="hph-modal hph-social-modal">
            <div class="hph-modal-header">
                <h3><?php esc_html_e('Schedule Social Media Post', 'happy-place'); ?></h3>
                <button type="button" class="hph-modal-close" onclick="closeModal(this)">&times;</button>
            </div>
            <div class="hph-modal-body">
                <form id="social-media-form">
                    <div class="hph-form-group">
                        <label for="social-platforms"><?php esc_html_e('Platforms', 'happy-place'); ?></label>
                        <div class="hph-checkbox-group">
                            <label><input type="checkbox" name="platforms[]" value="facebook"> <?php esc_html_e('Facebook', 'happy-place'); ?></label>
                            <label><input type="checkbox" name="platforms[]" value="instagram"> <?php esc_html_e('Instagram', 'happy-place'); ?></label>
                            <label><input type="checkbox" name="platforms[]" value="twitter"> <?php esc_html_e('Twitter', 'happy-place'); ?></label>
                            <label><input type="checkbox" name="platforms[]" value="linkedin"> <?php esc_html_e('LinkedIn', 'happy-place'); ?></label>
                        </div>
                    </div>
                    <div class="hph-form-group">
                        <label for="social-content"><?php esc_html_e('Post Content', 'happy-place'); ?></label>
                        <textarea id="social-content" name="content" rows="4" placeholder="<?php esc_attr_e('Write your post content...', 'happy-place'); ?>" required></textarea>
                        <small class="hph-form-help"><?php esc_html_e('Character count will be shown here', 'happy-place'); ?></small>
                    </div>
                    <div class="hph-form-group">
                        <label for="social-listing"><?php esc_html_e('Related Listing (Optional)', 'happy-place'); ?></label>
                        <select id="social-listing" name="listing_id">
                            <option value=""><?php esc_html_e('No listing', 'happy-place'); ?></option>
                            <?php
                            $user_listings = get_posts(array(
                                'post_type' => 'listing',
                                'author' => get_current_user_id(),
                                'post_status' => 'publish',
                                'numberposts' => -1
                            ));
                            foreach ($user_listings as $listing) {
                                echo '<option value="' . esc_attr($listing->ID) . '">' . esc_html($listing->post_title) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="hph-form-group">
                        <label for="social-schedule"><?php esc_html_e('Schedule', 'happy-place'); ?></label>
                        <select id="social-schedule" name="schedule_type">
                            <option value="now"><?php esc_html_e('Post Now', 'happy-place'); ?></option>
                            <option value="later"><?php esc_html_e('Schedule for Later', 'happy-place'); ?></option>
                        </select>
                    </div>
                    <div class="hph-form-group" id="schedule-datetime" style="display: none;">
                        <label for="social-datetime"><?php esc_html_e('Date & Time', 'happy-place'); ?></label>
                        <input type="datetime-local" id="social-datetime" name="scheduled_time">
                    </div>
                </form>
            </div>
            <div class="hph-modal-footer">
                <button type="button" class="hph-btn hph-btn-secondary" onclick="closeModal(this)"><?php esc_html_e('Cancel', 'happy-place'); ?></button>
                <button type="button" class="hph-btn hph-btn-primary" onclick="handleSocialMediaPost()"><?php esc_html_e('Schedule Post', 'happy-place'); ?></button>
            </div>
        </div>
    `;
    
    // Add schedule type change handler
    const scheduleSelect = modal.querySelector('#social-schedule');
    const datetimeGroup = modal.querySelector('#schedule-datetime');
    scheduleSelect.addEventListener('change', function() {
        datetimeGroup.style.display = this.value === 'later' ? 'block' : 'none';
    });
    
    return modal;
}

function createEmailCampaignModal() {
    const modal = document.createElement('div');
    modal.className = 'hph-modal-overlay';
    modal.innerHTML = `
        <div class="hph-modal hph-email-modal">
            <div class="hph-modal-header">
                <h3><?php esc_html_e('Create Email Campaign', 'happy-place'); ?></h3>
                <button type="button" class="hph-modal-close" onclick="closeModal(this)">&times;</button>
            </div>
            <div class="hph-modal-body">
                <form id="email-campaign-form">
                    <div class="hph-form-group">
                        <label for="email-subject"><?php esc_html_e('Subject Line', 'happy-place'); ?></label>
                        <input type="text" id="email-subject" name="subject" required placeholder="<?php esc_attr_e('Enter email subject...', 'happy-place'); ?>">
                    </div>
                    <div class="hph-form-group">
                        <label for="email-template"><?php esc_html_e('Email Template', 'happy-place'); ?></label>
                        <select id="email-template" name="template" required>
                            <option value=""><?php esc_html_e('Choose template...', 'happy-place'); ?></option>
                            <option value="newsletter"><?php esc_html_e('Newsletter', 'happy-place'); ?></option>
                            <option value="listing-announcement"><?php esc_html_e('New Listing Announcement', 'happy-place'); ?></option>
                            <option value="market-update"><?php esc_html_e('Market Update', 'happy-place'); ?></option>
                            <option value="open-house"><?php esc_html_e('Open House Invitation', 'happy-place'); ?></option>
                        </select>
                    </div>
                    <div class="hph-form-group">
                        <label for="email-content"><?php esc_html_e('Email Content', 'happy-place'); ?></label>
                        <textarea id="email-content" name="content" rows="6" placeholder="<?php esc_attr_e('Write your email content...', 'happy-place'); ?>" required></textarea>
                    </div>
                    <div class="hph-form-group">
                        <label for="email-audience"><?php esc_html_e('Target Audience', 'happy-place'); ?></label>
                        <select id="email-audience" name="audience" required>
                            <option value=""><?php esc_html_e('Select audience...', 'happy-place'); ?></option>
                            <option value="all-contacts"><?php esc_html_e('All Contacts', 'happy-place'); ?></option>
                            <option value="buyers"><?php esc_html_e('Buyers', 'happy-place'); ?></option>
                            <option value="sellers"><?php esc_html_e('Sellers', 'happy-place'); ?></option>
                            <option value="past-clients"><?php esc_html_e('Past Clients', 'happy-place'); ?></option>
                            <option value="leads"><?php esc_html_e('New Leads', 'happy-place'); ?></option>
                        </select>
                    </div>
                    <div class="hph-form-group">
                        <label for="email-send-time"><?php esc_html_e('Send Time', 'happy-place'); ?></label>
                        <select id="email-send-time" name="send_time">
                            <option value="now"><?php esc_html_e('Send Now', 'happy-place'); ?></option>
                            <option value="schedule"><?php esc_html_e('Schedule for Later', 'happy-place'); ?></option>
                        </select>
                    </div>
                    <div class="hph-form-group" id="email-schedule-datetime" style="display: none;">
                        <label for="email-datetime"><?php esc_html_e('Send Date & Time', 'happy-place'); ?></label>
                        <input type="datetime-local" id="email-datetime" name="scheduled_send">
                    </div>
                </form>
            </div>
            <div class="hph-modal-footer">
                <button type="button" class="hph-btn hph-btn-secondary" onclick="closeModal(this)"><?php esc_html_e('Cancel', 'happy-place'); ?></button>
                <button type="button" class="hph-btn hph-btn-primary" onclick="handleEmailCampaign()"><?php esc_html_e('Create Campaign', 'happy-place'); ?></button>
            </div>
        </div>
    `;
    
    // Add send time change handler
    const sendTimeSelect = modal.querySelector('#email-send-time');
    const datetimeGroup = modal.querySelector('#email-schedule-datetime');
    sendTimeSelect.addEventListener('change', function() {
        datetimeGroup.style.display = this.value === 'schedule' ? 'block' : 'none';
    });
    
    return modal;
}

function createCampaignModal() {
    const modal = document.createElement('div');
    modal.className = 'hph-modal-overlay';
    modal.innerHTML = `
        <div class="hph-modal hph-campaign-modal">
            <div class="hph-modal-header">
                <h3><?php esc_html_e('Create Marketing Campaign', 'happy-place'); ?></h3>
                <button type="button" class="hph-modal-close" onclick="closeModal(this)">&times;</button>
            </div>
            <div class="hph-modal-body">
                <form id="campaign-form">
                    <div class="hph-form-group">
                        <label for="campaign-name"><?php esc_html_e('Campaign Name', 'happy-place'); ?></label>
                        <input type="text" id="campaign-name" name="name" required placeholder="<?php esc_attr_e('Enter campaign name...', 'happy-place'); ?>">
                    </div>
                    <div class="hph-form-group">
                        <label for="campaign-type"><?php esc_html_e('Campaign Type', 'happy-place'); ?></label>
                        <select id="campaign-type" name="type" required>
                            <option value=""><?php esc_html_e('Select type...', 'happy-place'); ?></option>
                            <option value="listing-promotion"><?php esc_html_e('Listing Promotion', 'happy-place'); ?></option>
                            <option value="lead-generation"><?php esc_html_e('Lead Generation', 'happy-place'); ?></option>
                            <option value="brand-awareness"><?php esc_html_e('Brand Awareness', 'happy-place'); ?></option>
                            <option value="market-update"><?php esc_html_e('Market Update', 'happy-place'); ?></option>
                        </select>
                    </div>
                    <div class="hph-form-group">
                        <label for="campaign-description"><?php esc_html_e('Description', 'happy-place'); ?></label>
                        <textarea id="campaign-description" name="description" rows="3" placeholder="<?php esc_attr_e('Describe your campaign goals...', 'happy-place'); ?>"></textarea>
                    </div>
                    <div class="hph-form-group">
                        <label for="campaign-duration"><?php esc_html_e('Duration', 'happy-place'); ?></label>
                        <select id="campaign-duration" name="duration">
                            <option value="1-week"><?php esc_html_e('1 Week', 'happy-place'); ?></option>
                            <option value="2-weeks"><?php esc_html_e('2 Weeks', 'happy-place'); ?></option>
                            <option value="1-month" selected><?php esc_html_e('1 Month', 'happy-place'); ?></option>
                            <option value="3-months"><?php esc_html_e('3 Months', 'happy-place'); ?></option>
                            <option value="ongoing"><?php esc_html_e('Ongoing', 'happy-place'); ?></option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="hph-modal-footer">
                <button type="button" class="hph-btn hph-btn-secondary" onclick="closeModal(this)"><?php esc_html_e('Cancel', 'happy-place'); ?></button>
                <button type="button" class="hph-btn hph-btn-primary" onclick="handleCampaignCreation()"><?php esc_html_e('Create Campaign', 'happy-place'); ?></button>
            </div>
        </div>
    `;
    return modal;
}

// Modal utility functions
function closeModal(element) {
    const modal = element.closest('.hph-modal-overlay');
    if (modal) {
        modal.remove();
    }
}

// Handle form submissions
function handleFlyerGeneration() {
    const form = document.getElementById('flyer-generation-form');
    const formData = new FormData(form);
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const listingId = formData.get('listing_id');
    const templateId = formData.get('template_id');
    
    generateFlyer(listingId, templateId);
    closeModal(form);
}

function handleSocialMediaPost() {
    const form = document.getElementById('social-media-form');
    const formData = new FormData(form);
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const postData = Object.fromEntries(formData.entries());
    postData.platforms = formData.getAll('platforms[]');
    
    schedulePost(postData);
    closeModal(form);
}

function handleEmailCampaign() {
    const form = document.getElementById('email-campaign-form');
    const formData = new FormData(form);
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const campaignData = Object.fromEntries(formData.entries());
    
    createEmailCampaign(campaignData);
    closeModal(form);
}

function handleCampaignCreation() {
    const form = document.getElementById('campaign-form');
    const formData = new FormData(form);
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const campaignData = Object.fromEntries(formData.entries());
    
    // Create campaign via AJAX
    if (window.HphDashboard && window.HphDashboard.showToast) {
        window.HphDashboard.showToast('Campaign "' + campaignData.name + '" created successfully!', 'success');
    }
    
    closeModal(form);
}

// AJAX Functions for Marketing Actions
function generateFlyer(listingId, templateId) {
    if (!window.HphDashboard) {
        console.error('Dashboard object not available');
        return;
    }
    
    HphDashboard.showLoading();
    
    fetch(HphDashboard.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=hph_generate_flyer&listing_id=${listingId}&template_id=${templateId}&nonce=${HphDashboard.nonce}`
    })
    .then(response => response.json())
    .then(data => {
        HphDashboard.hideLoading();
        
        if (data.success) {
            HphDashboard.showToast(data.data.message, 'success');
            if (data.data.download_url) {
                window.open(data.data.download_url, '_blank');
            }
        } else {
            HphDashboard.showToast(data.data.message || 'Failed to generate flyer', 'error');
        }
    })
    .catch(error => {
        HphDashboard.hideLoading();
        console.error('Error:', error);
        HphDashboard.showToast('An error occurred while generating the flyer', 'error');
    });
}

function schedulePost(postData) {
    if (!window.HphDashboard) {
        console.error('Dashboard object not available');
        return;
    }
    
    HphDashboard.showLoading();
    
    const formData = new URLSearchParams();
    formData.append('action', 'hph_schedule_social_post');
    formData.append('nonce', HphDashboard.nonce);
    Object.keys(postData).forEach(key => {
        if (Array.isArray(postData[key])) {
            postData[key].forEach(value => formData.append(`${key}[]`, value));
        } else {
            formData.append(key, postData[key]);
        }
    });
    
    fetch(HphDashboard.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        HphDashboard.hideLoading();
        
        if (data.success) {
            HphDashboard.showToast(data.data.message, 'success');
        } else {
            HphDashboard.showToast(data.data.message || 'Failed to schedule post', 'error');
        }
    })
    .catch(error => {
        HphDashboard.hideLoading();
        console.error('Error:', error);
        HphDashboard.showToast('An error occurred while scheduling the post', 'error');
    });
}

function createEmailCampaign(campaignData) {
    if (!window.HphDashboard) {
        console.error('Dashboard object not available');
        return;
    }
    
    HphDashboard.showLoading();
    
    const formData = new URLSearchParams();
    formData.append('action', 'hph_create_email_campaign');
    formData.append('nonce', HphDashboard.nonce);
    Object.keys(campaignData).forEach(key => {
        if (Array.isArray(campaignData[key])) {
            campaignData[key].forEach(value => formData.append(`${key}[]`, value));
        } else {
            formData.append(key, campaignData[key]);
        }
    });
    
    fetch(HphDashboard.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        HphDashboard.hideLoading();
        
        if (data.success) {
            HphDashboard.showToast(data.data.message, 'success');
        } else {
            HphDashboard.showToast(data.data.message || 'Failed to create email campaign', 'error');
        }
    })
    .catch(error => {
        HphDashboard.hideLoading();
        console.error('Error:', error);
        HphDashboard.showToast('An error occurred while creating the email campaign', 'error');
    });
}

// Handler Functions for Modal Actions
function handleFlyerGeneration() {
    const form = document.getElementById('flyer-generation-form');
    if (!form) return;
    
    const formData = new FormData(form);
    const flyerData = Object.fromEntries(formData);
    
    // Validate required fields
    if (!flyerData.listing_id || !flyerData.template_id) {
        if (window.HphDashboard) {
            HphDashboard.showToast('Please fill in all required fields', 'error');
        }
        return;
    }
    
    // Generate flyer with real implementation
    if (window.HphDashboard) {
        HphDashboard.showLoading();
        
        // Create FormData for AJAX submission
        const formData = new FormData(form);
        formData.append('action', 'hph_generate_flyer');
        formData.append('nonce', window.hphAjax?.nonce || '');
        
        // Submit to WordPress backend
        fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            HphDashboard.hideLoading();
            
            if (data.success) {
                HphDashboard.showToast('Flyer generated successfully!', 'success');
                
                // If flyer URL is returned, offer download
                if (data.data && data.data.flyer_url) {
                    const downloadLink = document.createElement('a');
                    downloadLink.href = data.data.flyer_url;
                    downloadLink.download = `flyer-${Date.now()}.pdf`;
                    downloadLink.click();
                }
                
                closeModal(form);
            } else {
                HphDashboard.showToast(data.data?.message || 'Failed to generate flyer', 'error');
            }
        })
        .catch(error => {
            console.error('Flyer generation error:', error);
            HphDashboard.hideLoading();
            HphDashboard.showToast('Error generating flyer. Please try again.', 'error');
        });
    }
}

function handleSocialPost() {
    const form = document.getElementById('social-media-form');
    if (!form) return;
    
    const formData = new FormData(form);
    const platforms = formData.getAll('platforms[]');
    const content = formData.get('content');
    const mediaFiles = formData.getAll('media[]');
    const postTime = formData.get('post_time');
    const scheduledDate = formData.get('scheduled_date');
    
    // Validate required fields
    if (platforms.length === 0 || !content) {
        if (window.HphDashboard) {
            HphDashboard.showToast('Please select platforms and add content', 'error');
        }
        return;
    }
    
    const postData = {
        platforms: platforms,
        content: content,
        media: mediaFiles,
        post_time: postTime,
        scheduled_date: scheduledDate
    };
    
    schedulePost(postData);
    closeModal(form);
}

function handleEmailCampaign() {
    const form = document.getElementById('email-campaign-form');
    if (!form) return;
    
    const formData = new FormData(form);
    const campaignData = Object.fromEntries(formData);
    
    // Validate required fields
    if (!campaignData.subject || !campaignData.template || !campaignData.content || !campaignData.audience) {
        if (window.HphDashboard) {
            HphDashboard.showToast('Please fill in all required fields', 'error');
        }
        return;
    }
    
    createEmailCampaign(campaignData);
    closeModal(form);
}

function handleCampaignCreation() {
    const form = document.getElementById('campaign-form');
    if (!form) return;
    
    const formData = new FormData(form);
    const campaignData = Object.fromEntries(formData);
    
    // Validate required fields
    if (!campaignData.name || !campaignData.type) {
        if (window.HphDashboard) {
            HphDashboard.showToast('Please fill in campaign name and type', 'error');
        }
        return;
    }
    
    // Create campaign with real implementation
    if (window.HphDashboard) {
        HphDashboard.showLoading();
        
        // Create FormData for AJAX submission
        const formData = new FormData(form);
        formData.append('action', 'hph_create_campaign');
        formData.append('nonce', window.hphAjax?.nonce || '');
        
        // Submit to WordPress backend
        fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            HphDashboard.hideLoading();
            
            if (data.success) {
                HphDashboard.showToast('Campaign created successfully!', 'success');
                
                // Refresh campaigns list if available
                if (typeof refreshCampaignsList === 'function') {
                    refreshCampaignsList();
                }
                
                closeModal(form);
            } else {
                HphDashboard.showToast(data.data?.message || 'Failed to create campaign', 'error');
            }
        })
        .catch(error => {
            console.error('Campaign creation error:', error);
            HphDashboard.hideLoading();
            HphDashboard.showToast('Error creating campaign. Please try again.', 'error');
        });
    }
}
</script>
