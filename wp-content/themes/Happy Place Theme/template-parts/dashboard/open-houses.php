<?php
/**
 * Dashboard Open Houses Section
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get user's open houses
$current_user_id = get_current_user_id();
$upcoming_open_houses = get_posts([
    'post_type' => 'open_house',
    'author' => $current_user_id,
    'meta_query' => [
        [
            'key' => '_open_house_date',
            'value' => date('Y-m-d'),
            'compare' => '>='
        ]
    ],
    'orderby' => 'meta_value',
    'order' => 'ASC',
    'posts_per_page' => -1
]);

$past_open_houses = get_posts([
    'post_type' => 'open_house',
    'author' => $current_user_id,
    'meta_query' => [
        [
            'key' => '_open_house_date',
            'value' => date('Y-m-d'),
            'compare' => '<'
        ]
    ],
    'orderby' => 'meta_value',
    'order' => 'DESC',
    'posts_per_page' => 10
]);
?>

<div class="hph-dashboard-open-houses">
    
    <!-- Open Houses Header -->
    <div class="open-houses-header">
        <div class="header-content">
            <h2 class="page-title">Open Houses</h2>
            <p class="page-subtitle">Manage your scheduled open houses and events</p>
        </div>
        <div class="header-actions">
            <a href="#" class="action-btn action-btn--primary" onclick="scheduleNewOpenHouse()">
                <i class="fas fa-plus"></i> Schedule Open House
            </a>
        </div>
    </div>

    <!-- Open Houses Stats -->
    <div class="open-houses-stats">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-plus"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo count($upcoming_open_houses); ?></div>
                <div class="stat-label">Upcoming Events</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo count($past_open_houses); ?></div>
                <div class="stat-label">Completed Events</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value">127</div>
                <div class="stat-label">Total Attendees</div>
            </div>
        </div>
    </div>

    <!-- Upcoming Open Houses -->
    <div class="open-houses-section">
        <div class="section-header">
            <h3>Upcoming Open Houses</h3>
        </div>
        
        <?php if (!empty($upcoming_open_houses)) : ?>
            <div class="open-houses-grid">
                <?php foreach ($upcoming_open_houses as $open_house) : 
                    $listing_id = get_post_meta($open_house->ID, '_open_house_listing', true);
                    $date = get_post_meta($open_house->ID, '_open_house_date', true);
                    $start_time = get_post_meta($open_house->ID, '_open_house_start_time', true);
                    $end_time = get_post_meta($open_house->ID, '_open_house_end_time', true);
                ?>
                    <div class="open-house-card">
                        <div class="open-house-date">
                            <div class="date-day"><?php echo date('j', strtotime($date)); ?></div>
                            <div class="date-month"><?php echo date('M', strtotime($date)); ?></div>
                        </div>
                        
                        <div class="open-house-details">
                            <h4 class="open-house-title">
                                <a href="<?php echo get_permalink($listing_id); ?>">
                                    <?php echo get_the_title($listing_id); ?>
                                </a>
                            </h4>
                            <div class="open-house-time">
                                <i class="fas fa-clock"></i>
                                <?php echo date('g:i A', strtotime($start_time)); ?> - 
                                <?php echo date('g:i A', strtotime($end_time)); ?>
                            </div>
                            <div class="open-house-address">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo get_post_meta($listing_id, '_listing_address', true); ?>
                            </div>
                        </div>
                        
                        <div class="open-house-actions">
                            <a href="#" class="action-link" onclick="editOpenHouse(<?php echo $open_house->ID; ?>)">Edit</a>
                            <a href="#" class="action-link" onclick="viewAttendees(<?php echo $open_house->ID; ?>)">Attendees</a>
                            <a href="#" class="action-link action-link--danger" onclick="cancelOpenHouse(<?php echo $open_house->ID; ?>)">Cancel</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="empty-state">
                <i class="fas fa-calendar-alt"></i>
                <h4>No upcoming open houses</h4>
                <p>Schedule your first open house to start attracting potential buyers.</p>
                <a href="#" class="action-btn action-btn--primary" onclick="scheduleNewOpenHouse()">
                    Schedule Open House
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Open Houses -->
    <?php if (!empty($past_open_houses)) : ?>
    <div class="open-houses-section open-houses-past">
        <div class="section-header">
            <h3>Recent Open Houses</h3>
        </div>
        
        <div class="open-houses-table">
            <table class="hph-dashboard-table">
                <thead>
                    <tr>
                        <th>Property</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Attendees</th>
                        <th>Leads</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($past_open_houses, 0, 5) as $open_house) : 
                        $listing_id = get_post_meta($open_house->ID, '_open_house_listing', true);
                        $date = get_post_meta($open_house->ID, '_open_house_date', true);
                        $attendee_count = get_post_meta($open_house->ID, '_attendee_count', true) ?: 0;
                        $leads_count = get_post_meta($open_house->ID, '_leads_generated', true) ?: 0;
                    ?>
                        <tr>
                            <td>
                                <div class="property-cell">
                                    <strong><?php echo get_the_title($listing_id); ?></strong>
                                    <span class="address"><?php echo get_post_meta($listing_id, '_listing_address', true); ?></span>
                                </div>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($date)); ?></td>
                            <td>
                                <?php 
                                $start = get_post_meta($open_house->ID, '_open_house_start_time', true);
                                $end = get_post_meta($open_house->ID, '_open_house_end_time', true);
                                echo date('g:i A', strtotime($start)) . ' - ' . date('g:i A', strtotime($end));
                                ?>
                            </td>
                            <td><span class="metric-value"><?php echo $attendee_count; ?></span></td>
                            <td><span class="metric-value"><?php echo $leads_count; ?></span></td>
                            <td>
                                <a href="#" class="action-link" onclick="viewOpenHouseReport(<?php echo $open_house->ID; ?>)">
                                    View Report
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function scheduleNewOpenHouse() {
    // Implementation for scheduling new open house
    alert('Open house scheduling modal would open here');
}

function editOpenHouse(openHouseId) {
    // Implementation for editing open house
    console.log('Edit open house:', openHouseId);
}

function viewAttendees(openHouseId) {
    // Implementation for viewing attendees
    console.log('View attendees for:', openHouseId);
}

function cancelOpenHouse(openHouseId) {
    if (confirm('Are you sure you want to cancel this open house?')) {
        // Implementation for canceling open house
        console.log('Cancel open house:', openHouseId);
    }
}

function viewOpenHouseReport(openHouseId) {
    // Implementation for viewing detailed report
    console.log('View report for:', openHouseId);
}
</script>
