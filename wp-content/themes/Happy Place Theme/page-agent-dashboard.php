<?php
/**
 * Template Name: Agent Dashboard
 * 
 * The template for displaying the agent dashboard page.
 *
 * @package Happy_Place_Theme
 */

// Redirect to login if not logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

// Check if user has dashboard access
if (!hph_user_can_access_dashboard()) {
    wp_die(__('You do not have permission to access this page.', 'happy-place'));
}

get_header();

// Get current dashboard section
$current_section = hph_get_dashboard_section();
?>

<div class="dashboard-wrapper">
    <div class="dashboard-container">
        
        <!-- Dashboard Header -->
        <header class="dashboard-header">
            <div class="dashboard-header-content">
                <h1 class="dashboard-title">
                    <?php _e('Agent Dashboard', 'happy-place'); ?>
                </h1>
                
                <div class="dashboard-user-info">
                    <?php
                    $current_user = wp_get_current_user();
                    $agent_info = hph_get_agent_info($current_user->ID);
                    ?>
                    <span class="user-greeting">
                        <?php printf(__('Welcome, %s', 'happy-place'), $current_user->display_name); ?>
                    </span>
                    
                    <div class="user-actions">
                        <a href="<?php echo wp_logout_url(home_url()); ?>" class="btn btn-outline">
                            <?php _e('Logout', 'happy-place'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            
            <!-- Dashboard Navigation -->
            <nav class="dashboard-nav">
                <ul class="dashboard-nav-list">
                    <li class="dashboard-nav-item <?php echo $current_section === 'overview' ? 'active' : ''; ?>">
                        <a href="<?php echo hph_get_dashboard_url('overview'); ?>" data-section="overview">
                            <span class="nav-icon">📊</span>
                            <span class="nav-label"><?php _e('Overview', 'happy-place'); ?></span>
                        </a>
                    </li>
                    
                    <li class="dashboard-nav-item <?php echo $current_section === 'listings' ? 'active' : ''; ?>">
                        <a href="<?php echo hph_get_dashboard_url('listings'); ?>" data-section="listings">
                            <span class="nav-icon">🏠</span>
                            <span class="nav-label"><?php _e('My Listings', 'happy-place'); ?></span>
                        </a>
                    </li>
                    
                    <li class="dashboard-nav-item <?php echo $current_section === 'leads' ? 'active' : ''; ?>">
                        <a href="<?php echo hph_get_dashboard_url('leads'); ?>" data-section="leads">
                            <span class="nav-icon">👥</span>
                            <span class="nav-label"><?php _e('Leads', 'happy-place'); ?></span>
                        </a>
                    </li>
                    
                    <li class="dashboard-nav-item <?php echo $current_section === 'open-houses' ? 'active' : ''; ?>">
                        <a href="<?php echo hph_get_dashboard_url('open-houses'); ?>" data-section="open-houses">
                            <span class="nav-icon">🚪</span>
                            <span class="nav-label"><?php _e('Open Houses', 'happy-place'); ?></span>
                        </a>
                    </li>
                    
                    <li class="dashboard-nav-item <?php echo $current_section === 'performance' ? 'active' : ''; ?>">
                        <a href="<?php echo hph_get_dashboard_url('performance'); ?>" data-section="performance">
                            <span class="nav-icon">📈</span>
                            <span class="nav-label"><?php _e('Performance', 'happy-place'); ?></span>
                        </a>
                    </li>
                    
                    <li class="dashboard-nav-item <?php echo $current_section === 'profile' ? 'active' : ''; ?>">
                        <a href="<?php echo hph_get_dashboard_url('profile'); ?>" data-section="profile">
                            <span class="nav-icon">👤</span>
                            <span class="nav-label"><?php _e('Profile', 'happy-place'); ?></span>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Dashboard Main Content -->
            <main class="dashboard-main">
                
                <div class="dashboard-section dashboard-section-<?php echo esc_attr($current_section); ?> active">
                    
                    <?php
                    // Load section content
                    $section_file = "templates/template-parts/dashboard/section-{$current_section}.php";
                    
                    if (locate_template($section_file)) {
                        get_template_part('template-parts/dashboard/section', $current_section);
                    } else {
                        // Fallback content
                        ?>
                        <div class="dashboard-section-header">
                            <h2><?php echo ucfirst(str_replace('-', ' ', $current_section)); ?></h2>
                        </div>
                        
                        <div class="dashboard-section-content">
                            <div class="empty-state">
                                <div class="empty-state-icon">📋</div>
                                <h3><?php _e('Coming Soon', 'happy-place'); ?></h3>
                                <p><?php _e('This section is currently under development.', 'happy-place'); ?></p>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                    
                </div>
                
            </main>
            
        </div>
        
    </div>
</div>

<!-- Loading overlay -->
<div id="dashboard-loading" class="dashboard-loading" style="display: none;">
    <div class="loading-spinner"></div>
    <span class="loading-text"><?php _e('Loading...', 'happy-place'); ?></span>
</div>

<style>
/* Basic dashboard styles */
.dashboard-wrapper {
    min-height: 100vh;
    background: #f8f9fa;
}

.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0;
}

.dashboard-header {
    background: white;
    border-bottom: 1px solid #dee2e6;
    padding: 1rem 2rem;
}

.dashboard-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.dashboard-title {
    margin: 0;
    color: #333;
}

.dashboard-user-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.dashboard-content {
    display: flex;
    min-height: calc(100vh - 80px);
}

.dashboard-nav {
    width: 250px;
    background: white;
    border-right: 1px solid #dee2e6;
    padding: 1rem 0;
}

.dashboard-nav-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.dashboard-nav-item {
    margin: 0;
}

.dashboard-nav-item a {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1.5rem;
    color: #6c757d;
    text-decoration: none;
    transition: all 0.2s ease;
}

.dashboard-nav-item a:hover,
.dashboard-nav-item.active a {
    background: #e9ecef;
    color: #495057;
}

.dashboard-main {
    flex: 1;
    padding: 2rem;
}

.empty-state {
    text-align: center;
    padding: 3rem;
}

.empty-state-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.btn {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    transition: background 0.2s ease;
}

.btn:hover {
    background: #0056b3;
    color: white;
}

.btn-outline {
    background: transparent;
    border: 1px solid #007bff;
    color: #007bff;
}

.btn-outline:hover {
    background: #007bff;
    color: white;
}

.dashboard-loading {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.8);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #007bff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@media (max-width: 768px) {
    .dashboard-content {
        flex-direction: column;
    }
    
    .dashboard-nav {
        width: 100%;
        order: 2;
    }
    
    .dashboard-nav-list {
        display: flex;
        overflow-x: auto;
    }
    
    .dashboard-nav-item {
        flex-shrink: 0;
    }
    
    .dashboard-main {
        order: 1;
        padding: 1rem;
    }
}
</style>

<?php get_footer(); ?>