<?php
/**
 * Dashboard Overview Section Template
 * 
 * Displays the main dashboard overview with stats, recent activity, and quick actions
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get agent data (available from parent template)
$current_agent_id = $current_agent_id ?? get_current_user_id();
$stats = $stats ?? [];

// Calculate additional stats
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

// Stats for dashboard cards
$dashboard_stats = [
    'active_listings' => [
        'title' => __('Active Listings', 'happy-place'),
        'value' => $stats['active_listings'] ?? 0,
        'change' => $stats['listings_change'] ?? 0,
        'icon' => 'fa-home',
        'color' => 'primary'
    ],
    'pending_inquiries' => [
        'title' => __('Pending Inquiries', 'happy-place'),
        'value' => count($pending_inquiries),
        'change' => $stats['inquiries_change'] ?? 0,
        'icon' => 'fa-envelope',
        'color' => 'warning'
    ],
    'total_views' => [
        'title' => __('Total Views', 'happy-place'),
        'value' => number_format($stats['total_views'] ?? 0),
        'change' => $stats['views_change'] ?? 0,
        'icon' => 'fa-eye',
        'color' => 'info'
    ],
    'this_month_leads' => [
        'title' => __('Leads This Month', 'happy-place'),
        'value' => $stats['leads_this_month'] ?? 0,
        'change' => $stats['leads_change'] ?? 0,
        'icon' => 'fa-users',
        'color' => 'success'
    ]
];

// Recent activity data
$recent_activity = function_exists('hph_get_agent_activity') ? 
    hph_get_agent_activity($current_agent_id, 10) : 
    hph_get_default_activity($current_agent_id);
?>

<div class="hph-overview-section">
    
    <!-- Quick Stats Grid -->
    <div class="hph-dashboard-stats">
        <?php foreach ($dashboard_stats as $stat_key => $stat) : ?>
            <div class="hph-stat-card">
                <div class="hph-stat-card-header">
                    <h4 class="hph-stat-card-title"><?php echo esc_html($stat['title']); ?></h4>
                    <div class="hph-stat-card-icon">
                        <i class="fas <?php echo esc_attr($stat['icon']); ?>"></i>
                    </div>
                </div>
                
                <div class="hph-stat-card-value"><?php echo esc_html($stat['value']); ?></div>
                
                <?php if (isset($stat['change']) && $stat['change'] != 0) : ?>
                    <div class="hph-stat-card-change <?php echo $stat['change'] > 0 ? 'hph-stat-card-change--positive' : 'hph-stat-card-change--negative'; ?>">
                        <i class="fas <?php echo $stat['change'] > 0 ? 'fa-arrow-up' : 'fa-arrow-down'; ?>"></i>
                        <?php echo esc_html(abs($stat['change']) . '%'); ?>
                        <span class="hph-text-xs"><?php esc_html_e('vs last month', 'happy-place'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Main Content Grid -->
    <div class="hph-dashboard-content">
        
        <!-- Recent Activity Widget -->
        <div class="hph-dashboard-widget">
            <div class="hph-dashboard-widget-header">
                <h3 class="hph-dashboard-widget-title">
                    <i class="fas fa-history"></i>
                    <?php esc_html_e('Recent Activity', 'happy-place'); ?>
                </h3>
                <p class="hph-dashboard-widget-subtitle">
                    <?php esc_html_e('Your latest interactions and updates', 'happy-place'); ?>
                </p>
            </div>
            <div class="hph-dashboard-widget-content">
                <?php if (!empty($recent_activity)) : ?>
                    <div class="hph-activity-feed">
                        <?php foreach ($recent_activity as $activity) : ?>
                            <div class="hph-activity-item">
                                <div class="hph-activity-icon">
                                    <i class="fas <?php echo esc_attr($activity['icon'] ?? 'fa-circle'); ?>"></i>
                                </div>
                                <div class="hph-activity-content">
                                    <div class="hph-activity-title">
                                        <?php echo esc_html($activity['title']); ?>
                                    </div>
                                    <?php if (!empty($activity['description'])) : ?>
                                        <div class="hph-activity-description">
                                            <?php echo esc_html($activity['description']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="hph-activity-time">
                                        <?php echo esc_html($activity['time_ago'] ?? human_time_diff($activity['timestamp'] ?? time())); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="hph-empty-state">
                        <div class="hph-empty-state-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h4 class="hph-empty-state-title"><?php esc_html_e('No Recent Activity', 'happy-place'); ?></h4>
                        <p class="hph-empty-state-description">
                            <?php esc_html_e('Your recent activity will appear here once you start using the platform.', 'happy-place'); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="hph-dashboard-widget-footer">
                <a href="<?php echo esc_url(add_query_arg('section', 'activity')); ?>" class="hph-btn hph-btn--outline hph-btn--sm">
                    <?php esc_html_e('View All Activity', 'happy-place'); ?>
                </a>
            </div>
        </div>

        <!-- Quick Actions Sidebar -->
        <div class="hph-dashboard-sidebar-content">
            
            <!-- Quick Actions -->
            <div class="hph-dashboard-widget">
                <div class="hph-dashboard-widget-header">
                    <h3 class="hph-dashboard-widget-title">
                        <i class="fas fa-bolt"></i>
                        <?php esc_html_e('Quick Actions', 'happy-place'); ?>
                    </h3>
                </div>
                <div class="hph-dashboard-widget-content">
                    <div class="hph-quick-actions">
                        <a href="<?php echo esc_url(admin_url('post-new.php?post_type=listing')); ?>" class="hph-quick-action">
                            <div class="hph-quick-action-icon">
                                <i class="fas fa-plus"></i>
                            </div>
                            <div class="hph-quick-action-content">
                                <div class="hph-quick-action-title"><?php esc_html_e('Add Listing', 'happy-place'); ?></div>
                                <div class="hph-quick-action-description"><?php esc_html_e('Create a new property listing', 'happy-place'); ?></div>
                            </div>
                        </a>
                        
                        <a href="<?php echo esc_url(add_query_arg('section', 'open-houses')); ?>" class="hph-quick-action">
                            <div class="hph-quick-action-icon">
                                <i class="fas fa-calendar-plus"></i>
                            </div>
                            <div class="hph-quick-action-content">
                                <div class="hph-quick-action-title"><?php esc_html_e('Schedule Open House', 'happy-place'); ?></div>
                                <div class="hph-quick-action-description"><?php esc_html_e('Plan your next open house event', 'happy-place'); ?></div>
                            </div>
                        </a>
                        
                        <a href="<?php echo esc_url(add_query_arg('section', 'leads')); ?>" class="hph-quick-action">
                            <div class="hph-quick-action-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="hph-quick-action-content">
                                <div class="hph-quick-action-title"><?php esc_html_e('Manage Leads', 'happy-place'); ?></div>
                                <div class="hph-quick-action-description"><?php esc_html_e('Review and follow up on leads', 'happy-place'); ?></div>
                            </div>
                        </a>
                        
                        <a href="<?php echo esc_url(add_query_arg('section', 'performance')); ?>" class="hph-quick-action">
                            <div class="hph-quick-action-icon">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <div class="hph-quick-action-content">
                                <div class="hph-quick-action-title"><?php esc_html_e('View Reports', 'happy-place'); ?></div>
                                <div class="hph-quick-action-description"><?php esc_html_e('Check your performance metrics', 'happy-place'); ?></div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Upcoming Events -->
            <?php if (!empty($upcoming_open_houses)) : ?>
            <div class="hph-dashboard-widget">
                <div class="hph-dashboard-widget-header">
                    <h3 class="hph-dashboard-widget-title">
                        <i class="fas fa-calendar-alt"></i>
                        <?php esc_html_e('Upcoming Events', 'happy-place'); ?>
                    </h3>
                </div>
                <div class="hph-dashboard-widget-content">
                    <div class="hph-upcoming-events">
                        <?php foreach (array_slice($upcoming_open_houses, 0, 3) as $event) : ?>
                            <div class="hph-event-item">
                                <div class="hph-event-date">
                                    <div class="hph-event-day"><?php echo esc_html(wp_date('j', strtotime($event['date']))); ?></div>
                                    <div class="hph-event-month"><?php echo esc_html(wp_date('M', strtotime($event['date']))); ?></div>
                                </div>
                                <div class="hph-event-details">
                                    <div class="hph-event-title"><?php echo esc_html($event['title']); ?></div>
                                    <div class="hph-event-time">
                                        <?php echo esc_html($event['time']); ?>
                                        <?php if (!empty($event['location'])) : ?>
                                            • <?php echo esc_html($event['location']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="hph-dashboard-widget-footer">
                    <a href="<?php echo esc_url(add_query_arg('section', 'open-houses')); ?>" class="hph-btn hph-btn--outline hph-btn--sm">
                        <?php esc_html_e('View All Events', 'happy-place'); ?>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Performance Summary -->
            <div class="hph-dashboard-widget">
                <div class="hph-dashboard-widget-header">
                    <h3 class="hph-dashboard-widget-title">
                        <i class="fas fa-chart-line"></i>
                        <?php esc_html_e('This Month', 'happy-place'); ?>
                    </h3>
                </div>
                <div class="hph-dashboard-widget-content">
                    <div class="hph-performance-summary">
                        <div class="hph-performance-item">
                            <div class="hph-performance-label"><?php esc_html_e('New Listings', 'happy-place'); ?></div>
                            <div class="hph-performance-value"><?php echo esc_html($stats['new_listings_month'] ?? '0'); ?></div>
                        </div>
                        <div class="hph-performance-item">
                            <div class="hph-performance-label"><?php esc_html_e('Showings', 'happy-place'); ?></div>
                            <div class="hph-performance-value"><?php echo esc_html($stats['showings_month'] ?? '0'); ?></div>
                        </div>
                        <div class="hph-performance-item">
                            <div class="hph-performance-label"><?php esc_html_e('Closed Deals', 'happy-place'); ?></div>
                            <div class="hph-performance-value"><?php echo esc_html($stats['closed_deals_month'] ?? '0'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Listings -->
    <?php if (!empty($recent_listings)) : ?>
    <div class="hph-dashboard-widget">
        <div class="hph-dashboard-widget-header">
            <h3 class="hph-dashboard-widget-title">
                <i class="fas fa-home"></i>
                <?php esc_html_e('Recent Listings', 'happy-place'); ?>
            </h3>
            <p class="hph-dashboard-widget-subtitle">
                <?php esc_html_e('Your most recently added properties', 'happy-place'); ?>
            </p>
        </div>
        <div class="hph-dashboard-widget-content">
            <div class="hph-listings-grid">
                <?php foreach (array_slice($recent_listings, 0, 4) as $listing) : 
                    $listing_id = $listing->ID;
                    $listing_price = get_field('listing_price', $listing_id);
                    $listing_address = get_field('listing_address', $listing_id);
                    $listing_images = get_field('listing_images', $listing_id);
                    $listing_status = get_field('listing_status', $listing_id) ?: 'active';
                    $listing_bedrooms = get_field('bedrooms', $listing_id);
                    $listing_bathrooms = get_field('bathrooms', $listing_id);
                    $listing_sqft = get_field('square_footage', $listing_id);
                    
                    $featured_image = !empty($listing_images) ? $listing_images[0] : get_the_post_thumbnail_url($listing_id, 'medium');
                ?>
                    <div class="hph-listing-card">
                        <?php if ($featured_image) : ?>
                            <img src="<?php echo esc_url($featured_image); ?>" 
                                 alt="<?php echo esc_attr(get_the_title($listing_id)); ?>" 
                                 class="hph-listing-image"
                                 loading="lazy">
                        <?php endif; ?>
                        
                        <div class="hph-listing-status-badge hph-listing-status--<?php echo esc_attr($listing_status); ?>">
                            <?php echo esc_html(ucfirst($listing_status)); ?>
                        </div>
                        
                        <div class="hph-listing-content">
                            <?php if ($listing_price) : ?>
                                <div class="hph-listing-price">$<?php echo esc_html(number_format($listing_price)); ?></div>
                            <?php endif; ?>
                            
                            <div class="hph-listing-address">
                                <?php echo esc_html($listing_address ?: get_the_title($listing_id)); ?>
                            </div>
                            
                            <?php if ($listing_bedrooms || $listing_bathrooms || $listing_sqft) : ?>
                                <div class="hph-listing-details">
                                    <?php if ($listing_bedrooms) : ?>
                                        <span><i class="fas fa-bed"></i> <?php echo esc_html($listing_bedrooms); ?> bed</span>
                                    <?php endif; ?>
                                    <?php if ($listing_bathrooms) : ?>
                                        <span><i class="fas fa-bath"></i> <?php echo esc_html($listing_bathrooms); ?> bath</span>
                                    <?php endif; ?>
                                    <?php if ($listing_sqft) : ?>
                                        <span><i class="fas fa-ruler-combined"></i> <?php echo esc_html(number_format($listing_sqft)); ?> sq ft</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="hph-listing-actions">
                                <a href="<?php echo esc_url(get_permalink($listing_id)); ?>" 
                                   class="hph-listing-action" 
                                   title="<?php esc_attr_e('View Listing', 'happy-place'); ?>">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?php echo esc_url(get_edit_post_link($listing_id)); ?>" 
                                   class="hph-listing-action" 
                                   title="<?php esc_attr_e('Edit Listing', 'happy-place'); ?>">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="#" 
                                   class="hph-listing-action hph-listing-action--analytics" 
                                   title="<?php esc_attr_e('View Analytics', 'happy-place'); ?>"
                                   data-listing-id="<?php echo esc_attr($listing_id); ?>">
                                    <i class="fas fa-chart-bar"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="hph-dashboard-widget-footer">
            <a href="<?php echo esc_url(add_query_arg('section', 'listings')); ?>" class="hph-btn hph-btn--outline">
                <?php esc_html_e('View All Listings', 'happy-place'); ?>
            </a>
        </div>
    </div>
    <?php endif; ?>

</div>

<style>
/* Overview Section Specific Styles */
.hph-performance-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: var(--hph-spacing-4);
}

