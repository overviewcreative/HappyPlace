<?php
/**
 * Enhanced Dashboard Overview Section 
 * 
 * Real-time dashboard with live data, quick actions, and comprehensive stats
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get current user and agent data
$current_user = wp_get_current_user();
$current_agent_id = $current_user->ID;

// Enhanced stats calculation with proper error handling
try {
    // Active listings
    $active_listings = get_posts([
        'author' => $current_agent_id,
        'post_type' => 'listing',
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields' => 'ids'
    ]);

    // Pending listings
    $pending_listings = get_posts([
        'author' => $current_agent_id,
        'post_type' => 'listing',
        'post_status' => 'draft',
        'numberposts' => -1,
        'fields' => 'ids'
    ]);

    // Recent listings (last 30 days)
    $recent_listings = get_posts([
        'author' => $current_agent_id,
        'post_type' => 'listing',
        'post_status' => ['publish', 'draft'],
        'numberposts' => 5,
        'date_query' => [
            [
                'after' => '30 days ago',
                'before' => 'now',
                'inclusive' => true
            ]
        ]
    ]);

    // Calculate total views for all listings
    $total_views = 0;
    $monthly_views = 0;
    $this_month_start = date('Y-m-01');
    
    foreach ($active_listings as $listing_id) {
        $views = get_post_meta($listing_id, '_listing_views', true) ?: 0;
        $total_views += (int)$views;
        
        // Get monthly views (if tracking exists)
        $monthly_meta = get_post_meta($listing_id, '_monthly_views_' . date('Y_m'), true) ?: 0;
        $monthly_views += (int)$monthly_meta;
    }

    // Leads data (from user meta or custom table)
    $leads_this_month = get_user_meta($current_agent_id, '_leads_this_month', true) ?: 0;
    $total_leads = get_user_meta($current_agent_id, '_total_leads', true) ?: 0;

    // Open houses
    $upcoming_open_houses = get_posts([
        'author' => $current_agent_id,
        'post_type' => 'open_house',
        'post_status' => 'publish',
        'numberposts' => -1,
        'meta_query' => [
            [
                'key' => 'start_date',
                'value' => date('Y-m-d'),
                'compare' => '>='
            ]
        ]
    ]);

    // Calculate conversion rate
    $inquiries = get_user_meta($current_agent_id, '_total_inquiries', true) ?: 1;
    $conversion_rate = $total_leads > 0 ? round(($total_leads / max($inquiries, 1)) * 100, 1) : 0;

} catch (Exception $e) {
    error_log('Dashboard overview error: ' . $e->getMessage());
    // Fallback values
    $active_listings = [];
    $pending_listings = [];
    $recent_listings = [];
    $total_views = 0;
    $monthly_views = 0;
    $leads_this_month = 0;
    $upcoming_open_houses = [];
    $conversion_rate = 0;
}

// Recent activity feed
$recent_activity = [];

// Add listing activities
foreach (array_slice($recent_listings, 0, 3) as $listing) {
    $recent_activity[] = [
        'type' => 'listing',
        'icon' => 'fas fa-home',
        'message' => sprintf(__('New listing "%s" created', 'happy-place'), get_the_title($listing->ID)),
        'date' => get_the_date('c', $listing->ID),
        'url' => get_permalink($listing->ID)
    ];
}

// Add open house activities
foreach (array_slice($upcoming_open_houses, 0, 2) as $open_house) {
    $start_date = get_post_meta($open_house->ID, 'start_date', true);
    $recent_activity[] = [
        'type' => 'open_house',
        'icon' => 'fas fa-calendar-alt',
        'message' => sprintf(__('Open house scheduled for %s', 'happy-place'), date('M j, Y', strtotime($start_date))),
        'date' => get_the_date('c', $open_house->ID),
        'url' => '#'
    ];
}

// Sort by date
usort($recent_activity, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Notifications (mock data - replace with actual notification system)
$notifications = [
    [
        'type' => 'info',
        'icon' => 'fas fa-info-circle',
        'message' => __('Your listing photos are processing and will be ready shortly.', 'happy-place'),
        'date' => current_time('c'),
        'action_url' => add_query_arg('section', 'listings')
    ]
];

// Add notifications based on data
if (count($pending_listings) > 0) {
    $notifications[] = [
        'type' => 'warning',
        'icon' => 'fas fa-clock',
        'message' => sprintf(_n('You have %d listing waiting for review.', 'You have %d listings waiting for review.', count($pending_listings), 'happy-place'), count($pending_listings)),
        'date' => current_time('c'),
        'action_url' => add_query_arg(['section' => 'listings', 'status' => 'draft'])
    ];
}
?>

<div class="hph-dashboard-overview" data-section="overview">
    
    <!-- Enhanced Welcome Section -->
    <div class="hph-overview-header">
        <div class="hph-welcome-content">
            <h1 class="hph-overview-title">
                <?php 
                $greeting = '';
                $hour = (int)current_time('H');
                if ($hour < 12) {
                    $greeting = __('Good Morning', 'happy-place');
                } elseif ($hour < 17) {
                    $greeting = __('Good Afternoon', 'happy-place');
                } else {
                    $greeting = __('Good Evening', 'happy-place');
                }
                printf(esc_html__('%s, %s!', 'happy-place'), $greeting, esc_html($current_user->display_name)); 
                ?>
            </h1>
            <p class="hph-overview-subtitle">
                <?php esc_html_e('Here\'s your real estate business overview for today.', 'happy-place'); ?>
            </p>
        </div>
        <div class="hph-overview-actions">
            <button class="hph-btn hph-btn--refresh" data-refresh="overview" title="<?php esc_attr_e('Refresh Data', 'happy-place'); ?>">
                <i class="fas fa-sync"></i>
            </button>
            <a href="<?php echo esc_url(add_query_arg(['section' => 'listings', 'action' => 'new'])); ?>" 
               class="hph-btn hph-btn--primary">
                <i class="fas fa-plus"></i>
                <?php esc_html_e('Add New Listing', 'happy-place'); ?>
            </a>
        </div>
    </div>

    <!-- Enhanced Stats Grid -->
    <div class="hph-dashboard-stats">
        <!-- Active Listings -->
        <div class="hph-dashboard-stat-card" data-stat="active-listings">
            <div class="hph-dashboard-stat-icon">
                <i class="fas fa-home"></i>
            </div>
            <div class="hph-dashboard-stat-content">
                <h3 class="hph-stat-number" data-count="<?php echo count($active_listings); ?>">
                    <?php echo count($active_listings); ?>
                </h3>
                <p class="hph-stat-label"><?php esc_html_e('Active Listings', 'happy-place'); ?></p>
                <?php if (count($pending_listings) > 0): ?>
                    <span class="hph-stat-meta">
                        +<?php echo count($pending_listings); ?> <?php esc_html_e('pending', 'happy-place'); ?>
                    </span>
                <?php endif; ?>
            </div>
            <a href="<?php echo esc_url(add_query_arg('section', 'listings')); ?>" class="hph-stat-link">
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <!-- Monthly Views -->
        <div class="hph-dashboard-stat-card" data-stat="monthly-views">
            <div class="hph-dashboard-stat-icon">
                <i class="fas fa-eye"></i>
            </div>
            <div class="hph-dashboard-stat-content">
                <h3 class="hph-stat-number" data-count="<?php echo $monthly_views; ?>">
                    <?php echo number_format($monthly_views); ?>
                </h3>
                <p class="hph-stat-label"><?php esc_html_e('Views This Month', 'happy-place'); ?></p>
                <span class="hph-stat-meta">
                    <?php echo number_format($total_views); ?> <?php esc_html_e('total', 'happy-place'); ?>
                </span>
            </div>
            <a href="<?php echo esc_url(add_query_arg('section', 'performance')); ?>" class="hph-stat-link">
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <!-- Leads -->
        <div class="hph-dashboard-stat-card" data-stat="leads">
            <div class="hph-dashboard-stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="hph-dashboard-stat-content">
                <h3 class="hph-stat-number" data-count="<?php echo $leads_this_month; ?>">
                    <?php echo $leads_this_month; ?>
                </h3>
                <p class="hph-stat-label"><?php esc_html_e('Leads This Month', 'happy-place'); ?></p>
                <span class="hph-stat-meta">
                    <?php echo $conversion_rate; ?>% <?php esc_html_e('conversion', 'happy-place'); ?>
                </span>
            </div>
            <a href="<?php echo esc_url(add_query_arg('section', 'leads')); ?>" class="hph-stat-link">
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <!-- Open Houses -->
        <div class="hph-dashboard-stat-card" data-stat="open-houses">
            <div class="hph-dashboard-stat-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="hph-dashboard-stat-content">
                <h3 class="hph-stat-number" data-count="<?php echo count($upcoming_open_houses); ?>">
                    <?php echo count($upcoming_open_houses); ?>
                </h3>
                <p class="hph-stat-label"><?php esc_html_e('Upcoming Events', 'happy-place'); ?></p>
                <?php if (count($upcoming_open_houses) > 0): ?>
                    <span class="hph-stat-meta">
                        <?php
                        $next_event = get_post_meta($upcoming_open_houses[0]->ID, 'start_date', true);
                        echo esc_html(date('M j', strtotime($next_event)));
                        ?>
                    </span>
                <?php endif; ?>
            </div>
            <a href="<?php echo esc_url(add_query_arg('section', 'open-houses')); ?>" class="hph-stat-link">
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>

    <!-- Quick Actions Grid -->
    <div class="hph-dashboard-quick-actions">
        <h2 class="hph-section-title">
            <i class="fas fa-bolt"></i>
            <?php esc_html_e('Quick Actions', 'happy-place'); ?>
        </h2>
        
        <div class="hph-action-grid">
            <a href="<?php echo esc_url(add_query_arg(['section' => 'listings', 'action' => 'new'])); ?>" 
               class="hph-action-card hph-action-card--primary">
                <div class="hph-action-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div class="hph-action-content">
                    <h3><?php esc_html_e('Add New Listing', 'happy-place'); ?></h3>
                    <p><?php esc_html_e('Create a property listing with our advanced form', 'happy-place'); ?></p>
                </div>
            </a>

            <a href="<?php echo esc_url(add_query_arg(['section' => 'open-houses', 'action' => 'new'])); ?>" 
               class="hph-action-card hph-action-card--info">
                <div class="hph-action-icon">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <div class="hph-action-content">
                    <h3><?php esc_html_e('Schedule Open House', 'happy-place'); ?></h3>
                    <p><?php esc_html_e('Plan your next open house event', 'happy-place'); ?></p>
                </div>
            </a>

            <a href="<?php echo esc_url(add_query_arg(['section' => 'leads', 'action' => 'new'])); ?>" 
               class="hph-action-card hph-action-card--success">
                <div class="hph-action-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="hph-action-content">
                    <h3><?php esc_html_e('Add New Lead', 'happy-place'); ?></h3>
                    <p><?php esc_html_e('Create a new lead entry', 'happy-place'); ?></p>
                </div>
            </a>

            <a href="<?php echo esc_url(add_query_arg('section', 'performance')); ?>" 
               class="hph-action-card hph-action-card--warning">
                <div class="hph-action-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="hph-action-content">
                    <h3><?php esc_html_e('View Analytics', 'happy-place'); ?></h3>
                    <p><?php esc_html_e('Check your performance metrics', 'happy-place'); ?></p>
                </div>
            </a>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="hph-overview-content-grid">
        
        <!-- Recent Activity -->
        <div class="hph-dashboard-card hph-recent-activity">
            <header class="hph-card-header">
                <h3 class="hph-card-title">
                    <i class="fas fa-history"></i>
                    <?php esc_html_e('Recent Activity', 'happy-place'); ?>
                </h3>
                <button class="hph-btn hph-btn--sm hph-btn--ghost" data-refresh="activity">
                    <i class="fas fa-sync"></i>
                </button>
            </header>
            <div class="hph-card-content">
                <?php if (!empty($recent_activity)): ?>
                    <ul class="hph-activity-list">
                        <?php foreach (array_slice($recent_activity, 0, 5) as $activity): ?>
                            <li class="hph-activity-item">
                                <div class="hph-activity-icon">
                                    <i class="<?php echo esc_attr($activity['icon']); ?>"></i>
                                </div>
                                <div class="hph-activity-content">
                                    <p class="hph-activity-message">
                                        <?php if (!empty($activity['url'])): ?>
                                            <a href="<?php echo esc_url($activity['url']); ?>">
                                                <?php echo esc_html($activity['message']); ?>
                                            </a>
                                        <?php else: ?>
                                            <?php echo esc_html($activity['message']); ?>
                                        <?php endif; ?>
                                    </p>
                                    <time class="hph-activity-time" datetime="<?php echo esc_attr($activity['date']); ?>">
                                        <?php echo esc_html(human_time_diff(strtotime($activity['date']))); ?> ago
                                    </time>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="hph-card-footer">
                        <a href="<?php echo esc_url(add_query_arg('section', 'activity')); ?>" class="hph-btn hph-btn--sm hph-btn--outline">
                            <?php esc_html_e('View All Activity', 'happy-place'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="hph-empty-state">
                        <i class="fas fa-clock"></i>
                        <p><?php esc_html_e('No recent activity to display.', 'happy-place'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Notifications -->
        <div class="hph-dashboard-card hph-notifications">
            <header class="hph-card-header">
                <h3 class="hph-card-title">
                    <i class="fas fa-bell"></i>
                    <?php esc_html_e('Notifications', 'happy-place'); ?>
                    <?php if (count($notifications) > 0): ?>
                        <span class="hph-notification-badge"><?php echo count($notifications); ?></span>
                    <?php endif; ?>
                </h3>
                <button class="hph-btn hph-btn--sm hph-btn--ghost" data-action="mark-all-read">
                    <i class="fas fa-check"></i>
                </button>
            </header>
            <div class="hph-card-content">
                <?php if (!empty($notifications)): ?>
                    <ul class="hph-notification-list">
                        <?php foreach ($notifications as $notification): ?>
                            <li class="hph-notification-item hph-notification-item--<?php echo esc_attr($notification['type']); ?>">
                                <div class="hph-notification-icon">
                                    <i class="<?php echo esc_attr($notification['icon']); ?>"></i>
                                </div>
                                <div class="hph-notification-content">
                                    <p class="hph-notification-message">
                                        <?php echo esc_html($notification['message']); ?>
                                    </p>
                                    <time class="hph-notification-time" datetime="<?php echo esc_attr($notification['date']); ?>">
                                        <?php echo esc_html(human_time_diff(strtotime($notification['date']))); ?> ago
                                    </time>
                                    <?php if (!empty($notification['action_url'])): ?>
                                        <a href="<?php echo esc_url($notification['action_url']); ?>" class="hph-notification-action">
                                            <?php esc_html_e('View', 'happy-place'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="hph-empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <p><?php esc_html_e('No new notifications.', 'happy-place'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- Auto-refresh functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh data every 5 minutes
    setInterval(function() {
        if (document.querySelector('[data-section="overview"]')) {
            HphDashboard.refreshOverviewData();
        }
    }, 300000); // 5 minutes

    // Manual refresh buttons
    document.querySelectorAll('[data-refresh]').forEach(button => {
        button.addEventListener('click', function() {
            const target = this.dataset.refresh;
            HphDashboard.refreshSection(target);
        });
    });
});
</script>
