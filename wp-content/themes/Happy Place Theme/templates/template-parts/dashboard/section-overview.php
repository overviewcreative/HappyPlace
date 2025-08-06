<?php

/**
 * Dashboard Overview Section Template Part
 *
 * @package HappyPlace
 */

// Access the section data passed from the parent template
$section_data = $args['section_data'] ?? [];

// Get stats with fallbacks
$active_listings = $section_data['active_listings'] ?? 0;
$pending_leads = $section_data['total_leads'] ?? 0;
$recent_activity = $section_data['recent_activity'] ?? [];
$notifications = $section_data['notifications'] ?? [];
?>

<div class="hph-dashboard-overview">
    <div class="hph-dashboard-stat-card">
        <div class="hph-dashboard-stat-icon">
            <i class="fas fa-home"></i>
        </div>
        <div class="hph-dashboard-stat-content">
            <h3><?php echo esc_html($active_listings); ?></h3>
            <p><?php _e('Active Listings', 'happy-place'); ?></p>
        </div>
    </div>

    <div class="hph-dashboard-stat-card">
        <div class="hph-dashboard-stat-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="hph-dashboard-stat-content">
            <h3><?php echo esc_html($pending_leads); ?></h3>
            <p><?php _e('Pending Leads', 'happy-place'); ?></p>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="hph-dashboard-quick-actions">
    <h2><?php _e('Quick Actions', 'happy-place'); ?></h2>
    <div class="hph-dashboard-action-grid">
        <a href="<?php echo esc_url(add_query_arg(['action' => 'new-listing'], get_permalink())); ?>" class="hph-dashboard-action-card">
            <div class="hph-dashboard-action-icon">
                <i class="fas fa-plus-circle"></i>
            </div>
            <h3><?php _e('Add New Listing', 'happy-place'); ?></h3>
            <p><?php _e('Create a new property listing', 'happy-place'); ?></p>
        </a>

        <a href="<?php echo esc_url(add_query_arg(['action' => 'new-open-house'], get_permalink())); ?>" class="hph-dashboard-action-card">
            <div class="hph-dashboard-action-icon">
                <i class="fas fa-calendar-plus"></i>
            </div>
            <h3><?php _e('Schedule Open House', 'happy-place'); ?></h3>
            <p><?php _e('Schedule a new open house event', 'happy-place'); ?></p>
        </a>

        <a href="<?php echo esc_url(add_query_arg(['action' => 'new-lead'], get_permalink())); ?>" class="hph-dashboard-action-card">
            <div class="hph-dashboard-action-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h3><?php _e('Add New Lead', 'happy-place'); ?></h3>
            <p><?php _e('Create a new lead entry', 'happy-place'); ?></p>
        </a>
    </div>
</div>

<div class="hph-dashboard-overview-grid">
    <!-- Recent Activity -->
    <section class="hph-dashboard-card hph-dashboard-recent-activity">
        <h2 class="hph-dashboard-card-title">
            <i class="fas fa-history"></i>
            <?php _e('Recent Activity', 'happy-place'); ?>
        </h2>
        <?php if (!empty($recent_activity)) : ?>
            <ul class="hph-activity-list">
                <?php foreach ($recent_activity as $activity) : ?>
                    <li class="hph-activity-item">
                        <span class="hph-activity-icon">
                            <i class="<?php echo esc_attr($activity['icon'] ?? 'fas fa-circle'); ?>"></i>
                        </span>
                        <div class="hph-activity-content">
                            <p><?php echo esc_html($activity['message']); ?></p>
                            <time datetime="<?php echo esc_attr($activity['date']); ?>">
                                <?php echo esc_html(human_time_diff(strtotime($activity['date']), current_time('timestamp'))); ?> ago
                            </time>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p class="hph-no-content"><?php _e('No recent activity', 'happy-place'); ?></p>
        <?php endif; ?>
    </section>

    <!-- Notifications -->
    <section class="hph-dashboard-card hph-dashboard-notifications">
        <h2 class="hph-dashboard-card-title">
            <i class="fas fa-bell"></i>
            <?php _e('Notifications', 'happy-place'); ?>
        </h2>
        <?php if (!empty($notifications)) : ?>
            <ul class="hph-notification-list">
                <?php foreach ($notifications as $notification) : ?>
                    <li class="hph-notification-item hph-notification-item--<?php echo esc_attr($notification['type'] ?? 'info'); ?>">
                        <span class="hph-notification-icon">
                            <i class="<?php echo esc_attr($notification['icon'] ?? 'fas fa-info-circle'); ?>"></i>
                        </span>
                        <div class="hph-notification-content">
                            <p><?php echo esc_html($notification['message']); ?></p>
                            <?php if (!empty($notification['action_url'])) : ?>
                                <a href="<?php echo esc_url($notification['action_url']); ?>" class="hph-notification-action">
                                    <?php echo esc_html($notification['action_text'] ?? __('View', 'happy-place')); ?>
                                </a>
                            <?php endif; ?>
                            <time datetime="<?php echo esc_attr($notification['date']); ?>">
                                <?php echo esc_html(human_time_diff(strtotime($notification['date']), current_time('timestamp'))); ?> ago
                            </time>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p class="hph-no-content"><?php _e('No new notifications', 'happy-place'); ?></p>
        <?php endif; ?>
    </section>
</div>
</div>
<!-- Debug: Overview section ended -->