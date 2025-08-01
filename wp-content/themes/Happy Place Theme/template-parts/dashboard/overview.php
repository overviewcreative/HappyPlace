<?php
/**
 * Dashboard Overview Section
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get passed variables or defaults
$current_user = $current_user ?? wp_get_current_user();
$stats = $stats ?? [];
$recent_listings = $recent_listings ?? [];
?>

<div class="hph-dashboard-overview">
    
    <!-- Welcome Section -->
    <div class="overview-welcome">
        <div class="welcome-content">
            <h2>Welcome back, <?php echo esc_html($current_user->display_name); ?>!</h2>
            <p>Here's what's happening with your listings today.</p>
        </div>
        <div class="welcome-actions">
            <a href="<?php echo admin_url('post-new.php?post_type=listing'); ?>" class="action-btn action-btn--primary">
                <i class="fas fa-plus"></i> Add New Listing
            </a>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card stat-card--active-listings">
            <div class="stat-icon">
                <i class="fas fa-home"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html($stats['active_listings'] ?? 0); ?></div>
                <div class="stat-label">Active Listings</div>
            </div>
        </div>
        
        <div class="stat-card stat-card--inquiries">
            <div class="stat-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html($stats['pending_inquiries'] ?? 0); ?></div>
                <div class="stat-label">Pending Inquiries</div>
            </div>
        </div>
        
        <div class="stat-card stat-card--views">
            <div class="stat-icon">
                <i class="fas fa-eye"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html($stats['total_views'] ?? 0); ?></div>
                <div class="stat-label">Total Views</div>
            </div>
        </div>
        
        <div class="stat-card stat-card--leads">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html($stats['leads_this_month'] ?? 0); ?></div>
                <div class="stat-label">Leads This Month</div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="overview-sections">
        <div class="section-main">
            <div class="recent-listings">
                <div class="section-header">
                    <h3>Recent Listings</h3>
                    <a href="?section=listings" class="view-all-link">View All</a>
                </div>
                
                <?php if (!empty($recent_listings)) : ?>
                    <div class="listings-list">
                        <?php foreach (array_slice($recent_listings, 0, 5) as $listing) : ?>
                            <div class="listing-item">
                                <div class="listing-image">
                                    <?php if (has_post_thumbnail($listing->ID)) : ?>
                                        <?php echo get_the_post_thumbnail($listing->ID, 'thumbnail'); ?>
                                    <?php else : ?>
                                        <div class="placeholder-image">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="listing-details">
                                    <h4><a href="<?php echo get_permalink($listing->ID); ?>"><?php echo esc_html($listing->post_title); ?></a></h4>
                                    <div class="listing-meta">
                                        <span class="status">Published</span>
                                        <span class="date"><?php echo get_the_date('M j, Y', $listing->ID); ?></span>
                                    </div>
                                </div>
                                <div class="listing-actions">
                                    <a href="<?php echo get_edit_post_link($listing->ID); ?>" class="action-link">Edit</a>
                                    <a href="<?php echo get_permalink($listing->ID); ?>" class="action-link">View</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="empty-state">
                        <i class="fas fa-home"></i>
                        <h4>No listings yet</h4>
                        <p>Create your first listing to get started.</p>
                        <a href="<?php echo admin_url('post-new.php?post_type=listing'); ?>" class="action-btn action-btn--primary">Create Listing</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="section-sidebar">
            <div class="quick-actions">
                <h3>Quick Actions</h3>
                <div class="actions-list">
                    <a href="?section=marketing" class="quick-action">
                        <i class="fas fa-palette"></i>
                        <span>Create Marketing Material</span>
                    </a>
                    <a href="?section=analytics" class="quick-action">
                        <i class="fas fa-chart-line"></i>
                        <span>View Analytics</span>
                    </a>
                    <a href="<?php echo admin_url('upload.php'); ?>" class="quick-action">
                        <i class="fas fa-images"></i>
                        <span>Media Library</span>
                    </a>
                </div>
            </div>
            
            <div class="performance-summary">
                <h3>This Month</h3>
                <div class="performance-stats">
                    <div class="perf-stat">
                        <span class="label">Page Views</span>
                        <span class="value"><?php echo number_format($stats['total_views'] ?? 0); ?></span>
                    </div>
                    <div class="perf-stat">
                        <span class="label">Inquiries</span>
                        <span class="value"><?php echo esc_html($stats['pending_inquiries'] ?? 0); ?></span>
                    </div>
                    <div class="perf-stat">
                        <span class="label">Leads</span>
                        <span class="value"><?php echo esc_html($stats['leads_this_month'] ?? 0); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
