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

// Modal Functions
function openFlyerModal() {
    // TODO: Show flyer generation modal with listing selection and template options
    if (window.HphDashboard && window.HphDashboard.showToast) {
        window.HphDashboard.showToast('Flyer generator modal would open here', 'info');
    } else {
        console.log('Opening flyer generator modal...');
    }
}

function openSocialMediaModal() {
    // TODO: Show social media scheduling modal
    if (window.HphDashboard && window.HphDashboard.showToast) {
        window.HphDashboard.showToast('Social media scheduler modal would open here', 'info');
    } else {
        console.log('Opening social media scheduler modal...');
    }
}

function openEmailCampaignModal() {
    // TODO: Show email campaign creation modal
    if (window.HphDashboard && window.HphDashboard.showToast) {
        window.HphDashboard.showToast('Email campaign modal would open here', 'info');
    } else {
        console.log('Opening email campaign modal...');
    }
}

function openCampaignModal() {
    // TODO: Show general campaign creation modal
    if (window.HphDashboard && window.HphDashboard.showToast) {
        window.HphDashboard.showToast('Campaign creation modal would open here', 'info');
    } else {
        console.log('Opening campaign creation modal...');
    }
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
</script>
