<?php
/**
 * Dashboard Settings Section
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
?>

<div class="hph-dashboard-settings">
    
    <!-- Settings Header -->
    <div class="settings-header">
        <div class="header-content">
            <h2 class="page-title">Settings</h2>
            <p class="page-subtitle">Manage your account and dashboard preferences</p>
        </div>
    </div>

    <!-- Settings Tabs -->
    <div class="settings-tabs">
        <nav class="settings-nav">
            <button class="settings-tab-btn active" onclick="showSettingsTab('profile')">
                <i class="fas fa-user"></i> Profile
            </button>
            <button class="settings-tab-btn" onclick="showSettingsTab('notifications')">
                <i class="fas fa-bell"></i> Notifications
            </button>
            <button class="settings-tab-btn" onclick="showSettingsTab('branding')">
                <i class="fas fa-palette"></i> Branding
            </button>
            <button class="settings-tab-btn" onclick="showSettingsTab('preferences')">
                <i class="fas fa-cog"></i> Preferences
            </button>
        </nav>
    </div>

    <!-- Settings Content -->
    <div class="settings-content">
        
        <!-- Profile Settings Tab -->
        <div class="settings-tab-content" id="settings-profile" style="display: block;">
            <div class="settings-section">
                <h3>Profile Information</h3>
                
                <form class="settings-form" onsubmit="saveProfileSettings(event)">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="display-name">Display Name</label>
                            <input type="text" 
                                   id="display-name" 
                                   name="display_name" 
                                   value="<?php echo esc_attr($current_user->display_name); ?>"
                                   class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo esc_attr($current_user->user_email); ?>"
                                   class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" 
                                   id="phone" 
                                   name="phone" 
                                   value="<?php echo esc_attr(get_user_meta($current_user->ID, 'phone', true)); ?>"
                                   class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="license-number">License Number</label>
                            <input type="text" 
                                   id="license-number" 
                                   name="license_number" 
                                   value="<?php echo esc_attr(get_user_meta($current_user->ID, 'license_number', true)); ?>"
                                   class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea id="bio" 
                                  name="bio" 
                                  rows="4" 
                                  class="form-control"
                                  placeholder="Tell potential clients about yourself..."><?php echo esc_textarea(get_user_meta($current_user->ID, 'bio', true)); ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="action-btn action-btn--primary">Save Profile</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Notifications Tab -->
        <div class="settings-tab-content" id="settings-notifications" style="display: none;">
            <div class="settings-section">
                <h3>Notification Preferences</h3>
                
                <form class="settings-form" onsubmit="saveNotificationSettings(event)">
                    <div class="notification-group">
                        <h4>Email Notifications</h4>
                        
                        <div class="form-check">
                            <input type="checkbox" id="email-new-inquiries" name="email_new_inquiries" checked>
                            <label for="email-new-inquiries">New listing inquiries</label>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="email-new-leads" name="email_new_leads" checked>
                            <label for="email-new-leads">New leads</label>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="email-listing-updates" name="email_listing_updates">
                            <label for="email-listing-updates">Listing status updates</label>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="email-weekly-reports" name="email_weekly_reports" checked>
                            <label for="email-weekly-reports">Weekly performance reports</label>
                        </div>
                    </div>
                    
                    <div class="notification-group">
                        <h4>Browser Notifications</h4>
                        
                        <div class="form-check">
                            <input type="checkbox" id="browser-inquiries" name="browser_inquiries">
                            <label for="browser-inquiries">Real-time inquiry notifications</label>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="browser-leads" name="browser_leads">
                            <label for="browser-leads">New lead notifications</label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="action-btn action-btn--primary">Save Notifications</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Branding Tab -->
        <div class="settings-tab-content" id="settings-branding" style="display: none;">
            <div class="settings-section">
                <h3>Personal Branding</h3>
                
                <form class="settings-form" onsubmit="saveBrandingSettings(event)">
                    <div class="form-group">
                        <label for="brand-color">Primary Brand Color</label>
                        <div class="color-input-group">
                            <input type="color" 
                                   id="brand-color" 
                                   name="brand_color" 
                                   value="#0073aa" 
                                   class="color-input">
                            <input type="text" 
                                   value="#0073aa" 
                                   class="color-text form-control"
                                   placeholder="#0073aa">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="logo-upload">Logo/Profile Image</label>
                        <div class="file-upload-group">
                            <button type="button" class="action-btn action-btn--secondary" onclick="openMediaUploader()">
                                Choose Image
                            </button>
                            <span class="file-upload-text">No image selected</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="tagline">Personal Tagline</label>
                        <input type="text" 
                               id="tagline" 
                               name="tagline" 
                               placeholder="e.g., Your Trusted Real Estate Professional"
                               class="form-control">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="action-btn action-btn--primary">Save Branding</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Preferences Tab -->
        <div class="settings-tab-content" id="settings-preferences" style="display: none;">
            <div class="settings-section">
                <h3>Dashboard Preferences</h3>
                
                <form class="settings-form" onsubmit="savePreferencesSettings(event)">
                    <div class="form-group">
                        <label for="dashboard-theme">Dashboard Theme</label>
                        <select id="dashboard-theme" name="dashboard_theme" class="form-control">
                            <option value="light">Light</option>
                            <option value="dark">Dark</option>
                            <option value="auto">Auto (follows system)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="items-per-page">Items per page</label>
                        <select id="items-per-page" name="items_per_page" class="form-control">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="default-view">Default listings view</label>
                        <select id="default-view" name="default_view" class="form-control">
                            <option value="grid">Grid view</option>
                            <option value="list" selected>List view</option>
                        </select>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" id="auto-refresh" name="auto_refresh" checked>
                        <label for="auto-refresh">Auto-refresh dashboard data</label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="action-btn action-btn--primary">Save Preferences</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showSettingsTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.settings-tab-content').forEach(tab => {
        tab.style.display = 'none';
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.settings-tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab and mark button as active
    document.getElementById(`settings-${tabName}`).style.display = 'block';
    event.target.classList.add('active');
}

function saveProfileSettings(event) {
    event.preventDefault();
    // Implementation for saving profile settings
    alert('Profile settings saved successfully!');
}

function saveNotificationSettings(event) {
    event.preventDefault();
    // Implementation for saving notification settings
    alert('Notification settings saved successfully!');
}

function saveBrandingSettings(event) {
    event.preventDefault();
    // Implementation for saving branding settings
    alert('Branding settings saved successfully!');
}

function savePreferencesSettings(event) {
    event.preventDefault();
    // Implementation for saving preferences
    alert('Preferences saved successfully!');
}

function openMediaUploader() {
    // Implementation for WordPress media uploader
    alert('Media uploader would be implemented here');
}
</script>