.hph-performance-item {
    text-align: center;
    padding: var(--hph-spacing-3);
    background: var(--hph-color-gray-25);
    border-radius: var(--hph-radius-lg);
}

.hph-performance-label {
    font-size: var(--hph-font-size-xs);
    color: var(--hph-color-gray-600);
    margin-bottom: var(--hph-spacing-1);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.hph-performance-value {
    font-size: var(--hph-font-size-xl);
    font-weight: var(--hph-font-bold);
    color: var(--hph-color-primary-600);
}

.hph-quick-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--hph-spacing-3);
    padding: var(--hph-spacing-4);
    background: var(--hph-color-gray-25);
    border-radius: var(--hph-radius-lg);
    margin-top: auto;
}

.hph-quick-stat {
    text-align: center;
}

.hph-quick-stat-value {
    font-size: var(--hph-font-size-lg);
    font-weight: var(--hph-font-bold);
    color: var(--hph-color-primary-600);
    margin-bottom: var(--hph-spacing-1);
}

.hph-quick-stat-label {
    font-size: var(--hph-font-size-xs);
    color: var(--hph-color-gray-600);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.hph-dashboard-sidebar-footer {
    margin-top: auto;
    padding-top: var(--hph-spacing-6);
}

@media (max-width: 768px) {
    .hph-dashboard-content {
        grid-template-columns: 1fr;
    }
    
    .hph-performance-summary {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .hph-quick-stats {
        grid-template-columns: 1fr;
        gap: var(--hph-spacing-2);
    }
}
</style>

<?php
/**
 * Generate default activity if no function exists
 */
function hph_get_default_activity($agent_id) {
    return [
        [
            'title' => __('Welcome to your dashboard!', 'happy-place'),
            'description' => __('Start by adding your first listing or updating your profile.', 'happy-place'),
            'icon' => 'fa-star',
            'timestamp' => time(),
            'time_ago' => __('Just now', 'happy-place')
        ]
    ];
}
?>