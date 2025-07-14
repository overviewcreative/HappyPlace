<?php

/**
 * Dashboard Open Houses Section Template
 * 
 * Displays the open houses management interface
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get agent data (available from parent template)
$current_agent_id = $current_agent_id ?? get_current_user_id();

// Get open houses for the current agent
$open_houses = function_exists('hph_get_agent_open_houses') ?
    hph_get_agent_open_houses($current_agent_id) : [];

// Get upcoming and past open houses
$upcoming_open_houses = array_filter($open_houses, function ($oh) {
    return strtotime($oh['start_date']) >= strtotime('today');
});

$past_open_houses = array_filter($open_houses, function ($oh) {
    return strtotime($oh['start_date']) < strtotime('today');
});
?>

<div class="hph-open-houses-section">

    <!-- Section Header -->
    <div class="hph-dashboard-section-header">
        <div class="hph-section-title-group">
            <h2 class="hph-dashboard-section-title">
                <i class="fas fa-calendar-alt"></i>
                <?php esc_html_e('Open Houses', 'happy-place'); ?>
            </h2>
            <p class="hph-dashboard-section-subtitle">
                <?php esc_html_e('Schedule and manage your property open houses', 'happy-place'); ?>
            </p>
        </div>
        <div class="hph-section-actions">
            <a href="<?php echo esc_url(add_query_arg('action', 'new-open-house')); ?>"
                class="hph-btn hph-btn--primary">
                <i class="fas fa-plus"></i>
                <?php esc_html_e('Schedule Open House', 'happy-place'); ?>
            </a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="hph-dashboard-stats hph-dashboard-stats--compact">
        <div class="hph-stat-card">
            <div class="hph-stat-card-header">
                <h4 class="hph-stat-card-title"><?php esc_html_e('Upcoming', 'happy-place'); ?></h4>
                <div class="hph-stat-card-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
            </div>
            <div class="hph-stat-card-value"><?php echo count($upcoming_open_houses); ?></div>
        </div>

        <div class="hph-stat-card">
            <div class="hph-stat-card-header">
                <h4 class="hph-stat-card-title"><?php esc_html_e('This Month', 'happy-place'); ?></h4>
                <div class="hph-stat-card-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
            </div>
            <div class="hph-stat-card-value">
                <?php
                $this_month = count(array_filter($open_houses, function ($oh) {
                    return date('Y-m', strtotime($oh['start_date'])) === date('Y-m');
                }));
                echo $this_month;
                ?>
            </div>
        </div>

        <div class="hph-stat-card">
            <div class="hph-stat-card-header">
                <h4 class="hph-stat-card-title"><?php esc_html_e('Total Visitors', 'happy-place'); ?></h4>
                <div class="hph-stat-card-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="hph-stat-card-value">
                <?php
                $total_visitors = array_sum(array_column($open_houses, 'actual_visitors'));
                echo number_format($total_visitors);
                ?>
            </div>
        </div>
    </div>

    <!-- Open Houses Content -->
    <div class="hph-dashboard-content">

        <!-- Upcoming Open Houses -->
        <div class="hph-dashboard-widget">
            <div class="hph-dashboard-widget-header">
                <h3 class="hph-dashboard-widget-title">
                    <i class="fas fa-calendar-check"></i>
                    <?php esc_html_e('Upcoming Open Houses', 'happy-place'); ?>
                </h3>
                <p class="hph-dashboard-widget-subtitle">
                    <?php printf(
                        esc_html__('You have %s upcoming open houses scheduled', 'happy-place'),
                        count($upcoming_open_houses)
                    ); ?>
                </p>
            </div>
            <div class="hph-dashboard-widget-content">
                <?php if (!empty($upcoming_open_houses)) : ?>
                    <div class="hph-open-houses-list">
                        <?php foreach ($upcoming_open_houses as $open_house) : ?>
                            <div class="hph-open-house-item">
                                <div class="hph-open-house-date">
                                    <div class="hph-date-display">
                                        <span class="hph-date-month"><?php echo date('M', strtotime($open_house['start_date'])); ?></span>
                                        <span class="hph-date-day"><?php echo date('j', strtotime($open_house['start_date'])); ?></span>
                                    </div>
                                </div>
                                <div class="hph-open-house-details">
                                    <h4 class="hph-open-house-title">
                                        <?php echo esc_html($open_house['listing_title'] ?? __('Open House', 'happy-place')); ?>
                                    </h4>
                                    <p class="hph-open-house-address">
                                        <?php echo esc_html($open_house['address'] ?? ''); ?>
                                    </p>
                                    <div class="hph-open-house-time">
                                        <i class="fas fa-clock"></i>
                                        <?php
                                        echo date('g:i A', strtotime($open_house['start_time'])) . ' - ' .
                                            date('g:i A', strtotime($open_house['end_time']));
                                        ?>
                                    </div>
                                </div>
                                <div class="hph-open-house-actions">
                                    <a href="<?php echo esc_url(add_query_arg(['action' => 'edit-open-house', 'id' => $open_house['id']])); ?>"
                                        class="hph-btn hph-btn--outline hph-btn--sm">
                                        <i class="fas fa-edit"></i>
                                        <?php esc_html_e('Edit', 'happy-place'); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="hph-empty-state">
                        <div class="hph-empty-state-icon">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <h4 class="hph-empty-state-title"><?php esc_html_e('No Upcoming Open Houses', 'happy-place'); ?></h4>
                        <p class="hph-empty-state-message">
                            <?php esc_html_e('Schedule your first open house to start attracting potential buyers.', 'happy-place'); ?>
                        </p>
                        <a href="<?php echo esc_url(add_query_arg('action', 'new-open-house')); ?>"
                            class="hph-btn hph-btn--primary">
                            <i class="fas fa-plus"></i>
                            <?php esc_html_e('Schedule Open House', 'happy-place'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Open Houses -->
        <?php if (!empty($past_open_houses)) : ?>
            <div class="hph-dashboard-widget">
                <div class="hph-dashboard-widget-header">
                    <h3 class="hph-dashboard-widget-title">
                        <i class="fas fa-history"></i>
                        <?php esc_html_e('Recent Open Houses', 'happy-place'); ?>
                    </h3>
                    <p class="hph-dashboard-widget-subtitle">
                        <?php esc_html_e('Your recent open house events and attendance', 'happy-place'); ?>
                    </p>
                </div>
                <div class="hph-dashboard-widget-content">
                    <div class="hph-open-houses-list">
                        <?php
                        $recent_past = array_slice($past_open_houses, 0, 5);
                        foreach ($recent_past as $open_house) :
                        ?>
                            <div class="hph-open-house-item hph-open-house-item--past">
                                <div class="hph-open-house-date">
                                    <div class="hph-date-display">
                                        <span class="hph-date-month"><?php echo date('M', strtotime($open_house['start_date'])); ?></span>
                                        <span class="hph-date-day"><?php echo date('j', strtotime($open_house['start_date'])); ?></span>
                                    </div>
                                </div>
                                <div class="hph-open-house-details">
                                    <h4 class="hph-open-house-title">
                                        <?php echo esc_html($open_house['listing_title'] ?? __('Open House', 'happy-place')); ?>
                                    </h4>
                                    <p class="hph-open-house-address">
                                        <?php echo esc_html($open_house['address'] ?? ''); ?>
                                    </p>
                                    <div class="hph-open-house-attendance">
                                        <i class="fas fa-users"></i>
                                        <?php
                                        printf(
                                            esc_html__('%s visitors', 'happy-place'),
                                            $open_house['actual_visitors'] ?? 0
                                        );
                                        ?>
                                    </div>
                                </div>
                                <div class="hph-open-house-status">
                                    <span class="hph-status-badge hph-status-badge--completed">
                                        <?php esc_html_e('Completed', 'happy-place'); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="hph-dashboard-widget-footer">
                    <a href="<?php echo esc_url(add_query_arg('view', 'all-open-houses')); ?>"
                        class="hph-btn hph-btn--outline hph-btn--sm">
                        <?php esc_html_e('View All Open Houses', 'happy-place'); ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>