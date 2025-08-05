<?php
/**
 * Import required component classes
 */
use HappyPlace\Components\Listing\Hero;
use HappyPlace\Components\Listing\Gallery;
use HappyPlace\Components\Listing\Card;
use HappyPlace\Components\Agent\Card as AgentCard;
use HappyPlace\Components\Agent\Profile;
use HappyPlace\Components\Tools\Mortgage_Calculator;
use HappyPlace\Components\UI\Button;
use HappyPlace\Components\UI\Modal;

// Safety check for component availability
if (!class_exists('HappyPlace\Components\Listing\Hero')) {
    // Load theme manager to ensure components are available
    if (class_exists('HappyPlace\Core\Theme_Manager')) {
        HappyPlace\Core\Theme_Manager::get_instance();
    }
}

/**
 * Template Name: Agent Dashboard (Rebuilt)
 * 
 * Main template for the agent dashboard with all sections
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Production Authentication - REQUIRED for dashboard access
if (!is_user_logged_in()) {
    wp_redirect(add_query_arg('redirect_to', urlencode(get_permalink()), wp_login_url()));
    exit;
}

// Verify user capabilities - Agents need edit_posts or manage_options capability
if (!current_user_can('edit_posts') && !current_user_can('manage_options')) {
    wp_die(
        __('You do not have permission to access the agent dashboard. Please contact your administrator if you believe this is an error.', 'happy-place'),
        __('Access Denied', 'happy-place'),
        array('response' => 403)
    );
}

// Get authenticated user data
$current_user = wp_get_current_user();
$current_agent_id = $current_user->ID;

// Verify nonce for any POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wp_verify_nonce($_POST['hph_dashboard_nonce'] ?? '', 'hph_dashboard_action')) {
        wp_die(__('Security verification failed. Please refresh the page and try again.', 'happy-place'));
    }
}

// Calculate additional stats for sidebar and passing to templates
$recent_listings = get_posts([
    'author' => $current_agent_id,
    'post_type' => 'listing',
    'post_status' => 'publish',
    'numberposts' => 5,
    'meta_key' => '_listing_date',
    'orderby' => 'meta_value',
    'order' => 'DESC'
]);

$pending_inquiries = function_exists('hph_get_agent_inquiries') ? 
    hph_get_agent_inquiries($current_agent_id, 'pending') : [];

$upcoming_open_houses = function_exists('hph_get_agent_open_houses') ? 
    hph_get_agent_open_houses($current_agent_id, 'upcoming') : [];

// Stats for passing to template sections
$stats = [
    'active_listings' => count($recent_listings),
    'pending_inquiries' => count($pending_inquiries),
    'upcoming_open_houses' => count($upcoming_open_houses),
    'total_views' => get_user_meta($current_agent_id, '_total_listing_views', true) ?: 0,
    'leads_this_month' => get_user_meta($current_agent_id, '_leads_this_month', true) ?: 0,
];

// Get current section from URL or default to overview
$current_section = get_query_var('section') ?: (isset($_GET['section']) ? sanitize_text_field($_GET['section']) : 'overview');

// Available dashboard sections
$dashboard_sections = [
    'overview' => [
        'title' => __('Overview', 'happy-place'),
        'icon' => 'fas fa-tachometer-alt',
        'template' => 'overview'
    ],
    'listings' => [
        'title' => __('My Listings', 'happy-place'),
        'icon' => 'fas fa-home',
        'template' => 'listings'
    ],
    'open-houses' => [
        'title' => __('Open Houses', 'happy-place'),
        'icon' => 'fas fa-calendar-alt',
        'template' => 'open-houses'
    ],
    'marketing' => [
        'title' => __('Marketing', 'happy-place'),
        'icon' => 'fas fa-palette',
        'template' => 'marketing'
    ],
    'analytics' => [
        'title' => __('Analytics', 'happy-place'),
        'icon' => 'fas fa-chart-line',
        'template' => 'analytics'
    ]
];

// Validate current section
if (!array_key_exists($current_section, $dashboard_sections)) {
    $current_section = 'overview';
}

get_header(); ?>

<!-- Security Nonce for Dashboard Actions -->
<?php wp_nonce_field('hph_dashboard_action', 'hph_dashboard_nonce'); ?>

<div class="hph-dashboard-wrapper" id="hph-dashboard">
    
    <!-- Dashboard Sidebar -->
    <div class="hph-dashboard-sidebar">
        
        <!-- User Profile Section -->
        <div class="hph-dashboard-user">
            <div class="hph-dashboard-avatar">
                <?php echo get_avatar($current_user->ID, 64, '', esc_attr($current_user->display_name)); ?>
            </div>
            <div class="hph-dashboard-user-info">
                <h3><?php echo esc_html($current_user->display_name); ?></h3>
                <p><?php esc_html_e('Real Estate Agent', 'happy-place'); ?></p>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="hph-dashboard-nav">
            <?php foreach ($dashboard_sections as $section_key => $section) : ?>
                <a href="<?php echo esc_url(add_query_arg('section', $section_key)); ?>" 
                   class="hph-dashboard-nav-item <?php echo $current_section === $section_key ? 'hph-dashboard-nav-item--active' : ''; ?>"
                   data-section="<?php echo esc_attr($section_key); ?>">
                    <i class="<?php echo esc_attr($section['icon']); ?>"></i>
                    <span><?php echo esc_html($section['title']); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Sidebar Footer -->
        <div class="hph-dashboard-sidebar-footer">
            <div class="hph-quick-stats">
                <div class="hph-quick-stat">
                    <div class="hph-quick-stat-value"><?php echo count($recent_listings); ?></div>
                    <div class="hph-quick-stat-label"><?php esc_html_e('Active', 'happy-place'); ?></div>
                </div>
                <div class="hph-quick-stat">
                    <div class="hph-quick-stat-value"><?php echo count($pending_inquiries); ?></div>
                    <div class="hph-quick-stat-label"><?php esc_html_e('Inquiries', 'happy-place'); ?></div>
                </div>
            </div>
        </div>
        
    </div>

    <!-- Dashboard Main Content -->
    <div class="hph-dashboard-main">
        
        <!-- Dashboard Header -->
        <div class="hph-dashboard-header">
            <div class="hph-dashboard-title-group">
                <h1 class="hph-dashboard-title">
                    <?php echo esc_html($dashboard_sections[$current_section]['title']); ?>
                </h1>
                <p class="hph-dashboard-subtitle">
                    <?php 
                    printf(
                        /* translators: %s: agent name */
                        esc_html__('Welcome back, %s', 'happy-place'),
                        esc_html($current_user->display_name)
                    ); 
                    ?>
                </p>
            </div>
            
            <div class="hph-dashboard-actions">
                <button type="button" class="action-btn action-btn--secondary action-btn--sm" onclick="HphDashboard.showSettingsModal()">
                    <i class="fas fa-user-cog"></i>
                    <?php esc_html_e('Settings', 'happy-place'); ?>
                </button>
                <button type="button" class="action-btn action-btn--outline action-btn--sm" onclick="HphDashboard.confirmLogout()">
                    <i class="fas fa-sign-out-alt"></i>
                    <?php esc_html_e('Logout', 'happy-place'); ?>
                </button>
            </div>
        </div>

        <!-- Section Content -->
        <div class="hph-dashboard-content">
            <?php
            // Load the appropriate template part
            $template_file = $dashboard_sections[$current_section]['template'];
            $template_path = get_template_directory() . '/template-parts/dashboard/' . $template_file . '.php';
            
            if (file_exists($template_path)) {
                // Pass variables to template
                include $template_path;
            } else {
                // Fallback content
                echo '<div class="hph-empty-state">';
                echo '<div class="hph-empty-state-icon"><i class="fas fa-exclamation-triangle"></i></div>';
                echo '<h3 class="hph-empty-state-title">' . esc_html__('Section Not Available', 'happy-place') . '</h3>';
                echo '<p class="hph-empty-state-description">' . sprintf(
                    /* translators: %s: section name */
                    esc_html__('The %s section is currently under development.', 'happy-place'),
                    esc_html($dashboard_sections[$current_section]['title'])
                ) . '</p>';
                echo '</div>';
            }
            ?>
        </div>
        
    </div>

</div>

<!-- Dashboard Notifications Container -->
<div id="dashboard-notifications" class="hph-dashboard-notifications"></div>

<!-- Loading Overlay -->
<div id="dashboard-loading" class="hph-dashboard-loading hph-dashboard-loading--hidden">
    <div class="hph-dashboard-loading-spinner">
        <i class="fas fa-spinner fa-spin"></i>
        <span><?php esc_html_e('Loading...', 'happy-place'); ?></span>
    </div>
</div>

<!-- Settings Modal -->
<div id="hph-settings-modal" class="hph-dashboard-modal hph-dashboard-modal--hidden">
    <div class="hph-dashboard-modal-content">
        <div class="hph-dashboard-modal-header">
            <h3><?php esc_html_e('Agent Settings', 'happy-place'); ?></h3>
            <button type="button" class="hph-dashboard-modal-close" onclick="HphDashboard.closeModal('settings')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="hph-dashboard-modal-body">
            <form id="hph-agent-settings-form" class="hph-settings-form">
                
                <!-- Profile Information -->
                <div class="hph-settings-section">
                    <h4 class="hph-settings-section-title">
                        <i class="fas fa-user"></i>
                        <?php esc_html_e('Profile Information', 'happy-place'); ?>
                    </h4>
                    
                    <div class="hph-form-grid">
                        <div class="hph-form-group">
                            <label for="agent-display-name" class="hph-form-label">
                                <?php esc_html_e('Display Name', 'happy-place'); ?>
                            </label>
                            <input type="text" id="agent-display-name" name="display_name" 
                                   class="hph-form-input" 
                                   value="<?php echo esc_attr($current_user->display_name); ?>">
                        </div>
                        
                        <div class="hph-form-group">
                            <label for="agent-email" class="hph-form-label">
                                <?php esc_html_e('Email Address', 'happy-place'); ?>
                            </label>
                            <input type="email" id="agent-email" name="user_email" 
                                   class="hph-form-input" 
                                   value="<?php echo esc_attr($current_user->user_email); ?>">
                        </div>
                        
                        <div class="hph-form-group">
                            <label for="agent-phone" class="hph-form-label">
                                <?php esc_html_e('Phone Number', 'happy-place'); ?>
                            </label>
                            <input type="tel" id="agent-phone" name="agent_phone" 
                                   class="hph-form-input" 
                                   value="<?php echo esc_attr(get_user_meta($current_agent_id, 'agent_phone', true)); ?>">
                        </div>
                        
                        <div class="hph-form-group hph-form-group--full">
                            <label for="agent-bio" class="hph-form-label">
                                <?php esc_html_e('Bio/Description', 'happy-place'); ?>
                            </label>
                            <textarea id="agent-bio" name="description" 
                                      class="hph-form-textarea" rows="4"
                                      placeholder="<?php esc_attr_e('Tell clients about yourself...', 'happy-place'); ?>"><?php echo esc_textarea($current_user->description); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Dashboard Preferences -->
                <div class="hph-settings-section">
                    <h4 class="hph-settings-section-title">
                        <i class="fas fa-cog"></i>
                        <?php esc_html_e('Dashboard Preferences', 'happy-place'); ?>
                    </h4>
                    
                    <div class="hph-form-grid">
                        <div class="hph-form-group">
                            <label for="default-section" class="hph-form-label">
                                <?php esc_html_e('Default Dashboard Section', 'happy-place'); ?>
                            </label>
                            <select id="default-section" name="default_dashboard_section" class="hph-form-select">
                                <?php foreach ($dashboard_sections as $section_key => $section): ?>
                                    <option value="<?php echo esc_attr($section_key); ?>" 
                                            <?php selected(get_user_meta($current_agent_id, 'default_dashboard_section', true), $section_key); ?>>
                                        <?php echo esc_html($section['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="hph-form-group">
                            <label class="hph-form-checkbox">
                                <input type="checkbox" name="email_notifications" 
                                       <?php checked(get_user_meta($current_agent_id, 'email_notifications', true), '1'); ?>>
                                <span class="hph-form-checkbox-text">
                                    <?php esc_html_e('Email Notifications', 'happy-place'); ?>
                                </span>
                            </label>
                        </div>
                        
                        <div class="hph-form-group">
                            <label class="hph-form-checkbox">
                                <input type="checkbox" name="dashboard_tips" 
                                       <?php checked(get_user_meta($current_agent_id, 'dashboard_tips', true), '1'); ?>>
                                <span class="hph-form-checkbox-text">
                                    <?php esc_html_e('Show Dashboard Tips', 'happy-place'); ?>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Password Change -->
                <div class="hph-settings-section">
                    <h4 class="hph-settings-section-title">
                        <i class="fas fa-lock"></i>
                        <?php esc_html_e('Change Password', 'happy-place'); ?>
                    </h4>
                    
                    <div class="hph-form-grid">
                        <div class="hph-form-group">
                            <label for="current-password" class="hph-form-label">
                                <?php esc_html_e('Current Password', 'happy-place'); ?>
                            </label>
                            <input type="password" id="current-password" name="current_password" class="hph-form-input">
                        </div>
                        
                        <div class="hph-form-group">
                            <label for="new-password" class="hph-form-label">
                                <?php esc_html_e('New Password', 'happy-place'); ?>
                            </label>
                            <input type="password" id="new-password" name="new_password" class="hph-form-input">
                        </div>
                        
                        <div class="hph-form-group">
                            <label for="confirm-password" class="hph-form-label">
                                <?php esc_html_e('Confirm New Password', 'happy-place'); ?>
                            </label>
                            <input type="password" id="confirm-password" name="confirm_password" class="hph-form-input">
                        </div>
                    </div>
                </div>
                
            </form>
        </div>
        
        <div class="hph-dashboard-modal-footer">
            <button type="button" class="action-btn action-btn--outline" onclick="HphDashboard.closeModal('settings')">
                <?php esc_html_e('Cancel', 'happy-place'); ?>
            </button>
            <button type="button" class="action-btn action-btn--primary" onclick="HphDashboard.saveSettings()">
                <i class="fas fa-save"></i>
                <?php esc_html_e('Save Settings', 'happy-place'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Logout Confirmation Modal -->
<div id="hph-logout-modal" class="hph-dashboard-modal hph-dashboard-modal--hidden">
    <div class="hph-dashboard-modal-content hph-dashboard-modal-content--small">
        <div class="hph-dashboard-modal-header">
            <h3><?php esc_html_e('Confirm Logout', 'happy-place'); ?></h3>
            <button type="button" class="hph-dashboard-modal-close" onclick="HphDashboard.closeModal('logout')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="hph-dashboard-modal-body">
            <div class="hph-confirmation-content">
                <div class="hph-confirmation-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <p><?php esc_html_e('Are you sure you want to logout of your dashboard?', 'happy-place'); ?></p>
            </div>
        </div>
        
        <div class="hph-dashboard-modal-footer">
            <button type="button" class="action-btn action-btn--outline" onclick="HphDashboard.closeModal('logout')">
                <?php esc_html_e('Cancel', 'happy-place'); ?>
            </button>
            <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="action-btn action-btn--danger">
                <i class="fas fa-sign-out-alt"></i>
                <?php esc_html_e('Logout', 'happy-place'); ?>
            </a>
        </div>
    </div>
</div>

<!-- Automated Flyer Generation Modal -->
<div id="hph-flyer-generation-modal" class="hph-dashboard-modal hph-dashboard-modal--hidden">
    <div class="hph-dashboard-modal-content">
        <div class="hph-dashboard-modal-header">
            <h3 id="flyer-modal-title"><?php esc_html_e('Generate Marketing Materials', 'happy-place'); ?></h3>
            <button type="button" class="modal-close" onclick="HphDashboard.closeFlyerModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="hph-dashboard-modal-body">
            <div class="flyer-generation-options">
                <div class="flyer-option-group">
                    <h4><?php esc_html_e('Available Marketing Materials', 'happy-place'); ?></h4>
                    <p class="option-description" id="flyer-modal-description">
                        <?php esc_html_e('Generate professional marketing materials for your listing', 'happy-place'); ?>
                    </p>
                </div>
                
                <div class="flyer-actions-grid">
                    <div class="flyer-action-card" id="standard-flyer-card">
                        <div class="flyer-action-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="flyer-action-content">
                            <h5><?php esc_html_e('Standard Listing Flyer', 'happy-place'); ?></h5>
                            <p><?php esc_html_e('Professional listing flyer with property details and listing agent information', 'happy-place'); ?></p>
                            <button type="button" class="action-btn action-btn--primary" onclick="HphDashboard.generateAutomatedFlyer('listing')">
                                <i class="fas fa-magic"></i>
                                <?php esc_html_e('Generate Flyer', 'happy-place'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flyer-action-card" id="open-house-flyer-card" style="display: none;">
                        <div class="flyer-action-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="flyer-action-content">
                            <h5><?php esc_html_e('Open House Flyer', 'happy-place'); ?></h5>
                            <p><?php esc_html_e('Open house flyer with hosting agent information and event details', 'happy-place'); ?></p>
                            <button type="button" class="action-btn action-btn--primary" onclick="HphDashboard.generateAutomatedFlyer('open_house')">
                                <i class="fas fa-calendar-alt"></i>
                                <?php esc_html_e('Generate Open House Flyer', 'happy-place'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flyer-action-card">
                        <div class="flyer-action-icon">
                            <i class="fas fa-share-alt"></i>
                        </div>
                        <div class="flyer-action-content">
                            <h5><?php esc_html_e('Social Media Graphics', 'happy-place'); ?></h5>
                            <p><?php esc_html_e('Social media-ready graphics for Facebook, Instagram, and other platforms', 'happy-place'); ?></p>
                            <button type="button" class="action-btn action-btn--outline" onclick="HphDashboard.generateAutomatedSocial()">
                                <i class="fas fa-hashtag"></i>
                                <?php esc_html_e('Generate Social', 'happy-place'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="flyer-generation-settings">
                    <h5><?php esc_html_e('Automation Settings', 'happy-place'); ?></h5>
                    <div class="settings-grid">
                        <label class="setting-checkbox">
                            <input type="checkbox" id="auto-generate-on-publish" checked>
                            <span class="checkmark"></span>
                            <?php esc_html_e('Auto-generate flyers when publishing listings', 'happy-place'); ?>
                        </label>
                        <label class="setting-checkbox">
                            <input type="checkbox" id="auto-generate-open-house" checked>
                            <span class="checkmark"></span>
                            <?php esc_html_e('Auto-generate open house flyers when scheduling events', 'happy-place'); ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="hph-dashboard-modal-footer">
            <button type="button" class="action-btn action-btn--outline" onclick="HphDashboard.closeFlyerModal()">
                <?php esc_html_e('Maybe Later', 'happy-place'); ?>
            </button>
            <button type="button" class="action-btn action-btn--primary" onclick="HphDashboard.openMarketingTab()">
                <i class="fas fa-tools"></i>
                <?php esc_html_e('Go to Marketing Tools', 'happy-place'); ?>
            </button>
        </div>
    </div>
</div>

<script>
// Initialize Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Dashboard object for global functionality
    window.HphDashboard = {
        currentSection: '<?php echo esc_js($current_section); ?>',
        userId: <?php echo intval($current_agent_id); ?>,
        ajaxUrl: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
        nonce: '<?php echo wp_create_nonce('hph_dashboard_nonce'); ?>',
        
        // Show loading overlay
        showLoading: function() {
            document.getElementById('dashboard-loading').classList.remove('hph-dashboard-loading--hidden');
        },
        
        // Hide loading overlay
        hideLoading: function() {
            document.getElementById('dashboard-loading').classList.add('hph-dashboard-loading--hidden');
        },
        
        // Show toast notification
        showToast: function(message, type = 'info') {
            const container = document.getElementById('dashboard-notifications');
            const toast = document.createElement('div');
            toast.className = `hph-dashboard-toast hph-dashboard-toast--${type}`;
            toast.innerHTML = `
                <div class="hph-dashboard-toast-content">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                    <span>${message}</span>
                </div>
                <button class="hph-dashboard-toast-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            container.appendChild(toast);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 5000);
        },
        
        // Navigate to section
        navigateToSection: function(section) {
            const url = new URL(window.location);
            url.searchParams.set('section', section);
            window.location.href = url.toString();
        },
        
        // Show settings modal
        showSettingsModal: function() {
            document.getElementById('hph-settings-modal').classList.remove('hph-dashboard-modal--hidden');
        },
        
        // Show logout confirmation
        confirmLogout: function() {
            document.getElementById('hph-logout-modal').classList.remove('hph-dashboard-modal--hidden');
        },
        
        // Close modal
        closeModal: function(modalType) {
            document.getElementById(`hph-${modalType}-modal`).classList.add('hph-dashboard-modal--hidden');
        },
        
        // Save agent settings
        saveSettings: function() {
            const form = document.getElementById('hph-agent-settings-form');
            const formData = new FormData(form);
            formData.append('action', 'hph_save_agent_settings');
            formData.append('nonce', this.nonce);
            
            this.showLoading();
            
            fetch(this.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                this.hideLoading();
                if (data.success) {
                    this.showToast('Settings saved successfully!', 'success');
                    this.closeModal('settings');
                } else {
                    this.showToast(data.data || 'Failed to save settings.', 'error');
                }
            })
            .catch(() => {
                this.hideLoading();
                this.showToast('An error occurred while saving settings.', 'error');
                // Error logged for debugging if needed
            });
        },
        
        // Show listing form modal instead of wp-admin
        showListingForm: function(listingId = null) {
            // Load listing form content
            this.loadListingForm(listingId);
        },
        
        // Load listing form modal
        loadListingForm: function(listingId = null) {
            this.showLoading();
            
            const formData = new FormData();
            formData.append('action', 'hph_load_listing_form');
            formData.append('nonce', this.nonce);
            if (listingId) {
                formData.append('listing_id', listingId);
            }
            
            fetch(this.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                this.hideLoading();
                
                // Create modal if it doesn't exist
                let modal = document.getElementById('hph-listing-form-modal');
                if (!modal) {
                    modal = document.createElement('div');
                    modal.innerHTML = html;
                    document.body.appendChild(modal.firstElementChild);
                } else {
                    modal.innerHTML = html;
                }
                
                // Initialize listing form
                if (typeof HphListingForm !== 'undefined') {
                    HphListingForm.init();
                }
                
                // Show modal
                document.getElementById('hph-listing-form-modal').classList.remove('hph-dashboard-modal--hidden');
            })
            .catch(() => {
                this.hideLoading();
                this.showToast('Failed to load listing form.', 'error');
                // Error logged for debugging if needed
            });
        },
        
        // Automated flyer generation functions
        showFlyerGenerationModal: function(context = 'listing', listingId = null) {
            // Check user preferences before showing modal
            if (context === 'listing') {
                const autoGenerateOnPublish = document.getElementById('auto-generate-on-publish');
                if (autoGenerateOnPublish && !autoGenerateOnPublish.checked) {
                    return; // Don't show modal if user has disabled auto-generation for listings
                }
            } else if (context === 'open_house') {
                const autoGenerateOpenHouse = document.getElementById('auto-generate-open-house');
                if (autoGenerateOpenHouse && !autoGenerateOpenHouse.checked) {
                    return; // Don't show modal if user has disabled auto-generation for open houses
                }
            }
            
            this.currentFlyerContext = context;
            this.currentFlyerListingId = listingId || this.currentListing;
            
            // Update modal content based on context
            const modal = document.getElementById('hph-flyer-generation-modal');
            const title = document.getElementById('flyer-modal-title');
            const description = document.getElementById('flyer-modal-description');
            const openHouseCard = document.getElementById('open-house-flyer-card');
            
            if (context === 'open_house') {
                title.textContent = 'Generate Open House Marketing Materials';
                description.textContent = 'Create professional marketing materials for your open house event';
                openHouseCard.style.display = 'block';
            } else {
                title.textContent = 'Generate Marketing Materials';
                description.textContent = 'Generate professional marketing materials for your listing';
                openHouseCard.style.display = 'none';
            }
            
            modal.classList.remove('hph-dashboard-modal--hidden');
        },
        
        closeFlyerModal: function() {
            document.getElementById('hph-flyer-generation-modal').classList.add('hph-dashboard-modal--hidden');
        },
        
        generateAutomatedFlyer: function(flyerType = 'listing') {
            if (!this.currentFlyerListingId) {
                this.showToast('No listing selected for flyer generation', 'error');
                return;
            }
            
            this.showLoading();
            this.closeFlyerModal();
            
            // Use the existing flyer generator with automated parameters
            const formData = new FormData();
            formData.append('action', 'generate_flyer');
            formData.append('listing_id', this.currentFlyerListingId);
            formData.append('flyer_type', flyerType);
            formData.append('nonce', wp_create_nonce('flyer_generator_nonce'));
            
            fetch(this.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                this.hideLoading();
                if (data.success) {
                    // Open flyer generator with the data pre-loaded
                    this.openFlyerGenerator(flyerType, this.currentFlyerListingId);
                    this.showToast('Flyer data prepared! Complete generation in Marketing Tools.', 'success');
                } else {
                    this.showToast('Failed to prepare flyer data: ' + (data.data || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                this.hideLoading();
                this.showToast('Error preparing flyer: ' + error.message, 'error');
                console.error('Flyer generation error:', error);
            });
        },
        
        generateAutomatedSocial: function() {
            if (!this.currentFlyerListingId) {
                this.showToast('No listing selected for social media generation', 'error');
                return;
            }
            
            this.closeFlyerModal();
            // Generate social media graphics using existing functionality
            this.generateMarketing('social_media', { 
                platform: 'facebook', 
                type: this.currentFlyerContext === 'open_house' ? 'open_house' : 'listing' 
            });
        },
        
        openFlyerGenerator: function(flyerType = 'listing', listingId = null) {
            // Switch to marketing tab and set up flyer generator
            this.switchToTab('marketing');
            
            // Wait for tab to load, then set up flyer generator
            setTimeout(() => {
                const listingSelect = document.getElementById('listing-select');
                const flyerTypeSelect = document.getElementById('flyer-type-select');
                
                if (listingSelect && listingId) {
                    listingSelect.value = listingId;
                }
                
                if (flyerTypeSelect) {
                    flyerTypeSelect.value = flyerType;
                }
                
                // Scroll to flyer generator
                const flyerSection = document.querySelector('.flyer-generator');
                if (flyerSection) {
                    flyerSection.scrollIntoView({ behavior: 'smooth' });
                }
            }, 500);
        },
        
        openMarketingTab: function() {
            this.closeFlyerModal();
            this.switchToTab('marketing');
        },
        
        switchToTab: function(tabName) {
            // Update URL and navigate to tab
            const url = new URL(window.location);
            url.searchParams.set('section', tabName);
            window.location.href = url.toString();
        },
        
        // Settings management functions
        saveAutomationSettings: function() {
            const settings = {
                auto_generate_on_publish: document.getElementById('auto-generate-on-publish')?.checked || false,
                auto_generate_open_house: document.getElementById('auto-generate-open-house')?.checked || false
            };
            
            // Save to user meta via AJAX
            const formData = new FormData();
            formData.append('action', 'save_automation_settings');
            formData.append('settings', JSON.stringify(settings));
            formData.append('nonce', wp_create_nonce('automation_settings_nonce'));
            
            fetch(this.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showToast('Automation settings saved', 'success');
                }
            })
            .catch(error => {
                console.error('Error saving automation settings:', error);
            });
        },
        
        loadAutomationSettings: function() {
            // Load settings from user meta via AJAX
            const formData = new FormData();
            formData.append('action', 'load_automation_settings');
            formData.append('nonce', wp_create_nonce('automation_settings_nonce'));
            
            fetch(this.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.settings) {
                    const settings = data.data.settings;
                    
                    // Update checkboxes based on saved settings
                    const autoPublishCheckbox = document.getElementById('auto-generate-on-publish');
                    const autoOpenHouseCheckbox = document.getElementById('auto-generate-open-house');
                    
                    if (autoPublishCheckbox) {
                        autoPublishCheckbox.checked = settings.auto_generate_on_publish !== false;
                    }
                    if (autoOpenHouseCheckbox) {
                        autoOpenHouseCheckbox.checked = settings.auto_generate_open_house !== false;
                    }
                }
            })
            .catch(error => {
                console.error('Error loading automation settings:', error);
            });
        }
    };
    
    // Section navigation handling
    document.querySelectorAll('.hph-dashboard-nav-item').forEach(link => {
        link.addEventListener('click', function(e) {
            const section = this.dataset.section;
            if (section && section !== HphDashboard.currentSection) {
                HphDashboard.showLoading();
                // Let the default navigation happen
            }
        });
    });
    
    // Ensure dashboard is not stuck in loading state
    HphDashboard.hideLoading();
    
    // Remove any loading classes from dashboard wrapper
    const dashboardWrapper = document.getElementById('hph-dashboard');
    if (dashboardWrapper) {
        dashboardWrapper.classList.remove('is-loading');
    }
    
    // Show success toast for successful initialization
    setTimeout(function() {
        HphDashboard.showToast('Dashboard loaded successfully', 'success');
    }, 500);
    
    // Load automation settings
    HphDashboard.loadAutomationSettings();
    
    // Add event listeners for automation settings
    const autoPublishCheckbox = document.getElementById('auto-generate-on-publish');
    const autoOpenHouseCheckbox = document.getElementById('auto-generate-open-house');
    
    if (autoPublishCheckbox) {
        autoPublishCheckbox.addEventListener('change', function() {
            HphDashboard.saveAutomationSettings();
        });
    }
    
    if (autoOpenHouseCheckbox) {
        autoOpenHouseCheckbox.addEventListener('change', function() {
            HphDashboard.saveAutomationSettings();
        });
    }
    
    // Dashboard initialization complete
    console.log('HPH Dashboard JavaScript initialized successfully');
    console.log('Current section:', HphDashboard.currentSection);
    console.log('User ID:', HphDashboard.userId);
}); // End of DOMContentLoaded

// Initialize Listing Form JavaScript (separate from DOMContentLoaded)
window.HphListingForm = {
    currentListing: null,
    currentTab: 'basic',
    
    // Initialize the form
    init: function() {
        this.bindEvents();
        this.initTabs();
        this.initMediaUpload();
    },
    
    // Bind form events
    bindEvents: function() {
        // Tab navigation
        document.querySelectorAll('.hph-form-tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.switchTab(btn.dataset.tab);
            });
        });
        
        // Form validation on input
        document.querySelectorAll('.hph-form-input, .hph-form-select, .hph-form-textarea').forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
        });
    },
    
    // Initialize tabs
    initTabs: function() {
        this.switchTab('basic');
    },
    
    // Switch between tabs
    switchTab: function(tabName) {
        // Update tab buttons
        document.querySelectorAll('.hph-form-tab-btn').forEach(btn => {
            btn.classList.remove('hph-form-tab-btn--active');
        });
        document.querySelector(`[data-tab="${tabName}"]`).classList.add('hph-form-tab-btn--active');
        
        // Update tab content
        document.querySelectorAll('.hph-form-tab-content').forEach(content => {
            content.classList.remove('hph-form-tab-content--active');
        });
        document.getElementById(`tab-${tabName}`).classList.add('hph-form-tab-content--active');
        
        this.currentTab = tabName;
    },
    
    // Initialize media upload
    initMediaUpload: function() {
        document.querySelectorAll('.hph-media-input').forEach(input => {
            input.addEventListener('change', (e) => this.handleFileUpload(e));
        });
    },
    
    // Handle file upload preview
    handleFileUpload: function(e) {
        const input = e.target;
        const files = input.files;
        const uploadArea = input.closest('.hph-media-upload');
        const type = uploadArea.querySelector('.hph-media-upload-area').dataset.type;
        
        if (type === 'featured' && files.length > 0) {
            this.previewFeaturedImage(files[0], uploadArea);
        } else if (type === 'gallery' && files.length > 0) {
            this.previewGalleryImages(files, uploadArea);
        }
    },
    
    // Preview featured image
    previewFeaturedImage: function(file, uploadArea) {
        const preview = uploadArea.querySelector('.hph-media-preview');
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Featured Image">`;
            preview.classList.add('has-media');
        };
        
        reader.readAsDataURL(file);
    },
    
    // Preview gallery images
    previewGalleryImages: function(files, uploadArea) {
        const gallery = uploadArea.querySelector('.hph-media-gallery');
        
        Array.from(files).forEach((file, index) => {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const item = document.createElement('div');
                item.className = 'hph-media-gallery-item';
                item.innerHTML = `
                    <img src="${e.target.result}" alt="Gallery Image ${index + 1}">
                    <button type="button" class="remove-btn" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                gallery.appendChild(item);
            };
            
            reader.readAsDataURL(file);
        });
        
        gallery.classList.add('has-media');
    },
    
    // Validate form field
    validateField: function(field) {
        const value = field.value.trim();
        const isRequired = field.hasAttribute('required');
        let isValid = true;
        let errorMessage = '';
        
        // Remove existing error state
        field.classList.remove('has-error');
        const existingError = field.parentElement.querySelector('.hph-form-error-message');
        if (existingError) {
            existingError.remove();
        }
        
        // Required field validation
        if (isRequired && !value) {
            isValid = false;
            errorMessage = 'This field is required';
        }
        
        // Email validation
        if (field.type === 'email' && value && !this.isValidEmail(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid email address';
        }
        
        // URL validation
        if (field.type === 'url' && value && !this.isValidUrl(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid URL';
        }
        
        // Number validation
        if (field.type === 'number' && value) {
            const min = field.getAttribute('min');
            const max = field.getAttribute('max');
            const numValue = parseFloat(value);
            
            if (min && numValue < parseFloat(min)) {
                isValid = false;
                errorMessage = `Value must be at least ${min}`;
            } else if (max && numValue > parseFloat(max)) {
                isValid = false;
                errorMessage = `Value must be no more than ${max}`;
            }
        }
        
        // Show error if invalid
        if (!isValid) {
            field.classList.add('has-error');
            const errorDiv = document.createElement('div');
            errorDiv.className = 'hph-form-error-message';
            errorDiv.textContent = errorMessage;
            field.parentElement.appendChild(errorDiv);
        }
        
        return isValid;
    },
    
    // Email validation helper
    isValidEmail: function(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    },
    
    // URL validation helper
    isValidUrl: function(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    },
    
    // Validate entire form
    validateForm: function() {
        const form = document.getElementById('hph-listing-form');
        const inputs = form.querySelectorAll('.hph-form-input, .hph-form-select, .hph-form-textarea');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });
        
        return isValid;
    },
    
    // Save as draft
    saveDraft: function() {
        this.saveForm('draft');
    },
    
    // Save and publish
    saveAndPublish: function() {
        if (!this.validateForm()) {
            HphDashboard.showToast('Please fix the errors before publishing', 'error');
            return;
        }
        
        this.saveForm('publish');
    },
    
    // Save form data
    saveForm: function(status) {
        const form = document.getElementById('hph-listing-form');
        const formData = new FormData(form);
        formData.append('post_status', status);
        
        HphDashboard.showLoading();
        
        fetch(HphDashboard.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            HphDashboard.hideLoading();
            
            if (data.success) {
                HphDashboard.showToast(data.data.message, 'success');
                this.currentListing = data.data.listing_id;
                
                // Enable marketing tools if this was a new listing
                this.enableMarketingTools();
                
                // Close modal after short delay if publishing
                if (status === 'publish') {
                    setTimeout(() => {
                        this.closeModal();
                        
                        // Show automated flyer generation options after successful publish
                        setTimeout(() => {
                            this.showFlyerGenerationModal();
                        }, 500);
                        
                        // Refresh the current section to show updated listings
                        setTimeout(() => {
                            window.location.reload();
                        }, 3000);
                    }, 2000);
                }
            } else {
                HphDashboard.showToast(data.data || 'Failed to save listing', 'error');
            }
        })
        .catch(error => {
            HphDashboard.hideLoading();
            HphDashboard.showToast('An error occurred while saving', 'error');
            console.error('Save listing error:', error);
        });
    },
    
    // Enable marketing tools
    enableMarketingTools: function() {
        const marketingButtons = document.querySelectorAll('#tab-marketing button[disabled]');
        marketingButtons.forEach(btn => {
            btn.disabled = false;
        });
    },
    
    // Close modal
    closeModal: function() {
        document.getElementById('hph-listing-form-modal').classList.add('hph-dashboard-modal--hidden');
    },
    
    // Generate flyer
    generateFlyer: function() {
        if (!this.currentListing) {
            HphDashboard.showToast('Please save the listing first', 'info');
            return;
        }
        
        this.generateMarketing('flyer');
    },
    
    // Generate social media graphics
    generateSocialMedia: function() {
        if (!this.currentListing) {
            HphDashboard.showToast('Please save the listing first', 'info');
            return;
        }
        
        this.generateMarketing('social_media', { platform: 'facebook', type: 'listing' });
    },
    
    // Generate open house graphics
    generateOpenHouse: function() {
        if (!this.currentListing) {
            HphDashboard.showToast('Please save the listing first', 'info');
            return;
        }
        
        this.generateMarketing('social_media', { platform: 'facebook', type: 'open_house' });
    },
    
    // Generate open house flyer
    generateOpenHouseFlyer: function() {
        if (!this.currentListing) {
            HphDashboard.showToast('Please save the listing first', 'info');
            return;
        }
        
        this.generateMarketing('flyer', { flyer_type: 'open_house' });
    },
    
    // Generate marketing materials
    generateMarketing: function(type, options = {}) {
        const formData = new FormData();
        formData.append('action', 'hph_generate_listing_marketing');
        formData.append('nonce', HphDashboard.nonce);
        formData.append('listing_id', this.currentListing);
        formData.append('marketing_type', type);
        
        // Add options
        Object.keys(options).forEach(key => {
            formData.append(key, options[key]);
        });
        
        HphDashboard.showLoading();
        
        fetch(HphDashboard.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            HphDashboard.hideLoading();
            
            if (data.success) {
                HphDashboard.showToast('Marketing material generated successfully!', 'success');
                // Could open the generated file or show preview
                if (data.data.flyer_url || data.data.graphic_url) {
                    const url = data.data.flyer_url || data.data.graphic_url;
                    window.open(url, '_blank');
                }
            } else {
                HphDashboard.showToast(data.data || 'Failed to generate marketing material', 'error');
            }
        })
        .catch(() => {
            HphDashboard.hideLoading();
            HphDashboard.showToast('An error occurred while generating marketing material', 'error');
            // Error logged for debugging if needed
        });
    },
    
    // Focus virtual tour field
    focusVirtualTour: function() {
        this.switchTab('features');
        setTimeout(() => {
            document.getElementById('virtual-tour-url').focus();
        }, 100);
    }
}; // End of HphListingForm

</script>

<?php get_footer(); ?>
